---
id: ADR-0063
titulo: Família de ícones própria em fonte (G360 Icones)
estado: Aceita
substituida_por: []
data: 2026-11-13
afeta_funcionalidades: [FN-082]
afeta_caminhos: ["apps/web/public/fonts/icones/**", "apps/web/public/css/g360-icones.css", "apps/web/resources/views/components/icone*.blade.php", "apps/web/resources/brand/**", "style-system/**", "mockups/**"]
verificacao: teste
regras: [R-ICONES]
agents_secao: Interface
agents_ordem: 50
agents_resumo:
  - "Use só a fonte G360 Icones para ícones de interface: <span class=\"g360i g360i-<id>\" aria-hidden=\"true\">."
  - "Não use emoji, caractere Unicode nem SVG avulso como ícone; ícone que falta entra na família pelo registro."
  - "Não mude o código de um glifo existente; ícone novo entra no fim de registro.json."
  - "Cor de ícone só por token (currentColor ou as classes de tom); nenhuma cor fixa."
---
# ADR-0063 — O Gabarito360 usa uma família de ícones própria, entregue como fonte

**Data:** 2026-11-13
**Estado:** Aceita (família completa aprovada pelo responsável em 13/11/2026)
**Decisor:** responsável (lucasgenial)
**Origem:** conversa "icone" do projeto, 13/11/2026; inventário de ícones da homologação (commit 3374ebf)
**Afeta:** [FN-082](../funcionalidades/082-familia-icones-g360/planejamento.md); style-system, seção Iconografia

## Contexto

O inventário da homologação encontrou 68 conceitos de ícone desenhados de 91 formas. Há duas famílias oficiais em conflito: `x-icone` (traço 24, 1,8, contorno) e `x-icone-estrutura` (Material Symbols, grade 960, preenchido), com 14 nomes iguais e desenhos diferentes. Há também 39 SVGs avulsos que redesenham ícones existentes e 80 usos de caractere ou emoji no lugar de ícone. O menu tem 19 itens e só 7 desenhos, porque `equipe`, `escola` e `prova` cobrem itens diferentes. O mockup de Plano e uso criou uma terceira família. O responsável pediu "uma família própria de ícones para toda a aplicação, mais personalizada", e depois "temos que fazer uma fonte nossa, com os nossos ícones".

## Decisão

- **Uma família só, própria, chamada G360 Icones**, entregue como fonte em duas variantes com os mesmos códigos: **G360 Icones** (contorno, padrão) e **G360 Icones Preenchido** (item ativo e destaque).
- **Especificação:** grade 24 × 24, área útil 20 × 20, traço 1,8, pontas e junções redondas (o mesmo traço do `x-icone` atual). Linhas-guia: círculo r 9, folha 5–19 × 3–21, paisagem 3–21 × 6–18. Folga mínima de 1,2 px entre traços.
- **Duotônico:** a variante preenchida no tom claro, sob o contorno no tom forte. Cor só por token: `--accent`, `--texto-sucesso`, `--texto-erro`, `--warn-fg`, `--serie-5`, `--purple-fg`, `--g360-blue`. Sem token rosa.
- **Fonte única:** os desenhos de traço em SVG (`src/`). A compilação gera o contorno das duas variantes, o TTF (instalação no Windows e no CorelDRAW), o WOFF2 (app) e o CSS.
- **Local no repositório:** a fonte única e os scripts ficam em `style-system/icones/`. Os arquivos compilados ficam em `apps/web/public/fonts/icones/` (WOFF2 e TTF) e `apps/web/public/css/g360-icones.css`. A pasta `saida/` é gerada e fica fora do Git.
- **Códigos fixos** na área de uso privado, a partir de U+E001, em `registro.json`. Ícone novo entra no fim. Nenhum código existente muda.
- **Um conceito, um desenho, um id.** Cada item do menu tem o próprio ícone.
- Ficam fora da fonte o símbolo da marca (SVG próprio) e as formas CSS que não são ícone (ponto de situação, amostra de cor da legenda).

## Consequências

- Passa a ser obrigatório usar a fonte para todo ícone de interface, com `aria-hidden="true"` e nome acessível no controle quando o ícone for o único conteúdo.
- Fica proibido usar emoji, caractere Unicode ou SVG avulso como ícone. A exceção do ✕ do visor da câmera deixa de existir, porque `fechar` entra na família.
- Fica proibido mudar o código de um glifo ou dar dois desenhos ao mesmo id.
- Substitui a parte "Material Symbols Outlined locais" do [ADR-0033](0033-previa-rodada-3-contexto-icones-e-acoes.md) e a regra "copie o SVG, não redesenhe" do style-system, seção Iconografia.
- `x-icone` e `x-icone-estrutura` passam a emitir o glifo da fonte, mantendo os nomes atuais como apelidos durante a migração.
- A entrada no repositório só acontece com a família completa aprovada (decisão do responsável em 13/11/2026). Até lá, os lotes ficam fora do Git.

## Verificação

- Regra **R-ICONES** (planejada; script a criar). Ela reprova o PR se encontrar:
  - `<svg` de ícone fora de `resources/brand` e da marca;
  - emoji ou os caracteres ✕ × ← → ▲ ▼ ↕ ↑ ↓ ‹ › ✓ ⏳ ⏱ usados como ícone em Blade, JS ou CSS;
  - código alterado em `registro.json` em relação à base;
  - cor fixa em regra de `.g360i`.
- Ao rodar a compilação a partir de `src/`, o resultado deve ser idêntico aos arquivos versionados da fonte.
