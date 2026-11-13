"""
/**
 * Arquivo: icones/familia/lotes/lote7.py
 * Função: lote 7 da G360 Icones — 14 ícones de Avaliação e questões.
 *         Sistema da leitura: a "caixa de questão" (quadrado 4..20, raio 2) é a base comum de
 *         questao, acerto, erro-questao, anulada, em-branco e marcacao-dupla.
 */
"""
from motivos import CHECK_INTERNO, FOLHA, PONTO, XIS_INTERNO, selo_folga

# ---------- Motivos novos deste lote ----------
# Caixa de questão: uma questão isolada (célula do relatório, item da leitura). Quadrado 4..20.
m_CAIXA = "M6 4 H18 A2 2 0 0 1 20 6 V18 A2 2 0 0 1 18 20 H6 A2 2 0 0 1 4 18 V6 A2 2 0 0 1 6 4 Z"
m_caixa = f'<path class="corpo" d="{m_CAIXA}"/>'


def m_bolha_cheia(cx, cy, r=1.5):
    """Bolha de alternativa marcada: disco cheio (r + meio traço = raio visível)."""
    return f'<circle cx="{cx}" cy="{cy}" r="{r}" fill="currentColor"/>'


# Folha de prova (linhas de questão: bolinha + linha), já recortada para receber selo.
m_PROVA_LINHAS = (PONTO(8.5, 7.5) + PONTO(8.5, 11.5) + PONTO(8.5, 15.5)
                  + '<path d="M11.25 7.5 H15.75 M11.25 11.5 H13.5"/>')

ICONES = {
    "questao": f"""
{m_caixa}
<path d="M8 8.5 H16 M8 11.75 H13.5"/>
{m_bolha_cheia(8.75, 15.75, 0.5)}
<path d="M11.5 15.75 H16"/>""",

    "banco-questoes": """
<path class="corpo" d="M5 6 A7 2.75 0 0 1 19 6 V18 A7 2.75 0 0 1 5 18 Z"/>
<path d="M5 6 A7 2.75 0 0 0 19 6 M5 10 A7 2.75 0 0 0 19 10 M5 14 A7 2.75 0 0 0 19 14"/>""",

    "gabarito": f"""
<path class="corpo" d="{FOLHA}"/>
{PONTO(9.25, 7.25, 0.95)}{PONTO(13.5, 7.25, 1.5)}
{PONTO(9.25, 11.5, 1.5)}{PONTO(13.5, 11.5, 0.95)}
{PONTO(9.25, 15.75, 1.5)}
{selo_folga()}
<circle class="selo" cx="15.25" cy="17.5" r="1.75"/>
<path class="selo" d="M17 17.5 H21 M19.75 17.5 V19.5"/>""",

    "simulado": f"""
<path class="corpo" d="{FOLHA}"/>
{m_PROVA_LINHAS}
{selo_folga()}
<circle class="selo" cx="17.5" cy="18.25" r="2.75"/>
<path class="selo" d="M16.5 13.75 H18.5 M17.5 13.75 V15.5 M17.5 18.25 L18.5 17.25"/>""",

    "aplicacao": f"""
<path class="corpo" d="{FOLHA}"/>
{m_PROVA_LINHAS}
{selo_folga()}
<path class="selo" d="M15.75 14.75 L20.25 17.5 L15.75 20.25 Z" fill="currentColor"/>""",

    "nota": """
<path class="corpo" d="M12 3.5 L14.5 9.25 L20.75 10 L16.25 14 L17.5 20.25 L12 17 L6.5 20.25 L7.75 14 L3.25 10 L9.5 9.25 Z"/>""",

    "acerto": f"""
{m_caixa}
<path d="{CHECK_INTERNO}"/>""",

    "erro-questao": f"""
{m_caixa}
<path d="{XIS_INTERNO}"/>""",

    "anulada": f"""
{m_caixa}
<path d="M3 21 L21 3"/>""",

    "em-branco": f"""
{m_caixa}
<circle cx="12" cy="12" r="3.5"/>""",

    "marcacao-dupla": f"""
{m_caixa}
{m_bolha_cheia(8.5, 12)}{m_bolha_cheia(15.5, 12)}""",

    "escriba": """
<path class="corpo" d="M7.5 16.5 C7.5 9.5 13 4 20.5 3.5 C20.5 7 19.25 9.5 17.5 11.25 L15.5 11.75 L16 13 C13.75 15.5 11 16.5 7.5 16.5 Z"/>
<path d="M7.5 16.5 L11.75 12.25"/>
<path class="fixo" d="M7.5 16.5 L3.5 20.5"/>
<path class="fixo" d="M11 20 C12 18.75 13 18.75 14 20 C15 21.25 16 21.25 17 20"/>""",

    "curadoria": """
<circle class="corpo" cx="12" cy="5.25" r="2.25"/>
<path class="fixo" d="M12 7.5 V10.5"/>
<rect class="corpo" x="4.5" y="10.5" width="15" height="5" rx="1.5"/>
<path class="fixo" d="M3.5 19.5 H20.5"/>""",

    "olimpiada": """
<path class="sc" d="M10.25 10 L6 3 H10 L12 6.25 L14 3 H18 L13.75 10"/>
<path class="sp" d="M10.25 10 L6 3 H10 L12 6.25 L14 3 H18 L13.75 10 Z"/>
<circle class="corpo" cx="12" cy="15" r="5.5"/>
<circle cx="12" cy="15" r="2.25"/>""",
}
