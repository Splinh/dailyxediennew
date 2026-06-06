# AIE LUMI 946

## Mission

Create implementation-ready, token-driven UI guidance for AIE LUMI 946 that is optimized for consistency, accessibility, and fast delivery across e-commerce storefront.

## Brand

- Product/brand: AIE LUMI 946
- URL: https://dailyxedien.vn/san-pham/aie-lumi-946/
- Audience: online shoppers and consumers
- Product surface: e-commerce storefront

## Style Foundations

- Visual style: clean, functional, implementation-oriented
- Main font style: `font.family.primary=Be Vietnam Pro`, `font.family.stack=Be Vietnam Pro, sans-serif`, `font.size.base=16px`, `font.weight.base=600`, `font.lineHeight.base=20.8px`
- Typography scale: `font.size.xs=0px`, `font.size.sm=9.6px`, `font.size.md=10.8px`, `font.size.lg=11px`, `font.size.xl=12px`, `font.size.2xl=12.24px`, `font.size.3xl=12.8px`, `font.size.4xl=12.96px`
- Color palette: `color.text.primary=#1e73be`, `color.surface.base=#000000`, `color.text.tertiary=#ffffff`, `color.text.inverse=#333333`, `color.surface.strong=#f9f9f9`
- Spacing scale: `space.1=1.08px`, `space.2=1.6px`, `space.3=1.68px`, `space.4=1.92px`, `space.5=3.6px`, `space.6=4px`, `space.7=5px`, `space.8=5.28px`
- Radius/shadow/motion tokens: `radius.xs=3px`, `radius.sm=4px`, `radius.md=5px`, `radius.lg=50px`, `radius.xl=99px`, `radius.2xl=999px` | `shadow.1=rgba(0, 0, 0, 0.1) 0px 1px 2px 0px inset`, `shadow.2=rgba(255, 255, 255, 0) 0px 0px 0px -7px` | `motion.duration.instant=200ms`, `motion.duration.fast=300ms`

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
- Include known page component density: links (545), buttons (141), inputs (61), lists (37), cards (4), navigation (3), tables (2).

## Quality Gates

- Every non-negotiable rule must use "must".
- Every recommendation should use "should".
- Every accessibility rule must be testable in implementation.
- Teams should prefer system consistency over local visual exceptions.
