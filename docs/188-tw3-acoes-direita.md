# TW3 — correção transversal da coluna Ações

## Planejamento revisado antes da implementação

O responsável corrigiu a posição solicitada: Ações deve ser a última coluna, à direita de TODAS as tabelas, com largura suficiente para a integralidade dos botões. Consulta prévia independente de UI/UX e Product Design registrada em `tabelas-acoes-direita-design-previo.md` no diretório operacional protegido. A orientação anterior à esquerda está superada; cards preservam ações no rodapé esquerdo.

Inventário de origem: 56 tabelas em 47 views, de 18 famílias. Dessas, 28 tabelas têm célula operacional `tabela-acoes`; Turmas é a única com Ações antes dos dados. Outras matrizes, seleção e relatórios sem ações não recebem colunas inventadas. A prévia aprovada também deve refletir a correção. Inspecionar renderers JavaScript e preservar geração de PDFs/impressão.

| Passo | Entrega | Validação |
| --- | --- | --- |
| 1 | Última coluna real e identificação compartilhada em todas as tabelas com ações | Inventário estático e HTML renderizado, ordem dos cabeçalhos/células e teclado |
| 2 | Largura intrínseca e grupos completos, retirando restrições concorrentes | Grupos com várias ações, nomes longos, desktop com lateral expandida/compacta, tablet e celular |
| 3 | Referência da prévia alinhada ao novo padrão | Build reproduzível e auditoria da prévia |
| 4 | Revisão independente, CI e publicação isolada | Backup, arquivos/dados preservados, nenhuma migration/API/produção |
| 5 | Conferência no Chrome da homologação | Tabelas à direita, cards completos e tabela atualizada no chat |

Complemento humano durante a execução: centralizar título Ações e seus botões dentro da última coluna. Incluir ordenação diretamente nos cabeçalhos de todas as colunas de dados, alternando crescente/decrescente, com `aria-sort`, foco e anúncio do resultado. O select continua como alternativa nos cards. Números/datas usam valores semânticos, ausências ficam ao final e empates preservam ordem original. Operações e checkbox puro não são dados ordenáveis; estruturas agrupadas não são desmontadas. Coleções completas ordenam antes da paginação local; páginas parciais identificam “Ordenação dos registros desta página”. Formulários e seleções permanecem nos mesmos nós, sem requisições extras nem alteração do contrato da API.

Não é uma declaração de migração integral de todas as famílias para Tailwind. O restante de TW3 e TW4–TW6 continua pendente. Esta correção não altera contratos da API, consultas, rotas, CSRF, vínculo, sessão, permissões ou banco. Mantém o rodapé literal aprovado.

## Nova solicitação de organização dos painéis

Recebida durante esta correção: por padrão mover Resultados por período escolar e Agenda da turma para Resultados da turma, abaixo de Estudantes. Permitir reordenar grupos com título/widgets e os cards centrais completos, salvando por usuário/painel/turma. A organização é etapa própria, sujeita à consulta prévia, compatibilidade das preferências existentes e revisão independente. KPIs cadastrais continuam fixos e edição indisponível no celular. Não confundir esta mudança estrutural com a correção transversal das tabelas.
