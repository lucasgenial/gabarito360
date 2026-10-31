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
- B5 exige dois deploys autorizados sem etapas manuais adicionais. O aceite foi atingido em 31/10/2026, conforme registro abaixo; os deploys manuais anteriores não foram contados.

## Testes planejados

Executar comandos simulados em diretório temporário, sem produção: check sem mutações, SHA divergente, árvore suja, alvo fora da main, fila que não esvazia, falha em migração, falha HTTP e sucesso completo. Acrescentar os testes ao CI.

## Resultado da preparação

- Dez testes simulados aprovados, incluindo SHA inválido, manutenção preexistente, serviço isolado inativo, fila ocupada e job entrando durante a manutenção, falhas de dependência/migração/HTTP, execução pela entrada antiga e substituição do próprio script durante o deploy.
- Conferência real em 31/10 pelo modo `--check`, usando `e0ccc1489bfe65a3f90cae43e1d1a49c3cf2b9bf` como origem e alvo: passou, com fila zero. Sem atualização de código, manutenção, migração ou reinício. A primeira conferência identificou vendor de `lucasdev`; o roteiro foi ajustado para esse dono, mantendo caches/storage de `www-data`, e passou na repetição. Nenhuma permissão foi alterada.
- O `--check` do commit já publicado não validava as etapas de Composer, migração e reinício. Essas etapas foram validadas nas duas execuções reais abaixo.

## Aceite em produção — 31/10/2026

O responsável aprovou especificamente o merge do PR #66 e dois deploys consecutivos do mesmo commit. O PR foi incorporado no commit `07340a8b22cfc14569aebc405cea3aa79175bc43`; API, web e telas passaram no CI desse commit antes da publicação.

| Execução | Origem | Alvo | Intervalo UTC do roteiro | Resultado |
|---|---|---|---|---|
| 1 | `e0ccc14` | `07340a8` | 14:13:58 a 14:14:14 | concluída |
| 2 | `07340a8` | `07340a8` | 14:15:14 a 14:15:26 | concluída |

A primeira usou a cópia de bootstrap enviada a `/tmp` antes da instalação. A segunda usou diretamente `scripts/operacao/deploy.sh` já instalado na produção. Não houve correção manual, mudança do roteiro ou reparo entre as execuções. Os intervalos incluem a execução do roteiro; não são uma medição exata da indisponibilidade.

Nas duas execuções:

- Conferência prévia aprovada, fila zero, manutenção breve e serviços operados pelo próprio roteiro.
- `composer install` nas duas aplicações confirmou os arquivos lock e informou que não havia pacotes a instalar, atualizar ou remover; autoload otimizado e descoberta de pacotes executados.
- Migração da API executada sem novas migrações pendentes. Não foram executadas migrações da web.
- Caches refeitos, PHP-FPM recarregado e workers/agendador reativados.
- Início, login e saúde da API responderam HTTP 200 localmente e pelo domínio público.
- Conferência técnica após cada execução: Redis ativo na aplicação, filas alta/padrão/baixa vazias e zero jobs falhos; `retry_after=240`, OMR timeout de 60 segundos e tabela de envios de B6 presente.

Não houve alteração de `.env`, DNS, nginx, PHP-FPM ou unidades systemd. Não foram criados dados escolares. O teste valida o procedimento e sua repetição no mesmo commit; não exercitou uma falha real em produção, uma nova alteração de esquema ou uma leitura autenticada de cartão.
