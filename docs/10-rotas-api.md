# 10 — Rotas da API

Todas as rotas retornam JSON. A API nunca retorna HTML.
Prefixo base: `/api/v1`
Autenticação: Bearer Token (JWT) — exceto rotas públicas.

---

## Autenticação

| Método | Rota                  | Descrição                          | Permissão |
|--------|-----------------------|------------------------------------|-----------|
| POST   | /auth/login           | Login com e-mail e senha           | Pública   |
| POST   | /auth/logout          | Invalidar token                    | Autenticado|
| POST   | /auth/register        | Cadastro de novo usuário           | Pública   |
| POST   | /auth/forgot-password | Solicitar redefinição de senha     | Pública   |
| POST   | /auth/reset-password  | Redefinir senha com token          | Pública   |
| GET    | /auth/me              | Dados do usuário autenticado       | Autenticado|
| PUT    | /auth/me              | Atualizar dados do perfil          | Autenticado|

---

## Rede

| Método | Rota          | Descrição              | Permissão      |
|--------|---------------|------------------------|----------------|
| GET    | /redes        | Listar redes           | ADMIN_REDE     |
| GET    | /redes/{id}   | Detalhe da rede        | ADMIN_REDE     |
| PUT    | /redes/{id}   | Atualizar configurações| ADMIN_REDE     |

---

## Núcleos

| Método | Rota              | Descrição              | Permissão                |
|--------|-------------------|------------------------|--------------------------|
| GET    | /nucleos          | Listar núcleos         | ADMIN_REDE, DIR_NUCLEO   |
| GET    | /nucleos/{id}     | Detalhe do núcleo      | ADMIN_REDE, DIR_NUCLEO   |
| POST   | /nucleos          | Criar núcleo           | ADMIN_REDE               |
| PUT    | /nucleos/{id}     | Atualizar núcleo       | ADMIN_REDE, DIR_NUCLEO   |
| DELETE | /nucleos/{id}     | Excluir núcleo         | ADMIN_REDE               |

---

## Escolas

| Método | Rota                      | Descrição                        | Permissão                            |
|--------|---------------------------|----------------------------------|--------------------------------------|
| GET    | /escolas                  | Listar escolas (com filtros)     | ADMIN_REDE, DIR_NUCLEO+              |
| GET    | /escolas/{id}             | Detalhe da escola                | ADMIN_REDE, DIR_NUCLEO, DIR_ESCOLAR+ |
| POST   | /escolas                  | Criar escola                     | ADMIN_REDE                           |
| PUT    | /escolas/{id}             | Atualizar escola                 | ADMIN_REDE, DIR_ESCOLAR*             |
| POST   | /escolas/{id}/ativar      | Ativar escola inativa            | ADMIN_REDE                           |
| POST   | /escolas/{id}/desativar   | Desativar escola                 | ADMIN_REDE                           |
| GET    | /escolas/{id}/kpis        | KPIs da escola                   | ADMIN_REDE, DIR_NUCLEO, DIR_ESCOLAR* |
| GET    | /escolas/{id}/turmas      | Turmas da escola                 | ADMIN_REDE, DIR_NUCLEO, DIR_ESCOLAR* |
| GET    | /escolas/{id}/membros     | Equipe da escola                 | ADMIN_REDE, DIR_ESCOLAR*             |

*Escopo limitado à própria escola

---

## Turmas

| Método | Rota                    | Descrição                      | Permissão                      |
|--------|-------------------------|--------------------------------|--------------------------------|
| GET    | /turmas                 | Listar turmas (com filtros)    | Autenticado (escopo)           |
| GET    | /turmas/{id}            | Detalhe da turma               | Autenticado (escopo)           |
| POST   | /turmas                 | Criar turma                    | ADMIN_REDE, DIR_ESCOLAR, COORD |
| PUT    | /turmas/{id}            | Atualizar turma                | ADMIN_REDE, DIR_ESCOLAR, COORD |
| POST   | /turmas/importar        | Importar turmas por planilha   | ADMIN_REDE, DIR_ESCOLAR, COORD |
| GET    | /turmas/{id}/alunos     | Alunos da turma                | Autenticado (escopo)           |
| GET    | /turmas/{id}/provas     | Provas da turma                | Autenticado (escopo)           |
| GET    | /turmas/{id}/kpis       | KPIs da turma                  | Autenticado (escopo)           |

