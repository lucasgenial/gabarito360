"""
/**
 * Arquivo: icones/familia/gerar_fontes.py
 * Função: junta os desenhos de lotes/loteN.py e grava os SVGs de traço (fonte única).
 *   python3 gerar_fontes.py                 -> todos os lotes em src/ (uso do coordenador)
 *   python3 gerar_fontes.py --lote 3 DIR    -> só o lote 3, em DIR (uso dos agentes)
 *
 * Classes de construção (lidas por compilar.py):
 *   corpo  -> contorno: traço | preenchido: forma cheia (preenchimento + traço)
 *   (nada) -> detalhe: igual nos dois; no preenchido vira recorte dentro do corpo (XOR)
 *   fixo   -> igual nos dois; nunca é recortado
 *   sc     -> só no contorno
 *   sp     -> só no preenchido, como corpo
 *   folga  -> área retirada de tudo (abre espaço para o selo); não é desenhada
 *   selo   -> desenhado por último, igual nos dois, depois do recorte da folga
 */
"""
import argparse
import importlib
import sys
from pathlib import Path

BASE = Path(__file__).parent
sys.path.insert(0, str(BASE))

CAB = ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
       'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">')


def carregar(numeros=None):
    icones, origem = {}, {}
    for arq in sorted((BASE / "lotes").glob("lote*.py")):
        n = int(arq.stem.replace("lote", ""))
        if numeros and n not in numeros:
            continue
        mod = importlib.import_module(f"lotes.{arq.stem}")
        for nome, corpo in mod.ICONES.items():
            if nome in icones:
                raise SystemExit(f"id repetido: {nome} (lotes {origem[nome]} e {n})")
            icones[nome], origem[nome] = corpo, n
    return icones


def gravar(icones, destino):
    destino.mkdir(parents=True, exist_ok=True)
    for nome, corpo in icones.items():
        (destino / f"{nome}.svg").write_text(CAB + corpo.strip() + "</svg>\n", encoding="utf-8")


if __name__ == "__main__":
    ap = argparse.ArgumentParser()
    ap.add_argument("--lote", type=int, action="append")
    ap.add_argument("destino", nargs="?", default=str(BASE / "src"))
    a = ap.parse_args()
    ic = carregar(a.lote)
    gravar(ic, Path(a.destino))
    print(len(ic), "fontes gravadas em", a.destino)
