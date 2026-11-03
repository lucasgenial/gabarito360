"""Release somente na VM de homologação; nunca recebe endereço/credencial de produção."""
import os,pathlib,subprocess,hashlib,gzip,json,re,sys,tarfile,shutil,datetime,fcntl
assert os.geteuid()==0 and pathlib.Path('/etc/hostname').read_text().strip()=='g360-homologa'
os.umask(0o027)
sha,arquivo,checksum=sys.argv[1:4]
assert re.fullmatch('[a-f0-9]{40}',sha) and re.fullmatch('[a-f0-9]{64}',checksum)
arquivo=pathlib.Path(arquivo);assert arquivo.is_file();assert hashlib.file_digest(arquivo.open('rb'),'sha256').hexdigest()==checksum
BASE=pathlib.Path('/srv/gabarito360');lock=os.open(BASE/'.deploy-homologa.lock',os.O_CREAT|os.O_RDWR,0o600);fcntl.flock(lock,fcntl.LOCK_EX|fcntl.LOCK_NB);anterior=(BASE/'current').resolve();release=BASE/'releases'/sha
assert anterior.parent==BASE/'releases' and not release.exists()
recibo=pathlib.Path('/root/g360-homologa/releases')/sha;recibo.mkdir(parents=True,mode=0o700)
def run(args,**kw):
 p=subprocess.run(args,text=True,capture_output=True,**kw)
 if p.returncode:
  (recibo/'erro-privado.log').write_text(p.stdout+p.stderr)
  raise SystemExit('Falha em '+args[0]+'. Homologação preservada em manutenção; conferir log privado.')
 return p.stdout
release.mkdir(mode=0o755)
with tarfile.open(arquivo,'r:gz') as tf:
 for m in tf.getmembers():assert not pathlib.PurePosixPath(m.name).is_absolute() and '..' not in pathlib.PurePosixPath(m.name).parts
 tf.extractall(release,filter='data')
# Diretórios do filtro tar usam o umask; o PHP precisa atravessar o código.
for raiz,dirs,files in os.walk(release,followlinks=False):
 pathlib.Path(raiz).chmod(0o755)
 for nome in files:
  f=pathlib.Path(raiz)/nome
  if not f.is_symlink():f.chmod(0o755 if f.stat().st_mode & 0o111 else 0o644)
for app in ['api','web']:
 folder=release/'apps'/app;(folder/'bootstrap/cache').mkdir(parents=True,exist_ok=True)
 if not (folder/'storage').exists():(folder/'storage').mkdir()
 (folder/'storage').rename(folder/'storage.dist');(folder/'storage').symlink_to(BASE/'shared'/app/'storage',target_is_directory=True)
 (folder/'.env').symlink_to(BASE/'shared'/app/'.env')
 out=run(['composer','install','--no-dev','--prefer-dist','--no-interaction','--optimize-autoloader'],cwd=folder,env={**os.environ,'COMPOSER_ALLOW_SUPERUSER':'1'})
 (recibo/(app+'-composer.log')).write_text(out)
 run(['chown','-R','www-data:www-data',str(folder/'bootstrap/cache')]);(folder/'bootstrap/cache').chmod(0o750)
# Pausa apenas na homologação, após preparar dependências.
run(['systemctl','stop','g360-homologa-worker.service'])
if pathlib.Path('/etc/systemd/system/g360-homologa-copias.timer').exists():run(['systemctl','stop','g360-homologa-copias.timer'])
for app in ['api','web']:
 run(['runuser','-u','www-data','--','php','artisan','down','--retry=60'],cwd=anterior/'apps'/app)
 shutil.copy2(BASE/'shared'/app/'.env',recibo/(app+'.env-anterior'))
guard="require 'vendor/autoload.php';$a=require 'bootstrap/app.php';$a->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();if(!app()->environment('staging')||Illuminate\\Support\\Facades\\DB::connection()->getDatabaseName()!=='gabarito360_homolog_snapshot_20261103')exit(3);echo Illuminate\\Support\\Facades\\DB::connection()->getDatabaseName();"
database=run(['php','-r',guard],cwd=anterior/'apps/api').strip();assert database=='gabarito360_homolog_snapshot_20261103'
with (recibo/'banco-antes.sql.gz').open('wb') as dest:
 proc=subprocess.Popen(['mariadb-dump','--single-transaction','--quick','--hex-blob','--skip-lock-tables',database],stdout=subprocess.PIPE,stderr=subprocess.PIPE)
 with gzip.GzipFile(fileobj=dest,mode='wb') as z:
  while chunk:=proc.stdout.read(1024*1024):z.write(chunk)
 assert proc.wait()==0,'Backup próprio falhou.'
for app in ['api','web']:
 with tarfile.open(recibo/(app+'-arquivos-antes.tar.gz'),'w:gz') as tf:tf.add(BASE/'shared'/app/'storage/app',arcname='app')
