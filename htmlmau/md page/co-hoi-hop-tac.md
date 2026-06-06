# Tuyển Đại Lý Xe Điện – Cơ Hội Kinh Doanh Hấp Dẫn 2026

## Mission

Create implementation-ready, token-driven UI guidance for Tuyển Đại Lý Xe Điện – Cơ Hội Kinh Doanh Hấp Dẫn 2026 that is optimized for consistency, accessibility, and fast delivery across e-commerce storefront.

## Brand

- Product/brand: Tuyển Đại Lý Xe Điện – Cơ Hội Kinh Doanh Hấp Dẫn 2026
- URL: https://dailyxedien.vn/tuyen-dai-ly-xe-dien-co-hoi-kinh-doanh-hap-dan-2026/#
- Audience: online shoppers and consumers
- Product surface: e-commerce storefront

## Style Foundations

- Visual style: clean, functional, implementation-oriented
- Main font style: `font.family.primary=Be Vietnam Pro`, `font.family.stack=Be Vietnam Pro, sans-serif`, `font.size.base=16px`, `font.weight.base=400`, `font.lineHeight.base=25.6px`
- Typography scale: `font.size.xs=6.72px`, `font.size.sm=9.6px`, `font.size.md=11.2px`, `font.size.lg=12px`, `font.size.xl=12.24px`, `font.size.2xl=12.8px`, `font.size.3xl=13.19px`, `font.size.4xl=13.6px`
- Color palette: `color.text.primary=#1e73be`, `color.surface.base=#000000`, `color.text.tertiary=#ffffff`, `color.text.inverse=#444444`, `color.surface.strong=#f4f4f4`
- Spacing scale: `space.1=0.67px`, `space.2=1px`, `space.3=1.12px`, `space.4=1.44px`, `space.5=1.5px`, `space.6=1.62px`, `space.7=1.68px`, `space.8=1.92px`
- Radius/shadow/motion tokens: `radius.xs=2px`, `radius.sm=3px`, `radius.md=4px`, `radius.lg=5px`, `radius.xl=10px`, `radius.2xl=50px`, `radius.step7=99px`, `radius.step8=999px` | `shadow.1=rgba(0, 0, 0, 0.1) 0px 1px 2px 0px inset` | `motion.duration.instant=200ms`, `motion.duration.fast=300ms`

## Accessibility

- Target: WCAG 2.2 AA
- Keyboard-first interactions required.
- Focus-visible rules required.
- Contrast constraints required.

## Writing Tone

Concise, confident, implementation-focused.

## Rules: Do

- Use semantic tokens, not raw hex values, in component guidance.
- Every component must define states for default, hover, focus-visible, active, disabled, loading, and error.
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
- Include known page component density: links (465), buttons (103), inputs (67), lists (41), navigation (4), cards (2).

## Quality Gates

- Every non-negotiable rule must use "must".
- Every recommendation should use "should".
- Every accessibility rule must be testable in implementation.
- Teams should prefer system consistency over local visual exceptions.
