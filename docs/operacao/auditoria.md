# Conservação e recuperação da auditoria

**Atualizado em:** 10/11/2026, por lucasgenial
**Ambiente:** ambos (produção com captura ativa; homologação com captura própria). Os ensaios rodam em destino isolado.
**Quando usar:** conferir a auditoria, gerar a cópia mensal cifrada, ensaiar ou executar a recuperação depois de uma restauração, ou investigar lacunas.
**Duração estimada:** conferência, minutos. Ensaio de restauração, horas (depende do volume).

Este documento descreve a operação da auditoria geral de requisições (AU1). O desenho e as regras de privacidade estão em [11-seguranca-e-privacidade.md](../arquitetura/11-seguranca-e-privacidade.md). Funcionalidade: [FN-004](../funcionalidades/004-auditoria/planejamento.md). O responsável pela operação é o administrador da infraestrutura.

> **Atenção:** os comandos deste documento só leem ou geram arquivos, com uma exceção: a restauração, que exige autorização específica antes de parar serviços ou restaurar backups. **Não existe comando de expurgo, `DROP PARTITION` nem agendamento de limpeza.** Não execute exemplos genéricos de `REVOKE` em credenciais compartilhadas: isso pode interromper outras funções. Não ponha nomes, motivos livres ou dados de incidente na configuração da aplicação.

## Estado em produção

O responsável aprovou a retenção de 180 dias com preservação excepcional documentada. O padrão e o `.env.example` valem 180. Um valor vazio explícito sinaliza "política não configurada" e evita ativação implícita. Em 02/11/2026, a captura foi ativada na produção, com credencial separada de gravação (INSERT e SELECT), negação de UPDATE, DELETE e ALTER comprovada e sem expurgo. Os arquivos `.env` existentes não são alterados por uma release.

## Pré-requisitos

- Release e esquema compatíveis.
- Acesso de leitura ao banco.
- Manifesto de referência protegido.
- Destino isolado, fora de qualquer diretório público.
- Autorização específica antes de parar serviços ou restaurar backups.

Execute os comandos de aplicação em `apps/api`.

## Passos: conferir a auditoria (somente leitura)

1. Execute `php artisan g360:conferir-auditoria`. O comando emite contagens e SHA-256 por mês, sem identidades nem conteúdo de eventos.
2. Interprete o resultado:
   - Prazo vazio significa política ainda não definida.
   - `--dias=180` é uma simulação explícita. Ela não aprova a política e não exclui dados.
   - Meses listados em `AUDITORIA_MESES_PRESERVADOS` (por exemplo, `2026-01,2026-02`) ficam preservados por inteiro.
   - Configuração inválida impede a conferência.
3. Só um mês completamente anterior ao corte UTC pode ser classificado como fora do prazo.
4. Rode fora do pico, com os escritores parados quando precisar comparar com um backup. Uma varredura durante o tráfego é diagnóstico, não fotografia certificada de backup.

A varredura percorre a tabela inteira em ordem de mês e UUID, com cursor e memória limitada na aplicação. Os drivers podem manter buffers internos: o ensaio de volume equivalente continua obrigatório.

## Passos: preservação excepcional

Execute antes de qualquer eliminação futura.

1. Registre, em repositório operacional restrito: referência do incidente ou da apuração, meses cobertos, justificativa, data e responsável pela revisão.
2. Acrescente todos os meses cobertos a `AUDITORIA_MESES_PRESERVADOS`.
3. Gere o manifesto com `g360:conferir-auditoria`.
4. Preserve uma cópia cifrada separada (ver a próxima seção).
5. Revise periodicamente. Remover o bloqueio exige decisão registrada e nova conferência. Não é automático.

## Passos: cópia mensal cifrada (AU1-B3)

1. Pare os escritores. Escolha um destino privado fora de qualquer diretório público, com o diretório pai já criado e o diretório final **inexistente**.
2. Gere o pacote de um mês UTC fechado:

   ```bash
   php artisan g360:arquivar-auditoria AAAA-MM /destino/privado/revisao-nova
   ```

   O comando aceita só mês fechado. Cria o diretório com modo 0700 e os arquivos com 0600 em Linux (no Windows, aplique uma ACL equivalente antes). Não habilita captura, não escreve no banco e não faz expurgo. Não há agendamento.
