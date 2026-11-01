# 60 — V1-C3: conciliação e preparo do corte

Data: 01/11/2026. Base: `276b2f1`, merge autorizado do PR #91. Autorização: seguir com V1-C3, sem deploy. Origem: [T1 aprovado](40-clientes-espacos-e-vinculos.md), [conversão V1-B](56-v1-conversao-conciliada.md) e [V1-C2b](59-v1-gestao-e-resultados-contextuais.md).

## Entrega e estado

Backend preparado para conciliação final, convite de novo vínculo a uma conta existente e recusa de acesso legado após um futuro corte. `vinculos.obrigatorio` permanece **false** por padrão. Nada foi convertido, ativado, migrado ou publicado no servidor. V2 ainda precisa resolver contexto confiável por aba/token e implementar a interface de seleção/gestão/convite antes de qualquer ativação. Este pacote não declara release pronta para deploy.

## Conferência final, sem sobrescrever vínculos

`g360:conciliar-vinculos` é somente leitura: não altera contas, permissões, vínculos, tokens, configuração ou filas. Em ambiente equivalente com as migrations aplicadas:

```sh
umask 077
php artisan g360:conciliar-vinculos --preparar > conciliacao-v1.json
# Revisar cada conta e seu acesso. Não alterar os hashes nem os dados da revisão.
sha256sum conciliacao-v1.json
php artisan g360:conciliar-vinculos conciliacao-v1.json --sha256=HASH_EXATO_DO_ARQUIVO
```

O rascunho inclui todas as contas, inclusive adiadas e inativas, sem nomes, e-mails, CPF ou tokens. Exibe candidatos/pendências da origem, permissões legadas, estado dos vínculos, restrições, ajustes e permissões efetivas. Cada conta começa com `acao: adiar`; o arquivo não aprova a si próprio.

Decisões: `preservar` confirma o estado atual revisado e exige algum vínculo válido; `bloqueado` confirma ausência de acesso operacional **no instante da conferência**, sem modificar identidade ou impedir futuro aceite de convite; `adiar` mantém uma pendência que impede conclusão. Uma conta com acesso válido não pode ser declarada bloqueada. Vínculo ativo e vigente com estrutura inválida também impede conclusão. Contas, hashes ou revisão adulterados/incompletos são recusados.

Impressões independentes da origem e do destino detectam alterações posteriores à conversão, incluindo permissões, vínculos, restrições, eventos e convites. O hash por conta inclui a revisão efetiva, inclusive a validade temporal. Um manifesto antigo nunca sincroniza ou reativa um vínculo suspenso/encerrado. Se houver divergência, a revisão deve decidir o estado correto por operações autorizadas e gerar novo manifesto; não editar hashes nem excluir histórico para contornar a conferência.

Para contas ainda sem vínculos, usar o fluxo V1-B de preparação/simulação/aplicação **somente no corte aprovado**, com nova origem revisada. O V1-B permite adiar contas já convertidas enquanto cria vínculos das restantes; ele continua recusando reaplicação sobre um vínculo alterado. A conferência C3 então exige uma decisão de todas as contas e não aceita adiamento residual como conclusão. Perfis ainda não suportados, como secretaria, precisam de decisão explícita; não recebem administrador ou vínculo universal automaticamente.

O relatório também bloqueia conclusão por escopos órfãos, recibos B6 legados na fila/processando/erro e convites legados ainda válidos. Esses itens precisam de tratamento operacional revisado; não há descarte, expiração antecipada ou conversão automática de tokens nesta entrega. Convites existentes preservam seu prazo e conteúdo. Não se deve forçar a troca de estado de uma conta estabelecida para reaproveitar convite de primeira senha.

`conciliacao_aprovavel: true` significa apenas que a fotografia revisada passou pelos critérios de dados. O relatório sempre retorna `publicacao_autorizada: false` e lista os requisitos externos: V2, ensaio, backup, escritores pausados, inspeção de Redis/jobs e autorização do responsável. Uma leitura transacional não comprova pausa de escritores nem esvaziamento de filas externas. Repetir a conferência na futura janela, após a pausa.

## Convite de novo vínculo para conta existente

`vinculo_convites` preserva histórico, hash SHA-256 de token aleatório, prazo de sete dias, aceite/recusa, pessoa emissora e seu vínculo. A FK composta exige que o vínculo emissor pertença à pessoa emissora. Expansão aditiva e retomável; nenhum convite legado é apagado ou convertido por esta migration.

