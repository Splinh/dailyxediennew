---
name: impeccable
description:
    Use when designing, redesigning, auditing, polishing, or critiquing a frontend interface for
    production-grade visual quality. Covers color/contrast, typography, layout, motion, interaction,
    anti-patterns, accessibility, responsive behavior, and design-first iteration. Not for
    backend-only or non-UI tasks.
license: MIT
source: pbakaus/impeccable v3.9.1
---

# Impeccable: Production-Grade Frontend Design Skill

## 2026 Compatibility

- Source: `pbakaus/impeccable`, adapted as standalone skill (no CLI/scripts dependency).
- Scope: any frontend surface — landing pages, product UI, dashboards, components, forms, app
  shells, onboarding, empty states.
- Project rules in `.agent/rules/constraints.md` override this skill when they conflict.
- When used alongside `taste-skill`, this skill provides the **craft layer** (contrast, spacing,
  motion, accessibility) while taste-skill provides the **aesthetic direction** (vibe, layout
  variety, anti-template).
- For project-specific asset work, also load `.agent/skills/frontend/SKILL.md`.

> Production-grade code. Committed design choices. Exceptional craft. Every rule below applies
> during implementation, not just review.

---

## 0. BEFORE YOU TOUCH CODE

1. **Read the brief.** What is the user building? Who is it for? What counts as success?
2. **Read existing code first.** Familiarize yourself with any existing design system, conventions,
   tokens, and components. Don't reinvent the wheel; use what's there when it works.
3. **Determine the register:**
    - **Brand register** — marketing, landing page, campaign, portfolio, editorial (design IS the
      product).
    - **Product register** — app UI, admin, dashboard, tool (design SERVES the product).
    - Pick by first match: (1) task cue, (2) surface in focus, (3) project context.

---

## 1. COLOR

### Contrast (non-negotiable)

- Body text: **≥ 4.5:1** against its background.
- Large text (≥ 18px or bold ≥ 14px): **≥ 3:1**.
- Placeholder text: same 4.5:1, not muted-gray default.
- Most common AI failure: muted gray body text on tinted near-white. If close, bump toward ink end.

### Color system

- Use **OKLCH** for new projects.
- Gray text on colored background looks washed out. Use a darker shade of the background's hue, or a
  transparency of the text color.
- Tinted neutrals: add 0.005–0.015 chroma toward the brand's hue. Don't default-tint toward warm or
  cool.

### Anti-patterns

- **The cream/sand/beige body bg is the saturated AI default.** The whole warm-neutral band (OKLCH L
  0.84–0.97, C < 0.06, hue 40–100) reads as cream/sand regardless of token name (`--paper`,
  `--cream`, `--sand`, `--bone`, `--ivory`, `--linen`, `--parchment`). If the brief says "warm" or
  "editorial", carry warmth through accent + typography + imagery, not body bg.
- **Don't choose dark vs. light as a default.** Not dark "because tools look cool dark." Not light
  "to be safe." Write one sentence of physical scene: who uses this, where, under what light, in
  what mood. Let that pick.

---

## 2. TYPOGRAPHY

### Hard rules

- Cap body line length at **65–75ch**.
- Hero/display heading ceiling: `clamp()` max **≤ 6rem** (~96px). Above that, the page is shouting.
- Display heading letter-spacing floor: **≥ −0.04em**. Anything tighter and letters touch. −0.02 to
  −0.03em is plenty for tight grotesque display.
- Use `text-wrap: balance` on h1–h3 for even line lengths.
- Use `text-wrap: pretty` on long prose to reduce orphans.

### Pairing

- Don't pair fonts that are similar but not identical (two geometric sans-serifs, two humanist
  sans-serifs).
- Pair on a **contrast axis** (serif + sans, geometric + humanist) or use **one family** in multiple
  weights.

---

## 3. LAYOUT

### Structure

- Vary spacing for rhythm. Uniform spacing is monotonous.
- **Cards are the lazy answer.** Use them only when they're truly the best affordance. Nested cards
  are always wrong.
- Flexbox for 1D, Grid for 2D. Don't default to Grid when `flex-wrap` would be simpler.
- Responsive grids without breakpoints: `repeat(auto-fit, minmax(280px, 1fr))`.

### Z-index

- Build a semantic z-index scale: `dropdown → sticky → modal-backdrop → modal → toast → tooltip`.
- Never arbitrary values like 999 or 9999.

