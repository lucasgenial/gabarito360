# Backup e restauração

**Atualizado em:** 10/11/2026, por lucasgenial
**Ambiente:** produção (CT 111 e CT 106). A homologação (VM 120) tem backup próprio, descrito no fim.
**Quando usar:** conferir os backups diários, antecipar um backup, copiar para o computador, ou ensaiar e executar uma restauração.
**Duração estimada:** conferência, 5 minutos. Ensaio de restauração completa, cerca de 7 a 10 minutos de serviços mais a extração. Restauração via Proxmox, 30 s (banco) a 160 s (aplicação).

Este documento descreve as duas camadas de backup, a cópia para o computador, os testes de restauração já feitos e os procedimentos de recuperação. O resumo de topologia está em [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md). Funcionalidade: [FN-002](../funcionalidades/002-base-operacao/planejamento.md).

> **Atenção:** nunca restaure sobre os contêineres 106 e 111 sem decisão expressa do responsável. Restaure em contêineres temporários (por exemplo, 9106 e 9111) sem rede externa. Nunca imprima chaves, `.env` ou dados de alunos. Os backups contêm dados pessoais e segredos.

## Como funciona

| Camada | Onde | Quando | Retenção |
|---|---|---|---|
| 1 | SSD SATA no Proxmox, `/mnt/pve/backup-sata/dump` | 02:30, pelo timer `proxmox-backup-sata` | Hoje e 7 dias anteriores |
| 2 | Computador do responsável, `D:\Backups-Proxmox\dump` | 06:00, pela tarefa `Copia Backup Proxmox` | 8 datas mais recentes |

A camada 1 faz backup de todos os contêineres, incluindo o CT 111 (aplicação) e o CT 106 (MariaDB). Cada arquivo passa por `zstd -t`, recebe SHA-256 e fica imutável. O SSD só fica montado durante o uso.

A camada 2 é iniciada pelo Windows:

1. O script `D:\Backups-Proxmox\copiar-backups.ps1` chama `/usr/local/sbin/proxmox-backup-copia preparar` no servidor, pelo acesso SSH `pve`.
2. O script lista os backups e copia o que falta.
3. O script confere o SHA-256 de cada arquivo, com até 3 tentativas.
4. Só depois remove as datas além das 8 mais recentes.
5. O script desmonta o SSD.

Se o computador estiver desligado às 06:00, a tarefa roda quando ele ligar. O servidor recusa a cópia entre 02:00 e 05:00, para não conflitar com o backup.

Fontes no repositório: `scripts/operacao/proxmox-backup-copia` e `scripts/operacao/copiar-backups.ps1`. O ajudante do servidor aceita os comandos `preparar`, `listar`, `ler <arquivo>` e `encerrar`.

O prazo de 8 datas não autoriza apagar arquivos da aplicação nem registros escolares com a mesma idade. Os backups também não têm política de eliminação por titular: uma restauração antiga não autoriza republicar dados eliminados depois.

## Pré-requisitos

- Acesso SSH ao Proxmox (`pve`).
- Computador do responsável ligado, com o disco `D:` disponível.
- Para uma restauração: espaço livre suficiente (os arquivos de 01/11/2026 têm cerca de 2,2 GB para o banco e 8,7 GB para a aplicação), `zstd` e, se for ensaio, um ambiente sem rede externa.

## Passos: verificar a rotina diária

1. Abra `D:\Backups-Proxmox\ULTIMA-COPIA-OK.txt`. Confirme a data do último backup completo.
2. Consulte os registros em `D:\Backups-Proxmox\logs`. Procure erro ou arquivo parcial.
3. Confira 8 datas com 15 arquivos cada (inventário de 31/10/2026: 120 arquivos e 293 GB).
4. No servidor, consulte o SSD com `proxmox-backup-sata status`.
5. Falhas geram um e-mail ao endereço de suporte da equipe, se a chave do Resend estiver configurada.

## Passos: copiar só alguns arquivos (teste parcial)

Copie alguns arquivos sem aplicar a retenção:

```powershell
powershell -ExecutionPolicy Bypass -File D:\Backups-Proxmox\copiar-backups.ps1 -Filtro "lxc-305-2026_10_30"
```

## Passos: antecipar um backup antes de uma janela

Use esta variante quando precisar de um backup novo antes das 06:00. Ela não aplica retenção e não substitui o marcador da rotina completa.

