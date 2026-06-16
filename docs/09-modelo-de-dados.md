# 09 — Modelo de Dados

Este modelo é derivado dos mockups e das regras de negócio documentadas.
Representa o modelo conceitual. Detalhes de implementação (tipos exatos, índices específicos) serão definidos nas migrations de cada MP.

---

## Diagrama de Entidades (Texto)

```
Rede (1) ──── (N) Nucleo (1) ──── (N) Escola (1) ──── (N) Turma (1) ──── (N) Aluno
                                       │                      │
                                       │                      └──── (N) ProvaVinculo
                                       │
                                    (N) Usuario (N) ──── (1) Perfil
                                                │
                                            (N) Prova (1) ──── (1) Gabarito (1) ──── (N) GabaritoQuestao
                                                │
                                            (N) Cartao (1) ──── (N) CartaoResposta
                                                │
                                            (N) Nota
                                                │
                                         AmbiguidadeLog
```

---

## Entidades

---

### redes

| Coluna           | Tipo        | Descrição                         |
|------------------|-------------|-----------------------------------|
| id               | bigint PK   | Identificador único               |
| nome             | varchar(200)| Nome da rede de ensino            |
| tipo             | enum        | municipal, estadual, federal      |
| uf               | char(2)     | Unidade federativa                |
| meta_media       | decimal(4,2)| Meta de média da rede (ex.: 7.0)  |
| meta_minima      | decimal(4,2)| Nota mínima de aprovação (ex.: 6.0)|
| limiar_seges_min | int         | Minutos de atraso aceitáveis SEGES|
| created_at       | timestamp   |                                   |
| updated_at       | timestamp   |                                   |

---

### nucleos

| Coluna     | Tipo        | Descrição                    |
|------------|-------------|------------------------------|
| id         | bigint PK   |                              |
| rede_id    | FK → redes  |                              |
| nome       | varchar(200)| Nome do núcleo regional      |
| created_at | timestamp   |                              |
| updated_at | timestamp   |                              |

---

### escolas

| Coluna          | Tipo          | Descrição                                   |
|-----------------|---------------|---------------------------------------------|
| id              | bigint PK     |                                             |
| nucleo_id       | FK → nucleos  |                                             |
| nome            | varchar(200)  | Nome oficial da escola                      |
| inep            | char(8)       | Código INEP único                           |
| tipo_rede       | enum          | estadual, municipal, federal, privada       |
| logradouro      | varchar(255)  |                                             |
| cidade          | varchar(100)  |                                             |
| uf              | char(2)       |                                             |
| telefone        | varchar(20)   |                                             |
| email           | varchar(150)  | E-mail institucional                        |
| ativo           | boolean       | true = ativa, false = inativa               |
| created_at      | timestamp     |                                             |
| updated_at      | timestamp     |                                             |

**Índices:** nucleo_id, inep (unique)

---

### turmas

| Coluna          | Tipo          | Descrição                             |
|-----------------|---------------|---------------------------------------|
| id              | bigint PK     |                                       |
| escola_id       | FK → escolas  |                                       |
| nome            | varchar(50)   | Ex.: "9º A", "8º B"                  |
| serie           | varchar(50)   | Ex.: "9º ano", "8º ano"              |
| turno           | enum          | manha, tarde, noite, integral         |
| ano_letivo      | year          | Ano letivo (ex.: 2026)               |
| ativo           | boolean       |                                       |
| created_at      | timestamp     |                                       |
| updated_at      | timestamp     |                                       |

**Índices:** escola_id, (escola_id, ano_letivo)

---

### alunos

| Coluna           | Tipo         | Descrição                             |
|------------------|--------------|---------------------------------------|
| id               | bigint PK    |                                       |
| turma_id         | FK → turmas  |                                       |
| nome             | varchar(200) | Nome completo do aluno                |
| matricula        | varchar(20)  | Matrícula única no período letivo     |
| data_nascimento  | date         |                                       |
| nome_responsavel | varchar(200) |                                       |
| ativo            | boolean      |                                       |
| created_at       | timestamp    |                                       |
| updated_at       | timestamp    |                                       |

**Índices:** turma_id, matricula (unique por rede + ano_letivo)

---

### usuarios

| Coluna           | Tipo          | Descrição                                    |
|------------------|---------------|----------------------------------------------|
| id               | bigint PK     |                                              |
| perfil           | enum          | admin_rede, dir_nucleo, dir_escolar, coordenador, professor, aluno |
| nome             | varchar(200)  |                                              |
| email            | varchar(150)  | Único no sistema                            |
| cpf              | char(11)      | Apenas dígitos                              |
| password         | varchar(255)  | Hash bcrypt                                 |
| ativo            | boolean       |                                              |
| remember_token   | varchar(100)  |                                              |
| created_at       | timestamp     |                                              |
| updated_at       | timestamp     |                                              |

