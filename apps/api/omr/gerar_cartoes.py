"""
/**
 * Arquivo: apps/api/omr/gerar_cartoes.py
 * Função: Gera o PDF (A4) dos cartões-resposta nativos de uma prova: um cartão
 *         por aluno, com nome, turma, matrícula e QR de identificação
 *         (prova, aluno, layout), mais cartões em branco opcionais.
 *  Uso:  python3 gerar_cartoes.py < dados.json
 *        dados: {saida, escola, titulo, disciplina, data, prova_id, num_questoes,
 *                num_alternativas, opcao_lingua, brancos, alunos:[{id,nome,matricula,turma}]}
 *  Saída (stdout, JSON): {ok, arquivo, paginas}
 */
"""
import json
import os
import sys

from reportlab.graphics import renderPDF
from reportlab.graphics.barcode.qr import QrCodeWidget
from reportlab.graphics.shapes import Drawing
from reportlab.lib.colors import HexColor, black, white
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.pdfgen import canvas

import layout as L

BLUE, BLUE2, GRAY3, GRAY1 = HexColor("#1351b4"), HexColor("#0c326f"), HexColor("#8e8e8e"), HexColor("#f8f8f8")
STRIPE = HexColor("#eef3fb")
SITE = "gabarito360.com.br"
FD = os.path.join(os.path.dirname(os.path.abspath(__file__)), "fonts")
for _nm in ("Regular", "SemiBold", "Bold", "ExtraBold", "Black"):
    pdfmetrics.registerFont(TTFont(f"Raleway-{_nm}", os.path.join(FD, f"Raleway-{_nm}.ttf")))


def X(x):
    return x * mm


def Y(y):
    return (L.PAGE_H - y) * mm


def caber(c, txt, fonte, tam, larg_mm):
    """Reduz a fonte até o texto caber na largura."""
    while tam > 5 and pdfmetrics.stringWidth(txt, fonte, tam) / mm > larg_mm:
        tam -= 0.25
    c.setFont(fonte, tam)


def bolinha(c, x, y, r, letra=None, cheia=False):
    c.setLineWidth(0.8)
    c.setStrokeColor(black)
    c.setFillColor(black)
    c.circle(X(x), Y(y), r * mm, stroke=1, fill=1 if cheia else 0)
    if letra and not cheia:
        c.setFillGray(0.6)
        c.setFont("Helvetica", max(5.0, r * 2.9))
        c.drawCentredString(X(x), Y(y) - r * 1.05, letra)
    c.setFillColor(black)


def campo(c, x0, y0, x1, y1, rotulo, valor):
    c.setStrokeColor(GRAY3)
    c.setLineWidth(0.5)
    c.roundRect(X(x0), Y(y1), (x1 - x0) * mm, (y1 - y0) * mm, 1.2 * mm)
    c.setFillColor(BLUE)
    c.setFont("Raleway-Bold", 6.5)
    c.drawString(X(x0 + 1.5), Y(y0 + 3), rotulo)
    c.setFillColor(black)
    caber(c, valor, "Helvetica", 10, x1 - x0 - 3)
    c.drawString(X(x0 + 1.5), Y(y1 - 2), valor)


def selo(c, x, y, h=4.2, fs=7.0):
    w = pdfmetrics.stringWidth("G360", "Raleway-ExtraBold", fs) / mm + 2.6
    c.setFillColor(BLUE)
    c.roundRect(X(x), Y(y + h), w * mm, h * mm, 0.9 * mm, stroke=0, fill=1)
    c.setFillColor(white)
    c.setFont("Raleway-ExtraBold", fs)
    c.drawString(X(x + 1.3), Y(y + h) + (h * mm - fs * 0.7) / 2, "G360")
    c.setFillColor(black)
    return w


