---
description: Pressure-test vague requirements or designs before planning implementation.
---

# Grill Me Workflow

Use this workflow when the user invokes `/grill-me`, asks to be grilled, or wants to stress-test a feature, plan, or architecture.

## Hard Rules

- Do not edit production code.
- Do not create an implementation plan until major decisions are resolved.
- Ask one question at a time.
- If the answer is discoverable from the codebase, inspect the code instead of asking.
- Push back on unnecessary scope, weak contracts, and over-engineering.

## Skill Preflight

1. Read `.agent/skills/project-knowledge-base/SKILL.md`.
2. Read `.agent/skills/karpathy-guidelines/SKILL.md`.
3. Read only the domain skill for the feature area being clarified.

## Process

1. Identify the goal, user-facing behavior, constraints, and non-goals.
2. Inspect relevant code or plan files when needed.
3. Find decision branches and dependencies between decisions.
4. Ask the highest-impact unresolved question.
5. Include a recommended answer when project context supports one.
6. Wait for the user's answer before continuing.
7. When the scope is clear enough for `/plan`, write a short decision log.
8. If all major decisions are resolved and the user did not ask to stop at grilling, continue directly into `.agent/workflows/plan.md`.
9. Use the decision log as the plan input; do not ask the same resolved questions again.

## Output

Return a short decision log:

- Confirmed decisions.
- Open decisions.
- Recommended next workflow, or the generated plan path if auto-handoff to `/plan` ran.
