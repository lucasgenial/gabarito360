# CLAUDE.md — Gabarito360

Leia este arquivo e o [AGENTS.md](AGENTS.md) antes de qualquer ação. O AGENTS.md tem as regras vigentes de todas as ferramentas. Este arquivo complementa o AGENTS.md com o essencial para o Claude Code e não repete as regras dele.

## Idioma

Responda e escreva em português do Brasil, no padrão ASD-STE100 moderado (AGENTS.md).

## O que é o Gabarito360

O Gabarito360 é uma plataforma de gestão de avaliações educacionais para redes de ensino. Ele atende todo o ciclo: cadastro, provas e gabaritos, correção por leitura óptica (OMR), resultados e análise pedagógica.

Para entender o sistema, leia nesta ordem:

1. [Visão geral](docs/visao-geral/README.md): o produto em 15 minutos.
2. [Arquitetura](docs/arquitetura/README.md): a referência técnica.
3. [Catálogo de funcionalidades](docs/funcionalidades/README.md): o que existe e o estado de cada item.
4. [Plano mestre](docs/planejamento/00-plano-mestre.md): o que é feito agora e em que ordem.

## Regra fundamental

Nenhum código é criado sem documentação aprovada. Toda funcionalidade tem `planejamento.md` e `casos-de-uso.md` aprovados em `docs/funcionalidades/NNN-slug/` antes da execução.

## Fontes de verdade

Em conflito, vale esta ordem:

1. Mockups HTML: `mockups/`
2. Style system: `style-system/`
3. Documentação: `docs/` (estrutura em [docs/README.md](docs/README.md))
4. Código atual, para descrever o que existe hoje

Um legado antigo, quando presente fora deste repositório, serve só para recuperar regras de negócio validadas. É proibido reutilizar a arquitetura, o banco ou o código dele.

## Arquitetura em uma tabela

| Parte | Tecnologia | Pasta | Responsabilidade |
|---|---|---|---|
| API | Laravel 13, PHP 8.3, Sanctum | `apps/api` | Autenticação, regras de negócio, OMR, relatórios. Só JSON. |
| Web | Laravel 13 (BFF) | `apps/web` | Interface e painéis. Consome a API por HTTP. |
| Banco | MariaDB 10.11 | — | Único banco aprovado. A API é a dona do banco. |

**A API nunca renderiza telas.** Os apps móveis estão adiados (FN-010). Detalhes em [docs/arquitetura/01-contexto-e-containers.md](docs/arquitetura/01-contexto-e-containers.md).

Os perfis e as permissões estão em [docs/arquitetura/04-identidade-acesso-e-permissoes.md](docs/arquitetura/04-identidade-acesso-e-permissoes.md). O design system está em [docs/arquitetura/09-interface-e-design-system.md](docs/arquitetura/09-interface-e-design-system.md).

## Convenção de commits

```
tipo(escopo): descrição curta em pt-BR

Tipos: feat, fix, docs, refactor, test, chore, perf
Escopo: api, web, docs, db, infra

Exemplos:
  feat(api): implementa endpoint de autenticação
  fix(web): corrige o filtro da lista de turmas
  docs: atualiza o catálogo de funcionalidades
```

Sem `Co-Authored-By` de IA e sem "Generated with ..." (AGENTS.md, seção Autoria).

## Fluxo de trabalho

1. Consulte o agente `gerente-projetos`. Localize a funcionalidade (FN-NNN), a fase e a subfase no plano mestre. Confirme que ela está na fila priorizada.
2. Leia o `planejamento.md` e o `casos-de-uso.md` da funcionalidade. Sem os dois aprovados, escreva os documentos primeiro.
3. Verifique as dependências da subfase. Um módulo transversal pendente bloqueia a subfase.
4. Consulte os mockups e o style system para as telas envolvidas.
5. Use os agentes do time ([02-agentes.md](docs/planejamento/02-agentes.md)), na classe de modelo indicada.
6. Implemente seguindo a arquitetura.
7. Teste contra os critérios de aceite da subfase e dos casos de uso.
8. Faça o commit na convenção acima. Atualize a etapa no catálogo e no `planejamento.md`, com evidência.
9. Publique somente na homologação.

Os identificadores antigos (MP-xxx, T5, L4…) estão traduzidos para FN-NNN na coluna "Identificador antigo" do catálogo.
