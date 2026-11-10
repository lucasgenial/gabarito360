# 005 — "Ver como" e atendimento auditado — Planejamento

**Estado no tronco:** Pendente
**Prioridade no plano mestre:** Onda 4 (junto com FN-004 AU2 e FN-006), dependente de D9 e de AU1
**Natureza:** nova
**Origem:** tronco original — docs/28 (S1); AU3 citado como fase separada nos docs/67, 71, 72 e 77, sem documento próprio
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Permitir que o Suporte atenda um usuário vendo o sistema como ele vê (S1), com motivo, prazo curto, faixa visível e registro de usuário real e usuário visto, e definir o atendimento com acesso a dados pedagógicos de forma auditada (AU3), hoje negado a todo contexto interno.

## 2. Escopo
- **Inclui:**
  - S1 — iniciar "ver como" com motivo; token próprio de 30 minutos; permissões do usuário visto limitadas às do Suporte; bloqueio de escrita se D9 confirmar; alvos proibidos (Desenvolvedor, administrador, outro Suporte); faixa fixa com "Sair"; registro de início, fim e ações com usuário real e visto; aviso por e-mail ao usuário depois do acesso.
  - AU3 — contrato do atendimento com acesso pedagógico auditado (escopo, autorização, prazo, registro), a escrever.
- **Não inclui:**
  - Tela de consulta da auditoria → [FN-004](../004-auditoria/planejamento.md) (AU2).
  - Gestão de acessos internos → FN-013.
  - Exceção individual de simultaneidade de vínculos por Suporte/Desenvolvedor (PF1-C) → FN-014.
  - Modelos de e-mail → FN-060 (E4 fase 2 inclui o modelo "suporte-ver-como").

## 3. Fontes de verdade
- Mockups: nenhum. A faixa de "ver como" e a ação na tela de membros exigem mockup ou aprovação explícita.
- Documentos de origem: [docs/28](../../28-plano-mestre-proximos-modulos.md) (S1, D9), [docs/32](../../32-auditoria.md) ("não há S1/impersonação"), [docs/67](../../67-a1-planejamento-e-acessos-internos.md), [docs/71](../../71-t4-b-interface-clientes.md), [docs/72](../../72-au1-a-registro-auditoria.md), [docs/77](../../77-t5-planejamento-copias.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md), extensão A1 ("atendimento auditado é AU3").
- Plano mestre: [§2 FN-005](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| Decisão D9 (§4 item 9) | Define se o "ver como" é só leitura | Pendente |
| FN-004 auditoria (AU1) | Registro com usuário real e visto (colunas já existem) | Parcial (AU1 entregue) |
| FN-013 acessos-internos (A1) | Contexto interno do Suporte | Concluída |
| FN-015 vinculos-contexto (V2) | Vínculo do usuário visto | Concluída |
| FN-060 email-transacional (E4 fase 2) | Aviso ao usuário atendido | Parcial |
| FN-057 notificacoes | Aviso no sistema, se adotado além do e-mail | Pendente |

**Funcionalidades que dependem desta:** FN-060 (modelo de e-mail "suporte-ver-como" da E4 fase 2). Nenhuma outra declarada no plano mestre.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 5.1 (S1) "Ver como" | Middleware, token de 30 min, faixa, registro real × visto, aviso | Testes de bloqueio de escrita, validade e alvos proibidos | Pendente | Bloqueado por D9; sem rota nem código |
| 5.2 (AU3) Atendimento com acesso pedagógico auditado | Documento de contrato e, depois, implementação | Contrato aprovado | Pendente | Sem documento próprio |

## 6. Contrato técnico (proporcional ao risco)
- Proposta do docs/28: token próprio com validade de 30 minutos; interseção das permissões do usuário visto com as do Suporte; escrita bloqueada se D9 confirmar.
- Auditoria: `usuario_real_id` = Suporte, `usuario_visto_id` = usuário atendido; hoje `Auditoria::registrar` grava os dois iguais (FN-004).
- Sessão e URLs: seguir FN-018 (sem credencial na URL, contexto por aba, sessão serializada).
- Revogação: encerrar o "ver como" ao expirar, ao sair, ao revogar o acesso interno ou ao mudar o vínculo do usuário visto.

## 7. Interface
Sem mockup. Prever faixa fixa com identificação do usuário visto e botão "Sair", estados de carga e erro, desktop e celular. Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 9 — D9: "ver como" do Suporte permite alterar dados? | Só leitura; leitura e escrita | Só leitura, como no ECOAR (docs/28) | Pendente |
| AU3 sem documento | Escrever contrato próprio; incorporar a S1 | Escrever o contrato de AU3 como subfase 5.2 antes de qualquer acesso pedagógico | Pendente |
| Ver dados de menores pelo "ver como" | Limitar a telas sem dados pessoais; permitir com registro | Decidir junto com D9 e AU3, com base no docs/33 | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
