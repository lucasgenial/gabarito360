# Gabarito360 - Modelagem Relacional PostgreSQL

> **Documento legado:** a versao canonica e refinada desta modelagem esta em [06-modelagem-banco.md](06-modelagem-banco.md). Este arquivo foi preservado para nao quebrar referencias historicas.

## 1. Objetivo

Este documento define a modelagem relacional inicial do Gabarito360 para PostgreSQL. O modelo cobre organizacao, controle de acesso, provas, aplicacoes, leitura OMR, correcao, auditoria, arquivos e sincronizacao mobile.

A modelagem prioriza:

- integridade no banco, sem depender apenas da aplicacao;
- isolamento logico entre nucleos e escolas;
- historico de matriculas, gabaritos, leituras e resultados;
- confirmacao idempotente de leituras mobile;
- rastreabilidade de alteracoes manuais;
- consultas operacionais e dashboards;
- minimizacao e protecao de dados pessoais.

## 2. Convencoes PostgreSQL

### 2.1 Tipos e extensoes

- Chaves primarias: `uuid DEFAULT gen_random_uuid()`.
- Datas e horas: `timestamptz`, armazenadas com referencia UTC.
- Datas sem horario: `date`.
- E-mails: `citext`, com a extensao `citext`, ou `varchar(254)` normalizado pela aplicacao.
- Dados configuraveis e metricas OMR: `jsonb`.
- Percentuais e confiancas: `numeric(5,4)`, entre `0` e `1`.
- Notas e pontos: `numeric(12,4)`.
- Enderecos IP: `inet`.
- Hashes e checksums: `varchar(128)`.

Extensoes recomendadas:

```sql
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS citext;
```

### 2.2 Colunas padrao

Salvo indicacao contraria, tabelas de negocio possuem:

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `uuid` | PK, `DEFAULT gen_random_uuid()` |
| `created_at` | `timestamptz` | `NOT NULL DEFAULT now()` |
| `updated_at` | `timestamptz` | `NOT NULL DEFAULT now()` |

Cadastros que podem ser removidos logicamente tambem possuem:

| Campo | Tipo | Regra |
|---|---|---|
| `deleted_at` | `timestamptz` | `NULL`; exclusao logica |

`updated_at` deve ser atualizado pela aplicacao ou por trigger padronizado. Registros historicos imutaveis, como `auditorias`, nao possuem `updated_at`.

### 2.3 Politica de chaves estrangeiras

- `ON DELETE RESTRICT` e o padrao para entidades de negocio e historico.
- `ON DELETE CASCADE` e permitido apenas em tabelas associativas sem historico proprio.
- `ON DELETE SET NULL` e permitido para referencias a usuarios atores, preservando o evento mesmo apos anonimizacao.
- Nucleos, escolas, alunos, provas, aplicacoes, leituras e resultados nao devem ser apagados fisicamente pelo fluxo normal.

### 2.4 Status

Status devem usar `varchar` com `CHECK`, em vez de `ENUM` PostgreSQL, para facilitar migrations e evolucao controlada. Exemplos:

```sql
CHECK (status IN ('ativo', 'inativo'))
CHECK (status IN ('rascunho', 'publicada', 'em_aplicacao', 'finalizada', 'arquivada'))
```

### 2.5 Nomenclatura

- Tabelas e colunas em `snake_case`.
- PK: `id`.
- FK: `<tabela_singular>_id`.
- Indice unico: `uq_<tabela>_<campos>`.
- Indice comum: `idx_<tabela>_<campos>`.
- FK: `fk_<tabela>_<referencia>`.
- Check: `ck_<tabela>_<regra>`.

## 3. Decisoes estruturais

### 3.1 Cartao confirmado separado da tentativa OMR

`cartoes_resposta` representa o vinculo confirmado entre prova, aplicacao e aluno, preservando separadamente o codigo impresso externo e o codigo do sistema. `leituras_cartao` representa cada captura ou reprocessamento OMR.

### 3.2 Gabarito oficial versionado

`gabaritos_oficiais` representa o cabecalho de uma versao. `gabarito_respostas` armazena a resposta oficial de cada questao naquela versao. Uma aplicacao referencia uma versao especifica, e cada resultado registra a versao usada na correcao.

### 3.3 Resultado agregado e resultado por questao

`resultados` armazena totais e nota. `resultado_respostas` preserva, para cada questao, a resposta final, a resposta oficial e a pontuacao calculada. Isso permite relatorios historicos corretos mesmo apos uma recorrection.

### 3.4 Escopo de usuario

`usuario_perfis` associa um perfil ao usuario em escopo global, de nucleo ou de escola. Professores e aplicadores ainda precisam de vinculos operacionais em `aplicadores_turmas` ou `aplicacao_aplicadores`.

## 4. Diagrama textual

```text
nucleos 1 --- N escolas
nucleos 1 --- N provas
nucleos 1 --- N modelos_cartao

usuarios N --- N perfis                 via usuario_perfis
perfis N --- N permissoes               via perfil_permissoes
usuarios N --- N turmas                 via aplicadores_turmas

escolas 1 --- N turmas
escolas 1 --- N alunos
alunos N --- N turmas                   via matriculas_turmas

provas 1 --- N questoes
provas 1 --- N gabaritos_oficiais
gabaritos_oficiais 1 --- N gabarito_respostas
questoes 1 --- N gabarito_respostas
provas N --- N turmas                   via prova_turmas

provas 1 --- N aplicacoes
turmas 1 --- N aplicacoes
aplicacoes N --- N usuarios             via aplicacao_aplicadores
aplicacoes N --- N alunos               via aplicacao_alunos

aplicacoes 1 --- N cartoes_resposta
alunos 1 --- N cartoes_resposta
cartoes_resposta 1 --- N leituras_cartao
leituras_cartao 1 --- N respostas_detectadas
cartoes_resposta 1 --- N resultados
resultados 1 --- N resultado_respostas

arquivos 1 --- N leituras_cartao
usuarios 1 --- N auditorias
dispositivos_mobile 1 --- N logs_sincronizacao
```

## 5. Catalogo de tabelas

### 5.1 Tabelas principais solicitadas

`nucleos`, `escolas`, `usuarios`, `perfis`, `turmas`, `alunos`, `provas`, `questoes`, `gabaritos_oficiais`, `modelos_cartao`, `aplicacoes`, `aplicadores_turmas`, `leituras_cartao`, `respostas_detectadas`, `resultados`, `auditorias`, `arquivos` e `logs_sincronizacao`.

### 5.2 Tabelas associativas e de suporte necessarias

