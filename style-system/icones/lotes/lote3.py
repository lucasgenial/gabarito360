"""
/**
 * Arquivo: icones/familia/lotes/lote3.py
 * Função: lote 3 da G360 Icones — 32 ícones de Ações (botões, barras de ferramenta, alças).
 *         Prioridade de leitura em 16 px (botões "pq", alvo de 44 px): desenhos enxutos.
 */
"""
from motivos import (DOBRA, DOCUMENTO, LUPA, MAIS, PONTO, seta_circular, selo_folga)

# ---------- Auxiliares deste lote ----------
# Lupa: centro (10,5; 10,5), interior livre r 4. MAIS_INTERNO/MENOS_INTERNO reduzidos a meia-haste 2,5.
LUPA_MAIS = "M10.5 8 V13 M8 10.5 H13"
LUPA_MENOS = "M8 10.5 H13"

# Bandeja de arquivo (baixar / enviar-arquivo): U aberto em cima, 4..20 x 15..20.
m_BANDEJA = "M4 15 V18 A2 2 0 0 0 6 20 H18 A2 2 0 0 0 20 18 V15"

# Barras de ordenação (lista ordenada): a mais longa em cima. O sentido vem só da seta.
BARRAS_ORDEM = "M13.5 6 H20 M13.5 12 H18 M13.5 18 H16"
SETA_SOBE_ESQ = "M7 19.5 V4.5 M3.5 8 L7 4.5 L10.5 8"
SETA_DESCE_ESQ = "M7 4.5 V19.5 M3.5 16 L7 19.5 L10.5 16"

# Atualizar: duas setas circulares (r 7,5) em sentido horário.
arco_a, ponta_a = seta_circular(12, 12, 7.5, 160, 300, asa=2.2)
arco_b, ponta_b = seta_circular(12, 12, 7.5, 340, 480, asa=2.2)

# Funil: 4..20 no topo, gargalo 10..14, haste até 20 com base arredondada.
FUNIL = "M4 4 H20 L14 12 V19 A1 1 0 0 1 13 20 H11 A1 1 0 0 1 10 19 V12 Z"
FUNIL_MENOR = "M3 4 H17 L12 10.5 V17 A1 1 0 0 1 11 18 H9 A1 1 0 0 1 8 17 V10.5 Z"

