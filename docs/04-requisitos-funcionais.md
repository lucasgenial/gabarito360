# 04 — Requisitos Funcionais

Todos os requisitos abaixo têm origem rastreável nos mockups inventariados em `docs/01-inventario-dos-mockups.md`.

---

## RF-001 — Autenticação

### RF-001.1 — Login com e-mail e senha
- O sistema deve permitir login com e-mail institucional e senha
- E-mail deve seguir padrão institucional (ex.: nome@edu.gov.br)
- Senha mínima: 6 caracteres
- Opção "Manter conectado" disponível
- Link "Esqueci a senha" disponível
- Após login, redirecionar para dashboard do perfil correspondente

**Origem:** login.html

### RF-001.2 — Cadastro de usuário
- Formulário com: nome completo, CPF (com máscara), perfil, e-mail institucional
- Aceite dos termos de uso e LGPD obrigatório
- CPF com máscara automática XXX.XXX.XXX-XX

**Origem:** login.html

### RF-001.3 — Proteção por autenticação gov.br
- Mensagem "Acesso protegido por autenticação gov.br" na tela de login
- Integração planejada com gov.br (detalhes em docs/08-omr.md e docs/12-arquitetura.md)

---

## RF-002 — Gestão de Escolas

### RF-002.1 — Listar escolas
- Exibir escolas em grid de cards
- KPIs: total cadastradas, ativas, alunos totais, turmas ativas
- Busca em tempo real por nome
- Cada card exibe: nome, INEP, endereço, telefone, e-mail, diretor, status, alunos, turmas, provas

**Origem:** escolas.html

### RF-002.2 — Cadastrar escola
- Modal de formulário com seções: Identificação, Endereço, Contato, Status
- Campos obrigatórios: Nome
- Campos: Nome, INEP, Tipo de rede (Estadual/Municipal/Federal/Privada), Logradouro, Cidade, UF, Telefone, E-mail, Diretor(a), Status (ativa/inativa)
- Validação: nome não pode ser vazio

**Origem:** escolas.html (modal)

### RF-002.3 — Editar escola
- Mesmo modal do cadastro, pré-preenchido com dados existentes

### RF-002.4 — Desativar/Reativar escola
- Escola inativa exibida com opacidade reduzida e badge sem cor
- Botão "Reativar" disponível para escola inativa

**Origem:** escolas.html

### RF-002.5 — Detalhe da escola
- Visão completa: turmas, equipe, histórico de provas

---

## RF-003 — Gestão de Turmas

### RF-003.1 — Listar turmas
- Tabela com: turma (com avatar), série, alunos, desempenho médio (barra mini), status, ação
- Filtros: busca por nome, série, status
- Contador: total de turmas e total de alunos
- Status: Em dia, Em recuperação, Com pendências

**Origem:** turmas.html

### RF-003.2 — Criar turma
- Botão "Nova turma" no topo da lista
- Campos: nome, série, escola, professor(es), turno

### RF-003.3 — Importar turma por planilha
- Botão "Importar planilha" disponível
- Formato da planilha: a definir (xlsx/csv)

### RF-003.4 — Detalhe da turma
- Lista de alunos com nota e status
- Histórico de provas aplicadas
- Navegação para detalhe do aluno

**Origem:** turma-detalhe-2.html

---

## RF-004 — Gestão de Alunos

### RF-004.1 — Cadastrar aluno
- Formulário com: nome completo, matrícula, data de nascimento, responsável, turma
- Vínculo obrigatório com turma

**Origem:** aluno-cadastrar.html / aluno-cadastrar-redesign.html

### RF-004.2 — Editar aluno
- Edição dos dados cadastrais do aluno

**Origem:** aluno-editar.html

### RF-004.3 — Detalhe do aluno
- Avatar com iniciais, nome, série/turma, matrícula, data nasc., responsável
- KPIs: Média Geral, Frequência, Provas Realizadas
- Histórico de avaliações com filtro por bimestre
- Gráfico de evolução de notas por mês
- Exportar ficha PDF

**Origem:** aluno-detalhe.html

---

## RF-005 — Gestão de Membros (Usuários)

### RF-005.1 — Listar membros da equipe
- Lista de usuários da escola com perfil, status

**Origem:** perfis-equipe.html

