# Contratos da API e do Web

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve o contrato entre a API e o Web: o formato das rotas, as regras de URL (UR1 a UR4), a sessão com vínculo único e o contrato HTTP independente (AP1). Ele não copia tabelas de rotas. As rotas vivem em `apps/api/routes/api.php` e `apps/web/routes/web.php`, que são a fonte de verdade.

Funcionalidade relacionada: FN-018 (segurança de URLs, sessão e dependências). Desempenho: FN-077.

## 1. Princípios

1. A API responde só JSON. As exceções são downloads de arquivo (PDFs e imagens), nunca telas.
2. O Web é um BFF (backend for frontend). Ele renderiza HTML e chama a API por HTTP.
3. O Web não acessa o banco de negócio e não importa código da API.
4. O navegador nunca recebe token, handle de contexto ou URL da API.
5. A navegação do Web não obriga a mudar caminho ou corpo da API.

## 2. API

### Base e autenticação

- Prefixo: `/api/v1`. A raiz anterior à versão vem da variável `API_BASE_URL` do Web. O Web acrescenta `/v1`. Não coloque `/v1` na variável.
- Autenticação: Laravel Sanctum, com `Authorization: Bearer <token>`.
- Rotas públicas: `health`, `planos`, `leads`, `documentos/validar`, `webhooks/mercadopago` e as rotas públicas de `auth` (login, esqueci-senha, redefinir-senha, confirmar-email, convite).
- Rotas protegidas usam `auth:sanctum`, `contexto_vinculo` e `senha_trocada`.
- Cada rota protegida declara a permissão com `permissao:<chave>` ou o perfil com `perfil:<nome>`. As chaves de permissão estão em [04](04-identidade-acesso-e-permissoes.md).

### Dois tipos de token

| Token | Uso |
|---|---|
| Token de identidade | Representa a pessoa. Lista vínculos (`GET auth/vinculos`) e seleciona um (`POST auth/vinculos/selecionar`). |
| Token do vínculo (contextual) | Representa a atuação em uma escola, perfil e espaço. Todas as rotas de negócio exigem este token. |

Acessos internos têm seleção própria (`auth/acessos-internos`). O token contextual não emite outro contexto.

### Grupos de rotas

| Grupo | Prefixo | Assunto |
|---|---|---|
| Autenticação e conta | `auth/*` | Login, sessão, vínculos, troca de e-mail, imagem do perfil. |
| Estrutura escolar | `nucleos`, `escolas`, `turmas`, `alunos`, `censo` | Cadastros, arquivamento, imagens, logos. |
| Equipe | `usuarios`, `permissoes/perfis`, `configuracoes/funcoes` | Equipe, convites, permissões por usuário. |
| Currículo | `disciplinas`, `areas-conhecimento`, `turmas/{turma}/disciplinas` | Disciplinas, áreas e componentes da turma. |
| Provas e gabaritos | `provas` | Provas, gabarito, cópia de prova. |
| Correção e OMR | `provas/{id}/cartoes`, `cartoes`, `tentativas` | Envio de fotos, leituras, revisão, tentativas. |
| Resultados e relatórios | `relatorios`, `alunos/{id}/resultados`, `provas/{id}/resultados/pdf` | Relatórios e PDFs. |
| Painéis | `dashboard/*`, `turmas/{id}/painel`, `paineis/preferencias` | Dados dos painéis e preferências. |
| Catálogo de instituições | `catalogo/*` | Consulta, referências pessoais, solicitações, atendimento, gestão. |
| Cópias entre espaços | `copias/*` | Seleção, recebimento, lotes e aprovação. |
| Pedidos de titulares | `pedidos-titulares`, `autorizacoes-exportacao` | Pedidos LGPD, exportação protegida. |
| Clientes e acessos internos | `clientes`, `acessos-internos` | Cadastro de clientes e acessos internos. |
| Cobrança | `assinaturas`, `planos`, `webhooks/mercadopago` | Mantidas na API. A contratação é assistida (FN-030). |

### Dashboards

A API tem uma rota por tipo de painel: `admin`, `dir-nucleo`, `dir-escolar`, `coordenador`, `professor`, `aluno`, `secretaria` e `aplicador`. O perfil é conferido pelo middleware `perfil`.

