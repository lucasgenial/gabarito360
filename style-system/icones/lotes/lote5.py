"""
/**
 * Arquivo: icones/familia/lotes/lote5.py
 * Função: lote 5 da G360 Icones — 19 ícones de Estados e avisos.
 */
"""
import math

from motivos import (CHECK, CHECK_INTERNO, CIRCULO, FOLHA, PONTO, XIS, XIS_INTERNO, SELO_CX, SELO_CY,
                     seta, selo_folga)


def _n(v):
    return f"{v:.2f}".rstrip("0").rstrip(".")


def m_poligono_arredondado(vertices, r=2.0):
    """Polígono fechado com cantos em arco de raio r (vértices em sentido horário de tela).
    Os vértices são virtuais: o traço passa pelos arcos, dentro deles."""
    n = len(vertices)
    trechos = []
    for i in range(n):
        p0, p1, p2 = vertices[i - 1], vertices[i], vertices[(i + 1) % n]
        a = (p0[0] - p1[0], p0[1] - p1[1])
        b = (p2[0] - p1[0], p2[1] - p1[1])
        la, lb = math.hypot(*a), math.hypot(*b)
        ang = math.acos((a[0] * b[0] + a[1] * b[1]) / (la * lb))
        d = r / math.tan(ang / 2)
        t1 = (p1[0] + a[0] / la * d, p1[1] + a[1] / la * d)
        t2 = (p1[0] + b[0] / lb * d, p1[1] + b[1] / lb * d)
        trechos.append((t1, t2))
    d = f"M{_n(trechos[0][1][0])} {_n(trechos[0][1][1])}"
    for i in range(1, n + 1):
        t1, t2 = trechos[i % n]
        d += f" L{_n(t1[0])} {_n(t1[1])} A{_n(r)} {_n(r)} 0 0 1 {_n(t2[0])} {_n(t2[1])}"
    return d + " Z"


def m_arco(cx, cy, r, a0, a1):
    """Arco aberto no sentido horário de a0 a a1 (graus de tela, 0 = direita, 90 = baixo)."""
    rad = math.radians
    x0, y0 = cx + r * math.cos(rad(a0)), cy + r * math.sin(rad(a0))
    x1, y1 = cx + r * math.cos(rad(a1)), cy + r * math.sin(rad(a1))
    grande = 1 if ((a1 - a0) % 360) > 180 else 0
    return f"M{_n(x0)} {_n(y0)} A{_n(r)} {_n(r)} 0 {grande} 1 {_n(x1)} {_n(y1)}"


def m_nuvem(bolhas, base):
    """Nuvem: bolhas (cx, cy, r) da esquerda para a direita, apoiadas numa base reta em y=base."""
    def inter(c1, c2):
        (x1, y1, r1), (x2, y2, r2) = c1, c2
        dx, dy = x2 - x1, y2 - y1
        d = math.hypot(dx, dy)
        a = (r1 ** 2 - r2 ** 2 + d ** 2) / (2 * d)
        h = math.sqrt(r1 ** 2 - a ** 2)
        mx, my = x1 + a * dx / d, y1 + a * dy / d
        pts = [(mx + h * dy / d, my - h * dx / d), (mx - h * dy / d, my + h * dx / d)]
        return min(pts, key=lambda p: p[1])  # o ponto de cima
    primeiro, ultimo = bolhas[0], bolhas[-1]
    pts = [(primeiro[0], base)]
    for c1, c2 in zip(bolhas, bolhas[1:]):
        pts.append(inter(c1, c2))
    pts.append((ultimo[0], base))
    d = f"M{_n(pts[0][0])} {_n(pts[0][1])}"
    for (cx, cy, r), p0, p1 in zip(bolhas, pts, pts[1:]):
        a0 = math.atan2(p0[1] - cy, p0[0] - cx)
        a1 = math.atan2(p1[1] - cy, p1[0] - cx)
        varre = (a1 - a0) % (2 * math.pi)
        grande = 1 if varre > math.pi else 0
        d += f" A{_n(r)} {_n(r)} 0 {grande} 1 {_n(p1[0])} {_n(p1[1])}"
    return d + " Z"


def m_faixa(x0, y0, x1, y1, meia):
    """Faixa retangular (polígono) em volta do segmento, para usar como folga de um risco."""
    dx, dy = x1 - x0, y1 - y0
    L = math.hypot(dx, dy)
    nx, ny = -dy / L * meia, dx / L * meia
    p = [(x0 + nx, y0 + ny), (x1 + nx, y1 + ny), (x1 - nx, y1 - ny), (x0 - nx, y0 - ny)]
    return "M" + " L".join(f"{_n(x)} {_n(y)}" for x, y in p) + " Z"


