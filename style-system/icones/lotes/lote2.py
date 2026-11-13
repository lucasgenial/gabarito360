"""
/**
 * Arquivo: icones/familia/lotes/lote2.py
 * Função: lote 2 da G360 Icones — 28 ícones de Menu e casca (navegação principal, conta e tema).
 *         No menu, o item ativo usa a variante preenchida.
 */
"""
import math

from motivos import CIRCULO, DOBRA, DOCUMENTO, FOLHA, PONTO, SELO_CX, SELO_CY, pessoa, selo_folga


# ---------- Motivos novos deste lote (m_*) ----------
def _r(v):
    return f"{v:.2f}".rstrip("0").rstrip(".")


def m_sem_classe(svg):
    """Tira a classe de um motivo para ele virar detalhe (recorte no preenchido)."""
    return svg.replace(' class="corpo"', "")


def m_engrenagem(cx, cy, r_ext, r_int, n=8, ponta=0.36, raiz=0.62, giro=0.0):
    """Engrenagem: n dentes; ponta/raiz = fração do passo ocupada pelo dente no topo e na base."""
    passo = 2 * math.pi / n
    pts, cmd = [], []
    for k in range(n):
        a = giro + k * passo
        r0, r1 = a - raiz * passo / 2, a + raiz * passo / 2
        t0, t1 = a - ponta * passo / 2, a + ponta * passo / 2
        p = lambda r, ang: (cx + r * math.cos(ang), cy + r * math.sin(ang))
        pts.append((p(r_int, r0), p(r_ext, t0), p(r_ext, t1), p(r_int, r1)))
    for k, (b0, e0, e1, b1) in enumerate(pts):
        if k == 0:
            cmd.append(f"M{_r(b0[0])} {_r(b0[1])}")
        cmd.append(f"L{_r(e0[0])} {_r(e0[1])} A{_r(r_ext)} {_r(r_ext)} 0 0 1 {_r(e1[0])} {_r(e1[1])} L{_r(b1[0])} {_r(b1[1])}")
        nb = pts[(k + 1) % n][0]
        cmd.append(f"A{_r(r_int)} {_r(r_int)} 0 0 1 {_r(nb[0])} {_r(nb[1])}")
    return " ".join(cmd) + " Z"


def m_vale(dy=0.0, r=1.5):
    """O vale de motivos.vale() deslocado na vertical (mesmo desenho, 3..21 x 6+dy..18+dy)."""
    y = lambda v: _r(v + dy)
    return (f"M5 {y(6)} H19 A2 2 0 0 1 21 {y(8)} V{y(12 - r)} A{r} {r} 0 0 0 21 {y(12 + r)} V{y(16)} "
            f"A2 2 0 0 1 19 {y(18)} H5 A2 2 0 0 1 3 {y(16)} V{y(12 + r)} A{r} {r} 0 0 0 3 {y(12 - r)} "
            f"V{y(8)} A2 2 0 0 1 5 {y(6)} Z")


def m_predio(selo=False):
    """Prédio institucional: frontão, colunas e base, partes separadas com folga 3.
    Com selo, saem as duas colunas da direita (a folga do selo as cortaria em tocos)."""
    colunas = "M6 12.5 V17.5 M10 12.5 V17.5" + ("" if selo else " M14 12.5 V17.5 M18 12.5 V17.5")
    return ('<path class="corpo" d="M3 9.5 L12 3.5 L21 9.5 Z"/>'
            f'<path class="fixo" d="{colunas}"/>'
            '<path class="fixo" d="M3 20.5 H21"/>')


# selo de engrenagem: forma cheia com furo (evenodd), igual nas duas variantes
_eng_selo = m_engrenagem(SELO_CX, SELO_CY, 4.4, 3.25, n=6, ponta=0.42, raiz=0.66, giro=math.pi / 6)
SELO_ENGRENAGEM = (f'<path class="selo" fill="currentColor" stroke="none" fill-rule="evenodd" '
                   f'd="{_eng_selo} M{SELO_CX + 1.5} {SELO_CY} A1.5 1.5 0 1 0 {SELO_CX - 1.5} {SELO_CY} '
                   f'A1.5 1.5 0 1 0 {SELO_CX + 1.5} {SELO_CY} Z"/>')

