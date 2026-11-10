# Atualização de dependências (Dependabot)

**Atualizado em:** 2026-11-10, por lucasgenial
**Ambiente:** repositório no GitHub; a publicação segue o roteiro de homologação
**Quando usar:** o Dependabot abriu um PR, ou um alerta de segurança apareceu na aba *Security*
**Duração estimada:** 10 minutos por PR, mais a CI

> **Atenção:** o PR do Dependabot nunca entra direto na `main`. O autor dele é `dependabot[bot]`, e a CI reprova autor robô ([ADR-0051](../decisoes/0051-autoria-exclusiva-do-responsavel.md)). Adote o PR: o script refaz a mesma mudança num commit seu.

## O que o Dependabot faz

- **Alertas:** o Dependabot compara as versões das bibliotecas com as falhas conhecidas e abre um alerta na aba *Security*. Ele só avisa.
- **PRs de atualização:** toda segunda-feira, ele abre PRs para composer (`apps/api`, `apps/web`), npm (os scripts de build e a prévia do editor de painéis), pip (`apps/api/omr`, `scripts/branding`) e GitHub Actions. A configuração fica em `.github/dependabot.yml`.
- **PRs de segurança:** quando um alerta tem correção, ele abre um PR com a versão corrigida.

## Pré-requisitos

- `git` e `gh` configurados com a sua conta (`gh auth status`).
- A identidade do git local é a sua: `git config user.name` e `git config user.email`.
- O gancho de autoria está ativo: `sh scripts/configurar-ambiente.sh`.

## Passos

1. Abra a lista de PRs do Dependabot: `gh pr list --label dependencias`.
2. Leia as notas de versão do pacote. Uma versão maior (por exemplo, 12 para 13) pode quebrar a compatibilidade.
3. Adote o PR: `python scripts/adotar_pr_dependabot.py <número>`. O script:
   - confere que o PR é do Dependabot e só altera arquivos de dependências;
   - cria a branch `deps/pr-<número>` a partir da `main` e aplica a mesma mudança;
   - faz o commit com a sua identidade e abre um PR novo com o rótulo `dependencias`;
   - comenta no PR do robô com o link do PR novo.
4. Se o pacote alterado é de um script de build (`scripts/tailwind`, `scripts/paineis-grid`, `scripts/calendario`, `scripts/graficos`, `scripts/notificacoes`), rode o build dele e versione o resultado. O job `telas` da CI reprova um bundle desatualizado.
5. Espere os 6 jobs da CI. Faça o merge por squash.
6. Publique na homologação pelo [roteiro de publicação](publicacao-homologacao.md) quando a dependência é de `apps/`.

## Verificação

- O commit na `main` tem você como autor: `git log -1 --format='%an <%ae>' origin/main`.
- O Dependabot fecha o PR dele sozinho, porque a dependência já está atualizada.
- O alerta correspondente fecha na aba *Security*: `gh api repos/lucasgenial/gabarito360/dependabot/alerts?state=open`.

## Reversão

1. Abra um PR que reverte o commit: `git revert <sha>` numa branch nova.
2. Espere a CI e faça o merge por squash.
3. Se a versão nova quebrou a homologação, publique de novo a versão anterior pelo roteiro de publicação.

## Referências

- [ADR-0051](../decisoes/0051-autoria-exclusiva-do-responsavel.md): autoria exclusiva do responsável.
- [ADR-0048](../decisoes/0048-tronco-unico-de-planejamento.md): PR de código cita a FN; PR só de dependências fica dispensado.
- [FN-078](../funcionalidades/078-governanca-executavel/planejamento.md), subfase 4.2.
