# Infraestrutura e ambientes

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve onde o Gabarito360 roda, como os serviços se organizam e quais arquivos de configuração pertencem ao repositório. Os procedimentos passo a passo estão em [docs/operacao](../operacao/README.md).

## Resumo

O Gabarito360 tem dois ambientes. A produção usa dois contêineres no Proxmox. A homologação usa uma máquina virtual isolada, no mesmo Proxmox. Nenhuma publicação chega à produção sem decisão expressa do responsável (`lucasgenial`).

| Item | Produção | Homologação |
|---|---|---|
| Endereço público | `gabarito360.com.br` | `homologa.gabarito360.com.br` |
| Recurso no Proxmox | Contêiner LXC (Linux Containers) 111, aplicação, 10.0.0.11 | Máquina virtual QEMU 120, 10.0.0.120, ponte interna `vmbr1` |
| Banco de dados | MariaDB 10.11 no contêiner 106, 10.0.0.6 | MariaDB 10.11 local na própria VM, só em loopback |
| Código | `/home/lucasdev/projetos/gabarito360` | `/srv/gabarito360/releases/<SHA>`, com o link `current` |
| Publicação | `scripts/operacao/deploy.sh` (roteiro B5) | `infra/homologacao/deploy.py` |
| Acesso público | Túnel Cloudflare, sem autenticação extra | Túnel Cloudflare próprio, com Basic Auth e `noindex` |
| Dados | Reais | Cópia pontual dos dados reais (03/11/2026), mais testes |
| E-mail | Resend (envio real) | Registro em log, sem envio |
| Pagamentos | Mercado Pago sem cobrança ativa | Sandbox, sem credenciais |

O endereço 10.0.0.10 é outra aplicação (contêiner 110). Não use esse endereço para o Gabarito360.

## Topologia

1. O visitante acessa o domínio pela Cloudflare (DNS, proteção e túnel).
2. O túnel entrega a requisição ao nginx do servidor de aplicação.
3. O nginx encaminha `/api/v1/` ao PHP-FPM da API e o restante ao PHP-FPM da web.
4. A web chama a API por HTTP local. A API acessa MariaDB, Redis e o disco.
5. Os workers da fila e o agendador rodam como serviços systemd no mesmo servidor.

A API devolve somente JSON. A web renderiza as telas. A divisão das responsabilidades está em [01-contexto-e-containers.md](01-contexto-e-containers.md).

## Proxmox e contêineres

| Recurso | Papel | Observação |
|---|---|---|
| CT 111 (`srv-gabarito360`) | Aplicação: nginx, PHP 8.3, Redis, workers, agendador | Dono do código: `lucasdev`. Dono de `storage` e `bootstrap/cache`: `www-data` |
| CT 106 | MariaDB de produção, banco `gabarito-360` | A aplicação acessa pela rede interna |
| VM 120 (`g360-homologa`) | Homologação completa e isolada | 2 vCPU, 4 GiB de RAM, 40 GiB de disco; Ubuntu 24.04 |

O acesso administrativo ao Proxmox usa SSH. Para entrar no contêiner, use `pct exec 111`. Documentos antigos que citam `/var/www/gabarito360` estão errados para este servidor.

## Servidor de aplicação (CT 111)

### nginx

O nginx do servidor real fica fora do repositório. O modelo `deploy/nginx/gabarito360.conf` mostra a estrutura:

- A raiz pública é `apps/web/public`.
- A rota `/api/v1` vai ao `index.php` da API por `fastcgi_pass`.
- As demais rotas vão ao `index.php` da web.
- Toda URL terminada em `.php` retorna 404.
- O limite de envio é 20 MB (`client_max_body_size`).

Os arquivos `infra/nginx.conf` e `infra/deploy.sh` são legados. O `infra/deploy.sh` só delega ao roteiro oficial.

### PHP-FPM em dois grupos de processos

Desde 30/10/2026, web e API usam grupos de processos separados:

| Grupo | Aplicação | Processos | Socket |
|---|---|---|---|
| `www` | Web | 12 | `php8.3-fpm.sock` |
| `api` | API | 12 | `php8.3-fpm-api.sock` |

