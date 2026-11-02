# 86 — T5-C3: execução e recuperação do cadastro

Planejamento revisado antes de implementar, após merge #119. Somente código e ensaios descartáveis; sem deploy/migrations no servidor. Não há interface de transferência neste incremento.

## Unidade e autorização

- Uma unidade é uma turma explicitamente selecionada e somente seus alunos selecionados. Até 100 entidades por lote (C1), no máximo 128 KiB de JSON por unidade. Turma sozinha também é válida. Dependências são publicadas juntas, sem aluno sem turma.
- Preparação cifrada em `copias_staging`, identificada pelo item turma; nenhuma linha visível de aluno/turma nessa fase. Outra transação revalida vínculo do executor, duas decisões, evidência/cifra/categorias/validade, fonte e conciliação antes de publicar.
- Copiar campos positivos de C1; origem intacta, novos IDs, escola/espaço e autoria operacional do destino. Nunca copiar CPF/hash, responsável, gênero, fotos, cartões, notas ou links. Nascimento somente para destino escolar e se já autorizado/disponível. Complementação continua bloqueada, sem preenchimento inventado.
- Mapas anteriores válidos são reutilizados sem escrita no alvo. Cada item recebe recibo `item_publicado` ou `item_reutilizado`; novos objetos/mapas, estados e recibos fazem parte do mesmo commit. Rollback da unidade é completo. Não apagar unidades já publicadas ao revogar o restante.
- Conciliação aceita não deve ser invalidada pelos mapas que o próprio lote acabou de publicar. Normalizar somente mapas do próprio lote com item publicado e alvo/dependência válidos para a decisão original `novo`; mapas de outro lote, conflitos ou dependências alteradas continuam exigindo nova seleção/aceite. Essa normalização não concede autorização nem dispensa a revalidação.

## Intenção, fila e encerramento

Reutilizar intenção durável, outbox e staging do T5, com job próprio `ProcessarCopiaCadastro`, cifrado e contendo apenas UUID. O publicador usa catálogo fechado de políticas e limite compartilhado de 20 intenções; cadastro desativado não ocupa vagas de material. Material-1 nunca executa cadastro. `COPIAS_CADASTRO_EXECUCAO_ATIVA=false` adicional, subordinada às flags gerais, bloqueia solicitação e processamento.

Um job prepara/publica no máximo uma unidade; antes da próxima, aguarda confirmação dos recibos. Prazo de 30 s, três tentativas e mensagens de erro técnicas sem conteúdo/SQL. Erro inesperado preserva intenção e retorna erro sanitizado; bloqueio de autorização usa contexto histórico somente para auditoria. Lote só conclui após recibos finais. Depois disso, não reabrir nem desfazer objetos via revogação/revalidação do lote concluído.

## Validação

SQLite/MariaDB: isolamento do staging, minimização gravada, novos IDs/FKs, reutilização sem sobrescrita, lotes com várias turmas, conflito após preparação, revogação/cifra/validade, rollback após inserções, replay após commit, fila/flags/políticas, lock concorrente e unicidade por espaço após T2.

CI com MariaDB/Redis reais: terceiro ciclo de backup em contêineres novos, contendo evidência cifrada, unidade preparada, intenção e job cadastral pendentes. Conferir manifesto antes de consumir, terminar pelo job restaurado, repetir sem duplicar, confirmar recibos e comparar origem/conteúdo do destino. Usar unidade máxima de 100 entidades (uma turma + 99 alunos fictícios), medir job e espera da auditoria com limites de regressão de 20/30 s; não extrapolar capacidade do servidor. Regressão dos ensaios de materiais permanece obrigatória.

Depois: revisar T5-D (continuidade/resultados); interface/processo de evidências, acompanhamento T5-E, complementação e ensaio operacional de ativação continuam pendentes. A aprovação deste PR não autoriza deploy nem uso real.
