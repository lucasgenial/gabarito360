# 63 — T3: perfis por tipo de espaço

01/11/2026. Origem: T3 do plano mestre, T1 aprovado (docs/40) e autorização explícita para incorporar PR #95 e seguir com T3, sem deploy. Base `df1192c`.

O perfil `professor_autonomo` pertence exclusivamente ao espaço pessoal cujo proprietário é a própria conta. Não deriva poderes de vínculos escolares nem concede acesso institucional pelo cliente pagador. Escola conserva professor, coordenador, diretor escolar, aplicador e aluno; núcleo conserva diretor de núcleo; rede conserva administrador. Não existe professor de núcleo com acesso pedagógico implícito.

O autônomo gere turmas e alunos próprios (incluindo mudança de turma no mesmo espaço), provas, gabaritos, cartões, correção e relatórios de prova/turma. O cadastro mínimo contém nome, identificação simples (`matricula`), turma, avatar e estado ativo. CPF, nascimento, gênero, responsável e fotos não são aceitos. Não recebe gestão de equipe, escolas, núcleo, rede ou portal dos pais. O portal dos pais ainda não existe; sua implementação futura deve exigir espaço escolar.

Implementação em etapas revisáveis:

1. **T3-A — contrato de perfis e limites (em revisão):** catálogo central de compatibilidade perfil/espaço e permissões pessoais explícitas; validação institucional compartilhada em contexto e convites, bloqueio de uso como perfil global e testes. O acesso pessoal permanece bloqueado até a integração completa. O catálogo pode ser sincronizado sem habilitar esse acesso; a sincronização no servidor não foi executada.
2. **T3-B — dados pessoais e fluxos:** preparar expansão do enum de perfil dos vínculos e DDL de turmas/provas sem escola, integridade e auditoria equivalentes, escritas e consultas por espaço pessoal, formulário mínimo, parâmetros e relatórios próprios. Exercitar movimento de aluno sem corromper histórico de notas/cartões.
3. **T3-C — integração e habilitação:** seleção de vínculo pessoal, menu por tipo, dashboard, PDFs/filas e testes MariaDB/navegador. Só então liberar o contexto pessoal no resolvedor. Provisionamento comercial de cliente/espaço permanece T4.

Essa divisão mantém cada PR utilizável sem habilitar permissões sobre consumidores que ainda dependem de escola. T3 só estará concluído após as três etapas; catálogo preparado não significa acesso pessoal disponível. Não há alteração de configuração, criação de cliente real, migração ou publicação no servidor.

## Evidências e critérios das próximas partes

`PerfisEspacoTest` exercita a matriz de tipos, titularidade pessoal, poderes separados de uma mesma conta, recusa de professor em núcleo, opções institucionais de convite e bloqueio pessoal mesmo após preencher cache legado. A suíte participa também do CI MariaDB. `PerfisTest` permanece sem alterações.

O diagnóstico encontrou dependência de escola em `Espacos`, `Escopo`, `ConsultasPedagogicas`, criação de turmas/provas e formulários web. A FK composta de T2 usa MATCH SIMPLE: permitir escola nula exige validar também que o dono é pessoal, sem aceitar escola nula em espaço escolar. A movimentação de aluno precisa preservar notas/cartões anteriores; não basta alterar `turma_id`, pois consultas atuais relacionam notas à turma atual. T3-B deve resolver e testar esses dois pontos antes de T3-C.

O perfil autônomo não herda extras de diretor. Importação escolar e reabertura de correção não são concedidas automaticamente; são preservados os poderes pedagógicos padrão do professor mais a gestão mínima de turmas/alunos prevista em T3. A política do portal é pré-condição de tipo, não autorização suficiente para consultar dados.