### RF-005.2 — Cadastrar membro
- Formulário com: nome, e-mail, CPF, perfil, escola

**Origem:** membro-cadastrar.html

### RF-005.3 — Editar membro
- Edição de dados e perfil do membro

**Origem:** membro-editar.html

---

## RF-006 — Gestão de Provas

### RF-006.1 — Listar provas
- Tabela com: título, disciplina, turmas, data de aplicação, status, progresso, ação contextual
- Filtros: busca por título, disciplina, status
- Status: Rascunho, Publicada, Em correção, Corrigida
- Ação contextual varia conforme status

**Origem:** provas.html

### RF-006.2 — Criar prova (passo 1 — Dados)
- Campos: título, disciplina, série/ano, número de questões
- Stepper com 3 etapas: Dados → Gabarito oficial → Cartão & Turmas

### RF-006.3 — Criar gabarito (passo 2 — Gabarito Oficial)
- Editor de bolhas interativo
- Grid 2 colunas, até N questões (definido no passo 1)
- Alternativas: A–E (configurável: 3, 4 ou 5)
- Contador de progresso: X/N preenchido
- Barra de progresso
- Padrões configuráveis (expansível): alternativas, nota máxima, tipo de pontuação, escola, opções de anulação e geração de PDF

### RF-006.4 — Salvar rascunho
- Permite salvar a prova sem publicar o gabarito

### RF-006.5 — Publicar gabarito
- Torna o gabarito oficial disponível para uso no OMR
- Status muda de "Rascunho" para "Publicada"

### RF-006.6 — Visualizar gabarito publicado
- Exibição somente-leitura do gabarito oficial
- Informações da prova: disciplina, série/turmas, nº questões, alternativas, data
- Exportar PDF via window.print()

**Origem:** gabarito.html

---

## RF-007 — OMR e Correção

### RF-007.1 — Acompanhar correção (prova individual)
- Monitor em tempo real com: cartões lidos, pendentes, ambíguos, total turma
- Gráfico donut com percentual lido
- Barra de progresso horizontal
- Feed de atividade recente (aluno, nota, tempo)
- Botão "Atualizar leitura"

**Origem:** acompanhar-correcao.html

### RF-007.2 — Resolução de cartões ambíguos
- Lista de cartões com marcação ambígua
- Exibe: ID do cartão, questão problemática, opções possíveis
- Resolução manual: clicar na alternativa correta
- Ao resolver: ambíguo → lido, contadores atualizados

**Origem:** acompanhar-correcao.html

### RF-007.3 — Acompanhar correção por turma
- Visão consolidada de múltiplas provas de uma turma

**Origem:** acompanhar-correcao-turma.html

### RF-007.4 — Estado de leitura concluída
- Ao concluir todos os cartões: botão desativado, texto "Leitura concluída"
- Status da prova muda para "Corrigida"

---

## RF-008 — Resultados e Relatórios

### RF-008.1 — Resultado individual do aluno
- Gráfico donut com nota em %
- Nota final, nº de acertos, total de questões
- Badge: Aprovado (>= nota mínima) ou Recuperação
- Badge comparativo com média da turma
- Gráfico de acertos por tema/assunto
- Folha de respostas corrigida: verde (acerto), vermelho (erro), amarelo (branco/ambíguo)
- Legenda da folha
- Confiança OMR exibida (ex.: 98,6%)
- Breadcrumb dinâmico conforme origem
- Exportar PDF
- Revisar leitura

**Origem:** resultado.html

### RF-008.2 — Relatório da prova
- KPIs: média da prova, aprovação (%), cartões corrigidos, pendências
- Gráfico de acertos por tema
- Donut de aproveitamento médio
- Tabela com resultado de cada aluno: nome, turma, nota, status, ação

**Origem:** relatorio-prova.html

### RF-008.3 — Relatório da turma por prova
- Visão do desempenho de uma turma em uma prova específica

**Origem:** relatorio-turma-prova.html

---

## RF-009 — Dashboards

### RF-009.1 — Dashboard do Administrador da Rede
- KPIs: Escolas, Alunos, Provas aplicadas no mês, Média Geral
- Notice institucional com nome da rede e período letivo
- Gráfico: top 5 escolas por desempenho
- Painel de alertas: cartões pendentes, escolas abaixo da meta, integração SEGES
- Tabela: últimos acessos de usuários
- Ações rápidas: Nova Escola, Gerenciar Usuários, Gerar Relatório, Configurações
- Botão: "Gerar visão executiva"

