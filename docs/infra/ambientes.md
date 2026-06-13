# Gabarito360 - Ambientes e Segredos

## 1. Objetivo

Este documento define configuracoes, isolamento e responsabilidades para os ambientes `local`, `test`, `homologacao` e `producao`. Ele nao provisiona infraestrutura e deve ser aplicado junto do [ADR-D006](../decisoes/ADR-D006-retencao-imagens-logs.md), dos requisitos nao funcionais e do guia de contribuicao.

## 2. Principios obrigatorios

- Cada ambiente usa banco, Redis, storage, credenciais, URLs e chaves proprios.
- `test` e `homologacao` nunca apontam para servicos ou dados de producao.
- Segredos sao injetados no ambiente e nunca versionados, embutidos em imagens ou registrados em logs.
- MariaDB, Redis e storage privado nao ficam expostos publicamente.
- `APP_DEBUG` deve ser `false` fora do ambiente local.
- Homologacao deve ser representativa de producao sem utilizar dados pessoais reais, salvo processo formal autorizado e minimizado.
- Objetos identificaveis sao privados e seguem [retencao-e-arquivos.md](../seguranca/retencao-e-arquivos.md).

## 3. Matriz de ambientes

| Item | Local | Test | Homologacao | Producao |
|---|---|---|---|---|
| Finalidade | Desenvolvimento individual | Testes automatizados isolados | Validacao integrada e aceite | Operacao real |
| Dados permitidos | Sinteticos ou anonimizados | Fixtures sinteticas | Sinteticos ou anonimizados | Dados reais autorizados |
| `APP_ENV` | `local` | `testing` | `staging` | `production` |
| `APP_DEBUG` | `true` | `false` | `false` | `false` |
| MariaDB | Instancia portatil local, banco `gabarito360` | Banco descartavel e exclusivo, nunca compartilhado | Container/instancia e credencial exclusivas | Instancia privada, monitorada e com backup |
| Redis | Alvo do projeto; banco/prefixo local exclusivo | `array`/`sync` por padrao ou Redis exclusivo no teste de integracao | Redis privado exclusivo | Redis privado, monitorado e sem persistir dado pessoal desnecessario |
| Cache e filas | Redis no Compose ou `array`/`sync` no ambiente portatil minimo | `array` e `sync`, salvo teste especifico | Redis com workers controlados | Redis com workers monitorados |
| Storage | Disco `local` privado | Diretorio temporario descartavel | Bucket/prefixo S3 compativel exclusivo e privado | Bucket S3 compativel privado, criptografado e com lifecycle |
| E-mail | Driver `log`; nao envia externamente | Driver `array` | SMTP sandbox com destinatarios controlados | Provedor transacional autorizado |
| Logs | Locais, sem dados sensiveis | Minimos e descartaveis | Centralizados e restritos | Centralizados, monitorados e retidos por 90 dias |
| Segredos | `.env` local ignorado pelo Git | Valores efemeros sem privilegio | Cofre de segredos ou variaveis protegidas do deploy | Cofre de segredos com acesso minimo e rotacao |
| Acesso | Desenvolvedor local | Pipeline e desenvolvedor | Equipe autorizada | Equipe operacional autorizada e auditada |

O Compose do R7 fornece Redis, workers e Reverb para homologacao tecnica.
Storage S3 compativel, e-mail externo e observabilidade gerenciada dependem do
ambiente implantado.

## 4. Configuracoes por componente

### 4.1 Aplicacao

| Variavel | Regra |
|---|---|
| `APP_KEY` | Segredo unico por ambiente; gerar no destino e nunca copiar entre ambientes |
| `APP_URL` | URL propria; HTTPS obrigatorio em homologacao e producao |
| `APP_DEBUG` | `false` em `test`, homologacao e producao |
| `APP_LOCALE` | `pt_BR`; timestamps persistidos de forma consistente e exibidos no fuso do usuario |
| `LOG_LEVEL` | `debug` somente local; `info` ou mais restritivo fora do local |
| `LOG_DAILY_DAYS` | Nao ultrapassar 90 dias em logs tecnicos acessiveis |

### 4.2 MariaDB

