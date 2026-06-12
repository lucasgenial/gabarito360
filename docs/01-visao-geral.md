# Gabarito360 - Visao Geral do Produto

## 1. Identificacao

- **Produto:** Gabarito360
- **Finalidade:** gestao, aplicacao, leitura e correcao de cartoes-resposta por foto
- **Status deste documento:** escopo do MVP aprovado
- **Publico:** gestores educacionais, equipe de produto, desenvolvimento, QA, suporte e seguranca

## 2. Resumo executivo

O Gabarito360 e uma plataforma web e mobile para administrar avaliacoes objetivas em uma rede de ensino. O sistema organiza nucleos de educacao, escolas, turmas, alunos, provas e gabaritos oficiais. Durante uma aplicacao, professores ou aplicadores usam um aplicativo Android para fotografar o cartao-resposta, revisar a leitura automatica, vincular o cartao ao aluno e confirmar o resultado.

As leituras confirmadas alimentam dashboards em tempo real e relatorios por aluno, turma, escola, avaliacao e nucleo. A solucao deve reduzir digitacao manual, acelerar a consolidacao dos resultados e manter rastreabilidade das operacoes.

## 3. Problema a resolver

Processos manuais de correcao de cartoes-resposta consomem tempo, atrasam a disponibilidade dos resultados e estao sujeitos a erros de digitacao. Tambem dificultam o acompanhamento de aplicacoes simultaneas em varias escolas e a identificacao rapida de alunos pendentes ou leituras inconsistentes.

O Gabarito360 centraliza esse fluxo, utilizando OMR (Optical Mark Recognition) para detectar marcacoes em cartoes-resposta e mantendo revisao humana antes da confirmacao quando necessario.

## 4. Objetivos

### 4.1 Objetivos principais

- Centralizar cadastros de nucleos, escolas, usuarios, turmas e alunos.
- Permitir criar, publicar, aplicar e encerrar avaliacoes.
- Cadastrar e versionar gabaritos oficiais.
- Ler cartoes-resposta por foto em aplicativo Android.
- Detectar respostas marcadas, em branco, duplas ou duvidosas.
- Vincular cada cartao ao aluno correto no momento da leitura.
- Corrigir automaticamente respostas confirmadas.
- Acompanhar o progresso das aplicacoes em tempo real.
- Gerar relatorios e exportacoes auditaveis.
- Proteger dados pessoais conforme boas praticas de seguranca e LGPD.

### 4.2 Resultado esperado do MVP

O MVP deve comprovar uma aplicacao real controlada de ponta a ponta, desde os cadastros administrativos ate a confirmacao da leitura, correcao automatica, acompanhamento da aplicacao e emissao do relatorio basico por turma.

O gate de liberacao do MVP considera obrigatorios:

- Cadastros essenciais de nucleo, escolas, usuarios, turmas e alunos.
- Importacao validada de alunos por CSV.
- Prova objetiva padronizada com 20 questoes A-E, gabarito vigente e um modelo homologado de cartao.
- Aplicacao online pelo app Android, com captura, OMR, revisao humana, vinculacao do cartao ao aluno e confirmacao.
- Correcao automatica, dashboard simples por aplicacao, relatorio por turma em tela e CSV e auditoria de operacoes criticas.
- Autorizacao por perfil e escopo conforme a matriz aprovada em [05-casos-de-uso.md](05-casos-de-uso.md).

### 4.3 Fora do MVP

- Modo offline completo, sincronizacao em lote e resolucao de conflitos.
- Relatorios PDF e XLSX.
- Dashboards consolidados avancados de nucleo e escola.
- Multiplos modelos configuraveis de cartao em producao.
- Alteracao de gabarito publicado e recorrection em lote.
- Consulta operacional completa de auditoria e console de suporte.
- Criacao e distribuicao do caderno de questoes.
- Correcao de respostas discursivas.
- Uso obrigatorio de inteligencia artificial generativa.
- Integracao com todos os sistemas academicos existentes.
- Suporte inicial a iOS.
- Analise pedagogica preditiva avancada.

