# Painéis P1 — contratos e autorização

## Referência e alcance

Leitura integral em07/11/2026 de `D:/arquivos-usuarios/lmsouza/Downloads/Plano_Gabarito360_Paineis_Turmas.md` (544linhas) e das orientações complementares em `C:/Users/lmsouza/.codex/attachments/54602087-4337-4c79-9d88-6cac2477f638/Texto colado.txt` (271linhas). Aplicadas as skills architecture/code-review. Fonte de análise: MAIN `c16ceca5342b2313141d690a862ef488400b02bf`, PR209; a árvore `c96e49300a4920ba6af891a707b8e4ad2d517bec` coincide com o checkout de edição consultado. A proposta respeita P0–P8, seus modelos visuais e o protocolo autônomo; não constrói GII/TRI/agenda/finanças como efeito colateral de um card.

Pedido inicial foi somente diagnóstico; depois a raiz autorizou o primeiro ajuste mínimo API por achado concreto. Essa implementação está isolada no commit `98dcdef872d22cca1ada9eba4c800593cdb4928f`, branch `pacote/paineis-p1-projecao`, worktree `worktrees/paineis-p1-projecao`. Não alterei Web, docs177/178/179, rotas, migrations, servidor ou produção. Não houve push/merge/deploy por mim. Minha autoria precisa de revisão independente da raiz; este relatório não a autoaprova.

## Conclusão prévia

A direção P1 é viável com as políticas atuais: catálogo por conteúdo e ação, projeções no servidor e evolução aditiva da API. Não há motivo para esperar o novo servidor físico/M3, EI1 ou todos os motores futuros. O primeiro incremento seguro é impedir que a rota cadastral de turmas sirva conteúdo pedagógico só por turma.ver; depois conectar um catálogo Web que nem envie modelos pedagógicos aos administrativos. Autoria e lotação docente precisam de filtros semânticos distintos; renomear professor_id para Professor sem novo contrato seria incorreto.

## Contratos atuais reaproveitáveis

| Fronteira | Evidência real | Regra a preservar |
|---|---|---|
| Identidade e vínculo | `Permissao::efetivas`, `ContextoVinculo::permissoes/resolver`, `RegimeVinculos`, `Escopo` | Contexto atual revalidado, escola/cliente vigentes, permissões do vínculo e alcance. Não somar papéis da conta. |
| Catálogo de poderes | `CatalogoPermissoes` listas SECRETARIO_ESCOLAR/AUXILIAR_SECRETARIA/VICE_DIRETOR/VICE_EXTRAS; `ContextoVinculo::permissoes` cruza extras concedidos com extras válidos do catálogo | Extra arbitrário no banco não vira poder. Vice conserva leitura atual; mutações só com extra já previsto. Secretaria e auxiliar nunca recebem capacidades pedagógicas pelo perfil. |
| Administrativo | API `TurmaController::index/show` tem ramo administrativo; `PainelTurma::montar` exige relatorio.turma e veta PerfisEscolares::administrativo antes de construir opções/SQL; BFF `ContextoPorAba` whitelist e `turmas/administrativo.blade.php` | Secretaria escolar e auxiliar são administrativos; Secretário de Educação é outro perfil e não deve ser confundido por uma busca de string “secretaria”. |
| Conteúdo nominal | `AnalisePainelTurma::alunos` exige aluno.ver+resultado.ver, exclui aluno/aplicador e usa notas já autorizadas da turma/provas | Não enviar nomes/grupos ao cliente e apenas esconder com CSS. O agregado não dá automaticamente direito ao detalhamento nominal. |
| Correção | `PainelTurma` só constrói acompanhamento se correcao.acompanhar | Contadores de revisão/processamento/falha/não identificado também são conteúdo protegido, não decoração administrativa. |
| Arquivos | Rotas API de relatórios/resultados/fotos/cartões têm capacidades próprias; `PainelTurmaController::pdf` revalida acesso e DTO após geração; `CartaoController::arquivo` e GETs históricos de tentativas conferem alcance | Poder consultar um card ou conhecer ID não libera arquivo. Foto de aluno/turma é cadastro autorizado; cartão-resposta/PDF de avaliação é pedagógico. Legada URL assinada de cartão retorna404. |
| BFF e navegação | UR4, `RevisaoAcesso::BINARIOS/DADOS`, referências de operação, `SessaoWeb`, `ContextoPorAba`, ApiClient | URL fixa Web e referência no corpo; CSRF/revisão para transporte, Bearer só servidor. Aba obsoleta409 sem repetir mutação; perda real da sessão encerra login integral. |

