"""Verifica o vínculo código ↔ FN ↔ documentação (FN-078 subfase 3.3, regra R-FN-NO-PR, ADR-0053).

Uma mudança em apps/ precisa:
1. citar pelo menos uma funcionalidade (FN-NNN) existente no catálogo, no título ou na
   descrição do PR (variáveis PR_TITULO e PR_CORPO) ou numa mensagem de commit do intervalo;
2. alterar o planejamento.md de pelo menos uma das FNs citadas.
Exceção: um PR que só altera arquivos de dependências (scripts/dependencias.py).

Uso:
  python scripts/verificar_fn_no_pr.py <base>..<head>     # padrão: origin/main..HEAD
"""
import os
import re
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dependencias import so_dependencias  # noqa: E402

RAIZ = Path(os.environ.get('PRINCIPIOS_RAIZ') or Path(__file__).resolve().parents[1])
CATALOGO = RAIZ / 'docs' / 'funcionalidades' / 'README.md'


def git(*args):
    r = subprocess.run(['git', *args], cwd=RAIZ, capture_output=True, text=True, encoding='utf-8')
    if r.returncode != 0:
        raise SystemExit(f'git {" ".join(args)} falhou: {r.stderr.strip()}')
    return r.stdout


def pastas_das_fns():
    """FN-NNN -> pasta em docs/funcionalidades/ (só as FNs do catálogo)."""
    texto = CATALOGO.read_text(encoding='utf-8') if CATALOGO.exists() else ''
    catalogo = set(re.findall(r'^\| (FN-\d{3}) \|', texto, re.M))
    pastas = {}
    for p in (RAIZ / 'docs' / 'funcionalidades').glob('[0-9][0-9][0-9]-*'):
        fn = f'FN-{p.name[:3]}'
        if fn in catalogo:
            pastas[fn] = f'docs/funcionalidades/{p.name}'
    return pastas


def verificar(intervalo):
    base, _, topo = intervalo.partition('..')
    topo = topo.lstrip('.') or 'HEAD'
    alterados = [l for l in git('diff', '--name-only', f'{base}...{topo}').splitlines() if l]
    codigo = [a for a in alterados if a.startswith('apps/')]
    if not codigo:
        return [], 'nenhuma mudança em apps/'
    if so_dependencias(alterados):
        return [], 'só arquivos de dependências (manutenção, sem FN)'
    textos = ' '.join([os.environ.get('PR_TITULO', ''), os.environ.get('PR_CORPO', ''),
                       git('log', '--format=%B', f'{base}..{topo}')])
    citadas = sorted(set(re.findall(r'\bFN-\d{3}\b', textos)))
    pastas = pastas_das_fns()
    erros = []
    validas = [fn for fn in citadas if fn in pastas]
    for fn in citadas:
        if fn not in pastas:
            erros.append(f'{fn} citada, mas não existe no catálogo de funcionalidades')
    if not validas:
        erros.append(f'{len(codigo)} arquivo(s) em apps/ alterado(s) sem FN citada. '
                     'Escreva "FN: FN-NNN" na descrição do PR ou na mensagem do commit.')
        return erros, None
    atualizadas = [fn for fn in validas if f'{pastas[fn]}/planejamento.md' in alterados]
    if not atualizadas:
        erros.append('nenhum planejamento das FNs citadas foi atualizado. Atualize: '
                     + ', '.join(f'{pastas[fn]}/planejamento.md' for fn in validas))
    return erros, f'{len(codigo)} arquivo(s) em apps/; FN citada e planejamento atualizado: {", ".join(atualizadas)}'


if __name__ == '__main__':
    intervalo = sys.argv[1] if len(sys.argv) > 1 else 'origin/main..HEAD'
    erros, resumo = verificar(intervalo)
    if erros:
        print('Vínculo código ↔ FN ↔ documentação reprovado (R-FN-NO-PR):')
        for e in erros:
            print('  - ' + e)
        sys.exit(1)
    print(f'Vínculo código ↔ FN ↔ documentação aprovado: {resumo}.')
