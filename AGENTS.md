# Gabarito360

Sistema de gestão, aplicação, leitura e correção de cartões-resposta por foto.

## Objetivo
Criar uma plataforma com backend web, painel administrativo, app Android e módulo OMR para leitura automática de gabaritos.

## Stack desejada
- Backend: Laravel 12
- Banco: PostgreSQL
- Mobile: Flutter
- OMR: OpenCV
- Tempo real: Laravel Reverb/WebSockets
- Filas/cache: Redis
- Infraestrutura: Docker, Nginx

## Regras do projeto
- Trabalhar por etapas pequenas.
- Antes de codar, gerar documentação técnica.
- Não apagar arquivos sem autorização.
- Criar commits organizados.
- Priorizar MVP funcional.
- Toda funcionalidade deve ter validação, autenticação e controle de permissões.
- Dados de alunos devem seguir boas práticas de segurança e LGPD.

## Ideia já analisadas sobre o projeto
Nome sugerido: Gabarito360 – Gestão Inteligente de Aplicações e Correções

O sistema será composto por:
Backend web administrativo
Painel de gestão para Núcleo de Educação
Painel de gestão para Escolas
Aplicativo Android para professores/aplicadores
Módulo de leitura automática de cartões-resposta por foto
Dashboards em tempo real
Relatórios por aluno, turma, escola, prova e rede

## Contexto do sistema
O Gabarito360 será usado por um Núcleo de Educação que acompanha várias escolas. Cada escola possui um responsável. O responsável da escola cadastra professores ou aplicadores. Cada professor/aplicador terá login no sistema e será vinculado a uma ou mais turmas e aplicações de prova.
O sistema deve permitir o cadastro de provas, gabarito oficial, turmas e alunos. Durante a aplicação, o professor ou aplicador usará um aplicativo Android para fotografar o cartão-resposta do aluno. O cartão possui um código impresso, mas não possui o nome do aluno. Por isso, no momento da leitura, o professor selecionará o aluno no app, e o sistema vinculará o código do cartão ao aluno.
A escola e o núcleo poderão acompanhar em tempo real os cartões sendo lidos, os alunos já lançados, os ausentes, os acertos, médias, ranking, desempenho por turma, por escola e por questão.

