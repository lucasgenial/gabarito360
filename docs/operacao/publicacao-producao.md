# Publicação na produção

**Atualizado em:** 10/11/2026, por lucasgenial
**Ambiente:** produção (CT 111, banco no CT 106)
**Quando usar:** publicar uma versão que o responsável aprovou para a produção, ou aplicar um ajuste operacional pontual aprovado.
**Duração estimada:** 5 a 15 minutos sem pausa de backup. Some o tempo do backup final quando o roteiro o exige (cerca de 15 a 20 minutos medidos em ensaio).

Este documento descreve o roteiro único de deploy (B5), a publicação por fase, o corte com backup final, a home pública independente e o histórico das publicações. A topologia está em [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md). Funcionalidade: [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md).

> **Atenção:** a produção exige aprovação expressa do responsável para cada merge, deploy e migração. Uma aprovação não vale para a próxima ação. Nunca publique toda a `main` para corrigir um incidente isolado. Não altere `.env`, DNS, nginx, PHP-FPM nem unidades systemd com este roteiro. Não use reset forçado do Git nem rollback de migrations.

## Regras gerais

1. Publique primeiro na homologação ([publicacao-homologacao.md](publicacao-homologacao.md)).
2. Publique API e web juntas. Os argumentos antigos `api`, `web` e `all` não existem mais.
3. Execute o roteiro dentro do CT 111, como `root`. O operador confere o CI e a revisão antes. O roteiro não consulta o GitHub.
4. Execute migrations **só na API**. As migrations padrão da web não representam o banco de negócio.
5. Nunca corra duas publicações ao mesmo tempo. O roteiro usa um lock.
6. O roteiro exige Redis como fila e manutenção do Laravel por arquivo. Outra configuração interrompe a conferência antes de publicar.

## Pré-requisitos

- Aprovação expressa do responsável para o merge, para o deploy e para as migrations do commit.
- CI aprovado no SHA exato. Os SHAs têm 40 caracteres em minúsculas.
- `ALVO` é o SHA aprovado. `ATUAL` é o SHA publicado hoje no servidor.
- Backup recente das duas camadas, com restauração testada e chaves conferidas (ver [backup-e-restauracao.md](backup-e-restauracao.md)).
- Janela combinada. Evite 02:00 a 06:00: o backup diário e a cópia usam esse intervalo.
- Quando houver cifra ou conversão de campos, siga o procedimento de chaves de [11-seguranca-e-privacidade.md](../arquitetura/11-seguranca-e-privacidade.md).

## Passos: deploy rotineiro (B5)

1. Entre no contêiner: `pct exec 111 -- bash` pelo Proxmox.
2. Na primeira instalação, antes de o roteiro existir na produção, transfira a versão revisada para `/tmp/g360-deploy.sh` e execute desse caminho. Confira o SHA-256 do arquivo contra o commit aprovado. Depois do primeiro deploy, use `scripts/operacao/deploy.sh`.
3. Execute a conferência prévia:

   ```bash
   bash scripts/operacao/deploy.sh --commit "$ALVO" --from "$ATUAL" --check
   ```

   O modo `--check` busca as referências do Git e confere SHAs, árvore rastreada limpa, branch `main`, alvo contido na `main`, serviços e configuração da fila. Não troca código, não coloca em manutenção, não instala dependências e não migra. Cria apenas o lock e arquivos temporários.
