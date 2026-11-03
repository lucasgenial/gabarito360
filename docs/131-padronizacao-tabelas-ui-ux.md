# TBL1 — padronização de tabelas e listas da área logada

Parecer independente de UI/UX e Product Design, em 03/11/2026. Referência de código: `f8e4ef3`. Este documento registra planejamento; não implementa componentes e não aprova visualmente as 41 tabelas atuais.

## Escopo, metodologia e evidência

O responsável solicitou padronizar todas as tabelas, admitindo apresentação em cards no celular e preservando seus botões, links, campos relevantes e permissões. KPIs complementam as listas; não substituem registros ou ações.

Inventário obtido por `rg` e conferência dos blocos `<table>`, cabeçalhos, ações, formulários, condições Blade, CSS e JavaScript: **41 instâncias em 33 views/partials logadas**. Os caminhos abaixo são relativos a `apps/web/resources/views`; a ordem de instâncias é a ordem dos elementos no arquivo. A tabela ilustrativa de `home/index.blade.php` é pública e não entra nessa contagem.

Aplicadas as orientações das skills `design:design-critique`, `product-design:index`, `product-design:audit` e `frontend-design`: tarefa e contexto antes da composição; hierarquia, usabilidade, consistência, estados e acessibilidade; preservação da marca azul/Figtree e dos componentes existentes. As referências de Product Design e o preflight de contexto foram consultados na revisão em andamento. Não se trata de novo produto ou identidade visual.

Esta rodada é uma **auditoria estrutural de código**. A revisão visual da busca CI1-F e do layout global usa capturas próprias e tem parecer separado. Ela não aprova antecipadamente o redesenho das tabelas. Screenshots também não comprovam, isoladamente, acessibilidade completa. O implementador não aprova sua própria UI; cada entrega terá revisão independente de renderizações atuais e ações principais/erros, conforme `AGENTS.md`.

## Inventário confirmado e preservação

Legenda: F = filtros presentes; P = paginação apresentada; O = ordenação interativa. Ausente significa não encontrado na superfície examinada, não inexistência de ordenação interna no banco. “Dados secundários” continuam disponíveis no celular, eventualmente em expansão acessível. Ações fora da linha, mas necessárias ao fluxo, são indicadas como contexto.

