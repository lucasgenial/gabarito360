# P1 — parecer independente sobre a projeção de conteúdo da API

07/11/2026. Revisor: `/root/paineis_indicadores`; implementador: `/root/security_independent_review`. Commit examinado: **98dcdef872d22cca1ada9eba4c800593cdb4928f**, worktree `paineis-p1-projecao`. Somente leitura e testes descartáveis; nenhuma alteração de código pelo revisor, nenhuma consulta à homologação ou produção.

**APROVADO para o delta da API neste commit.** Aprovação restrita à projeção de index/show por conteúdo, seus sete testes e preservação do escopo. Não aprova automaticamente Web, composição visual, CI ou deploy; limites de integração abaixo exigem solução antes da entrega conjunta.

## O que foi conferido

- `turma.ver` continua requisito da rota e não concede nomes, provas ou resultados implicitamente.
- `relatorio.turma` controla agregado, com veto de secretaria/auxiliar mesmo com extras injetados.
- `aluno.ver` controla a coleção nominal; sem essa permissão a API retorna coleção vazia e conserva somente a contagem cadastral permitida.
- Métricas individuais exigem simultaneamente agregado, nominal e `resultado.ver`.
- `prova.ver` controla metadados e contador de provas; sem agregado não há média por prova.
- Secretaria/auxiliar continuam sem provas, notas, médias e contadores pedagógicos. A seleção de foto_path é interna, removida antes da resposta; nenhum novo caminho privado é enviado.
- Os filtros de escola, espaço, turma, histórico e autoria de ConsultasPedagogicas/Escopo permanecem. Não há novo SQL sem escopo nem cache de autorização acrescentado pelo patch.
- As fórmulas legadas são preservadas para consumidores autorizados. O patch não as promove a novo indicador de desempenho, não muda denominadores, não calcula participação e não troca autoria por atribuição atual.
- Permissão retirada na mesma identidade é reavaliada e vínculo suspenso continua bloqueado. Nenhuma mutation, migration ou ampliação do catálogo de permissões foi incluída.
- Respostas autorizadas completas mantêm as chaves anteriores; a omissão de campos sem direito ao conteúdo corrige exposição, não redefine seu significado. Coleções vazias sem permissão precisam de tratamento correto no consumidor.

## Verificação executada independentemente

Ambiente: `/tmp/g360-p1-projecao-api`, WSL Ubuntu-24.04; PHPUnit SQLite `:memory:` conforme phpunit.xml, serviços fake/array de teste. Conferido SHA256 do controller no runtime e no worktree: **e19b1641db6f018269eec02b4e60510c888fe28186c866ac688b0f92cf6cbebb**, iguais.

Comando: `APP_KEY=<chave fictícia de teste> php artisan test --filter=TurmaConteudoAutorizadoTest --compact`.

Resultado executado por este revisor: **7 testes, 83 asserções, PASS**, duração 5,71s. Casos: turma.ver isolada sem consulta a notas; prova.ver isolada; relatorio.turma sem nomes/provas; resultado+aluno sem consolidado; nomes+agregado sem métricas individuais; preservação do shape e retirada/suspensão; administrativos com extras contra painel/PDF.

Também foi lido `/tmp/g360-p1-projecao-regressoes.log`: 62 testes/833 asserções PASS, execução do implementador. Não atribuir esse segundo resultado como execução independente deste revisor. CI completo e matriz MariaDB do candidato final continuam a cargo do fluxo principal.

## Pontos de integração Web identificados

1. `_alunos.blade.php` do baseline determina coluna pedagógica apenas por `!administrativo`. Se `resultado.ver` for retirada, a API passa a omitir `total_provas`, mas o fallback `??0` exibiria uma falsa contagem zero. A coluna precisa exigir a mesma combinação do conteúdo, ou mostrar estado de acesso sem inventar valor.
2. Sem `aluno.ver`, a API corretamente não envia nomes, mas a coleção vazia não significa Nenhum aluno matriculado. O consumidor precisa omitir a aba/lista ou apresentar ausência de permissão, sem confundir com vazio real. Aplicar regra também ao contador de lista.
3. Sem `prova.ver`, `provas=[]` não significa turma sem avaliações. UI não deve contar a ausência autorizativa como zero funcional.

Esses pontos foram informados ao coordenador para correção no worktree Web. Eles não alteram o parecer favorável à API isolada, mas impedem declarar o incremento integrado aprovado sem revisão das correções do consumidor.

## Limites

Não foi realizada conferência autenticada no servidor, teste visual, medição de produção ou auditoria de todas as outras rotas. O patch revisa a rota mista de turmas; não alegar cobertura integral dos módulos de relatórios/notificações. Qualquer alteração posterior de autorização/fórmula exige revisão do delta correspondente.
