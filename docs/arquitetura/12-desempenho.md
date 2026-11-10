# Desempenho

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve os fundamentos de desempenho do Gabarito360, as decisões da ADR-195, os resultados das etapas F0 a F6 na homologação e o padrão de medição com 1000 usuários simultâneos. A funcionalidade correspondente é a [FN-077](../funcionalidades/077-desempenho/planejamento.md). A infraestrutura está em [13-infraestrutura-e-ambientes.md](13-infraestrutura-e-ambientes.md).

## Resumo

O responsável aprovou, em 09/11/2026, melhorar o aplicativo por etapas completas, sem "recortes". Toda otimização foi publicada somente na homologação (VM 120). A produção não recebeu nenhuma delas.

| Etapa | Tema | Situação | Resultado principal |
|---|---|---|---|
| F0 | Fundamentos (ADR-195) | Concluída | Regras de fronteira, cache, telemetria e PR |
| F1 | Diagnóstico | Concluída | Oito perfis, 12 casos HTTP e oito capturas Chrome |
| F2 | Consultas e autorização dos painéis | Publicada na homologação | Painel do professor: 2.500 → 32 consultas |
| F3 | Arquivos e cache técnico | Publicada na homologação | Turma, segunda navegação: 8,16 MB → 39,7 KB |
| F4 | Carregamento e interação | Publicada na homologação | Abas e miniaturas sob demanda; UR4 mais barata |
| F5 | Volume e trabalhos demorados | Diagnosticada; adiada | Único gargalo: busca nacional do catálogo |
| F6 | Capacidade e operação | Parcial | Buffer pool de 1 GB; busca nacional 3,5 s → 1,8 s |

As medidas deste documento vêm de métodos diferentes. Não some nem subtraia valores de métodos distintos. As medianas locais não são P95, SLA (acordo de nível de serviço) nem capacidade concorrente.

## Princípios (ADR-195)

A ADR-195 está aceita como direção de arquitetura para as funcionalidades atuais e futuras. O registro está em [decisões](../decisoes/README.md).

1. **Fronteira API e web.** A API é dona das regras e do banco. A web compõe a apresentação por HTTP. A web não importa tabelas, modelos nem controladores da API. O navegador usa a ponte UR4 (referências sem identificadores na URL) e o cliente `G360Requisicao`.
2. **Arquitetura evolutiva.** Mantenha a API modular. Prepare o contrato para uma API em servidor separado. Não adote SPA (aplicação de página única) com React, microserviços nem novos serviços de notificação por inferência de desempenho.
3. **Acesso sempre atual.** O servidor revalida usuário, vínculo, espaço, perfil e recurso a cada operação. Nenhuma concessão fica guardada no ator, na requisição ou em cache com validade. Preferência, recibo, hash, canal entre abas e cache nunca concedem acesso.
4. **Leitura proporcional.** Projete só os campos necessários. Filtre, ordene e pagine no banco antes de carregar os dados. Substitua SQL por item de coleção crítica por agregação em lote autorizada. Preserve zero, nulo, médias, multiplicidade, histórico e desempates.
5. **Cache por classe de dado.** Ativos técnicos podem ter URL por hash e cache imutável. HTML, JSON, fotos e documentos pessoais não recebem regra genérica de cache.
6. **Privacidade em todos os caches.** Não coloque senha, token, decisão de permissão, dado escolar ou texto livre em cache técnico persistente do navegador nem do servidor. Estrutura sem títulos ainda pode ser metadado pessoal. Não a declare anonimizada.
7. **Concorrência e jobs.** Use transação curta, versão (CAS, comparar e trocar) e chave idempotente conforme o efeito. Uma retentativa nunca repete um comando em silêncio. Jobs revalidam o contexto na execução e na entrega.
8. **Interface progressiva.** Entregue a região inicial pequena. Carregue opções, abas e fotos sob demanda. Preserve o conteúdo anterior, o foco, a rolagem e o celular. Descarte respostas antigas. Desmonte observadores e listeners.
9. **Medição privada e por camada.** Registre release, método, cenário, volume e estado do cache. Meça rede, BFF (camada web que chama a API), SQL, renderização e fila separadamente. Use contagens e fingerprints sem valores. Não exporte HAR (arquivo de captura de rede) bruto.
10. **Evidência e publicação.** Cada etapa fecha implementação, testes, revisão independente e CI antes do merge. Publique primeiro só na homologação. Evidências e relatórios extensos ficam fora do Git, em área operacional protegida.

