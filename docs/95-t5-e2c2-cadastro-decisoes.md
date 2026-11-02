# 95 — T5-E2c2: evidência e decisões do cadastro

Plano revisado antes da implementação, dependente de #128. Sem merge, deploy ou migração no servidor; conferência visual pendente mantém os PRs em rascunho.

## Fluxo

1. Destino emite código próprio para cadastro. Generalizar o serviço de indicações com finalidade persistida; códigos de materiais não servem para cadastro nem vice-versa. Preservar 256 bits, hash, 15 minutos, limite de cinco códigos livres por vínculo e consumo atômico.
2. Origem informa o código após selecionar uma turma e até 20 alunos. Recalcular a prévia pelo tipo REAL do destino, sem aceitar tipo enviado como decisão. Exibir nome/tipo, todos os campos e pendências antes de confirmar. Criação revalida os dois digests e consome indicação junto de lote/outbox. Retry exato não refotografa nem renova.
3. Origem registra texto da evidência e confirma explicitamente sua aplicabilidade à seleção e destino. Texto cifrado no contrato existente, limite de 64 KiB; não aceitar URL como documento. Não ler/devolver documento por endpoint, não inserir em logs, flash ou URL. Prazo visível de até 24 horas nesta interface. Evidência não é autorização de saída.
4. Origem confere cadastro e autoriza saída separadamente, limitada pela validade da evidência e sem pendências. Compartilha referência manualmente; sem envio automático.
5. Destino somente consulta após evidência e saída válidas. Retornar cadastro mínimo e resultado da conciliação, sem documento, atores, vínculos ou IDs internos de origem/destino. Relacionar conflitos aos itens exibidos por posição; mostrar conflito de matrícula/mapa/dependência sem expor registros existentes. Recusar aprovação quando bloqueado ou digest da conciliação mudou.
6. Entrada exige confirmação explícita do conteúdo e conciliação; validade não excede evidência nem saída. Revalidar contexto, fonte, evidência e decisão da origem dentro da transação. Duas decisões não executam cópia.

## Limites e implementação

Reutilizar contratos existentes de cadastro. Extrair núcleos privados de criação/evidência/aprovação para composição atômica, preservando transações próprias SERIALIZABLE das APIs internas e recusando transações externas. Adaptador HTTP somente para lotes criados por indicação da finalidade correta, em estados anteriores à execução. Não expor revogação, retentativa, publicação, edição de matrícula ou complementação automática; E3 permanece separado.

Respostas por lista positiva; origem não recebe conflitos/dados existentes do destino. Web com contexto por aba, CSRF, escape e validação manual sem flash. Código/documento apenas em corpo POST. Datas exibidas em Brasília, persistidas em UTC. Flag existente desligada. Migration aditiva classifica indicações existentes como materiais; não altera as migrations já preparadas nos PRs anteriores.

## Verificação

SQLite e MariaDB: finalidade do código, confirmação obsoleta/tipo real, consumo único/retry/rollback, evidência cifrada sem exposição, saída separada, conflito e conciliação obsoleta, limites de prazo, contexto/versão/revogação, fonte alterada e estado posterior bloqueados; zero execução. HTTP recusa atores injetados e confirmações implícitas. Web verifica formulários, escape, contexto/CSRF, ausência de documento/código no flash e ações distintas. Executar cinco checks e restauração completa. E2c3/resultados segue após este incremento.
