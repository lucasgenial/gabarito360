# 015 — Vínculos por pessoa e seleção de vínculo — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** sem posição nas ondas do §5 (concluída). Pendências: aceite visual de V2; destino da conta de aluno sem vínculo (decisão, ligada à §4 item 10 e a FN-020); perfis não suportados (Secretário de Educação, via FN-014/FN-017).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 (V1, V2), docs/40 §5 e §9, docs/55–62, docs/100
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que a mesma conta tenha vários papéis em vários espaços (por exemplo, professora em uma escola e coordenadora em outra) e escolha, ao entrar, em qual vínculo vai trabalhar. Cada vínculo tem situação, vigência, alcance (turmas, alunos), permissões ajustadas e histórico. Convites ligam pessoas a espaços sem que o gestor veja ou defina senhas.

## 2. Escopo
- **Inclui:** tabelas `usuario_vinculos`, `vinculo_permissoes`, `vinculo_eventos`, `vinculo_turmas`, `vinculo_alunos`, `vinculo_convites`; conversão e conciliação das contas legadas; regime `VINCULOS_OBRIGATORIOS`; convite de primeira conta (criar senha); convite de novo vínculo para conta existente; escolha e troca de vínculo; gestão por vínculo (suspender, reativar, encerrar, permissões por vínculo).
- **Não inclui:**
  - Login, senha e e-mail: FN-011.
  - Motor de permissões e regra "quem cadastra quem": FN-012.
  - Acessos internos: FN-013.
  - Perfis institucionais novos: FN-014.
  - Sessão única por vínculo, contexto fora da URL, mutex: FN-018 (UR3/UR4 mudaram o transporte do contexto definido aqui).
  - Convite a partir do CPF, aceite antecipado e agendamento: FN-017.
  - Espaço pessoal e professor autônomo: FN-022.
  - Login do aluno: FN-020.

