# 059 — Marcação e aplicação de provas — Casos de uso

**Natureza:** evolução de existente
**Perfis envolvidos:** professor, professor autônomo, coordenador (marcam a data); diretor escolar e vice, diretor de núcleo (acompanham e recebem avisos); aplicador (aplica no dia); aluno (vê próximas provas). Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

O UC-003 do docs/07 (`docs/07-casos-de-uso.md`) (criar prova) cobre o formulário; aqui só a data de aplicação e o que decorre dela. O UC-007 (dashboards) é consumidor.

---

## UC-059-01 — Definir a data de aplicação da prova

**Ator:** professor, professor autônomo, coordenador
**Permissão:** `prova.criar` (criação) e `prova.editar` (alteração)
**Escopo:** provas do escopo do vínculo (professor: suas turmas; autônomo: próprias)
**Pré-condições:** prova em criação ou existente no escopo
**Pós-condições:** `provas.data_aplicacao` gravada (ou nula)
**Tela/rota:** formulário de prova (FN-040); API `POST /provas` e `PUT /provas/{id}` (`ProvaController`)

**Fluxo principal:**
1. Usuário informa a data de aplicação (opcional) junto com os demais dados da prova.
2. A API valida `data_aplicacao` como `sometimes|date|nullable` e grava.

**Fluxos alternativos e de erro:**
- 2a. Data inválida: 422 de validação.

**Regras de negócio:** docs/03 (diretor não cria prova; `prova.editar` só como extra para diretor).

**Critérios de aceite:**
- [x] Data opcional, validada.
- [ ] ⚠ Não gera evento na agenda nem avisos. Resolve: subfases 1.1 e 1.2.
- [ ] ⚠ Só data, sem horário nem turma específica. Resolve: subfase 1.1 (decisão de granularidade).

**Situação:** Implementado (parcial em relação ao planejado) — evidência: `ProvaController` (validação nas linhas de store e update)

---

## UC-059-02 — Ver as provas do dia (aplicador)

**Ator:** aplicador
**Permissão:** perfil `aplicador`; `cartao.enviar` para fotografar/enviar
**Escopo:** provas da sua escola com `data_aplicacao` igual a hoje e situação publicada, em correção ou corrigida
**Pré-condições:** vínculo de aplicador em uma escola
**Pós-condições:** nenhuma
**Tela/rota:** `dashboard/aplicador.blade.php`; API `GET /dashboard/aplicador` (`DashboardController::aplicador`); recorte em `Escopo::consultarProvas` e `ConsultaVinculo` (perfil aplicador)

**Fluxo principal:**
1. A API seleciona a escola do vínculo e lista as provas do dia com turmas, total de alunos e cartões enviados.
2. O aplicador escolhe a prova para enviar cartões (FN-045), sem acesso a gabarito, notas ou relatórios.

**Fluxos alternativos e de erro:**
- 1a. Sem escola vinculada: 403 "Usuário não vinculado a nenhuma escola."
- 1b. Nenhuma prova hoje: lista vazia.

**Regras de negócio:** docs/03 (aplicador: provas do dia da sua escola).

**Critérios de aceite:**
- [x] Aplicador não vê provas de outros dias nem conteúdo da prova.
- [ ] ⚠ "Hoje" é calculado em UTC (`RelogioConsulta::hojeUtc()`, `now()` com fuso UTC); entre 21h e 24h no horário de Brasília o recorte já é o do dia seguinte. Resolve: subfase 1.1 (decisão de fuso, §8 do planejamento).

**Situação:** Implementado — evidência: `DashboardController::aplicador`, `Escopo.php`, `ConsultaVinculo.php`

---

## UC-059-03 — Ver as próximas provas nos dashboards

**Ator:** aluno, professor, coordenador, diretor escolar, administrador da rede
**Permissão:** perfil do dashboard (`perfil:` nas rotas `dashboard/*`)
**Escopo:** o do perfil
**Pré-condições:** provas com `data_aplicacao` futura
**Pós-condições:** nenhuma
**Tela/rota:** `dashboard/*.blade.php`; `DashboardController` (coordenador: "agenda" das provas dos próximos 7 dias, limite 4; dirEscolar e professor: próximas provas; aluno: próxima prova; admin: provas do mês)