**Motivo da decisão:** a web chama a API por localhost. Com um só grupo, 47 fotos pedidas ao mesmo tempo esgotaram os processos e pararam o site. A configuração (`/etc/php/8.3/fpm/pool.d/api.conf` e o site do nginx) fica fora do repositório. Preserve essa separação ao mexer em nginx ou em deploy. A separação física futura da API pertence a [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md).

Na homologação, a API tem 2 processos FPM e a web tem 3. Por isso uma busca nacional do catálogo ocupa metade da capacidade da API durante alguns segundos (ver [12-desempenho.md](12-desempenho.md)).

### Redis

O Redis guarda cache, sessões e fila. A configuração do servidor (`/etc/redis/redis.conf`) é:

| Parâmetro | Valor | Motivo |
|---|---|---|
| `maxmemory` | 256 MB | Limita o uso de memória |
| `maxmemory-policy` | `volatile-lru` | Expulsa só chaves com validade e preserva a fila |
| `appendonly` | `yes` | Persiste a fila após reinício |

Variáveis do `.env`. API e web usam o mesmo driver e prefixos diferentes:

| Variável | API | Web |
|---|---|---|
| `CACHE_STORE` | `redis` | `redis` |
| `SESSION_DRIVER` | `redis` | `redis` |
| `QUEUE_CONNECTION` | `redis` | `redis` |
| `REDIS_PREFIX` | `g360_api_` | `g360_web_` |

Trocar o driver de sessão encerra as sessões ativas. A sessão web dura 120 minutos de inatividade (`SESSION_LIFETIME`).

### Filas e workers

A API processa trabalhos em segundo plano pela fila Redis. O arquivo `infra/systemd/gabarito360-worker@.service` define o serviço:

| Item | Valor |
|---|---|
| Instâncias | `gabarito360-worker@1` e `@2` |
| Filas, em ordem de prioridade | `alta`, `padrao`, `baixa` |
| Comando | `php artisan queue:work redis` com `--sleep=3 --tries=3 --timeout=120 --max-time=3600 --memory=256` |
| Reinício | Sempre, após 5 segundos |
| Usuário | `www-data` |

Os trabalhos se distribuem assim:

| Fila | Trabalhos |
|---|---|
| `alta` | Leitura de cartões (OMR, Reconhecimento Óptico de Marcas) |
| `padrao` | E-mails transacionais |
| `baixa` | Gravação da auditoria |

O tempo de reserva do job (`retry_after` do Redis) é o maior valor entre `3 × OMR_TIMEOUT + 60` e 240 segundos (`apps/api/config/queue.php`). Com `OMR_TIMEOUT=60`, o resultado é 240 segundos. Esse valor precisa superar o tempo máximo do job de cartão.

Jobs com falha ficam em `failed_jobs` (provedor `database-uuids`). Consulte-os com `php artisan queue:failed`.

### Agendador e timers

O serviço `gabarito360-agendador.service` executa `php artisan schedule:run`. O timer `gabarito360-agendador.timer` o dispara a cada minuto. A agenda da aplicação (`apps/api/routes/console.php`) contém:

| Tarefa | Frequência | Condição |
|---|---|---|
| `VerificarInadimplenciaJob` | Diária, 03:00 | Sempre |
| `g360:copias-auditoria` | A cada minuto | Flag `COPIAS_AUDITORIA_ENTREGA` ativa |
| `g360:copias-executar` | A cada minuto | Flags `COPIAS_EXECUCAO_ATIVA` e `COPIAS_ATIVAS` ativas |

Para alterar uma unidade systemd, copie os arquivos de `infra/systemd` para `/etc/systemd/system/` e execute `systemctl daemon-reload`.

O roteiro de deploy confere estes serviços: `nginx`, `php8.3-fpm`, `redis-server`, `gabarito360-worker@1`, `gabarito360-worker@2` e `gabarito360-agendador.timer`.

## Banco de dados

O MariaDB é o único banco aprovado. A produção usa a instância do contêiner 106. A homologação usa a instância local da VM 120.

### Parâmetro do otimizador

