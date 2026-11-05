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
import math
import os
import sys

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

BLUE, BLUE2, GRAY3 = HexColor("#1351b4"), HexColor("#0c326f"), HexColor("#6b6b6b")
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


def rodape(c, d):
    e = d.get('emissao')
    if not e:
        c.setFont('Raleway-Regular', 7.5)
        c.setFillColor(GRAY3)
        c.drawCentredString(W / 2, 12 * mm, 'Feito com Gabarito360 · gabarito360.com.br')
        return
    m = 16 * mm
    c.setStrokeColor(HexColor('#d5dfeb'))
    c.line(m, 50 * mm, W - m, 50 * mm)
    largura = W - 2 * m - 29 * mm
    estilo = ParagraphStyle('selo', fontName='Raleway-Regular', fontSize=7, leading=9, textColor=GRAY3)
    texto = ('Gerado por <b>' + escape(e['emissor']) + '</b> · ' + escape(e['perfil']) + '<br/>'
             + escape(e['contexto']) + ' · ' + escape(e['emitido_em']) + '<br/>'
             + '<b>' + escape(e['codigo']) + '</b><br/>'
             + 'Selo de emissão do sistema. Confira o arquivo pelo QR ou em:<br/>'
             + escape(e['url'].split('?')[0]) + '<br/>'
             + 'Página ' + str(c.getPageNumber()) + ' · Gabarito360')
    p = Paragraph(texto, estilo)
    _, altura = p.wrap(largura, 44 * mm)
    p.drawOn(c, m, 47 * mm - altura)
    qr = QrCodeWidget(e['url'])
    x1, y1, x2, y2 = qr.getBounds()
    tamanho = 24 * mm
    desenho = Drawing(tamanho, tamanho, transform=[tamanho/(x2-x1), 0, 0, tamanho/(y2-y1), 0, 0])
    desenho.add(qr)
    renderPDF.draw(desenho, c, W - m - tamanho, 10 * mm)


