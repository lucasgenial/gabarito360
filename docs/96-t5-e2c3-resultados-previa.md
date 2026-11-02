# 96 — T5-E2c3: seleção e prévia de resultados

Revisão antes da implementação, dependente de #129. Sem merge, deploy ou migração no servidor. Manter rascunho até conferência visual.

Catálogo paginado de 20 resultados do espaço de origem permitido pelo contexto atual. Antes das contagens e dos rótulos, exigir que nota, cartão, prova, aluno e turma pertençam ao mesmo espaço, que os vínculos do cartão sejam consistentes e que a aplicação prova/turma exista. Conferir também a escola de prova e turma. Exibir somente rótulos necessários à escolha (aluno, matrícula, prova, turma e nota registrada), sem imagem, resposta bruta, documento ou identificação de outro contexto.

Seleção explícita de 1–20 notas, sem marcar todas ou acumular páginas. POST de consulta revalida o contrato `PreviaResultadosCopia`: cartões finalizados, respostas completas/não ambíguas, gabarito íntegro e dependências consistentes. Lista positiva do adaptador remove contexto interno, ID de cartão e hashes técnicos da resposta pública. Revalidar elegibilidade ao final e limitar prévia a 512 KiB.

A prévia mostra a nota armazenada, acertos, quantidade de questões, situação e língua; identifica prova, aluno e turma associados e apresenta suas dependências únicas. Isso não confirma automaticamente dependências para transferência. Não recalcular notas nem atribuir o resultado ao gabarito atual: a versão histórica original não está comprovada. Tipo de destino é apenas simulação, não destinatário real.

Nenhum lote, pacote histórico, evidência, decisão, fila ou cópia é criado. E2c4 deverá conferir novamente destino real/dependências, preservar pacote cifrado e colher evidência, guarda contratual, reconhecimento do legado e decisões separadas. E3 continua separado.

Web preserva CSRF/contexto por aba, escapa textos, evita flash de seleção/dados e apresenta erros genéricos. Flags existentes desligadas. Sem nova migration.

Testes: isolamento antes de contagens e rótulos, paginação, seleção estrita, dependências divergentes, finalização/ambiguidade, capacidade/flags/revogação, versão histórica falsa e nota não recalculada após mudança de gabarito, zero efeitos persistentes. HTTP recusa atores/destino real/dependências forjadas. Web verifica contexto/CSRF, escape, seleção não automática, avisos e falhas sem dados internos. Executar cinco checks, incluindo MariaDB e restauração completa.
