---
description: Review implemented code for bugs, regressions, security, performance, and contract drift.
---

# Review Workflow

Use this workflow when the user invokes `/review`, asks for an audit, asks to review a plan against implementation, or asks to review and fix issues.

## Review Stance

Lead with findings. Prioritize:

1. Bugs and behavioral regressions.
2. Security issues.
3. Runtime contract drift.
4. Performance risks.
5. Missing verification.

Do not lead with praise or broad summaries.

## Hard Rules

- Ground each finding in file/line evidence.
- Verify claims with code inspection or commands when practical.
- Do not flag style-only preferences as issues.
- Do not flag speculative edge cases without a plausible runtime path.
- If the user asks to fix issues, patch concrete defects directly after identifying them.

## Skill Preflight

1. Read `.agent/skills/project-knowledge-base/SKILL.md`.
2. Read `.agent/skills/karpathy-guidelines/SKILL.md`.
3. Read only the domain skill for the reviewed area.

## Audit Checklist

### PHP And Architecture

- PSR-4 path and namespace consistency.
- `defined('ABSPATH') || exit;` guards.
- Local utilities preferred over raw globals where project contracts require it.
- Hooks use removable-safe callback style where needed.
- Cross-module integration uses public hooks, not custom bridge filters.
- No dead abstractions or speculative extension points.

### Security

- Input uses `wp_unslash()` plus the right sanitizer.
- Output is escaped with `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()`.
- AJAX/REST has capability and nonce checks.
- REST routes define `permission_callback` and parameter validation.
- SQL uses project DB helpers, `$wpdb->prepare()`, or `WP_Query` as appropriate.

### Bugs And Data Flow

- No root-cause masking with `@`, broad `empty()`, or repeated fallback reads.
- Data is initialized at the source before downstream use.
- False/null/error returns from WordPress APIs are handled intentionally.
- WooCommerce HPOS-safe getters/setters are used.
- Cache invalidation exists for mutations.

### Frontend

- No inline HTML string rendering in JavaScript; use `wp.template()`.
- Dynamic fragments use delegated events.
- Lazy modules are idempotent.
- CLS-critical styles live in primary SCSS, not lazy chunks.
- AJAX payload shape matches PHP handlers.

## Verification Menu

Use targeted checks first. Common commands:

```bash
php -l path/to/file.php
vendor\bin\wp eval "echo wp_get_theme()->get('Name');"
vendor\bin\wp eval-file path/to/probe.php
node --check path/to/file.js
pnpm build
composer dump-autoload -o
proxy curl.exe -s -o NUL -w "HTTP %{http_code}\n" https://2026.test/wp-json/
git diff --check
```

## Report Format

```markdown
## Findings

### Critical
- `path/file.php:123` - Issue. Impact. Fix.

### Medium
- `path/file.js:45` - Issue. Impact. Fix.

### Minor
- `path/file.scss:12` - Issue. Impact. Fix.

## Verification

- `[command]`: PASS/FAIL

## Notes

- Open questions or residual risk.
```

If no issues are found, say that clearly and list remaining verification gaps.
