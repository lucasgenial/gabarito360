# 85 — T5-C2: autorizações e conciliação

Revisão anterior à implementação, após merge #118. C1 identificou o formato mínimo; C2 prepara o contrato interno persistido. Sem endpoints, deploy, execução de cadastro ou migrations no servidor.

## Contrato revisado

- Política `t5-cadastro-v1`, finalidade `cadastro_minimo`, itens explícitos turma/aluno, até 100 somados. Lote guarda hashes e referências; não nomes, matrículas ou nascimento em claro. Prévia da origem é recalculada sob transação própria SERIALIZABLE no MariaDB. A seleção fica congelada; alteração exige novo lote.
- Evidência por lote: conteúdo documental até 64 KiB cifrado por APP_KEY, SHA-256 calculado pelo servidor, responsável/vínculo/versão, validade, categorias derivadas e digest da seleção/conteúdo. O autorizador atual da origem confirma explicitamente que revisou o conteúdo e a aplicabilidade à seleção, destino e finalidade. É uma declaração humana registrada, não validação jurídica automática ou integração F1. URL, referência comercial, booleano isolado ou pagamento não bastam. Nenhum documento sai para auditoria/fila/destinatário. Gestão/visualização do documento e seu processo operacional precisam de interface/revisão antes da ativação.
- Exigir evidência também para lote somente de turmas: campos livres podem identificar pessoas. Uma evidência não se estende a outro lote. Repetição exata não renova prazo; mudança/revogação exige novo lote.
- Aprovação da origem após evidência e prévia válida. Somente então o autorizador do destino pode consultar conciliação e aprovar. Duas decisões com contextos separados, prazos não superiores ao da evidência e outbox transacional. Revisão em `revisado` representa a confirmação humana documentada de cadastro, não classificação de material anônimo. Metadados próprios ficam em `copias_cadastros`/`copias_evidencias`.
- Matrícula colidente sem mapa retorna código de conflito, sem identidade/ID do aluno existente. Nome/CPF não são chaves de conciliação. Turma homônima não será mesclada. Mapa comprovado pode ser reutilizado somente se alvo/dependência ainda pertencem ao destino; preservar alterações do alvo, sem sincronizar. Mapa ausente significa criar novo objeto em C3. Mapa inválido bloqueia, sem reparação automática.
- Falta de nascimento escolar bloqueia a aprovação/entrada; não inventar nem preencher campo vedado no pessoal. Complementação autorizada ainda precisa de contrato próprio antes de liberar esse caso. Remover itens/alterar matrícula requer nova seleção/revisão; não excluir dados existentes para resolver conflito.
- O aceite do destino fica ligado ao digest da conciliação atual. Revalidar fonte, evidência, atores, versões, validade, mapas/conflitos e recibos antes de qualquer execução futura. Revogação bloqueia lote sem apagar conteúdo. Retomada não restaura autorização vencida.

## Esquema e isolamento

Expandir os enums de finalidade/entidade mantendo unicidades e FK composta de mapa para item. Duas tabelas aditivas: cadastro do lote (digest e conciliação aceita) e evidência restrita (FK do lote e FK composta ator/vínculo). Manter todas as linhas e down irreversível. Testar expansão no MariaDB, incluindo restauração da base com o novo esquema.

Serviço próprio `LotesCadastroCopia`. O executor material-1 continua recusando esta política, mesmo se sua flag estiver ligada. Nenhum job ou intenção de execução é criado em C2. `OutboxCopia::confirmada` é confirmação técnica, nunca autorização por si só.

## Critérios de saída

Testar dupla autorização, origem antes do destino, evidência cifrada e vinculada, alteração de fonte/versão, expiração/revogação, idempotência, auditoria atômica, conflito sem vazamento, mapa reutilizado sem sobrescrita, dependência incompatível, dados faltantes, constraints/lock no MariaDB e regressão material-1. C3 implementará publicação atômica e recuperação de cadastro; T5-D/E e liberação operacional continuam pendentes.
