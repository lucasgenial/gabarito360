# Gabarito360 - Modulo OMR

## 1. Objetivo

O modulo OMR (Optical Mark Recognition) transforma a imagem de um cartao-resposta em um conjunto estruturado de respostas detectadas e niveis de confianca. Ele nao substitui a revisao humana em casos duvidosos e nao depende de inteligencia artificial generativa.

## 2. Escopo inicial

- Cartao padronizado semelhante ao modelo da OBMEP.
- 20 questoes objetivas.
- Alternativas A, B, C, D e E.
- Codigo impresso legivel por QR Code, codigo de barras ou OCR definido no modelo.
- Marcadores de referencia para alinhamento.
- Uma marcacao valida por questao.

Outros formatos devem ser suportados futuramente por modelos versionados, sem alterar o significado das leituras historicas.

## 3. Requisitos do modelo de cartao

Cada versao de modelo deve definir:

- Dimensoes e proporcao esperadas.
- Posicao e tipo dos marcadores de referencia.
- Regiao do codigo identificador.
- Regioes de interesse das questoes e alternativas.
- Ordem de leitura.
- Limiar inicial de preenchimento.
- Faixa de baixa confianca.
- Criterios de branco e dupla marcacao.
- Resolucao minima e tolerancias geometricas.

O modelo deve ser validado com arquivos impressos reais antes de uso em producao.

## 4. Arquitetura recomendada

### 4.1 Estrategia hibrida

- **No app:** orientacao de captura, verificacao de qualidade e processamento preliminar rapido.
- **No backend/worker:** validacao opcional, reprocessamento, calibracao e analise de falhas.
- **Persistencia:** manter versao do modelo, metricas, deteccoes e, conforme politica, imagens original/processada.

O resultado confirmado pelo aplicador e a fonte utilizada para correcao. A deteccao original permanece preservada para auditoria e melhoria do OMR.

## 5. Pipeline de processamento

### 5.1 Entrada e validacao

1. Receber imagem e metadados.
2. Validar formato, tamanho e resolucao.
3. Corrigir orientacao conforme metadados quando confiavel.
4. Calcular metricas iniciais de nitidez, iluminacao, contraste e reflexo.
5. Rejeitar ou sinalizar imagem abaixo dos limites.

### 5.2 Pre-processamento

1. Converter para escala de cinza.
2. Aplicar normalizacao de iluminacao e contraste.
3. Reduzir ruido sem apagar contornos relevantes.
4. Aplicar binarizacao global ou adaptativa conforme o modelo.
5. Detectar bordas e contornos candidatos.

### 5.3 Localizacao e perspectiva

1. Detectar marcadores de referencia ou contorno principal.
2. Validar geometria e proporcao.
3. Ordenar pontos de referencia.
4. Calcular transformacao de perspectiva.
5. Gerar imagem normalizada nas dimensoes do modelo.

Se os marcadores esperados nao forem encontrados com confianca suficiente, o status deve ser parcial ou falha.

### 5.4 Leitura do codigo

1. Recortar a regiao do codigo.
2. Tentar decodificar conforme o tipo configurado.
3. Validar formato e digito verificador quando houver.
4. Retornar codigo e confianca.
5. Permitir digitacao manual no app quando a leitura falhar.

### 5.5 Deteccao das marcacoes

Para cada questao e alternativa:

1. Recortar a regiao de interesse.
2. Aplicar mascara que desconsidere bordas impressas.
3. Calcular proporcao de pixels preenchidos e outras metricas.
4. Comparar alternativas da mesma questao.
5. Classificar como marcada, branca, dupla ou duvidosa.
6. Calcular confianca e armazenar metricas de diagnostico.

### 5.6 Saida

1. Consolidar status geral.
2. Gerar lista de respostas e alertas.
3. Opcionalmente gerar imagem processada com sobreposicoes.
4. Retornar resultado estruturado.

## 6. Classificacao inicial

Os limiares abaixo sao conceituais e devem ser calibrados por modelo e dataset:

| Tipo | Regra conceitual |
|---|---|
| Marcada | Melhor alternativa supera limiar de preenchimento e possui separacao suficiente da segunda |
| Branca | Nenhuma alternativa supera limiar minimo |
| Dupla | Duas ou mais alternativas superam limiar de marcacao |
| Duvidosa | A diferenca entre candidatas e pequena ou a qualidade local e baixa |
| Falha | Regiao ou geometria nao pode ser determinada de forma confiavel |

Nao devem existir limiares globais ocultos no codigo. Cada valor deve estar associado a versao do modelo.

## 7. Confianca

A confianca por questao pode considerar:

- Proporcao de preenchimento da melhor candidata.
- Distancia para a segunda candidata.
- Nitidez e contraste na regiao.
- Qualidade do alinhamento.
- Integridade da regiao de interesse.

