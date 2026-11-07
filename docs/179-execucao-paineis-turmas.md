# 179 — Execução dos painéis de turmas

07/11/2026. Plano canônico deste escopo: [177](177-plano-paineis-turmas.md). Protocolo autorizado: [178](178-protocolo-execucao-paineis.md). Os identificadores P0–P8 são locais ao doc177; não renumeram os pacotes de portal do doc28.

**Checkpoint vigente:** a correção posterior [180](180-correcao-dashboards-diretriz.md) tem precedência nos pontos conflitantes de composição, tabela inicial e demonstrações preenchidas. Após cancelamento intermediário, a decisão humana mais recente retoma a personalização com GridStack.js e salvamento somente em área grande (corte operacional inicial 1024×700 CSS). Em telas menores, composição fixa sem controles nem sobrescrita do desktop. Saiba mais cadastral e Ver os números continuam removidos. A decisão ainda mais recente requer adaptador de linhas: 12 colunas preenchidas, mesma altura pelo maior widget, resize reorganiza a linha e drag cria/remove linhas; gerenciamento sem modal, substituindo a regra histórica de vizinhos imóveis. Main de partida `0e6e906` (PR210), homologação anterior `b005425`. O quadro inicial e o relato PR210 abaixo são históricos; a situação atual da correção está na seção final deste documento. A revisão visual do HEADc772 foi aprovada e permanece histórica. Pedido posterior do responsável reabriu a composição em P2.6/P2.8; essa aprovação não vale para o próximo candidato, que ainda não tem novo fechamento visual, publicação ou conferência autenticada declarados.

## Execução autônoma

Executar tarefas elegíveis sem pedir autorização para iniciar, continuar, fazer Git, integrar versões aprovadas ou publicar incrementos coerentes na homologação. Produção permanece excluída. Revisão especializada prévia e aprovação independente da versão final são obrigatórias; implementador não aprova sozinho. Falta de acesso ou decisão bloqueia apenas a dependência pertinente. Não reduzir testes, ampliar permissões, falsificar evidências ou apresentar demonstrações como dados reais para obter aprovação. Exemplos visuais identificados estão autorizados pelo doc180.

Registrar visual, funcional, testes, revisão, commit/PR, publicação e conferência autenticada separadamente. Modelo “Em breve” concluído visualmente não conclui o módulo. Não usar skeleton permanente, números/pessoas fictícias como reais nem chamadas para endpoints inexistentes. Preservar as entregas atuais e todos os itens do quadro176, inclusive fora destes painéis.

## P0 — referência inicial e trabalhos preservados (histórico anterior ao PR210)

- Main remoto conferido: `c16ceca5342b2313141d690a862ef488400b02bf` (PR209). Worktree novo `paineis-turmas`, branch `pacote/paineis-p0-p1`, sem carregar alterações de outras tarefas.
- Último recibo conhecido da homologação: `adb9698`, árvore do PR209; nova publicação exige reconfirmar o destino e sua release. Nenhuma evidência de produção é deduzida do merge.
- TU1-PDF permanece em `tu1-pdf-relatorio`, branch `feat/tu1-pdf-relatorio`, HEAD `b5cd6e0`, com teste local modificado. P8.1 reaproveita esse trabalho; não copiar o gerador antigo sobre ele.
- Imagem na edição IMG-E integrada no PR209; imagem no detalhe/perfil PR208. IMG-N/L/P continuam distintos. P3.1.3 amplia IMG-L; cadastro de turma e professor não são silenciosamente concluídos por isso.
- Agentes consultados: Product Design, indicadores e autorização. Pareceres são evidências específicas, não aprovação automática dos futuros incrementos.

## Matriz reconciliada