Toda conexão MariaDB da API executa `SET SESSION optimizer_search_depth=4` ao abrir (`apps/api/config/database.php`). Sem isso, o planejamento das consultas com várias subconsultas de acesso passou de 15 segundos e derrubou o painel do professor. O incidente de 01/11/2026 está em [incidentes.md](../operacao/incidentes.md). O valor global do servidor (62) não muda.

### Buffer pool na homologação

O arquivo `infra/homologacao/mariadb/90-g360-perf-f6.cnf` define `innodb_buffer_pool_size = 1G` na VM 120. O padrão era 128 MB. A produção não tem esse ajuste. No MariaDB 10.11.14, mudar o tamanho exige reiniciar o serviço. Medições em [12-desempenho.md](12-desempenho.md).

## Homologação isolada (VM 120)

**Decisão (ADR-H1, 03/11/2026):** o responsável não conseguia conferir o desenvolvimento enquanto a produção ficava na versão anterior. Por isso a homologação virou um ambiente permanente e isolado. Toda entrega concluída e testada vai primeiro à homologação. A promoção à produção exige decisão humana expressa. Não existe publicação automática. Registro da decisão em [decisões](../decisoes/README.md).

Regras de isolamento:

1. A VM tem MariaDB, Redis, arquivos, usuários técnicos, chaves e backups próprios.
2. O MariaDB e o nginx escutam só em loopback.
3. A VM bloqueia conexões aos IPs operacionais 10.0.0.6 e 10.0.0.11.
4. O acesso público passa por um túnel Cloudflare exclusivo, com Basic Auth, `X-Robots-Tag: noindex, nofollow, noarchive` e cookies exclusivos.
5. O e-mail fica em `log`. O Mercado Pago fica em sandbox, sem credenciais.
6. O timer da agenda geral (cobranças) fica desativado. O timer `g360-homologa-copias.timer` executa só os publicadores das cópias entre espaços.
7. A homologação não envia dados de volta à produção. Não há sincronização automática.

### Alternativas consideradas (ADR-H1)

| Alternativa | Decisão | Motivo |
|---|---|---|
| Outro diretório no servidor atual | Rejeitada | Compartilha o risco operacional |
| Novo contêiner LXC | Rejeitada | Não atende o pedido de separação por VM |
| VM QEMU própria | Escolhida | Isola e permite ensaiar recuperação |

O tamanho da VM será revisto pelo uso real. Não reduza a produção para abrir espaço.

### Dados da homologação

Em 03/11/2026, o responsável pediu que a homologação mantivesse os mesmos usuários e dados da produção. A cópia pontual está descrita em [copia-producao-homologacao.md](../operacao/copia-producao-homologacao.md). O banco inicial com dados fictícios foi preservado, para retorno.

### Cache de ativos no nginx da VM

A VM aplica a política de cache de ativos da etapa F3. O `map` fica em `/etc/nginx/conf.d/g360-ativos-map.conf`. O snippet fica em `/etc/nginx/snippets/g360-ativos-cache.conf`. Os modelos estão em `infra/homologacao/nginx/`. A política completa está em [12-desempenho.md](12-desempenho.md).

## Cloudflare e DNS

| Função | Uso |
|---|---|
| DNS | Zona `gabarito360.com.br` |
| Túnel | Entrega o tráfego ao nginx da produção e da homologação |
| Email Routing | Recebe e encaminha `contatos@` e `suporte@` (ver [email.md](../operacao/email.md)) |
| Turnstile | Verificação anti-robô do Fale Conosco |
| Web Analytics | Medição de visitas, sem cookies |

Uma regra da Cloudflare recusa clientes HTTP com a identificação padrão do Python (erro 1010). Scripts de verificação usam identificação de navegador no cliente. Não altere a regra por esse motivo.

## Página inicial independente da manutenção

Durante a manutenção, a home pública continua no ar. O nginx serve `/` e o CSS direto de `/var/www/gabarito360-publico/current`, sem PHP, Redis ou banco. A página é a mesma home Blade, renderizada previamente de forma anônima.

| Comportamento | Detalhe |
|---|---|
| Login e demais rotas web em manutenção | HTTP 503 com página responsiva |
| API em manutenção | HTTP 503 em JSON |
| Cabeçalhos do 503 | `Retry-After: 60` e `Cache-Control: no-store` |
| Formulários | A home estática não os aceita |
| Troca de versão | Atômica, pelo link simbólico `current` |
| Falha na geração | Mantém a última versão válida |

