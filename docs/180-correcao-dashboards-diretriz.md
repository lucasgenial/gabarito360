# Correção do planejamento e da implementação dos dashboards do Gabarito360

Quero corrigir a interpretação do planejamento e refazer a implementação das páginas **Turmas** e **da Turma**, incorporando as diretrizes abaixo.

A imagem anexada mostra o problema a corrigir. Ela não representa o resultado visual final desejado.

Faça a reconciliação com o planejamento existente, atualize os documentos necessários e implemente as correções. Não encerre a tarefa apenas com uma proposta ou com alterações na documentação.

## 1. Correção central da interpretação

Estamos construindo **dois dashboards distintos**:

1. **Dashboard de Turmas:** visão agregada das turmas às quais o usuário tem acesso.
2. **Dashboard da Turma:** visão detalhada de uma turma específica.

Minha preferência por cards e gráficos se refere principalmente à **apresentação dos indicadores e das análises**.

A relação de turmas pode ser apresentada como tabela e, nesta correção, **a tabela deve ser sua apresentação padrão**. Transformar cada registro de turma em um card grande não atende, por si só, à proposta de dashboard.

Também fica corrigida qualquer orientação anterior que tenha proibido dados fictícios nos cards de recursos futuros:

> Os cards “Em breve” devem apresentar sua composição visual completa, incluindo gráficos ou outros elementos apropriados, preenchidos com dados fictícios claramente identificados como demonstrativos.

Esta orientação substitui as recomendações anteriores conflitantes sobre apresentação em cards e uso de dados demonstrativos.

Preserve as funcionalidades válidas e a lógica que puder ser aproveitada. Refaça a composição e os comportamentos que não atendem a estas diretrizes.

## 2. Composição dos dois dashboards

### 2.1. Dashboard de Turmas — visão agregada

A página deve ajudar o usuário a responder:

**“Como estão as turmas sob minha responsabilidade e onde preciso agir?”**

Organize a composição com:

- Cabeçalho, identificação do contexto e filtros pertinentes ao perfil.
- Cards de indicadores agregados.
- Gráficos de acompanhamento e comparação entre turmas.
- Blocos de planejamento, agenda e pendências.
- Relação operacional das turmas, apresentada inicialmente em tabela.

Os indicadores devem distinguir, quando aplicável, **alunos distintos**, **vínculos ativos**, **avaliações** e **resultados**. Não usar um total com um nome que represente outra medida.

Os gráficos devem ocupar uma parte relevante da página. Não considerar o dashboard concluído apenas porque existem cards cadastrais e blocos com títulos.

Para comparações de desempenho entre turmas, definir uma base comparável. Não comparar automaticamente médias de avaliações diferentes como se representassem a mesma medida.

### 2.2. Dashboard da Turma — visão individual

A página deve ajudar o usuário a responder:

**“O que está acontecendo nesta turma e quais resultados, alunos ou conteúdos precisam de acompanhamento?”**

Organize a composição com:

- Identificação da turma e do recorte aplicado.
- Indicadores da turma.
- Distribuição dos resultados.
- Análise das avaliações.
- Acompanhamento dos estudantes, conforme as regras existentes.
- Agenda, devolutivas e intervenções pertinentes à turma.
- Modelos visuais dos recursos futuros previstos no planejamento.

Verifique a implementação atual e preserve os indicadores, gráficos, grupos de acompanhamento e regras já aprovados, corrigindo sua integração visual quando necessário.

Não substituir componentes funcionais por modelos demonstrativos.

Os dois dashboards podem compartilhar componentes, mas precisam ter **conteúdos e agregações próprios**. Não basta repetir a mesma página alterando seu título.

## 3. Relação de turmas: tabela como padrão e cards como alternativa

Reaproveite a lógica existente que transforma a apresentação das turmas em **“Tabela com ordenação e paginação”**, invertendo a preferência inicial.

O comportamento esperado é:

1. Sem uma preferência previamente salva, a relação de turmas abre em **Tabela**.
2. O usuário pode selecionar **Cards** como alternativa.
3. Depois de uma escolha explícita, a preferência pode ser salva e respeitada nos próximos acessos.
4. A alternância deve usar o mesmo conjunto de registros e as mesmas permissões.

Preserve, quando aplicável:

- Busca e filtros.
- Ordenação escolhida pelo usuário.
- Paginação.
- Seleção de registros.
- Ações disponíveis.
- Contexto de navegação.

A alternância não deve introduzir uma nova ordenação, alterar resultados ou provocar perda desnecessária do estado da listagem.

Na interface, prefira um título de seção como **“Turmas”** e um controle claro **“Tabela | Cards”**. A tabela deve estar efetivamente visível quando selecionada, sem depender de abrir um bloco recolhido enquanto grandes cards cadastrais permanecem como apresentação principal.

Na apresentação alternativa em cards, use uma composição compacta, adequada à consulta e às ações.

Essa alternância se refere à **relação de turmas**. Ela não deve transformar todo o dashboard analítico em uma tabela.

No celular, adapte a tabela e seus controles para uso adequado. Não force a mudança para cards apenas por causa da largura da tela.

## 4. Cards e gráficos personalizáveis pelo usuário

Os widgets dos dashboards — cards de indicadores, gráficos, agenda e outros blocos previstos — devem permitir **redimensionamento e movimentação manual**.

Essa personalização é uma funcionalidade dos dashboards e deve constar do planejamento, da implementação e dos critérios de aceite.

### 4.1. Movimentação dentro da mesma seção

O usuário deve poder:

- Mover um card para uma posição livre válida dentro da mesma seção.
- Arrastar um card sobre outro para trocar explicitamente suas posições.
- Visualizar o destino antes de concluir a movimentação.

A movimentação deve ficar limitada à seção à qual o card pertence.

Ao trocar dois cards, **somente os dois devem mudar de posição**. Os demais permanecem onde estavam.

Não permitir que a movimentação:

- Ordene automaticamente os cards.
- Empurre os vizinhos.
- Reorganize a seção em cascata.
- Compacte automaticamente os espaços.
- Transfira o card para outra seção.

Espaços vazios resultantes das escolhas do usuário são aceitáveis.

### 4.2. Redimensionamento

Cada componente deve ter limites mínimos e máximos adequados ao seu conteúdo.

O redimensionamento deve:

- Alterar efetivamente a área útil do componente.
- Recalcular a apresentação do gráfico.
- Manter títulos, legendas, eixos, valores e ações legíveis.
- Respeitar os limites da seção.
- Evitar sobreposição com outros cards.

Não basta aumentar o contêiner e deixar o gráfico pequeno, cortado ou com dimensões antigas.

Se o tamanho solicitado invadir outro card ou ultrapassar o espaço disponível, limite ou rejeite o ajuste com indicação compreensível. **Não resolva o conflito movendo outros cards automaticamente.**

Em uma troca entre cards de tamanhos diferentes, mantenha o tamanho associado a cada card. A troca só pode ser concluída se ambos couberem nos destinos sem sobreposição.

### 4.3. Controles de interação

Use controles próprios para mover e redimensionar, de forma que essas ações não conflitem com:

- Tooltips.
- Legendas interativas.
- Seleção de séries.
- Rolagem.
- Links e botões.
- Outras interações dos gráficos.

Pode haver um modo explícito **“Organizar painel”** para apresentar esses controles.

Ofereça uma alternativa acessível por teclado. No celular, evite que uma tentativa de rolar a página mova um card acidentalmente.

### 4.4. Persistência pessoal

Salve posições e dimensões por usuário, contexto de acesso e tipo de dashboard.

As preferências de uma pessoa não podem alterar a apresentação de outra.

Aplicar filtros, atualizar os dados, recarregar a página ou trocar um card de “Em breve” para funcional não pode reorganizar a disposição escolhida.

Preserve a configuração de desktop ao adaptar a apresentação para celular. Uma adaptação responsiva não deve sobrescrever silenciosamente o layout salvo para outra largura de tela.

Disponibilize uma ação explícita para restaurar a disposição padrão.

As preferências salvas não concedem permissões. Se um usuário perder acesso a um componente, esse componente não pode reaparecer por estar registrado em seu layout.

### 4.5. Ausência de reorganização automática

Não ordenar ou reposicionar os cards automaticamente por:

- Nome.
- Desempenho.
- Prioridade calculada.
- Quantidade de dados.
- Tamanho.
- Atualização.
- Estado funcional ou demonstrativo.

A composição inicial deve seguir o planejamento visual. Depois disso, as posições personalizadas pertencem ao usuário.

Quando um novo card autorizado for acrescentado, sua inclusão não pode deslocar os cards já organizados. Quando um modelo passar a funcionar com dados reais, preserve seu identificador, posição e dimensões.

## 5. Cards “Em breve” com demonstração visual completa

Os modelos devem mostrar como o recurso será apresentado quando estiver disponível.

Cada card futuro deve conter:

- Título e contexto.
- Selo **“Em breve”**.
- Identificação visível **“Dados demonstrativos”** ou **“Exemplo ilustrativo”**.
- Gráfico ou composição visual apropriada.
- Valores, rótulos, legendas e unidades necessários à compreensão.
- Dimensões compatíveis com o conteúdo final.
- Comportamento de redimensionamento e movimentação igual ao dos demais widgets.

Não aceitar como resultado final um retângulo com ícone e uma frase explicativa.

Não usar um estado permanente de carregamento para representar um recurso futuro.

### 5.1. Composições concretas

Use o planejamento reconciliado para definir o catálogo final. Como referência mínima para corrigir os blocos apresentados:

| Bloco | Composição esperada |
|---|---|
| Resultados entre turmas | Barras horizontais ou agrupadas, com turmas fictícias identificadas, valores e uma base demonstrativa comparável. |
| Cobertura por disciplina | Matriz de calor entre turmas e disciplinas, ou barras de cobertura, com legenda e definição clara da medida. Se houver percentual, explicitar seu denominador. |
| Próximos compromissos | Miniagenda semanal, calendário compacto ou linha do tempo com eventos fictícios, datas, horários e vínculos ilustrativos. |
| Devolutivas | Progresso de concluídas sobre o total, acompanhado de prazos e situações demonstrativas coerentes. |
| Acompanhamento por período | Gráfico com períodos escolares identificados e um indicador comparável. O exemplo deve explicitar que é demonstrativo. |
| Habilidades da turma | Matriz ou barras por habilidade, com categorias e valores fictícios. |
| Intervenções da turma | Composição visual com ações, situação, prazo e acompanhamento ilustrativos, conforme o planejamento do recurso. |

A agenda deve parecer uma agenda preenchida. Um gráfico de distribuição deve mostrar uma distribuição. Cada bloco precisa usar a representação adequada ao seu conteúdo.

Os modelos devem ter aparência próxima da versão final, com identificação clara de demonstração. Não tornar os gráficos ilegíveis com desfoque ou desativação visual excessiva.

### 5.2. Dados fictícios e dados reais

Crie conjuntos demonstrativos coerentes e reutilizáveis, separados dos dados operacionais.

Exija que:

- Totais, percentuais e distribuições sejam consistentes.
- Pessoas e turmas dos exemplos tenham nomes claramente fictícios.
- Dados demonstrativos não sejam gravados como registros reais.
- Exemplos não alimentem indicadores reais, relatórios operacionais, notificações ou decisões de negócio.
- Um mesmo gráfico não misture dados reais e fictícios.
- A identificação demonstrativa permaneça visível no próprio card.

Uma página pode conter componentes reais e demonstrativos, desde que a distinção seja clara em cada componente.

Os modelos podem ter interações visuais, como tooltip e seleção de séries. Ações que dependem de funcionalidades ainda inexistentes não podem aparentar que executaram uma operação real.

### 5.3. Filtros e estados

Não fazer um exemplo parecer um resultado real dos filtros aplicados.

Se um card demonstrativo não acompanhar o filtro global, sinalize que seu conteúdo é ilustrativo e não representa aquele recorte. Não usar nomes reais selecionados pelo usuário para atribuir resultados inventados.

Distinguir corretamente:

- Recurso futuro com demonstração.
- Recurso funcional com dados.
- Recurso funcional sem dados.
- Valor real igual a zero.
- Carregamento.
- Erro.
- Falta de permissão.

Não substituir automaticamente um erro, uma ausência de dados ou um zero verdadeiro por exemplos fictícios.

A ausência do módulo de agenda ou de outro recurso futuro não deve impedir a entrega do respectivo modelo visual.

## 6. Composição por perfil e filtros

Respeite as diferenças entre os perfis nos dois dashboards.

### 6.1. Direção, vicedireção, coordenação e pedagógico

Apresente os indicadores e análises permitidos no escopo institucional de cada perfil.