### Controle de uso e proteção

- Rotas sensíveis usam `throttle` (por exemplo, login e redefinição de senha).
- O middleware `RecusarCredenciaisNaUrl` responde 400 se a query tiver `token`, `senha` ou campos semelhantes.
- O middleware `ProtegerRespostas` envia `Cache-Control: private, no-store`.
- O middleware `RegistrarAuditoria` registra as requisições conforme [11](11-seguranca-e-privacidade.md).
- A rota antiga `cartoes/{id}/arquivo/{tipo}` responde 404. Uma assinatura na URL não substitui autenticação.

### Formato de resposta e erro

| Situação | Código | Corpo |
|---|---|---|
| Sucesso | 200 ou 201 | JSON do recurso. Não há envelope único: cada controlador define seu formato. |
| Validação | 422 | `message` e `errors` por campo. |
| Sem autenticação | 401 | `message`. A API nunca redireciona para login. |
| Sem permissão ou contexto | 403 | `message`. |
| Fora do escopo ou inexistente | 404 | `message`. A API usa 404 para recurso alheio. |
| Excesso de uso | 429 | Padrão do Laravel. |
| Credencial na URL | 400 | `message` orientando a usar o formulário. |

A API formata erros como JSON para todo caminho `api/*`.

## 3. Web (BFF)

### Papel do ApiClient

O `ApiClient` envia o token do vínculo ativo, com prazo de 15 segundos (180 segundos em PDFs). Ele conhece apenas `services.api.base_url`. Ele pode enviar os cabeçalhos técnicos `X-G360-Painel-Turma` e `X-G360-Painel-Editor`. Esses cabeçalhos não concedem autoridade. A API revalida o escopo.

### Grupos de rotas do Web

| Grupo | Exemplos | Assunto |
|---|---|---|
| Público | `/`, `/planos`, `/fale-conosco`, `/validar-documento`, `/health` | Páginas sem login. |
| Visitante | `/login`, `/esqueci-senha`, `/redefinir-senha`, `/convite` | Entrada e recuperação. Só para quem não tem sessão. |
| Confirmação | `/confirmar-email` | GET mostra formulário. POST confirma. |
| Área autenticada | `/app/*` | Todas as telas. Veja a seção 4. |
| Rotas de migração | Mesmos caminhos sem `/app`, e `/acessos/{acesso}/...` | Entrada legada que só encaminha. Nomes começam com `legado.` e `contexto.`. |

Os módulos sob `/app` cobrem painéis, escolas, turmas, alunos, membros, provas, gabaritos, correção, resultados, relatórios, disciplinas, áreas, catálogo, cópias, clientes, acessos internos, pedidos de titulares, perfil e configurações.

## 4. Regras de URL (UR1 a UR4)

### UR1 — sem credenciais na URL

1. O token da API fica no servidor do Web.
2. Links de e-mail (recuperação, convite, confirmação) levam um endereço fixo. O código vai em texto separado e é digitado no formulário.
3. Um GET só mostra o formulário. A ação exige POST com CSRF.
4. A confirmação de troca de e-mail é de uso único e consumida de forma atômica.
5. Fotos de cartões passam por rota autenticada do Web. O navegador não recebe URL assinada.
6. O proxy de imagens aceita só ID e tipo permitido (`miniatura`, `endireitada`, `original`). Ele não segue destino indicado pelo navegador.
7. A borda recusa `/storage` antes do PHP. O log de acesso não registra query, Referer, cookies nem Authorization.
8. O código público de validação de documento não é credencial.

### UR2 — dependências

Os pacotes de runtime (Laravel 13, Guzzle, Symfony, CommonMark, Flysystem) foram atualizados para versões sem alertas. A CI roda `composer audit --locked --no-dev` nos dois aplicativos. Veja [11](11-seguranca-e-privacidade.md).

### UR3 — navegação estável e vínculo único por sessão

Decisão do responsável (05/11/2026): login, depois `/app/vinculos`, depois seleção explícita, depois `/app/painel`. As ferramentas ficam em endereços estáveis como `/app/turmas`.

