"""Testes de verificar_fn_no_pr.py (FN-078 subfase 3.3)."""
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[1] / 'verificar_fn_no_pr.py'


class TestFnNoPr(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self.tmp.name)
        self.escrever('docs/funcionalidades/README.md', '| FN-001 | Login |\n| FN-002 | Provas |\n')
        self.escrever('docs/funcionalidades/001-login/planejamento.md', '# FN-001\n')
        self.escrever('docs/funcionalidades/002-provas/planejamento.md', '# FN-002\n')
        self.escrever('apps/web/a.php', '<?php\n')
        self.git('init', '-q', '-b', 'main')
        self.commit('base')
        self.git('checkout', '-q', '-b', 'tarefa')

    def tearDown(self):
        self.tmp.cleanup()

    def escrever(self, rel, texto):
        p = self.raiz / rel
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(texto, encoding='utf-8')

    def git(self, *args):
        subprocess.run(['git', '-c', 'user.name=t', '-c', 'user.email=t@t', *args], cwd=self.raiz,
                       check=True, capture_output=True)

    def commit(self, mensagem):
        self.git('add', '-A')
        self.git('commit', '-q', '--allow-empty', '-m', mensagem)

    def rodar(self, titulo='', corpo=''):
        env = dict(os.environ, PRINCIPIOS_RAIZ=str(self.raiz), PR_TITULO=titulo, PR_CORPO=corpo,
                   PYTHONIOENCODING='utf-8')
        r = subprocess.run([sys.executable, str(SCRIPT), 'main..HEAD'], env=env, cwd=self.raiz,
                           capture_output=True, text=True, encoding='utf-8')
        return r.returncode, r.stdout + r.stderr

    def test_sem_mudanca_em_apps_aprova(self):
        self.escrever('docs/outro.md', 'x\n')
        self.commit('docs: texto')
        codigo, saida = self.rodar()
        self.assertEqual(codigo, 0, saida)
        self.assertIn('nenhuma mudança em apps/', saida)

    def test_apps_sem_fn_reprova(self):
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.commit('fix(web): ajuste')
        codigo, saida = self.rodar(titulo='fix(web): ajuste')
        self.assertEqual(codigo, 1)
        self.assertIn('sem FN citada', saida)

    def test_fn_citada_sem_planejamento_reprova(self):
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.commit('fix(web): ajuste')
        codigo, saida = self.rodar(corpo='FN: FN-002')
        self.assertEqual(codigo, 1)
        self.assertIn('002-provas/planejamento.md', saida)

    def test_so_dependencias_dispensa_fn(self):
        self.escrever('apps/web/composer.lock', '{"x": 2}\n')
        self.escrever('apps/api/omr/requirements.txt', 'numpy>=2\n')
        self.commit('chore(deps): atualiza pacotes')
        codigo, saida = self.rodar()
        self.assertEqual(codigo, 0, saida)
        self.assertIn('dependências', saida)

    def test_dependencias_com_codigo_exige_fn(self):
        self.escrever('apps/web/composer.lock', '{"x": 3}\n')
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.commit('chore(deps): atualiza e muda código')
        self.assertEqual(self.rodar()[0], 1)

    def test_fn_inexistente_reprova(self):
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.escrever('docs/funcionalidades/002-provas/planejamento.md', '# FN-002\nevidência\n')
        self.commit('fix(web): ajuste')
        codigo, saida = self.rodar(corpo='FN: FN-002, FN-999')
        self.assertEqual(codigo, 1)
        self.assertIn('FN-999', saida)

    def test_fn_no_corpo_do_pr_com_planejamento_aprova(self):
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.escrever('docs/funcionalidades/002-provas/planejamento.md', '# FN-002\nevidência\n')
        self.commit('fix(web): ajuste')
        codigo, saida = self.rodar(corpo='FN: FN-002')
        self.assertEqual(codigo, 0, saida)

    def test_fn_na_mensagem_do_commit_aprova(self):
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.escrever('docs/funcionalidades/001-login/planejamento.md', '# FN-001\nevidência\n')
        self.commit('fix(web): ajuste do login\n\nFN: FN-001')
        codigo, saida = self.rodar()
        self.assertEqual(codigo, 0, saida)


if __name__ == '__main__':
    unittest.main()
