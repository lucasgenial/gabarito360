# 83 — T5-B3: recuperação de execução e volume

Planejamento revisado após merge #116, antes da implementação. Somente CI descartável com dados fictícios; sem deploy, migrations ou cópias no servidor.

## Ensaio

1. Após o ensaio AU1/B2, preparar um lote de 100 provas com 100 questões por prova e 19 lotes de uma prova com 100 questões. São 119 provas e 11.900 questões, usando os limites máximos de seleção e unidade admitidos.
2. Publicar a primeira unidade do lote maior, editar seu título no destino e preparar a segunda unidade em staging. Confirmar recibos anteriores e persistir as intenções dos 20 lotes. Enfileirar uma rajada de 20 jobs de cópia e um evento sentinela de auditoria atrás deles.
3. Com produtores/consumidores parados, salvar banco completo e Redis, hashes do pacote e manifesto de conteúdo/estado. Parar as origens e restaurar em outros contêineres e volumes, preservando APP_KEY e grants restritos.
4. Antes de consumir, conferir manifesto, estado parcial e fila. Consumir a rajada, confirmar recibos e continuar pelo publicador/Redis reais até concluir. Simular repetição após commit sem confirmação, comprovando ausência de duplicação e preservação da edição no destino.
5. Conferir origem intacta, novos IDs/FKs, 119 mapas, 11.900 questões no destino, staging limpo apenas das unidades publicadas, recibos confirmados e fila vazia.

## Medições e critérios

Registrar somente agregados: duração total, unidades por segundo, p95/máximo do job e espera da sentinela. Um consumidor sequencial, rajada inicial de 20 intenções; publicador acelerado no ensaio, sem esperar o intervalo de um minuto. Isso mede tempo de serviço/backlog, não latência de ponta a ponta do scheduler nem capacidade do servidor.

Critérios de regressão do runner: máximo do job menor que 20 s (margem para timeout de 30 s), sentinela menor que 30 s e consumo total menor que 600 s. Limites são hipóteses de ensaio, não SLA. Falha exige investigar/reduzir rajada antes de ativar. Não aumentar concorrência com base apenas neste ensaio.

As evidências não cobrem arquivos externos (fora de material-1), desastre operacional com outro hardware, perda da APP_KEY ou carga simultânea real. T5-C/D/E e ensaio final de ativação continuam pendentes; nenhuma publicação automática é autorizada.
