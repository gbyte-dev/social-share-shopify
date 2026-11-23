@extends('layouts.shopify')

@section('title', 'Choose Your Plan - ' . $shop)
@section('header_title', 'Choose Your Plan')
@section('header_subtitle', 'Get full access to everything Socialvire offers.')
@section('hide_top_navbar', true)

@section('content')
@php
    $hasVerifiedSubscription = !empty($hasActiveSubscription) && $activeSubscription !== null;

    if ($hasVerifiedSubscription) {
        $statusKey = 'active';
        $statusLabel = 'Active Subscription';
        $statusBg = '#ecfdf5';
        $statusBorder = '#a7f3d0';
        $statusColor = '#065f46';
        $statusDot = '#10b981';
        $statusNotice = 'Your Socialvire subscription is active with full features unlocked.';
    } elseif ($subscriptionStatus === 'cancelled' || ($latestSubscription && $latestSubscription->status === 'cancelled')) {
        $statusKey = 'cancelled';
        $statusLabel = 'Subscription Cancelled';
        $statusBg = '#fffbeb';
        $statusBorder = '#fde68a';
        $statusColor = '#92400e';
        $statusDot = '#f59e0b';
        $statusNotice = 'Your previous subscription was cancelled. Claim your plan below to continue with full access.';
    } elseif ($subscriptionStatus === 'expired' || ($latestSubscription && $latestSubscription->isExpired())) {
        $statusKey = 'expired';
        $statusLabel = 'Subscription Expired';
        $statusBg = '#fef2f2';
        $statusBorder = '#fecaca';
        $statusColor = '#991b1b';
        $statusDot = '#ef4444';
        $statusNotice = 'Your previous subscription period ended. Claim your plan below to restore full access.';
    } else {
        $statusKey = 'none';
        $statusLabel = 'No Active Plan';
        $statusBg = '#f1f5f9';
        $statusBorder = '#cbd5e1';
        $statusColor = '#475569';
        $statusDot = '#94a3b8';
        $statusNotice = 'No active subscription found. Claim your plan below to get full access to all features.';
    }

    $activatedDateStr = $activeSubscription?->activated_at?->format('M d, Y') ?? $latestSubscription?->activated_at?->format('M d, Y');
@endphp

