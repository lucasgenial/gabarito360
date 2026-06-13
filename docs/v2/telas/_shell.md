# Componente compartilhado: Shell autenticado

> Estrutura comum a todas as telas autenticadas (dashboards, escolas, turmas,
> alunos, provas, correção, resultados, relatórios, conta). Mapeado uma vez e
> referenciado pelas demais telas. Fonte: cabeçalho repetido em todos os HTML do
> mockup (`dashboard*.html`, etc.) + `css/gov.css` + `js/app.js`.

## Regiões

1. **Govbar** (`.govbar`): faixa governamental superior com "🇧🇷 Governo Federal"
   e links "Acessibilidade" e "Alto contraste".
2. **Header da aplicação** (`.app-header` > `.container`):
   - **Marca** (`.brand`): logo "G360" + "Gabarito360", link ao painel do ator.
   - **Navegação** (`.app-nav`): itens "Painel", "Provas", "Turmas", "Escolas"
     (o aluno vê navegação reduzida: "Painel" e "Minhas Provas"). Item ativo
     marcado com `.active`.
   - **Header-right**: badge de contexto (rede/escola/núcleo/turma), botão de
     **alternar tema** (`#theme-toggle`) e **menu de usuário**.
3. **Menu de usuário** (`.user-menu-container`): avatar com iniciais; ao abrir,
   exibe nome, papel·escopo e links "Meu Perfil", "Configurações" e "Sair".
4. **Breadcrumb** (`.breadcrumb`): trilha "Início / <seção>".

## Controles e ações

| Controle | Tipo | Ação | Endpoint/Evento | Regra |
|---|---|---|---|---|
| Links da govbar | link | acessibilidade/alto contraste | — (preferência de UI) | aplica tema/realce; persistido |
| Itens de navegação | link | navegar entre módulos | rotas web | visíveis conforme permissão do ator |
| Alternar tema | botão | claro/escuro | preferência (`preferencias_usuarios`) | claro é padrão; persistir por usuário/dispositivo |
| Avatar/menu | menu | abrir menu de conta | — (cliente) | foco/teclado acessível |
| Meu Perfil / Configurações | link | `/perfil`, `/configuracoes` | — | escopo do próprio usuário |
| Sair | link | encerrar sessão | `POST /api/v2/auth/logout` | revoga token; volta ao login |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Nome, iniciais, papel, escopo | `users`, `perfis`, `usuarios_lotacoes` | identidade do autenticado |
| Badge de contexto | escopo ativo (rede/escola/núcleo/turma) | reflete a lotação atual |
| Itens de navegação visíveis | permissões do perfil | navegação por ator |

## Estados

- `hover`/`focus`/`active` em links e botões; foco visível (WCAG 2.2 AA).
- Menu de usuário: `open`/`closed`.
- Tema: `claro` (padrão) / `escuro` (opcional, persistido).
- `access_denied`: itens de navegação fora do escopo não são exibidos.

## Responsividade

- Navegação colapsa em menu compacto nos viewports estreitos (≤ ~720–860px).
- Header e breadcrumb sem rolagem horizontal; alvos de toque ≥ 44px.

## Endpoints `/api/v2` relacionados

- `GET /api/v2/me` — identidade, papel, escopo e navegação permitida.
- `POST /api/v2/auth/logout` — encerrar sessão.
- `PATCH /api/v2/me/preferencias` — tema, acessibilidade e idioma.