4. Resolva qualquer falha da conferência antes de seguir.
5. Execute a publicação:

   ```bash
   bash scripts/operacao/deploy.sh --commit "$ALVO" --from "$ATUAL" [--fase IDENTIFICADOR] [--wait SEGUNDOS]
   ```

   O roteiro executa, nesta ordem:
   1. Obtém o lock e executa uma cópia temporária de si mesmo. Assim o `git` pode substituir o arquivo original sem afetar a execução. Aplica `umask 022`.
   2. Coloca a web em manutenção e suspende o timer do agendador.
   3. Aguarda as filas `alta`, `padrao` e `baixa` esvaziarem. A contagem inclui jobs reservados e atrasados. A API continua disponível, porque os workers pausam na manutenção. O padrão de espera é 300 segundos. `--wait` aceita de 0 a 3600. Com `--wait 0`, o roteiro cancela de imediato se a fila estiver ocupada.
   4. Coloca a API em manutenção e confere de novo a fila. Se entrou um job novo, cancela antes de trocar o código.
   5. Para os workers ociosos, confere o Git outra vez e atualiza por fast-forward para o SHA aprovado. Preserva arquivos não rastreados.
   6. Limpa caches como `www-data`. Executa `composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress --optimize-autoloader` nas duas aplicações, como `lucasdev`. Executa `package:discover` como `www-data`. Isso mantém os donos reais: código e `vendor` de `lucasdev`; caches e `storage` de `www-data`.
   7. Executa `php artisan migrate --force --no-interaction` somente na API.
   8. Recria os caches de configuração, rotas e views nas duas aplicações, recarrega o PHP-FPM e inicia os workers.
   9. Grava o marcador `release.json` (SHA, fase e horário UTC) em cada aplicação.
   10. Retira a manutenção. Exige HTTP 200 em início, login e `/api/v1/health`, tanto no nginx local quanto no domínio público HTTPS. Reativa o timer.
   11. Atualiza a home pública independente, quando a instalação existe (`/root/g360-operacao/publicar-home.sh`).

6. Confira cada serviço individualmente: `nginx`, `php8.3-fpm`, `redis-server`, `gabarito360-worker@1`, `gabarito360-worker@2` e `gabarito360-agendador.timer`.

## Passos: publicação por fase (cadência atual)

A cadência atual é publicar versões por fase ou microfase, com aceite visual depois. O servidor tem auxiliares privados em `/root/g360-operacao` (modo 0700) e pacotes em `/root/g360-releases`.

1. Revise o escopo, as migrations e as flags. Implemente, teste e integre o incremento.
2. Confira os cinco jobs do CI e o SHA exato da `main`.
3. No CT 111, prepare o pacote:

   ```bash
   /root/g360-operacao/preparar-release.sh SHA FASE
   ```

   O pacote contém o código (`git archive`) com hash, o SHA publicado, o diff operacional preservado, o diagnóstico de migrations, flags e filas e a lista de serviços. O comando não troca código nem migra. Se falhar, preserve o pacote incompleto e prepare uma nova tentativa. Não marque como pronto à mão.
4. Publique:

   ```bash
   /root/g360-operacao/publicar-fase.sh SHA ATUAL FASE
   ```

   O comando exige o pacote preparado para o mesmo SHA anterior e a mesma fase. Ele chama o B5 com o backup final obrigatório. `FASE` é um identificador como `T5-E3` ou `V3-A1`.
5. Durante o checkpoint do B5, faça o backup imediato dos contêineres 106 e 111 e copie para a segunda camada (ver a seção abaixo). Não espere a agenda das 06:00.
6. Depois da abertura, acompanhe logs sanitizados, filas, jobs falhos e serviços por 15 minutos.
7. O responsável valida em `https://gabarito360.com.br/`. Registre o resultado e trate as correções como a próxima microfase.

O marcador `/root/g360-operacao/primeira-ativacao-pendente` bloqueia o atalho rotineiro até a primeira ativação acumulada terminar. Não o contorne.

## Passos: corte com manutenção prolongada e backup final

Use esta variante para releases que expandem o esquema, convertem dados ou exigem um ponto de recuperação coerente (por exemplo, T2 e a ativação de vínculos).

1. Prepare três arquivos, após conferir seus hashes contra o commit aprovado:
   - B5 em `/tmp/g360-deploy.sh`;
   - o hook `pausa-backup-final.sh` em `/root/pausa-backup-final.sh` (root, modo 0700);
   - o confirmador `confirmar-backup-final.py` no host Proxmox.
   Python 3 deve existir no CT e no host. O host precisa de `zstd`.
2. Faça o preflight:

   ```bash
   bash /tmp/g360-deploy.sh --commit "$ALVO" --from "$ATUAL" --check --manter-manutencao --backup-final /root/pausa-backup-final.sh
   ```