| Origem | Existente/lacuna | Tratamento e destino | Dependência e conclusão |
|---|---|---|---|
| TU1/175/R3 | Abas, seis KPIs, três análises e grupos nominais | Reaproveitar P2.3/P3.2 | Mesmo recorte/base/permissão; preservar ações e imagem209 |
| Listagem Turmas | Tabela; média bruta e KPIs fora do filtro | Ampliar P2.2/P3.1/P3.3 | Contagens com universo declarado; não usar média bruta como ganho |
| PF1/134/135 | Permissões/contexto existentes; rota mista precisa revisão por conteúdo | Ampliar P1.3/P8.2 | Negação administrativa e retirada de permissão testadas no servidor |
| DC1/148 | Áreas N:N e lotação temporal | Reaproveitar P1.2; desdobrar professor autor/responsável | Não renomear filtro legado sem contrato histórico novo |
| UI1/174/UI1-R/AP1 | Casca e estados regionais; cobertura parcial | Ampliar P3.4 | Medição antes/depois e falha regional; AP1-L mantém item próprio |
| C1–C4/N1–N3/doc28 | Agenda/notificações não operacionais | Ampliar P5, visual P2 | Modelos sem endpoints até fonte; público administrativo separado |
| MT1/doc115 | Transferência intraescola; intervalos/elegibilidade incompletos | Ampliar P1.4.6/P3.3.5/P4.4 | Captura histórica de novos conjuntos; não inferir presença de cartão |
| Período letivo | Não localizado como módulo completo; vínculo DC1 não é bimestre | Criar especificação P4; verificar reserva PL1 antes de adotá-la | Calendário por turma, histórico e datas sem conversão presumida |
| TU1 acompanhamento | Recuperação/Top5 não implementam intervenções | Ampliar P6 em subplano ligado TU1 | Evidência, ação, responsável e retorno; revisão humana |
| GII/editor/TRI/integridade | Reservas de produto, motores não comprovados | Desdobrar apenas interfaces/contratos P7 | Não construir motores implicitamente nem alegar equivalência de provas |
| TU1-PDF | WIP já preservado e não publicado | Reaproveitar/ampliar P8.1 | Mesmos dados, todas linhas, retrato/paisagem, sem modelos no PDF |
| EI1/163 | Escola autônoma planejada | Reaproveitar dependência condicional | Não exigir EI1 para painéis de espaços já autorizados |

Nenhum item anterior é apagado ou substituído nesta reconciliação. P0 é uma base executável, ampliada pontualmente ao encontrar fontes adicionais.

## Achados que afetam o primeiro incremento

1. Média da listagem usa `AVG(nota_final)` e apresentação em escala fixa; não equivale ao percentual válido do TU1-R3. Não ativar comparação agregada com essa fonte.
2. Totais `kpis` da listagem não recebem todos os filtros. Usar somente contagens cuja coleção/universo esteja explícito; estudantes únicos não são soma de matrículas.
3. Projeção administrativa existente não basta: usuário não administrativo com permissão pedagógica retirada precisa ter os dados correspondentes omitidos também na API. Primeiro incremento de P1 tratará essa revisão.

## Quadro de continuidade inicial (histórico anterior ao PR210)

| Fase | Visual | Funcional | Revisão/publicação | Próximo critério |
|---|---|---|---|---|
| P0 | Não aplicável | Base e conciliação registradas | Em revisão independente | Relacionar pareceres e preservar fontes |
| P1 | Catálogo em preparação | Projeção por conteúdo em implementação | Não publicado | Testes de permissão e contratos compatíveis |
| P2 | Préconsulta solicitada | Modelos não equivalem a função | Não implementado/publicado | Composições por perfil e estados revisados |
| P3 | Reaproveita TU1-R3 | Dados atuais parciais | R3 já publicado; novas integrações pendentes | Ativar somente fontes válidas |
| P4 | Modelos previstos em P2 | Pendente | Não publicado | Calendário, períodos, elegibilidade/presença |
| P5 | Modelos previstos em P2 | C/N pendentes | Não publicado | Eventos e destinatários corretos |
| P6 | Modelos previstos em P2 | Pendente | Não publicado | Habilidades/intervenções com evidências |
| P7 | Modelos previstos em P2 | Integrações futuras | Não publicado | Aceite individual de cada integração |
| P8 | PDF WIP preservado | Verificação transversal em curso | Conferência autenticada pendente | Validar cada incremento e publicar só homologação |

Checkpoint: próximo trabalho elegível é concluir a projeção P1 com testes negativos e revisão independente, junto ao catálogo para P2. Não há nova publicação autorizada em produção. Os itens externos do quadro176 permanecem pendentes.

