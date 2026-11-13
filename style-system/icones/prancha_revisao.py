"""
/**
 * Arquivo: icones/familia/prancha_revisao.py
 * Função: monta a prancha de revisão (HTML) com cada ícone nas duas variantes,
 *         em 64 px com grade e em 16/20/24/32 px, nos temas claro e escuro.
 */
"""
from pathlib import Path

import sys
BASE = Path(__file__).parent
SAIDA = Path(sys.argv[1]) if len(sys.argv) > 1 else BASE / "saida"
HTML = Path(sys.argv[2]) if len(sys.argv) > 2 else BASE / "prancha-revisao.html"
REF = ["cobertura-plano", "leitura-pendente", "webcam"]  # calibragem: lote 1 aprovado
nomes = sorted(p.stem for p in (SAIDA / "contorno").glob("*.svg"))


def svg_inline(variante, nome, px, cor):
    pasta = SAIDA if (SAIDA / variante / f"{nome}.svg").exists() else BASE / "saida"
    d = (pasta / variante / f"{nome}.svg").read_text(encoding="utf-8")
    d = d.replace("<svg ", f'<svg width="{px}" height="{px}" fill="{cor}" ', 1)
    return d


GRADE = ('<svg class="grade" width="96" height="96" viewBox="0 0 24 24">'
         + "".join(f'<path d="M{i} 0V24M0 {i}H24" stroke="#e4e9f2" stroke-width="0.05"/>' for i in range(25))
         + '<rect x="2" y="2" width="20" height="20" fill="none" stroke="#f3a8a8" stroke-width="0.08"/>'
         + '<circle cx="12" cy="12" r="9" fill="none" stroke="#9cc3f5" stroke-width="0.08"/></svg>')

linhas = []
ref = "".join(svg_inline(v, n, px, "#16233a") for n in REF if (BASE / "saida" / "contorno" / f"{n}.svg").exists()
              for v in ("contorno", "preenchido") for px in (24, 48))
linhas.append(f'<div class="l"><b>REFERÊNCIA lote 1</b><div class="p">{ref}</div></div>')
for n in nomes:
    grande = "".join(f'<div class="g">{GRADE}<div class="sobre">{svg_inline(v, n, 96, "#16233a")}</div></div>'
                     for v in ("contorno", "preenchido"))
    pequenos = "".join(svg_inline(v, n, px, "#16233a") for v in ("contorno", "preenchido") for px in (16, 20, 24, 32))
    escuro = "".join(svg_inline(v, n, px, "#e7edf6") for v in ("contorno", "preenchido") for px in (16, 24))
    linhas.append(f'<div class="l"><b>{n}</b>{grande}<div class="p">{pequenos}</div><div class="p e">{escuro}</div></div>')

html = f"""<!doctype html><meta charset="utf-8"><style>
body{{margin:12px;font:11px system-ui,sans-serif;color:#16233a;background:#fff}}
.l{{display:flex;align-items:center;gap:14px;border-bottom:1px solid #eef2f8;padding:4px 0}}
b{{width:170px}}.g{{position:relative;width:96px;height:96px}}.g>*{{position:absolute;inset:0}}
.p{{display:flex;gap:10px;align-items:center;padding:6px 10px}}.e{{background:#0f1725;border-radius:6px}}
</style>{''.join(linhas)}"""
HTML.write_text(html, encoding="utf-8")
print("ok", len(nomes))
