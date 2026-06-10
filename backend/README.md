# Backend Gabarito360

API REST do Gabarito360, criada com Laravel 12 e preparada para evoluir com PostgreSQL, Laravel Sanctum, filas, jobs, policies, form requests, resources, services e actions.

Esta etapa contém somente a base técnica. Autenticação, CRUDs, models do domínio, dashboards, WebSockets e OMR ainda não foram implementados.

## Requisitos

- PHP 8.3 ou superior recomendado.
- Composer 2.
- PostgreSQL 14 ou superior.
- Extensões PHP exigidas pelo Laravel.
- Extensão PHP `pdo_pgsql` habilitada.

## Instalação

Entre na pasta do backend:

```bash
cd backend
```

Instale as dependências:

```bash
composer install
```

Crie o arquivo de ambiente:

```bash
cp .env.example .env
```

No PowerShell:

```powershell
Copy-Item .env.example .env
```

Gere a chave da aplicação:

```bash
php artisan key:generate
```

## PostgreSQL

Crie o banco `gabarito360` e configure o `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gabarito360
DB_USERNAME=postgres
DB_PASSWORD=
```

Execute as migrations técnicas do Laravel e Sanctum:

```bash
php artisan migrate
```

Não existem migrations de negócio nesta etapa.

### Banco de testes

Os testes de integração usam exclusivamente a conexão `pgsql_testing` e o banco descartável `gabarito360_testing`. Crie esse banco separado antes de executar migrations ou testes:

```sql
CREATE DATABASE gabarito360_testing;
```

O ambiente `testing` nunca utiliza SQLite nem a conexão PostgreSQL local. A migration técnica habilita somente as extensões aprovadas `pgcrypto` e `citext`.

```bash
php artisan migrate:fresh --env=testing
php artisan test --filter=DatabaseTest
```

O teste interrompe antes da conexão caso o banco configurado não possua um nome explicitamente identificado como banco de teste.

## Servidor local

Inicie o servidor:

```bash
php artisan serve
```

A API ficará disponível, por padrão, em `http://127.0.0.1:8000`.

## Health check

Teste o endpoint:

```bash
curl http://127.0.0.1:8000/api/v1/health
```

Resposta esperada:

```json
{
  "data": {
    "app": "Gabarito360",
    "status": "online"
  },
  "meta": {
    "request_id": "uuid"
  }
}
```

## Baseline validada

A baseline tecnica foi validada em 10 de junho de 2026 e deve permanecer sem regras de negocio ate os micropassos correspondentes.

| Componente | Versao congelada pelo `composer.lock` |
|---|---:|
| Laravel Framework | `12.62.0` |
| Laravel Sanctum | `4.3.2` |
| PHPUnit | `11.5.55` |
| Laravel Pint | `1.29.1` |

Escopo validado:

- `GET /api/v1/health` responde JSON conforme o contrato documentado.
- A unica rota funcional da API e `/api/v1/health`; a rota `/` permanece como pagina web padrao.
- A suite possui testes tecnicos da baseline e do contrato da API.
- Autenticacao, models de dominio e endpoints de negocio ainda nao fazem parte desta base.

Gates para revalidar a baseline:

```bash
composer validate --strict
php artisan test
php vendor/bin/pint --test
php artisan route:list --except-vendor
```

Pre-condicoes do ambiente:

- O alvo operacional do projeto e PHP 8.3 ou superior. Laravel 12 pode iniciar em PHP 8.2, mas isso nao aprova esse runtime para homologacao ou producao.
- A extensao `pdo_pgsql` deve estar habilitada para migrations e testes PostgreSQL.
- O MP-008 nao cria migrations de negocio nem valida Redis ou storage S3.

## Testes

Execute a suíte:

```bash
php artisan test
```

Execute somente o teste do health check:

```bash
php artisan test --filter=HealthTest
```

## Convencoes e qualidade

As convencoes obrigatorias de arquitetura, testes, branches, commits e revisao estao em [`../CONTRIBUTING.md`](../CONTRIBUTING.md). A especificacao do contrato REST esta em [`../docs/07-api.md`](../docs/07-api.md).

Antes de um commit, execute a partir de `backend/`:

```bash
vendor/bin/pint --test
php artisan test --filter=NomeDoTeste
```

No PowerShell, substitua `vendor/bin/pint` por `vendor\bin\pint.bat`.

Antes de abrir um pull request:

```bash
composer validate --strict
vendor/bin/pint --test
php artisan test
```

Quando aplicavel:

```bash
php artisan route:list --except-vendor
npm run build
```

- `route:list` e obrigatorio quando rotas forem alteradas.
- `npm run build` e obrigatorio quando assets do painel forem alterados.
- Analise estatica ainda nao esta configurada e nao deve ser simulada por comandos nao versionados.

## Rotas

Liste as rotas da aplicação:

```bash
php artisan route:list --except-vendor
```

## Sanctum

O Laravel Sanctum está instalado como preparação para autenticação futura. Nenhum endpoint de login, emissão de token ou rota protegida foi implementado nesta etapa.

## Estrutura inicial

```text
app/
|-- Actions/
|-- DTOs/
|-- Enums/
|-- Http/
|   |-- Controllers/
|   |   |-- Api/
|   |   \-- Web/
|   |-- Requests/
|   \-- Resources/
|-- Jobs/
|-- Models/
|-- Observers/
|-- Policies/
|-- Services/
\-- Support/
```

- `Actions`: casos de uso unicos; coordenam regras, persistencia e transacao.
- `DTOs`: transporte tipado de dados entre camadas.
- `Enums`: valores controlados e reutilizáveis.
- `Requests`: validacao de formato, tipos, limites e coerencia da entrada.
- `Resources`: representacao e minimizacao das respostas da API.
- `Jobs`: processamento assincrono idempotente e posterior ao commit.
- `Services`: capacidades reutilizaveis, algoritmos e integracoes.
- `Policies`: autorizacao por acao, recurso e escopo.
- `Observers`: reacoes simples e nao criticas a eventos de models.
- `Support`: utilitários técnicos compartilhados.

Controllers devem permanecer finos e reutilizar Actions, Policies e Resources. A tabela completa para decidir a camada correta esta no guia de contribuicao.

## Padrão de resposta JSON

Toda resposta da API segue o contrato canonico definido em [`../docs/07-api.md`](../docs/07-api.md). O backend gera um UUID quando `X-Request-ID` nao e informado ou e invalido, devolve o identificador no header e em `meta.request_id` e o compartilha com o contexto dos logs.

Respostas de sucesso usam:

```json
{
  "data": {},
  "meta": {
    "request_id": "uuid"
  }
}
```

Respostas de erro usam:

```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Os dados informados sao invalidos.",
    "details": {}
  },
  "meta": {
    "request_id": "uuid"
  }
}
```

Codigos de erro sao estaveis e usam `UPPER_SNAKE_CASE`. Excecoes internas nao expoem mensagens ou detalhes tecnicos ao cliente.
