<footer class="public-footer">
    <div class="public-footer-container">
        <div class="footer-grid">
            <div>
                <a href="{{ route('home') }}" class="brand-logo" aria-label="Socialvire Home">
                    <span class="brand-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    </span>
                    <span>Socialvire</span>
                </a>
                <p class="footer-brand-desc">Customizable floating social media icons and convenient product-sharing options built for Shopify stores.</p>
            </div>

            <div>
                <h4 class="footer-title">Navigation</h4>
                <ul class="footer-links-list">
                    <li><a href="{{ route('home') }}#features">Features</a></li>
                    <li><a href="{{ route('home') }}#how-it-works">How It Works</a></li>
                    <li><a href="{{ route('home') }}#preview">Preview</a></li>
                    <li><a href="{{ route('home') }}#pricing">Pricing</a></li>
                    <li><a href="{{ route('home') }}#faq">FAQ</a></li>
                </ul>
            </div>

            <div>
                <h4 class="footer-title">Get Started</h4>
                <ul class="footer-links-list">
                    <li><a href="{{ route('home') }}#how-it-works">How It Works</a></li>
                    <li><a href="{{ route('privacy.policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('terms.conditions') }}">Terms &amp; Conditions</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-legal-bar">
            <p>&copy; {{ date('Y') }} Socialvire. All rights reserved.</p>
            <p>Socialvire is an independent application for Shopify stores and is not affiliated with Shopify Inc.</p>
        </div>
    </div>
</footer>
