# 024 — Cópias entre espaços (T5) — Casos de uso

**Natureza:** existente documentada a partir do código (funcionalidade substituída em política)
**Perfis envolvidos:** Administrador da Rede, Diretor Escolar, professor autônomo (`copia.autorizar_origem`, `copia.autorizar_destino`). Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Registro resumido dos fluxos que existem no código. ⚠ Todos seguem a **política antiga**, declarada substituída pelo docs/115 §9; o destino de código e flags depende do §4 itens 3 e 4 do [plano mestre](../../planejamento/00-plano-mestre.md). Os fluxos equivalentes da política nova estão em FN-025 (TR1/TE1/UX1).

---

## UC-024-01 — Acompanhar cópias do contexto

**Ator:** perfil com `copia.autorizar_origem` ou `copia.autorizar_destino`
**Permissão:** `copia.autorizar_origem` | `copia.autorizar_destino`
**Escopo:** espaço do vínculo (rede, escola ou pessoal)
**Pré-condições:** `COPIAS_ATIVAS` e interface ativas.
**Tela/rota:** `/app/copias`; `GET /api/v1/copias`.

**Fluxo principal:** 1. O ator abre "Cópias". 2. Vê as operações do seu contexto, somente leitura, com o estado de cada uma.

**Situação:** Implementado (T5-E1, PR #125) — ⚠ aceite visual pendente (§4 item 4); política substituída → FN-025 (UX1-A).

---

## UC-024-02 — Selecionar, conferir e enviar materiais (provas e gabaritos)

**Ator:** responsável pela origem
**Permissão:** `copia.autorizar_origem`; destino confirma com `copia.autorizar_destino`
**Escopo:** origem e destino
**Tela/rota:** `/app/copias/materiais`, `/app/copias/receber-material`; `GET/POST /api/v1/copias/materiais*`, `POST /api/v1/copias/recebimentos`.

**Fluxo principal:**
1. Destino emite código de recebimento (256 bits, 15 minutos).
2. Origem seleciona materiais, gera a prévia `material-1` e informa o código.
3. Revisão humana `sem_dados_pessoais`; as duas partes aprovam o lote.
4. O executor publica por unidade a partir do staging cifrado.

**Fluxos alternativos e de erro:** código expirado ou de outro destino → recusado; repetição com a mesma chave não duplica.

**Situação:** Implementado (T5-B1/B2, E2a/E2b) — ⚠ política substituída → FN-025 (TR1/UX1-B); duplicação no mesmo espaço → FN-042.

---

## UC-024-03 — Copiar cadastro mínimo (turmas e alunos)

**Ator:** responsáveis pela origem e pelo destino
**Permissão:** `copia.autorizar_origem` / `copia.autorizar_destino`
**Tela/rota:** `/app/copias/cadastros`, `/app/copias/receber-cadastro`; `/api/v1/copias/cadastros*`.

**Fluxo principal:** prévia por tipo de destino → evidência cifrada → aprovações → execução com conciliação de matrícula (`LotesCadastroCopia`, `ProcessarCopiaCadastro`).

**Situação:** Implementado (T5-C1..C3, E2c1/E2c2) — ⚠ complemento de nascimento no destino escolar pendente; política substituída → FN-025 (TE1).

---

## UC-024-04 — Copiar resultados identificados

**Ator:** responsáveis pela origem e pelo destino
**Permissão:** `copia.autorizar_origem` / `copia.autorizar_destino`
**Tela/rota:** `/app/copias/resultados`, `/app/copias/receber-resultados`; `/api/v1/copias/resultados*`.

**Fluxo principal:** prévia de resultados (`PreviaResultadosCopia`) → pacote histórico cifrado com evidência `t5-resultados-v1` → aprovações → publicação em acervo separado.

**Situação:** Implementado (T5-D2..D3, E2c3/E2c4) — ⚠ política substituída → FN-025 (TE1-C snapshot/acervo).

---

## UC-024-05 — Solicitar, revogar e consultar o acervo de uma operação

**Ator:** participante da operação
**Permissão:** `copia.autorizar_origem` | `copia.autorizar_destino`
**Tela/rota:** `/app/copias/operacao/{id}` (+ `/solicitar`, `/revogar`, `/acervo`); `/api/v1/copias/operacao/{id}*`.

**Fluxo principal:** detalhe da operação → solicitar execução ou revogar antes da publicação → consultar o acervo publicado.

**Situação:** Implementado (T5-E3, PR #132) — ⚠ conferência visual integrada E3e pendente; política substituída → FN-025.

---

## Fluxos previstos em T5 e não implementados

- Solicitação limitada do professor escolar (docs/78–80).
- Estatística anônima (docs/77, 87).
- Limpeza de staging bloqueado e outbox; procedimento de guarda do acervo (docs/80, 82, 85, 90).
- Ensaio de volume/vazão no servidor (docs/82, 83).

**Situação:** Não implementado — o §3 do plano mestre propõe executá-los ou encerrá-los em favor de FN-025.
