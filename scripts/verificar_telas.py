#!/usr/bin/env python3
"""Checa os <script> das telas Blade: sem componente <x-...> e com JavaScript válido.

Uso: python scripts/verificar_telas.py [pasta das views]
"""
import pathlib
import re
import subprocess
import sys
import tempfile

RAIZ = pathlib.Path(sys.argv[1] if len(sys.argv) > 1 else "apps/web/resources/views")
SCRIPT = re.compile(r"<script(?![^>]*\bsrc=)[^>]*>(.*?)</script>", re.S)
ECO = re.compile(r"\{\{.*?\}\}|\{!!.*?!!\}", re.S)
JSON = re.compile(r"@json\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\)", re.S)
DIRETIVA = re.compile(r"^\s*@\w+.*$", re.M)


def linha(texto, pos):
    return texto.count("\n", 0, pos) + 1


def neutro(m):
    return "0" + "\n" * m.group(0).count("\n")


def como_js(trecho):
    """Troca o que é do Blade por um valor neutro, mantendo as linhas."""
    return DIRETIVA.sub("", JSON.sub(neutro, ECO.sub(neutro, trecho)))


erros = total = 0
for arquivo in sorted(RAIZ.rglob("*.blade.php")):
    texto = arquivo.read_text(encoding="utf-8")
    for m in SCRIPT.finditer(texto):
        total += 1
        corpo = m.group(1)
        inicio = linha(texto, m.start(1))
        comp = re.search(r"<x-[\w.-]+", corpo)
        if comp:
            erros += 1
            print(f"{arquivo}:{inicio + corpo.count(chr(10), 0, comp.start())}: componente Blade dentro de JavaScript: {comp.group(0)}")
            continue
        with tempfile.NamedTemporaryFile("w", suffix=".js", delete=False, encoding="utf-8") as t:
            t.write(como_js(corpo))
        r = subprocess.run(["node", "--check", t.name], capture_output=True, text=True, encoding="utf-8")
        pathlib.Path(t.name).unlink()
        if r.returncode:
            erros += 1
            motivo = next((l for l in r.stderr.splitlines() if "Error" in l), "erro de sintaxe")
            print(f"{arquivo}:{inicio}: {motivo}")

print(f"scripts: {total} erros: {erros}")
sys.exit(1 if erros else 0)
