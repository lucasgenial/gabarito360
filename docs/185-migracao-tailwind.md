# Tailwind — migração visual do conjunto de telas

Planejamento revisado em08/11/2026, antes de alterar a interface. BaseGit375a1dc; homologação6ced29b. Substitui a abordagem de ajustar o CSS próprio descrita originalmente em184. Esta prioridade transversal antecedeP3.3; não representa implementação ou publicação concluída.

## Decisão do responsável

Adotar TailwindCSS efetivamente e preservar a paleta do Gabarito360. Fontes, espaçamentos, raios, sombras e composição podem mudar para uma base coerente com a escala do framework. Não reproduzir o CSS anterior sob novos nomes nem introduzir uma conciliação permanente. Preservar ativos de marca, conteúdo, funções e requisitos do produto não equivale a preservar a estética antiga.

Avaliar o conjunto de telas e fluxos. O responsável identificou a referência https://avalionedu.com.br/ e esclareceu que considera a aparência válida, mas deseja identidade própria para oGabarito360. Isso não equivale a uma alegação de cópia nem autoriza reproduzir a referência.

## Comparação visual inicial e direção própria

Inspeção somente da página pública e das imagens de demonstração divulgadas nela: /demo/telas/resultados.webp e /demo/telas/cartao-detalhe.webp. Evidências locais em operacao-20261102/tailwind-referencia/avalion-resultados.png e avalion-cartao.png. UI/UX e ProductDesign compararam a demonstraçãoResultados à captura atualG360Turmas (botoes-sm-auditoria/01-turmas-tabela-desktop.jpg). Não houve acesso autenticado aoAvalion,criação de conta,importação de seus ativos para o produto ou auditoria integral das suas telas.

Há convenções compartilhadas observáveis: navegação lateral,topo branco,fundo claro,superfícies brancas arredondadas,faixa de quatro indicadores e tabela. Isso explica familiaridade visual; não demonstra cópia. As capturas mostram tarefas diferentes e a capturaG360 está rolada: não é comparação equivalente de todos os fluxos/cabeçalhos.

Direções complementares aprovadas para desenvolvimento:

1. **Cabeçalho de trabalhoG360:** integrar espaço/perfil,título,breadcrumb e ação primária à hierarquia da página. Evitar caixas independentes para cada faixa de contexto. Rótulos legíveis na navegação expandida; menu recolhido continua opcional.
2. **Hierarquia por entidade e decisão:** identidade/brasão da turma ou prova, resumo cadastral compacto e operação principal acessível. Detalhes organizam estudantes e análises pedagógicas na ordem já definida para o produto; não inventar indicadores ou funções para parecer diferente.
3. **Superfícies mais enxutas:** reservar cards para tarefas/dados independentes,integrar controles à tabela,reduzir caixas aninhadas. Usar escala tipográficaTailwind,separadores e espaço para distinguir títulos,dados e metadados. Mobile mantém a prioridade operacional e alvos44; gráficos preservam a paleta semântica.

A originalidade será trabalhada na composição e coerência do conjunto. AdotarTailwind ou trocar somente cores não comprova diferenciação. As escolhas de cada família serão registradas na fundação e avaliadas visualmente; defaultsTailwind são uma escala, não um design único pronto.

## Situação técnica confirmada

- apps/web/package.json e vite.config.js declaram Tailwind4, e resources/css/app.css o importa. Os layouts atuais carregam public/css/app.css e extensões próprias; a interface operacional não está padronizada efetivamente pelo Tailwind.
- O inventário inicial local encontrou56 tags de tabela em47 views,53 usos de x-tabela e14 fontesJS que geram controles. Esses números são estáticos aproximados: includes, estados e permissões exigem revisão de renderização.
- Tailwind fornece utilitários, escala visual e mecanismos de tema/responsividade. Não fornece por si uma aparência pronta de tabela, modal ou botão. A composição será definida em componentes compartilhados, usando seus defaults/escala e coresG360.
- Pré-consulta independente UI/UX e ProductDesign realizada no agente product_design_review. A direção foi atualizada após a decisão humana: a estética anterior não é critério obrigatório; somente aprovar o resultado após avaliar a composição e os fluxos completos. A aprovação prévia de direção não é aceite visual da implementação.

