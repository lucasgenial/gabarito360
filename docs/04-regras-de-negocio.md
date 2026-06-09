# Gabarito360 - Regras de Negocio

## 1. Convencoes

- **Identificador:** `RNnnn`.
- Regras devem ser aplicadas no backend, ainda que tambem sejam antecipadas pela interface.
- Operacoes historicas devem ser canceladas ou inativadas, evitando exclusao fisica sem politica aprovada.

## 2. Escopo organizacional e permissoes

| ID | Regra |
|---|---|
| RN001 | Uma escola deve pertencer a um unico nucleo ativo por vez. |
| RN002 | Um gestor de nucleo acessa apenas o proprio nucleo e suas escolas vinculadas. |
| RN003 | Um responsavel de escola acessa apenas dados da propria escola. |
| RN004 | Professor e aplicador acessam apenas turmas e aplicacoes as quais estejam vinculados. |
| RN005 | Leitor/Consulta nao pode alterar cadastros, leituras ou resultados. |
| RN006 | Acoes de suporte tecnico que acessem dados operacionais devem ser autorizadas e auditadas. |
| RN007 | Inativar usuario deve revogar suas sessoes e impedir novas autenticacoes. |

## 3. Turmas e alunos

| ID | Regra |
|---|---|
| RN008 | Uma turma pertence a uma escola e a um ano letivo. |
| RN009 | A matricula do aluno deve ser unica no escopo definido pela escola ou pelo nucleo. |
| RN010 | A transferencia de aluno deve preservar historico de aplicacoes e resultados. |
| RN011 | Aluno inativo permanece visivel em registros historicos. |
| RN012 | Importacoes so podem ser confirmadas depois da validacao de linhas invalidas e duplicadas. |

## 4. Avaliacoes, modelos e gabaritos

| ID | Regra |
|---|---|
| RN013 | Uma avaliacao em rascunho pode ser editada por usuario autorizado. |
| RN014 | Uma avaliacao so pode ser publicada com modelo de cartao e gabarito validos. |
| RN015 | A quantidade de respostas do gabarito deve corresponder a quantidade de questoes da avaliacao. |
| RN016 | Cada questao possui no maximo uma alternativa correta, salvo modelo futuro explicitamente configurado. |
| RN017 | O gabarito oficial deve ser bloqueado apos o inicio da primeira aplicacao. |
| RN018 | Alterar gabarito bloqueado exige permissao especial, justificativa e registro de versao. |
| RN019 | Alteracao autorizada de gabarito deve marcar resultados afetados para recorrection. |
| RN020 | O modelo de cartao utilizado em uma leitura deve ser identificado por versao. |
| RN021 | Avaliacao arquivada permanece consultavel, mas nao aceita novas aplicacoes. |

## 5. Aplicacoes

| ID | Regra |
|---|---|
| RN022 | Uma aplicacao vincula uma avaliacao publicada a uma escola e turma autorizadas. |
| RN023 | Apenas aplicador vinculado ou gestor autorizado pode iniciar ou finalizar a aplicacao. |
| RN024 | Uma aplicacao finalizada nao aceita novas leituras confirmadas. |
| RN025 | Reabrir uma aplicacao exige permissao especial, justificativa e auditoria. |
| RN026 | Alunos previstos sao definidos a partir do vinculo ativo com a turma no momento de preparacao da aplicacao. |
| RN027 | Alunos sem leitura valida devem aparecer como pendentes ou ausentes, conforme registro. |

## 6. Cartoes, leituras e resultados

