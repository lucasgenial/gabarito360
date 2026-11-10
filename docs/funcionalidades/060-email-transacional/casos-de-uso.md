# 060 — E-mail transacional e Fale Conosco — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** visitante (Fale Conosco), titular da conta (senha e e-mail), gestores com `usuario.gerir` (convites), equipe do Gabarito360 (destino do Fale Conosco; teste de envio). Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Os fluxos de login do UC-001 e de equipe do UC-008 ([docs/07](../../07-casos-de-uso.md)) não são repetidos; aqui só o que envolve e-mail. Todos os envios usam `App\Mail\EmailModelo` pela fila `padrao` e são registrados em `emails_enviados` (modelo, hash do destinatário, data), sem o corpo.

---

## UC-060-01 — Pedir recuperação de senha

**Ator:** titular da conta (não autenticado)
**Permissão:** pública, `throttle:5,1`
**Escopo:** a própria conta
**Pré-condições:** nenhuma
**Pós-condições:** se a conta existir e estiver ativa, token de 60 min gravado (hash) e e-mail `recuperar-senha` enfileirado
**Tela/rota:** web `GET/POST /esqueci-senha` (`LoginController`); API `POST /auth/esqueci-senha` (`AuthController::esqueceuSenha`, `ContaUsuario::enviarLinkDeSenha`)

**Fluxo principal:**
1. Usuário informa o e-mail.
2. A API procura conta ativa pelo hash do e-mail; se houver, grava o token e envia link e código.
3. Responde sempre "Se o e-mail estiver cadastrado, as instruções e o código de recuperação foram enviados."

**Fluxos alternativos e de erro:**
- 2a. E-mail inexistente ou conta inativa: mesma resposta, nada enviado.
- 2b. Gestor em contexto de vínculo tenta `POST /usuarios/{id}/link-senha`: 422 "A recuperação da conta deve ser solicitada pelo titular em Esqueci minha senha." (fora do vínculo, o gestor ainda pode enviar o link, com `usuario.gerir` e `throttle:5,1`).

**Regras de negócio:** docs/28 E3.

**Critérios de aceite:**
- [x] Mesma resposta para e-mail existente ou não (teste `test_email_desconhecido_recebe_a_mesma_resposta_e_nada_e_enviado`).
- [x] Envio pela fila (teste `test_recuperacao_envia_link_pela_fila`).
- [ ] ⚠ Limite só por IP (rota); não há limite por e-mail (docs/28 E3 passo 3). Resolve: subfase 3.1 ou decisão de dispensa.

**Situação:** Implementado — evidência: `AuthController::esqueceuSenha`; `EmailsTest`

---

## UC-060-02 — Redefinir a senha pelo código

**Ator:** titular da conta
**Permissão:** pública, `throttle:10,1`
**Escopo:** a própria conta
**Pré-condições:** token válido
**Pós-condições:** senha trocada, token apagado, sessões (tokens Sanctum) encerradas, e-mail `senha-alterada` enviado (exceto quando é a primeira senha de um convite)
**Tela/rota:** web `GET/POST /redefinir-senha`; API `POST /auth/redefinir-senha`

**Fluxo principal:**
1. Usuário informa e-mail, código e a nova senha (mínimo 8, confirmada).
2. A API confere o token sob bloqueio, apaga-o, confere a validade (60 min) e troca a senha.
3. Envia o aviso de senha alterada.

**Fluxos alternativos e de erro:**
- 1a. Código na query string: 422 "Envie o código no corpo da requisição."
- 2a. Token inválido ou já usado: 422 "Token inválido ou expirado."
- 2b. Token vencido: 422 "Código expirado. Solicite um novo código de recuperação."

**Regras de negócio:** docs/28 E3; FN-018 (UR4, sem credencial na URL).

**Critérios de aceite:**
- [x] Token vale uma vez e avisa a troca (`test_token_so_vale_uma_vez_e_avisa_a_troca`).
- [x] Token com mais de 60 min recusado (`test_token_com_mais_de_60_minutos_e_recusado`).
- [x] Credenciais na URL não executam ações (`test_credenciais_na_url_nao_executam_acoes_de_email`).

**Situação:** Implementado — evidência: `AuthController::redefinirSenha`; `EmailsTest`

---

## UC-060-03 — Convidar usuário e criar a senha pelo link

**Ator:** gestor com `usuario.gerir` (convida); convidado (cria a senha)
**Permissão:** `usuario.gerir`
**Escopo:** conforme a seção 3 do docs/03 (quem cadastra quem)
**Pré-condições:** conta criada pelo gestor
**Pós-condições:** convite válido por 7 dias; ao criar a senha, contexto do convite aceito
**Tela/rota:** web `/membros/*` e `/convite`; API cadastro de usuário, `POST /usuarios/{id}/convite` (reenvio), `POST /auth/convite` (consultar) e `POST /auth/redefinir-senha` (criar senha)

