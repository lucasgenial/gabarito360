"""Mesmo desenho no PDF individual e no lote; só dados fictícios."""
import importlib.util
from pathlib import Path
import tempfile
import unittest
import re

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
            self.assertEqual(re.sub(r'Página \d+/\d+','',individual.pages[0].extract_text()), re.sub(r'Página \d+/\d+','',paginas.pages[0].extract_text()))
            self.assertIn('Página 01/01',individual.pages[0].extract_text())
            self.assertIn('Página 01/02',paginas.pages[0].extract_text())
            self.assertIn('Página 02/02',paginas.pages[1].extract_text())
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
                for numero, page in enumerate(pages,1):
                    text = page.extract_text()
                    self.assertIn(f'Página {numero:02d}/{len(pages):02d}',text)
                    self.assertIn('Emissor fictício', text)
                    self.assertIn('G360-'+'A'*32, text)
                    self.assertIn('2026-11-05 12:00:00 UTC', text)
                text = '\n'.join(p.extract_text() for p in pages)
                self.assertIn('180', text)
                if modelo == 'professor':
                    self.assertIn('75,0% (6/8)', text)
                    self.assertIn('Relatório do professor', text)
                else:
                    self.assertNotIn('75,0%', text)

    def test_professor_preserva_cabecalho_logos_nota_e_resumo_do_aluno(self):
        with tempfile.TemporaryDirectory() as tmp:
            paginas = []
            for modelo in ('aluno', 'professor'):
                d = dados('Pessoa fictícia para conferir o padrão institucional')
                d['modelo'] = modelo
                d['logo'] = str(MODULO.parents[3] / 'apps/web/public/images/g360-carregando.webp')
                d['logo_ente'] = d['logo']
                d['saida'] = str(Path(tmp) / f'{modelo}.pdf')
                pdf.gerar(d)
                paginas.append(PdfReader(d['saida']).pages)
            prefixos = [p[0].extract_text().split('QUESTÃO POR QUESTÃO')[0] for p in paginas]
            self.assertEqual(prefixos[0], prefixos[1])
            for pages in paginas:
                for page in pages:
                    self.assertEqual(prefixos[0], page.extract_text().split('QUESTÃO POR QUESTÃO')[0])
                    self.assertGreater(len(page.images), 0)
                    text = page.extract_text()
                    for item in ('REDE FICTÍCIA', 'Escola fictícia', 'SIMULADO FICTÍCIO', '6,9', 'Acertos', 'Erros'):
                        self.assertIn(item.casefold(), text.casefold())

    def test_textos_longos_paginam_sem_perder_questoes(self):
        with tempfile.TemporaryDirectory() as tmp:
            for modelo in ('aluno', 'professor'):
                d = dados('Pessoa fictícia '+('de nome composto '*12))
                d.update(modelo=modelo, escola='Colégio fictício '+('de educação integral '*10),
                    titulo='Simulado fictício '+('de avaliação de aprendizagem '*8))
                d['folha']=[dict(d['folha'][n%45],numero_questao=n+1) for n in range(90)]
                d['emissao']={'emissor':'Emissor fictício','perfil':'Coordenador','contexto':d['escola'],
                    'emitido_em':'05/11/2026 09:00:00 -03:00','codigo':'G360-'+'A'*32,
                    'url':'https://homologa.gabarito360.com.br/validar-documento?codigo=G360-'+'A'*32}
                d['saida']=str(Path(tmp)/f'{modelo}-longo.pdf')
                pdf.gerar(d)
                numeros=[]
                for page in PdfReader(d['saida']).pages:
                    corpo=page.extract_text().split('QUESTÃO POR QUESTÃO',1)[1]
                    numeros.extend(int(n) for n in re.findall(r'(?m)^(\d{2,3})(?:\s*·[^\n]*)?$',corpo))
                self.assertEqual(list(range(1,91)),numeros)


if __name__ == '__main__':
    unittest.main()
