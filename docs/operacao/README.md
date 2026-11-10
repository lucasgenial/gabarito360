# Operação

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento é o índice dos procedimentos de operação do Gabarito360: publicar, fazer backup, restaurar, auditar, enviar e-mail e tratar incidentes. Cada procedimento segue o modelo [_modelo.md](_modelo.md).

## Regras comuns

1. A produção exige decisão expressa do responsável (`lucasgenial`) para cada merge, deploy e migração. Uma aprovação não vale para a próxima ação.
2. Toda entrega vai primeiro à homologação.
3. Nunca registre senhas, chaves, tokens, caminhos de segredos, endereços de e-mail pessoais ou dados de alunos em documentos, logs ou Git.
4. Conferências visuais ficam registradas por texto. Nenhum print é versionado.
5. Depois de uma migration, não restaure só o Git. Use o conjunto coerente de banco, arquivos e chaves.

## Ambientes

| Ambiente | Recurso | Endereço |
|---|---|---|
| Produção | Contêiner 111 (aplicação) e contêiner 106 (MariaDB) | `gabarito360.com.br` |
| Homologação | VM 120, isolada | `homologa.gabarito360.com.br` |

A topologia completa está em [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md).

## Procedimentos

| Procedimento | Quando usar |
|---|---|
| [Publicação na homologação](publicacao-homologacao.md) | Publicar uma versão aprovada no CI na VM 120 |
| [Publicação na produção](publicacao-producao.md) | Deploy rotineiro (B5), publicação por fase, corte com backup final, home independente e histórico de publicações |
| [Backup e restauração](backup-e-restauracao.md) | Conferir backups, antecipar um backup, copiar para o computador, ensaiar e executar restauração |
| [Cópia da produção para a homologação](copia-producao-homologacao.md) | Recarregar a homologação com dados reais, ou voltar ao banco fictício |
| [Auditoria](auditoria.md) | Conferir a auditoria, gerar a cópia mensal cifrada e ensaiar a recuperação |
| [E-mail](email.md) | Configurar o domínio, testar o envio e entender os modelos |
| [Incidentes](incidentes.md) | Tratar um incidente e consultar os incidentes já registrados |

## Evidências

A pasta [evidencias](evidencias/) guarda resumos sanitizados em JSON de ensaios e publicações (contagens, estados e tempos). Ela não contém dados pessoais, chaves nem prints.

## Outros documentos relacionados

| Assunto | Documento |
|---|---|
| Segurança e privacidade (LGPD) | [11-seguranca-e-privacidade.md](../arquitetura/11-seguranca-e-privacidade.md) |
| Desempenho | [12-desempenho.md](../arquitetura/12-desempenho.md) |
| Decisões de arquitetura | [decisões](../decisoes/README.md) |
| Catálogo de funcionalidades | [funcionalidades](../funcionalidades/README.md) |

## Documentos de operação consolidados

Em 10/11/2026, os documentos abaixo foram consolidados nos procedimentos acima. O Git guarda o texto original.

| Documento antigo | Novo lugar |
|---|---|
| `backup.md` | [backup-e-restauracao.md](backup-e-restauracao.md) |
| `deploy.md` | [publicacao-producao.md](publicacao-producao.md) e [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md) |
| `deploy-b6-2026-10-31.md`, `conciliacao-2026-10-31.md` | Histórico em [publicacao-producao.md](publicacao-producao.md) |
| `protecao-dados.md`, `avatares-alunos.md` | [11-seguranca-e-privacidade.md](../arquitetura/11-seguranca-e-privacidade.md) e histórico em [publicacao-producao.md](publicacao-producao.md) |
| `incidente-painel-professor-2026-11-01.md` | [incidentes.md](incidentes.md) |
