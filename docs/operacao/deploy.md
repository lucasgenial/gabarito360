# Deploy e serviços

Produção: CT 111 do Proxmox, em `/home/lucasdev/projetos/gabarito360`. Acesso por `ssh pve` e `pct exec 111`.

## Serviços

| Serviço | Papel |
|---|---|
| `nginx` | Entrada web |
| `php8.3-fpm` | Grupos `www` (web) e `api`, com 12 processos cada |
| `redis-server` | Cache, sessão e fila |
| `gabarito360-worker@1` e `@2` | Fila da API: `alta`, `padrao` e `baixa` |
| `gabarito360-agendador.timer` | Roda `schedule:run` a cada minuto |

Arquivos em `infra/systemd`. Ao mudá-los:

```bash
cp infra/systemd/gabarito360-* /etc/systemd/system/
systemctl daemon-reload
```

## Redis

`/etc/redis/redis.conf`:

```text
maxmemory 256mb
maxmemory-policy volatile-lru
appendonly yes
```

`volatile-lru` só expulsa chaves com validade, preservando a fila.

Valores no `.env`:

| Variável | API | Web |
|---|---|---|
| `CACHE_STORE` | `redis` | `redis` |
| `SESSION_DRIVER` | `redis` | `redis` |
| `QUEUE_CONNECTION` | `redis` | `redis` |
| `REDIS_PREFIX` | `g360_api_` | `g360_web_` |

A mudança de sessão desconecta quem estiver logado.

## Deploy

O roteiro único do pacote B5 está em `scripts/operacao/deploy.sh`. Executar dentro do CT 111, como root, somente após aprovação específica de merge e deploy do commit. O operador confere o CI e a revisão antes de executar; o roteiro não consulta o GitHub para validar aprovações.

A antiga entrada `infra/deploy.sh` delega ao mesmo roteiro e exige os mesmos argumentos. Os antigos argumentos `api`, `web` e `all` deixam de ser aceitos, pois as duas aplicações precisam ser publicadas juntas.

### Conferência prévia

Defina `ALVO` como o SHA completo aprovado e `ATUAL` como o SHA atualmente publicado. Ambos devem ter 40 caracteres em minúsculas. Depois:

```bash
bash scripts/operacao/deploy.sh --commit "$ALVO" --from "$ATUAL" --check
```

O modo `--check` busca as referências Git e confere SHAs, árvore rastreada limpa, main, alvo contido na main, serviços e configuração da fila. Não troca código, não coloca aplicações em manutenção, não instala dependências nem executa migrações. Cria somente lock e arquivos temporários de execução. O diretório padrão é `/home/lucasdev/projetos/gabarito360`; `--root` permite uma cópia de teste explicitamente indicada.

Na primeira instalação, antes de o roteiro existir na produção, transfira a versão revisada do script para `/tmp/g360-deploy.sh` e execute desse caminho. Isso evita antecipar o checkout do código aprovado. Depois do primeiro deploy, use o caminho versionado acima.

### Publicação aprovada

```bash
bash scripts/operacao/deploy.sh --commit "$ALVO" --from "$ATUAL"
```

O roteiro:

1. Impede concorrência com lock e executa uma cópia temporária de si mesmo para sobreviver à atualização do próprio arquivo.
2. Coloca a web em manutenção e suspende o timer do agendador. Espera as filas alta, padrão e baixa esvaziarem; a contagem inclui jobs reservados e atrasados. A API permanece disponível durante a drenagem porque os workers comuns pausam na manutenção.
3. Ao esvaziar, coloca a API em manutenção e confere novamente a fila. Se entrou outro job, cancela antes de trocar código. O padrão é aguardar até 300 segundos; `--wait 0` cancela imediatamente se a fila estiver ocupada. Aceita de 0 a 3600 segundos.
4. Para workers ociosos, confere novamente o Git e atualiza por fast-forward para o SHA aprovado, preservando arquivos não rastreados.
5. Limpa caches de configuração/compilados como `www-data`; executa `composer install --no-dev --no-scripts --prefer-dist --no-interaction --no-progress --optimize-autoloader` nas duas aplicações, como `lucasdev`, usando cache temporário próprio. Depois executa `package:discover` como `www-data`. Essa divisão preserva os donos reais: código/vendor de `lucasdev`, caches/storage de `www-data`. Os hooks Laravel de limpeza/descoberta são executados explicitamente; não roda `setup`, não cria `.env` nem gera chave. Se o projeto acrescentar outros hooks de instalação, revisar o roteiro antes de publicar.
6. Executa `php artisan migrate --force --no-interaction` **somente na API**. As migrations padrão da web não representam o banco de negócio.
7. Recria caches de configuração, rotas e views nas duas aplicações, recarrega PHP-FPM e inicia os workers.
8. Retira manutenção, exige HTTP 200 no início, login e `/api/v1/health`, tanto via nginx local quanto pelo domínio público HTTPS, e reativa o timer.

Todos os serviços são conferidos individualmente. O roteiro não altera `.env`, DNS, nginx, PHP-FPM ou unidades systemd. Ele requer Redis como fila e manutenção Laravel por arquivo; outra configuração interrompe a conferência antes de publicar.

### Se falhar

- Antes de mudar o código: tenta retirar somente a manutenção iniciada por ele e restaurar os serviços que parou. Confira o resultado da recuperação e o SHA real antes de tentar novamente.
- A partir da atualização: coloca ou mantém API e web em manutenção, imprime a etapa da falha e preserva o banco. Os serviços podem estar no estado da última etapa; confira workers e agendador antes de recuperar.
- Não há reset forçado, exclusão de arquivos não rastreados ou rollback automático de migrações. Não apague tabelas para voltar uma versão. Uma recuperação em produção precisa de análise e aprovação do responsável.
- Em caso de HTTP diferente de 200, não considere a publicação concluída mesmo que parte das etapas tenha passado.

### Verificação e aceite

Após sucesso, conferir os fluxos alterados, as filas e jobs falhos. O roteiro testa também o domínio público, mas a leitura real de um cartão precisa de validação funcional específica.

Os testes automatizados do roteiro usam diretórios temporários e comandos simulados:

```bash
bash -n scripts/operacao/deploy.sh
python3 scripts/tests/test_deploy.py
```

B5 só termina depois de **dois deploys aprovados consecutivos**, executados pelo roteiro sem etapas manuais adicionais. Registrar SHAs, resultados e limitações. O modo `--check`, os testes simulados e os deploys manuais anteriores não contam como esse aceite.

## Falhas da fila

```bash
php artisan queue:failed
php artisan queue:retry all
```
