---
id: ADR-0023
titulo: JavaScript executável em arquivos externos
estado: Aceita
substituida_por: []
data: 2026-11-06
afeta_funcionalidades: [FN-018, FN-075, FN-076]
afeta_caminhos: ["apps/web/resources/views/**", "apps/web/public/js/**"]
verificacao: teste
regras: [R-JS-EXTERNO]
agents_secao: Interface
agents_ordem: 60
agents_resumo:
  - "JavaScript executável fica em arquivos externos. Sem `<script>` inline nem `onclick`."
---
# ADR-0023 — O JavaScript executável fica em arquivos externos; as views não têm script inline nem eventos inline

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-06
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "JavaScript da aplicação" (decisão explícita do responsável em 06/11/2026)
**Afeta:** [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md); [FN-076](../funcionalidades/076-carregamento-estados/planejamento.md); [FN-018](../funcionalidades/018-seguranca-urls-sessao/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Lógica embutida em views dificulta a revisão, o teste e a política de segurança de conteúdo. O responsável pediu funções e responsabilidades claras em arquivos externos.

## Decisão

Decisão explícita: mantenha o JavaScript executável em arquivos externos bem definidos, com funções e responsabilidades claras.

- Não adicione lógica executável em `<script>` inline.
- Não use atributos de evento como `onclick` e `onsubmit` nas views.
- Transmita apenas dados de configuração por atributos `data-*` ou blocos não executáveis `application/json`, com o escape apropriado.
- Conecte os eventos nos arquivos JavaScript.

## Consequências

- Ao alterar uma tela com lógica embutida, migre as interações afetadas.
- Preserve o comportamento, a validação, o CSRF e a acessibilidade ao migrar.

## Verificação

- Procure `onclick=` e `onsubmit=` em `apps/web/resources/views`.
- Divergência: 12 views ainda usam esses atributos (`alunos/show`, `assinatura/index`, `checkout/index`, `escolas/show`, `membros/index`, `provas/gabarito`, `provas/gabarito-edit`, `provas/show` e quatro relatórios). A migração ocorre quando cada tela for alterada.
