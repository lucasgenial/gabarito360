"""
/**
 * Arquivo: icones/familia/catalogo.py
 * Função: catálogo mestre da família G360 Icones — um id por conceito, com origem e lote.
 *         Origem: app (inventário da homologação 3374ebf), menu (Acesso::ITENS e casca app.blade.php),
 *         mockup (Plano e uso, Leitor360) ou previsto (proposta pelo checklist de categorias do Font Awesome).
 *         Gera catalogo.json e catalogo.html (impressão em PDF).
 */
"""
import json
from pathlib import Path

BASE = Path(__file__).parent
reg = json.loads((BASE / "registro.json").read_text(encoding="utf-8"))

# (id, significado, origem, onde/nota)
LOTES = {
    "1 · Plano e uso e Leitor360 (feito)": [(n, "", "mockup", "lote 1 entregue") for n in reg],

    "2 · Menu e casca": [
        ("painel", "Painel (início)", "menu", "item painel; hoje casa"),
        ("usuarios-configuracao", "Configurações de usuários", "menu", "item funcoes; hoje equipe"),
        ("clientes", "Clientes", "menu", "item clientes; hoje equipe"),
        ("acessos-internos", "Acessos internos", "menu", "item internos; hoje equipe"),
        ("instituicoes-cadastro", "Cadastro de instituições (atendimento)", "menu", "item catalogo_atendimento; hoje escola"),
        ("pedidos-titulares", "Pedidos de titulares (LGPD)", "menu", "item pedidos; hoje equipe"),
        ("exportacoes-titulares", "Exportações de titulares", "menu", "item exportacoes; hoje equipe"),
        ("prova", "Provas", "menu", "item provas; 20 usos no app, 5 desenhos"),
        ("corrigir", "Corrigir", "menu", "item corrigir; hoje camera"),
        ("turma", "Turmas", "menu", "item turmas"),
        ("disciplina", "Disciplinas", "menu", "item disciplinas; hoje prova"),
        ("instituicao", "Minhas instituições", "menu", "item instituicoes; hoje escola"),
        ("solicitacao", "Minhas solicitações", "menu", "item catalogo_pedidos; hoje escola"),
        ("instituicao-gestao", "Cadastro institucional", "menu", "item catalogo_gestao; hoje escola"),
        ("relatorio", "Relatórios", "menu", "item relatorios; hoje grafico"),
        ("escola", "Escolas", "menu", "item escolas; prédio x capelo hoje"),
        ("aluno", "Alunos", "menu", "item alunos"),
        ("equipe", "Equipe", "menu", "item equipe; 7 desenhos hoje"),
        ("copias", "Cópias", "menu", "item copias; hoje prova"),
        ("menu", "Abrir / recolher menu", "menu", "casca"),
        ("mais-vertical", "Mais (navegação inferior)", "menu", "casca, celular"),
        ("sair", "Sair da conta", "menu", "casca"),
        ("perfil", "Meu perfil", "menu", "casca; hoje aluno"),
        ("configuracoes", "Configurações", "menu", "casca; engrenagem"),
        ("trocar-vinculo", "Trocar vínculo / acesso", "menu", "casca; hoje transferir"),
        ("plano-e-uso", "Plano e uso (menu da conta)", "mockup", "item novo; hoje grafico"),
        ("tema-claro", "Mudar para tema claro", "app", "sol"),
        ("tema-escuro", "Mudar para tema escuro", "app", "lua"),
    ],

    "3 · Ações": [
        ("adicionar", "Adicionar / novo", "app", "19 usos; inclui o '+ ' dos botões"),
        ("editar", "Editar", "app", ""), ("salvar", "Salvar", "app", ""), ("excluir", "Excluir", "app", "lixeira, hoje sem uso"),
        ("arquivar", "Arquivar / desarquivar", "app", "hoje arquivo; colide com lote"), ("copiar", "Copiar", "previsto", "FA: interface"),
        ("imprimir", "Imprimir / cartões em PDF", "app", "11 usos"), ("baixar", "Baixar", "app", ""), ("enviar-arquivo", "Enviar arquivo (upload)", "previsto", "FA: arquivos"),
        ("compartilhar", "Compartilhar", "app", ""), ("buscar", "Buscar", "app", ""), ("filtro", "Filtrar", "app", ""),
        ("limpar-filtro", "Limpar filtros", "app", ""), ("ordenar", "Ordenar (neutro)", "app", "hoje ↕"),
        ("ordenar-crescente", "Ordem crescente", "app", "hoje ↑"), ("ordenar-decrescente", "Ordem decrescente", "app", "hoje ↓"),
        ("ver", "Ver / detalhes", "app", ""), ("ocultar", "Ocultar", "previsto", "par de ver; senha"),
        ("atualizar", "Atualizar / tentar de novo", "app", ""), ("desfazer", "Desfazer", "previsto", "FA: interface"),
        ("arrastar", "Arrastar (alça)", "app", ""), ("mover", "Mover / reordenar", "app", "hoje seta girada"),
        ("fixar", "Fixar", "app", ""), ("redimensionar", "Redimensionar", "app", "cor fixa hoje"),
        ("ampliar", "Ampliar (zoom +)", "app", "hoje +"), ("diminuir", "Diminuir (zoom −)", "app", "hoje −"),
        ("tela-cheia", "Tela cheia", "previsto", "leitor de folha"), ("gerar-pdf", "Gerar PDF", "app", "hoje pdf"),
        ("exibir-tabela", "Exibir como tabela", "app", "hoje prova"), ("exibir-cards", "Exibir como cards", "app", "hoje turma"),
        ("opcoes", "Mais opções (horizontal)", "app", ""), ("abrir-painel", "Abrir painel atual", "app", ""),
    ],

    "4 · Setas e navegação": [
        ("voltar", "Voltar", "app", "13 usos"), ("avancar", "Avançar / continuar", "app", "hoje →"),
        ("chevron-esquerda", "Anterior", "app", "hoje ‹"), ("chevron-direita", "Próxima / expandir", "app", "hoje › e avulso"),
        ("chevron-cima", "Recolher", "app", "hoje ▲"), ("chevron-baixo", "Expandir", "app", "hoje ▼"),
        ("rolar-horizontal", "Dica de rolagem lateral", "app", "hoje ↔"), ("link-externo", "Abrir em nova aba", "previsto", "Central do cliente externa"),
        ("transferir", "Transferir", "app", "conceito diferente de trocar-vinculo"),
    ],

    "5 · Estados e avisos": [
        ("confirmar", "Confirmar / concluído", "app", "hoje ✓"), ("fechar", "Fechar / cancelar", "app", "21 usos; hoje ✕ ×"),
        ("sucesso", "Sucesso (check em círculo)", "app", "situacao-ativa e toast"), ("erro", "Erro", "app", "toast"),
        ("alerta", "Atenção", "app", ""), ("informacao", "Informação", "previsto", "FA: alertas"), ("ajuda", "Ajuda", "previsto", "FA: alertas"),
        ("carregando", "Em andamento", "app", "toast"), ("aguardando", "Aguardando pagamento / pendente", "app", "hoje ⏳"),
        ("periodo-teste", "Período de teste", "app", "hoje ⏱"), ("tendencia-alta", "Alta", "app", "hoje ▲ ↑"),
        ("tendencia-baixa", "Queda", "app", "hoje ▼ ↓"), ("tendencia-estavel", "Estável", "app", "hoje →"),
        ("cadeado", "Bloqueado / correção fechada", "app", ""), ("desbloqueado", "Desbloqueado / reabrir", "previsto", "par do cadeado"),
        ("leitura-conferida", "Leitura conferida", "mockup", "hoje ✓"), ("notificacao", "Notificações", "previsto", "fila do modo servidor avisa no fim"),
        ("sem-conexao", "Sem conexão", "previsto", "o app exige internet"), ("situacao", "Ligado / desligado", "app", "sem uso hoje"),
    ],

    "6 · Conteúdo e dados": [
        ("camera", "Fotografar", "app", "10 usos"), ("lanterna", "Lanterna", "app", ""), ("pdf", "Arquivo PDF", "app", ""),
        ("documento", "Documento", "app", "hoje documento-linhas, sentido variável"), ("rascunho", "Rascunho", "app", ""),
        ("calendario", "Calendário / agenda", "app", ""), ("relogio", "Horário / prazo", "previsto", "FA: tempo"),
        ("email", "E-mail", "app", ""), ("telefone", "Telefone", "app", ""), ("local", "Endereço", "app", ""),
        ("inep", "Código INEP", "app", "hoje monitor"), ("grafico-barras", "Gráfico de barras", "app", "barras-verticais"),
        ("grafico-barras-horizontais", "Gráfico de barras horizontais", "app", ""), ("grafico-linha", "Evolução", "app", ""),
        ("senha", "Senha / chave", "previsto", "hoje cadeado avulso"), ("qr-code", "QR code do cartão", "previsto", "cartão-resposta tem QR"),
        ("cartao-resposta", "Cartão-resposta", "previsto", "núcleo do produto; hoje sem ícone"),
    ],

    "7 · Avaliação e questões (previsto)": [
        ("questao", "Questão", "previsto", "banco de questões"), ("banco-questoes", "Banco de questões", "previsto", ""),
        ("gabarito", "Gabarito oficial", "previsto", "comparação da leitura"), ("simulado", "Simulado", "previsto", "branch dc1-simulados"),
        ("aplicacao", "Aplicação de prova", "previsto", "identidade da cobrança"), ("nota", "Nota / resultado", "previsto", ""),
        ("acerto", "Acerto", "previsto", "relatórios"), ("erro-questao", "Erro de questão", "previsto", "relatórios"),
        ("anulada", "Questão anulada", "previsto", "leitura: anulada"), ("em-branco", "Em branco", "previsto", "leitura: branco"),
        ("marcacao-dupla", "Marcação dupla", "previsto", "leitura: dupla"), ("escriba", "Recursos do Escriba", "previsto", "nunca 'IA' na tela"),
        ("curadoria", "Curadoria de questões", "previsto", "recompensa após curadoria"), ("olimpiada", "Olimpíada (OBMEP)", "previsto", "origem do produto"),
    ],
}

