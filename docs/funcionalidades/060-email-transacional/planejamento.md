# 060 — E-mail transacional e Fale Conosco — Planejamento

**Estado no tronco:** Parcial
**Prioridade no plano mestre:** Onda 2 (PROPOSTA do §5): E4 fase 2 e testes reais de E5/E6, depois de FN-057
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 E1–E7 (Fase 1); [docs/operacao/email.md](../../operacao/email.md). Equivalência: E1–E7 → FN-060; E8 (páginas públicas) → FN-072 ([plano mestre §1.2](../../planejamento/00-plano-mestre.md))
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Fazer o Gabarito360 enviar e receber e-mail com o próprio domínio, de forma confiável e auditável: recuperação de senha, convite de usuário e de vínculo, confirmação e troca de e-mail e o Fale Conosco. Atende todos os usuários e visitantes do site. As fases seguintes de modelos levam avisos do sistema (notificações, provas, correções, importação) ao e-mail.

## 2. Escopo
- **Inclui:** E1 domínio (Resend + Cloudflare Email Routing, DMARC); E2 motor `EmailModelo` pela fila com registro em `emails_enviados`; E3 recuperação de senha; E4 modelos (fases 1 a 4); E5 convite de usuário; E6 confirmação e troca de e-mail; E7 Fale Conosco; endurecimento do DMARC (`p=none` → `p=quarantine`).
- **Não inclui:**
  - E8 páginas públicas (planos, termos, privacidade, SEO) → [FN-072](../072-site-publico/planejamento.md) e [FN-061](../061-lgpd-termos-privacidade/planejamento.md).
  - Motor de notificações, central e preferências → [FN-057](../057-notificacoes/planejamento.md) (os modelos da fase 2 dependem dele).
  - Provas agendadas e lembretes → [FN-059](../059-agendamento-aplicacao-provas/planejamento.md) (modelos da fase 4).
  - Importação de alunos → [FN-034](../034-importacao-exportacao-alunos/planejamento.md) (modelo `importacao-concluida`, fase 3).
  - Convite por CPF e outbox de convites (VI1) → [FN-017](../017-convites-cpf/planejamento.md).
  - Pagamentos (`assinatura-pagamento`) → [FN-068](../068-financeiro/planejamento.md); resultado ao responsável (`portal-resultado`) → [FN-067](../067-portal-responsaveis/planejamento.md).

## 3. Fontes de verdade
- Mockups: não há mockup de e-mail; formulário Fale Conosco segue a identidade atual (docs/28 E7; inventário [docs/01](../../01-inventario-dos-mockups.md), seção de páginas SaaS).
- Documentos de origem: [docs/28](../../28-plano-mestre-proximos-modulos.md) §2 (E-mail no ECOAR, DNS) e Fase 1; [docs/operacao/email.md](../../operacao/email.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-001 do [docs/07](../../07-casos-de-uso.md) (autenticação) e UC-008 (gerir equipe) são relacionados.
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) (`usuario.gerir` para convites).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-002 base-operacao (B3 Redis, B4 workers) | Envio pela fila | Concluída |
| D15 do docs/28 (acesso à Cloudflare) | E1 | Resolvida (E1 entregue) |
| FN-057 notificacoes | Modelos E4 fase 2 (`notificacao-geral`, `resumo-diario` e avisos) | Pendente |
| FN-059 agendamento-aplicacao-provas | Modelos E4 fase 4 | Pendente |
| FN-034 importacao-exportacao-alunos | Modelo E4 fase 3 | Pendente |
| FN-015 vinculos-contexto | Convite para vínculo em conta existente | Concluída |