---

## Alunos

| Método | Rota                          | Descrição                        | Permissão                     |
|--------|-------------------------------|----------------------------------|-------------------------------|
| GET    | /alunos                       | Listar alunos (com filtros)      | Autenticado (escopo)          |
| GET    | /alunos/{id}                  | Detalhe do aluno                 | Autenticado (escopo)          |
| POST   | /alunos                       | Cadastrar aluno                  | ADMIN_REDE, DIR_ESCOLAR, COORD|
| PUT    | /alunos/{id}                  | Atualizar aluno                  | ADMIN_REDE, DIR_ESCOLAR, COORD|
| GET    | /alunos/{id}/historico        | Histórico de avaliações          | Autenticado (escopo)          |
| GET    | /alunos/{id}/evolucao         | Evolução de notas por período    | Autenticado (escopo)          |
| GET    | /alunos/{id}/ficha            | Gerar ficha PDF                  | Autenticado (escopo)          |

---

## Usuários / Membros

| Método | Rota                      | Descrição                    | Permissão                |
|--------|---------------------------|------------------------------|--------------------------|
| GET    | /usuarios                 | Listar usuários (escopo)     | ADMIN_REDE, DIR_ESCOLAR  |
| GET    | /usuarios/{id}            | Detalhe do usuário           | ADMIN_REDE, DIR_ESCOLAR  |
| POST   | /usuarios                 | Criar usuário                | ADMIN_REDE, DIR_ESCOLAR  |
| PUT    | /usuarios/{id}            | Atualizar usuário            | ADMIN_REDE, DIR_ESCOLAR  |
| POST   | /usuarios/{id}/ativar     | Ativar usuário               | ADMIN_REDE, DIR_ESCOLAR  |
| POST   | /usuarios/{id}/desativar  | Desativar usuário            | ADMIN_REDE, DIR_ESCOLAR  |

---

## Provas

| Método | Rota                          | Descrição                            | Permissão                     |
|--------|-------------------------------|--------------------------------------|-------------------------------|
| GET    | /provas                       | Listar provas (com filtros)          | Autenticado (escopo)          |
| GET    | /provas/{id}                  | Detalhe da prova                     | Autenticado (escopo)          |
| POST   | /provas                       | Criar prova                          | COORD, PROFESSOR              |
| PUT    | /provas/{id}                  | Atualizar prova (rascunho)           | COORD, PROFESSOR (dono)       |
| DELETE | /provas/{id}                  | Excluir prova (rascunho)            | COORD, PROFESSOR (dono)       |
| POST   | /provas/{id}/publicar         | Publicar gabarito                    | COORD, PROFESSOR (dono)       |
| GET    | /provas/{id}/gabarito         | Ver gabarito oficial                 | Autenticado (escopo)          |
| PUT    | /provas/{id}/gabarito         | Atualizar gabarito (pré-publicação)  | COORD, PROFESSOR (dono)       |
| GET    | /provas/{id}/gabarito/pdf     | Exportar gabarito em PDF             | Autenticado (escopo)          |
| GET    | /provas/{id}/turmas           | Turmas vinculadas à prova            | Autenticado (escopo)          |
| POST   | /provas/{id}/turmas           | Vincular turmas à prova              | COORD, PROFESSOR (dono)       |

---

## OMR e Cartões

| Método | Rota                                     | Descrição                              | Permissão              |
|--------|------------------------------------------|----------------------------------------|------------------------|
| GET    | /provas/{id}/cartoes                     | Listar cartões da prova                | Autenticado (escopo)   |
| GET    | /provas/{id}/cartoes/status              | Status resumido (lidos/pendentes/amb.) | Autenticado (escopo)   |
| POST   | /provas/{id}/cartoes                     | Upload de imagem para OMR              | COORD, PROFESSOR       |
| GET    | /cartoes/{id}                            | Detalhe do cartão                      | Autenticado (escopo)   |
| PUT    | /cartoes/{id}/vincular-aluno             | Vincular cartão a aluno                | COORD, PROFESSOR       |
| POST   | /cartoes/{id}/resolver-ambiguidade       | Resolver marcação ambígua              | COORD, PROFESSOR       |
| POST   | /cartoes/{id}/revisar                    | Revisar leitura pós-correção           | COORD, PROFESSOR       |

