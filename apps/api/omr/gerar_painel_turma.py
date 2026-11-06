"""Painel da turma: mesmo DTO autorizado da tela, identidade e emissão verificável."""
import json
import sys
from pathlib import Path
from xml.sax.saxutils import escape
from reportlab.lib.pagesizes import A4, landscape
from reportlab.lib.units import mm
from reportlab.lib.colors import HexColor, white
from reportlab.lib.styles import ParagraphStyle
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen.canvas import Canvas
from reportlab.platypus import SimpleDocTemplate, Paragraph, Table, TableStyle, Spacer
from reportlab.graphics.shapes import Drawing, Rect, String
from reportlab.graphics.barcode.qr import QrCodeWidget
from reportlab.graphics import renderPDF
import cabecalho_documento as identidade

ROOT = Path(__file__).resolve().parent
for suffix in ('Regular', 'SemiBold', 'Bold', 'ExtraBold'):
    pdfmetrics.registerFont(TTFont('Raleway-'+suffix, str(ROOT/'fonts'/('Raleway-'+suffix+'.ttf'))))
NAVY, BLUE, GRAY = map(HexColor, ('#001534','#0588FC','#59677b'))


class Numerado(Canvas):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        self.pages = []

    def showPage(self):
        self.pages.append(dict(self.__dict__))
        self._startPage()

    def save(self):
        total = len(self.pages)
        for state in self.pages:
            self.__dict__.update(state)
            self.setFont('Raleway-Regular', 8)
            self.setFillColor(GRAY)
            self.drawRightString(self._pagesize[0]-16*mm, 10*mm,
                                 f'{self.getPageNumber():02d}/{total:02d}')
            Canvas.showPage(self)
        Canvas.save(self)


def gerar(d):
    size = landscape(A4) if d.get('orientacao') == 'paisagem' else A4
    w,h=size; m=16*mm; usable=w-2*m
    normal=ParagraphStyle('texto',fontName='Raleway-Regular',fontSize=9,leading=13,textColor=NAVY)
    small=ParagraphStyle('pequeno',parent=normal,fontSize=8,leading=11)
    title=ParagraphStyle('titulo',parent=normal,fontName='Raleway-Bold',fontSize=16,leading=20,alignment=1)
    p=lambda text,style=normal: Paragraph(escape(str(text)),style)
    turma=d['turma']; k=d['kpis']; f=d.get('filtros',{}); rec=d['recorte']
    # Mede o cabeçalho real antes de definir a área de conteúdo.
    import io
    measure=Canvas(io.BytesIO(),pagesize=size)
    bottom=identidade.desenhar(measure,d['identidade'],w,h-m)
    top_margin=h-bottom+9*mm
    doc=SimpleDocTemplate(d['saida'],pagesize=size,rightMargin=m,leftMargin=m,
                         topMargin=top_margin,bottomMargin=57*mm,
                         title='Painel da turma · '+turma['nome'],author=d['emissao']['emissor'])

    def page(c,doc):
        identidade.desenhar(c,d['identidade'],w,h-m)
        e=d['emissao']; c.setStrokeColor(HexColor('#d5dfeb')); c.line(m,50*mm,w-m,50*mm)
        qr=QrCodeWidget(e['url']);x1,y1,x2,y2=qr.getBounds(); side=24*mm
        drawing=Drawing(side,side,transform=[side/(x2-x1),0,0,side/(y2-y1),0,0]);drawing.add(qr)
        renderPDF.draw(drawing,c,m,20*mm)
        text=('Emitido pelo Gabarito360\nGerado por '+e['emissor']+' · '+e['perfil']+'\n'+e['contexto']+'\n'+e['emitido_em']+'\nCódigo: '+e['codigo']+'\nValidação: '+e['url'].split('?')[0])
        block=Paragraph('<br/>'.join(escape(t) for t in text.splitlines()),small)
        _,bh=block.wrap(usable-side-6*mm,40*mm);block.drawOn(c,m+side+6*mm,46*mm-bh)
        identidade.marca(c,(w-identidade.largura_marca(3*mm))/2,13*mm,3*mm)

    date=lambda value: '/'.join(str(value)[:10].split('-')[::-1]) if value else ''
    pct=lambda n: '—' if n is None else f'{float(n):.1f}%'.replace('.',',')
    story=[p('Painel da turma',title),Spacer(1,5*mm),p(f"{turma['nome']} · {turma['serie']} · {turma['ano_letivo']}"),
           p(rec['disciplina']+' · Professor autor: '+rec['professor'],small),
           p('Aplicação: '+(date(f.get('inicio')) or 'Início do histórico')+' a '+(date(f.get('fim')) or 'Hoje'),small),Spacer(1,5*mm)]
    cards=Table([[p('Alunos ativos',small),p('Avaliações aplicadas',small),p('Resultados',small),p('Acerto médio',small)],
                 [p(k['alunos']),p(k['avaliacoes']),p(k['resultados']),p(pct(k['acerto_pct']))]],colWidths=[usable/4]*4)
    cards.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,-1),HexColor('#eef3fa')),('BOX',(0,0),(-1,-1),.5,HexColor('#d5dfeb')),('VALIGN',(0,0),(-1,-1),'TOP'),('PADDING',(0,0),(-1,-1),8)]))
    story += [cards,Spacer(1,5*mm),p('Desempenho por avaliação',ParagraphStyle('sub',parent=normal,fontName='Raleway-Bold',fontSize=11)),Spacer(1,3*mm)]
    rows=[[p(t,small) for t in ('Avaliação / disciplina','Aplicação','Resultados','Acerto médio')]]
    for a in d['avaliacoes']:
        drawing=Drawing(usable*.23-16,25)
        value=a['acerto_pct']; barw=usable*.23-16
        drawing.add(Rect(0,0,barw,5,fillColor=HexColor('#e4eaf3'),strokeColor=None))
        if value is not None: drawing.add(Rect(0,0,barw*max(0,min(100,value))/100,5,fillColor=BLUE,strokeColor=None))
        drawing.add(String(0,12,pct(value),fontName='Raleway-Bold',fontSize=9,fillColor=NAVY))
        rows.append([p(a['titulo']+' · '+a['disciplina'],small),p(date(a['data_aplicacao']) or 'Sem data',small),p(a['corrigidos'],small),drawing])
    if not d['avaliacoes']:rows.append([p('Nenhuma avaliação aplicada neste recorte.',small),'','',''])
    table=Table(rows,colWidths=[usable*.43,usable*.20,usable*.14,usable*.23],repeatRows=1,hAlign='LEFT')
    table.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),HexColor('#e4eaf3')),('VALIGN',(0,0),(-1,-1),'TOP'),('LINEBELOW',(0,0),(-1,-1),.4,HexColor('#d5dfeb')),('PADDING',(0,0),(-1,-1),7)]))
    story += [table,Spacer(1,4*mm),p('Acerto médio: média dos percentuais de acerto dos resultados válidos. Avaliações sem resultado ficam sem percentual. O filtro de disciplina seleciona avaliações vinculadas à disciplina, sem dividir o resultado da prova em áreas.',small)]
    doc.build(story,onFirstPage=page,onLaterPages=page,canvasmaker=Numerado)


if __name__=='__main__':
    gerar(json.load(sys.stdin))
    print(json.dumps({'ok':True}))
