# ID1 — fundação da identidade pessoal

Planejamento revisado em 04/11/2026 após concluir PF1/IM1. A autorização atual permite prosseguir autonomamente e publicar entregas validadas somente na homologação. Os planos115/117 continuam definindo as dependências. Esta entrega técnica não modifica cadastros existentes nem cria uma busca global disponível aos perfis atuais.

## Decisão de arquitetura

Pessoa terá ID interno e UUID estáveis, separados de conta, matrícula e função. O CPF não será chave primária nem credencial. A expansão começa com tabelas independentes, sem preencher FKs em usuários/alunos ou modificar leitores existentes. Pessoas provisórias não recebem CPF fictício. Documento declarado não significa titular verificado.

CPF usa normalização estrita e validação dos dígitos, cifra autenticada do Laravel e índice HMAC com separação de domínio e versão. A chave de busca vigente existente pode ser reutilizada com domínio próprio; rotação exige um procedimento que preserve todos os índices de unicidade. Nenhuma rotação é feita aqui. A chave e o valor em claro não aparecem em relatório, exceção ou serialização.

Estruturas: pessoas; pessoa_documentos; pessoa_documento_indices; pessoa_identidade_eventos. Documentos e eventos são registros imutáveis; correções futuras terão novo evento e conciliação explícita, não sobrescrita. Índices reservam o documento por tipo/país/versão e impedem duplicidade concorrente no banco. A troca de chave e documentos retificados precisa do contrato seguinte antes de escrita operacional. Não inferir titularidade pela simples existência do índice.

O inventário de legado é somente leitura, executado por comando administrativo local. Lê CPFs protegidos com o mecanismo atual, valida e compara usando HMAC em memória, devolvendo apenas contagens agregadas: ausentes, inválidos, ilegíveis e grupos repetidos. Repetição entre matrículas locais pode ser legítima e não autoriza fusão. O comando tem limite de volume e falha sem imprimir dados pessoais ou inventário parcial. Não usa nomes, e-mails, IDs ou CPFs na saída.

## Ordem de execução e critérios

| Parte | Entrega | Critério |
|---|---|---|
| ID1-A | Contrato, proteção e esquema aditivo | Cifra/HMAC, validação, unicidade e imutabilidade testadas em SQLite/MariaDB |
| ID1-B técnico | Busca exata interna por índice | Sem HTTP/listagem, retorno mínimo, nenhuma concessão implícita |
| ID1-C técnico | Inventário de conciliação | Somente leitura, sem identificar registros na saída ou fundir pessoas |
| ID1-D operacional | Mapear contas/alunos existentes | Depende de verificação e resolução das ambiguidades; não aplicar por coincidência de CPF |
| VI1 | CPF-primeiro e convite | Depois da matriz de consulta e da verificação de identidade; manter convites atuais |
| DC1 | Disciplinas/lotação | Próximo pacote independente quando o gate humano de identidade impedir habilitar busca |

A matriz de quem pode consultar a identidade global, os dados mínimos retornados e o procedimento que comprova a identidade precisam ser concretizados e aprovados antes de expor um endpoint. Isso não impede construir/testar as primitivas e inventariar a qualidade do legado. Contas de teste autorizadas com identificadores fictícios não devem virar identidades verificadas.

## Validação e publicação

Testar CPF com máscara, zeros iniciais e dígitos inválidos; cifra não determinística; HMAC determinístico e separado do legado; chave ausente; nenhum valor sensível em mensagens; repetição de documento sob concorrência; FK restritiva; eventos/documentos sem UPDATE/DELETE; inventário agregado, falha de decriptação e ausência de escrita. Não adicionar permissões, modificar foto ou migrar vínculo como efeito colateral.

Homologação: ensaiar a migration aditiva com backup recuperável e comparar todos os campos das nove tabelas acadêmicas, usuários e arquivos privados. Novas tabelas começam vazias. Rollback da aplicação conserva as estruturas e não executa down destrutivo. CI/merge/recibos finais serão registrados no PR; produção continua fora do escopo. Esta fundação não representa ID1/VI1 concluídos nem disponibiliza transferência ou identidade global à escola.

## Evidência inicial

SQLite e MariaDB local descartável:12 testes aprovados/61 asserções em cada motor. As chaves e documentos utilizados são fixtures matemáticas de teste; não comprovam identidade real. A FK composta impede que um evento de uma pessoa use o documento de outra. Índices recusam versão não suportada e formato inválido; documentos, índices e eventos recusam reescrita/exclusão no banco.

O ensaio também demonstrou um risco do legado: o índice atual usa o texto recebido; o mesmo CPF com máscara distinta pode ter outro hash. O inventário normaliza apenas em memória e conta essa coincidência sem alterar nenhuma linha. Não corrigir em massa o CPF ou seu hash atual antes de resolver concorrência, verificação e mapeamentos do ID1-D.
