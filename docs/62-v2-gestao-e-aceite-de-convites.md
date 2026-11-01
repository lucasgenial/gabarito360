# 62 — V2-C: gestão e aceite de convites

01/11/2026. Origem: T1 (docs/40), V2 do plano mestre, V1-C2b/C3 e autorização explícita para incorporar PR #94 e seguir com V2-C, sem deploy. Base `a544720`. Complementa [V2-A/B](61-v2-contexto-autenticado.md).

## Gestão no vínculo

Cada linha da equipe representa um vínculo: instituição, função, situação e ID próprio. Detalhes, suspensão/reativação e permissões enviam `vinculo_id` explicitamente. Não há edição da identidade global, senha ou papel por essa tela; mudança de papel exige outro vínculo, preservando o histórico. Convites/encerrados não são ativados por botão de gestão. O primeiro acesso pendente mantém a opção separada de reenviar seu convite. A criação de nova conta segue o fluxo já aprovado e leva o vínculo retornado às permissões.

A página de permissões preserva o alvo e mostra o histórico contextual. Corrigida a leitura de chaves com ponto nos formulários (`prova.ver`, por exemplo), incluindo validade de extras. Associação e remoção de professores em turmas também transportam o vínculo exato, sem confundir duas linhas da mesma pessoa.

## Convite para conta existente

`GET /api/v1/usuarios/convites-vinculo/opcoes` lista somente instituições, funções e turmas que o gestor pode atribuir. Usa contexto autenticado, catálogo de perfis e alcance institucional. `GET/POST /membros/convidar-vinculo` mostra/enfileira o convite via endpoint existente. O cadastro recebe resposta genérica, sem revelar existência da conta ou retornar token ao gestor. Reenvio só preservando o mesmo alcance; não reabre vínculo suspenso/encerrado. Nenhum e-mail real é enviado nos testes.

## Aceite pela própria pessoa

`GET /vinculos/convite` abre uma página sem dados do convite. O JS lê ID/token do fragmento, remove imediatamente o fragmento do histórico e guarda os dados temporariamente no `sessionStorage` da aba por até 20 minutos para atravessar o login. Não usa query, localStorage ou sessão compartilhada para o token do convite. Se o armazenamento não estiver disponível ou expirar, a pessoa reabre o link.

Login e troca obrigatória de senha retornam a uma rota fixa de convite, sem aceitar URLs de redirecionamento arbitrárias. A consulta exige identidade autenticada, sem exigir vínculo ativo anterior. O servidor usa o token de identidade, e a API valida titularidade, prazo, uso e situação. Após consulta, o navegador remove o segredo do armazenamento e recebe uma referência aleatória; ID/token ficam cifrados em cache por 20 minutos, vinculados à sessão/identidade. O formulário de aceitar/recusar envia somente a referência e decisão, com CSRF. Token/referência não são preservados como entrada antiga em falhas de validação.

O aceite revalida os poderes do emissor na API. A recusa encerra apenas o vínculo convidado. A resposta não altera senha, perfil global ou outros vínculos; a escolha seguinte é explícita. Replay, referência desconhecida, conta alheia e vínculo revogado são recusados. Erros não reenviam automaticamente decisões. Erros de validação em AJAX retornam JSON, sem redirecionar para HTML.

## Validação e limite da entrega

Testes de API cobrem opções autorizadas e o percurso emissão/e-mail simulado/consulta/aceite/seleção com tokens reais, além das regressões de gestão, corte e autenticação. Testes web cobrem duas linhas da mesma pessoa, payload restrito ao vínculo, permissões com ponto, turma, convite genérico, referência de confirmação, segredo fora da sessão/HTML, login e troca de senha.

O script `scripts/auditoria/convite-integrado.mjs` exercita web/API reais com uma fixture fictícia equivalente à de `ConviteInterfaceTest`, em processos locais isolados: conta 1 diretora da escola, conta 2 professora, convite adicional de coordenação. Recebe `CHROME_PATH`, `WEB_URL` e `V2C_FIXTURE` (JSON com convite_id/token/vinculo_id emitidos na base descartável). Esse cenário não usa serviços externos nem dados do servidor. O banco SQLite desse ensaio local não substitui o MariaDB: CI também executa os testes de convite no MariaDB 10.11.

Sem deploy, migration ou mudança de configuração no servidor. V2-C permanece em revisão. Antes de recomendar publicação, ainda é necessário o ensaio da release V1/V2 em ambiente equivalente, com conciliação revisada, latência, filas/PDFs, migrations, pausa/backup e recuperação conforme docs/60. O próximo pacote de desenvolvimento do plano é T3 (perfis por tipo de espaço).