3. Inicie o B5 em um terminal persistente no CT 111:

   ```bash
   bash /tmp/g360-deploy.sh --commit "$ALVO" --from "$ATUAL" --wait 300 --manter-manutencao --backup-final /root/pausa-backup-final.sh
   ```

   Depois do preflight e da drenagem, o B5 mantém API e web em manutenção e para os dois workers, o timer, o serviço do agendador e o PHP-FPM. Ele confere que a fila está vazia e chama o hook **antes do fast-forward, do Composer e do primeiro DDL**. O lock fica ativo durante a espera.
4. Aguarde a mensagem `CHECKPOINT AGUARDANDO: /root/g360-backup-final-...`. Confira que os serviços estão parados e que nenhum outro escritor está ativo (importadores e comandos de linha). O nginx pode responder 502 durante o checkpoint, porque o PHP-FPM está parado.
5. No host, respeite o lock `/run/lock/proxmox-backup-sata.lock`. Confira que o dispositivo SATA correto está montado para escrita, com espaço livre e sem conflito com o backup diário. Gere o backup só dos contêineres 106 e 111, sem retenção e sem e-mail:

   ```bash
   umask 022
   vzdump 106 111 --storage backup-sata --tmpdir /var/tmp --mode snapshot \
     --compress zstd --remove 0 --prune-backups keep-all=1 \
     --notification-mode legacy-sendmail
   ```

   Não configure destinatário de e-mail. Use `--tmpdir /var/tmp`: o `tar` sem privilégio do `vzdump` não lê metadados sob `/root`. Não chame a rotina completa, que aplica retenção.
6. Registre os nomes exatos dos arquivos. Copie para o computador do responsável com SHA-256 (ver [backup-e-restauracao.md](backup-e-restauracao.md)). Não sobrescreva o marcador do ciclo diário completo com uma cópia pontual.
7. No host, confirme o backup com o auxiliar:

   ```bash
   python3 /root/confirmar-backup-final.py --ct-banco 106 --ct-aplicacao 111 \
     --checkpoint "$CHECKPOINT" --banco "$BACKUP_BANCO" --aplicacao "$BACKUP_APP"
   ```

   O confirmador verifica nomes e contêineres, a data do snapshot (posterior ao checkpoint), `zstd -t`, SHA-256 integral, tamanho estável, prazo vigente, o SHA antigo e os serviços ainda parados. Só então grava o recibo `confirmado.json` dentro do CT, com um ID desta execução, os SHAs, a confirmação explícita e dois arquivos (banco e aplicação) com tamanho e SHA-256. O recibo deve pertencer a `root`, sem acesso de grupo ou outros.
8. O hook espera até 1.800 segundos pelo recibo. `G360_BACKUP_TIMEOUT` aceita de 1 a 3.600. Dimensione o valor para cobrir geração, verificação e cópia.
9. Com o recibo válido, o B5 atualiza o código, aplica a expansão do esquema e refaz os caches. Reinicia só o PHP-FPM e termina com `PREPARADO EM MANUTENÇÃO`. Não há teste HTTP nem retomada automática.
10. Execute os comandos de preenchimento, conciliação e restrição da release (por exemplo, os comandos `g360:` de espaços e vínculos), como `www-data`, na pasta `apps/api`. Confira o código de saída de cada comando antes do próximo. Uma divergência interrompe o corte.
11. Antes de abrir, exija auditoria e verificação do esquema sem inconsistência (por exemplo, `g360:verificar-corte-espacos`).
12. Após a decisão explícita de abertura:

    ```bash
    systemctl reload php8.3-fpm
    systemctl start gabarito360-worker@1 gabarito360-worker@2
    for unidade in nginx php8.3-fpm redis-server gabarito360-worker@1 gabarito360-worker@2; do
      systemctl is-active --quiet "$unidade" || exit 1
    done
    (cd /home/lucasdev/projetos/gabarito360/apps/api && runuser -u www-data -- php artisan up)
    (cd /home/lucasdev/projetos/gabarito360/apps/web && runuser -u www-data -- php artisan up)
    ```

13. Confira HTTP 200 em `/`, `/login` e `/api/v1/health`, tanto no nginx local quanto no HTTPS público. Faça login e uma leitura autorizada. Só então inicie `gabarito360-agendador.timer`. Acompanhe as filas e os erros.
14. Não execute `queue:retry all`. Jobs falhos antigos precisam de revisão de contexto e compatibilidade.
15. Guarde o registro da janela em área operacional restrita: operador, autorização, SHAs antes e depois, versão do banco, inventário, identificação do backup e do teste de restauração, horários, contagens, código de saída por passo e a decisão de abrir. Não anexe `.env`, chaves, dumps, tokens, fotos ou payloads ao Git.

