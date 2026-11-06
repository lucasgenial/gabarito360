# UI1 — carregamento e estados de erro

Interrupção pontual antes de TU1, solicitada pelo responsável em06/11/2026. Direção prévia independente de UI/UX e ProductDesign, revista após a correção de escopo do responsável: o preloader integral aparece somente na aba que acabou de confirmar seleção/troca de vínculo. As navegações comuns mantêm casca do aplicativo e skeleton no conteúdo pendente. Retirar card do preloader; centralizar marca oficial e mensagem; pulso suave limitado, sem flash/giro, sem tempo mínimo artificial; reduzir movimento conforme preferência. Manter tema, recursos locais e superfície útil do viewport. Erros são estáticos e não se confundem com carregamento.

| Parte interna | Aceitação |
|---|---|
| UI1-A | Mesmo componente na estrutura/ponte e errosHTML; sem alterar seleção, sessão ou API |
| UI1-B | Falha interrompe animação/busy e apresenta ações seguras; semreenvioPOST;401/419login e409abaantiga preservados |
| UI1-C | HTTP/status/JSON/Retry-After mantidos; nenhum detalhe interno de exceção; noscript e reduced-motion |
| UI1-D | Chrome desktop/mobile, sucesso/falhas, revisão independente, CI, merge e publicação somente na homologação; tabela atualizada |

Preservar dados/gabaritos, API e produção. Não forçar erro real, manutenção ou latência na aplicação compartilhada para obter screenshots. Usar ensaio local isolado para estados determinísticos; conferir carregamento e navegação reais na homologação. A apresentação de exceções HTTP pertence ao Web e não modifica o provedorAPI. Erros de infraestrutura que não chegam ao PHP dependem da camada que os emite e não são cobertos por estas views.

Nova folha de estilo usa tokens existentes. JavaScript executável permanece externo. O estado de carregamento usa role=status e aria-busy; a falha dinâmica vira alerta estático com foco, mantendo somente ações de leitura/retorno. A animação termina após três ciclos e não adiciona espera à navegação.

## Escopo de fluidez e regressão de correção

A estrutura logada é desenhada a partir do contexto vigente, sem consultar a API para montar opções de troca. O corpo SSR completo continua sendo obtido pela leitura protegida existente; não afirmar que todos os cards de todas as páginas ganharam endpoints independentes. As regiões já progressivas, os indicadores e as miniaturas de correção mantêm suas cargas próprias. O sinal de entrada é por aba, ligado à revisão retornada de uma seleção bem-sucedida e consumido uma vez; não alterar seleção/contrato de sessão nem colocar identificadores na URL.

Na correção, um include de modal fora da seção Blade produzia corpo antes do doctype. Ao converter navegação, DOMDocument perdia a classe body/tela-correcao, desativando os estilos existentes. A correção é mover o include para dentro de content, sem remendar HTML com regex nem redesenhar cartões/miniaturas. Testar classes no HTML final já convertido, além do screenshot.
