# A1-C — Secretário Escolar

## Revisão do planejamento — 01/11/2026

Pré-requisito concluído: PR #103 incorporado em `98b28e1b1625815a6a58e27caec0ac2e06b44ebe`. Esta etapa permanece sem deploy e sem migração no servidor.

1. Incluir `secretario_escolar` exclusivamente em vínculo de escola, com gestão de alunos e turmas e leitura dos dados administrativos da escola selecionada.
2. Manter a matriz legada de oito colunas; adicionar política independente, sem permissões extras, como já ocorre com o professor autônomo. O perfil global não concede acesso.
3. Permitir gestão desse vínculo por administrador da rede, diretor de núcleo e diretor escolar, dentro do respectivo escopo. Coordenador não administra esse papel administrativo; o secretário não administra usuários nem professores.
4. Separar a apresentação administrativa de escola/turma: nenhuma nota, média, prova, correção ou relatório no retorno para o secretário, inclusive nas rotas compartilhadas de leitura. Criar painel e detalhe de turma adequados a esse papel.
5. Validar isolamento entre escolas, cadastro e hierarquia, ausência de escalada por extras e revogação do vínculo; executar testes e ensaio local com dados fictícios antes de abrir o PR.

O código de acesso do responsável depende de P3/P4 (portal e vínculo responsável–aluno). Não será antecipado neste incremento; permanece pendência explícita dessas fases. Não haverá criação automática de contas reais nem alteração de acessos no servidor.

## Pendência anterior identificada

A permissão `aluno.importar` existe no catálogo, mas a tela atual só informa “Importação em breve”; não há rota ou serviço XLSX. A1-C prepara a permissão sem afirmar entrega do importador. Implementação e validação da importação permanecem no módulo de cadastro/importação de alunos.

## Validação local

API: 21 testes/227 verificações (Secretário Escolar, perfis por espaço e gestão de vínculos). Web: 5 testes/72 verificações (Secretário Escolar, área interna e professor autônomo). Casos cobrem CRUD administrativo, isolamento entre escolas, projeções sem notas/provas, suspensão, extras proibidos, cadastro contextual e convite sem alterar identidade global. O CI MariaDB também executa `SecretarioEscolarTest`.

Chrome/Computer Use: seleção explícita de Secretário Escolar, painel com Turmas/Alunos e criação de turma confirmada em SQLite local, com dados fictícios. Nenhuma ação no servidor. A conclusão da revisão automatizada e o ensaio de edição são conferidos no PR antes de solicitar merge.

Próxima fase após aprovação de merge: revisar T4-B (API/telas de clientes e referência contratual), conforme docs/67. Publicação e migração no servidor continuam dependendo de autorização separada.

## Incorporação

PR #104 incorporado em `20a57bf` após aprovação. CI completo aprovado e ensaio de edição confirmado no Chrome (“Turma atualizada.”). Sem deploy ou migração no servidor. Continuidade em [T4-B](71-t4-b-interface-clientes.md).