| Arquivo / instância | Tarefa; dados principais e secundários | Ações atuais e condições a preservar | F / P / O atuais | Direção mobile |
|---|---|---|---|---|
| `alunos/index.blade.php` · 1 | Encontrar aluno: nome, turma, situação; matrícula e série | Ver aluno | F nome/matrícula e turma; sem P/O | Card identificado pelo nome, com todos os campos e Ver |
| `provas/index.blade.php` · 1 | Encontrar prova: título, situação, progresso; turmas, aplicação e metadados | Publicar se rascunho e `prova.editar`; Acompanhar/Relatório/Ver por situação; Copiar pelo partial quando `pode_copiar` | F título/disciplina/situação; sem P/O | Card com progresso textual e ação contextual; preservar Copiar |
| `turmas/index.blade.php` · 1 | Identificar turma e escola/período; série, turno, alunos, desempenho, situação | Ver turma; desempenho ausente para secretário escolar | F nome/escola/ano; sem P/O | Card turma+escola+ano, métricas permitidas e Ver |
| `turmas/show.blade.php` · 1 | Consultar alunos: nome; matrícula, média, provas, situação | Ver; busca local da seção | F local nome; sem P/O | Card de aluno |
| `turmas/show.blade.php` · 2 | Acompanhar provas: título/situação; disciplina, aplicação, média | Acompanhar/Relatório/Ver por situação | Sem F/P/O | Card de prova |
| `turmas/show.blade.php` · 3 | Gerir professores: nome; e-mail e situação | Desvincular; formulário contextual para vincular, preservando usuário e vínculo | Sem F/P/O | Card de professor; Desvincular separado da consulta |
| `turmas/administrativo.blade.php` · 1 | Consultar alunos no perfil administrativo: nome, matrícula, situação | Sem ação na linha; edição/ativação da turma no contexto | Sem F/P/O | Card sem adicionar desempenho acadêmico |
| `membros/index.blade.php` · 1 | Gerir pessoa/vínculo por perfil: nome, situação; e-mail, início, espaço, identificador do vínculo | Editar; Permissões; Suspender/Reativar vínculo apenas nas situações atuais ou Desativar/Ativar conta | F nome/e-mail/perfil; sem P/O | Card identifica pessoa e vínculo; mantém todas as ações permitidas |
| `escolas/show.blade.php` · 1 | Consultar turmas resumidas: turma, turno, alunos, situação | Sem ação explícita na linha | Sem F/P/O | Card compacto de turma |
| `escolas/show.blade.php` · 2 | Consultar turmas completas: turma, ano, turno, alunos, situação | Sem ação explícita na linha | Sem F/P/O | Card incluindo ano |
| `escolas/show.blade.php` · 3 | Consultar provas: título; disciplina, turma, aplicação, alunos, média, situação | Acompanhar/Relatório/Ver por situação | Sem F/P/O | Card da prova e turma com ação contextual |
| `escolas/show.blade.php` · 4 | Consultar alunos: nome; matrícula, turma, média, provas, situação | Ver | Sem F/P/O | Card do aluno |
| `clientes/index.blade.php` · 1 | Gerir clientes: nome; tipo, modalidade, situação | Ver cadastro | F busca/tipo/modalidade; P servidor; sem O | Card com paginação preservada |
| `clientes/form.blade.php` · 1 | Consultar espaços: nome; tipo, situação, vínculos | Sem ação na linha | P servidor; sem F/O | Card do espaço sem inventar autorização administrativa |
| `catalogo-atendimento/index.blade.php` · 1 | Acompanhar protocolo: instituição, situação; município/UF e atualização | Abrir atendimento ou Acompanhar solicitação, conforme modo | F situação; P servidor; sem O | Card de protocolo com ação identificável |
| `copias/index.blade.php` · 1 | Acompanhar operação: conteúdo, situação; criação, publicação, auditoria pendente | Abrir operação somente quando `operacao_disponivel`; preservar lado/contexto | F espaço/lado; P servidor; sem O | Card de operação, estado e aviso de auditoria |
| `copias/cadastros.blade.php` · 1 | Escolher turma: nome; série e ano | Abrir turma | F espaço; P servidor; sem O | Card com Abrir turma |
| `copias/cadastros.blade.php` · 2 | Selecionar alunos: nome e matrícula | Checkbox individual; seleção da turma e continuação no formulário | P servidor; sem O | Seleção explicitamente associada ao aluno |
| `copias/materiais.blade.php` · 1 | Selecionar provas: título; disciplina, série, questões | Checkbox individual e continuação do formulário | F espaço; P servidor; sem O | Card selecionável com checkbox e rótulo inequívoco |
| `copias/resultados.blade.php` · 1 | Selecionar resultado: aluno e prova; matrícula, turma, nota | Checkbox individual; destino e continuação no formulário | F espaço; P servidor; sem O | Card identificado por aluno+prova, nota e seleção |
| `copias/_cadastro.blade.php` · 1 | Conferir cadastro selecionado: nome; matrícula, avatar, ativo; nascimento quando destino escolar | Sem ação na linha; preservar condição de nascimento | Sem F/P/O | Card de conferência com todos os campos permitidos |
| `copias/_materiais.blade.php` · 1 | Conferir gabarito: questão, alternativa; espanhol, peso, anulação | Sem ação na linha | Sem F/P/O | Lista compacta por questão, sem esconder peso/anulação |
| `copias/_pacote-resultados.blade.php` · 1 | Conferir respostas: questão e resposta | Disclosure de conferência | Sem F/P/O | Lista compacta no disclosure |
| `copias/_guia.blade.php` · 1 | Entender perfil e alcance | Sem ações | F/P/O não se aplicam | Lista de definição, preservando descrição integral |
| `correcao/turma.blade.php` · 1 | Comparar alunos × provas: notas e média; pendências/acertos | Abrir aluno, Revisar e abrir resultado pela nota | F só faltantes; sem P/O | Resumo por aluno com cada ação; quadro comparativo rolável opcional |
| `relatorios/aluno.blade.php` · 1 | Consultar provas do aluno: prova, nota; acertos, média turma, posição | Acertos e erros quando houver nota | Sem F/P/O | Card da prova com métricas e ação |
| `relatorios/prova.blade.php` · 1 | Consultar resultados: aluno; turma, nota, situação | Ver prova | Sem F/P/O | Card do aluno e resultado |
| `relatorios/turma-prova.blade.php` · 1 | Analisar desempenho: aluno, nota; desempenho e situação | Ver detalhes | Sem F/P/O | Card de aluno com ação |
| `relatorios/turma.blade.php` · 1 | Comparar provas: prova; corrigidos, média, aprovação, maior, menor | Análise por questão quando houver corrigidos | Sem F/P/O | Card da prova com métricas rotuladas |
| `relatorios/turma.blade.php` · 2 | Comparar alunos × provas/disciplinas: aluno, notas, média; posição | Abrir aluno e abrir resultado pela nota | Sem F/P/O | Detalhe por aluno com resultados acionáveis; quadro opcional |
| `relatorios/consolidado.blade.php` · 1 | Comparar áreas: área, média, aprovação, notas | Sem ação na linha | Sem F/P/O | Blocos de métricas por área |
| `relatorios/consolidado.blade.php` · 2 | Comparar turmas/escolas: grupo, notas; posição, média, aprovação, alunos | Abrir turma ou escola pelo nome, conforme agrupamento | Sem F/P/O | Card por grupo, disciplinas acessíveis e ação preservada |
| `relatorios/_analise-questoes.blade.php` · 1 | Comparar alternativas: questão, distribuição, acerto; branco e gabaritos/idioma | Sem ação na linha | Destaques ordenados internamente; sem O interativa | Card por questão com distribuição completa e quadro opcional |
| `relatorios/_distribuicao-notas.blade.php` · 1 | Entender distribuição: faixa e quantidade | Sem ação na linha | F/P/O não se aplicam | Lista curta ou barras com valores textuais |
| `assinatura/index.blade.php` · 1 | Consultar pagamentos: valor, situação; data, método, ID | Sem ação por linha; cancelar assinatura é ação contextual separada | Sem F/P/O | Card identificado como pagamento, mantendo ID e método |
| `dashboard/admin.blade.php` · 1 | Acompanhar usuários: nome; perfil, unidade, último acesso, situação | Sem ação na linha | Resumo sem F/P/O | Card de usuário; não inventar rotas de gestão |
| `dashboard/aluno.blade.php` · 1 | Acompanhar disciplina: disciplina, nota; última prova, média, tendência | Sem ação na linha | Resumo sem F/P/O | Card por disciplina com métricas identificadas |
| `dashboard/aplicador.blade.php` · 1 | Executar aplicação: prova; disciplina, turmas, alunos, cartões enviados | Fotografar nos estados permitidos | Resumo sem F/P/O | Card da prova com Fotografar e progresso |
| `dashboard/diretor-escolar.blade.php` · 1 | Acompanhar turmas: turma; alunos, última prova, média, situação | Sem ação na linha | Resumo sem F/P/O | Card por turma; KPIs complementares |
| `dashboard/diretor-nucleo.blade.php` · 1 | Acompanhar escolas: escola; turmas, alunos, média, situação | Sem ação na linha | Resumo sem F/P/O | Card por escola; preservar cada registro |
| `dashboard/secretaria.blade.php` · 1 | Acompanhar redes: rede; modalidade, escolas, alunos, média | Sem ação na linha | Resumo sem F/P/O | Card por rede com métricas rotuladas |