**Funcionalidades que dependem desta:** FN-057 (N3 e-mail), FN-059 (lembretes), FN-034 (aviso de importação), FN-017 (convites), FN-011 (recuperação de senha e troca de e-mail), FN-067 (P8, segunda versão).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (E1) Domínio | Resend `sa-east-1`, DKIM, Email Routing, SPF, DMARC `p=none` | Domínio verificado; teste com SPF, DKIM e DMARC aprovados | Entregue (produção) | PR #53; docs/operacao/email.md |
| 1.2 (E2) Motor de e-mail | `App\Mail\EmailModelo` (fila `padrao`, 3 tentativas, valores escapados, versão texto, cabeçalho `X-G360-Modelo`); `emails_enviados` sem corpo; `g360:email-teste` | Testes de substituição, escape e fila; envio real | Entregue (produção) | PR #54; `apps/api/tests/Feature/EmailsTest.php` |
| 1.3 (E3) Recuperação de senha | Link de 60 min, uso único, resposta igual, aviso de senha alterada, sessões encerradas | Fluxo ponta a ponta; testes de token vencido e repetido | Entregue (produção) | PR #54; `EmailsTest` |
| 1.4 (E7) Fale Conosco | Formulário com Turnstile e limite por IP; e-mail à equipe com responder-para; confirmação a quem escreveu | Envio real chega ao destino | Entregue (produção) | PR #53, #58; `LeadController`; `TurnstileTest` |
| 2.1 (E4 fase 1) Modelos da fase 1 | `recuperar-senha`, `senha-alterada`, `convite-usuario`, `confirmar-email`, `email-alterado-aviso`, `fale-conosco-equipe`, `fale-conosco-confirmacao` (mais `convite-recusado`, `convite-vinculo`, `boas-vindas-cadastro`) | Pré-visualização em `/referencia-visual` e teste de renderização | Entregue | PR #54; `apps/api/resources/emails/modelos/` |
| 2.2 (E5) Convite de usuário | Convite com link de 7 dias e reenvio; recusa avisa quem convidou | Conta nova define a própria senha pelo link | Entregue (código), sem teste real | PR #54, #55; `ContaUsuario::convidar` |
| 2.3 (E6) Confirmação e troca de e-mail | Senha atual exigida; endereço anterior mantido até confirmar (24 h); aviso ao anterior | E-mail só muda após confirmação | Entregue (código), sem teste real | PR #54; `ContaUsuario::pedirTrocaDeEmail` |
| 3.1 (E4 fase 2) Modelos de vínculo, permissão, conta suspensa, notificação, resumo, suporte-ver-como | Modelos e pré-visualização | Idem 2.1 | Pendente | depende de FN-057 |
| 3.2 (E4 fase 3) `importacao-concluida` | — | Idem | Pendente | depende de FN-034 |
| 3.3 (E4 fase 4) `prova-agendada`, `prova-lembrete`, `correcao-concluida`, `correcao-fechada` | — | Idem | Pendente | depende de FN-059 |
| 4.1 DMARC `p=quarantine` | Endurecer a política após duas semanas sem falhas | Relatórios sem falhas; registro atualizado | Pendente | docs/operacao/email.md |

## 6. Contrato técnico (proporcional ao risco)
- Modelos em HTML puro com `{{campos}}`, valores escapados por `e()`; campos não preenchidos removidos.
- Fila `padrao`, `tries=3`, `backoff` 60 e 300 s; mensagens cifradas na fila (`ShouldBeEncrypted`).
- `emails_enviados` guarda modelo, hash SHA-256 do destinatário e data; falhas em `failed_jobs`. Retenção desses registros segue FN-062.
- Endereços em `apps/api/config/emails.php` (`remetente`, `contato`, `url_web`); segredos só no `.env` do servidor.
- Tokens nunca na URL de ação: `redefinir-senha` recusa `token` na query (UR4).
- Notificações por e-mail (fase 2) saem pela fila `baixa` (docs/28 N3).

## 7. Interface
Telas web `/esqueci-senha`, `/redefinir-senha`, `/convite`, `/confirmar-email`, `/fale-conosco` e `/fale-conosco/obrigado`. Pré-visualização dos modelos para administradores prevista em `/referencia-visual` (ver §8). Conferência por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e a Onda 0 | Pendente |
| §4 item 5 — autocadastro e SaaS | Declarar autocadastro/checkout substituídos / retomar | Declarar MP-028 e checkout substituídos; isso define o destino do modelo `boas-vindas-cadastro`, hoje sem uso | Pendente |
| Testes reais de E5 e E6 | Executar na homologação com contas de teste | Executar na Onda 2 | Pendente |
| ⚠ Pré-visualização em `/referencia-visual` (critério de pronto de E4) | A view `referencia/index.blade.php` não contém pré-visualização de e-mails | Registrar como lacuna da fase 1; incluir na 3.1 | Pendente (divergência código × plano mestre, que marca E4 fase 1 como "Entregue") |
| ⚠ Limite de recuperação "por e-mail e por IP" (docs/28 E3 passo 3) | Só há `throttle:5,1` por rota (IP); não há limite por e-mail | Acrescentar limite por e-mail na 3.1 ou registrar a dispensa | Pendente |
| Modelo `boas-vindas-cadastro` sem remetente no código | Manter para o autocadastro / remover | Depende do §4 item 5 | Pendente |
| `docs/operacao/email.md` traz o endereço pessoal de encaminhamento | Manter / mover para fora do repositório | Avaliar à luz das regras de dados pessoais do projeto | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
