# Changelog

## 1.1.6 - 2026-05-31
- CI gates: kept the PHP 8.1 syntax lint matrix while skipping Composer dev-tool
  installation on PHP 8.1, because the locked PHPUnit 11 toolchain requires
  PHP >= 8.2.
- DoD workflow: scoped Node coverage and Playwright evidence gates to PRs that
  touch UI/E2E surfaces, so documentation and SendSprint evidence-only PRs can
  pass the relevant checks.
- DoD workflow: replaced the transient `npx commitlint` dependency with a
  self-contained Conventional Commits title regex.

## 1.1.5 - 2026-05-31
- Simplicio E2E validation: created terminal and visual GitHub issue flows for
  `simplicio-sprint` and `Simplicio-code`, both ending in draft PRs with
  evidence artifacts.
- Playwright E2E package refreshed to the latest compatible `@playwright/test`
  release and lockfile added for reproducible browser smoke runs.
- Mapper/Sprint artifacts refreshed so LLM-assisted runs can load the current
  project context and sprint retrospectives from versioned files.

## 1.1.4 - 2026-05-18
- Sprint 8 hardening — auth recovery delivery: added a transactional `Mailer` abstraction with `resend` and local `log` drivers, wired `POST /api/auth/forgot-password` to real email delivery in production, and exposed `data.debug.code` only for local/log smoke without ever logging the plaintext code.
- unit testing foundation: added `composer.json`, `phpunit.xml.dist`, and a first PHPUnit suite covering `Jwt`, `Totp`, `PasswordPolicy`, `StoragePath`, and `RateLimit`.
- CI and architecture: accepted ADRs `ADR-004-email-provider-auth-recovery.md` and `ADR-005-phpunit-core-unit-tests.md`, added `unit-phpunit` plus Newman auth-recovery smoke to CI, and marked Sprint 8 backlog items `#90`, `#92`, and epic `#95` as done.

## 1.1.3 - 2026-05-18
- Sprint 8 hardening — scalable rate limiting: introduced `RateLimitStore` with `MySQLRateLimitStore` (default) and `RedisRateLimitStore` selected via `RATE_LIMIT_DRIVER`, keeping the same `X-RateLimit-*` and `429` contract for clients while opening an optional Redis path for non-HostGator environments.
- runtime + validation: `docker-compose.yml` now includes an optional Redis profile, `.env.example` documents `RATE_LIMIT_DRIVER` / `REDIS_URL`, and `scripts/validate-rate-limit-driver.sh` plus `tests/api/rate-limit.postman_collection.json` validate the 429 flow through Newman for both drivers.
- architecture + planning: accepted `ADR-003-rate-limit-driver.md`, updated Sprint 8 backlog/status bookkeeping for `#94`, and documented why HostGator keeps `mysql` as the default production driver in v1.x.

## 1.1.2 - 2026-05-18
- Sprint 8 hardening — Docker onboarding: added an official `Dockerfile` multi-stage plus `docker-compose.yml` with `app` (`php:8.2-apache`) and `db` (`mysql:8.0`) services, persistent DB volume, Apache docroot at `public/`, and automatic `schema.sql` + `database/migrations/*.sql` + `seed.sql` import on first boot so the containerized DB matches the current app schema.
- CI runtime validation: `.github/workflows/ci.yml` now includes a `docker-smoke` job that builds the stack, waits for `GET /api/health`, and verifies seeded admin login inside the Dockerized runtime.
- docs and architecture: documented "Setup via Docker" in the repo instructions and accepted ADR `ADR-002-docker-dev-runtime.md` to keep Docker as the official local/onboarding runtime while HostGator remains the production path for v1.x.

## 1.1.1 - 2026-05-18
- Sprint 8 hardening — adoption metrics: new admin endpoint `GET /api/admin/metrics/adoption` returns `active_users_30d`, `mau_by_role`, and visitor registration p50/p95 based on `audit_logs` (`visitor.created` -> `visitor.qr_issued`) for the current condominium.
- dashboard visibility: síndico/admin dashboard now surfaces the same adoption metrics in cards, and the API dashboard payload adds `metrics.adoption` for the síndico view.
- session activity throttling: `ApiTokenRepository::isActive()` now updates `api_tokens.last_used_at` at most once per minute per token, reducing write amplification while preserving 30-day adoption tracking.
- regression coverage: Postman/Newman collection gained a dedicated `S8 — Adoption metrics` flow that logs in, creates a visitor fixture, issues the QR, and validates the new metrics envelope end-to-end.

