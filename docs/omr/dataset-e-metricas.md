# Gabarito360 - Dataset, Metricas e Homologacao OMR

## 1. Objetivo

Este documento define como criar, rotular, separar e medir o dataset usado para calibrar e homologar o OMR do Gabarito360. Ele torna verificaveis as metas do [ADR-D008](../decisoes/ADR-D008-metas-qualidade-omr.md) nos dispositivos avaliados conforme o [ADR-D007](../decisoes/ADR-D007-dispositivos-android.md).

O MP-005 nao inclui imagens, codigo de avaliacao ou resultados medidos. Nenhuma acuracia pode ser declarada antes da execucao reproduzivel deste protocolo sobre um dataset versionado.

## 2. Principios contra vies e vazamento

- Usar somente cartoes sem dados pessoais reais.
- Codigos impressos, quando presentes, devem ser sinteticos e nao reutilizar identificadores operacionais reais.
- Separar calibracao, teste e regressao por folha fisica e sessao de captura, nunca por copia da mesma imagem.
- Proibir ajuste de limiar, geometria ou regra apos observar o resultado do conjunto de teste selado.
- Registrar versoes do modelo, configuracao OMR, app, backend e ferramenta de avaliacao.
- Preservar amostras falhas; nao excluir silenciosamente imagens dificeis.
- Medir resultados gerais e segmentados por dispositivo, condicao e tipo de resposta.

## 3. Estrutura planejada

A estrutura executavel sera criada no MP-035. O contrato documental esperado e:

```text
omr/dataset/
|-- manifest.json
|-- labels/
|   |-- <sample_id>.json
|-- calibration/
|-- test/
\-- regression/
```

- Imagens reais identificaveis nao devem ser versionadas no Git.
- O manifesto pode ser versionado se contiver apenas metadados tecnicos e IDs sinteticos.
- Arquivos restritos devem seguir [retencao-e-arquivos.md](../seguranca/retencao-e-arquivos.md).

## 4. Unidade amostral e divisao

### 4.1 Unidade amostral

Uma amostra representa uma captura de uma folha fisica em um dispositivo, condicao e sessao determinados.

IDs relacionados:

- `sheet_id`: folha fisica sem significado pessoal;
- `sample_id`: captura individual;
- `session_id`: sessao de captura;
- `device_profile_id`: perfil tecnico do dispositivo;
- `print_batch_id`: lote e configuracao de impressao.

### 4.2 Conjuntos

| Conjunto | Finalidade | Pode ajustar limiares? | Regra |
|---|---|---:|---|
| `calibration` | Ajustar geometria, qualidade, preenchimento e confianca | Sim | Resultados e erros podem orientar mudancas |
| `test` | Medir o gate antes do piloto | Nao | Selado antes da calibracao final |
| `regression` | Detectar regressao a cada mudanca | Nao durante a entrega | Conjunto fixo, representativo e rapido |

Nenhum `sheet_id` ou `session_id` pode aparecer em mais de um conjunto. Capturas quase identicas e recortes derivados permanecem no mesmo conjunto da origem.

## 5. Cobertura minima planejada antes do piloto

Os valores abaixo sao requisitos de coleta, nao resultados:

| Cobertura | Minimo planejado |
|---|---:|
| Dispositivos fisicos candidatos | 3, em faixas de desempenho diferentes |
| Folhas fisicas no conjunto de teste | 500 |
| Respostas rotuladas no conjunto de teste | 10.000 |
| Folhas de teste por dispositivo homologado | 100 |
| Folhas de teste por perfil de identificador fisico avaliado | 50 |
| Folhas de teste em condicao adversa | 30% do conjunto de teste |
| Exemplos rotulados de branco | 500 respostas |
| Exemplos rotulados de dupla marcacao | 500 respostas |
| Exemplos rotulados de marcacao valida por alternativa | 500 por alternativa |

Se a cobertura nao permitir avaliar uma meta com confianca adequada, a equipe deve ampliar o dataset ou aplicar uma regra operacional mais conservadora. O volume minimo nao autoriza a liberacao quando alguma meta falhar.

## 6. Cenarios de captura

### 6.1 Condicoes normais

- iluminacao interna uniforme;
- pagina completa e sem oclusao;
- inclinacao pequena;
- foco e exposicao adequados;
- impressao e preenchimento dentro do protocolo.

### 6.2 Condicoes adversas controladas

- iluminacao baixa;
- sombra parcial;
- reflexo moderado;
- rotacao e perspectiva;
- distancia maior e menor;
- leve desfoque;
- impressao clara ou escura;
- canetas e intensidades de preenchimento permitidas;
- rasura, branco e dupla marcacao;
- codigo externo ou codigo do sistema afixado legivel, ausente, parcialmente degradado ou invalido, conforme o perfil avaliado.

Condicoes que tornem a imagem improcessavel devem permanecer no dataset com o rotulo de falha esperado e orientacao acionavel.

