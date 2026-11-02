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
            // When opened directly or refreshed outside Shopify Admin (window.top === window.self),
            // immediately redirect the browser to Shopify Admin so the app renders embedded
            // surrounded by Shopify Admin navigation and sidebar (like ZeoSync).
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

    <!-- Shopify App Bridge CDN -->
    <script src="https://cdn.shopifycdn.net/shopifycloud/app-bridge.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --primary-border: #c7d2fe;
            --surface-bg: #f8fafc;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --text-main: #0f172a;
            --text-muted: #475569;
            --text-subtle: #64748b;
            --success: #10b981;
            --success-light: #ecfdf5;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --danger: #ef4444;
            --danger-light: #fef2f2;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.07), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -4px rgba(0, 0, 0, 0.04);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--surface-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
        }

        /* Layout Container */
        .app-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #ffffff;
            border-right: 1px solid var(--card-border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 40;
            transition: transform 0.25s ease;
        }

        .sidebar-header {
            padding: 1.5rem 1.25rem 1.25rem;
            border-bottom: 1px solid var(--card-border);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
            flex-shrink: 0;
        }

        .brand-name {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--text-main);
        }

        .brand-tag {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary);
            letter-spacing: 0.05em;
        }

        /* Sidebar Navigation */
        .sidebar-nav {
            padding: 1.25rem 0.75rem;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .nav-category {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-subtle);
            padding: 0.5rem 0.75rem 0.25rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: var(--radius-md);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.15s ease;
        }

        .nav-link:hover {
            color: var(--primary);
            background: var(--primary-light);
        }

        .nav-link.active {
            color: var(--primary);
            background: var(--primary-light);
            font-weight: 700;
        }

        .nav-link svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        /* Sidebar Footer / User Profile */
        .sidebar-footer {
            padding: 1rem 1.25rem;
            border-top: 1px solid var(--card-border);
            background: #ffffff;
        }

        .store-pill {
            background: var(--surface-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            padding: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .store-label {
            font-size: 0.6875rem;
            color: var(--text-subtle);
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.05em;
            margin-bottom: 0.2rem;
        }

        .store-domain {
            font-size: 0.8125rem;
            font-weight: 700;
            color: var(--text-main);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .store-plan-badge {
            display: inline-block;
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--primary);
            background: var(--primary-light);
            padding: 0.15rem 0.45rem;
            border-radius: 4px;
            margin-top: 0.25rem;
        }

        .btn-logout {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: transparent;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            color: var(--text-muted);
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-logout:hover {
            color: var(--danger);
            border-color: #fca5a5;
            background: var(--danger-light);
        }

        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: calc(100% - 260px);
        }

        /* Top Header */
        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--card-border);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .page-header-info h2 {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
        }

        .page-header-info p {
            font-size: 0.8125rem;
            color: var(--text-subtle);
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .badge-live {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            background: var(--success-light);
            color: var(--success);
            border: 1px solid #a7f3d0;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--success);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
        }

        /* Content Canvas */
        .content-canvas {
            padding: 2rem;
            flex: 1;
            max-width: 1400px;
            width: 100%;
            box-sizing: border-box;
        }

        /* Common Components */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.55rem 1.1rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.15s ease;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
        }

        .btn-secondary {
            background-color: #ffffff;
            color: var(--text-main);
            border-color: var(--card-border);
        }

        .btn-secondary:hover {
            background-color: var(--surface-bg);
            border-color: #cbd5e1;
        }

        .btn-danger {
            background-color: var(--danger);
            color: #ffffff;
            border-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .btn-sm {
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        /* Flash Message Alerts */
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

        .alert-success {
            background: var(--success-light);
            border: 1px solid #a7f3d0;
            color: #065f46;
        }

        .alert-error {
            background: var(--danger-light);
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert-warning {
            background: var(--warning-light);
            border: 1px solid #fde68a;
            color: #92400e;
        }

        /* Mobile Responsive */
        .mobile-toggle {
            display: none;
            background: transparent;
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            padding: 0.4rem;
            cursor: pointer;
            color: var(--text-main);
        }

        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                width: 100%;
            }

            .mobile-toggle {
                display: block;
            }

            .mobile-brand-icon {
                display: flex !important;
            }

            .content-canvas {
                padding: 1.25rem;
            }
        }

        /* Floating Toast Notification System */
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
            border-radius: 8px;
            padding: 0.75rem 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            animation: toastSlideIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-sizing: border-box;
        }

        .toast-item.toast-warning {
            border: 1px solid #fde68a;
            border-left: 4px solid #f59e0b;
        }

        .toast-item.toast-success {
            border: 1px solid #a7f3d0;
            border-left: 4px solid #10b981;
        }

        .toast-item.toast-error {
            border: 1px solid #fecaca;
            border-left: 4px solid #ef4444;
        }

        .toast-content {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            flex: 1;
            min-width: 0;
        }

        .toast-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .toast-message {
            font-size: 0.8125rem;
            font-weight: 600;
            color: #0f172a;
            line-height: 1.4;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .toast-close-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.15s ease;
        }

        .toast-close-btn:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        @keyframes toastSlideIn {
            from {
                transform: translateX(40px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateX(0) scale(1);
                opacity: 1;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="app-layout">
        <!-- Sidebar Navigation -->
        <aside class="sidebar" id="sidebarMenu">
            <div>
                <div class="sidebar-header">
                    <div class="brand-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <div>
                        <div class="brand-name">SocialNest</div>
                        <div class="brand-tag">Merchant Portal</div>
                    </div>
                </div>

                @php
                    $navParams = array_filter([
                        'host' => request('host') ?? session('shopify_host'),
                        'shop' => $shop ?? session('shopify_shop'),
                    ]);
                @endphp
                <nav class="sidebar-nav">
                    <div class="nav-category">Main Menu</div>

                    <a href="{{ route('shopify.dashboard', $navParams) }}" class="nav-link {{ request()->routeIs('shopify.dashboard') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <span>Dashboard</span>
                    </a>

                    <a href="{{ route('shopify.social-links.index', $navParams) }}" class="nav-link {{ request()->routeIs('shopify.social-links.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        <span>My Social Links</span>
                    </a>

                    <a href="{{ route('shopify.settings.index', $navParams) }}" class="nav-link {{ request()->routeIs('shopify.settings.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        <span>Storefront Settings</span>
                    </a>

                    <div class="nav-category" style="margin-top: 1rem;">Administration</div>

                    <a href="{{ route('shopify.plans.index', $navParams) }}" class="nav-link {{ request()->routeIs('shopify.plans.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        <span>Plans & Billing</span>
                    </a>

                    <a href="{{ route('shopify.support.index', $navParams) }}" class="nav-link {{ request()->routeIs('shopify.support.*') ? 'active' : '' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <span>Help & Support</span>
                    </a>
                </nav>
            </div>

            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <div class="store-pill">
                    <div class="store-label">Connected Store</div>
                    <div class="store-domain" title="{{ $shop }}">{{ $shop }}</div>
                    <span class="store-plan-badge">{{ ucfirst($store->plan_name ?: 'Free') }} Plan</span>
                </div>

                <form action="{{ route('shopify.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-logout" title="Log out of SocialNest session">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        <span>Log out</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Column -->
        <div class="main-content">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="mobile-toggle" id="mobileToggleBtn" aria-label="Toggle navigation menu">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                    </button>
                    <div class="brand-icon mobile-brand-icon" style="display: none; width: 32px; height: 32px; border-radius: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </div>
                    <div class="page-header-info">
                        <h2>@yield('header_title', 'Merchant Portal')</h2>
                        <p>@yield('header_subtitle', 'Manage your storefront social media sharing widget')</p>
                    </div>
                </div>

                <div class="top-actions">
                    <div class="badge-live">
                        <span class="badge-live-dot"></span>
                        <span>Live & Authenticated</span>
                    </div>

                    @yield('header_actions')
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
        const mobileToggle = document.getElementById('mobileToggleBtn');
        const sidebar = document.getElementById('sidebarMenu');
        if (mobileToggle && sidebar) {
            mobileToggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
            });
        }

        // Automatic URL parameter preservation for internal navigation in Shopify Admin iframe
        document.addEventListener('DOMContentLoaded', function() {
            const host = window.sessionStorage ? window.sessionStorage.getItem('shopify_host') : null;
            const shop = window.sessionStorage ? window.sessionStorage.getItem('shopify_shop') : null;

            if (host || shop) {
                // Ensure internal links preserve host and shop
                document.querySelectorAll('a[href]').forEach(function(link) {
                    const href = link.getAttribute('href');
                    if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank') {
                        return;
                    }

                    try {
                        const url = new URL(link.href, window.location.origin);
                        if (url.origin === window.location.origin && url.pathname.startsWith('/shopify')) {
                            if (host && !url.searchParams.has('host')) {
                                url.searchParams.set('host', host);
                            }
                            if (shop && !url.searchParams.has('shop')) {
                                url.searchParams.set('shop', shop);
                            }
                            link.href = url.toString();
                        }
                    } catch (e) {
                        // Ignore malformed URLs
                    }
                });

                // Ensure internal forms preserve host and shop
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
                    } catch (e) {
                        // Ignore malformed URLs
                    }
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
                        if (host && !url.searchParams.has('host')) {
                            url.searchParams.set('host', host);
                            modified = true;
                        }
                        if (shop && !url.searchParams.has('shop')) {
                            url.searchParams.set('shop', shop);
                            modified = true;
                        }
                        if (modified) {
                            link.href = url.toString();
                        }
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
                } catch (err) {}
            }
        }, true);

        // Toast Notification Logic
        function dismissToast(el) {
            if (!el || el.dataset.dismissing === 'true') return;
            el.dataset.dismissing = 'true';
            el.style.opacity = '0';
            el.style.transform = 'translateY(-10px) scale(0.95)';
            setTimeout(function() {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 300);
        }

        function initAutoDismissToast(toast, duration) {
            duration = duration || 3000;
            var timer = setTimeout(function() {
                dismissToast(toast);
            }, duration);

            toast.addEventListener('mouseenter', function() {
                clearTimeout(timer);
            });

            toast.addEventListener('mouseleave', function() {
                timer = setTimeout(function() {
                    dismissToast(toast);
                }, 1500);
            });
        }

        window.showToast = function(message, type, duration) {
            type = type || 'warning';
            duration = duration || 3000;
            var container = document.getElementById('toastContainer');
            if (!container) return;

            var toast = document.createElement('div');
            toast.className = 'toast-item toast-' + type;
            toast.setAttribute('role', 'alert');

            var iconSvg = type === 'success'
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

            toast.innerHTML =
                '<div class="toast-content">' +
                    '<div class="toast-icon">' + iconSvg + '</div>' +
                    '<div class="toast-message">' + message + '</div>' +
                '</div>' +
                '<button type="button" class="toast-close-btn" onclick="dismissToast(this.closest(\'.toast-item\'))" aria-label="Close notification" title="Close">' +
                    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
                '</button>';

            container.appendChild(toast);
            initAutoDismissToast(toast, duration);
        };

        document.addEventListener('DOMContentLoaded', function() {
            var autoDismissToasts = document.querySelectorAll('.toast-auto-dismiss');
            autoDismissToasts.forEach(function(toast) {
                initAutoDismissToast(toast, 3000);
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
