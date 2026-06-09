# Gabarito360 - Requisitos Nao Funcionais

## 1. Convencoes

- **Identificador:** `RNFnnn`.
- Os valores apresentados sao metas iniciais e devem ser validados por testes e pela capacidade da infraestrutura contratada.

## 2. Seguranca e privacidade

| ID | Requisito |
|---|---|
| RNF001 | Todo trafego externo deve utilizar HTTPS/TLS em versao suportada. |
| RNF002 | Senhas devem ser armazenadas com algoritmo de hash forte suportado pelo framework, nunca de forma reversivel. |
| RNF003 | Toda rota protegida deve validar autenticacao, permissao e escopo de dados no backend. |
| RNF004 | Tokens e sessoes devem possuir expiracao, revogacao e rotacao conforme o tipo de cliente. |
| RNF005 | Operacoes sensiveis devem ser protegidas contra abuso, repeticao e forca bruta. |
| RNF006 | Segredos e credenciais de infraestrutura nao devem ser versionados no repositorio. |
| RNF007 | Dados pessoais devem ser coletados e exibidos apenas quando necessarios a finalidade declarada. |
| RNF008 | Imagens de cartoes devem possuir politica explicita de acesso, retencao e descarte. |
| RNF009 | Logs tecnicos nao devem registrar senhas, tokens, imagens completas ou dados pessoais desnecessarios. |
| RNF010 | O sistema deve suportar atendimento auditavel a solicitacoes de titulares conforme a LGPD. |
| RNF011 | Contas privilegiadas devem suportar autenticacao multifator na V2. |

## 3. Desempenho e capacidade

| ID | Requisito |
|---|---|
| RNF012 | Operacoes comuns da API devem responder em ate 500 ms no percentil 95, excluindo uploads, exportacoes e processamento OMR. |
| RNF013 | A confirmacao de uma leitura deve responder em ate 2 segundos no percentil 95, sem contar transferencia de imagem em rede lenta. |
| RNF014 | O app deve apresentar o resultado preliminar do OMR em ate 5 segundos em dispositivo homologado. |
| RNF015 | Atualizacoes de progresso devem chegar aos dashboards em ate 5 segundos apos a confirmacao. |
| RNF016 | Importacoes, exportacoes, recorrecoes e reprocessamentos devem executar em fila quando puderem exceder o tempo de uma requisicao. |
| RNF017 | O sistema deve suportar crescimento horizontal de workers e servicos de tempo real. |
| RNF018 | Consultas frequentes de dashboard devem utilizar agregacoes, indices ou cache para evitar carga excessiva. |

## 4. Disponibilidade, continuidade e resiliencia

| ID | Requisito |
|---|---|
| RNF019 | A meta inicial de disponibilidade mensal do backend em producao e 99,5%, excluindo manutencoes programadas. |
| RNF020 | Falhas temporarias de fila ou tempo real nao devem causar perda de leituras confirmadas. |
| RNF021 | Operacoes enviadas pelo app devem ser idempotentes para suportar repeticao por falha de rede. |
| RNF022 | Backups do banco devem ser automaticos, monitorados e testados por restauracao periodica. |
| RNF023 | Arquivos criticos devem possuir redundancia compativel com a politica de continuidade. |
| RNF024 | O plano inicial deve definir RPO de ate 24 horas e RTO de ate 8 horas; metas devem ser revistas antes da producao. |

## 5. Usabilidade e acessibilidade

| ID | Requisito |
|---|---|
| RNF025 | O fluxo mobile de captura ate confirmacao deve exigir o menor numero pratico de passos. |
| RNF026 | Alertas de branco, dupla marcacao e baixa confianca devem ser distinguiveis por texto e nao apenas por cor. |
| RNF027 | Interfaces web devem buscar conformidade com WCAG 2.1 nivel AA nos fluxos principais. |
| RNF028 | Mensagens de erro devem indicar problema e acao de recuperacao sem expor detalhes internos. |
| RNF029 | O app deve manter botoes e alvos de toque adequados ao uso em campo. |
| RNF030 | Datas, horarios e numeros devem seguir localidade configurada, com armazenamento temporal padronizado. |

## 6. Compatibilidade e operacao mobile

| ID | Requisito |
|---|---|
| RNF031 | O app deve suportar as versoes Android definidas na matriz de homologacao do projeto. |
| RNF032 | O app deve declarar e solicitar apenas permissoes necessarias, explicando camera e localizacao quando aplicavel. |
| RNF033 | Dados offline devem ser armazenados de forma protegida no dispositivo. |
| RNF034 | O app deve sobreviver a encerramento inesperado sem perder operacoes ja confirmadas localmente. |
| RNF035 | A sincronizacao deve tolerar conexao instavel e apresentar conflitos ao usuario quando nao puder resolve-los automaticamente. |

## 7. Manutenibilidade e qualidade

| ID | Requisito |
|---|---|
| RNF036 | O backend deve seguir convencoes do Laravel, separando autorizacao, validacao e regras de negocio. |
| RNF037 | Alteracoes de banco devem ser versionadas por migrations e possuir estrategia de rollback quando viavel. |
| RNF038 | Contratos da API devem ser documentados e versionados. |
| RNF039 | Funcionalidades criticas devem possuir testes automatizados de unidade, integracao e autorizacao. |
| RNF040 | O modulo OMR deve possuir conjunto de imagens de referencia e metricas de acuracia reproduziveis. |
| RNF041 | O pipeline de entrega deve executar verificacoes estaticas, testes e validacoes de seguranca definidas pelo projeto. |
| RNF042 | Configuracoes devem ser externas ao codigo e separadas por ambiente. |

## 8. Observabilidade e auditoria

| ID | Requisito |
|---|---|
| RNF043 | Servicos devem emitir logs estruturados com identificador de correlacao. |
| RNF044 | Metricas devem cobrir latencia, erros, filas, conexoes em tempo real e processamentos OMR. |
| RNF045 | Falhas criticas e filas acumuladas devem gerar alertas operacionais. |
| RNF046 | Registros de auditoria devem ser imutaveis para usuarios comuns e possuir retencao definida. |
| RNF047 | Horarios de eventos devem ser persistidos de forma consistente e exibidos no fuso do usuario. |

## 9. Infraestrutura e implantacao

| ID | Requisito |
|---|---|
| RNF048 | Os componentes devem ser implantaveis por containers Docker. |
| RNF049 | O Nginx deve atuar como proxy reverso, aplicar TLS e limites de requisicao adequados. |
| RNF050 | Banco, Redis e storage nao devem ficar publicamente expostos. |
| RNF051 | Ambientes de desenvolvimento, homologacao e producao devem ser isolados. |
| RNF052 | Atualizacoes de producao devem possuir procedimento de reversao e janela de manutencao quando necessaria. |

## 10. Criterios de verificacao

- Testes de carga validam `RNF012-RNF018`.
- Simulacoes de indisponibilidade e restauracao validam `RNF019-RNF024`.
- Revisoes de seguranca e testes de autorizacao validam `RNF001-RNF011`.
- Testes em dispositivos homologados validam `RNF014` e `RNF031-RNF035`.
- Auditorias de acessibilidade e usabilidade validam `RNF025-RNF030`.
- Pipeline, cobertura e dataset OMR validam `RNF036-RNF042`.
