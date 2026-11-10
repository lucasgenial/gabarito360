"""Verifica as regras de código dos princípios (FN-078 subfases 3.1 e 3.2, ADR-0053).

Regras:
  R-JS-EXTERNO          ADR-0023  sem atributos on* nem <script> inline com código nas views
  R-SEM-DIALOGO-NATIVO  ADR-0025  sem confirm(), alert() ou prompt() nas views e no JavaScript próprio
  R-API-SO-JSON         ADR-0001  a API não renderiza views (e-mails são permitidos)
  R-TABELA-PADRAO       ADR-0022  toda <table> das views fica dentro de <x-tabela>
  R-VERSAO-RODAPE       ADR-0038  versão só em apps/web/config/versao.php; rodapé no formato aprovado

Linha de base (docs/decisoes/linha-de-base.json): as violações conhecidas em 10/11/2026.
- Uma violação fora da linha de base reprova.
- Uma violação corrigida exige tirar a entrada da linha de base (rode --atualizar-linha-de-base).
- Com --base REF, a linha de base não pode crescer em relação a REF.

Uso:
  python scripts/verificar_regras_codigo.py R-JS-EXTERNO [--base origin/main]
  python scripts/verificar_regras_codigo.py --atualizar-linha-de-base
"""
import hashlib
import json
import os
import re
import subprocess
import sys
from pathlib import Path

RAIZ = Path(os.environ.get('PRINCIPIOS_RAIZ') or Path(__file__).resolve().parents[1])
LINHA_DE_BASE = RAIZ / 'docs' / 'decisoes' / 'linha-de-base.json'
VIEWS_WEB = 'apps/web/resources/views'
JS_WEB = 'apps/web/public/js'
# Pacotes gerados por scripts/*/build.mjs a partir de bibliotecas de terceiros.
JS_GERADOS = {'painel-grade.js', 'editor-paineis.js', 'painel-agenda.js', 'turma-graficos.js', 'echarts-g360.min.js'}


def arquivos(pasta, sufixo):
    base = RAIZ / pasta
    if not base.exists():
        return []
    return sorted(p for p in base.rglob('*' + sufixo) if p.is_file())


def rel(p):
    return p.relative_to(RAIZ).as_posix()


def chave(p, trecho):
    """Chave estável: arquivo + hash do trecho normalizado (não depende do número da linha)."""
    norma = re.sub(r'\s+', ' ', trecho).strip()
    return f'{rel(p)}#{hashlib.sha1(norma.encode("utf-8")).hexdigest()[:12]}'


def linhas(p):
    return p.read_text(encoding='utf-8', errors='replace').splitlines()


def js_externo():
    achados = []
    atributo = re.compile(r'\son[a-z]+\s*=\s*["\']', re.I)
    script = re.compile(r'<script\b([^>]*)>', re.I)
    for p in arquivos(VIEWS_WEB, '.blade.php'):
        for n, linha in enumerate(linhas(p), 1):
            if atributo.search(linha):
                achados.append((chave(p, linha), f'{rel(p)}:{n}: atributo on* na view'))
            for m in script.finditer(linha):
                attrs = m.group(1).lower()
                if 'src=' in attrs or re.search(r'type\s*=\s*["\']application/(ld\+)?json', attrs):
                    continue
                achados.append((chave(p, linha), f'{rel(p)}:{n}: <script> inline com código'))
    return achados


def sem_dialogo_nativo():
    achados = []
    dialogo = re.compile(r'(?<![\w.$])(?:window\.)?(confirm|alert|prompt)\s*\(')
    alvos = arquivos(VIEWS_WEB, '.blade.php') + [
        p for p in arquivos(JS_WEB, '.js') if '/libs/' not in rel(p) and p.name not in JS_GERADOS]
    for p in alvos:
        for n, linha in enumerate(linhas(p), 1):
            for m in dialogo.finditer(linha):
                achados.append((chave(p, linha), f'{rel(p)}:{n}: {m.group(1)}() nativo'))
    return achados


def api_so_json():
    achados = []
    chamada = re.compile(r'(?<![\w>:$])view\s*\(|View::make\s*\(|->view\s*\(|Response::view\s*\(')
    for pasta in ('apps/api/app', 'apps/api/routes'):
        for p in arquivos(pasta, '.php'):
            if '/Mail/' in rel(p) or '/Notifications/' in rel(p):
                continue  # e-mails usam views; não são respostas HTTP
            for n, linha in enumerate(linhas(p), 1):
                if chamada.search(linha):
                    achados.append((chave(p, linha), f'{rel(p)}:{n}: a API renderiza uma view'))
    for p in arquivos('apps/api/resources/views', '.blade.php'):
        if '/emails/' in rel(p) or '/mail/' in rel(p) or '/vendor/' in rel(p):
            continue
        achados.append((chave(p, rel(p)), f'{rel(p)}: view de tela na API'))
    return achados


