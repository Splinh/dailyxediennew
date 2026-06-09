# Hệ thống Đại Lý và Cửa Hàng xe điện toàn quốc

## Mission

Create implementation-ready, token-driven UI guidance for Hệ thống Đại Lý và Cửa Hàng xe điện toàn
quốc that is optimized for consistency, accessibility, and fast delivery across e-commerce
storefront.

## Brand

- Product/brand: Hệ thống Đại Lý và Cửa Hàng xe điện toàn quốc
- URL: https://dailyxedien.vn/he-thong-dai-ly-va-cua-hang-xe-dien-toan-quoc/
- Audience: online shoppers and consumers
- Product surface: e-commerce storefront

## Style Foundations

- Visual style: structured, tokenized, content-first
- Main font style: `font.family.primary=Be Vietnam Pro`,
  `font.family.stack=Be Vietnam Pro, sans-serif`, `font.size.base=14px`, `font.weight.base=400`,
  `font.lineHeight.base=18.2px`
- Typography scale: `font.size.xs=9.6px`, `font.size.sm=12px`, `font.size.md=12.8px`,
  `font.size.lg=13px`, `font.size.xl=13.19px`, `font.size.2xl=13.5px`, `font.size.3xl=14px`,
  `font.size.4xl=14.4px`
- Color palette: `color.text.primary=#0078a8`, `color.text.secondary=#1e73be`,
  `color.text.tertiary=#333333`, `color.text.inverse=#ffffff`, `color.surface.base=#000000`,
  `color.surface.muted=#f0f4ff`, `color.surface.raised=#f0f0f0`, `color.surface.strong=#f5f5f5`,
  `color.border.strong=#bbdefb`
- Spacing scale: `space.1=1.68px`, `space.2=1.92px`, `space.3=3px`, `space.4=3.6px`, `space.5=4px`,
  `space.6=5px`, `space.7=5.28px`, `space.8=6px`
- Radius/shadow/motion tokens: `radius.xs=2px`, `radius.sm=4px`, `radius.md=5px`, `radius.lg=8px`,
  `radius.xl=50px`, `radius.2xl=99px`, `radius.step7=999px` |
  `shadow.1=rgba(21, 101, 192, 0.4) 0px 2px 8px 0px`, `shadow.2=rgba(0, 0, 0, 0.08) 0px 1px 4px 0px`
  | `motion.duration.instant=150ms`, `motion.duration.fast=160ms`, `motion.duration.normal=180ms`,
  `motion.duration.slow=200ms`, `motion.duration.slower=300ms`, `motion.duration.step6=400ms`

## Accessibility

- Target: WCAG 2.2 AA
- Keyboard-first interactions required.
- Focus-visible rules required.
- Contrast constraints required.

## Writing Tone

Concise, confident, implementation-focused.

## Rules: Do

- Use semantic tokens, not raw hex values, in component guidance.
- Every component must define states for default, hover, focus-visible, active, disabled, loading,
  and error.
- Component behavior should specify responsive and edge-case handling.
- Interactive components must document keyboard, pointer, and touch behavior.
- Accessibility acceptance criteria must be testable in implementation.

## Rules: Don't

- Do not allow low-contrast text or hidden focus indicators.
- Do not introduce one-off spacing or typography exceptions.
- Do not use ambiguous labels or non-descriptive actions.
- Do not ship component guidance without explicit state rules.

## Guideline Authoring Workflow

1. Restate design intent in one sentence.
2. Define foundations and semantic tokens.
3. Define component anatomy, variants, interactions, and state behavior.
4. Add accessibility acceptance criteria with pass/fail checks.
5. Add anti-patterns, migration notes, and edge-case handling.
6. End with a QA checklist.

## Required Output Structure

- Context and goals.
- Design tokens and foundations.
- Component-level rules (anatomy, variants, states, responsive behavior).
- Accessibility requirements and testable acceptance criteria.
- Content and tone standards with examples.
- Anti-patterns and prohibited implementations.
- QA checklist.

## Component Rule Expectations

- Include keyboard, pointer, and touch behavior.
- Include spacing and typography token requirements.
- Include long-content, overflow, and empty-state handling.
- Include known page component density: links (1276), buttons (484), cards (266), lists (136),
  inputs (65), navigation (2).

## Quality Gates

- Every non-negotiable rule must use "must".
- Every recommendation should use "should".
- Every accessibility rule must be testable in implementation.
- Teams should prefer system consistency over local visual exceptions.
