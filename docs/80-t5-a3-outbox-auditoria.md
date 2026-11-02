# 80 — T5-A3: auditoria transacional de cópias

Revisão antes da implementação, após merge #113. Sem deploy, migration no servidor, cópia real ou execução de lote.

## Contrato e compatibilidade

Usar a tabela AU1 existente e a mesma credencial restrita (INSERT/SELECT). Tornar método, rota e status HTTP anuláveis; eventos de domínio deixam os três nulos. `referencias` recebe envelope fechado `tipo=copia`, `versao=1`, ação de catálogo, lote, decisão opcional, origem/destino, versão do acesso e executor lógico. Ator/vínculo continuam em campos próprios. Nunca inventar uma requisição para representar commit de negócio.

Os campos e a serialização canônica AU1 permanecem iguais. Isso preserva hashes/arquivos HTTP existentes e inclui automaticamente os novos eventos nos inventários, exportações cifradas, retenção de 180 dias e preservação excepcional. Versão do envelope de domínio não é versão do arquivo mensal. UUID repetido com conteúdo diferente passa a falhar, sem alterar o evento anterior.

## Entrega durável

- Criação do lote, aprovação, revogação e bloqueio gravam recibo na `copias_outbox` dentro da mesma transação. Falha no recibo aborta a mudança. Repetir uma operação idempotente não gera outro recibo.
- A fila recebe apenas UUID do recibo, cifrado. Publicador periódico consulta pendências em lotes limitados, grava tentativa de enfileiramento e agenda nova tentativa; indisponibilidade do Redis não apaga o recibo.
- Consumidor trava o recibo, valida seu digest, entrega usando a credencial AU1 e só depois confirma localmente. Queda após INSERT na auditoria e antes de confirmar a outbox repete o mesmo UUID e compara todo o conteúdo canônico. Falha fica pendente; não há fallback à credencial geral.
- Uma confirmação de auditoria não autoriza cópia: T5-B deve revalidar fonte, contexto e decisões no commit da publicação, junto ao próprio evento transacional. A fila deste pacote nunca executa cópia.
- Lotes anteriores ao contrato recebem versão de auditoria zero; ausência de recibos não é sucesso. Exigem nova prévia/lote, sem fabricar eventos retrospectivos.
- `COPIAS_AUDITORIA_ENTREGA=false` é flag operacional independente de `COPIAS_ATIVAS`. Desabilitar novas cópias não deve interromper entrega já ativada. A captura transacional não depende de `AUDITORIA_ATIVA` (esta controla HTTP).
- Scheduler publica no máximo 100 pendências por minuto; reenvio após cinco minutos sem confirmação. Worker usa fila `baixa`, três tentativas e timeout de 20 s. Recibos confirmados seguem a guarda de 180 dias e preservação excepcional documentada, como os eventos AU1; este pacote não implementa expurgo da outbox. Limpeza deve respeitar pendências e preservação, em pacote próprio.

## Limites e validação

Estado de negócio permanece `aguardando_auditoria` até o executor futuro; consulta técnica de confirmação exige versão atual, recibos obrigatórios presentes e nenhuma pendência. Não declarar lote concluído ou publicado por entrega de recibo.

Testar rollback de negócio/outbox, replay, colisão UUID, falha da credencial, flags, compatibilidade canônica HTTP, exportação mista, retomada sem Redis e após gravação remota. Estender ensaio descartável de restauração completa para incluir outbox pendente e domínio, sem modificar dados operacionais. Monitoramento mostra contagens/erro técnico, nunca payload escolar ou exceção livre.

Próximo pacote: T5-B, revisão da prévia e classificação de materiais antes de implementar staging e publicação. Autorização identificada/F1, solicitação do professor e T6 continuam pendentes conforme docs/77–79.
