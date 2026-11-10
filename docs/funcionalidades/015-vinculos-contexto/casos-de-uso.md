# 015 — Vínculos por pessoa e seleção de vínculo — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis escolares e de rede/núcleo como titulares de vínculo; gestores com `usuario.gerir` como emissores de convite ([docs/03](../../03-perfis-e-permissoes.md))
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

Relação com [docs/07](../../07-casos-de-uso.md): UC-001 (passo "identifica o perfil" passa pela escolha de vínculo, UC-015-01) e UC-008 (convite e gestão por vínculo, UC-015-03 a UC-015-07). O transporte do contexto (sessão única, nada na URL) está em FN-018.

---

## UC-015-01 — Escolher o vínculo ao entrar

**Ator:** pessoa autenticada com um ou mais vínculos
**Permissão:** nenhuma para listar
**Escopo:** espaço do vínculo escolhido
**Pré-condições:** login feito; regime de vínculos obrigatório.
**Pós-condições:** token `vinculo-contexto` na sessão; perfil, permissões e espaço ativos; painel aberto.
**Tela/rota:** extensão V2-B `auth/vinculo`; web `GET /app/vinculos`, `POST /app/vinculos` (20/min); API `GET /api/v1/auth/vinculos` (30/min), `POST /api/v1/auth/vinculos/selecionar` (20/min)

**Fluxo principal:**
1. A tela lista os vínculos válidos (perfil, nome e tipo do espaço) e, se houver, os acessos internos (FN-013).
2. A pessoa escolhe um vínculo.
3. A API resolve o vínculo da própria conta (situação ativa, vigência, espaço válido), calcula as permissões e cria o token ligado ao token de origem.
4. A web confere que o vínculo devolvido é o pedido, ativa o contexto e abre `/app/painel`.

**Fluxos alternativos e de erro:**
- 1a. Nenhum vínculo válido: estado vazio com "Verificar novamente" e "Sair da conta".
- 1b. Sessão expirada na API (401): "Entre novamente para continuar."
- 1c. Regime de vínculos desligado: entrada legada pelo perfil da conta (`POST /app/vinculos/legado`).
- 3a. Vínculo indisponível: 403; a web mostra "Este vínculo não está disponível. Atualize a página para conferir seus acessos."
- 3b. Falha de comunicação: "Não foi possível confirmar a troca. Atualize a página antes de tentar novamente."

**Regras de negócio:** nunca escolher sozinho o vínculo mais recente; mostrar todos os válidos (docs/115 §5).

**Critérios de aceite:**
- [ ] Vínculos suspensos, encerrados, futuros ou de espaço inválido não aparecem.
- [ ] O contexto vale só para a conta dona do vínculo.
- [ ] ⚠ Aceite visual de V2 registrado (subfase 2.4).

**Situação:** Implementado — evidência: `SelecaoVinculoController`, `Auth\VinculoController::index/store`, PR #93, #94.

---

## UC-015-02 — Aceitar ou recusar o convite de primeira conta

**Ator:** pessoa cadastrada por um gestor (UC-012-03)
**Permissão:** nenhuma (rota pública)
**Escopo:** vínculo convidado
**Pré-condições:** convite vigente (7 dias) recebido por e-mail com código de 64 caracteres.
**Pós-condições:** aceite → senha criada e vínculo do convite aceito; recusa → convite anulado e emissor avisado.
**Tela/rota:** web `GET /convite`, `POST /convite/conferir` (20/min), `POST /redefinir-senha`, `POST /convite/recusar`; API `POST /api/v1/auth/convite` (20/min), `POST /api/v1/auth/redefinir-senha`, `POST /api/v1/auth/convite/recusar` (10/min)

**Fluxo principal:**
1. A pessoa abre `/convite` e cola e-mail e código.
2. A API confere o convite e mostra a função, o espaço e quem convidou.
3. A pessoa cria a senha (mínimo 8, com confirmação).
4. A API aceita o contexto do convite, grava a senha e responde "Senha criada."; a pessoa entra pelo login.

**Fluxos alternativos e de erro:**
- 1a. Abrir `/convite` com código, conta ou e-mail na URL: redireciona e orienta "Cole o código recebido por e-mail no formulário...".
- 2a. Convite inválido ou vencido: "Não foi possível conferir o convite. Confira o e-mail e o código ou peça um novo convite."
- 3a. Recusar: "Convite recusado. Avisamos quem enviou."

**Regras de negócio:** o gestor nunca vê nem define a senha; convite legado sem vínculo exige conciliação.

**Critérios de aceite:**
- [ ] Código usado uma vez; vencido após 7 dias.
- [ ] ⚠ Teste real de entrega do e-mail (E5) registrado em FN-060.

