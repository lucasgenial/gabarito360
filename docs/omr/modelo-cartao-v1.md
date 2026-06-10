# Gabarito360 - Modelo de Cartao OMR v1

## 1. Identificacao e status

| Campo | Valor |
|---|---|
| Identificador logico | `gabarito360-cartao-omr-v1` |
| Versao da especificacao | `1.0.0-pre-homologacao` |
| Estado | Candidato para impressao, dataset e homologacao |
| Pagina | A4, retrato, 210 x 297 mm |
| Impressao | Preto sobre fundo branco, escala 100% |
| Questoes | 20 |
| Alternativas | A, B, C, D e E |
| Identificacao pessoal impressa | Proibida |

Esta especificacao materializa o modelo aprovado no [ADR-D001](../decisoes/ADR-D001-modelo-fisico-cartao.md). Ela define uma geometria candidata reproduzivel, mas nao declara o cartao homologado. A versao somente pode mudar para `1.0.0` depois de impressao real, captura nos dispositivos da matriz e aprovacao do gate descrito em [dataset-e-metricas.md](dataset-e-metricas.md).

Qualquer mudanca de pagina, marcadores, coordenadas, regioes, ordem das questoes ou regiao do codigo impresso gera nova versao. Limiares de preenchimento e confianca nao pertencem a este documento; devem ser calibrados e versionados na configuracao OMR.

## 2. Sistema de coordenadas

O modelo usa uma imagem canonica de A4 a 300 DPI:

| Eixo | Dimensao | Origem |
|---|---:|---|
| `x` | 2480 px | Borda esquerda |
| `y` | 3508 px | Borda superior |

- Todas as regioes usam retangulos no formato `[x, y, largura, altura]`.
- A transformacao de perspectiva deve gerar exatamente `2480 x 3508` pixels antes de recortar regioes.
- Valores em pixels representam a geometria canonica, nao uma exigencia de resolucao da captura original.
- A configuracao persistida deve incluir `spec_id`, `spec_version`, dimensoes canonicas e checksum do artefato de impressao homologado.

## 3. Margens e areas proibidas

| Area | Regiao canonica | Regra |
|---|---|---|
| Margem externa de seguranca | 100 px em todos os lados | Nenhum elemento essencial |
| Zona dos marcadores | Quadrados de 220 x 220 px nos cantos | Nao inserir texto, bolhas ou codigo |
| Identificacao pessoal | Pagina inteira | Nome, matricula, CPF e turma nao podem ser impressos |
| Codigo do sistema | Sem regiao exclusiva | Pode usar a regiao fisica reservada somente em perfil homologado `sistema_afixado` |

O codigo do sistema `G360-XXXXXXXXXXXX-C` nao deve ser confundido com o codigo impresso externo. O OMR v1 le somente elementos fisicamente presentes e declarados pelo perfil do modelo, conforme [ADR-D010](../decisoes/ADR-D010-identificacao-cartao.md).

## 4. Marcadores fiduciais

O v1 possui quatro marcadores OpenCV ArUco do dicionario `DICT_4X4_50`, com IDs unicos para distinguir posicao e rotacao:

| Posicao | ArUco ID | Retangulo canonico `[x, y, w, h]` | Centro esperado |
|---|---:|---|---|
| `TL` | `0` | `[120, 120, 140, 140]` | `[190, 190]` |
| `TR` | `1` | `[2220, 120, 140, 140]` | `[2290, 190]` |
| `BR` | `2` | `[2220, 3248, 140, 140]` | `[2290, 3318]` |
| `BL` | `3` | `[120, 3248, 140, 140]` | `[190, 3318]` |

Regras:

- Cada marcador deve ser gerado pelo mesmo dicionario e artefato versionado, com zona clara minima de 40 px ao redor.
- A deteccao deve encontrar exatamente os IDs `0`, `1`, `2` e `3` e preservar o mapeamento de posicao.
- IDs unicos devem permitir detectar rotacao e impedir inversao silenciosa da ordem das questoes.
- O contorno da pagina pode auxiliar a validacao, mas nao substitui silenciosamente marcador ausente.
- Tolerancias geometricas e limiar de deteccao devem ser calibrados no dataset e registrados na configuracao OMR.
- Falha ou ambiguidade em marcador deve produzir falha acionavel ou revisao, nunca respostas silenciosamente confiantes.

## 5. Regiao do identificador fisico

O v1 reserva uma regiao para codigo que ja venha fisicamente impresso ou materialmente afixado no cartao:

| Campo | Valor |
|---|---|
| Regiao reservada | `[360, 250, 1760, 420]` |
| Obrigatoriedade fisica | Opcional |
| Tipos permitidos por perfil homologado | `sem_codigo`, `externo_qr`, `externo_codigo_barras`, `externo_ocr_texto`, `sistema_afixado_qr`, `sistema_afixado_ocr_texto` |
| Conteudo de teste | Somente codigos sinteticos sem dado pessoal |

Regras:

- Um perfil homologado do modelo escolhe exatamente um tipo e uma origem semantica: codigo externo ou codigo do sistema materialmente afixado.
- A regiao permanece reservada mesmo quando o perfil for `sem_codigo`.
- Mudar tipo, regiao, normalizacao ou regra de validacao exige nova versao ou novo perfil homologado explicitamente associado ao modelo.
- O valor detectado deve ser preservado separadamente da versao normalizada e encaminhado ao campo correspondente a sua origem.
- Falha na leitura automatica permite digitacao manual auditada; cartao sem codigo registra o motivo aplicavel.
- O OMR nao gera codigo do sistema e nao assume que um codigo externo segue o formato G360. Ele somente reconhece um codigo G360 como `sistema_afixado` quando o perfil homologado declarar essa origem.

