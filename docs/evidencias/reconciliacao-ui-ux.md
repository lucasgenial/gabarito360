# Reconciliação dos pedidos de UI/UX — TU1 e entregas relacionadas

07/11/2026. Auditoria **somente leitura** do main **a80a7e3cf65ab05a66c1a8cb982f68d1b0232fbb** por `git show`, sem incorporar mudanças em andamento. O release TU-IMGa67b2fb e esse merge têm árvore sem diferenças (`git diff --stat a67b2fb a80a7e3` vazio). Não alterei fonte, Git, navegador, servidor ou dados. A correção atual da edição da turma e o refinamentoPDF estão deliberadamente fora deste baseline.

## Conclusão

Existem lacunas reais de integração, não apenas um problema de o usuário localizar o recurso. A imagem chegou ao detalhe da turma e ao perfil próprio, mas não ao formulário Editar turma, ao modal Nova turma ou ao cadastro de membro/professor. A lista de turmas ainda usa letras. O PDF foi mantido funcional e teve sua paginação corrigida, mas a paridade com o painel novo ficou explicitamente adiada: ainda imprime questões e não os grupos nominais.

Os demais pedidos principais de TU1 estão presentes na árvore examinada. Isso **não** significa conferência autenticada atual na homologação: os recibos de TU-IMG registram publicação e a pendência de login. A aprovação local de design não fecha essa pendência.

## Como ler os níveis de evidência

- **Código**: caminho e comportamento presentes no commit fixado; não execução nova.
- **CI**: há testes pertinentes e recibo de execução; não comprova que todo requisito visual foi exercitado.
- **Publicação**: recibo operacional informa release na homologação; não afirma navegação logada por perfil.
- **Visual**: distinguir capturas de QA local de capturas reais do servidor. Não reutilizar um aceite antigo para declarar visual novo conferido no servidor.

## Matriz dos pedidos