**Situação:** Implementado — evidência: `AuthController::consultarConvite`, `AuthController::recusarConvite`, `ContaUsuario::convidar`, `LoginController::conferirConvite`, PR #91.

---

## UC-015-03 — Convidar uma conta existente para um novo vínculo

**Ator:** gestor com `usuario.gerir`
**Permissão:** `usuario.gerir` (e `turma.vincular_professor` para turmas do professor)
**Escopo:** espaço dentro do alcance do gestor
**Pré-condições:** gestor em vínculo selecionado; perfil gerível e compatível com o tipo do espaço.
**Pós-condições:** vínculo `convidado` (novo ou reaproveitado), convites anteriores do mesmo vínculo expirados, novo convite de 7 dias e e-mail enviado.
**Tela/rota:** extensão V2-C; web `GET|POST /membros/convidar-vinculo`; API `GET /api/v1/usuarios/convites-vinculo/opcoes`, `POST /api/v1/usuarios/convites-vinculo` (10/min)

**Fluxo principal:**
1. O gestor informa e-mail, espaço, perfil e, para professor, turmas.
2. A API confere destino, perfil, turmas no escopo e grupos funcionais (FN-014).
3. Cria ou reaproveita o vínculo `convidado`, registra o evento `convite_emitido` e envia o código ao titular.
4. A resposta é sempre "Se a conta puder receber este vínculo, o convite será enviado."

**Fluxos alternativos e de erro:**
- 2a. Conta inexistente, inativa, o próprio gestor, papel já ativo, alcance diferente, vigência fora ou primeira conta ainda pendente: nada é enviado e a resposta é a mesma (sem revelar o motivo).
- 2b. Turmas para perfil que não é professor, ou sem `turma.vincular_professor`: 403.

**Regras de negócio:** não alterar alcance nem revelar vínculo existente; o token nunca volta ao gestor por HTTP.

**Critérios de aceite:**
- [ ] A resposta não permite descobrir se a conta existe.
- [ ] Um novo convite invalida os anteriores do mesmo vínculo.

**Situação:** Implementado — evidência: `ConviteVinculo::emitir`, `ConviteVinculoController` (API e web), PR #95.

---

## UC-015-04 — Responder a um convite de vínculo

**Ator:** titular da conta convidada
**Permissão:** nenhuma; precisa estar logado na conta convidada
**Escopo:** vínculo convidado
**Pré-condições:** convite vigente; vínculo `convidado` e vigente.
**Pós-condições:** aceite → vínculo ativo; recusa → convite recusado; outros vínculos preservados.
**Tela/rota:** web `GET /app/vinculos/convite`, `POST /app/vinculos/convite/consultar` (20/min), `POST /app/vinculos/convite/responder` (10/min); API `POST /api/v1/auth/convites-vinculo/{id}/consultar`, `POST /api/v1/auth/convites-vinculo/{id}/responder`

**Fluxo principal:**
1. Logado, o titular cola o código `{número}.{64 caracteres}`.
2. A API confere que o convite é da própria conta e mostra função, espaço, emissor e validade.
3. A web guarda a conferência cifrada por 20 minutos e pede a confirmação.
4. O titular aceita ou recusa; a API revalida emissor e vigência e grava.
5. A web volta à escolha de vínculo: "Vínculo aceito. Escolha onde deseja entrar." ou "Convite recusado. Seus outros vínculos foram preservados."

**Fluxos alternativos e de erro:**
- 1a. Sem login: login com `retorno_convite` e volta ao convite.
- 2a. Código errado, vencido ou de outra conta: "Convite indisponível. Confira a conta e o código ou peça um novo convite."
- 4a. Conferência expirada: "A conferência expirou. Cole novamente o código do convite."
- 4b. Falha ao confirmar: "Não foi possível confirmar. Confira novamente o código e a conta usada."

**Critérios de aceite:**
- [ ] Contexto interno não responde convites (403).
- [ ] Recusar não afeta outros vínculos.

**Situação:** Implementado — evidência: `ConviteVinculo::responder`, `ConviteVinculoController::consultar/responder`, PR #95.

---

## UC-015-05 — Suspender ou reativar um vínculo

**Ator:** gestor com `usuario.gerir`
**Permissão:** `usuario.gerir`
**Escopo:** espaço do gestor
**Pré-condições:** vínculo `ativo` ou `suspenso` no alcance do gestor.
**Pós-condições:** situação alterada e evento gravado.
**Tela/rota:** web `POST /membros/{id}/toggle`; API `POST /api/v1/usuarios/{id}/toggle` (delegado a `GestaoVinculoController::toggle`)

