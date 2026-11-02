@extends('layouts.shopify')

@section('title', 'Help & Support - ' . $shop)
@section('header_title', 'Help & Support')
@section('header_subtitle', 'Guidance on theme extensions, storefront testing, and troubleshooting')

@section('content')
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: flex-start;">
        <div>
            <!-- Step-by-Step Setup Guide -->
            <div class="card" style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">
                    Storefront Theme Activation Guide
                </h3>
                <p style="font-size: 0.8125rem; color: var(--text-subtle); margin-bottom: 1.5rem;">
                    Follow these 3 quick steps to enable your floating social icons on Shopify Online Store 2.0.
                </p>

                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <!-- Step 1 -->
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #ffffff; font-weight: 800; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            1
                        </div>
                        <div>
                            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Add Your Social Channels</h4>
                            <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5;">
                                Go to <a href="{{ route('shopify.social-links.index') }}" style="color: var(--primary); font-weight: 600;">My Social Links</a> and add your Instagram, WhatsApp, Facebook, TikTok, or YouTube links. Make sure they are toggled to <strong>Active</strong>.
                            </p>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #ffffff; font-weight: 800; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            2
                        </div>
                        <div>
                            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Open Theme Editor App Embeds</h4>
                            <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5;">
                                Open your Shopify Admin, click <strong>Online Store &rarr; Themes &rarr; Customize</strong>. In the left panel, click the <strong>App Embeds</strong> tab (third icon from top).
                            </p>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div style="display: flex; gap: 1rem; align-items: flex-start;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #ffffff; font-weight: 800; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            3
                        </div>
                        <div>
                            <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Enable & Save</h4>
                            <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5;">
                                Toggle <strong>SocialNest Widget</strong> to ON, then click <strong>Save</strong> in the upper right. Your floating social dock is now live on your store!
                            </p>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--card-border);">
                    <a href="https://{{ $shop }}/admin/themes/current/editor?context=apps" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <span>Open Shopify Theme Editor (App Embeds)</span>
                    </a>
                </div>
            </div>

            <!-- FAQs -->
            <div class="card">
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-bottom: 1.25rem;">
                    Frequently Asked Questions
                </h3>

                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <div>
                        <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">How does WhatsApp sharing work on product pages?</h4>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5;">
                            When shoppers click the WhatsApp icon, it opens WhatsApp Web or mobile app with your configured message and product link ready to send to friends or support contacts.
                        </p>
                    </div>

                    <div>
                        <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">Will the icons slow down my storefront?</h4>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5;">
                            No. SocialNest uses lightweight vector SVGs with zero bloated JavaScript dependencies. Configuration is cached for maximum speed and instant rendering.
                        </p>
                    </div>

                    <div>
                        <h4 style="font-size: 0.9rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.35rem;">Can I reposition the icons if they block my chat widget?</h4>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5;">
                            Yes. In <a href="{{ route('shopify.settings.index') }}" style="color: var(--primary); font-weight: 600;">Storefront Settings</a>, you can switch position between Bottom Right, Bottom Left, Vertical Right, or Vertical Left.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Contact Details -->
        <div>
            <div class="card" style="margin-bottom: 1.5rem;">
                <h4 style="font-size: 1rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">Need Assistance?</h4>
                <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem;">
                    Our team is here to assist with theme styling adjustments or custom platform integrations.
                </p>

                <div style="font-size: 0.8125rem; line-height: 1.6; color: var(--text-main);">
                    <div><strong>Store:</strong> {{ $shop }}</div>
                    <div><strong>App Version:</strong> 1.0.0 (Shopify 2025-01 API)</div>
                    <div><strong>Architecture:</strong> Shopify Online Store 2.0</div>
                </div>
            </div>

            <div class="card" style="background: var(--surface-bg);">
                <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.35rem;">Shopify Dev Status</h4>
                <p style="font-size: 0.75rem; color: var(--text-subtle); line-height: 1.5;">
                    SocialNest is built for the Shopify App Store. All OAuth and webhook handshakes strictly adhere to Shopify App Requirements.
                </p>
            </div>
        </div>
    </div>
@endsection
