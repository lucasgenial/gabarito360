# Diagnóstico e correção da demora no MariaDB do CI

O job `integridade-mariadb` do PR216 levou29m44; a suíte principal passou755testes/8.163asserções, com1caso pulado e5/24 de progresso em execução separada. O resultado foi aprovado, mas o tempo atrasou as entregas. Esta investigação é separada da correção da prévia dos painéis.

## Evidências anteriores à alteração

- Homologação, coleta somente leitura: MariaDB10.11.14, sem transações ou espera de locks no intervalo; `SELECT1` pela conta da aplicação com mediana quente0,189ms, máximo0,280ms. CPUociosa, sem swap, sem espera deI/O na coleta. Contador de consultas acima do limiar configurado10s era zero. A coleta breve sem carga não certifica desempenho em picos.
- O trait `BancoCurriculoDescartavel` executa `migrate:fresh` entre casos para preservar commits e auditoria reais, em vez de envolver toda a suíte numa transação artificial. O esquema atual possui94migrations e130tabelas. Esse isolamento continua necessário.
- Comparação local controlada: mesmo MariaDB10.11.14, mesmo código, buffer64MiB, redo32MiB, flush de commit1 e mesma sequência de1.542/1.546consultas. Três reconstruções emext4 levaram41,845/41,533/42,433s; emtmpfs2,520/2,173/2,315s. O tempoSQL dominou a execução em disco. Isso confirma custo deI/O na preparação, sem atribuir o atraso ao banco operacional.
- O MariaDB descartável emtmpfs passou36testes/685asserções, incluindo preferências, concorrência e auditoria, em102,48s. A execução longa em disco foi abreviada após preservar as três medições; o baseline funcional completo é o CI aprovado anterior. Valores locais não são previsão do tempo no GitHub.

## Correção proposta e limites

Somente o serviço do job `integridade-mariadb` recebe `/var/lib/mysql` emtmpfs de1GiB. A [documentação Docker](https://docs.docker.com/engine/storage/tmpfs/) define esse armazenamento como temporário; o contêiner já nasce descartável e não recebe dados de usuários. O job de recuperação/backup continua com seu armazenamento anterior.

O guard deCI confere imagem, banco exato, tmpfs real, capacidade e `innodb_flush_log_at_trx_commit=1` antes da suíte. Não mudar parâmetros de commit, conexões, permissões, filtros, cenários ou asserções. A suíte completa e os testes reais de concorrência permanecem. Nenhuma configuração ou dado de homologação/produção é alterado por esse patch. Não aplicar tmpfs a banco operacional.

## Validação e planejamento

| Trabalho | Situação deste candidato | Fechamento |
|---|---|---|
| Diagnóstico CI/homologação | Concluído com medições | Evidências operacionais `mariadb-diagnostico-homologa.json` e `mariadb-ci-baseline-disk.json` |
| Ensaio controlado tmpfs | Concluído,36/685 | `mariadb-ci-benchmark.json` e logRAM |
| Guard e tmpfs noCI | Implementado | Revisão e execução real noGitHub pendentes |
| Suíte completa otimizada | Pendente | Mesmos755/8.163 mais progresso5/24; comparar tempo efetivo |
| Integração | Pendente | Cinco checks aprovados e revisão do candidato |

Artefato final previsto: `operacao-20261102/mariadb-ci-entrega-final.json`, somente após o CI real. Nenhum ganho do GitHub é declarado antes dessa execução.
