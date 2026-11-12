{{-- Shared public website navbar used by Welcome, Privacy Policy and Terms pages. --}}
<style>
    .navbar {
        position: sticky;
        top: 0;
        z-index: 100;
        background: rgba(255, 255, 255, .92);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid #e2e8f0;
    }
    .navbar .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }
    .navbar .nav-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-height: 4.5rem;
        gap: 1rem;
    }
    .navbar .brand-logo {
        display: flex;
        align-items: center;
        gap: .75rem;
        color: #0f172a;
        text-decoration: none;
        font-weight: 800;
        font-size: 1.25rem;
        letter-spacing: -.025em;
        white-space: nowrap;
    }
    .navbar .brand-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #4f46e5, #3730a3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: 0 4px 10px rgba(79,70,229,.25);
        flex-shrink: 0;
    }
    .navbar .nav-links {
        display: flex;
        align-items: center;
        gap: 2rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .navbar .nav-link {
        color: #475569;
        text-decoration: none;
        font-size: .9375rem;
        font-weight: 500;
        transition: color .15s ease;
    }
    .navbar .nav-link:hover { color: #4f46e5; }
    .navbar .nav-actions { display:flex; align-items:center; gap:1rem; }
    .navbar .btn {
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:.5rem;
        padding:.625rem 1.25rem;
        font-size:.9375rem;
        font-weight:600;
        border-radius:.75rem;
        text-decoration:none;
        cursor:pointer;
        transition:all .2s ease;
        border:1px solid transparent;
        white-space:nowrap;
    }
    .navbar .btn-primary {
        background:#4f46e5;
        color:#fff;
        box-shadow:0 4px 14px rgba(79,70,229,.28);
    }
    .navbar .btn-primary:hover {
        background:#4338ca;
        transform:translateY(-1px);
        box-shadow:0 6px 18px rgba(79,70,229,.35);
    }
    .navbar .mobile-menu-btn {
        display:none;
        background:none;
        border:0;
        color:#0f172a;
        cursor:pointer;
        padding:.5rem;
    }
    .navbar .mobile-menu {
        display:none;
        flex-direction:column;
        gap:1rem;
        padding:1.25rem 1.5rem 1.5rem;
        background:#fff;
        border-bottom:1px solid #e2e8f0;
        box-shadow:0 10px 25px rgba(15,23,42,.06);
    }
    .navbar .mobile-menu .btn { align-self:flex-start; }
    @media(max-width:768px) {
        .navbar .nav-links { display:none; }
        .navbar .mobile-menu-btn { display:block; }
        .navbar .nav-actions > .btn { display:none; }
    }
</style>

<!-- Navbar -->
    <header class="navbar">
        <div class="container nav-content">
            <a href="{{ route('home') }}" class="brand-logo" aria-label="Socialvire Home">
                <div class="brand-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <span>Socialvire</span>
            </a>

            <nav>
                <ul class="nav-links">
                    <li><a href="{{ route('home') }}#features" class="nav-link">Features</a></li>
                    <li><a href="{{ route('home') }}#how-it-works" class="nav-link">How It Works</a></li>
                    <li><a href="{{ route('home') }}#preview" class="nav-link">Preview</a></li>
                    <li><a href="{{ route('home') }}#pricing" class="nav-link">Pricing</a></li>
                    <li><a href="{{ route('home') }}#faq" class="nav-link">FAQ</a></li>
                </ul>
            </nav>

            <div class="nav-actions">
                <a href="{{ route('home') }}#how-it-works" class="btn btn-primary">
                    <span>Get Started</span>
                </a>

                <button class="mobile-menu-btn" id="mobileMenuToggle" aria-label="Open navigation menu">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>
        </div>

        <div class="mobile-menu" id="mobileMenuDrawer">
            <a href="{{ route('home') }}#features" class="nav-link">Features</a>
            <a href="{{ route('home') }}#how-it-works" class="nav-link">How It Works</a>
            <a href="{{ route('home') }}#preview" class="nav-link">Preview</a>
            <a href="{{ route('home') }}#pricing" class="nav-link">Pricing</a>
            <a href="{{ route('home') }}#faq" class="nav-link">FAQ</a>
            <a href="{{ route('home') }}#how-it-works" class="btn btn-primary" style="margin-top: 0.5rem;">Get Started</a>
        </div>
    </header>
<script>
(function () {
    const toggle = document.getElementById('mobileMenuToggle');
    const drawer = document.getElementById('mobileMenuDrawer');

    if (!toggle || !drawer) return;

    toggle.addEventListener('click', function () {
        const isOpen = drawer.style.display === 'flex';
        drawer.style.display = isOpen ? 'none' : 'flex';
        toggle.setAttribute('aria-expanded', String(!isOpen));
    });

    drawer.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            drawer.style.display = 'none';
            toggle.setAttribute('aria-expanded', 'false');
        });
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 768) {
            drawer.style.display = 'none';
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>