## Base de composição aprovada para desenvolvimento

| Família | Direção inicial |
|---|---|
| Tipografia | font-sans de sistema, text-sm/leading-5 nas ações/dados densos; hierarquia com a escalaTailwind, sem fontes carregadas apenas para reproduzir a aparência anterior |
| Paleta | Cores de marca e semânticasG360, com estados coerentes claro/escuro e contraste; não trocar significados por tons decorativos |
| AçõesSMALL | min-h-8,px-3,gap-2,rounded-md,text-sm,font-medium,ícones size-4; altura cresce quando o rótulo quebra; alvo44px em toque/tela pequena sem sobreposição de hitboxes |
| Conteúdo de botões | Texto curto ou ícone+texto para consulta/edição; ícone isolado somente quando reconhecível e com nome acessível. Editar/Arquivar/Desarquivar turma mantêm texto e ícone. Publicar/Salvar/Transferir/Encerrar/Retirar preservam efeitos explícitos |
| Tabelas | px-3/py-2 nas células, alturaauto determinada pelo maior conteúdo da própria linha, dados legíveis e ações em fluxo/flex-wrap/gap-2. Sem reserva genérica150px, margens cumulativas ou truncamento usado para forçar compactação |
| Cards | rounded-lg,borda,shadow-sm,p-4 como ponto de partida; títulos e ações alinhados. Cards de registros independentes não são esticados globalmente ao mais alto |
| Estados | Foco visível,disabled/loading/erro/vazio distintos; botões conservam semântica nativa e proteção contra duplo envio; estados extremos fazem parte da revisão |

Essa direção é base de implementação, não uma especificação imutável sobre cada widget. GridStack,gráficos,calendários,fotos,recorte e leitoresPDF têm contratos próprios que precisam de integração explícita.

## Execução e limites

| Etapa | Entrega e aceite | Situação |
|---|---|---|
| TW1 — inventário e fundação | Prévia de componentes, pipeline e revisão do conjunto | Concluída; aprovação humana da rodada4 |
| TW2 — estrutura e composição completa | Shell autenticado: navegação,topo,títulos,breadcrumbs,tipografia e área útil; tela representativa com tabela/cards/formulário/modal para validar o conjunto desktop/mobile | Pendente |
| TW3 — ações,tabelas e cards | Todas as famílias inventariadas e seus renderersJS; controlesSMALL e linhas naturais; paginação/ordenação/seleção/permissões preservadas; retirar CSS concorrente das famílias convertidas | Pendente |
| TW4 — formulários e diálogos | Campos,validação,confirmações,feedback e fluxos completos; sem confirmação nativa do navegador; preservar CSRF,intenção,versão e tratamento de falha | Pendente |
| TW5 — painéis e ferramentas | GridStack,ECharts,agenda,imagens/cropper,cartões-resposta e leitores; ajuda pelo título,loading/vazio/erro e disposição por usuário/perfil preservados | Pendente |
| TW6 — demais superfícies | Inventariar e migrar explicitamente autenticação,site público,erros/e-mails/documentos conforme seu renderizador. PDF/print têm escopo próprio e comparação visual; não recebem reset incidental | Pendente |
| TW7 — validação transversal | Percorrer telas e fluxos por perfil,desktop/mobile,temas,zoom,teclado,permissões e estados; comparação visual independente atual e CI por etapa antes da integração/publicação | Contínua; nenhuma interface nova validada ainda |

As subdivisões técnicas serão executadas autonomamente. Não apresentar como concluída uma etapa que só recebeu classes novas em uma parte da tela. Registrar fronteiras ainda pendentes com clareza. Publicar entregas completas e validadas somente na homologação; produção continua dependente da decisão explícita do responsável.

## Arquitetura de implementação

