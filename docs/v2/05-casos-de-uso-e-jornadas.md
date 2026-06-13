# Casos de Uso e Jornadas V2

## Atores

Administrador, diretor de núcleo, diretor escolar, vice-diretor, coordenador
pedagógico, professor, aplicador, aluno, suporte autorizado e sistema externo.

## Jornadas principais

### J1 - Entrada e conta

Usuário entra, recupera senha ou inicia criação de conta; o sistema valida a
instituição, estabelece contexto, carrega permissões e direciona ao dashboard
correto. O usuário gerencia perfil, preferências, notificações e sessões.

### J2 - Gestão da rede

Administrador e gestores cadastram núcleos/escolas, mantêm equipe, definem
cargos/perfis, suspendem acessos, consultam indicadores e registram eventos.

### J3 - Gestão acadêmica

Profissional autorizado cria turma, importa ou cadastra alunos, vincula
responsáveis, acompanha histórico e acessa ficha e resultados.

### J4 - Preparação da prova

Professor/coordenador cria prova, define padrões, questões e gabarito, salva
rascunho, valida, publica, gera material e vincula turmas.

### J5 - Aplicação e OMR

Aplicador abre a aplicação no Android, seleciona aluno, fotografa cartão,
recebe a interpretação OMR, revisa alertas, confirma e acompanha pendências.

### J6 - Correção e análise

Gestores e professores acompanham progresso, resolvem ambiguidades, acessam
resultado individual, relatórios e comparativos e exportam arquivos autorizados.

### J7 - Aluno

Aluno autenticado consulta suas provas, resultados, evolução, avisos e perfil,
sem acesso a dados de outros alunos.

### J8 - Configuração e LGPD

Usuário ajusta aparência/acessibilidade, integrações e preferências; solicita
relatório de dados, exportação, desativação ou exclusão controlada.

## Critérios comuns

- A tela equivalente do mockup é preservada.
- Toda ação tem estados de carregamento, vazio, erro, sucesso e acesso negado.
- Autorizações são verificadas no backend.
- Ações sensíveis são auditadas.
- Navegação funciona nos nove viewports oficiais.
