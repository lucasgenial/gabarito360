"""Verifica a consistência do catálogo de funcionalidades (governança, 10/11/2026).

Regras:
1. Cada pasta docs/funcionalidades/NNN-slug/ tem uma linha no catálogo, e cada linha tem uma pasta.
2. Cada pasta tem planejamento.md e casos-de-uso.md, não vazios.
3. Implementação e Etapa do cabeçalho do planejamento.md são iguais às do catálogo.
4. O plano mestre só cita FN que existem no catálogo.
5. Os valores de Implementação e Etapa pertencem às listas da governança.
"""
import re
import sys
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[1]
FUNC = RAIZ / 'docs' / 'funcionalidades'
PLANO = RAIZ / 'docs' / 'planejamento' / '00-plano-mestre.md'

IMPLEMENTACAO = {'Concluída', 'Em homologação', 'Parcial', 'Pendente', 'Adiada', 'Substituída', 'Candidata'}
ETAPA = {'Candidata', 'Priorizada', 'Em planejamento', 'Planejamento aprovado', 'Em execução',
         'Em homologação', 'Concluída', 'Adiada', 'Substituída'}

erros = []
catalogo = (FUNC / 'README.md').read_text(encoding='utf-8')
linhas = {}
for m in re.finditer(r'^\| FN-(\d{3}) \|([^|]+)\|([^|]+)\|([^|]+)\|([^|]+)\|([^|]+)\|([^|]+)\|', catalogo, re.M):
    fn = m.group(1)
    if fn in linhas:
        erros.append(f'FN-{fn} aparece mais de uma vez no catálogo')
    linhas[fn] = {'impl': m.group(5).strip(), 'etapa': m.group(6).strip(), 'docs': m.group(7)}

pastas = {p.name[:3]: p for p in FUNC.iterdir() if p.is_dir() and re.fullmatch(r'\d{3}-[a-z0-9-]+', p.name)}

for fn in sorted(set(linhas) - set(pastas)):
    erros.append(f'FN-{fn} está no catálogo sem pasta em docs/funcionalidades/')
for fn in sorted(set(pastas) - set(linhas)):
    erros.append(f'pasta {pastas[fn].name}/ sem linha no catálogo')

for fn, dados in sorted(linhas.items()):
    if dados['impl'] not in IMPLEMENTACAO:
        erros.append(f'FN-{fn}: Implementação inválida "{dados["impl"]}"')
    if dados['etapa'] not in ETAPA:
        erros.append(f'FN-{fn}: Etapa inválida "{dados["etapa"]}"')
    pasta = pastas.get(fn)
    if not pasta:
        continue
    if f'({pasta.name}/planejamento.md)' not in dados['docs'] or f'({pasta.name}/casos-de-uso.md)' not in dados['docs']:
        erros.append(f'FN-{fn}: links do catálogo não apontam para {pasta.name}/')
    for nome in ('planejamento.md', 'casos-de-uso.md'):
        arquivo = pasta / nome
        if not arquivo.is_file() or not arquivo.read_text(encoding='utf-8').strip():
            erros.append(f'{pasta.name}/{nome} ausente ou vazio')
    plan = pasta / 'planejamento.md'
    if plan.is_file():
        texto = plan.read_text(encoding='utf-8')
        impl = re.search(r'^\*\*Implementação:\*\* (.+)$', texto, re.M)
        etapa = re.search(r'^\*\*Etapa:\*\* (.+)$', texto, re.M)
        if not impl or impl.group(1).strip() != dados['impl']:
            erros.append(f'{pasta.name}/planejamento.md: Implementação difere do catálogo ("{dados["impl"]}")')
        if not etapa or etapa.group(1).strip() != dados['etapa']:
            erros.append(f'{pasta.name}/planejamento.md: Etapa difere do catálogo ("{dados["etapa"]}")')

if PLANO.is_file():
    citados = set(re.findall(r'FN-(\d{3})', PLANO.read_text(encoding='utf-8')))
    for fn in sorted(citados - set(linhas)):
        erros.append(f'plano mestre cita FN-{fn}, que não existe no catálogo')

if erros:
    print('Catálogo de funcionalidades inconsistente:')
    for e in erros:
        print('  - ' + e)
    sys.exit(1)
print(f'Catálogo consistente: {len(linhas)} funcionalidades, pastas e cabeçalhos alinhados.')
