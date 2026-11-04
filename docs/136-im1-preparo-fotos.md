# IM1 — preparação e armazenamento de retratos

Planejamento revisado em04/11/2026 após teste real do responsável no celular. PF1-C entregou avatar/foto, mas o seletor rejeita o original acima de2MB e não permite enquadramento. A correção429 segue separada no PR165. IM1 acrescenta preparação explícita da foto para usuário/aluno, sem alterar identidade, permissões ou dados acadêmicos. Homologação primeiro; produção depende de autorização própria.

| Bloco interno | Entrega | Estado |
|---|---|---|
| A | Diagnóstico, orçamento e desenho de interação | Estudado; fluxo aprovado previamente por UI/UX e Product Design |
| B | Enquadramento circular, zoom, rotação, cancelamento e compactação | Implementado; visual aprovado pelo responsável, validação final em andamento |
| C | HEIC/HEIF com conversor controlado e limites de recursos | Implementado; amostra pública convertida no Chrome |
| D | RAW/DNG e compatibilidade real de aparelhos | Prévia JPEG incorporada de DNG implementada; teste sintético. Sem revelação RAW universal ou certificação de aparelho |
| E | Orçamento de armazenamento, reprocessamento e ciclo de substituição | Implementado; testes de peso, orientação e saída privada aprovados |
| F | Testes, revisão visual independente, CI e homologação | Testes locais aprovados; fechamento em andamento |

## Fluxo aprovado previamente

Dentro do modal atual: Tirar foto/Escolher arquivo → Preparando foto → enquadramento com máscara circular, arrastar/teclas, roda do mouse/pinça e rotação fina → Salvar imagem. Aluno novo mantém Usar esta imagem e persistência junto ao cadastro. Sem etapa de confirmação redundante. Cancelar preserva a imagem anterior; erro de envio conserva a edição. Alvos44px, controles rotulados, rolagem fora da área de gesto e botões acessíveis no celular.

Após revisão holística de UI/UX e feedback expresso do responsável, a edição oculta automaticamente Avatar/Foto, galeria e ações de origem. Exibe somente orientação de gestos, imagem sem moldura/traço circular, máscara externa suave, rotação e ações finais. Trocar foto revela as opções de origem sem descartar o enquadramento ao cancelar o seletor. O responsável removeu atalhos90°, Restaurar e slider de zoom, substituiu o campo numérico por texto “Rotação:5,5°” junto ao slider e aprovou o visual em04/11/2026. A revisão anterior foi considerada excessivamente fragmentada e não é usada como aprovação desta versão.

Rotação de−180° a+180° em passos de0,5°, com texto em pt-BR, aria-valuetext e teclado. Zoom por roda/pinça e +/− com área focada; setas movem, atalhos do navegador são preservados. Cropper.js2.3.1 MIT é o motor, em arquivo local/versionado; interface própria inspirada no cuidado de interação do Pintura, sem SDK/licença paga ou ativos proprietários. A geometria compensa escala/posição na rotação para cobrir o recorte; guarda de no máximo3× o enquadramento mínimo. Girar não revela áreas vazias.

## Contrato de processamento

Priorizar preparação local no navegador para JPG/PNG/WebP: orientação, redução de resolução, enquadramento e geração do arquivo final antes de transmitir. O limite2MB da API é uma defesa, não o tamanho esperado do retrato nem o limite inicial de uma foto comum do celular. Definir separadamente limites de entrada, megapixels, tempo e memória; recusar arquivos excessivos com orientação. Conversores adicionais devem ser carregados apenas quando necessários e mantidos com versão, licença, origem e hash; sem enviar fotos a serviços externos.

