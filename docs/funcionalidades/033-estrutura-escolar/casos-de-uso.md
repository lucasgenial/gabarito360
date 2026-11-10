# 033 — Núcleos, escolas, turmas e alunos — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Vice-diretor, Secretário Escolar, Coordenador, Professor, Professor autônomo (espaço pessoal) — ver docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-033-01 — Manter núcleos

**Ator:** Administrador da Rede
**Permissão:** `nucleo.ver` (listar/ver); `nucleo.gerir` (criar/editar/excluir)
**Escopo:** rede
**Pré-condições:** usuário autenticado com vínculo ativo; rede no escopo (`Instituicoes::redesIds`).
**Pós-condições:** núcleo criado/alterado/excluído; com espaços ativos, criação de espaço do tipo núcleo.
**Tela/rota:** sem tela web; API `GET/POST /v1/nucleos`, `GET/PUT/DELETE /v1/nucleos/{id}`

**Fluxo principal:**
1. O ator envia `nome` (até 200), `rede_id` e `orgao_regional` (opcional, até 20).
2. A API confere que o perfil é `admin_rede` e que a rede está no escopo; cria o núcleo e o espaço em transação.
3. Listagem devolve id, rede, nome, órgão regional e total de escolas no escopo do ator.

**Fluxos alternativos e de erro:**
- 2a. Perfil diferente de `admin_rede` ou rede fora do escopo → 404.
- 3a. Edição com troca de `rede_id` e espaços ativos → 422 "Mudança de rede exige procedimento próprio."
- 3b. Exclusão de núcleo com escolas → 422 "Núcleo possui escolas vinculadas. Reatribua-as antes de excluir."
- 3c. Exclusão de núcleo com espaço registrado → 422 "Núcleo com espaço registrado; encerramento exige procedimento próprio."

**Regras de negócio:** docs/06 (hierarquia rede → núcleo → escola).

**Critérios de aceite:**
- [ ] Só `admin_rede` cria núcleo, e só em rede do próprio escopo.
- [ ] Núcleo com escolas ou espaço não é excluído.

**Situação:** Implementado (API) — evidência: `apps/api/app/Http/Controllers/Api/NucleoController.php`. ⚠ Não há tela web (MP-014 "tela a definir"); dados do núcleo e `nucleo.editar` são o A3 (subfase 2.1).

---

## UC-033-02 — Cadastrar escola

**Ator:** Administrador da Rede; Diretor de Núcleo (no próprio núcleo)
**Permissão:** `escola.criar`; consulta ao Censo também exige `escola.criar`
**Escopo:** rede | núcleo
**Pré-condições:** núcleo no escopo do ator.
**Pós-condições:** escola criada; com espaços ativos, espaço da escola criado.
**Tela/rota:** `escolas.html`; web `GET /escolas/nova`, `POST /escolas`, `GET /escolas/censo/buscar|pesquisar`; API `POST /v1/escolas`, `GET /v1/censo/escolas[/{inep}]`

**Fluxo principal:** segue UC-002 do docs/07 (`docs/07-casos-de-uso.md`), com as diferenças:
1. O ator pode pesquisar a escola no Censo (INEP) e pré-preencher os dados.
2. Obrigatórios: `nucleo_id`, `nome`, `inep` (8 dígitos, único), `tipo_rede` (estadual, municipal, federal, privada).
3. Opcionais: sigla, tipo (sede, anexo, unidade vinculada) e escola sede, situação de funcionamento, localização, endereço, CEP, município (código IBGE), UF, coordenadas, telefone, e-mail, turnos, etapas, modalidades.

**Fluxos alternativos e de erro:**
- 2a. Anexo sem sede → 422 "Escolha a escola sede deste anexo."; sede de outro núcleo → 422 "O anexo precisa ser do mesmo núcleo da escola sede."
- 2b. Núcleo fora do escopo → 404 (com espaços) ou 403 "Você só pode cadastrar escolas no seu núcleo."

**Critérios de aceite:**
- [ ] Diretor de Núcleo só cadastra no próprio núcleo.
- [ ] INEP duplicado é recusado.

