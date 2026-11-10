# 018 — Segurança de URLs, sessão e dependências (UR1–UR4) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos ([docs/03](../../03-perfis-e-permissoes.md)); equipe de desenvolvimento em UC-018-07
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

Tudo abaixo está **só na homologação** (⚠ subfase 5.1: produção depende de decisão expressa). O docs/07 não tem caso de uso equivalente; UC-001 é afetado no passo de redirecionamento (agora `/app/vinculos` → `/app/painel`).

---

## UC-018-01 — Usar um código recebido por e-mail sem expô-lo na URL

**Ator:** titular da conta
**Permissão:** nenhuma
**Escopo:** conta
**Pré-condições:** e-mail de convite, recuperação de senha, confirmação de e-mail ou convite de vínculo.
**Pós-condições:** código consumido só por POST com CSRF.
**Tela/rota:** web `/convite`, `/redefinir-senha`, `/confirmar-email`, `/app/vinculos/convite`; API `auth/*` correspondentes

**Fluxo principal:**
1. O e-mail traz um endereço fixo e o código no corpo.
2. A pessoa abre a página e cola o código no formulário.
3. O envio por POST confirma a ação.

**Fluxos alternativos e de erro:**
- 2a. Página aberta com `token`, `conta`, `email` ou `codigo` na URL: redireciona para o endereço limpo com "Cole o código recebido por e-mail no formulário."
- 3a. API chamada com `token` na query: 422 "Envie o código no corpo da requisição."

**Critérios de aceite:**
- [ ] Abrir a página (GET) nunca confirma nem consome o código.
- [ ] Nenhum e-mail contém credencial no endereço.

**Situação:** Implementado (homologação) — evidência: `RecusarCredenciaisNaUrl`, `LoginController::showConvite/showRedefinirSenha`, `PerfilController::showConfirmarEmail`, PR #192; subfase 1.2.

---

## UC-018-02 — Trabalhar com um único vínculo por sessão

**Ator:** pessoa autenticada
**Permissão:** nenhuma
**Escopo:** vínculo selecionado
**Pré-condições:** login e escolha de vínculo (UC-015-01).
**Pós-condições:** o vínculo vale enquanto a sessão existir.
**Tela/rota:** `/app/vinculos`, `/app/painel`; middlewares `Autenticado`, `RevisaoAcesso`

**Fluxo principal:**
1. Depois do login a pessoa escolhe o vínculo; a navegação usa só `/app/...`.
2. O contexto fica exclusivamente na sessão; nenhuma URL o carrega.
3. Cada ação envia a revisão do acesso que a tela conhecia.

**Fluxos alternativos e de erro:**
- 2a. Sem sessão: "Faça login para continuar." (ou 401 com `login_necessario` em chamadas de dados).
- 2b. Sem vínculo escolhido: "Escolha seu vínculo para continuar."
- 3a. Acesso trocado em outra aba: 409 com a tela de acesso alterado; a ação antiga não é executada.
- 3b. Contexto perdido ou recusado pela API: a sessão é encerrada e a pessoa entra de novo.

**Critérios de aceite:**
- [ ] Uma tela aberta com o vínculo anterior não grava nada depois da troca.
- [ ] Requisição forjada (sem CSRF válido) nunca encerra a sessão autenticada.

**Situação:** Implementado (homologação) — evidência: `RevisaoAcesso`, `ExigirSessaoIntegra`, `ContextoWeb`, `SessaoWeb`, PR #195, #199; subfases 3.1 e 4.3.

---

## UC-018-03 — Abrir um recurso sem identificador na URL

**Ator:** pessoa autenticada com vínculo
**Permissão:** a da tela de destino
**Escopo:** do vínculo
**Pré-condições:** navegação iniciada dentro da aplicação.
**Pós-condições:** tela aberta; o endereço não mostra IDs de turma, aluno, prova ou cartão.
**Tela/rota:** middlewares `RecursoNavegacao` e `RespostaNavegacao`; `NavegacaoWeb`

**Fluxo principal:**
1. Os links da tela carregam uma referência interna, não o ID.
2. O motor de navegação envia a seleção no corpo de um POST, depois de CSRF e revisão de acesso.
3. O servidor valida os parâmetros e confere que correspondem à tela.

