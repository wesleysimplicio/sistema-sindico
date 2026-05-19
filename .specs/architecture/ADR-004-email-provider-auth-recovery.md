# ADR-004: `Adotamos Resend como provedor inicial de email transacional e adiamos SMS`

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

O fluxo de recuperacao de senha do `sistema-sindico` ja gera e persiste o codigo com hash em `password_resets`, mas ainda terminava em um TODO sem envio real. Isso impede o fechamento do fluxo em producao e deixa a story `#90` aberta, apesar de todo o restante do pipeline de auth recovery ja existir.

- O projeto nao usa framework nem SDKs pesados hoje.
- Precisamos de um provedor simples de integrar via HTTP, com tier inicial amigavel e sem expor o plaintext em logs.
- O schema atual de `users` tem `email` e `phone`, mas a jornada de recuperacao ja esta modelada sobretudo por email/documento e nao ha UX de SMS consolidada no app.

---

## Decisão

Adotamos `Resend` como provedor inicial de email transacional para recuperacao de senha e adiamos o envio por SMS para uma sprint futura.

- Escopo: novo `Mailer` com drivers trocaveis (`resend` e `log`), configurado por `.env`.
- Em `APP_ENV=local`, o driver `log` continua permitido para smoke/CI, mas o codigo plaintext so aparece na resposta debug local e nunca em log.
- Em producao, `MAIL_DRIVER=resend` e `MAIL_API_KEY` tornam-se obrigatorios para entrega real.
- SMS fica explicitamente postergado ate existir UX, budget e operacao clara para esse canal.

---

## Consequências

### Positivas (+)

- O reset de senha finalmente fecha ponta a ponta em producao sem depender de logs manuais.
- O driver `log` preserva smoke local/CI sem adicionar servico externo ao pipeline.
- A interface de transporte permite trocar de provedor depois sem reescrever o controller.

### Negativas (-)

- Email passa a depender de credencial externa e monitoramento operacional.
- Usuarios sem email valido continuam sem canal automatico de recuperacao ate SMS entrar.
- Resend adiciona lock-in leve de payload HTTP no primeiro release.

### Neutras / observações

- O endpoint continua respondendo com mensagem neutra para evitar enumeracao.
- A exibicao do codigo em `debug.code` fica restrita ao ambiente local.

---

## Alternativas consideradas

### Alternativa A — SendGrid/Mailgun

- Tambem resolveriam o envio por HTTP.
- Foram descartados neste ciclo por maior atrito de setup frente ao escopo minimo da story.

### Alternativa B — Implementar email + SMS no mesmo PR

- Cobriria todos os canais de uma vez.
- Foi descartada porque o produto nao tem jornada de SMS consolidada nem decisao de fornecedor/orcamento para esse canal.

### Alternativa C — Manter apenas log local

- Simples e sem credenciais.
- Foi descartada porque nao fecha o fluxo real em producao.

---

## Critério de revisão

- Revisar quando o app precisar recuperar senha por telefone sem email.
- Revisar se o custo/limites do Resend deixarem de caber no volume esperado.
- Revisar se outro provedor passar a ser padrao corporativo do ambiente de deploy.

---

## Links

- Issue / task: <https://github.com/wesleysimplicio/sistema-sindico/issues/90>
- PR de implementação: commit direto em `main`
- Documentos relacionados: [DESIGN](./DESIGN.md), [PATTERNS](./PATTERNS.md)
- ADRs relacionados: [ADR-002](./ADR-002-docker-dev-runtime.md), [ADR-003](./ADR-003-rate-limit-driver.md)
