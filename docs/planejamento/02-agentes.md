# Modelo de operação multiagente

**Atualizado em:** 10/11/2026, por lucasgenial
**Status:** PROPOSTA. O responsável aprova este modelo antes do uso.

## 1. Objetivo

Este documento define o time de agentes do Gabarito360. Ele diz quem faz cada trabalho, em que ordem e com que limites.

O modelo cumpre a [governança](01-governanca.md) e os portões do [plano mestre](00-plano-mestre.md) (seção 3). Ele não cria prioridade. A prioridade vem do plano mestre.

## 2. Princípios

1. **O gerente orquestra.** O agente `gerente-projetos` localiza o pedido, escolhe os agentes pela matriz (seção 6) e confere a evidência. Ele não implementa código.
2. **Quem implementa não revisa a própria entrega.** O autor de uma mudança nunca emite o parecer sobre ela.
3. **Revisão de UI/UX e de segurança sempre por outro agente.** Os agentes `ui-ux` e `seguranca-privacidade` revisam. Eles não implementam a mudança revisada.
4. **Cada agente edita só a sua área.** Os limites estão na tabela da seção 3. Um agente que precisa de uma mudança fora da sua área devolve o pedido ao gerente.
5. **Decisão é do responsável.** Prioridade, aprovação de planejamento, aceite visual e produção são decisões do responsável. Os agentes preparam e recomendam.
6. **Todos seguem o AGENTS.md:**
   - Nenhum print ou screenshot da aplicação é salvo ou publicado. Registre as conferências visuais por texto.
   - A autoria de commits e PRs é só `lucasgenial`. Nenhuma IA aparece como autora.
   - Escreva no padrão ASD-STE100 moderado.
   - Publique primeiro na homologação. A produção exige decisão expressa do responsável.
   - Toda medição de desempenho usa 1000 usuários simultâneos.
7. **Documentação antes do código.** Sem `planejamento.md` e `casos-de-uso.md` aprovados, a próxima tarefa é escrevê-los.

## 3. Time de agentes

Os arquivos dos agentes ficam em `.claude/agents/` (implementação para o Claude Code). Os papéis e as classes valem para qualquer ferramenta, inclusive o Codex. Sigla "ADR": registro de decisão (`docs/decisoes/`).

