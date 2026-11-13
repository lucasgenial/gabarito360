"""
/**
 * Arquivo: icones/familia/lotes/lote6.py
 * Função: lote 6 da G360 Icones — 17 ícones de Conteúdo e dados (câmera, arquivos, agenda,
 *         contato, gráficos, senha, QR e o cartão-resposta).
 */
"""
from motivos import CALENDARIO, CAMERA, CAMERA_LENTE, CIRCULO, DOBRA, DOCUMENTO, FOLHA, PONTO, selo_folga


# ---------- Motivos novos deste lote (relatados ao coordenador) ----------
def m_lapis_selo():
    """Lápis sólido na diagonal, centrado no selo (17,5; 17,5); cabe em r 4,8 com o traço."""
    return ('<path class="selo" fill="currentColor" '
            'd="M14.75 20.25 L15.5 18.5 L19.25 14.75 L20.25 15.75 L16.5 19.5 Z"/>')


def m_barra_v(x, topo, base=20, meia=1.5):
    """Barra vertical de gráfico apoiada na linha de base: traço no contorno, barra cheia no preenchido."""
    return (f'<path class="sc" d="M{x} {base} V{topo}"/>'
            f'<path class="sp" d="M{x - meia} {base} V{topo} H{x + meia} V{base} Z"/>')


def m_barra_h(y, ponta, eixo=4, meia=1.5):
    """Barra horizontal apoiada no eixo vertical: traço no contorno, barra cheia no preenchido."""
    return (f'<path class="sc" d="M{eixo} {y} H{ponta}"/>'
            f'<path class="sp" d="M{eixo} {y - meia} H{ponta} V{y + meia} H{eixo} Z"/>')


BASE_GRAFICO = '<path class="fixo" d="M3 20 H21"/>'  # linha de base comum aos gráficos


def m_bolha_marcada(cx, cy, rx=1.35, ry=2):
    """Bolha preenchida do cartão-resposta: oval cheio, mais alto que a bolha vazia (PONTO r 0,95)."""
    return f'<ellipse cx="{cx}" cy="{cy}" rx="{rx}" ry="{ry}" fill="currentColor" stroke="none"/>'


def _bolhas(colunas, linhas, marcadas, r=0.95):
    return "".join(m_bolha_marcada(x, y) if (i, j) in marcadas else PONTO(x, y, r)
                   for j, y in enumerate(linhas) for i, x in enumerate(colunas))


def _modulos(posicoes, lado=2):
    return "".join(f'<rect class="fixo" x="{x}" y="{y}" width="{lado}" height="{lado}" rx="0.4" '
                   f'fill="currentColor" stroke="none"/>' for x, y in posicoes)


ICONES = {
    "camera": f"""
<path class="corpo" d="{CAMERA}"/>
{CAMERA_LENTE}""",

    "lanterna": """
<path class="fixo" d="M12 5 V3 M8 5 L6.75 3.25 M16 5 L17.25 3.25"/>
<path class="corpo" d="M7 8 H17 A1 1 0 0 1 18 9 V10.5 L15.5 13.5 V19 A2 2 0 0 1 13.5 21 H10.5 A2 2 0 0 1 8.5 19 V13.5 L6 10.5 V9 A1 1 0 0 1 7 8 Z"/>
<path d="M12 15.5 V17.5"/>""",

    "pdf": f"""
<path class="corpo" d="{DOCUMENTO}"/>
<path d="{DOBRA}"/>
<path d="M8.5 18 H15.5"/>
<rect class="folga" x="0.75" y="7.75" width="14.5" height="9" rx="2.5" fill="#000" stroke="none"/>
<rect class="selo" x="3" y="10" width="10" height="4.5" rx="1" fill="currentColor"/>""",

    "documento": f"""
<path class="corpo" d="{DOCUMENTO}"/>
<path d="{DOBRA}"/>
<path d="M8.5 12 H15.5 M8.5 15 H15.5 M8.5 18 H12.5"/>""",

    "rascunho": f"""
<path class="corpo" d="{DOCUMENTO}"/>
<path d="{DOBRA}"/>
<path d="M8.5 11.5 H13.5 M8.5 15 H10.5"/>
{selo_folga()}
{m_lapis_selo()}""",

    "calendario": CALENDARIO + "".join(PONTO(x, y, 1.1) for y in (12.75, 16.25) for x in (8, 12, 16)),

    "relogio": f"""
{CIRCULO}
<path d="M12 7 V12 L15.5 14"/>""",

    "email": """
<rect class="corpo" x="3" y="6" width="18" height="12" rx="2"/>
<path d="M4 7.25 L12 12.75 L20 7.25"/>""",

    "telefone": """
<path class="corpo" d="M5.25 3.75 H8.75 L10.25 8.25 L8.25 9.75 C9.25 12.25 11.75 14.75 14.25 15.75 L15.75 13.75 L20.25 15.25 V18.75 A1.5 1.5 0 0 1 18.75 20.25 C10.25 19.75 4.25 13.75 3.75 5.25 A1.5 1.5 0 0 1 5.25 3.75 Z"/>""",

    "local": """
<path class="corpo" d="M12 21 C9 18 5.5 13.5 5.5 9.5 A6.5 6.5 0 0 1 18.5 9.5 C18.5 13.5 15 18 12 21 Z"/>
<circle cx="12" cy="9.5" r="2.5"/>""",

    "inep": """
<rect class="corpo" x="3" y="5" width="18" height="14" rx="2"/>
<path d="M6.5 8.75 V15.25 M9.5 8.75 V15.25 M10.5 8.75 V15.25 M13.5 8.75 V15.25 M16.5 8.75 V15.25 M17.5 8.75 V15.25"/>""",

    "grafico-barras": f"""
{BASE_GRAFICO}
{m_barra_v(6, 12)}{m_barra_v(12, 5)}{m_barra_v(18, 9)}""",

    "grafico-barras-horizontais": f"""
<path class="fixo" d="M4 3 V21"/>
{m_barra_h(6, 12)}{m_barra_h(12, 19)}{m_barra_h(18, 15)}""",

    "grafico-linha": f"""
{BASE_GRAFICO}
<path class="fixo" d="M5 16 L10 10.5 L14 13.5 L19 6"/>
{"".join(PONTO(x, y, 1.6) for x, y in ((5, 16), (10, 10.5), (14, 13.5), (19, 6)))}""",

    "senha": f"""
<circle class="corpo" cx="8" cy="8" r="4.5"/>
{PONTO(8, 8, 1.5)}
<path class="fixo" d="M11.25 11.25 L19.25 19.25 M19.25 19.25 L21 17.5 M16.25 16.25 L18 14.5"/>""",

    "qr-code": f"""
<rect class="corpo" x="3.5" y="3.5" width="7" height="7" rx="1"/>
<rect class="corpo" x="13.5" y="3.5" width="7" height="7" rx="1"/>
<rect class="corpo" x="3.5" y="13.5" width="7" height="7" rx="1"/>
{PONTO(7, 7, 1.3)}{PONTO(17, 7, 1.3)}{PONTO(7, 17, 1.3)}
{_modulos([(12.75, 12.75), (19.25, 12.75), (16, 16), (12.75, 19.25), (19.25, 19.25)])}""",

    "cartao-resposta": f"""
<path class="corpo" d="{FOLHA}"/>
{_bolhas((8.5, 12, 15.5), (7.75, 12, 16.25), {(1, 0), (0, 1), (2, 2)})}""",
}

