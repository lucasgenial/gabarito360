# Glossário

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento define os termos do domínio e os termos técnicos do Gabarito360. Cada conceito tem um único termo. Em caso de dúvida de interpretação, este glossário prevalece.

## 1. Termos do domínio

### 1.1 Estrutura da rede

| Termo | Definição |
|---|---|
| Rede | Conjunto de escolas sob a mesma administração. Exemplo: rede municipal. É o nível mais alto da hierarquia. |
| Núcleo | Subdivisão regional da rede. Supervisiona um grupo de escolas. |
| Escola | Unidade de ensino identificada pelo código INEP. Pertence a um núcleo ou é independente. |
| Anexo | Escola vinculada a uma sede. O cadastro marca a sede e o núcleo. |
| Turma | Grupo de alunos de uma série, numa escola e num período letivo. Exemplo: "9º Ano A". |
| Aluno | Estudante matriculado numa turma. |
| Matrícula | Vínculo do aluno com a escola e a turma, por período. Também nomeia o identificador do aluno. |
| Enturmação | Colocação do aluno numa turma, com intervalo de datas (FN-026). |
| Período letivo | Ano letivo de referência. Contextualiza provas, turmas e matrículas (FN-039). |
| Bimestre | Quarta parte do período letivo. Serve de filtro em relatórios. |
| Disciplina | Componente curricular da escola, por exemplo Matemática (FN-038). |
| Área de conhecimento | Agrupamento de disciplinas, por exemplo Linguagens. A relação com disciplina é N:N, com período de vigência (FN-038). |
| INEP | Instituto Nacional de Estudos e Pesquisas Educacionais Anísio Teixeira. O código INEP, de 8 dígitos, identifica cada escola. |
| Censo | Base pública do INEP. O cadastro de escola preenche os dados a partir do INEP (FN-031). |
| Catálogo nacional de instituições | Cadastro versionado de instituições, mantido pelo sistema e consultado no cadastro de escolas (FN-031). |

### 1.2 Clientes, espaços e pessoas

| Termo | Definição |
|---|---|
| Cliente | Organização ou pessoa que contrata o sistema: rede, escola independente ou professor autônomo (FN-021). |
| Espaço | Área de trabalho de um cliente. Tipos: rede, núcleo, escola e pessoal. Os dados de espaços diferentes não se misturam. |
| Espaço pessoal | Espaço de um professor autônomo. Só o proprietário o usa (FN-022). |
| Vínculo | Ligação de uma pessoa com um espaço, com perfil e período. Uma pessoa pode ter vários (FN-015). |
| Contexto | Vínculo ativo da sessão. Define perfil, espaço e escopo da pessoa naquele momento. |
| Perfil | Papel da pessoa no sistema. Define as permissões padrão. Lista de 15 perfis em [Perfis e jornadas](visao-geral/perfis-e-jornadas.md). |
| Permissão | Ação autorizada, nomeada por uma chave. Exemplo: `prova.ver`. |
| Permissão efetiva | Padrão do perfil, mais extras concedidas e válidas, menos padrões retirados (FN-012). |
| Escopo | Conjunto de dados que a pessoa alcança: rede, núcleo, escola ou turmas próprias. A permissão nunca amplia o escopo. |
| Membro | Pessoa da equipe de uma escola, com perfil operacional. |
| Acesso interno | Acesso da equipe do produto (Desenvolvedor, Suporte, Financeiro) em contexto separado (FN-013). |
| Exceção funcional | Autorização do Suporte para acumular grupos funcionais incompatíveis, com auditoria (FN-014). |
| Grupo funcional | Grupo de perfis: pedagógico (professor, coordenador, diretor, vice-diretor, aplicador) ou administrativo (secretário escolar, auxiliar). |
| Identidade pessoal | Registro único da pessoa, conciliado por CPF protegido por HMAC (FN-016). |
| Identidade provisória | Identidade criada sem CPF conciliado. Aguarda conciliação (FN-016). |
| Convite | Chamado para uma pessoa assumir um vínculo. O fluxo CPF-primeiro está pendente (FN-017). |
| Titular | Pessoa a quem os dados pessoais se referem. Pode pedir acesso, exportação ou correção (FN-064). |

