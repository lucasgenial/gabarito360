# 050 — Relatório PDF da turma — Planejamento

**Estado no tronco:** Pendente
**Prioridade no plano mestre:** Onda 1, trilha paralela de baixo risco, **se o responsável aceitar** ("integrar WIP do PDF da turma, sem dependência pendente"). O §5 é PROPOSTA.
**Natureza:** evolução de existente (o PDF do recorte da turma existe desde TU1 R4; falta a paridade com o painel)
**Origem:** pendência do TU1 (docs/176, TU1-PDF), absorvida como P8.1 do plano dos painéis (docs/177)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Gerar o relatório em PDF da turma com o mesmo recorte, as mesmas bases e as mesmas regras de autorização do painel da turma, para impressão e reuniões pedagógicas.

## 2. Escopo
- **Inclui:** seis KPIs, três análises, grupos nominais (Atenção com critério e Top 5) com todas as linhas autorizadas e empates; retrato e paisagem; paginação, identidade institucional e verificação documental; exclusão de dados demonstrativos (P8.1.1–P8.1.5).
- **Não inclui:**
  - Indicadores e composição do painel → FN-053.
  - Emissão verificável e cabeçalhos → FN-047 e FN-071 (reutilizados).
  - Documentos administrativos da secretaria (P8.1.6) → a definir junto com FN-053 (variantes por perfil, P2.4).

## 3. Fontes de verdade
- Mockup de referência: `mockups/turma-detalhe-2.html` ([docs/01](../../01-inventario-dos-mockups.md) seção 14); direção de design citada em `operacao-20261102/tu1-pdf-direcao-design.md` (fora do Git).
- Documentos de origem: [docs/176](../../176-reconciliacao-solicitacoes.md) (TU1-PDF), [docs/177](../../177-plano-paineis-turmas.md) (P8.1), [docs/tu1-r3-dashboard-analitico.md](../../tu1-r3-dashboard-analitico.md), [docs/182](../../182-p32-indicadores.md) (bases de P3.2).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-010 do [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) §4.6 (`relatorio.turma`).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-053 paineis-turmas (P3.2) | Bases, exclusões e abrangência dos indicadores | Entregue (homologação) |
| FN-047 omr-qualidade-resultado | Registro de emissão e validação pública | Em homologação |
| FN-071 marca-documentos | Cabeçalho institucional ou autônomo | Em homologação |
| FN-036 organizacao-turma (R4) | PDF do recorte já existente | Em homologação |

**Funcionalidades que dependem desta:** nenhuma identificada.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (TU1 R4) PDF do recorte | `turmas/{id}/painel/pdf`, retrato/paisagem | PDF com emissão registrada | Entregue (homologação) | PR #205, #206 (FN-036) |
| 1.1 (P8.1.1) Reconciliar o WIP com a implementação vigente | Branch `feat/tu1-pdf-relatorio` atualizada com a main | Testes antigos atualizados | Pendente | WIP local b5cd6e0 (remoto 9c074dd) |
| 1.2 (P8.1.2–P8.1.3) Paridade com o painel | 6 KPIs, 3 análises, grupos nominais e empates | Mesmos números do painel no mesmo recorte | Pendente | docs/177 |
| 1.3 (P8.1.4–P8.1.5) Layout e verificação | Retrato/paisagem, paginação, identidade, sem demonstrativos | Revisão UI/UX por texto | Pendente | docs/177 |
| 1.4 Revisão, CI e homologação | PR, revisão independente, publicação na homologação | Recibo de publicação | Pendente | — |

## 6. Contrato técnico (proporcional ao risco)
- **API existente**: `GET v1/turmas/{id}/painel/pdf` (`relatorio.turma`, limitador `painel-turma-pdf`), filtros `disciplina_id`, `professor_id`, `inicio`, `fim`, `analise_prova_id`, `orientacao=retrato|paisagem`. Usa o mesmo DTO de `PainelTurma::montar` e `omr/gerar_painel_turma.py`.
- **Web**: `TurmaController::painelPdf` (`turmas.painelPdf`), entrega inline ou como download (`baixar`).
- **Integridade**: depois de gerar, revalida versão de acesso e permissão; se o DTO mudou durante a geração, 409 "Os dados mudaram. Gere novamente o relatório."; registra a emissão (FN-047).
- **Autorização**: perfis administrativos (secretário escolar, auxiliar) recebem 403 em `PainelTurma::montar`.
- **WIP**: altera só `apps/api/omr/gerar_painel_turma.py` e acrescenta `docs/tu1-pdf-em-andamento.md` (diff contra a base do branch). Sem mudança de API ou dados.

## 7. Interface
PDF A4 retrato e paisagem; casos de listas longas, nomes extensos, ausência de permissão e empates (docs/tu1-pdf-em-andamento.md no branch). Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 e §5 — aceitar FN-050 como trilha paralela da Onda 1 | Aceitar / aguardar a Onda 2 com FN-053 | Aceitar (baixo risco, sem dependência pendente) | Pendente |
| ⚠ Referência do WIP: o plano mestre cita `b5cd6e0` como HEAD; esse commit é um merge da main existente só na branch local; o remoto `origin/feat/tu1-pdf-relatorio` está em 9c074dd | Publicar o merge / refazer a partir do remoto | Conferir antes de retomar; não alterado nesta documentação | Pendente |
| WIP com testes desatualizados | Atualizar / reescrever | Atualizar na subfase 1.1 | — |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
