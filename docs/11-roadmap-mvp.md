# Gabarito360 - Roadmap e MVP

## 1. Objetivo do planejamento

Entregar valor em etapas pequenas, validando primeiro o fluxo completo de uma aplicacao real: preparar dados, fotografar cartao, revisar leitura, confirmar resultado e acompanhar progresso.

O roadmap evita iniciar com todos os dashboards, modelos de cartao e integracoes. A prioridade e provar confiabilidade operacional e qualidade do OMR em um escopo controlado.

## 2. Principios de execucao

- Documentar e validar antes de implementar.
- Entregar incrementos demonstraveis de ponta a ponta.
- Priorizar regras de integridade, seguranca e auditoria desde o inicio.
- Testar OMR com cartoes e dispositivos reais.
- Nao ampliar modelos de cartao antes de estabilizar o primeiro.
- Criar commits pequenos e organizados por objetivo.
- Medir qualidade em vez de presumir acuracia.

## 3. Definicao do MVP 1

O MVP 1 deve permitir uma aplicacao real controlada com:

- Um nucleo e suas escolas.
- Usuarios e perfis essenciais.
- Turmas e cadastro/importacao de alunos.
- Avaliacao objetiva de 20 questoes A-E.
- Gabarito oficial.
- Vinculo da avaliacao a turma e criacao da aplicacao.
- App Android com login, selecao, captura, OMR, revisao e confirmacao.
- Vinculo unico entre cartao e aluno.
- Correcao automatica.
- Dashboard simples de progresso.
- Relatorio basico por turma em CSV.
- Auditoria de operacoes criticas.

## 4. Etapas do MVP

### Etapa 0 - Fundacao e decisoes

**Objetivo:** remover ambiguidades antes do codigo.

**Entregas:**

- Revisao e aprovacao dos documentos em `docs`.
- Modelo real do cartao inicial.
- Definicao de unicidade de matricula e codigo do cartao.
- Politica inicial de retencao de imagens e logs.
- Matriz de permissoes aprovada.
- Dataset OMR inicial e dispositivos de teste definidos.
- Criterios mensuraveis do piloto.

**Saida:** decisoes pendentes resolvidas e backlog priorizado.

### Etapa 1 - Base segura do backend

**Objetivo:** disponibilizar identidade, organizacao e autorizacao.

**Entregas:**

- Projeto Laravel e ambiente Docker.
- PostgreSQL, Redis e storage configurados.
- Autenticacao e autorizacao por escopo.
- Nucleos, escolas, usuarios e perfis.
- Auditoria inicial.
- Testes de acesso entre escopos.

**Criterio de conclusao:** uma escola nao consegue consultar ou alterar dados de outra escola.

### Etapa 2 - Cadastros academicos e avaliacoes

**Objetivo:** preparar todos os dados de uma aplicacao.

**Entregas:**

- Turmas, alunos e importacao validada.
- Vinculo de professores/aplicadores.
- Avaliacoes, questoes, modelo de cartao e gabarito.
- Publicacao e vinculo a turma.
- Criacao e inicio de aplicacao.

**Criterio de conclusao:** gestor prepara uma aplicacao valida sem intervencao tecnica.

### Etapa 3 - Prototipo e calibracao OMR

**Objetivo:** provar leitura confiavel do modelo inicial.

**Entregas:**

- Pipeline de imagem reproduzivel.
- Configuracao versionada do cartao.
- Dataset rotulado.
- Deteccao de marcadas, brancas, duplas e duvidosas.
- Metricas de acuracia e tempo.
- Criterios de qualidade para nova captura.

**Criterio de conclusao:** metas definidas para o piloto sao atingidas no dataset de teste.

### Etapa 4 - App Android e fluxo completo

**Objetivo:** executar captura, revisao e confirmacao.

**Entregas:**

- Login e aplicacoes autorizadas.
- Lista de alunos e pendencias.
- Captura orientada.
- Processamento OMR.
- Conferencia e alteracao manual auditavel.
- Confirmacao idempotente.
- Resultado individual e historico recente.

**Criterio de conclusao:** aplicador conclui o fluxo ponta a ponta em dispositivos homologados sem criar duplicidades.

### Etapa 5 - Tempo real, relatorio e operacao