### 1.3 Avaliação

| Termo | Definição |
|---|---|
| Prova | Instrumento de avaliação com questões de múltipla escolha, aplicado a uma ou mais turmas (FN-040). |
| Questão | Item da prova, com alternativas A a E. |
| Alternativa | Opção de resposta de uma questão. |
| Gabarito | Conjunto das respostas corretas de uma prova. |
| Gabarito oficial | Gabarito publicado. Não pode ser editado depois da publicação. |
| Questão anulada | Questão que conta como acerto para todos os alunos. |
| Autoria da prova | Registro de quem criou a prova e de onde ela veio (FN-041). |
| Meu espaço | Área do autor para as provas dele (FN-041). |
| Duplicação | Cópia de prova própria, sem turmas, cartões ou resultados (FN-042). |
| Cartão-resposta | Folha em que o aluno marca as respostas. Também chamado "cartão". |
| Cartão ambíguo | Cartão com marcação de baixa confiança ou com marcas múltiplas. Exige resolução manual. |
| OMR | Optical Mark Recognition: leitura óptica de marcas. Lê as marcações do cartão a partir de uma foto (FN-045). |
| Driver OMR | Componente que executa a leitura. Hoje existe o driver nativo. O driver externo é candidato (FN-048). |
| Confiança da leitura | Grau de certeza da leitura. Abaixo do limiar, a leitura vira ambígua. |
| Tentativa | Nova leitura de um mesmo cartão. O sistema preserva as leituras anteriores (FN-047). |
| Revisão de leitura | Conferência humana das leituras duvidosas (FN-046). |
| Resolução manual | Escolha, por pessoa autorizada, da alternativa de uma questão ambígua. |
| Fechamento da correção | Ato do coordenador que impede novos envios e exclusões de cartões. O coordenador pode reabrir. |
| Nota final | `(acertos / total de questões) × nota máxima`, considerando as questões anuladas. |
| Aprovado | Aluno com nota igual ou acima da nota mínima de aprovação. |
| Recuperação | Aluno com nota abaixo da nota mínima de aprovação. |
| Aluno em atenção | Aluno com desempenho abaixo do mínimo, listado nos painéis para acompanhamento. |
| Tendência | Variação do desempenho em relação ao período anterior: melhora, queda ou estável. |
| Documento verificável | Documento emitido com validação pública de autenticidade (FN-047). |
| TCT | Teoria Clássica dos Testes. Método de análise de resultados (FN-051). |
| TRI | Teoria de Resposta ao Item. Método de análise de resultados (FN-051). |
| GII | Acervo inteligente de itens. Candidato futuro (FN-044). |

### 1.4 Painéis, relatórios e comunicação

| Termo | Definição |
|---|---|
| Painel | Tela inicial personalizada por perfil, com indicadores, alertas e ações (FN-052). |
| KPI | Key Performance Indicator: indicador numérico exibido nos painéis. |
| Ação rápida | Atalho de painel para uma funcionalidade. |
| Editor de painéis | Recurso que permite à pessoa organizar seções e cards do painel (FN-054). |
| Card | Bloco visual de informação num painel ou em lista. |
| Relatório consolidado | Relatório de rede, núcleo ou escola (FN-049). |
| Visita pedagógica | Visita programada de um diretor de núcleo a uma escola (FN-058). |
| Cópia entre espaços | Mecanismo anterior de transferência de dados. Substituído por transferências institucionais (FN-024, FN-025). |
| Transferência institucional | Passagem de histórico entre escolas ou espaços, com aprovação e auditoria (FN-025). |
| Toast | Aviso flutuante e temporário após uma ação. |
| Stepper | Fluxo em etapas com indicador de progresso. |
| Skeleton | Esboço de carregamento exibido enquanto os dados chegam (FN-076). |

