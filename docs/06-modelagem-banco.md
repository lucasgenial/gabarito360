# Gabarito360 - Modelagem Relacional MariaDB

## 1. Objetivo e status

Este documento e o contrato relacional canonico para a refatoracao do
Gabarito360 em MariaDB. Ele orienta as migrations da R2, mas **nao implementa
migrations ou SQL nesta etapa**.

O modelo cobre organizacao, autorizacao, estrutura academica, equipe escolar,
alunos e responsaveis, provas, aplicacoes, OMR, resultados, relatorios,
auditoria, arquivos, preferencias e sincronizacao.

## 2. Convencoes MariaDB

| Tema | Convencao |
|---|---|
| Engine | `InnoDB` |
| Charset/collation | `utf8mb4` / `utf8mb4_unicode_ci` |
| Chaves de negocio | `char(36)` com UUID gerado pela aplicacao |
| Chaves tecnicas de alto volume | `bigint unsigned auto_increment` quando indicado |
| Datas e horas | `datetime(6)` em UTC |
| Datas civis | `date` |
| Booleanos | `boolean` (`tinyint(1)`) |
| JSON | `json`, validado tambem pela aplicacao |
| Valores monetarios/pontos | `decimal(12,4)` |
| Percentuais/confianca | `decimal(6,5)` entre 0 e 1 |
| Exclusao | inativacao, encerramento ou anonimizacao; sem delete operacional |

Colunas padrao das entidades de negocio:

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `char(36)` | PK, UUID atribuido pela aplicacao |
| `created_at` | `datetime(6)` | obrigatorio |
| `updated_at` | `datetime(6)` | obrigatorio |
| `deleted_at` | `datetime(6) null` | apenas onde descarte logico for permitido |

FKs usam `ON DELETE RESTRICT` por padrao. `CASCADE` e permitido somente em
associacoes sem historico proprio; `SET NULL` preserva eventos quando o ator for
anonimizado.

## 3. Estrategia de integridade

- Unicidade simples usa `UNIQUE`.
- Unicidade dependente de vigencia usa a coluna nullable `chave_vigente`.
  Enquanto o vinculo estiver ativo, o service grava uma chave deterministica
  unica; ao encerrar, grava `NULL`, permitindo preservar o historico.
- Fluxos que dependem de varias tabelas usam transacao e `SELECT ... FOR UPDATE`.
- Nao usar triggers como mecanismo principal de negocio.
- Status ficam em `varchar`, representados por Enums Laravel e, quando simples e
  estaveis, por `CHECK` compativel com MariaDB.
- Dados pessoais, tokens, imagens e segredos nao entram em JSON de auditoria.

## 4. Organizacao e acesso

### 4.1 `nucleos`

**Finalidade:** representar a unidade gestora que agrega escolas.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `char(36)` | PK |
| `nome` | `varchar(160)` | obrigatorio |
| `codigo` | `varchar(40)` | obrigatorio, unico |
| `email` | `varchar(254) null` | normalizado |
| `telefone` | `varchar(30) null` |  |
| `status` | `varchar(20)` | `ativo` ou `inativo` |
| `created_at`, `updated_at` | `datetime(6)` | obrigatorios |

**Indices:** `UNIQUE(codigo)`, `INDEX(status, nome)`.

**Relacionamentos e regras:** possui escolas e lotacoes. Inativacao nao remove
escolas nem historico.

### 4.2 `escolas`

**Finalidade:** armazenar unidades escolares pertencentes a um nucleo.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `char(36)` | PK |
| `nucleo_id` | `char(36)` | FK `nucleos.id` |
| `nome` | `varchar(180)` | obrigatorio |
| `codigo` | `varchar(50)` | obrigatorio |
| `inep` | `varchar(20) null` | unico quando informado |
| `email`, `telefone` | `varchar(254) null`, `varchar(30) null` |  |
| `logradouro`, `numero`, `complemento`, `bairro`, `cidade`, `uf`, `cep` | `varchar` | endereco opcional |
| `status` | `varchar(20)` | `ativa` ou `inativa` |
| `created_at`, `updated_at` | `datetime(6)` | obrigatorios |

**Indices:** `UNIQUE(nucleo_id, codigo)`, `UNIQUE(inep)`,
`INDEX(nucleo_id, status, nome)`.

**Relacionamentos e regras:** pertence a um nucleo; agrega lotacoes, turmas,
alunos, provas e aplicacoes. Deve ser inativada, nunca apagada pelo fluxo comum.

