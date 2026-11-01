# 51 — T2: confirmação do backup e recuperação dos serviços

Autorização: merge do PR #82, confirmação do backup e validação da recuperação, **sem deploy nem migração no servidor**. O responsável deve ser avisado quando a versão e a operação estiverem aptas; execução exige nova aprovação expressa.

## Release incorporado

[PR #82](https://github.com/lucasgenial/gabarito360/pull/82) incorporado em `b2fa772f3e46a5eb02e76336fc094bc42a1a607b`. As quatro verificações do PR e da main passaram. Esse é o candidato de ativação, não o SHA publicado. A produção segue em `dc9949e3690744b72c5071d919955c09ad2be9b8`.

## Backup: integridade confirmada, atualidade ainda pendente

Em 31/10/2026 BRT, a cópia concluída mais recente continuava sendo a de 08h50, com arquivos daquela madrugada. O SSD foi consultado pelo mecanismo operacional de leitura e desmontado ao fim. Os SHA-256 fornecidos pela camada do servidor foram confrontados com cálculo integral dos arquivos locais:

| Arquivo | SHA-256 idêntico nas duas camadas |
|---|---|
| `vzdump-lxc-106-2026_10_31-02_36_34.tar.zst` | `cd9cfde401f1b6c012b30cb787ba8d9b9e6f78e5da593236a968e0bedc99a87a` |
| `vzdump-lxc-111-2026_10_31-03_00_14.tar.zst` | `7eef32b99d7c29079d5ff00e1790cb913d2b21918b8772e1c2829d604cdf424b` |

Os arquivos são íntegros, mas anteriores a L3. A ausência de DADOS_HASH_KEY nesse backup, constatada no doc. 50, permanece. A próxima ocorrência consultada no timer era **01/11/2026 às 02h35min34s BRT**, sujeita à execução efetiva; cópia do Windows prevista às **06h**, ou quando o computador estiver disponível. Não antecipamos, modificamos ou declaramos concluída essa execução futura.

O próximo checkpoint é conferir ambas as cópias posteriores a L3, hashes e restauração das chaves/cifras. A rotina diária não substitui o backup coerente final com escritores parados na janela autorizada. A idade dos arquivos atuais não é uma garantia de RPO; o RPO operacional deve considerar o ponto realmente restaurável e as escritas posteriores.

## Serviços restaurados: ensaio concluído em 01/11 BRT

O filesystem do CT 111 foi extraído em WSL, sob raiz privada `/recuperacao-t2`, fora do Git. Os binários recuperados são nginx 1.24.0, PHP-FPM 8.3.6 e Redis 7.0.15. O código candidato foi colocado em `/opt/candidato`; os vendors recuperados têm composer.lock idêntico ao candidato em API e web. MariaDB local 10.11.14, sem porta TCP, usa somente a base fictícia `g360_t2_ensaio_test` gerada pelo ensaio T2 de escala 1.

Todos os serviços ficaram em namespaces próprios de rede, mounts e PID. Somente loopback foi habilitado. Configurações da aplicação foram substituídas por valores fictícios; nenhum segredo recuperado foi carregado e não houve acesso a SMTP, gateway, Cloudflare ou banco de produção. O nginx do ensaio usa porta 8080 e dois pools FPM com dois processos cada. Redis roda como usuário redis, AOF ativo, 256 MB e política volatile-lru.

[Evidência sanitizada](operacao/evidencias/t2-servicos-restaurados-2026-11-01.json):

- Sintaxe nginx e PHP-FPM aprovada; ambos iniciaram com Redis e responderam HTTP.
- `/`, `/login`, health, painel da API e relatórios das duas redes: HTTP 200.
- Login web através de nginx/FPM com CSRF, API real local e sessão no Redis; painel autenticado acessível.
- Dois workers vivos; job técnico fictício despachado pela aplicação e consumido pela fila Redis.
- `schedule:run` executado com sucesso. As unidades restauradas de worker, serviço e timer passaram em `systemd-analyze verify`.
- Web/API em manutenção responderam 503. Workers e FPM foram parados e iniciados, o esquema foi reconferido, a manutenção foi retirada e o painel voltou a 200.
- Um job foi enfileirado com workers parados; Redis foi encerrado e reiniciado a partir do AOF. A sessão continuou válida e o job foi consumido na retomada.
- Auditoria T2 final sem inconsistências e nenhum job falho. A retomada exige permanecer em `/painel`, sem aceitar redirecionamento para login.

Início dos serviços: **0,96 s**; sequência funcional e recuperação: **23,94 s**. A segunda extração integral do CT 111 levou **181,42 s** (3 min 1 s) no WSL, com arquivo já lido anteriormente. Os tempos de serviços excluem transferência, extração, preparação do banco e inicialização de um contêiner completo. **Não são RTO de desastre nem justificativa para garantir uma janela de 45 minutos.**

## Reprodução e limites

Os auxiliares `scripts/ensaios/t2-preparar-servicos.py` e `t2-servicos.py` são exclusivos do diretório local fixo. O segundo recusa rede externa e exige namespace de mounts distinto do PID 1. Não executar no Proxmox/CT real.

Preparação usada: extração do CT 111 na raiz privada; `git archive` do SHA candidato em `opt/candidato`; cópia dos vendors correspondentes; MariaDB local iniciado com o socket/datadir fixos do doc. 49; `t2-corte.php --recriar-banco --escala=1` em testing para gerar apenas dados fictícios. Criou-se usuário local `ensaio_runtime`, com senha fictícia e acesso somente a essa base. O socket foi exposto por bind mount somente dentro do namespace do ensaio.

Executar a preparação como root do WSL e o teste com `unshare --mount --net --pid --fork python3 scripts/ensaios/t2-servicos.py`. O teste gera helper/job exclusivamente na cópia e encerra seus processos no finally. A saída contém somente estados, rotas e tempos. Não reutilizar o banco sintético para dados reais.

Limites: não houve `pct restore`, boot do systemd recuperado, teste de reinício automático do timer/worker nem validação TLS/Cloudflare. As definições das unidades foram verificadas, e os executáveis/comandos foram exercitados diretamente. Hardware e kernel diferem da produção. Recuperação completa do contêiner e RTO continuam pendentes e devem usar o próximo backup atual antes de liberar a janela.

## Decisão operacional

O candidato passou no CI, no corte da cópia atual (doc. 50) e no ensaio dos serviços. **Ainda não está liberado para deploy/migração no servidor.** Faltam:

1. Backup posterior a L3 nas duas camadas, com chaves e recuperação conferidas.
2. Ensaio de restauração completa com retomada supervisionada e duração de recuperação registrada.
3. Janela, operador, ponto final coerente e nova autorização expressa de deploy/migração.

Não houve alteração de código, dados, acesso, serviços ou rotina de backup em produção. A próxima etapa é conferir o novo ciclo de backup e usá-lo no ensaio completo; o responsável será avisado quando esses critérios estiverem atendidos.
