# F2 — Consultas atuais, autorização e agregações dos painéis

**Revisão de 09/11/2026. Status: F2 concluída, publicada e validada somente na homologação.** Base de entrada: `origin/main` em `0faa2ec800f59b74c76b84a7e229cd206e575ad1`, branch `perf/f2-consultas-paineis`. A implementação e a correção de compatibilidade foram integradas pelos PR230/231; runtime homologado `a7e08f5cb89f1a7753ef58cb2bb059ceb2bb9c60`. Este documento conserva o desenho de entrada e registra o fechamento de F2 de [194](194-planejamento-desempenho-integral.md), sob os fundamentos aceitos em [195](195-adr-fundamentos-desempenho.md). Evidências anteriores: [196](196-diagnostico-desempenho-f1.md) e insumos operacionais fora do Git.

## Resultado exigido e limites

Entregar a cadeia completa de consultas/autorização e os painéis existentes com menos trabalho repetido, mantendo o contrato HTTP, os resultados pedagógicos e a autoridade atual na execução. Consolidar `ContextoVinculo::resolver` é uma subdivisão, não a entrega de F2. Não publicar esse helper isoladamente nem encerrar a etapa sem revisar os ramos do Dashboard e o percurso completo.

A API continua dona das regras e do banco; Web/BFF e navegador mantêm os contratos atuais e UR4. Não haverá cache de concessões, perfil efetivo ou listas de IDs reutilizadas como autoridade. Não fazem parte desta etapa alteração visual, novos endpoints, React/SPA, cache de conteúdo escolar, redução de auditoria, ajuste de pool ou promoção para produção. Índice só acompanha F2 se o plano medido demonstrar dependência real, com migration aditiva e revisão operacional próprias.

## Evidência que orienta a intervenção

Na F1, a amostra API kernel CLI do professor registrou 2.500 statements, dos quais cinco shapes repetidos 465 vezes somaram 2.325. No coordenador foram 1.445, com cinco shapes repetidos 275 vezes, somando 1.375. São profiling CLI, não contagens obtidas do mesmo request HTTP da tabela de latências. A origem mapeada é a cadeia contexto/espaço/grupo; o backtrace de primeira ocorrência não atribui todas as chamadas à Auditoria.

O custo também aparece na composição indireta: `Usuario::getPerfilAttribute`, `RegimeVinculos`, `EspacoPessoal`, `Escopo`, `Espacos`, `Instituicoes` e `ConsultasPedagogicas`. A substituição de SQL por arrays de IDs resolve autoridade antes da execução e pode congelar uma concessão em builders atrasados. Os painéis ainda possuem consultas dentro de mapas, principalmente professor, aluno, direção escolar, secretaria e aplicador. `PainelTurma` e `AnalisePainelTurma` já possuem agregações/janelas que precisam ser preservadas.

Não se fixa neste plano uma contagem final ou percentual de ganho. Uma SQL maior pode reduzir statements e aumentar custo por linha; EXPLAIN, conteúdo e tempo comparável fazem parte do aceite.

## Desenho de consultas e dependências

### Módulo interno específico, com fachadas compatíveis

Adotar um módulo interno pequeno de composição SQL, denominado `ConsultaVinculo` no desenho de implementação. Não criar uma API HTTP nova nem um framework genérico `PredicateScope`. As fachadas públicas existentes continuam disponíveis; as partes de leitura passam a compartilhar predicados folha em vez de chamar umas às outras recursivamente.

Direção das dependências: políticas estáticas de perfil/configuração e expressões de relógio → composição SQL de contexto/alcance → fachadas de consulta → serviços/controladores. O módulo folha não chama `ContextoVinculo::resolver`, accessor de perfil, `RegimeVinculos::exigir`, `ConsultasPedagogicas` ou `Escopo`; essas chamadas reintroduziriam SQL eager e ciclos. Métodos que compõem subqueries não usam `get`, `pluck`, `exists`, `count` ou materialização implícita para decidir acesso.

