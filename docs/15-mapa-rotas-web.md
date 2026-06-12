# Mapa Canonico de Rotas Web

## 1. Finalidade

Este documento fecha o contrato das telas web identificadas em `style-system/`.
As rotas sao planejadas para implementacao posterior. A R1 nao cria frontend,
endpoints ou permissoes no codigo.

## 2. Convencoes

- Prefixo web autenticado: sem prefixo adicional.
- `/painel` resolve a composicao pelo contexto ativo e pelas permissoes.
- Toda consulta respeita escopo global, nucleo, escola ou vinculo operacional.
- Permissoes marcadas como **planejadas** ainda serao adicionadas no codigo.
- Estado comum obrigatorio: `carregando`, `vazio`, `erro`, `sucesso` e
  `acesso_negado`. Formularios tambem possuem `editando`, `enviando`,
  `validacao_invalida` e `concluido`.

## 3. Acesso e conta

| Mockup canonico | Rota | Ator | Permissao | Dados principais | Estados especificos |
|---|---|---|---|---|---|
| `login.html` | `GET /login` | Publico | Publica | credenciais e lembrete | autenticando, credencial_invalida, bloqueado |
| `login.html` | `GET /esqueci-senha` | Publico | Publica | e-mail | solicitado, limite_excedido |
| `login.html` | `GET /redefinir-senha/{token}` | Publico | Token valido | token, e-mail, nova senha | token_invalido, token_expirado |
| `perfil.html` | `GET /perfil` | Usuario autenticado | propria conta | usuario, cargos, lotacoes, perfis, sessoes | sem_foto, sessao_revogada |
| `configuracoes.html` | `GET /configuracoes` | Usuario autenticado | propria conta; secoes administrativas exigem permissao planejada `configuracoes.gerenciar` | aparencia, acessibilidade, notificacoes, seguranca e LGPD | salvo, integracao_indisponivel |

Nao existe rota publica de cadastro. Criacao e convite de usuarios pertencem ao
fluxo de equipe escolar.

## 4. Dashboards

| Mockup canonico | Rota | Ator/contexto | Permissao | Dados principais | Estados especificos |
|---|---|---|---|---|---|
| `dashboard.html` e `dashboard-admin.html` | `GET /painel` | Administrador | `dashboards.aplicacao.consultar` e escopo global | nucleos, escolas, usuarios, provas, aplicacoes, alertas | sem_atividade, dados_parciais |
| `dashboard-diretor-nucleo.html` | `GET /painel` | Cargo diretor de nucleo com perfil autorizado | `dashboards.aplicacao.consultar` no nucleo | escolas, turmas, aplicacoes, desempenho agregado | sem_escolas, dados_parciais |
| `dashboard-diretor-escolar.html` | `GET /painel` | Diretor/vice da escola autorizado | `dashboards.aplicacao.consultar` na escola | turmas, alunos, equipe, provas, aplicacoes | sem_turmas, dados_parciais |
| `dashboard-coordenador.html` | `GET /painel` | Coordenador autorizado | `dashboards.aplicacao.consultar` na escola/turmas | turmas, provas, desempenho, pendencias | sem_vinculos, dados_parciais |
| `dashboard-professor.html` | `GET /painel` | Professor autorizado | `dashboards.aplicacao.consultar` nas turmas vinculadas | turmas, proximas aplicacoes, correcoes e desempenho | sem_vinculos, dados_parciais |
| `dashboard-aluno.html` | `GET /painel` na V2 | Aluno autenticado | permissao V2 | resultados proprios e calendario | indisponivel_no_mvp |

## 5. Organizacao, equipe, turmas e alunos

| Mockup canonico | Rota | Ator | Permissao | Dados principais | Estados especificos |
|---|---|---|---|---|---|
| `escolas.html` | `GET /escolas` | Gestor autorizado | `escolas.gerenciar` ou consulta no escopo | escolas, nucleo, status e totais | sem_resultado, filtros_ativos |
| `escola-detalhe.html` | `GET /escolas/{escola}` | Gestor autorizado | `escolas.gerenciar` ou dashboard no escopo | escola, equipe, turmas, alunos, provas e indicadores | inativa, sem_atividade |
| `perfis-equipe.html` | `GET /escolas/{escola}/equipe` | Gestor autorizado | `usuarios_perfis_vinculos.gerenciar` | membros, cargos, perfis, disciplinas, turmas e vigencia | sem_membros, filtros_ativos |
| `membro-cadastrar.html` | `GET /escolas/{escola}/equipe/novo` | Gestor autorizado | `usuarios_perfis_vinculos.gerenciar` | usuario, contato, cargos, perfis, disciplinas e turmas | convite_pendente, duplicado |
| `membro-editar.html` | `GET /escolas/{escola}/equipe/{usuario}/editar` | Gestor autorizado | `usuarios_perfis_vinculos.gerenciar` | membro e vinculos vigentes | inativo, privilegio_negado |
| `turmas.html` | `GET /turmas` | Gestor/profissional autorizado | `turmas_alunos.consultar` | turmas, escola, serie, periodo, professor e totais | sem_resultado, filtros_ativos |
| `turma-detalhe-2.html` | `GET /turmas/{turma}` | Gestor/profissional vinculado | `turmas_alunos.consultar` | turma, alunos, equipe, provas, aplicacoes e desempenho | sem_alunos, sem_aplicacoes |
| `aluno-cadastrar-redesign.html` | `GET /turmas/{turma}/alunos/novo` | Gestor autorizado | `turmas_alunos.gerenciar` | aluno, matricula, foto e responsaveis | matricula_duplicada, responsavel_existente |
| `aluno-detalhe.html` | `GET /alunos/{aluno}` | Profissional autorizado | `turmas_alunos.consultar` | aluno, responsaveis, matriculas, resultados e evolucao | sem_resultados, inativo |
| `aluno-editar.html` | `GET /alunos/{aluno}/editar` | Gestor autorizado | `turmas_alunos.gerenciar` | aluno, foto, responsaveis e matriculas | matricula_duplicada, inativo |

