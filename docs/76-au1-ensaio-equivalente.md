# AU1 — ensaio completo em ambiente equivalente

Planejamento revisto em 01/11/2026, antes da execução. PR #109 incorporado mediante aprovação. Autorizado ensaio de recuperação completa e volume, sem deploy no servidor de referência.

## Escopo e sequência

1. Conferir backups consistentes anteriores ao corte T2 (docs/54), seus hashes e isolamento. Restaurar apenas IDs temporários livres 9106/9111, sem interfaces em bridge, onboot desligado e rede direta somente entre clones.
2. Aplicar o candidato incorporado somente no clone; recuperar chaves e arquivos, preparar esquema e credencial restrita de auditoria. Não converter vínculos de usuários reais nem habilitar módulos no servidor atual.
3. Usar 1.800.000 eventos inteiramente sintéticos: hipótese de 10.000 requisições auditadas/dia por 180 dias. É um envelope de capacidade declarado, não tráfego medido. Medir população, inventário, cópia mensal, consultas e escrita pela fila com recursos equivalentes aos contêineres originais.
4. Parar escritores dos clones, obter backup completo coerente de ambos com um job cifrado pendente e gerar referências. Restaurar em outro par de volumes/contêineres novos 9206/9211, também sem rede externa.
5. Conferir banco, arquivos, chaves, manifesto mensal e grants; consumir pendência e replay; verificar HTTP, workers, agendador e reinício completo. Distinguir duração observada de RTO/SLA garantido.
6. Registrar somente resultados agregados; remover contêineres e dados temporários após conferir IDs, nomes e volumes. Preservar backups originais e verificar SHA/saúde do servidor atual ao final.

## Limites

O ensaio não autoriza deploy nem ativação. Volume sintético não substitui observação de tráfego ou teste de todos os fluxos de negócio. A carga pode revelar gargalos; falha bloqueia conclusão e exige correção/repetição pertinente. Não extrapolar a recuperação no mesmo host para perda total do hardware ou do local.

## Ambiente conferido

Backups de referência: os dois arquivos finais de 08h20/08h23 BRT do doc. 54, com SHA-256 integral e teste zstd novamente conferidos antes de restaurar. CTs de referência 106/111 permanecem ativos; SHA inicial do servidor: `af4c6d1e9e1d324ebd10e404af149b78a28270a4`.

