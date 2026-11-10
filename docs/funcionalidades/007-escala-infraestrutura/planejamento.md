# 007 — Limites, armazenamento externo, separação e observabilidade — Planejamento

**Estado no tronco:** Pendente
**Prioridade no plano mestre:** trilha contínua de qualidade (aplicada dentro das entregas, sem fila própria); medição com 1000 usuários antes de qualquer abertura comercial (FN-077)
**Natureza:** evolução de existente (há partes isoladas no código: limites por rota, Turnstile no contato, grupos PHP separados, mutex local de sessão)
**Origem:** tronco original — docs/28 fase 9 (X3–X6); AP1-L (docs/176); mutex de sessão do docs/167 (UR3); separação física da API (docs/172, 173)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Preparar a plataforma para crescer sem perder segurança nem disponibilidade: limites de requisição por rota e na borda, fotos fora do servidor, serviços separáveis e observabilidade com alertas, na ordem e na medida que a medição de carga (FN-077) indicar.

## 2. Escopo
- **Inclui:**
  - X3 — limites por usuário e IP em todas as rotas (AP1-L: inventário de 93 rotas), WAF da Cloudflare, Turnstile nas rotas públicas.
  - X4 — fotos (cartões, imagens de perfil e de alunos) em armazenamento externo (Cloudflare R2 avaliado), mantendo links assinados.
  - X5 — separação de serviços: workers em contêiner próprio, réplica de leitura, mais de uma instância da WEB; hoje só os grupos PHP-FPM da WEB e da API estão separados.
  - X6 — logs estruturados, rastreio de erros, métricas, alertas e página de saúde.
  - Mutex de sessão distribuído, antes de mais de um host da WEB.
- **Não inclui:**
  - X1 medição e X2 banco e consultas → FN-077 (desempenho).
  - Painel técnico de filas e falhas → [FN-006](../006-painel-tecnico/planejamento.md).
  - Backups e restauração → [FN-002](../002-base-operacao/planejamento.md) e [FN-003](../003-publicacao-e-homologacao/planejamento.md).

