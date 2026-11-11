<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="shopify-api-key" content="{{ $apiKey ?? config('shopify.api_key') }}" />
    <title>@yield('title', 'SocialNest - Shopify Social Sharing')</title>

    <!-- Shopify App Bridge and Embedded Host Preservation -->
    <script>
        (function() {
            const urlParams = new URLSearchParams(window.location.search);
            let host = urlParams.get('host');
            let shop = urlParams.get('shop');

            const serverHost = @json(session('shopify_host') ?? request('host'));
            const serverShop = @json($shop ?? session('shopify_shop'));

            if (!host && serverHost) {
                host = serverHost;
            }
            if (!host && window.sessionStorage) {
                host = window.sessionStorage.getItem('shopify_host');
            }

            if (!shop && serverShop) {
                shop = serverShop;
            }
            if (!shop && window.sessionStorage) {
                shop = window.sessionStorage.getItem('shopify_shop');
            }

            // Synthesize host from shop domain if missing, ensuring App Bridge targets admin.shopify.com
            if (!host && shop) {
                const cleanShop = shop.trim().toLowerCase();
                if (cleanShop.endsWith('.myshopify.com')) {
                    const storeHandle = cleanShop.replace('.myshopify.com', '');
                    try {
                        host = window.btoa('admin.shopify.com/store/' + storeHandle);
                    } catch (e) {}
                }
            }

            if (host && window.sessionStorage) {
                window.sessionStorage.setItem('shopify_host', host);
            }
            if (shop && window.sessionStorage) {
                window.sessionStorage.setItem('shopify_shop', shop);
            }

            if (host && !urlParams.has('host')) {
                urlParams.set('host', host);
                if (shop && !urlParams.has('shop')) {
                    urlParams.set('shop', shop);
                }
                const newSearch = urlParams.toString();
                const newUrl = window.location.pathname + '?' + newSearch + window.location.hash;
                window.history.replaceState(null, '', newUrl);
            }

            // Auto-Embedding Break-in:
            if (window.top === window.self) {
                const apiKey = @json($apiKey ?? config('shopify.api_key'));
                let adminAppUrl = null;

                if (host) {
                    try {
                        let base64 = host.replace(/-/g, '+').replace(/_/g, '/');
                        while (base64.length % 4) {
                            base64 += '=';
                        }
                        const decodedHost = window.atob(base64);
                        if (decodedHost && (decodedHost.includes('admin.shopify.com') || decodedHost.includes('.myshopify.com'))) {
                            adminAppUrl = 'https://' + decodedHost.replace(/\/+$/, '') + '/apps/' + encodeURIComponent(apiKey);
                        }
                    } catch (e) {}
                }

                if (!adminAppUrl && shop) {
                    const cleanShop = shop.trim().toLowerCase();
                    if (cleanShop.endsWith('.myshopify.com')) {
                        const storeHandle = cleanShop.replace('.myshopify.com', '');
                        adminAppUrl = 'https://admin.shopify.com/store/' + encodeURIComponent(storeHandle) + '/apps/' + encodeURIComponent(apiKey);
                    } else {
                        adminAppUrl = 'https://' + encodeURIComponent(cleanShop) + '/admin/apps/' + encodeURIComponent(apiKey);
                    }
                }

                if (adminAppUrl && apiKey) {
                    window.location.replace(adminAppUrl);
                    return;
                }
            }
        })();
    </script>

    <!-- CORRECT Shopify App Bridge CDN -->
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Brand & Accent Palette */
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-active: #3730a3;
            --primary-light: #eef2ff;
            --primary-subtle: #f5f7ff;
            --primary-border: #c7d2fe;
            --primary-focus: rgba(79, 70, 229, 0.16);

            /* Surfaces & Borders */
            --surface-bg: #f8fafc;
            --surface-hover: #f1f5f9;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --card-border-hover: #cbd5e1;
            --divider: #f1f5f9;

            /* Typography Colors */
            --text-main: #0f172a;
            --text-secondary: #334155;
            --text-muted: #475569;
            --text-subtle: #64748b;
            --text-placeholder: #94a3b8;

            /* Semantic Status Colors */
            --success: #10b981;
            --success-dark: #059669;
            --success-light: #ecfdf5;
            --success-border: #a7f3d0;

            --warning: #f59e0b;
            --warning-dark: #d97706;
            --warning-light: #fffbeb;
            --warning-border: #fde68a;

            --danger: #ef4444;
            --danger-dark: #dc2626;
            --danger-light: #fef2f2;
            --danger-border: #fecaca;

            /* Radii & Shadows */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --shadow-xs: 0 1px 2px 0 rgba(15, 23, 42, 0.03);
            --shadow-sm: 0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.03);
            --shadow-md: 0 4px 6px -1px rgba(15, 23, 42, 0.06), 0 2px 4px -2px rgba(15, 23, 42, 0.04);
            --shadow-lg: 0 10px 15px -3px rgba(15, 23, 42, 0.07), 0 4px 6px -4px rgba(15, 23, 42, 0.04);
            --shadow-primary: 0 4px 12px rgba(79, 70, 229, 0.22);

            /* Layout Dimensions */
            --header-height: 72px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            background-color: var(--surface-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            font-size: 0.875rem;
            line-height: 1.5;
            overflow-x: hidden;
        }

        /* Prevent App Bridge Navigation from flashing visible blue links */
        ui-nav-menu {
            display: none !important;
        }

        /* ==========================================================================
           Layout Container (Full Width Native App)
           ========================================================================== */
        .app-layout {
            display: flex;
            flex-direction: column;
            width: 100%;
            min-height: 100vh;
            position: relative;
        }

        .main-content {
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--surface-bg);
            margin-left: 0; /* Forced override of previous offset */
        }

        /* ==========================================================================
           Top Header
           ========================================================================== */
      .top-navbar {
            min-height: var(--header-height);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--card-border);
            padding: 0.875rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 30;
            max-width: 1400px;
            width: calc(100% - 4rem); /* Dono taraf se 2rem + 2rem space chhodne ke liye */
            margin: 0 auto;
            box-sizing: border-box;
        }

        .page-header-info h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
            line-height: 1.25;
        }

        .page-header-info p {
            font-size: 0.8125rem;
            color: var(--text-subtle);
            margin-top: 0.125rem;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .badge-live {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.375rem 0.825rem;
            background: var(--success-light);
            color: var(--success-dark);
            border: 1px solid var(--success-border);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--success);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.22);
            animation: statusPulse 2.2s infinite ease-in-out;
        }

        @keyframes statusPulse {
            0%, 100% { box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25); }
            50% { box-shadow: 0 0 0 5px rgba(16, 185, 129, 0.12); }
        }

        /* Content Canvas */
        .content-canvas {
            padding: 1.75rem 2rem 2.5rem;
            flex: 1;
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
        }

        /* ==========================================================================
           Buttons
           ========================================================================== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5625rem 1.125rem;
            font-size: 0.875rem;
            font-weight: 600;
            line-height: 1.25;
            border-radius: var(--radius-md);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .btn svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
        }

        .btn:disabled, .btn.disabled {
            opacity: 0.6;
            cursor: not-allowed;
            pointer-events: none;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 1px 2px rgba(79, 70, 229, 0.15);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: var(--shadow-primary);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #ffffff;
            color: var(--text-main);
            border-color: var(--card-border);
            box-shadow: var(--shadow-xs);
        }

        .btn-secondary:hover {
            background-color: var(--surface-bg);
            border-color: var(--card-border-hover);
        }

        .btn-outline {
            background-color: transparent;
            color: var(--text-muted);
            border-color: var(--card-border);
        }

        .btn-outline:hover {
            background-color: var(--danger-light);
            color: var(--danger-dark);
            border-color: var(--danger-border);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        /* ==========================================================================
           Cards & Containers
           ========================================================================== */
        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .card-hover:hover {
            border-color: var(--card-border-hover);
            box-shadow: var(--shadow-md);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-bottom: 1rem;
            margin-bottom: 1.25rem;
            border-bottom: 1px solid var(--divider);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-main);
        }

        /* ==========================================================================
           Forms & Input Controls
           ========================================================================== */
        .form-group { margin-bottom: 1.25rem; }
        .form-label { display: block; font-size: 0.8125rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.4rem; }
        .form-control {
            width: 100%;
            padding: 0.5625rem 0.875rem;
            font-size: 0.875rem;
            color: var(--text-main);
            background-color: #ffffff;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            outline: none;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-focus);
        }

        /* ==========================================================================
           Tables
           ========================================================================== */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border-radius: var(--radius-lg);
            border: 1px solid var(--card-border);
            background: #ffffff;
        }
        table.table { width: 100%; border-collapse: collapse; text-align: left; font-size: 0.875rem; }
        table.table th { background: var(--surface-bg); color: var(--text-subtle); font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--card-border); }
        table.table td { padding: 0.9375rem 1.25rem; border-bottom: 1px solid var(--divider); color: var(--text-secondary); }
        table.table tbody tr:last-child td { border-bottom: none; }

        /* ==========================================================================
           Badges & Status Pills
           ========================================================================== */
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 9999px;
        }
        .badge-primary { background: var(--primary-light); color: var(--primary); border: 1px solid var(--primary-border); }
        .badge-success { background: var(--success-light); color: var(--success-dark); border: 1px solid var(--success-border); }

        /* ==========================================================================
           Flash Message Alerts
           ========================================================================== */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .alert-error { background: var(--danger-light); border: 1px solid var(--danger-border); color: #991b1b; }

        /* ==========================================================================
           Mobile & Tablet Responsive Behavior
           ========================================================================== */
        @media (max-width: 640px) {
            .top-navbar { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .top-actions { width: 100%; justify-content: space-between; }
            .content-canvas { padding: 1rem; }
        }

        /* ==========================================================================
           Floating Toast Notification System
           ========================================================================== */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 24px;
            z-index: 999999;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: 440px;
            width: calc(100% - 48px);
            pointer-events: none;
        }
        .toast-item {
            pointer-events: auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 0.825rem 1rem;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 4px 6px -2px rgba(15, 23, 42, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            animation: toastSlideIn 0.3s forwards;
            transition: all 0.3s;
        }
        .toast-item.toast-warning { border: 1px solid #fde68a; border-left: 4px solid #f59e0b; }
        .toast-item.toast-success { border: 1px solid #a7f3d0; border-left: 4px solid #10b981; }
        .toast-item.toast-error { border: 1px solid #fecaca; border-left: 4px solid #ef4444; }
        .toast-content { display: flex; align-items: center; gap: 0.625rem; flex: 1; }
        .toast-message { font-size: 0.8125rem; font-weight: 600; color: #0f172a; line-height: 1.4; }
        .toast-close-btn { background: transparent; border: none; cursor: pointer; padding: 4px; border-radius: 4px; color: #64748b; }
        @keyframes toastSlideIn { from { transform: translateX(40px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $navParams = array_filter([
            'host' => request('host') ?? session('shopify_host'),
            'shop' => $shop ?? session('shopify_shop'),
        ]);
    @endphp

    <!-- SHOPIFY NATIVE APP BRIDGE NAVIGATION -->
    <!-- The 'hidden' attribute hides the raw links from the page, but App Bridge still reads them for the native black sidebar -->
    <ui-nav-menu hidden>
        <a href="{{ route('shopify.dashboard', $navParams) }}">Dashboard</a>
        <a href="{{ route('shopify.social-links.index', $navParams) }}">My Social Links</a>
        <a href="{{ route('shopify.settings.index', $navParams) }}">Storefront Settings</a>
        <a href="{{ route('shopify.plans.index', $navParams) }}">Plans & Billing</a>
        <a href="{{ route('shopify.support.index', $navParams) }}">Help & Support</a>
    </ui-nav-menu>

    <div class="app-layout">
        <!-- Main Content Column (Now completely full width) -->
        <div class="main-content">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="page-header-info">
                    <h2>@yield('header_title', 'Merchant Portal')</h2>
                    <p>@yield('header_subtitle', 'Manage your storefront social media sharing widget')</p>
                </div>

                <div class="top-actions">
                    <div class="badge-live">
                        <span class="badge-live-dot"></span>
                        <span>Live</span>
                    </div>

                    @yield('header_actions')

                    <!-- Preserved Backend Logic: Logout Route & CSRF Form -->
                    <form action="{{ route('shopify.logout') }}" method="POST" style="margin: 0;">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm" title="Log out of SocialNest session">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            <span style="margin-left: 4px;">Log out</span>
                        </button>
                    </form>
                </div>
            </header>

            <!-- Page Body -->
            <main class="content-canvas">
                <!-- Global Floating Toast Container -->
                <div id="toastContainer" class="toast-container" aria-live="polite">
                    @if(session('success'))
                        <div class="toast-item toast-success toast-auto-dismiss" role="alert" data-timeout="3000">
                            <div class="toast-content">
                                <div class="toast-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                </div>
                                <div class="toast-message">{{ session('success') }}</div>
                            </div>
                            <button type="button" class="toast-close-btn" onclick="dismissToast(this.closest('.toast-item'))" aria-label="Close notification" title="Close">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="toast-item toast-warning toast-auto-dismiss" role="alert" data-timeout="3000">
                            <div class="toast-content">
                                <div class="toast-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                </div>
                                <div class="toast-message">{{ session('warning') }}</div>
                            </div>
                            <button type="button" class="toast-close-btn" onclick="dismissToast(this.closest('.toast-item'))" aria-label="Close notification" title="Close">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="toast-item toast-error toast-auto-dismiss" role="alert" data-timeout="3000">
                            <div class="toast-content">
                                <div class="toast-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                </div>
                                <div class="toast-message">{{ session('error') }}</div>
                            </div>
                            <button type="button" class="toast-close-btn" onclick="dismissToast(this.closest('.toast-item'))" aria-label="Close notification" title="Close">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    @endif
                </div>

                @if(isset($errors) && $errors->any())
                    <div class="alert alert-error">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <div>
                            <strong>Please check the following errors:</strong>
                            <ul style="margin-top: 0.25rem; padding-left: 1.25rem;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        // Automatic URL parameter preservation for internal navigation in Shopify Admin iframe
        document.addEventListener('DOMContentLoaded', function() {
            const host = window.sessionStorage ? window.sessionStorage.getItem('shopify_host') : null;
            const shop = window.sessionStorage ? window.sessionStorage.getItem('shopify_shop') : null;

            if (host || shop) {
                document.querySelectorAll('a[href]').forEach(function(link) {
                    const href = link.getAttribute('href');
                    if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank') {
                        return;
                    }
                    try {
                        const url = new URL(link.href, window.location.origin);
                        if (url.origin === window.location.origin && url.pathname.startsWith('/shopify')) {
                            if (host && !url.searchParams.has('host')) url.searchParams.set('host', host);
                            if (shop && !url.searchParams.has('shop')) url.searchParams.set('shop', shop);
                            link.href = url.toString();
                        }
                    } catch (e) {}
                });

                document.querySelectorAll('form').forEach(function(form) {
                    const action = form.getAttribute('action') || window.location.pathname;
                    try {
                        const url = new URL(action, window.location.origin);
                        if (url.origin === window.location.origin && url.pathname.startsWith('/shopify')) {
                            if (host && !form.querySelector('input[name="host"]')) {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'host';
                                input.value = host;
                                form.appendChild(input);
                            }
                            if (shop && !form.querySelector('input[name="shop"]')) {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'shop';
                                input.value = shop;
                                form.appendChild(input);
                            }
                        }
                    } catch (e) {}
                });
            }
        });

        // Continuous delegated interception for all clicks and form submissions
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (!link) return;
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank') {
                return;
            }
            const host = window.sessionStorage ? window.sessionStorage.getItem('shopify_host') : null;
            const shop = window.sessionStorage ? window.sessionStorage.getItem('shopify_shop') : null;
            if (host || shop) {
                try {
                    const url = new URL(link.href, window.location.origin);
                    if (url.origin === window.location.origin && url.pathname.startsWith('/shopify')) {
                        let modified = false;
                        if (host && !url.searchParams.has('host')) { url.searchParams.set('host', host); modified = true; }
                        if (shop && !url.searchParams.has('shop')) { url.searchParams.set('shop', shop); modified = true; }
                        if (modified) link.href = url.toString();
                    }
                } catch (err) {}
            }
        }, true);

        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (!form || !form.action) return;
            const host = window.sessionStorage ? window.sessionStorage.getItem('shopify_host') : null;
            const shop = window.sessionStorage ? window.sessionStorage.getItem('shopify_shop') : null;
            if (host || shop) {
                try {
                    const url = new URL(form.action, window.location.origin);
                    if (url.origin === window.location.origin && url.pathname.startsWith('/shopify')) {
                        if (host && !form.querySelector('input[name="host"]')) {
                            const input = document.createElement('input'); input.type = 'hidden'; input.name = 'host'; input.value = host; form.appendChild(input);
                        }
                        if (shop && !form.querySelector('input[name="shop"]')) {
                            const input = document.createElement('input'); input.type = 'hidden'; input.name = 'shop'; input.value = shop; form.appendChild(input);
                        }
                    }
                } catch (err) {}
            }
        }, true);

        // Toast Notification Logic
        function dismissToast(el) {
            if (!el || el.dataset.dismissing === 'true') return;
            el.dataset.dismissing = 'true';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-10px) scale(0.95)';
            setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 300);
        }

        function initAutoDismissToast(toast, duration) {
            duration = duration || 3000;
            var timer = setTimeout(function() { dismissToast(toast); }, duration);
            toast.addEventListener('mouseenter', function() { clearTimeout(timer); });
            toast.addEventListener('mouseleave', function() { timer = setTimeout(function() { dismissToast(toast); }, 1500); });
        }

        window.showToast = function(message, type, duration) {
            type = type || 'warning';
            duration = duration || 3000;
            var container = document.getElementById('toastContainer');
            if (!container) return;

            var toast = document.createElement('div');
            toast.className = 'toast-item toast-' + type;
            toast.setAttribute('role', 'alert');

            var iconSvg = type === 'success' ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>' 
                : (type === 'error' ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>');

            toast.innerHTML = '<div class="toast-content"><div class="toast-icon">' + iconSvg + '</div><div class="toast-message">' + message + '</div></div><button type="button" class="toast-close-btn" onclick="dismissToast(this.closest(\'.toast-item\'))"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg></button>';
            container.appendChild(toast);
            initAutoDismissToast(toast, duration);
        };

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.toast-auto-dismiss').forEach(function(toast) { initAutoDismissToast(toast, 3000); });
        });
    </script>
    @stack('scripts')
</body>
</html>