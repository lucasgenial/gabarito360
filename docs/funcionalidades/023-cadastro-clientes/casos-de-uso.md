# 023 — Cadastro de clientes (T4) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Desenvolvedor, Suporte, Financeiro (contexto interno). Perfis escolares e pessoais não têm acesso. Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Descreve o comportamento **atual** e marca com ⚠ as diferenças em relação ao planejado.

---

## UC-023-01 — Cadastrar cliente

**Ator:** Desenvolvedor, Suporte ou Financeiro
**Permissão:** `cliente.gerir` (contexto interno)
**Escopo:** plataforma
**Pré-condições:** acessos internos habilitados; contexto interno ativo e revalidado; conta ativa.
**Pós-condições:** cliente `ativo` criado com `criado_por`; evento `cadastro` gravado na mesma transação.
**Tela/rota:** `/app/clientes/novo` → `POST /app/clientes`; `POST /api/v1/clientes`.

**Fluxo principal:**
1. O ator abre "Clientes" e escolhe cadastrar.
2. Informa tipo, nome, modalidade e, se quiser, documento, contato e referência contratual.
3. A API valida a lista fechada de campos, cifra documento e contatos e grava cliente e evento.

**Fluxos alternativos e de erro:**
- 3a. Campo fora da lista → `422 'O cadastro contém campos não permitidos.'`.
- 3b. Tipo ou modalidade inválidos, e-mail inválido → `422`.
- 3c. Sem contexto interno, perfil escolar ou acesso interno revogado → `403`.
- 3d. Falha ao gravar o evento → nada é gravado.
- 3e. Documento repetido não une clientes automaticamente.

**Regras de negócio:** docs/66 (modalidade pagante não gera cobrança; referência contratual não é contrato).

**Critérios de aceite:**
- [ ] Documento e contatos ficam cifrados no banco e ausentes da serialização comum.
- [ ] Administrador da rede recebe `403`.

**Situação:** Implementado — evidência: `ClienteController` (API/web); `CadastroCliente`; `CadastroClienteTest`; PR #100, #105.

⚠ Cadastrar o cliente não cria espaço nem vínculo. O provisionamento não tem caminho na aplicação (ver §8 do [planejamento](planejamento.md); UC-022-07; FN-029 EI1-F).

---

## UC-023-02 — Listar e pesquisar clientes

**Ator:** Desenvolvedor, Suporte ou Financeiro
**Permissão:** `cliente.ver`
**Escopo:** plataforma
**Pré-condições:** contexto interno ativo.
**Pós-condições:** nenhuma.
**Tela/rota:** `/app/clientes`; `GET /api/v1/clientes?busca=&tipo=&modalidade=&page=`.

**Fluxo principal:**
1. O ator abre "Clientes".
2. Filtra por nome, tipo ou modalidade; a lista vem paginada (20), ordenada por nome.

**Fluxos alternativos e de erro:**
- 2a. Filtro inválido → `422`.

**Critérios de aceite:**
- [ ] A lista não traz documento, contatos nem referência contratual.

**Situação:** Implementado — evidência: `ClienteController::index`.

---

## UC-023-03 — Consultar e editar cliente

**Ator:** Desenvolvedor, Suporte ou Financeiro
**Permissão:** `cliente.ver` (consulta), `cliente.gerir` (edição)
**Escopo:** plataforma
**Pré-condições:** cliente existente.
**Pós-condições:** alteração gravada com evento `cadastro_atualizado` ou `modalidade_alterada` (guarda modalidade anterior e nova).
**Tela/rota:** `/app/clientes/{id}`; `GET/PUT /api/v1/clientes/{id}`.

**Fluxo principal:**
1. O ator abre o detalhe; vê os campos de edição comercial.
2. Altera e salva; a API trava o cliente (`lockForUpdate`), revalida a autorização e grava só se houver mudança.

**Fluxos alternativos e de erro:**
- 2a. Nada mudou → sem evento.
- 2b. Tentativa de alterar situação → campo não permitido (`422`); situação é T6 (FN-028).

**Critérios de aceite:**
- [ ] Mudança de modalidade gera um evento com valores anterior e novo, sem dados protegidos.

**Situação:** Implementado — evidência: `ClienteController::show/update`; `CadastroCliente::atualizar`.

---

## UC-023-04 — Consultar os espaços de um cliente

**Ator:** Desenvolvedor, Suporte ou Financeiro
**Permissão:** `cliente.ver`
**Escopo:** plataforma
**Pré-condições:** cliente existente.
**Pós-condições:** nenhuma.
**Tela/rota:** tabela de espaços no detalhe do cliente; `GET /api/v1/clientes/{id}/espacos?page=`.

**Fluxo principal:**
1. No detalhe, o ator vê os espaços do cliente (id, nome, tipo, situação), paginados.
2. Cada espaço mostra a contagem de vínculos por situação.

**Fluxos alternativos e de erro:**
- 1a. Cliente inexistente → `404`.

**Critérios de aceite:**
- [ ] A resposta não traz usuários, CPF, e-mail, alunos, provas ou notas.

**Situação:** Implementado — evidência: `ClienteController::espacos`; docs/71.

---

## UC-023-05 — Registrar contrato e situação comercial do cliente

**Ator:** Financeiro (proposto)
**Permissão:** a definir em FN-068
**Escopo:** plataforma
**Tela/rota:** sem mockup.

**Fluxo principal (proposto):** registrar contrato, cláusulas, vigência e situação; suspender/encerrar segue T6.

**Situação:** Não implementado — remetido a FN-068 (F1–F3) e FN-028 (T6).
