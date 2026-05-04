# Sistema Síndico — Sprint Backlog (PO + Scrum Master view)

> Companion to `docs/specs/SCREENS-ANALYSIS.md`. Plans the development of the 26 modules across 7 sprints. Story points use a Fibonacci scale (1, 2, 3, 5, 8, 13). One sprint ≈ 2 weeks. Stack: PHP 8.2 + MySQL 8 backend (this repo) + a future native mobile app that consumes `/api`.
>
> Created: 2026-05-04. Owner: Wesley Simplicio. PO + Scrum Master perspective baked in: every story has User Story (As/I/So that), Acceptance Criteria (Given/When/Then), Definition of Done, and Dependencies.

---

## Cross-sprint Definition of Done

A story is Done when:

1. Code is on `main`, behind a feature module that is loadable via the existing autoloader.
2. PDO repository + controller + routes are wired and protected by the right middleware.
3. JSON envelope `{success,data,meta}` is respected for API endpoints.
4. `database/schema.sql` and a numbered `database/migrations/NNN_*.sql` are committed together.
5. PHP syntax lint passes (`find src public routes templates -name '*.php' -print0 | xargs -0 -n1 php -l`).
6. `scripts/smoke-public-site.sh` still passes against a local boot.
7. `CHANGELOG.md` has a bullet under the next semver and `VERSION` is bumped.
8. The matching GitHub issue is moved to Done with a link to the merge commit.

---

## Sprint 0 — Discovery (already finished by this analysis)

- 64 prints reviewed.
- `docs/specs/SCREENS-ANALYSIS.md` created.
- This backlog created.
- GitHub labels, milestones and issues created from this document.
- Goal: shared language between PO, dev and QA before code starts.

---

## Sprint 1 — Foundations (auth completion + onboarding + role dashboards)

**Sprint goal:** a signed-in user lands on the right dashboard for their role, in the right condo and unit.

**Capacity target:** ~30 SP.

| ID | Epic | Story (As/I/So that) | AC (G/W/T) | SP |
|---|---|---|---|---|
| S1-01 | Auth recovery | As a user, I want to recover my password by document + 6-digit code, so that I can get back in. | Given an existing user, when I POST `/api/auth/forgot-password`, then a 6-digit code is generated and "sent" (logged in dev). | 5 |
| S1-02 | Auth recovery | As a user, I want to verify the code and set a new password, so that I regain access. | Given a valid code, when I POST `/api/auth/verify-code` and `/api/auth/reset-password`, then my password hash is updated and old code is invalidated. | 3 |
| S1-03 | Memberships | As any user with multiple condos, I want a picker, so that I can choose which condo I am acting in. | Given memberships > 1, when I GET `/api/memberships`, then I get a list with role per condo. | 3 |
| S1-04 | Memberships | As a user, I want to select condo+unit, so that subsequent calls are scoped. | Given a selection, when I POST `/api/memberships/select`, then the JWT is reissued with `condominium_id`, `unit_id?`, `role`. | 5 |
| S1-05 | Profile | As a user, I want to view and edit my profile (name/phone/avatar) and change my password, so that my data is up to date. | Given I am authenticated, when I PATCH `/api/me` and `/api/me/password`, then changes persist and password_history is appended. | 5 |
| S1-06 | Dashboard | As a morador, I want a home dashboard with notices/maintenance/deliveries counters and FAB shortcuts, so that I see what matters first. | Given my role morador, when I GET `/api/dashboard`, then I see counters scoped to my unit. | 3 |
| S1-07 | Dashboard | As a síndico, I want a home dashboard with open maintenance, recent notices, recent visitors, so that I can triage quickly. | Same as above for role síndico, scoped to condo. | 3 |
| S1-08 | Dashboard | As a porteiro, I want a home dashboard with deliveries today, expected visitors, recent access, so that I run the lobby. | Same as above for role porteiro. | 3 |

