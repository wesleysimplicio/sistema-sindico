# CLAUDE.md

> Espelha [AGENTS.md](./AGENTS.md). Edite ambos juntos OU mantenha apenas `AGENTS.md` e `ln -sf AGENTS.md CLAUDE.md`. Claude Code lê arquivo regular, não símbolo, por isso a duplicação aqui.

Stack canônica: **PHP 8.2 + MySQL 8** sem framework, custom router em `src/Core/Router.php`, autoload PSR-4 (`App\` to `src/`). E2E via Playwright, regression de API via Newman, deploy em HostGator via GitHub Actions.

---

## Comandos importantes

```bash
# setup local (1a vez)
cp .env.example .env                             # ajustar DB_*, JWT_SECRET (>= 32 chars)
mysql -u root -p < database/schema.sql           # cria schema
mysql -u root -p sistema_sindico < database/seed.sql   # popula usuarios + dados de exemplo

# desenvolvimento
php -S 127.0.0.1:8000 -t public                  # front controller em :8000

# desenvolvimento (Docker)
docker compose up -d --build                     # app em :8000, MySQL exposto em :3307
docker compose down -v                           # reseta volume e reaplica schema + seed

# qualidade (PHP)
php -l src/Controllers/Api/AuthController.php    # syntax check arquivo a arquivo
find src -name "*.php" -exec php -l {} \;        # syntax check em massa

# regression API (Newman)
npx newman run tests/api/sistema-sindico.postman_collection.json \
  --env-var baseUrl=http://127.0.0.1:8000

# E2E web (Playwright)
npx playwright install                           # instala browsers (1a vez)
BASE_URL=http://127.0.0.1:8000 npx playwright test
npx playwright show-report

# release HostGator
scripts/build-hostgator-release.sh               # gera .deploy-build/
scripts/verify-hostgator-release.sh              # checa integridade do pacote
scripts/smoke-public-site.sh                     # smoke test de URLs publicas pos-deploy

# git/PR
git checkout -b feat/<task-id>-<slug>
gh pr create --fill                              # usa template de PR
gh run watch                                     # acompanha CI do branch atual
gh issue list --state open --label sprint:8      # ver itens da sprint corrente
```

## Setup via Docker

```bash
docker compose up -d --build
curl -s http://127.0.0.1:8000/api/health
```

- App: `http://127.0.0.1:8000`
- MySQL no host: `127.0.0.1:3307`
- Reset do banco Docker: `docker compose down -v`
- Primeiro boot aplica `database/schema.sql`, depois `database/migrations/*.sql` e por fim `database/seed.sql` quando o volume estiver vazio

Credenciais seed default (so dev): `admin@sindico.local` / `senha123` (idem para os demais usuarios semeados). Trocar antes de qualquer deploy fora de localhost.

---

## Tudo mais (workflow, DoD, padrões, proibições, skills, atalhos)

Ver [AGENTS.md](./AGENTS.md). Mudou algo lá? Reflete aqui na seção "Comandos importantes" e no `.github/copilot-instructions.md`.

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
