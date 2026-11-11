# Carregando

O componente `x-carregando` mostra a marca com o rótulo e um esqueleto de barras enquanto o conteúdo de uma região carrega.

## Quando usar

- Use no conteúdo pendente e mantenha a casca visível (doc 09 §6).
- Não use preloader de tela inteira, exceto na troca de vínculo.
- Ao falhar, pare a animação e ofereça ações seguras. Não reenvie POST. Não apague regiões que já carregaram.

## O que o consumidor fornece

- `rotulo` (padrão "Carregando…") e `formato` (`linhas` ou `pagina`).
- `role="status"` como atributo. O componente não o define (Divergências, item 22). Exemplo real: `correcao/show.blade.php:69`.

## Variantes

| Variante | Regra | Fonte |
|---|---|---|
| `g360-carregando-linhas` | altura mínima 160, padding 16; 3 barras de 20 px, a última com 72% | `LEG:131-137` |
| `g360-carregando-pagina` | altura mínima 360; barras de 70 px | `LEG:138-139` |
| Esqueleto de navegação | `navegacao-esqueleto`: KPIs em 4 colunas (2 até 767), blocos com raio 8 | `estado-tela.css:31-40` |

## Movimento

- Marca: `g360-respirar`, 1,8 s. Barras: `g360-esqueleto`, 1,8 s, brilho de `surface-2` a `border-soft`.
- `prefers-reduced-motion`: sem animação (`LEG:145`).

ADRs: doc 09 §6.
