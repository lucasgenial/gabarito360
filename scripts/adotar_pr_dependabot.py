"""Adota um PR do Dependabot em nome do responsável (FN-078 subfase 4.2, ADR-0051).

O PR do robô nunca entra na main, porque o autor dele é dependabot[bot].
Este script refaz a mesma mudança num commit do responsável e abre um PR novo:
1. confere que o PR é do Dependabot e só altera arquivos de dependências;
2. cria a branch deps/pr-<número> a partir da origin/main e aplica a mudança;
3. faz o commit com a identidade do git local (o responsável) e envia a branch;
4. abre o PR novo e comenta no PR do robô. O Dependabot fecha o PR dele sozinho
   depois do merge, porque a dependência já estará atualizada.

Uso:
  python scripts/adotar_pr_dependabot.py <número do PR do Dependabot>
"""
import json
import re
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from dependencias import so_dependencias  # noqa: E402

RAIZ = Path(__file__).resolve().parents[1]
ROBO = re.compile(r'\[bot\]|dependabot', re.I)


def rodar(*args, entrada=None):
    r = subprocess.run(args, cwd=RAIZ, capture_output=True, input=entrada,
                       text=entrada is None or isinstance(entrada, str), encoding=None if isinstance(entrada, bytes) else 'utf-8')
    if r.returncode != 0:
        erro = r.stderr if isinstance(r.stderr, str) else r.stderr.decode('utf-8', 'replace')
        raise SystemExit(f'Falhou: {" ".join(args)}\n{erro.strip()}')
    return r.stdout


def titulo_em_portugues(titulo):
    titulo = re.sub(r'^chore\(deps(-dev)?\):\s*', '', titulo).strip()
    grupo = ''
    g = re.search(r' in the (\S+) group(?: across \d+ director(?:y|ies))?$', titulo)
    if g:
        grupo, titulo = f' (grupo {g.group(1)})', titulo[:g.start()]
    m = re.match(r'(?:[Bb]ump|[Uu]pdate) (\S+?)(?: requirement)? from (\S+) to (\S+)(?: in (\S+))?$', titulo)
    if m:
        local = f' em {m.group(4)}' if m.group(4) else ''
        return f'chore(deps): atualiza {m.group(1)} de {m.group(2)} para {m.group(3)}{local}{grupo}'
    m = re.match(r'[Bb]ump the (\S+) group(?: across \d+ director(?:y|ies))?(?: in (\S+))? with (\d+) updates?', titulo)
    if m:
        local = f' em {m.group(2)}' if m.group(2) else ''
        return f'chore(deps): atualiza o grupo {m.group(1)}{local} ({m.group(3)} pacote(s))'
    return 'chore(deps): ' + titulo


def main(numero):
    pr = json.loads(rodar('gh', 'pr', 'view', numero, '--json', 'author,title,body,state,files,url'))
    if pr['state'] != 'OPEN':
        raise SystemExit(f'O PR #{numero} não está aberto.')
    if not ROBO.search(pr['author']['login']):
        raise SystemExit(f'O PR #{numero} não é do Dependabot (autor: {pr["author"]["login"]}).')
    arquivos = [f['path'] for f in pr['files']]
    if not so_dependencias(arquivos):
        fora = [a for a in arquivos if not so_dependencias([a])]
        raise SystemExit('O PR altera arquivos que não são de dependências: ' + ', '.join(fora))
    nome, email = rodar('git', 'config', 'user.name').strip(), rodar('git', 'config', 'user.email').strip()
    if ROBO.search(f'{nome} {email}') or not nome or not email:
        raise SystemExit(f'A identidade do git local não é a do responsável: {nome} <{email}>.')

    rodar('git', 'fetch', '-q', 'origin', 'main', f'pull/{numero}/head')
    diff = subprocess.run(['git', 'diff', '--binary', 'origin/main...FETCH_HEAD'], cwd=RAIZ,
                          capture_output=True, check=True).stdout
    if not diff.strip():
        raise SystemExit(f'O PR #{numero} não tem diferença em relação à main.')
    branch = f'deps/pr-{numero}'
    rodar('git', 'switch', '-c', branch, 'origin/main')
    rodar('git', 'apply', '--index', '-', entrada=diff)
    titulo = titulo_em_portugues(pr['title'])
    mensagem = (f'{titulo}\n\nAdota o PR #{numero} do Dependabot. A mudança é a mesma; '
                'o commit é do responsável (ADR-0051).\n')
    rodar('git', 'commit', '-q', '-F', '-', entrada=mensagem)
    rodar('git', 'push', '-q', '-u', 'origin', branch)
    corpo = (f'## Dependências\n\nAdota o PR #{numero} do Dependabot ({pr["url"]}). A mudança é a mesma; '
             'o commit é do responsável (ADR-0051).\n\nArquivos:\n' + ''.join(f'- `{a}`\n' for a in arquivos) +
             '\nSe um pacote de build mudou, rode o build dele e versione o resultado (a CI confere).\n')
    novo = rodar('gh', 'pr', 'create', '--base', 'main', '--head', branch, '--title', titulo,
                 '--body', corpo, '--label', 'dependencias').strip()
    rodar('gh', 'pr', 'comment', numero, '--body', f'Adotado em {novo}. Este PR fecha sozinho depois do merge.')
    print(f'PR adotado: {novo}')


if __name__ == '__main__':
    if len(sys.argv) != 2 or not sys.argv[1].isdigit():
        print(__doc__)
        sys.exit(2)
    main(sys.argv[1])
