# Modelo de importacao de alunos

O arquivo [`importacao-alunos.csv`](importacao-alunos.csv) e o template oficial do MVP.

## Formato

- Codificacao: UTF-8.
- Separador: virgula.
- Extensao aceita: `.csv`.
- Limite padrao: 2 MB e 1.000 linhas de dados.
- Cabecalho obrigatorio e imutavel:
  `matricula,codigo_interno,nome,numero_chamada`.

| Coluna | Obrigatoria | Regra |
|---|---|---|
| `matricula` | Sim | Identificador unico do aluno dentro da escola; normalizado para maiusculas |
| `codigo_interno` | Nao | Codigo auxiliar da escola |
| `nome` | Sim | Nome do aluno |
| `numero_chamada` | Nao | Numero do aluno na turma selecionada |

CPF, data de nascimento, observacoes e outros dados pessoais nao pertencem ao
template do MVP.

## Fluxo seguro

1. O gestor seleciona uma escola, uma turma ativa e envia o CSV.
2. O sistema armazena o arquivo em disco privado e apresenta inclusoes,
   atualizacoes, matriculas e erros por linha.
3. Nenhum aluno e gravado enquanto houver erros ou antes da confirmacao.
4. A confirmacao inicia um job transacional e idempotente.
5. Reenvios nao criam alunos ou matriculas ativas duplicadas.