### Lista de entrada e aceite

| Área | Definir na entrada | Evidência de aceite |
|---|---|---|
| Contrato e volume | Consumidor, DTO, erros, versão ou opt-in, universo e limites | Compatibilidade e cenário pequeno e grande |
| Acesso e concorrência | Contextos, revogação, versão, idempotência, retry | Isolamento e revogação; disputa não duplica nem sobrescreve |
| Consulta e plano | Filtros, ordem, página, agregações, índices | Contagem sem consulta por item; plano dos caminhos críticos |
| Cache | Classe do dado, chave, versão, invalidação, TTL (tempo de vida) ou "sem cache" | Acerto, alteração, revogação; nenhum dado pessoal vedado |
| Interface | Conteúdo inicial, regiões sob demanda, erro e cancelamento | Chamadas e bytes; respostas antigas descartadas; foco e celular |
| Medição | Cenário, release, método, orçamento por camada | Medido separado de meta; overhead conhecido |
| Jobs e operação | Efeito, prioridade, timeout, entrega autorizada, retorno | Falha, duplicata e revogação seguras |

Aplique cada item ao risco da mudança. "Não se aplica" exige uma razão concreta. Não escreva testes que só repitam a implementação nem cronômetros frágeis em CI compartilhada. As metas de latência ainda precisam de calibração. Hoje valem invariantes verificáveis: a contagem de consultas não cresce por item onde deve existir lote, e o runtime de gráficos não se duplica.

## F1 — diagnóstico (release `2cb3305`)

A F1 mediu a homologação sem alterar a aplicação. Ela usou cinco perfis institucionais (administrador da rede, diretor de núcleo, diretor escolar, coordenador e professor) e três internos (desenvolvedor, suporte e financeiro). Foram 12 casos, com três amostras sequenciais por caso. Todas as respostas retornaram HTTP 200.

Os cinco formatos de consulta dominantes vinham da cadeia de contexto (`ContextoVinculo::resolver`, `espacoValido` e `GruposFuncionais::conferir`). No painel do professor, eles somavam 2.325 de 2.500 consultas. No coordenador, 1.375 de 1.445.

| Perfil e caso | API HTTP (ms) | Web HTTP (ms) | Consultas (API CLI) |
|---|---:|---:|---:|
| Administrador da rede, painel | 448,8 | 549,8 | 653 |
| Diretor de núcleo, painel | 680,8 | 777,0 | 880 |
| Diretor escolar, painel | 1.192,9 | 1.320,7 | 1.507 |
| Coordenador, painel | 1.198,9 | 1.319,5 | 1.445 |
| Professor, painel | 2.006,6 | 2.150,9 | 2.500 |
| Coordenador, turma com editor | 475,7 | 2.143,7 | 573 |
| Coordenador, relatório da prova | 1.083,0 | 1.251,2 | 1.112 |

No navegador, a turma carregava 78 recursos e 8,16 MB, praticamente iguais com e sem cache. A causa era `Cache-Control: no-store` em todo o nginx, mais sprites PNG de 2,3 a 2,5 MB cada e dois runtimes ECharts.

## F2 — consultas e autorização dos painéis

A F2 reescreveu a cadeia de contexto e as agregações dos painéis. Publicada na homologação com a release `a7e08f5` (PRs 230 e 231).

### Decisões de projeto

