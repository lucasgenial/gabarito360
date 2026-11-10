"""Verifica os princípios do projeto (FN-078 subfases 2.1 e 2.2, ADR-0053).

Regras:
1. Todo ADR tem cabeçalho de campos válido (id, título, estado, data, afeta, verificação).
2. Todo ADR aceito com verificação 'teste' cita pelo menos uma regra de docs/decisoes/regras.yml.
3. Toda regra citada existe; toda regra ativa tem comando e o script do comando existe.
4. A versão dos princípios (docs/decisoes/VERSAO) corresponde ao conjunto atual de ADRs.

Uso:
  python scripts/verificar_principios.py              # verifica
  python scripts/verificar_principios.py --atualizar  # sobe a versão depois de mudar um ADR
"""
import re
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
import principios as P  # noqa: E402

OBRIGATORIOS = ('id', 'titulo', 'estado', 'substituida_por', 'data', 'afeta_funcionalidades', 'afeta_caminhos', 'verificacao', 'regras')


def verificar():
    erros, avisos = [], []
    regras = {r.get('id'): r for r in P.ler_regras()}
    lista = P.adrs()
    ids = {i for i, *_ in lista}
    fns = P.funcionalidades()

    for rid, r in regras.items():
        if not rid or not str(rid).startswith('R-'):
            erros.append(f'regras.yml: regra sem id válido ({rid})')
            continue
        if r.get('adr') not in ids:
            erros.append(f'regras.yml: {rid} cita ADR inexistente ({r.get("adr")})')
        if r.get('estado') not in ('ativa', 'planejada'):
            erros.append(f'regras.yml: {rid} com estado inválido ({r.get("estado")})')
        if r.get('estado') == 'ativa':
            comando = str(r.get('comando') or '')
            script = re.search(r'scripts/[\w/.-]+\.py', comando)
            if not script or not (P.RAIZ / script.group(0)).exists():
                erros.append(f'regras.yml: {rid} ativa sem comando com script existente')

    for adr_id, caminho, c, erro in lista:
        nome = caminho.name
        if erro:
            erros.append(f'{nome}: {erro}')
            continue
        faltam = [k for k in OBRIGATORIOS if k not in c]
        if faltam:
            erros.append(f'{nome}: campos ausentes: {", ".join(faltam)}')
            continue
        if c['id'] != adr_id:
            erros.append(f'{nome}: id {c["id"]} diferente do arquivo ({adr_id})')
        if c['estado'] not in P.ESTADOS:
            erros.append(f'{nome}: estado inválido ({c["estado"]})')
        if not re.fullmatch(r'\d{4}-\d{2}-\d{2}', str(c['data'])):
            erros.append(f'{nome}: data fora do formato AAAA-MM-DD ({c["data"]})')
        if c['verificacao'] not in P.VERIFICACOES:
            erros.append(f'{nome}: verificacao inválida ({c["verificacao"]})')
        for fn in c['afeta_funcionalidades'] or []:
            if fn != 'todas' and fn not in fns:
                erros.append(f'{nome}: funcionalidade inexistente no catálogo ({fn})')
        for outro in c['substituida_por'] or []:
            if outro not in ids:
                erros.append(f'{nome}: substituída por ADR inexistente ({outro})')
        if c['estado'] == 'Substituída' and not c['substituida_por']:
            erros.append(f'{nome}: estado Substituída sem substituida_por')
        for regra in c['regras'] or []:
            if regra not in regras:
                erros.append(f'{nome}: regra {regra} não existe em regras.yml')
        if c['estado'] == 'Aceita' and c['verificacao'] == 'teste':
            if not c['regras']:
                erros.append(f'{nome}: ADR verificável sem regra (ADR-0053 proíbe princípio sem verificação)')
            elif all(regras.get(r, {}).get('estado') == 'planejada' for r in c['regras']):
                avisos.append(f'{adr_id}: regra(s) ainda planejada(s): {", ".join(c["regras"])}')
        tem_agents = any(k in c for k in ('agents_secao', 'agents_ordem', 'agents_resumo'))
        if tem_agents:
            if c['estado'] != 'Aceita':
                erros.append(f'{nome}: só ADR aceito pode ter resumo no AGENTS.md')
            if c.get('agents_secao') not in P.SECOES:
                erros.append(f'{nome}: agents_secao inválida ({c.get("agents_secao")})')
            if not isinstance(c.get('agents_ordem'), int):
                erros.append(f'{nome}: agents_ordem deve ser inteiro')
            resumo = c.get('agents_resumo') or []
            if not 1 <= len(resumo) <= 4:
                erros.append(f'{nome}: agents_resumo deve ter de 1 a 4 itens')

    versao, digital = P.ler_versao()
    atual = P.impressao_digital()
    if digital != atual:
        erros.append(f'VERSAO desatualizada: os ADRs mudaram desde a versão {versao}. '
                     'Rode: python scripts/verificar_principios.py --atualizar')
    return erros, avisos, versao


if __name__ == '__main__':
    if '--atualizar' in sys.argv:
        versao, digital = P.ler_versao()
        atual = P.impressao_digital()
        if digital == atual:
            print(f'Princípios sem mudança: versão {versao}.')
        else:
            P.gravar_versao(versao + 1, atual)
            print(f'Versão dos princípios atualizada: {versao} -> {versao + 1}.')
        sys.exit(0)
    erros, avisos, versao = verificar()
    for a in avisos:
        print('aviso: ' + a)
    if erros:
        print(f'Princípios inconsistentes ({len(erros)} problema(s)):')
        for e in erros:
            print('  - ' + e)
        sys.exit(1)
    print(f'Princípios consistentes: versão {versao}, {len(P.adrs())} ADRs, {len(P.ler_regras())} regras.')
