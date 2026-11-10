# Plano mestre — orquestrador da execução

**Atualizado em:** 10/11/2026, por lucasgenial
**Status:** PROPOSTA. A sequência (seção 4) aguarda a aprovação do responsável.

## 1. Para que serve este documento

Este documento controla a execução do Gabarito360. Ele define o que é feito, em que ordem e com quais condições.

| Pergunta | Onde está a resposta |
|---|---|
| Quais funcionalidades existem? | [Catálogo de funcionalidades](../funcionalidades/README.md) |
| O que cada funcionalidade faz e quais subfases tem? | `docs/funcionalidades/NNN-slug/planejamento.md` e `casos-de-uso.md` |
| O que é feito agora e o que vem depois? | Este documento |
| Quais são as regras? | [Governança](01-governanca.md) |

Este documento não repete a lista de funcionalidades. Ele cita cada funcionalidade pelo identificador FN-NNN.

## 2. Como a execução funciona

Uma funcionalidade passa por estes passos. O agente `gerente-projetos` controla cada passo.

1. **Fila.** O responsável coloca a funcionalidade numa onda (seção 4).
2. **Planejamento.** O agente escreve ou revisa o `planejamento.md` e o `casos-de-uso.md`.
3. **Aprovação.** O responsável aprova os dois documentos. Sem aprovação, a execução não começa.
4. **Execução por subfase.** Uma subfase é feita de cada vez, na ordem do `planejamento.md`.
5. **Portões da subfase.** Cada subfase passa pelos portões da seção 3.
6. **Homologação.** A entrega é publicada somente na homologação.
7. **Validação.** O responsável valida a entrega na homologação.
8. **Produção.** A produção recebe a entrega somente por decisão expressa do responsável (seção 7).
9. **Registro.** O agente atualiza a etapa no catálogo e no `planejamento.md`, com evidência.

**Regras de fila:**

- Execute uma funcionalidade de produto por vez. A trilha de qualidade (seção 4.6) acompanha as entregas e não ocupa a fila.
- Não comece uma funcionalidade antes das suas dependências.
- Um módulo transversal (por exemplo, agenda ou notificações) bloqueia as funcionalidades que dependem dele. Essas funcionalidades não criam versões locais do módulo.
- Um pedido novo durante uma entrega segue a governança: ramo da entrega atual ou funcionalidade nova no catálogo.

## 3. Portões de cada subfase

Uma subfase só termina quando passa por todos os portões aplicáveis.

| Portão | Critério |
|---|---|
| Planejamento aprovado | Os dois documentos da funcionalidade estão aprovados. |
| Implementação | O código segue a arquitetura oficial e os casos de uso. |
| Testes | Os testes cobrem os critérios de aceite da subfase. |
| Revisão independente | Outro agente revisa a interface (UI/UX) e a segurança, quando aplicável. |
| CI | Todos os jobs da CI passam. |
| Merge | O PR é mesclado sem identificação de IA na autoria. |
| Homologação | A publicação na homologação gera recibo: backup, dados preservados, sem perda de arquivos. |
| Desempenho | Quando a subfase mede desempenho, a medição usa 1000 usuários simultâneos. |
| Registro | A etapa e a evidência estão no catálogo e no `planejamento.md`. |

## 4. Sequência de execução

### 4.1 Situação atual

| Item | Situação em 10/11/2026 |
|---|---|
| Em execução | Nenhuma funcionalidade. A execução aguarda a aprovação deste plano. |
| Próximo passo | Onda 0: FN-078 (governança executável), a partir da subfase 5.1 (subfases 1.1 a 4.2 entregues); depois, decisões do responsável (seção 5) e aprovação dos documentos da Onda 1. |
| Última entrega | FN-077 Desempenho, etapas F0 a F4, publicadas na homologação em 09/11/2026. |

### 4.2 Onda 0 — Saneamento e decisões

Esta onda não muda o código de produto.