## 2. Termos técnicos

| Termo | Definição |
|---|---|
| API | Aplicação Laravel em `apps/api`. Serve JSON, aplica regras, permissões e OMR. |
| WEB | Aplicação Laravel em `apps/web`. Renderiza as telas com Blade e consome a API. Não acessa o banco. |
| Laravel | Framework PHP das duas aplicações. |
| MariaDB | Banco de dados relacional oficial. Só a API o acessa. |
| Sanctum | Pacote Laravel de autenticação por token, usado na API. |
| Fila | Mecanismo de tarefas em segundo plano (Redis). Executa leitura de cartões e outras tarefas pesadas (FN-002). |
| Job | Tarefa executada pela fila. Exemplo: processar um cartão. |
| Redis | Armazenamento em memória usado por fila e cache. |
| Migration | Arquivo que define a estrutura do banco de forma versionada. |
| Seed | Arquivo que popula o banco com dados iniciais ou de teste. |
| Eloquent | Mapeador objeto-relacional do Laravel. |
| Blade | Motor de templates das telas da WEB. |
| Tailwind | Biblioteca de estilos adotada na migração visual (FN-075). |
| ECharts | Biblioteca de gráficos prevista para a visualização de dados (FN-070). |
| Referência de navegação | Código opaco que a WEB usa no lugar de identificadores na URL. Não autoriza nada e viaja no corpo da requisição (FN-018). |
| HMAC | Código de autenticação por chave. Protege o CPF numa busca sem expor o número (FN-016). |
| Cifragem de dados | Proteção de campos sensíveis em repouso (FN-063). |
| Auditoria | Registro de requisições e eventos relevantes, com expurgo e consulta (FN-004). |
| Outbox | Tabela de eventos a publicar. Garante entrega confiável, por exemplo nas cópias entre espaços. |
| CI | Integração contínua. Roda testes e verificações a cada alteração (FN-008). |
| Homologação | Ambiente de validação, separado da produção (FN-003). |
| Produção | Ambiente de uso real. Recebe entregas só por decisão expressa do responsável. |
| Recibo de publicação | Registro de uma publicação: backup, dados preservados, arquivos intactos. |
| ADR | Architecture Decision Record: registro de uma decisão de arquitetura, em [decisões](decisoes/README.md). |
| FN | Identificador permanente de funcionalidade no [catálogo](funcionalidades/README.md), no formato FN-NNN. |
| Plano mestre | Documento que ordena a execução em ondas ([plano mestre](planejamento/00-plano-mestre.md)). |
| Gerente de projetos | Agente que controla a fila, as fases e as subfases. |
| LGPD | Lei Geral de Proteção de Dados (Lei 13.709/2018). |
| RIPD | Relatório de Impacto à Proteção de Dados. |
| RBAC | Controle de acesso baseado em perfis. No sistema atual, ele é complementado por permissões e escopo. |
| WCAG | Diretrizes de acessibilidade de conteúdo web. A meta é o nível AA. |
| SEGES | Sistema de gestão escolar da Secretaria. Origem de alunos e matrículas. A sincronização não está entregue. |
| MVP | Produto mínimo viável: o primeiro ciclo de avaliação. |
| Mockup | Desenho HTML de referência de uma tela, em `mockups/`. |
| Style system | Tokens e componentes visuais de referência, em `style-system/`. |

## 3. Siglas das séries antigas e o FN correspondente

Os documentos antigos (`docs/NN-*.md`) usam siglas de séries de trabalho. A tabela traduz cada sigla para a funcionalidade atual. A tradução completa está na coluna "Identificador antigo" do [catálogo](funcionalidades/README.md).

