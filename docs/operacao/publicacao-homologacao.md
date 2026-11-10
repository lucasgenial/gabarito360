# Publicação na homologação

**Atualizado em:** 10/11/2026, por lucasgenial
**Ambiente:** homologação (VM 120)
**Quando usar:** publicar uma versão aprovada no CI em `homologa.gabarito360.com.br`, para conferência visual e funcional pelo responsável.
**Duração estimada:** 10 a 20 minutos, mais o tempo do backup da VM quando o roteiro o exige.

Este documento descreve como publicar na homologação isolada (ADR-H1, 03/11/2026). A topologia está em [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md). A publicação na produção está em [publicacao-producao.md](publicacao-producao.md). Funcionalidade: [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md).

> **Atenção:** este roteiro só altera a VM 120. Ele nunca recebe endereço nem credencial da produção. A promoção à produção é decisão humana expressa do responsável. Nenhum resultado de teste dispensa essa decisão.

## Regras do fluxo

1. Toda entrega concluída e testada vai primeiro à homologação.
2. A tela autenticada mostra a fase e o SHA curto (marcador de versão). O marcador identifica o código e não substitui o aceite funcional.
3. O especialista em design revisa as telas. O responsável valida o uso e decide a promoção.
4. A conferência visual acontece depois da publicação. Ela não condiciona o merge. Ela não dispensa o CI.
5. Um PR integrado não é publicado automaticamente. Cada publicação exige um comando do operador.
6. Publique por etapa completa, não por subdivisão técnica. Exceção: a regra "L4 só no fechamento" abaixo.

Regra específica de L4: não publique protocolos nem exportações dos titulares (L4-A a L4-C) isoladamente. Publique L4 somente ao fechar as partes A a E.

## Pré-requisitos

- O PR foi integrado à `main` e os cinco jobs do CI passaram no SHA exato (ver lista de jobs em [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md)).
- Você tem o SHA completo (40 caracteres) do commit.
- Você tem acesso root à VM 120 (`g360-homologa`).
- O script `infra/homologacao/deploy.py` da versão aprovada está na VM.
- A aplicação base já existe em `/srv/gabarito360`, com `shared/api` e `shared/web` (arquivos `.env` e `storage`).
- Você leu os limites de cada fase no plano mestre e o diff de migrations e flags.

## Passos

1. Gere o pacote do código no seu computador, a partir do commit aprovado.

   ```bash
   git archive --format=tar.gz -o release.tar.gz <SHA>
   sha256sum release.tar.gz
   ```

2. Copie o arquivo e o `deploy.py` para a VM. Confira o SHA-256 do arquivo na VM.
3. Se a release alterar a configuração do nginx ou do MariaDB da VM, aplique antes o arquivo versionado em `infra/homologacao/` com backup do arquivo anterior e `nginx -t`. O nginx usa o hash do arquivo de site como referência de conferência dos próximos operadores. Atualize essa referência.
4. Execute como root, somente na VM:

   ```bash
   python3 deploy.py <SHA40> /caminho/release.tar.gz <SHA256> [FASE]
   ```

   O parâmetro `FASE` identifica a publicação (por exemplo, `HOMOLOGACAO-PERF-F4B`). O padrão é `HOMOLOGACAO`.

5. Aguarde o fim da execução. O script faz, nesta ordem:
   1. Recusa rodar fora do hostname `g360-homologa` e valida o formato do SHA e do hash.
   2. Obtém uma trava exclusiva. Duas publicações não rodam juntas.
   3. Extrai o código para `/srv/gabarito360/releases/<SHA>` e normaliza as permissões (diretórios 755, arquivos 644 ou 755).
   4. Liga `storage` e `.env` aos diretórios compartilhados e instala as dependências com o Composer, sem pacotes de desenvolvimento.
   5. Para o worker `g360-homologa-worker.service` e o timer `g360-homologa-copias.timer`.
   6. Coloca a API e a web atuais em manutenção.
   7. Confere que o ambiente é `staging` e que o banco é o da homologação. Se não for, aborta.
   8. Salva o banco (`banco-antes.sql.gz`) e os arquivos (`storage/app`) no diretório de recibos `/root/g360-homologa/releases/<SHA>`.
   9. Captura os nomes das colunas e um hash do conteúdo de nove tabelas acadêmicas (`alunos`, `turmas`, `provas`, `prova_turmas`, `gabaritos`, `gabarito_questoes`, `cartoes`, `cartao_respostas`, `notas`).
   10. Executa `php artisan migrate --force` e `g360:permissoes-sincronizar`.
   11. Ajusta as flags de ambiente da homologação (catálogo, cópias, pedidos dos titulares, e-mail em `log`, Mercado Pago em sandbox).
   12. Reconstrói os caches de configuração e de views como `www-data`.
   13. Recoleta as colunas e o conteúdo. Se uma coluna sumiu, se apareceu uma coluna não prevista ou se o conteúdo mudou, o script aborta e **mantém a manutenção**.
   14. Troca o link `current` de forma atômica, recarrega o PHP-FPM e inicia o worker.
   15. Retira a manutenção da API e testa a ponte web-API (planos devem retornar 200 e um login inválido deve retornar 401). Retira a manutenção da web.
   16. Reinicia o timer das cópias e grava `recibo.json` com `producao_alterada: false`.

