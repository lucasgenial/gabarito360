# 88 — T5-D2: contrato de resultados identificados

Planejamento revisto antes da implementação, após merge #121. Sem deploy.

## Achado e divisão

`CalculadoraNota::calcular` atualiza a nota existente por cartão usando o gabarito e a meta atuais. `notas` não referencia uma versão histórica imutável. Uma prova copiada pelo T5-B é um rascunho editável. Portanto, nem a cópia de material nem a evidência de cadastro demonstram qual regra produziu a nota antiga.

| Parte | Entrega |
|---|---|
| D2 — este PR | Prévia interna somente na origem, seleção explícita de notas, conferência das dependências, projeção mínima do resultado armazenado e bloqueios de transferência |
| D2b | Persistência de um pacote histórico imutável, política de legado e evidência exclusiva para resultados, vinculada à seleção e ao conteúdo; duas decisões atuais |
| D3 | Publicação separada dos resultados operacionais, idempotência, revogação, auditoria, recuperação completa e volume representativo |

## Contrato da prévia

`PreviaResultadosCopia::previaOrigem` exige flags e contexto explícito com capacidade de origem. Recebe 1–20 IDs positivos e distintos de notas; não expande a seleção para colegas, provas inteiras ou outras notas. O tipo de destino é hipotético, sem consulta ao destino. Não há endpoint, lote, job, evidência ou autorização nova neste incremento.

Confere nota/cartão/prova/aluno/turma e vínculo da turma de aplicação à prova, todos no mesmo espaço. A turma registrada na nota deve corresponder à aplicação do cartão (ou turma atual no legado sem aplicação registrada). Divergências não são corrigidas silenciosamente. Confere também o espaço dos filhos do cartão e do gabarito. Cartão pendente/ambíguo, respostas incompletas/ambíguas ou material inválido impedem a prévia.

Retorna IDs de origem das dependências para seleção futura explícita, a nota armazenada, acertos, total, situação e língua. Não retorna nomes, matrícula, CPF, nascimento, responsável, imagem/URL, operadores OMR, confiança ou temas livres. O digest é da prévia observada, não prova de versão histórica nem documento autorizador. Uma consulta posterior pode observar outra versão; D2b deve capturar atomicamente e revalidar antes de qualquer decisão.

Sempre indica `autoriza_execucao=false`, `evidencia_resultados_obrigatoria=true` e bloqueios para contrato de evidência, preservação histórica e execução pendentes. Mesmo uma evidência de cadastro válida não remove esses bloqueios. A continuidade D1 continua indisponível para resultados.

## Decisões para a fase seguinte

- Usar um acervo histórico separado, sem inserir notas/cartões nos fluxos de correção, relatórios, B6 ou recálculo do destino. Preservar proveniência e IDs novos; não reutilizar rascunhos editáveis como versão histórica.
- Capturar o resultado armazenado e o contexto observado com indicação explícita de legado sem versão original. Nunca afirmar retrospectivamente que o gabarito atual originou a nota. Revisar a aceitação dessa limitação e bloquear qualquer representação enganosa.
- Evidência própria deve cobrir resultados, respostas e cadastros estritamente necessários, finalidade, espaços, seleção, conteúdo, responsável vigente, prazo e preservação excepcional. Documento cifrado; hash e auditoria sem conteúdo pessoal. Cadastro já aprovado não equivale a aprovação de resultados.
- Imagens de cartões e arquivos ficam excluídos em ambos os destinos neste contrato inicial. Respostas podem integrar o futuro pacote apenas após evidência específica. Nada é anonimizado por trocar IDs.
- Dependências sugeridas não são seleção aprovada. D2b deve exigir confirmação explícita e resolver matrícula/mapas sem correspondência por nome/CPF, sem inventar nascimento nem substituir a turma histórica pela atual.

## Validação

Testar projeção mínima, seleção determinística, isolamento entre espaços e vínculos, flags, dependências divergentes, filhos em outro espaço, ambiguidades e ausência de efeitos. Executar em SQLite e MariaDB; manter os cinco checks de regressão, inclusive restauração já existente. O ensaio específico de resultados pertence a D3.
