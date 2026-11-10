"""Verifica a estrutura da documentação (docs/README.md).

Regras:
1. Todo link relativo em Markdown aponta para um arquivo ou pasta que existe.
2. Nenhum link aponta para um documento antigo numerado (docs/NN-*.md), que não faz parte da estrutura.
3. Com --estrito: nenhum arquivo Markdown existe fora das pastas e arquivos permitidos.
4. Os modelos de PR e de issue em .github/ existem e têm os campos da governança (FN-078 4.1).

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

# Modelos de PR e de issue (FN-078 subfase 4.1).
GITHUB = RAIZ / '.github'
MODELO_PR = GITHUB / 'pull_request_template.md'
if not MODELO_PR.exists():
    erros.append('.github/pull_request_template.md ausente')
else:
    texto = MODELO_PR.read_text(encoding='utf-8')
    for trecho in ('FN: FN-NNN', 'Conforme princípios', 'ADR-0049', 'ADR-0051'):
        if trecho not in texto:
            erros.append(f'.github/pull_request_template.md sem "{trecho}"')
MODELOS_ISSUE = GITHUB / 'ISSUE_TEMPLATE'
for nome, rotulo in (('1-funcionalidade-candidata.yml', 'funcionalidade-candidata'), ('2-correcao.yml', 'correcao'),
                     ('3-decisao-do-responsavel.yml', 'decisao-do-responsavel')):
    p = MODELOS_ISSUE / nome
    if not p.exists():
        erros.append(f'.github/ISSUE_TEMPLATE/{nome} ausente')
        continue
    texto = p.read_text(encoding='utf-8')
    for campo in ('name:', 'description:', 'body:'):
        if not re.search(rf'^{campo}', texto, re.M):
            erros.append(f'.github/ISSUE_TEMPLATE/{nome} sem "{campo}"')
    if f'"{rotulo}"' not in texto:
        erros.append(f'.github/ISSUE_TEMPLATE/{nome} sem o rótulo "{rotulo}"')
    if '\t' in texto:
        erros.append(f'.github/ISSUE_TEMPLATE/{nome} com tabulação (YAML exige espaços)')
config = MODELOS_ISSUE / 'config.yml'
if not config.exists() or not re.search(r'^blank_issues_enabled:\s*false\s*$', config.read_text(encoding='utf-8'), re.M):
    erros.append('.github/ISSUE_TEMPLATE/config.yml deve ter "blank_issues_enabled: false"')

if erros:
    print(f'Documentação com {len(erros)} problema(s):')
    for e in erros:
        print('  - ' + e)
    sys.exit(1)
print('Documentação consistente: links resolvidos' + (' e estrutura respeitada.' if estrito else '.'))