**Índices:** email (unique), cpf (unique)

---

### usuario_escopos

Vínculo entre usuário e seu contexto institucional.

| Coluna      | Tipo               | Descrição                                      |
|-------------|-------------------|------------------------------------------------|
| id          | bigint PK         |                                                |
| usuario_id  | FK → usuarios     |                                                |
| escopo_tipo | enum              | rede, nucleo, escola, turma, aluno            |
| escopo_id   | bigint            | ID do registro correspondente ao tipo          |
| created_at  | timestamp         |                                                |

**Nota:** Um professor pode ter múltiplas turmas; usa múltiplas linhas com escopo_tipo='turma'

---

### provas

| Coluna            | Tipo            | Descrição                                    |
|-------------------|-----------------|----------------------------------------------|
| id                | bigint PK       |                                              |
| escola_id         | FK → escolas    |                                              |
| criado_por        | FK → usuarios   | Professor ou Coordenador criador             |
| titulo            | varchar(255)    |                                              |
| disciplina        | varchar(100)    |                                              |
| serie             | varchar(50)     |                                              |
| num_questoes      | tinyint         | 1–100                                       |
| num_alternativas  | tinyint         | 3, 4 ou 5                                   |
| nota_maxima       | decimal(5,2)    | Padrão: 10.0                                |
| tipo_pontuacao    | enum            | igual, personalizado                         |
| anular_se_todas   | boolean         | Anular questão se todas marcadas            |
| gerar_cartao_pdf  | boolean         | Gerar cartão-resposta em PDF                |
| status            | enum            | rascunho, publicada, em_correcao, corrigida |
| data_aplicacao    | date            | Nullable                                    |
| created_at        | timestamp       |                                              |
| updated_at        | timestamp       |                                              |

**Índices:** escola_id, criado_por, status, disciplina

---

### prova_turmas

Associação entre prova e turmas onde foi/será aplicada.

| Coluna     | Tipo         | Descrição |
|------------|--------------|-----------|
| prova_id   | FK → provas  |           |
| turma_id   | FK → turmas  |           |

**PK composta:** (prova_id, turma_id)

---

### gabaritos

| Coluna         | Tipo          | Descrição                           |
|----------------|---------------|-------------------------------------|
| id             | bigint PK     |                                     |
| prova_id       | FK → provas   | Unique (uma prova = um gabarito)    |
| publicado_por  | FK → usuarios |                                     |
| publicado_em   | timestamp     |                                     |
| created_at     | timestamp     |                                     |
| updated_at     | timestamp     |                                     |

---

### gabarito_questoes

| Coluna        | Tipo             | Descrição                                 |
|---------------|------------------|-------------------------------------------|
| id            | bigint PK        |                                           |
| gabarito_id   | FK → gabaritos   |                                           |
| numero_questao| tinyint          | 1 a N                                    |
| alternativa   | char(1)          | A, B, C, D ou E                          |
| peso          | decimal(5,2)     | Peso da questão (padrão: 1.0)            |
| anulada       | boolean          | Questão anulada manualmente              |

**Índice:** (gabarito_id, numero_questao) unique

---

### cartoes

Representa o cartão-resposta físico de um aluno em uma prova.

| Coluna           | Tipo            | Descrição                                  |
|------------------|-----------------|--------------------------------------------|
| id               | bigint PK       |                                            |
| prova_id         | FK → provas     |                                            |
| aluno_id         | FK → alunos     | Nullable até vinculação                   |
| imagem_url       | varchar(500)    | URL da imagem armazenada                  |
| status           | enum            | pendente, lido, ambiguo                   |
| confianca_geral  | decimal(5,4)    | 0.0000 a 1.0000                           |
| resolvido_por    | FK → usuarios   | Nullable                                  |
| resolvido_em     | timestamp       | Nullable                                  |
| revisado_por     | FK → usuarios   | Nullable                                  |
| revisado_em      | timestamp       | Nullable                                  |
| created_at       | timestamp       |                                            |
| updated_at       | timestamp       |                                            |

**Índices:** prova_id, aluno_id, status

---

### cartao_respostas

Respostas detectadas pelo OMR para cada questão do cartão.

