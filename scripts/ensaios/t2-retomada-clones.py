"""Falha de worker e reboot completo, exclusivamente nos CTs temporários."""
import json,subprocess as sp,time
from pathlib import Path
from importlib.machinery import SourceFileLoader
m=SourceFileLoader('restore','/root/g360-t2-20261101/t2-restaurar-cts.py').load_module()
pct=m.pct;run=m.run;W=m.W
ROOT='/home/lucasdev/projetos/gabarito360'
assert pct(9111,'hostname').strip()==m.NOMES[9111]
assert pct(9106,'hostname').strip()==m.NOMES[9106]
def helper(mode):return pct(9111,'php','/root/t2-restauracao-completa.php',mode)
def estado(unit,prop):return pct(9111,'systemctl','show',unit,'-p',prop,'--value').strip()
r={'somente_clones':True,'fila_antes':json.loads(helper('estado-fila'))}
job=r"""<?php
namespace App\Jobs;
class EnsaioRecuperacaoJob implements \Illuminate\Contracts\Queue\ShouldQueue {
 use \Illuminate\Foundation\Queue\Queueable;
 public function handle(): void { file_put_contents(storage_path('logs/t2-job-ok'), 'consumido'); }
}
"""
(W/'EnsaioRecuperacaoJob.php').write_text(job)
pct(9111,'test','!','-e',ROOT+'/apps/api/app/Jobs/EnsaioRecuperacaoJob.php')
run(['pct','push','9111',str(W/'EnsaioRecuperacaoJob.php'),ROOT+'/apps/api/app/Jobs/EnsaioRecuperacaoJob.php'])
pct(9111,'chmod','644',ROOT+'/apps/api/app/Jobs/EnsaioRecuperacaoJob.php')
marker=ROOT+'/apps/api/storage/logs/t2-job-ok'
def consumir():
 pct(9111,'rm','-f',marker);helper('fila')
 for _ in range(15):
  if sp.run(['pct','exec','9111','--','test','-f',marker],stdout=sp.DEVNULL,stderr=sp.DEVNULL).returncode==0:return
  time.sleep(1)
 raise RuntimeError('Job não consumido')
consumir();r['job_antes']=True
pid=estado('gabarito360-worker@1','MainPID');assert pid.isdigit() and int(pid)>1
restarts=int(estado('gabarito360-worker@1','NRestarts'))
pct(9111,'kill','-KILL',pid)
for _ in range(20):
 novo=estado('gabarito360-worker@1','MainPID')
 if novo!='0' and novo!=pid and int(estado('gabarito360-worker@1','NRestarts'))>restarts:break
 time.sleep(1)
else:raise RuntimeError('Worker não reiniciou automaticamente')
consumir();r['worker_restart_automatico']=True
antes=estado('gabarito360-agendador.timer','LastTriggerUSec')
for _ in range(35):
 time.sleep(2)
 agora=estado('gabarito360-agendador.timer','LastTriggerUSec')
 if agora and agora!='n/a' and agora!=antes:break
else:raise RuntimeError('Timer não disparou')
assert estado('gabarito360-agendador.service','Result')=='success'
r['timer_disparou']=True
# Encerramento/start real dos dois contêineres, não apenas restart dos daemons.
t=time.monotonic()
for id in [9111,9106]:
 run(['pct','shutdown',str(id),'--timeout','60'])
 assert 'stopped' in run(['pct','status',str(id)])
for id in [9106,9111]:run(['pct','start',str(id)])
m.rede()
for _ in range(40):
 try:
  pct(9106,'systemctl','is-active','--quiet','mariadb')
  for unit in ['nginx','php8.3-fpm','redis-server','gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer']:
   pct(9111,'systemctl','is-active','--quiet',unit)
  break
 except RuntimeError:time.sleep(2)
else:raise RuntimeError('Serviços não voltaram no boot')
r['reboot_e_rede_s']=round(time.monotonic()-t,2)
r['http_pos_reboot']=json.loads(pct(9111,'python3','/root/t2-http-restauracao.py','apos-reboot'))
consumir();r['job_pos_reboot']=True
r['fila_depois']=json.loads(helper('estado-fila'))
assert r['fila_depois']['falhos']==r['fila_antes']['falhos'] and r['fila_depois']['pendentes']==0
r['integridade']=json.loads(helper('pos-corte'))
pct(9106,'mariadb-check','--silent','--databases','gabarito-360')
r['mariadb_check_pos_reboot']=True
(W/'retomada.json').write_text(json.dumps(r,indent=2));print(json.dumps(r),flush=True)
