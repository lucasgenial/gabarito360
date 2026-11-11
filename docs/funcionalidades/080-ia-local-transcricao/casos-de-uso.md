# 080 — Escriba: IA local de transcrição — Casos de uso

**Natureza:** nova (candidata; casos de uso preliminares de alto nível)
**Perfis envolvidos:** perfis que leem respostas abertas no Leitor360 (os mesmos de `cartao.enviar` e `correcao.alterar`, a confirmar na FN-079); responsável pelo modelo (papel técnico, fora dos perfis da aplicação). Ver [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md). A permissão de uso da IA segue a decisão D-9 da FN-079.
**Atualizado em:** 2026-11-11

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Nenhum caso está implementado. Os números de tamanho, tempo e qualidade são metas a definir pelo responsável ([planejamento §5.1](planejamento.md)). O Leitor360 ([FN-079](../079-leitor360/casos-de-uso.md)) chama este modelo pelo método `transcricao-manuscrita`.

---

## UC-080-01 — Baixar e preparar o modelo no aparelho

**Ator:** usuário do Leitor360 (o download é transparente)
**Permissão:** a mesma da leitura de respostas abertas na FN-079 (⚠ D-9 da FN-079)
**Escopo:** aparelho do usuário
**Pré-condições:** a prova tem regiões abertas no mapa de respostas; o aparelho passou na detecção de capacidade (UC-079-02).
**Pós-condições:** o modelo está no cache do aparelho, com versão e integridade conferidas.
**Tela/rota:** tela de preparo do Leitor360 (FN-079; sem mockup)

