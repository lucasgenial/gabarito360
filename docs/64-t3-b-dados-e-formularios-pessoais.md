# 64 — T3-B: dados e formulários pessoais

01/11/2026. Origem: T3/T1 e docs/63, aprovados pelo responsável após PR #96, incorporado em `a4c198c`. Autorização para desenvolver T3-B, sem deploy.

## Entrega

- Expansão do enum de `usuario_vinculos.perfil` para `professor_autonomo`, sem mudar `usuarios.perfil` global.
- `turmas` e `provas` aceitam escola nula exclusivamente quando pertencem a espaço pessoal. `espaco_tipo` nas três raízes (turmas/provas/alunos) possui FK composta para `(espacos.id, espacos.tipo)`. Triggers de insert/update impõem escola preenchida para tipo escolar e ausente para pessoal; alunos pessoais só podem conter cadastro mínimo.
- Cadastro mínimo: nome, identificação simples (`matricula`), turma, avatar e estado ativo. API e formulário rejeitam CPF, nascimento, gênero, responsável e fotos. Respostas Eloquent pessoais omitem esses campos. O espaço e seu tipo são derivados do ator e da turma, nunca de um ID de propriedade recebido no formulário.
- Consultas e escritas de turma/prova/aluno pessoais isoladas pelo vínculo e proprietário. Não há escola fictícia nem herança de dados da rede do pagador. Vínculo pessoal de outra pessoa, cliente/conta/espaço suspenso e destinos externos são recusados.
- Relatórios de prova/turma e boletim utilizam o nome do espaço no cabeçalho. Mantêm as referências já existentes: aprovação 6/10 e referência de média 7/10, sem consultar outra instituição. Não há tela nova de parâmetros nesta etapa.

## Mudança de turma e histórico

`cartoes.turma_aplicacao_id` é um snapshot, com FK composta ao espaço da turma. Novos cartões pessoais o registram ao vincular aluno. Na mudança de turma pessoal, aluno/destino/cartões/notas são bloqueados e conferidos na mesma transação: cartões antigos recebem a turma da nota, quando existente, ou a turma atual; inconsistências recusam toda a operação. Notas não são movidas nem copiadas. A troca exige turma ativa no mesmo espaço.

Consultas de notas/cartões, cálculo, auditoria e relatórios usam o snapshot quando preenchido. O boletim pessoal conserva provas anteriores, e o consolidado da turma inclui alunos com notas históricas nela. A volta à turma original não reescreve o histórico. O comportamento institucional de movimentação não é ampliado por esta entrega.

## Migração e habilitação

Migration `2026_11_01_000006_preparar_dados_pessoais`: prepara esquema e catálogo, sem criar contas/clientes/vínculos reais, sem backfill inferido dos cartões e sem ativar o acesso pessoal. Não executar rollback destrutivo; escola nula não é compatível com o código antigo. DDL deve ser ensaiado com escritores pausados e backup restaurável na publicação, conforme docs/60.

`espacos.pessoal` permanece **false** no arquivo de configuração, sem variável de ambiente que o habilite inadvertidamente. Os testes alteram a configuração apenas no processo descartável. Ativação e integração final são T3-C; o regime de vínculos também conserva seu gate anterior. Não há provisionamento comercial (T4), importação escolar para pessoal, portal de pais ou cópia entre espaços (T5).

## Validação

Testes de API: cadastro mínimo, payload excessivo, tentativa entre dois espaços pessoais, propriedade derivada, turmas/provas sem escola, agregados, restrições diretas no banco, mudança de turma e retorno, recálculo e boletim histórico. Testes web: campos mínimos, ausência de consultas institucionais, payload e rejeição antes de envio. Nova suíte participa também do CI MariaDB. Regressões institucionais permanecem exigidas.

T3-B em revisão. T3-C ainda deve validar seleção/menu/dashboard, percurso completo em navegador, PDFs, filas e habilitação; ensaio da release em ambiente equivalente continua necessário antes de publicar. Nenhum deploy, migração ou sincronização foi executado no servidor.

Atualização T3-C: T3-B incorporado no PR #97. Integração e habilitação preparadas no código em docs/65, ainda em revisão e sem publicação no servidor; o estado desligado acima descreve a entrega T3-B original.
