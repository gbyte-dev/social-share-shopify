@extends('layouts.shopify')

@section('title', 'Help & Support - ' . $shop)
@section('header_title', 'Help & Support')
@section('header_subtitle', 'Guidance on theme extensions, storefront testing, and troubleshooting')

@section('content')
    <style>
        .support-grid-2col {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
            gap: 1.5rem;
            align-items: stretch;
            margin-bottom: 2rem;
        }
        .support-wishlist-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 1.5rem;
            align-items: stretch;
            margin-bottom: 1.5rem;
        }
        @media (max-width: 960px) {
            .support-grid-2col,
            .support-wishlist-grid {
                grid-template-columns: 1fr;
            }
        }
        .support-step-item {
            display: flex;
            gap: 0.6rem;
            align-items: baseline;
            line-height: 1.5;
        }
        .support-step-num {
            font-weight: 700;
            color: var(--text-main);
            min-width: 16px;
        }
        .support-code-pill {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 0.1rem 0.35rem;
            border-radius: 4px;
            font-weight: 700;
            color: var(--primary);
            font-family: monospace;
            font-size: 0.85em;
        }
    </style>

    <!-- Top Section: Storefront Theme Activation Guide + System Status -->
    <div class="support-grid-2col">
        <!-- Storefront Theme Activation Guide -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem;">
                    Storefront Theme Activation Guide
                </h3>
                <p style="font-size: 0.8125rem;   margin-bottom: 1.5rem;">
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
                                Toggle <strong>Socialvire Widget</strong> to ON, then click <strong>Save</strong> in the upper right. Your floating social dock is now live on your store!
                            </p>
                        </div>
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

        <!-- Sidebar Info (Right Column of Top Section) -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem; justify-content: space-between;">
            <div class="card" style="background: var(--surface-bg);">
                <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.35rem;">Shopify Dev Status</h4>
                <p style="font-size: 0.775rem;   line-height: 1.5; margin-bottom: 1rem;">
                    Socialvire is built for the Shopify App Store. All OAuth and webhook handshakes strictly adhere to Shopify App Requirements.
                </p>
                <div style="font-size: 0.8125rem; line-height: 1.6; color: var(--text-main); border-top: 1px solid var(--card-border); padding-top: 0.75rem;">
                    <div><strong>Store:</strong> {{ $shop }}</div>
                    <div><strong>Architecture:</strong> Shopify Online Store 2.0</div>
                    <div><strong>API Version:</strong> 2025-01</div>
                </div>
            </div>

            <div class="card">
                <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.35rem;">Need Assistance?</h4>
                <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5;">
                    Our team is here to assist with theme styling adjustments or custom platform integrations.
                </p>
            </div>
        </div>
    </div>

    <!-- Wishlist Final Save Instruction -->
    <div style="margin-bottom: 0.85rem; display: flex; align-items: center; gap: 0.625rem;">
        <div style="width: 30px; height: 30px; border-radius: 8px; background: #ecfdf5; color: #008060; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                <path d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div>
            <h3 style="font-size: 1rem; font-weight: 800; color: #202223; margin: 0;">
                Wishlist setup is ready
            </h3>
            <p style="font-size: 0.8rem; font-weight: 600; color: #5c5f62; margin: 2px 0 0;">
                The Wishlist page and app block are prepared. Just save your theme to publish the changes.
            </p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1rem; padding: 0.9rem 1rem; border: 1px solid #a7f3d0; background: #f0fdf4;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: flex-start; gap: 0.65rem; min-width: 0;">
                <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="#008060" stroke-width="2.2" style="flex-shrink:0; margin-top:1px;">
                    <path d="M5 13l4 4L19 7"/>
                    <path d="M12 22a10 10 0 1 0-10-10"/>
                </svg>
                <div>
                    <h4 style="font-size: 0.9rem; font-weight: 800; color: #202223; margin: 0 0 3px;">
                        Final step: Save your Shopify theme
                    </h4>
                    <p style="font-size: 0.78rem; font-weight: 600; color: #3f5f50; line-height: 1.5; margin: 0;">
                        In the Theme Editor, click <strong>Save</strong> in the top-right corner. This publishes the Wishlist page setup and makes it available on your storefront.
                    </p>
                </div>
            </div>
            <span style="flex-shrink:0; display:inline-flex; align-items:center; gap:6px; padding:5px 9px; border:1px solid #a7f3d0; border-radius:999px; background:#fff; color:#047857; font-size:0.7rem; font-weight:800;">
                <span style="width:6px;height:6px;border-radius:50%;background:#10b981;"></span>
                One action remaining
            </span>
        </div>
    </div>

    <!-- Important Notes (Full Width) -->
    <div class="card" style="margin-bottom: 2rem; background: var(--primary-light); border: 1px solid var(--primary-border);">
        <h5 style="font-size: 0.875rem; font-weight: 800; color: var(--primary); margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.35rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Important Notes
        </h5>
        <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.8125rem; color: var(--text-main); line-height: 1.6; display: flex; flex-direction: column; gap: 0.25rem;">
            <li>Click <strong>Save</strong> in the Shopify Theme Editor to publish the Wishlist setup.</li>
            <li>After saving, open your storefront Wishlist page and confirm that the Wishlist block is visible.</li>
            <li>If the Wishlist block does not appear, verify that the Socialvire app embed or app block is enabled in your active theme.</li>
            <li>Wishlist data is currently stored in the customer's browser using <code style="background: #ffffff; border: 1px solid var(--primary-border); padding: 0.05rem 0.3rem; border-radius: 3px; font-weight: 700; color: var(--primary);">localStorage</code>.</li>
        </ul>
    </div>

    <!-- Bottom Section: Frequently Asked Questions -->
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
                    No. Socialvire uses lightweight vector SVGs with zero bloated JavaScript dependencies. Configuration is cached for maximum speed and instant rendering.
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

@endsection
