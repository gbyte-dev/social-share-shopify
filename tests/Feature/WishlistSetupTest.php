<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Services\ShopifyService;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WishlistSetupTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $store;

    protected string $testApiSecret = 'test_api_secret_67890_min_32_bytes_length';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'shopify.api_key' => 'test_api_key_12345',
            'shopify.api_secret' => $this->testApiSecret,
            'shopify.api_version' => '2025-01',
        ]);

        $this->store = ShopifyStore::create([
            'shop' => 'test-shop.myshopify.com',
            'access_token' => 'shpat_test_token_secret',
            'scope' => 'read_products,write_content,write_themes,read_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);
    }

    protected function generateSessionToken(array $overrides = []): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => "https://{$this->store->shop}/admin",
            'dest' => "https://{$this->store->shop}",
            'aud' => 'test_api_key_12345',
            'sub' => '12345678',
            'exp' => $now + 3600,
            'nbf' => $now - 10,
            'iat' => $now,
            'jti' => 'jwt_nonce_'.uniqid(),
        ], $overrides);

        return JWT::encode($payload, $this->testApiSecret, 'HS256');
    }

    protected function authStore(): self
    {
        $token = $this->generateSessionToken();

        return $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ])->withHeaders([
            'Authorization' => "Bearer {$token}",
        ]);
    }

    /**
     * Helper to mock Shopify GraphQL responses confirming templates/page.wishlist.json exists in active theme.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mockThemeWithWishlistTemplate(string $themeId = 'gid://shopify/OnlineStoreTheme/190549655919'): array
    {
        return [
            // 1. getThemes query
            [
                'data' => [
                    'themes' => [
                        'edges' => [
                            [
                                'node' => [
                                    'id' => $themeId,
                                    'name' => 'Horizon',
                                    'role' => 'MAIN',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // 2. getThemeFiles query (templates/page.wishlist.json exists)
            [
                'data' => [
                    'theme' => [
                        'id' => $themeId,
                        'name' => 'Horizon',
                        'role' => 'MAIN',
                        'files' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'filename' => 'templates/page.wishlist.json',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Helper to mock Shopify GraphQL responses when templates/page.wishlist.json is MISSING from active theme.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mockThemeWithoutWishlistTemplate(string $themeId = 'gid://shopify/OnlineStoreTheme/190549655919'): array
    {
        return [
            // 1. getThemes query
            [
                'data' => [
                    'themes' => [
                        'edges' => [
                            [
                                'node' => [
                                    'id' => $themeId,
                                    'name' => 'Horizon',
                                    'role' => 'MAIN',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // 2. getThemeFiles query (templates/page.wishlist.json missing)
            [
                'data' => [
                    'theme' => [
                        'id' => $themeId,
                        'name' => 'Horizon',
                        'role' => 'MAIN',
                        'files' => [
                            'edges' => [],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_unauthenticated_request_is_redirected_to_install(): void
    {
        $response = $this->get('/shopify/wishlist/setup');

        $response->assertRedirect(route('shopify.install'));
    }

    public function test_setup_creates_new_page_assigns_template_suffix_wishlist_and_redirects_to_page_wishlist_editor(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $themeMocks = $this->mockThemeWithWishlistTemplate();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                // 1. getThemes query
                ->push($themeMocks[0])
                // 2. getThemeFiles query (confirms templates/page.wishlist.json exists)
                ->push($themeMocks[1])
                // 3. getPagesByHandle query -> empty (page does not exist yet)
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                // 3b. getRecentPagesFallback query -> empty (confirms page does not exist in store)
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                // 4. pageCreate mutation -> page successfully created
                ->push([
                    'data' => [
                        'pageCreate' => [
                            'page' => [
                                'id' => 'gid://shopify/Page/12345678',
                                'title' => 'Wishlist',
                                'handle' => 'wishlist',
                                'isPublished' => true,
                            ],
                            'userErrors' => [],
                        ],
                    ],
                ])
                // 5. pageUpdate mutation -> templateSuffix assigned to wishlist
                ->push([
                    'data' => [
                        'pageUpdate' => [
                            'page' => [
                                'id' => 'gid://shopify/Page/12345678',
                                'title' => 'Wishlist',
                                'handle' => 'wishlist',
                                'templateSuffix' => 'wishlist',
                            ],
                            'userErrors' => [],
                        ],
                    ],
                ]),
        ]);

        $response = $this->authStore()->get('/shopify/wishlist/setup?target=mainSection');

        // Verify redirect to Shopify Theme Editor targeting dedicated template=page.wishlist
        $expectedDeepLink = 'https://test-shop.myshopify.com/admin/themes/current/editor?template=page.wishlist&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=mainSection';
        $response->assertRedirect($expectedDeepLink);

        $this->assertStringContainsString('template=page.wishlist', $expectedDeepLink);
        $this->assertStringNotContainsString('template=page&', $expectedDeepLink);
        $this->assertStringContainsString('addAppBlockId=test_api_key_12345%2Fwishlist-page', $expectedDeepLink);
        $this->assertStringContainsString('target=mainSection', $expectedDeepLink);

        // Verify pageUpdate mutation was invoked with templateSuffix: 'wishlist'
        Http::assertSent(function (Request $request) {
            return str_contains($request['query'] ?? '', 'pageUpdate')
                && ($request['variables']['page']['templateSuffix'] ?? '') === 'wishlist'
                && ($request['variables']['id'] ?? '') === 'gid://shopify/Page/12345678';
        });
    }

    public function test_setup_ajax_request_creates_page_and_returns_json_with_staged_deep_link(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $themeMocks = $this->mockThemeWithWishlistTemplate();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                ->push($themeMocks[0])
                ->push($themeMocks[1])
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                // Fallback direct listing query -> empty
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'pageCreate' => [
                            'page' => [
                                'id' => 'gid://shopify/Page/987654',
                                'title' => 'Wishlist',
                                'handle' => 'wishlist',
                                'isPublished' => true,
                            ],
                            'userErrors' => [],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'pageUpdate' => [
                            'page' => [
                                'id' => 'gid://shopify/Page/987654',
                                'title' => 'Wishlist',
                                'handle' => 'wishlist',
                                'templateSuffix' => 'wishlist',
                            ],
                            'userErrors' => [],
                        ],
                    ],
                ]),
        ]);

        $response = $this->authStore()->postJson('/shopify/wishlist/setup', [
            'target' => 'mainSection',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'created' => true,
            'staged' => true,
            'installed' => false, // Staged until merchant clicks Save in Theme Editor
            'template_suffix' => 'wishlist',
            'page' => [
                'id' => 'gid://shopify/Page/987654',
                'title' => 'Wishlist',
                'handle' => 'wishlist',
                'templateSuffix' => 'wishlist',
            ],
            'deep_link_url' => 'https://test-shop.myshopify.com/admin/themes/current/editor?template=page.wishlist&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=mainSection',
        ]);

        $this->assertFalse($response->json('installed'));
        $this->assertTrue($response->json('staged'));
        $this->assertSame('wishlist', $response->json('template_suffix'));
        $this->assertStringContainsString('template=page.wishlist', $response->json('deep_link_url'));
    }

    public function test_setup_reuses_existing_page_without_duplicate_creation_and_updates_template_suffix(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $themeMocks = $this->mockThemeWithWishlistTemplate();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                ->push($themeMocks[0])
                ->push($themeMocks[1])
                // getPagesByHandle finds existing page
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/555555',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                        'templateSuffix' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                // pageUpdate assigns templateSuffix = 'wishlist' to existing page
                ->push([
                    'data' => [
                        'pageUpdate' => [
                            'page' => [
                                'id' => 'gid://shopify/Page/555555',
                                'title' => 'Wishlist',
                                'handle' => 'wishlist',
                                'templateSuffix' => 'wishlist',
                            ],
                            'userErrors' => [],
                        ],
                    ],
                ]),
        ]);

        $response = $this->authStore()->postJson('/shopify/wishlist/setup');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'created' => false,
            'staged' => true,
            'installed' => false,
            'template_suffix' => 'wishlist',
            'page' => [
                'id' => 'gid://shopify/Page/555555',
                'title' => 'Wishlist',
                'handle' => 'wishlist',
                'templateSuffix' => 'wishlist',
            ],
        ]);

        // Assert pageCreate mutation was NEVER called (idempotency, no duplicate pages)
        Http::assertNotSent(function (Request $request) {
            return str_contains($request['query'] ?? '', 'pageCreate');
        });

        // Assert pageUpdate was called for the existing page ID
        Http::assertSent(function (Request $request) {
            return str_contains($request['query'] ?? '', 'pageUpdate')
                && ($request['variables']['id'] ?? '') === 'gid://shopify/Page/555555'
                && ($request['variables']['page']['templateSuffix'] ?? '') === 'wishlist';
        });
    }

    public function test_setup_succeeds_when_custom_json_template_is_missing_and_uses_default_page_template(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $themeMocks = $this->mockThemeWithoutWishlistTemplate();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                ->push($themeMocks[0])
                ->push($themeMocks[1]) // templates/page.wishlist.json is missing
                // 3. getPagesByHandle -> empty
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                // 4. getRecentPagesFallback -> empty
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                // 5. pageCreate -> creates page with default template (templateSuffix: null)
                ->push([
                    'data' => [
                        'pageCreate' => [
                            'page' => [
                                'id' => 'gid://shopify/Page/777888',
                                'title' => 'Wishlist',
                                'handle' => 'wishlist',
                                'templateSuffix' => null,
                                'isPublished' => true,
                            ],
                            'userErrors' => [],
                        ],
                    ],
                ]),
        ]);

        $response = $this->authStore()->postJson('/shopify/wishlist/setup');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'created' => true,
            'staged' => true,
            'installed' => false,
            'has_dedicated_template' => false,
            'template' => 'page',
            'template_suffix' => null,
            'page' => [
                'id' => 'gid://shopify/Page/777888',
                'title' => 'Wishlist',
                'handle' => 'wishlist',
            ],
            'deep_link_url' => 'https://test-shop.myshopify.com/admin/themes/current/editor?template=page&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=newAppsSection',
        ]);

        $this->assertStringContainsString('template=page&', $response->json('deep_link_url'));
        $this->assertStringNotContainsString('template=page.wishlist', $response->json('deep_link_url'));
        $this->assertStringContainsString('target=newAppsSection', $response->json('deep_link_url'));

        // Assert pageCreate WAS called to create the wishlist page
        Http::assertSent(function (Request $request) {
            return str_contains($request['query'] ?? '', 'pageCreate');
        });

        // Assert pageUpdate was NEVER called (never assign a nonexistent template)
        Http::assertNotSent(function (Request $request) {
            return str_contains($request['query'] ?? '', 'pageUpdate');
        });
    }

    public function test_privacy_choices_page_remains_on_default_template_without_wishlist_suffix(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';

        Http::fake([
            $graphqlEndpoint => Http::response([
                'data' => [
                    'pages' => [
                        'edges' => [
                            [
                                'node' => [
                                    'id' => 'gid://shopify/Page/708488298863',
                                    'title' => 'Your Privacy Choices',
                                    'handle' => 'data-sharing-opt-out',
                                    'templateSuffix' => null,
                                ],
                            ],
                            [
                                'node' => [
                                    'id' => 'gid://shopify/Page/710614909295',
                                    'title' => 'Wishlist',
                                    'handle' => 'wishlist',
                                    'templateSuffix' => 'wishlist',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        /** @var ShopifyService $service */
        $service = app(ShopifyService::class);
        $pages = $service->executeGraphQL($this->store, '{ pages(first: 10) { edges { node { id handle templateSuffix } } } }');

        $edges = $pages['data']['pages']['edges'] ?? [];
        $privacyChoices = collect($edges)->firstWhere('node.handle', 'data-sharing-opt-out');
        $wishlist = collect($edges)->firstWhere('node.handle', 'wishlist');

        $this->assertNotNull($privacyChoices);
        $this->assertNull($privacyChoices['node']['templateSuffix']); // Unassigned: uses standard page template

        $this->assertNotNull($wishlist);
        $this->assertSame('wishlist', $wishlist['node']['templateSuffix']); // Uses dedicated page.wishlist template
    }

    public function test_setup_supports_explicit_new_apps_section_target(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $themeMocks = $this->mockThemeWithWishlistTemplate();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                ->push($themeMocks[0])
                ->push($themeMocks[1])
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/111111',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'pageUpdate' => [
                            'page' => [
                                'id' => 'gid://shopify/Page/111111',
                                'title' => 'Wishlist',
                                'handle' => 'wishlist',
                                'templateSuffix' => 'wishlist',
                            ],
                            'userErrors' => [],
                        ],
                    ],
                ]),
        ]);

        $response = $this->authStore()->postJson('/shopify/wishlist/setup', [
            'target' => 'newAppsSection',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('target=newAppsSection', $response->json('deep_link_url'));
        $this->assertStringContainsString('template=page.wishlist', $response->json('deep_link_url'));
    }

    public function test_get_wishlist_theme_editor_deep_link_generates_correct_app_block_and_page_wishlist_template(): void
    {
        /** @var ShopifyService $service */
        $service = app(ShopifyService::class);

        // Explicit dedicated template and mainSection target
        $deepLink = $service->getWishlistThemeEditorDeepLink('my-custom-store.myshopify.com', 'mainSection', true, 'page.wishlist');

        $this->assertSame(
            'https://my-custom-store.myshopify.com/admin/themes/current/editor?template=page.wishlist&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=mainSection',
            $deepLink
        );

        $this->assertStringContainsString('template=page.wishlist', $deepLink);
        $this->assertStringNotContainsString('template=page&', $deepLink);
        $this->assertStringContainsString('addAppBlockId=test_api_key_12345%2Fwishlist-page', $deepLink);
        $this->assertStringContainsString('target=mainSection', $deepLink);

        // Default parameters (template=page, target=newAppsSection)
        $defaultDeepLink = $service->getWishlistThemeEditorDeepLink('my-custom-store.myshopify.com');
        $this->assertSame(
            'https://my-custom-store.myshopify.com/admin/themes/current/editor?template=page&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=newAppsSection',
            $defaultDeepLink
        );
        $this->assertStringContainsString('template=page', $defaultDeepLink);
        $this->assertStringContainsString('target=newAppsSection', $defaultDeepLink);

        // When block is already active ($addWishlistBlock = false), addAppBlockId is omitted to prevent duplicates
        $activeDeepLink = $service->getWishlistThemeEditorDeepLink('my-custom-store.myshopify.com', 'newAppsSection', false, 'page');
        $this->assertStringNotContainsString('addAppBlockId', $activeDeepLink);
        $this->assertStringContainsString('template=page', $activeDeepLink);
    }

    public function test_setup_handles_page_update_user_errors_from_shopify_gracefully(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $themeMocks = $this->mockThemeWithWishlistTemplate();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                ->push($themeMocks[0])
                ->push($themeMocks[1])
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/999999',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'pageUpdate' => [
                            'page' => null,
                            'userErrors' => [
                                [
                                    'field' => ['templateSuffix'],
                                    'message' => 'Template suffix is invalid',
                                ],
                            ],
                        ],
                    ],
                ]),
        ]);

        $response = $this->authStore()->postJson('/shopify/wishlist/setup');

        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
            'created' => false,
        ]);
    }

    public function test_support_page_renders_automated_wishlist_setup_card_and_guide(): void
    {
        $response = $this->authStore()->get('/shopify/support');

        $response->assertOk();
        $response->assertSee('Automated 1-Click Wishlist Setup');
        $response->assertSee('btn-auto-setup-wishlist');
        $response->assertSee('Auto-Setup Wishlist', false);
        $response->assertSee('How to Set Up Wishlist');
        $response->assertSee('Create a Wishlist Page in Shopify');
        $response->assertSee('Add the SocialNest Wishlist App Block');
        $response->assertSee('/pages/wishlist');
        $response->assertSee('SocialNest Wishlist');
        $response->assertSee('Important Notes');
        $response->assertSee('localStorage');
        $response->assertSee(route('shopify.wishlist.setup'));
    }

    /**
     * Regression Test for Issue A:
     * Reproduces the exact production sequence where getWishlistPage search query returns
     * empty edges due to Shopify search index lag, but fallback direct listing query locates
     * the existing Wishlist page and prevents duplicate pageCreate.
     */
    public function test_wishlist_setup_prevents_duplicate_page_creation_when_search_index_lags(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $themeMocks = $this->mockThemeWithWishlistTemplate();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                // Sequence Call 1:
                // Theme check -> template exists -> search query returns page 710614909295 -> reused
                ->push($themeMocks[0])
                ->push($themeMocks[1])
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/710614909295',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                        'templateSuffix' => 'wishlist',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                // Sequence Call 2:
                // Theme check -> template exists
                // 1) Primary search query returns empty edges [] due to Shopify search index lag!
                // 2) Fallback direct listing query returns existing page 710614909295
                ->push($themeMocks[0])
                ->push($themeMocks[1])
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/710614909295',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                        'templateSuffix' => 'wishlist',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
        ]);

        // Call 1 at 07:17:19: Existing Wishlist page found and reused
        $response1 = $this->authStore()->postJson('/shopify/wishlist/setup');
        $response1->assertOk();
        $this->assertFalse($response1->json('created'));
        $this->assertSame('gid://shopify/Page/710614909295', $response1->json('page.id'));

        // Call 2 at 07:21:53: Primary search returns empty, fallback locates page 710614909295 and reuses it
        $response2 = $this->authStore()->postJson('/shopify/wishlist/setup');
        $response2->assertOk();
        $this->assertFalse($response2->json('created'));
        $this->assertSame('gid://shopify/Page/710614909295', $response2->json('page.id'));

        // Verify that pageCreate was NEVER called, completely preventing duplicate page creation
        Http::assertNotSent(function (Request $request) {
            return str_contains($request->body(), 'pageCreate');
        });
    }

    /**
     * Helper to mock Shopify GraphQL responses when the Wishlist app block has been saved in the theme.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function mockThemeWithSavedWishlistBlock(string $themeId = 'gid://shopify/OnlineStoreTheme/190549655919'): array
    {
        return [
            // 1. getThemes query
            [
                'data' => [
                    'themes' => [
                        'edges' => [
                            [
                                'node' => [
                                    'id' => $themeId,
                                    'name' => 'Dawn',
                                    'role' => 'MAIN',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            // 2. getThemeFiles query
            [
                'data' => [
                    'theme' => [
                        'id' => $themeId,
                        'name' => 'Dawn',
                        'role' => 'MAIN',
                        'files' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'filename' => 'templates/page.json',
                                        'body' => [
                                            'content' => json_encode([
                                                'sections' => [
                                                    'main' => ['type' => 'main-page'],
                                                    'apps' => [
                                                        'type' => 'apps',
                                                        'blocks' => [
                                                            'wishlist_block' => [
                                                                'type' => 'shopify://apps/socialnest/blocks/wishlist-page/abcd',
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ]),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_status_endpoint_reports_missing_page_correctly(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                // getPagesByHandle -> empty
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                // getRecentPagesFallback -> empty
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ]),
        ]);

        $response = $this->authStore()->getJson('/shopify/wishlist/status');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'page_exists' => false,
            'block_active' => false,
            'page_url' => null,
            'setup_status' => 'not_configured',
        ]);
    }

    public function test_status_endpoint_reports_pending_activation_when_page_exists_but_block_not_saved(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $themeMocks = $this->mockThemeWithoutWishlistTemplate();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                // getPagesByHandle finds existing page
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/555555',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                        'templateSuffix' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                // Theme inspection queries (getThemes + getThemeFiles without wishlist-page)
                ->push($themeMocks[0])
                ->push($themeMocks[1]),
        ]);

        $response = $this->authStore()->getJson('/shopify/wishlist/status');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'page_exists' => true,
            'block_active' => false,
            'page_url' => 'https://test-shop.myshopify.com/pages/wishlist',
            'setup_status' => 'setup_pending',
        ]);
        $this->assertStringContainsString('addAppBlockId', $response->json('deep_link_url'));
    }

    public function test_status_endpoint_reports_active_state_when_page_exists_and_block_is_saved(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $savedThemeMocks = $this->mockThemeWithSavedWishlistBlock();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                // getPagesByHandle finds existing page
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/555555',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                        'templateSuffix' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                // Theme inspection queries (getThemes + getThemeFiles with wishlist-page present)
                ->push($savedThemeMocks[0])
                ->push($savedThemeMocks[1]),
        ]);

        $response = $this->authStore()->getJson('/shopify/wishlist/status');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'page_exists' => true,
            'block_active' => true,
            'page_url' => 'https://test-shop.myshopify.com/pages/wishlist',
            'setup_status' => 'active',
        ]);
        // When already active, deep link omits addAppBlockId to prevent duplicate blocks
        $this->assertStringNotContainsString('addAppBlockId', $response->json('deep_link_url'));
    }

    public function test_merchant_closing_theme_editor_without_saving_leaves_status_as_pending(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $unsavedThemeMocks = $this->mockThemeWithoutWishlistTemplate();

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                // Page exists (created in step 2 of setup)
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/555555',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                        'templateSuffix' => null,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ])
                // But merchant closed editor without saving, so theme files do not contain block
                ->push($unsavedThemeMocks[0])
                ->push($unsavedThemeMocks[1]),
        ]);

        $response = $this->authStore()->getJson('/shopify/wishlist/status');

        $response->assertOk();
        $this->assertTrue($response->json('page_exists'));
        $this->assertFalse($response->json('block_active'));
        $this->assertSame('setup_pending', $response->json('setup_status'));
    }

    public function test_unauthorized_request_to_status_is_rejected(): void
    {
        $response = $this->get('/shopify/wishlist/status');

        $response->assertRedirect(route('shopify.install'));
    }

    public function test_cross_tenant_request_cannot_access_other_store_wishlist_status(): void
    {
        // Store 2
        $store2 = ShopifyStore::create([
            'shop' => 'other-shop.myshopify.com',
            'access_token' => 'shpat_other_store_token',
            'scope' => 'read_products,write_content,write_themes,read_themes',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $tokenStore2 = JWT::encode([
            'iss' => "https://{$store2->shop}/admin",
            'dest' => "https://{$store2->shop}",
            'aud' => 'test_api_key_12345',
            'sub' => '99999999',
            'exp' => time() + 3600,
            'nbf' => time() - 10,
            'iat' => time(),
            'jti' => 'jwt_nonce_'.uniqid(),
        ], $this->testApiSecret, 'HS256');

        $graphqlEndpointStore2 = 'https://other-shop.myshopify.com/admin/api/2025-01/graphql.json';
        Http::fake([
            $graphqlEndpointStore2 => Http::sequence()
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ]),
        ]);

        // Request with Store 2's credentials
        $response = $this->withSession([
            'shopify_shop' => $store2->shop,
            'shopify_store_id' => $store2->id,
        ])->withHeaders([
            'Authorization' => "Bearer {$tokenStore2}",
        ])->getJson('/shopify/wishlist/status');

        $response->assertOk();
        // Verifies store 2 cannot see store 1's pages or status
        $this->assertFalse($response->json('page_exists'));
        $this->assertSame('not_configured', $response->json('setup_status'));
    }
}