**Fluxo principal:**
1. O gestor aciona ativar/desativar na equipe.
2. A API alterna entre `ativo` e `suspenso`, conferindo vigência e grupos funcionais ao reativar.

**Fluxos alternativos e de erro:**
- 2a. Vínculo convidado ou encerrado: "Vínculo não pode ser reativado por esta operação."
- 2b. Fora da vigência: "Vigência do vínculo não permite ativação."
- 2c. Mudar papel ou escola na edição: "Mudança de papel exige outro vínculo; preserve o histórico deste." / "Transferência exige outro vínculo."

**Critérios de aceite:**
- [ ] Suspender um vínculo não afeta os outros vínculos da pessoa.

**Situação:** Implementado — evidência: `GestaoVinculoController::toggle/update`, PR #91.

---

## UC-015-06 — Ajustar permissões de um vínculo

**Ator:** gestor com `usuario.permissoes`
**Permissão:** `usuario.permissoes` (ver: `usuario.ver`)
**Escopo:** espaço do gestor
**Pré-condições:** vínculo no alcance do gestor.
**Pós-condições:** extras e retiradas do vínculo gravadas; eventos com autor e validade.
**Tela/rota:** web `GET|PUT /membros/{id}/permissoes`; API `GET|PUT /api/v1/usuarios/{id}/permissoes` (delegado a `GestaoVinculoController`)

**Fluxo principal:**
1. O gestor vê, por permissão: tipo (padrão/extra), estado, validade, quem definiu e se pode alterar; as efetivas; os últimos 30 eventos.
2. Altera estados (`padrao`, `conceder`, `retirar`) com validade opcional.
3. A API aplica as regras de UC-012-06 item a item.

**Fluxos alternativos e de erro:**
- 3a. Item proibido: 403 com o motivo.
- 1a. A própria pessoa vê suas permissões, mas não pode alterá-las.

**Critérios de aceite:**
- [ ] O ajuste vale só para aquele vínculo.

**Situação:** Implementado — evidência: `GestaoVinculoController::permissoes/definirPermissoes`.

---

## UC-015-07 — Encerrar um vínculo

**Ator:** gestor com `usuario.gerir`
**Permissão:** `usuario.gerir`
**Escopo:** espaço do gestor
**Pré-condições:** vínculo `ativo`, `suspenso` ou `convidado`.
**Pós-condições:** situação `encerrado`, `fim_em` agora, evento `encerrado`; histórico preservado.
**Tela/rota:** web `POST /membros/{id}/encerrar-vinculo`; API `POST /api/v1/usuarios/{id}/encerrar-vinculo`

**Fluxo principal:**
1. O gestor encerra o vínculo.
2. A API grava e responde "Vínculo encerrado. O histórico foi preservado."

**Fluxos alternativos e de erro:**
- 2a. Já encerrado: "Vínculo já encerrado."

**Critérios de aceite:**
- [ ] Vínculo encerrado não pode ser reativado; um novo papel exige novo convite.

**Situação:** Implementado — evidência: `GestaoVinculoController::encerrar`.

---

## UC-015-08 — Trocar de vínculo durante o uso

**Ator:** pessoa com mais de um vínculo
**Permissão:** nenhuma
**Escopo:** novo vínculo
**Pré-condições:** sessão ativa.
**Pós-condições:** contexto substituído pelo novo vínculo.
**Tela/rota:** troca no topo → `GET /app/vinculos?trocar=1`, `POST /app/vinculos`

**Fluxo principal:**
1. A pessoa aciona a troca no topo.
2. Escolhe outro vínculo como em UC-015-01.

**Regras de negócio:** desde UR4 há um vínculo por sessão, não por aba (FN-018, UC-018-02).

**Critérios de aceite:**
- [ ] Depois da troca, ações de telas abertas com o vínculo anterior são recusadas com aviso de acesso alterado.

**Situação:** Implementado — evidência: `Auth\VinculoController`, `RevisaoAcesso` (web).

---

## UC-015-09 — Tratar conta de aluno sem vínculo

**Ator:** responsável (decisão) e gestor
**Permissão:** —
**Escopo:** escola
**Pré-condições:** conta com perfil `aluno` sem vínculo convertido.
**Pós-condições:** a definir.
**Tela/rota:** —

**Fluxo principal:** proposto. Hoje a conta não tem vínculo válido e vê o estado vazio de UC-015-01. O destino depende da decisão sobre o login do aluno (FN-020, §4 item 10).

**Critérios de aceite:**
- [ ] Decisão registrada no plano mestre.

**Situação:** Não implementado — evidência: docs/60, docs/100; subfase 2.5.
