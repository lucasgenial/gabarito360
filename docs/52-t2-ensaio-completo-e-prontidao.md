# 52 — T2: backups antecipados, restauração completa e prontidão

## Escopo confirmado pelo responsável

Em 01/11/2026, o responsável autorizou antecipar os backups e a cópia e executar a restauração completa, sem deploy/migração no servidor atual. Esclareceu que o sistema está em desenvolvimento e testes, ainda não lançado ao público geral; o ambiente deve representar o funcionamento futuro em produção.

Adotamos **homologação representativa de produção** para o servidor atual. O termo produção nos roteiros anteriores identifica esse servidor de referência e suas configurações operacionais; não significa lançamento público. APP_ENV e os controles técnicos existentes foram preservados. A decisão abaixo cobre a atualização T2 nesse ambiente.

## Resultado e candidato validado

**Candidato validado no ensaio completo; falta fechar o checkpoint final do roteiro antes de executar deploy e migração T2 no servidor atual.** Os bloqueios de backup com chaves e de restauração completa dos contêineres foram resolvidos neste ensaio.

Candidato executado: `3af6c19de426ef0ea89bbbeab66e7e167c3970c2` (merge aprovado do PR #83), com quatro verificações de CI aprovadas. Esta entrega posterior acrescenta somente documentação, evidência e auxiliares de ensaio; não modifica API, web, infra ou B5. Antes de publicar um SHA posterior, confirmar que essas partes permanecem idênticas ou repetir a validação pertinente.

[Evidência sanitizada completa](operacao/evidencias/t2-restauracao-completa-2026-11-01.json).

## Backups antecipados e duas camadas

Foi executado backup snapshot somente dos CTs 106 e 111, sem retenção/exclusão de backups anteriores, sem envio de e-mail e sem alteração do timer diário. O lock operacional e o dispositivo SATA configurados foram respeitados. A primeira tentativa foi interrompida porque umask 077 deixou o diretório temporário inacessível ao processo unprivileged do vzdump; a repetição usou umask 022 apenas no comando de backup. Os snapshots temporários da tentativa falha foram removidos pelo Proxmox; os CTs originais continuaram rodando.

| Backup novo | Tamanho | SHA-256 confirmado no servidor e neste computador |
|---|---:|---|
| `vzdump-lxc-106-2026_11_01-00_22_15.tar.zst` | 2.208.133.923 bytes | `8517348bf92b0183880da1a42f264f30cdcac12c7f0125141e2b96ac53992178` |
| `vzdump-lxc-111-2026_11_01-00_24_28.tar.zst` | 8.749.437.681 bytes | `1f2f912f00ff3247cfc8927cd559b38c1a83939763738576affb5810cf93736c` |

Início da tentativa bem-sucedida: **00h22min14s BRT**. Geração e verificações encerradas às **00h34min02s**; ambas as cópias locais confirmadas às **00h36min36s**, sem esperar 06h. Cada arquivo passou por `zstd -t`, SHA-256 e proteção de imutabilidade no servidor. A transferência calculou o hash integral durante a gravação e só então renomeou o arquivo parcial.

A cópia pontual está registrada em `D:\Backups-Proxmox\T2-COPIA-OK-2026-11-01.json`. Não foi sobrescrito `ULTIMA-COPIA-OK.txt`, que representa o ciclo completo de todos os contêineres, nem alterada a rotina diária. Os arquivos novos permanecem no SSD de backup e em `D:\Backups-Proxmox\dump`.

## Restauração real no Proxmox

Foram restaurados com `pct restore` os CTs temporários **9106** (banco) e **9111** (aplicação), em volumes novos local-lvm. Seus IDs estavam livres, os nomes/volumes foram conferidos e onboot ficou desligado. Os CTs de referência 106 e 111 não foram substituídos.

A rede herdada do Proxmox foi retirada antes do primeiro start. Um par veth foi colocado inteiramente dentro dos dois namespaces dos CTs, sem bridge ou ponta no host, mantendo os endereços esperados entre aplicação e banco. O networkd restaurado reaplicou endereços/rota originais quando eth0 apareceu; o auxiliar foi ajustado para usar `addr replace`, aguardar essa configuração e remover rotas default. A topologia de par direto e a ausência de rotas externas foram verificadas. O mesmo procedimento passou no segundo boot.

Os binários, unidades systemd, dados, arquivos, dependências e chaves vieram dos backups completos. A aplicação foi adaptada somente no clone para URL HTTP local, domínio de cookie local e transporte de e-mail em memória. O Git do clone recebeu um bundle local, sem internet. Chaves reais não foram regeneradas, exibidas ou versionadas.

- MariaDB iniciou a partir do backup e `mariadb-check` aprovou o banco.
- APP_KEY e DADOS_HASH_KEY recuperadas: 20 campos cifrados e seus índices protegidos conferidos.
- Os 47 arquivos referenciados por cartões estavam presentes e legíveis.
- Nginx, PHP-FPM, Redis, os dois workers e o timer iniciaram; health respondeu 200.

## Ensaio do procedimento de atualização e migração

O B5 do candidato foi executado **apenas no CT 9111**, com origem `dc9949e3690744b72c5071d919955c09ad2be9b8`, destino `3af6c19de426ef0ea89bbbeab66e7e167c3970c2` e `--manter-manutencao`. O Git local simulou a obtenção da release; Composer usou os vendors/locks recuperados, sem acesso externo.

O procedimento drenou a fila, parou escritores, atualizou código, executou a expansão e preparou caches, terminando com as duas aplicações em manutenção. Foram confirmados individualmente os estados parados dos workers, timer e serviço agendador.

Depois, na cópia, foram executados inventário, simulação/aplicação do preenchimento, conciliação do administrador identificado no banco, auditoria, simulação/aplicação/repetição das restrições e verificação final do esquema. Somente então houve reload do FPM e retomada dos serviços no clone.

Resultado: conteúdo das colunas originais das 13 tabelas preservado por comparação agregada, contagens mantidas, esquema completo e zero inconsistências. Entre as contagens: 14 turmas, 414 alunos, 47 cartões/notas e 2.115 respostas. O administrador recebeu as duas atribuições previstas; não foi concedido acesso global.

Com conta fictícia criada exclusivamente na cópia, login API/web e relatório da primeira rede retornaram 200. A segunda rede retornou 404 antes do vínculo e 200 depois dele, com as 47 notas esperadas. A sessão web foi persistida no Redis.

## Falha, agendamento e boot completo

Um job técnico foi despachado pela aplicação e consumido pelos workers reais. O processo principal do worker 1 foi encerrado intencionalmente; o systemd o reiniciou automaticamente e o consumo foi novamente confirmado. O timer disparou uma nova ocorrência, com término bem-sucedido de `schedule:run`.

Ambos os contêineres foram encerrados e iniciados novamente pelo Proxmox. Após recompor o par de rede isolado, banco e serviços voltaram sem start manual individual. A sessão anterior continuou autenticada no painel, sem redirecionar para login. Novo login, relatórios, consumo da fila, auditoria e `mariadb-check` passaram novamente. Filas vazias e zero jobs falhos antes/depois.

| Medição observada | Tempo |
|---|---:|
| Restauração do CT de banco | 30,31 s |
| Restauração do CT de aplicação | 157,86 s |
| Início da restauração até serviços/health, incluindo ajuste de rede | 412,05 s — 6 min 52 s |
| B5 até entrega em manutenção | 21,79 s |
| Inventário, preenchimento, conciliação, restrição e verificações do corte | 26,35 s |
| Encerramento/start dos dois CTs, recomposição da rede e serviços ativos | 44,93 s |

A medição de recuperação usa backups já disponíveis no SSD e recursos livres do próprio host; não inclui reposição de hardware, reconstrução do host, transferência do computador nem indisponibilidade total do local. É evidência de recuperação observada, não SLA. O ponto dos dados do banco é o snapshot de 00h22min15s; alterações posteriores não estão contidas nele. Não presumir RPO zero com snapshots online de CTs distintos.

## Encerramento e servidor atual

As evidências foram retiradas apenas em forma de contagens, estados e tempos. Os CTs 9106/9111 foram parados e destruídos após conferir nomes, identificação do ensaio e volumes próprios. Ausência de configs e volumes confirmada; a área temporária com scripts/logs e o bundle local foram removidos. Backups novos e antigos preservados.

Conferência do servidor atual ao fim: SHA continua `dc9949e3690744b72c5071d919955c09ad2be9b8`, zero tabelas clientes/espacos, health 200 e todos os serviços conferidos individualmente ativos. **Não houve deploy ou migração T2 no servidor de referência.**

## Próxima autorização e execução

O responsável pode agora revisar esta evidência. Antes de solicitar a execução da publicação/migração T2, fechar a pendência operacional abaixo. Executar o roteiro do doc. 49 com o B5 do candidato aprovado e `--manter-manutencao`; não usar o roteiro antigo instalado no servidor como se já contivesse as guardas novas.

Na janela autorizada: reconfirmar SHA/CI e inventário; parar/drenar escritores e obter ponto coerente final de banco, arquivos e chaves; aplicar expansão, preenchimento, conciliação e restrições; exigir auditoria e verificação de esquema antes de retomar. Conferir login, as duas redes, fila e timer, e acompanhar erros. Restaurar só Git após DDL não é rollback válido; a recuperação exige o conjunto consistente de backup e avaliação de escritas posteriores.

Nenhuma data de publicação foi imposta e nenhuma publicação foi executada automaticamente. A antecipação autorizada dos backups e o ensaio completo estão concluídos.

## Pendência identificada na revisão final do roteiro

O B5 atual para os escritores e segue diretamente para atualização e migração aditiva; `--manter-manutencao` pausa somente depois dessa migração. Além disso, seu preflight rejeita aplicações já em manutenção. Portanto, não há hoje checkpoint executável entre a parada dos escritores e o primeiro DDL para obter o ponto final coerente exigido pelo doc. 49. Não interpretar a opção como pausa anterior à migração.

Próximo trabalho: preparar e ensaiar esse checkpoint no procedimento, com banco, arquivos e chaves coerentes, antes de executar a janela no servidor atual. Os backups online antecipados e a restauração completa continuam válidos como evidência de recuperação, mas não substituem esse ponto final. Nenhuma alteração do B5 foi incluída nesta entrega.

Os auxiliares desta entrega registram o ensaio pontual de 01/11: contêm IDs, caminhos e SHAs fixos e dependem de arquivos temporários já removidos. Não são comandos genéricos para reexecutar automaticamente; revisar e preparar os pré-requisitos em novo ensaio.

## Complemento — checkpoint anterior à migração

O ajuste e o ensaio da pausa foram concluídos no [doc. 53](53-t2-checkpoint-backup-final.md), resolvendo a pendência operacional registrada acima. Nenhum deploy/migração foi feito no servidor atual. A nova entrega depende de revisão e merge autorizado antes da janela.
