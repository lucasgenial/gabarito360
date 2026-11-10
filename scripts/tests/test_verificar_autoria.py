"""Testes da verificação de autoria e do gancho commit-msg (FN-078, UC-078-02)."""
import os
import shutil
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(RAIZ / 'scripts'))
import verificar_autoria  # noqa: E402

GANCHO = RAIZ / '.githooks' / 'commit-msg'


def git(repo, *args, env=None):
    return subprocess.run(['git', '-C', str(repo), *args], check=True, capture_output=True, text=True,
                          encoding='utf-8', env=env).stdout.strip()


class VerificarAutoriaTest(unittest.TestCase):
    def setUp(self):
        self.repo = Path(tempfile.mkdtemp())
        git(self.repo, 'init', '-q', '-b', 'main')
        git(self.repo, 'config', 'user.name', 'Responsavel Teste')
        git(self.repo, 'config', 'user.email', 'responsavel@exemplo.test')
        git(self.repo, 'config', 'core.hooksPath', '/dev/null')
        git(self.repo, 'commit', '-q', '--allow-empty', '-m', 'chore: base')
        self.base = git(self.repo, 'rev-parse', 'HEAD')
        self.cwd = os.getcwd()
        os.chdir(self.repo)

    def tearDown(self):
        os.chdir(self.cwd)
        shutil.rmtree(self.repo, ignore_errors=True)

    def commit(self, mensagem, autor=None):
        args = ['commit', '-q', '--allow-empty', '-m', mensagem]
        if autor:
            args += ['--author', autor]
        git(self.repo, *args)

    def verificar(self):
        return verificar_autoria.verificar(f'{self.base}..HEAD')

    def test_commit_limpo_aprova(self):
        self.commit('feat(api): implementa regra')
        total, erros = self.verificar()
        self.assertEqual((total, erros), (1, []))

    def test_coautoria_de_ia_reprova(self):
        self.commit('fix(web): corrige filtro\n\nCo-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>')
        _, erros = self.verificar()
        self.assertEqual(len(erros), 1)
        self.assertIn('marca de IA', erros[0])

    def test_coautoria_minuscula_de_codex_reprova(self):
        self.commit('docs: ajusta\n\nco-authored-by: Codex <codex@openai.com>')
        self.assertEqual(len(self.verificar()[1]), 1)

    def test_generated_with_reprova(self):
        self.commit('docs: ajusta\n\nGenerated with [Claude Code](https://claude.com/claude-code)')
        self.assertEqual(len(self.verificar()[1]), 1)

    def test_autor_ia_reprova(self):
        self.commit('feat(api): algo', autor='Claude <noreply@anthropic.com>')
        _, erros = self.verificar()
        self.assertTrue(any('autor identifica' in e for e in erros))

    def test_coautoria_humana_aprova(self):
        self.commit('feat: algo\n\nCo-Authored-By: Pessoa Real <pessoa@exemplo.test>')
        self.assertEqual(self.verificar()[1], [])

    def test_palavra_comum_nao_dispara(self):
        self.commit('docs: registra o agente gerente-projetos e o CLAUDE.md')
        self.assertEqual(self.verificar()[1], [])


@unittest.skipUnless(shutil.which('sh'), 'sh indisponível')
class GanchoCommitMsgTest(unittest.TestCase):
    def rodar(self, texto):
        with tempfile.NamedTemporaryFile('w', delete=False, suffix='.txt', encoding='utf-8', newline='\n') as f:
            f.write(texto)
            caminho = f.name
        try:
            subprocess.run(['sh', str(GANCHO), caminho], check=True, capture_output=True)
            return Path(caminho).read_text(encoding='utf-8')
        finally:
            os.remove(caminho)

    def test_remove_linhas_de_ia(self):
        saida = self.rodar('feat(web): tela\n\nCorpo da mensagem.\n\nCo-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>\n')
        self.assertNotIn('Co-Authored-By', saida)
        self.assertIn('Corpo da mensagem.', saida)
        self.assertFalse(saida.endswith('\n\n'))

    def test_preserva_mensagem_limpa(self):
        texto = 'fix(api): corrige nota\n\nDetalhe.\n'
        self.assertEqual(self.rodar(texto), texto)


if __name__ == '__main__':
    unittest.main(verbosity=2)
