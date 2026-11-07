# Painéis P1/P2 — direção prévia de UI/UX e Product Design

> Registro histórico da consulta inicial. As decisões posteriores em docs180 e docs177/179 prevalecem: tabela inicial com alternância Cards, exemplos identificados, GridStack com persistência por escopo e perfil, resumo fixo e dois widgets nominais independentes na seção estudantes. Os widgets atuais têm título + conteúdo útil, sem os textos auxiliares previstos inicialmente. Esta direção antiga não constitui aprovação visual do novo candidato.

07/11/2026. **APROVADA a direção abaixo para implementação incremental de P1/P2.** Não é aceite visual de telas futuras nem conclusão funcional de agenda, períodos, participação, habilidades ou motores avançados. Não editei app/Git nem operei navegador/servidor.

## Leitura e referência preservada

Li integralmente `Plano_Gabarito360_Paineis_Turmas.md` (544linhas) e as orientações anexadas em54602087/Texto colado.txt (271linhas), incluindo P0–P8 e o protocolo de continuidade/publicação. Usei AGENTS, doc27ECharts no commitc16ceca e os alvos atuais de turmas como base, além das capturas previamente inspecionadasTU1-R3/IMG-E. A correção de imagem na edição já integrada emPR209 **não é lacuna nova**; o snapshot de reconciliação176 precisa ser lido com seus sucessores. O TU1-PDF em trabalho continua separado: não sobrescrever seu gerador ou reativar questões para justificar outro painel.

Aplicam-se Product Design index/audit e critical-overrides, design-critique, design-system e frontend-design já consultadas nesta atuação. É evolução de um produto existente, não criação de site/template ou três alternativasImageGen. Esta consulta fornece escolhas implementáveis agora; o usuário já autorizou continuidade e publicação dos incrementos aprovados somente na homologação.

## Decisões para começar o código

1. Um catálogo de blocos/estados compartilhado, alimentado pela política e pela disponibilidade real, serve aos dois níveis de painel.
2. **Turmas** ganha coleção de cards como corpo principal pedagógico; secretaria/auxiliar mantêm lista operacional com x-tabela. Não duplicar o mesmo conjunto inteiro em cards e tabela simultaneamente: tabela fica em modo Detalhar/lista quando útil à operação/acessibilidade.
3. **Visão geral da turma** conserva seisKPIs, três análises e Alunos no recorte. Acrescenta modelos abaixo do funcional nos grupos definidos; não redesenha abas, imagem, ações, arquivamento ou transferência.
4. Modelos são componentes estáticos terminados, com **Em breve**, benefício e representação neutra. Não têm números/pessoas/eventos fictícios, carregamento perpétuo ou botões de operação simulada. São obrigatórios na entrega visual e não esperam o motor futuro.
5. Fonte inexistente não impede composição; fonte funcional existente não volta a modelo. Cada bloco tem marcos visual e funcional separados.

## P1 — contrato mínimo de apresentação e acesso

Registrar cada bloco com IDestável, título, finalidade, página/seção/ordem, capacidades de agregado/nomes/ação, estado de disponibilidade, origem/dto, filtros aplicáveis, unidade/base, exclusões, atualizado_em quando real, dependência e critério de ativação. Separar `visual_aprovado`, `funcional_validado`, `commit`, `publicacao` e `conferencia_ambiente`. O catálogo pode ser configuração/DTO do pacote; não precisa virar um motor de dashboards personalizável.

Permissão e disponibilidade são eixos distintos. **Sem permissão: o bloco e seus dados não são enviados.** Modelo autorizado: configuração estática segura, sem chamar serviço inexistente. Loading é estado transitório da consulta real, não um valor permanente retornado pelo cadastro. Falha em um grupo preserva os demais grupos já carregados. Independência de região não exige um endpoint porcard nem consultasN+1.

### Estados visuais compartilhados

| Estado | Conteúdo e interação |
|---|---|
| Modelo | Título + selo Em breve adjacente + benefício curto + forma neutra/ícone já existente. Ajuda Saiba mais pode explicar a futura fonte. Nenhuma escala, data, nome ou valor aparente. Sem chamadas/polling. |
| Carregando | Skeleton de região com duração limitada enquanto consulta real está pendente; aria-busy e mensagem discreta. Não retirar casca/menu nem redesenhar conteúdo estável já existente. |
| Sem registros | Texto específico do recorte; ajustar filtros ou ação real autorizada. Não usar Em breve. |
| Zero válido | Número0 com base/contexto; não mensagem de ausência nem erro. |
| Parcial | Valor útil + cobertura/exclusões/situação provisória. Nunca esconder basepequena ou tratar aplicação sem evidência como ausência. |
| Falha | Mensagem curta + Tentar novamente para a região; nenhumzero falso, outroconteúdo mantido e foco preservado na recuperação. |
| Funcional | Dados autorizados com base, recorte, unidade e ação verdadeira. Gráfico tem alternativa textual com mesmos valores. |

