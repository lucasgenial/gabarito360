# 035 — Dados dos responsáveis — Casos de uso

**Natureza:** nova
**Perfis envolvidos:** Secretário Escolar, Coordenador, Diretor Escolar, Auxiliar de secretaria (cadastro); perfis com `aluno.ver` (consulta) — ver docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-035-01 — Informar o nome do responsável (comportamento atual)

**Ator:** perfis com `aluno.gerir`
**Permissão:** `aluno.gerir`
**Escopo:** escola | espaço pessoal
**Pré-condições:** aluno no escopo.
**Pós-condições:** `nome_responsavel` gravado em texto.
**Tela/rota:** web `alunos/create`, `alunos/edit`, `alunos/show`; API `POST/PUT /v1/alunos`

**Fluxo principal:**
1. O ator preenche "Nome do responsável (pai/mãe/tutor)" (opcional, até 200).
2. O detalhe do aluno mostra "Responsável" quando preenchido.

**Critérios de aceite:**
- [ ] Campo opcional; até 200 caracteres.

**Situação:** Parcial — evidência: `AlunoController` (`nome_responsavel`), views `alunos/*`. ⚠ Um único nome em texto livre, sem contatos e sem cifragem; a forma estruturada é a subfase 1.1.

---

## UC-035-02 — Cadastrar responsáveis do aluno

**Ator:** Secretário Escolar, Coordenador, Diretor Escolar
**Permissão:** a definir (base `aluno.gerir`)
**Escopo:** escola
**Pré-condições:** aluno no escopo; subfase 1.1 entregue.
**Pós-condições:** responsável associado ao aluno com papel; contatos cifrados.
**Tela/rota:** bloco "Responsáveis" no cadastro/edição do aluno (a desenhar)

**Fluxo principal:**
1. O ator abre o aluno e adiciona um responsável: nome, papel, contatos opcionais.
2. O sistema grava os contatos cifrados e associa ao aluno.
3. O ator pode editar ou remover a associação.

**Fluxos alternativos e de erro:**
- 1a. Responsável já cadastrado para outro aluno da mesma escola → associar sem duplicar a pessoa.

**Critérios de aceite:**
- [ ] Contatos nunca aparecem em texto aberto no banco.
- [ ] Um aluno pode ter mais de um responsável; um responsável pode ter mais de um aluno.

**Situação:** Não implementado — origem: docs/28 A6 passos 1–3 (subfases 1.1 e 1.2).

---

## UC-035-03 — Listar alunos com dados pendentes

**Ator:** Secretário Escolar, Diretor Escolar, Coordenador
**Permissão:** `aluno.ver`
**Escopo:** escola
**Pré-condições:** —
**Pós-condições:** nenhuma.
**Tela/rota:** a definir

**Fluxo principal:**
1. O ator consulta os alunos sem data de nascimento ou sem responsável.
2. Abre cada cadastro para completar.

**Critérios de aceite:**
- [ ] Lista restrita ao escopo do ator.

**Situação:** Não implementado — origem: docs/28 A6 passo 4 (subfase 1.3).
