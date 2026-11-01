# 50 — T2: validação de recuperação e candidato de ativação

O responsável aprovou o merge do PR #81 e autorizou validar ambiente equivalente, backup/restauração e release de ativação, sem deploy. PR #81 incorporado em `1d42000e3b145bf756ef5f000583a9adb623eda2`. O responsável reforçou que deve ser avisado quando houver versão estável; publicação depende de nova autorização.

## Trabalho autorizado

Validar backups locais e uma cópia atual de leitura, em WSL isolado, sem restaurar contêineres no Proxmox e sem interromper produção. Configurações/chaves e dados ficam em diretório restrito, fora do Git. Confrontar versões, contagens, integridade, leitura de campos protegidos e relações escolares antes/depois do corte na cópia. Nenhum dado real é usado nos testes comuns ou enviado por e-mail/gateway.

Preparar candidato com adaptador T2 ativo por configuração versionada. O candidato não é implantado por merge. B5 deverá impedir reabertura automática quando o adaptador estiver ativo e as constraints ainda não estiverem completas; `--manter-manutencao` continua sendo o caminho da janela. Acrescentar verificação explícita de prontidão do esquema para a retomada. Regressões legadas permanecem explicitamente no modo legado; suíte MariaDB e ensaio de candidato validam modo novo.

Resultados e limitações serão registrados antes da decisão de estabilidade. Recuperação a partir de cópia online não substitui o ponto final coerente com escritores parados na janela real.

## Resultado do ensaio autorizado

A produção foi consultada somente para leitura: CT 111 permanece em `dc9949e3690744b72c5071d919955c09ad2be9b8`, PHP 8.3.6; CT 106 usa MariaDB 10.11.14. Nginx, PHP-FPM, Redis, dois workers e timer estavam ativos. Fuso do servidor: America/Sao_Paulo. Não houve deploy, parada, migração, alteração de acesso ou exclusão em produção.

Os arquivos automáticos de 31/10 do CT 106 (2.205.995.208 bytes) e CT 111 (8.742.225.459 bytes) passaram em `zstd -t` e foram extraídos integralmente em WSL. O MariaDB do CT 106 iniciou dentro do filesystem restaurado, em namespace sem rede, sem replicação/eventos e apenas socket local. `mariadb-check` aprovou as 39 tabelas do banco restaurado. A cópia do CT 111 contém APP_KEY, mas não DADOS_HASH_KEY: ela antecede a publicação de L3 das 17:08 UTC. Isso não indica corrupção do backup antigo; significa que ele não comprova recuperação do estado atual cifrado.

Uma fotografia atual foi capturada por leitura (`mariadb-dump --single-transaction --skip-lock-tables` e tar de configurações/arquivos públicos), restaurada em banco local exclusivo e conferida com `mariadb-check`. Chaves e dados ficaram fora do repositório, sob diretório root 0700. A API candidata executou sem interfaces externas, transporte de e-mail em memória e sem workers. Somente as chaves de cifra/busca foram carregadas; credenciais de serviços externos não foram importadas.

[Evidência sanitizada](operacao/evidencias/t2-copia-restaurada-2026-10-31.json):

- Adaptador ativo por configuração versionada; expansão, preenchimento, duas atribuições do administrador e restrições executados apenas na cópia.
- Conteúdo das colunas originais das 13 tabelas preservado por comparação agregada; 14 turmas, 414 alunos, 47 cartões/notas e 2.115 respostas mantidos. Zero inconsistências e repetição sem DDL pendente.
- 20 campos cifrados lidos e índices protegidos conferidos; chave errada recusada. Os 47 arquivos referenciados por cartões foram recuperados e estão legíveis.
- Login e painel HTTP 200; relatório de rede sem vínculo 404 e com vínculo 200. Conta fictícia, token e vínculos temporários revertidos por transação.
- Expansão 3,18 s, preenchimento 4,32 s, conciliação 0,07 s, restrição 18,08 s e repetição 1,02 s. Cerca de 26,67 s nessas etapas; não inclui backup, instalação, drenagem, auditorias externas nem retomada.

## Correção encontrada no ambiente equivalente

O relatório consolidado com os dados restaurados permaneceu mais de 127 segundos no estado Statistics do MariaDB; a consulta local foi interrompida. As subconsultas de permissão eram combinadas pelo otimizador numa árvore de joins muito grande. A correção preserva os filtros e materializa as quatro tabelas derivadas com DISTINCT, projetando apenas ID e os campos utilizados. A chave primária mantém uma linha por entidade; os filtros de autorização continuam dentro de cada subconsulta. A [documentação do MariaDB sobre tabelas derivadas](https://mariadb.com/docs/server/ha-and-performance/optimization-and-tuning/query-optimizations/optimizations-for-derived-tables/condition-pushdown-into-derived-table-optimization) descreve essa barreira à fusão de consultas.

Na cópia atual, o relatório autorizado passou a 259 ms. O ensaio usa limite de 15 segundos por consulta somente na conexão isolada. A regressão MariaDB usa dados fictícios, duas redes, 414 alunos, 47 notas, limite de 5 segundos por consulta e confere os totais e a recusa após retirada do vínculo. Nenhum parâmetro global do banco foi alterado.

## Release candidato e retomada

`config/espacos.php` passa a true neste candidato; um merge não publica essa configuração. A primeira instalação deve usar o B5 desta versão com `--manter-manutencao` e o roteiro do doc. 49. Executar um B5 antigo não oferece a nova proteção. O B5 normal verifica o esquema antes de reabrir e, se faltar o corte, termina com manutenção preservada. Ele não aplica preenchimento, conciliação ou restrições automaticamente.

`g360:verificar-corte-espacos` é leitura: exige adaptador ativo, FKs/checks habilitados na conexão e todas as definições do plano. Não exige novamente as quantidades do manifesto inicial, permitindo conferência em deploys futuros. Ele não substitui auditoria de dados, testes funcionais nem conferência dos demais escritores/configurações.

## Condições ainda pendentes para avisar que o deploy pode ser marcado

1. Revisar e aprovar o merge deste candidato, com CI final verde; definir SHA incorporado como alvo.
2. Confirmar backup automático posterior a L3, incluindo APP_KEY e DADOS_HASH_KEY, nas duas camadas. A cópia online validada não é prova de um ponto coerente entre banco e arquivos durante escritas concorrentes.
3. Completar o ensaio operacional da restauração de serviços/contêineres e medir RTO/RPO antes de assumir que a reserva de 45 minutos cobre recuperação. O ensaio presente comprova arquivo, banco e kernel da aplicação com versões equivalentes; não é restauração completa de nginx/FPM/Redis/systemd no Proxmox.
4. Confirmar janela, operador e ausência de escritores externos. Na janela autorizada, capturar ponto final coerente com escritores parados, rever inventário/manifesto e executar o roteiro passo a passo.
5. Aprovação expressa de deploy e corte pelo responsável. Avisar antes de executar quando os critérios anteriores estiverem atendidos; não declarar a versão liberada para produção só porque os testes passaram.

O ciclo OMR/fila é coberto com dependências simuladas na suíte MariaDB. Leitura de cartão real e envio externo de e-mail continuam pendências operacionais já registradas em B6/E5/E6. V1 e seleção de vínculo permanecem fora desta entrega.

## Verificações da entrega

Sete testes MariaDB passaram (89 assertions somadas nas execuções local anterior e da nova regressão), incluindo DDL real, integridade, retomada, unicidade, OMR simulado e acesso ao relatório. Quinze testes do B5 passaram. Sintaxe PHP/Bash aprovada. Regressão completa e CI final serão vinculados no PR.