def tabela_padrao():
    achados = []
    for p in arquivos(VIEWS_WEB, '.blade.php'):
        if rel(p).endswith('components/tabela.blade.php'):
            continue
        texto = p.read_text(encoding='utf-8', errors='replace')
        for m in re.finditer(r'<table\b', texto, re.I):
            antes = texto[:m.start()]
            dentro = len(re.findall(r'<x-tabela\b', antes)) - len(re.findall(r'</x-tabela>', antes))
            if dentro <= 0:
                n = antes.count('\n') + 1
                achados.append((chave(p, texto.splitlines()[n - 1]), f'{rel(p)}:{n}: <table> fora de <x-tabela>'))
    return achados


def versao_rodape():
    achados = []
    config = RAIZ / 'apps/web/config/versao.php'
    if not config.exists() or not re.search(r"'numero'\s*=>\s*'\d+\.\d+\.\d+'", config.read_text(encoding='utf-8')):
        achados.append(('apps/web/config/versao.php#ausente', 'apps/web/config/versao.php sem o número da versão'))
    layout = RAIZ / 'apps/web/resources/views/layouts/app.blade.php'
    formato = "Gabarito360 - v{{ config('versao.numero') }} - {{ config('versao.ambiente') }}"
    if not layout.exists() or formato not in layout.read_text(encoding='utf-8'):
        achados.append(('layouts/app#rodape', 'rodapé fora do formato "Gabarito360 - v<versão> - <ambiente>"'))
    literal = re.compile(r'\bv\d+\.\d+\.\d+\b')
    for p in arquivos(VIEWS_WEB, '.blade.php'):
        for n, linha in enumerate(linhas(p), 1):
            if literal.search(linha):
                achados.append((chave(p, linha), f'{rel(p)}:{n}: versão escrita à mão na view'))
    return achados


REGRAS = {
    'R-JS-EXTERNO': js_externo,
    'R-SEM-DIALOGO-NATIVO': sem_dialogo_nativo,
    'R-API-SO-JSON': api_so_json,
    'R-TABELA-PADRAO': tabela_padrao,
    'R-VERSAO-RODAPE': versao_rodape,
}


def contar(achados):
    contagem = {}
    for k, _ in achados:
        contagem[k] = contagem.get(k, 0) + 1
    return contagem


def ler_linha_de_base(texto=None):
    if texto is None:
        if not LINHA_DE_BASE.exists():
            return {}
        texto = LINHA_DE_BASE.read_text(encoding='utf-8')
    return json.loads(texto).get('regras', {})


def total(base):
    return sum(sum(v.values()) for v in base.values())


def verificar(regra, ref_base=None):
    achados = REGRAS[regra]()
    atual = contar(achados)
    conhecido = ler_linha_de_base().get(regra, {})
    erros = []
    for k, qtd in atual.items():
        if qtd > conhecido.get(k, 0):
            for kk, msg in achados:
                if kk == k:
                    erros.append('nova violação: ' + msg)
                    break
    obsoletas = [k for k, qtd in conhecido.items() if atual.get(k, 0) < qtd]
    if obsoletas:
        erros.append(f'{len(obsoletas)} violação(ões) corrigida(s) ainda na linha de base. '
                     'Rode: python scripts/verificar_regras_codigo.py --atualizar-linha-de-base')
    if ref_base:
        r = subprocess.run(['git', 'show', f'{ref_base}:docs/decisoes/linha-de-base.json'],
                           cwd=RAIZ, capture_output=True, text=True, encoding='utf-8')
        if r.returncode == 0:
            antes = sum(ler_linha_de_base(r.stdout).get(regra, {}).values())
            agora = sum(conhecido.values())
            if agora > antes:
                erros.append(f'a linha de base de {regra} cresceu de {antes} para {agora}. '
                             'Ela só pode diminuir: corrija a violação nova.')
    return erros, len(achados)


def atualizar_linha_de_base():
    dados = {
        'descricao': 'Violações conhecidas das regras de código (FN-078, subfase 3.2). '
                     'A contagem só pode diminuir. Gerado por scripts/verificar_regras_codigo.py.',
        'regras': {r: dict(sorted(contar(f()).items())) for r, f in REGRAS.items()},
    }
    LINHA_DE_BASE.write_text(json.dumps(dados, ensure_ascii=False, indent=2) + '\n', encoding='utf-8', newline='\n')
    return dados['regras']


if __name__ == '__main__':
    args = sys.argv[1:]
    if '--atualizar-linha-de-base' in args:
        anterior = ler_linha_de_base()
        novo = atualizar_linha_de_base()
        for r in REGRAS:
            print(f'{r}: {sum(anterior.get(r, {}).values())} -> {sum(novo[r].values())}')
        sys.exit(0)
    ref = args[args.index('--base') + 1] if '--base' in args else None
    regras = [a for a in args if a in REGRAS] or list(REGRAS)
    falhou = False
    for regra in regras:
        erros, qtd = verificar(regra, ref)
        if erros:
            falhou = True
            print(f'{regra}: reprovada')
            for e in erros[:30]:
                print('  - ' + e)
        else:
            print(f'{regra}: aprovada; {qtd} violação(ões) antiga(s), todas na linha de base.')
    sys.exit(1 if falhou else 0)
