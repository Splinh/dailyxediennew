---
name: frontend
description: Use for Vite, Tailwind 4, SCSS/BEM, JS entry points, FX lazy loading, WooCommerce frontend modules, AJAX filters, CLS, or DOM rendering in 2026.
compatibility: 2026 WordPress workspace only; target must use Vite assets, SCSS, vanilla JS, Tailwind 4, or existing HD frontend modules.
---

# Frontend

## Source Of Truth

- Theme Vite: `wp/wp-content/themes/hd/vite.config.ts` plus shared Vite files when present.
- Plugin Vite: target plugin `vite.config.ts`.
- Assets must resolve through the target `Asset`/Vite manifest helper.
- Constraints: `.agent/rules/constraints.md`.

## Build And Assets

- Use `pnpm build`; never `npm run build`.
- Output goes to `assets/`.
- CSS order: `tailwind.css` -> `main.scss` -> page SCSS.
- Use SCSS BEM: `block__element--modifier`; keep variables in `_variables.scss`.

## Entry Points

- `preflight.js`: critical head scripts.
- `index.js`: deferred app + FX loader.
- `extra.js`: deferred extras.
- `admin.js`: admin-only scripts.

## FX And Modules

- FX scans `data-fx-[name]` and loads chunks via `resources/scripts/core/createLoader.js`.
- Loader groups: `fx/`, `modules/`; no `plugins/` group.
- `template-*.php` auto-enqueues `resources/components/{slug}.(scss|js)` via `enqueue_assets_template_{slug}`.

## DOM Rendering

- No inline HTML strings in JS.
- Use `wp.template()` for dynamic markup.
- Use delegated handlers for AJAX fragments and dynamic elements, e.g. `$(document).on(...)`.

## Performance

- Put CLS-critical skeleton/layout styles in primary SCSS such as `woocommerce.scss`, not lazy chunks.
- Defer non-critical scripts.
- Keep lazy modules idempotent.

## WooCommerce Frontend

- WC modules are flat in `modules/index.js`.
- Expected selectors: `[data-wc-gallery]`, `[data-wc-swatches]`, `[data-wc-quickview]`, `[data-wc-filter]`.
- After AJAX filtering, swatches reinit from `hd:filter:updated`.
- QuickView uses document-level delegation; no explicit reinit required.

## Filter Gotchas

- `collectFilters()` must collect checkboxes, active swatches, search, `select[name^="hd_"]`, and range sliders.
- Restore/reset must mirror every collected input type.
- Reset calls `handleFilterChange()` directly; do not also dispatch `change`.

## Validation

- JS touched: `node --check path\to\edited.js`.
- Vite/SCSS/import/manifest touched: `pnpm build`.
- Check no unsafe `innerHTML` string assembly for untrusted data.
- Check lazy/delegated UI remains idempotent after AJAX/DOM replacement.
- Check responsive fixed controls have stable dimensions and no obvious overflow.
