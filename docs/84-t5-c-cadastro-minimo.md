# 84 — T5-C: cadastro mínimo entre espaços

Revisão antes da implementação, após merge #117. T5-B3 passou em CI com 119 provas/11.900 questões, restauração de staging/fila e cinco checks verdes. Evidência: PR #117. Sem deploy ou migrations no servidor.

## Constatações

O executor atual é exclusivo de material-1/provas. Lotes, itens, mapas e finalidade possuem contratos fechados; não basta acrescentar alunos ao job. `AlunoController` exige nascimento no cadastro escolar e o proíbe no pessoal. Matrícula é única por espaço, não identifica uma pessoa entre espaços. Nenhum mecanismo existente comprova a evidência exigida para transferência de dados identificados.

## Subdivisão e critérios

| Incremento | Entrega | Limite/critério |
|---|---|---|
| C1 — este PR | Prévia interna da origem, seleção explícita de turmas/alunos, projeção mínima por tipo de destino e indicação de campos faltantes | Sem rotas, persistência, leitura do cadastro do destino ou execução; autorização atual da origem obrigatória |
| C2 | Contrato versionado de cadastro, decisões vinculadas à seleção/categorias e evidência restrita validada; mapa por entidade; conciliação de matrícula e dependências | Sem inferir consentimento por pagamento, autoria, vínculo ou contrato_referencia; sem merge por nome/CPF/matrícula; revisão antes de migrations |
| C3 | Staging cifrado, publicação por unidade consistente, remapeamento e recuperação | Revalidar ambos os lados/evidência; conflitos bloqueiam; mapa existente preserva edições; MariaDB/Redis, concorrência e restauração; sem habilitar notas/resultados |

T5-C só estará concluído após C2/C3. Interface permanece em T5-E. T5-D continua responsável pela continuidade/resultados; T6/F1 não são simulados.

## Contrato C1

`previaOrigem` recebe ator com vínculo, espaço de origem, tipo de destino (`pessoal`/`escola`) e listas explícitas de IDs de turmas e alunos. Tipo de destino é uma hipótese para avaliar formato, não validação de um destinatário real. Até 100 entidades somadas, IDs inteiros positivos e sem repetição. Turma selecionada não inclui automaticamente seus alunos. Cada aluno selecionado exige sua turma explicitamente selecionada; reutilização de mapa fica para C2.

Somente autorizador da origem com alcance atual pode consultar. Não combinar poderes de outros vínculos nem aceitar IDs fora do espaço. Origem suspensa/encerrada, regime legado e flags desligadas bloqueiam. Nenhum dado do destino é consultado; colisão de matrícula somente poderá ser analisada em C2 com contexto próprio e autorização aplicável. A prévia não é comprovante de autorização e deve ser refeita/revalidada na futura execução.

Lista positiva de turma: nome, série, turno, ano letivo, ativo. Lista positiva de aluno: nome, matrícula, avatar de catálogo e ativo. Para destino escolar, acrescentar nascimento somente se disponível e válido; ausência gera pendência explícita. Para pessoal, nascimento/CPF/hash/gênero/responsável/foto/caminhos nunca entram na projeção. Não copiar professores, vínculos, cartões, notas, imagens ou links. IDs da origem aparecem apenas como referências de seleção/dependência, nunca como novos IDs do destino.

Como nomes e matrículas identificam alunos, a prévia permanece restrita à origem. Não expor ao destinatário nem executar com base nesta consulta. Toda seleção demanda revisão; alunos também exigem evidência/autorização específica validada em C2, inclusive entre dois espaços pessoais. Não inventar nascimento ou resolver matrícula automaticamente. O digest descreve apenas esta projeção/contexto, não uma decisão persistida nem uma credencial.

## Testes

Seleção parcial sem ampliação, listas vazias/duplicadas/acima do limite, IDs forjados, turma faltante, autorização por vínculo, revogação e espaço suspenso, minimização por destino, ausência/invalidez de nascimento, avatar inválido, campos vedados ausentes e nenhuma gravação. Rodar SQLite e MariaDB; manter executor material-1 inalterado e todos os checks existentes.
