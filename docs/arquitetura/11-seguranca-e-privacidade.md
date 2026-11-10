# Segurança e privacidade (LGPD)

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve como o Gabarito360 trata dados pessoais: papéis, bases legais, inventário, retenção, terceiros, proteção técnica, pedidos dos titulares, avatares, auditoria e o relatório de impacto preliminar. O texto é uma minuta técnica. Um advogado ou o encarregado ainda precisa revisá-lo antes de qualquer publicação. A segurança de sessão e de acesso está em [05-acesso-e-permissoes.md](04-identidade-acesso-e-permissoes.md) e em [04-contratos-api-e-web.md](05-contratos-api-web.md).

Funcionalidades relacionadas: [FN-004 auditoria](../funcionalidades/004-auditoria/planejamento.md), [FN-061 termos](../funcionalidades/061-lgpd-termos-privacidade/planejamento.md), [FN-062 inventário e retenção](../funcionalidades/062-lgpd-inventario-retencao/planejamento.md), [FN-063 proteção](../funcionalidades/063-lgpd-protecao-dados/planejamento.md), [FN-064 pedidos dos titulares](../funcionalidades/064-pedidos-titulares/planejamento.md), [FN-065 incidentes](../funcionalidades/065-plano-incidentes/planejamento.md) e [FN-066 avatares](../funcionalidades/066-avatares-alunos/planejamento.md).

## Resumo do estado

| Tema | Estado em 10/11/2026 |
|---|---|
| Cifra de CPF e contatos, logs sem dados pessoais (L3) | Publicada na produção em 31/10/2026 |
| Avatar no lugar de foto de aluno (L6) | Publicado na produção em 31/10/2026; divergência registrada no fim deste documento |
| Auditoria de requisições (AU1) | Ativa, com credencial própria de gravação e retenção de 180 dias, sem expurgo |
| Pedidos dos titulares (L4) | API e telas prontas, com flags desligadas; ativas só na homologação |
| Inventário de dados e retenção (L2) | Parcial: prazos aprovados, mas nenhuma rotina de expurgo implementada |
| Avisos de privacidade e termos (L1) | Parcial: páginas prontas com campos a preencher, sem rota pública |
| Plano de incidentes (L5) | Pendente: existe só o [modelo de registro](../operacao/incidentes.md) |
| Relatório de impacto (RIPD) do portal dos responsáveis | Preliminar: aguarda controles e assunção pelo controlador |

## Normas consideradas

- Lei Geral de Proteção de Dados (LGPD), Lei 13.709/2018.
- Estatuto da Criança e do Adolescente (ECA), Lei 8.069/1990.
- ECA Digital, Lei 15.211/2025, em vigor desde 17/03/2026.
- Enunciado CD/ANPD 1/2023, sobre bases legais para dados de crianças e adolescentes.
- Resoluções CD/ANPD 15/2024 (incidentes), 18/2024 (encarregado) e 19/2024 (transferência internacional).

O guia da ANPD sobre fornecedores no ECA Digital estava em consulta pública em 2026. Confirme a versão final antes de publicar.

## Papéis

| Situação | Controlador | Gabarito360 |
|---|---|---|
| Escola, núcleo, rede ou secretaria cliente | A instituição | Operador |
| Professor autônomo | O professor | Operador |
| Dados de conta, cobrança, segurança e Fale Conosco | Gabarito360 | Controlador |
| Portal dos responsáveis (ainda não implementado) | A instituição ou o professor | Operador |

Confirme esses papéis nos contratos, inclusive em redes e secretarias com várias instituições.

## Bases legais por finalidade

O consentimento não é a única base possível para dados de crianças e adolescentes. O Enunciado 1/2023 permite outra hipótese legal, se prevalecer o melhor interesse. Cada finalidade precisa de uma base própria.

| Código | Finalidade | Base sugerida |
|---|---|---|
| B-ESC (pública) | Correção, notas e relatórios em instituição pública | Execução de políticas públicas e obrigação legal, a confirmar pela instituição |
| B-ESC (privada) | Correção, notas e relatórios em instituição privada | Execução de contrato com a família e legítimo interesse pedagógico, a confirmar pela instituição |
| B-AUT | Alunos do professor autônomo | A confirmar com advogado. O cadastro fica limitado a nome, turma e identificação simples, sem portal dos pais |
| B-CONTA | Conta, suporte e contratação | Execução de contrato; separe usos administrativos de marketing |
| B-SEG | Autenticação, permissões, prevenção de abuso e auditoria | Obrigação legal e legítimo interesse; minimize campos |
| Portal | Portal dos responsáveis | Execução da atividade educacional. O aceite registra ciência e não é a única base |
| Cobrança | Cobrança | Execução de contrato |
| B-COM | Comunicações comerciais a responsáveis | Só com consentimento separado e opcional. Não deduza consentimento do Fale Conosco nem do aceite de termos |

Não classifique notas, CPF, nascimento, gênero ou foto comum como dado sensível ou biometria de forma automática. Nenhum reconhecimento facial existe no motor OMR (Reconhecimento Óptico de Marcas). Campos livres podem receber dados sensíveis não solicitados e exigem orientação aos usuários.

## Requisitos de privacidade (RL1 a RL19)

Os pacotes da fase LGPD cobram estes requisitos do sistema.

| ID | Requisito |
|---|---|
| RL1 | Coletar só o necessário. Todo campo novo de aluno exige justificativa |
| RL2 | Manter o inventário de dados e o registro das operações (art. 37) |
| RL3 | Oferecer tela de pedidos de titulares, com protocolo e prazo |
| RL4 | Exportar os dados de um titular em formato estruturado |
| RL5 | Eliminar ou anonimizar por titular e por fim de contrato |
| RL6 | Aplicar a tabela de retenção por rotina automática |
| RL7 | Registrar versão e aceite dos termos |
| RL8 | Cifrar CPF, contatos e código do portal |
| RL9 | Mascarar dados pessoais em logs, e-mails e auditoria |
| RL10 | Dar acesso mínimo por perfil, vínculo e escopo |
| RL11 | Ter plano de resposta a incidentes, com registro e modelos de aviso |
| RL12 | Publicar a lista de suboperadores e analisar a transferência internacional |
| RL13 | Elaborar o relatório de impacto do portal e dos dados de menores |
| RL14 | Não rastrear nem fazer publicidade direcionada em telas de alunos e responsáveis |
| RL15 | Publicar o canal do encarregado no site |
| RL16 | Usar cookies necessários por padrão e dar escolha sobre os opcionais |
| RL17 | Revisar por humano as leituras automáticas (atendido pela tela de revisão) |
| RL18 | Limitar o professor autônomo a dados mínimos de alunos; portal dos pais só para escolas e perfis superiores |
| RL19 | Não guardar foto nem vídeo de aluno; usar avatar sem imagem enviada |

