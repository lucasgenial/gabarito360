# ADR-0047 — O plano integral de desempenho executa as etapas completas F0 a F6, com regras de medição e de cache

**Atualizado em:** 10/11/2026, por lucasgenial
**Data:** 2026-11-09
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** AGENTS.md, seção "Desempenho — plano integral e critérios de novas funcionalidades"; docs/194-planejamento-desempenho-integral.md
**Afeta:** [FN-077](../funcionalidades/077-desempenho/planejamento.md); [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md)

Este documento registra uma decisão do responsável e as regras que dela decorrem.

## Contexto

Após o ADR-195, o responsável aprovou o plano integral. Um experimento isolado de F2 foi separado da entrega de F0 e F1.

## Decisão

Execute o desempenho por etapas completas F0 a F6, conforme o docs/194, com os fundamentos do ADR-195. Antes de cada etapa, revise seu planejamento com a evidência anterior e atualize a tabela ao terminar. Uma redução isolada no resolver não encerra F2.

Na entrega de F0 e F1, preserve os experimentos de F2 separados. Não os incorpore ao commit nem à publicação. Subdivisões técnicas autorizadas prosseguem sem nova confirmação por item. A produção continua exigindo a decisão vigente.

Cada funcionalidade declara, proporcionalmente ao risco: contrato API, BFF ou UR4; universo e volume; autorização e revogação; consulta, projeção, ordem e paginação; cache ou a decisão de não usar; lifecycle regional; concorrência e jobs; evidência de validação.

## Consequências

- API é dona do banco e das regras. A WEB consome HTTP.
- Não guarde concessões no ator, por requisição ou por TTL para ganhar desempenho.
- O cache técnico persistente do navegador ou do servidor não recebe senha, token, decisão de permissão, conteúdo escolar nem título livre. O cache estrutural usa contexto, versão, catálogo e schema e nunca autoriza acesso.
- Medições identificam release, cenário, volume e método, e separam CLI, HTTP, BFF e browser. Não produza percentis sem amostra adequada. Não subtraia métodos ou cenários diferentes.
- Não exporte SQL literal, bindings, corpos, aluno, título, e-mail, cookies, tokens ou HAR bruto. Evidências operacionais ficam fora do Git.
- F0 e F1 podem fechar em documentos sem deploy quando não há delta de app ou configuração. A instrumentação, se necessária, é opt-in, só em staging e desativada por padrão.
- Prefira testes de contrato, de crescimento de consultas, de isolamento, de integridade e de bytes determinísticos a cronômetros frágeis.

## Verificação

- Veja o estado das etapas em [FN-077](../funcionalidades/077-desempenho/planejamento.md) e nos documentos docs/196 a docs/200.
