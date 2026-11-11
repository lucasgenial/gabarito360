# Estudo — Arquitetura de execução do Leitor360 e do Escriba

**Data:** 2026-11-11, por lucasgenial
**Situação:** aprovado pelo responsável em 11/11/2026 (E-1 a E-6). As decisões estão no [ADR-0060](../../decisoes/0060-arquitetura-de-execucao-da-leitura.md).
**Funcionalidades:** [FN-079 Leitor360](planejamento.md) (D-4, D-5, §6.4) e [FN-080 Escriba](../080-ia-local-transcricao/planejamento.md).

Marcas: **[F]** fato com fonte; **[M]** medido nesta pesquisa; **[I]** inferência, a medir na prova de viabilidade.

## 1. A pergunta

A leitura roda no aparelho para poupar o servidor. Quando o aparelho não dá conta, o servidor processa com fila e créditos (§6.12). A regra é a **paridade**: o que roda no aparelho roda igual no servidor. O estudo responde com que tecnologia isso roda no celular, no computador e no servidor, e qual é o custo em cada um.

## 2. Núcleo de leitura das objetivas (OMR)

### 2.1 O que o código usa hoje [M]

`apps/api/omr/` (`ler_cartao.py`, `qualidade.py`, `layout.py`) usa do OpenCV: `cvtColor`, `GaussianBlur`, `medianBlur`, `threshold` (OTSU), `divide`, `morphologyEx`, `dilate`, `findContours`, `approxPolyDP`, `arcLength`, `convexHull`, `isContourConvex`, `contourArea`, `moments`, `boundingRect`, `drawContours`, `getPerspectiveTransform`, `warpPerspective`, `resize`, `Laplacian`, `QRCodeDetector`. Do NumPy, só o básico. Nada é exclusivo do OpenCV 5. O `reportlab` só gera PDF e não entra na leitura.

### 2.2 Opções

| Opção | Como fica a paridade | Custo |
|---|---|---|
| **A. Pyodide no aparelho e o mesmo Pyodide no servidor (Node)** | Literal: o mesmo binário WASM e o mesmo lockfile nos dois lados [M: funciona em Node] | O código Python atual roda sem reescrita. No servidor, de 6 a 8 vezes mais CPU que o nativo |
| B. Pyodide no aparelho e Python nativo no servidor com as mesmas versões | Mesma versão, binário diferente (o nativo tem SIMD e threads). Sem garantia de resultado idêntico [I] | Barato no servidor |
| C. OpenCV.js no aparelho e no servidor (Node) | Literal, mas com a versão 4.13 e com o leitor reescrito em JS | Reescrever o leitor |
| D. C++ compilado (WASM no navegador, nativo no servidor e no app) | Mesma fonte e mesma tag do OpenCV nos três alvos | O melhor desempenho; de 2 a 4 semanas de quem domina CMake e Emscripten, e três cadeias de build [I] |

### 2.3 Números do Pyodide

- Versão estável 314.0.7 (14/09/2026), com `numpy` 2.4.6 e `opencv-python` 4.11.0.86 [F]. Todas as funções do §2.1 existem nesse build [M].
- O OpenCV do Pyodide vem **sem SIMD e sem threads** [M].
- Primeiro download com compressão: cerca de **20 MB** (runtime 3,4; stdlib 2,5; numpy 2,9; opencv 10,7) [M]. Depois, fica no cache do navegador.
- Em Node, num contêiner de 2 vCPU: inicialização em 2,7 s, carga de numpy e opencv em 1,4 s, cerca de 126 MB de memória, e **0,43 s por cartão** sintético de 2000×1500 (o nativo leva de 55 a 70 ms) [M]. Falta medir com foto real.
- No celular, o primeiro acesso deve levar de 5 a 15 s por causa da rede e da compilação [I].
- O pacote do OpenCV usa `eval()` para carregar módulos [M]. O site precisa permitir `'unsafe-eval'` e `'wasm-unsafe-eval'` onde o Pyodide roda [I, a testar].

### 2.4 Limites dos aparelhos

- **iPhone (Safari):** só cerca de 300 MB de memória para WASM são confiáveis, e esse limite cai a cada recarga da página (bug do WebKit) [F]. A foto precisa ser reduzida antes da leitura (D-12 já reduz a 2400 px), e só uma página fica em memória por vez.
- **Android (Chrome):** o limite é bem maior (um Redmi chegou a 4 GB) [F].
- **Computador:** sem limite prático para uma folha. No lote de PDF, o limite vem do tempo (D-3).
- **Leitor de QR nativo (`BarcodeDetector`):** só no Chrome Android é confiável [F]. Ele serve como atalho para a identificação do modo servidor, nunca como única opção.

### 2.5 Recomendação para as objetivas [I]

**Opção A.** Ela é a única que entrega a paridade que o responsável pediu sem reescrever o leitor:

1. No aparelho, o Pyodide 314.0.7 roda num Web Worker dedicado, com a política de segurança (CSP) liberada só para ele.
2. No servidor, a leitura do modo servidor roda o **mesmo Pyodide em Node**, com o mesmo lockfile. O Python nativo fica só para gerar PDF (`reportlab`).
3. O `requirements.txt` da leitura passa a fixar as versões do Pyodide (`opencv-python-headless==4.11.0.86`, `numpy==2.4.6`) enquanto o Python nativo existir. Isso **volta** do OpenCV 5 para o 4.11. O código não usa nada do 5 [M].
4. O custo maior de CPU no servidor é aceitável: o servidor só lê no modo servidor, e o Xeon E5-2650 v4 (12 núcleos) roda vários processos em paralelo, a cerca de 0,5 s por cartão cada [I].
5. Se o celular de entrada falhar na prova de viabilidade, a evolução é a opção D, que também serve ao app nativo.

## 3. Escriba (respostas abertas)

### 3.1 Modelo-base

- **Qwen3.5-0.8B** (mar/2026, Apache-2.0, visão nativa): OCRBench 79,1; cerca de 533 MB em Q4 [F]. Há os tamanhos 2B, 4B e 9B [F].
- A família anterior, Qwen3-VL-2B, pesa cerca de 1,1 GB em Q4 [F].
- Há modelos dedicados a OCR: PaddleOCR-VL 0,9B; e TexTeller, para fórmulas manuscritas [F].
- Nenhum modelo pequeno tem medição pública em letra de aluno em português [I]. Quem decide é o conjunto de referência da subfase 0.2 da FN-080.

### 3.2 Onde roda

| Lugar | Caminho A (ONNX) | Caminho B (GGUF) |
|---|---|---|
| Navegador | transformers.js v4 com WebGPU; existe ONNX oficial do Qwen3.5-0.8B com imagem [F] | wllama (llama.cpp em WASM), WebGPU desde a v3.1; no Safari e no Firefox, fica bem mais lento [F] |
| Servidor sem GPU | o mesmo transformers.js em Node (ONNX Runtime CPU) | `llama-server` com `--mmproj` [F] |
| App nativo (FN-010) | ONNX Runtime Mobile | llama.cpp |

- **WebGPU:** Chrome Android 121 ou mais novo (Android 12+, GPU Qualcomm ou ARM); Safari no iOS/macOS 26; Firefox no Windows e no Mac ARM, mas ainda não no Android [F].
- **Limites no navegador:** 2 GB por arquivo e 4 GB no total para o WASM de 32 bits [F]. O 0.8B quantizado (cerca de 0,7 a 1 GB) cabe [I].
- **Servidor:** no Xeon E5 v4 (AVX2, sem GPU), o 0.8B em Q4 deve levar de **10 a 20 s por região** de 200 tokens, com menos de 2 GB por processo [I, sem benchmark publicado].

### 3.3 Paridade do Escriba (atenção)

O mesmo arquivo de pesos **não garante a mesma saída** no WebGPU do aparelho e na CPU do servidor. Os cálculos arredondam diferente, e uma escolha de palavra pode mudar quando duas opções empatam [I]. Já houve diferença de qualidade de OCR entre runtimes no Qwen2.5-VL [F]. Proposta de definição de paridade para o Escriba:

- o mesmo arquivo de modelo, com hash, nos dois lados;
- o mesmo pré-processamento da imagem e o mesmo prompt;
- decodificação gulosa, sem sorteio;
- uma meta medida: taxa de transcrições idênticas entre aparelho e servidor, e uma tolerância de erro por caractere (CER) fixada pelo responsável.

### 3.4 "Este aparelho suporta o Escriba" [I]

O aparelho suporta o Escriba quando passa nos quatro testes:

1. Tem WebGPU, e o navegador entrega um adaptador.
2. Os limites de memória da GPU e o recurso `shader-f16` comportam o modelo.
3. Há mais de 1 GB livre para o cache.
4. No primeiro uso, transcreve uma imagem de referência embutida dentro do tempo-limite (exemplo: 30 s), com a saída esperada.

O passo 4 mede o desempenho e já confere a paridade naquele aparelho. A tela continua só com "Este aparelho suporta o Escriba" ou "Este aparelho não tem suporte ao Escriba".

### 3.5 Recomendação para o Escriba [I]

- Modelo-base **Qwen3.5-0.8B**, com o 2B de reserva se o ajuste não atingir a meta.
- Quantização: decoder em int4 e codificador de visão em fp16 ou int8. O download fica entre 0,7 e 1 GB, baixado uma vez como "recursos do Escriba".
- **Caminho A (ONNX + transformers.js)**, com o mesmo código JavaScript de pré-processamento no navegador e no servidor (Node). O caminho B (GGUF + llama.cpp) é a reserva, se a exportação do modelo ajustado para ONNX falhar ou se a CPU do servidor ficar lenta demais.
- Antes da subfase 1.x, conferir se saiu um modelo pequeno mais novo da família.

## 4. Quem roda o quê

