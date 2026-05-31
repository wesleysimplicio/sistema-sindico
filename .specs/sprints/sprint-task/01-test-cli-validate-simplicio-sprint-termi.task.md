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

## Simplicio Sprint terminal E2E

Run this issue through `sendsprint run github` using the local Simplicio dev-cli.

Target file: docs/simplicio-sprint-terminal-flow.md

## Expected flow

- Import this GitHub issue into the sprint format.
- Move the card/issue to `In Progress`.
- Plan the work with the mapper/spec artifacts.
- Execute development with local `simplicio-dev-cli`.
- Use `dev-cli + simplicio-prompt + agents` as the default execution profile.
- Move the card/issue to `In Review`.
- Push the branch and attach evidence to a draft PR.

## Acceptance criteria

- `docs/simplicio-sprint-terminal-flow.md` exists.
- The file contains `Simplicio Sprint CLI E2E - terminal`.
- SendSprint evidence includes the grep validation command.
- The PR remains draft and includes the generated evidence comment.

## Validation command

```bash
grep -q "Simplicio Sprint CLI E2E - terminal" docs/simplicio-sprint-terminal-flow.md
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
