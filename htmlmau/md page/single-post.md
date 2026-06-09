# BLUERA VIỆT NHẬT LINH LY SẮP KHAI TRƯƠNG – ĐỊA CHỈ MUA XE ĐIỆN TẠI CÀ MAU CHÍNH HÃNG, UY TÍN CÙNG ƯU ĐÃI HẤP DẪN

## Mission

Create implementation-ready, token-driven UI guidance for BLUERA VIỆT NHẬT LINH LY SẮP KHAI TRƯƠNG –
ĐỊA CHỈ MUA XE ĐIỆN TẠI CÀ MAU CHÍNH HÃNG, UY TÍN CÙNG ƯU ĐÃI HẤP DẪN that is optimized for
consistency, accessibility, and fast delivery across e-commerce storefront.

## Brand

- Product/brand: BLUERA VIỆT NHẬT LINH LY SẮP KHAI TRƯƠNG – ĐỊA CHỈ MUA XE ĐIỆN TẠI CÀ MAU CHÍNH
  HÃNG, UY TÍN CÙNG ƯU ĐÃI HẤP DẪN
- URL: https://dailyxedien.vn/bluera-viet-nhat-linh-ly-dia-chi-mua-xe-dien-tai-ca-mau/
- Audience: online shoppers and consumers
- Product surface: e-commerce storefront

## Style Foundations

- Visual style: clean, functional, implementation-oriented
- Main font style: `font.family.primary=Be Vietnam Pro`,
  `font.family.stack=Be Vietnam Pro, sans-serif`, `font.size.base=16px`, `font.weight.base=400`,
  `font.lineHeight.base=25.6px`
- Typography scale: `font.size.xs=6.72px`, `font.size.sm=9.6px`, `font.size.md=10.8px`,
  `font.size.lg=11.2px`, `font.size.xl=12px`, `font.size.2xl=12.24px`, `font.size.3xl=12.8px`,
  `font.size.4xl=13px`
- Color palette: `color.surface.base=#000000`, `color.text.secondary=#1e73be`,
  `color.text.tertiary=#ffffff`, `color.text.inverse=#c3c4c7`, `color.surface.strong=#e6f2e8`
- Spacing scale: `space.1=0.67px`, `space.2=1px`, `space.3=1.08px`, `space.4=1.12px`,
  `space.5=1.44px`, `space.6=1.5px`, `space.7=1.68px`, `space.8=1.92px`
- Radius/shadow/motion tokens: `radius.xs=2px`, `radius.sm=3px`, `radius.md=5px`, `radius.lg=8px`,
  `radius.xl=50px`, `radius.2xl=99px`, `radius.step7=999px` |
  `shadow.1=rgba(0, 0, 0, 0.1) 0px 1px 2px 0px inset`, `shadow.2=rgba(0, 0, 0, 0.1) 0px 2px 8px 0px`
  | `motion.duration.instant=200ms`, `motion.duration.fast=300ms`, `motion.duration.normal=400ms`

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
- Include known page component density: links (614), buttons (129), lists (67), inputs (60),
  navigation (4), cards (1), tables (1).

## Quality Gates

- Every non-negotiable rule must use "must".
- Every recommendation should use "should".
- Every accessibility rule must be testable in implementation.
- Teams should prefer system consistency over local visual exceptions.
