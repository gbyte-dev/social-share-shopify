@extends('layouts.shopify')

@section('title', 'Subscription & Billing - ' . $shop)
@section('header_title', 'Subscription & Billing')
@section('header_subtitle', 'Manage your Socialvire plan, access period and billing status.')

@section('content')
@php
    $statusText = 'No subscription';
    $statusBg = '#f1f5f9';
    $statusBorder = '#e2e8f0';
    $statusColor = '#475569';
    $statusDot = '#000000';
    $statusNotice = 'Subscribe to Socialvire Pro to unlock all features.';

    if ($hasActiveSubscription) {
        $statusText = 'Active';
        $statusBg = '#ecfdf5';
        $statusBorder = '#a7f3d0';
        $statusColor = '#065f46';
        $statusDot = '#10b981';
        $expiryDateStr = $activeSubscription->current_period_end
            ? $activeSubscription->current_period_end->format('M d, Y')
            : 'the end of your period';

        $statusNotice = $activeSubscription->cancel_at_period_end
            ? "Your subscription will not renew. Access remains available until {$expiryDateStr}."
            : "Your subscription is active until {$expiryDateStr}.";
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

    $subscriptionDate = $activeSubscription?->activated_at
        ?? $latestSubscription?->activated_at;

    $subscriptionExpiry = $activeSubscription?->current_period_end
        ?? $latestSubscription?->current_period_end;
@endphp

<style>
    .sv-billing {
        --sv-ink: #172033;
        --sv-border: #e5eaf2;
        --sv-soft: #f7f9fc;
        --sv-primary: #5146e5;
        --sv-primary-dark: #4338ca;
        width: 100%;
        max-width: 1360px;
        margin: 0 auto;
        color: var(--sv-ink);
    }

    .sv-billing * { box-sizing: border-box; }

    .sv-billing-hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 24px;
        padding: 14px 18px;
        margin-bottom: 11px;
        border: 1px solid var(--sv-border);
        border-radius: 16px;
        background:
            radial-gradient(circle at 90% 0%, rgba(99,102,241,.10), transparent 28%),
            linear-gradient(135deg, #fff 0%, #f8faff 100%);
    }

    .sv-billing-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        color: var(--sv-primary);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .sv-billing-hero h2 {
        margin: 9px 0 6px;
        color: #111827;
        font-size: clamp(22px, 3vw, 29px);
        line-height: 1.2;
        font-weight: 800;
        letter-spacing: -.04em;
    }

    .sv-billing-hero p {
        margin: 0;
        font-size: 13px;
        line-height: 1.65;
    }

    .sv-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
        padding: 0px 4px;
        border: 1px solid {{ $statusBorder }};
        border-radius: 999px;
        background: {{ $statusBg }};
        color: {{ $statusColor }};
        font-size: 12px;
        font-weight: 750;
        white-space: nowrap;
    }

    .sv-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: {{ $statusDot }};
        box-shadow: 0 0 0 3px {{ $statusBg }};
    }

    .sv-status-notice {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 8px 11px;
        margin-bottom: 11px;
        border: 1px solid {{ $statusBorder }};
        border-radius: 11px;
        background: {{ $statusBg }};
        color: {{ $statusColor }};
        font-size: 12px;
        line-height: 1.6;
    }

    .sv-status-notice svg { flex: 0 0 auto; margin-top: 1px; }

    .sv-billing-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr);
        gap: 12px;
        align-items: start;
    }

    .sv-panel {
        min-width: 0;
        overflow: hidden;
        border: 1px solid var(--sv-border);
        border-radius: 15px;
        background: #fff;
        box-shadow: 0 3px 14px rgba(15, 23, 42, .035);
    }

    .sv-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 18px;
        padding: 13px 15px;
        border-bottom: 1px solid #edf0f5;
    }

    .sv-plan-kicker {
        margin-bottom: 7px;
        color: #000000;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
    }

    .sv-plan-title {
        margin: 0;
        color: #111827;
        font-size: 21px;
        line-height: 1.3;
        font-weight: 800;
        letter-spacing: -.035em;
    }

    .sv-plan-description {
        max-width: 620px;
        margin: 7px 0 0;
        color: var(-- );
        font-size: 12px;
        line-height: 1.65;
    }

    .sv-price {
        flex-shrink: 0;
        text-align: right;
    }

    .sv-price strong {
        display: block;
        color: #111827;
        font-size: 30px;
        line-height: 1.1;
        font-weight: 850;
        letter-spacing: -.045em;
    }

    .sv-price span {
        display: block;
        margin-top: 5px;
        color: var(-- );
        font-size: 11px;
    }

    .sv-plan-facts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0;
        border-bottom: 1px solid #edf0f5;
        background: #fbfcfe;
    }

    .sv-fact {
        min-width: 0;
        padding: 9px 13px;
        border-bottom: 1px solid #edf0f5;
    }

    .sv-fact:nth-child(odd) { border-right: 1px solid #edf0f5; }
    .sv-fact:nth-last-child(-n+2) { border-bottom: 0; }

    .sv-fact-label {
        display: block;
        margin-bottom: 6px;
        color: #000000;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .07em;
        text-transform: uppercase;
    }

    .sv-fact-value {
        display: block;
        overflow-wrap: anywhere;
        color: #1e293b;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.5;
    }

    .sv-panel-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 10px 13px;
    }

    .sv-action-note {
        max-width: 420px;
        margin: 0;
        color: var(-- );
        font-size: 11px;
        line-height: 1.6;
    }

    .sv-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 41px;
        padding: 0 16px;
        border: 1px solid transparent;
        border-radius: 9px;
        font-size: 12px;
        font-weight: 750;
        text-decoration: none !important;
        cursor: pointer;
        transition: background .18s ease, border-color .18s ease, transform .18s ease;
        white-space: nowrap;
    }

    .sv-btn:hover { transform: translateY(-1px); }

    .sv-btn-primary {
        background: var(--sv-primary);
        color: #fff !important;
        box-shadow: 0 5px 12px rgba(79,70,229,.18);
    }

    .sv-btn-primary:hover { background: var(--sv-primary-dark); }

    .sv-btn-danger {
        border-color: #fecaca;
        background: #fff;
        color: #b91c1c !important;
    }

    .sv-btn-danger:hover { background: #fef2f2; }

    .sv-btn-muted {
        border-color: var(--sv-border);
        background: #fff;
        color: #334155 !important;
    }

    .sv-side-head {
        padding: 13px 15px 9px;
        border-bottom: 1px solid #edf0f5;
    }

    .sv-side-head h3 {
        margin: 0;
        color: #172033;
        font-size: 15px;
        font-weight: 800;
        letter-spacing: -.02em;
    }

    .sv-side-head p {
        margin: 6px 0 0;
        color: var(-- );
        font-size: 11px;
        line-height: 1.6;
    }

    .sv-feature-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 12px;
        gap: 0;
        padding: 3px 15px 6px;
    }

    .sv-feature {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 7px 0;
        border-bottom: 1px solid #f1f3f7;
        color: #334155;
        font-size: 12px;
        line-height: 1.5;
    }

    .sv-feature:last-child { border-bottom: 0; }

    .sv-feature-check {
        display: grid;
        place-items: center;
        flex: 0 0 19px;
        width: 19px;
        height: 19px;
        border-radius: 6px;
        background: #ecfdf5;
        color: #059669;
    }

    .sv-plan-terms {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 9px;
        padding: 9px 11px;
        border-top: 1px solid #edf0f5;
        background: #fbfcfe;
    }

    .sv-term {
        text-align: center;
        padding: 8px 4px;
        border-radius: 8px;
        background: #fff;
        border: 1px solid #edf0f5;
    }

    .sv-term span {
        display: block;
        margin-bottom: 4px;
        color: #000000;
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .sv-term strong { color: #334155; font-size: 11px; }

    .sv-billing-footnote {
        display: flex;
        align-items: flex-start;
        gap: 9px;
        margin-top: 9px;
        padding: 8px 11px;
        border: 1px solid #e6eaf2;
        border-radius: 10px;
        background: #fff;
        color: #64748b;
        font-size: 10px;
        line-height: 1.65;
    }

    .sv-billing-footnote svg { flex: 0 0 auto; margin-top: 1px; color: #64748b; }

    .sv-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 99999;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(15, 23, 42, .58);
        backdrop-filter: blur(4px);
    }

    .sv-modal-backdrop.is-open { display: flex; }

    .sv-modal {
        width: 100%;
        max-width: 440px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.7);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15,23,42,.25);
    }

    .sv-modal-content { padding: 19px; }

    .sv-modal-icon {
        display: grid;
        place-items: center;
        width: 42px;
        height: 42px;
        margin-bottom: 15px;
        border-radius: 12px;
        background: #fef2f2;
        color: #dc2626;
    }

    .sv-modal h3 {
        margin: 0 0 8px;
        color: #111827;
        font-size: 18px;
        font-weight: 800;
    }

    .sv-modal p {
        margin: 0;
        color: #64748b;
        font-size: 12px;
        line-height: 1.7;
    }

    .sv-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 9px;
        padding: 10px 18px;
        border-top: 1px solid #edf0f5;
        background: #fbfcfe;
    }

    @media (max-width: 760px) {
        .sv-billing-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
        .sv-feature-list { grid-template-columns: 1fr; }
        .sv-billing-hero { flex-direction: column; padding: 20px; }
        .sv-panel-head { flex-direction: column; padding: 19px; }
        .sv-price { text-align: left; }
        .sv-price strong { font-size: 27px; }
        .sv-plan-facts { grid-template-columns: 1fr; }
        .sv-fact, .sv-fact:nth-child(odd) { border-right: 0; border-bottom: 1px solid #edf0f5; }
        .sv-fact:last-child { border-bottom: 0; }
        .sv-panel-actions { align-items: stretch; flex-direction: column; padding: 17px 19px; }
        .sv-panel-actions .sv-btn { width: 100%; }
        .sv-plan-terms { gap: 6px; padding: 12px; }
        .sv-term strong { font-size: 10px; }
        .sv-modal-actions { flex-direction: column-reverse; }
        .sv-modal-actions .sv-btn { width: 100%; }
    }
</style>

<div class="sv-billing">
    <!-- <section class="sv-billing-hero">
        <div>
            <span class="sv-billing-eyebrow">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6l8-4z"/><path d="m9 12 2 2 4-4"/></svg>
                Account billing
            </span>
            <h2>Your plan & subscription</h2>
            <p>Review your current access, subscription period and Socialvire Pro benefits.</p>
        </div>

        <span class="sv-status-pill">
            <span class="sv-status-dot"></span>
            {{ $statusText }}
        </span>
    </section> -->

    <div class="sv-status-notice" role="status">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        <span>{{ $statusNotice }}</span>
    </div>

    <div class="sv-billing-grid">
        <section class="sv-panel">
            <div class="sv-panel-head">
                <div>
                    <div class="sv-plan-kicker">Your subscription</div>
                    <h3 class="sv-plan-title">{{ $plan->name }} <span class="sv-status-pill">
            <span class="sv-status-dot"></span>
            {{ $statusText }}
        </span> </h3>
                    <p class="sv-plan-description">{{ $plan->description }}</p>
                </div>

                <div class="sv-price">
                    <strong>{{ $plan->formattedPrice() }}</strong>
                    <span>per 30-day period</span>
                </div>
            </div>

            <div class="sv-plan-facts">
                <div class="sv-fact">
                    <span class="sv-fact-label">Connected store</span>
                    <span class="sv-fact-value" title="{{ $shop }}">{{ $shop }}</span>
                </div>

                <div class="sv-fact">
                    <span class="sv-fact-label">Subscription status</span>
                    <span class="sv-fact-value">{{ $statusText }}</span>
                </div>

                <div class="sv-fact">
                    <span class="sv-fact-label">Activation date</span>
                    <span class="sv-fact-value">{{ $subscriptionDate?->format('M d, Y') ?? '—' }}</span>
                </div>

                <div class="sv-fact">
                    <span class="sv-fact-label">Expiry date</span>
                    <span class="sv-fact-value">{{ $subscriptionExpiry?->format('M d, Y') ?? '—' }}</span>
                </div>

                <div class="sv-fact">
                    <span class="sv-fact-label">Remaining access</span>
                    <span class="sv-fact-value">
                        @if($hasActiveSubscription)
                            {{ $activeSubscription->daysRemaining() }} days left
                        @elseif($latestSubscription && $latestSubscription->status === 'cancelled')
                            Cancelled
                        @elseif($latestSubscription && $latestSubscription->isExpired())
                            Expired
                        @else
                            Not active
                        @endif
                    </span>
                </div>

                <div class="sv-fact">
                    <span class="sv-fact-label">Renewal method</span>
                    <span class="sv-fact-value">Manual renewal</span>
                </div>
            </div>

            <div class="sv-panel-actions">
                <p class="sv-action-note">
                    @if($hasActiveSubscription)
                        Your subscription is active and all plan features are available.
                    @elseif($subscriptionStatus === 'cancelled')
                        Your subscription is cancelled. You can subscribe again whenever you need access.
                    @elseif($subscriptionStatus === 'expired')
                        Your subscription has expired. Renew your plan to restore access.
                    @else
                        An active subscription is required to access Socialvire Pro features.
                    @endif
                </p>

                <div>
                    @if($hasActiveSubscription)
                        <button type="button" onclick="openCancelModal()" class="sv-btn sv-btn-danger">
                            Cancel subscription
                        </button>
                    @elseif($subscriptionStatus === 'cancelled' || $subscriptionStatus === 'expired')
                        <a href="{{ route('shopify.plans.checkout', ['plan' => $plan->id, 'shop' => $shop, 'host' => $host]) }}" class="sv-btn sv-btn-primary">
                            Subscribe again
                        </a>
                    @else
                        <a href="{{ route('shopify.plans.checkout', ['plan' => $plan->id, 'shop' => $shop, 'host' => $host]) }}" class="sv-btn sv-btn-primary">
                            Subscribe now
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <aside class="sv-panel">
            <div class="sv-side-head">
                <h3>Included with {{ $plan->name }}</h3>
                <p>Everything you need to connect your storefront with your customers' social platforms.</p>
            </div>

            <div class="sv-feature-list">
                <div class="sv-feature"><span class="sv-feature-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span><span>Multi-platform floating social dock</span></div>
                <div class="sv-feature"><span class="sv-feature-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span><span>Product share button with custom color picker</span></div>
                <div class="sv-feature"><span class="sv-feature-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span><span>Real-time conversion tracking and click analytics</span></div>
                <div class="sv-feature"><span class="sv-feature-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span><span>Custom positioning and responsive layout controls</span></div>
                <div class="sv-feature"><span class="sv-feature-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span><span>Full access for 30 days</span></div>
                <div class="sv-feature"><span class="sv-feature-check"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span><span>No automatic renewal; renew manually</span></div>
            </div>

            <div class="sv-plan-terms">
                <div class="sv-term"><span>Duration</span><strong>30 days</strong></div>
                <div class="sv-term"><span>Billing</span><strong>{{ $plan->formattedPrice() }}</strong></div>
                <div class="sv-term"><span>Renewal</span><strong>Manual</strong></div>
            </div>
        </aside>
    </div>

    <div class="sv-billing-footnote">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        <span>
            Billing information shown here reflects the current Socialvire plan configuration.
            The plan runs for 30 days and requires manual renewal. No automatic renewal is initiated from this page.
        </span>
    </div>
</div>

<div id="cancelModal" class="sv-modal-backdrop" aria-hidden="true">
    <div class="sv-modal" role="dialog" aria-modal="true" aria-labelledby="cancelModalTitle">
        <div class="sv-modal-content">
            <div class="sv-modal-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.2 18a2 2 0 0 0 1.7 3h16.2a2 2 0 0 0 1.7-3l-8.1-14.1a2 2 0 0 0-3.4 0z"/></svg>
            </div>
            <h3 id="cancelModalTitle">Cancel your subscription?</h3>
            <p>
                Are you sure you want to cancel your subscription? Cancelling will end your subscription
                and update your store access according to the current cancellation process.
                You can subscribe again at any time.
            </p>
        </div>

        <div class="sv-modal-actions">
            <button type="button" onclick="closeCancelModal()" class="sv-btn sv-btn-muted">Keep subscription</button>

            <form method="POST" action="{{ route('shopify.plans.cancel') }}" style="margin:0;">
                @csrf
                <input type="hidden" name="shop" value="{{ $shop }}">
                <input type="hidden" name="host" value="{{ $host }}">
                <button type="submit" class="sv-btn sv-btn-danger">Confirm cancellation</button>
            </form>
        </div>
    </div>
</div>

<script>
    function openCancelModal() {
        const modal = document.getElementById('cancelModal');
        if (!modal) return;

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeCancelModal() {
        const modal = document.getElementById('cancelModal');
        if (!modal) return;

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') closeCancelModal();
    });

    document.getElementById('cancelModal')?.addEventListener('click', function (event) {
        if (event.target === this) closeCancelModal();
    });

    @if(!$hasActiveSubscription)
    document.addEventListener('DOMContentLoaded', function () {
        const protectedLinks = document.querySelectorAll(
            '.sidebar-nav a:not([href*="plans"]):not([href*="logout"])'
        );

        protectedLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.showToast) {
                    window.showToast('Paid plan is required to use features on this app', 'warning', 3000);
                }
            });
        });
    });
    @endif
</script>
@endsection