## 1.1.0 - 2026-05-07
- Adopted `agentic-starter` scaffold: `.specs/{product,architecture,workflow,sprints}/`, `.skills/`, `.claude/{settings.json,hooks/}`, `.codex/config.toml`, `.github/{workflows/dod.yml,PULL_REQUEST_TEMPLATE.md,ISSUE_TEMPLATE/,copilot-instructions.md}`, `playwright.config.ts`, `presentation/`. Existing `AGENTS.md`/`CLAUDE.md` preserved.
- `.specs/product/{VISION,DOMAIN,PERSONAS}.md` mapped to condominium, user, unit, visitor+invitation, payment.
- `.specs/architecture/{DESIGN,PATTERNS}.md` aligned with PHP/MySQL stack.
- `.specs/sprints/BACKLOG.md` from real TODOs.
- Bump VERSION 1.0.0 -> 1.1.0 (minor: structure added).

## 1.0.0 - 2026-05-04
- Sprint 7 — Polish & release: Playwright E2E scaffold, Newman API regression, full security pass, performance pass, README endpoint matrix + ER diagram, v1.0.0 cut.
- security — cross-tenant guard: `PaymentController::markPaid` now resolves the row via `findInCondo` and `PaymentRepository::markPaid` requires `condominium_id` in the UPDATE itself; returns 404 instead of leaking existence.
- security — SSRF: `GateTriggerController::callDevice` resolves hostname via `gethostbyname`, rejects private/reserved IPs through `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`, pins DNS via `CURLOPT_RESOLVE`, restricts protocols to HTTP/HTTPS, disables redirects.
- security — rate limiting: new `Middleware\RateLimit::enforce(bucket, max, windowSec, key)` with sliding-window counter in new `rate_limits` table (idempotent migration `database/migrations/011_rate_limits.sql`); login `auth_login` 10/15min + dummy `password_verify` for missing user (timing leak), 2FA verify `auth_2fa` 5/15min, forgot-password `auth_forgot` 3/hour, verify-code `auth_verify_code` 5/15min, webhook `webhook_access` 60/min; emits `X-RateLimit-Limit/Remaining/Reset` headers and `429 + Retry-After` when exhausted.
- security — JWT guard: `Jwt` rejects secrets shorter than 32 bytes (`MIN_SECRET_BYTES`), throws on encode and returns null on decode; recovery branch in `AuthRecoveryController` differentiates email vs document via `FILTER_VALIDATE_EMAIL` instead of cross-column LIKE.
- security — path traversal lockdown: new `App\Core\StoragePath` with `isSafeRelative` (rejects NUL, absolute, schemes, drive letters, backslash, traversal segments; per-segment regex `/^[A-Za-z0-9._\- ]+$/`) and `resolve` (`realpath` boundary check against `storage/uploads/`); wired into `DocumentController::store`/`download`, `NoticeController::addAttachment`, `MaintenanceController::addAttachment`.
- performance — composite indexes (idempotent migration `database/migrations/012_perf_indexes.sql`): `idx_notices_condo_scope`, `idx_notices_condo_pinned_pub`, `idx_nread_user_notice`, `idx_pay_condo_due`, `idx_del_condo_recv`, `idx_vis_condo_expected`, `idx_del_unit_recv`, `idx_vis_unit_created`, `idx_users_condo_role`.
- performance — bulk fan-out: new `NotificationRepository::pushBulk` issues a single multi-row INSERT for N recipients; `ContactController::store` switched to it for sindico fan-out.
- performance — `NoticeRepository::listForUser` replaces correlated `(SELECT COUNT(*) FROM notice_reads ...)` with `LEFT JOIN notice_reads r ON r.notice_id = n.id AND r.user_id = :uid` and exposes `(r.notice_id IS NOT NULL) AS is_read`.
- testing — Playwright happy-path scaffold under `tests/e2e/` (`playwright.config.js` desktop-chromium + mobile-chromium projects, `specs/` smoke flows); Newman/Postman v2.1.0 collection at `tests/api/sistema-sindico.postman_collection.json` covering health, login, profile, dashboard, notices, payments, contact.
- docs — `README.md` rewritten with full endpoint matrix grouped by domain (auth, condominium, notices, maintenance, payments, visitors, deliveries, bookings, documents, messages, access control, notifications, settings, contact), Security posture, Performance posture, and Mermaid ER diagram of core tables.
- VERSION bumped to 1.0.0.