**Fluxos alternativos e de erro:**
- 1a. Endereço aberto direto, sem seleção: página de estrutura que pede a seleção, ou "Abra este recurso pela tela correspondente." para arquivos e dados.
- 2a. Seleção enviada na query: 422 "Envie a seleção no corpo da requisição."
- 3a. Parâmetro inválido ou divergente: 422 "Seleção inválida." / "A seleção não corresponde a esta tela."
- 1b. Endereço legado (`/turmas/...`, `/acessos/{acesso}/...`): só redireciona; nunca interpreta IDs como seleção de acesso.

**Critérios de aceite:**
- [ ] Copiar o endereço de uma tela não dá acesso ao recurso em outra sessão.
- [ ] Voltar e avançar no navegador restauram a seleção da entrada de histórico.

**Situação:** Implementado (homologação) — evidência: `RecursoNavegacao`, `RespostaNavegacao`, `NavegacaoWeb`, PR #197; subfase 4.1.

---

## UC-018-04 — Ver fotos e documentos protegidos

**Ator:** pessoa autenticada
**Permissão:** a do recurso (por exemplo `aluno.ver`, `correcao.acompanhar`)
**Escopo:** do vínculo
**Pré-condições:** sessão com vínculo.
**Pós-condições:** arquivo exibido sem link público.
**Tela/rota:** web `/app/perfil/foto`, `/app/usuarios/{id}/foto`, `/app/alunos/{id}/foto`, `/app/cartoes/{id}/imagens/{tipo}`; API correspondente

**Fluxo principal:**
1. A tela busca o arquivo por requisição autenticada e o exibe a partir da memória.
2. A web repassa o pedido à API com o contexto da sessão.

**Fluxos alternativos e de erro:**
- 2a. Sem permissão ou fora do escopo: 403/404.
- 2b. Link assinado antigo de cartão: 404.

**Critérios de aceite:**
- [ ] Respostas com `private, no-store` e `nosniff`.
- [ ] Nenhuma assinatura na URL substitui autenticação.

**Situação:** Implementado (homologação) — evidência: `PerfilController::arquivo`, `CartaoImagemController`, rota `cartoes.arquivo` (404), PR #192, #195; subfases 1.2 e 3.1.

---

## UC-018-05 — Impedir operações simultâneas na mesma sessão

**Ator:** sistema
**Permissão:** —
**Escopo:** sessão
**Pré-condições:** duas requisições da mesma sessão ao mesmo tempo.
**Pós-condições:** uma requisição por vez altera a sessão.
**Tela/rota:** middleware `SerializarSessao`

**Fluxo principal:**
1. Cada requisição obtém o bloqueio da sessão antes de ler e o mantém até salvar.

**Fluxos alternativos e de erro:**
- 1a. Bloqueio não obtido: 423 "Há outra operação em andamento. Tente novamente."
- 1b. Topologia diferente de host único com disco compartilhado: 503.

**Critérios de aceite:**
- [ ] Troca de vínculo e ação concorrente nunca se misturam.
- [ ] ⚠ Antes de múltiplos hosts, substituir por solução distribuída (FN-007).

**Situação:** Implementado (homologação) — evidência: `SerializarSessao`, PR #195.

---

## UC-018-06 — Manter as dependências sem vulnerabilidades conhecidas

**Ator:** equipe de desenvolvimento / CI
**Permissão:** —
**Escopo:** repositório
**Pré-condições:** pull request aberto.
**Pós-condições:** CI reprova dependência com vulnerabilidade conhecida.
**Tela/rota:** `.github/workflows/verificacao.yml` (`composer audit --locked --no-dev`)

**Fluxo principal:**
1. A CI roda a auditoria de dependências em cada verificação.

**Critérios de aceite:**
- [ ] Auditoria sem alertas na `main`.

**Situação:** Implementado (homologação) — evidência: PR #193; subfase 2.1.

---

## UC-018-07 — Levar as correções de dependências à versão de produção

**Ator:** responsável (decisão) e equipe de publicação
**Permissão:** decisão expressa de produção
**Escopo:** produção
**Pré-condições:** decisão §4 itens 2 e 16.
**Pós-condições:** produção com as dependências corrigidas.
**Tela/rota:** PR #194 (UR2-P)

**Fluxo principal (proposto):**
1. Decidir entre promover UR1–UR4 em bloco ou aplicar só o hotfix de dependências.
2. Publicar pelo roteiro de FN-003.

**Critérios de aceite:**
- [ ] Recibo de publicação registrado.

**Situação:** Não implementado em produção — evidência: PR #194 aberto desde 05/11; subfase 2.2.
