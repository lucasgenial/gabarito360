# 13 — Roadmap

---

## Micro-Passos (MPs)

Cada MP é uma unidade atômica de implementação com critérios de aceite claros.
Nenhum MP pode ser iniciado sem que suas dependências estejam concluídas.

---

## MP-001 — Estrutura do Repositório

**Objetivo:** Criar a estrutura base do monorepo com diretórios oficiais.

**Dependências:** Nenhuma

**Entregáveis:**
- apps/api/ (Laravel instalado)
- apps/web/ (Laravel instalado)
- apps/android/ (React Native configurado)
- apps/ios/ (React Native configurado)
- docs/ (documentação inicial)
- .gitignore configurado
- README.md

**Critérios de Aceite:**
- [ ] Cada app executa seu comando de start sem erros
- [ ] Estrutura de diretórios confirmada
- [ ] Repositório limpo (sem node_modules, vendor, .env no git)

**Commit esperado:** `chore: inicializa estrutura do monorepo com apps API, WEB, Android e iOS`

---

## MP-002 — Documentação Consolidada ✅ (Este MP)

**Objetivo:** Criar toda a documentação oficial do produto antes de qualquer código.

**Dependências:** Nenhuma

**Entregáveis:**
- CLAUDE.md
- docs/00 a docs/16

**Critérios de Aceite:**
- [x] Todos os mockups inventariados
- [x] Perfis e permissões documentados
- [x] Modelo de dados conceitual criado
- [x] Rotas API e WEB definidas
- [x] Roadmap criado
- [x] GAP Analysis criada

**Commit esperado:** `docs: cria documentação oficial completa do produto`

---

## MP-003 — Modelagem Conceitual e Banco de Dados

**Objetivo:** Criar o banco de dados MariaDB com todas as entidades do MVP.

**Dependências:** MP-001, MP-002

**Entregáveis:**
- Migrations para todas as entidades (ver docs/09-modelo-de-dados.md)
- Seeders para dados de desenvolvimento (rede, núcleo, escolas, turmas, alunos, usuários por perfil)
- Diagrama ER gerado

**Migrations (em ordem):**
1. create_redes_table
2. create_nucleos_table
3. create_escolas_table
4. create_turmas_table
5. create_alunos_table
6. create_usuarios_table
7. create_usuario_escopos_table
8. create_provas_table
9. create_prova_turmas_table
10. create_gabaritos_table
11. create_gabarito_questoes_table
12. create_cartoes_table
13. create_cartao_respostas_table
14. create_notas_table
15. create_ambiguidade_logs_table
16. create_visitas_table
17. create_sincronizacoes_seges_table

**Critérios de Aceite:**
- [ ] `php artisan migrate` executa sem erros
- [ ] `php artisan db:seed` popula dados de dev
- [ ] Todas as FKs com índices corretos
- [ ] Dados de seed incluem ao menos 1 registro de cada perfil

**Commit esperado:** `db: cria migrations e seeders completos do MVP`

---

## MP-004 — Arquitetura da API (Base)

**Objetivo:** Configurar a API Laravel com autenticação, estrutura de rotas e padrão de resposta.

**Dependências:** MP-003

**Entregáveis:**
- Laravel Sanctum configurado
- Estrutura de rotas em routes/api.php
- AuthController (login, logout, me)
- Middleware de autenticação e autorização
- Padrão de resposta JSON (success/error)
- Policies base para cada perfil

**Critérios de Aceite:**
- [ ] POST /api/v1/auth/login retorna token e dados do usuário
- [ ] GET /api/v1/auth/me retorna dados do usuário autenticado
- [ ] POST /api/v1/auth/logout invalida o token
- [ ] Rotas protegidas retornam 401 sem token
- [ ] Rotas fora do escopo retornam 403

**Commit esperado:** `feat(api): implementa autenticação base com Sanctum e RBAC`

---

## MP-005 — Arquitetura do WEB (Base)

**Objetivo:** Configurar o WEB Laravel com layout base, autenticação e estrutura de views.

