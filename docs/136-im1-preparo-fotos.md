# IM1 — preparação e armazenamento de retratos

Planejamento revisado em04/11/2026 após teste real do responsável no celular. PF1-C entregou avatar/foto, mas o seletor rejeita o original acima de2MB e não permite enquadramento. A correção429 segue separada no PR165. IM1 acrescenta preparação explícita da foto para usuário/aluno, sem alterar identidade, permissões ou dados acadêmicos. Homologação primeiro; produção depende de autorização própria.

| Bloco interno | Entrega | Estado |
|---|---|---|
| A | Diagnóstico, orçamento e desenho de interação | Estudado; fluxo aprovado previamente por UI/UX e Product Design |
| B | Enquadramento circular, zoom, rotação, cancelamento e compactação | Em implementação |
| C | HEIC/HEIF com conversor controlado e limites de recursos | Em estudo técnico e validação de fixtures |
| D | RAW/DNG e compatibilidade real de aparelhos | Delimitar por formato/codec e amostras; não prometer RAW universal |
| E | Orçamento de armazenamento, reprocessamento e ciclo de substituição | Definido abaixo; verificar em código/testes |
| F | Testes, revisão visual independente, CI e homologação | Pendente dos blocos anteriores |

## Fluxo aprovado previamente

Dentro do modal atual: Tirar foto/Escolher arquivo → Preparando foto → enquadramento com máscara circular, arrastar/teclas, zoom e giro90° → Salvar imagem. Aluno novo mantém Usar esta imagem e persistência junto ao cadastro. Sem etapa de confirmação redundante. Cancelar preserva a imagem anterior; erro de envio conserva a edição quando possível. Alvos44px, controles rotulados, rolagem fora da área de gesto e botões acessíveis no celular. Aprovação visual final requer evidências novas, não reutiliza o aceite anterior como prova do recorte.

## Contrato de processamento

Priorizar preparação local no navegador para JPG/PNG/WebP: orientação, redução de resolução, enquadramento e geração do arquivo final antes de transmitir. O limite2MB da API é uma defesa, não o tamanho esperado do retrato nem o limite inicial de uma foto comum do celular. Definir separadamente limites de entrada, megapixels, tempo e memória; recusar arquivos excessivos com orientação. Conversores adicionais devem ser carregados apenas quando necessários e mantidos com versão, licença, origem e hash; sem enviar fotos a serviços externos.

Arquivo de entrada e formato de armazenamento são contratos distintos. HEIC/HEIF exige decodificador próprio quando o navegador não o lê. RAW inclui formatos diferentes, como DNG, e pode precisar de LibRaw/preview incorporada, memória elevada ou codecs próprios; suporte somente após testes dos formatos efetivamente anunciados. Nome/extensão/MIME não comprovam conteúdo. Não aumentar indiscriminadamente upload, memória ou tempo do processo PHP para aceitar RAW.

Saída proposta: WebP quadrado384×384, máscara circular apenas na interface, qualidade adaptativa para alvo96KiB e teto128KiB. Uma imagem sintética de ruído384×384 mediu90362bytes com qualidade72 no GD local; é ensaio de orçamento, não média de retratos nem garantia de todas as imagens. Teto128KiB significa cerca de12,2GiB para100mil retratos ativos, antes de backups, réplicas e metadados. Medir imagens de teste e revisar legibilidade antes da aprovação.

Servidor deve validar novamente conteúdo, dimensões e peso; recodificar para remover metadados e manter saída previsível. Armazenar arquivos privados e apenas referência/versão no banco, sem originais ou base64 em tabelas/logs. Guardar novo arquivo antes de alterar referência, limpar novo em falha de transação e remover anterior somente após sucesso. Limpeza de órfãos deve ser reconciliada e auditada; nenhuma exclusão em massa é autorizada por este plano. Fotos e banco entram no mesmo plano de backup/restauração; acesso continua autenticado e por alcance.

## Verificação

Fotos grandes válidas e orientações EXIF, corte/zoom/giro, resultado fiel ao círculo, peso/dimensões finais, cancelamento/preparação obsoleta, erros que preservam a edição e a imagem salva, envio duplicado, dados pessoais fora de logs, limites de memória/pixels/tempo, arquivo inválido, usuário/aluno/novo cadastro, teclado/touch, desktop/mobile. HEIC e RAW exigem fixtures não pessoais, licenças registradas e aparelho real; não declarar conferência física com emulação responsiva.

Referências técnicas: [Canvas/toBlob](https://developer.mozilla.org/en-US/docs/Web/API/HTMLCanvasElement/toBlob), [createImageBitmap/orientação](https://developer.mozilla.org/en-US/docs/Web/API/Window/createImageBitmap), [libheif](https://github.com/strukturag/libheif), [LibRaw e recursos](https://www.libraw.org/docs/API-notes.html). As medidas e os limites propostos são decisões técnicas do produto, sujeitas aos testes desta entrega.
