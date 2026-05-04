# Sistema Síndico — Screens Analysis (mobile prints + admin web)

> Source material: `docs/print/sindico1.jpeg` … `docs/print/sindico64.jpeg` (1200×1600 portrait JPEGs).
> Reference app naming surfaced in print 60 native dialog: **Condfy**.
> Date: 2026-05-04. Version baseline: 0.3.1.

This document is the canonical product+technical reference extracted from all 64 reference screens. It maps every screen to UI components, REST endpoints, data tables, relationships, and flags gaps against the current schema. It feeds `docs/specs/SPRINT-BACKLOG.md` and the GitHub issues created from it.

---

## 1. Executive summary

- **Platforms shown**: native mobile app (resident + síndico + porteiro flows) and a small web admin shell already scaffolded.
- **Modules covered by prints**: 26 functional areas (auth, onboarding, dashboard, condominium picker, unit hub, residents, guests, contractors, vehicles, porter notes, login invitations, event invitations + guest list, notices, documents, maintenance, deliveries, visitors + QR, access logs, cameras, gate triggers, incidents, notifications feed + preferences, security settings, app version + permissions, contact/help).
- **Schema gap**: current DB has 14 functional tables. UI requires column extensions on 7 existing tables and ≈20 new tables.
- **Brand-new modules with zero current support**: cameras, gate_triggers, access_logs+readers, incidents, login_invitations.
- **Multi-tenant scoping**: every domain row carries `condominium_id`. Membership is many-to-many (one user can be resident in unit X and síndico in condo Y).
- **Auth model**: session-based web admin (already shipped), JWT HS256 mobile API (already shipped). Adds invitation-based onboarding and per-device push token registration.

---

## 2. Inventory — print → screen map