6. Se a janela afetou a home independente, a página pública continua no ar durante a manutenção (ver [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md)).

## Verificação

1. Abra `https://homologa.gabarito360.com.br/` com a Basic Auth do ambiente. Confirme HTTP 200 em `/up`, em `/login` e em `/api/v1/health`.
2. Confira o marcador de fase e SHA na tela autenticada.
3. Confira o cabeçalho `X-Robots-Tag: noindex, nofollow, noarchive`.
4. Confira que `/storage` retorna 404 e `/.env` retorna 403.
5. Confira no `recibo.json` os campos `academico_identico: true` e `producao_alterada: false`.
6. Teste o caminho real da web à API: o POST de login pelo formulário deve chegar a `/api/v1/auth/login` e retornar 401 para uma identidade inexistente. Não use apenas chamadas diretas à API. A base da API na web é `/api` e o controlador acrescenta `/v1`.
7. Confira worker e timer das cópias ativos. O timer da agenda geral deve continuar inativo.
8. Faça a conferência visual no Chrome (tema claro e escuro, desktop e celular). Registre o resultado por texto. Não guarde prints.

## Reversão

1. Não restaure só o Git depois de uma migration. Não apague tabelas.
2. Se o script falhou antes de abrir, a homologação continua em manutenção. Leia o log privado `erro-privado.log` no diretório de recibos da release. Corrija a causa e publique de novo.
3. Para voltar à versão anterior sem mudar o esquema: restaure o link `current` para a release anterior e restaure o `.env` pelos arquivos `api.env-anterior` e `web.env-anterior` do recibo. Reconstrua os caches como `www-data` e retome os serviços.
4. Para voltar também os dados: restaure `banco-antes.sql.gz` em um **banco alternativo** da VM e valide antes da troca. Nunca restaure por cima da produção.
5. Para voltar ao estado de dados fictícios anterior à cópia da produção: use o banco `gabarito360_homolog` e os diretórios com sufixo `ficticio-20261103`, conforme [copia-producao-homologacao.md](copia-producao-homologacao.md).

## Intercorrências conhecidas

| Sintoma | Causa | Solução |
|---|---|---|
| O Composer ou o PHP-FPM não lê os arquivos extraídos | O `umask` restritivo do chamador criou arquivos sem leitura | O script já normaliza as permissões antes do Composer. Rode com `umask 022` |
| A publicação parou na conferência do marcador anterior | O gerador de operador trocava também a fase anterior | Corrigido na F4-A. Preserve o operador falho fora do caminho e repita |
| A Cloudflare retornou erro 1010 a um script de verificação | Regra da borda contra a identificação padrão do Python | Use identificação de navegador no cliente. Não altere a regra |
| A sessão do Chrome do responsável caiu | A publicação reinicia o PHP-FPM e encerra as sessões | Faça novo login |
| O formulário de login mostra "E-mail ou senha incorretos" para todos | Base da API com `/api/v1` duplicou `/v1` (404) | Defina `API_BASE_URL=http://127.0.0.1:8081/api` e refaça o cache |

## Referências

- [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md)
- [copia-producao-homologacao.md](copia-producao-homologacao.md)
- [publicacao-producao.md](publicacao-producao.md)
- [FN-003 Publicação, deploy e homologação isolada](../funcionalidades/003-publicacao-e-homologacao/planejamento.md)

## Histórico de publicações na homologação

| Data | Release | Fase | Observação |
|---|---|---|---|
| 03/11/2026 | `68671c8` (PR 158) | CI1-E | Catálogo nacional carregado (214.192 observações de 2025 e 2.561 instituições de ensino superior de 2024, 27 UFs, 5.571 municípios); funcionalidades habilitadas; restauração completa em clone 9120 sem rede. Vinte requisições simultâneas de catálogo, em 20 UFs, completaram: média 1,7 s e máximo 3,2 s numa VM de 2 vCPU. O arquivo do backup da VM tem SHA-256 registrado no recibo privado |
| 09/11/2026 | `a7e08f5` | Desempenho F2 | Painéis; sem migration |
| 09/11/2026 | `9aee7fa` | HOMOLOGACAO-PERF-F3 | Cache de ativos; 130 tabelas e 201 arquivos idênticos; o `map` do nginx foi instalado |
| 09/11/2026 | `43ab393`, `9e4a1be`, `ed7dd0c` | HOMOLOGACAO-PERF-F4A, F4B, F4CD | Carregamento e interação; sem migration |

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/127` (ADR-H1) | Previa acesso protegido "a definir" e banco inicial só com dados fictícios | A proteção é Basic Auth mais túnel exclusivo. Os dados são a cópia pontual da produção de 03/11/2026 |
| `docs/129` | O exemplo do `deploy.py` omite o parâmetro `FASE` | O quarto argumento `FASE` existe no código e define o marcador |
| `docs/99` | O fluxo "preparar-release.sh / publicar-fase.sh" é da produção | A homologação usa `deploy.py` |
