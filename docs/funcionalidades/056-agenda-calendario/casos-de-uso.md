# 056 — Agenda e calendário (módulo transversal) — Casos de uso

**Natureza:** nova, com parte visual existente documentada a partir do código
**Perfis envolvidos:** professor, professor autônomo, coordenador, equipe pedagógica, diretor e vice-diretor escolar, secretaria/auxiliar (só público administrativo), diretor de núcleo, administrador da rede; responsável pelo produto (C1). Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para o que já existe, descreve o comportamento **atual** e marca as diferenças com ⚠, apontando a subfase que as resolve. O UC-007 do docs/07 (`docs/07-casos-de-uso.md`) (dashboard por perfil) é consumidor deste módulo e não é repetido aqui.

---

## UC-056-01 — Visualizar agenda demonstrativa nos painéis de turma

**Ator:** perfis com acesso aos painéis de turma
**Permissão:** `turma.ver` (preferências do painel); `relatorio.turma` (painel individual da turma)
**Escopo:** turma / turmas do vínculo ativo
**Pré-condições:** painel de turmas ou da turma aberto; widget `agenda-pedagogica`, `proximos-compromissos` ou `agenda-administrativa` presente na composição
**Pós-condições:** nenhuma; somente leitura
**Tela/rota:** Turmas e turma (FN-053); view `apps/web/resources/views/turmas/_modelos.blade.php`; script `apps/web/public/js/painel-agenda.js` (gerado de `apps/web/resources/js/painel-agenda.js` por `scripts/calendario/build.mjs`); catálogo `apps/api/app/Support/CatalogoWidgetsPainel.php`

**Fluxo principal:**
1. O painel renderiza o card com os selos "Em breve" e "Dados demonstrativos" e o atributo `data-agenda-demo` com itens fictícios.
2. O adaptador valida os itens (até 32, data `AAAA-MM-DD`, hora `HH:MM`), escapa os textos e chama `calendarjs.Timeline` com `editable:false`, `remote:false` e sem controles.
3. O adaptador remove navegação, edição e `onclick`, aplica `role="list"`/`listitem` e oculta a alternativa textual.

**Fluxos alternativos e de erro:**
- 2a. Dados inválidos ou widget sem `data-demo="true"`: nada é renderizado pelo CalendarJS.
- 3a. Falha de renderização: o contêiner é limpo, `data-agenda-falhou` é marcado e a alternativa textual acessível fica visível.

**Regras de negócio:** docs/177 P2.8.5 e docs/180 (modelos "Em breve + Dados demonstrativos"; não simular cadastro nem salvamento). Secretaria recebe só a variante administrativa.

**Critérios de aceite:**
- [x] Nenhuma requisição remota, edição ou persistência a partir do card.
- [x] Falha do componente não derruba o painel e mantém alternativa acessível.
- [ ] ⚠ Os dados são fictícios; a substituição por eventos reais é o UC-056-08 (subfase 3.1).

**Situação:** Implementado (visual demonstrativo) — evidência: PR #211–#214; `apps/web/tests/Unit/DemonstracoesPaineisTest.php`

---

## UC-056-02 — Avaliar e aprovar a prova de conceito do CalendarJS

**Ator:** agente de Design/execução; responsável pelo produto (aprovação)
**Permissão:** não se aplica (decisão de produto)
**Escopo:** plataforma
**Pré-condições:** biblioteca instalada com versões fixas (`@calendarjs/ce` 1.1.0, `lemonadejs` 5.3.6)
**Pós-condições:** decisão registrada no planejamento desta funcionalidade e no plano mestre
**Tela/rota:** página de teste isolada, sem dados reais

**Fluxo principal:**
1. Testar Schedule (dia e semana), Timeline e visão mensal com eventos de teste.
2. Testar repetição e exceções.
3. Traduzir para pt-BR com `setDictionary`.
4. Conferir temas claro e escuro e largura de 375 px; registrar por texto.
5. Apresentar o resultado ao responsável e registrar a decisão.

**Fluxos alternativos e de erro:**
- 1a. Não há visão mensal ou repetição: levar alternativas ao responsável antes de iniciar a C2 (docs/28 C1).

**Regras de negócio:** docs/28 C1; docs/177 P5.1 (o card resumido não fica condicionado à biblioteca da página completa).

**Critérios de aceite:**
- [ ] Visão mensal confirmada ou alternativa aprovada.
- [ ] Repetição confirmada ou tratada pelo modelo de eventos.
- [ ] Decisão do responsável registrada.

**Situação:** Parcial — biblioteca adotada nos widgets por orientação do responsável (docs/179), sem aprovação formal da PoC. ⚠ Resolve: subfase 1.1 (C1).

---

## UC-056-03 — Consultar eventos do escopo por período

