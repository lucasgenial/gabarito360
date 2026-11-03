# H1 e CI1-E — publicação na homologação e recuperação validada

03/11/2026. O responsável autorizou continuar e publicar na homologação tudo que já havia sido produzido. Produção continua dependente de decisão explícita posterior.

## Release e escopo publicado

PR158 integrado, merge `68671c8c920609340082068b57fe9fd045123868`, publicado em `homologa.gabarito360.com.br`. O commit de implementação aprovado `a3ad1b712ac7ebafe24370ee3287445802fea9cb` e o merge têm árvores idênticas. CI41711629942 aprovado nos cinco jobs: API, web, telas, MariaDB e recuperação. A execução adicional do merge na main é independente dessa verificação do conteúdo publicado.

Toda a base main anterior foi mantida, incluindo CP1, fases anteriores de T5/L4 e os resultados em lote. Acrescentado CI1-E: solicitação privada, acompanhamento/complemento, análise interna, resolução por referência existente/nova, cadastro manual auditável e manutenção institucional condicionada a autoridade comprovada. A nova política TR1/TE1/UX1 continua planejada; a homologação não transforma a implementação T5 anterior nessa política futura nem autoriza sua promoção em produção.

Interface CI1-E aprovada pelo revisor independente product_design_review no commit a3ad1b7, depois de código e capturas atuais em1440/2560 e390. Revisor não implementou as telas. Conferidos envio, recuperação de422, UF/município preservados, complemento, referência nova/existente, confirmação inicialmente desmarcada, permissão institucional e CTA móvel por teclado. A informação pedida no complemento aparece no próximo passo. Essa revisão não equivale a auditoria completa de acessibilidade. Melhoria menor para backlog: exibir o estado atual da permissão fora da option longa no celular.

## Catálogo e funcionalidades habilitadas

Fontes públicas previamente verificadas foram carregadas somente na VM120:214.192 observações básicas2025 e2.561 IES2024, geografia de27UFs/5.571municípios e3.936 relações, com8 lacunas preservadas. Não representar todas as observações como instituições ativas, nem inventar campi/associações ausentes. A projeção tem216.753 entradas dessas fontes; filtros e elegibilidade são aplicados na consulta. Escola INEP29428254 encontrada na busca exata em ambos os perfis internos autorizados.

Habilitadas na homologação as flags das entregas implementadas: catálogo, protocolos de titulares/exportações e interface/execução T5. As permissões e os contextos continuam determinando o que cada perfil vê. Habilitar uma flag não concede vínculo escolar nem autoridade de manutenção. Cadastro do catálogo aparece em Desenvolvedor/Suporte no acesso interno; solicitações próprias dependem do espaço pessoal; gestão escolar vê apenas unidades formalmente liberadas.

E-mails continuam em log, pagamentos sandbox sem credenciais e agenda geral de cobranças desativada. Publicadores T5 têm timer próprio `g360-homologa-copias.timer`, sem chamar a rotina de inadimplência. Worker, banco, Redis, arquivos, sessões, chaves correntes e túnel continuam exclusivos da homologação. A proteção inicial revisor foi preservada. Não há sincronização automática com produção.

## Dados e verificações

Backup SQL e arquivos anteriores à troca em `/root/g360-homologa/releases/68671c8c920609340082068b57fe9fd045123868`, na VM. Comparação canônica antes/depois das migrations confirmou conteúdo idêntico em nove tabelas acadêmicas. Mantidos10 usuários,414 alunos,4 provas,4 gabaritos,180 questões e47 resultados. Não redefinir senhas nem substituir o snapshot por fixtures para publicar a aplicação.

Testes locais: API12/128 em SQLite; recorte MariaDB24/265 com concorrência em processos separados; web8/56; JavaScript4testes; verificador29scripts/0erros. O CI completo verifica também a base anterior.

Após publicar: HTTP das10 identidades, sete vínculos e três acessos internos; sete consultas de provas; financeiro negado ao atendimento do catálogo; suporte/desenvolvimento com27UFs e escola conhecida; pedidos de titulares acessíveis aos dois perfis autorizados. Vinte requisições HTTP simultâneas de catálogo, em20UFs, completaram corretamente: média1,703s e maior3,217s, nessa VM2vCPU/4GiB. Isso mede requisições concorrentes com fila do PHP-FPM, não comprova20 conexões SQL simultâneas nem substitui todos os cenários CI1-F.