## 3. Fontes de verdade
- Mockups: sem mockup; extensões aprovadas V2-B e V2-C no [inventário de mockups](../../01-inventario-dos-mockups.md) (`auth/vinculo`, `auth/contexto`, troca no topo, gestão por vínculo).
- Origem: [docs/40](../../40-clientes-espacos-e-vinculos.md), [docs/55](../../55-v1-estrutura-e-inventario.md)–[docs/62](../../62-v2-gestao-e-aceite-de-convites.md), [docs/100](../../100-publicacao-inicial-v1-t5.md), [docs/12](../../12-arquitetura.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-001, UC-008 e UC-011 de [docs/07](../../07-casos-de-uso.md).
- Código: `apps/api/app/Support/ContextoVinculo.php`, `ConsultaVinculo.php`, `TokenVinculo.php`, `RegimeVinculos.php`, `GestaoVinculos.php`, `apps/api/app/Services/ConviteVinculo.php`, `ContaUsuario.php`, `ConciliarVinculos.php`, `apps/api/app/Http/Controllers/Api/SelecaoVinculoController.php`, `ConviteVinculoController.php`, `GestaoVinculoController.php`, `apps/web/app/Http/Controllers/Auth/VinculoController.php`, `ConviteVinculoController.php`, `Auth/LoginController.php` (convite de conta).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-021 clientes-espacos | Vínculo aponta para um espaço (rede, núcleo, escola, pessoal) | Concluída |
| FN-012 permissoes-motor | Permissões do vínculo partem da matriz do perfil | Concluída |
| FN-011 autenticacao-conta | Conta e senha | Parcial |
| FN-060 email-transacional | Entrega dos convites (E5) | Parcial (sem teste real de E5) |

**Funcionalidades que dependem desta:** FN-013, FN-014, FN-016, FN-017, FN-018, FN-020, FN-022, FN-024 e todas as consultas escopadas por vínculo.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (V1-A) | Tabelas de vínculo e `g360:inventariar-vinculos` | Esquema aditivo | Entregue (produção 02/11) | PR #87 |
| 1.2 (V1-B) | `vinculo_turmas`/`vinculo_alunos` com FKs compostas; `g360:converter-vinculos` | Manifesto, hash, simulação | Entregue (produção 02/11) | PR #88 |
| 1.3 (V1-C1) | `Usuario::noVinculo()`, `ContextoVinculo`, permissão/escopo por vínculo | — | Entregue (produção 02/11) | PR #89 |
| 1.4 (V1-C2a) | Fila B6 com `vinculo_id` e revalidação no worker | — | Entregue (produção 02/11) | PR #90 |
| 1.5 (V1-C2b) | Gestão por vínculo, suspensão, permissões por vínculo, convite de primeira conta | — | Entregue (produção 02/11) | PR #91 |
| 1.6 (V1-C3) | `g360:conciliar-vinculos`, `vinculo_convites`, regime obrigatório | Sem retorno ao legado | Entregue (produção 02/11) | PR #92 |
| 1.7 (V1) | Conversão em produção (6 contas → 7 vínculos) e ativação da flag | — | Entregue (produção 02/11) | docs/100; `a90fbf5` |
| 2.1 (V2-A) | `GET auth/vinculos`, `POST auth/vinculos/selecionar`, token `vinculo-contexto` | — | Entregue (produção 02/11) | PR #93 |
| 2.2 (V2-B) | Tela de escolha e troca no topo | Contexto por aba, depois substituído por sessão única (FN-018) | Entregue (produção 02/11) | PR #94 |
| 2.3 (V2-C) | Gestão por vínculo na interface; convite para conta existente | — | Entregue (produção 02/11) | PR #95 |
| 2.4 | Aceite visual de V2 | Conferência registrada por texto | Pendente | docs/100 |
| 2.5 | Destino da conta de aluno sem vínculo | Decisão do responsável | Pendente (decisão) | docs/60, 100 |

## 6. Contrato técnico (proporcional ao risco)
- **Situações do vínculo:** `convidado`, `ativo`, `suspenso`, `encerrado`; vigência `inicio_em`/`fim_em`. Encerrar preserva o histórico.
- **Token:** `vinculo-contexto` com habilidades `vinculo:{id}` e `identidade:{token de origem}`. O contexto vem do token persistido; IDs em corpo ou cabeçalho não o substituem (`ExigirContextoVinculo`).
- **Antes da seleção,** só valem `auth/me`, `auth/imagem`, `auth/foto`, `auth/email`, `auth/logout`, `auth/vinculos*`, `auth/acessos-internos*` e respostas a convites.
- **Convites:** primeira conta em `password_reset_tokens` (com `expira_em` de 7 dias e `vinculo_id`); conta existente em `vinculo_convites` (hash do token, 7 dias, emissor e vínculo do emissor). Código no corpo do e-mail no formato `{id}.{64 caracteres}`; nunca na URL. Emitir não revela se a conta existe, está inativa ou já tem o papel.
- **API:** `GET auth/vinculos` (30/min), `POST auth/vinculos/selecionar` (20/min), `POST auth/convite` (20/min), `POST auth/convite/recusar` (10/min), `POST auth/convites-vinculo/{id}/consultar` (20/min), `POST auth/convites-vinculo/{id}/responder` (10/min), `GET usuarios/convites-vinculo/opcoes`, `POST usuarios/convites-vinculo` (10/min), `POST usuarios/{id}/encerrar-vinculo`.
- **Concorrência:** bloqueio da conta e do vínculo em toda alteração; eventos em `vinculo_eventos`.

## 7. Interface
`auth/vinculo` (lista de vínculos e acessos internos, estado sem vínculo), `auth/contexto`, `auth/convite` (primeira conta), `auth/convite-vinculo`, `membros/convidar-vinculo`, `membros/vinculo`. Conferência visual de V2 ainda pendente, a registrar por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 10 — Login do aluno | Decidir junto com o portal | Plano mestre: decidir com FN-067; afeta o destino da conta de aluno sem vínculo | Pendente |
| Aceite visual de V2 | Fazer agora / incorporar na próxima entrega que tocar as telas | Registrar conferência por texto | Pendente |
| Perfis não suportados (Secretário de Educação) | Contrato contextual em FN-017 | Não declarar acesso antes do contrato | Pendente |
| ⚠ Cadastro de membro exige CPF (11 dígitos) mesmo no fluxo por vínculo | Manter / tornar opcional com FN-016/FN-017 | Divergência com a direção do docs/115 (CPF-primeiro com conciliação); tratar em FN-017 | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
