---
id: ADR-0001
titulo: Monorepo com API Laravel só JSON, WEB Laravel e MariaDB
estado: Aceita
substituida_por: []
data: 2026-06-16
afeta_funcionalidades: [FN-001]
afeta_caminhos: ["apps/api/**", "apps/web/**"]
verificacao: teste
regras: [R-API-SO-JSON]
---
# ADR-0001 — O Gabarito360 usa monorepo com API Laravel (somente JSON), WEB Laravel e MariaDB

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-06-16
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** CLAUDE.md, seções "Arquitetura Oficial" e "Convenções de Implementação"; docs/13-roadmap.md (MP-001); docs/14-plano-de-execucao.md (MP-001, MP-003, MP-004, MP-005)
**Afeta:** [FN-001](../funcionalidades/001-fundacao-monorepo/planejamento.md); [arquitetura](../arquitetura/)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O produto precisava de uma base única e rastreável antes de qualquer código. A primeira rodada de planejamento definiu os aplicativos, o banco e a fronteira entre API e WEB. O legado não serviu de base técnica.

## Decisão

Adotar um monorepo com estes aplicativos:

| App | Tecnologia | Diretório | Responsabilidade |
|---|---|---|---|
| API | Laravel | `apps/api` | Autenticação, regras de negócio, OMR, relatórios |
| WEB | Laravel | `apps/web` | Interface administrativa e painéis |
| Android | React Native | `apps/android` | Aplicativo móvel que consome a API |
| iOS | React Native | `apps/ios` | Aplicativo móvel que consome a API |

O MariaDB é o único banco aprovado. Regra literal do CLAUDE.md: "A API nunca renderiza telas. Apenas JSON."

A API usa Laravel Sanctum e a classe `ApiResponse` como padrão de resposta (MP-004). A WEB chama a API por um cliente HTTP próprio (MP-005).

## Consequências

- A API só responde JSON. É proibido renderizar HTML na API.
- É proibido misturar responsabilidades entre API e WEB. A WEB não acessa as tabelas da API.
- É proibido adotar outro banco sem nova decisão.
- Os aplicativos móveis ficam adiados (ver [ADR-0006](0006-android-adiado-web-api-primeiro.md)).

## Verificação

- Confirme que existem `apps/api` e `apps/web`. Os diretórios `apps/android` e `apps/ios` **não existem** hoje (ver [ADR-0006](0006-android-adiado-web-api-primeiro.md)).
- Confirme `laravel/sanctum` em `apps/api/composer.json` e a classe `apps/api/app/Http/Responses/ApiResponse.php`.