O card emmodelo não deve ser cinza totalmente apagado ou parecer botão desabilitado gigante. Mesma tipografia/superfície do funcional, selo neutro Em breve e corpo distinto de skeleton. O estado modelo deve ser compreensível também sem cor e por leitor de tela. Usar verbo explicativo Saiba mais; não Agendar/Salvar/Gerar análise quando nada acontece.

### Filtros e significados

Preservar ícone44px abrindo modal compacto e recorte sutil no contexto. Busca textual da coleção permanece próxima dos cards, com label e botão Filtrar. A contagem declara se representa todo o recorte ou apenas a página. Cancelar modal mantém recorte aplicado;422preserva rascunho e análise anterior. Atualização de professor/disciplinas nunca apaga escolhas inválidas silenciosamente.

- **Professor escolar**: só turmas/disciplinas autorizadas. Na turma, uma disciplina elegível aparece como texto fixo; várias permitem seleção no modal. Nenhuma elegível não vira acesso geral. Isso não substitui a política de provas por autoria/atribuição.
- **Gestão**: professor, disciplina e período dentro do alcance. **Professor autor** continua com seu nome e contrato enquanto representar autoria de criação. Responsável na aplicação, aplicador e docente atual são filtros diferentes; se não houver evidência temporal válida, mostrar modelo próprio **Responsabilidade docente · Em breve**, sem renomear o filtro antigo.
- **Datas**: intervalo livre atual permanece funcional. Período letivo aparece em linha/modelo separado **Períodos letivos · Em breve**, não em um select fictício contendo1ºbimestre por dedução de meses.
- **Multidisciplinar**: filtro atual mantém avaliação inteira. Desempenho específico de disciplina e mapa de habilidade continuam modelo sem relação item→disciplina/classificação válida.
- Mudança de vínculo conserva a regra atual: um contexto de sessão, abas antigas bloqueadas409, sem replay/recuperação de respostas de outro contexto. P1não redesenhaUR4.

## P2 — biblioteca curta e catálogo de posição

Reutilizar superfícies/card-pad, KPI, x-avatar-turma, ações/ícones, details e x-tabela. Ampliar **x-grafico/configuração compartilhada** de modo opt-in, incorporando as necessidades dos gráficosTU1-R3; não criar terceiro wrapper independente nem quebrar os relatórios existentes. ECharts local de versão fixa, inicialização quando visível/dimensionado, ResizeObserver, temas e reduced-motion. Nota/média ou andamento não viram roscas; situação categórica pode seguir a representação aprovada até convergir para barras quando pertinente. Não criar meta que não foi cadastrada.

Tamanhos de referência: KPIcompacto com altura pelo conteúdo; cardturma aproximadamente180–230px dependendo de ações/contexto; gráfico280–340px desktop/240–300px celular conforme labels; modelo160–220px conforme finalidade. São faixas para alinhar irmãos, não alturas fixas que cortam nomes ou geram vazio. Largura útil100%; margens/paddings compartilhados24/16. Grade analítica2colunas desktop,1coluna mobile. Coleção de turmas3colunas quando couber,2intermediárias,1em390/320; evitar cards comprimidos só para caber quatro.

### Catálogo implementável

