# T4-B — interface do cadastro de clientes

## Revisão antes da implementação

PR #104 incorporado em `20a57bf`, após aprovação do usuário. T4-A e A1 fornecem persistência comercial, atribuição interna e seleção por aba. A1-C foi validado com quatro jobs do CI e ensaio de criação/edição no Chrome. Sem deploy ou migração no servidor.

1. Expor API paginada de clientes, cadastro e edição, somente no contexto interno de Desenvolvedor, Suporte ou Financeiro. Identidade e vínculos escolares/pessoais não autorizam essas operações.
2. Usar `CadastroCliente`, preservando validação fechada, cifra, autoria e evento transacional. Listagem sem documento/contatos; detalhe autorizado apresenta os campos necessários à edição comercial.
3. Consultar espaços do cliente com paginação e contagens de vínculos por situação. Não retornar identidade de usuários, CPF, e-mail, alunos, provas ou notas. Não criar, transferir ou alterar espaços/vínculos por essa interface.
4. Criar menu, lista, formulário e detalhe na web. Respeitar contexto por aba e revalidar acesso. Referência contratual é texto, não assinatura nem cobrança; situação do cliente continua sob T6.
5. Testar tokens reais, revogação, negativa institucional, lista de campos permitidos, cifra, isolamento da consulta por cliente, paginação e regressões web; concluir CI SQLite/MariaDB e ensaio fictício por Computer Use antes da aprovação de merge.

Sem nova migration nesta fase. Nenhum cliente ou acesso real será criado. Contratos completos são F1, auditoria geral AU1 e atendimento com dados pedagógicos AU3. A habilitação interna no servidor permanece desligada.

## Validação

API: 17 testes/231 verificações de interface, persistência comercial e seleção interna. Web: 11 testes/153 verificações de clientes e regressões dos contextos interno/escolar. Teste da interface incluído no CI MariaDB. Ensaio Chrome e CI completo são registrados no PR antes de solicitar merge.

## Próxima dependência

T5 depende de T3, B4 e AU1; T3/B4 estão concluídos, AU1 ainda pendente. Após merge de T4-B, revisar e preparar AU1 (auditoria geral) antes de implementar cópias entre espaços. Isso não significa conclusão da Fase 3: T5 e T6 continuam pendentes.