# ---------- Geometria ----------
# Triângulo de alerta: vértices virtuais fora da área útil; com raio 2 o traço fica em 2,06..21,94 x 3,4..21,4.
_TRI = m_poligono_arredondado([(12, 2.31), (22.5, 20.5), (1.5, 20.5)], 2)

# Cadeado: corpo 5..19 x 10.5..21 (largura da folha), alça r 4.
_CORPO_CADEADO = '<rect class="corpo" x="5" y="10.5" width="14" height="10.5" rx="2"/>'
_FECHADURA = PONTO(12, 14.9, 1.4) + '<path d="M12 15.2 V17.6"/>'

# Nuvem: bolhas esquerda, central e direita sobre a base y 18.
_NUVEM = m_nuvem([(7.25, 14.25, 3.75), (12, 10.75, 4.75), (17, 14, 4)], 18)
_RISCO = (3.5, 3.5, 20.5, 20.5)

ICONES = {
    "confirmar": f"""
<path class="fixo" d="{CHECK}"/>""",

    "fechar": f"""
<path class="fixo" d="{XIS}"/>""",

    "sucesso": f"""
{CIRCULO}
<path d="{CHECK_INTERNO}"/>""",

    "erro": f"""
{CIRCULO}
<path d="{XIS_INTERNO}"/>""",

    "alerta": f"""
<path class="corpo" d="{_TRI}"/>
<path d="M12 9 V13.75"/>
{PONTO(12, 17.1)}""",

    "informacao": f"""
{CIRCULO}
{PONTO(12, 7.6)}
<path d="M12 11 V16.6"/>""",

    "ajuda": f"""
{CIRCULO}
<path d="M9.4 9.25 A2.6 2.6 0 1 1 13.45 11.4 C12.5 12 12 12.6 12 13.6"/>
{PONTO(12, 16.95)}""",

    "carregando": f"""
<path class="fixo" d="{m_arco(12, 12, 8.75, 0, 270)}"/>""",

    "aguardando": f"""
<rect class="corpo" x="3" y="6" width="18" height="12" rx="2"/>
<path d="M3 10 H21"/>
<path d="M6.5 14.5 H9"/>
{selo_folga()}
<circle class="selo" cx="{SELO_CX}" cy="{SELO_CY}" r="3.6"/>
<path class="selo" d="M17.5 16.1 V17.5 H18.7"/>""",

    "periodo-teste": """
<circle class="corpo" cx="12" cy="13.25" r="7.75"/>
<path class="fixo" d="M9.5 3 H14.5 M12 3 V5.5"/>
<path class="fixo" d="M17.48 7.77 L18.9 6.35"/>
<path d="M12 13.25 V9.25"/>""",

    "tendencia-alta": """
<path class="fixo" d="M3 17 L9 11 L13 15 L21 7 M15.5 7 H21 V12.5"/>""",

    "tendencia-baixa": """
<path class="fixo" d="M3 7 L9 13 L13 9 L21 17 M15.5 17 H21 V11.5"/>""",

    "tendencia-estavel": """
<path class="fixo" d="M3 14 L8 10 L12 14 H21 M17.5 10.5 L21 14 L17.5 17.5"/>""",

    "cadeado": f"""
{_CORPO_CADEADO}
<path class="fixo" d="M8 10.5 V7.5 A4 4 0 0 1 16 7.5 V10.5"/>
{_FECHADURA}""",

    "desbloqueado": f"""
{_CORPO_CADEADO}
<path class="fixo" d="M8 10.5 V7.5 A4 4 0 0 1 15.63 5.81"/>
{_FECHADURA}""",

    "leitura-conferida": f"""
<path class="corpo" d="{FOLHA}"/>
<path d="{CHECK_INTERNO}"/>""",

    "notificacao": """
<path class="corpo" d="M4.75 17 L5.75 15.75 V10.5 A6.25 6.25 0 0 1 18.25 10.5 V15.75 L19.25 17 Z"/>
<path class="fixo" d="M12 3 V4.25"/>
<path d="M10 20 A2.5 2.5 0 0 0 14 20"/>""",

    "sem-conexao": f"""
<path class="corpo" d="{_NUVEM}"/>
<path class="folga" d="{m_faixa(2, 2, 22, 22, 2.1)}" fill="#000" stroke="none"/>
<path class="selo" d="M{_RISCO[0]} {_RISCO[1]} L{_RISCO[2]} {_RISCO[3]}"/>""",

    "situacao": f"""
<path class="fixo" d="{m_arco(12, 12.75, 8.25, -50, 230)}"/>
<path class="fixo" d="M12 3 V11"/>""",
}