| Tabela | Necessidade |
|---|---|
| `permissoes` | Catalogar capacidades atomicas |
| `perfil_permissoes` | Associar perfis e permissoes |
| `usuario_perfis` | Definir perfil e escopo de cada usuario |
| `matriculas_turmas` | Preservar historico aluno-turma |
| `prova_turmas` | Autorizar provas para turmas |
| `gabarito_respostas` | Detalhar respostas de uma versao oficial |
| `aplicacao_aplicadores` | Autorizar aplicador em uma aplicacao especifica |
| `aplicacao_alunos` | Congelar alunos previstos e controlar pendencias |
| `cartoes_resposta` | Representar o cartao confirmado, separado das tentativas |
| `resultado_respostas` | Preservar correcao e pontuacao por questao |
| `dispositivos_mobile` | Identificar instalacoes do app para sincronizacao |

## 6. Organizacao e controle de acesso

### 6.1 `nucleos`

**Finalidade:** representar o nucleo de educacao responsavel por varias escolas.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `codigo` | `varchar(50)` | Nao | Codigo de negocio |
| `nome` | `varchar(180)` | Nao | Nome oficial |
| `municipio` | `varchar(120)` | Sim |  |
| `estado` | `char(2)` | Sim | UF em maiusculas |
| `email` | `citext` | Sim |  |
| `telefone` | `varchar(30)` | Sim |  |
| `status` | `varchar(20)` | Nao / `ativo` | `ativo`, `inativo` |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |
| `deleted_at` | `timestamptz` | Sim | Exclusao logica |

**Indices e restricoes:**

- `uq_nucleos_codigo`: unico em `lower(codigo)` onde `deleted_at IS NULL`.
- `idx_nucleos_status`: `(status)` onde `deleted_at IS NULL`.
- `ck_nucleos_estado`: `estado IS NULL OR estado ~ '^[A-Z]{2}$'`.

**Relacionamentos:** possui muitas `escolas`, `provas`, `modelos_cartao` e atribuicoes em `usuario_perfis`.

### 6.2 `escolas`

**Finalidade:** armazenar escolas pertencentes a um nucleo.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `nucleo_id` | `uuid` | Nao | FK `nucleos.id` |
| `codigo` | `varchar(50)` | Nao | Unico no nucleo |
| `nome` | `varchar(180)` | Nao |  |
| `municipio` | `varchar(120)` | Nao |  |
| `estado` | `char(2)` | Nao | UF |
| `endereco` | `jsonb` | Sim | Dados estruturados |
| `email` | `citext` | Sim |  |
| `telefone` | `varchar(30)` | Sim |  |
| `status` | `varchar(20)` | Nao / `ativo` | `ativo`, `inativo` |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |
| `deleted_at` | `timestamptz` | Sim | Exclusao logica |

**FKs:** `nucleo_id -> nucleos.id ON DELETE RESTRICT`.

**Indices e restricoes:**

- `uq_escolas_nucleo_codigo`: unico em `(nucleo_id, lower(codigo))` onde `deleted_at IS NULL`.
- `idx_escolas_nucleo_status`: `(nucleo_id, status)` onde `deleted_at IS NULL`.
- `idx_escolas_nome`: `(nucleo_id, nome)` para busca administrativa.

**Relacionamentos:** pertence a um nucleo; possui turmas, alunos, aplicacoes e perfis de usuario no escopo escolar.

### 6.3 `usuarios`

**Finalidade:** armazenar identidades autenticaveis.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `nome` | `varchar(180)` | Nao |  |
| `email` | `citext` | Nao | Unico |
| `documento` | `varchar(30)` | Sim | CPF/matricula, protegido |
| `telefone` | `varchar(30)` | Sim |  |
| `password` | `varchar(255)` | Nao | Hash, nunca senha reversivel |
| `status` | `varchar(20)` | Nao / `ativo` | `ativo`, `inativo`, `bloqueado` |
| `email_verified_at` | `timestamptz` | Sim |  |
| `ultimo_acesso_at` | `timestamptz` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |
| `deleted_at` | `timestamptz` | Sim | Exclusao logica ou anonimizacao |

**Indices e restricoes:**

- `uq_usuarios_email`: unico em `email` onde `deleted_at IS NULL`.
- `idx_usuarios_status`: `(status)` onde `deleted_at IS NULL`.
- `idx_usuarios_documento`: `(documento)` onde `documento IS NOT NULL AND deleted_at IS NULL`; tornar unico apenas se a regra for aprovada.
- Dados sensiveis devem ser minimizados e, quando necessario, protegidos em nivel de aplicacao.

**Relacionamentos:** recebe perfis, vinculos de turma, aplicacoes, leituras, auditorias e dispositivos mobile.

### 6.4 `perfis`

**Finalidade:** catalogar papeis como administrador, gestor, responsavel escolar, professor, aplicador, consulta e suporte.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `codigo` | `varchar(60)` | Nao | Ex.: `gestor_nucleo` |
| `nome` | `varchar(120)` | Nao |  |
| `descricao` | `text` | Sim |  |
| `escopo_permitido` | `varchar(20)` | Nao | `global`, `nucleo`, `escola`, `operacional` |
| `sistema` | `boolean` | Nao / `false` | Perfil protegido |
| `status` | `varchar(20)` | Nao / `ativo` |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- `uq_perfis_codigo`: unico em `lower(codigo)`.
- `idx_perfis_status`: `(status)`.

### 6.5 `permissoes`

**Finalidade:** catalogar capacidades atomicas usadas pelas policies.

Campos: `id uuid PK`, `codigo varchar(100) NOT NULL`, `descricao text`, `created_at`, `updated_at`.

**Indices:** `uq_permissoes_codigo` unico em `lower(codigo)`.

### 6.6 `perfil_permissoes`

**Finalidade:** associar permissoes aos perfis.

| Campo | Tipo | Chave |
|---|---|---|
| `perfil_id` | `uuid` | PK composta, FK `perfis.id ON DELETE CASCADE` |
| `permissao_id` | `uuid` | PK composta, FK `permissoes.id ON DELETE CASCADE` |
| `created_at` | `timestamptz` | `NOT NULL DEFAULT now()` |

**PK:** `(perfil_id, permissao_id)`.

### 6.7 `usuario_perfis`

**Finalidade:** atribuir perfil a um usuario em escopo global, de nucleo ou de escola.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `usuario_id` | `uuid` | Nao | FK `usuarios.id` |
| `perfil_id` | `uuid` | Nao | FK `perfis.id` |
| `nucleo_id` | `uuid` | Sim | FK `nucleos.id` |
| `escola_id` | `uuid` | Sim | FK `escolas.id` |
| `concedido_por` | `uuid` | Sim | FK `usuarios.id`, ator |
| `inicio_at` | `timestamptz` | Nao / `now()` |  |
| `fim_at` | `timestamptz` | Sim | Revogacao do vinculo |
| `created_at` | `timestamptz` | Nao / `now()` |  |

**Restricoes e indices:**

- `ck_usuario_perfis_escopo`: `num_nonnulls(nucleo_id, escola_id) <= 1`.
- Unico com `NULLS NOT DISTINCT` em `(usuario_id, perfil_id, nucleo_id, escola_id, fim_at)` ou indice parcial equivalente para vinculos ativos.
- `idx_usuario_perfis_usuario_ativo`: `(usuario_id)` onde `fim_at IS NULL`.
- `idx_usuario_perfis_nucleo`: `(nucleo_id, perfil_id)` onde `fim_at IS NULL`.
- `idx_usuario_perfis_escola`: `(escola_id, perfil_id)` onde `fim_at IS NULL`.