**Situação:** Implementado — evidência: `apps/api/app/Http/Controllers/Api/EscolaController.php` (`store`, `regras`, `validarSede`). ⚠ O campo "Diretor(a)" do mockup não é campo da escola; a direção vem dos vínculos (FN-012/FN-015).

---

## UC-033-03 — Editar, ativar/desativar escola e enviar logo

**Ator:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar (editar e logo); Vice-diretor por extra concedível
**Permissão:** `escola.editar`; `escola.desativar`; `escola.logo`; `rede.logo` (logo da rede)
**Escopo:** rede | núcleo | escola
**Pré-condições:** escola no escopo.
**Pós-condições:** dados, situação ou logo atualizados.
**Tela/rota:** web `GET /escolas/{id}/editar`, `PUT /escolas/{id}`, `POST /escolas/{id}/toggle`, `POST /escolas/{id}/logo`; API equivalentes em `/v1/escolas`

**Fluxo principal:**
1. O ator altera os campos permitidos e salva.
2. Toggle alterna `ativo` e responde "Escola ativada." / "Escola desativada.".
3. Logo: imagem PNG/JPG/WEBP até 2 MB.

**Fluxos alternativos e de erro:**
- 1a. Troca de núcleo com espaços ativos → 422 "Mudança de núcleo exige procedimento próprio."
- 1b. Sede com anexos mudando de tipo → 422 "Esta escola tem anexos; ela precisa continuar como sede."
- 1c. Escola fora do escopo → 404 "Escola não encontrada."

**Critérios de aceite:**
- [ ] Diretor Escolar edita só a própria escola.
- [ ] Logo fora do formato/tamanho é recusada.

**Situação:** Implementado — evidência: `EscolaController::update`, `toggle`, `enviarLogo`, `enviarLogoRede`.

---

## UC-033-04 — Consultar escolas e detalhe da escola

**Ator:** perfis com `escola.ver` (Administrador, Diretor de Núcleo, Diretor Escolar, Coordenador, Professor, Aplicador, Secretário, Vice-diretor)
**Permissão:** `escola.ver`
**Escopo:** rede | núcleo | escola
**Pré-condições:** autenticado.
**Pós-condições:** nenhuma.
**Tela/rota:** `escolas.html`, `escola-detalhe.html`; web `GET /escolas`, `GET /escolas/{id}`; API `GET /v1/escolas`, `GET /v1/escolas/{id}`

**Fluxo principal:**
1. A lista mostra as escolas no escopo, com busca por nome.
2. O detalhe mostra dados, turmas com total de alunos, equipe e alunos com notas, conforme o escopo.

**Fluxos alternativos e de erro:**
- 2a. Escola fora do escopo → 404.

**Critérios de aceite:**
- [ ] Nenhuma escola fora do escopo aparece na lista ou no detalhe.

**Situação:** Implementado — evidência: `EscolaController::index`, `show`.

---

## UC-033-05 — Cadastrar e editar turma

**Ator:** Administrador da Rede, Diretor Escolar, Coordenador, Secretário Escolar; Professor autônomo no espaço pessoal; Vice-diretor por extra
**Permissão:** `turma.gerir` (criar/editar); `turma.desativar` para criar já inativa
**Escopo:** escola | espaço pessoal
**Pré-condições:** escola no escopo (`Cadastro::escolaNoEscopo`).
**Pós-condições:** turma criada/alterada com autoria de espaço.
**Tela/rota:** `turmas.html`; web `POST /turmas`, `GET /turmas/{id}/editar`, `PUT /turmas/{id}`; API `POST /v1/turmas`, `PUT /v1/turmas/{id}`

**Fluxo principal:**
1. O ator informa `escola_id` (proibido no espaço pessoal), `nome` (até 50), `serie` (até 50), `turno` (manha, tarde, noite, integral), `ano_letivo` (2020–2099) e, opcionalmente, sala, capacidade (1–1000), etapa de ensino e observações (até 1000).
2. A API cria a turma pelo serviço `ArquivamentoCadastro::criar`, que trava o ator e confere a permissão.

