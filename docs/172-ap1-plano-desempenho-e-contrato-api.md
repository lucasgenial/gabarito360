# AP1 — desempenho do painel e contrato independente da API

Planejamento revisto antes da implementação em 06/11/2026. Base: main/PR199, homologação isolada. Autorização do responsável: priorizar a lentidão, preservar contrato/permissões, validar e publicar somente na homologação.

## Entrega e subdivisões internas

| Parte | Entrega | Aceitação |
|---|---|---|
| AP1-A | Baseline HTTP/SQL e inventário do contrato atual | Mesmo ator/espaço/dados; distinguir API, BFF, primeira carga e repetições; sem exportar credenciais ou dados pessoais |
| AP1-B | Reduzir consultas de hierarquia e consultas por prova | Revalidar a cada uso; sem cache de concessões, sessão, TTL ou estado global; consultas com os mesmos filtros de integridade |
| AP1-C | Provar contrato e segurança | Métodos, caminhos, status, campos, tipos, limites e semântica preservados; testes SQLite/MariaDB, revogação e isolamento; Web permanece consumidor independente |
| AP1-D | Validar publicação/configuração independente | API configurada por `services.api.base_url`; cliente não conhece banco/controladores da API; dependências e artefatos próprios; não mover diretórios ou mudar URLs nesta entrega |
| AP1-E | Publicar e medir na homologação | CI/revisões, backup, dados/arquivos preservados, Chrome desktop/mobile, comparação antes/depois e acompanhamento de erros; mesma planilha atualizada |

A migração física de diretórios e uma futura hospedagem da API em outro servidor são mudanças com aceite próprio. Esta entrega comprova o contrato e mantém a separação existente; não antecipa essas migrações.

## Direção técnica escolhida

A amostra anterior registrou 10.930 consultas no painel do coordenador e aproximadamente 7,2s no endpoint HTTP da API. A medição CLI não equivale a FPM/p95. Repetir a baseline na release atual antes de publicar.

Não reutilizar snapshots de autorização nem builders com IDs materializados entre consultas. Em vez disso, consolidar as verificações de hierarquia em SQL com `EXISTS`: mesma validade do vínculo, cliente, espaço, instituição, ancestralidade e propriedade. Cada uso continua consultando o banco. A autorização funcional continua respeitando exceções e períodos de vínculos opostos.

No coordenador, substituir quatro consultas auxiliares por prova por quatro consultas em lote, sempre construídas por `ConsultasPedagogicas`. Manter nomes das turmas e lista ordenada em consultas distintas para preservar os formatos/ordenação existentes. Não remover filtros de notas, cartões ou turma de aplicação histórica. Eliminar somente a contagem ambígua comprovadamente não usada e evitar buscar duas vezes as opções da mesma seleção.

## Contrato e limites

- API mantém `/api/v1/dashboard/coordenador` GET e os demais métodos/caminhos existentes. Não alterar transporte para atender a navegação do BFF.
- Preservar JSON, códigos 401/403/404/422, null/zero, arredondamento, multiplicidade, limites e seleção institucional. Sem novos campos obrigatórios ou credenciais em URL.
- Sem dependência nova, índice/migration, configuração de expiração ou alteração visual. Sem cache que sobreviva à chamada, requisição ou job.
- Publicação exclusivamente na homologação. Produção e promoção final continuam dependentes de autorização expressa.

## Validação prevista

Fixtures determinísticas com 1/4/8 provas, alunos ativos/inativos, notas ausentes/zero, histórico de turma e relações cruzadas. Comparar payload e crescimento das consultas; testar revogação/suspensão e mudança de alcance com o mesmo objeto de ator, além de cliente e hierarquia incoerentes. Rodar as suítes existentes da API e Web e o gate MariaDB.

Na homologação, registrar pelo menos dez leituras antes/depois, de forma sequencial e com o mesmo escopo. Comparar hashes semânticos, SQL/tempo total e HTTP API/BFF; uma amostra curta não define SLA nem capacidade de produção. Registrar concorrência moderada sem alterar pools ou mutex. UI/UX deve conferir que a interface e as ações continuam iguais.