Clones com os limites originais: banco 2 CPUs/1 GiB RAM/512 MiB swap; aplicação 4 CPUs/2 GiB RAM/1 GiB swap. MariaDB 10.11.14, PHP 8.3.6 e Redis 7.0.15 recuperados dos backups. Candidato `218858296aacb6fec9c44512d60469f51b2dd10c` (merge #109), instalado exclusivamente na cópia isolada.

O inventário inicial conferiu 52 tabelas fora da auditoria, 47 arquivos e 20 campos cifrados com seus índices protegidos. A tabela de auditoria tem conferência própria por mês. Um erro no auxiliar de inventário foi corrigido: a listagem inicial incluía outros schemas do servidor compartilhado; passou a solicitar explicitamente somente o schema da aplicação. A falha ocorreu em leitura e a preparação foi repetida com sucesso.

## Volume e medições observadas

| Operação no clone | Resultado |
|---|---:|
| Inserção de 1.800.000 eventos fictícios em lotes | 101,769 s |
| Inventário integral e digests mensais | 82,645 s |
| Cópia cifrada de setembro, 300.000 eventos | 32,046 s |
| Tamanho do arquivo cifrado | 273.900.000 bytes |
| Verificação integral desse arquivo | 11,283 s |
| Consulta mensal de 100 eventos, p95 de 50 execuções | 2,569 ms |
| Pico de memória alocada pelo PHP na medição | 316.686.336 bytes, cerca de 302 MiB |
| Consumo de 1.000 jobs por dois workers reais | 2,750 s entre primeiro RUNNING e último DONE |

Consultas repetidas com cache aquecido, sem carga concorrente de usuários. A memória reportada é a alocada pelo PHP, não RSS total ou uso do host. São observações dessa carga nesse equipamento, não garantia de capacidade futura. Os eventos de volume são sintéticos; não representam ações reais das pessoas cujos IDs eventualmente coincidam com os números fictícios.

O backup original continha manutenção ativa, e os workers corretamente aguardaram sua retirada. A espera total de 91,166 s inclui esse ajuste; não foi usada como tempo de processamento. Os 2,750 s foram extraídos do journal dos dois workers, com exatamente 1.000 inícios e 1.000 conclusões. Nenhum evento foi duplicado e um evento adicional foi mantido pendente no Redis para o checkpoint.

O primeiro backup dos clones falhou porque o tar unprivileged não atravessava `/root` para acessar o temporário. O auxiliar passou a usar `/var/tmp`, como no roteiro anterior, mantendo a área final privada e os clones parados. Não foram alteradas permissões de `/root` nem apagados backups originais. A repetição passou na integridade e na restauração descritas abaixo.

## Resultado da recuperação completa

[Evidência agregada](operacao/evidencias/au1-recuperacao-volume-2026-11-01.json). A geração e a verificação dos dois backups dos clones levaram 385,93 s. Ambos passaram em zstd e SHA-256, e foram restaurados em volumes novos nos CTs 9206/9211. Os CTs 9106/9111 permaneceram desligados.

Restauração do banco: 60,94 s; aplicação: 259,23 s. Do início da conferência dos backups até a comparação do checkpoint restaurado: 489,21 s (8 min 9 s). Esse intervalo inclui recuperação, boot isolado e comparação, mas não inclui geração do backup, sua transferência de outro local ou a etapa posterior de retomada/reboot.

Antes de liberar os workers, conferiram os hashes e contagens das 52 tabelas fora da auditoria, os 47 arquivos, os 20 campos cifrados e seus índices, os digests de todos os meses e o arquivo mensal de setembro. A credencial recuperada manteve INSERT/SELECT e recusou UPDATE/DELETE/ALTER. `mariadb-check` passou.

O job cifrado pendente foi recuperado e consumido pelos workers reais. A reentrega do mesmo UUID manteve 1.801.001 registros, sem duplicação. Uma requisição HTTP real recusada (401) foi auditada no clone, elevando corretamente a contagem para 1.801.002. Fila vazia e zero jobs falhos.

O systemd reiniciou automaticamente um worker após encerramento intencional do processo. O serviço do agendador executou com sucesso. Depois do encerramento e início completo dos dois CTs, rede isolada e serviços voltaram em 68,12 s: nginx, PHP-FPM, Redis, MariaDB, dois workers e timer ativos; saúde API e página de login 200; contagem mantida, fila vazia e zero jobs falhos. Página de login 200 não significa validação de conversão V1 ou de todos os fluxos autenticados.

## Decisão e próximo passo

O ensaio de recuperação e volume do AU1 foi aprovado tecnicamente. Não houve mudança no código de produção nesta entrega; ela registra roteiro, auxiliares e evidências. As cinco verificações do candidato incorporado passaram.

Ainda não publicar: a release consolidada precisa conferir o corte V1 e demais fases pendentes, credenciais/grants reais, monitoramento e backup final coerente do estado atual. O backup de referência usado aqui é o conjunto consistente anterior ao corte T2, acrescido do candidato e da carga exclusivamente nos clones; não é promessa de RPO atual do servidor.

O próximo pacote de desenvolvimento recomendado é retomar **T5 — cópia de dados entre espaços**, revisando primeiro seu planejamento e mantendo a ativação condicionada à auditoria operacional. Isso não autoriza deploy nem execução de cópias entre espaços reais.

Os auxiliares são pontuais, com IDs, caminhos e SHA fixos, para registrar este ensaio. Não executar automaticamente em outro ambiente nem tratar os tempos como SLA.

## Encerramento

A evidência agregada foi copiada e conferida por SHA-256 antes da limpeza. Os quatro CTs temporários, seus volumes próprios e a área de backups/dados temporários foram removidos após verificar IDs, nomes e identificação do ensaio. Backups originais preservados; SSD desmontado novamente.

Verificação final do servidor de referência: SHA `af4c6d1e9e1d324ebd10e404af149b78a28270a4` mantido, API health 200, nginx/FPM/Redis/MariaDB/workers/timer ativos. **Nenhum deploy ou migration foi realizado no servidor de referência.**
