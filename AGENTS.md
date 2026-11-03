# Orientações do projeto

## UI, UX e Product Design

Regra solicitada pelo responsável em03/11/2026: toda alteração que afete interface, experiência, navegação, formulários ou textos de interação exige aprovação explícita do usuário **ou de um agente especializado em UI/UX e Product Design** antes de ser considerada concluída ou integrada.

- Utilizar as skills disponíveis de Product Design, design critique e frontend-design, quando pertinentes. Consultar as instruções efetivas de cada skill; a ausência de uma skill não dispensa a revisão.
- Antes de implementar ou refazer uma interface, revisar o objetivo do usuário, o fluxo e os padrões já existentes no produto. Preservar identidade visual, componentes, acessibilidade e linguagem do Gabarito360.
- O agente implementador não aprova a própria interface. Delegar a revisão a um agente especializado, autorizado por esta regra, ou apresentar uma proposta concreta ao usuário. Não pedir que o usuário autorize cada subdivisão técnica.
- A revisão final exige evidência visual atual no navegador, com desktop e celular, e conferência das ações principais e estados de erro. Screenshots sozinhas não comprovam acessibilidade completa. Registrar o parecer, as pendências resolvidas e a aprovação; alterações visuais posteriores devem ser revistas.
- Se a revisão reprovar, corrigir e reenviar. Não apresentar testes de código como aprovação de design nem reutilizar uma aprovação de outra tela ou versão. A aprovação da conferência CI1-D não aprova o novo formulário CI1-E.
- A aprovação do especialista é de qualidade de design. Não substitui autorizações do usuário para publicação, acesso sensível ou outras ações que dependam delas.

## Continuidade e publicação

Revisar o planejamento antes de implementar cada etapa e apresentar a tabela atualizada ao finalizar. Prosseguir autonomamente pelas subdivisões internas autorizadas. Usar terminal para Git/GitHub e preferir o Chrome do usuário para testes visuais do aplicativo.

Decisão atualizada pelo responsável em03/11/2026: toda entrega deve ser publicada primeiro na homologação isolada `homologa.gabarito360.com.br`, com banco, arquivos, filas e credenciais de serviços próprios. Posteriormente o responsável autorizou expressamente copiar os usuários e dados atuais de produção para homologação, mantendo suas senhas de acesso. A cópia é pontual, protegida e independente: não compartilhar o banco operacional, não enviar e-mails ou pagamentos reais, não reaproveitar sessões, tokens de acesso ou trabalhos pendentes. Preservar a origem e validar a cópia antes da troca; manter a proteção adicional da homologação. Dados criptografados exigem material de leitura protegido, sem copiar indiscriminadamente o `.env` de produção.

O ambiente atual `gabarito360.com.br` é produção. Sua publicação depende de decisão explícita do responsável no momento combinado, mesmo após CI, revisão do especialista e validação na homologação. Concluir L4 ou obter parecer de design não autoriza promoção automática. Preservar os gabaritos e os demais dados existentes. Não incluir arquivos ou alterações preexistentes de outras tarefas nos commits desta entrega.
