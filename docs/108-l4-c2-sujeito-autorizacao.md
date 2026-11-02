# 108 — L4-C2: sujeito e autorização delimitada

Planejamento revisto em 02/11/2026, autorizado pelo usuário. Sem deploy até concluir L4 completo. Esta implementação começa pela vinculação explícita do aluno; a escolha de quais perfis institucionais podem conceder exportação foi apresentada ao responsável pelo produto e permanece pendente de resposta. Não implementar o poder de concessão antes dessa decisão.

## Vinculação do sujeito

Somente o responsável interno atual do protocolo pode vincular um aluno identificado por ID explícito. Flags de protocolos e de espaços devem estar ativas. Não há busca global nem associação por nome/CPF. Validar aluno, turma e espaço coerentes; espaço pessoal deve ter proprietário ativo e espaço escolar deve manter a cadeia institucional válida. O aluno deve pertencer exatamente ao espaço do protocolo, não apenas ao mesmo cliente.

Permitir vinculação/revisão apenas em recebida, em_verificacao ou aguardando_informacao. Pedidos em análise devem voltar pela triagem. Operação exige versão esperada, chave idempotente, justificativa e referência de evidência protegidas. Cada alteração incrementa a versão do sujeito e do protocolo, inicia novo ciclo de conferência e coloca o protocolo em em_verificacao. Não altera o cadastro do aluno. Mesmo uma reafirmação explícita com chave nova inicia nova conferência; repetição da mesma chave retorna recibo original, sem novo ciclo.

Persistir aluno_id com FK restritiva, sujeito_versao e sujeito_evidencia cifrada. Históricos de vinculação devem preservar aluno anterior/novo por versão em tabela própria com evidência cifrada, ator e data. Não copiar nomes, CPF ou notas para eventos genéricos. Consulta atribuída retorna somente o ID vinculado e sua versão, sem ampliar leitura escolar.

Concorrência segue a ordem atual: controle de acessos internos, protocolo, cliente/espaço e aluno/turma. Revalidar atribuição, versão, espaço e vínculo antes da escrita. Falha do histórico/evento/recibo reverte toda a transação. Novo ciclo torna conferências anteriores inelegíveis e futuras concessões deverão fixar sujeito_versao e ciclo para se invalidarem automaticamente.

## Concessão de exportação — dependência explícita

Decisão pendente: Administrador de Rede no próprio contexto e proprietário de espaço pessoal, ou incluir Diretor Escolar na própria escola. Suporte e Desenvolvedor não aprovam liberação apenas pelo papel interno. Nenhuma concessão, geração ou entrega é habilitada pela vinculação implementada neste incremento. Após resposta, detalhar e implementar concessão revogável com destinatário, campos, prazo, versões e auditoria conforme doc107.

## Testes

Cobrir espaço correto, espaço de outra instituição, aluno e turma incoerentes, aluno inexistente, responsável diferente, Financeiro, versão divergente, repetição e payload divergente, estado em análise/terminal, flags desligadas, evidência cifrada, novo ciclo e falha do histórico com rollback. Executar no SQLite local e no MariaDB do CI. Apenas fixtures; sem migração no servidor.

## Validação da vinculação

24 testes de pedidos e 214 asserções aprovados localmente. Inclui contexto pessoal e escolar, hierarquia suspensa, aluno de outro espaço do mesmo cliente, outro cliente, perfil/atribuição incorretos, flags, conflito de versão/payload, histórico cifrado, novo ciclo e rollback. Migration 000009 somente aditiva; reversão destrutiva bloqueada. Não aplicada no servidor. C2 permanece parcial até implementar a concessão após decisão dos aprovadores.
