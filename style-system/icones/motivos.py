"""
/**
 * Arquivo: icones/familia/motivos.py
 * Função: biblioteca de motivos compartilhados da G360 Icones. Toda forma que aparece em mais de um
 *         ícone sai daqui, com as mesmas coordenadas, para a família ter um só desenho por ideia.
 *         CONGELADO para os agentes de lote: não editar; motivo novo vai no próprio arquivo do lote
 *         e é relatado ao coordenador.
 *
 * Convenções: grade 24, traço 1,8 (definido na raiz do SVG), pontas e junções redondas.
 * Nunca use transform: a escala muda a espessura do traço. Calcule as coordenadas.
 */
"""
import math

# ---------- Contornos-base ----------
CIRCULO = '<circle class="corpo" cx="12" cy="12" r="9"/>'
FOLHA = "M7 3 H17 A2 2 0 0 1 19 5 V19 A2 2 0 0 1 17 21 H7 A2 2 0 0 1 5 19 V5 A2 2 0 0 1 7 3 Z"  # página, retrato
DOCUMENTO = "M14 3 H7 A2 2 0 0 0 5 5 V19 A2 2 0 0 0 7 21 H17 A2 2 0 0 0 19 19 V8 Z"  # arquivo, canto dobrado
DOBRA = "M14 3 V8 H19"
CALENDARIO = ('<rect class="corpo" x="4" y="5" width="16" height="15" rx="2"/>'
              '<path class="fixo" d="M8 3 V6.5 M16 3 V6.5"/><path d="M4 9 H20"/>')  # área interna livre: 6..18 x 11..18
CAMERA = "M3 9 A2 2 0 0 1 5 7 H7.5 L9 5 H15 L16.5 7 H19 A2 2 0 0 1 21 9 V17 A2 2 0 0 1 19 19 H5 A2 2 0 0 1 3 17 Z"
CAMERA_LENTE = '<circle cx="12" cy="13" r="3"/>'
LUPA = '<circle class="corpo" cx="10.5" cy="10.5" r="6.5"/><path class="fixo" d="M15.3 15.3 L20 20"/>'  # interior livre: r 4


def vale(r=1.5):
    """Vale de correção (ticket), paisagem 3..21 x 6..18. Unidade de crédito do produto."""
    return (f"M5 6 H19 A2 2 0 0 1 21 8 V{12-r} A{r} {r} 0 0 0 21 {12+r} V16 A2 2 0 0 1 19 18 "
            f"H5 A2 2 0 0 1 3 16 V{12+r} A{r} {r} 0 0 0 3 {12-r} V8 A2 2 0 0 1 5 6 Z")


PICOTE = "".join(f'<circle cx="8" cy="{y}" r="0.95" fill="currentColor" stroke="none"/>' for y in (9, 12, 15))


# ---------- Sinais ----------
CHECK = "M5 12.5 L9.5 17 L19 7.5"                  # sozinho
CHECK_INTERNO = "M8.5 12.2 L11 14.7 L15.8 9.6"     # dentro de círculo r 9, folha ou escudo
XIS = "M6.5 6.5 L17.5 17.5 M17.5 6.5 L6.5 17.5"
XIS_INTERNO = "M9.2 9.2 L14.8 14.8 M14.8 9.2 L9.2 14.8"
MAIS = "M12 5 V19 M5 12 H19"
MAIS_INTERNO = "M12 8.5 V15.5 M8.5 12 H15.5"
MENOS = "M5 12 H19"
MENOS_INTERNO = "M8.5 12 H15.5"
PONTO = lambda cx, cy, r=1.15: f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="currentColor" stroke="none"/>'


def girar(pontos, graus, cx=12, cy=12):
    a = math.radians(graus)
    return [(cx + (x - cx) * math.cos(a) - (y - cy) * math.sin(a), cy + (x - cx) * math.sin(a) + (y - cy) * math.cos(a))
            for x, y in pontos]