### 4.3 `usuarios`

**Finalidade:** identidade autenticavel de profissionais e administradores.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `char(36)` | PK |
| `nome` | `varchar(180)` | obrigatorio |
| `email` | `varchar(254)` | obrigatorio, normalizado e unico |
| `cpf_hash` | `char(64) null` | unico; nao armazenar CPF aberto sem decisao especifica |
| `telefone` | `varchar(30) null` |  |
| `password` | `varchar(255)` | hash forte |
| `foto_arquivo_id` | `char(36) null` | FK `arquivos.id` adicionada apos `arquivos` |
| `email_verified_at`, `ultimo_acesso_at` | `datetime(6) null` |  |
| `status` | `varchar(30)` | `convite_pendente`, `ativo`, `bloqueado`, `inativo` |
| `remember_token` | `varchar(100) null` |  |
| `created_at`, `updated_at` | `datetime(6)` | obrigatorios |

**Indices:** `UNIQUE(email)`, `UNIQUE(cpf_hash)`, `INDEX(status, nome)`.

**Relacionamentos e regras:** possui perfis, cargos, lotacoes, disciplinas,
turmas, preferencias, sessoes e auditorias. Nao existe auto-cadastro aberto.

### 4.4 `perfis`

**Finalidade:** agrupar permissoes de autorizacao, sem representar cargo.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `char(36)` | PK |
| `codigo` | `varchar(80)` | unico |
| `nome` | `varchar(120)` | obrigatorio |
| `descricao` | `varchar(500) null` |  |
| `escopo_maximo` | `varchar(30)` | `global`, `nucleo`, `escola`, `operacional` |
| `sistema` | `boolean` | protege perfis internos |
| `ativo` | `boolean` | obrigatorio |

**Indices:** `UNIQUE(codigo)`, `INDEX(ativo, nome)`.

**Relacionamentos e regras:** possui permissoes e vinculos com usuarios.

### 4.5 `permissoes`

**Finalidade:** catalogar capacidades atomicas.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `char(36)` | PK |
| `codigo` | `varchar(120)` | unico |
| `descricao` | `varchar(255)` | obrigatorio |
| `mutacao` | `boolean` | informa se altera estado |
| `ativa` | `boolean` | obrigatorio |

**Indices:** `UNIQUE(codigo)`, `INDEX(ativa, codigo)`.

### 4.6 `perfil_permissoes`

**Finalidade:** associar permissoes a perfis.

| Campo | Tipo | Regra |
|---|---|---|
| `perfil_id` | `char(36)` | PK/FK `perfis.id` |
| `permissao_id` | `char(36)` | PK/FK `permissoes.id` |
| `created_at` | `datetime(6)` | obrigatorio |

**Indices:** PK composta `(perfil_id, permissao_id)`,
`INDEX(permissao_id, perfil_id)`.

### 4.7 `usuario_perfis`

**Finalidade:** conceder um perfil a um usuario em escopo e periodo definidos.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `char(36)` | PK |
| `usuario_id`, `perfil_id` | `char(36)` | FKs |
| `nucleo_id`, `escola_id` | `char(36) null` | FKs de escopo |
| `concedido_por_id` | `char(36) null` | FK `usuarios.id` |
| `inicio_at`, `fim_at` | `datetime(6)` | vigencia |
| `chave_vigente` | `varchar(255) null` | unico enquanto ativo |

**Indices:** `UNIQUE(chave_vigente)`,
`INDEX(usuario_id, fim_at)`, `INDEX(perfil_id, escola_id, fim_at)`.

**Regras:** escopo deve ser coerente com `perfis.escopo_maximo`; concessao e
revogacao sao auditadas.

### 4.8 `cargos`

**Finalidade:** catalogar funcoes institucionais como diretor, vice-diretor,
coordenador e professor.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `char(36)` | PK |
| `codigo`, `nome` | `varchar(80)`, `varchar(120)` | codigo unico |
| `descricao` | `varchar(500) null` |  |
| `ativo` | `boolean` | obrigatorio |

**Indices:** `UNIQUE(codigo)`, `INDEX(ativo, nome)`.

**Regra:** cargo nao concede permissao.

### 4.9 `usuario_lotacoes`

**Finalidade:** registrar onde e em qual cargo um usuario atua.

