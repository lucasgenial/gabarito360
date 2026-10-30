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


def gerar(d):
    c = canvas.Canvas(d["saida"], pagesize=A4)
    aluno, nota, folha = d["aluno"], d["nota"], d["folha"]
    c.setTitle(f"Resultado — {aluno['nome']} — {d['disciplina']}")
    m = 16 * mm
    y = H - 16 * mm

    # Cabeçalho institucional: logo da escola (se houver) à esquerda, textos centralizados
    topo = y
    if d.get("logo") and os.path.isfile(d["logo"]):
        try:
            c.drawImage(d["logo"], m, topo - 12 * mm, width=18 * mm, height=18 * mm,
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
    c.setFont("Raleway-ExtraBold", 10.5)
    c.drawString(m + 4 * mm, y - 0.3 * mm, f"{d['titulo'].upper()} · RESULTADO")
    c.drawRightString(W - m - 4 * mm, y - 0.3 * mm, d["disciplina"].upper())
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

    # Resumo
    cont = {k: 0 for k in CORES}
    for q in folha:
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
    if d.get("media_turma") is not None:
        c.setFont("Raleway-Regular", 9)
        c.setFillColor(GRAY3)
        c.drawString(m, y, f"Média da turma nesta prova: {fmt(d['media_turma'])}")
        y -= 7 * mm

    # Folha questão por questão (colunas de cima para baixo)
    c.setFillColor(BLUE2)
    c.setFont("Raleway-Bold", 10)
    c.drawString(m, y, "QUESTÃO POR QUESTÃO")
    y -= 4 * mm
    cols = 3 if len(folha) <= 60 else 4
    rows = math.ceil(len(folha) / cols)
    cw = (W - 2 * m - (cols - 1) * 4 * mm) / cols
    rh = min(9 * mm, (y - 32 * mm) / max(rows, 1))
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
    c.setFont("Raleway-Regular", 7.5)
    c.setFillColor(GRAY3)
    c.drawCentredString(W / 2, 12 * mm, "Feito com Gabarito360 · gabarito360.com.br")
    c.showPage()
    c.save()
    return {"ok": True, "arquivo": d["saida"]}


if __name__ == "__main__":
    try:
        r = gerar(json.load(sys.stdin))
    except Exception as e:  # nunca deixa o PHP sem JSON
        r = {"ok": False, "erro": f"{type(e).__name__}: {e}"}
    print(json.dumps(r, ensure_ascii=False))
