#!/bin/bash
# =============================================================
# Gabarito360 — Script de deploy (mesmo servidor)
# Servidor: 10.0.0.10   PHP: 8.3   Nginx   MariaDB: 10.0.0.6
# =============================================================
# Uso: bash infra/deploy.sh [api|web|all]
# Primeira vez: bash infra/deploy.sh all
# =============================================================

set -e

REPO_DIR="/var/www/gabarito360"
TARGET="${1:-all}"

log() { echo "[$(date '+%H:%M:%S')] $*"; }

deploy_api() {
    log "=== Deployando API ==="
    cd "$REPO_DIR/apps/api"

    composer install --no-dev --optimize-autoloader --no-interaction
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan migrate --force

    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache

    log "API pronta."
}

deploy_web() {
    log "=== Deployando WEB ==="
    cd "$REPO_DIR/apps/web"

    composer install --no-dev --optimize-autoloader --no-interaction
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan migrate --force

    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache

    log "WEB pronta."
}

setup_nginx() {
    log "=== Configurando Nginx ==="
    cp "$REPO_DIR/infra/nginx.conf" /etc/nginx/sites-available/gabarito360
    ln -sf /etc/nginx/sites-available/gabarito360 /etc/nginx/sites-enabled/gabarito360
    nginx -t && systemctl reload nginx
    log "Nginx configurado."
}

setup_ssl() {
    log "=== Obtendo certificados SSL (Let's Encrypt) ==="
    certbot --nginx \
        -d gabarito360.com.br \
        -d www.gabarito360.com.br \
        -d api.gabarito360.com.br \
        --non-interactive \
        --agree-tos \
        --email genio.lucas@gmail.com
    log "SSL configurado."
}

primeira_vez() {
    log "=== Primeira instalação ==="

    # Clonar se não existir
    if [ ! -d "$REPO_DIR" ]; then
        git clone git@github.com:lucasgenial/gabarito360.git "$REPO_DIR"
    fi

    # Verificar .env
    for app in api web; do
        if [ ! -f "$REPO_DIR/apps/$app/.env" ]; then
            log "ATENÇÃO: Crie $REPO_DIR/apps/$app/.env antes de continuar!"
            log "  cp $REPO_DIR/apps/$app/.env.example $REPO_DIR/apps/$app/.env"
            log "  nano $REPO_DIR/apps/$app/.env"
            exit 1
        fi
    done

    deploy_api
    deploy_web
    setup_nginx
    setup_ssl
    log "=== Instalação concluída! ==="
}

atualizar() {
    log "=== Atualizando código ==="
    cd "$REPO_DIR"
    git pull origin main

    case "$TARGET" in
        api) deploy_api ;;
        web) deploy_web ;;
        *)   deploy_api; deploy_web ;;
    esac
}

# Detectar se é primeira vez ou atualização
if [ ! -d "$REPO_DIR/.git" ]; then
    primeira_vez
else
    atualizar
fi
