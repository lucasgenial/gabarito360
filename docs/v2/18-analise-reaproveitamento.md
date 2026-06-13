# Plano de Reconstrução e Remoção do Legado V2

> Sob a restrição de **produto único, sem legado**
> ([ADR-D016](../decisoes/ADR-D016-v2-sem-legado.md)), este documento substitui
> a antiga "matriz de reaproveitamento". Não há classificação "aproveitar":
> cada módulo é **Reconstruir** (construído do zero sob os contratos V2) ou
> **Remover** (descontinuado e apagado do produto).
> Reuso permitido é apenas de *padrões e conhecimento*, nunca de código legado
> preservado por compatibilidade.

## Legenda

- **Reconstruir** — implementar do zero conforme o mockup e os contratos V2.
- **Remover** — apagar do produto; no máximo arquivar como registro histórico.

## Backend e domínio

| Módulo | Decisão | Justificativa |
|---|---|---|
| Estrutura Laravel 12 (camadas Actions/Requests/Resources/Services/Policies) | Reconstruir | Reaplicar o padrão num projeto V2 limpo, sem herdar implementações V1 |
| API REST | Reconstruir | Exclusivamente `/api/v2`; sem `/api/v1` nem compatibilidade |
| Autorização por perfil e escopo | Reconstruir | Modelar para todos os atores do mockup desde o início |
| Sanctum, idempotência, auditoria | Reconstruir | Definir como base V2, sem arrastar configuração legada |
| Endpoint `/api/v1/health` e API v1 | Remover | A V2 não expõe v1 |

## Dados

| Módulo | Decisão | Justificativa |
|---|---|---|
| Esquema MariaDB | Reconstruir | Esquema **único V2** consolidado; `migrate:fresh` baseline |
| Migrations/seeders V1 não consolidados | Remover | Sem migração de dados V1; seeders são da V2 |
| Modelo físico do cartão-resposta | Reconstruir | Definido em V2 a partir do padrão homologado (OBMEP) |

## Web

| Módulo | Decisão | Justificativa |
|---|---|---|
| Páginas Blade/Livewire anteriores | Remover | Não implementam o mockup integral |
| Tokens/componentes UI anteriores | Reconstruir | Extrair tokens do mockup; biblioteca de componentes nova |
| Rotas web anteriores | Reconstruir | Mapa de rotas derivado das 30 telas |
| Dados/JS estáticos do protótipo | Remover | Substituídos por consultas reais |

## Mobile

| Módulo | Decisão | Justificativa |
|---|---|---|
| App Flutter (`mobile/` atual) | Remover | ADR-D015 adota React Native; sem base legada |
| Novo app React Native | Reconstruir | Projeto novo, feature-first, tokens do mockup |
| Jornadas mobile | Reconstruir | Especificadas pelo mockup, não herdadas de código |

## OMR

| Módulo | Decisão | Justificativa |
|---|---|---|
| Pipeline OMR OpenCV | Reconstruir | Implementar e homologar com dataset real e métricas |
| Modelo pré-homologação | Remover | Substituído pelo pipeline V2 homologado |

## Infraestrutura e qualidade

| Módulo | Decisão | Justificativa |
|---|---|---|
| Docker/Nginx/Redis/Reverb | Reconstruir | Montados para servir somente a V2 |
| CI, backup e restauração | Reconstruir | Pipelines V2 cobrindo as novas telas e gates |
| `compose.yaml` | Reconstruir | Composição V2 sem serviços/configs legados |

## Documentação

| Módulo | Decisão | Justificativa |
|---|---|---|
| `docs/v2/` canônica | Reconstruir/Manter | Única fonte de contratos V2 |
| `docs/` numerada V1 | Remover (arquivar) | Histórico; não governa a V2 |
| Matrizes/planos R1–R7 | Remover (arquivar) | Não definem escopo V2 |

## Resumo executivo

- **Reconstruir:** todo o produto — backend, dados, web, mobile, OMR, infra e
  documentação canônica — sob os contratos V2.
- **Remover:** API v1, páginas e app legados, migrations/seeders V1 não
  consolidados, dados/JS estáticos do protótipo e a documentação V1 (arquivada).

## Sequência recomendada de remoção do legado

1. Congelar a V1 num marco/arquivo de histórico (tag e pasta de arquivo).
2. Remover endpoints `/api/v1` e o app Flutter.
3. Remover páginas/rotas/componentes web legados.
4. Recriar o esquema MariaDB V2 do zero (`migrate:fresh`).
5. Arquivar a documentação V1 numerada, deixando `docs/v2/` como única canônica.
