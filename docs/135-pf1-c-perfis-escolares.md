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


## Complemento do responsável — grupos funcionais e imagem

Confirmado em03/11/2026: funções administrativas (Secretário/Auxiliar) não se acumulam com Professor, Coordenação, Direção/Vice-direção ou Aplicador na mesma instituição. Vínculo encerrado não impede nova contratação de outro grupo; o histórico é preservado sem manter o acesso antigo. Escolas distintas são independentes. Acumulação excepcional exige referência de chamado e liberação individual por usuário/instituição, feita por Suporte/Desenvolvedor, com motivo, versão e auditoria imutável. A liberação não cria vínculo nem une permissões. Este complemento substitui orientações anteriores que admitiam acumulação administrativa sem exceção.

O responsável também autorizou foto de perfil e escolha de avatar para usuários/professores. A imagem é editada pelo próprio titular, com galeria existente ou upload limitado/reprocessado e armazenamento privado. Não altera identidade, papéis ou permissões. Evidências e testes dessas duas extensões integram o fechamento de PF1-C antes de publicar somente na homologação.
