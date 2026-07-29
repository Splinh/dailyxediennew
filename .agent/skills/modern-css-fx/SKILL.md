---
name: modern-css-fx
description: Use for View Transitions API, CSS Scroll-driven animations (@scroll-timeline), CSS Anchor Positioning, and HTML Popover API / <dialog> elements.
---

# Modern CSS FX & Web APIs

Use this skill when implementing modern native web visual effects, page transitions, scroll-driven animations, popovers, and dialogs without heavy JavaScript dependencies.

## Core Rules

- Prefer native CSS Web APIs over heavy JS animation libraries for structural effects.
- Use View Transitions API for seamless page and DOM state transitions.
- Use CSS Scroll-driven Animations (`animation-timeline: scroll()`) for scroll indicators and progress bars running hardware-accelerated on the compositor thread.
- Use HTML Popover API (`popover`, `popovertarget`) and `<dialog>` element for accessible, zero-JS modals and dropdown menus.
- Use CSS Anchor Positioning (`anchor-name`, `position-anchor`) for positioning tooltips and popups relative to trigger elements.

## View Transitions API

```javascript
// Wrap DOM mutations in document.startViewTransition
if (document.startViewTransition) {
  document.startViewTransition(() => {
    updateDOMState();
  });
} else {
  updateDOMState();
}
```

```css
/* CSS View Transition Name */
.hero-card {
  view-transition-name: hero-card-active;
}
```

## CSS Scroll-driven Animations

```css
@keyframes scroll-progress {
  from { scale: 0 1; }
  to { scale: 1 1; }
}

.scroll-indicator {
  animation: scroll-progress linear;
  animation-timeline: scroll(root block);
  transform-origin: left;
}
```

## Native Popover API & HTML Dialog

```html
<!-- Native Popover -->
<button popovertarget="my-popover">Open Menu</button>
<div id="my-popover" popover>
  <p>Popover content rendered in top-layer DOM.</p>
</div>

<!-- Native Accessible Dialog -->
<dialog id="modal-dialog">
  <form method="dialog">
    <h2>Dialog Header</h2>
    <button type="submit">Close</button>
  </form>
</dialog>
```

## Checklist

- [ ] View transitions guarded with `if (document.startViewTransition)`.
- [ ] Scroll animations run on compositor using `animation-timeline`.
- [ ] Popovers use native `popover` attribute.
- [ ] Modals use `<dialog>` element with `showModal()` / `close()`.
