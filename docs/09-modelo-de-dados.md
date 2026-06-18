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

### secretarias (novo — MP-025, SaaS)

Nível opcional acima da Rede. Só existe quando uma secretaria (geralmente estadual)
supervisiona múltiplas redes (geralmente municipais) dentro da mesma assinatura.

| Coluna     | Tipo        | Descrição                              |
|------------|-------------|------------------------------------------|
| id         | bigint PK   |                                          |
| nome       | varchar(200)| Nome da secretaria                      |
| tipo       | enum        | estadual, federal, consorcio            |
| uf         | char(2)     | Unidade federativa                      |
| usuario_titular_id | FK → usuarios | Titular da assinatura (RN-015.2)  |
| created_at | timestamp   |                                          |
| updated_at | timestamp   |                                          |

---

### redes

| Coluna           | Tipo        | Descrição                         |
|------------------|-------------|-----------------------------------|
| id               | bigint PK   | Identificador único               |
| secretaria_id    | FK → secretarias (nullable) | Preenchido apenas quando supervisionada por uma secretaria |
| modalidade       | enum        | institucional, individual (novo — MP-025, RN-002.4) |
| usuario_titular_id | FK → usuarios (nullable) | Titular da assinatura quando `modalidade=individual` ou quando a rede institucional foi criada via cadastro autônomo (RN-015.2) |
| nome             | varchar(200)| Nome da rede de ensino            |
| tipo             | enum        | municipal, estadual, federal      |
| uf               | char(2)     | Unidade federativa                |
| meta_media       | decimal(4,2)| Meta de média da rede (ex.: 7.0)  |
| meta_minima      | decimal(4,2)| Nota mínima de aprovação (ex.: 6.0)|
| limiar_seges_min | int         | Minutos de atraso aceitáveis SEGES|
| created_at       | timestamp   |                                   |
| updated_at       | timestamp   |                                   |

**Nota:** `tipo` (municipal/estadual/federal) e `modalidade` (institucional/individual)
são campos independentes — uma rede individual de um professor autônomo tem
`modalidade=individual` e `tipo` é irrelevante/null nesse caso.

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
| cpf              | char(11)     | Opcional. Apenas dígitos               |
| data_nascimento  | date         |                                       |
| genero           | enum         | M, F, O (opcional)                    |
| foto_path        | varchar(255) | Caminho do arquivo no disco `public` (opcional) |
| nome_responsavel | varchar(200) |                                       |
| ativo            | boolean      |                                       |
| created_at       | timestamp    |                                       |
| updated_at       | timestamp    |                                       |

**Índices:** turma_id, matricula (unique por rede + ano_letivo), cpf (unique, permite múltiplos nulos)

**Adicionado em:** MP-024 (auditoria mockup `aluno-cadastrar.html`) — cpf, genero, foto_path.

---

### usuarios

| Coluna                 | Tipo          | Descrição                                    |
|------------------------|---------------|----------------------------------------------|
| id                     | bigint PK     |                                              |
| perfil                 | enum          | secretario_educacao, admin_rede, dir_nucleo, dir_escolar, coordenador, professor, aplicador, aluno (8 perfis — secretario_educacao e aplicador adicionados na MP-026, SaaS) |
| nome                   | varchar(200)  |                                              |
| email                  | varchar(150)  | Único no sistema                            |
| cpf                    | char(11)      | Apenas dígitos                              |
| password               | varchar(255)  | Hash bcrypt                                 |
| ativo                  | boolean       |                                              |
| escola_nome            | varchar(200)  | Denormalizado para exibição rápida (opcional) |
| ultimo_acesso          | timestamp     | Atualizado a cada login                     |
| data_nascimento        | date          | Opcional                                    |
| telefone               | varchar(20)   | Opcional                                    |
| data_ingresso          | date          | Data de início na rede/escola (opcional)    |
| formacao_academica     | varchar(200)  | Graduação/licenciatura (opcional)           |
| especializacao         | varchar(200)  | Opcional                                    |
| registro_profissional  | varchar(50)   | Opcional                                    |
| observacoes            | text          | Anotações internas (opcional)               |
| forcar_troca_senha     | boolean       | Default false. Exige troca no próximo login |
| remember_token         | varchar(100)  |                                              |
| created_at             | timestamp     |                                              |
| updated_at             | timestamp     |                                              |

**Índices:** email (unique), cpf (unique)

