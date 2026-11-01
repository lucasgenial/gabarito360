# 53 — T2: pausa para backup final antes da migração

## Autorização e escopo

O responsável autorizou o merge do PR #84 e o ajuste/ensaio da pausa, **sem deploy no servidor atual**. PR #84 incorporado em `b1d2b9577b8b0638063706b1925fc418769aad27`. O ambiente atual é homologação representativa de produção. Somente os clones isolados podem receber o ensaio desta entrega.

## Funcionamento

O B5 aceita `--backup-final /root/pausa-backup-final.sh`. Depois do preflight e da drenagem, mantém API/web em manutenção, para os dois workers, timer, serviço agendador e PHP-FPM. Parar o FPM encerra também os processos que poderiam continuar atendendo requisições iniciadas antes da manutenção. Verifica fila vazia e chama o hook **antes do fast-forward, Composer e primeiro DDL**. O lock do deploy permanece adquirido durante toda a espera.

O hook cria uma pasta privada `/root/g360-backup-final-*` com `pronto.json`. Espera até 1.800 segundos pelo recibo `confirmado.json`. A variável `G360_BACKUP_TIMEOUT` aceita de 1 a 3.600 segundos; seu valor deve cobrir geração, verificação e cópia previstas. O recibo exige ID desta execução, SHAs anterior/alvo, confirmação explícita e dois arquivos distintos, banco e aplicação, com tamanho e SHA-256. Deve pertencer a root, sem acesso de grupo/outros. Recibo de outra execução, incompleto ou inválido não libera o deploy.

O hook não acessa o SSD remoto: recebe a atestação das verificações realizadas no host. O auxiliar `confirmar-backup-final.py`, executado no Proxmox, verifica nomes/CTs, data do snapshot posterior ao checkpoint, `zstd -t`, SHA-256 integral, tamanho estável, prazo vigente, SHA antigo e serviços ainda parados. Só então grava o recibo atomicamente dentro do CT. Não escolher arquivos apenas por serem os mais recentes; usar os nomes da execução bem-sucedida do backup.

Timeout, interrupção ou erro após iniciar o checkpoint mantém as aplicações em manutenção e solicita parada dos escritores/FPM. Não há rollback de banco nem reabertura automática. O recibo não substitui aprovação de publicação. `--check` não chama o hook nem para serviços. Sem a nova opção, o comportamento anterior do B5 permanece.

## Janela futura — somente após autorização de deploy/migração

1. Confirmar SHA aprovado, CI, origem real, inventário, operador e ausência de outros importadores/CLI escrevendo. Revisar os três auxiliares e copiar as versões aprovadas antes de atualizar código: B5 em `/tmp/g360-deploy.sh`, hook em `/root/pausa-backup-final.sh` (root, modo 0700), confirmador no host. Verificar hashes contra o commit aprovado. Python 3 precisa estar disponível no CT e no host; zstd no host.
2. Fazer preflight com `--check --manter-manutencao --backup-final /root/pausa-backup-final.sh`.
3. Iniciar B5 em terminal persistente no CT 111:

```bash
bash /tmp/g360-deploy.sh --commit "$ALVO" --from "$ATUAL" --wait 300 \
  --manter-manutencao --backup-final /root/pausa-backup-final.sh
```

4. Aguardar `CHECKPOINT AGUARDANDO: /root/g360-backup-final-...`. Conferir os estados parados e a ausência de outros escritores. Durante o checkpoint o nginx pode responder 502 porque o PHP-FPM está parado; isso faz parte da indisponibilidade planejada.
5. No host, usar o lock `/run/lock/proxmox-backup-sata.lock`, conferir o dispositivo SATA correto montado para escrita e espaço livre, sem conflito com o backup diário. Gerar somente os CTs 106/111, sem retenção/exclusão e sem e-mail:

```bash
umask 022
vzdump 106 111 --storage backup-sata --tmpdir /var/tmp --mode snapshot \
  --compress zstd --remove 0 --prune-backups keep-all=1 \
  --notification-mode legacy-sendmail
```

Não configurar destinatário de e-mail. A montagem, lock e desmontagem seguem a rotina operacional existente; não chamar a rotina completa que aplica retenção. `--tmpdir /var/tmp` evita que o tar unprivileged tente ler metadados sob `/root`. Banco, arquivos e chaves devem permanecer sem alterações durante ambos os snapshots.
6. Registrar os nomes exatos. Preservar as cópias e SHA-256 conforme a política de backup, incluindo a segunda camada, antes de liberar a migração; dimensionar o prazo da pausa para isso. Não sobrescrever o marcador do ciclo diário completo com uma cópia pontual. No host, executar com os caminhos reais e a pasta exata impressa pelo hook:

```bash
python3 /root/confirmar-backup-final.py --ct-banco 106 --ct-aplicacao 111 \
  --checkpoint "$CHECKPOINT" --banco "$BACKUP_BANCO" --aplicacao "$BACKUP_APP"
```

Arquivar a saída como evidência privada. O confirmador valida os arquivos do host; a verificação da segunda cópia é uma etapa separada e anterior a este comando.
7. O B5 volta a conferir manutenção, fila e serviços, atualiza código, aplica expansão e refaz caches. Reinicia apenas o FPM e termina em manutenção por causa de `--manter-manutencao`. Continuar preenchimento, conciliação e restrições do doc. 49; exigir auditoria e esquema completos antes da retomada de usuários/workers/agendador.