| Sigla | Significado | FN |
|---|---|---|
| MP-NNN | Micro-passo do plano de execução inicial | Varia. MP-001 a 005: FN-001. MP-012: FN-011. MP-013, 018, 026, 030, 031, 036: FN-012. MP-014 a 017, 035a, 039: FN-033. MP-016 (importação): FN-034. MP-019, 020, 037: FN-040. MP-021, 033, 038: FN-045. MP-022, 034: FN-049. MP-023: FN-010. MP-024: FN-019. MP-025 a 029: FN-030. MP-032: FN-058. MP-035b: FN-026. MP-041: FN-020. MP-006 a 011, 040: FN-052. |
| B1 a B4, B6 | Base de operação | FN-002 |
| B5 | Deploy único | FN-003 |
| H1 (docs 125 a 130) | Homologação isolada | FN-003 |
| H1 (docs 161, 162) | Identidade visual oficial | FN-071 |
| H1-P | Apresentação pública | FN-072 |
| CI do MariaDB | Integração contínua | FN-008 |
| AU1 | Auditoria | FN-004 |
| AU2 | Tela de consulta da auditoria | FN-004 |
| AU3, S1 | "Ver como" e atendimento | FN-005 |
| A1, A1-A, A1-B1, A1-B2 | Acessos internos | FN-013 |
| A1-C | Secretário escolar | FN-014 |
| PF1 | Perfis institucionais | FN-014 |
| A2 painel técnico | Painel técnico | FN-006 |
| A3, A4 | Estrutura escolar | FN-033 |
| A5 | Importar e exportar alunos | FN-034 |
| A6 | Responsáveis | FN-035 |
| A7 | Duplicação de provas | FN-042 |
| V1, V2 (vínculos) | Vínculos e seleção de contexto | FN-015 |
| V1 a V5 (base visual) | Identidade visual | FN-070 |
| VI1 | Convites por CPF | FN-017 |
| ID1 | Identidade pessoal | FN-016 |
| UR1 a UR4 | Segurança de URLs, sessão e dependências | FN-018 |
| IM1 | Imagem de perfil | FN-019 |
| T1, T2 | Clientes e espaços | FN-021 |
| T3 | Espaço pessoal | FN-022 |
| T4 | Cadastro de clientes | FN-023 |
| T5 | Cópias entre espaços | FN-024 (substituída por FN-025) |
| T6 | Encerramento de cliente | FN-028 |
| TR1, TE1, UX1 | Transferências institucionais | FN-025 |
| MT1 | Matrícula e enturmação | FN-026 |
| PA1 | Professor independente | FN-027 |
| EI1 | Escola independente | FN-029 |
| CI1 | Catálogo nacional de instituições | FN-031 |
| F5 (busca) | Busca por autocompletar | FN-032 |
| TU1, R1 a R6 | Organização da turma | FN-036 |
| TU-IMG, IMG-E, IMG-L, IMG-N, IMG-P | Imagens de turmas e educadores | FN-037 |
| DC1 | Disciplinas, áreas e currículo | FN-038 |
| PL1, P4 (painéis) | Períodos letivos | FN-039 |
| CP1, CP2 | Duplicação de provas | FN-042 |
| GII, P7.1 | Acervo inteligente | FN-044 |
| R1 a R3 (fotos e revisão) | Revisão de leituras | FN-046 |
| OMR1 a OMR3 | Qualidade, resultado e documentos | FN-047 |
| P8.1, TU1-PDF | Relatório PDF da turma | FN-050 |
| P7.2 a P7.4 | Análises avançadas | FN-051 |
| P0 a P3, P8.2 a P8.4 | Painéis de turmas | FN-053 |
| EI1 a EI6 (editor), FT1 a FT3 | Editor de painéis | FN-054 |
| P6 | Acompanhamento pedagógico | FN-055 |
| C1 a C3 | Agenda e calendário | FN-056 |
| C4 | Aplicação de provas | FN-059 |
| N1 a N4 | Notificações | FN-057 |
| E1 a E7 | E-mail transacional | FN-060 |
| E8 | Páginas públicas | FN-072 |
| L1 | Termos e privacidade | FN-061 |
| L2 | Inventário e retenção | FN-062 |
| L3 | Proteção dos dados | FN-063 |
| L4 | Pedidos dos titulares | FN-064 |
| L5 | Plano de incidentes | FN-065 |
| L6 | Avatares de alunos | FN-066 |
| RL1 a RL19 | Riscos de LGPD (docs/33) | FN-061 a FN-066 |
| P1 a P8 (portal) | Portal dos responsáveis | FN-067 |
| F1 a F3, FIN | Financeiro | FN-068 |
| R1 (selo) | Selo dos documentos | FN-069 |
| G1 a G3 | Gráficos | FN-070 |
| L0 a L8 | Layout responsivo | FN-073 |
| TBL1, TBL2 | Padrão de tabelas | FN-074 |
| TW1 a TW7, UX-D, UX-T, SMALL | Tailwind e UX transversal | FN-075 |
| UI1, UI1-R | Carregamento e estados de erro | FN-076 |
| F0 a F4, F6, PERF, FUND, X1, X2, ST1, AP1 | Desempenho e carga | FN-077 |
| X3 a X6, AP1-L | Limites, armazenamento e observabilidade | FN-007 |
| S1 | Suporte | FN-005 |
| RF-NNN | Requisito funcional da série 04 | Rastreado nos casos de uso das funcionalidades |
| RNF-NNN | Requisito não funcional da série 05 | Ver seção 4 |
| UC-NNN | Caso de uso | `casos-de-uso.md` da funcionalidade |