Disponibilize filtros por professor, disciplina e período conforme as permissões e os vínculos efetivos.

Não transformar acesso ao dashboard em autorização automática para modificar dados.

### 6.2. Professor autônomo e professor vinculado à escola

Apresente as turmas, disciplinas e informações autorizadas para aquele professor e contexto.

No dashboard da Turma:

- Mostrar o seletor de disciplina somente quando o professor tiver mais de uma disciplina autorizada naquela turma.
- Quando houver apenas uma, identificar a disciplina sem apresentar um seletor desnecessário.
- Permitir o recorte por período conforme os recursos disponíveis.
- Não expor dados de outros professores ou disciplinas fora de seu acesso.

Distinguir, quando necessário, o professor responsável por um vínculo do professor autor de uma avaliação. Não usar um filtro com um significado e apresentá-lo com outro.

### 6.3. Secretaria administrativa

Preserve uma experiência predominantemente operacional, com tabela, busca e ações pertinentes.

Podem existir cards administrativos úteis, como cadastros, matrículas, documentos, pendências e agenda administrativa, conforme o planejamento e os recursos disponíveis.

A secretaria não deve acessar dados de avaliações, notas, desempenho, rankings pedagógicos ou outros resultados protegidos.

Essa restrição também vale para o catálogo de modelos: não apresentar exemplos de análises pedagógicas como funcionalidades disponíveis para esse perfil.

Aplicar as permissões na obtenção dos dados e nas ações, não apenas ocultando elementos da interface.

### 6.4. Períodos escolares

Preparar a composição para a organização temporal configurada pela instituição: unidades, bimestres, trimestres, semestres, meses ou outras divisões previstas.

Não fixar todos os dashboards em um único tipo de período.

Se a estrutura necessária ainda não estiver implementada, preparar o componente e seu contrato, registrar a dependência e manter a distinção entre exemplos e filtros funcionais.

## 7. Reconciliação e atualização do planejamento

Antes de implementar, examine o estado atual do código e os planejamentos relacionados. Não presumir que um diagnóstico antigo representa a versão atual.

Para cada correção ou dependência, registrar:

- O que já existe e funciona.
- O que existe parcialmente.
- O que já está previsto em outro planejamento.
- O que precisa ser corrigido.
- O que constitui uma necessidade nova.

Quando houver planejamento anterior, referencie seus identificadores e atualize os itens pertinentes. Evite criar uma segunda iniciativa para o mesmo recurso.

Em especial, concilie as dependências de:

- Agenda.
- Períodos escolares.
- Notificações.
- Permissões.
- Indicadores e fontes de dados.
- Componentes de gráficos.
- Preferências pessoais e persistência do layout.

Atualize as fases, subfases e tarefas afetadas, preservando a organização em níveis e a rastreabilidade existente.

Inclua explicitamente as subfases necessárias para:

1. Corrigir a composição dos dois dashboards.
2. Inverter a preferência da relação de turmas para tabela.
3. Completar os modelos demonstrativos.
4. Implementar movimentação e troca de posição.
5. Implementar redimensionamento.
6. Persistir preferências pessoais.
7. Validar permissões, responsividade e acessibilidade.
8. Publicar e verificar em homologação.

Use o marcador **[necessario ver atender recurso]** nas dependências ainda não atendidas, indicando o item de planejamento correspondente.

Esse marcador pertence à documentação. A interface deve usar textos adequados ao usuário, como “Em breve” e “Dados demonstrativos”.

Separe os estados de entrega:

- Modelo visual concluído.
- Integração funcional pendente.
- Integração funcional concluída.
- Revisão concluída.
- Publicação em homologação verificada.

Um card demonstrativo aprovado não significa que o módulo correspondente foi implementado.

## 8. Execução com agentes e skills

Leia as instruções vigentes do projeto e use agentes especializados com as skills pertinentes.

Distribua as análises independentes quando isso ajudar a qualidade ou o andamento:

- Reconciliação do planejamento e arquitetura.
- UX/UI e composição dos dashboards.
- Dados, métricas e permissões.
- Implementação.
- Revisão visual e validação funcional.

Os agentes aplicam as skills e emitem pareceres fundamentados. Não registrar aprovação sem executar a revisão correspondente.

Consulte a revisão de UX/UI antes das mudanças visuais e faça uma revisão independente da implementação concluída. O agente que implementa não deve ser o único responsável por aprovar seu trabalho.

Reaproveite o sistema visual, os componentes e a solução de gráficos existentes no projeto. Verifique a infraestrutura disponível antes de introduzir outra dependência.

Na escolha ou configuração da grade personalizável, confirme que ela permite bloquear compactação, movimentação de vizinhos e reorganização automática. Arrastar e soltar funcionando, sozinho, não atende a este pedido.

Respeite as diretrizes existentes sobre área útil, temas, tabelas, JavaScript, acessibilidade e responsividade.

### Autonomia

Prossiga pelas subfases desbloqueadas desta correção sem depender de comandos como “pode continuar” ou “faça a próxima fase”.

Resolva decisões técnicas e visuais reversíveis com base no planejamento, nos padrões do projeto e na revisão dos agentes.

Solicite minha decisão apenas diante de uma escolha relevante de produto sem definição suficiente, alteração material de escopo, ação destrutiva, custo adicional ou bloqueio real de acesso ou autorização.

Quando precisar perguntar, apresente a decisão concreta, sua recomendação e os impactos. Continue os trabalhos independentes que não dependem dessa resposta.

A construção completa dos módulos futuros deve seguir seus planejamentos próprios. A correção dos dashboards não deve ficar esperando esses módulos quando o que precisa ser entregue agora é seu modelo visual.

## 9. Critérios de aceite

Verifique os dois dashboards, separadamente, em desktop e celular.

A entrega precisa demonstrar que:

1. O dashboard de Turmas possui indicadores e gráficos agregados com hierarquia visual clara.
2. O dashboard da Turma possui conteúdo específico daquela turma.
3. A relação de turmas abre em tabela quando não há preferência salva.
4. A alternância Tabela → Cards → Tabela preserva o estado pertinente.
5. Os cards “Em breve” exibem gráficos ou composições preenchidas com exemplos identificados.
6. Dados reais e demonstrativos permanecem separados.
7. Os gráficos se ajustam corretamente ao redimensionamento.
8. A troca entre dois cards altera somente a posição dos dois.
9. Uma tentativa de mover um card para outra seção é bloqueada.
10. Um redimensionamento sem espaço não empurra outros cards.
11. Filtros, atualizações e recarregamento não reorganizam o layout.
12. As preferências persistem e ficam isoladas entre usuários.
13. A adaptação para celular não destrói a configuração de desktop.
14. As permissões são respeitadas nos componentes, dados e ações.
15. Há alternativa acessível às interações de arrastar e redimensionar.

Execute as verificações pertinentes aos comportamentos alterados e registre evidências das telas e interações. A revisão deve avaliar a implementação efetiva, incluindo a apresentação visual, e não apenas a existência dos componentes no código.

## 10. Homologação e conclusão

A publicação em **homologação** das alterações deste escopo está autorizada após as revisões e validações necessárias.

Siga o fluxo existente do projeto para integração e publicação em:

**homologa.gabarito360.com.br**

Confirme que o fluxo utilizado não aciona uma publicação em produção.

Depois de publicar, verifique as duas páginas no ambiente de homologação, incluindo os gráficos demonstrativos, a tabela como padrão, a movimentação, o redimensionamento e a persistência das preferências.

Na entrega final, informe:

- O que foi corrigido no planejamento.
- O que foi refeito na implementação.
- Quais componentes usam dados reais.
- Quais permanecem como modelos demonstrativos.
- Quais dependências continuam pendentes e onde estão planejadas.
- Quais revisões e validações foram realizadas.
- Qual versão foi publicada.
- Os endereços e as evidências para conferência em homologação.

**Execute a correção do planejamento e da implementação até sua publicação e verificação em homologação, avançando autonomamente por tudo o que estiver autorizado e desbloqueado.**

## Adendos posteriores do responsável — 07/11/2026

**Decisão intermediária, superada pela retomada abaixo:** o responsável pediu retirar os ajustes e o salvamento dos cards. Essa variante não chegou a ser publicada. Permanecem retirados “Saiba mais sobre os cadastros” e botões “Ver os números”; preservar a descrição acessível dos gráficos sem esses controles visuais.

**Diretriz vigente — retomada com GridStack.js:** o responsável solicitou restabelecer ajuste e salvamento utilizando [GridStack.js](https://gridstackjs.com/#demo). A personalização é exclusiva de desktop e tablets grandes; celulares e telas pequenas têm composição responsiva fixa, sem consultas ou gravação de disposição que sobrescrevam o desktop. A dimensão física em polegadas não é verificável com precisão no navegador: corte inicial de área útil adotado em 1.024 × 700 pixels CSS, declarado como aproximação operacional.

**Comportamento vigente por linhas, em substituição à regra inicial de vizinhos imóveis:** redimensionar reorganiza e empurra os membros da mesma linha. Cada linha preenche as 12 colunas e a altura de todos os seus widgets acompanha o maior widget nela. Mover permite inserir na linha anterior/seguinte ou criar uma linha abaixo da última, sempre ocupada; uma linha sem membros é removida. Nenhum modal para gerenciar posição ou dimensões. Eventos nativos do GridStack e um adaptador de linhas preservam dados, permissões e IDs. A demanda individual de altura usa o campo técnico opcional `altura_desejada`; `h` registra a altura efetiva comum. O salvamento continua pessoal por usuário, contexto e tipo de painel. A alternância Tabela/Cards permanece local sobre os mesmos registros, sem reconstruir nem reordenar a coleção.

- Adotar [Calendar.js](https://calendarjs.com/) nos dashboards. Nesta correção, a suíte renderiza as três agendas demonstrativas em Timeline somente leitura; a integração de eventos reais continua em C2/C3/P5, com notificações em C4/N1–N3.
- Os gráficos devem ajustar-se à área útil do card, sem barra de rolagem horizontal ou vertical. Usar [Apache ECharts](https://echarts.apache.org/examples/en/), já existente no projeto. Tamanhos mínimos devem preservar a legibilidade; redimensionamento recalcula o desenho. Dados numéricos completos continuam acessíveis em alternativas para leitores de tela, sem botão ou diálogo “Ver os números”. Para muitas avaliações, navegação explícita entre grupos no gráfico mantém a ordem e não modifica filtros, fontes ou relatórios.

**Complementos finais do responsável:** a referência pessoal é distinta por turma quando o painel for individual e deve ser reutilizável pelos próximos dashboards. O perfil ativo fornece o padrão; uma disposição pessoal salva para o mesmo escopo tem prioridade. Não permitir adicionar, excluir ou duplicar widgets. O bloco de resumo/KPIs é fixo nos dois dashboards, com composição por perfil, fora do motor de edição; não significa fixá-lo à janela durante rolagem.

**Limpeza visual e foco confirmados posteriormente:** títulos dos widgets devem ser autoexplicativos e bastam junto aos dados, gráficos e listas; remover subtítulos, “Saiba mais” e parágrafos explicativos visíveis. Bases e critérios necessários ficam em tooltip/ARIA ou conteúdo para leitores de tela. Preservar estados de erro/vazio e os selos compactos “Em breve”/“Dados demonstrativos” para não confundir exemplos com dados reais. No agregado, resumo fixo → relação de turmas Tabela/Cards → análises/planejamento. No painel individual, resumo fixo → dois widgets independentes “Destaques da turma” e “Alunos que precisam de atenção” → gráficos e demais seções. Substituir o agrupador `alunos-recorte` sem modificar os dados, cálculos, permissões ou relatórios existentes. Gráficos mínima8/padrão9/máxima20; alunos mínima10/padrão12; agendas mínima12/padrão14. A aprovação antiga do c772 permanece histórica; a nova composição exige nova revisão visual independente antes de integrar/publicar.

**Nova decisão — larguras livres, posterior à publicação31286c3:** retirar a obrigação de preencher toda a linha e publicar a correção somente na homologação. A largura escolhida permanece quando a soma cabe nas12 colunas; widget único6 continua6 e dois widgets4+4 continuam4+4, inclusive após salvar/reabrir. Não distribuir automaticamente o espaço livre. Só ajustar irmãos quando há excesso/colisão, respeitando mínimos, prioridade do redimensionamento e quebra de linha. Mantêm-se altura comum da linha, resumo/KPIs fixos, seções, salvamento explícito e composição móvel sem personalização. Os padrões iniciais por perfil podem ocupar toda a linha; isso é uma composição inicial, não uma restrição sobre as escolhas pessoais. Não regravar preferências existentes, alterar contrato da API ou executar migration. Esta decisão substitui somente a obrigatoriedade anterior de largura total.
