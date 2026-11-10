# 046 — Fotos da correção e revisão de leituras — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Administrador da Rede, Diretor de Núcleo (só vê), Diretor Escolar, Coordenador, Professor. Ver docs/03 (`docs/03-perfis-e-permissoes.md`) §4.5.
**Atualizado em:** 2026-11-09

Descreve o comportamento **atual**; diferenças em relação ao planejado estão marcadas com ⚠. Complementa o sub-fluxo de ambíguos do UC-004 e o "Revisar leitura" do UC-005 do docs/07 (`docs/07-casos-de-uso.md`).

---

## UC-046-01 — Revisar as questões pendentes de uma prova

**Ator:** Administrador da Rede, Diretor Escolar, Coordenador, Professor (Diretor de Núcleo só consulta)
**Permissão:** `correcao.acompanhar` para abrir; `correcao.alterar` para decidir
**Escopo:** prova no escopo; nunca o aplicador
**Pré-condições:** existem cartões `ambiguo` com questões ambíguas.
**Pós-condições:** cada decisão segue o UC-045-04.
**Tela/rota:** web `correcao.revisao`; API `GET v1/provas/{id}/revisao`

**Fluxo principal:**
1. O usuário abre "Revisar" na prova.
2. O sistema lista as questões ambíguas ordenadas por aluno, com turma, alternativas detectadas, recorte da questão e links autenticados da foto original e endireitada.
3. O usuário decide uma questão por vez (alternativa, em branco ou anulada).

**Fluxos alternativos e de erro:**
- 1a. Prova fora do escopo: "Prova não encontrada." (404).
- 2a. Foto endireitada ausente: só a original é oferecida.

**Critérios de aceite:**
- [x] A fila só contém cartões do contexto do usuário.
- [x] O recorte corresponde à questão (layout de `omr/layout.py`).

**Situação:** Implementado — evidência: `CartaoController::revisao`, PR #41 (R2)

---

## UC-046-02 — Revisar a leitura de um cartão já lido

**Ator:** Administrador da Rede, Diretor Escolar, Coordenador, Professor
**Permissão:** `correcao.alterar`
**Escopo:** prova no escopo
**Pré-condições:** cartão `lido`; correção aberta ou ator com `correcao.reabrir`.
**Pós-condições:** resposta alterada, decisão em `ambiguidade_logs`, `revisado_por/em` preenchidos, nota recalculada e evento `revisada` no histórico (FN-047).
**Tela/rota:** web `correcao.revisao` com o cartão (`?cartao=`) e `cartoes.revisar`; API `POST v1/cartoes/{id}/revisar`

**Fluxo principal:**
1. Do resultado ou do acompanhamento, o usuário abre a revisão do cartão.
2. O sistema mostra todas as questões do cartão.
3. O usuário altera a questão; a nota é recalculada.

**Fluxos alternativos e de erro:**
- 1a. Cartão não lido: "Apenas cartões já lidos podem ser revisados." (422).
- 3a. Correção fechada: 403 com a mensagem de bloqueio.

**Regras de negócio:** decisão 1 do docs/25 (anulada não pontua) — aplicada no código, ⚠ ainda não registrada formalmente (planejamento, item 8).

**Critérios de aceite:**
- [x] A revisão preserva o registro anterior no histórico.

**Situação:** Implementado — evidência: `CartaoController::revisar`

---

## UC-046-03 — Ver a foto do cartão com segurança

**Ator:** perfis com `correcao.acompanhar` (exceto aplicador)
**Permissão:** `correcao.acompanhar`
**Escopo:** cartão de prova no escopo, inclusive histórico
**Pré-condições:** cartão com foto.
**Pós-condições:** nenhuma.
**Tela/rota:** web `cartoes.imagem-segura` (`original`, `miniatura`, `endireitada`), `cartoes.imagem`; API `GET v1/cartoes/{id}/imagens/{tipo}`, `GET v1/cartoes/{id}/imagem`

**Fluxo principal:**
1. A tela pede a imagem por requisição autenticada (fetch + Blob, FN-018).
2. A API confere usuário ativo, perfil, permissão, contexto e escopo da prova e entrega o arquivo com `private, no-store` e `nosniff`.

**Fluxos alternativos e de erro:**
- 2a. Sem acesso: 404. Arquivo ausente: "Imagem não encontrada."
- 2b. Endereço antigo assinado (`cartoes/{id}/arquivo/{tipo}`): 404.

**Critérios de aceite:**
- [x] Nenhuma foto é servida por URL pública.

**Situação:** Implementado — evidência: `CartaoController::arquivo`, `ImagensCartao`; testes `ImagensCartaoTest`, `RecursosCorrecaoWebTest`

---

## UC-046-04 — Consultar o cartão inteiro durante a revisão

**Ator:** perfis com `correcao.acompanhar`
**Permissão:** `correcao.acompanhar`
**Escopo:** prova no escopo
**Pré-condições:** revisão aberta.
**Pós-condições:** nenhuma.
**Tela/rota:** web `correcao.revisao` (parciais `correcao/_documento`, `_leitura-questoes`)

**Fluxo principal:**
1. No desktop, o cartão digitalizado aparece em painel lateral (55/45) com PDF.js sem controles; no celular, em modal acessível.
2. A imagem lateral desestimula cópia (docs/155).

**Critérios de aceite:**
- [x] O documento exibido é o cartão-resposta digitalizado, não o gabarito (docs/157).
- [ ] Conferência registrada por texto em desktop e celular, temas claro e escuro, na homologação.

**Situação:** Implementado na homologação — evidência: PR #184, #185, #187

---

## UC-046-05 — Ajustar os limiares de ambiguidade (R3)

**Ator:** equipe técnica, com decisão do responsável
**Permissão:** não se aplica (configuração do motor)
**Escopo:** rede
**Pré-condições:** medição das decisões reais dos professores em relação à leitura.
**Pós-condições:** limiares revisados e documentados.
**Tela/rota:** sem tela; `omr/ler_cartao.py`

**Fluxo principal:**
1. Levantar, em `ambiguidade_logs`, quantas ambiguidades foram confirmadas pela leitura original.
2. Propor novos limiares e validar na homologação.

**Critérios de aceite:**
- [ ] Redução de revisões desnecessárias medida, sem aumento de leituras erradas.

**Situação:** Não implementado — subfase 1.2 (docs/25 R3)