**Fluxo principal:**
1. A API consulta `provas` do escopo por `data_aplicacao` e devolve a lista.
2. O dashboard exibe título, disciplina, data e situação.

**Fluxos alternativos e de erro:**
- 1a. Nenhuma prova futura: estado vazio do card.

**Regras de negócio:** UC-007 do docs/07.

**Critérios de aceite:**
- [x] Respeita o escopo do perfil.
- [ ] ⚠ Leitura direta de `provas`, não da agenda única; sem eventos não provenientes de provas. Resolve: subfase 2.2, após FN-056.

**Situação:** Implementado (leitura derivada) — evidência: `DashboardController` (métodos aluno, professor, coordenador, dirEscolar, admin)

---

## UC-059-04 — Registrar a aplicação na agenda

**Ator:** sistema, disparado por UC-059-01
**Permissão:** a de quem altera a prova, revalidada
**Escopo:** o da prova
**Pré-condições:** FN-056 C2 com contrato de integração
**Pós-condições:** evento da aplicação criado, atualizado ou cancelado
**Tela/rota:** serviço interno; visível na tela e nos cards de FN-056

**Fluxo principal:**
1. Ao gravar a data de aplicação, a API registra o evento com `origem_tipo=aplicacao`.
2. Mudança de data atualiza o mesmo evento; exclusão da prova ou remoção da data cancela o evento.

**Fluxos alternativos e de erro:**
- 1a. Reenvio da mesma operação: não duplica.

**Regras de negócio:** docs/28 C4 passos 1–2; docs/177 P5.4.1.

**Critérios de aceite:**
- [ ] Testes cobrem criação, mudança e cancelamento.
- [ ] Nenhum calendário de provas fora de FN-056.

**Situação:** Não implementado — subfase 1.1.

---

## UC-059-05 — Avisar e lembrar a aplicação

**Ator:** sistema (agendador); destinatários: coordenação, direção, núcleo e, se aprovado, professor e aplicador
**Permissão:** revalidada no envio
**Escopo:** o da prova
**Pré-condições:** FN-057 N1 e N3; modelos `prova-agendada` e `prova-lembrete` (FN-060)
**Pós-condições:** notificações e e-mails conforme preferências
**Tela/rota:** central de FN-057; e-mail

**Fluxo principal:**
1. Ao criar ou mudar a aplicação, notifica coordenação, direção e núcleo.
2. Na véspera, o agendador envia um lembrete único por aplicação.

**Fluxos alternativos e de erro:**
- 2a. Aplicação cancelada ou destinatário revogado antes do envio: não envia.

**Regras de negócio:** docs/28 C4 passos 3–4; docs/177 P5.4.4–P5.4.5 (a agenda funciona antes dos avisos; lembrete não se confunde com evento).

**Critérios de aceite:**
- [ ] Testes de destinatários e de lembrete não duplicado.

**Situação:** Não implementado — subfase 1.2.

---

## UC-059-06 — Registrar elegíveis e participação na aplicação

**Ator:** coordenador, professor, aplicador (registro de presença/ausência, conforme matriz a aprovar); sistema (captura dos elegíveis)
**Permissão:** a definir (por exemplo, `aplicacao.registrar`)
**Escopo:** turmas da aplicação
**Pré-condições:** contrato P1.4.6 validado; MT1 (FN-026) para o histórico de matrícula
**Pós-condições:** conjunto esperado de estudantes e estado de cada um gravados
**Tela/rota:** a desenhar (sem mockup)

**Fluxo principal:**
1. Ao abrir a aplicação, o sistema grava os estudantes elegíveis com a referência histórica.
2. Durante e após a aplicação, registram-se presença confirmada, ausência, dispensa; cartão recebido, processado e resultado válido vêm da correção (FN-045).

**Fluxos alternativos e de erro:**
- 2a. Ingresso tardio, transferência ou reaplicação: segue a política aprovada, sem reescrever relatórios.

**Regras de negócio:** docs/177 P1.4.6, P1.4.7 (elegibilidade sozinha não comprova participação), P3.3.5.

**Critérios de aceite:**
- [ ] Participação nunca é presumida a partir da elegibilidade.
- [ ] Aplicações antigas sem evidência ficam como indisponíveis, sem reconstrução.

**Situação:** Não implementado — subfase 2.1.