## 0.10.0 - 2026-05-04
- Sprint 6 — Notifications, 2FA, sessions, password policy, system endpoints, contact inbox (issues `#54` `#55` `#56` `#57` `#58` `#59` `#60` `#61` `#62` `#63`)
- notifications feed: `GET /api/notifications` paginated per-user feed; `GET /api/notifications/unread-count`; `POST /api/notifications/{id}/read` marks single read with tenant scoping; `POST /api/notifications/read-all` bulk-acks; new `notifications` table with `user_id`, `condominium_id`, `type`, `title`, `body`, `entity_type`, `entity_id`, `read_at`, `created_at`
- notification preferences matrix: `GET /api/notification-preferences` returns channel × event matrix (`push|email|sms` × `notice|maintenance|delivery|visitor|incident|contact_reply|access`) with sensible defaults; `PUT /api/notification-preferences` upserts using composite key `pref_key = "<channel>:<event>"`
- FCM device tokens: `GET /api/devices` lists active devices for current user; `POST /api/devices` upserts by `token` (migrates token between users on reuse), captures `platform` (`ios|android|web`), `app_version`, `last_seen_at`; `DELETE /api/devices/{id}` revokes one device
- 2FA TOTP (RFC 6238): new `src/Core/Totp.php` (HMAC-SHA1, 6 digits, 30s period, ±1 window tolerance, base32); `users.totp_secret VARCHAR(64)` + `twofa_enabled TINYINT(1)`; `GET /api/settings/security` status; `POST /api/settings/security/2fa/setup` generates secret + `otpauth://` URL; `POST /api/settings/security/2fa/enable` verifies code and flips `twofa_enabled=1`; `POST /api/settings/security/2fa/disable` requires current code when enabled; login challenge returns `twofa_required:true` (HTTP 200) when secret enabled and `code` missing, `twofa_invalid` (401) on bad code
- active sessions: `api_tokens` repurposed as session store with new columns `ip`, `user_agent`, `revoked_at` and `UNIQUE(token_hash)` where `token_hash = sha256(jti)`; JWT now carries `jti` claim (16 random bytes hex); `ApiAuth` middleware checks `ApiTokenRepository::isActive($jti)` rejecting revoked tokens with 401; `Auth::setJti/jti()` request-scoped for current-session marker; `GET /api/settings/sessions` lists devices with `current=true` via `hash_equals` against the current jti; `DELETE /api/settings/sessions/{id}` revokes one; logout calls `revokeByJti`
- password policy + history: new `src/Core/PasswordPolicy.php` enforces min 8 chars + lowercase + uppercase + digit; `Response::error` returns `weak_password` code with `violations` array and policy descriptor on 422; new `password_history` table (idempotent migration `database/migrations/010_password_history.sql`) with last-5-hash check on every change via `PasswordHistoryRepository::matchesAnyRecent`; `ProfileController::changePassword` and `AuthRecoveryController::resetPassword` both wired
- system endpoints (public): `GET /api/system/version` reads `VERSION` file, accepts `?platform=ios|android|web` + `?current=` and returns `update_required` via `version_compare` against per-platform minimums; `GET /api/system/permissions` returns pt-BR copy for 6 permission descriptors (notifications, camera, photos, location, biometrics, contacts) plus `policy_url`/`support_url` for client onboarding screens
- contact form + síndico inbox: `POST /api/contact` (any auth user) validates `subject` ≤200, `body` ≤5000, `email` via `FILTER_VALIDATE_EMAIL`, captures `ip`, fans out push notifications to all síndicos of the condominium; `GET /api/contact-messages` (admin/síndico) paginated with `?status=new|read|replied` filter and `unread_count` in meta; `GET /api/contact-messages/{id}` auto-marks read; `PATCH /api/contact-messages/{id}` accepts `action=reply|mark_read`, on reply pushes `contact_reply` notification back to original sender
- error envelope: `Response::error($msg, $status, $details, $code)` adds explicit `code` field for client-side error mapping (`weak_password`, `twofa_required`, `twofa_invalid`, etc.)
- new tables (idempotent migrations `009_sprint_6.sql` + `010_password_history.sql`): `notifications`, `notification_preferences`, `user_devices`, `contact_messages`, `password_history`; new columns on `users` (`totp_secret`, `twofa_enabled`) and `api_tokens` (`ip`, `user_agent`, `revoked_at` + `UNIQUE token_hash`)
- new core: `src/Core/Totp.php`, `src/Core/PasswordPolicy.php`; new repositories: `NotificationRepository`, `NotificationPreferenceRepository`, `UserDeviceRepository`, `ApiTokenRepository`, `ContactMessageRepository`, `PasswordHistoryRepository`; new controllers: `NotificationController`, `NotificationPreferenceController`, `UserDeviceController`, `SecurityController`, `SystemController`, `ContactController`
- 17 new authenticated API routes + 2 public system routes registered
- VERSION bumped to 0.10.0

