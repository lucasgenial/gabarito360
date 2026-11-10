---
id: ADR-0053
titulo: Princípios executáveis, versionados e mutáveis
estado: Aceita
substituida_por: []
data: 2026-11-10
afeta_funcionalidades: [todas]
afeta_caminhos: ["docs/decisoes/**", "AGENTS.md", "scripts/**"]
verificacao: teste
regras: [R-PRINCIPIOS, R-AGENTS-GERADO, R-VERSAO-PRINCIPIOS-FN]
agents_secao: Princípios executáveis
agents_ordem: 10
agents_resumo:
  - "Os princípios podem mudar a qualquer momento. Uma mudança vale para todas as fases futuras."
  - "Todo princípio vigente tem verificação automática: um teste na CI ou a revisão do agente de conformidade. A verificação não depende de ação humana."
  - "Antes de alterar uma funcionalidade, revalide o planejamento dela contra os princípios vigentes e aplique as adequações pendentes. Depois, suba o campo `Conforme princípios` do `planejamento.md` para a versão atual. A CI reprova o PR que altera `apps/` sem essa revalidação."
  - "Violações antigas das regras de código ficam em `docs/decisoes/linha-de-base.json`. Uma violação nova reprova a CI. A linha de base só pode diminuir. Implementação: FN-078."
---
# ADR-0053 — Os princípios do projeto são executáveis, versionados e podem mudar a qualquer momento

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-10
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** pedido do responsável em 10/11/2026, na conversa sobre a nova documentação
**Afeta:** todas as funcionalidades; [FN-078](../funcionalidades/078-governanca-executavel/planejamento.md); [AGENTS.md](../../AGENTS.md); [governança](../planejamento/01-governanca.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

No início do projeto, o responsável definiu princípios. Depois de muito tempo, ele percebeu que entregas posteriores não seguiam esses princípios. A inconsistência gerou retrabalho de correção.

A causa foi estrutural: o princípio existia só como texto. Pessoas e agentes de IA deixaram de ler ou interpretaram de outro modo. A verificação dependia de uma pessoa, e o volume de trabalho tornou essa verificação inviável.

## Decisão

Decisão do responsável: "Será bom criar algo que impossibilite essa ação." "Princípios do projeto podem ser modificados a qualquer momento, e a partir dessa mudança o sistema/LLMs vai ter que incorporá-las nas fases futuras." A verificação não pode depender de uma ação humana.

1. Todo princípio vigente é um ADR com campos legíveis por máquina: identificador, versão, estado, o que afeta e como verificar.
2. Todo princípio verificável por código tem um teste na CI. A CI bloqueia o merge quando o teste falha.
3. Todo princípio que exige julgamento tem revisão automática por um agente de conformidade na CI. Um veredito "reprovado" bloqueia o merge.
4. Os princípios têm uma versão única. Cada `planejamento.md` registra a versão com que foi validado.
5. Uma mudança de princípio é propagada automaticamente: o sistema identifica o que ela afeta, cria as subfases de adequação e atualiza os resumos gerados.
6. Uma funcionalidade que será alterada é revalidada contra os princípios vigentes antes da alteração. A CI bloqueia a alteração se a funcionalidade estiver numa versão de princípios superada por um ADR que a afeta.
7. As seções de regras do `AGENTS.md` e do `CLAUDE.md` são geradas a partir dos ADRs. A CI bloqueia a divergência.
8. A ação humana fica restrita a decidir princípios, priorizar funcionalidades e autorizar a produção.

## Consequências

- Obrigatório: cada ADR novo ou alterado declara como é verificado e o que afeta.
- Obrigatório: a CI roda todas as verificações registradas e bloqueia o merge quando uma falha.
- Proibido: um princípio vigente sem forma de verificação (teste determinístico ou revisão automática).
- Proibido: editar à mão as seções geradas do `AGENTS.md` e do `CLAUDE.md`.
- Esta decisão complementa o [ADR-0048](0048-tronco-unico-de-planejamento.md). O tronco único organiza a fila; este ADR garante que a fila obedeça aos princípios vigentes.
- A implementação é a [FN-078 Governança executável](../funcionalidades/078-governanca-executavel/planejamento.md).
- Obrigatório: todo `planejamento.md` declara `Conforme princípios: versão N`. O arquivo `VERSAO` guarda a versão em que cada ADR mudou. Um PR que altera `apps/` e cita uma FN reprova quando um ADR aceito que afeta a FN mudou depois da versão N.
- Obrigatório: uma violação existente antes da regra entra na [linha de base](linha-de-base.json). A linha de base só pode diminuir. A correção entra como subfase de adequação da funcionalidade dona.

## Verificação

- `docs/decisoes/regras.yml` liga cada ADR verificável ao seu teste. A CI falha se um ADR aceito e verificável não tiver teste.
- A CI falha se o `AGENTS.md` gerado diferir do que os ADRs produzem.
- A CI falha se uma funcionalidade alterada estiver numa versão de princípios superada.
