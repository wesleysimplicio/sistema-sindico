# Changelog

## 0.5.0 - 2026-05-04
- password recovery flow: `POST /api/auth/forgot-password`, `POST /api/auth/verify-code`, `POST /api/auth/reset-password` (S1-01, S1-02)
- `password_reset_tokens` table added to `database/schema.sql` and `database/migrations/001_password_reset_tokens.sql`
- `UserRepository` extended with `findByDocument`, `createPasswordResetToken`, `findValidResetByCode`, `attachResetToken`, `findValidResetByToken`, `markResetTokenUsed`, `updatePassword`
- web recovery screens (prints 3-5): `/forgot-password`, `/verify-code`, `/reset-password` with CSRF protection, session-based state hand-off and password strength validation
- login template (`templates/auth/login.php`) updated with "Esqueci minha senha" link and info-banner support (print 2 → print 3 bridge)
- `AuthController::validatePassword` public static method enforces ≥8 chars, ≥1 letter, ≥1 digit; reused by both API and web layers
- `public/assets/app.css`: added `.alert.info`, `.alert.success`, `.link-muted` styles
- `docs/specs/PASSWORD-RULES.md`: documents current and planned password rules, recovery flow, and table locations
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
