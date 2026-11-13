"""
/**
 * Arquivo: icones/familia/prancha_final.py
 * Função: monta a prancha de aprovação do lote 1 usando a FONTE compilada (não os SVGs),
 *         com a mesma estrutura de pastas do app (css/ e fonts/icones/), e grava prancha/index.html.
 */
"""
import json
import shutil
from pathlib import Path

BASE = Path(__file__).parent
P = BASE / "prancha"
(P / "css").mkdir(parents=True, exist_ok=True)
(P / "fonts" / "icones").mkdir(parents=True, exist_ok=True)
shutil.copy(BASE / "fonte" / "g360-icones.css", P / "css")
for f in ("g360-icones.woff2", "g360-icones-preenchido.woff2"):
    shutil.copy(BASE / "fonte" / f, P / "fonts" / "icones")

reg = json.loads((BASE / "registro.json").read_text(encoding="utf-8"))
import importlib, sys
sys.path.insert(0, str(BASE))
LOTES = {n: list(importlib.import_module(f"lotes.lote{n}").ICONES) for n in range(1, 8)}
NOMES_LOTE = {1: "Plano e uso e Leitor360", 2: "Menu e casca", 3: "Ações", 4: "Setas e navegação",
              5: "Estados e avisos", 6: "Conteúdo e dados", 7: "Avaliação e questões"}
CAT = {x["id"]: x["significado"] for v in json.loads((BASE / "catalogo.json").read_text(encoding="utf-8"))["lotes"].values() for x in v}
PLANO = ["cobertura-plano", "correcoes-disponiveis", "franquia-ciclo", "consumo-ciclo", "referencia-acompanhamento",
         "adicionais", "teto-periodo", "operacoes-processamento", "beneficio", "contratacao", "reenvio",
         "consumo-operacao", "teto-operacao"]
LEITOR = ["webcam", "galeria", "lote-pdf", "tipo-objetiva", "tipo-texto", "tipo-formula", "tipo-desenho",
          "leitura-pendente", "pagina-faltando"]
assert sorted(sum(LOTES.values(), [])) == sorted(reg), "lista da prancha diferente do registro"

SIGNIFICADO = {
    "cobertura-plano": "Escudo com check: cobertura vigente", "correcoes-disponiveis": "Vale de correção (unidade de crédito)",
    "franquia-ciclo": "Calendário com check: incluído no ciclo", "consumo-ciclo": "Medidor: consumo no ciclo",
    "referencia-acompanhamento": "Gráfico com linha de referência tracejada", "adicionais": "Vale + : correções adicionais",
    "teto-periodo": "Barras sob a linha de teto", "operacoes-processamento": "Ampulheta: em processamento",
    "beneficio": "Presente: benefício por contribuição", "contratacao": "Documento assinado: contratação",
    "reenvio": "Folha com seta circular: reenvio", "consumo-operacao": "Vale − : consumo no extrato",
    "teto-operacao": "Linha de teto com seta dupla: ajuste de teto", "webcam": "Webcam do computador",
    "galeria": "Fotos da galeria", "lote-pdf": "Pilha de documentos: lote de PDFs", "tipo-objetiva": "Opção marcada: objetiva",
    "tipo-texto": "T: resposta em texto", "tipo-formula": "Σ: resposta com fórmula", "tipo-desenho": "Formas: resposta com desenho",
    "leitura-pendente": "Folha com !: pendência para conferir", "pagina-faltando": "Folha tracejada: página faltando",
}
TONS = ["azul", "verde", "vermelho", "amarelo", "laranja", "lilas", "marca"]


def ic(nome, extra="", px=None):
    est = f' style="font-size:{px}px"' if px else ""
    return f'<span class="g360i g360i-{nome} {extra}"{est} aria-hidden="true"></span>'


def bloco(titulo, nomes):
    cards = "".join(
        f'<div class="card"><div class="tres">{ic(n, px=44)}{ic(n, "g360i--preenchido", 44)}{ic(n, "g360i--duo g360i--azul", 44)}</div>'
        f'<b>{n}</b><small>{SIGNIFICADO.get(n) or CAT.get(n, "")}</small><code>U+{reg[n]:04X}</code></div>' for n in nomes)
    return f"<h2>{titulo}</h2><div class='grade'>{cards}</div>"


def tamanhos(tema):
    linhas = "".join(
        f'<div class="tl"><span class="nm">{n}</span>' + "".join(ic(n, px=s) for s in (16, 20, 24, 32))
        + '<i></i>' + "".join(ic(n, "g360i--preenchido", s) for s in (16, 20, 24, 32)) + "</div>" for n in sum(LOTES.values(), []))
    return f'<div class="tema" data-theme="{tema}"><h3>Tema {"claro" if tema == "light" else "escuro"}: 16, 20, 24, 32 px — contorno | preenchido</h3><div class="tam">{linhas}</div></div>'