## Primeiro incremento — PR210

Implementado em branch isolado e submetido no PR210. Projeção API por conteúdo/ação (sem novas rotas, migrações ou fórmulas), catálogo inicial de modelos estáticos e coleção pedagógica com tabela alternativa. Secretaria/auxiliar conservam tabela e modelos administrativos; retirada de permissão não se transforma em zero ou lista vazia na apresentação. Contagens cadastrais usam a coleção filtrada completa; valores ausentes/inválidos não viram zero. A média bruta legada não alimenta os novos cards.

Ordenação/paginação continuam no modo explicitamente chamado “Tabela com ordenação e paginação”. A coleção apresenta todos os registros do recorte e não herda a página/ordenação local da tabela. Esse comportamento é uma entrega inicial, não o fechamento de P2.2. Disciplinas, resumos agregados, prioridade por perfil, demais modelos e estados regionais continuam elegíveis para os incrementos seguintes.

Validação local: Web completo **355 testes/12.900 asserções**; API novos7/83, regressões62/833, revisão independente com repetição7/83. Verificação de telas26scripts/0erros. Chrome local fictício: desktop,390 e320, tema escuro/claro, secretaria, alternância tabela/coleção,25registros e paginação/ordenação. Não se trata de login ou conferência autenticada do servidor.

Pareceres em `docs/evidencias/paineis/`. A aprovação visual é restrita ao primeiro incremento; não declara P1/P2 completos. CI, merge, publicação e conferência do novo incremento ainda pendentes neste checkpoint. O TU1-PDF continua separado e não foi incluído nesta versão.

Próximo trabalho elegível: satisfazer gates finais/publicar este incremento somente na homologação e prosseguir P1/P2 (catálogo completo, identidade/posição dos componentes, perfis/filtros e composições), mantendo P8 a cada entrega. P4/P5/P6 e motores P7 não estão funcionais por causa dos modelos.

## Correção vigente — doc180 e P2.6–P2.13

O PR210 foi integrado em main `0e6e906`; a release anterior de homologação é `b005425`. Essa publicação não contém a correção atual completa. O relato anterior não será utilizado como aprovação automática da nova interface. A execução atual preserva a projeção por conteúdo, histórico TU1-R3, imagem IMG-E/IMG-L e o trabalho separado TU1-PDF.

### Conciliação das decisões

| Necessidade | Origem e tratamento | Implementação que deve permanecer | Correção e critério |
|---|---|---|---|
| Dois dashboards com conteúdo próprio | Ampliar P2.2/P2.3 em P2.6 | KPIs, gráficos, grupos e operações autorizadas da Turma | Agregado de Turmas não é coleção de cards grandes nem cópia da tela individual; análises e fontes próprias |
| Relação operacional em tabela | Substituir a preferência inicial por cards de P2.2.2, com histórico, em P2.7 | x-tabela, filtros, ordenação, paginação, ações, imagens | Tabela padrão visível; Cards compactos alternativos compartilham registros e estado; celular não força alternativa |
| Modelos visuais preenchidos | Substituir modelo neutro/ícone-frase inicial em P2.8 | IDs, autorização, seções e separação dos módulos | Em breve + Dados demonstrativos e composição completa; exemplos não simulam filtro nem entram em documentos reais |
| Movimento/troca/resize | P2.9/P2.10 retomadas com GridStack após cancelamento intermediário | Marca, gráficos compartilhados, estados e autorização | Somente área grande; GridStack/adaptador de linhas cheias e alturas iguais, resize com reflow na linha, drag entre linhas e remoção de vazias; abaixo do corte, apresentação fixa |
| Preferência pessoal | P2.11 retomada por decisão humana mais recente | Sessão/vínculo e proteção de abas vigentes, sem nova preferência | Restaurar API/BFF/migration já examinados e revisar deltas; mapa desktop não pode ser sobrescrito por telas menores; DDL ainda não executado |
| Agenda, períodos, notificações, matrícula | Reaproveitar C/N/doc28, MT1/doc115 e P4/P5 | Seus identificadores e históricos próprios | Modelos não implementam módulos; [necessario ver atender recurso] fontes reais C2/C3/C4/N1–N3/P4/MT1 antes de ativação |
| Intervenções, habilidades, metas, conselho | Ampliar TU1/P6 | Recuperação e grupos atuais não viram diagnóstico | [necessario ver atender recurso] ação, fonte curricular, prazo, autorização e retorno; demonstração não fecha P6 |
| Comparação entre turmas | Reaproveitar P3.3 e parecer de indicadores | Média legada não é nova fonte de comparação | [necessario ver atender recurso] instrumentos/bases comparáveis; nesta correção, desempenho agregado permanece demonstrativo |

