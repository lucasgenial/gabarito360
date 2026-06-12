# Componentes Web

## 1. Escopo

O painel web do MVP usa Blade, Livewire e Tailwind conforme ADR-D009. A biblioteca visual deve ser criada no MP-019A e reutilizada pelas telas funcionais seguintes.

## 2. Estrutura recomendada

- Tokens convertidos em configuração semântica do Tailwind ou propriedades CSS geradas.
- Componentes Blade para apresentação reutilizável.
- Componentes Livewire apenas quando houver estado ou interação de servidor.
- Policies e regras do backend permanecem obrigatórias; ocultar um botão não autoriza uma ação.

## 3. Componentes fundamentais

| Componente | Diretriz |
|---|---|
| Button | variantes primária, secundária, perigo, neutra e textual; tamanhos dos tokens `components.button` |
| Input/Textarea/Select/DatePicker | label obrigatório, ajuda, erro associado e foco visível; input com altura de `48px` |
| Card | usar `components.card`; evitar cards apenas decorativos |
| Modal | usar `components.modal`; controlar foco, fechar por teclado e restaurar foco |
| Drawer | navegação ou tarefa contextual; não esconder ação crítica exclusivamente nele |
| Badge | sempre combinar cor e texto de estado |
| Toast/Alert | mensagem humana, duração suficiente e anúncio acessível |
| Tooltip | conteúdo complementar, nunca informação essencial |
| Tabs/Accordion | navegação por teclado e estado selecionado anunciado |
| Breadcrumb | indicar localização sem substituir título da página |
| Pagination | controles nomeados e estado atual explícito |
| Avatar | decorativo ou com alternativa textual adequada |

## 4. Tabelas e DataTables

- Usar tokens de `components.table`.
- Cabeçalhos devem identificar coluna e ordenação.
- Filtros ativos e critérios devem permanecer visíveis.
- Em telas estreitas, priorizar colunas essenciais ou apresentar lista responsiva.
- Ações por linha devem ter nome acessível e confirmação proporcional ao risco.
- Estados vazio, carregando e erro são obrigatórios.
- Dados pessoais devem ser minimizados e mascarados quando aplicável.

## 5. Layout administrativo

- Sidebar com largura `components.sidebar.width` e estado colapsado tokenizado.
- Cabeçalho com identidade do contexto atual, navegação e conta.
- Conteúdo com título, descrição curta, ações principais, filtros e resultado.
- Uma ação primária predominante por contexto.
- Breadcrumb em fluxos hierárquicos de núcleo, escola, turma e aplicação.

## 6. Responsividade

- Começar pelo breakpoint `xs`.
- Sidebar deve se transformar em drawer em telas pequenas.
- Formulários usam uma coluna quando necessário.
- Tabelas não devem causar perda silenciosa de informação.
- Alvos interativos devem ter área mínima adequada para toque.

## 7. Critérios de aceite

- Nenhum valor visual é hardcoded sem justificativa documentada.
- Todos os componentes funcionam por teclado.
- Estados claro e escuro são verificáveis.
- Componentes têm exemplo de uso e estados relevantes.
- Testes cobrem renderização, autorização funcional aplicável e interações críticas.

## 8. Implementação compartilhada

A fundação web consolidada na R4 está em:

- `backend/resources/views/components/ui/`: catálogo Blade compartilhado;
- `backend/resources/views/layouts/`: shell autenticado e público;
- `backend/resources/css/tokens.css`: espelho congelado dos tokens oficiais;
- `backend/resources/css/app.css`: composição, temas e estados baseados em tokens;
- `backend/resources/js/app.js`: tema, modal, drawer, toast e tabs acessíveis;
- `backend/tests/Feature/Web/DesignSystemTest.php`: contrato automatizado da fundação visual.

Catálogo disponível:

| Componente Blade | Uso |
|---|---|
| `x-ui.button` | botão ou link com variantes, tamanhos, disabled e loading |
| `x-ui.input`, `x-ui.textarea`, `x-ui.select`, `x-ui.date-picker` | campos com label, ajuda, erro associado e estado inválido |
| `x-ui.card` | agrupamento de conteúdo e variante de perigo |
| `x-ui.modal`, `x-ui.drawer` | diálogos nativos com fechamento previsível e retorno de foco |
| `x-ui.badge` | estado textual com variante semântica |
| `x-ui.table`, `x-ui.pagination` | tabela responsiva e navegação paginada nomeada |
| `x-ui.alert`, `x-ui.toast`, `x-ui.tooltip` | feedback persistente, temporário e complementar |
| `x-ui.accordion`, `x-ui.tabs`, `x-ui.tab`, `x-ui.tab-panel` | organização progressiva com navegação por teclado |
| `x-ui.breadcrumb`, `x-ui.avatar`, `x-ui.account-menu`, `x-ui.theme-toggle` | localização, identidade e controles do shell |
| `x-ui.kpi`, `x-ui.chart` | indicadores e gráficos com contexto e alternativa tabular |
| `x-ui.loading` | progresso anunciado |
| `x-ui.error-state` | falha com recuperação possível |
| `x-ui.empty-state` | ausência de conteúdo com orientação |

Exemplo:

```blade
<x-ui.input
    name="nome"
    label="Nome"
    :value="old('nome')"
    help="Informe o nome institucional."
    required
/>

<x-ui.button type="submit" :loading="$processando">
    Salvar
</x-ui.button>
```

Exemplo de modal e estados:

```blade
<x-ui.button variant="danger" data-modal-open="confirmar-inativacao">
    Inativar
</x-ui.button>

<x-ui.modal
    id="confirmar-inativacao"
    title="Confirmar inativacao"
    description="O historico sera preservado."
>
    <x-ui.alert variant="warning">Revise o impacto antes de continuar.</x-ui.alert>
</x-ui.modal>

<x-ui.loading label="Carregando resultados" />
<x-ui.error-state title="Falha ao carregar">Tente novamente.</x-ui.error-state>
<x-ui.empty-state title="Nenhum resultado">Revise os filtros.</x-ui.empty-state>
```

Estados aplicáveis:

| Componente | Estados cobertos |
|---|---|
| Button | padrão, hover, foco, disabled e loading |
| Input, Textarea e Select | padrão, foco, disabled e erro associado |
| Modal | fechado, aberto, fechamento por botão, clique externo e tecla Escape nativa |
| Alert e Badge | sucesso, informação, atenção, perigo e neutro |
| Loading, Error e Empty State | progresso anunciado, falha recuperável e ausência orientada |

## 9. Extensões de layout justificadas

Os valores abaixo não representam identidade visual nova. Eles resolvem necessidades estruturais que não possuem token oficial equivalente e ficam centralizados em propriedades CSS:

| Propriedade | Valor | Justificativa |
|---|---:|---|
| `--layout-auth-max` | `480px` | limita formulários de autenticação para leitura confortável |
| `--layout-filter-control-min` | `180px` | mantém filtros utilizáveis antes da quebra de linha |
| `--layout-table-min` | `720px` | preserva colunas essenciais e ativa rolagem horizontal controlada |
| `--layer-skip-link` | `50` | garante que o atalho de conteúdo permaneça acima do layout |

Os breakpoints repetidos nas media queries correspondem diretamente a
`breakpoints.sm`, `breakpoints.md` e `breakpoints.lg` do JSON. CSS não permite
usar custom properties em condições de media query.

A lista completa e o contrato congelado estão em
[`tokens-web-r4.md`](tokens-web-r4.md).

## 10. Shell Canônico da R4

- celular e tablet usam cabeçalho compacto e `x-ui.drawer`;
- desktop a partir de `breakpoints.lg` usa sidebar persistente;
- breadcrumb e menu da conta fazem parte do layout autenticado;
- tema claro é o valor inicial declarado no HTML;
- alternância de tema é persistida localmente e não depende da preferência do
  sistema operacional;
- a rota raiz direciona ao painel autenticado e não exibe página demonstrativa
  estática.
