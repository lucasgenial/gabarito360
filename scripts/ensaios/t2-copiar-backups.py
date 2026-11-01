"""Cópia pontual autorizada dos dois arquivos novos; sem retenção nem e-mail."""
from pathlib import Path
import datetime,hashlib,json,subprocess as sp
SSH=['ssh','-o','BatchMode=yes','-o','ServerAliveInterval=30','pve']
DEST=Path('D:/Backups-Proxmox/dump')
def remoto(cmd):return sp.check_output(SSH+[cmd],text=True)
nomes=[Path(p).name for p in remoto('cat /root/g360-t2-20261101/arquivos.txt').splitlines()]
assert len(nomes)==2 and all(n.startswith(('vzdump-lxc-106-2026_11_01-','vzdump-lxc-111-2026_11_01-')) and n.endswith('.tar.zst') for n in nomes)
remoto('proxmox-backup-copia preparar')
resultado=[]
try:
 linhas={x.split('\t')[0]:x.split('\t') for x in remoto('proxmox-backup-copia listar').splitlines()}
 for nome in nomes:
  _,size,expected=linhas[nome];size=int(size)
  final=DEST/nome;parcial=DEST/(nome+'.parcial-t2')
  assert final.parent.resolve()==DEST.resolve() and not final.exists() and not parcial.exists()
  sha=hashlib.sha256();total=0
  with parcial.open('xb') as f:
   p=sp.Popen(SSH+['proxmox-backup-copia ler '+nome],stdout=sp.PIPE,stderr=sp.PIPE)
   while chunk:=p.stdout.read(4*1024*1024):f.write(chunk);sha.update(chunk);total+=len(chunk)
   p.stdout.close();p.stdout=None
   _,err=p.communicate()
   assert p.returncode==0 and total==size and sha.hexdigest()==expected,'Cópia não confirmada; preservar parcial para diagnóstico'
  parcial.rename(final)
  resultado.append({'arquivo':nome,'bytes':total,'sha256':expected,'confirmado':True})
  print('Cópia confirmada:',nome,flush=True)
finally:remoto('proxmox-backup-copia encerrar')
r={'copiado_em_utc':datetime.datetime.now(datetime.timezone.utc).isoformat(),'copia_pontual_t2':True,'arquivos':resultado}
Path('D:/Backups-Proxmox/T2-COPIA-OK-2026-11-01.json').write_text(json.dumps(r,indent=2)+'\n')
print(json.dumps(r),flush=True)
