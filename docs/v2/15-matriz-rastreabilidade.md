# Matriz de Rastreabilidade V2

> Liga cada tela do mockup (`style-system/`, mapeada em
> [`telas/`](telas/README.md)) à rota web, aos endpoints `/api/v2`, às tabelas e
> ao passo do backend ([`21-plano-backend.md`](21-plano-backend.md)). Sob a
> restrição de **produto único, sem legado**
> ([ADR-D016](../decisoes/ADR-D016-v2-sem-legado.md)): tudo é construído para a
> V2; nenhuma tabela ou endpoint herdado permanece. Toda tabela/endpoint abaixo
> tem origem em uma tela — e nenhuma tela é "entregue" só por existir uma rota.

## Como usar

- A coluna **Mapa** aponta o documento de tela com o detalhe (controles, dados,
  estados, regras, responsividade).
- A coluna **Backend** indica o passo (B0–B9) que cria as tabelas/endpoints.
- A coluna **Tabelas** lista as principais; a modelagem completa está em
  [`07-modelagem-dados-mariadb.md`](07-modelagem-dados-mariadb.md).

## Matriz tela → rota → API → dados → backend

### Acesso e conta

| Tela | Mapa | Rota | Endpoints `/api/v2` | Tabelas | Backend |
|---|---|---|---|---|---|
| Acesso | [login](telas/login.md) | `/login` | `auth/login`, `auth/forgot-password`, `auth/reset-password`, `onboarding`, `onboarding/perfis` | `users`, `perfis`, `solicitacoes_cadastro`, `convites_usuarios`, `consentimentos` | B1 |
| Shell autenticado | [_shell](telas/_shell.md) | (todas) | `me`, `auth/logout`, `me/preferencias` | `users`, `usuarios_lotacoes`, `preferencias_usuarios` | B1 |
| Perfil | [perfil](telas/perfil.md) | `/perfil` | `me`, `me/senha`, `me/foto`, `me/sessoes`, `me/preferencias` | `users`, `sessoes_usuarios`, `historicos_acesso`, `preferencias_notificacoes`, `arquivos` | B1/B8 |
| Configurações | [configuracoes](telas/configuracoes.md) | `/configuracoes` | `me/preferencias`, `plano-uso`, `integracoes`, `integracoes/{id}/testar`, `importacoes`, `exportacoes`, `solicitacoes-lgpd` | `preferencias_usuarios`, `planos_uso`, `integracoes`, `credenciais_integracoes`, `consentimentos`, `politicas_retencao`, `solicitacoes_lgpd` | B2/B8 |

### Dashboards

| Tela | Mapa | Rota | Endpoints `/api/v2` | Tabelas | Backend |
|---|---|---|---|---|---|
| 7 painéis por ator | [dashboards](telas/dashboards.md) | `/painel` | `dashboards/aplicacao/{id}` ✓, `dashboards/prova/{id}` ✓, `dashboards/prova/{id}/snapshot` ✓; `dashboards/{ator}`, `.../kpis`, `.../desempenho`, `atividades-recentes`, `agenda`, `alertas` (B7) | `snapshots_indicadores`, `atividades_recentes`, `eventos_agenda`, `notificacoes` (+ agregações) | B6 ✓ / B7 |

### Escolas e equipe

| Tela | Mapa | Rota | Endpoints `/api/v2` | Tabelas | Backend |
|---|---|---|---|---|---|
| Lista de escolas | [escolas](telas/escolas.md) | `/escolas` | `escolas`, `escolas/{id}`, `escolas/{id}/reativar`, `escolas/kpis` | `escolas`, `nucleos` | B2 |
| Detalhe da escola | [escola-detalhe](telas/escola-detalhe.md) | `/escolas/{id}` | `escolas/{id}`, `escolas/{id}/indicadores`, `.../turmas|provas|alunos|equipe` | `escolas`, `turmas`, `provas`, `alunos`, `usuarios_lotacoes` | B2/B3/B4 |
| Perfis de equipe | [perfis-equipe](telas/perfis-equipe.md) | `/escolas/{id}/perfis` | `escolas/{id}/perfis`, `.../{perfil}/permissoes`, `.../{perfil}/membros` | `perfis`, `permissoes`, `usuarios_perfis` | B1 |
| Cadastrar/editar membro | [membros](telas/membros.md) | `/escolas/{id}/membros/...` | `escolas/{id}/membros`, `.../{m}`, `.../{m}/suspender`, `.../{m}/foto`, `solicitacoes-lgpd` | `users`, `usuarios_perfis`, `usuarios_lotacoes`, `usuarios_disciplinas`, `auditorias` | B1/B8 |

### Turmas e alunos

