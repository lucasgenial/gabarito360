# Gabarito360

Plataforma completa de gestão de avaliações educacionais para redes públicas de ensino.

## Estrutura do Repositório

```
gabarito360/
├── apps/
│   ├── api/        # Laravel — API REST (autenticação, regras de negócio, OMR)
│   ├── web/        # Laravel — Interface administrativa e dashboards
│   ├── android/    # React Native — App mobile Android
│   └── ios/        # React Native — App mobile iOS
├── docs/           # Documentação do projeto
├── mockups/        # Mockups HTML das telas
└── style-system/   # Design System gov.br
```

## Requisitos

- PHP 8.3+
- Composer 2+
- MariaDB 10.6+
- Node.js 18+

## Setup — API

```bash
cd apps/api
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8001
```

## Setup — WEB

```bash
cd apps/web
cp .env.example .env
composer install
php artisan key:generate
npm install && npm run dev
php artisan serve --port=8000
```

## Banco de Dados

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gabarito360
DB_USERNAME=gabarito360
DB_PASSWORD=secret
```

## Documentação

Consulte a pasta `docs/` para arquitetura, regras de negócio, rotas e plano de execução.
