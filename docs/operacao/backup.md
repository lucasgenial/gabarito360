# Backup

## Como funciona

| Camada | Onde | Quando | Retenção |
|---|---|---|---|
| 1 | SSD SATA no Proxmox, `/mnt/pve/backup-sata/dump` | 02:30, pelo timer `proxmox-backup-sata` | Hoje e os 7 dias anteriores |
| 2 | Computador do responsável, `D:\Backups-Proxmox\dump` | 06:00, pela tarefa `Copia Backup Proxmox` | 8 datas mais recentes |

A camada 1 faz backup de todos os contêineres, incluindo o CT 111 (Gabarito360) e o CT 106 (MariaDB). Cada arquivo passa por `zstd -t`, recebe SHA-256 e fica imutável. O SSD só fica montado durante o uso.

A camada 2 é iniciada pelo Windows:

1. `D:\Backups-Proxmox\copiar-backups.ps1` chama `/usr/local/sbin/proxmox-backup-copia preparar`.
2. Lista os backups e copia pelo acesso SSH `pve` o que falta.
3. Confere o SHA-256 de cada arquivo, com até 3 tentativas.
4. Só depois remove as datas além das 8 mais recentes.
5. Desmonta o SSD.

Se o computador estiver desligado às 06:00, a tarefa roda quando ele ligar. O servidor recusa a cópia entre 02:00 e 05:00, para não conflitar com o backup.

## Fontes

- `scripts/operacao/proxmox-backup-copia`
- `scripts/operacao/copiar-backups.ps1`

## Verificar

- Última cópia: `D:\Backups-Proxmox\ULTIMA-COPIA-OK.txt`.
- Registros: `D:\Backups-Proxmox\logs`.
- Falhas geram e-mail para o endereço `+gabarito360`, se `RESEND_API_KEY` estiver configurada.
- Situação do SSD: `proxmox-backup-sata status`.

## Teste parcial

Para copiar só alguns arquivos, sem limpeza por retenção:

```powershell
powershell -ExecutionPolicy Bypass -File D:\Backups-Proxmox\copiar-backups.ps1 -Filtro "lxc-305-2026_10_30"
```

## Teste de restauração

Feito em 31/10/2026, no WSL do computador do responsável, a partir da cópia em `D:\Backups-Proxmox\dump`. Nenhum contêiner de produção foi usado.

Antes do teste, a cópia completa terminou às 08:50 com 8 datas, 15 arquivos por data e 293 GB, sem erro no registro e sem arquivo parcial. O SSD foi desmontado.

| Item | Backup | Resultado |
|---|---|---|
| CT 106 | 31/10, 02:36 | Extraído sem erro. MariaDB 10.11.14 iniciou sem erro no registro. `mariadb-check` aprovou as 39 tabelas de `gabarito-360` |
| CT 106 | 30/10, 02:41 | Extraído sem erro. `mariadb-check` aprovou as 32 tabelas |
| CT 111 | 31/10, 03:00 | Extraído sem erro. Commit `57f77f3`, `.env` da API e da web, `vendor`, OMR, arquivos enviados, nginx, Redis, PHP-FPM e serviços presentes. PHP 8.3.6 e Laravel 13.15.0 executaram |

Entre as duas datas, as contagens subiram e 7 tabelas surgiram. Isso é esperado: pelas datas dos arquivos, as tabelas novas foram criadas depois do backup de 30/10, e ele tem menos dados que o de 31/10. Nenhuma contagem caiu.

O teste provou que os arquivos podem ser recuperados e que o banco abre. Não houve restauração com `pct restore`, nem subida do site completo. A pasta de teste foi apagada ao final.

## Pendências

- Comparar as contagens do backup com o banco de produção, após aprovação.
- Confirmar BitLocker no disco D:. Os arquivos não são criptografados e contêm dados de alunos e segredos dos sistemas.
- Avaliar uma terceira cópia fora do local. As duas camadas ficam no mesmo endereço.

## Antecipação e ensaio T2 — 01/11/2026

Com autorização do responsável, os backups dos CTs 106/111 foram antecipados e copiados com SHA-256 às 00h36 BRT, sem esperar 06h e sem aplicar retenção. Registro pontual: `D:\Backups-Proxmox\T2-COPIA-OK-2026-11-01.json`; o marcador da rotina completa não foi substituído. Restauração completa pelo Proxmox nos CTs temporários 9106/9111, recuperação das chaves pós-L3 e reinício supervisionado validados. Os temporários foram removidos; os originais e backups foram preservados. Veja [docs/52](../52-t2-ensaio-completo-e-prontidao.md) para arquivos, hashes, tempos e limites.

## Corte T2 aplicado — 01/11/2026

Backup final coerente dos CTs 106/111 gerado com escritores parados às 08h20/08h23 BRT, copiado e conferido nas duas camadas antes da migração. Marcador pontual `T2-CORTE-COPIA-OK-2026-11-01-4f3150d5.json` no computador. Arquivos, hashes e procedimento executado no [doc. 54](../54-t2-publicacao-homologacao.md). O servidor foi atualizado e reaberto com T2 ativo.