1. Peça autorização expressa ao responsável.
2. Gere o backup em modo snapshot só dos contêineres 106 e 111. Respeite o lock do SSD e use `umask 022` no comando (com `umask 077`, o diretório temporário fica inacessível ao `vzdump`). Não envie e-mail.
3. Aguarde as verificações: `zstd -t`, SHA-256 e proteção de imutabilidade.
4. Copie para o computador com SHA-256 calculado durante a gravação. A transferência só renomeia o arquivo parcial depois de conferir o hash.
5. Registre a cópia em um marcador pontual, por exemplo `T2-COPIA-OK-AAAA-MM-DD.json`. Não sobrescreva `ULTIMA-COPIA-OK.txt`.

Medição de 01/11/2026: a geração dos dois arquivos e as verificações levaram cerca de 12 minutos (00h22 a 00h34). A cópia local ficou conferida 2 minutos depois.

## Passos: backup final com serviços parados (janela de corte)

O ponto final coerente exige escritores parados, e é parte do roteiro de corte em [publicacao-producao.md](publicacao-producao.md). Ao chegar ao checkpoint do B5, execute o `vzdump` e o `confirmar-backup-final.py` como descrito lá. Banco, arquivos e chaves precisam permanecer sem alteração durante os dois snapshots.

## Passos: ensaiar a restauração completa no Proxmox

Este ensaio já foi feito em 01/11/2026 e em 02/11/2026. Repita-o antes de mudanças grandes.

1. Escolha os backups. Use os nomes exatos, não "os mais recentes".
2. Restaure com `pct restore` em IDs livres (9106 para o banco, 9111 para a aplicação), em volumes novos, com `onboot` desligado.
3. Retire a rede herdada **antes do primeiro start**. Coloque os dois contêineres em um par de interfaces virtuais isolado, sem ponte nem ponta no host, para que aplicação e banco se enxerguem. Reaplique os endereços (use `addr replace`), aguarde a configuração do `networkd` e remova rotas padrão.
4. Adapte somente o clone: URL HTTP local, domínio de cookie local e transporte de e-mail em memória. Não regenere nem exiba chaves reais.
5. Inicie os serviços: MariaDB, nginx, PHP-FPM, Redis, os dois workers e o timer.
6. Execute as verificações abaixo.
7. Ao terminar, pare e destrua os clones depois de conferir nome, identificação do ensaio e volumes próprios. Confirme a ausência de configurações e volumes remanescentes. Preserve os backups originais.

## Verificação

Depois de qualquer restauração, confirme:

1. `mariadb-check` aprova todas as tabelas do banco restaurado.
2. A `APP_KEY` e a `DADOS_HASH_KEY` foram recuperadas. Leia 20 campos cifrados e confira os índices protegidos. Uma chave errada deve ser recusada. **Backups anteriores a 31/10/2026 17:08 UTC não contêm `DADOS_HASH_KEY`** (anteriores a L3). Eles não comprovam a recuperação do estado cifrado atual.
3. Os arquivos referenciados por cartões estão presentes e legíveis (47 em 01/11/2026).
4. As contagens do banco coincidem com o esperado e nenhuma contagem caiu.
5. Nginx, PHP-FPM, Redis, workers e timer sobem. `/`, `/login` e `/api/v1/health` respondem 200.
6. Um job técnico enfileirado é consumido. Matar o processo de um worker o reinicia pelo systemd.
7. Reiniciar Redis preserva a fila (AOF) e a sessão.
8. Uma conta fictícia faz login pela API e pela web. Rotas de outra rede retornam 404 antes do vínculo e 200 depois.
9. Hashes das tabelas escolares restauradas coincidem com a referência extraída do próprio backup.
10. O clone não tem rota para fora. A internet, o SMTP e os gateways de pagamento ficam bloqueados.

### Medições observadas

| Medição | Tempo |
|---|---|
| Restauração do CT de banco (`pct restore`) | 30,3 s |
| Restauração do CT de aplicação | 157,9 s |
| Do início da restauração a serviços e health, com ajuste de rede | 412 s (6 min 52 s) |
| Do início ao health, na restauração de 02/11/2026 | ~510 s |
| Início dos serviços em ambiente WSL | 0,96 s; sequência funcional 23,9 s |
| Extração integral do CT 111 em WSL | 181 s |
| Encerramento e início dos dois CTs, recomposição da rede e serviços | 44,9 s |

Esses tempos usam backups já disponíveis no SSD e recursos livres do host. **Não são RTO (tempo de recuperação) nem SLA.** Não incluem reposição de hardware, reconstrução do host, transferência pelo computador nem indisponibilidade total do local. O ponto de dados é o do snapshot. Não presuma RPO (perda máxima de dados) zero com snapshots online de contêineres distintos.

