# ADR-005: `Adotamos PHPUnit como runner oficial de unit tests do core`

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

O projeto saiu de `v1.0.0` com boa cobertura funcional via Newman e Playwright, mas ainda sem unit tests formais sobre o core de seguranca e infraestrutura (`Jwt`, `Totp`, `PasswordPolicy`, `StoragePath`, `RateLimit`). A issue `#92` pede um runner canonico e os primeiros testes desses componentes.

- O projeto segue sem framework PHP.
- Precisamos de algo estavel, amplamente conhecido e com baixo atrito em CI.
- A CI atual ja detecta `composer.json` de forma opcional, entao a introducao de Composer dev-only cabe no fluxo atual.

---

## Decisão

Adotamos `PHPUnit` como runner oficial de unit tests do core, com Composer usado apenas para dependencias de desenvolvimento.

- Escopo: `composer.json`, `phpunit.xml.dist`, bootstrap simples e suite inicial em `tests/unit/`.
- O foco inicial fica em `src/Core` e `src/Middleware` criticos.
- `Pest` nao entra neste ciclo; se desejado no futuro, podera vir por cima do PHPUnit, nao no lugar.

---

## Consequências

### Positivas (+)

- O projeto ganha um runner padrao para testes pequenos e deterministas.
- Os componentes de seguranca passam a ter feedback mais rapido do que uma rodada Docker/Newman completa.
- A CI fica pronta para crescer cobertura por diff sem reinventar harness proprio.

### Negativas (-)

- Entramos com `composer.json` num repo que antes nao precisava dele.
- O pipeline ganha mais um job e mais tempo de execucao.
- O time precisa manter duas camadas de validacao: unit + integração/E2E.

### Neutras / observações

- Composer continua dev-only; o runtime em producao nao depende do autoloader do Composer para funcionar.
- O Docker runtime deve instalar dependencias sem `--dev` para nao carregar PHPUnit no container final.

---

## Alternativas consideradas

### Alternativa A — Pest

- DSL mais fluente sobre PHPUnit.
- Foi descartado neste ciclo por adicionar uma camada extra sem ganho estrutural para o objetivo minimo.

### Alternativa B — Continuar apenas com Newman/Playwright

- Mantem a stack atual enxuta.
- Foi descartado porque nao cobre bem regras internas do core nem falhas pequenas de seguranca.

### Alternativa C — Runner custom sem Composer

- Evitaria manifestos e vendor.
- Foi descartado por custo de manutencao e falta de ecossistema.

---

## Critério de revisão

- Revisar se a suite unit crescer e exigir fixtures/plugins que justifiquem Pest.
- Revisar se Composer passar a ser obrigatorio tambem para runtime, simplificando bootstrap.
- Revisar se a suite unit nao for mantida e virar custo sem retorno.

---

## Links

- Issue / task: <https://github.com/wesleysimplicio/sistema-sindico/issues/92>
- PR de implementação: commit direto em `main`
- Documentos relacionados: [DESIGN](./DESIGN.md), [PATTERNS](./PATTERNS.md)
- ADRs relacionados: [ADR-004](./ADR-004-email-provider-auth-recovery.md)