Conservar adaptadores de arrays onde o contrato realmente os exige, como seletores e apresentação. Executá-los somente na fronteira correspondente, sem realimentar as consultas críticas com snapshots de concessões. Preservar a diferença histórica entre `null` (alcance irrestrito no regime legado aplicável) e `[]` (nenhum alcance). Não usar `getRawOriginal('perfil')` como substituto do perfil contextual efetivo.

### Autoridade permanente em SQL

Cada consulta protegida deve conter uma barreira SQL que reavalie usuário ativo, vínculo pertencente ao ator, situação/vigência, perfil compatível e habilitado, espaço ativo, referências exclusivas corretas, cliente ativo, propriedade pessoal e pais institucionais válidos no mesmo cliente. Para escola, incorporar a política atual de grupos funcionais e sua exceção. Alcances de escola/turma/aluno, disciplinas/atribuições docentes e integridade dos recursos entram por `EXISTS`/subqueries atuais, sem listas de IDs que concedam acesso depois da mudança.

Podem ser capturados IDs estáveis do ator/contexto e um perfil/tipo esperado para restringir o SQL. Se perfil/tipo mudar, a query antiga deve negar, nunca adquirir os poderes do novo perfil ou conservar os do anterior. Uma nova operação resolve novamente o contexto corrente. Flags do ator antigo, como `$u->ativo`, não substituem o estado atual no banco.

Não depender de `beforeQuery`, callbacks, `toSql()` ou validação apenas na construção: uma callback pode ser consumida durante compilação e o SQL reaproveitado. A barreira precisa permanecer no SQL de `get/count/exists/pluck`, clones, modelos e subqueries, inclusive depois de `joinSub/fromSub/whereIn` ou compilação prévia. Evitar aliases conflitantes e manter agrupamento de `OR` para que nenhuma alternativa escape da barreira comum.

Permissões funcionais continuam explícitas por operação, incluindo catálogo, concessões extras válidas e retiradas. Não inferir uma permissão única por nome de tabela: leitura agregada de notas e leitura nominal podem ter políticas diferentes. Registrar os consumidores que exigem cada permissão e provar revogação nos respectivos pontos de execução. O módulo de alcance não concede uma capacidade funcional ausente.

### Resolver e compatibilidade dos erros

O resolver pode consolidar vínculo/usuário/espaço/cliente/hierarquia e conflito funcional em uma statement, reconstruindo o objeto público com os mesmos campos, valores e tipos. Projeções privadas não vazam no payload. A flag de conflito precisa permitir manter a mensagem específica atual dos grupos; filtrar tudo e devolver apenas o 403 genérico alteraria esse contrato.

Manter `RegimeVinculos::exigir` como fronteira efetiva, inclusive para comandos e consumidores fora dos painéis. Não transformá-lo globalmente em no-op. A composição interna evita chamá-lo recursivamente, mas os pontos públicos continuam rejeitando contexto inválido com status/mensagem equivalentes. Distinguir contexto inválido (403) de recurso fora do alcance (coleção vazia/404 conforme consumidor). O fail-closed da query atrasada não autoriza mudar silenciosamente os erros HTTP atuais.

Remover a consulta de metadados `Schema::hasTable` do caminho quente de contexto somente com a tabela de exceções comprovadamente obrigatória na release e no gate de schema. Ausência inesperada não pode permitir acesso. O caminho de escrita de `GruposFuncionais::conferir` mantém sua compatibilidade própria; não remover sua proteção por arrasto. Preservar convidados/ativos/suspensos, intervalo semiaberto, exclusão do próprio vínculo, escopo usuário+espaço e revogação da exceção.

### Relógio na execução

Vigência de autorização deve ser comparada com relógio SQL corrente em UTC, inclusive quando o builder já foi compilado. Expressão PHP que gera um literal ao compilar não basta: `joinSub/toSql` podem congelá-lo. Usar expressão SQL nativa por driver; no MariaDB, `UTC_TIMESTAMP`, sem depender do timezone da sessão, e equivalente UTC no SQLite.

