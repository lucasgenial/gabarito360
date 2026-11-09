# F1 — Diagnóstico de desempenho da homologação

**Data:** 09/11/2026. **Release medida:** `2cb3305c1e01b3428f16000bb159409455ff0d7c` (PR228). **Ambiente:** somente homologação. **Resultado:** baseline e prioridades para F2–F6 obtidas, sem otimizações ou alteração da aplicação/configuração nesta entrega.

## Método e cobertura

Inventário com cinco perfis institucionais elegíveis (Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador e Professor) e três internos (Desenvolvedor, Suporte e Financeiro). Foram usados vínculos existentes, sem criar usuários, conceder permissões ou adaptar cadastros. Os demais perfis previstos no código permanecem identificados como ausentes da população atual; sua cobertura funcional usa fixtures, sem inventar latências reais para eles.

Na janela do servidor, 10:57:06–10:58:44 UTC, foram executados 12 casos: 36 GETs API HTTP autenticados, 36 consultas Web UR4 com CSRF/revisão e 33 GETs de estrutura; todos responderam 200. O caso de cartões é uma consulta JSON, portanto não exige GET de estrutura. Mais 12 amostras API kernel CLI e 12 Web kernel CLI explicam SQL/composição, separadas da série HTTP. Os três casos API internos são controles de identidade `/auth/me`, não painéis pedagógicos.

Autenticação HTTP usa credenciais de ensaio próprias, com validade de 30 minutos e habilidades/contextos do protocolo vigente, sem permissões adicionais. A API HTTP percorre autenticação normal; sessões Web próprias passam pelos middlewares, mutex, CSRF e revisão reais. Os 16 tokens e oito sessões de ensaio foram removidos e sua remoção conferida pelo coletor. A auditoria normal dos GETs não foi apagada. Execução PHP como `www-data`, sem bootstrap da aplicação como root.

## API e Web HTTP

Medianas de **três amostras sequenciais por caso** em loopback. Não incluem a internet/borda ou renderização do navegador, não são P95 nem SLA. Cache e OPcache normais, sem flush/aquecimento deliberado; primeira amostra não é declarada fria.

| Perfil / caso | API HTTP (ms) | Hidratação Web HTTP (ms) | Statements da amostra API CLI |
|---|---:|---:|---:|
| Administrador da Rede / painel | 448,792 | 549,836 | 653 |
| Diretor de Núcleo / painel | 680,822 | 777,029 | 880 |
| Diretor Escolar / painel | 1.192,929 | 1.320,713 | 1.507 |
| Coordenador / painel | 1.198,942 | 1.319,532 | 1.445 |
| Professor / painel | 2.006,601 | 2.150,889 | 2.500 |
| Coordenador / lista de turmas + editor | 424,871 | 820,107 | 518 |
| Coordenador / turma + editor | 475,681 | 2.143,703 | 573 |
| Coordenador / cartões da correção | 284,679 | 498,009 | 321 |
| Coordenador / relatório da prova | 1.083,028 | 1.251,231 | 1.112 |
| Desenvolvedor / identidade e painel interno | 16,345 | 79,468 | 3 |
| Suporte / identidade e painel interno | 16,762 | 82,290 | 3 |
| Financeiro / identidade e painel interno | 19,256 | 74,043 | 3 |

Os statements vêm de **outro método**, API kernel CLI com guard contextual em memória e conexão read-only, sem transporte/validação de Bearer persistido nem `terminate()`. Não subtrair esses tempos dos HTTP para atribuir overhead. A auditoria de captura durante o kernel permanece; o enfileiramento de `terminate()` não integra essa amostra CLI. HTTP percorre o serviço normal, com seus efeitos operacionais próprios.

## Consultas e composição

Os cinco shapes dominantes mapeiam a cadeia `ContextoVinculo::resolver`, `espacoValido` e `GruposFuncionais::conferir`. No painel do professor são 465 ocorrências por shape, 2.325 statements de um total de 2.500. No coordenador, 275 por shape, 1.375 de 1.445. O backtrace registrado identifica **somente a primeira ocorrência** de cada shape: não atribui todas as repetições à Auditoria nem justifica remover auditoria, revogação ou verificações de acesso.

Os EXPLAINs projetados mostram uso de PK/índices únicos nos acessos pontuais e consulta de metadados na conferência repetida de existência de tabela. A evidência favorece reduzir a amplificação da aplicação antes de aumentar hardware ou criar índices genéricos. Isso não prova que toda consulta do sistema tenha plano ideal.

Na amostra Web CLI da turma houve oito chamadas HTTP reais à API: identidade, turma, professores, usuários professores, disciplinas, opções docentes, painel e vínculos. Elas ocorreram na abertura da visão geral, evidenciando composição sequencial de regiões/opções que serão revistas em F4. O bootstrap do editor veio na leitura principal, sem chamada adicional exclusiva de preferências.

