# DC1 — áreas do conhecimento no cadastro de disciplinas

04/11/2026. Planejamento revisto antes da implementação a pedido do responsável. Esta adaptação trata somente da organização atual das disciplinas; não planeja nem implementa o GII.

## Contrato desta entrega

- Áreas próprias do catálogo da escola, com nome, sigla opcional, situação e versão. Nenhuma classificação ENEM/BNCC é importada como pertencimento automático. A área exibida nas referências nacionais identifica apenas aquela referência externa.
- Relação muitos para muitos: uma disciplina pode pertencer a várias áreas, sem duplicar seu cadastro. O vínculo é opcional e explícito. Não criar uma área implicitamente pelo texto digitado.
- Coordenação com a mesma autorização `disciplina.gerir` administra o catálogo de áreas. Direção e professores continuam com a leitura já permitida; áreas não concedem acesso a disciplinas, professores, turmas ou provas.
- Nomes normalizados únicos por espaço. IDs e chaves compostas impedem cruzamento entre escolas. Transações, ordem de travas consistente, versão otimista e auditoria append-only incluem as alterações de áreas e de seus vínculos.
- Inativar uma área preserva vínculos existentes e permite vê-los/retirá-los, mas impede novos vínculos enquanto inativa. Editar apenas o nome de uma disciplina não descarta suas áreas. Enviar lista vazia retira explicitamente os vínculos; omitir a lista os mantém.
- Novo esquema aditivo, sem conciliação por nome, sem backfill, sem alterar provas, questões, gabaritos, notas, fotos ou dados de aplicações. Áreas são organização atual do catálogo; ainda não produzem indicadores, pontuação ou classificação dos itens.
- Interface existente ampliada com grupo empilhado, seleção múltipla por caixas de seleção e gestão de áreas acessível a partir de Disciplinas. Preservar preenchimento/contexto/retorno em erros; descrever claramente a seleção opcional e múltipla. Área nacional de referência e áreas próprias ficam rotuladas distintamente.

## Verificação e publicação

| Parte | Resultado esperado | Situação antes do código |
|---|---|---|
| DC1 — organização docente | Catálogo, indicações e atribuições por turma | Implementado; revisão visual final pendente |
| DC1 — áreas do conhecimento | Catálogo local e relação N:N com disciplinas | Em implementação |
| DC1 — integração com provas novas | Escolha da disciplina compatível com todas as turmas | Preparação interna implementada; integração pendente |
| DC1 — fechamento | Revisão independente desktop/celular, testes SQLite/MariaDB, CI e recuperação completa | Pendente |
| Homologação acessível | Publicação do DC1 completo com backup e preservação dos registros anteriores | Somente após fechamento do DC1 |
| Produção | Nenhuma publicação nesta etapa | Fora do escopo |
| GII | Sem planejamento ou implementação | Futuro |

Testar isolamento no serviço e no banco, permissões retiradas, múltiplas áreas e várias disciplinas na mesma área, edição parcial, retirada explícita, área inativa, conflitos concorrentes, erros de campo, paginação real e histórico imutável. A revisão UI/UX prévia não substitui o parecer visual final independente.

## Ampliação autorizada durante a execução

O responsável autorizou adaptar também provas já aplicadas, sem excluir registros, e incluir os quatro simulados ENEM. Pediu professores de teste por disciplina (`professor1@gabarito360.dev` em diante) com o padrão de senha existente, somente na homologação. O inventário somente leitura encontrou provas3–6, todas do espaço13/escola8, turmas14/15, com `criado_por=10` e `perfil_criacao=coordenador` no registro atual. Isso **não comprova a função original**: o responsável esclareceu que criou sob Professor antes de assumir Coordenação. É necessário conferir a procedência e registrar a correção separadamente da disponibilização institucional, sem inferir autoria histórica pelo perfil atual.

Nova prova deve guardar usuário, vínculo e perfil da criação. Mudança posterior de cargo não reclassifica a prova. Professor+Coordenador na mesma pessoa usa o contexto selecionado, sem soma de permissões. Associar áreas/disciplinas não institucionaliza automaticamente uma prova particular. Professores acessam prova institucional apenas pelo escopo permitido de disciplina e turma; a área isoladamente não concede acesso. Essa ampliação será testada e entregue antes do fechamento DC1; não está concluída pela entrega do catálogo N:N.

Validação inicial do catálogo ampliado:19 testes da API/200 asserções em SQLite;10 testes web/88 asserções. Inclui preservação de associações em edição parcial, retirada explícita, erro de seleção associado ao grupo e leitura sem gestão. Chrome local com API real e dados fictícios confirmou criação de duas áreas, associação conjunta e reabertura preservada. MariaDB, revisão visual independente e CI ainda em conferência.
