"""
/**
 * Arquivo: apps/api/omr/gerar_imagens.py
 * Função: Gera as versões leves da foto de um cartão: a miniatura da lista e,
 *         quando os marcadores aparecem, a foto endireitada da revisão.
 *  Uso:  python3 gerar_imagens.py <imagem> <miniatura.jpg> <endireitada.jpg>
 *  Saída (stdout, JSON): {ok, miniatura, endireitada}
 */
"""
import json
import sys

import cv2

import ler_cartao as C

LARGURA_MINIATURA = 240
QUALIDADE_MINIATURA = 70
QUALIDADE_ENDIREITADA = 80


def salvar(caminho, img, qualidade):
    if not cv2.imwrite(caminho, img, [cv2.IMWRITE_JPEG_QUALITY, qualidade]):
        raise OSError(f"não foi possível gravar {caminho}")


def gerar(origem, miniatura, endireitada):
    img = cv2.imread(origem)
    if img is None:
        return {"ok": False, "erro": "imagem ilegível"}
    pts = C.achar_marcadores(cv2.cvtColor(img, cv2.COLOR_BGR2GRAY))
    base = C.retificar(img, pts) if pts is not None else img
    if pts is not None:
        salvar(endireitada, base, QUALIDADE_ENDIREITADA)
    alt = round(base.shape[0] * LARGURA_MINIATURA / base.shape[1])
    salvar(miniatura, cv2.resize(base, (LARGURA_MINIATURA, alt), interpolation=cv2.INTER_AREA), QUALIDADE_MINIATURA)
    return {"ok": True, "miniatura": True, "endireitada": pts is not None}


if __name__ == "__main__":
    try:
        r = gerar(sys.argv[1], sys.argv[2], sys.argv[3])
    except Exception as e:  # nunca deixa o PHP sem JSON
        r = {"ok": False, "erro": f"{type(e).__name__}: {e}"}
    print(json.dumps(r, ensure_ascii=False))