## 7. Estrutura academica

### 7.1 `turmas`

**Finalidade:** representar turmas de uma escola em um ano letivo.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `escola_id` | `uuid` | Nao | FK `escolas.id` |
| `codigo` | `varchar(50)` | Nao | Codigo no ano letivo |
| `nome` | `varchar(120)` | Nao |  |
| `serie_ano` | `varchar(60)` | Nao |  |
| `turno` | `varchar(30)` | Sim | `matutino`, `vespertino`, `noturno`, `integral` |
| `ano_letivo` | `smallint` | Nao | Entre limites plausiveis |
| `status` | `varchar(20)` | Nao / `ativo` |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |
| `deleted_at` | `timestamptz` | Sim |  |

**Indices e restricoes:**

- `uq_turmas_escola_ano_codigo`: unico em `(escola_id, ano_letivo, lower(codigo))` onde `deleted_at IS NULL`.
- `idx_turmas_escola_ano_status`: `(escola_id, ano_letivo, status)`.
- `ck_turmas_ano_letivo`: limite operacional configurado, por exemplo entre `2000` e `2100`.

### 7.2 `alunos`

**Finalidade:** armazenar alunos no escopo da escola.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `escola_id` | `uuid` | Nao | FK `escolas.id` |
| `matricula` | `varchar(80)` | Nao | Unica na escola |
| `codigo_interno` | `varchar(80)` | Sim | Integracao futura |
| `nome` | `varchar(180)` | Nao |  |
| `data_nascimento` | `date` | Sim | Dado pessoal |
| `documento` | `varchar(30)` | Sim | Coletar somente se necessario |
| `status` | `varchar(20)` | Nao / `ativo` | `ativo`, `inativo` |
| `observacoes` | `text` | Sim | Evitar dados sensiveis desnecessarios |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |
| `deleted_at` | `timestamptz` | Sim |  |

**Indices e restricoes:**

- Regra do MVP: `uq_alunos_escola_matricula` unico em `(escola_id, lower(matricula))` onde `deleted_at IS NULL`.
- `idx_alunos_escola_nome`: `(escola_id, nome)` onde `deleted_at IS NULL`.
- `idx_alunos_escola_status`: `(escola_id, status)`.
- `documento` nao deve ser unico ate a politica de coleta ser aprovada.

### 7.3 `matriculas_turmas`

**Finalidade:** preservar o historico de vinculo do aluno com turmas.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `aluno_id` | `uuid` | Nao | FK `alunos.id` |
| `turma_id` | `uuid` | Nao | FK `turmas.id` |
| `ano_letivo` | `smallint` | Nao | Snapshot igual ao da turma |
| `numero_chamada` | `varchar(20)` | Sim |  |
| `status` | `varchar(20)` | Nao / `ativa` | `ativa`, `transferida`, `encerrada` |
| `inicio_em` | `date` | Nao |  |
| `fim_em` | `date` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- `uq_matriculas_aluno_ano_ativa`: unico em `(aluno_id, ano_letivo)` onde `status = 'ativa'`.
- `idx_matriculas_turma_status`: `(turma_id, status)`.
- Trigger valida que aluno e turma pertencem a mesma escola e que `ano_letivo` corresponde a turma.

### 7.4 `aplicadores_turmas`

**Finalidade:** vincular professores e aplicadores as turmas que podem visualizar ou operar.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `turma_id` | `uuid` | Nao | FK `turmas.id` |
| `usuario_id` | `uuid` | Nao | FK `usuarios.id` |
| `papel` | `varchar(30)` | Nao | `professor`, `aplicador`, `responsavel` |
| `inicio_em` | `date` | Nao |  |
| `fim_em` | `date` | Sim | Vinculo ativo quando nulo |
| `vinculado_por` | `uuid` | Sim | FK `usuarios.id` |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- `uq_aplicadores_turmas_ativo`: unico em `(turma_id, usuario_id, papel)` onde `fim_em IS NULL`.
- `idx_aplicadores_turmas_usuario_ativo`: `(usuario_id, turma_id)` onde `fim_em IS NULL`.
- `idx_aplicadores_turmas_turma_ativo`: `(turma_id)` onde `fim_em IS NULL`.

## 8. Provas, questoes, gabaritos e modelos

### 8.1 `modelos_cartao`

**Finalidade:** versionar a configuracao geometrica e os limiares do OMR.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `nucleo_id` | `uuid` | Sim | FK `nucleos.id`; nulo para modelo global |
| `nome` | `varchar(120)` | Nao |  |
| `versao` | `integer` | Nao | Maior que zero |
| `quantidade_questoes` | `smallint` | Nao |  |
| `quantidade_alternativas` | `smallint` | Nao |  |
| `alternativas` | `jsonb` | Nao | Ex.: `["A","B","C","D","E"]` |
| `tipo_codigo` | `varchar(30)` | Nao | `qr_code`, `codigo_barras`, `ocr_texto`, `sem_codigo` |
| `origem_codigo` | `varchar(30)` | Nao | `externo`, `sistema_afixado`, `nenhum`; distingue a origem semantica conforme ADR-D010 |
| `configuracao_omr` | `jsonb` | Nao | Canvas, regioes, marcadores, grade, limiares e normalizacao explicita do codigo impresso |
| `artefato_checksum_sha256` | `char(64)` | Sim | Obrigatorio para homologar; referencia imutavel do artefato de impressao |
| `status` | `varchar(20)` | Nao / `rascunho` | `rascunho`, `homologado`, `inativo` |
| `criado_por` | `uuid` | Sim | FK `usuarios.id` |
| `homologado_por` | `uuid` | Sim | FK `usuarios.id` |
| `homologado_at` | `timestamptz` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- Unico, sem diferenciar maiusculas/minusculas e com `NULLS NOT DISTINCT`, em `(nucleo_id, lower(nome), versao)`.
- `idx_modelos_cartao_nucleo_status`: `(nucleo_id, status)`.
- Checks para versao e quantidades positivas, JSON valido, semantica coerente entre tipo/origem do codigo, checksum SHA-256 e metadados de homologacao.
- Regioes devem permanecer dentro do canvas; marcadores, grade, centros, normalizacao e limiares sao validados antes da homologacao.
- Toda versao homologada e inativa e imutavel no banco. Uma versao homologada pode somente ser inativada; qualquer alteracao exige nova versao.

### 8.2 `provas`