**Ator:** qualquer perfil com `calendario.ver`
**Permissão:** `calendario.ver` (a criar)
**Escopo:** o do vínculo ativo (turma, escola, núcleo, rede ou espaço pessoal)
**Pré-condições:** usuário autenticado com vínculo selecionado
**Pós-condições:** nenhuma
**Tela/rota:** endpoint de consulta por intervalo (a definir na C2), consumido pela tela e pelos cards

**Fluxo principal:**
1. O cliente pede eventos de um intervalo de datas limitado, com filtros opcionais (turma, disciplina, tipo).
2. A API resolve o escopo no servidor e devolve apenas eventos visíveis ao vínculo.
3. Para secretaria e auxiliar, devolve só eventos administrativos e só campos administrativos.

**Fluxos alternativos e de erro:**
- 1a. Intervalo acima do limite: 422.
- 2a. Sem permissão: 403. Recurso fora do escopo: tratado como inexistente.

**Regras de negócio:** docs/28 C2; docs/177 P5.2.4 e a restrição da secretaria (sem provas, notas, participação ou alertas, inclusive na agenda).

**Critérios de aceite:**
- [ ] Cada perfil recebe só os eventos do seu escopo (testes por perfil).
- [ ] Secretaria nunca recebe eventos ou campos pedagógicos.
- [ ] Consulta medida com o padrão de 1000 usuários simultâneos.

**Situação:** Não implementado — subfase 2.1 (C2).

---

## UC-056-04 — Criar evento

**Ator:** professor, coordenador, direção, equipe pedagógica, secretaria (administrativo), diretor de núcleo — conforme matriz a aprovar
**Permissão:** `calendario.criar` (a criar)
**Escopo:** o do vínculo ativo
**Pré-condições:** C2 entregue
**Pós-condições:** evento gravado com responsável, situação e auditoria
**Tela/rota:** tela de calendário (C3); endpoint de criação (C2)

**Fluxo principal:**
1. Usuário escolhe tipo (aplicação, correção, devolutiva, recuperação, reunião, administrativo), título, início, fim, fuso, vínculos (escola, turma, disciplina, período quando aplicáveis) e participantes autorizados.
2. API valida datas, escopo e público.
3. Evento é criado e aparece na tela e nos cards do escopo.

**Fluxos alternativos e de erro:**
- 2a. Fim antes do início, turma fora do escopo ou participante não autorizado: 422 com mensagem específica.
- 1a. Tipos de origem externa (aplicação de prova, visita) não são criados manualmente aqui: vêm do módulo de origem (UC-056-10).

**Regras de negócio:** docs/177 P5.2.1–P5.2.4.

**Critérios de aceite:**
- [ ] Evento fora do escopo é recusado.
- [ ] Secretaria só cria eventos administrativos.

**Situação:** Não implementado — subfases 2.1 e 3.1.

---

## UC-056-05 — Alterar ou mover evento, inclusive repetição

**Ator:** autor do evento ou perfil com `calendario.editar` no escopo
**Permissão:** `calendario.editar` (a criar)
**Escopo:** o do vínculo ativo
**Pré-condições:** evento existente e visível
**Pós-condições:** evento atualizado; histórico preservado
**Tela/rota:** tela de calendário (arrastar e soltar ou formulário)

**Fluxo principal:**
1. Usuário altera data, horário ou dados do evento.
2. Para evento repetido, escolhe "só esta ocorrência" ou "esta e as seguintes".
3. API valida, grava a alteração e registra o histórico.

**Fluxos alternativos e de erro:**
- 1a. Evento de origem externa: a alteração é feita no módulo de origem (por exemplo, mudar a data da aplicação em FN-059).

**Regras de negócio:** docs/177 P5.2.5 (sem duplicar eventos nem apagar histórico relevante).

**Critérios de aceite:**
- [ ] Exceção de repetição não duplica ocorrências.
- [ ] Alteração fica registrada com autor e data.

**Situação:** Não implementado — subfases 2.1 e 3.1.

---

## UC-056-06 — Cancelar evento

**Ator:** autor do evento ou perfil com `calendario.cancelar`
**Permissão:** `calendario.cancelar` (a criar)
**Escopo:** o do vínculo ativo
**Pré-condições:** evento existente e não cancelado
**Pós-condições:** situação "cancelado"; registro mantido
**Tela/rota:** tela de calendário

**Fluxo principal:**
1. Usuário pede o cancelamento e confirma.
2. API muda a situação, preserva o registro e o histórico.

**Fluxos alternativos e de erro:**
- 1a. Evento de origem externa: cancelado pelo módulo de origem.

**Regras de negócio:** docs/177 P5.2.3 e P5.2.5.

**Critérios de aceite:**
- [ ] Evento cancelado some das visões padrão e continua consultável no histórico autorizado.

**Situação:** Não implementado — subfases 2.1 e 3.1.

---

## UC-056-07 — Abrir a tela de calendário

