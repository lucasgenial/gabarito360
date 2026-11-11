# Marca

Arquivos reais de `apps/web/public/brand/` (commit `37ba645`). O vetor oficial é `apps/web/resources/brand/gabarito360-icon.svg`, igual a `gabarito360-icon.svg`. Hashes em `apps/web/resources/brand/assets-sha256.json`.

- `gabarito360-icon.svg` — Símbolo para fundo claro: círculo `#001534` (g360-navy) e check `#0588FC` (g360-blue). Fundo do carregamento `.g360-marca-carregando` (`marca.css:37`).
- `gabarito360-icon-dark.svg` — Símbolo para fundo escuro: círculo `#FFFFFF` e check `#0588FC` (`marca.css:38`).
- `gabarito360-icon.png` — Símbolo em PNG, fundo claro. Gerado por `scripts/branding/gerar_assets.py`.
- `gabarito360-icon-dark.png` — Símbolo em PNG, fundo escuro.
- `gabarito360-email.png` — Marca para e-mail, fundo claro.
- `gabarito360-email-dark.png` — Marca para e-mail, fundo escuro.
- `gabarito360-og.png` — Imagem de compartilhamento 1200 × 630 (`og:image`, `twitter:image`, `layouts/_marca-head.blade.php:19-28`).

Regras:

1. Use o arquivo como está. Não recolora, não recorte e não redesenhe.
2. No app, prefira o componente `x-marca`: ele põe o SVG inline e troca a cor do círculo pelo tema.
3. Os SVG têm cores fixas; eles não herdam `currentColor`. Escolha o arquivo pelo fundo.
4. Favicons e ícones de PWA ficam em `apps/web/public/` e não estão neste grupo.
