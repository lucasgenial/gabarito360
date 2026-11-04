# heic-to 1.6.7

Distribuição local, carregada somente na preparação de HEIC/HEIF. Nenhuma foto é enviada ao projeto do codec ou a uma CDN.

- Origem: pacote npm `heic-to@1.6.7`, `dist/next/heic-to.js`, sem alterações.
- SHA256 do arquivo: `923713573d972152d741cb9a4e6a37ad8c8fb813c86e0c8f9bc5d68a0a57a849`.
- Integridade npm: `sha512-GCPYNl86u6eZ7mr2aa+uQB+PrQOVKSBYfH8esovr8kh2c6EYQSDYIJ6uhXSmM2hPC8PdBVJ/Trn0jnfM9H9gvg==`.
- Licença: LGPL-3.0, cópia em `heic-to-LICENSE.txt`.
- Fontes, versões e instruções de reconstrução: https://github.com/hoppergee/heic-to (tag/versão1.6.7, `esbuild.mjs` e instruções de compilação do libheif no README).
- O pacote usa libheif1.23.7; fonte e licenças de seus componentes: https://github.com/strukturag/libheif/tree/v1.23.7 e libde265: https://github.com/strukturag/libde265.

Atualizações devem preservar a licença, registrar novo hash e repetir fixtures/limites de memória, pixels e tempo. Não substituir por CDN ou versão flutuante. RAW/DNG não é decodificado por este pacote: o caminho DNG próprio aceita apenas uma prévia JPEG incorporada, com validação de limites, sem revelação completa do RAW.
