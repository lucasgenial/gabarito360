"""
/**
 * Arquivo: icones/familia/compilar.py
 * Função: converte src/*.svg (traço) nos contornos das duas variantes,
 *         saida/contorno/*.svg e saida/preenchido/*.svg, prontos para virar glifo.
 *         Mantém o registro fixo de códigos (registro.json): ícone novo entra no fim,
 *         nenhum código antigo muda.
 */
"""
import json
import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path

import pathops
from picosvg.svg import SVG
from picosvg.svg_pathops import skia_path, svg_commands
from picosvg.svg_types import SVGPath

BASE = Path(__file__).parent
SRC, OUT = BASE / "src", BASE / "saida"
NS = "http://www.w3.org/2000/svg"
ET.register_namespace("", NS)
PRIMEIRO_CODIGO = 0xE001


def contornar(elementos, raiz_attrs, encher=False):
    """Converte elementos SVG (traço e/ou preenchimento) num único caminho skia, regra nonzero."""
    if not elementos:
        return pathops.Path()
    partes = []
    for el in elementos:
        el = ET.fromstring(ET.tostring(el))
        el.attrib.pop("class", None)
        if encher and el.get("fill", "none") == "none":
            el.set("fill", "#000")
        partes.append(ET.tostring(el, encoding="unicode"))
    attrs = " ".join(f'{k}="{v}"' for k, v in raiz_attrs.items() if k != "xmlns")
    texto = f'<svg xmlns="{NS}" {attrs}>{"".join(partes)}</svg>'
    texto = texto.replace("currentColor", "#000").replace(f' xmlns:ns0="{NS}"', "").replace("ns0:", "")
    pico = SVG.fromstring(texto).topicosvg()
    caminho = pathops.Path()
    for forma in pico.shapes():
        sk = skia_path(forma.as_cmd_seq(), forma.fill_rule)
        caminho = pathops.op(caminho, sk, pathops.PathOp.UNION)
    return caminho


def gravar(caminho, destino):
    cmds = list(svg_commands(caminho))
    d = SVGPath.from_commands(cmds).d
    d = re.sub(r"(\d+\.\d{3})\d+", r"\1", d)
    destino.write_text(f'<svg xmlns="{NS}" viewBox="0 0 24 24"><path d="{d}"/></svg>\n', encoding="utf-8")


def compilar(arquivo, saida):
    raiz = ET.parse(arquivo).getroot()
    raiz_attrs = {k: v for k, v in raiz.attrib.items()}
    grupos = {"corpo": [], "detalhe": [], "fixo": [], "sc": [], "sp": [], "folga": [], "selo": []}
    for el in list(raiz):
        grupos[el.get("class", "detalhe")].append(el)

    folga = contornar(grupos["folga"], raiz_attrs, encher=True)
    selo = contornar(grupos["selo"], raiz_attrs)
    contorno = contornar(grupos["corpo"] + grupos["detalhe"] + grupos["fixo"] + grupos["sc"], raiz_attrs)
    contorno = pathops.op(pathops.op(contorno, folga, pathops.PathOp.DIFFERENCE), selo, pathops.PathOp.UNION)

    cheio = contornar(grupos["corpo"] + grupos["sp"], raiz_attrs, encher=True)
    recorte = contornar(grupos["detalhe"], raiz_attrs)
    preenchido = pathops.op(cheio, recorte, pathops.PathOp.XOR)
    preenchido = pathops.op(preenchido, contornar(grupos["fixo"], raiz_attrs), pathops.PathOp.UNION)
    preenchido = pathops.op(pathops.op(preenchido, folga, pathops.PathOp.DIFFERENCE), selo, pathops.PathOp.UNION)

    gravar(contorno, saida / "contorno" / arquivo.name)
    gravar(preenchido, saida / "preenchido" / arquivo.name)


def atualizar_registro(nomes):
    reg_arq = BASE / "registro.json"
    reg = json.loads(reg_arq.read_text(encoding="utf-8")) if reg_arq.exists() else {}
    proximo = max(reg.values(), default=PRIMEIRO_CODIGO - 1) + 1
    for nome in nomes:
        if nome not in reg:
            reg[nome] = proximo
            proximo += 1
    reg_arq.write_text(json.dumps(reg, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
    return reg


if __name__ == "__main__":
    import argparse
    ap = argparse.ArgumentParser()
    ap.add_argument("--src", default=str(SRC))
    ap.add_argument("--saida", default=str(OUT))
    ap.add_argument("--sem-registro", action="store_true", help="agentes: nunca tocar no registro de códigos")
    a = ap.parse_args()
    src, saida = Path(a.src), Path(a.saida)
    for v in ("contorno", "preenchido"):
        (saida / v).mkdir(parents=True, exist_ok=True)
    arquivos = sorted(src.glob("*.svg"))
    for arq in arquivos:
        try:
            compilar(arq, saida)
        except Exception as e:  # noqa: BLE001
            print("ERRO", arq.name, repr(e), file=sys.stderr)
            raise
    if a.sem_registro:
        print(len(arquivos), "ícones compilados (registro intocado)")
    else:
        reg = atualizar_registro([x.stem for x in arquivos])
        print(len(arquivos), "ícones compilados;", len(reg), "códigos no registro")
