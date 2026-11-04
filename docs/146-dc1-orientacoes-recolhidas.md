# DC1 — orientações recolhidas no cadastro de disciplinas

04/11/2026. Planejamento revisto antes da alteração de interface. O responsável determinou que orientações acima de uma linha fiquem recolhidas com “Saiba mais +”, também no celular, e depois esclareceu que o controle deve aparecer sempre.

Parecer prévio do agente UI/UX e Product Design: utilizar `details/summary` nativo, sempre fechado inicialmente e com controle visível em todos os tamanhos. Isso substitui a proposta preliminar de medir linhas e esconder o botão quando o texto couber. Não exigir JavaScript; estado manual permanece no redimensionamento. Aberto, o controle passa a “Saiba menos −”. Cada bloco coerente terá uma única orientação expansível, sem diálogos ou tooltips.

Aplicar à tela compartilhada de adicionar/editar disciplina: orientação de referência/própria, situação e áreas. Subtítulo curto identifica a tarefa; instruções extensas ficam fora do `p` do cabeçalho para manter HTML válido. Nomes, dados escolhidos, rótulos, obrigatoriedade, estados vazios, erros e consequências essenciais permanecem visíveis. A ajuda é complementar e não interfere no salvamento.

| Verificação | Situação inicial |
|---|---|
| Direção independente antes do código | Aprovada pelo especialista |
| Componente nativo e aplicação na tela | Em implementação |
| Desktop e320/390px, fechado/aberto, teclado e semJS | Pendente |
| Revisão visual independente atualizada | Pendente |
| CI e integração | Pendente |
| Deploy | Somente no fechamento do DC1 completo, homologação; sem produção |

Não reaproveitar como aprovação final as capturas anteriores do formulário com textos abertos. Nada de planejamento do GII.

Implementação verificada:11 testes web/101 asserções. Chrome local confirmou controles nativos fechados inicialmente, abertura, fechamento por Enter com foco mantido, alvos44px e estado aberto preservado ao passar de390 para320px. Com scripts temporariamente desativados somente na aba de QA local, a orientação abriu pelo controle nativo; a execução de scripts foi restaurada em `finally`. Capturas atuais desktop/320/390, claro/escuro e área inativa encaminhadas para parecer independente. Revisão final e CI ainda pendentes neste registro.
