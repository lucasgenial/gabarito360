# 011 — Autenticação e conta — Planejamento

**Estado no tronco:** Parcial
**Prioridade no plano mestre:** sem posição própria nas ondas do §5. As pendências seguem por outras frentes: testes reais de E6 em FN-060 (Onda 2), autocadastro na decisão §4 item 5 (FN-030).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/13 MP-012, docs/04 RF-001, docs/28 E3/E6
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que qualquer pessoa com conta ativa entre no Gabarito360 com e-mail e senha, recupere a senha, troque a senha (inclusive a troca obrigatória definida pelo gestor) e troque o e-mail de acesso com confirmação. A conta é a identidade de autenticação. O que a pessoa pode fazer depois de entrar depende do vínculo escolhido (FN-015) e do motor de permissões (FN-012).

## 2. Escopo
- **Inclui:** login e saída; "Esqueci a senha" e redefinição por código; troca obrigatória de senha no primeiro acesso ou por exigência do gestor; troca voluntária de senha; troca de e-mail com confirmação (E6); aviso por e-mail de senha e e-mail alterados.
- **Não inclui:**
  - Autocadastro com aceite LGPD (RF-001.2, MP-012): substituído pelo acesso por convite. Ver FN-030 e a decisão §4 item 5.
  - Convite de primeira conta e convite de vínculo: FN-015.
  - Escolha de vínculo ou de acesso interno depois do login: FN-015 e FN-013.
  - Sessão única por vínculo, credenciais fora da URL, mutex de sessão: FN-018.
  - Edição do nome e da imagem de perfil: FN-019.
  - Motor de e-mail, modelos e DMARC: FN-060.
  - Aceite de termos e privacidade no login: FN-061.
  - Login gov.br (RF-001.3): sem implementação; não há subfase planejada (ver §8).
  - Limite de tentativas no login: FN-007 (X3), ver risco no §8.

## 3. Fontes de verdade
- Mockup: `mockups/login.html` ([inventário §01](../../01-inventario-dos-mockups.md)).
- Origem: [docs/04 RF-001](../../04-requisitos-funcionais.md), [docs/13](../../13-roadmap.md) MP-012, [docs/28](../../28-plano-mestre-proximos-modulos.md) E3/E6, [docs/165](../../165-ur1-seguranca-e-urls.md) (UR1-B: código no corpo do e-mail).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-001 de [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md).
- Código: `apps/api/app/Http/Controllers/Api/AuthController.php`, `apps/api/app/Services/ContaUsuario.php`, `apps/api/app/Http/Middleware/SenhaTrocada.php`, `apps/web/app/Http/Controllers/Auth/LoginController.php`, `apps/web/app/Http/Controllers/PerfilController.php`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-001 fundacao-monorepo | API base, tabela `usuarios`, Sanctum | Concluída |
| FN-060 email-transacional | Envio do código de recuperação, confirmação de e-mail e avisos | Parcial (E3 em produção; E6 sem teste real) |
| FN-018 seguranca-urls-sessao | UR1-B: código no corpo do e-mail e confirmação só por POST | Em homologação |
| FN-063 lgpd-protecao-dados | E-mail e CPF cifrados com índice (`email_hash`) | Concluída |

**Funcionalidades que dependem desta:** FN-012, FN-013, FN-015, FN-018, FN-019 e, na prática, toda funcionalidade autenticada.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (MP-012, E3) | Esqueci/redefinir senha | Código de 64 caracteres no corpo do e-mail, validade de 60 min, sessões anteriores encerradas | Entregue (produção) | commit 3506ab4; docs/28 E3 |
| 1.2 (E6) | Confirmação e troca de e-mail | Endereço atual vale até confirmar; código de 24 h; aviso ao e-mail antigo | Entregue (código), sem teste real | PR #54 |
| 1.3 (MP-012) | Autocadastro com aceite LGPD | — | Substituída | sem rota `/cadastro`; acesso por convite (FN-015); ver FN-030 |
| 1.4 (RF-001.3) | Login gov.br | — | Incerto no plano mestre; nenhuma integração no código (ver §8) | grep sem resultado em `apps/*/app` |
| 1.5 | Teste real de E6 com caixa de e-mail | Recibo textual do envio e da confirmação | Pendente | tratado em FN-060 (Onda 2) |

## 6. Contrato técnico (proporcional ao risco)
- **API (pública, prefixo `/api/v1/auth`):** `POST login`, `POST esqueci-senha` (throttle 5/min), `POST redefinir-senha` (10/min), `POST confirmar-email` (10/min). **Autenticada:** `POST logout`, `GET me`, `PUT me` (nome e senha), `POST email` (5/min).
- **Web (BFF):** `GET|POST /login`, `GET|POST /esqueci-senha`, `GET|POST /redefinir-senha`, `GET|POST /confirmar-email`, `POST /logout`, `GET|PUT /app/trocar-senha`, `PUT /perfil/senha`, `POST /perfil/email`.
- **Dados:** `usuarios` (e-mail cifrado com `email_hash`, `forcar_troca_senha`, `ultimo_acesso`), `password_reset_tokens` (chave = hash do e-mail, token com hash), `trocas_email` (novo e-mail cifrado, `token_hash`, `expira_em`).
- **Tokens:** login cria token Sanctum `api-token`, ou `troca-senha` quando a troca é obrigatória; o middleware `SenhaTrocada` restringe esse token a `auth/me` e `auth/logout`. `sanctum.expiration` é nulo; a sessão web expira por `SESSION_LIFETIME` (120 min por padrão).
- **Privacidade:** "Esqueci a senha" sempre responde a mesma mensagem (sem enumeração). Códigos nunca na URL; `GET` com `token` na query é recusado.
- **Desempenho:** sem cache; operações unitárias. Não há medição no padrão de 1000 usuários (FN-077).

## 7. Interface
Telas `auth/login`, `auth/esqueci-senha`, `auth/redefinir-senha`, `auth/trocar-senha`, `auth/confirmar-email` e a seção de senha/e-mail de `perfil/show`. Mensagens de erro em português, sem revelar se o e-mail existe. Conferência visual registrada por texto, sem prints. O mockup prevê abas Login/Cadastro, que não existem porque o cadastro foi substituído.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 5 — Autocadastro e SaaS | Manter substituído por convite / retomar autocadastro | Plano mestre: declarar MP-028 substituído e registrar a contratação assistida no docs/06 | Pendente |
| Login gov.br (RF-001.3) | Planejar como candidata / arquivar | Plano mestre: "verificar gov.br". O código não tem nenhuma integração; o docs/04 só previa a mensagem na tela | Pendente |
| ⚠ Login sem limite de tentativas | Throttle na rota da API e da web / WAF (FN-007 X3) | Divergência encontrada: `POST /api/v1/auth/login` e `POST /login` não têm `throttle`, ao contrário das demais rotas públicas de autenticação | Pendente |
| ⚠ "Manter conectado" sem efeito | Implementar sessão longa / retirar a caixa | A caixa `lembrar` existe na tela, mas `LoginController::store` não a usa | Pendente |
| ⚠ Senha mínima | RF-001.1 diz 6 caracteres; o código exige 8 em toda criação e troca | Registrar 8 como regra vigente no docs/06 | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
