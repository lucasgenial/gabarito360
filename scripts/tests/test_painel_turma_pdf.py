"""Integração do gerador real: recorte, paginação, identidade e orientação."""
import sys
import tempfile
import unittest
from pathlib import Path
from pypdf import PdfReader
from reportlab.lib.units import mm

sys.path.insert(0,str(Path(__file__).resolve().parents[2]/'apps/api/omr'))
from gerar_painel_turma import gerar


class PainelTurmaPdfTest(unittest.TestCase):
    def conferir_contexto_por_pagina(self,reader,dados):
        for i,page in enumerate(reader.pages):
            with self.subTest(pagina=i+1):
                retangulos_qr=[]
                def operacao(op,args,cm,tm):
                    if op!=b're':return
                    x,y=map(float,args[:2])
                    px=cm[0]*x+cm[2]*y+cm[4];py=cm[1]*x+cm[3]*y+cm[5]
                    if 16*mm<=px<=40*mm and 20*mm<=py<=44*mm:retangulos_qr.append((px,py))
                text=page.extract_text(visitor_operand_before=operacao)
                self.assertIn(dados['identidade']['escola'],text)
                for value in (dados['emissao']['emissor'],dados['emissao']['perfil'],dados['emissao']['codigo'],dados['emissao']['emitido_em'],'Emitido pelo Gabarito360'):
                    self.assertIn(value,text)
                self.assertGreater(len(retangulos_qr),30,'QR vetorial ausente da área reservada do rodapé')
                self.assertIn(f'{i+1:02d}/{len(reader.pages):02d}',text)

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
                    path=Path(temp)/(modo+'.pdf');d=self.dados(path,modo,60);gerar(d);r=PdfReader(path)
                    self.conferir_contexto_por_pagina(r,d)
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

    def test_sem_filtros_php_array_vazio_ou_objeto_nas_duas_orientacoes(self):
        with tempfile.TemporaryDirectory()as temp:
            for modo in ('retrato','paisagem'):
                for filtros in ([],{}):
                    with self.subTest(modo=modo,filtros=filtros):
                        path=Path(temp)/(modo+str(type(filtros).__name__)+'.pdf')
                        d=self.dados(path,modo,1);d['filtros']=filtros
                        gerar(d);r=PdfReader(path)
                        text=' '.join(p.extract_text()for p in r.pages)
                        self.assertIn('Aplicação: Início do histórico a Hoje',text)
                        self.assertIn('Avaliação 001',text)
                        self.assertEqual(modo=='paisagem',float(r.pages[0].mediabox.width)>float(r.pages[0].mediabox.height))

    def test_situacoes_e_operacoes_usam_bases_diferentes_sem_inventar_participacao(self):
        with tempfile.TemporaryDirectory()as temp:
            path=Path(temp)/'situacoes.pdf';d=self.dados(path,'retrato',2)
            d['kpis'].update(alunos_com_resultado=2,resultados_com_percentual=5,avaliacoes_sem_resultado=1,leituras_revisao=1)
            d['situacoes_resultados']={'total':6,'dentro_criterio':4,'indicacao_recuperacao':2,'outros':0}
            d['acompanhamento']={'total':8,'revisao':1,'processamento':1,'falhas':0,'nao_identificados':1,'aguardando_leitura':0}
            gerar(d);text=' '.join(p.extract_text()for p in PdfReader(path).pages)
            self.assertIn('Dentro do critério registrado: 4',text)
            self.assertIn('Com indicação de recuperação: 2',text)
            self.assertIn('Base: 6 resultados.',text)
            self.assertIn('Base: 8 cartões vigentes',text)
            self.assertIn('percentual válido: 5',text)
            self.assertNotIn('100,0%',text)

    def dados_analiticos(self,saida,modo):
        d=self.dados(saida,modo,2)
        d['filtros']=[]
        d['kpis'].update(alunos_com_resultado=3,resultados_com_percentual=6,avaliacoes_sem_resultado=0)
        d['situacoes_resultados']={'total':6,'dentro_criterio':4,'indicacao_recuperacao':2,'outros':0}
        resumo={'n':6,'media_pct':75,'mediana_pct':75,'min_pct':50,'max_pct':100,'desvio_pct':20.4}
        faixas=[{'de':i*10,'ate':(i+1)*10,'label':f'{i*10}–'+('100%' if i==9 else f'<{(i+1)*10}%'),'total':2 if i in (5,7,9) else 0}for i in range(10)]
        items=[]
        for numero in range(1,46):
            anulada=numero>4;acertos=[3,3,2,1][numero-1] if not anulada else 0
            items.append({'numero':numero,'acertos':acertos,'base':3,'pct':round(100*acertos/3,1)if not anulada else None,
                'anulada':anulada,'sem_registro':0,'brancas':0,'ambiguas':0,'incorretas':3-acertos,'por_lingua':[]})
        d['analise']={'resumo':resumo,'distribuicao_acertos':{'faixas':faixas,'total':6,'percentuais_excluidos':0},
            'por_avaliacao':[dict(resumo,n=3,avaliacao_id=i+1,titulo=f'Avaliação {i+1:03d}')for i in range(2)],
            'questoes':{'disponivel':True,'avaliacao_id':1,'titulo':'Avaliação 001','items':items}}
        return d

    def test_seis_kpis_quatro_analises_e_questoes_paginadas_nas_duas_orientacoes(self):
        with tempfile.TemporaryDirectory()as temp:
            for modo in ('retrato','paisagem'):
                with self.subTest(modo=modo):
                    path=Path(temp)/(modo+'.pdf');d=self.dados_analiticos(path,modo);gerar(d);r=PdfReader(path)
                    self.conferir_contexto_por_pagina(r,d)
                    text=' '.join(p.extract_text()for p in r.pages)
                    for label in ('Alunos com resultado','Mediana do acerto','Faixa observada','Distribuição dos percentuais de acerto','Situação dos resultados','Desempenho por avaliação','Acerto por questão','Anulada'):
                        self.assertIn(label,text)
                    self.assertIn('50,0% a 100,0%',text)
                    self.assertIn('Base: 6 resultados com percentual válido',text)
                    self.assertIn('80–100%',text)
                    self.assertNotIn('90–100%',text)
                    self.assertIn('45',text)
                    for i,page in enumerate(r.pages):
                        self.assertIn(f'{i+1:02d}/{len(r.pages):02d}',page.extract_text())
                        self.assertIn('Responsável fictício',page.extract_text())
                        if 'Acerto por questão' in page.extract_text():
                            self.assertIn('Percentual de acerto',page.extract_text())

    def test_acompanhamento_e_sua_base_ficam_na_mesma_pagina(self):
        with tempfile.TemporaryDirectory()as temp:
            for modo in ('retrato','paisagem'):
                with self.subTest(modo=modo):
                    path=Path(temp)/(modo+'.pdf');d=self.dados_analiticos(path,modo)
                    d['acompanhamento']={'total':8,'revisao':1,'processamento':1,'falhas':0,'nao_identificados':1,'aguardando_leitura':0}
                    gerar(d);reader=PdfReader(path);self.conferir_contexto_por_pagina(reader,d)
                    paginas=[p.extract_text()for p in reader.pages]
                    indices=[i for i,t in enumerate(paginas)if 'Acompanhamento das leituras' in t]
                    self.assertEqual(len(indices),1)
                    self.assertIn('Base: 8 cartões vigentes',paginas[indices[0]])
                    self.assertIn('não são faltas de alunos.',paginas[indices[0]])


if __name__=='__main__':unittest.main()
