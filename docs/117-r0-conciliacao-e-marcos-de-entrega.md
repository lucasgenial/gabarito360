# 117 — R0: conciliação e marcos de entrega

03/11/2026. O responsável autorizou seguir os planos115/116 mantendo sua regra de fases, subdivisões e atualização do servidor. Registro da conciliação documental e inventário de código; não comprova migração, homologação ou funcionalidades novas implementadas.

## Regra de execução e publicação

- Fase: agrupamento geral do planejamento. Etapa/subfase de nível1: entrega identificada como L4, CI1, CP1 etc. Letras e números internos são organização técnica de nível2 ou superior.
- Executar subdivisões internas em sequência, revendo planejamento quando necessário e mantendo testes, revisão, commits e push/PR. Não criar pedidos de autorização nem mensagens de início/fim para cada subdivisão.
- Publicar e atualizar o servidor no fechamento validado de etapa de nível1 ou fase; nunca por concluir uma subdivisão. A orientação específica vigente mantém L4 sem deploy até seu fechamento completo.
- Uma etapa dependente pode terminar tecnicamente sem ser publicável isoladamente. Registrar a dependência e compor a release coerente, evitando habilitar metade de uma política de acesso. Concluir um documento ou PR não equivale a fechar a etapa funcional.
- Deploy exige backup recuperável, migrações ensaiadas, revisão da release e comparação de conteúdo antes/depois. Preservar gabaritos, questões, cartões, respostas, notas e imagens. Nenhuma autorização de continuidade permite exclusão desses dados.
- Antes da janela, comunicar entrega concreta, impacto e necessidade de migração. Não pedir nova autorização rotineira para publicar entrega já autorizada; consultar o responsável apenas em decisão nova, ação fora do alcance ou impedimento que realmente dependa dele.

## R0-A: inventário conciliado

| Área | Evidência atual | Decisão e destino |
|---|---|---|
| Duplicação pedagógica | Prova.php possui configuração, autoria e estado; ProvaController relaciona prova_turmas/gabaritos; sem operação própria de duplicar identificada | CP1 cria rascunho no mesmo espaço com lista positiva, IDs novos e procedência, sem resultados/turmas. Não reutilizar processo T5 |
| Conteúdo de questões | Gabarito armazena alternativa, peso e anulação; editor textual completo não demonstrado | Copiar apenas conteúdo suportado. Não prometer variação de enunciado por duplicar gabarito |
| Transferências existentes | ElegibilidadeCopia aceita admin_rede, dir_escolar e professor_autonomo; jobs/serviços T5 já lidam com lotes e decisões | Reaproveitar infraestrutura após revisão; substituir capacidades genéricas incompatíveis em TR1. Renomear interface não altera autorização |
| Vínculos | ConviteVinculo parte de e-mail, preserva conta e cria vínculo; aceite exige inicio_em <= agora | VI1 acrescenta CPF-primeiro e aceite futuro sem antecipar vigência. Não destruir vínculos simultâneos |
| Instituições | ImportarCenso/CensoController/censo_escolas já existem; busca exige escola.criar, limite30, importador padrão BA | CI1 amplia contrato nacional e consulta pessoal sem conceder criação; preserva escolas operacionais e seus IDs |
| ECOAR | docs10, leitor CSV, config anual, importador, filtros e formulário UF/município consultados | Usar como referência técnica; sem modificar ECOAR, copiar banco ou depender de sua operação |
| Alunos e histórico | Turma atual e contexto histórico de aplicação já existem parcialmente | MT1 separa matrícula/enturmação por período; TE1 recebe histórico autorizado por corte, sem deslocar notas antigas |
| Guarda e L4-D/E | AvaliacaoGuardaTitular e teste são arquivos locais ainda não rastreados; doc113 é proposta anterior | Preservar arquivos sem integrar por acidente. Reconciliar retenção com identidade/matrículas/transferências antes de declarar L4 concluído |

## R0-B: decisões para implementação

Fonte atual de produto: orientações explícitas do usuário e planos115/116. Documentos antigos continuam como histórico; os avisos de revisão em40/77/102/114/28 impedem tratá-los como política nova. Novos nomes de pacotes são organização do trabalho, não aumento automático de permissões.

Não fazer alteração destrutiva: acrescentar estruturas, mapear legado, comparar conteúdo e só então trocar leitores/escritores. Não preencher autorias, CPFs, escolas ou datas históricas por suposição. Divergência bloqueia o lote afetado e gera conciliação, sem apagar registros existentes. Manifesto real do servidor deverá ser renovado antes de publicação; números históricos não substituem conferência atual.

Primeira entrega funcional: CP1, após seu contrato API/UX/dados e testes de autorização, independência e idempotência. Pode usar contexto existente e não depende de importar catálogo nem de identidade global. CI1-A pode preparar contrato do catálogo e de-para sem modificar dados pessoais. Execução sequencial local; nenhuma delegação/agente paralelo autorizada por esta conciliação.

Dependências posteriores: CI1 antes da seleção pessoal de instituição PA1-A; ID1 antes da consulta de identidade/VI1 e conciliação pessoal; DC1 antes da elegibilidade definitiva CP2; MT1/TR1 antes de transferências escolares TE1. L4-D/E deve ser adaptado aos novos modelos e à release planejada. Não publicar política antiga de transferência como se implementasse os planos115/116.

## Situação e próximo marco

R0 documental concluída com inventário de código e decisões de dependência. CI1-A ainda requer contrato detalhado de schema/rotas/permissões e inventário da base real; CP1 requer implementação e homologação. Planos e conciliação estão no PR150; sua integração seguirá revisão/checks, sem confundir merge com deploy. Não houve mudança funcional, migration, importação, notificação ou operação no servidor nesta rodada.
