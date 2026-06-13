# Plano de Execução do Backend V2 — Laravel + MariaDB (sem legado)

> Detalha a etapa **V2-02** em passos viáveis e incrementais, sob a restrição de
> **produto único, sem legado** ([ADR-D016](../decisoes/ADR-D016-v2-sem-legado.md)).
> O backend é construído como V2 limpo: API exclusivamente `/api/v2`, esquema
> MariaDB **único** consolidado, **sem** compatibilidade com `/api/v1` e **sem**
> tabelas/migrations herdadas da V1.
> Fontes: `07-modelagem-dados-mariadb.md`, `08-api-e-integracoes.md`,
> `04-regras-de-negocio.md`, `02-inventario-funcional-mockup.md`.

## Princípios da execução

- **Base limpa.** O esquema é recriado do zero para a V2; `migrate:fresh --seed`
  é a baseline. Não há migração de dados V1 nem tabelas "reutilizadas".
- **API única.** Apenas `/api/v2`. Nenhum endpoint v1 é criado ou mantido.
- **Incremental, não big bang.** Cada passo é uma fatia migrável e testável, com
  migrations aditivas dentro do próprio esquema V2.
- **Contrato antes de código.** Cada recurso entra primeiro no `docs/openapi-v2.yaml`
  com teste de contrato.
- **Toda tabela** referencia uma tela/capacidade na matriz de rastreabilidade e
  tem política de retenção.
- **Toda funcionalidade** inclui validação, autenticação, autorização por
  escopo, auditoria quando aplicável e testes proporcionais ao risco.

## Padrões transversais (definir no passo B0 e seguir sempre)

| Tema | Padrão |
|---|---|
| IDs | UUID gerado pela aplicação |
| Datas | UTC; `soft delete` quando aplicável |
| Resposta | envelope `data`/`meta` com `request_id`; erros em `UPPER_SNAKE_CASE` |
| Transações | actions/services com transação + lock para regras multi-entidade |
| Idempotência | `Idempotency-Key` em captura, confirmação, importação e exportação |
| Autorização | Policies por perfil + escopo organizacional (núcleo → escola → turma) |
| Camadas | Controller fino → Action (caso de uso) → Service (capacidade) → Model |
| Testes | Feature (API), unit (regras), contrato (OpenAPI), conexão `mariadb_testing` |

## Ordem de dependências

```text
B0 base limpa & contrato v2
   |
B1 identidade & autorização
   |
B2 organização ---------\
B3 acadêmico ------------+--> B4 provas & gabaritos
                         |
                         +--> B5 aplicações & OMR --> B6 resultados/relatórios
                                                          |
B7 comunicação/tempo real  <------------------------------+
B8 conta & LGPD
B9 consolidação e hardening (lançamento V2)
```

---

## B0 — Base limpa e fundação da API v2

**Objetivo:** estabelecer o projeto V2 limpo, sem artefatos v1.

**Ações:**
- remover endpoints/contrato `/api/v1` e migrations/seeders V1 não usados na V2;
- recriar o esquema base com `migrate:fresh` e seeders V2 de demonstração;
- criar grupo de rotas `/api/v2` com middleware de auth, escopo e idempotência;
- padronizar envelope de resposta, erros e `request_id`;
- iniciar `docs/openapi-v2.yaml` e o lint/contrato.

**Tabelas:** `idempotency_keys` (infra V2), tabela de auditoria base.

**Aceite:** não há rota `api/v1`; `GET /api/v2/health` responde no padrão; lint
OpenAPI verde; `migrate:fresh --seed` cria o banco do zero.

**Verificação:**
```powershell
cd backend
php artisan route:list   # nao deve haver path api/v1
php artisan migrate:fresh --env=testing --seed --force
php artisan test --filter=Api\\V2\\Foundation
npx --yes @redocly/cli@2.32.0 lint ../docs/openapi-v2.yaml
```

**Commit:** `feat(backend): base limpa v2 e fundacao da api`

---

## B1 — Identidade, acesso e autorização

**Objetivo:** sustentar login, onboarding, sessões e escopo de todos os atores.

**Ações:**
- modelar perfis/permissões para os atores do mockup (admin geral, gestor de
  núcleo, responsável de escola, professor, aplicador, leitor, suporte);
- implementar convites, solicitações de cadastro e aprovação;
- registrar sessões e histórico de acesso (listar/encerrar sessões);
- configurar Sanctum V2 (escopos, expiração, revogação) e policies por escopo.

**Tabelas:** `users`, `perfis`, `permissoes`, `usuarios_perfis`,
`usuarios_lotacoes`, `cargos`, `personal_access_tokens`, `convites_usuarios`,
`solicitacoes_cadastro`, `sessoes_usuarios`, `historicos_acesso`.

