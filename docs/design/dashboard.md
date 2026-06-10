# Padrões de Dashboard

## 1. Objetivo

Dashboards devem priorizar acompanhamento operacional, clareza dos critérios e tomada de ação. Durante uma aplicação aberta, dados em tempo real devem ser identificados como provisórios.

## 2. Estrutura

Ordem recomendada:

1. contexto, título e atualização mais recente;
2. filtros ativos;
3. KPIs principais;
4. alertas e pendências acionáveis;
5. gráficos e tabelas detalhadas;
6. estados de conexão, atualização e contingência.

## 3. Dashboard Card

Usar `components.dashboardCard`:

- altura `120px`;
- raio `16px`;
- ícone `48px`;
- valor `32px`.

Cada card deve possuir rótulo, valor, unidade quando necessária, contexto temporal e indicação de indisponibilidade. Divisão por zero deve resultar em indicador indisponível, não em zero enganoso.

## 4. Gráficos

- Usar a sequência `charts.series`.
- Usar `charts.gridLight`/`gridDark` e `charts.textLight`/`textDark`.
- Incluir título, legenda, unidade, período e critério.
- Não usar somente cor para distinguir séries; combinar rótulos, padrões, ícones ou formas.
- Oferecer alternativa tabular acessível para dados relevantes.
- Não usar gráficos 3D ou decoração que prejudique comparação.

Bibliotecas recomendadas pelo SDGB: Chart.js, ApexCharts ou ECharts. A escolha deve respeitar ADR e capacidade de acessibilidade.

## 5. Estados em tempo real

- Exibir última atualização e estado de conexão.
- Durante reconexão, preservar o último snapshot com aviso explícito.
- Em contingência por polling, informar que a atualização pode estar atrasada.
- Alertas devem apontar ação possível, como revisar leitura ou localizar pendência.
- Eventos não devem transportar dados pessoais completos.

## 6. Componentes do MVP

- KPI Card.
- Barra ou anel de progresso com alternativa textual.
- Lista de últimas leituras.
- Lista de alunos pendentes.
- Painel de alertas de leitura.
- Filtros por aplicação e status quando aplicável.
- Estado vazio, carregando, erro e reconexão.

## 7. Evolução

O MP-046 fornece o snapshot consistente sem criar interface. O MP-046A cria os componentes visuais compartilhados. O MP-048 monta o dashboard operacional em tempo real, e o MP-049 adiciona resiliência e validação de latência.