Não confundir timestamp UTC do vínculo com datas civis de currículo/aplicação: há regras atuais em `America/Sao_Paulo` e outras com data UTC. A implementação deve inventariar essas diferenças e conservar cada regra, sem trocar todas por `CURRENT_DATE`. Decisão de entrada: timestamps UTC com precisão de segundos, compatível com as colunas atuais; para a data civil corrente de São Paulo, `DATE(UTC - 3 horas)`, limitada à regra vigente desde 2019, sem converter instantes históricos nem presumir tabelas de timezone instaladas. Não generalizar esse deslocamento para outras zonas ou períodos; eventual mudança legal da zona exige revisão. Ensaiar a virada de dia nas duas regras separadamente.

Fixtures com Carbon travel precisam controlar também o relógio da conexão descartável de teste. Harness exclusivo de `TestCase`/`testing` sincroniza `SET SESSION timestamp` MariaDB e a função `CURRENT_TIMESTAMP` SQLite, com restauração/isolamento e sem bypass na aplicação produtiva. Isso não autoriza alterar relógio, sessão ou configuração da homologação. Cobrir fronteiras exatas `inicio <= agora`, `fim > agora`, meia-noite e reexecução do mesmo builder. Um ensaio com relógio SQL real complementa os testes determinísticos para evitar prova circular do fake.

## Agregações: cobertura integral dos ramos

| Ramo | Alteração a desenvolver | Semântica a preservar |
|---|---|---|
| Professor | Lotes autorizados para nomes/listas de turmas, alunos ativos e cartões das até 12 provas; demais métricas com subqueries atuais | Ordem/prioridade de status/data, prova própria versus institucional, disciplinas/atribuições, histórico, turma da aplicação, distinção entre nomes agregados e lista ordenada |
| Aluno | Última nota por disciplina em lote e agregação dos períodos quando equivalente | Última por data não é maior nota; datas, ausência de nota, zero/null e desempates existentes; vínculo individual não concede toda a turma |
| Direção escolar e vice | Lotes por turma para alunos, notas, última disciplina e professor | Evitar produto cartesiano entre dimensões; seleção/ordem atual do docente, médias e turma aplicada |
| Secretaria | Agregados independentes por rede/escola em lote | Limites administrativos, vedação nominal vigente, hierarquia e ausência de multiplicação das médias |
| Aplicador | Lotes por prova de turmas, alunos ativos e cartões | Data/status permitidos, sem acesso ao conteúdo vedado, ordem e histórico existentes |
| Coordenador | Reutilizar composição atual/novos predicados e conferir lotes já implantados | Não regredir ganhos AP1 nem duplicar helpers de agregação |
| Administrador da rede e direção de núcleo | Auditar planos e composição de todos os agregados, removendo repetição efetiva quando existir | Top/ranking, universo, contagens, médias e multiplicidade; não redefinir indicador pedagógico incidentalmente |
| Autônomo, espaço pessoal e controles internos | Conferir fronteiras e ramo implementado pertinente | Propriedade pessoal, escola nula, perfil habilitado; controles internos não passam a receber dados pedagógicos |
| Painel da turma/análises | Integrar predicados atuais sem desfazer agregações e janelas existentes | Medianas, períodos, currículo, notas, arquivo/histórico e recortes já validados |

Consultar cartões/notas pela turma de aplicação registrada, respeitando os fallbacks existentes; não trocar por turma atual do aluno. Pré-agregar cada relação de muitos antes de combinar dimensões que multiplicariam linhas. Não aplicar `DISTINCT` indiscriminadamente como correção de cardinalidade. Alteração pedagógica detectada, por exemplo tratamento de zero ou desempate não especificado, deve ser isolada e decidida; não entra silenciosamente como otimização.

## Sequência interna de execução