def cartao(c, d, aluno):
    nq, na = int(d["num_questoes"]), int(d["num_alternativas"])
    g = L.grade(nq, na)
    lingua = bool(d.get("opcao_lingua"))
    aid = int(aluno["id"]) if aluno else 0

    s = L.FID_SIZE
    for fx, fy in L.FIDUCIALS:
        c.rect(X(fx - s / 2), Y(fy + s / 2), s * mm, s * mm, stroke=0, fill=1)

    # cabeçalho
    c.setFillColor(GRAY3)
    caber(c, (d.get("rede") or "").upper(), "Raleway-SemiBold", 7.5, 165)
    c.drawCentredString(X(105), Y(20), (d.get("rede") or "").upper())
    c.setFillColor(BLUE2)
    caber(c, (d.get("escola") or "").upper(), "Raleway-ExtraBold", 12, 165)
    c.drawCentredString(X(105), Y(26.5), (d.get("escola") or "").upper())
    c.setFillColor(BLUE)
    c.roundRect(X(20), Y(39.5), 170 * mm, 8.5 * mm, 1.5 * mm, stroke=0, fill=1)
    c.setFillColor(white)
    titulo = f"{d.get('titulo', '')} · CARTÃO-RESPOSTA".upper()
    caber(c, titulo, "Raleway-Black", 10.5, 120)
    c.drawString(X(23.5), Y(36.6), titulo)
    caber(c, (d.get("disciplina") or "").upper(), "Raleway-ExtraBold", 9.5, 44)
    c.drawRightString(X(186.5), Y(36.6), (d.get("disciplina") or "").upper())
    c.setFillColor(black)

    campo(c, 20, 43, 155, 53, "ESTUDANTE", aluno["nome"] if aluno else "")
    campo(c, 20, 55, 60, 65, "TURMA", (aluno or {}).get("turma") or "")
    campo(c, 62, 55, 105, 65, "MATRÍCULA", (aluno or {}).get("matricula") or "")
    campo(c, 107, 55, 155, 65, "DATA", d.get("data") or "____/____/______")

    qr_txt = f"G360|P{int(d['prova_id'])}|A{aid}|N{nq}|K{na}|L{1 if lingua else 0}"
    qx, qy, qs = L.QR_BOX
    w = QrCodeWidget(qr_txt, barLevel="M")
    b = w.getBounds()
    dr = Drawing(qs * mm, qs * mm, transform=[qs * mm / (b[2] - b[0]), 0, 0, qs * mm / (b[3] - b[1]), 0, 0])
    dr.add(w)
    renderPDF.draw(dr, c, X(qx), Y(qy + qs))

    # instruções
    c.setFillColor(GRAY1)
    c.roundRect(X(20), Y(95), 100 * mm, 26 * mm, 1.5 * mm, stroke=0, fill=1)
    c.setFillColor(BLUE2)
    c.setFont("Raleway-ExtraBold", 8)
    c.drawString(X(23), Y(74.5), "COMO MARCAR")
    c.setFillColor(black)
    c.setFont("Raleway-SemiBold", 7.6)
    linhas = ["Use caneta preta ou azul e preencha TODA a bolinha:",
              "Rasura ou mais de uma marcação ANULA a questão.",
              "Não dobre, não amasse e não escreva perto do QR",
              "e dos quadrados pretos dos cantos."]
    for i, t in enumerate(linhas):
        c.drawString(X(23), Y(79.5 + i * 4.2), t)
    bolinha(c, 97.5, 78.4, 1.8, cheia=True)
    if not aluno:
        c.setFillColor(HexColor("#e52207"))
        c.setFont("Raleway-Bold", 7)
        c.drawString(X(23), Y(97.5) - 1, "Cartão sem aluno: preencha o nome — o professor vincula na correção.")
        c.setFillColor(black)

    if lingua:
        c.setFillColor(BLUE2)
        c.setFont("Raleway-ExtraBold", 8)
        c.drawString(X(L.LANG_X - 4), Y(74.5), "LÍNGUA ESTRANGEIRA")
        c.setFillColor(GRAY3)
        c.setFont("Raleway-Regular", 6.5)
        c.drawString(X(L.LANG_X - 4), Y(77.3), "marque apenas uma")
        c.setFillColor(black)
        for k, nome in (("ING", "INGLÊS"), ("ESP", "ESPANHOL")):
            bolinha(c, L.LANG_X, L.LANG_Y[k], L.LANG_R)
            c.setFont("Raleway-Bold", 8.5)
            c.drawString(X(L.LANG_X + 4), Y(L.LANG_Y[k]) - 3, nome)

    # bloco de respostas
    c.setFillColor(BLUE2)
    c.roundRect(X(20), Y(L.TITLE_Y + 7), 170 * mm, 7 * mm, 1.2 * mm, stroke=0, fill=1)
    c.setFillColor(white)
    c.setFont("Raleway-ExtraBold", 9.5)
    c.drawString(X(23), Y(L.TITLE_Y + 4.8), f"RESPOSTAS · {nq} QUESTÕES")
    if lingua:
        c.setFont("Raleway-SemiBold", 8)
        c.drawRightString(X(187), Y(L.TITLE_Y + 4.8), "01 a 05: língua estrangeira escolhida")
    pos = L.bolinhas(nq, na)
    # letras do cabeçalho das colunas + faixas alternadas
    c.setFillColor(BLUE)
    c.setFont("Helvetica-Bold", 8)
    for q, letra, x, y in pos:
        if (q - 1) % g["rows"] == 0:
            c.drawCentredString(X(x), Y(L.LETTERS_Y + 1), letra)
    c.setFillColor(STRIPE)
    for col in range(g["cols"]):
        for r in range(0, g["rows"], 2):
            if col * g["rows"] + r + 1 > nq:
                break
            y = L.ROW0_Y + r * g["row_pitch"]
            c.rect(X(L.AREA_X0 + col * g["col_w"] + 1.5), Y(y + g["row_pitch"] * 0.45),
                   (g["col_w"] - 3) * mm, g["row_pitch"] * 0.9 * mm, stroke=0, fill=1)
    c.setFillColor(black)
    for q, letra, x, y in pos:
        if letra == "A":
            c.setFont("Helvetica-Bold", min(9.5, g["row_pitch"] * 1.2))
            c.drawRightString(X(x - g["r"] - 1.6), Y(y) - 3.2, f"{q:02d}")
        bolinha(c, x, y, g["r"], letra)

    # assinatura e rodapé
    c.setLineWidth(0.5)
    c.line(X(55), Y(258), X(155), Y(258))
    c.setFont("Raleway-Regular", 7.5)
    c.drawCentredString(X(105), Y(261.5), "Assinatura do(a) estudante")
    c.setStrokeColor(HexColor("#e0e0e0"))
    c.line(X(22), Y(270), X(188), Y(270))
    t1, brand = "Feito com", "Gabarito360"
    t2 = f"  ·  Corrija provas em minutos, não em dias  ·  {SITE}"
    w1 = pdfmetrics.stringWidth(t1, "Raleway-SemiBold", 7.5) / mm
    wb = pdfmetrics.stringWidth(brand, "Raleway-Black", 9) / mm
    w2 = pdfmetrics.stringWidth(t2, "Raleway-SemiBold", 7.5) / mm
    x = 105 - (w1 + 1.5 + 11 + 1.2 + wb + w2) / 2
    c.setFillColor(GRAY3)
    c.setFont("Raleway-SemiBold", 7.5)
    c.drawString(X(x), Y(277), t1)
    x += w1 + 1.5
    x += selo(c, x, 273.9) + 1.2
    c.setFillColor(BLUE)
    c.setFont("Raleway-Black", 9)
    c.drawString(X(x), Y(277), brand)
    c.linkURL(f"https://{SITE}", (X(x - 12), Y(279), X(x + wb + w2), Y(272)))
    c.setFillColor(GRAY3)
    c.setFont("Raleway-SemiBold", 7.5)
    c.drawString(X(x + wb), Y(277), t2)
    c.setFont("Helvetica", 5.5)
    c.setFillColor(HexColor("#b0b0b0"))
    c.drawCentredString(X(105), Y(283), qr_txt)
    c.setFillColor(black)


def gerar(d):
    c = canvas.Canvas(d["saida"], pagesize=A4)
    c.setTitle(f"Cartões-resposta · {d.get('titulo', '')}")
    c.setAuthor(f"Gabarito360 · {SITE}")
    paginas = 0
    for a in d.get("alunos", []):
        cartao(c, d, a)
        c.showPage()
        paginas += 1
    for _ in range(int(d.get("brancos") or 0)):
        cartao(c, d, None)
        c.showPage()
        paginas += 1
    if paginas == 0:
        cartao(c, d, None)
        c.showPage()
        paginas = 1
    c.save()
    return {"ok": True, "arquivo": d["saida"], "paginas": paginas}


if __name__ == "__main__":
    try:
        r = gerar(json.load(sys.stdin))
    except Exception as e:
        r = {"ok": False, "erro": f"{type(e).__name__}: {e}"}
    print(json.dumps(r, ensure_ascii=False))
