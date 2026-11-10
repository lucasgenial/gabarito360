# 001 — Fundação do monorepo, banco e bases API/WEB — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** sem posição na fila (concluída); nenhuma pendência no §3 (apps móveis → FN-010)
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/13 (MP-001..005), docs/12, docs/14, docs/15
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar ao Gabarito360 a estrutura mínima sobre a qual todas as funcionalidades são construídas: monorepo com a API Laravel (dona do banco, só JSON) e a WEB Laravel (interface, consumidora da API), o esquema inicial do MariaDB com seeders de desenvolvimento e a base de autenticação, resposta padronizada e layout.

## 2. Escopo
- **Inclui:**
  - Estrutura `apps/api` e `apps/web` (MP-001, parcial).
  - Documentação consolidada inicial (MP-002).
  - Migrations e seeders do MVP: redes, núcleos, escolas, turmas, alunos, usuários, escopos, provas, gabaritos e cartões (MP-003).
  - API base com Sanctum, envelope `ApiResponse`, exceções em JSON para `api/*`, `401` sem redirecionamento e rota de saúde (MP-004).
  - WEB base com layout e login pela API, cliente HTTP `ApiClient` com `API_BASE_URL` e rota de saúde (MP-005).
- **Não inclui:**
  - Aplicativos Android e iOS → [FN-010](../010-app-mobile/planejamento.md).
  - Autenticação, conta, recuperação de senha e convites → FN-011.
  - Motor de permissões e menus → FN-012.
  - Segurança de URLs, sessão e respostas (`RecusarCredenciaisNaUrl`, `ProtegerRespostas`) → FN-018.
  - Estrutura-alvo de diretórios (`apps/api/plataforma/`) → [FN-009](../009-estrutura-diretorios-alvo/planejamento.md).

## 3. Fontes de verdade
- Mockups: `mockups/login.html` (base da tela de login; ver [docs/01](../../01-inventario-dos-mockups.md)).
- Documentos de origem: [docs/13](../../13-roadmap.md) (MP-001..005), [docs/12](../../12-arquitetura.md), [docs/14](../../14-plano-de-execucao.md), [docs/15](../../15-plano-de-commits.md), [docs/00](../../00-project-rules.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-001 do [docs/07](../../07-casos-de-uso.md) (autenticação, detalhada em FN-011).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) (não há permissão própria desta funcionalidade).
- Plano mestre: [§2 FN-001](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| — | Funcionalidade-base | — |

**Funcionalidades que dependem desta:** todas. Em especial FN-002 (operação), FN-008 (CI), FN-010 (apps móveis consumirão a mesma API) e FN-011 (autenticação).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (MP-001) Estrutura do monorepo | `apps/api`, `apps/web` | Diretórios oficiais criados | Parcial | Só `apps/api` e `apps/web` existem; `apps/android` e `apps/ios` nunca foram criados (→ FN-010) |
| 1.2 (MP-002) Documentação consolidada | docs 00–17 | Documentos de base publicados | Entregue (produção) | docs/13 |
| 1.3 (MP-003) Migrations e seeders do MVP | migrations `2026_01_01_0000xx`, seeders `Rede`, `Nucleo`, `Escola`, `Turma`, `Aluno`, `Usuario`, `Plano` | `migrate --seed` cria a base de desenvolvimento | Entregue (produção) | commit `c1e2a1b` |
| 1.4 (MP-004) API base | Sanctum, `ApiResponse`, `GET /api/v1/health` | Login e resposta JSON padronizada | Entregue (produção) | commit `c20691f` |
| 1.5 (MP-005) WEB base | Layout, login via API, `GET /health` | Usuário entra pela WEB autenticando na API | Entregue (produção) | commit `9906f71` |

## 6. Contrato técnico (proporcional ao risco)
- API: prefixo `/api/v1`; `GET /api/v1/health` responde `{"status":"ok","app":"gabarito360-api"}`; o Laravel expõe também `/up` nas duas aplicações. A API não tem tela de login: requisição sem autenticação recebe `401` (`redirectGuestsTo(null)`); exceções em `api/*` são renderizadas como JSON (`apps/api/bootstrap/app.php`).
- Envelope: `App\Http\Responses\ApiResponse` com `success`, `error` (422 padrão), `unauthorized` (401), `forbidden` (403) e `notFound`.
- WEB: `App\Services\ApiClient` usa `services.api.base_url` (`API_BASE_URL`); a WEB não acessa o banco de negócio. As migrations padrão da WEB não representam o banco de negócio (o deploy só migra a API, ver FN-003).
- Banco: MariaDB; 95 migrations na data deste documento, todas aditivas a partir do esquema MP-003.

## 7. Interface
Base visual inicial substituída depois por FN-070 (identidade visual), FN-073 (layout responsivo) e FN-075 (Tailwind). Nenhuma tela própria a conferir nesta funcionalidade.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 do plano mestre — aprovação do plano e da priorização | Aprovar §1–§3 como inventário | Aprovar §1–§3 e a Onda 0 | Pendente |
| docs/13 marca MP-001 "Concluído", mas android/ios não existem (§6 do plano mestre) | Manter o docs/13 como histórico | Vale o plano mestre: MP-001 Parcial, apps móveis em FN-010 | Registrado no plano mestre |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