---

## 4. MOTION

### Core principles

- Motion should be **intentional**, not an afterthought. Consider it as part of the build.
- Don't animate CSS layout properties unless truly needed.
- Ease out with exponential curves (ease-out-quart / quint / expo). **No bounce, no elastic.**

### Accessibility (non-negotiable)

- Every animation needs `@media (prefers-reduced-motion: reduce)` alternative: typically a crossfade
  or instant transition.
- Reveal animations must enhance an already-visible default. Don't gate content visibility on a
  class-triggered transition; transitions pause on hidden tabs and headless renderers, so the reveal
  never fires and the section ships blank.

### Quality

- Staggering items within one list is fine. The tell of AI slop is the **uniform reflex** — one
  identical entrance applied to every section. Each reveal should fit what it reveals.
- Premium motion uses more than transform/opacity. **Blur, backdrop-filter, clip-path, mask,
  shadow/glow** are part of the palette when they improve the effect and stay smooth.
- Use libraries for advanced motion (GSAP, Motion, Anime.js, Lenis).

---

## 5. INTERACTION

### Dropdowns & popovers

- Dropdowns with `position: absolute` inside `overflow: hidden` or `overflow: auto` will be clipped.
  Use native `<dialog>` / popover API, `position: fixed`, or a portal to escape the stacking
  context.

### Focus & keyboard

- All interactive elements must be keyboard-accessible.
- Focus rings must be visible and have sufficient contrast.
- Tab order must be logical.

---

## 6. ANTI-PATTERN LIBRARY

These are the most common AI-generated design failures. Check your output against each:

| Anti-Pattern                               | Fix                                                              |
| ------------------------------------------ | ---------------------------------------------------------------- |
| Purple/blue gradient on everything         | Use the project's actual brand palette                           |
| Cream/sand/beige body bg                   | Carry warmth through accent + type, not bg                       |
| Muted gray body text (< 4.5:1 contrast)    | Bump toward ink end of the ramp                                  |
| Identical card grid for every section      | Vary layout: full-bleed, split, asymmetric, editorial            |
| Letter-spacing tighter than −0.04em        | Floor at −0.04em; −0.02 to −0.03 is better                       |
| Uniform entrance animation on all sections | Each reveal should fit what it reveals                           |
| z-index: 9999                              | Use a semantic z-index scale                                     |
| Nested cards (card inside card)            | Flatten: use spacing + borders instead                           |
| Generic Lucide icons everywhere            | Use Iconify Solar (outline, broken, duotone) for variety         |
| Hero heading > 6rem                        | Cap at 6rem with clamp()                                         |
| No reduced-motion alternative              | Add `@media (prefers-reduced-motion: reduce)` to every animation |
| Content gated behind JS-triggered reveal   | Content must be visible by default; animation enhances           |

---

## 7. DESIGN-FIRST ITERATION WORKFLOW

When building or redesigning a frontend surface:

1. **Lock the hero first.** The hero section is half the job. Everything else flows from it.
2. **Build section-by-section**, not full-page. Hero → features → social proof → CTA → footer.
   Faster iterations, better creative control.
3. **Change 1–2 things per iteration.** Don't make 10 changes at once. One variable at a time
   produces traceable improvement.
4. **Screenshots beat paragraphs.** A screenshot contains fonts, spacing, colors, icons, layout.
   Reference images are the fastest way to communicate intent.
5. **Verify in-browser.** Don't ship without checking at mobile (375px), tablet (768px), and desktop
   (1280px+).

---

## 8. RESPONSIVE BEHAVIOR

- Mobile is not "the desktop version but narrower." Rethink layout, hierarchy, and touch targets.
- Touch targets: minimum **44×44px** (WCAG 2.5.5).
- Test at **375px, 768px, 1280px, 1440px**.
- Use `clamp()` for fluid typography and spacing instead of hard breakpoints where possible.

---

## 9. ACCESSIBILITY BASELINE

These are non-negotiable for production:

- Color contrast ratios (see §1).
- `prefers-reduced-motion` (see §4).
- Semantic HTML (`<nav>`, `<main>`, `<article>`, `<section>`, `<aside>`, `<footer>`).
- ARIA labels on icon-only buttons.
- Skip-to-content link.
- Focus management for modals and drawers.
- Alt text for meaningful images; `alt=""` for decorative.
