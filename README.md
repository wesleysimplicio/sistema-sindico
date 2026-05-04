# Sistema Sindico

Condominium management system in **PHP 8.2 + MySQL 8**, with a server-rendered admin panel and mobile-ready REST endpoints under `/api`.

PT-BR version: [README.pt-BR.md](README.pt-BR.md).

## Features

- Session-based web admin (sindico/admin roles).
- JWT-based JSON API for the future mobile app (residents/porteiros).
- Multi-tenant: every domain table scopes by `condominium_id`.
- Modules: condominiums, units, residents, notices, maintenance, payments, deliveries, visitors, common areas, bookings, documents, messages.
- No framework dependency — minimal custom router, PDO repositories, custom HS256 JWT.

## Stack

- PHP 8.2+, PDO MySQL
- MySQL 8 (InnoDB, utf8mb4)
- Session + CSRF for the web panel
- HS256 JWT (7-day TTL) for the API
- Plain CSS in `public/assets/app.css`

## Layout

```
public/         entrypoint + static assets
routes/         web.php + api.php
src/Core/       bootstrap, router, auth, jwt, request, response, view, db
src/Controllers/Web   server-rendered admin
src/Controllers/Api   mobile-ready JSON
src/Middleware/       AdminOnly, ApiAuth, WebAuth
src/Repositories/     one per entity
templates/      layouts + module views
database/       schema.sql + seed.sql
docs/print/     UI references
```

## Requirements

- PHP 8.2+ with `pdo_mysql`
- MySQL 8+

## Setup

```bash
cp .env.example .env
# edit DB_* and JWT_SECRET
mysql -u root -p -e "CREATE DATABASE sistema_sindico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p sistema_sindico < database/schema.sql
mysql -u root -p sistema_sindico < database/seed.sql
php -S 127.0.0.1:8000 -t public
```

Then open:

- Web admin: <http://127.0.0.1:8000/login>
- API health: <http://127.0.0.1:8000/api/health>

## Seeded credentials

All seeded users use the password `senha123`.

| Role     | Email                          |
|----------|--------------------------------|
| admin    | admin@sistemasindico.local     |
| sindico  | sindico@sistemasindico.local   |
| morador  | morador@sistemasindico.local   |
| porteiro | porteiro@sistemasindico.local  |

## REST API

Authenticated endpoints expect `Authorization: Bearer <jwt>` obtained from `POST /api/auth/login`. Responses follow `{ success, data, meta }`.

### Public

- `GET  /api/health`
- `POST /api/auth/login` — body `{ email, password }`

### Authenticated

| Method | Path                              | Notes |
|--------|-----------------------------------|-------|
| GET    | `/api/auth/me`                    | current user |
| POST   | `/api/auth/logout`                | |
| GET    | `/api/profile`                    | full profile + condo + unit |
| GET    | `/api/condominiums`               | |
| GET    | `/api/condominiums/{id}`          | |
| GET    | `/api/units`                      | scoped to current condo |
| GET    | `/api/residents`                  | role=morador |
| GET    | `/api/notices`                    | |
| GET    | `/api/notices/{id}`               | |
| POST   | `/api/notices`                    | admin/sindico |
| GET    | `/api/maintenance`                | `?status=` |
| GET    | `/api/maintenance/mine`           | requester |
| POST   | `/api/maintenance`                | |
| PATCH  | `/api/maintenance/{id}`           | admin/sindico |
| GET    | `/api/payments`                   | `?status=` |
| GET    | `/api/payments/mine`              | resident |
| GET    | `/api/payments/summary`           | grouped totals |
| PATCH  | `/api/payments/{id}/pay`          | admin/sindico |
| GET    | `/api/visitors`                   | |
| GET    | `/api/visitors/mine`              | host |
| POST   | `/api/visitors`                   | auto QR token |
| PATCH  | `/api/visitors/{id}`              | admin/sindico/porteiro |
| GET    | `/api/visitors/qr/{token}`        | porteiro lookup |
| GET    | `/api/deliveries`                 | |
| GET    | `/api/deliveries/mine`            | resident |
| POST   | `/api/deliveries`                 | admin/sindico/porteiro |
| PATCH  | `/api/deliveries/{id}/withdraw`   | |
| GET    | `/api/common-areas`               | |
| GET    | `/api/bookings`                   | |
| GET    | `/api/bookings/mine`              | resident |
| POST   | `/api/bookings`                   | conflict-checked |
| PATCH  | `/api/bookings/{id}`              | admin/sindico |
| GET    | `/api/documents`                  | `?category=` |
| GET    | `/api/messages`                   | `?channel=` |
| GET    | `/api/messages/inbox`             | |
| POST   | `/api/messages`                   | |
| PATCH  | `/api/messages/{id}/read`         | |

## Smoke check

```bash
find src public routes templates -name '*.php' -print0 | xargs -0 -n1 php -l
php -S 127.0.0.1:8000 -t public
curl -s http://127.0.0.1:8000/api/health | jq
```

## Automation: PR conflict resolver

Workflow [`resolve-conflicts.yml`](.github/workflows/resolve-conflicts.yml) merges the PR base branch into the PR head automatically.

Triggers (either):

- Add the label `resolve-conflicts` to the PR.
- Comment `/resolve-conflicts` on the PR.

Outcomes:

- Clean merge → bot pushes the merge commit and posts a success comment.
- Conflicts → bot posts a comment listing conflicting files plus local resolution steps; workflow run fails so it is visible.

Notes:

- Uses the default `GITHUB_TOKEN` (no extra secrets).
- Cross-repo PRs require *Allow edits by maintainers*.

## Roadmap

- File upload for documents/avatars
- Real QR-code rendering
- Push notifications channel
- Mobile app (React Native or Flutter) consuming `/api`
- Refine UI from `docs/print/` mockups
