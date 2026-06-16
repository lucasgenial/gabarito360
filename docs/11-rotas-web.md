# 11 — Rotas do WEB

A aplicação WEB (apps/web) é um frontend Laravel que consome a API.
Todas as rotas retornam views HTML (Blade templates).

---

## Convenção de Nomenclatura

- Rotas em português (para URLs amigáveis e alinhadas com o contexto educacional)
- Padrão: `/modulo/acao` ou `/modulo/{id}/acao`
- Rotas autenticadas protegidas por middleware de autenticação

---

## Autenticação (Pública)

| Método | Rota               | Controller                | View                | Descrição                    |
|--------|--------------------|---------------------------|---------------------|------------------------------|
| GET    | /login             | Auth\LoginController      | auth.login          | Tela de login                |
| POST   | /login             | Auth\LoginController      | —                   | Processar login              |
| POST   | /logout            | Auth\LoginController      | —                   | Logout                       |
| GET    | /cadastro          | Auth\RegisterController   | auth.login          | Tela de cadastro (tab)       |
| POST   | /cadastro          | Auth\RegisterController   | —                   | Processar cadastro           |
| GET    | /esqueci-senha     | Auth\PasswordController   | auth.forgot         | Solicitar reset de senha     |
| POST   | /esqueci-senha     | Auth\PasswordController   | —                   | Enviar e-mail de reset       |
| GET    | /redefinir-senha   | Auth\PasswordController   | auth.reset          | Tela de nova senha           |
| POST   | /redefinir-senha   | Auth\PasswordController   | —                   | Salvar nova senha            |

**Mockup:** login.html

---

## Dashboard (Autenticado — Redirecionamento por Perfil)

| Método | Rota     | Controller             | View                  | Descrição                              |
|--------|----------|------------------------|-----------------------|----------------------------------------|
| GET    | /painel  | DashboardController    | dashboard.{perfil}    | Redireciona para dashboard do perfil   |

**Lógica:** O controller detecta o perfil do usuário autenticado e renderiza a view correspondente.

| Perfil       | View                          | Mockup                        |
|--------------|-------------------------------|-------------------------------|
| ADMIN_REDE   | dashboard.admin               | dashboard-admin.html          |
| DIR_NUCLEO   | dashboard.diretor-nucleo      | dashboard-diretor-nucleo.html |
| DIR_ESCOLAR  | dashboard.diretor-escolar     | dashboard-diretor-escolar.html|
| COORDENADOR  | dashboard.coordenador         | dashboard-coordenador.html    |
| PROFESSOR    | dashboard.professor           | dashboard-professor.html      |
| ALUNO        | dashboard.aluno               | dashboard-aluno.html          |

---

## Escolas

| Método | Rota                    | Controller             | View              | Mockup             |
|--------|-------------------------|------------------------|-------------------|--------------------|
| GET    | /escolas                | EscolaController       | escolas.index     | escolas.html       |
| GET    | /escolas/{id}           | EscolaController       | escolas.show      | escola-detalhe.html|
| POST   | /escolas                | EscolaController       | —                 | (modal AJAX)       |
| PUT    | /escolas/{id}           | EscolaController       | —                 | (modal AJAX)       |
| POST   | /escolas/{id}/ativar    | EscolaController       | —                 | (AJAX)             |
| POST   | /escolas/{id}/desativar | EscolaController       | —                 | (AJAX)             |

---

## Turmas

| Método | Rota                  | Controller         | View              | Mockup                  |
|--------|-----------------------|--------------------|-------------------|-------------------------|
| GET    | /turmas               | TurmaController    | turmas.index      | turmas.html             |
| GET    | /turmas/{id}          | TurmaController    | turmas.show       | turma-detalhe-2.html    |
| POST   | /turmas               | TurmaController    | —                 | (modal AJAX)            |
| PUT    | /turmas/{id}          | TurmaController    | —                 | (modal AJAX)            |
| POST   | /turmas/importar      | TurmaController    | —                 | (upload AJAX)           |

---

## Alunos

| Método | Rota                              | Controller       | View              | Mockup                    |
|--------|-----------------------------------|------------------|-------------------|---------------------------|
| GET    | /alunos/cadastrar                 | AlunoController  | alunos.create     | aluno-cadastrar.html      |
| POST   | /alunos                           | AlunoController  | —                 | —                         |
| GET    | /alunos/{id}                      | AlunoController  | alunos.show       | aluno-detalhe.html        |
| GET    | /alunos/{id}/editar               | AlunoController  | alunos.edit       | aluno-editar.html         |
| PUT    | /alunos/{id}                      | AlunoController  | —                 | —                         |
| GET    | /alunos/{id}/ficha                | AlunoController  | —                 | (PDF)                     |

---

## Membros / Equipe

