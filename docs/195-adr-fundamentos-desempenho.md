# ADR-195 — Fundamentos de desempenho e aceitação das funcionalidades

**Status:** aceito como direção de arquitetura, após aprovação do plano integral em09/11/2026. **Aplicação:** funcionalidades atuais e futuras. **Decisores:** responsável pelo Gabarito360, arquitetura e segurança. **Execução:** etapas completas de [docs/194](194-planejamento-desempenho-integral.md); este ADR não declara essas etapas implementadas nem altera ambiente.

## Contexto

O App Web/BFF e a API Laravel já têm fronteiras e runtimes próprios. Medições da homologação demonstram repetição de consultas, assets sem reaproveitamento e carregamento em duas etapas. A baseline disponível contém métodos distintos: API kernel CLI com identidade em memória e navegação HTTP real; não se pode subtrair seus tempos ou anunciar percentis de uma única amostra. Consultas e preferências não concedem acesso. Reduzir chamadas não autoriza enfraquecer revalidação, isolamento ou auditoria.

O usuário aprovou planejar e executar o conjunto por etapas. Uma melhoria de helper, como reduzir statements de um resolver, pode ser experimento interno de F2; não encerra a etapa de consultas/autorização e painéis. F0 consolida regras; F1 identifica os gargalos por cenário e método; F2–F6 implementam e validam conjuntos completos. Produção continua fora da publicação automática.

## Decisão

1. **Fronteira API/BFF:** banco e regras de negócio pertencem à API. Web consome HTTP e compõe apresentação; não importa tabelas/modelos/controladores da API. Contratos descrevem método, versão/opt-in, DTO, erros, limites e compatibilidade. Navegador usa UR4/G360Requisicao; IDs, credenciais e referências de acesso não entram nas URLs públicas.
2. **Arquitetura evolutiva:** manter API modular, serviços de aplicação e queries de leitura com responsabilidade explícita. Preparar contrato e operação para servidor API separado quando necessário. Não adotar SPA/React, microserviços ou novos serviços de push por inferência de desempenho.
3. **Acesso atual:** usuário/vínculo/espaço/perfil/recurso/histórico são revalidados pelo servidor. Consolidar predicados/leituras e remover amplificação de queries sem guardar concessões no ator, por requisição ou TTL. Preservar revogação com o mesmo ator e autoridade na execução das operações. Preferência, recibo, hash, canal entre abas e cache não autorizam acesso.
4. **Leitura proporcional:** projetar somente campos necessários; filtrar, ordenar e paginar no banco antes de materializar. Totais do recorte são independentes da página. SQL por item de coleção crítica é substituído por agregação/lote autorizado; preservar zero/null, médias, multiplicidade, histórico e desempates. Escolher índice por query/plano/volume medidos, não por lista genérica.
5. **Cache por classe de dado:** assets técnicos classificados podem ter URL por hash e política imutável compatível com a proteção da homologação; HTML/JSON/foto/documento pessoal não recebe regra genérica de cache. Cache estrutural pessoal usa contexto/versão/catálogo/capacidades/schema/padrão/projetor e autoridade corrente. TTL limita retenção/custo; não define autorização nem substitui versão atual. Valores antigos só preenchem chaves da sua versão, evitando repopulação de `current` obsoleto.
6. **Privacidade em todos os stores:** não colocar senha/token, decisão de permissão, dados escolares ou texto livre em cache técnico persistente do navegador **ou servidor**. Títulos pessoais podem conter informação escolar; árvore completa permanece na memória da página, títulos atuais vêm da fonte autoritativa. Estrutura sem títulos ainda pode ser metadado pessoal associável: não declarar anonimização. Banco autoritativo, sessão/auditoria existentes e suas finalidades próprias não são dispensados por esta regra nem renomeados como cache técnico.
7. **Concorrência e jobs:** transação curta, versão/CAS e chave idempotente conforme o efeito. Retry não repete comando silenciosamente. Jobs tratam entrega repetida, timeout/retry_after, prioridade, rollback e despacho após commit quando necessário; contexto é revalidado na execução e entrega. Não remover conferência de acesso/conteúdo para acelerar documento.
8. **Frontend progressivo:** região inicial pequena, opções/abas/fotos/módulos sob demanda e feedback local acessível. Preservar conteúdo anterior na atualização, foco, scroll, impressão e mobile. Abortar/desconsiderar respostas antigas e desmontar listeners/observers/blobs. Loader global não substitui tratamento regional. Consolidar runtime e coalescer renderizações; verificar no browser.
9. **Medição privada e por camada:** identificar release, método, cenário lógico, volume, população e estado de cache. Relatar tempo de rede, BFF, SQL, render e fila separadamente, distinguindo intervalos inclusivos/exclusivos. Contagens/fingerprints sem valores, cardinalidade/retenção limitadas; nenhum corpo, SQL literal/binding, aluno, título, e-mail, cookie ou credencial em evidência de performance. Não exportar HAR bruto.
10. **Evidência e publicação:** cada etapa fecha implementação aplicável, testes proporcionais, revisão independente e CI antes do merge/publicação correspondente só na homologação. F0/F1 podem fechar em documentos/evidências sem deploy quando não há delta de app/configuração. Mudança visual mantém consulta prévia/revisão de UI vigente. Backups/rollback e comparação de dados/arquivos/schema seguem o procedimento operacional. Experimentos de outras etapas não entram por acidente no commit.