## 0.9.0 - 2026-05-04
- Sprint 5 — Access control, cameras, gate triggers, incidents (issues `#43` `#44` `#45` `#46` `#47` `#48` `#49` `#50` `#51` `#52` `#53`)
- access logs: `GET /api/access-logs` accepts `from`, `to`, `unit_id`, `direction` (`in|out`), `result` (`granted|denied`), `type` (`visitor|resident`), `page`, `limit` filters with date validation via `strtotime`; `GET /api/access-logs/{id}` joins visitors/users/units; `AccessLogRepository::listWithFilters` paginates server-side via OFFSET/LIMIT
- cameras: `GET /api/cameras` lists condominium-scoped devices; `GET /api/cameras/{id}` strips `rtsp_url` from response (defense in depth); `GET /api/cameras/{id}/stream` validates `enabled=1` + `hls_path`, mints HMAC-sha256 base64url token namespaced `cam|<id>|<exp>` (TTL 600s, signed with `JWT_SECRET`) and returns proxy-friendly `/streams/<hls_path>?token=` URL — token namespace prevents cross-resource replay against document URLs
- gate triggers: `GET /api/gate-triggers` excludes `auth_token` at the SELECT layer AND via `unset()` in the controller; `POST /api/gate-triggers/{id}/fire` (admin/sindico/porteiro) calls device endpoint via cURL POST `{"action":"open"}` with `Authorization: Bearer <auth_token>` header, `CURLOPT_SSL_VERIFYPEER=true`, `CURLOPT_FOLLOWLOCATION=false`, capped timeouts 500..30000ms, validates `^https?://` regex on `endpoint_url`; success/failure logged in `gate_trigger_logs` (http_status, duration_ms, error_message) and `audit_logs`; returns 502 on device failure; `GET /api/gate-triggers/{id}/logs` paginated
- incidents: `GET /api/incidents` supports `status` (`open|in_progress|resolved|closed`) and `type_id` filters; `GET /api/incidents/{id}` attaches comments timeline; `POST /api/incidents` validates `incident_type_id` belongs to the condominium and `occurred_at` format; `PATCH /api/incidents/{id}` (admin/sindico/porteiro) writes a system comment `[status] from -> to | optional note` so the timeline shows full history + audit log entry; `GET/POST /api/incidents/{id}/comments`; `GET /api/incident-types`, `POST /api/incident-types` (admin/sindico)
- access webhook (public, HMAC-validated): `POST /api/webhooks/access-event` registered OUTSIDE `ApiAuth`; rejects empty/`change-me` `ACCESS_WEBHOOK_SECRET`; max 16384 byte body via `php://input`; requires `X-Signature: sha256=<hex>` + `X-Timestamp` headers; `abs(time() - ts) > 300` rejected (replay window); signature verified with `hash_equals` against `hash_hmac('sha256', "$ts.$body", $secret)`; validates `condominium_id`, `direction` enum, `result` enum; persists via `AccessLogRepository::record(...)` and returns 201
- new tables (idempotent migration `database/migrations/007_sprint_5.sql`): `cameras`, `gate_triggers`, `gate_trigger_logs`, `incident_types`, `incidents`, `incident_comments` — all scoped by `condominium_id` with FK CASCADE on condo, SET NULL on user/unit
- new repositories: `CameraRepository`, `GateTriggerRepository` (excludes `auth_token` from list), `GateTriggerLogRepository::record` validates `result` enum, `IncidentRepository`, `IncidentTypeRepository`, `IncidentCommentRepository`; `AccessLogRepository` extended with `listWithFilters` and `findInCondo`
- 16 new authenticated API routes + 1 public webhook route registered
- VERSION bumped to 0.9.0

