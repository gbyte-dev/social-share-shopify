@extends('layouts.public')

@section('title', 'Terms & Conditions | Socialvire')
@push('styles')
<style>
.sn-legal-page{max-width:1180px;margin:0 auto;color:#172033}
/* .sn-legal-hero{position:relative;overflow:hidden;border-radius:24px;padding:42px 46px;margin-bottom:28px;background:radial-gradient(circle at 85% 15%,rgba(129,140,248,.38),transparent 28%),linear-gradient(125deg,#111827 0%,#25245b 58%,#4338ca 100%);color:#fff;box-shadow:0 18px 45px rgba(31,41,90,.18)}
.sn-legal-hero:after{content:"";position:absolute;width:260px;height:260px;border:1px solid rgba(255,255,255,.13);border-radius:50%;right:-70px;bottom:-160px;box-shadow:0 0 0 28px rgba(255,255,255,.035),0 0 0 58px rgba(255,255,255,.025)}
.sn-legal-eyebrow{display:inline-flex;padding:7px 12px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.09);border-radius:999px;font-size:11px;font-weight:800;letter-spacing:.13em;color:#e0e7ff}
.sn-legal-hero h1{font-size:clamp(30px,4vw,44px);line-height:1.12;letter-spacing:-.045em;font-weight:800;color:#fff;margin:20px 0 12px}
.sn-legal-hero p{max-width:760px;color:#dbe3ff;font-size:14px;line-height:1.8;margin:0} */
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


/* Simple Legal Page Header */
.sn-legal-hero {
    position: relative;
    width: 100%;
    padding: 8px 0 22px;
    margin: 0 0 26px;
    background: transparent;
    color: #111827;
    border: none;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 0;
    box-shadow: none;
    overflow: visible;
}

.sn-legal-hero::after {
    display: none;
    content: none;
}

.sn-legal-hero h1 {
    margin: 0;
    color: #111827;
    font-size: clamp(25px, 3vw, 34px);
    line-height: 1.25;
    font-weight: 800;
    letter-spacing: -0.8px;
}

.sn-legal-eyebrow {
    display: none;
}

.sn-legal-hero p {
    margin: 8px 0 0;
    max-width: 760px;
    color: #64748b;
    font-size: 14px;
    line-height: 1.6;
}

.sn-legal-meta {
    display: inline-flex;
    margin-top: 12px;
    padding: 0;
    background: transparent;
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
}

/* Responsive */
@media (max-width: 600px) {
    .sn-legal-hero {
        padding: 5px 0 17px;
        margin-bottom: 20px;
    }

    .sn-legal-hero h1 {
        font-size: 25px;
        line-height: 1.3;
    }

    .sn-legal-hero p {
        font-size: 13px;
    }
}

@media(max-width:900px){.sn-legal-layout{grid-template-columns:1fr}.sn-legal-sidebar{position:static}.sn-legal-nav{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));max-height:220px}}
@media(max-width:600px){.sn-legal-hero{padding:28px 23px;border-radius:18px}.sn-legal-card{grid-template-columns:34px minmax(0,1fr);gap:11px;padding:18px 15px}.sn-legal-card-index{width:32px;height:32px;border-radius:9px}.sn-legal-nav{grid-template-columns:1fr 1fr}.sn-legal-nav-link{font-size:10px}.sn-legal-footer{align-items:flex-start;flex-direction:column}}
</style>
@endpush

