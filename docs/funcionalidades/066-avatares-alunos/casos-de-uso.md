# 066 — Avatar no lugar de foto de aluno — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** perfis com `aluno.gerir` (cadastro e edição) e `aluno.ver` (visualização) no alcance do vínculo: Coordenador, Diretor Escolar, Secretário Escolar, Auxiliar de secretaria, professor autônomo no espaço pessoal, entre outros conforme docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve. O cadastro do aluno em si pertence a FN-033.

---

## UC-066-01 — Escolher o avatar no cadastro ou na edição do aluno

**Ator:** perfil com `aluno.gerir`
**Permissão:** `aluno.gerir`
**Escopo:** alunos do alcance do vínculo ativo (`Escopo::alunos`)
**Pré-condições:** formulário de cadastro ou edição aberto.
**Pós-condições:** `avatar_id` gravado (1 a 36) ou nulo (iniciais).
**Tela/rota:** web `alunos/create` e edição; API `POST /v1/alunos`, `PUT /v1/alunos/{id}`, `POST /v1/alunos/{id}/imagem` com `tipo=avatar`.

**Fluxo principal:**
1. O ator abre o seletor e vê as iniciais e as 36 figuras.
2. Escolhe uma opção; a prévia muda na hora.
3. Salva o formulário; a escolha persiste.

**Fluxos alternativos e de erro:**
- 2a. Volta às iniciais: `avatar_id` nulo.
- 3a. Valor fora de 1–36: rejeitado pela web antes da API e pela API.
- 3b. Cancelar não salva a escolha.

**Regras de negócio:** RL19; docs/28 L6 passo 4.

**Critérios de aceite:**
- [x] 37 opções; persistência só ao salvar; teclado funciona.

**Situação:** Implementado — evidência: PR #70; testes citados em docs/operacao/avatares-alunos.md.

---

## UC-066-02 — Ver o aluno representado por avatar

**Ator:** perfil com `aluno.ver`
**Permissão:** `aluno.ver`
**Escopo:** alcance do vínculo
**Pré-condições:** aluno existente.
**Pós-condições:** nenhuma.
**Tela/rota:** cadastro, lista, detalhe, turma e painéis de aluno, professor e coordenador (componente `avatar.blade.php`).

**Fluxo principal:**
1. O ator abre uma tela que mostra o aluno.
2. O sistema mostra a figura escolhida ou as iniciais com cor derivada do ID.

**Fluxos alternativos e de erro:**
- 2a. Nome vazio: alternativa visual sem erro; iniciais com acentos e espaços tratados e escapados.

**Regras de negócio:** cor estável após troca de nome; tokens dos dois temas.

**Critérios de aceite:**
- [x] Mesmo componente em todas as telas listadas.

**Situação:** Implementado — evidência: PR #70.

---

## UC-066-03 — Recusar foto de aluno

**Ator:** perfil com `aluno.gerir`
**Permissão:** `aluno.gerir`
**Escopo:** alcance do vínculo
**Pré-condições:** nenhuma.
**Pós-condições:** nenhuma foto gravada.
**Tela/rota:** API `POST /v1/alunos`, `PUT /v1/alunos/{id}`, `POST /v1/alunos/{id}/imagem`.

**Fluxo principal (planejado no L6):**
1. O ator tenta enviar foto ou `foto_path`.
2. A API recusa; a tela não oferece upload.

**Fluxos alternativos e de erro:**
- 1a. `foto_path` no formulário: recusado (`prohibited`) — comportamento atual mantido.

**Regras de negócio:** RL19; docs/28 L6 passos 1 e 4.

**Critérios de aceite:**
- [ ] Nenhuma tela ou API aceita foto de aluno.

**Situação:** ⚠ Divergente — a produção (PR #70) recusa foto, mas o código atual, só na homologação, aceita foto de aluno: `ImagemAlunoController::salvar` com `tipo=foto` e `AlunoController::store` com campo `foto`, reprocessando em WebP 384×384 no disco local privado (PF1-C/IM1, docs/135, commit `987c91e`). A edição pelo formulário genérico (`PUT`) continua proibindo `foto`. Resolve: decisão do responsável registrada no planejamento §8; sem subfase definida.

---

## UC-066-04 — Eliminar fotos legadas e a coluna `foto_path`

**Ator:** operação (Desenvolvedor), com aprovação do responsável
**Permissão:** não se aplica (procedimento operacional)
**Escopo:** plataforma
**Pré-condições:** aprovação expressa; backup recente verificado; nova conferência agregada no momento da execução.
**Pós-condições:** arquivos removidos; coluna eliminada por migration.
**Tela/rota:** procedimento em docs/operacao/avatares-alunos.md (a complementar).

**Fluxo principal:**
1. Conferir contagem agregada de `foto_path` e do diretório.
2. Fazer backup e conferir a restauração.
3. Apagar arquivos e aplicar a migration.

**Fluxos alternativos e de erro:**
- 1a. Contagem diferente de zero: apresentar ao responsável antes de prosseguir.

**Regras de negócio:** docs/28 L6 passo 6.

**Critérios de aceite:**
- [ ] Execução registrada com contagens antes e depois, sem dados pessoais.

**Situação:** Não implementado — subfase 6.4, não autorizada. ⚠ Com o retrato de aluno da homologação (UC-066-03), a coluna voltou a ser usada; o passo 6 depende da decisão sobre a divergência.
