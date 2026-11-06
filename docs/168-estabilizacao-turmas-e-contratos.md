# ST1 — estabilização da turma e dos formulários após UR3

Planejamento revisto em05/11/2026 após o relato do responsável sobre erro500 em `/app/turmas/14`. Publicação somente na homologação; produção permanece excluída. O fechamento da validação UR3 foi interrompido pelo incidente e não deve ser apresentado como aprovado integralmente.

## Diagnóstico e contrato preservado

O log da aplicação Web registra ConnectionException na chamada inicial de TurmaController::show. Uma conferência isolada mediu12,019s na API e13,465s no BFF para a turma, perto do timeout de15s. A versão UR3 publicada não alterou a fonte da API. O gargalo existente reconstrói escopos/consultas de notas para cada aluno e prova.

A correção interna agrega por aluno/prova usando clones do mesmo ConsultasPedagogicas. Mantém a média histórica do aluno, COUNT de notas, AVG SQL, arredondamento, ausência de nota, escopo e permissões. Endpoints, parâmetros e JSON permanecem iguais. Não aumenta timeout nem introduz cache de autorização.

Na fixture pequena, as consultas passaram de4421 para1367; na maior, de26195 para1367. Esses números medem regressão local, não SLO. A construção de escopos ainda é custosa e precisa de avaliação própria; não remover validações para reduzir consultas.

## Sequência de execução

| Parte | Trabalho | Aceite |
|---|---|---|
| ST1-A | Reproduzir e distinguir transporte, API, autorização e renderização | Logs sanitizados e sessão temporária própria; dados escolares preservados. |
| ST1-B | Eliminar crescimento de SQL por aluno/prova | Mesmas métricas/ACL; regressão SQLite e MariaDB. |
| ST1-C | Tratar indisponibilidade no Web e corrigir transporte dos formulários | Falha temporária503 sem métricas falsas; POST SSR com CSRF/revisão funciona; campos desconhecidos continuam recusados. |
| ST1-D | Revisar código e interface, executar CI | Revisão independente, conferência desktop/celular, negativos e gates completos. |
| ST1-E | Publicar e validar a correção na homologação | Backup, comparação dos dados/arquivos, turma real, demais fluxos afetados e monitoramento. Sem produção. |

As correções de formulários pertencem ao BFF: Exportações aceita GET protegido ou POST com CSRF/revisão; Pedidos inclui a revisão na whitelist de transporte; Catálogo retira os campos de transporte antes de enviar dados de negócio. Isso não exige alterar a API.

## Frentes distintas na continuidade

- **UR4:** revisão transversal das URLs, ações, formulários e perfis. A retirada do vínculo na UR3 não removeu os IDs dos registros. A exigência posterior do responsável deve ser atendida por contrato próprio, preservando duas turmas distintas em abas diferentes, refresh/histórico, CSRF e autorização; não por ocultação cosmética nem por uma turma global na sessão. Inventário preliminar e proposta estão em `operacao-20261102/ur4-urls-fixas-contrato-proposto.md`.
- **AP1:** tratar API e Web como aplicações independentes, com contratos de comunicação e publicação próprios. Navegação do cliente não deve obrigar a alterar endpoint/payload da API. Estudo inicial em `operacao-20261102/api-web-independencia-estudo.md`.
- **Estrutura alvo aceita pelo responsável:** `apps/web/`, `apps/api/plataforma/` e diretórios próprios para os motores futuros. A API atual continua em `apps/api/` até uma migração específica de estrutura/CI/operação; ST1 não move diretórios nem cria motores. Não detalhar GII nesta etapa.

As alterações de API e Web deste hotfix têm justificativas próprias. O pipeline atual ainda publica os dois juntos; sua separação é uma pendência explícita, não uma propriedade já concluída. A mudança futura de servidor requer validar comunicação remota e independência de dados/configuração.