## Inventário de dados (L2)

O inventário abaixo vem da leitura do código e das migrations em 31/10/2026. Ele não certifica conformidade, não prova contratos com terceiros e não usou dados reais. Atualize-o a cada mudança de campo, fornecedor, finalidade, contrato, retenção ou acesso.

| ID | Titulares e dados | Local e cópias |
|---|---|---|
| O01 | Usuários: nome, e-mail, CPF, nascimento, telefone, perfil, escola, dados profissionais, último acesso | Tabela `usuarios`; parte replicada na sessão web e em convites; backups |
| O02 | Credenciais: hash de senha, token de lembrança, tokens de acesso | `usuarios`, `personal_access_tokens`; o token utilizável fica na sessão web |
| O03 | E-mail, hash de token, datas e gestor convidante de recuperação, convite e troca de e-mail | `password_reset_tokens`, `trocas_email`; links por e-mail; jobs de e-mail; backups |
| O04 | Alunos: nome, matrícula, turma, nascimento, CPF opcional, gênero opcional, nome do responsável, avatar, caminho de foto | `alunos`; disco local `alunos`; backups |
| O05 | Estrutura: escolas, redes, núcleos, secretarias, turmas, contatos institucionais, dados do Censo | `redes`, `nucleos`, `escolas`, `secretarias`, `turmas`, `censo_escolas` |
| O06 | Avaliações: títulos, disciplinas, gabaritos, questões, autoria, turmas, datas | `provas`, `prova_turmas`, `gabaritos`, `gabarito_questoes`; PDFs; backups |
| O07 | Foto do cartão, respostas, marcações ambíguas, confiança, notas e análise por tema | `cartoes`, `cartao_respostas`, `notas`; disco; processamento local em Python; backups |
| O08 | Recibos de envio de cartão: UUID, autor, prova, estado e JSON do resultado | `cartao_envios`; UUIDs em `sessionStorage`; backups |
| O09 | Concessões e alterações de acesso, decisões de revisão | `usuario_escopos`, `usuario_permissoes`, `usuario_permissoes_log`, `ambiguidade_logs` |
| O10 | Fale Conosco: nome, e-mail, telefone, instituição, cargo, assunto, mensagem | `leads`; e-mail à equipe e confirmação; Resend; encaminhamento; caixa de destino |
| O11 | Dados de envio de e-mail: modelo, assunto, destinatário, campos | Payload na fila; SMTP; `emails_enviados` guarda modelo, hash do destinatário e data, sem o corpo; `failed_jobs` pode guardar payload cifrado |
| O12 | Assinaturas e pagamentos: titular, plano, licenças, valor, estado, IDs do gateway | `planos`, `assinaturas`, `pagamentos`; Mercado Pago só nas operações realmente chamadas |
| O13 | Visitas pedagógicas e sincronização SEGES | `visitas`, `sincronizacoes_seges`; a tabela não prova integração externa ativa |
| O14 | Sessão, token de API, cookies, preferência visual, UUIDs da aba | Redis; cookies; `sessionStorage` |
| O15 | Logs de aplicação, nginx, sistema e fila | Arquivos de log; cópia de backup pode incluí-los |
| O16 | CSV de importação, PDFs temporários, impressões e downloads | Arquivo informado ao comando; temporários no servidor |
| O17 | Cópia integral de banco, aplicação, arquivos, logs e segredos operacionais | CT 106 e CT 111; SSD do Proxmox; computador do responsável |
| O18 | Portal dos responsáveis: matrícula, nascimento, código, aceite, consultas | Apenas planejado: nenhuma tabela ou rota existe |

Fatos relevantes do inventário:

- O OMR roda localmente, com Python e OpenCV. Nenhuma API externa de IA recebe cartões.
- O disco `public` tem visibilidade pública no código. As imagens de cartão usam rotas controladas e assinadas.
- A exclusão de um cartão invalida seu recibo (B6). Isso não substitui um procedimento de eliminação do titular em todas as tabelas, filas, arquivos e backups.
- Desativar um aluno ou usuário não elimina seus dados.
- Os 32 `Schema::create` iniciais estão cobertos neste inventário. Desde então o banco passou a ter 130 tabelas. O inventário das tabelas posteriores (vínculos, espaços, catálogo, cópias, pedidos dos titulares) ainda precisa de revisão (pendência de [FN-062](../funcionalidades/062-lgpd-inventario-retencao/planejamento.md)).

## Retenção

Uma validade limita o uso de um token. Ela não remove o registro. O TTL de sessão e de cache também não define a retenção das tabelas de negócio. O agendamento atual contém apenas a verificação de inadimplência e as rotinas de cópias entre espaços. **Nenhuma rotina de expurgo existe no código.**

### Política aprovada (31/10/2026), ainda não implementada

O responsável aprovou estes prazos como proposta operacional. Eles não são prazos universais exigidos por lei.

| Grupo | Prazo aprovado | Cuidados na implementação |
|---|---|---|
| Logs da aplicação | 30 dias | Defina rotação e redija os dados antes de gravar. Não estenda aos logs de infraestrutura |
| Jobs falhos | 14 dias | Não remova casos em atendimento ou preservação justificada |
| Resultado detalhado do envio de cartão (B6) | 30 dias | Elimine nome, nota e demais dados do resultado |
| Recibo mínimo do envio de cartão (B6) | 90 dias | Preserve a idempotência; defina o marco exato e os campos que restam |
| Tokens com vencimento | Remover em até 24 horas após vencer | Não crie vencimento por idade nos tokens de API sem prazo |
| Contatos sem contrato | 6 meses desde a última interação | Defina "interação válida"; trate clientes, suporte e caixas à parte |
| Dados escolares e cartões | Conforme o contrato de cada instituição | Não defina prazo escolar único; inclua cópias, contestação e exportação |
| Backups | 8 datas (decisão anterior) | Não confunda com o prazo das tabelas de negócio |

