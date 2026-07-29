---
name: tailwind-v4-mastery
description: Use for Tailwind CSS v4 development, @theme directive, CSS variables, @utility directives, Container Queries, :has() selector patterns, and Vite integration without legacy tailwind.config.js.
---

# Tailwind CSS v4 Mastery

Use this skill when styling with Tailwind CSS v4 in themes, plugins, Vite setups, or modern web interfaces.

## Core Rules

- Never create or look for `tailwind.config.js` or `tailwind.config.ts`. Tailwind v4 is CSS-first.
- Import Tailwind via `@import "tailwindcss";` in primary SCSS/CSS entry points.
- Define design tokens in CSS using `@theme` block in main CSS/SCSS files.
- Prefer CSS variable architecture (`var(--color-primary)`) over hardcoded color values.
- Use `@utility` for custom utilities instead of `@layer utilities`.
- Enforce responsive design with `@container` queries and native CSS `:has()` selectors.

## Theme Token Configuration (`@theme`)

```css
@import "tailwindcss";

@theme {
  --color-primary: #0f172a;
  --color-primary-hover: #1e293b;
  --color-accent: #0284c7;

  --font-sans: 'Inter', system-ui, -apple-system, sans-serif;
  --font-mono: 'JetBrains Mono', monospace;

  --radius-card: 12px;
  --shadow-card: 0 4px 20px -2px rgba(0, 0, 0, 0.08);
}
```

## Utility Directives (`@utility`)

```css
@utility glass-panel {
  background-color: rgba(255, 255, 255, 0.75);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}
```

## Container Queries & Advanced Selectors

- Container declaration: `@container` or `@container/sidebar`
- Container query utilities: `@min-[320px]:grid-cols-2`, `@max-[640px]:flex-col`
- Parent state matching with `:has()`: `group-has-[:checked]:bg-primary-50`

## Vite Integration

Ensure Vite configuration uses `@tailwindcss/vite`:

```typescript
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
});
```

## Checklist

- [ ] Primary CSS uses `@import "tailwindcss";`.
- [ ] Design tokens live inside `@theme { ... }`.
- [ ] No `tailwind.config.js` file present.
- [ ] Custom utility classes use `@utility name { ... }`.
- [ ] Mobile/responsive layouts verified with container queries where appropriate.