| Passo | Entrega verificável | Dependência/gate |
|---|---|---|
| F2.1 | Mapa de consumidores/fachadas, contrato e oráculo anterior; matriz de perfil/tempo/escopo | Desenho de segurança e arquitetura revisado antes de implementar |
| F2.2 | Predicados folha de contexto/alcance e relógio, resolver consolidado, fachadas compatíveis | SQL permanece atual após compilação; erros/grupos/schema preservados |
| F2.3 | Integrar Escopo/Consultas/Espacos/Instituicoes sem ciclos/materialização autorizadora; auditoria dos accessors e Regime | Mesma instância e builder pré-mudança em todas as fronteiras críticas |
| F2.4 | Agregações dos ramos da tabela anterior, com lotes por coleção | Contrato/oráculo, cardinalidade e crescimento; pode desenvolver em paralelo após estabilizar interfaces internas de F2.2 |
| F2.5 | Regressão integrada SQLite/MariaDB, EXPLAIN e HTTP/CLI comparável a F1 | Resolver e painel completos, sem cherry-pick do helper como etapa fechada |
| F2.6 | Revisão independente final, CI, candidata/backup/rollback e publicação só em homologação; comparação pós-release | Todos os gates da etapa atendidos; nenhum deploy em produção |

As subdivisões não pedem nova autorização humana para cada detalhe já aprovado. Problemas técnicos são resolvidos dentro da etapa; mudança de contrato, semântica pedagógica, escopo ou princípio do ADR exige explicitação antes de prosseguir com essa mudança.

## Matriz mínima de testes e evidências

1. **Oráculo independente:** registrar fixtures e resultados da versão anterior para todos os perfis/ramos implementados. Comparar status, mensagens, chaves, tipos, ordem, zero/null, médias/medianas e referências históricas. Não escrever somente testes que espelham o novo predicado.
2. **Mesmo ator:** suspender usuário/vínculo/cliente/espaço/pai, expirar vínculo, trocar perfil/tipo, reparentear instituição, retirar turma/aluno/atribuição e revogar exceção/permissão; consultar novamente sem limpar caches, recriar ator ou dar novo login. Cobrir restauração legítima em operação nova sem reaproveitar a concessão antiga.
3. **Builder pré-mudança:** construir, compilar e/ou embutir em subquery antes da mudança e executar depois; reexecutar o mesmo objeto/clones. Incluir `get`, `count`, `exists`, `pluck`, `modelo`, `joinSub/fromSub/whereIn`, execução após expiração e mudança em outra conexão com commit. Preservar a política de isolamento: não prometer revogação no meio de statement/snapshot transacional já iniciado; evitar transações de leitura longas e não trocar isolamento globalmente.
4. **Hierarquia e integridade:** rede/núcleo/escola/pessoal; referências nulas, múltiplas ou inválidas, cliente divergente, proprietário suspenso, recurso com espaço/turma/prova incompatíveis; aluno não enxerga colegas e aplicador não lê gabarito/conteúdo vedado. Regime legado desativado mantém seus contratos.
5. **Grupo e datas:** combinações administrativo/pedagógico, convidados/ativos/suspensos, início/fim exatos, fim nulo, próprio vínculo, exceção permitida/removida, concessão/retirada com validade e currículo/atribuições com data local. Preservar os status 403/422 de seus caminhos próprios.
6. **Cardinalidade e crescimento:** provas 1/4/12 e coleções pequenas/maiores de turmas, alunos e disciplinas, incluindo duplicidades válidas de relações. Contagem não cresce por item onde o lote é esperado. Limites absolutos serão registrados após medir a implementação; cronômetro de CI compartilhada não vira SLA.
7. **Drivers e planos:** SQLite e MariaDB nos caminhos críticos. EXPLAIN de predicados/aggregates, volume e índices existentes; avaliar scans, correlação, linhas e tempo, sem decidir índice só por contar statements. Sem SQL literal/bindings ou PII nos relatórios exportados.
8. **Percurso completo:** repetir os casos HTTP autenticados de F1 com os mesmos cenários/volumes disponíveis e protocolo seguro; API HTTP, Web HTTP, profiling CLI e navegador separados. Conferir custos de contexto/autenticação/auditoria/composição, número de chamadas e memória além do total SQL. Perfis ausentes continuam em fixtures, sem latências fictícias.
9. **Interface e operação:** contrato preservado ainda exige smoke do painel/editor/lista/turma e estados de acesso, sem alterar a apresentação. Havendo delta visual, aplicar o gate de UI/UX vigente. Validar migrações aplicáveis, backup/recuperação, manifesto de dados/arquivos e rollback antes de publicar a etapa.