| File | Screen name | Module | Type | Platform |
|---|---|---|---|---|
| sindico1.jpeg | Splash / app open | auth | splash | mobile |
| sindico2.jpeg | Login (CPF + senha) | auth | form | mobile |
| sindico3.jpeg | Login — esqueci a senha | auth | form | mobile |
| sindico4.jpeg | Login — código de verificação | auth | form | mobile |
| sindico5.jpeg | Login — nova senha | auth | form | mobile |
| sindico6.jpeg | Onboarding — selecionar condomínio | condominiums | picker | mobile |
| sindico7.jpeg | Onboarding — selecionar unidade | units | picker | mobile |
| sindico8.jpeg | Home (morador) | dashboard | grid + FAB | mobile |
| sindico9.jpeg | Home (síndico) | dashboard | grid + FAB | mobile |
| sindico10.jpeg | Home (porteiro) | dashboard | grid + FAB | mobile |
| sindico11.jpeg | Drawer / menu lateral | navigation | drawer | mobile |
| sindico12.jpeg | Perfil do usuário | users | profile | mobile |
| sindico13.jpeg | Editar perfil | users | form | mobile |
| sindico14.jpeg | Trocar senha | users | form | mobile |
| sindico15.jpeg | Minha unidade — visão geral | units | hub | mobile |
| sindico16.jpeg | Minha unidade — moradores | residents | list | mobile |
| sindico17.jpeg | Minha unidade — adicionar morador | residents | form | mobile |
| sindico18.jpeg | Minha unidade — veículos | vehicles | list | mobile |
| sindico19.jpeg | Minha unidade — adicionar veículo | vehicles | form | mobile |
| sindico20.jpeg | Minha unidade — prestadores | contractors | list | mobile |
| sindico21.jpeg | Minha unidade — adicionar prestador | contractors | form | mobile |
| sindico22.jpeg | Visitantes — lista | visitors | list | mobile |
| sindico23.jpeg | Visitantes — novo visitante | visitors | form | mobile |
| sindico24.jpeg | Visitantes — QR code de liberação | visitors | qr | mobile |
| sindico25.jpeg | Visitantes — histórico | visitors | list | mobile |
| sindico26.jpeg | Convites de evento — lista | invitations | list | mobile |
| sindico27.jpeg | Convites de evento — criar convite | invitations | form | mobile |
| sindico28.jpeg | Convites de evento — lista de convidados | invitations | list | mobile |
| sindico29.jpeg | Convites de evento — adicionar convidado | invitations | form | mobile |
| sindico30.jpeg | Convites de login (síndico) | login_invitations | list | mobile |
| sindico31.jpeg | Convites de login — novo | login_invitations | form | mobile |
| sindico32.jpeg | Comunicados — lista (morador) | notices | list | mobile |
| sindico33.jpeg | Comunicado — detalhe | notices | detail | mobile |
| sindico34.jpeg | Comunicados — criar (síndico) | notices | form | mobile |
| sindico35.jpeg | Comunicados — destinatários | notices | picker | mobile |
| sindico36.jpeg | Documentos — lista por pasta | documents | list | mobile |
| sindico37.jpeg | Documento — visualizador | documents | detail | mobile |
| sindico38.jpeg | Documentos — upload | documents | form | mobile |
| sindico39.jpeg | Manutenção — lista (morador) | maintenance | list | mobile |
| sindico40.jpeg | Manutenção — abrir chamado | maintenance | form | mobile |
| sindico41.jpeg | Manutenção — detalhe + comentários | maintenance | detail | mobile |
| sindico42.jpeg | Manutenção — fila do síndico | maintenance | list | mobile |
| sindico43.jpeg | Encomendas — lista (porteiro/morador) | deliveries | list | mobile |
| sindico44.jpeg | Encomendas — registrar | deliveries | form | mobile |
| sindico45.jpeg | Encomendas — retirada | deliveries | action | mobile |
| sindico46.jpeg | Acessos — log unificado | access_logs | list | mobile |
| sindico47.jpeg | Acessos — detalhe + foto | access_logs | detail | mobile |
| sindico48.jpeg | Câmeras — grade ao vivo | cameras | grid | mobile |
| sindico49.jpeg | Câmera — visualização única | cameras | detail | mobile |
| sindico50.jpeg | Acionamentos — lista (portões/luzes) | gate_triggers | list | mobile |
| sindico51.jpeg | Acionamentos — confirmação | gate_triggers | action | mobile |
| sindico52.jpeg | Ocorrências — lista | incidents | list | mobile |
| sindico53.jpeg | Ocorrências — abrir | incidents | form | mobile |
| sindico54.jpeg | Ocorrências — detalhe | incidents | detail | mobile |
| sindico55.jpeg | Notificações — feed | notifications | list | mobile |
| sindico56.jpeg | Notificações — preferências | notifications | settings | mobile |
| sindico57.jpeg | Configurações — segurança | settings | form | mobile |
| sindico58.jpeg | Configurações — versão do app | settings | info | mobile |
| sindico59.jpeg | Configurações — permissões | settings | info | mobile |
| sindico60.jpeg | Permissão nativa (Condfy quer acessar…) | settings | dialog | mobile |
| sindico61.jpeg | Fale conosco | contact | form | mobile |
| sindico62.jpeg | Sobre | settings | info | mobile |
| sindico63.jpeg | Logout / confirmação | auth | dialog | mobile |
| sindico64.jpeg | Erro / sem conexão | system | empty state | mobile |

---

## 3. Functional modules vs current schema

