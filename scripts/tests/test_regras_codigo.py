"""Testes de verificar_regras_codigo.py (FN-078 subfases 3.1 e 3.2)."""
import json
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPT = Path(__file__).resolve().parents[1] / 'verificar_regras_codigo.py'
LAYOUT = ("<footer class=\"rodape-app\">Gabarito360 - v{{ config('versao.numero') }} - "
          "{{ config('versao.ambiente') }}</footer>\n")


class TestRegrasCodigo(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self.tmp.name)
        for pasta in ('docs/decisoes', 'apps/web/resources/views/layouts', 'apps/web/resources/views/components',
                      'apps/web/public/js/libs', 'apps/web/config', 'apps/api/app/Http', 'apps/api/routes'):
            (self.raiz / pasta).mkdir(parents=True)
        self.escrever('apps/web/config/versao.php', "<?php return ['numero' => '0.1.0'];\n")
        self.escrever('apps/web/resources/views/layouts/app.blade.php', LAYOUT)
        self.escrever('apps/web/resources/views/ok.blade.php',
                      '<x-tabela titulo="T"><table></table></x-tabela>\n<script src="/js/a.js"></script>\n'
                      '<script type="application/json" id="c">@json($c)</script>\n')
        self.escrever('apps/web/public/js/libs/terceiro.js', 'alert(1);\n')
        self.escrever('apps/web/public/js/proprio.js', 'G360.confirmar("x"); confirmarRetirada();\n')
        self.escrever('apps/api/routes/api.php', "<?php Route::get('/x', fn() => response()->json([]));\n")

    def tearDown(self):
        self.tmp.cleanup()

    def escrever(self, rel, texto):
        p = self.raiz / rel
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(texto, encoding='utf-8')

    def rodar(self, *args):
        env = dict(os.environ, PRINCIPIOS_RAIZ=str(self.raiz), PYTHONIOENCODING='utf-8')
        r = subprocess.run([sys.executable, str(SCRIPT), *args], env=env, cwd=self.raiz,
                           capture_output=True, text=True, encoding='utf-8')
        return r.returncode, r.stdout + r.stderr

    def test_codigo_limpo_aprova(self):
        codigo, saida = self.rodar()
        self.assertEqual(codigo, 0, saida)

    def test_cada_regra_detecta_violacao_nova(self):
        casos = {
            'R-JS-EXTERNO': ('apps/web/resources/views/a.blade.php', '<button onclick="x()">A</button>\n'),
            'R-SEM-DIALOGO-NATIVO': ('apps/web/public/js/b.js', "if (!window.confirm('Sim?')) return;\n"),
            'R-API-SO-JSON': ('apps/api/app/Http/C.php', "<?php return view('tela');\n"),
            'R-TABELA-PADRAO': ('apps/web/resources/views/d.blade.php', '<table><tr><td>1</td></tr></table>\n'),
            'R-VERSAO-RODAPE': ('apps/web/resources/views/e.blade.php', '<p>Gabarito360 v0.2.0</p>\n'),
        }
        for regra, (rel, texto) in casos.items():
            with self.subTest(regra=regra):
                self.escrever(rel, texto)
                codigo, saida = self.rodar(regra)
                self.assertEqual(codigo, 1, saida)
                self.assertIn('nova violação', saida)
                (self.raiz / rel).unlink()

    def test_script_inline_reprova(self):
        self.escrever('apps/web/resources/views/f.blade.php', '<script>document.title = "x";</script>\n')
        self.assertEqual(self.rodar('R-JS-EXTERNO')[0], 1)

    def test_linha_de_base_tolera_violacao_antiga_e_nao_a_nova(self):
        self.escrever('apps/web/resources/views/a.blade.php', '<button onclick="x()">A</button>\n')
        self.rodar('--atualizar-linha-de-base')
        self.assertEqual(self.rodar('R-JS-EXTERNO')[0], 0)
        # A violação antiga muda de linha: a chave não depende do número da linha.
        self.escrever('apps/web/resources/views/a.blade.php', '<p>novo</p>\n<button onclick="x()">A</button>\n')
        self.assertEqual(self.rodar('R-JS-EXTERNO')[0], 0)
        self.escrever('apps/web/resources/views/a.blade.php',
                      '<button onclick="x()">A</button>\n<a onmouseover="y()">B</a>\n')
        codigo, saida = self.rodar('R-JS-EXTERNO')
        self.assertEqual(codigo, 1)
        self.assertIn('a.blade.php:2', saida)

    def test_correcao_exige_reduzir_linha_de_base(self):
        self.escrever('apps/web/resources/views/a.blade.php', '<button onclick="x()">A</button>\n')
        self.rodar('--atualizar-linha-de-base')
        (self.raiz / 'apps/web/resources/views/a.blade.php').unlink()
        codigo, saida = self.rodar('R-JS-EXTERNO')
        self.assertEqual(codigo, 1)
        self.assertIn('corrigida', saida)
        self.rodar('--atualizar-linha-de-base')
        self.assertEqual(self.rodar('R-JS-EXTERNO')[0], 0)
        dados = json.loads((self.raiz / 'docs/decisoes/linha-de-base.json').read_text(encoding='utf-8'))
        self.assertEqual(dados['regras']['R-JS-EXTERNO'], {})

    def test_linha_de_base_nao_pode_crescer_em_relacao_a_base(self):
        subprocess.run(['git', 'init', '-q'], cwd=self.raiz, check=True)
        subprocess.run(['git', '-c', 'user.name=t', '-c', 'user.email=t@t', 'commit', '-q', '--allow-empty', '-m', 'x'],
                       cwd=self.raiz, check=True)
        self.rodar('--atualizar-linha-de-base')
        subprocess.run(['git', 'add', '-A'], cwd=self.raiz, check=True)
        subprocess.run(['git', '-c', 'user.name=t', '-c', 'user.email=t@t', 'commit', '-q', '-m', 'base'],
                       cwd=self.raiz, check=True)
        self.escrever('apps/web/resources/views/a.blade.php', '<button onclick="x()">A</button>\n')
        self.rodar('--atualizar-linha-de-base')
        codigo, saida = self.rodar('R-JS-EXTERNO', '--base', 'HEAD')
        self.assertEqual(codigo, 1)
        self.assertIn('cresceu', saida)


if __name__ == '__main__':
    unittest.main()
