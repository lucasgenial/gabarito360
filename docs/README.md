# Documentação do Gabarito360

**Atualizado em:** 12/11/2026, por lucasgenial

Esta é a porta de entrada da documentação. Uma pessoa ou um agente de IA entende o sistema inteiro lendo só estes documentos.

## Por onde começar

| Se você quer… | Leia |
|---|---|
| Entender o que é o Gabarito360, em 15 minutos | [Visão geral](visao-geral/README.md) |
| Conhecer a arquitetura e os detalhes técnicos | [Arquitetura](arquitetura/README.md) |
| Saber o que cada funcionalidade faz e o estado dela | [Catálogo de funcionalidades](funcionalidades/README.md) |
| Saber o que é feito agora e em que ordem | [Plano mestre](planejamento/00-plano-mestre.md) |
| Entender por que uma decisão foi tomada | [Decisões (ADRs)](decisoes/README.md) |
| Publicar, fazer backup, restaurar ou tratar um incidente | [Operação](operacao/README.md) |
| Entender um termo | [Glossário](glossario.md) |

## Estrutura

| Pasta | Nível | Conteúdo |
|---|---|---|
| `visao-geral/` | Introdução | O produto, o público, os perfis, os módulos e o estado atual. Leitura fácil, sem jargão. |
| `arquitetura/` | Referência técnica | Contexto, containers, modelo de dados, acesso e permissões, contratos, regras de negócio, interface, segurança, desempenho, infraestrutura e integração com o aplicativo financeiro. |
| `funcionalidades/` | Funcionalidades | Catálogo e, para cada funcionalidade, o planejamento e os casos de uso. |
| `planejamento/` | Execução | Plano mestre (orquestrador) e governança. |
| `decisoes/` | Decisões | Um registro de decisão de arquitetura (ADR) por arquivo, com data, contexto e consequência. |
| `operacao/` | Procedimentos | Roteiros de publicação, backup, restauração, auditoria, e-mail e incidentes. |
| `historico/` | Histórico | Inventários antigos e o mapa dos documentos antigos para os novos. |
| `glossario.md` | Apoio | Um termo para cada conceito. |

## Regras da documentação

- Escreva no padrão ASD-STE100 moderado (AGENTS.md).
- Cada assunto tem um único documento dono. Os outros documentos citam o dono por link.
- Um fato técnico (rota, tabela, permissão, comando) deve existir no código. Confira antes de escrever.
- Não inclua prints ou screenshots da aplicação, segredos, dados pessoais reais ou nomes de alunos.
- A autoria é somente do responsável (`lucasgenial`).

## Documentos antigos

Até 10/11/2026, a documentação ficava em arquivos numerados (`docs/NN-*.md`). O conteúdo desses arquivos foi validado e incorporado nesta estrutura. O mapa de cada documento antigo para o novo lugar está em [historico/mapa-documentos-antigos.md](historico/mapa-documentos-antigos.md). O Git guarda o texto original.