manifest_php="""require 'vendor/autoload.php';$a=require 'bootstrap/app.php';$a->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();$r=[];foreach(['alunos','turmas','provas','prova_turmas','gabaritos','gabarito_questoes','cartoes','cartao_respostas','notas'] as $t){$rows=Illuminate\\Support\\Facades\\DB::table($t)->get()->map(function($v){$v=(array)$v;ksort($v);return json_encode($v,JSON_THROW_ON_ERROR);})->all();sort($rows,SORT_STRING);$r[$t]=['total'=>count($rows),'sha256'=>hash('sha256',implode("\\n",$rows))];}echo json_encode($r,JSON_THROW_ON_ERROR);"""
antes=run(['php','-r',manifest_php],cwd=anterior/'apps/api');(recibo/'academico-antes.json').write_text(antes)
env={**os.environ,'APP_CONFIG_CACHE':str(recibo/'config-migration.php'),'DB_USERNAME':'root','DB_PASSWORD':'','DB_HOST':'localhost','DB_SOCKET':'/run/mysqld/mysqld.sock'}
(recibo/'migrations.log').write_text(run(['php','artisan','migrate','--force'],cwd=release/'apps/api',env=env))
run(['php','artisan','g360:permissoes-sincronizar'],cwd=release/'apps/api')
flags={
 'CATALOGO_INTERFACE_ATIVA':'true','COPIAS_INTERFACE_ATIVA':'true','COPIAS_ATIVAS':'true',
 'COPIAS_AUDITORIA_ENTREGA':'true','COPIAS_EXECUCAO_ATIVA':'true','COPIAS_CADASTRO_EXECUCAO_ATIVA':'true','COPIAS_RESULTADOS_EXECUCAO_ATIVA':'true',
 'PEDIDOS_TITULARES_ATIVOS':'true','PEDIDOS_EXPORTACAO_AUTORIZACOES_ATIVAS':'true','PEDIDOS_EXPORTACAO_GERACAO_ATIVA':'true','PEDIDOS_EXPORTACAO_ENTREGA_ATIVA':'true',
 'PEDIDOS_TITULARES_INTERFACE_ATIVA':'true','PEDIDOS_EXPORTACOES_INTERFACE_ATIVA':'true',
 'MAIL_MAILER':'log','MP_SANDBOX':'true','MP_ACCESS_TOKEN':'','MP_PUBLIC_KEY':''}
for app in ['api','web']:
 p=BASE/'shared'/app/'.env';changes=dict(flags)
 if app=='web':changes['API_BASE_URL']='http://127.0.0.1:8081/api'
 lines=[]
 for line in p.read_text().splitlines():
  key=line.split('=',1)[0]
  if key in changes:line=key+'='+changes.pop(key)
  lines.append(line)
 lines.extend(k+'='+v for k,v in changes.items());p.write_text('\n'.join(lines)+'\n');p.chmod(0o640)
 rel=BASE/'shared'/app/'storage/app/release.json';shutil.copy2(rel,recibo/(app+'-release-anterior.json'))
 rel.write_text(json.dumps({'sha':sha,'fase':'HOMOLOGACAO-CI1-E'}));rel.chmod(0o640)
 run(['chown','www-data:www-data',str(rel),str(p)])
 run(['runuser','-u','www-data','--','php','artisan','config:cache'],cwd=release/'apps'/app)
 run(['runuser','-u','www-data','--','php','artisan','view:cache'],cwd=release/'apps'/app)
 for f in (release/'apps'/app/'bootstrap/cache').glob('*.php'):f.chmod(0o640)
depois=run(['php','-r',manifest_php],cwd=release/'apps/api');assert json.loads(antes)==json.loads(depois),'Conteúdo acadêmico divergente; não abrir.'
(recibo/'academico-depois.json').write_text(depois)
nextlink=BASE/'next';assert not nextlink.exists();nextlink.symlink_to(release,target_is_directory=True);os.replace(nextlink,BASE/'current')
run(['systemctl','reload','php8.3-fpm']);run(['systemctl','start','g360-homologa-worker.service'])
run(['runuser','-u','www-data','--','php','artisan','up'],cwd=release/'apps/api')
probe="require 'vendor/autoload.php';$a=require 'bootstrap/app.php';$a->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();$api=app(App\\Services\\ApiClient::class);if($api->get('/v1/planos')->status()!==200||$api->post('/v1/auth/login',['email'=>'g360-probe@example.invalid','password'=>bin2hex(random_bytes(16))])->status()!==401)exit(3);echo 'web-api-ok';"
run(['runuser','-u','www-data','--','php','-r',probe],cwd=release/'apps/web')
run(['runuser','-u','www-data','--','php','artisan','up'],cwd=release/'apps/web')
if pathlib.Path('/etc/systemd/system/g360-homologa-copias.timer').exists():run(['systemctl','start','g360-homologa-copias.timer'])
result={'sha':sha,'anterior':anterior.name,'banco':database,'academico_identico':True,'homologacao_publicada':True,'producao_alterada':False,'backup':str(recibo)}
(recibo/'recibo.json').write_text(json.dumps(result,indent=2));print(json.dumps(result))
