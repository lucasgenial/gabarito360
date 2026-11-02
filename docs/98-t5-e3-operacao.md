# 98 — T5-E3: operação explícita e acervo

Planejamento revisado antes da implementação. Base #131. O usuário autorizou os merges pendentes após concluir E3 e resolver as validações, sem deploy nem migração no servidor.

| Etapa | Entrega | Verificação |
|---|---|---|
| E3a | Detalhe operacional mínimo do lote, ligado ao acompanhamento | Contexto/lado atual; destino só após sua decisão; nenhuma evidência ou identidade da contraparte |
| E3b | Solicitação explícita de processamento no destino e revogação do lado atual | Reutilizar transações dos serviços; flags, fonte, decisões, conciliação e recibos revalidados pelos contratos existentes; sem executar workers na requisição |
| E3c | Retentativa idempotente e orientação de recuperação | Repetir pedido não duplica nem reinicia bloqueados; falhas transitórias seguem fila existente; bloqueados exigem nova seleção e decisões |
| E3d | Consulta do acervo publicado de resultados | Apenas destino elegível; projeção positiva sem IDs técnicos; pacote independente da fonte após publicação; legado visível |
| E3e | Testes completos e conferência visual integrada E1–E3 | Cinco checks, Chrome local com dados fictícios; corrigir problemas antes dos merges encadeados |

Detalhe somente de lotes com indicação da finalidade correta, política reconhecida e auditoria v1. GET não revalida com efeitos de escrita. Contagens e sinal de recibos não são autorização. Os botões são orientativos; cada POST usa ator do contexto, confirmação explícita e contrato transacional existente. Não aceitar ator/vínculo/destino/finalidade no corpo. Preservar contexto por aba, CSRF, erros genéricos e ausência de flash sensível.

Retentativa de transporte usa a mesma referência e o mesmo serviço de solicitação; não altera autoria, prazo, seleção, estado bloqueado ou agendamento. O agendador já tenta novamente falhas transitórias. Não oferecer reset de bloqueio. Revogação interrompe o restante e não remove objetos publicados; resultados publicados são indivisíveis e não admitem revogação retroativa. Nenhuma exclusão será implementada.

Acervo é acessado pela referência do lote do destino, resolvendo o ID internamente. Mostrar conteúdo já publicado e data, sem depender da validade atual da origem ou das autorizações antigas. Revalidar acesso atual do destino e integridade cifrada. Materiais/cadastros publicados continuam nas áreas existentes. Flags continuam desligadas; nenhuma migration nova.

Critérios: solicitações repetidas, flags, auditoria pendente, revogação antes/depois de publicação, autoria/contextos distintos, mudança da fonte, bloqueio definitivo, acervo após mudança da origem, corrupção do acervo, minimização, CSRF e escape. Não declarar validação visual concluída por testes automatizados. Merge autorizado não equivale a ativação no servidor.
