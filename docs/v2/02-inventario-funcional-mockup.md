# Inventário Funcional Integral do Mockup

## Regra

Todos os arquivos abaixo são fonte de requisitos. Arquivos variantes não são
descartados: suas capacidades são consolidadas em uma única experiência V2.

| Módulo | Telas fonte | Capacidades obrigatórias |
|---|---|---|
| Acesso | `login.html` | entrar, manter conectado, esqueci senha, cadastrar/criar conta |
| Dashboards | `dashboard.html`, `dashboard-admin.html`, `dashboard-aluno.html`, `dashboard-coordenador.html`, `dashboard-diretor-escolar.html`, `dashboard-diretor-nucleo.html`, `dashboard-professor.html` | composições por ator, KPIs, gráficos, alertas, agenda, ações rápidas e navegação contextual |
| Escolas | `escolas.html`, `escola-detalhe.html` | pesquisar, cadastrar, editar, reativar, detalhe, abas, indicadores, turmas, provas, alunos e equipe |
| Equipe | `perfis-equipe.html`, `membro-cadastrar.html`, `membro-editar.html` | cargos, perfis, permissões, disciplinas, turmas, foto, dados profissionais, suspensão e remoção controlada |
| Turmas | `turmas.html`, `turma-detalhe-2.html` | busca, filtros, importação, cadastro, indicadores, alunos, provas, acompanhamento e relatórios |
| Alunos | `aluno-cadastrar.html`, `aluno-cadastrar-redesign.html`, `aluno-detalhe.html`, `aluno-editar.html` | foto, dados, matrícula, responsável, turma, status, histórico, evolução, resultados e ficha PDF |
| Provas | `provas.html`, `criar-prova.html`, `gabarito.html` | busca, filtros, rascunho, padrões, questões, respostas, publicação, acompanhamento, resultados e PDF |
| Correção | `acompanhar-correcao.html`, `acompanhar-correcao-turma.html` | progresso geral/por turma, leituras, pendências, ambiguidades, atualização e resolução |
| Resultados | `resultado.html`, `resultado-dinamico.html` | resultado individual, nota, respostas, desempenho, revisão e PDF |
| Relatórios | `relatorio-prova.html`, `relatorio-turma-prova.html` | KPIs, distribuição, questões, alunos, comparativos, detalhes e PDF |
| Conta | `perfil.html`, `configuracoes.html` | dados pessoais, foto, senha, notificações, sessões, aparência, idioma/região, acessibilidade, importação/exportação, plano/uso, integrações, privacidade e zona de perigo |

## Interações compartilhadas

- Tema claro/escuro persistido e tema claro inicial.
- Menu de usuário, breadcrumbs, tabs, filtros, busca e modais.
- Editor de gabarito, validação de formulários e confirmações.
- Gráficos de barras e donuts com alternativa acessível.
- Estados `default`, `hover`, `focus`, `active`, `disabled`, `loading`,
  `empty`, `error`, `success` e `access_denied`.

## Viewports obrigatórios

`360x800`, `390x844`, `430x932`, `600x960`, `820x1180`, `1024x768`,
`1366x768`, `1440x900` e `1920x1080`, sem rolagem horizontal indevida.

## Adaptações seguras sem perda funcional

- Criar conta pode usar convite, validação institucional e aprovação, mas deve existir.
- Remover/excluir deve usar inativação, anonimização ou solicitação LGPD.
- Integrações e plano/uso devem possuir estados reais de disponibilidade.
- Dados e indicadores estáticos do protótipo viram consultas reais.
