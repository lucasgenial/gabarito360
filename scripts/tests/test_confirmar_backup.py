"""Verificador do host com pct simulado e arquivos zstd reais, sem contêineres."""
import datetime as dt, json, os, shutil, subprocess as sp, tempfile, unittest
from pathlib import Path
SCRIPT=Path(__file__).resolve().parents[1]/'operacao/confirmar-backup-final.py'
MOCK="""#!/usr/bin/env python3
import json,os,subprocess,sys
from pathlib import Path
a=sys.argv[4:]
if a[0]=='cat':print(Path(a[1]).read_text())
elif a[0]=='systemctl':print('inactive')
elif a[0]=='runuser':print('a'*40)
elif a[0]=='python3':sys.exit(subprocess.run(a,input=sys.stdin.buffer.read()).returncode)
elif a[0]=='test' and a[1]=='!':sys.exit(int(Path(a[-1]).exists()))
"""
@unittest.skipUnless(os.geteuid()==0, 'Pasta de checkpoint requer root')
class ConfirmacaoTest(unittest.TestCase):
 def executar(self,caso):
  with tempfile.TemporaryDirectory(prefix='g360-backup-final-',dir='/root') as nome:
   pasta=Path(nome);bin=pasta/'bin';bin.mkdir()
   fake=bin/'pct';fake.write_text(MOCK);fake.chmod(0o700)
   pronto={'checkpoint':'teste','raiz':'/home/lucasdev/projetos/gabarito360','anterior':'a'*40,'alvo':'b'*40,'criado_utc':(dt.datetime.now(dt.timezone.utc)-dt.timedelta(seconds=5)).isoformat(),'limite_segundos':60}
   (pasta/'pronto.json').write_text(json.dumps(pronto))
   arquivos=[]
   for ct in [9106,9111]:
    data='2020_01_01-00_00_00' if caso=='antigo' else dt.datetime.now().strftime('%Y_%m_%d-%H_%M_%S')
    n=9999 if caso=='ct_errado' and ct==9106 else ct
    arquivo=pasta/f'vzdump-lxc-{n}-{data}.tar.zst'
    if caso=='corrompido':arquivo.write_bytes(b'invalido')
    else:sp.run(['zstd','-q','-o',str(arquivo)],input=b'backup ficticio para teste',check=True)
    arquivos.append(arquivo)
   env={**os.environ,'PATH':str(bin)+os.pathsep+os.environ['PATH']}
   r=sp.run(['python3',str(SCRIPT),'--ct-banco','9106','--ct-aplicacao','9111','--checkpoint',nome,'--banco',str(arquivos[0]),'--aplicacao',str(arquivos[1])],env=env,capture_output=True,text=True)
   self.assertEqual(r.returncode==0,caso=='valido',r.stderr)
   self.assertEqual((pasta/'confirmado.json').exists(),caso=='valido')
   if caso=='valido':
    recibo=json.loads((pasta/'confirmado.json').read_text());self.assertEqual(len(recibo['arquivos']),2)
    self.assertEqual((pasta/'confirmado.json').stat().st_mode & 0o777,0o600)
 def test_valido(self):self.executar('valido')
 def test_recusa_antigo_ct_errado_e_corrompido(self):
  for c in ['antigo','ct_errado','corrompido']:
   with self.subTest(caso=c):self.executar(c)
if __name__=='__main__':unittest.main()