| # | Module | Current support | Gap | Target sprint |
|---|---|---|---|---|
| 1 | Auth (login, recuperação, código, nova senha) | Web session + JWT done | Recovery flow + 6-digit code, password history, password rules | S1 |
| 2 | Onboarding (escolher condomínio + unidade) | none | memberships table, picker endpoints | S1 |
| 3 | Dashboard por papel | partial (admin web) | Mobile dashboards (morador/sindico/porteiro), counters, FAB shortcuts | S1 |
| 4 | Perfil do usuário | partial | edit profile, change password, avatar upload | S1 |
| 5 | Unit hub (visão geral) | unit table only | aggregator endpoint, attached people/vehicles/contractors counters | S2 |
| 6 | Residents (moradores) | partial via users | residents table linking user↔unit↔role with per-unit role | S2 |
| 7 | Vehicles | none | vehicles table | S2 |
| 8 | Contractors (prestadores) | none | contractors table + access window | S2 |
| 9 | Porter notes (recados de portaria) | none | porter_notes table | S2 |
| 10 | Login invitations (síndico cria acesso) | none | login_invitations table + token email/SMS flow | S3 |
| 11 | Event invitations + guests | none | invitations + invitation_guests | S3 |
| 12 | Visitors + QR | partial table | QR token, photo, expected_at, status flow, face profile | S3 |
| 13 | Notices | partial | attachments, recipients filter, read receipts | S4 |
| 14 | Documents | partial | folders, mime/size, signed URL | S4 |
| 15 | Maintenance | partial | attachments, comments, status flow + assignees | S4 |
| 16 | Deliveries | partial | photos, locker location, withdrawal record | S4 |
| 17 | Access logs | none | access_readers + access_logs (face, RFID, QR) | S5 |
| 18 | Cameras | none | cameras table + HLS proxy | S5 |
| 19 | Gate triggers | none | gate_triggers + gate_trigger_logs | S5 |
| 20 | Incidents (ocorrências) | none | incident_types + incidents | S5 |
| 21 | Notifications feed | partial (preferences only) | notifications table + FCM device tokens | S6 |
| 22 | Notification preferences | partial | granular per channel × event | S6 |
| 23 | Security settings | none | password_history, 2fa flag, session list | S6 |
| 24 | App version / permissions screens | none | app_versions, feature_flags, permissions copy | S6 |
| 25 | Contact / Fale conosco | none | contact_messages | S6 |
| 26 | Empty/error states + offline | none | client cache + retry; backend health/version | S7 |

---

## 4. Consolidated REST surface

All endpoints are JSON, prefix `/api`, scoped by JWT (header `Authorization: Bearer <token>`). Response envelope: `{success, data, meta}` (already in place for current controllers).

### Auth & onboarding

```
POST   /api/auth/login                          { document, password }
POST   /api/auth/forgot-password                { document }
POST   /api/auth/verify-code                    { document, code }
POST   /api/auth/reset-password                 { reset_token, new_password }
POST   /api/auth/refresh                        { refresh_token }
POST   /api/auth/logout
GET    /api/me
PATCH  /api/me                                  { name, phone, avatar_url }
PATCH  /api/me/password                         { current_password, new_password }

GET    /api/memberships                         # condos this user belongs to
GET    /api/memberships/{condoId}/units         # units this user is tied to inside condo
POST   /api/memberships/select                  { condominium_id, unit_id? } -> issues scoped token
```

### Unit hub

```
GET    /api/condominium/{condoId}/units/{unitId}/overview
GET    /api/condominium/{condoId}/units/{unitId}/residents
POST   /api/condominium/{condoId}/units/{unitId}/residents
DELETE /api/condominium/{condoId}/units/{unitId}/residents/{residentId}

GET    /api/condominium/{condoId}/units/{unitId}/vehicles
POST   /api/condominium/{condoId}/units/{unitId}/vehicles
PATCH  /api/condominium/{condoId}/units/{unitId}/vehicles/{vehicleId}
DELETE /api/condominium/{condoId}/units/{unitId}/vehicles/{vehicleId}

GET    /api/condominium/{condoId}/units/{unitId}/contractors
POST   /api/condominium/{condoId}/units/{unitId}/contractors
PATCH  /api/condominium/{condoId}/units/{unitId}/contractors/{contractorId}
DELETE /api/condominium/{condoId}/units/{unitId}/contractors/{contractorId}

GET    /api/condominium/{condoId}/units/{unitId}/porter-notes
POST   /api/condominium/{condoId}/units/{unitId}/porter-notes
```

### Visitors & invitations

