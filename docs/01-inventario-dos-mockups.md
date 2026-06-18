# 01 — Inventário dos Mockups

Este documento é a base para todos os requisitos funcionais, casos de uso, modelo de dados e rotas.
Nenhuma funcionalidade pode ser implementada sem estar registrada aqui ou aprovada posteriormente.

**Total de telas:** 30 (conforme DESIGN-MANIFEST.json)

---

## Estrutura de Navegação Principal

Todas as telas autenticadas compartilham:
- **GovBar:** faixa do Governo Federal com links de Acessibilidade e Alto Contraste
- **Header:** logo G360 / Gabarito360, navegação principal, badge de contexto (rede/escola/turma), toggle de tema, menu do usuário
- **Menu do usuário:** nome, perfil, links para Meu Perfil, Configurações, Sair
- **Breadcrumb:** trilha de navegação hierárquica
- **Footer:** espaço de 48px (sem conteúdo)

---

## 01 — login.html

**Título:** Gabarito360 — Acesso

**Descrição:** Tela de autenticação com layout two-column.

### Componentes

| Componente        | Descrição                                                          |
|-------------------|--------------------------------------------------------------------|
| Aside institucional | Painel esquerdo azul com tagline, estatísticas (12s/cartão, 98,6% precisão, 340 escolas) |
| Tabs Login/Cadastro | Alternância entre formulário de login e cadastro                 |
| Form Login        | E-mail institucional (nome@edu.gov.br), Senha, "Manter conectado", "Esqueci a senha" |
| Form Cadastro     | Nome completo, CPF (com máscara), Perfil (select), E-mail, Aceite LGPD |
| Botão Login       | "Entrar no painel" → redireciona para dashboard.html              |
| Botão Cadastro    | "Criar conta"                                                      |

### KPIs da Aside
- 12s por cartão lido
- 98,6% precisão do OMR
- 340 escolas na rede

### Validações
- E-mail: formato válido (regex)
- Senha: mínimo 6 caracteres
- CPF: máscara automática XXX.XXX.XXX-XX

### Entidades
- Usuario (email, senha, perfil)

### Permissões
- Pública (sem autenticação)

### Fluxos
- Login com e-mail/senha → dashboard do perfil correspondente
- "Esqueci a senha" → fluxo de recuperação (não mockado)
- Cadastro → aguarda aprovação (fluxo não mockado)

---

## 02 — dashboard-admin.html

**Título:** Gabarito360 — Painel Administrativo

**Perfil:** Administrador da Rede

**Contexto exibido no header:** Rede Municipal

### KPIs

| KPI                      | Valor Exemplo | Tendência        |
|--------------------------|---------------|------------------|
| Total de Escolas         | 48            | ▲ 2 novas unidades|
| Total de Alunos          | 12.840        | ▲ 1,4%           |
| Provas aplicadas (mês)   | 312           | ▲ 28 nesta semana|
| Média Geral da Rede      | 6,8           | ● meta: 7,0      |

### Componentes

| Componente                  | Descrição                                                   |
|-----------------------------|-------------------------------------------------------------|
| Notice institucional        | Banner azul com nome da rede e período letivo               |
| Gráfico de barras           | Top 5 escolas por desempenho médio                          |
| Painel de alertas críticos  | Cartões pendentes, escolas abaixo da meta, integração SEGES |
| Tabela últimos acessos      | Nome, Perfil, Escola, Último acesso, Status (Online/Ativo/Offline) |
| Ações rápidas (4 cards)     | Nova Escola, Gerenciar Usuários, Gerar Relatório, Configurações |

### Alertas Críticos (3 tipos)
1. Cartões pendentes com marcação ambígua (quantidade)
2. Escolas abaixo da meta (com solicitação de intervenção pedagógica)
3. Integração SEGES com atenção (atraso na sincronização)

### Ações
- **Gerar visão executiva** (botão primário no topo)
- Nova Escola → escolas.html
- Gerenciar Usuários → (rota a definir)
- Gerar Relatório → (rota a definir)
- Configurações → configuracoes.html

### Navegação Principal (Admin)
- Painel, Provas, Turmas, Escolas

