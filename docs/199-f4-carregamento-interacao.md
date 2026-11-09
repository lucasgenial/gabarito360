# F4 — carregamento e interação: planejamento de entrada

**Data:** 09/11/2026. **Status:** F4-A e F4-B publicadas na homologação; F4-C e F4-D em revisão, para publicação conjunta. **Escopo:** somente homologação. Produção fora do escopo.

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

## F4-B — abas da turma sob demanda

Na abertura pela visão geral, a turma deixa de pedir à API os professores com acesso, a lista de professores da escola, as disciplinas e as opções docentes. Isso tira até quatro chamadas sequenciais da primeira tela. As abas Acesso e Disciplinas mostram o carregador padrão (`G360Carga`) e buscam um fragmento HTML na primeira abertura, pela operação `turmas.secao`:

- A URL do fragmento é emitida pelo SSR e convertida em referência UR4. O pedido leva CSRF e a revisão do contexto (`RevisaoAcesso::DADOS`).
- O BFF revalida a turma na API antes de montar o fragmento. Responde `no-store` e recusa seção desconhecida (422), perfil administrativo, Acesso para perfil pessoal e Disciplinas fora do escopo DC1 (404).
- Retornos de ações (`?secao=acesso|disciplinas`), rascunhos de atribuição (`retorno_componente`) e erros de validação continuam renderizados por inteiro no servidor, com foco e valores preservados.
- Trocar de aba ou redimensionar a janela não repete a busca. Uma falha mostra "Não foi possível carregar este bloco" com o botão "Tentar novamente", sem apresentar lista vazia.
- A confirmação de formulários (`data-turma-confirmar`), o texto da atribuição e o vínculo do professor selecionado passam a usar delegação no documento e funcionam também no conteúdo que chega depois. CSS e JS das abas saem dos parciais para a própria tela.
- Sem JavaScript, a aba oferece o link que abre a mesma seção renderizada no servidor.

A aba Alunos já vem nos dados da própria turma e continua no SSR. Testes: três cenários PHP, com adiamento, fragmento, negativas e falha, e três testes JS (`turma-abas-adiadas.test.mjs`).

Revisão independente (UI/UX, acessibilidade e segurança): **aprovada com ressalvas**, sem bloqueios. Incorporado neste PR:

- Erros de validação de `vinculo_id` ao conceder ou retirar acesso voltam para a tela da turma, na aba Acesso. Antes, um formulário vindo do fragmento voltaria para o endpoint JSON.
- Um atributo de URL inválido cai no estado de erro do bloco, sem interromper as abas.
- O carregador fica num card, como o conteúdo que o substitui.
- Com o link legado `#professores`, a tela rola até o título depois da carga.
- O fragmento pede a turma sem seguir redirecionamentos, como a tela.
- Testes adicionados para: negativas de perfil pessoal e administrativo, retorno e rascunho renderizados no servidor, retorno do fragmento com erro de validação e composição delegada da confirmação.

Limite aceito: o rótulo "Carregando…" aparece junto com a região `role=status` e pode não ser lido por todos os leitores de tela. O erro é sempre anunciado.

## F4-C — transformação UR4 mais barata

A transformação de cada resposta (`RespostaNavegacao`) resolve as URLs da página para operações UR4 e grava uma referência cifrada por destino. Medição local da turma com 40 alunos e 15 provas (197 KB, 179 URLs, 145 referências):

| Parte | Antes | Depois |
|---|---|---|
| Compilar o catálogo de rotas, por requisição | 6,8 ms | 1,6 ms |
| Resolver uma URL distinta | 240 µs | 140 µs |
| Transformação da turma, cache em arquivo (como na homologação) | ~107 ms | ~73 ms |
| Transformação da turma, cache em memória | ~65 ms | ~43 ms |

As medidas:

- **Catálogo guardado.** As expressões compiladas das rotas ficam no cache sob uma assinatura das rotas da release. O valor é cifrado e autenticado pela `APP_KEY`, como as referências. Um valor adulterado, em claro ou ilegível é ignorado e recompilado (teste dedicado). O catálogo continua sem alvos, sessão ou autoridade.
- **Índice por módulo.** A busca percorre só as rotas do mesmo primeiro segmento (`/app/<módulo>`), mantendo a ordem original do catálogo.
- **Deduplicação.** URL e referência repetidas na mesma requisição e no mesmo escopo (sessão, identidade e revisão) são resolvidas e gravadas uma vez. A validade da referência é calculada uma vez por requisição.

O HTML de saída da turma, normalizado nas referências, tokens e revisão (que variam por sessão), é idêntico byte a byte ao anterior.

O custo restante é a gravação de uma referência por destino distinto: 145 escritas em arquivo na turma. Agrupar essas escritas, ou mudar o armazenamento das referências, altera o desenho da UR4 e a operação. Fica para a F6, com medição na homologação.

## F4-D — ciclo de vida dos gráficos da turma

`turma-graficos.js` passa a agrupar as renderizações num quadro de animação. Vários avisos do `ResizeObserver` viram um desenho por gráfico, e um tamanho inalterado não redesenha. O observador de tema só redesenha quando os tokens usados pelos gráficos mudam; abrir um modal, que altera classe ou estilo do documento, não redesenha mais. Na saída definitiva da página, o quadro pendente é cancelado e instâncias e observadores são descartados. Uma página guardada pelo navegador (bfcache) volta intacta, sem gráficos descartados. Testes: `turma-graficos-ciclo.test.mjs`, com 3 cenários que falham no código anterior. O `graficos.js` já agrupava por quadro e descartava gráficos desconectados, e não muda.