**Objetivo:** acompanhar e encerrar uma aplicacao.

**Entregas:**

- Dashboard de aplicacao.
- Atualizacao via Reverb/WebSockets.
- Finalizacao da aplicacao.
- Relatorio de turma e CSV.
- Monitoramento, backups e runbook inicial.

**Criterio de conclusao:** gestor acompanha o piloto e gera resultado final coerente.

### Etapa 6 - Piloto controlado

**Objetivo:** validar o produto em contexto real limitado.

**Entregas:**

- Treinamento curto dos aplicadores.
- Execucao em poucas turmas e dispositivos homologados.
- Auditoria amostral entre cartao, deteccao e resultado.
- Medicao de tempos, erros e revisoes.
- Lista priorizada de correcoes antes da expansao.

**Criterio de conclusao:** resultados do piloto aprovados pelos responsaveis tecnico e operacional.

## 5. Versao 2

- Modo offline com sincronizacao e conflitos.
- Dashboards consolidados de escola e nucleo.
- Relatorios PDF e XLSX.
- Alteracao controlada de gabarito e recorrection em lote.
- Presenca e ausencia.
- Reprocessamento de leituras.
- MFA para perfis privilegiados.
- Consulta avancada de auditoria.
- Melhorias de escalabilidade e observabilidade.

## 6. Versao 3

- Multiplos modelos configuraveis de cartao.
- Avaliacoes com pesos e politicas ampliadas.
- Integracoes com sistemas academicos.
- Agendamento de relatorios.
- Analises pedagogicas avancadas.
- Aplicativo para outras plataformas, se houver demanda.
- Controles aprimorados de governanca e retencao.

## 7. Funcionalidades futuras

- Geracao de cartoes personalizados.
- Identificacao por QR Code com dados minimizados.
- Portal de consulta para aluno/responsavel, se autorizado.
- Analise longitudinal de desempenho.
- Deteccao assistida por modelos de visao computacional, somente se houver beneficio mensuravel.
- Operacao multi-regiao e alta disponibilidade ampliada.

## 8. Backlog inicial de historias

### EP01 - Acesso e organizacao

| ID | Historia | Criterios de aceite resumidos |
|---|---|---|
| US001 | Como administrador, quero cadastrar um nucleo para organizar suas escolas. | Campos validados; codigo unico; operacao auditada. |
| US002 | Como gestor do nucleo, quero cadastrar escolas para administrar a rede. | Escola vinculada ao nucleo; outro nucleo nao acessa; inativacao preserva historico. |
| US003 | Como gestor, quero cadastrar usuarios e perfis para delegar operacoes. | Perfil e escopo validados; usuario inativo perde acesso. |
| US004 | Como usuario, quero autenticar com seguranca para acessar meu contexto. | Credencial valida entra; invalida nao revela detalhes; acesso fica registrado. |

### EP02 - Turmas e alunos

| ID | Historia | Criterios de aceite resumidos |
|---|---|---|
| US005 | Como responsavel da escola, quero cadastrar turmas para organizar alunos. | Turma pertence a escola/ano; codigo nao duplica no escopo. |
| US006 | Como responsavel, quero cadastrar alunos para inclui-los nas aplicacoes. | Dados obrigatorios validados; escola externa nao acessa. |
| US007 | Como responsavel, quero importar alunos para reduzir trabalho manual. | Arquivo validado; erros por linha; confirmacao explicita; sem duplicidade indevida. |
| US008 | Como responsavel, quero vincular aplicadores a turmas para autorizar o app. | Aplicador ve somente turmas vinculadas. |

### EP03 - Avaliacao e aplicacao

| ID | Historia | Criterios de aceite resumidos |
|---|---|---|
| US009 | Como gestor, quero criar uma avaliacao para aplicar uma prova objetiva. | Numero de questoes e alternativas validos; inicia em rascunho. |
| US010 | Como gestor, quero cadastrar o gabarito oficial para corrigir resultados. | Uma resposta por questao; completude validada. |
| US011 | Como gestor, quero publicar e vincular a avaliacao a turma. | Publicacao bloqueada se incompleta; apenas turma autorizada recebe aplicacao. |
| US012 | Como aplicador, quero iniciar a aplicacao para receber cartoes. | Apenas vinculado inicia; alunos previstos carregados; evento auditado. |

