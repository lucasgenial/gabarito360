# 002 — Base de operação (backup, Redis, filas, leitura pela fila) — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Conforme princípios:** versão 1
**Observação de estado:** com ressalva
**Prioridade no plano mestre:** sem posição própria na fila; pendências residuais no §3 (validação de B6 com cartão real e retenção do recibo B6, esta dentro do pacote de retenção da Onda 3, decisão §4 item 11)
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 fase 0 (B1–B4, B6); correções em docs/36; operação em docs/operacao/backup.md e docs/operacao/deploy-b6-2026-10-31.md
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar ao servidor de produção uma base operacional segura: backup copiado e restaurável, Redis para cache, sessão e fila, workers e agendador como serviços, e a leitura de cartões fora do processo HTTP, para que a correção não ocupe a API.

## 2. Escopo
- **Inclui:**
  - B1 — cópia dos backups diários do Proxmox para disco local com SHA-256 e teste de restauração.
  - B3 — Redis para cache, sessão e fila, com prefixos separados para API e WEB.
  - B4 — workers `queue:work` nas filas `alta`, `padrao` e `baixa` e agendador `schedule:run` por timer systemd.
  - B6 — envio de cartão respondido com `202` e leitura OMR no job `ProcessarCartaoJob` (fila `alta`), com idempotência por chave do envio e nova tentativa.
  - Retenção do resultado e do recibo de envio B6 (aprovada no docs/39, não implementada).
- **Não inclui:**
  - B2 troca de senha do banco: cancelada pelo responsável em 31/10/2026.
  - B5 roteiro de deploy → [FN-003](../003-publicacao-e-homologacao/planejamento.md).
  - Motor OMR e acompanhamento da correção → FN-045.
  - Painel técnico de filas e falhas → [FN-006](../006-painel-tecnico/planejamento.md).
  - Observabilidade, separação de serviços e armazenamento externo → [FN-007](../007-escala-infraestrutura/planejamento.md).
  - Cópia externa dos backups da homologação → FN-003.