**Finalidade:** definir uma avaliacao objetiva.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `nucleo_id` | `uuid` | Sim | FK `nucleos.id` |
| `escola_id` | `uuid` | Sim | FK `escolas.id` |
| `modelo_cartao_id` | `uuid` | Nao | FK `modelos_cartao.id` |
| `codigo` | `varchar(60)` | Nao | Codigo no proprietario |
| `titulo` | `varchar(180)` | Nao |  |
| `descricao` | `text` | Sim |  |
| `tipo` | `varchar(50)` | Nao | Ex.: `simulado`, `diagnostico` |
| `nivel` | `varchar(80)` | Sim |  |
| `ano_referencia` | `smallint` | Sim |  |
| `quantidade_questoes` | `smallint` | Nao | Maior que zero |
| `quantidade_alternativas` | `smallint` | Nao |  |
| `alternativas` | `jsonb` | Nao |  |
| `status` | `varchar(30)` | Nao / `rascunho` | Fluxo da prova |
| `criado_por` | `uuid` | Sim | FK `usuarios.id` |
| `publicada_at` | `timestamptz` | Sim |  |
| `finalizada_at` | `timestamptz` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |
| `deleted_at` | `timestamptz` | Sim |  |

**Restricoes e indices:**

- `ck_provas_proprietario`: `num_nonnulls(nucleo_id, escola_id) = 1`.
- `uq_provas_nucleo_codigo`: unico em `(nucleo_id, lower(codigo))` para provas de nucleo nao excluidas.
- `uq_provas_escola_codigo`: unico em `(escola_id, lower(codigo))` para provas de escola nao excluidas.
- `idx_provas_nucleo_status`: `(nucleo_id, status)`.
- `idx_provas_escola_status`: `(escola_id, status)`.
- Checks para quantidades positivas e alternativas em formato de array.
- Trigger valida compatibilidade entre quantidades da prova e do modelo.

### 8.3 `questoes`

**Finalidade:** representar as questoes da prova para gabarito, correcao e analise.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `prova_id` | `uuid` | Nao | FK `provas.id` |
| `numero` | `smallint` | Nao | Ordem na prova |
| `codigo` | `varchar(50)` | Sim | Codigo externo opcional |
| `peso_padrao` | `numeric(10,4)` | Nao / `1` | Maior ou igual a zero |
| `status` | `varchar(20)` | Nao / `ativa` | `ativa`, `inativa` |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- `uq_questoes_prova_numero`: unico em `(prova_id, numero)`.
- `uq_questoes_id_prova`: unico em `(id, prova_id)`, usado por FKs compostas.
- `idx_questoes_prova_status`: `(prova_id, status)`.
- Check `numero > 0` e `peso_padrao >= 0`.

### 8.4 `gabaritos_oficiais`

**Finalidade:** representar uma versao imutavel do gabarito oficial da prova.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `prova_id` | `uuid` | Nao | FK `provas.id` |
| `versao` | `integer` | Nao | Sequencial por prova |
| `status` | `varchar(20)` | Nao / `rascunho` | `rascunho`, `vigente`, `substituido` |
| `justificativa` | `text` | Sim | Obrigatoria em substituicao |
| `criado_por` | `uuid` | Sim | FK `usuarios.id` |
| `publicado_por` | `uuid` | Sim | FK `usuarios.id` |
| `publicado_at` | `timestamptz` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` | Somente enquanto rascunho |

**Indices e restricoes:**

- `uq_gabaritos_oficiais_prova_versao`: unico em `(prova_id, versao)`.
- `uq_gabaritos_oficiais_vigente`: unico em `(prova_id)` onde `status = 'vigente'`.
- `uq_gabaritos_oficiais_id_prova`: unico em `(id, prova_id)`, usado por FKs compostas.
- `idx_gabaritos_oficiais_prova_status`: `(prova_id, status)`.

### 8.5 `gabarito_respostas`

**Finalidade:** armazenar a resposta oficial de cada questao em uma versao do gabarito.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `prova_id` | `uuid` | Nao | Parte das FKs compostas |
| `gabarito_oficial_id` | `uuid` | Nao | FK composta para gabarito/prova |
| `questao_id` | `uuid` | Nao | FK composta para questao/prova |
| `alternativa_correta` | `varchar(10)` | Sim | Nula quando anulada |
| `anulada` | `boolean` | Nao / `false` |  |
| `peso` | `numeric(10,4)` | Nao / `1` |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` | Enquanto rascunho |

**FKs compostas:**

- `(gabarito_oficial_id, prova_id) -> gabaritos_oficiais(id, prova_id)`.
- `(questao_id, prova_id) -> questoes(id, prova_id)`.

**Indices e restricoes:**

- `uq_gabarito_respostas_gabarito_questao`: unico em `(gabarito_oficial_id, questao_id)`.
- `idx_gabarito_respostas_questao`: `(questao_id)`.
- Check: `(anulada AND alternativa_correta IS NULL) OR (NOT anulada AND alternativa_correta IS NOT NULL)`.
- Check `peso >= 0`.

### 8.6 `prova_turmas`

**Finalidade:** autorizar uma prova para uma turma.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `prova_id` | `uuid` | Nao | FK `provas.id` |
| `turma_id` | `uuid` | Nao | FK `turmas.id` |
| `data_prevista` | `date` | Sim |  |
| `vinculado_por` | `uuid` | Sim | FK `usuarios.id` |
| `created_at` | `timestamptz` | Nao / `now()` |  |

**Indices:** unico em `(prova_id, turma_id)`; indice em `(turma_id, data_prevista)`.

## 9. Aplicacoes e participantes

### 9.1 `aplicacoes`

**Finalidade:** controlar a execucao de uma prova em uma turma.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `prova_id` | `uuid` | Nao | FK `provas.id` |
| `escola_id` | `uuid` | Nao | FK `escolas.id` |
| `turma_id` | `uuid` | Nao | FK `turmas.id` |
| `gabarito_oficial_id` | `uuid` | Nao | FK composta com `prova_id` |
| `modelo_cartao_id` | `uuid` | Nao | FK `modelos_cartao.id` |
| `codigo` | `varchar(80)` | Nao | Codigo operacional |
| `data_prevista` | `date` | Sim |  |
| `status` | `varchar(30)` | Nao / `aguardando` | `aguardando`, `em_andamento`, `finalizada`, `cancelada` |
| `iniciada_por` | `uuid` | Sim | FK `usuarios.id` |
| `iniciada_at` | `timestamptz` | Sim |  |
| `finalizada_por` | `uuid` | Sim | FK `usuarios.id` |
| `finalizada_at` | `timestamptz` | Sim |  |
| `reaberta_por` | `uuid` | Sim | FK `usuarios.id` |
| `reaberta_at` | `timestamptz` | Sim |  |
| `motivo_reabertura` | `text` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**FKs e integridade:**

- `(gabarito_oficial_id, prova_id) -> gabaritos_oficiais(id, prova_id)`.
- `turma_id -> turmas.id`; trigger valida que a turma pertence a `escola_id`.
- Trigger valida que a prova foi autorizada em `prova_turmas`.
- Trigger bloqueia inicio sem gabarito vigente e modelo homologado.

**Indices:**

