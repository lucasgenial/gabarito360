# Estratégia de Git — V2

> Alinha o controle de versão da V2 às regras do `AGENTS.md`: commits pequenos e
> temáticos, mensagens em português, nunca executar Git automaticamente sem
> confirmação. Este documento define branches, convenção de commits, fluxo de
> merge, versionamento, releases e tags, e entrega os comandos prontos.

## 1. Estratégia de branches

A V2 **continua no mesmo repositório**, partindo da fundação R7 (decisão
[ADR-D014](../decisoes/ADR-D014-v2-mockup-canonico.md)). Não se cria repositório
novo: isso preservaria a base tecnicamente validada e o histórico.

| Branch | Papel |
|---|---|
| `main` | Linha estável; recebe apenas merges homologados |
| `v2/mockup-canonico` | Linha integradora da V2 (branch atual) |
| `feat/v2-<etapa>-<assunto>` | Trabalho por etapa do plano executável (V2-01…V2-09) |
| `fix/<assunto>` | Correções pontuais |
| `docs/v2-<assunto>` | Alterações apenas de documentação |

Fluxo: branches de trabalho saem de `v2/mockup-canonico`, voltam para ela via PR;
quando uma etapa é homologada, `v2/mockup-canonico` é promovida a `main`.

```text
main
 \__ v2/mockup-canonico
        \__ feat/v2-01-mapa-telas
        \__ feat/v2-02-dados-api
        \__ feat/v2-07-mobile-react-native
```

## 2. Convenção de commits

Padrão Conventional Commits, **descrição em português**, no imperativo, curta.

```
<tipo>(<escopo>): <descrição>
```

Tipos: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`, `build`, `ci`, `perf`.
Escopos sugeridos: `backend`, `web`, `mobile`, `omr`, `dados`, `infra`, `v2`.

Exemplos:

```bash
git commit -m "docs(v2): registrar ADR-D015 e adotar react native"
git commit -m "docs(v2): analise de gap e reaproveitamento da v2"
git commit -m "feat(backend): ampliar dados e contratos para a v2"
git commit -m "feat(web): reconstruir fundacao visual fiel ao mockup"
git commit -m "feat(mobile): estrutura inicial do app react native"
git commit -m "feat(omr): homologar pipeline opencv da v2"
```

Regras: um assunto por commit; informar os arquivos de cada commit; sugerir
`git push` apenas após o commit; nunca um único commit gigante.

## 3. Fluxo de merge

1. Abrir PR da branch de trabalho para `v2/mockup-canonico`.
2. Revisão obrigatória + CI verde (lint, testes, build).
3. Para etapas com UI, anexar evidência visual nos 9 viewports.
4. Merge preferencialmente com `--no-ff` para preservar o agrupamento da etapa.
5. Atualizar `15-matriz-rastreabilidade.md` antes de fechar a etapa.
6. Promoção a `main` apenas após aceite humano da etapa.

## 4. Versionamento, releases e tags

- Versionamento **SemVer**: `MAJOR.MINOR.PATCH`.
- A V2 inicia a linha `2.x`. Marcos por etapa do plano executável:

| Tag | Marco |
|---|---|
| `v2.0.0-alpha.1` | Canonização do mockup + documentação V2 (V2-00) |
| `v2.0.0-beta.1` | Fluxo web integral homologado (V2-06) |
| `v2.0.0-rc.1` | App React Native + OMR homologados (V2-07/08) |
| `v2.0.0` | Produto integral homologado (V2-09) |

Tag anotada por release:

```bash
git tag -a v2.0.0-alpha.1 -m "V2 canonizada: mockup integral e documentacao"
git push origin v2.0.0-alpha.1
```

## 5. Comandos operacionais (executar manualmente)

> O `AGENTS.md` proíbe execução automática de Git. Os comandos abaixo são para
> você rodar e revisar. Use Git Bash ou PowerShell na raiz do repositório.

### 5.1 Confirmar contexto

```bash
git status --short
git rev-parse --abbrev-ref HEAD   # esperado: v2/mockup-canonico
```

### 5.2 Branch da V2 (caso ainda não exista localmente)

```bash
git checkout main
git pull origin main
git checkout -b v2/mockup-canonico   # ou: git checkout v2/mockup-canonico
```

### 5.3 Publicar a branch

```bash
git push -u origin v2/mockup-canonico
```

### 5.4 Commits desta entrega de documentação V2

Agrupados por tema (rodar na ordem):

```bash
# 1) Decisão e contrato mobile React Native
git add docs/decisoes/ADR-D015-mobile-react-native.md \
        docs/v2/10-android-react-native.md \
        docs/v2/10-android-flutter.md
git commit -m "docs(v2): adotar react native no mobile (ADR-D015)"

# 2) Decisão: V2 sem legado
git add docs/decisoes/ADR-D016-v2-sem-legado.md
git commit -m "docs(v2): v2 como produto unico sem legado (ADR-D016)"

# 3) Análises de gap e reconstrução/remoção
git add docs/v2/17-analise-gap.md docs/v2/18-analise-reaproveitamento.md
git commit -m "docs(v2): gap e plano de reconstrucao sem legado"

# 4) Plano de backend e estratégia de Git
git add docs/v2/21-plano-backend.md docs/v2/19-estrategia-git.md
git commit -m "docs(v2): plano de backend limpo e estrategia de git"

# 5) Propagar 'sem legado' nos canônicos e índices
git add README.md AGENTS.md docs/v2/README.md \
        docs/v2/06-arquitetura-e-reaproveitamento-v1.md \
        docs/v2/07-modelagem-dados-mariadb.md \
        docs/v2/08-api-e-integracoes.md \
        docs/v2/16-plano-executavel-v2.md
git commit -m "docs(v2): refletir produto unico sem legado e react native"

# 6) Relatório de execução
git add docs/v2/20-relatorio-execucao.md
git commit -m "docs(v2): relatorio de execucao da fase de planejamento"
```

### 5.5 Publicar e marcar o release de planejamento

```bash
git push origin v2/mockup-canonico
git tag -a v2.0.0-alpha.1 -m "V2 canonizada: mockup integral e documentacao"
git push origin v2.0.0-alpha.1
```

## 6. Decisão sobre repositório novo (rejeitada)

Criar um repositório novo foi avaliado e **rejeitado** (ADR-D014): a arquitetura
alvo não é suficientemente diferente da atual para justificar perder a base R7
validada e o histórico. A V2 evolui no mesmo repositório, isolada pela branch
`v2/mockup-canonico` e pelas tags `2.x`.