## Decisões a fechar antes do código correspondente

O desenho de entrada é viável, condicionado à prova de quatro pontos: barreira SQL permanente, equivalência do relógio UTC/data civil, erros e grupo/schema preservados, e ausência de ciclos nas fachadas. São gates de desenho e implementação, não resultados já obtidos. Em particular, adiar consulta de autorização para callback ou conservar arrays capturados como autoridade bloquearia a entrega.

Formalizar a assinatura mínima do módulo interno e dos agregadores para permitir trabalho separado sem duplicar política. Reutilizar regras estáticas/catalogadas; manter uma única definição SQL da validade de contexto. Não criar cache para esconder custo residual. Se uma SQL correlacionada maior regredir, reexaminar plano/predicado e eventual índice justificado, preservando os mesmos gates de acesso.

## Fechamento e rastreabilidade

### Revisão de compatibilidade após a primeira coleta nativa

A comparação nativa da primeira candidata identificou perda de linhas no comparativo por escola do núcleo, apesar de os conjuntos autorizados e os bindings permanecerem iguais. A reprodução descartável em MariaDB 10.11.14 confirmou o problema com notas e alunos fictícios. Os controles de aliases, materialização e projeção separaram a interação de composição/otimização de uma mudança nas regras de acesso; isso não identifica, por si só, uma falha upstream específica do banco.

Nos LEFT JOINs de registros autorizados, usar `ConsultasPedagogicas::paraJuncao` com PK obrigatória e somente as colunas consumidas. O DISTINCT preserva registros distintos pela PK e impede a mesclagem da subconsulta neste percurso. A autorização continua sendo SQL corrente; não guardar IDs, concessões ou conteúdo em cache. Não aplicar este adaptador indiscriminadamente a agregações já agrupadas, INNER JOINs ou consultas gerais. As oito composições existentes foram inventariadas em Dashboard, acompanhamento e relatórios. Novos consumidores precisam declarar projeção, cardinalidade, comportamento sem correspondência e custo do plano.

Conservar também a condição do AVG anterior sobre o retorno bruto do driver: MariaDB pode retornar DECIMAL zero como string. Converter para float antes da condição alterava zero para null. A regressão cobre positivo, zero e ausência de notas, respeitando o contrato anterior de cada driver.

Ordenações antigas sem desempate exigem oráculo específico, não normalização genérica: enumeração de respostas FULL apenas para os valores/linhas empatados demonstrados por SQL, preservando todos os demais campos, tipos e ordens. Há controles separados para última prova da direção, alunos com a mesma média e provas da turma com a mesma data. Qualquer alteração além desses empates, perda de linhas, mudança de tipos ou dados entre as janelas bloqueia o aceite.

As correções passaram no foco SQLite/MariaDB (59 testes, 609 assertivas), incluindo a reprodução RED→GREEN do núcleo; a fixture final de isolamento passou em ambas as engines (sete testes, 57 assertivas). O CI do PR231 passou nos cinco jobs, incluindo 1.039 testes da API e 812 do filtro MariaDB, além dos cinco testes preliminares de progresso. O fechamento inclui `F2JuncoesTest` explicitamente no filtro MariaDB para não depender apenas da suíte completa SQLite e da execução MariaDB local. Produção permanece fora do escopo.

