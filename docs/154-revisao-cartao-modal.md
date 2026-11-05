# Revisão — cartão inteiro no modal

Planejamento revisto em 04/11/2026 a pedido do responsável: visualizar a imagem inteira dentro da revisão, sem abrir nova aba ou página. Preservar a questão atual, as respostas e a assinatura da imagem. Não editar arquivos, notas ou dados escolares.

Consultar UI/UX e Product Design antes da implementação. Usar diálogo acessível com tokens existentes, aproveitando a área disponível no desktop e no celular. Prever carregamento, falha de imagem, fechamento por botão/Esc, foco contido e retorno ao acionador. Bloquear atalhos de resposta enquanto o visualizador estiver aberto. Ampliação, se incluída, deve ser simples e somente visual.

Validar abertura e fechamento, questão preservada, teclado sem POST involuntário, imagem assinada intacta, nova abertura com outro cartão e estados de erro. Conferir desktop e celular no Chrome e obter aceite especializado final. Executar CI e integração com publicação somente na homologação, sobre fa58f1c; backup, comparação de dados e retorno à release anterior. Sem deploy em produção ou migração.
