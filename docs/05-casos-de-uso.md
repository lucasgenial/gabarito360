# Gabarito360 - Casos de Uso

## 1. Atores

| Ator | Descricao |
|---|---|
| Administrador Geral | Configura e supervisiona todo o sistema. |
| Gestor do Nucleo | Gerencia escolas e acompanha resultados consolidados. |
| Responsavel da Escola | Gerencia operacao e cadastros da escola. |
| Professor/Aplicador | Executa aplicacoes e confirma leituras. |
| Leitor/Consulta | Consulta dashboards e relatorios autorizados. |
| Suporte Tecnico | Investiga falhas com acesso controlado. |
| Servico OMR | Processa imagens e retorna deteccoes. |
| Worker de Filas | Executa tarefas assincronas. |

## 2. Catalogo de casos de uso

| ID | Caso de uso | Ator principal | Prioridade |
|---|---|---|---|
| UC001 | Autenticar usuario | Todos os usuarios | MVP |
| UC002 | Gerenciar nucleo | Administrador Geral | MVP |
| UC003 | Gerenciar escola | Gestor do Nucleo | MVP |
| UC004 | Gerenciar usuarios e perfis | Administrador/Gestores | MVP |
| UC005 | Gerenciar turmas | Responsavel da Escola | MVP |
| UC006 | Cadastrar aluno | Responsavel da Escola | MVP |
| UC007 | Importar alunos | Responsavel da Escola | MVP |
| UC008 | Vincular aplicador a turma | Responsavel da Escola | MVP |
| UC009 | Criar avaliacao | Gestor autorizado | MVP |
| UC010 | Cadastrar gabarito oficial | Gestor autorizado | MVP |
| UC011 | Publicar avaliacao | Gestor autorizado | MVP |
| UC012 | Vincular avaliacao a turma | Gestor autorizado | MVP |
| UC013 | Iniciar aplicacao | Professor/Aplicador | MVP |
| UC014 | Capturar e processar cartao | Professor/Aplicador | MVP |
| UC015 | Revisar e confirmar leitura | Professor/Aplicador | MVP |
| UC016 | Corrigir leitura manualmente | Professor/Aplicador | MVP |
| UC017 | Finalizar aplicacao | Professor/Aplicador | MVP |
| UC018 | Acompanhar aplicacao em tempo real | Gestores/Consulta | MVP |
| UC019 | Consultar resultado por turma | Gestores/Professor | MVP |
| UC020 | Exportar relatorio | Usuario autorizado | MVP/V2 |
| UC021 | Operar e sincronizar offline | Professor/Aplicador | V2 |
| UC022 | Alterar gabarito e recorrerigir | Gestor autorizado | V2 |
| UC023 | Reprocessar leitura | Usuario autorizado/Suporte | V2 |
| UC024 | Consultar auditoria | Usuario autorizado | V2 |

## 3. UC001 - Autenticar usuario

**Objetivo:** permitir acesso seguro conforme perfil e escopo.

**Pre-condicoes:** usuario ativo e credencial valida.

**Fluxo principal:**

1. O usuario informa suas credenciais.
2. O sistema valida credenciais e situacao da conta.
3. O sistema cria sessao ou emite tokens adequados ao cliente.
4. O sistema registra o acesso e retorna o contexto autorizado.

**Fluxos alternativos:**

- Credencial invalida: negar acesso sem revelar qual campo esta incorreto.
- Usuario inativo: negar acesso e registrar tentativa.
- Excesso de tentativas: aplicar bloqueio temporario.

**Pos-condicao:** usuario autenticado com escopo de acesso definido.

## 4. UC007 - Importar alunos

**Objetivo:** cadastrar alunos em lote com validacao previa.

**Pre-condicoes:** usuario autorizado na escola e arquivo em formato aceito.

**Fluxo principal:**

1. O responsavel seleciona a escola, turma e arquivo.
2. O sistema valida formato, colunas, tipos, obrigatoriedade e duplicidades.
3. O sistema apresenta resumo de inclusoes, atualizacoes e erros.
4. O responsavel confirma a importacao.
5. O sistema processa o lote e registra auditoria.

**Fluxos alternativos:**

- Arquivo invalido: rejeitar e informar erros acionaveis.
- Linhas invalidas: permitir correcao do arquivo antes da confirmacao.
- Processamento longo: executar em fila e notificar conclusao.

**Pos-condicao:** alunos validos cadastrados ou atualizados sem perder historico.

## 5. UC009 - Criar avaliacao

**Objetivo:** preparar uma prova para aplicacao.

**Pre-condicoes:** usuario com permissao para criar avaliacao.

**Fluxo principal:**

1. O usuario informa dados gerais da avaliacao.
2. Define quantidade de questoes, alternativas e modelo de cartao.
3. Cadastra o gabarito oficial.
4. O sistema valida consistencia.
5. O usuario salva a avaliacao em rascunho.

**Pos-condicao:** avaliacao disponivel para revisao e posterior publicacao.

## 6. UC011 - Publicar avaliacao

**Objetivo:** tornar uma avaliacao disponivel para vinculacao e aplicacao.

**Pre-condicoes:** avaliacao em rascunho, modelo e gabarito completos.

**Fluxo principal:**

1. O gestor solicita publicacao.
2. O sistema valida dados, questoes, gabarito e modelo.
3. O sistema altera o status para publicada.
4. O sistema registra auditoria.

**Excecao:** inconsistencias impedem a publicacao e sao apresentadas ao gestor.

## 7. UC013 - Iniciar aplicacao

**Objetivo:** abrir uma aplicacao para recebimento de cartoes.

**Pre-condicoes:** avaliacao publicada, turma vinculada e aplicador autorizado.

