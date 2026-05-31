---
id: TASK-97
title: test(cli): validate Simplicio Sprint terminal flow
sprint: sprint-task
owner: @team
status: todo
source_key: 97
---

# TASK-97 — test(cli): validate Simplicio Sprint terminal flow

## Contexto

## Simplicio terminal flow test

Purpose: exercise `simplicio-sprint` end-to-end from a GitHub issue into a branch, local Simplicio dev-cli execution, evidence collection, and draft PR.

### Scope
- Target repo: `wesleysimplicio/sistema-sindico`
- Target file: `GOAL_RESULT.md`
- Add a short dated evidence section titled exactly `Simplicio Sprint CLI E2E - terminal`.
- Mention that the terminal flow imported a GitHub issue, moved the card to `In Progress`, ran local Simplicio dev-cli, collected evidence, and opened a draft PR.
- Do not change application runtime behavior.

### Acceptance criteria
- `GOAL_RESULT.md` contains `Simplicio Sprint CLI E2E - terminal`.
- Existing PHPUnit unit tests still pass when available.
- The PR body includes SendSprint evidence.

### Suggested validation
```bash
grep -q "Simplicio Sprint CLI E2E - terminal" GOAL_RESULT.md
composer test:unit
```

Origin: https://github.com/wesleysimplicio/sistema-sindico/issues/97

## Acceptance Criteria

- [ ] AC-1 — test(cli): validate Simplicio Sprint terminal flow is implemented and verified by a test.

## Out of scope

- Only what this card requires; anything tangential becomes a new backlog item.

## Test plan

### Unit

- [ ] Cover the new/changed behaviour with valid and invalid inputs.
- [ ] Keep existing tests green; mock external dependencies.

### Integration

- [ ] Exercise the happy path plus at least one error path end to end.

### End-to-end

- [ ] Capture evidence (test run + screenshot when UI is touched) for the PR.

## Definition of Done

- [ ] All Acceptance Criteria met and verified.
- [ ] Tests green locally and in CI.
- [ ] Draft PR opened linking this task and the source ticket.
- [ ] Status updated in BACKLOG.md and SPRINT.md.

## Links

- Sprint: `.specs/sprints/sprint-task/SPRINT.md`
- Backlog: `.specs/sprints/BACKLOG.md`
- Ticket: https://github.com/wesleysimplicio/sistema-sindico/issues/97
- Source key: `97`
- Labels: module:docs, type:chore, In Progress, In Review, simplicio:e2e
