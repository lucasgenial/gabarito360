# ADR-D003 - Formato unico para o codigo do cartao

- **Status:** substituida pelo ADR-D010
- **Data:** 2026-06-10
- **Responsaveis:** Produto + OMR + Backend
- **Prazo:** substituida em 2026-06-10 apos revisao da origem dos cartoes

> Esta decisao foi preservada como historico. A premissa de que todos os cartoes seriam impressos pelo Gabarito360 estava incorreta. A decisao vigente esta em [ADR-D010](ADR-D010-identificacao-cartao.md).

## Contexto

O codigo precisa ser lido automaticamente, digitado manualmente quando necessario e validado antes de vincular o cartao ao aluno.

## Decisao

O codigo do cartao do MVP sera impresso como QR Code e texto legivel, no formato:

```text
G360-XXXXXXXXXXXX-C
```

- `G360` e o prefixo fixo.
- `XXXXXXXXXXXX` contem 12 caracteres aleatorios em base 36, usando `A-Z` e `0-9`.
- `C` e um digito verificador em base 36.
- A validacao sintatica usa `^G360-[A-Z0-9]{12}-[A-Z0-9]$`.
- O codigo e normalizado para maiusculas e sem espacos.
- O codigo confirmado deve ser unico dentro da prova.
- Falha de leitura automatica permite digitacao manual validada e auditada.

O digito verificador sera calculado assim:

1. Mapear os 12 caracteres para valores base 36: `0-9` valem `0-9` e `A-Z` valem `10-35`.
2. Multiplicar os valores, da esquerda para a direita, pelos pesos ciclicos `2, 3, 5, 7, 11, 13`.
3. Somar os produtos.
4. Calcular `(36 - (soma mod 36)) mod 36`.
5. Converter o resultado novamente para um caractere base 36.

Gerador, app e backend devem compartilhar vetores de teste do algoritmo antes da impressao do lote piloto.

## Justificativa

O formato e curto, reconhecivel, resistente a erros de digitacao e nao contem dados pessoais ou identificadores do aluno.

## Impactos

- O backend deve validar formato, digito e unicidade antes da confirmacao.
- O app deve apresentar QR detectado, permitir conferencia e validar entrada manual.
- O gerador de cartoes deve impedir repeticao no lote e manter rastreabilidade da geracao.
