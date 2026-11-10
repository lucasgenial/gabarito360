# 018 — Segurança de URLs, sessão e dependências (UR1–UR4) — Planejamento

**Implementação:** Em homologação
**Etapa:** Em homologação
**Prioridade no plano mestre:** Onda 0, se aprovada a decisão §4 item 2: primeiro bloco da promoção à produção (UR1–UR4, PR #194 e hotfix de leituras 500). Fila de produção no §7.
**Natureza:** existente documentada a partir do código
**Origem:** docs/165–171; priorizada pelo responsável durante a conferência de uma turma na homologação
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Tirar das URLs tudo o que identifica acesso ou recurso: códigos de e-mail, contexto de vínculo, assinaturas de arquivo e identificadores de registros. Garantir um único vínculo por sessão, com o mesmo ciclo de vida dela, e manter as dependências sem vulnerabilidades conhecidas.

## 2. Escopo
- **Inclui:** UR1 (e-mails com endereço fixo e código no corpo, confirmação só por POST, fotos por proxy autenticado, links assinados desativados); UR2 (atualização de dependências e `composer audit` na CI); UR3 (navegação em `/app`, um vínculo por sessão, revisão de acesso, mutex de sessão, fotos e PDF por fetch+Blob); UR4 (recursos sem identificadores na URL, catálogo de operações, referência no corpo); UR4-R1 e sessão única (contexto perdido leva a novo login); UR2-P (hotfix de dependências para a versão de produção).
- **Não inclui:**
  - Login, senha e e-mail em si: FN-011.
  - Modelo de vínculos e convites: FN-015.
  - Limites por rota, WAF e mutex distribuído: FN-007.
  - Otimizações de desempenho do UR4 (F4-C, gravação em lote): FN-077.
  - Estabilização da turma pós-UR3 (ST1): FN-077.
  - Carregamento e estados de erro: FN-076.

## 3. Fontes de verdade
- Mockups: sem mudança visual de mockup; telas de apoio `auth/acesso-alterado`, `navegacao/selecionar`, `navegacao/estrutura`, `navegacao/redirecionar`.
- Origem: [docs/165](../../165-ur1-seguranca-e-urls.md), [docs/166](../../166-ur2-dependencias-seguranca.md), [docs/167](../../167-ur3-rotas-app-contexto-sessao.md), [docs/169](../../169-ur4-recursos-sem-identificadores-url.md), [docs/170](../../170-ur4-recuperacao-vinculo-expirado.md), [docs/171](../../171-ur4-sessao-e-vinculo-unicos.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) (vale para todos os perfis).
- Código: `apps/web/app/Http/Middleware/` (`RecusarCredenciaisNaUrl`, `SerializarSessao`, `RevisaoAcesso`, `ExigirSessaoIntegra`, `Autenticado`, `RecursoNavegacao`, `RespostaNavegacao`, `ContextoPorAba`, `ProtegerRespostas`), `apps/web/app/Support/` (`ContextoWeb`, `SessaoWeb`, `NavegacaoWeb`, `RotasNavegacao`, `UrlContextual`), `apps/api/app/Http/Middleware/RecusarCredenciaisNaUrl.php`, `ProtegerRespostas.php`, `.github/workflows/verificacao.yml`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-015 vinculos-contexto | O contexto transportado é o vínculo selecionado (V2) | Concluída |
| FN-011 autenticacao-conta | Fluxos de código por e-mail | Parcial |
| FN-003 publicacao-e-homologacao | Promoção à produção por decisão expressa | Concluída |

**Funcionalidades que dependem desta:** FN-036 (TU1 retomou depois do UR4), FN-077 (ST1, F4-C), FN-076, FN-007 (mutex distribuído antes de múltiplos hosts) e todas as telas autenticadas, que passam pelo motor de navegação.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (UR1-A) | Contexto no path (`/acessos/vinculo-N/...`) | — | Substituída por UR3 | PR #192 |
| 1.2 (UR1-B..E) | E-mails sem token na URL; proxy de fotos; regressão e homologação | GET inerte; POST com CSRF | Entregue (homologação) | PR #192 |
| 2.1 (UR2) | Atualização de dependências + `composer audit` | CI falha com vulnerabilidade conhecida | Entregue (homologação) | PR #193 |
| 2.2 (UR2-P) | Hotfix de dependências para a versão de produção | Decisão e merge | Pendente | PR #194 aberto desde 05/11 |
| 3.1 (UR3) | `/app`, um vínculo por sessão, revisão de acesso, mutex, fetch+Blob | — | Entregue (homologação) | PR #195 |
| 4.1 (UR4) | Recursos sem identificadores na URL | Referência no corpo por entrada de histórico | Entregue (homologação) | PR #197 |
| 4.2 (UR4-R1) | Recuperação de vínculo expirado | — | Entregue (homologação); em parte substituída pela sessão única | PR #198 |
| 4.3 (UR4 sessão única) | Vínculo com o ciclo de vida da sessão | Contexto perdido → novo login | Entregue (homologação) | PR #199 |
| 5.1 | Validação do responsável e produção | Decisão expressa | Pendente | §7 do plano mestre |

## 6. Contrato técnico (proporcional ao risco)
- **Credenciais fora da URL:** API e web recusam `token` e similares na query; telas de convite, redefinição e confirmação redirecionam sem os parâmetros.
- **Sessão:** mutex por arquivo (`flock`), sem TTL, exige topologia `single-host-shared-filesystem`; concorrência responde 423 "Há outra operação em andamento. Tente novamente." Antes de múltiplos hosts, trocar por solução distribuída (FN-007).
- **Revisão de acesso:** cada formulário e chamada leva a revisão do acesso (`X-G360-Revisao`); divergência responde 409 com a tela `auth/acesso-alterado`.
- **Navegação UR4:** GET em `/app/...` com query é recusado (409/422); a seleção de recurso vai no corpo de POST, depois de CSRF e revisão; parâmetros são validados e precisam coincidir com a tela.
- **Respostas:** `Cache-Control: private, no-store`, `Referrer-Policy: no-referrer`, `X-Content-Type-Options: nosniff`; arquivos binários só por fetch autenticado.
- **Rotas legadas:** nomes `legado.*` e `/acessos/{acesso}` só redirecionam; `cartoes/{id}/arquivo/{tipo}` responde 404.
- **Desempenho:** o custo do motor por requisição foi tratado em FN-077 (F4-C).

## 7. Interface
Sem telas novas de negócio. Mensagens: "Entre novamente para continuar.", "Escolha seu vínculo para continuar.", "Abra este recurso pela tela correspondente.", "Seleção inválida.". Conferência por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — Gate de produção e fila acumulada | Revogar o gate L4 em favor do ADR-H1; promover em blocos | Plano mestre: começar pela segurança (UR1–UR4, PR #194, hotfix de leituras 500) | Pendente |
| §4 item 16 — PR #194 (UR2-P) | Mesclar junto com a promoção / aplicar o hotfix isolado | Plano mestre: decidir com o item 2; se a produção demorar, aplicar isolado | Pendente |
| Mutex `flock` em um único host | Manter / solução distribuída | Manter até haver mais de um host (FN-007) | — |

Nenhuma divergência entre o plano mestre e o código encontrada.

## 9. Ramos desta entrega
UR1-A (contexto no path) e parte do UR4-R1 foram substituídos dentro da própria série. O hotfix de assinatura do cartão inteiro (docs/153, FN-046) ficou obsoleto com UR1/UR3.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