| Agente | Papel | Quando entra | Entradas | Saídas | Pode editar | Não pode editar | Classe de modelo |
|---|---|---|---|---|---|---|---|
| `gerente-projetos` | Guardião do tronco. Orquestra o time. | Antes de qualquer trabalho, em pedido novo e ao fim de cada subfase. | Pedido, catálogo, plano mestre. | Roteiro de subfases, classificação do pedido, registro de etapa. | `docs/funcionalidades/**`, `docs/planejamento/**` | Código, AGENTS.md, CLAUDE.md, workflows | equilibrado |
| `arquiteto-software` | Decide estrutura técnica, contratos e limites API/Web. | Planejamento de API, migration, módulo novo ou mudança de contrato. | `planejamento.md`, `docs/arquitetura/*`, ADRs. | Desenho técnico na seção do `planejamento.md`; rascunho de ADR. | `docs/arquitetura/**`, `docs/decisoes/**` (rascunho), seção técnica do `planejamento.md` | Código, mockups, style system | raciocínio-alto |
| `product-design` | Define fluxo, escopo de produto e textos de interação. | Planejamento de tela ou fluxo novo. | `casos-de-uso.md`, `mockups/`, `docs/funcionalidades/*`. | Fluxo, estados, textos e critérios de aceite de produto. | `casos-de-uso.md`, seção de fluxo do `planejamento.md` | Código, `style-system/`, `mockups/` | equilibrado |
| `ui-ux` | Revisa interface: composição, acessibilidade, responsividade, estados. Consulta prévia e parecer final. | Antes de projetar tela (consulta) e após implementar (parecer). | `mockups/`, `style-system/`, `docs/arquitetura/09-*`, PR, conferência no navegador. | Parecer com veredito. | Nada. Só leitura. | Tudo | equilibrado |
| `dados-dashboards` | Define indicadores, consultas, projeções e gráficos dos painéis. | Planejamento de painel, indicador ou relatório. | `docs/arquitetura/03-*`, `08-*`, `10-*`, catálogo de widgets. | Definição de indicador (fórmula, fonte, universo, permissão) e contrato de dados. | Seção de indicadores do `planejamento.md` | Código, migrations | equilibrado |
| `seguranca-privacidade` | Revisa segurança, acesso, LGPD e dados pessoais. | Após implementar, quando a matriz exige; e no planejamento de dados pessoais. | `docs/arquitetura/04-*`, `11-*`, PR, ADRs. | Parecer com veredito. | Nada. Só leitura. | Tudo | raciocínio-alto |
| `desenvolvedor-api` | Implementa API, migrations, jobs e testes da API. | Implementação de regra de negócio, rota JSON ou banco. | `planejamento.md` aprovado, contratos, `apps/api`. | Código da API, testes, PR. | `apps/api/**` | `apps/web/**`, `docs/**` (exceto notas do PR) | equilibrado |
| `desenvolvedor-web` | Implementa telas, BFF, JavaScript externo e estilos. | Implementação de tela ou fluxo. | `planejamento.md` aprovado, mockups, style system, `apps/web`. | Código da Web, testes, PR. | `apps/web/**` | `apps/api/**`, `mockups/`, `style-system/` | equilibrado |
| `qualidade-testes` | Revisa cobertura, critérios de aceite e CI. | Após implementar, antes do merge. | PR, `casos-de-uso.md`, resultado da CI. | Parecer com veredito; lista de testes faltantes. | Nada. Só leitura. | Tudo | equilibrado |
| `desempenho` | Revisa consultas, cache, carga e medição. | Subfase com consulta nova, lista grande, cache ou meta de carga. | `docs/arquitetura/12-*`, ADR-0046, ADR-0047, ADR-0052, PR. | Parecer com veredito; plano de medição com 1000 usuários. | Nada. Só leitura. | Tudo | equilibrado |
| `operacao` | Prepara publicação na homologação: backup, recibo, rollback. | Publicação, mudança de infraestrutura ou incidente. | `docs/operacao/*`, `docs/arquitetura/13-*`, PR mesclado. | Recibo de publicação; roteiro de rollback. | `docs/operacao/**` | Código, produção sem decisão expressa | equilibrado |
| `documentacao` | Escreve e revisa documentos no padrão ASD-STE100. | Subfase que muda comportamento documentado; subfase só de documentação. | Código final, `docs/**`, glossário. | Documentos atualizados e conferidos contra o código. | `docs/visao-geral/**`, `docs/arquitetura/**`, `docs/operacao/**`, `docs/glossario.md` | Código, `docs/planejamento/**`, `docs/decisoes/**` | equilibrado |
| `selecao-modelos` | Recomenda classe e modelo por papel e fornecedor. | Quando o gerente distribui o trabalho de uma subfase. | Subfase ou tarefa, `docs/planejamento/03-modelos-de-ia.md`. | Classe e modelo recomendado por fornecedor e por papel, com justificativa curta. | Nada. Só leitura. | Tudo | economico |

Regras da tabela:

- "Só leitura" quer dizer sem Edit e sem Write. O revisor devolve o parecer na resposta. O gerente registra o parecer no `planejamento.md` ou no PR.
- Duas áreas se sobrepõem em `docs/arquitetura/**`. O arquiteto muda decisões técnicas. A documentação muda texto e fatos conferidos no código.
- A tabela vale para o trabalho do dia a dia. O responsável pode ampliar o limite de um agente, por escrito, para um caso.

## 4. Política de modelos (custo e efetividade)

### 4.1 Neutralidade de fornecedor

O projeto usa modelos de mais de um fornecedor: Anthropic (Claude Code) e OpenAI (Codex). Por isso, este modelo de operação não cita modelos concretos. Ele usa **classes de modelo**.

- O `AGENTS.md` é lido pelo Claude Code e pelo Codex. As regras do time ficam em `docs/planejamento/` e no `AGENTS.md`.
- A pasta `.claude/agents/` é só a implementação para o Claude Code. No campo `model:` do frontmatter, use o mapeamento Anthropic: raciocínio-alto = `opus`, equilibrado = `sonnet`, economico = `haiku`.
- A tabela de classe para modelo concreto, por fornecedor, está em [03-modelos-de-ia.md](03-modelos-de-ia.md).

### 4.2 Classes

| Classe | Use em | Motivo |
|---|---|---|
| raciocínio-alto | Arquitetura. Revisão crítica de segurança e privacidade. | Erro nessas áreas custa caro e é difícil de reverter. |
| equilibrado | Planejamento, desenvolvimento e revisões comuns. | Melhor equilíbrio entre custo e qualidade. |
| economico | Tarefas mecânicas: checagens, mapeamentos, contagens, conferência de links, seleção de modelos. | Custo baixo. A tarefa não exige julgamento. |