| Campo | Tipo | Regra |
|---|---|---|
| `id` | `char(36)` | PK |
| `usuario_id`, `cargo_id` | `char(36)` | FKs |
| `nucleo_id`, `escola_id` | `char(36) null` | FKs; um contexto obrigatorio |
| `matricula_funcional` | `varchar(60) null` |  |
| `inicio_em`, `fim_em` | `date` | vigencia |
| `principal` | `boolean` | destaque visual |
| `chave_vigente` | `varchar(255) null` | unico enquanto ativo |

**Indices:** `UNIQUE(chave_vigente)`, `INDEX(escola_id, fim_em, cargo_id)`,
`INDEX(usuario_id, fim_em)`.

**Relacionamentos e regras:** separa equipe escolar da autorizacao. Lotacao nao
substitui `usuario_perfis`.

## 5. Estrutura academica e equipe

### 5.1 `periodos_letivos`

**Finalidade:** definir ano letivo e subdivisoes.

Campos: `id char(36)` PK, `escola_id char(36)` FK, `nome varchar(100)`,
`ano smallint unsigned`, `tipo varchar(30)`, `inicio_em date`, `fim_em date`,
`status varchar(20)`, timestamps.

**Indices/regras:** `UNIQUE(escola_id, ano, nome)`,
`INDEX(escola_id, status, inicio_em)`; datas nao podem se inverter.

### 5.2 `series_anos`

**Finalidade:** normalizar serie/ano escolar.

Campos: `id char(36)` PK, `codigo varchar(40)` unico, `nome varchar(100)`,
`ordem smallint unsigned`, `nivel varchar(40)`, `ativo boolean`, timestamps.

**Indices:** `UNIQUE(codigo)`, `INDEX(ativo, ordem)`.

### 5.3 `disciplinas`

**Finalidade:** catalogar componentes curriculares.

Campos: `id char(36)` PK, `codigo varchar(40)` unico, `nome varchar(120)`,
`cor_token varchar(80) null`, `ativo boolean`, timestamps.

**Indices:** `UNIQUE(codigo)`, `INDEX(ativo, nome)`.

### 5.4 `turmas`

**Finalidade:** representar agrupamento de alunos em uma escola e periodo.

Campos: `id char(36)` PK, `escola_id char(36)` FK,
`periodo_letivo_id char(36)` FK, `serie_ano_id char(36)` FK,
`codigo varchar(50)`, `nome varchar(120)`, `turno varchar(30)`,
`capacidade smallint unsigned null`, `status varchar(20)`, timestamps.

**Indices/regras:** `UNIQUE(escola_id, periodo_letivo_id, codigo)`,
`INDEX(escola_id, status, serie_ano_id)`; periodo e turma pertencem a mesma
escola.

### 5.5 `usuario_turmas`

**Finalidade:** vincular profissionais a turmas.

Campos: `id char(36)` PK, `usuario_id char(36)` FK, `turma_id char(36)` FK,
`papel varchar(40)`, `inicio_em date`, `fim_em date null`,
`chave_vigente varchar(255) null`, timestamps.

**Indices/regras:** `UNIQUE(chave_vigente)`, `INDEX(turma_id, fim_em, papel)`,
`INDEX(usuario_id, fim_em)`; substitui o conceito restrito de
`aplicadores_turmas` e suporta professor, aplicador e coordenador.

### 5.6 `usuario_disciplinas`

**Finalidade:** registrar disciplinas em que o profissional pode atuar.

Campos: `id char(36)` PK, `usuario_id char(36)` FK,
`disciplina_id char(36)` FK, `escola_id char(36) null` FK,
`inicio_em date`, `fim_em date null`, `chave_vigente varchar(255) null`,
timestamps.

**Indices:** `UNIQUE(chave_vigente)`, `INDEX(usuario_id, fim_em)`,
`INDEX(disciplina_id, escola_id, fim_em)`.

## 6. Alunos e responsaveis

### 6.1 `alunos`

**Finalidade:** armazenar a pessoa estudante sem transforma-la em usuario.

Campos: `id char(36)` PK, `escola_id char(36)` FK, `nome varchar(180)`,
`nome_social varchar(180) null`, `data_nascimento date null`,
`matricula varchar(80)`, `matricula_normalizada varchar(80)`,
`foto_arquivo_id char(36) null` FK `arquivos.id`, `status varchar(20)`,
timestamps.

**Indices/regras:** `UNIQUE(escola_id, matricula_normalizada)`,
`INDEX(escola_id, status, nome)`; aluno inativo permanece no historico.

