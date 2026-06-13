# Módulo OMR OpenCV V2

## Esclarecimento

O ORM de banco já existe por meio do Eloquent/Laravel. Este documento trata do
OMR, reconhecimento óptico das marcações do cartão-resposta.

## Entrada e saída

Entrada: imagem original, modelo/versionamento do cartão e metadados mínimos.
Saída: qualidade, marcadores, perspectiva, identificadores, resposta por
questão, confiança, alertas, artefatos permitidos e versão do processador.

## Pipeline

1. Validar formato, tamanho e resolução.
2. Medir nitidez, iluminação, contraste e recorte.
3. Detectar marcadores e corrigir perspectiva.
4. Localizar região de identificação e respostas.
5. Interpretar código impresso/QR quando presente.
6. Calcular preenchimento por alternativa.
7. Classificar marcada, branca, dupla, duvidosa ou falha.
8. Retornar confiança e orientação de recaptura/revisão.
9. Persistir métricas, versão e histórico de reprocessamento.

## Homologação

- Dataset real versionado, com conjuntos separados de calibração e teste.
- Cartões impressos em variações autorizadas e fotos de dispositivos reais.
- Metas por classe, taxa de revisão e p95 documentados antes do piloto.
- O modelo pré-homologação existente é reutilizável apenas como contrato.

## Regra de segurança

O OMR não publica resultado sozinho. Leitura ambígua, código divergente ou baixa
confiança exige revisão humana explícita e auditada.
