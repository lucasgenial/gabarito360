Leia integralmente o planejamento anexado dos painéis de turmas e da turma do Gabarito360 e execute o trabalho previsto, com continuidade autônoma, revisão por agentes especializados e publicação das entregas aprovadas no ambiente de homologação.

O anexo organiza o trabalho nas fases P0–P8. Esta mensagem complementa o planejamento com as regras de execução, autonomia, revisão e publicação.

## 1. Objetivo e autorização para executar

Quero implementar a evolução das duas áreas:

1. Painel de turmas.
2. Painel de uma turma.

Priorize gráficos e cards nas composições dos professores e da gestão. Preserve a experiência administrativa da secretaria, com os resumos e as operações pertinentes ao perfil.

Execute as fases e subfases previstas, respeitando suas dependências. Não encerre o trabalho apenas com uma análise, uma proposta ou um novo planejamento quando já houver condições de implementar.

**Não preciso autorizar novamente o início de cada tarefa, subfase ou fase.** Ao concluir uma entrega e cumprir seus critérios, avance automaticamente para o próximo trabalho elegível.

Antes de executar, confira o estado real do projeto. O planejamento anexado utilizou uma versão específica do repositório como referência e pode haver trabalho posterior já realizado. Preserve essas evoluções.

## 2. Incorpore este protocolo ao planejamento

Durante a P0:

- Leia o AGENTS.md, as instruções aplicáveis, os planos vigentes e os documentos dos pacotes afetados.
- Confira a branch, as alterações locais, os trabalhos em andamento e as entregas já integradas.
- Acrescente ao acompanhamento do projeto uma seção de execução autônoma com as regras desta mensagem.
- Preserve os identificadores, as decisões e o histórico do planejamento anexado.
- Atualize somente o que precisar de conciliação com a situação atual.
- Não reinicie trabalhos concluídos nem substitua uma implementação recente por uma referência antiga.

Para cada necessidade ainda não atendida, verifique se existe planejamento anterior.

Classifique o tratamento como:

- Reaproveitar.
- Ampliar.
- Desdobrar.
- Substituir com histórico e justificativa.
- Criar novo item quando não houver planejamento adequado nas fontes examinadas.

Registre a referência anterior, o comportamento existente, a lacuna, o encaminhamento, as dependências e o critério de conclusão.

Não crie módulos ou listas de tarefas paralelas para necessidades já cobertas por planos existentes. Agenda, notificações, matrícula histórica, perfis, relatórios e demais frentes devem permanecer relacionadas aos respectivos pacotes.

A P0 deve produzir uma base suficiente para começar. Continue a reconciliação pontualmente durante a execução, sem transformar o diagnóstico em uma etapa indefinida.

## 3. Avanço autônomo e decisões delegadas

Resolva autonomamente as escolhas técnicas e os detalhes de implementação que estejam dentro do escopo, das regras e das decisões já aprovadas.

Isso inclui, conforme o projeto:

- Organização interna dos componentes.
- Reutilização de serviços e consultas.
- Divisão de uma entrega em incrementos coerentes.
- Ajustes de responsividade e acessibilidade.
- Correções de defeitos encontrados no escopo.
- Refinamentos de textos e interações compatíveis com os padrões existentes.
- Testes e evidências necessários.
- Atualização dos documentos afetados.

Não me pergunte “posso continuar?”, “posso iniciar a próxima fase?” ou equivalentes quando a próxima ação já estiver autorizada.

Atualizações de progresso são informativas e não devem virar pontos de parada.

Quando uma tarefa estiver bloqueada, registre o motivo e avance nas tarefas independentes que continuem autorizadas e possam ser executadas com segurança.

## 4. Utilize agentes especializados e skills pertinentes

Atue como coordenador da execução e delegue análises, implementação ou revisão quando isso melhorar a qualidade ou permitir trabalho paralelo.

Utilize as skills realmente disponíveis e pertinentes, lendo suas instruções. Não invente skills, ferramentas ou pareceres.

Organize as responsabilidades conforme a necessidade:

### Agente de UI/UX e Product Design

- Revisar previamente o fluxo e a composição quando exigido pelo projeto.
- Conferir hierarquia, cards, gráficos, filtros e adaptação ao celular.
- Preservar a identidade e os componentes aprovados.
- Revisar a interface efetivamente implementada e suas evidências atuais.
- Verificar estados de modelo, carregamento, vazio, parcial e erro.

### Agente de dados e indicadores

- Conferir cálculos, bases, denominadores e agregações.
- Distinguir estudantes, matrículas, participações, avaliações e cartões.
- Verificar os filtros de professor, disciplina e período.
- Conferir histórico e avaliações multidisciplinares.
- Impedir interpretações que os dados não sustentem.

### Agente de arquitetura e autorização

- Verificar escopo institucional e pessoal.
- Conferir permissões no servidor e nos consumidores.
- Revisar contratos da API e compatibilidade.
- Verificar relatórios, arquivos, notificações e demais pontos que possam expor dados.

### Agente de qualidade e integração

- Conferir os critérios de conclusão.
- Executar ou revisar os testes pertinentes.
- Verificar regressões e a integração entre componentes.
- Conferir o funcionamento da versão publicada em homologação.

Essas responsabilidades podem ser distribuídas entre os agentes disponíveis. Não é necessário criar um agente permanente para cada título.

**O agente que implementou uma alteração não deve ser o único responsável por aprová-la.** A revisão deve ser independente e produzir um parecer com evidências, pendências e conclusão.

Uma aprovação técnica não substitui uma revisão visual exigida, e uma aprovação visual não comprova, sozinha, segurança ou funcionamento.

Se faltar alguma capacidade de revisão, registre a limitação, utilize alternativas válidas quando existirem e avance no trabalho independente. Não declare uma aprovação que não ocorreu.

## 5. Preserve os cards de modelo

Esta é uma determinação do produto:

**Os componentes de recursos futuros devem ter sua entrega visual preparada, mesmo quando a funcionalidade ainda não estiver disponível.**

Cada card ou gráfico deve ter:

- Identificador.
- Título e finalidade.
- Perfil autorizado.
- Página, posição e tamanho.
- Comportamento no celular.
- Estado de modelo.
- Fonte e contrato de dados previstos.
- Dependência funcional.
- Critério de ativação.

Exemplos: agenda, períodos escolares, habilidades, intervenções e análises futuras.

Enquanto a dependência não estiver disponível:

- Exiba o selo “Em breve”.
- Mantenha a aparência final do componente.
- Use conteúdo neutro ou ilustração claramente identificada.
- Não apresente números, pessoas ou eventos fictícios como dados reais.
- Não faça chamadas repetidas a serviços inexistentes.
- Não mantenha skeleton ou carregamento infinito.
- Não simule cadastro, agendamento ou salvamento.
- Uma ação pode explicar o recurso futuro.

Quando a funcionalidade estiver pronta, conecte os dados ao mesmo componente, preservando sua identidade e organização.

Mantenha estados distintos para recurso não implementado, ausência de registros, zero válido, dados parciais, carregamento e erro.

Recursos que já funcionam devem continuar funcionais.

A secretaria recebe somente componentes administrativos autorizados, inclusive entre os modelos.

**Registre separadamente conclusão visual e conclusão funcional.** Um card visualmente pronto não conclui o módulo do qual depende.

## 6. Respeite o alcance do planejamento

Execute os módulos e integrações cuja implementação esteja explicitamente incluída no planejamento.

Quando um componente depender de um motor ou projeto externo ao pacote atual, prepare sua interface e seu contrato, relacione a dependência e mantenha o estado “Em breve” até a integração.

Não transforme a preparação de um card em construção implícita de todo o GII, de todo o motor TRI, do sistema financeiro ou de outro produto.

Ao mesmo tempo, não use a existência dessas dependências como justificativa para interromper os painéis, seus dados atuais ou as demais etapas independentes.

Preserve as regras de filtros e acesso:

- Professor escolar consulta suas turmas e disciplinas.
- Na turma individual, uma disciplina aparece como identificação fixa; mais de uma permite seleção.
- Gestão consulta professor, disciplina e período dentro do alcance autorizado.
- Autoria da avaliação, aplicação e responsabilidade docente permanecem conceitos distintos.
- Secretaria não recebe dados de avaliações, inclusive contadores, notificações, relatórios ou modelos pedagógicos.
- Espaço pessoal e espaços institucionais permanecem separados.

## 7. Revisão e aprovação de cada entrega

Para cada pacote:

1. Confirme o escopo e as dependências.
2. Faça a consulta especializada prévia quando aplicável.
3. Implemente.
4. Execute os testes e verificações obrigatórios do projeto.
5. Solicite revisão independente.
6. Corrija os problemas identificados.
7. Obtenha o parecer correspondente à versão corrigida.
8. Publique em homologação quando os critérios estiverem atendidos.
9. Confira o resultado publicado.
10. Atualize o planejamento e continue.

As aprovações técnicas e de UI/UX delegáveis podem ser concedidas pelos agentes especializados, conforme as regras do projeto e esta autorização.

Registre o escopo, a versão revisada, as evidências e o resultado do parecer. Alterações relevantes posteriores exigem nova revisão das partes afetadas.

Não reutilize aprovação de outra tela ou de uma versão anterior como aprovação automática do novo trabalho.

Não reduza verificações obrigatórias nem afrouxe permissões para obter aprovação. Uma reprovação deve gerar correção e nova conferência.

## 8. Autorização para Git e homologação

**Autorizo as operações necessárias para executar e integrar este escopo**, incluindo:

- Criar branches ou worktrees isolados.
- Fazer commits.
- Enviar as alterações.
- Criar e atualizar pull requests.
- Integrar ou fazer merge das alterações aprovadas, conforme o fluxo e as proteções do projeto.
- Executar as migrações de homologação previstas e validadas no pacote.
- Publicar os incrementos aprovados no ambiente isolado de homologação.

Use o fluxo existente que publique exclusivamente em homologação, tendo como referência o ambiente homologa.gabarito360.com.br. Confira a identidade e a configuração do destino antes de publicar.

**Não solicite uma nova autorização para cada publicação em homologação já abrangida por este pedido.**

A aprovação dos especialistas e o cumprimento dos critérios devem resultar na publicação do incremento. Não deixe uma entrega parada apenas porque terminou a implementação local.

Publique incrementos coerentes. Não espere todos os módulos futuros ficarem prontos para disponibilizar os painéis com os cards de modelo previstos.

Preserve os dados, os arquivos, as alterações de outras tarefas e a separação dos ambientes. Não use reset ou exclusão de dados reais como atalho de validação.

Esta autorização não inclui publicação, migração ou alteração operacional em produção. A integração autorizada também não inclui disparar uma publicação em produção.

## 9. Quando você deve me consultar

Consulte-me somente quando existir uma decisão real ainda não resolvida, como:

- Regra de produto ou pedagógica com impacto relevante.
- Mudança material de escopo.
- Alteração de permissões além do que já foi solicitado.
- Conflito entre requisitos que não possa ser resolvido pelas decisões vigentes.
- Contratação, custo externo ou ação fora das autorizações existentes.
- Operação destrutiva ou irreversível não prevista.
- Impedimento de acesso ou aprovação que realmente exija minha atuação.
- Promoção para produção.

Antes de perguntar, consulte o planejamento, as decisões anteriores e os especialistas pertinentes.

Apresente a questão de forma concreta:

1. Qual é a decisão.
2. Por que ela depende de mim.
3. Qual regra ou evidência originou a necessidade.
4. Qual é a recomendação.
5. Quais são as alternativas e seus impactos.
6. O que pode continuar enquanto aguarda.

Agrupe decisões relacionadas quando isso reduzir interrupções.

Não contorne controles de acesso, proteções de branches, verificações obrigatórias ou rejeições automáticas. Se uma rejeição impedir a ação e não existir alternativa válida, identifique a ação e o motivo informado.

## 10. Acompanhamento e continuidade

Mantenha o planejamento atualizado como referência de continuidade.

Para cada pacote, registre:

- Situação atual.
- Entrega visual.
- Entrega funcional.
- Testes e revisões.
- Commit ou PR.
- Situação da publicação em homologação.
- Resultado da conferência no ambiente.
- Pendências e dependências.
- Próximo trabalho elegível.

Envie atualizações curtas sobre entregas, achados e bloqueios. Essas mensagens não precisam de resposta quando não contiverem uma decisão reservada a mim.

Crie checkpoints suficientes para retomar o trabalho se a sessão for interrompida, preservando o que foi feito, a versão, os trabalhos pendentes e o próximo passo.

Não declare uma fase completa quando faltarem critérios de conclusão. Registre separadamente partes concluídas e partes bloqueadas.

**Comece pela P0, incorpore este protocolo, reconcilie o anexo com o estado atual e prossiga autonomamente pela execução. Após cada incremento aprovado, publique e confira em homologação, atualize o acompanhamento e avance para o próximo pacote elegível.**