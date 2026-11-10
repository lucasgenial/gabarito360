"""Verifica a autoria dos commits (ADR-0051, FN-078 subfase 1.2).

Regra: o único autor é o responsável. Nenhuma IA aparece como autora, committer ou coautora.

O script reprova quando um commit do intervalo tem:
- linha "Co-Authored-By" de IA (Claude, Anthropic, OpenAI, Codex, Copilot, Gemini, ChatGPT e similares);
- linha "Generated with" de ferramenta de IA;
- e-mail noreply@anthropic.com em qualquer parte da mensagem;
- autor ou committer cujo nome ou e-mail identifica uma IA.

Uso:
  python scripts/verificar_autoria.py <base>..<head>   # intervalo de commits
  python scripts/verificar_autoria.py                  # padrão: origin/main..HEAD
"""
import re
import subprocess
import sys

IA = r'(claude|anthropic|openai|codex|copilot|gemini|chatgpt|gpt-[0-9]|devin|cursor\s*agent)'
MENSAGEM = [
    re.compile(r'^\s*co-authored-by:.*' + IA, re.I | re.M),
    re.compile(r'generated with .*' + IA, re.I),
    re.compile(r'noreply@anthropic\.com', re.I),
]
PESSOA = re.compile(IA, re.I)
SEP = '\x1e'


def commits(intervalo):
    formato = SEP.join(['%H', '%an', '%ae', '%cn', '%ce', '%B']) + '\x1f'
    saida = subprocess.run(['git', 'log', f'--format={formato}', intervalo],
                           capture_output=True, text=True, encoding='utf-8')
    if saida.returncode != 0:
        print(f'Não foi possível ler o intervalo {intervalo}: {saida.stderr.strip()}')
        sys.exit(2)
    for bloco in saida.stdout.split('\x1f'):
        bloco = bloco.strip('\n')
        if not bloco:
            continue
        sha, an, ae, cn, ce, corpo = bloco.split(SEP, 5)
        yield sha, an, ae, cn, ce, corpo


def verificar(intervalo):
    erros = []
    total = 0
    for sha, an, ae, cn, ce, corpo in commits(intervalo):
        total += 1
        curto = sha[:10]
        for padrao in MENSAGEM:
            m = padrao.search(corpo)
            if m:
                linha = corpo[m.start():].splitlines()[0].strip()
                erros.append(f'{curto}: mensagem com marca de IA: "{linha[:90]}"')
                break
        if PESSOA.search(f'{an} {ae}'):
            erros.append(f'{curto}: autor identifica uma IA ({an})')
        if PESSOA.search(f'{cn} {ce}'):
            erros.append(f'{curto}: committer identifica uma IA ({cn})')
    return total, erros


if __name__ == '__main__':
    intervalo = sys.argv[1] if len(sys.argv) > 1 else 'origin/main..HEAD'
    total, erros = verificar(intervalo)
    if erros:
        print(f'Autoria inválida em {intervalo} (ADR-0051):')
        for e in erros:
            print('  - ' + e)
        print('Corrija com: git rebase -i e remova as linhas, ou ative o gancho com scripts/configurar-ambiente.sh.')
        sys.exit(1)
    print(f'Autoria válida: {total} commit(s) em {intervalo}, sem marca de IA.')
