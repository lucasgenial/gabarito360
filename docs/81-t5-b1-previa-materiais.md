# 81 — T5-B1: prévia e revisão de materiais

Revisão antes da fase, após merge #114. Dividir T5-B em B1 (este pacote: projeção e revisão) e B2 (staging privado, fila de execução, novos IDs/mapas e publicação transacional). Sem deploy/migration no servidor, rotas ou cópias reais.

## Conteúdo e acesso

Prévia interna somente para autorizador da origem, com vínculo ativo e alcance atual. Não basta ser autorizador do destino para ler conteúdo da fonte. A interface e a disponibilização de prévia ao destinatário, depois do consentimento de saída, precisam de contrato próprio em B2/E.

Lista positiva v1: título, disciplina, série, número de questões/alternativas, nota máxima, tipo de pontuação, regra de anulação e opção de língua; gabarito com número, alternativas, peso e anulação de cada questão. Nenhum ID escolar, usuário, turma, aluno, nota, resposta, cartão, caminho, link, data de aplicação, status de publicação/correção ou histórico integra o objeto de material. IDs da prova só aparecem na referência técnica do item da prévia da origem.

Gabarito ausente/incompleto, sequência inválida, alternativas incompatíveis, pesos inválidos ou escopo de dependência divergente bloqueiam a prévia. Não completar lacunas nem corrigir silenciosamente. Provas com opção de língua preservam espanhol nas primeiras cinco questões; dados fora desse contrato bloqueiam.

Textos livres não recebem classificação automática. O autorizador deve revisar a projeção apresentada e declarar explicitamente `sem_dados_pessoais=true`. O serviço confere o digest dessa projeção e da fonte, registra ator/vínculo/versão e cria recibo transacional `revisado`. Isso é revisão do material selecionado; não autoriza notas identificadas, arquivos ou F1/T6.

## Integração à autorização

Antes de cada aprovação, exigir revisão válida e anterior às decisões, vinculada à seleção imutável. Revisão não altera a fonte nem reescreve a seleção. Repetir a mesma revisão pelo mesmo contexto é idempotente; outro conteúdo ou revisor exige novo lote. Revogar/suspender o vínculo do revisor ou mudar sua versão invalida a revisão.

Lotes com decisões anteriores a esta etapa e sem revisão não podem ser promovidos retrospectivamente. Criar novo lote; não fabricar consentimento de conteúdo. Revalidação bloqueia lote com decisão cuja revisão deixou de ser válida. A confirmação de auditoria passa a exigir também o recibo da revisão.

A revisão guarda apenas hashes e referências, nunca o texto do material. A futura cópia deve usar exatamente esta projeção, gravar novo rascunho, resetar estado/datas de publicação, criar caminhos independentes e revalidar no commit. B1 não fornece armazenamento temporário nem escreve objetos no destino.

## Validação e continuidade

Testar lista positiva, alternativas/idioma/pesos, dados incompletos, ausência de revisão, digest forjado/desatualizado, origem/destino separados, revisão idempotente, versão revogada e recibo durável. Rodar MariaDB e repetir o ensaio de recuperação com quatro recibos (solicitação, revisão e duas aprovações). Não declarar T5-B concluído: B2 ainda precisa provar isolamento do staging, concorrência/publicação e recuperação da execução.