Funcionalidades fora do MVP podem ser planejadas ou preparadas tecnicamente, mas nao bloqueiam a liberacao do piloto.

## 5. Perfis de usuario

| Perfil | Escopo principal | Responsabilidades |
|---|---|---|
| Administrador Geral | Todo o sistema | Configuracao global, usuarios, perfis, suporte e auditoria |
| Gestor do Nucleo | Nucleo e escolas vinculadas | Escolas, avaliacoes oficiais, acompanhamento e relatorios consolidados |
| Responsavel da Escola | Uma escola | Usuarios locais, turmas, alunos, aplicacoes e relatorios da escola |
| Professor | Turmas e aplicacoes vinculadas | Consulta de turmas, aplicacao, leitura e acompanhamento |
| Aplicador | Aplicacoes vinculadas | Captura, revisao e confirmacao de cartoes |
| Leitor/Consulta | Escopo concedido | Consulta de dashboards e relatorios, sem alteracao operacional |
| Suporte Tecnico | Escopo controlado e auditado | Diagnostico tecnico sem acesso irrestrito a dados pessoais |

## 6. Escopo funcional do MVP

### 6.1 Gestao administrativa

- Nucleos de educacao e escolas.
- Usuarios, perfis, permissoes e vinculos.
- Turmas, alunos e importacao por planilha.
- Avaliacoes, questoes, modelos de cartao e gabaritos oficiais.
- Aplicacoes por escola e turma.

### 6.2 Operacao mobile

- Autenticacao do aplicador.
- Selecao de escola, avaliacao, turma e aluno.
- Captura orientada da imagem.
- Processamento OMR e leitura do codigo impresso do cartao, quando existente.
- Revisao e correcao manual auditada.
- Confirmacao e sincronizacao com o backend.
- Operacao online, com idempotencia para repeticao segura de requisicoes.

### 6.3 Acompanhamento e analise

- Progresso de aplicacoes em tempo real.
- Resultados e pendencias da aplicacao.
- Indicadores de pendencias e inconsistencias.
- Relatorio por turma em tela e exportacao CSV.
- Auditoria de eventos criticos.

## 7. Modulos do sistema

1. Autenticacao e controle de acesso.
2. Gestao de nucleos e escolas.
3. Gestao de usuarios, perfis e permissoes.
4. Gestao de turmas e alunos.
5. Gestao de avaliacoes, questoes e gabaritos.
6. Gestao de aplicacoes.
7. Aplicativo Android.
8. Processamento OMR.
9. Correcao automatica e resultados.
10. Dashboards em tempo real.
11. Relatorios e exportacoes.
12. Arquivos, auditoria e sincronizacao.

## 8. Fluxo principal de negocio

1. O gestor configura nucleo, escolas e usuarios.
2. A escola cadastra turmas e alunos, manualmente ou por importacao.
3. Um usuario autorizado cria a avaliacao, define o modelo de cartao e cadastra o gabarito oficial.
4. A avaliacao e publicada e vinculada as turmas.
5. O aplicador inicia a aplicacao no app Android.
6. Para cada aluno, o aplicador fotografa o cartao-resposta.
7. O modulo OMR processa a imagem e retorna respostas e confiancas.
8. O aplicador revisa alertas, confirma o aluno, preserva o codigo impresso quando houver e confirma o codigo do sistema quando utilizado ou exigido.
9. O backend valida a operacao, corrige a prova e registra o resultado.
10. Dashboards e pendencias sao atualizados em tempo real.
11. A aplicacao e finalizada e os relatorios ficam disponiveis.

## 9. Arquitetura recomendada