**Fluxo principal:**
1. O Leitor360 verifica se o modelo da versão vigente está no cache.
2. Se não está, o Leitor360 baixa o modelo sob demanda, da origem do Gabarito360, e mostra o progresso.
3. O Leitor360 confere o sha256 de cada arquivo contra o manifesto da versão ([FN-079 §6.4](../079-leitor360/planejamento.md#64-núcleo-de-leitura-decisão-d-4)).
4. O Leitor360 carrega o modelo no runtime escolhido.

**Fluxos alternativos e de erro:**
- 1a. O modelo vigente já está no cache: o Leitor360 carrega o modelo sem download.
- 2a. A conexão cai: o download retoma depois, sem recomeçar do zero (a confirmar no plano).
- 3a. A integridade falha: o Leitor360 apaga o arquivo e baixa de novo.
- 4a. O aparelho não aguenta o modelo: vai para UC-080-09.

**Regras de negócio:** só a prova com região aberta pede o modelo. Provas só objetivas não baixam o modelo. Nenhum arquivo vem de CDN ou do Hugging Face em tempo de execução. O modelo usa formato sem código executável.

**Critérios de aceite:**
- [ ] Prova só com questões objetivas não dispara o download.
- [ ] O segundo uso no mesmo aparelho não baixa o modelo de novo.
- [ ] Um arquivo com sha256 diferente do manifesto nunca é carregado.
- [ ] O log de rede mostra todas as requisições do modelo para a origem do Gabarito360.
- [ ] Tamanho do download dentro da meta por formato (a medir).

**Situação:** Não implementado

---

## UC-080-02 — Transcrever uma resposta manuscrita em texto

**Ator:** Leitor360 (método `transcricao-manuscrita`)
**Permissão:** herdada da sessão do Leitor360
**Escopo:** aparelho do usuário
**Pré-condições:** modelo carregado (UC-080-01); recorte da região aberta e tipo esperado `texto`.
**Pós-condições:** texto transcrito, com confiança por trecho e versão do modelo.
**Tela/rota:** sem tela própria; resultado exibido no leitor visual (UC-079-16)

**Fluxo principal:**
1. O Leitor360 envia o recorte e o tipo esperado ao modelo.
2. O modelo transcreve a letra do aluno em texto.
3. O modelo devolve os trechos, a confiança de cada trecho e a versão do modelo, no formato do [planejamento §6.1](planejamento.md#61-conteúdo-do-trecho).

**Fluxos alternativos e de erro:**
- 2a. A região está em branco: o modelo devolve saída vazia, marcada como em branco.
- 2b. O modelo falha: o Leitor360 marca a região como não lida.
- 2c. A letra não é legível: o modelo devolve um trecho do tipo `ilegivel`.

**Regras de negócio:** o modelo só lê. Ele não avalia a resposta nem atribui nota.

**Critérios de aceite:**
- [ ] Nenhum dado sai do aparelho durante a inferência (teste de rede da [FN-079 §6.8](../079-leitor360/planejamento.md#68-autorização-e-lgpd)).
- [ ] A saída sempre traz a versão do modelo.
- [ ] CER no conjunto de referência dentro da meta (a medir).
- [ ] Tempo por região dentro da meta da classe de aparelho (a medir).

**Situação:** Não implementado

---

## UC-080-03 — Transcrever uma fórmula

**Ator:** Leitor360 (método `transcricao-manuscrita`)
**Permissão:** herdada da sessão do Leitor360
**Escopo:** aparelho do usuário
**Pré-condições:** modelo carregado; recorte com tipo esperado `formula` ou `misto`.
**Pós-condições:** fórmula em notação matemática, com confiança por trecho.
**Tela/rota:** sem tela própria; resultado exibido no leitor visual (UC-079-16)

**Fluxo principal:**
1. O Leitor360 envia o recorte e o tipo esperado.
2. O modelo transcreve a fórmula na notação definida (LaTeX é a opção recomendada; decisão pendente).
3. O modelo devolve a fórmula, a confiança e a versão.

**Fluxos alternativos e de erro:**
- 2a. Região mista com texto e fórmula: o modelo separa os trechos por tipo.
- 2b. A notação gerada é inválida: o trecho sai com confiança baixa (UC-080-06).

**Critérios de aceite:**
- [ ] A notação de saída é válida no formato escolhido.
- [ ] Taxa de erro de fórmulas no conjunto de referência dentro da meta (a medir).

**Situação:** Não implementado

---

## UC-080-04 — Preservar e descrever um desenho

**Ator:** Leitor360 (método `transcricao-manuscrita`)
**Permissão:** herdada da sessão do Leitor360
**Escopo:** aparelho do usuário
**Pré-condições:** modelo carregado; região com desenho (tipo esperado `desenho` ou detectado num `misto`).
**Pós-condições:** recorte do desenho preservado; descrição em texto quando necessária.
**Tela/rota:** sem tela própria; resultado exibido no leitor visual (UC-079-16)

**Fluxo principal:**
1. O modelo identifica o trecho de desenho no recorte.
2. O Leitor360 guarda o recorte do desenho com a posição.
3. Quando a descrição é pedida, o modelo gera uma descrição curta em texto.

**Regras de negócio:** o recorte é a fonte do desenho. A descrição é auxiliar e nunca substitui o recorte. Quando a descrição é pedida está pendente (planejamento §8.1).

**Critérios de aceite:**
- [ ] Todo desenho sai com o recorte preservado.
- [ ] A descrição só aparece quando a regra pede.

**Situação:** Não implementado

---

## UC-080-05 — Transcrever uma produção textual longa

**Ator:** Leitor360 (método `transcricao-manuscrita`)
**Permissão:** herdada da sessão do Leitor360
**Escopo:** aparelho do usuário
**Pré-condições:** modelo carregado; resposta distribuída em várias regiões ou várias páginas no mapa de respostas.
**Pós-condições:** um texto contínuo por resposta, com origem (página e região) de cada trecho.
**Tela/rota:** sem tela própria; resultado exibido no leitor visual (UC-079-16)

**Fluxo principal:**
1. O Leitor360 envia as regiões na ordem do mapa de respostas.
2. O modelo transcreve cada região.
3. O Leitor360 junta os trechos na ordem e guarda a origem de cada um.

**Fluxos alternativos e de erro:**
- 1a. Falta uma página da resposta: o Leitor360 marca a resposta como incompleta e não junta trechos fora de ordem.
- 2a. O aparelho fica sem memória no meio da resposta: o Leitor360 processa uma região por vez e retoma da região seguinte.

**Critérios de aceite:**
- [ ] A ordem dos trechos segue o mapa de respostas.
- [ ] Cada trecho aponta a página e a região de origem.
- [ ] Tempo por página dentro da meta da classe de aparelho (a medir).
- [ ] Memória máxima dentro da meta da classe de aparelho (a medir).

**Situação:** Não implementado

---

## UC-080-06 — Informar baixa confiança por trecho ao leitor visual

**Ator:** Leitor360 (método `transcricao-manuscrita`)
**Permissão:** herdada da sessão do Leitor360
**Escopo:** aparelho do usuário
**Pré-condições:** transcrição feita (UC-080-02 a UC-080-05).
**Pós-condições:** os trechos de baixa confiança estão marcados para conferência humana.
**Tela/rota:** leitor visual (UC-079-14 e UC-079-16)

**Fluxo principal:**
1. O modelo devolve a confiança de cada trecho.
2. O Leitor360 compara a confiança com o limiar definido.
3. O leitor visual destaca os trechos abaixo do limiar, lado a lado com o recorte.

**Regras de negócio:** o limiar é a definir e é calibrado no conjunto de referência.

**Critérios de aceite:**
- [ ] Todo trecho tem confiança.
- [ ] A calibração da confiança atinge a meta no conjunto de referência (a medir).

**Situação:** Não implementado

---

## UC-080-07 — Atualizar a versão do modelo

**Ator:** responsável pelo modelo (publica); Leitor360 (aplica no aparelho)
**Permissão:** a definir (publicação restrita ao responsável)
**Escopo:** sistema
**Pré-condições:** a nova versão passou na avaliação (UC-080-08).
**Pós-condições:** os aparelhos usam a nova versão; as transcrições antigas mantêm a versão com que foram feitas.
**Tela/rota:** a definir

**Fluxo principal:**
1. O responsável publica a nova versão com número e manifesto (sha256 de cada arquivo).
2. No próximo uso, o Leitor360 detecta que o cache tem versão antiga.
3. O Leitor360 baixa a nova versão (UC-080-01) e remove a antiga do cache.

**Fluxos alternativos e de erro:**
- 1a. A versão nova apresenta problema: o responsável volta a versão anterior.
- 2a. Uma leitura está em andamento: a troca espera o fim da folha atual.

**Critérios de aceite:**
- [ ] Toda transcrição guarda a versão do modelo usada.
- [ ] A troca de versão não interrompe uma folha em leitura.
- [ ] Nenhuma versão é publicada sem avaliação aprovada.

**Situação:** Não implementado

---

## UC-080-08 — Avaliar uma versão do modelo no conjunto de referência

**Ator:** responsável pelo modelo
**Permissão:** fora da aplicação (processo técnico)
**Escopo:** sistema
**Pré-condições:** conjunto de referência de manuscritos com transcrição esperada, conforme o [planejamento §6.3](planejamento.md#63-dados-de-treino-e-de-referência) (subfase 0.2).
**Pós-condições:** relatório de avaliação da versão; versão aprovada ou recusada.

**Fluxo principal:**
1. O responsável roda a versão do modelo sobre o conjunto de referência.
2. O processo mede CER, erro de fórmulas, calibração da confiança e qualidade por subgrupo, com as definições do planejamento §5.1 e decodificação determinística.
3. O processo mede tamanho, memória e tempo em cada classe de aparelho, em WebGPU e em WASM.
4. O processo roda o teste de memorização (planejamento §6.3, regra 5).
5. O processo compara as medidas com as metas e com a versão anterior.
6. O responsável aprova ou recusa a versão.

**Regras de negócio:** o conjunto de referência nunca entra no treino. A avaliação roda no ambiente do responsável, nunca na CI hospedada. Ela é repetível e registrada por texto.

**Critérios de aceite:**
- [ ] O relatório tem todas as métricas do planejamento §5.1.
- [ ] Uma versão abaixo de qualquer meta é recusada.
- [ ] Os dados do conjunto de referência estão pseudonimizados (planejamento §6.3, regra 3).
- [ ] Uma versão que reproduz trechos do treino no teste de memorização é recusada.
- [ ] O relatório registra a diferença de CER entre WebGPU e WASM.

**Situação:** Não implementado

---

## UC-080-09 — Atender aparelho sem capacidade

**Ator:** Leitor360
**Permissão:** herdada da sessão do Leitor360
**Escopo:** aparelho do usuário
**Pré-condições:** a detecção de capacidade indica que o aparelho não aguenta o modelo (memória, WebGPU, WASM ou tempo).
**Pós-condições:** pendente da decisão do responsável.
**Tela/rota:** Leitor360 (FN-079, UC-079-23; sem mockup)

⚠ O comportamento está pendente. Opções na D-2 da [FN-079](../079-leitor360/planejamento.md#8-riscos-e-decisões-pendentes-do-responsável) (decisão única). A detecção pertence à FN-079 §6.3; esta FN fornece os requisitos mínimos do modelo.

**Fluxo principal (provisório):**
1. O Leitor360 detecta a falta de capacidade antes do download.
2. O Leitor360 informa o usuário e aplica a opção decidida pelo responsável.

**Critérios de aceite:**
- [ ] O aparelho sem capacidade faz zero requisições aos arquivos do modelo (log de rede).
- [ ] A mensagem diz só "Este aparelho não tem suporte ao Escriba" e a ação possível. Ela não lista recursos nem motivos (decisão do responsável em 11/11/2026).
- [ ] Nenhum dado vai a terceiros, em qualquer opção.

**Situação:** Não implementado