### 6.2 `responsaveis`

**Finalidade:** armazenar contato minimo de responsavel pelo aluno.

Campos: `id char(36)` PK, `nome varchar(180)`, `email varchar(254) null`,
`telefone varchar(30) null`, `documento_hash char(64) null`, `status varchar(20)`,
timestamps.

**Indices/regras:** `UNIQUE(documento_hash)`, `INDEX(nome)`; coletar apenas dados
necessarios e nao criar login automaticamente.

### 6.3 `aluno_responsaveis`

**Finalidade:** vincular aluno e responsavel com parentesco e autorizacoes.

Campos: `id char(36)` PK, `aluno_id char(36)` FK,
`responsavel_id char(36)` FK, `parentesco varchar(50)`, `principal boolean`,
`autorizado_contato boolean`, `inicio_em date`, `fim_em date null`,
`chave_vigente varchar(255) null`, timestamps.

**Indices:** `UNIQUE(chave_vigente)`, `INDEX(aluno_id, fim_em)`,
`INDEX(responsavel_id, fim_em)`.

### 6.4 `matriculas_turmas`

**Finalidade:** preservar o historico do aluno nas turmas.

Campos: `id char(36)` PK, `aluno_id char(36)` FK, `turma_id char(36)` FK,
`numero_chamada smallint unsigned null`, `inicio_em date`, `fim_em date null`,
`situacao varchar(30)`, `chave_vigente varchar(255) null`, timestamps.

**Indices/regras:** `UNIQUE(chave_vigente)`, `INDEX(turma_id, situacao, fim_em)`,
`INDEX(aluno_id, fim_em)`; apenas uma matricula vigente por aluno no mesmo
periodo letivo, garantida por service transacional.

### 6.5 `importacoes_alunos` e `importacao_aluno_linhas`

**Finalidade:** validar importacoes antes da confirmacao.

`importacoes_alunos`: `id` PK, `escola_id`, `turma_id`, `arquivo_id`,
`solicitante_id` FKs, `status`, contadores, `confirmada_at`, timestamps.

`importacao_aluno_linhas`: `id bigint unsigned` PK, `importacao_id` FK,
`numero_linha`, `dados json`, `status`, `erros json`, `aluno_id null` FK.

**Indices/regras:** linha unica por `(importacao_id, numero_linha)`;
`INDEX(importacao_id, status)`; nenhuma linha invalida e confirmada.

## 7. Provas, questoes e gabaritos

### 7.1 `modelos_cartao`

**Finalidade:** versionar geometria e parametros homologados de OMR.

Campos: `id char(36)` PK, `codigo varchar(80)`, `versao unsigned smallint`,
`nome varchar(160)`, `quantidade_questoes smallint unsigned`,
`alternativas json`, `configuracao_omr json`, `arquivo_template_id char(36) null`
FK, `status varchar(30)`, `homologado_at datetime(6) null`, timestamps.

**Indices/regras:** `UNIQUE(codigo, versao)`, `INDEX(status, nome)`; parametros
OMR sao imutaveis apos homologacao.

### 7.2 `temas_habilidades`

**Finalidade:** classificar questoes por tema ou habilidade pedagogica.

Campos: `id char(36)` PK, `disciplina_id char(36)` FK,
`codigo varchar(60) null`, `nome varchar(180)`, `descricao text null`,
`tipo varchar(30)`, `ativo boolean`, timestamps.

**Indices:** `UNIQUE(disciplina_id, codigo)`, `INDEX(disciplina_id, ativo, nome)`.

### 7.3 `provas`

**Finalidade:** representar uma avaliacao criada por profissional autorizado.

Campos: `id char(36)` PK, `escola_id char(36) null` FK,
`nucleo_id char(36) null` FK, `disciplina_id char(36)` FK,
`serie_ano_id char(36) null` FK, `modelo_cartao_id char(36)` FK,
`autor_id char(36)` FK `usuarios.id`, `titulo varchar(180)`,
`descricao text null`, `tipo varchar(40)`, `quantidade_questoes smallint`,
`valor_total decimal(12,4)`, `status varchar(30)`, `publicada_at`,
`encerrada_at` datetimes nullable, timestamps.

**Indices/regras:** `INDEX(escola_id, status, created_at)`,
`INDEX(disciplina_id, serie_ano_id, status)`, `INDEX(autor_id, status)`;
professor cria prova apenas com permissao e escopo explicitos.

### 7.4 `questoes`

