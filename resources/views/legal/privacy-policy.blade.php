@extends('layouts.public')

@section('title', 'Privacy Policy | Socialvire')
@push('styles')
<style>
.sn-legal-page{max-width:1180px;margin:34px auto 56px;color:#172033}
.sn-legal-hero{position:relative;overflow:hidden;border-radius:24px;padding:42px 46px;margin-bottom:28px;background:radial-gradient(circle at 85% 15%,rgba(129,140,248,.38),transparent 28%),linear-gradient(125deg,#111827 0%,#25245b 58%,#4338ca 100%);color:#fff;box-shadow:0 18px 45px rgba(31,41,90,.18)}
.sn-legal-hero:after{content:"";position:absolute;width:260px;height:260px;border:1px solid rgba(255,255,255,.13);border-radius:50%;right:-70px;bottom:-160px;box-shadow:0 0 0 28px rgba(255,255,255,.035),0 0 0 58px rgba(255,255,255,.025)}
.sn-legal-eyebrow{display:inline-flex;padding:7px 12px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.09);border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.13em;color:#e0e7ff}
.sn-legal-hero h1{font-size:clamp(30px,4vw,44px);line-height:1.12;letter-spacing:-.045em;font-weight:800;color:#fff;margin:20px 0 12px}
.sn-legal-hero p{max-width:760px;color:#dbe3ff;font-size:14px;line-height:1.8;margin:0}
.sn-legal-meta{display:inline-flex;margin-top:22px;padding:9px 13px;border-radius:10px;background:rgba(255,255,255,.1);color:#fff;font-size:12px;font-weight:700}
.sn-legal-layout{display:grid;grid-template-columns:255px minmax(0,1fr);gap:24px;align-items:start}
.sn-legal-sidebar{position:sticky;top:92px;background:#fff;border:1px solid #e6eaf2;border-radius:18px;padding:18px;box-shadow:0 5px 20px rgba(15,23,42,.035)}
.sn-legal-sidebar-title{font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#000000;margin:2px 8px 12px}
.sn-legal-nav{display:flex;flex-direction:column;gap:3px;max-height:calc(100vh - 180px);overflow:auto}
.sn-legal-nav-link{display:flex;align-items:flex-start;gap:10px;padding:9px 8px;border-radius:9px;color:#64748b;text-decoration:none;font-size:11px;line-height:1.45;transition:.18s}
.sn-legal-nav-link span{font-size:10px;font-weight:800;color:#818cf8;min-width:20px}
.sn-legal-nav-link b{font-weight:650}
.sn-legal-nav-link:hover{background:#f3f4ff;color:#4338ca}
.sn-legal-content{display:flex;flex-direction:column;gap:14px;min-width:0}
.sn-legal-card{display:grid;grid-template-columns:48px minmax(0,1fr);gap:16px;background:#fff;border:1px solid #e7eaf1;border-radius:17px;padding:24px 26px;box-shadow:0 4px 16px rgba(15,23,42,.025);scroll-margin-top:100px;transition:border-color .2s,box-shadow .2s}
.sn-legal-card:hover{border-color:#c7d2fe;box-shadow:0 9px 25px rgba(79,70,229,.06)}
.sn-legal-card-index{width:40px;height:40px;display:grid;place-items:center;border-radius:12px;background:#eef2ff;color:#4f46e5;font-size:12px;font-weight:800}
.sn-legal-card h2{font-size:16px;line-height:1.4;font-weight:800;letter-spacing:-.02em;color:#111827;margin:5px 0 13px}
.sn-legal-card p,.sn-legal-card li{font-size:13px;line-height:1.85;color:#526078}
.sn-legal-card p{margin:0 0 11px}.sn-legal-card p:last-child{margin-bottom:0}
.sn-legal-card ul{padding-left:19px;margin:7px 0 12px}.sn-legal-card li{padding-left:3px;margin:3px 0}
.sn-legal-card li::marker{color:#818cf8}.sn-legal-card a{color:#4f46e5;font-weight:700;text-decoration:none}.sn-legal-card a:hover{text-decoration:underline}
.sn-legal-footer{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:18px 4px 4px;color:#000000;font-size:11px}
.sn-legal-footer a{color:#4f46e5;text-decoration:none;font-weight:700}
@media(max-width:900px){.sn-legal-layout{grid-template-columns:1fr}.sn-legal-sidebar{position:static}.sn-legal-nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));max-height:220px}}
@media(max-width:600px){.sn-legal-hero{padding:28px 23px;border-radius:18px}.sn-legal-card{grid-template-columns:34px minmax(0,1fr);gap:11px;padding:18px 15px}.sn-legal-card-index{width:32px;height:32px;border-radius:9px}.sn-legal-nav{grid-template-columns:1fr 1fr}.sn-legal-nav-link{font-size:10px}.sn-legal-footer{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
<div class="sn-legal-page">
    <header class="sn-legal-hero">
        <span class="sn-legal-eyebrow">SOCIALVIRE / LEGAL CENTER</span>
        <h1>Privacy Policy</h1>
        <p><p> Socialvire ("we", "our", or "us") respects your privacy. This Privacy Policy explains how we collect, use, store, and protect information when you use our Shopify application. </p></p>
        <div class="sn-legal-meta">Effective date &middot; {{ now()->format('F d, Y') }}</div>
    </header>
    <div class="sn-legal-layout">
        <aside class="sn-legal-sidebar">
            <div class="sn-legal-sidebar-title">On this page</div>
            <nav class="sn-legal-nav" aria-label="Privacy Policy sections">
                <a class="sn-legal-nav-link" href="#legal-section-1"><span>01</span><b>Information We Collect</b></a><a class="sn-legal-nav-link" href="#legal-section-2"><span>02</span><b>How We Use Your Information</b></a><a class="sn-legal-nav-link" href="#legal-section-3"><span>03</span><b>Shopify Store Data</b></a><a class="sn-legal-nav-link" href="#legal-section-4"><span>04</span><b>Social Media Links</b></a><a class="sn-legal-nav-link" href="#legal-section-5"><span>05</span><b>Click Tracking and Analytics</b></a><a class="sn-legal-nav-link" href="#legal-section-6"><span>06</span><b>Cookies and Similar Technologies</b></a><a class="sn-legal-nav-link" href="#legal-section-7"><span>07</span><b>Data Sharing</b></a><a class="sn-legal-nav-link" href="#legal-section-8"><span>08</span><b>Data Retention and Deletion</b></a><a class="sn-legal-nav-link" href="#legal-section-9"><span>09</span><b>Data Security</b></a><a class="sn-legal-nav-link" href="#legal-section-10"><span>10</span><b>Your Privacy Rights</b></a><a class="sn-legal-nav-link" href="#legal-section-11"><span>11</span><b>Children's Privacy</b></a><a class="sn-legal-nav-link" href="#legal-section-12"><span>12</span><b>Changes to This Policy</b></a><a class="sn-legal-nav-link" href="#legal-section-13"><span>13</span><b>Contact Us</b></a>
            </nav>
        </aside>
        <div class="sn-legal-content">
            <section class="sn-legal-card" id="legal-section-1"><div class="sn-legal-card-index">01</div><div class="sn-legal-card-body"><h2>1. Information We Collect</h2><p>When you install or use Socialvire, we may collect the following information:</p>
            <ul>
                <li>Shopify store domain and store identification details.</li>
                <li>Store configuration and application settings.</li>
                <li>Social media platform names, URLs, labels, and display preferences configured by you.</li>
                <li>Application usage and technical information.</li>
                <li>Storefront interaction data, such as social link clicks.</li>
                <li>Technical information such as IP address and browser user agent, where applicable.</li>
            </ul></div></section><section class="sn-legal-card" id="legal-section-2"><div class="sn-legal-card-index">02</div><div class="sn-legal-card-body"><h2>2. How We Use Your Information</h2><p>We use collected information to:</p>
            <ul>
                <li>Provide and operate Socialvire features.</li>
                <li>Display social media icons and links on your Shopify storefront.</li>
                <li>Save and manage your application settings.</li>
                <li>Measure social link interactions and application performance.</li>
                <li>Identify technical issues and prevent misuse.</li>
                <li>Provide customer support and improve our services.</li>
            </ul></div></section><section class="sn-legal-card" id="legal-section-3"><div class="sn-legal-card-index">03</div><div class="sn-legal-card-body"><h2>3. Shopify Store Data</h2><p>
                Socialvire integrates with Shopify and may access store information
                required to provide its functionality.
            </p>
            <p>
                We only use Shopify data for application functionality and related
                services. We do not sell Shopify merchant data to third parties.
            </p>
            <p>
                Shopify's own privacy policy and terms may also apply to your use
                of the Shopify platform.
            </p></div></section><section class="sn-legal-card" id="legal-section-4"><div class="sn-legal-card-index">04</div><div class="sn-legal-card-body"><h2>4. Social Media Links</h2><p>
                Socialvire allows merchants to configure links to third-party
                social media platforms such as Facebook, Instagram, WhatsApp,
                Pinterest, and other supported platforms.
            </p>
            <p>
                These links direct visitors to external websites or services.
                Socialvire does not control the privacy practices or content
                of those third-party platforms.
            </p></div></section><section class="sn-legal-card" id="legal-section-5"><div class="sn-legal-card-index">05</div><div class="sn-legal-card-body"><h2>5. Click Tracking and Analytics</h2><p>
                Socialvire may collect interaction information when storefront
                visitors click configured social links. This information helps
                merchants understand engagement and helps us maintain the service.
            </p>
            <p>
                Technical information, including hashed IP addresses or browser
                user-agent information, may be processed where applicable for
                analytics, security, and abuse prevention.
            </p></div></section><section class="sn-legal-card" id="legal-section-6"><div class="sn-legal-card-index">06</div><div class="sn-legal-card-body"><h2>6. Cookies and Similar Technologies</h2><p>
                Socialvire may use cookies or similar technologies where necessary
                for application functionality, authentication, session management,
                and security.
            </p>
            <p>
                Third-party services linked through the application may use their
                own cookies and tracking technologies.
            </p></div></section><section class="sn-legal-card" id="legal-section-7"><div class="sn-legal-card-index">07</div><div class="sn-legal-card-body"><h2>7. Data Sharing</h2><p>
                We do not sell or rent your personal information. Information may
                be shared only when necessary:
            </p>
            <ul>
                <li>With service providers that help us operate Socialvire.</li>
                <li>To comply with applicable laws or valid legal requests.</li>
                <li>To protect our rights, users, application, and infrastructure.</li>
                <li>With your consent or at your direction.</li>
            </ul></div></section><section class="sn-legal-card" id="legal-section-8"><div class="sn-legal-card-index">08</div><div class="sn-legal-card-body"><h2>8. Data Retention and Deletion</h2><p>
                We retain information only for as long as reasonably necessary
                to provide our services, meet legal obligations, resolve disputes,
                and enforce our agreements.
            </p>
            <p>
                If you uninstall Socialvire or request deletion of your data,
                we will handle the applicable information in accordance with
                our data deletion procedures and legal obligations.
            </p>
            <p>
                Shopify-related customer data deletion requests will be handled
                in accordance with applicable Shopify requirements.
            </p></div></section><section class="sn-legal-card" id="legal-section-9"><div class="sn-legal-card-index">09</div><div class="sn-legal-card-body"><h2>9. Data Security</h2><p>
                We implement reasonable technical and organizational safeguards
                designed to protect information against unauthorized access,
                alteration, disclosure, or destruction.
            </p>
            <p>
                However, no internet-based service can guarantee absolute security.
            </p></div></section><section class="sn-legal-card" id="legal-section-10"><div class="sn-legal-card-index">10</div><div class="sn-legal-card-body"><h2>10. Your Privacy Rights</h2><p>
                Depending on applicable law, you may have the right to request
                access to, correction of, or deletion of your personal information.
            </p>
            <p>
                To submit a privacy-related request, contact us using the
                information provided below.
            </p></div></section><section class="sn-legal-card" id="legal-section-11"><div class="sn-legal-card-index">11</div><div class="sn-legal-card-body"><h2>11. Children's Privacy</h2><p>
                Socialvire is a business application and is not intended for
                children. We do not knowingly collect personal information
                directly from children.
            </p></div></section><section class="sn-legal-card" id="legal-section-12"><div class="sn-legal-card-index">12</div><div class="sn-legal-card-body"><h2>12. Changes to This Policy</h2><p>
                We may update this Privacy Policy from time to time.
                Any changes will be published on this page with an updated
                effective date.
            </p>
            <p>
                Continued use of Socialvire after changes are published
                constitutes use subject to the updated policy.
            </p></div></section><section class="sn-legal-card" id="legal-section-13"><div class="sn-legal-card-index">13</div><div class="sn-legal-card-body"><h2>13. Contact Us</h2><p>
                If you have questions about this Privacy Policy or want to
                submit a privacy request, contact us:
            </p>
            <ul>
                <li><strong>Application:</strong> Socialvire</li>
                <li><strong>Developer:</strong> Webvire</li>
                <li><strong>Email:</strong> {{ config('app.privacy_email', 'support@yourdomain.com') }}</li>
                <li><strong>Website:</strong>
                    <a href="https://socialvire.webvire.com">
                        https://socialvire.webvire.com
                    </a>
                </li>
            </ul></div></section>
        </div>
    </div>
</div>
@endsection
