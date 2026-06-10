# Gabarito360 - Requisitos Funcionais

## 1. Convencoes

- **Prioridade:** `MVP`, `V2`, `V3` ou `Futuro`.
- **Identificador:** `RFnnn`.
- Todo requisito deve respeitar autenticacao, autorizacao, validacao, auditoria e escopo de dados aplicaveis.

## 2. Autenticacao e acesso

| ID | Prioridade | Requisito |
|---|---|---|
| RF001 | MVP | O sistema deve permitir login por credencial individual. |
| RF002 | MVP | O sistema deve permitir logout e revogar a sessao ativa. |
| RF003 | MVP | O sistema deve permitir recuperacao segura de senha. |
| RF004 | MVP | O sistema deve controlar acesso por perfil e permissao. |
| RF005 | MVP | O sistema deve restringir dados por nucleo, escola, turma e aplicacao. |
| RF006 | V2 | O sistema deve permitir consultar e revogar sessoes ativas. |
| RF007 | V2 | O sistema deve permitir autenticacao multifator para perfis privilegiados. |

## 3. Nucleos, escolas e usuarios

| ID | Prioridade | Requisito |
|---|---|---|
| RF008 | MVP | O administrador deve poder cadastrar, editar, consultar e inativar nucleos. |
| RF009 | MVP | O gestor do nucleo deve poder cadastrar, editar, consultar e inativar escolas vinculadas. |
| RF010 | MVP | O sistema deve permitir vincular responsaveis a escolas. |
| RF011 | MVP | Usuarios autorizados devem poder cadastrar, editar, consultar e inativar usuarios. |
| RF012 | MVP | O sistema deve permitir atribuir perfis e permissoes aos usuarios. |
| RF013 | MVP | O sistema deve permitir vincular professores e aplicadores a uma ou mais turmas. |
| RF014 | V2 | O sistema deve permitir convite e ativacao de usuario por e-mail. |
| RF015 | V2 | O suporte deve poder consultar diagnosticos tecnicos sem obter acesso irrestrito aos dados pessoais. |

## 4. Turmas e alunos

| ID | Prioridade | Requisito |
|---|---|---|
| RF016 | MVP | A escola deve poder cadastrar, editar, consultar e inativar turmas. |
| RF017 | MVP | A escola deve poder cadastrar, editar, consultar e inativar alunos. |
| RF018 | MVP | A escola deve poder importar alunos por arquivo CSV ou XLSX. |
| RF019 | MVP | O sistema deve validar a importacao e apresentar erros por linha antes da confirmacao. |
| RF020 | MVP | O sistema deve permitir vincular alunos a turmas por ano letivo. |
| RF021 | V2 | O sistema deve permitir transferir aluno entre turmas preservando o historico. |
| RF022 | V2 | O sistema deve permitir exportar a lista de alunos e pendencias. |

## 5. Avaliacoes, modelos e gabaritos

| ID | Prioridade | Requisito |
|---|---|---|
| RF023 | MVP | Usuarios autorizados devem poder criar, editar, consultar e arquivar avaliacoes. |
| RF024 | MVP | A avaliacao deve definir titulo, tipo, nivel, numero de questoes e alternativas. |
| RF025 | MVP | O sistema deve permitir cadastrar o gabarito oficial por questao. |
| RF026 | MVP | O sistema deve validar completude e consistencia do gabarito antes da publicacao. |
| RF027 | MVP | O sistema deve permitir publicar e encerrar uma avaliacao. |
| RF028 | MVP | O sistema deve permitir vincular avaliacoes a turmas. |
| RF029 | MVP | O sistema deve manter um modelo de cartao versionado para cada avaliacao. |
| RF030 | V2 | O sistema deve permitir importar gabarito oficial por planilha. |
| RF031 | V2 | O sistema deve permitir anular questoes e definir pesos. |
| RF032 | V2 | O sistema deve permitir duplicar uma avaliacao. |
| RF033 | V2 | Usuarios autorizados devem poder alterar gabarito publicado com justificativa e auditoria. |
| RF034 | V2 | O sistema deve permitir recorrection em lote apos alteracao autorizada do gabarito. |

## 6. Aplicacoes

| ID | Prioridade | Requisito |
|---|---|---|
| RF035 | MVP | O sistema deve criar uma aplicacao para uma avaliacao, escola e turma. |
| RF036 | MVP | O aplicador vinculado deve poder iniciar e finalizar uma aplicacao. |
| RF037 | MVP | O sistema deve apresentar alunos previstos, lidos e pendentes. |
| RF038 | MVP | O sistema deve atualizar o progresso da aplicacao apos cada confirmacao. |
| RF039 | MVP | O sistema deve impedir novas confirmacoes em aplicacao finalizada. |
| RF040 | V2 | Usuario autorizado deve poder reabrir uma aplicacao com justificativa. |
| RF041 | V2 | O sistema deve permitir registrar presenca, ausencia e justificativa. |

## 7. Aplicativo Android e sincronizacao

