# 58 — V1-C2a: fila B6 e referências do ator

Data: 01/11/2026. Base: `298c60f`, merge autorizado do PR #89. Continuação do [núcleo V1-C1](57-v1-nucleo-de-autorizacao.md).

## Entrega

O envio de cartão passa a conservar `vinculo_id` no recibo e no payload escalar do job. A FK composta `(usuario_id,vinculo_id)` impede atribuir ao recibo vínculo de outra pessoa. O espaço do recibo continua sendo o espaço escolar do cartão: não se exige igualdade com o espaço do vínculo, pois um gestor pode atuar por um vínculo de rede/núcleo. Autorização do destino continua sendo conferida pelo núcleo.

A expansão adiciona campos nullable sem converter recibos existentes. Registros e jobs antigos permanecem com contexto nulo; um job sem vínculo não processa recibo contextual. A migration pode ser retomada e não remove automaticamente os campos no rollback. Nenhuma migration foi executada no servidor nesta entrega.

## Idempotência e acesso ao recibo

Para envio contextual, `chave` é um UUID v5 determinístico com namespace URL e nome composto por prefixo técnico, pessoa, vínculo, espaço e UUID fornecido pelo cliente. `chave_cliente` preserva o UUID público. Assim, dois vínculos da mesma pessoa podem usar o mesmo UUID público sem compartilhar recibo, lock ou resultado, mantendo os índices únicos existentes do T2. A chave derivada não é segredo nem substitui autorização.

Envio legado mantém a chave original. Colisões de chave interna não permitem atravessar contexto: o recibo existente precisa corresponder à pessoa e ao vínculo, inclusive distinguindo nulo de contextual. Respostas devolvem `chave_cliente` quando presente, preservando o contrato da interface.

Polling e retentativa derivam a chave com o contexto atual e filtram o vínculo. `ResultadoEnvio` também exige identidade/contexto antes de ler ou atualizar recibos, inclusive quando o cartão foi removido. O aplicador continua recebendo somente os campos permitidos pelo contrato B6.

## Worker e revogação

O job transporta IDs de pessoa, vínculo, espaço, cartão, recibo e tentativa. A seleção do recibo exige o vínculo exato. Payload com vínculo ausente ou divergente não processa nem marca como falha um recibo de outro contexto.

Antes do OMR, o worker reconstrói `Usuario::noVinculo()` e confere permissão, prova e aluno. Antes da gravação, recarrega a pessoa preservando o vínculo e repete a autorização. Suspensão/encerramento durante o processamento resulta em recusa sem respostas, nota ou recibo concluído; a foto/cartão são preservados para conciliação, conforme comportamento T2. Falhas técnicas continuam usando a retentativa B6. Um QR não pode recuperar os poderes do perfil global da conta para acessar outra escola.

Compatibilidade testada também com payload serializado sem a nova propriedade `vinculoId`; o valor padrão nulo conserva o tratamento de jobs antigos. Isso não libera fallback para recibos contextuais.

## Consultas e pendências

Referências do ator no painel de professor/admin, relatório de núcleo, resolução de núcleo do Censo e autorização de logo da rede passam por `Instituicoes`/`Escopo`. O Censo considera somente núcleos acessíveis no modo T2.

**Registro da entrega C2a:** as pendências abaixo foram tratadas na implementação do [V1-C2b](59-v1-gestao-e-resultados-contextuais.md), em revisão. A seleção de vínculo permanece não ativada. Escopo encaminhado ao C2b:

- Gestão por vínculo em `UsuarioController`, `VinculoProfessorController`, `GestaoLegada` e `ContaUsuario`, evitando alteração global da identidade ou desativação de todos os vínculos ao operar sobre apenas um.
- Projeções de terceiros que ainda consultam `usuario_escopos` em dashboards/escolas; listar os perfis do vínculo, sem usar o perfil global como autoridade.
- Consumidor de resultado individual do aluno e revisão das demais rotas com contexto autenticado.

Depois, V1-C3 deve preparar conciliação final, contas adiadas, alterações posteriores à conversão e ativação sem fallback. V2 continua responsável pela experiência de escolha por aba e pelo estado de envio no navegador particionado por contexto. Não ligar o contexto ao login antes de concluir essas integrações.

## Validação

`FilaVinculoTest` exercita as rotas de upload/polling/retentativa com usuário contextual autenticado em teste, execução real do job com driver OMR simulado, dois vínculos com o mesmo UUID, revogação antes/durante a leitura, QR fora de contexto e compatibilidade de payload antigo. `CartoesFilaTest` e `PerfisTest` permanecem sem edição. `VinculosMariaDbTest` verifica a FK composta, preservação do recibo, legado nullable e retomada do DDL no MariaDB descartável.

Sem deploy, conversão, migration ou modificação de dados no servidor. A publicação futura exige migrations antes de reiniciar workers e deve ser coordenada com o procedimento operacional aprovado; não há autorização implícita de publicação neste documento.