```
GET    /api/visitors
POST   /api/visitors                            { name, document, expected_at, photo_url? }
GET    /api/visitors/{id}
GET    /api/visitors/{id}/qr                    -> short-lived QR token
POST   /api/visitors/{id}/check-in
POST   /api/visitors/{id}/check-out
GET    /api/visitors/history

GET    /api/invitations                         # event-style, with guest list
POST   /api/invitations
GET    /api/invitations/{id}
PATCH  /api/invitations/{id}
DELETE /api/invitations/{id}
GET    /api/invitations/{id}/guests
POST   /api/invitations/{id}/guests
DELETE /api/invitations/{id}/guests/{guestId}

# síndico-only: convidar pessoas para acessar o app
GET    /api/login-invitations
POST   /api/login-invitations                   { name, document, email, phone, role, unit_id? }
DELETE /api/login-invitations/{id}
POST   /api/login-invitations/{token}/accept    { password }
```

### Notices, documents, maintenance, deliveries

```
GET    /api/notices?folder=&unread=true
POST   /api/notices                             # síndico
GET    /api/notices/{id}
POST   /api/notices/{id}/read
GET    /api/notices/{id}/attachments
POST   /api/notices/{id}/attachments

GET    /api/documents?folder_id=
POST   /api/documents
GET    /api/documents/{id}/download             -> signed URL
DELETE /api/documents/{id}
GET    /api/documents/folders
POST   /api/documents/folders

GET    /api/maintenance
POST   /api/maintenance                         # morador abre chamado
GET    /api/maintenance/{id}
PATCH  /api/maintenance/{id}/status             # síndico transitions
POST   /api/maintenance/{id}/comments
GET    /api/maintenance/{id}/attachments
POST   /api/maintenance/{id}/attachments

GET    /api/deliveries
POST   /api/deliveries                          # porteiro registra
POST   /api/deliveries/{id}/withdraw            { withdrawn_by_user_id, signature_url? }
```

### Access, cameras, triggers, incidents

```
GET    /api/access-logs?from=&to=&unit_id=&type=
GET    /api/access-logs/{id}

GET    /api/cameras
GET    /api/cameras/{id}/stream                 -> HLS URL (signed)

GET    /api/gate-triggers
POST   /api/gate-triggers/{id}/fire             # produces gate_trigger_log row

GET    /api/incidents
POST   /api/incidents
GET    /api/incidents/{id}
PATCH  /api/incidents/{id}
GET    /api/incidents/types
```

### Notifications, settings, contact, system

```
GET    /api/notifications?unread=true
POST   /api/notifications/{id}/read
POST   /api/notifications/read-all

GET    /api/notification-preferences
PUT    /api/notification-preferences

GET    /api/settings/security
PATCH  /api/settings/security                   { two_factor_enabled }
GET    /api/settings/sessions
DELETE /api/settings/sessions/{id}

GET    /api/system/version                      # already exists as /api/health-style
GET    /api/system/permissions                  # static copy for native dialog screen
POST   /api/system/devices                      { fcm_token, platform }
DELETE /api/system/devices/{id}

POST   /api/contact                             { subject, message }
```

---

## 5. Database — additions and changes

### 5.1 Column ALTERs on existing tables