## 0.8.0 - 2026-05-04
- Sprint 4 — Notices, documents, maintenance, deliveries (issues `#32` `#33` `#34` `#35` `#36` `#37` `#38` `#39` `#40` `#41` `#42`)
- notices: scope ENUM(`all|block|unit|role`) with `scope_block`/`scope_unit_id`/`scope_role` columns resolved server-side against the requesting user's block/unit_id/role on every `GET /api/notices`; `GET /api/notices/{id}` auto-marks read; `POST /api/notices` validates scope target before insert; `POST /api/notices/{id}/attachments` (admin/sindico) appends a row in `notice_attachments`; `POST /api/notices/{id}/read` writes to `notice_reads` via `INSERT IGNORE` (UNIQUE `notice_id+user_id`); `GET /api/notices/unread-count` returns the personal unread total
- documents: `documents.folder_id` BIGINT links to new `document_folders` (parent_id self-FK, condominium-scoped); `GET /api/documents?folder_id=` lists by folder (NULL handled), `POST /api/documents` validates folder ownership, `GET /api/documents/{id}/signed-url` returns a base64url HMAC-sha256 token (TTL 600s, signed with `JWT_SECRET`), `GET /api/documents/{id}/download?token=` validates the token + tenant before streaming the file confined to the project root; full folders CRUD `GET/POST /api/document-folders`, `GET/DELETE /api/document-folders/{id}`
- maintenance: `GET /api/maintenance` accepts `status`, `priority`, `unit_id` filters; new `GET /api/maintenance/{id}` returns the request with attachments + threaded comments; `POST /api/maintenance/{id}/attachments` (owner or admin/sindico); `GET/POST /api/maintenance/{id}/comments`; `PATCH /api/maintenance/{id}` now records the `from→to` transition as a comment line and audit log entry
- deliveries: `deliveries` gains `locker_code`, `received_by_id`, `withdrawn_user_id` columns; `POST /api/deliveries` (admin/sindico/porteiro) auto-fills `received_by_id` from the authenticated porter, optional `locker_code`; `PATCH /api/deliveries/{id}/withdraw` resolves `withdrawn_user_id` from the resident or accepts an explicit value, returns 409 if already withdrawn; new `GET /api/deliveries/{id}` show endpoint; index supports `unit_id` filter
- new tables (idempotent migration `database/migrations/006_sprint_4.sql`): `document_folders`, `notice_attachments`, `notice_reads` (UNIQUE notice+user), `maintenance_attachments`, `maintenance_comments`; new columns on `notices`/`documents`/`deliveries` via INFORMATION_SCHEMA guards
- new repositories: `DocumentFolderRepository`, `MaintenanceAttachmentRepository`, `MaintenanceCommentRepository`; `NoticeRepository` rewritten with `listForUser/listAdmin/findInCondo/findWithAttachments/addAttachment/markRead/unreadCountForUser` (legacy `listByCondominium` kept as alias); `MaintenanceRepository` extended with `priority`/`unit_id` filters and `findInCondo`; `DocumentRepository` extended with `listInFolder`/`findInCondo`; `DeliveryRepository` extended with `findInCondo` and `markWithdrawn` accepting an optional `withdrawn_user_id`
- audit logs wired on every Sprint-4 mutation: `notice.created/attachment_added/read`, `document.uploaded/downloaded/folder_created/folder_deleted`, `maintenance.created/status_changed/attachment_added/comment_added`, `delivery.received/withdrawn`
- 15 new API routes registered inside the ApiAuth-protected group
- VERSION bumped to 0.8.0

