# 105 — L4-B2: verificação registrada e conclusão administrativa

Continuação autorizada em 02/11/2026, após L4-B1 integrado (#140). Planejamento revisto: registrar conferência humana, não autenticar documentos automaticamente nem tratar uma flag do navegador como prova. Mantém a atribuição, contexto e escopo do B1, flag desligada e ausência de operações sobre dados escolares.

## Verificações

POST /api/v1/pedidos-titulares/{uuid}/verificacoes exige responsável atual, protocolo em_verificacao, versão esperada, chave UUID, tipo (identidade ou autoridade), método compatível, referência protegida da evidência (5–500), motivo (5–500) e validade futura. Métodos de identidade: conferencia_documental ou canal_autenticado; de autoridade: confirmacao_institucional ou instrumento_representacao. A conferência de identidade deve abranger a representação quando houver; a de autoridade deve abranger a instituição e o espaço daquele protocolo. Não faz upload, consulta externa, envio de mensagem nem busca automática por CPF.

O responsável atesta uma conferência manual já realizada. A referência localiza a evidência no procedimento da instituição; não carregar documento nem copiar informação desnecessária nesse campo. O servidor registra autor, acesso e versão, protocolo e ciclo de verificação, método, data e validade. Não aceita ator, ciclo, escopo, data de registro ou booleanos identidade_verificada/autoridade_verificada do cliente.

Há uma conferência de cada tipo por ciclo; tentativa diferente no mesmo tipo/ciclo é conflito, sem sobrescrever evidência. Registros são preservados. Solicitar informação adicional incrementa o ciclo e invalida todas as conferências anteriores para decisões futuras. Para corrigir ou renovar evidência: passar por aguardando_informacao e em_verificacao; não editar o registro anterior.

## Análise e conclusão

POST /api/v1/pedidos-titulares/{uuid}/decisao aceita somente em_analise ou concluida, versão, chave e motivo. Usa a máquina L4-A e verifica no banco as duas conferências do ciclo corrente, ainda válidas e vinculadas à mesma atribuição/versão. Revalida também acesso interno e cliente/espaço. Evidência vencida bloqueia a decisão; a triagem continua permitindo solicitar informações/renovação.

Conclusão requer código atendido, parcialmente_atendido ou indeferido e referência protegida da resposta administrativa já produzida (5–500). Esses campos são proibidos na entrada em análise. Registrar conclusão não envia resposta nem executa exportação, correção ou eliminação. O registro descreve o resultado informado pelo responsável; não é recibo técnico de operação sobre dados, que pertence às fases próprias. Não reabre protocolo terminal.

Idempotência retorna o recibo original depois de revalidar acesso/atribuição, sem prolongar validade, repetir evento ou reexecutar decisão. Mesmo payload com chave nova e versão obsoleta retorna 409. Toda gravação de verificação, estado, resposta, histórico e recibo é transacional. Falha de histórico obrigatório desfaz a operação. Referências e motivos cifrados; recibos contêm apenas referência do protocolo, estado e versão. Consulta detalhada, já auditada no B1, mostra somente as verificações do ciclo corrente e a referência da resposta; não expõe esses campos em listagem.

## Persistência e testes

Migration aditiva: ciclo_verificacao (default 1) e resposta_referencia cifrada/nullable em pedidos_titulares; tabela pedido_titular_verificacoes com FK, unicidade protocolo/ciclo/tipo e vínculo ao ator/acesso. Não preenche verificações para protocolos existentes. Sem down destrutivo.

Cobrir fluxo completo, um tipo faltando, flags fabricadas, métodos trocados, validade vencida, idempotência, resultado incompatível, versões obsoletas, outro responsável/Financeiro/identidade simples, ciclo novo, perda de acesso, conteúdo cifrado e rollback de histórico. Executar no CI MariaDB juntamente com regressões B1/A1. Não declarar inspeção real de documentos ou teste visual a partir de testes automatizados.

Entrega permanece desligada no servidor. Próxima fase B3: telas de atendimento, com indicação explícita de conferência manual, ciclo/validade e conclusão administrativa; só depois preparar publicação e validação visual pelo fluxo do doc. 99.