As condições de rota, middleware, API, `Acesso::pode`, `@pode`, propriedade, contexto, situação e versão continuam obrigatórias mesmo quando não repetidas na célula do inventário. Ausência de ação atual não autoriza criar uma operação nova. Revisar novamente os partials incluídos ao implementar cada superfície; copiar prova demonstra por que procurar apenas links dentro do `<table>` é insuficiente.

### Listas equivalentes já em cards

| Superfície | Identificação e ações a preservar | Contrato relevante |
|---|---|---|
| `escolas/index.blade.php` | Escola/INEP, situação, contatos, indicadores; abrir escola; Nova escola conforme permissão; busca | Busca local atual não deve aparentar pesquisa global paginada |
| `instituicoes/index.blade.php` | Referência pessoal, instituição, situação/edição; Arquivar referência; Conferir catálogo; Adicionar/Consultar catálogo; solicitações permitidas | Paginação servidor; arquivamento com versão; referência não concede vínculo |
| `instituicoes/buscar.blade.php` | Identidade pública e origem; Conferir instituição; filtros; solicitar cadastro no vazio permitido | Busca/paginação servidor e separação entre referência pública e acesso escolar |
| `pedidos/index.blade.php` | Tipo, situação, cliente/espaço, protocolo; Abrir atendimento e Abrir protocolo autorizado | Paginação/contexto e atribuição de atendimento |
| `internos/index.blade.php` | Pessoa/perfil/situação/validade; Conceder acesso e Salvar acesso individual | Justificativa, versão da autorização, contexto e paginação servidor |
| `dashboard/_provas-ciclo.blade.php`, reutilizado por painéis e `correcao/index.blade.php` | Prova, turmas, professor, data, situação/progresso; Continuar gabarito, Corrigir, cartões por turma, Resultados, Detalhes | Preservar permissões e estados de cada ação; não reduzir vários links de cartões a um link sem turma |

