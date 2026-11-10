---
id: ADR-0055
titulo: Estrutura da documentação e documento dono de cada assunto
estado: Aceita
substituida_por: []
data: 2026-11-10
afeta_funcionalidades: [todas]
afeta_caminhos: ["docs/**"]
verificacao: teste
regras: [R-DOCUMENTACAO]
agents_secao: Documentação
agents_ordem: 10
agents_resumo:
  - "A documentação fica em `docs/`, na estrutura de `docs/README.md`: visão geral, arquitetura, funcionalidades, planejamento, decisões, operação e glossário. A CI verifica os links com `scripts/verificar_documentacao.py`."
  - "Cada assunto tem um único documento dono. Os outros documentos citam o dono por link."
  - "Um fato técnico (rota, tabela, permissão, comando) deve existir no código. Confira antes de escrever."
  - "Registre toda decisão nova do responsável em `docs/decisoes/`, no modelo `_modelo.md`. Se ela substitui outra, marque a antiga como 'Substituída por ADR-NNNN'."
---
# ADR-0055 — A documentação fica em `docs/`, com um único documento dono para cada assunto

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-10
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** pedido do responsável em 10/11/2026, na reorganização da documentação
**Afeta:** todas as funcionalidades; [docs/README.md](../README.md)

## Contexto

A documentação antiga estava espalhada em muitos arquivos numerados. Vários assuntos apareciam em mais de um lugar e se contradiziam. Uma pessoa ou uma IA não conseguia entender o sistema só pela leitura.

## Decisão

A documentação fica em `docs/`, com esta estrutura: README de entrada, visão geral de fácil leitura, arquitetura técnica, funcionalidades, planejamento, decisões, operação, histórico e glossário.

- Cada assunto tem um único documento dono. Os outros documentos citam o dono por link.
- Um fato técnico (rota, tabela, permissão, comando) deve existir no código. Confira antes de escrever.
- Os documentos antigos foram incorporados e removidos.
- Toda decisão nova do responsável é registrada em `docs/decisoes/`, no modelo `_modelo.md`.

Palavras do responsável: "a ideia é que a documentação nova concentre ... a arquitetura do sistema, status do que foi feito, para que uma pessoa e uma ia, tenha uma ideia completa do sistema somente lendo a documentação".

E ainda: "temos que ter uma versão de introdução que é fácil leitura e uma versão extremamente técnica".

## Consequências

- Existe uma versão de introdução, de fácil leitura, e uma versão técnica, para o mesmo assunto.
- Um assunto novo recebe um dono antes de ser escrito.
- Duplicar um assunto em outro documento é proibido. Use link.
- A CI verifica links e estrutura com `scripts/verificar_documentacao.py`.

## Verificação

Rode `python scripts/verificar_documentacao.py`. A CI roda o mesmo script no job `governanca`.