A confianca geral deve considerar:

- Qualidade global da imagem.
- Confianca dos marcadores e perspectiva.
- Percentual de questoes duvidosas.
- Confianca da leitura do codigo.

Baixa confianca nao significa necessariamente resposta errada; significa que a leitura exige revisao.

## 8. Contrato de saida conceitual

```json
{
  "modelo_cartao": {
    "id": "uuid",
    "versao": 1
  },
  "status": "parcial",
  "codigo": {
    "valor": "CARTAO-000123",
    "confianca": 0.91
  },
  "qualidade": {
    "nitidez": 0.86,
    "contraste": 0.78,
    "alinhamento": 0.95
  },
  "confianca_geral": 0.89,
  "respostas": [
    {
      "questao": 1,
      "alternativa": "B",
      "tipo": "marcada",
      "confianca": 0.98,
      "metricas": {
        "preenchimento": {
          "A": 0.08,
          "B": 0.74,
          "C": 0.06,
          "D": 0.05,
          "E": 0.07
        }
      }
    }
  ],
  "alertas": [
    {
      "tipo": "BAIXA_CONFIANCA",
      "questao": 8
    }
  ]
}
```

## 9. Estados da leitura

| Estado | Significado | Acao esperada |
|---|---|---|
| `sucesso` | Todas as regioes foram lidas acima dos criterios | Conferencia resumida e confirmacao |
| `parcial` | Ha alertas, mas a leitura pode ser revisada | Revisao obrigatoria |
| `falha` | Nao foi possivel produzir leitura confiavel | Nova captura ou procedimento alternativo |

## 10. Estrategia de calibracao

1. Criar dataset versionado com cartoes reais anonimizados.
2. Incluir variacoes de camera, impressao, caneta, iluminacao, inclinacao e desgaste.
3. Rotular respostas esperadas e anomalias.
4. Separar conjuntos de calibracao, teste e regressao.
5. Ajustar limiares apenas no conjunto de calibracao.
6. Medir resultados no conjunto de teste sem alteracao posterior.
7. Registrar configuracao, versao e metricas de cada experimento.

## 11. Metricas de qualidade

- Acuracia por resposta.
- Precisao e revocacao para marcacoes validas.
- Taxa de falso positivo em respostas em branco.
- Taxa de falso negativo em marcacoes validas.
- Taxa de dupla marcacao corretamente sinalizada.
- Percentual de leituras que exigem revisao.
- Taxa de falha por modelo de dispositivo.
- Tempo medio e percentil 95 de processamento.

Meta final do MVP deve ser definida apos o piloto com cartoes e dispositivos reais. A equipe nao deve declarar acuracia sem dataset reproduzivel.

## 12. Estrategias para reduzir erros

- Usar marcadores de referencia robustos e bem posicionados.
- Padronizar impressao, papel e dimensoes.
- Incluir guia de captura no app.
- Detectar nitidez, corte, sombra e reflexo antes de processar.
- Ignorar contorno impresso da bolha no calculo de preenchimento.
- Comparar alternativas dentro da mesma questao.
- Exigir revisao de baixa confianca e dupla marcacao.
- Preservar imagem e metricas para diagnostico conforme politica.
- Executar piloto antes de uso amplo.

## 13. Falhas e tratamento

| Falha | Tratamento |
|---|---|
| Marcadores ausentes | Solicitar nova captura |
| Cartao de modelo diferente | Bloquear ou selecionar modelo autorizado |
| Codigo ilegivel | Permitir digitacao manual validada |
| Perspectiva extrema | Rejeitar imagem |
| Sombra/reflexo | Orientar nova captura |
| Rasura | Sinalizar baixa confianca ou dupla |
| Regiao cortada | Falha de leitura |
| Processamento interrompido | Manter tentativa e permitir nova execucao |

## 14. Seguranca e privacidade

- Processar somente arquivos autorizados e validar o tipo real.
- Limitar tamanho, resolucao e tempo de processamento.
- Isolar workers de processamento quando executados no backend.
- Nao expor caminhos internos das imagens.
- Restringir acesso a imagens e aplicar retencao.
- Remover metadados desnecessarios das imagens armazenadas.

## 15. Criterios de aceite do MVP OMR

- Processa o modelo inicial versionado de 20 questoes A-E.
- Detecta e corrige perspectiva dentro das tolerancias homologadas.
- Retorna exatamente uma entrada por questao.
- Classifica branco, dupla e baixa confianca.
- Retorna confianca e metricas suficientes para auditoria.
- Nunca confirma automaticamente uma leitura no lugar do aplicador.
- Mantem resultado reproduzivel para a mesma imagem, modelo e configuracao.
- Possui suite de regressao com imagens rotuladas.