### 4.3 Regras

1. A classe padrão de cada agente está na tabela da seção 3. O frontmatter do arquivo do agente usa o mapeamento Anthropic.
2. O gerente consulta o agente `selecao-modelos` ao distribuir o trabalho. O agente devolve a classe e o modelo recomendado por fornecedor.
3. O gerente pode **subir** a classe quando o risco exigir. Exemplos: revisão de UI/UX de fluxo de dados pessoais, desempenho de uma consulta crítica, falha repetida de um agente.
4. O gerente pode **baixar** para economico uma tarefa puramente mecânica de qualquer agente.
5. Parecer de segurança sobre dados pessoais, autenticação ou permissões usa sempre raciocínio-alto.
6. Um revisor independente pode usar outro fornecedor que o do implementador (veja o documento 03).
7. O gerente registra no `planejamento.md` a classe e o modelo usados em cada subfase, e o motivo de qualquer troca.

## 5. Fluxo por subfase

Siga estes passos para cada subfase. O gerente confere cada passo.

1. **Consulta ao gerente.** `gerente-projetos` localiza a subfase, classifica o pedido, confere dependências e escolhe os agentes pela matriz.
   - `selecao-modelos` recomenda a classe e o modelo de cada agente escolhido. O gerente registra a escolha no `planejamento.md`.
2. **Planejamento.**
   - `product-design` define fluxo, estados e textos (se há tela).
   - `ui-ux` faz a consulta prévia de composição (se há tela).
   - `arquiteto-software` define estrutura e contratos (se há API, migration ou módulo).
   - `dados-dashboards` define os indicadores (se há painel).
   - `seguranca-privacidade` revisa o desenho (se há dados pessoais ou acesso).
   - O responsável aprova `planejamento.md` e `casos-de-uso.md`.
3. **Implementação.** `desenvolvedor-api` e `desenvolvedor-web` implementam e escrevem os testes. Eles abrem o PR com a autoria do responsável.
4. **Revisão independente.** Cada revisor obrigatório da matriz emite um parecer:
   - `ui-ux` revisa a interface no navegador, em desktop e celular, nos temas claro e escuro.
   - `seguranca-privacidade` revisa acesso, validação e dados pessoais.
   - `qualidade-testes` revisa cobertura e critérios de aceite.
   - `desempenho` revisa consultas, cache e carga.
5. **Correção.** O implementador corrige os bloqueios. O revisor reavalia. Uma mudança visual posterior exige novo parecer de `ui-ux`.
6. **CI.** Todos os jobs passam. `qualidade-testes` confere o resultado.
7. **Merge.** O responsável (ou a pessoa que ele indicar) mescla o PR. O gerente confere a mensagem final: sem identificação de IA.
8. **Homologação.** `operacao` faz backup, publica só na homologação e gera o recibo.
9. **Documentação.** `documentacao` atualiza os documentos afetados.
10. **Validação do responsável.** O responsável valida na homologação. A produção depende de decisão expressa.
11. **Registro.** `gerente-projetos` atualiza a etapa no catálogo e no `planejamento.md`, com a evidência.

## 6. Matriz: tipo de subfase e agentes obrigatórios

Legenda: **O** = obrigatório. **C** = condicional (veja a nota). **—** = não entra.

| Agente | Tela nova | API ou migration | Painel ou indicador | Dados pessoais | Desempenho | Só documentação | Publicação |
|---|---|---|---|---|---|---|---|
| `gerente-projetos` | O | O | O | O | O | O | O |
| `arquiteto-software` | C (1) | O | C (1) | O | C (1) | — | — |
| `product-design` | O | — | O | C (2) | — | — | — |
| `ui-ux` | O | — | O | C (3) | — | — | — |
| `dados-dashboards` | — | C (4) | O | — | — | — | — |
| `seguranca-privacidade` | C (5) | O | C (5) | O | — | C (6) | O |
| `desenvolvedor-api` | C (7) | O | C (7) | O | C (7) | — | — |
| `desenvolvedor-web` | O | C (7) | O | C (7) | C (7) | — | — |
| `qualidade-testes` | O | O | O | O | O | — | O |
| `desempenho` | C (8) | C (8) | O | C (8) | O | — | C (8) |
| `operacao` | O | O | O | O | O | — | O |
| `documentacao` | O | O | O | O | O | O | C (9) |
| `selecao-modelos` | O | O | O | O | O | C (10) | O |