### Fontes reais e exemplos nesta correção

- **Fontes atuais preservadas:** coleção cadastral completa autorizada de turmas; quatro resumos cadastrais (turmas no recorte, em uso, arquivadas e cadastros ativos nas turmas), sem afirmar pessoas únicas; distribuição cadastral de turmas por turno; dados TU1-R3 da turma no recorte e permissões atuais.
- **Exemplos isolados:** comparação entre turmas, cobertura por disciplina, participação, responsabilidade na aplicação, períodos, agendas, devolutivas, habilidades, prioridades/intervenções, reavaliações, metas, conselho e documentos administrativos. Factory estática conhecida por ID, dados coerentes de 2026, sem DB/sessão/serviços, gravar registros, emitir avisos ou incorporar nomes selecionados.
- **Não ativado por ter exemplo:** agenda C/N, período escolar P4, elegibilidade MT1, análise por habilidade e acompanhamento P6, motores P7. Falha, zero ou vazio de componente funcional nunca é substituído pelo exemplo. Secretaria recebe somente exemplos administrativos permitidos.
- **Steering posterior — Calendar.js:** o responsável determinou seu uso real nos três widgets de agenda. Adaptador demonstrativo local/somente leitura em implementação pelo agente de Design, com eventos fictícios separados. A alteração reabre a revisão visual específica das agendas; não constitui aprovação nem publicação. Integração operacional permanece em C2/C3/P5 e avisos em C4/N1–N3, sem outro módulo paralelo. [necessario ver atender recurso] fonte real, permissão de eventos e destinatários antes da ativação.
- **Steering posterior — gráficos sem rolagem:** os gráficos precisam preencher e ajustar-se à área útil do card, sem scroller vertical/horizontal, cortes ou perda de legibilidade. Composição fixa em telas menores e resize somente em área grande devem garantir espaço legível por conteúdo/dispositivo. Overflow automático e mero corte não aprovam o gráfico. A revisão visual final da composição GridStack e da alternativa fixa permanece pendente.

### Subdivisões e situação verificável

| Item | Implementação | Modelo visual / integração funcional | Revisão | Publicação/conferência |
|---|---|---|---|---|
| P0/P1 — conciliação e contratos | Base atual relacionada; projeção por conteúdo já integrada no PR210; preferências retomadas; adaptador GridStack em prova de conceito | Preserva capacidade e bases; refinamentos de filtros históricos ainda parciais | Revisão dos novos deltas separada do PR210 | Base PR210 publicada; correção nova pendente |
| P2.6 — duas composições | Em implementação na branch da correção | Reaproveita TU1 e fontes cadastrais; composição visual não concluída | Revisão visual independente pendente | Pendente |
| P2.7 — Tabela/Cards | Em implementação, com mesma coleção/estado | Alternância local sem persistência em validação | Evidências efetivas desktop/celular pendentes | Pendente |
| P2.8 — modelos preenchidos | Factory estática implementada, renderização em integração | Demonstrações não são integração funcional dos módulos | Unit da factory 6 testes/1.138 asserções PASS; revisão visual pendente | Pendente |
| P2.9 — movimento e troca | RETOMADA com GridStack; prova de conceito em curso | Área grande somente; linhas completas, reflow e altura uniforme por linha; gerenciamento sem modal | Nova revisão independente da integração pendente; ensaios antigos não aprovam GridStack | Pendente |
| P2.10 — resize | RETOMADA na área grande | Telas menores fixas sem controles; gráficos legíveis e sem rolagem em ambos | Nova revisão visual independente pendente | Pendente |
| P2.11 — preferências | RETOMADA; restauração em curso | Cliente usa mapa desktop na área grande, sem overwrite por telas menores | API f02c337/15a04de: 15 testes/266 asserções SQLite PASS históricos; delta e consumidor atuais requerem revisão | Migration ainda não executada; publicação pendente |
| P2.12/P8 — qualidade | Testes e revisões em curso por delta | Modelo visual concluído só após evidência atual aprovada | CI/visual/funcional atuais pendentes de fechamento | Conferência autenticada permanece separada |
| P2.13/P8.4 — publicação | Ainda não executada para a correção GridStack | Publicar somente candidato aprovado; preservar dados/arquivos; migration retomada exige gate do operador | Backup, operador, gates e pareceres atuais exigidos | Homologação pendente; produção fora do escopo |
| P3.3/P4/P5/P6/P7 | Fontes ou módulos permanecem parciais/pendentes | Integração funcional pendente mesmo com modelo aprovado no futuro | Cada ativação terá revisão própria | Não declarar concluídos pela correção visual |
| P8.1 — TU1-PDF | WIP preservado na worktree própria | Não substituir gerador refinado por baseline nem incluir exemplos em relatório real | Layout final/testes/parecer próprios pendentes | Nova diagramação não publicada |

