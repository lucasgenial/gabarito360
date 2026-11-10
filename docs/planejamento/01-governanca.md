# Governança do planejamento

**Atualizado em:** 10/11/2026, por lucasgenial
**Decisão do responsável:** 09/11/2026 (tronco único) e 10/11/2026 (catálogo, orquestrador e padrão de escrita).

## 1. Objetivo

Esta governança mantém um único planejamento para o Gabarito360. Ela impede que planos paralelos mudem a prioridade sem decisão do responsável.

Antes desta governança, cada entrega criava planos próprios. Esses planos cresciam e passavam à frente do plano principal. Esta governança substitui essa prática.

## 2. Documentos e papéis

O planejamento tem três tipos de documento. Cada tipo tem um papel diferente.

| Documento | Papel | Quem atualiza | Quando atualizar |
|---|---|---|---|
| [`docs/funcionalidades/README.md`](../funcionalidades/README.md) | **Catálogo.** A lista oficial de funcionalidades: identificador, nome, identificador antigo, domínio, implementação e etapa. | Gerente de projetos | Ao criar uma funcionalidade e a cada mudança de etapa |
| `docs/funcionalidades/NNN-slug/planejamento.md` e `casos-de-uso.md` | **Detalhe.** Objetivo, escopo, subfases, evidências, dependências e casos de uso de uma funcionalidade. | Agentes de planejamento e de execução | Antes e durante a execução |
| [`docs/planejamento/00-plano-mestre.md`](00-plano-mestre.md) | **Orquestrador.** A ordem de execução, os portões, as decisões pendentes, o saneamento e a fila de produção. | Gerente de projetos, com aprovação do responsável | Quando o responsável prioriza ou decide |

Regras de consistência:

- Uma funcionalidade aparece uma única vez no catálogo.
- O plano mestre não repete o catálogo. Ele cita cada funcionalidade pelo identificador FN-NNN.
- A implementação e a etapa no cabeçalho do `planejamento.md` são iguais às do catálogo.
- O cabeçalho declara `Conforme princípios: versão N`: a versão dos princípios contra a qual o planejamento foi revalidado. Antes de alterar o código da funcionalidade, revalide e suba N para a versão atual ([ADR-0053](../decisoes/0053-principios-executaveis-e-mutaveis.md)).
- A CI verifica estas regras com `scripts/verificar_funcionalidades.py`.

Os documentos numerados `docs/NN-*.md` são histórico e evidência. Eles não definem prioridade. Quando divergem do plano mestre, vale o plano mestre.

Os modelos dos documentos de funcionalidade estão em [`docs/funcionalidades/_modelos/`](../funcionalidades/_modelos/).

## 3. Tronco e ramos

**Tronco:** o catálogo e o plano mestre. Toda funcionalidade, módulo ou capacidade do sistema entra no tronco.

**Ramo de entrega:** um plano que nasce durante uma entrega. O ramo serve só para concluir essa entrega. Exemplos: uma subdivisão técnica, uma correção ou um ajuste da revisão. O ramo fica no `planejamento.md` da funcionalidade em execução, como subfase. O ramo termina com a entrega.

Quando um pedido novo aparece durante uma entrega, faça estes passos:

1. Verifique se o pedido serve só para concluir a entrega atual.
2. Se serve só para a entrega atual, registre o pedido como subfase no `planejamento.md` da funcionalidade.
3. Se o pedido vai além da entrega atual, registre uma funcionalidade nova no catálogo. Use a etapa "Candidata" e registre a origem.
4. Não execute a funcionalidade candidata. Ela aguarda a priorização do responsável.
5. Se a funcionalidade nova é um módulo transversal, liste as funcionalidades que dependem dela. Essas funcionalidades aguardam o módulo.
6. Termine a entrega atual com o que pertence a ela. No lugar do recurso futuro, use estado vazio ou "Em breve", conforme a regra de design.

**Exemplo:** a entrega dos painéis de turma mostrou a necessidade de uma agenda. A agenda serve também à marcação de provas e às visitas pedagógicas. Por isso, a agenda é uma funcionalidade nova e transversal (FN-056). Os painéis, a marcação de provas e as visitas aguardam a agenda.

É proibido:

- Criar uma série de documentos que defina prioridade fora do plano mestre.
- Executar uma funcionalidade sem `planejamento.md` e `casos-de-uso.md` aprovados.
- Criar, dentro de uma funcionalidade, uma versão local de um módulo transversal do tronco. Exemplos: uma segunda agenda ou uma segunda lista de notificações.

## 4. Ciclo de vida de uma funcionalidade

A coluna "Etapa" do catálogo registra o ciclo de vida.

| Etapa | Significado | Quem muda |
|---|---|---|
| Candidata | Registrada no catálogo, sem prioridade. | Gerente de projetos registra. O responsável decide. |
| Priorizada | Tem posição numa onda do plano mestre. | Responsável |
| Em planejamento | Os documentos estão em escrita ou revisão. | Gerente de projetos e agentes de planejamento |
| Planejamento aprovado | O responsável aprovou os documentos. A execução pode começar. | Responsável |
| Em execução | Uma subfase está em desenvolvimento. | Agentes de execução |
| Em homologação | A entrega está publicada só na homologação. | Agentes de execução |
| Concluída | A entrega está validada. A produção depende de decisão expressa. | Responsável |
| Adiada ou Substituída | A funcionalidade está fora da fila. O motivo está no planejamento. | Responsável |

Uma subfase só termina com evidência: PR mesclado, CI aprovada, revisão independente quando há interface e recibo de publicação na homologação.

## 5. Papel do gerente de projetos

O agente `gerente-projetos` (`.claude/agents/gerente-projetos.md`) é o guardião do tronco. Chame o agente antes de cada trabalho, ao fim de cada subfase e quando um pedido novo aparece.

O agente faz estes passos:

1. Localiza o pedido no catálogo e no plano mestre: funcionalidade, fase e subfase.
2. Classifica o pedido: ramo da entrega atual ou funcionalidade nova.
3. Verifica se o `planejamento.md` e o `casos-de-uso.md` existem e estão aprovados. Se não estão, a próxima tarefa é escrever esses documentos.
4. Lista as fases e as subfases restantes até a entrega final, com dependências e portões.
5. Ao fim de cada subfase, atualiza a etapa no catálogo e no `planejamento.md`, com evidência.
6. Aponta desvios: trabalho sem plano, ramo que virou módulo, prioridade alterada sem decisão ou documento numerado que contradiz o tronco.

Os agentes de execução seguem a indicação do gerente. Eles não começam uma funcionalidade fora da fila.

## 6. Regras que continuam em vigor

O AGENTS.md e o CLAUDE.md continuam em vigor. Destaques:

- Os mockups e o style system são fonte de verdade.
- A API é dona do banco e das regras de negócio.
- A publicação é feita somente na homologação. A produção exige decisão expressa.
- Nenhum print ou screenshot da aplicação fica no repositório ou em serviços de imagem.
- A autoria de commits e de PRs é somente do responsável, sem identificação de IA.
- Toda medição de desempenho usa 1000 usuários simultâneos.
- Todo documento novo ou revisado segue o padrão ASD-STE100 moderado.