def _aluno_pagina(d, c):
    aluno, nota, folha = d["aluno"], d["nota"], d["folha"]
    m = 16 * mm
    y = H - 16 * mm

    # Cabeçalho institucional: logo do ente (rede ou mantenedora) à esquerda,
    # logo da escola à direita (só a escola: à esquerda), textos centralizados
    topo = y
    logos = [p for p in (d.get("logo_ente"), d.get("logo")) if p and os.path.isfile(p)]
    for i, caminho in enumerate(logos):
        x = m if i == 0 else W - m - 18 * mm
        try:
            c.drawImage(caminho, x, topo - 12 * mm, width=18 * mm, height=18 * mm,
                        preserveAspectRatio=True, anchor="c", mask="auto")
        except Exception:
            pass  # logo inválido não impede o PDF
    c.setFillColor(GRAY3)
    c.setFont("Raleway-SemiBold", 8)
    if d.get("rede"):
        c.drawCentredString(W / 2, y, d["rede"].upper())
        y -= 5 * mm
    c.setFillColor(BLUE2)
    caber(c, d.get("escola") or "", "Raleway-ExtraBold", 12, W - 2 * m - 44 * mm)
    c.drawCentredString(W / 2, y, d.get("escola") or "")
    y -= 4.5 * mm
    if d.get("detalhe"):
        c.setFillColor(GRAY3)
        c.setFont("Raleway-Regular", 8)
        c.drawCentredString(W / 2, y, d["detalhe"])
        y -= 4 * mm
    y = min(y, topo - 14 * mm) - 3 * mm

    # Faixa da prova
    c.setFillColor(BLUE)
    c.roundRect(m, y - 4 * mm, W - 2 * m, 10 * mm, 2 * mm, stroke=0, fill=1)
    c.setFillColor(white)
    caber(c, f"{d['titulo'].upper()} · RESULTADO", "Raleway-ExtraBold", 10.5, W - 2*m - 8*mm)
    c.drawString(m + 4 * mm, y - 0.3 * mm, f"{d['titulo'].upper()} · RESULTADO")
    y -= 14 * mm

    # Aluno + nota
    c.setFillColor(HexColor("#222222"))
    caber(c, aluno["nome"], "Raleway-Bold", 14, W - 2 * m - 45 * mm)
    c.drawString(m, y, aluno["nome"])
    c.setFont("Raleway-Regular", 9.5)
    c.setFillColor(GRAY3)
    linha = " · ".join(filter(None, [
        f"Turma {aluno['turma']}" if aluno.get("turma") else None,
        f"Matrícula {aluno['matricula']}" if aluno.get("matricula") else None,
        ("Espanhol" if d.get("lingua") == "ESP" else "Inglês") if d.get("lingua") else None,
        d.get("data"),
    ]))
    c.drawString(m, y - 5.5 * mm, linha)

    maxima = float(d.get("nota_maxima") or 10)
    aprovado = float(nota["nota_final"]) >= maxima * 0.6
    c.setFillColor(HexColor("#168821") if aprovado else HexColor("#e52207"))
    c.setFont("Raleway-ExtraBold", 30)
    c.drawRightString(W - m, y - 4 * mm, fmt(nota["nota_final"]))
    c.setFont("Raleway-SemiBold", 8)
    c.setFillColor(GRAY3)
    c.drawRightString(W - m, y - 9 * mm, f"NOTA (de {fmt(maxima)})")
    y -= 16 * mm
    c.setFont('Raleway-Regular', 9)
    c.setFillColor(GRAY3)
    caber(c, d['disciplina'], 'Raleway-Regular', 9, W-2*m)
    c.drawString(m, y, d['disciplina'])
    y -= 6*mm

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
    if d.get("media_turma") is not None and d.get('modelo') != 'aluno':
        c.setFont("Raleway-Regular", 9)
        c.setFillColor(GRAY3)
        c.drawString(m, y, f"Média da turma nesta prova: {fmt(d['media_turma'])}")
        y -= 7 * mm

    # Folha questão por questão (colunas de cima para baixo)
    c.setFillColor(BLUE2)
    c.setFont("Raleway-Bold", 10)
    intervalo = d.get('intervalo', '')
    c.drawString(m, y, "QUESTÃO POR QUESTÃO" + (' · ' + intervalo if intervalo else ''))
    y -= 4 * mm
    cols = 3 if len(folha) <= 60 else 4
    rows = math.ceil(len(folha) / cols)
    cw = (W - 2 * m - (cols - 1) * 4 * mm) / cols
    rh = min(9 * mm, (y - (56 if d.get('emissao') else 32) * mm) / max(rows, 1))
    for i, q in enumerate(folha):
        col, row = divmod(i, rows)
        x = m + col * (cw + 4 * mm)
        yy = y - (row + 1) * rh
        fundo, borda, texto = CORES.get(q["situacao"], CORES["anulada"])
        c.setFillColor(fundo)
        c.setStrokeColor(borda)
        c.setLineWidth(0.6)
        c.roundRect(x, yy + 0.6 * mm, cw, rh - 1.2 * mm, 1.5 * mm, stroke=1, fill=1)
        c.setFillColor(texto)
        c.setFont("Raleway-ExtraBold", 9.5)
        base = yy + rh / 2 - 1.2 * mm
        c.drawString(x + 3 * mm, base, f"{q['numero_questao']:02d}")
        c.setFont("Raleway-SemiBold", 9)
        sit, marc, gab = q["situacao"], q.get("marcada"), q.get("gabarito")
        if sit == "correta":
            txt = f"acertou ({marc})"
        elif sit == "incorreta":
            txt = f"marcou {marc} · certa {gab}"
        elif sit == "branco":
            txt = f"em branco · certa {gab}"
        else:
            txt = "anulada"
        c.drawString(x + 11 * mm, base, txt)
    y -= rows * rh + 6 * mm

    # Rodapé
    rodape(c, d)
    c.showPage()


def percentual(s):
    if not s or s.get('percentual') is None:
        return '—'
    return f"{fmt(s['percentual'])}% ({s['acertos']}/{s['total']})"


