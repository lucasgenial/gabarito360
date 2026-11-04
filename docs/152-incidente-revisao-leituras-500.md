# Correção urgente — revisão de leituras e erros500

04/11/2026. Planejamento revisto após relato do responsável. Correção isolada da produção83dd5ae e homologação3b9e610; nenhuma fase DC1, migration, recálculo ou exclusão escolar.

Diagnóstico somente leitura nos dois servidores: web/API básicos200 em menos de0,04s. Em produção, lista53cartões executa47.401 consultas/39,94s; revisão45 questões de um cartão40.276 consultas/34,82s. Na homologação, lista48cartões56.490 consultas/52,84s e revisão45questões52.956 consultas/37,60s. O cliente web expira em15s, gerando ConnectionException e500. A emissão de links repete autorização/escopo/versão de acesso para cada foto e cada questão. Detalhe de escola também agrega notas aluno a aluno, multiplicando a mesma resolução de alcance.

Preparar autorização dos links em lote por prova e IDs, mantendo escopo integral, integridade, assinatura, prazo e revalidação na abertura. Não usar cache global de permissão nem ampliar leitura docente. Agrupar contagem de ambiguidades e médias/notas de alunos, sem mudar os dados. O tratamento visual adicional de indisponibilidade fica separado e preservado fora deste hotfix, para revisão final posterior. Esta entrega não muda interface, regras de acesso, schema, permissões ou dados acadêmicos.

Testar crescimento de consultas com volume, isolamento de prova/espaço/vínculo, revogação de links e contrato do legado. Publicar primeiro a correção equivalente na homologação com backup protegido, manifesto acadêmico antes/depois e medição dos mesmos consumidores; confirmar no Chrome. A promoção em produção é somente do hotfix isolado, conforme decisão do responsável, preservando todas as fases pendentes. Registrar versão, arquivos, rollback e verificações.


## Recorte de publicação confirmado

O responsável confirmou: consultar logs da produção; problemas de código devem ser corrigidos na homologação; ajustes de infraestrutura, se necessários, diretamente no servidor afetado. Este incidente foi identificado no código. Portanto, nenhum deploy de código em produção é autorizado nesta entrega.

Ensaio candidato somente leitura, sem substituir arquivos da release ativa, na homologação: lista48 cartões,1.432 consultas/0,764s; revisão45questões,1.431 consultas/0,737s; escola54alunos,7.201 consultas/4,013s. Validação usa ator autorizado e transação READ ONLY com rollback, sem imprimir dados ou URLs dos alunos.

Primeira entrega: três arquivos da API, teste de regressão e este registro. Rollback: voltar o symlink da homologação à release3b9e610 e recarregar FPM/worker, sem restaurar nem modificar banco. Não executar migrations, sincronização de permissões, edição de flags ou cópia de dados da produção. Backup próprio e comparação de conteúdo acadêmico são obrigatórios antes de abrir a release.
