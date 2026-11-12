# Arquitetura do Gabarito360

**Atualizado em:** 12/11/2026, por lucasgenial

Este documento é o índice da referência técnica do Gabarito360. Ele lista os catorze documentos de arquitetura e diz o que cada um explica.

## Como usar esta pasta

1. Leia primeiro a [visão geral](../visao-geral/README.md), se você ainda não conhece o produto.
2. Leia os documentos 01 e 02 para entender os blocos do sistema e a organização do código.
3. Leia os demais documentos conforme o assunto que você precisa.
4. Confira sempre o código. Um fato técnico só vale se existir no código da branch atual.

Cada assunto tem um único documento dono. Os outros documentos citam o dono por link.

## Documentos

| Nº | Documento | O que explica |
|---|---|---|
| 01 | [Contexto e containers](01-contexto-e-containers.md) | Atores, sistemas externos e blocos executáveis (modelo C4, níveis 1 e 2), com diagramas. |
| 02 | [Estrutura do código](02-estrutura-do-codigo.md) | Pastas do monorepo, convenções, testes e integração contínua (CI). |
| 03 | [Modelo de dados](03-modelo-de-dados.md) | Tabelas, relações, espaços e regras de integridade no MariaDB. |
| 04 | [Identidade, acesso e permissões](04-identidade-acesso-e-permissoes.md) | Contas, perfis, vínculos, tokens e o motor de permissões. |
| 05 | [Contratos da API e do Web](05-contratos-api-web.md) | API só JSON, Web como BFF, regras de URL (UR1 a UR4) e contrato HTTP (AP1). |
| 06 | [OMR e documentos](06-omr-e-documentos.md) | Motor de leitura de cartões, cartões-resposta, QR, fila de leitura e PDFs. |
| 07 | [Clientes, espaços e vínculos](07-clientes-espacos-e-vinculos.md) | Propriedade dos dados por espaço, vínculos e cópias entre espaços. |
| 08 | [Regras de negócio](08-regras-de-negocio.md) | Regras de notas, provas, correção, resultados e relatórios. |
| 09 | [Interface e design system](09-interface-e-design-system.md) | Padrões de tela, componentes, tabelas e responsividade. |
| 10 | [Painéis e editor](10-paineis-e-editor.md) | Painéis por perfil, painéis de turmas e editor de painéis. |
| 11 | [Segurança e privacidade](11-seguranca-e-privacidade.md) | Proteção de dados, LGPD, auditoria e dependências. |
| 12 | [Desempenho](12-desempenho.md) | Critérios, medições e otimizações. |
| 13 | [Infraestrutura e ambientes](13-infraestrutura-e-ambientes.md) | Servidores, processos, homologação e produção. |
| 14 | [Integração com o aplicativo financeiro](14-integracao-financeira.md) | Contrato com o aplicativo financeiro externo: capacidades, contextos, dados mínimos, consumo, respostas, credenciais, simulador e falhas. |

## Mapa rápido por pergunta

| Pergunta | Documento |
|---|---|
| Quais aplicações existem e como elas se falam? | [01](01-contexto-e-containers.md) |
| Onde fica cada tipo de arquivo no repositório? | [02](02-estrutura-do-codigo.md) |
| Por que a URL do navegador não mostra o ID da turma? | [05](05-contratos-api-web.md) |
| Como um cartão fotografado vira nota? | [06](06-omr-e-documentos.md) |
| Quem pode ver o quê? | [04](04-identidade-acesso-e-permissoes.md) |
| Como publicar uma versão? | [Operação](../operacao/README.md) |
| Onde ficam saldo, créditos e pagamentos? | [14](14-integracao-financeira.md) |

## Documentos relacionados

- [Catálogo de funcionalidades](../funcionalidades/README.md): estado de cada funcionalidade (FN-NNN).
- [Decisões (ADRs)](../decisoes/README.md): motivo de cada decisão de arquitetura.
- [Glossário](../glossario.md): definição dos termos e das siglas.
- [Governança do planejamento](../planejamento/01-governanca.md): regras de documentação e de entrega.
