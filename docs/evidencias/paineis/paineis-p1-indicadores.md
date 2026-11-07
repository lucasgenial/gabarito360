# Painéis P1 — parecer de dados e indicadores

07/11/2026. Revisão independente por `/root/paineis_indicadores`, somente leitura de código e documentação. Planejamento anexado P0–P8 lido integralmente; AGENTS aplicável lido. Não acessou produção, não executou SQL nem testes, não modificou aplicação ou dados. Este parecer não constitui revisão visual nem conferência autenticada.

## Versão e conclusão

Checkout examinado: `worktrees/tu-img-editar-turma`, HEAD `adb96983bf3e9113b0744919f03445acabf1f451`. `git diff --quiet c16ceca HEAD` retornou 0: árvore igual ao baseline de main informado pelo anexo. TU1-PDF em outro branch não foi refeito nem aprovado por este parecer.

**Favorável ao reaproveitamento do DTO TU1 e ao primeiro incremento cadastral/modelos, com condições abaixo.** Não aprova ativar comparativos agregados, participação, cobertura ou filtros de responsabilidade docente a partir dos campos legados existentes. O painel individual e a listagem têm definições diferentes hoje; não usar a média da listagem como substituta do TU1.

## Achados que afetam a implementação

1. **Média legada da listagem não é a métrica TU1.** `apps/api/app/Http/Controllers/Api/TurmaController.php:57` usa `AVG(nota_final)` das notas escopadas, sem exigir cartão vigente lido, percentual válido ou mesmo recorte de avaliações do painel. `apps/web/resources/views/turmas/index.blade.php` divide essa média por 10 e aplica referências fixas 5/7. A nota máxima é configurável por prova. Preservar o campo legado por contrato não obriga a reutilizá-lo no novo painel; não rotular como aprendizagem, aproveitamento comparável ou percentual.
2. **KPIs globais e lista filtrada têm populações diferentes.** `TurmaController::index`, linhas 66–76, reinicia `baseQ` sem busca/escola/ano/ativo. Logo, `total_turmas`, `total_ativas` e `total_alunos` representam o alcance autorizado completo, enquanto `turmas` é a coleção filtrada. É válido mostrar ambos apenas com rótulos inequívocos. Não usar esses KPIs como total do filtro. Não converter erro de API em lista vazia ou contagem zero.
3. **Custo da listagem cresce por turma.** Há contagem de alunos e, para perfil pedagógico, média dentro do map de cada turma. Não expandir o padrão para N chamadas a PainelTurma ou vários gráficos por card. P3.3/P3.4 precisa consulta agrupada, com orçamento e medição. O teste de quantidade de consultas de `TurmaResumoDesempenhoTest` cobre `show`, não prova que `index` é constante.
4. **Autor é autor, não docente responsável.** `PainelTurma.php:29–31` define professor_id por `provas.criado_por` e `perfil_criacao` professor/professor_autonomo; não representa o coordenador que criou prova nem o docente atribuído hoje. A UI atual diz Professor autor e deve continuar assim até haver contrato novo. Opções de autores/disciplinas são construídas antes de aplicar os filtros, não são selects relacionados ainda.
5. **Filtro de disciplina inclui a prova inteira.** `PainelTurma.php:26–27` usa prova_disciplinas para selecionar provas, não questões. `PainelTurmaAnaliseTest` comprova que Matemática e Física da mesma avaliação retornam a mesma base global. Rotular análise disciplinar como funcional seria incorreto sem item×disciplina e regra explícita. A versão inicial pode conservar Resultado global da avaliação e um modelo de análise disciplinar.
6. **Professor atual e histórico não são equivalentes.** `Escopo::consultarProvas` mantém autoria própria e provas institucionais autorizadas nas turmas/disciplinas/atribuições do vínculo atual. Não derivar docente da aplicação a partir dessa autorização de leitura. Preservar histórico de autoria por perfil; mudança de cargo do usuário não reatribui uma prova. A identificação fixa de uma disciplina deve vir de atribuição autorizada, não de contar as disciplinas de avaliações disponíveis (uma prova ENEM pode ter várias).
7. **Pessoa distinta no TU1 é cadastro local distinto.** O COUNT DISTINCT atual usa notas.aluno_id. É correto na turma/espaço atual, mas não prova unicidade civil entre escolas ou cadastros provisórios. No agregado interinstitucional, usar rótulo Cadastros de estudantes distintos, ou resolver identidade conciliada com ID1 antes de afirmar pessoas únicas. Não deduplicar por nome/CPF declarado nem somar os DISTINCT de cada turma: um transferido pode aparecer em várias turmas históricas.

