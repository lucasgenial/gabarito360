# DC1 — opções curriculares para novas provas: preparação interna

04/11/2026. Subdivisão técnica do contrato142, preparada enquanto a conferência visual do PR171 aguarda recuperação do Chrome. Sem nova tela, rota HTTP, escrita de prova, migration, backfill ou publicação. Depende do código DC1 em171; não integra nem fecha esse PR.

`OpcoesDisciplinaProva::listar` calcula a interseção de disciplinas locais ativas dos componentes ativos de todas as turmas selecionadas. Verifica o vínculo atual, escola/espaço e a capacidade real de criar ou editar prova já existente. Professor exige indicação ativa, atribuição em curso hoje e autorização real a cada turma. Fim de intervalo é exclusivo. Uma atribuição programada não serve como acesso vigente. Coordenação/Direção continuam limitadas às capacidades atuais: a direção não recebe criação de prova por consultar o catálogo.

Retorna somente ID/nome/sigla, com limite explícito de500 turmas e recusa de IDs duplicados/inválidos ou fora do alcance. Não transforma texto legado em identidade de disciplina. Não cria acesso, indicação ou atribuição. Seleção vazia retorna lista vazia, sem expor catálogo irrestrito.

O resultado é sugestão interna, não decisão de autorização durável. A futura gravação/publicação terá de revalidar e travar contexto, disciplina e atribuições pertinentes dentro da transação de prova. Esta preparação não modifica `ProvaController`, publicação, leitura de provas antigas nem geração de cartões.

Testes: cenário institucional compartilhado entre as suítes sem duplicar execução dos testes anteriores. Cinco testes novos cobrem interseção em duas turmas, futuro/fim exclusivo, revogação posterior, capacidade de direção e isolamento/duplicidade. A primeira revisão somou13 testes/108 asserções emSQLite eMariaDB. A base atual também inclui a regressão de erro de campo doPR171; a suíte será conferida novamente noCI. O CI inclui ambas as suítes no jobMariaDB. A integração das opções no formulário e no commit da prova continua pendente no contrato142.

| Trabalho | Situação |
|---|---|
| Organização DC1 — PR171 | Código e CI aprovados; revisão visual final pendente |
| Backup/restauração171 | Banco completo restaurado;116 tabelas, índices, FKs e gatilhos idênticos no clone isolado |
| Opções curriculares internas | Implementadas e testadas aqui; sem exposição HTTP |
| Relação disciplina–prova e escrita transacional | Pendente, antes de fecharDC1 |
| Formulário e revisão UI/UX | Pendente; não substituídos por testes de código |
| CP2 | Contrato142 preparado; depende deDC1 completo |
| Homologação acessível/produção | Sem alteração por esta subdivisão |
