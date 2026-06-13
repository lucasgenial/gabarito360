# Requisitos da V2

## Requisitos funcionais

| ID | Requisito |
|---|---|
| V2-RF001 | Permitir login, logout, sessão persistente, recuperação de senha e criação/onboarding de conta. |
| V2-RF002 | Selecionar dashboard, navegação, dados e ações conforme ator, permissões e contexto ativo. |
| V2-RF003 | Gerenciar núcleos, escolas, equipe, cargos, perfis, permissões, lotações e acessos. |
| V2-RF004 | Gerenciar turmas, matrículas, alunos, responsáveis, fotos, histórico e importações. |
| V2-RF005 | Criar provas e padrões, editar questões/gabaritos, salvar rascunho, publicar e exportar cartão/gabarito. |
| V2-RF006 | Planejar e executar aplicações por turma, com aplicadores, presença, progresso e encerramento. |
| V2-RF007 | Capturar cartão pelo Android, identificar aluno/cartão, processar OMR, revisar e confirmar. |
| V2-RF008 | Acompanhar correções gerais e por turma, resolver ambiguidades e visualizar atividade recente. |
| V2-RF009 | Calcular e apresentar resultados por aluno, prova, turma, escola e núcleo. |
| V2-RF010 | Gerar relatórios em tela e exportações PDF, CSV e XLSX quando apresentadas pelo produto. |
| V2-RF011 | Manter perfil, senha, notificações, sessões, aparência, idioma/região e acessibilidade. |
| V2-RF012 | Disponibilizar importação/exportação, integrações, plano/uso, privacidade e solicitações LGPD. |
| V2-RF013 | Registrar agenda, reuniões, visitas e eventos usados nos dashboards. |
| V2-RF014 | Atualizar progresso e indicadores em tempo real, com fallback por consulta. |
| V2-RF015 | Auditar operações sensíveis e manter histórico de mudanças e reprocessamentos. |

## Requisitos não funcionais

| ID | Requisito |
|---|---|
| V2-RNF001 | Reproduzir o mockup com validação visual nos nove viewports oficiais. |
| V2-RNF002 | Atender WCAG 2.2 AA, navegação por teclado, foco visível e alternativas textuais/tabulares. |
| V2-RNF003 | Usar tema claro por padrão e tema escuro opcional persistido. |
| V2-RNF004 | Restringir toda consulta e ação por autenticação, permissão e escopo. |
| V2-RNF005 | Proteger dados pessoais conforme LGPD, com minimização, retenção e auditoria. |
| V2-RNF006 | Manter contratos API versionados, erros estáveis e operações críticas idempotentes. |
| V2-RNF007 | Usar MariaDB, Redis, filas e storage privado sem dependências de PostgreSQL. |
| V2-RNF008 | Garantir backup/restauração, observabilidade, CI e deploy reproduzível. |
| V2-RNF009 | Medir precisão OMR com dataset real separado de calibração e teste. |
| V2-RNF010 | Não apresentar placeholders ou métricas estáticas como dados reais. |

## Prioridade

Todas as capacidades presentes no mockup pertencem ao produto V2. A ordem de
execução pode variar por dependência, mas "não implementado ainda" deve aparecer
como estado real e rastreável, nunca como remoção silenciosa do escopo.
