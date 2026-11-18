
@extends('admin.layout')

@section('title', 'Merchants')
@section('heading', 'Merchants')

@push('styles')
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 22px;
    }

    .page-title {
        font-size: 21px;
        font-weight: 800;
        letter-spacing: -.6px;
        margin: 0 0 5px;
    }

    .page-description {
        color: #6d7175;
        font-size: 12px;
        margin: 0;
    }

    .merchant-count {
        background: #fff;
        border: 1px solid #e1e3e5;
        border-radius: 7px;
        padding: 9px 13px;
        font-size: 11px;
        font-weight: 700;
    }

    .filter-card,
    .table-card {
        background: #fff;
        border: 1px solid #e1e3e5;
        border-radius: 9px;
    }

    .filter-card {
        padding: 14px;
        margin-bottom: 15px;
    }

    .filter-form {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-input,
    .filter-select {
        height: 37px;
        padding: 0 11px;
        border: 1px solid #c9cccf;
        border-radius: 6px;
        background: #fff;
        color: #202223;
        font-family: inherit;
        font-size: 11px;
        outline: none;
    }

    .filter-input {
        width: 260px;
        max-width: 100%;
    }

    .filter-select {
        min-width: 135px;
    }

    .filter-input:focus,
    .filter-select:focus {
        border-color: #008060;
        box-shadow: 0 0 0 2px rgba(0, 128, 96, .1);
    }

    .filter-btn {
        height: 37px;
        padding: 0 15px;
        border: 0;
        border-radius: 6px;
        background: #008060;
        color: #fff;
        font-family: inherit;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
    }

    .reset-link {
        color: #606467;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        padding: 8px;
    }

    .table-heading {
        padding: 16px;
        border-bottom: 1px solid #e1e3e5;
        font-size: 13px;
        font-weight: 800;
    }

    .table-wrap {
        width: 100%;
        overflow-x: auto;
    }

    .merchant-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 750px;
    }

    .merchant-table th {
        padding: 12px 16px;
        text-align: left;
        background: #fafbfb;
        color: #6d7175;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }

    .merchant-table td {
        padding: 15px 16px;
        border-top: 1px solid #f0f1f2;
        font-size: 11px;
        vertical-align: middle;
    }

    .store-name {
        font-weight: 700;
        color: #202223;
        margin-bottom: 4px;
    }

    .store-domain {
        color: #8c9196;
        font-size: 10px;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 9px;
        font-weight: 700;
    }

    .status-active {
        background: #e4f5ec;
        color: #008060;
    }

    .status-uninstalled {
        background: #fcebea;
        color: #b42318;
    }

    .status-inactive {
        background: #f1f2f3;
        color: #606467;
    }

    .view-btn {
        display: inline-block;
        padding: 7px 11px;
        border: 1px solid #d2d5d8;
        border-radius: 6px;
        color: #202223;
        text-decoration: none;
        font-size: 10px;
        font-weight: 700;
        white-space: nowrap;
    }

    .view-btn:hover {
        border-color: #008060;
        color: #008060;
    }

    .empty-state {
        padding: 45px 15px;
        text-align: center;
        color: #6d7175;
        font-size: 12px;
    }

    .pagination-wrap {
        padding: 14px 16px;
        border-top: 1px solid #e1e3e5;
    }

    .pagination-wrap nav {
        display: flex;
        justify-content: flex-end;
    }

    .pagination-wrap nav > div:first-child {
        display: none;
    }

    .pagination-wrap svg {
        width: 15px;
    }

    @media(max-width: 600px) {
        .page-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .filter-form {
            flex-wrap: wrap;
        }

        .filter-input {
            width: 100%;
        }

        .filter-select {
            flex: 1;
        }
    }
</style>
@endpush

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Merchants</h1>
        <p class="page-description">
            Manage and monitor your Socialvire app merchants.
        </p>
    </div>

    <div class="merchant-count">
        {{ $merchants->total() }} Total Merchants
    </div>
</div>

<div class="filter-card">
    <form method="GET" action="{{ route('admin.merchants.index') }}"
          class="filter-form">

        <input
            type="text"
            name="search"
            class="filter-input"
            placeholder="Search Shopify store..."
            value="{{ request('search') }}"
        >

        <select name="status" class="filter-select">
            <option value="">All Merchants</option>
            <option value="active" @selected(request('status') === 'active')>
                Active
            </option>
            <option value="uninstalled" @selected(request('status') === 'uninstalled')>
                Uninstalled
            </option>
        </select>

        <button type="submit" class="filter-btn">
            Apply Filters
        </button>

        <a href="{{ route('admin.merchants.index') }}" class="reset-link">
            Reset
        </a>
    </form>
</div>

<div class="table-card">
    <div class="table-heading">
        All Merchants
    </div>

    <div class="table-wrap">
        <table class="merchant-table">
            <thead>
                <tr>
                    <th>STORE</th>
                    <th>PLAN</th>
                    <th>ACCOUNT STATUS</th>
                    <th>SUBSCRIPTION</th>
                    <th>INSTALLED ON</th>
                    <th>ACTION</th>
                </tr>
            </thead>

            <tbody>
                @forelse($merchants as $merchant)
                    @php
                        $subscription = $merchant->activeSubscription;
                        $isUninstalled = $merchant->uninstalled_at !== null;
                        $isActive = $merchant->is_active && !$isUninstalled;
                    @endphp

                    <tr>
                        <td>
                            <div class="store-name">
                                {{ $merchant->shop }}
                            </div>
                            <div class="store-domain">
                                Merchant ID: #{{ $merchant->id }}
                            </div>
                        </td>

                        <td>
                            {{ $subscription?->plan?->name ?? 'No active plan' }}
                        </td>

                        <td>
                            @if($isUninstalled)
                                <span class="status-badge status-uninstalled">
                                    Uninstalled
                                </span>
                            @elseif($isActive)
                                <span class="status-badge status-active">
                                    Active
                                </span>
                            @else
                                <span class="status-badge status-inactive">
                                    Inactive
                                </span>
                            @endif
                        </td>

                        <td>
                            @if($subscription)
                                <span class="status-badge status-active">
                                    Active
                                </span>
                            @else
                                <span class="status-badge status-inactive">
                                    No Active Subscription
                                </span>
                            @endif
                        </td>

                        <td>
                            {{ $merchant->installed_at?->format('d M Y') ?? 'N/A' }}
                        </td>

                        <td>
                            <a
                                href="{{ route('admin.merchants.show', $merchant) }}"
                                class="view-btn"
                            >
                                View Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                No merchants found.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($merchants->hasPages())
        <div class="pagination-wrap">
            {{ $merchants->links() }}
        </div>
    @endif
</div>

@endsection