- Usar banco e usuario exclusivos por ambiente, com privilegio minimo.
- Producao e homologacao devem exigir conexao protegida e rede privada.
- Testes devem falhar antes de executar caso o banco configurado nao seja reconhecido como banco de teste.
- Migrations devem ser executadas por processo controlado; a aplicacao nao recebe privilegio administrativo desnecessario.
- Backups devem atender inicialmente RPO de ate 24 horas e RTO de ate 8 horas, com restauracao testada antes da producao.

### 4.3 Redis

- Usar credencial, prefixo e bases logicas exclusivas por ambiente.
- Redis nao deve ser usado como armazenamento permanente de resultado, auditoria ou arquivo.
- Cache nao deve conter dados pessoais alem do estritamente necessario e deve possuir expiracao.
- Filas devem transportar IDs e contexto minimo, evitando payloads completos com dados pessoais.
- A configuracao de filas deve garantir que efeitos dependentes de persistencia ocorram somente apos o commit.

### 4.4 Storage

- Arquivos de negocio usam storage privado; o disco `public` nao deve receber cartoes, importacoes ou relatorios.
- Homologacao e producao usam buckets ou prefixos totalmente separados.
- Caminhos usam identificadores internos e nunca nome de aluno, matricula, CPF ou codigo impresso.
- Downloads exigem autorizacao e URL temporaria; URLs permanentes publicas sao proibidas.
- Lifecycle do storage deve refletir a classificacao e `retencao_ate` registrada no banco.

### 4.5 E-mail

- Local usa `log` e test usa `array`.
- Homologacao usa sandbox e impede envio para destinatarios nao autorizados.
- Producao usa dominio verificado, TLS e credencial exclusiva.
- E-mails nao devem anexar cartoes ou exportacoes com dados pessoais; devem fornecer acesso autenticado quando necessario.

## 5. Politica de segredos

### 5.1 O que e segredo

Sao segredos: `APP_KEY`, senhas de banco e Redis, credenciais SMTP, tokens, chaves privadas, credenciais S3, webhooks autenticados e qualquer valor que conceda acesso.

Nao sao segredos, mas ainda exigem revisao: nomes de host internos, nomes de bucket, IDs de conta, URLs privadas e configuracoes que revelem topologia.

### 5.2 Armazenamento e entrega

- O repositorio versiona somente `.env.example` com valores seguros, vazios ou locais.
- O `.env` local permanece ignorado pelo Git e nao deve ser enviado por chat, chamado ou captura de tela.
- Homologacao e producao recebem segredos por cofre ou mecanismo protegido do deploy.
- Pipelines acessam somente os segredos do ambiente e etapa necessarios.
- Imagens de container e artefatos de build nao podem conter `.env` ou segredos.

### 5.3 Rotacao e incidente

- Rotacionar imediatamente um segredo suspeito de exposicao.
- Rotacionar credenciais ao remover acesso de pessoa ou servico.
- Registrar proprietario, finalidade, ambiente e data da ultima rotacao no cofre, nao no repositorio.
- Revogar primeiro, investigar impacto, auditar acessos e substituir dependencias afetadas.
- Nunca reutilizar credenciais entre ambientes.

## 6. Responsabilidades

| Papel | Responsabilidade |
|---|---|
| Desenvolvimento | Manter exemplos sem segredos, usar dados sinteticos e declarar novas variaveis |
| QA | Garantir isolamento dos testes e ausencia de dados reais nas evidencias |
| Plataforma/Infraestrutura | Provisionar redes, banco, Redis, storage, backups e cofre por ambiente |
| Seguranca | Aprovar acessos, revisar segredos, retencao e resposta a incidentes |
| Produto/Operacao | Autorizar finalidade e acesso a dados reais em producao |

## 7. Gates antes de promover

- Configuracoes e segredos pertencem ao ambiente correto.
- `APP_DEBUG=false` e HTTPS estao ativos fora do local.
- Banco, Redis e storage nao estao publicamente expostos.
- Storage privado aplica acesso e lifecycle esperados.
- E-mail de homologacao nao alcanca destinatario real nao autorizado.
- Logs nao apresentam senhas, tokens, imagens ou dados pessoais desnecessarios.
- Backup e restauracao possuem procedimento validado antes da producao.

## 8. Referencias

- [Retencao e arquivos](../seguranca/retencao-e-arquivos.md)
- [ADR-D006](../decisoes/ADR-D006-retencao-imagens-logs.md)
- [Requisitos nao funcionais](../03-requisitos-nao-funcionais.md)
- [Guia de contribuicao](../../CONTRIBUTING.md)
