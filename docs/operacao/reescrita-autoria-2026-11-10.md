# Reescrita do histórico para remover autoria de IA (10/11/2026)

**Decisão do responsável:** commits e PRs não identificam IA como autora ou coautora; o único autor é `lucasgenial` (regra em AGENTS.md/CLAUDE.md, "Autoria de commits e PRs"). O responsável autorizou expressamente reescrever o histórico já publicado, incluindo a `main`.

## O que foi feito

- Cópia de segurança completa antes de qualquer alteração: espelho do GitHub e bundle verificado em `D:\projetos\gabarito360\backups\` (fora do Git).
- Reescrita com `git filter-repo` num clone isolado, em todas as 229 branches:
  - removidas das mensagens as linhas `Co-Authored-By` de IA, "Generated with Claude Code" e e-mails `noreply@anthropic.com` (157 commits com essas marcas);
  - autor e committer "Claude" (4 commits de 29/10/2026) passaram para Lucas Matos (`lucasgenial`).
- Conferência antes de publicar: 0 marcas restantes; 832 commits antes e depois; as árvores dos 806 commits alterados são idênticas às originais; o conteúdo das 229 branches não mudou.
- Publicação com `--force-with-lease` por branch (228 reescritas, 1 inalterada). O PR #238 continuou mesclável; o PR #194 foi reescrito junto com sua branch.

## Consequências operacionais

- **Identificadores de commit mudaram.** O mapa completo antigo → novo está em [mapa-reescrita-autoria-2026-11-10.tsv](mapa-reescrita-autoria-2026-11-10.tsv). Documentos numerados, recibos e pastas de release nos servidores continuam citando os identificadores antigos, que valem como histórico. Releases de referência:

  | Release | Antigo | Novo |
  |---|---|---|
  | Produção (CT 111) | `a90fbf5` | `9e1ba87` |
  | Hotfix de produção | `83dd5ae` | `d4ae4d8` |
  | Homologação F3 | `9aee7fa` | `19107a9` |
  | Homologação F4-A | `43ab393` | `442fc22` |
  | Homologação F4-B | `9e4a1be` | `6338696` |
  | Homologação F4-C/D (atual) | `ed7dd0c` | `3374ebf` |

- **Próxima publicação:** os operadores conferem o "marcador anterior" pelo identificador gravado no servidor (antigo). Na próxima release, informar a base pelo identificador antigo do marcador e gerar o pacote a partir do commit novo equivalente. O conteúdo é o mesmo, porque as árvores são idênticas.
- **Clones e worktrees locais** criados antes da reescrita têm branches com os identificadores antigos. Para cada um: `git fetch --force origin` e realinhar as branches ao remoto. Quando houver trabalho local não publicado, refazer sobre a branch nova com `git rebase --onto`.
- **Limite do GitHub:** os refs somente leitura de PRs antigos (`refs/pull/*`) continuam apontando para os commits antigos, e o GitHub pode mantê-los em cache. Apagá-los de vez exige pedido ao suporte do GitHub.
