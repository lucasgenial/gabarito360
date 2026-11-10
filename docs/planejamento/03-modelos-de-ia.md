# Modelos de IA por classe e fornecedor

**Atualizado em:** 10/11/2026, por lucasgenial
**Status:** PROPOSTA. O responsável aprova a tabela antes do uso.

## 1. Objetivo

Este documento liga cada classe de modelo do [modelo de operação multiagente](02-agentes.md) a um modelo concreto de cada fornecedor. O projeto usa a Anthropic (Claude Code) e a OpenAI (Codex).

Só este documento cita nomes de modelos. Quando um modelo muda, atualize somente este documento e o campo `model:` dos agentes em `.claude/agents/`.

## 2. Classes e modelos

| Classe | Anthropic (Claude Code) | Identificador Anthropic | OpenAI (Codex) |
|---|---|---|---|
| raciocínio-alto | Claude Opus 5.5 | `claude-opus-5-5` | a preencher pelo responsável |
| equilibrado | Claude Sonnet 5.5 | `claude-sonnet-5-5` | a preencher pelo responsável |
| economico | Claude Haiku 5.5 | `claude-haiku-5-5` | a preencher pelo responsável |

Alternativa registrada (Anthropic): Claude Fable 5.2 (`claude-fable-5-2`). O gerente a usa só quando o responsável autoriza para um caso.

Mapeamento do campo `model:` em `.claude/agents/`:

| Classe | Valor de `model:` |
|---|---|
| raciocínio-alto | `opus` |
| equilibrado | `sonnet` |
| economico | `haiku` |

## 3. Critérios de escolha

O agente `selecao-modelos` aplica estes critérios. Quanto mais critérios apontam para cima, mais alta a classe.

| Critério | Classe mais alta quando | Classe mais baixa quando |
|---|---|---|
| Complexidade | A tarefa pede decisão de estrutura, contrato ou compromisso entre opções. | A tarefa segue um roteiro fixo. |
| Risco de segurança ou de dados | A tarefa toca autenticação, permissões, dados pessoais ou LGPD. | Nenhum dado pessoal ou acesso. |
| Tamanho do contexto | Muitos arquivos ou documentos precisam ficar juntos na análise. | Um arquivo ou uma lista curta. |
| Custo | O erro custa mais que o modelo. | A tarefa se repete muitas vezes e o erro é barato. |
| Ferramenta onde roda | A ferramenta só oferece certos modelos. Use o melhor disponível da classe. | Qualquer ferramenta serve. |
| Visão e navegador | A tarefa exige conferir a interface no navegador (UI/UX). Use um modelo com essa capacidade. | Não há conferência visual. |

Regras:

1. Em dúvida entre duas classes, escolha a mais alta.
2. Parecer de segurança sobre dados pessoais, autenticação ou permissões usa sempre raciocínio-alto.
3. Tarefa puramente mecânica (checagem, mapeamento, contagem, conferência de links) usa economico.
4. Se o fornecedor da classe estiver sem célula preenchida na tabela da seção 2, use o fornecedor com célula preenchida e avise o gerente.

## 4. Revisão por fornecedor diferente

O revisor independente pode usar outro fornecedor que o do implementador. Isso reduz o risco de o revisor repetir o mesmo erro do autor.

1. Use outro fornecedor para revisão de segurança, de dados pessoais e de arquitetura.
2. Use outro fornecedor quando a mesma classe já falhou duas vezes na mesma subfase.
3. Mantenha o mesmo fornecedor quando a ferramenta do outro não consegue ler o PR ou o ambiente.
4. O parecer segue o mesmo formato, com veredito, bloqueios e ressalvas, qualquer que seja o fornecedor.
5. O fornecedor do revisor não altera as regras do AGENTS.md.

## 5. Como registrar o modelo usado

O registro mostra a classe e o modelo de cada papel. O registro não atribui autoria a nenhuma IA.

1. No `planejamento.md`, na seção da subfase, acrescente uma tabela:

   | Papel | Agente | Classe | Modelo | Fornecedor |
   |---|---|---|---|---|
   | Implementação | `desenvolvedor-api` | equilibrado | `claude-sonnet-5-5` | Anthropic |

2. Se a classe mudou, escreva o motivo em uma frase.
3. No PR, escreva uma linha na descrição, na seção "Verificação": "Revisão de segurança: classe raciocínio-alto, outro fornecedor." Não inclua nomes de IA como autoras nem linhas `Co-Authored-By`.
4. A autoria do commit e do PR é somente `lucasgenial` (AGENTS.md).
5. O gerente confere o registro ao fim da subfase.
