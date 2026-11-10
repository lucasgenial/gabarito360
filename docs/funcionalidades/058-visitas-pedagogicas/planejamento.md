# 058 — Visitas pedagógicas — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Observação de estado:** aguarda FN-056
**Prioridade no plano mestre:** Onda 2 (PROPOSTA do §5), depende de FN-056
**Natureza:** evolução de existente (tabela, model e leitura no dashboard do núcleo existem; gestão não existe)
**Origem:** tronco original — docs/13 MP-032; docs/07 UC-009; docs/16 (pede mockup de gestão antes do MP). Equivalência: MP-032 → FN-058 ([plano mestre §1.2](../../planejamento/00-plano-mestre.md))
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que o Diretor de Núcleo agende, acompanhe e encerre visitas pedagógicas às escolas do núcleo, com tipo e urgência, para orientar o acompanhamento em campo. As visitas aparecem na agenda única (FN-056) e no dashboard do núcleo.

## 2. Escopo
- **Inclui:** mockup de gestão (pré-requisito do docs/16); agendar, alterar, cancelar e listar visitas do núcleo; registro da visita como evento da agenda pelo contrato de integração de FN-056; permissões próprias; leitura no dashboard do núcleo a partir da mesma fonte.
- **Não inclui:**
  - Calendário, tela de agenda ou tabela de eventos próprios → [FN-056](../056-agenda-calendario/planejamento.md). **Proibido criar agenda local** (governança §2).
  - Avisos às escolas visitadas → [FN-057](../057-notificacoes/planejamento.md).
  - "Agendar reunião de acompanhamento" (ação do mockup do núcleo) → evento genérico da agenda (FN-056), não visita.
  - Registro pedagógico da visita (relato, encaminhamentos) → não previsto nos documentos de origem; se pedido, entra como candidata.

## 3. Fontes de verdade
- Mockup: `mockups/dashboard-diretor-nucleo.html` (lista "Próximas visitas": data, tipo, urgência Prioritária/Monitorar/Referência; ação rápida "Agendar Visita", rota a definir); inventário em docs/01 (`docs/01-inventario-dos-mockups.md`). **Não há mockup da tela de gestão.**
- Documentos de origem: docs/13 (`docs/13-roadmap.md`) MP-032; docs/07 (`docs/07-casos-de-uso.md`) UC-009; docs/16 (`docs/16-gap-analise.md`).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) (Diretor de Núcleo: escolas do seu núcleo). Não há chave `visita.*` no catálogo.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-056 agenda-calendario (C2, contrato de integração) | Visita é exibida e consultada como evento da agenda única | Pendente |
| Decisão sobre `visitas` × modelo de eventos (§8) | Define se a tabela permanece como dado de domínio | Pendente |
| Mockup de gestão de visitas | docs/16 exige antes da implementação | Pendente |
| FN-057 notificacoes | Aviso opcional à escola visitada | Pendente (não bloqueia o CRUD) |
| FN-012 permissoes-motor | Chaves `visita.*` | Concluída |

**Funcionalidades que dependem desta:** nenhuma identificada no plano mestre. FN-052 (dashboard do Diretor de Núcleo) já lê `visitas`.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (MP-001/MP-007) Estrutura e leitura | Tabela `visitas`, model `Visita`, lista "Próximas visitas" no dashboard do núcleo | Dashboard lista as 3 próximas visitas do núcleo | Entregue (produção) | `2026_01_01_000016_create_visitas_table.php`; `apps/api/app/Models/Visita.php`; `DashboardController::dirNucleo`; `dashboard/diretor-nucleo.blade.php` |
| 1.1 (MP-032) Mockup de gestão | Mockup aprovado da tela de agendar/listar visitas | Aprovação do responsável | Pendente | — |
| 2.1 (MP-032) CRUD de visitas sobre a agenda | API e web para agendar, alterar, cancelar e listar; evento correspondente em FN-056 | UC-058-02 a 05 aceitos; nenhuma agenda local | Pendente | Não há `VisitaController` nem rotas em `api.php`/`web.php` |

## 6. Contrato técnico (proporcional ao risco)
- API dona do banco; qualquer mudança em `visitas` por migration aditiva.
- Escopo já tratado: `visitas` tem `espaco_id` e está em `ConsultasPedagogicas`/`ConsultaVinculo` com conferência de que a escola pertence ao núcleo da visita (`PlanoIntegridadeEspacos`, constraint `visitas_nucleo`).
- Integração com a agenda: cada visita gera ou atualiza um evento com `origem_tipo=visita` de forma idempotente; cancelar a visita cancela o evento.
- Autorização: chaves `visita.ver` e `visita.gerir` (nomes a aprovar); hoje o acesso de leitura é só pelo middleware `perfil:dir_nucleo` do dashboard.
- Volume pequeno; listas paginadas por intervalo de datas.

## 7. Interface
Tela de gestão segundo o mockup a aprovar; ação "Agendar Visita" no dashboard do núcleo; estados carregando, vazio ("Nenhuma visita agendada." já existe), erro; desktop e 375 px. Conferência por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e a Onda 0 | Pendente |
| §4 item 7 — escopo do módulo de agenda e PoC (C1) | Aprovar / alternativa | Aprovar a PoC e priorizar FN-039 com FN-056 | Pendente |
| Sobreposição `visitas`/`Visita.php` × modelo de eventos de FN-056 | (a) migrar para eventos e aposentar `visitas`; (b) manter `visitas` como dado de domínio (tipo, urgência, núcleo, escola) e projetar cada visita como evento com origem; (c) manter separadas | (b). (c) viola a regra de agenda única. Decidir na C2 de FN-056 | Pendente (nova; não está no §4) |
| Campos atuais (`data_visita` só data, sem horário nem situação) | Ampliar na 2.1 / manter | Acrescentar horário e situação (agendada, realizada, cancelada) por migration aditiva | Pendente |
| `urgencia` como enum fixo (`prioritaria`, `monitorar`, `referencia`) | Manter / catálogo | Manter (vem do mockup) | Registrado |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
