"""Ensaio pontual no host pve; somente clones 9106/9111 identificados, sem rede externa.
Requer restauração inicial, bundle HEAD e auxiliares do doc. 52 na pasta privada W.
"""
import datetime, hashlib, json, os, re, subprocess as sp, sys, time
from pathlib import Path
from importlib.machinery import SourceFileLoader
m=SourceFileLoader('restore','/root/g360-t2-20261101/t2-restaurar-cts.py').load_module()
pct=m.pct;run=m.run;W=m.W
ROOT='/home/lucasdev/projetos/gabarito360'
ANTES='dc9949e3690744b72c5071d919955c09ad2be9b8'
ALVO='6a9724c8107c1349eb2f1b592958d3e7e895aedb'  # Candidato pontual do ensaio.
UNITS=['gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer','gabarito360-agendador.service','php8.3-fpm']
r={'inicio_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'somente_clones':True}

def guardas():
 for id,nome in m.NOMES.items():
  conf=run(['pct','config',str(id)])
  assert 'hostname: '+nome in conf and 'g360-t2-20261101-isolado' in conf
  assert not re.search(r'^net\d+:',conf,re.M)
  assert all(x.get('dst')=='10.0.0.0/24' for x in json.loads(pct(id,'ip','-j','route')))

def artisan(app,*args):return pct(9111,'runuser','-u','www-data','--','php',ROOT+'/apps/'+app+'/artisan',*args)
def git(*args):return pct(9111,'runuser','-u','lucasdev','--','git','-C',ROOT,*args).strip()
def tabelas():return int(pct(9106,'mariadb','-Nse',"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='gabarito-360' AND table_name IN ('clientes','espacos')"))
def parado():
 for a in ['api','web']:pct(9111,'test','-f',ROOT+'/apps/'+a+'/storage/framework/down')
 for u in UNITS:assert pct(9111,'systemctl','show',u,'-p','ActiveState','--value').strip()=='inactive'
 assert git('rev-parse','HEAD')==ANTES and tabelas()==0

def abrir_antigo():
 assert git('rev-parse','HEAD')==ANTES and tabelas()==0
 for a in ['api','web']:artisan(a,'up')
 pct(9111,'systemctl','start','php8.3-fpm','gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer')

continuar='--continuar' in sys.argv
if not continuar:
 guardas()
 for f in ['deploy.sh','pausa-backup-final.sh','t2-restauracao-completa.php','t2-http-restauracao.py']:
  run(['pct','push','9111',str(W/f),'/root/'+f])
  pct(9111,'chmod','700','/root/'+f)
 run(['pct','push','9111',str(W/'release.bundle'),'/tmp/t2-release.bundle'])
 pct(9111,'chmod','644','/tmp/t2-release.bundle')
 pct(9111,'runuser','-u','lucasdev','--','git','init','--bare','/home/lucasdev/t2-release.git')
 pct(9111,'runuser','-u','lucasdev','--','git','-C','/home/lucasdev/t2-release.git','fetch','/tmp/t2-release.bundle','HEAD:refs/heads/main')
 ALVO=pct(9111,'runuser','-u','lucasdev','--','git','--git-dir=/home/lucasdev/t2-release.git','rev-parse',ALVO).strip()
 r['alvo']=ALVO
 git('remote','set-url','origin','/home/lucasdev/t2-release.git')
 args=['pct','exec','9111','--','env','COMPOSER_DISABLE_NETWORK=1','bash','/root/deploy.sh','--commit',ALVO,'--from',ANTES,'--manter-manutencao','--backup-final','/root/pausa-backup-final.sh']
 # Check não cria checkpoint nem entra em manutenção.
 saida=run(args+['--check']);assert 'CHECK concluído' in saida
 for a in ['api','web']:pct(9111,'test','!','-f',ROOT+'/apps/'+a+'/storage/framework/down')
 r['check_sem_parada']=True
 # Timeout real: não pode migrar nem reabrir.
 timeout_args=args.copy();timeout_args.insert(timeout_args.index('bash'),'G360_BACKUP_TIMEOUT=1')
 f=sp.run(timeout_args,stdout=sp.PIPE,stderr=sp.STDOUT)
 (W/'checkpoint-timeout.log').write_bytes(f.stdout)
 assert f.returncode!=0
 parado();r['timeout_sem_ddl_sem_reabertura']=True
 abrir_antigo()
 print('Timeout conferido: código/esquema antigos e manutenção preservada.',flush=True)
 # Inventário/chaves antes do backup, sem expor dados.
 antes=json.loads(pct(9111,'php','/root/t2-restauracao-completa.php','inventario'))
 r['inventario']=antes
 (W/'checkpoint-em-andamento.json').write_text(json.dumps(r,indent=2))
else:
 r=json.loads((W/'checkpoint-em-andamento.json').read_text());antes=r['inventario'];ALVO=r['alvo']
 guardas();parado()
log=W/'checkpoint-deploy.log'
with log.open('ab' if continuar else 'wb') as out:
 proc=None if continuar else sp.Popen(args,stdout=out,stderr=sp.STDOUT)
 inicio=time.monotonic()
 for _ in range(90):
  s=log.read_text(errors='replace')
  achou=re.search(r'CHECKPOINT AGUARDANDO: (/root/g360-backup-final-[a-z0-9_]+)',s)
  if achou:break
  assert proc is None or proc.poll() is None,'Deploy terminou antes da pausa'
  time.sleep(1)
 else:raise RuntimeError('Checkpoint não apareceu')
 pasta=achou[1];parado()
 pronto=json.loads(pct(9111,'cat',pasta+'/pronto.json'))
 r['pausa_antes_codigo_ddl']=True
 # Lock permanece preso durante a espera.
 if not continuar:
  paralelo=sp.run(args+['--check'],stdout=sp.PIPE,stderr=sp.STDOUT)
  assert paralelo.returncode!=0 and b'Outro deploy' in paralelo.stdout
 r['lock_durante_backup']=True
 print('Pausa confirmada; criando backups dos dois clones.',flush=True)
 dump=W/'checkpoint-arquivos';dump.mkdir(mode=0o700,exist_ok=continuar)
 assert not list(dump.glob('*.tar.zst'))
 # Backup pontual SOMENTE dos clones; nenhuma retenção ou notificação.
 os.umask(0o022)
 saida=run(['vzdump','9106','9111','--dumpdir',str(dump),'--tmpdir','/var/tmp','--mode','snapshot','--compress','zstd','--remove','0','--prune-backups','keep-all=1','--notification-mode','legacy-sendmail'])
 (W/'checkpoint-vzdump.log').write_text(saida)
 parado();assert proc is None or proc.poll() is None
 paths={id:list(dump.glob('vzdump-lxc-'+str(id)+'-*.tar.zst')) for id in [9106,9111]}
 assert all(len(v)==1 for v in paths.values())
 confirmado=run(['python3',str(W/'confirmar-backup-final.py'),'--ct-banco','9106','--ct-aplicacao','9111','--checkpoint',pasta,'--banco',str(paths[9106][0]),'--aplicacao',str(paths[9111][0])])
 recibo=json.loads(confirmado);arquivos=recibo['arquivos']
 (W/'recibo.json').write_text(json.dumps(recibo,indent=2))
 r['backup_s']=round(time.monotonic()-inicio,2)
 if proc is not None:assert proc.wait(timeout=300)==0
 else:
  for _ in range(300):
   texto=log.read_text()
   assert 'FALHA na etapa' not in texto
   if 'PREPARADO EM MANUTENÇÃO' in texto:break
   time.sleep(1)
  else:raise RuntimeError('Deploy não encerrou após confirmação')
r['backups']=arquivos
assert 'PREPARADO EM MANUTENÇÃO' in log.read_text()
assert git('rev-parse','HEAD')==ALVO and tabelas()==2
r['expansao_somente_apos_recibo']=True
# Corte e integridade com os mesmos dados restaurados.
admin=pct(9111,'php','/root/t2-restauracao-completa.php','admin').strip();assert re.fullmatch(r'\d+',admin)
for i,cmd in enumerate([
 ['g360:inventariar-espacos'],['g360:preencher-espacos'],['g360:preencher-espacos','--aplicar','--manifesto=legado-20261031-v1'],
 ['g360:conciliar-admin-espacos','--usuario='+admin,'--manifesto=legado-20261031-v1','--aplicar'],
 ['g360:auditar-espacos'],['g360:restringir-espacos','--aplicar','--manifesto=legado-20261031-v1','--escritores-parados'],['g360:verificar-corte-espacos']]):
 (W/('checkpoint-corte-'+str(i)+'.log')).write_text(artisan('api',*cmd))
r['integridade']=json.loads(pct(9111,'php','/root/t2-restauracao-completa.php','pos-corte'))
print('Confirmação liberou expansão e corte; dados íntegros.',flush=True)
# Repor os MESMOS clones a partir do checkpoint para comprovar recuperação anterior ao DDL.
guardas()
for id in [9111,9106]:
 conf=run(['pct','config',str(id)])
 assert re.search(r'^rootfs: local-lvm:vm-'+str(id)+r'-disk-\d+(?:,|$)',conf,re.M)
 run(['pct','stop',str(id)])
 run(['pct','destroy',str(id),'--purge','1'])
for id,papel in [(9106,'banco'),(9111,'aplicacao')]:
 assert not Path('/etc/pve/lxc/'+str(id)+'.conf').exists()
 arquivo=next(a['arquivo'] for a in arquivos if a['papel']==papel)
 run(['pct','restore',str(id),arquivo,'--storage','local-lvm','--hostname',m.NOMES[id],'--onboot','0','--start','0','--description','g360-t2-20261101-isolado','--tags','ensaio-t2'])
 assert not re.search(r'^net\d+:',run(['pct','config',str(id)]),re.M)
for id in m.NOMES:run(['pct','start',str(id)])
m.rede();guardas()
for _ in range(30):
 try:pct(9106,'mariadb-check','--silent','--databases','gabarito-360');break
 except RuntimeError:time.sleep(2)
else:raise RuntimeError('Banco não recuperou')
assert git('rev-parse','HEAD')==ANTES and tabelas()==0
for a in ['api','web']:pct(9111,'test','-f',ROOT+'/apps/'+a+'/storage/framework/down')
restaurado=json.loads(pct(9111,'php','/root/t2-restauracao-completa.php','inventario'))
assert restaurado==antes
arquivo=next(a['arquivo'] for a in arquivos if a['papel']=='aplicacao')
p=sp.Popen(['zstd','-dc',arquivo],stdout=sp.PIPE,stderr=sp.DEVNULL)
referencia=sp.run(['tar','--occurrence=1','-xOf','-','./root/t2-inventario.json'],stdin=p.stdout,stdout=sp.PIPE,stderr=sp.PIPE)
p.stdout.close();p.wait()
assert referencia.returncode==0
assert json.loads(referencia.stdout)==json.loads(pct(9111,'cat','/root/t2-inventario.json'))
r['hashes_13_tabelas_restauradas']=True
abrir_antigo()
assert pct(9111,'curl','-s','-o','/dev/null','-w','%{http_code}','http://localhost/api/v1/health')=='200'
r['restauracao_checkpoint']={'sha_anterior':True,'sem_tabelas_t2':True,'chaves_arquivos_contagens':True,'health':200}
r['fim_utc']=datetime.datetime.now(datetime.timezone.utc).isoformat()
(W/'checkpoint-resultado.json').write_text(json.dumps(r,indent=2))
print(json.dumps(r),flush=True)
