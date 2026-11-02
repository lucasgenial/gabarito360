# 79 — T5-A2: lotes e decisões persistidas

Revisão antes da implementação, após merge #112. Este pacote prepara a persistência da fundação T5; não executa cópia nem habilita rotas. `COPIAS_ATIVAS=false` permanece obrigatório até a release completa.

## Recorte e contrato

- Catálogo inicial: seleção explícita de provas, finalidade `preparacao_material`, política `t5-material-v1`. Gabarito e questões participam da impressão digital da fonte, sem guardar seu conteúdo no lote. A classificação de conteúdo e a lista positiva dos campos realmente copiáveis pertencem a T5-B: aprovação administrativa aqui não declara material anônimo ou revisado.
- A criação exige autorizador da origem (diretor, administrador ou proprietário pessoal). Solicitação limitada do professor escolar será integrada posteriormente; não se concede exportação a esse perfil.
- Cada lado aprova em uma chamada separada com seu contexto autenticado. Ator, vínculo, versão de acesso e impressão digital da seleção são resolvidos no servidor. Validade explícita no futuro, em UTC. Uma aprovação não substitui a outra; decisões não são sobrescritas.
- Revogação registra separadamente quem revogou, seu vínculo/versão e o instante, preservando o autorizador original. Gestor atualmente elegível daquele lado pode interromper o lote.
- Lote e seleção são imutáveis pelo serviço. Chave UUID pertence ao solicitante/vínculo e parâmetros normalizados; repetição equivalente retorna o mesmo lote, alteração retorna conflito. Mudança de fonte exige nova seleção e nova chave. IDs duplicados, ausentes ou fora da origem são recusados.
- Seleções limitadas a 100 provas por lote nesta fundação, para manter curta a transação. Não há promessa de execução em lote único: T5-B definirá unidades/bytes/tempo de processamento.
- Estados disponíveis: `aguardando_autorizacao` → `aguardando_auditoria`; qualquer decisão revogada, fonte alterada ou autorização vencida/inválida leva a `bloqueado` na revalidação. `aguardando_auditoria` não enfileira nem permite publicação. Estado bloqueado não é reaberto; exige novo lote e novas decisões. Estados de processamento serão adicionados com o executor.
- FKs compostas amarram decisões/itens/mapas ao lote e seus dois espaços. O mapa tem unicidade de origem por destino e de registro de destino, sem cascatas. Referências de entidades são históricas/polimórficas conforme docs/40, sem impedir exclusão da fonte. T5-B deverá verificar existência/propriedade ao gravar mapas, junto à publicação; este pacote não fornece gravador de mapas nem objetos copiados.
- Sem recibos de auditoria AU1 ainda: A3 precisa produzir eventos e intenção de execução atomicamente. Não confundir o histórico mínimo das decisões com auditoria entregue.

## Concorrência

O serviço exige transação própria, recusando chamada dentro de transação externa. Em MariaDB usa isolamento `SERIALIZABLE` somente para a próxima transação, mais lock do lote. Leituras de vínculo, permissões, fonte, hierarquia e cliente permanecem protegidas até o commit; isso inclui ausência de retirada de permissão (phantoms). Não reaproveitar fotografia criada fora dessa transação.

Revogação concorrente deve aguardar o commit ou ser observada pela operação; depois dela, revalidação bloqueia. Deadlock/timeout aborta integralmente e deve ser repetido pelo chamador com a mesma chave. Nenhuma falha de banco é convertida em aprovação. SQLite verifica comportamento funcional, não prova concorrência de produção. CI MariaDB verifica FKs, unicidades e disputa real em duas conexões.

## Saída e próximo passo

Testar idempotência, dois consentimentos distintos, validade/versão, fonte e gabarito alterados, retirada/revogação, isolamento de espaços, rollback, constraints e concorrência. Não executar migration no servidor.

Após revisão e merge autorizado: T5-A3 (outbox e contrato de auditoria de domínio). Depois, T5-B (prévia/classificação, armazenamento privado e publicação). T5 completo ainda não entregue.