| Ordem | Item | Resultado esperado |
|---|---|---|
| 0.0 | FN-078 Governança executável (urgente, decisão do responsável em 10/11/2026): proteção da `main` e verificação de autoria primeiro | Princípios verificados e propagados sem ação humana ([ADR-0053](../decisoes/0053-principios-executaveis-e-mutaveis.md)) |
| 0.1 | Decisões 1 a 8 da seção 5 | Decisões registradas neste documento |
| 0.2 | Aprovar os documentos de FN-056, FN-057, FN-039, FN-016 e FN-026 | Etapa "Planejamento aprovado" no catálogo |
| 0.3 | Marcar os documentos numerados desatualizados (seção 6) com a nota "superado pelo plano mestre" | Nenhum agente segue documento desatualizado |
| 0.4 | Se a decisão 2 for aprovada: promover à produção em blocos (seção 7) | Produção com as correções de segurança |

### 4.3 Onda 1 — Módulos transversais e fundações

| Ordem | FN | Próximas subfases | Depende de |
|---|---|---|---|
| 1 | FN-056 Agenda e calendário | Aprovação da prova de conceito C1; C2 modelo de eventos; C3 tela; ativação dos cards reais | Decisão 7 |
| 2 | FN-057 Notificações | N1, N2, N3 (N4 opcional) | — |
| 3 | FN-039 Períodos letivos | P4.1 a P4.4 | FN-056 (C2) |
| 4 | FN-016 Identidade pessoal | Matriz de consulta; contrato de conciliação; flag de provisórios e backfill | Decisão 6 |
| 5 | FN-026 Matrícula e enturmação | MT1-A a MT1-C | FN-039 |

Trilha paralela de baixo risco, se o responsável aceitar: FN-065 (plano de incidentes, só documento) e FN-050 (integrar o rascunho do PDF da turma).

### 4.4 Onda 2 — Funcionalidades que esperam os módulos transversais

| FN | Próximas subfases | Depende de |
|---|---|---|
| FN-059 Agendamento da aplicação de provas | C4; registro de aplicação e elegíveis | FN-056, FN-057 |
| FN-058 Visitas pedagógicas | Mockup de gestão; CRUD sobre o módulo de agenda | FN-056 |
| FN-042 Duplicação de provas | CP2; confirmar A7 | FN-057 |
| FN-017 Convites por CPF | VI1-A a VI1-C | FN-016, FN-057 |
| FN-053 Painéis de turmas | P1.2, P1.4, P1.4.6, P2.4, P3.3, P3.4 | FN-026, FN-059 |
| FN-055 Acompanhamento pedagógico | P6.1 a P6.4 | FN-056, FN-039 |
| FN-060 E-mail transacional | E4 fases 2 a 4; testes reais de E5 e E6; DMARC | FN-057 |

### 4.5 Ondas 3 e 4 — Dados pessoais, comercial e portal

| Onda | FN | Próximas subfases | Depende de |
|---|---|---|---|
| 3 | FN-025 Transferências institucionais | TR1, TE1, UX1 | FN-016, FN-026, FN-031, FN-057 |
| 3 | FN-027 Professor independente | PA1-A a PA1-D; revisão de T3 | FN-016 |
| 3 | FN-064 Pedidos dos titulares e retenção | L4-D, L4-E; retenção (FN-062, FN-066, expurgo de FN-004) | FN-016, FN-026, decisão 11 |
| 3 | FN-034 Importação e exportação de alunos | Importação por tela; exportação; formato da planilha | FN-026, FN-060 |
| 3 | FN-035 Responsáveis dos alunos | A6 | FN-016 |
| 3 | FN-033 Estrutura escolar | A3, A4 | — |
| 4 | FN-061 Termos e privacidade | L1 | Decisões D13 e D14 |
| 4 | FN-068 Financeiro, depois FN-069 Selo | F1 a F3; R1 | Decisão comercial |
| 4 | FN-029 Escola independente | EI1-B a EI1-G | FN-068 |
| 4 | FN-028 Encerramento de cliente | T6-A a T6-E | FN-064, FN-068 |
| 4 | FN-067 Portal dos responsáveis | P1 a P8 | FN-035, FN-061, FN-062, FN-069, D10 |
| 4 | FN-020 Acesso do aluno | Decisão 10 | Avaliar junto com FN-067 |
| 4 | FN-005 Suporte "ver como"; FN-004 AU2; FN-006 Painel técnico | S1, AU3, AU2, A2 | D9, FN-004 |

### 4.6 Trilha de qualidade

Esta trilha não ocupa a fila. Cada entrega aplica a trilha às telas que muda.

