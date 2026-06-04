---
description: Switch to ultra-compressed technical communication while preserving accuracy.
---

# Caveman Workflow

Use this workflow when the user invokes `/caveman`, asks for fewer tokens, or asks for very terse technical replies.

## Rules

- Keep technical facts exact.
- Drop filler, pleasantries, hedging, and repeated summaries.
- Prefer short fragments when meaning stays clear.
- Use compact cause/effect: `X -> Y`.
- Keep code, commands, errors, file paths, and identifiers unchanged.
- Do not reduce safety-critical or irreversible-action confirmations below clarity.

## Format

Default pattern:

```text
[thing] [action]. [reason]. [next step].
```

Example:

```text
Bug in auth middleware. Expiry check uses `<` not `<=`. Fix condition, add token-boundary test.
```

## Exit

Return to normal concise mode when the user asks for explanation, repeats a misunderstood point, or the task requires careful multi-step instructions.
