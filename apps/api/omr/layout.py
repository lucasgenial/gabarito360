"""
/**
 * Arquivo: apps/api/omr/layout.py
 * Função: Geometria do cartão-resposta nativo do Gabarito360 (mm, origem no canto
 *         superior esquerdo de uma folha A4). Compartilhada pelo gerador de PDF
 *         (gerar_cartoes.py) e pelo leitor OMR (ler_cartao.py): a mesma função
 *         produz as posições nos dois lados, a partir de (questões, alternativas).
 */
"""
import math

PAGE_W, PAGE_H = 210.0, 297.0
FID_SIZE = 8.0
FIDUCIALS = [(12.0, 12.0), (198.0, 12.0), (12.0, 285.0), (198.0, 285.0)]  # TL, TR, BL, BR

QR_BOX = (160.0, 42.0, 30.0)          # x, y (topo) e lado do QR
LANG_X = 128.0
LANG_Y = {"ING": 83.0, "ESP": 90.0}
LANG_R = 2.3

AREA_X0, AREA_W = 20.0, 170.0
TITLE_Y = 100.0
LETTERS_Y = 110.0
ROW0_Y = 116.0
ROWS_MAX_H = 128.0                   # altura útil para as linhas (até ~244 mm)
LETRAS = "ABCDE"


def grade(n_questoes: int, n_alt: int) -> dict:
    """Define colunas/linhas e tamanhos das bolinhas para caber em A4."""
    if n_questoes <= 45:              # até 45: sempre 3 colunas (bolinhas grandes)
        cols = 3
        rows = max(1, math.ceil(n_questoes / cols))
    else:                             # provas longas: 20 linhas, até 6 colunas
        rows = 20
        cols = math.ceil(n_questoes / rows)
        if cols > 6:
            cols = 6
            rows = math.ceil(n_questoes / cols)
    col_w = AREA_W / max(cols, 1)
    pitch = min(8.0, (col_w - 12.0) / n_alt)
    r = min(2.6, pitch * 0.34)
    row_pitch = min(8.0, ROWS_MAX_H / max(rows - 1, 1))
    r = min(r, row_pitch * 0.36)
    return {"rows": rows, "cols": cols, "col_w": col_w, "pitch": pitch, "r": r, "row_pitch": row_pitch}


def bolinhas(n_questoes: int, n_alt: int):
    """[(questao, letra, x, y)] — questões numeradas de cima para baixo, coluna a coluna."""
    g = grade(n_questoes, n_alt)
    out = []
    for q in range(1, n_questoes + 1):
        c, rr = divmod(q - 1, g["rows"])
        y = ROW0_Y + rr * g["row_pitch"]
        x_ult = AREA_X0 + (c + 1) * g["col_w"] - 3.0 - g["pitch"] / 2
        for k in range(n_alt):
            x = x_ult - (n_alt - 1 - k) * g["pitch"]
            out.append((q, LETRAS[k], x, y))
    return out
