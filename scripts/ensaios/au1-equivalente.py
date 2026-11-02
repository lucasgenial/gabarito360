"""Ensaio pontual autorizado: somente CTs temporários identificados, nunca 106/111."""
import datetime, hashlib, json, os, re, secrets, shutil, subprocess as sp, sys, time, traceback
from pathlib import Path

W = Path('/root/g360-au1-20261101')
ROOT = '/home/lucasdev/projetos/gabarito360'
SHA = '218858296aacb6fec9c44512d60469f51b2dd10c'
NOMES = {9106:'g360-au1-db-origem', 9111:'g360-au1-app-origem',
         9206:'g360-au1-db-restaurado', 9211:'g360-au1-app-restaurado'}
UNIDADES = ['gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer','gabarito360-agendador.service']
BASE = [('/mnt/pve/backup-sata/dump/vzdump-lxc-106-2026_11_01-08_20_46.tar.zst','6ac8aaf1f1ad5193e9127d25e92f1b747c282fc230dee8be3f763c1632314901'),
        ('/mnt/pve/backup-sata/dump/vzdump-lxc-111-2026_11_01-08_23_02.tar.zst','3e51a7083b522e07af6cdc9f37c67e22057aa48b2b17f5b09787dbe67fc46dd4')]
assert os.geteuid() == 0 and W.is_dir()

def salvar(nome, valor):
    p=W/nome; p.write_text(json.dumps(valor,indent=2)); p.chmod(0o600)

def run(args, entrada=None):
    r=sp.run(args,input=entrada,stdout=sp.PIPE,stderr=sp.PIPE)
    if r.returncode:
        p=W/'ultimo-erro-restrito.log'; p.write_bytes(r.stdout+r.stderr); p.chmod(0o600)
        raise RuntimeError('Comando recusado: '+args[0])
    return r.stdout.decode()

def pct(ct,*args,entrada=None):
    assert ct in NOMES
    assert f'hostname: {NOMES[ct]}\n' in run(['pct','config',str(ct)])
    return run(['pct','exec',str(ct),'--',*args],entrada)

def isolamento(ct):
    conf=run(['pct','config',str(ct)])
    assert f'hostname: {NOMES[ct]}\n' in conf
    assert not re.search(r'^(net\d+|mp\d+|dev\d+|hookscript|lxc.mount):',conf,re.M)
    assert re.search(r'^rootfs: local-lvm:vm-'+str(ct)+r'-disk-\d+(?:,|$)',conf,re.M)

def rede(db,app):
    for ct in [db,app]:
        isolamento(ct)
        assert json.loads(pct(ct,'ip','-j','route')) == []
    nomes={db:f'au1d{db}',app:f'au1a{app}'}
    run(['ip','link','add',nomes[db],'type','veth','peer','name',nomes[app]])
    for ct,endereco in [(db,'10.0.0.6/24'),(app,'10.0.0.11/24')]:
        pid=run(['lxc-info','-n',str(ct),'-pH']).strip()
        assert pid.isdigit()
        run(['ip','link','set',nomes[ct],'netns',pid])
        pct(ct,'ip','link','set',nomes[ct],'name','eth0')
        pct(ct,'ip','addr','replace',endereco,'dev','eth0')
        pct(ct,'ip','link','set','eth0','up')
    time.sleep(2)
    for ct in [db,app]:
        pct(ct,'ip','route','flush','default'); pct(ct,'ip','-6','route','flush','default')
        assert all(r.get('dst')=='10.0.0.0/24' for r in json.loads(pct(ct,'ip','-j','route')))

