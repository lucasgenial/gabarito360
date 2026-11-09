# Planejamento integral de desempenho — etapas de entrega

**Revisão de 09/11/2026. Status: F0 a F4 concluídas. F2, F3 e F4 publicadas somente na homologação; resultados e limites em [197](197-f2-consultas-paineis.md), [198](198-f3-arquivos-cache.md) e [199](199-f4-carregamento-interacao.md). F5/F6: diagnóstico medido em [200](200-f5-f6-diagnostico-catalogo.md); as mudanças dependem de decisões do responsável (semântica da busca nacional e ajuste do buffer pool do MariaDB).**

Direção humana: “Não faça recorte, faça por etapas” e “Planeje isso antes!”, seguida de autorização para executar o plano apresentado. Este plano substitui a proposta de encerrar e publicar uma otimização isolada do resolver. Não apresentar uma alteração pontual como conclusão da etapa de desempenho da API. A revisão de arquitetura anterior aprovava somente aquele experimento limitado; sua implementação será reavaliada no conjunto de F2.

## Objetivo e estado de partida

Melhorar o aplicativo já publicado na homologação e estabelecer fundamentos para as funcionalidades seguintes: consultas proporcionais ao volume, menos transferências repetidas, carregamento regional e personalização segura. Preservar contrato da API, isolamento por vínculo/perfil, dados escolares, gabaritos, histórico e auditoria. Produção não será alterada.

Editor e toast estão concluídos na homologação (PR227/228). Há medições parciais CLI e navegador da release82abc67 em docs/192 e no relatório operacional. A medição CLI mais recente da release2cb3305, ainda anterior às otimizações, encontrou 1.445 consultas no painel do coordenador, 518 na lista de turmas e 573 na turma; evidência operacional perf02a-baseline-api-kernel-cli-2cb3305.json. Esses números não medem autenticação HTTP, BFF, mutex e renderização completos, nem representam todos os perfis. Identificar a release vigente em cada comparação. Não declarar percentuais de conclusão ou economia sem base correspondente.

A tentativa local de consolidar consultas em ContextoVinculo.php e seus testes fica separada, sem commit/merge/deploy nesta revisão. Será reavaliada no conjunto de F2. Nenhuma otimização parcial será publicada como etapa fechada.

## Etapas e critérios de fechamento

| Etapa | Entrega completa | Critério de conclusão | Situação |
|---|---|---|---|
| F0 — Fundamentos | ADR de fronteiras API/App, classificação de cache, regras de invalidação, telemetria e critérios de PR/CI | Regras registradas e aplicáveis às novas entregas, separando invariantes de metas ainda a calibrar | Concluída: ADR195, AGENTS e template de PR |
| F1 — Diagnóstico completo | Matriz de rotas e perfis existentes; navegador → Web/BFF → API → SQL; consultas, planos, chamadas, assets e payloads | Gargalos atribuídos por camada, release/cenário/volume registrados e protocolo de comparação repetível | Concluída no escopo disponível: oito perfis/12 casos e oito capturas Chrome; métodos e limites em docs/196 |
| F2 — Consultas e autorização dos painéis | Cadeia de contexto/escopos, agregações e remoção de SQL em loops dos painéis existentes | Contrato equivalente, revogação atual preservada, contagens sem crescimento indevido por item, testes SQLite/MariaDB e comparação do percurso completo | Concluída na homologação: PR230/231, comparação nativa de 12 casos sem divergências, testes e revisões independentes; dados/arquivos preservados, sem migration. Resultados em docs/197 |
| F3 — Arquivos e cache técnico | Versionamento/cache de assets, runtime de gráficos consolidado e sprites públicos otimizados | Navegação fria/quente e nova release corretas; bytes reduzidos, proteção da homologação e revisão visual preservadas | Concluída na homologação: PR233, release 9aee7fa, cache por versão de conteúdo no nginx; turma 8.159.322 → 39.688 B com cache. Resultados em docs/198 |
| F4 — Carregamento e interação | Composição inicial mínima, regiões/fotos/opções sob demanda, UR4 deduplicado e lifecycle de gráficos/layout | Esqueleto regional preservado, menos trabalho desnecessário, ausência de respostas tardias aplicadas e regressões de foco/scroll/mobile | Concluída; publicada na homologação (F4-A, F4-B e F4-C/D, ver [199](199-f4-carregamento-interacao.md)) |
| F5 — Volume e trabalhos demorados | DTOs/projeções, paginação/ordenação, índices pelos planos e filas de documentos conforme gargalos comprovados | Totais corretos, contratos compatíveis, consultas/payloads controlados e jobs idempotentes com reautorização | Diagnóstico medido ([200](200-f5-f6-diagnostico-catalogo.md)): gargalo único na busca nacional do catálogo; aguarda decisão de semântica |
| F6 — Capacidade e operação | Ajustes justificados de pools/OPcache/MariaDB e orçamento de manutenção/backup | Experimento controlado demonstra benefício sem OOM, perda de durabilidade, auditoria ou recuperabilidade | Buffer pool de 1 GB aplicado na homologação, com busca nacional ~48% mais rápida ([200](200-f5-f6-diagnostico-catalogo.md)); pools FPM após observar a memória |

