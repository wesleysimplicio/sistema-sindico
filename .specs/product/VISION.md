# VISION — sistema-sindico

> Documento de uma página. Mantém o time alinhado sobre o porquê. Atualizar quando a tese mudar; nunca apagar a versão anterior sem registrar em ADR.

---

## Problema

Síndicos, administradoras e moradores de condomínios brasileiros operam o dia a dia em planilhas, grupos de WhatsApp e cadernos de portaria. O resultado: avisos perdidos, encomendas extraviadas, reservas conflitantes, visitantes sem rastro e cobranças com baixa visibilidade. As ferramentas existentes ou são caras (suítes ERP), ou desconectam portaria/morador/síndico, ou não tratam multi-condomínio com isolamento real de dados.

---

## Quem usa

Resumo das personas. Detalhes completos em `PERSONAS.md`.

- **Persona primária:** síndico — gerencia avisos, manutenção, finanças e comunicação do condomínio.
- **Persona secundária:** morador — acompanha boletos, encomendas, agenda áreas comuns e autoriza visitantes.
- **Persona operacional:** porteiro — registra visitantes, encomendas, ocorrências e dispara o portão.
- **Persona administrativa:** admin (administradora ou superusuário) — opera vários condomínios via uma única conta.
- **Quem NÃO é o público:** condomínios sem internet na portaria; loteamentos horizontais sem unidades cadastradas; uso pessoal/residencial fora de contexto condominial.

Veja `./PERSONAS.md` para objetivos, frustrações e contexto de uso de cada persona.

---

## Diferencial

- **Multi-tenant nativo** — toda tabela de domínio carrega `condominium_id` com FK e enforcement explícito no middleware `ApiAuth` (cross-tenant leak é tratado como bug P0).
- **Painel web (síndico/admin) + API REST mobile-ready (morador/porteiro)** no mesmo backend, sem dependência de framework pesado: PHP 8.2 puro, PDO e router custom.
- **Auth de dois mundos**: sessão + CSRF para o painel web e JWT HS256 (TTL 7 dias, jti revogável) para o app móvel — coexistindo sob o mesmo `users`.
- **Trilha de segurança real**: 2FA TOTP, recuperação por código com hash SHA-256 + attempt counter + lockout, política de senha com histórico das últimas 5, rate limit por IP+email e webhook de portão validado por HMAC.
- **Pipeline DoD automatizado** — workflow `dod.yml` bloqueia merge sem unit + lint + e2e + evidências.

---

## Métricas de sucesso

| Métrica | Baseline | Meta (3 meses) | Como medimos |
|---|---|---|---|
| Cobertura de tests no diff | TODO: humano preencher | >= 80% | CI gate (`dod.yml`) |
| Tempo médio para registrar visita na portaria | TODO: humano preencher | < 30s do clique ao QR emitido | Newman + log do endpoint `POST /api/visitors` |
| Adoção do app por moradores ativos | TODO: humano preencher | TODO: humano preencher | `api_tokens.last_used_at` agregado |
| Cross-tenant leaks detectados em prod | 0 | 0 | Audit log + alerta no `audit_logs` |
| Taxa de sucesso de payments marcados pagos | TODO: humano preencher | TODO: humano preencher | `payments.status = 'pago'` / total mensal |

---

## Não-objetivos

- **Não somos um ERP financeiro.** Geração de boleto, conciliação bancária e DRE ficam fora — integramos via PIX/`barcode`/`pix_code` mas não emitimos.
- **Não substituímos sistemas de CFTV.** Lemos eventos de portão via webhook, mas streaming/gravação fica com o fornecedor da câmera.
- **Não competimos com WhatsApp.** Mensagens são internas (síndico, portaria, suporte, direto) — não há gateway de WhatsApp Business.
- **Não somos multi-país.** Domínio fixo em pt-BR (CNPJ, CEP, status em português, locale default `pt-BR`).
- **Não exportamos relatório PDF nativo no v1.** Backlog P2 quando houver demanda.

---

## Tese de longo prazo

Em 12 meses, qualquer condomínio com 20+ unidades opera comunicação, portaria, manutenção e reservas no Sistema Sindico — síndico no painel web, morador e porteiro no app — sem precisar de planilha ou grupo de WhatsApp para o operacional do dia.

---

## Histórico

| Data | Versão | Mudança | Quem |
|---|---|---|---|
| 2026-05-07 | 0.2 | Reescrita pós-bootstrap com base no schema, rotas e middleware reais | Wesley Simplicio |
| 2026-05-07 | 0.1 | Criação inicial (template) | Wesley Simplicio |
