# DC1 — integração curricular das provas

04/11/2026. Revisão anterior à implementação, após as áreas N:N, períodos históricos e padrão TBL2. Os cinco checks dos heads72c08fa (PR178) e88a1c18 (PR179) passaram. A direção da integração recebeu parecer prévio favorável de UI/UX e Product Design; não equivale à aprovação de uma interface ainda não implementada.

## Contrato atualizado

Uma prova institucional pode contemplar várias disciplinas locais. Os IDs devem pertencer à escola do vínculo selecionado e ser elegíveis em todas as turmas escolhidas. Professor precisa de indicação e atribuição atuais, além do acesso já autorizado a cada turma. Coordenação e direção mantêm as capacidades existentes; gerir disciplinas não concede criar/editar/publicar prova. A referência temporal é a data civil atual em São Paulo.

Separar relação curricular e versão da configuração em tabelas externas com chaves compostas. Preservar IDs e campos acadêmicos anteriores; não inferir disciplina por texto. Registrar os períodos de área/disciplina utilizados no momento da configuração, sem reescrever esse uso após desvinculação. Publicar uma prova já configurada não a reclassifica em novas áreas automaticamente. As relações de área nunca concedem leitura ou acesso.

Criar prova diretamente na escola exige turmas e disciplinas. Cópia CP1 continua rascunho sem turmas; configura-se o currículo antes de publicar. Espaço pessoal mantém o contrato atual e não escreve no catálogo oficial. O caminho antigo continua quando o módulo DC1 está desligado; nenhuma ativação ou publicação operacional ocorrerá neste incremento.

Editar somente título/observações preserva a classificação existente. Alterar turmas ou disciplinas exige seleção explícita e versão atual da configuração; o backend confere novamente sob transação e travas. Mudança curricular pública limita-se ao rascunho. A adaptação dos quatro simulados de desenvolvimento já está autorizada e será operação explícita preservando gabaritos/cartões/respostas/notas, sem criar verificação especial de legado. Não reclassificar dados automaticamente por nomes.

Revisão técnica de concorrência: no MariaDB, uma leitura comum anterior à espera por uma trava pode manter um retrato antigo em REPEATABLE READ. As quatro operações de gravação/publicação curricular devem iniciar uma transação própria em READ COMMITTED, definida apenas para a próxima transação, mantendo as travas e a revalidação. Reaplicar o isolamento a cada tentativa de deadlock, com limite de três tentativas e detecção do próprio Laravel. Recusar transação curricular MariaDB já aninhada, em vez de herdar um isolamento desconhecido. Não alterar o isolamento global ou da sessão. Validar com uma segunda conexão que confirma uma inativação após a primeira leitura e antes da revalidação; conferir rollback e ausência de mudança permanente do isolamento.

Professor acessa prova institucional apenas quando houver uma disciplina da prova compatível com sua atribuição em uma turma autorizada da aplicação. Provas particulares de outro professor continuam privadas. Coordenador/diretor mantêm leitura de toda a escola. Autoria e vínculo de criação já capturados permanecem imutáveis.

## Sequência de implementação

| Parte | Situação inicial |
|---|---|
| Contrato e direção UI/UX | Revistos; direção prévia aprovada |
| Relações, opções e validação na API | Em implementação |
| Criação/edição/publicação e escopo docente | Em implementação; regressão obrigatória |
| Formulário Turmas → Disciplinas → Dados | Próxima parte do mesmo fechamento; sem publicação isolada |
| Quatro simulados e professores de teste por disciplina | Pendentes após a integração do escopo |
| Revisão visual e recuperação completa | Pendentes para o fechamento DC1 |
| Homologação | Somente com DC1 completo e validado |
| Produção | Não autorizada |

No formulário futuro, usar seleção explícita de disciplinas com Adicionar à prova e Remover, sem gravação intermediária. Mudar turmas mantém escolhas incompatíveis em Precisam de revisão; carregamento, falha, ausência de opções e requisitos obrigatórios ficam visíveis. Erros e conflitos preservam o preenchimento. Orientação extensa fica em Saiba mais fechado. Implementar a interface após o contrato/API, sem pedir autorização a cada subdivisão interna.

Parecer prévio complementar do agente UI/UX e Product Design: provas já publicadas/aplicadas mostram classificação histórica em resumo somente leitura, sem aparentar erro por alteração na atribuição atual. Uma cópia CP1 sem configuração mostra “Configure turmas e disciplinas antes de publicar”, sem inferir disciplina pelo texto. Conflito409 preserva o rascunho e oferece “Atualizar estado e revisar”; buscar o estado não substitui a seleção, não aceita a versão nova silenciosamente e não reenvia a gravação/publicação. Exigir nova confirmação após a revisão. A revisão visual final continua pendente.

Testar SQLite e MariaDB: duas turmas e várias disciplinas, interseção parcial, disciplina estrangeira, ausência de atribuição, revogação após GET, publicação por ambas as rotas, metadata sem remap, concorrência de configuração, isolamento do professor, CP1, módulo desligado e uso conjunto que exige confirmação na desvinculação. A suíte existente de autoria/cópias continua obrigatória. Não implementar editor, questões/enunciados ou planejamento GII.

## Fechamento técnico da parte de API

Implementadas a migração007 aditiva, a configuração curricular por IDs, as opções contextualizadas, o escopo docente e a revalidação nas três entradas de publicação (prova, gabarito e mudança de status). A primeira publicação fica registrada para impedir reclassificação por reabertura de rascunho. Provas com currículo registrado são preservadas na exclusão. Nenhum dado operacional foi adaptado ou migrado nesta entrega.

Verificações locais em banco descartável: regressão direcionada SQLite42 testes/466 asserções; suíte completa local da API789 aprovados/28 ignorados/6511 asserções; currículo e concorrência MariaDB14 testes/119 asserções. A suíte completa local inclui dois testes preexistentes não versionados e fora do escopo, que não entram no commit; o CI valida exclusivamente o conteúdo versionado. Os testes de concorrência confirmam inativação após leitura inicial, rollback, isolamento sem efeito permanente, reaplicação em nova tentativa por deadlock, limite de três tentativas e recusa de transação curricular MariaDB aninhada.

| Parte | Estado após esta entrega |
|---|---|
| TBL2 — tabelas/cards/avisos | PR179; revisão visual aprovada e cinco checks verdes |
| DC1 — relações e validação na API | Implementadas e verificadas localmente; CI do novo PR a conferir |
| DC1 — autoria/cópia/visibilidade docente | Integradas na API; regressões locais aprovadas |
| DC1 — criação/edição na interface | Próximo trabalho; direção prévia aprovada, implementação e revisão visual final pendentes |
| DC1 — quatro simulados e professores de teste | Pendentes após interface/escopo validados; preservar todos os fatos acadêmicos |
| DC1 — recuperação e aceite completo | Pendentes; a revisão final da organização do PR171 também permanece necessária |
| Homologação | Sem deploy parcial; publicar somente após o DC1 completo |
| Produção | Sem alterações; promoção não autorizada |
