# Sistema Síndico

Scaffold inicial de um sistema de gestão condominial em **PHP + MySQL**, preparado para evoluir para app mobile via endpoints JSON em `/api`.

## Escopo inicial
- painel web administrativo server-side
- bootstrap leve sem framework pesado
- módulos base inspirados nos prints em `docs/print/`
- estrutura pronta para persistência MySQL
- endpoints REST stub para futura integração mobile

## Módulos mapeados dos prints
- condomínios
- unidades
- moradores
- visitantes
- prestadores
- veículos
- mural de avisos
- documentos
- encomendas
- solicitações e manifestações
- ocorrências
- histórico de acessos
- convites QR / avisar portaria
- manutenção
- pagamentos
- perfil / configurações / notificações

## Estrutura
- `public/` — entrypoint web
- `src/Core/` — bootstrap, router, response, request, auth helpers
- `src/Controllers/Web/` — telas server-side
- `src/Controllers/Api/` — endpoints JSON
- `database/schema.sql` — schema inicial MySQL
- `database/seed.sql` — dados de desenvolvimento
- `docs/print/` — referências visuais fornecidas pelo usuário
- `docs/ui-reference-summary.md` — resumo funcional dos prints

## Requisitos locais
- PHP 8.2+
- MySQL 8+

## Como rodar
1. Copie o arquivo de ambiente:
   ```bash
   cp .env.example .env
   ```
2. Ajuste as credenciais MySQL no `.env`.
3. Crie o banco:
   ```sql
   CREATE DATABASE sistema_sindico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Importe o schema e os seeds:
   ```bash
   mysql -u root -p sistema_sindico < database/schema.sql
   mysql -u root -p sistema_sindico < database/seed.sql
   ```
5. Suba o servidor local:
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```
6. Acesse:
   - Web: `http://127.0.0.1:8000/`
   - API health: `http://127.0.0.1:8000/api/health`

## Endpoints iniciais
Exemplos de endpoints planejados/iniciais:
- `GET /api/health`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/condominiums`
- `GET /api/units`
- `GET /api/residents`
- `GET /api/visitors`
- `GET /api/providers`
- `GET /api/vehicles`
- `GET /api/notices`
- `GET /api/documents`
- `GET /api/deliveries`
- `GET /api/requests`
- `GET /api/occurrences`
- `GET /api/access-history`
- `GET /api/invitations`
- `GET /api/settings`
- `GET /api/profile`

## Observações
- O visual atual é scaffold. A implementação final das telas seguirá os prints em `docs/print/`.
- A autenticação real, upload de arquivos, QR Code real, regras de permissão e integração facial ainda serão implementados.
- O código e os commits seguem inglês; a interface pode usar PT-BR.