O tempo da pausa é parte da indisponibilidade. A reserva anterior de 45 minutos deve ser reavaliada para incluir a geração, cópia e conferência final; não prometer duração com base apenas no tempo de migração do clone.

## Falha e recuperação

Se a pausa expirar ou o backup falhar, conferir logs/recibo e o estado real; não reenviar confirmação tardia. Sem mudança de SHA/esquema, o operador pode, após decidir cancelar a janela, reabrir a versão anterior e iniciar FPM/workers/timer. Para nova tentativa, começar um novo preflight/checkpoint; não reaproveitar ID antigo. Após DDL, recuperação exige o conjunto coerente de banco, arquivos e chaves e avaliação de escritas posteriores, nunca apenas Git.

Ao restaurar contêineres desse checkpoint, os arquivos de manutenção estarão presentes, mas unidades habilitadas podem iniciar no boot. Restaurar primeiro em rede isolada, parar/verificar escritores e conferir SHA, esquema e integridade antes de conectar à rede real ou reabrir. A memória/processo da espera não é restaurada pelo backup; não tentar retomar o deploy antigo com o recibo salvo.

## Validação desta entrega

Testes automatizados: ordem parada → backup → código → DDL; falha do hook sem migração/reabertura; preflight sem hook; rejeição de hook gravável; falha HTTP posterior volta a manutenção; recibo válido, timeout, ID errado, ausência de arquivo, hash malformado e permissões indevidas; verificador do host rejeita backup antigo, CT errado e arquivo corrompido.

**Ensaio completo aprovado em 01/11/2026.** [Evidência sanitizada](operacao/evidencias/t2-checkpoint-backup-2026-11-01.json).

- Candidato `6a9724c8107c1349eb2f1b592958d3e7e895aedb` executado somente nos CTs 9106/9111, restaurados dos backups verificados do doc. 52, sem rede externa. O B5 ensaiado é idêntico ao desta entrega. Depois do ensaio, o hook recebeu duas guardas adicionais (Python isolado e rejeição de recibo após o prazo), cobertas pelos testes automatizados. O confirmador do host desta entrega foi usado no ensaio real.
- `--check` não parou o ambiente. Timeout real de um segundo manteve código/esquema anteriores, manutenção e escritores parados. O operador reabriu somente o clone antigo para a nova tentativa.
- Durante a pausa, outra execução foi recusada pelo lock; os cinco serviços estavam inativos e ainda não existiam as tabelas clientes/espacos.
- A primeira geração falhou porque metadados temporários estavam sob a pasta privada `/root`. Nenhum recibo foi emitido e o deploy continuou parado. A repetição, com `--tmpdir /var/tmp`, concluiu os dois backups. O roteiro definitivo incorpora essa correção. O ensaio teve essa intervenção; não é apresentado como execução sem ajustes.
- Arquivos dos clones: banco de 2.206.220.087 bytes e aplicação de 8.757.210.619 bytes, `zstd -t` e SHA-256 integral conferidos pelo confirmador. Geração/conferência da tentativa válida: **877,07 s (14 min 37 s)**. Pausa total até a confirmação, incluindo a tentativa anterior e ajuste: **981 s (16 min 21 s)**. A segunda cópia desses arquivos descartáveis não foi repetida neste ensaio; transferência e dupla cópia dos backups de referência já estão comprovadas no doc. 52. Seu tempo deve ser incluído na janela real.
- Somente após o recibo o B5 atualizou e expandiu. Preenchimento, conciliação do administrador, restrições e auditoria passaram; conteúdo das 13 tabelas preservado e zero inconsistências.
- Os clones migrados foram substituídos pelos backups recém-criados. Recuperaram o SHA anterior `dc9949e3690744b72c5071d919955c09ad2be9b8`, sem tabelas T2. Chaves e 20 cifras, 47 arquivos e contagens conferidos. Os hashes das 13 tabelas restauradas foram comparados com a referência extraída do próprio backup e coincidiram. API voltou a responder 200 após a retomada deliberada da cópia antiga.
- **23 testes automatizados aprovados** (19 B5, 2 recibo, 2 confirmador, incluindo cenários parametrizados). CI também executa esses testes. Nenhuma aplicação funcional foi modificada.

O procedimento está validado para solicitar a publicação/migração T2 na homologação representativa, após revisão/merge autorizado desta entrega e conferência do SHA final. O backup final do servidor atual ainda será gerado na janela autorizada, enquanto os escritores estiverem parados; o ensaio não o substitui. A aprovação deste código e a autorização de publicação são decisões separadas.

## Encerramento

Os clones 9106/9111, seus volumes, os backups descartáveis do ensaio e a área temporária foram removidos após as conferências. Os backups de referência do doc. 52 permanecem preservados. No servidor atual, o SHA continua `dc9949e3690744b72c5071d919955c09ad2be9b8`, não existem tabelas clientes/espacos, health responde 200 e os serviços foram conferidos individualmente ativos. **Sem deploy ou migração no servidor atual.**

## Publicação posterior autorizada

O PR #85 foi incorporado em `af4c6d1e9e1d324ebd10e404af149b78a28270a4`. Após nova autorização do responsável, o T2 foi aplicado e validado no servidor atual em 01/11/2026; execução, backups finais e ajustes operacionais em [doc. 54](54-t2-publicacao-homologacao.md).
