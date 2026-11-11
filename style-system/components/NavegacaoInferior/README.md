# Navegação inferior

A navegação inferior `.nav-mobile-app` é o menu principal até 767 px: quatro atalhos do perfil e o botão Mais, que abre a folha com todas as opções.

## Quando usar

- Só até 767 px. Acima disso ela fica oculta e a Lateral assume (ADR-0032).
- Mostre 4 atalhos por perfil e Mais (doc 09 §11.1). Exemplo do professor: Painel, Corrigir, Provas, Turmas.

## Partes

| Parte | Regra | Fonte |
|---|---|---|
| Barra | grade de colunas iguais, fixa no fim, `z-index` 50, fundo `surface`, borda superior `border`, `safe-area` | `EST:86` |
| Item | coluna, gap 4, altura 56, 12/16, cor `muted`; ativo `accent` 600 | `EST:87-88` |
| Folha Mais | `<dialog>` modal, `inset: auto 8px 8px`, raio `12 12 8 8`, backdrop `#00153465` | `EST:89-91` |
| Cabeçalho da folha | título 16/24 600; Fechar 44 × 44 | `EST:92-94` |
| Itens da folha | 52 px, gap 16, divisor `border` | `EST:96` |

## Comportamento

- Mais abre a folha com `showModal()`. O foco vai para Fechar.
- Clique fora ou num link fecha a folha. O foco volta para Mais.
- Com a folha aberta, a página não rola.
- Ao passar de 768 px, a folha fecha.

## Acessibilidade

- `aria-label="Menu principal (celular)"` na barra; `aria-current="page"` no item atual.
- Mais tem `aria-haspopup="dialog"`, `aria-controls` e `aria-expanded`.

## Regras por faixa

- Até 767 px: visível. O conteúdo ganha padding inferior de 92 px mais `safe-area`.
- O rodapé de versão fica oculto nessa faixa (ADR-0044).

ADRs: 0031, 0032, 0044.