## Diagnóstico comum e padrão proposto

Há famílias CSS paralelas: `.table`, `.data-table`, `.tab`, `.quadro`, `.aq-tabela` e `.historico-table`. A apresentação móvel varia entre `.lista-cel` e rolagem simples. O adaptador de `app.js` obtém rótulos do primeiro cabeçalho via JavaScript; o CSS móvel presume que a primeira célula é título e oculta `.hide-sm`. Isso falha como padrão universal para checkbox, posição ou data e pode retirar campos sem alternativa.

Adotar quatro tipos explícitos, compartilhando tokens e controles:

1. **Lista operacional:** registro identificável, situação, informação secundária e ações.
2. **Seleção em lote:** identidade + checkbox, quantidade/alcance da seleção e continuação clara. Não prometer selecionar todos os resultados quando apenas a página está selecionada.
3. **Matriz comparativa:** relações aluno/prova/disciplina preservadas; resumo móvel por entidade e quadro rolável opcional, sem eliminar links de cada resultado.
4. **Resumo estatístico ou informativo:** lista curta/definições/métricas; sem controles de pesquisa ou paginação decorativos.

Componente Blade comum, com aprimoramento progressivo por JavaScript leve, é a direção recomendada. DataTables não é requisito. Cabeçalho/total, filtros, estado vazio, paginação e slots de ações podem ser compartilhados; células e prioridade móvel precisam ser explícitas por superfície. Renderizar rótulos necessários no servidor. Evitar duas cópias simultaneamente acessíveis do mesmo formulário, IDs duplicados ou divergência entre ações de tabela e card.

