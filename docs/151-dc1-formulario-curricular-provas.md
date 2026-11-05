# DC1 — formulário curricular das provas

04/11/2026. Planejamento revisto antes da implementação. O PR180, head64f836d, passou nos cinco checks. Consulta prévia ao agente UI/UX e Product Design confirmou a composição abaixo; aprovação visual final depende da interface funcionando no Chrome.

O formulário institucional ocupará a largura útil, empilhando Turmas, Disciplinas da prova e Dados. Reutilizar tokens, card-pad, x-tabela compacta e orientação recolhida. Remover a coluna lateral/stepper contraditório neste caminho. Criar somente no comando final; adicionar/remover disciplina e atualizar opções apenas reexibem o preenchimento. O fluxo pessoal e o módulo desligado preservam a tela anterior.

Uma página própria configura turmas/disciplinas do rascunho; a edição rápida de metadados não envia currículo. A API deve informar se a classificação pode ser alterada, incluindo publicação anterior, gabarito publicado, cartões ou notas; não inferir somente pelo status atual. Publicadas/aplicadas exibem resumo histórico somente leitura. Cópias sem currículo mostram a ação Configurar, sem inferência textual.

Implementação progressiva: todas as ações intermediárias funcionam por POST com CSRF e contexto explícito, sem exigir campos de Dados ainda vazios. JavaScript aprimora a atualização das opções e seleção por fragmento HTML, com estado de carregamento/erro, cancelamento e descarte de resposta antiga. Nenhum token API no navegador. Preservar seleções inválidas com motivo e Remover, distinguir ausência de opção comum de falha de comunicação. API de opções também aceitará POST de leitura para evitar URLs extensas de muitas turmas/IDs.

Conflitos409 mantêm a seleção enviada e a versão antiga. Atualizar estado e revisar carrega o estado atual separadamente, mostra turmas/disciplinas atuais e a proposta preservada, e exige conferência explícita antes de aceitar a versão atual em nova gravação. Não reenviar nem publicar automaticamente. Nova corrida volta a exigir revisão. O backend permanece a autoridade na gravação/publicação.

Validar contratos web, ausência de escrita nas ações intermediárias, estado inválido/falha/retry, conflito antes/durante gravação, metadados sem remapeamento, prova aplicada, CP1 e pessoal. Conferir Chrome desktop amplo/menu recolhido,390/320px, teclado, tema escuro e caminho sem JavaScript; guardar evidências atuais e obter parecer independente. Não adaptar os quatro simulados nem criar professores nesta parte. Sem deploy até o DC1 inteiro, sem produção.

| Parte | Estado inicial |
|---|---|
| API curricular PR180 | Cinco checks aprovados |
| Direção de UI/UX e Product Design | Prévia aprovada |
| Formulário institucional e edição curricular | Em implementação |
| Revisão visual e testes deste incremento | Pendentes |
| Simulados/professores, recuperação e fechamento DC1 | Pendentes após interface |
| Homologação | Somente no fechamento completo DC1 |
| Produção | Sem alterações |

## Aceite deste incremento

Interface aprovada pelo agente independente de UI/UX e Product Design no head7e315c41e0974fe1b1cd6826f6b1e43406874cd0. O parecer local `operacao-20261102/dc1-provas-parecer-ui-ux.md` registra as capturas atuais e os limites. O último achado — mensagem positiva na falha503 — foi corrigido e recapturado; o status usa falha explícita, sem confundir seleção incompleta com indisponibilidade. Esta aprovação não fecha o DC1 inteiro nem representa certificação WCAG/API/segurança integral.

Chrome real: desktop1440px/menu recolhido,320/390px, tema escuro, campos e ações sem overflow, foco após preparação, diálogo de metadados com labels/retorno/Escape e navegação por Tab, adição por POST sem JavaScript, prova fictícia criada e redirecionada ao gabarito, conflito real com seleção preservada e revisão explícita, falha503 com retry, timeout25s sob latência35s e quatro mudanças rápidas da turma sem aplicação de resposta antiga. O formulário pessoal foi conferido com conta e turma fictícias e permanece no contrato anterior. Injeções e credenciais de QA ficaram exclusivamente na cópia local, fora do repositório e da release; os servidores não foram acessados.

Verificações: API completa local789 aprovados/28 ignorados/6516 asserções (inclui dois testes preexistentes não versionados, fora deste commit); contratos alterados MariaDB2 testes/25 asserções; web completa187 testes aprovados e regressão específica de falha9 testes/81 asserções. O CI valida o conteúdo versionado. Não há nova migration neste incremento.

| Parte | Estado atualizado |
|---|---|
| API curricular PR180 | Cinco checks aprovados |
| Formulário institucional e página de configuração | Implementados; revisão UI/UX aprovada |
| Metadados e classificação publicada/aplicada | Separados e conferidos; histórico preservado |
| Verificações locais deste incremento | Aprovadas; CI do novo PR a conferir |
| Quatro simulados e professores de teste | Próximo trabalho, após revisão do planejamento de adaptação |
| Recuperação completa e revisão global PR171 | Pendentes para fechar o DC1 |
| Homologação | Sem deploy parcial; publicar após todo o DC1 validado |
| Produção | Sem alterações; promoção não autorizada |
