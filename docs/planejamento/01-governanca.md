# Governança do planejamento

**Decisão do responsável (09/11/2026).** O planejamento do Gabarito360 passa a ter **um único tronco**: o [plano mestre](00-plano-mestre.md). Este documento define como ele é mantido e como os agentes trabalham a partir dele. Ele substitui a prática anterior, em que planos ramificados das entregas cresciam e passavam a ter prioridade sobre o plano principal.

## 1. Estrutura

| Onde | O quê |
|---|---|
| `docs/planejamento/00-plano-mestre.md` | Fonte única de prioridade e status: lista ordenada de funcionalidades, fases, subfases, dependências e situação de cada uma |
| `docs/planejamento/01-governanca.md` | Estas regras |
| `docs/funcionalidades/<NNN-slug>/planejamento.md` | Planejamento da funcionalidade: objetivo, escopo, fases e subfases, critérios de pronto, dependências e riscos |
| `docs/funcionalidades/<NNN-slug>/casos-de-uso.md` | Casos de uso da funcionalidade: atores, perfis, fluxos principal e alternativos, regras, telas e critérios verificáveis |
| `docs/funcionalidades/_modelos/` | Modelos obrigatórios dos dois documentos |
| `docs/NN-*.md` (numerados) | Histórico e registros de execução. Continuam válidos como evidência, mas **não definem prioridade**. Quando divergirem do plano mestre, vale o plano mestre |

Toda funcionalidade, nova ou já existente, tem os dois documentos. Uma funcionalidade que já existe é documentada a partir do que está implementado e marcada como tal.

## 2. Tronco e ramos

**Tronco (plano mestre):** toda funcionalidade, módulo ou capacidade do sistema.

**Ramo de entrega:** um plano que nasce durante uma entrega serve **apenas** para concluí-la. Exemplos: subdivisões técnicas, correções, ajustes de revisão. Ele vive dentro do `planejamento.md` da funcionalidade em andamento, como subfase, e termina com ela.

Quando, durante uma entrega, surge algo que **não é só para resolver aquela entrega**:

1. Registrar como **nova funcionalidade candidata** no plano mestre, com origem, por exemplo "surgiu na entrega dos painéis de turma".
2. O responsável decide a **prioridade** em comparação com a lista existente. Até lá, ela fica em "Aguardando priorização" e **não é executada**.
3. Se ela é um **módulo transversal**, usado por várias funcionalidades, registram-se as funcionalidades que dependem dela. Exemplo: a agenda, usada pelos painéis, pela marcação de provas e pelas visitas pedagógicas. Essas funcionalidades **aguardam a conclusão do módulo** para receber as mudanças que ele traz, em vez de criar soluções locais paralelas.
4. A entrega em andamento termina com o que lhe cabe. No lugar do recurso futuro, usa estado vazio ou "Em breve", conforme a regra de design vigente.

O que fica proibido:

- Abrir uma nova série de documentos que passe a definir prioridade fora do plano mestre.
- Executar uma funcionalidade sem `planejamento.md` e `casos-de-uso.md` aprovados.
- Implementar dentro de uma funcionalidade uma versão local de um módulo transversal que já está no tronco, como uma segunda agenda ou uma segunda lista de notificações.

## 3. Ciclo de vida de uma funcionalidade

| Estado | Significado | Quem muda |
|---|---|---|
| Candidata | Registrada no tronco, sem prioridade | Qualquer agente (registro), responsável (decisão) |
| Priorizada | Tem posição na fila e dependências mapeadas | Responsável |
| Em planejamento | `planejamento.md` e `casos-de-uso.md` em escrita ou revisão | Gerente de projetos e agentes de planejamento |
| Planejamento aprovado | Documentos aprovados pelo responsável; pode iniciar | Responsável |
| Em execução | Subfases em andamento, uma por vez, com revisão | Agentes de execução |
| Em homologação | Publicada só na homologação, aguardando validação | Agentes de execução |
| Concluída | Validada na homologação; produção somente por decisão específica | Responsável |
| Adiada / Substituída | Fora da fila, com motivo e referência | Responsável |

Subfase concluída exige evidência: PR mesclado, CI, revisão independente quando houver interface e recibo de publicação na homologação.

## 4. Papel do gerente de projetos

O agente `gerente-projetos` (`.claude/agents/gerente-projetos.md`) é o guardião do tronco. Antes de cada trabalho, e sempre que uma entrega termina ou um pedido novo aparece, ele:

1. Localiza o pedido no plano mestre: qual funcionalidade, fase e subfase.
2. Classifica: ramo da entrega atual ou nova funcionalidade.
3. Confere se `planejamento.md` e `casos-de-uso.md` existem e estão aprovados. Se faltarem, a próxima tarefa é escrevê-los.
4. Lista as fases e subfases restantes até a entrega final, com dependências e gates (revisão, CI, homologação, decisão de produção).
5. Ao fim de cada subfase, atualiza o status no plano mestre e no `planejamento.md`, com evidência.
6. Aponta desvios: trabalho sem plano, ramo que virou módulo, prioridade alterada sem decisão, documento numerado contradizendo o tronco.

Os agentes de execução seguem o que o gerente indicar e não iniciam funcionalidade fora da fila.

## 5. Regras herdadas que continuam valendo

AGENTS.md e CLAUDE.md continuam em vigor: mockups e style system como fonte, a API como dona do banco, as regras de UI/UX, a publicação somente na homologação, a produção só com decisão expressa, a ausência de prints da aplicação e o padrão de carga de 1000 usuários simultâneos nas medições de desempenho.
