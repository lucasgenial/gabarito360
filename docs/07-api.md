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
| GET | `/me/sessoes` | Autenticado | Listar sessoes ativas |
| DELETE | `/me/sessoes/{id}` | Autenticado | Revogar sessao propria |

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

## 6. Provas, questoes, gabaritos e modelos

| Metodo | Endpoint | Acesso |
|---|---|---|
| GET | `/provas` | Administrador; gestor de nucleo no proprio escopo |
| POST | `/provas` | Administrador; gestor de nucleo no proprio escopo |
| GET | `/provas/{id}` | Administrador; gestor de nucleo no proprio escopo |
| PATCH | `/provas/{id}` | Administrador; gestor de nucleo no proprio escopo; somente rascunho |
| GET | `/provas/{id}/questoes` | Administrador; gestor de nucleo no proprio escopo |
| POST | `/provas/{id}/questoes` | Administrador; gestor de nucleo no proprio escopo; somente rascunho |
| GET | `/provas/{id}/questoes/{questaoId}` | Administrador; gestor de nucleo no proprio escopo |
| PATCH | `/provas/{id}/questoes/{questaoId}` | Administrador; gestor de nucleo no proprio escopo; somente rascunho |
| POST | `/provas/{id}/publicar` | Administrador; gestor de nucleo no proprio escopo; exige `gabarito_oficial_id` completo |
| POST | `/provas/{id}/finalizar` | Gestor autorizado; etapa futura |
| GET | `/provas/{id}/turmas` | Administrador, gestor de nucleo ou responsavel escolar no escopo autorizado |
| POST | `/provas/{id}/turmas` | Administrador, gestor de nucleo ou responsavel escolar; prova publicada e turma ativa compativel |
| DELETE | `/provas/{id}/turmas/{turmaId}` | Administrador, gestor de nucleo ou responsavel escolar no escopo autorizado |
| GET | `/provas/{id}/gabaritos` | Administrador; gestor de nucleo no proprio escopo |
| POST | `/provas/{id}/gabaritos` | Administrador; gestor de nucleo no proprio escopo; prova rascunho |
| GET | `/provas/{id}/gabaritos/{gabaritoId}` | Administrador; gestor de nucleo no proprio escopo |
| GET | `/provas/{id}/gabaritos/{gabaritoId}/respostas` | Administrador; gestor de nucleo no proprio escopo |
| PUT | `/provas/{id}/gabaritos/{gabaritoId}/respostas/{questaoId}` | Administrador; gestor de nucleo no proprio escopo; rascunho |
| GET | `/provas/{id}/gabaritos/{gabaritoId}/validacao` | Administrador; gestor de nucleo no proprio escopo |
| POST | `/provas/{id}/gabaritos/{versaoId}/recorrigir` | Permissao especial; V2 |
| GET | `/modelos-cartao` | Conforme escopo |
| POST | `/modelos-cartao` | Administrador/gestor autorizado |
| GET | `/modelos-cartao/{id}` | Conforme escopo |
| PATCH | `/modelos-cartao/{id}` | Administrador/gestor autorizado; somente rascunho |
| POST | `/modelos-cartao/{id}/homologar` | Administrador/gestor autorizado; configuracao completa |
| DELETE | `/modelos-cartao/{id}` | Administrador/gestor autorizado; inativacao |

Modelos globais sao gerenciados somente pelo administrador geral. O gestor de nucleo consulta modelos globais e gerencia apenas modelos do proprio nucleo. A resposta inclui a configuracao OMR completa e seus limiares versionados. A homologacao exige checksum SHA-256 do artefato, sem placeholders ou limiares pendentes, e torna a versao imutavel.

Provas e questoes em rascunho foram implementadas no MP-025. Cada prova pertence exatamente a um nucleo ou escola, deve usar modelo de cartao homologado global ou do mesmo nucleo e deve repetir exatamente suas quantidades e alternativas. O codigo e unico, sem diferenciar maiusculas e minusculas, dentro do proprietario. Questoes possuem numero unico por prova e nao podem exceder a quantidade configurada.