<style>
    /* Full-width Responsive Container */
    .sn-plans-page {
        --sn-ink: #0f172a;
        --sn-subtle: #334155;
        --sn-muted: #64748b;
        --sn-border: #e2e8f0;
        --sn-border-focus: #c7d2fe;
        --sn-primary: #4f46e5;
        --sn-primary-hover: #4338ca;
        --sn-primary-light: #eef2ff;
        --sn-success: #10b981;
        --sn-success-light: #ecfdf5;
        --sn-success-dark: #059669;
        --sn-card-bg: #ffffff;
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0.25rem 0 2.5rem;
        color: var(--sn-ink);
        box-sizing: border-box;
    }

    .sn-plans-page * {
        box-sizing: border-box;
    }

    .sn-sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* Full-width Structured Page Header */
    .sn-plans-hero {
        position: relative;
        width: 100%;
        padding: 0.75rem 1rem;
        margin-bottom: 1.5rem;
        background: linear-gradient(135deg, #ffffff 0%, #f8faff 50%, #eef2ff 100%);
        border: 1px solid var(--sn-border);
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    }

    .sn-hero-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 0.875rem;
    }

    .sn-header-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 9999px;
        background: var(--sn-primary-light);
        color: var(--sn-primary);
        font-size: 0.75rem;
        font-weight: 750;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }

    .sn-header-eyebrow svg {
        flex-shrink: 0;
    }

    .sn-header-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 4px 14px;
        background: var(--sn-success-light);
        border: 1px solid #a7f3d0;
        border-radius: 9999px;
        color: #065f46;
        font-size: 0.8125rem;
        font-weight: 650;
    }

    .sn-badge-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--sn-success);
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
    }

    /*.sn-hero-heading {*/
    /*    margin: 0 0 0.375rem;*/
    /*    color: var(--sn-ink);*/
    /*    font-size: clamp(1.75rem, 3.2vw, 2.25rem);*/
    /*    line-height: 1.2;*/
    /*    font-weight: 800;*/
    /*    letter-spacing: -0.03em;*/
    /*}*/

    /*.sn-hero-subtitle {*/
    /*    margin: 0;*/
    /*    color: var(--sn-subtle);*/
    /*    font-size: 1rem;*/
    /*    line-height: 1.5;*/
    /*    max-width: 680px;*/
    /*}*/

    /* Embedded Current Subscription Bar */
    .sn-status-strip {
        margin-top: 1.25rem;
        padding-top: 1.15rem;
        border-top: 1px solid rgba(226, 232, 240, 0.85);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
    }

    .sn-status-strip-left {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .sn-status-kicker {
        font-size: 0.6875rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--sn-muted);
    }

    .sn-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 750;
        white-space: nowrap;
        border: 1px solid;
    }

    .sn-status-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }

    .sn-status-plan,
    .sn-status-shop,
    .sn-status-date {
        font-size: 0.8125rem;
        color: var(--sn-muted);
    }

    .sn-status-plan strong,
    .sn-status-shop strong,
    .sn-status-date strong {
        color: var(--sn-ink);
    }

    .sn-btn-cancel-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border: 1px solid #fecaca;
        border-radius: 7px;
        background: #fff;
        color: #b91c1c;
        font-size: 0.75rem;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.15s ease, border-color 0.15s ease;
        text-decoration: none;
    }

    .sn-btn-cancel-link:hover {
        background: #fef2f2;
        border-color: #fca5a5;
    }

    /* Two-Column Desktop Grid */
    .sn-plans-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(320px, 0.95fr);
        gap: 20px;
        align-items: start;
        margin-bottom: 2rem;
    }

    /* LEFT COLUMN: Main Socialvire Free Pricing Card */
    .sn-card-main {
        position: relative;
        background: var(--sn-card-bg);
        border: 2px solid var(--sn-border-focus);
        border-radius: 18px;
        box-shadow: 0 10px 30px -6px rgba(79, 70, 229, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }

    .sn-card-badge-top {
        position: absolute;
        top: 0;
        right: 0;
        background: linear-gradient(135deg, var(--sn-primary) 0%, #6366f1 100%);
        color: #ffffff;
        font-size: 0.6875rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 6px 18px;
        border-bottom-left-radius: 12px;
    }

    .sn-card-header {
        padding: 2.25rem 2.25rem 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        flex-wrap: wrap;
    }

    .sn-plan-meta {
        min-width: 0;
        flex: 1 1 280px;
    }

    .sn-plan-title-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 0.35rem;
    }

    .sn-plan-name {
        margin: 0;
        font-weight: 800;
        color: var(--sn-ink);
        line-height: 1.2;
    }

    .sn-pill-free-forever {
        display: inline-flex;
        align-items: center;
        padding: 2px 10px;
        background: var(--sn-success-light);
        border: 1px solid #a7f3d0;
        color: var(--sn-success-dark);
        font-size: 0.6875rem;
        font-weight: 750;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .sn-plan-desc {
        margin: 0.5rem 0 0;
        font-size: 0.9375rem;
        color: var(--sn-subtle);
        line-height: 1.55;
        max-width: 520px;
    }

    .sn-plan-pricing-box {
        text-align: right;
        flex-shrink: 0;
    }

    .sn-price-row {
        display: flex;
        align-items: baseline;
        justify-content: flex-end;
        gap: 3px;
    }

    .sn-currency {
        font-size: 1.625rem;
        font-weight: 750;
        color: var(--sn-ink);
    }

    .sn-amount {
        font-size: 2.5rem;
        font-weight: 850;
        color: var(--sn-ink);
        line-height: 1;
        letter-spacing: -0.05em;
    }

    .sn-interval {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--sn-muted);
        margin-left: 4px;
    }

    .sn-price-subtext {
        display: block;
        margin-top: 6px;
        font-size: 0.75rem;
        font-weight: 650;
        color: var(--sn-success-dark);
    }

    .sn-divider {
        height: 1px;
        background: #f1f5f9;
        margin: 0 2.25rem;
    }

    /* Core Features Checklist */
    .sn-features-section {
        padding: 1.75rem 2.25rem;
    }

    .sn-features-heading {
        margin: 0 0 1.25rem;
        font-size: 0.8125rem;
        font-weight: 750;
        letter-spacing: 0.07em;
        text-transform: uppercase;
        color: var(--sn-ink);
    }

    .sn-features-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem 1.75rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sn-feature-item {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        min-width: 0;
    }

    .sn-feature-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        border-radius: 6px;
        background: var(--sn-success-light);
        color: var(--sn-success-dark);
        flex-shrink: 0;
        margin-top: 1px;
    }

    .sn-feature-text {
        font-size: 0.875rem;
        color: #1e293b;
        line-height: 1.45;
    }

    .sn-feature-text strong {
        font-weight: 650;
    }

    /* CTA Banner in Main Card */
    .sn-card-cta-footer {
        padding: 1.75rem 2.25rem 2.25rem;
        background: #fafbfc;
        border-top: 1px solid #f1f5f9;
        text-align: center;
    }

    .sn-btn-primary-prominent {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        max-width: 420px;
        padding: 0.95rem 2rem;
        background: linear-gradient(135deg, var(--sn-primary) 0%, #4338ca 100%);
        color: #ffffff !important;
        border: none;
        border-radius: 10px;
        font-size: 1.0625rem;
        font-weight: 750;
        cursor: pointer;
        box-shadow: 0 4px 16px rgba(79, 70, 229, 0.32);
        transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        text-decoration: none !important;
    }

    .sn-btn-primary-prominent:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 22px rgba(79, 70, 229, 0.42);
        background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
    }

    .sn-btn-primary-prominent:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(79, 70, 229, 0.28);
    }

    .sn-btn-primary-prominent svg {
        transition: transform 0.18s ease;
    }

    .sn-btn-primary-prominent:hover svg {
        transform: translateX(3px);
    }

    .sn-cta-subtext {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        margin: 0.875rem 0 0;
        font-size: 0.75rem;
        color: var(--sn-muted);
        line-height: 1.5;
    }

    .sn-cta-subtext svg {
        flex-shrink: 0;
        color: var(--sn-success-dark);
    }

    /* RIGHT COLUMN: Separate Information Panel */
    .sn-card-side {
        background: var(--sn-card-bg);
        border: 1px solid var(--sn-border);
        border-radius: 18px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.04);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .sn-side-header {
        padding: 1.75rem 1.75rem 1.25rem;
        border-bottom: 1px solid #edf0f5;
        background: #fbfcfe;
    }

    .sn-side-header h3 {
        margin: 0 0 0.375rem;
        color: var(--sn-ink);
        font-size: 1.125rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .sn-side-header p {
        margin: 0;
        color: var(--sn-muted);
        font-size: 0.8125rem;
        line-height: 1.5;
    }

    .sn-side-breakdown {
        padding: 1.25rem 1.75rem;
        display: flex;
        flex-direction: column;
        gap: 14px;
        flex: 1;
    }

    .sn-breakdown-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .sn-breakdown-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    .sn-breakdown-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: var(--sn-primary-light);
        color: var(--sn-primary);
        flex-shrink: 0;
    }

    .sn-breakdown-details h4 {
        margin: 0 0 2px;
        font-size: 0.875rem;
        font-weight: 750;
        color: var(--sn-ink);
    }

    .sn-breakdown-details p {
        margin: 0;
        font-size: 0.75rem;
        color: var(--sn-muted);
        line-height: 1.45;
    }

    .sn-side-guarantee-box {
        margin: 0 1.75rem 1.5rem;
        padding: 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }

    .sn-side-guarantee-box h4 {
        margin: 0 0 4px;
        font-size: 0.8125rem;
        font-weight: 750;
        color: var(--sn-ink);
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .sn-side-guarantee-box h4 svg {
        color: var(--sn-success-dark);
        flex-shrink: 0;
    }

    .sn-side-guarantee-box p {
        margin: 0;
        font-size: 0.75rem;
        color: var(--sn-subtle);
        line-height: 1.5;
    }

    .sn-plan-terms-bar {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 8px;
        padding: 10px 1.75rem;
        border-top: 1px solid #edf0f5;
        background: #fbfcfe;
    }

    .sn-term-item {
        text-align: center;
        padding: 6px 4px;
        border-radius: 8px;
        background: #ffffff;
        border: 1px solid #edf0f5;
    }

    .sn-term-label {
        display: block;
        font-size: 0.625rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--sn-muted);
        margin-bottom: 2px;
    }

    .sn-term-val {
        font-size: 0.75rem;
        font-weight: 750;
        color: var(--sn-ink);
    }

    /* Additional Information Section (3 Compact Cards) */
    .sn-info-cards-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
        margin-top: 0.5rem;
    }

    .sn-info-card {
        background: var(--sn-card-bg);
        border: 1px solid var(--sn-border);
        border-radius: 14px;
        padding: 1.5rem 1.35rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .sn-info-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }

    .sn-info-card-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: var(--sn-primary-light);
        color: var(--sn-primary);
        margin-bottom: 1rem;
    }

    .sn-info-card h4 {
        margin: 0 0 0.5rem;
        font-size: 0.9375rem;
        font-weight: 750;
        color: var(--sn-ink);
    }

    .sn-info-card p {
        margin: 0;
        font-size: 0.8125rem;
        color: var(--sn-muted);
        line-height: 1.55;
    }

    /* Modal Backdrop & Modal Styles (Preserved for Cancellation) */
    .sv-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 99999;
        align-items: center;
        justify-content: center;
        padding: 18px;
        background: rgba(15, 23, 42, 0.58);
        backdrop-filter: blur(4px);
    }

    .sv-modal-backdrop.is-open {
        display: flex;
    }

    .sv-modal {
        width: 100%;
        max-width: 440px;
        overflow: hidden;
        border: 1px solid rgba(255, 255, 255, 0.7);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.25);
    }

    .sv-modal-content {
        padding: 19px;
    }

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

    .sv-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 38px;
        padding: 0 16px;
        border: 1px solid transparent;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 750;
        cursor: pointer;
        transition: background 0.18s ease;
        text-decoration: none !important;
        white-space: nowrap;
    }

    .sv-btn-danger {
        border-color: #fecaca;
        background: #fff;
        color: #b91c1c !important;
    }

    .sv-btn-danger:hover {
        background: #fef2f2;
    }

    .sv-btn-muted {
        border-color: var(--sn-border);
        background: #fff;
        color: #334155 !important;
    }

    .sv-btn-muted:hover {
        background: #f8fafc;
    }

    /* Responsive Adjustments */
    @media (max-width: 1024px) {
        .sn-plans-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .sn-info-cards-grid {
            grid-template-columns: 1fr;
            gap: 14px;
        }
    }

    @media (max-width: 768px) {
        .sn-plans-hero {
            padding: 1.5rem 1.25rem;
        }

        .sn-card-header {
            flex-direction: column;
            padding: 1.75rem 1.5rem 1.25rem;
        }

        .sn-plan-pricing-box {
            text-align: left;
        }

        .sn-price-row {
            justify-content: flex-start;
        }

        .sn-features-section {
            padding: 1.5rem;
        }

        .sn-features-grid {
            grid-template-columns: 1fr;
            gap: 0.875rem;
        }

        .sn-card-cta-footer {
            padding: 1.5rem;
        }

        .sn-btn-primary-prominent {
            max-width: 100%;
        }

        .sn-side-header,
        .sn-side-breakdown {
            padding-left: 1.25rem;
            padding-right: 1.25rem;
        }

        .sn-side-guarantee-box,
        .sn-plan-terms-bar {
            margin-left: 1.25rem;
            margin-right: 1.25rem;
        }

        .sn-status-strip {
            flex-direction: column;
            align-items: flex-start;
        }

        .sn-divider {
            margin: 0 1.5rem;
        }
    }

    @media (max-width: 480px) {
        .sn-amount {
            font-size: 2.85rem;
        }

        .sn-plan-title-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
    }
