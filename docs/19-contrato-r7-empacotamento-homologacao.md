# Contrato de Entrega R7 - Empacotamento e Homologacao

## 1. Objetivo

O R7 empacota a fundacao da branch `refatoracao-mockup-mariadb`, estabelece
continuidade operacional, amplia a CI e prepara uma PR revisavel sem realizar
merge direto.

Em 13 de junho de 2026, a implementacao, a regressao local e a homologacao
integrada foram concluidas. O daemon Docker local nao iniciou, mas o job remoto
da draft PR #1 aprovou build, subida, health, backup e restauracao isolada.

## 2. Entregaveis

- Compose com Nginx, Laravel/PHP-FPM, migration, fila, scheduler, Reverb,
  MariaDB e Redis separados.
- Imagens imutaveis com assets web e contrato OMR.
- MariaDB e Redis restritos a rede interna.
- Scripts de backup, restauracao protegida e verificacao isolada.
- CI para backend, OpenAPI, Flutter, OMR, containers, health e restauracao.
- Documentos de deploy, continuidade, runbooks, homologacao e merge.

## 3. Verificacao

```powershell
docker compose --env-file .env.docker config --quiet
docker compose --env-file .env.docker up -d --build --wait
Invoke-RestMethod http://127.0.0.1:8080/api/v1/health
powershell -ExecutionPolicy Bypass -File scripts/infra/verify-restore.ps1

cd backend
composer validate --strict
php vendor/bin/pint --test
php artisan test
npm.cmd run build
cd ..

python -m pytest omr/tests -q
cd mobile
C:\develop\flutter\bin\flutter.bat analyze
C:\develop\flutter\bin\flutter.bat test
C:\develop\flutter\bin\flutter.bat build apk --debug
```

## 4. Limites

- Compose e homologacao tecnica nao substituem infraestrutura gerenciada de
  producao.
- Piloto continua bloqueado pelos gates OMR real, dispositivos reais e LGPD
  organizacional.
- Merge direto e deploy automatico de producao nao fazem parte do R7.
- Dados demonstrativos nao sao semeados pelo deploy.
- Achados altos de dependencia exclusiva de build devem ser avaliados antes de
  producao; a CI bloqueia achados criticos.
