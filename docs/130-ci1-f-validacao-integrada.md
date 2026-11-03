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

## Fechamento técnico e parecer independente

UI/UX da busca e geometria global **aprovadas** pelo revisor independente product_design_review no conteúdo de aplicação `f8e4ef32d58ead912861a738f3b7155048c7f136`. O revisor abriu código e capturas atuais: nome/localização e código, resultados,422 preservando valor/foco, desktop1440/2560, menu recolhido, mobile390, tabela, formulário e tela curta. Duas capturas iniciais com pintura antiga foram rejeitadas e substituídas antes do parecer final. Menu recolhido confirmado na classe do elemento html (85px físicos com zoom1,25); CTA móvel inteiro com foco acima do rodapé. Não é certificação completa de acessibilidade.

Em2560: main2270px após menu290px; formulário2130px, margens70px. Em390: nenhuma rolagem horizontal do documento, CTA de busca e de complemento inteiros acima do rodapé740px. Tela curta preenche1440px de altura em2560×1440 e844px em390×844, sem scroll vertical artificial. Evidências fora do Git em operacao-20261102/ci1-f-*, medidas e resultados agregados sem dados de pessoas em docs/evidencias/ci1-f-catalogo-20261103.json.

Fluxo integrado real no clone: envio pelo formulário → fila interna → iniciar análise → pedir complementação → mensagem no próximo passo do proprietário → município preservado → reenviar no mesmo protocolo. Duas identidades autenticadas pela API: proprietário200, detalhe do outro404, lista do outro vazia, pessoal sem fila interna403, suporte sem alunos403, atendimento autorizado200. Tokens próprios do ensaio HTTP encerrados; sessão web e contas originais preservadas.

Nova rodada depois da atualização nacional, com edição manual ativa e histórico de edições:20conexões coexistentes,500 amostras, zero erros,22MiB por trabalhador. P95 código68,58ms, município112,79ms, universidade235,92ms, sigla1.730,97ms e nome2.017,99ms; todas as mesmas metas atendidas.

CI41730791649 do head f8e4ef3 aprovado nos cinco jobs. Ajuste operacional posterior permite identificar a fase no rodapé sem nome CI1-E fixo: quarto argumento opcional do deploy.py, restrito a letras maiúsculas/números/hífen/underscore; padrão HOMOLOGACAO. Conteúdo de apps permanece idêntico ao aprovado pelo especialista, a conferir por git diff antes de publicar. Novo head operacional/documental exige seus próprios checks e recibo de homologação. Sem migration nova nesta entrega.

O responsável ampliou o trabalho para padronizar todas as tabelas/cards e preservar suas ações. O inventário independente encontrou41tabelas/33views e listas equivalentes; TBL1 é pacote próprio. A tabela de solicitações ainda fica comprimida no desktop por sua estrutura antiga e não recebe aprovação de novo padrão aqui. Esta limitação segue explicitamente no TBL1; não confundir aprovação da busca/geometria com conclusão das41tabelas. Produção permanece sem promoção.

| Pacote | Situação ao fechar a validação |
|---|---|
| H1 | Homologação isolada e recuperação completa validadas |
| CI1-A–E | Integrados e publicados anteriormente |
| CI1-F | Técnica e UI aprovadas; publicação do head final em homologação após CI |
| CP1 | Disponível conforme perfil |
| TBL1-A–D | Auditoria/plano definidos; implementação separada, ações preservadas |
| ID1/VI1/DC1/CP2/MT1/TR1/TE1/PA1/UX1 | Planejados, sem antecipar implementação |
| Produção | Preservada; promoção exige decisão explícita |