Notas:

1. Entra se a subfase muda contrato, estrutura de dados ou módulo.
2. Entra se há formulário ou texto novo que o titular vê.
3. Entra se a subfase muda formulário, consentimento ou aviso de privacidade.
4. Entra se a API expõe agregações ou novos campos para gráficos.
5. Entra se a tela muda acesso, formulário com entrada de usuário, upload ou dado de outra pessoa.
6. Entra se o documento descreve segurança, acesso ou privacidade.
7. Entra se o código da subfase está na área desse agente.
8. Entra se há consulta nova, lista com paginação, cache ou rota de uso intenso.
9. Entra se a publicação muda comportamento documentado.
10. Entra se a subfase de documentação tem revisão que usa mais de um agente.

Regra de decisão:

- Em dúvida, o gerente inclui o revisor. Incluir um revisor custa menos que um defeito em produção.
- Uma subfase que combina tipos soma os agentes obrigatórios de todos os tipos.
- Publicação em produção não está na matriz. Ela depende de decisão expressa do responsável (ADR-0018).

## 7. Regras de passagem (handoff)

Cada agente entrega ao próximo um resultado verificável e o registra no lugar definido.

| De | Para | O que entrega | Onde registra |
|---|---|---|---|
| `gerente-projetos` | Todos | Subfase, critério de pronto, dependências, agentes escolhidos, modelo. | `planejamento.md` (seção da subfase) |
| `product-design` | `ui-ux`, `arquiteto-software`, devs | Fluxo, estados, textos, critérios de aceite de produto. | `casos-de-uso.md` |
| `ui-ux` (consulta) | Devs | Orientação de composição e padrões a reutilizar. | `planejamento.md` (seção de interface) |
| `arquiteto-software` | Devs, `seguranca-privacidade`, `desempenho` | Estrutura, contratos, invariantes, riscos. | `planejamento.md` (seção técnica); ADR quando a decisão é durável |
| `dados-dashboards` | Devs, `desempenho` | Indicador: fórmula, fonte, universo, permissão, ordem. | `planejamento.md` (seção de indicadores) |
| Devs | Revisores | PR com descrição, testes, como verificar, riscos e itens não feitos. | PR |
| Revisores | Gerente e devs | Parecer: veredito, bloqueios, ressalvas, o que foi verificado. | Resposta do revisor, copiada para o `planejamento.md` e o PR |
| Devs | `operacao` | PR mesclado, CI aprovada, migrations e variáveis novas. | PR |
| `operacao` | Gerente e responsável | Recibo: backup, versão, dados preservados, rollback. | `docs/operacao/evidencias/` e `planejamento.md` |
| `documentacao` | Gerente | Lista de documentos alterados e fatos conferidos no código. | PR |
| Gerente | Responsável | Evidência completa e pedido de validação. | `planejamento.md`, catálogo |

Regras do parecer:

1. O veredito é APROVADO, APROVADO COM RESSALVAS ou REPROVADO.
2. **Bloqueios** são defeitos que impedem o merge. **Ressalvas** são melhorias que não impedem.
3. Liste bloqueios e ressalvas em seções separadas.
4. Registre o que foi verificado, onde, em que tema e largura. Não anexe imagens.
5. REPROVADO volta ao implementador. O parecer vale só para a versão revisada.
6. A aprovação de um agente não substitui a decisão do responsável.

## 8. Como acionar os agentes

Cada agente é um arquivo em `.claude/agents/<nome>.md`. Acione o agente pelo nome, em linguagem natural ou pela ferramenta de subagentes.

Exemplo de pedido ao gerente:

> Use o agente `gerente-projetos`. Quero iniciar a subfase C3 da FN-056 (tela da agenda). Liste os agentes da matriz e a ordem.

Exemplo de pedido a um revisor:

> Use o agente `seguranca-privacidade`. Revise o PR da subfase N2 da FN-057. Foque em permissões e dados pessoais. Entregue o parecer com veredito.

Exemplo de pedido de implementação:

> Use o agente `desenvolvedor-api`. Implemente a subfase N2 da FN-057 conforme o `planejamento.md` aprovado. Abra o PR sem identificação de IA.

Regras ao acionar:

1. Cite a FN, a subfase e os documentos que o agente deve ler.
2. Diga a classe de modelo apenas quando quiser mudar o padrão (seção 4).
3. Não peça ao agente implementador que revise a própria entrega.
4. Não peça a um agente que edite fora da sua área.
