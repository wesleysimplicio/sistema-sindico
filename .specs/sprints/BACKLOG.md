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

## Sprint 7 — Polish and release (origem: GitHub Issues #64-70)

| #  | Issue | Título                                       | Prioridade | Sprint alvo | Status | Origem |
|----|-------|----------------------------------------------|------------|-------------|--------|--------|
| 1  | #64   | [EPIC] E2E and hardening                     | P1         | sprint-07   | todo   | gh issue #64 |
| 2  | #67   | [S7-03] security review pass                 | P0         | sprint-07   | todo   | gh issue #67 |
| 3  | #65   | [S7-01] Playwright web admin happy path      | P1         | sprint-07   | todo   | gh issue #65 |
| 4  | #66   | [S7-02] Postman/Newman API regression        | P1         | sprint-07   | todo   | gh issue #66 |
| 5  | #68   | [S7-04] performance pass                     | P1         | sprint-07   | todo   | gh issue #68 |
| 6  | #69   | [S7-05] README + ER diagram                  | P2         | sprint-07   | todo   | gh issue #69 |
| 7  | #70   | [S7-06] tag v1.0.0 + release notes           | P2         | sprint-07   | todo   | gh issue #70 |

---

## Pendências detectadas no código (TODO/FIXME grep)

| #  | Item                                                                      | Prioridade | Sprint alvo | Status | Origem |
|----|---------------------------------------------------------------------------|------------|-------------|--------|--------|
| 8  | Integrar provedor real de email/SMS para reset de senha (escolher gateway, configurar credenciais via `.env`, remover TODO) | P1 | sprint-07 | todo | `src/Controllers/Api/AuthRecoveryController.php:55` |

Detalhe do TODO em `AuthRecoveryController.php:55`:

```
// TODO: dispatch via mail/SMS sender — never log the plaintext code
```

Hoje o código de recuperação é gerado, persistido com hash SHA-256 em `password_resets` e devolvido apenas em ambiente local/dev. Sem provedor real, o fluxo de reset não fecha em produção end-to-end.

---

## Itens estruturais (não-issue) já mapeados

Itens identificados durante o bootstrap das specs que ainda não viraram issue. Promover para issue quando entrarem em sprint.

| #  | Item                                                                                          | Prioridade | Sprint alvo | Status |
|----|-----------------------------------------------------------------------------------------------|------------|-------------|--------|
| 9  | Adicionar Dockerfile/docker-compose oficiais (hoje não existem na raiz; runtime alvo HostGator) | P2         | backlog     | todo   |
| 10 | Avaliar PHPUnit/Pest para unit tests (hoje só Newman + Playwright cobrem regressão)           | P2         | backlog     | todo   |
| 11 | Métricas de adoção (`api_tokens.last_used_at`) e tempo médio de cadastro de visita (VISION.md) | P2         | backlog     | todo   |
| 12 | Cache distribuído (Redis) para rate limit quando MySQL virar gargalo                          | P2         | backlog     | todo   |

---

## Histórico recente (últimos done)

| #   | Título                                       | Sprint     | Concluído em |
| --- | -------------------------------------------- | ---------- | ------------ |
| 0   | Bootstrap de repositório + AGENTS.md         | sprint-00  | 2026-05-07   |

---

## Itens descartados ou movidos pra fora

- Nenhum item descartado ainda.

---

## Próximas decisões pendentes

- Provedor de email/SMS transacional para item #8 (depende de ADR de infra externa).
- Estratégia oficial de container (item #9): manter HostGator-only ou abrir caminho pra Docker em outro provedor?
- Criar PHPUnit/Pest no v1.1 (item #10) ou postergar até existir lógica de domínio com complexidade que justifique?

---

## Como atualizar este arquivo

1. Nova issue no GitHub com label `sprint:N` entra na seção da sprint correspondente.
2. TODO/FIXME novo no código (com link para issue) entra em "Pendências detectadas no código".
3. Após `gh issue close`, mover linha para "Histórico recente" (com data) no mesmo PR.
4. Rodar `gh issue list --state open --label sprint:N` para conferir antes de cada review de sprint.
