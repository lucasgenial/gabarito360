# AU1-B — conservação e ensaio operacional

## Revisão antes da implementação

PR #106 incorporado com autorização. Nenhuma ativação ou migração no servidor.

1. Corrigir a integração do alerta de indisponibilidade com a lista positiva de logs L3 e testar a saída sanitizada real.
2. Disponibilizar comando somente de leitura: inventário por mês, contagem, digest determinístico, corte de retenção e meses preservados. Prazo não configurado bloqueia classificação; `--dias` permite simulação explícita, nunca exclusão.
3. Comparar manifesto anterior com a cópia restaurada por contagem/digest. Ensaiar restauração lógica com dados fictícios, reentrega e detecção de perda/alteração, em SQLite e MariaDB.
4. Documentar separação de credenciais, preservação por incidente, recuperação da fila, alerta e critérios de ativação. Sem conceder privilégios ou alterar workers reais.
5. Revisar particionamento antes de DDL: manter UUID global e índices nesta entrega; a mudança física exige decisão e ensaio próprio. A coluna mensal indexada não é partição.

## Decisões pendentes

Atualização após PR #107: responsável aprovou explicitamente 180 dias com preservação excepcional documentada. AU1-B2 registra o padrão configurável; nenhuma exclusão é implementada. Meses preservados bloqueiam a classificação de descarte do mês inteiro; justificativa e revisão ficam no registro operacional restrito, referenciado fora dos eventos.

O MariaDB exige que toda chave única inclua a coluna de particionamento. Particionar por mês mudaria a garantia atual de UUID global. Não alterar a chave apenas para cumprir uma etapa do plano. Esta preparação não conclui AU1: faltam ensaio de recuperação completa em ambiente equivalente, credenciais restritas e solução física mensal aprovada antes da ativação. T5 continua pendente dessa conclusão. AU1-B2 trata credencial de gravação e fila isolada (docs/74).

Fonte técnica: [limitações de particionamento MariaDB](https://mariadb.com/docs/server/server-usage/partitioning-tables/partitioning-limitations).
