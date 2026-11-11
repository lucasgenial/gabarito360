---
id: ADR-0060
titulo: Arquitetura de execução da leitura (objetivas e Escriba) no aparelho e no servidor
estado: Aceita
substituida_por: []
data: 2026-11-11
afeta_funcionalidades: [FN-079, FN-080]
afeta_caminhos: ["apps/api/omr/**", "apps/web/**", "apps/api/**"]
verificacao: revisao-ia
regras: []
agents_secao: Planejamento
agents_ordem: 61
agents_resumo:
  - "As objetivas são lidas pelo Pyodide (versão fixa, hoje 314.0.7) num Web Worker do navegador e pelo mesmo Pyodide em Node no servidor, com o mesmo lockfile. A leitura usa OpenCV 4.11.0.86 e NumPy 2.4.6, as versões do Pyodide. O Python nativo fica só para gerar PDF."
  - "A CSP libera `'unsafe-eval'` e `'wasm-unsafe-eval'` só no Worker do leitor, nunca na página inteira."
  - "O Escriba parte do Qwen3.5-0.8B (reserva: 2B), em ONNX, com transformers.js no navegador (WebGPU) e no servidor (Node). O mesmo arquivo de modelo, com hash, roda nos dois lados."
  - "Paridade do Escriba: mesmo arquivo, mesmo pré-processamento, mesmo prompt e decodificação gulosa, com meta medida de saídas idênticas e de CER. Igualdade total entre GPU e CPU não é exigida."
---
# ADR-0060 — Arquitetura de execução da leitura (objetivas e Escriba) no aparelho e no servidor

**Data:** 2026-11-11
**Estado:** Aceita
**Decisor:** lucasgenial

## Contexto

O [ADR-0058](0058-leitura-no-aparelho-com-paridade-e-servidor-com-creditos.md) decidiu que a leitura roda no aparelho e que o servidor processa no modo servidor, com paridade total. Faltava escolher a tecnologia de cada lado. O [estudo da arquitetura de execução](../funcionalidades/079-leitor360/estudo-arquitetura-execucao.md) comparou as opções com fontes e medições: Pyodide, OpenCV.js e C++ para as objetivas; modelos pequenos e runtimes (ONNX e GGUF) para o Escriba. Ele também mediu o custo no celular, no computador e no servidor (Xeon E5-2650 v4, 16 GB, sem GPU).

## Decisão

O responsável aprovou as decisões E-1 a E-6 do estudo em 11/11/2026.

1. **E-1 Núcleo das objetivas.** O Pyodide roda o código Python atual de leitura (`ler_cartao.py`, `qualidade.py`, `layout.py`) num Web Worker do navegador. No servidor, o modo servidor roda o mesmo Pyodide em Node, com o mesmo lockfile. A versão do Pyodide é fixa e só muda com o conjunto de referência aprovado (UC-079-24).
2. **E-2 Versões.** A leitura usa OpenCV 4.11.0.86 e NumPy 2.4.6, que são as versões do Pyodide 314.0.7. Enquanto existir leitura em Python nativo, ela fixa essas mesmas versões. O código não usa nada exclusivo do OpenCV 5. O `reportlab` e a geração de PDF continuam em Python nativo.
3. **E-3 Segurança do site.** A política de segurança (CSP) libera `'unsafe-eval'` e `'wasm-unsafe-eval'` só no Worker do leitor, servido com cabeçalho próprio. A página continua com a CSP atual (ADR-0023).
4. **E-4 Modelo-base do Escriba.** Qwen3.5-0.8B (Apache-2.0), com o Qwen3.5-2B de reserva se o ajuste não atingir a meta. Antes da subfase 1.x da FN-080, a equipe confere se saiu um modelo pequeno mais novo da família.
5. **E-5 Runtime do Escriba.** Modelo em ONNX (decoder int4, codificador de visão fp16 ou int8), com transformers.js no navegador (WebGPU) e em Node no servidor (ONNX Runtime CPU). A reserva é GGUF com llama.cpp (wllama no navegador, `llama-server` no servidor), se a exportação do modelo ajustado para ONNX falhar ou se a CPU do servidor ficar lenta demais. O app nativo (FN-010) segue o formato escolhido.
6. **E-6 Paridade do Escriba.** Paridade é: o mesmo arquivo de modelo, com hash; o mesmo pré-processamento de imagem, em código JavaScript compartilhado; o mesmo prompt; e decodificação gulosa. A meta é medida no conjunto de referência: taxa de transcrições idênticas entre aparelho e servidor e tolerância de CER, fixadas na subfase 1.x da FN-080. Igualdade total entre a GPU do aparelho e a CPU do servidor não é exigida, porque os cálculos arredondam diferente.
7. **"Este aparelho suporta o Escriba"** quando o aparelho tem WebGPU com adaptador, limites de GPU e `shader-f16` suficientes, mais de 1 GB livre para o cache, e transcreve a imagem de referência embutida dentro do tempo-limite, com a saída esperada. A tela mostra só a mensagem simples.

## Consequências

- O servidor gasta de 6 a 8 vezes mais CPU por cartão do que o OpenCV nativo (cerca de 0,4 s contra 0,06 s, medido em 2 vCPU). Isso vale só no modo servidor e cabe no Xeon com vários processos.
- O primeiro acesso ao leitor baixa cerca de 20 MB, e os recursos do Escriba baixam entre 0,7 e 1 GB, uma vez. Os dois ficam no cache do navegador.
- No iPhone, só cerca de 300 MB de memória para WASM são confiáveis. A foto reduzida (D-12) e uma página por vez em memória são obrigatórias.
- O `apps/api/omr/requirements.txt` volta para OpenCV 4.11.0.86 e NumPy 2.4.6 na subfase 1.1 da FN-079, com os testes de regressão do OMR.
- Se o celular de entrada falhar na prova de viabilidade (subfase 1.1), a evolução é o núcleo em C++ compilado (WASM, nativo e app). Essa troca exige novo ADR.
- ⚠ Os tempos no celular e no Xeon e o comportamento da CSP são inferências até a prova de viabilidade. A subfase 1.1 registra as medições.
- Esta decisão não substitui ADR anterior. Ela detalha o ADR-0058.
