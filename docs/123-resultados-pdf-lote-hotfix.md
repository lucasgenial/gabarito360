# Resultados em lote — entrega urgente isolada

Pedido: reunir os resultados individuais de uma prova no mesmo PDF, mantendo o modelo existente. Base operacional a90fbf51; não publicar L4/CP1/CI1 junto deste incremento. Sem migration, mudança de dependências, recálculo ou escrita nas tabelas acadêmicas.

GET `/api/v1/provas/{provaId}/resultados/pdf`, filtro opcional turma_id. Exige relatorio.prova e resultado.compartilhar, contexto/escopo vigentes. Aluno/aplicador não têm lote. Reutiliza montar/dadosPdf e o desenho individual ReportLab; um processo Python, uma página por resultado, até 500 por arquivo e 120s para gerar. Sem resultado: 422; acima do limite: orientar filtro por turma. Somente resultados existentes, sem inventar notas de alunos pendentes. Ordenação por turma/nome. Nenhum ZIP necessário para esta entrega: o documento único atende a impressão coletiva.

Arquivo temporário privado, sem URL pública, no-store, removido após envio/falha. Revalida versão das permissões/escopos e acesso a cada aluno depois de gerar. Botões no relatório da prova e no relatório da turma/prova. Downloads passam pela sessão web existente.

Validação: isolamento entre espaços, turma inválida/sem resultado, revogação durante geração e limpeza em falha; regressão do individual; renderização PDF fictício multipágina. Publicação: patch somente destes arquivos sobre a release atual, backup prévio e hash das tabelas acadêmicas antes/depois, caches/rotas e PHP-FPM; não usar o deploy acumulado que executa migrations. Reverter apenas os arquivos desta alteração se falhar. Preservar alterações preexistentes, uploads, banco, gabaritos e fases pendentes.
