# F5/F6 — diagnóstico de entrada: volume e capacidade

**Data:** 09/11/2026. **Status:** medição concluída na homologação; decisões pendentes do responsável. **Escopo:** somente homologação. Produção fora do escopo.

## Volumes reais (snapshot da homologação)

Os dados escolares são pequenos: 414 alunos, 2.160 respostas de cartão e 180 questões de gabarito. O volume está no catálogo de instituições:

| Tabela | Linhas | Tamanho |
|---|---|---|
| `catalogo_unidades` | ~234 mil | 55 MB |
| `catalogo_instituicoes` | ~218 mil | 15 MB |
| `catalogo_identificadores` | ~209 mil | 47 MB |
| `catalogo_entradas` | ~207 mil | 271 MB |
| `catalogo_observacoes` | ~205 mil | 145 MB |

Listas escolares sem paginação (turmas, professores, alunos da turma) ficam abaixo de centenas de linhas neste volume. Não há gargalo de volume comprovado nelas, e paginar contratos sem necessidade quebraria consumidores (regra 1 da F5).

## Gargalo comprovado: busca nacional por texto no catálogo

Medições de `ConsultarCatalogoInstituicoes::buscar` na homologação, só de leitura, mediana de 3:

| Filtros | Total | Tempo |
|---|---|---|
| texto "escola municipal", sem UF | 23.300 | 3,48 s |
| texto de 4 letras, sem UF | 24 | 3,25 s |
| texto sem resultado, sem UF | 0 | 1,46 s |
| texto + UF | 2.093 | 0,13 s |
| só UF | 16.251 | 0,14 s |

Cada busca nacional roda duas consultas (contagem e página) de cerca de 1,5 s. A API da homologação tem dois processos FPM: uma busca nacional ocupa metade da capacidade por segundos.

### Causas medidas

1. **Plano.** O otimizador parte de `catalogo_municipios` (5.503 linhas) e busca as entradas por município. Isso gera cerca de 207 mil leituras de linhas largas só para avaliar o `LIKE`. Forçar a ordem publicação → entradas → municípios dá a mesma contagem em 0,86 s, contra 1,5 s.
2. **Buffer pool de 128 MB** (padrão do MariaDB), contra mais de 500 MB do catálogo. A mesma varredura das entradas leva 0,19 s com as páginas em memória e 0,59 s sem elas. A taxa de leitura do disco é de cerca de 5% das requisições ao buffer pool.
3. **`LIKE '%termo%'`** exige avaliar todas as entradas. Mesmo um índice estreito só com o nome levou 0,55 s. Nenhum índice B-tree elimina esse custo.

## Propostas e decisões necessárias

| Item | Etapa | Efeito esperado | Risco/decisão |
|---|---|---|---|
| Fixar a ordem de junção da busca nacional, com a mesma semântica e testes de equivalência de contagem e ordem | F5 | ~1,5 s → ~0,9 s por consulta | Baixo; mudança de código |
| `innodb_buffer_pool_size` de 128 MB para 1 GB na VM 120 (3,8 GB de RAM, 2,7 GB disponíveis, sem swap), com reversão a quente e drop-in versionado | F6 | Varreduras em memória (~0,2–0,5 s) | Mudança operacional no MariaDB compartilhado da VM. **Bloqueada nesta sessão pela permissão de alterar recurso compartilhado; precisa de autorização expressa** |
| Busca nacional por palavras (FULLTEXT/n-gram) ou exigir UF/município para texto curto | F5 | Abaixo de 100 ms | **Muda o resultado da busca** (substring → palavras, ou novo filtro obrigatório). Decisão de produto do responsável; a regra da F5 proíbe alterar a busca silenciosamente |

A F6 também precisa medir, com a F4 publicada, a memória privativa dos pools FPM (API: 2 processos; web: 3) e o OPcache. Ajustes de pool só fazem sentido depois do buffer pool, um experimento por vez, com reversão própria.

## Experimento F6-1: buffer pool de 1 GB (aplicado em 09/11/2026)

Autorizado pelo responsável. Nesta versão do MariaDB (10.11.14), `innodb_buffer_pool_size_max` fica fixo no tamanho do início do serviço. Por isso o ajuste a quente foi recusado, e a mudança exigiu reiniciar o MariaDB, também autorizado.

O operador pausou o worker e o timer de cópias, instalou o drop-in versionado [`infra/homologacao/mariadb/90-g360-perf-f6.cnf`](../infra/homologacao/mariadb/90-g360-perf-f6.cnf), conferiu com `my_print_defaults` e reiniciou. Depois conferiu o tamanho, a durabilidade e as tabelas, e restaurou as unidades. Se algo falhasse, removeria o drop-in e reiniciaria de novo. Recibo em `/root/g360-homologa/infra/perf-f6-buffer-pool/recibo.json`, na VM.

| Item | Antes | Depois |
|---|---|---|
| Buffer pool | 128 MB | 1.024 MB |
| `innodb_flush_log_at_trx_commit` | 1 | 1 (inalterado) |
| Tabelas do banco | 130 | 130 |
| Reinício do MariaDB | — | 0,8 s |
| Leituras do disco / requisições ao buffer pool | ~4,5% | ~0,04% |
| Busca nacional "escola municipal" (23.300 resultados) | 3,49 s | 1,79 s |
| Busca nacional de 4 letras | 3,25 s | 1,70 s |
| Busca nacional sem resultado | 1,46 s | 0,80 s |
| Busca com UF / só UF | 0,13 / 0,14 s | 0,13 / 0,14 s |
| Memória disponível da VM | 2,69 GB | 2,79 GB logo após; o buffer pool ocupa até 1 GB à medida que enche |

Aplicação conferida no Chrome depois do reinício, sem erros. Worker ativo e timer de cópias ativo; o timer de agenda continua inativo, como antes.

Reversão: remover o drop-in e reiniciar o MariaDB.

A busca nacional continua perto de 1,8 s, porque cada requisição faz duas consultas (contagem e página) que avaliam `LIKE '%termo%'` em todas as entradas. O próximo ganho sem mudar a semântica é a ordem de junção da F5, estimada em mais ~40%. Abaixo de 100 ms, só com a decisão de produto descrita acima. Observar a memória do MariaDB com o buffer pool cheio antes de qualquer ajuste de pools FPM.
