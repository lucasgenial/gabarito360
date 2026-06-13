# Modelagem de Dados MariaDB V2

## Princípios

- UUID gerado pela aplicação, timestamps em UTC e exclusão lógica quando aplicável.
- FKs, `unique`, índices compostos e transações protegem integridade.
- JSON somente para configuração versionada ou payload técnico, não para
  relacionamentos centrais.
- Esquema **único V2**, construído do zero (ADR-D016). `migrate:fresh` é a
  baseline; não há tabelas herdadas nem migração de dados V1.

## Domínios e entidades

| Domínio | Tabelas núcleo V2 | Tabelas complementares V2 |
|---|---|---|
| Identidade e acesso | `users`, `perfis`, `permissoes`, `usuarios_perfis`, `usuarios_lotacoes`, `cargos`, `personal_access_tokens` | `convites_usuarios`, `solicitacoes_cadastro`, `sessoes_usuarios`, `historicos_acesso` |
| Organização | `nucleos`, `escolas`, `usuarios_disciplinas` | `configuracoes_organizacionais`, `integracoes`, `credenciais_integracoes` |
| Acadêmico | `periodos_letivos`, `series_anos`, `disciplinas`, `turmas`, `alunos`, `matriculas_turmas`, `responsaveis`, `alunos_responsaveis` | `frequencias`, `ocorrencias_alunos`, `historicos_academicos` |
| Provas | `modelos_cartao`, `provas`, `questoes`, `temas_habilidades`, `gabaritos_oficiais`, `gabaritos_respostas`, `provas_turmas` | `padroes_prova`, `versoes_prova`, `materiais_prova` |
| Aplicações e OMR | `aplicacoes`, `aplicacoes_alunos`, `aplicacoes_aplicadores`, `leituras_cartao`, `respostas_detectadas`, `cartoes_resposta`, `logs_sincronizacao` | `processamentos_omr`, `metricas_omr`, `revisoes_leitura`, `dispositivos_homologados` |
| Resultados | `resultados`, `resultados_questoes`, `relatorios`, `arquivos` | `snapshots_indicadores`, `exportacoes`, `comparativos` |
| Comunicação | `preferencias_notificacoes` | `notificacoes`, `eventos_agenda`, `participantes_eventos`, `atividades_recentes` |
| Conta e LGPD | `preferencias_usuarios`, `solicitacoes_lgpd`, `auditorias` | `consentimentos`, `politicas_retencao`, `execucoes_descarte`, `planos_uso` |

## Índices e unicidades obrigatórios

- Escola única por núcleo e código/INEP quando informado.
- Turma única por escola, período letivo e código.
- Matrícula ativa única por aluno, turma e período.
- Perfil/cargo/lotação vigentes sem duplicidade.
- Questão única por prova e ordem; resposta oficial única por gabarito e questão.
- Aplicação única por prova, turma e ciclo definido.
- Resultado vigente único por aluno e prova.
- Código impresso único no escopo da prova; código do sistema único globalmente.
- Idempotency key única por cliente/operação.
- Índices por escopo, status, datas, chaves de busca e FKs.

## Relacionamentos centrais

```text
nucleo -> escolas -> turmas -> matriculas_turmas -> alunos
users -> lotacoes/cargos/perfis -> escolas/turmas/disciplinas
prova -> questoes -> gabarito_oficial -> gabarito_respostas
prova -> provas_turmas -> aplicacao -> aplicacoes_alunos
aplicacao -> leituras_cartao -> respostas_detectadas -> resultado
resultado -> resultados_questoes
qualquer agregado -> arquivos/relatorios/auditorias
```

## Gate antes das migrations V2

Cada tabela deve estar vinculada a uma tela/capacidade na matriz de
rastreabilidade e possuir política de retenção. Não há migração de dados V1: os
dados de demonstração são semeados pela V2.