**Dependências:** MP-004

**Entregáveis:**
- Layout base Blade (com GovBar, Header, Nav, Breadcrumb)
- Implementação dos tokens CSS do gov.br (gov.css integrado)
- Middleware de autenticação WEB
- Rotas base em routes/web.php
- Login/Logout funcional consumindo a API
- Redirecionamento para dashboard conforme perfil

**Critérios de Aceite:**
- [ ] Tela de login idêntica ao mockup login.html
- [ ] Login com credenciais válidas redireciona para dashboard do perfil
- [ ] Login com credenciais inválidas exibe mensagem de erro
- [ ] Logout funciona e redireciona para /login
- [ ] Layout base renderiza GovBar, Header e Breadcrumb corretamente
- [ ] Toggle de tema claro/escuro funciona

**Commit esperado:** `feat(web): implementa layout base e autenticação consumindo API`

---

## MP-006 — Dashboard Admin

**Objetivo:** Implementar o dashboard do Administrador da Rede.

**Dependências:** MP-004, MP-005

**Entregáveis:**
- GET /api/v1/dashboard/admin (endpoint)
- View dashboard-admin fiel ao mockup dashboard-admin.html
- KPIs: Escolas, Alunos, Provas do mês, Média da rede
- Gráfico: Top 5 escolas por desempenho
- Painel de alertas: cartões pendentes, escolas abaixo da meta, SEGES
- Tabela: últimos acessos de usuários
- Ações rápidas: 4 cards linkáveis

**Critérios de Aceite:**
- [ ] Dashboard visualmente idêntico ao mockup
- [ ] KPIs exibem dados reais do banco
- [ ] Alertas aparecem apenas quando há dados críticos
- [ ] Acesso negado para qualquer outro perfil
- [ ] Responsivo nos breakpoints definidos

**Commit esperado:** `feat(web): implementa dashboard do administrador da rede`

---

## MP-007 — Dashboard Diretor de Núcleo

**Objetivo:** Implementar o dashboard do Diretor de Núcleo.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/diretor-nucleo
- View dashboard-diretor-nucleo fiel ao mockup
- KPIs, tabela comparativa de escolas, gráfico bimestral, lista de visitas

**Critérios de Aceite:**
- [ ] Dados limitados ao escopo do núcleo do usuário
- [ ] Visitas programadas exibidas com urgência correta
- [ ] Acesso negado para outros perfis

**Commit esperado:** `feat(web): implementa dashboard do diretor de núcleo`

---

## MP-008 — Dashboard Diretor Escolar

**Objetivo:** Implementar o dashboard do Diretor Escolar.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/diretor-escolar
- View dashboard-diretor-escolar fiel ao mockup
- Banner escola, KPIs, tabela de turmas, equipe, calendário

**Commit esperado:** `feat(web): implementa dashboard do diretor escolar`

---

## MP-009 — Dashboard Coordenador

**Objetivo:** Implementar o dashboard do Coordenador.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/coordenador
- View dashboard-coordenador fiel ao mockup

**Commit esperado:** `feat(web): implementa dashboard do coordenador`

---

## MP-010 — Dashboard Professor

**Objetivo:** Implementar o dashboard do Professor.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/professor
- View dashboard-professor fiel ao mockup

**Commit esperado:** `feat(web): implementa dashboard do professor`

---

## MP-011 — Dashboard Aluno

**Objetivo:** Implementar o dashboard do Aluno.

**Dependências:** MP-006

**Entregáveis:**
- GET /api/v1/dashboard/aluno
- View dashboard-aluno fiel ao mockup

**Commit esperado:** `feat(web): implementa dashboard do aluno`

---

## MP-012 — Autenticação Completa

**Objetivo:** Completar o fluxo de autenticação com redefinição de senha e cadastro.

**Dependências:** MP-005

**Entregáveis:**
- Fluxo "Esqueci a senha" com e-mail de recuperação
- Tela de redefinição de senha
- Cadastro de usuário com aceite de LGPD