| Pedido | Código no main a80 | CI/ensaios registrados | Publicação e visual | Resultado/lacuna |
|---|---|---|---|---|
| Cinco abas da turma | turmas/show define Visão geral, Alunos, Disciplinas e professores, Provas e Acesso; _abas e turma-abas.js preservam hash/history.state, teclado e resize | TurmaOrganizacaoWebTest e ensaiosTU1 | Integra release; há parecer realTU1 anterior, além de capturas locais atuais | Implementado. Sem nova auditoria autenticada de todas as abas neste baseline |
| Voltar ao grupo Alunos após adicionar/editar | alunos/create usa retorno de turma; AlunoController redireciona a turmas.show com secao=alunos após salvar | Coberturas de retorno em TurmaOrganizacaoWebTest/fluxosR2 | Já incluído em release; evidências de fluxoR2 anteriores | Implementado; não é retorno genérico à lista |
| Disciplina+professor em dois selects | disciplinas/_atribuir-turma tem disciplina_id/professor_vinculo_id; confirmação explica os efeitos; formulário antes do catálogo recolhido | TurmaRevisaoTest/TurmaOrganizacaoWebTest e ensaio de par atômico | RealR2 já inspecionado; quando não há disciplinas livres há mensagem própria | Implementado. Ausência de opções quando todas estão atribuídas é um estado explícito, não desaparecimento do formulário |
| Duplicatas/co-docência/períodos | GestaoDisciplinas rejeita sobreposição de atribuições sem filtrar papel; preserva histórico e recusa professor repetido no intervalo | TestesAPI de revisão/exclusividade; suíte integrada | Política de novos vínculos no release; não concedi/testei vínculos reais nesta auditoria | Novas atribuições exclusivas conforme última ordem humana. Histórico/papel legado não é autorização para sobreposição nova |
| Editar turma em página completa e campos adicionais | turmas/edit: nome/série/ano, turno, etapa_ensino, sala, capacidade, observações | TurmaCadastroExpandidoTest/TurmaOrganizacaoWebTest | PublicadoR2; capturas reais37 anteriores conferidas | Implementado, **exceto imagem** |
| SeisKPIs e gráficos | _painel/AnalisePainelTurma: seisKPIs, distribuição, média/mediana, situações; gráfico de questões removido da Web | PainelTurmaAnaliseTest/Indicadores e suítePR207 | PR20763247f3 publicado; visual atual aprovado em QA local | Implementado; sem nova conferência logada do dashboardR3 no servidor |
| Atenção, dentro do critério eTop5 | _painel_alunos + turma-painel.js; bases/resultados; Ver todos acima de5 em atenção/critério; empate no corte sinalizado; regra nominal autorizada | Revisão técnica e testesAnalisePainelTurma | Captura26 local e fontes aprovadas; integra release | Implementado naWeb. Não é diagnóstico, ranking global ou componente já incorporado aoPDF |
| Filtro sóícone/modal/recorte sutil | show/_painel: ícone com rótulo acessível, filtros em dialog, recorte condicional em itálico e ajuda recolhida | Testes de filtros/422; capturas07/08/16/17 locais | PublicadoPR207; não teste logado novo | Implementado |
| Cores, ícones e texto nas ações | show e turma-detalhe.css: filtro/imprimir azul, PDFteal, editar âmbar, arquivo danger; editar/arquivar identificados por texto | CoberturaWeb + inspeção de fonte | Capturas22/23 locais aprovadas, release207 | Implementado nas ações solicitadas do cabeçalho. Não equivale a revisão de toda ação de todas as telas |
| Arquivar em modal da aplicação | components/arquivamento e arquivamento.js: dialog próprio, efeito/nome, cancelamento e envio do formulário original, sem confirm nativo | ArquivamentoCadastroTest/ConcorrenciaMariaDb, testesBFF de retorno | Captura24 e foco/cancelamento locais relatados, release207 | Implementado; sem excluir históricos ou chamar de encerramento temporal de matrícula |
| Transferir aluno entre turmas com revisão | _alunos aciona dialog; turma-transferencia.js e alunos/_transferencia-form: origem/destino/motivo e duas etapas, Confirmar e salvar | TurmaRevisaoTest e estados422/409/incerteza | Capturas reaisR2 de abertura/revisão canceladas, semPOSTfinal real; release mantém fluxo | Implementado, sem afirmar transferência real executada |
| Imagem no detalhe da turma | avatar-turma/show/administrativo/_imagem; edição condicionada a turma.gerir | ImagemTurmaTest/PessoalTest/WebTest | PR208a67 publicado; capturas locais01..10 aprovadas | Implementado. Conferência autenticada real pendente |
| **Imagem em Editar turma** | **Nenhum avatar-turma, acionador ou include_imagem em turmas/edit no baseline** | CI verde não cobre um elemento que não foi implementado | Ausente no releasea67/a80; correção está sendo implementada à parte | **Ausente no baseline. P0corretiva atual**, não marcar concluído até novo commit/ensaio/publicação |
| **Imagem ao cadastrar Nova turma** | Modal de index tem só nome/série/turno/escola/ano, sem avatar_id/foto/editor | Não há fluxo de imagem antes deID nesse modal | Ausente de código e release | **Ausente. P1**. Exige rascunho de seleção e tratamento do novoID; não chamar o detalhe após criar de recurso do cadastro |
| Imagem na lista de turmas | index usa div.turma-av com letras do nome, não x-avatar-turma | Não há prova de renderização da imagem nessa lista | Continua inicial/abreviação no release | **Parcial/consistênciaP2**: imagem persistida não se reflete no ponto de descoberta da turma |
| **Foto/avatar no cadastro de professor/membro** | membros/create tem previewSVG/iniciais, sem inputfile, seletor36, crop ou imagem-usuario; membros/vinculo também sem imagem. MembroController::create retorna essa view | ImagemUsuario/Perfil testes referem conta própria; não provam cadastro pela gestão | Modal do perfil próprio existe e foi atualizado para adultos; cadastro de membro não | **Ausente no ponto pedido. P1**. Distinguir foto global da pessoa de vínculo escolar; não permitir sobrescrever foto de conta existente por simples cadastro escolar |
| Professor reutilizar imagem da conta | avatar-usuario e modal/layouts_imagem-usuario usam catálogoeducadores; imagem-usuario.js atualiza conta própria | TestesImagemPerfil/Usuario e inspeção dos36assets | Capturas locais01/02; PR208 publicado | Implementado na conta própria/componentes integrados, não em todo preview manual de membro |
| **PDFparidade com painel atual** | gerar_painel_turma.py tem seisKPIs e análises, mas **ainda Acerto por questão**; sem Atenção/Dentro/Top5 | TestesPDF cobrem renderização/paginação anterior; quatroPDFs/20PNGs anteriores auditados | PDFfuncional e orientações aprovadas na etapa anterior; refinamento foi adiado pelo usuário e retomado agora | **Parcial e explicitamente pendente TU1-PDF**. Não afirmar paridade final comWeb |
| Tabelas e mobile | x-tabela integrada, labelsSSR, cards preservando ações; TBL2 migrou famílias; _alunos usa componente cominset | TabelasWebTest/ensaiosTBL1/TBL2 | Pareceres próprios locais/reais anteriores; sem inventário visual integral novo | Padrão implementado; **cobertura transversal não pode ser declarada100%** por essas capturas. Listas/manuais legadas exigem revisão pontual |
| Área útil100% | Layout compartilhado e CSS/global/main redimensionados; dashboard local amplo/mobile | Ensaios de overflow e testesWeb | Evidência anterior de global e capturas atuaisTU1 | Padrão presente. **Formulários legados de membros ainda usam identity-card lateral/member-layout**, fora da composição empilhada recente |
| Breadcrumb dinâmico | layouts/_breadcrumb chama Support/Breadcrumbs e fallback@section quando não mapeado | BreadcrumbsTest | Motor integrado; capturas de fluxosTU1/UR4 anteriores | Implementado com fallback, não mapeamento comprovadamente completo de todo sistema |
| Toast | resources/js/notificacoes usa SolidToast real, região em fluxo, feedback interno em dialog | EnsaiosOMR2 específicos e suíteWeb | ParecerOMR2 e publicação anterior | Implementado como padrão; avisos contextuais/validação continuam intencionalmente, nem todoflash foi convertido emtoast |
| Loaders | navegacao/estrutura estende layoutapp, esqueleto e estado especial de entrada de vínculo; thumbs/fotos têm carga própria | EnsaiosUI1/UR4 e fotos | UI1 teve conferência real48/48; isso não prova todo carregamento regional de toda página nova | Implementados casca+skeleton e regiões existentes. **Não há prova de migração individual de todoSSR para carregamento progressivo porcard**; não prometer esse alcance |

## Evidência de CI e publicação examinada

- tu1r3-gates-finais.json: PR207head63247f3, mergec506073, cincojobsSUCCESS do run42344959962. tu1r3-publicacao-recibo.json informa publicado=true/sha63247f3 e produção_alterada=false.
- tu-img-gates-finais.json/tu-img-fechamento.json: PR208, cincochecks aprovados do run42347042724;936API/347Web/721MariaDB passaram segundo o recibo, com seus skips. A execução não foi repetida nesta auditoria.
- tu-img-publicacao-recibo.json informa a67b2fb publicado na homologação,128tabelas/201arquivos originais preservados e produção não alterada. **conferencia_autenticada=pendente_usuario_indisponivel**; fechamento permanece PUBLICADO_HOMOLOGACAO_CONFERENCIA_AUTENTICADA_PENDENTE.
- tu1r3-design-premerge-final.json e tu-img-design-premerge-final.json são aceites **locais**. tu1r2-design-homologacao-final.json documenta conferência real anterior, no releasecaca1e95, não do novoR3/TU-IMG.

Nenhum desses recibos transforma os formulários ausentes em implementados. CI verde verifica o contrato testado, não completude contra todos os pedidos humanos.

## Fechamento prático, sem ampliar implementação nesta auditoria

1. Fechar correção já em andamento de imagem em **Editar turma** com direção específica existente e captura antes/depois. Não misturar seu estado dirty com o mainauditado.
2. Registrar como tarefas explícitas imagem em **Nova turma** e imagem em **cadastro do professor/membro**, com préconsulta de fluxo/escopo de conta e sem concessão de permissões por efeito colateral. Para conta existente, reutilizar foto da pessoa sem permitir sobrescrita silenciosa pela escola. A responsabilidade da imagem precisa ser definida tecnicamente, sem pedir autorização de cada subetapa.
3. Retomar TU1-PDF conforme direção já emitida; remover questões e incorporar grupos autorizados com bases, empates, identidade e paginação.
4. Refletir imagem na lista de turmas como consistência, preservando componente/tabela e fallback; escopo menor, ainda não implementado.
5. Após cada entrega autorizada, registrar código/testes/CI/release **e** conferir o ponto de uso pedido, não só o endpoint ou o modal reutilizável. Quando login real estiver indisponível, declarar pendente a confirmação logada; não bloquear planejamento nem alegar que foi feita.

A auditoria não edita a aplicação, não inaugura redesign de todos os formulários, não planejaGII e não autoriza produção. P0/P1/P2 acima são prioridades de reconciliação, não novas permissões. As lacunas de código devem aparecer na tabela de planejamento do responsável como pendentes até seu fechamento concreto.

## Adendo posterior à auditoria do maina80 — correção EDIT

A integração de imagem em Editar turma foi implementada em candidato separado e aprovada visualmente em QA local (tu-img-edicao-design-premerge-final.md/.json, quatro fontes porSHA256). Isso resolve a lacuna de código no candidato, mas não altera o diagnóstico histórico de a80 nem prova CI/publicação/conferência logada do novo incremento. As lacunas de Nova turma, cadastro de professor, imagem na lista e paridadePDF continuam independentes.