Os wrappers operacionais delegam aos middlewares originais; o tempo exclusivo é elapsed menos downstream. Na turma observaram-se 0,144 ms de overhead do mutex, 37,593 ms de conferência de contexto, 150,341 ms de transformação UR4 e 177,063 ms de Blade com controle de profundidade. São amostras CLI, **sem contenção provocada**; mutex não é espera pura. Conferência de contexto inclui `/auth/me`; render inclui seus compositores. Não somar intervalos sobrepostos nem tratar isso como decomposição da mesma requisição FPM.

## Navegador e arquivos

Oito capturas atuais no Chrome autenticado: painel, lista e turma com cache do tab desabilitado e normal; correção com cartões existentes; lista em viewport móvel de 393 × 852. Desktop observado em 2560 × 1297. Nenhum cache global foi limpo; viewport e coleta foram restaurados ao final, sem alterar layout salvo ou registros.

| Percurso desktop | Recursos concluídos | Bytes transferidos de recursos, cache desabilitado | Bytes transferidos de recursos, cache normal |
|---|---:|---:|---:|
| Painel | 49 | 2.609.515 | 2.609.068 |
| Lista de turmas | 60 | 5.271.418 | 5.270.691 |
| Turma | 78 | 8.161.093 | 8.159.322 |

São bytes de `PerformanceResourceTiming`, incluindo a parcela de transferência reportada pelo browser; documento de navegação é contabilizado separadamente na evidência. Não somar novamente os bytes CDP. As três sprites públicas somaram 7.027.345 bytes na turma. ECharts comum (~255 KB transferidos) e `turma-graficos.js` (~207 KB) foram carregados juntos. Os metadados de assets observados indicam `no-store`; a borda usa zstd em respostas observadas. A pendência não é genericamente “ativar compressão”: é reduzir bytes/runtimes e permitir reaproveitamento seguro por versão/hash.

Na correção foram 115 recursos / 3.299.628 bytes na janela, incluindo 48 chamadas à rota de miniaturas e consultas periódicas de status. Na lista móvel foram 61 recursos / 5.301.344 bytes; menu inferior visível e editor oculto. O viewport emulado não representa CPU/rede de um celular real. Manifesto público técnico recebeu 401 repetidos sob a proteção Basic: revisar referência/credenciais do recurso em F3, preservando a proteção do ambiente.

Houve limitação/intermediação da automação do navegador e truncamento de alguns buffers CDP. Por isso, buffers incompletos não fundamentam as somas de bytes; usam-se as entradas nativas de recursos concluídos. Os tempos Chrome não são latência pura atribuível ao aplicativo nem são combinados com a série HTTP. Paint/FCP foram registrados; LCP/CLS/INP e capacidade concorrente não foram obtidos e permanecem validações específicas das etapas seguintes.

## Segurança, integridade e limites

Comparação de todas as colunas de 26 tabelas declaradas de negócio, incluindo usuários, vínculos/permissões, turmas/alunos, provas/gabaritos, cartões/respostas/notas, histórico de revisão e preferências: hashes e contagens idênticos durante a janela do servidor. Isso não é manifesto de todo o banco ou storage. Tokens, sessões, cache técnico e auditoria HTTP têm efeitos operacionais próprios; não declarar filesystem read-only nem ausência de toda escrita.

Evidências contêm somente rotas lógicas normalizadas, contagens, tempos, tamanhos, hashes e planos projetados. Não foram exportados SQL literal, bindings, payload escolar, cookies, tokens, nomes de aluno, títulos livres ou HAR bruto. Produção não foi acessada. A sessão do Chrome expirou antes da coleta operacional e foi reaberta normalmente pelo responsável; é independente das oito sessões de ensaio.

Fixtures existentes executadas sem alterações do código de app: 71 testes API / 1.465 assertivas e 12 Web / 230 assertivas, cobrindo fronteiras de perfil, revogação, espaço pessoal, restrições administrativas e estados de interface. São testes funcionais, não medições de latência dos perfis ausentes. As amostras não caracterizam capacidade, contenção real, P95 ou SLA.

## Prioridade das próximas etapas

1. **F2 completa:** cadeia de contexto/perfil/Regime/grupos/escopos e consultas dos painéis, começando pelos custos observados do professor e da gestão; agregações em lote e equivalência de contrato/revogação. A redução isolada de um helper não fecha F2.
2. **F3:** sprites, versionamento/cache, runtime único de gráficos, duplicações entre estrutura/hidratação e manifesto, mantendo proteção e identidade visual.
3. **F4:** composição inicial mínima, opções/abas e fotos sob demanda, referências UR4 deduplicadas e lifecycle do frontend.
4. **F5/F6:** volume, paginação, índices/fila e capacidade pelos gargalos remanescentes medidos; sem enfraquecer durabilidade ou segurança.

Protocolo, JSONs, logs, hashes e pareceres independentes ficam fora do Git em `operacao-20261102/perf-f1-20261109` e no parecer operacional de arquitetura. A release da aplicação continua a PR228; o merge documental F0/F1 não exige deploy nem migration. Ver [plano integral](194-planejamento-desempenho-integral.md) e [ADR195](195-adr-fundamentos-desempenho.md).