**Fluxo principal:**

1. O aplicador seleciona avaliacao e turma.
2. O sistema carrega alunos previstos e status atual.
3. O aplicador confirma o inicio.
4. O sistema registra data, aplicador e muda status para em andamento.
5. O dashboard recebe o evento de inicio.

**Pos-condicao:** aplicacao aceita leituras confirmadas.

## 8. UC014 - Capturar e processar cartao

**Objetivo:** transformar uma foto em respostas detectadas.

**Pre-condicoes:** aplicacao em andamento e camera autorizada.

**Fluxo principal:**

1. O aplicador abre a captura.
2. O app orienta enquadramento e qualidade.
3. O aplicador fotografa o cartao.
4. O app valida a imagem e executa o processamento OMR.
5. O servico retorna codigo, respostas, confiancas e alertas.
6. O app apresenta a tela de revisao.

**Fluxos alternativos:**

- Imagem inadequada: solicitar nova captura.
- Codigo nao identificado: permitir digitacao manual.
- Falha de processamento: registrar erro e oferecer nova tentativa.

**Pos-condicao:** leitura preliminar pronta para revisao, ainda sem resultado valido.

## 9. UC015 - Revisar e confirmar leitura

**Objetivo:** vincular a leitura ao aluno correto e criar o resultado valido.

**Pre-condicoes:** leitura preliminar processada e aplicacao em andamento.

**Fluxo principal:**

1. O aplicador revisa respostas e alertas.
2. O aplicador seleciona ou confirma o aluno.
3. O aplicador confirma ou informa o codigo impresso quando existente.
4. O app gera ou confirma o codigo do sistema quando utilizado ou exigido e envia a operacao com identificador idempotente.
5. O backend valida permissao, aplicacao, aluno, cartao e duplicidades.
6. O backend persiste a leitura final, calcula o resultado e registra auditoria.
7. O sistema atualiza pendencias e publica evento de tempo real.
8. O app informa sucesso.

**Fluxos alternativos:**

- Aluno ja possui cartao valido: rejeitar e orientar fluxo autorizado de substituicao.
- Codigo impresso ja vinculado na prova ou codigo do sistema reutilizado: rejeitar e apresentar conflito especifico.
- Alerta nao revisado: impedir confirmacao.
- Falha de rede: manter operacao pendente para sincronizacao.

**Pos-condicao:** resultado vigente criado e progresso atualizado.

## 10. UC016 - Corrigir leitura manualmente

**Objetivo:** ajustar deteccoes incorretas antes da confirmacao.

**Pre-condicoes:** leitura preliminar ou fluxo autorizado de substituicao.

**Fluxo principal:**

1. O aplicador seleciona a questao sinalizada.
2. Altera a resposta final.
3. Informa motivo obrigatorio para a alteracao.
4. O sistema conserva valor detectado e valor final.
5. A alteracao e incluida na auditoria da confirmacao.

**Pos-condicao:** resposta final corrigida e rastreavel.

## 11. UC018 - Acompanhar aplicacao em tempo real

**Objetivo:** acompanhar progresso e alertas durante a aplicacao.

**Pre-condicoes:** usuario autenticado e autorizado no escopo consultado.

**Fluxo principal:**

1. O usuario acessa o dashboard.
2. O sistema apresenta previstos, lidos, pendentes e alertas.
3. O cliente assina o canal autorizado de atualizacoes.
4. Novas confirmacoes atualizam indicadores e listas.
5. O usuario pode filtrar escola, turma ou aplicacao conforme permissao.

## 12. UC021 - Operar e sincronizar offline

**Objetivo:** permitir continuidade temporaria sem internet.

**Pre-condicoes:** contexto da aplicacao previamente sincronizado no dispositivo.

**Fluxo principal:**

1. O app detecta indisponibilidade de rede.
2. O aplicador captura, revisa e confirma localmente.
3. O app armazena a operacao protegida com identificador unico.
4. Quando a rede volta, o app envia operacoes pendentes.
5. O backend aplica idempotencia e regras de conflito.
6. O app atualiza o status local.

**Excecao:** conflitos de aluno ou cartao exigem resolucao explicita, sem substituicao silenciosa.

## 13. Matriz resumida de permissoes

| Acao | Admin | Gestor Nucleo | Escola | Professor/Aplicador | Consulta | Suporte |
|---|---:|---:|---:|---:|---:|---:|
| Gerenciar nucleos | Sim | Nao | Nao | Nao | Nao | Nao |
| Gerenciar escolas | Sim | No proprio nucleo | Nao | Nao | Nao | Nao |
| Gerenciar turmas e alunos | Sim | Consulta/gestao delegada | Na propria escola | Consulta vinculada | Consulta concedida | Nao |
| Criar/publicar avaliacao | Sim | Sim | Se permitido | Nao no MVP | Nao | Nao |
| Iniciar/finalizar aplicacao | Sim | Sim | Sim | Se vinculado | Nao | Nao |
| Confirmar leitura | Sim | Excepcional | Excepcional | Se vinculado | Nao | Nao |
| Consultar dashboards | Sim | Proprio nucleo | Propria escola | Vinculados | Escopo concedido | Diagnostico |
| Consultar auditoria | Sim | Escopo autorizado | Escopo autorizado | Proprias acoes | Nao | Diagnostico autorizado |

## 14. Referencias

- Requisitos: [02-requisitos-funcionais.md](02-requisitos-funcionais.md)
- Regras: [04-regras-de-negocio.md](04-regras-de-negocio.md)
- Fluxo mobile: [08-mobile-android.md](08-mobile-android.md)
- Roadmap: [11-roadmap-mvp.md](11-roadmap-mvp.md)
