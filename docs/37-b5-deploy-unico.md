# 37 — B5: roteiro único de deploy

Escopo autorizado pelo responsável ao solicitar a próxima etapa após B6. Dependência B4 concluída. Implementação em `scripts/operacao/deploy.sh`; instruções em `docs/operacao/deploy.md`.

A entrada antiga `infra/deploy.sh` passa a delegar ao roteiro oficial. Isso remove o caminho antigo e as alterações automáticas de nginx, SSL e `.env` daquele arquivo, evitando que uma chamada por hábito execute o procedimento ultrapassado. Os testes e o job de CI são ampliados para cobrir o contrato do roteiro.

## Micropassos

1. Exigir os SHAs completos do commit aprovado e da produção esperada; validar main, alterações rastreadas, serviços e ferramentas.
2. Oferecer `--check`: conferir e buscar referências sem manutenção, alteração de código, migração ou reinício.
3. Executar uma cópia temporária do próprio script, evitando que o fast-forward substitua o programa enquanto ele roda. Impedir deploys concorrentes com lock.
4. Colocar a web em manutenção, suspender o timer e aguardar as filas Redis esvaziarem, incluindo trabalhos reservados e atrasados. A API permanece disponível durante a drenagem porque os workers pausam quando ela entra em manutenção. Em até 300 segundos, prosseguir ou recuperar o estado anterior sem atualizar código. Fechar a API e conferir novamente a fila antes da atualização; se entrou outro job, cancelar preservando a versão anterior.
5. Parar workers ociosos, atualizar por fast-forward, instalar dependências dos arquivos lock, aplicar migrações somente na API, recriar caches, recarregar PHP-FPM e iniciar workers. A web conserva migrations padrão do Laravel que não representam o banco de negócio; não executá-las.
6. Retirar manutenção, testar início/login/saúde da API no nginx local e no domínio público e confirmar serviços. Reativar o timer.
7. Testar o roteiro com comandos simulados e executar o CI. Após aprovação específica, validar dois deploys consecutivos e registrar resultados.

## Limites e falhas

- Não alterar `.env`, DNS, nginx, PHP-FPM ou unidades systemd.
- Não apagar arquivos não rastreados, dados ou tabelas. Sem reset forçado de Git ou rollback automático de migrações.
- Falha antes de mudar código retira apenas a manutenção iniciada pelo roteiro e restaura os serviços que ele parou.
- Falha a partir da atualização do código mantém as aplicações em manutenção e mostra a etapa para investigação. Retorno de versão depende de análise das migrações e autorização.
- `--check` não substitui CI, revisão e aprovação do SHA. O operador confere esses itens antes da execução real.
- B5 permanece em revisão até dois deploys autorizados funcionarem sem etapas manuais adicionais. Os deploys anteriores ao roteiro não contam para esse aceite.

## Testes planejados

Executar comandos simulados em diretório temporário, sem produção: check sem mutações, SHA divergente, árvore suja, alvo fora da main, fila que não esvazia, falha em migração, falha HTTP e sucesso completo. Acrescentar os testes ao CI.

## Resultado da preparação

- Dez testes simulados aprovados, incluindo SHA inválido, manutenção preexistente, serviço isolado inativo, fila ocupada e job entrando durante a manutenção, falhas de dependência/migração/HTTP, execução pela entrada antiga e substituição do próprio script durante o deploy.
- Conferência real em 31/10 pelo modo `--check`, usando `e0ccc1489bfe65a3f90cae43e1d1a49c3cf2b9bf` como origem e alvo: passou, com fila zero. Sem atualização de código, manutenção, migração ou reinício. A primeira conferência identificou vendor de `lucasdev`; o roteiro foi ajustado para esse dono, mantendo caches/storage de `www-data`, e passou na repetição. Nenhuma permissão foi alterada.
- Os dois deploys de aceite pelo roteiro continuam pendentes de aprovação. O `--check` do commit já publicado não valida as etapas de Composer, migração e reinício.