1. **Módulo interno `ConsultaVinculo`.** Compõe o SQL de contexto e alcance (`apps/api/app/Support/ConsultaVinculo.php`). As fachadas existentes continuam. Os predicados folha não chamam o resolver, o accessor de perfil nem `Escopo`. Isso evita ciclos e consultas antecipadas.
2. **Autoridade no SQL.** Cada consulta protegida reavalia usuário ativo, vínculo vigente, perfil, espaço, cliente e hierarquia dentro do próprio SQL. Uma lista de IDs capturada antes nunca concede acesso depois.
3. **Relógio no SQL.** A vigência compara com o relógio corrente do banco, em UTC (`RelogioConsulta`). Datas civis de currículo e aplicação continuam em `America/Sao_Paulo`.
4. **Erros preservados.** `RegimeVinculos::exigir` continua rejeitando contexto inválido com o status e a mensagem anteriores.
5. **Adaptador de junção.** Nos `LEFT JOIN` de registros autorizados, `ConsultasPedagogicas::paraJuncao` exige chave primária e só as colunas usadas. Ele evitou perda de linhas no comparativo por escola do núcleo, causada por interação de composição e otimização no MariaDB 10.11.14. Não use o adaptador em agregações já agrupadas nem em `INNER JOIN`.
6. **Sem mudança pedagógica silenciosa.** Três famílias de empate sem desempate no SQL antigo (última prova da direção, alunos com a mesma média, provas da turma com a mesma data) receberam oráculo específico.

### Resultado medido (versão `2cb3305` → `a7e08f5`)

A comparação nativa cobriu 12 casos de oito perfis, sem divergência de conteúdo. As consultas vêm do profiling CLI. As latências são medianas de três requisições HTTP locais.

| Percurso | Consultas | API HTTP (ms) | Web HTTP (ms) |
|---|---:|---:|---:|
| Painel do professor | 2.500 → 32 | 2.034,7 → 229,4 | 2.142,8 → 329,8 |
| Painel do coordenador | 1.445 → 38 | 1.179,3 → 186,7 | 1.302,5 → 305,8 |
| Painel da direção escolar | 1.507 → 41 | 1.197,8 → 184,2 | 1.305,3 → 296,2 |
| Painel da direção de núcleo | 880 → 32 | 678,4 → 168,5 | 776,4 → 250,1 |
| Turma com editor | 573 → 49 | 469,8 → 187,2 | 1.980,7 → 1.071,7 |
| Correção (cartões) | 321 → 33 | 279,8 → 141,4 | 531,1 → 341,3 |
| Relatório da prova | 1.112 → 39 | 1.077,5 → 324,6 | 1.231,4 → 472,4 |

O adaptador de junção tem custo próprio: num microensaio de uma consulta, 9,8 ms → 11,0 ms. Acompanhe esse custo em novos consumidores. A publicação não teve migration nem alterou dados escolares.

## F3 — arquivos e cache técnico

Publicada na homologação com a release `9aee7fa` (PR 233).

### Decisões

1. **Versão por conteúdo.** O helper `ativo('caminho')` (`apps/web/app/Support/Ativos.php`) gera `asset(caminho)?v=<hash>`. O hash é o xxh128 do arquivo, truncado em 16 hexadecimais. Todo CSS e JS local da web usa o helper. Arquivo ausente mantém a URL sem versão.
2. **Cache por classe, no nginx da VM 120.**

| Resposta | `Cache-Control` |
|---|---|
| CSS, JS, fontes e imagens com `?v=` de 16 hexadecimais | `private, max-age=31536000, immutable` |
| Estáticos sem versão ou com versão inválida | `private, no-cache` (revalida por ETag e devolve 304) |
| HTML, JSON, PHP, API, fotos, documentos, manifesto, `/storage` | `no-store` |

   O valor `private` impede a borda de compartilhar conteúdo protegido pela Basic Auth. A Basic Auth, o `X-Robots-Tag` e o bloqueio de `/storage` continuam em todas as respostas. Uma URL com `v` forjado em formato válido recebe cache imutável só no navegador de quem a pediu e serve o arquivo atual.
