---
description: Execute an approved implementation plan one verified step at a time.
---

# Build Workflow

Use this workflow when the user invokes `/build`, points to an approved plan, or explicitly asks to implement an existing `## Implementation Plan`.

## Hard Rules

- Execute only an approved plan document.
- Do not invent missing plan steps while coding. If the plan is incomplete, update the plan first and ask for approval.
- Touch only files required by the active checklist item.
- Mark a checklist item complete only after its verification passes.

## Preflight

1. Open the referenced plan file.
2. Confirm it contains `## Implementation Plan`.
3. Confirm it has unchecked `- [ ]` items.
4. Confirm each active step has a concrete `-> Verify:` command.
5. If the document has no checklist, stop and tell the user to run `/plan` first.
6. If all items are checked, stop and report that the plan is already complete.

## Skill Preflight

1. Read `.agent/skills/project-knowledge-base/SKILL.md`.
2. Read `.agent/skills/karpathy-guidelines/SKILL.md` for non-trivial implementation discipline.
3. Read only the domain skill needed by the active checklist item.

## Process

1. Select the first unchecked item.
2. Read the files needed for that step.
3. Implement the smallest code change that satisfies the step.
4. Run the step's `-> Verify:` command.
5. If verification fails, diagnose and fix within the step scope, then rerun verification.
6. Change the item from `- [ ]` to `- [x]`.
7. Continue to the next unchecked item unless the user asked for only one step.

## Verification

- PHP touched: run targeted `php -l`, `vendor\bin\wp eval ...`, or plugin/theme autoload checks.
- Frontend touched: run `node --check` for edited JS and `pnpm build` when build output may change.
- REST/HTTP touched: run `vendor\bin\wp eval ...` or `proxy curl.exe ...`.
- Final hygiene: run `git diff --check`.

## Output

Report:

- Completed checklist items.
- Verification commands and pass/fail result.
- Files intentionally changed.
- Any blocked item with exact reason.
