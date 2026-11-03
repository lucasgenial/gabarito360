# 103 — L4-A: contrato dos pedidos dos titulares

Execução autorizada em 02/11/2026, dando sequência ao doc. 102. L4-A entrega o contrato técnico e a máquina de estados testável; L4-B implementará persistência, autorização, registro e triagem. Não há endpoint, tela, migration ou operação sobre dados pessoais nesta entrega.

## Modelo a persistir em L4-B

| Campo/conjunto | Contrato |
|---|---|
| Referência | UUID aleatório gerado no servidor; identificador opaco não substitui autorização |
| Escopo | cliente_id e espaco_id obrigatórios, par validado no servidor; imutáveis depois de aberto. Pedido que abrange vários espaços gera protocolos relacionados, sem ampliar escopo de um protocolo existente |
| Tipo | acesso, correcao, anonimizacao, eliminacao, informacao; enum fechado. Tipo é intenção de atendimento, não comando de execução |
| Situação | EstadoPedidoTitular; abertura sempre recebida |
| Responsável | Acesso interno ativo e designação explícita para aquele protocolo; não aceitar perfil/nome do responsável como prova de autoridade |
| Sujeito/representação | Referência protegida ao sujeito e evidência mínima separada. Não associar aluno à identidade por coincidência de nome ou e-mail. Revalidar pertença ao espaço; cadastro mínimo não equivale a identidade confirmada |
| Datas | recebido_em, atualizado_em, concluido_em em UTC; datas operacionais do servidor. Prazo_atendimento e origem_regra exigem regra aplicável conferida, não prazo universal implícito |
| Verificações | Registro protegido de identidade/representação e autoridade institucional, com autor, data, versão e escopo. Mudança relevante invalida verificação anterior |
| Concorrência | versão incremental; atualização exige versão esperada e bloqueio transacional. Payload não define situação inicial nem flags de verificação |
| Idempotência | Chave vinculada a ator, ação e escopo; unicidade no banco. Repetição idêntica retorna o mesmo recibo; mesma chave com payload diferente recusa com 409 |
| Resultado | Código fechado; justificativa protegida separada, necessária para conclusão/cancelamento no serviço. Conclusão administrativa não significa que exportação ou eliminação foi executada |
| Histórico | Eventos append-only na mesma transação da alteração, com estado anterior/novo, ator e referência de justificativa; sem copiar conteúdo sensível para auditoria geral |

Anexos e upload não entram na primeira entrega. Identificadores/evidências pessoais usam proteção existente (DadoProtegido); não aparecem em URL, logs ou evento genérico. Definir limite de tamanho e campos permitidos no contrato de API antes de aceitar conteúdo livre. Histórico de protocolos tem política própria a definir: não herda os 180 dias da auditoria.

## Matriz de autorização prevista para L4-B

Nenhuma permissão é adicionada ao ContextoInterno nesta etapa. A matriz abaixo exige política específica, além da resolução atual do contexto.

| Ator | Registro/triagem de protocolo | Dados escolares/exportação |
|---|---|---|
| Desenvolvedor | Designação explícita por escopo, com justificativa; pode administrar atribuições, sem leitura automática de evidências por possuir papel global | Não concedido por esta permissão |
| Suporte | Somente protocolos atribuídos ao seu acesso interno válido; sem listagem global de sujeitos | Não concedido por esta permissão |
| Financeiro | Negado, inclusive filtros, contagens e busca de referências | Negado |
| Perfis escolares/identidade sem contexto | Sem rota L4 nesta primeira entrega; canal autenticado do titular será detalhado separadamente | Permissões existentes permanecem como estão |
| Anônimo | Negado; não haverá consulta pública por número de protocolo | Negado |

Designação de atendimento não comprova autoridade da instituição para liberar dados. A verificação institucional fica separada e deve estar vigente antes de análise/conclusão. Autorizações de leitura e de mutação devem resolver o contexto atual a cada operação. Perda de acesso interno, vínculo inválido ou mudança da versão de autorização revoga o uso, inclusive em jobs. Responsável não pode transferir protocolo para outro cliente/espaço.

## Estados e transições

| Origem | Destinos admitidos |
|---|---|
| recebida | em_verificacao, cancelada |
| em_verificacao | em_analise, aguardando_informacao, cancelada |
| em_analise | aguardando_informacao, concluida, cancelada |
| aguardando_informacao | em_verificacao, cancelada |
| concluida / cancelada | nenhum |

Entrada em análise e conclusão exigem identidade/representação e autoridade verificadas. Retorno de informação passa novamente pela verificação. A máquina recebe esses fatos de um serviço confiável, não de booleanos fornecidos pelo navegador; ela não consulta banco nem substitui a política de autorização.

Conclusão exige código atendido, parcialmente_atendido ou indeferido. Cancelamento exige cancelado_pelo_solicitante, duplicado ou inviavel_verificar. Cancelamento por impossibilidade de verificar não exige fingir identidade confirmada; continua exigindo ator autorizado, justificativa e histórico. Resultado é recusado em estados intermediários. Estados finais não são reabertos: eventual novo pedido recebe protocolo relacionado. Repetição de uma requisição é tratada pela idempotência antes da transição, sem criar novo evento.

## Matriz de validação

| Caso | Evidência nesta etapa / próxima etapa |
|---|---|
| Fluxo normal, pedido de informação e nova verificação | Teste unitário da máquina de estados |
| Pular verificação, concluir sem autoridade/identidade, reabrir terminal | Recusa unitária, inclusive perda de confirmação antes da conclusão |
| Resultado ausente, incompatível ou texto pessoal livre | Recusa unitária com mensagem genérica |
| Duas instituições e uma pessoa com dois contextos | L4-B: integração, sem existência/contagem/identidade da outra instituição |
| Financeiro, contexto revogado, atribuição vencida | L4-B: integração contra política e contexto reais; não declarar isolamento implementado neste PR |
| Mesma chave repetida, payload divergente, concorrência | L4-B: transações e constraints no MariaDB |
| Falha na gravação do evento obrigatório | L4-B: rollback do protocolo e histórico; auditoria geral assíncrona não é prova suficiente de gravação |
| Exportação, correção, anonimização ou eliminação | Ausentes; fases próprias, sem teste sobre os gabaritos existentes |

## Publicação e saída

Não há comportamento novo acessível pelo usuário nesta microfase. A classe será consumida pelo serviço transacional de L4-B; publicar apenas esse contrato não disponibilizaria uma tela para validação visual. A próxima entrega deve incluir a política de escopo, armazenamento/histórico e os testes de integração antes de expor registro/triagem. Nenhum deploy ou migration é necessário para ativar L4-A, pois não há funcionalidade ativável isoladamente.
