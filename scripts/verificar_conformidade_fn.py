"""Revalidação da funcionalidade contra os princípios vigentes (FN-078 subfase 3.4, regra R-VERSAO-PRINCIPIOS-FN, ADR-0053).

Cada planejamento.md declara "**Conforme princípios:** versão N": a versão dos princípios
contra a qual a funcionalidade foi revalidada pela última vez.

Um PR que altera apps/ e cita uma FN reprova quando um ADR aceito que afeta essa FN
(afeta_funcionalidades com a FN ou "todas") mudou depois da versão N. Para aprovar:
1. revalide o planejamento contra os ADRs listados e registre as adequações pendentes;
2. suba o campo para a versão atual (docs/decisoes/VERSAO).

Uso:
  python scripts/verificar_conformidade_fn.py <base>..<head>     # padrão: origin/main..HEAD
"""
import os
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import principios as P  # noqa: E402
import verificar_fn_no_pr as V  # noqa: E402


def conforme(pasta):
    plan = P.RAIZ / pasta / 'planejamento.md'
    m = re.search(r'^\*\*Conforme princípios:\*\* versão (\d+)', plan.read_text(encoding='utf-8'), re.M) if plan.exists() else None
    return int(m.group(1)) if m else 0


def pendentes(fn, desde):
    """ADRs aceitos que afetam a FN e mudaram depois da versão 'desde'."""
    versoes = P.versao_dos_adrs()
    saida = []
    for adr_id, _, c, erro in P.adrs():
        if erro or not c or c.get('estado') != 'Aceita':
            continue
        afeta = c.get('afeta_funcionalidades') or []
        if fn in afeta or 'todas' in afeta:
            v = versoes.get(adr_id, 0)
            if v > desde:
                saida.append((adr_id, v, c.get('titulo', '')))
    return saida


def verificar(intervalo):
    base, _, topo = intervalo.partition('..')
    topo = topo.lstrip('.') or 'HEAD'
    alterados = [l for l in V.git('diff', '--name-only', f'{base}...{topo}').splitlines() if l]
    if not any(a.startswith('apps/') for a in alterados):
        return [], 'nenhuma mudança em apps/'
    textos = ' '.join([os.environ.get('PR_TITULO', ''), os.environ.get('PR_CORPO', ''),
                       V.git('log', '--format=%B', f'{base}..{topo}')])
    pastas = V.pastas_das_fns()
    citadas = sorted(fn for fn in set(re.findall(r'\bFN-\d{3}\b', textos)) if fn in pastas)
    if not citadas:
        return [], 'nenhuma FN citada (a regra R-FN-NO-PR trata esse caso)'
    atual = P.ler_versao()[0]
    erros = []
    for fn in citadas:
        n = conforme(pastas[fn])
        lista = pendentes(fn, n)
        if lista:
            adrs = '; '.join(f'{a} (versão {v}): {t}' for a, v, t in lista)
            erros.append(f'{fn} está conforme a versão {n}, mas mudaram depois: {adrs}. '
                         f'Revalide {pastas[fn]}/planejamento.md e suba "Conforme princípios" para a versão {atual}.')
    return erros, f'{", ".join(citadas)} conforme(s) aos princípios vigentes (versão {atual})'


if __name__ == '__main__':
    intervalo = sys.argv[1] if len(sys.argv) > 1 else 'origin/main..HEAD'
    erros, resumo = verificar(intervalo)
    if erros:
        print('Funcionalidade sem revalidação contra os princípios vigentes (R-VERSAO-PRINCIPIOS-FN):')
        for e in erros:
            print('  - ' + e)
        sys.exit(1)
    print(f'Revalidação de princípios aprovada: {resumo}.')
