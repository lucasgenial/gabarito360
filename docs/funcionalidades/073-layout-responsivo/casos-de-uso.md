# 073 — Layout responsivo — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis autenticados e o visitante (ver [docs/03](../../03-perfis-e-permissoes.md))
**Atualizado em:** 2026-11-09

Funcionalidade transversal: os casos de uso descrevem garantias de apresentação válidas para todas as telas, não um fluxo de negócio próprio. Os fluxos de negócio de cada tela estão nas FNs donas e em [docs/07](../../07-casos-de-uso.md).

---

## UC-073-01 — Usar a aplicação em qualquer largura de tela

**Ator:** qualquer usuário autenticado ou visitante
**Permissão:** a da tela acessada
**Escopo:** o da tela acessada
**Pré-condições:** navegador moderno; larguras entre 375 e 3840 px.
**Pós-condições:** conteúdo legível e completo, sem rolagem lateral da página.
**Tela/rota:** todas as rotas do `apps/web`; layouts `layouts/app.blade.php`, `auth.blade.php`, `public.blade.php`.

**Fluxo principal:**
1. O usuário abre uma tela.
2. O conteúdo ocupa a largura útil da tela (decisão 2 do docs/23: tela toda), com formulários e textos corridos em coluna de leitura.
3. Grades de indicadores e blocos ganham colunas conforme a largura.

**Fluxos alternativos e de erro:**
- 2a. Celular (< 768 px): navegação vai para a barra inferior; tabelas viram cards (FN-074).
- 2b. Quadros comparativos (matrizes) têm rolagem própria dentro da região, não da página (FN-074).

**Regras de negócio:** docs/23 §4 e §6.

**Critérios de aceite:**
- [x] Nenhuma rolagem lateral da página em 375 px nas telas auditadas (L0, L8).
- [x] Conteúdo sem largura máxima fixa de 1280 px na área logada (L1, decisão 2).
- [x] Impressão A4 preservada em relatórios (L6).

**Situação:** Implementado — evidência: PR #25, #28–#34; `scripts/auditoria/medir.mjs`.

---

## UC-073-02 — Navegar pelo menu em qualquer perfil e largura

**Ator:** qualquer usuário autenticado
**Permissão:** os itens do menu respeitam as permissões do perfil ativo
**Escopo:** vínculo ativo
**Pré-condições:** sessão autenticada.
**Pós-condições:** menu estável, sem itens quebrando em duas linhas.
**Tela/rota:** `layouts/app.blade.php` (estrutura lateral e barra inferior).

**Fluxo principal:**
1. Em ≥ 768 px, o usuário vê o menu lateral com ícone e nome, recolhível para só ícones.
2. Em < 768 px, vê a barra inferior e a folha "Mais".

**Fluxos alternativos e de erro:**
- 1a. Nome longo da escola ou rede: aparece no contexto ativo, não espreme o menu.

**Regras de negócio:** decisões 1 e 3 do docs/23 (menu lateral; nada que remeta a "gov").

**Critérios de aceite:**
- [x] Mesmo menu em todos os perfis.
- [x] Ícones desenhados, sem emoji.
- [x] ⚠ A versão atual do menu é a estrutura TW2 (lateral 256/80 px), que substituiu a da L2 — mantida em [FN-075](../075-tailwind-ui/), subfase TW2.

**Situação:** Implementado — evidência: PR #29; estrutura atual em `apps/web/public/css/estrutura-app.css` (PR #223).

---

## UC-073-03 — Ler textos e números sem dados fixos ou falsos

**Ator:** qualquer usuário autenticado ou visitante
**Permissão:** a da tela
**Escopo:** o da tela
**Pré-condições:** —
**Pós-condições:** textos em pt-BR, sem números ou nomes inventados.
**Tela/rota:** painéis, login, página inicial.

**Fluxo principal:**
1. Datas, meses e mensagens padrão aparecem em português (`APP_LOCALE=pt_BR`).
2. Rede, oferta e turnos vêm do cadastro; o login não mostra estatísticas inventadas, "gov.br" nem aba "Cadastrar".

**Regras de negócio:** decisões 4 e 5 do docs/23.

**Critérios de aceite:**
- [x] Nenhum texto "Governo Federal", "gov.br" ou estatística fictícia no login e na home.
- [x] Mês em português nos painéis.

**Situação:** Implementado — evidência: PR #25, #34; `apps/web/.env.example`.

---

## UC-073-04 — Verificar o layout antes de cada PR

**Ator:** agente de execução ou desenvolvedor
**Permissão:** acesso ao repositório (operacional)
**Escopo:** branch em revisão
**Pré-condições:** branch enviado ao GitHub.
**Pós-condições:** relatório de rolagem lateral, erros de JavaScript e excesso de fotos pedidas por tela.
**Tela/rota:** `bash scripts/auditoria/rodar.sh <branch>`; CI: `python3 scripts/verificar_telas.py`.

**Fluxo principal:**
1. O agente roda a auditoria; as telas são geradas numa pasta temporária.
2. `medir.mjs` mede as telas em 6 larguras e aponta problemas.
3. A pasta temporária é apagada ao final; nada entra no repositório.

**Fluxos alternativos e de erro:**
- 2a. A CI roda `verificar_telas.py` (scripts Blade sem componentes e JavaScript válido) e `verificar_imagens.py` (sem prints versionados) em cada PR.

**Critérios de aceite:**
- [x] Nenhuma tela gerada é versionada (`trap 'rm -rf "$SAIDA"' EXIT`).
- [x] CI executa `verificar_telas.py`.
- [ ] ⚠ A auditoria usa cópia de desenvolvimento no CT 111; avaliar a migração para a homologação (registrado no planejamento, §8).

**Situação:** Implementado — evidência: `scripts/auditoria/rodar.sh`, `scripts/verificar_telas.py`, workflow da CI.
