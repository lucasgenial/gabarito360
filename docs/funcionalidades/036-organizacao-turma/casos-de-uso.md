# 036 — Organização da turma (TU1) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Coordenador, Diretor Escolar, Vice-diretor, Secretário Escolar, Professor, Professor autônomo, Administrador da Rede — ver docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-036-01 — Navegar pelas seções da turma

**Ator:** perfis com `turma.ver`
**Permissão:** `turma.ver`; cada seção exige a sua (ex.: notas exigem permissões pedagógicas; secretaria não vê notas)
**Escopo:** escola | turma | espaço pessoal
**Pré-condições:** turma no escopo.
**Pós-condições:** nenhuma.
**Tela/rota:** `turma-detalhe-2.html`; web `GET /turmas/{id}`, `GET /turmas/{id}/secao`; API `GET /v1/turmas/{id}`

**Fluxo principal:**
1. O ator abre a turma; vê Visão geral, Alunos, Disciplinas e professores, Provas e Acesso, conforme o que pode ver.
2. Troca de seção pelas abas (teclado: setas, Home/End, Enter/Espaço).
3. Formulários e paginação retornam à seção de origem.

**Fluxos alternativos e de erro:**
- 1a. Turma fora do escopo → 404 "Turma não encontrada."
- 2a. Falha na inicialização das abas → as seções continuam legíveis.

**Critérios de aceite:**
- [ ] Perfil administrativo e espaço pessoal recebem só as seções autorizadas.
- [ ] Nenhum identificador vai para a URL (só o fragmento da seção).

**Situação:** Implementado — evidência: views `turmas/show`, `_abas`; PR #203. ⚠ Conferência autenticada e aceite visual final pendentes (subfase 1.8).

---

## UC-036-02 — Vincular disciplina e professor à turma (operação composta)

**Ator:** Coordenador
**Permissão:** `turma.ver` + `disciplina.gerir` + `turma.vincular_professor`
**Escopo:** escola
**Pré-condições:** DC1 ativo; turma ativa; professor com vínculo aceito e vigente na mesma escola.
**Pós-condições:** componente criado se necessário, professor indicado na disciplina, acesso à turma concedido e atribuição registrada desde hoje, em uma transação.
**Tela/rota:** aba Disciplinas e professores; API `GET /v1/turmas/{id}/vinculo-docente/opcoes`, `POST /v1/turmas/{id}/vinculos-docentes`

**Fluxo principal:**
1. O ator escolhe a disciplina disponível e o professor cadastrado (sem CPF/e-mail).
2. Confere o par, a turma e os efeitos (indicação e acesso) e confirma.
3. A API grava tudo com recibo idempotente.

**Fluxos alternativos e de erro:**
- 2a. Turma inativa → 422 "A turma precisa estar ativa."; turma ou disciplina inativa → 422 "Turma e disciplina precisam estar ativas."
- 2b. Disciplina já com atribuição em curso ou programada → 409.
- 2c. Disciplina inativa na turma → 422 "Reative explicitamente a disciplina nesta turma antes de vincular."
- 2d. Professor sem vínculo aceito e vigente → 422 "O vínculo Professor precisa estar aceito e vigente."
- 3a. Chave reaproveitada com outro par → 409 "Esta chave já foi utilizada para outra operação."

**Regras de negócio:** sem sobreposição de papel docente na mesma disciplina da turma; fim exclusivo; sem autoconcessão (tu1-revisao-fluxos).

**Critérios de aceite:**
- [ ] Falha não deixa concessão parcial.
- [ ] Reenvio com a mesma chave não repete efeitos.
- [ ] Diretor e vice não recebem a gestão DC1.

**Situação:** Implementado — evidência: `VinculoDocenteCompostoController`, `App\Services\VinculoDocenteComposto`; PR #204.

---

## UC-036-03 — Transferir aluno entre turmas da mesma escola

**Ator:** Coordenador, Diretor Escolar, Vice-diretor, Secretário Escolar; Professor autônomo no espaço pessoal
**Permissão:** `aluno.gerir` e perfil autorizado (`MovimentarAlunoTurma::autorizado`)
**Escopo:** escola | espaço pessoal
**Pré-condições:** aluno ativo; turma de destino ativa na mesma escola e espaço; sem leitura OMR pendente/processando no espaço.
**Pós-condições:** aluno na nova turma; movimentação registrada (origem, destino, responsável, momento, motivo cifrado); resultados antigos permanecem na turma de aplicação.
**Tela/rota:** web `GET /alunos/{id}/transferir`, `POST /alunos/{id}/transferencias-turma` (modal com conferência); API `POST /v1/alunos/{id}/transferencias-turma`

**Fluxo principal:**
1. O ator escolhe a turma de destino e informa o motivo (5–500 caracteres).
2. Confere origem e destino e confirma; a API responde "Aluno transferido. Histórico preservado."

**Fluxos alternativos e de erro:**
- 2a. Turma de origem mudou → 409 "A turma do aluno mudou. Atualize os dados antes de confirmar."
- 2b. Aluno inativo → 422 "Ative o aluno antes de transferir."; mesma turma → 422 "Escolha outra turma."
- 2c. Outra escola ou espaço → 422 "A transferência deve ocorrer entre turmas da mesma escola…"
- 2d. Leitura em andamento ou histórico inconsistente → recusa (409).

