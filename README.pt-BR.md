# Sistema Sindico

Sistema de gestao condominial em **PHP 8.2 + MySQL 8**, com painel web administrativo renderizado no servidor e endpoints REST prontos para o futuro app mobile em `/api`.

English version: [README.md](README.md).

## Recursos

- Painel web por sessao (papeis sindico/admin).
- API JSON com JWT para o app mobile (moradores/porteiros).
- Multi-tenant: toda tabela de dominio escopa por `condominium_id`.
- Modulos: condominios, unidades, moradores, avisos, manutencao, pagamentos, encomendas, visitantes, areas comuns, reservas, documentos, mensagens.
- Sem framework — router proprio minimo, repositorios PDO, JWT HS256 proprio.

## Stack

- PHP 8.2+, PDO MySQL
- MySQL 8 (InnoDB, utf8mb4)
- Sessao + CSRF no painel web
- JWT HS256 (TTL 7 dias) na API
- CSS puro em `public/assets/app.css`

## Estrutura

```
public/         entrypoint + assets estaticos
routes/         web.php + api.php
src/Core/       bootstrap, router, auth, jwt, request, response, view, db
src/Controllers/Web   admin renderizado no servidor
src/Controllers/Api   JSON para mobile
src/Middleware/       AdminOnly, ApiAuth, WebAuth
src/Repositories/     um por entidade
templates/      layouts + views por modulo
database/       schema.sql + seed.sql
docs/print/     referencias visuais
```

## Requisitos

- PHP 8.2+ com `pdo_mysql`
- MySQL 8+

## Setup

```bash
cp .env.example .env
# editar DB_* e JWT_SECRET
mysql -u root -p -e "CREATE DATABASE sistema_sindico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p sistema_sindico < database/schema.sql
mysql -u root -p sistema_sindico < database/seed.sql
php -S 127.0.0.1:8000 -t public
```

Depois acesse:

- Painel web: <http://127.0.0.1:8000/login>
- Health da API: <http://127.0.0.1:8000/api/health>

## Credenciais semeadas

Todos os usuarios semeados usam a senha `senha123`.

| Papel    | Email                          |
|----------|--------------------------------|
| admin    | admin@sistemasindico.local     |
| sindico  | sindico@sistemasindico.local   |
| morador  | morador@sistemasindico.local   |
| porteiro | porteiro@sistemasindico.local  |

## API REST

Endpoints autenticados exigem `Authorization: Bearer <jwt>` obtido em `POST /api/auth/login`. Respostas seguem `{ success, data, meta }`.

Veja a tabela completa de rotas no [README.md](README.md).

## Roadmap

- Upload de arquivos para documentos/avatares
- QR-code real
- Notificacoes push
- App mobile (React Native ou Flutter) consumindo `/api`
- Refinamento visual a partir dos prints em `docs/print/`