### Entidades
- Rede, Escola, Aluno, Prova, Usuario, Integração (SEGES)

### Permissões
- Exclusivo para: Administrador da Rede

---

## 03 — dashboard-diretor-nucleo.html

**Título:** Gabarito360 — Painel do Núcleo

**Perfil:** Diretor de Núcleo

**Contexto:** Núcleo Norte (supervisão de 12 escolas)

### KPIs

| KPI                   | Valor Exemplo | Tendência              |
|-----------------------|---------------|------------------------|
| Escolas no Núcleo     | 12            | ● 100% calendário validado |
| Alunos                | 3.240         | ▲ 94 matrículas        |
| Provas Realizadas     | 86            | ▲ 11 aplicações        |
| Média do Núcleo       | 6,5           | ▼ 0,1 ponto da meta    |

### Componentes

| Componente               | Descrição                                                    |
|--------------------------|--------------------------------------------------------------|
| Tabela comparativa escolas | Escola, Turmas, Alunos, Média, Tendência, Status           |
| Gráfico bimestral         | Barras com média consolidada por bimestre (4 bimestres)    |
| Lista visitas programadas | Data, tipo de visita, urgência (Prioritária/Monitorar/Referência) |
| Ações rápidas (3 cards)   | Ver Escolas, Relatório Comparativo, Agendar Visita         |

### Status das Escolas (tabela)
- Destaque (badge-success)
- Acima da meta (badge-success)
- Monitoramento (badge-info)
- Atenção (badge-warn)
- Abaixo da meta (badge-danger)

### Ações
- Agendar reunião de acompanhamento (botão primário)
- Ver Escolas → escolas.html
- Relatório Comparativo → (rota a definir)
- Agendar Visita → (rota a definir)

### Entidades
- Nucleo, Escola, Aluno, Prova, Visita

### Permissões
- Exclusivo para: Diretor de Núcleo

---

## 04 — dashboard-diretor-escolar.html

**Título:** Gabarito360 — Painel da Escola

**Perfil:** Diretor Escolar

**Contexto:** EMEF Tiradentes

### Componentes Específicos

| Componente         | Descrição                                              |
|--------------------|--------------------------------------------------------|
| Banner da escola   | Nome, INEP, tipo de ensino, turnos, badge Escola Ativa |
| KPIs (4)          | Turmas Ativas, Total Alunos, Provas Aplicadas, Média  |
| Tabela por turma   | Turma, Professor, Alunos, Última Prova, Média, Status |
| Equipe da escola   | Cards com avatar, nome e função (Diretor, Vice, Coord, Prof.) |
| Calendário provas  | Lista de aplicações agendadas com status               |
| Ações rápidas (3)  | Gerenciar Equipe, Ver Turmas, Relatório da Escola     |

### Status das Turmas
- Bom desempenho, Em dia, Monitorar, Atenção leve, Destaque, Plano de apoio

### Entidades
- Escola, Turma, Professor, Prova, Usuario

### Permissões
- Exclusivo para: Diretor Escolar

---

## 05 — dashboard-coordenador.html

**Título:** Gabarito360 — Painel do Coordenador

**Perfil:** Coordenador

### KPIs

| KPI                      | Valor | Estado          |
|--------------------------|-------|-----------------|
| Provas em andamento      | 8     | normal          |
| Cartões pendentes leitura| 43    | warn (amarelo)  |
| Alunos abaixo da média   | 67    | danger (vermelho)|
| Próximas provas          | 5     | normal          |

### Componentes

| Componente            | Descrição                                                       |
|-----------------------|-----------------------------------------------------------------|
| Tabela provas         | Disciplina, Professor, Turma, Total alunos, Cartões lidos, Status |
| Lista alunos atenção  | Avatar iniciais, Nome, Turma, Disciplina, Badge com nota atual  |
| Agenda da semana      | Ícone tipo, Descrição evento, Data/hora                        |
| Ações rápidas (3)     | Criar Prova, Acompanhar Correções, Ver Turmas                  |

### Status das Provas
- em correção (badge-info)
- concluído (badge-success)
- aguardando (badge-warn)

### Entidades
- Prova, Turma, Aluno, Professor, Agenda

### Permissões
- Exclusivo para: Coordenador