Os números de testes acima se referem aos deltas isolados que os produziram, não a uma execução completa do candidato conjunto. A migration de preferências e os endpoints foram retomados após cancelamento intermediário. A revisão anterior não autoriza execução sem revalidar o candidato atual, integração GridStack, controles de área e gates; nenhuma execução no servidor foi declarada. Os pareceres operacionais locais devem ser incorporados como evidências versionadas quando a versão integrada estiver pronta.

**Próximo trabalho elegível:** concluir a prova de conceito GridStack com adaptador de linhas completas/altura uniforme, reflow e criação/remoção de linhas sem colisão e sem modal de gerenciamento, integrar preferências da área grande e alternativa fixa de telas menores, alternância local e modelos; executar conferência desktop/celular e obter revisão visual independente da versão atual. Remover Saiba mais cadastral e Ver os números, preservando alternativas acessíveis. Em seguida cumprir CI e revisão do operador, integrar e publicar exclusivamente na homologação; conferir ambos os dashboards e atualizar o quadro. Falta de login autenticado bloqueia essa evidência, não autoriza inventá-la nem interrompe as tarefas independentes. Todas as pendências externas do documento176 continuam preservadas.


### Cronologia das decisões de 07/11/2026

1. Doc180 autorizou personalização manual com salvamento e restrições de geometria.
2. Pedido intermediário cancelou ajustar/salvar layout e removeu Saiba mais cadastral/Ver os números; a retirada começou, sem deploy declarado.
3. Pedido mais recente retomou personalização com **GridStack.js e salvamento**, apenas desktop/tablet grande. Corte operacional inicial 1024×700 CSS aproxima área disponível e não polegadas físicas; pode receber ajuste posterior do responsável. Em telas menores, composição fixa, sem controles e sem overwrite do desktop. A remoção de Saiba mais cadastral/Ver os números continua vigente. A nova integração não está aprovada por esta cronologia.

4. Decisão mais recente redefine o algoritmo: resize reorganiza membros da mesma linha; cada linha preenche 12 colunas sem gaps; altura é o máximo do maior widget e igual para todos; drag admite linha anterior/posterior ou nova após a última, destruindo linhas vazias. Nenhum modal de gerenciamento: Salvar e confirmação inline de restauração, com alças acessíveis ao teclado. O pedido substitui expressamente os antigos gates de vizinhos imóveis/sem compactação; esses ensaios ficam históricos. Persistência exige geometria final válida, sem colisões e com conteúdo legível.


### Contrato técnico de altura individual

