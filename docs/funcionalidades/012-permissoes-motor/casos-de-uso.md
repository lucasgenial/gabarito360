# 012 — Motor de permissões, equipe e menus — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Vice-diretor (com extra `usuario.gerir`), Coordenador; todos os perfis como sujeitos da autorização ([docs/03](../../03-perfis-e-permissoes.md))
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

Referência: UC-008 "Gerenciar Equipe da Escola" de [docs/07](../../07-casos-de-uso.md). Os casos abaixo detalham UC-008 conforme o código. Quando o gestor está em um vínculo selecionado (regime de vínculos obrigatório, em produção desde 02/11), a listagem, a ativação e as permissões são delegadas à gestão por vínculo (FN-015, UC-015-05 e UC-015-06).

---

## UC-012-01 — Autorizar uma ação

**Ator:** qualquer pessoa autenticada
**Permissão:** a chave exigida pela rota (por exemplo `turma.ver`)
**Escopo:** o do contexto ativo
**Pré-condições:** token válido; vínculo ou acesso interno selecionado quando o regime exige.
**Pós-condições:** a ação segue ou é recusada.
**Tela/rota:** middleware `permissao:` (API); `Acesso::pode` (web)

**Fluxo principal:**
1. A rota declara uma ou mais chaves (`permissao:a,b` aceita qualquer uma).
2. O motor calcula as efetivas: contexto interno → permissões do acesso interno; contexto de vínculo → permissões do vínculo; caso legado → padrão do perfil + extras válidas − retiradas.
3. Com a chave presente, a ação segue; o escopo é conferido depois por `Escopo`/`ConsultaVinculo`.

**Fluxos alternativos e de erro:**
- 2a. Sem usuário: 401. Sem a chave: 403.
- 2b. Perfil interno gravado no campo legado sem contexto: nenhuma permissão.
- 2c. `professor_autonomo`, `secretario_escolar`, `vice_dir_escolar`, `auxiliar_secretaria` sem vínculo: nenhuma permissão.
- ⚠ 1a. Oito rotas `dashboard/*` ainda usam `perfil:` em vez de `permissao:` (subfase 2.3).

**Regras de negócio:** permissão nunca amplia escopo ([docs/03 §5](../../03-perfis-e-permissoes.md)).

**Critérios de aceite:**
- [ ] Extra vencida (`valida_ate` < hoje) não conta.
- [ ] Retirada de um padrão remove a permissão.
- [ ] Teste por perfil cobre as rotas protegidas.

**Situação:** Implementado — evidência: `Permissao::efetivas`, `CheckPermissao`, PR #15–#22.

---

## UC-012-02 — Ver a equipe do escopo

**Ator:** Admin Rede, Dir. Núcleo, Dir. Escolar, Coordenador; Professor com extra
**Permissão:** `usuario.ver`
**Escopo:** rede, núcleo ou escola do gestor
**Pré-condições:** sessão com contexto.
**Pós-condições:** nenhuma.
**Tela/rota:** `mockups/perfis-equipe.html`; web `GET /membros`; API `GET /api/v1/usuarios`

**Fluxo principal:**
1. O gestor abre "Equipe".
2. Com vínculo selecionado, a API lista pessoas e vínculos do espaço (FN-015); no modo legado, lista usuários do escopo ordenados por perfil e nome, com filtro por perfil.

**Critérios de aceite:**
- [ ] Ninguém fora do escopo aparece na lista.

**Situação:** Implementado — evidência: `UsuarioController::index`, `GestaoVinculoController::index`.

---

## UC-012-03 — Cadastrar um membro

**Ator:** gestor com `usuario.gerir`
**Permissão:** `usuario.gerir` (e `turma.vincular_professor` para turmas do professor)
**Escopo:** escola ou núcleo dentro do escopo do gestor
**Pré-condições:** o gestor gere o perfil escolhido (`Cadastro::PODE_GERIR`).
**Pós-condições:** conta criada com senha aleatória; vínculo `convidado` no espaço (regime de vínculos) e convite de primeira conta enviado (UC-015-02).
**Tela/rota:** `mockups/membro-cadastrar.html`; web `GET /membros/novo`, `POST /membros`; API `POST /api/v1/usuarios`

**Fluxo principal:**
1. O gestor informa nome, e-mail, CPF (11 dígitos), perfil e, conforme o perfil, escola, núcleo e turmas.
2. A API valida unicidade protegida de e-mail e CPF, perfil gerível e escopo.
3. Cria a conta e, em contexto de vínculo, o vínculo no espaço da escola ou do núcleo, com turmas do professor.
4. Envia o convite de primeira conta.

**Fluxos alternativos e de erro:**
- 2a. "E-mail já cadastrado." / "CPF já cadastrado." / "CPF deve conter 11 dígitos."
- 2b. Perfil fora da gestão: "Você não pode cadastrar ou atribuir o perfil ...".
- 2c. Escola fora do escopo: "Escola fora do seu escopo."; dir. núcleo sem núcleo: "Informe um núcleo válido para o diretor de núcleo."
- 2d. Turmas sem `turma.vincular_professor`: "Você não pode vincular professores a turmas."; turma de outra escola: "As turmas precisam ser da escola do professor."
- 2e. Secretário escolar, vice-diretor ou auxiliar sem vínculo do gestor: "Este perfil exige vínculo de escola."
- 2f. Novo `admin_rede` com espaços ativos: "Novo administrador exige atribuição explícita por vínculo."
- 2g. Conta já existente: o caminho é o convite de vínculo (UC-015-03), não um novo cadastro.