A pausa do backup faz parte da indisponibilidade. Em ensaio, a geração e a conferência levaram 14 min 37 s e a pausa total 16 min 21 s para clones. Some o tempo da cópia para a segunda camada. Reserve inicialmente 45 minutos fora das tarefas de backup, e reavalie com o tempo medido.

## Passos: home pública independente da manutenção

1. Instale `publicar-home.sh`, `exportar-home.php`, `manutencao.html` e `preparar-nginx-publico.py` juntos em `/root/g360-operacao`.
2. Gere a configuração candidata do nginx a partir da configuração **real**:

   ```bash
   python3 preparar-nginx-publico.py ORIGEM DESTINO
   ```

   Confira o diff, preserve uma cópia, instale `deploy/nginx/publico-independente.conf` como `/etc/nginx/snippets/gabarito360-publico.conf`, valide com `nginx -t` e recarregue. O gerador preserva os logs protegidos e o socket da API. Não substitua o arquivo real pelo modelo genérico do repositório.
3. Execute `publicar-home.sh` enquanto a aplicação estiver disponível. Ele obtém os planos válidos da API, recusa formulários e tokens na home e não troca a versão anterior por preços de reserva se a API falhar.
4. O B5 atualiza o conteúdo público depois de reabrir API e web. No corte com `--manter-manutencao`, execute o publicador depois da abertura.
5. Para mudanças comerciais fora do deploy (por exemplo, links de redes sociais ou preços), regenere a home com o mesmo comando.
6. Confira todos os links da home (contato, login, planos, recuperação, redes sociais). Os links da própria aplicação ficam relativos à origem do visitante.

Redes sociais publicadas no rodapé: Instagram, Facebook e TikTok, todos com o identificador `@gabarito360`, em nova aba. O YouTube segue em preparação.

## Verificação

1. Início, login e `/api/v1/health` retornam HTTP 200 localmente e pelo domínio público.
2. Todos os serviços listados estão ativos.
3. As filas `alta`, `padrao` e `baixa` estão vazias e `failed_jobs` não aumentou.
4. O marcador de fase e SHA aparece na tela autenticada.
5. Os fluxos alterados foram conferidos. A leitura real de um cartão exige validação funcional específica: o roteiro não a executa.
6. Confira os testes do roteiro, quando mudar o `deploy.sh`:

   ```bash
   bash -n scripts/operacao/deploy.sh
   python3 scripts/tests/test_deploy.py
   ```

## Reversão

1. Se a falha ocorreu **antes de mudar o código**, o roteiro retira só a manutenção que ele iniciou e restaura os serviços que parou. Confira o resultado e o SHA real antes de tentar de novo.
2. Se a falha ocorreu **a partir da atualização do código**, o roteiro mantém API e web em manutenção, imprime a etapa e preserva o banco. Os serviços ficam no estado da última etapa. Confira workers e agendador antes de recuperar.
3. Se o HTTP retornou algo diferente de 200, não considere a publicação concluída.
4. Não há reset forçado, exclusão de arquivos não rastreados nem rollback automático de migrations. Não apague tabelas para voltar uma versão.
5. Depois de um DDL, não restaure só o Git. A recuperação exige o conjunto coerente de banco, arquivos e chaves, e a avaliação das escritas posteriores ao ponto do backup. Restaure primeiro em rede isolada, pare os escritores e confira o SHA e a integridade antes de reabrir.
6. Ao restaurar um contêiner do checkpoint, os arquivos de manutenção estarão presentes, mas unidades habilitadas podem iniciar no boot. Pare e verifique os escritores antes de conectar à rede real. O processo em espera não é restaurado pelo backup: não retome o deploy antigo com o recibo salvo.
7. Se a pausa expirar ou o backup falhar, confira os logs e o recibo. Não reenvie uma confirmação tardia. Sem mudança de SHA ou de esquema, reabra a versão anterior e inicie FPM, workers e timer. Para nova tentativa, comece um novo preflight.
8. Para falha de DDL no corte, mantenha API e web em manutenção e todos os escritores parados. Registre o último passo sem SQL nem dados pessoais. Repita o mesmo plano depois de corrigir a causa. Não desative chaves estrangeiras.
9. Em falha na abertura, recoloque API e web em manutenção e pare workers, timer e agendador. Registre se houve escritas novas.
10. Para um ajuste operacional pontual (por exemplo, o hotfix de `optimizer_search_depth`), reverta só o arquivo alterado e refaça o cache da API. Nenhum rollback de banco é necessário.