**Critérios de aceite:**
- [ ] Notas antigas continuam na turma em que a prova ocorreu.
- [ ] Mesma chave não duplica a movimentação.

**Situação:** Implementado — evidência: `AlunoController::transferir`, `App\Services\MovimentarAlunoTurma`; PR #204–#206. Intervalos de matrícula completos → FN-026 (MT1).

---

## UC-036-04 — Arquivar e desarquivar turma ou aluno

**Ator:** Coordenador, Diretor Escolar, Secretário Escolar, Administrador da Rede; Professor autônomo
**Permissão:** `turma.desativar` / `aluno.desativar` (chaves legadas)
**Escopo:** escola | espaço pessoal
**Pré-condições:** cadastro no escopo.
**Pós-condições:** cadastro arquivado ou desarquivado com recibo; histórico preservado; turma arquivada não aceita nova associação de prova.
**Tela/rota:** web `PUT /turmas/{id}/arquivamento`, `PUT /alunos/{id}/arquivamento`; API idem em `/v1`

**Fluxo principal:**
1. O ator pede o arquivamento, informando motivo opcional (até 500).
2. A API confere `esperado_ativo` e `versao`, grava com chave UUID e responde "Cadastro arquivado. Histórico preservado." ou "Cadastro desarquivado.".

**Fluxos alternativos e de erro:**
- 2a. Estado mudou → 409 "O estado do cadastro mudou. Atualize antes de confirmar."
- 2b. Campos extras no pedido → 422.

**Critérios de aceite:**
- [ ] Reversível; nenhuma nota ou resultado apagado.

**Situação:** Implementado — evidência: `ArquivamentoCadastroController`, `App\Services\ArquivamentoCadastro`; PR #207. ⚠ Usa as chaves legadas `*.desativar` e os toggles `POST /turmas/{id}/toggle` e `/alunos/{id}/toggle` continuam ativos (subfase 1.9).

---

## UC-036-05 — Consultar o painel analítico da turma

**Ator:** perfis com `relatorio.turma` (Coordenador, Diretor, Vice-diretor, Professor, Administrador)
**Permissão:** `relatorio.turma`
**Escopo:** turma
**Pré-condições:** turma no escopo.
**Pós-condições:** nenhuma.
**Tela/rota:** aba Visão geral; API `GET /v1/turmas/{id}/painel`

**Fluxo principal:**
1. O painel mostra 6 KPIs e 4 gráficos com provas aplicadas e visíveis ao ator.
2. O ator filtra por disciplina (vínculo curricular), professor autor e período de aplicação.

**Fluxos alternativos e de erro:**
- 2a. Recorte sem dados → estado vazio explicado.

**Regras de negócio:** "professor" = autor registrado da prova; provas agendadas não entram; provas sem data só entram sem período delimitado.

**Critérios de aceite:**
- [ ] Secretaria não acessa notas nem análises.
- [ ] Agregados calculados no banco.

**Situação:** Implementado — evidência: `PainelTurmaController::show`; PR #207. Personalização e cards → FN-053/FN-054.

---

## UC-036-06 — Gerar PDF do recorte da turma

**Ator:** perfis com `relatorio.turma`
**Permissão:** `relatorio.turma`
**Escopo:** turma
**Pré-condições:** recorte aplicado no painel.
**Pós-condições:** PDF entregue; emissão registrada; arquivo temporário apagado.
**Tela/rota:** web `GET|POST /turmas/{id}/painel/pdf`; API `GET /v1/turmas/{id}/painel/pdf`

**Fluxo principal:**
1. O ator lê a orientação de impressão (modal) e escolhe retrato ou paisagem.
2. A API gera o PDF, revalida autorização e dados e entrega.

**Fluxos alternativos e de erro:**
- 2a. Dados mudaram durante a geração → 409 "Os dados mudaram. Gere novamente o relatório."
- 2b. Falha na geração → 503 "Não foi possível gerar o documento."

**Critérios de aceite:**
- [ ] Mesmo recorte do painel; cabeçalho compartilhado.

**Situação:** Parcial — evidência: `PainelTurmaController::pdf`; PR #204–#207. ⚠ Paridade com o novo quadro "Atenção/Top5" pendente → FN-050 (TU1-PDF).

---

## UC-036-07 — Editar a turma em página completa

**Ator:** perfis com `turma.gerir`
**Permissão:** `turma.gerir`
**Escopo:** escola | espaço pessoal
**Pré-condições:** turma no escopo.
**Pós-condições:** dados atualizados, incluindo sala, capacidade, etapa e observações.
**Tela/rota:** web `GET /turmas/{id}/editar`, `PUT /turmas/{id}`

**Fluxo principal:**
1. O ator abre "Editar turma", altera os campos agrupados e salva; retorna à turma.

**Regras de negócio:** capacidade é planejamento (sem bloqueio de matrícula); etapa é cadastral; observações não entram nos documentos.

**Critérios de aceite:**
- [ ] Erros associados aos campos; retorno à seção de origem.

**Situação:** Implementado — evidência: `turmas/edit`; PR #204. Regras de validação em [UC-033-05](../033-estrutura-escolar/casos-de-uso.md).