**Finalidade:** armazenar estrutura e pontuacao das questoes.

Campos: `id char(36)` PK, `prova_id char(36)` FK, `numero smallint unsigned`,
`enunciado text null`, `peso decimal(12,4)`, `status varchar(20)`,
`metadados json null`, timestamps.

**Indices/regras:** `UNIQUE(prova_id, numero)`, `INDEX(prova_id, status)`;
numeracao deve ser continua antes da publicacao.

### 7.5 `questao_temas`

**Finalidade:** associar questoes a temas/habilidades.

Campos: `questao_id char(36)` e `tema_habilidade_id char(36)` como PK/FKs,
`principal boolean`, `created_at datetime(6)`.

**Indices:** PK composta, `INDEX(tema_habilidade_id, questao_id)`.

### 7.6 `gabaritos_oficiais`

**Finalidade:** versionar gabaritos de uma prova.

Campos: `id char(36)` PK, `prova_id char(36)` FK, `versao smallint unsigned`,
`status varchar(30)`, `criado_por_id char(36)` FK,
`publicado_por_id char(36) null` FK, `justificativa text null`,
`publicado_at datetime(6) null`, `chave_vigente varchar(255) null`, timestamps.

**Indices/regras:** `UNIQUE(prova_id, versao)`, `UNIQUE(chave_vigente)`,
`INDEX(prova_id, status)`; somente um gabarito vigente por prova.

### 7.7 `gabarito_respostas`

**Finalidade:** armazenar resposta oficial e pontuacao por questao.

Campos: `id char(36)` PK, `gabarito_oficial_id char(36)` FK,
`questao_id char(36)` FK, `alternativa_correta varchar(10) null`,
`pontuacao decimal(12,4)`, `anulada boolean`, timestamps.

**Indices/regras:** `UNIQUE(gabarito_oficial_id, questao_id)`,
`INDEX(questao_id)`; anulada nao possui alternativa obrigatoria.

### 7.8 `prova_turmas`

**Finalidade:** autorizar uma prova para turmas.

Campos: `id char(36)` PK, `prova_id char(36)` FK, `turma_id char(36)` FK,
`vinculado_por_id char(36)` FK, `status varchar(20)`, timestamps.

**Indices:** `UNIQUE(prova_id, turma_id)`, `INDEX(turma_id, status)`.

## 8. Aplicacoes, cartoes e OMR

### 8.1 `aplicacoes`

**Finalidade:** representar a execucao de uma prova em uma turma.

Campos: `id char(36)` PK, `prova_id`, `turma_id`, `escola_id`,
`gabarito_oficial_id` FKs, `titulo varchar(180)`, `inicio_previsto_at`,
`fim_previsto_at`, `iniciada_at`, `finalizada_at` datetimes nullable,
`status varchar(30)`, `criada_por_id char(36)` FK, timestamps.

**Indices/regras:** `INDEX(turma_id, status, inicio_previsto_at)`,
`INDEX(prova_id, status)`, `INDEX(escola_id, status)`; gabarito e turma devem
ser validos para a prova; finalizar bloqueia novas confirmacoes.

### 8.2 `aplicacao_aplicadores`

**Finalidade:** vincular profissionais autorizados a uma aplicacao.

Campos: `id char(36)` PK, `aplicacao_id`, `usuario_id` FKs,
`papel varchar(30)`, `inicio_at`, `fim_at` datetimes nullable,
`chave_vigente varchar(255) null`, timestamps.

**Indices:** `UNIQUE(chave_vigente)`, `INDEX(aplicacao_id, fim_at)`,
`INDEX(usuario_id, fim_at)`.

### 8.3 `aplicacao_alunos`

**Finalidade:** congelar os alunos previstos e seu estado operacional.

Campos: `id char(36)` PK, `aplicacao_id`, `aluno_id`, `matricula_turma_id` FKs,
`status varchar(30)`, `resultado_vigente_id char(36) null` FK adicionada apos
`resultados`, `confirmado_at datetime(6) null`, timestamps.

**Indices/regras:** `UNIQUE(aplicacao_id, aluno_id)`,
`INDEX(aplicacao_id, status)`, `INDEX(aluno_id, aplicacao_id)`.

### 8.4 `cartoes_resposta`

**Finalidade:** preservar separadamente identificadores impressos e do sistema.

