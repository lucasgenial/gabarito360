# 022 — Espaço pessoal e professor autônomo (T3) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** `professor_autonomo` (proprietário do espaço pessoal). Ver docs/03 (`docs/03-perfis-e-permissoes.md`), atualização T3.
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Descreve o comportamento **atual**, marca com ⚠ as diferenças em relação ao planejado e aponta a subfase que as resolve.

---

## UC-022-01 — Entrar no espaço pessoal

**Ator:** professor autônomo
**Permissão:** vínculo `professor_autonomo` válido (sem chave específica)
**Escopo:** espaço pessoal
**Pré-condições:** conta, cliente e espaço pessoal ativos; `usuario_proprietario_id` = conta; `espacos.pessoal = true`.
**Pós-condições:** aba com contexto do espaço pessoal; painel do professor com dados apenas desse espaço.
**Tela/rota:** `/app/vinculos` (seleção por aba, FN-015); `GET /api/v1/dashboard/professor` (`perfil:professor,professor_autonomo`).

**Fluxo principal:**
1. O professor autentica (UC-001 do docs/07 (`docs/07-casos-de-uso.md`)).
2. Escolhe o vínculo do espaço pessoal na lista de vínculos.
3. O painel mostra turmas e provas do espaço pessoal, com o nome do espaço no cabeçalho.

**Fluxos alternativos e de erro:**
- 2a. Espaço ou cliente suspenso, ou vínculo de outra conta → vínculo indisponível (`403`).
- 2b. Mesma conta com vínculo escolar: os contextos ficam isolados por aba; o pessoal não vê dados da escola.

**Critérios de aceite:**
- [ ] Dois espaços pessoais fictícios não se enxergam.
- [ ] Menu sem gestão de equipe, escolas e associação de professores.

**Situação:** Implementado — evidência: `PerfisEspaco`; `IntegracaoPessoalTest`, `IntegracaoPessoalWebTest`; PR #99.

---

## UC-022-02 — Cadastrar turma pessoal

**Ator:** professor autônomo
**Permissão:** `turma.gerir`
**Escopo:** espaço pessoal
**Pré-condições:** contexto pessoal (UC-022-01).
**Pós-condições:** turma com `escola_id` nulo e `espaco_tipo = pessoal`.
**Tela/rota:** formulário de turma; `POST /api/v1/turmas`.

**Fluxo principal:**
1. O professor informa os dados da turma.
2. A API grava com escola nula e tipo pessoal; não oferece coluna ou seleção de escola.

**Fluxos alternativos e de erro:**
- 2a. Turma com escola nula fora do espaço pessoal → recusada por trigger/FK composta.

**Critérios de aceite:**
- [ ] Turma pessoal não aparece em nenhuma consulta institucional.

**Situação:** Implementado — evidência: `TurmaController.php` (ramo `$pessoal`); docs/64.

---

## UC-022-03 — Cadastrar aluno com dados mínimos

**Ator:** professor autônomo
**Permissão:** `aluno.gerir`
**Escopo:** espaço pessoal
**Pré-condições:** turma pessoal existente.
**Pós-condições:** aluno com `espaco_tipo = pessoal`, somente com os campos permitidos.
**Tela/rota:** formulário de aluno; `POST/PUT /api/v1/alunos`.

**Fluxo principal:**
1. O professor informa nome, turma e identificação simples; pode enviar retrato.
2. `EspacoPessoal::validarAluno` recusa campos vedados; a API grava.

**Fluxos alternativos e de erro:**
- 1a. Envio de `cpf`, `data_nascimento`, `genero`, `nome_responsavel` → `422` (campo proibido); trigger reforça no banco.
- 1b. Troca de turma pela edição → `AtualizarAlunoPessoal`, sem registro em `aluno_movimentacoes`.

**Regras de negócio:** docs/64; termos de uso (cadastro limitado).

**Critérios de aceite:**
- [ ] Nenhum campo vedado é persistido, nem por API direta.

**Situação:** Implementado — evidência: `apps/api/app/Support/EspacoPessoal.php`; migration `2026_11_01_000006_preparar_dados_pessoais`.

⚠ docs/115 §10 propõe CPF opcional, protegido e verificado no aluno pessoal → revisão formal de T3 (subfase 1.4) e FN-027 (PA1-B).

