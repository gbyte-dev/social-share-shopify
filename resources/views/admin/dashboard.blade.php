
@extends('admin.layout')

@section('title', 'Dashboard')
@section('heading', 'Overview')

@section('content')

<style>
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 23px;
    }

    .dashboard-header h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -.7px;
    }

    .dashboard-header p {
        margin: 6px 0 0;
        color: #6d7175;
        font-size: 12px;
    }

    .date-label {
        padding: 9px 12px;
        border: 1px solid #e1e3e5;
        background: white;
        border-radius: 7px;
        color: #55595c;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 22px;
    }

    .stat-card {
        padding: 17px;
        background: white;
        border: 1px solid #e1e3e5;
        border-radius: 9px;
    }

    .stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-label {
        color: #6d7175;
        font-size: 11px;
        font-weight: 600;
    }

    .stat-icon {
        width: 32px;
        height: 32px;
        display: grid;
        place-items: center;
        border-radius: 8px;
        background: #eaf5f0;
        color: #008060;
    }

    .stat-icon svg {
        width: 17px;
        height: 17px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .stat-value {
        margin-top: 16px;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -.8px;
    }

    .stat-description {
        margin-top: 5px;
        color: #92969a;
        font-size: 10px;
    }

    .dashboard-panel {
        background: white;
        border: 1px solid #e1e3e5;
        border-radius: 9px;
        overflow: hidden;
    }

    .panel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 17px;
        border-bottom: 1px solid #eeeeef;
    }

    .panel-header h3 {
        margin: 0;
        font-size: 13px;
        font-weight: 800;
    }

    .panel-subtitle {
        margin-top: 5px;
        color: #92969a;
        font-size: 10px;
    }

    .table-wrapper {
        width: 100%;
        overflow-x: auto;
    }

    .merchant-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .merchant-table th {
        padding: 12px 17px;
        background: #fafafa;
        color: #73777a;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }

    .merchant-table td {
        padding: 14px 17px;
        border-top: 1px solid #eeeeef;
        font-size: 11px;
        white-space: nowrap;
    }

    .merchant-name {
        font-weight: 700;
        color: #202223;
    }

    .merchant-id {
        display: block;
        margin-top: 4px;
        color: #92969a;
        font-size: 9px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 8px;
        border-radius: 5px;
        font-size: 9px;
        font-weight: 700;
    }

    .status-active {
        background: #eaf5f0;
        color: #008060;
    }

    .status-inactive {
        background: #f1f2f3;
        color: #6d7175;
    }

    .status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
    }

    .empty-state {
        min-height: 180px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 20px;
    }

    .empty-icon {
        width: 43px;
        height: 43px;
        display: grid;
        place-items: center;
        background: #f1f2f3;
        border-radius: 10px;
        margin-bottom: 12px;
        color: #73777a;
    }

    .empty-icon svg {
        width: 21px;
        height: 21px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.7;
    }

    .empty-state strong {
        font-size: 12px;
    }

    .empty-state p {
        margin: 6px 0 0;
        color: #92969a;
        font-size: 10px;
    }

    .system-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
        margin-top: 18px;
    }

    .system-item {
        padding: 15px;
        background: white;
        border: 1px solid #e1e3e5;
        border-radius: 8px;
    }

    .system-item span {
        display: block;
        color: #73777a;
        font-size: 10px;
    }

    .system-item strong {
        display: block;
        margin-top: 8px;
        font-size: 12px;
        font-weight: 700;
    }

    /* Analytics Two-Column Grid & Chart Cards */
    .analytics-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 22px;
    }

    .chart-card {
        background: white;
        border: 1px solid #e1e3e5;
        border-radius: 9px;
        padding: 18px 20px 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 380px;
    }

    .chart-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid #eeeeef;
    }

    .chart-header h3 {
        margin: 0;
        font-size: 13px;
        font-weight: 800;
        color: #202223;
    }

    .chart-subtitle {
        margin-top: 4px;
        color: #92969a;
        font-size: 10px;
    }

    .filter-group {
        display: inline-flex;
        background: #f1f2f3;
        padding: 2px;
        border-radius: 7px;
        border: 1px solid #e1e3e5;
    }

    .filter-pill {
        border: 0;
        background: transparent;
        padding: 4px 10px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 600;
        color: #6d7175;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }

    .filter-pill:hover {
        color: #202223;
    }

    .filter-pill.active {
        background: white;
        color: #008060;
        font-weight: 700;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .chart-body {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 250px;
    }

    .chart-summary {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 14px;
    }

    .summary-value {
        font-size: 22px;
        font-weight: 800;
        color: #202223;
        letter-spacing: -0.5px;
    }

    .summary-label {
        font-size: 11px;
        color: #6d7175;
        font-weight: 500;
    }

    .chart-canvas-container {
        position: relative;
        height: 230px;
        width: 100%;
    }

    .donut-layout {
        display: flex;
        align-items: center;
        gap: 22px;
        height: 100%;
    }

    .donut-canvas-container {
        position: relative;
        width: 190px;
        height: 190px;
        flex-shrink: 0;
        margin: 0 auto;
    }

    .donut-legend {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
    }

    .legend-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        font-size: 11px;
        padding: 6px 10px;
        border-radius: 6px;
        background: #fafafa;
        border: 1px solid #eeeeef;
    }

    .legend-left {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .legend-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .legend-name {
        color: #303438;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .legend-right {
        display: flex;
        align-items: baseline;
        gap: 5px;
        white-space: nowrap;
    }

    .legend-count {
        color: #202223;
        font-weight: 700;
    }

    .legend-percent {
        color: #92969a;
        font-size: 10px;
    }

    .badge-total {
        font-size: 11px;
        font-weight: 700;
        color: #008060;
        background: #eaf5f0;
        border: 1px solid #c6ead8;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .empty-chart-state {
        min-height: 220px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 20px;
    }

    .empty-chart-state strong {
        font-size: 12px;
        color: #202223;
    }

    .empty-chart-state p {
        margin: 5px 0 0;
        color: #92969a;
        font-size: 11px;
    }

    @media(max-width: 1100px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media(max-width: 960px) {
        .analytics-grid {
            grid-template-columns: 1fr;
        }
    }

    @media(max-width: 600px) {
        .dashboard-header {
            align-items: flex-start;
        }

        .dashboard-header h1 {
            font-size: 19px;
        }

        .date-label {
            display: none;
        }

        .system-grid {
            grid-template-columns: 1fr;
        }
    }

    @media(max-width: 520px) {
        .donut-layout {
            flex-direction: column;
            gap: 16px;
        }

        .donut-legend {
            width: 100%;
        }
    }

    @media(max-width: 380px) {
        .stats-grid {
            gap: 8px;
        }

        .stat-card {
            padding: 12px;
        }

        .stat-value {
            font-size: 22px;
        }
    }
</style>

{{-- Dashboard Heading --}}
<div class="dashboard-header">
    <div>
        <h1>Welcome back, {{ auth('admin')->user()->name }}</h1>
        <p>Here's what's happening with your Socialvire application.</p>
    </div>

    <div class="date-label">
        {{ now()->format('d M Y') }}
    </div>
</div>

{{-- Statistics --}}
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Merchants</span>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
        </div>
        <div class="stat-value">{{ number_format($totalStores) }}</div>
        <div class="stat-description">Registered Shopify stores</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Active Merchants</span>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24">
                    <path d="m5 12 4 4L19 6"/>
                    <circle cx="12" cy="12" r="10"/>
                </svg>
            </div>
        </div>
        <div class="stat-value">{{ number_format($activeStores) }}</div>
        <div class="stat-description">Currently installed stores</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Active Subscriptions</span>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <path d="M2 10h20"/>
                </svg>
            </div>
        </div>
        <div class="stat-value">{{ number_format($activeSubscriptions) }}</div>
        <div class="stat-description">Currently active plans</div>
    </div>

    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Social Links Created</span>
            <div class="stat-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                </svg>
            </div>
        </div>
        <div class="stat-value">{{ number_format($totalSocialLinks) }}</div>
        <div class="stat-description">Configured social links</div>
    </div>

</div>

{{-- Two-Column Analytics Section --}}
<div class="analytics-grid">

    {{-- Chart One: Merchant Registration Trend --}}
    <div class="chart-card">
        <div class="chart-header">
            <div>
                <h3>Merchant Registration Trend</h3>
                <div class="chart-subtitle">New store sign-ups over time</div>
            </div>

            <div class="filter-group" id="registration-range-filters">
                <button type="button" class="filter-pill" data-range="7">7 Days</button>
                <button type="button" class="filter-pill active" data-range="30">30 Days</button>
                <button type="button" class="filter-pill" data-range="90">90 Days</button>
            </div>
        </div>

        <div class="chart-body">
            <div class="chart-summary">
                <span class="summary-value" id="trend-total-count">{{ number_format($registrationTrends[30]['total'] ?? 0) }}</span>
                <span class="summary-label" id="trend-total-label">new merchants in last 30 days</span>
            </div>

            <div class="chart-canvas-container">
                <canvas id="registrationTrendChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Chart Two: Subscription Status Distribution --}}
    <div class="chart-card">
        <div class="chart-header">
            <div>
                <h3>Subscription Status Distribution</h3>
                <div class="chart-subtitle">Active, cancelled &amp; expired plan breakdown</div>
            </div>

            <span class="badge-total">{{ number_format($totalSubscriptions) }} Subscriptions</span>
        </div>

        <div class="chart-body">
            @if($totalSubscriptions > 0)
                <div class="donut-layout">
                    <div class="donut-canvas-container">
                        <canvas id="subscriptionDistributionChart"></canvas>
                    </div>

                    <div class="donut-legend">
                        @foreach($subscriptionDistribution as $dist)
                            <div class="legend-row">
                                <div class="legend-left">
                                    <span class="legend-dot" style="background-color: {{ $dist['color'] }};"></span>
                                    <span class="legend-name">{{ $dist['label'] }}</span>
                                </div>
                                <div class="legend-right">
                                    <strong class="legend-count">{{ number_format($dist['count']) }}</strong>
                                    <span class="legend-percent">({{ $dist['percentage'] }}%)</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="empty-chart-state">
                    <div class="empty-icon">
                        <svg viewBox="0 0 24 24">
                            <rect x="2" y="5" width="20" height="14" rx="2"/>
                            <path d="M2 10h20"/>
                        </svg>
                    </div>
                    <strong>No subscriptions recorded</strong>
                    <p>Status distribution will appear here once merchants subscribe to plans.</p>
                </div>
            @endif
        </div>
    </div>

</div>

{{-- Recent Merchants --}}
<div class="dashboard-panel">

    <div class="panel-header">
        <div>
            <h3>Recent Merchants</h3>
            <div class="panel-subtitle">
                Latest Shopify stores registered in Socialvire
            </div>
        </div>
    </div>

    @if($recentStores->isNotEmpty())

        <div class="table-wrapper">
            <table class="merchant-table">
                <thead>
                    <tr>
                        <th>STORE</th>
                        <th>STATUS</th>
                        <th>INSTALLED ON</th>
                        <th>REGISTERED</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($recentStores as $store)
                        <tr>
                            <td>
                                <span class="merchant-name">
                                    {{ $store->shop }}
                                </span>

                                <span class="merchant-id">
                                    Store #{{ $store->id }}
                                </span>
                            </td>

                            <td>
                                @if($store->is_active && !$store->uninstalled_at)
                                    <span class="status-badge status-active">
                                        <span class="status-dot"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="status-badge status-inactive">
                                        <span class="status-dot"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>

                            <td>
                                {{ $store->installed_at
                                    ? $store->installed_at->format('d M Y')
                                    : '—' }}
                            </td>

                            <td>
                                {{ $store->created_at
                                    ? $store->created_at->format('d M Y')
                                    : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    @else

        <div class="empty-state">
            <div class="empty-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="4" width="18" height="16" rx="2"/>
                    <path d="M3 10h18"/>
                </svg>
            </div>

            <strong>No merchants found</strong>
            <p>Shopify stores will appear here when registered.</p>
        </div>

    @endif

</div>

{{-- System Information --}}
<div class="system-grid">

    <div class="system-item">
        <span>Admin Account</span>
        <strong>{{ auth('admin')->user()->email }}</strong>
    </div>

    <div class="system-item">
        <span>Authentication</span>
        <strong>Separate Admin Guard</strong>
    </div>

    <div class="system-item">
        <span>Application</span>
        <strong>Socialvire Admin</strong>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') {
        console.warn('[Socialvire] Chart.js failed to load from CDN.');
        return;
    }

    // --- Chart One: Merchant Registration Trend (Line Chart) ---
    const trendsData = @json($registrationTrends ?? []);
    let currentRange = 30;

    const lineCanvas = document.getElementById('registrationTrendChart');
    let trendChart = null;

    if (lineCanvas && trendsData && trendsData[currentRange]) {
        const ctx = lineCanvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 220);
        gradient.addColorStop(0, 'rgba(0, 128, 96, 0.20)');
        gradient.addColorStop(1, 'rgba(0, 128, 96, 0.00)');

        trendChart = new Chart(lineCanvas, {
            type: 'line',
            data: {
                labels: trendsData[currentRange].labels,
                datasets: [{
                    label: 'New Merchants',
                    data: trendsData[currentRange].data,
                    borderColor: '#008060',
                    borderWidth: 2.2,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#008060',
                    pointBorderWidth: 2,
                    pointRadius: currentRange > 30 ? 2 : 3.5,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#008060',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#202223',
                        titleFont: { family: 'Inter', size: 11, weight: '700' },
                        bodyFont: { family: 'Inter', size: 12, weight: '600' },
                        padding: 10,
                        cornerRadius: 6,
                        displayColors: false,
                        callbacks: {
                            label: function (context) {
                                const val = context.parsed.y || 0;
                                return val === 1 ? '1 new merchant' : `${val} new merchants`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            color: '#92969a',
                            font: { family: 'Inter', size: 10 },
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: currentRange === 7 ? 7 : (currentRange === 30 ? 8 : 10)
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                            color: '#92969a',
                            font: { family: 'Inter', size: 10 },
                            padding: 8
                        },
                        grid: {
                            color: '#f1f2f3',
                            drawBorder: false
                        }
                    }
                }
            }
        });

        // Filter Pills: 7 Days / 30 Days / 90 Days
        const filterButtons = document.querySelectorAll('#registration-range-filters .filter-pill');
        const summaryValue = document.getElementById('trend-total-count');
        const summaryLabel = document.getElementById('trend-total-label');

        filterButtons.forEach(btn => {
            btn.addEventListener('click', function () {
                const range = parseInt(this.getAttribute('data-range'), 10);
                if (!trendsData[range] || range === currentRange) return;

                filterButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentRange = range;

                if (summaryValue) {
                    summaryValue.textContent = Number(trendsData[range].total).toLocaleString();
                }
                if (summaryLabel) {
                    summaryLabel.textContent = `new merchants in last ${range} days`;
                }

                trendChart.data.labels = trendsData[range].labels;
                trendChart.data.datasets[0].data = trendsData[range].data;
                trendChart.data.datasets[0].pointRadius = range > 30 ? 2 : 3.5;
                trendChart.options.scales.x.ticks.maxTicksLimit = range === 7 ? 7 : (range === 30 ? 8 : 10);
                trendChart.update();
            });
        });
    }

    // --- Chart Two: Subscription Status Distribution (Donut Chart) ---
    const subDistData = @json($subscriptionDistribution ?? []);
    const totalSubs = {{ (int) ($totalSubscriptions ?? 0) }};
    const donutCanvas = document.getElementById('subscriptionDistributionChart');

    if (donutCanvas && subDistData && subDistData.length > 0) {
        const labels = subDistData.map(d => d.label);
        const counts = subDistData.map(d => d.count);
        const colors = subDistData.map(d => d.color);

        // Center text plugin to display total subscriptions count in donut hole
        const centerTextPlugin = {
            id: 'centerText',
            afterDraw(chart) {
                const { ctx, chartArea: { top, bottom, left, right } } = chart;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                const centerX = (left + right) / 2;
                const centerY = (top + bottom) / 2;

                ctx.font = '800 22px Inter, sans-serif';
                ctx.fillStyle = '#202223';
                ctx.fillText(totalSubs.toLocaleString(), centerX, centerY - 7);

                ctx.font = '600 10px Inter, sans-serif';
                ctx.fillStyle = '#92969a';
                ctx.fillText('TOTAL', centerX, centerY + 13);
                ctx.restore();
            }
        };

        new Chart(donutCanvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: counts,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            plugins: [centerTextPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#202223',
                        titleFont: { family: 'Inter', size: 11, weight: '700' },
                        bodyFont: { family: 'Inter', size: 12, weight: '600' },
                        padding: 10,
                        cornerRadius: 6,
                        callbacks: {
                            label: function (context) {
                                const val = context.parsed || 0;
                                const pct = totalSubs > 0 ? ((val / totalSubs) * 100).toFixed(1) : 0;
                                return ` ${val.toLocaleString()} (${pct}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
