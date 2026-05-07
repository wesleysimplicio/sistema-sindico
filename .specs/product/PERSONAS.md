# PERSONAS — sistema-sindico

Quem usa sistema-sindico. Cada persona é um arquétipo: representa um grupo real de pessoas com objetivos, frustrações e contexto comuns. Decisões de produto e features se justificam contra estas personas, não contra opiniões.

> Regra: se uma feature não move a agulha de pelo menos uma persona aqui, ela não entra no backlog.

Os roles são inferidos diretamente do ENUM `users.role` em `database/schema.sql` e dos middlewares `AdminOnly`/`ApiAuth` em `src/Middleware/`. Os usuários default vêm de `database/seed.sql` (publicados também no `README.md`).

---

## 1. Sindico (`role = sindico`) — persona primária

**Quem é:** morador eleito (ou contratado) que responde pelo condomínio. Opera o painel web em desktop, geralmente fora de horário comercial.

**O que precisa fazer no sistema:**
- Publicar avisos (`POST /api/notices`) e fixar os críticos.
- Acompanhar e responder chamados de manutenção (`/maintenance`), atribuir a um responsável, mudar status.
- Aprovar reservas de áreas comuns que tenham `requires_approval = 1`.
- Marcar pagamentos como pagos (`PATCH /api/payments/{id}/pay`), exportar inadimplentes.
- Cadastrar moradores e gerar `login_invitation` para o app.
- Conferir `audit_logs` quando algo "some".

**Frustrações que o produto resolve:**
- Síndico hoje gerencia tudo no WhatsApp e perde rastro de decisão.
- Cobrança manual dá margem a esquecimento e retrabalho.
- Não consegue auditar quem aprovou o quê.

**Acessos:** policies `AdminOnly` (web) + JWT (`role=sindico`) na API. Pode escalar para qualquer endpoint de admin do seu condomínio.

**Seed default:** `sindico@sistemasindico.local` / `senha123`.

---

## 2. Morador (`role = morador`) — persona secundária

**Quem é:** residente da unidade (titular, locatário ou dependente com acesso). Usa primariamente o app móvel; entra no painel web só excepcionalmente.

**O que precisa fazer no sistema:**
- Ver avisos, marcar como lidos (`POST /api/notices/{id}/read`), receber contagem de não-lidos.
- Acompanhar boletos/PIX da sua unidade (`/api/payments/mine`), baixar comprovante.
- Abrir chamado de manutenção e acompanhar comentários.
- Reservar área comum (`POST /api/bookings`) com checagem de conflito.
- Liberar visita pré-cadastrada com QR (`POST /api/visitors`) e gerenciar convites recorrentes (`/api/invitations`).
- Atualizar perfil e senha (`PATCH /api/me`, `PATCH /api/me/password`).
- Cadastrar veículos da unidade e prestadores recorrentes.

**Frustrações que o produto resolve:**
- Não saber quanto deve nem o que vence quando.
- Visitante chegar e portaria não ter registro.
- Reservar churrasqueira e descobrir conflito no dia.

**Acessos:** JWT (`role=morador`). Bloqueado de tudo que tenha `AdminOnly`. Visa apenas dados da própria unidade (filtros `mine`).

**Seed default:** `morador@sistemasindico.local` / `senha123`.

---

## 3. Porteiro (`role = porteiro`) — persona operacional

**Quem é:** funcionário da portaria 24h. Usa o app móvel ou um terminal fixo na guarita.

**O que precisa fazer no sistema:**
- Registrar encomendas recebidas (`POST /api/deliveries`) e dar baixa quando o morador retira (`PATCH /api/deliveries/{id}/withdraw`).
- Validar visita por QR (`GET /api/visitors/qr/{token}`), fazer check-in (`POST /api/visitors/{id}/check-in`) e check-out.
- Anotar ocorrências de turno em `porter_notes`.
- Acionar portão remotamente (`gate_triggers`) — deixa rastro em `gate_trigger_logs`.
- Reagir ao webhook `/api/webhooks/access-event` (HMAC) quando há leitor automático.

**Frustrações que o produto resolve:**
- Caderno de portaria some/queima/molha; é ilegível para auditoria.
- Visitante não-cadastrado obriga ligação ao morador (à noite).
- Encomenda extraviada não tem prova de quem retirou.

**Acessos:** JWT (`role=porteiro`). Endpoints específicos de portaria (visitors, deliveries, porter-notes, gate triggers, access logs). NÃO acessa pagamentos, manutenção administrativa, configurações.

**Seed default:** `porteiro@sistemasindico.local` / `senha123`.

---

## 4. Admin (`role = admin`) — persona administrativa

**Quem é:** superusuário da plataforma — operador da administradora ou time interno. Pode atender vários condomínios.

**O que precisa fazer no sistema:**
- Criar/configurar `condominium`, cadastrar `units`.
- Atribuir `memberships` para que síndicos e moradores acessem condomínios específicos.
- Disparar `login_invitations` em massa.
- Auditar tudo: `audit_logs`, `access_logs`, sessões ativas (`api_tokens`).
- Cuidar de incidentes operacionais e fazer suporte direto via canal `suporte` em `messages`.

**Frustrações que o produto resolve:**
- Operar 5 condomínios em 5 sistemas diferentes.
- Sem trilha de auditoria não tem como provar conformidade.
- Provisionamento de novo condomínio leva semanas.

**Acessos:** super-policy. `AdminOnly` no painel web cobre `admin` E `sindico`; o que diferencia o admin é poder operar fora do escopo de um único `condominium_id` (ele troca de tenant via `POST /api/memberships/select`).

**Seed default:** `admin@sistemasindico.local` / `senha123`.

---

## Quem NÃO é persona

- **Visitante avulso** — entra via QR, não tem conta.
- **Prestador de serviço** — cadastrado em `contractors`, não tem login (a menos que o síndico crie um `login_invitation` pra ele).
- **Convidado de evento** — registrado em `invitation_guests`, sem credencial.
- **Sistema externo (CFTV, leitor de portão)** — autentica por HMAC no webhook, não é "persona" no sentido de UX.

---

## Mapeamento role x UI

| Role | Painel web (`/`) | App mobile (API JWT) | Endpoints `AdminOnly` |
|---|---|---|---|
| admin | Sim, completo | Sim | Sim (todos os condomínios via membership switch) |
| sindico | Sim, completo | Sim | Sim (somente seu condomínio) |
| morador | Não (bloqueado por `AdminOnly`) | Sim, escopo `mine` | Não |
| porteiro | Não | Sim, endpoints de portaria | Não |
