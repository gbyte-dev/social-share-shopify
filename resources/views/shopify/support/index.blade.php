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
                <p style="font-size: 0.775rem; color: var(--text-subtle); line-height: 1.5; margin-bottom: 1rem;">
                    SocialNest is built for the Shopify App Store. All OAuth and webhook handshakes strictly adhere to Shopify App Requirements.
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

    <!-- Wishlist Setup Header -->
    <div style="margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
        <div style="display: flex; align-items: center; gap: 0.625rem;">
            <div style="width: 32px; height: 32px; border-radius: 8px; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
            </div>
            <div>
                <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0;">
                    How to Set Up Wishlist
                </h3>
                <p style="font-size: 0.8125rem; color: var(--text-subtle); margin: 0.15rem 0 0 0;">
                    Follow these two procedures to configure your dedicated storefront Wishlist page.
                </p>
            </div>
        </div>
        <span style="font-size: 0.75rem; font-weight: 700; color: #ef4444; background: #fef2f2; border: 1px solid #fecaca; padding: 0.25rem 0.625rem; border-radius: 9999px; display: inline-flex; align-items: center; gap: 0.35rem;">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: #ef4444;"></span>
            2-Step Required Setup
        </span>
    </div>

    <!-- 1-Click Automated Wishlist Setup Card -->
    <div class="card" style="margin-bottom: 1.5rem; background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(245, 243, 255, 0.85) 100%); border: 1.5px solid var(--primary-border);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div style="flex: 1; min-width: 280px;">
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 6px; background: var(--primary); color: #fff;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </span>
                    <h4 style="font-size: 1rem; font-weight: 800; color: var(--text-main); margin: 0;">Automated 1-Click Wishlist Setup</h4>
                    <span style="font-size: 0.7rem; font-weight: 700; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.5rem; border-radius: 9999px;">GraphQL & Deep Link</span>
                </div>
                <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 0.5rem 0;">
                    Checks if <code class="support-code-pill">/pages/wishlist</code> exists (or creates and publishes it via Shopify GraphQL without overwriting existing content), then opens your Theme Editor with the <strong>SocialNest Wishlist</strong> app block pre-selected in your main section.
                </p>
                <div style="display: flex; align-items: center; gap: 1.25rem; flex-wrap: wrap; font-size: 0.75rem; color: var(--text-subtle);">
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Preserves existing Wishlist pages
                    </span>
                    <span style="display: inline-flex; align-items: center; gap: 0.35rem;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        Targets main page section with auto-fallback to Apps
                    </span>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.35rem;">
                <button type="button" id="btn-auto-setup-wishlist" onclick="runAutomatedWishlistSetup()" class="btn btn-primary" style="padding: 0.65rem 1.25rem; font-weight: 700; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.2); display: inline-flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <svg id="icon-auto-setup" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    <span id="text-auto-setup">Auto-Setup Wishlist & Open Theme Editor</span>
                </button>
                <span id="status-auto-setup" style="font-size: 0.75rem; color: var(--text-subtle); display: none;"></span>
            </div>
        </div>
    </div>

    <!-- Wishlist 2-Column Side-by-Side Grid (Step 1 on Left, Step 2 on Right) -->
    <div class="support-wishlist-grid">
        <!-- Step 1: Create a Wishlist Page in Shopify (Left Card) -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--card-border);">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 0.75rem; font-weight: 800;">1</span>
                        Create a Wishlist Page in Shopify
                    </h4>
                    <span style="font-size: 0.7rem; color: var(--primary); font-weight: 700; background: var(--primary-light); border: 1px solid var(--primary-border); padding: 0.15rem 0.45rem; border-radius: 4px;">Step 1</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8125rem; color: var(--text-muted);">
                    <div class="support-step-item">
                        <span class="support-step-num">1.</span>
                        <span>Open <strong>Shopify Admin</strong>.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">2.</span>
                        <span>Go to <strong>Online Store &rarr; Pages</strong>.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">3.</span>
                        <span>Click <strong>Add Page</strong>.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">4.</span>
                        <span>Enter page title: <code class="support-code-pill">Wishlist</code></span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">5.</span>
                        <span>Set the URL handle to <code class="support-code-pill">wishlist</code></span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">6.</span>
                        <span>Save the page.</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; font-size: 0.775rem; color: var(--text-main); background: var(--surface-bg); padding: 0.5rem 0.75rem; border-radius: 6px; border: 1px solid var(--card-border); display: flex; align-items: center; gap: 0.4rem;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary); flex-shrink: 0;"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                    <span>The page URL will be <strong style="color: var(--primary);">/pages/wishlist</strong>.</span>
                </div>
            </div>

            <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--card-border);">
                <a href="https://{{ $shop }}/admin/pages" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="width: 100%; justify-content: center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span>Manage Pages in Shopify</span>
                </a>
            </div>
        </div>

        <!-- Step 2: Add the SocialNest Wishlist App Block (Right Card) -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid var(--card-border);">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; background: var(--primary); color: #fff; font-size: 0.75rem; font-weight: 800;">2</span>
                        Add the SocialNest Wishlist App Block
                    </h4>
                    <span style="font-size: 0.7rem; color: var(--primary); font-weight: 700; background: var(--primary-light); border: 1px solid var(--primary-border); padding: 0.15rem 0.45rem; border-radius: 4px;">Step 2</span>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.8125rem; color: var(--text-muted);">
                    <div class="support-step-item">
                        <span class="support-step-num">1.</span>
                        <span>Open <strong>Online Store &rarr; Themes</strong>.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">2.</span>
                        <span>Find your active theme and click <strong>Customize</strong>.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">3.</span>
                        <span>Navigate to the newly created Wishlist page using the page selector.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">4.</span>
                        <span>Open the main page section.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">5.</span>
                        <span>Click <strong>Add block</strong> or <strong>Add section</strong>, depending on the theme.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">6.</span>
                        <span>Find and select the <strong>SocialNest Wishlist</strong> app block.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">7.</span>
                        <span>Add it to the main page content area, not the header or footer.</span>
                    </div>
                    <div class="support-step-item">
                        <span class="support-step-num">8.</span>
                        <span>Save the theme.</span>
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid var(--card-border);">
                <a href="{{ route('shopify.wishlist.setup') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                    <span>Open Theme Editor with Wishlist Block</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Important Notes (Full Width) -->
    <div class="card" style="margin-bottom: 2rem; background: var(--primary-light); border: 1px solid var(--primary-border);">
        <h5 style="font-size: 0.875rem; font-weight: 800; color: var(--primary); margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 0.35rem;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
            Important Notes
        </h5>
        <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.8125rem; color: var(--text-main); line-height: 1.6; display: flex; flex-direction: column; gap: 0.25rem;">
            <li>Both steps are required for Wishlist page setup.</li>
            <li>Use the exact page handle <code style="background: #ffffff; border: 1px solid var(--primary-border); padding: 0.05rem 0.3rem; border-radius: 3px; font-weight: 700; color: var(--primary);">wishlist</code>.</li>
            <li>The Wishlist app block must be placed inside the main page content.</li>
            <li>If the SocialNest Wishlist block is not visible, verify that the SocialNest Theme App Extension is enabled and the latest app extension is deployed.</li>
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

    <script>
        function runAutomatedWishlistSetup() {
            const btn = document.getElementById('btn-auto-setup-wishlist');
            const text = document.getElementById('text-auto-setup');
            const status = document.getElementById('status-auto-setup');

            if (!btn) return;
            btn.disabled = true;
            btn.style.opacity = '0.7';
            text.textContent = 'Verifying page & preparing Theme Editor...';
            if (status) {
                status.style.display = 'block';
                status.style.color = 'var(--text-subtle)';
                status.textContent = 'Connecting to Shopify Admin API...';
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch('{{ route('shopify.wishlist.setup') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ target: 'mainSection' })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return { status: response.status, data: data };
                });
            })
            .then(function(res) {
                if (res.status >= 200 && res.status < 300 && res.data.success) {
                    text.textContent = 'Opening Theme Editor...';
                    if (status) {
                        if (res.data.requires_reauth && res.data.reauth_url) {
                            status.style.color = '#b45309';
                            status.innerHTML = '⚠️ Theme Editor opened in new tab! To enable automated page creation, <a href="' + res.data.reauth_url + '" target="_top" style="color: var(--primary); font-weight: 700; text-decoration: underline;">approve updated page permissions</a>, or verify <code class="support-code-pill">/pages/wishlist</code> in Pages.';
                        } else {
                            status.style.color = '#047857';
                            status.textContent = res.data.message + ' Opening Theme Editor in a new tab...';
                        }
                    }
                    if (res.data.deep_link_url) {
                        window.open(res.data.deep_link_url, '_blank', 'noopener,noreferrer');
                    }
                    setTimeout(function() {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        text.textContent = 'Auto-Setup Wishlist & Open Theme Editor';
                    }, 4000);
                } else {
                    throw new Error(res.data.message || 'Failed to complete wishlist setup.');
                }
            })
            .catch(function(err) {
                btn.disabled = false;
                btn.style.opacity = '1';
                text.textContent = 'Auto-Setup Wishlist & Open Theme Editor';
                if (status) {
                    status.style.color = '#dc2626';
                    status.textContent = err.message || 'Error executing setup.';
                }
                alert(err.message || 'Unable to complete automated setup. Please use the manual steps below.');
            });
        }
    </script>
@endsection
