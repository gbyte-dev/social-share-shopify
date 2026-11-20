@extends('layouts.shopify')

@section('title', 'Help & Support - ' . $shop)
@section('header_title', 'Help & Support')
@section('header_subtitle', 'Guidance on theme extensions, storefront testing, and troubleshooting')

@section('content')
    <style>
        .support-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Nav Quick-Links Bar */
        .support-nav-bar {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            flex-wrap: wrap;
            padding: 0.75rem 1rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xs);
        }

        .support-nav-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-decoration: none;
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            transition: all 0.15s ease-in-out;
        }

        .support-nav-pill:hover {
            color: var(--primary);
            background: var(--primary-light);
            border-color: var(--primary-border);
        }

        /* 2-Column Responsive Grids */
        .support-grid-2col {
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
            gap: 1.5rem;
            align-items: stretch;
        }

        .support-wishlist-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 1.5rem;
            align-items: stretch;
        }

        .support-grid-3col {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.25rem;
        }

        @media (max-width: 960px) {
            .support-grid-2col,
            .support-wishlist-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Step Items */
        .support-step-item {
            display: flex;
            gap: 0.6rem;
            align-items: baseline;
            line-height: 1.5;
        }

        .support-step-num {
            font-weight: 700;
            color: var(--text-main);
            min-width: 18px;
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

        /* FAQ Accordion / Item */
        .faq-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 1.25rem 1.5rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }

        .faq-card:hover {
            border-color: var(--card-border-hover);
            box-shadow: var(--shadow-sm);
        }

        .faq-question {
            font-size: 0.9375rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .faq-answer {
            font-size: 0.8125rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Diagnostic Badge */
        .diag-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.45rem 0;
            border-bottom: 1px solid var(--card-border);
            font-size: 0.8125rem;
        }

        .diag-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .diag-label {
            color: var(--text-subtle);
            font-weight: 500;
        }

        .diag-val {
            font-weight: 700;
            color: var(--text-main);
            font-family: monospace;
            font-size: 0.8em;
            background: var(--surface-bg);
            padding: 0.15rem 0.4rem;
            border-radius: 4px;
            border: 1px solid var(--card-border);
        }

        /* Checkbox Diagnostic List */
        .troubleshoot-list {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            margin-top: 0.75rem;
        }

        .troubleshoot-item {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            font-size: 0.8125rem;
            line-height: 1.5;
            color: var(--text-secondary);
        }

        .troubleshoot-bullet {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            background: var(--primary-light);
            color: var(--primary);
            font-weight: 800;
            font-size: 0.7rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }
    </style>

    <div class="support-container">
        <!-- Section A: Support Overview Header & Quick Navigation -->
        <div class="card" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.04) 0%, rgba(255, 255, 255, 1) 100%); border: 1.5px solid var(--primary-border);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div style="max-width: 800px;">
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.65rem; background: var(--primary-light); color: var(--primary); border: 1px solid var(--primary-border); border-radius: 9999px; font-size: 0.75rem; font-weight: 700; margin-bottom: 0.75rem;">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        SocialNest Knowledge Base & Technical Center
                    </div>
                    <h2 style="font-size: 1.45rem; font-weight: 800; color: var(--text-main); margin-bottom: 0.5rem; letter-spacing: -0.02em;">
                        Help & Support
                    </h2>
                    <p style="font-size: 0.875rem; color: var(--text-secondary); line-height: 1.6; margin: 0;">
                        Welcome to the SocialNest Help Center. Find step-by-step documentation, configuration walkthroughs, and troubleshooting guides to activate your floating social docks, configure inline product sharing, and launch your dedicated storefront wishlist on Shopify Online Store 2.0.
                    </p>
                </div>

                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="#contact-support" class="btn btn-primary btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <span>Contact Support</span>
                    </a>
                    <a href="https://{{ $shop }}/admin/themes/current/editor?context=apps" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <span>Theme Editor</span>
                    </a>
                </div>
            </div>

            <!-- Quick Jump Anchor Bar -->
            <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--card-border);">
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-right: 0.25rem;">Jump to:</span>
                    <a href="#contact-support" class="support-nav-pill">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                        Contact Support
                    </a>
                    <a href="#getting-started" class="support-nav-pill">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                        Getting Started
                    </a>
                    <a href="#wishlist-help" class="support-nav-pill">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        Wishlist Setup
                    </a>
                    <a href="#product-share-help" class="support-nav-pill">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                        Product Share
                    </a>
                    <a href="#faqs" class="support-nav-pill">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        FAQs
                    </a>
                    <a href="#troubleshooting" class="support-nav-pill">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                        Troubleshooting
                    </a>
                </div>
            </div>
        </div>

        <!-- Section B: Contact Support & Store Diagnostics -->
        <div id="contact-support" class="support-grid-2col">
            <!-- Official Contact Support Card -->
            <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.625rem; margin-bottom: 0.5rem;">
                        <div style="width: 36px; height: 36px; border-radius: 8px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <div>
                            <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0;">
                                Contact Official Support
                            </h3>
                            <p style="font-size: 0.8125rem; color: var(--text-subtle); margin: 0.15rem 0 0 0;">
                                Direct assistance for technical integrations, theme styling, and storefront issues.
                            </p>
                        </div>
                    </div>

                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin: 1rem 0;">
                        Encountering styling conflicts with your active Shopify theme, or need assistance configuring custom icons? Reach out to our technical team directly.
                    </p>

                    <!-- Official Email Section -->
                    <div style="background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-bottom: 1rem;">
                        <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                            Official Support Channel
                        </div>

                        @php
                            $supportEmailAddress = $supportEmail ?: (config('shopify.support_email') ?: 'support@socialnest.io');
                        @endphp

                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                            <div>
                                <a href="mailto:{{ $supportEmailAddress }}?subject={{ rawurlencode('SocialNest Support Request - ' . $shop) }}" style="font-size: 1.05rem; font-weight: 700; color: var(--primary); text-decoration: none; word-break: break-all;">
                                    {{ $supportEmailAddress }}
                                </a>
                                <div style="font-size: 0.75rem; color: var(--text-subtle); margin-top: 0.2rem;">
                                    Click to compose an email from your default client
                                </div>
                            </div>
                            <a href="mailto:{{ $supportEmailAddress }}?subject={{ rawurlencode('SocialNest Support Request - ' . $shop) }}" class="btn btn-primary btn-sm">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <span>Send Email</span>
                            </a>
                        </div>
                    </div>

                    <div style="display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.775rem; color: var(--text-subtle); line-height: 1.45;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--primary); flex-shrink: 0; margin-top: 0.1rem;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                        <span>
                            <strong>Ticket Response Policy:</strong> Support inquiries are reviewed and answered in the order received. For expedited diagnosis, please mention your active store domain (<code class="support-code-pill" style="font-size: 0.8em;">{{ $shop }}</code>) and current theme name in your message.
                        </span>
                    </div>
                </div>
            </div>

            <!-- Store & App Diagnostics Panel -->
            <div class="card" style="background: var(--surface-bg); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            Store Diagnostics
                        </h4>
                        <span class="badge-live">
                            <span class="badge-live-dot"></span>
                            Active Store Context
                        </span>
                    </div>
                    <p style="font-size: 0.775rem; color: var(--text-subtle); line-height: 1.5; margin-bottom: 1rem;">
                        Reference details when filing a support ticket or verifying storefront compatibility.
                    </p>

                    <div style="background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 0.75rem 1rem;">
                        <div class="diag-row">
                            <span class="diag-label">Store Domain</span>
                            <span class="diag-val">{{ $shop }}</span>
                        </div>
                        <div class="diag-row">
                            <span class="diag-label">Theme Architecture</span>
                            <span class="diag-val">Shopify Online Store 2.0</span>
                        </div>
                        <div class="diag-row">
                            <span class="diag-label">App Status</span>
                            <span class="diag-val" style="color: #047857; background: #ecfdf5; border-color: #a7f3d0;">Active & Installed</span>
                        </div>
                        <div class="diag-row">
                            <span class="diag-label">Theme Extension</span>
                            <span class="diag-val">Native App Embed & Blocks</span>
                        </div>
                        <div class="diag-row">
                            <span class="diag-label">Wishlist Route</span>
                            <span class="diag-val">/pages/wishlist</span>
                        </div>
                        <div class="diag-row">
                            <span class="diag-label">Response Time</span>
                            <span class="diag-val">Within 24 Hours</span>
                        </div>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--card-border); font-size: 0.75rem; color: var(--text-subtle); display: flex; align-items: center; gap: 0.4rem;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>SocialNest is fully integrated and optimized for your Shopify Online Store 2.0 theme.</span>
                </div>
            </div>
        </div>

        <!-- Section C: Getting Started Guide -->
        <div id="getting-started" class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.25rem 0;">
                        Storefront Theme Activation Guide
                    </h3>
                    <p style="font-size: 0.8125rem; color: var(--text-subtle); margin: 0;">
                        Follow these 3 quick steps to enable your floating social icons on Shopify Online Store 2.0.
                    </p>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--primary); background: var(--primary-light); border: 1px solid var(--primary-border); padding: 0.25rem 0.625rem; border-radius: 9999px;">
                    3-Step Quickstart
                </span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                <!-- Step 1 -->
                <div style="display: flex; gap: 1rem; align-items: flex-start; padding: 1rem; background: var(--surface-bg); border-radius: var(--radius-md); border: 1px solid var(--card-border);">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #ffffff; font-weight: 800; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        1
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Add Your Social Channels</h4>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.5rem;">
                            Go to <a href="{{ route('shopify.social-links.index', ['shop' => $shop, 'host' => $host]) }}" style="color: var(--primary); font-weight: 600;">My Social Links</a> and add your Instagram, WhatsApp, Facebook, TikTok, or YouTube links. Make sure they are toggled to <strong>Active</strong>. You can drag and drop icons to set your exact display order.
                        </p>
                        <a href="{{ route('shopify.social-links.index', ['shop' => $shop, 'host' => $host]) }}" class="btn btn-secondary btn-sm">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            <span>Manage My Social Links</span>
                        </a>
                    </div>
                </div>

                <!-- Step 2 -->
                <div style="display: flex; gap: 1rem; align-items: flex-start; padding: 1rem; background: var(--surface-bg); border-radius: var(--radius-md); border: 1px solid var(--card-border);">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #ffffff; font-weight: 800; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        2
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Open Theme Editor App Embeds</h4>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.5rem;">
                            Open your Shopify Admin, click <strong>Online Store &rarr; Themes &rarr; Customize</strong>. In the left panel, click the <strong>App Embeds</strong> tab (third icon from top).
                        </p>
                        <div style="font-size: 0.775rem; color: var(--text-subtle);">
                            Tip: SocialNest uses a native Theme App Extension. It runs directly inside Shopify's CDN without modifying your core theme template files.
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div style="display: flex; gap: 1rem; align-items: flex-start; padding: 1rem; background: var(--surface-bg); border-radius: var(--radius-md); border: 1px solid var(--card-border);">
                    <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: #ffffff; font-weight: 800; font-size: 0.875rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        3
                    </div>
                    <div style="flex: 1;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Enable & Save</h4>
                        <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.5rem;">
                            Toggle <strong>SocialNest Widget</strong> to ON, then click <strong>Save</strong> in the upper right. Your floating social dock is now live on your store!
                        </p>
                        <div style="font-size: 0.775rem; color: var(--text-subtle);">
                            To customize colors, icon shapes, or dock positioning, visit <a href="{{ route('shopify.settings.index', ['shop' => $shop, 'host' => $host]) }}" style="color: var(--primary); font-weight: 600;">Storefront Settings</a> at any time.
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--card-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <div style="font-size: 0.8125rem; color: var(--text-muted);">
                    Need direct access to Shopify's Theme Customizer?
                </div>
                <a href="https://{{ $shop }}/admin/themes/current/editor?context=apps" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span>Open Shopify Theme Editor (App Embeds)</span>
                </a>
            </div>
        </div>

        <!-- Section D: Wishlist Setup & Help -->
        <div id="wishlist-help">
            <!-- Wishlist Header -->
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
                            <span style="font-size: 0.7rem; font-weight: 700; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.15rem 0.5rem; border-radius: 9999px;">GraphQL &amp; Deep Link</span>
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
                        <a href="{{ route('shopify.wishlist.setup', ['shop' => $shop, 'host' => $host]) }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                            <span>Open Theme Editor with Wishlist Block</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Important Notes (Full Width) -->
            <div class="card" style="margin-top: 1.5rem; background: var(--primary-light); border: 1px solid var(--primary-border);">
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
        </div>

        <!-- Section E: Product Share Help -->
        <div id="product-share-help" class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 0.625rem;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    </div>
                    <div>
                        <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0;">
                            Product Page Sharing & Floating Dock
                        </h3>
                        <p style="font-size: 0.8125rem; color: var(--text-subtle); margin: 0.15rem 0 0 0;">
                            Guide to enabling, configuring, and styling product sharing across your store.
                        </p>
                    </div>
                </div>
                <a href="{{ route('shopify.settings.index', ['shop' => $shop, 'host' => $host]) }}" class="btn btn-secondary btn-sm">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Open Storefront Settings</span>
                </a>
            </div>

            <div class="support-grid-3col">
                <!-- Card 1: Toggle & Control -->
                <div style="background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 1.25rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                        Control
                    </div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                        Enabling & Disabling Product Share
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.75rem;">
                        In <a href="{{ route('shopify.settings.index', ['shop' => $shop, 'host' => $host]) }}" style="color: var(--primary); font-weight: 600;">Storefront Settings</a>, locate the <strong>Product Page Sharing</strong> section. Toggle the switch to ON or OFF and click Save Settings.
                    </p>
                    <div style="font-size: 0.75rem; color: var(--text-subtle); line-height: 1.4;">
                        When OFF, product share buttons are completely unmounted from product templates with zero leftover DOM footprints.
                    </div>
                </div>

                <!-- Card 2: Floating vs Inline -->
                <div style="background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 1.25rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                        Placement
                    </div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                        Floating Dock vs. Inline Share
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.75rem;">
                        <strong>Floating Social Dock:</strong> Persistent across all store pages (Home, Collection, Pages) connecting customers directly to your brand community.
                    </p>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        <strong>Inline Product Share:</strong> Placed specifically on product pages, encouraging shoppers to send the product link to friends via WhatsApp, Facebook, or X.
                    </p>
                </div>

                <!-- Card 3: Supported Channels -->
                <div style="background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 1.25rem;">
                    <div style="font-size: 0.75rem; font-weight: 700; color: var(--primary); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.35rem;">
                        Channels
                    </div>
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.5rem;">
                        Supported Sharing Platforms
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.5rem;">
                        SocialNest supports instant sharing to all major messaging and social networks:
                    </p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.35rem; font-size: 0.75rem;">
                        <span class="support-code-pill">WhatsApp</span>
                        <span class="support-code-pill">Facebook</span>
                        <span class="support-code-pill">X (Twitter)</span>
                        <span class="support-code-pill">Pinterest</span>
                        <span class="support-code-pill">LinkedIn</span>
                        <span class="support-code-pill">Telegram</span>
                        <span class="support-code-pill">Messenger</span>
                        <span class="support-code-pill">Native Web Share</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section F: Frequently Asked Questions -->
        <div id="faqs" class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.25rem 0;">
                        Frequently Asked Questions
                    </h3>
                    <p style="font-size: 0.8125rem; color: var(--text-subtle); margin: 0;">
                        Common questions regarding widget performance, theme compatibility, and customer privacy.
                    </p>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-subtle); background: var(--surface-bg); border: 1px solid var(--card-border); padding: 0.25rem 0.625rem; border-radius: 9999px;">
                    Knowledge Base
                </span>
            </div>

            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div class="faq-card">
                    <div class="faq-question">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="flex-shrink: 0; margin-top: 0.1rem;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span>How does WhatsApp sharing work on product pages?</span>
                    </div>
                    <div class="faq-answer">
                        When shoppers click the WhatsApp icon, it opens WhatsApp Web or mobile app with your configured message and product link ready to send to friends or support contacts. The message automatically includes the product title and canonical product URL.
                    </div>
                </div>

                <div class="faq-card">
                    <div class="faq-question">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="flex-shrink: 0; margin-top: 0.1rem;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" cy="17" x2="12.01" y2="17"/></svg>
                        <span>Will the icons slow down my storefront?</span>
                    </div>
                    <div class="faq-answer">
                        No. SocialNest uses lightweight vector SVGs with zero bloated JavaScript dependencies. Configuration is cached for maximum speed and instant rendering. All assets are loaded asynchronously directly from Shopify's high-speed global CDN.
                    </div>
                </div>

                <div class="faq-card">
                    <div class="faq-question">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="flex-shrink: 0; margin-top: 0.1rem;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" cy="17" x2="12.01" y2="17"/></svg>
                        <span>Can I reposition the icons if they block my chat widget?</span>
                    </div>
                    <div class="faq-answer">
                        Yes. In <a href="{{ route('shopify.settings.index', ['shop' => $shop, 'host' => $host]) }}" style="color: var(--primary); font-weight: 600;">Storefront Settings</a>, you can switch position between Bottom Right, Bottom Left, Vertical Right, or Vertical Left, or configure Custom Coordinates with custom pixel offsets so it never collides with Shopify Inbox, Gorgias, or other live chat widgets.
                    </div>
                </div>

                <div class="faq-card">
                    <div class="faq-question">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="flex-shrink: 0; margin-top: 0.1rem;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" cy="17" x2="12.01" y2="17"/></svg>
                        <span>Do I need to edit any theme Liquid code or template files?</span>
                    </div>
                    <div class="faq-answer">
                        Never. SocialNest is engineered 100% on Shopify's Online Store 2.0 Theme App Extension standard. Everything renders cleanly via App Embeds and App Blocks without touching or altering your theme's underlying source code.
                    </div>
                </div>

                <div class="faq-card">
                    <div class="faq-question">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" style="flex-shrink: 0; margin-top: 0.1rem;"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" cy="17" x2="12.01" y2="17"/></svg>
                        <span>How does guest wishlist data work if a customer is not logged in?</span>
                    </div>
                    <div class="faq-answer">
                        Guest shoppers can save products immediately. Wishlist items are stored safely in the shopper's local browser storage. When the customer signs into their Shopify store account, SocialNest automatically synchronizes and merges their guest items to their customer account in the cloud.
                    </div>
                </div>
            </div>
        </div>

        <!-- Section G: Troubleshooting & Self-Help Guide -->
        <div id="troubleshooting" class="card">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
                <div>
                    <h3 style="font-size: 1.15rem; font-weight: 800; color: var(--text-main); margin: 0 0 0.25rem 0;">
                        Troubleshooting & Self-Help Guide
                    </h3>
                    <p style="font-size: 0.8125rem; color: var(--text-subtle); margin: 0;">
                        Actionable diagnostic checklists to resolve common storefront display and configuration issues.
                    </p>
                </div>
                <span style="font-size: 0.75rem; font-weight: 700; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; padding: 0.25rem 0.625rem; border-radius: 9999px;">
                    Diagnostic Checklists
                </span>
            </div>

            <div class="support-grid-2col">
                <!-- Checklist 1: Dock not appearing -->
                <div style="background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 1.25rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.35rem 0; display: flex; align-items: center; gap: 0.4rem;">
                        <span style="color: #ef4444;">⚠️</span>
                        Floating Social Dock Is Not Showing
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.5rem;">
                        If the floating social dock does not appear on your storefront pages, verify the following:
                    </p>
                    <div class="troubleshoot-list">
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">1</span>
                            <span>Verify you have at least one link toggled to <strong>Active</strong> in <a href="{{ route('shopify.social-links.index', ['shop' => $shop, 'host' => $host]) }}" style="color: var(--primary); font-weight: 600;">My Social Links</a>. Empty docks are hidden automatically.</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">2</span>
                            <span>Open Shopify Theme Editor &rarr; <strong>App Embeds</strong> tab &rarr; Verify <strong>SocialNest Widget</strong> is enabled (ON).</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">3</span>
                            <span>Click <strong>Save</strong> in the upper right corner of the Theme Editor.</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">4</span>
                            <span>Test in an Incognito / Private window to rule out stale browser cache.</span>
                        </div>
                    </div>
                </div>

                <!-- Checklist 2: Wishlist 404 -->
                <div style="background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 1.25rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.35rem 0; display: flex; align-items: center; gap: 0.4rem;">
                        <span style="color: #ef4444;">⚠️</span>
                        Wishlist Page Shows 404 Not Found
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.5rem;">
                        If navigating to <code class="support-code-pill">/pages/wishlist</code> returns a 404 page:
                    </p>
                    <div class="troubleshoot-list">
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">1</span>
                            <span>Click the <strong>Auto-Setup Wishlist</strong> button above to verify or create the page automatically.</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">2</span>
                            <span>Open Shopify Admin &rarr; <strong>Online Store &rarr; Pages</strong> and verify a page titled "Wishlist" exists with URL handle <code class="support-code-pill">wishlist</code>.</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">3</span>
                            <span>Ensure the page visibility setting is marked as <strong>Visible</strong>, not Hidden.</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">4</span>
                            <span>Ensure the <strong>SocialNest Wishlist</strong> App Block is added to the page section in Theme Customizer.</span>
                        </div>
                    </div>
                </div>

                <!-- Checklist 3: Settings changes not appearing -->
                <div style="background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 1.25rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.35rem 0; display: flex; align-items: center; gap: 0.4rem;">
                        <span style="color: #3b82f6;">ℹ️</span>
                        Storefront Changes Do Not Update Instantly
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.5rem;">
                        If colors, shapes, or positions changed in SocialNest do not update on your live store:
                    </p>
                    <div class="troubleshoot-list">
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">1</span>
                            <span>Ensure you clicked <strong>Save Settings</strong> at the bottom of the Storefront Settings view.</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">2</span>
                            <span>Shopify caches storefront API configurations for high performance; allow up to 30–60 seconds for CDN cache invalidation.</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">3</span>
                            <span>Perform a hard refresh (Ctrl+F5 or Cmd+Shift+R) on the storefront.</span>
                        </div>
                    </div>
                </div>

                <!-- Checklist 4: Chat widget collision -->
                <div style="background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 1.25rem;">
                    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.35rem 0; display: flex; align-items: center; gap: 0.4rem;">
                        <span style="color: #3b82f6;">ℹ️</span>
                        Dock Collides With Mobile Chat or Cart Buttons
                    </h4>
                    <p style="font-size: 0.8125rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 0.5rem;">
                        To prevent floating social icons from covering sticky checkout or customer service widgets:
                    </p>
                    <div class="troubleshoot-list">
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">1</span>
                            <span>Navigate to <a href="{{ route('shopify.settings.index', ['shop' => $shop, 'host' => $host]) }}" style="color: var(--primary); font-weight: 600;">Storefront Settings</a>.</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">2</span>
                            <span>Select <strong>Bottom Left</strong> or <strong>Vertical Left</strong> to move the dock to the opposite side of your chat button.</span>
                        </div>
                        <div class="troubleshoot-item">
                            <span class="troubleshoot-bullet">3</span>
                            <span>Alternatively, choose <strong>Custom Coordinates</strong> and enter custom pixel offsets for desktop and mobile displays.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Client-Side Automated Wishlist Setup Script -->
    <script>
        (function() {
            'use strict';

            let isRunning = false;

            async function runAutomatedWishlistSetup() {
                if (isRunning) return;

                const btn = document.getElementById('btn-auto-setup-wishlist');
                const text = document.getElementById('text-auto-setup');
                const status = document.getElementById('status-auto-setup');

                if (!btn) return;
                isRunning = true;
                btn.disabled = true;
                btn.style.opacity = '0.7';
                text.textContent = 'Verifying page & preparing Theme Editor...';
                if (status) {
                    status.style.display = 'block';
                    status.style.color = 'var(--text-subtle)';
                    status.textContent = 'Connecting to Shopify Admin API...';
                }

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const fetchFn = typeof window.shopifyAuthenticatedFetch === 'function'
                        ? window.shopifyAuthenticatedFetch
                        : fetch;

                    const response = await fetchFn('{{ route('shopify.wishlist.setup') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ target: 'mainSection', preview_path: '/pages/wishlist' })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        text.textContent = 'Opening Theme Editor...';
                        if (status) {
                            if (data.requires_reauth && data.reauth_url) {
                                status.style.color = '#b45309';
                                status.innerHTML = '⚠️ Theme Editor opened in new tab! To enable automated page creation, <a href="' + data.reauth_url + '" target="_top" style="color: var(--primary); font-weight: 700; text-decoration: underline;">approve updated page permissions</a>, or verify <code class="support-code-pill">/pages/wishlist</code> in Pages.';
                            } else {
                                status.style.color = '#047857';
                                status.textContent = data.message + ' Opening Theme Editor in a new tab...';
                            }
                        }
                        if (data.deep_link_url) {
                            let targetUrl = data.deep_link_url;
                            try {
                                const parsedUrl = new URL(targetUrl, window.location.origin);
                                if (parsedUrl.searchParams.get('template') === 'page') {
                                    parsedUrl.searchParams.delete('template');
                                }
                                const pageHandle = (data.page && data.page.handle) ? data.page.handle : 'wishlist';
                                parsedUrl.searchParams.set('previewPath', '/pages/' + pageHandle);
                                targetUrl = parsedUrl.toString();
                            } catch (urlError) {
                                if (targetUrl && !targetUrl.includes('previewPath=')) {
                                    const glue = targetUrl.includes('?') ? '&' : '?';
                                    targetUrl += glue + 'previewPath=%2Fpages%2Fwishlist';
                                }
                            }
                            window.open(targetUrl, '_blank', 'noopener,noreferrer');
                        }
                        setTimeout(function() {
                            isRunning = false;
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            text.textContent = 'Auto-Setup Wishlist & Open Theme Editor';
                        }, 4000);
                    } else {
                        throw new Error(data.message || 'Failed to complete wishlist setup.');
                    }
                } catch (err) {
                    isRunning = false;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    text.textContent = 'Auto-Setup Wishlist & Open Theme Editor';
                    if (status) {
                        status.style.color = '#dc2626';
                        status.textContent = err.message || 'Error executing setup.';
                    }
                    AppNotice.error(err.message || 'Unable to complete automated setup. Please use the manual steps below.', 'Automated Setup Failed');
                }
            }

            // Expose globally for inline onclick handler
            window.runAutomatedWishlistSetup = runAutomatedWishlistSetup;

            // Safe Teardown for Client-Side Authenticated Navigation
            function teardownSupportPage() {
                isRunning = false;
                document.removeEventListener('shopify:before-swap', teardownSupportPage);
            }
            document.addEventListener('shopify:before-swap', teardownSupportPage);
        })();
    </script>
@endsection
