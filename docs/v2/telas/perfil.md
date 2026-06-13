# Tela: Perfil (`perfil.html`)

- **Rota web:** `/perfil`
- **Módulo:** Conta
- **Atores/permissões:** qualquer usuário autenticado (próprio perfil).
- **Objetivo:** gerenciar dados pessoais, foto, senha, notificações, sessões e
  histórico de atividade da própria conta.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Layout 2 colunas:** sidebar (card do usuário + **navegação interna** por
  seções) e conteúdo principal em **section-cards**.
- **Dados pessoais:** foto (`photo-upload` com avatar/iniciais), nome, e-mail,
  telefone e demais campos (grids 2/3 colunas).
- **Segurança / senha:** campos de senha com **indicador de força**
  (`password-strength`).
- **Notificações:** lista de **toggles** (preferências por tipo de evento).
- **Sessões / atividade:** `activity-list` com itens (dispositivo/ação, data),
  permitindo encerrar sessões.

## Controles e ações

| Controle | Tipo | Ação | Endpoint | Regra/Validação |
|---|---|---|---|---|
| Upload de foto | file | atualiza avatar | `POST /api/v2/me/foto` | imagem; fallback iniciais |
| Nome/telefone/campos | input | edita dados | `PUT /api/v2/me` | validação por campo |
| E-mail | input | edita e-mail | `PUT /api/v2/me` | confirmação/verificação |
| Nova senha | password | redefine | `PUT /api/v2/me/senha` | força mínima; hash forte |
| Toggle de notificação | toggle | preferências | `PATCH /api/v2/me/preferencias` | por tipo de evento |
| Encerrar sessão | botão | revoga sessão | `DELETE /api/v2/me/sessoes/{id}` | auditado |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Dados pessoais e foto | `users`, `arquivos` | próprio usuário |
| Preferências de notificação | `preferencias_notificacoes` | por tipo |
| Sessões/atividade | `sessoes_usuarios`, `historicos_acesso` | encerráveis |

## Estados

`default`, `focus`, `invalid`, `loading`, `success` (salvo), `error`,
`access_denied`. Indicador de força da senha (fraca→forte).

## Regras de negócio

- Cada usuário gerencia apenas o próprio perfil.
- Senha sempre com hash forte; troca exige força mínima.
- Alterações sensíveis (e-mail, senha, sessões) são auditadas.
- Encerrar sessão revoga o token correspondente.

## Responsividade

`profile-layout` 2→1 coluna ≤900px (sidebar vira linha); grids 1 coluna.

## Endpoints `/api/v2` necessários

- `GET /api/v2/me` · `PUT /api/v2/me` — ler/editar dados.
- `PUT /api/v2/me/senha` — trocar senha.
- `POST /api/v2/me/foto` — avatar.
- `GET /api/v2/me/sessoes` · `DELETE /api/v2/me/sessoes/{id}` — sessões.
- `PATCH /api/v2/me/preferencias` — notificações.

## Pendências/decisões

- Definir verificação de e-mail e política de senha (complexidade/expiração).