| FN | Regra |
|---|---|
| FN-075 Tailwind | Cada entrega migra as telas que muda. TW4 a TW6 ganham janela própria depois da Onda 2. |
| FN-076 Carregamento e estados | Cada entrega aplica carregamento regional às telas que muda. |
| FN-074 Tabelas | Cada tabela nova usa o componente padrão. |
| FN-077 Desempenho | Medição com 1000 usuários antes de qualquer abertura comercial. F6 restante quando houver uso real. |
| FN-007 Escala | X3 a X6 conforme a medição de FN-077. |

### 4.7 Fora das ondas

| Grupo | FN |
|---|---|
| Concluídas ou em homologação, sem trabalho na fila | FN-001, 002, 003, 008, 012, 013, 014, 015, 018, 019, 021, 022, 023, 031, 036, 037, 038, 040, 041, 045, 046, 047, 049, 052, 054, 063, 070, 071, 072, 073 |
| Parcial, com correção de segurança proposta para a Onda 0 (decisão 18) | FN-011 |
| Candidatas sem prioridade | FN-043, FN-044, FN-048, FN-051, FN-009 |
| Adiadas | FN-010 (app móvel), FN-032 (busca com autocompletar, até haver uso real) |
| Substituídas | FN-024 (por FN-025), FN-030 (por contratação por contato) |

As pendências pequenas das funcionalidades fora das ondas estão no `planejamento.md` de cada uma. O gerente de projetos propõe a inclusão delas numa onda quando forem necessárias.

## 5. Decisões pendentes do responsável

Cada decisão recebe um estudo antes da resposta. O estudo compara as opções e termina com uma recomendação. As decisões 1 a 8 desbloqueiam a Onda 0.

| Nº | Decisão | Afeta | Recomendação inicial | Estado |
|---|---|---|---|---|
| 1 | Aprovar este plano, o catálogo e a sequência da seção 4 | Todas | Aprovar o catálogo agora; discutir a sequência por partes | Em análise |
| 2 | Regra de produção: revogar o gate L4 (docs/102) a favor do ADR-H1 (docs/127) e promover em blocos | FN-003, FN-018, FN-046, FN-064 | Revogar; promover primeiro o bloco de segurança | Aberta |
| 3 | Política T5 (cópias entre espaços) contra o docs/115 (transferências) | FN-024, FN-025 | Declarar T5 substituída; desligar as flags `COPIAS_*` em produção até TR1 | Aberta |
| 4 | Aceite visual da tela antiga de T5 | FN-024 | Dispensar; fazer o aceite na nova tela (UX1) | Aberta |
| 5 | Autocadastro e assinatura online (MP-027, 028, 029) | FN-030, FN-068 | Declarar substituídos; desativar o código remanescente do Mercado Pago | Aberta |
| 6 | Matrizes de permissão de identidade (ID1) e de disciplinas (DC1) | FN-016, FN-017, FN-038 | Aprovar a matriz ID1 do docs/139; registrar a matriz DC1 em vigor no docs/03 | Aberta |
| 7 | Escopo da agenda e biblioteca de calendário (prova de conceito C1) | FN-056, FN-039 | Concluir a prova de conceito com critérios; priorizar FN-039 junto | Aberta |
| 8 | Identidades para a carga de 1000 usuários | FN-077 | Contas sintéticas só na homologação; gerador fora da VM | Aberta |
| 9 | Decisões D9, D10, D13, D14 do docs/28 | FN-005, FN-061, FN-067 | Responder D13 e D14 primeiro; confirmar D10 | Aberta |
| 10 | Login do aluno (decisão 6.4 do docs/19) | FN-020, FN-067 | Decidir junto com o portal dos responsáveis | Aberta |
| 11 | Pacote de retenção LGPD (passo 6 do L6, prazos abertos, expurgo da auditoria) | FN-004, FN-062, FN-064, FN-066 | Aprovar como um pacote único na Onda 3 | Aberta |
| 12 | Contrato de conciliação de identidade | FN-016, FN-064 | Escrever como subfase de FN-016 na Onda 1 | Aberta |
| 13 | Frentes "Em breve" da home pública sem plano | FN-043, FN-044, FN-051, FN-029 | Manter "Em breve" só para o que estiver priorizado | Aberta |
| 14 | Condições de lançamento comercial | FN-068, FN-072 | Tratar no planejamento de FN-068 | Aberta |
| 15 | Estrutura-alvo de diretórios | FN-009 | Manter como candidata até haver um segundo motor de análise | Aberta |
| 16 | PR #194 (dependências para a versão de produção) | FN-018 | Decidir junto com a decisão 2 | Aberta |
| 17 | Foto de aluno aceita de novo (PF1-C/IM1), contra a regra de avatares | FN-066, FN-019 | Estudo pendente | Aberta |
| 18 | Limite de tentativas no login | FN-011 | Incluir na Onda 0 como correção de segurança | Aberta |