F2 só termina com todas as linhas de cobertura tratadas e verificadas, permissões/revogação atuais, contrato equivalente, crescimento controlado, comparação integrada por método, revisão de segurança/arquitetura e CI. O número de statements/latência final será resultado da medição, nunca promessa deste plano. Conservar evidências sanitizadas no diretório operacional, com SHA/release, método, cenário, população e limitações. Atualizar o status do plano integral e apresentar a tabela de etapas ao responsável.

### Resultado final medido na homologação

A comparação final usou a versão anterior `2cb3305c1e01b3428f16000bb159409455ff0d7c` e a candidata exata `a7e08f5cb89f1a7753ef58cb2bb059ceb2bb9c60`, com os mesmos atores, alvos e conteúdo das 34 tabelas de negócio observadas. Os 12 casos de oito perfis passaram sem divergências, incluindo respostas completas, tipos e estados de acesso. As únicas variações admitidas são as três famílias de empates SQL preexistentes demonstradas acima, com enumeração limitada das respostas completas possíveis; nenhum campo foi removido ou normalizado para obter equivalência. O comparativo do núcleo exige igualdade completa.

| Percurso | Consultas API kernel CLI antes → depois | Mediana API HTTP local antes → depois | Mediana hidratação Web HTTP local antes → depois |
|---|---:|---:|---:|
| Painel do professor | 2.500 → 32 | 2.034,738 → 229,372 ms | 2.142,771 → 329,800 ms |
| Painel do coordenador | 1.445 → 38 | 1.179,308 → 186,676 ms | 1.302,515 → 305,827 ms |
| Painel da direção escolar | 1.507 → 41 | 1.197,836 → 184,183 ms | 1.305,275 → 296,172 ms |
| Painel da direção de núcleo | 880 → 32 | 678,397 → 168,485 ms | 776,441 → 250,103 ms |
| Turma com editor, coordenador | 573 → 49 | 469,766 → 187,183 ms | 1.980,710 → 1.071,710 ms |
| Correção/cartões, coordenador | 321 → 33 | 279,789 → 141,366 ms | 531,052 → 341,324 ms |
| Relatório da prova, coordenador | 1.112 → 39 | 1.077,470 → 324,608 ms | 1.231,426 → 472,439 ms |

As contagens vêm do profiling kernel CLI; as latências, de três requisições HTTP locais sequenciais por caso, usando FPM. Não somar/subtrair esses métodos nem apresentar as medianas como P95, SLA, tempo do navegador pela Internet ou capacidade concorrente. A quantidade de chamadas Web→API foi preservada (oito na turma com editor); deduplicação/composição permanece em F4. Os painéis internos conservaram três consultas, sem otimização atribuída a F2. O caso financeiro apresentou oscilação Web de 71,261 para 144,269 ms sem aumento das consultas/chamadas; a amostra curta não demonstra uma causa nem uma regressão sustentada.

O adaptador de LEFT JOIN mantém custo que precisa continuar acompanhado: no microensaio causal de uma consulta, 9,757 → 10,984 ms, com planos maiores. Esse microensaio exclui a preparação do contexto e não substitui os ganhos do percurso completo. Crescimento em volume e capacidade seguem em F5/F6; não houve ajuste global do otimizador, isolamento, relógio, pool ou durabilidade para produzir os resultados.

Backup completo validado antes da troca, comparação de banco/schema/ledger/arquivos durante a pausa e verificações pós-publicação passaram. Não houve migration nem alteração de dados escolares. Tokens e sessões temporários da coleta foram removidos, mantendo a auditoria normal. A proteção adicional, a página pública independente e os serviços foram preservados. A conferência autenticada no Chrome complementa a medição nativa; não houve mudança de interface nesta etapa.

Os recibos, hashes, planos e coletas sanitizadas permanecem no diretório operacional, fora do Git. O complemento de documentação/CI não altera os bytes de `apps/api` ou `apps/web` da candidata publicada e dispensa nova publicação. F3 é a próxima etapa; produção não foi alterada.
