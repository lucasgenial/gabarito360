"""No host Proxmox: verifica dois backups e confirma um checkpoint B5 ainda parado.
Não gera backups, não altera retenção, não envia mensagens e não abre serviços.
"""
import argparse, datetime as dt, hashlib, json, os, re, subprocess as sp
from pathlib import Path

def main():
    if not __debug__:
        raise RuntimeError("Não executar com otimização Python: validações são obrigatórias")
    p=argparse.ArgumentParser(description=__doc__)
    p.add_argument('--ct-banco',type=int,required=True)
    p.add_argument('--ct-aplicacao',type=int,required=True)
    p.add_argument('--checkpoint',required=True)
    p.add_argument('--banco',type=Path,required=True)
    p.add_argument('--aplicacao',type=Path,required=True)
    a=p.parse_args()
    assert os.geteuid()==0
    assert (a.ct_banco,a.ct_aplicacao) in {(106,111),(9106,9111)}
    assert re.fullmatch(r'/root/g360-backup-final-[a-z0-9_]+',a.checkpoint)
    def pct(*args,entrada=None):
        r=sp.run(['pct','exec',str(a.ct_aplicacao),'--',*args],input=entrada,stdout=sp.PIPE,stderr=sp.PIPE)
        if r.returncode:raise RuntimeError('Verificação no CT falhou; nenhum recibo confirmado')
        return r.stdout.decode().strip()
    pronto=json.loads(pct('cat',a.checkpoint+'/pronto.json'))
    for k in ['anterior','alvo']:assert re.fullmatch(r'[0-9a-f]{40}',pronto[k])
    assert pronto['raiz']=='/home/lucasdev/projetos/gabarito360'
    inicio=dt.datetime.fromisoformat(pronto['criado_utc'].replace('Z','+00:00'))
    def guardas():
        assert dt.datetime.now(dt.timezone.utc)<inicio+dt.timedelta(seconds=pronto['limite_segundos'])
        pct('test','!','-e',a.checkpoint+'/falhou.txt')
        pct('test','!','-e',a.checkpoint+'/confirmado.json')
        for u in ['gabarito360-worker@1','gabarito360-worker@2','gabarito360-agendador.timer','gabarito360-agendador.service','php8.3-fpm']:
            assert pct('systemctl','show',u,'-p','ActiveState','--value')=='inactive'
        for app in ['api','web']:pct('test','-f',pronto['raiz']+'/apps/'+app+'/storage/framework/down')
        assert pct('runuser','-u','lucasdev','--','git','-C',pronto['raiz'],'rev-parse','HEAD')==pronto['anterior']
    guardas()
    arquivos=[]
    for papel,ct,arquivo in [('banco',a.ct_banco,a.banco),('aplicacao',a.ct_aplicacao,a.aplicacao)]:
        assert arquivo.is_absolute() and arquivo.is_file() and not arquivo.is_symlink()
        nome=re.fullmatch(r'vzdump-lxc-'+str(ct)+r'-(\d{4}_\d{2}_\d{2}-\d{2}_\d{2}_\d{2})\.tar\.zst',arquivo.name)
        assert nome,'Arquivo não corresponde ao CT solicitado'
        snapshot=dt.datetime.strptime(nome[1],'%Y_%m_%d-%H_%M_%S').astimezone()
        assert snapshot>=inicio,'Backup anterior à parada dos escritores'
        st=arquivo.stat()
        sp.run(['zstd','-t',str(arquivo)],check=True,stdout=sp.DEVNULL,stderr=sp.DEVNULL)
        h=hashlib.sha256()
        with arquivo.open('rb') as f:
            while chunk:=f.read(4*1024*1024):h.update(chunk)
        atual=arquivo.stat()
        assert (st.st_ino,st.st_size,st.st_mtime_ns)==(atual.st_ino,atual.st_size,atual.st_mtime_ns)
        arquivos.append({'papel':papel,'arquivo':str(arquivo),'bytes':st.st_size,'sha256':h.hexdigest()})
    guardas()
    recibo={k:pronto[k] for k in ['checkpoint','anterior','alvo']}
    recibo.update(verificado=True,arquivos=arquivos)
    # Escrita exclusiva e publicação atômica, dentro da pasta privada do checkpoint.
    gravar="""import json,os,sys
from pathlib import Path
p=Path(sys.argv[1]);dados=sys.stdin.buffer.read()
assert not (p/'falhou.txt').exists() and not (p/'confirmado.json').exists()
os.umask(0o077)
with (p/'confirmado.tmp').open('xb') as f:
 f.write(dados);f.flush();os.fsync(f.fileno())
os.rename(p/'confirmado.tmp',p/'confirmado.json')
"""
    pct('python3','-c',gravar,a.checkpoint,entrada=json.dumps(recibo).encode())
    print(json.dumps(recibo,indent=2))

if __name__=='__main__':main()