Exceções de suporte, incidente ou preservação exigem motivo, responsável e data de revisão.

### Prazos de tokens e sessões (estado atual)

| Item | Valor |
|---|---|
| Recuperação de senha | 60 minutos; o uso válido remove o registro |
| Convite de usuário | 7 dias; aceite ou recusa consome o registro |
| Troca de e-mail | 24 horas |
| Token de API (Sanctum) | Sem expiração global (`expiration=null`). O responsável decidiu mantê-lo, pelo uso exclusivo no produto |
| Sessão web | 120 minutos de inatividade, em Redis. A atividade renova a contagem |
| Logout e redefinição de senha | Revogam o token atual e os tokens da conta |

### Pendências de retenção

Os prazos de contas sem vínculo, auditoria (além dos 180 dias sem expurgo), documentos fiscais, arquivos de importação e caixas de e-mail não foram aprovados. Assinaturas e pagamentos ficam sem prazo até o responsável fiscal definir. O CSV de importação não é removido após a leitura. O backup de 8 datas não autoriza apagar arquivos da aplicação com a mesma idade. Uma restauração não pode reintroduzir dados já eliminados: reaplique as decisões vigentes antes de reabrir os serviços.

## Terceiros e transferência internacional

A lista abaixo é interna e ainda não foi publicada no site (RL12 parcial). O papel contratual de cada terceiro depende do serviço. Não os trate todos como suboperadores do mesmo controlador.

| Serviço | Dados envolvidos | Situação |
|---|---|---|
| Hospedagem e backup próprios | Todos os dados de O01 a O17 | O responsável declarou servidores e cópias no Brasil, com administração exclusiva dele. Criptografia do disco de backup não confirmada |
| Cloudflare (DNS e túnel) | Tráfego HTTP e metadados técnicos | Conta própria declarada. Contrato e mecanismo internacional por confirmar |
| Cloudflare Turnstile | Token de verificação no Fale Conosco | A API envia segredo técnico e token, sem os campos do contato |
| Cloudflare Web Analytics | Visitas e desempenho (`static.cloudflareinsights.com`, `/cdn-cgi/rum`) | O responsável decidiu manter e documentar. O fornecedor informa medição sem cookies nem `localStorage` |
| Cloudflare Email Routing | Conteúdo e endereços do e-mail recebido | Confirme destinatários, retenção e termos |
| Resend | Destinatário, assunto, campos, mensagem e links | Conta própria. A região `sa-east-1` não prova tratamento só no Brasil. Confirme o DPA (acordo de tratamento de dados) |
| Amazon SES (na cadeia do Resend) | Envio de e-mail | Sem contrato direto comprovado. Confirme a cadeia com o Resend |
| Caixa da equipe (Gmail pessoal) | Mensagens do Fale Conosco | Modalidade confirmada. Retenção e condições contratuais pendentes; não presuma DPA corporativo |
| Google Fonts | Metadados de conexão do navegador | Requisições a `fonts.googleapis.com` e `fonts.gstatic.com`. Avalie hospedagem local |
| Mercado Pago | Nome, e-mail e referência na criação de cobrança | O checkout recusa novas cobranças (HTTP 409). O responsável declarou ausência de pagamento real |
| GitHub e ferramentas de desenvolvimento | Código e artefatos de CI | Use dados fictícios nos testes. Nenhum CSV, dump, foto ou segredo reais |

As configurações padrão de S3, SES, Postmark e Slack não são evidência de uso.

Para cada fluxo externo, o encarregado deve preencher: exportador, importador, países, dados, finalidade, duração, transferências posteriores, contrato aceito, mecanismo aplicável e evidência. Hoje os campos estão como "a confirmar". Não afirme adequação de país nem equivalência de cláusula sem essa documentação. A região comercial de um fornecedor não comprova o enquadramento do fluxo.

## Proteção de dados em vigor (L3)

A entrega L3 foi publicada na produção em 31/10/2026 (PR 69, roteiro B5).

### Cifra de campos e índices de busca

| Campos | Proteção |
|---|---|
| `usuarios.email`, `usuarios.cpf`, `usuarios.telefone`, `alunos.cpf`, `leads.email`, `leads.telefone`, `escolas.email`, `escolas.telefone`, `trocas_email.novo_email` | Cifra autenticada do Laravel, com prefixo `g360:v1:` (cast `DadoProtegido`). Valor nulo permanece nulo. Chave errada ou conteúdo adulterado gera erro, sem retorno a texto simples |
| E-mail e CPF de usuários; CPF de alunos | Índice HMAC-SHA256 para busca exata e unicidade. A API não serializa os índices |

Regras:

1. A chave dedicada `DADOS_HASH_KEY` (`apps/api/config/dados.php`) alimenta os índices. Ela deve ter ao menos 32 bytes aleatórios e ficar em cofre. Fora do ambiente `testing`, a ausência da chave bloqueia a migração e a busca. Não há retorno à `APP_KEY`.
2. Mantenha `DADOS_HASH_KEY` estável. Trocá-la exige reindexar tudo, incluindo recuperação e convites. Não a troque em uma rotação comum da `APP_KEY`.
3. Na rotação da cifra, preserve `APP_PREVIOUS_KEYS` até recifrar e verificar todos os registros e jobs.
4. A busca parcial de membros limita primeiro por escopo e perfil e filtra depois, já decifrado. Não existe índice de fragmentos de e-mail.
5. Os registros de recuperação usam como chave o índice do e-mail. Os links novos levam uma `conta` opaca e o token. Os links antigos com e-mail valem até o vencimento original. O identificador opaco é pseudônimo, não anonimização.
6. Os e-mails transacionais ficam cifrados na fila, inclusive em `failed_jobs`. Nenhum modelo novo envia CPF.
7. O provedor de falhas `database-uuids` guarda o payload para retentativa e grava só um diagnóstico sanitizado da exceção.
8. `migrate:rollback` não decifra esses campos. Para voltar ao código antigo, restaure um backup consistente de banco e chaves. Nunca reverta só o Git.