**Atenção:** P e R se repetem em famílias diferentes. "P1 a P8" é o portal dos responsáveis, no plano mestre antigo. "P0 a P8" com subitens é a série de painéis (docs 177 a 179). Leia sempre a coluna "Identificador antigo" do catálogo para resolver a ambiguidade.

## 4. Requisitos não funcionais (RNF)

Os requisitos não funcionais da série antiga vivem hoje nos documentos donos da arquitetura. Este glossário só aponta o dono.

| Antigo | Assunto | Dono atual |
|---|---|---|
| RNF-001 | Desempenho (OMR, API, carga) | [Desempenho](arquitetura/12-desempenho.md) e FN-077 |
| RNF-002 | Segurança (autenticação, autorização, LGPD, HTTPS, ataques comuns) | [Segurança](arquitetura/11-seguranca-e-privacidade.md) |
| RNF-003 | Disponibilidade, backup, integração SEGES | [Infraestrutura](arquitetura/13-infraestrutura-e-ambientes.md) e [operação](operacao/README.md) |
| RNF-004 | Usabilidade e acessibilidade, responsividade, tema | FN-070, FN-073, FN-075 |
| RNF-005 | Manutenibilidade e testes | [Governança](planejamento/01-governanca.md) |
| RNF-006 | Idioma pt-BR, datas DD/MM/AAAA, vírgula decimal | Regra vigente para toda a interface |
| RNF-007 | Banco, armazenamento de imagens, ambientes, logs | [Infraestrutura](arquitetura/13-infraestrutura-e-ambientes.md) |
| RNF-008 | Evolução futura | [Visão geral](visao-geral/README.md), seção 8 |

## Divergências encontradas

| Documento antigo | Afirmava | Situação atual |
|---|---|---|
| 17-glossario | Autenticação por JWT. | O código usa Sanctum. |
| 17-glossario | Seis perfis, com códigos `ADMIN_REDE`, `DIR_NUCLEO` e semelhantes. | Os valores de perfil no código usam minúsculas (`admin_rede`, `dir_nucleo`). Existem 15 perfis. |
| 17-glossario | Termo "Membro" limitado a diretor, coordenador e professor. | Membro cobre qualquer perfil escolar cadastrável pela equipe. |
| 17-glossario | Matrícula no formato AAAA.NNNNN. | O formato é regra de cadastro. Confirme em [regras de negócio](arquitetura/README.md) antes de assumir. |
| 17-glossario | Termo "Gov.br DS" e "GovBar" como parte da interface. | Termos removidos. A interface usa identidade própria (FN-070). |
| 17-glossario | "MP" como unidade de trabalho vigente. | MP é histórico. A unidade vigente é a funcionalidade (FN). |
