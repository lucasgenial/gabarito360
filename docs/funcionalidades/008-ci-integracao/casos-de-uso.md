# 008 — Integração contínua (MariaDB) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** agentes de execução e desenvolvedores (autores de PR); operador técnico (confere o CI antes de publicar). Nenhum perfil da matriz do docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve. O docs/07 (`docs/07-casos-de-uso.md`) não tem caso de uso equivalente.

---

## UC-008-01 — Verificar um PR ou um push na `main`

**Ator:** autor do PR; GitHub Actions
**Permissão:** acesso de escrita ao repositório (fora da aplicação)
**Escopo:** repositório
**Pré-condições:** PR aberto ou push na `main`.
**Pós-condições:** quatro jobs com resultado visível no PR: `auditoria-recuperacao`, `telas`, `testes` (matriz API e WEB) e `integridade-mariadb`.
**Tela/rota:** `.github/workflows/verificacao.yml` (workflow "Verificação").

**Fluxo principal:**
1. O evento dispara o workflow.
2. `telas` confere telas e imagens, reconstrói os artefatos gerados e roda os testes Python, Node e de navegador.
3. `testes` audita dependências (`composer audit --locked --no-dev`) e roda `php artisan test` na API e na WEB.
4. `integridade-mariadb` e `auditoria-recuperacao` rodam em serviços descartáveis.

**Fluxos alternativos e de erro:**
- 2a. Artefato gerado diferente do versionado: `git diff --exit-code` falha o job.
- 3a. Dependência de runtime com vulnerabilidade conhecida: o job falha antes dos testes.

**Regras de negócio:** governança §3 — subfase concluída exige CI; FN-003 só publica SHA com CI conferido pelo operador.

**Critérios de aceite:**
- [x] Todo PR recebe os quatro jobs.
- [x] Falha em qualquer job fica visível antes do merge.

**Situação:** Implementado — evidência: `.github/workflows/verificacao.yml`.

---

## UC-008-02 — Rodar os testes de integridade em MariaDB descartável

**Ator:** GitHub Actions (job `integridade-mariadb`)
**Permissão:** não se aplica
**Escopo:** contêiner de serviço da CI
**Pré-condições:** serviço MariaDB 10.11 com dados em `tmpfs`.
**Pós-condições:** testes de integridade e concorrência executados em MariaDB real.
**Tela/rota:** job `integridade-mariadb`; guarda `scripts/ci/verificar_mariadb_descartavel.py`.

**Fluxo principal:**
1. A guarda confere que o armazenamento do MariaDB é descartável e a configuração de commits.
2. Roda `ProgressoCorrecaoTest` primeiro.
3. Roda a lista explícita de testes por `--filter`.

**Fluxos alternativos e de erro:**
- 1a. Armazenamento não descartável: a guarda falha o job.

**Critérios de aceite:**
- [x] Tempo do job reduzido de 29m44 para 20m15 (PR #217).
- [x] −28,9 % de consultas após remover validações redundantes (PR #217, #218).
- [ ] ⚠ Artefato final `mariadb-ci-entrega-final.json` sem localização confirmada. Resolve: subfase 8.4.
- [ ] ⚠ Teste novo de MariaDB precisa ser incluído à mão na lista `--filter`.

**Situação:** Implementado — evidência: `.github/workflows/verificacao.yml`, `scripts/ci/verificar_mariadb_descartavel.py`, docs/181 (`docs/181-desempenho-mariadb-ci.md`).

---

## UC-008-03 — Ensaiar a recuperação da auditoria e a indisponibilidade da fila

**Ator:** GitHub Actions (job `auditoria-recuperacao`)
**Permissão:** não se aplica
**Escopo:** contêineres de serviço da CI (MariaDB e Redis)
**Pré-condições:** serviços saudáveis.
**Pós-condições:** restauração conferida; comportamento com Redis parado conferido.
**Tela/rota:** job `auditoria-recuperacao`; `scripts/auditoria/ensaio-fila.php`, `ensaio-copias.php`, `ensaio-restauracao.sh`.

**Fluxo principal:**
1. Para o Redis descartável e confere os ensaios de fila e de cópias com a fila indisponível.
2. Religa o Redis e espera a resposta.
3. Restaura banco e fila em contêineres novos e confere.

**Critérios de aceite:**
- [x] Falha da fila não derruba a operação auditada (regra de FN-004, UC-004-01).
- [x] Restauração em contêineres novos confere.

**Situação:** Implementado — evidência: `.github/workflows/verificacao.yml`; caso de uso de origem em [FN-004](../004-auditoria/casos-de-uso.md) (UC-004-04).

---

## UC-008-04 — Bloquear prints e imagens indevidas no repositório

**Ator:** GitHub Actions (job `telas`)
**Permissão:** não se aplica
**Escopo:** repositório
**Pré-condições:** PR com arquivos de imagem.
**Pós-condições:** PR com imagem fora dos locais permitidos falha.
**Tela/rota:** `scripts/verificar_imagens.py`.

**Fluxo principal:**
1. O script percorre os arquivos versionados.
2. Aceita imagens só como assets da aplicação (`apps/web/public/`, `apps/web/resources/images/`) ou desenhos de referência (`mockups/`, `style-system/`).

**Fluxos alternativos e de erro:**
- 2a. Imagem em `docs/` ou outro local: o job falha.

**Regras de negócio:** CLAUDE.md, "Prints e imagens da aplicação" (decisão de 09/11/2026).

**Critérios de aceite:**
- [x] Nenhum print da aplicação entra pelo PR.

**Situação:** Implementado — evidência: `scripts/verificar_imagens.py`, job `telas`.
