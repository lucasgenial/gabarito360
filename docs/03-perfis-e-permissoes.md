# 03 — Perfis e Permissões

---

## ⚠️ Mudança Estrutural (2026-06-18)

O Gabarito360 passa a ser vendido como **produto SaaS multi-tenant**, com dois caminhos de
cadastro/assinatura:

1. **Individual:** um professor se cadastra sozinho, escolhe um plano, paga e passa a
   gerenciar sua própria escola/turmas/alunos/provas, sem depender de nenhuma rede
   institucional.
2. **Institucional:** uma secretaria de educação, rede municipal/estadual ou escola se
   cadastra como instituição (fluxo já existente, hoje sujeito a aprovação manual),
   cadastra sua estrutura (núcleos, escolas, turmas) e convida sua equipe.

Isso adiciona **2 novos perfis** (SECRETARIO_EDUCACAO, APLICADOR) e **1 novo nível
hierárquico** (Secretaria, acima da Rede). O sistema passa a reconhecer **8 perfis**.
Ver `docs/13-roadmap.md` (MP-025 em diante) para o plano de implementação.

---

## Perfis Oficiais

| Código              | Perfil                  | Escopo de Visibilidade                          |
|---------------------|--------------------------|-------------------------------------------------|
| SECRETARIO_EDUCACAO  | Secretário de Educação   | Todas as redes vinculadas à sua secretaria      |
| ADMIN_REDE           | Administrador Rede       | Toda a rede (institucional ou individual)       |
| DIR_NUCLEO           | Diretor de Núcleo        | Escolas do núcleo                                |
| DIR_ESCOLAR          | Diretor Escolar          | Sua escola                                       |
| COORDENADOR          | Coordenador              | Sua escola (foco pedagógico)                     |
| PROFESSOR            | Professor                | Suas turmas e provas                             |
| APLICADOR            | Aplicador                | Provas do dia que precisa aplicar, em sua escola |
| ALUNO                | Aluno                    | Seus próprios dados                              |

Nenhum perfil adicional pode ser criado sem aprovação documentada.

**Nota sobre conta individual (professor autônomo):** ao se cadastrar como conta
individual, o professor recebe o perfil **PROFESSOR**, mas dentro da escola que o
sistema cria automaticamente para ele, suas permissões são equivalentes às de um
DIR_ESCOLAR (cadastra suas próprias turmas, alunos, outros professores/aplicadores que
convidar). Isso é resolvido via o motor de permissões configuráveis (MP-029), não por
um perfil novo — o "perfil" continua sendo PROFESSOR, o que muda é o conjunto de
permissões dentro daquele contexto de escola.

---

## Hierarquia de Acesso

```
SECRETARIO_EDUCACAO (supervisiona N redes de uma secretaria)
    └── REDE (institucional OU individual — ver nota abaixo)
            └── ADMIN_REDE
                    └── DIR_NUCLEO (por núcleo)
                            └── DIR_ESCOLAR (por escola)
                                    └── COORDENADOR (por escola)
                                            └── PROFESSOR (por turma)
                                            └── APLICADOR (por escola, restrito ao dia de aplicação)
                                                    └── ALUNO (por matrícula)
```

**Nota — Rede individual:** quando um professor se cadastra sozinho, o sistema cria
automaticamente, de forma transparente (sem o professor precisar ver essas telas):
1 `rede` com `tipo = 'individual'`, 1 `nucleo` padrão dentro dela, e 1 `escola` (a dele).
Ele assume o perfil PROFESSOR com permissões elevadas (ver nota acima) dentro dessa
escola. A estrutura de dados continua igual à institucional — só a UI esconde os
níveis "rede"/"núcleo" para esse tipo de conta. Isso permite que, se uma prefeitura
assinar depois, a escola do professor possa ser **migrada** para dentro da rede
institucional (MP-030) sem mudança de schema.

---

## Permissões por Módulo

### Autenticação e Cadastro

