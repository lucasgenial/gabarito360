# Matriz Funcional do Mockup Web

## 1. Finalidade

Este documento transforma as 30 telas de `style-system/` em referência funcional
para a refatoração. O mockup define intenção, navegação, hierarquia e estados,
mas não substitui autorização, validação, LGPD, contratos da API ou regras de
negócio.

## 2. Fontes analisadas

- 30 telas HTML.
- `style-system/css/gov.css`.
- `style-system/js/app.js`.
- `style-system/DESIGN-MANIFEST.json`.
- `style-system/DESIGN-HANDOFF.md`.
- Cinco imagens de revisão visual.

O protótipo possui CSS inline em todas as telas, JavaScript inline e dados
estáticos. Na aplicação real, esses elementos devem virar componentes, services,
consultas e estados testáveis.

## 3. Telas por módulo

| Módulo | Telas de referência | Funcionalidades identificadas | Situação atual |
|---|---|---|---|
| Acesso | `login.html` | login, manter conectado e recuperação; cadastro é apenas ilustrativo | Login existe; cadastro aberto foi rejeitado e recuperação integra o MVP |
| Dashboards | `dashboard*.html` | visão por administrador, aluno, coordenador, diretor escolar, diretor de núcleo e professor | Apenas painel organizacional mínimo existe |
| Escolas | `escolas.html`, `escola-detalhe.html` | busca, cadastro, edição, reativação, visão consolidada, turmas, provas, alunos e equipe | CRUD básico existe; detalhe consolidado não |
| Equipe e perfis | `membro-cadastrar.html`, `membro-editar.html`, `perfis-equipe.html` | dados profissionais, lotação, disciplinas, turmas, perfis e permissões | Usuários e perfis existem parcialmente |
| Turmas | `turmas.html`, `turma-detalhe-2.html` | filtros, importação, KPIs, provas, alunos, desempenho e frequência | API e matrículas existem; telas e métricas não |
| Alunos | `aluno-cadastrar-redesign.html`, `aluno-detalhe.html`, `aluno-editar.html` | cadastro, foto, matrícula, responsável, histórico, evolução e PDF | API existe parcialmente; responsável, foto e telas não |
| Provas e gabaritos | `provas.html`, `criar-prova.html`, `gabarito.html` | disciplina, série, padrões, rascunho, publicação, editor de respostas e PDF | Backend central existe; telas e campos adicionais não |
| Correção | `acompanhar-correcao.html`, `acompanhar-correcao-turma.html` | progresso, pendências, ambiguidade, revisão e atividade recente | Ainda não implementado |
| Resultados e relatórios | `resultado-dinamico.html`, `relatorio-prova.html`, `relatorio-turma-prova.html` | nota, acertos por tema, respostas, evolução, desempenho e PDF | Ainda não implementado |
| Perfil e configurações | `perfil.html`, `configuracoes.html` | dados pessoais, senha, sessões, notificações, aparência, acessibilidade, integrações e LGPD | Ainda não implementado |

## 4. Arquivos duplicados ou não canônicos

| Arquivo | Decisão |
|---|---|
| `aluno-cadastrar.html` | Manter como histórico; usar `aluno-cadastrar-redesign.html` como referência canônica |
| `resultado.html` | Manter como histórico; usar `resultado-dinamico.html` como referência canônica |
| `dashboard.html` | Usar como referência compartilhada; a rota real deve selecionar dashboard por contexto/perfil |
| `app-android.html` | Link quebrado do protótipo; não existe e não deve ser criado na aplicação web |

## 5. Novos conceitos de domínio identificados

O modelo atual precisa avaliar ou incorporar:

- disciplinas;
- períodos letivos e bimestres;
- séries/anos normalizados;
- temas ou habilidades vinculados às questões;
- responsáveis de alunos;
- foto/arquivo de perfil com retenção e autorização;
- equipe escolar e dados profissionais;
- cargos institucionais separados de perfis de autorização;
- vínculos de usuários com disciplinas e turmas;
- configurações e preferências por usuário;
- preferências de notificação;
- integrações externas;
- solicitações LGPD e sessões ativas;
- aplicações, leituras, ambiguidades, respostas detectadas e resultados;
- agendas e eventos, somente após validação de escopo.

## 6. Perfis representados

O mockup apresenta:

- administrador;
- diretor de núcleo;
- diretor escolar;
- vice-diretor;
- coordenador pedagógico;
- professor;
- aluno.

Cargo institucional e perfil de autorização não devem ser o mesmo conceito.
Aplicador, consulta e suporte técnico continuam necessários mesmo sem dashboard
próprio no mockup.

## 7. Classificação das funcionalidades

### Obrigatórias para a primeira aplicação web funcional

- login seguro;
- dashboard coerente com o perfil;
- escolas e equipe;
- turmas e alunos;
- provas e gabaritos;
- acompanhamento de correção;
- resultado do aluno;
- relatório por prova e por turma;
- perfil, senha e alternância de tema;
- tema claro padrão e responsividade.

### Decisões fechadas na R1

| Tema | Decisão |
|---|---|
| Auto-cadastro | Não implementar; usuários são provisionados ou convidados por gestores autorizados |
| gov.br | Adiado; não alegar integração ou vinculação oficial |
| Dashboard do aluno | V2; resultados do MVP são consultados por profissionais autorizados |
| Agenda e reuniões | V2; MVP exibe apenas provas e aplicações registradas |
| Integrações externas | V2 e somente após aprovação específica |
| Plano, faturamento e limites | Fora do escopo atual |
| Exportação integral e idiomas | V2; MVP opera em pt-BR |
| PDF | MVP para relatórios canônicos de aluno, prova e turma/prova |
| XLSX | V2 |
| Criação de provas por professor | Permitida somente com permissão explícita e escopo concedido |

As decisões completas estão na
[ADR-D013](decisoes/ADR-D013-contrato-produto-web-r1.md) e o contrato de rotas,
atores, permissões, dados e estados está em
[15-mapa-rotas-web.md](15-mapa-rotas-web.md).

### Ações do protótipo que não devem ser copiadas literalmente

- exclusão permanente de aluno, membro ou histórico auditável;
- acesso a relatórios sem policy e escopo;
- uso de dados pessoais fictícios como estrutura de produção;
- alegação de vínculo oficial com o Governo Federal sem validação institucional;
- indicadores estáticos apresentados como dados reais.

## 8. Contrato visual

- Tema inicial: claro.
- Tema escuro: opcional por botão e preferência persistida.
- Cabeçalho institucional, navegação principal, breadcrumb, menu de usuário,
  cards, tabelas, filtros, badges, KPIs e gráficos são componentes compartilhados.
- Navegação deve adaptar-se a celular, tablet e desktop sem rolagem horizontal da
  página.
- Toda tela deve possuir estados de carregamento, vazio, erro, sucesso e acesso
  negado quando aplicável.
- O mockup define composição; os tokens oficiais definem valores.

## 9. Fechamento da R1

- As 30 telas HTML foram classificadas como canônicas, históricas, referência
  compartilhada ou referência V2.
- Toda tela canônica possui rota, ator, permissão, dados e estados definidos em
  [15-mapa-rotas-web.md](15-mapa-rotas-web.md).
- Duplicidades e funcionalidades adiadas foram decididas na
  [ADR-D013](decisoes/ADR-D013-contrato-produto-web-r1.md).
- A modelagem necessária às telas está definida para MariaDB em
  [06-modelagem-banco.md](06-modelagem-banco.md).