## Checklist proporcional de entrada e aceite

| Área | Definir na entrada | Evidência de aceite |
|---|---|---|
| Contrato e volume | Consumidor, DTO/status/erros, opt-in/versão, universo e limites | Compatibilidade, campos necessários e cenário pequeno/grande identificado |
| Acesso e concorrência | Contextos, revogação, CAS/idempotência/retry quando houver efeito | Isolamento e revogação; disputa/duplicata não sobrescrevem ou repetem indevidamente |
| Consulta e plano | Filtros/ordem/página/agregações; crescimento e índices existentes | Contagem sem query por item; plano dos caminhos críticos; totais e semântica preservados |
| Cache | Classe de dado, chave, fonte da versão atual, invalidação/TTL/falha ou decisão “sem cache” | Hit/miss, alteração/revogação/leitor atrasado; ausência de dado pessoal vedado em novo cache técnico |
| Interface e lifecycle | Conteúdo inicial, módulos/regiões sob demanda, erro/cancelamento | Trace de chamadas/bytes; callbacks antigos descartados; foco/teclado/mobile/revisão visual quando aplicáveis |
| Medição e telemetria | Cenário/release/método; orçamento por camada e dados permitidos | Resultado medido separado de meta; overhead/limites; evidência sanitizada fora do Git |
| Jobs e operação | Efeito, prioridade, timeout/retry, entrega autorizada, rollout/rollback | Falha/duplicata/rollback/revogação seguros; etapa e candidata rastreáveis |

Aplicar cada item ao risco. “Não aplicável” exige razão concreta; não pede cerimônia nem teste de corrida para edição textual. Usar o template de PR. Não criar aprovação humana para cada subdivisão já autorizada. Não escrever testes que apenas espelhem a implementação nem cronômetros frágeis de CI compartilhada.

## Orçamentos e verificação

As metas de latência/queries/bytes precisam de baseline e calibração. Não tornar números sugeridos no relatório operacional um SLA automático. Exigir inicialmente invariantes verificáveis: contagem não cresce por item onde deve existir lote; coleção nova é limitada; runtime não é duplicado; carga secundária não é antecipada; custo obrigatório de segurança permanece medido. Fixtures/CI comprovam contrato, crescimento, isolamento e tamanho determinístico; HTTP/browser em cenário definido comprovam o comportamento observado. Não comparar CLI com HTTP como se fossem a mesma medida, nem produzir P95 a partir de amostra insuficiente.

F1 usa prioritariamente inventário de fonte, HTTP real de homologação com identidade/sessão de ensaio próprias autorizadas e profiling CLI operacional que delega à aplicação original. Sem alteração do app quando esses meios bastam. Um eventual delta de observabilidade precisa ser opt-in, staging, desativado por padrão, sem payload/SQL literal e sem modificar contrato/autoridade; seria entrega controlada de F1, nunca incorporação do experimento F2.

## Alternativas consideradas e consequências

- **Escolhida:** melhorar consultas, dados transferidos e lifecycle por etapas, preservando as fronteiras existentes. Permite comprovar benefícios e preparar independência operacional sem reescrita.
- **Cache de concessões/HTML pessoal:** rejeitado; desloca o problema para revogação e retenção indevidas.
- **Mais hardware ou nova plataforma primeiro:** sem evidência suficiente; pode mascarar amplificação e aumentar operação. Capacidade será avaliada em F6 após reduzir trabalho evitável.
- **Experimento pontual como entrega integral:** rejeitado pela direção humana. A etapa permanece aberta até cumprir seu conjunto de critérios.

Consequência: planejamento e PR precisam declarar método e cobertura; relatórios extensos e dados de ensaio permanecem no diretório operacional protegido, não no Git. A documentação aceita não significa que todos os gates/otimizações já foram implementados. Evolução do ADR exige decisão explícita sobre mudança do princípio, sem reabrir permissões para ações técnicas já autorizadas.

## Referências

- [Fundamentos e histórico do editor](192-fundamentos-desempenho.md).
- [Planejamento integral por etapas F0–F6](194-planejamento-desempenho-integral.md).
- Relatório e protocolos operacionais em `operacao-20261102`, com release/método registrados e dados sensíveis excluídos.