| Ação                              | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|------------------------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Login                              | ✓       | ✓     | ✓       | ✓       | ✓     | ✓    | ✓     | ✓     |
| Cadastro institucional autônomo    | ✓*      | ✓*    | -       | -       | -     | -    | -     | -     |
| Cadastro individual autônomo       | -       | -     | -       | -       | -     | ✓**  | -     | -     |
| Cadastro de usuário (convite)      | -       | ✓     | -       | ✓***    | -     | -    | -     | -     |
| Redefinir senha                    | ✓       | ✓     | ✓       | ✓       | ✓     | ✓    | ✓     | ✓     |
| Gerenciar usuários                 | -       | ✓     | -       | ✓***    | -     | -    | -     | -     |

*SEC_EDU/ADMIN: cadastro institucional sujeito a confirmação de e-mail + assinatura ativa (ver RN-001 revisada), não mais aprovação manual.
**PROF: cadastro individual sujeito apenas a pagamento confirmado.
***DIR_ESC: apenas usuários de sua escola, incluindo agora PROFESSOR e APLICADOR.

---

### Secretaria (novo)

| Ação                        | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|------------------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Ver dados da secretaria      | ✓       | -     | -       | -       | -     | -    | -     | -     |
| Criar rede dentro da secretaria | ✓    | -     | -       | -       | -     | -    | -     | -     |
| Relatório consolidado (todas as redes da secretaria) | ✓ | - | - | - | - | - | - | - |

---

### Rede

| Ação              | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|-------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Ver dados da rede | ✓*      | ✓     | -       | -       | -     | -    | -     | -     |
| Editar rede       | -       | ✓     | -       | -       | -     | -    | -     | -     |
| Relatório da rede | ✓*      | ✓     | -       | -       | -     | -    | -     | -     |

*SEC_EDU: apenas redes vinculadas à sua secretaria.

---

### Núcleos

| Ação               | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|--------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Listar núcleos     | -       | ✓     | ✓*      | -       | -     | -    | -     | -     |
| Criar núcleo       | -       | ✓     | -       | -       | -     | -    | -     | -     |
| Editar núcleo      | -       | ✓     | ✓*      | -       | -     | -    | -     | -     |
| Relatório núcleo   | -       | ✓     | ✓*      | -       | -     | -    | -     | -     |
| Agendar visita     | -       | ✓     | ✓*      | -       | -     | -    | -     | -     |

*DIR_NUC: apenas seu núcleo

---

### Escolas

| Ação                | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|---------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Listar escolas      | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓**  | ✓**   | -     |
| Criar escola        | -       | ✓     | -       | -       | -     | ✓‡   | -     | -     |
| Editar escola       | -       | ✓     | -       | ✓**     | -     | ✓**‡ | -     | -     |
| Desativar escola    | -       | ✓     | -       | -       | -     | -    | -     | -     |
| Reativar escola     | -       | ✓     | -       | -       | -     | -    | -     | -     |
| Ver detalhes escola | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓**  | ✓**   | -     |

*DIR_NUC: escolas do seu núcleo
**DIR_ESC/COORD/PROF/APLIC: apenas sua escola
‡PROF: apenas em conta individual, e apenas a própria escola auto-criada no cadastro (não pode criar uma segunda)

---

### Turmas

| Ação               | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|--------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Listar turmas      | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | ✓***  | -     |
| Criar turma        | -       | ✓     | -       | ✓**     | ✓**   | ✓‡   | -     | -     |
| Editar turma       | -       | ✓     | -       | ✓**     | ✓**   | ✓‡   | -     | -     |
| Importar planilha  | -       | ✓     | -       | ✓**     | ✓**   | ✓‡   | -     | -     |
| Ver detalhe turma  | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | ✓***  | -     |

*DIR_NUC: turmas das escolas do núcleo
**DIR_ESC/COORD: turmas de sua escola
***PROF/APLIC: apenas suas turmas / turmas com prova agendada para o dia
‡PROF: apenas dentro da própria escola, em conta individual

---

### Alunos

| Ação                   | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|------------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Listar alunos          | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     | -     |
| Cadastrar aluno        | -       | ✓     | -       | ✓**     | ✓**   | ✓‡   | -     | -     |
| Editar aluno           | -       | ✓     | -       | ✓**     | ✓**   | ✓‡   | -     | -     |
| Ver detalhe aluno      | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     | ✓**** |
| Exportar ficha PDF     | -       | ✓     | -       | ✓**     | ✓**   | ✓*** | -     | ✓**** |
| Ver histórico avaliação| -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     | ✓**** |