```sql
ALTER TABLE condominiums
  ADD COLUMN logo_url        VARCHAR(255) NULL,
  ADD COLUMN administradora_id BIGINT UNSIGNED NULL,
  ADD COLUMN timezone        VARCHAR(64)  NOT NULL DEFAULT 'America/Sao_Paulo';

ALTER TABLE units
  ADD COLUMN block_label     VARCHAR(32)  NULL,
  ADD COLUMN floor           VARCHAR(16)  NULL,
  ADD COLUMN parking_spots   TINYINT      NOT NULL DEFAULT 0;

ALTER TABLE users
  ADD COLUMN avatar_url      VARCHAR(255) NULL,
  ADD COLUMN phone           VARCHAR(32)  NULL,
  ADD COLUMN locale          VARCHAR(8)   NOT NULL DEFAULT 'pt-BR',
  ADD COLUMN last_login_at   DATETIME     NULL,
  ADD COLUMN password_changed_at DATETIME NULL;

ALTER TABLE visitors
  ADD COLUMN photo_url       VARCHAR(255) NULL,
  ADD COLUMN qr_token        VARCHAR(64)  NULL,
  ADD COLUMN qr_expires_at   DATETIME     NULL,
  ADD COLUMN status          ENUM('expected','arrived','denied','left','expired') NOT NULL DEFAULT 'expected',
  ADD UNIQUE KEY uq_visitors_qr (qr_token);

ALTER TABLE deliveries
  ADD COLUMN photo_url       VARCHAR(255) NULL,
  ADD COLUMN locker_label    VARCHAR(32)  NULL,
  ADD COLUMN withdrawn_at    DATETIME     NULL,
  ADD COLUMN withdrawn_by_user_id BIGINT UNSIGNED NULL;

ALTER TABLE notices
  ADD COLUMN scope           ENUM('all','block','unit','role') NOT NULL DEFAULT 'all',
  ADD COLUMN scope_value     VARCHAR(64)  NULL,
  ADD COLUMN published_at    DATETIME     NULL;

ALTER TABLE documents
  ADD COLUMN folder_id       BIGINT UNSIGNED NULL,
  ADD COLUMN mime_type       VARCHAR(64)  NULL,
  ADD COLUMN size_bytes      BIGINT UNSIGNED NULL;
```

### 5.2 New tables

