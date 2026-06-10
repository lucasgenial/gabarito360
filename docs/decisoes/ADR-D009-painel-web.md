# ADR-D009 - Abordagem do painel web

- **Status:** aceita para o MVP
- **Data:** 2026-06-10
- **Responsaveis:** Arquitetura + Produto
- **Prazo:** resolvida; revisar apos o piloto ou antes de ampliar o painel

## Contexto

O MVP precisa de telas administrativas, dashboard e relatorio sem introduzir uma segunda aplicacao frontend complexa antes de validar o produto.

## Decisao

O painel web do MVP sera um monolito Laravel usando:

- Blade para templates;
- Livewire para interacoes administrativas e atualizacoes de tela;
- Tailwind CSS com os tokens documentados em `docs/ui_token_gov_brasil.json`;
- Laravel Reverb para atualizacoes em tempo real;
- a mesma camada de Actions, Policies, Requests e Resources usada pela API quando aplicavel.

Nao sera criada uma SPA Vue ou React no MVP. A API REST continua sendo o contrato do app mobile e de integracoes futuras.

## Justificativa

A abordagem reduz custo de entrega, autenticacao duplicada e manutencao, mantendo capacidade suficiente para o painel inicial.

## Impactos

- Dependencias de Livewire devem ser adicionadas apenas quando o primeiro fluxo web for implementado.
- Policies do backend permanecem como barreira obrigatoria de autorizacao.
- Uma migracao futura para SPA exige novo ADR e nao deve alterar os contratos mobile sem versionamento.
