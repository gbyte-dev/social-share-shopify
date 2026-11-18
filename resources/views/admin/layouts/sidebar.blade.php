<aside class="admin-sidebar">

    @php
    $brandName = \App\Models\SiteSetting::getValue('brand_name', 'Socialvire');
    $brandLogo = \App\Models\SiteSetting::getValue('brand_logo');
    @endphp

    <div class="sidebar-brand">
        @if($brandLogo)
        <img
            src="{{ asset('public/' . $brandLogo) }}"
            alt="{{ $brandName }}"
            style="width:34px;height:34px;object-fit:contain;border-radius:8px;">
        @else
        <div class="brand-icon">
            {{ strtoupper(substr($brandName, 0, 1)) }}
        </div>
        @endif

        <div class="brand-name">{{ $brandName }}</div>
    </div>

    <div class="sidebar-label">WORKSPACE</div>

    <nav class="sidebar-nav">

        <a href="{{ route('admin.dashboard') }}"
            class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24">
                <rect x="3" y="3" width="8" height="8" rx="2" />
                <rect x="13" y="3" width="8" height="5" rx="2" />
                <rect x="13" y="10" width="8" height="11" rx="2" />
                <rect x="3" y="13" width="8" height="8" rx="2" />
            </svg>
            <span>Dashboard</span>
        </a>

        <a href="{{ route('admin.merchants.index') }}"
            class="sidebar-link {{ request()->routeIs('admin.merchants.*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                <circle cx="9" cy="7" r="4" />
                <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
            <span>Merchants</span>
        </a>

        <a href="#" class="sidebar-link">
            <svg viewBox="0 0 24 24">
                <rect x="2" y="5" width="20" height="14" rx="2" />
                <path d="M2 10h20" />
            </svg>
            <span>Subscriptions</span>
            <span class="nav-soon">Soon</span>
        </a>

        <a href="#" class="sidebar-link">
            <svg viewBox="0 0 24 24">
                <path d="M12 3v3m0 12v3M3 12h3m12 0h3" />
                <circle cx="12" cy="12" r="7" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <span>App Settings</span>
            <span class="nav-soon">Soon</span>
        </a>


        <a href="{{ route('admin.settings.branding') }}"
            class="sidebar-link {{ request()->routeIs('admin.settings.branding*') ? 'active' : '' }}">
            <svg viewBox="0 0 24 24">
                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                <path d="m16 3 5 5" />
                <path d="m10 14 8-8 3 3-8 8-4 1z" />
            </svg>
            <span>Website Branding</span>
        </a>


    </nav>

    <div class="sidebar-bottom">
        <div class="sidebar-admin">
            <div class="admin-avatar">
                {{ strtoupper(substr(auth('admin')->user()->name, 0, 1)) }}
            </div>

            <div class="admin-info">
                <strong>{{ auth('admin')->user()->name }}</strong>
                <span>Administrator</span>
            </div>
        </div>
    </div>

</aside>