## 3. Fontes de verdade
- Mockups: `mockups/acompanhar-correcao.html` (tela de câmera e acompanhamento, ver docs/01 (`docs/01-inventario-dos-mockups.md`)).
- Documentos de origem: docs/28 (`docs/28-plano-mestre-proximos-modulos.md`) fase 0, docs/36 (`docs/36-b6-correcoes-revisao.md`), docs/39 (`docs/39-validacoes-l2.md`) (retenção B6), [docs/operacao/backup.md](../../operacao/backup-e-restauracao.md), [docs/operacao/deploy.md](../../operacao/publicacao-homologacao.md), [docs/operacao/deploy-b6-2026-10-31.md](../../operacao/publicacao-producao.md), docs/58 (`docs/58-v1-fila-e-consultas-contextuais.md`) (fila com vínculo).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-004 do docs/07 (`docs/07-casos-de-uso.md`).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) (`cartao.enviar`, `correcao.acompanhar`).
- Plano mestre: [§2 FN-002](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-001 fundacao-monorepo | API, banco e WEB | Concluída |
| FN-045 omr-correcao | Driver OMR usado pelo job B6 | Concluída (com ressalva) |
| FN-062 lgpd-inventario-retencao | Prazos de retenção do resultado e do recibo B6 (docs/39) | Parcial |

**Funcionalidades que dependem desta:** FN-003 (B5 exige Redis e workers), FN-004 (auditoria grava pela fila `baixa`), FN-006 (A2 mostra filas e falhas), FN-007 (X1 depende de B6; X6 de B4), FN-024 (jobs de cópia), FN-045 (correção pela fila), FN-057 (N1 depende de B4), FN-060 (motor de e-mail pela fila).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 2.1 (B1) Backup e restauração | `scripts/operacao/proxmox-backup-copia`, `copiar-backups.ps1` | Restauração real feita e registrada | Entregue (produção) | PR #51, #62; docs/operacao/backup.md; ensaios docs/52, docs/100 |
| 2.2 (B2) Troca da senha do banco | — | — | Cancelada | Decisão do responsável de 31/10/2026 |
| 2.3 (B3) Redis | `.env` com `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION=redis`; prefixos `g360_api_`/`g360_web_` | Login, correção e relatórios funcionam com Redis | Entregue (produção) | PR #52 (`b49baec`); docs/operacao/deploy.md |
| 2.4 (B4) Workers e agendador | `infra/systemd/gabarito360-worker@.service`, `gabarito360-agendador.service/.timer` | Trabalho de teste passa pela fila; falha aparece em `failed_jobs` | Entregue (produção) | PR #52 |
| 2.5 (B6) Leitura de cartão pela fila | `CartaoController::store` 202, `FilaCartoes`, `ProcessarCartaoJob` | 50 envios simultâneos em teste não ocupam a API e todos terminam | Entregue (produção), com ressalva | PR #61, #65; docs/36; falta validação com cartão impresso real em produção |
| 2.6 (B6) Retenção do resultado (30 dias) e do recibo mínimo (90 dias) | Rotina de eliminação com marco definido | Prazos aplicados sem quebrar retentativas | Pendente | Aprovada no docs/39; sem implementação no código |

## 6. Contrato técnico (proporcional ao risco)
- **Filas:** conexão `redis`; workers com `--queue=alta,padrao,baixa --tries=3 --timeout=120 --max-time=3600 --memory=256`; dois workers (`@1`, `@2`) em produção. Falhas ficam em `failed_jobs` (`queue:failed`, `queue:retry`).
- **Redis:** `maxmemory 256mb`, `maxmemory-policy volatile-lru` (só expulsa chaves com validade, preserva a fila), `appendonly yes`.
- **B6:** `POST /api/v1/provas/{id}/cartoes` valida imagem (≤ 10 MB), aluno opcional e `envio_id` UUID opcional; grava a foto e enfileira; responde `202`. A chave do envio é contextual (usuário, vínculo, espaço) e protegida por `Cache::lock`; repetir a mesma chave não duplica. O job usa `WithoutOverlapping` por cartão, 3 tentativas com espera de 5 e 20 s, `failOnTimeout`, e revalida vínculo, espaço e permissão no worker.
- **Agendador:** timer a cada minuto; tarefas atuais em `apps/api/routes/console.php` (verificação de inadimplência diária, entregas e execuções de cópias sob flag).
- **Backup:** duas camadas no mesmo local; SHA-256 com até 3 tentativas; mantém as 8 datas mais recentes. Os arquivos não são cifrados (pendência de criptografia de disco em FN-062).
- **Desempenho:** a medição com 1000 usuários simultâneos é FN-077 (X1); B6 não tem medição nesse padrão.

## 7. Interface
A tela de câmera e acompanhamento da correção pertence a FN-045/FN-046. Esta funcionalidade só define o contrato assíncrono (202, consulta e repetição do envio). Sem conferência visual própria.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 11 — LGPD operacional: implementar as retenções aprovadas no docs/39 (inclui recibo B6) | Pacote único de retenção na Onda 3 ou implementação isolada | Pacote único de retenção na Onda 3 | Pendente |
| B6 sem validação com cartão impresso real em produção | Validar na próxima janela de produção ou na homologação com cartão impresso | Validar com cartão real antes de declarar a ressalva encerrada (também pendência de FN-045) | Pendente |
| Backups sem cifra e no mesmo local (docs/operacao/backup.md) | BitLocker no disco local; terceira cópia externa | Tratar no pacote de retenção/criptografia (FN-062) | Pendente |
| `WorkingDirectory` fixo na unidade systemd do worker | Manter; parametrizar | Revisar junto com FN-007 (X5) se houver mais de um host | Sem decisão necessária agora |

## 9. Ramos desta entrega
- docs/36 — correções da revisão de B6 (encerrado).
- V1-C2a (docs/58) — fila B6 com `vinculo_id` no recibo e revalidação no worker (encerrado, registrado em FN-015).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
