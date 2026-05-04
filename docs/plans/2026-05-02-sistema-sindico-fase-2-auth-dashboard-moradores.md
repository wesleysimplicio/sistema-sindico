# Sistema Síndico Fase 2 — Auth, Dashboard e Moradores Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Transformar o scaffold inicial em um primeiro bloco funcional e visualmente mais próximo dos prints, cobrindo login web/API, dashboard com dados reais e módulo de moradores com listagem útil para a futura experiência mobile.

**Architecture:** Manter a base em PHP puro + MySQL, evoluindo o scaffold atual com autenticação por sessão para o painel web e JWT para a API mobile-ready. Em vez de tentar fechar todos os módulos de uma vez, executar em blocos pequenos: primeiro acesso/autenticação, depois dashboard com métricas reais, depois moradores com listagem e detalhes suficientes para espelhar as referências visuais.

**Tech Stack:** PHP 8.5, MySQL, plain PHP MVC-style structure, built-in PHP server para smoke local, Git/GitHub, Claude Code para implementação guiada.

---

## Current state snapshot
- Repo local já existe em `/Users/wesleysimplicio/Projetos/novos/sistema-sindico` e está limpo em `main`.
- A fase 1 já entregou scaffold web/API, schema MySQL, seeds, changelog, versionamento e repositório GitHub privado.
- O layout atual está funcional mas ainda genérico: `templates/layouts/app.php` tem sidebar desktop, `templates/modules/dashboard.php` é um scaffold e `templates/modules/placeholder.php` ainda representa os módulos.
- Já existe base de autenticação técnica em `src/Core/Auth.php`, `src/Core/Session.php`, `src/Core/Jwt.php`, `src/Middleware/WebAuth.php`, `src/Middleware/ApiAuth.php` e `src/Middleware/AdminOnly.php`, mas o fluxo real ainda não foi ligado às telas web nem ao endpoint API.
- O endpoint `/api/auth/login` ainda devolve token stub em `src/Controllers/Api/AuthController.php`.
- O módulo de moradores hoje só existe como placeholder web em `src/Controllers/Web/ModuleController.php`, enquanto a API `/api/residents` já busca dados reais em `UserRepository`.
- Os prints já foram analisados e resumidos em `docs/ui-reference-summary.md`, indicando padrões recorrentes como topo roxo, navegação mobile-like, atalhos, listas com avatar/ícone e uso de PT-BR.
- Validação já conhecida da fase 1: lint PHP OK, smoke em `/`, `/api/health`, `/api/residents`, `/api/notices`, `/api/maintenance` e `/api/payments` OK após importar `database/schema.sql` e `database/seed.sql`.
- Primeira tentativa ampla com Claude Code na fase 2 atingiu `max turns (20)` sem alterar arquivos do produto; por isso a execução foi quebrada em tarefas menores começando apenas por login web e proteção de rotas.

## Recommended execution order
1. Fechar o fluxo de login web/API primeiro, porque dashboard e moradores devem respeitar usuário/condomínio autenticado.
2. Proteger rotas web com middleware e adicionar logout antes de refinar a experiência visual.
3. Evoluir dashboard para usar dados reais do condomínio logado.
4. Substituir o placeholder de moradores por uma tela real de listagem com cards/tabela leve, filtros simples e CTA preparados para evolução.
5. Validar o bloco inteiro localmente antes de tocar outros módulos visuais.

### Task 1: Criar tela web de login e rotas públicas/protegidas

**Objective:** Entregar a primeira experiência real de acesso ao painel com tela de login em PT-BR, baseada na identidade visual observada nos prints.

**Files:**
- Modify: `routes/web.php`
- Create: `src/Controllers/Web/AuthController.php`
- Create: `templates/auth/login.php`
- Modify: `templates/layouts/app.php`
- Modify: `src/Controllers/Web/HomeController.php`

**Step 1: Write failing test**

Como o projeto ainda não possui suíte automatizada, usar smoke manual controlado como teste inicial.

Run: `php -S 127.0.0.1:8099 -t public`
Expected: ao abrir `http://127.0.0.1:8099/login`, hoje a rota deve falhar com 404 porque a tela ainda não existe.

**Step 2: Run test to verify failure**

Run: `curl -i http://127.0.0.1:8099/login`
Expected: FAIL com `404` ou HTML de página não encontrada.

**Step 3: Write minimal implementation**

Criar rotas GET `/login`, POST `/login` e POST `/logout`, deixando `/dashboard` protegido por `WebAuth` ou `AdminOnly` conforme o perfil esperado. A tela `templates/auth/login.php` deve usar copy em PT-BR, card centralizado, identidade roxa e mensagem clara sobre ambiente demo quando aplicável.

