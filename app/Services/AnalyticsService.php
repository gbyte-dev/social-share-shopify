<?php

namespace App\Services;

use App\Models\ShopifyStore;
use App\Models\SocialLinkClick;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Resolve date range filter into start and end timestamps.
     *
     * @return array{start: Carbon, end: Carbon, range: string, label: string}
     */
    public function resolveDateRange(string $range = 'last_7_days', ?string $customStart = null, ?string $customEnd = null, string $timezone = 'UTC'): array
    {
        $now = Carbon::now($timezone);

        switch ($range) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = 'Today';
                break;

            case 'this_week':
                $start = $now->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
                $end = $now->copy()->endOfWeek(CarbonInterface::SUNDAY)->endOfDay();
                $label = 'This Week';
                break;

            case 'last_30_days':
                $start = $now->copy()->subDays(29)->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = 'Last 30 Days';
                break;

            case 'last_90_days':
                $start = $now->copy()->subDays(89)->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = 'Last 90 Days';
                break;

            case 'custom':
                if (! empty($customStart) && ! empty($customEnd)) {
                    try {
                        $start = Carbon::parse($customStart, $timezone)->startOfDay();
                        $end = Carbon::parse($customEnd, $timezone)->endOfDay();

                        if ($start->gt($end)) {
                            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                        }

                        $label = $start->format('M j, Y').' - '.$end->format('M j, Y');
                        break;
                    } catch (\Exception $e) {
                        // Fall back to last 7 days on parse error
                    }
                }
                $range = 'last_7_days';
                // Fallthrough to last_7_days

            case 'last_7_days':
            default:
                $range = 'last_7_days';
                $start = $now->copy()->subDays(6)->startOfDay();
                $end = $now->copy()->endOfDay();
                $label = 'Last 7 Days';
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
            'range' => $range,
            'label' => $label,
        ];
    }

    /**
     * Build base query scoped to store and click filter.
     *
     * @param  'all'|'social_links'|'product_shares'  $eventType
     * @return Builder<SocialLinkClick>
     */
    public function buildScopedQuery(ShopifyStore $store, string $eventType = 'all'): Builder
    {
        $query = SocialLinkClick::query()->where('shopify_store_id', $store->id);

        if ($eventType === 'social_links') {
            $query->whereIn('link_type', [SocialLinkClick::TYPE_FLOATING_DOCK, SocialLinkClick::TYPE_INLINE_SHARE])
                ->whereNull('product_id')
                ->whereNotIn('platform', ['copy_link', 'native', 'floating_cta', 'cta']);
        } elseif ($eventType === 'product_shares') {
            $query->where(function ($q) {
                $q->where('link_type', SocialLinkClick::TYPE_PRODUCT_SHARE)
                    ->orWhereNotNull('product_id')
                    ->orWhereIn('platform', ['copy_link', 'native']);
            })->whereNotIn('platform', ['floating_cta', 'cta']);
        } elseif ($eventType === 'floating_cta') {
            $query->where('link_type', SocialLinkClick::TYPE_FLOATING_CTA);
        }

        return $query;
    }

    /**
     * Compute summary metrics for the given store and filter.
     *
     * @return array{
     *     total_clicks: int,
     *     today_clicks: int,
     *     week_clicks: int,
     *     most_clicked_platform: array{platform: string, name: string, total: int, brand_color: string}|null,
     *     social_links_count: int,
     *     product_shares_count: int,
     * }
     */
    public function getSummaryMetrics(ShopifyStore $store, array $dateRange, string $eventType = 'all', string $timezone = 'UTC'): array
    {
        $now = Carbon::now($timezone);
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $weekStart = $now->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $weekEnd = $now->copy()->endOfWeek(CarbonInterface::SUNDAY)->endOfDay();

        $baseQuery = $this->buildScopedQuery($store, $eventType);

        // Total clicks in selected range
        $totalClicks = (clone $baseQuery)
            ->where(function ($q) use ($dateRange) {
                $q->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
                    ->orWhere(function ($fallbackQ) use ($dateRange) {
                        $fallbackQ->whereNull('clicked_at')
                            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
                    });
            })
            ->count();

        // Clicks today (store timezone)
        $todayClicks = (clone $baseQuery)
            ->where(function ($q) use ($todayStart, $todayEnd) {
                $q->whereBetween('clicked_at', [$todayStart, $todayEnd])
                    ->orWhere(function ($fallbackQ) use ($todayStart, $todayEnd) {
                        $fallbackQ->whereNull('clicked_at')
                            ->whereBetween('created_at', [$todayStart, $todayEnd]);
                    });
            })
            ->count();

        // Clicks this week (Monday - Sunday)
        $weekClicks = (clone $baseQuery)
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('clicked_at', [$weekStart, $weekEnd])
                    ->orWhere(function ($fallbackQ) use ($weekStart, $weekEnd) {
                        $fallbackQ->whereNull('clicked_at')
                            ->whereBetween('created_at', [$weekStart, $weekEnd]);
                    });
            })
            ->count();

        // Most clicked platform in selected date range
        $topPlatformRow = (clone $baseQuery)
            ->where(function ($q) use ($dateRange) {
                $q->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
                    ->orWhere(function ($fallbackQ) use ($dateRange) {
                        $fallbackQ->whereNull('clicked_at')
                            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
                    });
            })
            ->select('platform', DB::raw('COUNT(*) as total'))
            ->groupBy('platform')
            ->orderByDesc('total')
            ->first();

        $mostClicked = null;
        if ($topPlatformRow && $topPlatformRow->total > 0) {
            $platKey = $topPlatformRow->platform;
            $platName = $this->formatPlatformName($platKey);
            $brandColor = $this->formatPlatformColor($platKey);

            $mostClicked = [
                'platform' => $platKey,
                'name' => $platName,
                'total' => (int) $topPlatformRow->total,
                'brand_color' => $brandColor,
                'icon' => SocialPlatformService::iconSvg($platKey, 18),
            ];
        }

        // Sub-breakdowns of click types in selected range
        $socialLinksCount = (clone $baseQuery)
            ->where(function ($q) use ($dateRange) {
                $q->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
                    ->orWhere(function ($fallbackQ) use ($dateRange) {
                        $fallbackQ->whereNull('clicked_at')
                            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
                    });
            })
            ->whereIn('link_type', [SocialLinkClick::TYPE_FLOATING_DOCK, SocialLinkClick::TYPE_INLINE_SHARE])
            ->whereNull('product_id')
            ->whereNotIn('platform', ['copy_link', 'native'])
            ->count();

        $productSharesCount = (clone $baseQuery)
            ->where(function ($q) use ($dateRange) {
                $q->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
                    ->orWhere(function ($fallbackQ) use ($dateRange) {
                        $fallbackQ->whereNull('clicked_at')
                            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
                    });
            })
            ->where(function ($q) {
                $q->where('link_type', SocialLinkClick::TYPE_PRODUCT_SHARE)
                    ->orWhereNotNull('product_id')
                    ->orWhereIn('platform', ['copy_link', 'native']);
            })
            ->count();

        return [
            'total_clicks' => $totalClicks,
            'today_clicks' => $todayClicks,
            'clicks_today' => $todayClicks,
            'week_clicks' => $weekClicks,
            'clicks_this_week' => $weekClicks,
            'most_clicked_platform' => $mostClicked,
            'top_platform' => $mostClicked['platform'] ?? null,
            'top_platform_name' => $mostClicked['name'] ?? null,
            'top_platform_clicks' => $mostClicked['total'] ?? 0,
            'top_platform_color' => $mostClicked['brand_color'] ?? '#e2e8f0',
            'top_platform_icon' => $mostClicked['icon'] ?? ($mostClicked ? SocialPlatformService::iconSvg($mostClicked['platform'], 18) : null),
            'social_links_count' => $socialLinksCount,
            'social_links_clicks' => $socialLinksCount,
            'product_shares_count' => $productSharesCount,
            'product_shares_clicks' => $productSharesCount,
        ];
    }

    /**
     * Weekly clicks chart data (Monday to Sunday for current week).
     * Strictly includes all 7 days with zero counts when no clicks exist.
     *
     * @return array{
     *     labels: list<string>,
     *     dates: list<string>,
     *     data: list<int>,
     *     total: int,
     *     start_date: string,
     *     end_date: string,
     *     days: list<array{date: string, day_name: string, label: string, clicks: int}>
     * }
     */
    public function getWeeklyClicksChart(ShopifyStore $store, string $eventType = 'all', string $timezone = 'UTC'): array
    {
        $now = Carbon::now($timezone);
        $startOfWeek = $now->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $endOfWeek = $now->copy()->endOfWeek(CarbonInterface::SUNDAY)->endOfDay();

        $query = $this->buildScopedQuery($store, $eventType)
            ->where(function ($q) use ($startOfWeek, $endOfWeek) {
                $q->whereBetween('clicked_at', [$startOfWeek, $endOfWeek])
                    ->orWhere(function ($fallbackQ) use ($startOfWeek, $endOfWeek) {
                        $fallbackQ->whereNull('clicked_at')
                            ->whereBetween('created_at', [$startOfWeek, $endOfWeek]);
                    });
            });

        // Group by date - cross database compatible
        $records = $query
            ->select(DB::raw('DATE(COALESCE(clicked_at, created_at)) as click_date'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('DATE(COALESCE(clicked_at, created_at))'))
            ->pluck('total', 'click_date')
            ->toArray();

        $labels = [];
        $dates = [];
        $data = [];
        $days = [];
        $totalWeek = 0;

        $cursor = $startOfWeek->copy();
        while ($cursor->lte($endOfWeek)) {
            $dateStr = $cursor->format('Y-m-d');
            $dayName = $cursor->format('D'); // Mon, Tue, etc.
            $dayLabel = $cursor->format('D, M j'); // Mon, Oct 26
            $count = (int) ($records[$dateStr] ?? 0);

            $labels[] = $dayName;
            $dates[] = $dateStr;
            $data[] = $count;
            $totalWeek += $count;

            $days[] = [
                'date' => $dateStr,
                'day_name' => $dayName,
                'full_day' => $cursor->format('l'),
                'label' => $dayLabel,
                'formatted_date' => $cursor->format('M j'),
                'clicks' => $count,
                'is_today' => $dateStr === $now->format('Y-m-d'),
            ];

            $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'dates' => $dates,
            'data' => $data,
            'total' => $totalWeek,
            'total_week_clicks' => $totalWeek,
            'has_data' => $totalWeek > 0,
            'week_range' => $startOfWeek->format('M j').' - '.$endOfWeek->format('M j, Y'),
            'start_date' => $startOfWeek->format('Y-m-d'),
            'end_date' => $endOfWeek->format('Y-m-d'),
            'days' => $days,
        ];
    }

    /**
     * Clicks grouped by platform for chart visualization.
     *
     * @return array{
     *     labels: list<string>,
     *     platforms: list<string>,
     *     data: list<int>,
     *     colors: list<string>,
     *     percentages: list<float>,
     *     total: int,
     *     items: list<array{platform: string, name: string, total: int, brand_color: string, percentage: float}>
     * }
     */
    public function getPlatformClicksChart(ShopifyStore $store, array $dateRange, string $eventType = 'all'): array
    {
        $rows = $this->buildScopedQuery($store, $eventType)
            ->whereNotIn('platform', ['floating_cta', 'cta'])
            ->where(function ($q) use ($dateRange) {
                $q->whereBetween('clicked_at', [$dateRange['start'], $dateRange['end']])
                    ->orWhere(function ($fallbackQ) use ($dateRange) {
                        $fallbackQ->whereNull('clicked_at')
                            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
                    });
            })
            ->select('platform', DB::raw('COUNT(*) as total'))
            ->groupBy('platform')
            ->orderByDesc('total')
            ->get();

        $totalClicks = (int) $rows->sum('total');

        $labels = [];
        $platforms = [];
        $data = [];
        $colors = [];
        $percentages = [];
        $items = [];

        foreach ($rows as $row) {
            $platKey = $row->platform;
            $count = (int) $row->total;
            $name = $this->formatPlatformName($platKey);
            $color = $this->formatPlatformColor($platKey);
            $pct = $totalClicks > 0 ? round(($count / $totalClicks) * 100, 1) : 0.0;

            $labels[] = $name;
            $platforms[] = $platKey;
            $data[] = $count;
            $colors[] = $color;
            $percentages[] = $pct;

            $items[] = [
                'platform' => $platKey,
                'platform_name' => $name,
                'name' => $name,
                'clicks' => $count,
                'total' => $count,
                'brand_color' => $color,
                'percentage' => $pct,
            ];
        }

        return [
            'labels' => $labels,
            'platforms' => $items,
            'platform_keys' => $platforms,
            'data' => $data,
            'colors' => $colors,
            'percentages' => $percentages,
            'total' => $totalClicks,
            'has_data' => $totalClicks > 0,
            'items' => $items,
        ];
    }

    /**
     * Social link performance breakdown table data.
     * Includes all store social links (active and inactive, even with 0 clicks),
     * and historical click events from deleted links.
     *
     * @return list<array{
     *     id: int|null,
     *     is_historical: bool,
     *     label: string,
     *     platform: string,
     *     platform_name: string,
     *     is_active: bool,
     *     url: string|null,
     *     brand_color: string,
     *     icon_svg: string,
     *     total_clicks: int,
     *     this_week_clicks: int,
     *     last_clicked_at: string|null,
     *     last_clicked_diff: string,
     * }>
     */
    public function getSocialLinksPerformance(ShopifyStore $store, array $dateRange, string $eventType = 'all', string $timezone = 'UTC'): array
    {
        $now = Carbon::now($timezone);
        $weekStart = $now->copy()->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $weekEnd = $now->copy()->endOfWeek(CarbonInterface::SUNDAY)->endOfDay();

        // 1. Fetch all configured social links for the store
        $links = $store->socialLinks()->get();

        // 2. Fetch all-time aggregated clicks & last click for links in a single query (No N+1)
        $allTimeAggregates = $this->buildScopedQuery($store, $eventType)
            ->whereNotNull('social_link_id')
            ->select(
                'social_link_id',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(COALESCE(clicked_at, created_at)) as last_click')
            )
            ->groupBy('social_link_id')
            ->get()
            ->keyBy('social_link_id');

        // 3. Fetch aggregated clicks for links this week (Monday - Sunday store timezone) in a single query
        $weekAggregates = $this->buildScopedQuery($store, $eventType)
            ->whereNotNull('social_link_id')
            ->where(function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('clicked_at', [$weekStart, $weekEnd])
                    ->orWhere(function ($fallbackQ) use ($weekStart, $weekEnd) {
                        $fallbackQ->whereNull('clicked_at')
                            ->whereBetween('created_at', [$weekStart, $weekEnd]);
                    });
            })
            ->select('social_link_id', DB::raw('COUNT(*) as total'))
            ->groupBy('social_link_id')
            ->pluck('total', 'social_link_id');

        $result = [];

        // Build records for all existing configured links
        foreach ($links as $link) {
            $agg = $allTimeAggregates->get($link->id);
            $total = (int) ($agg->total ?? 0);
            $week = (int) ($weekAggregates->get($link->id) ?? 0);
            $lastClickRaw = $agg->last_click ?? null;
            $lastClickCarbon = $lastClickRaw ? Carbon::parse($lastClickRaw, $timezone) : null;

            $result[] = [
                'id' => $link->id,
                'is_historical' => false,
                'is_deleted' => false,
                'label' => $link->label ?: $link->platform_name,
                'platform' => $link->platform,
                'platform_name' => $link->platform_name,
                'is_active' => (bool) $link->is_active,
                'url' => $link->url,
                'brand_color' => $link->brand_color,
                'icon_html' => $link->icon_html,
                'icon_svg' => $link->icon_svg,
                'logo_small_url' => $link->logo_small_url,
                'total_clicks' => $total,
                'this_week_clicks' => $week,
                'week_clicks' => $week,
                'last_clicked_at' => $lastClickCarbon?->format('Y-m-d H:i:s'),
                'last_clicked_diff' => $lastClickCarbon ? $lastClickCarbon->diffForHumans() : 'Never',
                'last_clicked_formatted' => $lastClickCarbon ? $lastClickCarbon->diffForHumans() : 'Never',
            ];
        }

        // 4. Check for orphan historical clicks (e.g. social link deleted or general platform click)
        $orphanAggregates = $this->buildScopedQuery($store, $eventType)
            ->whereNull('social_link_id')
            ->select(
                'platform',
                DB::raw('COUNT(*) as total'),
                DB::raw('MAX(COALESCE(clicked_at, created_at)) as last_click')
            )
            ->groupBy('platform')
            ->get();

        if ($orphanAggregates->isNotEmpty()) {
            $orphanWeekAggregates = $this->buildScopedQuery($store, $eventType)
                ->whereNull('social_link_id')
                ->where(function ($q) use ($weekStart, $weekEnd) {
                    $q->whereBetween('clicked_at', [$weekStart, $weekEnd])
                        ->orWhere(function ($fallbackQ) use ($weekStart, $weekEnd) {
                            $fallbackQ->whereNull('clicked_at')
                                ->whereBetween('created_at', [$weekStart, $weekEnd]);
                        });
                })
                ->select('platform', DB::raw('COUNT(*) as total'))
                ->groupBy('platform')
                ->pluck('total', 'platform');

            foreach ($orphanAggregates as $orphan) {
                $platKey = $orphan->platform;
                $platName = $this->formatPlatformName($platKey);
                $brandColor = $this->formatPlatformColor($platKey);
                $total = (int) $orphan->total;
                $week = (int) ($orphanWeekAggregates->get($platKey) ?? 0);
                $lastClickRaw = $orphan->last_click ?? null;
                $lastClickCarbon = $lastClickRaw ? Carbon::parse($lastClickRaw, $timezone) : null;
                $iconSvg = SocialPlatformService::iconSvg($platKey, 18);

                $result[] = [
                    'id' => null,
                    'is_historical' => true,
                    'is_deleted' => true,
                    'label' => $platName.' (Archived / Direct)',
                    'platform' => $platKey,
                    'platform_name' => $platName,
                    'is_active' => false,
                    'url' => null,
                    'brand_color' => $brandColor,
                    'icon_html' => $iconSvg,
                    'icon_svg' => $iconSvg,
                    'logo_small_url' => null,
                    'total_clicks' => $total,
                    'this_week_clicks' => $week,
                    'week_clicks' => $week,
                    'last_clicked_at' => $lastClickCarbon?->format('Y-m-d H:i:s'),
                    'last_clicked_diff' => $lastClickCarbon ? $lastClickCarbon->diffForHumans() : 'Never',
                    'last_clicked_formatted' => $lastClickCarbon ? $lastClickCarbon->diffForHumans() : 'Never',
                ];
            }
        }

        // Sort: highest total clicks first, active links next, label ascending
        usort($result, function ($a, $b) {
            if ($a['total_clicks'] !== $b['total_clicks']) {
                return $b['total_clicks'] <=> $a['total_clicks'];
            }
            if ($a['is_active'] !== $b['is_active']) {
                return $b['is_active'] <=> $a['is_active'];
            }

            return strcasecmp($a['label'], $b['label']);
        });

        return $result;
    }

    /**
     * Complete dashboard analytics payload for view or AJAX response.
     *
     * @param  array{range?: string, start_date?: string|null, end_date?: string|null, event_type?: string, timezone?: string}  $options
     * @return array<string, mixed>
     */
    public function getDashboardAnalytics(ShopifyStore $store, array $options = []): array
    {
        $range = (string) ($options['range'] ?? $options['date_range'] ?? 'last_7_days');
        $customStart = $options['start_date'] ?? null;
        $customEnd = $options['end_date'] ?? null;
        $eventType = (string) ($options['event_type'] ?? 'all');
        $timezone = (string) ($options['timezone'] ?? $store->timezone ?? config('app.timezone', 'UTC'));

        if (! in_array($eventType, ['all', 'social_links', 'product_shares'], true)) {
            $eventType = 'all';
        }

        $dateRange = $this->resolveDateRange($range, $customStart, $customEnd, $timezone);

        $summary = $this->getSummaryMetrics($store, $dateRange, $eventType, $timezone);
        $weeklyChart = $this->getWeeklyClicksChart($store, $eventType, $timezone);
        $platformChart = $this->getPlatformClicksChart($store, $dateRange, $eventType);
        $performanceTable = $this->getSocialLinksPerformance($store, $dateRange, $eventType, $timezone);

        return [
            'success' => true,
            'filter' => [
                'range' => $dateRange['range'],
                'date_range' => $dateRange['range'],
                'label' => $dateRange['label'],
                'start_date' => $dateRange['start']->format('Y-m-d'),
                'end_date' => $dateRange['end']->format('Y-m-d'),
                'event_type' => $eventType,
                'timezone' => $timezone,
            ],
            'summary' => $summary,
            'weekly_chart' => $weeklyChart,
            'platform_chart' => $platformChart,
            'social_links' => [
                'total_count' => count($performanceTable),
                'links' => $performanceTable,
            ],
            'links_table' => $performanceTable,
        ];
    }

    /**
     * Human-readable label for platforms including product share actions.
     */
    protected function formatPlatformName(string $platform): string
    {
        return match (strtolower(trim($platform))) {
            'copy_link' => 'Copy Link',
            'native' => 'Mobile Share (Native)',
            'telegram' => 'Telegram',
            'twitter' => 'X (Twitter)',
            'reddit' => 'Reddit',
            default => SocialPlatformService::name($platform),
        };
    }

    /**
     * Color for platforms including product share actions.
     */
    protected function formatPlatformColor(string $platform): string
    {
        return match (strtolower(trim($platform))) {
            'copy_link' => '#475569',
            'native' => '#0284c7',
            'telegram' => '#229ED9',
            'twitter' => '#000000',
            'reddit' => '#FF4500',
            default => SocialPlatformService::get($platform)['brand_color'] ?? '#4f46e5',
        };
    }
}