```sql
-- 1. Multi-tenant memberships
CREATE TABLE memberships (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT UNSIGNED NOT NULL,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  role            ENUM('admin','sindico','morador','porteiro') NOT NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL,
  updated_at      DATETIME NOT NULL,
  UNIQUE KEY uq_membership (user_id, condominium_id, role),
  KEY idx_membership_condo (condominium_id),
  CONSTRAINT fk_mb_user  FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_mb_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id)
);

-- 2. Resident link per unit
CREATE TABLE residents (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED NOT NULL,
  user_id         BIGINT UNSIGNED NULL,
  full_name       VARCHAR(120) NOT NULL,
  document        VARCHAR(32)  NULL,
  birth_date      DATE         NULL,
  relationship    ENUM('owner','tenant','dependent','other') NOT NULL DEFAULT 'owner',
  is_responsible  TINYINT(1) NOT NULL DEFAULT 0,
  created_at      DATETIME NOT NULL,
  updated_at      DATETIME NOT NULL,
  KEY idx_res_unit (unit_id),
  CONSTRAINT fk_res_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id),
  CONSTRAINT fk_res_unit  FOREIGN KEY (unit_id) REFERENCES units(id),
  CONSTRAINT fk_res_user  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 3. Vehicles
CREATE TABLE vehicles (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED NOT NULL,
  resident_id     BIGINT UNSIGNED NULL,
  plate           VARCHAR(16)  NOT NULL,
  brand           VARCHAR(64)  NULL,
  model           VARCHAR(64)  NULL,
  color           VARCHAR(32)  NULL,
  vehicle_type    ENUM('car','motorcycle','bike','other') NOT NULL DEFAULT 'car',
  parking_spot    VARCHAR(16)  NULL,
  created_at      DATETIME NOT NULL,
  updated_at      DATETIME NOT NULL,
  UNIQUE KEY uq_vehicle_plate_condo (condominium_id, plate),
  CONSTRAINT fk_veh_unit  FOREIGN KEY (unit_id) REFERENCES units(id)
);

-- 4. Contractors / prestadores
CREATE TABLE contractors (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED NOT NULL,
  full_name       VARCHAR(120) NOT NULL,
  document        VARCHAR(32)  NULL,
  service_type    VARCHAR(64)  NULL,
  access_starts_at DATE NULL,
  access_ends_at   DATE NULL,
  status          ENUM('pending','approved','expired','revoked') NOT NULL DEFAULT 'pending',
  created_at      DATETIME NOT NULL,
  updated_at      DATETIME NOT NULL
);

-- 5. Porter notes
CREATE TABLE porter_notes (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED NULL,
  author_user_id  BIGINT UNSIGNED NOT NULL,
  body            TEXT NOT NULL,
  created_at      DATETIME NOT NULL
);

-- 6. Login invitations (síndico → futuro usuário)
CREATE TABLE login_invitations (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED NULL,
  email           VARCHAR(120) NULL,
  phone           VARCHAR(32)  NULL,
  full_name       VARCHAR(120) NOT NULL,
  document        VARCHAR(32)  NULL,
  role            ENUM('sindico','morador','porteiro') NOT NULL,
  token           VARCHAR(64)  NOT NULL,
  expires_at      DATETIME     NOT NULL,
  accepted_at     DATETIME     NULL,
  created_by_user_id BIGINT UNSIGNED NOT NULL,
  created_at      DATETIME NOT NULL,
  UNIQUE KEY uq_login_inv_token (token)
);

-- 7. Event invitations + guests
CREATE TABLE invitations (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED NOT NULL,
  host_user_id    BIGINT UNSIGNED NOT NULL,
  title           VARCHAR(120) NOT NULL,
  starts_at       DATETIME NOT NULL,
  ends_at         DATETIME NULL,
  notes           TEXT NULL,
  status          ENUM('draft','active','done','cancelled') NOT NULL DEFAULT 'active',
  created_at      DATETIME NOT NULL,
  updated_at      DATETIME NOT NULL
);

CREATE TABLE invitation_guests (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invitation_id   BIGINT UNSIGNED NOT NULL,
  full_name       VARCHAR(120) NOT NULL,
  document        VARCHAR(32) NULL,
  status          ENUM('expected','arrived','no_show') NOT NULL DEFAULT 'expected',
  CONSTRAINT fk_guest_inv FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
);

-- 8. Document folders + notice attachments + maintenance attachments
CREATE TABLE document_folders (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  parent_id       BIGINT UNSIGNED NULL,
  name            VARCHAR(120) NOT NULL,
  created_at      DATETIME NOT NULL
);

CREATE TABLE notice_attachments (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  notice_id   BIGINT UNSIGNED NOT NULL,
  file_url    VARCHAR(255) NOT NULL,
  mime_type   VARCHAR(64)  NULL,
  size_bytes  BIGINT UNSIGNED NULL,
  created_at  DATETIME NOT NULL
);

CREATE TABLE notice_reads (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  notice_id   BIGINT UNSIGNED NOT NULL,
  user_id     BIGINT UNSIGNED NOT NULL,
  read_at     DATETIME NOT NULL,
  UNIQUE KEY uq_notice_user (notice_id, user_id)
);

CREATE TABLE maintenance_attachments (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id  BIGINT UNSIGNED NOT NULL,
  file_url    VARCHAR(255) NOT NULL,
  uploaded_by_user_id BIGINT UNSIGNED NOT NULL,
  created_at  DATETIME NOT NULL
);

CREATE TABLE maintenance_comments (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  request_id  BIGINT UNSIGNED NOT NULL,
  author_user_id BIGINT UNSIGNED NOT NULL,
  body        TEXT NOT NULL,
  created_at  DATETIME NOT NULL
);

-- 9. Access control runtime
CREATE TABLE access_readers (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  label           VARCHAR(64) NOT NULL,
  location        VARCHAR(120) NULL,
  reader_type     ENUM('face','rfid','qr','pin','manual') NOT NULL,
  hardware_id     VARCHAR(64) NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE access_logs (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  reader_id       BIGINT UNSIGNED NULL,
  user_id         BIGINT UNSIGNED NULL,
  visitor_id      BIGINT UNSIGNED NULL,
  unit_id         BIGINT UNSIGNED NULL,
  direction       ENUM('in','out') NOT NULL,
  result          ENUM('granted','denied') NOT NULL,
  reason          VARCHAR(120) NULL,
  photo_url       VARCHAR(255) NULL,
  occurred_at     DATETIME NOT NULL,
  KEY idx_access_time (condominium_id, occurred_at)
);

CREATE TABLE cameras (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  label           VARCHAR(64) NOT NULL,
  location        VARCHAR(120) NULL,
  rtsp_url        VARCHAR(255) NOT NULL,
  hls_proxy_url   VARCHAR(255) NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE gate_triggers (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  label           VARCHAR(64) NOT NULL,
  device_endpoint VARCHAR(255) NOT NULL,
  trigger_type    ENUM('gate','door','light','siren','other') NOT NULL DEFAULT 'gate',
  is_active       TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE gate_trigger_logs (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  trigger_id      BIGINT UNSIGNED NOT NULL,
  user_id         BIGINT UNSIGNED NOT NULL,
  occurred_at     DATETIME NOT NULL,
  result          ENUM('ok','failed') NOT NULL,
  reason          VARCHAR(120) NULL
);

-- 10. Incidents
CREATE TABLE incident_types (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  label           VARCHAR(64) NOT NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE incidents (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  reporter_user_id BIGINT UNSIGNED NOT NULL,
  type_id         BIGINT UNSIGNED NULL,
  title           VARCHAR(120) NOT NULL,
  body            TEXT NOT NULL,
  status          ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  occurred_at     DATETIME NOT NULL,
  created_at      DATETIME NOT NULL,
  updated_at      DATETIME NOT NULL
);

-- 11. Notifications & devices
CREATE TABLE notifications (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT UNSIGNED NOT NULL,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  category        VARCHAR(48) NOT NULL,
  title           VARCHAR(120) NOT NULL,
  body            TEXT NULL,
  payload_json    JSON NULL,
  read_at         DATETIME NULL,
  created_at      DATETIME NOT NULL,
  KEY idx_notif_user_created (user_id, created_at)
);

CREATE TABLE user_devices (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT UNSIGNED NOT NULL,
  fcm_token       VARCHAR(255) NOT NULL,
  platform        ENUM('android','ios','web') NOT NULL,
  last_seen_at    DATETIME NULL,
  created_at      DATETIME NOT NULL,
  UNIQUE KEY uq_device_token (fcm_token)
);

-- 12. Security & meta
CREATE TABLE password_history (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT UNSIGNED NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  created_at      DATETIME NOT NULL
);

CREATE TABLE app_versions (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  platform        ENUM('android','ios','web') NOT NULL,
  version         VARCHAR(32) NOT NULL,
  is_required     TINYINT(1) NOT NULL DEFAULT 0,
  release_notes   TEXT NULL,
  released_at     DATETIME NOT NULL
);

CREATE TABLE feature_flags (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  flag_key        VARCHAR(64) NOT NULL,
  is_enabled      TINYINT(1) NOT NULL DEFAULT 0,
  rollout_percent TINYINT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_flag (flag_key)
);

CREATE TABLE administradoras (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name            VARCHAR(120) NOT NULL,
  cnpj            VARCHAR(20) NULL,
  contact_email   VARCHAR(120) NULL
);

CREATE TABLE contact_messages (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  user_id         BIGINT UNSIGNED NOT NULL,
  subject         VARCHAR(160) NOT NULL,
  body            TEXT NOT NULL,
  status          ENUM('new','read','answered') NOT NULL DEFAULT 'new',
  created_at      DATETIME NOT NULL
);
```