**Step 4: Run test to verify pass**

Run:
- `curl -i http://127.0.0.1:8099/login`
- `curl -i http://127.0.0.1:8099/dashboard`

Expected:
- `/login` responde `200`
- `/dashboard` redireciona para `/login` quando não autenticado

**Step 5: Commit**

```bash
git add routes/web.php src/Controllers/Web/AuthController.php templates/auth/login.php templates/layouts/app.php src/Controllers/Web/HomeController.php
git commit -m "feat: add web login flow scaffold"
```

### Task 2: Ligar autenticação real no web login e no login da API

**Objective:** Substituir o stub atual por autenticação real usando `users.password_hash`, sessão web e JWT para consumo mobile futuro.

**Files:**
- Modify: `src/Controllers/Api/AuthController.php`
- Modify: `src/Core/Auth.php`
- Modify: `src/Repositories/UserRepository.php`
- Modify: `routes/api.php`
- Modify: `database/seed.sql`
- Optionally modify: `src/Core/Response.php`

**Step 1: Write failing test**

Run:
- `curl -sS -X POST http://127.0.0.1:8099/api/auth/login -H 'Content-Type: application/json' -d '{"email":"admin@sindico.local","password":"errada"}'`
- `curl -sS -X POST http://127.0.0.1:8099/api/auth/login -H 'Content-Type: application/json' -d '{"email":"admin@sindico.local","password":"senha123"}'`

Expected today:
- login incorreto ainda pode não falhar do jeito certo
- login correto ainda devolve token stub sem validar senha nem tocar `last_login_at`

**Step 2: Run test to verify failure**

Expected: comportamento inconsistente com o objetivo da fase 2.

**Step 3: Write minimal implementation**

Implementar leitura de email/senha, validação com `Auth::attempt`, atualização de `last_login_at`, geração de JWT real com `Jwt::encode`, retorno do usuário autenticado e endpoint `/api/me` para o app mobile futuro. No fluxo web, o POST `/login` deve autenticar e redirecionar para `/dashboard`.

**Step 4: Run test to verify pass**

Run:
- `curl -i -sS -X POST http://127.0.0.1:8099/api/auth/login -H 'Content-Type: application/json' -d '{"email":"admin@sindico.local","password":"senha123"}'`
- `curl -i -sS -X POST http://127.0.0.1:8099/api/auth/login -H 'Content-Type: application/json' -d '{"email":"admin@sindico.local","password":"errada"}'`

Expected:
- senha correta retorna `200`, token JWT e dados reais do usuário
- senha inválida retorna `401` com mensagem clara

**Step 5: Commit**

```bash
git add src/Controllers/Api/AuthController.php src/Core/Auth.php src/Repositories/UserRepository.php routes/api.php database/seed.sql src/Core/Response.php
git commit -m "feat: wire real auth for web and api"
```

### Task 3: Transformar o dashboard em tela real baseada nos prints

**Objective:** Trocar o dashboard scaffold por uma home mais próxima dos prints, com cabeçalho, atalhos e cards de resumo alimentados por dados reais.

**Files:**
- Modify: `src/Controllers/Web/HomeController.php`
- Modify: `templates/modules/dashboard.php`
- Modify: `templates/layouts/app.php`
- Create: `src/Repositories/DashboardRepository.php`

**Step 1: Write failing test**

Run: `curl -sS http://127.0.0.1:8099/dashboard`
Expected today: HTML genérico sem métricas reais, sem bloco claro de resumo executivo do condomínio.

**Step 2: Run test to verify failure**

Expected: dashboard ainda insuficiente para refletir os prints.

**Step 3: Write minimal implementation**

Adicionar métricas reais como moradores ativos, avisos publicados, chamados abertos, pagamentos pendentes e encomendas aguardando. Renderizar um topo visual mais próximo dos prints, seção de atalhos, cards de resumo e listas curtas de atividade recente, mantendo PT-BR e sem inventar módulos novos.

**Step 4: Run test to verify pass**

Run:
- `curl -sS http://127.0.0.1:8099/dashboard | head -80`
- abrir no navegador local para inspeção visual

Expected: HTML com blocos reais do dashboard e labels coerentes com o domínio condominial.

**Step 5: Commit**

```bash
git add src/Controllers/Web/HomeController.php templates/modules/dashboard.php templates/layouts/app.php src/Repositories/DashboardRepository.php
git commit -m "feat: implement real dashboard overview"
```