## 0.7.0 - 2026-05-04
- Sprint 3 — Visitors and invitations delivered (issues `#23` `#24` `#25` `#26` `#27` `#28` `#29` `#30` `#31`)
- visitors expansion: `POST /api/visitors` now stores `photo_url` (https-only validation), generates `qr_token` + `qr_expires_at` (10 min TTL), strict `Y-m-d H:i:s` `expected_at` validation; new `POST /api/visitors/{id}/qr` rotates the token (host or porter/sindico/admin); new `POST /api/visitors/{id}/check-in` and `/check-out` enforce porter/sindico/admin role and write to both `access_logs` (operational) and `audit_logs` (compliance); `GET /api/visitors/history` returns finalized rows (saiu/expirado/negado) joined with units; `GET /api/visitors/qr/{token}` now validates the QR is non-expired and condominium-scoped via `findValidByQr`
- invitations CRUD `GET/POST/GET{id}/PATCH/DELETE /api/invitations`: title/unit_id/starts_at required, ends_at >= starts_at, status enum `draft|active|done|cancelled`, host-or-admin/sindico-only on update/delete, moradores see only their own (or `?mine=1`); audit on every mutation
- invitation guests nested CRUD `GET/POST /api/invitations/{id}/guests`, `PATCH /api/invitations/{id}/guests/{gid}`, `DELETE .../{gid}`: status flow `expected → arrived → no_show`, `arrived_at` auto-set on arrival, parent invitation tenant check on every call
- login invitations: `POST /api/login-invitations` (admin/sindico) issues a 32-byte hex token (stored as SHA-256, returned only once), 72h TTL, role enum `sindico|morador|porteiro`, optional `unit_id` validated against condominium; `GET /api/login-invitations` lists with optional `?accepted=0|1` filter; `DELETE /api/login-invitations/{id}` only removes pending invites; public `POST /api/auth/invitations/{token}/accept` creates the user (bcrypt password >=8 chars), creates the membership, marks the invite accepted
- new tables: `invitations`, `invitation_guests`, `access_logs`; `visitors` gains `photo_url` + `qr_expires_at`; `audit_logs` gains `condominium_id` (idempotent migration `database/migrations/005_sprint_3.sql` via INFORMATION_SCHEMA guards)
- new repositories: `AuditLogRepository::record(...)` writes JSON-encoded payloads (`JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`), `AccessLogRepository::record(...)` validates direction/result enums, `InvitationRepository`, `InvitationGuestRepository`; `VisitorRepository` gains `findValidByQr/refreshQr/listHistory`; `LoginInvitationRepository` gains `listByCondominium/findInCondo/deleteIfPending`
- 16 new API routes registered (3 invitations, 4 invitation guests, 4 visitors, 3 login invitations + 1 public `accept` outside `ApiAuth`)
- VERSION bumped to 0.7.0

