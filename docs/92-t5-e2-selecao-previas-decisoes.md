# 92 — T5-E2: seleção, prévias e decisões

Revisão antes da implementação, após merges #123/#124. E1 (#125) continua em rascunho por conferência visual pendente. Trabalho dependente de E1, sem deploy.

## Divisão revisada

| Parte | Entrega | Fronteira |
|---|---|---|
| E2a — este incremento | Seleção paginada de provas e prévia dos materiais na origem | Somente leitura; não cria lote, revisão, destinatário ou decisão |
| E2b | Identificação explícita do destinatário e duas decisões para materiais | Revisar compartilhamento mínimo e confirmação do espaço de destino antes de expor criação/aprovação HTTP; nunca receber credenciais/atores de outro contexto |
| E2c | Cadastro e resultados: dependências, evidência restrita, guarda e reconhecimento do legado | Não reaproveitar revisão de material; não divulgar documento ao outro contexto |
| E3 | Execução, revogação/retentativa e consulta do acervo | Ações explícitas e revalidação dos contratos já implementados |

A interface começa por materiais, conforme a ordem do plano T5. Os serviços internos das outras modalidades permanecem disponíveis para ensaios; este incremento não declara E2 concluído.

## Seleção de materiais

Usar o vínculo autenticado e os espaços de origem derivados pelo acompanhamento E1. Catálogo paginado antes de exibir contagens; nomes/títulos somente da origem autorizada. Não listar destinatários, outras identidades, matrículas ou dados de alunos. A permissão de destino sozinha não libera o catálogo.

Seleção explícita de 1–20 provas da página atual, sem selecionar todas automaticamente e sem acumular seleções ocultas entre páginas/espaços. Cada material precisa de gabarito íntegro e projeção positiva `MaterialCopia`. A prévia mostra os campos efetivamente incluídos e as questões, permite voltar e modificar a seleção, e informa que os textos ainda precisam de revisão antes de qualquer autorização. Não classificar automaticamente texto livre como anônimo.

POST somente de consulta para não colocar IDs selecionados no URL. Aceitar apenas espaço de origem e IDs de provas; nenhum usuário/vínculo/destinatário/digest fornecido pelo cliente serve de autorização. Revalidar acesso e toda a seleção no POST. Resposta não é uma decisão nem versão histórica; nenhuma persistência ou fila. Limites de 64 KiB por prova e 512 KiB por prévia, além dos 20 itens.

Flags de interface/API permanecem desligadas. Web conserva contexto por aba e CSRF, escapa campos livres e nunca propaga mensagens internas da API. Erro de seleção/gabarito exibe mensagem genérica na própria seleção, sem mascarar falta de acesso como sucesso.

## Critérios de verificação

Testar paginação/isolamento, perfil e capacidade, flags, IDs repetidos/alheios, gabarito incompleto, limites, mudança de vínculo e zero efeitos persistentes. Web: seleção explícita, POST com contexto/CSRF, escape dos textos e falha sem exposição de mensagens internas. Executar SQLite/MariaDB e cinco checks, incluindo restauração D3. Conferência visual mantém-se pendente enquanto o computer-use estiver bloqueado.

Nova tentativa de observar o Chrome em 02/11 foi interrompida pela ferramenta por não conseguir determinar a URL atual com confiança suficiente. Não houve entrada de dados ou tentativa de contornar o bloqueio. Manter E1/E2a em rascunho até a conferência visual.