- `uq_aplicacoes_escola_codigo`: unico em `(escola_id, lower(codigo))`.
- `uq_aplicacoes_id_prova`: unico em `(id, prova_id)`, usado por FK composta.
- `idx_aplicacoes_prova_status`: `(prova_id, status)`.
- `idx_aplicacoes_turma_status`: `(turma_id, status)`.
- `idx_aplicacoes_escola_status`: `(escola_id, status)`.
- `idx_aplicacoes_data_status`: `(data_prevista, status)`.

### 9.2 `aplicacao_aplicadores`

**Finalidade:** autorizar usuarios para uma aplicacao especifica, inclusive substituicoes pontuais.

| Campo | Tipo | Chave ou regra |
|---|---|---|
| `aplicacao_id` | `uuid` | PK composta, FK `aplicacoes.id` |
| `usuario_id` | `uuid` | PK composta, FK `usuarios.id` |
| `papel` | `varchar(30)` | `aplicador`, `responsavel` |
| `vinculado_por` | `uuid` | FK `usuarios.id`, nulo permitido |
| `created_at` | `timestamptz` | `NOT NULL DEFAULT now()` |

**Indices:** PK `(aplicacao_id, usuario_id)` e indice `(usuario_id, aplicacao_id)`.

### 9.3 `aplicacao_alunos`

**Finalidade:** congelar alunos previstos e controlar pendencias, presenca e resultado vigente.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `aplicacao_id` | `uuid` | Nao | FK `aplicacoes.id` |
| `aluno_id` | `uuid` | Nao | FK `alunos.id` |
| `status` | `varchar(30)` | Nao / `pendente` | `pendente`, `lido`, `ausente`, `cancelado` |
| `presenca` | `varchar(20)` | Sim | `presente`, `ausente`, `nao_informada` |
| `resultado_vigente_id` | `uuid` | Sim | FK adicionada apos `resultados` |
| `observacoes` | `text` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- `uq_aplicacao_alunos_aplicacao_aluno`: unico em `(aplicacao_id, aluno_id)`.
- `idx_aplicacao_alunos_status`: `(aplicacao_id, status)`.
- `idx_aplicacao_alunos_aluno`: `(aluno_id, aplicacao_id)`.
- Trigger valida que o aluno pertence a turma da aplicacao no momento do snapshot.

## 10. Cartoes, leituras OMR e respostas

### 10.1 `cartoes_resposta`

**Finalidade:** representar o vinculo confirmado entre prova, aplicacao e aluno com identificadores externo e interno separados.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `prova_id` | `uuid` | Nao | Parte da FK composta |
| `aplicacao_id` | `uuid` | Nao | FK composta para aplicacao/prova |
| `aluno_id` | `uuid` | Nao | FK com aluno previsto |
| `codigo_impresso` | `varchar(180)` | Sim | Codigo externo existente no papel |
| `codigo_impresso_normalizado` | `varchar(180)` | Sim | Codigo externo normalizado pelo modelo |
| `codigo_sistema` | `varchar(40)` | Sim | Codigo operacional adicional gerado pelo app ou backend |
| `codigo_sistema_afixado` | `boolean` | Nao / `false` | Informa se o codigo do sistema foi afixado ao papel |
| `motivo_sem_codigo_impresso` | `varchar(80)` | Sim | Motivo quando nao existe codigo impresso |
| `status` | `varchar(30)` | Nao / `confirmado` | `confirmado`, `substituido`, `cancelado` |
| `confirmado_por` | `uuid` | Sim | FK `usuarios.id` |
| `confirmado_at` | `timestamptz` | Nao / `now()` |  |
| `cancelado_por` | `uuid` | Sim | FK `usuarios.id` |
| `cancelado_at` | `timestamptz` | Sim |  |
| `motivo_cancelamento` | `text` | Sim | Obrigatorio ao cancelar |
| `substitui_cartao_id` | `uuid` | Sim | FK `cartoes_resposta.id` |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**FKs compostas:**

- `(aplicacao_id, prova_id) -> aplicacoes(id, prova_id)`.
- `(aplicacao_id, aluno_id) -> aplicacao_alunos(aplicacao_id, aluno_id)`.

**Indices criticos:**

```sql
CREATE UNIQUE INDEX uq_cartoes_resposta_prova_aluno_confirmado
ON cartoes_resposta (prova_id, aluno_id)
WHERE status = 'confirmado';

CREATE UNIQUE INDEX uq_cartoes_resposta_prova_codigo_impresso_confirmado
ON cartoes_resposta (prova_id, codigo_impresso_normalizado)
WHERE status = 'confirmado' AND codigo_impresso_normalizado IS NOT NULL;

CREATE UNIQUE INDEX uq_cartoes_resposta_codigo_sistema
ON cartoes_resposta (codigo_sistema)
WHERE codigo_sistema IS NOT NULL;
```

Indices adicionais: `(aplicacao_id, status)`, `(aluno_id, prova_id)` e `(substitui_cartao_id)`.

Esses indices implementam as regras de um cartao valido por aluno/prova, codigo impresso unico dentro da prova quando informado e codigo do sistema unico globalmente quando utilizado.

### 10.2 `arquivos`

**Finalidade:** catalogar imagens, planilhas, relatorios e outros objetos armazenados fora do banco.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `disco` | `varchar(40)` | Nao | Ex.: `s3`, `minio`, `local` |
| `caminho` | `varchar(700)` | Nao | Chave interna do objeto |
| `nome_original` | `varchar(255)` | Sim | Nao usar como caminho |
| `mime_type` | `varchar(120)` | Nao | Validado pelo conteudo |
| `tamanho_bytes` | `bigint` | Nao | Maior ou igual a zero |
| `checksum_sha256` | `char(64)` | Nao | Integridade |
| `classificacao` | `varchar(40)` | Nao | `cartao_original`, `cartao_processado`, `importacao`, `relatorio`, `modelo` |
| `criptografado` | `boolean` | Nao / `false` |  |
| `retencao_ate` | `timestamptz` | Sim | Politica LGPD |
| `criado_por` | `uuid` | Sim | FK `usuarios.id` |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `deleted_at` | `timestamptz` | Sim | Remocao logica do catalogo |

**Indices e restricoes:**

- `uq_arquivos_disco_caminho`: unico em `(disco, caminho)`.
- `idx_arquivos_checksum`: `(checksum_sha256)`.
- `idx_arquivos_classificacao_retencao`: `(classificacao, retencao_ate)`.
- Check `tamanho_bytes >= 0`.
- Download deve ocorrer por autorizacao e URL temporaria; `caminho` nao deve ser exposto ao cliente.

### 10.3 `leituras_cartao`

