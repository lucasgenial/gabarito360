# 38 — Avaliação preliminar de impacto: dados escolares e portal (L2)

Data: 31/10/2026. Documento técnico preliminar, para revisão do controlador. Não é aprovação de riscos, laudo de conformidade ou autorização para liberar o portal. Fonte técnica: main `ae5d34e600c12cdac48dab2f73cef2fee3ae1a22`; inventário em [docs/34](34-inventario-de-dados.md). Nenhum registro pessoal ou ambiente de produção foi consultado nesta análise.

## 1. Responsáveis e finalidade

| Papel | Preenchimento |
|---|---|
| Instituição/controlador dos dados escolares | `[INSTITUIÇÃO / IDENTIFICAÇÃO]` |
| Representante que decide finalidade e aceita riscos | `[NOME / FUNÇÃO / CONTATO]` |
| Operador técnico | Gabarito360; `[RAZÃO SOCIAL / IDENTIFICAÇÃO]` |
| Encarregado/canal de titulares | `[NOME / CONTATO]` |
| Responsável técnico e operacional | `[NOME / FUNÇÃO / CONTATO]` |
| Revisor de segurança/privacidade | `[RESPONSÁVEL / DATA]` |
| Próxima revisão | Antes do piloto e após mudança de dados, finalidade, fornecedor, retenção, acesso ou incidente |

O tratamento educacional atual serve a cadastro, provas, correção, revisão e relatórios. O portal proposto permite à família consultar os resultados de um estudante, com recuperação do acesso pela secretaria. O portal não foi identificado como implementado no código auditado; controles planejados abaixo não são apresentados como controles existentes.

Os papéis e hipóteses de base por finalidade permanecem sujeitos à documentação de cada controlador, conforme docs/33 e docs/34. Aceite de ciência e eventual autorização de comunicação promocional são registros distintos; não usar um deles para presumir base de todo o tratamento educacional.

Antes do piloto, conciliar também os registros de D10/D14 no quadro do plano com as validações e decisões já recebidas. Esta entrega não altera o desenho nem substitui o registro dessas decisões.

## 2. Orientação usada