Não são cobertos pela cifra de campos: nomes, matrículas, notas, datas de nascimento, mensagens livres e fotos. Esses dados seguem o escopo, a minimização e os pacotes próprios. A informação de contato dos responsáveis e o código do portal exigirão proteção própria quando existirem. A base pública do Censo mantém o telefone institucional publicado pelo INEP.

### Logs

API e web omitem conteúdo livre dos logs por padrão. Eles preservam eventos técnicos conhecidos, IDs numéricos permitidos e o diagnóstico de exceção (classe, arquivo, linha e pilha sem argumentos). SQL, bindings, corpo HTTP ou SMTP, mensagens livres e objetos não são registrados. Para registrar um evento novo, acrescente-o à lista positiva, sem dados pessoais. A configuração de log usa a stack `single` (`LOG_STACK`).

### Cabeçalhos

O middleware `ProtegerRespostas` define `Referrer-Policy: no-referrer` e `Cache-Control: private, no-store` nas respostas da aplicação. O middleware `RecusarCredenciaisNaUrl` recusa credenciais na URL e acrescenta `X-Content-Type-Options: nosniff`. Arquivos que o nginx serve direto seguem a configuração do nginx.

### nginx

Na publicação de L3, o nginx passou a registrar acessos com rotas classificadas, sem URI livre, query string, IP, cabeçalhos ou corpo. O serviço `g360-nginx-log-protegido` sanitiza os erros por um socket Unix. Os logs novos ficam em arquivos `gabarito360.access.protegido.log` e `gabarito360.error.protegido.log`. O histórico anterior foi preservado e não foi saneado. Não declare que todos os logs da infraestrutura estão limpos.

### Procedimento de publicação com mudança de cifra

Execute este roteiro em qualquer mudança que converta campos protegidos.

1. Obtenha aprovação de merge, de deploy e da configuração da chave.
2. Guarde em separado a chave de busca, a `APP_KEY` e as `APP_PREVIOUS_KEYS`. Não as escreva em relatório, Git ou conversa.
3. Obtenha um backup recente e confirme sua restauração com as chaves, em ambiente isolado.
4. Use o roteiro B5 com manutenção e fila drenada. O código antigo não pode escrever contatos em texto simples durante a migração.
5. Acompanhe a migração em lotes. O DDL (definição de esquema) do MariaDB não é transacional. Em caso de falha, mantenha a manutenção e retome com as mesmas chaves. O prefixo `g360:v1:` evita recifrar lotes concluídos.
6. Colisões de índice impedem a conversão. Não descarte registros para contorná-las.
7. Verifique login, convite e recuperação (legados e novos), confirmação de e-mail, cadastros e fila, com contas fictícias.

Os passos completos de publicação estão em [publicacao-producao.md](../operacao/publicacao-producao.md).

### Limites de L3 ainda abertos

- A aplicação não altera nem elimina logs históricos, caixas de e-mail, payloads antigos de falhas ou backups em texto simples.
- Não foi demonstrada exposição de arquivos reais pelo servidor. Mesmo assim, bloqueie qualquer publicação direta dos diretórios de fotos antes de criar um link público de `storage`. O link público de `storage` existe na API e não existe na web.
- A validação operacional de L3 não representa conformidade jurídica integral.

## Segurança de sessão e acesso

Este documento não repete o desenho de sessão, vínculo e permissão. Consulte [05-acesso-e-permissoes.md](04-identidade-acesso-e-permissoes.md) e [04-contratos-api-e-web.md](05-contratos-api-web.md). Os parâmetros verificados em 31/10/2026 são:

| Item | Valor |
|---|---|
| `APP_DEBUG` | Desativado na API e na web |
| Driver e duração da sessão | Redis, 120 minutos |
| Cookie de sessão | `Secure`, `HttpOnly`, `SameSite=Lax` |
| Cookie `XSRF-TOKEN` | `Secure`, legível por JavaScript (faz parte do mecanismo CSRF), `SameSite=Lax` |
| Cifra do payload da sessão (`session.encrypt`) | Desativada; proteja o Redis e a infraestrutura |
| Fila e falhas | Redis e `database-uuids` |
| Turnstile | Ativo na API e na web |

Um cookie de sessão vencido não prova a revogação automática do token de API.

## Pedidos dos titulares (L4)

L4 oferece protocolos de atendimento e exportação controlada de dados de um titular. Todas as flags ficam desligadas por padrão (`apps/api/config/pedidos_titulares.php`) e foram habilitadas só na homologação. Nada do L4 foi publicado na produção.

### Princípios

1. Um protocolo nunca dispara exportação, correção ou eliminação sozinho.
2. Abrir um protocolo é uma designação explícita. O Desenvolvedor abre e designa o responsável (Desenvolvedor ou Suporte com acesso interno ativo).
3. O Financeiro não tem acesso. Perfis escolares não têm rota de protocolo.
4. Atender um protocolo não concede leitura de dados escolares.
5. Não existe consulta pública por número de protocolo.
6. Identificadores, relatos e evidências são cifrados e nunca aparecem em URL, log ou evento genérico.

### Estados do protocolo (`EstadoPedidoTitular`)

| Origem | Destinos permitidos |
|---|---|
| `recebida` | `em_verificacao`, `cancelada` |
| `em_verificacao` | `em_analise`, `aguardando_informacao`, `cancelada` |
| `em_analise` | `aguardando_informacao`, `concluida`, `cancelada` |
| `aguardando_informacao` | `em_verificacao`, `cancelada` |
| `concluida`, `cancelada` | Nenhum |

Regras do ciclo:

1. A abertura sempre cria o protocolo em `recebida`.
2. A entrada em `em_analise` e a conclusão exigem a conferência de identidade e a de autoridade institucional, ambas vigentes no ciclo atual.
3. Cada pedido de informação incrementa o ciclo e invalida as conferências anteriores.
4. A conclusão exige um código: `atendido`, `parcialmente_atendido` ou `indeferido`. O cancelamento exige `cancelado_pelo_solicitante`, `duplicado` ou `inviavel_verificar`.
5. Estados finais não reabrem. Um pedido novo recebe um protocolo relacionado.
6. Concluir um protocolo registra um resultado administrativo. Não prova que a exportação, a correção ou a eliminação foi executada.

### Tipos e dados

