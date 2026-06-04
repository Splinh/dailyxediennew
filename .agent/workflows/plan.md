---
description: Convert requirements into a build-compatible implementation plan.
---

# Plan Workflow

Use this workflow when the user invokes `/plan`, asks for an implementation plan, or asks to standardize an existing plan.

## Hard Rules

- Do not edit production code.
- Work at the document layer only.
- Read relevant code and local skills before writing the plan.
- Ask the user only when a requirement cannot be inferred safely from code.
- Do not proceed to `/build` without explicit user approval.

## Skill Preflight

1. Read `.agent/skills/project-knowledge-base/SKILL.md`.
2. Read `.agent/skills/karpathy-guidelines/SKILL.md` for plan discipline.
3. Read only the domain skill needed by the target module.

## Process

1. Identify the objective, scope, non-goals, and target module.
2. If invoked from `/grill-me`, use the decision log as resolved input.
3. Inspect the current implementation and contracts.
4. Correct stale assumptions in the source plan, if updating an existing plan.
5. Decompose the work into ordered vertical slices.
6. Attach a concrete `-> Verify:` command to every step.
7. Write or update one plan file under `.agent/plans/[Module]/[feature].md`.

## Required Plan Shape

Every buildable plan must use this structure:

```markdown
# [Feature Name] Implementation Plan

## Context

[2-3 lines: problem, goal, important contract.]

## Implementation Plan

- [ ] **Step 1: [Action]** [One atomic implementation change with file/path detail.] -> Verify: `[verification command]`

- [ ] **Step 2: [Action]** [One atomic implementation change with file/path detail.] -> Verify: `[verification command]`

## Files Touched

- `path/to/file.php` (modify)
- `path/to/new-file.js` (create)
```

## Step Rules

- One checklist item equals one buildable code slice.
- Steps must be ordered top-to-bottom.
- No prose-only architecture sections in build plans.
- No placeholder verification such as `manual test`.
- Use `vendor\bin\wp eval ...`, `vendor\bin\wp eval-file ...`, `php -l`, `node --check`, `pnpm build`, `composer dump-autoload -o`, or `proxy curl.exe ...` as appropriate.
- Put completed steps as `[x]` only during `/build`, after verification passes.

## Output

Report:

- Plan path.
- Key contracts verified from code.
- Any unresolved assumption.
- That implementation is waiting for approval.