def _professor(d, c):
    m = 16 * mm
    folha = d['folha']
    inicio = 0
    while inicio < max(len(folha), 1):
        y = H - 18 * mm
        c.setFillColor(BLUE2)
        p = Paragraph(escape(d.get('escola') or 'Gabarito360'), ParagraphStyle('escola', fontName='Raleway-Bold', fontSize=11, leading=14, textColor=BLUE2))
        _, h = p.wrap(W-2*m, 25*mm); p.drawOn(c, m, y-h); y -= h+5*mm
        c.setFont('Raleway-Bold', 11)
        c.drawString(m, y, 'RELATÓRIO PEDAGÓGICO · PROFESSOR')
        y -= 7*mm
        for texto in [d['titulo']+' · '+d['disciplina'], d['aluno']['nome'], 'Turma '+(d['aluno'].get('turma') or '—')+' · Nota '+fmt(d['nota']['nota_final'])]:
            p = Paragraph(escape(texto), ParagraphStyle('contexto', fontName='Raleway-Regular', fontSize=9, leading=12, textColor=GRAY3))
            _, h = p.wrap(W-2*m, 20*mm); p.drawOn(c, m, y-h); y -= h+2*mm
        y -= 3*mm
        c.setFont('Raleway-Regular', 8)
        c.drawString(m, y, 'Percentuais: alunos com correção concluída. Em branco conta como erro.')
        y -= 4*mm
        c.drawString(m, y, 'Geral: turmas da mesma prova e espaço. Anuladas e amostra vazia: —.')
        y -= 9*mm
        larguras = [22, 24, 24, 54, 54]
        titulos = ['Questão', 'Marcou', 'Certa', '% acerto turma', '% acerto geral']
        xs = [m]
        for largura in larguras: xs.append(xs[-1]+largura*mm)
        c.setFillColor(BLUE); c.rect(m, y-2*mm, W-2*m, 8*mm, fill=1, stroke=0)
        c.setFillColor(white); c.setFont('Raleway-Bold', 8.5)
        for x, titulo in zip(xs, titulos): c.drawString(x+2*mm, y, titulo)
        y -= 8*mm
        capacidade = max(1, int((y - 58*mm)/(6*mm)) + 1)
        for q in folha[inicio:inicio+capacidade]:
            c.setFillColor(HexColor('#f2f5fa') if q['numero_questao'] % 2 else white)
            c.rect(m, y-2*mm, W-2*m, 6*mm, fill=1, stroke=0)
            c.setFillColor(BLUE2); c.setFont('Raleway-Regular', 9)
            vals = [str(q['numero_questao']), q.get('marcada') or 'Em branco', 'Anulada' if q['situacao']=='anulada' else q.get('gabarito') or '—', percentual(q.get('turma')), percentual(q.get('geral'))]
            for x, txt in zip(xs, vals): c.drawString(x+2*mm, y, txt)
            y -= 6*mm
        rodape(c, d); c.showPage()
        inicio += capacidade


def gerar(d, c=None):
    individual = c is None
    if individual:
        c = canvas.Canvas(d['saida'], pagesize=A4)
        c.setTitle(f"Resultado — {d['aluno']['nome']} — {d['disciplina']}")
    if d.get('modelo') == 'professor':
        _professor(d, c)
    else:
        for inicio in range(0, max(len(d['folha']), 1), 90):
            pagina = dict(d, folha=d['folha'][inicio:inicio+90], folha_completa=d['folha'],
                intervalo=f"{inicio+1}–{min(inicio+90,len(d['folha']))} de {len(d['folha'])}" if len(d['folha'])>90 else '')
            _aluno_pagina(pagina, c)
    if individual:
        c.save()
        return {"ok": True, "arquivo": d["saida"]}


def gerar_lote(d):
    resultados = d["resultados"]
    if not resultados or len(resultados) > 500:
        raise ValueError("O lote deve conter de 1 a 500 resultados.")
    c = canvas.Canvas(d["saida"], pagesize=A4)
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
