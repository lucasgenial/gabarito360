# ADR-D012 - Reorientar o produto pelo mockup funcional e adotar MariaDB

- Status: aceito
- Data: 2026-06-12

## Contexto

O backend e o painel foram desenvolvidos até a MP-028 com PostgreSQL e uma
fundação visual própria. Após testar o painel, foi criado o mockup funcional
responsivo em `style-system/`, que representa com maior precisão a experiência,
os perfis, as telas e os fluxos esperados para o Gabarito360.

O projeto ainda não possui dados de produção. Portanto, este é o momento de
reorientar a fundação antes de implementar aplicações, OMR, dashboards em tempo
real e relatórios finais.

## Decisão

1. Suspender a execução do plano antigo após a MP-028.
2. Tornar `style-system/` a referência funcional e de composição da aplicação web.
3. Manter `docs/ui_token_gov_brasil.json` como fonte dos valores visuais e
   `docs/SDGB.md` como referência normativa.
4. Usar tema claro como padrão e tema escuro somente após escolha explícita.
5. Substituir PostgreSQL por MariaDB como banco relacional alvo.
6. Preservar a arquitetura Laravel, autenticação, autorização, services, actions,
   auditoria e testes de domínio que não dependam do banco anterior.
7. Refazer migrations e regras específicas do banco porque não há produção a
   migrar.
8. Converter o mockup em componentes Blade/Livewire reutilizáveis; não copiar CSS
   inline, JavaScript duplicado ou dados estáticos para produção.

## Consequências

- Migrations, testes de infraestrutura, CI e documentação PostgreSQL precisam ser
  reescritos para MariaDB.
- Regras hoje implementadas em PL/pgSQL devem migrar preferencialmente para
  services transacionais, locks e constraints portáveis.
- `citext`, `jsonb`, índices parciais, `NULLS NOT DISTINCT`,
  `gen_random_uuid()` e timestamps com timezone não podem permanecer como
  premissas.
- A matriz de perfis e o modelo relacional devem ser ampliados para refletir
  disciplinas, períodos letivos, equipe escolar, responsáveis, configurações,
  aplicações, resultados e relatórios.
- O mockup inclui ações que não serão copiadas automaticamente, como
  auto-cadastro aberto e exclusão permanente de registros auditáveis.

## Alternativas rejeitadas

- Continuar a MP-029 antes da reorientação: ampliaria o retrabalho.
- Copiar os HTMLs diretamente para Blade: manteria duplicação e dados falsos.
- Reescrever todo o backend do zero: descartaria contratos, segurança e testes
  já válidos sem benefício proporcional.
- Trocar apenas `DB_CONNECTION`: as migrations atuais dependem profundamente de
  recursos exclusivos do PostgreSQL.