**Regras de negócio:** ninguém concede mais poder do que tem; grupos funcionais (FN-014).

**Critérios de aceite:**
- [ ] O gestor não cria perfil igual ou acima do seu, exceto admin_rede → admin_rede conforme a matriz.
- [ ] A conta nova não entra antes de aceitar o convite e criar a senha.

**Situação:** Implementado — evidência: `UsuarioController::store`, `Cadastro`.

---

## UC-012-04 — Editar, ativar ou desativar um membro

**Ator:** gestor com `usuario.gerir`
**Permissão:** `usuario.gerir`
**Escopo:** do gestor
**Pré-condições:** pessoa no escopo e perfil gerível.
**Pós-condições:** dados ou situação alterados.
**Tela/rota:** `mockups/membro-editar.html`; web `GET /membros/{id}/editar`, `PUT /membros/{id}`, `POST /membros/{id}/toggle`; API `PUT /api/v1/usuarios/{id}`, `POST /api/v1/usuarios/{id}/toggle`

**Fluxo principal:**
1. O gestor altera dados cadastrais ou aciona ativar/desativar.
2. Em contexto de vínculo, a ação vale para o vínculo no espaço (suspender/reativar), preservando o histórico.

**Fluxos alternativos e de erro:**
- 2a. Perfil fora da gestão: "Você não pode alterar um usuário deste perfil." / "Você não pode desativar um usuário deste perfil."
- 2b. Troca de perfil ou de escola: "Mudança de perfil exige gestão por vínculo." / "Mudança de escola exige gestão por vínculo."

**Critérios de aceite:**
- [ ] Mudança de papel nunca reescreve o vínculo existente.

**Situação:** Implementado — evidência: `UsuarioController::update`, `UsuarioController::toggle`, `GestaoVinculoController`.

---

## UC-012-05 — Reenviar convite ou enviar link de nova senha

**Ator:** gestor com `usuario.gerir`
**Permissão:** `usuario.gerir`
**Escopo:** do gestor
**Pré-condições:** pessoa ativa no escopo.
**Pós-condições:** e-mail enviado com código (sem token na URL).
**Tela/rota:** web `POST /membros/{id}/convite`, `POST /membros/{id}/link-senha`; API `POST /api/v1/usuarios/{id}/convite`, `POST /api/v1/usuarios/{id}/link-senha` (5/min)

**Fluxo principal:**
1. O gestor aciona "Reenviar convite" (vínculo ainda `convidado`) ou "Enviar link de senha".

**Fluxos alternativos e de erro:**
- 1a. Vínculo que não aguarda convite: "Este vínculo não aguarda convite."; conta inativa: "Ative o usuário antes de enviar ...".
- 1b. Em alguns contextos de vínculo o link de senha é recusado: "A recuperação da conta deve ser solicitada pelo titular em Esqueci minha senha."

**Critérios de aceite:**
- [ ] O gestor nunca vê nem define a senha da pessoa.

**Situação:** Implementado — evidência: `UsuarioController::reenviarConvite`, `UsuarioController::enviarLinkSenha`.

---

## UC-012-06 — Conceder ou retirar permissões de uma pessoa

**Ator:** gestor com `usuario.permissoes`
**Permissão:** `usuario.permissoes` (ver: `usuario.ver`)
**Escopo:** do gestor
**Pré-condições:** o gestor gere o perfil da pessoa e tem a permissão que concede.
**Pós-condições:** extras (com validade opcional) e retiradas gravadas.
**Tela/rota:** web `GET|PUT /membros/{id}/permissoes`, `GET /membros/perfis`; API `GET|PUT /api/v1/usuarios/{id}/permissoes`, `GET /api/v1/permissoes/perfis`

**Fluxo principal:**
1. O gestor vê padrão, extras concedíveis e retiradas do perfil.
2. Marca extras (com `valida_ate` opcional, não anterior a hoje) e retiradas.
3. A API valida item a item com `Permissao::podeDefinir` e grava.

**Fluxos alternativos e de erro:**
- 3a. Qualquer item inválido: 403 "Algumas permissões não puderam ser definidas." com o motivo por chave (por exemplo "já é padrão deste perfil", "não pode ser concedida a este perfil", "porque não tem essa permissão").

**Critérios de aceite:**
- [ ] Gestor sem a permissão não consegue concedê-la.
- [ ] Só extras do perfil podem ser concedidas; só padrões podem ser retirados.

**Situação:** Implementado — evidência: `UsuarioController::definirPermissoes`, `Permissao::podeDefinir`.

---

## UC-012-07 — Navegar por menus conforme as permissões

**Ator:** qualquer pessoa com contexto ativo
**Permissão:** a de cada item
**Escopo:** do contexto
**Pré-condições:** contexto selecionado.
**Pós-condições:** menu lateral e barra do celular só com itens permitidos.
**Tela/rota:** layout web; `apps/web/app/Support/Acesso.php`

**Fluxo principal:**
1. A web lê as permissões efetivas da sessão.
2. Monta o menu na ordem definida e escolhe os itens principais da barra inferior no celular.

**Critérios de aceite:**
- [ ] Nenhum item aparece sem a permissão correspondente.
- [ ] Esconder o item não substitui a autorização na API.

**Situação:** Implementado — evidência: `Acesso::ITENS`, `Acesso::ORDEM`, `Acesso::PRINCIPAIS`; commit 59a80ba (MP-036).