`CheckPermissao` interpreta chaves separadas por vírgula como **OU**. Um bloco que exige aluno.ver **e** resultado.ver não pode usar `permissao:aluno.ver,resultado.ver` como se fosse AND: usar duas guardas ou conferência explícita de todas. É uma armadilha concreta para a implementação P1, não uma mudança solicitada no middleware global.

## Lacunas prioritárias

### P1-SEG-01 — turma.ver não é autorização pedagógica

Na referência c16, GET `/api/v1/turmas` e GET `/api/v1/turmas/{id}` exigem turma.ver. Para todo não administrativo, o controller calcula/envia media_turma; detalhe inclui provas, total_provas e médias/contagens por aluno sem conferir capacidades específicas. `ConsultasPedagogicas` e Escopo limitam instituição/autoria/integridade, mas não substituem essa autorização por conteúdo. Retirar relatorio.turma/resultado.ver/prova.ver mantendo turma.ver ainda deixava essa superfície de dados acessível.

Reprodução local controlada: o teste novo de somente turma.ver falhou na fonte original por presença de `data.turmas.0.media_turma`. A execução usou QA SQLite fictício e troca temporária somente do controller na cópia QA, com restauração em trap; nenhuma fonte Git foi alterada para o ensaio negativo.

A raiz aceitou a prioridade e autorizou o ajuste mínimo descrito no adendo abaixo. A projeção continua dentro de Escopo e mantém shapes dos consumidores plenamente autorizados, sem nova permissão, endpoint, tabela ou flag.

### P1-SEG-02 — modelos também precisam de autorização

Hoje não há catálogo de componentes P1 completo com contrato de modelos/estados. Um simples `@if perfil` ou array global de todos os cards seguido de ocultação no navegador não atende P1.3.6. Modelo de recuperação, ranking, avaliação, agenda pedagógica ou integridade não deve existir na resposta administrativa, nem ter contagem em badge, tooltip, estado JSON, busca, notificação ou relatório.

Primeiro catálogo pode ser local à Web: IDs estáveis + capacidades obrigatórias + predicados de política/escopo + fonte/dependência + estado. Filtrar antes de renderizar e antes de requisitar dados. Isso não concede capacidades na API. O usuário sem permissão não recebe o descritor do bloco; o usuário autorizado sem implementação recebe um modelo estático Em breve, sem nomes/números/eventos reais ou fictícios. Modelos não disparam endpoints inexistentes, polling, ações simuladas nem skeleton permanente.

### P1-SEG-03 — autoria não identifica docente atual ou na aplicação

`AutoriaProva::capturar` grava perfil_criacao e vinculo_criacao_id; `PainelTurma` filtra `professor_id` por provas.criado_por com perfil_criacao de professor/autônomo. O label atual “Professor autor” e o texto de ajuda estão corretos. Não renomear silenciosamente esse campo nem reescrever autoria porque o criador virou coordenador.

DC1 fornece `turma_disciplinas`, `disciplina_professores` e `docente_atribuicoes` datadas. `OpcoesDisciplinaProva::listar` mostra o exemplo de filtrar professor pelo vínculo atual, disciplina/componente ativo, período e indicação. Esse contrato é de operação com provas; não reutilizá-lo sem adaptar como se fosse filtro histórico do painel. Hoje as disciplinas do painel derivam das **avaliações autorizadas presentes**, não de toda atribuição docente vigente; uma turma sem avaliação não demonstra ausência de disciplina/professor.

Contratos a nomear separadamente:

| Conceito | Fonte admissível | Comportamento inicial |
|---|---|---|
| Autor da avaliação | criado_por + perfil/vínculo capturados | Preservar professor_id e label atuais como compatibilidade. |
| Aplicador | Registro explícito da aplicação/ação | Não inferir a partir de autor ou de quem corrigiu. Modelo/indisponível se não houver fonte. |
| Docente atualmente atribuído | DC1+vínculo aceito/vigente+data de referência explícita+alcance | Nova opção/campo aditivo, filtrando disciplinas relacionadas. Não muda dono da prova. |
| Docente na aplicação | Fotografia/referência histórica confiável daquele fato | Não reconstruir usando o substituto atual. Se não existir referência, informar não registrado/indisponível. |

