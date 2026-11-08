# Organização de seções e cards centrais do painel

## Planejamento revisado antes de implementar

Solicitação: no painel individual, Resultados por período escolar e Agenda da turma pertencem por padrão ao card Resultados da turma, logo após Estudantes. Permitir mover uma seção com seu título e todos os widgets, além de mover os cards centrais completos. Consulta prévia independente de UI/UX/Product Design registrada no diretório operacional em `paineis-secoes-cards-design-previo.md`.

| Etapa | Entrega | Critério |
| --- | --- | --- |
| Organização inicial | Resultados da turma: Estudantes → Período e agenda → Análises da turma | Widgets existentes, IDs, fontes e selos demonstrativos preservados |
| Preferências compatíveis | Ordenação de seção e card no mesmo salvamento versionado da geometria | Novos metadados opcionais em JSON existente, sem DDL; clientes anteriores continuam válidos |
| Organização por usuário | Mover seção inteira dentro do mesmo card e cards centrais entre si | Handles distintos, teclado/setas, arrastar, Salvar/Cancelar/Restaurar padrão |
| Proteções | KPIs fixos, sem excluir/duplicar/adicionar widgets ou mover seções entre cards | Permissões, contexto, pins e coordenadas relativas preservados |
| Validação | Testes de contrato/isolamento/concorrência, gestos e responsividade | Edição somente ≥1024×700; disposição salva não se perde ao entrar no celular |
| Publicação | Revisão independente, CI, backup e somente homologação | Produção e dados de gabaritos preservados |

Separar esta entrega da correção transversal das tabelas, PR225. Compatibilidade: manter URLs e campos obrigatórios da API; admitir `ordem_card` e `ordem_secao` opcionais por widget, como os metadados visuais já existentes. Persistir na mesma transação e no mesmo evento de preferências. Validar inteiros, consistência dos grupos e catálogo autorizado. Não aceitar nomes de classes, URLs ou grupos livres do cliente.

As seções `periodos-escolares`/`agenda-pedagogica` deixam o grupo antigo Planejamento e entram em `periodo-agenda`. Ler preferências/eventos anteriores por projeção compatível, sem apagar registros nem editar o histórico. Aceitar corpos anteriores com o grupo original desses dois IDs e manter o hash de idempotência do pedido original. Clientes que não enviam os novos metadados preservam a ordenação já salva quando ajustam geometria; Restaurar padrão continua explícito.

## Implementação e verificação

Handles no título movem seção ou card inteiros, sem modal. Setas para cima/baixo e ponteiro usam a mesma ordenação; toque fica limitado à área do handle. Escape/pointercancel/blur/resize cancelam o gesto; o salvamento não ocorre durante um gesto. Mover Analises inclui o wrapper com os dados/contexto do ECharts, preservando instâncias e hooks. Não recriar os nós nem alterar a geometria do grid interno ao reordenar grupos.

O cliente novo sempre envia ranks explícitos no salvamento, inclusive após Restaurar padrão. O BFF valida pares, tipos, consistência e unicidade da mesma maneira que a API; resposta upstream inválida vira502, não uma organização ambígua. A fixture de auditoria do navegador é confrontada com o catálogo/padrão real da API no teste de contrato. A edição não aparece no celular; mudanças de viewport não gravam nem apagam a disposição desktop.

Validação local inicial:395 testes Web/14.874 assertions;51 testes API de painéis/959 assertions; auditoria do navegador cobre ponteiro/teclado, cards/seções, pin, salvar/reload, cancelar, restaurar/ranks explícitos, mobile e409 sem reenvio automático. Conferência no Chrome local usa dados fictícios e preferências em memória, sem tocar nos usuários/gabaritos da homologação. Revisão independente/CI/publicação ainda são gates próprios.

## Compatibilidade e reversão

Não há alteração de schema, rotas, autenticação, contexto nem campos obrigatórios. Corpos anteriores continuam aceitos; registros/eventos originais são preservados. Os novos campos são opcionais e estritamente visuais. Isso não significa que um binário anterior conheça a composição nova: após salvar `periodo-agenda`, um leitor antigo com catálogo antigo recusa essas preferências, embora os dados da turma e relatórios continuem acessíveis com a apresentação padrão.

Por isso, rollback é de código/configuração/marcadores, sem restaurar SQL antigo ou editar eventos. Se necessário depois de tráfego, a organização personalizada pode ficar temporariamente indisponível no binário antigo; preservar os registros para correção adiante e validar os percursos de dados. Não declarar rollback completo da personalização entre essas versões. Antes da publicação, ensaiar leitura antiga do novo registro e a recuperação pelo leitor novo, registrando o limite explicitamente. Produção permanece fora do escopo.
