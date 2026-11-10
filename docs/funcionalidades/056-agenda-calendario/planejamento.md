# 056 — Agenda e calendário (módulo transversal) — Planejamento

**Estado no tronco:** Pendente · **MÓDULO TRANSVERSAL**
**Prioridade no plano mestre:** Onda 1, ordem 1 (PROPOSTA do §5, ainda não aprovada): C1 formal → C2 → C3
**Natureza:** nova, com parte visual existente documentada a partir do código (widgets demonstrativos com CalendarJS)
**Origem:** tronco original — docs/28 C1–C3 (Fase 7); ampliada pelos painéis de turma em docs/177 P5, docs/179, docs/180 e docs/189. Equivalências: C1–C3 e P5 dos painéis → FN-056; M3 "Tempo escolar e agenda" → FN-056 e FN-039 ([plano mestre §1.2](../../planejamento/00-plano-mestre.md))
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Dar ao Gabarito360 **uma única agenda**: um modelo de eventos com escopo e permissões próprios, uma tela de calendário e cards de agenda reais nos painéis. Professores, coordenação, direção, equipe pedagógica, secretaria e núcleo consultam e registram compromissos (aplicações, devolutivas, reuniões, visitas, tarefas administrativas) sem que cada funcionalidade invente o seu próprio calendário.

## 2. Escopo
- **Inclui:**
  - C1: aprovação formal da prova de conceito do CalendarJS, com os critérios do docs/28 (visão mensal, repetição, pt-BR, temas claro e escuro, 375 px) e o registro da decisão.
  - C2: modelo de eventos (tipo, espaço, escola, turma, disciplina e período quando aplicáveis, início, fim, fuso, responsável, participantes autorizados, situação, origem), permissões `calendario.*` separadas para consultar, criar, alterar e cancelar, endpoints por período e escopo, público pedagógico e administrativo, repetição e exceções sem duplicar eventos nem apagar histórico.
  - C3: tela de calendário (agenda, linha do tempo e visão mensal, se aprovada no C1), criação, movimentação e cancelamento conforme permissão, e ativação dos três cards dos painéis (Agenda da turma, Próximos compromissos/Agenda de avaliações, Agenda administrativa) com estado vazio funcional no lugar de "Em breve".
  - Contrato de integração para os módulos consumidores: um evento sempre aponta para a sua origem (por exemplo, aplicação de prova ou visita), e a criação, atualização e cancelamento vindos de outro módulo são idempotentes.
- **Não inclui:**
  - Provas no calendário, lembretes e registro de aplicação (C4) → [FN-059](../059-agendamento-aplicacao-provas/planejamento.md).
  - Motor, central e preferências de notificações (N1–N3) → [FN-057](../057-notificacoes/planejamento.md).
  - Períodos letivos, bimestres e calendário escolar por turma (P4) → [FN-039](../039-periodos-letivos/planejamento.md).
  - Gestão das visitas pedagógicas do núcleo (MP-032, UC-009) → [FN-058](../058-visitas-pedagogicas/planejamento.md).
  - Intervenções, conselho e metas que geram compromissos (P6) → [FN-055](../055-acompanhamento-pedagogico/planejamento.md).
  - Agendamento do início de vínculo (VI1-B) → [FN-017](../017-convites-cpf/planejamento.md).
  - Push no navegador (N4) → FN-057, adiada.

### Regra do módulo transversal
Pela [governança](../../planejamento/01-governanca.md) (§2, item 3 e "O que fica proibido"), **nenhuma funcionalidade pode criar agenda, calendário ou tabela de eventos local**. As dependentes abaixo aguardam este módulo e, até lá, usam estado vazio ou "Em breve", conforme a regra de design vigente.

| Dependente | O que espera deste módulo | Proibido até lá |
|---|---|---|
| [FN-053](../053-paineis-turmas/planejamento.md) painéis/agenda da turma | Cards de agenda ligados a eventos reais (P5.3) | Ligar os cards a qualquer fonte que não seja o modelo de eventos de C2 |
| [FN-055](../055-acompanhamento-pedagogico/planejamento.md) acompanhamento pedagógico | Vincular intervenções e ações à agenda existente (P6.2.3) | Criar um "segundo calendário" para intervenções |
| [FN-058](../058-visitas-pedagogicas/planejamento.md) visitas pedagógicas | Visita registrada como evento do núcleo | CRUD de visitas com agenda própria (ver decisão sobre `visitas` na §8) |
| [FN-059](../059-agendamento-aplicacao-provas/planejamento.md) marcação e aplicação de provas | Evento criado, alterado e cancelado a partir da aplicação (C4) | Calendário de provas paralelo |
| [FN-039](../039-periodos-letivos/planejamento.md) períodos letivos | C2 como base de datas e eventos; P4 fornece o recorte por período | Calendário escolar separado do modelo de eventos |
| [FN-017](../017-convites-cpf/planejamento.md) convites (VI1-B agendado) | Exibir vínculos com início agendado como compromisso, se o responsável confirmar esse uso | Agenda própria de convites |