**Finalidade:** manter cada captura, tentativa ou reprocessamento OMR sem apagar historico.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `prova_id` | `uuid` | Nao | FK `provas.id` |
| `aplicacao_id` | `uuid` | Nao | FK composta para aplicacao/prova |
| `cartao_resposta_id` | `uuid` | Sim | FK `cartoes_resposta.id`; preenchido na confirmacao |
| `modelo_cartao_id` | `uuid` | Nao | FK `modelos_cartao.id` |
| `operacao_mobile_id` | `uuid` | Sim | Idempotencia gerada no dispositivo |
| `leitura_origem_id` | `uuid` | Sim | FK para leitura reprocessada |
| `imagem_original_id` | `uuid` | Sim | FK `arquivos.id` |
| `imagem_processada_id` | `uuid` | Sim | FK `arquivos.id` |
| `codigo_sistema_proposto` | `varchar(40)` | Sim | Codigo adicional proposto pelo app ou backend |
| `codigo_impresso_detectado` | `varchar(180)` | Sim | Valor externo detectado na imagem |
| `confianca_codigo_impresso` | `numeric(5,4)` | Sim | Entre 0 e 1 |
| `confianca_geral` | `numeric(5,4)` | Sim | Entre 0 e 1 |
| `status` | `varchar(30)` | Nao / `recebida` | `recebida`, `processando`, `sucesso`, `parcial`, `falha`, `confirmada`, `cancelada` |
| `requer_revisao` | `boolean` | Nao / `false` |  |
| `alertas_aceitos` | `boolean` | Nao / `false` | Aceite explicito |
| `processado_em` | `varchar(20)` | Nao | `app`, `backend`, `hibrido` |
| `metricas_qualidade` | `jsonb` | Sim | Nitidez, contraste, alinhamento |
| `metadados_captura` | `jsonb` | Sim | Dispositivo e dados permitidos |
| `capturada_at` | `timestamptz` | Nao | Hora informada pelo dispositivo |
| `processada_at` | `timestamptz` | Sim |  |
| `confirmada_at` | `timestamptz` | Sim |  |
| `criado_por` | `uuid` | Sim | FK `usuarios.id` |
| `confirmada_por` | `uuid` | Sim | FK `usuarios.id` |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- `uq_leituras_cartao_operacao_mobile`: unico em `operacao_mobile_id` onde nao nulo.
- `idx_leituras_cartao_aplicacao_data`: `(aplicacao_id, created_at DESC)`.
- `idx_leituras_cartao_cartao_data`: `(cartao_resposta_id, created_at DESC)`.
- `idx_leituras_cartao_status`: `(status, created_at)` para workers e suporte.
- `idx_leituras_cartao_revisao`: `(aplicacao_id, requer_revisao)` onde `requer_revisao`.
- Checks de confianca entre `0` e `1`.
- `(aplicacao_id, prova_id) -> aplicacoes(id, prova_id)`.
- Trigger valida que o modelo da leitura corresponde ao modelo congelado na aplicacao.

### 10.4 `respostas_detectadas`

**Finalidade:** registrar deteccao original, revisao humana e resposta final por questao.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `leitura_cartao_id` | `uuid` | Nao | FK `leituras_cartao.id` |
| `prova_id` | `uuid` | Nao | Parte da FK composta |
| `questao_id` | `uuid` | Nao | FK composta para questao/prova |
| `alternativa_detectada` | `varchar(10)` | Sim | Nula para branco/dupla/falha |
| `alternativa_final` | `varchar(10)` | Sim | Resposta usada na correcao |
| `tipo_deteccao` | `varchar(30)` | Nao | `marcada`, `branca`, `dupla`, `duvidosa`, `falha` |
| `confianca` | `numeric(5,4)` | Sim | Entre 0 e 1 |
| `preenchimentos` | `jsonb` | Sim | Metricas por alternativa |
| `alterada_manualmente` | `boolean` | Nao / `false` |  |
| `motivo_alteracao` | `text` | Sim | Obrigatorio com 10 a 500 caracteres quando alterada manualmente |
| `alterada_por` | `uuid` | Sim | FK `usuarios.id` |
| `alterada_at` | `timestamptz` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` | Ate confirmacao |

**Indices e restricoes:**

- `uq_respostas_detectadas_leitura_questao`: unico em `(leitura_cartao_id, questao_id)`.
- `(questao_id, prova_id) -> questoes(id, prova_id)`.
- `idx_respostas_detectadas_questao_tipo`: `(questao_id, tipo_deteccao)`.
- `idx_respostas_detectadas_alteradas`: `(leitura_cartao_id)` onde `alterada_manualmente`.
- Check de confianca entre `0` e `1`.
- Check exige `alterada_por` e `alterada_at` quando `alterada_manualmente = true`.

## 11. Correcao e resultados

### 11.1 `resultados`

**Finalidade:** armazenar cada versao de correcao agregada de um cartao.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `cartao_resposta_id` | `uuid` | Nao | FK `cartoes_resposta.id` |
| `leitura_cartao_id` | `uuid` | Nao | FK `leituras_cartao.id` confirmada |
| `prova_id` | `uuid` | Nao | FK `provas.id` |
| `aplicacao_id` | `uuid` | Nao | FK `aplicacoes.id` |
| `aluno_id` | `uuid` | Nao | FK `alunos.id` |
| `gabarito_oficial_id` | `uuid` | Nao | FK composta com `prova_id` |
| `versao` | `integer` | Nao | Sequencial por cartao |
| `acertos` | `smallint` | Nao / `0` |  |
| `erros` | `smallint` | Nao / `0` |  |
| `brancos` | `smallint` | Nao / `0` |  |
| `duplas` | `smallint` | Nao / `0` |  |
| `anuladas` | `smallint` | Nao / `0` |  |
| `pontos_obtidos` | `numeric(12,4)` | Nao / `0` |  |
| `pontos_possiveis` | `numeric(12,4)` | Nao / `0` |  |
| `nota` | `numeric(12,4)` | Sim | Conforme politica |
| `status` | `varchar(20)` | Nao / `valido` | `valido`, `substituido`, `cancelado` |
| `vigente` | `boolean` | Nao / `true` | Uma versao vigente por cartao |
| `calculado_por` | `uuid` | Sim | FK `usuarios.id`; nulo para worker |
| `calculado_at` | `timestamptz` | Nao / `now()` |  |
| `motivo_recalculo` | `text` | Sim | Obrigatorio em recorrection |
| `created_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- `uq_resultados_cartao_versao`: unico em `(cartao_resposta_id, versao)`.
- `uq_resultados_cartao_vigente`: unico em `(cartao_resposta_id)` onde `vigente = true`.
- `idx_resultados_aplicacao_vigente`: `(aplicacao_id, vigente)` incluindo `nota`, `acertos`.
- `idx_resultados_prova_vigente`: `(prova_id, vigente)`.
- `idx_resultados_aluno_prova`: `(aluno_id, prova_id, vigente)`.
- `(gabarito_oficial_id, prova_id) -> gabaritos_oficiais(id, prova_id)`.
- Checks impedem totais e pontos negativos.
- Trigger valida coerencia entre cartao, leitura, prova, aplicacao e aluno.

### 11.2 `resultado_respostas`