| ID | Bloco | Local/posição e público | Primeira entrega e ativação |
|---|---|---|---|
| T-RESUMO | Resumo de turmas | Turmas, após contexto;4–6KPIs por capacidade | Só contagens cadastrais/indicadores com fonte real. Totais agregados ausentes ficam modelo, não soma da página. |
| T-COLECAO | Minhas turmas/Turmas | Turmas, região principal após busca; professor/gestão | Card com imagem/fallback, nome, série/turno/ano, disciplinas pertinentes e Ver turma. Reaproveitar escopo/listagem, preservar ordenação/paginação/ações. |
| T-COMPARACAO | Resultados entre turmas | Turmas, abaixo da coleção; pedagógico | Modelo se não houver agregado conciliado. Ao ativar, comparação descritiva com instrumento/base, nunca média simples das médias. |
| T-COBERTURA | Cobertura turma × disciplina | Turmas, faixa analítica; coordenação/gestão | Modelo neutro sem heatmap colorido fingindo diagnóstico. Ativar só com definição do tipo de cobertura e denominador; atribuição docente não equivale a cobertura de aprendizagem. |
| T-PRIORIDADES | Prioridades de acompanhamento | Turmas, abaixo da análise; perfilautorizado | Pendência real quando fonte adequada; demais prioridades emmodelo sem contadores fictícios. Não derivar atraso de falta de leitura. |
| T-AGENDA | Próximos compromissos | Turmas, faixa de planejamento; variante por perfil | ModeloC2/C3, lista cronológica neutra sem datas/eventos. Secretaria só variante administrativa. |
| T-DEVOLUTIVAS | Devolutivas | Turmas, ao lado/abaixo da agenda; pedagógico | ModeloC/N até obrigação e data registradas; sem0atrasos inventado. |
| U-RETRATO | Retrato da turma | Visão geral, primeira faixa; pedagógico | Reaproveitar seisKPIsTU1-R3; não voltar a versão anterior. |
| U-DISTRIBUICAO | Distribuição dos resultados | Visão geral, análise principal | Funcional existente, cincofaixas e N; preservar0/null/exclusões. |
| U-AVALIACOES | Resultados por avaliação | Visão geral, análise principal | Funcional existente média/mediana/N; sem curva de aprendizagem entre instrumentos. |
| U-SITUACOES | Situações registradas | Visão geral, análise principal | Funcional existente; não confundir recuperação registrada com diagnóstico. |
| U-ALUNOS | Alunos no recorte | Visão geral, posição do quarto bloco | Preservar Atenção/Dentro/Top5, nomes autorizados, bases, empates e expansão; não restaurar questões. |
| U-PARTICIPACAO | Participação nas aplicações | Visão geral, depois da análise; pedagógico | Modelo ligadoMT1/aplicação; não contar cartões/alunosatuais como frequência passada. |
| U-AGENDA | Agenda da turma | Visão geral, Planejamento e acompanhamento | ModeloC2/C3: Aplicações, devolutivas e compromissos deste período aparecerão aqui. Sem fluxo de agendar falso. |
| U-PERIODOS | Organização do período | Mesmo grupo, próximo da agenda | ModeloPL1/P4; preservar intervalo de datas funcional. |
| U-HABILIDADES | Habilidades em acompanhamento | Ensino e acompanhamento, abaixo do funcional | ModeloP6/GII; matriz neutra sem eixos de habilidades inventados. Cadastro manual validado pode ativar depois, sem esperar GII completo. |
| U-INTERVENCOES | Intervenções | Mesmo grupo; pedagógico autorizado | ModeloP6; indicação de recuperação continua funcional em U-ALUNOS, sem virar plano executado. |
| U-REAVALIACOES | Reavaliações | Mesmo grupo, após intervenções | ModeloP6; não confundir nova leitura de foto com reaplicação. |
| U-CONSELHO | Conselho de classe | Síntese e decisões, gestão/pedagógico autorizado | ModeloP6, sem observações ou decisões fictícias. |
| U-METAS | Metas do período | Mesmo grupo | ModeloP6; sem meta75% ou progresso artificial. |
| U-INTEGRIDADE | Revisão de integridade | Acesso contextual em Provas/resultado autorizado, não placar na primeira faixa | ModeloP7e permissão específica; ausência de política não autoriza mostrar bloco. Não confundir integridade documentalPDF com motor de padrões. |
| A-RESUMO | Resumo administrativo | Turmas/admin, primeira faixa | Contagens cadastrais sustentadas (turmas/estudantes distintos) e identificação da base. Sem avaliações/notas ou equivalentes empayload. |
| A-LISTA | Turmas e estudantes | Admin, regiãooperacional principal | x-tabela integrada e ações PF1, sem substituir operaçãoemlote por gráfico. |
| A-MOVIMENTACOES | Movimentações cadastrais | Admin, abaixo da lista/resumo | Histórico atual quando fonte existir e autorizado; extensõesMT1 emmodelo próprio sem fingir matrícula temporal completa. |
| A-DOCUMENTOS | Documentos/solicitações | Admin, faixa de ações | Modelo administrativo se módulo inexistente; nenhumcontador pedagógico indireto. |
| A-AGENDA | Agenda administrativa | Admin, planejamento | ModeloC, conteúdo exclusivamente logístico/cadastral autorizado. |

Os IDs são identificadores de componentes, não novos pacotes de negócio ou perfis. Reutilizar C/N/MT1/DC1/PF1/P6/P7 como dependências. Subcomponentes futuros avançados só aparecem ao perfil com autorização definida; falta de decisão de permissão não permite enviá-los ao cliente.

## Composição por perfil, sem criar sistemas paralelos

**Turmas pedagógico**

```text
Meu espaço / breadcrumb / título / ações existentes
Resumo4–6 + busca/ordenação e filtroícone
Coleção de cards de turmas com paginação verdadeira
Comparação descritiva | Cobertura (modelo quando necessário)
Prioridades pertinentes
Agenda | Devolutivas/intervenções (modelos)
```

