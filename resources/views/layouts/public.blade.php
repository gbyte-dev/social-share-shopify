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
        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background: #f8fafc;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        main.public-main {
            width: 100%;
            padding: 34px 24px 56px;
        }

        @media (max-width: 600px) {
            main.public-main {
                padding: 22px 14px 40px;
            }
        }

        /* Shared Public Footer */

        .public-footer {
            width: 100%;
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            color: #475569;
            padding: 38px 0 18px;
        }

        .public-footer-container {
            width: 95%;
            max-width: 1440px;
            margin: 0 auto;
        }

        /* Three equal columns across the full container */
        .public-footer .footer-grid {
            display: grid;
            width: 100%;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
            align-items: start;
            padding: 0 0 30px;
        }

        .public-footer .footer-grid>div:nth-child(1) {
            justify-self: start;
        }

        .public-footer .footer-grid>div:nth-child(2) {
            justify-self: center;
        }

        .public-footer .footer-grid>div:nth-child(3) {
            justify-self: end;
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
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            color: #fff;
            background: linear-gradient(135deg, #4f46e5, #3730a3);
            box-shadow: 0 4px 10px rgba(79, 70, 229, .22);
        }

        .public-footer .footer-brand-desc {
            max-width: 370px;
            margin: 12px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        .public-footer .footer-title {
            margin: 4px 0 16px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .04em;
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
            color: #64748b;
            font-size: 14px;
            text-decoration: none;
            transition: color .15s ease;
        }

        .public-footer .footer-links-list a:hover {
            color: #4f46e5;
        }

        .public-footer .footer-legal-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 42px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }

        .public-footer .footer-legal-bar p {
            margin: 0;
            color: #64748b;
            font-size: 12px;
        }

        /* Tablet */
        @media (max-width: 768px) {
            .public-footer {
                padding: 30px 0 16px;
            }

            .public-footer-container {
                width: 92%;
            }

            .public-footer .footer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 26px 20px;
                padding-bottom: 24px;
            }

            .public-footer .footer-grid>div:first-child {
                grid-column: 1 / -1;
            }

            .public-footer .footer-grid>div:nth-child(2),
            .public-footer .footer-grid>div:nth-child(3) {
                justify-self: start;
            }
        }

        /* Mobile */
        @media (max-width: 480px) {
            .public-footer .footer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 22px 14px;
            }

            .public-footer .footer-title {
                font-size: 11px;
            }

            .public-footer .footer-links-list a {
                font-size: 12px;
            }

            .public-footer .footer-legal-bar {
                flex-wrap: wrap;
                gap: 8px;
            }
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