"""Verifica a estrutura da documentação (docs/README.md).

Regras:
1. Todo link relativo em Markdown aponta para um arquivo ou pasta que existe.
2. Nenhum link aponta para um documento antigo numerado (docs/NN-*.md), que não faz parte da estrutura.
3. Com --estrito: nenhum arquivo Markdown existe fora das pastas e arquivos permitidos.

Uso: python scripts/verificar_documentacao.py [--estrito]
"""
import re
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[1]
DOCS = RAIZ / 'docs'
PERMITIDOS = ('visao-geral', 'arquitetura', 'funcionalidades', 'planejamento', 'decisoes', 'operacao', 'historico')
ARQUIVOS_RAIZ = {'README.md', 'glossario.md'}
ANTIGO = re.compile(r'(^|/)(\d{2,3}-[^/]*\.md|tu1?-[^/]*\.md)$')
LINK = re.compile(r'\]\(([^)\s]+)\)')

estrito = '--estrito' in sys.argv
erros = []

def documentos():
    for p in sorted(DOCS.rglob('*.md')):
        rel = p.relative_to(DOCS)
        if rel.parts[0] in PERMITIDOS or (len(rel.parts) == 1 and rel.name in ARQUIVOS_RAIZ):
            yield p, True
        else:
            yield p, False

extras = [RAIZ / 'AGENTS.md', RAIZ / 'CLAUDE.md', RAIZ / 'README.md']
for p, permitido in list(documentos()) + [(e, True) for e in extras if e.exists()]:
    if not permitido:
        if estrito:
            erros.append(f'{p.relative_to(RAIZ)}: documento fora da estrutura de docs/')
        continue
    texto = p.read_text(encoding='utf-8')
    for alvo in LINK.findall(texto):
        if re.match(r'^[a-z]+:', alvo) or alvo.startswith('#'):
            continue
        caminho = alvo.split('#', 1)[0]
        if not caminho:
            continue
        destino = (p.parent / caminho).resolve()
        rel = p.relative_to(RAIZ)
        if ANTIGO.search(caminho) and DOCS in destino.parents and destino.parent == DOCS:
            erros.append(f'{rel}: link para documento antigo {alvo}')
        elif not destino.exists():
            erros.append(f'{rel}: link quebrado {alvo}')

if erros:
    print(f'Documentação com {len(erros)} problema(s):')
    for e in erros:
        print('  - ' + e)
    sys.exit(1)
print('Documentação consistente: links resolvidos' + (' e estrutura respeitada.' if estrito else '.'))