---

## 06 — dashboard-professor.html

**Título:** Gabarito360 — Painel do Professor

**Perfil:** Professor

### KPIs

| KPI                  | Valor | Estado      |
|----------------------|-------|-------------|
| Minhas provas        | 18    | normal      |
| Cartões p/ corrigir  | 12    | warn        |
| Minhas turmas        | 3     | normal      |
| Média das turmas     | 6,4   | down        |

### Componentes

| Componente               | Descrição                                                           |
|--------------------------|---------------------------------------------------------------------|
| Tabela minhas provas     | Prova, Turma, Data, Total alunos, Status, Ações (3 botões por linha)|
| Ranking desempenho       | Top 5 e Bottom 3 por nota                                          |
| Alunos que precisam atenção | Cards com avatar, nome, turma, nota, tendência                 |
| Ações rápidas (3)        | Nova Prova, Capturar Cartões, Ver Turmas                          |

### Ações por Prova (inline na tabela)
- Ver gabarito → gabarito.html
- Resultados → relatorio-prova.html
- Acompanhar → acompanhar-correcao.html

### Entidades
- Prova, Turma, Aluno, Gabarito

### Permissões
- Exclusivo para: Professor (vê apenas suas próprias provas e turmas)

---

## 07 — dashboard-aluno.html

**Título:** Gabarito360 — Painel do Aluno

**Perfil:** Aluno

### KPIs

| KPI               | Valor       | Destaque          |
|-------------------|-------------|-------------------|
| Provas realizadas | 14          | ▲ 3 neste bimestre|
| Minha média       | 7,4         | badge bom desempenho |
| Melhor disciplina | História    | ▲ 9,2 última prova|
| Próxima prova     | Seg. 16/06  | ● Matemática 7h30 |

### Componentes

| Componente           | Descrição                                               |
|----------------------|---------------------------------------------------------|
| Welcome card         | Avatar iniciais, nome, turma, escola                   |
| Tabela minhas notas  | Disciplina, Última prova, Nota, Média bimestral, Tendência |
| Evolução bimestral   | Gráfico de barras por bimestre + cards de bimestres futuros |
| Próximas provas      | Lista com disciplina, professor, data, badge cor       |
| Mensagem de incentivo| Card motivacional do MEC                               |

### Navegação do Aluno (limitada)
- Painel
- Minhas Provas

### Entidades
- Aluno, Prova, Nota, Turma

### Permissões
- Exclusivo para: Aluno (vê apenas seus próprios dados)

---

## 08 — provas.html

**Título:** Gabarito360 — Provas e Gabaritos

### Componentes

| Componente          | Descrição                                                  |
|---------------------|------------------------------------------------------------|
| Toolbar de filtros  | Busca por título, filtro por disciplina, filtro por status |
| Tabela de provas    | Prova, Turmas, Aplicação, Status, Progresso, Ação          |
| Coluna Progresso    | Barra mini + valor (varia conforme status)                 |
| Botão contextual    | Varia conforme status da prova                             |

### Status das Provas
| Status      | Badge       | Ação Contextual          |
|-------------|-------------|--------------------------|
| Rascunho    | badge-muted | Continuar (editar)       |
| Publicada   | badge-info  | Ver gabarito             |
| Em correção | badge-warn  | Acompanhar               |
| Corrigida   | badge-success | Ver resultados         |

### Disciplinas (filtro)
Matemática, Português, Ciências, História, Geografia, Inglês

### Entidades
- Prova, Turma, Gabarito, Cartão

### Permissões
- Professor: vê apenas suas provas
- Coordenador/Diretor: vê provas da escola
- Admin/Núcleo: vê todas

---

## 09 — criar-prova.html

**Título:** Gabarito360 — Criar Prova

**Fluxo em 3 etapas (stepper):**
1. Dados ✓
2. Gabarito oficial (ativo)
3. Cartão & Turmas

### Componentes

