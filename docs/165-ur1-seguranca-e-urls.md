# UR1 — URLs, contexto e acesso autenticado a arquivos

Planejamento revisto em 05/11/2026, antes das alterações. O responsável priorizou a revisão de segurança durante a conferência da turma 3A. TU1 permanece na sequência, antes de retomar EI1. Produção continua excluída da publicação automática.

## Diagnóstico e contrato

- O Bearer da API já permanece no servidor. `_ctx` é um handle de contexto ligado à sessão, cifrado no cache, não o token da API nem o cookie de sessão. Sua propagação por URLs será substituída.
- Navegação SSR precisa distinguir atuações simultâneas. URLs passam a identificar o recurso de negócio, por exemplo `/acessos/vinculo-5/turmas/14`. O número não concede acesso: o servidor resolve somente a referência selecionada pela própria sessão e revalida identidade, vínculo e permissões.
- Não usar cookie global de perfil, mini-SPA, troca cosmética da barra de endereço ou POST indiscriminado para consultas. Filtros e paginação continuam podendo usar GET. Criação, atualização e comandos usam corpo de requisição e CSRF nas rotas web.
- Links antigos de imagens assinadas funcionam como capacidades temporárias. A rota antiga será desativada inclusive para assinaturas ainda válidas. Novos arquivos exigem autenticação Sanctum e escopo atual na API. O navegador recebe uma rota web autenticada e o servidor envia o Bearer apenas no cabeçalho da chamada à API.
- O proxy de imagens recebe somente ID e tipo permitido. Não recebe um URL arbitrário, não segue um destino indicado pelo navegador e não repassa HTML como imagem. Respostas são privadas e sem armazenamento em cache.
- E-mails de recuperação, convite e confirmação passam a levar um link fixo e o código completo em texto separado. Não reduzir a entropia existente. GET apresenta formulário; a ação exige POST explícito com CSRF. Código, senha e token não podem ser extraídos da query para executar a operação.
- Confirmação de troca de e-mail precisa de consumo atômico para uso único. Preservar expiração e regras de reenvio existentes, sem migrations ou troca de senhas por efeito de abertura de página.
- Código público de validação de documento não é credencial de autenticação. Não bloquear esse recurso por compartilhar o nome de campo `codigo`.

## Execução

| Parte | Entrega | Critério de saída |
|---|---|---|
| UR1-A | Contexto não secreto no path e transporte de dados | Duas abas/perfis independentes; nenhum handle em links, imagens, formulários GET ou Location; referências inválidas e conflitos recusados |
| UR1-B | E-mails e confirmação explícita | Links fixos, códigos no corpo, GET inerte, CSRF, expiração/uso único, mensagens sem enumeração |
| UR1-C | Fotos dos cartões e proxy autenticado | Visitante recusado; links antigos desativados; autorização e revogação revalidadas; nenhuma assinatura ou capacidade enviada no URL ao navegador |
| UR1-D | Integração e regressão | Rotas e parâmetros posicionais preservados; cobertura funcional migrada ao contrato novo, sem enfraquecer middleware ou assertions |
| UR1-E | Revisão independente e homologação | CI completa, conferência UI dos formulários, backup, preservação de dados, smoke e monitoramento |

As subdivisões não exigem autorização de continuidade. Uma revisão do implementador não aprova seu próprio módulo: os agentes fazem revisão cruzada, além da integração pelo responsável pela tarefa. A consulta de Product Design precedeu os formulários de código e a composição futura da turma; o aceite visual final continua necessário.

## Compatibilidade e limites

GET antigo com `_ctx` só migra uma referência válida da própria sessão para o endereço canônico. Uma ação antiga em regime de múltiplos vínculos não é reexecutada ou convertida silenciosamente por redirect. O modo anterior sem `auth_vinculos` continua autenticado e dependente da autorização da API; não permite selecionar um vínculo negociado ou contornar o corte obrigatório da API.

Não apagar imagens, respostas, notas ou documentos. Não ler ou expor valores de cookies, tokens ou códigos em evidências. Verificação inicial da homologação confirmou cookie de sessão Secure/HttpOnly/SameSite=Lax; o cookie XSRF é Secure/SameSite=Lax e não HttpOnly por finalidade. Isso não é certificação integral da infraestrutura nem pentest.

A defesa de infraestrutura também recusa `/storage` e seus descendentes antes do PHP, mesmo se alguém criar um `storage:link` no futuro. A inspeção inicial da homologação não encontrou esse link nem alias de arquivos. O formato novo de access log registra método e caminho sem query, Referer, cookies ou Authorization. Logs anteriores são preservados; a mudança não afirma remover registros antigos, logs de terceiros ou histórico do navegador.

E-mails emitidos antes da mudança com código na URL precisam ser reenviados. Códigos novos continuam completos, com a mesma entropia e expiração; são enviados ao servidor no corpo do POST. A publicação não invalida sessões ou altera senhas dos usuários por conta própria.

Não alegar incidente ou acesso indevido sem evidência. Há mecanismos comprovados a corrigir, mas não foi demonstrado vazamento do Bearer, leitura entre usuários por conhecer `_ctx` ou comprometimento de dados. A eventual publicação em produção precisa de autorização própria após resultado concreto e revisado.

## Fontes primárias

- Laravel 13, CSRF: https://laravel.com/docs/13.x/csrf
- OWASP, REST Security: https://cheatsheetseries.owasp.org/cheatsheets/REST_Security_Cheat_Sheet.html
- OWASP, Forgot Password: https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html
- MDN, métodos HTTP seguros: https://developer.mozilla.org/en-US/docs/Glossary/Safe/HTTP

Pareceres e recibos de teste/revisão/publicação são registrados fora do Git, sem dados pessoais ou credenciais. Este documento planeja a entrega e não declara integração, aprovação final ou deploy antecipadamente.
