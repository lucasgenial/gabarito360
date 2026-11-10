---
id: ADR-0030
titulo: Avatar abre o menu da conta
estado: Aceita
substituida_por: []
data: 2026-11-08
afeta_funcionalidades: [FN-015, FN-019, FN-075]
afeta_caminhos: ["apps/web/resources/views/**"]
verificacao: revisao-ia
regras: []
agents_secao: Interface
agents_ordem: 91
agents_resumo:
  - "O avatar abre o menu da conta. Não duplique a troca de vínculo."
---
# ADR-0030 — O avatar abre o menu da conta, não a navegação principal; a troca de vínculo não se duplica

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Esclarecimento final do responsável")
**Afeta:** [FN-019](../funcionalidades/019-perfil-configuracoes/planejamento.md); [FN-015](../funcionalidades/015-vinculos-contexto/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

A prévia confundia o menu da conta com a navegação principal.

## Decisão

Esclarecimento final: o AVATAR abre o menu DA CONTA, não a navegação principal.

O topo da conta mostra o nome completo e o e-mail de acesso. As ações são Meu perfil, Trocar vínculo (somente se houver mais de um vínculo elegível) e Sair. A navegação principal tem botão próprio, pode ficar à mostra ou escondida e também abre por Mais no celular.

## Consequências

- Não duplique a troca de vínculo em outro ícone do cabeçalho.
- Na prévia, nome, e-mail e vínculos são fictícios. Perfil e Sair são feedback de demonstração, sem abrir cadastro nem encerrar sessão real.

## Verificação

- O botão do avatar tem o id `user-menu-trigger` em `apps/web/resources/views/layouts/app.blade.php`.
