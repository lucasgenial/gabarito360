"""Testes de verificar_principios.py e gerar_agents.py (FN-078 subfases 2.2 e 2.3)."""
import os
import subprocess
import sys
import tempfile
import unittest
from pathlib import Path

SCRIPTS = Path(__file__).resolve().parents[1]

ADR_OK = """---
id: ADR-0001
titulo: Regra de teste
estado: Aceita
substituida_por: []
data: 2026-11-10
afeta_funcionalidades: [FN-001]
afeta_caminhos: ["apps/**"]
verificacao: teste
regras: [R-TESTE]
agents_secao: Interface
agents_ordem: 10
agents_resumo:
  - "Primeira frase."
  - "Segunda frase."
---
# ADR-0001 — Regra de teste
"""

REGRAS = """- id: R-TESTE
  adr: ADR-0001
  estado: ativa
  gravidade: bloqueia
  comando: python3 scripts/ok.py
  descricao: Teste.
"""


class Base(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory()
        self.raiz = Path(self.tmp.name)
        (self.raiz / 'docs' / 'decisoes').mkdir(parents=True)
        (self.raiz / 'docs' / 'funcionalidades').mkdir(parents=True)
        (self.raiz / 'scripts').mkdir()
        (self.raiz / 'scripts' / 'ok.py').write_text('print(1)\n', encoding='utf-8')
        (self.raiz / 'docs' / 'funcionalidades' / 'README.md').write_text('| FN-001 | x |\n', encoding='utf-8')
        self.escrever('docs/decisoes/regras.yml', REGRAS)
        self.escrever('docs/decisoes/0001-regra-de-teste.md', ADR_OK)

    def tearDown(self):
        self.tmp.cleanup()

    def escrever(self, rel, texto):
        (self.raiz / rel).write_text(texto, encoding='utf-8')

    def rodar(self, script, *args):
        env = dict(os.environ, PRINCIPIOS_RAIZ=str(self.raiz), PYTHONIOENCODING='utf-8')
        r = subprocess.run([sys.executable, str(SCRIPTS / script), *args], env=env,
                           capture_output=True, text=True, encoding='utf-8')
        return r.returncode, r.stdout + r.stderr


class TestVerificarPrincipios(Base):
    def test_consistente_depois_de_atualizar(self):
        self.assertEqual(self.rodar('verificar_principios.py')[0], 1)  # sem VERSAO
        codigo, saida = self.rodar('verificar_principios.py', '--atualizar')
        self.assertIn('0 -> 1', saida)
        codigo, saida = self.rodar('verificar_principios.py')
        self.assertEqual(codigo, 0, saida)
        self.assertIn('versão 1', saida)

    def test_mudar_adr_exige_nova_versao(self):
        self.rodar('verificar_principios.py', '--atualizar')
        self.escrever('docs/decisoes/0001-regra-de-teste.md', ADR_OK + '\nTexto novo.\n')
        codigo, saida = self.rodar('verificar_principios.py')
        self.assertEqual(codigo, 1)
        self.assertIn('VERSAO desatualizada', saida)
        self.assertIn('1 -> 2', self.rodar('verificar_principios.py', '--atualizar')[1])

    def test_adr_verificavel_sem_regra(self):
        self.escrever('docs/decisoes/0001-regra-de-teste.md', ADR_OK.replace('regras: [R-TESTE]', 'regras: []'))
        self.rodar('verificar_principios.py', '--atualizar')
        codigo, saida = self.rodar('verificar_principios.py')
        self.assertEqual(codigo, 1)
        self.assertIn('sem regra', saida)

    def test_regra_inexistente_e_fn_inexistente(self):
        texto = ADR_OK.replace('[R-TESTE]', '[R-NAO-EXISTE]').replace('[FN-001]', '[FN-999]')
        self.escrever('docs/decisoes/0001-regra-de-teste.md', texto)
        self.rodar('verificar_principios.py', '--atualizar')
        codigo, saida = self.rodar('verificar_principios.py')
        self.assertEqual(codigo, 1)
        self.assertIn('R-NAO-EXISTE', saida)
        self.assertIn('FN-999', saida)

    def test_adr_sem_cabecalho(self):
        self.escrever('docs/decisoes/0002-sem-campos.md', '# ADR-0002\n')
        self.rodar('verificar_principios.py', '--atualizar')
        codigo, saida = self.rodar('verificar_principios.py')
        self.assertEqual(codigo, 1)
        self.assertIn('sem cabeçalho', saida)

    def test_id_diferente_do_arquivo(self):
        self.escrever('docs/decisoes/0001-regra-de-teste.md', ADR_OK.replace('id: ADR-0001', 'id: ADR-0009'))
        self.rodar('verificar_principios.py', '--atualizar')
        self.assertIn('diferente do arquivo', self.rodar('verificar_principios.py')[1])

    def test_regra_ativa_sem_script(self):
        self.escrever('docs/decisoes/regras.yml', REGRAS.replace('scripts/ok.py', 'scripts/falta.py'))
        self.rodar('verificar_principios.py', '--atualizar')
        codigo, saida = self.rodar('verificar_principios.py')
        self.assertEqual(codigo, 1)
        self.assertIn('sem comando com script existente', saida)


class TestGerarAgents(Base):
    def test_gera_e_verifica(self):
        self.rodar('verificar_principios.py', '--atualizar')
        self.assertEqual(self.rodar('gerar_agents.py', '--verificar')[0], 1)  # ainda não existe
        self.assertEqual(self.rodar('gerar_agents.py')[0], 0)
        texto = (self.raiz / 'AGENTS.md').read_text(encoding='utf-8')
        self.assertIn('## Interface', texto)
        self.assertIn('- Primeira frase.\n- Segunda frase. ([ADR-0001](docs/decisoes/0001-regra-de-teste.md))', texto)
        self.assertIn('Versão dos princípios: 1', texto)
        self.assertEqual(self.rodar('gerar_agents.py', '--verificar')[0], 0)

    def test_edicao_manual_e_detectada(self):
        self.rodar('verificar_principios.py', '--atualizar')
        self.rodar('gerar_agents.py')
        with open(self.raiz / 'AGENTS.md', 'a', encoding='utf-8') as f:
            f.write('- Regra escrita à mão.\n')
        self.assertEqual(self.rodar('gerar_agents.py', '--verificar')[0], 1)

    def test_adr_substituido_sai_do_agents(self):
        texto = ADR_OK.replace('estado: Aceita', 'estado: Substituída')
        self.escrever('docs/decisoes/0001-regra-de-teste.md', texto)
        self.rodar('gerar_agents.py')
        self.assertNotIn('Primeira frase', (self.raiz / 'AGENTS.md').read_text(encoding='utf-8'))


if __name__ == '__main__':
    unittest.main()
