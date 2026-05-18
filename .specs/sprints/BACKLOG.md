# Backlog — sistema-sindico

Lista priorizada de tudo que precisa ser feito. Fonte da verdade de pendências do produto.

## Como usar este backlog

- Cada linha é um item rastreável que vira uma `task.md` quando entra em sprint.
- Prioridades:
  - **P0** — bloqueador, sem isso o produto não fecha v1.
  - **P1** — importante, planejado pra próximas 1-2 sprints.
  - **P2** — desejável, fica no radar mas pode esperar.
- Status: `todo`, `doing`, `done`.
- Ordenação: P0 primeiro, P1, P2; dentro do mesmo P, por sprint alvo.

## Regras de manutenção

- Toda nova ideia entra como P2 até alguém defender priorizar.
- Itens `done` ficam por uma sprint e depois são arquivados em `BACKLOG-archive.md`.
- Item parado 2 sprints como `todo`: reavalia (ainda faz sentido?) ou remove.
- Quem altera prioridade ou move pra `doing` atualiza a tabela no mesmo PR.

---

## Sprint 8 — v1.1 hardening pós-release (origem: GitHub Issues #90-#95)

Endurecer o sistema pós-v1.0.0 com provedor real de email/SMS, infra container, unit tests formais, métricas de adoção e rate limit escalável. **Sem features novas.**

| #  | Issue | Título                                                                  | Prioridade | Sprint alvo | Status | Origem |
|----|-------|-------------------------------------------------------------------------|------------|-------------|--------|--------|
| 1  | #95   | [EPIC] Sprint 8 — v1.1 hardening pós-release                            | P1         | sprint-08   | todo   | gh issue #95 |
| 2  | #90   | [S8-01] Integrar provedor real de email/SMS para reset de senha         | P1         | sprint-08   | todo   | gh issue #90 |
| 3  | #92   | [S8-03] Adotar PHPUnit/Pest para unit tests do core PHP                 | P2         | sprint-08   | todo   | gh issue #92 |
| 4  | #94   | [S8-05] Cache distribuído (Redis) para rate limit                       | P2         | sprint-08   | todo   | gh issue #94 |

---

## Próximas decisões pendentes

- **#90** — Provedor email/SMS transacional (depende de ADR de infra externa). Trial gratuito: SendGrid 100/dia, Mailgun 5k/mês trial.
- **#91** — Estratégia oficial de container: manter HostGator-only ou abrir caminho pra Docker em outro provedor?
- **#92** — Adotar PHPUnit ou Pest? Recomendação inicial: PHPUnit (mais estável, sem nova DSL).
- **#94** — HostGator oferece Redis? Se não, ADR define adiar ou trocar de host.

---

## Histórico — done

### Sprint 8 — v1.1 hardening (em andamento)

| Issue | Título                                                                | Concluído em |
| ----- | --------------------------------------------------------------------- | ------------ |
| #91   | [S8-02] Adicionar Dockerfile + docker-compose oficiais                | 2026-05-18   |
| #93   | [S8-04] Métricas de adoção: api_tokens.last_used_at e tempo de visita | 2026-05-18   |

### Sprint 7 — Polish & release (v1.0.0, 2026-05-04)

| Issue | Título                                       | Concluído em |
| ----- | -------------------------------------------- | ------------ |
| #64   | [EPIC] E2E and hardening                     | 2026-05-04   |
| #65   | [S7-01] Playwright web admin happy path      | 2026-05-04   |
| #66   | [S7-02] Postman/Newman API regression        | 2026-05-04   |
| #67   | [S7-03] security review pass                 | 2026-05-04   |
| #68   | [S7-04] performance pass                     | 2026-05-04   |
| #69   | [S7-05] README + ER diagram                  | 2026-05-04   |
| #70   | [S7-06] tag v1.0.0 + release notes           | 2026-05-04   |

### Sprints anteriores

| Sprint     | Issues fechadas | Versão  | Concluído em |
| ---------- | --------------- | ------- | ------------ |
| sprint-00  | bootstrap       | -       | 2026-05-07   |
| sprint-01  | #1-#11          | v0.5.0  | 2026-05-04   |
| sprint-02  | #12-#22         | v0.6.0  | 2026-05-04   |
| sprint-03  | #23-#31         | v0.7.0  | 2026-05-04   |
| sprint-04  | #32-#42         | v0.8.0  | 2026-05-04   |
| sprint-05  | #43-#53         | v0.9.0  | 2026-05-04   |
| sprint-06  | #54-#63         | v0.10.0 | 2026-05-04   |
| sprint-07  | #64-#70         | v1.0.0  | 2026-05-04   |

---

## Itens descartados ou movidos pra fora

- Nenhum item descartado ainda.

---

## Como atualizar este arquivo

1. Nova issue no GitHub com label `sprint:N` entra na seção da sprint correspondente.
2. TODO/FIXME novo no código (com link para issue) entra como nova linha na sprint apropriada.
3. Após `gh issue close`, mover linha para "Histórico — done" (com data) no mesmo PR.
4. Rodar `gh issue list --state open --label sprint:N` para conferir antes de cada review de sprint.
