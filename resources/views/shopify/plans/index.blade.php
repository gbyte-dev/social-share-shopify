@extends('layouts.shopify')

@section('title', 'Subscription & Billing - ' . $shop)
@section('header_title', 'Subscription & Billing')
@section('header_subtitle', 'Manage your SocialNest plan and subscription.')

@section('content')
@php
    $statusText = 'No subscription';
    $statusBg = '#f1f5f9';
    $statusBorder = '#e2e8f0';
    $statusColor = '#475569';
    $statusDot = '#94a3b8';
    $statusNotice = 'Subscribe to SocialNest Pro to unlock all features.';

    if ($hasActiveSubscription) {
        $statusText = 'Active';
        $statusBg = '#ecfdf5';
        $statusBorder = '#a7f3d0';
        $statusColor = '#065f46';
        $statusDot = '#10b981';
        $expiryDateStr = $activeSubscription->current_period_end ? $activeSubscription->current_period_end->format('M d, Y') : 'the end of your period';
        if ($activeSubscription->cancel_at_period_end) {
            $statusNotice = "Your subscription will not renew. Access remains available until {$expiryDateStr}.";
        } else {
            $statusNotice = "Your subscription is active until {$expiryDateStr}.";
        }
    } elseif ($latestSubscription && $latestSubscription->status === 'cancelled') {
        $statusText = 'Cancelled';
        $statusBg = '#fef2f2';
        $statusBorder = '#fecaca';
        $statusColor = '#991b1b';
        $statusDot = '#ef4444';
        if ($latestSubscription->current_period_end && $latestSubscription->current_period_end->isFuture()) {
            $expiryDateStr = $latestSubscription->current_period_end->format('M d, Y');
            $statusNotice = "Your subscription will not renew. Access remains available until {$expiryDateStr}.";
        } else {
            $statusNotice = 'Your subscription has been cancelled. Subscribe again to restore access.';
        }
    } elseif ($subscriptionStatus === 'expired' || ($latestSubscription && $latestSubscription->isExpired())) {
        $statusText = 'Expired';
        $statusBg = '#fffbeb';
        $statusBorder = '#fde68a';
        $statusColor = '#92400e';
        $statusDot = '#f59e0b';
        $statusNotice = 'Your subscription has expired. Subscribe again to restore access.';
    }
@endphp

