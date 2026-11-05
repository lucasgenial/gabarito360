"""Identidade compartilhada dos documentos; não conhece nem altera a grade OMR."""
from pathlib import Path
from xml.sax.saxutils import escape
from reportlab.lib.colors import HexColor
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import Paragraph

NAVY, BLUE, GRAY = map(HexColor, ('#001534', '#0588FC', '#626b77'))
ROOT = Path(__file__).resolve().parent
for family in ('Figtree', 'AlbertSans'):
    pdfmetrics.registerFont(TTFont(family+'-ExtraBold', str(ROOT/'fonts'/(family+'-ExtraBold.ttf'))))
ICON = ROOT.parents[1]/'web/public/brand/gabarito360-icon.png'


def largura_marca(height):
    return height*1.103+2*mm+sum(pdfmetrics.stringWidth(t,f+'-ExtraBold',height*.83)
        for t,f in [('Gabarito3','Figtree'),('6','AlbertSans'),('0','Figtree')])


def marca(c, x, top, height=12*mm):
    """Wordmark em texto com as fontes oficiais, sem rasterizar sua composição."""
    size = height*.83
    if ICON.is_file():
        c.drawImage(str(ICON), x, top-height, width=height*1.103, height=height,
                    preserveAspectRatio=True, mask='auto')
    cursor = x+height*1.103+2*mm
    baseline = top-height*.75
    for text, font, color in [('Gabarito','Figtree-ExtraBold',NAVY), ('3','Figtree-ExtraBold',BLUE),
                              ('6','AlbertSans-ExtraBold',BLUE), ('0','Figtree-ExtraBold',BLUE)]:
        c.setFillColor(color); c.setFont(font,size); c.drawString(cursor,baseline,text)
        cursor += pdfmetrics.stringWidth(text,font,size)
    return cursor-x


def _p(c, text, font, size, width, x, top, color=GRAY):
    p=Paragraph(escape(str(text)),ParagraphStyle('identidade',fontName=font,fontSize=size,
                 leading=size+2,textColor=color,alignment=1))
    _,height=p.wrap(width,1000)
    p.drawOn(c,x,top-height)
    return height


def desenhar(c, d, width, top, margin=16*mm, compact=False):
    """Devolve o limite inferior real; rede é determinada pelo contexto, não pela logo."""
    modo=d.get('identidade') or ('rede' if d.get('rede') else 'escola')
    usable=width-2*margin
    if compact:
        # Área reservada antes da faixa já existente: nenhum marcador/campo muda.
        h=12*mm; side=12*mm
        if modo=='autonomo':
            marca(c,margin,top,8*mm)
            texts=['Uso autônomo', d.get('escola') or 'Espaço pessoal']
            x=margin+65*mm; tw=usable-65*mm
        else:
            left=d.get('logo_ente') if modo=='rede' else d.get('logo')
            right=d.get('logo') if modo=='rede' else None
            _logos(c,left,right,width,top,margin,side)
            x=margin+side+3*mm; tw=usable-2*side-6*mm
            texts=[t for t in [d.get('rede'),d.get('escola')] if t]
        # Nomes extensos cabem na área fixa sem deslocar o formulário óptico.
        for i,t in enumerate(texts[:2]):
            fs=7 if i==0 else 9
            while fs>5 and pdfmetrics.stringWidth(t,'Raleway-Bold',fs)>tw:fs-=.25
            c.setFont('Raleway-Bold',fs);c.setFillColor(NAVY)
            c.drawCentredString(x+tw/2,top-(4+i*5)*mm,t)
        return top-h
    if modo=='autonomo':
        marca(c,margin,top,12*mm)
        c.setFillColor(NAVY);c.setFont('Raleway-SemiBold',9)
        c.drawRightString(width-margin,top-7*mm,'Uso autônomo')
        y=top-18*mm
        name=d.get('instituicao_referencia') or ('Espaço pessoal: '+(d.get('escola') or 'Professor autônomo'))
        y-=_p(c,name,'Raleway-Bold',11,usable,margin,y,NAVY)
        if d.get('detalhe'):y-=_p(c,d['detalhe'],'Raleway-Regular',8,usable,margin,y-2*mm)+2*mm
        return y
    left=d.get('logo_ente') if modo=='rede' else d.get('logo')
    right=d.get('logo') if modo=='rede' else None
    side=18*mm
    _logos(c,left,right,width,top,margin,side)
    x=margin+side+4*mm; tw=usable-2*side-8*mm
    y=top
    if d.get('rede'):
        y-=_p(c,d['rede'].upper(),'Raleway-SemiBold',8,tw,x,y)+2*mm
    y-=_p(c,d.get('escola') or '', 'Raleway-ExtraBold',12,tw,x,y,NAVY)
    if d.get('detalhe'):y-=_p(c,d['detalhe'],'Raleway-Regular',8,tw,x,y-2*mm)+2*mm
    return min(y,top-side)


def _logos(c,left,right,width,top,margin,side):
    for path,x in ((left,margin),(right,width-margin-side)):
        if path and Path(path).is_file():
            try:c.drawImage(path,x,top-side,width=side,height=side,preserveAspectRatio=True,anchor='c',mask='auto')
            except Exception:pass  # Arquivo institucional ilegível não impede o documento.
