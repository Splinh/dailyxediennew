# HD Theme — Technical Overview

> **Version:** 2.1.1 · **PHP:** 8.3+ · **WordPress:** 6.7+ · **License:** MIT

## Overview

**HD** is a custom WordPress theme built with a strict engineering approach. It replaces typical
WordPress "spaghetti code" with **OOP architecture**, **PSR-4 autoloading** via Composer, and
**Vite** for frontend asset compilation. The theme is designed for scalability, performance, and
maintainability.

---

## Architecture

### Bootstrapping Flow

```
functions.php → Composer Autoloader → Bootstrap::get_instance()
                                         ├── Theme (frontend setup, assets, templates)
                                         ├── Admin (admin-only features)
                                         ├── Core (Cache, Customizer, Optimizer, Asset)
                                         ├── Plugins (ACF, WooCommerce, CF7, RankMath integrations)
                                         └── App (API, Events, Modules)
```

- **`functions.php`** — PHP version guard, constants, Composer autoloader, class alias
  (`HD_Helper`).
- **`Bootstrap`** — Central service container. Initializes `Theme` and registers all services via
  `registerServices()`.
- **`Theme`** — Handles theme supports, widget management, asset enqueueing, and dynamic template
  asset loading.

### Singleton Pattern

Core classes (`Bootstrap`, `Theme`, `Cache`, `Admin`, etc.) use a shared `Singleton` trait, ensuring
each is initialized exactly once.

---

## Folder Structure

```
hd/
├── src/                        # PHP classes (PSR-4: HD\)
│   ├── Bootstrap.php           # Service container
│   ├── Theme.php               # Theme setup & frontend hooks
│   ├── Admin/                  # Admin panel functionality
│   │   └── Admin.php
│   ├── App/                    # Application layer
│   │   ├── API/                # REST API endpoints (AbstractAPI pattern)
│   │   ├── Events/             # Cron & event handlers (AbstractEvent pattern)
│   │   └── Modules/            # Feature modules (AbstractModule pattern)
│   ├── Core/                   # Infrastructure
│   │   ├── Asset.php           # Vite-aware asset enqueue & versioning
│   │   ├── Cache.php           # Environment-aware caching (Redis/transient fallback)
│   │   ├── Customizer.php      # Theme Customizer settings
│   │   ├── DB.php              # Database abstraction layer
│   │   └── Optimizer/          # HTML/CSS/JS optimization (CssClass, ImageSize, ScriptLoader)
│   ├── Plugins/                # Plugin integration layer
│   │   ├── Plugin.php          # Plugin autoloader
│   │   └── Integrations/       # ACF, WooCommerce, CF7, RankMath
│   └── Utilities/              # Shared utilities
│       ├── Helper.php          # Central helper class (uses 20 traits)
│       ├── HorizontalNavWalker.php
│       ├── VerticalNavWalker.php
│       ├── Libraries/          # Minify_Html, CSS parser
│       ├── Shortcode/          # Shortcode registration
│       └── Traits/             # 20 trait files (see Helper System below)
├── inc/                        # Procedural files (Composer autoloaded)
│   ├── helpers.php             # Global helper functions (hd_svg, translations)
│   ├── setup.php               # WordPress hooks & filters setup
│   ├── template-hooks.php      # Template action hooks
│   └── svg-icons.php           # SVG icon definitions
├── resources/                  # Frontend source files (compiled by Vite)
│   ├── scripts/                # JavaScript/TypeScript source
│   ├── styles/                 # SCSS source
│   ├── components/             # Template-specific assets
│   ├── fonts/                  # Web fonts
│   └── img/                    # Source images
├── assets/                     # Compiled output (Vite build)
├── parts/                      # Template parts
│   ├── blocks/                 # Reusable block templates (cached)
│   └── post/                   # Post type partials
├── templates/                  # WordPress page templates
├── woocommerce/                # WooCommerce template overrides
├── languages/                  # Translation files
├── vite.config.ts              # Vite configuration
├── composer.json               # Composer (PSR-4, dependencies)
└── functions.php               # Theme entry point
```

---

## Helper System

The `Helper` class (`HD_Helper` alias) is the central utility class, composed of **20 traits**
organized by domain:

| Domain         | Traits                                                                              | Purpose                                                    |
| -------------- | ----------------------------------------------------------------------------------- | ---------------------------------------------------------- |
| **Base**       | `Base`, `Arr`, `Str`, `DateTime`                                                    | Core utilities, array/string manipulation, date conversion |
| **WordPress**  | `WpPost`, `WpQuery`, `WpMedia`, `WpOptions`, `WpTemplate`, `WpNavigation`, `WpMisc` | WordPress-specific operations                              |
| **ACF**        | `WpAcf`                                                                             | Advanced Custom Fields integration                         |
| **Security**   | `Encryption`, `Validation`                                                          | Sodium encryption, input validation                        |
| **File & URL** | `File`, `Url`                                                                       | File system operations, URL/IP utilities                   |
| **Content**    | `Embed`, `Minification`                                                             | Embeds, schema markup, HTML/CSS/JS minification            |
| **Generation** | `Generator`                                                                         | Random username/slug generation                            |

---

## Build System (Vite)

- **SCSS + Tailwind CSS 4** for styling.
- Entry points: `preflight.js`, `index.js`, `extra.js`, `admin.js` (JS); `main.scss`, `share.scss`,
  `page.scss`, `admin.scss`, `editor-style.scss` (CSS).
- Lazy-loaded modules: Swiper, Ensemble, FancyApps.
- **Dynamic template assets**: `Theme::dynamicTemplateInclude()` auto-enqueues matching
  `components/{template-slug}.scss/js` when a page template is loaded.
- Uses shared Vite config from project root (`vite.config.shared`).

### Commands

```bash
pnpm watch          # Development (HMR)
pnpm build          # Production build
composer install    # PHP dependencies
```

---

## Caching Strategy

The `Cache` class provides environment-aware caching:

- **Production** (Redis/Memcached/LiteSpeed): Uses `wp_cache_*` with group support (`hd_theme`,
  `theme_posts`, `theme_menus`, `theme_taxonomies`, `theme_queries`).
- **Development** (no persistent cache): Falls back to transients with `hd_` prefix.
- Auto-invalidation on `save_post`, `created_term`, `edited_term`, `wp_update_nav_menu`, etc.
- `Cache::remember()` — Laravel-style get-or-set pattern.
- `blockTemplate()` caching for reusable template parts (up to 256KB).

---

## Plugin Integrations

The `Plugins/` layer auto-detects and integrates with:

| Plugin             | Integration                                  |
| ------------------ | -------------------------------------------- |
| **ACF / ACF Pro**  | Custom fields, link helpers, term thumbnails |
| **WooCommerce**    | Product queries, shop templates, breadcrumbs |
| **Contact Form 7** | Form enhancements                            |
| **Rank Math SEO**  | Primary term detection, schema coordination  |

Each integration is loaded conditionally — only when the plugin is active.

---

## Requirements

- **PHP** ≥ 8.3
- **WordPress** ≥ 6.7
- **Node.js** + pnpm (for frontend build)
- **Composer** (for PHP autoloading)

## License

MIT — Copyright 2026 HD.
