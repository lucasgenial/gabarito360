# 057 — Notificações (módulo transversal) — Planejamento

**Estado no tronco:** Pendente · **MÓDULO TRANSVERSAL**
**Prioridade no plano mestre:** Onda 1, ordem 2 (PROPOSTA do §5, ainda não aprovada): N1 → N2 → N3
**Natureza:** nova
**Origem:** tronco original — docs/28 N1–N4 (Fase 4); docs/177 P5.4 (avisos dos painéis). Equivalências: N1–N4 → FN-057; P5 dos painéis → FN-056 e FN-057 ([plano mestre §1.2](../../planejamento/00-plano-mestre.md))
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Criar **um único motor de notificações** para avisar as pessoas certas, no escopo certo, sobre o que acontece no sistema (convites, duplicações de prova, aplicações agendadas, correções concluídas), com central no topo da aplicação, preferências por tipo e envio por e-mail. Evita que cada funcionalidade crie sua própria lista de avisos.

## 2. Escopo
- **Inclui:**
  - N1: tabelas `notificacoes`, `notificacao_destinatarios`, `notificacao_preferencias`; catálogo único de tipos (como o de permissões); `Notificador::enviar(tipo, dados, destinatarios)`; resolução de destinatários por perfil e escopo; agrupamento de eventos repetidos; revalidação de destinatários no envio (P5.4.4).
  - N2: sino com contador no layout, lista de lidas e não lidas, consulta a cada 60 s que para com a aba oculta.
  - N3: preferências de canal por tipo, e-mail pela fila `baixa` usando o motor de FN-060, resumo diário opcional.
- **Não inclui:**
  - N4 push no navegador → adiada (opcional, docs/28; docs/177 P5.4.6).
  - Motor de e-mail, modelos e domínio → [FN-060](../060-email-transacional/planejamento.md) (esta funcionalidade usa os modelos `notificacao-geral` e `resumo-diario` da fase 2 de E4).
  - Eventos de agenda → [FN-056](../056-agenda-calendario/planejamento.md); lembretes de aplicação → [FN-059](../059-agendamento-aplicacao-provas/planejamento.md) (consome este motor).
  - Mensagens de retorno de ação (toast de sucesso/erro), que já existem e não são notificações persistentes.

### Regra do módulo transversal
Pela [governança](../../planejamento/01-governanca.md), **nenhuma funcionalidade cria lista própria de notificações, outbox de avisos ao usuário ou central paralela**.

| Dependente | O que espera |
|---|---|
| [FN-042](../042-duplicacao-provas/planejamento.md) CP2 | Aviso ao professor de prova duplicada pela coordenação |
| [FN-017](../017-convites-cpf/planejamento.md) VI1-C | Outbox transacional de convites com histórico de entrega |
| [FN-059](../059-agendamento-aplicacao-provas/planejamento.md) C4 | Avisos à coordenação, direção e núcleo e lembrete na véspera |
| [FN-060](../060-email-transacional/planejamento.md) E4 fase 2 | Modelos `notificacao-geral`, `resumo-diario` e demais avisos da fase 2 |
| [FN-067](../067-portal-responsaveis/planejamento.md) P8 | Código por e-mail na segunda versão do portal |
| [FN-053](../053-paineis-turmas/planejamento.md) | Cards de avisos dos painéis |

## 3. Fontes de verdade
- Mockups: `mockups/configuracoes.html` (seção Notificações); nenhum mockup da central. Inventário em [docs/01](../../01-inventario-dos-mockups.md).
- Documentos de origem: [docs/28](../../28-plano-mestre-proximos-modulos.md) N1–N4 e E4; [docs/177](../../177-plano-paineis-turmas.md) P5.4; [docs/115](../../115-replanejamento-provas-identidade-transferencias.md) §5 (outbox transacional de convites).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-002 base-operacao (B4 filas e agendador) | Envio assíncrono e resumo diário | Concluída |
| FN-015 vinculos-contexto | Destinatários por vínculo e escopo | Concluída |
| FN-060 email-transacional (E2, E4) | Canal e-mail de N3 | Parcial (E2 entregue; modelos da fase 2 pendentes, dependem deste módulo) |
| FN-012 permissoes-motor | Catálogo e revalidação de permissões no envio | Concluída |

**Funcionalidades que dependem desta:** FN-042 (CP2), FN-017 (VI1-C), FN-059, FN-060 (modelos E4 fase 2), FN-067 (P8), FN-053 (cards de avisos).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (N1) Modelo e Notificador | Migrations aditivas, catálogo de tipos, `Notificador`, destinatários por escopo, agrupamento | Testes cobrem destinatários por escopo e revalidação no envio | Pendente | Nenhuma migration `notificacoes`; `docs/29-notificacoes.md` não existe |
| 2.1 (N2) Central | Sino com contador, lista lidas/não lidas, consulta a cada 60 s parada com a aba oculta | Uma notificação aparece e pode ser marcada como lida | Pendente | — |
| 3.1 (N3) Preferências e e-mail | Canais por tipo, fila `baixa`, resumo diário | Preferência respeitada nos testes | Pendente | — |
| 4.1 (N4) Push no navegador | — | — | Adiada | opcional (docs/28) |

## 6. Contrato técnico (proporcional ao risco)
- API dona do banco; migrations aditivas.
- Destinatários resolvidos no servidor por vínculo e escopo; revalidados no envio (revogação, contexto, preferência, cancelamento).
- Idempotência por chave do evento de origem; agrupamento de repetições.
- Conteúdo mínimo: sem dados pessoais desnecessários; secretaria e auxiliar não recebem conteúdo pedagógico (docs/177).
- Consulta do contador barata e paginada; medida com o padrão de 1000 usuários simultâneos (60 s × 1000 usuários).
- E-mail só pelo `EmailModelo` de FN-060, fila `baixa`.

## 7. Interface
Sino no `layouts/app.blade.php` (hoje existe só o contêiner de toasts `#g360-notificacoes`, que continua para retorno de ação); lista com estados carregando, vazio e erro; acessível por teclado; desktop e 375 px. Conferência registrada por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e a Onda 0 | Pendente |
| §4 item 8 — identidades para a carga de 1000 usuários | Contas sintéticas na VM 120 / outra | Contas sintéticas por comando, só na VM 120 | Pendente |
| Nome "notificações" já usado para toasts (`#g360-notificacoes`, `js/notificacoes.js`) | Renomear o toast / manter | Diferenciar no código e na interface para evitar confusão | Pendente |
| `copias_outbox` (T5) é outbox de auditoria, não de avisos | — | Não reaproveitar como lista de notificações | Registrado |
| Ramo legado "comunicacao agenda e tempo real v2" fora da main | Avaliar / ignorar | Avaliar na N1 sem trazer código sem revisão | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
