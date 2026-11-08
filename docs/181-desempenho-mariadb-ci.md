# Diagnóstico e correção da demora no MariaDB do CI

O job `integridade-mariadb` do PR216 levou29m44; a suíte principal passou755testes/8.163asserções, com1caso pulado e5/24 de progresso em execução separada. O resultado foi aprovado, mas o tempo atrasou as entregas. Esta investigação é separada da correção da prévia dos painéis.

## Evidências anteriores à alteração

- Homologação, coleta somente leitura: MariaDB10.11.14, sem transações ou espera de locks no intervalo; `SELECT1` pela conta da aplicação com mediana quente0,189ms, máximo0,280ms. CPUociosa, sem swap, sem espera deI/O na coleta. Contador de consultas acima do limiar configurado10s era zero. A coleta breve sem carga não certifica desempenho em picos.
- O trait `BancoCurriculoDescartavel` executa `migrate:fresh` entre casos para preservar commits e auditoria reais, em vez de envolver toda a suíte numa transação artificial. O esquema atual possui94migrations e130tabelas. Esse isolamento continua necessário.
- Comparação local controlada: mesmo MariaDB10.11.14, mesmo código, buffer64MiB, redo32MiB, flush de commit1 e mesma sequência de1.542/1.546consultas. Três reconstruções emext4 levaram41,845/41,533/42,433s; emtmpfs2,520/2,173/2,315s. O tempoSQL dominou a execução em disco. Isso confirma custo deI/O na preparação, sem atribuir o atraso ao banco operacional.
- O MariaDB descartável emtmpfs passou36testes/685asserções, incluindo preferências, concorrência e auditoria, em102,48s. A execução longa em disco foi abreviada após preservar as três medições; o baseline funcional completo é o CI aprovado anterior. Valores locais não são previsão do tempo no GitHub.

## Correção proposta e limites

Revisão do planejamento após o perfil de consultas: três casos de autorização executaram26.818consultas,20,856s deSQL e34,22s totais. Cerca de4.945resoluções repetiam cinco consultas de contexto; nenhuma dessas consultas isoladas ultrapassou7,2ms. O primeiro refactor mínimo reduziu apenas456consultas (1,7%) e não melhorou o tempo total no ensaio; por isso não encerra o diagnóstico. A investigação prossegue na cascata `Espacos::filtrar → ids → escolaIds`, preservando validação atual a cada entrada pública, sem cache de concessões. Revisão independente de segurança antes do aceite.

Plano de implementação: resolver uma vez a assinatura de acesso; eliminar validação imediatamente duplicada nas permissões; materializar os IDs de espaços numa única operação contextual validada; reutilizar somente o perfil local durante a construção de uma consulta de provas. Preservar guards de escrita/consumidores, contrato HTTP, hash, filtros, SQL de integridade, flags e comportamento legado/interno. Testar equivalência de alcance/hash e revogação entre chamadas com a mesma instância. Medir novamente a mesma sequência antes de declarar ganho.

Somente o serviço do job `integridade-mariadb` recebe `/var/lib/mysql` emtmpfs de1GiB. A [documentação Docker](https://docs.docker.com/engine/storage/tmpfs/) define esse armazenamento como temporário; o contêiner já nasce descartável e não recebe dados de usuários. O job de recuperação/backup continua com seu armazenamento anterior.

O guard deCI confere imagem, banco exato, tmpfs real, capacidade e `innodb_flush_log_at_trx_commit=1` antes da suíte. Não mudar parâmetros de commit, conexões, permissões ou filtros. A suíte completa e os testes reais de concorrência permanecem, com regressões adicionais de alcance e revogação. Nenhuma configuração ou dado de homologação/produção é alterado por esse patch. Não aplicar tmpfs a banco operacional.

## Resultado medido e revisão do escopo

No GitHub, o candidato apenas comtmpfs (`ec56e97`, run42446199934) passou os cinco checks: MariaDB20m15, suíte755/8.163+progresso5/24, contra29m44 do baselinePR216. O tempo da suíte principal foi1.178,62s. Isso confirma ganho na preparação, mas não resolve a repetição da aplicação.

Com o refactor contextual ampliado, os mesmos três casos/82asserções no MariaDB10.11.14 descartável passaram com19.065consultas (−28,9%),15,600sSQL (−25,2%) e27,29s totais (−20,3%) ante26.818/20,856s/34,22s. São medições locais controladas de regressão, não promessa de latência de cada tela ou medição de carga do servidor. Cada chamada pública revalida o contexto; nenhum estado de autorização é memoizado. A primeira extração mínima isolada não bastou e está registrada acima.

SQLite focal passou106testes/1.769asserções, incluindo contrato, autoria, hierarquia, grupos funcionais e contexto interno/pessoal. O candidato ampliado exige novo CI completo e revisão independente antes do merge e da publicação apenas na homologação. Não há migrations, índices novos, alteração de rotas, payloads, sessão ou dados existentes.

## Validação e planejamento

| Trabalho | Situação deste candidato | Fechamento |
|---|---|---|
| Diagnóstico CI/homologação | Concluído com medições | Evidências operacionais `mariadb-diagnostico-homologa.json` e `mariadb-ci-baseline-disk.json` |
| Ensaio controlado tmpfs | Concluído,36/685 | `mariadb-ci-benchmark.json` e logRAM |
| Guard e tmpfs noCI | Validado noGitHub, cinco checks aprovados |755/8.163+5/24;20m15 contra29m44 |
| Eliminar consultas contextuais redundantes | Implementado e medido localmente | Mesmos3/82, −28,9%consultas; revisão e CI ampliado pendentes |
| Suíte completa do candidato ampliado | Pendente | Preservar cobertura anterior e regressões novas |
| Integração | Pendente | Cinco checks aprovados e revisão do candidato |

Artefato final previsto: `operacao-20261102/mariadb-ci-entrega-final.json`, somente após o CI real. Nenhum ganho do GitHub é declarado antes dessa execução.
