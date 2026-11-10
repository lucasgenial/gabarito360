# 053 — Painéis de turmas e da turma — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** quem tem `turma.ver` (cadastro) e, para os resultados, `relatorio.turma`: Administrador da Rede, Diretor de Núcleo, Diretor Escolar, Vice-diretor, Coordenador, Professor, Professor autônomo; Secretário escolar e Auxiliar de secretaria só na visão administrativa. Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Descreve o comportamento **atual** (na homologação); diferenças em relação ao planejado estão marcadas com ⚠ e apontam a subfase do [planejamento](planejamento.md). Relaciona-se ao UC-007 do [docs/07](../../07-casos-de-uso.md).

---

## UC-053-01 — Consultar o painel de Turmas

**Ator:** perfis com `turma.ver`
**Permissão:** `turma.ver`
**Escopo:** turmas do vínculo (professor: as suas)
**Pré-condições:** vínculo selecionado.
**Pós-condições:** nenhuma.
**Tela/rota:** mockup `turmas.html`; web `turmas.index`; API `GET v1/turmas`

**Fluxo principal:**
1. A pessoa abre Turmas.
2. O sistema mostra o resumo cadastral (por exemplo, turmas por turno), a relação de turmas na Tabela padrão com Cards alternativos e os componentes do catálogo permitidos ao perfil.
3. Componentes sem fonte real aparecem como "Em breve" com "Dados demonstrativos".

**Fluxos alternativos e de erro:**
- 2a. Sem turmas: estado vazio.
- ⚠ A comparação de desempenho entre turmas é demonstrativa até P3.3 (subfase 3.2).
- ⚠ Agenda e avisos são demonstrativos: aguardam FN-056 e FN-057.

**Critérios de aceite:**
- [x] `turma.ver` sozinho não entrega dados pedagógicos (P1.3).
- [ ] Composição conferida para cada um dos 7 perfis (P2.4, subfase 2.2 — Incerto).

**Situação:** Parcial — evidência: `TurmaController::index` (web), `CatalogoWidgetsPainel`, PR #210–#215

---

## UC-053-02 — Consultar o painel da Turma (resultados)

**Ator:** perfis pedagógicos com `relatorio.turma`
**Permissão:** `turma.ver` para abrir a turma; `relatorio.turma` para os resultados; `correcao.acompanhar` para os dados operacionais da correção
**Escopo:** turma no escopo
**Pré-condições:** turma existente.
**Pós-condições:** nenhuma.
**Tela/rota:** mockup `turma-detalhe-2.html`; web `turmas.show`, `turmas.secao`; API `GET v1/turmas/{id}`, `GET v1/turmas/{id}/painel`

**Fluxo principal:**
1. A pessoa abre a turma.
2. O sistema mostra o resumo cadastral e o grupo "Resultados da turma": avaliações do recorte com corrigidos, média, percentual de acerto, situações registradas (dentro do critério, indicação de recuperação), alunos em destaque e em atenção, distribuição de acertos.
3. Cada indicador explica a base e as exclusões no título (popover).
4. Quem tem `correcao.acompanhar` vê também pendências de revisão, processamento, falhas e não identificados por avaliação.

**Fluxos alternativos e de erro:**
- 1a. Perfil administrativo: vista administrativa, sem resultados (a API nega com 403).
- 2a. Falha ao consultar o painel: a região mostra erro e o restante da turma continua disponível.
- 2b. Card sem dados: altura reduzida, sem dado inventado.
- ⚠ Taxa de participação não é calculada: faltam os elegíveis por aplicação (P1.4.6, subfase 1.4).

**Regras de negócio:** só provas publicadas, em correção ou corrigidas com data até hoje; só notas de cartão vigente lido; percentual só com base válida (docs/182).

**Critérios de aceite:**
- [x] Uma nota antiga não conta como concluída se o cartão voltou à revisão.
- [x] O horário da consulta é mostrado.

**Situação:** Implementado (homologação) — evidência: `PainelTurma::montar`, `PainelTurmaController::show`; testes `PainelTurmaIndicadoresTest`, `PainelTurmaAnaliseTest`

---

## UC-053-03 — Filtrar o recorte do painel da Turma

**Ator:** os mesmos do UC-053-02
**Permissão:** `relatorio.turma`
**Escopo:** opções limitadas ao que o vínculo alcança
**Pré-condições:** painel aberto.
**Pós-condições:** recorte aplicado e exibido de forma discreta.
**Tela/rota:** web `turmas.show` com `painel_avaliacao`, `painel_disciplina`, `painel_professor`, `painel_inicio`, `painel_fim`; API com `analise_prova_id`, `disciplina_id`, `professor_id`, `inicio`, `fim`

**Fluxo principal:**
1. A pessoa abre o filtro pelo ícone (modal compacto).
2. Escolhe disciplina, professor autor, intervalo de datas ou a avaliação em análise.
3. O painel é recalculado; cancelar mantém o recorte anterior.

**Fluxos alternativos e de erro:**
- 2a. Valor inválido: "Escolha uma opção válida.", "Informe uma data válida." ou "A data final deve ser igual ou posterior à data inicial."; o recorte anterior válido é mantido.
- 2b. Opção fora do alcance: "Disciplina fora do recorte autorizado." ou "Professor autor fora do recorte autorizado." (422).
- ⚠ O filtro de professor é pela **autoria original** da prova; distinguir autor, aplicador e docente responsável e relacionar os filtros entre si são P1.2.3 e P1.2.4 (subfase 1.2).
- ⚠ Não há seletor de período escolar: só intervalo de datas até FN-039 (P1.2.6).

**Critérios de aceite:**
- [x] As opções vêm só das provas autorizadas da turma.

**Situação:** Parcial — evidência: `TurmaController::regrasFiltrosPainel`, `PainelTurma::montar`

---

## UC-053-04 — Comparar turmas no painel agregado

**Ator:** gestão e professores com `relatorio.turma`
**Permissão:** `relatorio.turma`
**Escopo:** turmas do vínculo
**Pré-condições:** base comparável definida (P3.3.1–P3.3.3).
**Pós-condições:** nenhuma.
**Tela/rota:** painel de Turmas

**Fluxo principal:**
1. O sistema compara as turmas por avaliação, com numeradores, denominadores e cobertura explícitos, sem somar médias de turmas.

**Critérios de aceite:**
- [ ] Comparação pedagógica só quando houver base adequada; caso contrário, comparação descritiva por avaliação.
- [ ] Participação, cobertura completa e tempo de devolutiva continuam como modelos enquanto faltarem registros.

**Situação:** Não implementado — subfase 3.2 (P3.3); depende em parte de FN-026 e FN-059

---

## UC-053-05 — Registrar os elegíveis de uma aplicação

**Ator:** sistema, a partir do domínio de aplicação (FN-059)
**Permissão:** não se aplica
**Escopo:** prova × turma
**Pré-condições:** contrato P1.4.6 validado; matrícula temporal (FN-026).
**Pós-condições:** registro histórico de quem deveria fazer a avaliação.

**Fluxo principal:**
1. Na aplicação, o sistema grava os estudantes elegíveis.
2. Os painéis passam a calcular participação sem inferir presença a partir de cartões.

**Critérios de aceite:**
- [ ] Nenhuma taxa de participação é mostrada sem esse registro.

**Situação:** Não implementado — subfase 1.4 (P1.4.6 / P3.3.5)

---

## UC-053-06 — Emitir o PDF do painel da Turma

Ver UC-050-01 e UC-050-02 (FN-050).
