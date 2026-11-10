"""Testes de dependencias.py e do título do PR adotado (FN-078 subfase 4.2)."""
import sys
import unittest
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[1]))
from adotar_pr_dependabot import titulo_em_portugues  # noqa: E402
from dependencias import so_dependencias  # noqa: E402


class TestDependencias(unittest.TestCase):
    def test_reconhece_manifestos_e_travas(self):
        self.assertTrue(so_dependencias(['apps/api/composer.json', 'apps/api/composer.lock',
                                         'scripts/tailwind/package-lock.json', 'apps/api/omr/requirements.txt']))

    def test_codigo_ou_lista_vazia_nao_e_dependencia(self):
        self.assertFalse(so_dependencias(['apps/api/composer.lock', 'apps/api/app/Models/Prova.php']))
        self.assertFalse(so_dependencias([]))
        self.assertFalse(so_dependencias(['apps/web/public/js/libs/x/package.json.bak']))

    def test_titulo_em_portugues(self):
        self.assertEqual(titulo_em_portugues('Bump pillow from 12.3.0 to 13.0.0 in /scripts/branding'),
                         'chore(deps): atualiza pillow de 12.3.0 para 13.0.0 em /scripts/branding')
        self.assertEqual(titulo_em_portugues('chore(deps): bump the npm-menores group across 3 directories with 5 updates'),
                         'chore(deps): atualiza o grupo npm-menores (5 pacote(s))')
        self.assertEqual(titulo_em_portugues('chore(deps): update reportlab requirement from >=4.0 to >=5.1.0 in /apps/api/omr'),
                         'chore(deps): atualiza reportlab de >=4.0 para >=5.1.0 em /apps/api/omr')
        self.assertEqual(titulo_em_portugues('chore(deps): bump fonttools from 4.63.0 to 4.68.0 in /scripts/branding in the pip-menores group across 1 directory'),
                         'chore(deps): atualiza fonttools de 4.63.0 para 4.68.0 em /scripts/branding (grupo pip-menores)')
        self.assertEqual(titulo_em_portugues('chore(deps): bump actions/checkout from 4 to 7'),
                         'chore(deps): atualiza actions/checkout de 4 para 7')

    def test_workflows_sao_dependencias(self):
        self.assertTrue(so_dependencias(['.github/workflows/verificacao.yml']))
        self.assertFalse(so_dependencias(['.github/dependabot.yml']))


if __name__ == '__main__':
    unittest.main()