def restaurar(arquivos, ids):
    tempos={}
    for arquivo,ct in zip(arquivos,ids):
        assert not Path(f'/etc/pve/lxc/{ct}.conf').exists() and not Path(f'/etc/pve/qemu-server/{ct}.conf').exists()
        t=time.monotonic()
        run(['pct','restore',str(ct),arquivo,'--storage','local-lvm','--hostname',NOMES[ct],
             '--onboot','0','--start','0','--unique','1','--description','g360-au1-20261101-isolado','--tags','ensaio-au1'])
        conf=run(['pct','config',str(ct)])
        for chave in re.findall(r'^(net\d+):',conf,re.M):run(['pct','set',str(ct),'--delete',chave])
        run(['pct','set',str(ct),'--nameserver','127.0.0.1','--searchdomain','ensaio.invalid'])
        isolamento(ct)
        tempos[str(ct)]=round(time.monotonic()-t,2)
        print('Restaurado CT temporário',ct,flush=True)
    for ct in ids:run(['pct','start',str(ct)])
    rede(*ids)
    return tempos

def artisan(ct,*args):
    return pct(ct,'runuser','-u','www-data','--','php',ROOT+'/apps/api/artisan',*args)

def helper(ct,fase):return json.loads(pct(ct,'php','/root/au1-carga.php',fase))

def esperar_contagem(db,n):
    for _ in range(60):
        valor=pct(db,'mariadb','-N','-e','SELECT COUNT(*) FROM `gabarito-360`.auditoria_eventos').strip()
        if valor==str(n):return
        time.sleep(1)
    raise RuntimeError('Consumo não confirmado')

def saude(ct):
    for u in ['nginx','php8.3-fpm','redis-server','gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer']:
        pct(ct,'systemctl','is-active','--quiet',u)
    for caminho in ['/api/v1/health','/login']:
        assert pct(ct,'curl','-s','-o','/dev/null','-w','%{http_code}','http://localhost'+caminho)=='200'

