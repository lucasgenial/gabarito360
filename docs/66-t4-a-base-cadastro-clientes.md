# 66 — T4-A: base do cadastro de clientes

01/11/2026. PR #99 incorporado em `3b8aa5b`, com autorização para avançar ao T4 sem deploy.

Atualização: PR #100 aprovado e incorporado em `a161e87`, com CI completo aprovado (41354532563). A1 iniciado conforme planejamento revisto em docs/67; os limites abaixo descrevem a entrega T4-A original. Sem deploy.

## Dependência encontrada

T4 depende de A1, ainda pendente: os perfis Desenvolvedor, Suporte e Financeiro não existem no código atual. Perfis escolares não podem receber acesso global a contratantes. Por isso esta entrega prepara a persistência, sem rotas, telas ou concessões de acesso. T4 completo continua pendente.

## Entrega

- Migration aditiva e retomável acrescenta contato (nome, e-mail, telefone), referência contratual e eventos de cliente. Preserva clientes internos conciliados; não preenche dados nem altera modalidades existentes.
- Modelo `Cliente` cobre professor, escola, núcleo, rede, secretaria e governo; modalidades pagante, presenteado, teste e interno. Documento e contatos opcionais são cifrados com o mecanismo L3. Documento possui índice HMAC; dados protegidos não aparecem na serialização comum.
- `CadastroCliente` valida uma lista fechada de campos, define autoria pelo ator e situação inicial ativa. Não aceita situação, vínculo, espaço ou autor enviados como cadastro. Suspensão/encerramento permanecem no ciclo próprio de T6.
- Toda entrada exige ator existente/ativo e a ability `gerir-clientes`. **Esta entrega não define nem concede essa ability.** Sem a política futura de A1, o serviço nega acesso, inclusive ao administrador de rede. Os testes usam uma definição exclusiva do ambiente de teste para exercitar a persistência.
- Cadastro e evento ficam na mesma transação. Mudança de modalidade guarda ator, cliente, valores anterior/novo e instante. Não armazena documento, contatos, referência de contrato nem payload livre no histórico. A falha do evento desfaz a mudança. Eventos comerciais são específicos; não substituem AU1.
- A referência contratual é apenas um identificador opcional de evidência externa: não constitui contrato, assinatura, prazo de retenção ou ativação financeira. A modalidade pagante não cria cobrança. Documento compartilhado não une clientes automaticamente.

## Validação e limites

`CadastroClienteTest` cobre tipos/modalidades, cifra e leitura, serialização, remoção de campos opcionais, duplicidade documental sem união, negativa por ausência de política, ator desativado, validação, atomicidade do histórico e reexecução da migration. A suíte entra também no CI MariaDB.

Nenhuma conta interna foi criada ou promovida. Nenhum espaço, vínculo, assinatura ou cobrança é criado pelo serviço. Nenhuma migration foi executada no servidor. O acesso às telas do T4 ainda não pode ser ensaiado no Computer Use porque elas dependem de A1 e não fazem parte desta base.

## Sequência de conclusão

1. Revisar e aprovar T4-A.
2. Implementar A1 com contexto interno explícito, sem herdar poderes de um vínculo escolar e sem dados pedagógicos para Financeiro. Incluir a política comercial e testes de hierarquia/revogação.
3. Concluir T4-B: API e telas de listagem/cadastro/edição, consulta controlada de espaços e vínculos e referência contratual; validar pelo Computer Use.
4. Integrar contratos e auditoria geral nas fases F1/AU1. Qualquer ativação no servidor exige ensaio de release e aprovação separada.