## 0.6.0 - 2026-05-04
- Sprint 2 — Unit hub delivered (issues `#12` `#13` `#14` `#15` `#16` `#17` `#18` `#19` `#20` `#21` `#22`)
- new aggregator endpoint `GET /api/condominium/{c}/units/{u}/overview` returning the unit, residents, vehicles, contractors, last visitor, last delivery and the latest porter notes; visitor and delivery lookups now filtered server-side via `findLatestForUnit` instead of paging the whole condo list
- residents CRUD `GET/POST/DELETE /api/condominium/{c}/units/{u}/residents` with optional `invite_login=true` flag generating a one-time `login_invitations` row whose token is stored as a SHA-256 hash and only revealed in the create response
- vehicles CRUD `GET/POST/PATCH/DELETE /api/condominium/{c}/units/{u}/vehicles`; plate uniqueness enforced per condominium via `(condominium_id, plate)` UNIQUE; duplicate POST returns 409
- contractors CRUD with status flow `pending → approved → expired/revoked`, automatic expiry once `access_ends_at < CURDATE()`, strict `Y-m-d` date validation, end >= start, ≤ 365 day window; only admin/sindico can call `PATCH .../contractors/{id}/status`
- porter notes endpoints `GET/POST /api/condominium/{c}/porter-notes`; optional `unit_id` is validated to belong to the condominium before insert
- new tables: `residents`, `vehicles`, `contractors`, `porter_notes`, `login_invitations` (migration `database/migrations/004_sprint_2.sql`, idempotent INFORMATION_SCHEMA guards)
- `LoginInvitationRepository::findByToken` and `markAccepted` enforce single-use and `expires_at > NOW()` so future Sprint 3 acceptance flow lands on a hardened base
- `BaseRepository` now validates every column name passed to `all/update/count` against `^[a-zA-Z_][a-zA-Z0-9_]*$` to make the API safer for future callers
- web admin: read-only unit hub at `/unidades/{id}` (`templates/modules/unit-hub.php`); admin bypass + condominium-id match required, otherwise 403; output escaped with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`
- 15 new API routes registered inside the ApiAuth-protected group in `routes/api.php`
- VERSION bumped to 0.6.0

## 0.5.2 - 2026-05-04
- new `.github/workflows/resolve-conflicts.yml`: triggered by `resolve-conflicts` label or `/resolve-conflicts` PR comment; uses `pull_request_target` + `GITHUB_TOKEN` (no extra secrets) to merge the PR base branch into the head; on clean merge pushes the merge commit and posts a success comment; on conflict aborts the merge, lists conflicting files via `git diff --diff-filter=U`, posts manual resolution steps, and fails the run; blocks fork PRs without `maintainerCanModify`
- new `.github/copilot/agents/conflict-resolver.yml` descriptive fallback documenting the agent triggers, capabilities, and behavior for Copilot Extensions/Agents tooling
- README: new "Automation: PR conflict resolver" section with invocation steps and outcomes
- VERSION bumped to 0.5.2

## 0.5.1 - 2026-05-04
- new GitHub Actions workflow `.github/workflows/ci.yml`: PHP 8.1/8.2/8.3 lint matrix, optional composer install + audit, optional PHPStan/PHPCS via reviewdog, optional npm lint/build, HostGator release artifact build with manifest upload
- new `.github/workflows/code-review.yml`: AI review job calls Claude (`claude-sonnet-4-6`) on every PR diff (200KB cap), posts pt-BR comment with severity buckets; `static-review` job runs `php -l` + PHPStan + PHPCS through reviewdog as inline PR comments; `security-scan` job runs gitleaks for secret detection
- new `.github/CODEOWNERS` declaring `@wesleysimplicio` as required reviewer per directory
- new `.gitleaks.toml` allowlisting build artifacts, env examples and docs from secret scanning
- branch protection on `main` enforced via `gh api`: requires PR, ≥1 approval, CODEOWNERS review, dismiss stale reviews, passing `php-lint` + `static-review` + `claude-review` checks, conversation resolution
- VERSION bumped to 0.5.1

## 0.5.0 - 2026-05-04
- Sprint 1 — Foundations delivered (issues `#2` `#3` `#5` `#6` `#7` `#9` `#10` `#11`)
- password recovery flow: `POST /api/auth/forgot-password`, `/api/auth/verify-code`, `/api/auth/reset-password` — 6-digit code stored hashed, 15-min TTL, lockout after 5 failed verifications, `password_history` keeps last 5 hashes and blocks reuse, equal-timing path on user-not-found to prevent enumeration
- memberships: new `memberships` table; `GET /api/memberships` lists scopes for the authenticated user with synthesized fallback for legacy single-condominium users; `POST /api/memberships/select` re-issues the JWT with `cid`/`role` matching the chosen membership
- `ApiAuth` middleware now merges JWT `cid`/`role`/`uid` claims into the user context, so membership scope swap is honored by every downstream handler
- profile self-service: `PATCH /api/me` (name/phone/avatar_url/locale, with `https://` URL validation), `PATCH /api/me/password` (requires current password, blocks reuse of last 5)
- role-based dashboard `GET /api/dashboard` switching between `morador`, `sindico`, `porteiro` payloads, every query scoped by `condominium_id`
- security hardening: boot-time guard requires `JWT_SECRET` length >= 32 and aborts with `RuntimeException` otherwise; removed `'change-me-in-prod'` fallback from middleware/controllers; plaintext OTP no longer written to error log
- schema additions: `memberships`, `password_resets` (with `attempt_count`), `password_history`, plus `users.locale` and `users.password_changed_at`; idempotent migrations under `database/migrations/` using `INFORMATION_SCHEMA`-guarded `ADD COLUMN`
- VERSION bumped to 0.5.0

