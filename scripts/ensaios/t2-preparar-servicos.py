"""Prepara somente o filesystem fixo extraído; todos os dados/segredos usados são fictícios."""
from pathlib import Path
import os, stat
r=Path('/recuperacao-t2/ct111'); a=r/'opt/candidato'
assert os.geteuid()==0 and r.resolve()==r and a.is_dir()
for app in ['api','web']:
 p=a/'apps'/app
 for d in ['bootstrap/cache','storage/framework/cache','storage/framework/sessions','storage/framework/views','storage/logs']:(p/d).mkdir(parents=True,exist_ok=True)
 env='''APP_NAME=EnsaioT2
APP_ENV=staging
APP_DEBUG=false
APP_URL=http://127.0.0.1:8080
APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=
DADOS_HASH_KEY=chave-ficticia-de-busca-apenas-ensaio-2026
DB_CONNECTION=mariadb
DB_DATABASE=g360_t2_ensaio_test
DB_USERNAME=ensaio_runtime
DB_PASSWORD=senha-ficticia-runtime
DB_SOCKET=/mnt/db/mysql.sock
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=false
SESSION_LIFETIME=120
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_QUEUE=padrao
MAIL_MAILER=array
API_BASE_URL=http://127.0.0.1:8080/api
TURNSTILE_ENABLED=false
APP_MAINTENANCE_DRIVER=file
'''
 (p/'.env').write_text(env+'REDIS_PREFIX=ensaio_'+app+'_\n')
 for root,dirs,files in os.walk(p/'storage'):
  os.chown(root,33,33)
  for f in files:os.chown(Path(root)/f,33,33)
 os.chown(p/'bootstrap/cache',33,33)
 (p/'.env').chmod(0o640);os.chown(p/'.env',0,33)
(r/'run').mkdir(exist_ok=True);(r/'tmp').chmod(0o1777)
for name,minor in [('null',3),('zero',5),('random',8),('urandom',9)]:
 p=r/'dev'/name
 if not p.exists():os.mknod(p,stat.S_IFCHR|0o666,os.makedev(1,minor))
(r/'tmp/t2-fpm.conf').write_text('''[global]
error_log = /tmp/t2-fpm-error.log
[www]
user = www-data
group = www-data
listen = /run/t2-web.sock
listen.owner = www-data
listen.group = www-data
pm = static
pm.max_children = 2
[api]
user = www-data
group = www-data
listen = /run/t2-api.sock
listen.owner = www-data
listen.group = www-data
pm = static
pm.max_children = 2
''')
(r/'tmp/t2-nginx.conf').write_text('''user www-data;
worker_processes 1;
pid /tmp/t2-nginx.pid;
error_log /tmp/t2-nginx-error.log;
events { worker_connections 128; }
http {
 include /etc/nginx/mime.types;
 access_log off;
 server {
  listen 127.0.0.1:8080;
  server_name 127.0.0.1;
  root /opt/candidato/apps/web/public;
  index index.php;
  location /api/ {
   include /etc/nginx/fastcgi_params;
   fastcgi_param SCRIPT_FILENAME /opt/candidato/apps/api/public/index.php;
   fastcgi_pass unix:/run/t2-api.sock;
  }
  location / { try_files $uri $uri/ /index.php?$query_string; }
  location = /index.php {
   include /etc/nginx/fastcgi_params;
   fastcgi_param SCRIPT_FILENAME /opt/candidato/apps/web/public/index.php;
   fastcgi_pass unix:/run/t2-web.sock;
  }
 }
}
''')
(a/'apps/api/app/Jobs/EnsaioRecuperacaoJob.php').write_text(r'''<?php
namespace App\Jobs;
class EnsaioRecuperacaoJob implements \Illuminate\Contracts\Queue\ShouldQueue {
 use \Illuminate\Foundation\Queue\Queueable;
 public function handle(): void { file_put_contents('/tmp/t2-job-ok', 'consumido'); }
}
''')
(r/'tmp/t2-helper.php').write_text(r'''<?php
require '/opt/candidato/apps/api/vendor/autoload.php';
$app=require '/opt/candidato/apps/api/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (Illuminate\Support\Facades\DB::connection()->getDatabaseName() !== 'g360_t2_ensaio_test' || config('database.connections.mariadb.unix_socket') !== '/mnt/db/mysql.sock') exit(2);
if ($argv[1]==='preparar') {
 $u=App\Models\Usuario::findOrFail(1);
 $u->email='servicos@example.test'; $u->password=bcrypt('senha-ficticia-servicos'); $u->ativo=true; $u->forcar_troca_senha=false; $u->save();
} elseif ($argv[1]==='fila') { App\Jobs\EnsaioRecuperacaoJob::dispatch()->onQueue('padrao'); }
elseif ($argv[1]==='auditar') { if (!app(App\Services\AuditarIntegridadeEspacos::class)->executar()['apto_dados'] || Illuminate\Support\Facades\DB::table('failed_jobs')->count() !== 0) exit(3); }
else exit(2);
''')
print('Configurações fictícias preparadas; nenhum segredo real carregado.')