def m_lapis_selo(tip=(14.75, 20.25), l1=2.25, l2=6.75, w=1.25):
    """Lápis a 45° dentro do selo: ponta embaixo à esquerda, ponta traseira arredondada."""
    ux, uy = 1 / math.sqrt(2), -1 / math.sqrt(2)
    nx, ny = 1 / math.sqrt(2), 1 / math.sqrt(2)
    t = tip
    q = lambda l, s: (t[0] + l * ux + s * w * nx, t[1] + l * uy + s * w * ny)
    a, b, c, d = q(l1, 1), q(l2, 1), q(l2, -1), q(l1, -1)
    f = lambda p: f"{_r(p[0])} {_r(p[1])}"
    return (f'<path class="selo" d="M{f(t)} L{f(a)} L{f(b)} A{w} {w} 0 0 0 {f(c)} L{f(d)} Z '
            f'M{f(a)} L{f(d)}"/>')


SELO_LAPIS = m_lapis_selo()

# pessoa-base dos ícones com selo
PESSOA_SELO = pessoa(10.5, 3.5, 1.0)


# ---------- Aluno: capelo sobre a cabeça ----------
def _aluno():
    cx, cy, r = 12.0, 10.1, 2.8                       # cabeça de pessoa(12, 7.3, 0.8)
    esq, dir_, base_y = (3.5, 5.75), (20.5, 5.75), (12, 8.5)

    def corte(p, q):                                    # cabeça x aresta inferior do capelo
        (x0, y0), (x1, y1) = p, q
        dx, dy = x1 - x0, y1 - y0
        a = dx * dx + dy * dy
        b = 2 * (dx * (x0 - cx) + dy * (y0 - cy))
        c = (x0 - cx) ** 2 + (y0 - cy) ** 2 - r * r
        t = (-b - math.sqrt(b * b - 4 * a * c)) / (2 * a)
        return x0 + t * dx, y0 + t * dy
    pe, pd = corte(esq, base_y), corte(dir_, base_y)
    cabeca = f"M{_r(pe[0])} {_r(pe[1])} A{r} {r} 0 1 0 {_r(pd[0])} {_r(pd[1])}"
    ombros = pessoa(12, 7.3, 0.8).split("/>", 1)[1]
    return ('<path class="corpo" d="M3.5 5.75 L12 3 L20.5 5.75 L12 8.5 Z"/>'
            f'<path class="corpo" d="{cabeca}"/>'
            f'{ombros}'
            '<path class="fixo" d="M20.5 5.75 V9.75"/>')


# ---------- Lua: crescente ----------
def _lua():
    c1, r1 = (11.5, 12.5), 8.5
    c2, r2 = (17, 7), 6.5
    d = math.dist(c1, c2)
    a = (r1 * r1 - r2 * r2 + d * d) / (2 * d)
    h = math.sqrt(r1 * r1 - a * a)
    mx, my = c1[0] + a * (c2[0] - c1[0]) / d, c1[1] + a * (c2[1] - c1[1]) / d
    ux, uy = -(c2[1] - c1[1]) / d, (c2[0] - c1[0]) / d
    p1 = (mx + h * ux, my + h * uy)
    p2 = (mx - h * ux, my - h * uy)
    return (f'<path class="corpo" d="M{_r(p1[0])} {_r(p1[1])} A{r1} {r1} 0 1 1 {_r(p2[0])} {_r(p2[1])} '
            f'A{r2} {r2} 0 0 0 {_r(p1[0])} {_r(p1[1])} Z"/>')


def _sol():
    raios = []
    for k in range(8):
        a = math.radians(k * 45)
        x0, y0 = 12 + 7 * math.cos(a), 12 + 7 * math.sin(a)
        x1, y1 = 12 + 9 * math.cos(a), 12 + 9 * math.sin(a)
        raios.append(f"M{_r(x0)} {_r(y0)} L{_r(x1)} {_r(y1)}")
    return f'<circle class="corpo" cx="12" cy="12" r="4"/><path class="fixo" d="{" ".join(raios)}"/>'