## Reversão

Uma restauração em clones não altera a produção. Para desistir, pare e destrua os clones e preserve os backups. Para recuperar a produção de fato, use somente o conjunto coerente de banco, arquivos e chaves, depois de aprovação do responsável e avaliação das escritas ocorridas depois do ponto de backup.

## Testes de restauração já feitos

| Data | Escopo | Resultado |
|---|---|---|
| 31/10/2026 | Extração em WSL a partir da cópia local. Nenhum contêiner de produção usado. Cópia completa terminou às 08:50 (8 datas, 15 arquivos por data, 293 GB) | CT 106 de 31/10: MariaDB 10.11.14 iniciou, `mariadb-check` aprovou as 39 tabelas. CT 106 de 30/10: 32 tabelas aprovadas. CT 111: commit `57f77f3`, `.env`, `vendor`, OMR, arquivos enviados, nginx, Redis, PHP-FPM e serviços presentes; PHP 8.3.6 e Laravel 13.15.0 executaram. As 7 tabelas novas entre as duas datas eram esperadas |
| 31/10/2026 | Cópia atual de leitura, em WSL isolado, com ensaio do corte T2 | 39 tabelas íntegras; corte aplicado só na cópia; 20 campos cifrados lidos; chave errada recusada; 47 arquivos recuperados. Uma consulta de relatório que passava de 127 s foi corrigida (259 ms) |
| 01/11/2026 | Backups antecipados e restauração completa via `pct restore` nos CTs 9106 e 9111 | Serviços, migração do corte, reinício dos CTs, retomada de sessão e fila validados |
| 01/11/2026 | Pausa para backup final antes da migração, com B5 em clones | Ordem parada, backup, código e DDL validada; clones restaurados dos backups novos e dados conferidos |
| 02/11/2026 | Backup final da ativação V1 a T5, restaurado em clones antes da migration | MariaDB íntegro, chaves, seis serviços, health 200 |
| 03/11/2026 | VM 120 (homologação) restaurada em clone 9120 | Ver a seção seguinte |

As pastas de teste foram removidas ao final de cada ensaio. A comparação das contagens do backup com o banco de produção é opcional e depende de aprovação.

## Backup e restauração da homologação (VM 120)

O Proxmox faz o backup da VM com `vzdump` (formato `.vma.zst`) em diretório protegido do host, fora do repositório. O ensaio de 03/11/2026 restaurou a VM no clone 9120:

1. O clone recebeu um endereço MAC exclusivo e a interface ficou desconectada. O firewall bloqueia tudo (DROP) antes de iniciar.
2. O boot é explicitamente pelo disco `scsi0`.
3. MariaDB, Redis, PHP-FPM, nginx e worker subiram. O health retornou 200 localmente.
4. As contagens acadêmicas, as 216.753 entradas do catálogo e o esquema CI1-E estavam presentes.
5. O clone foi desligado e teve `onboot` desativado. O recibo fica no host.

Esse backup é local ao host. Ele comprova recuperação da VM, não a perda do próprio host nem uma cópia externa.

Antes de cada publicação, o `deploy.py` também salva o banco (`banco-antes.sql.gz`) e os arquivos (`storage/app`) da homologação no diretório de recibos da release. Veja [publicacao-homologacao.md](publicacao-homologacao.md).

## Pendências

1. Comparar as contagens do backup com o banco de produção (opcional, com aprovação).
2. Confirmar o BitLocker no disco `D:`. Os arquivos não são cifrados pela rotina e contêm dados de alunos e segredos.
3. Avaliar uma terceira cópia fora do local. As duas camadas ficam no mesmo endereço.
4. Medir o RTO e o RPO de um desastre completo (perda do host).

## Referências

- [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md)
- [publicacao-producao.md](publicacao-producao.md)
- [auditoria.md](auditoria.md) (restauração da auditoria)
- [FN-002 Base de operação](../funcionalidades/002-base-operacao/planejamento.md)

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/operacao/backup.md` | Dizia que a cópia de 31/10 era o último ciclo completo e que a cópia desta janela era "futura" | Os ensaios de 01/11 e 02/11/2026 resolveram o bloqueio de backup com chaves e a restauração completa |
| `docs/51` | Apontava a próxima rotina diária de 01/11 às 02h35 BRT como ainda pendente | A cópia antecipada de 01/11 (00h36 BRT) substituiu essa espera |
| `docs/operacao/backup.md` | Citava o e-mail de suporte do responsável | Este documento omite o endereço pessoal por regra de privacidade |
