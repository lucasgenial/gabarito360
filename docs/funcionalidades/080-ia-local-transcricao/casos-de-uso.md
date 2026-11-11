# 080 — Escriba: IA local de transcrição — Casos de uso

**Natureza:** nova (candidata; casos de uso preliminares de alto nível)
**Perfis envolvidos:** perfis que leem respostas abertas no Leitor360 (os mesmos de `cartao.enviar` e `correcao.alterar`, a confirmar na FN-079); responsável pelo modelo (papel técnico, fora dos perfis da aplicação). Ver [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md). A permissão de uso do Escriba é a chave nova `leitura.transcrever` (D-9 da FN-079). No aparelho não há crédito. No servidor, a transcrição consome créditos de correção.
**Versão:** 2.1 (11/11/2026) — alinhado às decisões D-1 a D-13 da FN-079 e ao modo servidor; crédito por aluno × prova e revisão manual.
**Atualizado em:** 2026-11-11

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Nenhum caso está implementado. Os números de tamanho, tempo e qualidade são metas a definir pelo responsável ([planejamento §5.1](planejamento.md)). O Leitor360 ([FN-079](../079-leitor360/casos-de-uso.md)) chama este modelo pelo método `transcricao-manuscrita`.

---

## UC-080-01 — Baixar e preparar o modelo no aparelho

**Ator:** usuário do Leitor360 (o download é transparente)
**Permissão:** `leitura.transcrever` (D-9 da FN-079), sem crédito no aparelho
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
- 4a. O aparelho não aguenta o modelo: vai para UC-080-09 e, com o aviso confirmado, para UC-080-10.

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

4. Se a imagem está ruim ou a confiança é baixa, o Escriba pede uma nova imagem e/ou a revisão manual do usuário.

**Regras de negócio:** o limiar é a definir e é calibrado no conjunto de referência. Quem confere a leitura é sempre o usuário. Não há decisão automática.

**Critérios de aceite:**
- [ ] Com imagem ruim, o Escriba pede nova imagem e/ou a revisão manual.
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
**Permissão:** `leitura.transcrever`
**Escopo:** aparelho do usuário
**Pré-condições:** a detecção de capacidade indica que o aparelho não aguenta o Escriba (memória, WebGPU, WASM ou tempo).
**Pós-condições:** o usuário sabe que o aparelho não tem suporte e recebe a oferta do modo servidor.
**Tela/rota:** Leitor360 (FN-079, UC-079-23 e UC-079-26; mockup `mockups/leitor360.html` aprovado em 11/11/2026)

