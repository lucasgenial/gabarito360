# PF1-C — implementação dos perfis escolares prioritários

Planejamento revisado em03/11/2026 após aprovação expressa da matriz134. Escopo: Vice-diretor escolar e Auxiliar de secretaria, preservando Secretário escolar. Concluir C1–C5 sem novos pedidos de continuidade e publicar apenas na homologação. Produção, ID1, importação XLSX e portais futuros ficam fora da entrega.

| Subdivisão | Entrega | Situação |
|---|---|---|
| C1 | Catálogo independente, contexto e migração aditiva de enums | Implementado; em validação |
| C2 | Gestão, convite, seleção e concessão limitada e auditada | Implementado; em validação |
| C3 | Projeção administrativa sem desempenho, autoria institucional central e proteção de status | Implementado; em validação |
| C4 | Rótulos, equipe, catálogo, painel e ações por capacidade | Implementado; revisão independente pendente |
| C5 | Testes, CI, ensaio isolado, backup e publicação na homologação | Em execução |

A migração acrescenta os novos valores aos enums sem criar vínculos, alterar pessoas ou promover contas. Sincronização é idempotente e mantém a política do Secretário. Reversão destrutiva é recusada. A comparação pré/pós do deploy deve preservar todo o conteúdo acadêmico e o perfil_criacao já existente.

A classificação de autoria institucional é única para Coordenador, Diretor e Vice-diretor. Provas criadas pelo Vice autorizado são institucionais; ao selecionar Professor, a mesma pessoa precisa da turma e não edita nem copia a prova. A fila existente mantém o perfil/contexto do ator, sem novos poderes de transferência.

Auxiliar usa projeções administrativas e não recebe notas, médias, provas, equipe ou relatórios. Status de aluno exige aluno.desativar também no campo ativo dos endpoints de cadastro/edição; aluno.gerir não contorna essa exigência. Vice tem leitura da equipe, com mutações condicionadas à concessão usuario.gerir e à hierarquia; sem autogestão nem gestão de pares/superiores.

Critérios: regressão do Secretário, mesma pessoa com vínculos distintos, escopo entre escolas, concessão real/auditoria, recusa de extras proibidos, retirada, expiração, suspensão, início futuro, autoria institucional e arquivos/exportações sem concessão. Web confere conteúdo administrativo, ações visíveis e troca por contexto. Evidência de UI em desktop/celular e parecer independente são necessários antes da integração. Recibos operacionais e capturas permanecem fora do Git.
