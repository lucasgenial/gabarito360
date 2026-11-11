# 079 — Leitor360: motor de leitura de provas — Casos de uso

**Natureza:** evolução de existente
**Perfis envolvidos:** Administrador da Rede, Diretor Escolar, Coordenador, Professor e Professor autônomo (leem, conferem e corrigem); Aplicador (captura no modo do dia; vê só as marcações da folha atual, conforme a D-6); Diretor de Núcleo (só acompanha, pela FN-045); equipe técnica (qualidade do motor). Ver [permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md).
**Versão:** 2.1 (11/11/2026) — decisões D-1 a D-13 tomadas; modo servidor com fila e créditos; sem releitura pelo servidor.
**Atualizado em:** 2026-11-11

Cada caso de uso é verificável. Os critérios viram testes ou conferências registradas por texto. Este documento descreve comportamento **proposto**. Rotas, tabelas e campos novos aparecem como "proposta". O que já existe aparece com o nome do código.

Documentos donos (este documento cita, não repete):

- Decisões D-1 a D-13, todas tomadas: [planejamento §8](planejamento.md#8-riscos-e-decisões-pendentes-do-responsável). Este documento cita cada uma pelo código.
- Modo servidor: [planejamento §6.12](planejamento.md#612-modo-servidor-leitura-no-servidor-com-fila-e-créditos) e [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md).
- Telas e modais a mockar: lista única no [planejamento §7](planejamento.md#7-interface). Nenhuma tela é aprovada sem mockup e revisão do agente `ui-ux`.
- Contrato da API, erros e testes do contrato: [planejamento §6.6](planejamento.md#66-api-proposta).
- Limiares e correspondência de estados: [planejamento §6.1](planejamento.md#61-arquitetura-em-camadas-do-motor).
- Regras de armazenamento no navegador: [planejamento §6.8](planejamento.md#68-autorização-e-lgpd).
- Conjunto de referência: [planejamento §6.9](planejamento.md#69-conjunto-de-referência).

App nativo ([FN-010](../010-app-mobile/planejamento.md), futuro): cada tela tem o equivalente nativo, com a câmera e o seletor de arquivos do sistema e o núcleo no pacote do app. Os casos abaixo não repetem essa nota.

Termos usados neste documento:

| Termo | Significado |
|---|---|
| Folha | Na interface do Leitor360, qualquer página lida, vinda de qualquer entrada. |
| Cartão-resposta | Só o tipo de documento (o cartão impresso à parte). |
| Gabarito | Só as respostas oficiais. O leitor nunca mostra o gabarito. O botão **Visualizar gabarito** da revisão atual passa a **Ver cartão inteiro**. |
| Sessão de leitura | O período entre abrir o Leitor360 para uma prova e escolher **Encerrar**. |
| Pendência | Questão em marcação dupla, marcação fraca ou não lida. Entra no contador e em **Próxima pendência**. |
| Aviso | Questão em branco seguro. Tem rótulo e ícone na lista, mas não entra no contador nem vai para a revisão. |
| Mapa de respostas | O documento versionado que diz, para cada página de uma prova, onde está cada região de resposta, qual é o tipo e qual método de leitura a lê. A FN-043 produz o mapa. Até lá, o Leitor360 deriva o mapa do cartão atual. |
| Método de leitura | Um módulo que lê um tipo de região de resposta. Hoje: `objetiva-bolhas`. Proposto: `transcricao-manuscrita` (FN-080). |
| Núcleo de leitura | O código de visão computacional que o Leitor360 executa no aparelho (D-4) e, com a mesma versão, no servidor. |
| Modo servidor | O caminho em que o servidor processa a folha, com fila e consumo de créditos de correção, quando o aparelho não processa (UC-079-26). |
| Marcador | O segmento de metadados que o aparelho grava na imagem com prova, aluno, página, versão do mapa e `envio_id`. Só encaminha a folha. |
| Folha com pendência | Folha salva com questões ainda por conferir (D-11). Ela trava o fechamento da correção (UC-079-27). |
| Leitor visual | A tela de conferência e correção da leitura, no estilo de um aplicativo de digitalização. |
| Conjunto de referência | O conjunto de imagens e PDFs com o resultado esperado. Cada folha tem a verdade anotada por pessoas e o instantâneo do servidor. |
| Paridade | Igualdade com o instantâneo do servidor. Portão de 100%. |
| Acerto | Igualdade com a verdade anotada. Métrica de relatório, sem portão de 100%. |
| Versão do motor | O campo `motor_versao` (Leitor360 e limiares). O núcleo e as bibliotecas ficam no campo separado `nucleo`. |

---

## Grupo A — Preparação

## UC-079-01 — Abrir o Leitor360 para uma prova

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede; Aplicador no modo do dia
**Permissão:** `cartao.enviar`
**Escopo:** prova no escopo do vínculo ativo; aplicador só em provas com data de aplicação igual a hoje
**Pré-condições:** prova `publicada`, `em_correcao` ou `corrigida`; correção aberta, ou ator com `correcao.reabrir`; gabarito publicado.
**Pós-condições:** sessão de leitura aberta no aparelho, com a prova, o mapa de respostas e a versão do mapa carregados.
**Tela/rota:** telas 1 e 14 do planejamento §7. Entrada pela tela atual de acompanhamento (web `correcao.show`, rota `/provas/{id}/acompanhar`). Rota web proposta: `GET /provas/{id}/leitor`. API proposta: `GET v1/provas/{id}/mapa-respostas`.

**Fluxo principal:**
1. O usuário abre a prova em Corrigir.
2. O usuário toca em **Ler folhas**.
3. O sistema confere a permissão, o escopo e a situação da correção.
4. O sistema carrega o mapa de respostas da prova.
5. O sistema executa o UC-079-02 (capacidade do aparelho).
6. O sistema mostra as entradas disponíveis neste aparelho: **Câmera**, **Webcam**, **Fotos**, **PDF de uma folha**, **Lote de PDFs**.
7. O usuário escolhe uma entrada. O fluxo segue no grupo B.

**Fluxos alternativos e de erro:**
- 1a. Aplicador: o sistema abre direto o modo do dia. Ele lista só as provas com data de aplicação igual a hoje. A tela não mostra nota, gabarito nem relatório.
- 1b. Aplicador sem prova no dia: estado vazio "Nenhuma prova para aplicar hoje."
- 3a. Correção fechada sem `correcao.reabrir`: o sistema mostra a mensagem atual "A correção desta prova foi fechada por … em …. Para enviar, excluir ou alterar cartões, peça à coordenação para reabrir a correção." O botão **Ler folhas** fica desabilitado.
- 3b. Prova não publicada: "A prova precisa estar publicada para receber cartões."
- 3c. Gabarito não publicado: "Publique o gabarito antes de ler as folhas." (texto proposto).
- 4a. Prova sem mapa próprio (todas as provas atuais): o sistema deriva o mapa do cartão atual (N, K, `layout.grade()`). O usuário não vê diferença.
- 4b. Falha ao carregar o mapa: "Não foi possível carregar a prova. Tente de novo." e botão **Tentar de novo**.
- 4c. O mapa muda de versão durante a sessão: o próximo envio recebe `layout_divergente`. O leitor recarrega o mapa e relê as folhas não enviadas.
- 6a. Entrada sem suporte no aparelho: o botão aparece desabilitado com o motivo em uma linha. Exemplo: "Este aparelho não tem câmera disponível."

**Variações por aparelho:** as entradas são escolhidas pela capacidade detectada (câmera traseira via `facingMode`, webcam via `enumerateDevices`), não pela largura. Um tablet com câmera traseira recebe **Câmera**. No celular, a lista é vertical e **Câmera** vem primeiro. No computador, **Webcam** e **Lote de PDFs** vêm primeiro.

**Regras de negócio:** correção fechada e aplicador só no dia ([08 Regras de negócio](../../arquitetura/08-regras-de-negocio.md), RN-COR-13; [04](../../arquitetura/04-identidade-acesso-e-permissoes.md)). O mapa de respostas é a única fonte de posição e tipo das questões. O leitor não decide o tipo do documento sozinho. Até a 4.1, **Ler folhas** existe só na homologação. Na 4.1, ele substitui **Fotografar cartões** e **Enviar fotos da galeria** (planejamento §7). A orientação de captura fica na ajuda do título do card ([ADR-0028](../../decisoes/0028-ajuda-contextual-no-titulo-de-cards.md)).

**Critérios de aceite:**
- [ ] Um usuário sem `cartao.enviar` não vê **Ler folhas** e recebe 403 na rota proposta.
- [ ] O aplicador vê só provas com data de aplicação igual a hoje.
- [ ] A resposta do mapa ao aplicador não contém gabarito, pesos nem alternativas corretas.
- [ ] Uma prova atual (sem FN-043) abre com o mapa derivado de N, K e `layout.grade()`, com as mesmas posições do `omr/layout.py`.
- [ ] Com a correção fechada e sem `correcao.reabrir`, **Ler folhas** fica desabilitado e a mensagem de bloqueio aparece.
- [ ] Um tablet com câmera traseira oferece **Câmera**.
- [ ] Conferência registrada por texto em celular (360 px), tablet (768 px) e computador (1280 px), temas claro e escuro.

**Situação:** Parcial — reaproveita a entrada atual de correção e o modo só-câmera do aplicador (`CorrecaoController::show`, view `correcao.camera`). Mapa de respostas, rota do leitor e tela de escolha: não implementados.

---

## UC-079-02 — Verificar a capacidade do aparelho e preparar os motores

**Ator:** sistema, na sessão do usuário do UC-079-01
**Permissão:** `cartao.enviar`
**Escopo:** aparelho do usuário
**Pré-condições:** UC-079-01 em andamento; página servida por HTTPS.
**Pós-condições:** perfil do aparelho registrado na sessão; núcleo de leitura pronto e em cache, ou motivo da indisponibilidade registrado.
**Tela/rota:** tela 2 do planejamento §7. Arquivos do núcleo servidos pela origem do Gabarito360, com manifesto versionado (planejamento §6.4).

**Fluxo principal:**
1. O sistema detecta as capacidades do aparelho (planejamento §6.3).
2. O sistema compara as capacidades com os métodos que o mapa de respostas exige.
3. O sistema confere se o núcleo de leitura da versão vigente já está em cache. Se o mapa tem regiões abertas, o sistema prepara também o modelo (UC-080-01).
4. Sem cache, o sistema baixa o núcleo e mostra o progresso: "Preparando o leitor… 45%".
5. Na primeira vez, o sistema mostra o aviso: "Na primeira vez, o leitor baixa os arquivos de leitura. Use Wi-Fi se puder."
6. O sistema confere o sha256 de cada arquivo, inicia o núcleo e confirma a versão do motor.
7. Enquanto o núcleo carrega, as entradas ficam liberadas. A folha capturada entra na fila com o estado **Aguardando o leitor**. A barra "Preparando o leitor… 45%" fica visível no topo, com `role="status"`. A leitura começa quando o núcleo fica pronto.

**Fluxos alternativos e de erro:**
- 1a. Página sem HTTPS: câmera e webcam ficam indisponíveis. O sistema mostra "A câmera só funciona em conexão segura." Fotos e PDF continuam disponíveis.
- 2a. O mapa exige `transcricao-manuscrita` e o aparelho não tem capacidade para a IA local: o sistema mostra a mensagem simples do UC-079-23 e oferece o modo servidor para as regiões abertas (UC-079-26). As objetivas seguem no aparelho.
- 2b. O aparelho não executa o núcleo de leitura (sem WebAssembly ou memória insuficiente): o sistema mostra a mensagem simples do UC-079-23 e oferece o modo servidor (UC-079-26).
- 4a. Falha no download: "Não foi possível preparar o leitor. Verifique a conexão e tente de novo." e botão **Tentar de novo**.
- 4b. Cache cheio ou bloqueado (navegação privada): o sistema baixa o núcleo só para a sessão e avisa "O leitor vai baixar os arquivos de novo na próxima vez."
- 4c. O usuário toca em **Cancelar** durante o download: o download para, e o cache parcial é apagado.
- 6a. Versão em cache diferente da vigente: o sistema descarta a versão antiga e baixa a nova. A leitura nunca usa versão não vigente.
- 6b. Um arquivo tem sha256 diferente do manifesto: o Worker apaga o arquivo, não executa e baixa de novo.

**Regras de negócio:** o núcleo roda por Pyodide, com paridade total entre aparelho e servidor (D-4, mesmo código e mesma versão). OpenCV e NumPy têm as mesmas versões nos dois lados (D-5; ⚠ falta confirmar o que o Pyodide empacota). Nenhum arquivo vem de CDN, PyPI ou Hugging Face. A versão do motor acompanha cada resultado enviado (UC-079-19). Só uma versão aprovada no UC-079-24 pode ser vigente. O cache guarda só os arquivos do manifesto (planejamento §6.8).

**Critérios de aceite:**
- [ ] Na segunda abertura no mesmo aparelho, o leitor fica pronto sem novo download do núcleo.
- [ ] O progresso aparece em porcentagem. Nenhuma tarefa longa acima de 50 ms ocorre na thread principal durante o download (`PerformanceObserver`).
- [ ] Um aparelho com versão antiga em cache baixa a vigente antes de ler.
- [ ] Um arquivo com sha256 diferente nunca é executado.
- [ ] Sem HTTPS, câmera e webcam aparecem desabilitadas com o motivo.
- [ ] Uma folha capturada durante o preparo fica **Aguardando o leitor** e é lida quando o núcleo fica pronto.
- [ ] O tempo de preparo na primeira vez é medido e registrado por tipo de aparelho (meta no planejamento §6.10).

**Situação:** Não implementado — não existe núcleo de leitura no navegador (nenhuma menção a Pyodide, OpenCV.js ou WASM no código). O aparelho não persiste nada além do cache dos arquivos do manifesto (D-8).

---

## Grupo B — Entrada

## UC-079-03 — Capturar com a câmera do celular

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede, Aplicador
**Permissão:** `cartao.enviar`
**Escopo:** sessão de leitura aberta (UC-079-01)
**Pré-condições:** permissão de câmera concedida no navegador; HTTPS.
**Pós-condições:** folha capturada e entregue à leitura (grupo C).
**Tela/rota:** tela 3 do planejamento §7. Base visual: `.cam-tela` em tela cheia e `_camera.blade.php`.

**Fluxo principal:**
1. O sistema abre a câmera traseira em tela cheia.
2. O sistema mostra as guias nos quatro cantos e a dica "Enquadre a folha inteira nas guias."
3. O sistema detecta os quatro marcadores em tempo real.
4. Com 4 análises consecutivas com os 4 marcadores detectados (`estaveis` em `correcao-camera.js`), o sistema captura a folha sozinho.
5. O sistema confirma com "Foto capturada." e entrega a folha à barreira de qualidade (UC-079-08).
6. Depois da leitura, a folha abre no leitor visual (UC-079-14).

**Fluxos alternativos e de erro:**
- 1a. Permissão negada: "O acesso à câmera foi bloqueado. Libere a câmera nas configurações do navegador ou envie uma foto." e botão **Enviar foto** (UC-079-05). Mudança intencional: hoje a câmera do sistema abre sozinha (`correcao-camera.js`). No Leitor360, o usuário escolhe em **Enviar foto**.
- 1b. Câmera em uso por outro app: "A câmera está em uso por outro aplicativo. Feche o outro aplicativo e tente de novo."
- 2a. Pouca luz: o usuário toca em **Lanterna**.
- 3a. Marcador fora da guia: "Inclua os quatro quadrados inteiros nas guias antes de fotografar." (texto atual).
- 4a. O usuário prefere disparar: toca em **Fotografar**.
- 4b. Prova em que a página é o gabarito: as guias seguem os marcadores da página definidos pelo mapa. A dica mostra a página esperada quando o QR já foi lido. Exemplo: "Página 2 de 3." (UC-079-13).

**Regras de negócio:** captura a 1920×1080 com redução a 2400 px e JPEG 0,88, como hoje. A imagem enviada ao servidor é a original da captura, reduzida a 2400 px e com EXIF aplicado (D-12), lida pelo núcleo a partir dos mesmos bytes (planejamento §6.1, regra 5). No modo servidor, a imagem leva o marcador (UC-079-26). Em provas abertas, a resolução mínima pode ser maior (a medir na FN-080).

**Critérios de aceite:**
- [ ] A captura automática dispara só depois de 4 análises consecutivas com os 4 marcadores detectados.
- [ ] A folha capturada abre no leitor visual sem novo envio ao servidor antes da conferência.
- [ ] Com a permissão negada, o botão **Enviar foto** leva ao UC-079-05, e a câmera do sistema não abre sozinha.
- [ ] A lanterna aparece só quando o aparelho a oferece.
- [ ] Conferência registrada por texto em Android e iOS, com luz ambiente normal e fraca.

**Situação:** Parcial — reaproveita `public/js/correcao-camera.js` (`getUserMedia` com `facingMode` `environment` 1920×1080, guias, detecção de cantos, disparo automático, lanterna, redução a 2400 px JPEG 0,88). Hoje a foto vai direto ao servidor; a leitura no aparelho e o leitor visual não existem.

---

## UC-079-04 — Capturar com a webcam do computador

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede, Aplicador
**Permissão:** `cartao.enviar`
**Escopo:** sessão de leitura aberta (UC-079-01)
**Pré-condições:** webcam disponível; permissão concedida; HTTPS.
**Pós-condições:** folha capturada e entregue à leitura (grupo C).
**Tela/rota:** tela 4 do planejamento §7.

**Fluxo principal:**
1. O sistema lista as webcams do computador e abre a última usada.
2. O sistema pede a maior resolução disponível.
3. O sistema mostra a resolução obtida e as guias dos quatro cantos.
4. O usuário segura a folha na frente da webcam, ou usa um suporte.
5. Com os marcadores estáveis, o sistema captura a folha sozinho.
6. A folha segue para a barreira de qualidade (UC-079-08).
7. A webcam e o leitor visual dividem a tela: vídeo à esquerda e leitor da última folha à direita. A captura automática fica pausada enquanto há folha aberta no leitor. **Continuar** devolve o foco ao vídeo.

**Fluxos alternativos e de erro:**
- 1a. Mais de uma webcam: o usuário escolhe em **Trocar câmera**.
- 1b. Nenhuma webcam: "Nenhuma câmera encontrada neste computador. Envie fotos ou um PDF." e botões **Enviar fotos** e **Enviar PDF**.
- 1c. Permissão negada: mesma mensagem do UC-079-03, 1a.
- 3a. Resolução abaixo do mínimo medido na 3.2: o sistema avisa "A câmera deste computador tem baixa resolução. Aproxime a folha ou use o celular." A captura continua possível.
- 5a. A barreira recusa por `baixa_resolucao`: o sistema mostra a mensagem do UC-079-08 e sugere **Usar o celular** ou **Enviar fotos**.
- 5b. Imagem espelhada: o sistema desfaz o espelho antes da leitura. A pré-visualização pode continuar espelhada.

**Regras de negócio:** a barreira recusa imagens com o menor lado abaixo de 600 px ou o maior lado abaixo de 900 px (`qualidade.py`). Um quadro de 1280×720 passa nessa regra. ⚠ Inferência a medir na 3.2: a folha ocupa só parte do quadro, e as bolhas podem ficar pequenas demais para a leitura.

**Critérios de aceite:**
- [ ] O sistema mostra a resolução real obtida antes da primeira captura.
- [ ] A imagem lida não está espelhada.
- [ ] Com resolução abaixo do mínimo, o aviso aparece antes da captura.
- [ ] A captura automática não dispara com uma folha aberta no leitor.
- [ ] Teste registrado por texto com webcam de 720p e de 1080p, com cartão impresso real.

**Situação:** Não implementado — a câmera atual pede a câmera traseira e não trata webcam, troca de câmera nem espelhamento.

---

## UC-079-05 — Enviar fotos de arquivo

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede, Aplicador
**Permissão:** `cartao.enviar`
**Escopo:** sessão de leitura aberta (UC-079-01)
**Pré-condições:** sessão aberta.
**Pós-condições:** cada foto vira uma folha na fila da sessão.
**Tela/rota:** tela 5 do planejamento §7. Base: seletor `input type=file` com `multiple`.

**Fluxo principal:**
1. O usuário toca em **Fotos**.
2. O usuário escolhe uma ou várias imagens JPG, PNG, WEBP ou HEIC.
3. O sistema converte o HEIC no aparelho com a biblioteca local `heic-to-1.6.7.js`.
4. O sistema cria uma folha por imagem na fila da sessão.
5. O sistema lê uma folha por vez (grupo C).
6. A primeira folha lida abre no leitor visual (UC-079-14).
7. Depois de **Continuar**, a próxima folha da fila abre no leitor.

**Fluxos alternativos e de erro:**
- 2a. Arquivo de outro tipo: "Escolha imagens JPG, PNG, WEBP ou HEIC." O arquivo não entra na fila.
- 2b. Imagem maior que 10 MB: "A imagem passa de 10 MB. Escolha uma foto menor." (limite atual da API).
- 2c. Imagem que não abre: "Não foi possível abrir a foto. Escolha outra imagem JPG, PNG ou WEBP." (texto atual de `imagem_ilegivel`).
- 4a. A mesma imagem escolhida duas vezes: o sistema detecta pelo sha256 e mostra "Esta foto já está na fila." A cópia não entra.
- 5a. A barreira recusa uma folha: a folha fica na fila marcada **Recusada** com o motivo. As outras continuam.

**Variações por aparelho:** no celular, o seletor oferece a galeria e a câmera do sistema (`capture`). No computador, o usuário também pode arrastar as imagens para a área "Solte as fotos aqui".

**Regras de negócio:** limite de 10 MB por imagem (API atual). Quantidade máxima por seleção: proposta na 3.3, a confirmar pelo responsável. A imagem enviada é a original reduzida a 2400 px, com EXIF aplicado (D-12). Hoje o `heic-to` é usado só nas imagens de usuário, aluno e turma, não na correção.

**Critérios de aceite:**
- [ ] Cada imagem válida vira exatamente uma folha na fila.
- [ ] Fotos JPG, PNG, WEBP e HEIC abrem no leitor visual.
- [ ] Uma imagem repetida na mesma sessão não entra duas vezes.
- [ ] Uma folha recusada não interrompe a leitura das outras.
- [ ] No computador, arrastar e soltar tem o mesmo efeito do seletor.

**Situação:** Parcial — reaproveita o seletor da câmera atual (`input file` com `capture` e lote pela galeria com `multiple`, em `correcao-camera.js`) e a fila de envio (`cartoes-fila.js`). A leitura no aparelho e o leitor visual não existem.

---

## UC-079-06 — Enviar o PDF de uma folha

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede
**Permissão:** `cartao.enviar`
**Escopo:** sessão de leitura aberta (UC-079-01)
**Pré-condições:** sessão aberta.
**Pós-condições:** a página do PDF vira uma folha na fila da sessão.
**Tela/rota:** tela 5 do planejamento §7.

**Fluxo principal:**
1. O usuário toca em **PDF de uma folha**.
2. O usuário escolhe um arquivo PDF.
3. O sistema abre o PDF no aparelho, num Web Worker.
4. O sistema transforma a página em imagem entre 200 e 300 DPI.
5. A imagem segue para a barreira de qualidade (UC-079-08) e para a leitura.
6. A folha abre no leitor visual (UC-079-14).

**Fluxos alternativos e de erro:**
- 2a. Arquivo não é PDF: "Escolha um arquivo PDF."
- 3a. PDF protegido por senha: "Este PDF tem senha. Remova a senha e envie de novo."
- 3b. PDF corrompido: "Não foi possível abrir o PDF. Escolha outro arquivo."
- 3c. PDF com mais de uma página: modal "Este PDF tem 12 páginas. Ler como lote?" com botões **Ler como lote** (UC-079-07) e **Cancelar**.

**Variações por tipo de documento:** no cartão à parte, uma página = uma folha. Na página da prova como gabarito, uma página = uma folha de um aluno; as outras páginas chegam em outras folhas (UC-079-13).

**Regras de negócio:** o PDF.js segue as regras de segurança do planejamento §6.5 (`isEvalSupported: false`, sem scripts, limite de páginas e de tamanho). A página renderizada é a imagem enviada ao servidor, reduzida a 2400 px (D-12). O PDF de entrada não vai ao servidor.

**Critérios de aceite:**
- [ ] Um PDF de uma página gera exatamente uma folha.
- [ ] A imagem gerada passa na barreira de qualidade quando o PDF vem de um scanner a 200 DPI ou mais (subfase 3.4).
- [ ] Um PDF com várias páginas nunca é lido como uma folha só.
- [ ] O PDF original não fica guardado no navegador depois do envio (planejamento §6.8).

**Situação:** Não implementado — a API aceita só imagem (`imagem` com regra `image`) e não aceita PDF. Reaproveitável: PDF.js 6.5.438 e pdf-lib 1.17.1 em `public/js/libs/` (usados por `leitor-cartao.js`).

---

## UC-079-07 — Processar um lote de PDFs digitalizados

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede
**Permissão:** `cartao.enviar`
**Escopo:** sessão de leitura aberta (UC-079-01)
**Pré-condições:** um ou mais PDFs com várias páginas.
**Pós-condições:** cada página lida e conferida vira uma folha salva; as páginas não lidas ficam listadas no resumo (UC-079-18).
**Tela/rota:** tela 6 do planejamento §7 (barra de progresso, lista de páginas, ações **Pausar**, **Retomar** e **Cancelar lote**).

**Fluxo principal:**
1. O usuário toca em **Lote de PDFs**.
2. O usuário escolhe um ou mais PDFs.
3. O sistema conta as páginas e mostra "40 páginas em 2 arquivos."
4. O sistema fixa a versão do motor e lê as páginas em segundo plano, uma por vez por Worker.
5. O sistema mostra o progresso: "Lote: 12 de 40 páginas lidas."
6. O sistema separa as páginas em três abas com contagem: **Sem pendências**, **Para conferir** e **Recusadas**.
7. O usuário abre **Para conferir** e confere as folhas no leitor visual (UC-079-14 a UC-079-17).
8. O usuário toca em **Salvar sem pendências**. O modal "Salvar 28 folhas sem pendências?" oferece **Salvar 28 folhas** e **Voltar**.
9. O usuário escolhe **Encerrar**. O sistema mostra o resumo (UC-079-18).

**Fluxos alternativos e de erro:**
- 4a. O usuário toca em **Pausar**: o sistema termina a página atual e para. O botão vira **Retomar**.
- 4b. O usuário recarrega a página ou fecha a aba: o aparelho não guarda nada (D-8). O sistema pede os arquivos de novo: "Escolha de novo os arquivos do lote para continuar." A API recusa como repetida a página já recebida, pela chave derivada do sha256 da página (proposta, a validar na 3.5).
- 4c. O aparelho fica sem memória: o sistema pausa o lote e mostra "O aparelho ficou sem memória. Feche outras abas e toque em Retomar."
- 4d. Lote acima do limite de páginas do aparelho (D-3, limite definido na 1.2): o navegador lê as páginas até o limite, com estimativa de tempo. As páginas acima do limite seguem o modo servidor, com aviso de créditos e de fila (UC-079-26).
- 4e. Perda de conexão (D-7): o lote pausa. Nada é salvo e nada fica guardado no aparelho. O sistema mostra "Sem conexão. O lote está pausado." Quando a conexão volta, o usuário toca em **Retomar**.
- 4f. A versão do motor muda durante o lote (422 `motor_desatualizado`): o lote pausa, baixa a vigente e relê as folhas salvas e ainda não enviadas. As correções do usuário são mantidas quando a nova leitura tem o mesmo valor. Quando o valor muda, a questão volta a ser pendência.
- 6a. Página sem marcadores: entra em **Recusadas** com o motivo da barreira.
- 6b. Página de outra prova: tratada no UC-079-09.
- 6c. PDF de scanner frente e verso com versos em branco: o verso fica em **Recusadas** como `pagina_em_branco`, separado das recusas de qualidade. Ele não conta como pendência.
- 9a. **Cancelar lote** com folhas não salvas: modal "Cancelar o lote?" com o texto "As páginas já salvas continuam salvas. As outras serão descartadas." e botões **Cancelar lote** e **Voltar**.

**Variações por tipo de documento:** no cartão à parte, uma página = um aluno. Na página da prova como gabarito, várias páginas = um aluno, agrupadas pelo QR (UC-079-13). Nas abertas e mistas, a transcrição (UC-079-11) pode deixar o lote bem mais lento (a medir na FN-080).

**Variações por aparelho:** no computador, o lote é a entrada principal. No celular, o lote fica disponível com o aviso "Lotes grandes podem demorar no celular." e limite de páginas definido na 1.2.

**Regra do PDF (D-11):** cada página do PDF abre como imagem, folha por folha. A análise corre em segundo plano. O resultado de cada página entra na tabela da página do lote. Os dados de envio de cada folha (chave de envio, sha256 e versão do motor) já ficam preparados nesse momento.

**Regras de negócio:** só uma página fica em memória por Worker. A ordem do lote não decide o aluno: o QR decide. Nenhuma página é salva sem passar pela barreira de qualidade. A lista de páginas usa `x-tabela` no card principal, com a coluna **Ações** à direita ([ADR-0022](../../decisoes/0022-tabelas-cards-de-registros-e-avisos.md), [ADR-0039](../../decisoes/0039-coluna-de-acoes-a-direita-e-ordenacao.md)).

**Critérios de aceite:**
- [ ] Nenhuma tarefa longa acima de 50 ms ocorre na thread principal durante o lote (`PerformanceObserver`).
- [ ] **Pausar** para depois da página atual; **Retomar** continua da página seguinte.
- [ ] Ao reabrir os mesmos arquivos, as páginas já salvas não são enviadas de novo, e nenhuma `envio_id` duplica.
- [ ] Sem conexão, o lote pausa, nada é salvo e nada fica guardado no aparelho.
- [ ] Páginas acima do limite do aparelho seguem o modo servidor só depois do aviso confirmado (UC-079-26).
- [ ] Os lotes de 40 e 200 páginas são lidos no computador de referência em tempo medido e registrado (meta proposta na 3.5).
- [ ] O pico de memória da aba, medido via CDP (`Performance.getMetrics`, `JSHeapUsedSize` e memória do Worker), com 200 páginas fica até 1,2 × o pico com 40 páginas (proposta).
- [ ] **Salvar sem pendências** nunca salva sem o modal de confirmação.
- [ ] Um verso em branco não conta como pendência.

**Situação:** Não implementado — a API não aceita PDF. Reaproveitável: PDF.js em `public/js/libs/`; idempotência por chave de envio (`cartoes-fila.js`, FN-002).

---

## Grupo C — Leitura

## UC-079-08 — Aplicar a barreira de qualidade no aparelho

**Ator:** sistema
**Permissão:** `cartao.enviar` (herdada da sessão)
**Escopo:** folha da sessão
**Pré-condições:** folha recebida de qualquer entrada (grupo B).
**Pós-condições:** folha aprovada para leitura, ou recusada com código e mensagem.
**Tela/rota:** sem tela própria. As mensagens aparecem na câmera, na webcam ou na fila.

**Fluxo principal:**
1. O sistema mede resolução, marcadores, geometria e nitidez da folha no aparelho.
2. O sistema registra a versão da barreira (hoje `omr-qualidade-1`).
3. Com a folha adequada, o sistema segue para a identificação (UC-079-09).

**Fluxos alternativos e de erro:**
- 1a. Folha recusada: o sistema mostra a mensagem do código. Todo problema de qualidade exige que o usuário verifique a prova manualmente (revisão manual); não há decisão automática. Mensagens e códigos: `omr/qualidade.py` e [UC-047-01](../047-omr-qualidade-resultado/casos-de-uso.md). Códigos atuais: `baixa_resolucao`, `marcadores_incompletos`, `marcador_cortado`, `geometria_insegura`, `sem_nitidez`, `imagem_ilegivel`.

**Variações por entrada:** na câmera e na webcam, a recusa aparece sobre o vídeo, e a câmera continua aberta. Na foto, no PDF e no lote, a folha fica **Recusada** na fila, com o motivo e as ações **Remover** e **Trocar imagem**.

**Regras de negócio:** a barreira no aparelho usa a mesma regra e os mesmos limites da barreira do servidor (FN-047). O servidor faz sempre uma verificação barata (dimensões, tipo e decodificação). O servidor não relê a folha. Uma folha recusada no aparelho não vai ao servidor.

**Critérios de aceite:**
- [ ] No conjunto de referência, a barreira no aparelho tem paridade de 100% (decisão e código).
- [ ] Toda recusa mostra a mensagem do código, em pt-BR.
- [ ] A câmera continua aberta depois de uma recusa.

**Situação:** Parcial — reaproveita a regra e as mensagens de `omr/qualidade.py` (FN-047, UC-047-01). Hoje a barreira roda só no servidor.

---

## UC-079-09 — Identificar a folha pelo QR

**Ator:** sistema; Professor, Coordenador, Diretor Escolar ou Administrador da Rede para vincular
**Permissão:** `cartao.enviar` para identificar; `correcao.alterar` para vincular um aluno à mão
**Escopo:** prova no escopo; aluno das turmas da prova
**Pré-condições:** folha aprovada pela barreira (UC-079-08).
**Pós-condições:** folha associada a prova, aluno e página; ou marcada **Sem aluno** para vinculação.
**Tela/rota:** no leitor visual (UC-079-14), faixa de identificação no topo. Vinculação: tela 8 do planejamento §7. Base: web `cartoes.vincular-aluno`.

**Fluxo principal:**
1. O sistema lê o QR da folha.
2. O sistema confere a prova do QR com a prova da sessão.
3. O sistema confere se o aluno pertence às turmas da prova.
4. O sistema confere a página quando o mapa tem várias páginas (UC-079-13).
5. O leitor visual mostra "Ana Souza · 9º A · Página 1 de 1".

**Fluxos alternativos e de erro:**
- 1a. Sem QR ou QR ilegível (cartão em branco): o leitor mostra "Aluno não identificado" e o botão **Escolher aluno**.
- 1b. O usuário escolhe o aluno na lista da turma. Busca por nome ou matrícula.
- 1c. QR legível sem o prefixo `G360` ou com campos inválidos: tratado como sem QR (1a).
- 1d. Aplicador sem `correcao.alterar`: a folha segue **Sem aluno**. A vinculação fica para a coordenação ou o professor (UC-045-05).
- 2a. QR de outra prova da mesma escola, acessível ao usuário: modal "Esta folha é de outra prova" com o texto "O QR indica a prova {nome}. Ler esta folha nessa prova?" e botões **Ler na outra prova** e **Descartar folha**. O mapa da outra prova só chega depois da checagem de escopo (planejamento §6.2, regra 8).
- 2b. QR de prova indisponível ao usuário ou de outra escola: "O QR indica outra prova que não está disponível para correção neste acesso. Confira o cartão e a prova." (texto atual). A folha é descartada.
- 2c. Cartão impresso com outra quantidade de questões ou alternativas: "Este cartão tem um formato diferente da prova. Confira o cartão." (código atual `layout_divergente`).
- 2d. Página da prova enviada como cartão, ou o contrário: `layout_divergente`, com a mesma mensagem.
- 3a. Aluno fora das turmas: "O aluno não pertence às turmas desta prova." (texto atual).
- 3b. Aluno já tem leitura: ver UC-079-21.

**Regras de negócio:** o QR envia a folha para a prova certa da mesma escola e espaço, como hoje. N e K do QR prevalecem sobre a prova. QR atual: `G360|P{prova}|A{aluno}|N|K|L`. O servidor decodifica de novo o QR da imagem e decide prova e aluno (planejamento §6.6, item 2d). O QR atual não traz página. O QR novo traz a página (D-10, FN-043), e o leitor aceita os dois formatos.

**Critérios de aceite:**
- [ ] No conjunto de referência, o QR lido no aparelho tem paridade de 100%.
- [ ] Uma folha de outra prova nunca é gravada na prova da sessão.
- [ ] O aplicador nunca vincula aluno à mão.
- [ ] A busca de aluno mostra só alunos das turmas da prova.
- [ ] Um QR sem o prefixo `G360` é tratado como sem QR.

**Situação:** Parcial — reaproveita a leitura do QR (`cv2.QRCodeDetector` em `ler_cartao.py`), o redirecionamento por QR (`ProcessarCartaoJob`) e a vinculação (`CartaoController::vincularAluno`, UC-045-05). Hoje tudo roda no servidor.

---

## UC-079-10 — Ler questões objetivas (método objetiva-bolhas)

**Ator:** sistema
**Permissão:** `cartao.enviar` (herdada da sessão)
**Escopo:** regiões objetivas do mapa de respostas
**Pré-condições:** folha identificada (UC-079-09); mapa com regiões do tipo objetiva.
**Pós-condições:** cada questão objetiva tem alternativa ou nulo, confiança, alternativas detectadas e estado.
**Tela/rota:** sem tela própria. O resultado abre no leitor visual (UC-079-14).

**Fluxo principal:**
1. O sistema orienta a folha e a retifica a 8 px/mm pelos quatro marcadores.
2. O sistema normaliza o fundo.
3. Para cada questão objetiva do mapa, o sistema mede a fração escura de cada bolha.
4. O sistema classifica cada questão e calcula a confiança. Limiares e correspondência de estados: [planejamento §6.1](planejamento.md#61-arquitetura-em-camadas-do-motor).
5. O sistema entrega o resultado ao leitor visual, com a versão do motor.

**Fluxos alternativos e de erro:**
- 1a. Folha girada 90° ou 180°: o preparo usa o QR para achar a orientação.
- 1b. Folha que não pode ser orientada: as questões ficam `nao_lido` com o código `orientacao_desconhecida`.
- 3a. Região do mapa fora da folha: a questão fica **Não lida**. O sistema registra o erro para a equipe técnica (UC-079-25).
- 5a. Falha do núcleo de leitura: "Não foi possível ler esta folha. Capture de novo ou escolha outro arquivo." A folha fica na fila como **Erro**.

**Regras de negócio:** o método no aparelho e o do servidor usam os mesmos limiares e a mesma geometria (`omr/layout.py`). A divergência de limiares com o [06](../../arquitetura/06-omr-e-documentos.md) é risco do planejamento §8.

**Critérios de aceite:**
- [ ] No conjunto de referência, o resultado no aparelho tem paridade de 100%, questão por questão: alternativa e estado iguais e confiança com diferença até 0,01 (proposta; planejamento, subfase 1.1).
- [ ] A leitura de um cartão de 45 questões cumpre a meta do planejamento §6.10, item 5, no celular e no computador de referência (subfase 1.2).
- [ ] Todo resultado carrega a versão do motor.

**Situação:** Parcial — reaproveita a lógica de `omr/ler_cartao.py` e `omr/layout.py` (cv2 + numpy). Hoje roda só no servidor. No aparelho, o núcleo roda por Pyodide, com as mesmas versões do servidor (D-4, D-5).

---

## UC-079-11 — Ler respostas abertas (método transcricao-manuscrita)

**Ator:** sistema
**Permissão:** `cartao.enviar` para enviar a folha; `leitura.transcrever` (chave nova, D-9) para usar a IA local. A transcrição no aparelho não consome crédito. No modo servidor, a transcrição consome créditos de correção (UC-079-26).
**Escopo:** regiões abertas do mapa de respostas
**Pré-condições:** folha identificada; mapa com regiões do tipo aberta, com `tipo_esperado`; modelo de IA local pronto no aparelho (UC-080-01).
**Pós-condições:** cada região aberta tem a lista de trechos, o recorte da imagem e a confiança.
**Tela/rota:** sem tela própria. O resultado abre no leitor visual (UC-079-14 e UC-079-16).

**Fluxo principal:**
1. O sistema recorta cada região aberta pela posição do mapa.
2. O sistema envia o recorte e o `tipo_esperado` ao modelo de IA local no aparelho.
3. O modelo devolve a lista de trechos (texto, fórmula ou desenho), cada um com confiança ([FN-080 §6.1](../080-ia-local-transcricao/planejamento.md)).
4. O sistema guarda os trechos junto com o recorte da imagem.
5. O sistema marca as regiões de baixa confiança como pendência.

**Fluxos alternativos e de erro:**
- 2a. Modelo não carregado ou aparelho sem capacidade: o sistema mostra a mensagem simples do UC-079-23 e oferece o modo servidor para as regiões abertas (UC-079-26). Se o usuário não aceita, a região fica **Não lida — transcrição indisponível**. O usuário ainda pode digitar a transcrição no leitor (UC-079-16).
- 3a. Região em branco: o sistema marca **Em branco**.
- 3b. Desenho sem texto: o modelo devolve um trecho do tipo desenho. O sistema guarda o recorte.
- 3c. Tempo esgotado na região: a região fica **Não lida**. As outras continuam.

**Variações por aparelho:** no computador com WebGPU, o caminho é o preferido. No celular, a transcrição depende da memória e do modelo comprimido (FN-080).

**Regras de negócio:** a IA **só lê**. Ela não corrige e não dá nota. A IA roda no aparelho e não envia dados a terceiros. Os trechos e o recorte vão ao servidor com a imagem (UC-079-19). A transcrição fica com `origem = aparelho` e "não verificada no servidor" (planejamento §6.8, regra 11). O método é plugável: o leitor visual não muda quando o método muda.

**Critérios de aceite:**
- [ ] No teste de rede do planejamento §6.8, nenhuma requisição vai a outra origem durante a transcrição.
- [ ] Toda transcrição fica guardada com o recorte da região.
- [ ] Nenhuma nota é calculada a partir da transcrição.
- [ ] Qualidade da transcrição medida no conjunto de manuscritos da FN-080 (metas na FN-080 §5.1).

**Situação:** Não implementado — depende da FN-080 (candidata). Não existe questão aberta no modelo de dados.

---

## UC-079-12 — Ler uma prova mista

**Ator:** sistema
**Permissão:** `cartao.enviar` (herdada da sessão)
**Escopo:** todas as regiões do mapa de respostas da folha
**Pré-condições:** folha identificada; mapa com regiões objetivas e abertas.
**Pós-condições:** resultado completo da folha, com cada região lida pelo seu método.
**Tela/rota:** sem tela própria. O resultado abre no leitor visual (UC-079-14).

**Fluxo principal:**
1. O sistema consulta o mapa de respostas da página.
2. Para cada região, o sistema escolhe o método pelo tipo: objetiva → `objetiva-bolhas`; aberta → `transcricao-manuscrita`.
3. O sistema lê primeiro as regiões objetivas (UC-079-10).
4. O sistema mostra a folha no leitor visual com as objetivas prontas.
5. O sistema lê as regiões abertas em segundo plano (UC-079-11).
6. Cada região aberta aparece no leitor quando termina.

**Fluxos alternativos e de erro:**
- 2a. Tipo de região sem método disponível: a região fica **Não lida — método indisponível**. As outras regiões são lidas.
- 5a. Transcrição indisponível no aparelho: as objetivas seguem normalmente no aparelho. As regiões abertas vão ao modo servidor (UC-079-26). O crédito é por correção da prova do aluno (aluno × prova), sem divisão por região.

**Regras de negócio:** o leitor segue o mapa e não deduz o tipo da questão. Um novo método entra sem mudar o leitor visual nem o despacho (planejamento §6.1).

**Critérios de aceite:**
- [ ] Numa prova mista, cada região é lida pelo método do seu tipo no mapa.
- [ ] As objetivas ficam disponíveis para conferência antes do fim da transcrição.
- [ ] A falta de um método não impede a leitura das outras regiões.

**Situação:** Não implementado — não existe mapa de respostas nem tipo por questão (`provas.formato_avaliacao` aceita só "gabarito").

---

## UC-079-13 — Ler uma prova em que a página é o gabarito, com várias páginas

**Ator:** sistema; usuário da sessão para completar páginas
**Permissão:** `cartao.enviar`
**Escopo:** páginas da prova no mapa de respostas
**Pré-condições:** mapa de respostas com páginas, vindo da FN-043; QR novo com página (D-10).
**Pós-condições:** as páginas do aluno ficam agrupadas numa única leitura do aluno.
**Tela/rota:** no leitor visual, faixa "Página 2 de 3" e lista de páginas do aluno. Tela 9 do planejamento §7.

**Fluxo principal:**
1. O sistema identifica prova, aluno e página pelo QR da folha.
2. O sistema aplica o mapa daquela página.
3. O sistema lê as regiões da página pelo método de cada uma (UC-079-12).
4. O sistema junta a página às outras páginas do mesmo aluno na sessão.
5. O leitor mostra "Ana Souza · Página 2 de 3 · Falta a página 3."
6. Com todas as páginas lidas, o leitor marca o aluno como **Completo**.

**Fluxos alternativos e de erro:**
- 1a. Página repetida do mesmo aluno: modal "Esta página já foi lida" com botões **Substituir** e **Manter a anterior**.
- 1b. Página sem QR: o usuário escolhe o aluno e a página em **Escolher aluno**.
- 5a. O usuário salva o aluno sem todas as páginas: o sistema salva as páginas lidas e marca "Faltam páginas". O resumo lista o aluno (UC-079-18).
- 5b. Ordem das páginas trocada no lote: o QR decide a página. A ordem não importa.

**Variações por entrada:** na câmera e na webcam, o usuário fotografa as páginas em sequência, e o leitor indica a próxima página esperada. No lote de PDFs, o sistema agrupa as páginas pelo QR, mesmo fora de ordem.

**Regras de negócio:** a FN-043 produz o mapa de respostas e o QR por página. O Leitor360 só consome. Sem a FN-043, este caso fica indisponível e aparece como "Em breve".

**Critérios de aceite:**
- [ ] Páginas fora de ordem são agrupadas corretamente pelo QR.
- [ ] Um aluno com página faltando aparece no resumo do lote.
- [ ] Uma página repetida nunca substitui a anterior sem confirmação.

**Situação:** Não implementado — depende da FN-043 (candidata). O QR atual não traz página.

---

## Grupo D — Leitor visual

## UC-079-14 — Conferir a leitura no leitor visual

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede; Aplicador, com visão restrita (D-6, [ADR-0059](../../decisoes/0059-aplicador-ve-a-leitura-sem-corrigir.md))
**Permissão:** `cartao.enviar` para abrir. O Aplicador vê as marcações lidas da folha atual com essa mesma permissão e não corrige.
**Escopo:** folha da sessão
**Pré-condições:** folha lida (grupo C).
**Pós-condições:** nenhuma alteração até o usuário corrigir ou salvar.
**Tela/rota:** tela 7 do planejamento §7. Base visual: revisão atual (web `correcao.revisao`, parciais `correcao/_documento` e `_leitura-questoes`) e `leitor-cartao.js`.

**Fluxo principal:**
1. O sistema mostra a folha endireitada com as marcações lidas sobrepostas.
2. O sistema separa dois níveis, cada um com rótulo e ícone:
   - **Pendência:** **Marcação dupla**, **Marcação fraca** e **Não lida**. Entra no contador e em **Próxima pendência**.
   - **Aviso:** **Em branco**. Aparece na lista, mas não entra no contador nem vai para a revisão.
3. O sistema mostra o contador "3 questões para conferir" e, à parte, "2 em branco".
4. O usuário toca em **Próxima pendência** (tecla N) para ir à próxima pendência.
5. O usuário amplia a imagem com pinça, roda do mouse ou os botões + e −.
6. O usuário corrige (UC-079-15 ou UC-079-16) ou salva (UC-079-17).

**Fluxos alternativos e de erro:**
- 2a. Sem pendências: o contador mostra "Nenhuma questão para conferir." e o botão **Salvar folha** fica em destaque.
- 2b. Transcrição em andamento: a região mostra "Transcrevendo…" (UC-079-12).
- 2c. Folha lida antes do núcleo ficar pronto: estado **Aguardando o leitor** (UC-079-02).
- 5a. Aplicador (D-6): vê só as marcações lidas da folha atual, antes de salvar, para refazer a foto se preciso. Ele não corrige. Ele não vê gabarito, acerto, nota, folhas anteriores nem folhas de outros.
- 6a. O usuário toca em **Sair do leitor** ou no Voltar do sistema: segue a regra de saída do planejamento §7 (modal "Sair do leitor?" com folhas não enviadas).

**Variações por aparelho (régua do 09):**
- Até 767 px: folha em tela cheia; a lista de questões abre numa gaveta inferior; navegação inferior oculta durante o leitor.
- De 768 a 1099 px: folha em cima e lista embaixo, sem gaveta.
- A partir de 1100 px: folha à esquerda e painel lateral à direita, como na revisão atual (`minmax(0,1.2fr) minmax(310px,1fr)`).

**Regras de negócio:** o leitor nunca mostra acerto, erro ou nota. A nota é calculada só no servidor. Os estados vêm do planejamento §6.1. A acessibilidade segue o planejamento §7.

**Critérios de aceite:**
- [ ] Toda questão em marcação dupla, marcação fraca ou não lida conta como pendência e aparece destacada.
- [ ] Uma questão em branco seguro aparece como aviso, fora do contador e fora da revisão.
- [ ] **Próxima pendência** percorre todas as pendências, em ordem.
- [ ] O leitor não mostra gabarito, acerto ou nota a nenhum perfil.
- [ ] O aplicador vê as marcações só da folha atual, antes de salvar, e não vê folhas anteriores nem de outros.
- [ ] Os destaques são distinguíveis sem depender só da cor (rótulo, ícone e forma).
- [ ] Conferência registrada por texto a 360, 768 e 1280 px, temas claro e escuro.

**Situação:** Parcial — reaproveita a revisão da FN-046 (`revisao.blade.php`, `correcao-revisao.js`), o leitor de documento (`leitor-cartao.js`, zoom 0,5–5, painel lateral no desktop e dialog no celular) e o recorte por questão (`ImagensCartao::recorte()`). Hoje a revisão acontece depois do envio, no servidor.

---

## UC-079-15 — Corrigir uma questão objetiva no leitor

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede
**Permissão:** `correcao.alterar` (o Aplicador não tem `correcao.alterar` e não corrige; D-6)
**Escopo:** folha da sessão; correção aberta ou ator com `correcao.reabrir`
**Pré-condições:** folha aberta no leitor visual.
**Pós-condições:** resposta da questão definida no aparelho e marcada como **Conferida pelo usuário**; a alteração vai ao servidor em `correcoes[]` no envio (UC-079-19).
**Tela/rota:** tela 7 do planejamento §7. Base: bloco `.rev-trabalho` e atalhos de `correcao-revisao.js`.

**Fluxo principal:**
1. O usuário toca na questão destacada.
2. No celular, a gaveta inferior abre no estado "corrigir". Ela mostra o bloco da revisão atual (`.rev-trabalho`): recorte, "Leitura do sistema: …", botões **A** a **E**, **Em branco**, **Anulada**, **Manter leitura** e **Desfazer**. A folha rola para deixar a questão visível acima da gaveta. No computador, o mesmo bloco aparece no painel lateral.
3. O usuário escolhe uma opção.
4. O sistema marca a questão como conferida, atualiza o contador e anuncia "Questão 7: B. Faltam 2." em `role="status"`.
5. O sistema vai para a próxima pendência.

**Fluxos alternativos e de erro:**
- 1a. Aplicador: ele não corrige (D-6). A questão segue para conferência no servidor (UC-046-01).
- 3a. No computador, o usuário usa o teclado: A a E respondem, 0 deixa em branco, X anula, N vai à próxima pendência, Ctrl+Enter salva. A linha de ajuda mostra todos os atalhos. Os atalhos ficam inativos com foco em campo de texto, em `select` ou com diálogo aberto.
- 3b. O usuário toca em **Desfazer**: a questão volta à leitura original.
- 3c. **Manter leitura** aparece só quando a leitura tem uma alternativa (estado `fraca` com letra). Em `dupla`, `nao_lido` e `fraca` sem letra, o botão não aparece.

**Regras de negócio:** "Em branco" e "Anulada" ficam sem alternativa e não pontuam (ver FN-046). Toda alteração no aparelho é registrada com a leitura original e a decisão. O servidor grava o autor da sessão e o momento do servidor (planejamento §6.6). No servidor, a decisão entra em `ambiguidade_logs`, como na revisão atual.

**Critérios de aceite:**
- [ ] A leitura original e a decisão do usuário chegam ao servidor separadas.
- [ ] **Desfazer** restaura a leitura original antes do envio.
- [ ] Os atalhos funcionam no computador e não disparam com foco num campo de texto.
- [ ] **Manter leitura** não aparece em marcação dupla.
- [ ] O contador chega a zero quando todas as pendências são conferidas.

**Situação:** Parcial — reaproveita `correcao-revisao.js` (botões de alternativa, Em branco, Anulada, atalhos 0 e X, desfazer local) e a regra de `CartaoController::resolverAmbiguidade/revisar`. Hoje a decisão vai ao servidor questão por questão. O reaproveitamento não herda os `alert()` atuais (planejamento §7).

---

## UC-079-16 — Corrigir a transcrição de uma resposta aberta

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede
**Permissão:** `correcao.alterar`
**Escopo:** folha da sessão; correção aberta ou ator com `correcao.reabrir`
**Pré-condições:** região aberta transcrita (UC-079-11).
**Pós-condições:** trechos corrigidos no aparelho, com a transcrição original preservada.
**Tela/rota:** tela 10 do planejamento §7. API proposta: `POST v1/cartoes/{id}/transcricoes`.

**Fluxo principal:**
1. O usuário toca na região aberta.
2. O sistema mostra o recorte da imagem e o texto transcrito lado a lado.
3. O sistema sublinha os trechos de baixa confiança.
4. O usuário edita o texto.
5. O usuário toca em **Confirmar texto**.
6. O sistema marca a região como conferida.

**Fluxos alternativos e de erro:**
- 4a. Fórmula: o usuário edita na notação definida pela FN-080.
- 4b. Região sem texto legível: o usuário toca em **Ilegível**. A região recebe um trecho do tipo ilegível e guarda só o recorte.
- 5a. O usuário sai sem confirmar: modal "Descartar a edição?" com botões **Descartar** e **Voltar**.

**Regras de negócio:** a conferência corrige a leitura. Ela não é correção pedagógica nem atribui nota. A transcrição original da IA vai no envio (`cartao.enviar`). A transcrição conferida só vai pela rota `transcricoes`, com `correcao.alterar`, e é recusada com a correção fechada. O texto é escapado em toda saída (planejamento §6.8, regra 11).

**Critérios de aceite:**
- [ ] O texto original e o conferido chegam ao servidor separados.
- [ ] No celular, o recorte continua visível enquanto o usuário digita.
- [ ] **Ilegível** grava a marca sem texto.
- [ ] Um texto com marcação HTML aparece como texto, sem executar.

**Situação:** Não implementado — depende da FN-080 e do modelo de dados de questão aberta (FN-043).

---

## UC-079-17 — Salvar a folha e escolher Continuar ou Encerrar

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede, Aplicador
**Permissão:** `cartao.enviar`
**Escopo:** folha da sessão
**Pré-condições:** folha aberta no leitor visual.
**Pós-condições:** folha enviada ao servidor (UC-079-19) e, se houver pendências, marcada "com pendência" (D-11); sessão continua com a próxima folha, ou termina com o resumo (UC-079-18).
**Tela/rota:** tela 7 e modais do planejamento §7 ([ADR-0025](../../decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md)).

**Fluxo principal:**
1. O usuário toca em **Salvar folha** (ou Ctrl+Enter).
2. O sistema envia a imagem e o resultado ao servidor (UC-079-19).
3. O sistema abre o modal "Folha salva" com o texto "A leitura de Ana Souza foi salva. 12 folhas nesta sessão."
4. O modal oferece **Continuar** (botão principal, foco inicial) e **Encerrar**.
5. Com **Continuar**, o sistema volta à entrada: a câmera reabre, ou a próxima folha da fila abre no leitor.
6. Com **Encerrar**, o sistema mostra o resumo (UC-079-18).

**Fluxos alternativos e de erro:**
- 1a. Restam pendências (D-11): modal "Salvar com pendências?" com o texto "3 questões vão para a revisão da prova." e botões **Salvar com pendências** e **Voltar**. A folha e a prova ficam marcadas "com pendência". As pendências seguem para a revisão atual (UC-046-01), onde o usuário as corrige depois. A correção da prova não pode ser fechada enquanto houver folha com pendência (UC-079-27). Os avisos (em branco) não contam.
- 1b. Aplicador: sempre salva. As pendências vão para a revisão da coordenação ou do professor.
- 1c. O usuário toca em **Descartar folha**: a folha sai da fila, e a imagem sai da memória.
- 2a. Sem conexão (D-7): o sistema não salva a folha e mostra "Sem conexão. Esta folha ainda não foi salva." A folha continua na tela. Quando a conexão volta, o sistema salva a folha. Nada fica guardado no aparelho (D-8).
- 3a. Folha sem aluno identificado: o texto é "A folha sem aluno identificado foi salva. 12 folhas nesta sessão."
- 4a. Lote com a próxima folha já lida: **Continuar** abre direto a próxima folha **Para conferir**.
- 4b. Enter aciona **Continuar**. Esc fecha o modal e mostra a folha salva só para leitura, com a faixa "Folha salva" e os botões **Continuar** e **Encerrar** no rodapé do leitor. A folha salva não aceita nova correção no aparelho.

**Regras de negócio:** salvar não calcula nota no aparelho. Nenhuma confirmação usa diálogo nativo do navegador.

**Critérios de aceite:**
- [ ] O modal aparece depois de toda folha salva.
- [ ] **Continuar** leva à próxima captura em até um toque.
- [ ] Nenhuma confirmação usa diálogo nativo do navegador.
- [ ] Uma folha com pendências salva vai para a revisão da prova (UC-046-01) e fica marcada "com pendência".
- [ ] Sem conexão, a folha não é salva, a mensagem "Sem conexão. Esta folha ainda não foi salva." aparece, e a folha é salva quando a conexão volta, sem cópia no aparelho.
- [ ] Depois de Esc, a folha salva não aceita correção.

**Situação:** Não implementado — hoje a câmera envia direto e mostra "Foto capturada. Coloque o próximo cartão." (`correcao-camera.js`). O padrão de modal da aplicação existe (ADR-0025).

---

## UC-079-18 — Ver o resumo do lote ao encerrar

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede; Aplicador com resumo reduzido
**Permissão:** `cartao.enviar`
**Escopo:** folhas da sessão
**Pré-condições:** usuário escolheu **Encerrar** (UC-079-17) ou terminou um lote (UC-079-07).
**Pós-condições:** sessão encerrada; memória do leitor apagada (planejamento §6.8, regra 7).
**Tela/rota:** tela 11 do planejamento §7. Componentes: `x-indicador` e `x-tabela` no card principal, coluna **Ações** à direita.

**Fluxo principal:**
1. O sistema mostra os indicadores do lote:
   - folhas salvas;
   - folhas enviadas;
   - folhas aguardando envio;
   - folhas recusadas;
   - folhas com pendências para revisão;
   - folhas sem aluno identificado;
   - alunos com páginas faltando.
2. O sistema lista as folhas que pedem ação, com o motivo e a ação.
3. O usuário toca em **Ver revisão da prova** (web `correcao.revisao`) ou em **Ver acompanhamento** (web `correcao.show`).

**Fluxos alternativos e de erro:**
- 1a. Folhas aguardando envio: aviso "4 folhas ainda não foram enviadas." e botão **Enviar agora**. Sair nesse estado abre o modal "Sair do leitor?" (planejamento §7).
- 1b. Aplicador: vê só folhas salvas, enviadas, recusadas e sem aluno. Não vê pendências de leitura nem links de revisão.
- 1c. Sessão sem folhas: estado vazio "Nenhuma folha lida nesta sessão."

**Regras de negócio:** o resumo não mostra notas. Ao encerrar, o leitor apaga imagens, recortes e buffers e encerra os Workers.

**Critérios de aceite:**
- [ ] Os números do resumo batem com as folhas da sessão (subfase 2.4).
- [ ] O aplicador não vê link de revisão nem de resultado.
- [ ] Com folhas aguardando envio, o modal "Sair do leitor?" aparece antes de o usuário sair.
- [ ] Depois de Encerrar, a inspeção de IndexedDB, Cache Storage, OPFS, `sessionStorage` e Service Worker não encontra imagem, recorte, resposta nem nome de aluno (conferência por texto, Chrome e Firefox).

**Situação:** Não implementado — reaproveitável: o resumo de envios atual ("Neste acompanhamento: … fotos · … concluídas · … precisam de ação.", `correcao-camera.js`).

---

## Grupo E — Servidor e integridade

## UC-079-19 — Enviar a imagem e o resultado ao servidor

**Ator:** sistema, na sessão do usuário
**Permissão:** `cartao.enviar`; `correcao.alterar` para enviar `correcoes[]`
**Escopo:** prova no escopo; aplicador só no dia
**Pré-condições:** folha salva (UC-079-17).
**Pós-condições:** imagem guardada no servidor; resultado guardado com sha256 da imagem, versão do motor e versão do mapa; cartão `lido` ou `ambiguo`; nota calculada só no servidor e só sem pendências.
**Tela/rota:** sem tela própria. Indicador de envio na fila. API: a rota atual `POST v1/provas/{id}/cartoes`, estendida com o campo opcional `leitura` ([planejamento §6.6](planejamento.md#66-api-proposta)). Consulta: `GET v1/provas/{id}/cartoes/envios/{chave}`, como hoje.

**Fluxo principal:**
1. O sistema gera uma chave de envio estável por folha.
2. O sistema envia a imagem e o resultado juntos.
3. A API confere permissão, escopo, correção aberta e conteúdo da chave.
4. A API confere o sha256 recebido com a imagem recebida. Esse hash garante só a integridade do transporte.
5. A API decodifica o QR da imagem e recalcula estado e confiança a partir de `detectadas`.
6. A API faz só a verificação barata (dimensões, tipo e decodificação) e grava. O servidor não relê a folha. Quem confere a leitura é o usuário (UC-079-20).
7. A API grava, numa transação que revalida o acesso, a imagem, o resultado e as decisões do usuário.
8. A API calcula a nota quando não há pendências.
9. A API responde com o estado do envio. O indicador da folha muda para **Enviada**.

**Fluxos alternativos e de erro:**
- 2a. Sem conexão (D-7): o envio não acontece e a folha não é salva. O leitor mostra "Sem conexão. Esta folha ainda não foi salva." Quando a conexão volta, o sistema envia a folha que está na tela, com a mesma chave. Não existe fila offline, e nada fica guardado no aparelho (D-8).
- 2b. Tempo esgotado (30 s, como hoje): o sistema tenta de novo com a mesma chave.
- 2c. Reenvio de folha `recusada` pelo servidor com imagem nova: usa nova `envio_id`.
- 3a. Mesma chave e mesmo conteúdo: a API devolve o envio existente. Nada é duplicado.
- 3b. Mesma chave e conteúdo diferente: 409 `chave_reutilizada`. Nada é alterado.
- 3c. Chave usada em outra prova: 409. A folha fica **Erro** com "Esta folha já foi enviada para outra prova."
- 3d. `correcoes[]` de quem não tem `correcao.alterar`: 403. A folha fica pendente para o professor conferir na revisão. ⚠ Inferência, não decisão: a proteção contra resultado adulterado é a conferência humana, a imagem original guardada, a nota só no servidor e a auditoria.
- 4a. sha256 diferente: 422 `imagem_divergente`. "A imagem chegou incompleta. Envie de novo." O sistema reenvia uma vez.
- 5a. QR ou aluno diferente do decodificado no servidor: 422 `identificacao_divergente`.
- 5b. Estado ou confiança incoerente com `detectadas`: 422 `leitura_inconsistente`. O evento é registrado.
- 6a. A verificação barata do servidor recusa uma folha que o aparelho aprovou: a folha fica **Recusada** com a mensagem do código, e o usuário verifica a prova manualmente. O evento entra nas métricas (UC-079-25).
- 6b. Versão do motor fora da lista aceita: 422 `motor_desatualizado`. O cliente relê a folha com a versão vigente, sem nova ação do usuário, e reenvia com a mesma `envio_id`. O leitor mostra "O leitor foi atualizado. A folha foi lida de novo." Se o valor de uma questão mudar, ela volta a ser pendência.
- 7a. Correção fechada ou acesso revogado entre o envio e a gravação: o envio fica `recusado` e nada é gravado (UC-079-22).
- 9a. Aplicador: a resposta traz só identificação, estado e aluno, como hoje.

**Regras de negócio:** a API é dona do banco. O resultado do aparelho é uma proposta de leitura. A conferência da leitura é sempre do usuário (UC-079-20). A nota é calculada só no servidor. O autor é o usuário da sessão, e o momento é o do servidor. O envio vai para a fila alta, como hoje (`FilaCartoes`).

**Critérios de aceite:**
- [ ] Um reenvio com a mesma chave e o mesmo conteúdo não cria segundo cartão.
- [ ] Um reenvio com a mesma chave e outro conteúdo recebe 409 e não altera nada.
- [ ] Todo cartão lido no aparelho tem sha256, versão do motor e versão do mapa gravados.
- [ ] Nenhuma nota é aceita do cliente.
- [ ] Os testes do contrato do planejamento §6.6 passam.
- [ ] A resposta ao aplicador não contém nota, gabarito nem imagem.
- [ ] Medição com 1000 usuários simultâneos registrada ([ADR-0052](../../decisoes/0052-medicao-com-1000-usuarios-simultaneos.md)).

**Situação:** Parcial — reaproveita `cartoes-fila.js` (chave estável, `FormData`, 30 s, `sessionStorage` e consulta), `FilaCartoes::receber` (trava por chave, idempotência por UUID, barreira) e `GravadorLeituras`. Hoje a API recebe só a imagem e lê no servidor. Hoje a idempotência devolve o envio existente sem comparar o conteúdo. O contrato com resultado é novo.

---

## UC-079-20 — Tratar leitura com dificuldade (nova imagem, reenvio sem custo ou revisão manual)

**Ator:** Professor, Coordenador, Diretor Escolar ou Administrador da Rede; sistema (Escriba)
**Permissão:** `cartao.enviar` para reenviar; `correcao.alterar` para a revisão manual
**Escopo:** prova no escopo do vínculo ativo
**Pré-condições:** folha lida com dificuldade: imagem ruim, ou o Escriba teve dificuldade (UC-079-11).
**Pós-condições:** nova imagem recebida; ou folha reenviada sem novo crédito; ou folha na revisão manual.
**Tela/rota:** leitor visual (UC-079-14) e revisão da prova (web `correcao.revisao`).

**Regra geral:** o servidor não relê folha e não sorteia amostra. Quem confere a leitura é sempre o usuário. Problema de qualidade da imagem sempre exige que o usuário verifique a prova manualmente. Não há decisão automática.

**Fluxo principal (a): Escriba ativo no aparelho com imagem ruim**
1. O Escriba detecta que a imagem não permite ler a região.
2. O Escriba pede uma nova imagem e/ou a revisão manual do usuário.
3. O usuário captura a nova imagem ou confere a prova manualmente no leitor visual (UC-079-14).

**Fluxo principal (b): modo servidor com dificuldade do Escriba do servidor**
1. O servidor termina a leitura e marca os alunos com problema.
2. O sistema mostra ao usuário a lista desses alunos.
3. O usuário escolhe **Reenviar só os alunos com problema** e/ou **Revisar manualmente**.
4. No reenvio, o sistema não cobra crédito de novo (UC-079-26).
5. A revisão manual é o caminho principal. O usuário confere a folha no leitor visual.

**Fluxos alternativos e de erro:**
- (a)-2a. O usuário não envia nova imagem: a folha segue para a revisão manual.
- (b)-3a. O usuário reenvia e a dificuldade continua: a folha vai para a revisão manual, sem novo crédito.
- (b)-3b. A folha enviada por quem não tem `correcao.alterar` (aplicador) fica pendente para o professor conferir na revisão. ⚠ Inferência, não decisão.

**Regras de negócio:** a nota é sempre calculada no servidor, só sem pendências. A proteção contra resultado adulterado é a conferência humana, a imagem original guardada, a nota só no servidor e a auditoria. Toda dificuldade registra a versão do motor e a versão do mapa. Muitas dificuldades da mesma versão do motor alertam a equipe técnica (UC-079-25).

**Critérios de aceite:**
- [ ] O servidor não relê nenhuma folha e não sorteia amostra.
- [ ] Com imagem ruim no aparelho, o Escriba pede nova imagem e/ou revisão manual.
- [ ] No modo servidor, o usuário reenvia só os alunos com problema, sem nova cobrança de crédito.
- [ ] Todo problema de qualidade da imagem leva à verificação manual do usuário.
- [ ] A revisão manual está disponível em toda folha com dificuldade.
- [ ] Uma folha enviada por quem não tem `correcao.alterar` fica pendente para o professor.
- [ ] A decisão está registrada na [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md).

**Situação:** Não implementado — reaproveitável: a fila de revisão (`CartaoController::revisao`) e o motor do servidor (`NativoOmrDriver`, `omr/ler_cartao.py`).

---

## UC-079-21 — Receber uma folha de aluno que já tem leitura

**Ator:** sistema; Professor, Coordenador, Diretor Escolar ou Administrador da Rede para aplicar ou descartar
**Permissão:** enviar: `cartao.enviar`; aplicar ou descartar: `correcao.alterar`
**Escopo:** prova e aluno no escopo
**Pré-condições:** aluno com cartão `lido` ou `ambiguo` na prova.
**Pós-condições:** nova tentativa `aguardando_confirmacao`; a correção vigente não muda.
**Tela/rota:** no leitor, modal da aplicação. Depois: web `correcao.tentativas`, `correcao.tentativa`, `correcao.tentativa.aplicar|descartar` (FN-047).

**Fluxo principal:**
1. O leitor identifica um aluno que já tem leitura na prova.
2. O leitor mostra o modal "Este aluno já tem leitura" com o texto "A nova folha vai para conferência. A correção atual não muda até alguém aplicar a nova tentativa."
3. O modal oferece **Enviar como nova tentativa** e **Descartar esta folha**.
4. Com **Enviar como nova tentativa**, o servidor cria a tentativa (`HistoricoCartao::propor`).
5. O usuário com `correcao.alterar` aplica ou descarta a tentativa depois (UC-047-03).

**Fluxos alternativos e de erro:**
- 1a. A mesma folha (mesmo sha256) já enviada: o servidor devolve o envio existente. Nenhuma tentativa nova.
- 1b. Aplicador: o modal mostra só "Este aluno já tem folha enviada." com **Enviar mesmo assim** e **Descartar esta folha**. Ele não vê a leitura anterior.
- 4a. Mesmo aluno duas vezes no mesmo lote: o sistema pergunta na segunda folha, com o mesmo modal.
- 4b. Duas folhas do mesmo aluno enviadas ao mesmo tempo, de abas ou aparelhos diferentes: a API cria uma leitura vigente e uma proposta de tentativa, nunca duas leituras vigentes.

**Regras de negócio:** uma correção vigente nunca é sobrescrita sem confirmação e motivo (FN-047). O histórico é append-only e encadeado por hash.

**Critérios de aceite:**
- [ ] Nenhuma folha nova substitui a correção vigente sem aplicação explícita.
- [ ] A tentativa guarda a leitura do aparelho e a versão do motor.
- [ ] O aplicador não vê a leitura anterior.
- [ ] O teste de concorrência no MariaDB nunca produz duas leituras vigentes.

**Situação:** Parcial — reaproveita `HistoricoCartao::propor`, `TentativaCartaoController` e o estado `aguardando_confirmacao` (FN-047). O modal no leitor é novo.

---

## UC-079-22 — Tratar correção fechada ou permissão revogada durante a sessão

**Ator:** sistema; usuário da sessão
**Permissão:** `cartao.enviar`; `correcao.reabrir` para continuar com a correção fechada
**Escopo:** sessão de leitura aberta
**Pré-condições:** sessão aberta; a correção é fechada ou o acesso muda durante a sessão.
**Pós-condições:** nenhuma folha é gravada sem permissão válida; o usuário sabe o que aconteceu com as folhas.
**Tela/rota:** leitor visual e fila. Modal "Correção fechada" (planejamento §7).

**Fluxo principal:**
1. Um envio recebe 403 por correção fechada.
2. O sistema pausa a fila de envio.
3. O sistema mostra o modal "Correção fechada" com a mensagem atual: "A correção desta prova foi fechada por … em …. Para enviar, excluir ou alterar cartões, peça à coordenação para reabrir a correção."
4. O modal oferece **Entendi**.
5. O resumo mostra as folhas não enviadas (UC-079-18).

**Fluxos alternativos e de erro:**
- 1a. Vínculo suspenso, permissão retirada, sessão encerrada (401/419), logout ou troca de vínculo em outra aba: o sistema para a leitura e mostra "Seu acesso a esta prova mudou. A leitura foi interrompida." O leitor apaga a memória e encerra os Workers (planejamento §6.8, regra 7). A sessão Web segue o [ADR-0024](../../decisoes/0024-vinculo-ativo-acompanha-a-sessao.md).
- 1b. Aplicador depois do dia da aplicação: "Esta prova não está mais disponível para aplicação hoje."
- 1c. A correção fecha entre o recebimento e a gravação: a transação de gravação recusa o envio (planejamento §6.6, "Revalidação na gravação").
- 3a. A coordenação reabre a correção: o usuário toca em **Tentar enviar de novo**. A fila retoma com as mesmas chaves.
- 5a. Folhas não enviadas no aparelho (D-8): nada é persistido. As folhas ficam só em memória e são apagadas ao sair. A foto e os dados vão ao servidor e ao banco.

**Regras de negócio:** correção fechada ([08](../../arquitetura/08-regras-de-negocio.md), RN-COR-13). A API revalida espaço, vínculo, `cartao.enviar` e bloqueio na gravação, como o job faz hoje. A leitura no aparelho não dá direito a gravar.

**Critérios de aceite:**
- [ ] Depois do fechamento, nenhuma folha é gravada por quem não tem `correcao.reabrir`.
- [ ] Uma correção fechada entre o envio e a gravação recusa a gravação (teste com fechamento concorrente).
- [ ] Depois da reabertura, o reenvio usa as mesmas chaves e não duplica.
- [ ] Com o acesso revogado, o leitor para e não mostra mais imagens da prova.
- [ ] Depois de logout ou de 403, a inspeção de IndexedDB, Cache Storage, OPFS, `sessionStorage` e Service Worker não encontra imagem, recorte, resposta nem nome de aluno (conferência por texto, Chrome e Firefox).

**Situação:** Parcial — reaproveita o bloqueio de correção fechada (`App\Support\Correcao`), a revalidação no job (`ProcessarCartaoJob`) e a mensagem atual. A pausa da fila, a limpeza da memória e o modal são novos.

---

## UC-079-23 — Atender um aparelho sem capacidade

**Ator:** sistema; usuário da sessão
**Permissão:** `cartao.enviar`
**Escopo:** aparelho do usuário
**Pré-condições:** o UC-079-02 detectou falta de capacidade para o núcleo de leitura ou para a IA local.
**Pós-condições:** o usuário sabe o que pode fazer neste aparelho.
**Tela/rota:** tela 12 do planejamento §7 (componente `x-aviso`).

**Fluxo principal (D-2):**
1. O sistema mostra a mensagem simples do resultado: "Este aparelho suporta o Escriba" ou "Este aparelho não tem suporte ao Escriba". Para o núcleo de leitura, o texto equivalente diz só se o aparelho suporta ou não o leitor.
2. O sistema oferece o modo servidor (UC-079-26). O usuário continua corrigindo pelo app.
3. Se o usuário aceita, o UC-079-26 assume a folha, com o aviso de créditos e de fila.
4. Se o usuário não aceita, o sistema não lê a folha neste aparelho e volta à escolha da entrada.

**Fluxos alternativos e de erro:**
- 1a. Falta só o Escriba (as objetivas funcionam): o aparelho lê as objetivas. As regiões abertas vão ao modo servidor, com 1 crédito por aluno × prova.
- 1b. Aparelho fraco, mas capaz: o sistema avisa "A leitura pode demorar neste aparelho." e continua.
- 2a. Região aberta e usuário sem modo servidor: o usuário ainda digita a transcrição no leitor visual (UC-079-16).

**Regras de negócio:** a mensagem ao usuário é simples e nunca lista recursos, motivos nem o que falta (decisão do responsável em 11/11/2026). O detalhe técnico fica só no registro técnico. O motor do servidor continua existindo. Os requisitos mínimos do aparelho ficam no planejamento. Os do modelo vêm da FN-080. A decisão está na [ADR-0058](../../decisoes/0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md).

**Critérios de aceite:**
- [ ] Todo aparelho sem capacidade recebe a mensagem simples (suporta ou não suporta), sem motivo nem lista de recursos.
- [ ] A mensagem sempre vem com a oferta do modo servidor (UC-079-26).
- [ ] Um aparelho sem capacidade para a IA não baixa o modelo.
- [ ] Uma folha lida no servidor tem origem da leitura "servidor".

**Situação:** Não implementado — o fluxo atual de leitura no servidor (UC-045-02) existe e serve de base para o modo servidor.

---

## UC-079-26 — Corrigir pelo modo servidor (fila e créditos)

**Ator:** Professor, Professor autônomo, Coordenador, Diretor Escolar, Administrador da Rede; Aplicador envia a captura, sem corrigir
**Permissão:** `cartao.enviar`; `leitura.transcrever` para as regiões abertas (D-9); `correcao.alterar` para conferir e corrigir no leitor
**Escopo:** prova no escopo do vínculo ativo; créditos do escopo cobrado
**Pré-condições:** UC-079-01 em andamento; o aparelho não processa (UC-079-23), o lote passou do limite do aparelho (UC-079-07, 4d) ou o usuário escolheu o modo servidor; saldo de créditos suficiente (dependência do sistema de créditos, abaixo).
**Pós-condições:** folha processada no servidor com o mesmo resultado que o aparelho daria; créditos consumidos por correção de prova de aluno (aluno × prova); folha aberta no leitor visual para conferência.
**Tela/rota:** tela H do mockup `mockups/leitor360.html` (`data-tela="sem-ia"`, "Aparelho sem capacidade para o Escriba"); modal de aviso da aplicação ([ADR-0025](../../decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md)). Envio pela rota atual `POST v1/provas/{id}/cartoes`, com o campo `modo = servidor` e sem `leitura` nas regiões que o servidor lê ([planejamento §6.6](planejamento.md#66-api-proposta)). Regras: [planejamento §6.12](planejamento.md#612-modo-servidor-leitura-no-servidor-com-fila-e-créditos).

**Fluxo principal:**
1. O sistema abre o modal de aviso antes do primeiro envio da sessão. O modal tem dois textos exatos:
   - "Os créditos de correção serão consumidos."
   - "A correção não é instantânea. Ela entra numa fila, e você recebe um aviso quando terminar."
2. O usuário confirma o modal. Sem confirmação, nada é enviado.
3. O usuário captura ou escolhe uma foto. O sistema trata cada foto como um envio, com `envio_id` e idempotência próprios.
4. O aparelho decodifica o QR e identifica prova, aluno e página. Ele não executa o núcleo inteiro.
5. O aparelho grava o marcador na imagem (proposta: segmento XMP no JPEG) com prova, aluno, página, versão do mapa e `envio_id`.
6. O sistema envia a imagem. O servidor revalida permissão, escopo, vínculo e a relação aluno × prova × turma.
7. O servidor põe a imagem na fila da prova indicada pelo marcador, sem etapa só para identificar a folha.
8. O app mostra a posição na fila e o tempo estimado. O usuário pode sair da tela.
9. O servidor lê a folha com o mesmo núcleo e, para as regiões abertas, com o mesmo Escriba, nas mesmas versões do aparelho (D-4, D-5).
10. O sistema avisa o usuário ao terminar (FN-057; até ela existir, aviso no app, na tela da prova).
11. O usuário abre a folha no leitor visual e confere (UC-079-14). Ele pode salvar com pendências (UC-079-17).

**Fluxos alternativos e de erro:**
- 1a. O usuário toca em **Cancelar** no modal: nada é enviado, e o sistema volta à escolha da entrada.
- 1b. O aviso aparece de novo a cada sessão do Leitor360.
- 4a. Sem QR legível no aparelho: a imagem vai sem marcador, e o servidor identifica a folha como hoje.
- 5a. Marcador forjado ou adulterado (outra prova, outro aluno): o marcador só encaminha. O servidor revalida e decodifica o QR da imagem. O QR prevalece, e a folha vai para revisão. O marcador nunca muda a prova nem o aluno.
- 6a. Marcador de prova ou aluno fora do escopo do usuário: o servidor recusa a folha com a mensagem do UC-079-09, 2b.
- 6b. A barreira de qualidade recusa a imagem: a folha fica **Recusada** com a mensagem do código. O usuário verifica a prova manualmente. Ela não consome créditos.
- 8a. O usuário sai da tela: a fila continua. Ao voltar, ele vê o estado de cada folha.
- 9a. Prova mista com só o Escriba faltando no aparelho: o aparelho lê as objetivas, e o servidor transcreve as regiões abertas. O crédito é um só por aluno × prova, sem divisão por região.
- 9b. A fila tem falha no servidor: a folha fica **Erro** com "Não foi possível ler esta folha. Capture de novo ou escolha outro arquivo." Ela não consome créditos.
- 9c. O Escriba do servidor teve dificuldade com um aluno: o usuário reenvia só os alunos com problema, sem cobrar crédito de novo, e/ou segue para a revisão manual (UC-079-20).
- 10a. Sem conexão durante o envio: a folha não é enviada, e nada fica guardado no aparelho (D-7, D-8). O sistema mostra "Sem conexão. Esta folha ainda não foi salva."
- 11a. Leitura com dificuldade: segue o UC-079-20 (nova imagem, reenvio sem custo ou revisão manual).
- 11b. O usuário quer corrigir a transcrição: ele digita no leitor visual (UC-079-16).
- 3b. Sem saldo de créditos (confirmado) ou sistema de créditos indisponível: o sistema recusa o envio e mostra "Os créditos de correção acabaram." (texto proposto). O sistema de créditos não existe ainda; ver a dependência nas regras.

**Regras de negócio:**
- **Consumo de créditos:** a unidade é uma correção de prova de um aluno (aluno × prova). Várias páginas do mesmo aluno na mesma prova valem 1 crédito. Não há cobrança por folha nem por região. O preço vem da FN de créditos.
- **Reenvio:** reenviar só os alunos com problema não cobra crédito de novo.
- **Sem saldo:** o envio é recusado (confirmado).
- **Não consome:** o processamento no aparelho; a imagem recusada pela barreira de qualidade; a folha com erro do servidor; o reenvio de aluno com problema.
- **Paridade:** para a mesma imagem, o resultado do servidor é igual ao do aparelho. O conjunto de referência prova isso (UC-079-24).
- **O marcador só encaminha** (confirmado). Se ele diverge do QR, vale o QR e a folha vai para revisão.
- **Dados:** a imagem já vai ao servidor em todo envio (D-12). O modo servidor não envia nada a terceiros.
- **⚠ Dependência:** o sistema de créditos de correção não existe no código nem tem FN. Ele bloqueia as subfases 4.3 e 6.3 ([planejamento §8](planejamento.md#8-riscos-e-decisões-pendentes-do-responsável)).

**Critérios de aceite:**
- [ ] O modal mostra os dois textos exatos e exige confirmação antes do primeiro envio da sessão.
- [ ] Cada foto é um envio com `envio_id` próprio; um reenvio não duplica.
- [ ] O aparelho não executa o núcleo inteiro no modo servidor: só identifica e grava o marcador.
- [ ] Um marcador forjado não muda a prova nem o aluno da folha; o QR prevalece.
- [ ] Sem QR legível, a imagem vai sem marcador e o servidor a identifica.
- [ ] A tela mostra a posição na fila e o tempo estimado, e o usuário pode sair dela.
- [ ] O usuário recebe um aviso quando a folha termina.
- [ ] Para a mesma imagem, o resultado do servidor tem paridade de 100% com o do aparelho, nas mesmas versões.
- [ ] O crédito consome por correção de prova de aluno (aluno × prova); várias páginas do mesmo aluno na mesma prova consomem 1 crédito.
- [ ] O processamento no aparelho e a imagem recusada não consomem.
- [ ] O reenvio só dos alunos com problema não cobra crédito.
- [ ] Sem saldo, o envio é recusado com mensagem.
- [ ] Conferência registrada por texto nas larguras do ADR-0057, temas claro e escuro.

**Situação:** Não implementado — o servidor lê hoje cada foto enviada (UC-045-02) e a fila alta existe (`FilaCartoes`). Não existem o marcador, a posição na fila, o aviso ao terminar, o campo `modo` nem o sistema de créditos (dependência). Mockup aprovado em 11/11/2026 (tela H).

---

## UC-079-27 — Impedir o fechamento da correção com folha pendente

**Ator:** usuário com `correcao.fechar` (perfis conforme o [catálogo de permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md))
**Permissão:** `correcao.fechar`
**Escopo:** prova no escopo do vínculo ativo
**Pré-condições:** prova com a correção aberta; pelo menos uma folha salva com pendência (D-11, UC-079-17).
**Pós-condições:** a correção continua aberta, e o usuário vê quais folhas pedem revisão.
**Tela/rota:** acompanhamento da correção (web `correcao.show`, botão de fechar). Rota web atual `POST /provas/{id}/correcao/fechar` (`CorrecaoController::fechar`, nome `correcao.fechar`). Rota da API atual `POST v1/provas/{id}/correcao/fechar` (`CartaoController::fecharCorrecao`, middleware `permissao:correcao.fechar`). Modal da aplicação com a lista; revisão em web `correcao.revisao`.

**Fluxo principal:**
1. O usuário toca em **Fechar correção** no acompanhamento.
2. O sistema confere a permissão e o escopo.
3. A API confere se a prova tem folha com pendência.
4. A API recusa o fechamento e devolve a lista das folhas pendentes.
5. O sistema abre um modal da aplicação, sem diálogo nativo, com o texto "Esta correção não pode ser fechada. Há folhas com pendência." e a lista das folhas.
6. Cada folha da lista tem o botão **Revisar**, que abre a revisão da prova nessa folha (UC-046-01).
7. Depois que o usuário resolve todas as pendências, o fechamento é aceito como hoje.

**Fluxos alternativos e de erro:**
- 3a. Sem folha pendente: a API fecha a correção como hoje e responde "Correção fechada."
- 3b. Correção já fechada: a API responde como hoje, sem alterar nada.
- 4a. Folha pendente ainda em leitura no modo servidor (UC-079-26): ela conta como pendente. A lista mostra "Em leitura no servidor".
- 4b. Usuário sem `correcao.fechar`: 403, como hoje. O botão não aparece.
- 5a. Lista longa: o modal mostra as primeiras folhas e a contagem total.
- 6a. A folha pendente é de aluno fora do escopo do usuário: ela entra só na contagem, sem nome nem link.

**Regras de negócio:** a trava vale na API, e não só na tela. A pendência é a do glossário deste documento (marcação dupla, marcação fraca ou não lida). Os avisos (em branco) não travam. A mensagem usa modal da aplicação ([ADR-0025](../../decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md)). A reabertura (`correcao.reabrir`) não muda.

**Critérios de aceite:**
- [ ] Com uma folha pendente, a API recusa o fechamento e não grava `correcao_fechada_em`.
- [ ] A recusa vale também para chamada direta à API, sem passar pela tela.
- [ ] O modal lista as folhas pendentes, cada uma com acesso à revisão.
- [ ] Questão em branco não trava o fechamento.
- [ ] Sem pendências, o fechamento continua como hoje.
- [ ] Nenhum diálogo nativo do navegador é usado na recusa.
- [ ] Conferência registrada por texto a 360, 768 e 1280 px, temas claro e escuro.

**Situação:** Parcial — o fechamento existe e não confere pendências: a API grava `correcao_fechada_em` e `correcao_fechada_por` (`CartaoController::fecharCorrecao`), e a tela pede só uma confirmação (`data-correcao-confirmar` em `correcao/show.blade.php`). A trava, a lista e o modal são novos. A marca "com pendência" da folha depende do UC-079-17.

---

## Grupo F — Qualidade do motor

## UC-079-24 — Validar uma versão do motor contra o conjunto de referência antes de publicar

**Ator:** equipe técnica, com decisão do responsável
**Permissão:** não se aplica (processo de publicação do motor)
**Escopo:** rede
**Pré-condições:** nova versão do núcleo de leitura, dos limiares, da barreira ou de um método; conjunto de referência guardado na VM do responsável, com manifesto no Git (D-13).
**Pós-condições:** versão aprovada e vigente; ou versão recusada com o relatório das diferenças.
**Tela/rota:** sem tela. Job `paridade-leitor` na CI e página de autoteste fora da CI (planejamento §6.9).

**Fluxo principal:**
1. A CI baixa o conjunto de referência pela versão do manifesto e confere cada sha256.
2. A CI roda o conjunto em três alvos: servidor, núcleo em Node e núcleo no Chrome headless.
3. A CI compara cada resultado com o instantâneo do servidor (paridade) e com a verdade anotada (acerto).
4. Com paridade de 100% com o instantâneo do servidor e sem queda de acerto em nenhuma categoria, a versão passa na CI.
5. A equipe roda a página de autoteste no Firefox, no Safari iOS e no Chrome Android e registra o resultado por texto.
6. A versão aprovada ganha um identificador e entra no manifesto como vigente.

**Fluxos alternativos e de erro:**
- 1a. Uma folha com sha256 diferente do manifesto: a execução reprova.
- 4a. Um alvo difere do instantâneo numa folha: a versão é recusada. O relatório lista a folha, o alvo e a diferença.
- 4b. Versões diferentes de OpenCV ou NumPy entre aparelho e servidor: a versão é recusada. A D-5 exige as mesmas versões nos dois lados. O relatório lista a diferença de versão. (⚠ Falta confirmar o que o Pyodide empacota.)
- 5a. Falta o registro de um dos 4 navegadores: a versão não é publicada.
- 6a. Defeito descoberto numa versão vigente: a versão sai da lista aceita na hora e do manifesto (planejamento §6.4, regra 10).

**Regras de negócio:** conteúdo mínimo, anotação, armazenamento e regra de dados reais: [planejamento §6.9](planejamento.md#69-conjunto-de-referência). Nenhuma imagem do conjunto entra no Git: o Git guarda só o manifesto (D-13). O servidor e o aparelho usam o mesmo núcleo (D-4), e a CI confere a paridade também para o modo servidor.

**Critérios de aceite:**
- [ ] O conjunto de referência tem fotos reais de cartão impresso (pendência da FN-045, subfase 1.3).
- [ ] A CI reprova uma versão com qualquer diferença de paridade em qualquer alvo.
- [ ] A CI reprova uma versão com acerto menor que a vigente em alguma categoria.
- [ ] Toda versão vigente tem identificador e relatório de aprovação.

**Situação:** Não implementado — existem só folhas sintéticas (`scripts/tests/test_qualidade_omr.py`). Não existe conjunto de fotos reais.

---

## UC-079-25 — Registrar métricas de leitura para ajustar limiares

**Ator:** sistema; equipe técnica para analisar, com decisão do responsável
**Permissão:** coleta: não se aplica; consulta: equipe técnica (sem chave no catálogo de permissões; proposta na 8.1)
**Escopo:** rede
**Pré-condições:** leituras enviadas pelo UC-079-19.
**Pós-condições:** métricas agregadas disponíveis para ajustar limiares (FN-046 R3).
**Tela/rota:** sem tela. Consulta por comando ou relatório técnico (proposta).

**Fluxo principal:**
1. O sistema registra métricas agregadas por versão do motor, classe de aparelho, entrada e dia:
   - versão do motor e do mapa;
   - código da barreira;
   - tempo de leitura;
   - questões destacadas por estado;
   - questões alteradas pelo usuário em relação à leitura;
   - leituras com dificuldade e reenvios (UC-079-20).
2. A equipe agrega as métricas por versão do motor.
3. A equipe propõe novos limiares com base nas decisões reais dos usuários.
4. A nova versão passa pelo UC-079-24 antes de valer.

**Fluxos alternativos e de erro:**
- 1a. Envio de métricas falha: a leitura não é afetada. A métrica é perdida e contada como perdida.
- 2a. Volume baixo de uma versão: o relatório marca "amostra insuficiente".

**Regras de negócio:** as métricas não guardam `cartao_id`, `aluno_id`, `envio_id`, chave de envio, QR, sha256 da imagem, nome, matrícula, imagem, texto transcrito nem horário exato (planejamento §6.8, regra 13). A FN-046 R3 decide os valores dos limiares; o Leitor360 fornece os dados. A divergência atual de limiares é risco do planejamento §8.

**Critérios de aceite:**
- [ ] Um teste valida o esquema e recusa os campos proibidos.
- [ ] O relatório mostra, por versão, a taxa de questões alteradas pelo usuário.
- [ ] Uma mudança de limiar só vale depois de aprovada no UC-079-24.

**Situação:** Parcial — reaproveita `ambiguidade_logs` (decisões dos usuários na revisão) e o planejamento da FN-046 R3. As métricas por aparelho, versão e tempo são novas.

---

## Matriz de cobertura

### (a) Entrada × casos de uso

A disponibilidade por aparelho está no [planejamento §6.3](planejamento.md#63-perfil-de-capacidade-do-aparelho).

| Entrada | Caso de uso | Observação |
|---|---|---|
| Câmera (celular e tablet) | UC-079-03 | Também para o aplicador |
| Webcam (computador) | UC-079-04 | Também para o aplicador |
| Foto de arquivo | UC-079-05 | Também para o aplicador |
| PDF de uma folha | UC-079-06 | Aplicador: não previsto (a confirmar) |
| Lote de PDFs | UC-079-07 | Aplicador: não previsto (a confirmar); acima do limite do aparelho, modo servidor |
| Aparelho sem capacidade | UC-079-23 | Mensagem simples e oferta do modo servidor |
| Modo servidor (fila e créditos) | UC-079-26 | Depende do sistema de créditos (sem FN) |

Casos comuns a todas as entradas: UC-079-08 (barreira), UC-079-09 (QR), UC-079-14 a UC-079-18 (leitor visual), UC-079-19 a UC-079-22 e UC-079-27 (servidor e integridade), UC-079-24 e UC-079-25 (qualidade do motor).

### (b) Tipo de documento × método

| Tipo de documento | `objetiva-bolhas` (OpenCV, no aparelho) | `transcricao-manuscrita` (IA local, FN-080) | Mapa de respostas | Situação |
|---|---|---|---|---|
| Cartão-resposta à parte | UC-079-10, UC-079-15 | — | Derivado do cartão (N, K, `layout.grade()`) | Leitura existe no servidor (FN-045); no aparelho, proposta |
| Página da prova é o gabarito | UC-079-10, UC-079-13, UC-079-15 | — | FN-043 (por página) | Depende da FN-043 (QR novo com página, D-10) |
| Prova aberta | — | UC-079-11, UC-079-16 | FN-043 | Depende da FN-043 e da FN-080 |
| Prova mista | UC-079-10, UC-079-12, UC-079-15 | UC-079-11, UC-079-12, UC-079-16 | FN-043 | Depende da FN-043 e da FN-080 |
| Layout de terceiros (futuro) | A FN-048 decide | — | A FN-048 decide | Candidata |

---

## Histórico de versões

| Versão | Data | Mudança | Por |
|---|---|---|---|
| 1 | 2026-11-11 | Casos de uso iniciais | lucasgenial |
| 2 | 2026-11-11 | Decisões D-1 a D-13, modo servidor (UC-079-26), trava de fechamento (UC-079-27) | lucasgenial |
| 2.1 | 2026-11-11 | Correções do responsável: crédito por correção de prova do aluno; sem releitura pelo servidor; reenvio sem custo; qualidade exige revisão manual; marcador confirmado | lucasgenial |