_eng_grande = m_engrenagem(12, 12, 8.75, 6.5, n=8, ponta=0.34, raiz=0.56, giro=0)


ICONES = {
    "painel": """
<rect class="corpo" x="4" y="4" width="6.5" height="9" rx="1.5"/>
<rect class="corpo" x="4" y="16" width="6.5" height="4" rx="1.5"/>
<rect class="corpo" x="13.5" y="4" width="6.5" height="4" rx="1.5"/>
<rect class="corpo" x="13.5" y="11" width="6.5" height="9" rx="1.5"/>""",

    "usuarios-configuracao": f"""
{PESSOA_SELO}
{selo_folga()}
{SELO_ENGRENAGEM}""",

    "clientes": """
<path class="fixo" d="M9 7 V5.5 A1.5 1.5 0 0 1 10.5 4 H13.5 A1.5 1.5 0 0 1 15 5.5 V7"/>
<rect class="corpo" x="3" y="7" width="18" height="13" rx="2"/>
<path d="M3 13 H21"/>""",

    "acessos-internos": f"""
{PESSOA_SELO}
{selo_folga()}
<circle class="selo" cx="15.5" cy="17.5" r="2.25"/>
<path class="selo" d="M17.75 17.5 H21 M19.75 17.5 V19.75"/>""",

    "instituicoes-cadastro": f"""
{m_predio(selo=True)}
{selo_folga()}
<path class="selo" d="M17.75 14.5 H20.25 M17.25 17.5 H21 M17.75 20.5 H20.25"/>
<circle class="selo" cx="14.75" cy="14.5" r="0.95" fill="currentColor" stroke="none"/>
<circle class="selo" cx="14.25" cy="17.5" r="0.95" fill="currentColor" stroke="none"/>
<circle class="selo" cx="14.75" cy="20.5" r="0.95" fill="currentColor" stroke="none"/>""",

    "pedidos-titulares": f"""
{pessoa(12, 3.125, 0.75)}
<path class="corpo" d="M3 15.5 H8 L9.5 17.5 H14.5 L16 15.5 H21 V18.5 A2 2 0 0 1 19 20.5 H5 A2 2 0 0 1 3 18.5 Z"/>""",

    "exportacoes-titulares": f"""
<path class="corpo" d="{DOCUMENTO}"/>
<path d="{DOBRA}"/>
<circle cx="9.75" cy="8.6" r="1.4" fill="currentColor" stroke="none"/>
<path d="M7.25 14 A2.5 2.5 0 0 1 12.25 14 Z" fill="currentColor" stroke="none"/>
{selo_folga()}
<path class="selo" d="M13.5 17.5 H21 M18 14.5 L21 17.5 L18 20.5"/>""",

    "prova": f"""
<path class="corpo" d="{FOLHA}"/>
{PONTO(8.5, 7.5)}{PONTO(8.5, 12)}{PONTO(8.5, 16.5)}
<path d="M11.75 7.5 H15.5 M11.75 12 H15.5 M11.75 16.5 H15.5"/>""",

    "corrigir": """
<path class="fixo" d="M3 7.5 V5 A2 2 0 0 1 5 3 H7.5 M16.5 3 H19 A2 2 0 0 1 21 5 V7.5 M21 16.5 V19 A2 2 0 0 1 19 21 H16.5 M7.5 21 H5 A2 2 0 0 1 3 19 V16.5"/>
<rect class="corpo" x="7.5" y="6.5" width="9" height="11" rx="1.5"/>
<path d="M10.5 10.5 H13.5 M10.5 13.5 H13.5"/>""",

    "turma": f"""
{pessoa(12, 8.375, 0.75)}
<circle class="corpo" cx="5.75" cy="5.63" r="2.63"/>
<circle class="corpo" cx="18.25" cy="5.63" r="2.63"/>
<path class="corpo" d="M6.25 11.25 H5.75 A2.75 2.75 0 0 0 3 14 V14.75"/>
<path class="corpo" d="M17.75 11.25 H18.25 A2.75 2.75 0 0 1 21 14 V14.75"/>""",

    "disciplina": """
<path class="corpo" d="M12 6.5 C10 5 6.5 4.5 3 5 V18.5 C6.5 18 10 18.5 12 20 C14 18.5 17.5 18 21 18.5 V5 C17.5 4.5 14 5 12 6.5 Z"/>
<path d="M12 6.5 V20"/>""",

    "instituicao": f"""
{m_predio()}""",

    "solicitacao": f"""
{m_predio(selo=True)}
{selo_folga()}
<path class="selo" d="M17.5 14 V21 M14 17.5 H21"/>""",

    "instituicao-gestao": f"""
{m_predio(selo=True)}
{selo_folga()}
{SELO_LAPIS}""",

    "relatorio": f"""
<path class="corpo" d="{DOCUMENTO}"/>
<path d="{DOBRA}"/>
<path d="M9 17.5 V14.5 M12 17.5 V11.5 M15 17.5 V13"/>""",

    "escola": """
<path class="corpo" d="M3 20.5 V14 A1.5 1.5 0 0 1 4.5 12.5 H8 V9.5 H16 V12.5 H19.5 A1.5 1.5 0 0 1 21 14 V20.5 Z"/>
<path class="fixo" d="M12 9.5 V3 H16.5 L15.25 4.75 L16.5 6.5 H12"/>
<path class="sp" d="M12 3 H16.5 L15.25 4.75 L16.5 6.5 H12 Z"/>
<path d="M10.5 20.5 V17 A1.5 1.5 0 0 1 13.5 17 V20.5"/>""",

    "aluno": _aluno(),

    "equipe": f"""
{pessoa(8.75, 7.875, 0.75)}
<circle class="corpo" cx="15.5" cy="5.63" r="2.63"/>
<path class="corpo" d="M14.5 11.25 H17.75 A3.13 3.13 0 0 1 20.88 14.38 V15.38"/>""",

    "copias": f"""
<path class="fixo" d="M8.5 3 H18 A2 2 0 0 1 20 5 V16.5"/>
<rect class="corpo" x="4" y="7" width="12" height="14" rx="2"/>
{PONTO(7.25, 11.5)}{PONTO(7.25, 16.5)}
<path d="M10.5 11.5 H12.5 M10.5 16.5 H12.5"/>""",

    "menu": """
<path class="fixo" d="M4 6 H20 M4 12 H20 M4 18 H20"/>""",

    "mais-vertical": f"""
{PONTO(12, 5, 1.6)}{PONTO(12, 12, 1.6)}{PONTO(12, 19, 1.6)}""",

    "sair": """
<path class="corpo" d="M12.5 7.5 V5 A2 2 0 0 0 10.5 3 H6 A2 2 0 0 0 4 5 V19 A2 2 0 0 0 6 21 H10.5 A2 2 0 0 0 12.5 19 V16.5"/>
<path d="M8.5 12 H20.5 M17 8.5 L20.5 12 L17 15.5"/>""",

    "perfil": f"""
{CIRCULO}
{m_sem_classe(pessoa(12, 6.75, 0.75))}""",

    "configuracoes": f"""
<path class="corpo" d="{_eng_grande}"/>
<circle cx="12" cy="12" r="2.75"/>""",

    "trocar-vinculo": f"""
{PESSOA_SELO}
{selo_folga()}
<path class="selo" d="M14.25 15.25 H20.5 M19.25 14 L20.5 15.25 L19.25 16.5 M20.5 19.75 H14.25 M15.5 18.5 L14.25 19.75 L15.5 21"/>""",

    "plano-e-uso": f"""
<path class="fixo" d="M5.5 5.5 H18.5"/>
<path class="corpo" d="{m_vale(2.5)}"/>
{PONTO(8, 11.5, 0.95)}{PONTO(8, 14.5, 0.95)}{PONTO(8, 17.5, 0.95)}""",

    "tema-claro": _sol(),

    "tema-escuro": _lua(),
}