## 3. Fontes de verdade
- Mockups: `mockups/dashboard-coordenador.html` (Agenda da semana), `mockups/dashboard-diretor-escolar.html` (Calendário de provas), `mockups/dashboard-diretor-nucleo.html` (Próximas visitas), `mockups/configuracoes.html` (Calendário letivo); inventário em [docs/01](../../01-inventario-dos-mockups.md). Não há mockup da tela completa de calendário.
- Style system e direção de design dos painéis: [docs/180](../../180-correcao-dashboards-diretriz.md) e [docs/177](../../177-plano-paineis-turmas.md) (P2.8.5, P5).
- Documentos de origem: [docs/28](../../28-plano-mestre-proximos-modulos.md) §2 (CalendarJS) e Fase 7; [docs/177](../../177-plano-paineis-turmas.md) P5; [docs/179](../../179-execucao-paineis-turmas.md); [docs/189](../../189-paineis-secoes-cards.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). UC-007 do [docs/07](../../07-casos-de-uso.md) (dashboard por perfil) é consumidor.
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md). As chaves `calendario.*` ainda não existem no catálogo.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| Decisão §4 item 7 (C1) | Sem a PoC aprovada, C2/C3 não começam | Pendente |
| FN-012 permissoes-motor | Registro das chaves `calendario.*` e padrões por perfil | Concluída |
| FN-013 acessos-internos (A1) | Dependência de C2 no docs/28 | Concluída |
| FN-015 vinculos-contexto | Escopo por vínculo nos endpoints | Concluída |
| FN-038 disciplinas-curriculo (DC1) | Vínculo opcional do evento com disciplina | Em homologação |
| FN-039 periodos-letivos | Recorte por período; não bloqueia eventos com datas explícitas (P5 "Paralelismo") | Candidata |
| FN-002 base-operacao (B4 agendador) | Repetição e tarefas agendadas | Concluída |

**Funcionalidades que dependem desta:** FN-053 (cards de agenda), FN-055, FN-058, FN-059, FN-039, FN-017 (VI1-B agendado). Elas aguardam este módulo para receber suas mudanças e não criam agenda própria.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (P2.8.5) Widgets de agenda demonstrativos | Três widgets com CalendarJS Timeline, somente leitura, dados fictícios, selos "Em breve" e "Dados demonstrativos" | Revisão visual dos painéis | Entregue (homologação, visual) | PR #211–#214; `apps/web/resources/js/painel-agenda.js`; `scripts/calendario/build.mjs` |
| 1.1 (C1, P5.1) Prova de conceito do CalendarJS | Teste de Schedule, Timeline e visão mensal; repetição; pt-BR; temas; 375 px; decisão registrada | Responsável aprova a PoC; se faltar visão mensal, alternativas apresentadas antes de seguir | Parcial | Biblioteca adotada (`@calendarjs/ce` 1.1.0, `lemonadejs` 5.3.6, licenças em `apps/web/public/js/libs/calendarjs/`); aprovação formal não registrada; `docs/30-calendario.md` não existe |
| 2.1 (C2, P5.2) Modelo de eventos e permissões `calendario.*` | Migration aditiva, model, permissões, endpoints por período e escopo, contrato de integração com origem | Cada perfil recebe só os eventos do seu escopo; secretaria só o público administrativo; alteração e cancelamento sem perder histórico | Pendente | Sem migration de eventos (`auditoria_eventos` e `vinculo_eventos` são outra coisa) |
| 3.1 (C3, P5.3) Tela de calendário e cards reais | Página completa; criar, mover e cancelar conforme permissão; três cards dos painéis com eventos reais e vazio funcional | Tela passa na auditoria de UI/UX, funciona no celular, cards substituem o exemplo sem redesenho | Pendente | — |

