# 049 — Resultados e relatórios consolidados — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Secretário de Educação, Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Vice-diretor, Coordenador, Professor, Professor autônomo, Aluno. Ver [docs/03](../../03-perfis-e-permissoes.md) §4.6.
**Atualizado em:** 2026-11-09

Descreve o comportamento **atual**; diferenças em relação ao planejado estão marcadas com ⚠. O resultado individual (UC-005 do [docs/07](../../07-casos-de-uso.md)) está detalhado em UC-047-04 e o PDF do resultado em UC-047-05; aqui ficam os relatórios.

---

## UC-049-01 — Consultar o relatório de uma prova

**Ator:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador, Professor
**Permissão:** `relatorio.prova`
**Escopo:** prova histórica com conteúdo no escopo (nunca aplicador)
**Pré-condições:** prova com notas.
**Pós-condições:** nenhuma.
**Tela/rota:** mockup `relatorio-prova.html`; web `relatorios.prova`; API `GET v1/relatorios/prova/{id}`

**Fluxo principal:**
1. O usuário abre "Ver resultados" na prova.
2. O sistema mostra turmas da prova, cabeçalho institucional, KPIs (média, meta da rede, aprovação %, aprovados, total, cartões lidos/total, pendências, maior nota), tabela de alunos e análise por questão.
3. O usuário abre o resultado de um aluno (UC-047-04).

**Fluxos alternativos e de erro:**
- 1a. Prova fora do escopo: "Prova não encontrada." (404).

**Critérios de aceite:**
- [x] Meta da rede vem de `redes.meta_media` (padrão 7,0).
- [ ] ⚠ O UC-006 prevê "gráfico de acertos por tema": não existe (subfase 2.1, depende de FN-043).

**Situação:** Parcial — implementado sem acertos por tema. Evidência: `RelatorioController::prova` (API e web)

---

## UC-049-02 — Consultar o relatório da turma em uma prova

**Ator:** os mesmos do UC-049-01
**Permissão:** `relatorio.prova`
**Escopo:** prova e turma no escopo; a turma precisa estar na prova
**Pré-condições:** prova aplicada à turma.
**Pós-condições:** nenhuma.
**Tela/rota:** mockup `relatorio-turma-prova.html`; web `relatorios.turmaProva`; API `GET v1/relatorios/turma/{turmaId}/prova/{provaId}`

**Fluxo principal:**
1. O usuário escolhe a turma no relatório da prova.
2. O sistema mostra KPIs, alunos e análise por questão restritos à turma (pela turma de aplicação do cartão).

**Fluxos alternativos e de erro:**
- 1a. Turma fora do escopo ou fora da prova: "Turma não encontrada." (404).

**Critérios de aceite:**
- [x] Cartões contam pela turma de aplicação, com recurso à turma atual do aluno.

**Situação:** Implementado — evidência: `RelatorioController::turmaProva`

---

## UC-049-03 — Consultar o relatório consolidado da turma

**Ator:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador, Professor
**Permissão:** `relatorio.turma`
**Escopo:** turma no escopo
**Pré-condições:** turma com provas.
**Pós-condições:** nenhuma.
**Tela/rota:** web `relatorios.turma`; API `GET v1/relatorios/turma/{turmaId}`

**Fluxo principal:**
1. O usuário abre o relatório da turma.
2. O sistema mostra média e aprovação por prova, comparação com as outras turmas da escola e ranking dos alunos (ativos e com nota na turma).

**Fluxos alternativos e de erro:**
- 1a. Turma fora do escopo: "Turma não encontrada." (404).

**Critérios de aceite:**
- [x] Alunos que saíram mas têm nota na turma aparecem no histórico.

**Situação:** Implementado — evidência: `RelatorioController::turma`

---

## UC-049-04 — Consultar o boletim do aluno

**Ator:** os mesmos do UC-049-03
**Permissão:** `relatorio.turma`
**Escopo:** aluno cuja turma está no escopo
**Pré-condições:** aluno com provas na turma ou notas históricas.
**Pós-condições:** nenhuma.
**Tela/rota:** web `relatorios.aluno`; API `GET v1/relatorios/aluno/{alunoId}`

