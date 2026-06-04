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