### F0 — Fundamentos

1. Consolidar as decisões de docs/192 em ADR e orientações de implementação/PR.
2. API permanece dona das regras e do banco; Web compõe apresentação via HTTP. Nenhuma dependência direta da Web em tabelas de negócio.
3. Cache estrutural e assets não concedem acesso. Credenciais, decisões de permissão e conteúdo escolar não serão colocados em cache persistente do navegador. O cache técnico persistente do servidor também exclui dados escolares e títulos livres; qualquer cache de conteúdo exige classificação e desenho próprio, sem autorização implícita neste plano.
4. Estabelecer medição sanitizada: contagens, tempos, tamanhos e fingerprints, sem SQL literal, bindings, aluno, título livre, e-mail, cookie ou token.

### F1 — Diagnóstico completo (PERF-01)

1. Inventariar os painéis/fluxos atuais e os perfis que realmente os acessam. Incluir os diferentes ramos de autorização e as telas de correção/fotos, relatórios e editor nos percursos pertinentes; não inventar portais futuros.
2. Separar tempo do documento, hidratação, espera da sessão, autenticação/contexto, chamadas BFF→API, API, SQL, composição e renderização.
3. Identificar os fingerprints dominantes, repetição por operação/registro, planos SQL e custos de download/execução das bibliotecas.
4. Definir volume, ator/cenário, condições fria/quente, orçamento de amostragem e limites de carga antes da coleta. Não produzir percentis a partir de uma única amostra nem comparar métodos diferentes como se fossem iguais.
5. Fechar um mapa ordenado de gargalos e uma baseline reproduzível para as próximas etapas. Medições ficam fora do Git e não alteram dados de negócio.

### F2 — Consultas e autorização (PERF-02/03)

1. Revisar a cadeia completa de contexto, perfil, permissões, hierarquia e escopos utilizada pelos painéis, incluindo chamadas recursivas e accessors.
2. Consolidar leituras e predicados SQL atuais sem guardar concessões no objeto, por requisição ou por TTL. Uma query construída não pode ganhar autoridade por um snapshot obsoleto; provar o comportamento na execução e nas operações seguintes.
3. Substituir SQL por registro nos painéis por agregações em lote autorizadas, mantendo médias, zeros, nulos, histórico da aplicação, ordenação e desempates.
4. Cobrir os ramos de perfil implementados, espaço pessoal/institucional, grupos funcionais e exceções, conta/cliente/hierarquia suspensos, revogação e alteração de alcance com o mesmo ator.
5. Comparar conteúdo/status/tipos e contagens em volumes pequenos e maiores. Medir o mesmo percurso antes/depois, incluindo custos que saírem do banco e passarem para a aplicação.
6. Revisão independente de segurança e arquitetura, CI e publicação só após concluir e validar o conjunto da etapa.