Os tipos são `acesso`, `correcao`, `anonimizacao`, `eliminacao` e `informacao`. O tipo expressa a intenção do atendimento e não executa nada. Na abertura, informe: cliente, espaço, responsável, tipo, identificação declarada (até 200 caracteres), relato (até 2.000), motivo (5 a 500), fim da atribuição (opcional) e uma chave UUID de idempotência. A identificação declarada é uma alegação não verificada e não vincula o pedido a um aluno.

### Rotas da API

Todas ficam sob `/api/v1`, com o middleware de permissão e a flag `PEDIDOS_TITULARES_ATIVOS`.

| Método e rota | Função |
|---|---|
| `GET /pedidos-titulares` e `/opcoes` | Lista dos protocolos atribuídos; opções de abertura (só Desenvolvedor) |
| `POST /pedidos-titulares` | Abrir protocolo |
| `GET /pedidos-titulares/{uuid}` | Detalhe (registra evento obrigatório) |
| `POST /pedidos-titulares/{uuid}/triagem` | Mover para verificação, aguardar informação ou cancelar |
| `POST /pedidos-titulares/{uuid}/verificacoes` | Registrar a conferência manual de identidade ou de autoridade |
| `POST /pedidos-titulares/{uuid}/decisao` | Entrar em análise ou concluir |
| `POST /pedidos-titulares/{uuid}/sujeito` | Vincular o aluno identificado por ID explícito |

As mutações aceitam a versão esperada, uma chave idempotente e um motivo. A mesma chave com o mesmo conteúdo devolve o recibo original. A mesma chave com conteúdo diferente retorna 409.

### Verificações

Cada ciclo comporta uma conferência de identidade (`conferencia_documental` ou `canal_autenticado`) e uma de autoridade (`confirmacao_institucional` ou `instrumento_representacao`). O responsável atesta uma conferência manual já feita. A referência da evidência (5 a 500 caracteres) localiza o documento no procedimento da instituição. O sistema não envia documento, não consulta fonte externa e não busca por CPF. Cada conferência tem validade. Uma evidência vencida bloqueia a decisão.

### Sujeito

Só o responsável do protocolo vincula um aluno, por ID explícito. O aluno deve pertencer ao espaço do protocolo. Não existe busca global nem associação por nome ou CPF. Cada vinculação incrementa a versão do sujeito, inicia um novo ciclo de conferências e põe o protocolo em `em_verificacao`.

### Exportação controlada

O fluxo tem três passos, cada um com sua flag.

| Passo | Quem | Flag | Regra |
|---|---|---|---|
| Concessão | Administrador da rede (no próprio contexto) ou proprietário do espaço pessoal. Diretor escolar e perfis internos não aprovam | `PEDIDOS_EXPORTACAO_AUTORIZACOES_ATIVAS` | Permissão `pedido.exportacao.autorizar`. Exige protocolo de tipo `acesso` em análise, sujeito vinculado e as duas conferências vigentes. A concessão fixa aluno, versão, campos, destinatário (o responsável interno atual), validade e referência da autorização institucional |
| Geração | Responsável interno do protocolo (Desenvolvedor ou Suporte) | `PEDIDOS_EXPORTACAO_GERACAO_ATIVA` | Síncrona e transacional. Uma concessão gera no máximo um artefato |
| Entrega | Mesmo responsável interno | `PEDIDOS_EXPORTACAO_ENTREGA_ATIVA` | Consulta mínima e download autenticado |

Detalhes do artefato:

1. O único formato é `cadastro_minimo_v1`, com os campos `nome` e/ou `matricula` do aluno vinculado. CPF, nascimento, responsável, notas e anexos não entram.
2. O arquivo é um JSON UTF-8 canônico com manifesto (formato, referência opaca, concessão, instante UTC, expiração, seleção e SHA-256 dos dados). Fica cifrado no banco, limitado a 16 KiB, sem arquivo em disco nem URL.
3. Estados do artefato (`EstadoExportacaoTitular`): `solicitada`, `gerando`, `disponivel`, `falhou`, `revogada` e `expirada`. Os três últimos são finais. A expiração é exclusiva: no instante `expira_em`, não há entrega.
4. Cada download revalida a concessão, o destinatário, a integridade (tamanho, SHA-256, MAC da cifra e manifesto) e a expiração. O evento `download_liberado` é gravado na mesma transação antes de liberar os bytes. Se a auditoria falha, nada é entregue.
5. O download devolve um anexo com nome opaco (UUID), `Cache-Control` sem cache e `nosniff`. Não há resposta 304, intervalo parcial, HEAD (retorna 405), URL pública, token permanente nem e-mail automático.
6. Revogação, retirada de permissão, fim do vínculo do aprovador, mudança de sujeito ou de ciclo e saída do aluno do escopo impedem o uso, mesmo sem um job atualizar o estado.

Rotas: `POST /autorizacoes-exportacao/pedidos/{uuid}` (conceder), `GET` da mesma rota (consulta mínima), `POST /autorizacoes-exportacao/{uuid}/revogar`, `POST /pedidos-titulares/exportacoes/{uuid}` (gerar), `GET /pedidos-titulares/exportacoes/{uuid}` (consultar) e `GET /pedidos-titulares/exportacoes/{uuid}/arquivo` (baixar).

### Interface web

As telas ficam sob `/pedidos-titulares` e `/exportacoes-titulares` e dependem da flag de interface própria. Cada envio usa um ticket cifrado, ligado à sessão e ao contexto, com validade de 15 minutos e um UUID de idempotência. Uma repetição após falha de rede reaproveita o conteúdo original. A web só devolve os bytes de um download depois do sucesso na API, como anexo opaco sem cache. O token nunca chega ao navegador.

### Persistência

Migrations aditivas, com reversão destrutiva bloqueada, criam protocolos, eventos, recibos, verificações, histórico do sujeito, concessões e artefatos (`2026_11_02_000007` a `000011`). O histórico é somente de inclusão e não herda os 180 dias da auditoria.

### O que ainda falta em L4

| Parte | Situação |
|---|---|
| L4-D: correção, avaliação de retenção e preservação | Pendente. Sem política de prazo por instituição, nenhuma eliminação ocorre |
| L4-E: simulação de anonimização e eliminação, ensaio de recuperação | Pendente. Use somente dados sintéticos |
| Publicação em produção | Pendente. Decisão do responsável: publicar L4 só ao fechar A a E |
| T6 (encerramento de cliente) | Depende de L4 ([FN-028](../funcionalidades/028-encerramento-cliente/planejamento.md)) |

