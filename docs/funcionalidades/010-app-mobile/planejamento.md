# 010 — Aplicativos Android e iOS — Planejamento

**Estado no tronco:** Adiada
**Prioridade no plano mestre:** Futuro (sem prioridade); pendência do §3: confirmar a manutenção do adiamento
**Natureza:** nova
**Origem:** tronco original — docs/13 MP-023 (Android) e iOS; docs/02 fase 8 (mobile offline); parte não entregue de MP-001
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Oferecer aplicativos Android e iOS em React Native, consumindo a mesma API, para autenticar, ver o painel do perfil, capturar cartões pela câmera e ver resultados, com suporte offline em fase posterior. Está adiada porque a correção pela câmera foi entregue na WEB móvel.

## 2. Escopo
- **Inclui (preliminar, MP-023):**
  - Estrutura `apps/android` e `apps/ios` (React Native).
  - Autenticação no app.
  - Painel do perfil correspondente.
  - Captura de cartões pela câmera e envio para o OMR.
  - Visualização de resultados.
  - Fase posterior (docs/02 fase 8): uso offline e sincronização.
- **Não inclui:**
  - Correção pela câmera na WEB móvel: já entregue → FN-045 e FN-073.
  - Notificações push → FN-057 (N4 é push no navegador; push nativo não está planejado).
  - Portal dos responsáveis → FN-067.

## 3. Fontes de verdade
- Mockups: nenhum específico para aplicativo; os mockups HTML valem como referência de fluxo.
- Documentos de origem: [docs/13](../../13-roadmap.md) (MP-001, MP-023 e Nota de Sequenciamento de 06/2026), [docs/02](../../02-visao-geral.md) (fase 8), [docs/12](../../12-arquitetura.md), [docs/18](../../18-omr-nativo-e-cartoes.md) e [docs/19](../../19-plano-telas-por-perfil.md) (princípio "celular primeiro" na WEB).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-001, UC-004, UC-005 e UC-007 do [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) (as mesmas da WEB; a API decide).
- Plano mestre: [§2 FN-010](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-001 fundacao-monorepo | API base (MP-004) | Concluída |
| FN-011 autenticacao-conta | Login e sessão por token | Parcial |
| FN-015 vinculos-contexto | Seleção de vínculo no app | Concluída |
| FN-018 seguranca-urls-sessao | A WEB usa BFF com credenciais no servidor; o app precisa de contrato próprio de token | Em homologação |
| FN-045 omr-correcao e FN-002 (B6) | Envio de cartão assíncrono (202 e acompanhamento) | Concluídas (com ressalva) |
| FN-007 escala-infraestrutura (X3) | Limites por rota para um segundo consumidor da API | Pendente |

**Funcionalidades que dependem desta:** nenhuma.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 10.1 (MP-023) App Android | App base com autenticação e captura OMR | Entregáveis do MP-023 | Adiada | Não existe `apps/android`; Nota de Sequenciamento de 06/2026 (docs/13) |
| 10.2 App iOS | App equivalente | — | Adiada | Não existe `apps/ios` |
| 10.3 (docs/02 fase 8) Offline e sincronização | Uso sem conexão e envio posterior | — | Adiada | Sem plano |

## 6. Contrato técnico (proporcional ao risco)
A detalhar se retomada. Pontos já conhecidos: a API é a única fonte (só JSON); o envio de cartão já é idempotente por `envio_id` UUID gerado no cliente (FN-002), o que favorece reenvio após falha de rede; a autenticação do app não pode reutilizar o desenho da sessão WEB (BFF) e exige definição de armazenamento seguro de token no dispositivo; dados de alunos no dispositivo (offline) exigem avaliação de LGPD (FN-062, FN-063).

## 7. Interface
Sem telas definidas. Se retomada: seguir a identidade própria (FN-070) e exigir mockups ou aprovação explícita. Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| Manutenção do adiamento (§3 do plano mestre) | Manter adiada; retomar; arquivar | Manter adiada: a WEB móvel já cobre a correção pela câmera | Pendente (confirmar) |
| §4 item 1 — aprovação do plano mestre | — | — | Pendente |
| docs/13 marca MP-001 como "Concluído" com android/ios (§6 do plano mestre) | — | Vale o plano mestre: FN-010 Adiada | Registrado no plano mestre |
| Dados pessoais de menores guardados no dispositivo no modo offline | Não guardar; guardar cifrado com prazo | Decidir antes de planejar 10.3 | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