- Superfícies ocupam toda a área útil do viewport, com margens responsivas e grupos empilhados, conforme decisão global. Textos podem manter medida de leitura; não impor teto fixo ao painel.
- Usar títulos claros, cabeçalhos de coluna e `scope`, caption/contexto quando útil; números alinhados, datas e estados consistentes. Não depender apenas de cor.
- Ações: principal evidente; secundárias encontráveis; destrutivas distinguíveis e com identificação do alvo. Menus, se necessários, acessíveis por teclado, toque e leitor de tela. Botões não desaparecem no mobile.
- Campos secundários podem entrar em expansão identificada. Campos não autorizados continuam ausentes; não viram simplesmente conteúdo recolhido.
- Filtros ativos visíveis e preservados na paginação; limpar filtros previsível; vazio inicial, nenhum resultado e erro recuperável distintos.
- Ordenação só onde útil e suportada, com estado/critério perceptível e `aria-sort` quando aplicável. Sem ícone clicável decorativo.
- Paginação informa alcance e total apenas quando o contrato os fornece. KPIs distinguem agregado completo de amostra/página; não substituir dados ausentes por zero.
- No mobile, ações com foco permanecem acima de rodapé/navegação; documento sem overflow horizontal, exceto dentro do quadro deliberadamente rolável.
- Relatórios mantêm impressão A4 e campos acadêmicos completos. Não aplicar uma transformação genérica de cards ao CSS de impressão.

## Contratos de dados e segurança

Os controllers web `AlunoController`, `ProvaController`, `TurmaController` e `MembroController` encaminham os filtros atuais, mas não `page` ou ordenação. Isso não significa que a resposta seja uma primeira página truncada. Segundo a conferência adicional do implementador, as listagens API de alunos, turmas e provas usam `get()` sobre a coleção filtrada pelo escopo. Pesquisa, ordenação e paginação locais podem ser verdadeiras quando a integralidade do conjunto autorizado estiver explicitamente garantida e o volume for adequado. Não basta adicionar controles visuais para prometer operações globais em qualquer outra coleção.

Para listas principais com potencial de crescimento, recomenda-se contrato servidor opcional, por exemplo `paginado=1`, preservando a resposta integral legada dos selects e demais consumidores. Confirmar a API ao implementar; usar whitelist de ordenação e tamanho de página, total filtrado, desempate por ID, preservação de `Escopo` e capacidades. Erro não deve aparecer como lista vazia. Esta opção é uma direção de planejamento, não uma mudança já realizada ou autorização para ampliar dados retornados.

Clientes, catálogo, cópias e outras listas já paginadas mantêm seu contrato existente. Não filtrar apenas as linhas DOM da página atual com rótulo de busca global; se houver refinamento deliberadamente local, identificá-lo como local. Paginação, busca, ordenação e seleção não podem ampliar o escopo autorizado nem enviar dados de outro contexto ao navegador.

Preservar `_ctx`, IDs de vínculo, lado da operação, situação, versões de concorrência, CSRF, métodos HTTP, validações e autorização novamente no servidor. Não converter mutações POST em links GET. Seleções entre páginas exigem decisão explícita de produto/contrato, não persistência silenciosa em JavaScript.

## Plano interno TBL1

Estas subdivisões são organização do trabalho e não exigem pedidos de autorização entre si. Revisar planejamento antes de implementação; apresentar tabela atualizada ao concluir a etapa. Não misturar este pacote com o fechamento CI1-F/catálogo.

| Parte | Entrega | Critério de conclusão |
|---|---|---|
| TBL1-A | Contrato/componentes comuns e listas provas, alunos, turmas, equipe | Cabeçalho/filtros/total/paginação honestos; cards preservam campos, vínculos, Copiar e demais ações; permissões conferidas |
| TBL1-B | Clientes, catálogo, pedidos/listas equivalentes e seleção em lote | Mesmas regras de contexto e versões; seleção/alcance explícitos; nenhuma perda de botões ou ações entre páginas |
| TBL1-C | Relatórios, matrizes, correção e impressão | Relações comparativas e ações por resultado preservadas; leitura mobile e impressão A4 verificadas |
| TBL1-D | Painéis, assinatura/financeiro, conclusão da convergência e revisão geral | Todas as 41 instâncias e listas equivalentes rastreadas; variações justificadas; documentação e evidências finais |

