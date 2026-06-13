# API, Eventos e Integrações V2

## Convenções

- API REST em `/api/v2`; `/api/v1` permanece durante a transição.
- JSON consistente com `success`, `message`, `data`, `errors` e metadados.
- Sanctum para clientes próprios; OAuth2/credenciais específicas para integrações.
- Paginação, filtros, ordenação, escopo e erros versionados.
- Idempotência obrigatória em captura, confirmação, importação e exportação.

## Recursos

`auth`, `me`, `onboarding`, `nucleos`, `escolas`, `equipe`, `perfis`,
`permissoes`, `turmas`, `alunos`, `responsaveis`, `provas`, `questoes`,
`gabaritos`, `aplicacoes`, `leituras`, `resultados`, `dashboards`, `relatorios`,
`exportacoes`, `agenda`, `notificacoes`, `configuracoes`, `integracoes`,
`solicitacoes-lgpd`, `arquivos` e `auditorias`.

## Eventos em tempo real

- `application.progress.updated`
- `reading.review.required`
- `reading.confirmed`
- `result.calculated`
- `report.ready`
- `notification.created`
- `calendar.event.changed`

Todo canal é privado e escopado. O cliente deve recarregar snapshot após
reconexão.

## Integrações

As integrações visíveis em configurações devem ter catálogo, status, conexão,
teste, sincronização, última execução, erros e desconexão. Segredos ficam
criptografados e nunca retornam completos à interface.

## Compatibilidade

A transição deve mapear endpoints V1 reutilizáveis para V2 sem quebrar o app
existente. Mudanças incompatíveis exigem endpoint V2, documentação OpenAPI,
teste de contrato e plano de desativação.
