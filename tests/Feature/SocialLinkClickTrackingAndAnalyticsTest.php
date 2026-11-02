<?php

namespace Tests\Feature;

use App\Models\ShopifyStore;
use App\Models\SocialLink;
use App\Models\SocialLinkClick;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinkClickTrackingAndAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected ShopifyStore $storeA;

    protected ShopifyStore $storeB;

    protected SocialLink $linkInstagram;

    protected SocialLink $linkWhatsApp;

    protected SocialLink $linkInactive;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Store A
        $this->storeA = ShopifyStore::create([
            'shop' => 'store-a.myshopify.com',
            'access_token' => 'shpat_store_a_valid_token',
            'is_active' => true,
            'installed_at' => now(),
            'scope' => 'read_products,write_products',
        ]);

        // Create Store B
        $this->storeB = ShopifyStore::create([
            'shop' => 'store-b.myshopify.com',
            'access_token' => 'shpat_store_b_valid_token',
            'is_active' => true,
            'installed_at' => now(),
            'scope' => 'read_products,write_products',
        ]);

        // Create Social Links for Store A
        $this->linkInstagram = $this->storeA->socialLinks()->create([
            'platform' => 'instagram',
            'platform_type' => 'builtin',
            'url' => 'https://instagram.com/store_a',
            'label' => 'Instagram',
            'brand_color' => '#E1306C',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->linkWhatsApp = $this->storeA->socialLinks()->create([
            'platform' => 'whatsapp',
            'platform_type' => 'builtin',
            'url' => 'https://wa.me/1234567890',
            'label' => 'WhatsApp Chat',
            'brand_color' => '#25D366',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->linkInactive = $this->storeA->socialLinks()->create([
            'platform' => 'tiktok',
            'platform_type' => 'builtin',
            'url' => 'https://tiktok.com/@store_a',
            'label' => 'TikTok (Draft)',
            'brand_color' => '#000000',
            'is_active' => false,
            'sort_order' => 3,
        ]);
    }

    /**
     * 1. Test click event recording with link_type and clicked_at.
     */
    public function test_records_click_event_with_link_type_and_clicked_at(): void
    {
        $response = $this->postJson(route('api.storefront.track-click'), [
            'shop' => $this->storeA->shop,
            'link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('social_link_clicks', [
            'shopify_store_id' => $this->storeA->id,
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
        ]);

        $click = SocialLinkClick::first();
        $this->assertNotNull($click->clicked_at);
        $this->assertNull($click->product_id);
    }

    /**
     * 2. Test correct shop and social link ownership validation.
     */
    public function test_rejects_click_when_link_does_not_belong_to_requested_shop(): void
    {
        // Link from Store A requested against Store B
        $response = $this->postJson(route('api.storefront.track-click'), [
            'shop' => $this->storeB->shop,
            'link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
        ]);

        $response->assertStatus(404)
            ->assertJson(['error' => 'Social link not found for this store']);

        $this->assertDatabaseCount('social_link_clicks', 0);
    }

    /**
     * 3. Test invalid or missing link IDs.
     */
    public function test_handles_missing_or_invalid_link_id_gracefully(): void
    {
        // Non-existent link ID
        $response = $this->postJson(route('api.storefront.track-click'), [
            'shop' => $this->storeA->shop,
            'link_id' => 999999,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
        ]);

        $response->assertStatus(404);

        // Missing link ID but valid active platform for store
        $response2 = $this->postJson(route('api.storefront.track-click'), [
            'shop' => $this->storeA->shop,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
        ]);

        $response2->assertStatus(200);
        $this->assertDatabaseHas('social_link_clicks', [
            'shopify_store_id' => $this->storeA->id,
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
        ]);
    }

    /**
     * 4. Test cross-shop analytics isolation.
     */
    public function test_cross_shop_analytics_isolation(): void
    {
        // Create clicks for Store A
        $this->storeA->clicks()->create([
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
            'clicked_at' => now(),
        ]);
        $this->storeA->clicks()->create([
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
            'clicked_at' => now(),
        ]);

        // Create click for Store B
        $this->storeB->clicks()->create([
            'platform' => 'facebook',
            'link_type' => 'floating_dock',
            'clicked_at' => now(),
        ]);

        $analyticsService = app(AnalyticsService::class);

        $analyticsA = $analyticsService->getDashboardAnalytics($this->storeA);
        $analyticsB = $analyticsService->getDashboardAnalytics($this->storeB);

        $this->assertEquals(2, $analyticsA['summary']['total_clicks']);
        $this->assertEquals(1, $analyticsB['summary']['total_clicks']);

        // Verify Store B has 0 instagram clicks
        $storeBPlatforms = collect($analyticsB['platform_chart']['platforms'])->pluck('platform')->toArray();
        $this->assertNotContains('instagram', $storeBPlatforms);
    }

    /**
     * 5. Test daily and weekly aggregation (including days with zero clicks).
     */
    public function test_weekly_aggregation_includes_all_seven_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-28 12:00:00')); // Wednesday

        // Add 3 clicks on Wednesday
        for ($i = 0; $i < 3; $i++) {
            $this->storeA->clicks()->create([
                'social_link_id' => $this->linkInstagram->id,
                'platform' => 'instagram',
                'link_type' => 'floating_dock',
                'clicked_at' => now(),
            ]);
        }

        // Add 2 clicks on Monday of current week
        for ($i = 0; $i < 2; $i++) {
            $this->storeA->clicks()->create([
                'social_link_id' => $this->linkWhatsApp->id,
                'platform' => 'whatsapp',
                'link_type' => 'floating_dock',
                'clicked_at' => Carbon::parse('2026-10-26 10:00:00'),
            ]);
        }

        $analyticsService = app(AnalyticsService::class);
        $weeklyChart = $analyticsService->getWeeklyClicksChart($this->storeA);

        $this->assertCount(7, $weeklyChart['days']);
        $this->assertEquals(5, $weeklyChart['total']);
        $this->assertTrue($weeklyChart['has_data']);

        // Monday should have 2 clicks
        $this->assertEquals(2, $weeklyChart['days'][0]['clicks']);
        $this->assertEquals('Mon', $weeklyChart['days'][0]['day_name']);

        // Tuesday should have 0 clicks
        $this->assertEquals(0, $weeklyChart['days'][1]['clicks']);
        $this->assertEquals('Tue', $weeklyChart['days'][1]['day_name']);

        // Wednesday should have 3 clicks
        $this->assertEquals(3, $weeklyChart['days'][2]['clicks']);
        $this->assertTrue($weeklyChart['days'][2]['is_today']);

        Carbon::setTestNow();
    }

    /**
     * 6. Test zero-click social links appear in performance table.
     */
    public function test_zero_click_social_links_appear_in_performance_table(): void
    {
        // Instagram has 1 click, WhatsApp and TikTok have 0 clicks
        $this->storeA->clicks()->create([
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
            'clicked_at' => now(),
        ]);

        $analyticsService = app(AnalyticsService::class);
        $analytics = $analyticsService->getDashboardAnalytics($this->storeA);

        $links = $analytics['social_links']['links'];
        $this->assertCount(3, $links);

        $whatsappItem = collect($links)->firstWhere('platform', 'whatsapp');
        $this->assertNotNull($whatsappItem);
        $this->assertEquals(0, $whatsappItem['total_clicks']);
        $this->assertEquals('Never', $whatsappItem['last_clicked_formatted']);

        $tiktokItem = collect($links)->firstWhere('platform', 'tiktok');
        $this->assertNotNull($tiktokItem);
        $this->assertFalse($tiktokItem['is_active']);
    }

    /**
     * 7. Test date range filtering (Last 7 Days, Last 30 Days, Last 90 Days, Custom).
     */
    public function test_date_range_filters_respect_boundaries(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-30 12:00:00'));

        // Click today (Oct 30)
        $this->storeA->clicks()->create([
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
            'clicked_at' => Carbon::parse('2026-10-30 10:00:00'),
        ]);

        // Click 10 days ago (Oct 20) -> outside last 7 days, inside last 30 days
        $this->storeA->clicks()->create([
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
            'clicked_at' => Carbon::parse('2026-10-20 10:00:00'),
        ]);

        // Click 45 days ago (Sep 15) -> outside last 30 days, inside last 90 days
        $this->storeA->clicks()->create([
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
            'clicked_at' => Carbon::parse('2026-09-15 10:00:00'),
        ]);

        $analyticsService = app(AnalyticsService::class);

        // Last 7 Days
        $res7 = $analyticsService->getDashboardAnalytics($this->storeA, ['range' => 'last_7_days']);
        $this->assertEquals(1, $res7['summary']['total_clicks']);

        // Last 30 Days
        $res30 = $analyticsService->getDashboardAnalytics($this->storeA, ['range' => 'last_30_days']);
        $this->assertEquals(2, $res30['summary']['total_clicks']);

        // Last 90 Days
        $res90 = $analyticsService->getDashboardAnalytics($this->storeA, ['range' => 'last_90_days']);
        $this->assertEquals(3, $res90['summary']['total_clicks']);

        // Custom Date Range (Oct 18 to Oct 22)
        $resCustom = $analyticsService->getDashboardAnalytics($this->storeA, [
            'range' => 'custom',
            'start_date' => '2026-10-18',
            'end_date' => '2026-10-22',
        ]);
        $this->assertEquals(1, $resCustom['summary']['total_clicks']);

        Carbon::setTestNow();
    }

    /**
     * 8. Test product share event separation from floating social link clicks.
     */
    public function test_product_share_events_are_separated_without_double_counting(): void
    {
        // 1 Floating dock click
        $this->storeA->clicks()->create([
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => SocialLinkClick::TYPE_FLOATING_DOCK,
            'clicked_at' => now(),
        ]);

        // 1 Inline share click
        $this->storeA->clicks()->create([
            'platform' => 'whatsapp',
            'link_type' => SocialLinkClick::TYPE_INLINE_SHARE,
            'clicked_at' => now(),
        ]);

        // 2 Product share clicks
        $this->storeA->clicks()->create([
            'platform' => 'whatsapp',
            'link_type' => SocialLinkClick::TYPE_PRODUCT_SHARE,
            'product_id' => '12345',
            'product_title' => 'Sneakers',
            'clicked_at' => now(),
        ]);
        $this->storeA->clicks()->create([
            'platform' => 'copy_link',
            'link_type' => SocialLinkClick::TYPE_PRODUCT_SHARE,
            'product_id' => '12345',
            'product_title' => 'Sneakers',
            'clicked_at' => now(),
        ]);

        $analyticsService = app(AnalyticsService::class);

        // Filter: all
        $all = $analyticsService->getDashboardAnalytics($this->storeA, ['event_type' => 'all']);
        $this->assertEquals(4, $all['summary']['total_clicks']);
        $this->assertEquals(2, $all['summary']['social_links_clicks']);
        $this->assertEquals(2, $all['summary']['product_shares_clicks']);

        // Filter: social_links only
        $socialOnly = $analyticsService->getDashboardAnalytics($this->storeA, ['event_type' => 'social_links']);
        $this->assertEquals(2, $socialOnly['summary']['total_clicks']);

        // Filter: product_shares only
        $productOnly = $analyticsService->getDashboardAnalytics($this->storeA, ['event_type' => 'product_shares']);
        $this->assertEquals(2, $productOnly['summary']['total_clicks']);
    }

    /**
     * 9. Test historical analytics are preserved when a social link is deleted.
     */
    public function test_deleted_social_link_clicks_are_preserved_historically(): void
    {
        // Create temporary social link with clicks
        $tempLink = $this->storeA->socialLinks()->create([
            'platform' => 'youtube',
            'url' => 'https://youtube.com/store_a',
            'label' => 'YouTube Channel',
            'is_active' => true,
        ]);

        $this->storeA->clicks()->create([
            'social_link_id' => $tempLink->id,
            'platform' => 'youtube',
            'link_type' => SocialLinkClick::TYPE_FLOATING_DOCK,
            'clicked_at' => now(),
        ]);

        // Delete the link
        $tempLink->delete();

        // Historical click record still exists with nullable social_link_id
        $this->assertDatabaseHas('social_link_clicks', [
            'shopify_store_id' => $this->storeA->id,
            'platform' => 'youtube',
        ]);

        $analyticsService = app(AnalyticsService::class);
        $analytics = $analyticsService->getDashboardAnalytics($this->storeA);

        $this->assertEquals(1, $analytics['summary']['total_clicks']);

        // Appears in links table as archived
        $youtubeRow = collect($analytics['social_links']['links'])->firstWhere('platform', 'youtube');
        $this->assertNotNull($youtubeRow);
        $this->assertTrue($youtubeRow['is_deleted']);
        $this->assertEquals(1, $youtubeRow['total_clicks']);
    }

    /**
     * 10. Test duplicate event prevention within 5 seconds for same IP + platform.
     */
    public function test_prevents_duplicate_click_within_five_seconds(): void
    {
        // First click
        $r1 = $this->postJson(route('api.storefront.track-click'), [
            'shop' => $this->storeA->shop,
            'link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
        ]);
        $r1->assertStatus(200);

        // Duplicate click immediately after
        $r2 = $this->postJson(route('api.storefront.track-click'), [
            'shop' => $this->storeA->shop,
            'link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
        ]);
        $r2->assertStatus(200);

        // Exactly 1 database record should be created
        $this->assertDatabaseCount('social_link_clicks', 1);
    }

    /**
     * 11. Test authenticated dashboard JSON endpoint returns full payload.
     */
    public function test_authenticated_analytics_ajax_endpoint_returns_json(): void
    {
        $response = $this->withSession([
            'shopify_shop' => $this->storeA->shop,
            'shopify_store_id' => $this->storeA->id,
        ])->getJson(route('shopify.analytics', ['date_range' => 'last_7_days']));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'filter' => ['range', 'date_range', 'label', 'start_date', 'end_date', 'event_type', 'timezone'],
                'summary' => ['total_clicks', 'clicks_today', 'clicks_this_week', 'top_platform'],
                'weekly_chart' => ['days', 'total', 'has_data'],
                'platform_chart' => ['platforms', 'total', 'has_data'],
                'social_links' => ['links', 'total_count'],
            ]);
    }

    /**
     * 12. Test click event recording when sent as text/plain beacon payload.
     */
    public function test_records_click_event_when_sent_as_plain_text_beacon_payload(): void
    {
        $payload = json_encode([
            'shop' => $this->storeA->shop,
            'link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
        ]);

        $response = $this->call(
            'POST',
            route('api.storefront.track-click'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'text/plain;charset=UTF-8',
                'HTTP_ACCEPT' => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('social_link_clicks', [
            'shopify_store_id' => $this->storeA->id,
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
        ]);
    }

    /**
     * 13. Test performance table shows all-time clicks regardless of range filter.
     */
    public function test_performance_table_shows_all_time_clicks_regardless_of_range_filter(): void
    {
        // Click 15 days ago (outside last_7_days)
        $this->storeA->clicks()->create([
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
            'clicked_at' => Carbon::now()->subDays(15),
            'ip_hash' => hash('sha256', '127.0.0.1'),
            'user_agent_hash' => hash('sha256', 'TestAgent'),
        ]);

        // Click today (inside this week)
        $this->storeA->clicks()->create([
            'social_link_id' => $this->linkInstagram->id,
            'platform' => 'instagram',
            'link_type' => 'floating_dock',
            'clicked_at' => Carbon::now(),
            'ip_hash' => hash('sha256', '127.0.0.2'),
            'user_agent_hash' => hash('sha256', 'TestAgent'),
        ]);

        $response = $this->withSession([
            'shopify_shop' => $this->storeA->shop,
            'shopify_store_id' => $this->storeA->id,
        ])->getJson(route('shopify.analytics', ['date_range' => 'today']));

        $response->assertStatus(200);
        $links = $response->json('social_links.links');

        $igItem = collect($links)->firstWhere('id', $this->linkInstagram->id);
        $this->assertNotNull($igItem);
        $this->assertEquals(2, $igItem['total_clicks']); // all-time
        $this->assertEquals(1, $igItem['this_week_clicks']); // this week
        $this->assertNotEquals('Never', $igItem['last_clicked_formatted']);
        $this->assertStringContainsString('<svg', $igItem['icon_html']);
    }
}