**Fluxo principal:**
1. Ao cadastrar a conta, `ContaUsuario::convidar` grava o token com validade de 7 dias e envia `convite-usuario` com função, vínculo e quem convidou.
2. O convidado abre `/convite`, confere o convite e define a senha.

**Fluxos alternativos e de erro:**
- 1a. Reenvio: em contexto de vínculo, só se o vínculo estiver "convidado" (senão 422 "Este vínculo não aguarda convite."); responde "Convite enviado."
- 2a. Convite vencido: recusado (`test_convite_vencido_e_recusado`).

**Regras de negócio:** docs/28 E5.

**Critérios de aceite:**
- [x] Convite informa função e quem convidou e cria a senha (`test_convite_informa_funcao_e_quem_convidou_e_cria_a_senha`).
- [ ] ⚠ Falta teste com convite real na homologação. Resolve: pendência de FN-060 no plano mestre (testes reais de E5/E6).

**Situação:** Implementado (sem teste real) — evidência: `ContaUsuario::convidar`; `UsuarioController::reenviarConvite`; `ConviteInterfaceTest`

---

## UC-060-04 — Recusar convite

**Ator:** convidado
**Permissão:** pública, `throttle:10,1`
**Escopo:** o próprio convite
**Pré-condições:** convite válido
**Pós-condições:** token apagado, conta desativada, e-mail `convite-recusado` a quem convidou
**Tela/rota:** web `POST /convite/recusar`; API `POST /auth/convite/recusar`

**Fluxo principal:**
1. O convidado recusa; a API confere o convite sob bloqueio e desativa a conta.
2. Avisa o gestor que convidou.

**Fluxos alternativos e de erro:**
- 1a. Convite indisponível ou vencido: 422 "Convite indisponível."

**Regras de negócio:** docs/28 E5 (extensão).

**Critérios de aceite:**
- [x] Recusa desativa a conta e avisa quem convidou (`test_recusar_desativa_a_conta_e_avisa_quem_convidou`).

**Situação:** Implementado — evidência: `ContaUsuario::recusarConvite`

---

## UC-060-05 — Trocar o e-mail da conta

**Ator:** usuário autenticado
**Permissão:** autenticado, `throttle:5,1`
**Escopo:** a própria conta
**Pré-condições:** senha atual conhecida
**Pós-condições:** pedido de troca gravado (novo e-mail cifrado, token com hash, validade 24 h); e-mail `confirmar-email` ao novo endereço
**Tela/rota:** web `POST /perfil/email`; API `POST /auth/email` (`AuthController::trocarEmail`)

**Fluxo principal:**
1. Usuário informa o novo e-mail e a senha atual.
2. A API valida unicidade e senha e envia o código ao novo endereço.
3. Responde "Enviamos um código de confirmação para o novo e-mail."

**Fluxos alternativos e de erro:**
- 2a. Senha atual incorreta: 422 "Senha atual incorreta." (`test_troca_exige_a_senha_atual`).
- 2b. E-mail em uso: "Este e-mail já está em uso."

**Regras de negócio:** docs/28 E6.

**Critérios de aceite:**
- [x] Troca exige a senha atual.
- [ ] ⚠ Falta teste real. Resolve: pendência de FN-060.

**Situação:** Implementado (sem teste real) — evidência: `AuthController::trocarEmail`; `ContaUsuario::pedirTrocaDeEmail`

---

## UC-060-06 — Confirmar o novo e-mail

**Ator:** titular da conta
**Permissão:** pública
**Escopo:** a própria conta
**Pré-condições:** pedido de troca válido (24 h)
**Pós-condições:** e-mail trocado; tokens de senha pendentes do endereço anterior apagados; `email-alterado-aviso` ao endereço anterior
**Tela/rota:** web `GET/POST /confirmar-email`; API `POST /auth/confirmar-email`

**Fluxo principal:**
1. Usuário informa o código recebido.
2. A API confere o hash, a validade e a unicidade e troca o e-mail.
3. Avisa o endereço anterior.

**Fluxos alternativos e de erro:**
- 2a. Código vencido ou novo e-mail já usado por outra conta: recusado (`test_link_de_troca_vencido_e_recusado`).

**Regras de negócio:** docs/28 E6.

**Critérios de aceite:**
- [x] E-mail só muda depois de confirmar e avisa o anterior (`test_email_so_muda_depois_de_confirmar_e_avisa_o_anterior`).

**Situação:** Implementado — evidência: `ContaUsuario::confirmarTrocaDeEmail`

---

