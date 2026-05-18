# GitHub Copilot — Project Review Guidance

This is a multi-tenant condominium management system in PHP 8.2+ with MySQL 8+.

When reviewing pull requests in this repository, focus on:

## Multi-tenant safety (CRITICAL)
- Every SQL query must be scoped by `condominium_id` where applicable. Cross-tenant data leak is a P0 bug.
- The authenticated user's `condominium_id` and `role` come from the JWT (merged into `Auth::user()` by `src/Middleware/ApiAuth.php`). Path parameters like `/condominium/{c}/...` MUST be checked against `Auth::user()['condominium_id']` before any DB query.

## SQL & Data
- Always use named placeholders with PDO. No string interpolation in SQL ever.
- New tables: include `condominium_id BIGINT UNSIGNED NOT NULL` and an FK back to `condominiums`.
- Migrations under `database/migrations/` must be idempotent — use `INFORMATION_SCHEMA` guards (MySQL 8/9 do not reliably support `IF NOT EXISTS` on `ADD COLUMN`).

## Auth & Crypto
- Passwords: only `password_hash` / `password_verify`. Never MD5/SHA1.
- JWT: `src/Core/Jwt.php` (HS256). The secret comes from `JWT_SECRET` env (>= 32 chars, asserted at boot in `public/index.php`). Never accept a hardcoded fallback.
- Reset codes: stored hashed (`hash('sha256', ...)`), single-use, with attempt counter and lockout.

## API conventions
- All responses go through `App\Core\Response::json($data, $status, $meta)` returning the envelope `{success, data, meta:{timestamp, version}}`.
- Errors: `Response::error($message, $status)`.
- Routes registered in `routes/api.php`; protected endpoints live inside the `ApiAuth` group.

## Code style
- `declare(strict_types=1);` on every PHP file.
- Namespace `App\` (PSR-4) under `src/`.
- Repositories extend `App\Repositories\BaseRepository` (PDO + table-name pattern).
- Comments / UI strings: PT-BR. Code identifiers: English.

## Out of scope for review
- Mobile app code (lives in a separate repo).
- Deploy workflow `.github/workflows/deploy-hostgator.yml` — already battle-tested.

## Local commands (mirrored from AGENTS.md / CLAUDE.md)

```bash
# setup
cp .env.example .env
mysql -u root -p < database/schema.sql
mysql -u root -p sistema_sindico < database/seed.sql

# dev server
php -S 127.0.0.1:8000 -t public

# docker runtime
docker compose up -d --build
docker compose down -v

# syntax check
find src -name "*.php" -exec php -l {} \;

# API regression (Newman + Postman collection)
npx newman run tests/api/sistema-sindico.postman_collection.json \
  --env-var baseUrl=http://127.0.0.1:8000

# E2E web (Playwright)
npx playwright install
BASE_URL=http://127.0.0.1:8000 npx playwright test

# release pipeline (HostGator)
scripts/build-hostgator-release.sh
scripts/verify-hostgator-release.sh
scripts/smoke-public-site.sh

# git / PR
gh pr create --fill
gh run watch
gh issue list --state open --label sprint:8
```

Docker app URL: `http://127.0.0.1:8000` and MySQL host port: `127.0.0.1:3307`. The first Docker DB boot imports `database/schema.sql`, then `database/migrations/*.sql`, then `database/seed.sql`.

Default seed credentials (dev only): `admin@sindico.local` / `senha123` (same password for the other seeded users). Rotate before any non-localhost deploy.

<!-- codex-long-running-agent-overlay:start -->
## Universal Long-Running Agent Overlay

This section complements the repository-specific guidance already in this file. If anything here conflicts with the repo-specific rules above, the repo-specific rules win.

- `PRD.md` is the task source of truth for long-running sessions.
- `PROGRESS.md` is the persistent checkpoint log.
- `GOAL_RESULT.md` is the final execution report.
- Before coding, read this file, `PRD.md`, `PROGRESS.md` when it exists, `README.md`, project manifests, tests, and the relevant source folders.
- Work in small checkpoints, run the smallest relevant validation after each meaningful change, update `PROGRESS.md`, and continue until complete or genuinely blocked.
- Stop only when the requested work is complete, validation is documented, and `GOAL_RESULT.md` reflects the outcome.
- Do not rewrite unrelated architecture, fake successful validation, expose secrets, or push without explicit operator instruction for the active session.
<!-- codex-long-running-agent-overlay:end -->