Campo opcional `altura_desejada` preserva demanda individual no JSON, separado de `h` efetivo uniforme da linha; exige inteiro e `minH <= altura_desejada <= h <= maxH`. Sem campo, cliente usa `h` como fallback e payload legado permanece sem reescrita. Sem coluna SQL adicional. Dois mapas continuam compatíveis; interface usa somente desktop e não consulta/grava preferências abaixo de1024×700 CSS. API/BFF não persistem conteúdo pedagógico ou HTML. Revisão independente do delta: API18 testes/304 asserções SQLite PASS; projector BFF2 testes/14 asserções PASS. Isso não aprova algoritmo de linhas, UI GridStack, migration em servidor ou publicação.


### Ampliação autorizada — infraestrutura genérica e escopo da turma

P1.5/P2.11.6 registram a nova decisão: preferências genéricas para dashboards atuais/futuros, sem duplicar módulos C/N/MT1. Chave efetiva por usuário+vínculo+espaço+perfil+painel e **turma quando individual**, autorizada no servidor antes de toda operação/recibo. Mapa pessoal válido do escopo prevalece sobre padrão do perfil; sem ele, usa-se padrão. Registro de novos painéis e padrões no servidor, catálogo autoritativo e permissão atual sempre prevalecem sobre IDs salvos. Usuário somente reordena/redimensiona, não adiciona, exclui ou duplica widgets.

Situação: ampliação planejada e implementação/revisão do delta em curso; não coberta automaticamente pelo parecer técnico anterior de altura individual. Testes próprios devem cobrir Turma A/B, acesso revogado, padrão por perfil versus mapa pessoal, composição completa e idempotência por escopo. Nenhuma publicação ou migration é declarada executada até nova ordem e gates atuais.


### Decisão posterior — resumo de KPIs fixo

P2.6.5: o resumo/KPIs das visões Turmas e Turma mantém o padrão autorizado por perfil e não pode ser movido ou redimensionado; não implica CSS sticky. SSR renderiza o bloco fixo sem alças de ponteiro/teclado. Registry/catálogo de preferências e `padrao.desktop` contêm somente os widgets editáveis fora do resumo. API deve rejeitar com422 payload que inclua ID fixo de KPI/resumo. Revisões de UI e API precisam demonstrar essa separação; não presumir aceite pelo código anterior. Demais regras de escopo/padrão por perfil/mapa pessoal permanecem. Implementação e revisão do delta pendentes, sem publicação/migration declaradas.

### Parecer técnico do delta de escopo/padrões/resumo fixo

Revisão independente em07/11: API22 testes/404 asserções PASS21,14s no SQLite descartável próprio; projector BFF3 testes/20 asserções PASS. Extrator independente1 teste/233 asserções PASS1,92s gerou seis DTOs GET reais de fixtures fictícias (coordenador/professor/secretário, Turmas/Turma), validando catálogo sem resumo, mapas de padrão compatíveis, linhas12 e alturas uniformes. Parecer técnico aprovado do delta, com hashes; sem aprovação da integração GridStack ou publicação. API aceita mapa técnico parcial legado, mas consumidor deve manter todos os IDs autorizados renderizados e completar ausentes pelo padrão; omitir preferência não exclui componente. Os ensaios de UI/gestos, isolamento visual por turma, viewport pequeno sem GET/PUT e persistência real em homologação continuam gates separados. Nenhuma migration ou publicação foi executada por esse parecer.


### Reabertura posterior ao aceite c772 — foco e limpeza dos cards

A revisão independente de `c772722268c0df415ef669b35cfb96e80019284a` foi aprovada e seus arquivos/evidências permanecem como histórico exato daquela fonte. A nova mensagem humana reabre a entrega por razões de UX. O CI/candidato anterior foi superado por novo escopo legítimo; não significa falha retroativa ou autorização para o próximo HEAD. Não emitir nova aprovação visual antes da implementação, sincronização da QA e evidência atual. Nenhuma publicação ou migration é declarada executada por esta reabertura.