Arquivo de entrada e formato de armazenamento são contratos distintos. HEIC/HEIF exige decodificador próprio quando o navegador não o lê. RAW inclui formatos diferentes, como DNG, e pode precisar de LibRaw/preview incorporada, memória elevada ou codecs próprios; suporte somente após testes dos formatos efetivamente anunciados. Nome/extensão/MIME não comprovam conteúdo. Não aumentar indiscriminadamente upload, memória ou tempo do processo PHP para aceitar RAW.

Saída proposta: WebP quadrado384×384, máscara circular apenas na interface, qualidade adaptativa para alvo96KiB e teto128KiB. Uma imagem sintética de ruído384×384 mediu90362bytes com qualidade72 no GD local; é ensaio de orçamento, não média de retratos nem garantia de todas as imagens. Teto128KiB significa cerca de12,2GiB para100mil retratos ativos, antes de backups, réplicas e metadados. Medir imagens de teste e revisar legibilidade antes da aprovação.

Servidor deve validar novamente conteúdo, dimensões e peso; recodificar para remover metadados e manter saída previsível. Armazenar arquivos privados e apenas referência/versão no banco, sem originais ou base64 em tabelas/logs. Guardar novo arquivo antes de alterar referência, limpar novo em falha de transação e remover anterior somente após sucesso. Limpeza de órfãos deve ser reconciliada e auditada; nenhuma exclusão em massa é autorizada por este plano. Fotos e banco entram no mesmo plano de backup/restauração; acesso continua autenticado e por alcance.

## Verificação

Fotos grandes válidas e orientações EXIF, corte/zoom/giro, resultado fiel ao círculo, peso/dimensões finais, cancelamento/preparação obsoleta, erros que preservam a edição e a imagem salva, envio duplicado, dados pessoais fora de logs, limites de memória/pixels/tempo, arquivo inválido, usuário/aluno/novo cadastro, teclado/touch, desktop/mobile. HEIC e RAW exigem fixtures não pessoais, licenças registradas e aparelho real; não declarar conferência física com emulação responsiva.

Referências técnicas: [Canvas/toBlob](https://developer.mozilla.org/en-US/docs/Web/API/HTMLCanvasElement/toBlob), [createImageBitmap/orientação](https://developer.mozilla.org/en-US/docs/Web/API/Window/createImageBitmap), [libheif](https://github.com/strukturag/libheif), [LibRaw e recursos](https://www.libraw.org/docs/API-notes.html). As medidas e os limites propostos são decisões técnicas do produto, sujeitas aos testes desta entrega.

## Evidência de implementação

Entradas locais até32MB/48MP; imagem redimensionada para edição de até1600px, saída384×384, alvo96KiB/teto128KiB. Se o navegador não codifica WebP, envia JPEG compacto e a API reprocessa em WebP. Não amplia o limite2MB da API. HEIC usa heic-to1.6.7/libheif1.23.7 em worker descartável com prazo25s e conferência de dimensões antes do quadro RGBA; pacote só é carregado nessa seleção. DNG lê uma prévia JPEG delimitada e validada; não desenvolve o mosaico RAW. Cancelamento/reseleção encerra worker, timer e promessa; resultados obsoletos não substituem a escolha atual.

Prévia QA isolada, pessoas e retratos fictícios: original JPEG de10641411bytes preparado e enviado comoWebP384×384 de16478bytes após rotação; amostra HEIC oficial do libheif enviada comoWebP33430bytes. Valores são amostras, não taxa média garantida. Roda do mouse e pinça de dois dedos exercitadas via Chrome/CUA; pinça em emulação, sem alegar teste de câmera física. Erro503 controlado preservou12,5°, arquivo e enquadramento para segunda tentativa bem-sucedida, sem selecionar novamente.

Testes: API de imagem/orçamento e regressões PF1; Web161 aprovados/1542asserções; Node10 aprovados para geometria, limites de codecs e descarte/cancelamento. O BFF mantém429 e Retry-After com mensagem em português quando há excesso real. CI, recibos e publicação exclusiva na homologação serão registrados no PR de fechamento; produção permanece fora do escopo.
