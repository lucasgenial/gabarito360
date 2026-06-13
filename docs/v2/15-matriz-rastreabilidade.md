# Matriz de Rastreabilidade V2

## Estado inicial

| Grupo de telas | Dados/backend V1 | Web V1 | Situação V2 |
|---|---|---|---|
| Login/conta | parcial | parcial | reconstruir incluindo onboarding, recuperação e sessões |
| Dashboards por ator | parcial | reduzido | reconstruir todos, inclusive aluno |
| Escolas/equipe/perfis | amplo | parcial | completar recursos e paridade |
| Turmas/alunos | amplo | parcial | completar histórico, importação, ficha e paridade |
| Provas/gabaritos | amplo | parcial | completar padrões, editor, publicação e exportações |
| Correção/aplicações | amplo | parcial | completar visual, ambiguidades e tempo real |
| Resultados/relatórios | parcial/amplo | parcial | completar PDFs, comparativos e exportações |
| Configurações/LGPD/integrações | parcial | reduzido | implementar integralmente |
| Android | contratos básicos | não aplicável | concluir jornadas, câmera e sincronização |
| OMR | contrato pré-homologação | não aplicável | implementar e homologar |

## Registro por entrega

Cada passo do plano deve acrescentar linhas com:

| Campo | Obrigatório |
|---|---|
| Tela/controle fonte | arquivo e elemento do mockup |
| Rota/superfície | rota web, endpoint ou tela Flutter |
| Fonte de dados | tabela, consulta, serviço ou evento |
| Regra/permissão | regra e policy aplicável |
| Estados | loading, vazio, erro, sucesso e específicos |
| Testes | funcional, autorização, acessibilidade e visual |
| Evidência | screenshot, relatório ou execução |

## Regra de encerramento

Nenhuma tela pode ser marcada como entregue apenas por existir uma rota. Todos
os controles visíveis e recursos associados devem estar funcionais ou possuir
estado explícito de indisponibilidade aprovado e temporário.
