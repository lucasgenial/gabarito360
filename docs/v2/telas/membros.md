# Telas: Cadastrar / Editar Membro (`membro-cadastrar.html`, `membro-editar.html`)

- **Rotas web:** `/escolas/{id}/membros/novo` e `/escolas/{id}/membros/{membro}/editar`
- **Módulo:** Equipe
- **Atores/permissões:** gestão escolar com "Gerenciar membros da equipe".
- **Objetivo:** cadastrar e manter integrantes da equipe escolar, com perfil de
  acesso, dados pessoais/profissionais, vínculos (disciplinas/turmas) e controle
  de acesso ao sistema.
- **Shell:** ver [`_shell.md`](_shell.md). As duas telas consolidam a **união**
  de capacidades de cadastro e edição.

## Layout e componentes

### Cadastrar (`membro-cadastrar.html`)
Layout 2 colunas: **card de identidade** (sticky) com pré-visualização de avatar
(iniciais coloridas), upload e resumo (Nome, Cargo/Perfil, E-mail) + **formulário**
em seções:
- **Perfil e Acesso:** Perfil (select), Data de início, Status (Ativo/Inativo).
- **Dados Pessoais:** Nome completo*, CPF (máscara), Data de nascimento,
  Telefone (máscara), E-mail.
- **Formação Acadêmica:** Graduação/Licenciatura, Especialização, Registro
  profissional.
- **Disciplinas e Turmas** (visível só quando Perfil = Professor): checkboxes de
  disciplinas e de turmas.
- **Observações:** textarea interno.
- **Barra de ações:** Cancelar / Salvar Membro.

### Editar (`membro-editar.html`)
Inclui tudo acima e adiciona:
- **Perfil e cargo** como cards de rádio (Professor/Diretor/Vice/Coordenador).
- **Dados profissionais:** Formação, registro, **Escola** (select), Data de
  ingresso; blocos de **disciplinas** (tags adicionáveis) e **turmas** (checkboxes)
  quando professor.
- **Acesso ao sistema:** Login (somente leitura), Nova senha (em branco mantém),
  toggles "Membro ativo" e "Forçar troca de senha".
- **Última atualização** (autor + data).
- **Zona de perigo:** "Suspender acesso" e "Remover membro permanentemente"
  (abre modal de confirmação).
- **Ações:** Cancelar / Salvar rascunho / Salvar alterações.
- **CPF e Login** ficam **desabilitados** (não alteráveis após cadastro).

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra/Validação |
|---|---|---|---|---|
| Perfil | select/rádio | define acesso | `POST/PUT membros` | obrigatório; mostra seção de professor |
| Nome completo* | input | identificação | idem | required; alimenta avatar/preview |
| CPF | input (máscara) | identificação | idem (POST) | máscara 000.000.000-00; **imutável** na edição |
| Data nasc./início/ingresso | date | dados | idem | — |
| Telefone | input (máscara) | contato | idem | máscara (xx) xxxxx-xxxx |
| E-mail* | input email | acesso/contato | idem | e-mail válido |
| Formação/Especialização/Registro | inputs | dados profissionais | idem | opcionais |
| Escola (editar) | select | lotação | idem | obrigatório |
| Disciplinas | checkboxes/tags | vínculo docente | idem | só Professor |
| Turmas | checkboxes | vínculo docente | idem | só Professor |
| Status / Membro ativo | rádio/toggle | habilita acesso | idem | — |
| Forçar troca de senha | toggle | segurança | idem | exige nova senha no próximo acesso |
| Nova senha | password | redefinição | idem | em branco mantém; hash forte |
| Upload de foto | botão | avatar | `POST membros/{id}/foto` | imagem; fallback iniciais |
| Salvar (membro/alterações/rascunho) | botão | persiste | `POST/PUT /api/v2/escolas/{id}/membros` | `reportValidity`; toast |
| Suspender acesso | botão | suspende | `POST /api/v2/.../membros/{id}/suspender` | reversível; auditado |
| Remover permanentemente | botão | abre modal de remoção | `DELETE`/solicitação LGPD | confirmação; inativar/anonimizar |

## Dados exibidos / capturados

| Campo | Origem/Destino | Observação |
|---|---|---|
| Perfil, status, datas | `usuarios_perfis`, `usuarios_lotacoes` | vínculo na escola |
| Dados pessoais | `users` | CPF/login imutáveis pós-cadastro |
| Formação/registro | dados profissionais do usuário | — |
| Disciplinas/turmas | `usuarios_disciplinas`, vínculo turma | só professor |
| Última atualização | `auditorias` | autor + data |

## Estados

`default`, `focus`, `invalid` (borda vermelha em campo inválido), `disabled`
(CPF/login; seção de professor oculta quando não-professor), `loading` (salvar),
`success` (toast + redireciona), `error`, `access_denied`. Modal de remoção:
`open`/`closed`.

## Regras de negócio

- Seção "Disciplinas e Turmas" só aparece para Perfil = Professor.
- CPF e Login são imutáveis após o cadastro.
- "Remover permanentemente" segue adaptação segura LGPD: inativação/anonimização
  ou solicitação rastreável — não exclusão direta de dados pessoais.
- "Suspender acesso" é reversível; alterações são auditadas.
- Senha sempre com hash forte; "forçar troca" exige nova senha no próximo acesso.

## Responsividade

Layout 2→1 coluna ≤960px (identidade deixa de ser sticky); grids de formulário e
checkboxes 1 coluna ≤720px; barra de ações empilha. Sem overflow horizontal.

## Endpoints `/api/v2` necessários

- `GET /api/v2/escolas/{id}/membros/{membro}` — dados para edição.
- `POST /api/v2/escolas/{id}/membros` — cadastrar.
- `PUT /api/v2/escolas/{id}/membros/{membro}` — editar.
- `POST /api/v2/escolas/{id}/membros/{membro}/suspender` — suspender acesso.
- `POST /api/v2/escolas/{id}/membros/{membro}/foto` — upload de avatar.
- `POST /api/v2/solicitacoes-lgpd` — remoção/anonimização rastreável.
- `GET /api/v2/perfis`, `GET /api/v2/disciplinas`, `GET /api/v2/escolas/{id}/turmas` — catálogos.

## Pendências/decisões

- Definir política de senha (complexidade) e fluxo de primeiro acesso.
- Confirmar se "Salvar rascunho" persiste no servidor ou só localmente.
- Padronizar a remoção como solicitação LGPD (recomendado) vs inativação simples.