ICONES = {
    "adicionar": f"""
<path class="fixo" d="{MAIS}"/>""",

    "editar": """
<path class="corpo" d="M4 20 L5.25 15.25 L15.75 4.75 A2.475 2.475 0 0 1 19.25 8.25 L8.75 18.75 Z"/>
<path d="M5.25 15.25 L8.75 18.75"/>""",

    "salvar": """
<path class="corpo" d="M6 4 H16 L20 8 V18 A2 2 0 0 1 18 20 H6 A2 2 0 0 1 4 18 V6 A2 2 0 0 1 6 4 Z"/>
<path d="M8.5 7.5 H13.5"/>
<rect x="8" y="13" width="8" height="4" rx="1"/>""",

    "excluir": """
<path class="fixo" d="M4 6.5 H20"/>
<path class="fixo" d="M9.5 6.5 V4.5 A1 1 0 0 1 10.5 3.5 H13.5 A1 1 0 0 1 14.5 4.5 V6.5"/>
<path class="corpo" d="M6 6.5 L6.8 18 Q6.95 20 9 20 H15 Q17.05 20 17.2 18 L18 6.5 Z"/>
<path d="M10 10 V16.5 M14 10 V16.5"/>""",

    "arquivar": """
<rect class="corpo" x="3" y="4" width="18" height="5" rx="1.5"/>
<path class="corpo" d="M5 9 V18 A2 2 0 0 0 7 20 H17 A2 2 0 0 0 19 18 V9 Z"/>
<path d="M5 9 H19"/>
<path d="M10 13 H14"/>""",

    "copiar": """
<path class="fixo" d="M5.5 15.5 A1.5 1.5 0 0 1 4 14 V6 A2 2 0 0 1 6 4 H14 A1.5 1.5 0 0 1 15.5 5.5"/>
<rect class="corpo" x="8.5" y="8.5" width="11.5" height="11.5" rx="2"/>""",

    "imprimir": """
<path class="fixo" d="M7 8 V4 A1 1 0 0 1 8 3 H16 A1 1 0 0 1 17 4 V8"/>
<path class="corpo" d="M7 17 H5 A2 2 0 0 1 3 15 V10 A2 2 0 0 1 5 8 H19 A2 2 0 0 1 21 10 V15 A2 2 0 0 1 19 17 H17"/>
<path d="M7 13 H17 V20 A1 1 0 0 1 16 21 H8 A1 1 0 0 1 7 20 Z"/>""",

    "baixar": f"""
<path class="fixo" d="{m_BANDEJA}"/>
<path class="fixo" d="M12 4 V14.5 M7.5 10 L12 14.5 L16.5 10"/>""",

    "enviar-arquivo": f"""
<path class="fixo" d="{m_BANDEJA}"/>
<path class="fixo" d="M12 15 V4.5 M7.5 9 L12 4.5 L16.5 9"/>""",

    "compartilhar": """
<circle class="corpo" cx="17" cy="5.75" r="2.75"/>
<circle class="corpo" cx="6.5" cy="12" r="2.75"/>
<circle class="corpo" cx="17" cy="18.25" r="2.75"/>
<path class="fixo" d="M8.86 10.59 L14.64 7.16 M8.86 13.41 L14.64 16.84"/>""",

    "buscar": f"""
{LUPA}""",

    "filtro": f"""
<path class="corpo" d="{FUNIL}"/>""",

    "limpar-filtro": f"""
<path class="corpo" d="{FUNIL_MENOR}"/>
<path class="selo" d="M15 15 L20 20 M20 15 L15 20"/>""",

    "ordenar": f"""
<path class="fixo" d="{SETA_SOBE_ESQ}"/>
<path class="fixo" d="M17 4.5 V19.5 M13.5 16 L17 19.5 L20.5 16"/>""",

    "ordenar-crescente": f"""
<path class="fixo" d="{SETA_SOBE_ESQ}"/>
<path class="fixo" d="{BARRAS_ORDEM}"/>""",

    "ordenar-decrescente": f"""
<path class="fixo" d="{SETA_DESCE_ESQ}"/>
<path class="fixo" d="{BARRAS_ORDEM}"/>""",

    "ver": """
<path class="corpo" d="M3 12 C5.5 7.5 8.5 5.5 12 5.5 C15.5 5.5 18.5 7.5 21 12 C18.5 16.5 15.5 18.5 12 18.5 C8.5 18.5 5.5 16.5 3 12 Z"/>
<circle cx="12" cy="12" r="3"/>""",

    "ocultar": """
<path class="corpo" d="M3 12 C5.5 7.5 8.5 5.5 12 5.5 C15.5 5.5 18.5 7.5 21 12 C18.5 16.5 15.5 18.5 12 18.5 C8.5 18.5 5.5 16.5 3 12 Z"/>
<circle cx="12" cy="12" r="3"/>
<path class="folga" d="M2.5 5.5 L5.5 2.5 L21.5 18.5 L18.5 21.5 Z" fill="#000" stroke="none"/>
<path class="selo" d="M4 4 L20 20"/>""",

    "atualizar": f"""
<path class="fixo" d="{arco_a}"/>
<path class="fixo" d="{ponta_a}"/>
<path class="fixo" d="{arco_b}"/>
<path class="fixo" d="{ponta_b}"/>""",

    "desfazer": """
<path class="fixo" d="M19.5 19 V15.5 A6.5 6.5 0 0 0 13 9 H5"/>
<path class="fixo" d="M9 5 L5 9 L9 13"/>""",

    "arrastar": f"""
{PONTO(9, 6, 1.5)}{PONTO(15, 6, 1.5)}
{PONTO(9, 12, 1.5)}{PONTO(15, 12, 1.5)}
{PONTO(9, 18, 1.5)}{PONTO(15, 18, 1.5)}""",

    "mover": """
<path class="fixo" d="M12 3.5 V20.5 M3.5 12 H20.5"/>
<path class="fixo" d="M9 6.5 L12 3.5 L15 6.5 M9 17.5 L12 20.5 L15 17.5 M6.5 9 L3.5 12 L6.5 15 M17.5 9 L20.5 12 L17.5 15"/>""",

    "fixar": """
<path class="corpo" d="M9.5 4 H14.5 V9.5 L17.5 13.5 H6.5 L9.5 9.5 Z"/>
<path class="fixo" d="M8 4 H16"/>
<path class="fixo" d="M12 13.5 V20.5"/>""",

    "redimensionar": """
<path class="fixo" d="M20 7 L7 20 M20 12.5 L12.5 20 M20 18 L18 20"/>""",

    "ampliar": f"""
{LUPA}
<path d="{LUPA_MAIS}"/>""",

    "diminuir": f"""
{LUPA}
<path d="{LUPA_MENOS}"/>""",

    "tela-cheia": """
<path class="fixo" d="M4 9 V4 H9 M4 4 L9.5 9.5"/>
<path class="fixo" d="M15 4 H20 V9 M20 4 L14.5 9.5"/>
<path class="fixo" d="M4 15 V20 H9 M4 20 L9.5 14.5"/>
<path class="fixo" d="M20 15 V20 H15 M20 20 L14.5 14.5"/>""",

    "gerar-pdf": f"""
<path class="corpo" d="{DOCUMENTO}"/>
<path d="{DOBRA}"/>
<path d="M8.5 11.5 H13"/>
{selo_folga()}
<path class="selo" d="M14 17.5 H20.5 M17.75 14.75 L20.5 17.5 L17.75 20.25"/>""",

    "exibir-tabela": """
<rect class="fixo" x="3" y="4" width="18" height="16" rx="2"/>
<path class="sp" d="M3 9 V6 A2 2 0 0 1 5 4 H19 A2 2 0 0 1 21 6 V9 Z"/>
<path class="fixo" d="M3 9 H21 M3 14.5 H21 M9.5 9 V20"/>""",

    "exibir-cards": """
<rect class="corpo" x="4" y="4" width="6.5" height="6.5" rx="1.5"/>
<rect class="corpo" x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/>
<rect class="corpo" x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/>
<rect class="corpo" x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>""",

    "opcoes": f"""
{PONTO(5, 12, 1.6)}{PONTO(12, 12, 1.6)}{PONTO(19, 12, 1.6)}""",

    "abrir-painel": """
<rect class="corpo" x="4" y="4" width="6.5" height="9" rx="1.5"/>
<rect class="corpo" x="4" y="16" width="6.5" height="4" rx="1.5"/>
<rect class="corpo" x="13.5" y="4" width="6.5" height="4" rx="1.5"/>
<path class="fixo" d="M14 20 L20 14 M15 14 H20 V19"/>""",
}
