# Proposta de AGENTS.md enxuto

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento propõe o novo texto do `AGENTS.md`, que passa a conter só as regras vigentes resumidas e links para os ADRs. O coordenador decide se aplica a proposta. Nenhum arquivo da raiz foi alterado.

## Por que enxugar

O `AGENTS.md` atual tem cerca de 28 mil caracteres. Ele mistura regras vigentes com o histórico de rodadas da prévia do menu e de correções já substituídas. Quem lê o arquivo não distingue o que vale hoje do que foi superado.

O registro de decisões guarda o histórico completo, com citações literais e o estado de cada decisão. O `AGENTS.md` enxuto guarda só o que o agente precisa aplicar agora.

## O que a proposta faz

1. Mantém as regras transversais curtas: idioma, escrita, autoria, tronco único, prints, publicação.
2. Resume cada regra em até 3 linhas e aponta o ADR.
3. Remove as rodadas 1 a 4 da prévia, as correções do menu e o versionamento por etapas. Elas ficam nos ADRs com estado "Substituída" ou "Aceita".
4. Mantém no CLAUDE.md o que é específico dele: perfis, convenção de commits e fluxo de trabalho.
5. Troca a regra "documentar decisões em `docs/12-arquitetura.md`" por "registrar decisões em `docs/decisoes/`".

## Texto proposto