Nunca teste eliminação sobre os gabaritos existentes. Um prazo vencido não autoriza remover backup compartilhado. Defina o ciclo de backup e um procedimento de restauração que reaplique as decisões vigentes antes de reabrir os serviços.

## Avatares de alunos (L6)

O sistema é pedagógico e não precisa de foto de aluno. O aluno aparece como avatar. O PR 70 foi publicado em 31/10/2026, sem excluir fotos.

1. O componente de avatar mostra as iniciais por padrão. A cor deriva do ID do aluno e não muda com o nome. No cadastro, antes de existir ID, a prévia é azul.
2. O responsável aprovou uma galeria de 36 personagens em pixel art, com farda inspirada na Bahia. O seletor oferece 37 opções (iniciais mais 36 figuras), mostra a prévia na hora e só grava ao salvar o formulário.
3. A coluna `alunos.avatar_id` (migration `2026_10_31_000005_adicionar_avatar_alunos`) aceita inteiros de 1 a 36 ou nulo. A API valida `between:1,36`.
4. O campo `foto_path` não aceita atribuição em massa e é omitido das respostas do cadastro. A coluna e os arquivos antigos foram preservados.
5. Inventário em produção (31/10/2026, só leitura, agregado): 0 alunos com foto, 0 caminhos distintos e 0 arquivos no diretório `alunos`. O levantamento não cobre backups nem outros diretórios.
6. A eliminação dos arquivos e da coluna `foto_path` exige aprovação específica, backup e nova conferência. Ela não foi executada.
7. Fotos de cartões-resposta não mudam. São o documento da correção.

## Auditoria geral (AU1)

A auditoria registra requisições da API para fins de responsabilização. O procedimento operacional está em [auditoria.md](../operacao/auditoria.md). A funcionalidade é a [FN-004](../funcionalidades/004-auditoria/planejamento.md).

### O que o middleware registra

O middleware `RegistrarAuditoria` (terminável) registra requisições às rotas conhecidas de `api/v1/*`, inclusive leituras, alterações e respostas recusadas. Saúde e planos públicos em GET ficam fora. Rotas inexistentes não são guardadas, porque o caminho pode conter segredos. Cada evento contém:

- UUID, mês e horário em UTC;
- usuário autenticado e contexto validado antes da operação;
- método e modelo estático da rota (sem a URL concreta);
- referências numéricas permitidas;
- status HTTP e resultado;
- IP reduzido a rede IPv4 /24 ou IPv6 /48.

Não se guardam nomes, documentos, notas, campos enviados, resposta, query string, cabeçalhos, agente do navegador, tokens, chaves ou mensagens de exceção. O sistema não confia em cabeçalhos de identidade ou de contexto. O IP observado pode ser o da aplicação web ou do proxy.

### Persistência e falhas

1. O enfileiramento ocorre depois da resposta, na conexão `AUDITORIA_QUEUE_CONNECTION` (padrão `redis`) e na fila `baixa`. O job usa cifra Laravel com a `APP_KEY`. Conexões síncronas, nulas ou adiadas não são aceitas.
2. O job tem três tentativas, com intervalos de 10 e 60 segundos. O UUID torna as reentregas idempotentes.
3. A gravação usa a conexão `auditoria_gravacao`, com credencial própria (`AUDITORIA_DB_USERNAME` e `AUDITORIA_DB_PASSWORD`), limitada a INSERT e SELECT na tabela. A ausência dessa configuração faz o job falhar. Não existe retorno à conexão geral.
4. Falha ao enfileirar gera só o alerta `auditoria_enfileiramento_indisponivel`. A operação principal não é desfeita. A indisponibilidade da fila pode causar uma lacuna.
5. O modelo rejeita `save` e `delete`, e não existem rotas de manutenção. Isso não torna o armazenamento inviolável: quem tem credencial de banco ainda altera registros. A preservação externa (cópia mensal cifrada) oferece a evidência independente.
6. Processos de linha de comando e jobs de negócio não passam pelo middleware HTTP. Cada módulo que os use precisa de integração explícita (por exemplo, as cópias entre espaços).

### Política

O responsável aprovou 180 dias de retenção com preservação excepcional documentada (`AUDITORIA_RETENCAO_DIAS`; `AUDITORIA_MESES_PRESERVADOS` lista os meses preservados). **Não existe expurgo, `DROP PARTITION` nem agendamento de limpeza.** O particionamento nativo por mês não se aplica: a chave UUID global conflita com a exigência de incluir o mês nas chaves únicas. A tabela vem da migration `2026_11_01_000011_criar_auditoria_eventos`. Eventos históricos anteriores à ativação não foram reconstruídos.

### Alternativas avaliadas para a conservação

| Alternativa | Decisão | Motivo |
|---|---|---|
| Particionamento nativo por mês | Rejeitada | Toda chave única precisa incluir a coluna de particionamento. O mês na chave enfraquece a unicidade global do UUID |
| Registro global separado (ledger) | Adiada | Exige transação entre duas tabelas, migration e manutenção. Reavalie depois de medir o volume real |
| Arquivo mensal cifrado | Adotada | Mantém o banco como fonte autoritativa e a chave UUID global. Não reduz o banco nem acelera consultas |

O arquivo mensal é um pacote novo por execução, com linhas cifradas e autenticadas e um manifesto de contagem e digest. Evento tardio em mês fechado gera nova revisão e conserva a anterior. Digests detectam divergência só diante de referência confiável: não impedem adulteração conjunta por quem controla chave e cópias. A perda da `APP_KEY` impede a leitura dos arquivos e da fila. Guarde a chave separadamente. Meses preservados bloqueiam a classificação de descarte do mês inteiro. O arquivamento não autoriza expurgo. O isolamento entregue é o da credencial do job. Os privilégios reais das credenciais gerais são revisados à parte.

A tela de consulta (AU2), o "ver como" (S1) e o painel técnico continuam pendentes. Hoje real e visto são a mesma identidade.

## Relatório de impacto preliminar (RIPD) do portal

Este resumo é a avaliação técnica inicial de RL13. Ele não aprova riscos, não é laudo de conformidade e não libera o portal. O controlador precisa assumi-lo e preencher os campos pendentes. O portal dos responsáveis não existe no código: os controles abaixo são planejados, não implementados.

