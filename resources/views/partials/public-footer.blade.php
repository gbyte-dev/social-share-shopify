
@php
    use App\Models\SiteSetting;

    $brandName = SiteSetting::getValue('brand_name', 'Socialvire');
    $brandLogo = SiteSetting::getValue('brand_logo');

    $footerDescription = SiteSetting::getValue(
        'footer_description',
        'Customizable floating social media icons and convenient product-sharing options built for Shopify stores.'
    );

    $navigationTitle = SiteSetting::getValue('footer_navigation_title', 'Navigation');
    $getStartedTitle = SiteSetting::getValue('footer_cta_title', 'Get Started');

    $navigationLinks = json_decode(
        SiteSetting::getValue('footer_navigation_links', '[]'),
        true
    );

    $getStartedLinks = json_decode(
        SiteSetting::getValue('footer_cta_links', '[]'),
        true
    );

    if (!is_array($navigationLinks) || empty($navigationLinks)) {
        $navigationLinks = [
            ['label' => 'Features', 'url' => route('home') . '#features'],
            ['label' => 'Pricing', 'url' => route('home') . '#pricing'],
            ['label' => 'FAQ', 'url' => route('home') . '#faq'],
        ];
    }

    if (!is_array($getStartedLinks) || empty($getStartedLinks)) {
        $getStartedLinks = [
            ['label' => 'How It Works', 'url' => route('home') . '#how-it-works'],
            ['label' => 'Privacy Policy', 'url' => route('privacy.policy')],
            ['label' => 'Terms & Conditions', 'url' => route('terms.conditions')],
        ];
    }
@endphp

<footer class="public-footer">
    <div class="public-footer-container">
        <div class="footer-grid">

            {{-- Brand Section --}}
            <div>
                <a href="{{ route('home') }}"
                   class="brand-logo"
                   aria-label="{{ $brandName }} Home">

                    @if($brandLogo)
                        <img
                            src="{{ asset('public/' . $brandLogo) }}"
                            alt="{{ $brandName }}"
                            style="width:38px;height:38px;object-fit:contain;border-radius:9px;"
                        >
                    @else
                        <span class="brand-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                        </span>
                    @endif

                    <span>{{ $brandName }}</span>
                </a>

                <p class="footer-brand-desc">
                    {{ $footerDescription }}
                </p>
            </div>

            {{-- Navigation --}}
            <div>
                <h4 class="footer-title">{{ $navigationTitle }}</h4>

                <ul class="footer-links-list">
                    @foreach($navigationLinks as $link)
                        @if(!empty($link['label']) && !empty($link['url']))
                            <li>
                                <a href="{{ $link['url'] }}">
                                    {{ $link['label'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>

            {{-- Get Started --}}
            <div>
                <h4 class="footer-title">{{ $getStartedTitle }}</h4>

                <ul class="footer-links-list">
                    <li><a href="{{ route('home') }}#how-it-works">How It Works</a></li>
                    <li><a href="{{ route('support.public') }}">Support &amp; Help Center</a></li>
                    <li><a href="{{ route('privacy.policy') }}">Privacy Policy</a></li>
                    <li><a href="{{ route('terms.conditions') }}">Terms &amp; Conditions</a></li>
                </ul>
            </div>

        </div>

        {{-- Copyright --}}
        <div class="footer-legal-bar">
            <p>
                &copy; {{ date('Y') }}
                {{ $brandName }}. All rights reserved.
            </p>
        </div>
    </div>
</footer>