**Origem:** dashboard-admin.html

### RF-009.2 — Dashboard do Diretor de Núcleo
- KPIs: Escolas no núcleo, Alunos, Provas realizadas, Média do núcleo
- Tabela comparativa das escolas: escola, turmas, alunos, média, tendência, status
- Gráfico de evolução bimestral (4 bimestres)
- Lista de visitas programadas com urgência
- Ações rápidas: Ver Escolas, Relatório Comparativo, Agendar Visita
- Botão: "Agendar reunião de acompanhamento"

**Origem:** dashboard-diretor-nucleo.html

### RF-009.3 — Dashboard do Diretor Escolar
- Banner com dados da escola (INEP, tipo de ensino, turnos)
- KPIs: Turmas ativas, Total alunos, Provas aplicadas, Média da escola
- Tabela: turmas com professor, alunos, última prova, média, status
- Equipe da escola: cards com avatar, nome, função
- Calendário: próximas provas agendadas
- Ações rápidas: Gerenciar Equipe, Ver Turmas, Relatório da Escola
- Botão: "Ver turmas ativas"

**Origem:** dashboard-diretor-escolar.html

### RF-009.4 — Dashboard do Coordenador
- KPIs: Provas em andamento, Cartões pendentes, Alunos abaixo da média, Próximas provas
- Tabela: provas em andamento com status e progresso
- Lista: alunos em atenção com nota e tendência
- Agenda da semana: eventos com tipo, descrição, data/hora
- Ações rápidas: Criar Prova, Acompanhar Correções, Ver Turmas
- Botão: "Criar prova"

**Origem:** dashboard-coordenador.html

### RF-009.5 — Dashboard do Professor
- Saudação personalizada com emoji
- KPIs: Minhas provas, Cartões a corrigir, Minhas turmas, Média das turmas
- Tabela: minhas provas com ações inline (Ver gabarito, Resultados, Acompanhar)
- Ranking: Top 5 e Bottom 3 de desempenho
- Cards: alunos que precisam de atenção com tendência
- Ações rápidas: Nova Prova, Capturar Cartões, Ver Turmas
- Botão: "+ Nova Prova"

**Origem:** dashboard-professor.html

### RF-009.6 — Dashboard do Aluno
- Welcome card com avatar e dados do aluno
- KPIs: Provas realizadas, Minha média, Melhor disciplina, Próxima prova
- Tabela: minhas notas com tendência por disciplina
- Gráfico de evolução bimestral
- Lista: próximas provas com disciplina, professor, data
- Card motivacional do MEC

**Origem:** dashboard-aluno.html

---

## RF-010 — Perfil e Configurações

### RF-010.1 — Meu Perfil
- Visualização e edição dos dados pessoais do usuário logado
- Campos: nome, e-mail, CPF, perfil/cargo, instituição, foto, senha

**Origem:** perfil.html

### RF-010.2 — Configurações do sistema
- Configurações de integrações, calendário, parâmetros institucionais
- Parâmetros padrão de avaliação (alternativas, nota máxima)

**Origem:** configuracoes.html

---

## RF-011 — Interface e UX

### RF-011.1 — Toggle de tema (claro/escuro)
- Botão no header disponível em todas as telas autenticadas

### RF-011.2 — GovBar
- Faixa superior com "🇧🇷 Governo Federal", links "Acessibilidade" e "Alto Contraste"

### RF-011.3 — Breadcrumb
- Presente em todas as telas autenticadas
- Hierarquia reflete a estrutura de navegação

### RF-011.4 — Toast de feedback
- Notificações de sucesso, atenção e erro para ações do usuário
- Ex.: "Escola salva com sucesso!"

### RF-011.5 — Responsividade
- Breakpoints obrigatórios conforme DESIGN-MANIFEST.json:
  - mobile-compact: 360px
  - mobile-standard: 390px
  - mobile-large: 430px
  - foldable: 600px
  - tablet-portrait: 820px
  - tablet-landscape: 1024px
  - laptop: 1366px
  - desktop: 1440px
  - wide: 1920px
