# 037 — Imagens de turmas e educadores — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** quem gerencia a turma (`turma.gerir`), quem vê a turma (`turma.ver`), o próprio educador, gestores de equipe — ver [docs/03](../../03-perfis-e-permissoes.md)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-037-01 — Definir a imagem da turma

**Ator:** Coordenador, Diretor Escolar, Secretário Escolar, Administrador da Rede, Professor autônomo
**Permissão:** `turma.gerir`
**Escopo:** escola | espaço pessoal
**Pré-condições:** turma no escopo (inclusive arquivada).
**Pós-condições:** imagem (brasão ou foto) gravada com nova versão; imagem anterior removida só após sucesso.
**Tela/rota:** detalhe da turma e Editar turma; web `POST /turmas/{id}/imagem`; API `POST /v1/turmas/{id}/imagem`

**Fluxo principal:**
1. O ator clica na imagem da turma e escolhe um brasão (1–36) ou envia foto (arquivo ou câmera, com recorte e compactação).
2. A API confere o escopo antes de processar o arquivo, grava e devolve a nova versão.

**Fluxos alternativos e de erro:**
- 1a. Cancelar → imagem anterior preservada.
- 2a. Turma fora do escopo → 404.
- 2b. Arquivo fora da regra → erro de validação; nada muda.
- 2c. Limite de versão → 409 "Atualização da imagem indisponível."

**Critérios de aceite:**
- [ ] Só quem gerencia a turma vê a ação de editar.
- [ ] Falha não apaga a imagem anterior.

**Situação:** Implementado — evidência: `apps/api/app/Http/Controllers/Api/ImagemTurmaController.php`, `turmas/_imagem`; PR #208, #209.

---

## UC-037-02 — Ver a imagem da turma

**Ator:** perfis com `turma.ver`
**Permissão:** `turma.ver`
**Escopo:** turma
**Pré-condições:** turma no escopo.
**Pós-condições:** nenhuma.
**Tela/rota:** lista de turmas, detalhe; web `GET /turmas/{id}/foto`; API `GET /v1/turmas/{id}/foto`

**Fluxo principal:**
1. Lista e detalhe mostram o brasão (sprite) ou a foto privada servida pela API.

**Critérios de aceite:**
- [ ] Foto nunca é servida por URL pública.

**Situação:** Implementado — evidência: `ImagemTurmaController::mostrar`, `<x-avatar-turma>` (PR #215).

---

## UC-037-03 — Escolher avatar de educador no próprio perfil

**Ator:** qualquer usuário autenticado (educador)
**Permissão:** própria conta
**Escopo:** espaço pessoal (conta)
**Pré-condições:** —
**Pós-condições:** avatar de educador (1–36) ou foto gravados.
**Tela/rota:** web `POST /perfil/imagem`; API `POST /v1/auth/imagem`, `GET /v1/auth/foto`

**Fluxo principal:** ver FN-019 (imagem de perfil); o catálogo usado é o de educadores, separado do de alunos.

**Critérios de aceite:**
- [ ] Profissionais usam o catálogo de educadores; alunos continuam com o de estudantes.

**Situação:** Implementado — evidência: `ImagemUsuarioController`; PR #208.

---

## UC-037-04 — Escolher a imagem ao criar a turma (IMG-N)

**Ator:** perfis com `turma.gerir`
**Permissão:** `turma.gerir`
**Escopo:** escola | espaço pessoal
**Pré-condições:** —
**Pós-condições:** turma criada já com imagem.
**Tela/rota:** formulário de criação em `turmas/index`

**Fluxo principal:**
1. Ao preencher a nova turma, o ator escolhe um brasão ou foto.
2. Ao salvar, a turma é criada com a imagem.

**Fluxos alternativos e de erro:**
- 2a. Falha só na imagem → turma criada sem imagem e aviso para tentar de novo.

**Critérios de aceite:**
- [ ] Mesmo componente de escolha usado no Editar turma.

**Situação:** Não implementado — origem: docs/176 IMG-N (subfase 1.7).

---

## UC-037-05 — Escolher a imagem no cadastro de professor (IMG-P)

**Ator:** gestores de equipe (`usuario.gerir`)
**Permissão:** `usuario.gerir` (a confirmar)
**Escopo:** escola
**Pré-condições:** —
**Pós-condições:** membro cadastrado com avatar de educador ou foto.
**Tela/rota:** `membros/create`

**Fluxo principal:**
1. Ao cadastrar o professor, o gestor escolhe um avatar de educador.

**Critérios de aceite:**
- [ ] Foto de terceiro só com regra aprovada (LGPD); avatar sempre disponível.

**Situação:** Não implementado — origem: docs/176 IMG-P (subfase 1.8). ⚠ Hoje `membros/create` mostra só as iniciais.