CARACTERES_FORA = [("indicador-situacao", "Ponto ● de situação: forma CSS, não glifo"),
                   ("legenda-cor", "Amostra de cor na legenda: forma CSS, não glifo"),
                   ("marca", "Símbolo da marca: continua SVG próprio, fora da fonte")]

ids = [i for lote in LOTES.values() for (i, *_rest) in lote]
dup = {i for i in ids if ids.count(i) > 1}
assert not dup, f"ids repetidos: {dup}"

dados = {"lotes": {k: [dict(id=i, significado=s, origem=o, nota=n) for i, s, o, n in v] for k, v in LOTES.items()},
         "fora_da_fonte": [dict(id=i, motivo=m) for i, m in CARACTERES_FORA], "total": len(ids)}
(BASE / "catalogo.json").write_text(json.dumps(dados, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")

cont = {o: sum(1 for lote in LOTES.values() for x in lote if x[2] == o) for o in ("app", "menu", "mockup", "previsto")}
linhas = []
for nome, itens in LOTES.items():
    linhas.append(f"<h2>Lote {nome} — {len(itens)}</h2><table><tr><th>id</th><th>significado</th><th>origem</th><th>nota</th></tr>")
    linhas += [f"<tr class='{o}'><td><code>{i}</code></td><td>{s}</td><td>{o}</td><td>{n}</td></tr>" for i, s, o, n in itens]
    linhas.append("</table>")
html = f"""<!doctype html><meta charset="utf-8"><title>Catálogo mestre — G360 Icones</title><style>
@page{{size:A4;margin:12mm}}body{{font:10px/1.4 system-ui,sans-serif;color:#16233a}}h1{{font-size:18px;color:#001534;margin:0}}
h2{{font-size:12.5px;color:#1d4f9e;margin:14px 0 4px}}table{{border-collapse:collapse;width:100%}}th,td{{border:1px solid #dbe2ed;padding:2px 5px;text-align:left}}
th{{background:#eef2f8}}tr{{break-inside:avoid}}tr.previsto td{{color:#56637a;font-style:italic}}code{{font-size:9.5px}}
.res{{display:flex;gap:10px;margin:8px 0}}.res div{{border:1px solid #dbe2ed;border-radius:6px;padding:6px 10px}}.res b{{font-size:15px;display:block}}</style>
<h1>Catálogo mestre — G360 Icones</h1>
<div>Um id por conceito. Origem: inventário da homologação (3374ebf), menu (Acesso::ITENS e casca), mockups, e <i>previsto</i> = proposta a partir das categorias do Font Awesome, para você cortar ou manter.</div>
<div class="res"><div><b>{len(ids)}</b>ícones no total</div><div><b>{cont['app']}</b>do app</div><div><b>{cont['menu']}</b>do menu</div><div><b>{cont['mockup']}</b>dos mockups</div><div><b>{cont['previsto']}</b>previstos</div></div>
{''.join(linhas)}
<h2>Fora da fonte</h2><ul>{''.join(f'<li><code>{i}</code>: {m}</li>' for i, m in CARACTERES_FORA)}</ul>"""
(BASE / "catalogo.html").write_text(html, encoding="utf-8")
print("total", len(ids), cont, {k.split(' ·')[0]: len(v) for k, v in LOTES.items()})
