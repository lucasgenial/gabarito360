---
id: ADR-0057
titulo: O app é responsivo; toda tela e todo mockup atendem a todas as faixas de aparelho
estado: Aceita
substituida_por: []
data: 2026-11-11
afeta_funcionalidades: [todas]
afeta_caminhos: ["apps/web/resources/views/**", "apps/web/public/css/**", "apps/web/public/js/**", "mockups/**", "style-system/**"]
verificacao: revisao-ia
regras: []
agents_secao: Interface
agents_ordem: 16
agents_resumo:
  - "O app é responsivo. Toda tela e todo mockup atendem a todas as faixas: celular pequeno, celular, tablet (retrato e paisagem), notebook, desktop e telas grandes até 4K (3840 px), com regras próprias para toque."
  - "Confira cada tela nas larguras de referência 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro. Registre a conferência por texto (ADR-0049)."
  - "Use os cortes canônicos (400, 720/721, 767/768, 1023/1024, 1099/1100, 1279/1280 px e altura 699/700 px), `pointer: coarse` e consultas de contêiner. Um corte novo exige registro no design system."
---
# ADR-0057 — O app é responsivo; toda tela e todo mockup atendem a todas as faixas de aparelho

**Data:** 2026-11-11
**Estado:** Aceita
**Decisor:** responsável (lucasgenial)
**Origem:** pedido do responsável em 11/11/2026, na criação do design system a partir da homologação
**Afeta:** todas as funcionalidades com interface; [ADR-0056](0056-app-atual-como-referencia-visual.md); [09 Interface e design system](../arquitetura/09-interface-e-design-system.md)

## Contexto

O Gabarito360 é usado no celular do professor, no tablet, no notebook da escola e em monitores grandes de secretarias. O CSS do app já tem regras específicas para cada faixa: navegação inferior até 767 px (ADR-0032), tabela em cards até 720 px (ADR-0022), lateral expandida por padrão a partir de 1280 px, painel lateral de documento a partir de 1100 px, alvos de 44 px em telas de toque e consultas de contêiner. Essas regras estavam espalhadas e não eram exigidas de forma explícita em mockups e telas novas.

## Decisão

O responsável decidiu: "Olha tem versões diferentes para tablets, celular e telas até 4k, e para telas pequenas. Tem regras muito específicas para cada tipo de aparelho e tela, o design system deve contemplar isso, pois os mockups têm que ser feitos para atender a tudo isso." e "Adicione isso na documentação inicial, é regra! O app é responsivo!"

1. O app é responsivo. Toda tela nova ou alterada, e todo mockup, atende a todas as faixas abaixo.
2. Faixas canônicas (cortes que já existem no CSS e no JS de `apps/web/public/`; inventário completo no design system, `style-system/`):

| Faixa | Largura | Regras principais |
|---|---|---|
| Celular pequeno | 320 a 400 px | Casca de celular. Tabelas em cards. Ações de página viram ícone de 44 px em contêiner abaixo de 24rem. |
| Celular | 401 a 720 px | Navegação inferior e folha "Mais" (ADR-0032). Lateral e rodapé de versão ocultos (ADR-0044). Tabelas em cards. Alvos de 44 px. |
| Celular largo e tablet pequeno | 721 a 767 px | Casca ainda de celular. A tabela volta a ser tabela, com rolagem dentro do card. |
| Tablet retrato | 768 a 1023 px | Lateral compacta (80 px) por padrão, margem lateral de 16 px, rodapé de versão visível. Alvos de 44 px em telas de toque. |
| Tablet paisagem e notebook | 1024 a 1279 px | Lateral compacta por padrão. Edição de painéis disponível a partir de 1024 px de largura e 700 px de altura. Painel lateral de documento a partir de 1100 px. |
| Notebook e desktop | 1280 a 1919 px | Lateral expandida (256 px) por padrão. As telas ocupam toda a área útil (ADR-0021). |
| Telas grandes | 1920 a 3840 px (4K) | Área logada sem escala de interface (`.tw2-app` fixa a escala em 1); as grades crescem pela área útil. A escala 1,125, 1,25 e 1,5 vale só nas páginas públicas e de acesso. ⚠ Lacuna: a área logada não tem regra própria para 4K; a decisão fica com o responsável. |

3. Regras transversais:
   - Em telas de toque (`pointer: coarse`), todo alvo tem pelo menos 44 × 44 px.
   - Componentes que dependem do espaço do contêiner usam consultas de contêiner (`@container`), não só a largura da janela.
   - `prefers-reduced-motion` desliga animações e transições.
   - A impressão (`@media print`) esconde a casca.
4. Larguras de referência para conferir telas e mockups: 320, 360, 390, 720, 768, 1024, 1280, 1440, 1920, 2560 e 3840 px, nos temas claro e escuro.
5. Um corte novo fora da tabela exige registro no design system (`style-system/`) e no [09](../arquitetura/09-interface-e-design-system.md).

## Consequências

- O design system (`style-system/` e o artifact Design System) documenta as faixas, os cortes e o que muda em cada uma.
- O mockup de uma funcionalidade mostra todas as faixas, com seletor de aparelho.
- O revisor `ui-ux` reprova uma tela ou um mockup sem conferência registrada nas larguras de referência.
- Os cortes avulsos que já existem no CSS (por exemplo 359, 380, 440, 480, 500, 600, 640, 700, 800, 860 e 899 px) ficam como divergência a consolidar numa adequação. Eles não são modelo para telas novas.
- A ausência de regra própria para telas 4K na área logada fica como lacuna, com decisão do responsável.

## Verificação

- O revisor `ui-ux` confere, em cada PR com interface, o registro por texto das larguras de referência nos dois temas.
- O AGENTS.md gerado cita esta regra na seção Interface.
