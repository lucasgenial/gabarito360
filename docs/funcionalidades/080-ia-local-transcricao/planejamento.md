# 080 — Escriba: IA local de transcrição — Planejamento

**Implementação:** Candidata
**Etapa:** Candidata
**Conforme princípios:** versão 14
**Posição no plano mestre:** fora das ondas (candidata; aguarda priorização do responsável)
**Natureza:** nova
**Origem:** surgiu na conversa de planejamento do Leitor360 (FN-079) em 2026-11-11
**Atualizado em:** 2026-11-11, por lucasgenial

Planejamento preliminar. Não há plano técnico aprovado. Nada é executado antes da priorização e da aprovação destes documentos.

Neste documento, "(proposta)" junto de um número significa: proposta do planejamento, a confirmar pelo responsável na 0.1.

## 1. Objetivo

**Nome:** a IA se chama **Escriba** (decisão do responsável em 11/11/2026). O nome diz o que ela faz: copia fielmente a letra do aluno, sem opinar. Nas telas, use "Escriba". Em texto técnico, "Escriba (IA local de transcrição)" na primeira menção.

**Regra de interface (decisão do responsável em 11/11/2026):** nenhuma tela diz que o aparelho baixa "uma IA" ou "um modelo". O texto fala dos **recursos do Escriba**. Exemplos: "Baixando os recursos do Escriba… 62%", "Cancelar o download dos recursos do Escriba?", "Este aparelho suporta o Escriba", "Este aparelho não tem suporte ao Escriba". "Modelo", "IA" e "pesos" ficam só nos documentos técnicos. A tela não informa o que o aparelho tem, o que falta nem o motivo: só se ele suporta ou não o Escriba.

O Leitor360 precisa ler respostas manuscritas de provas abertas e mistas. Exemplo: produção textual. Esta funcionalidade entrega um modelo de visão e linguagem **local**, que roda no aparelho do usuário (smartphone e computador) de forma transparente. O aparelho sem suporte usa o mesmo modelo no servidor do responsável, pelo modo servidor (§8.3).

O modelo transcreve a letra do aluno em três tipos de saída:

| Tipo de conteúdo | Saída |
|---|---|
| Texto | Texto simples |
| Fórmula | Notação matemática (formato pendente; LaTeX é a opção recomendada) |
| Desenho | Recorte da imagem preservado; descrição em texto só quando necessária |

Nesta fase o modelo **só lê. Ele não corrige.** O modelo-base é uma versão do Qwen ajustada pelo responsável. O Leitor360 ([FN-079](../079-leitor360/planejamento.md)) consome este modelo como o método de leitura `transcricao-manuscrita`.

### 1.1 Princípio de tamanho

Use sempre o menor modelo que atinge o melhor desempenho exigido. Três fatos orientam este princípio:

1. A especialização (ajuste fino) não reduz o tamanho do modelo. Um modelo ajustado tem o mesmo número de parâmetros do modelo-base.
2. O tamanho final vem de duas escolhas: o modelo-base e a compressão. A compressão inclui quantização, poda e destilação.
3. A especialização permite que um modelo-base menor atinja a qualidade exigida nesta tarefa estreita. Este é o ganho de tamanho que ela traz.

O responsável conduz o trabalho do modelo: seleção, dados, ajuste fino e compressão.

## 2. Escopo preliminar