O MP-026 implementa versoes sequenciais de gabarito em rascunho, preenchimento idempotente de uma resposta oficial por questao e validacao de completude. Alternativas devem pertencer a prova; questao anulada deve possuir alternativa nula e preserva seu peso conforme o ADR-D004. Respostas usam o peso informado ou, quando omitido, o peso padrao da questao.

O MP-027 implementa `POST /provas/{id}/publicar`. O corpo deve informar `gabarito_oficial_id`, pertencente a prova e ainda em rascunho. A operacao valida novamente modelo homologado, configuracao da prova, quantidade de questoes ativas e completude das respostas. Em uma unica transacao, o gabarito selecionado se torna `vigente` e a prova se torna `publicada`; conflitos concorrentes nao geram um segundo gabarito vigente. Prova, questoes, gabarito e respostas oficiais ficam imutaveis apos a publicacao.

O MP-028 implementa listagem, criacao e remocao de vinculos entre provas publicadas e turmas. Provas pertencentes a um nucleo podem ser vinculadas apenas a turmas de escolas desse nucleo; provas pertencentes a uma escola somente podem ser vinculadas a turmas da propria escola. Turma, escola e nucleo devem estar ativos. Vinculos duplicados sao rejeitados e todas as alteracoes sao auditadas.

Arquivamento, finalizacao, substituicao de gabarito vigente e recorrection ainda nao possuem endpoint implementado.

### 6.1 Exemplo de criacao de prova

```json
{
  "nucleo_id": "uuid",
  "escola_id": null,
  "modelo_cartao_id": "uuid",
  "codigo": "SIMULADO-MAT-01",
  "titulo": "Simulado de Matematica - Nivel 1",
  "tipo": "simulado",
  "nivel": "6o e 7o anos",
  "ano_referencia": 2026,
  "quantidade_questoes": 20,
  "quantidade_alternativas": 5,
  "alternativas": ["A", "B", "C", "D", "E"]
}
```

### 6.2 Exemplo de criacao de questao

```json
{
  "numero": 1,
  "codigo": "MAT-001",
  "peso_padrao": 1
}
```

### 6.3 Criar versao de gabarito em rascunho

`POST /provas/{id}/gabaritos` nao recebe payload funcional. A versao e calculada sequencialmente pelo backend e sempre nasce como `rascunho`.

### 6.4 Preencher resposta oficial

`PUT /provas/{id}/gabaritos/{gabaritoId}/respostas/{questaoId}`

```json
{
  "alternativa_correta": "B",
  "anulada": false,
  "peso": 1
}
```

Questao anulada:

```json
{
  "alternativa_correta": null,
  "anulada": true,
  "peso": 1
}
```

O `PUT` cria ou substitui idempotentemente a resposta da questao no mesmo gabarito. O endpoint de validacao informa quantidade esperada, questoes ativas, respostas registradas, numeros sem resposta e problemas que impedirao a publicacao futura.

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
| GET | `/provas/{id}/resultados` | Conforme escopo |
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

## 13. Contrato planejado pela R1

Esta secao registra recursos necessarios ao mockup e a modelagem MariaDB. Os
endpoints abaixo sao **planejados** e nao foram implementados pela R1.

### 13.1 Contexto, equipe e preferencias

| Metodo | Endpoint planejado | Finalidade |
|---|---|---|
| GET | `/me/contextos` | Listar contextos autorizados para o painel |
| GET/PATCH | `/me/preferencias` | Consultar ou alterar tema e acessibilidade |
| GET/PATCH | `/me/notificacoes` | Consultar ou alterar preferencias de notificacao |
| GET | `/escolas/{id}/equipe` | Listar equipe, cargos e vinculos |
| POST | `/escolas/{id}/equipe` | Criar ou convidar membro autorizado |
| PATCH | `/escolas/{id}/equipe/{usuarioId}` | Alterar lotacoes e vinculos |
| GET | `/cargos` | Consultar catalogo institucional |
| GET | `/disciplinas` | Consultar catalogo academico |
| GET | `/periodos-letivos` | Consultar periodos no escopo |
| GET | `/series-anos` | Consultar series/anos |

