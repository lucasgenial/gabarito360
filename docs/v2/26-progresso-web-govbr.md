# Registro de Execução — Web V2 (reconstrução fiel ao mockup gov.br)

## Status: Em execução (V2-03 — fundação visual)

A reconstrução da web V2 é **do zero, fiel ao mockup `style-system/`** (gov.br
Design System). Decisão do usuário (2026-06-15): **nada do web design anterior
(fase R4) é reaproveitado** — a paleta saturada (azul `#183EFF`, verde `#00D000`)
foi descartada em favor do gov.br institucional (`#1351b4` / `#168821`, fontes
Rawline/Raleway, raios 4–8px, govbar). Fonte canônica: `style-system/css/gov.css`
e `style-system/brand-spec.md`.

## Fatia 1 — Fundação + Login (entregue e validada no navegador)

- **`resources/css/app.css`**: portado fielmente de `style-system/css/gov.css`
  (tokens gov.br, reset, govbar, header azul institucional, botões, forms, cards,
  tabelas, KPIs, badges, breadcrumb, tema escuro por `data-theme`). Substitui a
  fundação R4 (o `tokens.css` R4 deixou de ser importado).
- **`layouts/guest.blade.php`**: shell de acesso com fontes Raleway/Roboto Mono
  (Google Fonts) e `@vite` do CSS.
- **`auth/login.blade.php`**: tela de login fiel a `style-system/login.html`
  (2 colunas, faixa azul com brand/headline/stats, abas Entrar/Cadastrar, campos
  com ícone), ligada ao login real (`route('login')`, CSRF, erros).
- **`DesignSystemTest`** reescrito para o gov.br: valida tokens (`--accent: #1351b4`),
  classes-base, layout guest, login fiel e **contraste WCAG AA** das cores gov.br.

**Validação visual:** `npm run build`, `php artisan serve` e preview no navegador
(preferência do usuário). Login renderiza fiel ao mockup nos viewports desktop
(2 colunas, faixa azul a 100vh) e estreito (`<860px` oculta a faixa). Sem erros
de console. Suíte Web verde (16 testes).

### Ambiente de execução local
- `.claude/launch.json` (`web`, porta 8010) para o preview gerenciar
  `php artisan serve`.
- Local sem Docker: `.env` dev aponta para o MariaDB local (3306, base
  `gabarito360_testing`) e `SESSION_DRIVER=file` (ver memória `db-testing-local`).

## Próximas fatias

1. **Shell autenticado** (govbar + header azul + nav + menu de conta + breadcrumb),
   fiel a `dashboard*.html`/`_shell`, portando `style-system/js/app.js`
   (tema claro/escuro, menu do usuário).
2. **Telas por módulo** seguindo o plano (V2-04 conta/config → V2-05 organização/
   acadêmico → V2-06 provas/correção/relatórios/dashboards), cada uma validada no
   navegador contra o HTML correspondente e ligada à API v2.
3. **Limpeza** dos artefatos R4 (componentes `x-ui` antigos, `tokens.css`, layout
   admin, views portal R4) à medida que cada tela é reconstruída.
