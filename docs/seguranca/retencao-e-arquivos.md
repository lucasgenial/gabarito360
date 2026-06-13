# Gabarito360 - Retencao, Arquivos e Descarte

## 1. Objetivo

Este documento operacionaliza a classificacao, o acesso, a retencao e o descarte de arquivos e registros do Gabarito360 conforme o [ADR-D006](../decisoes/ADR-D006-retencao-imagens-logs.md). Ele nao implementa jobs de descarte nem provisiona storage.

## 2. Principios

- Coletar, armazenar e expor somente o necessario para a finalidade declarada.
- Manter todo arquivo identificavel em storage privado.
- Registrar metadados no MariaDB; nao armazenar imagens como blob.
- Preencher `arquivos.retencao_ate` ao classificar o arquivo.
- Autorizar e auditar acesso, download, retencao legal e descarte.
- Nunca usar nome, matricula, CPF ou codigo impresso no caminho do objeto.
- Nao manter copias acessiveis depois do prazo aplicavel.

## 3. Niveis de classificacao

| Nivel | Definicao | Exemplos | Controle minimo |
|---|---|---|---|
| Publico | Conteudo aprovado para divulgacao | Documentacao publica sem dados reais | Integridade e revisao |
| Interno | Conteudo operacional sem dado pessoal identificavel | Modelo vazio de cartao, metricas agregadas | Acesso da equipe autorizada |
| Confidencial | Conteudo com dados organizacionais ou pessoais limitados | Importacao de alunos, relatorio por turma | Storage privado, autorizacao e auditoria |
| Restrito | Conteudo sensivel, evidencias ou credenciais | Imagem de cartao, auditoria, segredo | Menor privilegio, criptografia, auditoria e retencao estrita |

Segredos seguem a politica de [ambientes e segredos](../infra/ambientes.md) e nunca devem ser armazenados na tabela `arquivos`.

## 4. Classes de arquivo

| Classe operacional | Classificacao | Acesso | Prazo e inicio da contagem | Descarte |
|---|---|---|---|---|
| `cartao_original_confirmado` | Restrito | Perfis autorizados no escopo; suporte somente com autorizacao auditada | 180 dias apos finalizacao da aplicacao | Excluir objeto e auditar; preservar somente metadados permitidos |
| `cartao_original_nao_confirmado` | Restrito | Aplicador vinculado e diagnostico autorizado | 30 dias apos captura | Excluir objeto e auditar |
| `cartao_processado` | Restrito | Aplicador vinculado e diagnostico autorizado | 30 dias apos processamento | Excluir objeto e auditar |
| `artefato_diagnostico_omr` | Restrito | Equipe tecnica autorizada | 30 dias apos processamento | Excluir objeto e auditar |
| `exportacao` | Confidencial ou Restrito conforme conteudo | Solicitante e perfis autorizados no escopo | 7 dias apos geracao | Revogar download, excluir objeto e auditar |
| `importacao` | Confidencial | Solicitante e gestor autorizado da escola | 30 dias apos conclusao ou falha definitiva do processamento | Excluir arquivo fonte e auditar; preservar resultado necessario no banco |
| `modelo_cartao` | Interno; Restrito se incorporar amostra real | Gestores tecnicos e OMR autorizados | Enquanto houver prova, aplicacao, leitura ou resultado dependente; revisar apos arquivamento final | Excluir somente quando nao comprometer reprodutibilidade e houver autorizacao |
| `dataset_omr_sintetico` | Interno | Equipe OMR e QA | Enquanto util e versionado | Descartar por revisao tecnica |
| `dataset_omr_real` | Restrito | Excepcional, autorizado e auditado | Deve usar copia minimizada dentro do prazo da imagem de origem | Excluir no menor prazo aplicavel |

O prazo de 30 dias para `importacao` complementa operacionalmente o ADR-D006 e deve ser revalidado antes da homologacao. Arquivos de importacao nao substituem o historico relacional validado.

## 5. Registros nao armazenados como arquivo

| Registro | Prazo | Inicio da contagem | Regra |
|---|---:|---|---|
| Logs tecnicos da aplicacao | 90 dias | Evento | Sem imagens, senhas, tokens ou dados pessoais desnecessarios |
| Logs de sincronizacao mobile | 180 dias | Processamento | Preservar idempotencia e erro minimo necessario |
| Auditorias de negocio e seguranca | 5 anos | Evento | Imutaveis para usuarios operacionais |