| Coluna          | Tipo             | Descrição                              |
|-----------------|------------------|----------------------------------------|
| id              | bigint PK        |                                        |
| cartao_id       | FK → cartoes     |                                        |
| numero_questao  | tinyint          |                                        |
| alternativa     | char(1)          | Nullable (em branco ou ambígua)        |
| confianca       | decimal(5,4)     | Confiança da leitura desta questão     |
| ambigua         | boolean          | true se múltiplas marcações detectadas |
| alternativas_detectadas | json    | Array de alternativas com confiança individual |

**Índice:** (cartao_id, numero_questao) unique

---

### notas

Nota calculada após correção do cartão.

| Coluna             | Tipo          | Descrição                                  |
|--------------------|---------------|--------------------------------------------|
| id                 | bigint PK     |                                            |
| cartao_id          | FK → cartoes  | Unique (um cartão = uma nota)             |
| prova_id           | FK → provas   |                                            |
| aluno_id           | FK → alunos   |                                            |
| turma_id           | FK → turmas   |                                            |
| acertos            | tinyint       |                                            |
| total_questoes     | tinyint       |                                            |
| nota_final         | decimal(5,2)  |                                            |
| status_aprovacao   | enum          | aprovado, recuperacao                      |
| acertos_por_tema   | json          | {tema: percentual} para relatórios        |
| created_at         | timestamp     |                                            |
| updated_at         | timestamp     |                                            |

**Índices:** prova_id, aluno_id, turma_id

---

### ambiguidade_logs

Log de resoluções manuais de cartões ambíguos.

| Coluna              | Tipo              | Descrição                         |
|---------------------|-------------------|-----------------------------------|
| id                  | bigint PK         |                                   |
| cartao_id           | FK → cartoes      |                                   |
| cartao_resposta_id  | FK → cartao_respostas |                               |
| usuario_id          | FK → usuarios     | Quem resolveu                     |
| alternativa_escolhida | char(1)         |                                   |
| created_at          | timestamp         |                                   |

---

### visitas

Visitas pedagógicas agendadas pelo Diretor de Núcleo.

| Coluna        | Tipo           | Descrição                                  |
|---------------|----------------|--------------------------------------------|
| id            | bigint PK      |                                            |
| nucleo_id     | FK → nucleos   |                                            |
| escola_id     | FK → escolas   |                                            |
| agendado_por  | FK → usuarios  |                                            |
| data_visita   | date           |                                            |
| tipo          | varchar(100)   | Ex.: "visita pedagógica", "acompanhamento" |
| urgencia      | enum           | prioritaria, monitorar, referencia         |
| created_at    | timestamp      |                                            |
| updated_at    | timestamp      |                                            |

---

### sincronizacoes_seges

Log de sincronizações com o SEGES.

| Coluna          | Tipo       | Descrição                          |
|-----------------|------------|------------------------------------|
| id              | bigint PK  |                                    |
| status          | enum       | sucesso, erro, parcial             |
| iniciado_em     | timestamp  |                                    |
| concluido_em    | timestamp  | Nullable                          |
| duracao_minutos | int        | Calculado após conclusão          |
| detalhes        | json       | Detalhes de erros ou avisos       |

---

## Relacionamentos Chave

```
Rede (1:N) Nucleo
Nucleo (1:N) Escola
Escola (1:N) Turma
Turma (1:N) Aluno
Escola (1:N) Usuario (via usuario_escopos)
Turma (1:N) Usuario/Professor (via usuario_escopos)
Escola (1:N) Prova
Prova (N:M) Turma (via prova_turmas)
Prova (1:1) Gabarito
Gabarito (1:N) GabaritoQuestao
Prova (1:N) Cartao
Cartao (1:1) Aluno (após vinculação)
Cartao (1:N) CartaoResposta
Cartao (1:1) Nota (após correção)
```

---

## Considerações para Evolução Futura

### Banco de Questões (Fase 2)
Adicionar entidades:
- `questoes` (id, disciplina_id, assunto, habilidade, nivel_dificuldade, serie, enunciado)
- `alternativas_questao` (id, questao_id, letra, texto, correta)
- `disciplinas`
- `habilidades`

### Múltiplas Versões de Prova (Fase 4)
Adicionar:
- `prova_versoes` (id, prova_id, versao, ordem_questoes_json, gabarito_id)
- Cartão terá FK para `prova_versoes` além de `provas`

### Sistema de Pontuação Personalizado (Fase 5)
- Campo `peso` em `gabarito_questoes` já previsto
- Adicionar campo `pontos_questao` configurável

Estes campos não precisam ser implementados no MVP, mas a estrutura não deve impossibilitá-los.
