# V2-01 — Mapa Executável Tela a Tela

> Decompõe cada uma das 30 telas de `style-system/` em contrato verificável:
> componentes, controles, rotas, atores, dados, regras e estados. Esta pasta é o
> insumo direto do backend ([`../21-plano-backend.md`](../21-plano-backend.md)) e
> da reconstrução visual. Fidelidade ao HTML exportado é obrigatória
> (ADR-D014); a V2 é produto único sem legado (ADR-D016).

## Como ler

Cada arquivo `telas/<slug>.md` mapeia uma tela do mockup a partir do HTML real
em `style-system/<slug>.html`. O mapeamento alimenta migrations, endpoints
`/api/v2`, policies e componentes de UI. Nada aqui é ilustrativo: todo controle
visível tem destino de implementação.

## Template de mapeamento (por tela)

```markdown
# Tela: <Título> (`<slug>.html`)

- **Rota web:** /<rota>
- **Módulo:** <módulo>
- **Atores/permissões:** <perfis com acesso e escopo>
- **Objetivo:** <para que serve a tela>

## Layout e componentes
<regiões da tela: shell, header, navegação, cards, tabelas, formulários, modais>

## Controles e ações
| Controle | Tipo | Ação | Endpoint/Evento | Regra/Validação |

## Dados exibidos
| Campo | Origem (tabela/campo) | Observação |

## Estados
<default, hover, focus, active, disabled, loading, empty, error, success, access_denied — quais se aplicam e como>

## Regras de negócio
<regras implícitas observadas no mockup>

## Responsividade
<comportamento nos 9 viewports; thresholds; navegação adaptativa>

## Endpoints `/api/v2` necessários
<lista de endpoints e métodos>

## Pendências/decisões
<adaptações seguras LGPD/segurança e dúvidas a resolver>
```

## Contratos compartilhados (valem para todas as telas)

### Estados visuais obrigatórios

`default`, `hover`, `focus`, `active`, `disabled`, `loading`, `empty`, `error`,
`success` e `access_denied`. Cada controle interativo declara quais usa.

### Matriz de viewports (sem rolagem horizontal indevida)

`360×800`, `390×844`, `430×932`, `600×960`, `820×1180`, `1024×768`, `1366×768`,
`1440×900` e `1920×1080`.

### Tokens e fundação visual

Fonte: `style-system/css/gov.css` (variáveis de cor, tipografia Raleway/Roboto
Mono, espaçamento, raio, sombra) e `style-system/js/app.js` (interações). Tema
claro é padrão; escuro é opcional e persistido. Não criar estilos ad-hoc fora
dos tokens (ver `docs/ui_token_gov_brasil.json` e `docs/SDGB.md`).

### Interações compartilhadas

Menu de usuário, breadcrumbs, tabs, filtros, busca, modais, editor de gabarito,
validação de formulários, confirmações e gráficos (barras/donut) com alternativa
acessível (WCAG 2.2 AA).

### Adaptações seguras (sem perda funcional)

- "Criar conta" pode exigir convite/validação institucional/aprovação, mas existe.
- "Remover/excluir" usa inativação, anonimização ou solicitação LGPD rastreável.
- Integrações e plano/uso têm estados reais de disponibilidade.
- Dados/indicadores estáticos do protótipo viram consultas reais `/api/v2`.

## Índice das 30 telas

| # | Módulo | Tela | Arquivo mockup | Mapa |
|---|---|---|---|---|
| 1 | Acesso | Acesso ao sistema | `login.html` | [login](login.md) |
| 2 | Dashboards | Painel genérico | `dashboard.html` | [dashboards](dashboards.md) |
| 3 | Dashboards | Painel admin | `dashboard-admin.html` | [dashboards](dashboards.md) |
| 4 | Dashboards | Painel aluno | `dashboard-aluno.html` | [dashboards](dashboards.md) |
| 5 | Dashboards | Painel coordenador | `dashboard-coordenador.html` | [dashboards](dashboards.md) |
| 6 | Dashboards | Painel diretor escolar | `dashboard-diretor-escolar.html` | [dashboards](dashboards.md) |
| 7 | Dashboards | Painel diretor de núcleo | `dashboard-diretor-nucleo.html` | [dashboards](dashboards.md) |
| 8 | Dashboards | Painel professor | `dashboard-professor.html` | [dashboards](dashboards.md) |
| 9 | Escolas | Lista de escolas | `escolas.html` | [escolas](escolas.md) |
| 10 | Escolas | Detalhe da escola | `escola-detalhe.html` | [escola-detalhe](escola-detalhe.md) |
| 11 | Equipe | Perfis e equipe | `perfis-equipe.html` | [perfis-equipe](perfis-equipe.md) |
| 12 | Equipe | Cadastrar membro | `membro-cadastrar.html` | [membros](membros.md) |
| 13 | Equipe | Editar membro | `membro-editar.html` | [membros](membros.md) |
| 14 | Turmas | Lista de turmas | `turmas.html` | [turmas](turmas.md) |
| 15 | Turmas | Detalhe da turma | `turma-detalhe-2.html` | [turma-detalhe](turma-detalhe.md) |
| 16 | Alunos | Cadastrar aluno | `aluno-cadastrar.html` | [alunos](alunos.md) |
| 17 | Alunos | Cadastrar aluno (redesign) | `aluno-cadastrar-redesign.html` | [alunos](alunos.md) |
| 18 | Alunos | Detalhe do aluno | `aluno-detalhe.html` | [alunos](alunos.md) |
| 19 | Alunos | Editar aluno | `aluno-editar.html` | [alunos](alunos.md) |
| 20 | Provas | Lista de provas | `provas.html` | [provas](provas.md) |
| 21 | Provas | Criar prova | `criar-prova.html` | [criar-prova](criar-prova.md) |
| 22 | Provas | Editor de gabarito | `gabarito.html` | [gabarito](gabarito.md) |
| 23 | Correção | Acompanhar correção | `acompanhar-correcao.html` | [correcao](correcao.md) |
| 24 | Correção | Acompanhar correção (turma) | `acompanhar-correcao-turma.html` | [correcao](correcao.md) |
| 25 | Resultados | Resultado individual | `resultado.html` | [resultados](resultados.md) |
| 26 | Resultados | Resultado dinâmico | `resultado-dinamico.html` | [resultados](resultados.md) |
| 27 | Relatórios | Relatório da prova | `relatorio-prova.html` | [relatorios](relatorios.md) |
| 28 | Relatórios | Relatório turma/prova | `relatorio-turma-prova.html` | [relatorios](relatorios.md) |
| 29 | Conta | Perfil | `perfil.html` | [perfil](perfil.md) |
| 30 | Conta | Configurações | `configuracoes.html` | [configuracoes](configuracoes.md) |

> O índice é atualizado conforme cada tela é mapeada. As variantes (ex.: dois
> cadastros de aluno) consolidam a **união** de capacidades numa só experiência.

> Componente compartilhado: [`_shell.md`](_shell.md) (govbar, header, navegação,
> menu de usuário, tema e breadcrumb) — referenciado por todas as telas
> autenticadas.

### Progresso do mapeamento

- [x] Acesso (1/1)
- [x] Dashboards (7/7) + shell compartilhado
- [x] Escolas + Equipe (5/5)
- [x] Turmas + Alunos (6/6)
- [x] Provas + Correção + Resultados + Relatórios + Conta (11/11)

**V2-01 concluído: 30/30 telas mapeadas.**
