# 047 — Qualidade, tentativas, resultado e documentos verificáveis (OMR1–3) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** educadores com acesso à correção (Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador, Professor), Aplicador (envio), Aluno (resultado próprio), público (validação). Ver [docs/03](../../03-perfis-e-permissoes.md) §4.5 e §4.6.
**Atualizado em:** 2026-11-09

Descreve o comportamento **atual** (na homologação); diferenças em relação ao planejado estão marcadas com ⚠. Amplia o UC-005 e o UC-010 do [docs/07](../../07-casos-de-uso.md).

---

## UC-047-01 — Recusar foto sem qualidade para leitura

**Ator:** quem envia cartões (UC-045-02)
**Permissão:** `cartao.enviar`
**Escopo:** prova no escopo
**Pré-condições:** envio de foto.
**Pós-condições:** foto aprovada segue para a fila; foto reprovada não cria cartão e a correção vigente é mantida.
**Tela/rota:** web `correcao.upload`; API `POST v1/provas/{id}/cartoes`

**Fluxo principal:**
1. A API executa `ler_cartao.py --qualidade` sobre a imagem (até 12 s).
2. A análise exige resolução mínima, os 4 marcadores e geometria válida.
3. Aprovada, a qualidade fica gravada no cartão; o worker repete a verificação na leitura.

**Fluxos alternativos e de erro:**
- 2a. Reprovada: erro de validação no campo `imagem` com a mensagem do motor (ex.: "A foto tem pouca resolução. Aproxime a câmera mantendo a folha inteira e envie a foto original.").
- 1a. Verificador indisponível: 503 "A verificação da foto está indisponível. A correção atual foi mantida. Tente novamente."

**Critérios de aceite:**
- [x] A mesma barreira vale para câmera, galeria, upload direto e worker.
- [x] Geometria inválida nunca gera respostas inventadas.

**Situação:** Implementado (homologação) — evidência: `QualidadeCartao`, `omr/qualidade.py`, `FilaCartoes::receber`; teste `QualidadeCartaoTest`

---

## UC-047-02 — Consultar as tentativas de correção de uma prova

**Ator:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador, Professor
**Permissão:** `correcao.acompanhar`
**Escopo:** prova histórica com conteúdo no escopo; alunos das turmas da prova; nunca aplicador
**Pré-condições:** prova com envios.
**Pós-condições:** nenhuma.
**Tela/rota:** web `correcao.tentativas`, `correcao.tentativa`, `correcao.tentativa.imagem`; API `GET v1/provas/{id}/tentativas`, `GET v1/tentativas/{id}`, `GET v1/tentativas/{id}/imagem`

**Fluxo principal:**
1. O usuário abre o histórico da prova ou do aluno.
2. O sistema lista 25 tentativas por página com número, aluno, autor, data, motivo, nota e estado (vigente, aguardando confirmação, descartada, excluída…).
3. Ao abrir uma tentativa, mostra qualidade, leituras, nota, quadro de leituras e eventos com o ator registrado.

**Fluxos alternativos e de erro:**
- 1a. Aluno fora das turmas da prova: 404.
- 3a. Foto arquivada com hash divergente: 409 "Foto arquivada com integridade divergente."

**Critérios de aceite:**
- [x] Eventos são imutáveis e o instantâneo é cifrado.
- [x] A foto só é servida após conferir o SHA-256.

**Situação:** Implementado (homologação) — evidência: `TentativaCartaoController`, `HistoricoCartao`

---

## UC-047-03 — Aplicar ou descartar uma nova tentativa

**Ator:** Administrador da Rede, Diretor Escolar, Coordenador, Professor
**Permissão:** `correcao.alterar`
**Escopo:** prova no escopo
**Pré-condições:** tentativa `aguardando_confirmacao` (nova foto ou vínculo para aluno que já tinha correção); correção aberta ou ator com `correcao.reabrir`.
**Pós-condições:** aplicar — a tentativa vira a correção vigente e a anterior fica no histórico; descartar — a vigente é mantida e o envio é recusado com "Tentativa descartada. Correção vigente mantida."
**Tela/rota:** web `correcao.tentativa.aplicar|descartar`; API `POST v1/tentativas/{id}/aplicar` (`motivo` 10–500, `confirmar` aceito), `POST v1/tentativas/{id}/descartar`

**Fluxo principal:**
1. O usuário compara a proposta com a vigente.
2. Informa o motivo e confirma, ou descarta.

**Fluxos alternativos e de erro:**
- 2a. Tentativa já encerrada: 409 "Esta tentativa já foi encerrada."
- 2b. Foto anterior indisponível: 409 "A foto anterior está indisponível. Restaure o arquivo antes de alterar a correção."

**Critérios de aceite:**
- [x] Nenhuma correção vigente é sobrescrita sem confirmação e motivo.

**Situação:** Implementado (homologação) — evidência: `TentativaCartaoController::aplicar/descartar`, `HistoricoCartao::aplicar`

---

