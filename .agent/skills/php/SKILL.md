---
name: php
description: Use for PHP 8.1+ WP code, imports, types, PHPDoc arrays, hook callbacks, removable actions, or namespaced class references.
compatibility: PHP 8.1+ WordPress code in 2026, especially namespaced theme/plugin classes.
---

# PHP

## Source Of Truth

- Constraints: `.agent/rules/constraints.md`.
- Autoload: target theme/plugin `composer.json`.
- Runtime probes: `vendor\bin\wp eval ...` or `vendor\bin\wp eval-file ...`.
- This skill is authoritative for project FQCN import style.

## Syntax

- Use PHP 8.1+ features when clearer: promotion, `readonly`, enums, `match`, arrow functions.
- Prefer `??` over broad `isset()` when source shape is known.
- Avoid nested ternaries.
- Do not mask root causes with surface guards.
- Do not assign inside `return`.

## Types

- Type params/returns.
- Use `?Type` for nullable values.
- Use `Type|false` for WP APIs returning false.
- Use PHPDoc shapes like `array<string, mixed>` across method boundaries.

## Imports

- Import project classes with `use`, e.g. `use HD\Core\Helper;`.
- Do not inline project FQCNs like `\HD\Core\Helper::`; import then call `Helper::`.
- Keep global WP/PHP classes inline with leading slash: `\WP_Error`, `\WC_Order`, `\PLL_Language`, `\Throwable`, `\NOOP_Translations`.

## Iteration

- Use `array_map()` for transforms.
- Use `array_filter()` for filtering.
- Use `foreach` for side effects or clearer logic.

## Hooks

- Use first-class callables like `$this->method(...)` for stable singleton hooks.
- Use `[$this, 'method']` when hooks must be removed or are registered in loops/recurring filters.
- First-class callables create unique closures and cannot be removed reliably.
- `remove_action()`/`remove_filter()` must use the exact registered priority.

## Validation

- Edited PHP: `php -l path\to\file.php`.
- New/moved PSR-4 classes: `composer dump-autoload -o`.
- WP-dependent behavior: targeted `vendor\bin\wp eval ...`.
- Final hygiene: `git diff --check`.
