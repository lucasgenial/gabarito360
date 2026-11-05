"""
/**
 * Arquivo: apps/api/omr/gerar_resultado.py
 * Função: Gera o PDF (A4) do resultado de um aluno numa prova: nota, resumo e a
 *         folha questão por questão (acertou / errou com marcada → certa /
 *         em branco / anulada), para imprimir ou enviar ao aluno e responsáveis.
 *  Uso:  python3 gerar_resultado.py < dados.json
 *        dados: {saida, rede, escola, detalhe, logo (caminho, opcional), titulo, disciplina, data, nota_maxima,
 *                aluno:{nome, matricula, turma}, nota:{nota_final, acertos, total_questoes},
 *                lingua, media_turma, folha:[{numero_questao, gabarito, marcada, situacao}]}
 *  Saída (stdout, JSON): {ok, arquivo}
 */
"""
import json
import hashlib
import math
import os
import sys

# Também permite importação direta pelo ensaio de PDF.
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import cabecalho_documento as identidade

from reportlab.lib.colors import HexColor, white
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas
from reportlab.graphics import renderPDF
from reportlab.graphics.barcode.qr import QrCodeWidget
from reportlab.graphics.shapes import Drawing
from reportlab.platypus import Paragraph
from reportlab.lib.styles import ParagraphStyle
from xml.sax.saxutils import escape

BLUE, BLUE2, GRAY3 = HexColor("#001534"), HexColor("#001534"), HexColor("#6b6b6b")
CORES = {  # situação: (fundo, borda, texto)
    "correta": (HexColor("#e3f5e1"), HexColor("#168821"), HexColor("#0f5e17")),
    "incorreta": (HexColor("#fde8e4"), HexColor("#e52207"), HexColor("#a3190a")),
    "branco": (HexColor("#fff5c2"), HexColor("#b06800"), HexColor("#7a4800")),
    "anulada": (HexColor("#eeeeee"), HexColor("#9e9e9e"), HexColor("#555555")),
}
W, H = A4
FD = os.path.join(os.path.dirname(os.path.abspath(__file__)), "fonts")
for _nm in ("Regular", "SemiBold", "Bold", "ExtraBold"):
    pdfmetrics.registerFont(TTFont(f"Raleway-{_nm}", os.path.join(FD, f"Raleway-{_nm}.ttf")))


def fmt(n):
    return f"{float(n):.1f}".replace(".", ",")


def caber(c, txt, fonte, tam, larg):
    while tam > 6 and pdfmetrics.stringWidth(txt, fonte, tam) > larg:
        tam -= 0.25
    c.setFont(fonte, tam)


def texto_bloco(c, txt, fonte, tam, larg, x, y, cor, centro=False):
    """Preserva o tamanho padrão; nomes extensos quebram linha sem cortar dados."""
    c.setFillColor(cor)
    if pdfmetrics.stringWidth(txt, fonte, tam) <= larg:
        c.setFont(fonte, tam)
        (c.drawCentredString if centro else c.drawString)(x, y, txt)
        return 0
    estilo = ParagraphStyle('bloco', fontName=fonte, fontSize=tam, leading=tam+2, textColor=cor, alignment=1 if centro else 0)
    p = Paragraph(escape(txt), estilo)
    _, h = p.wrap(larg, H)
    p.drawOn(c, x-larg/2 if centro else x, y-h+estilo.leading)
    return max(0, h-estilo.leading)


class CanvasNumerado(canvas.Canvas):
    """Conclui rodapés após conhecer o total, inclusive em lotes de vários alunos."""
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self._paginas_g360 = []
        self._rodape_g360 = {}

    def showPage(self):
        self._paginas_g360.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        paginas = list(self._paginas_g360)
        total = len(paginas)
        for estado in paginas:
            self.__dict__.update(estado)
            rodape(self, self._rodape_g360, total)
            canvas.Canvas.showPage(self)
        canvas.Canvas.save(self)