## 6. Provas, gabaritos e correcao

| Mockup canonico | Rota | Ator | Permissao | Dados principais | Estados especificos |
|---|---|---|---|---|---|
| `provas.html` | `GET /provas` | Profissional autorizado | consulta no escopo ou `provas_gabaritos.gerenciar` | provas, disciplina, serie, autor, status e aplicacoes | sem_resultado, filtros_ativos |
| `criar-prova.html` | `GET /provas/nova` | Profissional autorizado | `provas_gabaritos.gerenciar` | prova, questoes, pesos, temas, modelo e turmas | rascunho, publicando, inconsistente |
| `gabarito.html` | `GET /provas/{prova}/gabarito` | Profissional autorizado | `provas_gabaritos.gerenciar` | prova, versao do gabarito e respostas | incompleto, publicado, bloqueado |
| `acompanhar-correcao.html` | `GET /correcoes` | Gestor/profissional autorizado | `dashboards.aplicacao.consultar` | aplicacoes, progresso, alertas e atividade recente | sem_aplicacoes, dados_parciais |
| `acompanhar-correcao-turma.html` | `GET /aplicacoes/{aplicacao}/correcao` | Gestor/profissional vinculado | `dashboards.aplicacao.consultar` e, para revisar, `leituras.confirmar` | alunos previstos, leituras, ambiguidades, resultados e eventos | aguardando_leituras, requer_revisao, finalizada |

## 7. Resultados e relatorios

| Mockup canonico | Rota | Ator | Permissao | Dados principais | Estados especificos |
|---|---|---|---|---|---|
| `resultado-dinamico.html` | `GET /resultados/{resultado}` | Profissional autorizado | permissao planejada `relatorios.resultados.consultar` | aluno, prova, nota, respostas, temas e comparativos | provisoria, vigente, substituida |
| `relatorio-prova.html` | `GET /provas/{prova}/relatorio` | Profissional autorizado | permissao planejada `relatorios.consultar` | prova, turmas, medias, distribuicao e questoes | processando, dados_parciais |
| `relatorio-turma-prova.html` | `GET /turmas/{turma}/provas/{prova}/relatorio` | Profissional autorizado | `relatorios.turma.consultar_exportar_csv` | turma, prova, alunos, resultados e desempenho | processando, dados_parciais |

Exportacoes usam `POST /api/v1/relatorios` e download autorizado temporario. PDF
e CSV pertencem ao MVP para as telas canonicas; XLSX fica em V2.

## 8. Rotas historicas ou rejeitadas

| Mockup | Decisao |
|---|---|
| `aluno-cadastrar.html` | Nao implementar; usar a rota do redesign |
| `resultado.html` | Nao implementar; usar `/resultados/{resultado}` |
| cadastro em `login.html` | Nao implementar; provisionamento por gestor |
| integracoes em `configuracoes.html` | Exibir somente quando uma integracao for aprovada e implementada |
| agenda/reunioes dos dashboards | V2; no MVP exibir apenas provas/aplicacoes |

## 9. Gate para implementacao

Uma tela so pode ser iniciada em R5 quando:

1. sua fonte de dados estiver implementada e coberta por policy;
2. seus estados estiverem definidos no componente;
3. nenhuma metrica depender de dado estatico do mockup;
4. a permissao e o escopo estiverem cobertos por teste;
5. o comportamento responsivo e acessivel estiver especificado.

## 10. Situacao apos R5

As rotas canonicas de conta, painel, escolas, equipe, turmas, alunos, provas,
gabaritos, acompanhamento de correcoes, resultados e relatorios foram
implementadas na R5.

As telas operacionais usam snapshots persistidos. Captura OMR, revisao,
confirmacao, tempo real e geracao de arquivos permanecem reservados para R6.
