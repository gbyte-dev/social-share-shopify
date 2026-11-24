<div align="center">

# 🔗 SocialVire

### All-in-One Social Sharing, Floating Dock, Custom Channels & Wishlist for Shopify

Drive viral store traffic and boost conversions. Elegant floating social docks, high-converting product share buttons, custom platform integration, and full customer wishlist functionality — all managed from a native Shopify Admin experience.

[![Shopify App](https://img.shields.io/badge/Shopify-App%20Bridge%203.0-96bf48.svg?logo=shopify)](https://shopify.dev)
[![Laravel 11](https://img.shields.io/badge/Laravel-11.x-FF2D20.svg?logo=laravel)](https://laravel.com)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1.svg?logo=mysql)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

[Features](#-features) · [Architecture](#-architecture--tech-stack) · [Prerequisites](#-prerequisites) · [Installation](#-installation--setup) · [Theme Extension](#-theme-app-extension-setup) · [Analytics](#-analytics--tracking) · [Support](#-support--customization)

</div>

---

## 📖 About SocialVire

**SocialVire** is a modern, high-performance Shopify application built with **Laravel 11**, **Shopify App Bridge**, and **Shopify Theme App Extensions**. It empowers Shopify merchants to effortlessly expand their social reach, capture visitor interest, and track engagement across all marketing channels.

Merchants can place sticky floating social docks, display clean product-level share buttons, register completely customized social and communication apps with automated logo discovery, and provide shoppers with a seamless, cross-device wishlist experience powered by **Shopify App Proxy**.

---

## 🌐 Supported Social Platforms & Custom Channels

SocialVire supports both out-of-the-box global networks and user-defined custom platforms:

| Built-in Channels | Built-in Channels | Built-in Channels |
| :---------------- | :---------------- | :---------------- |
| 📱 **WhatsApp**   | 📘 **Facebook**   | 🪡 **Threads**    |
| 📸 **Instagram**  | 🐦 **X (Twitter)**| 💼 **LinkedIn**   |
| 📌 **Pinterest**  | ✈️ **Telegram**   | 🎵 **TikTok**     |
| ▶️ **YouTube**    | 👾 **Discord**    | 👾 **Reddit**     |
| 👻 **Snapchat**   | 💬 **WeChat**     | 🔗 **Copy Link**  |

> 💡 **Custom Channels & Apps**: Merchants can add **any** platform (e.g. Kick, Rumble, Line, Viber, Twitch) with automatic logo discovery or custom image/SVG upload with optimized auto-resizing (36×36, 44×44, 56×56).

---

## ✨ Features

### 🛒 Customer Storefront (Theme App Extension)
- **Floating Social Dock:** Elegant, sticky social sidebar that adapts to desktop and mobile screens. Fully configurable dock style, icon shapes, custom brand colors, and hover animations.
- **Product Page Inline Share:** Sleek share icons rendered directly on product detail pages below "Add to Cart" or near product descriptions. Supports one-click native **Web Share API** on mobile devices.
- **Storefront Wishlist System:** High-performance wishlist block featuring guest storage (UUID/LocalStorage) and automatic migration to authenticated Shopify customer accounts upon login.
- **Shopify Online Store 2.0 Native:** Zero code injection into theme source files. Clean integration via Theme App Extension app blocks and embed blocks.

### ⚙️ Merchant Admin Experience (Embedded App Bridge)
- **Unified Channel Management:** Drag-to-reorder, active/inactive toggles, custom labels, destination URLs, and custom brand colors.
- **Live Interactive Preview Dock:** Visual real-time preview inside the merchant admin showing exactly how icons, badges, and layout render on the live store.
- **Smart Logo Discovery Engine:** Automatically discovers high-resolution brand icons from website URLs and domains with support for custom SVG and raster uploads.
- **Storefront Display Controls:**
  - **Dock Positions:** Left, Right, Bottom-Floating.
  - **Shapes & Styles:** Rounded, Circular, Square, Glassmorphism, and Minimalist.
  - **Hover Animations:** Scale, Slide, Glow, Bounce, and Color Fade.
  - **Mobile Controls:** Independent mobile sizing, hide/show on mobile, and collapsible drawer support.

### 📊 Deep Share & Click Analytics
- **Granular Event Tracking:** Records platform clicked, target URL, referrer domain, and timestamp without tracking personally identifiable information (GDPR compliant).
- **Merchant Dashboard Overview:** Real-time engagement statistics, top-performing channels, product share leaders, and platform distribution breakdowns.
- **Exportable Reports:** Export raw analytics data to CSV for marketing review and campaign attribution.

### 💳 Tiered Subscriptions & Entitlements
- **Shopify Billing API Integration:** Seamless recurring app subscriptions with automated trial periods and webhooks.
- **Flexible Plan Tiers:** Free, Pro, and Enterprise configurations controlling channel limits, custom apps, and advanced analytics.

---

## 🏗️ Architecture & Tech Stack

```
┌─────────────────────────────────────────────────────────────┐
│                       Shopify Admin                         │
│   (Embedded App Bridge / Blade Views / Polaris UI Tokens)   │
└──────────────┬──────────────────────────────▲───────────────┘
               │ OAuth & Session Tokens (JWT) │ Live Updates
┌──────────────▼──────────────────────────────┴───────────────┐
│                    Laravel 11 Backend                       │
│  - Controllers & Middleware (VerifyShopifyIdToken)          │
│  - Logo Processing (Intervention Image / GD / SVG Sanitizer)│
│  - App Proxy Controllers (HMAC-SHA256 Verification)        │
│  - Subscription & Entitlement Services                      │
└──────────────┬──────────────────────────────▲───────────────┘
               │ App Proxy / Storefront API   │ Storefront Events
┌──────────────▼──────────────────────────────┴───────────────┐
│             Shopify Storefront (OS 2.0 Theme)               │
│  - Floating Social Dock (Theme App Extension Block)         │
│  - Inline Product Share (Liquid & Vanilla JS)               │
│  - Wishlist App Proxy Client & Guest Storage                │
└─────────────────────────────────────────────────────────────┘
```

- **Backend Framework:** [Laravel 11](https://laravel.com) running on **PHP 8.3**
- **Authentication:** Shopify App Bridge Session Tokens (JWT via `firebase/php-jwt`) + HMAC-SHA256 Proxy Verification
- **Storefront Extension:** Shopify Theme App Extension (Liquid, Vanilla JavaScript, CSS)
- **Image Processing:** Intervention Image 4.x / GD / SVG Sanitizer for custom platform icons
- **Database:** MySQL 8.0+ (Transactions, Indexed Analytics, Model Relations)
- **Styling:** Shopify Polaris-inspired CSS design tokens & responsive components

---

## 📋 Prerequisites

Ensure your development environment meets the following specifications:

| Component | Minimum Version | Recommended |
| :-------- | :-------------- | :---------- |
| **PHP** | 8.3.0 | 8.3+ with `gd`, `curl`, `mbstring`, `pdo_mysql`, `fileinfo` |
| **Composer** | 2.5+ | Latest Composer 2.x |
| **Node.js** | 18.x | 20.x LTS |
| **NPM** | 9.x | 10.x |
| **Database** | MySQL 8.0+ / MariaDB 10.5+ | MySQL 8.0+ |
| **Shopify CLI** | 3.50+ | Latest Shopify CLI |

---

## 🚀 Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/webvire/social-share-shopify.git
cd social-share-shopify
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Frontend dependencies
npm install
```

### 3. Configure Environment

Copy `.env.example` to `.env` and update your settings:

```bash
cp .env.example .env
php artisan key:generate
```

Configure your Shopify App Credentials in `.env`:

```env
APP_NAME=SocialVire
APP_ENV=local
APP_DEBUG=true
APP_URL=https://your-tunnel-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=socialnest
DB_USERNAME=root
DB_PASSWORD=

# Shopify App Credentials (from Shopify Partners Dashboard)
SHOPIFY_API_KEY=your_shopify_api_key
SHOPIFY_API_SECRET=your_shopify_api_secret
SHOPIFY_API_SCOPES=read_themes,write_content,write_online_store_pages,write_themes,read_app_proxy,write_app_proxy
SHOPIFY_APP_URL=${APP_URL}
SHOPIFY_REDIRECT_URI=${APP_URL}/shopify/callback
SHOPIFY_APP_HANDLE=socialvire
SHOPIFY_APP_PROXY_PREFIX=apps
SHOPIFY_APP_PROXY_SUBPATH=socialnest
```

### 4. Database Setup & Storage Link

Run the database migrations and create the public storage symlink for uploaded logos:

```bash
php artisan migrate
php artisan storage:link
```

### 5. Build Assets

```bash
npm run build
```

### 6. Run the Application

```bash
# Start Laravel development server
php artisan serve

# Or run using Laravel Herd / Valet
# App will be accessible at https://social-share-shopify.test
```

---

## 🧩 Theme App Extension Setup

SocialVire includes an official Online Store 2.0 **Theme App Extension** located in `extensions/socialnest-theme-ext`:

1. **Deploy Theme Extension with Shopify CLI:**
   ```bash
   shopify app deploy
   ```
2. **Enable via Shopify Theme Editor:**
   - Open your **Shopify Admin** → **Online Store** → **Themes** → **Customize**.
   - Navigate to **App Embeds** on the left menu and toggle **SocialVire Floating Dock** to **Active**.
   - To show product share buttons, navigate to the **Default Product** template, click **Add Block**, and select **SocialVire Inline Share**.
   - Click **Save**.

---

## 📊 Analytics & Tracking

- **Tracked Metrics:** Channels clicked, daily/monthly traffic counts, top products shared, and conversion click-throughs.
- **Admin Access:** Navigate to **SocialVire App → Dashboard** in your Shopify Admin for visual charts and click distributions.
- **Data Export:** Download detailed share logs via the **Export CSV** action on the Analytics screen.

---

## 🧪 Testing & Code Quality

Run tests and code formatting checks:

```bash
# Run PHPUnit test suite
php artisan test --compact

# Run Laravel Pint formatting
vendor/bin/pint --dirty --format agent
```

---

## 🤝 Support & Customization

Facing an issue, need help with installation, or want custom features? We're happy to help.

|                    |                                                                             |
| :----------------- | :-------------------------------------------------------------------------- |
| 📧 **Email**       | [info@webvire.com](mailto:info@webvire.com)                                 |
| 📞 **Phone**       | [+91 7309277237](tel:+917309277237)                                         |
| 🌐 **Website**     | [webvire.com](https://webvire.com/)                                         |
| 🛟 **Help Center** | [Support & Help Center](https://socialviremagento.webvire.com/support.html) |

## 🔒 Legal

- [Privacy Policy](https://socialviremagento.webvire.com/privacy-policy.html)
- [Terms & Conditions](https://socialviremagento.webvire.com/terms-and-conditions.html)

---

<div align="center">

Developed with ❤️ by **[Webvire Software](https://webvire.com/)**

</div>
