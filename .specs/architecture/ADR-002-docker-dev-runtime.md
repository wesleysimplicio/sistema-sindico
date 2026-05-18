# ADR-002: `Adotamos Docker Compose como runtime oficial de onboarding local`

---

## Status

Aceito

---

## Data

2026-05-18

---

## Autores

- Wesley Simplicio

---

## Contexto

O `sistema-sindico` hoje roda bem no alvo principal de deploy (`HostGator` shared hosting), mas o onboarding local exige PHP 8.2, `pdo_mysql` e MySQL 8 configurados manualmente. Isso aumenta o custo para novos colaboradores e dificulta um smoke previsível no CI quando queremos validar o runtime completo em vez de apenas lintar arquivos PHP.

- O runtime de produção atual segue sendo LAMP compartilhado via FTP.
- A issue `#91` pede um caminho oficial com `Dockerfile` multi-stage, `docker-compose` e smoke no CI.
- O projeto ainda não usa `composer.json`, então qualquer stage de Composer precisa ser compatível com um repositório sem dependências Composer no momento.

---

## Decisão

Adotamos `Docker Compose` como runtime oficial de onboarding e smoke local, mantendo o fluxo HostGator como deploy canônico do v1.x.

- Escopo: `Dockerfile`, `docker-compose.yml`, `.dockerignore`, init do MySQL com `schema.sql` + `seed.sql`, e um job `docker-smoke` no CI.
- O container de app usa `php:8.2-apache` com `pdo_mysql` e docroot em `public/`.
- O container de banco usa `mysql:8.0`, volume persistente e aplica schema/seed apenas quando o volume está vazio.
- O stage de Composer permanece no `Dockerfile` para ser futuro-proof, mas faz no-op quando `composer.json` ainda não existe.
- Deploy produtivo continua sendo `deploy-hostgator.yml`; Docker não substitui esse pipeline nesta fase.

---

## Consequências

### Positivas (+)

- Onboarding local passa a depender só de `docker compose up --build`.
- O CI ganha uma validação do runtime completo, incluindo boot do Apache, variáveis de ambiente e seed inicial.
- A aplicação fica mais portátil se o hosting sair de HostGator no futuro.

### Negativas (-)

- A manutenção do repo ganha mais uma superfície de configuração (`Dockerfile`, Compose e smoke job).
- O setup Docker pode ficar desatualizado se o runtime HostGator evoluir e ninguém mantiver ambos alinhados.
- A primeira subida local pode ser mais lenta por causa do pull das imagens base.

### Neutras / observações

- O banco Docker expõe `3307` no host para evitar conflito com um MySQL local em `3306`.
- Como o projeto ainda não usa Composer, o stage de Composer é condicional e hoje não instala dependências reais.

---

## Alternativas consideradas

### Alternativa A — Manter apenas setup manual local

- Continuar documentando PHP + MySQL instalados na máquina.
- Foi descartada porque não atende a issue `#91` e mantém alto atrito para onboarding e smoke reproduzível.

### Alternativa B — Migrar o deploy oficial inteiro para Docker agora

- Fazer do Docker o caminho principal também para produção.
- Foi descartada porque o deploy canônico atual é HostGator shared hosting e a issue explicitamente não pede trocar esse alvo no v1.x.

---

## Critério de revisão

- Revisar se o deploy oficial sair de HostGator para um alvo container-native.
- Revisar se o repo passar a depender de Composer, para endurecer o stage de build e cache.
- Revisar se o smoke Docker no CI começar a falhar por drift entre o runtime container e o runtime produtivo.

---

## Links

- Issue / task: <https://github.com/wesleysimplicio/sistema-sindico/issues/91>
- PR de implementação: commit direto em `main`
- Documentos relacionados: [DESIGN](./DESIGN.md), [PATTERNS](./PATTERNS.md)
- ADRs relacionados: [ADR-001](./ADR-001-example.md)