---

## UC-022-04 — Criar prova, imprimir cartões e corrigir no espaço pessoal

**Ator:** professor autônomo
**Permissão:** `prova.criar`, `gabarito.editar`, `cartao.imprimir`, `cartao.enviar`, `correcao.*`
**Escopo:** espaço pessoal
**Pré-condições:** turma pessoal com alunos.
**Pós-condições:** prova com `escola_id` nulo; cartões com o nome do espaço; leituras processadas na fila do espaço.
**Tela/rota:** fluxos de UC-003 e UC-004 do docs/07 (`docs/07-casos-de-uso.md`); `ProvaController` (ramo pessoal), fila B6.

**Fluxo principal:**
1. Segue UC-003 (criar prova e gabarito) e UC-004 (acompanhar correção).
2. PDFs dos cartões usam o nome do espaço pessoal, sem rede, escola ou logo institucional.
3. Permissão e vínculo são revalidados após a geração e no worker.

**Fluxos alternativos e de erro:**
- 3a. Revogação durante a geração → entrega recusada.

**Critérios de aceite:**
- [ ] Job serializado com vínculo pessoal; QR de outro espaço recusado.

**Situação:** Implementado — evidência: `ProvaController.php:220`; `IntegracaoPessoalTest`; docs/65.

---

## UC-022-05 — Consultar resultados e relatórios pessoais

**Ator:** professor autônomo
**Permissão:** `resultado.ver`, `resultado.compartilhar`, `relatorio.prova`, `relatorio.turma`
**Escopo:** espaço pessoal
**Pré-condições:** prova corrigida.
**Pós-condições:** relatórios e PDFs com cabeçalho "Uso autônomo".
**Tela/rota:** UC-005, UC-006 e UC-010 do docs/07 (`docs/07-casos-de-uso.md`); `relatorios/_cabecalho.blade.php`.

**Fluxo principal:**
1. O professor abre o resultado individual ou o relatório da turma/prova.
2. O documento usa aprovação 6/10 e média de referência 7/10 e preserva a turma da aplicação.

**Critérios de aceite:**
- [ ] Resultado após movimentação do aluno mostra a turma da aplicação.

**Situação:** Implementado — evidência: docs/64, 65; `EmissaoDocumento` (rótulo "Professor autônomo").

⚠ Parâmetros fixos; a tela de parâmetros pessoais é a subfase 1.5 (UC-022-06).

---

## UC-022-06 — Configurar parâmetros do espaço pessoal

**Ator:** professor autônomo
**Permissão:** a definir (proposta: nenhuma extra; ação do proprietário)
**Escopo:** espaço pessoal
**Pré-condições:** contexto pessoal.
**Pós-condições:** metas próprias usadas nos relatórios do espaço.
**Tela/rota:** sem mockup; a definir.

**Fluxo principal (proposto):**
1. O professor abre os parâmetros do espaço.
2. Define nota de aprovação e média de referência.
3. Relatórios novos passam a usar os valores; documentos já emitidos não mudam.

**Critérios de aceite:**
- [ ] Alterar parâmetros não reescreve PDFs emitidos.

**Situação:** Não implementado — evidência: docs/64 ("não há tela nova de parâmetros"); subfase 1.5.

---

## UC-022-07 — Provisionar o espaço pessoal de um professor

**Ator:** operador interno (Suporte/Desenvolvedor/Financeiro) — proposto
**Permissão:** a definir (`cliente.gerir` não cria espaços hoje)
**Escopo:** plataforma
**Pré-condições:** contratação acordada (FN-023/FN-068).
**Pós-condições:** cliente `professor`, espaço `pessoal` com proprietário e vínculo `professor_autonomo` aceito.
**Tela/rota:** não existe.

**Fluxo principal (proposto):**
1. Operador cadastra o cliente (UC-023-01).
2. Cria o espaço pessoal para a conta do professor e o vínculo correspondente.
3. O professor aceita o convite e passa a ver o espaço (UC-022-01).

**Situação:** Não implementado — ⚠ nenhum código da aplicação cria espaço `pessoal` (só scripts de ensaio); a lacuna não tem subfase no plano mestre. Registrado em §8 do [planejamento](planejamento.md).
