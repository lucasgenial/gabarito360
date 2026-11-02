# Auditoria geral

AU1-A prepara a captura da API; AU1-B ainda é necessária antes de concluir AU1. Não existe tela de consulta nesta entrega (AU2).

## Registro e privacidade

`AUDITORIA_ATIVA=false` mantém a captura desligada. Quando habilitada após ensaio, o middleware terminável registra requisições a rotas conhecidas `api/v1/*`, incluindo leituras, alterações e respostas recusadas. Saúde e planos públicos em GET ficam fora. Rotas inexistentes não são guardadas, pois o caminho pode conter segredos.

Cada evento contém UUID, mês e horário UTC, usuário autenticado real/visto, contexto validado antes da operação, método e modelo estático da rota, referências numéricas permitidas, status HTTP e resultado. O modelo de rota identifica entidade e ação sem guardar a URL concreta. Referências em rotas recusadas são o alvo solicitado, não comprovação de vínculo com a entidade. Criações sem ID na rota não leem o corpo da resposta; eventos transacionais específicos continuam identificando a entidade criada.

Contexto inválido/revogado não recebe vínculo nem perfil autorizado. Usuário não autenticado fica nulo. IP é reduzido a rede IPv4 /24 ou IPv6 /48; não se confia em headers de identidade/contexto. O IP observado pode ser o da aplicação web/proxy; esta fase não muda a cadeia de proxies confiáveis.

Não se guardam nomes, documentos, notas, campos enviados, resposta, query string, headers, agente do navegador, tokens, códigos, chaves de envio nem mensagem de exceção. Não há S1/impersonação: real e visto são a mesma identidade autenticada. A futura integração deve fornecer atribuição exclusivamente do servidor.

## Persistência e falhas

O enfileiramento ocorre após a resposta, pela conexão assíncrona `AUDITORIA_QUEUE_CONNECTION` (padrão `redis`, como os workers existentes) e fila `baixa`. O job usa cifragem Laravel com `APP_KEY`; a recuperação da fila depende da conservação segura da chave. Conexões síncronas/null/deferred não são aceitas. O worker precisará consumir `baixa`; configuração e ensaio de ativação ficam pendentes, sem mudanças no servidor agora.

Três tentativas com intervalos de 10/60 segundos; UUID torna reentregas idempotentes. Erros de banco diferentes de duplicação continuam falhando para permitir retry. Falha no enfileiramento gera somente `auditoria_enfileiramento_indisponivel`, sem exceção/payload. A operação principal não é revertida; indisponibilidade da fila pode causar lacuna. Monitoramento e recuperação devem ser ensaiados em AU1-B.

Registro HTTP não prova commit de todas as operações internas e não substitui eventos transacionais de domínio. Processos CLI e jobs de negócio não passam pelo middleware HTTP; integrações explícitas serão necessárias nos módulos que os utilizarem (inclusive T5).

## Integridade e conservação

Não há rotas de manutenção. O modelo rejeita save/delete; a escrita é exclusiva do job no código atual. Isso não é armazenamento inviolável: SQL direto e operações em massa com credenciais de banco podem alterar registros. AU1-B deve definir privilégios, conservação externa, retenção específica e particionamento físico MariaDB. Mês indexado é preparação, não particionamento físico.

Não há expurgo ou agendamento de exclusões. Os 30 dias de logs da aplicação aprovados anteriormente não são aplicados automaticamente à auditoria. A migration aditiva deve entrar somente em release aprovada; nenhum evento histórico é reconstruído.

## Preparação AU1-B

Conferência mensal somente de leitura e roteiro de recuperação em [operação da auditoria](operacao/auditoria.md). O comando não implementa expurgo nem particionamento e não torna a ativação automaticamente aprovada. Decisões e limites em [docs/73](73-au1-b-operacao-auditoria.md).
