# Changelog

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