| ID | Regra |
|---|---|
| RN028 | Um aluno pode ter apenas um cartao valido confirmado por avaliacao. |
| RN029 | Um codigo de cartao pode estar vinculado a apenas um aluno dentro da mesma avaliacao. |
| RN030 | O codigo do cartao deve ser vinculado ou confirmado no momento da confirmacao da leitura. |
| RN031 | Uma nova tentativa de leitura nao apaga tentativas anteriores. |
| RN032 | Reprocessar ou substituir uma leitura confirmada exige permissao, justificativa e auditoria. |
| RN033 | Leituras de baixa confianca devem exigir revisao manual explicita. |
| RN034 | Questoes em branco, duplas ou duvidosas devem ser destacadas antes da confirmacao. |
| RN035 | Uma leitura com alertas pode ser confirmada somente apos aceite explicito do aplicador. |
| RN036 | Toda alteracao manual de resposta deve registrar valor detectado, valor final, usuario, data e motivo quando exigido. |
| RN037 | O resultado deve ser calculado com a versao de gabarito vigente registrada na correcao. |
| RN038 | Questao em branco ou com dupla marcacao vale como incorreta, salvo regra especifica da avaliacao. |
| RN039 | Questao anulada deve seguir a politica configurada na avaliacao e ser aplicada igualmente aos resultados. |
| RN040 | O backend deve rejeitar confirmacao duplicada, mesmo quando recebida repetidamente por sincronizacao. |
| RN041 | Cancelar uma leitura confirmada invalida seu resultado vigente, mas preserva o historico. |

## 7. OMR e qualidade

| ID | Regra |
|---|---|
| RN042 | O OMR deve retornar confianca por questao e confianca geral. |
| RN043 | Limiares de marcacao e confianca pertencem a versao do modelo de cartao. |
| RN044 | Falha na identificacao automatica do codigo deve permitir digitacao manual validada. |
| RN045 | Imagem que nao atenda aos criterios minimos deve ser recusada ou marcada para nova captura. |
| RN046 | A resposta final confirmada prevalece sobre a detectada para correcao, mantendo ambas no historico. |

## 8. Offline e sincronizacao

| ID | Regra |
|---|---|
| RN047 | Toda operacao mobile sincronizavel deve possuir identificador unico gerado no dispositivo. |
| RN048 | Reenvio da mesma operacao nao pode criar duplicidade. |
| RN049 | Conflitos que alterem aluno, cartao ou resultado ja confirmado nao devem ser resolvidos silenciosamente. |
| RN050 | O app deve informar ao aplicador quais operacoes estao pendentes, sincronizadas ou com erro. |
| RN051 | Logout com operacoes pendentes deve alertar o usuario e preservar a fila local de forma segura. |

## 9. Auditoria, arquivos e retencao

| ID | Regra |
|---|---|
| RN052 | Devem ser auditadas alteracoes de gabarito, correcoes manuais, reprocessamentos, cancelamentos e mudancas de permissao. |
| RN053 | Registros de auditoria nao podem ser editados por usuarios operacionais. |
| RN054 | A coleta de localizacao aproximada depende de autorizacao e finalidade definida. |
| RN055 | Imagens originais e processadas devem seguir prazo de retencao configurado. |
| RN056 | Exclusao ou anonimizacao de dados deve respeitar obrigacoes legais e preservacao de evidencias necessarias. |
| RN057 | Exportacoes devem respeitar o escopo de acesso do solicitante e ser auditadas. |

## 10. Dashboards e relatorios

| ID | Regra |
|---|---|
| RN058 | Indicadores devem considerar apenas leituras e resultados validos vigentes. |
| RN059 | Dashboards de nucleo agregam apenas escolas vinculadas ao nucleo consultado. |
| RN060 | Resultados de alunos devem ser exibidos apenas a perfis com finalidade e permissao adequadas. |
| RN061 | Relatorios gerados devem registrar filtros, solicitante, data e versao dos dados quando aplicavel. |
| RN062 | Rankings devem informar criterio de ordenacao e tratamento de empates. |

## 11. Decisoes pendentes

- Definir escopo exato de unicidade da matricula do aluno.
- Definir politica de pontuacao para questoes anuladas.
- Definir limiares iniciais de confianca do OMR por modelo.
- Definir obrigatoriedade do motivo em toda correcao manual ou apenas em casos especificos.
- Definir prazos de retencao para imagens, auditoria, exportacoes e logs.
- Definir se professores podem criar avaliacoes locais no MVP.
