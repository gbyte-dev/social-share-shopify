
@extends('admin.layout')

@section('title', 'Merchant Details')
@section('heading', 'Merchant Details')

@push('styles')
<style>
    .details-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        margin-bottom: 22px;
    }

    .back-link {
        color: #606467;
        font-size: 11px;
        font-weight: 700;
        text-decoration: none;
    }

    .back-link:hover {
        color: #008060;
    }

    .merchant-heading {
        margin-top: 13px;
    }

    .merchant-heading h1 {
        font-size: 21px;
        font-weight: 800;
        margin: 0 0 6px;
        overflow-wrap: anywhere;
    }

    .merchant-heading p {
        color: #6d7175;
        font-size: 11px;
        margin: 0;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .details-card {
        background: #fff;
        border: 1px solid #e1e3e5;
        border-radius: 9px;
        overflow: hidden;
    }

    .card-heading {
        padding: 15px 17px;
        border-bottom: 1px solid #e1e3e5;
        font-size: 12px;
        font-weight: 800;
    }

    .card-body {
        padding: 5px 17px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 15px;
        padding: 14px 0;
        border-bottom: 1px solid #f0f1f2;
    }

    .detail-row:last-child {
        border-bottom: 0;
    }

    .detail-label {
        color: #6d7175;
        font-size: 11px;
    }

    .detail-value {
        max-width: 60%;
        text-align: right;
        font-size: 11px;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .status-badge {
        display: inline-block;
        padding: 5px 9px;
        border-radius: 20px;
        font-size: 9px;
        font-weight: 700;
    }

    .status-active {
        background: #e4f5ec;
        color: #008060;
    }

    .status-inactive {
        background: #f1f2f3;
        color: #606467;
    }

    .status-cancelled {
        background: #fcebea;
        color: #b42318;
    }

    .plan-name {
        font-size: 19px;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .plan-description {
        color: #6d7175;
        font-size: 11px;
    }

    .no-subscription {
        color: #6d7175;
        font-size: 12px;
        padding: 20px 0;
    }

    @media(max-width: 650px) {
        .details-grid {
            grid-template-columns: 1fr;
        }

        .details-header {
            align-items: flex-start;
        }
    }
</style>
@endpush

@section('content')

@php
    $subscription = $merchant->activeSubscription;
    $isUninstalled = $merchant->uninstalled_at !== null;
    $isActive = $merchant->is_active && !$isUninstalled;
@endphp

<div class="details-header">
    <div>
        <a href="{{ route('admin.merchants.index') }}" class="back-link">
            &larr; Back to Merchants
        </a>

        <div class="merchant-heading">
            <h1>{{ $merchant->shop }}</h1>
            <p>Merchant ID: #{{ $merchant->id }}</p>
        </div>
    </div>

    @if($isUninstalled)
        <span class="status-badge status-cancelled">Uninstalled</span>
    @elseif($isActive)
        <span class="status-badge status-active">Active Merchant</span>
    @else
        <span class="status-badge status-inactive">Inactive Merchant</span>
    @endif
</div>

<div class="details-grid">

    <div class="details-card">
        <div class="card-heading">Store Information</div>

        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Store Domain</span>
                <span class="detail-value">{{ $merchant->shop }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Merchant ID</span>
                <span class="detail-value">#{{ $merchant->id }}</span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Account Status</span>
                <span class="detail-value">
                    {{ $isActive ? 'Active' : ($isUninstalled ? 'Uninstalled' : 'Inactive') }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Plan Name</span>
                <span class="detail-value">
                    {{ $subscription?->plan?->name ?? 'No active plan' }}
                </span>
            </div>
        </div>
    </div>

    <div class="details-card">
        <div class="card-heading">Subscription Details</div>

        <div class="card-body">
            @if($subscription)
                <div style="padding: 17px 0;">
                    <div class="plan-name">
                        {{ $subscription->plan?->name ?? 'Unknown Plan' }}
                    </div>

                    <div class="plan-description">
                        {{ $subscription->plan?->description ?? 'No plan description available.' }}
                    </div>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Subscription Status</span>
                    <span class="detail-value">
                        <span class="status-badge status-active">
                            {{ ucfirst($subscription->status) }}
                        </span>
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Activated At</span>
                    <span class="detail-value">
                        {{ $subscription->activated_at?->format('d M Y, h:i A') ?? 'N/A' }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Billing Period Start</span>
                    <span class="detail-value">
                        {{ $subscription->current_period_start?->format('d M Y') ?? 'N/A' }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Billing Period End</span>
                    <span class="detail-value">
                        {{ $subscription->current_period_end?->format('d M Y') ?? 'N/A' }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Cancel At Period End</span>
                    <span class="detail-value">
                        {{ $subscription->cancel_at_period_end ? 'Yes' : 'No' }}
                    </span>
                </div>
            @else
                <div class="no-subscription">
                    No currently active subscription found.
                </div>
            @endif
        </div>
    </div>

    <div class="details-card">
        <div class="card-heading">Installation Details</div>

        <div class="card-body">
            <div class="detail-row">
                <span class="detail-label">Installed At</span>
                <span class="detail-value">
                    {{ $merchant->installed_at?->format('d M Y, h:i A') ?? 'N/A' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Uninstalled At</span>
                <span class="detail-value">
                    {{ $merchant->uninstalled_at?->format('d M Y, h:i A') ?? 'Not uninstalled' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Registered At</span>
                <span class="detail-value">
                    {{ $merchant->created_at?->format('d M Y, h:i A') ?? 'N/A' }}
                </span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Last Updated</span>
                <span class="detail-value">
                    {{ $merchant->updated_at?->format('d M Y, h:i A') ?? 'N/A' }}
                </span>
            </div>
        </div>
    </div>

    <div class="details-card">
        <div class="card-heading">Plan Information</div>

        <div class="card-body">
            @if($subscription?->plan)
                <div class="detail-row">
                    <span class="detail-label">Plan Name</span>
                    <span class="detail-value">{{ $subscription->plan->name }}</span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Billing Interval</span>
                    <span class="detail-value">
                        {{ ucfirst($subscription->plan->billing_interval) }}
                    </span>
                </div>

                <div class="detail-row">
                    <span class="detail-label">Plan Status</span>
                    <span class="detail-value">
                        {{ $subscription->plan->is_active ? 'Available' : 'Inactive' }}
                    </span>
                </div>
            @else
                <div class="no-subscription">
                    No plan information available.
                </div>
            @endif
        </div>
    </div>

</div>

@endsection
