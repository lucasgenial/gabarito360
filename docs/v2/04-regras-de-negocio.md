# Regras de Negócio V2

| ID | Regra |
|---|---|
| V2-RN001 | Todo usuário atua por permissões explícitas e escopo global, núcleo, escola, turma ou próprio registro. |
| V2-RN002 | Cargo institucional e perfil de autorização são conceitos separados e auditáveis. |
| V2-RN003 | Criação de conta exige validação institucional, convite ou aprovação; o recurso continua disponível na experiência. |
| V2-RN004 | Exclusões sensíveis viram inativação, anonimização ou solicitação LGPD, preservando histórico obrigatório. |
| V2-RN005 | Uma matrícula ativa não pode duplicar o mesmo aluno na mesma turma/período. |
| V2-RN006 | Dados de responsável, CPF, foto e contato são coletados e exibidos somente quando necessários e autorizados. |
| V2-RN007 | Prova publicada congela configuração, questões, pesos, modelo de cartão e gabarito vigente. |
| V2-RN008 | Alteração posterior de gabarito exige nova versão, justificativa, autorização e recorreção rastreável. |
| V2-RN009 | Uma aplicação mantém snapshot de alunos previstos e aplicadores autorizados. |
| V2-RN010 | Cartão pode possuir código impresso externo e código do sistema; ambos são preservados separadamente. |
| V2-RN011 | Um aluno possui no máximo um resultado vigente por prova; tentativas e substituições permanecem históricas. |
| V2-RN012 | Leitura ambígua ou de baixa confiança exige revisão explícita antes da confirmação. |
| V2-RN013 | Correção manual exige motivo e auditoria com valor anterior e posterior. |
| V2-RN014 | Métricas de dashboard e relatórios derivam apenas de dados persistidos e vigentes. |
| V2-RN015 | Agenda, visita e reunião respeitam participantes, escopo e visibilidade definidos. |
| V2-RN016 | Integrações só podem indicar "conectado" após validação real e registro da credencial de forma segura. |
| V2-RN017 | Preferências de tema, densidade, idioma, região, acessibilidade e notificação são persistidas por usuário ou dispositivo. |
| V2-RN018 | Exportações e relatórios com dados pessoais exigem autorização, expiração e auditoria. |
| V2-RN019 | Eventos em tempo real não substituem o snapshot confiável obtido por API. |
| V2-RN020 | O OMR nunca confirma sozinho uma leitura que exige revisão. |
