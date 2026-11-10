# 078 — Governança executável — Planejamento

**Implementação:** Parcial
**Etapa:** Em execução
**Posição no plano mestre:** Onda 0, ordem 0.0 (urgente, por decisão do responsável em 10/11/2026)
**Natureza:** nova
**Origem:** surgiu na reestruturação da documentação em 10/11/2026; decisão registrada no [ADR-0053](../../decisoes/0053-principios-executaveis-e-mutaveis.md)
**Atualizado em:** 2026-11-10, por lucasgenial

> Escreva este documento no padrão ASD-STE100 moderado (AGENTS.md): uma ideia por frase, voz ativa, frases curtas.

## 1. Objetivo

Garantir que todo trabalho futuro obedeça aos princípios vigentes do projeto, sem verificação humana. Garantir também que uma mudança de princípio chegue automaticamente às fases futuras.

O problema que esta funcionalidade resolve: princípios definidos no início do projeto foram esquecidos ao longo do tempo. A inconsistência só apareceu depois e gerou retrabalho.

## 2. Escopo

**Inclui:**

- Proteção da branch `main` no GitHub.
- Verificação de autoria na CI e num gancho local de commit.
- Campos legíveis por máquina em todos os ADRs, com versão única dos princípios.
- Registro `docs/decisoes/regras.yml`, que liga cada ADR verificável ao seu teste.
- Testes automáticos das regras vigentes que podem ser verificadas por código.
- Linha de base das violações conhecidas: violação nova bloqueia; violação antiga fica registrada e só pode diminuir.
- Vínculo entre código, funcionalidade e documentação em cada PR.
- Bloqueio de alteração numa funcionalidade validada com princípios superados.
- Seções de regras do `AGENTS.md` e do `CLAUDE.md` geradas a partir dos ADRs.
- Propagação automática de uma mudança de princípio (análise de impacto e subfases de adequação).
- Revisão de conformidade por agente de IA na CI, para as regras de julgamento.
- Modelo de PR, modelos de issue, varredura de segredos e Dependabot.
- Changelog, tags e releases por versão.
- Auditoria periódica automática.

**Não inclui:**

- Corrigir as violações antigas registradas na linha de base. A correção entra como subfase de adequação na funcionalidade dona, quando ela for alterada, ou na trilha de qualidade.
- Mudar a prioridade das funcionalidades. A prioridade continua sendo decisão do responsável.

## 3. Fontes de verdade

- [ADR-0053](../../decisoes/0053-principios-executaveis-e-mutaveis.md): a decisão que origina esta funcionalidade.
- [ADR-0048](../../decisoes/0048-tronco-unico-de-planejamento.md): tronco único de planejamento.
- [Registros de decisão](../../decisoes/README.md): os princípios vigentes.
- [Governança](../../planejamento/01-governanca.md) e [time de agentes](../../planejamento/02-agentes.md).
- [Casos de uso](casos-de-uso.md) desta funcionalidade.

## 4. Dependências