Para professor escolar, uma disciplina autorizada pode ser identificação fixa; múltiplas, seletor somente das pertinentes. Não unir automaticamente disciplina da prova pelo texto. Avaliação multidisciplinar selecionada continua inteira; não inferir nota disciplinar pelos agrupadores N:N.

### P1-SEG-04 — catálogo por capacidade não remove políticas restritivas

Layout pode variar por contexto, mas autoridade vem de capacidades e política do servidor. `GestaoDisciplinas::contexto/escrita` exige coordenação e disciplina.gerir; essa limitação foi aprovada no DC1. O catálogo deve espelhar a operação real, não dar acesso a diretor apenas porque um label diz Gestão. Nova “equipe pedagógica” não existe como um superperfil em `PerfisEscolares`: mapear ao papel/política aprovado quando for o mesmo cargo, ou registrar um trabalho PF1 específico antes de conceder poderes. Um cargo textual no formulário/DTO não concede leitura.

A classificação ADMINISTRATIVOS continua veto de conteúdo pedagógico mesmo se um extra inválido for injetado ou uma configuração futura vier incorreta. Isso é política explícita já existente; não é um array de cargos concedendo novas permissões. Contextos internos (Suporte/Desenvolvimento/Financeiro) tampouco herdam dados das escolas por compor um card.

### P1-SEG-05 — os estados/denominadores são parte do contrato de segurança

Definir por componente: fonte, população, unidade, numerador/denominador, datas, filtros aplicados, exclusões, atualização, nominal permitido e ação. O KPI cadastral total_alunos é diferente de alunos_com_resultado, participações, cartões vigentes e tentativas históricas. Os períodos de áreas DC1 não são bimestres. Não registrar ausente por falta de cartão; o próprio acompanhamento atual diz base_esperada_confiavel=false.

No catálogo, separar sem permissão (omitido), modelo (estático Em breve), carregando (consulta real), vazio, zero válido, parcial e falha. Nunca transformar403/500 emzero/sem registros. Toda variante administrativa usa fonte cadastral própria, não a resposta pedagógica sanitizada apenas no navegador.

## Matriz mínima para o primeiro incremento

Todas as linhas exigem autenticação/contexto/alcance; capacidades não ampliam o conjunto permitido por Escopo. Veto administrativo vale nas linhas pedagógicas.

| Conteúdo/ação | Requisito mínimo preservando o contrato atual | Fora do requisito |
|---|---|---|
| Identidade/estado da turma, contagem cadastral ativa | turma.ver | Sem objeto fora do alcance; ativo/arquivamento/identidade permanecem. |
| Nomes/lista/imagem cadastral de aluno | aluno.ver além de turma.ver no detalhe | Lista-base vazia sem dados nominais; não construir métricas individuais. Foto continua com sua rota/policy própria. |
| Média consolidada de turma nesta rota | relatorio.turma e não administrativo | Omitir media_turma; não consultar notas. |
| Consolidado individual media_geral/total_provas dentro da turma | relatorio.turma+aluno.ver+resultado.ver, não administrativo | Omitir esses campos nominais; não criar autorização de consolidado só por ler um resultado isolado. |
| Lista/count de provas e status | prova.ver, não administrativo | Coleção-base vazia; total_provas ausente. Status dos itens autorizados é preservado. |
| Média de cada prova no resumo da turma | prova.ver+relatorio.turma, não administrativo | Prova pode manter metadados/status sem média. A ação relatório da prova continua sua própria autorização. |
| Grupos Atenção/Dentro critério/Top5 | Envelope relatorio.turma + aluno.ver+resultado.ver e alcance da base | Não enviar nomes, listas ou contadores de grupos; negar administrativos. |
| Acompanhamento OMR | correcao.acompanhar+alcance; não administrativo | Nada de estados/contadores pedagógicos nos cards administrativos. |
| PDF e detalhamentos | Permissões atuais de cada rota + mesma seleção validada + revalidação antes de entregar | Nenhum atalho por card, URL de arquivo ou cache anterior. |
| Modelo de agenda administrativa | Capacidade de consultar o conteúdo administrativo que vier a existir + escopo | Sem eventos/contadores de avaliações; sem endpoint simulado. |

Escolha conservadora no consolidado individual: esta rota de resumo requer relatorio.turma além de resultado.ver. Não se retirou o direito de resultado individual da sua rota atual; não foi criada uma nova combinação de poderes. Isso evita inferir consolidação histórica por mera leitura de um resultado.

## Evolução aditiva e independência da API

