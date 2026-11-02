# 82 — T5-B2: execução de materiais revisados

Revisão antes da implementação, após merge #115. Sem deploy, migrations no servidor ou cópias reais. Este incremento implementa o executor interno; interface/rotas continuam para etapa posterior.

## Unidade, isolamento e publicação

- Escopo é a projeção `material-1` de prova e gabarito revisados em B1. Anexos, PDFs, imagens, alunos, turmas, notas e cartões não são copiados. Não há arquivos a compartilhar nem URLs temporárias neste formato.
- Staging em tabela própria, conteúdo cifrado por APP_KEY, no máximo 64 KiB por prova e até 100 questões. Consultas de provas, busca, relatórios e URLs não consultam esta tabela. Nenhuma linha de prova/gabarito é criada no destino durante preparação.
- Uma unidade por job: preparar em uma transação e publicar em outra, com revalidação entre ambas. Lote possui até 100 unidades; cada passagem confere a seleção/revisão inteira para não ampliar consentimento.
- Publicação cria prova em rascunho, novo gabarito e novas questões, atribuindo espaço/escola do destino e autoria operacional ao solicitante da execução. Zerar datas/estado de publicação/correção e desabilitar geração automática de cartão. Não copiar turma, status da fonte ou links.
- Prova, dependências, mapa de origem, estado do item e recibo `item_publicado` são atômicos. As unicidades SQL e locks do lote são a garantia de repetição. Nova chave não contorna o mapa; retomada não sobrescreve edição posterior no destino.
- Após publicação bem-sucedida, limpar somente a cifra temporária daquela unidade na mesma transação; conservar digest e referências. Staging bloqueado fica privado para análise, sem limpeza destrutiva neste pacote.

## Solicitação e execução duráveis

O autorizador do destino solicita execução após revisão, duas decisões válidas e confirmação da auditoria inicial. A intenção é persistida antes de usar Redis. A identidade/vínculo/versão do solicitante é derivada do contexto e revalidada no worker, sem perfil global.

Publicador periódico envia apenas UUID do lote em job cifrado na fila baixa, no máximo 20 intenções por minuto. Reenvio após cinco minutos sem avanço; falhas ficam com código técnico, sem SQL/conteúdo em failed_jobs. Worker tem três tentativas e timeout de 30 s. Em cada passagem há no máximo uma preparação/publicação de prova; a auditoria pendente bloqueia o avanço seguinte. Medir vazão/latência em volume antes de ativar, especialmente o impacto na fila baixa.

`COPIAS_EXECUCAO_ATIVA=false` é uma terceira flag. Sem ela não se registra execução nem se publica material. As flags anteriores também permanecem falsas na configuração entregue.

Revogação, suspensão, mudança na fonte/revisão ou digest inválido bloqueiam o restante. Unidade já publicada permanece intacta: não desfazer edições legítimas nem excluir objetos. Falha de banco reverte a unidade e deixa a intenção retomável. O lote só é exibido como concluído após confirmação dos recibos finais. Conclusão técnica não reabre autorização após revogação.

## Auditoria

Envelope v2 acrescenta `item_id` opcional e executor `worker_copia`, mantendo leitura de v1. O contexto histórico da solicitação é guardado para auditar bloqueio mesmo quando seu vínculo foi revogado; essa fotografia nunca concede acesso. A cópia efetiva sempre usa contexto atual. Eventos da unidade e de conclusão da publicação são registrados antes do commit; confirmação posterior apenas encerra a intenção.

## Validação e limites

Testar staging invisível/cifrado, novo rascunho e FKs, origem preservada, repetição e edições do destino, fonte/revisor/vínculo revogados entre preparação e publicação, corrupção da cifra, rollback de unidade, interrupção após commit, auditoria indisponível, colisão SQL e recuperação com Redis real em ambiente descartável.

Não declarar a release apta para deploy só por este PR: acompanhamento de usuário, autorização dos fluxos posteriores, ensaio de volume/vazão e preparação operacional continuam pendentes. T5-C/D/E e F1/T6 mantêm seus limites. Próximo passo deve revisar as evidências e o acompanhamento da execução antes de expor a funcionalidade.