Os arquivos ficam em `scripts/operacao/`: `publicar-home.sh`, `exportar-home.php`, `manutencao.html` e `preparar-nginx-publico.py`. O trecho de nginx está em `deploy/nginx/publico-independente.conf`. A instalação está em [publicacao-producao.md](../operacao/publicacao-producao.md). A exportação converte os links da própria aplicação em caminhos relativos. Assim a home não congela o `APP_URL` interno.

Limite: uma queda do próprio nginx, do servidor ou da rede ainda derruba o site.

## Backup

A produção tem duas camadas de backup. Os procedimentos estão em [backup-e-restauracao.md](../operacao/backup-e-restauracao.md).

| Camada | Local | Quando | Retenção |
|---|---|---|---|
| 1 | SSD SATA no Proxmox (`/mnt/pve/backup-sata/dump`) | 02:30, timer `proxmox-backup-sata` | Hoje e 7 dias anteriores |
| 2 | Computador do responsável (`D:\Backups-Proxmox\dump`) | 06:00, tarefa `Copia Backup Proxmox` | 8 datas mais recentes |

A homologação tem backup próprio da VM (`vzdump` local no Proxmox). Esse backup protege contra falha da VM. Ele não protege contra a perda do host.

## Integração contínua (CI)

O arquivo `.github/workflows/verificacao.yml` define a verificação do GitHub, executada a cada push:

| Job | Função |
|---|---|
| `testes` | Testes da API e da web, auditoria de dependências, renderização do percurso de interface e exclusão real de sessão entre processos |
| `telas` | Verificador estático de telas e testes de JavaScript |
| `integridade-mariadb` | Suíte contra MariaDB 10.11 descartável, com `/var/lib/mysql` em tmpfs de 1 GiB |
| `auditoria-recuperacao` | Ensaio de recuperação da auditoria com MariaDB e Redis descartáveis |

O job `integridade-mariadb` caiu de 29 min 44 s para 20 min 15 s com o tmpfs (ver [12-desempenho.md](12-desempenho.md)). O tmpfs vale só para o serviço de teste do CI. Nunca o use em banco operacional. O merge de um PR exige que todos os jobs passem. A CI também executa `scripts/verificar_imagens.py`, que recusa prints versionados.

## Arquivos de infraestrutura do repositório

| Caminho | Conteúdo |
|---|---|
| `infra/systemd/` | Worker, agendador e timer |
| `infra/homologacao/deploy.py` | Publicação na VM 120 |
| `infra/homologacao/mariadb/90-g360-perf-f6.cnf` | Buffer pool de 1 GB |
| `infra/homologacao/nginx/` | Mapa e snippet de cache de ativos |
| `deploy/nginx/gabarito360.conf` | Modelo de site nginx |
| `deploy/nginx/publico-independente.conf` | Trecho da home independente |
| `scripts/operacao/` | Deploy B5, backup, home pública, preparo de release |
| `scripts/ensaios/` | Ensaios isolados (T2 e serviços) |
| `scripts/auditoria/` | Ensaios de recuperação e verificação de telas |

Ficam fora do repositório: `.env`, sites do nginx, pools do PHP-FPM, `redis.conf` e os auxiliares instalados em `/root/g360-operacao` no servidor.

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/operacao/deploy.md` e `docs/52` | Chamam o CT 111 de "homologação representativa de produção" (01/11/2026) | Desde 03/11/2026 o CT 111 é a produção e a VM 120 é a homologação |
| `docs/operacao/deploy.md` | Cita `php8.3-fpm` com "12 processos cada" | O valor vem do registro de 30/10/2026. A configuração vive fora do repositório e não foi reconferida no servidor nesta migração |
| `docs/operacao/deploy.md` | Não lista as opções `--backup-final` e `--fase` do roteiro | O código atual de `scripts/operacao/deploy.sh` aceita `--commit`, `--from`, `--root`, `--wait`, `--backup-final`, `--fase`, `--check` e `--manter-manutencao` |
