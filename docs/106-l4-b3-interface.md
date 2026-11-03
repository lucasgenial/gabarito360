# 106 — L4-B3: interface de atendimento

Continuação autorizada; planejamento revisto em 02/11/2026. Regra atual de publicação: implementar, testar e integrar sem deploy/migration; publicar somente após fechar todo L4 (A–E), substituindo a previsão anterior de publicação ao concluir B.

Telas: lista paginada de protocolos atribuídos; abertura exclusiva de Desenvolvedor com escolhas de cliente/espaço/responsável elegível; detalhe com relato, situação e verificações do ciclo; triagem, conferência manual, análise e conclusão administrativa. Reutiliza layout/componentes e identidade atuais. Sem consulta pública, anexos, exportação, eliminação, envio de mensagens ou gestão de acessos a partir do protocolo.

API acrescenta catálogo paginado mínimo de opções de abertura, exclusivo de Desenvolvedor e protegido pela flag. Não reutiliza gestão interna, que exclui Desenvolvedores e inclui Financeiro. Opções contêm somente identificação necessária de clientes ativos, espaços do cliente e responsáveis ativos elegíveis; a gravação revalida tudo. Permissões de navegação pedido.atender e pedido.abrir são emitidas apenas com a flag ativa, nos contextos internos corretos; não concedem dados pedagógicos nem substituem atribuição do protocolo.

Web usa PEDIDOS_TITULARES_INTERFACE_ATIVA=false, ContextoWeb por aba e autorização em cada requisição. Financeiro, legado sem contexto e escolar não entram. Todas as mutações têm CSRF e confirmação explícita. Validade informada em Brasília é convertida para UTC. Referências de evidência/resposta são exibidas como texto escapado, nunca buscadas ou abertas automaticamente. Rotulagem diferencia conferência humana de validação automática e conclusão administrativa de execução sobre dados.

Envios usam ticket aleatório vinculado à sessão e contexto, ação e protocolo. Cache temporário cifrado por 15 minutos, sem flash de relato/evidências. Primeira tentativa vincula payload e UUID idempotente; alteração do payload no mesmo ticket é recusada. Repetição após falha de rede usa somente o payload original cifrado, sem novo UUID. Sucesso remove payload do cache e preserva recibo mínimo até expirar. Erro definitivo orienta nova consulta/correção; expiração nunca dispara nova operação. Após transição, consultar estado atual em vez de tratar recibo antigo como estado atual. Lock por ticket evita envios simultâneos divergentes.

Testar permissões/menu/flag, opções filtradas, CSRF, escape, ausência de tokens/flash sensível, transporte do contexto, validade UTC, repetição após timeout, mudança de payload, troca de contexto/sessão e remoção do payload após sucesso. CI completo e conferência visual local com dados fictícios; não afirmar validação visual se computer-use impedir captura. Não usar dados escolares reais nas telas de teste.

## Validação de B3

API: 33 testes e 338 asserções aprovados no recorte de pedidos e acessos internos. Web: 15 testes e 165 asserções aprovados no recorte inicial de pedidos, gestão interna e contexto por aba; acrescentada cobertura da abertura com recibo mínimo. Conferência visual local no Chrome realizada nas telas de atendimento e abertura, com HTML renderizado pelo Blade e dados fictícios. Essa prévia valida a apresentação; os fluxos HTTP são cobertos pelos testes e não foram executados no servidor.

B3 não acrescenta migration. API e interface continuam desabilitadas por padrão. Próximo incremento é revisar o contrato de L4-C (exportação por escopo). Publicação somente ao fechar L4 completo, com preservação e validação operacional pelo doc. 99.
Teste final da interface de pedidos: 7 testes e 93 asserções aprovados, incluindo abertura e recibo sem exposição do relato.
