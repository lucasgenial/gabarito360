# DC1 — simulados de desenvolvimento e fechamento integrado

04/11/2026. Retomada após os hotfixes182–185, incorporados sem conflito à base30c7c99. Revisão anterior ao código: a próxima entrega dos docs145/150/151 adapta os quatro simulados autorizados, prepara professores de teste por disciplina e conclui o ensaio do conjunto. Produção permanece excluída.

A operação será CLI, exclusiva da homologação isolada, sem endpoint de reclassificação. Simulação por padrão; aplicação explícita, transacional e auditada. Conferir escola INEP29428254, espaço13, autor10/vínculo7 e provas3–6 com seus nomes, áreas e turmas14/15. Recusar estado inesperado, autoria já conferida incompatível, contas existentes conflitantes ou disciplina ambígua. Não usar o cargo atual para inferir autoria de outras provas.

Criar quatro áreas locais e treze disciplinas: Língua Portuguesa, Arte, Educação Física, Língua Inglesa, Língua Espanhola; História, Geografia, Filosofia, Sociologia; Biologia, Física, Química; Matemática. Espanhol é componente local, sem referência oficial inventada. As provas por área continuam integrais, sem separar suas questões por disciplina. Preservar texto legado e todos os IDs, vínculos de turma, gabaritos, cartões, respostas e notas.

Contas professor1…13@gabarito360.dev somente neste ambiente, nome e observações identificados como teste, documentos fictícios reservados, hash da senha reaproveitado internamente da conta de teste existente. Não extrair senha/hash nem alterar contas preexistentes; não enviar e-mail. Atribuição explícita às duas turmas e à disciplina, vigente a partir da execução; não inventar atuação docente histórica. Aceite de teste documentado e separado do convite operacional. Reexecução deve conferir e preservar, nunca duplicar nem reaplicar adaptação divergente.

Consulta prévia UI/UX aprovada para ensaio sobre telas existentes. O parecer do formulário não substitui revisão global de catálogo/atribuições e da composição integrada. Conferir Coordenação, Professor elegível e negativos por disciplina/turma/escola, mesma pessoa em dois vínculos, futuros/encerrados e URLs diretas; manter leitura sem copiar/editar prova institucional. Nova evidência desktop amplo/menu recolhido,320/390px e erro/conflito antes do aceite final.

Publicação apenas ao fechar o DC1 completo: ensaiar migrations aditivas, ativação, adaptação, backup/restauração de banco e arquivos e recuperação dos serviços em cópia isolada atual. Comparar dados protegidos antes/depois, aceitando somente a adaptação autorizada. CI integral e revisão independente; integrar a pilha de PRs preservando hotfixes e publicar na homologação com backup e rollback documentado. Nenhuma migration reversa destrutiva, exclusão acadêmica, liberação de identidade global ou implementação do GII/editor.

| Parte | Estado na retomada |
|---|---|
| Catálogo, áreas, períodos, atribuições e currículo das provas | Implementados; PRs pendentes de integração do conjunto |
| Formulário curricular | UI/UX aprovado e cinco checks verdes no PR181 |
| Hotfixes revisão/modal/imagem | Integrados à base da entrega |
| Quatro simulados e professores de teste | Próxima implementação e ensaio |
| Recuperação atual e aceite global | Pendentes |
| Homologação | Publicar após fechamento completo DC1 |
| Produção | Sem alterações |