| Camada | Tecnologia inicial | Responsabilidade |
|---|---|---|
| Backend/API | Laravel 12 | Regras de negocio, API REST, autenticacao, filas e eventos |
| Painel web | Laravel Blade + Livewire + Tailwind | Administracao, dashboard simples e relatorio do MVP |
| Banco de dados | MariaDB | Persistencia relacional e integridade |
| Cache e filas | Redis | Filas, cache, locks e apoio ao tempo real |
| Tempo real | Laravel Reverb/WebSockets | Atualizacao de progresso e dashboards |
| Mobile | Flutter para Android | Captura, revisao e operacao online do MVP |
| OMR | OpenCV, estrategia hibrida | Pre-processamento local e validacao/reprocessamento no backend |
| Arquivos | Storage S3 compativel | Imagens, importacoes, relatorios e artefatos processados |
| Infraestrutura | Docker, Nginx e TLS | Implantacao, proxy reverso e comunicacao segura |

## 10. Estrategia OMR

O MVP deve priorizar um modelo de cartao controlado, semelhante ao da OBMEP, inicialmente com 20 questoes e alternativas de A a E. O cartao deve conter marcadores de referencia e pode possuir codigo externo ja impresso, cuja regiao e formato dependem do modelo.

A estrategia recomendada e hibrida:

- O app valida enquadramento e qualidade antes da captura.
- O processamento local fornece retorno rapido para revisao.
- O backend pode validar ou reprocessar imagens quando necessario.
- Toda leitura de baixa confianca exige acao explicita do aplicador.
- A confirmacao humana permanece como barreira de qualidade.

## 11. Seguranca e privacidade

- Aplicar autorizacao por perfil e escopo de dados.
- Minimizar coleta e exposicao de dados pessoais de alunos.
- Criptografar trafego com HTTPS/TLS.
- Armazenar senhas apenas com hash forte.
- Auditar leituras, correcoes manuais, alteracoes de gabarito e operacoes administrativas.
- Aplicar retencao e descarte definidos para imagens de cartoes.
- Restringir suporte tecnico por necessidade e registrar acessos.
- Obter autorizacao antes de coletar localizacao aproximada.

## 12. Premissas e restricoes

- O codigo impresso e o codigo do sistema nao substituem o vinculo explicito com o aluno.
- A conexao de internet pode ser instavel durante a aplicacao.
- A qualidade das cameras e das fotos varia entre dispositivos.
- O MVP utiliza um modelo de cartao padronizado.
- O sistema deve manter historico em vez de apagar registros operacionais.
- A escola acessa apenas seus dados; o nucleo acessa apenas escolas vinculadas.

## 13. Indicadores de sucesso

- Percentual de cartoes processados sem redigitacao completa.
- Tempo medio entre captura e confirmacao.
- Taxa de leituras que exigem revisao manual.
- Taxa de erro identificada em auditorias amostrais.
- Percentual de aplicacoes acompanhadas em tempo real.
- Tempo para disponibilizar relatorio final apos encerramento.
- Quantidade de incidentes de sincronizacao ou duplicidade.

## 14. Riscos iniciais

| Risco | Impacto | Tratamento inicial |
|---|---|---|
| Fotos com baixa qualidade | Leitura incorreta | Guia de captura, validacao de qualidade e revisao |
| Variacao de impressao | Desalinhamento do OMR | Marcadores, calibracao e modelo versionado |
| Repeticao de envio | Resultados duplicados | UUID, idempotencia e restricoes de integridade |
| Alteracao indevida de gabarito | Resultados inconsistentes | Bloqueio, permissao especial, versao e recorrection |
| Exposicao de dados pessoais | Risco legal e operacional | Minimizacao, RBAC, logs e retencao |
| Sobrecarga em aplicacoes simultaneas | Atraso nos dashboards | Filas, cache, testes de carga e escalabilidade |

## 15. Documentos relacionados

- [Requisitos funcionais](02-requisitos-funcionais.md)
- [Requisitos nao funcionais](03-requisitos-nao-funcionais.md)
- [Regras de negocio](04-regras-de-negocio.md)
- [Casos de uso](05-casos-de-uso.md)
- [Modelagem do banco de dados](06-modelagem-banco.md)
- [API REST](07-api.md)
- [Aplicativo Android](08-mobile-android.md)
- [Modulo OMR](09-modulo-omr.md)
- [Dashboards e relatorios](10-dashboard-relatorios.md)
- [Roadmap do MVP](11-roadmap-mvp.md)
