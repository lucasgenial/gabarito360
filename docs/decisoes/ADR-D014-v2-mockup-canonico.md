# ADR-D014 - Mockup funcional como contrato integral da V2

- **Status:** aceita
- **Data:** 2026-06-13
- **Branch:** `v2/mockup-canonico`

## Contexto

A branch R0-R7 criou uma fundação técnica consistente, mas interpretou o
mockup como referência parcial. Recursos visíveis foram classificados como
ilustrativos, adiados ou fora de escopo. Essa interpretação não representa a
direção atual do produto.

## Decisão

1. A V2 será reconstruída considerando `style-system/` como contrato funcional
   e visual integral.
2. As 30 telas devem possuir equivalentes funcionais em produção. Variantes
   duplicadas contribuem com a união de capacidades; a variante mais recente
   orienta a composição visual.
3. Cadastro, aluno autenticado, agendas, configurações, integrações,
   importação/exportação e demais recursos visíveis entram no backlog V2.
4. Regras de segurança e LGPD adaptam fluxos perigosos, mas não eliminam a
   intenção. Exemplo: "excluir dados" vira solicitação LGPD rastreável.
5. A V1 permanece preservada no histórico Git e em `docs/`; a documentação
   canônica passa a ser `docs/v2/`.
6. A V2 parte da base R7 para reaproveitar fundações comprovadas, em vez de
   criar um repositório sem histórico.
7. O ORM Eloquent já existe. O componente pendente de homologação é o OMR com
   OpenCV, além da conclusão do app Android.

## Consequências

- A ADR-D013 e as matrizes R1-R7 permanecem históricas, mas não governam escopo V2.
- As páginas web atuais serão comparadas e, quando necessário, substituídas.
- O modelo de dados e a API serão ampliados para sustentar todas as capacidades.
- Cada entrega deve atualizar a matriz de rastreabilidade e produzir evidência visual.

## Alternativas rejeitadas

- Criar outro repositório e perder a base tecnicamente validada.
- Manter a V1 e apenas adicionar OMR/Android.
- Copiar o mockup como HTML estático sem regras, dados reais e autorização.
