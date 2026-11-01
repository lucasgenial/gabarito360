# Erro 500 depois do login — 01/11/2026

## Diagnóstico

Servidor CT 111 no commit `af4c6d1e9e1d324ebd10e404af149b78a28270a4`, adaptador T2 ativo. Às 14:37 BRT, a autenticação respondeu 200 e a web redirecionou normalmente. A consulta subsequente a `/api/v1/dashboard/professor` excedeu os 15 segundos do ApiClient, gerando `ConnectionException` e resposta 500 na web. Não foi falha de senha, DNS ou do redirecionamento www.

Nginx, PHP-FPM e Redis ativos; health local em 9 ms. Reprodução somente de leitura do controller confirmou o gargalo. As consultas com subconsultas de isolamento e joins passam tempo excessivo no planejamento do MariaDB, configurado com `optimizer_search_depth=62`. Não foram removidos filtros de acesso.

## Correção preparada

Inicializar as conexões PDO MySQL/MariaDB **da API** com `SET SESSION optimizer_search_depth=4`. Não altera configuração global do banco, dados, esquema, filtros de autorização ou timeout HTTP. O comando também se aplica a reconexões. SQLite e a aplicação web não mudam.

A [documentação do MariaDB](https://mariadb.com/docs/server/server-management/variables-and-modes/server-system-variables#optimizer_search_depth) descreve o equilíbrio entre tempo de planejamento e qualidade do plano. O valor menor pode produzir planos menos eficientes para outras consultas; a suíte MariaDB e o acompanhamento dos endpoints fazem parte do aceite.

## Evidências e limites

- Conexão de diagnóstico isolada: painel respondeu 200 em aproximadamente 0,76 s com profundidade 4; profundidade 1 também respondeu em menos de um segundo.
- Não houve mudança persistente de configuração no servidor durante o diagnóstico.
- Rankings com empate não têm critério secundário de ordenação: planos distintos podem retornar alunos diferentes na última posição empatada. Não se afirma igualdade byte a byte entre respostas desses planos.
- O controller ainda gera cerca de 1.100 consultas neste cenário. Reduzir reconstruções de escopo é melhoria posterior, preservando isolamento; esta correção trata o bloqueio do acesso.
- O ensaio chamou o controller com o ator da autenticação recente, sem imprimir nomes, notas, tokens ou senhas. Não substitui o aceite final pelo navegador após a publicação autorizada.

## Aplicação autorizada e aceite

PR #98 incorporado no commit `9088ae9b934af715040246af8767116cd2c4ff94`. Em 01/11/2026 às 17:05 BRT, com autorização expressa, foi aplicado somente o ajuste de `apps/api/config/database.php` no CT 111. A base publicada permaneceu `af4c6d1e9e1d324ebd10e404af149b78a28270a4`; não houve migração, alteração de flags ou publicação das fases pendentes.

Backup do original, diff e registro: `/root/g360-hotfix-pr98-20261101T200522Z` no CT 111. Cache da API reconstruído como `www-data` e PHP-FPM recarregado. Conexão e reconexão confirmaram profundidade 4, com global 62. Controller do professor respondeu 200 em 0,842 s; login e health públicos retornaram 200. Serviços ativos e nenhum novo erro durante a verificação. O responsável confirmou depois: “perfeito, agora funcionando”. Incidente encerrado com aceite funcional.

O próximo deploy deve reconciliar esse único diff operacional com a correção já incorporada na main antes da checagem de árvore limpa. Não descartar outras alterações nem executar reset geral.

## Procedimento da aplicação pontual

Há funcionalidades posteriores na main ainda não autorizadas para deploy. **Não publicar toda a main para corrigir este incidente.** A correção operacional deve conter somente as duas opções PDO adicionadas em `apps/api/config/database.php`, aplicadas sobre o commit publicado, com cópia de segurança do arquivo, conferência do diff e reconstrução do cache da API como `www-data`. Registrar essa exceção operacional e o diff até sua reconciliação com o próximo release aprovado.

Não executar migrações nem alterar flags de ativação. Após aprovação: validar sintaxe/configuração, conexões novas e reconectadas, health, login e painel do professor; confirmar ausência de novos timeouts. Se houver regressão, restaurar somente o arquivo de configuração anterior e reconstruir o cache da API. Nenhum rollback de banco é necessário.
