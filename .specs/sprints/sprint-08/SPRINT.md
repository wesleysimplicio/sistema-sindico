---
sprint: sprint-08
status: todo
start: 2026-XX-XX
end: 2026-XX-XX
owner: Wesley Simplicio
---

# Sprint 08 — v1.1 hardening pós-release

## Objetivo

Endurecer o sistema pós-v1.0.0 com provedor real de email/SMS, infra container, unit tests formais, métricas de adoção e rate limit escalável. **Sem features novas.**

## Datas

- **Início:** 2026-XX-XX
- **Fim previsto:** 2026-XX-XX
- **Demo/review:** 2026-XX-XX
- **Retrospectiva:** 2026-XX-XX

## Deliverables

1. **Email/SMS real** — `AuthRecoveryController::forgotPassword` chama sender real com driver swappable; TODO em `:55` removido.
2. **Docker stack** — `Dockerfile` + `docker-compose.yml` boot < 30s respondendo `/api/health`.
3. **PHPUnit/Pest** — runner unit configurado em `tests/unit/` com primeiros testes de `src/Core/*` (Jwt, Totp, PasswordPolicy, StoragePath, RateLimit) e novo job CI.
4. **Métricas de adoção** — `api_tokens.last_used_at` atualizado por request; endpoint `GET /api/admin/metrics/adoption` com p50/p95 de tempo de registro de visita.
5. **Rate limit escalável** — interface `RateLimitStore` com drivers `mysql` e `redis`, selecionado via `.env`.

## Tasks da sprint

| Issue | Status | Owner             |
| ----- | ------ | ----------------- |
| #90   | todo   | @wesleysimplicio  |
| #91   | done   | @wesleysimplicio  |
| #92   | todo   | @wesleysimplicio  |
| #93   | done   | @wesleysimplicio  |
| #94   | todo   | @wesleysimplicio  |

## Riscos

- **Provedor email transacional custa** — definir orçamento ou tier free (SendGrid 100/dia, Mailgun 5k/mês trial).
- **Redis na HostGator** — shared hosting pode não oferecer; ADR pode forçar postergar ou trocar de host.
- **PHPUnit é dependência nova** — passa por aprovação humana conforme regra "sem dependência sem perguntar" do AGENTS.md.
- **Migration `api_tokens.last_used_at`** — adicionar índice se o UPDATE virar gargalo.

## Dependências

- ADR de provedor de email/SMS (#90).
- ADR de driver de rate limit (#94).
- ADR de framework de unit test (#92).
- Decisão sobre Docker como alternativa de deploy (#91).

## Critérios de pronto da sprint

- [ ] Todas as 5 stories `closed` como `completed`.
- [ ] VERSION bumped para `1.1.0` com tag `v1.1.0`.
- [ ] `CHANGELOG.md` com seção `## 1.1.0`.
- [ ] ADRs criadas e em status `accepted`.
- [ ] CI verde com novos jobs (`docker-smoke`, `unit-phpunit`).
- [ ] BACKLOG.md migra as 5 linhas para "Histórico — done".

## Notas de retrospectiva (preencher no fim)

- O que funcionou bem:
- O que travou:
- O que mudar na sprint-09:
