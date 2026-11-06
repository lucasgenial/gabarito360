# Orientações do projeto

## JavaScript da aplicação

Decisão explícita do responsável em 06/11/2026: manter o JavaScript executável em arquivos externos bem definidos, com funções e responsabilidades claras. Não adicionar lógica executável em `<script>` inline nem atributos de evento como `onclick` e `onsubmit` nas views. Transmitir apenas dados de configuração por atributos `data-*` ou blocos não executáveis `application/json`, com o escape apropriado; conectar os eventos nos arquivos JavaScript. Ao alterar uma tela com lógica embutida, migrar as interações afetadas e preservar o comportamento, a validação, o CSRF e a acessibilidade.

## UI, UX e Product Design

Regra solicitada pelo responsável em03/11/2026: toda alteração que afete interface, experiência, navegação, formulários ou textos de interação exige aprovação explícita do usuário **ou de um agente especializado em UI/UX e Product Design** antes de ser considerada concluída ou integrada.

- Utilizar as skills disponíveis de Product Design, design critique e frontend-design, quando pertinentes. Consultar as instruções efetivas de cada skill; a ausência de uma skill não dispensa a revisão.
- Decisão posterior do responsável: antes de executar qualquer tarefa de design, consultar o agente especializado em UI/UX e Product Design para definir o fluxo e a composição. A consulta prévia não substitui a revisão visual final.
- Antes de implementar ou refazer uma interface, revisar o objetivo do usuário, o fluxo e os padrões já existentes no produto. Preservar identidade visual, componentes, acessibilidade e linguagem do Gabarito360.
- O agente implementador não aprova a própria interface. Delegar a revisão a um agente especializado, autorizado por esta regra, ou apresentar uma proposta concreta ao usuário. Não pedir que o usuário autorize cada subdivisão técnica.
- A revisão final exige evidência visual atual no navegador, com desktop e celular, e conferência das ações principais e estados de erro. Screenshots sozinhas não comprovam acessibilidade completa. Registrar o parecer, as pendências resolvidas e a aprovação; alterações visuais posteriores devem ser revistas.
- Se a revisão reprovar, corrigir e reenviar. Não apresentar testes de código como aprovação de design nem reutilizar uma aprovação de outra tela ou versão. A aprovação da conferência CI1-D não aprova o novo formulário CI1-E.
- A aprovação do especialista é de qualidade de design. Não substitui autorizações do usuário para publicação, acesso sensível ou outras ações que dependam delas.

## Área útil e composição das telas

Decisão explícita do responsável em03/11/2026: as telas da aplicação devem ocupar toda a área útil do viewport, em largura e altura, descontando menu, cabeçalho, rodapé e navegação móvel. Usar o layout compartilhado e superfícies de trabalho com largura100%; não introduzir max-width fixo em painéis, formulários principais ou listas que deixe uma faixa vazia na tela ampla. Margens responsivas e limites de leitura de parágrafos são permitidos; controles, diálogos e imagens mantêm limites próprios quando necessários. Grupos de trabalho seguem empilhados, com campos responsivos dentro deles. Validar desktop amplo, menu recolhido e celular sem overflow nem ações encobertas por rodapé. Toda mudança visual continua sujeita à aprovação independente acima.

## Tabelas, cards de registros e avisos

Padrão aprovado pelo responsável em 04/11/2026: tabelas operacionais usam `x-tabela`, integrado à área interna do card principal, com alinhamento comum ao título, filtros e formulário. Não criar um card separado para a tabela nem deixar seu conteúdo full-bleed. Usar `interna=true` somente quando o pai não fornece padding; em card-pad, gráficos, details e diálogos, evitar inset duplicado. A composição compartilhada usa 24px no desktop e 16px no celular onde o próprio componente fornece o inset.

Listas podem virar cards no celular, preservando título, dados secundários, contexto e todas as ações autorizadas. O card principal continua visível; não anular sua superfície na adaptação móvel. Duas colunas Nome/dados e Ações são a escolha explícita do histórico de vínculos de áreas; não impor essa estrutura a matrizes comparativas, seleção em lote ou relatórios que precisem de outras colunas. Preservar paginação/seleção e nunca ativar controles de coleção completa sobre uma página parcial. Manter impressão/PDF sem padding adicional que prejudique A4.

Avisos compartilhados de sucesso, erro, atenção e informação têm separação antes e depois (`margin-block:16px`); nos containers explícitos com gap, deixar o próprio gap separar os elementos. Preservar cores semânticas e roles status/alert. Revisar o componente compartilhado antes de criar exceções locais. Alterações continuam exigindo consulta prévia e aprovação visual independente.

## Continuidade e publicação

Revisar o planejamento antes de implementar cada etapa e apresentar a tabela atualizada ao finalizar. Prosseguir autonomamente pelas subdivisões internas autorizadas. Usar terminal para Git/GitHub e preferir o Chrome do usuário para testes visuais do aplicativo.

Decisão atualizada pelo responsável em03/11/2026: toda entrega deve ser publicada primeiro na homologação isolada `homologa.gabarito360.com.br`, com banco, arquivos, filas e credenciais de serviços próprios. Posteriormente o responsável autorizou expressamente copiar os usuários e dados atuais de produção para homologação, mantendo suas senhas de acesso. A cópia é pontual, protegida e independente: não compartilhar o banco operacional, não enviar e-mails ou pagamentos reais, não reaproveitar sessões, tokens de acesso ou trabalhos pendentes. Preservar a origem e validar a cópia antes da troca; manter a proteção adicional da homologação. Dados criptografados exigem material de leitura protegido, sem copiar indiscriminadamente o `.env` de produção.

O ambiente atual `gabarito360.com.br` é produção. Sua publicação depende de decisão explícita do responsável no momento combinado, mesmo após CI, revisão do especialista e validação na homologação. Concluir L4 ou obter parecer de design não autoriza promoção automática. Preservar os gabaritos e os demais dados existentes. Não incluir arquivos ou alterações preexistentes de outras tarefas nos commits desta entrega.
