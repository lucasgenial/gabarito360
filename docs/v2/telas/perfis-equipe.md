# Tela: Perfis de Equipe (`perfis-equipe.html`)

- **Rota web:** `/escolas/{id}/perfis`
- **Módulo:** Equipe
- **Atores/permissões:** gestão escolar com permissão "Gerenciar membros da
  equipe" (ex.: Diretor Escolar). Escopo da escola.
- **Objetivo:** visualizar e editar as permissões de cada perfil da equipe e
  listar os membros vinculados a cada perfil.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Cabeçalho:** eyebrow + título "Perfis de Equipe" + subtítulo; nota fixa
  "Perfis existentes são fixos e não podem ser excluídos"; botão "Voltar para
  Equipe".
- **Faixa de KPIs (4):** um por perfil (Professor, Diretor Escolar, Vice-Diretor,
  Coordenador Pedagógico) com nº de membros vinculados e badge de classe.
- **Cards de perfil** (`role-card`), um por perfil:
  - **Resumo:** ícone, título, badge de contagem, descrição, selo "Perfil fixo".
  - **Painel de permissões** (`perm-panel`): linhas Permissão × Status, com modo
    **visualização** (Permitido/Bloqueado/Restrito) e modo **edição**
    (checkboxes). Permissões `data-locked` permanecem fixas mesmo em edição.
  - **Ações:** "Editar permissões" / "Ver membros" (view) e "Salvar"/"Cancelar"
    (edição). O perfil **Diretor Escolar** é totalmente bloqueado (`data-locked`).
- **Modal "Membros do perfil":** tabela (Membro, E-mail, Turmas/disciplinas,
  Ação Editar) + rodapé com contagem e "+ Adicionar membro".

### Catálogo de permissões (observado)

Criar provas; Corrigir provas; Ver relatórios da turma; Ver relatórios da escola;
Gerenciar alunos; Gerenciar turmas; Gerenciar membros da equipe; Definir padrões
de prova; Configurações da escola; Exportar dados.

### Matriz por perfil (do mockup)

| Permissão | Professor | Vice-Diretor | Coordenador | Diretor (fixo) |
|---|---|---|---|---|
| Criar provas | ✓ | ✓ | ✓ | ✓ |
| Corrigir provas | ✓ | ✓ | ✓ | ✓ |
| Relatórios da turma | ✓ | ✓ | ✓ | ✓ |
| Relatórios da escola | — | ✓ | ✓ | ✓ |
| Gerenciar alunos | — | ✓ | ✓ | ✓ |
| Gerenciar turmas | — | ✓ | ✓ | ✓ |
| Gerenciar membros | restrito | — | — | ✓ |
| Definir padrões de prova | — | — | ✓ | ✓ |
| Configurações da escola | restrito | — | — | ✓ |
| Exportar dados | — | ✓ | ✓ | ✓ |

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra |
|---|---|---|---|---|
| Editar permissões | botão | habilita checkboxes | — | bloqueado p/ Diretor (toast) |
| Checkbox de permissão | checkbox | alterna permissão | (no save) | `data-locked` não altera |
| Salvar | botão | persiste a matriz do perfil | `PUT /api/v2/escolas/{id}/perfis/{perfil}/permissoes` | auditado; toast |
| Cancelar | botão | reverte ao estado anterior | — | restaura `data-prev` |
| Ver membros | botão | abre modal de membros | `GET /api/v2/escolas/{id}/perfis/{perfil}/membros` | escopo |
| + Adicionar membro | link | `/escolas/{id}/membros/novo` | — | permissão |
| Editar (no modal) | link | `/escolas/{id}/membros/{membro}/editar` | — | permissão |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Perfis e contagem de membros | `perfis`, `usuarios_perfis` | por escola |
| Matriz de permissões | `permissoes`/pivot perfil-permissão | inclui flags fixas/restritas |
| Membros do perfil | `users` + vínculo (turmas/disciplinas) | modal |

## Estados

`default`, `editing` (card realçado), `hover`/`focus`, `disabled` (checkbox
fixo/locked), `loading` (salvar), `success` (toast), `error`, `access_denied`
(perfil fixo → ação bloqueada). Modal: `open`/`closed`, fecha por backdrop/Esc.

## Regras de negócio

- Perfis são **fixos**: não podem ser criados nem excluídos (apenas permissões
  ajustáveis, exceto perfis/permissões marcados como fixos).
- Diretor Escolar tem permissões fixas e bloqueadas.
- Algumas permissões são "restritas/bloqueadas" por perfil e não podem ser
  ativadas mesmo em edição.
- Alterações de permissão são auditadas (`auditorias`).

## Responsividade

`role-card` colapsa para 1 coluna ≤1120px; KPIs 4→2→1; `perm-row` adapta colunas
≤680/560px; modal com padding reduzido. Sem overflow horizontal.

## Endpoints `/api/v2` necessários

- `GET /api/v2/escolas/{id}/perfis` — perfis, contagem e matriz de permissões.
- `PUT /api/v2/escolas/{id}/perfis/{perfil}/permissoes` — salvar permissões.
- `GET /api/v2/escolas/{id}/perfis/{perfil}/membros` — membros do perfil.

## Pendências/decisões

- Definir quais permissões são globalmente fixas vs ajustáveis por escola.
- Confirmar se a edição de permissões é por escola ou herda padrão da rede.
