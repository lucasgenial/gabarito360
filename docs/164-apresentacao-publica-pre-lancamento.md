# H1-P — apresentação pública de pré-lançamento

Replanejamento de05/11/2026 sobre a branch feat/cabecalhos-documentos (base7716868, PR191 aberta). O pedido posterior amplia a apresentação sem reverter a marca, o degradê, os cabeçalhos e a exportação já aprovados. A base passou nos cinco grupos de CI; a reforma exige testes e revisão próprios. Nenhum merge ou deploy realizado nesta revisão inicial.

## Objetivo e limites

Apresentar o valor atual e a evolução do Gabarito360 a professores e gestores. Narrativa: preparação da avaliação, aplicação, leitura/correção, compreensão dos resultados e decisão pedagógica. Modalidades: Professor autônomo, Escolas, Núcleos e redes, Secretarias. Nenhum preço, desconto, periodicidade, limite comercial, compra, gratuidade ou distribuição de futuros por plano inventada. Catálogo financeiro, contratos, permissões e banco permanecem intactos.

Todos os recursos indisponíveis recebem selo literal **Em breve**, junto de cada ocorrência. Não usar publicamente os rótulos Previsto no planejamento, Planejado, Roadmap ou Em desenvolvimento. A antiga listagem genérica e o texto de indefinição dos planos foram substituídos pela decisão mais recente. A contratação ainda não está aberta; o próximo passo real do visitante é conhecer a apresentação ou conversar com a equipe.

Sete frentes futuras: elaboração de provas/versões; acervo inteligente GII com início pelo ENEM; marcações na própria página da avaliação; formatos impressos/digitais/mistos e questões abertas; análises pedagógicas/psicométricas TCT/TRI; integridade e padrões de respostas com revisão humana; adesão de escolas autônomas conforme EI1. Apresentar benefícios, sem implementar ou arquitetar esses módulos, prometer resultados validados ou datas.

## Fluxo e composição consultados antes da edição

Preservar hero com degradê #1D4F9E→#001534, marca oficial e superfícies atuais. Evoluir em rotina/fluxo, recursos por momento da avaliação, demonstrações fiéis com dados fictícios, confiança/controle, modalidades, FAQ e contato. Alternar composição, sem transformar tudo em cards repetidos. Conteúdo essencial visível em HTML; ajudas longas e FAQ em disclosures acessíveis. Menu móvel funciona semJavaScript; melhoria progressiva pode fechar ao selecionar destino/Escape. Âncoras respeitam cabeçalho fixo. Hierarquia h2/h3 adequada a home e /planos.

Remover a seção de estatísticas de estudos externos desta narrativa, substituindo-a por demonstrações do fluxo real. Nenhum número de produtividade, cliente, depoimento, certificação ou logo institucional sem evidência. Demonstrações fictícias não usam dados, notas ou imagens reais de alunos.

## Disciplinas e revisão

Skills disponíveis aplicadas: frontend-design, design:ux-copy e Product Design (contexto/audit e revisão). O catálogo da sessão não oferece skills intituladas marketing de produto, copywriting, conversão ou SEO; essas disciplinas são executadas pelo agente de marketing/redação e pela revisão de metadados, sem alegar ferramenta inexistente. Implementador consulta UI/UX antes de editar e não aprova o próprio trabalho. Revisor independente recebe capturas atuais desktop/celular/intermediária e recibos de interação; screenshots não certificam acessibilidade completa.

| Subdivisão interna | Entrega | Situação inicial |
|---|---|---|
| P1 | Matriz de evidências e narrativa de produto | Concluída: matriz/copy registradas |
| P2 | Composição prévia e copy | Consulta UI/UX e redação concluídas |
| P3 | Home, modalidades, demonstrações, navegação e metadados | Implementada; revisão visual pendente |
| P4 | Contrato estático/whitelist, integridade e testes | Contrato adaptado; ensaio final pendente |
| P5 | Revisão independente, CI, merge e homologação | Pendente da versão final |

## Publicação

Reutilizar o exportador Blade e os bundles imutáveis; somente CSS/JS públicos nominados entram na whitelist. Home e assets devem ser servidos sem PHP/API/sessão/banco. Falha preserva a última versão válida. Manter BasicAuth de homologação, backup SQL/arquivos/configuração, conferência de integridade e reversão. H1 não tem migration. Produção depende de decisão explícita posterior.

## Matriz resumida de evidências e disponibilidade

| Recurso | Evidência | Estado comunicado | Selo |
|---|---|---|---|
| Gabaritos/cartões para imprimir | GeradorCartoes, gerar_cartoes, CartoesFilaTest/Espacos | Atual, com cartão gerado pelo sistema | Não |
| Câmera/foto e revisão humana | leitor-cartao, QualidadeCartao, QuadroLeituras, testes de fila/revisão | Atual; imagem precisa adequada e professor confere | Não |
| Resultados e percentuais por questão | ResultadoController/pedagogico, ResultadosLoteTest, fechamentoOMR3homo45f9248 | Atual para educadores autorizados; agregado da mesma prova/espaço | Não |
| PDFs aluno/professor/lote | gerar_resultado, testesPDF/WEB, validaçãoOMR3 | Atual; H1 melhora cabeçalho/identidade sem mudar notas | Não |
| Histórico e intervenção | QuadroLeituras e teste de histórico; fechamentoOMR3 | Atual nos registros preservados; seção com mais de uma leitura | Não |
| Documento verificável | EmissaoDocumento/ValidacaoDocumentoController e testes; homologaçãoOMR3 | Registro de emissão/integridade, sem certificado pessoal; não retroativo | Não |
| Contextos e permissões | ProvasAutoriaTest/Pf1PerfisEscolaresTest/PerfisEspacoTest e escopos | Atual, sem acesso irrestrito por modalidade comercial | Não |
| Editor/versões | Pedido do responsável; editor não implementado | Futuro sem data ou equivalência validada | Em breve |
| Acervo/GII | Pedido do responsável; motor não implementado | Futuro, início pelo ENEM; sem vínculo oficial/importação anunciada | Em breve |
| Marcações na página da prova | Pedido do responsável | Futuro, sem leitura universal | Em breve |
| Digital/misto/questões abertas | Pedido do responsável | Futuro; assistência e conferência do professor | Em breve |
| TCT/TRI | Pedido do responsável | Futuro; sem calibração/nota oficial prometida | Em breve |
| Padrões de respostas/integridade | Pedido do responsável | Futuro; apoio à revisão humana, sem determinar fraude/culpado | Em breve |
| Adesão escolaautônoma | Doc163 e inventárioEI1; núcleo ainda obrigatório | Futuro, separado do professorautônomo e sem adesão já liberada | Em breve |

Marketing/redação conferiram esta classificação no brief externo publico-marketing-brief.md. Código/testes, validação na homologação e liberação comercial foram tratados separadamente. A base7716868 obteve sucesso nos cinco grupos da CI42084488242; esse resultado não aprova a reforma posterior. Pendências comerciais: condições de lançamento e distribuição futura de recursos por modalidade, sem impedir esta apresentação e sem inventar direitos de acesso.
