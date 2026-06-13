# Telas: Dashboards por ator (`dashboard*.html`)

- **Rotas web:** `/painel` (resolve para o painel do ator autenticado)
- **Módulo:** Dashboards
- **Atores:** genérico/coordenação de rede, admin, aluno, coordenador, diretor
  escolar, diretor de núcleo, professor.
- **Objetivo:** dar a cada ator a visão consolidada do seu escopo, com KPIs,
  gráficos, listas operacionais, agenda/alertas e ações rápidas.
- **Shell:** ver [`_shell.md`](_shell.md) (govbar, header, navegação, menu,
  breadcrumb, tema). Aqui mapeia-se apenas o conteúdo específico de cada painel.

## Padrão comum a todos os painéis

- **Cabeçalho de página:** eyebrow + título de saudação/seção + subtítulo com
  identidade/escopo, e um **botão primário** de ação principal (varia por ator).
- **Grid de KPIs** (`.kpi-grid`, 4 cartões): rótulo, valor e tendência
  (▲ up / ▼ down / ● neutro), com cor de alerta (warn/danger) quando aplicável.
- **Grid principal** (`.main-grid`/`.content-grid`, ~2fr/1fr): bloco maior
  (gráfico de barras `data-bars` ou tabela) + coluna lateral (listas).
- **Ações rápidas** (`.quick-actions`, cartões `.qa`) ao final.
- **Gráficos `data-bars`** exigem alternativa acessível (tabela/aria) — WCAG.

### Estados (todos os painéis)

`loading` (carregando KPIs/gráficos), `empty` (ator sem dados no escopo),
`error` (falha de carga com repetição), `success` (dados reais), `default`,
`hover`/`focus`/`active` nos cartões/links, `access_denied` (KPI/lista fora do
escopo não é exibida). Indicadores estáticos do protótipo viram consultas reais.

### Responsividade (todos)

KPI-grid 4→2→1 colunas; grids principais colapsam para 1 coluna em ≤1080px e
ações rápidas em ≤640–720px. Sem rolagem horizontal nos 9 viewports.

---

## 1. Painel genérico (`dashboard.html`) — coordenação de rede

- **Botão principal:** "+ Nova prova" (`/provas/criar`).
- **KPIs:** Provas aplicadas (128, ▲12 semana); Cartões corrigidos (9.842, ▲8,3%);
  Média geral (6,8, ▼0,2); Pendências de leitura (37, alerta warn).
- **Bloco principal:** gráfico "Desempenho médio por disciplina" (barras: Port.,
  Mat. [destaque vermelho], Ciên., Hist., Geo., Inglês) + texto de insight.
- **Lateral:** "Atividade recente" (lista de eventos: prova corrigida, gabarito
  publicado, cartões ambíguos, turma importada — com ícone, descrição e tempo).
- **Ações rápidas:** Criar prova e gabarito; Capturar cartões (app); Gerenciar turmas.

## 2. Painel admin (`dashboard-admin.html`) — visão da rede

- **Botão principal:** "Gerar visão executiva".
- **Faixa `notice`:** identificação do sistema/período letivo.
- **KPIs:** Total de Escolas (48); Total de Alunos (12.840); Provas no mês (312);
  Média Geral da Rede (6,8, meta 7,0).
- **Bloco principal:** "Desempenho médio por escola" (Top 5, barras com cores de
  status) + insight.
- **Lateral:** "Alertas críticos" (cartões: cartões pendentes, escolas abaixo da
  meta, integração com atraso) com badge de contagem.
- **Tabela:** "Últimos acessos registrados" (Nome, Perfil, Escola, Último acesso,
  Status [Online/Ativo/Sincronizado/Atenção/Offline]) + botão "Gerenciar usuários".
- **Ações rápidas:** Nova Escola; Gerenciar Usuários; Gerar Relatório; Configurações.

## 3. Painel aluno (`dashboard-aluno.html`)

- **Welcome-card:** avatar + saudação + identidade (aluno, turma, escola).
- **Navegação reduzida** (Painel, Minhas Provas).
- **KPIs:** Provas realizadas (14); Minha média (7,4, badge desempenho); Melhor
  disciplina (História, 9,2); Próxima prova (data + disciplina/horário).
- **Bloco principal:** tabela "Minhas notas" (Disciplina, Última prova, Nota,
  Média bimestral, Tendência ↑/→/↓).
- **Lateral:** "Evolução bimestral visual" (barras por bimestre + cartões de
  bimestres aguardando); "Próximas provas" (lista com disciplina/professor/data);
  "Mensagem de incentivo".
- **Sem ações rápidas** (perfil de consulta do próprio desempenho).

