<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Install SocialNest - Shopify App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --shopify-green: #008060;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --danger-bg: rgba(239, 68, 68, 0.15);
            --danger-border: rgba(239, 68, 68, 0.3);
            --danger-text: #fca5a5;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        body {
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .container {
            width: 100%;
            max-width: 500px;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.875rem;
            background: rgba(79, 70, 229, 0.2);
            border: 1px solid rgba(99, 102, 241, 0.3);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #a5b4fc;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        .app-title {
            font-size: 1.875rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: #ffffff;
            margin-bottom: 0.5rem;
        }

        .app-subtitle {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .alert {
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .alert-error {
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            color: var(--danger-text);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.875rem 1rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0.75rem;
            color: #ffffff;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            outline: none;
        }

        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            background: rgba(15, 23, 42, 0.85);
        }

        .input-hint {
            display: block;
            margin-top: 0.375rem;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .btn-submit {
            width: 100%;
            padding: 0.875rem 1.25rem;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 0.75rem;
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(79, 70, 229, 0.45);
        }

        .features-list {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: grid;
            gap: 0.75rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.8125rem;
            color: var(--text-muted);
        }

        .feature-icon {
            width: 18px;
            height: 18px;
            color: #10b981;
            flex-shrink: 0;
        }

        .footer-note {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="brand-header">
                <div class="brand-badge">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    Official Shopify Embedded App
                </div>
                <h1 class="app-title">Connect SocialNest</h1>
                <p class="app-subtitle">Enter your Shopify store domain to install SocialNest and synchronize your commerce ecosystem.</p>
            </div>

            @if (!empty($error))
                <div class="alert alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>{{ $error }}</div>
                </div>
            @endif

            <form action="{{ route('shopify.install') }}" method="GET">
                <div class="form-group">
                    <label for="shop" class="form-label">Shopify Store Domain</label>
                    <div class="input-wrapper">
                        <input
                            type="text"
                            id="shop"
                            name="shop"
                            value="{{ old('shop', $shop ?? '') }}"
                            placeholder="your-store.myshopify.com"
                            class="form-input"
                            required
                            autofocus
                        >
                    </div>
                    <span class="input-hint">Enter your full <code>your-store.myshopify.com</code> address or store handle</span>
                </div>

                <button type="submit" class="btn-submit">
                    <span>Authorize & Install App</span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>

            <div class="features-list">
                <div class="feature-item">
                    <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Compliant with Shopify OAuth 2.0 & Token Exchange security</span>
                </div>
                <div class="feature-item">
                    <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Store isolation with encrypted credential persistence</span>
                </div>
                <div class="feature-item">
                    <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    <span>Seamless embedded experience inside Shopify Admin</span>
                </div>
            </div>
        </div>

        <p class="footer-note">SocialNest &bull; Secure Shopify Integration &bull; Laravel {{ app()->version() }}</p>
    </div>
</body>
</html>
