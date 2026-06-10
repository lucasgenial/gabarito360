# ADR-D001 - Modelo fisico inicial do cartao

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Produto + OMR
- **Prazo:** resolvida; revalidar antes da homologacao OMR da FASE 7

## Contexto

O OMR, a impressao, o app e o banco precisam referenciar um modelo fisico estavel. Um cartao sem marcadores e regioes padronizadas aumenta o risco de leitura incorreta.

## Decisao

O MVP utilizara um unico modelo versionado de cartao-resposta com:

- uma pagina A4 em orientacao retrato, impressao preta sobre fundo branco;
- 20 questoes objetivas, com alternativas A, B, C, D e E;
- quatro marcadores fiduciais nos cantos para alinhamento e perspectiva;
- regiao configuravel para codigo impresso externo quando o modelo utilizado possuir esse elemento;
- nenhuma identificacao pessoal ou nome do aluno impresso;
- regioes e dimensoes definidas em uma configuracao OMR versionada;
- uma unica versao de modelo congelada por prova e aplicacao.

O desenho final deve ser validado com impressoes e cameras reais. Qualquer mudanca geometrica, de marcadores ou de regioes gera nova versao.

## Justificativa

Um modelo controlado reduz variacao, permite regressao reproduzivel e preserva o significado das leituras historicas.

## Impactos

- `modelos_cartao` deve ser versionado e imutavel depois de homologado.
- Cada prova do MVP referencia exatamente um modelo.
- O modelo define o tipo, a regiao e a normalizacao do codigo impresso; o codigo do sistema e tratado separadamente conforme D010.
- A imagem original sera obrigatoria para leituras enviadas ao backend, conforme D006.
- Limiares OMR permanecem fora deste ADR e serao calibrados com dataset real.
