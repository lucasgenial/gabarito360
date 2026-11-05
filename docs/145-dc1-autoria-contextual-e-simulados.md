# DC1 — autoria contextual, disponibilização e simulados existentes

04/11/2026. Revisão antes da implementação após os esclarecimentos do responsável. O catálogo de áreas está no PR174; este complemento não altera seu propósito nem inicia o planejamento do GII.

## Decisões recebidas

- O mesmo usuário pode atuar como Professor e Coordenador, inclusive na mesma escola. A prova registra a função e o vínculo selecionados na criação. Trocar de função depois não converte a autoria nem soma permissões.
- O responsável informou que os quatro simulados ENEM foram criados como Professor antes da mudança para Coordenador. O banco atual registra perfil de criação Coordenador. Em esclarecimento posterior, autorizou **adaptar os simulados de desenvolvimento como se tivessem sido gerados com as novas funcionalidades**, sem criar uma verificação/fluxo especial de legado agora. O pedido anterior de configurá-los como provas da coordenação continua válido.
- Essa adaptação pontual de dados de desenvolvimento será registrada com procedência própria e auditoria. Não implica converter automaticamente provas de usuários reais quando mudam de cargo, nem criar nesta etapa um recurso genérico de institucionalização.
- É autorizado adaptar provas aplicadas, sem excluir provas, gabaritos, cartões, respostas, notas, alunos ou associações de turma. Nenhuma reclassificação histórica por nome/perfil atual em massa.

## Implementação e limites

1. Capturar vínculo, perfil e procedência da autoria nas novas criações e cópias. Proteger no banco a autoria já registrada/conferida contra reescrita posterior. Legado sem contexto permanece identificado; não inventar um vínculo passado a partir do vínculo atual.
2. Adaptar explicitamente os quatro simulados autorizados à configuração institucional de Coordenador, com vínculo correspondente e procedência `adaptacao_desenvolvimento`. Registrar o estado anterior em auditoria. Sem verificação operacional diferente para essas provas, sem inferência em massa e sem endpoint genérico para adulterar autoria. Não implementar um domínio paralelo de disponibilização nesta etapa.
3. Associar provas a disciplinas por IDs do catálogo do mesmo espaço, inclusive várias disciplinas para os simulados por área. Área é agrupamento pedagógico, sem concessão de acesso. Professor precisa de indicação e atuação na disciplina de uma turma vinculada à prova e de autorização nessa turma. Coordenação/direção preservam visão de toda a escola.
4. Os quatro simulados atuais serão classificados explicitamente conforme seus títulos de área e os componentes oferecidos; manter texto legado, IDs, gabaritos e resultados. A prova por área é acessível como um todo aos docentes das disciplinas participantes, sem simular divisão de questões por disciplina (ainda não cadastrada).
5. Criar contas de teste numeradas por disciplina utilizada, apenas em homologação. Reutilizar o hash da senha da conta de teste existente no próprio ambiente, sem extrair senha nem imprimir hash; não alterar a senha de contas preexistentes. Identificadores fictícios autorizados anteriormente ficam identificados como teste. Vínculos e atribuições locais são explícitos, com aceite de teste documentado e sem e-mail real.
6. Revalidar listagem, detalhe, gabarito, resultados individuais, PDFs e demais consumidores do escopo para impedir contorno por URL direta; cópia/edição institucional não ficam disponíveis no contexto Professor, mesmo sendo a mesma pessoa.

## Sequência e comprovação

| Parte | Situação inicial |
|---|---|
| Áreas e disciplinas N:N | Implementado no PR174; CI/revisão final em andamento |
| Contexto original de criação e dupla função | Perfil já capturado; vínculo/procedência e testes adicionais em implementação |
| Adaptação dos quatro simulados à criação institucional | Autorizada; preparação em implementação |
| Disciplinas nas provas novas/existentes e escopo docente | Integração pendente; helper interno disponível |
| Contas de teste e quatro simulados na homologação | Autorizado; executar após regras de isolamento estarem publicadas |
| DC1 completo | Fechamento após testes, parecer visual independente, CI e ensaio recuperável |
| Produção | Nenhuma publicação autorizada |

Testar criação real sob os dois vínculos da mesma pessoa, mudança posterior de função, tentativa de forjar perfil/vínculo no payload e alterar contexto pela edição/SQL, autoria legada sem inferência, disponibilização explícita, disciplina/turma/escopo de outra escola, revogação após consulta e correção/impressão. Ensaio com cópia isolada antes de publicar o DC1 completo na homologação, preservando contagens e hashes dos dados anteriores.