**Finalidade:** preservar a correcao e a pontuacao de cada questao em uma versao de resultado.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `resultado_id` | `uuid` | Nao | FK `resultados.id` |
| `questao_id` | `uuid` | Nao | FK `questoes.id` |
| `alternativa_final` | `varchar(10)` | Sim | Snapshot da resposta confirmada |
| `alternativa_correta` | `varchar(10)` | Sim | Snapshot do gabarito usado |
| `situacao` | `varchar(20)` | Nao | `correta`, `incorreta`, `branca`, `dupla`, `anulada` |
| `peso` | `numeric(10,4)` | Nao | Snapshot |
| `pontos_obtidos` | `numeric(10,4)` | Nao |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- `uq_resultado_respostas_resultado_questao`: unico em `(resultado_id, questao_id)`.
- `idx_resultado_respostas_questao_situacao`: `(questao_id, situacao)`.
- Checks para pesos e pontos nao negativos.

## 12. Auditoria, dispositivos e sincronizacao

### 12.1 `auditorias`

**Finalidade:** manter trilha imutavel de eventos de negocio sensiveis.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `request_id` | `uuid` | Sim | Correlacao da requisicao |
| `usuario_id` | `uuid` | Sim | FK `usuarios.id ON DELETE SET NULL` |
| `nucleo_id` | `uuid` | Sim | FK `nucleos.id`; escopo para consulta |
| `escola_id` | `uuid` | Sim | FK `escolas.id`; escopo para consulta |
| `acao` | `varchar(100)` | Nao | Ex.: `leitura.confirmada` |
| `entidade_tipo` | `varchar(100)` | Nao | Nome logico |
| `entidade_id` | `uuid` | Sim | Referencia polimorfica, sem FK |
| `dados_anteriores` | `jsonb` | Sim | Dados permitidos antes |
| `dados_novos` | `jsonb` | Sim | Dados permitidos depois |
| `metadados` | `jsonb` | Sim | Motivo, dispositivo, contexto |
| `ip` | `inet` | Sim |  |
| `user_agent` | `text` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` | Imutavel |

**Indices e restricoes:**

- `idx_auditorias_entidade`: `(entidade_tipo, entidade_id, created_at DESC)`.
- `idx_auditorias_usuario_data`: `(usuario_id, created_at DESC)`.
- `idx_auditorias_acao_data`: `(acao, created_at DESC)`.
- `idx_auditorias_nucleo_data`: `(nucleo_id, created_at DESC)`.
- `idx_auditorias_escola_data`: `(escola_id, created_at DESC)`.
- Revogar `UPDATE` e `DELETE` para papeis operacionais.
- Avaliar particionamento mensal por `created_at` quando o volume justificar.

### 12.2 `dispositivos_mobile`

**Finalidade:** identificar instalacoes autorizadas do aplicativo Android.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `usuario_id` | `uuid` | Nao | FK `usuarios.id` |
| `identificador` | `varchar(180)` | Nao | Identificador gerado pelo app |
| `plataforma` | `varchar(30)` | Nao / `android` |  |
| `modelo_dispositivo` | `varchar(120)` | Sim | Diagnostico |
| `versao_sistema` | `varchar(50)` | Sim |  |
| `versao_app` | `varchar(30)` | Nao |  |
| `ultimo_acesso_at` | `timestamptz` | Sim |  |
| `revogado_at` | `timestamptz` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**Indices:** unico em `(usuario_id, identificador)`; indice em `(usuario_id, revogado_at)`.

### 12.3 `logs_sincronizacao`

**Finalidade:** controlar idempotencia, tentativas, conflitos e erros das operacoes mobile.

| Campo | Tipo | Nulo/Padrao | Chave ou regra |
|---|---|---|---|
| `id` | `uuid` | Nao / UUID | PK |
| `operacao_id` | `uuid` | Nao | ID idempotente do dispositivo |
| `dispositivo_id` | `uuid` | Nao | FK `dispositivos_mobile.id` |
| `usuario_id` | `uuid` | Nao | FK `usuarios.id` |
| `aplicacao_id` | `uuid` | Sim | FK `aplicacoes.id` |
| `tipo_operacao` | `varchar(80)` | Nao | Ex.: `confirmar_leitura` |
| `status` | `varchar(30)` | Nao / `recebida` | `recebida`, `processando`, `concluida`, `conflito`, `erro` |
| `payload_hash` | `char(64)` | Nao | Detecta reuso divergente da chave |
| `tentativas` | `integer` | Nao / `1` |  |
| `erro_codigo` | `varchar(80)` | Sim | Codigo estavel para o app |
| `erro_detalhes` | `jsonb` | Sim | Sem dados sensiveis desnecessarios |
| `entidade_resultante_tipo` | `varchar(80)` | Sim | Ex.: `leitura_cartao` |
| `entidade_resultante_id` | `uuid` | Sim | Resultado da operacao |
| `recebido_at` | `timestamptz` | Nao / `now()` |  |
| `processado_at` | `timestamptz` | Sim |  |
| `created_at` | `timestamptz` | Nao / `now()` |  |
| `updated_at` | `timestamptz` | Nao / `now()` |  |

**Indices e restricoes:**

- `uq_logs_sincronizacao_operacao`: unico em `operacao_id`.
- `idx_logs_sincronizacao_dispositivo_status`: `(dispositivo_id, status, created_at)`.
- `idx_logs_sincronizacao_aplicacao_status`: `(aplicacao_id, status)` onde `aplicacao_id IS NOT NULL`.
- `idx_logs_sincronizacao_erros`: `(status, created_at)` onde `status IN ('conflito', 'erro')`.
- Check `tentativas > 0`.
- Reenvio com mesmo `operacao_id` e mesmo `payload_hash` retorna o resultado anterior.
- Reenvio com mesmo `operacao_id` e hash diferente retorna conflito.

## 13. Relacionamentos resumidos e cardinalidades

| Origem | Cardinalidade | Destino | Implementacao |
|---|---:|---|---|
| Nucleo | 1:N | Escolas | `escolas.nucleo_id` |
| Escola | 1:N | Turmas | `turmas.escola_id` |
| Escola | 1:N | Alunos | `alunos.escola_id` |
| Usuario | N:N | Perfis | `usuario_perfis` |
| Perfil | N:N | Permissoes | `perfil_permissoes` |
| Aluno | N:N historico | Turmas | `matriculas_turmas` |
| Usuario | N:N | Turmas | `aplicadores_turmas` |
| Prova | 1:N | Questoes | `questoes.prova_id` |
| Prova | 1:N | Gabaritos oficiais | `gabaritos_oficiais.prova_id` |
| Gabarito oficial | 1:N | Respostas oficiais | `gabarito_respostas.gabarito_oficial_id` |
| Prova | N:N | Turmas | `prova_turmas` |
| Prova/Turma | 1:N | Aplicacoes | `aplicacoes.prova_id`, `turma_id` |
| Aplicacao | N:N | Usuarios | `aplicacao_aplicadores` |
| Aplicacao | N:N snapshot | Alunos | `aplicacao_alunos` |
| Aplicacao/Aluno | 1:N historico | Cartoes | `cartoes_resposta` |
| Cartao | 1:N | Leituras | `leituras_cartao.cartao_resposta_id` |
| Leitura | 1:N | Respostas detectadas | `respostas_detectadas.leitura_cartao_id` |
| Cartao | 1:N historico | Resultados | `resultados.cartao_resposta_id` |
| Resultado | 1:N | Resultado por questao | `resultado_respostas.resultado_id` |

## 14. Restricoes criticas de integridade

### 14.1 Um aluno por cartao valido e um cartao por aluno/prova

Garantido pelos indices unicos parciais de `cartoes_resposta`:

- `(prova_id, aluno_id)` onde `status = 'confirmado'`;
- `(prova_id, codigo_impresso_normalizado)` onde `status = 'confirmado'` e informado;
- `codigo_sistema` globalmente unico quando informado.

### 14.2 Uma versao vigente

Indices unicos parciais garantem:

- um `gabaritos_oficiais` vigente por prova;
- um `resultados` vigente por cartao;
- um vinculo ativo equivalente em tabelas de relacionamento.

### 14.3 Historico preservado

- Nova foto cria nova `leituras_cartao`.
- Substituicao cria novo `cartoes_resposta` e referencia `substitui_cartao_id`.
- Recorrection cria novo `resultados` e novo conjunto de `resultado_respostas`.
- Registros antigos mudam para `substituido` ou deixam de ser `vigente`; nao sao apagados.

### 14.4 Coerencia entre entidades

Algumas regras atravessam mais de uma tabela e exigem trigger transacional ou validacao obrigatoria no servico:

- turma pertence a escola da aplicacao;
- prova esta autorizada para turma;
- aluno pertence a turma no snapshot da aplicacao;
- modelo e gabarito pertencem ao contexto correto;
- questao pertence a prova da leitura;
- cartao, leitura e resultado referenciam a mesma prova, aplicacao e aluno.
- `aplicacao_alunos.resultado_vigente_id` aponta para o resultado vigente do mesmo aluno e aplicacao.

FKs compostas devem ser usadas sempre que removam ambiguidades sem tornar o modelo impraticavel.

## 15. Transacao de confirmacao da leitura

A confirmacao deve ocorrer em uma unica transacao PostgreSQL:

1. Consultar e bloquear a aplicacao com `SELECT ... FOR SHARE` ou estrategia equivalente.
2. Validar que a aplicacao esta `em_andamento`.
3. Validar aplicador, aluno previsto, modelo, alertas e respostas.
4. Registrar ou consultar `logs_sincronizacao` pela `operacao_id`.
5. Criar `cartoes_resposta`; os indices unicos impedem duplicidade concorrente.
6. Associar e marcar `leituras_cartao` como confirmada.
7. Persistir respostas finais e alteracoes manuais em `respostas_detectadas`.
8. Criar `resultados` e `resultado_respostas`.
9. Atualizar `aplicacao_alunos.status` e `resultado_vigente_id`.
10. Inserir `auditorias`.
11. Marcar o log de sincronizacao como concluido.
12. Efetivar o commit.
13. Publicar eventos WebSocket e jobs somente apos o commit.

Conflitos de unicidade devem ser convertidos pela API em erros `409` estaveis, como `ALUNO_JA_CONFIRMADO`, `CODIGO_IMPRESSO_JA_VINCULADO` e `CODIGO_SISTEMA_JA_UTILIZADO`.

## 16. Indices para dashboards e relatorios

Indices iniciais de maior impacto:

| Consulta | Indice principal |
|---|---|
| Progresso da aplicacao | `aplicacao_alunos(aplicacao_id, status)` |
| Ultimas leituras | `leituras_cartao(aplicacao_id, created_at DESC)` |
| Leituras com alerta | indice parcial em `leituras_cartao(aplicacao_id)` onde `requer_revisao` |
| Resultado vigente da aplicacao | `resultados(aplicacao_id, vigente)` |
| Resultado historico do aluno | `resultados(aluno_id, prova_id, vigente)` |
| Desempenho por questao | `resultado_respostas(questao_id, situacao)` |
| Auditoria por entidade | `auditorias(entidade_tipo, entidade_id, created_at DESC)` |
| Erros de sincronizacao | indice parcial de `logs_sincronizacao(status, created_at)` |

Indices de relatorio devem ser revistos com `EXPLAIN (ANALYZE, BUFFERS)` e dados representativos. Nao criar indices GIN em todos os `jsonb`; adicionar somente quando houver consulta real que os justifique.

## 17. Seguranca, LGPD e retencao

- Restringir acesso direto ao banco por papeis de menor privilegio.
- Considerar Row-Level Security futuramente, mantendo policies da aplicacao como primeira camada no MVP.
- Nao armazenar imagens em `bytea`; manter somente metadados em `arquivos`.
- Definir retencao de imagens e executar descarte auditado.
- Nao incluir senhas, tokens ou imagens em `auditorias` e `logs_sincronizacao`.
- Avaliar criptografia em nivel de aplicacao para documentos pessoais.
- Anonimizacao deve preservar chaves e historico necessario sem manter identificadores pessoais desnecessarios.

## 18. Particionamento e crescimento

Nao e necessario particionar no inicio. Avaliar particionamento quando metricas reais indicarem necessidade:

- `auditorias` por mes em `created_at`;
- `logs_sincronizacao` por mes em `created_at`;
- `leituras_cartao` por periodo ou prova em instalacoes de grande volume;
- `resultado_respostas` por prova em cenarios muito grandes.

Particionamento deve ser introduzido somente com testes de consultas, manutencao e retencao.

## 19. Ordem sugerida de migrations

1. Extensoes e funcoes padrao.
2. `nucleos`, `escolas`.
3. `usuarios`, `perfis`, `permissoes`, `perfil_permissoes`, `usuario_perfis`.
4. `turmas`, `alunos`, `matriculas_turmas`, `aplicadores_turmas`.
5. `arquivos`, `modelos_cartao`.
6. `provas`, `questoes`, `gabaritos_oficiais`, `gabarito_respostas`, `prova_turmas`.
7. `aplicacoes`, `aplicacao_aplicadores`, `aplicacao_alunos`.
8. `cartoes_resposta`, `leituras_cartao`, `respostas_detectadas`.
9. `resultados`, `resultado_respostas`.
10. Adicionar FK `aplicacao_alunos.resultado_vigente_id`.
11. `dispositivos_mobile`, `logs_sincronizacao`, `auditorias`.
12. Triggers de coerencia, indices parciais e permissoes de banco.

## 20. Decisoes adotadas antes das migrations

As decisoes bloqueadoras foram resolvidas no MP-001. A versao canonica e atualizada esta em [06-modelagem-banco.md](06-modelagem-banco.md), e o registro completo esta em [decisoes/README.md](decisoes/README.md).
