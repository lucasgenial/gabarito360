"""Fixtures geométricas sem nomes, QR pessoais ou arquivos de estudantes."""
import sys,unittest,tempfile
from pathlib import Path
import cv2
import numpy as np
sys.path.insert(0,str(Path(__file__).resolve().parents[2]/'apps/api/omr'))
import qualidade as Q
import ler_cartao as C
import layout as L

def folha(preenchida=False):
    im=np.full((1485,1050),255,np.uint8)
    for x,y in L.FIDUCIALS:
        cx,cy=round(x*5),round(y*5);cv2.rectangle(im,(cx-20,cy-20),(cx+20,cy+20),0,-1)
    for q,l,x,y in L.bolinhas(45,5):cv2.circle(im,(round(x*5),round(y*5)),round(L.grade(45,5)['r']*5),0,-1 if preenchida and l=='A' else 2)
    return im

class QualidadeOmrTest(unittest.TestCase):
    def test_folha_inteira_com_respostas_ou_legitimamente_em_branco(self):
        for marcada in [False,True]:
            with self.subTest(marcada=marcada):self.assertTrue(Q.analisar(folha(marcada))['aprovada'])
    def test_quadrados_internos_nao_substituem_cantos_ausentes(self):
        im=folha();im[30:95,30:95]=255;im[1390:1455,30:95]=255
        for x,y in [(.52,.39),(.10,.55)]:cv2.rectangle(im,(round(x*1050),round(y*1485)),(round(x*1050)+36,round(y*1485)+36),0,-1)
        self.assertEqual(Q.analisar(im)['codigo'],'marcadores_incompletos')
    def test_marcadores_parcialmente_cortados_sao_recusados(self):
        self.assertFalse(Q.analisar(folha()[45:,45:])['aprovada'])
    def test_bolinha_preenchida_nao_pode_substituir_marcador_ausente(self):
        im=folha(True);im[1390:1455,30:95]=255
        self.assertFalse(Q.analisar(im)['aprovada'])
    def test_baixa_resolucao(self):
        self.assertEqual(Q.analisar(cv2.resize(folha(),(420,594)))['codigo'],'baixa_resolucao')
    def test_desfoque_intenso(self):
        self.assertFalse(Q.analisar(cv2.GaussianBlur(folha(),(31,31),10))['aprovada'])
    def test_perspectiva_moderada(self):
        m=cv2.getPerspectiveTransform(np.float32([[0,0],[1049,0],[0,1484],[1049,1484]]),np.float32([[80,20],[1020,80],[20,1500],[1080,1520]]))
        self.assertTrue(Q.analisar(cv2.warpPerspective(folha(True),m,(1100,1600),borderValue=255))['aprovada'])
    def test_indicio_fraco_nao_vira_branco_confiavel(self):
        self.assertTrue(C.decidir({'A':.12,'B':.01,'C':0})['ambigua'])
        self.assertFalse(C.decidir({'A':.01,'B':0,'C':0})['ambigua'])
    def test_arquivo_invalido(self):
        with tempfile.TemporaryDirectory() as d:
            p=Path(d)/'invalido.jpg';p.write_bytes(b'imagem ficticia invalida')
            self.assertEqual(Q.avaliar(str(p))['codigo'],'imagem_ilegivel')

if __name__=='__main__':unittest.main()