Validação por parte: desktop1440 e2560, menu aberto/recolhido, celular390 e textos extensos; vazio/erro e filtros ativos; acesso por teclado às ações; situações e perfis diferentes; regressões de impressão onde aplicável. Testes de código e capturas têm papéis distintos. Especialista independente inspeciona a implementação renderizada e registra aprovação ou bloqueios concretos antes de considerá-la concluída.

**Publicação:** todas as entregas vão primeiro à homologação isolada, após validação e revisão independente, respeitando as fronteiras de etapa acordadas. Subdivisões internas não são publicações automáticas. Produção permanece fora deste pacote e exige decisão explícita do responsável. Preservar os gabaritos e demais dados; não alterar permissões, contratos de transferência ou retenção como efeito colateral da padronização.


## Implementação TBL1-A–D e revisão final

As41instâncias inventariadas receberam o componenteBlade `x-tabela`, estilos compartilhados e rótulos de célula renderizados no servidor. A coluna principal é explícita; não se deduz o título pelo índice da primeira célula. Não há parser deHTML em execução. A mesma árvore contém campos, botões, formulários e condições no desktop e nos cards, sem duplicação de IDs, seleção ou autorizações. Captions existentes com totais são reaproveitados, mantendo um caption e scope por cabeçalho.

Contrato do componente: `titulo` identifica o conjunto, `modo` é lista/selecao/matriz e `completa` é falso por padrão. Modos seleção e matriz não aceitam paginação local mesmo que o chamador passe completa. Somente Provas,Alunos,Turmas e os grupos deEquipe declaram coleção integral, conforme os endpoints atuais que usam get() sobre o escopo filtrado, incluindoGestaoVinculoController. SemJavaScript, todas as linhas e ações continuam renderizadas. Não foi adicionada uma segunda busca local: os filtrosGET continuam no servidor.

Nessas quatro listas, o aprimoramento oferece20/50/100/Todos, alcance 'X–Y deN registros carregados' e ordenação nominalA–Z/Z–A, comIntl.Collator pt-BR, data-valor-ordem contendo apenas nome/título e desempate pela posição original. Alterar ordem/tamanho volta à página1; navegação mantém foco útil. Não se oferece ordenação de notas/datas sem contrato próprio nem se promete pesquisa global em página parcial. Clientes, catálogo e cópias mantêm os links/paginadores do servidor. O contrato opcional de paginação daAPI continua uma evolução futura, sem truncar consumidores legados nesta entrega.

Matrizes preservam relações e links por célula em quadro comparativo com rolagem própria, região identificada/focável, instrução de teclado e cabeçalhos completos. **O resumo alternativo em cards por aluno foi adiado**; não faz parte da conclusão desta entrega. A decisão conserva a comparação entre provas e evita uma segunda árvore divergente. Quatro matrizes usam esse modo; as outras listas recebem cards no celular. Dados secundários de apresentação, antes hide-sm, continuam acessíveis; dados não autorizados seguem ausentes por suas condições originais.

Erros HTTP nas quatro listas principais deixam de aparentar registros vazios ou totais zero. O estado vazio tem composição própria, sem o layout horizontal de rótulo/valor. Ações mantidas incluemPublicar,Copiar,Ver,Acompanhar,Relatório,Editar,Permissões,Suspender/Reativar,selecionar econtinuar, conforme os estados/perfis existentes. Nenhum endpoint de mutação, contrato de transferência, regra de retenção ou banco foi alterado.

Revisão independente product_design_review: **APROVADO para o conteúdo de aplicação desta entrega**, após inspeção das41adaptações e capturas representativas atuais. Corrigidos progresso fragmentado, rolagem da matriz, caption/scope duplicados, ordenação com iniciais/metadados, estilo que afetava cards ancestrais e vazio móvel fragmentado. Aprovação deUI não é certificaçãoWCAG nem validação deAPI real. As prévias usam fixtures fictícias declaradas; nenhuma pessoa, instituição ou pagamento real foi criado.

