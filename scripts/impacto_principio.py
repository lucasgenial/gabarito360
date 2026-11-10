"""Agente de impacto dos princípios (FN-078 subfase 5.1, ADR-0053).

Quando um ADR nasce ou muda, este script propaga a mudança sem ação humana:
1. sobe a versão dos princípios (docs/decisoes/VERSAO);
2. gera de novo o AGENTS.md;
3. gera de novo docs/planejamento/04-adequacoes.md: as funcionalidades afetadas e a
   subfase de adequação de cada uma (revalidar contra os ADRs que mudaram).

A subfase de adequação é obrigatória quando a funcionalidade for alterada: a regra
R-VERSAO-PRINCIPIOS-FN reprova o PR de código até a revalidação.

Uso:
  python scripts/impacto_principio.py --aplicar          # depois de criar ou mudar um ADR
  python scripts/impacto_principio.py --verificar        # CI: arquivos gerados em dia
  python scripts/impacto_principio.py --adr ADR-0053     # impacto de um ADR, sem gravar nada
"""
import re
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import gerar_agents as G  # noqa: E402
import principios as P  # noqa: E402

ADEQUACOES = P.RAIZ / 'docs' / 'planejamento' / '04-adequacoes.md'


def catalogo():
    """FN-NNN -> (nome, etapa, pasta)."""
    texto = P.CATALOGO.read_text(encoding='utf-8') if P.CATALOGO.exists() else ''
    saida = {}
    for m in re.finditer(r'^\| (FN-\d{3}) \|([^|]+)\|[^|]*\|[^|]*\|[^|]*\|([^|]+)\|.*?\]\((\d{3}-[^/)]+)/', texto, re.M):
        saida[m.group(1)] = (m.group(2).strip(), m.group(3).strip(), m.group(4))
    return saida


def conforme(pasta):
    plan = P.RAIZ / 'docs' / 'funcionalidades' / pasta / 'planejamento.md'
    if not plan.exists():
        return 0
    m = re.search(r'^\*\*Conforme princípios:\*\* versão (\d+)', plan.read_text(encoding='utf-8'), re.M)
    return int(m.group(1)) if m else 0


def _padrao(glob):
    regex = ''
    i = 0
    while i < len(glob):
        if glob.startswith('**/', i):
            regex, i = regex + '(?:.*/)?', i + 3
        elif glob.startswith('**', i):
            regex, i = regex + '.*', i + 2
        elif glob[i] == '*':
            regex, i = regex + '[^/]*', i + 1
        else:
            regex, i = regex + re.escape(glob[i]), i + 1
    return re.compile(regex + '$')


def arquivos_versionados():
    r = subprocess.run(['git', 'ls-files'], cwd=P.RAIZ, capture_output=True, text=True, encoding='utf-8')
    return r.stdout.splitlines() if r.returncode == 0 else []


def contar_arquivos(caminhos, versionados):
    padroes = [_padrao(c) for c in caminhos or [] if c != '**']
    if not padroes:
        return None  # o ADR vale para o repositório inteiro
    return sum(1 for a in versionados if any(p.match(a) for p in padroes))


def adrs_aceitos():
    versoes = P.versao_dos_adrs()
    saida = []
    for adr_id, caminho, c, erro in P.adrs():
        if erro or not c or c.get('estado') != 'Aceita':
            continue
        saida.append({'id': adr_id, 'arquivo': caminho.name, 'titulo': c.get('titulo', ''),
                      'versao': versoes.get(adr_id, 0), 'afeta': c.get('afeta_funcionalidades') or [],
                      'caminhos': c.get('afeta_caminhos') or [], 'verificacao': c.get('verificacao'),
                      'regras': c.get('regras') or []})
    return saida


def afeta(adr, fn):
    return fn in adr['afeta'] or 'todas' in adr['afeta']


def pendencias():
    """Lista (fn, nome, etapa, pasta, conforme, [adrs pendentes]) das FNs com adequação pendente."""
    adrs = adrs_aceitos()
    saida = []
    for fn, (nome, etapa, pasta) in sorted(catalogo().items()):
        if etapa == 'Substituída':
            continue
        n = conforme(pasta)
        lista = [a for a in adrs if afeta(a, fn) and a['versao'] > n]
        if lista:
            saida.append((fn, nome, etapa, pasta, n, lista))
    return saida


