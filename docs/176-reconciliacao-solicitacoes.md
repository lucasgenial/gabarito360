# 176 - Reconciliação das solicitações do produto

07/11/2026. Solicitada após o responsável identificar pedidos ausentes na homologação e mensagens que deixaram de aparecer na conversa. Este registro preserva os requisitos recuperáveis; não afirma recuperar mensagens apagadas da interface. A auditoria fixa main a80a7e3 (árvore igual à release a67b2fb da homologação). Produção permanece fora do escopo.

## Critério de fechamento

Código presente, testes/CI aprovados, publicação e conferência no ponto de uso são evidências diferentes. Um endpoint ou modal reutilizável não conclui todos os formulários pedidos. Aprovação visual local não é conferência autenticada do servidor. O usuário informou que não pode entrar agora; essa conferência permanece pendente, sem pedir senha nem bloquear tarefas independentes.

Auditorias independentes versionadas: [UI/UX](evidencias/reconciliacao-ui-ux.md) e [negócio/segurança](evidencias/reconciliacao-negocio-seguranca.md). Registram caminhos, regras, testes e limites. Documentos antigos são históricos: seu estado inicial não substitui evidências posteriores nem autoriza chamar uma proposta de implementação.

## Lacunas confirmadas

| ID | Pedido | Estado no baseline publicado | Encaminhamento |
|---|---|---|---|
| IMG-E | Escolher brasão/foto no formulário Editar turma | Ausente, embora exista no detalhe | Correção atual: imagem no mesmo card, modal e salvamento separados; não perder campos digitados |
| IMG-N | Escolher imagem ao cadastrar nova turma | Ausente; cadastro ainda em modal com campos básicos | Implementar fluxo de criação com escolha pendente, sem gravar imagem antes de existir turma autorizada |
| IMG-L | Mostrar imagem persistida na lista de turmas | Parcial: lista ainda mostra letras | Integrar avatar-turma à lista, com foto privada, fallback e ações |
| IMG-P | Avatar/foto/câmera no cadastro de professor pela gestão | Ausente nesse formulário; editor próprio existe | Reusar identidade da conta; não sobrescrever foto global de conta existente por criar vínculo escolar |
| TU1-PDF | Relatório coerente com o painel novo | Parcial: ainda questões, sem listas nominais | Seis KPIs, três análises, Atenção/critério/Top 5, todas as linhas autorizadas, bases e empates; retrato/paisagem |
| UX-D | Retirar confirmações e alertas nativos de JavaScript em toda aplicação | Arquivamento corrigido; há usos legados em correção/câmera/revisão e outros fluxos | Inventariar e substituir por modal/toast contextual, sem reenviar operações |
| UX-T | Padronizar todas as tabelas, cards móveis, ações e área útil | Padrão compartilhado implementado; cobertura integral não demonstrada | Revisar famílias restantes, inclusive formulários legados com composição lateral |
| UI1-R | Carregamento por região/card em toda aplicação | Casca/skeleton e imagens progressivas presentes; não há migração integral de todo SSR por card | Inventariar regiões ainda monolíticas; preservar fluidez existente |
| AP1-L | Isolar limites das operações da API | Inventário de 93 rotas; correção ainda pendente | Preservar contrato, valores/janelas, autorização e isolamento de consumidores |
| EI1 | Escola autônoma, adesão pública/privada, cadastro/edição e rede declarada | Planejamento, sem fluxo operacional completo | Executar EI1-A a G: associação, contrato e acesso independentes; suporte cadastra redes e exceções públicas |
| FIN | Assunção financeira pela rede, planos e ausência de devolução | Regras registradas, módulo não entregue | Planejar/implementar contratação e transição auditável sem compartilhar dados só por associação institucional |
| CP2 | Coordenação entrega cópia da prova para outro professor | Não implementado; CP1 atende cópia própria | Nova cópia sem turmas/cartões/resultados, destinatário elegível e auditoria |
| ID1 | Identidade única conciliada por CPF | Fundação/HMAC/provisórios presentes; operação completa pendente | Consulta mínima, conciliação auditada e ativação compatível com históricos |
| VI1 | CPF primeiro, convite para conta existente e início futuro | Não entregue: convite ainda parte do e-mail e inicia agora | Implementar busca protegida, formulário condicionado, aceite/recusa e vínculo agendado |
| MT1 | Matrícula/enturmação por períodos | Parcial: transferência intraescola e coorte histórica já existem | Completar intervalos de matrícula, sem confundir arquivamento cadastral com encerramento de matrícula |
| TE1 | Cessão de histórico entre escolas | Não entregue | Origem/destino, aprovação, corte temporal e preservação dos históricos |
| TR1/UX1 | Reposicionar Cópias como Transferências e revisar poderes/fluxo | Motor anterior existe; nova política e interface incompletas | Acompanhamento primeiro, Transferir/Receber, delegação, notificações e auditoria |
| PA1 | Continuidade do professor independente e conciliação com escola | Parcial | Cadastro mínimo/CPF, integração autorizada e limites após encerramento escolar |
| L4/T6 | Retenção, preservação e encerramento sobre os novos modelos | Parcial | Conciliar L4-D/E com identidade, matrícula e transferências; não declarar L4 inteiro concluído |
| REL/UR2-P/UR1-P | Publicar versão final e adaptações isoladas em produção | Dependem de decisão explícita | Somente homologação nesta execução; preservar gabaritos |

