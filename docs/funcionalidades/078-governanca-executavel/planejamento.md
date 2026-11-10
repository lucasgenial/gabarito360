# 078 — Governança executável — Planejamento

**Implementação:** Parcial
**Etapa:** Em execução
**Conforme princípios:** versão 5
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
| 1.1 | Proteção da `main`: PR obrigatório, CI obrigatória, sem envio forçado, sem exclusão, só squash | Um envio direto ou forçado para a `main` é recusado pelo GitHub | Entregue | 10/11/2026: proteção ativa com os 6 jobs obrigatórios (`governanca`, `telas`, `testes (api)`, `testes (web)`, `integridade-mariadb`, `auditoria-recuperacao`), PR obrigatório (0 aprovações), histórico linear, sem envio forçado, sem exclusão, regra também para o administrador. Repositório só com squash; mensagem do squash = título e descrição do PR; branch apagada após o merge. Teste: envio direto recusado com "GH006 Protected branch update failed" |
| 1.2 | Verificação de autoria: `scripts/verificar_autoria.py` na CI (job `governanca`) e gancho `commit-msg` versionado em `.githooks/` | Um commit com `Co-Authored-By` de IA, "Generated with" ou autor/committer de IA reprova a CI; o gancho remove essas linhas antes do commit | Entregue | 9 testes em `scripts/tests/test_verificar_autoria.py`; `main` atual aprovada (618 commits); histórico anterior à reescrita reprovado (135 violações); PR #241 mesclado com o job `governanca` aprovado |
| 2.1 | Campos de máquina nos ADRs: `id`, `estado`, `versao`, `afeta` (FN e pastas), `verificacao` (teste, revisão de IA ou processo) | Os 53 ADRs têm os campos; a CI valida o formato | Entregue | 55 ADRs com cabeçalho (0001 a 0053, mais os novos 0054 e 0055): 11 `teste`, 26 `revisao-ia`, 18 `processo`; os 6 substituídos com `substituida_por`. A versão de cada ADR é a versão do conjunto em `VERSAO` |
| 2.2 | `docs/decisoes/regras.yml` e `docs/decisoes/VERSAO`; `scripts/verificar_principios.py` | A CI falha se um ADR aceito e verificável não tiver teste registrado; a versão sobe a cada ADR novo ou alterado | Entregue | 13 regras (6 ativas, 7 planejadas para a 3.1); `scripts/executar_regras.py` roda as ativas na CI sem mudar o workflow; versão dos princípios 2 (hash dos ADRs); 7 testes em `scripts/tests/test_principios.py` |
| 2.3 | `AGENTS.md` e `CLAUDE.md` gerados: `scripts/gerar_agents.py` | A CI falha se o arquivo versionado diferir do gerado | Entregue | `AGENTS.md` gerado dos campos `agents_*` dos ADRs aceitos, com a versão dos princípios no topo; regra `R-AGENTS-GERADO`; 3 testes. Nenhum item do AGENTS.md anterior se perdeu. O `CLAUDE.md` não é gerado: ele não contém regras, só aponta para o AGENTS.md |
| 3.1 | Testes das regras vigentes: JavaScript externo, sem `confirm`/`alert`/`prompt`, API só JSON, tabelas com `x-tabela`, rodapé de versão, sem prints, autoria | Cada regra verificável vigente tem teste ativo na CI | Entregue | 5 regras novas ativas na CI com `scripts/verificar_regras_codigo.py`: `R-JS-EXTERNO`, `R-SEM-DIALOGO-NATIVO`, `R-API-SO-JSON`, `R-TABELA-PADRAO`, `R-VERSAO-RODAPE`. Sem prints e autoria já estavam ativas. Total: 11 regras ativas. 6 testes em `scripts/tests/test_regras_codigo.py` |
| 3.2 | Linha de base das violações conhecidas: `docs/decisoes/linha-de-base.json` | Violação nova reprova; violação antiga não reprova; a contagem só pode diminuir | Entregue | `docs/decisoes/linha-de-base.json` com 67 violações antigas: 49 de JavaScript inline (20 views), 13 diálogos nativos (9 arquivos), 3 tabelas fora de `x-tabela` (`home/index`, `turmas/_modelos`), 1 view de tela na API (`welcome.blade.php`) e 1 versão escrita à mão (`apps/web/.../welcome.blade.php`). A chave não depende do número da linha. Violação corrigida exige reduzir a linha de base; a CI recusa linha de base maior que a da `main` |
| 3.3 | Vínculo código ↔ FN ↔ documentação: PR que altera `apps/` cita uma FN e atualiza o `planejamento.md` dela | Um PR com mudança em `apps/` sem FN ou sem atualização do planejamento reprova | Entregue | `scripts/verificar_fn_no_pr.py`, regra `R-FN-NO-PR` ativa. Lê a FN no título e na descrição do PR e nas mensagens dos commits. Exige o `planejamento.md` de uma FN citada no mesmo PR. Modelo de PR com o campo `FN:`. 6 testes em `scripts/tests/test_fn_no_pr.py`. Os 8 PRs anteriores com mudança em `apps/` não citavam FN; a regra vale daqui em diante |
| 3.4 | Versão dos princípios por funcionalidade: campo "Conforme princípios" no `planejamento.md` | Um PR que altera uma funcionalidade validada com princípios superados por ADR que a afeta reprova | Entregue | Campo `Conforme princípios` nos 78 planejamentos (versão 1; a FN-078 na versão atual). `VERSAO` guarda a versão em que cada ADR mudou (histórico real: ADR-0004 na 2, ADR-0053 na 3 e na 5, ADR-0048 na 4). `scripts/verificar_conformidade_fn.py`, regra `R-VERSAO-PRINCIPIOS-FN` ativa. 6 testes em `scripts/tests/test_conformidade_fn.py`. Todas as 13 regras do registro estão ativas |
| 4.1 | Modelo de PR e modelos de issue (nova funcionalidade candidata, correção, decisão do responsável) | Um PR ou issue novo abre no formato certo | Entregue | Modelo de PR reescrito: FN e subfase, revalidação dos princípios, contrato, validação, publicação e conferência final (prints, segredos, autoria). Três formulários de issue (funcionalidade candidata, correção, decisão do responsável), com rótulos próprios; issue em branco desativada. `scripts/verificar_documentacao.py` verifica os modelos na CI. Tela "New issue" conferida no GitHub em 10/11/2026: os 3 formulários e o link do catálogo aparecem; a issue em branco fica só para mantenedores |
| 4.2 | Varredura de segredos com bloqueio no envio e Dependabot | As duas opções estão ativas no repositório | Parcial | Varredura de segredos e bloqueio no envio já estavam ativos em 10/11/2026. Alertas do Dependabot ativados em 10/11/2026. As atualizações automáticas (de segurança e de versão) esperam a decisão do responsável da seção 8, porque abrem PRs com autor `dependabot[bot]` |
| 5.1 | Agente de impacto: `scripts/impacto_principio.py` e definição em `.claude/agents/` | Um ADR novo ou alterado gera a lista de funcionalidades afetadas, as subfases de adequação e o `AGENTS.md` regerado, sem ação humana | Pendente | |
| 6.1 | Revisor de conformidade por IA na CI, para as regras de julgamento | Um PR recebe veredito automático; "reprovado" bloqueia o merge; custo por PR dentro do limite definido | Pendente | |
| 7.1 | `CHANGELOG.md`, tags e releases por versão | Cada versão publicada tem entrada no changelog e release no GitHub | Pendente | |
| 7.2 | Auditoria periódica: fluxo agendado que roda todas as verificações e compara a documentação com o código | Um relatório de divergências é gerado por semana | Pendente | |

