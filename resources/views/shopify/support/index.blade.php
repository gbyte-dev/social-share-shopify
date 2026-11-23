@extends('layouts.shopify')

@section('title', 'Help & Support - ' . $shop)
@section('header_title', 'Help & Support')
@section('header_subtitle', ' Activate your floating social dock for '.$shop)

@section('content')
    <style>
        .support-wrap {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            max-width: 1360px;
            margin: 0 auto;
        }

        .support-hero {
            background: linear-gradient(125deg, rgba(79, 70, 229, 0.06) 0%, rgba(15, 23, 42, 0.02) 55%, var(--card-bg) 100%);
            border: 1px solid var(--primary-border);
        }

        .support-jump-bar {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
            margin-top: 1rem;
            padding-top: 0.9rem;
            border-top: 1px solid var(--card-border);
        }

        .support-jump-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.7rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-secondary);
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .support-jump-pill:hover {
            color: var(--primary);
            background: var(--primary-light);
            border-color: var(--primary-border);
        }

        .support-grid-2 {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
            gap: 1.15rem;
            align-items: stretch;
        }

        .support-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (max-width: 960px) {
            .support-grid-2,
            .support-grid-3 {
                grid-template-columns: 1fr;
            }
        }

        .support-compact-card {
            padding: 1.15rem 1.25rem;
        }

        .support-section-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-bottom: 0.95rem;
        }

        .support-section-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--text-main);
            margin: 0;
            letter-spacing: -0.015em;
        }

        .support-section-sub {
            font-size: 0.78rem;
            color: var(--text-subtle);
            margin: 0.15rem 0 0 0;
        }

        .support-code {
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            padding: 0.08rem 0.38rem;
            border-radius: 4px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--primary);
        }

        .support-step-row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 0.9rem;
            border-radius: var(--radius-md);
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
        }

        .support-step-badge {
            width: 24px;
            height: 24px;
            border-radius: 9999px;
            background: var(--primary);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 0.05rem;
        }

        .support-diag-table {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            padding: 0.45rem 0.85rem;
        }

        .support-diag-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.42rem 0;
            border-bottom: 1px solid var(--card-border);
            font-size: 0.78rem;
        }

        .support-diag-row:last-child {
            border-bottom: none;
        }

        .support-diag-key {
            color: var(--text-subtle);
            font-weight: 500;
        }

        .support-diag-val {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--text-main);
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            padding: 0.1rem 0.4rem;
            border-radius: 4px;
        }

        .support-subpanel {
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            padding: 1rem;
        }

        .support-faq-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.85rem;
        }

        @media (max-width: 820px) {
            .support-faq-grid {
                grid-template-columns: 1fr;
            }
        }

        .support-faq-item {
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            padding: 0.9rem 1rem;
        }

        .support-faq-q {
            font-size: 0.86rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0 0 0.35rem 0;
        }

        .support-faq-a {
            font-size: 0.78rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin: 0;
        }

        .support-check-list {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            margin-top: 0.55rem;
        }

        .support-check-item {
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            font-size: 0.78rem;
            color: var(--text-secondary);
            line-height: 1.45;
        }

        .support-check-num {
            width: 17px;
            height: 17px;
            border-radius: 4px;
            background: var(--primary-light);
            color: var(--primary);
            font-size: 0.68rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }
    </style>

    <div class="support-wrap">
        <!-- Header & Quick Navigation -->
        <div class="card support-hero support-compact-card">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
                <div style="max-width: 780px;">
                    <div style="display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.2rem 0.6rem; background: var(--primary-light); color: var(--primary); border: 1px solid var(--primary-border); border-radius: 9999px; font-size: 0.72rem; font-weight: 700; margin-bottom: 0.5rem;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        Socialvire Help Center
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <a href="#contact-support" class="btn btn-primary btn-sm">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <span>Contact Support</span>
                    </a>
                    <a href="https://{{ $shop }}/admin/themes/current/editor?context=apps" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <span>Theme Editor</span>
                    </a>
                </div>
            </div>

            <div class="support-jump-bar">
                <span style="font-size: 0.7rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.05em; margin-right: 0.2rem;">Jump:</span>
                <a href="#contact-support" class="support-jump-pill">Contact &amp; Diagnostics</a>
                <a href="#getting-started" class="support-jump-pill">Theme Activation</a>
                <a href="#wishlist-help" class="support-jump-pill">1-Click Wishlist</a>
                <a href="#product-share-help" class="support-jump-pill">Product Share</a>
                <a href="#faqs" class="support-jump-pill">FAQs</a>
                <a href="#troubleshooting" class="support-jump-pill">Troubleshooting</a>
            </div>
        </div>

        <!-- Contact Support & Store Diagnostics -->
        <div id="contact-support" class="support-grid-2">
            <div class="card support-compact-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.6rem;">
                        <div style="width: 34px; height: 34px; border-radius: 8px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="support-section-title">Contact Official Support</h3>
                            <p class="support-section-sub">Help with theme styling, app embeds, or storefront behavior.</p>
                        </div>
                    </div>

                    @php
                        $supportEmailAddress = $supportEmail ?: (config('shopify.support_email') ?: 'support@socialnest.io');
                    @endphp

                    <div style="background: var(--surface-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin: 0.75rem 0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.65rem;">
                        <div>
                            <div style="font-size: 0.7rem; font-weight: 700; color: var(--text-subtle); text-transform: uppercase; letter-spacing: 0.04em;">Direct Support Email</div>
                            <a href="mailto:{{ $supportEmailAddress }}?subject={{ rawurlencode('Socialvire Support Request - ' . $shop) }}" style="font-size: 0.98rem; font-weight: 700; color: var(--primary); text-decoration: none; word-break: break-all;">
                                {{ $supportEmailAddress }}
                            </a>
                        </div>
                        <a href="mailto:{{ $supportEmailAddress }}?subject={{ rawurlencode('Socialvire Support Request - ' . $shop) }}" class="btn btn-primary btn-sm">
                            <span>Send Email</span>
                        </a>
                    </div>
                </div>

                <div style="font-size: 0.75rem; color: var(--text-subtle); line-height: 1.45;">
                    Include <span class="support-code">{{ $shop }}</span> and your active theme name when emailing. Replies are sent within 24 hours.
                </div>
            </div>

            <div class="card support-compact-card" style="background: var(--surface-bg); display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
                        <h4 style="font-size: 0.92rem; font-weight: 800; color: var(--text-main); margin: 0;">Store Diagnostics</h4>
                        <span class="badge-live">
                            <span class="badge-live-dot"></span>
                            Active Store
                        </span>
                    </div>

                    <div class="support-diag-table">
                        <div class="support-diag-row">
                            <span class="support-diag-key">Store Domain</span>
                            <span class="support-diag-val">{{ $shop }}</span>
                        </div>
                        <div class="support-diag-row">
                            <span class="support-diag-key">Architecture</span>
                            <span class="support-diag-val">Online Store 2.0</span>
                        </div>
                        <div class="support-diag-row">
                            <span class="support-diag-key">Extension Type</span>
                            <span class="support-diag-val">App Embed &amp; Blocks</span>
                        </div>
                        <div class="support-diag-row">
                            <span class="support-diag-key">Wishlist Path</span>
                            <span class="support-diag-val">/pages/wishlist</span>
                        </div>
                        <div class="support-diag-row">
                            <span class="support-diag-key">Response SLA</span>
                            <span class="support-diag-val">Within 24 Hours</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Storefront Theme Activation Guide -->
        <div id="getting-started" class="card support-compact-card">
            <div class="support-section-head">
                <div>
                    <h3 class="support-section-title">Storefront Theme Activation</h3>
                    <p class="support-section-sub">Enable your floating social dock in 3 quick steps without editing Liquid files.</p>
                </div>
                <a href="https://{{ $shop }}/admin/themes/current/editor?context=apps" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm">
                    <span>Open App Embeds</span>
                </a>
            </div>

            <div class="support-grid-3">
                <div class="support-step-row">
                    <span class="support-step-badge">1</span>
                    <div style="font-size: 0.79rem; color: var(--text-muted); line-height: 1.5;">
                        <strong style="color: var(--text-main); display: block; margin-bottom: 0.15rem;">Add Active Links</strong>
                        Add and toggle links to <strong>Active</strong> in <a href="{{ route('shopify.social-links.index', ['shop' => $shop, 'host' => $host]) }}" style="color: var(--primary); font-weight: 600;">My Social Links</a>, then arrange their display order.
                    </div>
                </div>

                <div class="support-step-row">
                    <span class="support-step-badge">2</span>
                    <div style="font-size: 0.79rem; color: var(--text-muted); line-height: 1.5;">
                        <strong style="color: var(--text-main); display: block; margin-bottom: 0.15rem;">Open App Embeds</strong>
                        In <strong>Online Store &rarr; Themes &rarr; Customize</strong>, open the <strong>App Embeds</strong> tab in the left sidebar.
                    </div>
                </div>

                <div class="support-step-row">
                    <span class="support-step-badge">3</span>
                    <div style="font-size: 0.79rem; color: var(--text-muted); line-height: 1.5;">
                        <strong style="color: var(--text-main); display: block; margin-bottom: 0.15rem;">Toggle ON &amp; Save</strong>
                        Enable <strong>Socialvire Widget</strong> and click <strong>Save</strong>. Style options live in <a href="{{ route('shopify.settings.index', ['shop' => $shop, 'host' => $host]) }}" style="color: var(--primary); font-weight: 600;">Storefront Settings</a>.
                    </div>
                </div>
            </div>
        </div>

        <!-- 1-Click Automated Wishlist Setup -->
        <div id="wishlist-help" class="card support-compact-card" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.05) 0%, rgba(245, 243, 255, 0.75) 100%); border: 1.5px solid var(--primary-border);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: flex; align-items: center; gap: 0.45rem; flex-wrap: wrap; margin-bottom: 0.35rem;">
                        <h3 class="support-section-title">1-Click Wishlist Setup</h3>
                        <span style="font-size: 0.7rem; font-weight: 700; color: #047857; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 0.12rem 0.5rem; border-radius: 9999px;">Automated Page + Deep Link</span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted); line-height: 1.5; margin: 0 0 0.55rem 0;">
                        Checks or creates <span class="support-code">/pages/wishlist</span> via Shopify GraphQL without overwriting existing content, then opens the Theme Editor targeting the <strong>Socialvire Wishlist</strong> block in your main section.
                    </p>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.9rem; font-size: 0.75rem; color: var(--text-subtle);">
                        <span>• Preserves existing <span class="support-code">wishlist</span> page</span>
                        <span>• Add block inside main content (not header/footer)</span>
                        <span>• Guest saves use <span class="support-code">localStorage</span> &amp; sync on login</span>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.35rem;">
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; justify-content: flex-end;">
                        <button type="button" id="btn-auto-setup-wishlist" onclick="runAutomatedWishlistSetup()" class="btn btn-primary" style="padding: 0.6rem 1.15rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.45rem; cursor: pointer;">
                            <svg id="icon-auto-setup" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            <span id="text-auto-setup">Auto-Setup Wishlist &amp; Open Theme Editor</span>
                        </button>
                        <a href="{{ route('shopify.wishlist.setup', ['shop' => $shop, 'host' => $host]) }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center;">
                            <span>Direct Editor Link</span>
                        </a>
                    </div>
                    <span id="status-auto-setup" style="font-size: 0.75rem; color: var(--text-subtle); display: none;"></span>
                </div>
            </div>
        </div>

        <!-- Product Page Sharing & Floating Dock -->
        <div id="product-share-help" class="card support-compact-card">
            <div class="support-section-head">
                <div>
                    <h3 class="support-section-title">Product Page Sharing &amp; Floating Dock</h3>
                    <p class="support-section-sub">Control inline product share buttons and choose where your floating dock sits.</p>
                </div>
                <a href="{{ route('shopify.settings.index', ['shop' => $shop, 'host' => $host]) }}" class="btn btn-secondary btn-sm">
                    <span>Storefront Settings</span>
                </a>
            </div>

            <div class="support-grid-3">
                <div class="support-subpanel">
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--primary); text-transform: uppercase; margin-bottom: 0.25rem;">Toggle</div>
                    <h4 style="font-size: 0.88rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.35rem 0;">Enable or Disable</h4>
                    <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        Toggle <strong>Product Page Sharing</strong> in Storefront Settings and save. Turning it off removes share buttons cleanly with zero leftover DOM nodes.
                    </p>
                </div>

                <div class="support-subpanel">
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--primary); text-transform: uppercase; margin-bottom: 0.25rem;">Placement</div>
                    <h4 style="font-size: 0.88rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.35rem 0;">Dock vs. Inline Share</h4>
                    <p style="font-size: 0.78rem; color: var(--text-muted); line-height: 1.5; margin: 0;">
                        <strong>Floating Dock</strong> stays across store pages for brand links; <strong>Inline Share</strong> appears on product pages with the product title and link.
                    </p>
                </div>

                <div class="support-subpanel">
                    <div style="font-size: 0.7rem; font-weight: 700; color: var(--primary); text-transform: uppercase; margin-bottom: 0.25rem;">Channels</div>
                    <h4 style="font-size: 0.88rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.4rem 0;">Supported Networks</h4>
                    <div style="display: flex; flex-wrap: wrap; gap: 0.3rem;">
                        <span class="support-code">WhatsApp</span>
                        <span class="support-code">Facebook</span>
                        <span class="support-code">X</span>
                        <span class="support-code">Pinterest</span>
                        <span class="support-code">LinkedIn</span>
                        <span class="support-code">Telegram</span>
                        <span class="support-code">Messenger</span>
                        <span class="support-code">Web Share</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Frequently Asked Questions -->
        <div id="faqs" class="card support-compact-card">
            <div class="support-section-head">
                <div>
                    <h3 class="support-section-title">Frequently Asked Questions</h3>
                    <p class="support-section-sub">Quick answers on storefront speed, chat overlap, guest wishlists, and theme safety.</p>
                </div>
            </div>

            <div class="support-faq-grid">
                <div class="support-faq-item">
                    <h4 class="support-faq-q">How does WhatsApp sharing work on product pages?</h4>
                    <p class="support-faq-a">Clicking WhatsApp opens WhatsApp Web or mobile with your configured text, product title, and canonical product URL pre-filled.</p>
                </div>

                <div class="support-faq-item">
                    <h4 class="support-faq-q">Will Socialvire slow down my storefront?</h4>
                    <p class="support-faq-a">No. Icons render as lightweight inline SVGs with cached configuration and asynchronous asset delivery from Shopify's CDN.</p>
                </div>

                <div class="support-faq-item">
                    <h4 class="support-faq-q">How do I avoid overlapping live chat widgets?</h4>
                    <p class="support-faq-a">Switch dock placement to Bottom Left, Vertical Left, or Custom Coordinates with pixel offsets in Storefront Settings.</p>
                </div>

                <div class="support-faq-item">
                    <h4 class="support-faq-q">Do I need to edit Liquid or theme template files?</h4>
                    <p class="support-faq-a">No. Everything runs through Shopify Online Store 2.0 App Embeds and App Blocks without altering your theme source files.</p>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div id="troubleshooting" class="card support-compact-card">
            <div class="support-section-head">
                <div>
                    <h3 class="support-section-title">Troubleshooting Checklist</h3>
                    <p class="support-section-sub">Fast checks when a widget, wishlist page, or setting update does not appear.</p>
                </div>
            </div>

            <div class="support-grid-2">
                <div class="support-subpanel">
                    <h4 style="font-size: 0.88rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.25rem 0;">Floating Dock Not Showing</h4>
                    <div class="support-check-list">
                        <div class="support-check-item">
                            <span class="support-check-num">1</span>
                            <span>Confirm at least one link is <strong>Active</strong> in My Social Links (empty docks stay hidden).</span>
                        </div>
                        <div class="support-check-item">
                            <span class="support-check-num">2</span>
                            <span>Toggle <strong>Socialvire Widget</strong> ON under Theme Editor &rarr; <strong>App Embeds</strong> and click <strong>Save</strong>.</span>
                        </div>
                        <div class="support-check-item">
                            <span class="support-check-num">3</span>
                            <span>Check in a private window to bypass stale browser cache.</span>
                        </div>
                    </div>
                </div>

                <div class="support-subpanel">
                    <h4 style="font-size: 0.88rem; font-weight: 700; color: var(--text-main); margin: 0 0 0.25rem 0;">Wishlist 404 or Settings Delay</h4>
                    <div class="support-check-list">
                        <div class="support-check-item">
                            <span class="support-check-num">1</span>
                            <span>Run <strong>Auto-Setup Wishlist</strong> or confirm a visible page with handle <span class="support-code">wishlist</span> exists and includes the Wishlist block.</span>
                        </div>
                        <div class="support-check-item">
                            <span class="support-check-num">2</span>
                            <span>Confirm settings were saved and allow 30–60 seconds for CDN cache refresh, then hard-refresh (<span class="support-code">Ctrl+F5</span> / <span class="support-code">Cmd+Shift+R</span>).</span>
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
                                status.innerHTML = '⚠️ Theme Editor opened in new tab! To enable automated page creation, <a href="' + data.reauth_url + '" target="_top" style="color: var(--primary); font-weight: 700; text-decoration: underline;">approve updated page permissions</a>, or verify <code class="support-code">/pages/wishlist</code> in Pages.';
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