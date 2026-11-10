# 011 — Autenticação e conta — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis com conta (docs/03 (`docs/03-perfis-e-permissoes.md`)), inclusive os internos (Desenvolvedor, Suporte, Financeiro)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

Referência geral: UC-001 "Autenticar no Sistema" de docs/07 (`docs/07-casos-de-uso.md`). Os casos abaixo detalham UC-001 conforme o código; não o substituem.

---

## UC-011-01 — Entrar no sistema

**Ator:** qualquer pessoa com conta ativa
**Permissão:** nenhuma (rota pública)
**Escopo:** conta (antes da escolha de vínculo)
**Pré-condições:** conta com `ativo = true`.
**Pós-condições:** sessão web regenerada com o token da API; a pessoa é levada à escolha de vínculo (FN-015) ou à troca obrigatória de senha.
**Tela/rota:** `mockups/login.html`; web `GET|POST /login`; API `POST /api/v1/auth/login`

**Fluxo principal:**
1. A pessoa informa e-mail e senha.
2. A web valida o formato (`email` obrigatório e válido; senha obrigatória) e chama a API.
3. A API localiza a conta ativa pelo índice do e-mail e confere a senha.
4. A API registra `ultimo_acesso` e cria o token `api-token`.
5. Com o regime de vínculos obrigatório, a resposta não traz perfil nem permissões e indica `selecionar_vinculo`; a web limpa o contexto anterior, regenera a sessão e redireciona a `/app/vinculos`.

**Fluxos alternativos e de erro:**
- 3a. Conta inexistente, inativa ou senha errada: API responde 401 "Credenciais inválidas."; a web mostra "E-mail ou senha incorretos." e preserva só o e-mail.
- 4a. Conta com `forcar_troca_senha`: o token criado é `troca-senha` e a web redireciona para `/app/trocar-senha` (UC-011-04).
- 5a. Login vindo de um convite (`retorno_convite=1`): volta para a conferência do convite de vínculo.
- ⚠ 1a. "Manter conectado" aparece marcado na tela, mas não altera a sessão. Sem subfase; ver §8 do planejamento.
- ⚠ 3b. Não há limite de tentativas nesta rota. Sem subfase; ver §8 do planejamento (FN-007 X3).

**Regras de negócio:** identidade confirmada pelo servidor, nunca inferida do e-mail enviado; o perfil legado não concede acesso quando o regime de vínculos é obrigatório.

**Critérios de aceite:**
- [ ] Credenciais erradas e conta inativa produzem a mesma mensagem.
- [ ] Após o login a sessão é regenerada e nenhum contexto anterior permanece.
- [ ] Conta com troca obrigatória não alcança nenhuma rota além de `auth/me` e `auth/logout` na API.

**Situação:** Implementado — evidência: `AuthController::login`, `LoginController::store`.

---

## UC-011-02 — Sair do sistema

**Ator:** pessoa autenticada
**Permissão:** nenhuma
**Escopo:** sessão
**Pré-condições:** sessão web ativa.
**Pós-condições:** token atual apagado na API; sessão web invalidada e token CSRF regenerado.
**Tela/rota:** web `POST /logout`; API `POST /api/v1/auth/logout`

**Fluxo principal:**
1. A pessoa aciona "Sair".
2. A web pede à API a exclusão do token atual.
3. A web limpa o contexto, invalida a sessão e volta ao login.

**Fluxos alternativos e de erro:**
- 2a. API indisponível: a saída local acontece mesmo assim.

**Regras de negócio:** a saída local independe da API.

**Critérios de aceite:**
- [ ] Depois de sair, o token anterior não autentica na API.
- [ ] Com a API fora do ar, a sessão web é encerrada.

**Situação:** Implementado — evidência: `AuthController::logout`, `LoginController::destroy`.

---

## UC-011-03 — Recuperar a senha

**Ator:** titular da conta
**Permissão:** nenhuma (rota pública)
**Escopo:** conta
**Pré-condições:** conta ativa.
**Pós-condições:** nova senha gravada; todos os tokens da conta apagados; aviso "senha alterada" enviado.
**Tela/rota:** web `GET|POST /esqueci-senha` e `GET|POST /redefinir-senha`; API `POST /api/v1/auth/esqueci-senha` (5/min) e `POST /api/v1/auth/redefinir-senha` (10/min)