### EP04 - Leitura e correcao

| ID | Historia | Criterios de aceite resumidos |
|---|---|---|
| US013 | Como aplicador, quero fotografar um cartao para detectar respostas. | Captura valida qualidade; OMR retorna 20 questoes e confiancas. |
| US014 | Como aplicador, quero revisar alertas para evitar confirmacoes incorretas. | Brancos, duplas e duvidas destacados; revisao explicita. |
| US015 | Como aplicador, quero corrigir uma deteccao para registrar a resposta observada. | Detectada e final preservadas; alteracao auditada. |
| US016 | Como aplicador, quero vincular cartao ao aluno e confirmar a leitura. | Aluno e codigo unicos; operacao idempotente; resultado calculado. |
| US017 | Como gestor, quero manter historico de tentativas para investigar divergencias. | Tentativas anteriores permanecem consultaveis; cancelamento nao apaga dados. |

### EP05 - Acompanhamento e relatorio

| ID | Historia | Criterios de aceite resumidos |
|---|---|---|
| US018 | Como gestor, quero acompanhar progresso para identificar pendencias. | Totais coerentes; atualizacao em ate 5 segundos; escopo respeitado. |
| US019 | Como aplicador, quero ver alunos pendentes para concluir a turma. | Lista atualiza apos confirmacao; ausentes tratados conforme versao. |
| US020 | Como gestor, quero exportar resultado da turma para analise. | CSV usa resultados vigentes; filtros e download auditados. |

## 9. Estrategia de testes

### 9.1 Backend e API

- Unidade para regras de calculo e estados.
- Integracao para transacao de confirmacao.
- Autorizacao para cada perfil e escopo.
- Contrato da API e idempotencia.
- Importacao, exportacao, filas e eventos.

### 9.2 Mobile

- Unidade, widgets e integracao.
- Camera e OMR em aparelhos reais.
- Perda de rede, repeticao e interrupcao.
- Usabilidade em contexto de aplicacao.

### 9.3 OMR

- Dataset rotulado e versionado.
- Regressao por configuracao do modelo.
- Variacoes de camera, iluminacao, impressao e marcacao.
- Auditoria amostral no piloto.

### 9.4 Seguranca e operacao

- Testes de acesso horizontal e vertical.
- Validacao de upload e download.
- Testes de carga no fluxo de confirmacao e dashboard.
- Restauracao de backup.
- Revisao de logs e dados pessoais.

## 10. Definicao de pronto

Uma historia so esta pronta quando:

- Criterios de aceite foram atendidos.
- Validacao, autenticacao e autorizacao foram implementadas quando aplicaveis.
- Auditoria foi aplicada a operacoes criticas.
- Testes proporcionais ao risco foram aprovados.
- Documentacao afetada foi atualizada.
- Erros e estados vazios foram tratados.
- Nao houve regressao conhecida no fluxo principal.
- Observabilidade e operacao foram consideradas.

## 11. Riscos e mitigacoes do roadmap

| Risco | Mitigacao |
|---|---|
| OMR nao atingir qualidade no cartao atual | Melhorar o modelo impresso antes de complexificar algoritmo |
| Escopo crescer antes do fluxo principal | Usar MVP e criterios de saida por etapa |
| Offline introduzir conflitos cedo | Entregar online primeiro e projetar idempotencia desde o MVP |
| Permissoes inconsistentes | Testes sistematicos de escopo desde a Etapa 1 |
| Dados de piloto insuficientes | Planejar dataset e auditoria amostral na Etapa 0 |
| Relatorios divergirem do operacional | Definir resultados vigentes e formulas unicas |

## 12. Decisoes necessarias antes da implementacao

1. Aprovar nome e escopo do produto como Gabarito360.
2. Disponibilizar o modelo fisico inicial do cartao.
3. Definir responsaveis por nucleo, escola e suporte.
4. Aprovar politica LGPD, retencao e acesso a imagens.
5. Definir metas do piloto e acuracia OMR.
6. Definir versoes Android e aparelhos de homologacao.
7. Escolher abordagem inicial do painel web dentro do ecossistema Laravel.