Status: Pendente, Em execução, Entregue (homologação), Concluída, Adiada, Substituída.

## 6. Contrato técnico (proporcional ao risco)
- **API dona do banco.** Migration aditiva `eventos` (nome a confirmar na C2), com origem polimórfica controlada (`origem_tipo`, `origem_id`) para os consumidores; chave de idempotência por origem.
- **Autorização.** Chaves `calendario.ver`, `calendario.criar`, `calendario.editar`, `calendario.cancelar` (P5.2.3 pede separação; o docs/28 previa só `ver` e `editar`). Escopo sempre resolvido no servidor por `ConsultaVinculo`/`Escopo`, como as demais consultas pedagógicas. Secretaria e auxiliar só recebem campos administrativos (docs/177, linha de restrição da secretaria).
- **BFF (UR4).** Rotas web sem identificadores sensíveis na URL, seguindo FN-018.
- **Volume e paginação.** Consulta sempre por intervalo de datas limitado; cards pedem só a janela visível.
- **Cache.** Decisão na C2; não cachear por usuário sem invalidação por evento.
- **Filas.** Lembretes ficam em FN-059/FN-057; esta funcionalidade não envia avisos.
- **Desempenho.** Endpoints de consulta medidos com o padrão de 1000 usuários simultâneos antes de ativar os cards reais.
- **Frontend.** O bundle atual é gerado por `scripts/calendario/build.mjs` a partir de `apps/web/resources/js/painel-agenda.js`; a pasta `libs/calendarjs` guarda só licenças e versões fixas.

## 7. Interface
- Cards de painel já desenhados (alturas 12/14, seção `periodo-agenda` no painel individual, `administrativo` no agregado, docs/189).
- Estados: carregando, vazio funcional ("Nenhum compromisso no período"), erro que não derruba os demais blocos (P3.4.4).
- Acessibilidade: alternativa textual já existente (`data-agenda-alternativa`), papéis `list`/`listitem`; manter na versão real.
- Desktop e celular (375 px); temas claro e escuro. Conferência registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e a Onda 0 | Pendente |
| §4 item 7 — escopo do módulo de agenda e PoC do CalendarJS (C1) | Aprovar PoC com critérios do docs/28 / buscar alternativa | Aprovar a PoC com os critérios do docs/28 (visão mensal, repetição) e priorizar FN-039 junto com FN-056 | Pendente |
| §4 item 8 — identidades para a carga de 1000 usuários | Contas sintéticas na VM 120 / outra | Contas sintéticas geradas por comando, só na VM 120 | Pendente |
| Sobreposição `visitas`/`Visita.php` × modelo de eventos | (a) migrar `visitas` para eventos do tipo visita e aposentar a tabela; (b) manter `visitas` como dado de domínio (tipo, urgência) e projetar cada visita como evento com origem; (c) manter separadas | (b): preserva o dado de domínio e o leitor do dashboard do núcleo, e cumpre a regra de agenda única. Decidir na C2, antes de FN-058 | Pendente (nova; não está no §4) |
| Listas "agenda" já existentes nos dashboards por perfil (FN-052), derivadas de `provas.data_aplicacao` (professor, coordenador, diretor escolar, aluno, aplicador) e de `visitas` (diretor de núcleo) | Manter como leitura derivada / passar a ler eventos após C4 | Não são agendas paralelas (não armazenam eventos), mas devem passar a consumir o módulo quando FN-059 existir; registrar em FN-059 | Pendente |
| Dependência circular aparente com FN-039 (039 depende de C2; recorte por período de 056 depende de 039) | — | C2 com datas explícitas primeiro; integração por período depois de FN-039 (docs/177 P5 "Paralelismo") | Registrado |
| FN-017 como dependente (VI1-B) | VI1-B é um estado do vínculo (`aceito_agendado`, docs/115 §5), não um evento | Confirmar se o vínculo agendado aparece na agenda; o controle de acesso de VI1-B não pode depender deste módulo | Pendente |
| Ramo legado "comunicacao agenda e tempo real v2" fora da main (inventário G1b) | Avaliar reaproveitamento / ignorar | Avaliar na C2, sem trazer código sem revisão | Pendente |
| Visão mensal de eventos não confirmada no CalendarJS (docs/28 §2) | — | Testar na C1; se faltar, levar alternativas | Pendente |

## 9. Ramos desta entrega
Nenhum aberto. Subdivisões técnicas da C2/C3 entram aqui quando a execução começar.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