**Commit esperado:** `feat(api,web): implementa fluxo completo de autenticação`

---

## MP-013 — Perfis e Permissões

**Objetivo:** Implementar gerenciamento de membros da equipe.

**Dependências:** MP-005

**Entregáveis:**
- CRUD de usuários via API
- Telas: perfis-equipe, membro-cadastrar, membro-editar
- Ativação/desativação de membros

**Commit esperado:** `feat(api,web): implementa gestão de membros da equipe`

---

## MP-014 — Núcleos

**Objetivo:** Implementar CRUD de núcleos regionais.

**Dependências:** MP-004

**Entregáveis:**
- CRUD de núcleos via API
- (Tela WEB a definir — não há mockup específico)

**Commit esperado:** `feat(api): implementa CRUD de núcleos`

---

## MP-015 — Escolas

**Objetivo:** Implementar gestão completa de escolas.

**Dependências:** MP-014

**Entregáveis:**
- CRUD de escolas via API
- Telas: escolas.html, escola-detalhe.html
- Modal de criação/edição
- Ativar/desativar escola
- KPIs da escola
- Busca em tempo real

**Critérios de Aceite:**
- [ ] Grid de escolas idêntico ao mockup escolas.html
- [ ] Modal de criação/edição funcional
- [ ] Busca filtra em tempo real
- [ ] Escola inativa com visual diferenciado
- [ ] KPIs atualizados dinamicamente

**Commit esperado:** `feat(api,web): implementa gestão completa de escolas`

---

## MP-016 — Turmas

**Objetivo:** Implementar gestão de turmas.

**Dependências:** MP-015

**Entregáveis:**
- CRUD de turmas via API
- Telas: turmas.html, turma-detalhe-2.html
- Importação por planilha

**Commit esperado:** `feat(api,web): implementa gestão de turmas com importação por planilha`

---

## MP-017 — Alunos

**Objetivo:** Implementar gestão de alunos.

**Dependências:** MP-016

**Entregáveis:**
- CRUD de alunos via API
- Telas: aluno-cadastrar.html, aluno-detalhe.html, aluno-editar.html
- Histórico de avaliações do aluno
- Gráfico de evolução
- Exportar ficha PDF

**Commit esperado:** `feat(api,web): implementa gestão completa de alunos`

---

## MP-018 — Professores

**Objetivo:** Implementar cadastro e gestão de professores como membros da equipe.

**Dependências:** MP-013, MP-016

**Entregáveis:**
- Vínculo professor ↔ turmas
- Escopo de acesso do professor

**Commit esperado:** `feat(api,web): implementa vínculo de professores com turmas`

---

## MP-019 — Avaliações (Provas)

**Objetivo:** Implementar CRUD de provas e o stepper de criação.

**Dependências:** MP-016

**Entregáveis:**
- CRUD de provas via API
- Telas: provas.html, criar-prova.html
- Stepper de criação (3 etapas)
- Filtros: busca, disciplina, status
- Status: Rascunho, Publicada, Em correção, Corrigida
- Ação contextual por status

**Commit esperado:** `feat(api,web): implementa gestão de avaliações com stepper de criação`

---

## MP-020 — Gabaritos

**Objetivo:** Implementar o editor de gabarito e a visualização do gabarito publicado.

**Dependências:** MP-019

**Entregáveis:**
- Editor de bolhas interativo (criar-prova.html passo 2)
- Publicação do gabarito
- Tela de visualização: gabarito.html
- Exportação do gabarito em PDF
- Padrões configuráveis por prova

**Critérios de Aceite:**
- [ ] Editor de bolhas funcional e idêntico ao mockup
- [ ] Contador X/N atualiza em tempo real
- [ ] Publicação desabilitada com gabarito incompleto
- [ ] Gabarito publicado não pode ser editado
- [ ] Exportação PDF via window.print()

**Commit esperado:** `feat(api,web): implementa editor de gabarito e publicação`

---

## MP-021 — OMR (Leitura de Cartões)

