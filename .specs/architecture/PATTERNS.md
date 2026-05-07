# Patterns — `sistema-sindico`

> Como escrever código aqui. Curto, opinativo, executável.
> Audiência: dev humano e agent AI. Se a regra não está aqui, vale o senso comum + revisão de PR.
> Stack canônica: PHP 8.2 + MySQL 8 + PDO + sem framework. Autoload PSR-4 (`App\` to `src/`).

---

## 1. Naming

| Item | Convenção | Exemplo bom | Exemplo ruim |
|------|-----------|-------------|--------------|
| Classes (PHP) | PascalCase, sufixo do papel | `UserRepository`, `AuthController`, `RateLimit` | `userRepo`, `authCtrl` |
| Métodos | camelCase, verbo + objeto | `findByEmail`, `enforce`, `decode` | `do`, `process` |
| Arquivos PHP | mesmo nome da classe (`UserRepository.php`) | `BaseRepository.php` | `base_repository.php` |
| Pastas `src/` | PascalCase casando o namespace | `Controllers/Api`, `Repositories`, `Middleware` | `controllers/`, `repos/` |
| Migrations SQL | `NNN_<sprint-ou-feature>.sql` zero-padded | `011_rate_limits.sql` | `add-rate.sql` |
| Templates | snake-case por módulo | `templates/modules/dashboard/index.php` | `Dashboard.PHP` |
| Branches | `feat/<slug>`, `fix/<slug>`, `chore/<slug>` | `feat/visitor-qr-rotate` | `wesley-branch` |
| Commits | conventional commits, em inglês | `feat: rotate visitor qr token` | `update stuff` |
| Testes E2E | `*.spec.js` em `tests/e2e/specs/` | `admin-happy-path.spec.js` | `tests1.js` |
| Coleção API | `tests/api/sistema-sindico.postman_collection.json` | — | — |

Regra de ouro: nome conta o quê, não o como. `processData` é vago, `findByEmail` é claro.

Todo arquivo PHP novo começa com:

```php
<?php

declare(strict_types=1);

namespace App\<Camada>;
```

---

## 2. Estrutura de pastas (real do repo)

```
public/        front controller (index.php) + assets
routes/        web.php (sessão+CSRF) e api.php (JWT via ApiAuth)
src/
  Core/        Application, Router, Auth, Jwt, Totp, Session, Request,
               Response, Database, View, Env, StoragePath, PasswordPolicy
  Middleware/  AdminOnly, ApiAuth, WebAuth, RateLimit
  Controllers/
    Web/       SSR controllers (LoginController, DashboardController)
    Api/       REST controllers (AuthController, VisitorController)
  Repositories/  BaseRepository + 40 repos PDO (UserRepository, ApiTokenRepository)
  Support/     helpers utilitarios
templates/     views PHP (layouts/app.php, modules/<feature>/)
database/
  schema.sql       fonte da verdade do schema
  seed.sql         usuarios + dados de exemplo
  migrations/      001 a 012, idempotentes via INFORMATION_SCHEMA
tests/
  api/             colecao Postman/Newman
  e2e/             Playwright (specs/, playwright.config.js)
scripts/       build/verify/smoke do release HostGator
```

Regra de dependência: `Controllers` chamam `Core` + `Repositories`. `Repositories` só conhecem `Core/Database` (PDO). `Core` é o único acoplamento aos detalhes de runtime (PHP superglobals, sessão, JWT, file_get_contents). Templates só consomem variáveis injetadas pelo `Core/View`.

---

## 3. Como criar endpoint novo (REST API)

1. Controller em `src/Controllers/Api/<Recurso>Controller.php`. Classe `final class`, namespace `App\Controllers\Api`.
2. Cada ação é um método público sem parâmetros: lê via `Request::input('campo', default)` ou `Request::route('param')` e responde via `Response::json($data, $status)` ou `Response::error($msg, $status, $details, $code)`.
3. Tenant guard: leia `Auth::condominiumId()` ou `Auth::user()` e use sempre em queries: `WHERE condominium_id = :cid`. Path params com IDs cruzados (ex.: `/units/{u}`) são checados contra `Auth::user()['condominium_id']` antes de qualquer SQL.
4. Rota em `routes/api.php` dentro do grupo `ApiAuth`:
   ```php
   $router->group([ApiAuth::class], function ($router) {
       $router->post('/api/visitors/{id}/qr', [VisitorController::class, 'rotateQr']);
   });
   ```
   Endpoints públicos (login, webhook, health) ficam fora do grupo, no topo do arquivo.
5. Repository em `src/Repositories/<Recurso>Repository.php`, estende `BaseRepository`, define `protected string $table = '...';`. Toda coluna em `$where` e `INSERT/UPDATE` passa por `assertColumnName` (regex `^[a-zA-Z_][a-zA-Z0-9_]*$`); placeholders nomeados (`:col`) — nunca concatenar strings em SQL.
6. Validação inline no início da ação. Falha com `Response::error('mensagem', 422)` (envelope `{success:false, error:{code,message,details}, meta}`).
7. Rate limit em endpoints sensíveis: `RateLimit::enforce('bucket', limit, windowSeconds, RateLimit::ipKey($email))`.
8. Audit log quando a ação é sensível: `(new AuditLogRepository())->record(action, entity, payload, ip)`.
9. Postman: adicionar request à collection `tests/api/sistema-sindico.postman_collection.json` com exemplo `200` + `401` + `422`.
10. Playwright ou Newman para o caminho feliz quando o fluxo é crítico.

Critério de feito: 200 happy path + 401 sem token + 403 cross-tenant + 422 input inválido + 429 rate limit (quando aplicável) — todos cobertos por Newman.

---

## 4. Como criar tela web (painel síndico/admin)

1. Controller em `src/Controllers/Web/<Tela>Controller.php`. Métodos retornam HTML via `View::render('modules/<feature>/index', $data)`.
2. Rota em `routes/web.php` no grupo `AdminOnly` (sessão + role `admin|sindico`):
   ```php
   $router->group([AdminOnly::class], function ($router) {
       $router->get('/maintenance', [MaintenanceWebController::class, 'index']);
   });
   ```
3. Template em `templates/modules/<feature>/<acao>.php`. Estende `templates/layouts/app.php`. CSRF token vem de `Session::csrfToken()` em todo `<form>`.
4. Estados cobertos: vazio, lista paginada, erro de validação, sucesso após POST.
5. JS estático em `public/assets/`. Sem build step — código vanilla ou módulos ES nativos.

---

## 5. Como criar teste

Pirâmide: muitos Newman (API), poucos Playwright (E2E).

### API — Newman/Postman
- Collection raiz: `tests/api/sistema-sindico.postman_collection.json`.
- Cada endpoint novo ganha: 1 request feliz + 1 request de auth ausente + 1 request de input inválido.
- Variáveis de ambiente: `{{baseUrl}}`, `{{token}}`, `{{condominiumId}}`.
- Rodar local:
  ```bash
  npx newman run tests/api/sistema-sindico.postman_collection.json --env-var baseUrl=http://127.0.0.1:8000
  ```

### E2E — Playwright
- Specs em `tests/e2e/specs/*.spec.js`. Config em `tests/e2e/playwright.config.js` (e raiz `playwright.config.ts` para cross-browser).
- Cobre fluxos críticos do painel: login, criar aviso, marcar pagamento, cadastrar visita.
- Trace + screenshot on-failure. Evidência (`trace.zip`, screenshot) anexada ao PR.
- Rodar local:
  ```bash
  BASE_URL=http://127.0.0.1:8000 npx playwright test
  npx playwright show-report
  ```

### Sem unit test framework no v1
- Não há PHPUnit/Pest configurado. Lógica crítica deve ser exercitada via Newman/Playwright. Quando virar dor (>30s pra subir suite), avaliar PHPUnit em ADR.

Regra: não comitar com Newman vermelho. Não pular spec pra entregar mais rápido. Skip só com link pra issue de retomada.

---

## 6. Tratamento de erro

Princípio: validar na boundary, falhar rápido, retornar envelope JSON consistente.

- Input inválido para `Response::error('mensagem', 422, ['campo' => 'detalhe'])` antes de tocar repositório.
- Auth: token ausente/inválido para `ApiAuth` retorna `Response::error('...', 401)`. Sessão expirada no painel para redirect `/login`.
- Tenant leak: SQL deve sempre filtrar por `condominium_id`. Se uma query retornar registro de outro tenant, tratar como bug P0, abrir issue, escrever regression Newman.
- Domínio: regra violada (ex.: pagamento já pago, booking conflito) para `Response::error('mensagem', 409, [], 'conflict')`.
- Inesperado: `Application::run()` captura `Throwable`, loga via `error_log`, retorna `Response::error('Erro interno.', 500)`.

```php
if ($payment === null || (int) $payment['condominium_id'] !== Auth::condominiumId()) {
    Response::error('Pagamento nao encontrado.', 404);
    return;
}
if ($payment['status'] === 'pago') {
    Response::error('Pagamento ja quitado.', 409, [], 'already_paid');
    return;
}
```

Nunca `try { ... } catch (\Throwable $e) { /* swallow */ }`. Se ignorar é decisão consciente, comentar por quê.

---

## 7. Logging e auditoria

- Sem framework de log estruturado no v1 — `error_log()` para erros não-tratados.
- Ações sensíveis (login, reset de senha, mudança de role, marcar pagamento) gravam em `audit_logs` via `AuditLogRepository::record(user_id, action, entity, entity_id, payload_json, ip)`.
- Nunca logar: `password_hash`, `totp_secret`, JWT bruto, `qr_token` ativo, código de reset, body de webhook com payload sensível.
- Mascarar quando precisa contexto: `email: "u***@example.com"`.
- `payload` em `audit_logs` é JSON — incluir só o necessário (IDs, antes/depois de status), nunca dados pessoais completos.

---

## 8. Validação

- Validar uma vez na boundary (controller). Repositório confia que input já é válido.
- Para senha: `PasswordPolicy::validate($plain)` (mínimo, complexidade, histórico).
- Para campo de coluna: `BaseRepository::assertColumnName` impede injection nos métodos genéricos.
- Para path traversal em arquivo: `StoragePath::isSafeRelative($path)` antes de qualquer `file_put_contents` / `readfile`.
- Mensagens de erro consistentes: `{ field, code, message }` no array `details` do envelope.

```php
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Email invalido.', 422, ['email' => 'invalid_format']);
    return;
}
```

Nunca validar a mesma coisa em 3 camadas. Duplicação é dívida.

---

## 9. Persistência e migrations

- Toda nova tabela de domínio começa com:
  ```sql
  condominium_id BIGINT UNSIGNED NOT NULL,
  FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  KEY idx_<tabela>_condominium (condominium_id),
  ```
- Migrations em `database/migrations/NNN_<slug>.sql`, idempotentes via `INFORMATION_SCHEMA`. Padrão observado:
  ```sql
  SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rate_limits');
  SET @s := IF(@t = 0, "CREATE TABLE rate_limits (...)", "DO 0");
  PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
  ```
- Para `ADD COLUMN`: mesmo padrão consultando `INFORMATION_SCHEMA.COLUMNS`.
- Charset/engine fixos: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`.
- Status de fluxo de negócio em pt-BR (`pendente`, `pago`, `aberto`, `concluido`); status técnico em inglês (`active`, `revoked`, `expired`).
- `database/schema.sql` é a fonte da verdade. Após criar migration, atualizar `schema.sql` espelhando o estado final.

---

## 10. Quando dividir vs manter junto

- Controller maior que 300 linhas para considerar split por sub-recurso (ex.: `VisitorController` + `VisitorQrController`).
- Repositório com 7+ métodos públicos não-CRUD para revisar responsabilidade.
- 3 ocorrências da mesma query montada à mão em controllers diferentes para mover pra método nomeado no repositório.
- Lógica de negócio em controller (cálculo de prazo, regra de aprovação) com >20 linhas para extrair para `src/Support/<Feature>.php`.

Regra `Wesley Simplicio`: simplicidade ganha de elegância. Código óbvio é melhor que código esperto.
