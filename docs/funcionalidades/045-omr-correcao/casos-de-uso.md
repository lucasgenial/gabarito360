# 045 — Motor OMR e acompanhamento da correção — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Administrador da Rede, Diretor de Núcleo (só acompanha), Diretor Escolar, Vice-diretor (padrão de leitura e extras), Coordenador, Professor, Professor autônomo, Aplicador (só envia). Ver [docs/03](../../03-perfis-e-permissoes.md) §4.5.
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Descreve o comportamento **atual**; diferenças em relação ao planejado estão marcadas com ⚠. Base: UC-004 do [docs/07](../../07-casos-de-uso.md), que estes casos detalham sem repetir.

---

## UC-045-01 — Gerar o PDF dos cartões-resposta

**Ator:** Professor, Coordenador, Diretor Escolar, Administrador da Rede; Aplicador só com extra
**Permissão:** `cartao.imprimir`
**Escopo:** prova no escopo do usuário; turma opcional, que precisa estar na prova e no escopo
**Pré-condições:** prova existente e aplicável ao ator (`DuplicarProva::conferirAplicacao`).
**Pós-condições:** PDF baixado com um cartão por aluno (nome, matrícula e QR) mais cartões em branco.
**Tela/rota:** web `correcao.pdf`; API `GET v1/provas/{id}/cartoes/pdf` (`turma_id`, `brancos` 0–200)

**Fluxo principal:**
1. O usuário escolhe a prova e, opcionalmente, a turma e a quantidade de cartões em branco.
2. A API gera o PDF com `omr/gerar_cartoes.py` (QR `G360|P{prova}|A{aluno}|N|K|L`, quatro marcadores nos cantos).
3. A API revalida a permissão e o escopo antes de entregar o arquivo (o vínculo pode ter sido revogado durante a geração).

**Fluxos alternativos e de erro:**
- 1a. Turma fora do escopo ou fora da prova: 404.
- 2a. Falha do gerador: "Não foi possível gerar os cartões." (500).

**Regras de negócio:** docs/18 (cartão pré-impresso + QR; cartões em branco vão para vinculação manual).

**Critérios de aceite:**
- [x] PDF contém um cartão por aluno das turmas selecionadas e os brancos pedidos.
- [x] Revogação durante a geração impede a entrega (403) e apaga o arquivo temporário.

**Situação:** Implementado — evidência: `CartaoController::pdf`, `GeradorCartoes`, `omr/gerar_cartoes.py`

---

## UC-045-02 — Enviar a foto de um cartão para leitura

**Ator:** Professor, Coordenador, Diretor Escolar, Administrador da Rede, Aplicador
**Permissão:** `cartao.enviar`
**Escopo:** prova no escopo; aplicador só em provas com data de aplicação igual a hoje
**Pré-condições:** prova `publicada`, `em_correcao` ou `corrigida`; correção aberta, ou ator com `correcao.reabrir`.
**Pós-condições:** cartão `pendente` e envio `na_fila`; o worker lê, grava respostas e nota e atualiza o status da prova.
**Tela/rota:** web `correcao.show` (aplicador: `correcao.camera`), `correcao.upload`, `correcao.envio`; API `POST v1/provas/{id}/cartoes` (`imagem` ≤ 10 MB, `aluno_id` opcional, `envio_id` UUID), `GET .../cartoes/envios/{chave}`, `POST .../repetir`

**Fluxo principal:**
1. O usuário fotografa ou escolhe a imagem; a interface envia com uma chave de envio.
2. A API valida a prova, a correção aberta e o aluno (deve pertencer às turmas da prova) e aplica a barreira de qualidade (FN-047).
3. A foto é guardada; cartão e envio são criados; o job `ProcessarCartaoJob` entra na fila (FN-002). Resposta 202.
4. O worker lê o cartão. Se o QR indicar outra prova da mesma escola acessível ao ator, o cartão é movido para ela.
5. Sem ambiguidade, o cartão fica `lido` e a nota é calculada; com ambiguidade, fica `ambiguo`.
6. A interface consulta o envio pela chave até o resultado.

**Fluxos alternativos e de erro:**
- 2a. Correção fechada sem `correcao.reabrir`: 403 com a mensagem "A correção desta prova foi fechada por … em …. Para enviar, excluir ou alterar cartões, peça à coordenação para reabrir a correção."
- 2b. Prova não publicada: "A prova precisa estar publicada para receber cartões." (422).
- 2c. Aluno fora das turmas: "O aluno não pertence às turmas desta prova."
- 3a. Mesma chave já usada: devolve o envio existente (sem duplicar); chave usada em outra prova: 409.
- 4a. QR de prova indisponível ao ator ou de outra escola: envio recusado com "O QR indica outra prova que não está disponível para correção neste acesso. Confira o cartão e a prova."
- 4b. Cartão impresso com outra quantidade de questões ou alternativas: recusado (`layout_divergente`).

