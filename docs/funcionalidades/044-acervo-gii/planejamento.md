# 044 — Acervo inteligente (GII) — Planejamento

**Implementação:** Candidata
**Etapa:** Candidata
**Prioridade no plano mestre:** Futuro (candidatas sem prioridade, §5); aguarda priorização do responsável
**Natureza:** nova
**Origem:** pedido do responsável registrado em docs/164; citado como futuro em docs/144, 146, 176 e 177 (P7.1)
**Atualizado em:** 2026-11-09, por lucasgenial

Planejamento enxuto. Os documentos de origem afirmam que nenhum planejamento do GII foi iniciado; não há código nem motor. Nada pode ser executado antes da priorização e da aprovação destes documentos.

## 1. Objetivo
Oferecer um acervo de itens classificados, com início pelo ENEM, que alimente a elaboração de provas e os cards de habilidades dos painéis, sugerindo itens para uma necessidade pedagógica, sempre sujeitos à revisão do professor.

## 2. Escopo preliminar
- **Inclui:** definição do acervo e de sua classificação (P7.1.1–P7.1.3); sugestão de itens para uma necessidade selecionada (P7.1.4); integração com os cards de habilidades, planejamento e retomada, que ficam "Em breve" até lá (P7.1.5).
- **Não inclui:**
  - Banco de questões da escola, montador e versões → [FN-043](../043-editor-provas-questoes/planejamento.md).
  - TCT/TRI, integridade e sínteses (P7.2–P7.4) → FN-051.
  - Catálogo de disciplinas e áreas → [FN-038](../038-disciplinas-curriculo/planejamento.md).
  - Classificação manual de habilidades do acompanhamento (P6) → FN-055, que não fica bloqueada pelo GII.
  - Simulados ENEM de homologação (dados de teste, não acervo) → FN-038.

## 3. Fontes de verdade
- Documentos de origem: docs/164 (apresentação pública: acervo com início pelo ENEM, sem vínculo oficial nem importação anunciada); docs/144 (`docs/144-dc1-areas-conhecimento.md`); docs/146 (`docs/146-dc1-orientacoes-recolhidas.md`); docs/176 (`docs/176-reconciliacao-solicitacoes.md`); docs/177 (`docs/177-plano-paineis-turmas.md`) P7.1.
- Sem mockup.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) — chaves a definir.
- Plano mestre: [§2 FN-044](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-043 editor-provas-questoes | Itens e elaboração de provas | Candidata |
| FN-038 disciplinas-curriculo | Áreas e disciplinas para classificar itens | Em homologação |
| FN-045 omr-correcao | Sequência acordada: consolidar geração e correção antes do GII | Concluída |

**Funcionalidades que dependem desta:** FN-053 (cards de habilidades, P7.1), FN-055 (P6.1, opcional), FN-009 (a estrutura-alvo de diretórios só se justifica com um segundo motor como GII/TRI, §4 item 15).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 (P7.1.1) | Localizar decisões existentes e escrever o plano completo | Aprovado pelo responsável | Pendente | docs/176: nenhum planejamento iniciado |
| 1.1 (P7.1.2) | Acervo com início pelo ENEM | A definir | Pendente | — |
| 1.2 (P7.1.3) | Itens e classificações chegam aos cards | A definir | Pendente | — |
| 1.3 (P7.1.4) | Sugestão de itens com revisão do professor | A definir | Pendente | — |
| 1.4 (P7.1.5) | Ativação dos cards hoje "Em breve" | A definir | Pendente | — |

## 6. Contrato técnico (diretrizes já registradas)
- Indicador só é ativado com fonte adequada e autorização aplicada no servidor (docs/177).
- Não misturar enunciados ao gabarito atual (docs/176).
- Origem, licença e direito de uso dos itens precisam de definição antes de qualquer carga; a apresentação pública não anuncia vínculo oficial nem importação.
- Motor, armazenamento e forma de integração: a definir no plano completo.

## 7. Interface
A definir. Na home e nos painéis, os pontos correspondentes aparecem como "Em breve".

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 13 — frentes "Em breve" da home sem plano (GII entre elas) | Manter "Em breve" só para o priorizado / manter todas | Manter apenas o que estiver priorizado; esta FN já está registrada como candidata | Pendente |
| §4 item 15 — estrutura-alvo de diretórios (FN-009) | Manter candidata / planejar | Manter candidata até haver segundo motor (GII/TRI) | Pendente |
| Priorização desta candidata | Priorizar / manter no Futuro | Responsável decide | Pendente |
| Fonte e direito de uso dos itens do acervo | A definir | Tratar no plano completo, com revisão jurídica se necessário | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
