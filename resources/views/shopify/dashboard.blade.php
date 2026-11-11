@extends('layouts.shopify')

@section('title', 'SocialNest Dashboard - ' . $shop)
@section('header_title', 'Dashboard')
@section('header_subtitle', 'Store overview, social engagement, and storefront feature controls')

@section('header_actions')
   <button
    type="button"
    id="wishlist-setup-btn"
    data-url="{{ route('shopify.wishlist.setup') }}"
    class="btn btn-secondary btn-sm"
    style="padding: 0.4rem 0.75rem; font-size: 0.8125rem; color: #e11d48; border-color: #fecdd3; background: #fff1f2;"
>
    <span>1-Click Wishlist Setup</span>
</button>
    <a href="{{ route('shopify.social-links.create') }}" class="btn btn-primary btn-sm" style="padding: 0.4rem 0.75rem; font-size: 0.8125rem;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Add Social Link</span>
    </a>
@endsection

@section('content')
    <!-- Scoped Polaris-inspired Design Styles for Dashboard -->
    <style>
        /* Hide developer-facing Live & Authenticated badge on Dashboard */
        .top-actions .badge-live {
            display: none !important;
        }

        .sn-dash-wrap {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .sn-card {
            background: #ffffff;
            border: 1px solid #e1e3e5;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
            box-sizing: border-box;
        }

        .sn-metric-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
        }

        .sn-metric-card {
            padding: 0.625rem 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
            min-height: 44px;
            white-space: nowrap;
            overflow: hidden;
            box-sizing: border-box;
        }

        .sn-metric-label {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #5c5f62;
            margin: 0;
            flex-shrink: 0;
        }

        .sn-metric-body {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .sn-metric-val {
            font-size: 1rem;
            font-weight: 700;
            color: #202223;
            line-height: 1;
        }

        .sn-metric-sub {
            font-size: 0.75rem;
            color: #6d7175;
            white-space: nowrap;
        }

        .sn-nav-icon-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 4px;
            color: var(--primary, #4f46e5);
            background: #f1f2f4;
            text-decoration: none;
            transition: all 0.15s ease;
            flex-shrink: 0;
        }

        .sn-nav-icon-link:hover {
            background: #eef2ff;
            color: #3730a3;
        }

        .sn-features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .sn-feature-card {
            padding: 1rem 1.125rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sn-feature-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .sn-feature-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #202223;
            margin: 0;
        }

        .sn-badge-on {
            font-size: 0.6875rem;
            font-weight: 600;
            color: #007f5f;
            background: #e4f8ed;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
        }

        .sn-badge-off {
            font-size: 0.6875rem;
            font-weight: 600;
            color: #6d7175;
            background: #f1f2f4;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
        }

        .sn-feature-desc {
            font-size: 0.75rem;
            color: #6d7175;
            line-height: 1.4;
            margin: 0 0 0.75rem 0;
            flex: 1;
        }

        .sn-feature-link {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary, #4f46e5);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .sn-feature-link:hover {
            text-decoration: underline;
        }

        .sn-filter-bar {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            border-bottom: 1px solid #f1f2f4;
        }

        .sn-seg-control {
            display: inline-flex;
            background: #f1f2f4;
            padding: 2px;
            border-radius: 6px;
        }

        .sn-seg-btn {
            padding: 0.25rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            border: none;
            background: transparent;
            color: #5c5f62;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .sn-seg-btn.active {
            background: #ffffff;
            color: #202223;
            font-weight: 700;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
        }

        .sn-date-btn {
            padding: 0.25rem 0.55rem;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1px solid #e1e3e5;
            background: #ffffff;
            color: #5c5f62;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .sn-date-btn.active {
            border-color: var(--primary, #4f46e5);
            background: #eef2ff;
            color: var(--primary, #4f46e5);
            font-weight: 700;
        }

        .sn-charts-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 0.75rem;
        }

        .sn-chart-card {
            padding: 1rem 1.125rem;
        }

        .sn-chart-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .sn-chart-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #202223;
            margin: 0;
        }

        .sn-chart-sub {
            font-size: 0.75rem;
            color: #6d7175;
            margin: 0.1rem 0 0 0;
        }

        .sn-table-card {
            padding: 0.875rem 1rem;
        }

        .sn-table-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .sn-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            text-align: left;
        }

        .sn-table th {
            padding: 0.5rem 0.5rem 0.5rem 0;
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6d7175;
            border-bottom: 1px solid #e1e3e5;
        }

        .sn-table td {
            padding: 0.625rem 0.5rem 0.625rem 0;
            border-bottom: 1px solid #f1f2f4;
            color: #202223;
            vertical-align: middle;
        }

        .sn-table tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 1024px) {
            .sn-metric-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .sn-features-grid {
                grid-template-columns: 1fr;
            }
            .sn-charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .sn-metric-grid {
                grid-template-columns: 1fr;
            }
            .sn-filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>

    <div class="sn-dash-wrap">
        <!-- 1. Store Overview Metric Cards (Single-Line Content) -->
        <div class="sn-metric-grid">
            <!-- Metric 1: Configured Social Links -->
            <div class="sn-card sn-metric-card">
                <span class="sn-metric-label">Social Links</span>
                <div class="sn-metric-body">
                    <span class="sn-metric-val">{{ $activeLinks }}</span>
                    <span class="sn-metric-sub">/ {{ $totalLinks }}</span>
                    <a href="{{ route('shopify.social-links.index') }}" class="sn-nav-icon-link" title="Manage links" aria-label="Manage links">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
                    </a>
                </div>
            </div>

            <!-- Metric 2: Total Shopper Clicks -->
            <div class="sn-card sn-metric-card">
                <span class="sn-metric-label">Total Clicks</span>
                <div class="sn-metric-body">
                    <span class="sn-metric-val" id="sn-total-clicks">{{ number_format($analyticsData['summary']['total_clicks']) }}</span>
                    <span class="sn-metric-sub" id="sn-breakdown-clicks">({{ $analyticsData['summary']['social_links_clicks'] }} dock)</span>
                </div>
            </div>

            <!-- Metric 3: Today's Clicks & Weekly Total -->
            <div class="sn-card sn-metric-card">
                <span class="sn-metric-label">Clicks Today</span>
                <div class="sn-metric-body">
                    <span class="sn-metric-val" id="sn-today-clicks">{{ number_format($analyticsData['summary']['clicks_today']) }}</span>
                    <span class="sn-metric-sub">(<span id="sn-week-clicks">{{ number_format($analyticsData['summary']['clicks_this_week']) }}</span> wk)</span>
                </div>
            </div>

            <!-- Metric 4: Top Platform -->
            <div class="sn-card sn-metric-card">
                <span class="sn-metric-label">Top Platform</span>
                <div class="sn-metric-body">
                    <span id="sn-top-platform-badge" style="width: 18px; height: 18px; border-radius: 4px; background: {{ $analyticsData['summary']['top_platform_color'] ?? '#f1f2f4' }}; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                        @if(!empty($analyticsData['summary']['top_platform_icon']))
                            {!! $analyticsData['summary']['top_platform_icon'] !!}
                        @else
                            <span style="font-weight: 700; font-size: 0.625rem; color: #5c5f62;">—</span>
                        @endif
                    </span>
                    <strong id="sn-top-platform-name" style="font-size: 0.8125rem; font-weight: 700; color: #202223; max-width: 65px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $analyticsData['summary']['top_platform_name'] ?? 'None yet' }}</strong>
                    <span class="sn-metric-sub" id="sn-top-platform-clicks">
                        @if($analyticsData['summary']['top_platform'])
                            ({{ number_format($analyticsData['summary']['top_platform_clicks']) }})
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- 2. Storefront Features Status & Quick Shortcuts -->
        <div class="sn-features-grid">
            <!-- Feature 1: Floating Social Dock -->
            <div class="sn-card sn-feature-card">
                <div>
                    <div class="sn-feature-head">
                        <h3 class="sn-feature-title">Floating Social Dock</h3>
                        @if($settings->is_enabled)
                            <span class="sn-badge-on">Enabled</span>
                        @else
                            <span class="sn-badge-off">Disabled</span>
                        @endif
                    </div>
                    <p class="sn-feature-desc">
                        Floating dock displayed on storefront. Position: <strong>{{ ucfirst(str_replace('_', ' ', $settings->position)) }}</strong> ({{ ucfirst($settings->icon_size) }} icons).
                    </p>
                </div>
                <div>
                    <a href="{{ route('shopify.settings.index') }}" class="sn-feature-link">Configure dock settings &rarr;</a>
                </div>
            </div>

            <!-- Feature 2: Product Page Sharing -->
            <div class="sn-card sn-feature-card">
                <div>
                    <div class="sn-feature-head">
                        <h3 class="sn-feature-title">Product Sharing</h3>
                        @if($settings->product_share_enabled ?? true)
                            <span class="sn-badge-on">Active</span>
                        @else
                            <span class="sn-badge-off">Off</span>
                        @endif
                    </div>
                    <p class="sn-feature-desc">
                        Native share buttons allowing shoppers to share product URLs directly to WhatsApp, Facebook, X, and Pinterest.
                    </p>
                </div>
                <div>
                    <a href="{{ route('shopify.settings.index') }}" class="sn-feature-link">Customize buttons &rarr;</a>
                </div>
            </div>

            <!-- Feature 3: Supported Social Networks -->
            <div class="sn-card sn-feature-card">
                <div>
                    <div class="sn-feature-head">
                        <h3 class="sn-feature-title">Connected Platforms</h3>
                        <span class="sn-badge-on">{{ $totalPlatforms }} Supported</span>
                    </div>
                    <p class="sn-feature-desc">
                        Connect Instagram, WhatsApp, Facebook, TikTok, YouTube, X, Pinterest, LinkedIn, Threads, and more.
                    </p>
                </div>
                <div>
                    <a href="{{ route('shopify.social-links.create') }}" class="sn-feature-link">Add another platform &rarr;</a>
                </div>
            </div>
        </div>

        <!-- 3. Social Link Click Analytics Section -->
        <div id="sn-analytics-section" class="sn-card" style="padding: 0; overflow: hidden;">
            <!-- Filter Bar -->
            <div class="sn-filter-bar">
                <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                    <span style="font-size: 0.8125rem; font-weight: 700; color: #202223;">Click Analytics</span>
                    <!-- Event Type Tabs -->
                    <div class="sn-seg-control" id="sn-event-type-tabs">
                        <button type="button" class="sn-seg-btn sn-filter-btn active" data-type="all">All Clicks</button>
                        <button type="button" class="sn-seg-btn sn-filter-btn" data-type="social_links">Social Links</button>
                        <button type="button" class="sn-seg-btn sn-filter-btn" data-type="product_shares">Product Shares</button>
                    </div>
                </div>

                <!-- Date Range Filters -->
                <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                    <div id="sn-date-range-pills" style="display: inline-flex; gap: 0.25rem;">
                        <button type="button" class="sn-date-btn active" data-range="last_7_days">7 Days</button>
                        <button type="button" class="sn-date-btn" data-range="last_30_days">30 Days</button>
                        <button type="button" class="sn-date-btn" data-range="last_90_days">90 Days</button>
                        <button type="button" class="sn-date-btn" data-range="custom">Custom</button>
                    </div>

                    <!-- Custom Date Inputs -->
                    <div id="sn-custom-date-box" style="display: none; align-items: center; gap: 0.35rem; margin-left: 0.25rem;">
                        <input type="date" id="sn-start-date" value="{{ $analyticsData['filter']['start_date'] }}" style="padding: 0.2rem 0.4rem; font-size: 0.75rem; border: 1px solid #e1e3e5; border-radius: 4px;">
                        <span style="font-size: 0.75rem; color: #6d7175;">to</span>
                        <input type="date" id="sn-end-date" value="{{ $analyticsData['filter']['end_date'] }}" style="padding: 0.2rem 0.4rem; font-size: 0.75rem; border: 1px solid #e1e3e5; border-radius: 4px;">
                        <button type="button" id="sn-apply-custom-date" class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;">Apply</button>
                    </div>

                    <div style="font-size: 0.6875rem; color: #6d7175; margin-left: 0.25rem;" id="sn-range-label">
                        <span id="sn-range-dates">{{ $analyticsData['filter']['start_date'] }} &ndash; {{ $analyticsData['filter']['end_date'] }}</span>
                    </div>
                </div>
            </div>

            <!-- Charts Container -->
            <div style="padding: 1rem 1.125rem;">
                <div class="sn-charts-grid">
                    <!-- Weekly Clicks Bar Chart -->
                    <div style="border: 1px solid #f1f2f4; border-radius: 6px; padding: 0.875rem 1rem;">
                        <div class="sn-chart-head">
                            <div>
                                <h4 class="sn-chart-title">Weekly Clicks</h4>
                                <p class="sn-chart-sub" id="sn-week-range-text">{{ $analyticsData['weekly_chart']['week_range'] }}</p>
                            </div>
                            <span style="font-size: 0.6875rem; font-weight: 600; color: #4f46e5; background: #eef2ff; padding: 0.15rem 0.45rem; border-radius: 4px;" id="sn-week-total-pill">
                                {{ $analyticsData['weekly_chart']['total_week_clicks'] }} clicks this week
                            </span>
                        </div>
                        <div style="position: relative; height: 190px;" id="sn-weekly-chart-wrapper">
                            <canvas id="sn-weekly-chart"></canvas>
                            <div id="sn-weekly-empty-state" style="{{ $analyticsData['weekly_chart']['has_data'] ? 'display: none;' : 'display: flex;' }} position: absolute; inset: 0; align-items: center; justify-content: center; flex-direction: column; background: rgba(255,255,255,0.92); text-align: center;">
                                <div style="font-size: 0.8125rem; font-weight: 600; color: #202223;">No clicks recorded this week</div>
                                <div style="font-size: 0.75rem; color: #6d7175; margin-top: 0.2rem;">Daily counts update automatically as visitors interact with your links.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Platform Distribution Doughnut Chart -->
                    <div style="border: 1px solid #f1f2f4; border-radius: 6px; padding: 0.875rem 1rem;">
                        <div class="sn-chart-head">
                            <div>
                                <h4 class="sn-chart-title">Clicks by Platform</h4>
                                <p class="sn-chart-sub">
                                    Top: <strong id="sn-top-platform-name">{{ $analyticsData['summary']['top_platform_name'] ?? 'None yet' }}</strong>
                                    <span id="sn-top-platform-clicks" style="color: #6d7175;">
                                        @if($analyticsData['summary']['top_platform'])
                                            ({{ number_format($analyticsData['summary']['top_platform_clicks']) }} clicks)
                                        @endif
                                    </span>
                                </p>
                            </div>
                            <span id="sn-top-platform-badge" style="width: 24px; height: 24px; border-radius: 6px; background: {{ $analyticsData['summary']['top_platform_color'] ?? '#f1f2f4' }}; color: #ffffff; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                @if(!empty($analyticsData['summary']['top_platform_icon']))
                                    {!! $analyticsData['summary']['top_platform_icon'] !!}
                                @else
                                    <span style="font-weight: 700; font-size: 0.6875rem; color: #5c5f62;">—</span>
                                @endif
                            </span>
                        </div>
                        <div style="position: relative; height: 190px;" id="sn-platform-chart-wrapper">
                            <canvas id="sn-platform-chart"></canvas>
                            <div id="sn-platform-empty-state" style="{{ $analyticsData['platform_chart']['has_data'] ? 'display: none;' : 'display: flex;' }} position: absolute; inset: 0; align-items: center; justify-content: center; flex-direction: column; background: rgba(255,255,255,0.92); text-align: center;">
                                <div style="font-size: 0.8125rem; font-weight: 600; color: #202223;">No platform clicks recorded</div>
                                <div style="font-size: 0.75rem; color: #6d7175; margin-top: 0.2rem;">Data appears here once shoppers click your icons.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Social Link Performance Table -->
        <div class="sn-card sn-table-card">
            <div class="sn-table-head">
                <div>
                    <h3 style="font-size: 0.875rem; font-weight: 700; color: #202223; margin: 0;">Link Performance</h3>
                    <p style="font-size: 0.75rem; color: #6d7175; margin: 0.15rem 0 0 0;">Click volume per configured social link</p>
                </div>
                <span style="font-size: 0.75rem; color: #6d7175;" id="sn-table-count">{{ count($analyticsData['social_links']['links']) }} links tracked</span>
            </div>

            <div style="overflow-x: auto;">
                <table class="sn-table">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Link</th>
                            <th style="width: 15%;">Platform</th>
                            <th style="width: 12%;">Status</th>
                            <th style="width: 12%; text-align: right;">Total Clicks</th>
                            <th style="width: 12%; text-align: right;">This Week</th>
                            <th style="width: 14%; text-align: right;">Last Click</th>
                        </tr>
                    </thead>
                    <tbody id="sn-performance-tbody">
                        @forelse($analyticsData['social_links']['links'] as $item)
                            <tr class="sn-perf-row">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="width: 26px; height: 26px; border-radius: 6px; background: {{ $item['brand_color'] }}; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">
                                            {!! $item['icon_html'] ?? $item['icon_svg'] !!}
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: #202223; font-size: 0.8125rem;">
                                                {{ $item['label'] }}
                                            </div>
                                            @if(!empty($item['url']))
                                                <div style="font-size: 0.6875rem; color: #6d7175; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    {{ $item['url'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="text-transform: capitalize; font-size: 0.75rem; font-weight: 500;">{{ $item['platform_name'] }}</span>
                                </td>
                                <td>
                                    @if($item['is_deleted'] ?? false)
                                        <span class="sn-badge-off" style="color: #991b1b; background: #fef2f2;">Archived</span>
                                    @elseif($item['is_active'])
                                        <span class="sn-badge-on">Active</span>
                                    @else
                                        <span class="sn-badge-off">Inactive</span>
                                    @endif
                                </td>
                                <td style="text-align: right; font-weight: 700;">
                                    {{ number_format($item['total_clicks']) }}
                                </td>
                                <td style="text-align: right; color: {{ $item['week_clicks'] > 0 ? '#4f46e5' : '#6d7175' }}; font-weight: {{ $item['week_clicks'] > 0 ? '700' : '500' }};">
                                    {{ number_format($item['week_clicks']) }}
                                </td>
                                <td style="text-align: right; font-size: 0.75rem; color: #6d7175;">
                                    {{ $item['last_clicked_formatted'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 1.5rem; text-align: center; color: #6d7175; font-size: 0.8125rem;">
                                    No social links configured yet. Click "Add Social Link" above to start.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <!-- Chart.js 4.4.1 for Polaris-style analytics graphs -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        (function() {
            // Initial server-rendered payload
            let initialData = @json($analyticsData);

            let state = {
                dateRange: initialData.filter.date_range || 'last_7_days',
                eventType: initialData.filter.event_type || 'all',
                startDate: initialData.filter.start_date || '',
                endDate: initialData.filter.end_date || '',
                loading: false
            };

            let weeklyChartInstance = null;
            let platformChartInstance = null;

            // DOM Elements
            const eventTabs = document.getElementById('sn-event-type-tabs');
            const datePills = document.getElementById('sn-date-range-pills');
            const customBox = document.getElementById('sn-custom-date-box');
            const startDateInp = document.getElementById('sn-start-date');
            const endDateInp = document.getElementById('sn-end-date');
            const applyCustomBtn = document.getElementById('sn-apply-custom-date');
            const rangeDatesLabel = document.getElementById('sn-range-dates');

            // Summary card elements
            const totalClicksEl = document.getElementById('sn-total-clicks');
            const breakdownClicksEl = document.getElementById('sn-breakdown-clicks');
            const todayClicksEl = document.getElementById('sn-today-clicks');
            const weekClicksEl = document.getElementById('sn-week-clicks');
            const topPlatformBadge = document.getElementById('sn-top-platform-badge');
            const topPlatformName = document.getElementById('sn-top-platform-name');
            const topPlatformClicks = document.getElementById('sn-top-platform-clicks');
            const weekTotalPill = document.getElementById('sn-week-total-pill');
            const weekRangeText = document.getElementById('sn-week-range-text');

            // Empty states
            const weeklyEmptyState = document.getElementById('sn-weekly-empty-state');
            const platformEmptyState = document.getElementById('sn-platform-empty-state');
            const tbody = document.getElementById('sn-performance-tbody');
            const tableCount = document.getElementById('sn-table-count');

            // Initialize Charts
            function initWeeklyChart(chartData) {
                const ctx = document.getElementById('sn-weekly-chart');
                if (!ctx || typeof Chart === 'undefined') return;

                const labels = (chartData.days || []).map(d => d.day_name + ' (' + d.formatted_date + ')');
                const dataValues = (chartData.days || []).map(d => d.clicks);
                const backgroundColors = (chartData.days || []).map(d => d.is_today ? '#4f46e5' : '#818cf8');

                if (weeklyChartInstance) {
                    weeklyChartInstance.data.labels = labels;
                    weeklyChartInstance.data.datasets[0].data = dataValues;
                    weeklyChartInstance.data.datasets[0].backgroundColor = backgroundColors;
                    weeklyChartInstance.update();
                } else {
                    weeklyChartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Clicks',
                                data: dataValues,
                                backgroundColor: backgroundColors,
                                borderRadius: 4,
                                maxBarThickness: 24,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#202223',
                                    titleFont: { size: 11, weight: '700' },
                                    bodyFont: { size: 11 },
                                    padding: 8,
                                    cornerRadius: 4,
                                    displayColors: false,
                                    callbacks: {
                                        label: function(context) {
                                            return context.parsed.y + ' click' + (context.parsed.y === 1 ? '' : 's');
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1,
                                        precision: 0,
                                        font: { size: 10 },
                                        color: '#6d7175'
                                    },
                                    grid: {
                                        color: '#f1f2f4'
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: { size: 10, weight: '600' },
                                        color: '#5c5f62'
                                    },
                                    grid: { display: false }
                                }
                            }
                        }
                    });
                }

                if (weeklyEmptyState) {
                    weeklyEmptyState.style.display = chartData.has_data ? 'none' : 'flex';
                }
            }

            function initPlatformChart(platformData) {
                const ctx = document.getElementById('sn-platform-chart');
                if (!ctx || typeof Chart === 'undefined') return;

                const platforms = platformData.platforms || [];
                const labels = platforms.map(p => p.platform_name);
                const counts = platforms.map(p => p.clicks);
                const colors = platforms.map(p => p.brand_color || '#4f46e5');

                if (platformChartInstance) {
                    platformChartInstance.data.labels = labels;
                    platformChartInstance.data.datasets[0].data = counts;
                    platformChartInstance.data.datasets[0].backgroundColor = colors;
                    platformChartInstance.update();
                } else {
                    platformChartInstance = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                data: counts,
                                backgroundColor: colors,
                                borderWidth: 2,
                                borderColor: '#ffffff',
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 10,
                                        padding: 10,
                                        font: { size: 10, weight: '600' },
                                        color: '#5c5f62'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#202223',
                                    titleFont: { size: 11, weight: '700' },
                                    bodyFont: { size: 11 },
                                    padding: 8,
                                    cornerRadius: 4,
                                    callbacks: {
                                        label: function(context) {
                                            const val = context.parsed;
                                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                            const pct = total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                                            return ' ' + val + ' clicks (' + pct + '%)';
                                        }
                                    }
                                }
                            },
                            cutout: '66%'
                        }
                    });
                }

                if (platformEmptyState) {
                    platformEmptyState.style.display = platformData.has_data ? 'none' : 'flex';
                }
            }

            // Update UI with newly fetched analytics payload
            function updateDashboardUI(data) {
                if (!data || !data.success) return;

                // Update summary metrics
                const s = data.summary || {};
                if (totalClicksEl) totalClicksEl.textContent = Number(s.total_clicks || 0).toLocaleString();
                if (breakdownClicksEl) breakdownClicksEl.textContent = '(' + (s.social_links_clicks || 0) + ' dock)';
                if (todayClicksEl) todayClicksEl.textContent = Number(s.clicks_today || 0).toLocaleString();
                if (weekClicksEl) weekClicksEl.textContent = Number(s.clicks_this_week || 0).toLocaleString();

                if (topPlatformBadge) {
                    topPlatformBadge.style.background = s.top_platform_color || '#f1f2f4';
                    if (s.top_platform_icon) {
                        topPlatformBadge.innerHTML = s.top_platform_icon;
                    } else {
                        topPlatformBadge.innerHTML = '<span style="font-weight: 700; font-size: 0.6875rem; color: #5c5f62;">—</span>';
                    }
                }
                if (topPlatformName) {
                    topPlatformName.textContent = s.top_platform_name || 'None yet';
                }
                if (topPlatformClicks) {
                    topPlatformClicks.textContent = s.top_platform ? '(' + Number(s.top_platform_clicks || 0).toLocaleString() + ')' : '';
                }

                // Update week range labels
                const w = data.weekly_chart || {};
                if (weekTotalPill) weekTotalPill.textContent = (w.total_week_clicks || 0) + ' clicks this week';
                if (weekRangeText) weekRangeText.textContent = w.week_range || '';

                // Update date filter indicator
                if (rangeDatesLabel && data.filter) {
                    rangeDatesLabel.textContent = data.filter.start_date + ' \u2013 ' + data.filter.end_date;
                }

                // Update charts
                initWeeklyChart(w);
                initPlatformChart(data.platform_chart || {});

                // Update performance table
                const links = (data.social_links && data.social_links.links) ? data.social_links.links : [];
                if (tableCount) tableCount.textContent = links.length + ' links tracked';

                if (tbody) {
                    if (links.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="padding: 1.5rem; text-align: center; color: #6d7175; font-size: 0.8125rem;">No social links configured yet. Click "Add Social Link" above to start.</td></tr>';
                    } else {
                        tbody.innerHTML = links.map(function(item) {
                            const badge = item.is_deleted
                                ? '<span class="sn-badge-off" style="color: #991b1b; background: #fef2f2;">Archived</span>'
                                : (item.is_active
                                    ? '<span class="sn-badge-on">Active</span>'
                                    : '<span class="sn-badge-off">Inactive</span>');

                            const urlHtml = item.url ? '<div style="font-size: 0.6875rem; color: #6d7175; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + escapeHtml(item.url) + '</div>' : '';
                            const isNever = item.last_clicked_formatted === 'Never';
                            const iconContent = item.icon_html || item.icon_svg || ('<span style="font-weight: 700; font-size: 0.6875rem;">' + escapeHtml((item.platform || '').slice(0, 2).toUpperCase()) + '</span>');

                            return '<tr class="sn-perf-row">' +
                                '<td>' +
                                    '<div style="display: flex; align-items: center; gap: 0.5rem;">' +
                                        '<div style="width: 26px; height: 26px; border-radius: 6px; background: ' + (item.brand_color || '#4f46e5') + '; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden;">' +
                                            iconContent +
                                        '</div>' +
                                        '<div>' +
                                            '<div style="font-weight: 600; color: #202223; font-size: 0.8125rem;">' + escapeHtml(item.label || item.platform_name) + '</div>' +
                                            urlHtml +
                                        '</div>' +
                                    '</div>' +
                                '</td>' +
                                '<td><span style="text-transform: capitalize; font-size: 0.75rem; font-weight: 500;">' + escapeHtml(item.platform_name) + '</span></td>' +
                                '<td>' + badge + '</td>' +
                                '<td style="text-align: right; font-weight: 700;">' + Number(item.total_clicks || 0).toLocaleString() + '</td>' +
                                '<td style="text-align: right; color: ' + (item.week_clicks > 0 ? '#4f46e5' : '#6d7175') + '; font-weight: ' + (item.week_clicks > 0 ? '700' : '500') + ';">' + Number(item.week_clicks || 0).toLocaleString() + '</td>' +
                                '<td style="text-align: right; font-size: 0.75rem; color: #6d7175;">' + escapeHtml(item.last_clicked_formatted) + '</td>' +
                            '</tr>';
                        }).join('');
                    }
                }
            }

            function escapeHtml(str) {
                if (!str) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            // Fetch live analytics from backend
            function fetchAnalytics(silent) {
                if (state.loading) return;
                state.loading = true;
                const section = document.getElementById('sn-analytics-section');
                if (!silent && section) section.style.opacity = '0.65';

                const params = new URLSearchParams({
                    date_range: state.dateRange,
                    event_type: state.eventType,
                    _t: Date.now()
                });

                if (state.dateRange === 'custom') {
                    if (state.startDate) params.set('start_date', state.startDate);
                    if (state.endDate) params.set('end_date', state.endDate);
                }

                // Preserve host/shop for Shopify embedded context
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.get('host')) params.set('host', urlParams.get('host'));
                if (urlParams.get('shop')) params.set('shop', urlParams.get('shop'));

                fetch('{{ route('shopify.analytics') }}?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(res) {
                    if (!res.ok) throw new Error('Network error: ' + res.status);
                    return res.json();
                })
                .then(function(data) {
                    state.loading = false;
                    if (section) section.style.opacity = '1';
                    updateDashboardUI(data);
                })
                .catch(function(err) {
                    state.loading = false;
                    if (section) section.style.opacity = '1';
                    console.error('Failed to fetch analytics:', err);
                });
            }

            // Event Listeners for Event Type Tabs
            if (eventTabs) {
                eventTabs.addEventListener('click', function(e) {
                    const btn = e.target.closest('.sn-filter-btn');
                    if (!btn) return;

                    eventTabs.querySelectorAll('.sn-filter-btn').forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');

                    state.eventType = btn.dataset.type;
                    fetchAnalytics(false);
                });
            }

            // Event Listeners for Date Range Pills
            if (datePills) {
                datePills.addEventListener('click', function(e) {
                    const btn = e.target.closest('.sn-date-btn');
                    if (!btn) return;

                    datePills.querySelectorAll('.sn-date-btn').forEach(function(b) {
                        b.classList.remove('active');
                    });
                    btn.classList.add('active');

                    state.dateRange = btn.dataset.range;

                    if (state.dateRange === 'custom') {
                        if (customBox) customBox.style.display = 'inline-flex';
                    } else {
                        if (customBox) customBox.style.display = 'none';
                        fetchAnalytics(false);
                    }
                });
            }

            // Custom Date Range Apply Button
            if (applyCustomBtn) {
                applyCustomBtn.addEventListener('click', function() {
                    const s = startDateInp ? startDateInp.value : '';
                    const e = endDateInp ? endDateInp.value : '';
                    if (s && e) {
                        state.startDate = s;
                        state.endDate = e;
                        fetchAnalytics(false);
                    }
                });
            }

            // Initial chart rendering
            initWeeklyChart(initialData.weekly_chart || {});
            initPlatformChart(initialData.platform_chart || {});

            // Polling interval to refresh analytics automatically while the dashboard is open
            let pollTimer = null;
            function startPolling() {
                if (pollTimer) clearInterval(pollTimer);
                pollTimer = setInterval(function() {
                    if (!document.hidden && !state.loading) {
                        fetchAnalytics(true);
                    }
                }, 8000);
            }

            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    fetchAnalytics(true);
                }
            });
            window.addEventListener('focus', function() {
                fetchAnalytics(true);
            });

            startPolling();
        })();

   
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('wishlist-setup-btn');

    if (!button) return;

    button.addEventListener('click', async function () {
        const originalText = button.innerHTML;

        button.disabled = true;
        button.textContent = 'Setting up Wishlist...';

        try {
            const response = await fetch(button.dataset.url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const responseText = await response.text();

            let data;

            try {
                data = JSON.parse(responseText);
            } catch (error) {
                console.error('Wishlist invalid JSON response:', {
                    status: response.status,
                    response: responseText
                });

                throw new Error('Server returned an invalid response.');
            }

            if (!response.ok || !data.success) {
                console.error('Wishlist setup API failed:', {
                    status: response.status,
                    response: data
                });

                throw new Error(
                    data.message || `Request failed: ${response.status}`
                );
            }

            if (!data.deep_link_url) {
                throw new Error('Theme Editor URL missing.');
            }

            console.info('Wishlist setup successful:', data);

            // Navigate outside Shopify embedded iframe.
            window.top.location.href = data.deep_link_url;

        } catch (error) {
            console.error('Wishlist setup failed:', error);

            alert(error.message || 'Wishlist setup failed.');

            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
});

    </script>
@endpush
