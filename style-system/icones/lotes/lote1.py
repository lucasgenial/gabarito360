"""
/**
 * Arquivo: icones/familia/lotes/lote1.py
 * Função: lote 1 da G360 Icones — 22 ícones de Plano e uso e do Leitor360 (entregue em 13/11/2026).
 */
"""
from motivos import FOLHA, PICOTE, seta_circular, vale

arco_reenvio, cabeca_reenvio = seta_circular(12, 12, 8.5, 130, 60, asa=2.4)

ICONES = {
    # ---------- Plano e uso ----------
    "cobertura-plano": f"""
<path class="corpo" d="M12 3 L19 5.5 V11 C19 15.5 16 18.8 12 21 C8 18.8 5 15.5 5 11 V5.5 Z"/>
<path d="M9 12 L11.2 14.2 L15.2 10"/>""",

    "correcoes-disponiveis": f"""
<path class="corpo" d="{vale()}"/>
{PICOTE}""",

    "franquia-ciclo": """
<rect class="corpo" x="4" y="5" width="16" height="15" rx="2"/>
<path class="fixo" d="M8 3 V6.5 M16 3 V6.5"/>
<path d="M4 9 H20"/>
<path d="M9 14.8 L11 16.8 L15 12.8"/>""",

    "consumo-ciclo": """
<path class="sc" d="M6 19 A8.5 8.5 0 1 1 18 19"/>
<path class="sp" d="M6 19 A8.5 8.5 0 1 1 18 19 Z"/>
<path d="M12 13.5 L15.6 9.6"/>
<circle cx="12" cy="13.5" r="1.4" fill="currentColor" stroke="none"/>""",

    "referencia-acompanhamento": """
<path class="fixo" d="M4 4 V20 H20"/>
<path class="fixo" d="M7.5 7 H8.5 M12 7 H13 M16.5 7 H17.5"/>
<path class="fixo" d="M7.5 16.5 L11 13 L14 15 L19 10"/>
<path class="sp" d="M7.5 16.5 L11 13 L14 15 L19 10 V20 H7.5 Z" stroke="none"/>""",

    "adicionais": f"""
<path class="corpo" d="{vale()}"/>
{PICOTE}
<path d="M14 9.5 V14.5 M11.5 12 H16.5"/>""",

    "teto-periodo": """
<path class="fixo" d="M4 4.5 H20"/>
<path class="sc" d="M7 20 V14 M12 20 V10.5 M17 20 V8"/>
<path class="sp" d="M6.2 20 V14 H7.8 V20 Z M11.2 20 V10.5 H12.8 V20 Z M16.2 20 V8 H17.8 V20 Z"/>""",

    "operacoes-processamento": """
<path class="fixo" d="M5.5 3 H18.5 M5.5 21 H18.5"/>
<path class="corpo" d="M7.5 3 H16.5 V6 C16.5 9.5 13.2 10.8 13.2 12 C13.2 13.2 16.5 14.5 16.5 18 V21 H7.5 V18 C7.5 14.5 10.8 13.2 10.8 12 C10.8 10.8 7.5 9.5 7.5 6 Z"/>
<path d="M9.8 19.2 H14.2 L12 16.6 Z" fill="currentColor" stroke="none"/>""",

    "beneficio": """
<path class="corpo" d="M4 7.5 H20 A1 1 0 0 1 21 8.5 V10 A1 1 0 0 1 20 11 H4 A1 1 0 0 1 3 10 V8.5 A1 1 0 0 1 4 7.5 Z"/>
<path class="corpo" d="M5 11 H19 V19 A1.5 1.5 0 0 1 17.5 20.5 H6.5 A1.5 1.5 0 0 1 5 19 Z"/>
<path d="M12 7.5 V20.5"/>
<path class="fixo" d="M12 7.5 C10.5 4 7 4 7.5 6 C7.8 7.2 10 7.5 12 7.5 M12 7.5 C13.5 4 17 4 16.5 6 C16.2 7.2 14 7.5 12 7.5"/>""",

    "contratacao": """
<path class="corpo" d="M14 3 H7 A2 2 0 0 0 5 5 V19 A2 2 0 0 0 7 21 H17 A2 2 0 0 0 19 19 V8 Z"/>
<path d="M14 3 V8 H19"/>
<path d="M8.5 11.5 H13.5"/>
<path d="M8.5 17 C9.5 14.5 10.5 14.5 10.8 16.2 C11 17.4 11.8 17.3 12.4 16.2 C12.9 15.3 13.6 15.4 14 16.4 C14.3 17 14.9 17.1 15.5 16.5"/>""",

    "reenvio": f"""
<rect class="corpo" x="9.25" y="8.25" width="5.5" height="7.5" rx="1"/>
<path class="fixo" d="{arco_reenvio}"/>
<path class="fixo" d="{cabeca_reenvio}"/>""",

    "consumo-operacao": f"""
<path class="corpo" d="{vale()}"/>
{PICOTE}
<path d="M11.5 12 H16.5"/>""",

    "teto-operacao": """
<path class="fixo" d="M4 4.5 H20"/>
<path class="fixo" d="M12 8.5 V20 M9 11.5 L12 8.5 L15 11.5 M9 17 L12 20 L15 17"/>""",

    # ---------- Leitor360 ----------
    "webcam": """
<circle class="corpo" cx="12" cy="10" r="7"/>
<circle cx="12" cy="10" r="2.5"/>
<path class="fixo" d="M12 17 V20.5 M7.5 21 H16.5"/>""",

    "galeria": """
<path class="fixo" d="M7 3 H19 A2 2 0 0 1 21 5 V16"/>
<rect class="corpo" x="3" y="7" width="14" height="14" rx="2"/>
<path d="M3.5 19 L8 14.5 L11.5 18 L13.5 16 L16.5 19"/>
<circle cx="12.5" cy="11" r="1.6" fill="currentColor" stroke="none"/>""",

    "lote-pdf": """
<path class="fixo" d="M8 3 H15 L20 8 V16.5"/>
<path class="corpo" d="M12 6 H6 A2 2 0 0 0 4 8 V19 A2 2 0 0 0 6 21 H14 A2 2 0 0 0 16 19 V10 Z"/>
<path d="M12 6 V10 H16"/>
<path d="M7 14 H13 M7 17.5 H11"/>""",

    "tipo-objetiva": """
<circle class="fixo" cx="12" cy="12" r="8"/>
<circle class="fixo" cx="12" cy="12" r="3.6" fill="currentColor" stroke="none"/>""",

    "tipo-texto": """
<path class="fixo" d="M5 7 V4.5 H19 V7 M12 4.5 V19.5 M9 19.5 H15"/>""",

    "tipo-formula": """
<path class="fixo" d="M18 6.5 V4.5 H6 L12.5 12 L6 19.5 H18 V17.5"/>""",

    "tipo-desenho": """
<path class="corpo" d="M3.5 20.5 L9.5 10 L15.5 20.5 Z"/>
<circle class="corpo" cx="16.5" cy="7.5" r="4"/>""",

    "leitura-pendente": f"""
<path class="corpo" d="{FOLHA}"/>
<path d="M12 7.5 V13"/>
<circle cx="12" cy="16.8" r="1.15" fill="currentColor" stroke="none"/>""",

    "pagina-faltando": f"""
<path class="fixo" d="{FOLHA}" stroke-dasharray="2.2 3.1"/>""",
}
