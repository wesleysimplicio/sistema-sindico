# ADR-003: `Mantemos MySQL como driver default e abrimos Redis opcional para rate limit`

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

O rate limit atual usa a tabela `rate_limits` no MySQL principal. Isso funciona e nao exige infra extra, mas cada hit faz round-trip no banco transacional. A issue `#94` pede um caminho escalavel com Redis sem quebrar o comportamento atual dos clientes.

Ao mesmo tempo, o deploy canônico do v1.x continua em HostGator shared hosting. Nas docs oficiais atuais da HostGator para shared hosting, a plataforma segue com **sem root access** e com **limited custom installations**, o que torna improvavel hospedar um Redis local nesse alvo sem trocar de plano ou depender de um Redis externo gerenciado. Nao encontrei documentacao oficial da HostGator shared anunciando Redis nativo no plano atual, entao essa parte foi tratada como restricao de infraestrutura, nao como premissa de produto.

- Shared hosting docs: <https://www.hostgator.com/help/article/what-is-shared-hosting>
- Shared hosting getting started: <https://www.hostgator.com/help/article/shared-hosting-getting-started>

---

## Decisao

Adotamos uma interface `RateLimitStore` com dois drivers:

- `mysql` como default e caminho oficial para HostGator no v1.x;
- `redis` como opcional para Docker/local e para futuros ambientes que possam expor um Redis gerenciado.

Detalhes:

- `RATE_LIMIT_DRIVER=mysql|redis` seleciona o store em runtime.
- `REDIS_URL=redis://...` configura o driver Redis.
- O middleware preserva o mesmo contrato externo de headers e erro (`X-RateLimit-Limit`, `X-RateLimit-Remaining`, `Retry-After`, `429 rate_limited`).
- O driver Redis usa `INCR` + `EXPIRE` via script atomico, sem adicionar dependencia Composer no app.
- O switch produtivo para Redis em HostGator fica **postergado** ate existir Redis suportado pelo alvo ou migracao de hosting.

---

## Consequencias

### Positivas (+)

- O app ganha um caminho escalavel para ambientes que tenham Redis.
- O deploy atual nao quebra, porque `mysql` segue default.
- A mudanca reduz acoplamento do middleware ao MySQL e facilita evolucao futura.

### Negativas (-)

- O comportamento em Redis passa a depender de conectividade com um servico externo adicional.
- A base de codigo ganha mais uma superficie de configuracao e validacao.
- Em HostGator shared, Redis continua indisponivel como default pratico do v1.x.

### Neutras / observacoes

- A validacao automatizada cobre `429` via Newman nos drivers `mysql` e `redis`.
- O Docker local ganhou um servico Redis opcional via profile para smoke de desenvolvimento.

---

## Alternativas consideradas

### Alternativa A — Permanecer so com MySQL

- Menor complexidade operacional.
- Rejeitada porque nao atende a issue `#94` nem abre caminho para ambientes com maior volume.

### Alternativa B — Mudar o default para Redis agora

- Melhor desempenho em ambientes preparados.
- Rejeitada porque o alvo oficial atual (HostGator shared) nao e um encaixe natural para Redis no v1.x.

### Alternativa C — Migrar hosting antes de implementar

- Resolveria o gargalo de infra de uma vez.
- Rejeitada porque a issue pede primeiro o suporte no app, e a troca de hosting e um movimento maior que nao cabe nesta story.

---

## Criterio de revisao

- Revisar quando o deploy oficial sair de HostGator shared.
- Revisar se um Redis gerenciado passar a ser requisito real de producao.
- Revisar se o contrato dos headers de rate limit mudar para clientes mobile/web.

---

## Links

- Issue / task: <https://github.com/wesleysimplicio/sistema-sindico/issues/94>
- Documentos relacionados: [DESIGN](./DESIGN.md), [PATTERNS](./PATTERNS.md)
- ADRs relacionadas: [ADR-002](./ADR-002-docker-dev-runtime.md)
