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
from reportlab.platypus import SimpleDocTemplate, Paragraph, Table, TableStyle, KeepTogether, Spacer as BaseSpacer
from reportlab.graphics.shapes import Drawing, Rect, String
from reportlab.graphics.barcode.qr import QrCodeWidget
from reportlab.graphics import renderPDF
import cabecalho_documento as identidade

ROOT = Path(__file__).resolve().parent
for suffix in ('Regular', 'SemiBold', 'Bold', 'ExtraBold'):
    pdfmetrics.registerFont(TTFont('Raleway-'+suffix, str(ROOT/'fonts'/('Raleway-'+suffix+'.ttf'))))
NAVY, BLUE, GRAY = map(HexColor, ('#001534','#0588FC','#59677b'))
GREEN, AMBER, TEAL = map(HexColor, ('#168821','#6b5400','#0b7f75'))


class Spacer(BaseSpacer):
    # O título da análise não fica sozinho no fim de uma página.
    keepWithNext = True


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
    lead=ParagraphStyle('introducao',parent=normal,keepWithNext=True)
    leadsmall=ParagraphStyle('introducao-pequena',parent=small,keepWithNext=True)
    title=ParagraphStyle('titulo',parent=normal,fontName='Raleway-Bold',fontSize=16,leading=20,alignment=1)
    p=lambda text,style=normal: Paragraph(escape(str(text)),style)
    turma=d['turma']; k=d['kpis']; f=d.get('filtros') or {}; rec=d['recorte']
    # Mede o cabeçalho real antes de definir a área de conteúdo.
    import io
    measure=Canvas(io.BytesIO(),pagesize=size)
    bottom=identidade.desenhar(measure,d['identidade'],w,h-m)
    top_margin=h-bottom+9*mm
    doc=SimpleDocTemplate(d['saida'],pagesize=size,rightMargin=m,leftMargin=m,
                         topMargin=top_margin,bottomMargin=57*mm,
                         title='Painel da turma · '+turma['nome'],author=d['emissao']['emissor'])

    def page(c,doc):
        c.saveState()
        identidade.desenhar(c,d['identidade'],w,h-m)
        e=d['emissao']; c.setStrokeColor(HexColor('#d5dfeb')); c.line(m,50*mm,w-m,50*mm)
        qr=QrCodeWidget(e['url']);x1,y1,x2,y2=qr.getBounds(); side=24*mm
        drawing=Drawing(side,side,transform=[side/(x2-x1),0,0,side/(y2-y1),0,0]);drawing.add(qr)
        renderPDF.draw(drawing,c,m,20*mm)
        text=('Emitido pelo Gabarito360\nGerado por '+e['emissor']+' · '+e['perfil']+'\n'+e['contexto']+'\n'+e['emitido_em']+'\nCódigo: '+e['codigo']+'\nValidação: '+e['url'].split('?')[0])
        block=Paragraph('<br/>'.join(escape(t) for t in text.splitlines()),small)
        _,bh=block.wrap(usable-side-6*mm,40*mm);block.drawOn(c,m+side+6*mm,46*mm-bh)
        identidade.marca(c,(w-identidade.largura_marca(3*mm))/2,13*mm,3*mm)
        c.restoreState()

    date=lambda value: '/'.join(str(value)[:10].split('-')[::-1]) if value else ''
    pct=lambda n: '—' if n is None else f'{float(n):.1f}%'.replace('.',',')
    analise=d.get('analise') or {}; resumo=analise.get('resumo') or {}
    story=[p('Painel da turma',title),Spacer(1,5*mm),p(f"{turma['nome']} · {turma['serie']} · {turma['ano_letivo']}"),
           p(rec['disciplina']+' · Professor autor: '+rec['professor'],small),
           p('Aplicação: '+(date(f.get('inicio')) or 'Início do histórico')+' a '+(date(f.get('fim')) or 'Hoje'),small),Spacer(1,5*mm)]
    if analise:
        faixa='—' if resumo.get('min_pct') is None else pct(resumo['min_pct'])+' a '+pct(resumo['max_pct'])
        cards=Table([[p(t,small) for t in ('Avaliações aplicadas','Resultados concluídos','Alunos com resultado')],
                     [p(k['avaliacoes']),p(k['resultados']),p(k.get('alunos_com_resultado','—'))],
                     [p(t,small) for t in ('Acerto médio','Mediana do acerto','Faixa observada')],
                     [p(pct(k['acerto_pct'])),p(pct(resumo.get('mediana_pct'))),p(faixa)]],colWidths=[usable/3]*3)
    else:
        cards=Table([[p('Avaliações aplicadas',small),p('Resultados disponíveis',small),p('Acerto médio',small),p('Leituras para revisão',small)],
                     [p(k['avaliacoes']),p(k['resultados']),p(pct(k['acerto_pct'])),p(k.get('leituras_revisao') if k.get('leituras_revisao') is not None else '—')]],colWidths=[usable/4]*4)
    cards.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,-1),HexColor('#eef3fa')),('BOX',(0,0),(-1,-1),.5,HexColor('#d5dfeb')),('VALIGN',(0,0),(-1,-1),'TOP'),('PADDING',(0,0),(-1,-1),8)]))
    sub=ParagraphStyle('sub',parent=normal,fontName='Raleway-Bold',fontSize=11,keepWithNext=True)
    story += [cards,Spacer(1,2*mm),p(f"Matrícula atual: {k['alunos']} alunos ativos. Resultados com percentual válido: {k.get('resultados_com_percentual', k['resultados'])}.",small)]
    if 'alunos_com_resultado' in k:
        story += [p(f"{k['alunos_com_resultado']} alunos distintos com resultado no histórico selecionado; {k['avaliacoes_sem_resultado']} avaliações sem resultado. A matrícula atual não é o total esperado de aplicações passadas.",small)]
    distribuicao=analise.get('distribuicao_acertos')
    if distribuicao is not None:
        story += [Spacer(1,4*mm),p('Distribuição dos percentuais de acerto',sub),Spacer(1,3*mm)]
        faixas=distribuicao['faixas']
        # Mesma base do DTO: apresentação em cinco intervalos de20pp, somando pares.
        if len(faixas)==10:
            faixas=[{'label':f"{faixas[i]['de']}–"+('100%' if i==8 else f"<{faixas[i+1]['ate']}%"),
                     'total':faixas[i]['total']+faixas[i+1]['total']} for i in range(0,10,2)]
        chart=Drawing(usable,108)
        bw=usable/max(1,len(faixas)); maior=max([x['total'] for x in faixas]+[1])
        for i,faixa in enumerate(faixas):
            altura=68*faixa['total']/maior;x=i*bw+3
            chart.add(Rect(x,22,bw-6,max(.5,altura),fillColor=BLUE if faixa['total'] else HexColor('#eef2f8'),strokeColor=None))
            chart.add(String(x+(bw-6)/2,27+altura,str(faixa['total']),fontName='Raleway-Bold',fontSize=8,fillColor=NAVY,textAnchor='middle'))
            chart.add(String(x+(bw-6)/2,8,faixa['label'],fontName='Raleway-Regular',fontSize=6,fillColor=GRAY,textAnchor='middle'))
        story += [chart,p(f"Base: {distribuicao['total']} resultados com percentual válido. Excluídos da distribuição: {distribuicao['percentuais_excluidos']}. Faixas descritivas, sem classificação de aprendizagem.",small)]
        desvio=resumo.get('desvio_pct')
        story += [p('Desvio padrão populacional: '+('—' if desvio is None else str(desvio).replace('.',',')+' pontos percentuais')+'. Cada resultado tem o mesmo peso.',small)]
    situacoes=d.get('situacoes_resultados')
    if situacoes is not None:
        story += [Spacer(1,5*mm),p('Situação dos resultados',sub),Spacer(1,2*mm)]
        total=situacoes['total']; chart=Drawing(usable,12); offset=0
        chart.add(Rect(0,0,usable,9,fillColor=HexColor('#eef2f8'),strokeColor=None))
        for key,color in [('dentro_criterio',GREEN),('indicacao_recuperacao',HexColor('#d4a900')),('outros',GRAY)]:
            length=usable*situacoes[key]/total if total else 0
            if length: chart.add(Rect(offset,0,length,9,fillColor=color,strokeColor=None))
            offset+=length
        story += [chart,p(f"Dentro do critério registrado: {situacoes['dentro_criterio']} · Com indicação de recuperação: {situacoes['indicacao_recuperacao']} · Outros: {situacoes['outros']} · Base: {total} resultados.",small),
                  p('Situação registrada na correção, sem recalcular critérios históricos. Não representa aprovação anual nem diagnóstico de aprendizagem.',small)]
    op=d.get('acompanhamento')
    if op is not None:
        ops=Table([[p('Para revisão',small),p('Em processamento',small),p('Falhas de leitura',small)],
                   [p(op['revisao']),p(op['processamento']),p(op['falhas'])]],colWidths=[usable/3]*3)
        ops.setStyle(TableStyle([('BACKGROUND',(0,0),(0,-1),HexColor('#dcf3f0')),('BACKGROUND',(1,0),(1,-1),HexColor('#eef3fa')),('BACKGROUND',(2,0),(2,-1),HexColor('#fce8e6')),('VALIGN',(0,0),(-1,-1),'TOP'),('PADDING',(0,0),(-1,-1),7)]))
        nota=p(f"Base: {op['total']} cartões vigentes com turma atribuída. Sem aluno identificado: {op['nao_identificados']}; aguardando leitura: {op['aguardando_leitura']}. As contagens de identificação e estado podem se sobrepor; não são faltas de alunos.",small)
        story += [KeepTogether([Spacer(1,4*mm),p('Acompanhamento das leituras',sub),Spacer(1,2*mm),ops,nota])]
    story += [Spacer(1,5*mm),p('Desempenho por avaliação',sub),Spacer(1,3*mm)]
    rows=[[p(t,small) for t in ('Avaliação / disciplina','Aplicação','Resultados','Acerto médio')]]
    estatisticas=analise.get('por_avaliacao') or []
    for indice,a in enumerate(d['avaliacoes']):
        est=estatisticas[indice] if indice<len(estatisticas) else None
        drawing=Drawing(usable*.23-16,64 if est else 38)
        value=a['acerto_pct']; barw=usable*.23-16
        drawing.add(Rect(0,0,barw,5,fillColor=HexColor('#e4eaf3'),strokeColor=None))
        if value is not None: drawing.add(Rect(0,0,barw*max(0,min(100,value))/100,5,fillColor=BLUE,strokeColor=None))
        drawing.add(String(0,12,pct(value),fontName='Raleway-Bold',fontSize=9,fillColor=NAVY))
        drawing.add(String(0,26,f"Base: {a.get('resultados_com_percentual',a['corrigidos'])} resultados",fontName='Raleway-Regular',fontSize=7,fillColor=GRAY))
        if est:
            drawing.add(String(0,40,'Mediana: '+pct(est.get('mediana_pct')),fontName='Raleway-Regular',fontSize=7,fillColor=NAVY))
            intervalo='—' if est.get('min_pct') is None else pct(est['min_pct'])+' a '+pct(est['max_pct'])
            drawing.add(String(0,52,'Faixa: '+intervalo,fontName='Raleway-Regular',fontSize=7,fillColor=GRAY))
        resultado=[p(a['corrigidos'],small)]
        if a.get('situacoes'):
            s=a['situacoes']
            resultado += [p(f"Critério: {s['dentro_criterio']}",small),p(f"Recuperação: {s['indicacao_recuperacao']}",small)]
        rows.append([p(a['titulo']+' · '+a['disciplina'],small),p(date(a['data_aplicacao']) or 'Sem data',small),resultado,drawing])
    if not d['avaliacoes']:rows.append([p('Nenhuma avaliação aplicada neste recorte.',small),'','',''])
    table=Table(rows,colWidths=[usable*.43,usable*.20,usable*.14,usable*.23],repeatRows=1,hAlign='LEFT')
    table.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),HexColor('#e4eaf3')),('VALIGN',(0,0),(-1,-1),'TOP'),('LINEBELOW',(0,0),(-1,-1),.4,HexColor('#d5dfeb')),('PADDING',(0,0),(-1,-1),7)]))
    story += [table,Spacer(1,4*mm),p('Acerto médio: média dos percentuais de acerto dos resultados válidos de cartões lidos. Cada resultado tem o mesmo peso; provas diferentes não são uma medida de evolução. Sem resultado ou sem questões válidas: sem percentual. O filtro de disciplina seleciona avaliações vinculadas à disciplina, sem dividir o resultado da prova em áreas.',small)]
    questoes=analise.get('questoes')
    if questoes is not None:
        story += [Spacer(1,5*mm),p('Acerto por questão',sub),Spacer(1,2*mm)]
        if not questoes.get('disponivel'):
            story += [p('Análise de questões indisponível neste perfil.',small)]
        elif not questoes.get('items'):
            story += [p('Nenhuma questão disponível para a avaliação selecionada.',small)]
        else:
            story += [p(questoes['titulo'],lead),p('Somente resultados finais desta turma; gabarito conforme a opção de língua do cartão. Questões anuladas não recebem percentual.',leadsmall),Spacer(1,2*mm)]
            qr=[[p(t,small) for t in ('Questão','Acertos / base','Percentual de acerto','Registros')]]
            for item in questoes['items']:
                valor=item['pct']; largura=usable*.32-16; grafico=Drawing(largura,26)
                grafico.add(Rect(0,0,largura,5,fillColor=HexColor('#e4eaf3'),strokeColor=None))
                if valor is not None:grafico.add(Rect(0,0,largura*max(0,min(100,valor))/100,5,fillColor=BLUE,strokeColor=None))
                grafico.add(String(0,13,'Anulada' if item['anulada'] else pct(valor),fontName='Raleway-Bold',fontSize=8,fillColor=NAVY))
                detalhes=f"Sem registro: {item['sem_registro']} · Em branco: {item['brancas']}"
                if item.get('ambiguas'):detalhes+=f" · Ambíguas: {item['ambiguas']}"
                if len(item.get('por_lingua',[]))>1:
                    detalhes+=' · '+ '; '.join(f"{l['lingua']}: {l['acertos']}/{l['base']}" for l in item['por_lingua'])
                qr.append([p(item['numero'],small),p(f"{item['acertos']} / {item['base']}",small),grafico,p(detalhes,small)])
            qt=Table(qr,colWidths=[usable*.12,usable*.18,usable*.32,usable*.38],repeatRows=1,hAlign='LEFT')
            qt.setStyle(TableStyle([('BACKGROUND',(0,0),(-1,0),HexColor('#e4eaf3')),('VALIGN',(0,0),(-1,-1),'TOP'),('LINEBELOW',(0,0),(-1,-1),.4,HexColor('#d5dfeb')),('PADDING',(0,0),(-1,-1),7)]))
            story += [qt]
    doc.build(story,onFirstPage=page,onLaterPages=page,canvasmaker=Numerado)


if __name__=='__main__':
    gerar(json.load(sys.stdin))
    print(json.dumps({'ok':True}))
