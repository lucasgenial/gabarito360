# F4 — carregamento e interação: planejamento de entrada

**Data:** 09/11/2026. **Status:** F4-A publicada na homologação; F4-B em revisão; F4-C e F4-D planejadas. **Escopo:** somente homologação. Produção fora do escopo.

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
