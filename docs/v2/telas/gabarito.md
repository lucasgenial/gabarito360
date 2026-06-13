# Tela: Gabarito Oficial (visualização) (`gabarito.html`)

- **Rota web:** `/provas/{id}/gabarito`
- **Módulo:** Provas
- **Atores/permissões:** professor, coordenação, gestão escolar (escopo).
- **Objetivo:** exibir o gabarito oficial publicado da prova e permitir exportar
  em PDF. É o padrão que o OMR usa para corrigir os cartões.
- **Shell:** ver [`_shell.md`](_shell.md).

## Layout e componentes

- **Cabeçalho:** título da prova + subtítulo (disciplina · turmas · aplicação);
  badge de status (Publicada) + botão **"Exportar PDF"** (impressão).
- **Painel de dados (sticky):** Dados da prova (disciplina, série/turmas, nº de
  questões, alternativas A–E, aplicação) + nota explicativa.
- **Folha de respostas oficial:** grade de questões com bolhas A–E, a correta
  marcada (somente leitura).

## Controles e ações

| Controle | Tipo | Ação | Endpoint | Regra |
|---|---|---|---|---|
| Exportar PDF | botão | gera/imprime gabarito | `GET /api/v2/provas/{id}/gabarito.pdf` | exportação autorizada |
| Folha de respostas | leitura | exibe respostas oficiais | `GET /api/v2/provas/{id}/gabarito` | somente leitura |

## Dados exibidos

| Campo | Origem | Observação |
|---|---|---|
| Dados da prova | `provas` | — |
| Respostas oficiais | `gabaritos_oficiais`, `gabaritos_respostas` | marca a correta |

## Estados

`default`, `loading`, `error`, `access_denied`, `success` (export). Bolhas
`marked` em leitura.

## Regras de negócio

- O gabarito publicado é imutável como referência da correção; alterações geram
  nova versão (`versoes_prova`) e devem preservar histórico.
- Resultados registram a versão do gabarito utilizada.

## Responsividade

`editor-grid` 2→1 coluna ≤900px; folha 1 coluna. Impressão (PDF) preserva layout.

## Endpoints `/api/v2` necessários

- `GET /api/v2/provas/{id}/gabarito` — dados e respostas oficiais.
- `GET /api/v2/provas/{id}/gabarito.pdf` — exportação.

## Pendências/decisões

- Definir versionamento do gabarito (edição pós-publicação) e impacto em correções.
