# 94 — T5-E2c: cadastro e resultados na interface

Revisão anterior à implementação, dependente do PR #127. Sem merge, deploy ou migração no servidor. Os PRs #125–127 continuam em rascunho pela conferência visual pendente.

## Entregas revisadas

| Parte | Entrega | Limite |
|---|---|---|
| E2c1 — PR #128 em rascunho | Catálogo de turmas/alunos e prévia de cadastro na origem | Consulta sem lote, evidência, decisão ou destinatário real |
| E2c2 — PR #129 em rascunho, docs/95 | Indicação de destino para cadastro, evidência restrita e decisões com conciliação | Revalidar tipo real, dependências, pendências e conflito de matrícula; documento nunca aparece ao destino |
| E2c3 — PR #130 em rascunho, docs/96 | Seleção e prévia de resultados com dependências explícitas | Distinguir observação atual de versão histórica comprovada; somente leitura na origem |
| E2c4 — implementado em revisão, docs/97 | Pacote de resultados, evidência, guarda contratual e reconhecimento do legado | Duas decisões sobre pacote cifrado; nenhuma execução implícita |
| E3 | Execução, revogação/retentativa e acervo | Continua separado |

## Contrato E2c1

Catálogo de turmas paginado em 20 itens, derivado do espaço de origem permitido no contexto atual. Abrir uma turma mostra apenas seus alunos, também em páginas de 20; não seleciona a turma nem seus alunos automaticamente. Troca de turma/espaço/página limpa a seleção. Confirmar explicitamente a turma e até 20 alunos daquela turma. Prévia aceita somente uma turma e seus alunos; turma sozinha é permitida.

Tipo de destino (escolar/pessoal) é simulação da projeção, não identificação nem autorização de destino. Prévia reutiliza `PreviaCadastroCopia`, com lista positiva de campos, dependência explícita, nascimento somente na projeção escolar e pendência quando ausente, sem inventar dados. Evidência e decisões continuam obrigatórias em etapas posteriores. Não reutilizar revisão de material.

API não expõe contexto interno, IDs de vínculos, versão de acesso, documentos ou dados fora da lista positiva. Catálogo consulta somente nome/matrícula para distinguir alunos da turma; não busca foto/responsável/gênero/nascimento. Leitura revalida contexto ao final. Prévia limitada a 512 KiB. POST mantém IDs fora do URL; validação web manual não grava seleção/dados pessoais em flash, erros são genéricos e textos escapados. Flags existentes permanecem desligadas.

## Verificação

Testar escopo antes de contagens, paginação e turma de outro espaço, dependência não marcada, aluno de outra turma, tipos de destino e nascimento pendente, capacidade/flags/revogação, atores injetados, nenhum lote/evidência/fila. Web: contexto por aba/CSRF, ausência de seleção automática, campos escapados, simulação explícita, falhas sem dados em flash. Cinco checks, incluindo MariaDB e restauração. Conferência visual dos novos fluxos permanece pendente; manter PR em rascunho.