**Recursos API:** `auth`, `me`, `onboarding`.

**Aceite:** cada ator acessa apenas seu escopo; criar conta usa
convite/validação/aprovação (nunca cadastro aberto); auditoria ativa.

**Verificação:**
```powershell
php artisan test --filter=Auth
php artisan test --filter=Authorization
```

**Commit:** `feat(backend): identidade acesso e autorizacao v2`

---

## B2 — Organização e integrações

**Objetivo:** núcleos, escolas, configurações e catálogo de integrações.

**Ações:**
- modelar `nucleos`/`escolas` com abas, indicadores e reativação do mockup;
- implementar configurações organizacionais;
- implementar catálogo de integrações com status, conexão, teste, sincronização,
  última execução e erros; segredos criptografados.

**Tabelas:** `nucleos`, `escolas`, `usuarios_disciplinas`,
`configuracoes_organizacionais`, `integracoes`, `credenciais_integracoes`.

**Recursos API:** `nucleos`, `escolas`, `configuracoes`, `integracoes`.

**Aceite:** escopo respeitado; segredos nunca retornam completos; estados reais
de disponibilidade das integrações.

**Verificação:**
```powershell
php artisan test --filter=Organization
php artisan test --filter=Integration
```

**Commit:** `feat(backend): organizacao configuracoes e integracoes v2`

---

## B3 — Acadêmico (turmas, alunos, responsáveis)

**Objetivo:** cadastros completos, importação e histórico do mockup.

**Ações:**
- modelar turmas, alunos, matrículas e responsáveis com todos os campos do
  mockup (foto, matrícula, status, evolução);
- implementar importação por planilha (idempotente) e ficha PDF do aluno;
- modelar frequências, ocorrências e histórico acadêmico.

**Tabelas:** `periodos_letivos`, `series_anos`, `disciplinas`, `turmas`,
`alunos`, `matriculas_turmas`, `responsaveis`, `alunos_responsaveis`,
`frequencias`, `ocorrencias_alunos`, `historicos_academicos`.

**Recursos API:** `turmas`, `alunos`, `responsaveis`.

**Regras:** matrícula ativa única por aluno/turma/período; importação não
duplica via idempotência.

**Verificação:**
```powershell
php artisan test --filter=Academic
php artisan test --filter=Import
```

**Commit:** `feat(backend): gestao academica completa v2`

---

## B4 — Provas e gabaritos

**Objetivo:** rascunho, padrões, questões, gabarito oficial e publicação.

**Ações:**
- modelar `provas`/`questoes`/`gabaritos_oficiais` para o editor do mockup;
- implementar padrões de prova, versões e materiais;
- implementar publicação, vínculo a turmas e exportação PDF do gabarito.

**Tabelas:** `modelos_cartao`, `provas`, `questoes`, `temas_habilidades`,
`gabaritos_oficiais`, `gabaritos_respostas`, `provas_turmas`, `padroes_prova`,
`versoes_prova`, `materiais_prova`.

**Recursos API:** `provas`, `questoes`, `gabaritos`.

**Regras:** questão única por prova/ordem; resposta oficial única por
gabarito/questão; resultado registra a versão do gabarito usada.

**Verificação:**
```powershell
php artisan test --filter=Exam
php artisan test --filter=AnswerKey
```

**Commit:** `feat(backend): provas padroes e gabaritos v2`

---

## B5 — Aplicações e OMR

**Objetivo:** ciclo de aplicação, leitura, revisão e confirmação idempotente.

**Ações:**
- modelar `aplicacoes`/`leituras_cartao`/`respostas_detectadas`;
- registrar processamentos OMR, métricas e revisões de leitura;
- registrar dispositivos homologados;
- garantir confirmação idempotente e preservação de histórico/reprocessamento.

**Tabelas:** `aplicacoes`, `aplicacoes_alunos`, `aplicacoes_aplicadores`,
`leituras_cartao`, `respostas_detectadas`, `cartoes_resposta`,
`logs_sincronizacao`, `processamentos_omr`, `metricas_omr`, `revisoes_leitura`,
`dispositivos_homologados`.

**Recursos API:** `aplicacoes`, `leituras`.

**Regras:** um cartão válido por aluno/prova; código impresso único na prova,
código do sistema único global; baixa confiança exige revisão explícita;
confirmação idempotente evita duplicidade.

**Verificação:**
```powershell
php artisan test --filter=Application
php artisan test --filter=Reading
php artisan test --filter=Idempotency
```

**Commit:** `feat(backend): aplicacoes leitura e revisao omr v2`

