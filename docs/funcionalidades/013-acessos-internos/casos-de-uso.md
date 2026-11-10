# 013 — Acessos internos (Desenvolvedor, Suporte, Financeiro) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Desenvolvedor, Suporte, Financeiro (docs/03, extensão A1 (`docs/03-perfis-e-permissoes.md`))
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

O docs/07 não tem caso de uso para acessos internos; os casos abaixo são os primeiros.

---

## UC-013-01 — Escolher o acesso interno

**Ator:** pessoa com atribuição interna ativa
**Permissão:** nenhuma para listar; a atribuição precisa estar ativa e vigente
**Escopo:** plataforma (sem escola, núcleo ou rede)
**Pré-condições:** login feito; flags de acessos internos e de vínculos ligadas.
**Pós-condições:** sessão web com contexto interno e token `interno-contexto`; painel interno aberto.
**Tela/rota:** web `GET /app/vinculos`, `POST /app/vinculos/interno` (20/min); API `GET /api/v1/auth/acessos-internos` (30/min), `POST /api/v1/auth/acessos-internos/selecionar` (20/min)

**Fluxo principal:**
1. Após o login, a tela de escolha lista vínculos escolares e acessos internos válidos.
2. A pessoa escolhe o acesso interno.
3. A API confere, sob bloqueio, a atribuição da própria pessoa, cria o token interno ligado ao token de origem e devolve perfil e permissões.
4. A web confere que o acesso devolvido é o pedido e ativa o contexto.

**Fluxos alternativos e de erro:**
- 1a. Atribuição suspensa, vencida, de versão antiga ou conta inativa: não aparece na lista.
- 1b. Área interna desligada (404): a pessoa ainda pode escolher um vínculo escolar.
- 3a. Atribuição de outra pessoa ou mudada no meio: 403; a web mostra "Este acesso não está disponível. Atualize a página para conferir seus acessos."
- 3b. Falha de comunicação: "Não foi possível confirmar a troca. Atualize a página antes de tentar novamente."

**Regras de negócio:** contexto interno e vínculo escolar nunca coexistem no mesmo token; a sessão de origem encerrada invalida o token interno.

**Critérios de aceite:**
- [ ] Em contexto interno, rotas escolares respondem 403.
- [ ] Mudar a versão da atribuição invalida o token já emitido.

**Situação:** Implementado — evidência: `SelecaoInternaController`, `TokenInterno::resolver`, `ContextoInterno::resolver`, PR #102.

---

## UC-013-02 — Usar o painel interno

**Ator:** Desenvolvedor, Suporte, Financeiro
**Permissão:** as do perfil interno
**Escopo:** plataforma
**Pré-condições:** contexto interno ativo.
**Pós-condições:** nenhuma.
**Tela/rota:** web `GET /app/painel` (visão `dashboard/interno`); `GET /api/v1/auth/me` com `tipo_contexto = interno`

**Fluxo principal:**
1. O painel mostra apenas atalhos das áreas permitidas (clientes, acessos internos, funções, catálogo, pedidos), sem dados pedagógicos.

**Critérios de aceite:**
- [ ] Nenhum dado de aluno, turma, prova ou resultado aparece no painel interno.

**Situação:** Implementado — evidência: `AuthController::me` (ramo interno), `apps/web/resources/views/dashboard/interno.blade.php`.

---

## UC-013-03 — Conceder acesso de Suporte ou Financeiro

**Ator:** Desenvolvedor
**Permissão:** `acesso_interno.gerir`
**Escopo:** plataforma
**Pré-condições:** contexto interno de Desenvolvedor; conta alvo ativa e com a senha inicial trocada.
**Pós-condições:** atribuição ativa (versão 1) e evento `concessao` com motivo.
**Tela/rota:** web `GET|POST /acessos-internos`; API `GET|POST /api/v1/acessos-internos` (10/min)

**Fluxo principal:**
1. O Desenvolvedor informa e-mail da conta, perfil (`suporte` ou `financeiro`), fim da vigência opcional e motivo (5 a 200 caracteres).
2. A API bloqueia o controle, confere o próprio Desenvolvedor, localiza a conta e grava a atribuição e o evento.
3. A web mostra "Acesso salvo. A pessoa deve selecionar novamente seu acesso."

**Fluxos alternativos e de erro:**
- 2a. Conta inexistente, inativa ou com troca de senha pendente: "Conta elegível não encontrada."
- 2b. Conta do próprio ator ou de outro Desenvolvedor: 403.
- 2c. Atribuição igual já existente: "A atribuição já existe. Edite sua situação e vigência."
- 2d. Campos extras no pedido: "Campos não permitidos."

**Regras de negócio:** ninguém gere pares, eleva a si mesmo ou concede Desenvolvedor.

**Critérios de aceite:**
- [ ] Todo acesso concedido tem evento com ator, motivo e estado.
- [ ] Desenvolvedor não aparece na lista de gestão.

**Situação:** Implementado — evidência: `GestaoAcessosInternos::conceder`, `AcessoInternoController`, PR #103.

---

## UC-013-04 — Suspender, reativar ou alterar a vigência

**Ator:** Desenvolvedor
**Permissão:** `acesso_interno.gerir`
**Escopo:** plataforma
**Pré-condições:** atribuição de Suporte ou Financeiro existente.
**Pós-condições:** nova versão da atribuição; tokens anteriores dessa atribuição deixam de valer; evento `alteracao` com estado anterior e atual.
**Tela/rota:** web `PUT /acessos-internos/{id}`; API `PUT /api/v1/acessos-internos/{id}` (20/min)

**Fluxo principal:**
1. O Desenvolvedor escolhe situação (`ativo` ou `suspenso`), fim da vigência e motivo, enviando a versão que viu.
2. A API confere a versão, grava e incrementa a versão.

**Fluxos alternativos e de erro:**
- 2a. Versão diferente: 409 "O acesso mudou. Confira a versão atual e tente novamente."
- 2b. Reativar conta inativa ou com troca de senha pendente: "A conta não pode ser ativada."
- 2c. Tentar mudar pessoa ou perfil: "Pessoa e perfil não podem ser alterados."

**Critérios de aceite:**
- [ ] A suspensão vale no pedido seguinte da pessoa suspensa.

**Situação:** Implementado — evidência: `GestaoAcessosInternos::atualizar`.

---

## UC-013-05 — Iniciar o primeiro Desenvolvedor

**Ator:** operador do servidor
**Permissão:** acesso ao console do servidor
**Escopo:** plataforma
**Pré-condições:** aplicação em manutenção; flag de acessos internos desligada; nenhum Desenvolvedor existente; conta ativa e com senha trocada.
**Pós-condições:** atribuição de Desenvolvedor, evento `bootstrap` e marcador de bootstrap gravado.
**Tela/rota:** `php artisan g360:iniciar-desenvolvedor {usuario} --aplicar --confirmar-usuario= --motivo=`

**Fluxo principal:**
1. Sem `--aplicar`, o comando só simula.
2. Com `--aplicar`, grava a atribuição e encerra o bootstrap.

**Fluxos alternativos e de erro:**
- 1a. Bootstrap já feito ou Desenvolvedor existente: 409 "Bootstrap já encerrado ou Desenvolvedor existente."
- 1b. Fora da manutenção ou com a área interna ligada: 409.

**Critérios de aceite:**
- [ ] O comando não roda duas vezes.

**Situação:** Implementado — evidência: `IniciarDesenvolvedor`, `GestaoAcessosInternos::iniciarDesenvolvedor`.