1. Preservar rotas/métodos/payloads existentes e seu significado; o transporte Web fixo não altera contratos internos REST. O patch mínimo só restringe campos conforme capacidade, padrão já existente entre resposta administrativa e pedagógica.
2. Catálogo visual pode começar na Web com Acesso::pode e predicados conhecidos, sem importar modelos/banco/arquivos da API. O backend decide dados e ações; sessão de browser não vira payload de autoridade enviado para a API.
3. Para painel agregado futuro, preferir um DTO/endpoint aditivo de recorte quando houver cálculo novo, em vez de juntar N chamadas de detalhe no cliente ou somar médias arredondadas. Reusar serviços de escopo e medir chamadas/SQL, sem cache de concessões.
4. Novos filtros docentes precisam nome novo/semântica explícita; professor_id atual permanece autor. IDs públicos de recursos da API continuam no contrato; referências Web continuam no corpo e server-bound. Não colocar tokens nas URLs.
5. Status HTTP, erros, MIME/no-store/nosniff, timeouts e limites permanecem; só consultas seguras podem ser retomadas. POST de negócio não é refeito automaticamente depois de resultado incerto.
6. Cada exportação usa os mesmos critérios de conteúdo e a versão do recorte. Modelo Em breve não entra em relatório real. Impressão/PDF em trabalho concorrente precisa integrar esses gates antes da sua entrega, sem sobrescrever o branch.

## Aceite negativo que deve acompanhar P1/P2

- Secretaria/auxiliar: API/tela/fragmento/JSON/PDF/cartão/arquivo/count/agenda/notificação/modelo sem conteúdo pedagógico, inclusive com extras inválidos ou filtro/ID forjado. Auxiliar não ganha importar/arquivar/gerir turma.
- Não administrativo com só turma.ver: nenhum número/lista nominal/pedagógico; lista de turma continua legível e não consulta notas.
- Capacidade isolada: prova.ver não dá média; relatorio.turma não dá nome de estudante sem aluno.ver+resultado.ver; aluno.ver+resultado.ver não dá consolidado desta rota sem relatorio.turma. Nenhuma nova combinação ignora políticas já mais restritivas.
- Mesma conta em escolaA/B e funções diferentes; troca de vínculo invalida dados/formulário/arquivo antigos409 sem replay. Não limpar sessão válida nova por erro de aba antiga.
- Docente substituído, autoria institucional, vínculo encerrado, disciplina de outra escola, futuro e atribuição retirada: preservar histórico autorizado sem dar operações/nomes novos.
- Modelo autorizado não faz fetch até existir implementação; sem permissão não aparece nem no HTML/config. Ativação modelo→funcional distingue vazio, erro e zero real.
- Não inferir privacidade pela presença de nome agregado ou por ausência de botões. Verificar corpo HTTP e ações diretas com Bearer real no gate pertinente.

## Adendo de implementação autorizada

Commit isolado98dcdef872d22cca1ada9eba4c800593cdb4928f: somente `apps/api/app/Http/Controllers/Api/TurmaController.php` e novo `apps/api/tests/Feature/TurmaConteudoAutorizadoTest.php`. A função privada conteudos calcula capacidades da requisição atual, sem cache persistente, cruza veto administrativo e projeta campos/consultas. Mantém coleções-base para consumidores, sem flag global/disponibilidade nova; nomes/provas ausentes de autorização não são emitidos e as métricas protegidas são omitidas. O consumidor Web deve continuar a esconder a seção sem permissão em vez de interpretar coleção-base vazia como fonte funcional vazia.

Novos7testes/83asserções PASS5,16s. Regressões62testes/833asserções PASS70,79s em QA próprio `/tmp/g360-p1-projecao-api`, SQLite e dados fictícios. Conjuntos: TurmaRevisaoTest, ProvasAutoriaTest, SecretarioEscolarTest, Pf1PerfisEscolaresTest, ConsultasEspacosTest, DashboardDesempenhoContratoTest, ImagemTurmaTest, DadosPessoaisTest. O teste negativo na baseline c16 falhou exatamente pelo campo protegido. Diffcheck passou. Não executei CI completo ou MariaDB novo nesta entrega; pipeline da raiz e revisão independente continuam obrigatórios.

Critério para fechar esse primeiro incremento: raiz revisar o commit e matriz, integrar ao seu catálogo Web sem autoridade por rótulo, executar regressões/CI, Product conferir as telas da versão integrada e publicar/conferir exclusivamente homologação. Esta correção não conclui todoP1/P2, semântica docente futura, captura de elegíveis, agenda ou P4–P7.