## 0.4.0 - 2026-05-04
- new product spec `docs/specs/SCREENS-ANALYSIS.md` mapping all 64 mobile prints to UI components, REST surface, ~20 new tables, column ALTERs on 7 existing tables, permissions matrix, integrations and shared UI patterns
- new sprint plan `docs/specs/SPRINT-BACKLOG.md` with 7 sprints (S1 Foundations → S7 Polish/v1.0.0), Definition of Done, story points (Fibonacci), risks and dependencies
- GitHub: 40 labels created (`module:*`, `type:*`, `sprint:*`, `priority:*`), 7 milestones (Sprint 1 through Sprint 7), 70 issues opened (`#1`–`#70`) covering all epics and user stories from the backlog
- VERSION bumped to 0.4.0

## 0.3.1 - 2026-05-04
- workflow now skips the FTP upload step when `FTP_HOST` secret is empty, surfacing a `::warning::` instead of failing the build, so the pipeline goes green out of the box until credentials are configured
- new `scripts/smoke-public-site.sh` validates `/`, `/login`, `/assets/app.css`, `/api/health` envelope, and confirms `.env`, `database/schema.sql`, `CHANGELOG.md`, `VERSION`, `CLAUDE.md` are not publicly served
- VERSION bumped to 0.3.1

## 0.3.0 - 2026-05-04
- GitHub Actions deploy pipeline `.github/workflows/deploy-hostgator.yml` triggered on push to `main` (plus `workflow_dispatch`)
- build/verify scripts under `scripts/build-hostgator-release.sh` and `scripts/verify-hostgator-release.sh` produce a clean FTP package and abort if forbidden paths leak
- `.htaccess` at repo root rewrites every request into `public/` for shared hosting; `public/.htaccess` adds front-controller routing and static passthrough
- deploy guide `deploy/HOSTGATOR_DEPLOY.md` documenting required GitHub Secrets (FTP_HOST, FTP_USERNAME, FTP_PASSWORD, FTP_REMOTE_DIR, optional CLOUDFLARE_*), remote setup, smoke checks, rollback
- `.gitignore` now excludes `.deploy-build/` and `.ftp-deploy-sync-state.json`
- VERSION bumped to 0.3.0

## 0.2.0 - 2026-05-04
- session-based web auth with `/login`, `/logout`, CSRF and AdminOnly middleware
- JWT HS256 auth for the mobile API (`POST /api/auth/login`, ApiAuth middleware, 7-day TTL)
- 12 PDO repositories scoped by `condominium_id` (users, units, condominiums, notices, maintenance, payments, deliveries, visitors, common areas, bookings, documents, messages)
- 14 API controllers wired with role checks, conflict detection (bookings), QR token generation (visitors), and `{success,data,meta}` envelope
- web admin: real DashboardController with stats + ModuleController list pages backed by repositories
- shared list template `templates/modules/list.php` with money/date/datetime formatters
- external stylesheet at `public/assets/app.css` replacing inline layout styles
- routes regrouped: `/login` public, all admin pages behind `AdminOnly`; full `/api/*` group behind `ApiAuth`
- README in English (canonical) plus `README.pt-BR.md`
- VERSION bumped to 0.2.0

## 0.1.0 - 2026-05-02
- created initial PHP + MySQL scaffold for condominium management
- added web admin dashboard and module placeholders
- added mobile-ready JSON API baseline
- added MySQL schema and development seeds
- added screenshot reference folder and UI summary based on provided prints
