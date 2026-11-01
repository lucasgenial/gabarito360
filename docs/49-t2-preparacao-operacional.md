# 49 — T2: preparação do ensaio e da janela de corte

Autorização: após aprovar o merge do PR #80, o responsável autorizou preparar o ensaio e a janela, sem deploy. PR #80 incorporado em `1d7ed629f4a321788c9687c041a14f0ccd3cdd84`. Responsável operacional: administrador do ambiente; execução e decisão de abertura dependem de autorização específica.

## Entrega autorizada

Preparar ensaio sintético isolado, usando a hierarquia e as quantidades históricas do doc. 42, e um cenário de crescimento. Não copiar backups, chaves ou dados pessoais de produção nesta entrega. Preparar opção explícita `--manter-manutencao` no B5 para terminar após instalação/migração/cache, mantendo API/web em manutenção e workers/agendador parados. O modo normal conserva seu comportamento. Nenhum comando de preenchimento, conciliação, restrição ou ativação T2 será chamado automaticamente pelo deploy.

O ensaio usa banco descartável próprio, sem rede, com nome fixo e ambiente testing. Cria dados inteiramente fictícios, aplica expansão, preenchimento, conciliação e restrições, registra contagens/durações e repete a restrição. Não mede HTTP, drenagem, backup/restauração ou desempenho do servidor real. Não autoriza o corte de produção nem substitui validação de backup/chaves.

## Estado e condições para marcar a janela

Preparação técnica local, sem conexão à produção. O SHA incorporado do PR #80 é base de código, **não é release de ativação**: `espacos.ativo` continua false. O último SHA publicado registrado é `dc9949e3690744b72c5071d919955c09ad2be9b8` (doc. 42); não foi reconfirmado nesta entrega e não deve ser reutilizado como `--from` sem nova conferência.

Antes de marcar data/hora, preencher e revisar:

| Campo | Evidência necessária / situação atual |
|---|---|
| Origem e alvo | SHA real no CT 111 e SHA de release de ativação aprovado, ambos completos; CI verde e diff revisado |
| Versão e volume | MariaDB/PHP instalados e inventário atualizado comparados ao doc. 42; local usa MariaDB 10.11.14/PHP 8.3.6 |
| Manifesto | 2 redes, 4 núcleos, 7 escolas, 2 clientes provisórios/13 espaços; mudança ou SEGES sem conciliação interrompe |
| Administrador | ID da conta correta identificado pelo responsável; duas atribuições distintas de rede, sem concessão global. O ID 1 do ensaio é fictício |
| Backup e chaves | Backup recente de banco/arquivos e restauração com APP_KEY/DADOS_HASH_KEY conferida, sem registrar valores; cópias coerentes e ponto de recuperação anotados |
| Escritores | Dois workers, timer e serviço ativo do agendador, importadores e qualquer outro processo externo identificados; lista e método de parada/retomada revisados |
| Janela | Data/hora BRT, responsável disponível, usuários avisados pelo responsável, conflito com backup/tarefas descartado; nada agendado nesta preparação |
| Recuperação | Tempo de restauração consistente e impacto de perda de escritas conhecidos; não assumir que restauração cabe na reserva de contingência |

A conta de aluno sem escopo identificada no inventário permanece sem concessão nova; conciliação pertence a V1. Não associá-la pelo nome, CPF ou por suposição.

## Ensaio local reproduzível

Arquivo: `scripts/ensaios/t2-corte.php`. Somente aceita ambiente `testing`, banco `g360_t2_ensaio_test`, socket `/tmp/g360-t2-mariadb/mysql.sock`, datadir `/tmp/g360-t2-mariadb/data` e MariaDB com `skip_networking` ativo. Exige `--recriar-banco`; esse comando **apaga somente o banco descartável fixo** antes de cada execução. Não apontar o diretório temporário a backup/volume real. Não copiar `.env` de produção.

Em WSL com a instância isolada já inicializada e permissões locais adequadas:

```bash
mariadb --socket=/tmp/g360-t2-mariadb/mysql.sock -e 'CREATE DATABASE IF NOT EXISTS g360_t2_ensaio_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
# Chave abaixo é pública/fictícia, exclusiva do ensaio.
export APP_ENV=testing APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
export DB_CONNECTION=mariadb DB_DATABASE=g360_t2_ensaio_test DB_USERNAME=root DB_PASSWORD=
export DB_SOCKET=/tmp/g360-t2-mariadb/mysql.sock
php scripts/ensaios/t2-corte.php --recriar-banco --escala=1 > /tmp/g360-t2-ensaio-1.json
php scripts/ensaios/t2-corte.php --recriar-banco --escala=10 > /tmp/g360-t2-ensaio-10.json
```

Esperado: código 0, `ocorrencias=0`, contagens preservadas e `repeticao_sem_ddl=true`. Falha: não usar resultado parcial para dimensionar a janela; corrigir/repetir só no banco descartável. O programa não inicia Redis, workers ou envio externo e ativa o adaptador apenas na memória desse processo CLI. Não altera o arquivo de configuração.

Escala 1 reproduz **quantidades** do inventário histórico, não o conteúdo: 14 turmas, 414 alunos, 4 provas/gabaritos, 8 associações, 180 questões, 47 cartões/notas, 2.115 respostas e 28 logs. Escala 10 multiplica alunos e correções, conservando instituições/provas/turmas. Nenhum cenário simula 100 instituições, volume de imagens, tamanhos reais de cifras, hardware de produção ou carga concorrente.

Antes do corte real, repetir em ambiente equivalente isolado com volume/distribuição atualizados, backup/restauração e release candidato final. Se usar cópia restaurada autorizada, preservar os controles de acesso e as chaves, bloquear rede/SMTP/gateway e não publicar dados em relatórios. Esse uso de cópia real não ocorreu aqui.

## Orçamento proposto — ainda não agendado

Reservar inicialmente **45 minutos**, fora das tarefas de backup/cópia e com operador disponível. É uma proposta de reserva, não previsão de indisponibilidade nem garantia de recuperação. Confirmar o fuso dos timers antes de escolher horário: os documentos registram backup às 02:30, cópia às 06:00 e bloqueio de cópia entre 02:00 e 05:00.

| Parte | Reserva inicial | Como aprovar |
|---|---|---|
| Drenagem | até 5 min | B5 `--wait 300`; fila não zerou: cancelar antes de atualizar código |
| Instalação/expansão/backfill/conciliação/restrição | 10 min | Revalidar no ensaio equivalente; aumentar orçamento se 2 × tempo medido exceder reserva |
| Verificações de dados, acessos e abertura | 15 min | Operador disponível e roteiro funcional executável |
| Contingência operacional | 15 min | Não representa RTO de restauração; falha após DDL pode exigir manutenção além da reserva |

Backup novo e validação da restauração devem ser resolvidos **antes** de consumir a janela, com ponto final coerente definido após cessar escritores. Se esse backup final exigir parada adicional, medir e incluí-la explicitamente. Não começar DDL com orçamento restante insuficiente. O lock de preenchimento/conciliação tem TTL de uma hora: não permitir outra execução nem presumir liberação segura quando ele expirar. O corte usa também lock de conexão sem TTL.

## Sequência da janela futura — somente após autorização

### 1. Conferência e drenagem

No CT 111, como root; usar o script revisado desta entrega (bootstrap em `/tmp` se a produção ainda tiver o B5 anterior). Conferir SHA-256 do arquivo com a versão aprovada antes de executar. Não fazer checkout antecipado. Definir `ALVO` e `ATUAL` pelos SHAs confirmados, não pelos exemplos históricos.

```bash
bash /tmp/g360-deploy.sh --commit "$ALVO" --from "$ATUAL" --check --manter-manutencao
```

