# 39 — Validações de L2 com o responsável

Data: 31/10/2026. Base: [inventário](34-inventario-de-dados.md) e [impacto preliminar](38-ripd-preliminar-portal.md). Escopo: registrar decisões recebidas na conversa e conferir parâmetros técnicos de produção sem consultar registros pessoais, conteúdo de logs, caixas de correio, fotos, dumps ou credenciais.

As decisões de retenção abaixo são políticas aprovadas para implementação posterior. Não foram executados expurgos, alterações de configuração, migrações, deploy ou mudanças nos serviços externos. A aprovação não comprova contratos de fornecedores, base legal de cada cliente ou aceite do risco por todas as instituições.

## 1. Declarações confirmadas pelo responsável

| Item | Confirmação | Tipo de evidência / limite |
|---|---|---|
| Guarda de dados escolares | Prazo definido por contrato com cada instituição, com preservação necessária durante o uso e previsão de exportação/eliminação no encerramento | Decisão da plataforma; cada contrato ainda precisa registrar finalidade, prazo, exceções e responsável |
| Servidores e backups próprios | Servidores e as duas cópias ficam no Brasil, sob administração do responsável | Declaração do responsável; não é inspeção física nem declaração sobre locais dos fornecedores externos |
| Acesso à infraestrutura/caixa | Não há outras pessoas com acesso ao servidor, backups ou caixa da equipe, segundo o responsável | Acesso declarado; permissões efetivas de contas, máquinas e recuperação não auditadas |
| Cloudflare e Resend | Contas próprias do responsável | Titularidade declarada; contratos/versões aceitos e mecanismo internacional ainda não comprovados |
| Correio da equipe | Gmail pessoal | Não presumir Google Workspace ou um DPA corporativo; modalidade/adequação contratual permanece em revisão |
| Mercado Pago | Sem assinatura ou pagamento real ativo | Declaração; não foi consultada a conta nem o banco. Código e tabelas continuam existentes, para avaliar antes de ativar |
| API | Destinada exclusivamente a esta aplicação; manter política atual, sem introduzir limite fixo de oito horas | Decisão recebida após apresentar a proposta. Não equivale a confirmar isolamento de rede ou expiração automática dos tokens |
| Cloudflare Web Analytics | Manter e documentar | Decisão do responsável; sem alteração no painel. Atualização do aviso fica apenas na view de prévia, sem rota pública |

Razão social, CNPJ, endereço, identificação do encarregado e responsável de cada instituição continuam como campos a preencher, conforme orientação anterior. A identificação por função neste registro não substitui esses dados.

## 2. Política de retenção aprovada, ainda não implementada

| Grupo | Prazo aprovado | Marco e cuidados para implementar |
|---|---|---|
| Logs da aplicação | 30 dias | Pela data do evento/arquivo; definir rotação e redigir dados antes de gravar. Não aplicar automaticamente a todos os logs de infraestrutura |
| Jobs falhos | 14 dias | Pela falha; não remover caso em atendimento, investigação ou preservação justificada |
| Resultado detalhado B6 | 30 dias | Pela conclusão/encerramento; eliminar nome, nota e demais dados do resultado após o prazo, sem reapresentá-lo como resultado atual |
| Recibo mínimo B6 | 90 dias | Referente ao mesmo envio, sem nome/nota/JSON detalhado; campos remanescentes e marco exato precisam ser documentados na implementação para preservar idempotência |
| Tokens vencidos | Remover em até 24 horas após vencer | Vale para registros com vencimento definido. Não criar vencimento por idade em tokens Sanctum sem expiração, contrariando a decisão da API |
| Contatos sem contrato | Seis meses desde a última interação | Registrar uma interação válida; não usar qualquer atualização de cadastro como se fosse contato. Definir separadamente clientes, suporte e caixas de e-mail |
| Dados escolares e cartões | Conforme contrato de cada instituição | Não definir um prazo escolar único; incluir cópias, contestação, exportação e encerramento |
| Backups | Oito datas, decisão já existente | Preservada; não confundir com prazo das tabelas de negócio |

As políticas foram aprovadas como proposta operacional, não como prazo universal exigido por lei. Exceções justificadas de suporte, incidente ou preservação devem ter motivo, responsável e data de revisão; não são autorização para guardar tudo indefinidamente.

Para o recibo B6, propor na implementação um marco inequívoco para os 90 dias e revisar os casos de envios que permanecem com erro. Não apagar um recibo ainda necessário a uma retentativa sem apresentar seu tratamento ao responsável. O mero UUID e os IDs relacionados também podem continuar sendo dados vinculáveis a pessoas; “mínimo” não significa “anônimo”.

Prazos de contas sem vínculo, auditoria, documentos fiscais, arquivos de importação e caixas de correio não foram aprovados por extensão desta tabela. Permanecem pendentes conforme docs/34.

## 3. Configuração técnica conferida

Leitura pontual em produção no CT 111, na versão `07340a8`, com saída restrita a flags e parâmetros técnicos. Não foram impressos valores de cookies, tokens, chaves, senhas, destinatários ou conteúdos pessoais. É um retrato dessa versão: alterações posteriores de L3 devem ser avaliadas pelo respectivo PR e registro de implantação, sem considerar esta leitura como validação delas.

