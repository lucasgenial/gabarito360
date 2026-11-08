# Ajuste P3.2 — cards sem dados

Planejamento revisado em08/11/2026, antes da implementação. Base main86ef35f, homologação764b920. Pedido posterior: preservar as mensagens de ausência de resultados e reduzir os cards à altura necessária. O título como ajuda contextual foi aprovado como padrão do projeto.

Pré-consulta UI/UX e Product Design: capturas atuais confirmam reserva de h12 nas listas vazias e h9 nos gráficos vazios. Apenas encolher o conteúdo deixa o wrapper e a seção com a mesma área ociosa. Usar uma projeção de leitura separada da disposição canônica. Fonte: operacao-20261102/paineis-vazios-design-previo.

| Trabalho | Critério |
|---|---|
| Estado vazio confirmado | Marcar somente gráficos com base confirmada zero e listas disponíveis sem itens; erro, falta de permissão e carregamento continuam distintos |
| Desktop em leitura | Medir título e mensagem em fluxo natural, padding/borda/gap e margem da grade em pixels CSS locais; quantizar em células48 sem usar zoom visual como altura |
| Projeção | Reduzir altura somente dos vazios; manter x/larguras; compactar y dos não fixos sem colisões. Pin vazio mantém x/y; pin com dados mantém caixa completa |
| Preferências | Nenhum PUT automático. Não alterar layout/saved/dirty. Organizar restaura geometria canônica antes dos gestos; Salvar envia somente o mapa canônico |
| Mobile/fallback | Altura natural para a mensagem; preservar os controles de ajuda e mensagens, sem reservar a altura de um gráfico |
| Conteúdo real | Gráficos, listas com alunos, resumo fixo, dados e fórmulas permanecem preservados |
| Padrão de ajuda | Registrar título contextual emAGENTS, sem migrar accordions operacionais fora deste ajuste |

Aceitação: vazio total/misto, nomes e mensagens longos, zoom125/150, viewport320/390, leitura→edição→cancelar, SaveReload e pins. Conferência independente atual no Chrome e CI obrigatório antes do merge. Publicar somente na homologação com backup e comparação integral; sem API/DDL/produção. O refinamento dos documentos e P3.3 continuam separados.
