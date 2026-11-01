# 56 — V1-B: conversão conciliada dos vínculos

Data: 01/11/2026. Base: `28b3fa7`, merge autorizado do PR #87. Continuação de [V1-A](55-v1-estrutura-e-inventario.md), conforme [T1](40-clientes-espacos-e-vinculos.md).
Atualização: V1-B incorporado no PR #88 em `7a13ffe`. Continuidade em [V1-C1](57-v1-nucleo-de-autorizacao.md), sem ativação ou publicação.

## Entrega e limite de ativação

Implementados `vinculo_turmas` e `vinculo_alunos`, com unicidade por vínculo/referência e FKs compostas que exigem o mesmo espaço no vínculo e no registro. Exclusão e mudança de proprietário são restritas. A migration reutiliza os índices compostos T2-E ou os cria em instalação nova; não preenche vínculos nem altera dados existentes. A reversão automática continua bloqueada.

O conversor é operacional, sem endpoint público. Cria somente vínculos iniciais ativos para contas ativas e destinos institucionais coerentes. Valida perfil/espaço pela origem, início UTC não futuro, fim nulo e versão inicial 1. Não é um serviço genérico para convites ou transições de estado: suspensão, encerramento e gestão cotidiana continuam nas próximas entregas. Não converte tokens de convite/recuperação nem altera sua validade.

**Esta entrega não troca o motor de autorização.** `usuarios.perfil`, escopos, permissões e histórico legados permanecem intactos. A API, a web, as filas e os links continuam usando o contexto atual. V1-C deve reconciliar novamente a origem antes de ativar: alterações feitas no modelo atual após esta conversão não são sincronizadas automaticamente para os vínculos.

## Manifesto e simulação

Em ambiente de teste com as migrations aplicadas, executar na API:

```sh
umask 077
php artisan g360:inventariar-vinculos > inventario-v1.json
php artisan g360:converter-vinculos --preparar > manifesto-v1.json
```

O rascunho usa `acao: pendente`, propositalmente recusada pelo conversor. Revisar cada conta a partir do inventário e produzir o manifesto final. Arquivos contêm IDs e permissões: acesso restrito, sem incluir dados reais em PRs. Não incluir nomes, e-mails, CPF, credenciais ou justificativas livres.

Estrutura ilustrativa, com IDs fictícios e hash a substituir pelo gerado:

```json
{
  "versao": "v1-b-1",
  "fonte_sha256": "HASH_GERADO_NO_RASCUNHO",
  "inicio_em": "2026-11-01 12:00:00",
  "usuarios": [
    {
      "usuario_id": 1,
      "acao": "converter",
      "motivo": "conversao_revisada",
      "vinculos": [
        {"espaco_id": 1, "turmas": [], "alunos": [], "ajustes": [], "reducoes_aceitas": []},
        {"espaco_id": 2, "turmas": [], "alunos": [], "ajustes": [], "reducoes_aceitas": []}
      ]
    },
    {
      "usuario_id": 2,
      "acao": "adiar",
      "motivo": "sem_destino",
      "vinculos": []
    }
  ]
}
```

Todas as contas devem aparecer exatamente uma vez. `adiar` preserva a conta e registra a pendência no relatório; não cria, apaga ou suspende vínculos. Motivos permitidos: `conversao_revisada`, `sem_destino`, `origem_inconsistente`, `conta_inativa`, `aguardando_conciliacao`. Converter exige `conversao_revisada`. IDs são inteiros JSON. Não se permite inventar destino, mudar perfil ou alterar as restrições propostas pela origem.

`ajustes` contém IDs de `usuario_permissoes`, não chaves de catálogo. Cada ajuste, inclusive vencido, precisa de pelo menos um destino explicitamente escolhido. Preservam-se efeito, validade e autor original. Não há multiplicação automática; indicar o mesmo ID em vários destinos representa uma decisão explícita de manter esse ajuste em cada um deles.

A comparação usa o catálogo e a validade na data informada em `permissoes_em`, sem cache do motor atual. Recusa poderes adicionais, inclusive quando a falta de uma retirada faria reaparecer uma permissão padrão. Se uma concessão deve ficar apenas no vínculo A, as permissões que desaparecerão do vínculo B precisam estar em `reducoes_aceitas` de B, exatamente iguais à diferença calculada. Ajuste incompatível com o perfil exige conciliação da origem. Vencimento é preservado; nunca convertido em concessão permanente.

```sh
php artisan g360:converter-vinculos manifesto-v1.json
sha256sum manifesto-v1.json
```

A execução padrão só simula e retorna comparação antes/depois por pessoa/espaço, contas adiadas, hash do arquivo e hash da representação interna do manifesto. Código 0 não autoriza publicação nem indica V1 concluído. Revisar o relatório e o hash exato do arquivo antes de aprovar aplicação.

## Aplicação futura, atomicidade e retomada

Nenhuma aplicação no servidor foi executada ou autorizada nesta entrega. Uma aplicação futura exige aprovação operacional, backup validado, manutenção e pausa de todos os escritores (web, workers, timer, CLI e integrações), conforme procedimento de corte vigente. O código confere manutenção fora de `testing`; essa verificação não comprova que todos os escritores foram pausados.

Na execução aprovada, a CLI exige `--aplicar`, `--sha256` igual ao hash do **arquivo** revisado e `--ator` com administrador executor ativo. O executor é autoria da conversão e do evento, não é atribuído retroativamente como autor das permissões antigas. Não usar o hash interno do manifesto no argumento `--sha256`.

O serviço compara a impressão das fontes de autorização (contas/perfis/estado, escopos, ajustes, catálogo, instituições, espaços e relações de turma/aluno) antes de escrever. Não inclui conteúdo pessoal ou pedagógico. Mudanças nessas fontes invalidam o manifesto. A aplicação bloqueia as linhas dessas fontes durante a transação MariaDB; por isso deve ocorrer com escritores pausados. A CLI também usa lock de execução. Essas proteções não substituem o procedimento operacional.

Todo o lote de DML é uma transação: qualquer conta inválida desfaz inclusive os vínculos já criados anteriormente no lote. O DDL aditivo é separado e não é tratado como transação de dados. Cada vínculo criado recebe um único evento `conversao_legada` com identificação técnica do manifesto.

Repetir o mesmo manifesto confere vínculos, versão, situação, início/fim, turmas/alunos, permissões e evento antes de reutilizá-los. Não duplica nem sobrescreve; vínculo suspenso ou alterado bloqueia retomada. Um vínculo externo à conversão também bloqueia. Se a origem mudar após a conversão, preparar nova conciliação; não editar o hash para forçar reaplicação e não apagar vínculos para contornar a checagem. O fluxo de atualização/reconciliação para ativação será V1-C.

## Validação e próximo passo

Testes cobrem os sete perfis escolares do catálogo atual, duas redes, restrições por turma/aluno, concessão com destino único, retiradas, ajustes vencidos, simulação sem escrita, hash antigo, manifesto incompleto, ajuste alheio, repetição, suspensão posterior e rollback integral. `PerfisTest` continua sem alterações. Testes MariaDB cobrem DDL retomável, FKs compostas contra cruzamento de espaços e conversão repetida em banco descartável.

Próximo: **V1-C**, adaptar `Permissao`, `Escopo` e todos os consumidores ao vínculo ativo, com vigência/revogação, isolamento de poderes e dados, filas e links assinados. Também preparar a conciliação final para ativação, sem ignorar as contas adiadas. V2 cuidará da escolha na interface e isolamento por aba. Não há deploy nem migration no servidor nesta fase.