## 7. Formato de manifesto

O manifesto registra metadados globais e referencias sem dado pessoal:

```json
{
  "dataset_version": "0.1.0",
  "model_spec": {
    "id": "gabarito360-cartao-omr-v1",
    "version": "1.0.0-pre-homologacao"
  },
  "created_at": "2026-06-10T00:00:00Z",
  "splits": {
    "calibration": [],
    "test": [],
    "regression": []
  },
  "devices": [],
  "print_batches": []
}
```

Campos obrigatorios por amostra:

| Campo | Finalidade |
|---|---|
| `sample_id` | Identificador sintetico unico |
| `sheet_id` | Impedir vazamento entre conjuntos |
| `session_id` | Agrupar capturas correlacionadas |
| `split` | `calibration`, `test` ou `regression` |
| `image_ref` | Referencia controlada, sem caminho publico |
| `label_ref` | Referencia ao rotulo revisado |
| `device_profile_id` | Relacionar desempenho ao dispositivo |
| `print_batch_id` | Relacionar variacao de impressao |
| `conditions` | Lista controlada de condicoes |
| `processable_expected` | Se deve retornar leitura ou falha acionavel |
| `consent_and_origin` | Declaracao de origem sintetica ou autorizada |

## 8. Formato de rotulo por amostra

```json
{
  "sample_id": "sample-sintetico-0001",
  "sheet_id": "sheet-sintetico-0001",
  "processable_expected": true,
  "printed_code": {
    "profile": "externo_qr",
    "semantic_source": "external",
    "present": true,
    "value": "TESTE-000001",
    "expected_status": "detected"
  },
  "answers": [
    {
      "question": 1,
      "type": "marked",
      "alternatives": ["B"]
    },
    {
      "question": 2,
      "type": "blank",
      "alternatives": []
    },
    {
      "question": 3,
      "type": "double",
      "alternatives": ["A", "D"]
    }
  ],
  "review": {
    "reviewers": 2,
    "status": "approved",
    "notes": null
  }
}
```

Regras:

- Deve existir exatamente um rotulo para cada questao de 1 a 20 quando `processable_expected=true`.
- `marked` possui exatamente uma alternativa.
- `blank` nao possui alternativa.
- `double` possui duas ou mais alternativas.
- Casos ambiguos devem ser revisados; nao usar o resultado do algoritmo como verdade do rotulo.
- Teste e regressao exigem revisao por duas pessoas ou adjudicacao documentada.

## 9. Perfis de dispositivo e matriz de homologacao

Cada perfil registra apenas dados tecnicos necessarios:

| Campo | Exemplo de conteudo |
|---|---|
| `device_profile_id` | ID sintetico |
| Fabricante e modelo | Informacao tecnica declarada pelo sistema |
| Android e patch de seguranca | Versoes avaliadas |
| Arquitetura, RAM e espaco livre | Validacao dos requisitos minimos |
| Camera traseira, foco e flash | Capacidades necessarias |
| Versao do app e OMR | Reprodutibilidade |
| Estado | candidato, homologado, homologado com restricao ou rejeitado |
| Restricoes | Condicoes ou bloqueios documentados |

Checklist obrigatorio por dispositivo:

| Criterio do ADR-D007 | Condicao para aprovar |
|---|---|
| Sistema | Android 11 ou superior, com atualizacoes de seguranca suportadas |
| Camera | Traseira de pelo menos 8 MP, foco automatico e flash |
| Capacidade | Arquitetura 64 bits, pelo menos 4 GB de RAM e 2 GB livres |
| Qualidade OMR | Todas as metas aplicaveis do ADR-D008 atendidas |
| Cenarios | Suite aprovada em iluminacao normal e desfavoravel |
| Estabilidade | Nenhuma falha critica de camera, permissao, memoria ou rede |

Template da matriz:

| Dispositivo | Android | Camera/RAM | Suite funcional | Metas OMR | p95 dispositivo | Falhas criticas | Estado |
|---|---|---|---|---|---|---|---|
| A preencher | A preencher | A preencher | Nao executada | Nao medidas | Nao medido | Nao avaliadas | Candidato |

Pelo menos tres modelos fisicos devem ser avaliados. Um dispositivo somente pode ser marcado como homologado quando cumprir o ADR-D007 e todas as metas aplicaveis do ADR-D008.

## 10. Metricas e formulas

Considere uma posicao de resposta como correta quando tipo e alternativas previstos coincidem exatamente com a saida avaliada.

### 10.1 Acuracia geral por resposta

```text
respostas exatamente corretas / total de respostas esperadas
```

Meta: pelo menos `98,5%`.

### 10.2 Acuracia de alta confianca

```text
respostas exatamente corretas classificadas como alta confianca
/ total de respostas classificadas como alta confianca
```

Meta: pelo menos `99,5%`. O limiar de alta confianca deve vir da configuracao calibrada, nunca ser escolhido usando o conjunto de teste.

