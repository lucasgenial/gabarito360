---
id: ADR-0029
titulo: Meu espaço como bloco recolhível dentro do card
estado: Aceita
substituida_por: []
data: 2026-11-08
afeta_funcionalidades: [FN-041, FN-075]
afeta_caminhos: ["apps/web/resources/views/**"]
verificacao: revisao-ia
regras: []
agents_secao: Interface
agents_ordem: 90
agents_resumo:
  - "Meu espaço é um bloco recolhível dentro do card."
---
# ADR-0029 — O Meu espaço da primeira prévia volta como bloco recolhível dentro do card, com escola e perfil em duas colunas

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-08
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Continuidade e publicação" ("Correção posterior explícita do responsável"); docs/186
**Afeta:** [FN-041](../funcionalidades/041-autoria-provas/planejamento.md); [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Um ensaio posterior moveu o Meu espaço para um ícone com modal e depois para um bloco expandido no cabeçalho. O responsável rejeitou os dois.

## Decisão

Correção explícita: recupere o "Meu espaço" da primeira prévia, recolhível **dentro do card**, com escola e perfil em duas colunas, no desktop e no mobile.

Não crie botão informacional nem modal "Meu espaço" no cabeçalho. O controle de troca de vínculo já é uma ação própria do cabeçalho e aparece somente para quem tem mais de um vínculo. O avatar abre o menu, que pode ficar à mostra ou ser recolhido.

## Consequências

- Esta correção supera o ensaio de ícone e modal e o bloco expandido no cabeçalho.
- O Meu espaço é apenas informativo.

## Verificação

- Confirme o bloco recolhível dentro do card nas telas com contexto (`contexto-ativo.blade.php`).
