# TU1 — organização da turma

Planejamento revisto em 06/11/2026 antes da implementação. Direção prévia independente de UI/UX e Product Design registrada em `operacao-20261102/tu1-direcao-design.md`; diagnóstico de segurança em `tu1-diagnostico-seguranca-fluxo.md`.

| Parte interna | Entrega | Aceite |
|---|---|---|
| TU1-A | Conferir fluxo, autorização e composição atual | Evidências Chrome, direção prévia e API preservada. |
| TU1-B | Separar Visão geral, Alunos, Disciplinas e professores, Provas e Acesso à turma | Abas locais acessíveis, área útil integral, uma seção visível, sem repetir KPIs. Administrativo/pessoal recebem somente suas seções autorizadas. |
| TU1-C | Selecionar disciplina do catálogo na turma e tornar explícito o caminho de atribuição | Busca/paginação reais, formulários separados, retorno correto, indicação e acesso nunca concedidos implicitamente. |
| TU1-D | Validar código, permissões, teclado, navegação e visual | Revisões independentes, CI, Chrome desktop/mobile, refresh/retorno por referência e revisão de vínculo. |
| TU1-E | Publicar e conferir exclusivamente na homologação | Backup, dados/arquivos preservados, validação real e monitoramento. Sem produção ou migração. |

## Contrato e experiência

As abas são uma melhoria progressiva local: os dados são autorizados no servidor, os painéis continuam legíveis caso a inicialização das abas falhe, e JavaScript externo implementa tablist/tab/tabpanel, setas, Home/End, Enter/Espaço e foco. Apenas o fragmento de seção é usado no endereço, nunca identificadores. `history.state` da navegação UR4 permanece integralmente preservado. `secao` no descritor BFF permite retorno explícito de formulários e paginação; não é encaminhado à API.

Quatro operações continuam independentes: adicionar disciplina do catálogo à turma, indicar professor na disciplina, conceder acesso à turma e registrar atribuição com período/papel. Cada uma mantém seu endpoint/payload, permissões, CSRF, revisão e conflitos. Diretor/vice não recebem a gestão DC1 da coordenação; professor não recebe dados de outros professores por ocultação apenas visual. Histórico e notas não são alterados.

O catálogo na turma usa o GET paginado já existente da API. Falha de consulta é distinta de lista vazia. A seleção mostra somente a página consultada, com busca e paginação explícitas. A interface não apresenta essa página parcial como o catálogo inteiro. Nenhuma dependência nova ou mudança de esquema é necessária.