Campos: `id char(36)` PK, `prova_id`, `aluno_id`, `aplicacao_id` FKs,
`codigo_impresso varchar(120) null`, `codigo_impresso_normalizado varchar(120) null`,
`codigo_sistema varchar(19) null`, `codigo_sistema_afixado boolean`,
`motivo_sem_codigo_impresso varchar(80) null`, `status varchar(30)`,
`chave_vigente varchar(255) null`, timestamps.

**Indices/regras:** `UNIQUE(codigo_sistema)`,
`UNIQUE(prova_id, codigo_impresso_normalizado)`,
`UNIQUE(chave_vigente)` para garantir apenas um cartao vigente por aluno/prova
sem perder historico; os dois codigos nunca sobrescrevem um ao outro.

### 8.5 `leituras_cartao`

**Finalidade:** registrar cada tentativa de captura e processamento.

Campos: `id char(36)` PK, `aplicacao_id`, `aplicacao_aluno_id`,
`cartao_resposta_id null`, `modelo_cartao_id`, `arquivo_original_id`,
`arquivo_processado_id null`, `capturada_por_id`, `dispositivo_id null` FKs,
`operacao_id varchar(100)`, `status varchar(30)`, `confianca_geral decimal(6,5)`,
`requer_revisao boolean`, `alertas json null`, `confirmada_at datetime(6) null`,
`cancelada_at datetime(6) null`, timestamps.

**Indices/regras:** `UNIQUE(operacao_id)`,
`INDEX(aplicacao_id, status, created_at)`,
`INDEX(aplicacao_aluno_id, created_at)`; tentativa nunca sobrescreve anterior.

### 8.6 `respostas_detectadas`

**Finalidade:** preservar deteccao OMR e eventual resposta final revisada.

Campos: `id bigint unsigned auto_increment` PK, `leitura_cartao_id char(36)` FK,
`questao_id char(36)` FK, `alternativa_detectada varchar(10) null`,
`alternativa_final varchar(10) null`, `tipo_deteccao varchar(30)`,
`confianca decimal(6,5)`, `alterada_manualmente boolean`,
`motivo_alteracao varchar(500) null`, `alterada_por_id char(36) null` FK,
`alterada_at datetime(6) null`, timestamps.

**Indices/regras:** `UNIQUE(leitura_cartao_id, questao_id)`,
`INDEX(questao_id, tipo_deteccao)`; mudanca manual exige motivo e auditoria.

## 9. Resultados e relatorios

### 9.1 `resultados`

**Finalidade:** armazenar o calculo versionado de uma leitura confirmada.

Campos: `id char(36)` PK, `aplicacao_id`, `aplicacao_aluno_id`, `aluno_id`,
`prova_id`, `gabarito_oficial_id`, `leitura_cartao_id` FKs,
`versao smallint unsigned`, `status varchar(30)`, `acertos`, `erros`, `brancos`,
`duplas`, `anuladas` smallints, `pontuacao decimal(12,4)`,
`nota_percentual decimal(7,4)`, `calculado_at datetime(6)`,
`chave_vigente varchar(255) null`, timestamps.

**Indices/regras:** `UNIQUE(aplicacao_aluno_id, versao)`,
`UNIQUE(chave_vigente)`, `INDEX(aplicacao_id, status)`,
`INDEX(aluno_id, prova_id, status)`; somente um resultado vigente.

### 9.2 `resultado_questoes`

**Finalidade:** permitir relatorios e desempenho por questao sem recalcular OMR.

Campos: `id bigint unsigned auto_increment` PK, `resultado_id char(36)` FK,
`questao_id char(36)` FK, `resposta_final varchar(10) null`,
`situacao varchar(30)`, `pontuacao decimal(12,4)`, `tema_snapshot json null`,
timestamps.

**Indices:** `UNIQUE(resultado_id, questao_id)`,
`INDEX(questao_id, situacao)`.

### 9.3 `relatorios`

**Finalidade:** registrar solicitacao, geracao e expiracao de exportacoes.

Campos: `id char(36)` PK, `tipo varchar(50)`, `formato varchar(20)`,
`solicitante_id char(36)` FK, `arquivo_id char(36) null` FK,
`filtros json`, `escopo json`, `status varchar(30)`, `solicitado_at`,
`concluido_at`, `expira_at` datetimes nullable, `erro_codigo varchar(80) null`,
timestamps.

**Indices/regras:** `INDEX(solicitante_id, status, solicitado_at)`,
`INDEX(status, expira_at)`; CSV e PDF canonicos no MVP, XLSX V2; solicitacao e
download auditados.

