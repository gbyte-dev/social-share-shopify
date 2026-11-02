@extends('layouts.shopify')

@section('title', 'SocialNest Dashboard - ' . $shop)
@section('header_title', 'Dashboard & Analytics')
@section('header_subtitle', 'Storefront social click analytics, engagement trends, and active links')

@section('header_actions')
    <a href="{{ route('shopify.social-links.create') }}" class="btn btn-primary btn-sm">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>Add Social Link</span>
    </a>
@endsection

@section('content')
    <!-- Store Overview Metric Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.75rem;">
        <!-- Total Links -->
        <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Configured Links</div>
                <div style="font-size: 1.75rem; font-weight: 800; color: var(--text-main);">{{ $totalLinks }}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.15rem;">{{ $activeLinks }} active &bull; {{ $inactiveLinks }} inactive</div>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
            </div>
        </div>

        <!-- Storefront Widget Status -->
        <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Floating Dock</div>
                <div style="display: flex; align-items: center; gap: 0.4rem; margin: 0.2rem 0;">
                    @if($settings->is_enabled)
                        <span style="font-size: 0.8125rem; font-weight: 700; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 0.15rem 0.6rem; border-radius: 9999px;">Enabled</span>
                    @else
                        <span style="font-size: 0.8125rem; font-weight: 700; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 0.15rem 0.6rem; border-radius: 9999px;">Disabled</span>
                    @endif
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">{{ ucfirst(str_replace('_', ' ', $settings->position)) }} &bull; {{ ucfirst($settings->icon_size) }}</div>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #f0fdf4; color: #16a34a; display: flex; align-items: center; justify-content: center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
        </div>

        <!-- Product Share Button -->
        <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Product Sharing</div>
                <div style="display: flex; align-items: center; gap: 0.4rem; margin: 0.2rem 0;">
                    @if($settings->product_share_enabled ?? true)
                        <span style="font-size: 0.8125rem; font-weight: 700; background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 0.15rem 0.6rem; border-radius: 9999px;">Active</span>
                    @else
                        <span style="font-size: 0.8125rem; font-weight: 700; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 0.15rem 0.6rem; border-radius: 9999px;">Off</span>
                    @endif
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Product Page Button</div>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
            </div>
        </div>

        <!-- Quick Settings Link -->
        <div class="card" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem;">
            <div>
                <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.25rem;">Widget Controls</div>
                <div style="font-size: 0.9375rem; font-weight: 700; color: var(--text-main); margin-top: 0.25rem;">Theme & Layout</div>
                <a href="{{ route('shopify.settings.index') }}" style="font-size: 0.75rem; font-weight: 700; color: var(--primary); text-decoration: none; margin-top: 0.2rem; display: inline-block;">Configure &rarr;</a>
            </div>
            <div style="width: 44px; height: 44px; border-radius: 10px; background: #f8fafc; color: #64748b; display: flex; align-items: center; justify-content: center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
        </div>
    </div>

    <!-- Social Link Click Analytics Section -->
    <div id="sn-analytics-section" style="margin-bottom: 2rem;">
        <!-- Analytics Header & Filter Bar -->
        <div class="card" style="margin-bottom: 1.25rem; padding: 1.25rem 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <h2 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0;">Social Link Analytics</h2>
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.6875rem; font-weight: 700; color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.5rem; border-radius: 9999px;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                            Live Click Tracking
                        </span>
                    </div>
                    <p style="font-size: 0.8125rem; color: var(--text-subtle); margin: 0.25rem 0 0 0;">
                        Actual shopper clicks recorded by SocialNest widgets. Timezone: <strong id="sn-tz-display">{{ $analyticsData['filter']['timezone'] }}</strong>
                    </p>
                </div>

                <!-- Event Type Tabs / Segmented Control -->
                <div style="display: inline-flex; background: #f1f5f9; padding: 3px; border-radius: 8px; border: 1px solid #e2e8f0;" id="sn-event-type-tabs">
                    <button type="button" class="sn-filter-btn active" data-type="all" style="padding: 0.35rem 0.75rem; font-size: 0.8125rem; font-weight: 700; border: none; background: #ffffff; color: var(--text-main); border-radius: 6px; cursor: pointer; box-shadow: 0 1px 2px rgba(0,0,0,0.05); transition: all 0.15s;">
                        All Clicks
                    </button>
                    <button type="button" class="sn-filter-btn" data-type="social_links" style="padding: 0.35rem 0.75rem; font-size: 0.8125rem; font-weight: 600; border: none; background: transparent; color: var(--text-subtle); border-radius: 6px; cursor: pointer; transition: all 0.15s;">
                        Social Links
                    </button>
                    <button type="button" class="sn-filter-btn" data-type="product_shares" style="padding: 0.35rem 0.75rem; font-size: 0.8125rem; font-weight: 600; border: none; background: transparent; color: var(--text-subtle); border-radius: 6px; cursor: pointer; transition: all 0.15s;">
                        Product Shares
                    </button>
                </div>
            </div>

            <!-- Date Range Filters Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; border-top: 1px solid #f1f5f9; padding-top: 0.85rem;">
                <div style="display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;" id="sn-date-range-pills">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; margin-right: 0.25rem;">Date Range:</span>
                    <button type="button" class="sn-date-btn active" data-range="last_7_days" style="padding: 0.3rem 0.65rem; font-size: 0.8125rem; font-weight: 700; border: 1px solid var(--primary); background: #eef2ff; color: var(--primary); border-radius: 6px; cursor: pointer;">
                        Last 7 Days
                    </button>
                    <button type="button" class="sn-date-btn" data-range="last_30_days" style="padding: 0.3rem 0.65rem; font-size: 0.8125rem; font-weight: 600; border: 1px solid var(--card-border); background: #ffffff; color: var(--text-subtle); border-radius: 6px; cursor: pointer;">
                        Last 30 Days
                    </button>
                    <button type="button" class="sn-date-btn" data-range="last_90_days" style="padding: 0.3rem 0.65rem; font-size: 0.8125rem; font-weight: 600; border: 1px solid var(--card-border); background: #ffffff; color: var(--text-subtle); border-radius: 6px; cursor: pointer;">
                        Last 90 Days
                    </button>
                    <button type="button" class="sn-date-btn" data-range="custom" style="padding: 0.3rem 0.65rem; font-size: 0.8125rem; font-weight: 600; border: 1px solid var(--card-border); background: #ffffff; color: var(--text-subtle); border-radius: 6px; cursor: pointer;">
                        Custom Range
                    </button>
                </div>

                <!-- Custom Date Inputs (hidden by default unless Custom is active) -->
                <div id="sn-custom-date-box" style="display: none; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                        <label style="font-size: 0.75rem; color: var(--text-subtle);">From:</label>
                        <input type="date" id="sn-start-date" value="{{ $analyticsData['filter']['start_date'] }}" style="padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid var(--card-border); border-radius: 6px;">
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.35rem;">
                        <label style="font-size: 0.75rem; color: var(--text-subtle);">To:</label>
                        <input type="date" id="sn-end-date" value="{{ $analyticsData['filter']['end_date'] }}" style="padding: 0.3rem 0.5rem; font-size: 0.8125rem; border: 1px solid var(--card-border); border-radius: 6px;">
                    </div>
                    <button type="button" id="sn-apply-custom-date" class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;">
                        Apply
                    </button>
                </div>

                <div style="font-size: 0.75rem; color: var(--text-muted); display: flex; align-items: center; gap: 0.3rem;" id="sn-range-label">
                    <span>Filtering:</span>
                    <strong id="sn-range-dates">{{ $analyticsData['filter']['start_date'] }} to {{ $analyticsData['filter']['end_date'] }}</strong>
                </div>
            </div>
        </div>

        <!-- 4 Summary Metric Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;" id="sn-summary-cards">
            <!-- Total Clicks -->
            <div class="card" style="padding: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em;">Total Clicks</span>
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                    </span>
                </div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--text-main); line-height: 1.1;" id="sn-total-clicks">
                    {{ number_format($analyticsData['summary']['total_clicks']) }}
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;" id="sn-breakdown-clicks">
                    {{ $analyticsData['summary']['social_links_clicks'] }} dock &bull; {{ $analyticsData['summary']['product_shares_clicks'] }} shares
                </div>
            </div>

            <!-- Clicks Today -->
            <div class="card" style="padding: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em;">Clicks Today</span>
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                    </span>
                </div>
                <div style="font-size: 2rem; font-weight: 800; color: #059669; line-height: 1.1;" id="sn-today-clicks">
                    {{ number_format($analyticsData['summary']['clicks_today']) }}
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">
                    Shopper clicks today
                </div>
            </div>

            <!-- Clicks This Week -->
            <div class="card" style="padding: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em;">Clicks This Week</span>
                    <span style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </span>
                </div>
                <div style="font-size: 2rem; font-weight: 800; color: #2563eb; line-height: 1.1;" id="sn-week-clicks">
                    {{ number_format($analyticsData['summary']['clicks_this_week']) }}
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;">
                    Mon - Sun current week
                </div>
            </div>

            <!-- Top Social Platform -->
            <div class="card" style="padding: 1.25rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em;">Most Clicked Platform</span>
                    <span id="sn-top-platform-badge" style="width: 32px; height: 32px; border-radius: 8px; background: {{ $analyticsData['summary']['top_platform_color'] ?? '#e2e8f0' }}; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
                        @if(!empty($analyticsData['summary']['top_platform_icon']))
                            {!! $analyticsData['summary']['top_platform_icon'] !!}
                        @else
                            <span style="font-weight: 800; font-size: 0.8125rem;">—</span>
                        @endif
                    </span>
                </div>
                <div style="font-size: 1.625rem; font-weight: 800; color: var(--text-main); line-height: 1.1;" id="sn-top-platform-name">
                    {{ $analyticsData['summary']['top_platform_name'] ?? 'None yet' }}
                </div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.35rem;" id="sn-top-platform-clicks">
                    @if($analyticsData['summary']['top_platform'])
                        {{ number_format($analyticsData['summary']['top_platform_clicks']) }} recorded clicks
                    @else
                        Awaiting first click
                    @endif
                </div>
            </div>
        </div>

        <!-- 2 Side-by-Side Charts (Weekly Clicks & Platform Comparison) -->
        <div style="display: grid; grid-template-columns: 3fr 2fr; gap: 1.25rem; margin-bottom: 1.5rem;" class="sn-charts-grid">
            <!-- Weekly Clicks Daily Bar Chart (Mon - Sun) -->
            <div class="card" style="padding: 1.25rem 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div>
                        <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin: 0;">Weekly Clicks Overview</h3>
                        <p style="font-size: 0.75rem; color: var(--text-subtle); margin: 0.15rem 0 0 0;" id="sn-week-range-text">
                            Monday to Sunday ({{ $analyticsData['weekly_chart']['week_range'] }})
                        </p>
                    </div>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--primary); background: #eef2ff; padding: 0.2rem 0.55rem; border-radius: 6px;" id="sn-week-total-pill">
                        {{ $analyticsData['weekly_chart']['total_week_clicks'] }} clicks this week
                    </span>
                </div>

                <div style="position: relative; height: 240px;" id="sn-weekly-chart-wrapper">
                    <canvas id="sn-weekly-chart"></canvas>
                    <div id="sn-weekly-empty-state" style="{{ $analyticsData['weekly_chart']['has_data'] ? 'display: none;' : 'display: flex;' }} position: absolute; inset: 0; align-items: center; justify-content: center; flex-direction: column; background: rgba(255,255,255,0.95); text-align: center; border-radius: 8px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 0.5rem;"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <div style="font-size: 0.875rem; font-weight: 700; color: var(--text-main);">No clicks recorded for this week yet</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 280px; margin-top: 0.2rem;">Days with zero clicks are tracked. As visitors click social icons, daily counts update automatically.</div>
                    </div>
                </div>
            </div>

            <!-- Clicks by Social Platform Chart -->
            <div class="card" style="padding: 1.25rem 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div>
                        <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin: 0;">Clicks by Platform</h3>
                        <p style="font-size: 0.75rem; color: var(--text-subtle); margin: 0.15rem 0 0 0;">Platform distribution for selected period</p>
                    </div>
                </div>

                <div style="position: relative; height: 240px;" id="sn-platform-chart-wrapper">
                    <canvas id="sn-platform-chart"></canvas>
                    <div id="sn-platform-empty-state" style="{{ $analyticsData['platform_chart']['has_data'] ? 'display: none;' : 'display: flex;' }} position: absolute; inset: 0; align-items: center; justify-content: center; flex-direction: column; background: rgba(255,255,255,0.95); text-align: center; border-radius: 8px;">
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5" style="margin-bottom: 0.5rem;"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"/><path d="M22 12A10 10 0 0 0 12 2v10z"/></svg>
                        <div style="font-size: 0.875rem; font-weight: 700; color: var(--text-main);">No platform clicks recorded</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 240px; margin-top: 0.2rem;">Only active platforms for your store appear here once shoppers interact.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Link Performance Table -->
        <div class="card" style="padding: 1.25rem 1.5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h3 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin: 0;">Social Link Performance</h3>
                    <p style="font-size: 0.75rem; color: var(--text-subtle); margin: 0.15rem 0 0 0;">
                        Individual link click totals, weekly volume, and last click timestamp (including zero-click and historical links)
                    </p>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span style="font-size: 0.75rem; color: var(--text-subtle);" id="sn-table-count">{{ count($analyticsData['social_links']['links']) }} links tracked</span>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem;">
                    <thead>
                        <tr style="border-bottom: 1.5px solid var(--card-border); color: var(--text-subtle); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            <th style="padding: 0.65rem 0.75rem 0.65rem 0;">Social Link</th>
                            <th style="padding: 0.65rem 0.75rem;">Platform</th>
                            <th style="padding: 0.65rem 0.75rem;">Status</th>
                            <th style="padding: 0.65rem 0.75rem; text-align: right;">Total Clicks</th>
                            <th style="padding: 0.65rem 0.75rem; text-align: right;">This Week</th>
                            <th style="padding: 0.65rem 0 0.65rem 0.75rem; text-align: right;">Last Click</th>
                        </tr>
                    </thead>
                    <tbody id="sn-performance-tbody">
                        @forelse($analyticsData['social_links']['links'] as $item)
                            <tr style="border-bottom: 1px solid #f1f5f9;" class="sn-perf-row">
                                <td style="padding: 0.75rem 0.75rem 0.75rem 0;">
                                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                                        <div style="width: 32px; height: 32px; border-radius: 8px; background: {{ $item['brand_color'] }}; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">
                                            {!! $item['icon_html'] ?? $item['icon_svg'] !!}
                                        </div>
                                        <div>
                                            <div style="font-weight: 700; color: var(--text-main);">
                                                {{ $item['label'] }}
                                            </div>
                                            @if(!empty($item['url']))
                                                <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    {{ $item['url'] }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="padding: 0.75rem;">
                                    <span style="font-size: 0.75rem; font-weight: 600; text-transform: capitalize; color: var(--text-main);">
                                        {{ $item['platform_name'] }}
                                    </span>
                                </td>
                                <td style="padding: 0.75rem;">
                                    @if($item['is_deleted'] ?? false)
                                        <span style="font-size: 0.75rem; font-weight: 700; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; padding: 0.15rem 0.5rem; border-radius: 9999px;">Archived</span>
                                    @elseif($item['is_active'])
                                        <span style="font-size: 0.75rem; font-weight: 700; color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.5rem; border-radius: 9999px;">Active</span>
                                    @else
                                        <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 0.15rem 0.5rem; border-radius: 9999px;">Inactive</span>
                                    @endif
                                </td>
                                <td style="padding: 0.75rem; text-align: right;">
                                    <span style="display: inline-block; font-weight: 800; font-size: 0.875rem; color: var(--text-main); background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.15rem 0.6rem; border-radius: 6px;">
                                        {{ number_format($item['total_clicks']) }}
                                    </span>
                                </td>
                                <td style="padding: 0.75rem; text-align: right;">
                                    <span style="font-weight: 700; color: {{ $item['week_clicks'] > 0 ? 'var(--primary)' : 'var(--text-subtle)' }}; font-size: 0.8125rem;">
                                        {{ number_format($item['week_clicks']) }}
                                    </span>
                                </td>
                                <td style="padding: 0.75rem 0 0.75rem 0.75rem; text-align: right; font-size: 0.8125rem; color: {{ $item['last_clicked_formatted'] === 'Never' ? 'var(--text-subtle)' : 'var(--text-main)' }}; font-weight: {{ $item['last_clicked_formatted'] === 'Never' ? '400' : '600' }};">
                                    {{ $item['last_clicked_formatted'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.875rem;">
                                    No social links found for this store. Add links in the Social Links section to start tracking.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Responsive Styles for Analytics Grid & Platform Icons -->
    <style>
        #sn-top-platform-badge svg,
        .sn-perf-row td svg {
            width: 18px;
            height: 18px;
            display: block;
            fill: currentColor;
        }
        #sn-top-platform-badge img,
        .sn-perf-row td img {
            max-width: 20px;
            max-height: 20px;
            object-fit: contain;
            display: block;
        }
        @media (max-width: 900px) {
            .sn-charts-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
@endsection

@push('scripts')
    <!-- Chart.js 4.4.1 for interactive, Polaris-style analytics graphs -->
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
                                borderRadius: 6,
                                maxBarThickness: 32,
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#1e293b',
                                    titleFont: { size: 12, weight: '700' },
                                    bodyFont: { size: 12 },
                                    padding: 10,
                                    cornerRadius: 6,
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
                                        font: { size: 11 },
                                        color: '#64748b'
                                    },
                                    grid: {
                                        color: '#f1f5f9'
                                    }
                                },
                                x: {
                                    ticks: {
                                        font: { size: 11, weight: '600' },
                                        color: '#475569'
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
                                        boxWidth: 12,
                                        padding: 12,
                                        font: { size: 11, weight: '600' },
                                        color: '#334155'
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#1e293b',
                                    titleFont: { size: 12, weight: '700' },
                                    bodyFont: { size: 12 },
                                    padding: 10,
                                    cornerRadius: 6,
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
                            cutout: '62%'
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
                if (breakdownClicksEl) breakdownClicksEl.textContent = (s.social_links_clicks || 0) + ' dock \u2022 ' + (s.product_shares_clicks || 0) + ' shares';
                if (todayClicksEl) todayClicksEl.textContent = Number(s.clicks_today || 0).toLocaleString();
                if (weekClicksEl) weekClicksEl.textContent = Number(s.clicks_this_week || 0).toLocaleString();

                if (topPlatformBadge) {
                    topPlatformBadge.style.background = s.top_platform_color || '#e2e8f0';
                    if (s.top_platform_icon) {
                        topPlatformBadge.innerHTML = s.top_platform_icon;
                    } else {
                        topPlatformBadge.innerHTML = '<span style="font-weight: 800; font-size: 0.8125rem;">—</span>';
                    }
                }
                if (topPlatformName) {
                    topPlatformName.textContent = s.top_platform_name || 'None yet';
                }
                if (topPlatformClicks) {
                    topPlatformClicks.textContent = s.top_platform ? Number(s.top_platform_clicks || 0).toLocaleString() + ' recorded clicks' : 'Awaiting first click';
                }

                // Update week range labels
                const w = data.weekly_chart || {};
                if (weekTotalPill) weekTotalPill.textContent = (w.total_week_clicks || 0) + ' clicks this week';
                if (weekRangeText) weekRangeText.textContent = 'Monday to Sunday (' + (w.week_range || '') + ')';

                // Update date filter indicator
                if (rangeDatesLabel && data.filter) {
                    rangeDatesLabel.textContent = data.filter.start_date + ' to ' + data.filter.end_date;
                }

                // Update charts
                initWeeklyChart(w);
                initPlatformChart(data.platform_chart || {});

                // Update performance table
                const links = (data.social_links && data.social_links.links) ? data.social_links.links : [];
                if (tableCount) tableCount.textContent = links.length + ' links tracked';

                if (tbody) {
                    if (links.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" style="padding: 2rem; text-align: center; color: var(--text-muted); font-size: 0.875rem;">No social links found for this store. Add links in the Social Links section to start tracking.</td></tr>';
                    } else {
                        tbody.innerHTML = links.map(function(item) {
                            const badge = item.is_deleted
                                ? '<span style="font-size: 0.75rem; font-weight: 700; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; padding: 0.15rem 0.5rem; border-radius: 9999px;">Archived</span>'
                                : (item.is_active
                                    ? '<span style="font-size: 0.75rem; font-weight: 700; color: #065f46; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.5rem; border-radius: 9999px;">Active</span>'
                                    : '<span style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; border: 1px solid #cbd5e1; padding: 0.15rem 0.5rem; border-radius: 9999px;">Inactive</span>');

                            const urlHtml = item.url ? '<div style="font-size: 0.75rem; color: var(--text-muted); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + escapeHtml(item.url) + '</div>' : '';
                            const isNever = item.last_clicked_formatted === 'Never';

                            const iconContent = item.icon_html || item.icon_svg || ('<span style="font-weight: 700; font-size: 0.75rem;">' + escapeHtml((item.platform || '').slice(0, 2).toUpperCase()) + '</span>');

                            return '<tr style="border-bottom: 1px solid #f1f5f9;" class="sn-perf-row">' +
                                '<td style="padding: 0.75rem 0.75rem 0.75rem 0;">' +
                                    '<div style="display: flex; align-items: center; gap: 0.6rem;">' +
                                        '<div style="width: 32px; height: 32px; border-radius: 8px; background: ' + (item.brand_color || '#4f46e5') + '; color: #ffffff; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden;">' +
                                            iconContent +
                                        '</div>' +
                                        '<div>' +
                                            '<div style="font-weight: 700; color: var(--text-main);">' + escapeHtml(item.label || item.platform_name) + '</div>' +
                                            urlHtml +
                                        '</div>' +
                                    '</div>' +
                                '</td>' +
                                '<td style="padding: 0.75rem;"><span style="font-size: 0.75rem; font-weight: 600; text-transform: capitalize; color: var(--text-main);">' + escapeHtml(item.platform_name) + '</span></td>' +
                                '<td style="padding: 0.75rem;">' + badge + '</td>' +
                                '<td style="padding: 0.75rem; text-align: right;"><span style="display: inline-block; font-weight: 800; font-size: 0.875rem; color: var(--text-main); background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.15rem 0.6rem; border-radius: 6px;">' + Number(item.total_clicks || 0).toLocaleString() + '</span></td>' +
                                '<td style="padding: 0.75rem; text-align: right;"><span style="font-weight: 700; color: ' + (item.week_clicks > 0 ? 'var(--primary)' : 'var(--text-subtle)') + '; font-size: 0.8125rem;">' + Number(item.week_clicks || 0).toLocaleString() + '</span></td>' +
                                '<td style="padding: 0.75rem 0 0.75rem 0.75rem; text-align: right; font-size: 0.8125rem; color: ' + (isNever ? 'var(--text-subtle)' : 'var(--text-main)') + '; font-weight: ' + (isNever ? '400' : '600') + ';">' + escapeHtml(item.last_clicked_formatted) + '</td>' +
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
                        b.style.background = 'transparent';
                        b.style.color = 'var(--text-subtle)';
                        b.style.fontWeight = '600';
                        b.style.boxShadow = 'none';
                    });

                    btn.classList.add('active');
                    btn.style.background = '#ffffff';
                    btn.style.color = 'var(--text-main)';
                    btn.style.fontWeight = '700';
                    btn.style.boxShadow = '0 1px 2px rgba(0,0,0,0.05)';

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
                        b.style.borderColor = 'var(--card-border)';
                        b.style.background = '#ffffff';
                        b.style.color = 'var(--text-subtle)';
                        b.style.fontWeight = '600';
                    });

                    btn.classList.add('active');
                    btn.style.borderColor = 'var(--primary)';
                    btn.style.background = '#eef2ff';
                    btn.style.color = 'var(--primary)';
                    btn.style.fontWeight = '700';

                    state.dateRange = btn.dataset.range;

                    if (state.dateRange === 'custom') {
                        if (customBox) customBox.style.display = 'flex';
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

            // 5-second polling interval to refresh analytics automatically while the dashboard is open
            let pollTimer = null;
            function startPolling() {
                if (pollTimer) clearInterval(pollTimer);
                pollTimer = setInterval(function() {
                    if (!document.hidden && !state.loading) {
                        fetchAnalytics(true);
                    }
                }, 5000);
            }

            // Immediately refresh analytics when the merchant returns to the dashboard tab
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
    </script>
@endpush