**Regras de negócio:** docs/03 §5 (QR envia para a prova certa da mesma escola); docs/18.

**Critérios de aceite:**
- [x] Envio repetido com a mesma chave não cria segundo cartão.
- [x] Aplicador consegue enviar, mas não vê nota, gabarito nem relatório na resposta.
- [ ] Leitura conferida com foto de cartão impresso real (subfase 1.3).

**Situação:** Parcial — implementado; falta o teste com cartão impresso real. Evidência: `CartaoController::store/envio/repetirEnvio`, `FilaCartoes`, `ProcessarCartaoJob`; testes `CartoesFilaTest`, `CartoesEspacosTest`

---

## UC-045-03 — Acompanhar a correção de uma prova

**Ator:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador, Professor
**Permissão:** `correcao.acompanhar` (lista de cartões e status também aceitam `cartao.enviar`)
**Escopo:** prova no escopo; nunca o aplicador
**Pré-condições:** prova publicada.
**Pós-condições:** nenhuma alteração de estado.
**Tela/rota:** mockup `acompanhar-correcao.html`; web `correcao.show`, `correcao.status`, `correcao.cartoes`; API `GET v1/provas/{id}/cartoes/status`, `GET v1/provas/{id}/cartoes`

**Fluxo principal:**
1. O usuário abre a prova em Corrigir.
2. O sistema mostra total de cartões, lidos, ambíguos e pendentes; o progresso por aluno (esperados, concluídos, sem cartão, em processamento, em revisão, falhas, não identificados, percentual); a lista de ambiguidades; os oito resultados mais recentes; e a situação da correção (aberta ou fechada, por quem e quando).
3. A tela atualiza o status sem recarregar.

**Fluxos alternativos e de erro:**
- 1a. Prova fora do escopo: "Prova não encontrada." (404).

**Regras de negócio:** o percentual só chega a 100% quando todos os alunos esperados estão concluídos.

**Critérios de aceite:**
- [x] Contadores consideram apenas cartões do contexto do usuário.
- [ ] ⚠ O UC-004 do docs/07 fala em "cartões pendentes = 0"; o critério vigente é por aluno esperado. Registrar a mudança no docs/07 (pendência do planejamento, item 8).

**Situação:** Implementado — evidência: `CartaoController::status`, `ProgressoCorrecao`; teste `ProgressoCorrecaoTest`

---

## UC-045-04 — Resolver a ambiguidade de uma questão

**Ator:** Administrador da Rede, Diretor Escolar, Coordenador, Professor
**Permissão:** `correcao.alterar`
**Escopo:** prova no escopo
**Pré-condições:** cartão `ambiguo`; correção aberta ou ator com `correcao.reabrir`.
**Pós-condições:** resposta definida, decisão gravada em `ambiguidade_logs`; sem outras ambíguas, o cartão vira `lido`, a nota é calculada e o status da prova é atualizado.
**Tela/rota:** web `cartoes.resolver-ambiguidade`; API `POST v1/cartoes/{id}/resolver-ambiguidade` (`numero_questao`, `alternativa` A–E, `BRANCO` ou `ANULADA`)

**Fluxo principal:**
1. O usuário escolhe a alternativa, "em branco" ou "anulada" para a questão.
2. A API trava prova e cartão, grava a decisão e registra o evento no histórico do cartão (FN-047).
3. Se não restar ambiguidade, o cartão é concluído.

**Fluxos alternativos e de erro:**
- 1a. Questão inexistente no cartão: "Questão não encontrada no cartão." (404).
- 2a. Correção fechada: 403 com a mensagem de bloqueio (UC-045-02, 2a).
- 2b. Cartão mudou de prova durante a operação: 409.

**Regras de negócio:** "em branco" e "anulada" ficam sem alternativa e não pontuam (decisão 1 do docs/25, ainda não registrada formalmente — ver FN-046).

**Critérios de aceite:**
- [x] Toda decisão fica registrada com usuário e momento.
- [x] O último ambíguo resolvido recalcula a nota.

**Situação:** Implementado — evidência: `CartaoController::resolverAmbiguidade`, `registrarDecisao`

---

## UC-045-05 — Vincular um cartão sem aluno identificado

**Ator:** Administrador da Rede, Diretor Escolar, Coordenador, Professor
**Permissão:** `correcao.alterar`
**Escopo:** prova e aluno no escopo
**Pré-condições:** cartão lido sem aluno (cartão em branco ou QR ilegível); correção aberta ou ator com `correcao.reabrir`.
**Pós-condições:** cartão vinculado e nota calculada; ou, se o aluno já tiver correção, uma tentativa aguardando confirmação (FN-047).
**Tela/rota:** web `cartoes.vincular-aluno`; API `PUT v1/cartoes/{id}/vincular-aluno`