**Ator:** professores, coordenadores, direção (docs/28 C3) e demais perfis da matriz aprovada
**Permissão:** `calendario.ver`
**Escopo:** o do vínculo ativo
**Pré-condições:** C2 e C3 entregues
**Pós-condições:** nenhuma
**Tela/rota:** rota web a definir (sem identificadores sensíveis na URL, FN-018); sem mockup próprio

**Fluxo principal:**
1. Usuário abre o calendário e escolhe a visão (agenda, linha do tempo, mensal se aprovada).
2. Aplica filtros; a seleção é preservada ao abrir um evento (P5.3.3).

**Fluxos alternativos e de erro:**
- 1a. Período sem eventos: estado vazio funcional, sem selo "Em breve".
- 1b. Erro de carga: mensagem e opção de tentar de novo.

**Regras de negócio:** docs/28 C3; docs/177 P5.3.

**Critérios de aceite:**
- [ ] Tela aprovada na auditoria de UI/UX, em desktop e 375 px, temas claro e escuro (registro por texto).

**Situação:** Não implementado — subfase 3.1.

---

## UC-056-08 — Ativar os cards de agenda dos painéis com eventos reais

**Ator:** perfis com painéis de turma
**Permissão:** `calendario.ver` além das permissões do painel
**Escopo:** turma / turmas do vínculo
**Pré-condições:** C2 entregue
**Pós-condições:** cards "Agenda da turma", "Agenda de avaliações"/"Próximos compromissos" e "Agenda administrativa" exibem eventos reais
**Tela/rota:** painéis de FN-053, mesmos IDs de widget

**Fluxo principal:**
1. O card pede os eventos da janela visível ao endpoint de consulta.
2. Exibe os eventos no mesmo componente, sem os selos "Em breve" e "Dados demonstrativos".

**Fluxos alternativos e de erro:**
- 2a. Sem eventos: estado vazio funcional (P5.3.5).
- 2b. Falha: o card mostra erro e os demais blocos continuam (P3.4.4).

**Regras de negócio:** docs/177 P5.3 e o exemplo "Agenda da turma" (ativação substitui o exemplo mantendo ID e posição).

**Critérios de aceite:**
- [ ] Nenhum dado demonstrativo convive com dado real no mesmo card.
- [ ] Secretaria vê só a Agenda administrativa.
- [ ] ⚠ As listas "agenda"/"próximas provas" dos dashboards por perfil (FN-052) leem hoje `provas.data_aplicacao` e `visitas` diretamente; passam a consumir este módulo quando FN-059 e FN-058 existirem.

**Situação:** Não implementado — subfase 3.1.

---

## UC-056-09 — Consultar a agenda administrativa (secretaria e auxiliar)

**Ator:** secretaria escolar e auxiliar
**Permissão:** `calendario.ver` restrita ao público administrativo
**Escopo:** escola
**Pré-condições:** C2 entregue
**Pós-condições:** nenhuma
**Tela/rota:** card "Agenda administrativa" e tela de calendário filtrada

**Fluxo principal:**
1. Usuário abre a agenda; a API devolve apenas eventos administrativos e campos administrativos.

**Fluxos alternativos e de erro:**
- 1a. Tarefa logística compartilhada de um evento pedagógico: só os campos administrativos autorizados aparecem (P5.2.4).

**Regras de negócio:** docs/177 (secretaria e auxiliar não recebem provas, notas, participação ou alertas, inclusive na agenda); restrições adicionais do PF1 para o auxiliar.

**Critérios de aceite:**
- [ ] Teste garante ausência de título de prova, turma avaliada ou nota nos eventos devolvidos.

**Situação:** Não implementado — subfases 2.1 e 3.1.

---

## UC-056-10 — Registrar evento a partir de um módulo consumidor

**Ator:** sistema (FN-059 aplicação de provas, FN-058 visitas, FN-055 intervenções, FN-017 vínculo agendado se confirmado)
**Permissão:** a do usuário que age no módulo de origem, revalidada
**Escopo:** o do objeto de origem
**Pré-condições:** C2 entregue com contrato de integração
**Pós-condições:** um único evento por origem, sincronizado
**Tela/rota:** serviço interno da API (sem rota pública própria)

**Fluxo principal:**
1. O módulo de origem cria, altera ou cancela seu objeto.
2. Na mesma transação (ou por fila idempotente), chama o serviço de eventos com `origem_tipo`, `origem_id` e os dados.
3. O serviço cria ou atualiza o evento existente da origem; repetições da chamada não duplicam.

**Fluxos alternativos e de erro:**
- 2a. Origem excluída: o evento é cancelado, não apagado.

**Regras de negócio:** governança §2 item 3 (nenhuma agenda local); docs/28 C4; docs/177 P5.4.1.

**Critérios de aceite:**
- [ ] Teste de idempotência: duas chamadas iguais geram um evento.
- [ ] Nenhum consumidor grava datas de agenda em tabela própria para exibição em calendário.

**Situação:** Não implementado — subfase 2.1 (contrato) e funcionalidades consumidoras.