### 10.3 Revocacao de branco e dupla como alerta

Calcular separadamente para branco e dupla e tambem de forma combinada:

```text
casos esperados sinalizados para revisao / total de casos esperados
```

Meta critica: pelo menos `99,0%` para branco, para dupla e no combinado.

### 10.4 Erro silencioso de alta confianca

```text
respostas incorretas sem alerta classificadas como alta confianca
/ total de respostas classificadas como alta confianca
```

Meta: no maximo `0,1%`. Todo caso deve ser listado individualmente no relatorio.

### 10.5 Integridade estrutural

```text
imagens processaveis com exatamente 20 entradas
/ total de imagens processaveis
```

Meta: `100%`.

### 10.6 Latencia

- Dispositivo: medir do aceite da captura ate a exibicao do resultado preliminar, excluindo interacao humana.
- Backend: medir do inicio do processamento validado ate a conclusao do resultado ou falha acionavel.
- Registrar todas as amostras, aquecimento, versao e condicao.
- Reportar contagem, mediana, p95 e maximo por dispositivo e geral.

Metas: p95 de ate `5 segundos` no dispositivo homologado e p95 de ate `10 segundos` no backend.

### 10.7 Metricas secundarias obrigatorias

Mesmo quando nao constituirem gate isolado, o relatorio deve incluir:

- precisao dos alertas de branco e dupla;
- percentual de respostas enviado para revisao;
- taxa de falha acionavel por condicao e dispositivo;
- taxa de deteccao e validacao correta por perfil de identificador fisico;
- distribuicao de confianca para respostas corretas e incorretas.

## 11. Protocolo reproduzivel de medicao

1. Congelar `dataset_version`, especificacao do cartao, configuracao OMR e versoes executaveis.
2. Validar contrato, checksums, rotulos e ausencia de vazamento entre conjuntos.
3. Calibrar somente no conjunto `calibration`.
4. Congelar limiares e gerar checksum da configuracao.
5. Executar `test` uma unica vez para a decisao do gate.
6. Executar cada dispositivo sobre sua cobertura planejada nas mesmas versoes.
7. Calcular metricas gerais e segmentadas.
8. Listar falhas silenciosas, falhas criticas e imagens improcessaveis.
9. Gerar relatorio imutavel com comandos, ambiente, checksums e resultados.
10. Aprovar, restringir ou rejeitar modelo e dispositivos.

Qualquer alteracao depois do passo 5 invalida a medicao como gate e exige nova versao, novo conjunto de teste selado ou justificativa metodologica aprovada.

### 11.1 Contrato dos comandos futuros

O harness sera implementado no MP-035 e deve expor, no minimo:

```bash
cd omr && python -m pytest tests/test_dataset_contract.py
cd omr && python -m src.evaluation --help
cd omr && python -m src.evaluation validate --manifest dataset/manifest.json
cd omr && python -m src.evaluation run --manifest dataset/manifest.json --split test --output reports/gate.json
```

Os comandos acima sao contrato planejado, nao existem no MP-005. O runner deve recusar amostra sem rotulo, conjunto invalido, vazamento de `sheet_id`/`session_id` e tentativa de sobrescrever relatorio selado.

## 12. Gate do piloto

| Criterio | Condicao de aprovacao |
|---|---|
| Dataset | Versionado, rotulado, sem dados pessoais e sem vazamento |
| Modelo | Artefato e configuracao congelados |
| Dispositivos | Pelo menos tres avaliados; os usados no piloto homologados |
| Qualidade | Todas as metas do ADR-D008 atendidas |
| Falhas | Nenhuma falha critica de camera, permissao, memoria ou rede |
| Reprodutibilidade | Relatorio inclui versoes, checksums, comandos e ambiente |

Falha em meta critica bloqueia o piloto ou exige uma restricao operacional mais conservadora formalmente aprovada, como revisao obrigatoria adicional. Uma restricao nao pode ser usada para esconder erro silencioso de alta confianca.

## 13. Relatorio minimo

- versoes e checksums;
- contagem por conjunto, dispositivo, condicao e tipo de resposta;
- metricas gerais e segmentadas;
- matriz de homologacao de dispositivos;
- lista de erros silenciosos e falhas criticas;
- distribuicao de confianca e percentual enviado para revisao;
- latencia mediana, p95 e maxima;
- decisoes, restricoes e responsaveis;
- declaracao explicita de que nenhum dado pessoal real foi incluido.

## 14. Referencias

- [Modelo de cartao v1](modelo-cartao-v1.md)
- [Modulo OMR](../09-modulo-omr.md)
- [Retencao e arquivos](../seguranca/retencao-e-arquivos.md)
- [ADR-D001](../decisoes/ADR-D001-modelo-fisico-cartao.md)
- [ADR-D007](../decisoes/ADR-D007-dispositivos-android.md)
- [ADR-D008](../decisoes/ADR-D008-metas-qualidade-omr.md)
