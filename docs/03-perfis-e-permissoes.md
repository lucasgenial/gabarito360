# 03 — Perfis e Permissões

---

## Perfis Oficiais

O sistema reconhece exatamente 6 perfis. Nenhum perfil adicional pode ser criado sem aprovação documentada.

| Código        | Perfil              | Escopo de Visibilidade        |
|---------------|---------------------|-------------------------------|
| ADMIN_REDE    | Administrador Rede  | Toda a rede                   |
| DIR_NUCLEO    | Diretor de Núcleo   | Escolas do núcleo             |
| DIR_ESCOLAR   | Diretor Escolar     | Sua escola                    |
| COORDENADOR   | Coordenador         | Sua escola (foco pedagógico)  |
| PROFESSOR     | Professor           | Suas turmas e provas          |
| ALUNO         | Aluno               | Seus próprios dados           |

---

## Hierarquia de Acesso

```
ADMIN_REDE
    └── DIR_NUCLEO (por núcleo)
            └── DIR_ESCOLAR (por escola)
                    └── COORDENADOR (por escola)
                            └── PROFESSOR (por turma)
                                    └── ALUNO (por matrícula)
```

---

## Permissões por Módulo

### Autenticação

| Ação                | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|---------------------|-------|---------|---------|-------|------|-------|
| Login               | ✓     | ✓       | ✓       | ✓     | ✓    | ✓     |
| Cadastro de usuário | ✓     | -       | -       | -     | -    | -     |
| Redefinir senha     | ✓     | ✓       | ✓       | ✓     | ✓    | ✓     |
| Gerenciar usuários  | ✓     | -       | ✓*      | -     | -    | -     |

*DIR_ESC: apenas usuários de sua escola

---

### Rede

| Ação              | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|-------------------|-------|---------|---------|-------|------|-------|
| Ver dados da rede | ✓     | -       | -       | -     | -    | -     |
| Editar rede       | ✓     | -       | -       | -     | -    | -     |
| Relatório da rede | ✓     | -       | -       | -     | -    | -     |

---

### Núcleos

| Ação               | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|--------------------|-------|---------|---------|-------|------|-------|
| Listar núcleos     | ✓     | ✓*      | -       | -     | -    | -     |
| Criar núcleo       | ✓     | -       | -       | -     | -    | -     |
| Editar núcleo      | ✓     | ✓*      | -       | -     | -    | -     |
| Relatório núcleo   | ✓     | ✓*      | -       | -     | -    | -     |
| Agendar visita     | ✓     | ✓*      | -       | -     | -    | -     |

*DIR_NUC: apenas seu núcleo

---

### Escolas

| Ação                | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|---------------------|-------|---------|---------|-------|------|-------|
| Listar escolas      | ✓     | ✓*      | ✓**     | ✓**   | ✓**  | -     |
| Criar escola        | ✓     | -       | -       | -     | -    | -     |
| Editar escola       | ✓     | -       | ✓**     | -     | -    | -     |
| Desativar escola    | ✓     | -       | -       | -     | -    | -     |
| Reativar escola     | ✓     | -       | -       | -     | -    | -     |
| Ver detalhes escola | ✓     | ✓*      | ✓**     | ✓**   | ✓**  | -     |

*DIR_NUC: escolas do seu núcleo  
**DIR_ESC/COORD/PROF: apenas sua escola

---

### Turmas

| Ação               | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|--------------------|-------|---------|---------|-------|------|-------|
| Listar turmas      | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     |
| Criar turma        | ✓     | -       | ✓**     | ✓**   | -    | -     |
| Editar turma       | ✓     | -       | ✓**     | ✓**   | -    | -     |
| Importar planilha  | ✓     | -       | ✓**     | ✓**   | -    | -     |
| Ver detalhe turma  | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     |

*DIR_NUC: turmas das escolas do núcleo  
**DIR_ESC/COORD: turmas de sua escola  
***PROF: apenas suas turmas

---

### Alunos

| Ação                   | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|------------------------|-------|---------|---------|-------|------|-------|
| Listar alunos          | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     |
| Cadastrar aluno        | ✓     | -       | ✓**     | ✓**   | -    | -     |
| Editar aluno           | ✓     | -       | ✓**     | ✓**   | -    | -     |
| Ver detalhe aluno      | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | ✓**** |
| Exportar ficha PDF     | ✓     | -       | ✓**     | ✓**   | ✓*** | ✓**** |
| Ver histórico avaliação| ✓     | ✓*      | ✓**     | ✓**   | ✓*** | ✓**** |

*DIR_NUC: alunos das escolas do núcleo  
**DIR_ESC/COORD: alunos de sua escola  
***PROF: alunos de suas turmas  
****ALUNO: apenas seus próprios dados

---

### Equipe / Membros

