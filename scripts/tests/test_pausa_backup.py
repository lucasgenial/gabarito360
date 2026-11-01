"""Contrato do recibo: arquivos de teste privados, nenhum backup/serviço real."""
import json, os, shutil, subprocess, unittest
from pathlib import Path
SCRIPT=Path(__file__).resolve().parents[1]/'operacao/pausa-backup-final.sh'

@unittest.skipUnless(os.geteuid()==0, 'Hook requer root')
class PausaTest(unittest.TestCase):
    def executar(self, caso):
        env={**os.environ,'G360_BACKUP_TIMEOUT':'1' if caso=='timeout' else '10'}
        proc=subprocess.Popen(['bash',str(SCRIPT),'/repo','a'*40,'b'*40],env=env,stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True)
        pasta=None
        try:
            linha=proc.stdout.readline().strip()
            self.assertTrue(linha.startswith('CHECKPOINT AGUARDANDO: '),linha)
            pasta=Path(linha.split(': ',1)[1])
            self.assertEqual(pasta.parent,Path('/root'))
            self.assertTrue(pasta.name.startswith('g360-backup-final-'))
            pronto=json.loads((pasta/'pronto.json').read_text())
            if caso!='timeout':
                r={k:pronto[k] for k in ['checkpoint','anterior','alvo']}
                r.update(verificado=True,arquivos=[{'papel':p,'arquivo':'/backup/'+p,'bytes':123,'sha256':'a'*64} for p in ['banco','aplicacao']])
                if caso=='outro_checkpoint':r['checkpoint']='obsoleto'
                if caso=='sem_aplicacao':r['arquivos'].pop()
                if caso=='sha_invalido':r['arquivos'][0]['sha256']='invalido'
                recibo=pasta/'confirmado.json'
                recibo.write_text(json.dumps(r));recibo.chmod(0o644 if caso=='permissao' else 0o600)
            proc.communicate(timeout=15)
            self.assertEqual(proc.returncode==0,caso=='valido')
            self.assertEqual((pasta/'aceito.json').exists(),caso=='valido')
        finally:
            if proc.poll() is None:proc.kill();proc.communicate()
            if pasta and pasta.parent==Path('/root') and pasta.name.startswith('g360-backup-final-'):
                shutil.rmtree(pasta)

    def test_recibo_completo_e_vinculado_libera(self):self.executar('valido')
    def test_timeout_e_recibos_invalidos_nao_liberam(self):
        for caso in ['timeout','outro_checkpoint','sem_aplicacao','sha_invalido','permissao']:
            with self.subTest(caso=caso):self.executar(caso)

if __name__=='__main__':unittest.main()