</style>

<div class="sn-plans-page">
    {{-- Full-width Page Header --}}
    <header class="sn-plans-hero">
        <div class="sn-hero-top-row"  style="display:none">
            <div class="sn-header-eyebrow">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <span>Subscription &amp; Billing</span>
            </div>

                    <div class="sn-header-badge">
            <span class="sn-badge-dot"></span>
            <span>Special Launch Offer &middot; All Features Free</span>
        </div>

        </div>

        <h2 class="sn-hero-heading">Choose Your Plan</h2>
        <p class="sn-hero-subtitle">Get full access to everything Socialvire offers.</p>

        {{-- Current Subscription State Banner --}}
        <div class="sn-status-strip" aria-label="Current Subscription Status">
            <div class="sn-status-strip-left">
                <span class="sn-status-kicker">Your subscription</span>
                <span class="sn-status-pill" style="background: {{ $statusBg }}; border-color: {{ $statusBorder }}; color: {{ $statusColor }};">
                    <span class="sn-status-dot" style="background: {{ $statusDot }};"></span>
                    {{ $statusLabel }}
                </span>
                @if($hasVerifiedSubscription && $currentPlan)
                    <span class="sn-status-plan">
                        Plan: <strong>{{ $currentPlan->name }}</strong>
                    </span>
                @endif
                <span class="sn-status-shop" title="{{ $shop }}">
                    Store: <strong>{{ $shop }}</strong>
                </span>
                @if($activatedDateStr)
                    <span class="sn-status-date">
                        Activated: <strong>{{ $activatedDateStr }}</strong>
                    </span>
                @endif
            </div>

            @if($hasVerifiedSubscription)
                <div class="sn-status-actions">
                    <button type="button" onclick="openCancelModal()" class="sn-btn-cancel-link">
                        Cancel subscription
                    </button>
                </div>
            @endif
        </div>
    </header>

    {{-- Desktop Two-Column Layout --}}
    <div class="sn-plans-grid">
        <article class="sn-card-main" aria-labelledby="planTitle">
            @if($hasVerifiedSubscription)
                <div class="sn-card-badge-top" style="background:#0284c7; color:#ffffff;">Current Plan</div>
            @else
                <div class="sn-card-badge-top">Everything Included</div>
            @endif

            <div class="sn-card-header">
                <div class="sn-plan-meta">
                    <div class="sn-plan-title-row">
                        <h2 id="planTitle" class="sn-plan-name">{{ $hasVerifiedSubscription && $currentPlan ? $currentPlan->name : 'Socialvire Free' }} <span class="sn-sr-only">Socialvire Free</span></h2>
                        @if($hasVerifiedSubscription)
                            <span class="sn-pill-current" style="display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:999px; background:#e0f2fe; color:#0369a1; font-weight:700; font-size:0.75rem; border:1px solid #bae6fd;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                Current Plan
                            </span>
                        @else
                            <span class="sn-pill-free-forever">Free Forever</span>
                        @endif
                    </div>
                    <p class="sn-plan-desc">Everything you need to connect your storefront with your customers' social platforms.</p>
                </div>

                <div class="sn-plan-pricing-box">
                    <div class="sn-price-row">
                        <span class="sn-currency">$</span>
                        <span class="sn-amount">{{ $hasVerifiedSubscription && $currentPlan && (float)$currentPlan->price > 0 ? (int)$currentPlan->price : '0' }}</span>
                        <span class="sn-interval">/ Forever</span>
                    </div>
                    <span class="sn-price-subtext">Free Forever &middot; No Credit Card Required</span>
                </div>
            </div>

            <div class="sn-divider"></div>

            <div class="sn-features-section">
                <h3 class="sn-features-heading">What's included in {{ $hasVerifiedSubscription && $currentPlan ? $currentPlan->name : 'Socialvire Free' }}:</h3>
                <ul class="sn-features-grid" role="list">
                    {{-- 6 Reference Features from Shopify Pricing --}}
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Core floating social sharing dock</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>WhatsApp product sharing</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Standard dock positioning</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Shopify Theme App Extension</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Add Wishlist feature on store</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>All features free for now</strong></span>
                    </li>

                    {{-- Core Platform Capabilities --}}
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Unlimited Social Links</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Floating Social Dock</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Storefront Customization</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Product Sharing</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Wishlist</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Analytics &amp; Click Tracking</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>Complete Dashboard Access</strong></span>
                    </li>
                    <li class="sn-feature-item">
                        <span class="sn-feature-icon" aria-hidden="true">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </span>
                        <span class="sn-feature-text"><strong>All Available Socialvire Features</strong></span>
                    </li>
                </ul>
            </div>

            <div class="sn-card-cta-footer">
                @if($hasVerifiedSubscription)
                    <div class="sn-btn-current-active" style="display:inline-flex; align-items:center; justify-content:center; gap:8px; width:100%; padding:0.875rem 1.5rem; background:#f0fdf4; color:#15803d; border:1.5px solid #86efac; border-radius:12px; font-weight:700; font-size:1rem; cursor:default; box-shadow:0 1px 2px rgba(0,0,0,0.05);">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span>Current Active Plan &middot; All Features Unlocked</span>
                    </div>
                @else
                    <a href="{{ $pricingUrl }}" target="_top" id="claimPlanBtn" class="sn-btn-primary-prominent" onclick="handleClaimPlan(event)" style="text-decoration:none;">
                        <span>Claim Free Plan</span>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </a>
                @endif
                <p class="sn-cta-subtext">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    @if($hasVerifiedSubscription)
                        <span>Active via Shopify App Pricing &middot; Free Forever &middot; Full Access</span>
                    @else
                        <span>No feature restrictions after successful Shopify Free plan activation &middot; Free Forever</span>
                    @endif
                </p>
            </div>
        </article>

        {{-- RIGHT COLUMN (~35%): Separate Information Panel --}}
        <aside class="sn-card-side" aria-label="Feature Breakdown &amp; Plan Explanation">
            <div class="sn-side-header">
                <h3>Everything included with {{ $hasVerifiedSubscription && $currentPlan ? $currentPlan->name : 'Socialvire Free' }} <span class="sn-sr-only">Everything included with Socialvire Free</span></h3>
                <p>A full-featured engagement suite for your storefront visitors.</p>
            </div>

            <div class="sn-side-breakdown">
                <div class="sn-breakdown-item">
                    <div class="sn-breakdown-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="5" r="3"></circle>
                            <circle cx="6" cy="12" r="3"></circle>
                            <circle cx="18" cy="19" r="3"></circle>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>
                        </svg>
                    </div>
                    <div class="sn-breakdown-details">
                        <h4>Floating Social Dock</h4>
                        <p>Sticky desktop and mobile dock connecting shoppers directly to your active social networks.</p>
                    </div>
                </div>

                <div class="sn-breakdown-item">
                    <div class="sn-breakdown-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path>
                        </svg>
                    </div>
                    <div class="sn-breakdown-details">
                        <h4>WhatsApp Product Sharing</h4>
                        <p>One-tap direct chat sharing with prefilled product URLs, pricing, and titles.</p>
                    </div>
                </div>

                <div class="sn-breakdown-item">
                    <div class="sn-breakdown-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                        </svg>
                    </div>
                    <div class="sn-breakdown-details">
                        <h4>Wishlist Integration</h4>
                        <p>Customer wishlist hearts on product cards and dedicated <code>/pages/wishlist</code> collection.</p>
                    </div>
                </div>

                <div class="sn-breakdown-item">
                    <div class="sn-breakdown-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                    </div>
                    <div class="sn-breakdown-details">
                        <h4>Storefront Customization</h4>
                        <p>Complete control over colors, animations, border radius, dock alignments, and icon styles.</p>
                    </div>
                </div>

                <div class="sn-breakdown-item">
                    <div class="sn-breakdown-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                    </div>
                    <div class="sn-breakdown-details">
                        <h4>Analytics &amp; Click Tracking</h4>
                        <p>Real-time click monitoring and platform breakdown across desktop and mobile visitors.</p>
                    </div>
                </div>

                <div class="sn-breakdown-item">
                    <div class="sn-breakdown-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="16 18 22 12 16 6"></polyline>
                            <polyline points="8 6 2 12 8 18"></polyline>
                        </svg>
                    </div>
                    <div class="sn-breakdown-details">
                        <h4>Shopify Theme App Extension</h4>
                        <p>Native Online Store 2.0 app embed blocks with zero theme code pollution or slow assets.</p>
                    </div>
                </div>
            </div>

                    <div class="sn-side-guarantee-box">
            <h4>
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                Special Launch Offer — Free Access
            </h4>
            <p>To celebrate the official launch of Socialvire, this plan is currently available completely free. Enjoy full access to our engagement toolset at zero cost during our launch period.</p>
        </div>


                        <div class="sn-plan-terms-bar">
                <div class="sn-term-item">
                    <span class="sn-term-label">Offer</span>
                    <span class="sn-term-val">Launch Special</span>
                </div>
                <div class="sn-term-item">
                    <span class="sn-term-label">Billing</span>
                    <span class="sn-term-val">Free ($0)</span>
                </div>
                <div class="sn-term-item">
                    <span class="sn-term-label">Duration</span>
                    <span class="sn-term-val">Launch Period</span>
                </div>
            </div>

        </aside>
    </div>

    {{-- Additional Information Section: 3 Compact Cards --}}
    <section class="sn-info-cards-grid" aria-label="Additional Information">
                <div class="sn-info-card">
            <div class="sn-info-card-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2l8 4v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6l8-4z"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
            </div>
            <h4>Free Launch Offer</h4>
            <p>No monthly charges or hidden costs during our launch period. Enjoy full Socialvire capabilities on your store completely free.</p>
        </div>


        <div class="sn-info-card">
            <div class="sn-info-card-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
            </div>
            <h4>Complete Storefront Customization</h4>
            <p>Customize social dock positioning, appearance and storefront experience to match your brand style.</p>
        </div>

        <div class="sn-info-card">
            <div class="sn-info-card-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 3v18h18"/>
                    <path d="m19 9-5 5-4-4-3 3"/>
                </svg>
            </div>
            <h4>Live Click Analytics</h4>
            <p>Track visitor engagement and clicks across connected social platforms in real time from your unified dashboard.</p>
        </div>
    </section>
