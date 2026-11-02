# 93 — T5-E2b: destinatário e duas decisões de materiais

Revisão antes da implementação, dependente do PR #126; #125/#126 permanecem em rascunho por conferência visual pendente. Sem deploy ou migração no servidor.

## Fluxo e fronteiras

1. No contexto do destino, responsável com capacidade de entrada escolhe seu espaço e gera código aleatório de 256 bits, válido por 15 minutos. No banco fica somente SHA-256. Máximo de cinco códigos ainda livres/vigentes por vínculo e throttle HTTP. Código é indicação de destino, não decisão, sessão nem acesso aos dados.
2. O responsável compartilha manualmente o código com a origem. Não enviar e-mail/mensagem automaticamente. Código apenas em corpo POST; não inserir em URL, logs, auditoria, old input ou flash de sessão. A tela informa que consultar o código revela somente nome/tipo do espaço indicado, não identidade do responsável.
3. A origem seleciona os materiais e consulta o código no seu próprio contexto. Servidor revalida emissor/vínculo/versão/espaço e retorna nome/tipo do destino e digest da indicação. Não há busca/enumerador de destinatários.
4. A origem confirma destino e seleção. Criar lote e consumir código na mesma transação SERIALIZABLE, junto ao recibo de solicitação. Validar digest da seleção apresentada e digest do destino. Um código aceita apenas um lote; retry exato retorna o mesmo lote, sem refotografar nem renovar. Código consumido não permite outro pedido; retry do pedido original pode sobreviver à expiração, mantendo os contextos válidos.
5. A origem revisa explicitamente que o material não contém dados pessoais e registra essa revisão vinculada ao digest. Depois aprova a saída, com prazo exibido e limitado a 24 horas nesta interface. São ações separadas; ver prévia não equivale a revisar/aprovar.
6. Após a saída aprovada, a origem compartilha manualmente a referência do lote. O destino abre a referência no seu próprio contexto. Só recebe material e nome da origem depois de revisão/decisão de saída vigentes e fonte íntegra. Aprova o conteúdo exibido com prazo não superior ao da origem. Mesma identidade pode usar dois vínculos distintos; nenhum ator/vínculo pode ser fornecido pelo cliente para agir pelo outro lado.

Não há endpoint de execução, revogação/retentativa ou envio de arquivos neste incremento. Nenhuma chamada a `solicitarExecucao`. Duas decisões deixam lote aguardando auditoria, não publicam dados. E3 permanece separado. Cadastro/resultados/evidências pertencem a E2c.

## Implementação e integridade

Extrair apenas o núcleo transacional de criação/aprovação de `LotesCopia`, mantendo as APIs internas com transação própria. Adaptador de interface usa o mesmo núcleo dentro da transação que consome a indicação; não permitir transações externas arbitrárias. Manter regressão dos workers e da recuperação.

Leitura de revisão no destino não usa identidade/vínculo da origem como credencial do solicitante. Revalida a decisão persistida da origem separadamente. Rejeitar fonte/revisão/digest/prazo alterados, vínculo suspenso/encerrado, outro espaço e lote já em execução/publicado. A API não expõe documentos, nomes de autorizadores, tokens, IDs dos objetos da origem ao destino ou payloads da auditoria.

Campos livres são escapados; confirmação sempre usa conteúdo renderizado e digest revalidado. Web preserva CSRF/contexto por aba. Validação manual evita flash de código/digest/seleção em erros; mensagens genéricas. Referência UUID não concede acesso e pode constar do caminho protegido. Código temporário nunca consta do caminho. Datas são apresentadas em São Paulo; API persiste UTC.

## Verificação

SQLite/MariaDB: indicação sem concessão, hash sem código, expiração e versão do emissor, confirmação desatualizada, consumo único e retry, rollback de código/lote/outbox, origem/destino independentes, revisão obrigatória, aprovação contra digest/prazo, nenhuma leitura antes da saída válida e nenhum job. HTTP: atores/destinatários injetados proibidos, rotas protegidas/throttle. Web: fluxo, escape, CSRF/contexto, erros sem código no flash e sem mensagens internas. Cinco checks e regressão de restauração. Conferência visual continua requisito para retirar o rascunho.
## Entrega preparada

Implementados API e formulários de indicação, confirmação, revisão humana e decisões separadas. A migration aditiva `2026_11_02_000005_criar_recebimentos_material.php` armazena hashes, validade, contexto emissor e vínculo único com lote; não foi aplicada no servidor. O digest da confirmação inicial fica vinculado ao consumo para recusar retry com conteúdo diferente, sem refotografar a fonte.

Cobertura nova: rollback do recibo, consumo/idempotência, código vencido, limite de emissão, alteração de versão, confirmação obsoleta, revisão/saída obrigatórias antes de leitura pelo destino, prazos/digests, dois vínculos da mesma identidade, revogação, atores injetados e confirmação HTTP. Web cobre contexto/CSRF, escape, ações separadas, prazo visível e ausência de código no flash. O check MariaDB inclui a classe nova; a restauração completa permanece na CI.

A conferência visual no Chrome continua pendente do bloqueio de computer-use registrado em docs/92; não declarar validação visual concluída. Manter PR dependente de #126 em rascunho. Sem merge, deploy, migração no servidor ou alteração de flags neste incremento. Próxima parte de desenvolvimento: revisar E2c (cadastro/resultados e suas evidências), mantendo E3 separado.
