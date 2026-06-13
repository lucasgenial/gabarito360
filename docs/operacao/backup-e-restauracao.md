# Backup e Restauracao

## 1. Objetivo

O R7 estabelece backup consistente do MariaDB e do storage privado, com
restauracao verificada em alvos isolados. A meta inicial e RPO de ate 24 horas e
RTO de ate 8 horas. Producao exige automacao, criptografia, copia externa,
monitoramento e teste periodico.

## 2. Salvaguardas

- Os artefatos locais ficam em `.local/`, ignorado pelo Git.
- O manifesto registra data, origem e SHA-256 de cada arquivo.
- A restauracao padrao aceita apenas banco contendo `_restore`.
- Substituir um banco ativo exige `-AllowDestructiveRestore`.
- A restauracao de verificacao nao escreve no volume ativo da aplicacao.
- Dados reais nao devem ser usados em homologacao sem autorizacao formal.

## 3. Criar backup

Com o ambiente containerizado ativo:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/infra/backup.ps1
```

O diretorio gerado contem:

```text
database.sql
private-storage.tar.gz
manifest.json
```

O dump usa transacao consistente, triggers, rotinas e eventos. O storage privado
e empacotado separadamente.

## 4. Validar restauracao

```powershell
powershell -ExecutionPolicy Bypass -File scripts/infra/verify-restore.ps1
```

Esse comando:

1. cria novo backup;
2. valida checksums;
3. restaura o banco em `gabarito360_restore_<timestamp>`;
4. restaura arquivos em `.local/restore-verification/`;
5. compara a quantidade de tabelas e confirma o diretorio privado.

A evidencia deve ser anexada ao registro operacional da homologacao.

## 5. Restauracao controlada

Restauracao isolada manual:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/infra/restore.ps1 `
  -BackupDirectory .local/backups/AAAAmmdd-HHmmss `
  -TargetDatabase gabarito360_restore_manual
```

Restaurar o banco ativo e uma operacao excepcional. Exige janela aprovada,
backup atual, parada de escrita, responsavel identificado e uso consciente de
`-AllowDestructiveRestore`.

Depois da restauracao:

1. executar migrations pendentes;
2. validar integridade e amostras autorizadas;
3. reaplicar descarte por `retencao_ate` e retencoes legais;
4. validar fila, Reverb e storage;
5. auditar a operacao e remover copias temporarias.

## 6. Politica para producao

- Backup diario e antes de migrations de risco.
- Criptografia em repouso e transito.
- Copia em conta/local distinto do host da aplicacao.
- Retencao definida com Seguranca e LGPD.
- Alerta para falha ou atraso de backup.
- Restauracao testada ao menos trimestralmente.
- Credenciais de backup com menor privilegio possivel.

Backup sem restauracao testada nao e considerado valido.
