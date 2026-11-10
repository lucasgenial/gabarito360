# 003 — Publicação, deploy e homologação isolada — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** Onda 0 — se aprovada a decisão §4 item 2, promoção à produção em blocos, começando por UR1–UR4, PR #194 e hotfix de leituras 500; pendências residuais no §3
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 B5 e docs/37; procedimentos T2 (docs/49–54, ramo de T2 que virou padrão); publicação por fase (docs/99, docs/100); H1 homologação isolada (docs/125–130, ADR-H1 no docs/127)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Publicar versões de forma repetível e segura: um roteiro único de deploy para a produção (CT 111), publicação por fase com backup final obrigatório, e uma homologação isolada (VM 120) onde toda entrega é conferida antes de qualquer decisão de produção.

## 2. Escopo
- **Inclui:**
  - B5 — roteiro único `scripts/operacao/deploy.sh` com `--check`, `--manter-manutencao`, `--backup-final`, guarda de esquema, drenagem de filas e conferência HTTP; `umask 022`.
  - Publicação por fase: `preparar-release.sh` e `publicar-fase.sh`, com marcador de primeira ativação.
  - Ensaios de restauração (RTO observado, não é SLA).
  - H1-A..E — homologação isolada: VM própria, banco, Redis, HTTPS, `infra/homologacao/deploy.py`, backup e restauração da VM.
  - Promoção de blocos da homologação para a produção, por decisão expressa (§7 do plano mestre).
- **Não inclui:**
  - Backup diário e restauração do servidor de produção → [FN-002](../002-base-operacao/planejamento.md).
  - CI que precede a publicação → [FN-008](../008-ci-integracao/planejamento.md).
  - Home pública independente da manutenção e Nginx público → FN-072.
  - Separação física de serviços e mais de um host → [FN-007](../007-escala-infraestrutura/planejamento.md).

