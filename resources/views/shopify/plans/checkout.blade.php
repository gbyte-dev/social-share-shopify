@extends('layouts.shopify')

@section('title', 'Confirm Subscription - ' . $shop)
@section('header_title', 'Confirm Dummy Subscription')
@section('header_subtitle', 'Review your selected plan and activate your 30-day SocialNest access')

@section('content')
<style>
    .sn-checkout, .sn-checkout * { box-sizing: border-box; }
    .sn-checkout { width:100%; max-width:1180px; margin:0 auto; color:#202223; }
    .sn-checkout-card { background:#fff; border:1px solid #e1e3e5; border-radius:12px; overflow:hidden; box-shadow:0 1px 2px rgba(0,0,0,.04); }
    .sn-checkout-top { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:15px 18px; border-bottom:1px solid #e1e3e5; }
    .sn-checkout-title { margin:0; color:#202223; font-size:19px; font-weight:700; letter-spacing:-.025em; }
    .sn-checkout-desc { margin:5px 0 0; color: #000000; font-size:12px; line-height:1.5; }
    .sn-period { flex:0 0 auto; padding:5px 9px; border-radius:20px; background:#f1f0ff; color:#5146e5; font-size:11px; font-weight:700; }
    .sn-checkout-price { display:flex; align-items:baseline; flex-wrap:wrap; gap:7px; margin-top:8px; }
    .sn-checkout-price strong { font-size:27px; line-height:1; letter-spacing:-.04em; color:#202223; }
    .sn-checkout-price span { color: #000000; font-size:12px; }
    .sn-checkout-grid { display:grid; grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr); gap:13px; padding:13px 18px; align-items:start; }
    .sn-checkout-section { min-width:0; border:1px solid #e8eaed; border-radius:9px; overflow:hidden; }
    .sn-section-head { padding:10px 12px; background:#fafbfb; border-bottom:1px solid #e8eaed; }
    .sn-section-head h4 { margin:0; color:#303438; font-size:12px; font-weight:750; }
    .sn-feature-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 12px; padding:4px 12px; }
    .sn-feature { display:flex; align-items:flex-start; gap:7px; padding:7px 0; border-bottom:1px solid #f0f1f2; color:#42474b; font-size:12px; line-height:1.4; min-width:0; }
    .sn-feature svg { flex:0 0 15px; margin-top:1px; color:#008060; }
    .sn-detail-list { padding:4px 12px; }
    .sn-detail-row { display:flex; justify-content:space-between; align-items:flex-start; gap:14px; padding:7px 0; border-bottom:1px solid #f0f1f2; font-size:11px; line-height:1.4; }
    .sn-detail-row:last-child { border-bottom:0; }
    .sn-detail-row span { color: #000000; }
    .sn-detail-row strong { color:#303438; text-align:right; overflow-wrap:anywhere; font-weight:650; }
    .sn-disclosure { display:flex; gap:9px; margin:0 18px; padding:9px 11px; border:1px solid #c9dcff; border-left:3px solid #2c6ecb; border-radius:7px; background:#f2f7ff; color:#244f91; }
    .sn-disclosure svg { flex:0 0 16px; margin-top:1px; }
    .sn-disclosure strong { display:block; margin-bottom:2px; font-size:11px; }
    .sn-disclosure p { margin:0; font-size:11px; line-height:1.45; }
    .sn-checkout-actions { display:flex; justify-content:flex-end; align-items:center; gap:8px; padding:12px 18px; margin-top:12px; border-top:1px solid #e1e3e5; background:#fcfcfc; }
    .sn-checkout-actions .btn { min-height:35px; padding:6px 13px; font-size:12px; }
    @media(max-width:760px) {
        .sn-checkout-grid { grid-template-columns:1fr; padding:11px; gap:10px; }
        .sn-checkout-top { padding:13px; }
        .sn-disclosure { margin:0 11px; }
        .sn-checkout-actions { padding:11px; }
    }
    @media(max-width:480px) {
        .sn-feature-grid { grid-template-columns:1fr; }
        .sn-checkout-top { flex-direction:column; }
        .sn-checkout-actions { flex-direction:column-reverse; align-items:stretch; }
        .sn-checkout-actions .btn { width:100%; text-align:center; }
    }
</style>

<div class="sn-checkout">
    <div class="sn-checkout-card">
        <div class="sn-checkout-top">
            <div>
                <h3 class="sn-checkout-title">{{ $plan->name }} Plan</h3>
                <div class="sn-checkout-price">
                    <strong>{{ $plan->formattedPrice() }}</strong>
                    <span>for 30 days · Dummy billing</span>
                </div>
                <p class="sn-checkout-desc">{{ $plan->description }}</p>
            </div>
            <span class="sn-period">30-Day Period</span>
        </div>

        <div class="sn-checkout-grid">
            <section class="sn-checkout-section">
                <div class="sn-section-head"><h4>Included features</h4></div>
                @if(!empty($plan->features))
                    <div class="sn-feature-grid">
                        @foreach($plan->features as $feature)
                            <div class="sn-feature">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                <span>{{ $feature }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="padding:12px;color: #000000;font-size:12px;">No feature details are available for this plan.</div>
                @endif
            </section>

            <section class="sn-checkout-section">
                <div class="sn-section-head"><h4>Subscription details</h4></div>
                <div class="sn-detail-list">
                    <div class="sn-detail-row"><span>Connected store</span><strong>{{ $shop }}</strong></div>
                    <div class="sn-detail-row"><span>Activation timestamp</span><strong>{{ now()->format('M d, Y H:i T') }}</strong></div>
                    <div class="sn-detail-row"><span>Expiry date</span><strong>{{ now()->addDays(30)->format('M d, Y H:i T') }}</strong></div>
                    <div class="sn-detail-row"><span>Access period</span><strong>30 days</strong></div>
                    <div class="sn-detail-row"><span>Renewal type</span><strong>Manual renewal only</strong></div>
                    <div class="sn-detail-row"><span>Amount due</span><strong>{{ $plan->formattedPrice() }}</strong></div>
                </div>
            </section>
        </div>

        <div class="sn-disclosure">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            <div>
                <strong>Dummy subscription disclosure</strong>
                <p>This is a test subscription. No real money, credit cards, or Shopify recurring application charges will be collected. Your 30-day SocialNest access will be activated after confirmation.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('shopify.plans.subscribe') }}">
            @csrf
            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
            <input type="hidden" name="shop" value="{{ $shop }}">
            <input type="hidden" name="host" value="{{ $host }}">
            <div class="sn-checkout-actions">
                <a href="{{ route('shopify.plans.index', ['shop' => $shop, 'host' => $host]) }}" class="btn btn-secondary" style="text-decoration:none;">Cancel</a>
                <button type="submit" class="btn btn-primary" style="font-weight:700;">Confirm 30-Day Subscription</button>
            </div>
        </form>
    </div>
</div>
@endsection
