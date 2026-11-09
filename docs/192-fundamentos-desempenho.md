# Fundamentos de desempenho do App

**Status:** direção de execução aceita pelo responsável em09/11/2026. **Escopo:** aplicação atual e novas funcionalidades; publicar somente na homologação. Não é autorização para produção, reescrita em React, microserviços ou aumento indiscriminado de infraestrutura.

## Ordem de execução

1. Aplicar os critérios relevantes à integração do editor dos painéis, sem inserir silenciosamente outras otimizações nessa entrega.
2. Concluir CI, revisões, merge e publicação/validação do editor somente na homologação.
3. Executar PERF-01 sobre a release estabilizada: baseline autenticada, navegador/Web/BFF/API/SQL separados, sem exportar dados escolares ou credenciais.
4. Priorizar correções pelos resultados medidos e comparar cada candidata com a mesma população e condições. Metas sugeridas ainda precisam de calibração; não representam SLA atingido.

Relatório detalhado e coleta sanitizada da avaliação inicial ficam no diretório operacional protegido: `operacao-20261102/relatorio-desempenho-homologacao-20261109.md` e `desempenho-homologacao-metricas-readonly-20261109.json`. A avaliação inicial examinou a PR226 publicada; o editor posterior não era melhoria entregue naquele relatório.

## Entrada e aceite das próximas funcionalidades

| Área | Definir antes de implementar | Demonstrar antes de concluir |
|---|---|---|
| Fronteiras | Navegador → App Web/BFF → API → banco; contrato, DTO, método, versão/opt-in, erros e limites | Compatibilidade do consumidor, tipos/status/erros e ausência de acesso direto da Web às tabelas de negócio |
| Acesso | Usuário, perfil, vínculo, espaço e recurso resolvidos/revalidados pelo servidor | Isolamento, revogação e troca de contexto; cache e preferências nunca concedem permissão |
| Cache | Classificação dos campos, chave completa, versão corrente, invalidação, TTL e fallback; “sem cache” é decisão válida | Hit/miss, alteração, permissão revogada, leitor atrasado, falha e logout; nenhum segredo, conteúdo escolar ou texto livre em cache técnico |
| Banco | Universo autorizado, agregações, crescimento, projeção de colunas, ordem e paginação | Consultas sem amplificação por item nas rotas críticas, índices/planos quando necessários, totais corretos e preservação de histórico/zero/null |
| Frontend | Conteúdo inicial e regiões sob demanda; estados regionais e ciclo de vida | Sem loader global entre telas; abortar/desconsiderar respostas tardias; manter foco, scroll, acessibilidade, mobile e nós operacionais únicos |
| Escritas | Transação, versão/CAS, idempotência e comportamento de retry | Disputa entre conexões reais quando aplicável; não duplicar efeitos nem sobrescrever versão concorrente; erro não repete comando |
| Medição | Release, cenário, volume, condição fria/quente e métricas por camada | Separar resultados medidos de metas; tempo em máquina CI compartilhada não substitui ensaio controlado; preservar custo obrigatório de segurança |
| Telemetria | Amostragem, retenção, cardinalidade e fingerprints sem valores | Não registrar corpo, bindings SQL, aluno, e-mail, senha, token ou cookie; contabilizar overhead e limitar volume |
| Jobs/release | Prioridade, timeout/retry, autorização da entrega, migrations/assets e rollback | Retentativa/revogação sem efeitos indevidos; CI/revisões, backup e recibo da candidata; primeiro homologação |

Aplicar cada critério ao risco e à funcionalidade. Justificar o que não se aplica; não criar testes que apenas repitam a implementação ou cronômetros frágeis. Mudança visual conserva o fluxo vigente de consulta prévia e revisão independente. Não introduzir aprovações humanas para subdivisões técnicas já autorizadas.

## Backlog após a baseline

| Grupo | Mudança proposta | Situação |
|---|---|---|
| PERF-01 | Baseline autenticada e decomposição por camada | Próxima após estabilizar o editor |
| PERF-02/03 | Reduzir consultas repetidas de escopo e consultas em loops dos painéis | A priorizar por medição e prova de isolamento |
| PERF-07/08 | Cache/compressão/versionamento dos assets e runtime de gráficos | Problema na origem observado; falta medir borda/navegador |
| PERF-04/05/06/14 | Composição mínima, UR4 deduplicado, fotos e regiões progressivas | A medir por percurso |
| PERF-09/10/12 | DTOs/paginação, planos/índices e documentos em fila quando necessário | Dependem de custo/volume demonstrados |
| PERF-11 | Composição dos painéis com bootstrap, cache técnico versionado e estado em memória | Integrada à candidata do editor; validação da release ainda necessária |
| PERF-13/15 | Capacidade e operação/backup | Ajustar somente com evidência; manter durabilidade e segurança |

## Cache do editor nesta entrega

Bootstrap na consulta principal, sem pedido extra de preferência na abertura normal. Navegador mantém árvore somente em memória; não guarda layout em cookie/localStorage/sessionStorage. A API lê a versão e os títulos atuais e pode reutilizar somente a estrutura técnica derivada por chave imutável de contexto/versão/catálogo/schema/projector/configuração, TTL300s. O hash de contexto é uma referência técnica vinculada a uma pessoa; não é token de acesso nem declaração de anonimização. Cache no próprio banco é evitado para não acrescentar SQL.

ETag reautoriza antes de304; UR4/BFF devolve estado JSON explícito para a consulta condicional. Focus/visibilidade coalescem revalidações. Broadcast transmite apenas invalidação; não transporta árvores. GET atrasado não reduz a versão após Save confirmado. Conflito preserva rascunho e não repete PUT.

Tema e menu já usam preferências visuais locais (`g360-theme`, `g360-menu`); são independentes do editor. Cookies da sessão também são independentes. O inventário e a transparência da aplicação/página pública precisam acompanhar os usos efetivos; nenhuma estratégia de cache ou banner, isoladamente, comprova conformidade LGPD integral.