### Método

A escala é interna e preliminar. Probabilidade (P) e impacto (I) variam de 1 a 5. Risco = P × I. As faixas são: 1 a 4 baixo; 5 a 9 moderado; 10 a 15 alto; 16 a 25 crítico. Os valores são juízo técnico, não frequência medida. As notas descrevem o cenário sem os controles planejados.

### Riscos

| ID | Cenário | P / I / nível | Medida e responsável |
|---|---|---|---|
| R01 | Terceiro conhece matrícula e nascimento e chega primeiro ao portal | 4 / 5 / 20 crítico | Entrega assistida, registro do primeiro acesso, invalidação do código, limites e revisão do piloto (P1, P3, P4, P7) |
| R02 | Falha de escopo mostra dados de outro aluno ou instituição | 3 / 5 / 15 alto | Matriz de acesso e testes negativos por aluno e instituição (P1, P3, P5, P7) |
| R03 | Perfil indevido consulta o código do portal, ou o código vaza em log, URL ou impresso | 3 / 5 / 15 alto | Cifra, leitura só pela secretaria, auditoria sem segredo e invalidação (L3, AU1, P3, P4) |
| R04 | Foto de aluno, CPF ou dado sensível em campo livre amplia o dano | 3 / 4 / 12 alto | Cumprir L6 e o cadastro mínimo (A5) e orientar os campos livres |
| R05 | Foto ou resultado fica acessível por caminho público | 3 / 4 / 12 alto | Auditar acesso direto, assinatura, expiração, miniaturas e cache (L3) |
| R06 | Link de conta, e-mail ou conteúdo pessoal permanece em fila, log ou `failed_jobs` | 3 / 4 / 12 alto | Redação de logs, retenção e acesso à fila (L3, L4) |
| R07 | Prazo indefinido, ou restauração reintroduz o que já deveria estar eliminado | 3 / 4 / 12 alto | Matriz de prazos aprovada e reaplicação após restauração (L4) |
| R08 | Dados atravessam terceiros e países sem cadeia e instrumento confirmados | 3 / 4 / 12 alto | Matriz internacional e revisão de contratos (responsável e encarregado) |
| R09 | Leitura automática errada gera nota errada | 2 / 4 / 8 moderado | Revisão humana e contestação acessível |
| R10 | Familiar autorizado redistribui PDF ou fotografia | 3 / 4 / 12 alto | Minimizar o documento, orientar e usar marca d'água não secreta (P5, P6) |
| R11 | Marketing se confunde com condição de acesso ao resultado | 3 / 3 / 9 moderado | Testar que recusar comunicações não bloqueia o portal (P2, N3) |

Os níveis não demonstram vazamento. Eles vêm de análise documental, sem teste de invasão nem dados reais.

### Dados do portal e decisões já tomadas

1. Cadastro atual: nome, matrícula, nascimento, turma, escola, foto do cartão, respostas, notas e relatórios. CPF e gênero constam do cadastro. Foto de aluno consta do cadastro na homologação.
2. A primeira versão do portal não usa código por e-mail. A secretaria recupera o código e o código é legível só aos perfis autorizados.
3. O acesso planejado usa matrícula mais código, Turnstile, limite de tentativas, resposta uniforme e sessão de 15 minutos (P3).
4. Os PDFs e as impressões terão `noindex`, `no-store`, dados mínimos e marca d'água com um identificador do acesso, nunca o código de entrada.
5. Telas de alunos e responsáveis não têm propaganda.
6. Nascimento não é segredo. Priorize a entrega assistida e a invalidação pela escola.
7. A meta de capacidade (100 instituições, 600 professores, 1.000 responsáveis) não mede o volume tratado.

### Condições para o piloto

Antes do piloto, reúna as evidências: bases e retenção por instituição; contatos, termos e versões; dados mínimos; código, limites e sessão; isolamento; auditoria e resposta a incidentes; transparência sobre terceiros; teste do fluxo com dados fictícios; plano de retorno. Para cada risco, preencha controle implementado, evidência, avaliação residual justificada, decisão do controlador (aceitar, reduzir, evitar) e responsável. Não preencha a coluna residual só porque existe uma tarefa planejada.

## Avisos de privacidade e termos (L1)

As views `apps/web/resources/views/legal/privacidade.blade.php` e `termos.blade.php` existem com o texto preparado. Elas **não têm rota pública**: `/termos` e `/privacidade` respondem 404 de propósito. Não existem as tabelas `termos_versoes` e `termos_aceites`, nem bloqueio de aceite no login. O responsável adiou a publicação em 31/10/2026.

### Campos a preencher

`[DATA]`, `[RAZÃO SOCIAL]`, `[CNPJ]`, `[ENDEREÇO]`, `[NOME DO ENCARREGADO]`, `[E-MAIL DO ENCARREGADO]`, `[LINK DOS SUBOPERADORES]`, `[LINK DA TABELA DE RETENÇÃO]` e `[PRAZO DE EXPORTAÇÃO E ELIMINAÇÃO]`. Os campos ficam como texto. Não invente links, e-mails nem valores. A identificação da empresa se repete nas duas páginas.

### Conteúdo do aviso de privacidade

O aviso cobre, nesta ordem: identificação do operador e do encarregado; quem decide sobre os dados; dados tratados (usuários, alunos, responsáveis, clientes, contato); finalidades; natureza pedagógica do serviço; crianças e adolescentes; compartilhamento; transferência internacional; retenção; segurança; direitos dos titulares; cookies; medição de desempenho (Cloudflare Web Analytics, sem cookies); fontes do site (Google Fonts).

Compromissos do aviso:

1. O Gabarito360 é uma ferramenta pedagógica de provas, gabaritos e respostas. Não é rede social.
2. Os dados de crianças e adolescentes são tratados no melhor interesse deles.
3. O sistema não vende esses dados, não os usa em publicidade direcionada e não cria perfis de comportamento comercial.
4. O aviso afirma que não guarda fotos de alunos. **Não publique essa frase enquanto o código aceitar upload de foto de aluno** (ver divergências).

### Termo de ciência do responsável (portal)