**Fluxo principal:**
1. A pessoa informa o e-mail em "Esqueci a senha".
2. Se a conta existir e estiver ativa, a API grava um código de 64 caracteres (hash) e envia e-mail com o endereço fixo da tela e o código no corpo.
3. A tela sempre responde "Se o e-mail estiver cadastrado, as instruções e o código de recuperação foram enviados."
4. Em "Redefinir senha", a pessoa informa e-mail, código e nova senha com confirmação.
5. A API confere o código sob bloqueio, apaga o registro, verifica a validade de 60 minutos, grava a senha, desliga `forcar_troca_senha` e apaga todos os tokens.
6. A web mostra "Senha salva. Entre com a nova senha." no login.

**Fluxos alternativos e de erro:**
- 1a. Abrir `/redefinir-senha` com `token`, `conta` ou `email` na URL: redireciona sem esses dados e orienta a colar o código no formulário.
- 4a. Código com formato errado: "Copie os 64 caracteres do código recebido por e-mail."; senha com menos de 8 caracteres: "Use uma senha com pelo menos 8 caracteres."; confirmação diferente: "As senhas não coincidem."
- 5a. Código errado: "Token inválido ou expirado."; código vencido: "Código expirado. Solicite um novo código de recuperação."

**Regras de negócio:** sem enumeração de contas; código nunca em URL (UR1-B, FN-018).

**Critérios de aceite:**
- [ ] A resposta de "Esqueci a senha" é idêntica para e-mail existente e inexistente.
- [ ] O código vale uma vez e por 60 minutos.
- [ ] Após redefinir, sessões abertas com a senha antiga deixam de funcionar.

**Situação:** Implementado — evidência: `AuthController::esqueceuSenha`, `AuthController::redefinirSenha`, `ContaUsuario::enviarLinkDeSenha`.

---

## UC-011-04 — Trocar a senha obrigatoriamente

**Ator:** pessoa cuja conta tem `forcar_troca_senha`
**Permissão:** nenhuma
**Escopo:** conta
**Pré-condições:** login feito com token `troca-senha`.
**Pós-condições:** senha trocada e `forcar_troca_senha` desligado; acesso liberado a partir do próximo token.
**Tela/rota:** web `GET|PUT /app/trocar-senha`; API `PUT /api/v1/auth/me`

**Fluxo principal:**
1. Após o login, a web redireciona para a troca de senha; o middleware `Autenticado` mantém a pessoa nessa tela.
2. A pessoa informa a senha atual e a nova senha com confirmação.
3. A API confere a senha atual, recusa a nova se for igual à atual e grava.
4. A web segue para o painel ou de volta ao convite, se veio dele.

**Fluxos alternativos e de erro:**
- 3a. Senha atual errada: "Senha atual incorreta."
- 3b. Nova senha igual à atual: "Escolha uma senha diferente da atual."

**Regras de negócio:** sessões abertas antes de o gestor marcar a troca seguem válidas; a exigência vale a partir do próximo login.

**Critérios de aceite:**
- [ ] Com token `troca-senha`, qualquer rota da API fora de `auth/me` e `auth/logout` responde 403 "Troque sua senha para continuar."
- [ ] Repetir a mesma senha é recusado.

**Situação:** Implementado — evidência: `SenhaTrocada`, `AuthController::update`, `PerfilController::trocarSenha`.

---

## UC-011-05 — Trocar a senha pelo perfil

**Ator:** pessoa autenticada
**Permissão:** nenhuma
**Escopo:** própria conta
**Pré-condições:** sessão ativa.
**Pós-condições:** senha atualizada.
**Tela/rota:** `mockups/perfil.html`; web `PUT /perfil/senha`; API `PUT /api/v1/auth/me`

**Fluxo principal:**
1. Em "Meu perfil", a pessoa informa senha atual, nova senha e confirmação.
2. A API confere a senha atual e grava a nova.
3. A tela mostra "Senha alterada."

