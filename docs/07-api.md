# Gabarito360 - Especificacao Inicial da API REST

## 1. Padroes gerais

- Prefixo inicial: `/api/v1`.
- Formato: JSON, exceto uploads e downloads.
- Autenticacao mobile: tokens revogaveis por dispositivo.
- Autenticacao web: sessao segura ou token conforme arquitetura escolhida.
- Datas: ISO 8601 com fuso.
- IDs: UUID.
- Listagens: paginacao, filtros e ordenacao permitida explicitamente.
- Toda autorizacao e validacao deve ocorrer no backend.
- Operacoes offline ou repetiveis devem aceitar `Idempotency-Key`.

## 2. Formato de resposta

### 2.1 Sucesso

```json
{
  "data": {},
  "meta": {
    "request_id": "uuid"
  }
}
```

### 2.2 Erro

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Os dados informados sao invalidos.",
    "details": {
      "campo": ["Mensagem de validacao."]
    }
  },
  "meta": {
    "request_id": "uuid"
  }
}
```

### 2.3 Codigos HTTP principais

| Codigo | Uso |
|---|---|
| 200 | Consulta ou alteracao concluida |
| 201 | Recurso criado |
| 202 | Tarefa assincrona aceita |
| 204 | Operacao sem corpo de resposta |
| 400 | Requisicao malformada |
| 401 | Nao autenticado |
| 403 | Sem permissao ou fora do escopo |
| 404 | Recurso inexistente no escopo |
| 409 | Conflito de estado, duplicidade ou sincronizacao |
| 422 | Falha de validacao |
| 429 | Limite de requisicoes excedido |

## 3. Autenticacao e contexto

| Metodo | Endpoint | Acesso | Finalidade |
|---|---|---|---|
| POST | `/auth/login` | Publico | Autenticar usuario |
| POST | `/auth/logout` | Autenticado | Revogar sessao/token atual |
| POST | `/auth/refresh` | Token valido | Renovar token quando aplicavel |
| POST | `/auth/forgot-password` | Publico | Solicitar recuperacao |
| POST | `/auth/reset-password` | Publico com token | Redefinir senha |
| GET | `/me` | Autenticado | Consultar usuario, perfis, escopos e permissoes |
| GET | `/me/aplicacoes` | Professor/Aplicador | Listar aplicacoes autorizadas para o app |
| GET | `/me/sessoes` | Autenticado | Listar sessoes ativas na V2 |
| DELETE | `/me/sessoes/{id}` | Autenticado | Revogar sessao na V2 |

**Validacoes de login:** credenciais obrigatorias, usuario ativo, rate limit e dispositivo identificavel no mobile.

## 4. Nucleos, escolas e usuarios

| Metodo | Endpoint | Acesso |
|---|---|---|
| GET | `/nucleos` | Administrador |
| POST | `/nucleos` | Administrador |
| GET | `/nucleos/{id}` | Administrador |
| PATCH | `/nucleos/{id}` | Administrador |
| DELETE | `/nucleos/{id}` | Administrador, como inativacao |
| GET | `/escolas` | Administrador; gestor do nucleo no proprio escopo |
| POST | `/escolas` | Administrador; gestor do nucleo |
| GET | `/escolas/{id}` | Administrador; gestor do nucleo no proprio escopo |
| PATCH | `/escolas/{id}` | Administrador; gestor do nucleo no proprio escopo |
| DELETE | `/escolas/{id}` | Administrador; gestor do nucleo no proprio escopo, como inativacao |
| GET | `/usuarios` | Gestores no escopo |
| POST | `/usuarios` | Gestores autorizados |
| GET | `/usuarios/{id}` | Gestores no escopo; proprio usuario consulta `/me` |
| PATCH | `/usuarios/{id}` | Gestores autorizados no escopo |
| POST | `/usuarios/{id}/perfis` | Administrador/gestor autorizado |
| DELETE | `/usuarios/{id}/perfis/{vinculoId}` | Administrador/gestor autorizado |
| POST | `/usuarios/{id}/inativar` | Administrador/gestor autorizado |
| GET | `/perfis` | Administrador/gestor autorizado; catalogo ativo para atribuicao |

**Filtros comuns:** `status`, `nucleo_id`, `escola_id`, `search`, `page`, `per_page`.

**Regras da gestao administrativa de usuarios:**

- O cadastro exige um perfil inicial e o escopo correspondente.
- Perfis globais nao recebem nucleo ou escola; perfil de gestor do nucleo exige `nucleo_id`; perfis escolares e operacionais exigem `escola_id`.
- Gestores de nucleo administram somente usuarios vinculados ao proprio nucleo ou as suas escolas.
- Responsaveis escolares administram somente usuarios integralmente vinculados a propria escola e nao concedem perfis globais ou de gestor do nucleo.
- Um gestor pode consultar um usuario compartilhado com seu escopo, mas nao pode alterar ou inativar a identidade enquanto existirem vinculos ativos fora de sua autoridade.
- Listagens e detalhes retornam apenas os vinculos visiveis ao ator e nunca retornam o documento completo.
- A revogacao de perfil encerra o vinculo com `fim_at`; nao remove o historico.
- A inativacao preserva usuario e vinculos, revoga tokens, sessoes e dispositivos mobile e impede novas autenticacoes.

## 5. Turmas e alunos

| Metodo | Endpoint | Acesso | Observacao |
|---|---|---|---|
| GET | `/turmas` | Conforme escopo | Aplicador recebe apenas vinculadas |
| POST | `/turmas` | Gestor da escola | Valida ano letivo e codigo |
| GET | `/turmas/{id}` | Conforme escopo | Inclui resumo autorizado |
| PATCH | `/turmas/{id}` | Gestor da escola | Mantem historico |
| DELETE | `/turmas/{id}` | Gestor da escola | Inativacao |
| POST | `/turmas/{id}/aplicadores` | Gestor da escola | Vincula usuario autorizado |
| DELETE | `/turmas/{id}/aplicadores/{usuarioId}` | Gestor da escola | Encerra vinculo |
| GET | `/turmas/{id}/alunos` | Conforme escopo | Lista alunos e status |
| GET | `/alunos` | Conforme escopo | Filtros por escola/turma |
| POST | `/alunos` | Gestor da escola | Cadastro individual |
| GET | `/alunos/{id}` | Conforme escopo | Dados minimos por perfil |
| PATCH | `/alunos/{id}` | Gestor da escola | Auditar campos sensiveis |
| DELETE | `/alunos/{id}` | Gestor da escola | Inativacao |
| POST | `/alunos/importacoes` | Gestor da escola | Upload e validacao; retorna job |
| GET | `/alunos/importacoes/{id}` | Solicitante/gestor | Consulta validacao e estado |
| POST | `/alunos/importacoes/{id}/confirmar` | Solicitante/gestor | Confirma lote validado |

## 6. Avaliacoes, gabaritos e modelos

| Metodo | Endpoint | Acesso |
|---|---|---|
| GET | `/avaliacoes` | Conforme escopo |
| POST | `/avaliacoes` | Gestor autorizado |
| GET | `/avaliacoes/{id}` | Conforme escopo |
| PATCH | `/avaliacoes/{id}` | Gestor autorizado; conforme status |
| DELETE | `/avaliacoes/{id}` | Gestor autorizado; arquivamento |
| POST | `/avaliacoes/{id}/publicar` | Gestor autorizado |
| POST | `/avaliacoes/{id}/finalizar` | Gestor autorizado |
| POST | `/avaliacoes/{id}/turmas` | Gestor autorizado |
| DELETE | `/avaliacoes/{id}/turmas/{turmaId}` | Gestor autorizado; se permitido |
| GET | `/avaliacoes/{id}/gabaritos` | Conforme escopo |
| POST | `/avaliacoes/{id}/gabaritos` | Gestor autorizado |
| POST | `/avaliacoes/{id}/gabaritos/{versaoId}/publicar` | Gestor autorizado |
| POST | `/avaliacoes/{id}/gabaritos/{versaoId}/recorrigir` | Permissao especial; V2 |
| GET | `/modelos-cartao` | Conforme escopo |
| POST | `/modelos-cartao` | Administrador/gestor autorizado |
| GET | `/modelos-cartao/{id}` | Conforme escopo |

### 6.1 Exemplo de criacao de avaliacao

```json
{
  "titulo": "Simulado de Matematica - Nivel 1",
  "tipo": "simulado",
  "nivel": "6o e 7o anos",
  "numero_questoes": 20,
  "alternativas": ["A", "B", "C", "D", "E"],
  "proprietario": {
    "tipo": "nucleo",
    "id": "uuid"
  },
  "modelo_cartao_id": "uuid"
}
```

## 7. Aplicacoes

| Metodo | Endpoint | Acesso |
|---|---|---|
| GET | `/aplicacoes` | Conforme escopo |
| POST | `/aplicacoes` | Gestor autorizado |
| GET | `/aplicacoes/{id}` | Conforme escopo |
| GET | `/aplicacoes/{id}/alunos` | Gestor/aplicador vinculado |
| GET | `/aplicacoes/{id}/status` | Conforme escopo |
| POST | `/aplicacoes/{id}/iniciar` | Gestor/aplicador vinculado |
| POST | `/aplicacoes/{id}/finalizar` | Gestor/aplicador vinculado |
| POST | `/aplicacoes/{id}/reabrir` | Permissao especial; V2 |
| PATCH | `/aplicacoes/{id}/alunos/{alunoId}/presenca` | Gestor/aplicador vinculado; V2 |

**Validacoes de inicio:** avaliacao publicada, gabarito vigente, modelo definido, turma autorizada e usuario vinculado.

## 8. Cartoes e leituras OMR

| Metodo | Endpoint | Acesso | Observacao |
|---|---|---|---|
| POST | `/aplicacoes/{id}/leituras` | Aplicador vinculado | Upload/processamento inicial |
| GET | `/leituras/{id}` | Conforme escopo | Retorna deteccoes e alertas |
| POST | `/leituras/{id}/confirmar` | Aplicador vinculado | Operacao transacional e idempotente |
| POST | `/leituras/{id}/reprocessar` | Usuario autorizado; V2 | Gera nova tentativa |
| GET | `/cartoes/{id}` | Conforme escopo | Historico autorizado |
| POST | `/cartoes/{id}/cancelar` | Permissao especial | Exige motivo |
| POST | `/cartoes/{id}/substituir` | Permissao especial | Mantem historico |

### 8.1 Criar leitura

`POST /aplicacoes/{id}/leituras` usa `multipart/form-data`:

- `imagem`: arquivo de imagem.
- `operacao_mobile_id`: UUID para idempotencia.
- `codigo_sistema_proposto`: identificador operacional adicional gerado pelo app; pode ser omitido quando o codigo impresso for suficiente.
- `modelo_cartao_id`: UUID esperado.
- `aluno_id`: opcional nessa etapa.
- `capturada_at`: data da captura.
- `dispositivo_id`: identificador autorizado.
- `localizacao`: opcional e somente com consentimento.

Resposta esperada:

```json
{
  "data": {
    "id": "uuid",
    "status": "parcial",
    "codigo_sistema_proposto": null,
    "codigo_impresso_detectado": "CARTAO-000123",
    "confianca_geral": 0.93,
    "respostas": [
      {
        "questao": 1,
        "detectada": "B",
        "tipo": "marcada",
        "confianca": 0.98
      },
      {
        "questao": 2,
        "detectada": null,
        "tipo": "dupla",
        "confianca": 0.42
      }
    ],
    "requer_revisao": true
  }
}
```

### 8.2 Confirmar leitura

`POST /leituras/{id}/confirmar`

Headers obrigatorios no mobile:

```text
Idempotency-Key: uuid
```

Payload:

```json
{
  "aluno_id": "uuid",
  "codigo_sistema": null,
  "codigo_impresso": "CARTAO-000123",
  "codigo_sistema_afixado": false,
  "motivo_sem_codigo_impresso": null,
  "aceita_alertas": true,
  "respostas_finais": [
    {
      "questao_id": "uuid",
      "alternativa": "B",
      "motivo_alteracao": null
    },
    {
      "questao_id": "uuid",
      "alternativa": "D",
      "motivo_alteracao": "Marcacao revisada na imagem"
    }
  ]
}
```

**Conflitos `409`:**

- `ALUNO_JA_CONFIRMADO`
- `CODIGO_IMPRESSO_JA_VINCULADO`
- `CODIGO_SISTEMA_JA_UTILIZADO`
- `APLICACAO_FINALIZADA`
- `OPERACAO_IDEMPOTENTE_DIVERGENTE`
- `LEITURA_JA_CANCELADA`

O `codigo_impresso` preserva o valor existente no papel e pode ser nulo quando o cartao realmente nao possui identificador externo. Nesse caso, `motivo_sem_codigo_impresso` deve ser `cartao_sem_codigo_impresso` e o cartao persistido deve receber um `codigo_sistema`. No fluxo online, o backend pode gera-lo quando o cliente omitir; em operacao offline, o app deve gera-lo antes da sincronizacao. Quando houver codigo impresso suficiente, `codigo_sistema` e opcional e nunca substitui o valor externo.

Exemplo para cartao sem codigo impresso:

```json
{
  "aluno_id": "uuid",
  "codigo_impresso": null,
  "codigo_sistema": "G360-8JK4N2P7Q5MX-H",
  "codigo_sistema_afixado": false,
  "motivo_sem_codigo_impresso": "cartao_sem_codigo_impresso",
  "aceita_alertas": true,
  "respostas_finais": []
}
```

## 9. Resultados, dashboards e relatorios

| Metodo | Endpoint | Acesso |
|---|---|---|
| GET | `/resultados` | Conforme escopo e filtros |
| GET | `/resultados/{id}` | Conforme escopo |
| GET | `/alunos/{id}/resultados` | Conforme escopo |
| GET | `/turmas/{id}/resultados` | Conforme escopo |
| GET | `/avaliacoes/{id}/resultados` | Conforme escopo |
| GET | `/dashboards/nucleo/{id}` | Gestor do nucleo/consulta autorizada |
| GET | `/dashboards/escola/{id}` | Gestor da escola/nucleo |
| GET | `/dashboards/aplicacao/{id}` | Gestor/aplicador/consulta autorizada |
| POST | `/relatorios` | Usuario autorizado |
| GET | `/relatorios/{id}` | Solicitante/gestor autorizado |
| GET | `/relatorios/{id}/download` | Solicitante/gestor autorizado |

**Filtros de resultados:** `avaliacao_id`, `aplicacao_id`, `nucleo_id`, `escola_id`, `turma_id`, `aluno_id`, `status`, `periodo`.

## 10. Auditoria e sincronizacao

| Metodo | Endpoint | Acesso |
|---|---|---|
| GET | `/auditoria` | Usuario autorizado |
| GET | `/auditoria/{id}` | Usuario autorizado no escopo |
| POST | `/sincronizacoes/lote` | App autenticado; V2 |
| GET | `/sincronizacoes/{operacaoId}` | App/usuario proprietario; V2 |
| GET | `/me/dispositivos` | Usuario autenticado; V2 |
| DELETE | `/me/dispositivos/{id}` | Usuario autenticado; V2 |

## 11. Tempo real

O backend publica eventos apos a confirmacao da transacao. Canais devem aplicar autorizacao no momento da assinatura.

| Canal conceitual | Eventos principais |
|---|---|
| `nucleo.{id}` | Progresso agregado, escola atualizada |
| `escola.{id}` | Aplicacao atualizada, indicadores alterados |
| `aplicacao.{id}` | Leitura confirmada, aluno atualizado, alerta criado, aplicacao finalizada |

Eventos nao devem expor dados pessoais alem do necessario ao cliente autorizado.

## 12. Validacao, autorizacao e auditoria

- Requests validam estrutura, tipos, limites e coerencia dos dados de entrada, sem substituir a autorizacao.
- Policies validam permissao, recurso e relacionamento organizacional.
- Actions executam um caso de uso e coordenam regras, persistencia e transacoes.
- Services oferecem capacidades reutilizaveis, algoritmos ou integracoes, sem orquestrar endpoints completos.
- Resources definem e minimizam a representacao de saida, sem executar consultas ou alterar estado.
- Endpoints criticos registram auditoria com `request_id`.
- Uploads validam MIME real, tamanho, extensao e, quando aplicavel, malware.
- Downloads usam autorizacao e URL temporaria.
- Endpoints de listagem limitam `per_page` e campos de ordenacao.
- Erros internos retornam codigo generico ao cliente e detalhes somente nos logs protegidos.

## 13. Versionamento e documentacao

- Mudancas incompativeis geram nova versao da API.
- OpenAPI deve ser mantido junto da implementacao a partir do inicio do backend.
- Campos novos devem ser preferencialmente opcionais para clientes antigos.
- O app deve informar sua versao; o backend pode exigir atualizacao minima por motivo de seguranca.

## 14. Convencoes de implementacao

### 14.1 Fluxo por camada

```text
Route -> Form Request -> Policy -> Controller -> Action
      -> DTO / Model / Service -> Resource -> Response
