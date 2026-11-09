# F4 — carregamento e interação: planejamento de entrada

**Data:** 09/11/2026. **Status:** F4-A em implementação; F4-B a F4-D planejadas. **Escopo:** somente homologação. Produção fora do escopo.

## Ponto de partida

Após a F3 ([198](198-f3-arquivos-cache.md)), a segunda navegação transfere de 15 KB a 54 KB nos percursos medidos. O custo restante está no trabalho feito em cada abertura de tela, não nos arquivos:

| Achado (F1, [196](196-diagnostico-desempenho-f1.md)) | Efeito |
|---|---|
| A turma faz até sete chamadas sequenciais à API ao abrir a visão geral: turma, professores, usuários professores, disciplinas, opções docentes, painel e vínculos | Dados de abas fechadas entram no tempo da primeira tela |
| A correção pediu 48 miniaturas na mesma janela, numa fila de 4, inclusive as que estavam fora da tela | Uso dos processos PHP da API e do BFF para imagens que ninguém viu |
| Transformação UR4 de cerca de 150 ms na turma | Varreduras repetidas de referências por resposta |

Paralelizar as chamadas do BFF não entra nesta etapa. A API da homologação tem dois processos FPM, e uma rajada por página repete o padrão que esgotou os processos em 30/10/2026. Capacidade e paralelismo ficam na F6, com medição.

## Subdivisões

| Parte | Entrega | Critério |
|---|---|---|
| F4-A | Miniaturas da correção por visibilidade: `IntersectionObserver` com margem de 400 px. A miniatura só entra na fila de 4 cargas quando chega perto da área visível. Sem o observador, mantém o comportamento anterior | Nenhum pedido de miniatura fora da área visível; estados de carga, erro e nova tentativa preservados; testes JS |
| F4-B | Abas da turma sob demanda: a visão geral carrega primeiro; Alunos, Disciplinas e Acesso buscam seus dados na primeira abertura da aba, pela ponte UR4 com CSRF e revisão de contexto | Menos chamadas na abertura; estados de carga e erro por aba; foco e teclado preservados; revisão UI/UX |
| F4-C | Referências UR4 deduplicadas na transformação da resposta | Mesmo HTML de saída; menos tempo de transformação medido |
| F4-D | Ciclo de vida dos gráficos e observadores: descartar instâncias e observadores ao trocar de região | Sem callbacks tardios aplicados; memória estável em trocas repetidas |

## Regras

O comportamento de autorização não muda: a API revalida cada pedido, e nenhuma resposta passa a ser guardada em cache. Toda mudança de interface passa pela revisão independente de UI/UX antes do merge (AGENTS.md). Publicação somente na homologação, com o operador e as conferências de dados da F3.