```markdown
# Orientações do projeto

Este arquivo resume as regras vigentes. O histórico e as justificativas estão em [docs/decisoes/](docs/decisoes/README.md).
Em conflito entre este resumo e um ADR, vale o ADR.

## Idioma e escrita

- Responda e escreva em português do Brasil.
- Todo documento novo ou revisado segue o padrão ASD-STE100 moderado: uma ideia por frase, procedimento até 20 palavras, descrição até 25, voz ativa, imperativo nos passos.
- Documentos históricos `docs/NN-*.md` não são reescritos só para seguir o padrão.
- Decisão: [ADR-0050](docs/decisoes/0050-padrao-de-escrita-asd-ste100.md).

## Autoria

- O único autor de commits e PRs é `lucasgenial`. Nenhuma IA aparece como autora ou coautora.
- Não escreva `Co-Authored-By` de IA nem "Generated with ..." em commits, PRs, comentários ou documentos.
- Decisão: [ADR-0051](docs/decisoes/0051-autoria-exclusiva-do-responsavel.md).

## Planejamento

- O plano mestre e o catálogo de funcionalidades são o único tronco de planejamento.
- Consulte o agente `gerente-projetos` antes de iniciar uma funcionalidade e ao fim de cada subfase.
- Toda funcionalidade tem `planejamento.md` e `casos-de-uso.md` aprovados antes da execução.
- Um plano que nasce numa entrega serve só a ela. O que vai além vira funcionalidade candidata.
- Um módulo transversal bloqueia quem depende dele. Não crie versão local paralela.
- Decisão: [ADR-0048](docs/decisoes/0048-tronco-unico-de-planejamento.md).

## Publicação

- Publique primeiro na homologação isolada. Produção só por decisão expressa do responsável.
- Publique no fechamento validado de etapa de nível 1, nunca por subdivisão. Exija backup, migração ensaiada e comparação antes e depois.
- Preserve gabaritos, questões, cartões, respostas, notas e imagens.
- Não inclua em commits arquivos de outras tarefas.
- Decisões: [ADR-0016](docs/decisoes/0016-homologacao-isolada-adr-h1.md), [ADR-0017](docs/decisoes/0017-copia-pontual-de-producao-para-homologacao.md), [ADR-0018](docs/decisoes/0018-producao-so-por-decisao-expressa.md), [ADR-0019](docs/decisoes/0019-marcos-de-entrega-e-conciliacao-r0.md).

## Prints

- Nenhum print da aplicação fica no repositório ou em serviços de imagem (PRs, issues, Drive, Canva e similares).
- Registre as conferências visuais por texto: o que, onde, tema, largura e resultado.
- A CI verifica com `scripts/verificar_imagens.py`.
- Decisão: [ADR-0049](docs/decisoes/0049-sem-prints-da-aplicacao.md).

## Interface

- Toda mudança de interface exige consulta prévia e revisão final de um agente de UI/UX e Product Design. O implementador não aprova a própria tela. [ADR-0020](docs/decisoes/0020-revisao-de-ui-ux-por-especialista.md)
- Base visual: TailwindCSS com a paleta Gabarito360. [ADR-0026](docs/decisoes/0026-tailwindcss-como-base-visual.md)
- Telas ocupam toda a área útil. [ADR-0021](docs/decisoes/0021-telas-ocupam-toda-a-area-util.md)
- Tabelas usam `x-tabela`, com a coluna Ações à direita e centralizada. [ADR-0022](docs/decisoes/0022-tabelas-cards-de-registros-e-avisos.md), [ADR-0039](docs/decisoes/0039-coluna-de-acoes-a-direita-e-ordenacao.md)
- JavaScript executável fica em arquivos externos. Sem `<script>` inline nem `onclick`. [ADR-0023](docs/decisoes/0023-javascript-em-arquivos-externos.md)
- Confirmações usam modal da aplicação, nunca `confirm`, `alert` ou `prompt`. [ADR-0025](docs/decisoes/0025-confirmacoes-em-modal-e-acoes-de-turma.md)
- Ajuda contextual de cards e KPIs usa o título como botão. [ADR-0028](docs/decisoes/0028-ajuda-contextual-no-titulo-de-cards.md)
- Menu, conta, Meu espaço e prévia: [ADR-0029](docs/decisoes/0029-previa-correcao-meu-espaco-no-card.md), [ADR-0030](docs/decisoes/0030-previa-avatar-abre-o-menu-da-conta.md), [ADR-0032](docs/decisoes/0032-previa-rodada-2-menu-expandido-e-compacto.md), [ADR-0034](docs/decisoes/0034-previa-rodada-4-solucao-progressiva.md), [ADR-0036](docs/decisoes/0036-tw2-filtros-em-modal-e-superficie-escura.md).
- Rodapé: "Gabarito360 - v0.1.0 - Homologação", oculto no celular. Versão em `apps/web/config/versao.php`. [ADR-0038](docs/decisoes/0038-rodape-de-versao-com-ambiente.md), [ADR-0044](docs/decisoes/0044-rodape-de-versao-oculto-no-celular.md)
- Painéis e editor: [ADR-0040](docs/decisoes/0040-paineis-secoes-e-cards-reordenaveis.md), [ADR-0042](docs/decisoes/0042-editor-de-paineis-quadro-de-colunas.md), [ADR-0043](docs/decisoes/0043-editor-de-paineis-aceite-e-integracao.md).

## Sessão e vínculo

- O vínculo ativo acompanha a sessão. Sessão irrecuperável encerra toda a sessão Web. [ADR-0024](docs/decisoes/0024-vinculo-ativo-acompanha-a-sessao.md)

## Desempenho

- Toda medição usa 1000 usuários simultâneos, sem simulação. [ADR-0052](docs/decisoes/0052-medicao-com-1000-usuarios-simultaneos.md)
- Siga os fundamentos e o checklist do ADR-195: API é dona do banco, cache técnico sem autoridade de acesso, carga progressiva, escritas com integridade. [ADR-0046](docs/decisoes/0046-fundamentos-de-desempenho-adr-195.md), [ADR-0047](docs/decisoes/0047-plano-integral-de-desempenho-f0-f6.md)
- Não exporte SQL literal, corpos, dados de aluno, cookies, tokens nem HAR bruto.

## Registro de decisões

- Registre toda decisão nova do responsável em `docs/decisoes/`, no modelo `_modelo.md`.
- Se ela substitui outra, marque a antiga como "Substituída por ADR-NNNN".
```

## Itens que ficam fora do AGENTS.md

| Conteúdo atual | Destino |
|---|---|
| Rodadas 1 a 4 da prévia, correções do menu | ADRs 0029 a 0036 |
| Versionamento por etapas | ADR 0037, substituído em parte pelo ADR 0038 |
| Editor de painéis (prévia local, colunas, aceite) | ADRs 0041 a 0043 |
| Cópia de produção para homologação, detalhes | ADR 0017 |
| Plano integral de desempenho F0 a F6 | ADR 0047 e `docs/194` |

## Divergências encontradas

- O CLAUDE.md duplica várias seções do AGENTS.md. Se o coordenador aplicar esta proposta, aplique o mesmo texto no CLAUDE.md e mantenha lá só as seções próprias (perfis, commits e fluxo de trabalho).
- O CLAUDE.md cita `docs/13-roadmap.md` e `docs/14-plano-de-execucao.md` como consulta para executar um MP. Esses documentos são histórico (ver ADR-0005).