A [ANPD](https://www.gov.br/anpd/pt-br/canais_atendimento/agente-de-tratamento/relatorio-de-impacto-a-protecao-de-dados-pessoais-ripd), em orientação consultada em 31/10/2026, atribui ao controlador a elaboração do RIPD e recomenda avaliar previamente tratamentos que possam gerar alto risco. Este material fornece insumos técnicos para essa avaliação, com processos, riscos e medidas; a validação e as decisões institucionais ainda não estão preenchidas.

A [LGPD em texto atualizado da Câmara](https://www2.camara.leg.br/legin/fed/lei/2018/lei-13709-14-agosto-2018-787077-normaatualizada-pl.html) é referência para registro de operações e relatório de impacto. Não se conclui aqui que toda operação com menores tenha a mesma base ou que todo dado escolar seja dado pessoal sensível. A instituição deve avaliar o caso concreto e o melhor interesse, sem coletar dados adicionais apenas para preencher este relatório.

## 3. Titulares, dados e dimensão

- Estudantes, inclusive crianças/adolescentes: nome, matrícula, nascimento, turma, escola, foto do cartão, respostas, notas e relatórios. CPF, gênero e foto de aluno ainda constam do cadastro atual; revisar necessidade e cumprir a decisão de avatar (L6).
- Responsáveis: nome no cadastro atual; contatos e dados de acesso do portal são etapas futuras, cuja coleta deve ser justificada.
- Profissionais e gestores: conta, vínculos e registros das operações que executam.
- Visitantes: informações do contato e metadados técnicos, conforme inventário.

Quantidade real de titulares, idades e distribuição por instituição: `[A CONFIRMAR PELO CONTROLADOR]`. A meta do planejamento (100 instituições, 600 professores e 1.000 responsáveis) é objetivo de capacidade, não volume medido de dados tratados. Não extrair listas nominais para preencher esses campos; usar contagens agregadas aprovadas, se necessárias.

Campos livres e documentos enviados podem conter dados excessivos ou sensíveis, mesmo quando não são solicitados. Essa possibilidade deve entrar na orientação aos usuários, minimização e resposta a incidentes.

## 4. Fluxo atual e fluxo proposto

| Etapa | Atual no código | Proposto para o portal |
|---|---|---|
| Entrada dos dados | Cadastro/importação CSV e captura/upload de cartões | Instituição mantém cadastro necessário e entrega orientações/código conforme desenho aprovado |
| Tratamento | OMR Python local, notas, revisão e relatórios | Exibição apenas dos resultados do estudante associado ao acesso |
| Identificação familiar | Nome do responsável no cadastro; não equivale a verificação da pessoa | Primeiro acesso com matrícula/nascimento e emissão de código; risco conhecido no plano |
| Acesso | Conta autenticada e permissões/escopo nas operações atuais | Matrícula + código, Turnstile, limites, resposta uniforme e sessão de 15 minutos previstos em P3 |
| Recuperação | Fluxos de conta existentes, sem recuperação do portal implementada | Secretaria consulta/gera outro código; código anterior invalidado e operação auditada |
| Saída | Impressões/PDFs; cópias ficam com quem baixa/imprime | `noindex`, `no-store`, dados mínimos e marca d'água com identificador do acesso, nunca código de entrada |
| Retenção e direitos | Sem rotina geral de retenção/titulares identificada | Prazos institucionais aprovados, protocolo, exportação/eliminação e regras para backups |

Decisões arquiteturais já aprovadas são preservadas: primeira versão sem código por e-mail; recuperação na secretaria; código legível apenas aos perfis autorizados e protegido no armazenamento; propaganda fora das telas de alunos/responsáveis. A possibilidade de matrícula/nascimento serem conhecidos por terceiros já foi reconhecida no plano. Este relatório detalha as condições para reduzir esse risco, sem alterar a decisão de produto nesta entrega.

## 5. Necessidade e proporcionalidade a validar

| Dado/uso | Necessidade a demonstrar | Encaminhamento |
|---|---|---|
| Nome, turma, matrícula, prova, respostas e notas | Vincular avaliação ao estudante e informar resultado | Confirmar finalidade institucional, perfis autorizados e prazo |
| Nascimento no primeiro acesso | Elemento auxiliar do desenho aprovado, sem prova suficiente de responsabilidade legal | Não tratá-lo como segredo; priorizar entrega assistida e possibilidade de invalidação pela escola |
| CPF, gênero e foto de aluno | Não demonstrados como necessários ao portal no desenho atual | L6/A5 e revisão do cadastro; não acrescentar CPF como autenticador |
| Foto do cartão e derivados | Conferência da correção e eventual contestação | Acesso limitado, prazo associado à finalidade e eliminação consistente |
| Código de acesso | Dar acesso ao resultado do estudante | Proteger código, restringir leitura e auditar consulta/geração/impressão; não expor em URL |
| IP, horário e metadados | Segurança, apuração e limitação de abuso | Minimizar, restringir acesso e definir `[PRAZO DOS LOGS]` |
| PDF/impresso | Entregar documento educacional quando necessário | Minimizar conteúdo e explicar cuidado com cópias; não prometer impedir fotografia ou compartilhamento |
| Comunicação promocional | Não necessária à consulta de resultados | Escolha independente e opcional, sem condicionar acesso às notas |

## 6. Método de avaliação

Escala interna preliminar, não uma classificação normativa automática: probabilidade P e impacto I variam de 1 (menor) a 5 (maior); risco = P × I. Faixas usadas para priorização: 1–4 baixo; 5–9 moderado; 10–15 alto; 16–25 crítico. Os valores são juízo técnico qualitativo com justificativa, não frequência medida de incidentes.

As pontuações descrevem o cenário com as lacunas atuais ou, no portal, o desenho antes de implementar os controles previstos. Não foi calculado risco residual como se medidas propostas já estivessem comprovadas.

## 7. Riscos, evidências e tratamento

| ID | Cenário e impacto para a pessoa | P / I / nível | Controle observado ou decisão existente | Medida e evidência necessária / responsável |
|---|---|---|---|---|
| R01 | Terceiro conhece matrícula/nascimento e chega primeiro ao portal; obtém notas e código | 4 / 5 / 20 crítico: dados podem ser conhecidos por colegas/familiares | Risco reconhecido no plano; entrega prévia em papel e recuperação pela secretaria são opções decididas | Entrega assistida quando necessária, registro do primeiro acesso, aviso à escola, invalidação do código, limites e revisão do piloto; P1/P3/P4/P7, controlador + engenharia |
| R02 | Troca de identificador ou falha de escopo mostra dados de outro aluno/instituição | 3 / 5 / 15 alto: resultados de menores exigem isolamento verificável | Permissões/Escopo existem no sistema; portal ainda não implementado | Matriz de acesso, testes negativos por aluno/instituição, download e sessão; P1/P3/P5/P7, segurança + engenharia |
| R03 | Código do portal é consultado por perfil indevido ou vazado em log/URL/impresso errado | 3 / 5 / 15 alto: credencial reutilizável permite acesso continuado | Criptografia, secretaria exclusiva, auditoria e rotação previstos; ainda não comprovados | Testes de API que neguem código ao professor/outros perfis, cifragem, auditoria sem segredo e invalidação; L3/AU1/P3/P4 |
| R04 | Foto de aluno, CPF ou dado sensível em campo livre amplia dano em acesso indevido | 3 / 4 / 12 alto: coleta excedente observada no modelo/API | Decisão de avatar e mínimo do autônomo existe, mas cadastro ainda aceita foto | Implementar L6/A5, orientar campos livres e revisar necessidade; controlador + produto/engenharia |
| R05 | Foto/resultado fica acessível por caminho público ou cópia não autorizada | 3 / 4 / 12 alto: disco público existe; proteção efetiva completa não auditada | Imagens de cartão têm rotas controladas e assinadas | Auditar acesso direto, assinatura/expiração, miniaturas e cache; retirar qualquer caminho de desvio; L3, segurança/operação |
| R06 | Link de conta, e-mail de contato ou conteúdo pessoal permanece em fila, logs ou failed_jobs | 3 / 4 / 12 alto: parâmetros de URL e payloads são observados no código | Hash no registro de e-mails; testes impedem senha em sessão em fluxos existentes | Redação de logs, retenção, acesso à fila e tratamento de falhas; L3/L4, operação/engenharia |
| R07 | Prazo indefinido ou restauração reintroduz informação que já devia ser eliminada | 3 / 4 / 12 alto: sem expurgo geral identificado | Backups de oito dias e restauração B1 testada; exclusão específica de B6 limpa recibo | Matriz aprovada, eliminação por titular/cópias e procedimento de reaplicação após restauração; L4, controlador/operação |
| R08 | Dados atravessam terceiros/países sem cadeia e instrumento confirmados | 3 / 4 / 12 alto: serviços externos identificados; contratos não inspecionados | Documentos públicos de fornecedores disponíveis, sem comprovação da conta | Preencher matriz internacional de docs/34 e revisar contratos; responsável/encarregado |
| R09 | Leitura automática incorreta produz nota errada ou expõe aluno indevidamente | 2 / 4 / 8 moderado: revisão existe, mas erro de leitura é possível | Marcação de ambiguidade e revisão humana; B6 tem testes de duplicação/substituição | Validar com cartões representativos, contestação e revisão acessível; não usar confiança como garantia de acerto; equipe pedagógica/engenharia |
| R10 | Familiar autorizado redistribui PDF ou fotografia com dados escolares | 3 / 4 / 12 alto: cópia fora da aplicação não é controlável integralmente | Limitação reconhecida no plano | Minimizar documento, orientação e marca d'água não secreta; P5/P6, controlador/produto |
| R11 | Marketing se confunde com condição de acesso a resultado | 3 / 3 / 9 moderado no desenho ainda não implementado | Termo do documento 33 separa escolha opcional | Testar que recusar comunicações não bloqueia o portal; registrar finalidade e revogação; P2/N3, produto/controlador |

Os níveis não demonstram ocorrência de vazamento. A cobertura dos controles existentes foi avaliada documentalmente, sem pentest ou consulta a dados reais. As decisões anteriores de produto não equivalem a assinatura desta matriz por todas as instituições controladoras.

## 8. Condições de liberação e validação residual

Antes de um piloto, o responsável deve reunir as evidências dos pacotes previstos no plano: bases/finalidades e retenção por instituição; contatos/termos/versionamento; dados mínimos; segurança do código, limites e sessão; isolamento; auditoria e resposta a incidentes; transparência de terceiros; teste do fluxo com dados fictícios e plano de retorno.

Para cada R01–R11, preencher após a implementação:

| Campo | Registro pendente |
|---|---|
| Controle implementado e versão | `[CONTROLE / COMMIT]` |
| Evidência de teste e revisor | `[LINK / RESULTADO / RESPONSÁVEL]` |
| P e I após o controle | `[AVALIAÇÃO RESIDUAL JUSTIFICADA]` |
| Decisão do controlador | `[ACEITAR / REDUZIR / EVITAR / OUTRA DECISÃO FUNDAMENTADA]` |
| Responsável, data e revisão | `[PREENCHER]` |

Não preencher a coluna residual apenas com a existência de uma tarefa planejada. Não considerar uma nota alta de disponibilidade, CI aprovado ou backup recuperável como comprovação de proteção integral dos dados.

## 9. Encaminhamento

1. Revisar inventário e aprovar prazos/contratos efetivos (L2/L4).
2. Compatibilizar coleta com o produto decidido: avatar e mínimo de dados (L6/A5).
3. Tratar proteção e logs (L3), auditoria (AU1) e incidentes (L5) conforme dependências do plano.
4. Incorporar resultados no modelo de segurança do portal P1, sem iniciar a implementação pública antes das dependências exigidas.
5. Reavaliar risco residual com evidências, validar pelo controlador e realizar piloto conforme P7.

Este documento entrega a avaliação técnica inicial de RL13. O aceite institucional, a implementação dos controles e a autorização de entrada em operação permanecem pendentes.
