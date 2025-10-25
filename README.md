# 🔒 Laravel Security Tools
[![Latest Version on Packagist](https://img.shields.io/packagist/v/fragly/laravel-security-tools.svg?style=for-the-badge&color=blueviolet)](https://packagist.org/packages/fragly/laravel-security-tools)
[![Downloads](https://img.shields.io/packagist/dt/fragly/laravel-security-tools.svg?style=for-the-badge&color=brightgreen)](https://packagist.org/packages/fragly/laravel-security-tools)
[![License](https://img.shields.io/github/license/cristalNichita/laravel-security-tools.svg?style=for-the-badge)](https://github.com/cristalNichita/laravel-security-tools/blob/main/LICENSE)
[![Sponsor](https://img.shields.io/badge/Sponsor-Patreon-ff424d?logo=patreon&style=for-the-badge)](https://www.patreon.com/c/FraglyDev)

> **Automatically scan your Laravel project for common security vulnerabilities in `.env` and configuration files.**  
> Detect unsafe values, missing keys, and misconfigured HTTPS, CORS, and cookie settings — directly from your CLI.

---

## Contents

- [Features](#-features)
- [Installation](#-installation)
- [Usage](#-usage)
- [What It Checks](#-what-it-checks)
- [Markdown Report Example](#-markdown-report-example)
- [Configuration](#-configuration)
- [CI/CD Integration Example](#-cicd-integration-example)
- [Compatibility](#-compatibility)
- [Support & Sponsorship](#-support--sponsorship)
- [About the Author](#-about-the-author)
- [License](#-license)


## 🚀 Features

✅ Detects **risky environment variables** (`APP_DEBUG=true`, missing `APP_KEY`, etc.)  
✅ Scans for **insecure configuration values** (`CORS *`, `SESSION_SECURE=false`, `QUEUE=sync`, etc.)  
✅ Validates **HTTPS usage** in URLs and cookies  
✅ Generates **CLI or Markdown reports** for CI/CD pipelines  
✅ Includes **strict mode** (`--strict`) for automated fail conditions in CI  
✅ Lightweight and dependency-free — **works out of the box**

---

## 📦 Installation

```bash
composer require fragly/laravel-security-tools --dev
```
#### Laravel will auto-discover the service provider.
Alternatively, you can register it manually in `config/app.php:`

```bash
'providers' => [
    Fragly\SecurityTools\SecurityToolsServiceProvider::class,
],
```

## ⚙️ Usage
### Run a full scan
```bash
php artisan security:scan
```
### Generate Markdown report (for CI or audit logs)
```bash
php artisan security:scan --format=md
```
Output file (by default): `storage/logs/security-report.md`

### Strict mode (fail build on warnings)
```bash
php artisan security:scan --strict
```

## 🧠 What It Checks
### Environment (.env)
| Category                    | Example                              | Description                              |
| --------------------------- | ------------------------------------ | ---------------------------------------- |
| **Required Keys**           | `APP_KEY`, `APP_URL`, `DB_*`         | Must exist and be non-empty              |
| **Dangerous Values**        | `APP_DEBUG=true`                     | Warns if enabled in any environment      |
| **Forbidden in Production** | `SESSION_DRIVER=array`, `QUEUE=sync` | Not allowed in production                |
| **Format Validation**       | `APP_KEY`, `APP_URL`                 | Must match regex and be valid            |
| **HTTPS Enforcement**       | `APP_URL`, `ASSET_URL`               | Must start with `https://` in production |

### Config Checks (config())
| Check                           | Description                   |
| ------------------------------- | ----------------------------- |
| `app.debug=false` in production | Prevents debug mode in prod   |
| `session.secure=true`           | Enforces HTTPS cookies        |
| `session.http_only=true`        | Protects from JS access       |
| `cors.allowed_origins` ≠ `*`    | Disallows wildcard CORS       |
| `cache.default` ≠ `array`       | Production cache driver check |
| `queue.default` ≠ `sync`        | Warns if queue runs inline    |
| `mail.default` ≠ `log`          | Ensures real mailer in prod   |
| `log.level` ≠ `debug`           | Avoid verbose logs in prod    |
| `trustedproxy.proxies` ≠ `*`    | Ensures proxy whitelist       |
| `app.url` uses HTTPS            | Verifies production HTTPS URL |

## 🧾 Markdown Report Example
### When you run:
```bash
php artisan security:scan --format=md
```

## It generates:
### Laravel Security Tools Report
- Generated at: 2025-10-25 03:00:00

| Level | Area | Key | Message | Hint |
|-------|------|-----|----------|------|
| ERROR | env | APP_DEBUG | Dangerous value: true | Set APP_DEBUG=false in production. |
| WARNING | config | cors.allowed_origins | CORS allows all origins (*) | Avoid "*" in production. |

## ⚡ Configuration
### You can publish the config file to customize checks:
### Config file: `config/security-tools.php`

## 🧪 CI/CD Integration Example
### GitHub Actions

```name: Security Scan

on: [push, pull_request]

jobs:
security:
runs-on: ubuntu-latest
steps:
- uses: actions/checkout@v4
- name: Install dependencies
run: composer install --no-interaction --prefer-dist
- name: Run security scan
run: php artisan security:scan --strict
```

## 🧩 Compatibility
### - `Laravel: 9.x – 11.x`
### - `PHP: >=8.2`

## ❤️ Support & Sponsorship
### If you like this package, you can support its development and get access to exclusive Laravel & Next.js dev tools:

### [Support on Patreon](https://www.patreon.com/c/FraglyDev)
### 🎁 Get early access to private packages, beta features, and developer insights.
### [Or buy me a coffee ☕](https://buymeacoffee.com/fraglynet)

## 🧑‍💻 About the Author
### Fragly Dev — Building tools for modern Laravel & Next.js developers.
### Follow for more developer utilities, security helpers, and SaaS-ready boilerplates.

### [GitHub](https://github.com/cristalNichita)
### [Patreon](https://www.patreon.com/c/FraglyDev)
### [Website](https://fragly.net)

## 🪪 License
This package is open-sourced software licensed under the MIT license.

### Made with ❤️ by Fragly Dev — making Laravel projects safer by default.

---

<details>
<summary>🔍 SEO Keywords</summary>

laravel security, laravel security scan, laravel .env checker, laravel vulnerability scanner,  
laravel config security, laravel audit tool, laravel .env validation, laravel production best practices,  
laravel https cookie secure, laravel cors security, laravel session security, laravel debugging safe setup,  
laravel security tools by Fragly, laravel security artisan command, laravel security report generator,  
fraglydev, fragly security, fragly.net packages

</details>
<!--
SEO: laravel security, laravel security tools, laravel audit, laravel environment check, laravel security scan, 
fragly laravel package, laravel security config check, laravel best practices, laravel dev tools
-->