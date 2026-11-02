<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinksCrudTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $storeA;

    protected ShopifyStore $storeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeA = ShopifyStore::create([
            'shop' => 'store-alpha.myshopify.com',
            'access_token' => 'token_alpha',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->storeB = ShopifyStore::create([
            'shop' => 'store-beta.myshopify.com',
            'access_token' => 'token_beta',
            'scope' => 'read_products',
            'is_active' => true,
            'installed_at' => now(),
        ]);
    }

    protected function authStoreA(): self
    {
        return $this->withSession([
            'shopify_shop' => $this->storeA->shop,
            'shopify_store_id' => $this->storeA->id,
        ]);
    }

    protected function authStoreB(): self
    {
        return $this->withSession([
            'shopify_shop' => $this->storeB->shop,
            'shopify_store_id' => $this->storeB->id,
        ]);
    }

    public function test_unauthenticated_request_is_redirected_to_install(): void
    {
        $response = $this->get('/shopify/social-links');

        $response->assertRedirect(route('shopify.install'));
    }

    public function test_authenticated_merchant_can_view_social_links_index_empty_state(): void
    {
        $response = $this->authStoreA()->get('/shopify/social-links');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.social-links.index');
        $response->assertSee('No social links configured yet');
        $response->assertSee('store-alpha.myshopify.com');
    }

    public function test_authenticated_merchant_can_view_create_page(): void
    {
        $response = $this->authStoreA()->get('/shopify/social-links/create');

        $response->assertStatus(200);
        $response->assertViewIs('shopify.social-links.create');
        $response->assertSee('Add Social Link');
        $response->assertSee('Instagram');
        $response->assertSee('WhatsApp');
    }

    public function test_merchant_can_create_valid_social_link(): void
    {
        $payload = [
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/alphaboutique',
            'label' => 'Follow on Instagram',
            'is_active' => '1',
            'sort_order' => 1,
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertRedirect(route('shopify.social-links.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/alphaboutique',
            'label' => 'Follow on Instagram',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_merchant_can_create_valid_whatsapp_link(): void
    {
        $payload = [
            'platform' => 'whatsapp',
            'url' => 'https://wa.me/15551234567?text=Hello',
            'label' => 'Chat with us',
            'is_active' => '1',
            'sort_order' => 2,
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'whatsapp',
            'url' => 'https://wa.me/15551234567?text=Hello',
        ]);
    }

    public function test_rejects_unsupported_platform(): void
    {
        $payload = [
            'platform' => 'myspace',
            'url' => 'https://myspace.com/profile',
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertSessionHasErrors(['platform']);
        $this->assertDatabaseMissing('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'myspace',
        ]);
    }

    public function test_rejects_dangerous_or_invalid_url_schemes(): void
    {
        $payload = [
            'platform' => 'instagram',
            'url' => 'javascript:alert("hacked")',
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertSessionHasErrors(['url']);
        $this->assertDatabaseMissing('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
        ]);
    }

    public function test_instagram_can_save_any_valid_destination_url(): void
    {
        $payload = [
            'platform' => 'instagram',
            'url' => 'https://custom-domain.example.com/social/instagram-landing',
            'label' => 'Instagram Hub',
            'is_active' => '1',
            'sort_order' => 1,
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://custom-domain.example.com/social/instagram-landing',
        ]);
    }

    public function test_facebook_can_save_any_valid_destination_url(): void
    {
        $payload = [
            'platform' => 'facebook',
            'url' => 'https://community.mystore.com/groups/vip-members',
            'label' => 'VIP Community',
            'is_active' => '1',
            'sort_order' => 2,
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'facebook',
            'url' => 'https://community.mystore.com/groups/vip-members',
        ]);
    }

    public function test_youtube_can_save_any_valid_destination_url(): void
    {
        $payload = [
            'platform' => 'youtube',
            'url' => 'https://livestream.videohost.org/watch?channel=alpha',
            'label' => 'Live Shows',
            'is_active' => '1',
            'sort_order' => 3,
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'youtube',
            'url' => 'https://livestream.videohost.org/watch?channel=alpha',
        ]);
    }

    public function test_pinterest_can_save_any_valid_destination_url(): void
    {
        $payload = [
            'platform' => 'pinterest',
            'url' => 'https://lookbook.fashionbrand.net/collections/spring-2026',
            'label' => 'Inspiration Lookbook',
            'is_active' => '1',
            'sort_order' => 4,
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'pinterest',
            'url' => 'https://lookbook.fashionbrand.net/collections/spring-2026',
        ]);
    }

    public function test_tiktok_can_save_any_valid_destination_url(): void
    {
        $payload = [
            'platform' => 'tiktok',
            'url' => 'https://linkinbio.app/tiktok-special-deals',
            'label' => 'TikTok Deals',
            'is_active' => '1',
            'sort_order' => 5,
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'tiktok',
            'url' => 'https://linkinbio.app/tiktok-special-deals',
        ]);
    }

    public function test_x_can_save_any_valid_destination_url(): void
    {
        $payload = [
            'platform' => 'x',
            'url' => 'https://updates.externalnews.com/feed/company-announcements',
            'label' => 'Press & Updates',
            'is_active' => '1',
            'sort_order' => 6,
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'x',
            'url' => 'https://updates.externalnews.com/feed/company-announcements',
        ]);
    }

    public function test_whatsapp_can_save_any_valid_destination_url(): void
    {
        $payload = [
            'platform' => 'whatsapp',
            'url' => 'https://supportdesk.customerhelp.io/live-chat/whatsapp',
            'label' => 'Customer Care',
            'is_active' => '1',
            'sort_order' => 7,
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'whatsapp',
            'url' => 'https://supportdesk.customerhelp.io/live-chat/whatsapp',
        ]);
    }

    public function test_http_and_https_urls_are_accepted(): void
    {
        $httpPayload = [
            'platform' => 'pinterest',
            'url' => 'http://insecure-example.org/pins',
            'label' => 'HTTP Link',
        ];

        $responseHttp = $this->authStoreA()->post('/shopify/social-links', $httpPayload);
        $responseHttp->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'pinterest',
            'url' => 'http://insecure-example.org/pins',
        ]);

        $httpsPayload = [
            'platform' => 'facebook',
            'url' => 'https://secure-example.com/feed',
            'label' => 'HTTPS Link',
        ];

        $responseHttps = $this->authStoreA()->post('/shopify/social-links', $httpsPayload);
        $responseHttps->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'facebook',
            'url' => 'https://secure-example.com/feed',
        ]);
    }

    public function test_rejects_empty_destination_url(): void
    {
        $payload = [
            'platform' => 'instagram',
            'url' => '   ',
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);
        $response->assertSessionHasErrors(['url']);
    }

    public function test_rejects_malformed_url(): void
    {
        $payload = [
            'platform' => 'instagram',
            'url' => 'not-a-valid-url-without-domain',
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);
        $response->assertSessionHasErrors(['url']);
    }

    public function test_rejects_dangerous_schemes_including_javascript_data_and_file(): void
    {
        $schemes = [
            'javascript:alert(1)',
            'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
            'file:///etc/passwd',
            'vbscript:msgbox(1)',
        ];

        foreach ($schemes as $schemeUrl) {
            $response = $this->authStoreA()->post('/shopify/social-links', [
                'platform' => 'instagram',
                'url' => $schemeUrl,
            ]);

            $response->assertSessionHasErrors(['url']);
        }
    }

    public function test_rejects_duplicate_platform_selection_for_same_store(): void
    {
        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/first',
            'label' => 'First',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $payload = [
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/second',
            'label' => 'Second',
        ];

        $response = $this->authStoreA()->post('/shopify/social-links', $payload);

        $response->assertSessionHasErrors(['platform']);
        $this->assertEquals(1, SocialLink::where('shopify_store_id', $this->storeA->id)->where('platform', 'instagram')->count());
    }

    public function test_authenticated_merchant_can_edit_and_update_link(): void
    {
        $link = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'youtube',
            'url' => 'https://www.youtube.com/@initial',
            'label' => 'Initial Label',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $editResponse = $this->authStoreA()->get("/shopify/social-links/{$link->id}/edit");
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Social Link');
        $editResponse->assertSee('YouTube');

        $updateResponse = $this->authStoreA()->put("/shopify/social-links/{$link->id}", [
            'platform' => 'youtube',
            'url' => 'https://www.youtube.com/@updatedchannel',
            'label' => 'Updated Label',
            'is_active' => '0',
            'sort_order' => 5,
        ]);

        $updateResponse->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'id' => $link->id,
            'shopify_store_id' => $this->storeA->id,
            'url' => 'https://www.youtube.com/@updatedchannel',
            'label' => 'Updated Label',
            'is_active' => false,
            'sort_order' => 5,
        ]);
    }

    public function test_merchant_can_toggle_link_active_status(): void
    {
        $link = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'tiktok',
            'url' => 'https://www.tiktok.com/@alphaboutique',
            'label' => 'TikTok',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->authStoreA()->post("/shopify/social-links/{$link->id}/toggle");

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', [
            'id' => $link->id,
            'is_active' => false,
        ]);

        // Toggle back to true
        $this->authStoreA()->post("/shopify/social-links/{$link->id}/toggle");
        $this->assertDatabaseHas('social_links', [
            'id' => $link->id,
            'is_active' => true,
        ]);
    }

    public function test_merchant_can_delete_link(): void
    {
        $link = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'facebook',
            'url' => 'https://www.facebook.com/alphaboutique',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->authStoreA()->delete("/shopify/social-links/{$link->id}");

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseMissing('social_links', [
            'id' => $link->id,
        ]);
    }

    public function test_merchant_can_reorder_links(): void
    {
        $link1 = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/alpha',
            'sort_order' => 1,
        ]);
        $link2 = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'facebook',
            'url' => 'https://www.facebook.com/alpha',
            'sort_order' => 2,
        ]);

        $response = $this->authStoreA()->post('/shopify/social-links/reorder', [
            'order' => [
                $link2->id => 1,
                $link1->id => 2,
            ],
        ]);

        $response->assertRedirect(route('shopify.social-links.index'));
        $this->assertDatabaseHas('social_links', ['id' => $link2->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('social_links', ['id' => $link1->id, 'sort_order' => 2]);
    }

    public function test_multi_tenant_isolation_prevents_cross_store_access(): void
    {
        $linkA = SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/storea',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Store B tries to view edit form of Store A's link
        $response = $this->authStoreB()->get("/shopify/social-links/{$linkA->id}/edit");
        $response->assertStatus(404);

        // Store B tries to update Store A's link
        $updateResponse = $this->authStoreB()->put("/shopify/social-links/{$linkA->id}", [
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/storeb_hijack',
        ]);
        $updateResponse->assertStatus(404);
        $this->assertDatabaseMissing('social_links', ['url' => 'https://www.instagram.com/storeb_hijack']);

        // Store B tries to toggle Store A's link
        $toggleResponse = $this->authStoreB()->post("/shopify/social-links/{$linkA->id}/toggle");
        $toggleResponse->assertStatus(404);

        // Store B tries to delete Store A's link
        $deleteResponse = $this->authStoreB()->delete("/shopify/social-links/{$linkA->id}");
        $deleteResponse->assertStatus(404);
        $this->assertDatabaseHas('social_links', ['id' => $linkA->id]);

        // Store B views index: Store A's link should NOT appear
        $indexResponse = $this->authStoreB()->get('/shopify/social-links');
        $indexResponse->assertDontSee('https://www.instagram.com/storea');
    }

    public function test_dashboard_summary_cards_reflect_real_database_records(): void
    {
        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'instagram',
            'url' => 'https://www.instagram.com/alpha',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        SocialLink::create([
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'tiktok',
            'url' => 'https://www.tiktok.com/@alpha',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        // Store B also has links that must not inflate Store A's counts
        SocialLink::create([
            'shopify_store_id' => $this->storeB->id,
            'platform' => 'youtube',
            'url' => 'https://www.youtube.com/@beta',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->authStoreA()->get('/shopify/dashboard');
        $response->assertStatus(200);

        // Store A has 2 total links: 1 active, 1 inactive, 5 available platforms remaining
        $response->assertSee('2'); // Total Links
        $response->assertSee('1'); // Active Links
        $response->assertSee('5'); // Available Platforms
        $response->assertSee('https://www.instagram.com/alpha');
        $response->assertDontSee('https://www.youtube.com/@beta');
    }
}