@section('content')
<div class="sn-legal-page">
    <header class="sn-legal-hero">
        <h1>Terms & Conditions</h1>
    </header>
    <div class="sn-legal-layout">
        <aside class="sn-legal-sidebar">
            <div class="sn-legal-sidebar-title">On this page</div>
            <nav class="sn-legal-nav" aria-label="Terms & Conditions sections">
                <a class="sn-legal-nav-link" href="#legal-section-1"><span>01</span><b>About Socialvire</b></a><a class="sn-legal-nav-link" href="#legal-section-2"><span>02</span><b>Eligibility and Account</b></a><a class="sn-legal-nav-link" href="#legal-section-3"><span>03</span><b>Application Features</b></a><a class="sn-legal-nav-link" href="#legal-section-4"><span>04</span><b>Acceptable Use</b></a><a class="sn-legal-nav-link" href="#legal-section-5"><span>05</span><b>Merchant Responsibilities</b></a><a class="sn-legal-nav-link" href="#legal-section-6"><span>06</span><b>Subscription and Billing</b></a><a class="sn-legal-nav-link" href="#legal-section-7"><span>07</span><b>Cancellation and Refunds</b></a><a class="sn-legal-nav-link" href="#legal-section-8"><span>08</span><b>Third-Party Services</b></a><a class="sn-legal-nav-link" href="#legal-section-9"><span>09</span><b>Intellectual Property</b></a><a class="sn-legal-nav-link" href="#legal-section-10"><span>10</span><b>Availability and Service Changes</b></a><a class="sn-legal-nav-link" href="#legal-section-11"><span>11</span><b>Disclaimer of Warranties</b></a><a class="sn-legal-nav-link" href="#legal-section-12"><span>12</span><b>Limitation of Liability</b></a><a class="sn-legal-nav-link" href="#legal-section-13"><span>13</span><b>Suspension and Termination</b></a><a class="sn-legal-nav-link" href="#legal-section-14"><span>14</span><b>Privacy</b></a><a class="sn-legal-nav-link" href="#legal-section-15"><span>15</span><b>Changes to These Terms</b></a><a class="sn-legal-nav-link" href="#legal-section-16"><span>16</span><b>Governing Law</b></a><a class="sn-legal-nav-link" href="#legal-section-17"><span>17</span><b>Contact Information</b></a>
            </nav>
        </aside>
        <div class="sn-legal-content">
            <section class="sn-legal-card" id="legal-section-1"><div class="sn-legal-card-index">01</div><div class="sn-legal-card-body"><h2>1. About Socialvire</h2><p>
                Socialvire is a Shopify application designed to help merchants
                display social media icons, contact buttons, and product-sharing
                options on their online stores.
            </p>
            <p>
                The application is provided by Webvire ("we", "our", or "us").
            </p></div></section><section class="sn-legal-card" id="legal-section-2"><div class="sn-legal-card-index">02</div><div class="sn-legal-card-body"><h2>2. Eligibility and Account</h2><ul>
                <li>You must have a valid Shopify store to use Socialvire.</li>
                <li>You must have the authority to install and manage applications for your store.</li>
                <li>You are responsible for maintaining the security of your Shopify account.</li>
                <li>You are responsible for all activities performed through your account.</li>
                <li>You must provide accurate information when using the application.</li>
            </ul></div></section><section class="sn-legal-card" id="legal-section-3"><div class="sn-legal-card-index">03</div><div class="sn-legal-card-body"><h2>3. Application Features</h2><p>Depending on your subscription plan, Socialvire may provide:</p>
            <ul>
                <li>Floating social media icons and contact buttons.</li>
                <li>Social media platform link configuration.</li>
                <li>Customizable icon appearance and positioning.</li>
                <li>Hover animations and display settings.</li>
                <li>Product sharing functionality.</li>
                <li>Social link interaction tracking and analytics.</li>
            </ul>
            <p>
                Features may vary depending on the selected subscription plan.
                We reserve the right to modify, improve, or discontinue features
                where reasonably necessary.
            </p></div></section><section class="sn-legal-card" id="legal-section-4"><div class="sn-legal-card-index">04</div><div class="sn-legal-card-body"><h2>4. Acceptable Use</h2><p>You agree not to:</p>
            <ul>
                <li>Use Socialvire for unlawful or fraudulent activities.</li>
                <li>Attempt to access systems or data without authorization.</li>
                <li>Reverse engineer, exploit, or interfere with the application.</li>
                <li>Use the application to distribute malicious content.</li>
                <li>Misrepresent your business or impersonate another individual or organization.</li>
                <li>Abuse application functionality or attempt to disrupt its infrastructure.</li>
            </ul></div></section><section class="sn-legal-card" id="legal-section-5"><div class="sn-legal-card-index">05</div><div class="sn-legal-card-body"><h2>5. Merchant Responsibilities</h2><p>You are responsible for:</p>
            <ul>
                <li>Ensuring that configured social media links are accurate and lawful.</li>
                <li>Having the necessary rights to use any content, logos, or branding you upload or display.</li>
                <li>Ensuring your storefront complies with applicable laws and regulations.</li>
                <li>Managing your Shopify store and its customer-facing content.</li>
                <li>Obtaining any consent required for tracking or analytics on your storefront.</li>
            </ul></div></section><section class="sn-legal-card" id="legal-section-6"><div class="sn-legal-card-index">06</div><div class="sn-legal-card-body"><h2>6. Subscription and Billing</h2><p>
                Socialvire may offer free and paid subscription plans.
                Paid plans may provide additional features or usage limits.
            </p>
            <ul>
                <li>Subscription prices and billing intervals will be displayed before purchase.</li>
                <li>Payments and applicable billing processes may be handled through Shopify.</li>
                <li>Access to paid features depends on the status of your subscription.</li>
                <li>Applicable taxes may be added according to relevant laws and billing rules.</li>
                <li>Changes to plan features or pricing will be communicated where required.</li>
            </ul>
            <p>
                Your subscription is subject to the billing terms presented
                during the Shopify subscription approval process.
            </p></div></section><section class="sn-legal-card" id="legal-section-7"><div class="sn-legal-card-index">07</div><div class="sn-legal-card-body"><h2>7. Cancellation and Refunds</h2><p>
                You may uninstall Socialvire from your Shopify store to stop
                using the application. Paid subscription cancellation and
                billing are subject to Shopify's applicable billing process.
            </p>
            <p>
                Refund eligibility will be determined according to applicable
                law and the refund terms communicated at the time of purchase.
                Uninstalling the application does not automatically guarantee
                a refund for previously charged amounts.
            </p></div></section><section class="sn-legal-card" id="legal-section-8"><div class="sn-legal-card-index">08</div><div class="sn-legal-card-body"><h2>8. Third-Party Services</h2><p>
                Socialvire may integrate with Shopify and link to third-party
                platforms such as WhatsApp, Instagram, Facebook, Pinterest,
                and other social media services.
            </p>
            <p>
                These third-party services operate independently and are
                governed by their own terms, policies, and availability.
                We are not responsible for their content, services, policies,
                or actions.
            </p></div></section><section class="sn-legal-card" id="legal-section-9"><div class="sn-legal-card-index">09</div><div class="sn-legal-card-body"><h2>9. Intellectual Property</h2><p>
                Socialvire, including its software, design, branding, and
                application materials, is owned by or licensed to Webvire
                and is protected by applicable intellectual property laws.
            </p>
            <p>
                You retain ownership of your store content, configured links,
                and materials that you provide to the application.
            </p>
            <p>
                These Terms do not transfer ownership of either party's
                intellectual property.
            </p></div></section><section class="sn-legal-card" id="legal-section-10"><div class="sn-legal-card-index">10</div><div class="sn-legal-card-body"><h2>10. Availability and Service Changes</h2><p>
                We aim to keep Socialvire available and functioning reliably.
                However, uninterrupted or error-free service cannot be guaranteed.
            </p>
            <p>
                Service availability may be affected by maintenance, technical
                issues, Shopify platform changes, third-party outages, or
                circumstances beyond our reasonable control.
            </p></div></section><section class="sn-legal-card" id="legal-section-11"><div class="sn-legal-card-index">11</div><div class="sn-legal-card-body"><h2>11. Disclaimer of Warranties</h2><p>
                To the extent permitted by applicable law, Socialvire is
                provided on an "as is" and "as available" basis without
                warranties of any kind, whether express or implied.
            </p>
            <p>
                We do not guarantee that the application will meet every
                business requirement or produce any particular commercial result.
            </p></div></section><section class="sn-legal-card" id="legal-section-12"><div class="sn-legal-card-index">12</div><div class="sn-legal-card-body"><h2>12. Limitation of Liability</h2><p>
                To the maximum extent permitted by applicable law, Webvire
                will not be liable for indirect, incidental, special,
                consequential, or punitive damages, including loss of profits,
                revenue, data, or business opportunities arising from the
                use of or inability to use Socialvire.
            </p>
            <p>
                Nothing in these Terms excludes liability that cannot legally
                be excluded under applicable law.
            </p></div></section><section class="sn-legal-card" id="legal-section-13"><div class="sn-legal-card-index">13</div><div class="sn-legal-card-body"><h2>13. Suspension and Termination</h2><p>
                We may suspend or terminate access to Socialvire if:
            </p>
            <ul>
                <li>You violate these Terms.</li>
                <li>Your use creates a security or operational risk.</li>
                <li>We are legally required to do so.</li>
                <li>Continued service is no longer commercially or technically feasible.</li>
            </ul>
            <p>
                You may stop using Socialvire at any time by uninstalling
                the application from your Shopify store.
            </p></div></section><section class="sn-legal-card" id="legal-section-14"><div class="sn-legal-card-index">14</div><div class="sn-legal-card-body"><h2>14. Privacy</h2><p>
                Your use of Socialvire is also governed by our
                <a href="{{ route('privacy.policy') }}">Privacy Policy</a>,
                which explains how we collect, use, and protect information.
            </p></div></section><section class="sn-legal-card" id="legal-section-15"><div class="sn-legal-card-index">15</div><div class="sn-legal-card-body"><h2>15. Changes to These Terms</h2><p>
                We may update these Terms from time to time.
                Updated Terms will be published on this page with a revised
                effective date.
            </p>
                Continued use of Socialvire after updated Terms become
                effective constitutes acceptance of those changes,
                to the extent permitted by applicable law.</div></section><section class="sn-legal-card" id="legal-section-16"><div class="sn-legal-card-index">16</div><div class="sn-legal-card-body"><h2>16. Governing Law</h2><p>
                These Terms shall be governed by the laws of India,
                subject to applicable consumer protection and other
                mandatory legal requirements.
            </p></div></section><section class="sn-legal-card" id="legal-section-17"><div class="sn-legal-card-index">17</div><div class="sn-legal-card-body"><h2>17. Contact Information</h2><p>
                For questions regarding these Terms, contact us:
            </p>
            <ul>
                <li><strong>Application:</strong> Socialvire</li>
                <li><strong>Developer:</strong> Webvire</li>
                <li><strong>Email:</strong> {{ config('app.privacy_email', 'support@yourdomain.com') }}</li>
                <li>
                    <strong>Website:</strong>
                    <a href="https://socialvire.webvire.com">
                        https://socialvire.webvire.com
                    </a>
                </li>
            </ul></div></section>
        </div>
    </div>
</div>
@endsection
