"""Ensaia B5 e corte T2 somente no CT temporário, conectado ao DB temporário."""
import json,re,subprocess as sp,time
from pathlib import Path
from importlib.machinery import SourceFileLoader
m=SourceFileLoader('restore','/root/g360-t2-20261101/t2-restaurar-cts.py').load_module()
pct=m.pct;run=m.run;W=m.W
ROOT='/home/lucasdev/projetos/gabarito360'
ALVO='3af6c19de426ef0ea89bbbeab66e7e167c3970c2'
assert pct(9111,'hostname').strip()==m.NOMES[9111]
assert all(r.get('dst')=='10.0.0.0/24' for r in json.loads(pct(9111,'ip','-j','route')))
# O ensaio usa HTTP local; cookies continuam restritos ao clone, sem domínio público.
cookie_env=r"""from pathlib import Path
import re
for a in ['api','web']:
 p=Path('/home/lucasdev/projetos/gabarito360/apps')/a/'.env';s=p.read_text()
 for k,v in {'SESSION_DOMAIN':'','SESSION_SECURE_COOKIE':'false'}.items():
  if re.search(r'^'+k+r'=',s,re.M):s=re.sub(r'^'+k+r'=.*$',k+'='+v,s,flags=re.M)
  else:s+='\n'+k+'='+v+'\n'
 p.write_text(s)
"""
pct(9111,'python3','-',entrada=cookie_env.encode())
def artisan(*args):return pct(9111,'runuser','-u','www-data','--','php',ROOT+'/apps/api/artisan',*args)
def helper(modo):return pct(9111,'php','/root/t2-restauracao-completa.php',modo)
for f in ['t2-restauracao-completa.php','t2-http-restauracao.py']:
 run(['pct','push','9111',str(W/f),'/root/'+f])
antes=json.loads(helper('inventario'));(W/'inventario.json').write_text(json.dumps(antes,indent=2))
admin=helper('admin').strip();assert re.fullmatch(r'\d+',admin)
run(['pct','push','9111',str(W/'release.bundle'),'/tmp/t2-release.bundle'])
pct(9111,'chmod','644','/tmp/t2-release.bundle')
pct(9111,'test','!','-e','/home/lucasdev/t2-release.git')
pct(9111,'runuser','-u','lucasdev','--','git','init','--bare','/home/lucasdev/t2-release.git')
pct(9111,'runuser','-u','lucasdev','--','git','-C','/home/lucasdev/t2-release.git','fetch','/tmp/t2-release.bundle','refs/heads/pacote/t2-restauracao-completa:refs/heads/main')
pct(9111,'runuser','-u','lucasdev','--','git','-C',ROOT,'remote','set-url','origin','/home/lucasdev/t2-release.git')
script=pct(9111,'runuser','-u','lucasdev','--','git','--git-dir=/home/lucasdev/t2-release.git','show',ALVO+':scripts/operacao/deploy.sh')
(W/'deploy-candidato.sh').write_text(script)
run(['pct','push','9111',str(W/'deploy-candidato.sh'),'/root/t2-deploy.sh'])
t=time.monotonic()
saida=pct(9111,'env','COMPOSER_DISABLE_NETWORK=1','bash','/root/t2-deploy.sh','--commit',ALVO,'--from','dc9949e3690744b72c5071d919955c09ad2be9b8','--manter-manutencao')
(W/'b5-clone.log').write_text(saida)
assert 'PREPARADO EM MANUTENÇÃO' in saida
for app in ['api','web']:pct(9111,'test','-f',ROOT+'/apps/'+app+'/storage/framework/down')
for unit in ['gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer','gabarito360-agendador.service']:
 assert pct(9111,'systemctl','show',unit,'-p','ActiveState','--value').strip()=='inactive'
r={'somente_clone':True,'alvo':ALVO,'b5_manteve_manutencao':True,'b5_s':round(time.monotonic()-t,2)}
t=time.monotonic()
comandos=[['g360:inventariar-espacos'],['g360:preencher-espacos'],['g360:preencher-espacos','--aplicar','--manifesto=legado-20261031-v1'],['g360:conciliar-admin-espacos','--usuario='+admin,'--manifesto=legado-20261031-v1'],['g360:conciliar-admin-espacos','--usuario='+admin,'--manifesto=legado-20261031-v1','--aplicar'],['g360:auditar-espacos'],['g360:restringir-espacos'],['g360:restringir-espacos','--aplicar','--manifesto=legado-20261031-v1','--escritores-parados'],['g360:restringir-espacos'],['g360:auditar-espacos'],['g360:verificar-corte-espacos']]
for i,args in enumerate(comandos):(W/('corte-'+str(i)+'.log')).write_text(artisan(*args))
r['corte_s']=round(time.monotonic()-t,2);r.update(json.loads(helper('pos-corte')))
pct(9111,'systemctl','reload','php8.3-fpm')
pct(9111,'systemctl','start','gabarito360-worker@1','gabarito360-worker@2')
for app in ['api','web']:pct(9111,'runuser','-u','www-data','--','php',ROOT+'/apps/'+app+'/artisan','up')
helper('conta')
r['http']=json.loads(pct(9111,'python3','/root/t2-http-restauracao.py','primeiro'))
pct(9111,'systemctl','start','gabarito360-agendador.timer')
(W/'corte.json').write_text(json.dumps(r,indent=2));print(json.dumps(r),flush=True)
