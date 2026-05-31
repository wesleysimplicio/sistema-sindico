---
id: TASK-101
title: Validate SimplicioCode desktop DEBUG visual sprint flow
sprint: sprint-09
owner: @team
status: todo
source_key: 101
---

# TASK-101 — Validate SimplicioCode desktop DEBUG visual sprint flow

## Contexto

## Context
Validate that Simplicio-code desktop can drive a visual SendSprint flow with DEBUG logging enabled end to end.

## Implementation
- Create or update `docs/simplicio-code-desktop-debug-flow.md`.
- Include the marker text `SimplicioCode Desktop DEBUG E2E`.
- Keep the change simple and auditable.

## Acceptance
- SendSprint runs from the Simplicio-code flow wrapper with `SENDSPRINT_LOG_LEVEL=DEBUG`.
- Visual evidence is captured from `/login`.
- Logs and report are emitted for review.

Origin: https://github.com/wesleysimplicio/sistema-sindico/issues/101

## Acceptance Criteria

- [ ] AC-1 — Validate SimplicioCode desktop DEBUG visual sprint flow is implemented and verified by a test.

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

- Sprint: `.specs/sprints/sprint-09/SPRINT.md`
- Backlog: `.specs/sprints/BACKLOG.md`
- Ticket: https://github.com/wesleysimplicio/sistema-sindico/issues/101
- Source key: `101`
- Labels: module:docs, type:chore, In Progress, simplicio:e2e