**Objetivo:** Implementar o motor OMR e o fluxo de leitura de cartões.

**Dependências:** MP-020

**Entregáveis:**
- Interface OmrDriverInterface
- Implementação inicial do motor OMR
- Upload de imagem via API
- Job de processamento assíncrono
- Resolução manual de ambíguos
- Tela: acompanhar-correcao.html
- Polling a cada 5s para atualização

**Critérios de Aceite:**
- [ ] Upload de imagem aceito pela API
- [ ] OMR processa e retorna respostas com confiança
- [ ] Cartões ambíguos listados com opções de resolução
- [ ] Resolução manual atualiza contadores em tempo real
- [ ] Estado "Leitura concluída" funcional

**Commit esperado:** `feat(api,web): implementa motor OMR e fluxo de acompanhamento de correção`

---

## MP-022 — Relatórios

**Objetivo:** Implementar todos os relatórios do MVP.

**Dependências:** MP-021

**Entregáveis:**
- Resultado individual: resultado.html
- Relatório da prova: relatorio-prova.html
- Relatório turma × prova: relatorio-turma-prova.html
- Exportação PDF dos relatórios
- Revisão de leitura pós-correção

**Critérios de Aceite:**
- [ ] Resultado individual idêntico ao mockup
- [ ] Folha de respostas corrigida com cores corretas (verde/vermelho/amarelo)
- [ ] Badge Aprovado/Recuperação conforme nota
- [ ] Comparativo com média da turma exibido
- [ ] Relatório da prova com KPIs e tabela por aluno
- [ ] Breadcrumb dinâmico funcional

**Commit esperado:** `feat(api,web): implementa relatórios de resultado e prova`

---

## MP-023 — Aplicativo Android

**Objetivo:** Implementar o app Android em React Native.

**Dependências:** MP-004 (API base), MP-021 (OMR)

**Entregáveis:**
- Autenticação no app
- Dashboard do perfil correspondente
- Captura de cartões pela câmera
- Envio para OMR
- Visualização de resultados

**Commit esperado:** `feat(android): implementa app base com autenticação e captura OMR`

---

## Status dos MPs

| MP     | Título                          | Status      | Dependências       |
|--------|---------------------------------|-------------|--------------------|
| MP-001 | Estrutura do Repositório        | Pendente    | —                  |
| MP-002 | Documentação Consolidada        | Concluído   | —                  |
| MP-003 | Modelagem e Banco de Dados      | Pendente    | MP-001, MP-002     |
| MP-004 | Arquitetura da API (Base)       | Pendente    | MP-003             |
| MP-005 | Arquitetura do WEB (Base)       | Pendente    | MP-004             |
| MP-006 | Dashboard Admin                 | Pendente    | MP-004, MP-005     |
| MP-007 | Dashboard Diretor de Núcleo     | Pendente    | MP-006             |
| MP-008 | Dashboard Diretor Escolar       | Pendente    | MP-006             |
| MP-009 | Dashboard Coordenador           | Pendente    | MP-006             |
| MP-010 | Dashboard Professor             | Pendente    | MP-006             |
| MP-011 | Dashboard Aluno                 | Pendente    | MP-006             |
| MP-012 | Autenticação Completa           | Pendente    | MP-005             |
| MP-013 | Perfis e Permissões             | Pendente    | MP-005             |
| MP-014 | Núcleos                         | Pendente    | MP-004             |
| MP-015 | Escolas                         | Pendente    | MP-014             |
| MP-016 | Turmas                          | Pendente    | MP-015             |
| MP-017 | Alunos                          | Pendente    | MP-016             |
| MP-018 | Professores                     | Pendente    | MP-013, MP-016     |
| MP-019 | Avaliações (Provas)             | Pendente    | MP-016             |
| MP-020 | Gabaritos                       | Pendente    | MP-019             |
| MP-021 | OMR                             | Pendente    | MP-020             |
| MP-022 | Relatórios                      | Pendente    | MP-021             |
| MP-023 | Aplicativo Android              | Pendente    | MP-004, MP-021     |
