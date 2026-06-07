---
id: TASK-99
title: test(visual): validate SimplicioCode sprint flow
sprint: sprint-08
owner: @team
status: todo
source_key: 99
---

# TASK-99 — test(visual): validate SimplicioCode sprint flow

## Contexto

## SimplicioCode visual E2E

Run this issue through `Simplicio-code` using its canonical `script/simplicio/flow.sh --sprint github` wrapper.

Target file: docs/simplicio-code-visual-flow.md

## Expected visual flow

- Simplicio-code maps the target repo before sprint execution.
- The wrapper imports this GitHub issue through SendSprint.
- The issue/card moves to `In Progress`, then `In Review`.
- Development runs with local `simplicio-dev-cli + simplicio-prompt + agents`.
- SendSprint captures visual evidence from the local web UI.
- A draft PR is opened or updated with test and screenshot evidence.

## Acceptance criteria

- `docs/simplicio-code-visual-flow.md` exists.
- The file contains `SimplicioCode Visual E2E`.
- Evidence includes a Playwright screenshot of `/login`.
- The PR body/comment includes SendSprint evidence.

## Validation command

```bash
grep -q "SimplicioCode Visual E2E" docs/simplicio-code-visual-flow.md
```

Origin: https://github.com/wesleysimplicio/sistema-sindico/issues/99

## Acceptance Criteria

- [ ] AC-1 — test(visual): validate SimplicioCode sprint flow is implemented and verified by a test.

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

- Sprint: `.specs/sprints/sprint-08/SPRINT.md`
- Backlog: `.specs/sprints/BACKLOG.md`
- Ticket: https://github.com/wesleysimplicio/sistema-sindico/issues/99
- Source key: `99`
- Labels: module:docs, type:chore, simplicio:e2e
