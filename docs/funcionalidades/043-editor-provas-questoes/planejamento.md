# 043 — Editor de provas e banco de questões — Planejamento

**Implementação:** Candidata
**Etapa:** Candidata
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Futuro (candidatas sem prioridade, §5); aguarda priorização do responsável
**Natureza:** nova
**Origem:** tronco original — docs/02 e docs/12 (fases pós-MVP 2–5); docs/118; docs/164 (frente anunciada "Em breve" na home)
**Atualizado em:** 2026-11-09, por lucasgenial

Planejamento enxuto. Não há plano técnico; nada pode ser executado antes da priorização e da aprovação destes documentos.

## 1. Objetivo
Levar o Gabarito360 do gabarito avulso à elaboração completa da avaliação: banco de questões com enunciados, montador de provas, múltiplas versões, questões discursivas e formatos digital e misto, permitindo também o relatório por tema.

## 2. Escopo preliminar
- **Inclui:** banco de questões (fase 2 do docs/02); montador de provas (fase 3); múltiplas versões com gabarito por versão (fase 4); discursivas e pontuação avançada (fase 5); formatos digital e misto; marcações na página da prova; relatório por tema (pendência de FN-049).
- **Não inclui:**
  - Gabarito e cartão-resposta atuais → [FN-040](../040-provas-gabaritos/planejamento.md).
  - Acervo inteligente e sugestão de itens → [FN-044](../044-acervo-gii/planejamento.md).
  - TCT/TRI e integridade → FN-051.
  - Driver OMR externo → FN-048.
  - Cópia de provas → [FN-042](../042-duplicacao-provas/planejamento.md) (este editor define a versão seguinte do contrato de cópia).

## 3. Fontes de verdade
- Documentos de origem: docs/02 (`docs/02-visao-geral.md`) (fases 2–5); docs/12 (`docs/12-arquitetura.md`) ("Evolução Arquitetural Prevista"); docs/118 (`docs/118-cp1-contrato-duplicacao-pedagogica.md`) (preparação do editor); docs/164; docs/176 (`docs/176-reconciliacao-solicitacoes.md`).
- Sem mockup. Exige mockup ou aprovação explícita antes de qualquer código.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) — chaves a definir.
- Plano mestre: [§2 FN-043](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-040 provas-gabaritos | A prova é a composição da avaliação | Concluída |
| FN-038 disciplinas-curriculo | Classificação curricular das questões | Em homologação |
| FN-041 autoria-provas | Autoria e visibilidade do conteúdo | Em homologação |
| FN-045 omr-correcao | Cartão por versão (refatoração prevista no docs/12) | Concluída |

**Funcionalidades que dependem desta:** FN-049 (relatório por tema), FN-044 (GII conecta-se à elaboração de provas), FN-053 (P7.1), FN-051.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 0.1 | Plano completo e mockups | Aprovados pelo responsável | Pendente | — |
| 1.1 (fase 2 do docs/02) | Banco de questões | A definir | Pendente | — |
| 1.2 (fase 3) | Montador de provas | A definir | Pendente | — |
| 1.3 (fase 4) | Múltiplas versões | A definir | Pendente | — |
| 1.4 (fase 5) | Discursivas e pontuação avançada | A definir | Pendente | — |
| 1.5 | Formatos digital e misto; marcações na página | A definir | Pendente | — |
| 1.6 | Relatório por tema | A definir | Pendente | pendência de FN-049 |

## 6. Contrato técnico (diretrizes já registradas)
- Enunciados e questões são entidades próprias, em tabelas separadas; não criar colunas textuais de questões em `provas` nem usar `gabarito_questoes` como banco de enunciados (docs/118, docs/176).
- `formato_avaliacao` hoje aceita só `gabarito`; novos formatos são módulos próprios, sem conversão na edição.
- Cópias existentes não mudam retroativamente; a nova versão do contrato define composição, ordenação, versões e independência de conteúdo.
- docs/12 prevê `ProvaVersao` e gabaritos por versão com refatoração do cartão.

## 7. Interface
A definir. Na home, a frente aparece como "Em breve" (§4 item 13).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 13 — frentes "Em breve" da home sem plano (editor/versões, marcações, formatos) | Manter "Em breve" só para o priorizado / manter todas | Manter apenas o que estiver priorizado; esta FN já está registrada como candidata | Pendente |
| Priorização desta candidata | Priorizar / manter no Futuro | Responsável decide | Pendente |
| Tamanho: a FN reúne quatro fases pós-MVP | Uma FN / dividir | Avaliar a divisão ao escrever o plano completo | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
