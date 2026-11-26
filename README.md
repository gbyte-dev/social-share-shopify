<div align="center">

# 🔗 SocialVire

### All-in-One Social Sharing, Floating Dock, Custom Channels & Wishlist for Shopify

Drive viral store traffic and boost conversions. Elegant floating social docks, high-converting product share buttons, custom platform integration, and full customer wishlist functionality — all managed from a native Shopify Admin experience.

[![Shopify App](https://img.shields.io/badge/Shopify-App%20Bridge%203.0-96bf48.svg?logo=shopify)](https://shopify.dev)
[![Laravel 11](https://img.shields.io/badge/Laravel-11.x-FF2D20.svg?logo=laravel)](https://laravel.com)
[![PHP 8.3+](https://img.shields.io/badge/PHP-8.3%2B-777BB4.svg?logo=php)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1.svg?logo=mysql)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

[Features](#-features) · [Architecture](#-architecture--tech-stack) · [Prerequisites](#-prerequisites) · [Complete Setup Guide](#-complete-step-by-step-setup-guide) · [Shopify TOML Configuration](#-shopify-app-toml-configuration-guide) · [Theme Extension](#-theme-app-extension-setup) · [Analytics](#-analytics--tracking) · [Support](#-support--customization)

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
| **PHP** | 8.3.0 | 8.3+ with `gd`, `curl`, `mbstring`, `pdo_mysql`, `fileinfo`, `openssl` |
| **Composer** | 2.5+ | Latest Composer 2.x |
| **Node.js** | 18.x | 20.x LTS |
| **NPM** | 9.x | 10.x |
| **Database** | MySQL 8.0+ / MariaDB 10.5+ | MySQL 8.0+ |
| **Shopify CLI** | 3.50+ | Latest Shopify CLI (`npm install -g @shopify/cli`) |
| **Tunneling Tool** | Cloudflare Tunnel / Ngrok | HTTPS tunnel supporting public webhook delivery |

---

## 🚀 Complete Step-by-Step Setup Guide

Follow these steps in sequence to set up the project locally or on your production server.

### 1. Clone the Repository

```bash
git clone https://github.com/webvire/social-share-shopify.git
cd social-share-shopify
```

### 2. Install PHP and Frontend Dependencies

```bash
# Install PHP dependencies via Composer
composer install

# Install frontend JavaScript/CSS dependencies
npm install
```

---

### 3. Environment Setup (`.env`)

Duplicate the template `.env.example` file to create your active `.env`:

```bash
# macOS / Linux / Git Bash
cp .env.example .env

# Windows PowerShell
Copy-Item .env.example .env

# Windows Command Prompt (CMD)
copy .env.example .env
```

Generate the Laravel application encryption key:

```bash
php artisan key:generate
```

Open `.env` and fill in your database, application, and Shopify credentials:

```env
APP_NAME=SocialVire
APP_ENV=local
APP_KEY=base64:YOUR_GENERATED_APP_KEY
APP_DEBUG=true
APP_URL=https://your-public-tunnel-or-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_username
DB_PASSWORD=your_database_password

# ========================================================
# Shopify App Credentials (from Shopify Partners Dashboard)
# ========================================================
# Found under: Partner Dashboard -> Apps -> Your App -> API credentials
SHOPIFY_API_KEY=your_shopify_client_id_here
SHOPIFY_API_SECRET=your_shopify_client_secret_here

# Access scopes requested during store installation
SHOPIFY_API_SCOPES=read_themes,write_content,write_online_store_pages,write_themes,read_app_proxy,write_app_proxy

# App and Redirect URLs
SHOPIFY_APP_URL=${APP_URL}
SHOPIFY_REDIRECT_URI=${APP_URL}/shopify/callback
SHOPIFY_APP_HANDLE=socialvire

# App Proxy Routing Settings (Matches Shopify App Proxy config)
SHOPIFY_APP_PROXY_PREFIX=apps
SHOPIFY_APP_PROXY_SUBPATH=socialnest
```

> ⚠️ **Important Security Practice**: Never commit your real `.env` file containing live API keys, database credentials, or production secrets to Git.

---

### 4. Database Migrations & Public Storage Symlink

Run the database schema migrations:

```bash
php artisan migrate
```

Create the symbolic link from `public/storage` to `storage/app/public`. **This is mandatory** so custom platform icons and uploaded logos can be served publicly to storefronts and admin views:

```bash
php artisan storage:link
```

---

## 🛠️ Shopify App TOML Configuration Guide

Shopify CLI uses TOML files in the project root to manage app credentials, URLs, scopes, webhooks, and App Proxy paths.

This repository includes two template files:
1. `shopify.app.example.toml` — Standard template with all required Shopify configurations.
2. `shopify.app.socialvire.example.toml` — Pre-configured template with SocialVire specific dev settings.

### Step 1: Copy the Template TOML File

Create your active `shopify.app.toml` by copying from one of the template files according to your shell / OS environment:

#### 💻 Windows PowerShell (Recommended for Windows):
```powershell
# Option A: Standard template
Copy-Item shopify.app.example.toml shopify.app.toml

# Option B: SocialVire specific template
Copy-Item shopify.app.socialvire.example.toml shopify.app.toml
```

#### 🖥️ Windows Command Prompt (CMD):
```cmd
# Option A: Standard template
copy shopify.app.example.toml shopify.app.toml

# Option B: SocialVire specific template
copy shopify.app.socialvire.example.toml shopify.app.toml
```

#### 🐧 macOS / Linux / Git Bash:
```bash
# Option A: Standard template
cp shopify.app.example.toml shopify.app.toml

# Option B: SocialVire specific template
cp shopify.app.socialvire.example.toml shopify.app.toml
```

### Step 2: Edit `shopify.app.toml`

Open `shopify.app.toml` and substitute the placeholder values with your actual app details:

```toml
# ==============================================================================
# Shopify CLI App Configuration File
# Learn more: https://shopify.dev/docs/apps/tools/cli/configuration
# ==============================================================================

# Your Client ID (API Key) from Shopify Partner Dashboard -> App -> API Credentials
client_id = "YOUR_SHOPIFY_CLIENT_ID"

# Public application name displayed to merchants
name = "Socialvire"

# Unique handle for your app
handle = "socialvire"

# The HTTPS URL where your app is hosted (e.g. Cloudflare tunnel, ngrok, or production domain)
application_url = "https://your-public-tunnel-or-domain.com"

# Set to true so the app opens directly inside the Shopify Admin via App Bridge
embedded = true

[build]
# Set to true during local development to automatically sync URLs when the tunnel changes
automatically_update_urls_on_dev = true
dev_store_url = "your-dev-store.myshopify.com"

[access_scopes]
# Required permissions for theme blocks, app proxy, and online store content
scopes = "read_app_proxy,read_themes,write_app_proxy,write_content,write_online_store_pages,write_themes"
use_legacy_install_flow = false

[auth]
# Permitted OAuth callback URLs
redirect_urls = [
  "https://your-public-tunnel-or-domain.com/shopify/callback",
  "https://your-public-tunnel-or-domain.com/callback"
]

[webhooks]
api_version = "2026-10"

# Lifecycle Webhook (tracks when a merchant uninstalls the app)
[[webhooks.subscriptions]]
topics = ["app/uninstalled"]
uri = "/shopify/webhooks"

# Mandatory Shopify GDPR / Privacy Webhooks
[[webhooks.subscriptions]]
compliance_topics = ["customers/data_request", "customers/redact", "shop/redact"]
uri = "/shopify/webhooks"

# App Proxy configuration for Wishlist and Storefront APIs
[app_proxy]
url = "https://your-public-tunnel-or-domain.com/shopify/proxy"
subpath = "socialnest"
prefix = "apps"

[pos]
embedded = false
```

### Step 3: Push Configuration to Shopify

Once your `shopify.app.toml` is filled in, push the configuration to your Shopify Partners app using the Shopify CLI:

```bash
shopify app config push
```

> 🔒 **Security Notice**: Make sure `shopify.app.toml` containing your personal or staging credentials is kept safe and excluded from public repositories via `.gitignore`. Always use `.example.toml` templates with dummy values for public commits.

---

### 5. Compile Assets & Run the Application

```bash
# Build frontend assets using Vite
npm run build

# Start the Laravel application server
php artisan serve
```

If developing locally using **Shopify CLI** and a live dev store:

```bash
shopify app dev
```

---

## 🧩 Theme App Extension Setup

SocialVire includes an official Online Store 2.0 **Theme App Extension** located in `extensions/socialnest-theme-ext`:

### 1. Deploy Theme Extension

Push the Theme App Extension to your Shopify App:

```bash
shopify app deploy
```

### 2. Enable App Blocks in Shopify Theme Editor

1. Open your **Shopify Admin** → **Online Store** → **Themes** → **Customize**.
2. **Enable Floating Dock:**
   - On the left toolbar, click on **App Embeds** (icon with blocks).
   - Locate **SocialVire Floating Dock** and switch the toggle to **Active**.
   - Customize position (Left/Right/Bottom), icon size, and animations in the settings panel.
3. **Add Inline Product Share Buttons:**
   - In the theme editor top dropdown, navigate to **Products** → **Default product**.
   - In the left sidebar under the **Product information** section, click **Add block**.
   - Select **SocialVire Inline Share**. Drag it directly below the "Buy buttons" or "Description".
4. **Add Wishlist Page Block (Optional):**
   - Create or open a dedicated Page (e.g. `/pages/wishlist`).
   - Add the **SocialVire Wishlist Page** block.
5. Click **Save** in the top right corner.

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