1. Há um único vínculo ativo por sessão.
2. Trocar escola ou perfil vale para todas as abas da sessão.
3. Ações de telas antigas são bloqueadas.
4. A seleção bem-sucedida gera uma nova revisão de acesso.
5. O formulário traz o campo `_revisao`. Requisições de dados trazem o cabeçalho `X-G360-Revisao`.
6. Revisão ausente, antiga ou contraditória gera 409. A ação não executa.
7. Um mutex de arquivo (`flock`) serializa as requisições da mesma sessão. A espera máxima é de 15 segundos. Depois disso, a resposta é 423.
8. O mutex serve a um único servidor com disco compartilhado (`session.context_lock_topology`). Antes de escalar o Web para vários servidores, troque-o por exclusão distribuída.
9. A sessão dura 120 minutos sem atividade (`SESSION_LIFETIME`).

### UR4 — recursos fora das URLs

Decisão do responsável (06/11/2026): URLs do navegador não mostram identificadores de recursos.

1. O Web registra um catálogo de operações (`RotasNavegacao`). Ele gera uma URL fixa para cada rota de `/app`.
2. A URL fixa é a rota sem parâmetros. Se a rota termina em parâmetro, o sufixo indica a ação: `detalhes` (GET), `atualizar` (PUT), `excluir` (DELETE) ou `executar` (demais).
3. Cada rota de leitura ganha um POST irmão com final `/consultar`. O JavaScript hidrata a tela por esse POST.
4. O navegador guarda uma referência opaca por entrada de histórico. Ele a envia no campo `_recurso`, com CSRF e revisão.
5. O servidor resolve a referência para a operação e os parâmetros exatos. O navegador não escolhe controlador, host nem método de negócio.
6. A referência não autoriza nada. O controlador e a API conferem permissão e escopo como antes.
7. Duas abas podem abrir duas turmas diferentes no mesmo endereço. Uma aba não troca o alvo da outra.
8. Sem estado válido na aba, o usuário escolhe de novo o recurso. O sistema não recupera "o último recurso" da sessão.
9. Filtros, buscas e paginação não são autorização.
10. Os caminhos REST da API não mudam.

O código de navegação fica em arquivos JavaScript externos (`navegacao.js`, `contexto-aba.js`, `estado-tela.js`).

Cabeçalhos do Web: `X-G360-Revisao`, `X-G360-Hidratar`, `X-G360-Recurso`, `X-G360-Navegacao`.

### UR4 — sessão e vínculo com o mesmo ciclo de vida

Decisão do responsável (06/11/2026): o vínculo não expira separadamente.

1. O contexto ativo fica cifrado dentro da sessão, ligado à identidade e ao ID da sessão.
2. O cache anterior permanece só por compatibilidade. Sessões válidas migram ao serem lidas.
3. Se o contexto escolhido for perdido ou ficar inválido, o Web invalida a sessão inteira, renova o CSRF e descarta os snapshots de autoridade. Depois, ele responde 401 ou leva ao login.
4. O Web nunca reenvia operações após essa falha.
5. Uma revisão antiga após troca legítima gera 409. A aba antiga oferece só o painel atual.

### UR4 — recuperação de acesso expirado

1. O Web confere o contexto antes de emitir a estrutura da tela e antes de resolver a referência.
2. A ordem de verificação é: CSRF, revisão, contexto, alvo, autorização.
3. 401 e 419 levam ao login fixo. Revisão antiga (409) oferece só a seleção de vínculo.
4. Nenhum perfil é escolhido automaticamente. Nenhuma URL devolvida em JSON é seguida.
5. A compilação do catálogo de URLs ocorre uma vez por requisição. O cache não guarda identificadores, referências, identidade nem permissões.

## 5. Contrato HTTP independente (AP1)

### Fronteira

A API e o Web têm Composer, lock, bootstrap, configuração e runtime próprios. O Web usa o `ApiClient` e não importa modelos, controladores nem banco da API. Os dois rodam em grupos de processos PHP separados na homologação.

`API_BASE_URL` é a raiz anterior à versão. Exemplos: `http://127.0.0.1:8001/api` em desenvolvimento. Num host separado, use algo como `https://api.example.test/servico/api`. O valor já configurado no servidor é preservado.

### Contrato do painel do coordenador