## Objetivo da tarefa
Crie uma especificação completa contendo:
Visão geral do sistema
Objetivos do sistema
Perfis de usuários
Casos de uso
Requisitos funcionais
Requisitos não funcionais
Regras de negócio
Fluxos principais
Estrutura de banco de dados
Tabelas, campos e relacionamentos
APIs necessárias
Interfaces do sistema web
Interfaces do app Android
Estrutura do módulo de leitura de gabaritos por imagem
Dashboards e relatórios
Controle de permissões
Auditoria e segurança
MVP inicial
Etapas de desenvolvimento
Sugestão de stack tecnológica
Perfis de usuários
Considere inicialmente os seguintes perfis:
Administrador Geral do Sistema
Gestor do Núcleo de Educação
Responsável da Escola
Professor
Aplicador
Leitor/Consulta
Suporte Técnico
Funcionalidades principais
O sistema deve permitir:
Núcleo de Educação
Cadastrar escolas
Cadastrar responsáveis por escola
Criar provas oficiais
Definir modelo de cartão-resposta
Cadastrar gabarito oficial
Acompanhar aplicação em tempo real
Visualizar desempenho por escola
Gerar relatórios gerais
Exportar dados em PDF, Excel e CSV
Escola
Cadastrar turmas
Cadastrar alunos
Importar alunos via planilha
Cadastrar professores/aplicadores
Vincular professores às turmas
Vincular turmas a uma prova
Acompanhar aplicação em tempo real
Visualizar alunos presentes, ausentes e corrigidos
Gerar relatórios por turma e aluno
Professor/Aplicador
No aplicativo Android, o professor deve poder:
Fazer login
Selecionar escola, prova e turma
Visualizar lista de alunos
Selecionar aluno
Tirar foto do cartão-resposta
Ler automaticamente as marcações
Visualizar respostas detectadas
Corrigir manualmente uma leitura, se necessário
Confirmar o lançamento
Vincular código do cartão ao aluno
Salvar resultado
Trabalhar com internet e, se possível, com modo offline temporário
Sincronizar dados quando a internet voltar
Leitura automática do cartão
Descreva como o sistema deve:
Receber a foto do cartão
Detectar os pontos de referência do cartão
Corrigir perspectiva
Recortar a área de respostas
Identificar as alternativas A, B, C, D e E
Detectar marcações preenchidas
Identificar questões em branco
Identificar questões com dupla marcação
Comparar com o gabarito oficial
Calcular acertos
Retornar nível de confiança da leitura
Permitir conferência manual antes de salvar
Banco de dados
Modele as principais tabelas, incluindo, no mínimo:
nucleos
escolas
usuarios
perfis
turmas
alunos
provas
modelos_cartao
gabaritos_oficiais
questoes
aplicacoes
aplicadores_turmas
leituras_cartao
respostas_detectadas
resultados
auditorias
arquivos
logs_sincronizacao
Para cada tabela, informe:
Nome da tabela
Finalidade
Campos principais
Tipo de dado sugerido
Chaves primárias
Chaves estrangeiras
Relacionamentos
Regras de negócio importantes
Inclua regras como:
Um aluno só pode ter um cartão confirmado por prova, salvo reprocessamento autorizado.
O código do cartão deve ser vinculado ao aluno no momento da leitura.
O professor só visualiza turmas vinculadas a ele.
A escola só visualiza seus próprios dados.
O núcleo visualiza todas as escolas sob sua gestão.
Toda alteração manual em resposta detectada deve ser auditada.
Leituras com baixa confiança devem exigir revisão manual.
Questões em branco ou com dupla marcação devem ser destacadas.
O sistema deve registrar data, hora, usuário, dispositivo e localização aproximada, se autorizado.
Dashboards
Especifique dashboards para:
Núcleo
Total de escolas participantes
Total de alunos previstos
Total de cartões lidos
Percentual de aplicação concluída
Média geral por escola
Ranking de escolas
Desempenho por questão
Questões com maior índice de erro
Mapa de status por escola
Escola
Turmas participantes
Alunos lançados
Alunos pendentes
Média por turma
Ranking de alunos
Desempenho por questão
Exportação de resultados
Professor/Aplicador
Total de alunos da turma
Alunos já lidos
Alunos pendentes
Últimas leituras realizadas
Leituras com alerta
Botão para nova leitura
APIs
Proponha endpoints REST para:
Autenticação
Cadastro de escolas
Cadastro de turmas
Cadastro de alunos
Cadastro de provas
Cadastro de gabarito
Vinculação de prova à turma
Upload da imagem do cartão
Processamento da leitura
Confirmação da leitura
Correção manual
Consulta de resultados
Dashboards em tempo real
Exportação de relatórios
Stack tecnológica sugerida
Considere uma stack viável para produção:
Backend:
Laravel ou Node.js/NestJS
PostgreSQL ou MySQL
Redis para filas e tempo real
WebSocket para dashboard ao vivo
App Android:
Flutter ou Kotlin
Câmera nativa
OpenCV para processamento de imagem
Armazenamento local com SQLite
Sincronização com API
Painel Web:
Laravel Blade, Vue.js ou React
Bootstrap/Tailwind
Charts.js ou ApexCharts
Infraestrutura:
VPS ou Proxmox
Docker
Nginx
SSL
Backup automático
Logs e auditoria
Entregáveis esperados
Ao final, produza:
Documento de visão do sistema
Lista de casos de uso
Requisitos funcionais e não funcionais
Modelo inicial do banco de dados
Diagrama textual de relacionamentos
Fluxos de telas
Fluxo do aplicativo Android
Fluxo de processamento da imagem
Módulos do sistema
Roadmap de desenvolvimento do MVP
Backlog inicial em formato de histórias de usuário
Critérios de aceite para cada módulo
Organize a resposta de maneira técnica, clara e prática, como se fosse a documentação inicial para uma equipe de desenvolvimento começar o projeto.

