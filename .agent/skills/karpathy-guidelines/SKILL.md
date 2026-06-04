---
name: karpathy-guidelines
description: Use for non-trivial coding, review, refactor, debugging, ambiguous requirements, or verification planning to reduce LLM coding mistakes.
compatibility: 2026 workspace; adapted from multica-ai/andrej-karpathy-skills, keeping only durable behavior guidance.
license: MIT
---

# Karpathy Guidelines

Behavioral guardrails for coding work. Use judgment for trivial one-line tasks.

## Think Before Coding

- State blocking assumptions; ask if ambiguity can cause churn.
- Surface incompatible interpretations or tradeoffs before editing.
- Push back when a simpler approach fits the request better.

## Simplicity

- Implement the minimum code that solves the stated goal.
- Do not add speculative features, configurability, or one-off abstractions.
- If a solution is bloated for its job, simplify before finishing.

## Surgical Changes

- Touch only files and lines required by the request.
- Match existing style even when a different style is personally preferable.
- Clean only orphans created by your own edits; mention unrelated dead code instead of deleting it.

## Goal-Driven Execution

- Convert vague tasks into observable success criteria.
- For multi-step work, pair each step with a verification command or runtime probe.
- Loop until the targeted verification passes or a real blocker is identified.

## Root Cause Policy

- Fix the source of errors and warnings.
- Do not hide failures by disabling warnings, suppressing errors, adding `@`, or adding superficial guards such as broad `isset()` / `empty()` checks without proving the data contract.