## 4. Painel coordenador (`dashboard-coordenador.html`) — escola

- **Botão principal:** "Criar prova".
- **KPIs:** Provas em andamento (8); Cartões pendentes de leitura (43, warn);
  Alunos abaixo da média (67, danger); Próximas provas (5).
- **Bloco principal:** tabela "Provas em andamento" (Disciplina+subtítulo,
  Professor, Turma, Total alunos, Cartões lidos, Status).
- **Lateral:** "Alunos em atenção" (lista com inicial, turma, disciplina e badge
  de média); "Agenda da semana" (eventos com ícone, descrição e data/hora).
- **Ações rápidas:** Criar Prova; Acompanhar Correções; Ver Turmas.

## 5. Painel diretor escolar (`dashboard-diretor-escolar.html`)

- **Botão principal:** "Ver turmas ativas".
- **School-banner:** nome da unidade, INEP, etapa, turnos, badge "Escola Ativa".
- **KPIs:** Turmas Ativas (12); Total de Alunos (387, frequência 93%); Provas
  Aplicadas (24); Média da Escola (7,2).
- **Bloco principal:** tabela "Desempenho por turma" (Turma, Professor, Alunos,
  Última Prova, Média, Status).
- **Lateral:** "Equipe da escola" (cards de gestão/docentes); "Calendário de
  provas próximas" (aplicações agendadas com badge de situação).
- **Ações rápidas:** Gerenciar Equipe; Ver Turmas; Relatório da Escola.

## 6. Painel diretor de núcleo (`dashboard-diretor-nucleo.html`)

- **Botão principal:** "Agendar reunião de acompanhamento".
- **KPIs:** Escolas no Núcleo (12); Alunos (3.240); Provas Realizadas (86);
  Média do Núcleo (6,5, ▼0,1 vs meta).
- **Bloco principal:** tabela "Comparativo das escolas do núcleo" (Escola,
  Turmas, Alunos, Média, Tendência ▲/●/▼, Status).
- **Lateral:** "Evolução bimestral" (barras por bimestre + projeção);
  "Visitas programadas" (lista com escola, data/tipo e badge de prioridade).
- **Ações rápidas:** Ver todas as Escolas; Relatório Comparativo; Agendar Visita.

## 7. Painel professor (`dashboard-professor.html`)

- **Botão principal:** "+ Nova Prova".
- **KPIs:** Minhas provas (18); Cartões p/ corrigir (12, warn); Minhas turmas
  (3); Média das turmas (6,4, ▼0,3).
- **Bloco principal:** tabela "Minhas provas" (Prova, Turma, Data, Total alunos,
  Status, Ações: Ver gabarito / Resultados / Acompanhar).
- **Lateral:** "Ranking de desempenho da turma" (Top 5 e Bottom 3 com nota);
  "Alunos que precisam de atenção" (cards com tendência e badge).
- **Ações rápidas:** Nova Prova; Capturar Cartões; Ver Turmas.

---

## Regras de negócio (dashboards)

- Cada painel exibe **apenas o escopo do ator** (núcleo → escola → turma → aluno).
- KPIs, gráficos, listas e agendas vêm de consultas reais com autorização; nada
  de números fixos do protótipo.
- Aluno tem visão somente de consulta do próprio desempenho.
- Ações rápidas respeitam permissão (ex.: "Gerenciar Usuários" só admin/gestão).
- Agenda/visitas/alertas alimentam-se de `eventos_agenda`, `atividades_recentes`,
  `notificacoes` e `snapshots_indicadores`.

## Endpoints `/api/v2` necessários

- `GET /api/v2/dashboards/{ator}` — composição do painel do ator (KPIs, blocos).
- `GET /api/v2/dashboards/{ator}/kpis` — indicadores do escopo.
- `GET /api/v2/dashboards/{ator}/desempenho` — séries para gráficos (disciplina/
  escola/turma/bimestre), com payload acessível.
- `GET /api/v2/atividades-recentes` — feed de atividade.
- `GET /api/v2/agenda?escopo=...` — agenda/visitas/aplicações próximas.
- `GET /api/v2/alertas` — alertas críticos (pendências, metas, integrações).
- Ações rápidas reutilizam endpoints dos respectivos módulos (provas, turmas,
  correção, escolas, relatórios, configurações).

## Pendências/decisões

- Definir a resolução de `/painel` → painel do ator conforme perfil/escopo.
- Padronizar o contrato `data-bars` para um endpoint de série acessível.
- Consolidar agenda/alertas/atividades como serviços compartilhados entre painéis.
- Conteúdos institucionais (faixa do admin, mensagem de incentivo do aluno):
  configuráveis, não fixos.