| Depende de | Por quê | Situação |
|---|---|---|
| Estrutura de documentação de 10/11/2026 | Os ADRs, o catálogo e a governança são a base das verificações | Concluída (PR #239) |
| Permissão de administrador no repositório | Proteção da `main`, varredura de segredos e Dependabot | Disponível (conta `lucasgenial`) |
| Chave de API de um modelo de IA como segredo do GitHub | Revisor de conformidade e agente de impacto na CI | Decisão pendente (seção 8) |

**Funcionalidades que dependem desta:** todas. A partir da subfase 3.1, todo PR passa pelas verificações.

## 5. Fases e subfases

| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 | Proteção da `main`: PR obrigatório, CI obrigatória, sem envio forçado, sem exclusão, só squash | Um envio direto ou forçado para a `main` é recusado pelo GitHub | Pendente | |
| 1.2 | Verificação de autoria: `scripts/verificar_autoria.py` na CI (job `governanca`) e gancho `commit-msg` versionado em `.githooks/` | Um commit com `Co-Authored-By` de IA, "Generated with" ou autor/committer de IA reprova a CI; o gancho remove essas linhas antes do commit | Em execução | 9 testes em `scripts/tests/test_verificar_autoria.py`; `main` atual aprovada (618 commits); histórico anterior à reescrita reprovado (135 violações) |
| 2.1 | Campos de máquina nos ADRs: `id`, `estado`, `versao`, `afeta` (FN e pastas), `verificacao` (teste, revisão de IA ou processo) | Os 53 ADRs têm os campos; a CI valida o formato | Pendente | |
| 2.2 | `docs/decisoes/regras.yml` e `docs/decisoes/VERSAO`; `scripts/verificar_principios.py` | A CI falha se um ADR aceito e verificável não tiver teste registrado; a versão sobe a cada ADR novo ou alterado | Pendente | |
| 2.3 | `AGENTS.md` e `CLAUDE.md` gerados: `scripts/gerar_agents.py` | A CI falha se o arquivo versionado diferir do gerado | Pendente | |
| 3.1 | Testes das regras vigentes: JavaScript externo, sem `confirm`/`alert`/`prompt`, API só JSON, tabelas com `x-tabela`, rodapé de versão, sem prints, autoria | Cada regra verificável vigente tem teste ativo na CI | Pendente | |
| 3.2 | Linha de base das violações conhecidas: `docs/decisoes/linha-de-base.json` | Violação nova reprova; violação antiga não reprova; a contagem só pode diminuir | Pendente | |
| 3.3 | Vínculo código ↔ FN ↔ documentação: PR que altera `apps/` cita uma FN e atualiza o `planejamento.md` dela | Um PR com mudança em `apps/` sem FN ou sem atualização do planejamento reprova | Pendente | |
| 3.4 | Versão dos princípios por funcionalidade: campo "Conforme princípios" no `planejamento.md` | Um PR que altera uma funcionalidade validada com princípios superados por ADR que a afeta reprova | Pendente | |
| 4.1 | Modelo de PR e modelos de issue (nova funcionalidade candidata, correção, decisão do responsável) | Um PR ou issue novo abre no formato certo | Pendente | |
| 4.2 | Varredura de segredos com bloqueio no envio e Dependabot | As duas opções estão ativas no repositório | Pendente | |
| 5.1 | Agente de impacto: `scripts/impacto_principio.py` e definição em `.claude/agents/` | Um ADR novo ou alterado gera a lista de funcionalidades afetadas, as subfases de adequação e o `AGENTS.md` regerado, sem ação humana | Pendente | |
| 6.1 | Revisor de conformidade por IA na CI, para as regras de julgamento | Um PR recebe veredito automático; "reprovado" bloqueia o merge; custo por PR dentro do limite definido | Pendente | |
| 7.1 | `CHANGELOG.md`, tags e releases por versão | Cada versão publicada tem entrada no changelog e release no GitHub | Pendente | |
| 7.2 | Auditoria periódica: fluxo agendado que roda todas as verificações e compara a documentação com o código | Um relatório de divergências é gerado por semana | Pendente | |

Ordem de execução: 1.1, 1.2, 2.1, 2.2, 2.3, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 5.1, 6.1, 7.1, 7.2. As subfases 1.1 e 1.2 são as mais urgentes.

## 6. Contrato técnico

- **Formato do ADR:** um bloco YAML no início do arquivo, com `id`, `estado` (Aceita, Substituída, Revogada), `versao_principios`, `afeta.funcionalidades`, `afeta.caminhos` e `verificacao.tipo` (teste, revisao-ia, processo). Quando o tipo é `teste`, o bloco inclui `verificacao.regra`, com o identificador da regra em `regras.yml`.
- **`regras.yml`:** cada regra tem um identificador, o ADR de origem, o comando de verificação, os caminhos verificados e a gravidade (bloqueia ou avisa).
- **Linha de base:** um arquivo JSON por regra, com as ocorrências conhecidas (arquivo e linha normalizada). A CI compara o resultado atual com a linha de base.
- **Versão dos princípios:** um número inteiro em `docs/decisoes/VERSAO`. O script de verificação sobe o número quando um ADR muda e acusa quando ele não foi atualizado.
- **Gancho local:** `.githooks/commit-msg`, ativado com `git config core.hooksPath .githooks`. Um script de preparação do ambiente faz essa configuração.
- **Revisor de IA:** recebe o diff do PR, os ADRs que afetam os caminhos alterados e os documentos das funcionalidades citadas. Usa a classe de modelo econômica e sobe de classe só quando o risco exige ([modelos de IA](../../planejamento/03-modelos-de-ia.md)). A chave fica como segredo do GitHub e nunca aparece em logs.
- **Desempenho da CI:** as verificações determinísticas rodam em menos de um minuto. O revisor de IA roda só quando o PR altera `apps/`, `docs/decisoes/` ou `docs/funcionalidades/`.

## 7. Interface

Sem interface de usuário. As saídas são mensagens de CI, comentários de PR gerados pelos agentes e relatórios em texto. Nenhum relatório inclui prints, segredos ou dados pessoais.

## 8. Riscos e decisões pendentes do responsável

| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Fornecedor e chave do revisor de IA na CI | Anthropic (Claude), OpenAI (Codex) ou os dois | Começar com um fornecedor; usar o outro como revisor independente quando o risco for alto | Pendente |
| Limite de custo do revisor de IA | Valor máximo por PR e por mês | Definir um teto mensal; a CI usa só os ADRs afetados para reduzir o custo | Pendente |
| Violações antigas | Corrigir agora ou registrar na linha de base | Registrar na linha de base e corrigir como subfase de adequação, sem bloquear o trabalho em andamento | Pendente |
| Regra de proteção da `main` para o próprio responsável | Aplicar a regra também ao administrador ou permitir exceção | Aplicar também ao administrador; exceção só por decisão registrada em ADR | Pendente |

Riscos:

- O revisor de IA pode errar. Por isso, toda regra que pode virar teste determinístico vira teste, e o revisor cobre só o resto.
- Uma regra mal escrita pode bloquear trabalho legítimo. Cada regra nova entra primeiro como aviso por uma semana e depois passa a bloquear.

## 9. Ramos desta entrega

Nenhum até agora.

## 10. Histórico

| Data | Mudança | Por |
|---|---|---|
| 2026-11-10 | Funcionalidade registrada e planejamento escrito, a partir do ADR-0053 | lucasgenial |
| 2026-11-10 | Planejamento aprovado pelo responsável. Execução iniciada pelas subfases 1.2 e 1.1 | lucasgenial |
