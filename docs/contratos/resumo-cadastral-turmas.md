# P3.1 — Resumo cadastral das turmas

Extensão aditiva de GET /api/v1/turmas. Nenhuma rota, autoridade, filtro, coluna ou migration nova. Preservar data.turmas e data.kpis (universo legado); novo data.resumo_cadastral representa exatamente a coleção autorizada completa depois de busca, escola_id, ano_letivo e ativo.

```json
{"versao":1,"base":"colecao_completa_no_recorte","colecao_completa":true,"turmas":2,"em_uso":1,"arquivadas":1,"cadastros_ativos":54}
```

Contadores são inteiros>=0. turmas=em_uso+arquivadas. cadastros_ativos conta DISTINCT alunos.id ativos, associados às turmas do recorte e coerentes com seu espaço. É cadastro local, não pessoa global, matrícula histórica, participação ou presença. Ativos de turma arquivada continuam contados se a turma integra o filtro. Um recorte vazio completo tem todos os contadores0; falha não vira zero.

Queries agregadas de alunos e médias compartilham a seleção/autorização das turmas, sem consultar por turma. Médias AVG continuam apenas sob a concessão pedagógica anterior, sem média de médias. Secretaria/auxiliar não consultam notas nem recebem media_turma; o resumo não serializa nomes, documentos ou imagens. Modelo Turma continua ocultando foto_path e projeta tem_foto/avatar_id/imagem_versao. Não acrescentar cache de concessões.

BFF: ausência do campo mantém a leitura do contrato anterior, cuja lista é integral; campo explicitamente nulo/inválido/incompleto/versão desconhecida não usa fallback silencioso. Validar tipos, base, completude e coerência com a lista local para os valores verificáveis. Um total individual ausente pode coexistir com agregado canônico completo. Agregado inválido deixa os contadores indisponíveis, preservando a lista e o restante da página. Nenhum endpoint adicional por card.

O resumo de quatro cards continua fixo, fora do catálogo de preferências. Quarto título: Cadastros de alunos ativos. Não inferir disciplina/atribuição/identidade única sem contrato. Ativação funcional e agrupamentos visuais têm revisões e gates próprios. Homologação exclusiva, semDDL.