---

## B6 — Resultados, dashboards e relatórios

**Objetivo:** correção automática, indicadores e exportações.

**Ações:**
- modelar `resultados`/`resultados_questoes` e correção automática;
- implementar snapshots de indicadores, comparativos e exportações;
- gerar dashboards por ator e relatórios PDF/CSV/XLSX previstos no mockup.

**Tabelas:** `resultados`, `resultados_questoes`, `relatorios`, `arquivos`,
`snapshots_indicadores`, `exportacoes`, `comparativos`.

**Recursos API:** `resultados`, `dashboards`, `relatorios`, `exportacoes`.

**Regras:** resultado vigente único por aluno/prova; exportações autorizadas e
auditadas.

**Verificação:**
```powershell
php artisan test --filter=Result
php artisan test --filter=Report
php artisan test --filter=Export
```

**Commit:** `feat(backend): resultados dashboards e relatorios v2`

---

## B7 — Comunicação e tempo real

**Objetivo:** notificações, agenda e eventos em tempo real do mockup.

**Ações:**
- modelar notificações, eventos de agenda, participantes e atividades;
- publicar os eventos Reverb: `application.progress.updated`,
  `reading.review.required`, `reading.confirmed`, `result.calculated`,
  `report.ready`, `notification.created`, `calendar.event.changed`;
- canais privados e escopados; snapshot recarregável após reconexão.

**Tabelas:** `preferencias_notificacoes`, `notificacoes`, `eventos_agenda`,
`participantes_eventos`, `atividades_recentes`.

**Recursos API:** `agenda`, `notificacoes`.

**Verificação:**
```powershell
php artisan test --filter=Notification
php artisan test --filter=Calendar
php artisan reverb:start   # validação manual de canais
```

**Commit:** `feat(backend): comunicacao agenda e tempo real v2`

---

## B8 — Conta e LGPD

**Objetivo:** preferências, privacidade, plano/uso e zona de perigo seguros.

**Ações:**
- implementar preferências, notificações, aparência, idioma/região e
  acessibilidade da conta;
- implementar consentimentos, políticas de retenção, execuções de descarte e
  planos/uso;
- transformar "excluir dados" em solicitação LGPD rastreável (inativação,
  anonimização), nunca exclusão direta.

**Tabelas:** `preferencias_usuarios`, `solicitacoes_lgpd`, `auditorias`,
`consentimentos`, `politicas_retencao`, `execucoes_descarte`, `planos_uso`.

**Recursos API:** `configuracoes`, `solicitacoes-lgpd`, `auditorias`.

**Verificação:**
```powershell
php artisan test --filter=Account
php artisan test --filter=Lgpd
```

**Commit:** `feat(backend): conta privacidade e lgpd v2`

---

## B9 — Consolidação e hardening (lançamento V2)

**Objetivo:** fechar a API v2 e provar a base limpa pronta para produção.

**Ações:**
- completar `docs/openapi-v2.yaml` e os testes de contrato de todos os recursos;
- revisar índices/unicidades obrigatórios, performance, escopo e auditoria;
- confirmar que **nenhum** vestígio de `/api/v1` ou tabela legada permanece;
- rodar suíte completa, `pint`, `composer validate` e build.

**Aceite:** nenhuma tela depende de dado sem fonte; API só `/api/v2`; contratos
versionados e testados; CI verde.

**Verificação:**
```powershell
cd backend
composer validate --strict
php vendor/bin/pint --test
php artisan migrate:fresh --env=testing --seed --force
php artisan test
php artisan route:list   # confirmar ausencia de api/v1
npx --yes @redocly/cli@2.32.0 lint ../docs/openapi-v2.yaml
```

**Commit:** `feat(backend): consolidar e endurecer a api v2`

---

## Gate de conclusão do backend V2

O backend está pronto para sustentar a reconstrução visual quando:

1. todas as capacidades do mockup têm fonte de dados real e autorizada;
2. a API expõe **apenas** `/api/v2`, com OpenAPI e testes de contrato;
3. as unicidades/índices de `07-modelagem-dados-mariadb.md` estão aplicados num
   esquema único V2;
4. idempotência, auditoria e escopo cobrem captura, confirmação, importação e
   exportação;
5. `migrate:fresh --seed` + suíte completa passam em verde no CI;
6. não resta nenhum artefato `/api/v1`, página, app ou migration legada;
7. a matriz de rastreabilidade liga cada tabela/endpoint a uma tela do mockup.

## Próximo passo

Iniciar **B0** após concluir o V2-01 (mapa tela a tela), que fornece a lista
exata de campos e estados que cada endpoint precisa entregar.