| Componente               | Descrição                                                     |
|--------------------------|---------------------------------------------------------------|
| Painel de dados (sticky) | Título, Disciplina, Série/ano, Nº de questões                |
| Padrões da prova (expansível) | Alternativas por questão (3/4/5), Nota máxima, Tipo pontuação, Escola, Checkboxes |
| Contador gabarito        | X/20 preenchido com barra de progresso                       |
| Editor de bolhas         | Grid 2 colunas, 20 questões, 5 alternativas (A-E) clicáveis |
| Botões de ação           | Salvar rascunho, Publicar gabarito                           |

### Configurações Disponíveis
- Alternativas por questão: 3, 4 ou 5
- Nota máxima: 1–100 (padrão 10)
- Tipo de pontuação: pesos iguais ou personalizados por questão
- Escola vinculada
- Anular questão se todas marcadas (checkbox)
- Gerar cartão-resposta em PDF (checkbox)

### Entidades
- Prova, Gabarito, Questão, Alternativa, Turma

### Permissões
- Professor, Coordenador

---

## 10 — gabarito.html

**Título:** Gabarito360 — Gabarito Oficial

**Descrição:** Visualização somente-leitura do gabarito publicado.

### Componentes

| Componente       | Descrição                                       |
|------------------|-------------------------------------------------|
| Dados da prova   | Disciplina, Série/Turmas, Nº questões, Alt., Data |
| Nota informativa | Descrição do uso do gabarito no OMR             |
| Folha de respostas | Grid 2 colunas com bolhas marcadas (somente leitura) |
| Exportar PDF     | Botão → window.print()                         |
| Badge status     | "Publicada" (badge-info)                       |

### Entidades
- Prova, Gabarito, Questão, Alternativa

### Permissões
- Professor, Coordenador, Diretor

---

## 11 — acompanhar-correcao.html

**Título:** Gabarito360 — Acompanhar Correção

**Descrição:** Monitor em tempo real da leitura dos cartões de uma prova.

### Componentes

| Componente          | Descrição                                                   |
|---------------------|-------------------------------------------------------------|
| Estatísticas        | Cartões lidos, Pendentes, Ambíguos, Total turma            |
| Gráfico donut       | Percentual lido com cor dinâmica                           |
| Barra de progresso  | Preenchimento conforme leitura avança                      |
| Lista de ambíguos   | Cartão ID, questão problemática, botões de resolução manual|
| Feed de atividade   | Nome do aluno, nota atribuída, tempo decorrido             |
| Botão atualizar     | "Atualizar leitura" → simula pull de novos cartões         |

### Estados da Leitura
- Em andamento: botão ativo
- Concluída: botão desativado com "Leitura concluída"

### Resolução de Ambíguos
- Exibe opções possíveis (ex.: "B" ou "D")
- Resolução manual pelo usuário
- Ao resolver: ambíguo → lido, contador atualizado

### Entidades
- Prova, Cartão, Aluno, Nota, Leitura OMR

### Permissões
- Professor (suas provas), Coordenador

---

## 12 — acompanhar-correcao-turma.html

**Título:** Acompanhar Correção por Turma

**Descrição:** Visão consolidada do progresso de leitura de múltiplas provas de uma turma.

### Entidades
- Turma, Prova, Cartão, Aluno

### Permissões
- Coordenador, Diretor Escolar

---

## 13 — turmas.html

**Título:** Gabarito360 — Turmas

### Componentes

| Componente       | Descrição                                               |
|------------------|---------------------------------------------------------|
| Contador         | Total de turmas e total de alunos                      |
| Toolbar filtros  | Busca por nome, filtro por série, filtro por status    |
| Botões           | Importar planilha, Nova turma                          |
| Tabela turmas    | Turma (avatar), Série, Alunos, Desempenho médio, Status, Ver turma |

### Status das Turmas
- Em dia (badge-success)
- Em recuperação (badge-warn)
- Com pendências (badge-danger)

### Desempenho
- Barra mini colorida (verde/amarelo/vermelho conforme nota)

### Entidades
- Turma, Aluno, Escola, Série

### Permissões
- Professor (suas turmas), Coordenador/Diretor (escola), Admin/Núcleo (rede)

---

## 14 — turma-detalhe-2.html

**Título:** Detalhe da Turma

**Descrição:** Visão detalhada de uma turma com lista de alunos e histórico de provas.