| Método | Rota                      | Controller         | View                 | Mockup               |
|--------|---------------------------|--------------------|----------------------|----------------------|
| GET    | /equipe                   | MembroController   | equipe.index         | perfis-equipe.html   |
| GET    | /equipe/cadastrar         | MembroController   | equipe.create        | membro-cadastrar.html|
| POST   | /equipe                   | MembroController   | —                    | —                    |
| GET    | /equipe/{id}/editar       | MembroController   | equipe.edit          | membro-editar.html   |
| PUT    | /equipe/{id}              | MembroController   | —                    | —                    |
| POST   | /equipe/{id}/desativar    | MembroController   | —                    | (AJAX)               |

---

## Provas

| Método | Rota                          | Controller        | View              | Mockup                    |
|--------|-------------------------------|-------------------|-------------------|---------------------------|
| GET    | /provas                       | ProvaController   | provas.index      | provas.html               |
| GET    | /provas/criar                 | ProvaController   | provas.create     | criar-prova.html          |
| POST   | /provas                       | ProvaController   | —                 | —                         |
| GET    | /provas/{id}/editar           | ProvaController   | provas.edit       | criar-prova.html          |
| PUT    | /provas/{id}                  | ProvaController   | —                 | —                         |
| POST   | /provas/{id}/publicar         | ProvaController   | —                 | (AJAX)                    |
| GET    | /provas/{id}/gabarito         | ProvaController   | provas.gabarito   | gabarito.html             |
| GET    | /provas/{id}/gabarito/pdf     | ProvaController   | —                 | (PDF via print)           |
| GET    | /provas/{id}/acompanhar       | ProvaController   | provas.acompanhar | acompanhar-correcao.html  |
| GET    | /provas/{id}/relatorio        | ProvaController   | provas.relatorio  | relatorio-prova.html      |

---

## Acompanhamento de Correção

| Método | Rota                              | Controller          | View                        | Mockup                          |
|--------|-----------------------------------|---------------------|-----------------------------|---------------------------------|
| GET    | /provas/{id}/acompanhar           | CorrecaoController  | correcao.show               | acompanhar-correcao.html        |
| GET    | /turmas/{id}/acompanhar           | CorrecaoController  | correcao.turma              | acompanhar-correcao-turma.html  |
| POST   | /cartoes/{id}/resolver-ambiguidade| CorrecaoController  | —                           | (AJAX)                          |
| POST   | /cartoes/{id}/revisar             | CorrecaoController  | —                           | (AJAX)                          |

---

## Resultados e Relatórios

| Método | Rota                                        | Controller          | View                    | Mockup                      |
|--------|---------------------------------------------|---------------------|-------------------------|-----------------------------|
| GET    | /resultados/{aluno_id}/{prova_id}           | ResultadoController | resultados.show         | resultado.html              |
| GET    | /resultados/{aluno_id}/{prova_id}/pdf       | ResultadoController | —                       | (PDF)                       |
| GET    | /relatorios/turma/{turma_id}/prova/{prova_id}| RelatorioController | relatorios.turma-prova  | relatorio-turma-prova.html  |
| GET    | /relatorios/escola/{escola_id}              | RelatorioController | relatorios.escola       | —                           |

---

## Perfil e Configurações

| Método | Rota              | Controller            | View                  | Mockup              |
|--------|-------------------|-----------------------|-----------------------|---------------------|
| GET    | /perfil           | PerfilController      | perfil.show           | perfil.html         |
| PUT    | /perfil           | PerfilController      | —                     | —                   |
| GET    | /configuracoes    | ConfiguracaoController| configuracoes.index   | configuracoes.html  |
| PUT    | /configuracoes    | ConfiguracaoController| —                     | —                   |

---

## Visitas (Diretor de Núcleo)

| Método | Rota              | Controller        | View             | Mockup                       |
|--------|-------------------|-------------------|------------------|------------------------------|
| GET    | /visitas          | VisitaController  | visitas.index    | (no dashboard do núcleo)     |
| POST   | /visitas          | VisitaController  | —                | (AJAX/modal)                 |
| PUT    | /visitas/{id}     | VisitaController  | —                | (AJAX)                       |
| DELETE | /visitas/{id}     | VisitaController  | —                | (AJAX)                       |

---

## Notas sobre Implementação

### JavaScript / AJAX
Ações que não recarregam a página completa (identificadas nos mockups):
- Criação/edição de escolas (modal)
- Resolução de ambíguos (inline)
- Atualização de leitura (polling ou WebSocket)
- Busca em tempo real nas listagens
- Toggle de tema claro/escuro

### Middleware
- `auth`: todas as rotas autenticadas
- `checkPerfil`: valida se o perfil tem acesso à rota
- `checkEscopo`: valida se o usuário tem acesso ao recurso específico

### Redirecionamentos após Login
```
ADMIN_REDE   → /painel → dashboard-admin
DIR_NUCLEO   → /painel → dashboard-diretor-nucleo
DIR_ESCOLAR  → /painel → dashboard-diretor-escolar
COORDENADOR  → /painel → dashboard-coordenador
PROFESSOR    → /painel → dashboard-professor
ALUNO        → /painel → dashboard-aluno
```
