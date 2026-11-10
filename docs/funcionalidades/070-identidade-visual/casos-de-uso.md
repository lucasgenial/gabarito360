# 070 — Identidade visual e gráficos — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis autenticados e visitantes. Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Os casos abaixo descrevem garantias transversais de apresentação. Os dados mostrados nos gráficos pertencem às funcionalidades de origem (UC-006 e UC-007 do docs/07 (`docs/07-casos-de-uso.md`); FN-049, FN-052, FN-053).

---

## UC-070-01 — Ver telas com a identidade própria em claro e escuro

**Ator:** qualquer usuário ou visitante
**Permissão:** a da tela visitada
**Escopo:** o da tela
**Pré-condições:** nenhuma.
**Pós-condições:** nenhuma.
**Tela/rota:** todas as telas; tokens em `apps/web/public/css/app.css`.

**Fluxo principal:**
1. O usuário abre uma tela.
2. O sistema aplica cores, fonte Figtree, escalas e ícones da identidade, conforme o tema.

**Fluxos alternativos e de erro:**
- 2a. Tema escuro: tokens próprios com contraste equivalente.

**Regras de negócio:** docs/26 (direção "Atual refinada").

**Critérios de aceite:**
- [x] Sem emojis como ícones (V4).
- [x] Auditoria em 6 larguras aprovada em cada etapa.

**Situação:** Implementado — evidência: PR #43 a #46.

---

## UC-070-02 — Ler um gráfico com tabela equivalente

**Ator:** usuário com acesso a relatório, painel ou acompanhamento da correção
**Permissão:** a da tela (por exemplo `relatorio.turma`, `correcao.acompanhar`)
**Escopo:** o da tela
**Pré-condições:** tela com dados para gráfico.
**Pós-condições:** nenhuma.
**Tela/rota:** relatórios, painéis e correção que usam `<x-grafico>`.

**Fluxo principal:**
1. O usuário abre a tela; o ECharts carrega só ali.
2. O gráfico aparece em SVG com cores e fonte da identidade.
3. A tabela com os mesmos números fica disponível para leitura sem cor e leitores de tela.

**Fluxos alternativos e de erro:**
- 2a. Impressão A4: SVG nítido.
- 2b. Sem dados: estado vazio da tela de origem.

**Regras de negócio:** docs/27 (regras de desenho e cores das séries).

**Critérios de aceite:**
- [x] Média comparada à meta no lugar das roscas (G2).
- [x] Barra de progresso na correção (G3).

**Situação:** Implementado — evidência: PR #47 a #49; `components/grafico.blade.php`.
