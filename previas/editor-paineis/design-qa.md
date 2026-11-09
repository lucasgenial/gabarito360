# Design QA — editor de painéis

## Correção atual: altura e continuidade — 09/11/2026

final result: **passed**. Parecer específico de `/root/product_design_review`, sem bloqueios. Aprovação humana da prévia e integração continuam pendentes.

A revisão examinou a fonte atual e as capturas desktop, seção filha e mobile em `operacao-20261102/editor-resize-visual/02-desktop-recorte-corrigido.png`, `03-subsecao-reduzida.png` e `04-mobile-leitura.png`. O registro `operacao-20261102/editor-resize-design-final.json` identifica seis hashes SHA256 com normalização LF das fontes/outputs e três hashes das imagens. O commit base `9d35ac0` é a versão anterior; os hashes identificam o fix efetivamente revisado.

O mínimo histórico foi removido: o cabeçalho é medido depois da largura nativa, e o mínimo do pai depende dos filhos posicionados. Teclado e mouse usam a geometria viva. O helper de continuidade mantém a âncora e a rolagem durante a remontagem; restaura o controle destruído somente se o foco voltou ao body/html, sem roubar foco externo. Essa correção de foco foi uma observação da revisão e foi resolvida antes do aceite.

Ensaios executados no Chrome pelo implementador: seção com dois gráficos de sete linhas reduzida de22 para10; mouse10→13→10 sem salto; grip focado, mouse10→11 e Escape em novo gesto preservaram scroll829/top609.125/foco. Seção filha15→10 e pai externo25→20 preservaram os conteúdos. Pin bloqueou mover/resize sem mudar geometria. Salvamento/reabertura em contexto fictício separado manteve alturas18 e17. Mobile390×844 mostrou leitura da disposição salva, sem controles de edição nem overflow horizontal. Nenhum erro de console observado nas duas abas. Build isolado e23 testes do modelo passaram.

O revisor não operou o Chrome nem repetiu esses gestos: sua inspeção independente foi da fonte e das imagens atuais. Capturas não provam acessibilidade integral. Este aceite cobre a correção de altura/continuidade e não revalida todo o editor, indicadores, API ou persistência no servidor. Nenhuma autorização de merge em main ou deploy decorre dele.

## Histórico: rodada2

final result: **passed**

Revisor `/root/product_design_review`. **Aprovação humana pendente.** Resultado restrito à préviaLOCAL atual, semAPI, integração, mainmerge oudeployhomologação/produção autorizados.

## Direção vigente e comparação

A correção humana posterior revogouWYSIWYGnaabaDistribuição. O modelo atual é **Kanbanhierárquico**: três painéisroot porpadrão, Semseçãofixa e subseções embutidas comwidgets. Ajustesfinos usa grade mista raiz/subgrades, comwidgetsavulsos econtêineres disputando a mesma área. A referência67310b81 foi inspecionada junto àcaptura02 para hierarquia20/18/16bold; o Kanbananterior07 orienta continuidadedeinteração, nãofidelidadepixel.

## Verificação atual

1. Capturas01/02 atuais mostram trêsroots,Semseção eAgendafilha comseusitens; títulos econtroles distintos, direçãobase→topo visível, Semseção não reordenável. Semoverlapmaterial.
2. Captura03 mostraResultadosdaescola avulso fora docardcentral, comgradefine/pin/move/resize. Conteúdo egráfico íntegros. Scrollregionaldalista não foi confundido comscrollerdegráfico.
3. Captura04 mostra leituramobilecomregistroseidentidade/rodapé/nav,semcontrolesdeediçãovisíveis no recorte. A ausência global deedição/nooverflow foi ensaiada pelo root, não provada apenas pelo print.
4. Captura05 demonstra finoescurolegível. Capturas01/02 antigas antesdocorreto@source não foram usadas.
5. Fonte revista:reorderdeirmãos6+6aplicaordempedida,semalterarXYinterno; focusNode restauraalvosconformeaba; normalizar veta ampliaçãodepainelfixado; headersauto medidosdebaixoparacima;limpezadoDDantesengines protege troca deviewport.

**23testesunitários executados por este revisor passaram**, incluindo ciclo/profundidade/subtree,catálogo,remoçãopromovendo filhos,migraçõeslocais1/2,pins,ordem6+6 eSaveconflito. AçõesChrome foram executadas e relatadas pelo root:dragDevolutivasparaAgendafilha,IDsúnicos,Cancelarrestauraárvore,novovazioocultoleitura,avulsoresize6→7,gestoativo390→1440/DDlimpo/draftpreservado,Save/reloadcontextoB e409reconsulta. Não opereiChrome nesta rodada.

## Resultado e fronteira

Sem P0/P1remanescente identificado na fonte/capturas examinadas. **passed** significa qualidade suficiente para apresentar esta prévia ao responsável, não aprovação de propagação aoapp. Estado de fonteWIP: HEAD315ed0b é base, não commitdaUIcompleta. OPS/editor-paineis-rodada2-design-final.json registra snapshotSHA256LFdasfontes/outputs atuais (incluindograde-visual e quadro-secoes) e5SHA256PNG.

Preferências e dados sãofictícios locais; não hátesteautenticado/APIs/persistênciaservidor. Não é auditoriaWCAGintegral nemvalidação de métricasnovas/printPDF. Aprovações anteriores da primeira prévia/WYSIWYG não substituem esta revisão; nenhumdeploy oumainmerge autorizado.
