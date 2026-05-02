# Sistema Síndico Bootstrap Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Create the initial private repository scaffold for a condominium management system in PHP + MySQL, including a web admin base, API endpoints prepared for future mobile use, and documentation/placeholders for screenshot-driven UI work.

**Architecture:** Use a lightweight PHP MVC-style structure with a public entrypoint, app layer for controllers/services/repositories, SQL migration bootstrap, and JSON API routes under `/api`. Keep the first iteration dependency-light so the project can evolve quickly once screenshots and detailed workflows arrive.

**Tech Stack:** PHP 8.5, MySQL, plain PHP, built-in router/bootstrap, GitHub private repository, Claude Code for implementation.

---

## Current state snapshot
- Local repo created at `/Users/wesleysimplicio/Projetos/novos/sistema-sindico`.
- Git initialized on `main` with no commits yet.
- `taskflow inspect` reports a generic stack with manual validation expected.
- `CLAUDE.md` is present with project guidance.
- Screenshots are not yet present; `docs/print/` must exist for later ingestion.

## Recommended execution order
1. Create foundational repo structure and documentation.
2. Add PHP bootstrap, routing, configuration, and environment examples.
3. Add MySQL schema/migration starter plus seed examples.
4. Add web/admin and JSON API scaffolds for core condominium entities.
5. Validate syntax and smoke routes locally.
6. Create private GitHub repository and push.

### Task 1: Create repository skeleton

**Objective:** Establish the directory structure and starter documentation.

**Files:**
- Create: `docs/print/.gitkeep`
- Create: `src/`, `config/`, `database/`, `public/`, `routes/`, `storage/logs/`, `templates/`
- Create: `README.md`
- Create: `CHANGELOG.md`
- Create: `VERSION`

**Step 1:** Create folders and placeholder files.

**Step 2:** Write README with local run instructions and scope.

**Step 3:** Commit bootstrap docs and structure.

### Task 2: Add application bootstrap

**Objective:** Provide a working PHP entrypoint and router for web and API requests.

**Files:**
- Create: `public/index.php`
- Create: `src/Core/Application.php`
- Create: `src/Core/Router.php`
- Create: `config/app.php`
- Create: `.env.example`

**Step 1:** Add minimal bootstrap loading env/config.

**Step 2:** Add router capable of HTML and JSON responses.

**Step 3:** Verify with local PHP server.

### Task 3: Add MySQL-ready data layer

**Objective:** Prepare environment-driven database connection and first schema.

**Files:**
- Create: `src/Core/Database.php`
- Create: `database/schema.sql`
- Create: `database/seed.sql`

**Step 1:** Add PDO MySQL connection helper.

**Step 2:** Define initial schema for condos, units, residents, notices, maintenance_requests, payments, users.

**Step 3:** Document import instructions.

### Task 4: Add web admin scaffolding

**Objective:** Deliver initial admin-facing pages and navigation placeholders.

**Files:**
- Create: `src/Controllers/Web/*`
- Create: `templates/layouts/app.php`
- Create: `templates/dashboard.php`
- Create: `templates/modules/*.php`
- Create: `routes/web.php`

**Step 1:** Add dashboard and module landing pages.

**Step 2:** Keep copy in PT-BR and structure aligned to condomínio operations.

**Step 3:** Leave clear notes that UI will be refined from `docs/print/` references.

### Task 5: Add JSON API scaffold

**Objective:** Expose future-mobile-ready endpoints for core resources.

**Files:**
- Create: `src/Controllers/Api/*`
- Create: `routes/api.php`

**Step 1:** Add health endpoint.

**Step 2:** Add stub/list endpoints for condos, units, residents, notices, maintenance requests, payments.

**Step 3:** Return consistent JSON envelope.

### Task 6: Validate and publish initial baseline

**Objective:** Run local checks, create the private remote, and push the baseline.

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `VERSION`

**Step 1:** Run PHP syntax checks and route smoke tests.

**Step 2:** Create private GitHub repo.

**Step 3:** Commit and push baseline.