```

- Controllers adaptam HTTP, invocam autorizacao e caso de uso e devolvem resposta; nao contem regra de negocio ou transacao.
- Form Requests nao sao barreira suficiente de escopo. A Policy e os filtros aplicados pelo caso de uso permanecem obrigatorios.
- Uma Action representa um caso de uso nomeado e deve abrir a transacao quando a operacao exige atomicidade.
- Services nao devem conhecer Request, Controller ou formato de resposta HTTP.
- Resources nao devem disparar queries adicionais, autorizar ou expor campos pessoais sem necessidade.
- Web, API e Jobs reutilizam as mesmas Actions e Policies quando executam o mesmo caso de uso.
- Eventos, Jobs e notificacoes dependentes da persistencia devem ser publicados somente apos o commit.

Os criterios completos para escolher cada camada e os gates de qualidade estao em [CONTRIBUTING.md](../CONTRIBUTING.md).

### 14.2 Contrato e erros

- Todo endpoint funcional novo deve usar `/api/v1`.
- O endpoint tecnico de verificacao esta disponivel em `/api/v1/health`.
- Sucesso e erro devem seguir os envelopes da secao 2 e incluir `meta.request_id`.
- O header `X-Request-ID` aceita UUID informado pelo cliente ou recebe um UUID gerado pelo backend.
- O `request_id` deve ser compartilhado com o contexto dos logs sem registrar payloads sensiveis.
- Codigos de erro estaveis usam `UPPER_SNAKE_CASE` e nao dependem da mensagem apresentada.
- Erros `403` indicam acao conhecida fora da permissao; `404` pode ocultar recurso fora do escopo; `409` representa conflito de estado ou integridade esperado.
- Nenhum endpoint deve criar formato de resposta proprio ou retornar excecao interna ao cliente.

### 14.3 Transicao da base tecnica

A transicao da base tecnica foi concluida no MP-007. O componente central de resposta, o health check e o tratamento global de excecoes seguem os envelopes da secao 2.

- nenhum endpoint deve reintroduzir o envelope temporario com `success`, `message` e `errors`;
- toda resposta de API testada deve possuir `meta.request_id` e o header `X-Request-ID`;
- excecoes internas devem ser registradas com correlacao e retornar somente mensagem generica ao cliente;
- qualquer mudanca futura no contrato deve atualizar testes e OpenAPI na mesma entrega.
