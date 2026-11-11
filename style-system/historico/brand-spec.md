# Gabarito360 — Brand Spec (gov.br Design System)

Fonte: o usuário referenciou `docs/SDGB.md` + `ui_token_gov_brasil.json` (não
presentes no workspace) e confirmou a modificação para a paleta institucional
**gov.br** — o Padrão Digital de Governo brasileiro. Os tokens abaixo seguem os
valores oficiais conhecidos do gov.br DS (não inventados de memória de marca
proprietária; são padrões públicos do governo federal).

## Tokens de cor (OKLch + hex de referência gov.br)

| Token        | Hex gov.br | OKLch                          | Uso |
|--------------|-----------|--------------------------------|-----|
| `--bg`       | `#f8f8f8` | `oklch(97.5% 0.001 240)`       | canvas |
| `--surface`  | `#ffffff` | `oklch(100% 0 0)`              | cards |
| `--fg`       | `#1c2733` | `oklch(25% 0.02 250)`          | texto primário |
| `--muted`    | `#555555` | `oklch(48% 0.005 250)`         | texto secundário |
| `--border`   | `#cccccc` | `oklch(85% 0.003 250)`         | bordas |
| `--accent`   | `#1351b4` | `oklch(46% 0.16 263)`          | azul institucional (Blue Warm Vivid 50) |
| `--accent-2` | `#168821` | `oklch(53% 0.15 150)`          | verde sucesso (Green Cool Vivid 50) |
| `--warn`     | `#ffcd07` | `oklch(85% 0.16 92)`           | amarelo atenção (pendência) |
| `--danger`   | `#e52207` | `oklch(56% 0.22 30)`           | vermelho erro |

### Escala de azul gov.br
- `--accent-dark` `#0c326f` (Blue Warm Vivid 70 — hover/active)
- `--accent-light` `#c5d4eb` (Blue Warm Vivid 10 — fundos sutis)

## Tipografia
- **Display + body:** `'Rawline', 'Raleway', -apple-system, system-ui, sans-serif`
  (Rawline é a fonte oficial do gov.br; Raleway é o fallback público recomendado)
- **Mono / numéricos:** `'Roboto Mono', ui-monospace, monospace` para notas, IDs, scores
- Headings: peso 700–900, tracking levemente negativo em ≥32px
- Body: 16px / 1.5, peso 400

## Posturas de layout (gov.br DS)
1. Raio: botões/inputs 4–8px (gov.br usa cantos discretos, não pill); cards 8px
2. Bordas hairline `#cccccc`; sem sombras pesadas — elevação sutil
3. Header institucional azul `#1351b4` com faixa superior; breadcrumb abaixo
4. Acento azul em CTAs e links; verde reservado para estados de sucesso/aprovado
5. Componentes densos e funcionais (é um sistema de gestão, não marketing)
