<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Dashboard') | Socialvire Admin</title>

    @php
    $brandFaviconUrl = \App\Models\SiteSetting::getAssetUrl('brand_favicon');
    @endphp

    @if($brandFaviconUrl)
    @php
    $cleanFaviconPath = ltrim(parse_url($brandFaviconUrl, PHP_URL_PATH), '/');
    $extension = strtolower(pathinfo($cleanFaviconPath, PATHINFO_EXTENSION));

    $faviconType = match ($extension) {
        'ico' => 'image/x-icon',
        'webp' => 'image/webp',
        default => 'image/png',
    };
    @endphp

    <link
        rel="icon"
        type="{{ $faviconType }}"
        href="{{ $brandFaviconUrl }}">
    @else
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    @endif


    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #008060;
            --text: #202223;
            --muted: #6d7175;
            --border: #e1e3e5;
            --background: #f6f6f7;
            --sidebar-width: 225px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            -webkit-font-smoothing: antialiased;
        }

        .admin-sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            width: var(--sidebar-width);
            background: #fff;
            border-right: 1px solid var(--border);
            padding: 22px 15px;
            display: flex;
            flex-direction: column;
            z-index: 100;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 8px 28px;
        }

        .brand-icon {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            background: var(--primary);
            color: white;
            border-radius: 9px;
            font-size: 18px;
            font-weight: 800;
        }

        .brand-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .brand-name span {
            color: var(--primary);
        }

        .sidebar-label {
            padding: 0 10px;
            margin-bottom: 10px;
            color: #92969a;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .8px;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 11px;
            min-height: 39px;
            padding: 0 11px;
            border-radius: 7px;
            color: #606467;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: .2s;
        }

        .sidebar-link svg {
            width: 17px;
            height: 17px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        .sidebar-link:hover {
            background: #f6f6f7;
            color: var(--text);
        }

        .sidebar-link.active {
            background: #eaf5f0;
            color: var(--primary);
        }

        .nav-soon {
            margin-left: auto;
            color: #92969a;
            font-size: 9px;
            font-weight: 600;
        }

        .sidebar-bottom {
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid var(--border);
        }

        .sidebar-admin {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 5px;
        }

        .admin-avatar {
            width: 34px;
            height: 34px;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: #eaf5f0;
            color: var(--primary);
            font-weight: 800;
        }

        .admin-info {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .admin-info strong {
            font-size: 11px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .admin-info span {
            color: var(--muted);
            font-size: 10px;
        }

        .admin-main {
            min-height: 100vh;
            margin-left: var(--sidebar-width);
        }

        .admin-topbar {
            position: sticky;
            top: 0;
            z-index: 50;
            height: 62px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 27px;
            background: #fff;
            border-bottom: 1px solid var(--border);
        }

        .topbar-title {
            font-size: 14px;
            font-weight: 800;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .admin-status {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #008060;
            font-size: 10px;
            font-weight: 700;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            background: #008060;
            border-radius: 50%;
        }

        .logout-btn {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: #fff;
            color: var(--text);
            font-family: inherit;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #f6f6f7;
        }

        .admin-content {
            max-width: 1500px;
            margin: 0 auto;
            padding: 26px;
        }

        .mobile-brand {
            display: none;
        }

        @media(max-width: 760px) {
            .admin-sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 13px 15px;
                border-right: 0;
                border-bottom: 1px solid var(--border);
            }

            .sidebar-brand {
                padding: 0 5px 12px;
            }

            .sidebar-label,
            .sidebar-bottom {
                display: none;
            }

            .sidebar-nav {
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: 2px;
            }

            .sidebar-link {
                white-space: nowrap;
                min-height: 36px;
            }

            .nav-soon {
                display: none;
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-topbar {
                height: 56px;
                padding: 0 16px;
            }

            .admin-content {
                padding: 17px;
            }
        }

        @media(max-width: 420px) {
            .admin-status {
                display: none;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

    @include('admin.layouts.sidebar')

    <main class="admin-main">

        <header class="admin-topbar">
            <div class="topbar-title">
                @yield('heading', 'Dashboard')
            </div>

            <div class="topbar-right">
                <div class="admin-status">
                    <span class="status-dot"></span>
                    System Active
                </div>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        <section class="admin-content">
            @yield('content')
        </section>

    </main>

    @stack('scripts')

</body>

</html>