Índices comprovadamente necessários para os planos de F2 podem acompanhar esta etapa, com migration aditiva e revisão operacional próprias; não adiar uma dependência real artificialmente até F5.

### F3 — Arquivos e cache técnico (PERF-07/08 e verificação PERF-11)

1. Classificar assets públicos técnicos separadamente de HTML, JSON, fotos e documentos protegidos.
2. URLs por versão/hash e política de cache compatível com proteção Basic e troca de release; segunda navegação reaproveita o mesmo corpo e uma nova versão carrega os novos bytes.
3. Consolidar ECharts por página e otimizar sprites públicos de avatares/brasões, preservando qualidade pixelart e licenças. Carregar PDF/HEIC somente quando necessário.
4. Conferir bootstrap e invalidação do cache estrutural do editor já entregue; não guardar títulos livres nem árvore escolar em cookies/localStorage/sessionStorage.
5. Validar frio/quente, atualização, fallback, tema claro/escuro e desktop/mobile com UI/UX e Product Design antes do fechamento.

### F4 — Carregamento e interação (PERF-04/05/06/14)

1. Definir o DTO mínimo e os estados/erros da composição antes de entregar primeiro a região visível e carregar abas/opções secundárias sob demanda, sem disparar tudo em paralelo indiscriminadamente. A evolução por volume/paginação permanece em F5.
2. Carregar fotos por visibilidade e preservar placeholders, erros regionais e refresh individual.
3. Deduplicar referências/escritas e varreduras de UR4, respeitando CSRF, revisão da sessão, contexto e URLs públicas estáveis.
4. Agrupar atualização de gráficos/layout por ciclo, descartar callbacks antigos e desconectar observadores ao sair; preservar estados, teclado, impressão e abas reabertas.
5. Medir chamadas, bytes, tarefas longas e estabilidade visual; revisão independente dos percursos desktop/mobile.

### F5 — Volume e trabalhos demorados (PERF-09/10/12)

1. Evoluir projeções e paginação por contratos novos/opt-in quando necessário, sem quebrar os consumidores atuais nem alterar a busca silenciosamente.
2. Separar totais do recorte da página exibida. Ordenação estável e filtros no banco antes da materialização.
3. Definir índices somente com EXPLAIN e cenário/volume demonstrados; qualquer migration deve ser aditiva, revisada e ter plano de recuperação.
4. Isolar exportações/relatórios demorados em filas quando medição justificar, com idempotência, estados explícitos, prioridade, retry e reautorização na execução/entrega.

### F6 — Capacidade e operação (PERF-13/15)

1. Medir memória privativa, backlog, CPU, IO, buffer pool e OPcache após reduzir o trabalho evitável.
2. Ajustar uma hipótese operacional por experimento comparável, com rollback próprio e limites documentados. Não enfraquecer flush/durabilidade, autenticação ou isolamento.
3. Conferir manutenção, backup, restauração e concorrência com filas; preservar a recuperação completa e os arquivos existentes.

## Regra de execução e publicação

As subdivisões acima são internas: serão executadas sem pedir autorização por item. Revisar o planejamento detalhado na entrada de cada etapa usando os resultados da anterior; atualizar a tabela ao final. Consultar agentes de arquitetura/segurança e, quando houver interface, UI/UX/Product Design antes de implementar e revisar independentemente ao terminar.

Cada etapa precisa concluir implementação aplicável, validação, revisão e CI antes do merge e da publicação correspondente somente na homologação. Não publicar cada helper/subdivisão como entrega isolada. F0/F1 podem terminar em decisões/evidências sem deploy se não houver mudança de código/configuração; instrumentação necessária é uma entrega controlada. Produção exige autorização específica posterior do responsável.

Antes de cada publicação: candidata identificada, backup verificado, plano de rollback e comparação de dados/arquivos/schema. Capturas e relatórios operacionais não entram no Git. Nenhum resultado de desempenho autoriza apagar gabaritos/histórico, mudar a foto salva, desativar proteção ou promover produção.
