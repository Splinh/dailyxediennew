# Đại Lý Bluera Việt Nhật Út Tân

## Mission

Create implementation-ready, token-driven UI guidance for Đại Lý Bluera Việt Nhật Út Tân that is optimized for consistency, accessibility, and fast delivery across e-commerce storefront.

## Brand

- Product/brand: Đại Lý Bluera Việt Nhật Út Tân
- URL: https://bluerabike.com/local_store/dai-ly-bluera-viet-nhat-ut-tan/
- Audience: online shoppers and consumers
- Product surface: e-commerce storefront

## Style Foundations

- Visual style: clean, functional, implementation-oriented
- Main font style: `font.family.primary=Montserrat`, `font.family.stack=Montserrat, -apple-system, BlinkMacSystemFont, Segoe UI, system-ui, Ubuntu, Arial, cursive, sans-serif`, `font.size.base=16px`, `font.weight.base=400`, `font.lineHeight.base=25.888px`
- Typography scale: `font.size.xs=0px`, `font.size.sm=12px`, `font.size.md=13px`, `font.size.lg=14px`, `font.size.xl=15px`, `font.size.2xl=16px`, `font.size.3xl=17px`, `font.size.4xl=18px`
- Color palette: `color.text.primary=#11283c`, `color.text.secondary=#ffffff`, `color.text.tertiary=#cccccc`, `color.text.inverse=#5a6d7e`, `color.surface.base=#000000`, `color.surface.raised=#1e78c2`, `color.surface.strong=#1b7a3d`
- Spacing scale: `space.1=4px`, `space.2=5px`, `space.3=6px`, `space.4=8px`, `space.5=10px`, `space.6=11.2px`, `space.7=12px`, `space.8=14px`
- Radius/shadow/motion tokens: `radius.xs=4px`, `radius.sm=8px`, `radius.md=12px`, `radius.lg=16px`, `radius.xl=50px` | `shadow.1=rgba(0, 0, 0, 0.04) 0px 1px 3px 0px, rgba(0, 0, 0, 0.04) 0px 2px 8px 0px`, `shadow.2=rgba(27, 122, 61, 0.25) 0px 2px 8px 0px`, `shadow.3=rgba(30, 120, 194, 0.25) 0px 2px 8px 0px`, `shadow.4=rgba(0, 0, 0, 0.25) 0px 2px 10px 0px` | `motion.duration.instant=200ms`, `motion.duration.fast=300ms`, `motion.duration.normal=400ms`

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
- Include known page component density: links (118), buttons (57), lists (38), cards (18), inputs (10), navigation (8).

## Quality Gates

- Every non-negotiable rule must use "must".
- Every recommendation should use "should".
- Every accessibility rule must be testable in implementation.
- Teams should prefer system consistency over local visual exceptions.
