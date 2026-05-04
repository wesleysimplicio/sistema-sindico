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
