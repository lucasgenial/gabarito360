"""
/**
 * Arquivo: icones/familia/gerar_css.py
 * Função: gera fonte/g360-icones.css a partir de registro.json.
 *         Variantes: contorno (padrão), preenchido e duotônico (preenchido claro sob contorno forte).
 *         Cores só por token do app; nenhuma cor fixa.
 */
"""
import json
from pathlib import Path

BASE = Path(__file__).parent
reg = json.loads((BASE / "registro.json").read_text(encoding="utf-8"))

# Tons do duotônico: (traço, fundo). Fundo usa o token "-light" quando existe;
# o laranja não tem token claro, então o fundo sai do próprio token por color-mix.
TONS = {
    "azul": ("var(--accent)", "var(--accent-light)"),
    "verde": ("var(--texto-sucesso)", "var(--success-light)"),
    "vermelho": ("var(--texto-erro)", "var(--danger-light)"),
    "amarelo": ("var(--warn-fg)", "var(--warn-light)"),
    "laranja": ("var(--serie-5)", "color-mix(in srgb, var(--serie-5) 16%, transparent)"),
    "lilas": ("var(--purple-fg)", "var(--purple-light)"),
    "marca": ("var(--g360-blue)", "color-mix(in srgb, var(--g360-blue) 14%, transparent)"),
}

css = ["""/**
 * Arquivo: public/css/g360-icones.css
 * Função: família de ícones própria do Gabarito360 (fonte). Gerado por gerar_css.py; não editar à mão.
 * Uso: <span class="g360i g360i-cobertura-plano" aria-hidden="true"></span>
 *      variantes: g360i--preenchido | g360i--duo (+ tom: g360i--azul, --verde, --vermelho, --amarelo, --laranja, --lilas, --marca)
 */
@font-face{font-family:"G360 Icones";src:url("../fonts/icones/g360-icones.woff2") format("woff2");font-display:block}
@font-face{font-family:"G360 Icones Preenchido";src:url("../fonts/icones/g360-icones-preenchido.woff2") format("woff2");font-display:block}

.g360i{--icone-1:currentColor;--icone-2:transparent;position:relative;display:inline-block;width:1em;height:1em;
  font-family:"G360 Icones";font-style:normal;font-weight:400;font-size:1.5rem;line-height:1;vertical-align:-0.125em;
  color:var(--icone-1);speak:never;text-transform:none;-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale;flex-shrink:0}
.g360i::before{content:var(--g);display:block}
.g360i--preenchido{font-family:"G360 Icones Preenchido"}

/* Duotônico: ::before = preenchido no tom claro, ::after = contorno no tom forte, sobrepostos */
.g360i--duo{--icone-2:color-mix(in srgb, currentColor 16%, transparent)}
.g360i--duo::before{font-family:"G360 Icones Preenchido";color:var(--icone-2)}
.g360i--duo::after{content:var(--g);position:absolute;inset:0;font-family:"G360 Icones"}
"""]
for tom, (forte, claro) in TONS.items():
    css.append(f".g360i--{tom}{{--icone-1:{forte};--icone-2:{claro};color:var(--icone-1)}}")
css.append("\n/* Glifos (códigos fixos: ver registro.json) */")
for nome, cod in reg.items():
    css.append(f'.g360i-{nome}{{--g:"\\{cod:x}"}}')

(BASE / "fonte" / "g360-icones.css").write_text("\n".join(css) + "\n", encoding="utf-8")
print("css ok:", len(reg), "glifos,", len(TONS), "tons")