Esperado: preflight aprovado; nenhuma publicação/manutenção/migração. `--check` faz fetch e cria lock/temporários, portanto não é uma inspeção absolutamente sem escrita em disco. Resolver árvore suja, serviço inativo, mudança de SHA ou fila problemática antes da janela. Conferir externamente os importadores/escritores não administrados por B5.

### 2. Instalação mantendo a aplicação fechada

```bash
bash /tmp/g360-deploy.sh --commit "$ALVO" --from "$ATUAL" --wait 300 --manter-manutencao
```

Esperado: `PREPARADO EM MANUTENÇÃO`, não `DEPLOY CONCLUÍDO`. B5 fecha web, para timer e serviço de agendamento, drena com API ainda aberta, fecha API, reconfere fila e para workers. Confere todos parados **antes** do código/DDL. Instala dependências, executa migrações aditivas só da API, recria caches e termina sem abrir HTTP nem iniciar workers/timer. Não executa backfill, conciliação ou restrições e não modifica `.env`/flag. O release de ativação ainda precisa ser preparado/aprovado separadamente; não editar `config/espacos.php` à mão no servidor.

Falha antes de mudar código: B5 tenta restaurar o funcionamento anterior. Após mudar código: mantém manutenção; não rerodar cegamente B5, pois seu preflight recusa manutenção preexistente. Investigar passo e usar plano de recuperação aprovado.

### 3. Preenchimento e conferência

Aplicação ainda em manutenção; demais escritores confirmados parados. Executar na API como `www-data`, preservando logs técnicos em área restrita. `ADMIN_REVISADO` é o ID confirmado da conta correta; nunca copiar o ID fictício do ensaio.

```bash
set -euo pipefail
: "${ADMIN_REVISADO:?Confirme o ID da conta antes de aplicar}"
cd /home/lucasdev/projetos/gabarito360/apps/api
runuser -u www-data -- php artisan g360:inventariar-espacos
runuser -u www-data -- php artisan g360:preencher-espacos
runuser -u www-data -- php artisan g360:preencher-espacos --aplicar --manifesto=legado-20261031-v1
runuser -u www-data -- php artisan g360:conciliar-admin-espacos --usuario="$ADMIN_REVISADO" --manifesto=legado-20261031-v1
runuser -u www-data -- php artisan g360:conciliar-admin-espacos --usuario="$ADMIN_REVISADO" --manifesto=legado-20261031-v1 --aplicar
runuser -u www-data -- php artisan g360:auditar-espacos
```

Conferir o código de saída e resultado **de cada linha antes da próxima**. Esperado: manifesto exato, contagens iguais à fotografia final, 2 clientes/13 espaços, autoria desconhecida preservada, duas atribuições de rede e zero inconsistências. Divergência interrompe; não sobrescrever, deduplicar ou conceder acesso para fazer o comando passar.

### 4. Restrições

```bash
runuser -u www-data -- php artisan g360:restringir-espacos
runuser -u www-data -- php artisan g360:restringir-espacos --aplicar --manifesto=legado-20261031-v1 --escritores-parados
runuser -u www-data -- php artisan g360:restringir-espacos
runuser -u www-data -- php artisan g360:auditar-espacos
```

Esperado: primeira simulação íntegra; aplicação registra passos e contagens preservadas; segunda simulação sem passos pendentes; auditoria sem ocorrências. `--escritores-parados` é declaração do operador, não detecção automática. Flag desligada bloqueia; retornar à preparação do release, sem contornar a guarda.

Falha de DDL: manter API/web em manutenção e todos os escritores parados. Registrar último passo sem SQL/bindings/dados pessoais, diagnosticar e repetir o mesmo plano após corrigir a causa. DDL confirmado não é desfeito por transação. Não usar rollback de migrações, desativar FKs ou restaurar apenas Git.

### 5. Validação e retomada controlada

Antes de abrir, validar os serviços/configuração, estado das filas (incluindo reservados/atrasados), jobs falhos sem payload e hashes/cobertura. Confirmar novo código e cache com adaptador ativo. Recarregar PHP-FPM antes de retirar manutenção. A revisão do release deve incluir teste autenticado dos dois contextos, recusas cruzadas e ciclo OMR; o ensaio local já cobre constraints/fluxos, mas não certifica acesso real.

