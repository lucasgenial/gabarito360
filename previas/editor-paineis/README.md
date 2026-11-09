# Prévia local — editor de painéis

Protótipo isolado do aplicativo, com dados fictícios. Não chama API, banco, autenticação, e-mail, homologação ou produção. O seletor Contexto simula usuários/perfis/vínculos; não representa autorização real. A integração operacional depende de aprovação humana desta prévia e de um contrato servidor específico.

## Executar

Na raiz deste checkout, com Node e dependências do projeto disponíveis:

```powershell
npm ci --prefix scripts/tailwind
npm ci --prefix previas/editor-paineis
npm run build --prefix previas/editor-paineis
npm test --prefix previas/editor-paineis
npm run dev --prefix previas/editor-paineis
```

Abrir `http://localhost:9020/`. Servidor escuta somente127.0.0.1. `G360_PREVIA_PORT` altera a porta antes de iniciar. O build emite somente `previas/editor-paineis/dist`; os ativos operacionais não são recompilados nem modificados. Reutiliza marcas, fontes, ícones e ECharts locais do projeto. SortableJS1.15.7/GridStack14.1.0 ficam travados no lockfile; licenças acompanham a distribuição.

## Conferir

- Menu Turmas/Turma3ºA demonstra os dois painéis. Organizar painel aparece em desktop com ponteiro preciso, viewport mínimo1024×700. Mobile mostra a composição previamente salva ou o padrão do perfil, sem edição. Reduzir uma janela com rascunho não salva nem descarta esse rascunho.
- Distribuição preserva o quadro de colunas: cada coluna raiz corresponde a um painel, e as seções filhas são contêineres embutidos. Arraste itens ou use o select para escolher pai. Setas e teclado oferecem alternativas. Esquerda→direita = base→topo somente nas colunas; filhos têm ordem vertical natural.
- Clique nos títulos para editar; confirmar/cancelar e restaurar título são explícitos. Mostrar no painel oculta/reativa sem remover o widget do catálogo.
- Ajustes finos: GridStack12colunas na raiz e subgrades. Widgets avulsos e painéis compartilham a raiz; seções podem conter widgets e seções filhas. Setas movem e Shift+setas redimensionam; pin preserva posição/tamanho e pai. Não há transferência entre grades nessa aba.
- A altura mínima da seção acompanha o cabeçalho já dimensionado e os filhos efetivamente posicionados. É possível retirar a sobra inferior sem cortar gráficos, inclusive em seções filhas. O gesto não reutiliza a altura antiga como limite nem recoloca espaços de uma geometria anterior; ao concluir ou cancelar, preserva a posição visível e o controle focado. Alturas maiores escolhidas manualmente continuam sendo preferências válidas.
- Mouse e teclado atualizam o mesmo rascunho em todos os níveis: raiz, widget interno e widget de seção filha. O ciclo de drag/resize é recebido uma vez pela raiz, conforme o contrato do GridStack14, e validado pelo item/grade/tipo do gesto. Ver painel mantém a geometria escolhida; Salvar mantém a disposição local ao reabrir. Escape, troca para mobile e desmontagem invalidam gestos pendentes, impedindo commits atrasados.
- Padrão com três painéis configurados por perfil. Novo painel e Criar nova seção no select usam o mesmo diálogo. Remover painel preserva os filhos no nível acima e só confirma a preferência ao Salvar. Painéis sem descendentes visíveis somem na leitura. Esta prévia permite até15painéis e dois níveis de seções, sem ciclos.
- Ver painel confere o rascunho. Salvar disposição grava apenas neste navegador; Cancelar descarta alterações desde a entrada. Restaurar padrão carrega o padrão no rascunho e exige Salvar para mantê-lo.
- Contexto permite conferir isolamento fictício por usuário/perfil/vínculo/painel/turma e simular falha/conflito. No conflito, preservar rascunho e carregar explicitamente a versão salva; nunca repetir gravação automaticamente.

As chaves locais começam com `g360:previa-editor:v2:` e não usam sessões/usuários reais. A comparação de revisão local é uma simulação otimista; localStorage não oferece transação atômica entre abas. A futura API deve garantir autorização, CAS/idempotência e auditoria no servidor. Não transportar este payload para os endpoints atuais.

## Limites do ensaio

Dados pedagógicos e cadastros são demonstrações, não extração dos dados reais; não validar indicadores/decisões educacionais por eles. A tabela é uma representação reduzida para demonstrar um widget de lista, não a migração completa dos fluxos CRUD. Parecer visual em `design-qa.md`; registros técnicos e capturas ficam no diretório operacional externo ao Git. Nenhuma publicação está incluída nesta entrega.