*DIR_NUC: alunos das escolas do núcleo
**DIR_ESC/COORD: alunos de sua escola
***PROF: alunos de suas turmas
****ALUNO: apenas seus próprios dados
‡PROF: apenas dentro da própria escola, em conta individual

---

### Equipe / Membros

| Ação                  | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|-----------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Listar membros        | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓‡   | -     | -     |
| Cadastrar membro      | -       | ✓     | -       | ✓**     | -     | ✓‡   | -     | -     |
| Convidar aplicador    | -       | ✓     | -       | ✓**     | ✓**   | ✓‡   | -     | -     |
| Editar membro         | -       | ✓     | -       | ✓**     | -     | ✓‡   | -     | -     |
| Alterar perfil membro | -       | ✓     | -       | ✓**     | -     | -    | -     | -     |
| Desativar membro      | -       | ✓     | -       | ✓**     | -     | ✓‡   | -     | -     |

*DIR_NUC: membros das escolas do núcleo
**DIR_ESC: membros de sua escola
‡PROF: apenas em conta individual, dentro da própria escola (pode convidar outros professores/aplicadores para colaborar)

---

### Provas

| Ação                  | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|-----------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Listar provas         | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | ✓†    | -     |
| Criar prova           | -       | ✓     | -       | -       | ✓**   | ✓*** | -     | -     |
| Editar prova rascunho | -       | ✓     | -       | -       | ✓**   | ✓*** | -     | -     |
| Publicar gabarito     | -       | ✓     | -       | -       | ✓**   | ✓*** | -     | -     |
| Ver gabarito          | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     | -     |
| Exportar gabarito PDF | -       | ✓     | -       | ✓**     | ✓**   | ✓*** | -     | -     |

*DIR_NUC: provas das escolas do núcleo
**DIR_ESC/COORD: provas de sua escola
***PROF: apenas suas próprias provas
†APLIC: apenas provas agendadas para o dia, na sua escola — sem ver conteúdo/gabarito, só identificação e turma

---

### OMR e Correção

| Ação                        | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|-----------------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Acompanhar correção (prova) | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     | -     |
| Capturar/enviar cartões     | -       | ✓     | -       | -       | ✓**   | ✓*** | ✓†    | -     |
| Resolver ambíguos           | -       | ✓     | -       | -       | ✓**   | ✓*** | -     | -     |
| Revisar leitura             | -       | ✓     | -       | -       | ✓**   | ✓*** | -     | -     |
| Atualizar leitura           | -       | ✓     | -       | -       | ✓**   | ✓*** | -     | -     |

†APLIC: única ação permitida ao aplicador no módulo de correção — enviar os cartões da prova que aplicou no dia. Não acompanha progresso, não resolve ambíguos, não vê notas.

---

### Resultados

| Ação                        | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|-----------------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Ver resultado individual    | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     | ✓**** |
| Exportar resultado PDF      | -       | ✓     | -       | ✓**     | ✓**   | ✓*** | -     | ✓**** |
| Ver relatório da prova      | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     | -     |
| Ver relatório da turma      | -       | ✓     | ✓*      | ✓**     | ✓**   | ✓*** | -     | -     |
| Ver relatório da escola     | -       | ✓     | ✓*      | ✓**     | -     | -    | -     | -     |
| Ver relatório do núcleo     | -       | ✓     | ✓**     | -       | -     | -    | -     | -     |
| Ver relatório da rede       | -       | ✓     | -       | -       | -     | -    | -     | -     |
| Ver relatório da secretaria | ✓       | -     | -       | -       | -     | -    | -     | -     |
| Gerar visão executiva rede  | -       | ✓     | -       | -       | -     | -    | -     | -     |

*DIR_NUC: dados das escolas do núcleo
**DIR_ESC/COORD: dados de sua escola; DIR_NUC: relatório do próprio núcleo
***PROF: dados de suas turmas
****ALUNO: apenas seus próprios resultados

---

### Configurações

