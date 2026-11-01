"""Executar no host pve: restaura SOMENTE IDs temporários livres 9106/9111, sem bridge."""
import datetime, json, os, re, subprocess as sp, time
from pathlib import Path
W=Path('/root/g360-t2-20261101')
NOMES={9106:'g360-t2-db-20261101',9111:'g360-t2-app-20261101'}
assert os.geteuid()==0 and W.is_dir()

def run(args, entrada=None):
    r=sp.run(args,input=entrada,stdout=sp.PIPE,stderr=sp.PIPE)
    if r.returncode:
        (W/'ultimo-erro-restrito.log').write_bytes(r.stdout+r.stderr)
        raise RuntimeError('Falha no comando técnico: '+args[0])
    return r.stdout.decode()

def pct(id,*args,entrada=None):
    assert id in NOMES
    return run(['pct','exec',str(id),'--',*args],entrada)

def rede():
    # O par tem as duas pontas dentro dos CTs: não existe bridge/interface no host.
    for id,n in NOMES.items():
        assert n in run(['pct','config',str(id)])
        assert not re.search(r'^net\d+:',run(['pct','config',str(id)]),re.M)
        assert json.loads(pct(id,'ip','-j','route'))==[]
    pids={id:run(['lxc-info','-n',str(id),'-pH']).strip() for id in NOMES}
    run(['ip','link','add','t2db','type','veth','peer','name','t2app'])
    for id,n,ip in [(9106,'t2db','10.0.0.6/24'),(9111,'t2app','10.0.0.11/24')]:
        run(['ip','link','set',n,'netns',pids[id]])
        pct(id,'ip','link','set',n,'name','eth0')
        pct(id,'ip','addr','replace',ip,'dev','eth0')
        pct(id,'ip','link','set','eth0','up')
    # networkd pode reaplicar a configuração restaurada quando eth0 aparece.
    time.sleep(2)
    for id in NOMES:
        pct(id,'ip','route','flush','default')
        pct(id,'ip','-6','route','flush','default')
        routes=json.loads(pct(id,'ip','-j','route'))
        assert routes and all(r.get('dst')=='10.0.0.0/24' for r in routes)

if __name__=='__main__':
    inicio=time.monotonic();resultado={'temporarios':NOMES,'sem_rede_externa':True,'tempos_s':{}}
    resultado['inicio_utc']=datetime.datetime.now(datetime.timezone.utc).isoformat()
    (W/'restauracao-parcial.json').write_text(json.dumps(resultado,indent=2))
    arquivos=W.joinpath('arquivos.txt').read_text().splitlines()
    assert len(arquivos)==2
    for src,id in [(106,9106),(111,9111)]:
        assert not Path(f'/etc/pve/lxc/{id}.conf').exists()
        assert not Path(f'/etc/pve/qemu-server/{id}.conf').exists()
        candidatos=[p for p in arquivos if re.fullmatch(r'/mnt/pve/backup-sata/dump/vzdump-lxc-'+str(src)+r'-2026_11_01-[0-9_]+\.tar\.zst',p)]
        assert len(candidatos)==1 and Path(candidatos[0]).is_file()
        t=time.monotonic()
        run(['pct','restore',str(id),candidatos[0],'--storage','local-lvm','--hostname',NOMES[id],'--onboot','0','--start','0','--unique','1','--description','g360-t2-20261101-isolado','--tags','ensaio-t2'])
        run(['pct','set',str(id),'--delete','net0','--nameserver','127.0.0.1','--searchdomain','ensaio.invalid'])
        conf=run(['pct','config',str(id)])
        assert not re.search(r'^(net\d+|mp\d+|dev\d+|hookscript|lxc.mount):',conf,re.M)
        assert re.search(r'^rootfs: local-lvm:vm-'+str(id)+r'-disk-\d+(?:,|$)',conf,re.M)
        resultado['tempos_s']['restaurar_'+str(id)]=round(time.monotonic()-t,2)
        (W/'restauracao-parcial.json').write_text(json.dumps(resultado,indent=2))
        print('CT temporário restaurado:',id,flush=True)
    t=time.monotonic()
    for id in NOMES:run(['pct','start',str(id)])
    rede()
    pct(9111,'touch','/etc/g360-t2-ensaio')
    # Adaptações exclusivas dos clones: URL local e transporte em memória.
    codigo=r"""from pathlib import Path
import re
b=Path('/home/lucasdev/projetos/gabarito360/apps')
for a in ['api','web']:
 p=b/a/'.env';s=p.read_text()
 for k,v in {'APP_URL':'http://localhost'+('/api' if a=='api' else ''),'MAIL_MAILER':'array'}.items():
  s=re.sub(r'^'+k+r'=.*$',k+'='+v,s,flags=re.M)
 p.write_text(s)
"""
    pct(9111,'python3','-',entrada=codigo.encode())
    for a in ['api','web']:pct(9111,'runuser','-u','www-data','--','php',f'/home/lucasdev/projetos/gabarito360/apps/{a}/artisan','config:clear')
    if sp.run(['pct','exec','9106','--','systemctl','is-active','--quiet','mariadb']).returncode: pct(9106,'systemctl','restart','mariadb')
    pct(9106,'mariadb-check','--silent','--databases','gabarito-360')
    for _ in range(30):
        try:
            for unidade in ['nginx','php8.3-fpm','redis-server','gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer']:
                pct(9111,'systemctl','is-active','--quiet',unidade)
            assert pct(9111,'curl','-s','-o','/dev/null','-w','%{http_code}','http://localhost/api/v1/health')=='200'
            break
        except (RuntimeError,AssertionError):time.sleep(2)
    else:raise RuntimeError('Serviços não recuperaram')
    resultado['tempos_s']['boot_e_servicos']=round(time.monotonic()-t,2)
    resultado['tempos_s']['restauracao_ate_health']=round(time.monotonic()-inicio,2)
    resultado['mariadb_check']=True;resultado['servicos_ativos']=True;resultado['health']=200
    (W/'restauracao.json').write_text(json.dumps(resultado,indent=2))
    print(json.dumps(resultado),flush=True)
