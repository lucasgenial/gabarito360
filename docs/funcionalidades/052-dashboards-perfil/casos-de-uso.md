# 052 — Painéis por perfil — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Secretário de Educação, Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Vice-diretor, Coordenador, Professor, Professor autônomo, Aplicador, Aluno. Ver docs/03 (`docs/03-perfis-e-permissoes.md`) §3.
**Atualizado em:** 2026-11-09

Descreve o comportamento **atual**; diferenças em relação ao planejado estão marcadas com ⚠. Detalha o UC-007 do docs/07 (`docs/07-casos-de-uso.md`), sem repeti-lo.

---

## UC-052-01 — Abrir o painel inicial do perfil

**Ator:** qualquer pessoa autenticada com vínculo escolhido
**Permissão:** ⚠ não há chave de permissão; a API usa o middleware `perfil:` em cada rota `dashboard/*` (migração pendente em FN-012)
**Escopo:** o do vínculo (rede, núcleo, escola, turmas próprias, próprio aluno)
**Pré-condições:** sessão válida e vínculo selecionado (FN-015).
**Pós-condições:** nenhuma alteração.
**Tela/rota:** mockups `dashboard-*.html`; web `painel` (`/app/painel`); API `GET v1/dashboard/{admin|dir-nucleo|dir-escolar|coordenador|professor|aluno|secretaria|aplicador}`

**Fluxo principal:**
1. Após o login e a escolha do vínculo, a pessoa chega ao painel.
2. O Web identifica o perfil e chama o endpoint correspondente.
3. O sistema mostra a vista do perfil: `admin`, `diretor-nucleo`, `diretor-escolar` (também para o vice-diretor), `coordenador`, `professor` (também para o professor autônomo), `aluno`, `secretaria`, `aplicador`.

**Fluxos alternativos e de erro:**
- 2a. Secretário escolar e auxiliar: vista administrativa própria (FN-014). Acesso interno: vista interna (FN-013).
- 2b. Falha da API: o painel abre sem dados.
- 2c. Perfil sem vínculo com unidade: 403 ("Usuário não vinculado a nenhuma escola." ou equivalente).

**Regras de negócio:** docs/03 §3; o escopo é aplicado na API.

**Critérios de aceite:**
- [x] Cada perfil só alcança o endpoint do seu painel.
- [x] Os dados respeitam o espaço e o escopo do vínculo.

**Situação:** Implementado — evidência: `DashboardController` (API e web); testes `DashboardDesempenhoContratoTest`, `F2DashboardLotesTest`

---

## UC-052-02 — Escolher a unidade quando há mais de uma

**Ator:** pessoa com mais de uma escola, núcleo, secretaria ou aluno de referência no vínculo
**Permissão:** a mesma do UC-052-01
**Escopo:** referências do próprio vínculo
**Pré-condições:** mais de uma referência possível.
**Pós-condições:** painel aberto para a unidade escolhida.
**Tela/rota:** web `painel` com `escola_id`, `nucleo_id`, `secretaria_id` ou `aluno_id`; vista `dashboard.selecionar-contexto`

**Fluxo principal:**
1. A API responde 422 "Selecione o contexto da consulta." com o campo e as opções.
2. O Web mostra a tela de seleção.
3. A pessoa escolhe e o painel é carregado.

**Fluxos alternativos e de erro:**
- 3a. Identificador fora das referências: a seleção não é aceita.

**Critérios de aceite:**
- [x] Só aparecem opções do próprio vínculo.

**Situação:** Implementado — evidência: `DashboardController::escolha` (API), `Instituicoes::selecionarReferencia`

---

## UC-052-03 — Acompanhar a rede, o núcleo ou a secretaria

**Ator:** Administrador da Rede, Diretor de Núcleo, Secretário de Educação
**Permissão:** `perfil:admin_rede`, `perfil:dir_nucleo`, `perfil:secretario_educacao`
**Escopo:** rede; escolas do núcleo; redes da secretaria (somente leitura)
**Pré-condições:** dados cadastrados.
**Pós-condições:** nenhuma.
**Tela/rota:** mockups `dashboard-admin.html`, `dashboard-diretor-nucleo.html`; vistas `dashboard.admin`, `dashboard.diretor-nucleo`, `dashboard.secretaria`

**Fluxo principal:**
1. O painel mostra os indicadores consolidados e o comparativo das unidades do nível abaixo, com atalhos para os relatórios (FN-049).

