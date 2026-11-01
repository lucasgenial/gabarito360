"""Smoke dos serviços extraídos, só no namespace isolado e fixture do doc. 51."""
from http.cookiejar import CookieJar
import json
import os
from pathlib import Path
import re
import subprocess as sp
import time
import urllib.error
import urllib.parse
import urllib.request

ROOT = Path('/recuperacao-t2/ct111')
APP = '/opt/candidato'
processos = []
inicio = time.monotonic()
resultado = {'dados_ficticios': True, 'producao_alterada': False}

def executar(args, **kw):
    return sp.run(args, check=True, stdout=sp.PIPE, stderr=sp.PIPE, **kw)

def chroot(args):
    return executar(['chroot', str(ROOT), *args]).stdout.decode()

def php(app, *args):
    return chroot(['runuser', '-u', 'www-data', '--', 'php', APP+'/apps/'+app+'/artisan', *args])

def iniciar(args, log):
    f = open(ROOT / ('tmp/'+log), 'ab')
    p = sp.Popen(['chroot', str(ROOT), *args], stdout=f, stderr=f)
    f.close()
    processos.append(p)
    return p

def parar(p):
    if p.poll() is None:
        p.terminate()
        try: p.wait(timeout=15)
        except sp.TimeoutExpired: p.kill(); p.wait()

def requisitar(path, dados=None, token=None, opener=None, form=False):
    headers = {'Accept': 'application/json'}
    if token: headers['Authorization'] = 'Bearer '+token
    if form:
        headers['Content-Type'] = 'application/x-www-form-urlencoded'
        payload = urllib.parse.urlencode(dados).encode()
    else:
        headers['Content-Type'] = 'application/json'
        payload = json.dumps(dados).encode() if dados is not None else None
    req = urllib.request.Request('http://127.0.0.1:8080'+path, data=payload, headers=headers)
    try: resp = (opener.open if opener else urllib.request.urlopen)(req, timeout=20)
    except urllib.error.HTTPError as e: resp=e
    with resp: return resp.status, resp.read(), resp.url