| Desdobramento interno | Nova direção | O que preservar | Situação atual |
|---|---|---|---|
| P2.6.6 — foco em turmas no agregado | Proposta em pré-consulta: resumo fixo → relação Tabela/Cards → análises/modelos → planejamento | Mesma coleção, permissões, busca, ordenação, paginação e ações | Consulta/implementação/revisão pendentes |
| P2.6.7 — foco em alunos no individual | Seção `estudantes` logo após resumo fixo; dois widgets separados: Destaques da turma e Alunos que precisam de atenção | Top5 e Atenção reais, bases/exclusões/empates e mesmos gates nominais, sem mudar DTO/cálculos/relatórios | Implementação e delta de catálogo em curso; revisão pendente |
| P2.6.7 — retirar grupo antigo | Substituir `alunos-recorte` por `alunos-destaques`/`alunos-atencao`; Dentro do critério permanece somente no contrato/relatórios, sem o seletor visual antigo | Autorização nominal: agregado + aluno + resultado; veto administrativo; nenhuma expansão de alcance | Catálogo/defaults novos, mínimo10/padrão12, preferências antigas precisam de reconciliação testada |
| P2.8.6 — cards limpos | Remover todos Saiba mais, subtítulos e parágrafos auxiliares/didáticos; título + dados | Eixos, unidades, valores, bases e identificação necessária | Consulta/implementação/revisão pendentes |
| P2.8.6/P2.8.7 — honestidade e estados | Selos Em breve/Dados demonstrativos compactos no cabeçalho | Feedback/erro/vazio/parcial necessários e alternativas acessíveis; exemplos nunca viram fonte real | Revisão visual própria pendente |
| P2.6.8/P2.12 — novo aceite | Revisar composição atual desktop/celular e perfis, IDs/gates/defaults e interações GridStack afetadas | Parecer c772 só histórico; CI e gates do novo HEAD separados | Não aprovado nem publicado por esta tabela |

São subdivisões internas de P2.6/P2.8, sem novo módulo e sem alteração do roster C/N/MT1. A próxima ação elegível é concluir a consulta/implementação e o novo delta técnico, executar testes pertinentes e só então realizar revisão visual independente na QA sincronizada. Publicação continua exclusivamente em homologação, após os gates atuais; produção permanece fora do escopo.

Direção prévia de Design para esse novo delta: dois cards nominais lado a lado na composição padrão desktop, empilhados no celular em retrato, sem abas/seletor de grupos. Gráficos reais e demonstrativos com mínimo8/padrão9/máximo20 após a limpeza; nominais10/12 e agendas12/14 permanecem. Catálogo e metadata de teste ainda recebem esse ajuste; a revisão técnica anterior22/421 não cobre automaticamente a mudança de mínimos. Legibilidade/ausência de rolagem precisam de nova evidência visual; não presumir aprovação por reduzir alturas no código.

Revisão técnica independente do delta nominal e dos limites finais: API22 testes/434 asserções SQLite PASS21,62s; suplemento próprio de preferência legada1/8 PASS1,73s; extrator seis DTOs1/243 PASS1,90s. Código dos três arquivos aprovado tecnicamente, sem expansão dos gates ou alteração de DTO de análise, dados, fórmulas, relatórios ou schema. GET filtra o ID retirado sem perder o restante/sobrescrever DB; API fornece o novo padrão. Contrato documental atualizado pela raiz e conferido pelo revisor. Composição, reconciliação visual e alturas8/9 continuam em nova revisão independente. Artefatos operacionais `paineis-estudantes-api-revisao-independente.md/json` e `paineis-estudantes-api-fixtures.json`; nenhuma publicação/migration por esse parecer.


Implementação da limpeza concluída em fonte: títulos e conteúdo útil; descrições/bases somente tooltip/ARIA/leitor de tela; selos demonstrativos compactos no cabeçalho; Tabela/Cards antes das análises; dois widgets nominais separados antes dos gráficos. A revisão de segurança encontrou serialização nominal desnecessária no atributo dos gráficos, corrigida por projeção exclusiva de distribuicao_acertos e por_avaliacao, sem alterar o contrato da API. Probe DOM independente passou1/3. O novo teste Web decodifica o atributo e verifica a projeção, além dos gates HTML. Suíte Web completa383 testes/14.603 asserções PASS38,76s; geometria Node8/8 PASS. Fonte candidata ainda sujeita aos cinco checks CI, pareceres atuais e publicação/verificação exclusiva na homologação. O aceite anterior não autoriza esta árvore automaticamente.
