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

