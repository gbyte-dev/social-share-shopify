<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Services\ShopifyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WishlistSetupTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $store;

    protected function setUp(): void
    {
        parent::setUp();

        config(['shopify.api_key' => 'test_api_key_12345']);
        config(['shopify.api_version' => '2025-01']);

        $this->store = ShopifyStore::create([
            'shop' => 'test-shop.myshopify.com',
            'access_token' => 'shpat_test_token_secret',
            'scope' => 'read_products,write_content',
            'is_active' => true,
            'installed_at' => now(),
        ]);
    }

    protected function authStore(): self
    {
        return $this->withSession([
            'shopify_shop' => $this->store->shop,
            'shopify_store_id' => $this->store->id,
        ]);
    }

    public function test_unauthenticated_request_is_redirected_to_install(): void
    {
        $response = $this->get('/shopify/wishlist/setup');

        $response->assertRedirect(route('shopify.install'));
    }

    public function test_setup_creates_new_page_via_pagecreate_mutation_when_page_does_not_exist_and_redirects(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';

        Http::fake([
            $graphqlEndpoint => Http::sequence()
                // 1. getPagesByHandle query -> empty (page does not exist)
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [],
                        ],
                    ],
                ])
                // 2. pageCreate mutation -> page successfully created
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
                ]),
        ]);

        $response = $this->authStore()->get('/shopify/wishlist/setup');

        // Verify redirect to Shopify Theme Editor deep link
        $expectedDeepLink = 'https://test-shop.myshopify.com/admin/themes/current/editor?template=page&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=mainSection';
        $response->assertRedirect($expectedDeepLink);

        // Verify both GraphQL calls were made with proper authorization headers
        Http::assertSentCount(2);

        Http::assertSent(function (Request $request) {
            return str_contains($request['query'], 'getPagesByHandle')
                && $request->header('X-Shopify-Access-Token')[0] === 'shpat_test_token_secret';
        });

        Http::assertSent(function (Request $request) {
            return str_contains($request['query'], 'pageCreate')
                && $request['variables']['page']['handle'] === 'wishlist'
                && $request['variables']['page']['isPublished'] === true;
        });
    }

    public function test_setup_ajax_request_creates_page_and_returns_json_with_deep_link(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';

        Http::fake([
            $graphqlEndpoint => Http::sequence()
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
                ]),
        ]);

        $response = $this->authStore()->postJson('/shopify/wishlist/setup', [
            'target' => 'mainSection',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'created' => true,
            'page' => [
                'id' => 'gid://shopify/Page/987654',
                'title' => 'Wishlist',
                'handle' => 'wishlist',
            ],
            'deep_link_url' => 'https://test-shop.myshopify.com/admin/themes/current/editor?template=page&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=mainSection',
        ]);
    }

    public function test_setup_preserves_existing_page_without_calling_pagecreate_mutation(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';

        Http::fake([
            $graphqlEndpoint => Http::response([
                'data' => [
                    'pages' => [
                        'edges' => [
                            [
                                'node' => [
                                    'id' => 'gid://shopify/Page/55555',
                                    'title' => 'Existing Custom Wishlist',
                                    'handle' => 'wishlist',
                                ],
                            ],
                        ],
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
            'created' => false,
            'message' => 'Existing Wishlist page found and preserved.',
            'page' => [
                'id' => 'gid://shopify/Page/55555',
                'title' => 'Existing Custom Wishlist',
                'handle' => 'wishlist',
            ],
            'deep_link_url' => 'https://test-shop.myshopify.com/admin/themes/current/editor?template=page&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=mainSection',
        ]);

        // Only 1 GraphQL call should have been made (check query); pageCreate must NOT be called
        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) {
            return str_contains($request['query'], 'getPagesByHandle');
        });
        Http::assertNotSent(function (Request $request) {
            return str_contains($request['query'], 'pageCreate');
        });
    }

    public function test_setup_supports_explicit_new_apps_section_target(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';

        Http::fake([
            $graphqlEndpoint => Http::response([
                'data' => [
                    'pages' => [
                        'edges' => [
                            [
                                'node' => [
                                    'id' => 'gid://shopify/Page/55555',
                                    'title' => 'Wishlist',
                                    'handle' => 'wishlist',
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->authStore()->postJson('/shopify/wishlist/setup', [
            'target' => 'newAppsSection',
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'deep_link_url' => 'https://test-shop.myshopify.com/admin/themes/current/editor?template=page&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=newAppsSection',
        ]);
    }

    public function test_setup_handles_user_errors_from_shopify_gracefully(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';

        Http::fake([
            $graphqlEndpoint => Http::sequence()
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
                            'page' => null,
                            'userErrors' => [
                                [
                                    'field' => ['handle'],
                                    'message' => 'Handle is invalid or restricted',
                                ],
                            ],
                        ],
                    ],
                ]),
        ]);

        $response = $this->authStore()->postJson('/shopify/wishlist/setup');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'created' => false,
        ]);
        $this->assertNotEmpty($response->json('deep_link_url'));
    }

    public function test_execute_graphql_automatically_migrates_legacy_non_expiring_token_on_403(): void
    {
        $graphqlEndpoint = 'https://test-shop.myshopify.com/admin/api/2025-01/graphql.json';
        $tokenExchangeEndpoint = 'https://test-shop.myshopify.com/admin/oauth/access_token';

        Http::fake([
            // 1. Initial GraphQL call returns 403 legacy non-expiring token error
            $graphqlEndpoint => Http::sequence()
                ->push([
                    'errors' => '[API] Non-expiring access tokens are no longer accepted for the Admin API. Start using expiring offline tokens: https://shopify.dev/docs/apps/build/authentication-authorization/access-tokens/offline-access-tokens#expiring-vs-non-expiring-offline-tokens',
                ], 403)
                // 3. Retry with new token succeeds
                ->push([
                    'data' => [
                        'pages' => [
                            'edges' => [
                                [
                                    'node' => [
                                        'id' => 'gid://shopify/Page/999',
                                        'title' => 'Wishlist',
                                        'handle' => 'wishlist',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
            // 2. Token exchange returns new expiring access token
            $tokenExchangeEndpoint => Http::response([
                'access_token' => 'shpua_migrated_token_new',
                'scope' => 'read_products,write_content',
                'expires_in' => 3600,
                'refresh_token' => 'shprt_migrated_refresh_token',
                'refresh_token_expires_in' => 7776000,
            ]),
        ]);

        /** @var ShopifyService $service */
        $service = app(ShopifyService::class);

        $page = $service->getWishlistPage($this->store);

        $this->assertNotNull($page);
        $this->assertSame('wishlist', $page['handle']);

        // Verify the store model's access token was updated to the new token
        $this->store->refresh();
        $this->assertSame('shpua_migrated_token_new', $this->store->access_token);
    }

    public function test_get_wishlist_theme_editor_deep_link_method(): void
    {
        /** @var ShopifyService $service */
        $service = app(ShopifyService::class);

        $link = $service->getWishlistThemeEditorDeepLink('my-custom-store.myshopify.com', 'mainSection');

        $this->assertSame(
            'https://my-custom-store.myshopify.com/admin/themes/current/editor?template=page&addAppBlockId=test_api_key_12345%2Fwishlist-page&target=mainSection',
            $link
        );
    }

    public function test_support_page_renders_automated_wishlist_setup_card(): void
    {
        $response = $this->authStore()->get('/shopify/support');

        $response->assertOk();
        $response->assertSee('Automated 1-Click Wishlist Setup');
        $response->assertSee('btn-auto-setup-wishlist');
        $response->assertSee('Auto-Setup Wishlist', false);
        $response->assertSee(route('shopify.wishlist.setup'));
    }
}