### Componentes
- Header da turma (nome, série, escola, professor)
- KPIs (alunos, média, provas, pendências)
- Tabela de alunos com nota e status
- Histórico de provas aplicadas

### Entidades
- Turma, Aluno, Prova, Nota

---

## 15 — escolas.html

**Título:** Gabarito360 — Escolas

### Componentes

| Componente        | Descrição                                                 |
|-------------------|-----------------------------------------------------------|
| KPIs (4)         | Escolas cadastradas, Ativas, Alunos totais, Turmas ativas |
| Busca em tempo real | Filtro por nome da escola                              |
| Grid de cards    | Card por escola com dados completos                       |
| Modal Nova Escola | Formulário de cadastro/edição com seções                 |

### Card de Escola
- Ícone, Nome, Código INEP, Badge status (Ativa/Inativa)
- Endereço, Telefone, E-mail institucional, Diretor(a)
- Stats: Alunos, Turmas, Provas
- Ações: Editar, Ver mais

### Modal de Cadastro/Edição
**Seção Identificação:** Nome, INEP, Tipo de rede (Estadual/Municipal/Federal/Privada)
**Seção Endereço:** Logradouro, Cidade, UF
**Seção Contato:** Telefone, E-mail, Diretor(a)
**Seção Status:** Checkbox "Escola ativa"

### Estado Escola Inativa
- Card com opacidade reduzida
- Badge sem cor (badge padrão)
- Botão "Reativar" disponível

### Entidades
- Escola, Nucleo, Usuario (Diretor)

### Permissões
- Admin/Núcleo: todas as ações
- Diretor Escolar: visualizar e editar sua escola

---

## 16 — escola-detalhe.html

**Título:** Detalhe da Escola

**Descrição:** Visão completa de uma escola com turmas, equipe e histórico.

### Entidades
- Escola, Turma, Usuario, Prova

---

## 17 — aluno-cadastrar.html / aluno-cadastrar-redesign.html

**Título:** Cadastrar Aluno

**Descrição:** Formulário de cadastro de novo aluno na turma.

### Campos Esperados
- Nome completo
- Matrícula
- Data de nascimento
- Responsável
- Turma (vínculo)
- Dados adicionais

### Entidades
- Aluno, Turma

### Permissões
- Coordenador, Diretor Escolar

---

## 18 — aluno-editar.html

**Título:** Editar Aluno

**Descrição:** Formulário de edição dos dados de um aluno existente.

### Entidades
- Aluno, Turma

---

## 19 — aluno-detalhe.html

**Título:** Gabarito360 — Detalhe do Aluno

### Componentes

| Componente          | Descrição                                              |
|---------------------|--------------------------------------------------------|
| Header do aluno     | Avatar iniciais, Nome, Série/Turma, Matrícula, Nasc., Responsável |
| Ações do header     | Editar dados, Ficha do Aluno (PDF)                    |
| KPIs (3)           | Média Geral, Frequência, Provas Realizadas             |
| Histórico avaliações| Tabela: Prova, Data, Desempenho (barra), Nota, Ver Resultado |
| Filtro bimestre     | Selecionar bimestre ou "Todos"                        |
| Gráfico evolução    | Barras por mês com notas                              |

### Entidades
- Aluno, Prova, Nota, Turma

### Permissões
- Professor (seus alunos), Coordenador, Diretor

---

## 20 — resultado.html

**Título:** Gabarito360 — Resultado do Aluno

**Descrição:** Resultado individual de uma prova com folha de respostas corrigida.

### Componentes

| Componente         | Descrição                                                |
|--------------------|----------------------------------------------------------|
| Header aluno       | Avatar, nome, turma, prova, data                        |
| Nota final         | Gráfico donut + nota, acertos, total questões           |
| Badge status       | Aprovada (success) ou Recuperação (danger)             |
| Badge comparativo  | Acima/abaixo da média da turma                         |
| Acertos por tema   | Gráfico de barras por assunto (Álgebra, Geom., etc.)   |
| Folha corrigida    | Grid com cada questão: verde (acerto), vermelho (erro), amarelo (branco/ambíguo) |
| Legenda            | Correta, Incorreta, Em branco/Ambígua                  |
| Botões             | Exportar PDF, Revisar leitura                          |
| Breadcrumb dinâmico| Contextual conforme origem (turma / prova / aluno)     |