### 5.3 Relationship summary

- `users` ⇆ `memberships` ⇆ `condominiums` (M:N with role)
- `units` 1:N `residents`, 1:N `vehicles`, 1:N `contractors`, 1:N `invitations`, 1:N `porter_notes`
- `notices` 1:N `notice_attachments`, 1:N `notice_reads`, scope filters (`scope`, `scope_value`)
- `maintenance_requests` 1:N `maintenance_attachments`, 1:N `maintenance_comments`
- `visitors` 1:N `access_logs` (via `visitor_id`)
- `cameras`, `gate_triggers`, `access_readers` belong to `condominiums`; `gate_triggers` 1:N `gate_trigger_logs`
- `notifications` 1:1 user, fans out from notice/maintenance/delivery/access events
- `login_invitations` produces `users` + `memberships` on accept

---

## 6. Permissions matrix

| Action | admin | sindico | morador_responsavel | morador | porteiro |
|---|---|---|---|---|---|
| Manage condominium settings | ✓ | ✓ | – | – | – |
| Issue login invitations | ✓ | ✓ | – | – | – |
| Manage residents/vehicles/contractors of own unit | – | – | ✓ | partial | – |
| View unit hub | – | ✓ (any unit) | ✓ | ✓ | – |
| Create notices | ✓ | ✓ | – | – | – |
| Read notices | ✓ | ✓ | ✓ | ✓ | ✓ |
| Open maintenance ticket | – | – | ✓ | ✓ | – |
| Triage maintenance | ✓ | ✓ | – | – | – |
| Register delivery | – | – | – | – | ✓ |
| Withdraw delivery | – | – | ✓ | ✓ | – |
| Register visitor expectation | – | – | ✓ | ✓ | – |
| Visitor check-in/out | – | – | – | – | ✓ |
| View access logs | ✓ | ✓ | – | – | ✓ |
| View cameras | ✓ | ✓ | – | – | ✓ |
| Fire gate trigger | ✓ | ✓ | – | – | ✓ |
| Open incident | ✓ | ✓ | ✓ | ✓ | ✓ |
| Resolve incident | ✓ | ✓ | – | – | – |