| Ação                  | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|-----------------------|-------|---------|---------|-------|------|-------|
| Listar membros        | ✓     | ✓*      | ✓**     | ✓**   | -    | -     |
| Cadastrar membro      | ✓     | -       | ✓**     | -     | -    | -     |
| Editar membro         | ✓     | -       | ✓**     | -     | -    | -     |
| Alterar perfil membro | ✓     | -       | ✓**     | -     | -    | -     |
| Desativar membro      | ✓     | -       | ✓**     | -     | -    | -     |

*DIR_NUC: membros das escolas do núcleo  
**DIR_ESC: membros de sua escola

---

### Provas

| Ação                  | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|-----------------------|-------|---------|---------|-------|------|-------|
| Listar provas         | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     |
| Criar prova           | ✓     | -       | -       | ✓**   | ✓*** | -     |
| Editar prova rascunho | ✓     | -       | -       | ✓**   | ✓*** | -     |
| Publicar gabarito     | ✓     | -       | -       | ✓**   | ✓*** | -     |
| Ver gabarito          | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     |
| Exportar gabarito PDF | ✓     | -       | ✓**     | ✓**   | ✓*** | -     |

*DIR_NUC: provas das escolas do núcleo  
**DIR_ESC/COORD: provas de sua escola  
***PROF: apenas suas próprias provas

---

### OMR e Correção

| Ação                        | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|-----------------------------|-------|---------|---------|-------|------|-------|
| Acompanhar correção (prova) | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     |
| Capturar cartões            | ✓     | -       | -       | ✓**   | ✓*** | -     |
| Resolver ambíguos           | ✓     | -       | -       | ✓**   | ✓*** | -     |
| Revisar leitura             | ✓     | -       | -       | ✓**   | ✓*** | -     |
| Atualizar leitura           | ✓     | -       | -       | ✓**   | ✓*** | -     |

---

### Resultados

| Ação                        | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|-----------------------------|-------|---------|---------|-------|------|-------|
| Ver resultado individual    | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | ✓**** |
| Exportar resultado PDF      | ✓     | -       | ✓**     | ✓**   | ✓*** | ✓**** |
| Ver relatório da prova      | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     |
| Ver relatório da turma      | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     |
| Ver relatório da escola     | ✓     | ✓*      | ✓**     | -     | -    | -     |
| Gerar visão executiva rede  | ✓     | -       | -       | -     | -    | -     |

*DIR_NUC: dados das escolas do núcleo  
**DIR_ESC/COORD: dados de sua escola  
***PROF: dados de suas turmas  
****ALUNO: apenas seus próprios resultados

---

### Configurações

| Ação                         | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | ALUNO |
|------------------------------|-------|---------|---------|-------|------|-------|
| Configurações do sistema     | ✓     | -       | -       | -     | -    | -     |
| Configurações da escola      | ✓     | -       | ✓**     | -     | -    | -     |
| Parâmetros de avaliação      | ✓     | -       | ✓**     | -     | -    | -     |
| Integrações externas         | ✓     | -       | -       | -     | -    | -     |
| Meu Perfil                   | ✓     | ✓       | ✓       | ✓     | ✓    | ✓     |

---

## Dashboard por Perfil

| Perfil          | Dashboard                    | Arquivo mockup                   |
|-----------------|------------------------------|----------------------------------|
| ADMIN_REDE      | Painel Administrativo        | dashboard-admin.html             |
| DIR_NUCLEO      | Painel do Núcleo             | dashboard-diretor-nucleo.html    |
| DIR_ESCOLAR     | Painel da Escola             | dashboard-diretor-escolar.html   |
| COORDENADOR     | Painel do Coordenador        | dashboard-coordenador.html       |
| PROFESSOR       | Painel do Professor          | dashboard-professor.html         |
| ALUNO           | Painel do Aluno              | dashboard-aluno.html             |

---

## Navegação por Perfil

### ADMIN_REDE / DIR_NUCLEO / DIR_ESCOLAR / COORDENADOR / PROFESSOR
- Painel
- Provas
- Turmas
- Escolas

### ALUNO
- Painel
- Minhas Provas

---

## Contexto do Badge no Header

| Perfil      | Badge Exibido                    |
|-------------|----------------------------------|
| ADMIN_REDE  | Nome da rede (ex.: Rede Municipal)|
| DIR_NUCLEO  | Nome do núcleo (ex.: Núcleo Norte)|
| DIR_ESCOLAR | Nome da escola (ex.: EMEF Tiradentes)|
| COORDENADOR | Nome da escola                   |
| PROFESSOR   | Disciplina + nº turmas (ex.: Matemática · 3 turmas)|
| ALUNO       | Turma (ex.: 9º Ano A)           |

---

## Política de Isolamento de Dados

- Cada usuário só pode acessar dados dentro do seu escopo
- O escopo é definido no momento da criação do usuário
- Um professor não pode ver provas de outro professor
- Um aluno não pode ver resultados de outros alunos
- Um diretor escolar não pode ver dados de outra escola
- A validação de escopo deve ser feita na API (não apenas no frontend)