Validação local:138 testes web, 1.224 asserções; regressões novas verificam falha distinta de vazio,25alunos e ações disponíveis semJavaScript, escape, formuláriosPOST/CSRF e alvos de vínculo. NoChrome: desktop1440/2560 e390, ações e modalCopiar, teclado/foco, página2 com registros21–25, ordemdesc retornando àpágina1,Todos com25ações, visão deSecretário semdesempenho, seleção sempaginação local, filtro preservado no linkPróxima do servidor e quadromóvel semoverflow no documento. JavaScript desabilitado temporariamente:25ºregistro presente e controles progressivos ausentes; restaurado depois.

ImpressãoChromeA4 com3alunos e8disciplinas:2páginas renderizadas e inspecionadas,24notas preservadas, cabeçalho repetido, posições inteiras e nomes completos de disciplinas; sem controles da aplicação na impressão. Linhas ocultas apenas pela paginação local voltam a aparecer na impressão. PDF/capturas estão fora doGit, em operacao-20261102/tbl1-*. Capturas miniaturizadas iniciais foram descartadas como evidência; usar as finais legíveis.

| Parte | Situação ao preparar integração |
|---|---|
| TBL1-A | Componente e4listas implementados, testes eUI aprovados |
| TBL1-B | Listas paginadas/seleção adaptadas, ações/contexto preservados |
| TBL1-C | Relatórios/matrizes/impressão adaptados e revisados; resumo alternativo adiado |
| TBL1-D | Painéis/assinatura e convergência das41tabelas implementados e revisados |
| CI/integração/homologação | Executar no head final; conclusão depende do recibo de publicação |
| Produção | Sem publicação; decisão futura do responsável |


### Complemento de filtros e integração real

O parecer independente foi complementado após ajustar os estados sem resultados: `haFiltros` usa a whitelist de cada listagem e `filled`, incluindo `ativo=0`; o link Limpar utiliza a mesma condição. A orientação pede revisar/limpar filtros quando aplicados. Provas sem capacidade de criação recebem texto neutro. Regressão adicional cobre as quatro rotas e ativo=0 emAlunos/Turmas. Capturas desktop/mobile do estado filtrado foram novamente aprovadas pelo especialista; nenhuma outra composição foi alterada.

O pacote6e24d71 foi publicado com sucesso no clone9120, rede isolada, com backup e comparação acadêmica idêntica antes/depois da publicação. Depois, a web em desenvolvimento consultou aAPI real desse clone pelo transporteQGA, semHttp::fake. Criados somente no espaço fictício próprio: uma turma, três alunos, uma prova e cinco respostas do gabarito. NoChrome, Copiar criou um novo rascunho com cinco questões e zero turmas; a prova original manteve sua turma. Publicar alterou apenas a prova fictícia indicada; Ver abriu o aluno; o filtro local da turma preservou a tabela de provas. Outra identidade não acessou aluno/turma/provas do primeiro espaço (quatro respostas404). Comparação dos hashes de cada linha anterior nas nove tabelas confirmou os registros originais preservados mesmo com as inserções fictícias. A correção de orientação posterior foi conferida na mesmaAPI com ativo=0: três cadastrados, nenhum inativo, orientação e Limpar corretos.

Evidência agregada sem dados pessoais/tokens: docs/evidencias/tbl1-validacao-20261103.json. Imagens de prévia fictícia e de integração real têm identificações distintas. O transporte de ensaio tem custo adicional de chamadasSSH/QGA e não foi utilizado como benchmark de latência do produto. Nenhuma fixture ou conta de ensaio é enviada à homologação ou à produção. O arquivo operacional de servidor e aAPI permanecem idênticos ao pacote já ensaiado; o complemento muda somente estados/textos da web, testes e registros de validação.
