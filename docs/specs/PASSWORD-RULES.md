# Password Rules — Sistema Síndico

> Updated: 2026-05-04. References: `docs/specs/SPRINT-BACKLOG.md` (S1-01, S1-02, S6-05).

---

## Current rules (MVP — Sprint 1)

| Rule | Constraint | Error message |
|------|-----------|---------------|
| Minimum length | ≥ 8 characters | "A senha deve ter no mínimo 8 caracteres." |
| Contains a letter | At least one `[A-Za-z]` | "A senha deve conter pelo menos uma letra." |
| Contains a digit | At least one `[0-9]` | "A senha deve conter pelo menos um número." |

These rules are enforced in:
- `POST /api/auth/reset-password` (mobile API)
- `POST /reset-password` (web admin recovery flow)

---

## Planned rules (Sprint 6 — S6-05)

The following tightening is planned for Sprint 6 as user base grows:

| Rule | Constraint |
|------|-----------|
| Password history | Reject the last 5 used password hashes |
| Special character | At least one non-alphanumeric character (`!@#$%^&*`) |
| Maximum length | ≤ 72 characters (bcrypt limit) |

Password history will be stored in a dedicated `password_history` table (migration to be added in Sprint 6):

```sql
CREATE TABLE password_history (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NOT NULL,
  hash        VARCHAR(255) NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pwh_user (user_id),
  CONSTRAINT fk_pwh_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

The history check will be added to `AuthController::validatePassword` and `PATCH /api/me/password`.

---

## Password recovery flow

```
Screen 3 → POST /api/auth/forgot-password  { document }
Screen 4 → POST /api/auth/verify-code      { document, code }
Screen 5 → POST /api/auth/reset-password   { reset_token, new_password }
```

Web equivalents:

```
/forgot-password  (GET + POST)
/verify-code      (GET + POST)
/reset-password   (GET + POST)
```

The 6-digit code is valid for **10 minutes**. After a valid code is submitted,
a short-lived `reset_token` (64 hex chars, same 10-minute window) is issued and
stored in the session (web) or returned in the JSON response (API). Submitting
a new `/forgot-password` request invalidates all previous unused codes for that user.

---

## Where validation lives

| Layer | File | Method |
|-------|------|--------|
| API   | `src/Controllers/Api/AuthController.php` | `validatePassword(string): ?string` (public static, reused by web) |
| Web   | `src/Controllers/Web/LoginController.php` | calls `ApiAuth::validatePassword` |
