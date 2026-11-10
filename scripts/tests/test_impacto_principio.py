"""Testes de impacto_principio.py (FN-078 subfase 5.1)."""
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPTS = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(SCRIPTS))


def adr(numero, afeta, corpo='Corpo.', caminhos='[]'):
    return (f'---\nid: ADR-{numero}\ntitulo: Regra {numero}\nestado: Aceita\nsubstituida_por: []\n'
            f'data: 2026-11-10\nafeta_funcionalidades: [{afeta}]\nafeta_caminhos: {caminhos}\n'
            f'verificacao: processo\nregras: []\n---\n# ADR-{numero}\n\n{corpo}\n')


def linha(fn, nome, pasta, etapa='Concluída'):
    return (f'| {fn} | {nome} | — | Domínio | Concluída | {etapa} | '
            f'[planejamento]({pasta}/planejamento.md) · [casos de uso]({pasta}/casos-de-uso.md) |\n')


class TestImpacto(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self.tmp.name)
        self.escrever('docs/funcionalidades/README.md', linha('FN-001', 'Login', '001-login') +
                      linha('FN-002', 'Provas', '002-provas') + linha('FN-003', 'Antiga', '003-antiga', 'Substituída'))
        for pasta in ('001-login', '002-provas', '003-antiga'):
            self.escrever(f'docs/funcionalidades/{pasta}/planejamento.md', '**Conforme princípios:** versão 1\n')
        self.escrever('docs/decisoes/0001-geral.md', adr('0001', 'todas'))
        self.escrever('docs/decisoes/0002-provas.md', adr('0002', 'FN-002', caminhos='["apps/web/**"]'))
        self.escrever('apps/web/a.php', '<?php\n')
        self.escrever('apps/api/b.php', '<?php\n')
        subprocess.run(['git', 'init', '-q'], cwd=self.raiz, check=True)
        subprocess.run(['git', 'add', '-A'], cwd=self.raiz, check=True)
        self.rodar('--aplicar')  # versão 1

    def tearDown(self):
        self.tmp.cleanup()

    def escrever(self, rel, texto):
        p = self.raiz / rel
        p.parent.mkdir(parents=True, exist_ok=True)
        p.write_text(texto, encoding='utf-8')

    def rodar(self, *args):
        env = dict(os.environ, PRINCIPIOS_RAIZ=str(self.raiz), PYTHONIOENCODING='utf-8')
        r = subprocess.run([sys.executable, str(SCRIPTS / 'impacto_principio.py'), *args], env=env, cwd=self.raiz,
                           capture_output=True, text=True, encoding='utf-8')
        return r.returncode, r.stdout + r.stderr

    def adequacoes(self):
        return (self.raiz / 'docs/planejamento/04-adequacoes.md').read_text(encoding='utf-8')

    def test_sem_mudanca_nada_pendente(self):
        self.assertIn('Nenhuma funcionalidade pendente', self.adequacoes())
        self.assertEqual(self.rodar('--verificar')[0], 0)

    def test_adr_alterado_gera_adequacao_so_para_a_fn_afetada(self):
        self.escrever('docs/decisoes/0002-provas.md', adr('0002', 'FN-002', 'Corpo novo.', '["apps/web/**"]'))
        codigo, saida = self.rodar('--verificar')
        self.assertEqual(codigo, 1)  # mudou e não foi propagado
        self.assertIn('--aplicar', saida)
        codigo, saida = self.rodar('--aplicar')
        self.assertIn('1 -> 2', saida)
        texto = self.adequacoes()
        self.assertIn('Revalidar contra ADR-0002 e subir para a versão 2', texto)
        self.assertIn('[FN-002]', texto)
        self.assertNotIn('[FN-001]', texto)
        self.assertIn('| 1 |', texto)  # 1 arquivo versionado em apps/web/**
        self.assertEqual(self.rodar('--verificar')[0], 0)

    def test_adr_para_todas_ignora_fn_substituida(self):
        self.escrever('docs/decisoes/0001-geral.md', adr('0001', 'todas', 'Corpo novo.'))
        self.rodar('--aplicar')
        texto = self.adequacoes()
        self.assertIn('[FN-001]', texto)
        self.assertIn('[FN-002]', texto)
        self.assertNotIn('[FN-003]', texto)
        self.assertIn('2 funcionalidade(s) com adequação pendente', texto)

    def test_revalidar_tira_a_fn_da_lista(self):
        self.escrever('docs/decisoes/0002-provas.md', adr('0002', 'FN-002', 'Corpo novo.'))
        self.rodar('--aplicar')
        self.escrever('docs/funcionalidades/002-provas/planejamento.md', '**Conforme princípios:** versão 2\n')
        self.assertEqual(self.rodar('--verificar')[0], 1)
        self.rodar('--aplicar')
        self.assertIn('Nenhuma funcionalidade pendente', self.adequacoes())

    def test_impacto_de_um_adr(self):
        codigo, saida = self.rodar('--adr', 'ADR-0002')
        self.assertEqual(codigo, 0, saida)
        self.assertIn('Funcionalidades afetadas: 1', saida)
        self.assertIn('Arquivos no escopo: 1', saida)

    def test_padrao_de_caminho(self):
        import impacto_principio as I
        self.assertTrue(I._padrao('apps/web/**').match('apps/web/a/b.php'))
        self.assertTrue(I._padrao('**/*.blade.php').match('x.blade.php'))
        self.assertFalse(I._padrao('apps/web/*.php').match('apps/web/a/b.php'))


if __name__ == '__main__':
    unittest.main()