## UC-060-07 — Enviar mensagem pelo Fale Conosco

**Ator:** visitante
**Permissão:** pública; web e API com `throttle:5,1`; Cloudflare Turnstile quando habilitado
**Escopo:** não se aplica
**Pré-condições:** nenhuma
**Pós-condições:** lead gravado; `fale-conosco-equipe` ao endereço de contato com responder-para do visitante; `fale-conosco-confirmacao` ao visitante
**Tela/rota:** web `GET/POST /fale-conosco`, `/fale-conosco/obrigado`; API `POST /leads` (`LeadController::store`); `/checkout` redireciona para cá (contratação assistida)

**Fluxo principal:**
1. Visitante informa nome, e-mail, assunto, telefone, instituição, cargo, licenças estimadas e mensagem.
2. A API valida (Turnstile se ativo), grava o lead e enfileira os dois e-mails.
3. Responde "Recebemos seu contato! Nossa equipe entrará em contato em breve." (201).

**Fluxos alternativos e de erro:**
- 2a. Validação falha (nome, e-mail obrigatórios; limites de tamanho): 422.
- 2b. Turnstile inválido: recusado.
- 2c. Excesso de envios do mesmo IP: 429.

**Regras de negócio:** docs/28 E7; RN-015.1 (planos comerciais por contato).

**Critérios de aceite:**
- [x] Avisa a equipe e confirma a quem escreveu (`test_fale_conosco_avisa_a_equipe_e_confirma_a_quem_escreveu`).
- [x] Envio real confirmado pelo responsável (docs/28 §6).

**Situação:** Implementado — evidência: `LeadController`; `TurnstileTest`; `ContratacaoContatoTest`

---

## UC-060-08 — Enviar e-mail de teste (operação)

**Ator:** operador do servidor
**Permissão:** acesso ao console do servidor
**Escopo:** plataforma
**Pré-condições:** `.env` com SMTP configurado
**Pós-condições:** e-mail `senha-alterada` de teste enfileirado
**Tela/rota:** `php artisan g360:email-teste <destino>` (`EmailTeste`)

**Fluxo principal:**
1. Operador executa o comando com um destino de teste.
2. Confere a chegada com SPF, DKIM e DMARC aprovados.

**Fluxos alternativos e de erro:**
- 2a. Falha de envio: registrada em `failed_jobs`.

**Regras de negócio:** docs/28 E2; docs/operacao/email.md.

**Critérios de aceite:**
- [x] Comando disponível.

**Situação:** Implementado — evidência: `apps/api/app/Console/Commands/EmailTeste.php`

---

## UC-060-09 — Pré-visualizar modelos de e-mail

**Ator:** administrador
**Permissão:** a de `/referencia-visual` (autenticado)
**Escopo:** plataforma
**Pré-condições:** modelos da fase
**Pós-condições:** nenhuma
**Tela/rota:** `/referencia-visual` (`referencia/index.blade.php`)

**Fluxo principal:**
1. Administrador abre a referência visual e vê cada modelo renderizado com dados fictícios.

**Fluxos alternativos e de erro:**
- 1a. Modelo inexistente: erro "Modelo de e-mail não encontrado".

**Regras de negócio:** docs/28 E4 ("Pronto quando").

**Critérios de aceite:**
- [ ] Cada modelo da fase tem pré-visualização e teste de renderização.

**Situação:** Não implementado — ⚠ a referência visual não contém os modelos de e-mail, embora a fase 1 conste como entregue. Resolve: subfase 3.1.

---

## UC-060-10 — Enviar e-mails das fases 2 a 4

**Ator:** sistema
**Permissão:** a do evento de origem, revalidada
**Escopo:** o do evento
**Pré-condições:** FN-057 (fase 2), FN-034 (fase 3), FN-059 (fase 4)
**Pós-condições:** e-mails enviados pela fila e registrados
**Tela/rota:** `EmailModelo`, modelos novos em `apps/api/resources/emails/modelos/`

**Fluxo principal:**
1. O módulo de origem (ou o Notificador de FN-057) pede o envio com o modelo da fase: `vinculo-escola`, `permissao-alterada`, `conta-suspensa`, `notificacao-geral`, `resumo-diario`, `suporte-ver-como` (fase 2); `importacao-concluida` (fase 3); `prova-agendada`, `prova-lembrete`, `correcao-concluida`, `correcao-fechada` (fase 4).

**Fluxos alternativos e de erro:**
- 1a. Destinatário revogado ou preferência desligada: não envia (FN-057).

**Regras de negócio:** docs/28 E4 e N3.

**Critérios de aceite:**
- [ ] Teste de renderização e pré-visualização por modelo.

**Situação:** Não implementado — subfases 3.1, 3.2 e 3.3.