- **Inclui:**
  - Seleção da variante pequena de visão do Qwen (decisão do responsável), com a licença conferida.
  - Conjunto de dados de manuscritos de alunos para treino e referência, conforme o §6.3.
  - Ajuste fino da variante escolhida para letra de aluno, fórmulas e desenhos.
  - Compressão do modelo ajustado (quantização, poda ou destilação).
  - Formatos de execução por alvo:
    - Navegador no celular e no computador. Opções a avaliar: WebGPU, com WebAssembly (WASM) como alternativa. Runtimes candidatos a avaliar: ONNX Runtime Web, WebLLM/MLC, transformers.js, llama.cpp compilado para WASM. Nenhuma versão é afirmada aqui.
    - App nativo futuro ([FN-010](../010-app-mobile/planejamento.md)). Runtimes candidatos a avaliar: llama.cpp, MLC LLM, ONNX Runtime Mobile.
  - Empacotamento, versionamento e distribuição do modelo: download sob demanda, cache no aparelho, verificação de integridade e troca de versão, nas regras da [FN-079 §6.4](../079-leitor360/planejamento.md#64-núcleo-de-leitura-decisão-d-4).
  - Conteúdo do trecho de transcrição (§6.1). A interface do método pertence à FN-079.
  - Medição de qualidade e de custo:
    - Taxa de erro de caracteres (CER) do texto.
    - Taxa de erro de fórmulas.
    - Medição feita num conjunto de referência de manuscritos.
    - Latência e memória por classe de aparelho.
  - Requisitos mínimos do modelo (memória, WebGPU ou WASM, tempo) como entrada do perfil de capacidade da [FN-079 §6.3](../079-leitor360/planejamento.md#63-perfil-de-capacidade-do-aparelho), que é o dono da detecção.
- **Não inclui:**
  - Correção automática de respostas abertas. É uma ideia futura, sem FN. Registro em §8.
  - Leitor visual, fluxo de captura, despacho pelo mapa de respostas, detecção de capacidade e conferência humana → [FN-079](../079-leitor360/planejamento.md).
  - Comportamento do aparelho sem capacidade → decisão única D-2 da [FN-079](../079-leitor360/planejamento.md#8-riscos-e-decisões-pendentes-do-responsável).
  - Criação de questões abertas e do mapa de respostas → [FN-043](../043-editor-provas-questoes/planejamento.md).
  - App nativo em si → [FN-010](../010-app-mobile/planejamento.md). Esta FN entrega só o formato do modelo para esse alvo.
  - Termos, inventário e retenção gerais da LGPD → [FN-061](../061-lgpd-termos-privacidade/planejamento.md) e [FN-062](../062-lgpd-inventario-retencao/planejamento.md). Esta FN aplica essas regras aos dados de treino (§6.3).

## 3. Fontes de verdade

- Estudo da arquitetura de execução (modelo-base, runtime no navegador, no servidor e no app, paridade): [FN-079, estudo](../079-leitor360/estudo-arquitetura-execucao.md), decisões E-4 a E-6.

- Decisões do responsável de 2026-11-11, registradas na conversa de planejamento do Leitor360.
- Leitor360: [FN-079](../079-leitor360/planejamento.md), método `transcricao-manuscrita` e caso UC-079-11.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md). Hoje não existe permissão de IA nem de crédito (`CatalogoPermissoes.php`). A permissão segue a decisão D-9 da FN-079.
- Privacidade: [11 Segurança e privacidade](../../arquitetura/11-seguranca-e-privacidade.md).
- Sem mockup. Esta FN não tem tela própria. As telas de conferência da transcrição pertencem à FN-079.

## 4. Dependências

| Depende de | Por quê | Situação |
|---|---|---|
| FN-079 Leitor360 | Define a interface do método `transcricao-manuscrita`, o perfil de capacidade e as regras de distribuição | Candidata |
| FN-043 editor-provas-questoes | Produz o mapa de respostas com as regiões abertas e o tipo esperado | Candidata |
| FN-010 app-mobile | Alvo nativo do modelo | Adiada |
| [FN-081](../081-creditos-correcao/planejamento.md) creditos-correcao | Bloqueia o Escriba no servidor (§8.3); o sistema de créditos ainda não existe | Candidata |
| [FN-057 notificações](../057-notificacoes/planejamento.md) | Aviso ao usuário quando a transcrição no servidor termina; até lá, aviso no app | Pendente |
| FN-061 e FN-062 LGPD | Base de consentimento, inventário e retenção para os dados de treino | Parcial |

**Funcionalidades que dependem desta:** FN-079. Esta FN fornece o modelo e bloqueia só as subfases 6.x da FN-079 (UC-079-11 e UC-079-16).

## 5. Fases e subfases

Cada subfase registra na coluna Evidência o relatório versionado (texto, sem print) e o PR. As amostras de manuscritos nunca entram no repositório nem na CI hospedada (§6.3).

| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 | Plano completo | `planejamento.md` e `casos-de-uso.md` aprovados pelo responsável; decisões de §8 tomadas ou com prazo; metas numéricas do §5.1 fixadas antes da 1.2 | Pendente | |
| 0.2 | Conjunto de referência de manuscritos | Termo aprovado pelo encarregado; RIPD do treino registrado; o conjunto está no inventário (FN-062); base legal e consentimento registrados (§6.3); amostras pseudonimizadas; transcrição esperada anotada por duas pessoas por região; separação entre treino e referência; tamanho mínimo definido (proposta: pelo menos 200 regiões) | Pendente | |
| 1.1 | Seleção da variante do Qwen | Licença conferida e compatível com uso comercial; variantes comparadas no conjunto de referência | Pendente | |
| 1.2 | Linha de base de qualidade | CER e erro de fórmulas da variante escolhida medidos com as definições do §5.1 e registrados | Pendente | |
| 2.1 | Conjunto de treino | Dados de treino separados do conjunto de referência; origem e consentimento rastreáveis (§6.3) | Pendente | |
| 2.2 | Ajuste fino | Modelo ajustado supera a linha de base nas metas de §5.1 | Pendente | |
| 3.1 | Compressão | Modelo comprimido mantém a qualidade dentro da perda máxima aceita (meta do §5.1) | Pendente | |
| 3.2 | Orçamento por classe de aparelho | Tamanho de download, memória e tempo por página medidos em cada classe; metas atingidas | Pendente | |
| 3.3 | Formato para navegador | Modelo roda no runtime escolhido, com WebGPU e alternativa definida; diferença de CER entre WebGPU e WASM registrada | Pendente | |
| 4.1 | Empacotamento e distribuição | Download sob demanda, cache, verificação de sha256 por arquivo, formato sem código executável e troca de versão funcionam (FN-079 §6.4) | Pendente | |
| 4.2 | Integração ao Leitor360 | Interface da FN-079 §6.1 atendida; UC-079-11 passa com o modelo real; o teste de rede da FN-079 §6.8 passa | Pendente | |
| 4.3 | Formato nativo | Modelo roda no alvo da FN-010 | Pendente | depende da FN-010 |

### 5.1 Critérios de pronto mensuráveis

Todos os números são **metas a definir pelo responsável** na 0.1, antes da 1.2. Este documento não afirma tamanho nem desempenho de nenhum modelo. Os valores são a medir.

| Métrica | Onde medir | Meta |
|---|---|---|
| CER do texto | Conjunto de referência | A definir |
| Taxa de erro de fórmulas | Conjunto de referência | A definir |
| Perda de qualidade após compressão | Conjunto de referência, antes e depois | A definir |
| Tamanho de download | Por formato de execução | A definir |
| Memória máxima em uso | Por classe de aparelho | A definir |
| Tempo por região e por página | Por classe de aparelho | A definir |
| Calibração da confiança | Trechos de baixa confiança contra erros reais | A definir |
| Qualidade por subgrupo de letra | Subgrupos do conjunto de referência (viés) | A definir |

Definições:

1. CER = distância de Levenshtein por caractere ÷ caracteres da referência, depois de normalizar Unicode NFC e espaços. Caixa, acentos e pontuação contam.
2. Erro de fórmula = proporção de fórmulas cujo LaTeX normalizado difere da referência.
3. Calibração = erro de calibração esperado (ECE) em 10 faixas.
4. Subgrupos: série, estilo de letra e caneta ou lápis, definidos no manifesto. Nenhum subgrupo usa atributo sensível (§6.3, regra 6).
5. A inferência de avaliação usa decodificação determinística (greedy, temperatura 0).
6. O relatório compara WebGPU e WASM no mesmo conjunto e registra a diferença de CER entre os dois.

Uma versão do modelo só é publicada quando atinge todas as metas (UC-080-08).

## 6. Contrato técnico (diretrizes)

Este documento registra só o que já se sabe. O plano completo (0.1) detalha o resto.

### 6.1 Conteúdo do trecho

A interface do método (entrada, saída, estados e confiança da região) pertence à [FN-079 §6.1](../079-leitor360/planejamento.md#61-arquitetura-em-camadas-do-motor). Esta seção é dona do conteúdo do trecho.

| Lado | Campos |
|---|---|
| Entrada | Recorte da região de resposta (imagem); `tipo_esperado` (`texto`, `formula`, `desenho` ou `misto`), vindo do mapa; identificação da região no mapa de respostas |
| Saída | Lista de trechos. Cada trecho tem: tipo (`texto`, `formula`, `desenho` ou `ilegivel`), conteúdo (texto, notação da fórmula ou referência ao recorte), posição no recorte e confiança. A saída também traz a versão do modelo |

- A confiança é por trecho. A FN-079 usa a menor confiança dos trechos como confiança da região. O leitor visual destaca os trechos de baixa confiança (UC-080-06).
- Desenho e ilegível são tipos de trecho, não estados da região.
- Um desenho sempre preserva o recorte. A descrição em texto é opcional.

### 6.2 Regras

- A inferência é local. Ela roda no aparelho e, no modo servidor (§8.3), no servidor do Gabarito360, no ambiente do responsável. Nenhum dado vai a terceiros em nenhum dos dois casos. O envio de imagem e resultado ao servidor do Gabarito360 segue a FN-079.
- Registre a versão do modelo com cada transcrição. O servidor guarda essa versão junto do resultado.
- Confira a licença da variante do Qwen antes da subfase 1.1. A licença precisa permitir uso comercial em SaaS e distribuição do modelo ajustado.
- O modelo segue as regras de distribuição da [FN-079 §6.4](../079-leitor360/planejamento.md#64-núcleo-de-leitura-decisão-d-4): origem própria, sem download de Hugging Face em tempo de execução (`allowRemoteModels` falso ou equivalente), manifesto com sha256 por arquivo, SBOM e formato sem código executável (ONNX, GGUF ou safetensors, nunca pickle).
- O modelo não decide nota. A nota continua só no servidor.
- O cache do modelo no aparelho guarda só o modelo. Imagens de alunos seguem a regra de não persistência da FN-079 §6.8.
- A medição de desempenho da API que recebe as transcrições segue o ADR-0052 (1000 usuários simultâneos). A inferência no aparelho é medida por classe de aparelho.

### 6.3 Dados de treino e de referência

O Gabarito360 é **operador** dos dados escolares ([11 Segurança e privacidade](../../arquitetura/11-seguranca-e-privacidade.md), "Papéis"). Treinar um modelo próprio com provas de alunos é finalidade nova. A letra e o conteúdo de uma redação identificam o aluno.

1. É proibido usar em treino ou referência dados de produção: imagens de cartões, recortes, transcrições, `correcoes[]` ou eventos das tentativas. Nenhum código copia esses dados para treino.
2. A coleta é separada do produto. Ela tem um termo próprio, assinado pela instituição (controladora) e pelo responsável legal de cada aluno, com assentimento do aluno. O termo declara a finalidade (treinar e avaliar o modelo), que o modelo será distribuído aos aparelhos dos clientes, o prazo de guarda e o direito de retirada.
3. Antes do uso, cada amostra perde nome, matrícula, QR, cabeçalho e qualquer trecho que identifique pessoas. O conjunto é tratado como dado pessoal pseudonimizado, não como dado anônimo.
4. O treino e a guarda ficam no ambiente do responsável no Brasil. A nuvem exige decisão do responsável, análise de transferência internacional (RL12) e contrato. As amostras nunca vão ao GitHub, à CI nem a artifacts.
5. Antes de publicar, cada versão passa por um teste de memorização: o modelo não pode reproduzir trechos do treino a partir de prefixos. Com retirada de consentimento, a amostra sai do conjunto, e a próxima versão é treinada sem ela.
6. Os subgrupos de viés usam só atributos não sensíveis (série, estilo de letra, tipo de caneta). Um atributo de saúde ou deficiência exige base legal e consentimento específico, decididos pelo responsável.
7. O conjunto entra no inventário (FN-062) e no relatório de impacto (RL13) antes da subfase 0.2.

## 7. Interface

Esta FN não tem tela própria. As telas de preparo do modelo, de progresso do download e de conferência da transcrição pertencem à FN-079 e exigem mockup e revisão de UI/UX lá.

## 8. Riscos e decisões pendentes do responsável

### 8.1 Decisões pendentes

| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Variante do Qwen e licença | Variantes pequenas de visão do Qwen | Comparar as variantes no conjunto de referência (1.1) e escolher a menor que atinge as metas | Pendente |
| Metas de tamanho e latência por classe de aparelho | Valores definidos pelo responsável | Definir as classes de aparelho primeiro, depois as metas | Pendente |
| Aparelho sem capacidade | Decisão única: D-2 da [FN-079](../079-leitor360/planejamento.md#8-riscos-e-decisões-pendentes-do-responsável) | — | Decidida: modo servidor (FN-079 D-2, [§6.12](../079-leitor360/planejamento.md#612-modo-servidor-leitura-no-servidor-com-fila-e-créditos)) |
| Origem e consentimento dos dados de treino | Provas das turmas do responsável com consentimento / escolas parceiras / dados sintéticos / combinação | Começar com amostra pequena e consentida, nas regras do §6.3 | Pendente |
| Formato de saída de fórmulas | LaTeX / MathML / outro | LaTeX, a confirmar pelo responsável | Pendente |
| Formato de saída de desenhos | Só recorte / recorte e descrição sempre / descrição sob demanda | Recorte sempre; descrição só se necessária | Pendente |
| Onde roda o treino | Ambiente do responsável no Brasil / nuvem / outro | Ambiente do responsável no Brasil (§6.3, regra 4). A nuvem exige análise de transferência e contrato. | Pendente |
| Priorização desta candidata | Priorizar / manter fora das ondas | Responsável decide | Pendente |

### 8.2 Riscos

| Risco | Efeito | Mitigação proposta |
|---|---|---|
| Qualidade em letra de aluno | Letra irregular, rasuras e papel ruim aumentam o erro | Conjunto de referência com letra real; confiança por trecho; conferência humana na FN-079 |
| Tamanho para celular | Download grande e memória alta impedem o uso | Princípio de tamanho (§1.1); orçamento por classe de aparelho (3.2) |
| WebGPU ausente | Navegadores e aparelhos sem WebGPU ficam lentos ou sem o método | Alternativa em WASM a avaliar; detecção de capacidade na FN-079; D-2 da FN-079 |
| Viés | Qualidade pior para alguns grupos (estilo de letra, série) | Medir por subgrupo não sensível (§5.1); ampliar dados onde a qualidade cai |
| LGPD de menores e nova finalidade | Dados de treino com letra de crianças e adolescentes; o Gabarito360 vira controlador irregular sem instrução do controlador | §6.3 inteiro; termo próprio; inventário na FN-062; RIPD antes da 0.2 |
| Memorização no modelo | Os pesos vão a todos os aparelhos e podem reproduzir trechos do treino | Teste de memorização antes de publicar (§6.3, regra 5) |
| Cadeia de suprimento do modelo | Um arquivo trocado executa código ou altera o modelo | Regras de distribuição da FN-079 §6.4 |
| Custo de manutenção do modelo | Cada nova variante exige novo ajuste, compressão e medição | Processo repetível de avaliação (UC-080-08); versionamento; responsável define a cadência |
| Licença do modelo-base | Restrição comercial bloqueia o uso no SaaS | Conferir a licença antes de 1.1 |
| Confiança mal calibrada | O leitor visual não destaca trechos errados | Medir a calibração (§5.1) |

### 8.3 Decidido: Escriba no servidor, com fila e créditos

Decisão do responsável em 11/11/2026 (D-2 da FN-079, [§6.12](../079-leitor360/planejamento.md#612-modo-servidor-leitura-no-servidor-com-fila-e-créditos), [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md)). O Escriba no servidor faz parte do plano. Ele é o modo servidor da FN-079, na subfase 6.3 da FN-079. Em ordem, vem depois da versão no aparelho. Quando o aparelho não tem suporte, as imagens das respostas abertas sobem ao servidor. O servidor roda o mesmo Escriba e transcreve. O usuário espera numa fila e gasta créditos de correção.

Diretrizes para o modo servidor (a detalhar na subfase 6.3 da FN-079, com mockup aprovado antes do código):

1. **Só as abertas vão para a fila.** As objetivas continuam sendo lidas no aparelho e conferidas na hora. Numa prova mista, as abertas ficam "Aguardando o Escriba". Numa produção textual, a folha inteira espera.
2. **Espera assíncrona.** O usuário envia e pode sair da tela. O app mostra a posição na fila e um tempo estimado. Quando a transcrição termina, o usuário recebe um aviso e abre o leitor visual para conferir (UC-079-16). A conferência humana continua obrigatória.
3. **Paridade: mesmo modelo, mesma versão.** O servidor usa o mesmo Escriba, na mesma versão publicada para os aparelhos. Para a mesma imagem, o resultado é igual nos dois lados. A origem da transcrição ("aparelho" ou "servidor") e a versão ficam registradas com o resultado.
4. **Texto da tela.** Continua simples, sem motivo nem lista de recursos. A mensagem de capacidade é "Este aparelho não tem suporte ao Escriba". Antes do primeiro envio, a tela mostra os dois avisos exatos do modo servidor (FN-079 §6.12, regra 1):
   - "Os créditos de correção serão consumidos."
   - "A correção não é instantânea. Ela entra numa fila, e você recebe um aviso quando terminar."

   A tela mostra a posição na fila, por exemplo: "Na fila do Escriba: posição 12, cerca de 8 min".
5. **Custo e créditos.** A transcrição no servidor gasta processamento do Gabarito360, e não do aparelho. Ela consome créditos de correção (D-9 da FN-079). A unidade de crédito é uma correção de prova de um aluno (aluno × prova). Várias páginas do mesmo aluno na mesma prova custam um crédito. Sem saldo, o envio é recusado. Se o Escriba no servidor tem dificuldade, o usuário pode reenviar só os alunos com problema, sem cobrar crédito de novo, e/ou mandar a folha para a revisão manual. A revisão manual é o caminho principal. No aparelho, com imagem ruim, o Escriba pede uma nova imagem e/ou a revisão manual. A transcrição no aparelho nunca consome crédito. O sistema de créditos de correção ainda não existe. A dona dele é a [FN-081](../081-creditos-correcao/planejamento.md), candidata. Ela bloqueia o Escriba no servidor.
6. **Dados.** A imagem já vai ao servidor hoje (FN-079). A transcrição no servidor não envia nada a terceiros. O servidor dedicado fica no ambiente do responsável, no Brasil. Um serviço externo exige análise de transferência e entra na lista de terceiros do [11](../../arquitetura/11-seguranca-e-privacidade.md).
7. **Infraestrutura (risco).** ⚠ Inferência, sem medição: a VM atual (Xeon E5-2650 v4, 16 GB, sem GPU) deve transcrever devagar, vários segundos por resposta. Em semana de provas a fila pode ficar longa. A fase precisa medir o tempo por resposta e decidir entre GPU dedicada, limite de folhas por hora ou prioridade por plano.

### 8.4 Ideias futuras (sem FN)

- Correção automática de respostas abertas. Exige decisão do responsável e nova FN candidata.

## 9. Ramos desta entrega

Nenhum.

## 10. Histórico

| Data | Mudança | Por |
|---|---|---|
| 2026-11-11 | Funcionalidade registrada como candidata | lucasgenial |
| 2026-11-11 | A IA recebe o nome Escriba, por decisão do responsável | lucasgenial |
| 2026-11-11 | Regra de interface: as telas falam em "recursos do Escriba", nunca em baixar uma IA ou um modelo | lucasgenial |
| 2026-11-11 | Revisão independente (ui-ux, qualidade-testes, seguranca-privacidade, gerente-projetos) aplicada | lucasgenial |
| 2026-11-11 | Evolução decidida (§8.3): o aparelho sem suporte envia as abertas ao Escriba no servidor, com fila (versão inicial; substituída pela linha seguinte, que o inclui no plano) | lucasgenial |
| 2026-11-11 | Alinhado às decisões D-1 a D-13 da FN-079; Escriba no servidor com fila e créditos entra no plano | lucasgenial |
| 2026-11-11 | Correções do responsável: crédito por correção de prova do aluno, reenvio sem custo, revisão manual como caminho principal | lucasgenial |