1. Manter componentesBlade compartilhados para evitar que cada tela ou perfil invente classes/tamanhos. Mapas de variantes usam classes completas detectáveis pelo compilador, inclusive controles criados emJS.
2. CompilarTailwind localmente/CI com dependências travadas e asset versionado conforme o mecanismo de publicação do projeto. Não usar CDN/runtime de desenvolvimento em homologação/produção.
3. Controlar fronteiras de Preflight e cascade layers. Estilos legados não-layered podem sobrepor utilities. Retirar regras antigas da família migrada; não usar!important global,apagar CSS indiscriminadamente ou aplicar reset sobre telas não auditadas. Uma fronteira técnica transitória não autoriza reproduzir a estética antiga.
4. A adoção visual não altera contratoAPI,identificadores,autorização,sessão,CSRF,açõesHTTP,dados ou estrutura do banco. IDs/classes usados como hooks devem ser identificados antes de trocar markup; usar data-* para novos comportamentos e JS externo.
5. Preservar impressão/PDF e dados existentes. Após cada etapa, comprovar a extensão real da migração em uma matriz por família/template; uma auditoria estática não comprova conferência visual em todos os perfis.

## Contratos de produto mantidos

Área útil ampla/fullviewport,responsividade320/390 e desktop comzoom,ordem de leitura,teclado/foco,permissões e dados íntegros. Tabela/cards conservam dados e operações; matrizes não viram cards por inferência. Ajuda complementar pelo título/popover; não substituir conteúdo operacional por ajuda. Dados demonstrativos identificados. PreferênciasGridStack/KPIs fixos e fluxo de fotos/cartões/PDF continuam funcionando. Essas são exigências de uso, não a estética anterior que foi substituída.

## Referências oficiais

- https://tailwindcss.com/docs/theme
- https://tailwindcss.com/docs/styling-with-utility-classes
- https://tailwindcss.com/docs/preflight
- https://tailwindcss.com/docs/detecting-classes-in-source-files
- https://tailwindcss.com/docs/hover-focus-and-other-states

## TW2 — planejamento revisado após aprovação humana da rodada4

O responsável aprovou o conjunto da prévia e autorizou aplicar o padrão nas telas reais e publicar SOMENTE na homologação. PR222 integrado, baseafc8900. TW1/aceite da prévia concluídos. TW2 substitui a estrutura autenticada inteira: header único, lateral256/80 em fluxo desktop/tablet>=768, menu branco/darknavy, avatar como acionador da conta com nome/e-mail/perfil/troca condicional/saídaPOST, folha inferior móvel, MeuEspaço fixo na lateral expandida e recolhível/fullwidth no conteúdo, títulos/breadcrumbs/tipografia/rodapé/área útil.

Fronteira técnica: nova folhaTailwind compilada semPreflight geral; estilos da estrutura removidos de uma saída operacional derivada do legado, mantendo app.css original para público/auth/print externos. Nenhum novo reset incide nos gráficos, tabelas, formulários ou PDFs ainda não migrados. Escalazoomautomática do legado deixa de reger a nova casca. Reutilizar marcaSVG/fonteslocais e ícones oficiais. Preservar IDs/hooks, menus de permissões, CSRF/revisão/seleção de vínculo/sessão/rotas/API. Não importar o WIPc0216e5 integralmente.

Percurso representativo: Painel e Turmas(lista/cards/detalhe/editar/modal) para conferir composição e ações existentes dentro da nova casca. A migração integral dos componentes internos permanece emTW3(ações/tabelas/cards),TW4(campos/validação/modais) eTW5(painéis/mídia); não declarar essas famílias concluídas por receberem o novo shell. Shell compartilhado será conferido também com perfis escolares/pessoal/interno em testes, e visual real na homologação.

Entrega: testes locais funcionais e de permissão/contexto, buildreproduzível/CI5checks, revisão independente de fonte+UI atual antes integração, backup integral dos dados atuais congelados/arquivos e rollback de código, pacote com allowlist exataWeb/API0 e guardas homologação. Nenhuma migration/DDL necessária ou produção autorizada. Deploy apenas ao fecharTW2, após testes e revisões. Validação Chrome autenticada após publicação e tabela atualizada ao final.