### Confiança OMR
- Exibe "Leitura OMR · 98,6% de confiança" (badge)

### Entidades
- Aluno, Prova, Cartão, Gabarito, Questão, Nota, Leitura OMR

### Permissões
- Professor (seus alunos), Coordenador, Diretor, Aluno (seu próprio resultado)

---

## 21 — resultado-dinamico.html

**Título:** Resultado Dinâmico

**Descrição:** Variante do resultado com dados carregados dinamicamente.

---

## 22 — relatorio-prova.html

**Título:** Gabarito360 — Relatório da Prova

**Descrição:** Relatório consolidado de uma prova com resultados de todos os alunos.

### Componentes

| Componente           | Descrição                                               |
|----------------------|---------------------------------------------------------|
| KPIs (4)            | Média da prova, Aprovação (%), Cartões corrigidos, Pendências |
| Acertos por tema     | Gráfico de barras por assunto                          |
| Donut aproveitamento | Média geral em % com meta da rede                     |
| Tabela por aluno     | Nome, Turma, Nota, Status (Aprovado/Recuperação), Ver prova |

### Entidades
- Prova, Aluno, Nota, Turma, Gabarito

### Permissões
- Professor (suas provas), Coordenador, Diretor

---

## 23 — relatorio-turma-prova.html

**Título:** Relatório da Turma por Prova

**Descrição:** Visão do desempenho de uma turma em uma prova específica.

### Entidades
- Turma, Prova, Aluno, Nota

---

## 24 — perfil.html

**Título:** Meu Perfil

**Descrição:** Página de visualização e edição dos dados pessoais do usuário logado.

### Campos Esperados
- Nome completo
- E-mail institucional
- CPF
- Perfil/cargo
- Escola/instituição vinculada
- Foto (avatar)
- Senha (alteração)

### Entidades
- Usuario

### Permissões
- Todos os perfis (vê e edita apenas seu próprio perfil)

---

## 25 — configuracoes.html

**Título:** Configurações do Sistema

**Descrição:** Painel de configurações gerais do sistema.

### Seções Esperadas
- Integrações (SEGES e outras)
- Calendário letivo
- Parâmetros institucionais
- Parâmetros de avaliação (padrões de alternativas, nota máxima, etc.)
- Notificações

### Permissões
- Administrador da Rede (completo)
- Diretor Escolar (configurações da escola)

---

## 26 — perfis-equipe.html

**Título:** Perfis da Equipe

**Descrição:** Gestão de usuários e seus perfis dentro de uma escola.

### Componentes Esperados
- Lista de membros com perfil, escola, status
- Ações: editar perfil, desativar acesso

### Entidades
- Usuario, Escola, Perfil

### Permissões
- Diretor Escolar, Coordenador, Admin

---

## 27 — membro-cadastrar.html

**Título:** Cadastrar Membro

**Descrição:** Formulário para adicionar novo membro à equipe de uma escola.

### Entidades
- Usuario, Escola

---

## 28 — membro-editar.html

**Título:** Editar Membro

**Descrição:** Formulário para editar dados e perfil de um membro existente.

### Entidades
- Usuario, Escola

---

## 29 — dashboard.html

**Título:** Dashboard Genérico

**Descrição:** Dashboard padrão (possivelmente redirecionado conforme perfil do usuário).

---

## 30 — Telas Novas do SaaS Multi-Tenant (sem mockup — pendência de aprovação visual)

**Decisão registrada em 2026-06-18:** o sistema passa a ser vendido como SaaS, com
cadastro autônomo individual/institucional e cobrança recorrente (ver
`docs/06-regras-de-negocio.md` RN-015/RN-016 e `docs/13-roadmap.md` MP-025 a MP-031).
As telas abaixo não têm mockup HTML correspondente em `mockups/` — serão construídas
seguindo o Design System gov.br já em uso, com a mesma abordagem usada para telas
anteriores sem mockup (`provas/show`, `relatorio-prova`/`relatorio-turma-prova`).
**Antes da implementação de cada uma, vale uma revisão visual rápida com o usuário**,
mesmo sem mockup HTML formal, dado que envolvem fluxos novos (cadastro, pagamento).

