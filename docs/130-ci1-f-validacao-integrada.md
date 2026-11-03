# CI1-F — validação integrada do catálogo nacional

03/11/2026. Continuação autorizada após a publicação H1/CI1-E. A regra vigente é publicar primeiro em homologação; a produção depende de decisão explícita posterior. Este documento começa como plano, sem antecipar aprovação dos ensaios.

## Plano revisto antes da execução

| Divisão interna | Verificação e saída |
|---|---|
| F1 — evidências e isolamento | Reutilizar CI e recuperação H1 já comprovados. Ensaios de escrita/atualização apenas no clone9120, com interface desconectada e firewall DROP; não alterar dados acadêmicos da VM120 nem produção. |
| F2 — concorrência nacional | Vinte processos PHP com vinte CONNECTION_ID distintos, confirmados simultaneamente no processlist antes de liberar a barreira. Cada processo consulta código, município/tipo, universidade, sigla, nome e paginação no catálogo nacional. Zero erro, campos públicos mínimos, sem repetição entre páginas. |
| F3 — atualização e preservação | Nova edição controlada no clone: pausa não troca ponteiro; retomada conclui; IDs e referências antigas preservados; override manual continua prevalecendo; edição antiga recusada. Comparar conteúdo acadêmico antes/depois. Recuperação completa da VM/arquivos já registrada no129. |
| F4 — fluxo integrado no Chrome | Conferir catálogo real e percurso seleção/solicitação/atendimento, desktop e celular. Testes com marcadores fictícios e escopo próprio, sem transmitir dados reais ou enviar e-mails. Não reutilizar aprovação local como prova de integração real. |
| F5 — fechamento | Registrar resultados, limites, CI do head exato, revisão e Git. Publicar entrega operacional em homologação; não promover produção. Atualizar tabela geral. |

Metas definidas antes da carga: vinte conexões coexistentes; vinte trabalhadores, cinco amostras por caso após uma passagem inicial registrada separadamente; p95 de código até1.000ms, município/tipo até2.000ms, universidades até3.000ms e texto/sigla até5.000ms. Memória PHP por processo até128MiB. Registrar p50/p95/máximo, erros e condições da VM2vCPU/4GiB. Não excluir amostras lentas do conjunto medido; falha de meta exige análise e correção ou pendência explícita, não redefinição retrospectiva.

Os testes existentes de duas identidades, escopo institucional, revogação, versão obsoleta, idempotência e rollback de evento/aviso continuam no CI MariaDB/SQLite. Não repetir a suíte inteira sem mudança ou preocupação nova. Os arquivos preexistentes de L4-D/E permanecem fora deste incremento.

## Situação inicial

CI1-A–E integrados e publicados; nacional216.753 observações; Chrome local aprovado pelo especialista; HTTP20 requisições e recuperação VM completa aprovados. Falta consolidar as provas de concorrência SQL, atualização com o volume nacional e fluxo visual integrado. CI1-F ainda aberto.

## Resultados em execução

F2 confirmou vinte CONNECTION_ID distintos coexistentes no processlist,500 amostras medidas mais100 iniciais, zero erros e22MiB de pico PHP por trabalhador. A primeira rodada reprovou texto/sigla: p95 nome7.323,47ms e sigla5.276,07ms. EXPLAIN identificou uma subconsulta correlacionada por entrada para descobrir a edição manual, inclusive sem edição manual ativa. A consulta agora captura os IDs manuais no conjunto de edições já fixado e faz a exclusão diretamente pelo índice publicação/unidade; sem edição manual, não executa exclusão inútil. Não alterou busca parcial, acentos, campos públicos ou precedência. Não acrescentou cache ou índice/migration.

Repetição com as mesmas metas: código55,38ms, município/tipo82,10ms, universidades225,97ms, sigla1.198,61ms e nome1.858,82ms (p95). Rodada25,19s versus88,28s inicial. Primeira passagem registrada separadamente; isso não é cache frio garantido. Nova regressão troca a edição manual entre COUNT e página, preserva a resposta anterior coerente e não ressuscita edição manual antiga na consulta seguinte.

F3 executado exclusivamente no clone9120: revisão fictícia derivada do arquivo público nacional2025, sem apresentá-la como nova fonte oficial. Staging e projeção pausados em5.000/214.192 sem trocar vigência; retomada concluiu mantendo as214.192 identidades oficiais. Override manual e snapshot pessoal anterior preservados. Ano anterior recusado pela política; repetir importação já publicada antiga retorna seu recibo sem reativá-la. Nove tabelas acadêmicas idênticas por conteúdo.261,50s para preparação/promoção; pico178MiB do ensaio inteiro, que inclui dois vetores de214.192 IDs para comparação — não é medição isolada da memória do leitor/importador. Origem e homologação ativa não receberam essas mutações fictícias.

Conferência integrada usa web real local no Chrome, ponte autenticada SSH/QGA e API/MariaDB reais do clone com a interface de rede desligada. Sem Http::fake e sem fixtures de respostas. Duas contas fictícias existem somente no clone. A sessão da conta real na homologação permanece preservada. A ponte é infraestrutura de ensaio fora do Git, não código da aplicação nem caminho de publicação.

## Ajuste de escopo solicitado durante F4

O responsável reprovou o desenho da busca e pediu revisão especializada. Depois determinou como padrão global que as telas ocupem toda a área útil do viewport. O revisor independente abriu as capturas reais desta execução e reprovou a grade de nove campos: nome na segunda linha, filtros técnicos competindo, CTA tardio no celular e botões municipais com hidden vencido por CSS. Aprovou a direção de implementação, não a versão visual final.

Busca pessoal reorganizada: nome primeiro, localização/tipo agrupados, refinamento progressivo, percurso próprio de código por links GET com contexto preservado. Trocar percurso inicia busca limpa; URLs já combinadas continuam mostrando e aplicando todos os critérios, com seção correspondente aberta. Não adivinhar namespace pelo número. Resultados empilhados, origem/código/ano explícitos, informação manual preservada e singular/plural correto.422 retorna na própria tela, preserva valores e encaminha foco; erros de serviço continuam503, nunca resultado vazio. UI/mode/contexto não são encaminhados como filtros da API.

Padrão global registrado no AGENTS: largura100% e altura mínima útil no layout compartilhado, margem responsiva, grupos empilhados, texto com limites de leitura próprios. Removidos limites locais de superfícies principais do catálogo, pedidos, convites e acessos internos. Login, diálogos e documentos mantêm suas medidas próprias. Espaço de rodapé/nav/barras passa a ser reservado no conteúdo principal, sem soma de padding no body móvel. Aprovação final depende de capturas novas2560/1440/390, menu aberto/recolhido, tabela, formulário, tela curta, overflow e teclado/rodapé. Não publicar UI enquanto essa revisão estiver pendente.
