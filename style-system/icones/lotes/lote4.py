"""
/**
 * Arquivo: icones/familia/lotes/lote4.py
 * Função: lote 4 da G360 Icones — 9 ícones de Setas e navegação.
 *         Setas e chevrons saem de seta()/chevron() de motivos.py; ícones sem corpo usam
 *         classe "fixo" (preenchido = contorno). link-externo tem corpo (quadrado).
 */
"""
from motivos import _caminho, chevron, girar, mover, seta

# Calibragem ótica do lote (conferida em 16/20/24 px contra a REFERÊNCIA do lote 1).
SETA_COMP, SETA_ASA = 17, 6.5
CHEV = 7


def m_seta(graus=0, comprimento=16, asa=6, dx=0.0, dy=0.0, pontas=1):
    """Seta reta do motivo seta(), deslocada sem transform (para pares ⇄ e setas duplas ↔).
    pontas=2 põe ponta nas duas extremidades. Mesma construção de motivos.seta()."""
    m = comprimento / 2
    haste = [(12 - m, 12), (12 + m, 12)]
    ponta = [(12 + m - asa, 12 - asa), (12 + m, 12), (12 + m - asa, 12 + asa)]
    trechos = [haste, ponta]
    if pontas == 2:
        trechos.append([(12 - m + asa, 12 - asa), (12 - m, 12), (12 - m + asa, 12 + asa)])
    return _caminho(*(mover(girar(t, graus), dx, dy) for t in trechos))


ICONES = {
    "voltar": f"""
<path class="fixo" d="{seta(180, SETA_COMP, SETA_ASA)}"/>""",

    "avancar": f"""
<path class="fixo" d="{seta(0, SETA_COMP, SETA_ASA)}"/>""",

    "chevron-esquerda": f"""
<path class="fixo" d="{chevron(180, CHEV)}"/>""",

    "chevron-direita": f"""
<path class="fixo" d="{chevron(0, CHEV)}"/>""",

    "chevron-cima": f"""
<path class="fixo" d="{chevron(270, CHEV)}"/>""",

    "chevron-baixo": f"""
<path class="fixo" d="{chevron(90, CHEV)}"/>""",

    # ↔ sobre barra de rolagem (trilho em cápsula + cursor): "a tabela rola para os lados".
    # O trilho é corpo; no preenchido o cursor fica vazado.
    "rolar-horizontal": f"""
<path class="fixo" d="{m_seta(0, 16, 4, dy=-4.5, pontas=2)}"/>
<rect class="corpo" x="3" y="15" width="18" height="6" rx="3"/>
<path d="M8.5 18 H15.5"/>""",

    # Quadrado aberto no canto + seta diagonal saindo. No preenchido o quadrado é cheio,
    # com um canal vazado em volta da haste (folga 1,2 px).
    "link-externo": """
<path class="sc" d="M12.5 7 H5 A2 2 0 0 0 3 9 V19 A2 2 0 0 0 5 21 H15 A2 2 0 0 0 17 19 V11.5"/>
<path class="sp" d="M12.5 7 H5 A2 2 0 0 0 3 9 V19 A2 2 0 0 0 5 21 H15 A2 2 0 0 0 17 19 V11.5 L13.25 15.25 A3.18 3.18 0 0 1 8.75 10.75 Z"/>
<path class="fixo" d="M11 13 L21 3 M15.5 3 H21 V8.5"/>""",

    # ⇄ duas setas opostas em linhas separadas.
    "transferir": f"""
<path class="fixo" d="{m_seta(0, 16, 4, dy=-3.5)}"/>
<path class="fixo" d="{m_seta(180, 16, 4, dy=3.5)}"/>""",
}