def gerar_adequacoes():
    versao = P.ler_versao()[0]
    lista = pendencias()
    versionados = arquivos_versionados()
    adrs_pendentes = {}
    for *_, adrs in lista:
        for a in adrs:
            adrs_pendentes.setdefault(a['id'], a)
    linhas = [
        '<!-- Arquivo gerado por scripts/impacto_principio.py. Não edite à mão. -->',
        '# Adequações pendentes aos princípios',
        '',
        f'**Versão dos princípios:** {versao}',
        '',
        'Este documento lista as funcionalidades que precisam de revalidação, porque um ADR que as afeta mudou depois da '
        'versão registrada no campo "Conforme princípios" do `planejamento.md` ([ADR-0053](../decisoes/0053-principios-executaveis-e-mutaveis.md)).',
        '',
        'Como usar:',
        '',
        '1. Antes de alterar o código de uma funcionalidade, faça a subfase de adequação dela.',
        '2. Leia os ADRs listados. Registre no `planejamento.md` as mudanças que eles exigem.',
        f'3. Suba "Conforme princípios" para a versão {versao}. A regra `R-VERSAO-PRINCIPIOS-FN` reprova o PR de código sem esse passo.',
        '4. Rode `python scripts/impacto_principio.py --aplicar`. A funcionalidade sai desta lista.',
        '',
        '## 1. ADRs com adequação pendente',
        '',
    ]
    if not adrs_pendentes:
        linhas += ['Nenhuma adequação pendente.', '']
    else:
        linhas += ['| ADR | Versão | Título | Verificação | Funcionalidades pendentes | Arquivos no escopo |', '|---|---|---|---|---|---|']
        for a in sorted(adrs_pendentes.values(), key=lambda x: (x['versao'], x['id'])):
            qtd = sum(1 for *_, adrs in lista if any(x['id'] == a['id'] for x in adrs))
            arquivos = contar_arquivos(a['caminhos'], versionados)
            escopo = 'repositório inteiro' if arquivos is None else str(arquivos)
            regras = f' ({", ".join(a["regras"])})' if a['regras'] else ''
            linhas.append(f'| [{a["id"]}](../decisoes/{a["arquivo"]}) | {a["versao"]} | {a["titulo"]} | '
                          f'{a["verificacao"]}{regras} | {qtd} | {escopo} |')
        linhas.append('')
    linhas += ['## 2. Subfases de adequação por funcionalidade', '']
    if not lista:
        linhas += ['Nenhuma funcionalidade pendente.', '']
    else:
        linhas += [f'{len(lista)} funcionalidade(s) com adequação pendente.', '',
                   '| FN | Funcionalidade | Etapa | Conforme | Subfase de adequação |', '|---|---|---|---|---|']
        for fn, nome, etapa, pasta, n, adrs in lista:
            ids = ', '.join(a['id'] for a in adrs)
            linhas.append(f'| [{fn}](../funcionalidades/{pasta}/planejamento.md) | {nome} | {etapa} | versão {n} | '
                          f'Revalidar contra {ids} e subir para a versão {versao} |')
        linhas.append('')
    return '\n'.join(linhas)


def impacto_de(adr_id):
    adr = next((a for a in adrs_aceitos() if a['id'] == adr_id), None)
    if not adr:
        raise SystemExit(f'{adr_id} não existe ou não está aceito.')
    cat = catalogo()
    fns = sorted(fn for fn in cat if afeta(adr, fn) and cat[fn][1] != 'Substituída')
    pend = [fn for fn in fns if conforme(cat[fn][2]) < adr['versao']]
    arquivos = contar_arquivos(adr['caminhos'], arquivos_versionados())
    print(f'{adr_id} (versão {adr["versao"]}): {adr["titulo"]}')
    print(f'  Verificação: {adr["verificacao"]}; regras: {", ".join(adr["regras"]) or "nenhuma"}')
    print(f'  Arquivos no escopo: {"repositório inteiro" if arquivos is None else arquivos}')
    print(f'  Funcionalidades afetadas: {len(fns)}; com adequação pendente: {len(pend)}')
    for fn in pend:
        print(f'    - {fn} {cat[fn][0]} (conforme versão {conforme(cat[fn][2])})')


def ler(p):
    return p.read_text(encoding='utf-8').replace('\r\n', '\n') if p.exists() else ''


if __name__ == '__main__':
    args = sys.argv[1:]
    if '--adr' in args:
        impacto_de(args[args.index('--adr') + 1])
    elif '--aplicar' in args:
        antes, depois = P.atualizar_versao()
        P.AGENTS.write_text(G.gerar(), encoding='utf-8', newline='\n')
        ADEQUACOES.parent.mkdir(parents=True, exist_ok=True)
        ADEQUACOES.write_text(gerar_adequacoes(), encoding='utf-8', newline='\n')
        print(f'Versão dos princípios: {antes} -> {depois}. AGENTS.md e {ADEQUACOES.relative_to(P.RAIZ).as_posix()} gerados.')
        print(f'{len(pendencias())} funcionalidade(s) com adequação pendente.')
    elif '--verificar' in args:
        erros = []
        _, digital, por_adr = P.ler_versao()
        if digital != P.impressao_digital() or {a: h for a, (_, h) in por_adr.items()} != P.digitais_por_adr():
            erros.append('um ADR mudou e a versão dos princípios não subiu')
        if ler(ADEQUACOES) != gerar_adequacoes():
            erros.append('docs/planejamento/04-adequacoes.md desatualizado')
        if ler(P.AGENTS) != G.gerar():
            erros.append('AGENTS.md desatualizado')
        if erros:
            print('Impacto dos princípios não propagado: ' + '; '.join(erros) + '.')
            print('Rode: python scripts/impacto_principio.py --aplicar')
            sys.exit(1)
        print(f'Impacto dos princípios propagado: {len(pendencias())} funcionalidade(s) com adequação pendente, listadas em docs/planejamento/04-adequacoes.md.')
    else:
        print(gerar_adequacoes())
