# 59 — V1-C2b: gestão, projeções e resultado individual

Data: 01/11/2026. Base: `61e8722`, merge autorizado do PR #90. Execução autorizada pelo responsável: “Pode seguir com o V1-C2b, sem deploy agora.” Escopo de origem: [V1-C2a](58-v1-fila-e-consultas-contextuais.md), [T1](40-clientes-espacos-e-vinculos.md) e catálogo de permissões vigente.

## Gestão contextual

As rotas existentes de usuários e professor/turma reconhecem o contexto explícito já validado no objeto autenticado. Este pacote **não** resolve o ator a partir de `vinculo_id` recebido na requisição: esse campo identifica exclusivamente o vínculo **alvo** da gestão. V2 continua responsável pelo contexto autenticado e pelas telas.

`GestaoVinculos` limita os alvos à instituição e descendentes do ator, revalida espaços/clientes e deriva o perfil de cada vínculo. Um alvo com mais de um vínculo visível exige `vinculo_id`; não se escolhe o primeiro. Vínculo fora do alcance retorna 404. Gestão de si mesmo e de perfis fora da hierarquia é recusada. Listagens podem conter duas linhas da mesma pessoa, identificadas pelo vínculo.

Suspensão/reativação muda somente `usuario_vinculos.situacao`, incrementa a versão de autorização e registra evento. Não altera `usuarios.ativo`, outros vínculos, credenciais ou escopos legados. Encerrado, convidado e vigência expirada não são reativados por toggle. A conta global inativa continua sem acesso, independentemente do vínculo.

A edição contextual recusa campos da identidade global. Também recusa trocar espaço ou papel na mesma linha: isso exige fluxo de outro vínculo preservando o histórico. As rotas de identidade do próprio titular permanecem globais; a recuperação de senha continua disponível ao titular, sem novo limite de oito horas. O gestor contextual não envia redefinição da senha global de outra pessoa.

Permissões usam `vinculo_permissoes` e a hierarquia/catálogo existentes. Todo lote é transacional: uma concessão inválida reverte inclusive as alterações anteriores do lote. O histórico guarda ator, ação, chave técnica e validade sem dados livres sensíveis. A versão muda a cada alteração. Vínculos distintos não compartilham ajustes ou histórico. A gestão legada não modifica contas já convertidas; vínculos professor/turma convertidos também não aceitam escrita no escopo legado.

## Convite de primeira conta

O cadastro contextual de uma **nova identidade** cria um vínculo convidado e suas turmas no espaço validado, sem escrever `usuario_escopos`. O convite de primeira senha conserva o vínculo em `password_reset_tokens.vinculo_id`. Sua consulta e uso conferem titularidade, situação, vigência e hierarquia; aceite ativa somente esse vínculo e recusa encerra somente esse vínculo. Ambos conservam a identidade global. Tokens legados emitidos antes da conversão não podem desativar uma conta convertida.

Essa adaptação preserva o contrato atual de primeira senha. Por isso somente uma conta sem acesso anterior e com um único vínculo convidado pode usar esse fluxo. Não é o convite de um novo papel para uma conta estabelecida: a tabela/fluxo `vinculo_convites` previsto em T1 continua necessário para esse caso, sem redefinir senha, e deve ser incluído na verificação de prontidão do V1-C3. Não reutilizar convite de primeira senha para contas existentes. A criação de novos administradores permanece bloqueada no cadastro legado, como no T2.

A migration aditiva `2026_11_01_000004_contextualizar_convites.php` acrescenta o vínculo nullable com FK aos tokens e `valida_ate` nullable aos eventos. Mantém tokens antigos e permite repetir a expansão. Não foi executada no servidor. Aceite/recusa usam transação e bloqueio do registro do token; o link é de uso único. Recuperação normal limpa o contexto de convite ao substituir o token.

## Projeções e resultado individual

Escolas, painel escolar e últimos acessos do administrador projetam o papel do vínculo local. Contas/vínculos não vigentes ficam fora da equipe ativa; referências globais ou vínculos de outra rede não determinam o perfil exibido. A relação professor/turma usa `vinculo_turmas`, valida espaço e escola e registra inclusão/remoção com versão de autorização.

O aluno contextual consulta apenas o registro explicitamente atribuído em `vinculo_alunos`. A prova precisa pertencer ao mesmo espaço e ter relação coerente com o aluno/cartão/nota. Isso não libera listagem ou conteúdo geral de provas, turmas, cartões ou colegas. O resultado individual e o payload do PDF não incluem média da turma. Após gerar o PDF, autorização e vínculo são revalidados; uma revogação descarta o arquivo temporário e recusa o download.

## Verificação e limites

`GestaoVinculoTest` cobre rotas reais com Sanctum contextual: dois papéis em instituições distintas, alvo ambíguo, campos globais recusados, autoalteração, suspensão isolada, concessões e rollback, equipes/turmas, vigência/hierarquia, criação/aceite/recusa de convite, tokens revogados e legados, resultado próprio e revogação durante PDF. O renderizador externo é simulado para verificar payload e autorização, sem envio real de e-mail. `VinculosMariaDbTest` verifica DDL retomável, FK do convite e preservação do histórico no MariaDB descartável.

A revisão dos consumidores HTTP mantém `AuthController` como gestão da própria identidade; configuração institucional usa `Instituicoes`; assinaturas permanecem do titular e serão migradas em F1; links assinados e B6 já transportam vínculo em C1/C2a. Importadores CLI ainda recebem ator legado explícito e devem ser incluídos no corte sem fallback do C3 — não são endpoints autenticados por contexto.

Próximo pacote: **V1-C3**, conciliação final, contas adiadas/alteradas, convite para conta existente e fechamento das condições de ativação. V2 ainda deve adaptar seleção por aba e interface de gestão aos IDs de vínculo. A entrega C2b não torna V1 pronto para publicação. Sem deploy, conversão ou migration no servidor; merge e publicação dependem de aprovação específica.


Atualização: C2b incorporado no PR #91 (`276b2f1`). A preparação C3 e o convite para conta existente estão em [60-v1-conciliacao-e-preparo-do-corte.md](60-v1-conciliacao-e-preparo-do-corte.md), ainda em revisão e sem deploy.
