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

## Escopo do painel no MVP

O painel web deve atender somente os fluxos necessarios para o piloto:

- autenticacao e navegacao conforme perfil e escopo;
- cadastros essenciais de nucleo, escolas, usuarios, turmas e alunos;
- importacao validada de alunos por CSV;
- gestao de provas, gabarito vigente e aplicacoes;
- dashboard simples por aplicacao;
- relatorio por turma em tela e exportacao CSV.

Nao fazem parte do gate do painel no MVP:

- SPA Vue ou React;
- dashboards consolidados avancados;
- relatorios PDF ou XLSX;
- interface completa de auditoria;
- console operacional de suporte.

## Restricoes de implementacao

- Componentes Livewire nao devem conter autorizacao como unica barreira; Policies do backend continuam obrigatorias.
- O painel nao deve duplicar regras de negocio que pertencem a Actions ou Services compartilhados.
- O MP-002 registra somente a decisao. Telas e dependencias de frontend serao criadas nos micropassos funcionais correspondentes.