1. Contexto geral do sistema
O sistema será utilizado por um núcleo de educação, responsável por várias escolas.
Cada escola terá um responsável administrativo. Esse responsável poderá cadastrar professores, aplicadores, turmas e alunos.
O núcleo de educação terá visão geral de todas as escolas, podendo acompanhar em tempo real a aplicação das provas, os lançamentos dos cartões-resposta, os resultados por escola, turma, professor/aplicador e aluno.
O professor ou aplicador utilizará um aplicativo Android para fotografar os cartões-resposta dos alunos no momento em que forem entregues. O aplicativo deverá ler automaticamente as marcações do gabarito, identificar ou registrar o código do cartão-resposta impresso no documento, permitir que o aplicador selecione o aluno correspondente e vincular esse código ao aluno.
O cartão-resposta pode já possuir um código impresso, mas não necessariamente terá o nome do aluno. Por isso, o vínculo entre aluno e cartão será feito no momento da leitura pelo aplicador.
O sistema deverá permitir que o gestor ou professor cadastre a avaliação e informe o gabarito oficial correto da prova. A partir disso, o aplicativo corrige automaticamente o cartão do aluno e envia os dados para o sistema central.
2. Objetivo do sistema
Criar uma plataforma web e mobile capaz de:
Cadastrar núcleo de educação, escolas, responsáveis, professores, aplicadores, turmas e alunos.
Cadastrar avaliações/provas.
Cadastrar o gabarito oficial de cada avaliação.
Permitir aplicação da prova por turma.
Ler cartão-resposta por foto usando app Android.
Detectar as alternativas marcadas pelo aluno.
Identificar o código impresso no cartão-resposta ou permitir digitá-lo manualmente quando necessário.
Vincular o código do cartão ao aluno selecionado.
Corrigir automaticamente a prova.
Registrar acertos, erros, brancos, rasuras e dupla marcação.
Enviar os dados para o backend.
Disponibilizar dashboards em tempo real.
Gerar relatórios por aluno, turma, escola, avaliação, aplicador e núcleo.
Permitir exportação em PDF, Excel/CSV e painéis de análise.
3. Perfis de usuários
Especifique os perfis de acesso do sistema, considerando inicialmente:
3.1 Administrador Geral / Núcleo de Educação
Responsável pela visão geral de todo o sistema.
Permissões esperadas:
Cadastrar e gerenciar escolas.
Cadastrar responsáveis escolares.
Visualizar todas as avaliações.
Visualizar todas as escolas.
Acompanhar aplicações em tempo real.
Ver dashboards consolidados.
Gerar relatórios gerais.
Comparar desempenho entre escolas, turmas e avaliações.
Gerenciar permissões e usuários.
3.2 Responsável da Escola / Gestor Escolar
Usuário responsável por uma escola específica.
Permissões esperadas:
Gerenciar dados da sua escola.
Cadastrar professores/aplicadores.
Cadastrar turmas.
Cadastrar alunos.
Cadastrar ou importar listas de alunos.
Criar avaliações para sua escola, quando permitido.
Acompanhar aplicações da sua escola em tempo real.
Visualizar relatórios da escola.
Ver resultados por turma, aluno e professor/aplicador.
3.3 Professor / Aplicador
Usuário responsável pela aplicação da prova em uma ou mais turmas.
Permissões esperadas:
Acessar apenas as turmas vinculadas a ele.
Visualizar avaliações disponíveis para suas turmas.
Usar o app Android para fotografar os cartões-resposta.
Selecionar o aluno correspondente.
Vincular código do cartão ao aluno.
Confirmar ou corrigir manualmente a leitura feita pelo app.
Enviar resultado para o sistema.
Ver resumo da turma aplicada.
Identificar alunos pendentes de lançamento.
3.4 Usuário de Consulta / Observador
Perfil opcional para coordenação pedagógica ou equipe técnica.
Permissões esperadas:
Visualizar dashboards e relatórios.
Não alterar dados sensíveis.
Não cadastrar ou excluir usuários.
4. Módulos principais do sistema
Desenvolva a especificação completa para os seguintes módulos:
4.1 Módulo de Autenticação e Controle de Acesso
Login.
Recuperação de senha.
Controle por perfis.
Permissões por escola, turma e avaliação.
Registro de logs de acesso.
Sessões ativas.
Segurança básica contra acesso indevido.
4.2 Módulo de Núcleos de Educação
Campos sugeridos:
ID do núcleo.
Nome do núcleo.
Município.
Estado.
Responsável principal.
E-mail.
Telefone.
Status: ativo/inativo.
Data de cadastro.
Funcionalidades:
Criar núcleo.
Editar núcleo.
Listar escolas vinculadas.
Visualizar indicadores consolidados.
4.3 Módulo de Escolas
Campos sugeridos:
ID da escola.
Núcleo vinculado.
Código da escola.
Nome da escola.
Município.
Estado.
Endereço.
Responsável escolar.
E-mail.
Telefone.
Status.
Funcionalidades:
Cadastrar escola.
Editar escola.
Arquivar/inativar escola.
Vincular responsáveis.
Visualizar turmas, alunos, avaliações e resultados.
4.4 Módulo de Usuários
Campos sugeridos:
ID do usuário.
Nome completo.
CPF ou matrícula.
E-mail.
Telefone.
Perfil de acesso.
Escola vinculada.
Núcleo vinculado.
Status.
Senha criptografada.
Último acesso.
Data de cadastro.
Funcionalidades:
Cadastrar usuário.
Editar usuário.
Resetar senha.
Ativar/inativar usuário.
Vincular usuário a uma ou mais turmas.
Definir perfil de acesso.
4.5 Módulo de Turmas
Campos sugeridos:
ID da turma.
Escola vinculada.
Nome da turma.
Série/ano.
Turno.
Ano letivo.
Professor/aplicador responsável.
Status.
Funcionalidades:
Cadastrar turma.
Editar turma.
Importar alunos.
Vincular professores/aplicadores.
Visualizar resultados da turma.
Ver alunos pendentes de leitura.
4.6 Módulo de Alunos
Campos sugeridos:
ID do aluno.
Nome completo.
Matrícula.
Código interno.
CPF, quando houver.
Data de nascimento.
Turma vinculada.
Escola vinculada.
Status.
Observações.
Funcionalidades:
Cadastrar aluno manualmente.
Importar alunos via planilha CSV/Excel.
Editar aluno.
Transferir aluno de turma.
Inativar aluno.
Consultar histórico de avaliações.
Vincular código de cartão-resposta ao aluno durante a aplicação.
4.7 Módulo de Avaliações / Provas
Campos sugeridos:
ID da avaliação.
Título da avaliação.
Descrição.
Tipo: OBMEP, simulado, prova interna, diagnóstico etc.
Ano.
Nível/série.
Número de questões.
Quantidade de alternativas por questão.
Data de aplicação.
Escola ou núcleo responsável.
Turmas vinculadas.
Status: rascunho, publicada, em aplicação, finalizada, arquivada.
Criado por.
Data de criação.
Funcionalidades:
Criar avaliação.
Editar avaliação.
Definir número de questões.
Definir alternativas possíveis.
Definir gabarito oficial.
Vincular avaliação a escolas ou turmas.
Publicar avaliação.
Encerrar aplicação.
Duplicar avaliação.
Arquivar avaliação.
4.8 Módulo de Gabarito Oficial
Campos sugeridos:
ID do gabarito.
Avaliação vinculada.
Questão.
Alternativa correta.
Peso da questão, caso necessário.
Status.
Funcionalidades:
Lançar gabarito manualmente.
Importar gabarito via planilha.
Conferir inconsistências.
Bloquear alteração após início da aplicação, ou permitir alteração com log.
Registrar histórico de alterações.
4.9 Módulo de Aplicação da Prova
Deve controlar o evento de aplicação da avaliação.
Campos sugeridos:
ID da aplicação.
Avaliação vinculada.
Escola.
Turma.
Professor/aplicador.
Data e hora de início.
Data e hora de fim.
Status: aguardando, em andamento, finalizada.
Total de alunos previstos.
Total de cartões lidos.
Total de alunos pendentes.
Funcionalidades:
Iniciar aplicação.
Visualizar lista de alunos da turma.
Registrar cartões lidos.
Finalizar aplicação.
Reabrir aplicação, se permitido.
Exibir progresso em tempo real.
4.10 Módulo Mobile Android de Leitura do Cartão-Resposta
Especificar um aplicativo Android, preferencialmente desenvolvido em Flutter ou React Native, com processamento de imagem usando OpenCV ou biblioteca equivalente.
Funcionalidades obrigatórias:
Login do aplicador.
Seleção da escola, turma e avaliação, respeitando permissões.
Lista de alunos da turma.
Botão para fotografar cartão-resposta.
Captura da imagem pela câmera.
Correção de perspectiva da imagem.
Detecção da área de respostas.
Leitura das bolinhas marcadas.
Identificação de respostas em branco.
Identificação de dupla marcação.
Identificação de marcação duvidosa.
Tentativa de leitura do código do cartão impresso no gabarito.
Campo para digitar o código manualmente, caso a leitura automática falhe.
Seleção do aluno correspondente.
Tela de conferência antes de salvar.
Correção manual das respostas lidas.
Envio dos dados para o backend.
Modo offline temporário, se possível.
Sincronização automática quando houver internet.
Registro da imagem original e/ou imagem processada, conforme política do sistema.
Histórico dos cartões já lançados.
Indicador de alunos pendentes.
Opção de refazer leitura.
Fluxo esperado no app:
Aplicador faz login.
Seleciona avaliação.
Seleciona turma.
Seleciona aluno ou fotografa primeiro o cartão.
App captura foto do cartão.
Sistema detecta e corrige perspectiva.
Sistema lê as respostas.
Sistema tenta identificar o código do cartão.
Aplicador confere os dados.
Aplicador seleciona ou confirma o aluno.
Sistema vincula código do cartão ao aluno.
Sistema calcula resultado.
Sistema envia dados ao backend.
Dashboard web é atualizado em tempo real.
4.11 Módulo de Processamento de Imagem / OMR
OMR significa Optical Mark Recognition.
Especificar a lógica de processamento considerando cartões-resposta semelhantes ao modelo da OBMEP, com 20 questões e alternativas A-E.
Etapas esperadas:
Receber imagem capturada.
Melhorar contraste.
Converter para escala de cinza.
Aplicar binarização.
Detectar bordas do cartão.
Corrigir perspectiva.
Localizar área de respostas.
Mapear posições das alternativas.
Calcular preenchimento de cada bolinha.
Definir limiar de marcação.
Detectar alternativa marcada.
Detectar múltiplas marcações.
Detectar resposta em branco.
Detectar baixa confiança.
Retornar resultado estruturado em JSON.
Permitir revisão manual no app.
Retorno esperado da leitura:
Código do cartão, se identificado.
Lista de respostas detectadas.
Grau de confiança por questão.
Questões em branco.
Questões com dupla marcação.
Questões duvidosas.
Imagem processada ou pontos de marcação detectados.
Status da leitura: sucesso, parcial, falha.
4.12 Módulo de Correção Automática
Funcionalidades:
Comparar respostas lidas com gabarito oficial.
Calcular total de acertos.
Calcular total de erros.
Calcular total de brancos.
Calcular total de questões anuladas, se houver.
Calcular nota, quando aplicável.
Registrar resultado individual.
Atualizar indicadores da turma, escola e núcleo.
Permitir recorreção caso o gabarito oficial seja alterado, com log.
4.13 Módulo de Dashboards em Tempo Real
Especificar dashboards para:
Núcleo de Educação
Indicadores:
Total de escolas participantes.
Total de turmas.
Total de alunos previstos.
Total de provas lançadas.
Percentual de aplicação concluída.
Média geral por escola.
Ranking de escolas.
Escolas com aplicações pendentes.
Aplicações em andamento.
Mapa ou lista de status por escola.
Escola
Indicadores:
Total de turmas participantes.
Total de alunos previstos.
Total de cartões lidos.
Alunos pendentes.
Média geral da escola.
Média por turma.
Desempenho por questão.
Desempenho por professor/aplicador.
Aplicações em andamento.
Professor/Aplicador
Indicadores:
Turma selecionada.
Alunos previstos.
Alunos lançados.
Alunos pendentes.
Resultado individual.
Total de acertos por aluno.
Questões com maior erro na turma.
Status de sincronização.
4.14 Módulo de Relatórios
Relatórios desejados:
Relatório individual do aluno.
Relatório da turma.
Relatório da escola.
Relatório geral do núcleo.
Relatório de alunos pendentes.
Relatório de cartões lidos.
Relatório de inconsistências.
Relatório de desempenho por questão.
Relatório comparativo entre turmas.
Relatório comparativo entre escolas.
Exportações:
PDF.
Excel.
CSV.
JSON, se necessário para integração.
4.15 Módulo de Auditoria e Logs
Registrar:
Quem criou avaliação.
Quem cadastrou gabarito.
Quem alterou gabarito.
Quem iniciou aplicação.
Quem fotografou cartão.
Data e hora de cada leitura.
Imagem original/processada, se armazenada.
Alterações manuais feitas pelo aplicador.
Motivo de correção manual.
Reprocessamentos.
Exclusões ou cancelamentos.
5. Banco de dados
Proponha uma modelagem relacional completa para o sistema.
Considere as seguintes tabelas iniciais, podendo adicionar outras:
nucleos
escolas
usuarios
perfis
permissoes
turmas
alunos
avaliacoes
avaliacao_turmas
gabaritos
aplicacoes
aplicacao_alunos
cartoes_resposta
leituras_omr
respostas_detectadas
resultados
logs_auditoria
arquivos
sessoes
sincronizacoes_mobile
Para cada tabela, especifique:
Nome da tabela.
Finalidade.
Campos.
Tipos de dados.
Chave primária.
Chaves estrangeiras.
Índices recomendados.
Regras de integridade.
Relacionamentos.
6. API Backend
Especifique uma API RESTful para o backend.
Inclua endpoints para:
Autenticação
POST /api/auth/login
POST /api/auth/logout
POST /api/auth/refresh
POST /api/auth/forgot-password
Núcleos
GET /api/nucleos
POST /api/nucleos
GET /api/nucleos/{id}
PUT /api/nucleos/{id}
DELETE /api/nucleos/{id}
Escolas
GET /api/escolas
POST /api/escolas
GET /api/escolas/{id}
PUT /api/escolas/{id}
DELETE /api/escolas/{id}
Turmas
GET /api/turmas
POST /api/turmas
GET /api/turmas/{id}
PUT /api/turmas/{id}
DELETE /api/turmas/{id}
Alunos
GET /api/alunos
POST /api/alunos
POST /api/alunos/importar
GET /api/alunos/{id}
PUT /api/alunos/{id}
DELETE /api/alunos/{id}
Avaliações
GET /api/avaliacoes
POST /api/avaliacoes
GET /api/avaliacoes/{id}
PUT /api/avaliacoes/{id}
DELETE /api/avaliacoes/{id}
POST /api/avaliacoes/{id}/publicar
POST /api/avaliacoes/{id}/encerrar
Gabaritos
GET /api/avaliacoes/{id}/gabarito
POST /api/avaliacoes/{id}/gabarito
PUT /api/avaliacoes/{id}/gabarito
POST /api/avaliacoes/{id}/gabarito/importar
Aplicações
GET /api/aplicacoes
POST /api/aplicacoes
GET /api/aplicacoes/{id}
POST /api/aplicacoes/{id}/iniciar
POST /api/aplicacoes/{id}/finalizar
GET /api/aplicacoes/{id}/status
Leitura de Cartões
POST /api/cartoes/upload
POST /api/cartoes/processar
POST /api/cartoes/confirmar
GET /api/cartoes/{id}
PUT /api/cartoes/{id}/corrigir
DELETE /api/cartoes/{id}
Resultados
GET /api/resultados
GET /api/resultados/aluno/{id}
GET /api/resultados/turma/{id}
GET /api/resultados/escola/{id}
GET /api/resultados/avaliacao/{id}
Dashboards
GET /api/dashboard/nucleo
GET /api/dashboard/escola/{id}
GET /api/dashboard/turma/{id}
GET /api/dashboard/aplicacao/{id}/tempo-real
Relatórios
GET /api/relatorios/aluno/{id}
GET /api/relatorios/turma/{id}
GET /api/relatorios/escola/{id}
GET /api/relatorios/nucleo/{id}
POST /api/relatorios/gerar
GET /api/relatorios/{id}/download
Para cada endpoint, especifique:
Método HTTP.
URL.
Perfil autorizado.
Parâmetros.
Payload esperado.
Resposta esperada.
Validações.
Possíveis erros.
7. Interfaces Web
Especifique as telas web necessárias:
Painel do Núcleo
Dashboard geral.
Gestão de escolas.
Gestão de usuários.
Gestão de avaliações.
Resultados gerais.
Relatórios.
Auditoria.
Painel da Escola
Dashboard da escola.
Gestão de turmas.
Gestão de alunos.
Gestão de professores/aplicadores.
Avaliações.
Aplicações em andamento.
Resultados.
Relatórios.
Painel do Professor/Aplicador
Minhas turmas.
Minhas avaliações.
Aplicações.
Resultados da turma.
Alunos pendentes.
Histórico de leituras.
Para cada tela, descreva:
Objetivo.
Componentes visuais.
Campos.
Botões.
Filtros.
Tabelas.
Cards.
Gráficos.
Ações disponíveis.
Regras de permissão.
8. Interfaces do App Android
Especifique as telas do aplicativo Android:
Tela de login.
Tela inicial do aplicador.
Seleção de avaliação.
Seleção de turma.
Lista de alunos.
Tela de captura da câmera.
Tela de processamento.
Tela de conferência da leitura.
Tela de seleção/confirmação do aluno.
Tela de resultado individual.
Tela de alunos pendentes.
Tela de histórico de cartões lidos.
Tela de sincronização.
Tela de configurações.
Para cada tela, descreva:
Objetivo.
Elementos visuais.
Botões.
Campos.
Fluxos.
Validações.
Mensagens de erro.
Estados offline/online.
9. Regras de negócio fundamentais
Especifique detalhadamente as regras de negócio, incluindo:
Um aluno só pode ter um cartão válido por avaliação.
Um cartão-resposta só pode ser vinculado a um aluno por avaliação.
O código do cartão deve ser único dentro da avaliação.
Uma leitura pode ser refeita, mas deve manter histórico.
Alterações manuais nas respostas exigem registro de auditoria.
O gabarito oficial não deve ser alterado após início da aplicação, exceto por perfil autorizado.
Se o gabarito for alterado, o sistema deve permitir recorreção dos resultados.
Aplicador só vê turmas vinculadas a ele.
Escola só vê seus próprios dados.
Núcleo vê todos os dados das escolas vinculadas.
Sistema deve identificar alunos pendentes.
Sistema deve identificar leituras com baixa confiança.
Sistema deve permitir salvar leitura mesmo com questões duvidosas, desde que o aplicador confirme.
Sistema deve manter rastreabilidade de quem fez cada lançamento.
10. Requisitos funcionais
Liste e detalhe todos os requisitos funcionais do sistema.
Exemplos:
RF001 - O sistema deve permitir cadastro de escolas.
RF002 - O sistema deve permitir cadastro de turmas.
RF003 - O sistema deve permitir importação de alunos.
RF004 - O sistema deve permitir cadastro de avaliação.
RF005 - O sistema deve permitir cadastro de gabarito oficial.
RF006 - O app deve permitir captura de imagem do cartão-resposta.
RF007 - O app deve processar a imagem e detectar respostas marcadas.
RF008 - O sistema deve corrigir automaticamente a prova.
RF009 - O sistema deve exibir dashboards em tempo real.
RF010 - O sistema deve gerar relatórios em PDF e Excel.
Continue a lista de forma completa.
11. Requisitos não funcionais
Liste e detalhe requisitos não funcionais, incluindo:
Segurança.
LGPD e proteção de dados dos alunos.
Desempenho.
Escalabilidade.
Disponibilidade.
Usabilidade.
Acessibilidade.
Compatibilidade mobile.
Modo offline no aplicativo.
Backup.
Logs.
Auditoria.
Criptografia de senha.
Criptografia em trânsito via HTTPS.
Controle de permissões.
Armazenamento seguro das imagens dos cartões.
12. Arquitetura sugerida
Proponha uma arquitetura técnica para o sistema.
Considere:
Backend
Opções possíveis:
Laravel.
Node.js com Express/NestJS.
Django.
FastAPI.
Escolha uma arquitetura recomendada e justifique.
Banco de dados
Opções possíveis:
PostgreSQL.
MySQL/MariaDB.
Escolha uma opção recomendada e justifique.
App Mobile
Opções possíveis:
Flutter.
React Native.
Android nativo Kotlin.
Escolha uma opção recomendada e justifique.
Processamento de imagem
Opções possíveis:
OpenCV no app.
OpenCV no backend.
Modelo híbrido.
Escolha uma estratégia recomendada.
Tempo real
Opções possíveis:
WebSockets.
Socket.IO.
Laravel Reverb.
Firebase.
Pusher.
Escolha uma estratégia recomendada.
Armazenamento de arquivos
Opções possíveis:
Storage local.
S3 compatível.
MinIO.
Cloudflare R2.
Escolha uma estratégia recomendada.
13. MVP
Defina um MVP inicial para o sistema.
O MVP deve conter apenas as funcionalidades essenciais para funcionar em uma aplicação real de prova.
Sugestão de MVP:
Cadastro de núcleo.
Cadastro de escola.
Cadastro de usuários.
Cadastro de turma.
Cadastro/importação de alunos.
Cadastro de avaliação.
Cadastro do gabarito oficial.
App Android com login.
Seleção de turma e avaliação.
Captura da foto do cartão.
Leitura das respostas.
Seleção do aluno.
Confirmação da leitura.
Correção automática.
Dashboard simples em tempo real.
Relatório básico por turma.
Separe o planejamento em:
MVP 1.
Versão 2.
Versão 3.
Funcionalidades futuras.
14. Protótipos e layout
Descreva um padrão visual para o sistema.
Sugestão:
Sistema com visual educacional, limpo e profissional.
Cores principais: azul, branco e verde.
Painéis com cards.
Tabelas com filtros.
Dashboards com gráficos simples.
App mobile com botões grandes, leitura rápida e poucos passos.
Crie uma descrição textual de wireframes para as principais telas web e mobile.
15. Entregáveis esperados da análise
Ao final, gere os seguintes entregáveis:
Documento de visão do produto.
Lista completa de casos de uso.
Lista de requisitos funcionais.
Lista de requisitos não funcionais.
Regras de negócio.
Modelo entidade-relacionamento textual.
Estrutura de banco de dados.
Especificação da API REST.
Fluxos do app Android.
Fluxos do sistema web.
Matriz de permissões.
Plano de desenvolvimento por etapas.
Backlog inicial em formato de histórias de usuário.
Critérios de aceite para cada funcionalidade.
Riscos técnicos do projeto.
Estratégias para reduzir erros na leitura dos cartões.
Estratégia de testes.
Sugestão de stack tecnológica.
Roadmap de evolução do produto.
Resumo executivo para apresentar o projeto a gestores.
16. Observações importantes
O sistema deve ser pensado inicialmente para cartões-resposta semelhantes ao modelo da OBMEP, mas com possibilidade futura de cadastrar outros modelos de cartão.
O sistema não deve depender obrigatoriamente de IA generativa para corrigir as provas. A leitura deve ser feita preferencialmente por visão computacional e OMR, com possibilidade de revisão manual pelo aplicador.
O app mobile deve ser simples, rápido e confiável, pois será usado durante a aplicação real da prova.
A prioridade é reduzir o trabalho manual do professor/aplicador, gerar resultados em tempo real e permitir que a escola e o núcleo acompanhem a aplicação da avaliação enquanto ela acontece.
Agora, gere a análise completa e estruturada do sistema AvaliaScan Edu, seguindo todos os tópicos acima, com linguagem técnica, clara e organizada.
