"""Gera o AGENTS.md a partir dos ADRs aceitos (FN-078 subfase 2.3, ADR-0053).

O AGENTS.md não é editado à mão. Para mudar uma regra, altere o ADR e rode:
  python scripts/gerar_agents.py
A CI roda com --verificar e falha se o arquivo versionado diferir do gerado.
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import principios as P  # noqa: E402

CABECALHO = """<!-- Arquivo gerado por scripts/gerar_agents.py a partir dos ADRs em docs/decisoes/. Não edite à mão. -->
# Orientações do projeto

Este arquivo resume as regras vigentes do Gabarito360. Ele vale para todos os agentes e ferramentas (Claude Code, Codex e outros).
O histórico e as justificativas estão nos [registros de decisão](docs/decisoes/README.md). Em conflito entre este resumo e um ADR, vale o ADR.
A porta de entrada da documentação é [docs/README.md](docs/README.md).

**Versão dos princípios: {versao}.** Os princípios podem mudar a qualquer momento. Uma mudança vale para todas as fases futuras ([ADR-0053](docs/decisoes/0053-principios-executaveis-e-mutaveis.md)).
Para mudar uma regra deste arquivo, altere o ADR correspondente e rode `python scripts/verificar_principios.py --atualizar` e `python scripts/gerar_agents.py`.
"""


def gerar():
    versao = P.ler_versao()[0]
    por_secao = {s: [] for s in P.SECOES}
    for adr_id, caminho, c, erro in P.adrs():
        if erro or not c or c.get('estado') != 'Aceita' or 'agents_secao' not in c:
            continue
        por_secao.setdefault(c['agents_secao'], []).append((c.get('agents_ordem', 999), adr_id, caminho.name, c.get('agents_resumo') or []))
    partes = [CABECALHO.format(versao=versao)]
    for secao in P.SECOES:
        itens = sorted(por_secao.get(secao, []))
        if not itens:
            continue
        partes.append(f'\n## {secao}\n')
        for _, adr_id, arquivo, resumo in itens:
            for i, frase in enumerate(resumo):
                sufixo = f' ([{adr_id}](docs/decisoes/{arquivo}))' if i == len(resumo) - 1 else ''
                partes.append(f'- {frase}{sufixo}')
    return '\n'.join(partes).rstrip() + '\n'


if __name__ == '__main__':
    conteudo = gerar()
    if '--verificar' in sys.argv:
        atual = P.AGENTS.read_text(encoding='utf-8').replace('\r\n', '\n') if P.AGENTS.exists() else ''
        if atual != conteudo:
            print('AGENTS.md diferente do gerado a partir dos ADRs. Rode: python scripts/gerar_agents.py')
            sys.exit(1)
        print('AGENTS.md igual ao gerado a partir dos ADRs.')
        sys.exit(0)
    P.AGENTS.write_text(conteudo, encoding='utf-8', newline='\n')
    print(f'AGENTS.md gerado: {conteudo.count(chr(10))} linhas.')