Professorautônomo prioriza seu espaço, turmas/pendências e agenda; professorinstitucional prioriza suas disciplinas/compromissos e não recebe total da escola não autorizado. Coordenação prioriza cobertura/prioridades/ciclo; direção panorama cadastral por etapa/turno quando fonte real e síntese; vice organização/compromissos e providências dentroPF1. Equipe pedagógica usa política existente mapeada à coordenação/concessões: **não criar perfil novo a partir de um rótulo textual**. Demandas atribuídas/retornos futuros ficam modelo até sua fonte. Não duplicar seis telas comHTMLdivergente: mesma grade/catálogo com ordens e permissões explícitas.

**Turma individual**

```text
Identidade/ações/imagem e cincoabas preservadas
Visão geral: seisKPIs → trêsanálises + Alunos no recorte
Planejamento e acompanhamento: participação/agenda/períodos
Ensino e acompanhamento: habilidades/intervenções/reavaliação
Síntese e decisões: conselho/metas (quando perfil autoriza)
Acompanhamento operacional real existente
```

Modelos não tomam o lugar dos dados reais nem são todos empilhados antes dosKPIs. Cada grupo tem título claro e seus cards efetivamente desenhados, não um link genérico Abrir planejamento que esconda toda a composição. No mobile, mesma ordem por prioridade, cards empilhados; contexto de modelo e Em breve sempre visíveis sem precisar abrir ajuda. Detalhamento pode usar modal/aba existente, mantendo seleção e retorno.

**Administrativo**: cabeçalho/contexto → resumo cadastral → busca/filtros/lista → ações/movimentações/documentos → agenda administrativa. Secretaria e auxiliar recebem variantes pelas capacidades atuais, não apenas um if de nome de perfil. Não enviar provas, média, resultados, participação, recuperação, ranking, integridade ou modelos pedagógicos, inclusive busca/agenda/exportação. Nenhum modelo Não autorizado com valor omitido deve denunciar população protegida.

## Sequência de implementação elegível agora

1. **P1.1–P1.4**: registrar catálogo/estados/fontes/filtros/ACL mínimo. O que já é fonteTU1-R3 continua funcional; responsabilidade histórica e período têm dependência explícita. Não aguardar reconciliação perfeita de todoP4–P7.
2. **P2.1**: componentes do bloco/modelo e variantes, mais exemplos de todos os estados emQA. Texto essencialSSR;JSexterno e dadosescapados. Não migrar agenda ou criarendpointnovo só para renderizar modelo.
3. **P2.2**: Turmas professor/gestão emcards e administrativoemlista, mantendo ações/filtros/escopo. Os totais disponíveis podem ser reais; modelos não exigem agregadofuncionalP3.
4. **P2.3–P2.4**: acrescentar gruposmodelos ao individual preservando _painel/IMG-E; preparar variantes de perfil semreabriroperaçõesTU1.
5. **P2.5/P8**: revisãoindependente da implementação atual (desktopamplo/recolhido/390/320/temas, estados e perfis);CI, publicação do incremento coerente emhomologação e conferência registrada. Depois seguirP3e tarefasindependentes automaticamente.

Não sobrescrever arquivos concorrentesTU1-PDF/IMG. Preferir partials e extensão compartilhada pequena, integrar o resultado já existente. Aprovação desta direção permite iniciar a implementação do pacote; não pede nova autorização humana para suas subdivisões.

## Critérios finais de P1/P2

- Todos os blocos autorizados têm identidade/posição/ação/estado e dependência, marcos visual/funcional separados.
- Modelo não tem número/pessoa/evento/escala fictícios, polling, skeleton permanente ou operação enganosa; selo Em breve adjacente ao título.
- API/HTML administrativo sem qualquer dadopedagógico; perfil com agregado mas sem nomes não recebe lista nominativa nem modeloque reveleidentidades.
- Fontesfuncionais existentes continuam úteis. Vazio/zero/parcial/erro são visual e semanticamente distintos; falha regional não apaga resultado de outraregião.
- Busca/filtro/ordenação/paginação mantêm contexto e significado; conjunto parcial não vira totalglobal. Umadisciplina fixano professor, múltiplas selecionáveis apenas noalcance.
- ECharts compartilhado, legenda/tabela equivalente, nomeslongos e matriz com recorte legível, teclado/foco/Esc/reducedmotion. Nada escondido pelo rodapé ou navmobile.
- Documentos reais excluem os modelos; nenhuma exportação de desenho ilustrativo como evidênciapedagógica. TU1-PDF mantém seu gatepróprio e recebe apenas dadosfuncionais autorizados.
- Registrar commit/CI/parecer/publicação/conferência; loginindisponível impede alegar testeautenticado, não impede planejamento nemtrabalhoQAindependente. Produção permanecefora.

**Limite do parecer:** leitura de documentos e fontes/superfícies existentes; não foi inspecionada uma implementaçãoP1/P2 nova, ainda não existente nesta consulta. O próximo passo é código incremental e evidência, não outro diagnóstico indefinido.