def main():
    fase=sys.argv[1]
    if fase=='restaurar-base':
        t=time.monotonic()
        for arquivo,digest in BASE:
            assert hashlib.file_digest(open(arquivo,'rb'),'sha256').hexdigest()==digest
            run(['zstd','-t',arquivo])
        r={'inicio_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'candidato':SHA,
           'restauracao_s':restaurar([a for a,h in BASE],[9106,9111])}
        pct(9111,'systemctl','stop',*UNIDADES)
        pct(9111,'touch','/etc/g360-au1-ensaio')
        r['total_s']=round(time.monotonic()-t,2); salvar('base.json',r)
        print(json.dumps(r),flush=True)
    elif fase=='preparar':
        isolamento(9106); isolamento(9111)
        pct(9111,'systemctl','stop',*UNIDADES)
        run(['pct','push','9111',str(W/'release.bundle'),'/tmp/au1-release.bundle'])
        pct(9111,'chmod','644','/tmp/au1-release.bundle')
        pct(9111,'runuser','-u','lucasdev','--','git','-C',ROOT,'fetch','/tmp/au1-release.bundle','HEAD')
        pct(9111,'runuser','-u','lucasdev','--','git','-C',ROOT,'reset','--hard',SHA)
        run(['pct','push','9111',str(W/'au1-carga.php'),'/root/au1-carga.php'])
        # Ajustes locais: nenhuma credencial real nova e nenhuma rede externa.
        codigo="""from pathlib import Path
import re
for app in ['api','web']:
 p=Path('/home/lucasdev/projetos/gabarito360/apps')/app/'.env'; s=p.read_text()
 for k,v in {'APP_URL':'http://localhost'+('/api' if app=='api' else ''),'MAIL_MAILER':'array','SESSION_DOMAIN':'','SESSION_SECURE_COOKIE':'false','AUDITORIA_ATIVA':'false','AUDITORIA_RETENCAO_DIAS':'180','AUDITORIA_MESES_PRESERVADOS':'2026-05'}.items():
  s=re.sub(r'^'+k+r'=.*$',k+'='+v,s,flags=re.M) if re.search(r'^'+k+r'=',s,re.M) else s+'\\n'+k+'='+v+'\\n'
 p.write_text(s)
"""
        pct(9111,'python3','-',entrada=codigo.encode())
        for app in ['api','web']:
            pct(9111,'runuser','-u','www-data','--','php',ROOT+'/apps/'+app+'/artisan','config:clear')
        # Migrações exclusivamente na cópia. Nenhum corte/conversão de vínculos reais.
        artisan(9111,'migrate','--force')
        print(json.dumps(helper(9111,'inventario')),flush=True)
        senha=secrets.token_hex(24)
        sql="CREATE USER 'au1_ensaio'@'10.0.0.11' IDENTIFIED BY '"+senha+"'; GRANT SELECT,INSERT ON `gabarito-360`.auditoria_eventos TO 'au1_ensaio'@'10.0.0.11';"
        pct(9106,'mariadb',entrada=sql.encode())
        codigo="from pathlib import Path\np=Path('"+ROOT+"/apps/api/.env');p.write_text(p.read_text()+'\\nAUDITORIA_DB_USERNAME=au1_ensaio\\nAUDITORIA_DB_PASSWORD="+senha+"\\n')"
        pct(9111,'python3','-',entrada=codigo.encode());artisan(9111,'config:clear')
        print('Credencial de ensaio restrita configurada exclusivamente no clone.',flush=True)
    elif fase in ['popular','medir']:
        r=helper(9111,fase);salvar(fase+'.json',r);print(json.dumps(r),flush=True)
    elif fase=='fila':
        pct(9111,'systemctl','stop',*UNIDADES)
        r={'enfileirar':helper(9111,'enfileirar')};t=time.monotonic()
        artisan(9111,'up')  # O backup de referência foi tirado com a aplicação em manutenção.
        pct(9111,'systemctl','start','gabarito360-worker@1','gabarito360-worker@2')
        for _ in range(120):
            time.sleep(1)
            cont=pct(9106,'mariadb','-N','-e','SELECT COUNT(*) FROM `gabarito-360`.auditoria_eventos').strip()
            if cont=='1801000':break
        else:raise RuntimeError('Carga da fila não consumida')
        r['consumo_1000_s']=round(time.monotonic()-t,3)
        pct(9111,'systemctl','stop',*UNIDADES)
        r['checkpoint']=helper(9111,'checkpoint');salvar('fila.json',r);print(json.dumps(r),flush=True)
    elif fase=='backup':
        isolamento(9106);isolamento(9111)
        estados=[run(['pct','status',str(ct)]).strip() for ct in [9111,9106]]
        if all('running' in e for e in estados):
            pct(9111,'systemctl','stop',*UNIDADES,'nginx','php8.3-fpm')
            pct(9111,'systemctl','disable','gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer')
            pct(9111,'redis-cli','SAVE');pct(9111,'systemctl','stop','redis-server')
            pct(9106,'systemctl','stop','mariadb')
            for ct in [9111,9106]:run(['pct','shutdown',str(ct),'--timeout','60'])
        else:assert all('stopped' in e for e in estados) and (W/'fila.json').is_file()
        # /root não é atravessável pelo tar unprivileged. Temporário do vzdump fica em /var/tmp.
        W.chmod(0o700);(W/'backups').mkdir(mode=0o700,exist_ok=True)
        assert not list((W/'backups').glob('*.tar.zst'))
        t=time.monotonic()
        assert not re.search(r'^\s*mailto\s*:',Path('/etc/vzdump.conf').read_text(),re.M)
        run(['vzdump','9106','9111','--mode','stop','--compress','zstd','--dumpdir',str(W/'backups'),
             '--tmpdir','/var/tmp','--remove','0','--prune-backups','keep-all=1','--notification-mode','legacy-sendmail'])
        arquivos={}
        for ct in [9106,9111]:
            achados=list((W/'backups').glob(f'vzdump-lxc-{ct}-*.tar.zst'));assert len(achados)==1
            p=achados[0];p.chmod(0o600);run(['zstd','-t',str(p)])
            arquivos[str(ct)]={'arquivo':str(p),'bytes':p.stat().st_size,'sha256':hashlib.file_digest(open(p,'rb'),'sha256').hexdigest()}
        r={'arquivos':arquivos,'duracao_s':round(time.monotonic()-t,2)};salvar('backup.json',r);print('Backup coerente dos clones confirmado.',flush=True)
    elif fase=='tempos-fila':
        linhas=pct(9111,'journalctl','-u','gabarito360-worker@1','-u','gabarito360-worker@2','-o','json','--no-pager').splitlines()
        eventos=[json.loads(l) for l in linhas if l.strip()]
        eventos=[e for e in eventos if 'App\\Jobs\\GravarAuditoria' in e.get('MESSAGE','')]
        inicio=[int(e['__REALTIME_TIMESTAMP']) for e in eventos if e['MESSAGE'].endswith('RUNNING')]
        fim=[int(e['__REALTIME_TIMESTAMP']) for e in eventos if e['MESSAGE'].endswith('DONE')]
        assert len(inicio)==len(fim)==1000
        r={'jobs':1000,'primeiro_inicio_ao_ultimo_fim_s':round((max(fim)-min(inicio))/1000000,3),
           'exclui_espera_manutencao':True,'dois_workers':True}
        salvar('tempos-fila.json',r);print(json.dumps(r),flush=True)
    elif fase=='restaurar-checkpoint':
        b=json.loads((W/'backup.json').read_text());t=time.monotonic();arquivos=[]
        for ct in [9106,9111]:
            v=b['arquivos'][str(ct)];assert hashlib.file_digest(open(v['arquivo'],'rb'),'sha256').hexdigest()==v['sha256'];arquivos.append(v['arquivo'])
        r={'tempos_s':restaurar(arquivos,[9206,9211])}
        for _ in range(30):
            try:
                pct(9206,'systemctl','is-active','--quiet','mariadb');pct(9211,'systemctl','is-active','--quiet','redis-server');break
            except RuntimeError:time.sleep(2)
        else:raise RuntimeError('Serviços não recuperaram')
        for u in UNIDADES:exigir=pct(9211,'systemctl','show',u,'-p','ActiveState','--value').strip();assert exigir=='inactive'
        r['conferencia']=helper(9211,'conferir');r['total_s']=round(time.monotonic()-t,2)
        pct(9206,'mariadb-check','--silent','--databases','gabarito-360')
        r['mariadb_check']=True;salvar('restauracao.json',r);print(json.dumps(r),flush=True)
    elif fase=='retomar':
        isolamento(9206);isolamento(9211)
        pct(9211,'systemctl','enable','gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer')
        pct(9211,'systemctl','start','gabarito360-worker@1','gabarito360-worker@2')
        esperar_contagem(9206,1801001)
        r={'replay':helper(9211,'replay')};time.sleep(5);r['final']=helper(9211,'final')
        for app in ['api','web']:pct(9211,'runuser','-u','www-data','--','php',ROOT+'/apps/'+app+'/artisan','up')
        codigo="from pathlib import Path\np=Path('"+ROOT+"/apps/api/.env');p.write_text(p.read_text().replace('AUDITORIA_ATIVA=false','AUDITORIA_ATIVA=true'))"
        pct(9211,'python3','-',entrada=codigo.encode());artisan(9211,'config:clear')
        pct(9211,'systemctl','reload','php8.3-fpm')
        assert pct(9211,'curl','-s','-H','Accept: application/json','-o','/dev/null','-w','%{http_code}','http://localhost/api/v1/alunos')=='401'
        esperar_contagem(9206,1801002);r['http']=helper(9211,'http')
        # Reinício automático do worker após falha de processo, exclusivamente na cópia.
        unidade='gabarito360-worker@1'
        pid=pct(9211,'systemctl','show',unidade,'-p','MainPID','--value').strip();assert pid.isdigit() and int(pid)>1
        pct(9211,'kill','-KILL',pid)
        for _ in range(20):
            time.sleep(1);novo=pct(9211,'systemctl','show',unidade,'-p','MainPID','--value').strip()
            if novo not in ['0',pid]:break
        else:raise RuntimeError('Worker não retomou')
        r['worker_reiniciou']=True
        pct(9211,'systemctl','start','gabarito360-agendador.timer','gabarito360-agendador.service')
        assert pct(9211,'systemctl','show','gabarito360-agendador.service','-p','Result','--value').strip()=='success'
        saude(9211);t=time.monotonic()
        for ct in [9211,9206]:run(['pct','shutdown',str(ct),'--timeout','60'])
        for ct in [9206,9211]:run(['pct','start',str(ct)])
        rede(9206,9211)
        for _ in range(40):
            try:saude(9211);pct(9206,'systemctl','is-active','--quiet','mariadb');break
            except (RuntimeError,AssertionError):time.sleep(2)
        else:raise RuntimeError('Boot incompleto')
        r['reboot_servicos_s']=round(time.monotonic()-t,2);r['apos_reboot']=helper(9211,'http')
        pct(9206,'mariadb-check','--silent','--databases','gabarito-360')
        r['health']=200;r['login']=200;r['mariadb_check']=True;salvar('retomada.json',r);print(json.dumps(r),flush=True)
    elif fase=='evidencia':
        r={'versao':1,'data_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'candidato':SHA,
           'somente_clones':True,'volume_sintetico':{'eventos':1800000,'dias':180,'eventos_por_dia':10000,'trafego_medido':False},
           'ambiente':{'mariadb':'10.11.14','php':'8.3.6','redis':'7.0.15','db_cpu':2,'db_ram_mib':1024,'app_cpu':4,'app_ram_mib':2048}}
        for nome in ['base','popular','medir','fila','tempos-fila','backup','restauracao','retomada']:
            r[nome]=json.loads((W/(nome+'.json')).read_text())
        r['fila']['espera_total_inclui_ajuste_manutencao_s']=r['fila'].pop('consumo_1000_s')
        r['limites']=['Mesmo host e backups locais; não mede desastre do hardware/local.','Carga e consultas sintéticas; não é SLA nem tráfego observado.',
                     'Candidato instalado só no clone; sem conversão V1 de contas reais e sem publicação.','Credencial restrita criada apenas no clone; grants reais continuam requisito da ativação.']
        salvar('evidencia.json',r);print('Evidência agregada pronta.',flush=True)
    elif fase=='encerrar':
        assert (W/'evidencia.json').is_file() and (W/'evidencia-exportada.ok').is_file()
        assert (W/'evidencia-exportada.ok').read_text().strip()==hashlib.file_digest(open(W/'evidencia.json','rb'),'sha256').hexdigest()
        for ct in [9211,9206,9111,9106]:
            isolamento(ct)
            assert 'description: g360-au1-20261101-isolado' in run(['pct','config',str(ct)])
            if 'running' in run(['pct','status',str(ct)]):run(['pct','shutdown',str(ct),'--timeout','60'])
            assert 'stopped' in run(['pct','status',str(ct)])
            run(['pct','destroy',str(ct),'--purge','1'])
            assert not Path(f'/etc/pve/lxc/{ct}.conf').exists()
            assert len(run(['pvesm','list','local-lvm','--vmid',str(ct)]).strip().splitlines())==1
        # Somente os backups/dados descartáveis deste ensaio; fontes no SSD não pertencem a W.
        assert W.resolve()==Path('/root/g360-au1-20261101') and not W.is_symlink()
        shutil.rmtree(W)
        print('Quatro contêineres, volumes próprios e área temporária removidos.',flush=True)
    else:raise RuntimeError('Fase desconhecida')

if __name__=='__main__':
    try:main()
    except Exception as e:
        if W.is_dir():salvar('falha-local.json',{'tipo':type(e).__name__,'pontos':[{'funcao':f.name,'linha':f.lineno} for f in traceback.extract_tb(e.__traceback__)]})
        print('Ensaio interrompido: '+type(e).__name__+'; diagnóstico restrito no host.',flush=True);sys.exit(1)