---

## Notas e Resultados

| Método | Rota                                   | Descrição                            | Permissão              |
|--------|----------------------------------------|--------------------------------------|------------------------|
| GET    | /provas/{id}/resultados                | Resultados de todos os alunos        | Autenticado (escopo)   |
| GET    | /alunos/{id}/resultados/{prova_id}     | Resultado individual                 | Autenticado (escopo)   |
| GET    | /alunos/{id}/resultados/{prova_id}/pdf | Exportar resultado em PDF            | Autenticado (escopo)   |

---

## Relatórios

| Método | Rota                                   | Descrição                              | Permissão              |
|--------|----------------------------------------|----------------------------------------|------------------------|
| GET    | /relatorios/prova/{id}                 | Relatório consolidado da prova         | Autenticado (escopo)   |
| GET    | /relatorios/turma/{id}/prova/{prova_id}| Relatório da turma por prova           | Autenticado (escopo)   |
| GET    | /relatorios/escola/{id}                | Relatório consolidado da escola        | DIR_ESCOLAR+           |
| GET    | /relatorios/nucleo/{id}                | Relatório consolidado do núcleo        | DIR_NUCLEO+            |
| GET    | /relatorios/rede                       | Visão executiva da rede                | ADMIN_REDE             |
| GET    | /relatorios/rede/pdf                   | Exportar visão executiva em PDF        | ADMIN_REDE             |

---

## Dashboards

| Método | Rota                       | Descrição                                    | Permissão       |
|--------|----------------------------|----------------------------------------------|-----------------|
| GET    | /dashboard/admin           | Dados para dashboard do Admin                | ADMIN_REDE      |
| GET    | /dashboard/diretor-nucleo  | Dados para dashboard do Diretor de Núcleo    | DIR_NUCLEO      |
| GET    | /dashboard/diretor-escolar | Dados para dashboard do Diretor Escolar      | DIR_ESCOLAR     |
| GET    | /dashboard/coordenador     | Dados para dashboard do Coordenador          | COORDENADOR     |
| GET    | /dashboard/professor       | Dados para dashboard do Professor            | PROFESSOR       |
| GET    | /dashboard/aluno           | Dados para dashboard do Aluno                | ALUNO           |

---

## Visitas

| Método | Rota              | Descrição                   | Permissão   |
|--------|-------------------|-----------------------------|-------------|
| GET    | /visitas          | Listar visitas (do núcleo)  | DIR_NUCLEO  |
| POST   | /visitas          | Agendar visita              | DIR_NUCLEO  |
| PUT    | /visitas/{id}     | Atualizar visita            | DIR_NUCLEO  |
| DELETE | /visitas/{id}     | Cancelar visita             | DIR_NUCLEO  |

---

## Configurações

| Método | Rota                    | Descrição                        | Permissão   |
|--------|-------------------------|----------------------------------|-------------|
| GET    | /configuracoes          | Obter configurações do sistema   | ADMIN_REDE  |
| PUT    | /configuracoes          | Atualizar configurações          | ADMIN_REDE  |
| GET    | /configuracoes/escola/{id} | Configurações da escola       | DIR_ESCOLAR |
| PUT    | /configuracoes/escola/{id} | Atualizar config. da escola   | DIR_ESCOLAR |

---

## Integrações

| Método | Rota                     | Descrição                        | Permissão  |
|--------|--------------------------|----------------------------------|------------|
| GET    | /integracoes/seges/status | Status da última sincronização  | ADMIN_REDE |
| POST   | /integracoes/seges/sync   | Forçar sincronização manual     | ADMIN_REDE |
| GET    | /integracoes/seges/log    | Log de sincronizações           | ADMIN_REDE |

---

## Formato de Resposta Padrão

### Sucesso (200/201)
```json
{
  "data": { ... },
  "meta": { "page": 1, "per_page": 20, "total": 100 }
}
```

### Erro de Validação (422)
```json
{
  "message": "Dados inválidos.",
  "errors": {
    "nome": ["O nome é obrigatório."]
  }
}
```

### Erro de Autenticação (401)
```json
{
  "message": "Não autenticado."
}
```

### Erro de Autorização (403)
```json
{
  "message": "Acesso negado."
}
```

### Não Encontrado (404)
```json
{
  "message": "Recurso não encontrado."
}
```
