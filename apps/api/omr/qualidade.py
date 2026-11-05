"""Barreira conservadora de aquisição. Nunca inventa respostas para geometria inválida."""
import cv2
import numpy as np
import layout as L

VERSAO = "omr-qualidade-1"


def recusar(codigo, mensagem, **metricas):
    return {"aprovada": False, "versao": VERSAO, "codigo": codigo, "mensagem": mensagem, "metricas": metricas}


def analisar(gray):
    h, w = gray.shape
    if min(h, w) < 600 or max(h, w) < 900:
        return recusar("baixa_resolucao", "A foto tem pouca resolução. Aproxime a câmera mantendo a folha inteira e envie a foto original.")
    esc = min(1.0, 1600.0 / max(h, w))
    g = cv2.resize(gray, None, fx=esc, fy=esc) if esc < 1 else gray.copy()
    H, W = g.shape
    k = int(max(g.shape) / 14) | 1
    bg = cv2.morphologyEx(g, cv2.MORPH_CLOSE, cv2.getStructuringElement(cv2.MORPH_RECT, (k, k)))
    n = cv2.divide(g, np.maximum(bg, 1), scale=255)
    _, th = cv2.threshold(cv2.GaussianBlur(n, (5, 5), 0), 150, 255, cv2.THRESH_BINARY_INV)
    cnts, _ = cv2.findContours(th, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    cands = []
    for c in cnts:
        area = cv2.contourArea(c)
        if area < 80:
            continue
        x, y, bw, bh = cv2.boundingRect(c)
        poligono = cv2.approxPolyDP(c, .03 * cv2.arcLength(c, True), True)
        if len(poligono) != 4 or not cv2.isContourConvex(poligono):
            continue
        mascara = np.zeros((bh, bw), np.uint8)
        cv2.drawContours(mascara, [c - np.array([[[x, y]]])], -1, 255, -1)
        if (th[y:y + bh, x:x + bw][mascara > 0] > 0).mean() < .80:
            continue
        hull = cv2.contourArea(cv2.convexHull(c))
        if .6 < bw / float(bh) < 1.6 and hull and area / hull > .9 and area / float(bw * bh) > .7:
            m = cv2.moments(c)
            cands.append({"area": area, "x": m["m10"] / m["m00"], "y": m["m01"] / m["m00"], "bbox": (x, y, bw, bh)})
    escolhidos = []
    for direita, baixo in [(False, False), (True, False), (False, True), (True, True)]:
        zona = [p for p in cands if (p['x'] > .65 * W if direita else p['x'] < .35 * W)
                and (p['y'] > .65 * H if baixo else p['y'] < .35 * H)]
        if not zona:
            escolhidos.append(None)
            continue
        escolhidos.append(min(zona, key=lambda p: (p['x'] - (W if direita else 0)) ** 2 + (p['y'] - (H if baixo else 0)) ** 2))
    if any(p is None for p in escolhidos):
        return recusar("marcadores_incompletos", "Não identificamos os quatro quadrados pretos completos nos cantos. Fotografe novamente incluindo toda a folha, sem cortar as bordas.", marcadores=sum(p is not None for p in escolhidos))
    margem = max(2, round(min(H, W) * .003))
    for p in escolhidos:
        x, y, bw, bh = p['bbox']
        if x <= margem or y <= margem or x + bw >= W - margem or y + bh >= H - margem:
            return recusar("marcador_cortado", "Um dos quatro quadrados está cortado ou muito perto da borda da foto. Afaste um pouco a câmera e inclua a folha inteira.")
    pts = np.float32([[p['x'], p['y']] for p in escolhidos])
    pol = pts[[0, 1, 3, 2]]
    area = cv2.contourArea(pol)
    larguras = [np.linalg.norm(pts[1] - pts[0]), np.linalg.norm(pts[3] - pts[2])]
    alturas = [np.linalg.norm(pts[2] - pts[0]), np.linalg.norm(pts[3] - pts[1])]
    proporcao = sum(larguras) / sum(alturas)
    area_marca = area * L.FID_SIZE ** 2 / (186 * 273)
    if (not cv2.isContourConvex(pol) or area < W * H * .30 or not .40 < proporcao < 1.05
            or min(larguras) / max(larguras) < .45 or min(alturas) / max(alturas) < .45
            or any(not .40 < p['area'] / area_marca < 2.5 for p in escolhidos)):
        return recusar("geometria_insegura", "O alinhamento da folha não é confiável. Coloque o cartão em pé sobre uma superfície plana e fotografe de frente, com os quatro quadrados inteiros.")
    # Nitidez em escala fixa e apenas no campo das respostas; preserva folhas legitimamente em branco.
    dst = np.float32([[x * 4, y * 4] for x, y in L.FIDUCIALS])
    matriz = cv2.getPerspectiveTransform(pts, dst)
    folha = cv2.warpPerspective(g, matriz, (840, 1188), borderValue=255)
    campo = folha[432:1000, 80:760]
    nitidez = float(cv2.Laplacian(campo, cv2.CV_64F).var())
    if nitidez < 35:
        return recusar("sem_nitidez", "As marcações estão sem nitidez ou pouco visíveis. Melhore a luz, limpe a lente e tire outra foto sem movimento.", nitidez=round(nitidez, 2))
    return {"aprovada": True, "versao": VERSAO, "codigo": "adequada", "mensagem": "Foto adequada para tentar a leitura.",
            "metricas": {"nitidez": round(nitidez, 2), "marcadores": 4}, "pontos": pts / esc}


def avaliar(caminho):
    img = cv2.imread(caminho)
    if img is None:
        return recusar("imagem_ilegivel", "Não foi possível abrir a foto. Escolha outra imagem JPG, PNG ou WEBP.")
    return analisar(cv2.cvtColor(img, cv2.COLOR_BGR2GRAY))
