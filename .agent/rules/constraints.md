---
trigger: always_on
description: Minimal always-on constraints for the 2026 WordPress workspace.
---

# 2026 Always-On Constraints

## Project Role

- Act as a Staff Engineer / WordPress Architect.
- Global coding behavior lives in `~/.gemini/GEMINI.md`; do not repeat it here.

## Command And Workflow

- Always read and apply the `.agent/skills/karpathy-guidelines/SKILL.md` skill when creating implementation plans, writing task checklists, and implementing/reviewing code.
- Use `pnpm build`; never use `npm run build`.
- Prefer targeted verification: `php -l`, `node --check`, `vendor\bin\wp eval`,
  `vendor\bin\wp eval-file`, `curl`, `pnpm build`, and `git diff --check`.
- Do not run browser tests unless the user explicitly asks.

## WordPress Runtime

- Sanitize input, validate capabilities/nonces, and escape output at the
  boundary.
- Prefer local project utilities and contracts over generic WordPress advice.
- Cross-module integration must use public hooks/contracts and remain safe when
  either module is disabled.
- **ACF & Database Preservation**:
  - Do not define duplicate ACF fields (e.g. `tskt_specs`) if similar fields already exist in active plugins or database (e.g. `tskt_rows` in `hda` plugin).
  - Scripts populating mock/demo data must never set existing media/image field IDs to `0` or empty string on active pages.
  - CSV import scripts must check and preserve existing database values; do not overwrite with empty values if the CSV column or cell is blank.

## Theme Built-In Features

Prefer existing HD theme FX modules over custom code. The theme ships
with lazy-loaded `data-fx-*` components that cover most UI patterns:

- `data-fx-slider` (Swiper): hero slider, product carousel, news slider,
  testimonial carousel, playlist thumbnail slider.
- `data-fx-tabs`: product tabs, content tabs, category tabs.
- `data-fx-accordion`: FAQ, collapsible sections.
- `data-fx-modal`: video popup, confirmation dialogs, image preview.
- `data-fx-lightbox` (PhotoSwipe): gallery zoom.
- `data-fx-video`: lazy YouTube/Vimeo click-to-play embed.
- `data-fx-dropdown` / `data-fx-dropdown-menu`: search, nav, filter selects.
- `data-fx-sticky`: header sticky, sidebar sticky.
- `data-fx-off-canvas`: mobile drawer, sidebar filter panel.

Do not write custom JS for functionality that an FX module already provides.
Custom JS is acceptable only when the logic goes beyond what the FX module
offers (e.g., store locator filtering, multi-step form wizards, product
query presets).

## PageSpeed And SEO

Every page or section must be optimized for both performance and SEO by
default. Apply these rules without being asked:

- Use `loading="lazy"` and explicit `width`/`height` (or `aspect-ratio`) on
  all images and iframes below the fold; use `fetchpriority="high"` for the
  LCP image only.
- Use semantic HTML (`<section>`, `<article>`, `<nav>`, `<h1>`–`<h6>`) with a
  single `<h1>` per page and proper heading hierarchy.
- Prevent CLS: reserve space for dynamic content, sliders, and lazy-loaded
  blocks with skeleton/placeholder dimensions.
- Minimise render-blocking: defer non-critical JS, inline critical CSS only
  when justified, avoid large synchronous `<script>` blocks.
- Escape and set meaningful `alt` text on images; use `aria-label` on
  interactive elements that lack visible text.
- Output structured data (`JSON-LD`) when the section warrants it (FAQ,
  Product, Video, Breadcrumb).
- Keep `<script type="application/json">` data islands small; paginate or
  lazy-load large datasets.
- Avoid layout-triggering inline styles; prefer utility classes or CSS custom
  properties that the browser can batch.

## Lazy-Loaded Local Skills

Load only the skill needed for the current task:

- Project routing: `.agent/skills/project-knowledge-base/SKILL.md`
- Coding discipline for non-trivial implementation, review, or refactor:
  `.agent/skills/karpathy-guidelines/SKILL.md`
- HD theme, modules, WooCommerce, Polylang, ACF, CF7, theme REST:
  `.agent/skills/hd-theme/SKILL.md`
- HDA plugin lifecycle, modules, DB, assets, settings:
  `.agent/skills/hda-plugin/SKILL.md`
- HDAT gateway, providers, tokens, drivers, settings:
  `.agent/skills/hdat-plugin/SKILL.md`
- Vite, Tailwind, SCSS, JS, FX loaders, frontend WooCommerce:
  `.agent/skills/frontend/SKILL.md`
- PHP syntax, imports, types, hook callbacks:
  `.agent/skills/php/SKILL.md`
