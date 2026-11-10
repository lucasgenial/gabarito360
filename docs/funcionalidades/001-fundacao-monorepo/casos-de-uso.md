# 001 — Fundação do monorepo, banco e bases API/WEB — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** operador técnico (sem perfil na matriz do docs/03 (`docs/03-perfis-e-permissoes.md`)); clientes da API (WEB e, no futuro, apps móveis)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

A autenticação do usuário (UC-001 do docs/07 (`docs/07-casos-de-uso.md`)) é coberta por FN-011 e não é repetida aqui.

---

## UC-001-01 — Consultar a saúde da API e da WEB

**Ator:** operador técnico, monitor externo, roteiro de deploy (FN-003)
**Permissão:** nenhuma (rota pública)
**Escopo:** plataforma
**Pré-condições:** aplicação em execução.
**Pós-condições:** nenhuma alteração de estado.
**Tela/rota:** API `GET /api/v1/health`; WEB `GET /health` (`web.health`); Laravel `GET /up` nas duas aplicações.

**Fluxo principal:**
1. O cliente chama a rota de saúde.
2. A aplicação responde `200` com `{"status":"ok","app":"gabarito360-api"}` (ou `gabarito360-web`).

**Fluxos alternativos e de erro:**
- 2a. Aplicação em manutenção: Laravel responde `503`. O deploy (FN-003) exige `200` em `/api/v1/health` para considerar a publicação concluída.

**Regras de negócio:** a rota de saúde da API não gera evento de auditoria (FN-004).

**Critérios de aceite:**
- [x] `GET /api/v1/health` responde 200 com o JSON acima.
- [x] `GET /health` da WEB responde 200 com o JSON acima.
- [x] Nenhum dado de usuário ou de configuração é exposto.

**Situação:** Implementado — evidência: `apps/api/routes/api.php` (rota `health`), `apps/web/routes/web.php` (`web.health`).

---

## UC-001-02 — Responder em JSON padronizado na API

**Ator:** qualquer cliente da API
**Permissão:** conforme a rota chamada
**Escopo:** plataforma
**Pré-condições:** requisição para `api/*`.
**Pós-condições:** resposta JSON, nunca HTML.
**Tela/rota:** todas as rotas `api/v1/*`.

**Fluxo principal:**
1. O controller responde por `ApiResponse::success` (200 ou outro código informado, com `data` e `meta`).
2. Erros de regra respondem por `ApiResponse::error` (422 por padrão), `forbidden` (403), `notFound` (404) ou `unauthorized` (401).

**Fluxos alternativos e de erro:**
- 1a. Requisição sem autenticação em rota protegida: `401` em JSON, sem redirecionar para tela de login.
- 1b. Exceção não tratada em `api/*`: renderizada como JSON pelo manipulador de exceções.

**Regras de negócio:** "A API nunca renderiza telas. Apenas JSON." (CLAUDE.md, arquitetura oficial).

**Critérios de aceite:**
- [x] Nenhuma rota `api/*` devolve HTML, inclusive em erro.
- [x] Requisição não autenticada recebe 401 sem `Location`.

**Situação:** Implementado — evidência: `apps/api/app/Http/Responses/ApiResponse.php`, `apps/api/bootstrap/app.php` (`redirectGuestsTo`, `shouldRenderJsonWhen`).

---

## UC-001-03 — Preparar o banco de desenvolvimento

**Ator:** desenvolvedor do projeto
**Permissão:** acesso ao ambiente local (fora da aplicação)
**Escopo:** ambiente local ou CI
**Pré-condições:** MariaDB (ou SQLite nos testes) configurado no `.env` da API.
**Pós-condições:** esquema completo e dados fictícios de desenvolvimento.
**Tela/rota:** `php artisan migrate --seed` em `apps/api`.

**Fluxo principal:**
1. O desenvolvedor executa as migrations da API.
2. Os seeders criam rede, núcleos, escolas, turmas, alunos, usuários por perfil e planos fictícios.

**Fluxos alternativos e de erro:**
- 1a. Migrations da WEB: não representam o banco de negócio e não são executadas no deploy (FN-003).

**Regras de negócio:** a API é a dona do banco; migrations são aditivas.

**Critérios de aceite:**
- [x] Banco vazio chega ao esquema atual só com `migrate`.
- [x] Seeders não usam dados pessoais reais.

**Situação:** Implementado — evidência: `apps/api/database/migrations/2026_01_01_0000xx_*`, `apps/api/database/seeders/*`, commit `c1e2a1b`.

---

## UC-001-04 — Usar a WEB como cliente independente da API

**Ator:** WEB (BFF)
**Permissão:** a do usuário autenticado
**Escopo:** plataforma
**Pré-condições:** `API_BASE_URL` configurada na WEB.
**Pós-condições:** a WEB exibe dados obtidos só pela API.
**Tela/rota:** `App\Services\ApiClient` (WEB); login em `POST /login` → `POST /api/v1/auth/login` (detalhado em FN-011).

**Fluxo principal:**
1. A WEB recebe a ação do usuário.
2. A WEB chama a API pelo `ApiClient`, com o token da sessão no servidor.
3. A WEB renderiza a resposta.

**Fluxos alternativos e de erro:**
- 2a. API indisponível: a WEB mostra estado de erro (padrões de FN-076).

**Regras de negócio:** separação API × WEB (docs/12); credenciais nunca em URL (FN-018).

**Critérios de aceite:**
- [x] A WEB não consulta tabelas de negócio diretamente.
- [x] O endereço da API vem de configuração (`API_BASE_URL`), não de código fixo.

**Situação:** Implementado — evidência: `apps/web/config/services.php`, `apps/web/app/Services/ApiClient.php`, commit `9906f71`; contrato de fronteira em `ContratoApiIndependenteTest` (docs/173).