## 3. Fontes de verdade
- Mockups: não se aplica (operação).
- Documentos de origem: [docs/28](../../28-plano-mestre-proximos-modulos.md) B5, [docs/37](../../37-b5-deploy-unico.md), [docs/49](../../49-t2-preparacao-operacional.md), [docs/50](../../50-t2-validacao-e-release.md), [docs/51](../../51-t2-backup-e-servicos.md), [docs/52](../../52-t2-ensaio-completo-e-prontidao.md), [docs/53](../../53-t2-checkpoint-backup-final.md), [docs/54](../../54-t2-publicacao-homologacao.md), [docs/99](../../99-publicacao-por-fase.md), [docs/100](../../100-publicacao-inicial-v1-t5.md), [docs/102](../../102-planejamento-l4-t6.md), [docs/127](../../127-h1-homologacao-isolada.md), [docs/128](../../128-h1-copia-producao-homologacao.md), [docs/129](../../129-h1-publicacao-ci1-e.md), [docs/operacao/deploy.md](../../operacao/deploy.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: não se aplica (operação por root nos servidores).
- Plano mestre: [§2 FN-003](../../planejamento/00-plano-mestre.md), §4 itens 2 e 16, §7.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-002 base-operacao | Roteiro exige Redis como fila, workers e agendador; backup | Concluída (com ressalva) |
| FN-008 ci-integracao | O operador confere o CI do SHA antes de publicar | Concluída |
| Decisão §4 item 2 | Gate de produção e promoção em blocos | Pendente |

**Funcionalidades que dependem desta:** todas as que estão na fila de produção do §7 (FN-014, 016, 018, 019, 024, 031, 036, 037, 038, 041, 042, 046, 047, 053, 054, 064, 071, 072, 074, 075, 076, 077). Nenhuma vai à produção sem esta funcionalidade e sem decisão expressa.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 3.1 (B5) Roteiro único com `--manter-manutencao`, guarda de esquema e `--backup-final` | `scripts/operacao/deploy.sh`, `pausa-backup-final.sh`, `confirmar-backup-final.py` | Dois deploys aprovados seguidos sem passo manual | Entregue (produção) | PR #66/#67, #81, #82, #84; docs/37 |
| 3.2 (B5) `umask 022` | Normalização de permissões no roteiro | Código publicado legível pelo PHP | Entregue (produção) — ⚠ o plano mestre registra "Incerto" | Commit `34aefd4` no PR #86 (merge `b750b94`), presente em `scripts/operacao/deploy.sh`; anterior à release `a90fbf5` |
| 3.3 Ensaios de restauração | Restauração completa em clones | RTO observado registrado | Entregue | docs/52, docs/100 (cerca de 7 a 8,5 min, não é SLA) |
| 3.4 Publicação por fase e publicação V1–T5 | `preparar-release.sh`, `publicar-fase.sh` | Publicação acumulada com backup final | Entregue (produção 02/11) | PR #133, `a90fbf5`; `docs/operacao/evidencias/preparacao-deploy-fases-2026-11-02.json` |
| 3.5 Encerrar o marcador `primeira-ativacao-pendente` | Passagem ao fluxo rotineiro | Marcador removido e registrado | Incerto | docs/100 "Próxima execução"; `publicar-fase.sh` recusa publicar enquanto o marcador existe |
| 3.6 (H1-A..E) Homologação isolada | VM 120, banco, Redis, HTTPS, `infra/homologacao/deploy.py`, backup e restauração da VM | Homologação disponível e restaurável | Entregue | docs/127–129; PR #159 |
| 3.7 Cópia externa dos backups da homologação | Backup fora do host | Cópia conferida | Pendente | docs/129 |
| 3.8 Rever a reserva de 45 min da janela incluindo o backup final | Janela reavaliada | Tempo registrado | Pendente | docs/53 (backup final de cerca de 15 min) |
| 3.9 Promoção da homologação à produção em blocos | Blocos do §7 publicados por decisão | Decisão registrada e publicação conferida | Pendente | Depende da decisão §4 item 2 |

## 6. Contrato técnico (proporcional ao risco)
- **Produção (`deploy.sh`):** root no CT 111; exige SHAs completos do alvo e da origem; lock por `flock`; executa cópia de si mesmo; drena as filas `alta`, `padrao` e `baixa` com espera de 0 a 3600 s (padrão 300); manutenção da WEB e depois da API; `composer install --no-dev`; `migrate --force` só na API; recria caches; recarrega PHP-FPM; exige HTTP 200 no início, no login e em `/api/v1/health`, localmente e pelo domínio público. Não altera `.env`, DNS, nginx, PHP-FPM ou systemd. Sem rollback automático de migrations.
- **Publicação por fase:** `preparar-release.sh SHA FASE` cria pacote privado em `/root/g360-releases/<sha>` com conferência e estado dos serviços, sem publicar nem migrar; `publicar-fase.sh SHA ATUAL FASE` exige o pacote preparado para o SHA publicado e chama o roteiro com `--backup-final` sempre.
- **Homologação (`deploy.py`):** recusa executar fora do host de homologação; confere SHA-256 do pacote; extrai com filtro seguro; publica em `releases/<sha>` com troca atômica do link `current`; migra só o banco da homologação; recibo privado por release; não recebe endereço nem credencial de produção.
- **Testes do roteiro:** `scripts/tests/test_deploy.py`, `test_pausa_backup.py`, `test_confirmar_backup.py` (comandos simulados).

## 7. Interface
Sem interface própria. A homologação exibe identificação de ambiente, fase e commit (ADR-H1). Conferências visuais das entregas são registradas por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção e fila acumulada (docs/102 "nada antes de L4" × ADR-H1) | Revogar o gate L4; manter | Revogar o gate L4 em favor do ADR-H1, flags de L4 desligadas em produção, promoção em blocos começando pela segurança | Pendente |
| §4 item 16 — PR #194 (UR2-P) aberto desde 05/11 | Decidir com o item 2; hotfix isolado de dependências | Decidir junto com o item 2 | Pendente |
| §4 item 1 — aprovação do plano mestre | — | Aprovar §1–§3 e a Onda 0 | Pendente |
| Divergência: plano mestre marca `umask 022` como Incerto; o código e o Git mostram entrega no PR #86, anterior à release de produção | Atualizar o plano mestre | Registrar como Entregue (produção) ao revisar o plano mestre | Pendente (não corrigido aqui, por regra) |
| Marcador da primeira ativação sem registro de encerramento | Conferir no CT 111 | Conferir e registrar antes da próxima publicação por fase | Pendente |
| Pendências operacionais antes do próximo B5 em produção: reconciliar rodapé e helpers da home (docs/102) e o diff operacional do incidente do painel do professor | — | Tratar na preparação da próxima publicação | Pendente |
| Backups da homologação só no host | Cópia externa | Incluir na subfase 3.7 | Pendente |

## 9. Ramos desta entrega
- Procedimento de corte e checkpoint de backup de T2 (docs/49–54): nasceu como ramo de T2 e foi incorporado ao roteiro padrão (encerrado).
- Publicação por fase (docs/99): ramo da conclusão de T5-E (encerrado).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