**Fluxo principal:**
1. O usuário abre o relatório do aluno.
2. O sistema lista as provas da turma e as provas históricas com nota, com nota, acertos e posição na turma da aplicação.

**Fluxos alternativos e de erro:**
- 1a. Aluno inexistente ou turma fora do escopo: "Aluno não encontrado." (404).

**Critérios de aceite:**
- [x] A posição considera a turma em que o aluno fez a prova.

**Situação:** Implementado — evidência: `RelatorioController::aluno`

---

## UC-049-05 — Consultar o relatório consolidado da escola

**Ator:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Vice-diretor, Coordenador; Professor só com extra
**Permissão:** `relatorio.escola`
**Escopo:** escolas do escopo do usuário (o extra não amplia o escopo)
**Pré-condições:** escola com notas.
**Pós-condições:** nenhuma.
**Tela/rota:** web `relatorios.escola`; API `GET v1/relatorios/escola/{id?}`

**Fluxo principal:**
1. Sem id, o sistema usa a escola do usuário.
2. Mostra indicadores, média por área, comparativo entre turmas ativas, destaques e alunos em atenção.

**Fluxos alternativos e de erro:**
- 1a. Mais de uma escola no escopo e nenhuma escolhida: "Selecione a escola." (422).
- 1b. Escola fora do escopo: "Escola não encontrada." (404).

**Critérios de aceite:**
- [x] Notas na escala 0–10 para comparar provas diferentes.

**Situação:** Implementado — evidência: `RelatorioConsolidadoController::escola`, commit f995232

---

## UC-049-06 — Consultar o relatório consolidado do núcleo

**Ator:** Administrador da Rede, Diretor de Núcleo
**Permissão:** `relatorio.nucleo`
**Escopo:** administrador vê todos os núcleos; os demais, os do seu escopo
**Pré-condições:** núcleo com escolas.
**Pós-condições:** nenhuma.
**Tela/rota:** web `relatorios.nucleo`; API `GET v1/relatorios/nucleo/{id?}`

**Fluxo principal:**
1. Sem id, o sistema usa o núcleo do usuário.
2. Mostra indicadores e comparativo entre as escolas do núcleo.

**Fluxos alternativos e de erro:**
- 1a. Mais de um núcleo: "Selecione o núcleo." (422). Fora do escopo: "Núcleo não encontrado." (404).

**Situação:** Implementado — evidência: `RelatorioConsolidadoController::nucleo`

---

## UC-049-07 — Consultar o relatório consolidado da rede

**Ator:** Administrador da Rede; ⚠ Secretário de Educação pelo docs/03
**Permissão:** `relatorio.rede`
**Escopo:** redes visíveis ao usuário
**Pré-condições:** rede com escolas.
**Pós-condições:** nenhuma.
**Tela/rota:** web `relatorios.rede`; API `GET v1/relatorios/rede/{id?}`

**Fluxo principal:**
1. Com uma rede, o sistema a abre; com várias, devolve a lista para escolha.
2. Mostra indicadores e comparativo entre as escolas da rede.

**Fluxos alternativos e de erro:**
- 1a. Perfil diferente de `admin_rede`: "Rede não encontrada." (404). ⚠ O docs/03 dá `relatorio.rede` ao Secretário de Educação para as redes da sua secretaria; o código não atende (planejamento, item 8).

**Situação:** Parcial — implementado só para o Administrador da Rede. Evidência: `RelatorioConsolidadoController::rede`

---

## UC-049-08 — Exportar o PDF dos resultados de uma prova em lote

**Ator:** Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Coordenador, Professor
**Permissão:** `relatorio.prova` e `resultado.compartilhar` (5 por minuto)
**Escopo:** prova e turma opcional no escopo
**Pré-condições:** notas disponíveis.
**Pós-condições:** um arquivo combinado, ordenado por turma e nome, com emissão registrada (FN-047).
**Tela/rota:** web `resultados.lote`; API `GET v1/provas/{id}/resultados/pdf`

**Fluxo principal e erros:** ver UC-047-05 (limite de 500 resultados; "Selecione uma turma." acima disso).

**Critérios de aceite:**
- [x] Aluno e aplicador recebem 403.

**Situação:** Implementado — evidência: PR #156, `ResultadoController::lote`; teste `ResultadosLoteTest`