| Elemento | Contrato |
|---|---|
| Recurso | `GET /api/v1/dashboard/coordenador` |
| Seleção | `escola_id` opcional. Várias opções exigem seleção. |
| Autenticação | Bearer no cabeçalho, de servidor a servidor. Nunca em query. |
| Sucesso | 200 com `data.escola`, `data.kpis`, `data.provas`, `data.alunos_atencao`, `data.agenda`. |
| Provas | Até oito, por atualização decrescente. |
| Agenda | Até quatro entradas. |
| Recusas | 401 sem autenticação, 403 sem contexto, 404 para seleção alheia, 422 para seleção necessária ou inválida. |

### Regras de evolução

1. Não altere método, caminho, status, campos, tipos ou limites para atender o Web.
2. Não crie cache de autorização que sobreviva à chamada. Cada uso consulta o banco.
3. Registre medições e recibos fora do Git, sem tokens, senhas ou dados pessoais.
4. A separação física em servidores diferentes exige aceite próprio. Ela ainda não existe.

### Testes do contrato

| Teste | Garante |
|---|---|
| `DashboardDesempenhoContratoTest` | Carga útil, tipos, limites e crescimento de consultas (1, 4 e 8 provas). |
| `HierarquiaAcessoConsultasTest` | Revogação e mudança de alcance continuam visíveis. |
| `ContratoApiIndependenteTest` | Fronteira HTTP do Web e configuração padrão. |

## 6. Estabilização da turma (ST1)

Em 05/11/2026, a tela de turma falhou por tempo esgotado (a API levou cerca de 12 segundos). A causa era a reconstrução de escopos e notas por aluno e por prova. A correção agrega por aluno e prova com clones de `ConsultasPedagogicas`. Endpoints, parâmetros e JSON não mudaram. O Web passou a tratar a indisponibilidade com 503, sem métricas falsas. Veja [12 Desempenho](12-desempenho.md).

## Divergências encontradas

| Documento antigo | Afirmação | Situação no código |
|---|---|---|
| `10-rotas-api.md` | Autenticação por JWT. | Só Sanctum. |
| `10-rotas-api.md` | Rota `POST /auth/register`. | Não existe. O cadastro é por convite. |
| `10-rotas-api.md` | Rotas `/visitas`, `/integracoes/seges/*`, `/redes`. | Não existem na API (visitas: FN-058, pendente). Existem só `redes/{id}/logo`. |
| `10-rotas-api.md` | `POST /provas/{id}/turmas`, `/provas/{id}/resultados`, `/dashboard/diretor-nucleo`. | Hoje: `dashboard/dir-nucleo`; vínculo de turmas por outro fluxo; resultados por `relatorios/*`. |
| `10-rotas-api.md` | Envelope `{ data, meta }`. | Cada controlador define seu JSON. Não há envelope único. |
| `10-rotas-api.md` | Desativar escola por `/ativar` e `/desativar`. | Hoje: `POST .../toggle` e `PUT .../arquivamento`. |
| `11-rotas-web.md` | Rotas `/painel`, `/equipe`, `/cadastro`, `/visitas`. | Hoje: `/app/painel`, `/app/membros`. Não há `/cadastro` nem `/visitas`. |
| `11-rotas-web.md` | Middleware `checkPerfil` e `checkEscopo` no Web. | O Web usa `autenticado`, `RevisaoAcesso` e `RecursoNavegacao`. A API valida perfil e escopo. |
| `167-ur3-rotas-app-contexto-sessao.md` | Cache guarda o contexto por 120 minutos. | Desde a UR4, o contexto fica na sessão. O cache é só compatibilidade. |
| `165-ur1-seguranca-e-urls.md` | URL `/acessos/vinculo-5/turmas/14`. | Substituída pela UR3 e pela UR4. A rota `/acessos/{acesso}/...` existe só como entrada legada de migração. |
| `167-ur3-rotas-app-contexto-sessao.md` | Módulos como `/app/turmas` mostram o ID no endereço. | A UR4 trocou o ID por URL fixa e referência no corpo. |
| `172` e `173` | Pipeline publica API e Web juntos. | A separação de publicação continua pendente (FN-007, FN-077). |
