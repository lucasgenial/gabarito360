"""
/**
 * Arquivo: apps/api/omr/ler_cartao.py
 * Função: Motor OMR nativo. Lê a foto de um cartão-resposta gerado pelo
 *         Gabarito360: acha os 4 marcadores de canto, corrige a perspectiva,
 *         decodifica o QR (prova, aluno, nº de questões/alternativas, língua)
 *         e mede o preenchimento de cada bolinha.
 *  Uso:  python3 ler_cartao.py <imagem> <num_questoes> <num_alternativas>
 *  Saída (stdout, JSON): {ok, qr, prova_id, aluno_id, lingua, leituras:[...]}
 *        Cada leitura segue o contrato de OmrDriverInterface (PHP).
 */
"""
import json
import sys

import cv2
import numpy as np

import layout as L

PX = 8.0            # px por mm na imagem retificada
MARCA = 0.55        # fração preenchida a partir da qual a bolinha conta como marcada
DUVIDA = 0.30       # entre DUVIDA e MARCA: marcação fraca/rasura -> ambígua


def achar_marcadores(gray):
    h, w = gray.shape
    esc = min(1.0, 1600.0 / max(h, w))
    g = cv2.resize(gray, None, fx=esc, fy=esc) if esc < 1 else gray.copy()
    k = int(max(g.shape) / 14) | 1
    bg = cv2.morphologyEx(g, cv2.MORPH_CLOSE, cv2.getStructuringElement(cv2.MORPH_RECT, (k, k)))
    n = cv2.divide(g, bg, scale=255)
    _, th = cv2.threshold(cv2.GaussianBlur(n, (5, 5), 0), 150, 255, cv2.THRESH_BINARY_INV)
    cnts, _ = cv2.findContours(th, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    cands = []
    for c in cnts:
        a = cv2.contourArea(c)
        if a < 80:
            continue
        x, y, bw, bh = cv2.boundingRect(c)
        hull = cv2.contourArea(cv2.convexHull(c))
        if 0.6 < bw / float(bh) < 1.6 and hull and a / hull > 0.9 and a / float(bw * bh) > 0.7:
            m = cv2.moments(c)
            cands.append((a, m["m10"] / m["m00"], m["m01"] / m["m00"]))
    if len(cands) < 4:
        return None
    cands.sort(reverse=True)
    top = cands[:12]
    ref = top[min(3, len(top) - 1)][0] * 0.35
    big = [p for p in top if p[0] >= ref]
    H, W = g.shape
    pts = []
    for cx, cy in [(0, 0), (W, 0), (0, H), (W, H)]:
        b = min(big, key=lambda p: (p[1] - cx) ** 2 + (p[2] - cy) ** 2)
        pts.append((b[1] / esc, b[2] / esc))
    if len(set(pts)) < 4:
        return None
    return np.float32(pts)


def retificar(gray, pts):
    dst = np.float32([[x * PX, y * PX] for x, y in L.FIDUCIALS])
    m = cv2.getPerspectiveTransform(pts, dst)
    return cv2.warpPerspective(gray, m, (int(L.PAGE_W * PX), int(L.PAGE_H * PX)), borderValue=255)


def normalizar(w):
    bg = cv2.medianBlur(cv2.dilate(w, np.ones((9, 9), np.uint8)), 41)
    n = cv2.divide(w, bg, scale=255)
    _, b = cv2.threshold(n, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)
    return b.astype(np.float32) / 255.0


def preench(norm, x, y, r_mm):
    r = max(2, int(r_mm * PX * 0.62))
    cx, cy = int(round(x * PX)), int(round(y * PX))
    roi = norm[cy - r:cy + r + 1, cx - r:cx + r + 1]
    yy, xx = np.ogrid[-r:r + 1, -r:r + 1]
    m = xx * xx + yy * yy <= r * r
    return float((roi[m] < 0.5).mean())


def decidir(vals):
    """vals: {letra: fração}. Retorna leitura no contrato do OmrDriverInterface (sem número)."""
    ordem = sorted(vals.items(), key=lambda kv: -kv[1])
    marcadas = [k for k, v in ordem if v >= MARCA]
    detect = [{"alternativa": k, "confianca": round(v, 4)} for k, v in ordem if v >= DUVIDA]
    f1 = ordem[0][1]
    f2 = ordem[1][1] if len(ordem) > 1 else 0.0
    if len(marcadas) == 1:
        conf = max(0.0, min(1.0, 0.5 + (f1 - f2)))
        return {"alternativa": marcadas[0], "confianca": round(conf, 4), "ambigua": conf < 0.9,
                "multiplas_marcacoes": False, "alternativas_detectadas": detect}
    if len(marcadas) > 1:
        return {"alternativa": None, "confianca": 0.0, "ambigua": True,
                "multiplas_marcacoes": True, "alternativas_detectadas": detect}
    if f1 >= DUVIDA:  # marcação fraca: pede revisão humana
        return {"alternativa": None, "confianca": round(1.0 - f1, 4), "ambigua": True,
                "multiplas_marcacoes": False, "alternativas_detectadas": detect}
    return {"alternativa": None, "confianca": round(1.0 - f1, 4), "ambigua": False,
            "multiplas_marcacoes": False, "alternativas_detectadas": []}


def ler_qr(w):
    x, y, s = L.QR_BOX
    crop = w[int((y - 4) * PX):int((y + s + 4) * PX), int((x - 4) * PX):int((x + s + 4) * PX)]
    txt, _, _ = cv2.QRCodeDetector().detectAndDecode(crop)
    if not txt:
        txt, _, _ = cv2.QRCodeDetector().detectAndDecode(w)
    dados = {}
    if txt.startswith("G360|"):
        for parte in txt.split("|")[1:]:
            if parte[:1] and parte[1:].isdigit():
                dados[parte[0]] = int(parte[1:])
    return txt, dados


def ler(caminho, n_questoes, n_alt):
    img = cv2.imread(caminho)
    if img is None:
        return {"ok": False, "erro": "imagem ilegível"}
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    pts = achar_marcadores(gray)
    if pts is None:
        return {"ok": False, "erro": "marcadores de canto não encontrados"}
    w = retificar(gray, pts)
    norm = normalizar(w)
    txt, qr = ler_qr(w)
    # o QR descreve o layout impresso; prevalece sobre os parâmetros recebidos
    n_questoes = qr.get("N", n_questoes)
    n_alt = qr.get("K", n_alt)
    g = L.grade(n_questoes, n_alt)
    lingua = None
    if qr.get("L"):
        v = {k: preench(norm, L.LANG_X, y, L.LANG_R) for k, y in L.LANG_Y.items()}
        d = decidir(v)
        lingua = d["alternativa"]
    por_q = {}
    for q, letra, x, y in L.bolinhas(n_questoes, n_alt):
        por_q.setdefault(q, {})[letra] = preench(norm, x, y, g["r"])
    leituras = [{"numero_questao": q, **decidir(v)} for q, v in sorted(por_q.items())]
    return {"ok": True, "qr": txt, "prova_id": qr.get("P"), "aluno_id": qr.get("A") or None,
            "lingua": lingua, "leituras": leituras}


if __name__ == "__main__":
    try:
        r = ler(sys.argv[1], int(sys.argv[2]), int(sys.argv[3]))
    except Exception as e:  # nunca deixa o PHP sem JSON
        r = {"ok": False, "erro": f"{type(e).__name__}: {e}"}
    print(json.dumps(r, ensure_ascii=False))
