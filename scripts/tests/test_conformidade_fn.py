"""Testes da versão por ADR e de verificar_conformidade_fn.py (FN-078 subfase 3.4)."""
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPTS = Path(__file__).resolve().parents[1]


def adr(numero, afeta, texto='Corpo.'):
    return (f'---\nid: ADR-{numero}\ntitulo: Regra {numero}\nestado: Aceita\nsubstituida_por: []\n'
            f'data: 2026-11-10\nafeta_funcionalidades: [{afeta}]\nafeta_caminhos: []\n'
            f'verificacao: processo\nregras: []\n---\n# ADR-{numero}\n\n{texto}\n')


class TestConformidade(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self.tmp.name)
        self.escrever('docs/funcionalidades/README.md', '| FN-001 | Login |\n| FN-002 | Provas |\n')
        self.escrever('docs/funcionalidades/001-login/planejamento.md', '**Conforme princípios:** versão 1\n')
        self.escrever('docs/funcionalidades/002-provas/planejamento.md', '**Conforme princípios:** versão 1\n')
        self.escrever('docs/decisoes/0001-geral.md', adr('0001', 'todas'))
        self.escrever('docs/decisoes/0002-provas.md', adr('0002', 'FN-002'))
        self.escrever('apps/web/a.php', '<?php\n')
        self.rodar('verificar_principios.py', '--atualizar')  # versão 1
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

    def rodar(self, script, *args, corpo=''):
        env = dict(os.environ, PRINCIPIOS_RAIZ=str(self.raiz), PR_TITULO='', PR_CORPO=corpo,
                   PYTHONIOENCODING='utf-8')
        r = subprocess.run([sys.executable, str(SCRIPTS / script), *args], env=env, cwd=self.raiz,
                           capture_output=True, text=True, encoding='utf-8')
        return r.returncode, r.stdout + r.stderr

    def versao(self):
        return (self.raiz / 'docs/decisoes/VERSAO').read_text(encoding='utf-8')

    def test_so_o_adr_alterado_recebe_a_nova_versao(self):
        self.escrever('docs/decisoes/0002-provas.md', adr('0002', 'FN-002', 'Corpo novo.'))
        self.assertIn('1 -> 2', self.rodar('verificar_principios.py', '--atualizar')[1])
        texto = self.versao()
        self.assertRegex(texto, r'adr: ADR-0001 1 ')
        self.assertRegex(texto, r'adr: ADR-0002 2 ')

    def test_fn_conforme_aprova(self):
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.commit('fix(web): ajuste\n\nFN: FN-001')
        codigo, saida = self.rodar('verificar_conformidade_fn.py', 'main..HEAD')
        self.assertEqual(codigo, 0, saida)

    def test_adr_alterado_que_afeta_a_fn_exige_revalidacao(self):
        self.escrever('docs/decisoes/0002-provas.md', adr('0002', 'FN-002', 'Corpo novo.'))
        self.rodar('verificar_principios.py', '--atualizar')
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.commit('fix(web): ajuste das provas')
        codigo, saida = self.rodar('verificar_conformidade_fn.py', 'main..HEAD', corpo='FN: FN-002')
        self.assertEqual(codigo, 1)
        self.assertIn('ADR-0002 (versão 2)', saida)
        # FN-001 não é afetada pelo ADR-0002.
        codigo, saida = self.rodar('verificar_conformidade_fn.py', 'main..HEAD', corpo='FN: FN-001')
        self.assertEqual(codigo, 0, saida)

    def test_adr_para_todas_afeta_qualquer_fn(self):
        self.escrever('docs/decisoes/0001-geral.md', adr('0001', 'todas', 'Corpo novo.'))
        self.rodar('verificar_principios.py', '--atualizar')
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.commit('fix(web): ajuste')
        codigo, saida = self.rodar('verificar_conformidade_fn.py', 'main..HEAD', corpo='FN: FN-001')
        self.assertEqual(codigo, 1)
        self.assertIn('ADR-0001', saida)

    def test_revalidar_subindo_o_campo_aprova(self):
        self.escrever('docs/decisoes/0002-provas.md', adr('0002', 'FN-002', 'Corpo novo.'))
        self.rodar('verificar_principios.py', '--atualizar')
        self.escrever('docs/funcionalidades/002-provas/planejamento.md', '**Conforme princípios:** versão 2\n')
        self.escrever('apps/web/a.php', '<?php // novo\n')
        self.commit('fix(web): ajuste das provas\n\nFN: FN-002')
        codigo, saida = self.rodar('verificar_conformidade_fn.py', 'main..HEAD')
        self.assertEqual(codigo, 0, saida)

    def test_sem_mudanca_em_apps_aprova(self):
        self.escrever('docs/decisoes/0002-provas.md', adr('0002', 'FN-002', 'Corpo novo.'))
        self.rodar('verificar_principios.py', '--atualizar')
        self.commit('docs: ADR')
        self.assertEqual(self.rodar('verificar_conformidade_fn.py', 'main..HEAD', corpo='FN: FN-002')[0], 0)


if __name__ == '__main__':
    unittest.main()