try:
    assert os.geteuid()==0 and ROOT.resolve()==ROOT
    assert os.readlink('/proc/self/ns/mnt') != os.readlink('/proc/1/ns/mnt')
    interfaces = Path('/proc/net/dev').read_text().splitlines()[2:]
    assert all(l.split(':')[0].strip()=='lo' for l in interfaces)
    executar(['mount','--make-rprivate','/'])
    executar(['ip','link','set','lo','up'])
    executar(['mount','-t','proc','proc',str(ROOT/'proc')])
    (ROOT/'mnt/db').mkdir(parents=True,exist_ok=True)
    executar(['mount','--bind','/tmp/g360-t2-mariadb',str(ROOT/'mnt/db')])
    chroot(['nginx','-t','-c','/tmp/t2-nginx.conf'])
    chroot(['php-fpm8.3','-t','-y','/tmp/t2-fpm.conf'])
    redis_dir=ROOT/'tmp/t2-redis'
    redis_dir.mkdir(exist_ok=True)
    os.chown(redis_dir,int(chroot(['id','-u','redis'])),int(chroot(['id','-g','redis'])))
    redis_args=['runuser','-u','redis','--','redis-server','--bind','127.0.0.1','--port','6379','--save','','--appendonly','yes','--appendfsync','always','--maxmemory','256mb','--maxmemory-policy','volatile-lru','--dir','/tmp/t2-redis']
    redis=iniciar(redis_args, 't2-redis.log')
    fpm=iniciar(['php-fpm8.3','-F','-y','/tmp/t2-fpm.conf'], 't2-fpm.log')
    nginx=iniciar(['nginx','-c','/tmp/t2-nginx.conf','-g','daemon off;'], 't2-nginx.log')
    for _ in range(40):
        try:
            if requisitar('/api/v1/health')[0]==200: break
        except OSError: pass
        time.sleep(.25)
    else: raise RuntimeError('Serviços não iniciaram')
    resultado['inicio_servicos_s']=round(time.monotonic()-inicio,2)
    assert 'PONG' in chroot(['redis-cli','ping'])
    # Helper valida banco e usa somente usuário/dados fictícios.
    chroot(['runuser','-u','www-data','--','php','/tmp/t2-helper.php','preparar'])
    for app in ['api','web']:
        php(app,'config:cache'); php(app,'route:cache'); php(app,'view:cache')
    php('api','g360:verificar-corte-espacos')
    status,body,_=requisitar('/api/v1/auth/login',{'email':'servicos@example.test','password':'senha-ficticia-servicos'})
    assert status==200
    token=json.loads(body)['data']['token']
    http={}
    for path in ['/','/login','/api/v1/health']:
        http[path]=requisitar(path)[0]; assert http[path]==200
    for path in ['/api/v1/dashboard/admin','/api/v1/relatorios/rede/1','/api/v1/relatorios/rede/2']:
        http[path]=requisitar(path,token=token)[0]; assert http[path]==200
    # Sessão web real em Redis: CSRF, login e painel através de nginx/FPM.
    jar = CookieJar()
    opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    _,html,_=requisitar('/login',opener=opener)
    csrf=re.search(rb'name="_token"[^>]*value="([^"]+)"',html).group(1).decode()
    status,_,url=requisitar('/login',{'_token':csrf,'email':'servicos@example.test','password':'senha-ficticia-servicos'},opener=opener,form=True)
    assert status==200 and urllib.parse.urlsplit(url).path=='/painel'
    resultado['login_web_sessao_redis']=True
    worker_args=['runuser','-u','www-data','--','php',APP+'/apps/api/artisan','queue:work','redis','--queue=alta,padrao,baixa','--sleep=1','--tries=1','--timeout=30']
    workers=[iniciar(worker_args,'t2-worker-'+str(i)+'.log') for i in range(2)]
    (ROOT/'tmp/t2-job-ok').unlink(missing_ok=True)
    chroot(['runuser','-u','www-data','--','php','/tmp/t2-helper.php','fila'])
    for _ in range(40):
        if (ROOT/'tmp/t2-job-ok').exists(): break
        time.sleep(.25)
    else: raise RuntimeError('Fila não consumiu job')
    resultado['fila_redis_consumida']=True
    assert all(p.poll() is None for p in workers)
    php('api','schedule:run')
    resultado['schedule_run']=True
    # Ciclo de manutenção e reinício de processos; nenhuma escrita de dados reais.
    php('web','down'); php('api','down')
    assert requisitar('/login')[0]==503 and requisitar('/api/v1/health')[0]==503
    for p in workers: parar(p)
    # Persistência: sessão e novo job sobrevivem à parada do Redis.
    (ROOT/'tmp/t2-job-ok').unlink(missing_ok=True)
    chroot(['runuser','-u','www-data','--','php','/tmp/t2-helper.php','fila'])
    parar(redis)
    redis=iniciar(redis_args,'t2-redis-retomada.log')
    time.sleep(.5)
    parar(fpm)
    fpm=iniciar(['php-fpm8.3','-F','-y','/tmp/t2-fpm.conf'],'t2-fpm.log')
    time.sleep(.5)
    workers=[iniciar(worker_args,'t2-worker-retomada-'+str(i)+'.log') for i in range(2)]
    php('api','g360:verificar-corte-espacos'); php('api','up'); php('web','up')
    assert requisitar('/api/v1/health')[0]==200
    status,_,url=requisitar('/painel',opener=opener)
    assert status==200 and urllib.parse.urlsplit(url).path=='/painel'
    for _ in range(40):
        if (ROOT/'tmp/t2-job-ok').exists(): break
        time.sleep(.25)
    else: raise RuntimeError('Job não sobreviveu ao reinício do Redis')
    resultado['redis_aof_preservou_sessao_e_job']=True
    resultado['manutencao_503_e_retomada_200']=True
    chroot(['runuser','-u','www-data','--','php','/tmp/t2-helper.php','auditar'])
    resultado['auditoria_zero']=True
    resultado['http']=http
    resultado['total_servicos_s']=round(time.monotonic()-inicio,2)
    print(json.dumps(resultado,ensure_ascii=False,indent=2))
finally:
    for p in reversed(processos): parar(p)
    # Mounts pertencem somente ao namespace criado pelo operador.