Risks: JWT scope rotation needs care to not break long-lived tokens. Mitigation: 7-day TTL stays, client refresh on `select`.

---

## Sprint 2 — Unit hub (people, vehicles, contractors, porter notes)

**Sprint goal:** the unit screen is a real hub: residents, vehicles, contractors, porter notes are CRUD-complete.

**Capacity target:** ~32 SP.

| ID | Epic | Story | AC | SP |
|---|---|---|---|---|
| S2-01 | Unit overview | As a resident, I want a unit overview with counters (residents, vehicles, contractors, last visitor, last delivery), so that I understand the household at a glance. | Given my unit, when I GET `/api/condominium/{c}/units/{u}/overview`, then I get aggregated counters. | 5 |
| S2-02 | Residents | As the unit responsible, I want to add/remove residents (with optional invite to login), so that the family is registered. | Given add resident, when I POST, then row inserted; when invite checked, login_invitations row generated. | 5 |
| S2-03 | Vehicles | As any resident, I want CRUD of vehicles, so that the gate recognizes them. | Plate is unique per condo; CRUD endpoints behave as documented. | 3 |
| S2-04 | Contractors | As the unit responsible, I want to schedule a contractor with date window and access scope, so that the porter knows. | Status flow: pending → approved → expired/revoked; expired auto-set when window closes. | 5 |
| S2-05 | Porter notes | As any resident, I want to leave a note for the porter, so that the next shift sees it. | POST creates note; GET returns chronological list scoped to unit (or condo if global). | 3 |
| S2-06 | Schema | As the team, I want migrations 002–005 with the new tables and column ALTERs, so that schema matches code. | Migration runs clean on fresh DB; rerun is idempotent. | 5 |
| S2-07 | Web admin parity | As a síndico in the web admin, I want to view (read-only) the unit hub, so that I can support residents from the desk. | Web routes mirror API data, role-checked. | 6 |

---

## Sprint 3 — Visitors, invitations, login invitations

**Sprint goal:** the porter knows who is expected, by whom, when, with QR / face / list.

**Capacity target:** ~30 SP.

| ID | Epic | Story | AC | SP |
|---|---|---|---|---|
| S3-01 | Visitors | As a resident, I want to register an expected visitor with photo and arrival window, so that the porter can let them in. | POST `/api/visitors` accepts photo upload; status starts at `expected`. | 5 |
| S3-02 | Visitors | As a resident, I want a QR for my visitor, so that they can self-check at the gate. | GET `/api/visitors/{id}/qr` returns short-lived QR token; scan endpoint validates and toggles status. | 5 |
| S3-03 | Visitors | As a porter, I want check-in/check-out endpoints, so that the access log is filled. | POST check-in/out updates visitor + writes `access_logs`. | 5 |
| S3-04 | Event invitations | As a resident hosting an event, I want to create an invitation with a guest list, so that the porter can match arrivals to my list. | CRUD on `invitations` + `invitation_guests`; guest status flow. | 8 |
| S3-05 | Login invitations | As a síndico, I want to invite future users (síndico/morador/porteiro) by email/SMS with a role and unit, so that they can self-register. | POST `/api/login-invitations`, accept endpoint creates user + membership; token expires in 72h. | 5 |
| S3-06 | Audit | As a síndico, I want every visitor and login invitation logged in `audit_logs`, so that nothing is invisible. | Each mutation produces an audit row. | 2 |

---

## Sprint 4 — Communication (notices, documents, maintenance, deliveries enrichment)

**Sprint goal:** notices reach the right people, maintenance has a real conversation, documents are organized, deliveries close the loop.

**Capacity target:** ~32 SP.

