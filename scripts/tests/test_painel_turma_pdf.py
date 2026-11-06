"""Integração do gerador real: recorte, paginação, identidade e orientação."""
import sys
import tempfile
import unittest
from pathlib import Path
from pypdf import PdfReader

sys.path.insert(0,str(Path(__file__).resolve().parents[2]/'apps/api/omr'))
from gerar_painel_turma import gerar


class PainelTurmaPdfTest(unittest.TestCase):
    def dados(self,saida,modo,total):
        return {'saida':str(saida),'orientacao':modo,
            'turma':{'nome':'Turma fictícia','serie':'3ª série','ano_letivo':2026},
            'identidade':{'identidade':'escola','escola':'Escola fictícia'},
            'recorte':{'disciplina':'Matemática','professor':'Professor fictício'},
            'filtros':{'inicio':'2026-01-01','fim':'2026-11-06'},
            'kpis':{'alunos':3,'avaliacoes':total,'resultados':6,'acerto_pct':75},
            'avaliacoes':[{'titulo':f'Avaliação {i+1:03d}','disciplina':'Matemática','data_aplicacao':'2026-11-01','corrigidos':3,'acerto_pct':75}for i in range(total)],
            'emissao':{'emissor':'Responsável fictício','perfil':'Coordenador','contexto':'Escola fictícia','emitido_em':'06/11/2026 12:00:00 -03:00',
                'codigo':'G360-00000000000000000000000000000000','url':'https://example.test/validar-documento?codigo=G360-00000000000000000000000000000000'}}

    def test_orientacoes_e_paginacao_preservam_linhas_recorte_e_emissor(self):
        with tempfile.TemporaryDirectory()as temp:
            for modo in ('retrato','paisagem'):
                with self.subTest(modo=modo):
                    path=Path(temp)/(modo+'.pdf');gerar(self.dados(path,modo,60));r=PdfReader(path)
                    self.assertGreater(len(r.pages),1)
                    text=''.join(p.extract_text()for p in r.pages)
                    for i in range(60):self.assertIn(f'Avaliação {i+1:03d}',text)
                    self.assertIn('01/01/2026 a 06/11/2026',text)
                    self.assertIn('75,0%',text)
                    for i,p in enumerate(r.pages):
                        self.assertEqual(modo=='paisagem',float(p.mediabox.width)>float(p.mediabox.height))
                        self.assertIn(f'{i+1:02d}/{len(r.pages):02d}',p.extract_text())
                        self.assertIn('Responsável fictício',p.extract_text())

    def test_recorte_vazio_nao_inventa_resultados(self):
        with tempfile.TemporaryDirectory()as temp:
            path=Path(temp)/'vazio.pdf';d=self.dados(path,'retrato',0);d['kpis'].update(resultados=0,acerto_pct=None)
            gerar(d);text=''.join(p.extract_text()for p in PdfReader(path).pages)
            self.assertIn('Nenhuma avaliação aplicada neste recorte.',text)
            self.assertNotIn('75,0%',text)


if __name__=='__main__':unittest.main()
