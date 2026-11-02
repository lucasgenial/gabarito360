# AU1-B2 — credencial de gravação e recuperação da fila

## Revisão antes da implementação

PR #107 incorporado com aprovação. O responsável aprovou guarda de 180 dias com preservação excepcional documentada. Sem deploy, migration, exclusão ou concessão de privilégios no servidor.

1. Registrar 180 dias como padrão configurável. Meses preservados continuam excluídos da classificação; a configuração não executa expurgo.
2. Separar a conexão de gravação da auditoria. Não herdar usuário/senha da conexão geral; configuração ausente deve falhar e permitir retry, sem fallback silencioso. Modelos e conferência continuam somente de leitura no fluxo existente.
3. Ensaiar em serviços descartáveis do CI: usuário INSERT/SELECT, negação UPDATE/DELETE/ALTER, payload Redis cifrado, falha de conexão, reentrega e recuperação de Redis após parada/reinício com snapshot.
4. Manter a garantia UUID global. A arquitetura mensal física e a recuperação completa do banco/contêiner permanecem no próximo ensaio AU1-B3; não declarar AU1 concluído com teste de fila isolado.

O isolamento entregue é da credencial usada pelo job. Credenciais gerais existentes ainda precisam ter privilégios reais revisados antes da ativação; não afirmar inviolabilidade nem que administradores SQL perderam acesso.