## 6. Grade de respostas

### 6.1 Regiao geral

| Campo | Valor |
|---|---|
| Regiao da grade | `[340, 850, 1800, 2200]` |
| Linhas | 20 |
| Altura nominal por linha | 100 px |
| Passo vertical | 105 px |
| Centro da primeira linha | `y = 950` |
| Centros das alternativas | A=`900`, B=`1120`, C=`1340`, D=`1560`, E=`1780` |
| Raio nominal externo da bolha | 42 px |
| Raio da mascara interna candidata | 28 px |

O numero da questao e rotulos A-E sao elementos visuais, mas nao devem invadir a mascara interna usada para medir preenchimento.

### 6.2 Formula das regioes

Para a questao `q`, de 1 a 20:

```text
centro_y(q) = 950 + (q - 1) * 105
```

Para cada alternativa, o centro `x` e definido pela tabela da secao 6.1. A regiao de interesse nominal da bolha e:

```text
[centro_x - 50, centro_y - 50, 100, 100]
```

O pipeline deve retornar uma entrada para cada questao de 1 a 20, mesmo quando classificada como branca, dupla, duvidosa ou falha local.

## 7. Artefatos versionados exigidos

Antes da homologacao, a implementacao deve produzir e versionar:

- arquivo mestre vetorial ou equivalente usado para impressao;
- PDF de impressao com checksum SHA-256;
- configuracao OMR legivel por maquina com todas as regioes;
- imagem canonica de referencia;
- amostras sinteticas sem dados pessoais;
- changelog da especificacao.

Esses artefatos serao criados em micropasso de implementacao proprio. O MP-005 define o contrato documental e nao gera o desenho final nem o codigo OMR.

## 8. Configuracao OMR conceitual

O formato abaixo e orientativo para a futura configuracao executavel:

```json
{
  "spec_id": "gabarito360-cartao-omr-v1",
  "spec_version": "1.0.0-pre-homologacao",
  "canvas": {
    "width": 2480,
    "height": 3508
  },
  "markers": {
    "dictionary": "DICT_4X4_50",
    "items": {
      "TL": {"id": 0, "region": [120, 120, 140, 140]},
      "TR": {"id": 1, "region": [2220, 120, 140, 140]},
      "BR": {"id": 2, "region": [2220, 3248, 140, 140]},
      "BL": {"id": 3, "region": [120, 3248, 140, 140]}
    }
  },
  "printed_code": {
    "region": [360, 250, 1760, 420],
    "type": "PREENCHER_NA_HOMOLOGACAO",
    "semantic_source": "PREENCHER_NA_HOMOLOGACAO",
    "normalization": "PREENCHER_NA_HOMOLOGACAO"
  },
  "answers": {
    "questions": 20,
    "alternatives": ["A", "B", "C", "D", "E"],
    "first_center_y": 950,
    "row_step": 105,
    "centers_x": {
      "A": 900,
      "B": 1120,
      "C": 1340,
      "D": 1560,
      "E": 1780
    }
  },
  "thresholds": "PREENCHER_APENAS_APOS_CALIBRACAO"
}
```

Nenhum valor `PREENCHER_*` pode ser substituido por estimativa sem evidencias do dataset.

## 9. Protocolo de impressao e captura

- Imprimir em A4, retrato, preto e branco, escala 100%, sem "ajustar a pagina".
- Registrar impressora, configuracao, papel, lote e checksum do PDF.
- Confirmar medidas fisicas e ausencia de corte dos marcadores.
- Confirmar leitura dos quatro IDs ArUco e deteccao correta de rotacao.
- Preencher cartoes apenas com respostas e codigos sinteticos previstos no manifesto.
- Capturar pagina completa nos dispositivos candidatos, sem identificacao pessoal no enquadramento.
- Incluir condicoes normais e adversas definidas em [dataset-e-metricas.md](dataset-e-metricas.md).

## 10. Gate para congelar `1.0.0`

O modelo pode ser promovido de `pre-homologacao` para `1.0.0` somente quando:

- artefato de impressao e configuracao OMR possuem checksum versionado;
- medidas e regioes foram validadas em impressoes reais;
- os cartoes fisicos usados no piloto correspondem ao modelo ou foram associados a outra versao homologada, sem ajuste silencioso;
- tipo e normalizacao do codigo impresso estao definidos por perfil;
- dataset possui calibracao, teste selado e regressao sem dados pessoais;
- pelo menos tres dispositivos candidatos foram avaliados;
- todas as metas criticas do ADR-D008 foram atendidas ou uma restricao mais conservadora foi formalmente aprovada;
- qualquer alteracao realizada durante a calibracao foi registrada e nao contaminou o conjunto de teste.

## 11. Referencias

- [Dataset, metricas e homologacao](dataset-e-metricas.md)
- [Modulo OMR](../09-modulo-omr.md)
- [ADR-D001](../decisoes/ADR-D001-modelo-fisico-cartao.md)
- [ADR-D007](../decisoes/ADR-D007-dispositivos-android.md)
- [ADR-D008](../decisoes/ADR-D008-metas-qualidade-omr.md)
- [ADR-D010](../decisoes/ADR-D010-identificacao-cartao.md)
