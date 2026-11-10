# 006 — Painel técnico — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Onda 4 (junto com FN-005 e FN-004 AU2), dependente de AU1
**Natureza:** nova
**Origem:** tronco original — docs/28 (A2). Não confundir com T5-A2 (lotes de cópias, FN-024)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar ao Desenvolvedor uma tela com o estado técnico da plataforma — filas, trabalhos com falha, envio de e-mails, versão publicada e saúde dos serviços — sem acesso a dados pedagógicos, substituindo consultas manuais por linha de comando no servidor.

## 2. Escopo
- **Inclui:** tela restrita ao Desenvolvedor com fila (tamanho por fila `alta`, `padrao`, `baixa`), trabalhos com falha, situação dos e-mails, versão (SHA publicado) e saúde de API, WEB, banco, Redis, workers e agendador.
- **Não inclui:**
  - Reprocessar ou apagar trabalhos pela tela: fora do docs/28; exige decisão.
  - Métricas, rastreio de erros e alertas → [FN-007](../007-escala-infraestrutura/planejamento.md) (X6).
  - Consulta da auditoria → [FN-004](../004-auditoria/planejamento.md) (AU2).
  - Dados pedagógicos ou pessoais: proibidos no contexto interno (docs/03, A1).

## 3. Fontes de verdade
- Mockups: nenhum. Exige mockup ou aprovação explícita.
- Documentos de origem: docs/28 (`docs/28-plano-mestre-proximos-modulos.md`) (A2), [docs/operacao/deploy.md](../../operacao/publicacao-homologacao.md) (serviços e falhas da fila).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`), extensão A1 (Desenvolvedor).
- Plano mestre: [§2 FN-006](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-013 acessos-internos (A1) | Contexto interno do Desenvolvedor | Concluída |
| FN-002 base-operacao (B4) | Filas, workers, `failed_jobs` | Concluída (com ressalva) |
| FN-004 auditoria (AU1) | Acesso à tela auditado | Parcial (AU1 entregue) |
| FN-060 email-transacional | Fonte da situação dos e-mails | Parcial |

**Funcionalidades que dependem desta:** nenhuma declarada no plano mestre.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 6.1 (A2) Painel técnico | Tela e endpoint de leitura restritos ao Desenvolvedor | A tela mostra dados reais e nega acesso aos outros perfis | Pendente | Sem código. Bases existentes: `GET /api/v1/health`, `/up`, `queue:failed` |

## 6. Contrato técnico (proporcional ao risco)
- Endpoint de leitura na API, com contexto interno de Desenvolvedor; nunca expor `.env`, credenciais, payload de jobs nem destinatários de e-mail.
- Fila: contagem por fila no Redis; falhas por `failed_jobs` (classe, fila, horário, sem payload).
- Saúde: reaproveitar as rotas de saúde existentes e acrescentar banco, Redis, workers e agendador.
- Versão: SHA publicado e fase.
- Cache curto (segundos) para não pressionar Redis e banco; medir com o padrão de 1000 usuários simultâneos se a tela for consultada com frequência (FN-077).

## 7. Interface
Sem mockup. Cards de estado e tabela de falhas no padrão FN-074, estados de carga, vazio e erro (FN-076). Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Ações de reprocessar ou descartar trabalhos pela tela | Só leitura; com ações auditadas | Só leitura na primeira versão | Pendente |
| Sobreposição com X6 (observabilidade) | A2 antes de X6; usar a ferramenta de X6 | Planejar A2 junto com X6 para não duplicar coleta | Pendente |
| §4 item 1 — aprovação do plano mestre | — | — | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