O gestor contextual informa e-mail, espaço, perfil e, opcionalmente, turmas de professor. Perfil/hierarquia/destino são autorizados antes da busca da conta. A resposta HTTP é genérica para conta ausente/inativa, autoatribuição e papel já existente; não devolve token, ID da pessoa ou seus dados globais. Turmas fora do alcance são recusadas independentemente de a conta existir. Reenvio conserva o alcance, invalida somente os convites daquele vínculo e não reabre vínculos ativos, suspensos ou encerrados.

Aceitar/recusar exige autenticação da própria pessoa e token válido, pertencente àquele convite, ainda não usado e dentro do prazo. Aceitar revalida também o vínculo e os poderes atuais do emissor; uma revogação impede a concessão. Recusar encerra somente o vínculo convidado. Não muda senha, perfil global, conta ativa, outros vínculos ou tokens de recuperação. Operações são transacionais e bloqueiam os registros envolvidos; ficam registradas em `vinculo_eventos`.

Rotas preparadas:

| Rota | Autorização |
|---|---|
| `POST /api/v1/usuarios/convites-vinculo` | `usuario.gerir`, contexto do gestor, hierarquia e destino; limite de requisições |
| `POST /api/v1/auth/convites-vinculo/{id}/consultar` | Identidade titular + token; sem exigir vínculo ativo previamente |
| `POST /api/v1/auth/convites-vinculo/{id}/responder` | Identidade titular + token e booleano `aceitar`; limite de requisições |

O e-mail usa modelo próprio que não pede nova senha. O link reserva `/vinculos/convite` para V2, com ID/token no fragmento (não em query de GET). **Não liberar esse fluxo aos usuários antes de V2 implementar a página** e retirar o fragmento do histórico após consumo. Nesta entrega só há testes com e-mail simulado. O convite de primeira conta continua separado em `ContaUsuario`/C2b.

## Regime sem retorno ao legado

`VINCULOS_OBRIGATORIOS` é a chave de corte preparada, desligada por padrão. Não foi configurada no servidor. Quando habilitada em ensaio:

- Rotas operacionais exigem um objeto autenticado com vínculo validado. Payload, query ou header com ID de vínculo não selecionam contexto.
- `Permissao`, `Escopo`, referências institucionais e consultas com ator recusam contexto ausente antes de reutilizar cache ou perfil legados. Desligar `espacos.ativo` não recupera acesso.
- Login fornece token de identidade, perfil nulo, permissões vazias e indicação de seleção. A própria identidade, troca de senha/e-mail e resposta ao convite permanecem acessíveis antes da escolha. V2 fará a associação do token/contexto e a navegação.
- Links assinados antigos sem vínculo são recusados; jobs B6 sem contexto não executam OMR nem concluem recibos. Casos pendentes devem ter sido conciliados antes do corte.
- Importadores de alunos/provas/Censo aceitam `--vinculo`; após o corte, falta de vínculo ou destino fora dele é recusada. Importação de provas respeita as turmas do vínculo, sem recuperar as turmas do perfil global.
- O conversor legado não aplica dados depois de habilitado o corte. Nenhuma rota ou comando desta entrega liga a configuração automaticamente.

## Critérios antes da publicação futura

1. Incorporar V1 e concluir V2: escolha por aba, menus, formulários de gestão, convite e transporte confiável do contexto.
2. Ensaiar em ambiente equivalente com dados fictícios/anonimizados, incluindo dois vínculos da mesma pessoa, duas redes, contas sem vínculo, convites e revogação durante jobs/PDFs.
3. Medir consultas e latência do núcleo sem cache persistente de autorização; corrigir regressões antes do corte.
4. Planejar a publicação com o procedimento de pausa/backup/restauração já aprovado; incluir web, API, workers, timer, importadores e demais escritores.
5. Aplicar migrations/conversão apenas com autorização específica; conferir manifesto e filas novamente; não retomar a operação enquanto houver pendências.
6. Habilitar contexto obrigatório somente com V2 pronto; revalidar web/API/filas/arquivos e aprovar reabertura. Não desligar o regime para contornar falhas de autorização.

Testes novos em `CorteVinculosTest`; regressões de C1/C2, permissões, e-mails e B6 preservadas. `FilaVinculoTest` cobre recusa do job legado no corte e `VinculosMariaDbTest` cobre DDL retomável, histórico e FK composta do emissor. O ensaio local não substitui a conferência no servidor da futura release.
