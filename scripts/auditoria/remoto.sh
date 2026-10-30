#!/bin/bash
# Roda no CT 111, como root. Gera as telas em /tmp/aud a partir das respostas reais da API (só leitura).
T(){ cd /home/lucasdev/g360-dev/apps/$1 && su www-data -s /bin/bash -c "HOME=/tmp php artisan tinker --execute='require \"/tmp/$2\";'"; }
rm -rf /tmp/aud /tmp/aud_fake_* /tmp/aud_miss.json
sed -i "s/^APP_LOCALE=.*/APP_LOCALE=pt_BR/" /home/lucasdev/g360-dev/apps/web/.env
for i in 1 2 3; do T api aud_api.php >/dev/null; T web aud_web.php | tail -1; done
T api aud_api.php >/dev/null; T web aud_web.php
cd /tmp && tar czf /tmp/aud.tgz aud && chmod 644 /tmp/aud.tgz
rm -rf /tmp/aud /tmp/aud_fake_* /tmp/aud_miss.json /tmp/aud_api.php /tmp/aud_web.php /tmp/aud_cfg.json /tmp/remoto.sh /tmp/devcopy.sh /home/lucasdev/g360-dev
