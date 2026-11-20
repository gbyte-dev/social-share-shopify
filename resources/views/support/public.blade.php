@extends('layouts.public')

@section('title', 'Support & Help Center | SocialNest')

@push('styles')
<style>
    .public-support-page {
        max-width: 1200px;
        margin: 0 auto;
        color: #0f172a;
    }

    /* Page Hero */
    .support-hero {
        position: relative;
        padding: 32px 0 28px;
        margin-bottom: 32px;
        border-bottom: 1px solid #e2e8f0;
    }

    .support-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        background: #eef2ff;
        color: #4f46e5;
        border: 1px solid #c7d2fe;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 12px;
    }

    .support-hero h1 {
        font-size: clamp(28px, 4vw, 40px);
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.03em;
        color: #0f172a;
        margin: 0 0 10px;
    }

    .support-hero p {
        max-width: 780px;
        font-size: 15px;
        line-height: 1.65;
        color: #475569;
        margin: 0;
    }

    /* Main Grid Layout: Sidebar Navigation + Content Area */
    .support-layout {
        display: grid;
        grid-template-columns: 260px minmax(0, 1fr);
        gap: 32px;
        align-items: start;
    }

    /* Sticky Sidebar Navigation */
    .support-sidebar {
        position: sticky;
        top: 92px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
    }

    .support-sidebar-title {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #64748b;
        margin: 0 0 14px 6px;
    }

    .support-sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .support-sidebar-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 10px;
        color: #475569;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: all 0.15s ease;
    }

    .support-sidebar-link svg {
        color: #64748b;
        transition: color 0.15s ease;
        flex-shrink: 0;
    }

    .support-sidebar-link:hover {
        background: #eef2ff;
        color: #4f46e5;
    }

    .support-sidebar-link:hover svg {
        color: #4f46e5;
    }

    /* Main Content Sections */
    .support-content {
        display: flex;
        flex-direction: column;
        gap: 28px;
        min-width: 0;
    }

    /* Support Cards */
    .support-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 28px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.025);
        scroll-margin-top: 100px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .support-card:hover {
        border-color: #cbd5e1;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }

    .support-card-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 18px;
        padding-bottom: 14px;
        border-bottom: 1px solid #f1f5f9;
    }

    .support-card-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        background: #eef2ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .support-card-header h2 {
        font-size: 18px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #0f172a;
        margin: 0;
    }

    .support-card-header p {
        font-size: 13px;
        color: #64748b;
        margin: 2px 0 0;
    }

    /* Contact Support Highlight Card */
    .support-card-featured {
        background: linear-gradient(135deg, rgba(79, 70, 229, 0.03) 0%, #ffffff 100%);
        border: 1.5px solid #c7d2fe;
    }

    .support-email-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        margin: 16px 0;
    }

    .support-email-address {
        font-size: 17px;
        font-weight: 700;
        color: #4f46e5;
        text-decoration: none;
        word-break: break-all;
    }

    .support-email-address:hover {
        text-decoration: underline;
    }

    .support-code-pill {
        display: inline-block;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        padding: 2px 7px;
        border-radius: 4px;
        font-weight: 700;
        color: #4f46e5;
        font-family: monospace;
        font-size: 0.85em;
    }

    .btn-support-primary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #4f46e5;
        color: #ffffff;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        padding: 9px 18px;
        border-radius: 8px;
        transition: background-color 0.15s ease, transform 0.15s ease;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.22);
    }

    .btn-support-primary:hover {
        background: #4338ca;
        transform: translateY(-1px);
        color: #ffffff;
    }

    .btn-support-secondary {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        color: #0f172a;
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        padding: 9px 18px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        transition: all 0.15s ease;
    }

    .btn-support-secondary:hover {
        background: #f8fafc;
        border-color: #94a3b8;
    }

    /* Multi-step Walkthrough Grid */
    .steps-grid {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .step-box {
        display: flex;
        gap: 14px;
        align-items: flex-start;
        padding: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }

    .step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #4f46e5;
        color: #ffffff;
        font-weight: 800;
        font-size: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .step-content h3 {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 4px;
    }

    .step-content p {
        font-size: 13px;
        color: #475569;
        line-height: 1.6;
        margin: 0;
    }

    /* 2-Column Subgrid */
    .support-subgrid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-top: 14px;
    }

    .subcard {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px;
    }

    .subcard-tag {
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #4f46e5;
        margin-bottom: 6px;
    }

    .subcard h3 {
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 6px;
    }

    .subcard p {
        font-size: 13px;
        color: #475569;
        line-height: 1.55;
        margin: 0;
    }

    /* FAQ Box */
    .public-faq-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .public-faq-item {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 18px 20px;
    }

    .public-faq-question {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 6px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .public-faq-question svg {
        color: #4f46e5;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .public-faq-answer {
        font-size: 13px;
        color: #475569;
        line-height: 1.6;
        margin: 0;
        padding-left: 26px;
    }

    /* Diagnostic Checklist */
    .troubleshoot-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 10px;
    }

    .troubleshoot-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        font-size: 13px;
        color: #334155;
        line-height: 1.5;
    }

    .troubleshoot-badge {
        width: 20px;
        height: 20px;
        border-radius: 5px;
        background: #eef2ff;
        color: #4f46e5;
        font-weight: 800;
        font-size: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-top: 1px;
    }

    /* System Details Table */
    .system-details-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 18px;
        margin-top: 14px;
    }

    .system-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        font-size: 12px;
        border-bottom: 1px solid #f1f5f9;
    }

    .system-row:last-child {
        border-bottom: none;
    }

    .system-row span:first-child {
        color: #64748b;
        font-weight: 500;
    }

    .system-row span:last-child {
        color: #0f172a;
        font-weight: 700;
    }

    /* Responsive */
    @media (max-width: 900px) {
        .support-layout {
            grid-template-columns: 1fr;
        }

        .support-sidebar {
            position: relative;
            top: 0;
        }

        .support-sidebar-nav {
            flex-direction: row;
            flex-wrap: wrap;
            gap: 6px;
        }

        .support-sidebar-link {
            font-size: 12px;
            padding: 8px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
    }
</style>
@endpush

@section('content')
<div class="public-support-page">
    <!-- Section A: Page Hero / Header -->
    <header class="support-hero">
        <div class="support-hero-badge">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            Help & Documentation
        </div>
        <h1>Help & Support</h1>
        <p>
            Welcome to the SocialNest Help Center. Explore step-by-step guides, technical documentation, and self-help troubleshooting to configure floating social channels, inline product sharing, and your dedicated wishlist page on Shopify Online Store 2.0.
        </p>
    </header>

    <div class="support-layout">
        <!-- Sticky Left Sidebar Navigation -->
        <aside class="support-sidebar">
            <div class="support-sidebar-title">Categories</div>
            <nav class="support-sidebar-nav">
                <a href="#contact-support" class="support-sidebar-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    Contact Support
                </a>
                <a href="#getting-started" class="support-sidebar-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    Getting Started
                </a>
                <a href="#wishlist-help" class="support-sidebar-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    Wishlist Setup
                </a>
                <a href="#product-share-help" class="support-sidebar-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    Product Share
                </a>
                <a href="#faqs" class="support-sidebar-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    FAQs
                </a>
                <a href="#troubleshooting" class="support-sidebar-link">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    Troubleshooting
                </a>
            </nav>
        </aside>

        <!-- Right Content Body -->
        <main class="support-content">
            <!-- Section B: Contact Support -->
            <section id="contact-support" class="support-card support-card-featured">
                <div class="support-card-header">
                    <div class="support-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                    </div>
                    <div>
                        <h2>Contact Official Support</h2>
                        <p>Have questions about theme compatibility, custom positioning, or advanced storefront settings?</p>
                    </div>
                </div>

                <p style="font-size: 13.5px; color: #475569; line-height: 1.6; margin: 0 0 14px;">
                    Whether you are an active merchant or exploring SocialNest for your Shopify storefront, our technical team is here to assist with setup verification, styling adjustments, and custom platform integrations.
                </p>

                @php
                    $supportEmailAddress = $supportEmail ?: (config('shopify.support_email') ?: 'support@socialnest.io');
                @endphp

                <div class="support-email-box">
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 4px;">
                            Official Support Email
                        </div>
                        <a href="mailto:{{ $supportEmailAddress }}?subject={{ rawurlencode('SocialNest Support Request') }}" class="support-email-address">
                            {{ $supportEmailAddress }}
                        </a>
                        <div style="font-size: 12px; color: #64748b; margin-top: 3px;">
                            Monitored channel for merchant inquiries and theme assistance
                        </div>
                    </div>
                    <a href="mailto:{{ $supportEmailAddress }}?subject={{ rawurlencode('SocialNest Support Request') }}" class="btn-support-primary">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        <span>Send Support Email</span>
                    </a>
                </div>

                <p style="font-size: 12.5px; color: #64748b; line-height: 1.5; margin: 0;">
                    <strong>Inquiry Guidelines:</strong> Support requests are reviewed in the order received. When submitting a request, please include your Shopify store domain (e.g. <code>store.myshopify.com</code>) and active theme name for faster diagnosis.
                </p>

                <!-- Technical Platform Details -->
                <div class="system-details-box">
                    <div class="system-row">
                        <span>Theme Architecture</span>
                        <span>Shopify Online Store 2.0</span>
                    </div>
                    <div class="system-row">
                        <span>Admin API Version</span>
                        <span>{{ config('shopify.api_version', '2026-10') }}</span>
                    </div>
                    <div class="system-row">
                        <span>Integration Type</span>
                        <span>Native Theme App Extension (Zero Code Injections)</span>
                    </div>
                </div>
            </section>

            <!-- Section C: Getting Started -->
            <section id="getting-started" class="support-card">
                <div class="support-card-header">
                    <div class="support-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </div>
                    <div>
                        <h2>Getting Started with SocialNest</h2>
                        <p>Complete 3-step guide to installing and activating floating social icons on Shopify Online Store 2.0.</p>
                    </div>
                </div>

                <div class="steps-grid">
                    <div class="step-box">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Installing SocialNest</h3>
                            <p>
                                Install SocialNest from the Shopify App Store. The application requests minimal required permissions (theme assets, app proxy, and content pages) and establishes secure OAuth authentication with your store.
                            </p>
                        </div>
                    </div>

                    <div class="step-box">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Connecting & Configuring Social Links</h3>
                            <p>
                                Inside your Shopify Admin, open SocialNest and navigate to <strong>My Social Links</strong>. Add your social profiles (Instagram, WhatsApp, TikTok, Facebook, YouTube, etc.), enter your handles or phone numbers, and toggle each link to <strong>Active</strong>. You can drag and drop icons to control their exact display order.
                            </p>
                        </div>
                    </div>

                    <div class="step-box">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Activating the Theme App Embed</h3>
                            <p>
                                In Shopify Admin, go to <strong>Online Store &rarr; Themes &rarr; Customize</strong>. In the left panel, click the <strong>App Embeds</strong> tab, toggle <strong>SocialNest Widget</strong> to ON, and click <strong>Save</strong> in the upper right. Your floating dock is now live across your storefront!
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section D: Wishlist Setup -->
            <section id="wishlist-help" class="support-card">
                <div class="support-card-header">
                    <div class="support-card-icon" style="background: #fee2e2; color: #ef4444;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    </div>
                    <div>
                        <h2>How to Set Up Wishlist</h2>
                        <p>Configure a dedicated storefront wishlist page for your customers without manual code editing.</p>
                    </div>
                </div>

                <div class="steps-grid">
                    <div class="step-box">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Create a Wishlist Page in Shopify</h3>
                            <p>
                                In Shopify Admin, navigate to <strong>Online Store &rarr; Pages</strong> and click <strong>Add Page</strong>. Set the title to <code class="support-code-pill">Wishlist</code> and ensure the URL handle is set to <code class="support-code-pill">wishlist</code> (resulting in the public URL <code class="support-code-pill">/pages/wishlist</code>). Save the page and make sure visibility is set to <strong>Visible</strong>.
                            </p>
                        </div>
                    </div>

                    <div class="step-box">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Add the SocialNest Wishlist App Block</h3>
                            <p>
                                Open <strong>Online Store &rarr; Themes &rarr; Customize</strong>. Use the top page selector to navigate to your Wishlist page. In the left template sidebar, click <strong>Add block</strong> or <strong>Add section</strong> within the main page content, select the <strong>SocialNest Wishlist</strong> app block, and click <strong>Save</strong>.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="subcard" style="margin-top: 16px; background: #f0fdf4; border-color: #bbf7d0;">
                    <div class="subcard-tag" style="color: #15803d;">Automated Setup Option</div>
                    <h3>One-Click Wishlist Setup Available in Admin</h3>
                    <p>
                        Merchants with SocialNest installed can use the <strong>1-Click Wishlist Setup</strong> button on their SocialNest Dashboard. It checks for the page via GraphQL and opens the Theme Editor with the block pre-staged in your main section.
                    </p>
                </div>
            </section>

            <!-- Section E: Product Share Help -->
            <section id="product-share-help" class="support-card">
                <div class="support-card-header">
                    <div class="support-card-icon" style="background: #e0e7ff; color: #4338ca;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    </div>
                    <div>
                        <h2>Product Page Sharing & Floating Dock</h2>
                        <p>Turn every product page into a viral sharing opportunity for your store shoppers.</p>
                    </div>
                </div>

                <div class="support-subgrid">
                    <div class="subcard">
                        <div class="subcard-tag">Control</div>
                        <h3>Enabling & Disabling Product Share</h3>
                        <p>
                            In SocialNest Storefront Settings, find the <strong>Product Page Sharing</strong> section. Toggle the switch to ON or OFF. When disabled, sharing buttons are completely unmounted from product templates.
                        </p>
                    </div>

                    <div class="subcard">
                        <div class="subcard-tag">Placement</div>
                        <h3>Floating Dock vs. Inline Share</h3>
                        <p>
                            The <strong>Floating Social Dock</strong> persists across all pages to connect customers to your brand channels. The <strong>Inline Share Button</strong> renders right under the Add to Cart button on product pages.
                        </p>
                    </div>

                    <div class="subcard">
                        <div class="subcard-tag">Platforms</div>
                        <h3>Supported Sharing Channels</h3>
                        <p style="margin-bottom: 8px;">
                            SocialNest supports instant 1-click sharing across all major networks:
                        </p>
                        <div style="display: flex; flex-wrap: wrap; gap: 4px;">
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
            </section>

            <!-- Section F: Frequently Asked Questions -->
            <section id="faqs" class="support-card">
                <div class="support-card-header">
                    <div class="support-card-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    </div>
                    <div>
                        <h2>Frequently Asked Questions</h2>
                        <p>Helpful answers to common technical, performance, and configuration questions.</p>
                    </div>
                </div>

                <div class="public-faq-list">
                    <div class="public-faq-item">
                        <div class="public-faq-question">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <span>How does WhatsApp sharing work on product pages?</span>
                        </div>
                        <p class="public-faq-answer">
                            When shoppers click the WhatsApp icon, it opens WhatsApp Web (on desktop) or the WhatsApp mobile app with your configured message and product link ready to send to friends. The message automatically includes the product title and canonical product URL.
                        </p>
                    </div>

                    <div class="public-faq-item">
                        <div class="public-faq-question">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" cy="17" x2="12.01" y2="17"/></svg>
                            <span>Will the icons slow down my storefront?</span>
                        </div>
                        <p class="public-faq-answer">
                            No. SocialNest uses lightweight vector SVGs with zero bloated JavaScript dependencies. Assets are loaded asynchronously directly from Shopify's high-speed global CDN with aggressive caching.
                        </p>
                    </div>

                    <div class="public-faq-item">
                        <div class="public-faq-question">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" cy="17" x2="12.01" y2="17"/></svg>
                            <span>Can I reposition the icons if they block my chat widget?</span>
                        </div>
                        <p class="public-faq-answer">
                            Yes. In Storefront Settings, you can switch position between Bottom Right, Bottom Left, Vertical Right, or Vertical Left, or configure Custom Coordinates with custom pixel offsets so it never collides with Shopify Inbox, Gorgias, or other live chat widgets.
                        </p>
                    </div>

                    <div class="public-faq-item">
                        <div class="public-faq-question">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" cy="17" x2="12.01" y2="17"/></svg>
                            <span>Do I need to edit any theme Liquid code or template files?</span>
                        </div>
                        <p class="public-faq-answer">
                            Never. SocialNest is engineered 100% on Shopify's Online Store 2.0 Theme App Extension standard. Everything renders cleanly via App Embeds and App Blocks without touching or altering your theme's underlying source code.
                        </p>
                    </div>

                    <div class="public-faq-item">
                        <div class="public-faq-question">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" cy="17" x2="12.01" y2="17"/></svg>
                            <span>How does guest wishlist data work if a customer is not logged in?</span>
                        </div>
                        <p class="public-faq-answer">
                            Guest shoppers can save products immediately. Wishlist items are stored safely in the shopper's local browser storage. When the customer signs into their Shopify store account, SocialNest automatically synchronizes and merges their guest items to their customer account in the cloud.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Section G: Troubleshooting -->
            <section id="troubleshooting" class="support-card">
                <div class="support-card-header">
                    <div class="support-card-icon" style="background: #fffbeb; color: #d97706;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    </div>
                    <div>
                        <h2>Troubleshooting & Self-Help Guide</h2>
                        <p>Actionable diagnostic steps to resolve common storefront display or configuration issues.</p>
                    </div>
                </div>

                <div class="support-subgrid">
                    <div class="subcard">
                        <h3 style="color: #b91c1c; font-size: 14px;">Floating Dock Is Not Appearing</h3>
                        <p style="font-size: 12px; color: #64748b; margin-bottom: 8px;">If icons don't show on your storefront:</p>
                        <div class="troubleshoot-list">
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">1</span>
                                <span>Verify at least one link is set to <strong>Active</strong> in My Social Links.</span>
                            </div>
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">2</span>
                                <span>In Shopify Theme Editor, ensure <strong>SocialNest Widget</strong> is enabled in App Embeds.</span>
                            </div>
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">3</span>
                                <span>Click <strong>Save</strong> in the upper right corner of the Theme Editor.</span>
                            </div>
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">4</span>
                                <span>Test in an Incognito / Private window to rule out browser caching.</span>
                            </div>
                        </div>
                    </div>

                    <div class="subcard">
                        <h3 style="color: #b91c1c; font-size: 14px;">Wishlist Page Shows 404 Not Found</h3>
                        <p style="font-size: 12px; color: #64748b; margin-bottom: 8px;">If navigating to <code>/pages/wishlist</code> returns 404:</p>
                        <div class="troubleshoot-list">
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">1</span>
                                <span>Check Shopify Admin &rarr; Online Store &rarr; Pages for a page with URL handle <code class="support-code-pill">wishlist</code>.</span>
                            </div>
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">2</span>
                                <span>Ensure the page visibility is set to <strong>Visible</strong>, not Hidden.</span>
                            </div>
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">3</span>
                                <span>Verify the <strong>SocialNest Wishlist</strong> App Block has been added to the page section.</span>
                            </div>
                        </div>
                    </div>

                    <div class="subcard">
                        <h3 style="color: #4338ca; font-size: 14px;">Changes Do Not Appear Immediately</h3>
                        <p style="font-size: 12px; color: #64748b; margin-bottom: 8px;">If updated colors or positions don't reflect:</p>
                        <div class="troubleshoot-list">
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">1</span>
                                <span>Confirm you clicked <strong>Save Settings</strong> at the bottom of the settings page.</span>
                            </div>
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">2</span>
                                <span>Shopify caches storefront scripts; allow 30–60 seconds for CDN cache invalidation.</span>
                            </div>
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">3</span>
                                <span>Perform a hard refresh (Ctrl+F5 or Cmd+Shift+R) on the storefront.</span>
                            </div>
                        </div>
                    </div>

                    <div class="subcard">
                        <h3 style="color: #4338ca; font-size: 14px;">Dock Collides With Live Chat</h3>
                        <p style="font-size: 12px; color: #64748b; margin-bottom: 8px;">To prevent icons covering chat or cart widgets:</p>
                        <div class="troubleshoot-list">
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">1</span>
                                <span>Open SocialNest Storefront Settings.</span>
                            </div>
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">2</span>
                                <span>Switch position to <strong>Bottom Left</strong> or <strong>Vertical Left</strong>.</span>
                            </div>
                            <div class="troubleshoot-item">
                                <span class="troubleshoot-badge">3</span>
                                <span>Alternatively, use <strong>Custom Coordinates</strong> to set custom pixel offsets.</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