| Ação                         | SEC_EDU | ADMIN | DIR_NUC | DIR_ESC | COORD | PROF | APLIC | ALUNO |
|------------------------------|---------|-------|---------|---------|-------|------|-------|-------|
| Configurações do sistema     | -       | ✓     | -       | -       | -     | -    | -     | -     |
| Configurações da escola      | -       | ✓     | -       | ✓**     | -     | ✓**‡| -     | -     |
| Parâmetros de avaliação      | -       | ✓     | -       | ✓**     | -     | ✓**‡| -     | -     |
| Integrações externas         | -       | ✓     | -       | -       | -     | -    | -     | -     |
| Meu Perfil                   | ✓       | ✓     | ✓       | ✓       | ✓     | ✓    | ✓     | ✓     |

‡PROF: apenas em conta individual

---

### Assinatura e Cobrança (novo)

O acesso a este módulo não é determinado pelo perfil, e sim por **titularidade da
conta**: quem criou a rede individual (PROF) ou a estrutura institucional
(SEC_EDU/ADMIN_REDE no momento do cadastro autônomo) é o titular financeiro e o único
que vê/gerencia a assinatura. Outros membros da mesma escola/rede não têm acesso a
este módulo, independentemente do perfil.

| Ação                         | Titular | Demais usuários |
|-------------------------------|---------|------------------|
| Ver plano atual e status      | ✓       | -                |
| Escolher/trocar plano         | ✓       | -                |
| Ver histórico de pagamentos   | ✓       | -                |
| Cancelar assinatura           | ✓       | -                |
| Atualizar forma de pagamento  | ✓       | -                |

---

## Dashboard por Perfil

| Perfil               | Dashboard                    | Arquivo mockup                   |
|-----------------------|------------------------------|----------------------------------|
| SECRETARIO_EDUCACAO   | Painel da Secretaria (novo)  | *(sem mockup — a criar)*         |
| ADMIN_REDE            | Painel Administrativo        | dashboard-admin.html             |
| DIR_NUCLEO            | Painel do Núcleo             | dashboard-diretor-nucleo.html    |
| DIR_ESCOLAR           | Painel da Escola             | dashboard-diretor-escolar.html   |
| COORDENADOR           | Painel do Coordenador        | dashboard-coordenador.html       |
| PROFESSOR             | Painel do Professor          | dashboard-professor.html         |
| APLICADOR             | Painel do Aplicador (novo)   | *(sem mockup — a criar)*         |
| ALUNO                 | Painel do Aluno              | dashboard-aluno.html             |

---

## Navegação por Perfil

### SECRETARIO_EDUCACAO
- Painel
- Redes (da minha secretaria)
- Relatório Consolidado

### ADMIN_REDE / DIR_NUCLEO / DIR_ESCOLAR / COORDENADOR / PROFESSOR (institucional)
- Painel
- Provas
- Turmas
- Escolas

### PROFESSOR (conta individual)
- Painel
- Provas
- Turmas
- Alunos
- (sem "Escolas" — só tem a própria)

### APLICADOR
- Painel (lista de provas do dia)
- Enviar Cartões

### ALUNO
- Painel
- Minhas Provas

---

## Contexto do Badge no Header

| Perfil               | Badge Exibido                              |
|------------------------|---------------------------------------------|
| SECRETARIO_EDUCACAO    | Nome da secretaria (ex.: Secretaria Estadual)|
| ADMIN_REDE             | Nome da rede (ex.: Rede Municipal)           |
| DIR_NUCLEO             | Nome do núcleo (ex.: Núcleo Norte)           |
| DIR_ESCOLAR            | Nome da escola (ex.: EMEF Tiradentes)        |
| COORDENADOR            | Nome da escola                               |
| PROFESSOR (institucional) | Disciplina + nº turmas                    |
| PROFESSOR (individual) | "Conta Individual" + plano atual             |
| APLICADOR              | Nome da escola + "Aplicador"                 |
| ALUNO                  | Turma (ex.: 9º Ano A)                        |

---

## Política de Isolamento de Dados

- Cada usuário só pode acessar dados dentro do seu escopo
- O escopo é definido no momento da criação do usuário
- Um professor não pode ver provas de outro professor
- Um aluno não pode ver resultados de outros alunos
- Um diretor escolar não pode ver dados de outra escola
- **Uma rede individual nunca é visível para outra rede individual, nem para redes
  institucionais, exceto durante um processo explícito de migração/absorção (MP-030),
  conduzido apenas pelo titular da rede individual confirmando a migração**
- A validação de escopo deve ser feita na API (não apenas no frontend)
