# Plano: identidade própria e visual menos genérico

Situação em 30/10/2026: aprovado. V1 e V2 concluídas, V3 em andamento.

**Decisão já tomada:** direção "Atual refinada": azul próprio, verde-água para o que pede atenção e fonte Figtree. Login aprovado: mantém a composição e recebe só as novas cores e a nova fonte.

## Crítica de design

Foram vistas a página inicial, o painel do diretor e a lista de provas, em 1440 px, além do levantamento do CSS e das views.

### Impressão geral

O sistema funciona bem e está organizado. A aparência genérica vem da repetição de fórmulas conhecidas: faixa azul com degradê, cartões em tudo, emojis como ícones e frases de efeito. A maior oportunidade é mostrar o próprio produto: cartão-resposta, notas e turmas.

### O que passa a impressão de feito por IA

| Onde | O que acontece | Por que parece genérico | Correção |
|---|---|---|---|
| Página inicial | Degradê azul, palavra destacada e selo arredondado | É a abertura padrão de páginas geradas | Fundo liso e uma tela real do produto com dados fictícios |
| Página inicial | Painel lateral com emojis para Câmera, Notas e Relatórios | Emojis no lugar de produto | Ícones próprios, ou o fluxo real do cartão até a nota |
| Página inicial | "Corrija provas inteiras em minutos, não em dias" e a mesma fórmula em outras frases | A construção "X, não Y" soa pronta | Frases diretas sobre o que o professor faz |
| Painel | Título menor sobre cada bloco, 34 ocorrências, como "DESEMPENHO POR TURMA" | Um rótulo sobre outro título, sem informação nova | Um título claro por bloco |
| Painel e listas | Fileiras de números em cartões iguais; a lista de provas tem 5 números para 4 provas | Números sem decisão por trás | Só o que pede ação, como "5 cartões a revisar" |
| Sistema inteiro | Tudo dentro de cartão com a mesma borda, cantos e sombra | Nada se destaca | Tabelas e listas direto na página; cartão só onde separa algo |
| Sistema inteiro | 41 emojis em views e 826 estilos soltos no HTML | Aparência e ajustes inconsistentes | Ícones do sistema e estilos no CSS |
| Textos | "Consolidação das últimas provas", "Gestão e docentes de referência" | Linguagem de documento, distante da escola | Palavras do dia a dia: "Últimas provas por turma" |

### Usabilidade

| Problema | Gravidade | Correção |
|---|---|---|
| A página inicial oferece "Começar grátis" e 14 dias grátis, mas o cadastro público foi removido e o pagamento não está configurado | Alta | Ver a decisão 1 |
| O painel repete o nome da escola no título e no bloco logo abaixo | Média | Mostrar uma vez |
| Tabela do painel apertada, com "Última prova" e "Plano de ação" quebrando em duas linhas | Média | Reservar largura e simplificar as colunas |

### Consistência

O CSS tem 27 tamanhos de fonte diferentes e 90 regras de cantos arredondados, com valores variados. A proposta é reduzir para uma escala curta de tamanhos, dois raios de canto e uma sombra.

### Acessibilidade

Contraste calculado para as novas cores:

| Par | Contraste |
|---|---|
| Azul sobre branco | 7,9:1 |
| Texto secundário sobre fundo | 5,6:1 |
| Verde-água sobre branco | 4,9:1 |
| Azul sobre fundo escuro | 8,2:1 |
| Texto secundário sobre fundo escuro | 7,2:1 |

Todos passam do mínimo de 4,5:1 para texto normal. A etapa V1 ainda precisa conferir os demais pares nas telas reais.

### O que já funciona

- O login, que fica como está na composição.
- O menu lateral e a organização por perfil.
- A clareza da lista de provas e da nova tela de revisão.
- A página sem números inventados, com pesquisas e fontes.

## Etapas

### V1. Base visual

- Aplicar cores da Direção 1 nos modos claro e escuro e trocar a fonte para Figtree.
- Reduzir tamanhos de fonte, cantos e sombras, e tirar os degradês.
- Revisar o menu lateral, para ele não ser o elemento mais forte da tela.
- Atualizar o `CLAUDE.md`, que ainda cita o padrão gov.br.

### V2. Página inicial e páginas públicas

- Trocar a abertura com degradê por uma composição com o produto real e dados fictícios.
- Reescrever os textos e substituir os emojis.
- Aplicar a decisão 1 nos botões.

### V3. Painéis e listas

- Revisar os painéis por perfil, as listas e os relatórios.
- Retirar rótulos duplicados e números sem ação.
- Tirar cartões onde eles não ajudam e corrigir as tabelas apertadas.
- Levar para o CSS os estilos soltos das telas alteradas.

### V4. Ícones e textos do sistema

- Trocar os emojis restantes, inclusive na câmera, por ícones do sistema.
- Revisar botões, avisos e mensagens de erro com a mesma linguagem.

### V5. Gráficos modernos

Pedido do usuário em 30/10/2026. Biblioteca escolhida: ECharts, a mesma usada no ECOAR. Ela funciona nas telas Blade, sem React.

- Carregar o ECharts só nas telas com gráficos.
- Criar um componente único, com as cores e a fonte da identidade e suporte aos modos claro e escuro.
- Trocar os gráficos desenhados à mão por esse componente, mantendo a impressão em A4.
- Plano detalhado e aprovação antes de começar.

## Verificação

- Auditoria antes de cada PR, em todas as telas e 6 larguras.
- Capturas de antes e depois das telas principais, nos modos claro e escuro.
- Cada etapa em um PR, com aprovação antes do merge.

## Decisões a confirmar

1. **Botão principal da página inicial:** a recomendação é "Falar com a gente", levando ao Fale Conosco, e retirar a oferta de 14 dias até o pagamento funcionar.
2. **Imagem da página inicial:** a recomendação é usar telas reais do sistema com dados fictícios, sem fotos de banco de imagens.