**Fluxos alternativos e de erro:**
- 1a. Escola fora do escopo → 404.
- 2a. Edição que muda de escola → 422 "Transferência entre escolas exige procedimento próprio."

**Critérios de aceite:**
- [ ] Turma criada só em escola do escopo.
- [ ] No espaço pessoal, a turma fica sem escola e com `espaco_tipo = pessoal`.

**Situação:** Implementado — evidência: `apps/api/app/Http/Controllers/Api/TurmaController.php` (`store`, `update`). Página de edição completa e campos opcionais vieram de FN-036 (TU1-R1/R2).

---

## UC-033-06 — Consultar turmas

**Ator:** perfis com `turma.ver`
**Permissão:** `turma.ver` (professor: só as suas)
**Escopo:** escola | turma | espaço pessoal
**Pré-condições:** autenticado.
**Pós-condições:** nenhuma.
**Tela/rota:** `turmas.html`; web `GET /turmas`; API `GET /v1/turmas`

**Fluxo principal:**
1. A lista mostra turmas no escopo, com busca, total de alunos, média e estado de arquivamento.

**Critérios de aceite:**
- [ ] Professor não vê turmas sem vínculo.

**Situação:** Implementado — evidência: `TurmaController::index`. ⚠ O botão "Importar planilha" do mockup não existe → FN-034. Detalhe da turma → [FN-036](../036-organizacao-turma/casos-de-uso.md).

---

## UC-033-07 — Cadastrar aluno

**Ator:** Diretor Escolar, Coordenador, Secretário Escolar, Auxiliar de secretaria; Professor autônomo; Administrador da Rede
**Permissão:** `aluno.gerir`; `aluno.desativar` para criar já inativo
**Escopo:** escola | espaço pessoal
**Pré-condições:** turma ativa no escopo.
**Pós-condições:** aluno criado na turma, com autoria de espaço.
**Tela/rota:** `aluno-cadastrar.html`; web `GET /alunos/novo`, `POST /alunos`; API `POST /v1/alunos`

**Fluxo principal:**
1. O ator informa turma, nome (até 200), matrícula (até 20, única no espaço), data de nascimento (obrigatória na escola; proibida no espaço pessoal) e, opcionalmente, CPF (11 dígitos, único e protegido), gênero (M, F, O), nome do responsável, avatar (1–36) ou foto.
2. A API confere a turma ativa e grava o aluno; no espaço pessoal grava só os dados mínimos.

**Fluxos alternativos e de erro:**
- 1a. Matrícula ou CPF já usados no espaço → erro de validação.
- 2a. Turma arquivada ou fora do escopo → 404.
- 2b. Falha ao gravar com foto enviada → a foto é apagada.

**Critérios de aceite:**
- [ ] Matrícula não se repete no mesmo espaço.
- [ ] Espaço pessoal não guarda data de nascimento.

**Situação:** Implementado — evidência: `apps/api/app/Http/Controllers/Api/AlunoController.php` (`store`). ⚠ "Responsável" é texto livre `nome_responsavel`; dados estruturados → FN-035.

---

## UC-033-08 — Consultar e editar aluno

**Ator:** perfis com `aluno.ver` (consulta) e `aluno.gerir` (edição)
**Permissão:** `aluno.ver`; `aluno.gerir`; `aluno.desativar` para alterar `ativo`
**Escopo:** escola | turma (professor: alunos das suas turmas) | espaço pessoal
**Pré-condições:** aluno no escopo.
**Pós-condições:** cadastro atualizado; troca de turma passa pelo serviço protegido de movimentação.
**Tela/rota:** `aluno-detalhe.html`, `aluno-editar.html`; web `GET /alunos`, `GET /alunos/{id}`, `GET /alunos/{id}/editar`, `PUT /alunos/{id}`; API `GET /v1/alunos`, `GET/PUT /v1/alunos/{id}`

