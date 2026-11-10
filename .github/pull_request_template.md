## Funcionalidade

FN: FN-NNN
<!-- Obrigatório quando o PR altera apps/. Cite a FN do catálogo (docs/funcionalidades/README.md) e atualize o planejamento.md dela com a evidência. A CI verifica (R-FN-NO-PR). -->

## Problema e comportamento resultante

<!-- Explique o caso concreto, o resultado e o escopo final. Consulte docs/decisoes/0046-fundamentos-de-desempenho-adr-195.md e docs/arquitetura/12-desempenho.md. Preencha proporcionalmente; justifique o que não se aplica. Não anexar dados escolares, credenciais, SQL/bindings ou HAR bruto. -->

## Contrato, acesso e dados

- API/BFF/UR4, consumidores e compatibilidade/opt-in afetados:
- Perfil/vínculo/espaço/recurso e revogação; CAS/idempotência/retry quando houver efeito:
- Query/projeção/filtro/ordem/paginação/índice/plano ou razão de não aplicação:
- Cache: classe de dado, chave/versão/invalidação/TTL/falha, ou “sem cache”. Novo cache técnico sem segredo, conteúdo escolar ou título livre:

## Validação e desempenho

<!-- Relate evidência já obtida. Metas ainda propostas ficam identificadas. Mudança simples pode usar uma frase e verificações pertinentes; não inventar testes/medições. -->

- Release/cenário/volume e método (CI, CLI, HTTP, browser), antes/depois quando comparáveis:
- Queries/chamadas/bytes/latência relevantes; limites e custo obrigatório de segurança:
- Testes de contrato/isolamento/concorrência/jobs e checks executados:
- Se houver interface: carga regional/lifecycle/foco/mobile e revisão visual independente:
- Evidência operacional sanitizada fora do Git e pendências reais:

## Etapa e publicação

- Etapa completa do plano; mudanças/experimentos excluídos deste PR:
- Migration/assets/jobs/configuração, risco e rollback aplicáveis:
- Homologação/recibo/backup quando houver publicação. Produção não é promovida automaticamente:

## Conferência final

- [ ] Contrato e autorização preservados; critérios pertinentes do ADR195 demonstrados ou não aplicação justificada.
- [ ] Resultados medidos separados de metas; não há dado pessoal/credencial em logs, anexos ou novo cache técnico.
- [ ] Validações/revisões correspondem à candidata atual; não incluí alterações preexistentes de outra etapa.
