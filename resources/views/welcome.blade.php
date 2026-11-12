@extends('layouts.public')

@section('title', 'Socialvire - Social Sharing & Floating Media Icons for Shopify')

@push('styles')
<style>

        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-light: #eef2ff;
            --primary-border: #c7d2fe;
            --navy-dark: #0f172a;
            --navy-card: #1e293b;
            --text-main: #0f172a;
            --text-muted: #475569;
            --text-subtle: #64748b;
            --border-subtle: #e2e8f0;
            --bg-page: #f8fafc;
            --bg-card: #ffffff;
            --shopify-green: #008060;
            --whatsapp-green: #25d366;
            --shadow-subtle: 0 1px 3px rgba(0, 0, 0, 0.05);
            --shadow-card: 0 10px 25px -4px rgba(15, 23, 42, 0.06), 0 4px 6px -2px rgba(15, 23, 42, 0.03);
            --shadow-hover: 0 20px 30px -6px rgba(15, 23, 42, 0.1);
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: var(--bg-page);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.04) 0px, transparent 50%),
                radial-gradient(at 100% 15%, rgba(16, 185, 129, 0.03) 0px, transparent 40%),
                radial-gradient(at 50% 100%, rgba(99, 102, 241, 0.03) 0px, transparent 50%);
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-subtle);
        }

        .nav-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 4.5rem;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            font-size: 1.25rem;
            letter-spacing: -0.025em;
        }

        .brand-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2rem;
            list-style: none;
        }

        .nav-link {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9375rem;
            font-weight: 500;
            transition: color 0.15s ease;
        }

        .nav-link:hover {
            color: var(--primary);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.625rem 1.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.28);
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
        }

        .btn-secondary {
            background-color: #ffffff;
            color: var(--text-main);
            border-color: var(--border-subtle);
            box-shadow: var(--shadow-subtle);
        }

        .btn-secondary:hover {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-main);
            cursor: pointer;
            padding: 0.5rem;
        }

        .mobile-menu {
            display: none;
            flex-direction: column;
            gap: 1rem;
            padding: 1.5rem;
            background: #ffffff;
            border-bottom: 1px solid var(--border-subtle);
            box-shadow: var(--shadow-card);
        }

        /* Hero Section */
        .hero-section {
            padding: 4.5rem 0 4rem;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 3.5rem;
            align-items: center;
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.875rem;
            background: var(--primary-light);
            border: 1px solid var(--primary-border);
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 1.25rem;
        }

        .hero-headline {
            font-size: 3.25rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
            color: var(--text-main);
            margin-bottom: 1.25rem;
        }

        .hero-headline span {
            color: var(--primary);
        }

        .hero-description {
            font-size: 1.125rem;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 2rem;
            max-width: 560px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .hero-note {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.875rem;
             
        }

        .hero-note svg {
            color: var(--shopify-green);
            flex-shrink: 0;
        }

        /* Marketing Product Illustration Mockup */
        .mockup-container {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            overflow: hidden;
            position: relative;
        }

        .mockup-header {
            background: #f8fafc;
            border-bottom: 1px solid var(--border-subtle);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .mockup-dots {
            display: flex;
            gap: 6px;
        }

        .mockup-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #cbd5e1;
        }

        .mockup-url {
            font-size: 0.75rem;
             
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            padding: 0.2rem 0.75rem;
            border-radius: 6px;
            font-family: monospace;
        }

        .mockup-body {
            padding: 1.75rem;
            position: relative;
        }

        .mockup-product-card {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .mockup-product-visual {
            height: 220px;
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border: 1px solid #e0e7ff;
        }

        .mockup-product-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: #ffffff;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--primary);
            box-shadow: var(--shadow-subtle);
        }

        .mockup-product-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
        }

        .mockup-pricing-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .mockup-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .mockup-stock {
            font-size: 0.75rem;
            font-weight: 600;
            color: #059669;
            background: #ecfdf5;
            padding: 0.15rem 0.5rem;
            border-radius: 9999px;
        }

        .mockup-add-cart {
            background: var(--text-main);
            color: #ffffff;
            padding: 0.75rem;
            border-radius: 8px;
            text-align: center;
            font-size: 0.875rem;
            font-weight: 600;
        }

        /* Mockup Floating Social Dock */
        .mockup-floating-dock {
            position: absolute;
            bottom: 1.5rem;
            right: 1.5rem;
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: 9999px;
            padding: 0.35rem 0.6rem;
            box-shadow: 0 10px 25px -3px rgba(15, 23, 42, 0.15);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 10;
        }

        .mockup-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.875rem;
            transition: transform 0.15s ease;
        }

        .mockup-icon:hover {
            transform: scale(1.1);
        }

        .mockup-callout {
            position: absolute;
            top: -1.25rem;
            right: 1.5rem;
            background: var(--text-main);
            color: #ffffff;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: var(--shadow-card);
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .mockup-callout::after {
            content: '';
            position: absolute;
            bottom: -5px;
            right: 20px;
            width: 0;
            height: 0;
            border-left: 5px solid transparent;
            border-right: 5px solid transparent;
            border-top: 5px solid var(--text-main);
        }

        /* Platforms Bar */
        .platforms-section {
            padding: 2.5rem 0;
            background: #ffffff;
            border-top: 1px solid var(--border-subtle);
            border-bottom: 1px solid var(--border-subtle);
        }

        .platforms-label {
            text-align: center;
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
             
            margin-bottom: 1.5rem;
        }

        .platforms-strip {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 2.25rem;
        }

        .platform-badge {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-main);
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .platform-badge:hover {
            background: var(--bg-page);
            color: var(--primary);
        }

        .platform-badge svg {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
        }

        /* Section Headings */
        .section-header {
            text-align: center;
            max-width: 680px;
            margin: 0 auto 3.5rem;
        }

        .section-pill {
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .section-title {
            font-size: 2.25rem;
            font-weight: 800;
            letter-spacing: -0.025em;
            color: var(--text-main);
            margin-bottom: 1rem;
            line-height: 1.25;
        }

        .section-desc {
            font-size: 1.05rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Features Section */
        .features-section {
            padding: 5.5rem 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .feature-card {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            padding: 2.25rem 2rem;
            box-shadow: var(--shadow-subtle);
            transition: all 0.2s ease;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            border-color: var(--primary-border);
            box-shadow: var(--shadow-hover);
        }

        .feature-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--primary-light);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .feature-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.65rem;
        }

        .feature-card p {
            font-size: 0.925rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Interactive Preview Section */
        .preview-section {
            padding: 5.5rem 0;
            background: #ffffff;
            border-top: 1px solid var(--border-subtle);
            border-bottom: 1px solid var(--border-subtle);
        }

        .preview-card {
            background: var(--bg-page);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-card);
            padding: 2.5rem;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 3rem;
            align-items: center;
        }

        .preview-controls {
            display: flex;
            flex-direction: column;
            gap: 1.75rem;
        }

        .controls-group-label {
            font-size: 0.8125rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
             
            margin-bottom: 0.75rem;
        }

        .toggle-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .preview-btn {
            padding: 0.5rem 0.875rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border-radius: 8px;
            border: 1px solid var(--border-subtle);
            background: #ffffff;
            color: var(--text-main);
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .preview-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--text-main);
            cursor: pointer;
            user-select: none;
        }

        .checkbox-label input {
            accent-color: var(--primary);
            width: 16px;
            height: 16px;
        }

        .preview-note {
            font-size: 0.75rem;
             
            background: #ffffff;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border-left: 3px solid var(--primary);
            border: 1px solid var(--border-subtle);
            border-left-width: 3px;
        }

        .preview-stage {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            padding: 2rem;
            position: relative;
            min-height: 380px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .stage-product-mockup {
            background: var(--bg-page);
            border: 1px solid var(--border-subtle);
            border-radius: 12px;
            padding: 1.25rem;
            max-width: 300px;
        }

        .live-dock {
            position: absolute;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            padding: 0.4rem 0.6rem;
            border-radius: 9999px;
            box-shadow: var(--shadow-card);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            z-index: 10;
        }

        .dock-bottom-right {
            bottom: 1.5rem;
            right: 1.5rem;
            flex-direction: row;
        }

        .dock-bottom-left {
            bottom: 1.5rem;
            left: 1.5rem;
            flex-direction: row;
        }

        .dock-vertical-right {
            top: 50%;
            right: 1.5rem;
            transform: translateY(-50%);
            flex-direction: column;
            border-radius: 16px;
        }

        .live-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.85rem;
            transition: transform 0.15s ease;
        }

        .live-icon:hover {
            transform: scale(1.12);
        }

        /* How It Works Section */
        .how-section {
            padding: 5.5rem 0;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2.5rem;
        }

        .step-card {
            background: #ffffff;
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow-subtle);
        }

        .step-badge {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--primary);
            color: #ffffff;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }

        .step-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.75rem;
        }

        .step-card p {
            font-size: 0.9375rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Pricing Section */
        .pricing-section {
            padding: 5.5rem 0;
            background: #ffffff;
            border-top: 1px solid var(--border-subtle);
            border-bottom: 1px solid var(--border-subtle);
        }

        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2.5rem;
            max-width: 900px;
            margin: 0 auto;
        }

        .pricing-card {
            background: var(--bg-page);
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-xl);
            padding: 2.75rem 2.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: all 0.2s ease;
        }

        .pricing-card.featured {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 20px 35px -10px rgba(79, 70, 229, 0.15);
        }

        .featured-pill {
            position: absolute;
            top: -0.75rem;
            left: 50%;
            transform: translateX(-50%);
            background: var(--primary);
            color: #ffffff;
            padding: 0.25rem 0.875rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .plan-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .plan-summary {
            font-size: 0.9375rem;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
            min-height: 48px;
        }

        .plan-tier-heading {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border-subtle);
        }

        .plan-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
            margin-bottom: 2.5rem;
        }

        .plan-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.9375rem;
            color: var(--text-main);
        }

        .plan-item svg {
            width: 18px;
            height: 18px;
            color: var(--shopify-green);
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* FAQ Section */
        .faq-section {
            padding: 5.5rem 0;
        }

        .faq-stack {
            max-width: 800px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .faq-box {
            border: 1px solid var(--border-subtle);
            border-radius: var(--radius-lg);
            overflow: hidden;
            background: #ffffff;
            transition: border-color 0.15s ease;
        }

        .faq-box:hover {
            border-color: #cbd5e1;
        }

        .faq-trigger {
            width: 100%;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: none;
            border: none;
            text-align: left;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-main);
            cursor: pointer;
        }

        .faq-trigger svg {
            transition: transform 0.2s ease;
             
            flex-shrink: 0;
        }

        .faq-box.active .faq-trigger svg {
            transform: rotate(180deg);
            color: var(--primary);
        }

        .faq-content {
            padding: 0 1.5rem 1.25rem;
            font-size: 0.9375rem;
            color: var(--text-muted);
            line-height: 1.65;
            display: none;
        }

        .faq-box.active .faq-content {
            display: block;
        }

        /* Final CTA Section */
        .cta-section {
            padding: 6rem 0;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #ffffff;
            text-align: center;
        }

        .cta-title {
            font-size: 2.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin-bottom: 1rem;
        }

        .cta-description {
            font-size: 1.125rem;
            color: #cbd5e1;
            max-width: 580px;
            margin: 0 auto 2.5rem;
        }

        /* Footer */
        .footer {
            background: #ffffff;
            border-top: 1px solid var(--border-subtle);
            padding: 4rem 0 2rem;
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .footer-brand-desc {
            margin-top: 0.75rem;
            max-width: 340px;
             
            line-height: 1.5;
        }

        .footer-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: var(--text-main);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        .footer-links-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
        }

        .footer-links-list a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .footer-links-list a:hover {
            color: var(--primary);
        }

        .footer-legal-bar {
            padding-top: 2rem;
            border-top: 1px solid var(--border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            font-size: 0.8125rem;
             
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-grid {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .hero-headline {
                font-size: 2.75rem;
            }

            .features-grid, .steps-grid, .preview-card, .pricing-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .hero-headline {
                font-size: 2.25rem;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }

</style>
@endpush

@section('content')

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container hero-grid">
                <div class="hero-content">
                    <div class="badge-pill">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        Social Sharing for Shopify
                    </div>

                    <h1 class="hero-headline">
                        Make Every Product <span>Easier to Share.</span>
                    </h1>

                    <p class="hero-description">
                        Socialvire helps your Shopify store connect products with the social platforms your customers already use. Add customizable floating social icons and convenient product-sharing options.
                    </p>

                    <div class="hero-actions">
                        <a href="#how-it-works" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                            <span>Get Started</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                        </a>

                        <a href="#features" class="btn btn-secondary" style="padding: 0.75rem 1.5rem;">
                            <span>Explore Features</span>
                        </a>
                    </div>

                    <div class="hero-note">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Built for Shopify Online Store 2.0 with Theme App Extensions. No manual code edits required.</span>
                    </div>
                </div>

                <!-- Right Side: Marketing Product Illustration Mockup -->
                <div class="mockup-container">
                    <div class="mockup-header">
                        <div class="mockup-dots">
                            <span class="mockup-dot" style="background-color: #ef4444;"></span>
                            <span class="mockup-dot" style="background-color: #f59e0b;"></span>
                            <span class="mockup-dot" style="background-color: #10b981;"></span>
                        </div>
                        <span class="mockup-url">storefront-demo.myshopify.com/products/carafe</span>
                        <span style="width: 10px;"></span>
                    </div>

                    <div class="mockup-body">
                        <div class="mockup-product-card">
                            <div class="mockup-product-visual">
                                <span class="mockup-product-badge">Featured</span>
                                <svg width="110" height="110" viewBox="0 0 100 100" fill="none">
                                    <rect x="25" y="30" width="50" height="55" rx="10" fill="#4f46e5" fill-opacity="0.18" stroke="#4f46e5" stroke-width="2.5"/>
                                    <path d="M35 30V22C35 18 40 15 50 15C60 15 65 18 65 22V30" stroke="#4f46e5" stroke-width="2.5" stroke-linecap="round"/>
                                    <circle cx="50" cy="55" r="14" fill="#ffffff" stroke="#4f46e5" stroke-width="2"/>
                                    <path d="M50 48v7l4 2" stroke="#4f46e5" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>

                            <div class="mockup-product-title">Nordic Ceramic Pour-Over Carafe</div>
                            <div class="mockup-pricing-row">
                                <span class="mockup-price">$64.00</span>
                                <span class="mockup-stock">In Stock</span>
                            </div>
                            <div class="mockup-add-cart">Add to Cart</div>
                        </div>

                        <!-- Fictional Floating Social Media Dock -->
                        <div class="mockup-floating-dock" title="Floating Social Share Dock">
                            <div class="mockup-icon" style="background: #25d366;" title="WhatsApp Sharing">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </div>
                            <div class="mockup-icon" style="background: linear-gradient(45deg, #f09433 0%, #dc2743 50%, #bc1888 100%);" title="Instagram">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </div>
                            <div class="mockup-icon" style="background: #000000;" title="TikTok">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                            </div>
                            <div class="mockup-icon" style="background: #e60023;" title="Pinterest">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146 1.124.347 2.317.535 3.554.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>
                            </div>
                        </div>

                        <div class="mockup-callout">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            <span>Floating Storefront Widget</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Supported Platforms Strip -->
        <section class="platforms-section">
            <div class="container">
                <p class="platforms-label">Supported Social Media & Messaging Channels</p>
                <div class="platforms-strip">
                    <div class="platform-badge">
                        <svg style="color: #25d366;" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        <span>WhatsApp</span>
                    </div>

                    <div class="platform-badge">
                        <svg style="color: #e1306c;" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        <span>Instagram</span>
                    </div>

                    <div class="platform-badge">
                        <svg style="color: #1877f2;" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        <span>Facebook</span>
                    </div>

                    <div class="platform-badge">
                        <svg style="color: #000000;" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                        <span>TikTok</span>
                    </div>

                    <div class="platform-badge">
                        <svg style="color: #e60023;" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146 1.124.347 2.317.535 3.554.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>
                        <span>Pinterest</span>
                    </div>

                    <div class="platform-badge">
                        <svg style="color: #ff0000;" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                        <span>YouTube</span>
                    </div>

                    <div class="platform-badge">
                        <svg style="color: #000000;" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        <span>X (Twitter)</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section" id="features">
            <div class="container">
                <div class="section-header">
                    <p class="section-pill">Core Features</p>
                    <h2 class="section-title">Everything Built for Social Product Sharing</h2>
                    <p class="section-desc">Add attractive floating icons and product sharing directly to your Shopify storefront.</p>
                </div>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/></svg>
                        </div>
                        <h3>1. Customizable Floating Social Icons</h3>
                        <p>Keep your social channels within easy reach with a floating dock that smoothly stays visible as shoppers browse your catalog.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                        </div>
                        <h3>2. Product Page Sharing</h3>
                        <p>Enable customers to share the exact product they are viewing with a single click, passing product title, image, and canonical URL.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box" style="background: rgba(37, 211, 102, 0.12); color: #16a34a;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
                        </div>
                        <h3>3. WhatsApp Sharing with Prepared Message</h3>
                        <p>Automatically open WhatsApp on mobile or desktop with a pre-populated product link and message ready for the customer to send.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>
                        </div>
                        <h3>4. Multi-Platform Support</h3>
                        <p>Select which networks to activate from Instagram, Facebook, YouTube, Pinterest, TikTok, and X based on your audience demographics.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                        </div>
                        <h3>5. Customizable Icon Placement & Appearance</h3>
                        <p>Position icons on the bottom-right, bottom-left, or vertical side dock. Choose circular or squircle styles to match your theme.</p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                        </div>
                        <h3>6. Simple Merchant Configuration</h3>
                        <p>Manage your icons and settings straightforwardly without editing theme Liquid code or managing complex external scripts.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Interactive Preview Section -->
        <section class="preview-section" id="preview">
            <div class="container">
                <div class="section-header">
                    <p class="section-pill">Interactive Demo</p>
                    <h2 class="section-title">See How It Looks on Your Store</h2>
                    <p class="section-desc">Try changing dock positions, active platforms, and icon shapes below to preview the storefront experience.</p>
                </div>

                <div class="preview-card">
                    <div class="preview-controls">
                        <div>
                            <div class="controls-group-label">Dock Position</div>
                            <div class="toggle-btn-group" id="posControlBtns">
                                <button type="button" class="preview-btn active" data-pos="dock-bottom-right">Bottom Right</button>
                                <button type="button" class="preview-btn" data-pos="dock-bottom-left">Bottom Left</button>
                                <button type="button" class="preview-btn" data-pos="dock-vertical-right">Vertical Right</button>
                            </div>
                        </div>

                        <div>
                            <div class="controls-group-label">Platforms</div>
                            <div class="checkbox-grid">
                                <label class="checkbox-label">
                                    <input type="checkbox" id="toggle-whatsapp" checked>
                                    <span>WhatsApp</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="toggle-instagram" checked>
                                    <span>Instagram</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="toggle-tiktok" checked>
                                    <span>TikTok</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="toggle-pinterest" checked>
                                    <span>Pinterest</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="toggle-facebook">
                                    <span>Facebook</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="toggle-x">
                                    <span>X (Twitter)</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <div class="controls-group-label">Icon Shape</div>
                            <div class="toggle-btn-group" id="shapeControlBtns">
                                <button type="button" class="preview-btn active" data-radius="50%">Circle</button>
                                <button type="button" class="preview-btn" data-radius="8px">Squircle</button>
                            </div>
                        </div>

                        <div class="preview-note">
                            <strong>Marketing Preview:</strong> This interactive canvas is for illustration only and demonstrates storefront appearance without modifying store settings.
                        </div>
                    </div>

                    <!-- Live Stage -->
                    <div class="preview-stage">
                        <div class="stage-product-mockup">
                            <div style="height: 110px; background: #eef2ff; border-radius: 8px; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: center;">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#4f46e5" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
                            </div>
                            <div style="font-size: 0.95rem; font-weight: 700; margin-bottom: 0.25rem;">Minimalist Leather Backpack</div>
                            <div style="font-size: 0.85rem; color: #475569; margin-bottom: 0.5rem;">$128.00 USD</div>
                            <div style="background: #0f172a; color: #fff; font-size: 0.75rem; padding: 0.4rem; border-radius: 6px; text-align: center; font-weight: 600;">Add to Cart</div>
                        </div>

                        <div class="live-dock dock-bottom-right" id="demoLiveDock">
                            <div class="live-icon" id="demo-whatsapp" style="background: #25d366;" title="WhatsApp">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            </div>
                            <div class="live-icon" id="demo-instagram" style="background: linear-gradient(45deg, #f09433 0%, #dc2743 50%, #bc1888 100%);" title="Instagram">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </div>
                            <div class="live-icon" id="demo-tiktok" style="background: #000000;" title="TikTok">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                            </div>
                            <div class="live-icon" id="demo-pinterest" style="background: #e60023;" title="Pinterest">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 01.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.631-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146 1.124.347 2.317.535 3.554.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>
                            </div>
                            <div class="live-icon" id="demo-facebook" style="background: #1877f2; display: none;" title="Facebook">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                            </div>
                            <div class="live-icon" id="demo-x" style="background: #000000; display: none;" title="X (Twitter)">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="how-section" id="how-it-works">
            <div class="container">
                <div class="section-header">
                    <p class="section-pill">Simple Onboarding</p>
                    <h2 class="section-title">How It Works</h2>
                    <p class="section-desc">Get your floating social icons and product sharing live in three steps.</p>
                </div>

                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-badge">1</div>
                        <h3>1. Install Socialvire on Shopify</h3>
                        <p>Connect Socialvire directly to your Shopify store with secure, official OAuth. No developer assistance required.</p>
                    </div>

                    <div class="step-card">
                        <div class="step-badge">2</div>
                        <h3>2. Choose Platforms & Customize</h3>
                        <p>Select which social networks you want to display, adjust dock placement (left or right), and pick your icon styling.</p>
                    </div>

                    <div class="step-card">
                        <div class="step-badge">3</div>
                        <h3>3. Enable Theme Extension</h3>
                        <p>Turn on the Socialvire App Embed extension in your Shopify theme editor and save. Your icons appear immediately.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section class="pricing-section" id="pricing">
            <div class="container">
                <div class="section-header">
                    <p class="section-pill">Transparent Plans</p>
                    <h2 class="section-title">Pricing Plans</h2>
                    <p class="section-desc">Choose the plan that suits your Shopify storefront needs.</p>
                </div>

                <div class="pricing-grid">
                    <!-- Free Plan -->
                    <div class="pricing-card">
                        <div>
                            <h3 class="plan-title">Free Plan</h3>
                            <p class="plan-summary">Core social sharing features suitable for merchants getting started.</p>
                            <div class="plan-tier-heading">Free</div>

                            <ul class="plan-list">
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Core floating social sharing dock</span>
                                </li>
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>WhatsApp product sharing</span>
                                </li>
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Standard dock positioning (Bottom Right / Left)</span>
                                </li>
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Basic customization</span>
                                </li>
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Shopify Theme App Extension support</span>
                                </li>
                            </ul>
                        </div>

                        <a href="#how-it-works" class="btn btn-secondary" style="width: 100%;">
                            <span>Get Started</span>
                        </a>
                    </div>

                    <!-- Pro Plan -->
                    <div class="pricing-card featured">
                        <div class="featured-pill">Enhanced</div>
                        <div>
                            <h3 class="plan-title">Pro Plan</h3>
                            <p class="plan-summary">Enhanced customization and premium controls for growing stores.</p>
                            <div class="plan-tier-heading">Pro</div>

                            <ul class="plan-list">
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Everything in the Free plan</span>
                                </li>
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Enhanced customization options</span>
                                </li>
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>All supported social platforms simultaneously</span>
                                </li>
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Prepared message template customization</span>
                                </li>
                                <li class="plan-item">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    <span>Priority merchant support</span>
                                </li>
                            </ul>
                        </div>

                        <a href="#how-it-works" class="btn btn-primary" style="width: 100%;">
                            <span>Explore Pro</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section" id="faq">
            <div class="container">
                <div class="section-header">
                    <p class="section-pill">Questions & Answers</p>
                    <h2 class="section-title">Frequently Asked Questions</h2>
                    <p class="section-desc">Answers to common questions about Socialvire setup and functionality.</p>
                </div>

                <div class="faq-stack">
                    <div class="faq-box active">
                        <button class="faq-trigger" type="button" aria-expanded="true">
                            <span>What is Socialvire?</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content">
                            Socialvire is a Shopify app that allows merchants to display customizable floating social media icons on their storefront and lets shoppers easily share product links and details with their friends and followers.
                        </div>
                    </div>

                    <div class="faq-box">
                        <button class="faq-trigger" type="button" aria-expanded="false">
                            <span>Which social platforms does it support?</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content">
                            Socialvire supports Instagram, Facebook, YouTube, Pinterest, TikTok, X (formerly Twitter), and WhatsApp. You can choose which platforms to show or hide in your settings.
                        </div>
                    </div>

                    <div class="faq-box">
                        <button class="faq-trigger" type="button" aria-expanded="false">
                            <span>How does WhatsApp sharing work?</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content">
                            When a shopper clicks the WhatsApp icon on a product page, WhatsApp opens on their mobile device or desktop browser with a prepared message containing the product title and link ready to send.
                        </div>
                    </div>

                    <div class="faq-box">
                        <button class="faq-trigger" type="button" aria-expanded="false">
                            <span>Do I need to edit my theme code manually?</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content">
                            No manual code editing is required. Socialvire uses Shopify's Online Store 2.0 Theme App Extensions, so you simply activate the App Embed in your theme editor.
                        </div>
                    </div>

                    <div class="faq-box">
                        <button class="faq-trigger" type="button" aria-expanded="false">
                            <span>How do I enable the storefront icons?</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content">
                            After installation, navigate to your Shopify Admin &rarr; Online Store &rarr; Themes &rarr; Customize. In the left panel, click the App Embeds tab, enable Socialvire, and click Save.
                        </div>
                    </div>

                    <div class="faq-box">
                        <button class="faq-trigger" type="button" aria-expanded="false">
                            <span>Is there a free plan?</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                        </button>
                        <div class="faq-content">
                            Yes. Socialvire offers a Free plan with core social sharing features, WhatsApp product sharing, and standard placement. A Pro plan is available for enhanced customization.
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Final CTA Section -->
        <section class="cta-section">
            <div class="container">
                <h2 class="cta-title">Make Your Shopify Products Easier to Share.</h2>
                <p class="cta-description">Give your customers a simple way to share the products they love.</p>
                <a href="#how-it-works" class="btn btn-primary" style="padding: 0.875rem 2rem; font-size: 1rem;">
                    <span>Get Started with Socialvire</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </section>

@endsection

@push('scripts')
<script>

        // Mobile Navigation Drawer Toggle
        const mobileToggleBtn = document.getElementById('mobileMenuToggle');
        const mobileDrawer = document.getElementById('mobileMenuDrawer');

        if (mobileToggleBtn && mobileDrawer) {
            mobileToggleBtn.addEventListener('click', () => {
                const isDisplayed = mobileDrawer.style.display === 'flex';
                mobileDrawer.style.display = isDisplayed ? 'none' : 'flex';
            });
        }

        function hideMobileDrawer() {
            if (mobileDrawer) {
                mobileDrawer.style.display = 'none';
            }
        }

        // Interactive Preview: Position Controls
        const posBtns = document.querySelectorAll('#posControlBtns .preview-btn');
        const demoLiveDock = document.getElementById('demoLiveDock');

        posBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                posBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const chosenPos = btn.getAttribute('data-pos');
                if (demoLiveDock) {
                    demoLiveDock.classList.remove('dock-bottom-right', 'dock-bottom-left', 'dock-vertical-right');
                    demoLiveDock.classList.add(chosenPos);
                }
            });
        });

        // Interactive Preview: Platform Toggles
        const demoPlatforms = ['whatsapp', 'instagram', 'tiktok', 'pinterest', 'facebook', 'x'];

        demoPlatforms.forEach(plat => {
            const chk = document.getElementById(`toggle-${plat}`);
            const icn = document.getElementById(`demo-${plat}`);

            if (chk && icn) {
                chk.addEventListener('change', () => {
                    icn.style.display = chk.checked ? 'flex' : 'none';
                });
            }
        });

        // Interactive Preview: Shape Controls
        const shapeBtns = document.querySelectorAll('#shapeControlBtns .preview-btn');

        shapeBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                shapeBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                const chosenRadius = btn.getAttribute('data-radius');
                const icons = document.querySelectorAll('.live-icon');
                icons.forEach(ic => {
                    ic.style.borderRadius = chosenRadius;
                });
            });
        });

        // FAQ Accordion
        const faqBoxes = document.querySelectorAll('.faq-box');

        faqBoxes.forEach(box => {
            const trigger = box.querySelector('.faq-trigger');
            if (trigger) {
                trigger.addEventListener('click', () => {
                    const isOpen = box.classList.contains('active');
                    faqBoxes.forEach(b => {
                        b.classList.remove('active');
                        const t = b.querySelector('.faq-trigger');
                        if (t) t.setAttribute('aria-expanded', 'false');
                    });

                    if (!isOpen) {
                        box.classList.add('active');
                        trigger.setAttribute('aria-expanded', 'true');
                    }
                });
            }
        });

</script>
@endpush
