---
description: Persist durable project patterns into local skills or approved memory notes.
---

# Learn Workflow

Use this workflow when the user invokes `/learn`, asks to remember a project pattern, or asks to
update the local skill knowledge base after a non-routine decision or bug fix.

## Hard Rules

- Persist only durable patterns, architecture decisions, or recurring bug lessons.
- Skip routine implementation details.
- Keep entries concise and action-oriented.
- Update memory only when the active agent policy allows it and the user explicitly asked for it.
- Use the current skill folder structure: `.agent/skills/<skill-name>/SKILL.md`.

## Process

1. Identify the root lesson:
    - What failed or changed?
    - Why is it likely to recur?
    - What rule prevents it?
2. Choose the target:
    - Theme architecture: `.agent/skills/hd-theme/SKILL.md`
    - HDA plugin: `.agent/skills/hda-plugin/SKILL.md`
    - HDAT plugin: `.agent/skills/hdat-plugin/SKILL.md`
    - Frontend: `.agent/skills/frontend/SKILL.md`
    - PHP style: `.agent/skills/php/SKILL.md`
    - Routing/index guidance: `.agent/skills/project-knowledge-base/SKILL.md`
3. Add the smallest useful rule to the target skill.
4. If memory persistence is requested and allowed, write the required memory update through the
   active memory mechanism.
5. Verify the edited skill still has valid frontmatter and no unrelated churn.

## Output

Report:

- Target skill updated.
- New rule added.
- Memory update status, if applicable.