3. **Manifesto PWA** (aplicativo web instalável) com `crossorigin="use-credentials"`, para não gerar erro 401 sob a Basic Auth.
4. **Sprites em WebP.** Os arquivos `estudantes-v2.webp`, `educadores-v2.webp` e `brasoes-v2.webp` (1254 × 1254, qualidade 90) substituem os PNG v1 de 2,3 a 2,5 MB. Cada WebP tem 356 a 419 KB. Os PNG v1 ficam por causa da home independente já exportada.
5. **Um runtime de gráficos por página.** O script `scripts/graficos/build.mjs` gera `apps/web/public/js/libs/echarts-g360.min.js` (569.698 B), exposto como `window.echarts`. O `turma-graficos.js` caiu de 565.705 B para 9.955 B. O partial `layouts/_echarts` usa `@once`. Sem o runtime, a turma mostra um estado vazio com texto.
6. **Bibliotecas sob demanda.** `heic-to` (3,2 MB), pdf.js e pdf-lib carregam só nos fluxos que precisam delas.
7. **Sem cache de dados.** Nenhum dado escolar, título livre, credencial ou decisão de permissão entra em cache.

### Resultado medido (Chrome, coordenador, desktop)

A coluna "com cache" mostra a segunda navegação. A coluna "assets sem cache" baixa de novo todos os ativos da tela.

| Percurso | F1, cache normal (bytes) | F3, com cache (bytes) | F3, assets sem cache (bytes) |
|---|---:|---:|---:|
| Painel | 2.609.068 | 15.693 | 516.733 |
| Lista de turmas | 5.270.691 | 53.324 | 1.244.323 |
| Turma | 8.159.322 | 39.688 | 1.695.228 |

Limites: uma amostra por percurso, um perfil, desktop e rede real pela borda. LCP (maior pintura), INP (latência de interação) e celular real não foram medidos.

## F4 — carregamento e interação

Publicada na homologação em três releases: `43ab393` (F4-A), `9e4a1be` (F4-B) e `ed7dd0c` (F4-C e F4-D). A paralelização das chamadas do BFF ficou fora: a API da homologação tem só dois processos FPM, e uma rajada repetiria o esgotamento de 30/10/2026.

| Parte | Mudança | Resultado |
|---|---|---|
| F4-A | Miniaturas da correção por visibilidade (`IntersectionObserver`, margem de 400 px, fila de 4 cargas) | Nenhum pedido de miniatura fora da área visível |
| F4-B | Abas Acesso e Disciplinas da turma carregam um fragmento HTML na primeira abertura (operação `turmas.secao`, com CSRF e revisão de contexto) | Até quatro chamadas a menos na abertura da turma |
| F4-C | Catálogo de rotas UR4 guardado no cache, cifrado pela `APP_KEY`, com assinatura e validade de 30 dias; URL e referência repetidas resolvidas uma vez | Transformação da turma de ~107 ms para ~73 ms (cache em arquivo) |
| F4-D | Gráficos agrupados num quadro de animação; observadores descartados ao sair da página | Sem callbacks tardios; modal não redesenha mais |

Regras da F4-B: o BFF revalida a turma na API antes de montar o fragmento. A resposta é `no-store`. O fragmento recusa seção desconhecida (422), perfil administrativo, aba Acesso em perfil pessoal e Disciplinas fora do escopo DC1 (404). Sem JavaScript, a aba oferece o link da seção renderizada no servidor. A revisão independente aprovou com ressalvas, todas incorporadas.

Limite aceito: o rótulo "Carregando…" pode não ser lido por todos os leitores de tela. O erro sempre é anunciado. O custo restante de 145 gravações de referência por tela da turma (uma por destino) fica para a F6.

## F5 e F6 — volume e capacidade

### Volumes reais da homologação

Os dados escolares são pequenos: 414 alunos, 2.160 respostas e 180 questões. O volume está no catálogo nacional de instituições:

| Tabela | Linhas | Tamanho |
|---|---:|---:|
| `catalogo_unidades` | ~234 mil | 55 MB |
| `catalogo_instituicoes` | ~218 mil | 15 MB |
| `catalogo_identificadores` | ~209 mil | 47 MB |
| `catalogo_entradas` | ~207 mil | 271 MB |
| `catalogo_observacoes` | ~205 mil | 145 MB |

Listas escolares sem paginação ficam abaixo de centenas de linhas. Não há gargalo de volume comprovado nelas. Paginar sem necessidade quebraria consumidores.

### Gargalo: busca nacional por texto no catálogo

| Filtros | Resultados | Tempo antes | Tempo depois do buffer pool |
|---|---:|---:|---:|
| Texto "escola municipal", sem UF | 23.300 | 3,49 s | 1,79 s |
| Texto de 4 letras, sem UF | 24 | 3,25 s | 1,70 s |
| Texto sem resultado, sem UF | 0 | 1,46 s | 0,80 s |
| Texto com UF | 2.093 | 0,13 s | 0,13 s |
| Só UF | 16.251 | 0,14 s | 0,14 s |

Causas medidas:

1. O otimizador parte de `catalogo_municipios` e lê cerca de 207 mil linhas largas para avaliar o `LIKE`. Forçar a ordem publicação → entradas → municípios dá o mesmo resultado em 0,86 s.
2. O buffer pool padrão de 128 MB é menor que os mais de 500 MB do catálogo.
3. `LIKE '%termo%'` obriga a avaliar todas as entradas. Nenhum índice B-tree elimina esse custo.

### Experimento F6-1: buffer pool de 1 GB

O responsável autorizou o experimento e o reinício do MariaDB em 09/11/2026. O operador pausou o worker e o timer de cópias, instalou o arquivo `infra/homologacao/mariadb/90-g360-perf-f6.cnf`, conferiu com `my_print_defaults` e reiniciou (0,8 s). Conferiu tamanho, durabilidade e as 130 tabelas, e restaurou as unidades.

| Item | Antes | Depois |
|---|---|---|
| `innodb_buffer_pool_size` | 128 MB | 1.024 MB |
| `innodb_flush_log_at_trx_commit` | 1 | 1 (inalterado) |
| Leituras de disco por requisição ao buffer pool | ~4,5% | ~0,04% |

Reversão: remova o arquivo e reinicie o MariaDB. A produção não tem este ajuste.

### Decisão do responsável (09/11/2026): busca do catálogo adiada

A busca do catálogo serve a seletores pontuais, e a aplicação ainda não tem uso real. Por isso a otimização fica adiada. Três opções continuam registradas:

| Opção | Efeito | Observação |
|---|---|---|
| Fixar a ordem de junção | ~1,5 s → ~0,9 s por consulta | Baixo risco, mesma semântica |
| Busca por prefixo de palavra (FULLTEXT ou n-gram) | Abaixo de 100 ms | Muda o resultado da busca; é decisão de produto |
| Exigir UF ou município para texto curto | Abaixo de 100 ms | Muda o resultado da busca; é decisão de produto |

Desenho preferido para quando o tema voltar: um componente único de autocompletar acessível; prefixo de palavra; código INEP como busca exata; filtro de UF e município antes do texto; só os 10 primeiros resultados, sem contagem exata; mínimo de 3 letras (2 com UF); espera curta ao digitar; descarte de respostas antigas; índice reconstruído a cada edição publicada. Os pontos de uso mapeados são: vínculo do professor autônomo a uma escola (`InstituicoesPessoaisController`), vínculo de escolas por uma instituição, pedido de inclusão no catálogo, atendimento interno e seleção de UF e município (5.503 municípios).

### Pendências da F6

Meça a memória privativa dos pools FPM (API 2 processos, web 3) e o OPcache com o buffer pool cheio. Ajuste um pool por vez, com reversão própria. Agrupar as gravações de referência da UR4 também pertence à F6.

## Padrão de carga: 1000 usuários simultâneos

**Decisão do responsável (09/11/2026):** toda melhoria de desempenho é avaliada com 1000 usuários simultâneos, em todas as etapas e em tudo o que for testado. A medição de uma requisição isolada serve ao diagnóstico, mas não basta como resultado.