def _caminho(*trechos):
    return " ".join("M" + " L".join(f"{x:.2f} {y:.2f}" for x, y in t) for t in trechos)


def seta(graus=0, comprimento=16, asa=6):
    """Seta reta centrada; 0 = para a direita, 90 = para baixo, 180 = esquerda, 270 = cima."""
    m = comprimento / 2
    haste = [(12 - m, 12), (12 + m, 12)]
    ponta = [(12 + m - asa, 12 - asa), (12 + m, 12), (12 + m - asa, 12 + asa)]
    return _caminho(girar(haste, graus), girar(ponta, graus))


def chevron(graus=0, tamanho=6):
    """Chevron centrado; 0 = aponta para a direita."""
    t = tamanho
    return _caminho(girar([(12 - t / 2, 12 - t), (12 + t / 2, 12), (12 - t / 2, 12 + t)], graus))


def seta_circular(cx, cy, r, a0, a1, asa=1.7):
    """Arco no sentido horário de a0 a a1 (graus de tela) com ponta de seta no fim. Devolve (arco, ponta)."""
    rad = math.radians
    x0, y0 = cx + r * math.cos(rad(a0)), cy + r * math.sin(rad(a0))
    x1, y1 = cx + r * math.cos(rad(a1)), cy + r * math.sin(rad(a1))
    grande = 1 if ((a1 - a0) % 360) > 180 else 0
    arco = f"M{x0:.2f} {y0:.2f} A{r} {r} 0 {grande} 1 {x1:.2f} {y1:.2f}"
    tx, ty = -math.sin(rad(a1)), math.cos(rad(a1))
    nx, ny = math.cos(rad(a1)), math.sin(rad(a1))
    p1 = (x1 - asa * tx + asa * 0.75 * nx, y1 - asa * ty + asa * 0.75 * ny)
    p2 = (x1 - asa * tx - asa * 0.75 * nx, y1 - asa * ty - asa * 0.75 * ny)
    return arco, f"M{p1[0]:.2f} {p1[1]:.2f} L{x1:.2f} {y1:.2f} L{p2[0]:.2f} {p2[1]:.2f}"


# ---------- Pessoa ----------
def pessoa(cx=12.0, topo=4.0, s=1.0, classe="corpo"):
    """Pessoa: cabeça r 3,5·s e ombros em arco. s=1 ocupa 4,5..19,5 x 4..20,5.
    Para grupos, use s entre 0,75 e 0,85 e deslocamentos; mantenha folga de 1,2 px entre pessoas."""
    r = 3.5 * s
    cy = topo + r
    base = topo + 16.5 * s
    meia = 7.5 * s
    ombro = 4.5 * s
    yo = cy + r + 4 * s  # topo dos ombros
    corpo = (f"M{cx-meia:.2f} {base:.2f} V{base-1*s:.2f} A{ombro:.2f} {ombro:.2f} 0 0 1 {cx-meia+ombro:.2f} {yo:.2f} "
             f"H{cx+meia-ombro:.2f} A{ombro:.2f} {ombro:.2f} 0 0 1 {cx+meia:.2f} {base-1*s:.2f} V{base:.2f}")
    return f'<circle class="{classe}" cx="{cx:.2f}" cy="{cy:.2f}" r="{r:.2f}"/><path class="{classe}" d="{corpo}"/>'


# ---------- Selo (badge) no canto inferior direito ----------
SELO_CX, SELO_CY, SELO_R = 17.5, 17.5, 4.5  # o conteúdo do selo cabe no círculo r 4,5


def selo_folga(r=6.2):
    """Área recortada do desenho-base para o selo respirar (classe 'folga')."""
    return f'<circle class="folga" cx="{SELO_CX}" cy="{SELO_CY}" r="{r}" fill="#000" stroke="none"/>'


def mover(caminho_pontos, dx, dy):
    return [(x + dx, y + dy) for x, y in caminho_pontos]
