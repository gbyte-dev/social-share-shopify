
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

    @media(max-width: 1100px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
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
