"""Contrato do roteiro Bash: comandos externos simulados, sem acesso a produção."""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

SCRIPT = Path(__file__).resolve().parents[1] / 'operacao' / 'deploy.sh'
ANTES = 'a' * 40
ALVO = 'b' * 40
MOCK = r'''#!/usr/bin/env python3
import json, os, sys
from pathlib import Path
cmd=Path(sys.argv[0]).name
args=sys.argv[1:]
if cmd=='runuser':
    p=Path(os.environ['G360_TEST_STATE'])
    s=json.loads(p.read_text())
    s['events'].append([cmd,*args])
    p.write_text(json.dumps(s))
    os.execvpe(args[3],args[3:],os.environ)
p=Path(os.environ['G360_TEST_STATE'])
s=json.loads(p.read_text())
s['events'].append([cmd,*args])
code=0
if cmd=='id': print('0')
elif cmd=='git':
    a=args[2:]
    if a==['rev-parse','--show-toplevel']: print(s['root'])
    elif a==['branch','--show-current']: print(s.get('branch','main'))
    elif a==['rev-parse','HEAD']: print(s['head'])
    elif a[:1]==['diff']: code=int(s.get('dirty',False))
    elif a[:1]==['merge-base'] and a[-1]=='origin/main': code=int(s.get('outside_main',False))
    elif a[:1]==['merge']:
        s['head']=a[-1]
        # Simula a substituição do arquivo de origem durante o fast-forward.
        if s.get('replace_source'): Path(s['source']).write_text('exit 97\n')
elif cmd=='php':
    if args[0]=='artisan':
        a=args[1:]; app=Path.cwd().name
        marker=Path.cwd()/'storage/framework/down'
        if a[0]=='down': marker.write_text('maintenance')
        elif a[0]=='up': marker.unlink(missing_ok=True)
        if a[0]=='migrate' and s.get('migration_failure'): code=1
        s['events'][-1].append('app='+app)
    else:
        if '--validar-corte' in args:
            code=int(s.get('cut_pending',False)); print('0')
        elif '--somente-manutencao' in args: print('0')
        else:
            values=s.setdefault('queue',[0])
            q=values.pop(0) if len(values)>1 else values[0]
            print(q)
elif cmd=='composer': code=int(s.get('composer_failure',False))
elif cmd=='systemctl':
    if args[0]=='show': print('active' if s.get('stop_failure') else 'inactive')
    if args[0]=='is-active' and (s.get('inactive') or args[-1]==s.get('inactive_unit')): code=3
elif cmd=='curl': print('500' if s.get('http_failure') else '200')
p.write_text(json.dumps(s))
sys.exit(code)
'''


class DeployTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="g360 deploy ' teste-")
        self.base = Path(self.temp.name)
        self.root = self.base / 'repo'
        (self.root / '.git').mkdir(parents=True)
        for app in ['api', 'web']:
            pasta = self.root / 'apps' / app
            (pasta / 'vendor').mkdir(parents=True)
            (pasta / 'vendor/autoload.php').touch()
            (pasta / 'composer.lock').touch()
            (pasta / 'storage/framework').mkdir(parents=True)
            (pasta / 'bootstrap/cache').mkdir(parents=True)
        self.bin = self.base / 'bin'
        self.bin.mkdir()
        for nome in ['id', 'runuser', 'git', 'php', 'composer', 'systemctl', 'curl', 'chown', 'sleep']:
            arq = self.bin / nome
            arq.write_text(MOCK)
            arq.chmod(0o755)
        self.source = self.base / 'deploy.sh'
        self.source.write_text(SCRIPT.read_text())
        self.state = self.base / 'state.json'

    def tearDown(self):
        self.temp.cleanup()

    def run_deploy(self, *, check=False, target=ALVO, legacy=False, hold=False, **scenario):
        s = {'root': str(self.root), 'head': ANTES, 'events': [], 'source': str(self.source), **scenario}
        self.state.write_text(json.dumps(s))
        env = {**os.environ, 'PATH': str(self.bin) + os.pathsep + os.environ['PATH'],
               'G360_TEST_STATE': str(self.state)}
        env.pop('G360_DEPLOY_SNAPSHOT', None)
        source = SCRIPT.parents[2] / 'infra/deploy.sh' if legacy else self.source
        args = ['bash', str(source), '--root', str(self.root), '--commit', target, '--from', ANTES, '--wait', '0']
        if hold:
            args.append('--manter-manutencao')
        if check:
            args.append('--check')
        self.result = subprocess.run(args, env=env, capture_output=True, text=True, timeout=20)
        self.s = json.loads(self.state.read_text())
        return self.result.returncode

    def artisan(self, command):
        return [e for e in self.s['events'] if e[:3] == ['php', 'artisan', command]]

    def test_check_nao_publica_nem_interrompe_servicos(self):
        self.assertEqual(self.run_deploy(check=True, queue=[7]), 0, self.result.stderr)
        self.assertEqual(self.s['head'], ANTES)
        self.assertFalse(self.artisan('down'))
        self.assertFalse(self.artisan('migrate'))
        self.assertFalse([e for e in self.s['events'] if e[:2] == ['systemctl', 'stop']])

    def test_preparacao_mantem_manutencao_e_nao_inicia_servicos_nem_corte(self):
        self.assertEqual(self.run_deploy(hold=True), 0, self.result.stderr)
        self.assertEqual(self.s['head'], ALVO)
        self.assertEqual(len(self.artisan('migrate')), 1)
        self.assertFalse(self.artisan('up'))
        self.assertFalse([e for e in self.s['events'] if e[:2] == ['systemctl', 'start']])
        self.assertFalse([e for e in self.s['events'] if e[0] == 'curl'])
        self.assertFalse([e for e in self.s['events'] if any('g360:' in a for a in e)])
        self.assertIn(['systemctl', 'stop', 'gabarito360-agendador.service'], self.s['events'])
        for app in ['api', 'web']:
            self.assertTrue((self.root / f'apps/{app}/storage/framework/down').exists())
        self.assertIn('PREPARADO EM MANUTENÇÃO', self.result.stdout)
        self.assertNotIn('DEPLOY CONCLUÍDO', self.result.stdout)

    def test_check_com_manutencao_nao_muda_o_ambiente(self):
        self.assertEqual(self.run_deploy(check=True, hold=True), 0, self.result.stderr)
        self.assertEqual(self.s['head'], ANTES)
        self.assertFalse(self.artisan('down'))
        self.assertFalse(self.artisan('migrate'))
        self.assertFalse([e for e in self.s['events'] if e[:2] == ['systemctl', 'stop']])

    def test_preparacao_nao_confirma_sucesso_se_servico_permanece_ativo(self):
        self.assertNotEqual(self.run_deploy(hold=True, stop_failure=True), 0)
        self.assertEqual(self.s['head'], ANTES)
        self.assertFalse(self.artisan('migrate'))
        self.assertNotIn('PREPARADO EM MANUTENÇÃO', self.result.stdout)

    def test_corte_pendente_impede_abertura_automatica_e_preserva_manutencao(self):
        self.assertNotEqual(self.run_deploy(cut_pending=True), 0)
        self.assertEqual(self.s['head'], ALVO)
        self.assertFalse(self.artisan('up'))
        self.assertFalse([e for e in self.s['events'] if e[:2] == ['systemctl','start']])
        for app in ['api','web']:
            self.assertTrue((self.root / f'apps/{app}/storage/framework/down').exists())

    def test_preparo_em_manutencao_permite_corte_ainda_pendente_sem_abrir(self):
        self.assertEqual(self.run_deploy(hold=True,cut_pending=True),0,self.result.stderr)
        self.assertFalse(self.artisan('up'))
        self.assertFalse([e for e in self.s['events'] if '--validar-corte' in e])

    def test_sha_invalido_nao_chega_ao_git(self):
        self.assertEqual(self.run_deploy(target='invalido; comando'), 2)
        self.assertFalse([e for e in self.s['events'] if e[0]=='git'])

    def test_entrada_antiga_delega_para_o_roteiro_novo(self):
        self.assertEqual(self.run_deploy(legacy=True, check=True), 0, self.result.stderr)
        self.assertEqual(self.s['head'], ANTES)
        self.assertFalse(self.artisan('migrate'))

    def test_preserva_manutencao_que_ja_existia(self):
        marker=self.root / 'apps/api/storage/framework/down'
        marker.write_text('manutenção de outra operação')
        self.assertNotEqual(self.run_deploy(), 0)
        self.assertEqual(marker.read_text(), 'manutenção de outra operação')
        self.assertFalse(self.artisan('up'))
        self.assertFalse(self.artisan('migrate'))

    def test_preflight_recusa_sha_diferente_arvore_suja_e_alvo_fora_da_main(self):
        for scenario in [{'head': 'c'*40}, {'dirty': True}, {'outside_main': True}, {'branch': 'outra'}, {'inactive': True}, {'inactive_unit':'gabarito360-worker@2'}]:
            with self.subTest(scenario=scenario):
                self.assertNotEqual(self.run_deploy(**scenario), 0)
                self.assertFalse(self.artisan('down'))
                self.assertFalse(self.artisan('migrate'))

    def test_fila_nao_esvazia_preserva_codigo_e_reabre_web(self):
        self.assertNotEqual(self.run_deploy(queue=[1]), 0)
        self.assertEqual(self.s['head'], ANTES)
        self.assertFalse(self.artisan('migrate'))
        self.assertFalse([e for e in self.s['events'] if e[:3] == ['systemctl', 'stop', 'gabarito360-worker@1']])
        self.assertFalse((self.root / 'apps/web/storage/framework/down').exists())

    def test_job_que_entra_apos_fechar_api_cancela_antes_da_atualizacao(self):
        self.assertNotEqual(self.run_deploy(queue=[0, 0, 1]), 0)
        self.assertEqual(self.s['head'], ANTES)
        self.assertFalse(self.artisan('migrate'))
        for app in ['api', 'web']:
            self.assertFalse((self.root / f'apps/{app}/storage/framework/down').exists())

    def test_falha_de_dependencia_ou_migracao_preserva_manutencao(self):
        for scenario in [{'composer_failure': True}, {'migration_failure': True}]:
            with self.subTest(scenario=scenario):
                self.assertNotEqual(self.run_deploy(**scenario), 0)
                self.assertEqual(self.s['head'], ALVO)
                for app in ['api', 'web']:
                    self.assertTrue((self.root / f'apps/{app}/storage/framework/down').exists())
                # Limpa apenas marcadores fictícios entre os subtestes.
                for marker in self.root.glob('apps/*/storage/framework/down'):
                    marker.unlink()

    def test_falha_http_volta_a_manutencao_sem_rollback_de_banco(self):
        self.assertNotEqual(self.run_deploy(http_failure=True), 0)
        for app in ['api', 'web']:
            self.assertTrue((self.root / f'apps/{app}/storage/framework/down').exists())
        self.assertFalse(self.artisan('migrate:rollback'))

    def test_sucesso_migra_so_api_e_sobrevive_a_substituicao_do_proprio_script(self):
        self.assertEqual(self.run_deploy(replace_source=True), 0, self.result.stderr)
        self.assertEqual(self.s['head'], ALVO)
        migrations = self.artisan('migrate')
        self.assertEqual(len(migrations), 1)
        self.assertIn('app=api', migrations[0])
        installs=[e for e in self.s['events'] if e[0]=='runuser' and 'composer' in e]
        self.assertEqual(len(installs),2)
        self.assertTrue(all(e[2]=='lucasdev' and '--no-scripts' in e for e in installs))
        self.assertEqual(len(self.artisan('package:discover')),2)
        for app in ['api', 'web']:
            self.assertFalse((self.root / f'apps/{app}/storage/framework/down').exists())
        self.assertIn(['systemctl', 'start', 'gabarito360-agendador.timer'], self.s['events'])


if __name__ == '__main__':
    unittest.main()
