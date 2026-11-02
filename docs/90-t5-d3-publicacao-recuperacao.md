# 90 — T5-D3: publicação e recuperação de resultados

Planejamento revisto antes de implementar. Trabalho dependente do PR #123, mantido em branch/PR separado enquanto o merge aguarda aprovação. Sem deploy ou migração no servidor.

## Publicação

Acervo próprio cifrado, fora das tabelas operacionais e dos relatórios/B6. Um pacote de até 20 notas e 512 KiB é uma unidade atômica: staging privado, depois publicação em outra transação com revalidação integral. Novo UUID do acervo e novos IDs de itens, com mapas de proveniência nota → item do acervo. Nunca mapear para `notas` operacionais. Um mapa prévio da mesma nota para o destino bloqueia novo lote; não sobrescrever histórico nem inventar versionamento de revisões nesta fase.

Flag exclusiva desligada por padrão, além das flags gerais. Intenção durável e job cifrado de UUID na fila baixa. Agendador usa lista fechada de políticas e o limite compartilhado de 20. Worker revalida fonte, evidência, duas decisões, versão do solicitante e do executor, estado dos espaços e auditoria. Erros técnicos sem conteúdo pessoal. Falha/expiração/revogação antes da publicação bloqueia sem acervo parcial. Staging só é limpo ao publicar.

Publicação, mapas, estado dos itens e recibos são atômicos; confirmação final da auditoria ocorre depois e não republica nem reabre decisões. Revogação do lote é aceita até a publicação; depois dela, o acervo é independente e não pode ser removido/reaberto por essa operação. Pendência do recibo não representa transferência incompleta. Exclusão/preservação do acervo requer procedimento próprio de guarda, fora desta fase.

Consulta interna do acervo exige contexto atual com capacidade no destino, verifica dono, política, cifra e hash. Não exige acesso atual à origem ou evidência ainda vigente: autorização expirando depois da publicação não desfaz transferência concluída. Origem e terceiros não recebem acesso por autoria. Ainda sem endpoint/UI; não prometer acesso de todos os perfis antes de definir a interface T5-E.

## Recuperação

Novo ciclo de CI em contêineres descartáveis, depois do ciclo de cadastro: preparar pacote com 20 resultados e 100 respostas por resultado, evidência, duas decisões, staging e job já na fila; snapshot de banco inteiro e Redis, restaurar em novos contêineres com a mesma chave, conferir manifesto e credencial restrita, consumir job restaurado e sentinela, confirmar recibos e replay sem duplicação. Conferir integridade do acervo, origem intacta e nenhuma nota/cartão operacional no destino. Medir tempo sem declarar SLA.

## Validação e limites

SQLite/MariaDB: flags, publicação/replay, acesso ao acervo, revogação antes/depois da publicação, alteração entre staging/publicação, mapas preexistentes, falha atômica e auditoria pendente. Cinco checks, incluindo novo ciclo real de restauração. Publicação em servidor e liberação das flags continuam pendentes de aprovação. T5-E, complementação de cadastro e procedimento operacional de guarda/evidências seguem pendentes.