**Fluxo principal:**
1. O usuário escolhe o aluno da turma da prova.
2. A API vincula; se o cartão está `lido`, calcula a nota e registra o evento `vinculada`.

**Fluxos alternativos e de erro:**
- 2a. O aluno já tem cartão lido ou ambíguo: gera proposta (`aguardando_confirmacao`) em vez de sobrescrever; sem leituras completas, 409 "Este aluno já possui correção. Envie outra foto para preparar uma tentativa."
- 2b. Aluno fora das turmas: 404.

**Critérios de aceite:**
- [x] Uma correção existente nunca é sobrescrita sem confirmação.

**Situação:** Implementado — evidência: `CartaoController::vincularAluno`, `HistoricoCartao::propor`

---

## UC-045-06 — Excluir uma correção para refotografar

**Ator:** Administrador da Rede, Diretor Escolar, Coordenador, Professor
**Permissão:** `correcao.alterar`
**Escopo:** prova no escopo
**Pré-condições:** correção aberta ou ator com `correcao.reabrir`; versão (`versao`, 64 caracteres) obtida na confirmação.
**Pós-condições:** cartão, respostas e nota removidos; foto operacional apagada; instantâneo e foto privada preservados no histórico; status da prova recalculado.
**Tela/rota:** web `cartoes.retirada` (preparo) e `cartoes.excluir`; API `DELETE v1/cartoes/{id}` (`motivo` 10–500 caracteres)

**Fluxo principal:**
1. O usuário abre a confirmação, informa o motivo e confirma.
2. A API confere a versão, registra o evento `excluida` com motivo e remove o cartão.

**Fluxos alternativos e de erro:**
- 2a. A correção mudou desde a confirmação: 409 "A correção mudou durante a conferência. Abra novamente a confirmação para ver a versão atual."
- 2b. Cartão mudou de prova: 409.

**Critérios de aceite:**
- [x] Exclusão exige motivo e versão atual.
- [x] O histórico continua consultável depois da exclusão.

**Situação:** Implementado — evidência: `CartaoController::destroy`

---

## UC-045-07 — Fechar e reabrir a correção

**Ator:** fechar — Administrador da Rede, Diretor Escolar, Coordenador, Professor; reabrir — Administrador da Rede, Diretor Escolar, Coordenador (Professor só com extra)
**Permissão:** `correcao.fechar`; `correcao.reabrir`
**Escopo:** prova no escopo
**Pré-condições:** prova publicada.
**Pós-condições:** `provas.correcao_fechada_em/por` preenchidos (fechar) ou limpos (reabrir).
**Tela/rota:** web `correcao.fechar`, `correcao.reabrir`; API `POST v1/provas/{id}/correcao/fechar|reabrir`

**Fluxo principal:**
1. O usuário fecha a correção; a API registra quem e quando ("Correção fechada.").
2. Com a correção fechada, só quem tem `correcao.reabrir` envia, exclui, vincula ou revisa.
3. A supervisão reabre ("Correção reaberta.").

**Fluxos alternativos e de erro:**
- 1a. Já fechada: a operação é idempotente.

**Regras de negócio:** docs/03 §5 (correção fechada); registro de 30/10/2026 no docs/03.

**Critérios de aceite:**
- [x] Professor sem extra recebe a mensagem de bloqueio ao tentar alterar com a correção fechada.

**Situação:** Implementado — evidência: `CartaoController::fecharCorrecao/reabrirCorrecao`, `App\Support\Correcao`

---

## UC-045-08 — Acompanhar a correção de uma turma

**Ator:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador, Professor
**Permissão:** `correcao.acompanhar`
**Escopo:** turma no escopo (professor: as suas); nunca aplicador ou aluno
**Pré-condições:** turma com provas publicadas, em correção ou corrigidas.
**Pós-condições:** nenhuma alteração.
**Tela/rota:** mockup `acompanhar-correcao-turma.html`; web `correcao.turma`; API `GET v1/turmas/{id}/correcao`

**Fluxo principal:**
1. O usuário abre a turma em Corrigir.
2. O sistema mostra as provas da turma × alunos ativos: quem já teve o cartão lido, quem falta, ambíguos e notas.

**Fluxos alternativos e de erro:**
- 1a. Turma fora do escopo, ou ator aplicador/aluno: "Turma não encontrada." (404).

**Critérios de aceite:**
- [x] O professor vê apenas as provas das turmas dele.

**Situação:** Implementado — evidência: `AcompanhamentoTurmaController::show`, commit b1b8c0f (MP-038)