## 3. Fontes de verdade
- Mockups: não se aplica (infraestrutura). O widget Turnstile segue o formulário existente do Fale Conosco.
- Documentos de origem: [docs/28](../../28-plano-mestre-proximos-modulos.md) fase 9, [docs/167](../../167-ur3-rotas-app-contexto-sessao.md), [docs/172](../../172-ap1-plano-desempenho-e-contrato-api.md), [docs/173](../../173-ap1-contrato-http-e-operacao-independente.md), [docs/176](../../176-reconciliacao-solicitacoes.md), [docs/operacao/deploy.md](../../operacao/deploy.md), [docs/12](../../12-arquitetura.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) (limites não mudam autorização).
- Plano mestre: [§2 FN-007](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-077 desempenho (X1) | docs/28: X3, X4 e X5 dependem da medição; "só se a medição pedir" | Parcial (X1 Pendente) |
| Decisão §4 item 8 | Identidades para a carga de 1000 usuários simultâneos | Pendente |
| FN-002 base-operacao (B4, B6) | X6 depende de B4; X1 de B6 | Concluída (com ressalva) |
| FN-018 seguranca-urls-sessao | Mutex `flock` da sessão (UR3) a substituir antes de múltiplos hosts | Em homologação |

**Funcionalidades que dependem desta:** FN-046 e FN-037 (fotos passariam ao armazenamento externo em X4); FN-006 (painel técnico pode consumir a observabilidade de X6); FN-077 (as correções de escala que a medição apontar). Nenhuma está bloqueada hoje.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 7.1 (X3; AP1-L) Limites por rota, WAF, Turnstile | Limite em todas as rotas por usuário e IP; WAF; Turnstile nas rotas públicas | Inventário de 93 rotas tratado sem mudar contrato | Pendente — ⚠ o código já tem limites em parte das rotas | `throttle` em 84 linhas de `apps/api/routes/api.php` e 11 de `apps/web/routes/web.php`; 4 limitadores nomeados em `AppServiceProvider`; `POST /api/v1/auth/login` e `POST /login` sem limite; Turnstile só no Fale Conosco, atrás de `TURNSTILE_ENABLED` |
| 7.2 (X4) Fotos em armazenamento externo | Disco externo do Laravel com links assinados | Fotos migradas sem quebrar links | Pendente | Fotos de cartões no disco `public` local (`FilaCartoes`) |
| 7.3 (X5) Separação de serviços | Grupos PHP-FPM; depois workers próprios, réplica, várias WEB | Separação física conforme a medição | Parcial | Grupos `www` (WEB) e `api`, 12 processos cada, desde 30/10 (docs/operacao/deploy.md); separação física adiada |
| 7.4 (X6) Observabilidade | Logs estruturados, rastreio de erros, métricas, alertas, página de saúde | Alertas ativos e testados | Pendente | Só rotas de saúde e logs locais |
| 7.5 Mutex de sessão distribuído | Exclusão entre processos válida em vários hosts | Teste adversarial em mais de um host | Pendente | `SerializarSessao` usa `flock` local (docs/167) |

## 6. Contrato técnico (proporcional ao risco)
- **Limites atuais:** `throttle:N,1` por rota e limitadores nomeados `preferencias-paineis` (GET 60, escrita 20 por minuto, por usuário e método), `imagem-perfil` (10/min por usuário e rota), `catalogo-operacao` (GET 60; escrita 10, solicitações 5, por usuário e rota), `painel-turma-pdf` (5/min por usuário). Resposta padrão do Laravel: `429`.
- **Turnstile:** `App\Services\Turnstile` valida `cf-turnstile-response` quando `TURNSTILE_ENABLED=true`, com lista de hostnames aceitos; só usado em `LeadController` (API e WEB).
- **Sessão:** `SerializarSessao` (WEB) trava por arquivo com HMAC do SID em storage persistente, espera até 15 s e responde `423` "Há outra operação em andamento. Tente novamente."; não atende múltiplos hosts com sistemas de arquivos separados (docs/167).
- **Requisitos de X5 (docs/28):** aplicação sem estado local — sessão e cache no Redis (já atendido, FN-002) e arquivos externos (X4).
- **AP1-L:** preservar contrato, valores e janelas existentes, autorização e isolamento de consumidores (docs/176).
- **Desempenho:** toda medição segue o padrão de 1000 usuários simultâneos, sem simulação.

## 7. Interface
Sem telas próprias. O Turnstile, se estendido a outros formulários públicos (login, esqueci a senha, convite), deve seguir o padrão visual do Fale Conosco e ter estados de erro claros.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 8 — identidades para a carga de 1000 usuários simultâneos | Contas sintéticas por comando só na VM 120; outra fonte | Contas sintéticas geradas por comando na homologação e gerador fora da VM | Pendente |
| ⚠ Divergência: o plano mestre diz "throttle pontual só em L4"; o código tem limites em boa parte das rotas da API (autenticação pública, catálogo, cópias, disciplinas, preferências, PDFs, documentos, contato) | Atualizar o plano mestre; refazer o inventário AP1-L | Refazer o inventário das rotas sem limite antes de executar 7.1 | Pendente (não corrigido aqui, por regra) |
| Login da API e da WEB sem limite de tentativas | Antecipar só esse ponto de X3; esperar X3 completo | Avaliar antecipação por ser rota pública de autenticação (tema também de FN-011) | Pendente |
| Ordem de X5 condicionada à medição | Seguir docs/28; antecipar | Seguir docs/28: só se X1 pedir | Sem decisão necessária agora |
| Custo e LGPD de armazenamento externo de fotos (X4) | R2; outro provedor; manter local | Avaliar junto com o pacote de retenção (FN-062) e a eliminação de fotos (FN-066) | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