Saúde, login e planos externos retornaram200 com Basic Auth. A ponte real da web para API retornou planos200 e login propositalmente inválido401, confirmando que a base permanece `/api` e os controllers acrescentam `/v1`, sem duplicação. Tokens temporários do ensaio devem ser encerrados; os tokens de usuários reais não são alvo dessa limpeza.

O primeiro ensaio operacional detectou permissões de diretórios extraídos com umask restritivo. A homologação permaneceu em manutenção, sem abrir código parcialmente acessível; diretórios de código foram corrigidos para755 e caches/configurações protegidos para750/640. A retomada preservou o backup e confirmou o manifesto acadêmico antes de trocar o symlink. O script versionado normaliza essas permissões antes do Composer.

## Backup e restauração completa

Backup da base anterior restaurado primeiro; depois foi gerado e restaurado o backup da própria release68671c8. Arquivo final no Proxmox: `/root/g360-homologa/backup-vm-ci1e-20261103/vzdump-qemu-120-2026_11_03-17_45_03.vma.zst`, SHA256 `b4a58d6f5cf82ccca79dbfe1ca86ea6f4fa49cbdacad16bdccf4165dfbe036bd`. São backups locais protegidos no host; esse ensaio comprova recuperação da VM, não perda do próprio host ou cópia externa.

Restauração para clone9120, MAC exclusivo, interface desconectada e firewall DROP antes de iniciar. Boot explicitamente por scsi0. Na VM restaurada, MariaDB/Redis/PHP-FPM/Nginx/worker ficaram ativos; HTTP local autenticado de saúde/login200; release68671c8; mesmas contagens acadêmicas,216.753 entradas e schema CI1-E disponíveis. Interface enp6s18 permaneceu down; nenhum conector de teste foi autorizado a usar rede. Clone desligado ao finalizar, onboot desativado. O clone temporário anterior foi reconstruído somente após conferir identidade, estado desligado e recibo da operação.

Recibo protegido no host: `/root/g360-homologa/recuperacao-vm-ci1e-9120.json`. VM120 permanece ativa e configurada para iniciar com o host. Produção CT106/111 não recebeu código, migrations, alteração de banco ou mudança de rota/DNS principal.

## Próximas publicações e retorno

`infra/homologacao/deploy.py` aceita SHA completo, arquivo git archive e SHA256. Executar como root somente em g360-homologa, com aplicação já preparada em `/srv/gabarito360`. Exemplo de forma: `python3 deploy.py SHA40 /caminho/release.tar.gz SHA256`. O arquivo e o código precisam vir de commit revisado/CI aprovado; o script não concede essa aprovação.

Usa trava exclusiva, prepara release/dependências, pausa somente homologação, salva backup e configurações, aplica migrations no banco local aprovado, compara acervo, troca symlink, valida a ponte web/API e reabre. Sem `migrate:fresh`, sem down destrutivo, sem credencial da produção. Falha exige conservar manutenção e consultar log privado. Para retorno, restaurar configurações/identificação anteriores e symlink anterior com o banco compatível; se precisar reverter dados, restaurar em banco alternativo próprio e validar antes da troca. Nunca restaurar por cima de produção.

## Tabela atualizada

| Parte | Situação |
|---|---|
| H1-A/B — Planejamento, VM e isolamento |Concluídos|
| H1-C/D — Release, dados, HTTPS e acessos |Publicados e testados; design conferido no Chrome local fictício e API real conferida na VM|
| H1-E — Publicação repetível e recuperação |Roteiro/script preparados; backup da release e restauração completa validados|
| CP1 — Copiar prova para si |Código integrado, disponível na homologação conforme perfil|
| CI1-A/B/C/D |Integrados, catálogo nacional carregado e interface habilitada|
| CI1-E1–E5 |Concluídos, aprovados e publicados na homologação|
| CI1-F |Próximo: ensaio integrado completo; carga HTTP e recuperação já adiantadas|
| ID1/VI1/DC1/CP2/MT1/TR1/TE1/PA1/UX1 |Planejados no115; não anunciados como implementados|
| L4-D/E e T6 |Reconciliar com identidade/histórico; arquivos preexistentes de outra tarefa preservados|
| Produção |Sem promoção; depende de decisão explícita do responsável|