### Task 4: Substituir o placeholder de moradores por uma tela real

**Objective:** Entregar a primeira tela de módulo real usando os dados existentes de `users` e `units`.

**Files:**
- Modify: `src/Controllers/Web/ModuleController.php`
- Create: `src/Controllers/Web/ResidentController.php`
- Create: `templates/modules/residents/index.php`
- Modify: `src/Repositories/UserRepository.php`
- Modify: `routes/web.php`
- Optionally create: `templates/components/empty-state.php`

**Step 1: Write failing test**

Run: `curl -sS http://127.0.0.1:8099/moradores`
Expected today: HTML placeholder com texto genérico e sem listagem real.

**Step 2: Run test to verify failure**

Expected: FAIL visual/funcional em relação ao objetivo da fase 2.

**Step 3: Write minimal implementation**

Criar tela real de moradores com busca simples, resumo quantitativo, cards ou tabela leve com nome, unidade, telefone, status e papel. Usar `UserRepository` para listar apenas usuários do condomínio atual e priorizar labels/estrutura observados nos prints.

**Step 4: Run test to verify pass**

Run:
- `curl -sS http://127.0.0.1:8099/moradores | head -120`
- `curl -sS http://127.0.0.1:8099/api/residents`

Expected:
- tela web deixa de ser placeholder
- API permanece íntegra e coerente com a listagem

**Step 5: Commit**

```bash
git add src/Controllers/Web/ModuleController.php src/Controllers/Web/ResidentController.php templates/modules/residents/index.php src/Repositories/UserRepository.php routes/web.php templates/components/empty-state.php
git commit -m "feat: add residents management screen"
```

### Task 5: Refinar navegação, mensagens e estados vazios do bloco inicial

**Objective:** Ajustar o shell visual para que login, dashboard e moradores pareçam parte do mesmo produto.

**Files:**
- Modify: `templates/layouts/app.php`
- Modify: `templates/auth/login.php`
- Modify: `templates/modules/dashboard.php`
- Modify: `templates/modules/residents/index.php`

**Step 1: Write failing test**

Run: inspeção visual manual das três telas.
Expected today: possíveis inconsistências entre espaçamento, cabeçalhos, CTA e estados vazios.

**Step 2: Run test to verify failure**

Registrar os pontos incoerentes vistos localmente.

**Step 3: Write minimal implementation**

Padronizar header, breadcrumbs curtos, CTA primário, avatar/identificação do usuário logado, mensagens vazias e blocos de apoio, sem exagerar em responsividade complexa neste primeiro bloco.

**Step 4: Run test to verify pass**

Run: abrir `/login`, `/dashboard` e `/moradores` no navegador.
Expected: experiência visual coerente e claramente além do scaffold.

**Step 5: Commit**

```bash
git add templates/layouts/app.php templates/auth/login.php templates/modules/dashboard.php templates/modules/residents/index.php
git commit -m "style: unify visual shell for auth and residents flows"
```

### Task 6: Validar, documentar e publicar o bloco da fase 2

**Objective:** Fechar o primeiro bloco funcional com validação local, changelog, versionamento, commit e push.

**Files:**
- Modify: `README.md`
- Modify: `CHANGELOG.md`
- Modify: `VERSION`
- Modify: `docs/plans/2026-05-02-sistema-sindico-fase-2-auth-dashboard-moradores.md`

**Step 1: Write failing test**

Run: `find src routes config templates -type f -name '*.php' -print0 | xargs -0 -n1 php -l`
Expected: zero erros de sintaxe; se houver qualquer erro, corrigir antes de encerrar.

**Step 2: Run test to verify failure**

Se algum lint ou smoke falhar, tratar como bloqueio de fechamento.

**Step 3: Write minimal implementation**

Atualizar README com credenciais demo e fluxo do bloco 1 da fase 2, adicionar changelog e version bump compatíveis com a entrega real.

**Step 4: Run test to verify pass**

Run:
- `php -l public/index.php`
- `find src routes config templates -type f -name '*.php' -print0 | xargs -0 -n1 php -l`
- `taskflow run /Users/wesleysimplicio/Projetos/novos/sistema-sindico`
- smoke manual em `/login`, `/dashboard`, `/moradores`, `/api/auth/login`, `/api/residents`

Expected: lint OK, smoke OK, checklist humano gerado/lido.

**Step 5: Commit**

```bash
git add README.md CHANGELOG.md VERSION docs/plans/2026-05-02-sistema-sindico-fase-2-auth-dashboard-moradores.md
git commit -m "docs: record phase 2 auth dashboard residents milestone"
```