</div>

{{-- Preserved Subscription Cancellation Modal --}}
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

            <button type="button" id="confirmCancelBtn" onclick="handleCancelActiveSubscription()" class="sv-btn sv-btn-danger">Confirm cancellation</button>
        </div>
    </div>
</div>

<script>
    (function () {
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

        function handleEscapeKey(event) {
            if (event.key === 'Escape') closeCancelModal();
        }

        function handleBackdropClick(event) {
            if (event.target === this) closeCancelModal();
        }

        function handleClaimPlan(event) {
            const pricingUrl = @json($pricingUrl ?? ($store ? $store->getAppPricingUrl() : ''));
            if (!pricingUrl) {
                return;
            }

            // 1. Try Shopify App Bridge top-level navigation
            if (window.shopify && typeof window.shopify.open === 'function') {
                if (event && event.preventDefault) event.preventDefault();
                window.shopify.open(pricingUrl, '_top');
                return;
            }

            // 2. Try window.open with _top target
            try {
                if (window.open(pricingUrl, '_top')) {
                    if (event && event.preventDefault) event.preventDefault();
                    return;
                }
            } catch (e) {
                console.warn('[SocialNest] window.open failed, falling back to natural link navigation', e);
            }

            // 3. Native fallback: do NOT preventDefault, allowing <a href="..." target="_top"> to navigate
        }

        async function handleCancelActiveSubscription() {
            const cancelBtn = document.getElementById('confirmCancelBtn');
            if (cancelBtn) {
                cancelBtn.disabled = true;
                cancelBtn.textContent = 'Cancelling...';
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || '{{ csrf_token() }}';

                const response = await fetch("{{ route('shopify.subscription.cancel.active') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        shop: "{{ $shop }}",
                        host: "{{ $host }}",
                    }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    if (window.showToast) {
                        window.showToast('Subscription cancelled successfully. Your store is now on free tier.', 'success', 3500);
                    }
                    setTimeout(function () {
                        window.location.reload();
                    }, 500);
                } else {
                    AppNotice.error(data.message || 'Failed to cancel subscription. Please try again.', 'Unable to Cancel Subscription');
                    if (cancelBtn) {
                        cancelBtn.disabled = false;
                        cancelBtn.textContent = 'Confirm cancellation';
                    }
                }
            } catch (error) {
                console.error('Cancellation error:', error);
                AppNotice.error('An unexpected error occurred while cancelling your subscription.', 'Unable to Cancel Subscription');
                if (cancelBtn) {
                    cancelBtn.disabled = false;
                    cancelBtn.textContent = 'Confirm cancellation';
                }
            }
        }

        document.addEventListener('keydown', handleEscapeKey);

        const cancelModal = document.getElementById('cancelModal');
        if (cancelModal) {
            cancelModal.addEventListener('click', handleBackdropClick);
        }

        window.openCancelModal = openCancelModal;
        window.closeCancelModal = closeCancelModal;
        window.handleClaimPlan = handleClaimPlan;
        window.handleCancelActiveSubscription = handleCancelActiveSubscription;

        document.addEventListener('shopify:before-swap', function teardownPlansPage() {
            document.removeEventListener('keydown', handleEscapeKey);
            if (cancelModal) {
                cancelModal.removeEventListener('click', handleBackdropClick);
            }
            document.body.style.overflow = '';
            document.removeEventListener('shopify:before-swap', teardownPlansPage);
        });
    })();
</script>
@endsection