def rodape(c, d, total):
    m = 16*mm
    modelo = 'Relatório do professor' if d.get('modelo') == 'professor' else 'Relatório do aluno'
    c.setFont('Raleway-Regular', 7.5); c.setFillColor(GRAY3)
    c.drawString(m, 10*mm, modelo)
    identidade.marca(c,(W-identidade.largura_marca(3*mm))/2,13*mm,3*mm)
    c.setFillColor(GRAY3);c.setFont('Raleway-Regular',7.5)
    c.drawRightString(W-m, 10*mm, f'Página {c.getPageNumber():02d}/{total:02d}')
    e = d.get('emissao')
    if not e:
        return
    c.setStrokeColor(HexColor('#d5dfeb'))
    c.line(m, 50*mm, W-m, 50*mm)
    tamanho = 24*mm
    topo = 46*mm
    largura = W-2*m-tamanho-6*mm
    estilo = ParagraphStyle('selo', fontName='Raleway-Regular', fontSize=7.5, leading=9.5, textColor=GRAY3)
    texto = ('<b>Emitido pelo Gabarito360</b><br/>'
             + 'Gerado por <b>'+escape(e['emissor'])+'</b> · '+escape(e['perfil'])+'<br/>'
             + escape(e['contexto'])+'<br/>'
             + 'Data/hora: '+escape(e['emitido_em'])+'<br/>'
             + 'Código: <b>'+escape(e['codigo'])+'</b><br/>'
             + 'Validação: '+escape(e['url'].split('?')[0]))
    p = Paragraph(texto, estilo)
    _, altura = p.wrap(largura, 40*mm)
    p.drawOn(c, m+tamanho+6*mm, topo-altura)
    # Um único QR vetorial por arquivo, reutilizado em todas as páginas.
    nome = 'g360qr_'+hashlib.sha256(e['url'].encode()).hexdigest()[:16]
    if not c.hasForm(nome):
        qr = QrCodeWidget(e['url']);x1,y1,x2,y2=qr.getBounds()
        desenho=Drawing(tamanho,tamanho,transform=[tamanho/(x2-x1),0,0,tamanho/(y2-y1),0,0]);desenho.add(qr)
        c.beginForm(nome,0,0,tamanho,tamanho)
        renderPDF.draw(desenho,c,0,0)
        c.endForm()
    c.saveState();c.translate(m,topo-tamanho);c.doForm(nome);c.restoreState()