## Falhas da fila

```bash
php artisan queue:failed
php artisan queue:retry all
```

Use `queue:retry all` só fora do corte de esquema e depois de revisar os jobs falhos.

## Referências

- [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md)
- [backup-e-restauracao.md](backup-e-restauracao.md)
- [publicacao-homologacao.md](publicacao-homologacao.md)
- [incidentes.md](incidentes.md)
- [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md)

## Histórico de publicações em produção

Estas entradas resumem registros de execução. As evidências sanitizadas em formato JSON estão em evidencias (`docs/operacao/evidencias/`, removido em 10/11/2026; disponível no histórico do Git).

| Data | Versão (SHA curto) | Conteúdo | Resultado e observações |
|---|---|---|---|
| 29 e 30/10/2026 | (manual) | Primeiras publicações, Redis para cache, sessão e fila (B3), workers e agendador (B4) | Tarefas rodavam manualmente no caminho real do CT 111. Mudar a sessão encerrou os logins ativos. A web e a API passaram a usar grupos PHP-FPM separados em 30/10 |
| 31/10/2026 | `305c3e4` → `b020570` (PR 61) | B6: leitura de cartão pela fila `alta` | Migration `create_cartao_envios` (lote 17), com espaço livre de 25 GB. `retry_after` do Redis passou de 90 para 240 s, acima do timeout de 210 s do job. Início, login e health retornaram 200. Um job técnico foi consumido em 25,55 ms. `/termos` e `/privacidade` retornaram 404, como previsto. Sem teste autenticado com foto real nem observação prolongada. O teste de 50 cartões foi de ambiente de teste, não medição de capacidade |
| 31/10/2026 | `e0ccc14` → `07340a8` (PR 66), duas vezes | B5: aceite do roteiro único | Duas execuções consecutivas, sem etapas manuais (14:13:58 a 14:14:14 UTC e 14:15:14 a 14:15:26 UTC). Sem migrations novas. Fila zero e nenhum job falho. Os deploys manuais anteriores não contam. O teste não exercitou falha real nem leitura autenticada |
| 31/10/2026 | `90b7d3e` (PR 69) | L3: cifra de campos, índices HMAC, logs protegidos | Publicado às 17:08:56 UTC. Backup recente, restauração isolada com 40 tabelas. Login, conta, recuperação e troca de e-mail conferidos com conta fictícia em transação revertida. Nginx com logs sem URI livre. Sem envio real de e-mail |
| 31/10/2026 | `dc9949e` (PR 70) | L6: avatares de alunos | Migration aditiva de `avatar_id` em 26,88 ms. Roteiro B5 às 18:11:55 BRT. Kernel da API: cadastro com avatar 36, trocas entre 1, 9 e 36, retorno às iniciais, rejeição do ID 37 e de `foto_path`. Nenhuma foto apagada |
| 01/11/2026 | `dc9949e` → `af4c6d1` (PR 85) | T2: clientes e espaços | Manutenção de ~08h20 a 08h50 BRT. Backup final em duas camadas. 2 clientes internos e 13 espaços. 14 turmas, 414 alunos, 47 cartões, 47 notas e 2.115 respostas preservados. Três intercorrências: `umask 077` no chamador (64 arquivos sem leitura), um auxiliar de verificação chamado `http.py` que colidiu com a biblioteca padrão e o erro 1010 da Cloudflare |
| 01/11/2026, 17:05 BRT | `af4c6d1` + ajuste pontual | Hotfix PR 98: `optimizer_search_depth=4` | Só o arquivo `apps/api/config/database.php` foi alterado no CT 111, com backup do original. Sem migration. Detalhes em [incidentes.md](incidentes.md) |
| 02/11/2026 | `af4c6d1` → `a90fbf5` (PR 133) | Publicação inicial acumulada V1 a T5 | 21 migrations. Backups finais restaurados em clones isolados antes da migration (cerca de 510 s para recuperar serviços). Seis contas convertidas em sete vínculos, sem ampliar permissões. Auditoria ativada com credencial própria. Flags de vínculos, acessos internos e cópias habilitadas. Cookie de sessão renomeado, exigindo novo login. Os 4 gabaritos, 180 questões, 47 cartões, 2.115 respostas, 47 notas, 414 alunos e 14 turmas foram preservados. O ajuste PR 98 foi reconciliado antes do fast-forward |
| 02/11/2026 | (operacional) | Contas internas | Contas de Desenvolvedor, Suporte e Financeiro criadas com identificadores fictícios de teste, após autorização expressa. Apenas o Desenvolvedor gere acessos internos. As três recebem 403 em provas sem vínculo escolar |
| 02/11/2026 | (operacional) | Home independente e redes sociais | Home servida pelo nginx sem PHP durante a manutenção. Links do Instagram, Facebook e TikTok publicados |

