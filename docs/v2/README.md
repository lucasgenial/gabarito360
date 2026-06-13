# Documentação Canônica Gabarito360 V2

## Status

Esta pasta é a **única** fonte de verdade da V2. O produto deve reproduzir
integralmente as funcionalidades, recursos, conteúdo, interações, responsividade
e identidade visual presentes nas 30 telas de `style-system/`.

A V2 é **produto único, sem legado** ([ADR-D016](../decisoes/ADR-D016-v2-sem-legado.md)):
não há reaproveitamento da V1 nem compatibilidade retroativa. Os documentos
anteriores em `docs/` são histórico arquivado e não governam a V2.

## Precedência

1. `style-system/DESIGN-HANDOFF.md` e os HTMLs definem o produto e o resultado visual.
2. `style-system/css/gov.css` e `style-system/js/app.js` definem tokens e interações.
3. `docs/ui_token_gov_brasil.json` e `docs/SDGB.md` definem valores e normas visuais.
4. Esta pasta define contratos de implementação, rastreabilidade e entrega.
5. Segurança, LGPD e integridade podem adaptar a execução, sem remover a capacidade.

## Documentos

| Arquivo | Finalidade |
|---|---|
| `01-visao-produto.md` | Escopo e princípios da V2 |
| `02-inventario-funcional-mockup.md` | Inventário integral das 30 telas |
| `03-requisitos.md` | Requisitos funcionais e não funcionais |
| `04-regras-de-negocio.md` | Regras canônicas |
| `05-casos-de-uso-e-jornadas.md` | Atores, jornadas e casos de uso |
| `06-arquitetura-e-reaproveitamento-v1.md` | Arquitetura e matriz de reaproveitamento |
| `07-modelagem-dados-mariadb.md` | Modelo de dados V2 |
| `08-api-e-integracoes.md` | Contratos API, eventos e integrações |
| `09-web-design-system.md` | Contrato da aplicação web responsiva |
| `10-android-react-native.md` | Contrato do aplicativo Android (React Native, ADR-D015) |
| `10-android-flutter.md` | Histórico (substituído por React Native) |
| `11-omr-opencv.md` | Contrato do módulo OMR |
| `12-dashboards-e-relatorios.md` | Indicadores, relatórios e exportações |
| `13-seguranca-lgpd.md` | Segurança, privacidade e auditoria |
| `14-infraestrutura-e-qualidade.md` | Ambientes, CI, testes e operação |
| `15-matriz-rastreabilidade.md` | Rastreabilidade mockup -> produto -> entrega |
| `16-plano-executavel-v2.md` | Ordem executável de reconstrução |
| `17-analise-gap.md` | GAP: existente vs mockup, faltas e divergências |
| `18-analise-reaproveitamento.md` | Classificação de reaproveitamento por módulo |
| `19-estrategia-git.md` | Branches, commits, merge, versionamento e tags |
| `20-relatorio-execucao.md` | Relatório da fase de planejamento V2 |
| `21-plano-backend.md` | Plano executável do backend Laravel + MariaDB (V2-02) |

## Regra de conclusão

Uma tela ou recurso só está concluído quando possui dados reais, autorização,
estados, testes, responsividade e comparação visual aprovada contra o mockup.
