# 45 — T2-D2: instituições e gestão legada

Base: PR #76 incorporado em `1fc27bfd046eb04c505c51c19c55943884e4d846`. Continuidade autorizada pelo responsável, **sem deploy**. Deriva de [T1 aprovado](40-clientes-espacos-e-vinculos.md), do [manifesto](42-t2-inventario-e-manifesto.md) e do [roteiro D/E](44-t2-adaptacao-e-corte.md).

## Entrega incorporada — PR #77

Merge em `24e335ae3927fb6291f7346d18fc219c65d1654e`, sem deploy. Continuidade: [T2-D3](46-t2-cartoes-fila-e-arquivos.md).

- Núcleos e escolas passam a usar o adaptador institucional. No modo novo, listas, detalhes, alterações, logos e contagens dependem da instituição atribuída e da hierarquia de espaços ativa. Agregados escolares conferem espaço e relações; referências de sede/anexos são limitadas antes da consulta.
- Criação de núcleo/escola no modo novo cria o espaço na mesma transação, herdando o cliente do pai autorizado. O payload não escolhe cliente ou espaço. Mudança de rede/núcleo por edição simples é recusada; núcleo com espaço registrado não é apagado pelo CRUD.
- Configuração aceita `rede_id` explícito e autorizado. No modo novo, zero redes resulta em 404, uma pode ser resolvida sem ambiguidade e duas exigem seleção (422 sem ID). Não usa a primeira rede como autoridade nesse modo.
- Logos são autorizados antes da abertura, sem cache reutilizável após revogação. Novos uploads ficam em diretórios da instituição. Arquivos anteriores continuam acessíveis pelo caminho existente quando autorizados; nenhuma movimentação ou exclusão de arquivos foi executada nesta entrega. Aluno pode consultar o logo da própria escola sem receber acesso às turmas da escola.
- Totais de usuários usam a mesma abrangência da lista, inclusive no regime legado. No modo novo, consultas de gestão exigem ao menos um escopo conhecido e que **todos** os escopos da conta estejam dentro da gestão. Conta com referência externa, suspensa, desconhecida ou sem destino não pode sofrer alteração global por essa gestão.
- Esse bloqueio transitório evita que alteração de perfil, ativação, convite, senha ou permissões globais afete outra instituição. Não substitui V1. Alteração de perfil/escola e criação de novos administradores pelo CRUD ficam recusadas no modo novo, aguardando gestão por vínculo. Cadastro de usuários e escopos é transacional; atributos institucionais incompatíveis com o perfil não são inseridos.
- Associação professor–turma exige turma autorizada e vínculo escolar explícito do professor. Listagem de turmas é filtrada pelo gestor; o professor mantém consulta às próprias turmas. A associação escolar existente em outra rede não é removida.

## Conciliação preparada do administrador

`g360:conciliar-admin-espacos --usuario=ID --manifesto=legado-20261031-v1` simula, sem gravar atribuições. O ID deve ser conferido pelo responsável; o comando não busca por nome, não escolhe o primeiro usuário e não imprime contatos.

Antes de aceitar, confere inventário/hierarquia, exatamente um administrador ativo no conjunto de administradores, ID informado, clientes internos previstos e espaços das redes 1 e 2 ativos. Escopos existentes só podem ser atribuições únicas dessas duas redes. Divergência interrompe sem corrigir ou sobrescrever.

`--aplicar` acrescenta somente as duas atribuições legadas que faltam, em transação, com lock compartilhado com o preenchimento T2. Repetição não duplica. Fora de testes, exige manutenção. O procedimento operacional precisa parar escritores e terminar dentro da vigência de uma hora do lock. Não cria `usuario_vinculos`, não concede permissões extras, não ativa T2 e não altera tokens.

**O comando não foi executado em produção**, nem em simulação nesta entrega. Aplicação real exige autorização operacional, backup e verificação da conta. A autorização do modelo de acesso às duas redes permanece registrada; esta aprovação de código não é deploy nem execução do comando.

## Limites e próximo passo

`config/espacos.php` continua com `ativo = false`, sem chave de ambiente. O regime legado de configuração sem `rede_id` conserva a compatibilidade da tela atual; a seleção/propagação da rede pela web deve estar pronta antes do corte. Não ativar o adaptador isoladamente.

Gestão por vínculo, múltiplos perfis e contexto por aba continuam em V1/V2. Retenção, contratos, exclusão de dados e cobrança não são alterados. Cabeçalhos internos de relatórios continuam dependentes da autorização dos consumidores, que entram na revisão D3/D4.

Próxima parte: **D3 — gabaritos, cartões, notas, fila e arquivos**, seguida de D4 (relatórios, painéis, imports e propagação do contexto) e E (unicidades, FKs e campos obrigatórios). O corte só pode ocorrer após essa conclusão e autorização própria.

## Validação

Testes fictícios cobrem redes distintas, suspensão, seleção obrigatória, contagens, criação com cliente herdado, bloqueio de transferência, logos, conta com escopos mistos, gestão de permissões, cadastro e associação professor–turma. A conciliação testa simulação, repetição e recusa de conta/escopo ambíguos. Há ensaio local isolado em MariaDB e regressões da API/web no CI. Resultados finais registrados no PR; não são certificação de conclusão de T2-D/E.