| ID | Epic | Story | AC | SP |
|---|---|---|---|---|
| S4-01 | Notices | As a síndico, I want to create a notice with attachments and a recipient scope (all / block / unit / role), so that targeting is precise. | POST notice persists scope + attachments; recipients computed; reads tracked per user. | 8 |
| S4-02 | Notices | As a resident, I want to mark a notice as read and see unread count, so that I can clean my feed. | POST read writes `notice_reads`; counters update in dashboard. | 3 |
| S4-03 | Documents | As a síndico, I want folders and uploads, so that documents are findable. | `document_folders` + nested listing endpoints; mime/size stored. | 5 |
| S4-04 | Documents | As a resident, I want a signed download URL, so that I can read on phone. | GET download returns short-lived URL or stream proxy. | 3 |
| S4-05 | Maintenance | As a resident, I want to attach photos and have a comment thread on a ticket, so that the síndico has context. | Attachments + comments endpoints; status transitions logged. | 5 |
| S4-06 | Maintenance | As a síndico, I want a triage list with filters (status, priority, unit), so that I can run the queue. | GET `/api/maintenance?status=&priority=&unit_id=` ordered by created_at desc. | 3 |
| S4-07 | Deliveries | As a porter, I want to register a delivery with photo and locker label; as a resident, I want to mark withdrawn. | POST delivery, POST withdraw write rows; notifications fan out. | 5 |

---

## Sprint 5 — Real-time access (logs, cameras, gate triggers, incidents)

**Sprint goal:** the síndico/porter screen is a live operations console.

**Capacity target:** ~34 SP. (heavy infra)

| ID | Epic | Story | AC | SP |
|---|---|---|---|---|
| S5-01 | Access logs | As a síndico/porter, I want a unified access log filtered by date/unit/type, so that I can audit. | GET `/api/access-logs?from=&to=&unit_id=&type=`; pagination. | 5 |
| S5-02 | Access logs | As a síndico, I want detail with photo and reader info, so that I can investigate denials. | GET `/api/access-logs/{id}` returns full row joined to reader/visitor/user. | 3 |
| S5-03 | Cameras | As a síndico/porter, I want a list of cameras with HLS URLs, so that I can watch live. | GET `/api/cameras` returns metadata; GET `/api/cameras/{id}/stream` returns signed HLS URL (proxy assumed external). | 5 |
| S5-04 | Gate triggers | As a síndico/porter, I want to fire a gate/door from the app and have it logged, so that operations are traceable. | POST `/api/gate-triggers/{id}/fire` calls device endpoint + writes `gate_trigger_logs`; failures captured. | 8 |
| S5-05 | Incidents | As any role, I want to open an incident with type, title, body and timestamp, so that the síndico has a queue. | POST `/api/incidents` + types CRUD; status flow open→in_progress→resolved→closed. | 5 |
| S5-06 | Incidents | As a síndico, I want to triage incidents (assign, comment, close), so that the queue moves. | PATCH `/api/incidents/{id}` for status; note: comments could reuse maintenance_comments pattern. | 5 |
| S5-07 | Real-time hooks | As a porter, I want a webhook receiver `/api/webhooks/access-event`, so that the access controller hardware can push events. | POST validates HMAC, writes `access_logs`, fans push notifications. | 3 |

---

## Sprint 6 — Notifications, security, settings, contact

**Sprint goal:** the user stays informed and in control of their account.

**Capacity target:** ~28 SP.