def tons(tema):
    amostra = ["cobertura-plano", "correcoes-disponiveis", "consumo-ciclo", "adicionais", "leitura-pendente", "webcam"]
    linhas = "".join(f'<div class="tn"><span class="nm">{t}</span>' + "".join(ic(n, f"g360i--duo g360i--{t}", 32) for n in amostra)
                     + "".join(ic(n, f"g360i--{t}", 32) for n in amostra[:2]) + "</div>" for t in TONS)
    return f'<div class="tema" data-theme="{tema}"><h3>Duotônico por token — tema {"claro" if tema == "light" else "escuro"}</h3>{linhas}</div>'


def menu(tema):
    itens = [("painel", "Painel", 0), ("corrigir", "Corrigir", 1), ("prova", "Provas", 0), ("turma", "Turmas", 0),
             ("relatorio", "Relatórios", 0), ("escola", "Escolas", 0), ("aluno", "Alunos", 0), ("equipe", "Equipe", 0)]
    lis = "".join(f'<li class="{"at" if a else ""}">{ic(i, "g360i--preenchido" if a else "", 20)}<span>{r}</span></li>' for i, r, a in itens)
    return (f'<div class="tema" data-theme="{tema}"><h3>Menu lateral — item ativo no preenchido — tema {"claro" if tema == "light" else "escuro"}</h3>'
            f'<ul class="menu">{lis}</ul></div>')


def contexto(tema):
    cards = [("cobertura-plano", "Sua cobertura", "Plano Professor · ciclo até 30/11", "marca", True),
             ("correcoes-disponiveis", "Correções disponíveis", "412", "azul", False),
             ("franquia-ciclo", "Franquia do ciclo", "500", "verde", False),
             ("consumo-ciclo", "Consumo no ciclo", "88", "laranja", False),
             ("adicionais", "Adicionais", "0", "lilas", False)]
    html = "".join(
        f'<div class="ind{" princ" if p else ""}"><span class="tile">{ic(n, f"g360i--duo g360i--{t}", 28)}</span>'
        f'<div><small>{r}</small><strong>{v}</strong></div></div>' for n, r, v, t, p in cards)
    entradas = [("camera", "Fotografar folhas"), ("webcam", "Usar a webcam"), ("galeria", "Enviar fotos da galeria"),
                ("pdf", "Enviar PDF"), ("lote-pdf", "Lote de PDFs")]
    lista = "".join(
        f'<li>{ic(n, px=22) if "existente" not in n else "<span class=vazio>(ícone atual do app)</span>"}<span>{r}</span></li>'
        for n, r in entradas)
    return (f'<div class="tema" data-theme="{tema}"><h3>Ilustração de uso — não é mockup — tema {"claro" if tema == "light" else "escuro"}</h3>'
            f'<div class="inds">{html}</div><ul class="ent">{lista}</ul></div>')