1. Cada percurso medido roda com 1000 usuários virtuais repetindo o pedido por uma janela fixa.
2. O relatório traz média, p50, p95 e p99, pedidos por segundo, taxa de erros (4xx, 5xx e conexões recusadas) e o consumo de CPU e memória da VM.
3. O gerador de carga roda fora da VM medida. O tráfego não passa pela Cloudflare.
4. Antes e depois de cada mudança, a comparação usa o mesmo cenário.
5. A carga é só de leitura. Nenhum teste altera gabaritos, resultados ou dados escolares.
6. O responsável decide como obter as 1000 identidades autenticadas.

Este padrão ainda não foi aplicado a nenhum resultado das etapas acima. As medições de F1 a F4 usam requisições sequenciais. A meta inicial de capacidade do produto é 100 instituições, 600 professores e 1.000 responsáveis (pacote X1, em [FN-077](../funcionalidades/077-desempenho/planejamento.md)).

## CI do MariaDB

O job `integridade-mariadb` levou 29 min 44 s no PR 216. A investigação encontrou duas causas:

1. **E/S na preparação.** O trait `BancoCurriculoDescartavel` executa `migrate:fresh` entre casos (94 migrations, 130 tabelas). A mesma sequência de ~1.540 consultas levou ~42 s em disco e ~2,3 s em tmpfs.
2. **Consultas de contexto repetidas.** Três casos de autorização executaram 26.818 consultas. Cerca de 4.945 resoluções repetiam cinco consultas.

Correções:

- Só o serviço MariaDB do job recebe `/var/lib/mysql` em tmpfs de 1 GiB. Um guarda confere imagem, banco exato, tmpfs real, capacidade e `innodb_flush_log_at_trx_commit=1` antes da suíte. O job de recuperação mantém armazenamento em disco.
- O refactor de contexto reduziu, nos mesmos três casos, 26.818 para 19.065 consultas (-28,9%) e 34,2 s para 27,3 s (-20,3%).
- No GitHub, o job caiu para 20 min 15 s com a mesma suíte (755 testes, 8.163 asserções).

Cada chamada pública continua revalidando o contexto. Nenhum estado de autorização é memorizado.

## Correções de desempenho já em produção

| Data | Correção | Referência |
|---|---|---|
| 30/10/2026 | Grupos PHP-FPM separados para web e API | [13-infraestrutura-e-ambientes.md](13-infraestrutura-e-ambientes.md) |
| 31/10/2026 | Leitura de cartões pela fila `alta` (B6); teste de 50 envios | [publicacao-producao.md](../operacao/publicacao-producao.md) |
| 01/11/2026 | `optimizer_search_depth=4` nas conexões da API | [incidentes.md](../operacao/incidentes.md) |
| 04/11/2026 | Hotfix de revisão de leituras (emissão de links de fotos em lote por prova); a promoção à produção segue a decisão do responsável e o registro está em outro documento (FN-046) | [FN-046](../funcionalidades/046-revisao-leituras/planejamento.md) |

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/194` (cabeçalho) | Diz "F0 a F4 concluídas" e F5 e F6 pendentes de decisões | F6-1 (buffer pool) foi aplicado e medido em 09/11/2026; F5 está adiada por decisão do responsável |
| `docs/192` | Descreve o cache do editor como entrega em andamento | O cache estrutural do editor está publicado na homologação (`CacheComposicaoPainel`, validade de 300 s) |
| `docs/198` | Os PNG v1 e o `echarts-6.1.0.common.min.js` aparecem como "removidos numa entrega posterior" | Os arquivos ainda existem no repositório (`apps/web/public/js/libs/echarts-6.1.0.common.min.js`) e a limpeza segue pendente |
| `docs/194` | Padrão de 1000 usuários simultâneos "em todas as etapas" | Nenhuma medição concluída o aplica ainda; as medições de F1 a F4 são sequenciais |
