# Divergências

Esta seção lista as divergências reais do app atual (`apps/web`, commit `37ba645`). Elas são pendências. Nada foi corrigido. Este design system documenta o código como ele está. A correção de cada item exige uma adequação aprovada pelo responsável.

## Pendências principais

### 1. Dois sistemas de componentes: legado e TW2

As regras TW2 de `.btn`, `.field`, `.input`, `.select`, `.card`, `.modal*` e `.table` valem só em `body.tela-turmas` e `body.tela-turma` (`EST:103-151`). O legado exclui essas telas com `:not(:where(.tela-turmas, …))` em mais de 60 seletores. O mesmo nome de classe muda de aparência conforme a tela.

| Classe | Legado | TW2 (Turmas) |
|---|---|---|
| `.btn` | padding 10/20, 600, borda 1,5 px | 32 px de altura, padding 4/12, 500, borda 1 px |
| `.btn-secondary` | contorno e texto `accent` | fundo `surface`, texto `fg`, borda `border` |
| `.btn-success` | fundo `success`, texto branco | fundo `success-light`, texto `success` |
| `.input` | borda 1,5 px, foco por sombra, sem outline | borda 1 px, 40 px, foco por outline de 2 px |
| `.card` | borda `border-soft`, sem sombra, padding 24 | borda `border`, sombra do Tailwind, padding 16 |
| `.modal` | raio 8, backdrop `rgba(0,0,0,.45)`, fechar 34 px | raio 12, backdrop `#00153480`, fechar 44 px |

Não existe classe para escolher a família. A família vem do nome da tela. O doc 09 §3 registra TW3 e TW4 como pendentes.

### 2. Duas folhas de tokens: `app.css` e legado

- `public/css/app.css` (páginas públicas, acesso e estado) e `public/css/app-conteudo-legado.css` (área logada) repetem os mesmos tokens e quase as mesmas regras. Uma mudança de token precisa ser feita nas duas.
- O doc 09 §2.1 diz que os tokens vivem em `app.css`. Para a área logada, eles vivem no legado.
- `app.css` ainda tem a casca antiga (`.app-header`, `.app-lateral`, `.nav-inferior`, `.user-menu*`) e cópias de `.g-pagina`, `.breadcrumb` e `.page-title`.
- Seletores da casca antiga continuam nas folhas da área logada, sem uso nas views. Em `estado-tela.css:27`, eles tentam esconder a casca na entrada de vínculo e não alcançam a casca atual.

### 3. Restos do padrão gov.br

- Os apelidos `color-primary-*`, `color-secondary-*`, `color-success-*`, `color-warning-*` e `color-danger-*` seguem a nomenclatura gov.br. Só `checkout/index.blade.php` e `checkout/pendente.blade.php` os usam. A identidade gov.br foi substituída em 30/10/2026 (doc 09 §2.1).
- Inferência, não confirmada no código: `success` (#168821) e `warn` (#ffcd07) coincidem com cores do padrão gov.br. Confirme com o responsável.

### 4. Cores e medidas sem token

- Barra do item ativo da lateral: `#0588fc` fixo, igual a `g360-blue` (`EST:34`).
- Sete fundos de backdrop diferentes: `#00153465`, `#00153480`, `rgba(0,0,0,.45)`, `.4`, `.35`, `.6`, `.8`.
- Cores da lateral no escuro: `#e7edf6`, `#c4d0e2`, `#ffffff18` (`EST:42-44`).
- Resultado por questão: bordas e textos fixos `#9fd39b`/`#0f5e17`, `#f1a99f`/`#a3190a`, `#e8cf72`/`#7a4800` (`LEG:746-748`).
- Arquivar no escuro: `#ff9c8e` (`arquivamento.css:1`). Sombras `#00153420` e `#00153430` (`EST:20,119`).
- Visor da câmera: preto, branco e transparências fixos (`LEG:857-868`).
- Raios fora da escala: 12 px nos diálogos e filtros, 16 px no diálogo de turma, `12 12 8 8` na folha Mais, `99px` literal no lugar de `radius-pill`.
- Escala `--text-*` quase sem uso fora dos componentes `g-*`. O título TW2 tem 24 px; `--text-2xl` tem 28 px.

### 5. Contraste

| Par | Claro | Escuro |
|---|---|---|
| `success` sobre `success-light` (badge, aviso) | 4,02:1 | 3,39:1 |
| `atencao` sobre `atencao-light` (avatar verde) | 4,21:1 | 6,61:1 |
| `danger` sobre `danger-light` (badge) | 4,66:1 | 3,34:1 |
| `danger` sobre `surface` (texto de erro, Sair) | 5,49:1 | 2,90:1 |
| `success` sobre `surface` (`kpi-trend.up`) | 4,59:1 | 3,48:1 |
| `border` sobre `surface` (borda de controle) | 1,53:1 | 1,39:1 |

O escuro não redefine `success`, `warn`, `danger` nem `faixa`. Só avisos e resultados recebem `texto-sucesso` e `texto-erro`.

## Outras pendências

6. Escala de telas grandes: o ADR-0057 e o CLAUDE.md pedem escala; `.tw2-app` a anula na área logada; o doc 09 §12 diz que ela não rege mais a casca. Os três textos não concordam.
7. Fonte: o token legado `--font-body` lista Figtree, mas a área logada usa a fonte do sistema. A Figtree normal só carrega nas páginas públicas, por CDN externo.
8. Tema: o app não segue `prefers-color-scheme`. O seletor `html[data-tema="escuro"]` em `arquivamento.css` não tem script que o defina.
9. `--shadow-sm` é `none`, mas o card TW2 usa a sombra do Tailwind.
10. `--container` (1280 px) e `--margem` não agem na casca TW2.
11. Toast "atenção" citado no doc 09 §9 não existe no código.
12. `x-carregando` sem `role` padrão; o doc 09 §6 diz que ele tem `role="status"` e `aria-busy`.
13. `.sr-only` sem regra global na área logada. Exemplo afetado: a `legend.sr-only` do modal de imagem fica visível.
14. `.field-error` e `.progress-track` sem regra global.
15. Ajuda no título só nos widgets de painel. Turmas ainda usa `details.tu1-ajuda` ("Saiba mais").
16. Filtros em modal só em Turmas. As outras listas usam `form.filtros-cel` em linha.
17. JavaScript em linha: 23 atributos `onclick`, `onsubmit` e `onchange` em 10 views, contra o ADR-0023. `alert()` em `public/js/correcao-acompanhamento.js:86`, contra o ADR-0025.
18. Regras duplicadas no legado (`.tela-rel-prova`, `.row-between`, `.table-wrap`, `.stepper`, `.kpi-grid`).
19. Breadcrumb em dois formatos: `<ol><li>` dinâmico e `<span class="sep">` manual.
20. ADRs substituídos ainda citados como regra (ADR-0033 e ADR-0003).
21. Ativos sem referência: `favicon-48x48.png`, `favicon-96x96.png`, `android-chrome-*.png`, `manifest.json` e os atlas `*-v1.png` de avatares.
22. Régua documentada (`LEG:236-240`: lg 1024–1439) diferente dos cortes reais (1100 e 1280).
23. Termo: o botão "Visualizar gabarito" da revisão abre o cartão do aluno. O glossário define gabarito como as respostas oficiais. A FN-079 troca o rótulo por "Ver cartão inteiro".
24. Card da câmera e visor com estilos em linha e caractere ✕ no lugar de `x-icone`.
