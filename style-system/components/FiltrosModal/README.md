# Filtros em modal

Os filtros de uma lista abrem num diálogo a partir do botão de ícone Filtrar em `x-pagina`; Aplicar envia por GET.

## Quando usar

- Use em toda lista nova com filtros (ADR-0036).
- Hoje só a lista de Turmas usa este padrão. As outras listas ainda usam `form.filtros-cel` em linha (Divergências, item 27).

## Comportamento

1. O botão de ícone `filtro` em `x-pagina` abre `dialog.tw2-filtros-dialog`.
2. Aplicar filtros envia o formulário por GET.
3. Cancelar e Escape descartam o rascunho e devolvem o foco ao botão.
4. Limpar campos esvazia os campos sem enviar.
5. Com filtro ativo, mostre `p.tw2-filtros-aplicados` abaixo do título: "Dados filtrados: Busca: …; escola: …; ano letivo: …."

## Medidas

| Parte | Regra | Fonte |
|---|---|---|
| Caixa | `min(520px, 100% - 32px)`, raio 12, borda `border`, fundo `surface` | `EST:143` |
| Backdrop | `#00153480` (valor fixo) | `EST:144` |
| Cabeçalho | `.modal-head` padding 16, título 20/28 600, fechar 44 × 44 | `EST:120-122` |
| Rodapé | `.modal-foot` à direita, gap 8, padding 16 | `EST:124` |
| Resumo ativo | 12/18 itálico `muted` | `EST:142` |

## Acessibilidade

- `aria-labelledby` no título do diálogo.
- Mostre na tela que a lista está filtrada (doc 09 §5.1).

## Regras por faixa

- O diálogo fica centrado em todas as faixas. Não há versão em folha inferior (lacuna 11).

ADRs: 0036.