Cargo, perfil e permissao sao contratos distintos. Nenhum endpoint deve inferir
autorizacao apenas pelo cargo.

### 13.2 Alunos, responsaveis e temas

| Metodo | Endpoint planejado | Finalidade |
|---|---|---|
| GET/POST | `/alunos/{id}/responsaveis` | Consultar ou vincular responsavel |
| PATCH/DELETE | `/alunos/{id}/responsaveis/{vinculoId}` | Alterar ou encerrar vinculo |
| GET | `/temas-habilidades` | Consultar classificacoes por disciplina |
| POST | `/questoes/{id}/temas` | Vincular tema por usuario autorizado |

`DELETE` de vinculo significa encerramento logico, nunca exclusao do historico.

### 13.3 Painel e relatorios canonicos

| Metodo | Endpoint planejado | Finalidade |
|---|---|---|
| GET | `/dashboards/contexto-atual` | Snapshot para a composicao de `/painel` |
| GET | `/correcoes` | Listar progresso e alertas autorizados |
| GET | `/resultados/{id}` | Consultar resultado individual autorizado |
| GET | `/provas/{id}/relatorio` | Dados do relatorio por prova |
| GET | `/turmas/{turmaId}/provas/{provaId}/relatorio` | Dados do relatorio turma/prova |
| POST | `/relatorios` | Solicitar CSV ou PDF canonico |

PDF e CSV pertencem ao MVP para os relatorios canonicos; XLSX permanece em V2.
Solicitacao e download exigem permissao, escopo e auditoria.

### 13.4 Itens rejeitados ou adiados

- Nao criar endpoint publico de cadastro de usuario.
- Nao criar autenticacao gov.br sem decisao e integracao aprovadas.
- Nao expor dashboard autenticado de aluno no MVP.
- Nao criar agenda, faturamento, limites comerciais ou integracoes simuladas.

O mapa de telas e rotas web esta em
[15-mapa-rotas-web.md](15-mapa-rotas-web.md).

## 14. Versionamento e documentacao

- Mudancas incompativeis geram nova versao da API.
- OpenAPI deve ser mantido junto da implementacao a partir do inicio do backend.
- Campos novos devem ser preferencialmente opcionais para clientes antigos.
- O app deve informar sua versao; o backend pode exigir atualizacao minima por motivo de seguranca.

## 15. Convencoes de implementacao

### 15.1 Fluxo por camada

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

### 15.2 Contrato e erros

- Todo endpoint funcional novo deve usar `/api/v1`.
- O endpoint tecnico de verificacao esta disponivel em `/api/v1/health`.
- Sucesso e erro devem seguir os envelopes da secao 2 e incluir `meta.request_id`.
- O header `X-Request-ID` aceita UUID informado pelo cliente ou recebe um UUID gerado pelo backend.
- O `request_id` deve ser compartilhado com o contexto dos logs sem registrar payloads sensiveis.
- Codigos de erro estaveis usam `UPPER_SNAKE_CASE` e nao dependem da mensagem apresentada.
- Erros `403` indicam acao conhecida fora da permissao; `404` pode ocultar recurso fora do escopo; `409` representa conflito de estado ou integridade esperado.
- Nenhum endpoint deve criar formato de resposta proprio ou retornar excecao interna ao cliente.

### 15.3 Transicao da base tecnica

A transicao da base tecnica foi concluida no MP-007. O componente central de resposta, o health check e o tratamento global de excecoes seguem os envelopes da secao 2.

- nenhum endpoint deve reintroduzir o envelope temporario com `success`, `message` e `errors`;
- toda resposta de API testada deve possuir `meta.request_id` e o header `X-Request-ID`;
- excecoes internas devem ser registradas com correlacao e retornar somente mensagem generica ao cliente;
- qualquer mudanca futura no contrato deve atualizar testes e OpenAPI na mesma entrega.