As etapas F2 a F4 de desempenho foram publicadas só na homologação. O último registro desta lista é a ativação de 02/11/2026 (`a90fbf5`, com ajustes operacionais). Um registro posterior (04/11/2026, hotfix da revisão de leituras, produção em `83dd5ae`) está em [FN-046](../funcionalidades/046-revisao-leituras/planejamento.md). Confira sempre o SHA real no servidor antes de qualquer nova publicação.

### Cronograma de aceite de B5

B5 exigiu dois deploys consecutivos aprovados, sem etapas manuais, e o aceite foi atingido em 31/10/2026. O modo `--check`, os testes simulados e os deploys manuais anteriores não contaram para o aceite.

### Conciliação do planejamento em 31/10/2026

Em 31/10/2026, uma conciliação documental conferiu os PRs e o commit publicado (`305c3e4`, PR 60). Registros:

- Cópia diária completa comprovada (`ULTIMA-COPIA-OK` às 08:50, 8 datas, 15 arquivos por data).
- E5 (convites) e E6 (troca de e-mail): código publicado, sem teste real ainda.
- E7 (Turnstile): código publicado; merge não prova ativação nem envio real.
- B6: CI aprovado, com 50 envios registrados no PR.
- O percentual de pacotes concluídos não mede esforço nem prazo. Um pacote só termina quando seu critério de aceite termina, não quando o PR é integrado.

### Correções da revisão de B6 (PR 61)

A revisão apontou três problemas, corrigidos antes do merge:

1. Preservar no acompanhamento da aba os envios com erro que ainda permitem nova tentativa, para que o recarregamento restaure o recibo e o botão sem nova foto.
2. Ao concluir uma foto nova de um aluno identificado, substituir também cartões anteriores pendentes por falha definitiva. Não descartar uma foto mais nova ainda na fila.
3. Na exclusão manual, invalidar o recibo e limpar o resultado na mesma transação, usando a mesma ordem de bloqueios do worker (prova, cartão, recibo). Os arquivos só saem após o commit. A operação é recusada se o QR mudar o cartão de prova durante a requisição.

Resultados locais: API 91 testes, 471 asserções; web 22 testes, 93 asserções; 4 testes de JavaScript; verificador estático de telas: 24 scripts, zero erros.

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/operacao/deploy.md` | Fala em "produção" e a documentação de 01/11 em "homologação representativa" para o mesmo CT 111 | O CT 111 é a produção; a homologação é a VM 120 |
| `docs/operacao/deploy.md` | Diz que `--manter-manutencao` não chama comandos T2 | Continua verdadeiro. O B5 não chama comandos de preenchimento nem de restrição |
| `docs/99` | Cita 21 migrations "pendentes" e flags específicas como plano | Esses itens foram executados em 02/11/2026 (`a90fbf5`). A tabela de histórico acima registra o resultado |
| `docs/37` | Diz que o B5 só termina com dois deploys; o aceite consta como "planejado" em partes | O aceite foi concluído em 31/10/2026 |