O termo aparece no primeiro acesso ao portal e registra versão, data, hora, aluno e acesso. Declarações do termo: o responsável é pai, mãe ou responsável legal (ou autorizado); a instituição é responsável pelos dados escolares; o portal mostra notas só para consulta da família; o código de acesso é pessoal; a secretaria pode consultar ou gerar novo código; cada acesso é registrado; documentos impressos ou em PDF ficam sob responsabilidade de quem os gera; não se repassam dados a terceiros sem base legal; o responsável pode pedir acesso e correção à instituição. A opção de receber novidades do Gabarito360 é separada e opcional.

### Cláusulas para contratos com clientes

O contrato com escola, rede, secretaria ou professor deve conter: papéis de controlador e operador; instruções e finalidades; base legal definida pelo controlador; medidas de segurança; lista de suboperadores e aviso de mudanças; apoio em pedidos de titulares e incidentes; auditoria e prestação de contas; exportação e eliminação no fim do contrato, com prazo; regra de cópia de dados do professor.

### Regra do professor que sai da escola

Notas identificadas só seguem com o professor se o contrato da escola permitir, com base legal e finalidade legítima. Sem a cláusula, o sistema copia só provas, materiais e notas anonimizadas. Dados copiados para a escola permanecem nela. Se a escola sair, seus dados ficam preservados. A mecânica técnica está em [FN-024](../funcionalidades/024-copias-entre-espacos/planejamento.md) e [FN-025](../funcionalidades/025-transferencias-institucionais/planejamento.md).

### Pontos que exigem revisão jurídica

1. Professor que deixa a escola: levar dados de menores é compartilhar dados entre controladores.
2. Dados que permanecem após o desligamento da escola: possível durante o prazo contratual e legal, mas a escola precisa poder exportar e eliminar.
3. Propaganda em documentos: o bloco deve ser institucional, igual para todos e sem dados do aluno.
4. Primeiro acesso do portal: matrícula e nascimento são fatores fracos.
5. ECA Digital: a aplicabilidade depende de o serviço ser direcionado a menores ou de acesso provável por eles. Até a revisão jurídica, não use propaganda em telas de alunos, caixas de recompensa, perfilamento nem publicidade direcionada. Prefira as configurações mais protetivas por padrão.

## Decisões vigentes

| Decisão | Registro |
|---|---|
| Papéis de controlador e operador, como na tabela acima | Documento de LGPD (minuta técnica) |
| Sem foto nem vídeo de aluno; foto do cartão-resposta é mantida; o aluno é representado por avatar | RL19 e L6 (ver divergência abaixo) |
| Professor autônomo tem cadastro mínimo de alunos e não tem portal dos pais | RL18 |
| Cliente presenteado exibe a divulgação do Gabarito360 em PDFs; cliente pagante exibe "Feito no gabarito360.com.br" | Plano mestre antigo, fase 3 |
| API exclusiva do produto: tokens sem limite fixo de 8 horas | Decisão do responsável, 31/10/2026 |
| Sessão web de 120 minutos de inatividade | Decisão do responsável, 31/10/2026 |
| Cloudflare Web Analytics: manter e documentar | Decisão do responsável, 31/10/2026 |
| Auditoria: 180 dias com preservação excepcional, sem expurgo | Decisão do responsável |
| Exportação do titular: só Administrador da Rede ou proprietário do espaço pessoal concedem | Decisão do responsável, 02/11/2026 |
| Termos e privacidade: publicação adiada | Decisão do responsável, 31/10/2026 |
| Pendente (D13): dados da empresa para os termos (razão social, CNPJ, endereço, encarregado) | Pendente |
| Pendente (D14): revisão jurídica por advogado ou encarregado | Pendente |
| Pendente (D10): primeiro acesso do responsável, com código gerado pelo sistema (recomendação) | Pendente, bloqueia o portal |

## Pendências abertas

1. Preencher os dados da empresa e do encarregado e publicar L1 com tabelas de versão e aceite.
2. Implementar as rotinas de retenção aprovadas e os prazos ainda sem definição.
3. Concluir contratos, locais e mecanismo internacional dos fornecedores externos.
4. Confirmar a criptografia do disco do computador onde ficam as cópias de backup (consulta de 31/10/2026 inconclusiva).
5. Concluir L4-D e L4-E e publicar L4 completo.
6. Implementar o plano de incidentes (L5), que depende da auditoria.
7. Decidir se o retrato de aluno (autorizado em IM1) prevalece sobre a regra de L6.
8. Revisar o inventário para as tabelas posteriores a 31/10/2026.

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/33` (RL19), `docs/28` L6 e `docs/operacao/avatares-alunos.md` | Dizem que nenhuma tela ou API aceita foto de aluno | O código atual aceita o campo `foto` em `POST /api/v1/alunos` e grava `foto_path` (`AlunoController::store`), e há `POST /api/v1/alunos/{id}/imagem` e `GET /api/v1/alunos/{id}/foto` (preparo de fotos, IM1/PF1-C, só na homologação). A produção segue sem upload. A decisão entre manter o retrato privado ou voltar ao L6 está pendente ([FN-066](../funcionalidades/066-avatares-alunos/planejamento.md)) |
| `docs/34` | Diz que `AlunoController` aceita foto e que CPF e contatos não têm cifra | A cifra de CPF e contatos está publicada (L3). A foto passou por L6 e voltou por IM1 na homologação |
| `docs/34` | Diz que há 32 tabelas | O banco tem 130 tabelas (conferido em 09/11/2026, homologação). O inventário ainda não as cobre |
| `docs/35` | Diz que as views de prévia têm rota ausente | Confirmado: `apps/web/resources/views/legal/` tem as views e `apps/web/routes/web.php` não tem rota para elas |
| `docs/28` (L1) | Cita as tabelas `termos_versoes` e `termos_aceites` | Nenhuma migration as cria |
| `docs/32` | Diz que a captura de auditoria continua desligada no servidor | A produção foi ativada em 02/11/2026 com credencial própria de gravação e 180 dias (config `auditoria.ativa` depende de `AUDITORIA_ATIVA`) |
| `docs/operacao/protecao-dados.md` | Cita arquivos de log `.protegido.log` e um serviço `g360-nginx-log-protegido` | São configuração do servidor. Não existem no repositório e não foram reconferidos nesta migração |
| `docs/33` e `docs/38` | Falam em consentimento do portal como aceite de ciência | O portal não existe no código; o texto vale como requisito, não como estado atual |
