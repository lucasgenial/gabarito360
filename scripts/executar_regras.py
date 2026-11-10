"""Executa todas as regras ativas de docs/decisoes/regras.yml (FN-078, ADR-0053).

A CI chama este script. Uma regra nova entra na CI quando passa a 'ativa' no registro,
sem mudar o workflow. O marcador {INTERVALO} recebe a variável de ambiente INTERVALO; {BASE} recebe o início do intervalo.
"""
import os
import shlex
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import principios as P  # noqa: E402

intervalo = os.environ.get('INTERVALO', 'origin/main..HEAD')
falhas = []
for regra in P.ler_regras():
    if regra.get('estado') != 'ativa':
        continue
    comando = str(regra['comando']).replace('{INTERVALO}', intervalo).replace('{BASE}', intervalo.split('..')[0])
    print(f'\n== {regra["id"]} ({regra.get("adr")}): {comando}', flush=True)
    partes = shlex.split(comando)
    if partes and partes[0] in ('python', 'python3'):
        partes[0] = sys.executable
    codigo = subprocess.run(partes, cwd=P.RAIZ).returncode
    if codigo != 0:
        if regra.get('gravidade') == 'avisa':
            print(f'aviso: {regra["id"]} falhou, mas a gravidade é "avisa".')
        else:
            falhas.append(regra['id'])

if falhas:
    print(f'\nRegras reprovadas: {", ".join(falhas)}')
    sys.exit(1)
print('\nTodas as regras ativas aprovadas.')