**Fluxos alternativos e de erro:**
- ⚠ Alerta SEGES: o painel do administrador lê `SincronizacaoSeges`, mas não há serviço de integração (subfase 1.3).
- ⚠ "Agendar visita" e "Próximas agendas em campo" do mockup do núcleo (UC-009) não existem: aguardam FN-056 e FN-058.
- ⚠ O atalho do Secretário de Educação para o relatório da rede não funciona no código atual (ver UC-049-07).

**Critérios de aceite:**
- [x] O Diretor de Núcleo só vê as escolas do seu núcleo.

**Situação:** Parcial — implementado sem SEGES operante e sem visitas. Evidência: `DashboardController::admin/dirNucleo/secretaria`

---

## UC-052-04 — Acompanhar a escola

**Ator:** Diretor Escolar, Vice-diretor, Coordenador
**Permissão:** `perfil:dir_escolar,vice_dir_escolar`; `perfil:coordenador`
**Escopo:** a escola do vínculo
**Pré-condições:** escola com turmas e provas.
**Pós-condições:** nenhuma.
**Tela/rota:** mockups `dashboard-diretor-escolar.html`, `dashboard-coordenador.html`; vistas `dashboard.diretor-escolar`, `dashboard.coordenador`

**Fluxo principal:**
1. O painel mostra indicadores da escola, provas em andamento com progresso da correção e atalhos para correção e relatórios.

**Fluxos alternativos e de erro:**
- ⚠ A agenda do mockup do coordenador não existe como dado real: aguarda FN-056.

**Critérios de aceite:**
- [x] O vice-diretor usa a mesma vista do diretor escolar.

**Situação:** Implementado — evidência: `DashboardController::dirEscolar/coordenador`; parcial `_provas-ciclo`

---

## UC-052-05 — Acompanhar as próprias turmas e provas (professor)

**Ator:** Professor, Professor autônomo
**Permissão:** `perfil:professor,professor_autonomo`
**Escopo:** turmas vinculadas (professor); espaço pessoal (autônomo)
**Pré-condições:** vínculo com turmas ou espaço pessoal.
**Pós-condições:** nenhuma.
**Tela/rota:** mockup `dashboard-professor.html`; vista `dashboard.professor`

**Fluxo principal:**
1. O painel mostra as turmas e provas do professor, com atalhos "Resultados" (relatório da prova) e "Acompanhar" (correção).

**Critérios de aceite:**
- [x] O professor não vê provas de turmas que não são dele.

**Situação:** Implementado — evidência: `DashboardController::professor`

---

## UC-052-06 — Ver o painel do aplicador

**Ator:** Aplicador
**Permissão:** `perfil:aplicador`
**Escopo:** provas da escola com data de aplicação igual a hoje
**Pré-condições:** vínculo com uma escola.
**Pós-condições:** nenhuma.
**Tela/rota:** vista `dashboard.aplicador` (sem mockup próprio)

**Fluxo principal:**
1. O painel lista as provas do dia com turmas, total de alunos e cartões enviados.
2. O aplicador segue para a câmera da prova (UC-045-02).

**Critérios de aceite:**
- [x] Nenhuma nota, gabarito ou relatório é devolvido ao aplicador.

**Situação:** Implementado — evidência: `DashboardController::aplicador`, commit 917dd81 (⚠ o plano mestre marca MP-040 como Incerto; ver planejamento, item 8)

---

## UC-052-07 — Ver o próprio painel (aluno)

**Ator:** Aluno
**Permissão:** `perfil:aluno`
**Escopo:** o próprio aluno, resolvido pela referência do vínculo
**Pré-condições:** conta de aluno vinculada a um cadastro de aluno.
**Pós-condições:** nenhuma.
**Tela/rota:** mockup `dashboard-aluno.html`; vista `dashboard.aluno`

**Fluxo principal:**
1. O painel mostra as notas e provas do próprio aluno, com acesso ao resultado (UC-047-04).

**Fluxos alternativos e de erro:**
- ⚠ O docs/03 ainda descreve o vínculo usuário↔aluno como pendente; o código resolve por referência. A forma de acesso do aluno depende da decisão §4 item 10 (FN-020).

**Critérios de aceite:**
- [x] O aluno nunca vê dados de outro aluno.

**Situação:** Parcial — implementado; uso real depende de FN-020. Evidência: `DashboardController::aluno`