html = f"""<!doctype html><html lang="pt-BR"><meta charset="utf-8"><title>Ícones Gabarito360 — lote 1</title>
<link rel="stylesheet" href="css/g360-icones.css"><style>
@page{{size:A4;margin:12mm}}
:root,[data-theme=light]{{--fg:#16233a;--muted:#56637a;--surface:#ffffff;--surface-2:#eef2f8;--bg:#f4f6fa;--border:#c9d2df;
 --accent:#1d4f9e;--accent-light:#dfe9f8;--success:#168821;--success-light:#e3f5e1;--danger:#c62f1c;--danger-light:#fce8e6;--texto-sucesso:#168821;--texto-erro:#c62f1c;
 --warn-fg:#6b5400;--warn-light:#fff8d6;--serie-5:#c2410c;--purple-fg:#6d28d9;--purple-light:#efe7ff;--g360-blue:#0588fc}}
[data-theme=dark]{{--fg:#e7edf6;--muted:#a2afc4;--surface:#182234;--surface-2:#1e2a3f;--bg:#0f1725;--border:#2c3a50;
 --accent:#86b1f2;--accent-light:#22344f;--success:#168821;--success-light:#0a2a10;--danger:#c62f1c;--danger-light:#2a0a08;--texto-sucesso:#86efac;--texto-erro:#fca5a5;
 --warn-fg:#f0c060;--warn-light:#2a200a;--serie-5:#d9713c;--purple-fg:#c4a8ff;--purple-light:#2d1f4a;--g360-blue:#0588fc}}
body{{font:11px/1.45 system-ui,sans-serif;color:var(--fg);background:#fff;margin:0}}
h1{{font-size:20px;color:#001534;margin:0 0 4px}}h2{{font-size:14px;color:#1d4f9e;border-bottom:1px solid #c9d2df;padding-bottom:3px;margin:16px 0 8px}}
h3{{font-size:11.5px;margin:0 0 8px;color:var(--fg)}}
.spec{{display:grid;grid-template-columns:1fr 1fr;gap:4px 18px;margin:8px 0}}.spec div{{border-left:3px solid #0588fc;padding-left:6px}}
.grade{{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}}
.card{{border:1px solid #dbe2ed;border-radius:8px;padding:8px;break-inside:avoid;display:flex;flex-direction:column;gap:2px;color:#16233a}}
.tres{{display:flex;gap:10px;color:#16233a;margin-bottom:4px}}.card b{{font-size:11px}}.card small{{color:#56637a}}.card code{{font-size:9px;color:#56637a}}
.tema{{background:var(--bg);color:var(--fg);border-radius:8px;padding:10px;margin:8px 0;break-inside:avoid}}
.tam{{columns:2;column-gap:16px}}.tl{{display:flex;align-items:center;gap:8px;padding:2px 0;break-inside:avoid}}.tl i{{width:10px}}
.nm{{width:150px;font-size:10px;color:var(--muted)}}.tn{{display:flex;align-items:center;gap:12px;padding:3px 0}}
.inds{{display:grid;grid-template-columns:1.4fr repeat(4,1fr);gap:8px}}.ind{{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:10px;display:flex;gap:8px;align-items:center}}
.ind small{{display:block;color:var(--muted)}}.ind strong{{font-size:15px}}.princ strong{{font-size:12px}}
.tile{{width:40px;height:40px;border-radius:10px;background:var(--surface-2);display:grid;place-items:center;flex-shrink:0}}
.ent{{list-style:none;padding:0;margin:10px 0 0;display:grid;grid-template-columns:repeat(5,1fr);gap:8px}}
.ent li{{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:10px;display:flex;flex-direction:column;gap:6px;align-items:flex-start}}
.vazio{{font-size:9px;color:var(--muted)}}
.menu{{list-style:none;padding:0;margin:0;display:grid;grid-template-columns:repeat(4,1fr);gap:4px}}.menu li{{display:flex;gap:8px;align-items:center;padding:7px 10px;border-radius:8px;color:var(--fg)}}
.menu li.at{{background:var(--accent-light);color:var(--accent);font-weight:600}}.quebra{{break-before:page}}
</style><body>
<h1>G360 Icones — família completa ({len(reg)} ícones)</h1>
<div>Proposta para aprovação. Tudo nesta prancha é desenhado pela <b>fonte compilada</b> (G360 Icones / G360 Icones Preenchido), não por imagem.</div>
<div class="spec">
<div><b>Grade</b> 24 × 24, área útil 20 × 20 (margem 2). Linhas-guia: círculo r 9, folha 5–19 × 3–21, paisagem 3–21 × 6–18.</div>
<div><b>Traço</b> 1,8, pontas e junções redondas — o mesmo do <code>x-icone</code> atual. Folga mínima entre traços de 1,2 px.</div>
<div><b>Variantes</b> contorno (padrão) e preenchido (item ativo, destaque). Os detalhes internos viram recorte no preenchido.</div>
<div><b>Duotônico</b> preenchido no tom claro sob o contorno no tom forte. Cor só por token: <code>--accent</code>, <code>--texto-sucesso</code>, <code>--texto-erro</code>, <code>--warn-fg</code>, <code>--serie-5</code>, <code>--purple-fg</code>, <code>--g360-blue</code>.</div>
<div><b>Códigos</b> fixos na área de uso privado, a partir de U+E001, em <code>registro.json</code>. Ícone novo entra no fim; nenhum código muda.</div>
<div><b>Arquivos</b> TTF (instala no Windows e no CorelDRAW) e WOFF2 (app). Fonte única: os SVGs de traço em <code>src/</code>.</div>
</div>
<div style="color:#56637a">Em cada cartão: contorno · preenchido · duotônico azul.</div>
{"".join(bloco(f"Lote {n} · {NOMES_LOTE[n]} ({len(v)})", v) for n, v in LOTES.items())}
<h2 class="quebra">Tamanhos e temas</h2>{tamanhos("light")}<div class="quebra"></div>{tamanhos("dark")}
<h2 class="quebra">Duotônico por tom</h2>
<div style="color:#56637a">Seis ícones duotônicos e, no fim de cada linha, dois em contorno simples no mesmo tom. O laranja e a marca não têm token claro; o fundo sai do próprio token por <code>color-mix</code>.</div>
{tons("light")}{tons("dark")}
<h2>Em uso</h2>{menu("light")}{menu("dark")}{contexto("light")}{contexto("dark")}
</body></html>"""
(P / "index.html").write_text(html, encoding="utf-8")
print("prancha ok")