## 10. Preferencias, arquivos, auditoria e LGPD

### 10.1 `preferencias_usuario`

**Finalidade:** persistir aparencia e acessibilidade.

Campos: `usuario_id char(36)` PK/FK, `tema varchar(20)` default `claro`,
`densidade varchar(20)`, `contraste_alto boolean`, `reduzir_movimento boolean`,
`idioma varchar(10)` default `pt-BR`, timestamps.

**Regra:** tema escuro depende de escolha explicita.

### 10.2 `preferencias_notificacao`

**Finalidade:** controlar canais e eventos de notificacao.

Campos: `id char(36)` PK, `usuario_id char(36)` FK, `evento varchar(80)`,
`canal varchar(30)`, `habilitada boolean`, timestamps.

**Indices:** `UNIQUE(usuario_id, evento, canal)`.

### 10.3 `arquivos`

**Finalidade:** registrar metadados de objetos privados em storage externo.

Campos: `id char(36)` PK, `disco varchar(40)`, `caminho varchar(500)`,
`nome_original varchar(255)`, `mime varchar(120)`, `tamanho_bytes bigint unsigned`,
`checksum varchar(128)`, `classificacao varchar(40)`, `proprietario_tipo varchar(80)`,
`proprietario_id char(36) null`, `criado_por_id char(36) null` FK,
`reter_ate datetime(6) null`, `descartado_at datetime(6) null`, timestamps.

**Indices/regras:** `UNIQUE(disco, caminho)`, `INDEX(checksum)`,
`INDEX(classificacao, reter_ate)`; conteudo nao fica no banco.

### 10.4 `auditorias`

**Finalidade:** manter trilha imutavel de operacoes criticas.

Campos: `id bigint unsigned auto_increment` PK, `ator_id char(36) null` FK,
`acao varchar(120)`, `entidade_tipo varchar(100)`, `entidade_id char(36) null`,
`escopo_tipo varchar(40) null`, `escopo_id char(36) null`,
`request_id char(36) null`, `ip varchar(45) null`, `user_agent varchar(500) null`,
`antes json null`, `depois json null`, `metadados json null`,
`created_at datetime(6)`.

**Indices/regras:** `INDEX(entidade_tipo, entidade_id, created_at)`,
`INDEX(ator_id, created_at)`, `INDEX(request_id)`; sem `updated_at`, segredos ou
payloads sensiveis.

### 10.5 `solicitacoes_lgpd`

**Finalidade:** acompanhar solicitacoes de acesso, correcao, anonimizacao ou
eliminacao.

Campos: `id char(36)` PK, `tipo varchar(40)`, `titular_tipo varchar(40)`,
`titular_referencia_hash char(64)`, `solicitante_id char(36) null` FK,
`status varchar(30)`, `descricao text`, `decisao text null`, `prazo_at date`,
`concluida_at datetime(6) null`, timestamps.

**Indices:** `INDEX(status, prazo_at)`, `INDEX(titular_referencia_hash)`.

### 10.6 `integracoes`

**Finalidade:** reservar configuracao governada para integracoes futuras.

Campos: `id char(36)` PK, `tipo varchar(80)`, `nome varchar(120)`,
`escopo_tipo varchar(40)`, `escopo_id char(36) null`, `status varchar(30)`,
`configuracao_publica json null`, `segredo_referencia varchar(255) null`,
timestamps.

**Indices/regras:** `INDEX(tipo, status)`; V2, desabilitada por padrao; segredos
ficam em cofre externo, nunca em JSON.

## 11. Mobile, sincronizacao e tabelas tecnicas

### 11.1 `dispositivos_mobile`

**Finalidade:** identificar dispositivos autorizados e versoes do app.

Campos: `id char(36)` PK, `usuario_id char(36)` FK,
`identificador_hash char(64)`, `plataforma varchar(30)`, `modelo varchar(120)`,
`versao_app varchar(30)`, `ultimo_acesso_at datetime(6)`, `revogado_at datetime(6) null`,
timestamps.

**Indices:** `UNIQUE(identificador_hash)`, `INDEX(usuario_id, revogado_at)`.

### 11.2 `logs_sincronizacao`

**Finalidade:** rastrear operacoes idempotentes recebidas do app.

