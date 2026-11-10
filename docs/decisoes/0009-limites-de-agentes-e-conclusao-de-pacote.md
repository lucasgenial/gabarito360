---
id: ADR-0009
titulo: Ações sensíveis exigem aprovação; pacote só termina com testes e quadro atualizado
estado: Aceita
substituida_por: []
data: 2026-10-31
afeta_funcionalidades: [FN-002, FN-003, FN-008]
afeta_caminhos: []
verificacao: processo
regras: []
---
# ADR-0009 — Ações sensíveis exigem aprovação explícita; um pacote só termina com testes, verificações e quadro atualizado

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-10-31
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** docs/28-plano-mestre-proximos-modulos.md, seção 1 "Regras para quem executa"
**Afeta:** [FN-002](../funcionalidades/002-base-operacao/planejamento.md); [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md); [FN-008](../funcionalidades/008-ci-integracao/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

O plano dos próximos módulos (pacotes B, E, L, T, V, A e outros) foi escrito para pessoas e agentes de IA. Era preciso limitar o que um executor faz sem consulta e definir o fim de um pacote.

## Decisão

Nenhum executor faz estas ações sem aprovação explícita do responsável, dada para aquela ação:

- Merge, deploy ou migração em produção.
- Mudança de `.env`, DNS, Cloudflare, Resend, nginx ou PHP-FPM.
- Criar conta ou inserir credenciais em serviços externos.
- Apagar dados ou arquivos em produção.
- Enviar e-mail real para pessoas fora da equipe.

Regra literal: "Chaves e senhas nunca entram no Git, em logs, em PRs ou em conversas."

Durante o pacote: um pacote por PR; toda rota da API usa `permissao:`; toda consulta respeita o escopo; todo dado de turma, aluno, prova, cartão e nota pertence a um espaço; gráficos só por `<x-grafico>`; cores e fontes só pelos tokens; toda mudança com dados pessoais cumpre os requisitos RL1 a RL17 (hoje RL1 a RL19) de `docs/33`.

Um pacote termina quando: os testes de API e WEB passam; `python scripts/verificar_telas.py` passa; a auditoria de telas passa quando há tela nova; a verificação do GitHub passa; o PR diz o que foi e o que não foi testado; o quadro de andamento está atualizado.

## Consequências

- As ações da lista exigem pedido ao responsável em cada ocorrência.
- O quadro de andamento do plano 28 é histórico. A fila vigente está no plano mestre.
- Os pacotes B1 a B6 (base de operação) e E1 a E7 (e-mail) estão concluídos. Os demais seguem o catálogo.

## Verificação

- A CI executa `scripts/verificar_telas.py`, `scripts/verificar_imagens.py` e `scripts/verificar_funcionalidades.py` (`.github/workflows/verificacao.yml`).
- O modelo de PR em `.github/pull_request_template.md` registra o que foi testado.