Cache, sessoes, filas e arquivos temporarios nao sao historico. Devem possuir expiracao curta compativel com sua finalidade e nunca ser usados para contornar a politica de retencao.

## 6. Armazenamento e acesso

- `cartao_original_*`, `cartao_processado`, `artefato_diagnostico_omr`, `importacao` e `exportacao` usam disco privado.
- Cada objeto recebe caminho interno imprevisivel, MIME validado pelo conteudo, tamanho e checksum SHA-256.
- O nome original pode existir apenas como metadado protegido e nao define o caminho.
- Downloads exigem Policy, escopo e URL temporaria; o caminho fisico nao e retornado ao cliente.
- Acesso de suporte e diagnostico exige justificativa, prazo e auditoria.
- O disco `public` nao recebe arquivos de negocio.

## 7. Ciclo de vida

### 7.1 Criacao

1. Validar autorizacao, MIME real, tamanho e finalidade.
2. Classificar o objeto e definir escopo organizacional.
3. Armazenar no disco privado do ambiente.
4. Registrar checksum, criador, classificacao e `retencao_ate`.
5. Auditar a operacao quando aplicavel.

### 7.2 Acesso

1. Autenticar o solicitante.
2. Autorizar acao e escopo no backend.
3. Registrar acesso quando o conteudo for restrito.
4. Entregar por resposta controlada ou URL temporaria.

### 7.3 Descarte

1. Selecionar objetos com prazo vencido e sem retencao legal ativa.
2. Excluir o objeto fisico do storage privado.
3. Remover ou anonimizar metadados que nao precisem permanecer.
4. Registrar classificacao, identificador, motivo, data e resultado do descarte.
5. Alertar e tentar novamente em caso de falha, sem marcar descarte como concluido.

O job automatico, seus testes e alertas serao implementados no MP-055.

## 8. Retencao legal e investigacao

Uma suspensao de descarte exige:

- justificativa e finalidade documentadas;
- autorizacao de Seguranca e responsavel competente;
- classes e objetos abrangidos;
- inicio, prazo de revisao e responsavel;
- auditoria de criacao, renovacao e encerramento.

A suspensao nao amplia acesso ao conteudo. Encerrada a necessidade, o objeto retorna ao descarte pelo prazo original ou e descartado imediatamente quando vencido.

## 9. Backups e restauracao

- Backups devem ser criptografados, isolados e acessiveis somente pela equipe autorizada.
- A politica detalhada e os scripts de backup/restauracao estao em
  [`../operacao/backup-e-restauracao.md`](../operacao/backup-e-restauracao.md).
- Restaurar um backup nao pode reativar permanentemente arquivos cujo prazo ja venceu.
- Apos restauracao, o processo de descarte deve reaplicar `retencao_ate` e retencoes legais vigentes.
- Testes de restauracao usam dados sinteticos ou anonimizados sempre que possivel.

## 10. Regras por ambiente

| Ambiente | Regra |
|---|---|
| Local | Somente arquivos sinteticos ou anonimizados; armazenamento privado local e descartavel |
| Test | Fixtures sinteticas temporarias; limpeza ao final da suite |
| Homologacao | Storage isolado; sem imagens reais identificaveis salvo autorizacao formal |
| Producao | Dados reais autorizados; acesso, lifecycle, monitoramento e descarte auditados |

Arquivos reais identificaveis e segredos nunca devem ser adicionados ao repositorio.

## 11. Responsabilidades

| Papel | Responsabilidade |
|---|---|
| Produto e Seguranca | Aprovar finalidade, classe, prazo e excecoes |
| Backend | Registrar metadados, `retencao_ate`, autorizacao e auditoria |
| Plataforma | Aplicar storage privado, criptografia, lifecycle, backup e alertas |
| Operacao autorizada | Acessar apenas pelo fluxo permitido e justificar excecoes |
| QA | Validar classificacao, acesso, expiracao e descarte sem usar dados reais |

## 12. Checklist de verificacao

- O arquivo possui classificacao e `retencao_ate` coerentes?
- O objeto esta em storage privado e no ambiente correto?
- O caminho evita identificadores pessoais?
- O acesso exige Policy e escopo?
- O download expira e e auditado quando necessario?
- O descarte elimina o objeto e registra resultado?
- Logs e auditorias respeitam os prazos do ADR-D006?
- Retencao legal possui justificativa, responsavel e revisao?
