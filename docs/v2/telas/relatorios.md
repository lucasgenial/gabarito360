# Telas: Relatórios da Prova (`relatorio-prova.html`, `relatorio-turma-prova.html`)

- **Rotas web:** `/relatorios/prova/{prova}` (todas as turmas) e
  `/relatorios/turma-prova/{turma}/{prova}` (recorte por turma).
- **Módulo:** Relatórios
- **Atores/permissões:** professor, coordenação, gestão (escopo).
- **Objetivo:** consolidar o desempenho de uma prova — KPIs, distribuição por
  tema, aproveitamento e resultado por aluno, com acesso ao resultado individual.
- **Shell:** ver [`_shell.md`](_shell.md). A versão **turma×prova** recorta os
  mesmos blocos a uma turma.

## Layout e componentes

- **Cabeçalho:** título da prova + subtítulo (disciplina · turmas · aplicação ·
  cartões corrigidos); badge de status (Corrigida).
- **KPI grid (4):** Média da prova (vs meta), Aprovação (%), Cartões corrigidos
  (N/N), Pendências de leitura.
- **Acertos por tema:** barras por tema/habilidade + insight textual.
- **Aproveitamento médio:** donut (nota média / meta).
- **Resultado por aluno:** tabela (Aluno, Turma, Nota, Status, ação **Ver prova**
  → resultado individual com `?from=prova`/`rel-turma`).

## Controles e ações

| Controle | Tipo | Ação | Endpoint | Regra |
|---|---|---|---|---|
| Ver prova (linha) | link | resultado individual | `/resultados/{aluno}/{prova}?from=...` | — |
| Exportar (PDF/CSV/XLSX) | botão | exporta relatório | `GET /api/v2/relatorios/prova/{id}.{fmt}` | autorizado/auditado |
| Aluno (link) | link | detalhe do aluno | `/alunos/{id}` | — |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| KPIs (média, aprovação, cartões, pendências) | agregações de `resultados`, `leituras` | escopo prova/turma |
| Acertos por tema | `resultados_questoes` × `temas_habilidades` | barras acessíveis |
| Aproveitamento | agregação | donut + meta |
| Resultado por aluno | `resultados` | nota + status |

## Estados

`default`, `hover`, `loading`, `empty`, `error`, `success` (export),
`access_denied`. Status por aluno: aprovado (success) / recuperação (warn).

## Regras de negócio

- Métricas calculadas sobre resultados reais e a versão do gabarito da prova.
- Escopo: professor/turma veem apenas o que lhes compete.
- Exportações (PDF/CSV/XLSX) autorizadas e auditadas (`exportacoes`).

## Responsividade

KPI grid 4→2→1; blocos de gráfico colapsam; tabela com rolagem controlada.

## Endpoints `/api/v2` necessários

- `GET /api/v2/relatorios/prova/{id}` e `.../turma-prova/{turma}/{prova}` — dados.
- `GET /api/v2/relatorios/prova/{id}.{pdf|csv|xlsx}` — exportações.

## Pendências/decisões

- Definir comparativos (turmas/escolas) e filtros adicionais (tema, período).
- Alternativa acessível para barras/donut.