| Tela                          | Perfil/Contexto                          | MP de origem |
|--------------------------------|-------------------------------------------|--------------|
| Cadastro expandido (escolha Individual/Institucional) | Público (pré-login) | MP-028 |
| Página de Planos/Preços        | Público (pré-login)                       | MP-027       |
| Checkout (escolher plano + pagamento) | Público (pré-login)                | MP-027       |
| Minha Assinatura               | Titular da conta                          | MP-027       |
| Painel da Secretaria           | SECRETARIO_EDUCACAO                       | MP-026       |
| Painel do Aplicador            | APLICADOR                                 | MP-026       |
| Tela de migração/absorção de rede individual | ADMIN_REDE/DIR_NUCLEO (gera convite), PROFESSOR titular (confirma) | MP-029 |

---

## Resumo das Entidades do Domínio

| Entidade     | Telas Onde Aparece                                                          |
|--------------|-----------------------------------------------------------------------------|
| Rede         | dashboard-admin                                                              |
| Nucleo       | dashboard-admin, dashboard-diretor-nucleo                                   |
| Escola       | escolas, escola-detalhe, dashboards, turmas                                 |
| Turma        | turmas, turma-detalhe-2, dashboards, criar-prova                            |
| Aluno        | turma-detalhe-2, aluno-detalhe, aluno-cadastrar, resultado, dashboards       |
| Professor    | perfis-equipe, membro-cadastrar, dashboards                                 |
| Prova        | provas, criar-prova, gabarito, acompanhar-correcao, relatorio-prova         |
| Gabarito     | criar-prova, gabarito, acompanhar-correcao, resultado                       |
| Cartão       | acompanhar-correcao, resultado                                              |
| Nota         | resultado, aluno-detalhe, relatorio-prova, dashboards                       |
| Leitura OMR  | acompanhar-correcao, resultado                                              |
| Usuario      | login, perfil, perfis-equipe, membro-cadastrar, configuracoes               |
| Visita       | dashboard-diretor-nucleo                                                    |
| Secretaria   | painel da secretaria (novo, sem mockup — seção 30)                         |
| Plano/Assinatura/Pagamento | página de planos, checkout, minha assinatura (novos, sem mockup — seção 30) |
| Agenda       | dashboard-coordenador                                                       |
| Integração   | dashboard-admin, configuracoes                                              |

---

## Resumo dos Fluxos Principais

### Fluxo 1 — Autenticação
login.html → dashboard do perfil

### Fluxo 2 — Ciclo de Avaliação Completo
criar-prova.html → gabarito.html → acompanhar-correcao.html → resultado.html → relatorio-prova.html

### Fluxo 3 — Gestão de Escola
escolas.html → escola-detalhe.html → turmas.html → turma-detalhe-2.html → aluno-detalhe.html

### Fluxo 4 — Gestão de Aluno
turma-detalhe-2.html → aluno-detalhe.html → aluno-editar.html

### Fluxo 5 — Acompanhamento Pedagógico (Coordenador)
dashboard-coordenador.html → acompanhar-correcao.html → resultado.html

### Fluxo 6 — Visão Gerencial (Admin)
dashboard-admin.html → escolas.html → relatorio-prova.html

---

## Componentes Transversais (Presentes em Todas as Telas Autenticadas)

| Componente    | Descrição                                                   |
|---------------|-------------------------------------------------------------|
| GovBar        | Faixa gov.br com Acessibilidade e Alto Contraste           |
| App Header    | Logo, Nav, Badge contexto, Toggle tema, Menu usuário       |
| Breadcrumb    | Trilha hierárquica de navegação                            |
| Badge status  | Sistema unificado: success/warn/danger/info/muted          |
| Mini-bar      | Barra de progresso compacta para notas e contadores        |
| Avatar        | Iniciais do usuário em círculo ou quadrado                 |
| Toast         | Notificação flutuante de feedback (success/warn/error)     |
| Modal         | Dialog centralizado para formulários e confirmações        |
| Donut chart   | Gráfico circular com percentual para notas e progresso     |
| Bar chart     | Gráfico de barras horizontal para rankings e comparativos  |