Ordem de execução: 1.1, 1.2, 2.1, 2.2, 2.3, 3.1, 3.2, 3.3, 3.4, 4.1, 4.2, 5.1, 6.1, 7.1, 7.2. As subfases 1.1 e 1.2 são as mais urgentes.

## 6. Contrato técnico

- **Formato do ADR:** um cabeçalho em YAML restrito no início do arquivo, com `id`, `titulo`, `estado` (Aceita, Substituída, Revogada), `substituida_por`, `data`, `afeta_funcionalidades`, `afeta_caminhos`, `verificacao` (teste, revisao-ia, processo) e `regras`. Quando `verificacao` é `teste`, `regras` cita pelo menos uma regra de `regras.yml`. Os campos `agents_secao`, `agents_ordem` e `agents_resumo` levam a regra ao AGENTS.md. O modelo está em `docs/decisoes/_modelo.md`.
- **`regras.yml`:** cada regra tem um identificador, o ADR de origem, o comando de verificação, os caminhos verificados e a gravidade (bloqueia ou avisa).
- **Linha de base:** um arquivo JSON (`docs/decisoes/linha-de-base.json`), com as ocorrências conhecidas de cada regra (arquivo e hash da linha normalizada). A CI compara o resultado atual com a linha de base e com a linha de base da `main`.
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
| Violações antigas | Corrigir agora ou registrar na linha de base | Registrar na linha de base e corrigir como subfase de adequação, sem bloquear o trabalho em andamento | Aplicada a recomendação na subfase 3.2 (10/11/2026). O responsável pode mudar a decisão; corrigir tudo agora exige mudar 20 views e 4 arquivos JavaScript, com revisão de UI |
| Atualizações automáticas do Dependabot | (a) Só alertas: o responsável aplica as correções em PR próprio. (b) PRs do Dependabot, com o commit final reescrito pelo responsável. (c) PRs do Dependabot com autor `dependabot[bot]` | (a) Só alertas. O ADR-0051 quer só lucasgenial como autor. Um PR do Dependabot também reprovaria as regras `R-FN-NO-PR` e `R-VERSAO-PRINCIPIOS-FN` sem exceção nova | Pendente |
| Regra de proteção da `main` para o próprio responsável | Aplicar a regra também ao administrador ou permitir exceção | Aplicar também ao administrador; exceção só por decisão registrada em ADR | Aplicada também ao administrador (aprovação da FN-078 em 10/11/2026) |

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
| 2026-11-10 | Subfases 1.1 e 1.2 entregues: `main` protegida e verificação de autoria ativa (#241) | lucasgenial |
| 2026-11-10 | Subfases 2.1 a 2.3 entregues: campos nos ADRs, registro de regras, versão dos princípios e AGENTS.md gerado | lucasgenial |
| 2026-11-10 | Subfases 3.1 e 3.2 entregues: 5 regras de código ativas e linha de base com 67 violações antigas | lucasgenial |
| 2026-11-10 | Subfase 3.3 entregue: PR que altera `apps/` cita a FN e atualiza o planejamento dela | lucasgenial |
| 2026-11-10 | Subfase 3.4 entregue: versão dos princípios por funcionalidade e revalidação obrigatória antes de alterar o código | lucasgenial |
| 2026-11-10 | Subfase 4.1 entregue: modelos de PR e de issue | lucasgenial |
| 2026-11-10 | Subfase 4.2: alertas do Dependabot ativados; atualizações automáticas aguardam decisão | lucasgenial |