3. O pacote contém `eventos.enc` (um evento cifrado e autenticado por linha, com a `APP_KEY`) e `manifesto.json` (mês, contagem e digests). O digest do conteúdo canônico coincide com o manifesto de conferência do banco. O manifesto só é gravado depois de fechar o arquivo. Uma falha deixa pacote incompleto, que não pode ser usado. Não o complete à mão: investigue e gere outra revisão em novo destino.
4. Verifique o pacote:

   ```bash
   php artisan g360:arquivar-auditoria AAAA-MM /destino/privado/revisao-nova --verificar
   ```

   A verificação não consulta o banco e não expõe conteúdo decifrado. Confere chave, cifra, sequência UUID, quantidade e digests. Uma falha retorna código diferente de zero.
5. Guarde o SHA-256 de referência **externamente**, com acesso separado. Um pacote e seu manifesto alterados em conjunto não são evidência independente.
6. Um evento tardio em mês fechado exige nova revisão e comparação com a referência anterior. Não substitua a anterior.
7. As cópias cifradas ainda contêm dados pessoais. Acesso, transporte, chaves e conservação seguem o procedimento restrito de backup. O pacote não autoriza eliminação.

## Passos: ensaio de restauração da auditoria

1. Prepare um destino isolado, com integrações externas e envio de mensagens desligados. Pare os escritores da origem do ensaio e drene a fila `baixa`. Registre pendências e falhas sem revelar payload.
2. Salve a saída de `g360:conferir-auditoria` em um manifesto restrito. Guarde um backup consistente do banco e do Redis e as chaves de cifra, em duas cópias. Guarde o SHA-256 do manifesto e do backup em referência independente. Um digest sozinho não impede a adulteração conjunta.
3. Restaure banco, fila e chaves **somente no destino do ensaio**. Compare com:

   ```bash
   php artisan g360:conferir-auditoria --comparar=/caminho/manifesto.json
   ```

   Um código de saída diferente de zero bloqueia o avanço. A comparação valida o conteúdo armazenado e não comprova eventos que nunca chegaram à fila.
4. Consuma a fila cifrada de teste. Simule indisponibilidade e reinício. Confira a reentrega sem duplicação, a ausência de segredos nos logs e o esvaziamento da fila. Gere o manifesto posterior em separado, porque consumir pendências pode acrescentar eventos legitimamente.
5. Meça duração, espaço, latência de gravação e consultas mensais em volume representativo. O teste lógico de 1.000 eventos não é benchmark de capacidade nem restauração de contêiner.

### Ensaios automatizados

- **Job de CI `auditoria-recuperacao`.** Cria MariaDB e Redis descartáveis. Prova os grants da credencial do job, a falha e a recuperação do banco dedicado, o payload cifrado, o snapshot do Redis com job pendente, a parada e o reinício e o replay idempotente. O script exige o banco `g360_auditoria_ci`, loopback e ambiente de teste. **Não o execute no servidor.**
- **Script `scripts/auditoria/ensaio-restauracao.sh`.** Exclusivo do runner de CI, com banco fictício e Docker local. Faz dump de todos os bancos do serviço de teste (com grants), salva o Redis com pendência, para as origens e restaura em contêineres novos com volumes próprios. Confere manifesto antes do consumo, grants, tabela de controle fora da auditoria, migrations, arquivo mensal, job cifrado pendente e replay sem duplicação. A chave da aplicação fica no runner e não é impressa. Backups e chaves não são publicados como artefatos. O tempo medido não é RTO de produção.
- **Ensaio de contêineres completos.** A recuperação em clones isolados, com chaves, arquivos, reinício e 1,8 milhão de eventos sintéticos, está registrada no histórico (evidência `au1-recuperacao-volume-2026-11-01.json`, em [evidencias](evidencias/)). Ela não restaura o contêiner Proxmox real, as unidades systemd nem os arquivos de usuários.

## Referência de capacidade (ensaio de 01/11/2026)

O ensaio em clones isolados usou 1.800.000 eventos sintéticos. A hipótese foi 10.000 requisições auditadas por dia durante 180 dias. É um envelope declarado, não tráfego medido.

| Operação | Resultado |
|---|---:|
| Inserção de 1,8 milhão de eventos em lotes | 102 s |
| Inventário integral com digests mensais | 83 s |
| Cópia cifrada de um mês de 300.000 eventos | 32 s (274 MB) |
| Verificação desse arquivo | 11 s |
| Consulta mensal de 100 eventos (p95) | 2,6 ms |
| Consumo de 1.000 jobs por dois workers | 2,8 s |
| Restauração completa em volumes novos, do backup à comparação | 8 min 9 s |

