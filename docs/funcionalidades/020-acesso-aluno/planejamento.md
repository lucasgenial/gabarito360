# 020 — Acesso próprio do aluno — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Observação de estado:** Incerto no plano mestre
**Prioridade no plano mestre:** Onda 4, a avaliar junto com FN-067; depende da decisão §4 item 10. Proposta ainda não aprovada (§5).
**Natureza:** evolução de existente (há código parcial sem liberação)
**Origem:** tronco original — docs/19 MP-041 e decisão 6.4; docs/03
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que o aluno entre com a própria conta e veja apenas os seus resultados: painel do aluno e "Minhas provas" com a folha corrigida. Por envolver acesso individual de menores, a liberação depende de decisão do responsável.

## 2. Escopo preliminar
- **Inclui:** decisão de liberar o login do aluno; forma de ligar a conta ao cadastro de aluno; painel do aluno; "Minhas provas" e resultado próprio; regras de menoridade e consentimento.
- **Não inclui:**
  - Acesso dos responsáveis: FN-067.
  - Termos, privacidade e aceite: FN-061.
  - Identidade canônica do aluno: FN-016.
  - Convite e vínculo em si: FN-015 e FN-017.
  - Resultado individual e relatórios (telas já existentes para a equipe): FN-049.

## 3. Fontes de verdade
- Mockup: `mockups/dashboard-aluno.html` ([inventário §07](../../01-inventario-dos-mockups.md)).
- Origem: [docs/19](../../19-plano-telas-por-perfil.md) MP-041 e decisão 6.4; [docs/03 §2 e §7](../../03-perfis-e-permissoes.md) (vínculo usuário↔aluno "pendente").
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-005 e UC-007 de [docs/07](../../07-casos-de-uso.md).
- Código existente: `apps/api/app/Http/Controllers/Api/DashboardController.php` (`aluno`), `apps/api/app/Support/ContextoVinculo.php` (referências de aluno por `vinculo_alunos`), `apps/web/resources/views/dashboard/aluno.blade.php`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| Decisão §4 item 10 | Liberar ou não o login do aluno | Pendente |
| FN-015 vinculos-contexto | Vínculo de perfil `aluno` e tabela `vinculo_alunos` | Concluída (sem tela para ligar conta e aluno) |
| FN-016 identidade-pessoal | Identidade do aluno sem exigir conta | Parcial |
| FN-061 lgpd-termos-privacidade | Aceite e base legal para menores | Parcial |
| FN-067 portal-responsaveis | Decisão conjunta sobre acesso individual de menores | Pendente |

**Funcionalidades que dependem desta:** nenhuma registrada; FN-067 é avaliada em conjunto.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (decisão 6.4) | Decidir o login do aluno | Decisão registrada no plano mestre | Pendente | §4 item 10 |
| 1.2 | Confirmar e definir a ligação conta↔aluno | Fluxo de gestão aprovado | Pendente | hoje só `g360:conciliar-vinculos` grava `vinculo_alunos` |
| 1.3 (MP-041) | Painel do aluno e "Minhas provas" liberados | Só os próprios dados; conferência por perfil | Parcial (código do painel existe; sem liberação) | `DashboardController::aluno` |

## 6. Contrato técnico (preliminar)
- **O que existe:** rota `GET /api/v1/dashboard/aluno` com `perfil:aluno`; o aluno é resolvido pelas referências do vínculo (`vinculo_alunos`, mesma escola e mesmo espaço); sem referência, responde "Aluno não encontrado." A matriz dá ao perfil `aluno` os padrões `resultado.ver` e `resultado.compartilhar`.
- **O que falta definir:** como a ligação é criada e desfeita pela gestão; idade mínima e consentimento; sessão e recuperação de senha para menores; volume (o número de contas cresce na ordem do número de alunos, relevante para o padrão de 1000 usuários simultâneos de FN-077).

## 7. Interface
`dashboard/aluno` já existe. "Minhas provas" e a navegação limitada do mockup precisam de conferência antes de liberar. Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 10 — Login do aluno (decisão 6.4 do docs/19) | Liberar agora / decidir com o portal | Plano mestre: decidir junto com FN-067, pois os dois tocam acesso individual de menores | Pendente |
| ⚠ Estado "Incerto" no plano mestre | — | Verificado no código: o painel funciona para vínculo de aluno com `vinculo_alunos`, mas o cadastro de membro com perfil `aluno` não cria essa ligação; só o comando de conciliação a grava. O docs/03 ("pendente") está desatualizado quanto ao mecanismo, não quanto à liberação | Registrar |
| Rota do painel usa `perfil:aluno` | Migrar para `permissao:` (FN-012, MP-031) | Tratar junto com as outras 7 rotas `dashboard/*` | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
