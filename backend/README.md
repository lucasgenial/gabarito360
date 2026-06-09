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

## Servidor local

Inicie o servidor:

```bash
php artisan serve
```

A API ficará disponível, por padrão, em `http://127.0.0.1:8000`.

## Health check

Teste o endpoint:

```bash
curl http://127.0.0.1:8000/api/health
```

Resposta esperada:

```json
{
  "success": true,
  "message": "API Gabarito360 online",
  "data": {
    "app": "Gabarito360",
    "status": "online"
  }
}
```

## Testes

Execute a suíte:

```bash
php artisan test
```

Execute somente o teste do health check:

```bash
php artisan test --filter=HealthTest
```

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

- `Actions`: operações de aplicação com responsabilidade única.
- `DTOs`: transporte tipado de dados entre camadas.
- `Enums`: valores controlados e reutilizáveis.
- `Requests`: validação e autorização de entrada.
- `Resources`: transformação de respostas da API.
- `Jobs`: processamento assíncrono por filas.
- `Services`: integrações e serviços compartilhados.
- `Policies`: autorização por recurso.
- `Observers`: observação de eventos de models.
- `Support`: utilitários técnicos compartilhados.

## Padrão de resposta JSON

Respostas de sucesso usam:

```json
{
  "success": true,
  "message": "Mensagem",
  "data": {}
}
```

Respostas de erro usam:

```json
{
  "success": false,
  "message": "Mensagem",
  "data": null,
  "errors": {}
}
```
