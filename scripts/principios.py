"""Leitura dos princípios do projeto (ADRs) e do registro de regras (FN-078, ADR-0053).

Usa só a biblioteca padrão. O formato é um YAML restrito:
- cabeçalho do ADR entre linhas '---', com chaves simples, listas na mesma linha ([a, b])
  e uma única lista em bloco ('agents_resumo', itens '  - "texto"');
- regras.yml: lista de itens '- chave: valor', com chaves '  chave: valor'.
"""
import hashlib
import os
import re
from pathlib import Path

RAIZ = Path(os.environ.get('PRINCIPIOS_RAIZ') or Path(__file__).resolve().parents[1])
DECISOES = RAIZ / 'docs' / 'decisoes'
REGRAS = DECISOES / 'regras.yml'
VERSAO = DECISOES / 'VERSAO'
AGENTS = RAIZ / 'AGENTS.md'
CATALOGO = RAIZ / 'docs' / 'funcionalidades' / 'README.md'

ESTADOS = ('Aceita', 'Substituída', 'Revogada')
VERIFICACOES = ('teste', 'revisao-ia', 'processo')
SECOES = ('Idioma e escrita', 'Autoria', 'Planejamento', 'Princípios executáveis', 'Agentes e modelos de IA',
          'Documentação', 'Publicação', 'Prints', 'Interface', 'Sessão e vínculo', 'Desempenho')
ARQUIVO_ADR = re.compile(r'^(\d{4})-[a-z0-9-]+\.md$')


def _valor(bruto):
    bruto = bruto.strip()
    if bruto.startswith('[') and bruto.endswith(']'):
        interno = bruto[1:-1].strip()
        if not interno:
            return []
        return [_valor(x) for x in re.findall(r'"[^"]*"|[^,]+', interno)]
    if len(bruto) >= 2 and bruto[0] == bruto[-1] == '"':
        return bruto[1:-1]
    if re.fullmatch(r'-?\d+', bruto):
        return int(bruto)
    return bruto


def ler_cabecalho(texto):
    """Devolve (campos, erro). Campos é um dicionário; erro é None ou uma mensagem."""
    linhas = texto.replace('\r\n', '\n').split('\n')
    if not linhas or linhas[0].strip() != '---':
        return None, 'sem cabeçalho de campos (linha "---" no início)'
    campos, lista_atual = {}, None
    for n, linha in enumerate(linhas[1:], start=2):
        if linha.strip() == '---':
            return campos, None
        if not linha.strip():
            continue
        item = re.match(r'^\s+-\s+(.*)$', linha)
        if item and lista_atual:
            campos[lista_atual].append(_valor(item.group(1)))
            continue
        chave = re.match(r'^([a-z_]+):\s*(.*)$', linha)
        if not chave:
            return None, f'linha {n} inválida no cabeçalho: {linha.strip()[:60]}'
        nome, valor = chave.group(1), chave.group(2)
        if valor == '':
            campos[nome], lista_atual = [], nome
        else:
            campos[nome], lista_atual = _valor(valor), None
    return None, 'cabeçalho sem linha "---" de fechamento'


def adrs():
    """Lista (id, caminho, campos, erro) de todos os ADRs, em ordem."""
    saida = []
    for p in sorted(DECISOES.glob('*.md')):
        m = ARQUIVO_ADR.match(p.name)
        if not m:
            continue
        campos, erro = ler_cabecalho(p.read_text(encoding='utf-8'))
        saida.append((f'ADR-{m.group(1)}', p, campos, erro))
    return saida


def ler_regras():
    """Lista de dicionários de regras.yml."""
    if not REGRAS.exists():
        return []
    regras, atual = [], None
    for linha in REGRAS.read_text(encoding='utf-8').replace('\r\n', '\n').split('\n'):
        if not linha.strip() or linha.lstrip().startswith('#'):
            continue
        novo = re.match(r'^-\s+([a-z_]+):\s*(.*)$', linha)
        if novo:
            atual = {novo.group(1): _valor(novo.group(2))}
            regras.append(atual)
            continue
        chave = re.match(r'^\s+([a-z_]+):\s*(.*)$', linha)
        if chave and atual is not None:
            atual[chave.group(1)] = _valor(chave.group(2))
    return regras


def funcionalidades():
    return set(re.findall(r'^\| (FN-\d{3}) \|', CATALOGO.read_text(encoding='utf-8'), re.M)) if CATALOGO.exists() else set()


def impressao_digital():
    """Hash do conjunto de ADRs. Qualquer mudança num ADR muda o hash."""
    h = hashlib.sha256()
    for _, p, _, _ in adrs():
        texto = p.read_text(encoding='utf-8').replace('\r\n', '\n')
        texto = '\n'.join(l.rstrip() for l in texto.split('\n')).strip() + '\n'
        h.update(p.name.encode('utf-8') + b'\0' + texto.encode('utf-8') + b'\0')
    return h.hexdigest()


def ler_versao():
    if not VERSAO.exists():
        return 0, ''
    dados = dict(re.findall(r'^(\w+):\s*(\S+)', VERSAO.read_text(encoding='utf-8'), re.M))
    return int(dados.get('versao', 0)), dados.get('hash', '')


def gravar_versao(versao, digital):
    VERSAO.write_text(
        '# Versão dos princípios do projeto (ADR-0053). Não edite à mão.\n'
        '# Atualize com: python scripts/verificar_principios.py --atualizar\n'
        f'versao: {versao}\nhash: {digital}\n', encoding='utf-8', newline='\n')
