# ADR-D016 - V2 como produto único, sem legado nem compatibilidade retroativa

- **Status:** aceita
- **Data:** 2026-06-13
- **Branch:** `v2/mockup-canonico`
- **Revoga:** ADR-D014 itens 6 e 7 (partir da base R7 para reaproveitar
  fundações) e a estratégia de manter a V1 em execução.
- **Mantém:** ADR-D014 quanto ao mockup `style-system/` como contrato integral;
  ADR-D015 (React Native); ADR-D012 (MariaDB).

## Contexto

As decisões anteriores assumiam reaproveitamento seletivo da fundação R0–R7,
compatibilidade temporária com `/api/v1` e preservação da V1 (páginas Blade,
app Flutter, documentos numerados) como histórico reutilizável.

A direção do produto definiu uma restrição mais forte: **não deve restar nada de
legado**. A V2 é o único produto. Não há transição gradual, dual-stack ou
artefato V1 em operação.

## Decisão

1. **Produto único.** Existe apenas a V2. A V1 não é executada, publicada nem
   mantida em paralelo.
2. **Sem compatibilidade retroativa.** A API é exclusivamente `/api/v2`. Não há
   `/api/v1`, nem mapeamento de compatibilidade, nem período de transição.
3. **Sem reaproveitamento de legado.** Nenhum módulo é mantido "como está" por
   ser legado. Cada capacidade é **reconstruída** sob os contratos V2 ou
   **removida**. Reuso permitido é apenas de *padrões e conhecimento*, nunca de
   código herdado preservado por compatibilidade.
4. **Esquema de dados único V2.** O banco MariaDB tem um único esquema V2,
   consolidado. `migrate:fresh` é a baseline. Não há tabelas "reutilizadas da
   V1" nem migração de dados V1; dados de demonstração são semeados pela V2.
5. **Mobile do zero.** O diretório Flutter é removido; o app é um projeto React
   Native novo (ADR-D015).
6. **Web do zero.** As páginas Blade/componentes anteriores são removidos; a
   camada visual é reconstruída fiel ao mockup.
7. **Documentação.** `docs/v2/` é a única documentação canônica. Os documentos
   numerados em `docs/` (V1) e as matrizes R1–R7 são **arquivados** como registro
   histórico de decisões e não governam nada da V2.

## Consequências

- Remoção do código e dos artefatos V1 (páginas, rotas, app Flutter, endpoints
  v1, seeders/migrations específicos da V1 não aproveitados pelo esquema V2).
- O plano de backend passa a descrever um esquema V2 único, sem coluna de
  "tabelas reutilizadas" e sem etapa de compatibilidade v1→v2.
- A "matriz de reaproveitamento" deixa de existir como tal; vira **plano de
  reconstrução e remoção** (Reconstruir vs Remover).
- A análise de GAP deixa de ter a dimensão "o que reaproveitar"; passa a
  descrever o que a V2 constrói e o que do legado deve ser removido.
- Risco assumido: maior volume de reconstrução inicial, em troca de uma base
  limpa, sem dívida técnica herdada e sem ambiguidade de escopo.

## Alternativas rejeitadas

- **Reaproveitar a fundação R7** (ADR-D014 original): contraria a restrição de
  não manter legado.
- **Manter `/api/v1` em transição:** reintroduz dual-stack e superfície legada.
- **Preservar a V1 em execução para fallback:** mantém produto legado vivo.
