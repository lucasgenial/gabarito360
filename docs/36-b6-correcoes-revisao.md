# 36 — Correções da revisão de B6

Autorização: responsável solicitou corrigir os três problemas encontrados na revisão do PR #61. Escopo restrito a B6; sem merge, deploy ou alteração de produção.

## Micropassos

1. Preservar no acompanhamento da aba os envios com erro que ainda permitem retentativa. Recarregar a tela deve restaurar o recibo e o botão sem pedir outra foto.
2. Ao concluir uma nova foto de um aluno identificado, substituir também cartões anteriores desse aluno que estejam pendentes por falha definitiva do envio. Não descartar uma foto mais nova ainda na fila ou com erro.
3. Na exclusão manual, invalidar o recibo e limpar seu resultado na mesma transação. Na resposta, tratar também recibos cujo cartão já não existe, sem devolver notas antigas. Manter o recibo para idempotência, sem restaurar o cartão ao repetir o upload.
4. Acrescentar regressões de API e de JavaScript; executar testes API/web, verificador estático e CI do PR.

## Critérios

- Retomar erro após recarga permite nova tentativa sem upload.
- A foto nova válida encerra a falha antiga sem pendência artificial; a geração antiga não refaz nota nem altera o recibo substituído.
- Excluir o cartão elimina a nota e o resultado do recibo; consultar, repetir e reenviar o mesmo UUID não ressuscitam dados.
- Resultados redirecionados continuam respeitando o escopo e o aplicador continua sem acesso a notas.

Testes isolados com dados fictícios. Não repetir teste de carga nem auditoria com dados reais, pois as alterações são recuperação de estados e consistência, sem mudança de layout ou do algoritmo OMR.

## Resultados locais

- API: 91 testes, 471 asserções, sem falhas.
- Web: 22 testes, 93 asserções, sem falhas.
- JavaScript: quatro testes de regressão, também incluídos no job `telas` do CI.
- Chrome: erro retomado após recarga, nova tentativa e conclusão com um único upload; cinco larguras de 320 a 3840 px sem rolagem lateral ou erro JavaScript.
- Verificador estático: 24 scripts, zero erros.
- Exclusão agora usa a mesma ordem de bloqueios do worker (prova, cartão, recibo), preserva os arquivos até o commit e recusa a operação se o QR tiver mudado o cartão de prova durante a requisição.
