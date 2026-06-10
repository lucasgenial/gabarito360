# ADR-D010 - Identificacao de cartoes impressos e codigos gerados

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Produto + OMR + Mobile + Backend
- **Prazo:** resolvida; revalidar antes das migrations de cartoes e da homologacao OMR

## Contexto

Os cartoes usados pelo Gabarito360 podem chegar com um codigo ja impresso por uma organizacao externa. Esse codigo pode usar QR Code, codigo de barras, OCR ou texto e nao necessariamente segue um formato controlado pelo Gabarito360.

O app tambem pode gerar um codigo operacional novo. Esse codigo ajuda na idempotencia, sincronizacao e rastreabilidade do registro, mas nao identifica fisicamente o papel se nao for impresso, escrito ou etiquetado no cartao.

Tratar os dois codigos como um unico campo causaria perda do valor original impresso, validacao incorreta e conflitos entre identificadores com finalidades diferentes.

## Decisao

Cada cartao confirmado deve preservar dois conceitos separados:

1. **Codigo impresso externo**
   - Valor que ja existe fisicamente no cartao.
   - Pode ser detectado pelo OMR ou digitado pelo aplicador.
   - E opcional quando o cartao realmente nao possui codigo impresso.
   - Deve preservar o valor informado e uma versao normalizada para busca e unicidade.
   - Seu formato, tipo de leitura e normalizacao sao definidos pela versao do modelo de cartao.
   - Quando informado, deve ser unico dentro da prova.

2. **Codigo do sistema**
   - Identificador operacional gerado pelo app ou backend.
   - Usa o formato `G360-XXXXXXXXXXXX-C`.
   - E opcional quando o codigo impresso ja identifica adequadamente o cartao.
   - E obrigatorio quando nao houver codigo impresso e a confirmacao precisar de um codigo de negocio.
   - Quando informado, e unico globalmente.
   - Nao substitui nem sobrescreve o codigo impresso.
   - Nao deve ser apresentado como identificador fisico do papel quando nao estiver materialmente afixado ao cartao.

O UUID tecnico do registro e a chave idempotente da operacao continuam existindo independentemente desses codigos. O fluxo deve exibir campos separados para o codigo impresso e o codigo do sistema quando este for utilizado. Se houver codigo impresso, o aplicador deve confirma-lo ou corrigi-lo antes do envio. Se nao houver, o motivo `cartao_sem_codigo_impresso` e um codigo do sistema devem ser registrados.

Se o projeto decidir usar o codigo do sistema como identificador fisico em uma aplicacao, ele devera ser impresso, etiquetado ou escrito no cartao antes da confirmacao, e essa operacao devera ser registrada.

O digito verificador do codigo do sistema sera calculado assim:

1. Mapear os 12 caracteres para valores base 36: `0-9` valem `0-9` e `A-Z` valem `10-35`.
2. Multiplicar os valores, da esquerda para a direita, pelos pesos ciclicos `2, 3, 5, 7, 11, 13`.
3. Somar os produtos.
4. Calcular `(36 - (soma mod 36)) mod 36`.
5. Converter o resultado novamente para um caractere base 36.

## Justificativa

A separacao preserva o dado original, permite trabalhar com cartoes legados e mantem um identificador tecnico confiavel sem criar uma falsa associacao com o papel.

## Impactos

- `cartoes_resposta` deve possuir `codigo_impresso`, `codigo_impresso_normalizado` e `codigo_sistema` opcional.
- `leituras_cartao` deve preservar o codigo impresso detectado, a confianca e o codigo do sistema proposto pelo app quando houver.
- O modelo do cartao define como detectar e normalizar o codigo impresso.
- API e app devem apresentar os dois campos separadamente.
- Conflitos de codigo impresso e codigo do sistema devem possuir codigos de erro distintos.
- O OMR le apenas o codigo fisicamente presente; ele nao inventa nem detecta um codigo do sistema que nao esteja no papel.

## Impacto no plano executavel

A ordem das fases permanece valida. Os seguintes micropassos devem aplicar esta decisao:

- `MP-005`: especificar a regiao e as regras do codigo impresso por modelo.
- `MP-024`: modelar `tipo_codigo`, normalizacao e opcao `sem_codigo`.
- `MP-031`: preservar codigo impresso detectado e codigo do sistema proposto separadamente.
- `MP-032`: criar constraints e conflitos distintos para os dois identificadores.
- `MP-039`: limitar o OMR a codigos fisicamente presentes.
- `MP-043`, `MP-044` e `MP-045`: capturar, revisar e confirmar os campos separadamente.