**Fluxo principal:**
1. A lista filtra por turma, busca (nome ou matrícula) e situação; mostra KPIs de total e ativos.
2. O detalhe mostra dados, turma e estado de arquivamento.
3. A edição aceita os mesmos campos do cadastro (foto proibida no PUT; usa `POST /v1/alunos/{id}/imagem`).

**Fluxos alternativos e de erro:**
- 3a. Troca de turma para outra escola → 422 "Transferência entre escolas exige procedimento próprio."
- 3b. Aluno fora do escopo → 404 "Aluno não encontrado."

**Critérios de aceite:**
- [ ] Professor só vê alunos das próprias turmas.
- [ ] Troca de turma no PUT usa o mesmo serviço da transferência (FN-036).

**Situação:** Parcial — evidência: `AlunoController::index`, `show`, `update`. ⚠ A "Ficha do Aluno (PDF)" do mockup não foi encontrada (subfase 1.5, Incerto). ⚠ KPIs de frequência e filtro por bimestre do mockup não existem (períodos → FN-039).

---

## UC-033-09 — Vincular professor à turma (legado MP-018)

**Ator:** Administrador da Rede, Diretor Escolar, Coordenador; Vice-diretor por extra
**Permissão:** `turma.vincular_professor` (vincular/desvincular); `turma.ver` (listar)
**Escopo:** escola
**Pré-condições:** turma e professor na mesma escola.
**Pós-condições:** professor passa a ver a turma.
**Tela/rota:** web `POST /turmas/{id}/professores`, `DELETE /turmas/{id}/professores/{uid}`; API `GET/POST /v1/turmas/{id}/professores`, `DELETE /v1/turmas/{id}/professores/{usuarioId}`, `GET /v1/usuarios/{id}/turmas`

**Fluxo principal:**
1. O ator escolhe o professor e confirma; a API registra o vínculo.

**Critérios de aceite:**
- [ ] Professor de outra escola é recusado.

**Situação:** Implementado — evidência: `VinculoProfessorController`. Com DC1 ativo, a operação recomendada é a composta disciplina + professor de [FN-036](../036-organizacao-turma/casos-de-uso.md) / [FN-038](../038-disciplinas-curriculo/casos-de-uso.md).

---

## UC-033-10 — Editar dados do núcleo (A3)

**Ator:** Diretor de Núcleo (próprio núcleo); Administrador da Rede
**Permissão:** `nucleo.editar` (a criar)
**Escopo:** núcleo
**Pré-condições:** vínculo ativo de núcleo.
**Pós-condições:** código, endereço, cidade, contatos, responsável e logo do núcleo atualizados; logo usada nos relatórios.
**Tela/rota:** sem mockup; proposta `PUT /v1/nucleos/{id}` e tela de edição

**Fluxo principal:**
1. O ator abre os dados do próprio núcleo, altera e salva.

**Fluxos alternativos e de erro:**
- 1a. Núcleo de outro diretor → 404.

**Critérios de aceite:**
- [ ] Diretor de Núcleo edita só o próprio núcleo.
- [ ] Logo do núcleo aparece nos relatórios do núcleo.

**Situação:** Não implementado — origem: docs/28 A3 (subfase 2.1).

---

## UC-033-11 — Consultar equipe do núcleo separada da escola (A4)

**Ator:** Diretor de Núcleo, Administrador da Rede, Diretor Escolar
**Permissão:** a definir em A4 (base `usuario.ver`)
**Escopo:** núcleo | escola
**Pré-condições:** A3 concluído.
**Pós-condições:** nenhuma.
**Tela/rota:** proposta "Equipe do núcleo"; "Equipe da escola" limitada a vínculos de escola

**Fluxo principal:**
1. "Equipe do núcleo" lista só pessoas com vínculo de núcleo.
2. "Equipe da escola" lista só pessoas com vínculo de escola.
3. Uma pessoa com os dois vínculos aparece nas duas listas, sem duplicar o cadastro.

**Critérios de aceite:**
- [ ] Cada lista mostra só o que pertence a ela; testes cobrem o escopo.
- [ ] `usuarios.escola_nome` deixa de ser usado.

**Situação:** Não implementado — origem: docs/28 A4 (subfase 2.2).