| Tela | Mapa | Rota | Endpoints `/api/v2` | Tabelas | Backend |
|---|---|---|---|---|---|
| Lista de turmas | [turmas](telas/turmas.md) | `/turmas` | `turmas`, `turmas/importar`, `turmas/kpis` | `turmas`, `series_anos`, `periodos_letivos`, `matriculas_turmas` | B3 |
| Detalhe da turma | [turma-detalhe](telas/turma-detalhe.md) | `/turmas/{id}` | `turmas/{id}`, `.../indicadores`, `.../provas`, `.../alunos` | `turmas`, `aplicacoes`, `resultados`, `frequencias` | B3/B5/B6 |
| Alunos (cadastrar/detalhe/editar) | [alunos](telas/alunos.md) | `/alunos/...` | `alunos`, `alunos/{id}`, `.../foto`, `.../avaliacoes`, `.../evolucao`, `.../ficha.pdf`, `solicitacoes-lgpd` | `alunos`, `matriculas_turmas`, `responsaveis`, `alunos_responsaveis`, `historicos_academicos`, `arquivos` | B3 |

### Provas, correção, resultados e relatórios

| Tela | Mapa | Rota | Endpoints `/api/v2` | Tabelas | Backend |
|---|---|---|---|---|---|
| Lista de provas | [provas](telas/provas.md) | `/provas` | `provas`, `provas/kpis` | `provas`, `provas_turmas` | B4 |
| Criar prova | [criar-prova](telas/criar-prova.md) | `/provas/criar` | `provas`, `provas/{id}/gabarito`, `.../publicar`, `padroes-prova`, `.../cartao.pdf`, `.../turmas` | `provas`, `questoes`, `gabaritos_oficiais`, `gabaritos_respostas`, `padroes_prova`, `versoes_prova`, `materiais_prova` | B4 |
| Gabarito oficial | [gabarito](telas/gabarito.md) | `/provas/{id}/gabarito` | `provas/{id}/gabarito`, `.../gabarito.pdf` | `gabaritos_oficiais`, `gabaritos_respostas`, `versoes_prova` | B4 |
| Acompanhar correção (geral/turma) | [correcao](telas/correcao.md) | `/correcao/{prova}[/turma/{t}]` | `correcao/{prova}`, `.../pendencias`, `leituras/{l}/revisao` + eventos Reverb | `aplicacoes`, `leituras_cartao`, `respostas_detectadas`, `processamentos_omr`, `revisoes_leitura` | B5/B7 |
| Resultado individual | [resultados](telas/resultados.md) | `/resultados/{aluno}/{prova}` | `resultados` (filtro), `resultados/{id}`, `leituras/{l}/revisao` | `resultados`, `resultados_questoes`, `respostas_detectadas`, `metricas_omr` | B6 ✓ |
| Relatórios (prova/turma) | [relatorios](telas/relatorios.md) | `/relatorios/...` | `relatorios/prova/{id}` (`?turma_id=`), `relatorios/prova/{id}/exportar` (`csv\|pdf\|xlsx`), `exportacoes`, `exportacoes/{id}/download`, `comparativos/nucleo/{n}` | `resultados`, `resultados_questoes`, `temas_habilidades`, `exportacoes`, `comparativos` | B6 ✓ |

## Cobertura por passo de backend

| Passo | Telas cobertas |
|---|---|
| B1 Identidade/autorização | Acesso, Shell, Perfil, Perfis de equipe, Membros |
| B2 Organização/integrações | Escolas (lista/detalhe), Configurações (integrações) |
| B3 Acadêmico | Turmas, Detalhe da turma, Alunos |
| B4 Provas/gabaritos | Lista de provas, Criar prova, Gabarito |
| B5 Aplicações/OMR | Acompanhar correção |
| B6 Resultados/relatórios | Resultado, Relatórios, Dashboards (indicadores) |
| B7 Comunicação/tempo real | Dashboards (agenda/alertas), Correção (eventos) |
| B8 Conta/LGPD | Perfil, Configurações (privacidade/zona de perigo), remoções LGPD |

## Registro por entrega (manter por tela)

| Campo | Obrigatório |
|---|---|
| Tela/controle fonte | arquivo e elemento do mockup (ver `telas/`) |
| Rota/superfície | rota web, endpoint `/api/v2` ou tela React Native |
| Fonte de dados | tabela, consulta, serviço ou evento |
| Regra/permissão | regra de negócio e policy/escopo aplicável |
| Estados | loading, vazio, erro, sucesso e específicos (10 estados) |
| Testes | funcional, autorização, acessibilidade (WCAG 2.2 AA) e visual |
| Evidência | screenshot nos 9 viewports, relatório ou execução de teste |

## Regra de encerramento

Nenhuma tela é "entregue" apenas por existir uma rota. Todos os controles
visíveis e recursos associados devem estar funcionais (com dados reais e
autorização por escopo) ou possuir estado explícito de indisponibilidade
aprovado e temporário. Cada entrega atualiza esta matriz e produz evidência
visual contra o mockup.
