<div align="center">

# 🔗 SocialVire

### Free Social Sharing & Analytics Extension for Magento 2

Make your products go viral. Floating share icons, product page share buttons and deep share analytics, all configurable from the Admin Panel.

[![Magento 2.4.x](https://img.shields.io/badge/Magento-2.4.x-orange.svg)](https://magento.com)
[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-OSL--3.0-green.svg)](https://opensource.org/licenses/OSL-3.0)
[![Price](https://img.shields.io/badge/Price-100%25%20Free-brightgreen.svg)](#)

[Features](#-features) · [Installation](#-installation) · [Configuration](#%EF%B8%8F-configuration) · [Analytics](#-viewing-analytics) · [Support](#-support--customization)

</div>

---

## 📖 About

**SocialVire** is a powerful, 100% free and fully customizable Magento 2 extension designed to increase your store's social media visibility.

It adds elegant floating share icons to every page and compact share buttons on product pages, so customers can instantly share products on **WhatsApp, Facebook, X (Twitter), LinkedIn, Pinterest, Telegram**, or copy the link directly.

Every share is tracked, so you always know which products and platforms are driving traffic.

## 🌐 Supported Platforms

| Platform     | Platform  | Platform    |
| :----------- | :-------- | :---------- |
| WhatsApp     | Facebook  | X (Twitter) |
| LinkedIn     | Pinterest | Telegram    |
| 🔗 Copy Link |           |             |

---

## ✨ Features

### 🛒 Customer Frontend

- **Floating Social Sidebar:** beautiful, modern share icons that stick to the left or right side of every page.
- **Product Page Inline Buttons:** compact share icons placed directly below the "Add to Cart" button.
- **Instant Copy Link:** one-click URL copy with clipboard support.
- **Mobile Responsive:** looks great on all devices and screen sizes.

### ⚙️ Admin Panel (Full Control)

- **Enable / Disable Module:** turn the extension on or off easily.
- **Icon Shape:** Circle or Square.
- **Floating Position:** Left or Right.
- **Platform Selection:** choose exactly which social networks to display.
- **Icon Size:** Small (35px), Medium (45px) or Large (55px).
- **Hover Animations:** Scale, Slide or Glow.
- **Close Button:** let users close the floating bar.

### 📊 Deep Analytics & Tracking

- **Detailed Share Logs:** tracks every share event (Platform, IP Address, URL, Date/Time).
- **Admin Analytics Grid:** view, filter and export share data as CSV.
- **Dashboard Donut Chart:** a visual breakdown of which platforms drive the most traffic, right on your Magento Dashboard.

---

## 📋 Requirements

| Requirement | Version       |
| :---------- | :------------ |
| Magento 2   | 2.4.x         |
| PHP         | 7.4 or higher |

---

## 🚀 Installation

Follow these steps to install the extension on your Magento 2 store.

### 1. Upload files

Extract the `.zip` file and upload the `SocialVire` folder to your Magento root directory's `app/code/` folder.

The final path should be:

```
app/code/Webvire/SocialVire/
```

### 2. Run Magento commands

Open your terminal (SSH), go to your Magento root directory and run:

```bash
php bin/magento module:enable MyVendor_SocialShare
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

> **Note:** If your store is in **Developer mode**, you can skip the `setup:di:compile` and `setup:static-content:deploy` steps.

---

## 🎛️ Configuration

1. Log in to your **Magento Admin Panel**.
2. Go to **Stores → Configuration → General → SocialVire Settings**.
3. Set your preferred options:

| Setting                    | Options                                                            |
| :------------------------- | :----------------------------------------------------------------- |
| **Enable Module**          | Yes / No                                                           |
| **Icon Shape**             | Circle / Square                                                    |
| **Floating Position**      | Left / Right                                                       |
| **Select Platforms**       | Multi-select. Hold `Ctrl` (Windows) or `Cmd` (Mac) to pick several |
| **Show below Add to Cart** | Yes / No                                                           |
| **Icon Size**              | Small / Medium / Large                                             |
| **Hover Animation**        | Scale / Slide / Glow                                               |
| **Show Close Button**      | Yes / No                                                           |

4. Click **Save Config**. The cache clears automatically.

---

## 📊 Viewing Analytics

### 1. Detailed logs

- Go to **Admin Sidebar → Content → Social Share Analytics**.
- The grid shows `Platform`, `Product Name`, `IP Address` and `Date/Time`.
- Click **Export** to download the data as a CSV file.

### 2. Dashboard chart

- Open the **Admin Dashboard**.
- Scroll down to the **Social Share Statistics** donut chart.
- 💡 **Tip:** click **Reload Data** on the dashboard to see the latest statistics.

---

## 📄 Documentation

For detailed user guides, see the `docs/` folder in this repository or visit our website.

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