Estes tempos valem para esse equipamento, com cache aquecido e sem usuários concorrentes. Não são RTO nem SLA. O ensaio manteve 1.801.001 registros após a reentrega, sem duplicação, e recusou UPDATE, DELETE e ALTER na credencial restrita. Um backup com manutenção ativa faz os workers aguardarem a retirada dela. Ver [FN-004](../funcionalidades/004-auditoria/planejamento.md).

## Privilégios do banco

1. A conexão `auditoria_gravacao` usa `AUDITORIA_DB_USERNAME` e `AUDITORIA_DB_PASSWORD` próprios, sem herdar a credencial geral. A configuração ausente faz o job falhar. Não existe fallback para a conexão geral.
2. Conceda à credencial do job somente INSERT e SELECT na tabela de auditoria. Mantenha separadas as credenciais de migration e de conservação. Um leitor operacional recebe só SELECT.
3. O CI ensaia a negação de UPDATE, DELETE e ALTER.
4. Documente os grants exatos e ensaie numa cópia antes de aplicar. O administrador do banco continua tecnicamente capaz de alterar dados. A cópia mensal cifrada e a referência externa oferecem a evidência independente.

## Monitoramento

1. Monitore o alerta `auditoria_enfileiramento_indisponivel`. Ele passa pela lista positiva de logs, sem contexto livre.
2. Monitore jobs falhos, o atraso e a profundidade da fila `baixa` e o espaço em disco.
3. Uma falha abre incidente e registra a possível lacuna (ver [incidentes.md](incidentes.md)).
4. Os limites e o destinatário operacional ainda serão definidos. Nenhum e-mail automático está configurado.

**Particionamento:** não aplique o particionamento nativo por mês no esquema atual. A chave UUID global conflita com a exigência de incluir o mês nas chaves únicas do MariaDB (limitação do particionamento). A cópia mensal verificável substitui o particionamento. Ela não reduz o banco.

## Falha, interrupção e escalonamento

| Sinal | Conduta |
|---|---|
| `politica_definida=false` | Registre o prazo como pendente. Não leia os meses como liberados para exclusão |
| `restauracao_confere=false` ou saída 1 | Pare a aprovação da restauração. Confira se a origem estava congelada, se o manifesto corresponde ao backup e se a fila foi consumida na etapa correta. Não sobrescreva a referência para esconder a divergência |
| Conferência indisponível | Confira esquema, configuração, permissão de leitura e validade do manifesto. Não despeje credenciais nem dados de eventos no terminal |
| Job falho ou alerta de enfileiramento | Acione o administrador, preserve as evidências e registre o intervalo da possível lacuna. Repita o job só depois de diagnosticar a falha e confirmar a chave de cifra |

## Verificação

1. `g360:conferir-auditoria` termina com código 0 e mostra contagens por mês.
2. `g360:arquivar-auditoria ... --verificar` termina com código 0.
3. A fila `baixa` está sem pendências e `failed_jobs` não tem eventos de auditoria.
4. O SHA-256 de referência está guardado fora do pacote.

## Reversão

O comando de conferência não altera dados, então não precisa de reversão. Em caso de falha de restauração:

1. Mantenha o destino isolado e a origem preservada.
2. Retome apenas pelo roteiro de [backup-e-restauracao.md](backup-e-restauracao.md) e pela decisão do administrador.
3. Não reverta o banco original e não desabilite proteções para obter um resultado verde.

O canal de escalonamento é o acompanhamento deste projeto com o responsável. Nenhum envio automático foi configurado.

## Referências

- [11-seguranca-e-privacidade.md](../arquitetura/11-seguranca-e-privacidade.md), seção "Auditoria geral (AU1)"
- [backup-e-restauracao.md](backup-e-restauracao.md)
- [FN-004](../funcionalidades/004-auditoria/planejamento.md)
- Evidência sanitizada: [au1-recuperacao-volume-2026-11-01.json](evidencias/au1-recuperacao-volume-2026-11-01.json)

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/32` e a versão anterior deste runbook | "Sem ativação no servidor", "captura desligada" | Ativada na produção em 02/11/2026 (`docs/100`). `AUDITORIA_ATIVA` continua `false` por padrão no código |
| A versão anterior deste runbook | Apontava `docs/76` e `docs/75` | O conteúdo dos documentos 75 e 76 foi incorporado na seção "Referência de capacidade" e no histórico de FN-004. O resumo do ensaio e a decisão de arquivo mensal estão neste runbook |