Campos: `id bigint unsigned auto_increment` PK, `operacao_id varchar(100)`,
`usuario_id`, `dispositivo_id`, `aplicacao_id` FKs nullable,
`tipo varchar(80)`, `status varchar(30)`, `tentativas smallint unsigned`,
`payload_hash char(64)`, `erro_codigo varchar(80) null`,
`erro_detalhe_sanitizado varchar(500) null`, `processado_at datetime(6) null`,
`created_at datetime(6)`.

**Indices/regras:** `UNIQUE(operacao_id)`, `INDEX(status, created_at)`,
`INDEX(dispositivo_id, created_at)`; nao registrar payload pessoal.

### 11.3 Tabelas Laravel

`personal_access_tokens`, `password_reset_tokens`, `sessions`, `jobs`,
`job_batches`, `failed_jobs`, `cache` e `cache_locks` seguem as migrations
oficiais compativeis com MariaDB. Tokens sao armazenados por hash. Jobs falhos e
sessoes obedecem retencao e acesso restrito.

## 12. Relacionamentos centrais

```text
nucleo -> escolas -> periodos_letivos -> turmas -> matriculas_turmas -> alunos
                    |                    |             |
                    |                    |             -> aplicacao_alunos
                    |                    -> usuario_turmas
                    -> usuario_lotacoes

usuarios -> usuario_perfis -> perfis -> perfil_permissoes -> permissoes
usuarios -> usuario_lotacoes -> cargos
usuarios -> usuario_disciplinas -> disciplinas

provas -> questoes -> questao_temas -> temas_habilidades
provas -> gabaritos_oficiais -> gabarito_respostas
provas -> prova_turmas -> turmas
provas -> aplicacoes -> aplicacao_alunos -> leituras_cartao -> respostas_detectadas
                                      \-> resultados -> resultado_questoes
```

## 13. Transacoes criticas

### Confirmacao de leitura

1. Bloquear aplicacao, aluno previsto e identificadores do cartao.
2. Validar permissao, estado, idempotencia e unicidades.
3. Criar ou vincular `cartoes_resposta`.
4. Confirmar `leituras_cartao` e respostas finais.
5. Criar `resultados` e `resultado_questoes`.
6. Atualizar `aplicacao_alunos.resultado_vigente_id`.
7. Registrar auditoria.
8. Publicar evento somente apos commit.

### Publicacao de gabarito

1. Bloquear prova e gabaritos da prova.
2. Validar questoes, respostas, modelo e escopo do autor.
3. Encerrar a `chave_vigente` anterior e publicar nova versao.
4. Bloquear alteracao comum apos a primeira aplicacao iniciada.
5. Registrar auditoria.

## 14. Indices prioritarios para o MVP

| Consulta | Indice |
|---|---|
| Progresso da aplicacao | `aplicacao_alunos(aplicacao_id, status)` |
| Ultimas leituras | `leituras_cartao(aplicacao_id, status, created_at)` |
| Resultado vigente | `resultados(chave_vigente)` |
| Desempenho por questao | `resultado_questoes(questao_id, situacao)` |
| Turmas do profissional | `usuario_turmas(usuario_id, fim_em)` |
| Equipe da escola | `usuario_lotacoes(escola_id, fim_em, cargo_id)` |
| Auditoria da entidade | `auditorias(entidade_tipo, entidade_id, created_at)` |
| Retencao de arquivos | `arquivos(classificacao, reter_ate)` |

Indices adicionais devem ser justificados por consulta real e validados com
`EXPLAIN` em dados representativos.

## 15. Ordem sugerida das migrations da R2

1. tabelas Laravel e organizacao;
2. usuarios, perfis, permissoes, cargos e vinculos;
3. periodos, series, disciplinas e turmas;
4. alunos, responsaveis, matriculas e importacoes;
5. arquivos e modelos de cartao;
6. provas, questoes, temas, gabaritos e vinculos;
7. aplicacoes e alunos previstos;
8. cartoes, leituras e respostas detectadas;
9. resultados e relatorios;
10. preferencias, auditoria, LGPD, dispositivos e sincronizacao;
11. FKs circulares previamente adiadas;
12. seeders de catalogos e perfis.

## 16. Gate para iniciar migrations

- ADR-D013 e mapa de rotas aprovados.
- Nenhum tipo, funcao, indice parcial ou trigger depende de PostgreSQL.
- Toda unicidade condicional possui estrategia `chave_vigente` ou service
  transacional documentado.
- Tabelas com dados pessoais possuem finalidade, escopo e retencao definidos.
- Migrations serao validadas em MariaDB vazio por `migrate:fresh --seed`.