| ID | Prioridade | Requisito |
|---|---|---|
| RF042 | MVP | O app deve permitir login e carregar apenas aplicacoes autorizadas. |
| RF043 | MVP | O app deve permitir selecionar avaliacao, turma e aluno. |
| RF044 | MVP | O app deve exibir alunos lidos e pendentes. |
| RF045 | MVP | O app deve permitir capturar a foto do cartao-resposta. |
| RF046 | MVP | O app deve orientar enquadramento e informar problemas de qualidade. |
| RF047 | MVP | O app deve exibir as respostas detectadas antes da confirmacao. |
| RF048 | MVP | O app deve destacar respostas em branco, duplas e de baixa confianca. |
| RF049 | MVP | O app deve permitir confirmar ou informar o codigo impresso externo e, quando utilizado ou exigido, gerar ou receber separadamente o codigo do sistema. |
| RF050 | MVP | O app deve permitir corrigir manualmente uma resposta antes da confirmacao. |
| RF051 | MVP | O app deve exigir motivo em toda correcao manual de resposta. |
| RF052 | MVP | O app deve permitir refazer uma captura sem apagar o historico confirmado. |
| RF053 | MVP | O app deve enviar a leitura confirmada ao backend de forma idempotente. |
| RF054 | V2 | O app deve armazenar operacoes temporariamente quando estiver offline. |
| RF055 | V2 | O app deve sincronizar operacoes pendentes quando a conexao voltar. |
| RF056 | V2 | O app deve exibir estado e erros de sincronizacao. |

## 8. OMR, cartoes e correcao

| ID | Prioridade | Requisito |
|---|---|---|
| RF057 | MVP | O modulo OMR deve detectar os pontos de referencia do cartao. |
| RF058 | MVP | O modulo OMR deve corrigir perspectiva e localizar a area de respostas. |
| RF059 | MVP | O modulo OMR deve detectar marcacoes de A a E no modelo inicial. |
| RF060 | MVP | O modulo OMR deve identificar respostas em branco, duplas e duvidosas. |
| RF061 | MVP | O modulo OMR deve retornar confianca por resposta e status geral. |
| RF062 | MVP | O sistema deve vincular ao aluno o codigo impresso quando existente e o codigo do sistema quando utilizado, preservando ambos separadamente. |
| RF063 | MVP | O sistema deve comparar respostas confirmadas com o gabarito oficial. |
| RF064 | MVP | O sistema deve calcular acertos, erros, brancos, invalidas e nota. |
| RF065 | MVP | O sistema deve manter historico das tentativas e reprocessamentos. |
| RF066 | V2 | O backend deve permitir reprocessar uma imagem armazenada. |
| RF067 | V3 | O sistema deve suportar varios modelos configuraveis de cartao. |

## 9. Dashboards, relatorios e tempo real

| ID | Prioridade | Requisito |
|---|---|---|
| RF068 | MVP | O sistema deve exibir dashboard de progresso por aplicacao e turma. |
| RF069 | MVP | O sistema deve atualizar indicadores operacionais em tempo real. |
| RF070 | MVP | O sistema deve gerar relatorio basico de resultados por turma. |
| RF071 | MVP | O sistema deve permitir exportar resultados em CSV. |
| RF072 | V2 | O sistema deve exibir dashboards consolidados de escola e nucleo. |
| RF073 | V2 | O sistema deve exibir desempenho por questao. |
| RF074 | V2 | O sistema deve gerar relatorios por aluno, escola, avaliacao e nucleo. |
| RF075 | V2 | O sistema deve exportar relatorios em PDF e XLSX. |
| RF076 | V3 | O sistema deve permitir agendar relatorios recorrentes. |

## 10. Auditoria, arquivos e suporte

| ID | Prioridade | Requisito |
|---|---|---|
| RF077 | MVP | O sistema deve auditar operacoes administrativas e alteracoes manuais em leituras. |
| RF078 | MVP | O sistema deve registrar usuario, data, origem e dados relevantes da operacao auditada. |
| RF079 | MVP | O sistema deve armazenar arquivos com metadados, proprietario e politica de retencao. |
| RF080 | MVP | O sistema deve registrar erros de processamento e sincronizacao. |
| RF081 | V2 | Usuarios autorizados devem poder consultar a trilha de auditoria por filtros. |
| RF082 | V2 | O sistema deve permitir cancelar logicamente uma leitura confirmada com justificativa. |
| RF083 | V3 | O sistema deve oferecer API de integracao para sistemas educacionais externos. |

## 11. Rastreabilidade inicial do MVP

O MVP compreende `RF001-RF005`, `RF008-RF013`, `RF016-RF020`, `RF023-RF029`, `RF035-RF039`, `RF042-RF053`, `RF057-RF065`, `RF068-RF071` e `RF077-RF080`.

Os criterios de aceite detalhados serao refinados no backlog de cada etapa descrita em [Roadmap do MVP](11-roadmap-mvp.md).