## Entregas presentes que não devem ser confundidas com as lacunas

- TU1: abas, retorno Alunos, seleção disciplina/professor, exclusividade temporal, edição em página, transferência intraescola com confirmação e arquivamento auditado estão no código. R3 adiciona seis KPIs, três gráficos e grupos de alunos. A conferência autenticada atual continua pendente.
- CP1: duplicar prova própria, sem vínculos da original, com autoria/proveniência. Não atende CP2.
- PF1: cargos, incompatibilidade simultânea entre grupos na mesma instituição e exceção de suporte; outra instituição e vínculo anterior encerrado são casos distintos. Editor de imagem da conta e do aluno existe. Não atende sozinho IMG-P.
- DC1: disciplinas, grandes áreas N:N e períodos de vinculação possuem implementação/evidências posteriores. Não tratar o planejamento inicial como ausência de toda a entrega.
- TU-IMG: 36 educadores adultos e 36 brasões; imagem no detalhe, fotografia privada, recorte/rotação/conversão/compactação. PR208/CI/publicação existem. Os quatro pontos de uso IMG-E/N/L/P acima continuam distintos.
- UR4: navegação Web por referências em corpo de requisição, vínculo por sessão, revisão de abas e rejeição de credenciais na URL. Não confundir referências de navegação com credenciais de autenticação nem mudar o contrato REST da API para uma preferência de URL do cliente.
- UI1/TBL/breadcrumb/toast: componentes e motor compartilhados existem. Não provar cobertura de todas as telas por uma única captura.
- OMR/documentos: qualidade da imagem, leituras preservadas, intervenção humana, PDF.js, relatórios distintos e emissão verificável têm implementação. A auditoria atual não repetiu todos esses fluxos com dados reais nem presume que documentos antigos foram retroativamente autenticados.
- Página pública, identidade, favicon, redes sociais e metadados de compartilhamento possuem fontes e entregas anteriores. A página pública é uma publicação independente da aplicação; conferir recibo próprio ao alterá-la.

## Sequência revisada

1. Fechar IMG-E com teste de campos preservados, revisão visual, CI, merge e homologação, sem migration.
2. Fechar IMG-N, IMG-L e IMG-P com planejamento e revisão específicos; não chamar a galeria isolada de integração completa.
3. Retomar TU1-PDF, preservado em worktree separado e ainda não integrado/publicado.
4. Executar AP1-L e correções transversais UX-D/T/R sem apagar as pendências de negócio da tabela.
5. Prosseguir com EI1 e demais dependências registradas no plano integrado115 e no quadro completo de pendências.

O futuro editor de provas/enunciados e o GII não são implementação desta etapa. Enunciados e questões não serão misturados ao gabarito atual. Nenhum planejamento novo do GII foi iniciado.

## Atualização posterior — painéis P0–P8 em07/11/2026

IMG-E foi integrado/publicado no PR209; a tabela acima permanece como retrato do baseline anterior, não como ausência atual. [Plano177](177-plano-paineis-turmas.md), [protocolo178](178-protocolo-execucao-paineis.md) e [execução179](179-execucao-paineis-turmas.md) são a continuidade autorizada dos painéis. TU1-PDF corresponde a P8.1, IMG-L a P3.1.3 e UI1-R/AP1-L conservam os itens próprios. As pendências fora do painel permanecem registradas; não criar lista paralela para C/N/MT1. Os incrementos aprovados terão publicação somente na homologação, sem nova autorização interna.