Decisão D-2 da [FN-079](../079-leitor360/planejamento.md#612-modo-servidor-leitura-no-servidor-com-fila-e-créditos): o aparelho sem capacidade continua corrigindo pelo app, e o servidor transcreve as regiões abertas. A detecção pertence à FN-079 §6.3; esta FN fornece os requisitos mínimos do Escriba.

**Fluxo principal:**
1. O Leitor360 detecta a falta de capacidade antes do download.
2. O Leitor360 mostra a mensagem simples "Este aparelho não tem suporte ao Escriba".
3. O Leitor360 oferece o modo servidor (FN-079 UC-079-26).
4. O usuário aceita e o fluxo segue no UC-080-10.

**Fluxos alternativos e de erro:**
- 4a. O usuário não aceita: as objetivas continuam no aparelho e as regiões abertas ficam sem transcrição. A folha pode ser salva com pendências (D-11 da FN-079).

**Critérios de aceite:**
- [ ] O aparelho sem capacidade faz zero requisições aos arquivos do Escriba (log de rede).
- [ ] A mensagem diz só "Este aparelho não tem suporte ao Escriba" e a oferta do modo servidor. Ela não lista recursos nem motivos (decisão do responsável em 11/11/2026).
- [ ] Nenhum dado vai a terceiros.

**Situação:** Não implementado

---

## UC-080-10 — Transcrever no servidor, com fila

**Ator:** usuário do Leitor360; servidor do Gabarito360 (executa o Escriba)
**Permissão:** `leitura.transcrever`; o servidor revalida permissão, escopo e vínculo a cada envio
**Escopo:** servidor do Gabarito360, no ambiente do responsável, no Brasil
**Pré-condições:** UC-080-09 concluído; o usuário aceitou o modo servidor; existe saldo de créditos de correção (sistema de créditos sem FN, bloqueia este caso); a prova tem regiões abertas. A unidade de crédito é uma correção de prova de um aluno (aluno × prova).
**Pós-condições:** a transcrição está registrada com origem "servidor" e a versão do Escriba; o usuário foi avisado; a conferência humana está pendente.
**Tela/rota:** Leitor360 (FN-079 §6.12 e UC-079-26); aviso pela FN-057, ou no app até ela existir

**Fluxo principal:**
1. O Leitor360 mostra um modal com os dois avisos: "Os créditos de correção serão consumidos." e "A correção não é instantânea. Ela entra numa fila, e você recebe um aviso quando terminar."
2. O usuário confirma. O aviso reaparece a cada sessão do Leitor360.
3. O Leitor360 envia a imagem de cada folha, foto por foto, com o marcador quando o QR foi lido.
4. O servidor revalida permissão e escopo. O QR prevalece sobre o marcador.
5. O servidor põe só as regiões abertas na fila. As objetivas seguem no aparelho, se o núcleo roda.
6. O app mostra a posição na fila e o tempo estimado. O usuário pode sair da tela.
7. O servidor roda o mesmo Escriba, na mesma versão publicada para os aparelhos, e registra a origem "servidor" e a versão.
8. O servidor avisa o usuário ao terminar.
9. O usuário confere a transcrição no leitor visual (UC-079-16). A conferência humana é obrigatória.

**Fluxos alternativos e de erro:**
- 1a. O usuário cancela o modal: nada é enviado e nenhum crédito é consumido.
- 3a. A barreira de qualidade recusa a imagem: o servidor não consome crédito e o usuário verifica a prova manualmente (revisão manual).
- 4a. O QR diverge do marcador: o QR prevalece e a folha vai para revisão.
- 4b. A permissão ou o escopo não confere: o servidor recusa o envio.
- 7a. O Escriba tem dificuldade com um aluno: o usuário pode reenviar só os alunos com problema, sem cobrar crédito de novo, e/ou mandar a folha para a revisão manual. A revisão manual é o caminho principal.
- Sem saldo de créditos: o servidor recusa o envio (confirmado) e o app informa o usuário.

**Regras de negócio:** o servidor nunca altera a nota nem decide a resposta. Um crédito vale uma correção de prova de um aluno (aluno × prova); várias páginas do mesmo aluno na mesma prova custam um crédito. O servidor não relê por conta própria. Quem confere a leitura é sempre o usuário. ⚠ Inferência, não decisão: as leituras de quem não tem `correcao.alterar` ficam como pendência para o professor conferir. A proteção contra resultado adulterado é a conferência humana, a imagem original guardada, a nota só no servidor e a auditoria. O processamento no aparelho nunca consome. Nenhum dado vai a terceiros.

**Critérios de aceite:**
- [ ] O modal mostra exatamente os dois avisos, sem motivo nem lista de recursos, antes do primeiro envio.
- [ ] Para a mesma imagem, o servidor e o aparelho produzem a mesma transcrição, com a mesma versão do Escriba (conjunto de referência da FN-079).
- [ ] Só as regiões abertas entram na fila. O consumo é de um crédito por aluno × prova.
- [ ] Várias páginas do mesmo aluno na mesma prova consomem um crédito.
- [ ] Sem saldo, o envio é recusado.
- [ ] O reenvio dos alunos com problema não cobra crédito de novo.
- [ ] Toda transcrição guarda a origem ("servidor") e a versão do Escriba.
- [ ] Uma imagem recusada pela barreira de qualidade não consome crédito e vai para a verificação manual do usuário.
- [ ] O usuário vê a posição na fila e pode sair da tela sem perder o envio.
- [ ] O usuário recebe um aviso ao terminar.
- [ ] A transcrição só vale depois da conferência humana.
- [ ] O log de rede do servidor não mostra requisição a terceiros durante a inferência.

**Situação:** Não implementado

---

## Histórico de versões

| Versão | Data | Mudança | Por |
|---|---|---|---|
| 1 | 2026-11-11 | Casos de uso preliminares UC-080-01 a UC-080-09 | lucasgenial |
| 2 | 2026-11-11 | Alinhado às decisões D-1 a D-13 da FN-079; UC-080-09 decidido; UC-080-10 (Escriba no servidor, com fila) | lucasgenial |
| 2.1 | 2026-11-11 | Correções do responsável: crédito por aluno × prova; reenvio sem custo e revisão manual; pedido de nova imagem no aparelho | lucasgenial |
