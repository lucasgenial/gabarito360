# Conservação e recuperação da auditoria — preparação, não ativação

Responsável: administrador da infraestrutura. Uso: antes e depois de restauração autorizada, ou em investigação de lacunas. Revisado em 01/11/2026; ainda não executado em ambiente equivalente.

Pré-requisitos: release e esquema compatíveis, acesso de leitura ao banco, manifesto de referência protegido, destino isolado e autorização específica antes de parar serviços ou restaurar backups. Os comandos abaixo são executados em `apps/api`.

## Comando somente de leitura

`php artisan g360:conferir-auditoria` emite contagens e SHA-256 por mês, sem identidades ou conteúdo de eventos. Prazo vazio significa política ainda não definida. `--dias=180` é simulação explícita; não aprova a política e não exclui dados. Meses em `AUDITORIA_MESES_PRESERVADOS=2026-01,2026-02` ficam preservados integralmente. Configuração inválida impede a conferência.

Só um mês completamente anterior ao corte UTC pode ser classificado fora do prazo. Não há comando de expurgo, `DROP PARTITION` ou agendamento de limpeza. Backups também precisam de política própria; restaurar uma cópia antiga não autoriza apagar ou republicar seus dados.

O inventário percorre a auditoria inteira em ordem de mês/UUID com cursor e memória limitada na aplicação. Drivers podem manter buffers internos: ensaio de volume equivalente continua obrigatório. Rodar fora do pico, com escritores parados para comparação de backup. Uma varredura durante tráfego é diagnóstico, não fotografia certificada de backup.

## Preservação excepcional

Antes de qualquer futura eliminação: responsável registra referência do incidente/apuração, meses cobertos, justificativa, data e responsável pela revisão em repositório operacional restrito; adiciona todos os meses ao bloqueio, gera manifesto e preserva cópia cifrada separada. Revisar periodicamente. Remover o bloqueio exige decisão registrada e nova conferência; não é automático. Não colocar nomes, motivos livres ou dados do incidente na configuração da aplicação.

## Ensaio de restauração autorizado, ainda pendente no ambiente equivalente

1. Preparar destino isolado, com integrações externas e envio de mensagens desligados. Parar escritores da origem do ensaio e drenar a fila `baixa`; registrar pendências e falhas sem revelar payload.
2. Salvar a saída de `g360:conferir-auditoria` em manifesto restrito. Guardar backup consistente do banco e Redis e chaves de cifra pelo procedimento existente, em duas cópias. SHA-256 do manifesto/backup deve ficar em referência independente; um digest sozinho não impede adulteração conjunta.
3. Restaurar banco, fila e chaves somente no destino do ensaio. Comparar por `g360:conferir-auditoria --comparar=/caminho/manifesto.json`. Código de saída diferente de zero bloqueia avanço. Comparação valida conteúdo armazenado, não comprova eventos que nunca chegaram à fila.
4. Consumir fila cifrada de teste, simular indisponibilidade/reinício, conferir reentrega sem duplicação, ausência de segredos nos logs e esvaziamento da fila. Gerar manifesto posterior separadamente, pois consumir pendências pode legitimamente acrescentar eventos.
5. Medir duração, espaço, latência de gravação e consultas mensais em volume representativo. Não usar o teste lógico de 1.000 eventos como benchmark de capacidade nem como restauração de contêiner.

## Privilégios antes de ativar

Usar identidade dedicada de gravação de auditoria com somente INSERT/SELECT na tabela, separada das credenciais de migration e conservação; leitor operacional somente SELECT. A aplicação atual usa a conexão geral: a separação técnica das conexões e o teste de negação de UPDATE/DELETE/ALTER ainda são requisito de ativação, não garantia entregue por este PR. Não conceder privilégios neste procedimento sem validar o ambiente e a autorização da janela.

Não executar exemplos genéricos de REVOKE em credenciais compartilhadas: isso pode interromper outras funções. Documentar os grants exatos e ensaiar numa cópia. O administrador do banco continua tecnicamente capaz de alterar dados; preservação externa deve oferecer a evidência independente.

## Monitoramento e particionamento

O alerta `auditoria_enfileiramento_indisponivel` agora passa pela lista positiva L3, sem contexto livre. Monitorar esse alerta, jobs falhos, atraso/profundidade de `baixa` e espaço disponível; falha deve abrir incidente e registrar possível lacuna. Limites e destinatário operacional serão definidos no ensaio, sem e-mails automáticos nesta entrega.

Não aplicar particionamento nativo por mês ao esquema atual: a chave UUID global conflita com a exigência de incluir o mês nas chaves únicas. Projeto posterior deve ensaiar ledger global de deduplicação + tabela mensal, ou arquivo mensal verificável, incluindo replay tardio e preservação parcial. Índices atuais são mantidos. Fonte: [MariaDB — limitações](https://mariadb.com/docs/server/server-usage/partitioning-tables/partitioning-limitations).

## Falha, interrupção e escalonamento

| Sinal | Conduta |
| --- | --- |
| `politica_definida=false` | Registrar prazo pendente; não interpretar meses como liberados para exclusão. |
| `restauracao_confere=false` ou saída 1 | Parar a aprovação da restauração; conferir se origem estava congelada, manifesto corresponde ao backup e fila foi consumida na etapa correta. Não sobrescrever a referência para esconder divergência. |
| Conferência indisponível | Conferir esquema/configuração/permissão de leitura e validade do manifesto; não despejar credenciais ou dados de eventos no terminal. |
| Job falho ou alerta de enfileiramento | Acionar administrador, preservar evidências e registrar intervalo da possível lacuna; só repetir job após diagnosticar a falha e confirmar chave de cifra. |

O comando de conferência não precisa de rollback: não altera dados. Para falha de restauração, manter o destino isolado e a origem preservada; retomar somente pelo roteiro de backup existente e pela decisão do administrador. Não reverter o banco original nem desabilitar proteções para obter resultado verde. Canal de escalonamento é o acompanhamento deste projeto com o responsável; nenhum envio automático foi configurado.
