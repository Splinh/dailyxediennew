---
description: Choose the right workflow for feature, refactor, bug, or review work.
---

# Feature Workflow

Use this workflow only when the next phase is unclear. It is a router, not a
replacement for `/grill-me`, `/plan`, `/build`, `/diagnose`, or `/review`.

## Routes

| Situation | Route |
| --- | --- |
| Vague feature or architecture | `/grill-me` -> auto `/plan` when decisions are resolved -> `/build` -> `/review` |
| Clear feature or refactor | `/plan` -> `/build` -> `/review` |
| Bug, warning, regression, failed verification | `/diagnose` -> `/review` |
| Review only | `/review` |
| Durable lesson after non-routine work | `/learn` |

## Rules

- Do not skip `/plan` before `/build`.
- Treat `/diagnose` as conditional, not mandatory after every build.
- Use `/grill-me` when requirements are vague; it may hand off to `/plan` after the decision log is complete.
- Use `/learn` only when the user asks to persist durable project knowledge.

## Minimal Tracker

```markdown
Feature:
Current phase:
Plan path:
Next workflow:
Blocked by:
```