## UC-047-04 — Ver o resultado do aluno com a folha e o histórico

**Ator:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador, Professor; Aluno (só o próprio)
**Permissão:** `resultado.ver`; foto e histórico exigem também `correcao.acompanhar`
**Escopo:** aluno e prova no escopo; aluno pelo próprio vínculo
**Pré-condições:** nota registrada.
**Pós-condições:** nenhuma.
**Tela/rota:** mockup `resultado.html`; web `resultados.show`, `resultados.historico`; API `GET v1/alunos/{id}/resultados/{provaId}`

**Fluxo principal:**
1. O usuário abre o resultado a partir do relatório, do aluno ou da turma.
2. O sistema mostra identificação em 3 linhas, nota, folha de respostas com itens coloridos (leitura anterior e intervenção), qualidade da foto, número da tentativa vigente e, quando houver mais de uma leitura, o histórico paginado.
3. Educadores veem os percentuais de acerto por questão da turma e geral; o aluno não.

**Fluxos alternativos e de erro:**
- 1a. Sem nota: "Resultado ainda não disponível para este aluno nesta prova." (404).
- 2a. Qualidade da foto não verificável: "Não foi possível verificar a qualidade desta foto agora."

**Critérios de aceite:**
- [x] Histórico só aparece com `correcao.acompanhar` e mais de uma leitura.
- [ ] ⚠ O UC-005 prevê "gráfico de acertos por tema"; não existe (depende de FN-043; ver FN-049).

**Situação:** Implementado (homologação) — evidência: `ResultadoController::show` (API e web); teste `CartaoResultadoWebTest`

---

## UC-047-05 — Emitir o PDF do resultado (aluno ou pedagógico)

**Ator:** perfis com `resultado.compartilhar`; o modelo pedagógico exige `relatorio.prova` e não vale para aluno ou aplicador
**Permissão:** `resultado.compartilhar` (+ `relatorio.prova` no modelo `professor` e no lote)
**Escopo:** aluno e prova no escopo
**Pré-condições:** nota registrada; validação de documentos configurada com URL HTTPS.
**Pós-condições:** PDF entregue e emissão registrada em `documento_emissoes`.
**Tela/rota:** web `resultados.pdf`, `resultados.lote`; API `GET v1/alunos/{id}/resultados/{provaId}/pdf?modelo=aluno|professor`, `GET v1/provas/{id}/resultados/pdf` (lote, `turma_id` opcional)

**Fluxo principal:**
1. O usuário escolhe "PDF do aluno" ou "PDF pedagógico" (ou o lote da prova/turma).
2. A API gera com `omr/gerar_resultado.py`, incluindo rodapé com QR e código de validação.
3. Após gerar, revalida versão de acesso e permissão, registra a emissão e entrega.

**Fluxos alternativos e de erro:**
- 1a. Modelo pedagógico sem `relatorio.prova`: 403.
- 1b. Lote sem resultados: "Nenhum resultado disponível para este lote." (422); mais de 500: "O limite é de 500 resultados por PDF. Selecione uma turma." (422).
- 2a. Falha do gerador: "Não foi possível gerar o PDF do resultado." (500).
- 3a. Acesso revogado durante a geração: 403 e arquivo apagado.

**Critérios de aceite:**
- [x] O PDF do aluno não contém média da turma nem percentuais por questão.
- [x] Nenhuma página do lote é liberada se um aluno deixar de estar acessível.

**Situação:** Implementado (homologação) — evidência: `ResultadoController::pdf/lote`, `EmissaoDocumento`; testes `ResultadosLoteTest`, `ResultadosLoteWebTest`

---

## UC-047-06 — Validar publicamente um documento emitido

**Ator:** qualquer pessoa, sem login
**Permissão:** nenhuma (rota pública com limite de 20 por minuto)
**Escopo:** documento emitido pelo Gabarito360
**Pré-condições:** código `G360-` + 32 hexadecimais impresso no PDF.
**Pós-condições:** nenhuma.
**Tela/rota:** web `/validar-documento` (`documentos.validar`, `documentos.conferir`); API `POST v1/documentos/validar` (`codigo`, `sha256` opcional)

**Fluxo principal:**
1. A pessoa informa o código e, opcionalmente, o SHA-256 do arquivo.
2. O sistema confere o selo HMAC do registro e responde: emissão encontrada, arquivo confirmado ou arquivo divergente, com data UTC, modelo, quantidade e instituição.

**Fluxos alternativos e de erro:**
- 2a. Código desconhecido: estado `nao_encontrado`.
- 2b. Selo não confere: 503 "Registro temporariamente indisponível para validação."

**Critérios de aceite:**
- [x] A resposta não expõe dados do aluno; traz `no-store` e `noindex`.

**Situação:** Implementado (homologação) — evidência: `ValidacaoDocumentoController` (API e web), `EmissaoDocumento::verificar`; testes `ValidacaoDocumentoTest`, `ValidacaoDocumentoWebTest`
