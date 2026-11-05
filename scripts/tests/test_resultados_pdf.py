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

    def test_modelos_paginam_e_identificam_cada_pagina_sem_perder_questoes(self):
        with tempfile.TemporaryDirectory() as tmp:
            for modelo in ('aluno', 'professor'):
                d = dados('Aluno fictício de nome extenso para conferir a paginação')
                d['modelo'] = modelo
                d['emissao'] = {'emissor': 'Emissor fictício', 'perfil': 'coordenador', 'contexto': 'Escola fictícia',
                    'emitido_em': '2026-11-05 12:00:00 UTC', 'codigo': 'G360-'+'A'*32,
                    'url': 'https://homologa.gabarito360.com.br/validar-documento?codigo=G360-'+'A'*32}
                d['folha'] = [dict(d['folha'][n % 45], numero_questao=n+1, turma={'percentual':50,'acertos':2,'total':4}, geral={'percentual':75,'acertos':6,'total':8}) for n in range(180)]
                d['saida'] = str(Path(tmp) / f'{modelo}.pdf')
                pdf.gerar(d)
                pages = PdfReader(d['saida']).pages
                self.assertGreater(len(pages), 1)
                for page in pages:
                    text = page.extract_text()
                    self.assertIn('Emissor fictício', text)
                    self.assertIn('G360-'+'A'*32, text)
                    self.assertIn('2026-11-05 12:00:00 UTC', text)
                text = '\n'.join(p.extract_text() for p in pages)
                self.assertIn('180', text)
                if modelo == 'professor':
                    self.assertIn('75,0% (6/8)', text)
                    self.assertIn('RELATÓRIO PEDAGÓGICO', text)
                else:
                    self.assertNotIn('75,0%', text)


if __name__ == '__main__':
    unittest.main()