Após decisão explícita de abertura dentro da janela autorizada:

```bash
systemctl reload php8.3-fpm
systemctl start gabarito360-worker@1 gabarito360-worker@2
for unidade in nginx php8.3-fpm redis-server gabarito360-worker@1 gabarito360-worker@2; do
  systemctl is-active --quiet "$unidade" || exit 1
done
(cd /home/lucasdev/projetos/gabarito360/apps/api && runuser -u www-data -- php artisan up)
(cd /home/lucasdev/projetos/gabarito360/apps/web && runuser -u www-data -- php artisan up)
```

Conferir cada serviço individualmente, HTTP 200 em `/`, `/login`, `/api/v1/health` via nginx local e HTTPS público, login e leitura autorizada. Sem validação funcional, health 200 não encerra o corte. Só então iniciar `gabarito360-agendador.timer`, conferir filas e acompanhar erros. Não executar `queue:retry all`: jobs falhos antigos precisam de revisão de contexto e compatibilidade.

Falha na abertura: recolocar API/web em manutenção e parar workers/timer/serviço de agendamento; registrar se houve escritas novas. Correção progressiva é preferida após constraints; restauração consistente de banco/arquivos/chaves exige aprovação e avaliação dessas escritas. Não prometer retorno ao código legado depois de aceitar matrículas iguais entre espaços.

## Registro de execução a preencher na janela

Guardar em área operacional restrita: operador e autorização; SHAs antes/depois; versão do banco; inventário e manifesto; identificação técnica do backup e teste de restauração; horários UTC/BRT de fechamento, drenagem, DDL e abertura; contagens/cobertura; código de saída por passo; verificação de contexto/fila; decisão de abrir ou manter manutenção. Não anexar `.env`, chaves, dumps, tokens, fotos ou payloads ao Git/PR.

## Evidências desta preparação

Ensaios em 31/10/2026 BRT (01/11 UTC), WSL local, MariaDB 10.11.14 e PHP 8.3.6. Base da API: merge aprovado do PR #80. Evidências versionadas contêm somente metadados e dados sintéticos, com hash do script:

- [Escala histórica 1×](operacao/evidencias/t2-ensaio-sintetico-2026-10-31-1x.json).
- [Alunos/correções 10×](operacao/evidencias/t2-ensaio-sintetico-2026-10-31-10x.json).

| Etapa | Histórico 1× | Crescimento 10× |
|---|---:|---:|
| Expansão | 3,42 s | 3,43 s |
| Preenchimento | 3,71 s | 36,92 s |
| Restrição (inclui pré/pós-verificação) | 12,43 s | 18,61 s |
| Total das etapas de corte medidas | 21,32 s | 61,65 s |

Totais excluem recriação do esquema e geração de dados fictícios; incluem inventário, conciliação, auditorias e repetição sem DDL. Zero ocorrências nos dois cenários, nenhuma contagem alterada, repetição sem passos pendentes. Dados grandes tornam o preenchimento mais caro; não extrapolar esses tempos para hardware/dados reais sem ensaio equivalente.

B5: 13 testes simulados passaram, incluindo manutenção preservada, ausência de abertura/retomada automática, ausência de comandos T2, `--check` sem publicação e serviço que não para bloqueando antes de trocar código. Sintaxe Bash/PHP aprovada. Guardas do ensaio recusaram ausência de confirmação, escala não permitida e banco diferente do banco fixo descartável. CI e revisão final serão registrados no PR.

Nenhuma data foi agendada e nenhum procedimento foi executado na produção. Não houve cópia de dados reais, abertura de backup, deploy, mudança de acesso ou ativação. Próxima decisão: aprovar este preparo; depois validar ambiente equivalente/recuperação e revisar o release de ativação antes de solicitar autorização operacional.