## Contrato vigente sustentado — painel de uma turma

Fonte canônica: `apps/api/app/Services/PainelTurma.php`, `AnalisePainelTurma.php`, `Support/ConsultasPedagogicas.php`. Entrada sempre sob Escopo e permissão; a regra administrativa é revisada pelo agente de autorização.

| Bloco / identificador sugerido | Unidade e população | Cálculo e significado | Filtros / limites |
|---|---|---|---|
| TI-AVALIACOES | Provas distintas vinculadas à turma, autorizadas, publicadas/em correção/corrigidas | COUNT de provas com aplicação até hoje; sem data somente em correção/corrigidas. Não é presença comprovada nem número de sessões de aplicação | Disciplina seleciona prova toda; autor original e intervalo data_aplicacao. Sem data excluída ao aplicar limite temporal |
| TI-RESULTADOS | Registros de nota ligados a cartão vigente com status lido, na turma de aplicação | COUNT de notas do recorte; o mesmo estudante pode contribuir em várias avaliações | Mesmos filtros de prova; nota anterior de cartão em revisão não conta como final |
| TI-ALUNOS-RESULTADO | Cadastros aluno_id distintos com resultado final | DISTINCT em todas as notas selecionadas, antes de arredondar/formatar | Inclui aluno transferido com resultado histórico daquela turma. Não usar como alunos atuais ou taxa de participação |
| TI-ACERTO | Percentual por resultado final com total_questoes > 0, acertos entre 0 e total | AVG(100×acertos/total_questoes); cada resultado tem o mesmo peso; não é SUM acertos/SUM questões | Exibir n válido e exclusões. Sem base: null, não 0. Sem inferência de equivalência entre instrumentos |
| TI-MEDIANA | Mesma base válida | Percentil central, média dos dois centrais em n par | Escala percentual descritiva, não TRI nem comparação de qualidade docente |
| TI-FAIXA | Mesma base válida | Mínimo e máximo observados | Não é meta, proficiência ou intervalo de confiança |
| TI-DISTRIBUICAO | Resultados, não estudantes | DTO fornece 10 faixas [0,10), … [90,100], contagens inteiras; total deve somar n | Se visual agrupar em 5 faixas, somar pares, sem alterar DTO ou rótulos/bordas. Não recomputar a partir de médias |
| TI-POR-AVALIACAO | Um instrumento e seus resultados válidos | Média, mediana e n por prova; null se n=0 | Não ligar barras como ganho longitudinal sem comparabilidade |
| TI-SITUACOES | Todas as notas finais, inclusive percentual inválido | Status de aprovação registrado: aprovado, recuperação e outros | Não recalcular por limiar universal 5/7. Denominador pode diferir do gráfico percentual e precisa ser declarado |
| TI-ATENCAO | Cadastros com pelo menos uma indicação de recuperação registrada no recorte | Um aluno por linha, total resultados e recuperações; preserva toda a lista com expansão | Não representa risco, diagnóstico ou plano de intervenção já existente |
| TI-CRITERIO | Alunos com resultados e todos eles aprovados no recorte | aprovados = resultados, resultados > 0 | Quem não fez/tem resultado não entra. Não atribui proficiência |
| TI-TOP5 | Alunos com ao menos um percentual válido | Média por aluno, arredondada a 1 casa no contrato atual; ranking até cinco linhas, posições competitivas empatadas e aviso de empatados no corte | É descritivo do recorte, não progresso; conservar empate do DTO, não gerar ranking diferente no JS/PDF |
| TI-LEITURAS | Cartões vigentes atribuíveis à turma | Estado atual do cartão e último envio, não todas as tentativas arquivadas | Permissão correcao.acompanhar; categorias podem se sobrepor (ex.: não identificado em processamento), portanto não somar como partição exclusiva |
| TI-CADASTRO | Alunos atuais ativos na turma | COUNT atual do cadastro, fora dos filtros de desempenho | Separar de histórico de resultados, matrículas temporais e participação |

Sobre notas anuladas/idiomas: os percentuais do painel usam o resultado salvo, não recalculam gabarito. A análise por questão já separa anulada/null, sem registro, branco, ambígua e idioma, mas fica nos resultados da avaliação conforme decisão vigente. Não chamar branco de ausência e não restaurar o quarto gráfico no painel.

## Primeiro incremento agregado seguro

Pode ativar agora, com escopo conferido e critérios de UI/P8:

- Coleção de cards das turmas já autorizadas, identificação, série, turno, ano, estado de arquivamento, imagem autorizada e ações existentes. Reutiliza IMG-L, não abre um pacote paralelo.
- KPIs cadastrais calculados sobre a coleção completa que a API realmente retornou: Turmas no resultado, Em uso no resultado, Arquivadas no resultado e Cadastros de alunos ativos nas turmas do resultado. O endpoint atual não pagina; ao introduzir paginação, mover totalização ao servidor e expor sua população. Não usar `count(pagina)` como total.
- Distribuição cadastral por turno/etapa para gestão, calculada a partir das turmas autorizadas e declarada como distribuição de turmas, sem interpretação de desempenho. Null/etapa não preenchida é Não informado, não excluído silenciosamente.
- Manter comparações de desempenho, cobertura turma×disciplina, tempo de devolutiva, participação, atrasos e habilidades como modelos neutros até suas consultas/fontes próprias. Um zero real de cadastro não ativa um modelo pedagógico.

P3.3 pode acrescentar agregados reais por consulta agrupada, preservando campos legados e contrato de API. A unidade recomendada é **prova×turma×resultado final** com chaves distintas explícitas. Uma prova aplicada a três turmas é uma avaliação distinta e três aplicações/associações; escolher o rótulo antes do COUNT. Para média global de acerto, agregar soma de percentuais não arredondados e n válido; não média simples das médias arredondadas das turmas. Para percentual de acertos por item, usar acertos/base com a mesma definição daquele item/idioma. Nunca misturar as duas ponderações sob o mesmo nome.

## Elegibilidade histórica — contrato para P1.4.6/P3.3.5, não implementação presumida

Vincular a MT1 e ao domínio de aplicações. Registrar identificador/versionamento da aplicação, espaço, turma no instante, estudante local/pessoa conciliada quando disponível, fonte da elegibilidade, intervalo/corte, situação elegível/dispensado, motivo e ator. Captura idempotente e auditada; alteração posterior cria versão/evento em vez de apagar a população anterior.

Ingresso tardio, transferência e reaplicação precisam de política da aplicação: não adicionar retrospectivamente por matrícula atual. Reaplicação é nova oportunidade vinculada ao evento original; tentativa de leitura do mesmo cartão não cria nova aplicação nem estudante esperado. Legado sem evidência permanece Sem base confiável. Presença/ausência confirmadas são eventos distintos do recebimento/processamento do cartão. O denominador confiável não ativa taxa enquanto o numerador estiver indefinido ou parcial. Nenhuma migration/retroassociação foi autorizada por este parecer técnico.

## Testes necessários e evidência atual

Fontes examinadas: PainelTurmaIndicadoresTest, PainelTurmaAnaliseTest, TurmaResumoDesempenhoTest e PainelTurmaLimitePdfTest. Os arquivos contêm casos de null/zero/percentual inválido, revisão versus nota final, aluno transferido, espaço cruzado, perda de permissão de acompanhamento, mediana par/ímpar, multidisciplinar, idiomas/anulação e empates. **Não foram executados nesta revisão**, logo não há novo resultado PASS aqui.

Antes de ativar o novo agregado, acrescentar casos com:

1. Turmas com tamanhos diferentes, resultados por aluno diferentes e nota máxima diferente de 10: demonstrar ponderação e não reuso da média legada.
2. Busca/escola/ano/arquivadas: KPIs do filtro versus total autorizado, inclusive retorno vazio e falha real.
3. Mesmo aluno em duas turmas históricas e mesma prova em várias turmas: não duplicar estudantes/provas pelo N:N; aplicações explicitamente separadas.
4. Autor coordenador, mudança posterior de perfil, substituição docente, autoria própria versus institucional e selects relacionados.
5. Professor com uma/múltiplas disciplinas e prova multidisciplinar: não vazar outras provas nem apresentar percentual por disciplina sem itens atribuídos.
6. Status finais fora dos critérios conhecidos, tentativa antiga arquivada e última leitura em revisão; sem converter cobertura/frequência a partir desses registros.
7. Secretaria/auxiliar: ausência dos campos pedagógicos na resposta, não só CSS oculto. Modelos pedagógicos também ausentes.
8. Crescimento de turmas/estudantes/provas: orçamento de consultas sem N+1 e comparação de tempo sob mesmo recorte; estado de falha por região sem zero substituto.

**Aceite de dados para primeiro incremento:** cards cadastrais fiéis à coleção e à autorização; indicadores TI existentes preservados; modelos não se passam por dado real. O parecer final deve citar commit, testes executados, telas atuais e publicação; este é parecer prévio de contratos.
