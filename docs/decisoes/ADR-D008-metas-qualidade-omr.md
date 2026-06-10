# ADR-D008 - Metas de qualidade OMR para o piloto

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Produto + OMR + QA
- **Prazo:** resolvida; medir antes do piloto e apos mudanca de modelo

## Contexto

O OMR nao pode ser considerado pronto apenas por funcionar em exemplos isolados. O piloto precisa de metas reproduziveis em conjunto de teste separado.

## Decisao

O gate OMR do piloto exige, no conjunto de teste nao usado para calibracao:

| Metrica | Meta minima |
|---|---:|
| Acuracia geral por resposta | 98,5% |
| Acuracia de respostas classificadas com alta confianca | 99,5% |
| Revocacao de branco e dupla marcacao como alerta | 99,0% |
| Resposta incorreta silenciosa classificada com alta confianca | no maximo 0,1% |
| Imagens processaveis com exatamente 20 respostas retornadas | 100% |
| Tempo preliminar no dispositivo homologado | p95 de ate 5 segundos |
| Tempo de validacao ou reprocessamento no backend | p95 de ate 10 segundos |

Imagens fora dos criterios devem falhar com orientacao acionavel, em vez de produzir resposta silenciosamente incorreta.

Os limiares de preenchimento e confianca nao sao definidos neste ADR. Eles serao calibrados exclusivamente no conjunto de calibracao, versionados em `configuracao_omr` e avaliados no conjunto de teste.

## Justificativa

As metas priorizam evitar erro silencioso e tornam a liberacao do piloto verificavel.

## Impactos

- O dataset precisa separar calibracao, teste e regressao.
- O relatorio deve apresentar metricas por dispositivo e tipo de marcacao.
- Falha em qualquer meta critica bloqueia o piloto ou exige revisao humana mais restritiva documentada.