<style>
    .billing-full-container {
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        box-sizing: border-box;
    }

    .billing-grid {
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: 24px;
        align-items: start;
        width: 100%;
    }

    .billing-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        overflow: hidden;
        box-sizing: border-box;
    }

    .features-grid-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem 1rem;
    }

    @media (max-width: 1024px) {
        .billing-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    @media (max-width: 640px) {
        .features-grid-list {
            grid-template-columns: 1fr;
            gap: 0.65rem;
        }

        .billing-card-header {
            flex-direction: column;
            align-items: flex-start !important;
        }

        .billing-price-lockup {
            text-align: left !important;
            margin-top: 0.5rem;
        }

        .billing-action-bar {
            flex-direction: column;
            align-items: stretch !important;
            gap: 0.75rem;
        }

        .billing-action-btn {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="billing-full-container">

    <!-- Subtle, Full-Width Contextual Status Row -->
    <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.65rem 1.25rem; margin-bottom: 1.25rem; font-size: 0.8125rem;">
        <div style="display: flex; align-items: center; gap: 0.5rem; color: #334155; min-width: 0;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $statusNotice }}</span>
        </div>
        <span style="display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.75rem; font-weight: 600; background: {{ $statusBg }}; color: {{ $statusColor }}; border: 1px solid {{ $statusBorder }}; padding: 0.15rem 0.55rem; border-radius: 6px; white-space: nowrap; flex-shrink: 0;">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: {{ $statusDot }};"></span>
            {{ $statusText }}
        </span>
    </div>


    <!-- Two-Column Desktop Layout (Left ~58%, Right ~42%) -->
    <div class="billing-grid">
        
        <!-- LEFT COLUMN: Main SocialNest Pro Subscription Card -->
        <div class="billing-card">
            
            <!-- Card Header: Plan Name, Price, and Status -->
            <div class="billing-card-header" style="display: flex; align-items: flex-start; justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9; gap: 1rem;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <h3 style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0; letter-spacing: -0.01em;">
                            {{ $plan->name }}
                        </h3>
                        <span style="font-size: 0.75rem; font-weight: 600; background: {{ $statusBg }}; color: {{ $statusColor }}; border: 1px solid {{ $statusBorder }}; padding: 0.125rem 0.5rem; border-radius: 4px;">
                            {{ $statusText }}
                        </span>
                    </div>
                    <p style="font-size: 0.8125rem; color: #64748b; margin: 0.25rem 0 0 0; line-height: 1.4;">
                        {{ $plan->description }}
                    </p>
                </div>

                <div class="billing-price-lockup" style="text-align: right; flex-shrink: 0;">
                    <div style="display: flex; align-items: baseline; justify-content: flex-end; gap: 0.25rem;">
                        <span style="font-size: 1.65rem; font-weight: 750; color: #0f172a; line-height: 1;">{{ $plan->formattedPrice() }}</span>
                        <span style="font-size: 0.8125rem; color: #64748b; font-weight: 500;">/ 30 days</span>
                    </div>
                    <div style="font-size: 0.6875rem; color: #94a3b8; margin-top: 0.25rem;">Manual renewal &bull; Single tier</div>
                </div>
            </div>

            <!-- Horizontal Information Strip -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.75rem; padding: 1rem 1.5rem; background: #f8fafc; border-bottom: 1px solid #f1f5f9; font-size: 0.8125rem;">
                <div>
                    <div style="font-size: 0.6875rem; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.03em;">Connected Store</div>
                    <div style="font-weight: 600; color: #0f172a; margin-top: 0.2rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $shop }}">{{ $shop }}</div>
                </div>

                <div>
                    <div style="font-size: 0.6875rem; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.03em;">Activation Date</div>
                    <div style="font-weight: 600; color: #0f172a; margin-top: 0.2rem;">
                        {{ $activeSubscription?->activated_at?->format('M d, Y') ?? ($latestSubscription?->activated_at?->format('M d, Y') ?? '—') }}
                    </div>
                </div>

                <div>
                    <div style="font-size: 0.6875rem; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.03em;">Expiry Date</div>
                    <div style="font-weight: 600; color: #0f172a; margin-top: 0.2rem;">
                        {{ $activeSubscription?->current_period_end?->format('M d, Y') ?? ($latestSubscription?->current_period_end?->format('M d, Y') ?? '—') }}
                    </div>
                </div>

                <div>
                    <div style="font-size: 0.6875rem; color: #64748b; text-transform: uppercase; font-weight: 600; letter-spacing: 0.03em;">Remaining Days</div>
                    <div style="font-weight: 600; color: {{ $hasActiveSubscription ? '#0f172a' : '#64748b' }}; margin-top: 0.2rem;">
                        @if($hasActiveSubscription)
                            {{ $activeSubscription->daysRemaining() }} days left
                        @elseif($latestSubscription && $latestSubscription->status === 'cancelled')
                            Cancelled
                        @elseif($latestSubscription && $latestSubscription->isExpired())
                            Expired
                        @else
                            None
                        @endif
                    </div>
                </div>
            </div>

            <!-- Action Bar -->
            <div class="billing-action-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.5rem; flex-wrap: wrap; gap: 0.75rem;">
                <div style="font-size: 0.8125rem; color: #64748b;">
                    @if($hasActiveSubscription)
                        Subscription is active. Access is enabled for all features.
                    @elseif($subscriptionStatus === 'cancelled')
                        Subscription is cancelled. Re-subscribe at any time.
                    @elseif($subscriptionStatus === 'expired')
                        Subscription expired. Renew to continue using SocialNest.
                    @else
                        Active subscription required to use SocialNest.
                    @endif
                </div>

                <div>
                    @if($hasActiveSubscription)
                        <button type="button" onclick="openCancelModal()" class="billing-action-btn" style="background: #ffffff; border: 1px solid #e2e8f0; color: #dc2626; font-size: 0.8125rem; font-weight: 500; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; transition: all 0.15s ease;">
                            Cancel Subscription
                        </button>
                    @elseif($subscriptionStatus === 'cancelled' || $subscriptionStatus === 'expired')
                        <a href="{{ route('shopify.plans.checkout', ['plan' => $plan->id, 'shop' => $shop, 'host' => $host]) }}" class="billing-action-btn" style="display: inline-block; background: #0f172a; color: #ffffff; text-decoration: none; font-size: 0.8125rem; font-weight: 600; padding: 0.5rem 1.15rem; border-radius: 6px; transition: background 0.15s ease;">
                            Subscribe Again ($5.00 / 30 Days)
                        </a>
                    @else
                        <a href="{{ route('shopify.plans.checkout', ['plan' => $plan->id, 'shop' => $shop, 'host' => $host]) }}" class="billing-action-btn" style="display: inline-block; background: #0f172a; color: #ffffff; text-decoration: none; font-size: 0.8125rem; font-weight: 600; padding: 0.5rem 1.15rem; border-radius: 6px; transition: background 0.15s ease;">
                            Subscribe Now ($5.00 / 30 Days)
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: What's Included in SocialNest Pro Card -->
        <div class="billing-card" style="padding: 1.25rem 1.5rem;">
            <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 0.85rem; margin-bottom: 1rem;">
                <h4 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin: 0 0 0.2rem 0;">
                    What's included in SocialNest Pro
                </h4>
                <p style="font-size: 0.8125rem; color: #64748b; margin: 0; line-height: 1.35;">
                    All-in-one storefront conversion suite &bull; Active for 30 days
                </p>
            </div>

            <!-- Features 2-Column Grid List Inside Card -->
            <div class="features-grid-list">
                <div style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.8125rem; color: #334155;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Multi-platform floating social dock</span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.8125rem; color: #334155;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Product share button with custom color picker</span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.8125rem; color: #334155;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Real-time conversion tracking &amp; click analytics</span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.8125rem; color: #334155;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Custom positioning &amp; responsive layout controls</span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.8125rem; color: #334155;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Full access for exactly 30 days</span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.8125rem; color: #334155;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5" style="flex-shrink: 0; margin-top: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Zero automatic renewal surprises</span>
                </div>
            </div>

            <!-- Subtle Plan Entitlement Note Inside Card -->
            <div style="margin-top: 1.15rem; padding-top: 0.85rem; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; color: #64748b;">
                <span>Duration: <strong>30 Days</strong></span>
                <span>Type: <strong>Dummy Billing</strong></span>
                <span>Renewal: <strong>Manual</strong></span>
            </div>
        </div>

    </div>

    <!-- Subtle, Minimal System & Billing Note (Full Width) -->
    <div style="padding: 1.25rem 0.25rem 0.5rem 0.25rem; font-size: 0.6875rem; color: #94a3b8; line-height: 1.4; text-align: center;">
        Shopify Billing API Status: SocialNest operates under a dummy 30-day billing cycle. Active subscriptions use the Pro Plan ($5.00 / 30 days). No Free Plan is currently offered.
    </div>

</div>

<!-- Cancellation Confirmation Modal -->
<div id="cancelModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #ffffff; border-radius: 8px; width: 100%; max-width: 420px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1); overflow: hidden; border: 1px solid #e2e8f0;">
        <div style="padding: 1.25rem 1.25rem 0.75rem 1.25rem;">
            <h3 style="font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0 0 0.5rem 0;">Cancel Subscription Immediately?</h3>
            <p style="font-size: 0.8125rem; color: #475569; line-height: 1.45; margin: 0;">
                Are you sure you want to cancel your subscription? Cancelling will end your subscription and update your store status. You can subscribe again at any time.
            </p>
        </div>

        <div style="padding: 0.75rem 1.25rem; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: flex-end; gap: 0.5rem;">
            <button type="button" onclick="closeCancelModal()" style="background: #ffffff; border: 1px solid #e2e8f0; color: #475569; font-size: 0.8125rem; font-weight: 500; padding: 0.4rem 0.75rem; border-radius: 6px; cursor: pointer;">
                Keep Subscription
            </button>
            <form method="POST" action="{{ route('shopify.plans.cancel') }}" style="margin: 0;">
                @csrf
                <input type="hidden" name="shop" value="{{ $shop }}">
                <input type="hidden" name="host" value="{{ $host }}">
                <button type="submit" style="background: #dc2626; border: 1px solid #dc2626; color: #ffffff; font-size: 0.8125rem; font-weight: 600; padding: 0.4rem 0.875rem; border-radius: 6px; cursor: pointer;">
                    Cancel &amp; Block Access Instantly
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    function openCancelModal() {
        var modal = document.getElementById('cancelModal');
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeCancelModal() {
        var modal = document.getElementById('cancelModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    window.addEventListener('click', function(e) {
        var modal = document.getElementById('cancelModal');
        if (e.target === modal) {
            closeCancelModal();
        }
    });

    @if(!$hasActiveSubscription)
    document.addEventListener('DOMContentLoaded', function() {
        var protectedLinks = document.querySelectorAll('.sidebar-nav a:not([href*="plans"]):not([href*="logout"])');
        protectedLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                if (window.showToast) {
                    window.showToast('Paid plan is required to use features on this app', 'warning', 3000);
                }
            });
        });
    });
    @endif
</script>
@endsection
