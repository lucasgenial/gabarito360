# 34 — Inventário de dados, retenção e terceiros (L2)

Versão técnica de 31/10/2026. Base de código: `ae5d34e600c12cdac48dab2f73cef2fee3ae1a22` (main após #67). Autor: Codex. Não foram consultados registros pessoais, banco de produção, logs reais, fotos, dumps ou segredos nesta análise.

Este documento descreve o tratamento encontrado no código e os registros operacionais existentes. Não certifica conformidade nem comprova contratos com terceiros. Prazos sem definição permanecem como `[PRAZO A DEFINIR]`; dados da empresa e encarregado continuam em aberto conforme orientação do responsável. A aprovação anterior dos textos jurídicos não é usada como comprovação de contratação de fornecedores ou aceite dos riscos aqui identificados.

## 1. Escopo e micropassos

1. Mapear migrations, modelos, controladores, filas, arquivos, configurações e documentação operacional.
2. Registrar titular, finalidade, hipótese de base, responsáveis, armazenamento, acesso e cópias.
3. Separar validade, retenção e eliminação; identificar o que efetivamente está implementado.
4. Listar terceiros observados e condicionais, com pendências contratuais e de transferência internacional.
5. Elaborar a avaliação preliminar de impacto em [docs/38](38-ripd-preliminar-portal.md).
6. Submeter a documentação à revisão; manter L2 parcial enquanto faltarem decisões de retenção, contratos, lista pública e validação do controlador.

Não há mudança de código, configuração, publicação de páginas ou rotina de exclusão neste pacote. A revisão deste inventário deve acompanhar mudanças de campos, fornecedor, finalidade, contrato, retenção ou acesso.

## 2. Papéis e bases a confirmar

O modelo de papéis parte do documento 33: instituição cliente como controladora dos dados escolares; Gabarito360 como operador; plataforma como controladora dos próprios cadastros de conta, contato e cobrança. Confirmar esses papéis nos contratos, inclusive em redes e secretarias com várias instituições. Contato de referência: `[ENCARREGADO / CONTATO]`; representante de cada instituição: `[RESPONSÁVEL DO CONTROLADOR]`.

As hipóteses abaixo são as do planejamento, sujeitas à validação para cada finalidade e contrato. Não equivalem a uma base jurídica já documentada para todos os clientes.

| Código | Finalidade | Hipótese do documento 33 / pendência |
|---|---|---|
| B-ESC | Avaliação educacional institucional | Instituição pública: política pública/obrigação legal; privada: contrato com a família/interesse pedagógico, a confirmar pelo controlador |
| B-AUT | Alunos do professor autônomo | Base e necessidade ainda a confirmar; planejamento limita a nome, turma e identificação simples |
| B-CONTA | Conta, suporte e contratação | Hipótese contratual e finalidade de segurança a validar; separar usos administrativos de marketing |
| B-SEG | Autenticação, permissões, prevenção de abuso e auditoria | Hipótese de segurança/obrigação aplicável a validar; minimizar campos e definir retenção |
| B-COM | Comunicações promocionais | Planejamento prevê autorização separada e opcional; não inferir consentimento a partir de Fale Conosco ou aceite de termos |

Não classificar automaticamente notas, CPF, nascimento, gênero ou foto comum como dados sensíveis ou biometria. Há tratamento de menores e possibilidade de dados sensíveis em campos livres, que exigem avaliação própria; nenhum mecanismo de reconhecimento facial foi identificado no motor OMR examinado.

## 3. Registro técnico das operações

Em todas as linhas, prazos devem ser lidos junto da seção 5. Identificadores, vínculos e timestamps também podem relacionar uma pessoa a uma operação. Permissões e escopo precisam ser verificados em cada operação; a existência das classes de controle não demonstra cobertura integral de todos os endpoints.

| ID | Titulares e dados | Finalidade / origem / hipótese | Local, acesso e cópias | Evidência |
|---|---|---|---|---|
| O01 | Usuários: nome, e-mail, CPF, nascimento, telefone, perfil, ativo, escola informada, ingresso, formação, especialização, registro profissional, observações, último acesso e marca de troca de senha | Conta, vínculo e administração; gestor/perfil; B-CONTA, B-SEG | `usuarios` no MariaDB; perfis com permissão e escopo; parte replicada na sessão web e em convites; backups | E01, E02 |
| O02 | Credenciais: hash de senha, remember token, tokens de acesso e metadados de uso | Login e revogação; usuário/API; B-SEG | `usuarios`, `personal_access_tokens`; token utilizável fica na sessão web; senha e remember token ocultos na serialização do modelo | E01, E03 |
| O03 | E-mail, hash de token, datas, gestor convidante; novo e-mail e hash de confirmação | Recuperação, convite e alteração de contato; B-CONTA, B-SEG | `password_reset_tokens`, `trocas_email`; links por e-mail e parâmetros de URL; cópias nos jobs de e-mail e backups | E04 |
| O04 | Alunos: nome, matrícula, turma, nascimento, CPF opcional, gênero opcional, nome do responsável, ativo e caminho da foto | Cadastro/importação e avaliação; escola/gestor/CSV; B-ESC; B-AUT ainda pendente | `alunos` e disco público em `storage/app/public/alunos`; API retorna o cadastro conforme endpoint; backups | E05 |
| O05 | Estrutura: escolas, redes, núcleos, secretarias, turmas; nomes e contatos institucionais, endereço, localização da escola e dados do Censo | Organização e vínculo; cadastro e Censo; B-ESC, B-CONTA quando contato identificar pessoa | `redes`, `nucleos`, `escolas`, `secretarias`, `turmas`, `censo_escolas`; dados do Censo não demonstram localização do estudante | E06 |
| O06 | Avaliações: títulos, disciplina, gabaritos, questões, temas, autor, turmas, datas, revisores/fechamento | Preparar, aplicar e revisar prova; professor/gestor; B-ESC | `provas`, `prova_turmas`, `gabaritos`, `gabarito_questoes`; PDFs e impressões; backups | E07 |
| O07 | Foto do cartão, identificação do aluno/prova/língua, respostas, marcações ambíguas, confiança, notas, aprovação e análise por tema | Leitura óptica e resultados; câmera, galeria, upload manual e revisão; B-ESC | `cartoes`, `cartao_respostas`, `notas`; disco público, originais e derivados; processamento Python local; relatórios e backups | E08 |
| O08 | UUID, autor, prova, cartão, tentativa, estado, mensagem e JSON com aluno, nota e resultado | Acompanhar a leitura e repetir sem duplicação; B-ESC, B-SEG | `cartao_envios`; fila Redis usa IDs; UUIDs em `sessionStorage`; JSON duplica dados do resultado; backups | E09 |
| O09 | Concessões e alterações de acesso, usuário alvo/autor, decisões de revisão, alternativas e datas | Controle e responsabilização; B-SEG, B-ESC | `usuario_escopos`, `usuario_permissoes`, `usuario_permissoes_log`, `ambiguidade_logs`; catálogos `permissoes` e `perfil_permissoes`; backups | E02, E07 |
| O10 | Nome, e-mail, telefone, instituição, cargo, assunto, mensagem livre, interesse em plano e licenças | Responder contato comercial/suporte; visitante; B-CONTA, não B-COM automaticamente | `leads`; job e e-mail para equipe e confirmação; Resend, encaminhamento e caixa de destino; backups | E10 |
| O11 | Dados de envio: modelo, assunto, destinatário, reply-to, campos dos modelos, vínculos e links; registro com hash do destinatário | Comunicações transacionais; B-CONTA, B-SEG | Payload de `EmailModelo` na fila; SMTP/Resend; caixas de entrada; `emails_enviados` guarda modelo/hash/data, não corpo; `failed_jobs` pode conter payload e exceção | E11 |
| O12 | Titular da assinatura, plano, rede/secretaria, licenças, valor, estado, método e IDs do gateway | Contratação e conciliação; B-CONTA | `planos`, `assinaturas`, `pagamentos`; Mercado Pago apenas nas operações efetivamente chamadas; logs de webhook; backups | E12 |
| O13 | Escola/núcleo, usuário que agendou, data/tipo/urgência da visita; estado e JSON de sincronização SEGES | Gestão pedagógica e sincronização prevista; B-ESC | `visitas`, `sincronizacoes_seges`; existência de tabela não comprova integração externa ativa. `visitas` é agenda institucional, não analytics de navegação | E13 |
| O14 | Dados técnicos de sessão, token API, nome/e-mail/perfil/permissões; cookies; preferência visual e UUIDs da aba | Sessão, segurança e interface; B-SEG | Redis documentado para sessão/cache/fila; cookies no navegador; `sessionStorage` para B6; preferências locais. Não foram inspecionados cookies nem configuração efetiva nesta entrega | E14 |
| O15 | Campos presentes em requisições/erros, IDs e caminhos de imagem; JSON do webhook; potenciais IP/URL/token em logs de infraestrutura | Operação e diagnóstico; B-SEG | Logs de API/web, nginx, sistema e fila; conteúdo/rotação reais não consultados; cópia de backup pode incluir logs | E15 |
| O16 | CSV de importação, PDF temporário de cartões/resultados, impressões e downloads com identificação e notas | Importar e entregar documentos; B-ESC | Arquivo fornecido ao comando, temporários no servidor e cópias baixadas/impresas fora do controle direto da aplicação | E16 |
| O17 | Cópia integral de banco/aplicação, arquivos, logs e segredos operacionais | Recuperação; B-SEG e preservação conforme contrato | CTs 106/111; SSD do Proxmox e cópia local do responsável; ambiente isolado no teste de restauração já documentado | E17 |
| O18 | Portal futuro: matrícula, nascimento, código, responsável, aceite, consultas, IP/metadados e documentos | Consulta familiar; B-ESC; ciência não é, sozinha, a base de todo tratamento | Apenas planejado: tabelas/rotas específicas ainda não identificadas; não declarar que controles do portal já existem | E18 e [RIPD preliminar](38-ripd-preliminar-portal.md) |

## 4. Fluxos e diferenças frente aos textos preparados

- OMR: a foto é salva localmente e processada por Python/OpenCV no servidor. Não foi encontrada chamada a API externa de IA nesse fluxo. Isso não elimina o acesso da camada de publicação ao tráfego nem o conteúdo que o próprio operador enviar a ferramentas externas.
- Foto de aluno: `AlunoController` ainda aceita `foto` no cadastro/edição. O modelo tem `foto_path`; CPF e nascimento também continuam no cadastro. A remoção da foto e o cadastro mínimo do autônomo são decisões futuras (L6/A5), não estado atual. Não publicar a frase de que a plataforma não guarda foto de aluno antes de compatibilizar código e texto.
- Campos livres de usuário e contato podem receber dados além da finalidade, inclusive informações sensíveis não solicitadas. Revisar necessidade e orientar o preenchimento.
- CPF e contatos não possuem cast de criptografia nos modelos examinados; as migrations usam campos de texto. Isso descreve a proteção na aplicação, não demonstra a presença ou ausência de criptografia de discos ou do banco na infraestrutura. L3 ainda precisa tratar a proteção desses campos e a busca correspondente.
- `public` é um disco com visibilidade pública no código. Existem rotas de imagens de cartões com controle e assinatura, mas isso não comprova que o acesso direto via `/storage` esteja bloqueado. Auditar nginx, links e exposição efetiva sem divulgar arquivos pessoais (L3).
- Links de convite/recuperação levam e-mail e token na URL; confirmação de e-mail leva token. Rever logs, cabeçalhos de referência e limpeza do endereço na etapa L3.
- Hash do destinatário no registro de envios não anonimiza o conjunto: o e-mail e o conteúdo ainda transitam na fila e no provedor. O hash pode ser relacionado a endereços conhecidos.
- A exclusão de cartão em B6 invalida seu recibo; isso não substitui um procedimento de eliminação de titular em todas as tabelas, filas, arquivos e backups. Desativar aluno ou usuário também não elimina os dados.

## 5. Retenção: estado observado e decisão pendente

Uma validade limita o uso do token; não garante remoção física do registro. TTL de sessão/cache e retenção de backup não definem a retenção das tabelas de negócio. Não foi identificada rotina geral de expurgo no agendamento examinado; ele agenda a verificação de inadimplência.

| Grupo | Prazo/ação observado no código ou operação | Decisão necessária e responsável |
|---|---|---|
| Conta e dados profissionais | Sem prazo geral de exclusão identificado; desativação não é eliminação | `[PRAZO A DEFINIR]`, minimização de campos e tratamento pós-contrato — plataforma/encarregado |
| Alunos, notas, respostas e revisão | Sem expurgo automático identificado; há exclusões específicas e cascatas por entidade | `[PRAZO A DEFINIR PELO CONTROLADOR]`, conforme finalidade/contrato e obrigações aplicáveis; não presumir prazo escolar único — instituição/encarregado |
| Fotos de aluno | Upload ainda permitido; substituição de caminho não demonstra limpeza da foto antiga | Migração para avatar e eliminação autorizada após backup — L6 |
| Originais/derivados de cartões | Excluídos em fluxos de substituição/exclusão do cartão; sem prazo geral identificado | Prazo ligado à revisão, contestação e contrato; confirmar arquivos órfãos — controlador/engenharia |
| Recibos de envio B6 | Sem TTL de banco; conclusão conserva JSON; exclusão/substituição limpa o resultado nos fluxos corrigidos | `[PRAZO A DEFINIR]` para recibo e resultado, preservando idempotência pelo período escolhido — produto/engenharia/controlador |
| Recuperação de senha | Validade de 60 minutos; uso válido remove o registro; novo pedido substitui por e-mail | Expurgar registros vencidos não utilizados — segurança/L4 |
| Convite | Validade de sete dias; aceite/recusa consome registro | Definir expurgo dos vencidos e tratamento da conta não ativada — plataforma |
| Troca de e-mail | Validade de 24 horas; tentativa com token encontrado remove o registro antes da confirmação | Definir expurgo de solicitações vencidas não consultadas — plataforma |
| Tokens Sanctum | `expiration=null` no config; criação não fixa prazo; logout revoga token atual e redefinição revoga os tokens da conta | Política de sessão/revogação e limpeza de tokens inativos — segurança; não inferir validade de 120 minutos para tokens API |
| Sessão web | Config padrão: 120 minutos, dependente de ambiente; valor efetivo não conferido aqui | Confirmar TTL real, cookies e sessão técnica em Redis — operação |
| Fila/failed_jobs | Jobs processados saem da fila; reentrega e retentativa não são retenção. Sem limpeza geral de falhas identificada no agendamento | `[PRAZO A DEFINIR]` e procedimento que não apague jobs ainda em atendimento — operação/L4 |
| Registro de e-mails e contatos | Sem prazo/expurgo identificado; caixas de entrada são cópias separadas | `[PRAZO A DEFINIR]` para leads, hash de destinatário e correio; distinguir suporte de marketing — plataforma/encarregado |
| Permissões e revisão | Sem expurgo periódico identificado; algumas relações têm cascata ao excluir entidades | `[PRAZO A DEFINIR]`, necessidade de responsabilização e preservação em incidentes — controlador/plataforma |
| Assinaturas/pagamentos | Sem prazo de eliminação identificado; há restrições de exclusão por relações | `[PRAZO A DEFINIR COM RESPONSÁVEL FISCAL/JURÍDICO]`; sem assumir prazo fiscal universal — plataforma |
| Logs | Padrão de stack usa `single`; canal `daily` tem padrão 14 dias, mas sua ativação real não foi conferida | Confirmar canais/rotação/redação, definir prazo e acesso; 14 dias não é uma política global implementada — operação/L3 |
| CSV e PDF | Importador não remove o CSV de origem. Downloads de PDF usam `deleteFileAfterSend`; geração com erro pode deixar temporários | Definir limpeza de fontes/temporários e responsabilidade por downloads/impresso — instituição/operação |
| Navegador | `sessionStorage` B6 conserva UUIDs recuperáveis e remove estados encerrados; dados de preferência podem persistir localmente | Informar uso técnico, revisar sessões compartilhadas e cookies reais — plataforma/L1 |
| Backups | Oito datas no SSD e no computador, conforme política já decidida e teste B1; sem terceira cópia externa confirmada | Preservar decisão de oito dias; testar limpeza/rotação e impedir reintrodução de dados eliminados numa restauração — operação |

O prazo de oito dias não autoriza apagar arquivos da aplicação ou registros escolares com a mesma idade. Nenhuma exclusão, alteração de TTL ou de retenção foi executada por este inventário.

## 6. Terceiros e transferências internacionais

Lista interna de fluxos a confirmar contratualmente. O papel de cada terceiro depende do serviço e do contrato; não tratar todos automaticamente como suboperadores do mesmo controlador. A lista ainda não está publicada no site (RL12 parcial).

| Serviço | Evidência e dados envolvidos | Situação e verificação pendente |
|---|---|---|
| Hospedagem e backup próprios | Documentação dos CTs, SSD e computador; dados de O01–O17 | Localização física, responsáveis com acesso, proteção dos discos e contratos de operação a confirmar. Proxmox/MariaDB/Redis/OpenCV são componentes, não prova de envio a um SaaS |
| Cloudflare — publicação/DNS/Tunnel | Operação documentada; tráfego HTTP e metadados técnicos | Confirmar plano, serviços ativos, locais e mecanismos contratuais; usar [DPA](https://www.cloudflare.com/cloudflare-customer-dpa/) e [lista oficial](https://www.cloudflare.com/gdpr/subprocessors/) como referências, não como evidência de aceite desta conta |
| Cloudflare — Turnstile | Widget no contato e validação do token em `Turnstile.php`; a API envia segredo técnico e token, sem incluir os campos do contato nesse POST | Config real não inspecionada nesta entrega; confirmar tratamento do widget e enquadramento contratual antes de afirmar cobertura integral |
| Cloudflare Email Routing | Recebimento/encaminhamento registrado em `docs/operacao/email.md`; conteúdo do e-mail e endereços | Confirmar destinatários autorizados, retenção e termos desse serviço |
| Resend | Envio SMTP documentado; destinatário, assunto, campos transacionais, mensagem e links | Região de envio `sa-east-1` documentada não demonstra que todos os metadados, suporte e subprocessadores fiquem no Brasil. Confirmar DPA e abrangência da conta: [DPA](https://resend.com/legal/dpa) |
| AWS / Amazon SES na cadeia de envio | Documentação operacional cita infraestrutura de envio; Resend lista AWS como hospedagem/envio | Não declarar contrato direto com AWS/SES sem evidência. Confirmar serviço/região e cadeia efetiva com Resend; [lista Resend](https://resend.com/legal/subprocessors) consultada em 31/10/2026, atualizada em 27/08/2026 |
| Caixa da equipe em Google/Gmail | Destino de encaminhamento documentado, sem reproduzir o endereço pessoal aqui | Confirmar titularidade da conta, acesso, modalidade contratada, retenção, compartilhamento e condições para dados escolares. Não presumir contrato corporativo ou DPA |
| Google Fonts | Layouts fazem requisições a `fonts.googleapis.com` e `fonts.gstatic.com`; metadados de conexão do navegador | Fluxo observado no código; avaliar hospedagem local das fontes e informações de transparência. Referência: [Google Fonts — privacidade](https://fonts.googleblog.com/2022/11/your-privacy-and-google-fonts.html) |
| Mercado Pago | Código de criação envia nome/e-mail/referência; consulta/cancelamento e webhook permanecem | Checkout atual recusa novas cobranças com 409. Não declarar fluxo ativo de novas compras; confirmar conta, dados e termos de integrações existentes antes de reativar |
| GitHub e ferramentas de desenvolvimento | Versionamento/CI e artefatos, não destino operacional necessário de cadastros | Usar dados fictícios nos testes/prints; não enviar CSV, dumps, fotos ou segredos reais. Não foi feita varredura histórica de todos os artefatos ou auditoria de contas nesta entrega |

Configurações padrão de S3, SES, Postmark, Slack e outros conectores não são evidência de uso. Não foram promovidas a fornecedores ativos apenas por existirem arquivos de configuração.

### Encaminhamento da análise internacional

A [ANPD](https://www.gov.br/anpd/pt-br/assuntos/assuntos-internacionais/transferencia-internacional-de-dados) mantém orientação sobre os mecanismos de transferência; a [Resolução 19/2024](https://www.gov.br/anpd/pt-br/acesso-a-informacao/institucional/atos-normativos/regulamentacoes_anpd/resolucao-cd-anpd-no-19-de-23-de-agosto-de-2024) trata do regulamento e cláusulas-padrão. Região comercial ou termos estrangeiros, isoladamente, não comprovam o enquadramento do fluxo brasileiro.

Para cada fluxo externo, o responsável/encarregado deve preencher: exportador, importador, países, dados, finalidade, duração, transferências posteriores, contrato aceito, mecanismo aplicável e evidência. Campos atuais: `[A CONFIRMAR]`. Não afirmar adequação de país, equivalência de cláusula ou adoção de mecanismo sem essa documentação. A lista ampla de subprocessadores do fornecedor não comprova que cada integrante recebeu dados do Gabarito360.

## 7. Pendências e responsáveis

| Prioridade | Pendência | Responsável / evidência de fechamento |
|---|---|---|
| Alta | Compatibilizar coleta com texto: fotos de aluno e mínimo do autônomo | L6/A5; teste de API/telas e eliminação de arquivos só após aprovação |
| Alta | Definir prazos por finalidade, autoridade decisória e exceções de preservação | Controlador/encarregado; matriz aprovada, sem prazo escolar ou fiscal presumido |
| Alta | Proteger CPF/contatos e revisar disco público, URLs e logs | L3; testes de acesso e mascaramento, sem expor amostras reais |
| Alta | Contratos, locais, cadeia e mecanismo internacional dos fluxos externos | Responsável/encarregado; documentos efetivamente aceitos pela conta |
| Alta | Revisar e assumir a avaliação de impacto do portal | Controlador; preencher [docs/38](38-ripd-preliminar-portal.md) e fechar riscos antes do piloto |
| Média | Procedimento de pedidos, expurgo e restauração sem reintroduzir excluídos | L4/operação; testes em dados fictícios e roteiro de recuperação |
| Média | Inventariar acessos reais a discos, caixas, repositório e backups | Operação; confirmação sem copiar segredos ou registros pessoais |
| Média | Publicar lista e contatos corretos no aviso | L1; empresa/encarregado e revisão do texto conforme comportamento implementado |

## 8. Evidências de código e operação

Os caminhos são relativos à raiz do repositório. Conferência feita em fontes, não em valores de ambiente ou bases reais.

| Código | Referências |
|---|---|
| E01 | `apps/api/app/Models/Usuario.php`; `apps/api/database/migrations/2026_06_18_191148_add_dados_profissionais_to_usuarios_table.php` |
| E02 | `apps/api/app/Support/Escopo.php`; `apps/api/app/Support/Permissao.php`; `apps/api/app/Support/CatalogoPermissoes.php`; `apps/api/database/migrations/2026_10_30_000003_create_permissoes_tables.php` |
| E03 | `apps/api/app/Http/Controllers/Api/AuthController.php`; `apps/api/config/sanctum.php`; `apps/web/app/Http/Controllers/Auth/LoginController.php` |
| E04 | `apps/api/app/Services/ContaUsuario.php`; `apps/api/database/migrations/2026_10_31_000003_convites_e_troca_de_email.php` |
| E05 | `apps/api/app/Models/Aluno.php`; `apps/api/app/Http/Controllers/Api/AlunoController.php`; `apps/api/database/migrations/2026_06_18_191133_add_cpf_genero_foto_to_alunos_table.php` |
| E06 | Migrations `2026_01_01_000001` a `000004`, `2026_06_18_200000_create_secretarias_table.php` e `2026_10_31_000001_escola_censo.php`, em `apps/api/database/migrations/` |
| E07 | Migrations de provas/gabaritos, `ambiguidade_logs` e fechamento de correção, em `apps/api/database/migrations/`; `apps/api/app/Http/Controllers/Api/CartaoController.php` |
| E08 | `apps/api/app/Services/Omr/NativoOmrDriver.php`; `apps/api/app/Services/Omr/ImagensCartao.php`; `apps/api/app/Services/Omr/CalculadoraNota.php`; `apps/api/config/filesystems.php` |
| E09 | `apps/api/app/Models/CartaoEnvio.php`; `apps/api/app/Services/Omr/FilaCartoes.php`; `apps/api/app/Services/Omr/ResultadoEnvio.php`; `apps/api/app/Jobs/ProcessarCartaoJob.php`; `apps/web/public/js/cartoes-fila.js` |
| E10 | `apps/api/app/Http/Controllers/Api/LeadController.php`; `apps/api/app/Services/Turnstile.php`; `apps/web/resources/views/leads/fale-conosco.blade.php` |
| E11 | `apps/api/app/Mail/EmailModelo.php`; `apps/api/app/Providers/AppServiceProvider.php`; `apps/api/config/queue.php`; `apps/api/database/migrations/2026_10_31_000002_emails_e_assunto_de_lead.php`; `docs/operacao/email.md` |
| E12 | `apps/api/app/Http/Controllers/Api/AssinaturaController.php`; `apps/api/app/Http/Controllers/Api/WebhookController.php`; `apps/api/app/Services/MercadoPagoService.php`; migrations de assinaturas/pagamentos |
| E13 | `apps/api/database/migrations/2026_01_01_000016_create_visitas_table.php`; `apps/api/database/migrations/2026_01_01_000017_create_sincronizacoes_seges_table.php` |
| E14 | `apps/web/config/session.php`; `apps/web/app/Http/Controllers/Auth/LoginController.php`; `apps/web/resources/views/layouts/`; `apps/web/public/js/cartoes-fila.js`; `docs/operacao/deploy.md` |
| E15 | `apps/api/config/logging.php`; `apps/web/config/logging.php`; `apps/api/app/Http/Controllers/Api/WebhookController.php`; `apps/api/app/Services/Omr/NativoOmrDriver.php` |
| E16 | `apps/api/app/Console/Commands/ImportarAlunos.php`; `apps/api/app/Services/Omr/GeradorCartoes.php`; `apps/api/app/Http/Controllers/Api/ResultadoController.php`; métodos PDF de `CartaoController` |
| E17 | `docs/operacao/backup.md`; `scripts/operacao/copiar-backups.ps1`; `scripts/operacao/proxmox-backup-copia`; `docs/operacao/deploy-b6-2026-10-31.md` |
| E18 | `docs/28-plano-mestre-proximos-modulos.md`, fase 8; `docs/33-lgpd-privacidade-e-consentimento.md` |

## 9. Situação de L2

| Requisito | Entrega atual | O que falta |
|---|---|---|
| RL2 — inventário | Operações, cópias, locais, finalidades e hipóteses mapeados | Validar responsáveis, bases por cliente/finalidade, volumes e políticas de retenção; manter o registro atualizado |
| RL12 — terceiros/internacional | Fluxos observados e condicionais identificados com fontes e campos pendentes | Contratos da conta, mecanismo/localização e publicação da lista |
| RL13 — impacto | Avaliação preliminar técnica em docs/38 | Revisão/assunção pelo controlador, decisões e evidências dos controles antes do portal |

L2 permanece parcial. Este PR não libera o portal nem encerra L1/L3/L4/L6. Registro e impacto serão revistos quando os campos pendentes forem preenchidos ou o sistema mudar.

## 10. Verificação desta entrega

- As 32 tabelas declaradas por `Schema::create` nas migrations da API estão mencionadas no inventário. Isso não é uma contagem de tabelas ou registros do banco de produção.
- Conferidas 42 referências exatas a arquivos de código e os links relativos entre documentos; nenhuma referência local inválida encontrada.
- Fontes externas consultadas são documentos oficiais da ANPD, texto atualizado da LGPD e páginas oficiais dos fornecedores. Elas orientam a análise e não comprovam a configuração ou o contrato da conta.
- Sem código de aplicação, migração, consulta pessoal, leitura de segredos ou mudança de produção. Verificação documental por `git diff --check`; CI do PR acompanha os checks do repositório.
