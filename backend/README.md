# Backend Gabarito360

API REST e painel administrativo do Gabarito360, construídos com Laravel 12 e MariaDB. A arquitetura utiliza Sanctum, requests, resources, policies, actions, services, filas e auditoria.

A fundação R3 também disponibiliza modelos e contratos persistentes para
estrutura acadêmica, equipe, responsáveis, aplicações, leituras, resultados e
relatórios. Os fluxos e endpoints desses novos contratos permanecem reservados
para as próximas fatias funcionais.

A R4 consolidou o shell web responsivo, tema claro padrão com alternância
persistida, tokens oficiais e biblioteca Blade compartilhada. A R5 conectará
essa fundação visual aos fluxos funcionais, sem dados estáticos do mockup.

## Requisitos

- PHP 8.3 ou superior com `pdo_mysql`;
- Composer 2;
- Node.js 24 e npm;
- PowerShell 5.1 ou superior para o ambiente local portátil.

O MariaDB local não precisa estar instalado no Windows. Os scripts baixam a distribuição oficial MariaDB 11.4.8 para `.local/`, que não é versionada.

## Instalação inicial

Na raiz do repositório:

```powershell
cd backend
composer install
npm install
Copy-Item .env.example .env
php artisan key:generate
cd ..
powershell -ExecutionPolicy Bypass -File scripts/local/setup.ps1 -Fresh
```

O setup:

- baixa e inicializa o MariaDB portátil;
- inicia o servidor em `127.0.0.1:3307`;
- cria `gabarito360` e `gabarito360_testing`;
- configura `backend/.env`;
- executa migrations e seeders;
- carrega dados locais de demonstração.

Use `-Fresh` somente quando puder recriar todos os dados locais.

## Banco MariaDB

Configuração local padrão:

```dotenv
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=gabarito360
DB_USERNAME=root
DB_PASSWORD=
```

UUIDs são gerados pela aplicação. Datas são armazenadas em UTC, JSON usa o tipo `json` e regras entre múltiplas entidades ficam em actions/services transacionais com locks e testes.

Comandos manuais:

```powershell
cd backend
php artisan migrate --seed
php artisan migrate:fresh --seed
```

## Serviços locais

Na raiz do repositório:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/local/start.ps1
powershell -ExecutionPolicy Bypass -File scripts/local/stop.ps1
```

O comando `start.ps1` inicia MariaDB e Laravel. Endereços:

- painel: `http://127.0.0.1:8000/admin/login`;
- health check: `http://127.0.0.1:8000/api/v1/health`.

## Banco de testes

Os testes de integração usam exclusivamente a conexão `mariadb_testing` e o banco descartável `gabarito360_testing`.

```powershell
cd backend
php artisan migrate:fresh --env=testing --seed --force
php artisan test
```

O teste de infraestrutura interrompe a execução se o banco configurado não estiver isolado e identificado como banco de testes.

## Redis, filas e storage privado

Produção usará Redis para cache e filas. O ambiente portátil usa `array`, `sync` e storage privado local para não exigir serviços adicionais durante o desenvolvimento.

```dotenv
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_CACHE_CONNECTION=cache
REDIS_QUEUE_CONNECTION=queue
FILESYSTEM_DISK=private
FILESYSTEM_PRIVATE_DISK=private
FILESYSTEM_CLOUD=s3_private
```

Arquivos de negócio devem usar o disco indicado por `FILESYSTEM_PRIVATE_DISK`.

## Qualidade e testes

Execute a partir de `backend/`:

```powershell
composer validate --strict
php vendor/bin/pint --test
php artisan test
npm run build
npx --yes @redocly/cli@2.32.0 lint ../docs/openapi.yaml
```

O CI utiliza PHP 8.3, Node.js 24, MariaDB 11.4 e Redis 7. O pipeline instala dependências, aplica migrations e seeders, valida formatação e OpenAPI e executa a suíte completa.

## Rotas e contrato API

Liste as rotas:

```powershell
php artisan route:list --except-vendor
```

O contrato OpenAPI está em [`../docs/openapi.yaml`](../docs/openapi.yaml). Toda resposta inclui um `request_id`, compartilhado com os logs:

```json
{
  "data": {},
  "meta": {
    "request_id": "uuid"
  }
}
```

Erros usam código estável em `UPPER_SNAKE_CASE` e não expõem detalhes internos.

## Estrutura

```text
app/
|-- Actions/
|-- DTOs/
|-- Enums/
|-- Http/
|   |-- Controllers/
|   |-- Requests/
|   \-- Resources/
|-- Jobs/
|-- Models/
|-- Observers/
|-- Policies/
|-- Services/
\-- Support/
```

Controllers devem permanecer finos. Actions coordenam casos de uso e transações; services concentram capacidades reutilizáveis; policies controlam autorização e escopo.

## Design System

O painel usa os tokens oficiais de `docs/ui_token_gov_brasil.json` e componentes Blade compartilhados em `resources/views/components/ui`.

```powershell
php artisan test --filter=DesignSystemTest
npm run build
```

O contrato visual exige tema claro padrão, dark mode explícito, responsividade e WCAG 2.2 AA.