---

## 7. Integrations

| Concern | Choice |
|---|---|
| Push notifications | Firebase Cloud Messaging (FCM); table `user_devices` |
| Transactional email | SMTP via env (HostGator) for v1, swap to SES/Mailgun later |
| SMS / OTP | Twilio or Zenvia abstraction `SmsGatewayInterface` |
| File storage | Local `public/storage` for v1; S3/R2 driver later (signed URLs) |
| RTSP camera proxy | MediaMTX (or go2rtc) external service, stores HLS URL in `cameras.hls_proxy_url` |
| Facial recognition | Out of scope for v1; design `face_profiles` table later when SDK is picked (Hikvision/Intelbras/Control iD) |
| Monitoring | structured JSON logs + simple `/api/health` envelope already in place |

---

## 8. Shared UI patterns

- Header: purple `#3B3B98`, condo logo on the left, bell + avatar on the right.
- Bottom nav: Home / Notifications / Profile.
- FAB speed-dial on home dashboards (síndico/porteiro): create notice, open trigger, open camera, register delivery.
- Empty state: illustration + short copy + primary CTA.
- Bottom-sheet modal for create/edit forms.
- Period grouping in lists ("Hoje", "Ontem", "Esta semana", date).
- Status badges color-coded: green=ok/granted, red=denied/cancelled, amber=pending.

---

## 9. Effort gap matrix

| Area | Effort | Notes |
|---|---|---|
| Auth recovery + memberships + dashboards | M | mostly endpoints + 3 mobile screens shared |
| Unit hub + people/vehicles/contractors/porter notes | L | 6 new tables + CRUD |
| Visitors expansion + invitations + login_invitations | L | QR token, email/SMS, accept flow |
| Notices + documents + maintenance + deliveries enrichment | M | additive (attachments/reads/comments) |
| Access logs + cameras + gate triggers + incidents | XL | 8 new tables, integrations, real-time |
| Notifications + settings + version + permissions + contact | M | 5 new tables, FCM glue |
| Polish + E2E + offline + empty/error | S | UX polish |

---

## 10. References

- Reference prints: `docs/print/sindico1.jpeg` … `docs/print/sindico64.jpeg`
- Existing schema: `database/schema.sql`
- Existing controllers: `src/Controllers/Api/*`
- Repositories: `src/Repositories/*`
- Auth core: `src/Core/Auth.php`, `src/Core/Jwt.php`, `src/Middleware/*`
- Roadmap: `docs/specs/SPRINT-BACKLOG.md`
- Changelog: `CHANGELOG.md`
- Version: `VERSION`
