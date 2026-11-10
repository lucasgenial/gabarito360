# 009 — Estrutura-alvo de diretórios — Casos de uso

**Natureza:** nova (candidata)
**Perfis envolvidos:** agentes de execução e desenvolvedores; operador técnico. Nenhum perfil da matriz do [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Casos de uso propostos, de alto nível, derivados do [docs/168](../../168-estabilizacao-turmas-e-contratos.md). **Nenhum está implementado** e nenhum pode ser executado antes da priorização (decisão §4 item 15 do [plano mestre](../../planejamento/00-plano-mestre.md)). O [docs/07](../../07-casos-de-uso.md) não tem caso de uso equivalente.

---

## UC-009-01 — Mover a API da plataforma para `apps/api/plataforma/`

**Ator:** agente de execução
**Permissão:** acesso de escrita ao repositório
**Escopo:** repositório, CI e roteiros de operação
**Pré-condições:** funcionalidade priorizada e plano aprovado; nenhuma outra entrega em execução.
**Pós-condições:** API em `apps/api/plataforma/`; CI, deploy, systemd e Nginx apontando para o novo caminho; comportamento HTTP inalterado.
**Tela/rota:** não se aplica.

**Fluxo principal (proposto):**
1. Mover o código e ajustar caminhos em CI, roteiros e unidades de serviço.
2. Publicar na homologação e conferir saúde, login, fila e agendador.
3. Publicar em produção só por decisão expressa.

**Fluxos alternativos e de erro:**
- 2a. Falha em serviço com caminho antigo: manter em manutenção e corrigir antes de reabrir.

**Critérios de aceite:**
- [ ] CI verde sem alteração de testes além de caminhos.
- [ ] Contrato da API idêntico antes e depois.

**Situação:** Não implementado — resolve: subfase 9.2 (se priorizada).

---

## UC-009-02 — Acrescentar o diretório de um motor novo

**Ator:** agente de execução
**Permissão:** acesso de escrita ao repositório
**Escopo:** repositório
**Pré-condições:** UC-009-01 concluído; motor planejado em sua própria funcionalidade (FN-044 ou FN-051).
**Pós-condições:** motor em diretório próprio, com fronteira explícita em relação à API da plataforma.
**Tela/rota:** não se aplica.

**Fluxo principal (proposto):**
1. Criar o diretório do motor conforme a estrutura aceita.
2. Definir o contrato entre o motor e a plataforma no planejamento do motor.
3. Incluir o motor na CI.

**Critérios de aceite:**
- [ ] O motor não acessa o banco de negócio fora do contrato definido.

**Situação:** Não implementado — depende de FN-044/FN-051 e da subfase 9.2.