def _resultado_pagina(d, c):
    aluno, nota, folha = d["aluno"], d["nota"], d["folha"]
    m = 16 * mm
    y = H - 16 * mm

    # A identidade ocupa sua altura real, incluindo logos e nomes extensos.
    y = identidade.desenhar(c, d, W, y, m) - 7*mm
    titulo = f"{d['titulo'].upper()} · RESULTADO"
    p = Paragraph(escape(titulo), ParagraphStyle('titulo', fontName='Raleway-ExtraBold',
                  fontSize=10.5, leading=13, textColor=white, alignment=1))
    _, h = p.wrap(W-2*m-8*mm, H)
    altura = max(10*mm, h+6*mm)
    c.setFillColor(BLUE)
    c.roundRect(m, y-altura, W-2*m, altura, 2*mm, stroke=0, fill=1)
    p.drawOn(c,m+4*mm,y-(altura-h)/2-h)
    top = y-altura-6*mm

    # Identificação e nota são duas superfícies com altura comum e conteúdo íntegro.
    note_width=36*mm; gap=4*mm; left_width=W-2*m-note_width-gap
    content_width=left_width-8*mm
    linha = " · ".join(filter(None, [
        f"Turma {aluno['turma']}" if aluno.get('turma') else None,
        f"Matrícula {aluno['matricula']}" if aluno.get('matricula') else None,
        ('Espanhol' if d.get('lingua')=='ESP' else 'Inglês') if d.get('lingua') else None,
        d.get('data'),
    ]))
    parts=[]
    for txt,font,size in [(aluno['nome'],'Raleway-Bold',13),(linha,'Raleway-Regular',9),
                          (d.get('disciplina') or '', 'Raleway-Regular',9)]:
        if txt:
            par=Paragraph(escape(txt),ParagraphStyle('aluno',fontName=font,fontSize=size,
                          leading=size+3,textColor=HexColor('#222222') if not parts else GRAY3))
            _,ph=par.wrap(content_width,H);parts.append((par,ph))
    box_height=max(26*mm,sum(ph for _,ph in parts)+8*mm+max(0,len(parts)-1)*2*mm)
    for x,bw in [(m,left_width),(W-m-note_width,note_width)]:
        c.setFillColor(HexColor('#f5f8fc'));c.setStrokeColor(HexColor('#d5dfeb'));c.setLineWidth(.6)
        c.roundRect(x,top-box_height,bw,box_height,2*mm,stroke=1,fill=1)
    py=top-4*mm
    for par,ph in parts:
        par.drawOn(c,m+4*mm,py-ph);py-=ph+2*mm
    maxima=float(d.get('nota_maxima') or 10)
    center=W-m-note_width/2
    c.setFillColor(GRAY3);c.setFont('Raleway-SemiBold',8)
    c.drawCentredString(center,top-6*mm,'NOTA')
    c.setFillColor(HexColor('#168821') if float(nota['nota_final'])>=maxima*.6 else HexColor('#e52207'))
    c.setFont('Raleway-ExtraBold',27)
    c.drawCentredString(center,top-17*mm,fmt(nota['nota_final']))
    c.setFillColor(GRAY3);c.setFont('Raleway-SemiBold',8)
    c.drawCentredString(center,top-23*mm,f'de {fmt(maxima)}')
    y=top-box_height-8*mm

    # Resumo
    cont = {k: 0 for k in CORES}
    for q in d.get('folha_completa', folha):
        cont[q["situacao"]] = cont.get(q["situacao"], 0) + 1
    itens = [("Acertos", cont["correta"], "correta"), ("Erros", cont["incorreta"], "incorreta"),
             ("Em branco", cont["branco"], "branco"), ("Anuladas", cont["anulada"], "anulada")]
    bw = (W - 2 * m - 3 * 4 * mm) / 4
    for i, (rot, val, sit) in enumerate(itens):
        x = m + i * (bw + 4 * mm)
        fundo, borda, texto = CORES[sit]
        c.setFillColor(fundo)
        c.setStrokeColor(borda)
        c.setLineWidth(0.8)
        c.roundRect(x, y - 10 * mm, bw, 12 * mm, 2 * mm, stroke=1, fill=1)
        c.setFillColor(texto)
        c.setFont("Raleway-ExtraBold", 15)
        c.drawString(x + 4 * mm, y - 6.5 * mm, str(val))
        c.setFont("Raleway-SemiBold", 8.5)
        c.drawRightString(x + bw - 4 * mm, y - 5.8 * mm, rot.upper())
    y -= 15 * mm
    if d.get("media_turma") is not None and d.get('modelo') not in ('aluno', 'professor'):
        c.setFont("Raleway-Regular", 9)
        c.setFillColor(GRAY3)
        c.drawString(m, y, f"Média da turma nesta prova: {fmt(d['media_turma'])}")
        y -= 7 * mm

    # Mesmo cabeçalho, nota e resumo; apenas os itens variam entre os modelos.
    professor = d.get('modelo') == 'professor'
    cols = 3 if professor or len(folha) <= 60 else 4
    disponivel = y-4*mm-(56 if d.get('emissao') else 32)*mm
    if professor:
        capacidade = min(24, max(1,int(disponivel/(17*mm)))*cols)
    else:
        capacidade = max(1,int(disponivel/(5.5*mm)))*cols
    folha = folha[:capacidade]
    total = len(d.get('folha_completa', d['folha']))
    inicio = d.get('inicio_questoes', 0)
    intervalo = f"Questões {inicio+1}–{inicio+len(folha)} de {total}" if total > len(folha) else ''
    c.setFillColor(BLUE2); c.setFont('Raleway-Bold', 10)
    c.drawString(m, y, 'QUESTÃO POR QUESTÃO' + (' · '+intervalo if intervalo else ''))
    y -= 4*mm
    rows = math.ceil(len(folha)/cols)
    cw = (W-2*m-(cols-1)*4*mm)/cols
    rh = 17*mm if professor else min(9*mm, (y-(56 if d.get('emissao') else 32)*mm)/max(rows,1))
    for i, q in enumerate(folha):
        col, row = divmod(i, rows)
        x = m+col*(cw+4*mm); yy = y-(row+1)*rh
        fundo, borda, texto = CORES.get(q['situacao'], CORES['anulada'])
        c.setFillColor(fundo); c.setStrokeColor(borda); c.setLineWidth(0.6)
        c.roundRect(x, yy+0.6*mm, cw, rh-1.2*mm, 1.5*mm, fill=1, stroke=1)
        c.setFillColor(texto)
        sit, marc, gab = q['situacao'], q.get('marcada'), q.get('gabarito')
        if sit=='correta': resposta=f'acertou ({marc})'
        elif sit=='incorreta': resposta=f'marcou {marc} · certa {gab}'
        elif sit=='branco': resposta=f'em branco · certa {gab}'
        else: resposta='anulada'
        if professor:
            c.setFont('Raleway-SemiBold', 9)
            c.drawString(x+2.5*mm, yy+11.5*mm, f"{q['numero_questao']:02d} · {resposta}")
            c.setFont('Raleway-Regular', 8.5)
            c.drawString(x+2.5*mm, yy+7.5*mm, 'Turma '+('—' if sit=='anulada' else percentual(q.get('turma'))))
            c.drawString(x+2.5*mm, yy+3.5*mm, 'Geral '+('—' if sit=='anulada' else percentual(q.get('geral'))))
        else:
            base=yy+rh/2-1.2*mm
            c.setFont('Raleway-ExtraBold', 9.5)
            c.drawString(x+3*mm, base, f"{q['numero_questao']:02d}")
            c.setFont('Raleway-SemiBold', 9)
            c.drawString(x+11*mm, base, resposta)
    c._rodape_g360 = {'modelo': d.get('modelo'), 'emissao': d.get('emissao')}
    c.showPage()
    return len(folha)