**Fluxos alternativos e de erro:**
- 2a. Senha atual errada: "Senha atual incorreta."

**Regras de negócio:** mínimo de 8 caracteres. ⚠ O docs/04 RF-001.1 diz 6; vale o código até registro no docs/06 (ver §8 do planejamento).

**Critérios de aceite:**
- [ ] Sem a senha atual correta, nada muda.

**Situação:** Implementado — evidência: `PerfilController::senha`, `AuthController::update`.

---

## UC-011-06 — Trocar o e-mail de acesso

**Ator:** pessoa autenticada (inclusive em acesso interno)
**Permissão:** nenhuma
**Escopo:** própria conta
**Pré-condições:** sessão ativa; novo e-mail não usado por outra conta.
**Pós-condições:** após a confirmação, a conta passa a usar o novo e-mail; códigos de senha pendentes do e-mail antigo são apagados; o e-mail antigo recebe um aviso.
**Tela/rota:** web `POST /perfil/email`, `GET|POST /confirmar-email`; API `POST /api/v1/auth/email` (5/min) e `POST /api/v1/auth/confirmar-email` (10/min)

**Fluxo principal:**
1. A pessoa informa o novo e-mail e a senha atual.
2. A API confere a senha, guarda o novo e-mail cifrado com código de 24 horas e envia o código ao novo endereço.
3. A tela informa: "Enviamos um código de confirmação para o novo e-mail. O atual segue valendo até lá."
4. A pessoa abre `/confirmar-email`, cola o código e confirma por POST.
5. A API troca o e-mail, apaga a solicitação e avisa o endereço antigo.

**Fluxos alternativos e de erro:**
- 1a. E-mail já usado: "Este e-mail já está em uso."; senha errada: "Senha atual incorreta."
- 4a. Abrir a página com código na URL: redireciona e pede para colar o código.
- 5a. Código inválido, vencido ou e-mail ocupado no meio do caminho: "Código inválido ou expirado. Peça a confirmação de e-mail novamente."

**Regras de negócio:** abrir a página nunca confirma nem consome o código.

**Critérios de aceite:**
- [ ] Antes da confirmação, o login continua com o e-mail antigo.
- [ ] O e-mail antigo recebe o aviso de alteração.
- [ ] ⚠ Teste real de envio e confirmação registrado (subfase 1.5, via FN-060).

**Situação:** Parcial — implementado no código sem teste real de entrega — evidência: `AuthController::trocarEmail`, `ContaUsuario::pedirTrocaDeEmail`, `ContaUsuario::confirmarTrocaDeEmail`, PR #54.

---

## UC-011-07 — Criar conta por autocadastro

**Ator:** visitante
**Permissão:** —
**Escopo:** —
**Pré-condições:** —
**Pós-condições:** —
**Tela/rota:** aba "Cadastro" de `mockups/login.html` (RF-001.2); nenhuma rota

**Fluxo principal:** não implementado. O acesso é concedido por convite (FN-015), por cadastro de clientes (FN-023) ou por espaço pessoal (FN-022).

**Regras de negócio:** decisão §4 item 5 do plano mestre.

**Critérios de aceite:**
- [ ] Decisão registrada (substituir ou retomar).

**Situação:** Não implementado (Substituída) — evidência: ausência de `/cadastro` em `apps/web/routes/web.php`; FN-030.

---

## UC-011-08 — Entrar com gov.br

**Ator:** servidor público com conta gov.br
**Permissão:** —
**Escopo:** conta
**Pré-condições:** integração com o provedor gov.br.
**Pós-condições:** sessão aberta pela identidade gov.br.
**Tela/rota:** mensagem prevista em `mockups/login.html` (RF-001.3)

**Fluxo principal:** proposto, sem desenho. O docs/04 só previa a mensagem "Acesso protegido por autenticação gov.br" e uma integração futura.

**Critérios de aceite:**
- [ ] Decisão do responsável sobre planejar ou arquivar.

**Situação:** Não implementado — evidência: nenhuma referência a gov.br nos controladores de autenticação nem na tela de login.
