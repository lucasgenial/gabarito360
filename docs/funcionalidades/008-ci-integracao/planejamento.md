# 008 — Integração contínua (MariaDB) — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Prioridade no plano mestre:** sem posição na fila (concluída); pendência residual no §3: confirmar o artefato final do CI
**Natureza:** existente documentada a partir do código
**Origem:** surgiu na entrega dos painéis de turma (PR #216 levou 29m44 no job MariaDB) — docs/181
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Garantir que todo PR e todo push na `main` passem por uma verificação automática única — telas, imagens, artefatos gerados, testes das duas aplicações, integridade em MariaDB real e ensaio de recuperação da auditoria — em tempo aceitável, para que o operador só publique SHAs conferidos.

## 2. Escopo
- **Inclui:**
  - Workflow `Verificação` (`.github/workflows/verificacao.yml`) com os jobs `auditoria-recuperacao`, `telas`, `testes` e `integridade-mariadb`.
  - Otimização do job MariaDB: `tmpfs` para os dados, guarda que confere o armazenamento descartável e eliminação de validações contextuais redundantes.
  - Bloqueio de prints e imagens indevidas (`scripts/verificar_imagens.py`) e verificação de telas (`scripts/verificar_telas.py`).
- **Não inclui:**
  - Publicação e deploy → [FN-003](../003-publicacao-e-homologacao/planejamento.md) (o roteiro não consulta o GitHub; o operador confere o CI).
  - Medição de desempenho com 1000 usuários simultâneos → FN-077.
  - Desempenho do MariaDB da homologação (buffer pool, F6-1) → FN-077.

## 3. Fontes de verdade
- Mockups: não se aplica.
- Documentos de origem: docs/181 (`docs/181-desempenho-mariadb-ci.md`).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: não se aplica.
- Plano mestre: [§2 FN-008](../../planejamento/00-plano-mestre.md); governança §3 (subfase concluída exige CI).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-001 fundacao-monorepo | Aplicações e testes | Concluída |
| FN-004 auditoria | Job `auditoria-recuperacao` ensaia a restauração de AU1 | Parcial (AU1 entregue) |

**Funcionalidades que dependem desta:** todas. A governança exige CI em toda subfase concluída; FN-003 publica só SHAs com CI conferido.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 8.1 Diagnóstico do CI e da homologação | Causa da lentidão identificada | Diagnóstico registrado | Entregue | docs/181 |
| 8.2 `tmpfs` e guarda no job `integridade-mariadb` | Dados do MariaDB em memória; `scripts/ci/verificar_mariadb_descartavel.py` | Tempo do job reduzido sem perder cobertura | Entregue | PR #217 (29m44 → 20m15) |
| 8.3 Eliminar validações contextuais redundantes | Menos consultas por requisição nos testes | Redução medida | Entregue | PR #217, #218 (−28,9 % de consultas) |
| 8.4 Artefato final `mariadb-ci-entrega-final.json` | Registro final da entrega | Artefato localizado e conferido | Incerto | Previsto fora do Git (docs/181) |

## 6. Contrato técnico (proporcional ao risco)
- **Gatilhos:** `pull_request` e `push` na `main`. Sem impacto em produção (§7 do plano mestre).
- **`auditoria-recuperacao`:** MariaDB 10.11 e Redis 7 como serviços; restaura banco e fila em contêineres novos e confere a auditoria.
- **`telas`:** `verificar_telas.py`, `verificar_imagens.py`; reconstrói os artefatos gerados (Tailwind, grade e editor de painéis, calendário, gráficos, notificações) e falha se diferirem do versionado (`git diff --exit-code`); testes Python de PDFs e de qualidade OMR; testes Node; roteiros de navegador em `scripts/auditoria/*.mjs`; testes do Nginx público e da publicação da home.
- **`testes`:** `composer audit --locked --no-dev`, `php artisan test` (API e WEB, por matriz), percurso TW2 no navegador e exclusão real entre processos da sessão WEB.
- **`integridade-mariadb`:** MariaDB 10.11 com `--tmpfs /var/lib/mysql` (1 GiB); guarda de armazenamento descartável; `ProgressoCorrecaoTest` primeiro; depois a lista explícita de testes de integridade e concorrência por `--filter`.
- **Risco:** a lista `--filter` é manual; um teste novo de MariaDB fora da lista não roda nesse job.

## 7. Interface
Sem interface. Os roteiros de navegador da CI conferem estrutura por texto e DOM; não salvam prints (regra do CLAUDE.md, verificada por `verificar_imagens.py`).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Artefato final do CI fora do Git (Incerto) | Localizar e registrar; dispensar | Localizar e registrar a referência, ou declarar dispensado | Pendente |
| Lista manual de testes no job `integridade-mariadb` | Manter; selecionar por grupo/anotação | Avaliar seleção por grupo ao tocar o workflow em uma próxima entrega | Sem decisão necessária agora |
| §4 item 1 — aprovação do plano mestre | — | — | Pendente |

## 9. Ramos desta entrega
Esta funcionalidade nasceu como ramo da correção da prévia dos painéis (PR #216) e foi registrada no tronco como FN-008. Sem ramos abertos.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
