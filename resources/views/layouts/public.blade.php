<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Socialvire')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        main.public-main { width:100%; padding:0; margin:0; }

    .public-footer {
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        padding: 56px 0 24px;
        color: #475569;
        font-size: 14px;
    }
    .public-footer .public-footer-container {
        width: min(calc(100% - 22px), 1100px);
        margin: 0 auto;
    }
    .public-footer .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 48px;
        margin-bottom: 42px;
    }
    .public-footer .brand-logo {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        color: #0f172a;
        text-decoration: none;
        font-size: 20px;
        font-weight: 800;
    }
    .public-footer .brand-icon {
        width: 36px;
        height: 36px;
        display: grid;
        place-items: center;
        border-radius: 10px;
        color: #fff;
        background: linear-gradient(135deg, #4f46e5, #3730a3);
    }
    .public-footer .footer-brand-desc {
        max-width: 340px;
        margin-top: 12px;
        line-height: 1.65;
        color: #64748b;
    }
    .public-footer .footer-title {
        margin: 0 0 16px;
        color: #0f172a;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .05em;
        text-transform: uppercase;
    }
    .public-footer .footer-links-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .public-footer .footer-links-list a {
        color: #475569;
        text-decoration: none;
        transition: color .15s ease;
    }
    .public-footer .footer-links-list a:hover { color: #4f46e5; }
    .public-footer .footer-legal-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        padding-top: 22px;
        border-top: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 12px;
    }
    .public-footer .footer-legal-bar p { margin: 0; }
    @media (max-width: 768px) {
        .public-footer { padding: 40px 0 20px; }
        .public-footer .footer-grid { grid-template-columns: 1fr; gap: 28px; margin-bottom: 30px; }
        .public-footer .public-footer-container { width: min(calc(100% - 22px), 1100px); }
        .public-footer .footer-legal-bar { align-items: flex-start; flex-direction: column; }
    }

    </style>
    @stack('styles')
</head>
<body>
    @include('partials.public-navbar')
    <main class="public-main">
        @yield('content')
    </main>
    @include('partials.public-footer')
    @stack('scripts')
</body>
</html>