| Aparelho | Objetivas (OMR) | Escriba |
|---|---|---|
| Celular de entrada (iPhone antigo, Android com 3 GB) | No aparelho, se passar na prova de viabilidade; senão, modo servidor | Modo servidor |
| Celular intermediário Android (Chrome 121+, Android 12+) | No aparelho | No aparelho, se passar no teste do §3.4 |
| iPhone com iOS 26 | No aparelho, com a foto reduzida | No aparelho, se passar no teste do §3.4 |
| Notebook e desktop | No aparelho, inclusive o lote de PDF até o limite (D-3) | No aparelho, com WebGPU |
| Servidor (Xeon E5-2650 v4, 16 GB, sem GPU) | Pyodide em Node, vários processos | transformers.js em Node; capacidade limitada (FN-080 §8.3) |

## 5. Prova de viabilidade (subfase 1.1 da FN-079 e 1.x da FN-080)

Medir, com fotos reais do conjunto de referência:

1. O primeiro carregamento e o segundo (com cache), num iPhone com iOS 17 ou mais novo, num iPhone com iOS 26 e num Android de entrada, em 4G.
2. O pico de memória por aba, com e sem redução da foto, e recargas seguidas no iPhone.
3. O tempo por cartão no celular, no notebook e no Node do servidor.
4. A taxa de sucesso do leitor de QR.
5. A igualdade das respostas das objetivas entre aparelho e servidor, em 50 ou mais fotos.
6. A CSP mínima que funciona com o Pyodide isolado no Worker.
7. No Escriba: CER e erro de fórmula por quantização e runtime; taxa de saídas idênticas entre aparelho e servidor; tempo por região em cada aparelho e no Xeon; tamanho e tempo do download; sucesso da exportação do modelo ajustado.

## 6. Decisões (aprovadas em 11/11/2026)

| Item | Recomendação |
|---|---|
| E-1 Núcleo das objetivas | Opção A: Pyodide no aparelho e o mesmo Pyodide no servidor (Node) |
| E-2 Versões | OpenCV 4.11.0.86 e NumPy 2.4.6 (as do Pyodide), voltando do OpenCV 5 |
| E-3 Segurança do site | Liberar `'unsafe-eval'` e `'wasm-unsafe-eval'` só no Worker do leitor |
| E-4 Modelo-base do Escriba | Qwen3.5-0.8B, com o 2B de reserva |
| E-5 Runtime do Escriba | Caminho A (ONNX + transformers.js no navegador e no Node), com o B de reserva |
| E-6 Paridade do Escriba | Mesmo arquivo com hash, mesmo pré-processamento, decodificação gulosa, e meta medida de saídas idênticas e de CER, porque igualdade total entre GPU e CPU não é garantida |

## 7. Fontes

- Pyodide: https://pyodide.org/en/stable/usage/packages-in-pyodide.html ; https://pyodide.org/en/stable/project/changelog.html ; https://pyodide.org/en/stable/usage/index.html ; https://cdn.jsdelivr.net/pyodide/v314.0.7/full/pyodide-lock.json
- OpenCV.js: https://docs.opencv.org/4.x/d4/da1/tutorial_js_setup.html ; https://docs.opencv.org/4.13.0/opencv.js
- Memória no iOS: https://bugs.webkit.org/show_bug.cgi?id=269777
- Recursos do navegador: https://caniuse.com/mdn-api_barcodedetector ; https://web-platform-dx.github.io/web-features-explorer/features/wasm-simd ; https://web-platform-dx.github.io/web-features-explorer/features/wasm-threads ; https://web-platform-dx.github.io/web-features-explorer/features/offscreen-canvas ; https://sqlite.org/wasm/doc/tip/csp.md
- Versões no PyPI: https://pypi.org/project/opencv-python-headless/4.11.0.86/ ; https://pypi.org/project/numpy/2.4.6/
- Qwen3.5: https://artificialanalysis.ai/articles/qwen3-5-small-models ; https://huggingface.co/Qwen/Qwen3.5-0.8B ; https://huggingface.co/unsloth/Qwen3.5-0.8B-GGUF ; https://huggingface.co/onnx-community/Qwen3.5-0.8B-ONNX-OPT ; https://huggingface.co/spaces/webml-community/Qwen3.5-WebGPU
- Qwen3-VL: https://huggingface.co/Qwen/Qwen3-VL-4B-Instruct ; https://huggingface.co/unsloth/Qwen3-VL-2B-Instruct-GGUF
- Modelos OCR: https://huggingface.co/blog/ocr-open-models ; https://huggingface.co/blog/lightonai/lightonocr ; https://arxiv.org/abs/2508.09220v1
- Runtimes: https://huggingface.co/blog/transformersjs-v4 ; https://cdn.jsdelivr.net/npm/@wllama/wllama@3.8.1/README.md ; https://onnxruntime.ai/docs/tutorials/web/large-models.html ; https://web.dev/blog/webgpu-supported-major-browsers ; https://github.com/ggml-org/llama.cpp/blob/master/docs/multimodal.md ; https://github.com/ggml-org/llama.cpp/issues/16334 ; https://huggingface.co/blog/openvino-vlm
