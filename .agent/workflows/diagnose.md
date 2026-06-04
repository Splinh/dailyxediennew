---
description: Diagnose bugs and regressions with a reproducible feedback loop before patching.
---

# Diagnose Workflow

Use this workflow when the user invokes `/diagnose`, reports a bug, says something is broken, shows an error, or describes a non-obvious regression.

## Hard Rules

- Build a fast pass/fail loop before editing code.
- Reproduce the reported issue before choosing a fix.
- Change code only after the root cause is supported by evidence.
- Remove temporary debug logs, probes, and harnesses before finishing.

## Skill Preflight

1. Read `.agent/skills/project-knowledge-base/SKILL.md`.
2. Read `.agent/skills/karpathy-guidelines/SKILL.md`.
3. Read only the domain skill for the affected area.

## Process

1. Scope the symptom, affected path, expected behavior, and actual behavior.
2. Create a deterministic feedback loop:
   - `vendor\bin\wp eval ...`
   - `vendor\bin\wp eval-file ...`
   - `proxy curl.exe ...`
   - targeted `node --check`, `php -l`, or a disposable local harness
3. Run the loop and confirm it reproduces the exact issue.
4. List 3 to 5 ranked, falsifiable hypotheses when the cause is not obvious.
5. Instrument only the boundary needed to test the top hypothesis.
6. Apply the smallest root-cause fix.
7. Rerun the minimal loop and the broader affected workflow.
8. Clean up instrumentation and disposable harnesses.

## Output

Report:

- Reproduction command.
- Root cause.
- Fix applied.
- Regression verification.
- Residual risk or skipped checks, if any.