| ID | Epic | Story | AC | SP |
|---|---|---|---|---|
| S6-01 | Notifications | As any user, I want a feed of in-app notifications, so that I do not miss events. | GET `/api/notifications`, POST read, POST read-all; pagination. | 5 |
| S6-02 | Notifications | As any user, I want granular preferences per channel × event, so that I am not spammed. | GET/PUT `/api/notification-preferences`; matrix stored as JSON. | 3 |
| S6-03 | Push | As the system, I want to register and revoke FCM device tokens, so that pushes land. | `user_devices` CRUD endpoints. | 3 |
| S6-04 | Security | As a user, I want to enable 2FA and see active sessions, so that I control my account. | PATCH `/api/settings/security`, GET/DELETE `/api/settings/sessions`. | 5 |
| S6-05 | Security | As the system, I want password rules (length, history of last 5), so that weak/reused passwords are blocked. | Validation enforced on `/api/me/password` and `/api/auth/reset-password`. | 3 |
| S6-06 | App version | As the system, I want a `/api/system/version` endpoint and a permissions copy endpoint, so that the mobile dialog screens have data. | GET returns latest version per platform + required flag; permissions endpoint returns static localized copy. | 3 |
| S6-07 | Contact | As any user, I want a "Fale conosco" form, so that I can talk to the síndico/admin. | POST `/api/contact` writes `contact_messages`; síndico inbox endpoint. | 3 |
| S6-08 | Empty/error | As any user, I want graceful empty/error states across the app, so that the UI never looks broken. | Backend returns consistent error envelope with code+message; client maps to UI. | 3 |

---

## Sprint 7 — Polish, E2E, hardening

**Sprint goal:** ship-ready quality.

**Capacity target:** ~22 SP.

| ID | Epic | Story | AC | SP |
|---|---|---|---|---|
| S7-01 | E2E | As QA, I want Playwright tests for the web admin login → dashboard → create notice flow, so that the happy path stays green. | CI job `e2e` runs Playwright headless. | 5 |
| S7-02 | E2E API | As QA, I want a Postman/Newman collection that covers `/api/auth/*`, unit hub, visitors, notices, maintenance, deliveries, so that regressions are caught. | CI job `api-e2e` runs `newman run`. | 5 |
| S7-03 | Security review | As the team, I want a security pass on input validation, rate limiting, secrets handling and SSRF on stream proxy, so that we do not ship known holes. | Reviewer signoff on PR with findings list. | 5 |
| S7-04 | Performance | As any user, I want lists to paginate and avoid N+1, so that the app scales beyond 1 condo. | Indexes added; eager-load patterns in repositories. | 3 |
| S7-05 | Docs | As the team, I want README updated with the new endpoints + ER diagram, so that onboarding is fast. | README includes diagram (`docs/diagrams/er.svg`) and endpoint matrix. | 2 |
| S7-06 | Release | As the team, I want VERSION 1.0.0 and CHANGELOG entries, so that we mark the milestone. | Git tag `v1.0.0` + GitHub release notes. | 2 |

---

## Backlog metrics

- 7 sprints (S1–S7), Sprint 0 already done.
- ~210 SP total.
- ~30 SP/sprint average → fits a 1-dev pace at 2 weeks/sprint with focused effort.
- Hard dependencies between sprints follow the modules section of `SCREENS-ANALYSIS.md`.

---

## GitHub mapping

- **Labels** (created in this sprint 0):
  - `module:auth`, `module:onboarding`, `module:dashboard`, `module:units`, `module:residents`, `module:vehicles`, `module:contractors`, `module:porter-notes`, `module:visitors`, `module:invitations`, `module:login-invitations`, `module:notices`, `module:documents`, `module:maintenance`, `module:deliveries`, `module:access-logs`, `module:cameras`, `module:gate-triggers`, `module:incidents`, `module:notifications`, `module:settings`, `module:contact`, `module:security`, `module:devops`, `module:e2e`, `module:docs`
  - `type:epic`, `type:story`, `type:bug`, `type:chore`
  - `sprint:1` … `sprint:7`
  - `priority:p0`, `priority:p1`, `priority:p2`
- **Milestones**: `Sprint 1 — Foundations`, `Sprint 2 — Unit hub`, `Sprint 3 — Visitors & invitations`, `Sprint 4 — Communication`, `Sprint 5 — Real-time access`, `Sprint 6 — Notifications & settings`, `Sprint 7 — Polish & release`.
- **Issues**: each `Sx-yy` row above becomes one issue with the labels `module:*`, `type:story`, `sprint:N`, plus a parent `type:epic` per Epic.
