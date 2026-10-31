# Plano: gráficos com ECharts (V5)

Situação em 30/10/2026: aprovado, com a troca das roscas. G1 e G2 concluídas, G3 em andamento.

## Decisão já tomada

A biblioteca é o ECharts, a mesma usada no ECOAR. Ela funciona nas telas Blade, sem React.

## Situação atual

Os gráficos são desenhados à mão, em três formas:

| Onde | Forma atual | Dado |
|---|---|---|
| Relatório da turma | Barras SVG | Médias por prova e turma |
| Relatório do aluno | Barras SVG | Aluno comparado à turma |
| Relatórios consolidados | Barras SVG | Média por turma ou escola |
| Análise de questões | Barras SVG | Acerto por questão |
| Distribuição de notas | Barras SVG | Alunos por faixa de nota |
| Relatório da prova e da turma na prova | Rosca em `app.js` | Média comparada à meta |
| Acompanhamento da correção | Rosca em `app.js` | Cartões lidos |
| Painéis | Barras em HTML e `drawBarChart` | Médias por período e escola |

## Como vai funcionar

- O arquivo do ECharts fica no próprio sistema, com versão fixa, sem depender de CDN.
- Ele carrega só nas telas com gráfico, para não pesar nas demais.
- Um componente único, `<x-grafico>`, recebe os dados. Uma configuração central cuida de cores, fonte, modo claro e escuro, tamanho da tela e dicas ao passar o mouse.
- Os gráficos usam SVG, que sai nítido na impressão em A4.
- Cada gráfico mantém uma tabela com os mesmos números, para leitura sem cor e para quem usa leitor de tela.

## Regras de desenho

- Uma cor para uma série. Comparações usam as cores fixas abaixo, na mesma ordem.
- Uma turma ou prova mantém a mesma cor quando um filtro muda.
- Sempre um eixo só.
- Rótulos de valor só onde ajudam a decidir.
- Legenda sempre que houver duas séries ou mais.
- Verde, amarelo e vermelho ficam reservados para situação e vêm com texto.
- A média comparada à meta deixa de ser rosca. Rosca sugere parte de um todo, e uma nota não é isso. Entra um número grande com barra e marca da meta.
- O andamento da correção também vira barra de progresso.

## Cores das séries

| Ordem | Modo claro | Situação do validador |
|---|---|---|
| 1 | `#1d4f9e` | Aprovada |
| 2 | `#008a7c` | Aprovada |
| 3 | `#b7791f` | Aprovada |
| 4 | `#7b3fa0` | Aprovada |
| 5 | `#c2410c` | Aprovada |

A sequência clara passou em todas as verificações, inclusive daltonismo. O verde da série 2 é um pouco mais forte que o verde-água da interface, para funcionar em gráfico.

A sequência do modo escuro é `#5f8fe8`, `#14a88f`, `#b98527`, `#a278d4` e `#d9713c`, aprovada no mesmo validador.

## Etapas

### G1. Base

- Adicionar o ECharts, o componente `<x-grafico>` e a configuração central.
- Fechar as cores do modo escuro.
- Migrar a distribuição de notas, como primeiro caso completo, incluindo impressão.
- Ensinar a auditoria a carregar os novos arquivos.

### G2. Relatórios

- Migrar relatório da turma, aluno, consolidados e análise de questões.
- Trocar as roscas de média por número, barra e meta.
- Conferir a impressão em A4 de cada relatório.

### G3. Painéis e correção

- Migrar os gráficos dos painéis.
- Trocar a rosca da correção por barra de progresso.
- Remover `drawBarChart`, `drawDonut` e o CSS que deixar de ser usado.

Cada etapa vai em um PR, com aprovação antes do merge.

## Riscos

| Risco | Cuidado |
|---|---|
| O ECharts pesa cerca de 1 MB sem compressão | Carregar só nas telas com gráfico e avaliar a versão reduzida na G1 |
| Impressão pode cortar ou desenhar antes da hora | Testar A4 em cada relatório, com a tabela como reserva |
| O PDF gerado pela API usa outro caminho | Fica fora deste plano e não muda |
| Componente Blade dentro de JavaScript quebra a tela | Usar `<x-grafico>` só no HTML; a verificação automática já bloqueia esse erro |

## Verificação

- Auditoria em todas as telas e 6 larguras.
- Conferência visual de cada gráfico nos modos claro e escuro e em 375 px.
- Prévia de impressão em A4 dos relatórios.
- Validador de cores nos dois modos.

## Decisão a confirmar

A recomendação é trocar as roscas por número, barra e marca da meta. Se preferir manter as roscas, elas continuam, agora em ECharts.