O estudo de cada decisão fica em `docs/planejamento/decisoes/` quando for escrito. O resultado volta para esta tabela.

## 6. Saneamento: documentos desatualizados e divergências

Use esta seção na Onda 0. Ela lista o que diverge e o que vale agora.

### 6.1 Documentos desatualizados


| Onde | Divergência | O que vale agora |
|---|---|---|
| docs/13 tabela de status (parou em 19/06) | MP-030, 031, 033, 034 constam "Pendente", mas foram entregues; MP-028 e 035 foram substituídos | Este plano (§2) |
| docs/13 MP-001 | "Concluído", mas android/ios nunca foram criados | FN-001 Parcial no MP-001; FN-010 Adiada |
| docs/13 MP-030 | Descreve `PermissaoService`/`escola_perfil_permissoes`; o código usa `Support\Permissao` | docs/21 e o código |
| docs/13 MP-033 e docs/19 MP-038 | Mesma entrega com dois números | FN-045 |
| docs/14, 15, 16 | Cobrem só MP-001..022 / retrato do início | Histórico |
| CLAUDE.md, seção "Fluxo de Trabalho" | Manda ler o MP no docs/14 e as dependências no docs/13; a seção "Planejamento centralizado" do mesmo arquivo manda seguir o plano mestre | Plano mestre e governança. Resolvido em 09/11: a seção "Fluxo de Trabalho" foi reescrita para o tronco único |
| docs/06 RN-016 e docs/01 §30 | Autocadastro e checkout como planejados | Contratação por contato (PR #59); decisão §4 item 5 |
| docs/03 | Vínculo usuário↔aluno "pendente"; `DashboardController::aluno` já resolve por referência | Incerto até a decisão §4 item 10 |
| docs/10, 11 | Rotas parcialmente defasadas (pré-UR3/UR4) | Código |
| docs/22 | Usuários `@gabarito360.dev` e aluno "sem vínculo", anteriores a V1/A1 | Histórico |
| docs/23 | Aponta capturas em `docs/evidencias/layout-2026-10-30/`; a pasta não existe no worktree e não há imagens versionadas em `docs/` | Regra "sem prints": os 10 prints de `docs/evidencias/` foram removidos em 09/11 e a CI bloqueia novos (`scripts/verificar_imagens.py`); remover a referência ao reescrever FN-073 |
| docs/196 (F1) | Registra "8 capturas Chrome" | Confirmar que não estão versionadas nem em serviços |
| docs/28 §6 | Quadro parou em 02/11; previa docs 29, 30, 31, 36 que não existem (36 virou correções B6) | Este plano |
| docs/28 identificadores | Colisões L, R, P, F, V, A, C com outras séries | §1.2 |
| docs/32 | "Captura continua desligada" | Ativada em produção em 02/11 (docs/100) |
| docs/33, 34 | Numeração RL fora de ordem; docs/34 §4 descreve estado anterior a L3/L6 | L3/L6 entregues (FN-063, FN-066) |
| docs/40 (T1) e docs/63–65 (T3) | Identificação por espaço, CPF proibido no pessoal, capacidades da escola, nome T5 | Direção do docs/115, sujeita à decisão §4 itens 3 e 6 |
| docs/55–60 | "Sem deploy" | Publicado em produção em 02/11 (docs/100) |
| docs/92, 94 | Status "em revisão/rascunho" | Mesclados (docs/99) |
| docs/99 | "Publicado ainda em af4c6d1" | Histórico; produção em `a90fbf5` |
| docs/102, 106 | "Não publicar microfases de L4" | ADR-H1 para a homologação; produção pendente da decisão §4 item 2 |
| docs/24 × docs/25 | "Mesmo grupo de processos" × "já separados" | Separados desde 30/10 |
| docs/25 | "R2 em andamento" | R2 entregue (PR #41) |
| docs/117 | Cita docs/113 (proposta de guarda L4-D/E) | docs/113 não existe; arquivos de guarda fora do Git |
| docs/127–129 × docs/161–164 | "H1" com dois significados | FN-003 × FN-071/072 |
| docs/141–151 | Tabelas "em implementação/pendente" | PR #186 (DC1 entregue na homologação) |
| docs/163 × docs/165 | "EI1 após H1" × "UR1 → TU1 → EI1"; EI1 nunca retomada | Este plano (Onda 4) |
| docs/177–180 | Blocos "Decisão mais recente" empilhados e contraditórios; corpo do 177 (P2.9.3, P2.10.1) superado | Fim do docs/179 (PR #216) + editor do docs/191 (PR #227) |
| docs/179 | Tabelas com P2.6–P2.13 "pendente" | Mescladas (PR #211–#214) |
| docs/191 | EI4–EI6 "Pendente"; "EI" colide com escolas independentes | PR #227 mesclado; FN-054 |
| docs/199 | "Verificação visual no Chrome pendente" | Feita em 09/11 (decisão do responsável) |
| docs/200 | F5 em planejamento | Adiada em 09/11 (FN-032) |
| Inventários G4 × G5 (DC1) | G4: DC1-B..D "pendente/incerto"; G5: entregue via PR #186 | G5 (verificado no merge 3738d04) |
| Inventários G4 × G5 (ID1-D) | G4: "pendente/parcial?"; G5: ID1-D1 entregue (PR #170), backfill pendente | G5 |
| Inventário G6 (F4) | Conferência no Chrome pendente | Decisão de 09/11: feita, sem erros |
| Inventários G5 × G6 (TU1) | G5: aceite visual final incerto; G6: conferência autenticada pendente | Pendente (FN-036) |
| Inventários G1b × G6 (C1) | "Parcial/Incerto" × "Parcial" | Parcial, sem aprovação formal (FN-056) |
| docs/176 | Fila "TU1-PDF logo após IMG" | Ultrapassada; FN-050 Pendente |

---

### 6.2 Divergências entre código e documentos (10/11/2026)

Registradas na seção 8 de cada `planejamento.md`. Viram decisões ou subfases quando o responsável aprovar os documentos.

| FN | Divergência |
|---|---|
| 011 | `POST /login` e `/api/v1/auth/login` sem limite de tentativas; "Manter conectado" sem efeito; senha mínima de 8 no código e de 6 no docs/04; sem integração gov.br |
| 012 | `secretario_educacao` aceito no cadastro de membro, mas não selecionável como vínculo; restam 8 rotas `perfil:` em `dashboard/*` |
| 019 | Sem configuração por escola para o Diretor Escolar; seções de integrações, calendário e notificações do mockup inexistentes |
| 020 | Painel do aluno funciona, mas o cadastro de membro `aluno` não cria a referência em `vinculo_alunos` (só o comando de conciliação) |
| 021–023 | Não há caminho na aplicação para criar espaço `pessoal` ou de `rede`; lacuna sem subfase |
| 025 | Faltava FN-057 (notificações) nas dependências (TR1-D, UX1-D) |
| 026 | Mudança de turma publicada como "Transferir aluno" (docs/115 reserva o termo); `PUT /alunos/{id}` com `turma_id` move sem motivo nem registro; espaço pessoal não grava `aluno_movimentacoes`; dependência de FN-039 não declarada |
| 030 | Webhook do Mercado Pago, `/checkout/*`, "Minha assinatura" e `POST /assinaturas/cancelar` (sem permissão) seguem ativos |
| 031 | Permissões do CI1-E em vigor não constam do docs/03 |
| 033 | `nucleo.gerir` concedível ao Diretor de Núcleo, mas `NucleoController::store` aceita só `admin_rede`; ficha PDF do aluno inexistente |
| 034 | Mockup prevê "Importar planilha"; só há importação por terminal |
| 035 | Campo legado `alunos.nome_responsavel` em texto livre, sem cifragem |
| 038 | Matriz do docs/139 (`disciplina.ver`, `lotacao.gerir`) não existe; só `disciplina.gerir`, atrás da flag `DISCIPLINAS_ATIVAS` |
| 049 | `relatorio.rede` do Secretário de Educação devolve 404 (`RelatorioConsolidadoController::rede` aceita só `admin_rede`) |
| 059 | "Provas do dia" usa data UTC; entre 21h e 24h de Brasília mostra o dia seguinte |
| 060 | Prévia dos modelos de e-mail (E4 fase 1) ausente; limite por e-mail/IP (E3) só por rota; modelo `boas-vindas-cadastro` sem remetente |
| 066 | Foto de aluno voltou a ser aceita (PF1-C/IM1, homologação), contrariando RL19 e o passo 6 do L6; autorização registrada no docs/135 |
| 068 | Tabelas `planos`, `assinaturas`, `pagamentos` e rotas de assinatura ativas (ver 030) |
| 072 | Link do YouTube já está no rodapé (homologação), embora conste pendente |
| 075 | Restam 1 `confirm(` e 4 `alert(` nativos em `correcao-*.js` |

## 7. Fila de produção

A produção (CT 111) recebe uma entrega somente por decisão expressa do responsável. A tabela lista o que está só na homologação. Os identificadores de commit citados são anteriores à reescrita de 10/11/2026; use o [mapa de identificadores](../operacao/mapa-reescrita-autoria-2026-11-10.tsv).


Produção (CT 111): release `a90fbf5` (02/11/2026) + hotfix `83dd5ae` (PDF em lote, PR #156). Tudo abaixo foi mesclado e está só na homologação (VM 120), salvo indicação.

| Bloco | FN | PRs / referência |
|---|---|---|
| Segurança de URLs e sessão; dependências | 018 | #192, #193, #195, #197–#199; **#194 aberto** (UR2-P para a versão de produção) |
| Hotfix leituras 500 e revisão | 046 | #182–#185, #187 (produção expressamente não autorizada no docs/152) |
| Pedidos de titulares (flags ativas na homologação) | 064 | #139–#148 |
| Revisão UX de T5 | 024 | #149 |
| Duplicação de prova CP1 | 042 | #151 |
| Catálogo de instituições | 031 | #152–#158, #160 |
| Tabelas | 074 | #161, #186 |
| Autoria das provas | 041 | #162 |
| Perfis institucionais; imagem de perfil | 014, 019 | #163–#167 |
| Identidade (flags desligadas) | 016 | #168, #170 |
| Disciplinas e currículo | 038 | #171, #186 |
| OMR1–3 | 047 | #188–#190 |
| Marca e apresentação pública | 071, 072 | #191 (Nginx/home de produção pendente) |
| Estabilização e desempenho | 077 | #196, #200, #229–#237; F6-1 só na VM 120 |
| Carregamento e estados | 076 | #201, #202 |
| Organização da turma e imagens | 036, 037 | #203–#209 |
| Painéis e editor | 053, 054 | #210–#216, #218–#221, #226–#228 |
| Tailwind | 075 | #222–#225 |
| CI | 008 | #217 (sem impacto em produção) |
| Gate REL do docs/115, UR1-P | — | dependem da decisão §4 item 2 |
| Pendências operacionais antes do próximo B5 em produção | 003, 072 | reconciliar rodapé e helpers da home (docs/102) e o diff operacional do incidente do painel do professor (docs/operacao/incidente-painel-professor-2026-11-01.md) |

Nada desta lista vai para produção sem decisão expressa do responsável.

## 8. Histórico

| Data | Mudança |
|---|---|
| 09/11/2026 | Primeiro plano mestre, com inventário completo. Arquivado em [historico/2026-11-09-inventario-inicial.md](../historico/2026-11-09-inventario-inicial.md). |
| 10/11/2026 | FN-078 Governança executável entra na Onda 0, ordem 0.0, por decisão do responsável (ADR-0053). |
| 10/11/2026 | Plano mestre passa a ser o orquestrador da execução. A lista de funcionalidades passa para o [catálogo](../funcionalidades/README.md). Texto revisado no padrão ASD-STE100 moderado. |