**Adicionado em:** MP-024 (auditoria mockups `membro-cadastrar.html`/`membro-editar.html`) — data_nascimento, telefone, data_ingresso, formacao_academica, especializacao, registro_profissional, observacoes, forcar_troca_senha. O vínculo com escola já existia via `usuario_escopos` (escopo_tipo='escola').

**Escopo não coberto nesta rodada:** os mockups também previam uma grade de "Disciplinas e Turmas" (checkboxes) para professores e exclusão permanente de membro — ambos exigem modelagem adicional (relação usuário↔disciplina; revisão de FKs antes de permitir delete) e foram deixados para decisão futura.

---

### usuario_escopos

Vínculo entre usuário e seu contexto institucional.

| Coluna      | Tipo               | Descrição                                      |
|-------------|-------------------|------------------------------------------------|
| id          | bigint PK         |                                                |
| usuario_id  | FK → usuarios     |                                                |
| escopo_tipo | enum              | secretaria, rede, nucleo, escola, turma, aluno (secretaria adicionado na MP-026, SaaS) |
| escopo_id   | bigint            | ID do registro correspondente ao tipo          |
| created_at  | timestamp         |                                                |

**Nota:** Um professor pode ter múltiplas turmas; usa múltiplas linhas com escopo_tipo='turma'

**Nota — APLICADOR:** usa escopo_tipo='escola' (como dir_escolar/coordenador), mas suas
permissões efetivas dentro da escola são restritas pelo motor de permissões
configuráveis (MP-029/030), não pelo escopo em si.

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

### planos (novo — MP-027, SaaS)

Catálogo de planos comerciais. Gerenciado apenas pela equipe do Gabarito360 (RN-015.1).

| Coluna       | Tipo          | Descrição                                      |
|--------------|---------------|--------------------------------------------------|
| id           | bigint PK     |                                                  |
| nome         | varchar(100)  | Ex.: "Professor Individual", "Rede Municipal"   |
| publico      | enum          | individual, institucional                       |
| preco        | decimal(10,2) |                                                  |
| periodicidade| enum          | mensal, anual                                    |
| limites      | json          | Ex.: {"escolas": 1, "turmas": 10, "usuarios": 5} |
| ativo        | boolean       | Plano disponível para novas assinaturas          |
| created_at   | timestamp     |                                                  |
| updated_at   | timestamp     |                                                  |

---

### assinaturas (novo — MP-027, SaaS)

| Coluna                | Tipo          | Descrição                                      |
|-----------------------|---------------|--------------------------------------------------|
| id                    | bigint PK     |                                                  |
| plano_id              | FK → planos   |                                                  |
| titular_id            | FK → usuarios | Usuário titular (RN-015.2)                      |
| rede_id               | FK → redes (nullable)       | Preenchido se a assinatura é de uma rede |
| secretaria_id         | FK → secretarias (nullable) | Preenchido se a assinatura é de uma secretaria |
| status                | enum          | trial, ativa, atrasada, cancelada               |
| gateway_subscription_id | varchar(100)| Referência da assinatura no Mercado Pago        |
| trial_termina_em      | timestamp (nullable) |                                           |
| proxima_cobranca_em   | timestamp (nullable) |                                           |
| created_at            | timestamp     |                                                  |
| updated_at            | timestamp     |                                                  |

---

### pagamentos (novo — MP-027, SaaS)

| Coluna                | Tipo          | Descrição                                      |
|-----------------------|---------------|--------------------------------------------------|
| id                    | bigint PK     |                                                  |
| assinatura_id         | FK → assinaturas |                                               |
| valor                 | decimal(10,2) |                                                  |
| status                | enum          | pendente, aprovado, rejeitado, reembolsado      |
| metodo                | enum          | pix, boleto, cartao                              |
| gateway_payment_id    | varchar(100)  | Referência do pagamento no Mercado Pago         |
| pago_em               | timestamp (nullable) |                                           |
| created_at            | timestamp     |                                                  |

**Nota de segurança:** nenhuma dessas tabelas armazena dados de cartão de crédito —
apenas os IDs de referência retornados pelo Mercado Pago (RN-015.3).

---

## Relacionamentos Chave

```
Secretaria (1:N) Rede [opcional]
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
Plano (1:N) Assinatura
Usuario/Titular (1:N) Assinatura
Assinatura (1:N) Pagamento
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