| Item | API | Web | Interpretação |
|---|---|---|---|
| Debug | desativado | desativado | Estado observado |
| Sessão | Redis, 120 min | Redis, 120 min | Configuração atual; não é validade do token Sanctum |
| Expirar ao fechar navegador | não | não | Cookie tem duração definida, não apenas vida da janela |
| Cifrar payload da sessão no servidor | não | não | `session.encrypt=false`; não é declaração de senha em texto nem de cookie sem criptografia; proteção do Redis/infra continua relevante |
| Secure na configuração | null | null | Avaliado também no navegador público; o valor null não foi tratado como evidência de cookie inseguro |
| HttpOnly / SameSite | true / lax | true / lax | Cookie de sessão público conferido abaixo |
| Log | stack com single | stack com single | A retenção aprovada de 30 dias ainda não foi implementada |
| Canal daily | padrão de 14 dias | padrão de 14 dias | Canal não é o integrante observado da stack; não assumir rotação global de 14 dias |
| Turnstile | ativo | ativo | Configuração habilitada; não houve envio de formulário nesta validação |
| Link público storage | existe | não existe | Só a existência foi conferida; não foram lidas fotos nem testado acesso a arquivo pessoal |
| Sanctum | expiração global null | não aplicável | Mantido por decisão; logout/redefinição seguem os fluxos atuais. Sessão web vencida não prova revogação automática do token API |
| Fila/falhas | Redis / database-uuids | não conferido | Não houve consulta a payload ou conteúdo de failed_jobs |
| E-mail | SMTP em smtp.resend.com | não conferido | Compatível com a operação documentada; sem envio de teste |
| OMR | nativo | não aplicável | Sem leitura de cartão nesta entrega |

## 4. Navegação pública anônima

Chrome isolado, sem login e sem envio de formulários. Início, login e Fale Conosco responderam HTTP 200. A primeira tentativa de aguardar rede totalmente ociosa expirou no contato; a observação foi repetida por carregamento do documento e janela curta, sem exigir que o widget encerre todas as conexões.

Cookies observados, sem seus valores:

| Nome | Secure | HttpOnly | SameSite | Duração restante aproximada |
|---|---|---|---|---|
| XSRF-TOKEN | sim | não | Lax | 120 minutos |
| gabarito360-session | sim | sim | Lax | 120 minutos |

O cookie XSRF ser legível por JavaScript faz parte do mecanismo CSRF; não confundi-lo com o cookie de sessão. A amostra não comprova os cookies de todos os perfis autenticados, localidades, dispositivos ou configurações futuras.

Foram observadas conexões a `gabarito360.com.br`, `fonts.googleapis.com`, `fonts.gstatic.com`, `challenges.cloudflare.com` e `static.cloudflareinsights.com`, além de requisição ao caminho `/cdn-cgi/rum`. O conteúdo do beacon não foi lido nem registrado.

O fluxo de Web Analytics não aparecia no inventário baseado só nos templates; passa a integrar a lista de terceiros. A [documentação oficial da Cloudflare](https://developers.cloudflare.com/web-analytics/data-metrics/data-origin-and-collection/) identifica a origem desse beacon; o fornecedor informa que a medição não usa [cookies ou localStorage para métricas](https://developers.cloudflare.com/web-analytics/data-metrics/core-web-vitals/). Isso não substitui verificar os dados efetivos e os termos aplicáveis, nem uma análise de transparência/base por finalidade. Não se conclui apenas pela presença do script que ele exige ou dispensa consentimento.

## 5. Conferência de criptografia do backup local

A consulta a BitLocker do disco D: retornou acesso negado na camada de gerenciamento, seguida de mensagem sem volume associado. Resultado inconclusivo: não registrar proteção ativa ou disco desprotegido com base nessa execução. Confirmação administrativa permanece pendente. Não foram acessadas chaves de recuperação ou conteúdo dos backups; nenhuma criptografia foi ativada/desativada.

## 6. Contratos e riscos ainda pendentes

- Cloudflare e Resend: manter evidência da modalidade, versão contratual aplicável à conta e instrumento de transferência internacional. Páginas públicas de DPA são referências, não confirmação do aceite específico ou de cobertura do fluxo brasileiro. Fontes: [Cloudflare DPA](https://www.cloudflare.com/cloudflare-customer-dpa/), [Resend DPA](https://resend.com/legal/dpa) e [orientação ANPD](https://www.gov.br/anpd/pt-br/assuntos/assuntos-internacionais/transferencia-internacional-de-dados).
- Gmail pessoal: confirmar condições aplicáveis ao uso pela plataforma e retenção da caixa; avaliar uma modalidade contratual adequada antes de tratá-la como fornecedor coberto por acordo corporativo. Não foi determinada migração ou compra nesta validação.
- Dados/arquivos públicos, CPF/contatos, redação de logs, rotinas de retenção e contas sem vínculo: implementar e testar nos pacotes próprios, com aprovação de qualquer alteração de produção.
- Aviso com Analytics/Fonts: alteração autorizada de conteúdo na prévia; permanece sem rota pública, junto dos campos da empresa a preencher. Não publica uma promessa de que todos os controles já foram implantados.
- O portal não está liberado e sua avaliação residual permanece pendente de controles e validação institucional.

## 7. Situação após a validação

Foram validadas decisões de política e declarações operacionais; vários parâmetros foram conferidos tecnicamente. L2 permanece parcial pelos contratos, dados cadastrais, bases/prazos de cada instituição, lista pública, proteção do disco e validação institucional de impacto ainda pendentes. As políticas aprovadas não são controles já implementados em L3/L4.
