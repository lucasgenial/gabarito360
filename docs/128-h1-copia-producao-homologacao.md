# H1 — cópia pontual de produção para homologação

**Data:**03/11/2026. **Autorização:** “Agora mantenha os mesmos usuarios da produção em homologação! E até mesmo os dados que já temos na produção!”. Essa decisão substitui a restrição inicial a dados fictícios do ADR127; não autoriza publicar código ou executar migrations em produção.

## Resultado executado

Origem: aplicação CT111, MariaDB CT106. Destino: VM120, hostname g360-homologa, banco local `gabarito360_homolog_snapshot_20261103`. Release da homologação: `4b8d3068b6e7f22dd3ea84286b44b2b43be183d3`, base main já aprovada no CI. O CI1-E em reformulação não integra essa release.

| Dado copiado | Quantidade |
|---|---:|
| Usuários |10|
| Alunos |414|
| Provas |4|
| Gabaritos |4|
| Questões de gabarito |180|
| Resultados/notas |47|
| Vínculos institucionais válidos |7|
| Acessos internos válidos |3|
| Arquivos da API |149|
| Arquivos da web |3|

Exportação MariaDB com `--single-transaction --quick --hex-blob --skip-lock-tables`, somente InnoDB. Não colocar a origem em manutenção, não interromper seus escritores nem alterar registros. Contagens das68 tabelas coincidiram antes/depois da captura; na importação todas coincidiram com o snapshot. Treze tabelas de identidade e dados acadêmicos receberam também comparação SHA256 das linhas canônicas, incluindo hashes de senha. Após as migrations aditivas no destino, a comparação projetada nas colunas originais confirmou novamente as13 tabelas.

Os152 arquivos importados foram comparados individualmente por tamanho e SHA256 com os arquivos dos tarballs de origem. Foram preservados os marcadores de release próprios da homologação. Não copiar logs, cookies, sessões web, caches de framework ou `.env` completo da origem.

## Credenciais, isolamento e ações externas

- Usuários, e-mails, hashes de senha, permissões e vínculos mantidos. Usar o mesmo login e a mesma senha de produção, depois da proteção adicional `revisor`, cuja senha permanece no arquivo privado de acesso já entregue ao responsável.
- A chave corrente APP_KEY da API de homologação continua exclusiva. O material necessário para leitura dos valores cifrados antigos está em APP_PREVIOUS_KEYS, protegido; o índice HMAC foi preservado para localizar as mesmas identidades. A chave web, credenciais MariaDB, sessões, Redis e URLs continuam próprias. Não copiar SMTP, tokens de pagamento, token do túnel de produção ou credenciais do banco operacional.
- Sessões, tokens de acesso/recuperação, jobs, lotes de jobs, falhas de jobs, caches e troca de e-mail pendente foram limpos somente no novo banco. Não executar trabalho pendente da origem na homologação. A auditoria histórica foi preservada; os testes geraram eventos novos apenas no destino.
- E-mail em `log`; Mercado Pago sandbox sem credenciais; execução de transferências desativada. Timer de agenda da homologação desabilitado para impedir cobrança/rotinas automáticas herdadas. Worker próprio ativo para auditoria e tarefas novas autorizadas nesse ambiente.
- MariaDB e Nginx da VM somente em loopback; túnel exclusivo, Basic Auth, noindex/nofollow/noarchive, cookies exclusivos e HTTPS. A VM mantém bloqueios de rede aos IPs operacionais10.0.0.6 e10.0.0.11.
- Esta é uma cópia pontual independente. Não há sincronização automática. Alterações na homologação não retornam à produção; futuras recargas precisam de novo snapshot e preservação dos testes que se deseja manter.

## Validação e recuperação

Dez identidades foram consultadas pelo HTTP da API com tokens temporários próprios; sete vínculos foram selecionados e consultaram provas; três acessos internos foram selecionados e consultados. Leitura cifrada e busca por e-mail conferidas para os dez usuários. Esses tokens e seus contextos filhos foram encerrados ao final. Isso verifica identidade/contextos e preservação dos hashes, sem conhecer ou modificar as senhas atuais dos usuários.

HTTPS externo autenticado: `/up` e `/login` retornaram200, com cabeçalho noindex, formulário apontando para o host correto e cookie exclusivo. Corrigida a configuração interna da web para `http://127.0.0.1:8081/api/v1`. Os serviços MariaDB de produção e Nginx/PHP-FPM/workers da aplicação de produção permaneceram ativos. Conferência visual no Chrome continua pendente; não apresentá-la como realizada por testes HTTP.

Snapshot e manifesto privados no Proxmox: `/root/g360-homologa/snapshot-20261103`. A mesma captura está protegida na VM. O banco fictício anterior `gabarito360_homolog` foi mantido; backup SQL, arquivos e configurações anteriores em `/root/g360-homologa/antes-snapshot-20261103`, na VM. As pastas anteriores de arquivos, cache e sessões também foram preservadas com sufixo ficticio-20261103. Não armazenar esses backups, material de leitura ou dados pessoais em Git.

Retorno: colocar somente a homologação em manutenção, parar seu worker, restaurar suas configurações e arquivos anteriores a partir do backup, apontar para o banco fictício ainda existente, reconstruir o cache e validar antes de reabrir. Não usar o banco operacional como destino de retorno. O ensaio completo de recuperação de VM e o roteiro de publicação repetível ainda pertencem a H1-E; não foram encerrados por esta cópia.

## Planejamento atualizado

| Parte | Situação |
|---|---|
| H1-A — Planejamento e autorização de dados |Concluído; decisão revisada neste documento|
| H1-B — VM e serviços exclusivos |Concluído|
| H1-C — Base estável e dados |Concluído; dados de produção copiados e validados|
| H1-D — HTTPS e validação funcional |HTTP/API validados; conferência visual pendente|
| H1-E — Publicação repetível e ensaio de recuperação |Pendente; snapshot e retorno da troca preparados|
| CI1-E — Solicitações e manutenção do catálogo |Em reformulação; revisão visual final pendente; fora desta release|

Produção permanece sujeita à decisão explícita do responsável para cada promoção combinada. Nenhuma alteração de dados feita na homologação deve ser promovida como se fosse código.