def percentual(s):
    if not s or s.get('percentual') is None:
        return '—'
    return f"{fmt(s['percentual'])}% ({s['acertos']}/{s['total']})"


def gerar(d, c=None):
    individual = c is None
    if individual:
        c = CanvasNumerado(d['saida'], pagesize=A4)
        c.setTitle(f"Resultado — {d['aluno']['nome']} — {d['disciplina']}")
    inicio = 0
    while inicio < max(len(d['folha']), 1):
        limite = 24 if d.get('modelo') == 'professor' else 90
        pagina = dict(d, folha=d['folha'][inicio:inicio+limite], folha_completa=d['folha'], inicio_questoes=inicio)
        quantidade = _resultado_pagina(pagina, c)
        inicio += max(quantidade, 1)
    if individual:
        c.save()
        return {"ok": True, "arquivo": d["saida"]}


def gerar_lote(d):
    resultados = d["resultados"]
    if not resultados or len(resultados) > 500:
        raise ValueError("O lote deve conter de 1 a 500 resultados.")
    c = CanvasNumerado(d["saida"], pagesize=A4)
    c.setTitle("Resultados dos alunos — Gabarito360")
    for resultado in resultados:
        gerar(resultado, c)
    c.save()
    return {"ok": True, "arquivo": d["saida"], "total": len(resultados)}


if __name__ == "__main__":
    try:
        dados = json.load(sys.stdin)
        r = gerar_lote(dados) if "resultados" in dados else gerar(dados)
    except Exception as e:  # nunca deixa o PHP sem JSON
        r = {"ok": False, "erro": f"{type(e).__name__}: {e}"}
    print(json.dumps(r, ensure_ascii=False))
