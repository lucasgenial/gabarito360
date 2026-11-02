# 89 — T5-D2b: pacote histórico e evidência de resultados

Planejamento revisto antes da implementação, após merge #122. Sem deploy nem migração no servidor.

## Contrato revisto

O pacote é uma fotografia cifrada, capturada em transação SERIALIZABLE própria. Contém somente as notas explicitamente selecionadas (1–20), dependências explicitamente confirmadas, respostas, material observado e identificação mínima. Não é uma cópia operacional de alunos, cartões ou provas. Não depende de conciliar matrícula no destino: D3 publicará um acervo separado, com IDs próprios, sem cadastrar alunos ou vincular por nome/CPF. Nascimento obrigatório continua aplicável à cópia operacional C, não a este acervo.

O chamador confirma exatamente os IDs de provas, alunos e turmas indicados na prévia. Não há expansão para colegas. Alunos: nome/matrícula; turmas da aplicação: nome/série/turno/ano. Não carregar imagens, arquivos, CPF, nascimento, gênero, responsáveis, confiança OMR, operadores, temas livres, tokens, links ou recibos de envio. Material observado pode conter texto livre e por isso integra a evidência de dados identificados.

O pacote declara `legado_sem_versao_original`: nota armazenada e contexto observado não demonstram a regra original do cálculo. Não recalcular. Evidência e aprovações exigem reconhecimento explícito dessa limitação. A fotografia nunca é atualizada pelo serviço; mudanças de conteúdo exigem novo lote. O hash é conferido junto à cifra em cada leitura. Isso é imutabilidade no contrato da aplicação, não proteção contra um administrador que controle banco e chave.

## Evidência e duas decisões

Política própria `t5-resultados-v1`, finalidade `resultados_identificados`, itens do tipo nota e tabela própria do pacote. Evidência vinculada ao lote, seleção e conteúdo, com categorias derivadas pelo servidor, documento cifrado e guarda definida por contrato. O documento deve explicar finalidade, aplicabilidade às categorias e espaços, prazo/critério de guarda e preservação excepcional; campos estruturados de guarda e preservação são exigidos na revisão. Não há prazo universal inventado, validação jurídica automática ou reutilização de evidência de cadastro.

Revisor da origem, autorizadores e solicitante devem manter vínculos, capacidades e versões atuais. As duas decisões têm validade finita não superior à evidência. Destino só recebe a fotografia depois de evidência e decisão de saída válidas. Nova evidência não renova um lote existente: retry exato é idempotente; mudanças exigem novo lote. Revogação bloqueia o lote e preserva seu histórico.

A fonte é comparada à fotografia antes da evidência, leitura para decisão e aprovações: alteração em notas, respostas, material ou identificação mínima exige novo lote. A fotografia anterior permanece preservada. Não usar datas/hashes como prova retrospectiva de versão do gabarito. Eventos de solicitação, revisão, aprovação e revogação usam a outbox existente sem conteúdo pessoal.

## Limites e próxima fase

Não há endpoint, publicação, job de resultados ou escrita nos dados operacionais do destino. A consulta de situação revalida decisões, evidência, fonte e integridade, mas sempre retorna `autoriza_execucao=false`. A lista fechada do agendador mantém resultados fora da fila. Continuidade D1 segue indicando resultados indisponíveis.

D3 deverá publicar o pacote em acervo separado e definir sua consulta, isolamento e preservação; revalidar autorização sob locks, concluir auditoria, tratar revogação/replay e testar restauração completa com volume representativo antes de habilitar qualquer execução. Ativação e deploy dependem de aprovação própria.

## Verificação

SQLite e MariaDB: fotografia mínima cifrada, seleção/dependências exatas, nenhuma ampliação/efeito operacional, mudança da fonte sem sobrescrever fotografia, evidência exclusiva e guarda obrigatória, reconhecimento do legado pelos dois lados, expiração/revogação/versões, idempotência, adulteração da cifra/hash e atomicidade da outbox. Manter cinco checks de regressão. Nova migração apenas em bancos descartáveis nesta etapa.
