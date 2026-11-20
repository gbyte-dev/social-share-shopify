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

    <!-- SweetAlert2 Professional Notification Library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Centralized Professional Notification Engine (SweetAlert2) -->
    <script>
        (function() {
            window.AppNotice = {
                fire: function(options) {
                    if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                        const defaults = {
                            customClass: {
                                popup: 'sn-swal-popup',
                                title: 'sn-swal-title',
                                htmlContainer: 'sn-swal-content',
                                confirmButton: 'sn-swal-confirm-btn',
                                cancelButton: 'sn-swal-cancel-btn'
                            },
                            buttonsStyling: false,
                            confirmButtonText: 'OK',
                            focusConfirm: true
                        };
                        return Swal.fire(Object.assign({}, defaults, options));
                    }
                    
                    const text = (options && (options.text || options.title)) || '';
                    console.warn('[AppNotice Fallback]', text);
                    if (typeof window.showToast === 'function') {
                        const toastType = (options && options.icon === 'error') ? 'error' : ((options && options.icon === 'warning') ? 'warning' : 'success');
                        window.showToast(text, toastType, 4000);
                    }
                    return Promise.resolve({ isConfirmed: true, isDismissed: false });
                },

                success: function(message, title) {
                    return this.fire({
                        icon: 'success',
                        title: title || 'Operation Completed',
                        text: String(message || 'Operation completed successfully.')
                    });
                },

                error: function(message, title) {
                    return this.fire({
                        icon: 'error',
                        title: title || 'Something Went Wrong',
                        text: String(message || 'An unexpected error occurred. Please try again.')
                    });
                },

                warning: function(message, title) {
                    return this.fire({
                        icon: 'warning',
                        title: title || 'Please Check Your Input',
                        text: String(message || 'Please check your inputs and try again.')
                    });
                },

                info: function(message, title) {
                    return this.fire({
                        icon: 'info',
                        title: title || 'Information',
                        text: String(message || '')
                    });
                },

                toast: function(message, icon, title) {
                    const ic = icon || 'success';
                    if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                        return Swal.fire({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3500,
                            timerProgressBar: true,
                            icon: ic,
                            title: title || (ic === 'success' ? 'Success' : (ic === 'error' ? 'Error' : 'Notice')),
                            text: String(message || '')
                        });
                    }
                    if (typeof window.showToast === 'function') {
                        window.showToast(message, ic, 3500);
                    }
                    return Promise.resolve();
                },

                confirm: function(message, options) {
                    const opts = options || {};
                    return this.fire({
                        icon: opts.icon || 'warning',
                        title: opts.title || 'Are you sure?',
                        text: String(message || ''),
                        showCancelButton: true,
                        confirmButtonText: opts.confirmButtonText || 'Confirm',
                        cancelButtonText: opts.cancelButtonText || 'Cancel',
                        reverseButtons: true
                    });
                },

                alert: function(message, title) {
                    const msg = String(message || '');
                    const lower = msg.toLowerCase();
                    let icon = 'info';
                    let inferredTitle = title;

                    if (lower.includes('error') || lower.includes('fail') || lower.includes('failed') || lower.includes('unable') || lower.includes('could not')) {
                        icon = 'error';
                        if (!inferredTitle) inferredTitle = 'Something Went Wrong';
                    } else if (lower.includes('please') || lower.includes('enter') || lower.includes('require') || lower.includes('missing') || lower.includes('check')) {
                        icon = 'warning';
                        if (!inferredTitle) inferredTitle = 'Please Check Your Input';
                    } else if (lower.includes('success') || lower.includes('saved') || lower.includes('completed')) {
                        icon = 'success';
                        if (!inferredTitle) inferredTitle = 'Operation Completed';
                    } else {
                        if (!inferredTitle) inferredTitle = 'Notice';
                    }

                    return this.fire({
                        icon: icon,
                        title: inferredTitle,
                        text: msg
                    });
                }
            };

            window.showNotice = window.AppNotice;

            // Global safety net: divert any native alert() to AppNotice.alert()
            window.alert = function(msg) {
                return window.AppNotice.alert(msg);
            };
        })();
    </script>

    <!-- Centralized Shopify App Bridge Authenticated Fetch -->
    <script>
        (function() {
            /**
             * Retrieves a fresh session ID token from Shopify App Bridge.
             * Returns a Promise resolving to the JWT string or null.
             */
            async function getShopifyIdToken() {
                try {
                    if (window.shopify && typeof window.shopify.idToken === 'function') {
                        const token = await window.shopify.idToken();
                        if (token) return token;
                    }
                } catch (err) {
                    console.warn('[SocialNest] Failed to retrieve session token from App Bridge:', err);
                }
                return null;
            }

            /**
             * Centralized authenticated fetch helper for Shopify embedded requests.
             *
             * - Automatically requests an ID token from window.shopify.idToken()
             * - Injects Authorization: Bearer <id-token> header
             * - Preserves existing request headers (Content-Type, X-CSRF-TOKEN, Accept, etc.)
             * - Does not store tokens in localStorage or sessionStorage
             * - Intercepts HTTP 401 with X-Shopify-Retry-Invalid-Session-Request: 1,
             *   obtains a fresh session token, and retries the request once.
             *
             * @param {string} url
             * @param {RequestInit} [options={}]
             * @param {boolean} [isRetry=false]
             * @returns {Promise<Response>}
             */
            async function shopifyAuthenticatedFetch(url, options = {}, isRetry = false) {
                const token = await getShopifyIdToken();

                const opts = { ...options };
                const headers = new Headers(opts.headers || {});

                if (token) {
                    headers.set('Authorization', 'Bearer ' + token);
                }

                if (!headers.has('X-CSRF-TOKEN')) {
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    if (csrfMeta && csrfMeta.content) {
                        headers.set('X-CSRF-TOKEN', csrfMeta.content);
                    }
                }

                if (!headers.has('X-Requested-With')) {
                    headers.set('X-Requested-With', 'XMLHttpRequest');
                }

                opts.headers = headers;

                const response = await fetch(url, opts);

                // Handle session expiry retry mechanism
                if (response.status === 401 && !isRetry) {
                    const retryHeader = response.headers.get('X-Shopify-Retry-Invalid-Session-Request');
                    if (retryHeader === '1' || retryHeader === 'true') {
                        console.info('[SocialNest] Session token expired or invalid; retrying with fresh token...');
                        return shopifyAuthenticatedFetch(url, options, true);
                    }
                }

                return response;
            }

            // Expose globally for all embedded views and scripts
            window.shopifyAuthenticatedFetch = shopifyAuthenticatedFetch;
            window.getShopifyIdToken = getShopifyIdToken;
        })();
    </script>

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
            --text-placeholder: #000000;

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
        [hidden] {
            display: none !important;
        }

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
        table.table th { background: var(--surface-bg);   font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--card-border); }
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
        /* SweetAlert2 Polaris Theme Customizations */
        div.swal2-container {
            z-index: 9999999 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif !important;
        }
        .swal2-popup.sn-swal-popup {
            border-radius: 14px !important;
            padding: 1.5rem 1.75rem !important;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
            border: 1px solid #e2e8f0 !important;
        }
        .swal2-title.sn-swal-title {
            font-size: 1.125rem !important;
            font-weight: 700 !important;
            color: #0f172a !important;
            margin-bottom: 0.5rem !important;
        }
        .swal2-html-container.sn-swal-content {
            font-size: 0.875rem !important;
            color: #475569 !important;
            line-height: 1.5 !important;
        }
        .swal2-confirm.sn-swal-confirm-btn {
            background-color: #0284c7 !important;
            color: #ffffff !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            padding: 0.55rem 1.25rem !important;
            border-radius: 8px !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
            cursor: pointer !important;
        }
        .swal2-confirm.sn-swal-confirm-btn:hover {
            background-color: #0369a1 !important;
        }
        .swal2-cancel.sn-swal-cancel-btn {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            padding: 0.55rem 1.25rem !important;
            border-radius: 8px !important;
            border: 1px solid #cbd5e1 !important;
            cursor: pointer !important;
        }
        .swal2-cancel.sn-swal-cancel-btn:hover {
            background-color: #e2e8f0 !important;
        }
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
            @unless(View::hasSection('hide_top_navbar'))
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

                    <div id="headerActionsSlot" class="header-actions-slot">
                        @yield('header_actions')
                    </div>

                    <!-- Preserved Backend Logic: Logout Route & CSRF Form -->
                </div>
            </header>
            @endunless

            <!-- Page Body -->
            <main class="content-canvas">
                <!-- Global Floating Toast Container -->
                <div id="toastContainer" class="toast-container" aria-live="polite">
                    @if(session('purchase_success'))
                        <div class="toast-item toast-success toast-auto-dismiss" role="alert" data-timeout="5000" style="border-left: 4px solid #10b981; background: #ffffff;">
                            <div class="toast-content" style="align-items: flex-start;">
                                <div class="toast-icon" style="margin-top: 2px;">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <div style="font-size: 0.875rem; font-weight: 700; color: #065f46;">Purchase Successful!</div>
                                    <div class="toast-message" style="font-size: 0.8125rem; font-weight: 500; color: #374151;">{{ session('purchase_success') }}</div>
                                </div>
                            </div>
                            <button type="button" class="toast-close-btn" onclick="dismissToast(this.closest('.toast-item'))" aria-label="Close notification" title="Close">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    @elseif(session('success'))
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

        /**
         * Core client-side authenticated navigation engine for embedded pages.
         * Fetches destination HTML with Authorization: Bearer <ShopifyIdToken>
         * and swaps content canvas without native document reloads or cookie dependence.
         */
        let isNavigating = false;

        async function shopifyNavigateTo(url, pushState = true) {
            if (isNavigating) return;

            // Preserve host and shop parameters if available
            const host = window.sessionStorage ? window.sessionStorage.getItem('shopify_host') : null;
            const shop = window.sessionStorage ? window.sessionStorage.getItem('shopify_shop') : null;
            let targetUrl;
            try {
                targetUrl = new URL(url, window.location.origin);
                if (host && !targetUrl.searchParams.has('host')) targetUrl.searchParams.set('host', host);
                if (shop && !targetUrl.searchParams.has('shop')) targetUrl.searchParams.set('shop', shop);
            } catch (e) {
                targetUrl = new URL(url, window.location.href);
            }

            const destinationHref = targetUrl.toString();

            isNavigating = true;

            // Subtle loading indicator
            const canvas = document.querySelector('.content-canvas');
            if (canvas) {
                canvas.style.transition = 'opacity 0.15s ease';
                canvas.style.opacity = '0.4';
                canvas.style.pointerEvents = 'none';
            }

            try {
                const response = await shopifyAuthenticatedFetch(destinationHref, {
                    method: 'GET',
                    headers: {
                        'Accept': 'text/html, application/xhtml+xml',
                        'X-Shopify-Navigation': '1'
                    }
                });

                if (response.status === 403) {
                    let upgradeUrl = null;
                    let message = 'This feature requires a SocialNest Pro subscription.';
                    try {
                        const json = await response.json();
                        if (json && json.message) message = json.message;
                        if (json && json.upgrade_url) upgradeUrl = json.upgrade_url;
                    } catch (e) {}

                    if (typeof window.showToast === 'function') {
                        window.showToast(message, 'warning', 4000);
                    }

                    if (upgradeUrl) {
                        return shopifyNavigateTo(upgradeUrl, true);
                    }
                    return;
                }

                if (!response.ok) {
                    throw new Error('Navigation failed with status: ' + response.status);
                }

                const htmlText = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(htmlText, 'text/html');

                const newCanvas = doc.querySelector('.content-canvas');
                if (!newCanvas) {
                    throw new Error('Invalid response: missing content canvas');
                }

                // Notify current page to tear down components (e.g. Chart.js instances) right before DOM swap
                document.dispatchEvent(new CustomEvent('shopify:before-swap', {
                    detail: { nextUrl: destinationHref }
                }));

                // 1. Update document title
                const newTitle = doc.querySelector('title');
                if (newTitle) {
                    document.title = newTitle.textContent;
                }

                // 2. Update page header info
                const currentHeaderInfo = document.querySelector('.page-header-info');
                const newHeaderInfo = doc.querySelector('.page-header-info');
                if (currentHeaderInfo && newHeaderInfo) {
                    currentHeaderInfo.innerHTML = newHeaderInfo.innerHTML;
                }

                // 3. Update header actions slot
                const currentActions = document.getElementById('headerActionsSlot');
                const newActions = doc.getElementById('headerActionsSlot');
                if (currentActions && newActions) {
                    currentActions.innerHTML = newActions.innerHTML;
                } else if (currentActions) {
                    currentActions.innerHTML = '';
                }

                // 4. Update main content canvas
                const currentCanvas = document.querySelector('.content-canvas');
                if (currentCanvas && newCanvas) {
                    currentCanvas.innerHTML = newCanvas.innerHTML;
                }

                // 5. Update browser history & sync with Shopify Admin URL
                if (pushState) {
                    window.history.pushState({ shopifyNav: true, url: destinationHref }, '', destinationHref);
                }

                // 6. Execute scripts contained within the newly swapped content sequentially
                if (currentCanvas) {
                    const scripts = Array.from(currentCanvas.querySelectorAll('script'));
                    for (const oldScript of scripts) {
                        await new Promise(function(resolve) {
                            const newScript = document.createElement('script');
                            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                            newScript.textContent = oldScript.textContent;
                            if (newScript.src) {
                                if (newScript.src.includes('chart') && typeof window.Chart !== 'undefined') {
                                    resolve();
                                    return;
                                }
                                newScript.onload = function() { resolve(); };
                                newScript.onerror = function() { resolve(); };
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                            } else {
                                oldScript.parentNode.replaceChild(newScript, oldScript);
                                resolve();
                            }
                        });
                    }
                }

                // 7. Dispatch lifecycle events
                document.dispatchEvent(new CustomEvent('shopify:after-swap', {
                    detail: { url: destinationHref }
                }));

                // Auto-dismiss toasts on new view
                document.querySelectorAll('.toast-auto-dismiss').forEach(function(toast) {
                    initAutoDismissToast(toast, 3000);
                });

                // Re-check embed status if status elements exist
                if (typeof window.checkEmbedStatus === 'function') {
                    window.checkEmbedStatus();
                }

                // Scroll to top
                window.scrollTo(0, 0);

            } catch (err) {
                console.error('[SocialNest] Authenticated client navigation error:', err);
                if (typeof window.showToast === 'function') {
                    window.showToast('Unable to load page. Please check your connection and try again.', 'error');
                }
            } finally {
                isNavigating = false;
                if (canvas) {
                    canvas.style.opacity = '1';
                    canvas.style.pointerEvents = '';
                }
            }
        }

        window.shopifyNavigateTo = shopifyNavigateTo;

        // Continuous delegated interception for all clicks and form submissions
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href]');
            if (!link) return;
            const href = link.getAttribute('href');
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank' || link.target === '_top' || link.hasAttribute('download') || link.hasAttribute('data-no-spa')) {
                return;
            }

            const host = window.sessionStorage ? window.sessionStorage.getItem('shopify_host') : null;
            const shop = window.sessionStorage ? window.sessionStorage.getItem('shopify_shop') : null;

            try {
                const url = new URL(link.href, window.location.origin);
                if (url.origin === window.location.origin && url.pathname.startsWith('/shopify')) {
                    // Do not intercept binary file downloads or outside targets
                    if (url.pathname.startsWith('/shopify/gdpr/export')) {
                        return;
                    }

                    if (host && !url.searchParams.has('host')) url.searchParams.set('host', host);
                    if (shop && !url.searchParams.has('shop')) url.searchParams.set('shop', shop);

                    e.preventDefault();
                    shopifyNavigateTo(url.toString(), true);
                }
            } catch (err) {}
        }, true);

        // Synchronize back/forward browser and Shopify Admin navigation
        window.addEventListener('popstate', function(e) {
            if (window.location.pathname.startsWith('/shopify') && !window.location.pathname.startsWith('/shopify/gdpr/export')) {
                shopifyNavigateTo(window.location.href, false);
            }
        });

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
    <script>
        (function () {
            const EMBED_HANDLE = 'floating-social-dock';
            const TIMEOUT_MS = 5000;
            const MAX_ATTEMPTS = 3;
            const RETRY_DELAYS = [500, 1000, 2000];

            let currentRequestId = 0;
            let inFlightPromise = null;
            let lastKnownResult = null;

            function setEmbedStatus(status, message) {
                const isActive = status === 'active';
                const isAvailable = status === 'available';
                const isUnavailable = status === 'unavailable';
                const isUnknown = !isActive && !isAvailable && !isUnavailable;

                document.querySelectorAll('[data-embed-status-badge]').forEach((element) => {
                    element.classList.remove('is-active', 'is-inactive');

                    if (isActive) {
                        element.textContent = 'Storefront Widget Enabled';
                        element.classList.add('is-active');
                    } else if (isAvailable) {
                        element.textContent = 'Storefront Widget Not Enabled';
                        element.classList.add('is-inactive');
                    } else if (isUnavailable) {
                        element.textContent = 'Storefront Widget Unavailable';
                        element.classList.add('is-inactive');
                    } else {
                        element.textContent = message || 'Storefront status unavailable';
                    }
                });

                document.querySelectorAll('[data-embed-status-label]').forEach((element) => {
                    element.textContent = isActive
                        ? 'Enabled on published theme'
                        : isAvailable
                            ? 'Not enabled on published theme'
                            : isUnavailable
                                ? 'Unavailable on published theme'
                                : (message || 'Status could not be verified');
                    element.className = isActive ? 'sn-badge-on' : 'sn-badge-off';
                });

                document.querySelectorAll('[data-embed-enable]').forEach((button) => {
                    // Show enable button ONLY when embed is confirmed available to be enabled.
                    // Never show for active, unavailable, or unknown states.
                    button.hidden = !isAvailable;
                });

                const warning = document.getElementById('sn-embed-warning');
                if (warning) {
                    // Show warning banner ONLY when embed is confirmed available (not enabled on theme).
                    // Never show for active, unavailable, or unknown states.
                    warning.hidden = !isAvailable;
                }
            }

            async function executeStatusCheck(requestId) {
                for (let attempt = 1; attempt <= MAX_ATTEMPTS; attempt++) {
                    // Stale check: abort if a newer request was initiated
                    if (requestId !== currentRequestId) {
                        return;
                    }

                    try {
                        if (!window.shopify?.app?.extensions) {
                            throw new Error('Shopify App Bridge extensions API is not available yet');
                        }

                        let timerId = null;
                        const timeoutPromise = new Promise((_, reject) => {
                            timerId = setTimeout(() => {
                                reject(new Error('Shopify App Bridge extensions request timed out'));
                            }, TIMEOUT_MS);
                        });

                        let extensions;
                        try {
                            extensions = await Promise.race([
                                window.shopify.app.extensions(),
                                timeoutPromise
                            ]);
                        } finally {
                            if (timerId !== null) {
                                clearTimeout(timerId);
                                timerId = null;
                            }
                        }

                        // Stale check: abort if a newer request superseded this one while awaiting
                        if (requestId !== currentRequestId) {
                            return;
                        }

                        const themeExtensions = (Array.isArray(extensions) ? extensions : []).filter(
                            (extension) => extension && extension.type === 'theme_app_extension'
                        );

                        const embed = themeExtensions
                            .flatMap((extension) => (extension && Array.isArray(extension.activations) ? extension.activations : []))
                            .find((activation) => activation && activation.handle === EMBED_HANDLE);

                        if (!embed) {
                            console.warn('[SocialNest] Embed handle was not found in Shopify extension status.', {
                                expectedHandle: EMBED_HANDLE,
                                extensions
                            });
                            lastKnownResult = {
                                status: 'unknown',
                                message: 'SocialNest embed status not returned by Shopify'
                            };
                            setEmbedStatus(lastKnownResult.status, lastKnownResult.message);
                            return;
                        }

                        const resolvedStatus = embed.status || 'unknown';
                        lastKnownResult = { status: resolvedStatus };
                        setEmbedStatus(resolvedStatus);

                        console.info('[SocialNest] Published-theme embed status resolved:', {
                            handle: embed.handle,
                            status: resolvedStatus
                        });
                        return;

                    } catch (error) {
                        console.warn(`[SocialNest] Storefront status check attempt ${attempt}/${MAX_ATTEMPTS} failed:`, error.message);

                        if (requestId !== currentRequestId) {
                            return;
                        }

                        if (attempt < MAX_ATTEMPTS) {
                            const delay = RETRY_DELAYS[attempt - 1] || 1000;
                            await new Promise((resolve) => setTimeout(resolve, delay));
                        }
                    }
                }

                // All retries exhausted
                if (requestId === currentRequestId) {
                    lastKnownResult = {
                        status: 'unknown',
                        message: 'Could not verify storefront status'
                    };
                    setEmbedStatus(lastKnownResult.status, lastKnownResult.message);
                    console.error('[SocialNest] All storefront status check retries exhausted.');
                }
            }

            function checkEmbedStatus(options) {
                const force = options && options.force === true;
                if (force) {
                    lastKnownResult = null;
                }

                // 1. Immediately apply any verified cached status to newly rendered elements in the DOM
                if (lastKnownResult) {
                    setEmbedStatus(lastKnownResult.status, lastKnownResult.message);
                }

                // 2. If a request is already running, return the active promise to avoid duplicate in-flight requests
                if (inFlightPromise) {
                    return inFlightPromise;
                }

                // 3. Initiate bounded check with deduplication and stale request protection
                const requestId = ++currentRequestId;
                inFlightPromise = executeStatusCheck(requestId).finally(() => {
                    if (requestId === currentRequestId) {
                        inFlightPromise = null;
                    }
                });

                return inFlightPromise;
            }

            // Expose globally for SPA navigation engine and external triggers
            window.checkEmbedStatus = checkEmbedStatus;
            window.setEmbedStatus = setEmbedStatus;

            // Delegated event listener for [data-embed-enable] buttons (works across SPA navigations)
            document.addEventListener('click', async function (e) {
                const button = e.target.closest('[data-embed-enable]');
                if (!button || button.disabled) {
                    return;
                }

                const originalText = button.textContent.trim();
                button.disabled = true;
                button.textContent = 'Opening Theme Editor...';

                try {
                    const activationUrl = button.dataset.url;
                    if (!activationUrl) {
                        throw new Error('Activation URL is missing.');
                    }

                    const response = await shopifyAuthenticatedFetch(activationUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const data = await response.json();

                    if (!response.ok || !data.success || !data.redirect_url) {
                        throw new Error(data.message || 'Unable to open Theme Editor.');
                    }

                    window.open(data.redirect_url, '_top');
                } catch (error) {
                    console.error('[SocialNest] Embed activation failed:', error);
                    AppNotice.error(error.message || 'Unable to open Shopify Theme Editor.', 'Unable to Open Theme Editor');
                    button.disabled = false;
                    button.textContent = originalText;
                }
            });

            // Initial execution: handle both loading and interactive/complete DOM states
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    checkEmbedStatus();
                });
            } else {
                checkEmbedStatus();
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>