@extends('layouts.shopify')

@section('title', 'Confirm Subscription - ' . $shop)
@section('header_title', 'Confirm Dummy Subscription')
@section('header_subtitle', 'Review your selected plan and activate your 30-day SocialNest access')

@section('content')
    <div style="max-width: 680px; margin: 0 auto;">
        <!-- Subscription Confirmation Card -->
        <div class="card" style="margin-bottom: 2rem; background: #ffffff; border: 2px solid var(--primary);">
            <div style="margin-bottom: 1.5rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--card-border);">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem;">
                    <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--text-main);">
                        {{ $plan->name }} Plan
                    </h3>
                    <span style="font-size: 0.75rem; font-weight: 700; background: var(--primary-light); color: var(--primary); padding: 0.25rem 0.75rem; border-radius: 9999px;">
                        30-Day Period
                    </span>
                </div>
                <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                    <span style="font-size: 2.5rem; font-weight: 800; color: var(--text-main);">
                        {{ $plan->formattedPrice() }}
                    </span>
                    <span style="font-size: 0.875rem; color: var(--text-muted);">
                        for 30 days (Dummy Billing)
                    </span>
                </div>
                <p style="font-size: 0.875rem; color: var(--text-muted); margin-top: 0.5rem;">
                    {{ $plan->description }}
                </p>
            </div>

            <!-- Plan Features List -->
            <div style="margin-bottom: 1.5rem;">
                <h4 style="font-size: 0.875rem; text-transform: uppercase; font-weight: 700; color: var(--text-subtle); letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                    Included Features
                </h4>
                @if(!empty($plan->features))
                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 0.625rem; font-size: 0.875rem; color: var(--text-main);">
                        @foreach($plan->features as $feature)
                            <li style="display: flex; align-items: center; gap: 0.5rem;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <span>{{ $feature }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Subscription Period Breakdown -->
            <div style="margin-bottom: 1.5rem; background: var(--surface-bg); padding: 1rem; border-radius: 8px; font-size: 0.8125rem;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Connected Store:</span>
                    <strong style="color: var(--text-main);">{{ $shop }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Activation Timestamp:</span>
                    <strong style="color: var(--text-main);">{{ now()->format('M d, Y H:i T') }}</strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span style="color: var(--text-muted);">Expiry Date:</span>
                    <strong style="color: var(--text-main);">{{ now()->addDays(30)->format('M d, Y H:i T') }} (30 Days)</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);">Renewal Type:</span>
                    <strong style="color: var(--text-main);">Manual Renewal Only</strong>
                </div>
            </div>

            <!-- Test / Dummy Disclosure Notice -->
            <div style="margin-bottom: 1.75rem; background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 6px;">
                <div style="display: flex; align-items: flex-start; gap: 0.625rem;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <div>
                        <h5 style="font-size: 0.875rem; font-weight: 700; color: #1e40af; margin-bottom: 0.2rem;">Dummy Subscription Disclosure</h5>
                        <p style="font-size: 0.8125rem; color: #1d4ed8; line-height: 1.4;">
                            This is a dummy test subscription. No real money, credit cards, or Shopify recurring application charges will be collected. Your 30-day access to all SocialNest features will be activated immediately upon clicking the confirmation button below.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Confirmation Action Form -->
            <form method="POST" action="{{ route('shopify.plans.subscribe') }}">
                @csrf
                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                <input type="hidden" name="shop" value="{{ $shop }}">
                <input type="hidden" name="host" value="{{ $host }}">

                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 1rem;">
                    <a href="{{ route('shopify.plans.index', ['shop' => $shop, 'host' => $host]) }}" class="btn btn-secondary" style="text-decoration: none;">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.75rem; font-size: 0.95rem; font-weight: 700;">
                        Confirm 30-Day Subscription
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
