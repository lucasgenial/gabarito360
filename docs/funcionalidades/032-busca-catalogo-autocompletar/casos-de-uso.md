# 032 — Busca do catálogo por autocompletar — Casos de uso

**Natureza:** evolução de existente — casos de uso propostos, de alto nível (funcionalidade adiada)
**Perfis envolvidos:** os mesmos dos pontos de uso: `professor_autonomo`, Suporte/Desenvolvedor, gestores com `catalogo.manter_escopo`, perfis com `escola.criar`. Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Derivados do desenho preferido do [docs/200](../../200-f5-f6-diagnostico-catalogo.md). O comportamento atual da busca está em UC-031-01.

---

## UC-032-01 — Encontrar instituição digitando o nome

**Ator:** qualquer perfil num dos pontos de uso (Minhas instituições, Minhas solicitações, atendimento, gestão)
**Permissão:** a do ponto de uso (ex.: `catalogo.consultar`, `catalogo.solicitacao.analisar`)
**Escopo:** leitura de dado público do catálogo
**Pré-condições:** edição publicada; de preferência UF/município já escolhidos.
**Tela/rota:** componente único de autocompletar; endpoint de sugestão a definir. Hoje: `GET /api/v1/catalogo/instituicoes`.

**Fluxo principal (proposto):**
1. A pessoa escolhe UF e, se quiser, município.
2. Digita ao menos 3 letras (2 com UF).
3. Após uma espera curta, vê até 10 sugestões por prefixo de palavra, sem contagem total.
4. Escolhe uma sugestão e segue o fluxo do ponto de uso (ex.: conferir e adicionar, UC-031-02).

**Fluxos alternativos e de erro:**
- 3a. Resposta antiga chega depois de uma nova digitação → descartada.
- 3b. Sem sugestões → mensagem de ausência e caminho para solicitar cadastro (UC-031-04).
- 3c. Falha de rede → indisponibilidade com "tentar novamente", nunca falsa ausência.

**Critérios de aceite:**
- [ ] Utilizável por teclado e leitor de tela.
- [ ] Tempo de resposta abaixo de 100 ms no servidor, medido com 1000 usuários simultâneos.
- [ ] O mesmo componente em todos os pontos de uso.

**Situação:** Não implementado — ⚠ hoje a busca é por substring, com contagem exata e página de resultados, ~1,8 s sem UF (UC-031-01). Adiada; subfases 1.1–1.4.

---

## UC-032-02 — Encontrar instituição pelo código oficial

**Ator:** os mesmos de UC-032-01
**Fluxo principal:** informar o código INEP e a fonte; a busca é exata e dispensa UF.

**Situação:** Parcial — a busca exata por código já existe (`namespace` + `codigo`, UC-031-01); falta integrá-la ao componente único.

---

## UC-032-03 — Escolher o município a partir da UF

**Ator:** os mesmos de UC-032-01
**Fluxo principal (proposto):** ao escolher a UF, o componente recebe a lista completa de municípios daquela UF (pequena e quase fixa) e filtra no navegador.

**Situação:** Parcial — hoje `GET /api/v1/catalogo/municipios` é pesquisável e paginado (50 por página) por UF; trocar a UF já limpa a seleção. A lista completa por UF e o cache não existem.

---

## UC-032-04 — Buscar escola ao cadastrá-la na instituição

**Ator:** Administrador da Rede ou Diretor de Núcleo
**Permissão:** `escola.criar`
**Fluxo (a decidir):** usar o mesmo componente sobre o catálogo publicado, em vez da base do Censo.

**Situação:** Não implementado — hoje o cadastro de escola consulta `censo_escolas` por `GET /api/v1/censo/escolas[/{inep}]` (rotas web `escolas/censo/buscar|pesquisar`). Decisão pendente, a avaliar em FN-033.

---

## UC-032-05 — Reconstruir o índice ao publicar uma edição

**Ator:** operador da plataforma (junto de UC-031-09)
**Fluxo principal (proposto):** a publicação de uma edição reconstrói o índice e as facetas e invalida o cache da edição anterior.

**Critérios de aceite:**
- [ ] Nenhuma sugestão aponta para unidade fora da edição corrente.

**Situação:** Não implementado — subfase 1.2.
