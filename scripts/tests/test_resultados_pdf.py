"""Mesmo desenho no PDF individual e no lote; só dados fictícios."""
import importlib.util
from pathlib import Path
import tempfile
import unittest

from pypdf import PdfReader

MODULO = Path(__file__).resolve().parents[2] / 'apps/api/omr/gerar_resultado.py'
spec = importlib.util.spec_from_file_location('resultado', MODULO)
pdf = importlib.util.module_from_spec(spec)
spec.loader.exec_module(pdf)


def dados(nome):
    return {'titulo': 'Simulado fictício', 'disciplina': 'Ciências Humanas',
            'escola': 'Escola fictícia', 'rede': 'Rede fictícia',
            'nota_maxima': 10, 'nota': {'nota_final': 6.9, 'acertos': 31, 'total_questoes': 45},
            'aluno': {'nome': nome, 'matricula': 'FICTICIA', 'turma': '3B'},
            'media_turma': 3.5,
            'folha': [{'numero_questao': n, 'gabarito': 'B', 'marcada': 'A' if n % 3 else 'B',
                       'situacao': 'incorreta' if n % 3 else 'correta'} for n in range(1, 46)]}


class ResultadosPdfTest(unittest.TestCase):
    def test_lote_repete_modelo_sem_misturar_alunos_e_individual_continua_igual(self):
        with tempfile.TemporaryDirectory() as tmp:
            a, b = dados('Aluno fictício A'), dados('Aluno fictício B')
            a['saida'] = str(Path(tmp) / 'individual.pdf')
            pdf.gerar(a)
            lote = str(Path(tmp) / 'lote.pdf')
            self.assertEqual(2, pdf.gerar_lote({'saida': lote, 'resultados': [a, b]})['total'])
            individual, paginas = PdfReader(a['saida']), PdfReader(lote)
            self.assertEqual(1, len(individual.pages))
            self.assertEqual(2, len(paginas.pages))
            self.assertEqual(individual.pages[0].extract_text(), paginas.pages[0].extract_text())
            self.assertIn('Aluno fictício B', paginas.pages[1].extract_text())
            self.assertNotIn('Aluno fictício A', paginas.pages[1].extract_text())
            self.assertIn('QUESTÃO POR QUESTÃO', paginas.pages[0].extract_text())

    def test_lote_vazio_recusado(self):
        with self.assertRaises(ValueError):
            pdf.gerar_lote({'saida': 'nao-criado.pdf', 'resultados': []})


if __name__ == '__main__':
    unittest.main()
