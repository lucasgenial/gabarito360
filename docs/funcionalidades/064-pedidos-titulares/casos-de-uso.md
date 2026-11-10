# 064 — Pedidos dos titulares (L4) — Casos de uso

**Natureza:** existente documentada a partir do código (UC-064-01 a 08); propostos (UC-064-09 a 11)
**Perfis envolvidos:** Desenvolvedor e Suporte (contexto interno); Administrador da Rede e professor autônomo (contexto de vínculo, só concessão de exportação). Financeiro, perfis escolares e identidades sem contexto não acessam. Ver docs/03 (`docs/03-perfis-e-permissoes.md`), seções L4-B1, L4-B2 e concessão de exportação.
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

Regras comuns aos UC-064-01 a 06 (`App\Services\PedidosTitulares`):
- Exigem `PEDIDOS_TITULARES_ATIVOS`, token interno habilitado, contexto interno com perfil `desenvolvedor` ou `suporte` e conta sem troca de senha pendente; caso contrário, 403.
- Cliente e espaço do protocolo devem estar ativos; caso contrário, 404.
- Campos fora da lista recebem "Campos não permitidos."; textos são aparados antes da validação.
- Toda escrita exige `chave` UUID e `motivo` (5 a 500 caracteres); repetição com a mesma chave devolve o recibo anterior; mesma chave com outros dados devolve 409 "Chave já utilizada para outro pedido.".
- Alterações exigem a `versao` atual do protocolo; divergência devolve 409 "Protocolo alterado. Atualize a consulta.".
- Leitura e alteração só para o responsável atribuído, com a mesma versão de autorização e atribuição vigente. Ser Desenvolvedor ou criador não concede leitura.
- Na web, as telas exigem `PEDIDOS_TITULARES_INTERFACE_ATIVA` (senão 404) e as permissões `pedido.abrir` (abertura, só Desenvolvedor) ou `pedido.atender`.

---

## UC-064-01 — Abrir protocolo de pedido de titular

**Ator:** Desenvolvedor
**Permissão:** `pedido.abrir` (web); perfil interno `desenvolvedor` (API)
**Escopo:** cliente e espaço ativos escolhidos
**Pré-condições:** pedido recebido por canal externo; responsável interno (Desenvolvedor ou Suporte) ativo e vigente.
**Pós-condições:** protocolo `recebida`, versão 1, atribuído ao responsável; evento "abertura"; recibo com referência.
**Tela/rota:** web `GET /pedidos-titulares/novo`, `POST /pedidos-titulares`; API `GET /v1/pedidos-titulares/opcoes`, `POST /v1/pedidos-titulares`.

**Fluxo principal:**
1. O Desenvolvedor abre "Novo pedido"; o sistema lista clientes ativos, espaços do cliente e responsáveis internos, paginados de 20 em 20.
2. Informa cliente, espaço, responsável, tipo (`acesso`, `correcao`, `anonimizacao`, `eliminacao`, `informacao`), identificação declarada (até 200), relato (até 2000), motivo e, opcionalmente, fim da atribuição.
3. A API valida, cifra identificação e relato e grava o protocolo.
4. A web mostra o recibo com a referência.

**Fluxos alternativos e de erro:**
- 1a. Suporte tenta abrir: 403.
- 3a. Fim da atribuição no passado: erro de validação.
- 3b. Cliente, espaço ou responsável inativo: 404.

**Regras de negócio:** RL3; docs/104.

**Critérios de aceite:**
- [x] Só Desenvolvedor abre; protocolo nasce `recebida`.
- [x] Repetição com a mesma chave não duplica.

**Situação:** Implementado (homologação) — evidência: `PedidosTitulares::abrir`, PR #140, #142.

---

## UC-064-02 — Consultar protocolos atribuídos

**Ator:** Desenvolvedor ou Suporte responsável
**Permissão:** `pedido.atender`
**Escopo:** protocolos atribuídos ao próprio acesso interno vigente
**Pré-condições:** acesso interno ativo.
**Pós-condições:** a consulta do detalhe gera evento "consulta".
**Tela/rota:** web `GET /pedidos-titulares`, `GET /pedidos-titulares/{id}`; API `GET /v1/pedidos-titulares`, `GET /v1/pedidos-titulares/{id}`.

**Fluxo principal:**
1. O responsável abre a lista (20 por página, mais recentes primeiro), com cliente, espaço, tipo, situação e versão.
2. Abre um protocolo e vê identificação, relato, resultado, ciclo de verificação, sujeito vinculado e conferências do ciclo com validade.

**Fluxos alternativos e de erro:**
- 1a. Sem filtros livres nem busca por identidade nesta entrega.
- 2a. Protocolo de outro responsável, atribuição vencida ou versão de autorização diferente: 404.

**Regras de negócio:** docs/104; RL10.

**Critérios de aceite:**
- [x] Outro Desenvolvedor não lê protocolo não atribuído.
- [x] Consulta do detalhe é registrada.

**Situação:** Implementado (homologação) — evidência: `PedidosTitulares::listar`, `consultar`.

---

## UC-064-03 — Triar protocolo

**Ator:** responsável atribuído
**Permissão:** `pedido.atender`
**Escopo:** protocolo atribuído
**Pré-condições:** protocolo não terminal.
**Pós-condições:** nova situação, versão incrementada, evento "triagem".
**Tela/rota:** web `POST /pedidos-titulares/{id}/triagem`; API `POST /v1/pedidos-titulares/{id}/triagem`.

**Fluxo principal:**
1. O responsável escolhe `em_verificacao`, `aguardando_informacao` ou `cancelada` e informa o motivo.
2. A API aplica a transição permitida pela máquina de estados.

**Fluxos alternativos e de erro:**
- 1a. `cancelada` exige resultado `cancelado_pelo_solicitante`, `duplicado` ou `inviavel_verificar`.
- 2a. Transição fora do permitido: "Transição de protocolo indisponível.".
- 2b. Ida para `aguardando_informacao` abre novo ciclo de verificação.

**Regras de negócio:** `EstadoPedidoTitular` (recebida → em_verificacao/cancelada; em_verificacao → em_analise/aguardando_informacao/cancelada; em_analise → aguardando_informacao/concluida/cancelada; aguardando_informacao → em_verificacao/cancelada).

**Critérios de aceite:**
- [x] Estados terminais não mudam.

**Situação:** Implementado (homologação) — evidência: PR #139, #140.

---

## UC-064-04 — Registrar conferência de identidade ou de autoridade

**Ator:** responsável atribuído
**Permissão:** `pedido.atender`
**Escopo:** protocolo atribuído em `em_verificacao`
**Pré-condições:** nenhuma conferência do mesmo tipo no ciclo atual.
**Pós-condições:** conferência gravada com método, evidência cifrada e validade; versão incrementada.
**Tela/rota:** web `POST /pedidos-titulares/{id}/verificacoes`; API `POST /v1/pedidos-titulares/{id}/verificacoes`.

**Fluxo principal:**
1. O responsável informa tipo (`identidade` ou `autoridade`), método, referência da evidência (5 a 500), validade futura e motivo.
2. A API grava a conferência no ciclo atual.

**Fluxos alternativos e de erro:**
- 1a. Método incompatível (identidade aceita `conferencia_documental` ou `canal_autenticado`; autoridade aceita `confirmacao_institucional` ou `instrumento_representacao`): "Método incompatível com a conferência.".
- 2a. Protocolo fora de `em_verificacao`: "Protocolo fora da etapa de verificação.".
- 2b. Conferência repetida no ciclo: 409 "Conferência já registrada neste ciclo.".

**Regras de negócio:** docs/105. O registro atesta conferência humana, não autentica documentos.

**Critérios de aceite:**
- [x] Validade no passado é recusada.

**Situação:** Implementado (homologação) — evidência: PR #141.

---

## UC-064-05 — Decidir protocolo (análise e conclusão)

**Ator:** responsável atribuído
**Permissão:** `pedido.atender`
**Escopo:** protocolo atribuído
**Pré-condições:** conferências de identidade e autoridade vigentes no ciclo.
**Pós-condições:** protocolo `em_analise` ou `concluida` com resultado e referência de resposta cifrada.
**Tela/rota:** web `POST /pedidos-titulares/{id}/decisao`; API `POST /v1/pedidos-titulares/{id}/decisao`.

**Fluxo principal:**
1. O responsável move para `em_analise`.
2. Ao concluir, informa resultado (`atendido`, `parcialmente_atendido`, `indeferido`) e referência da resposta.

**Fluxos alternativos e de erro:**
- 1a. Conferências ausentes ou vencidas: "Transição indisponível ou conferências pendentes.".
- 2a. Resultado informado sem conclusão: erro de validação.

**Regras de negócio:** docs/105; RL3.

**Critérios de aceite:**
- [x] Não há conclusão sem as duas conferências vigentes.

**Situação:** Implementado (homologação). ⚠ A conclusão é administrativa: a correção, anonimização ou eliminação dos dados não é executada pelo sistema; resolve: subfases 4.10 e 4.11.

---

## UC-064-06 — Vincular o aluno ao protocolo (sujeito)

**Ator:** responsável atribuído
**Permissão:** `pedido.atender`; web exige também `PEDIDOS_EXPORTACOES_INTERFACE_ATIVA`
**Escopo:** aluno do mesmo espaço do protocolo (escola ou espaço pessoal)
**Pré-condições:** espaços ativos (`Espacos::ativo()`); protocolo em `recebida`, `em_verificacao` ou `aguardando_informacao`.
**Pós-condições:** sujeito versionado; protocolo volta a `em_verificacao` com novo ciclo.
**Tela/rota:** web `POST /pedidos-titulares/{id}/sujeito`; API `POST /v1/pedidos-titulares/{id}/sujeito`.

**Fluxo principal:**
1. O responsável informa o ID do aluno, a referência da evidência e o motivo.
2. A API confere aluno, turma e espaço e grava a nova versão do sujeito.

**Fluxos alternativos e de erro:**
- 1a. Etapa inválida: 422 "Vinculação indisponível nesta etapa.".
- 2a. Aluno fora do espaço ou turma incompatível: 404.
- 2b. Espaço pessoal com `espacos.pessoal` desligado: 403.

**Regras de negócio:** docs/108.

**Critérios de aceite:**
- [x] Vincular invalida conferências anteriores (novo ciclo).

**Situação:** Implementado (homologação) — evidência: PR #144.

---

## UC-064-07 — Conceder ou revogar a exportação

**Ator:** Administrador da Rede ou professor autônomo proprietário do espaço pessoal
**Permissão:** `pedido.exportacao.autorizar` (padrão só desses dois perfis; retirada no vínculo é respeitada)
**Escopo:** escola da própria rede (admin_rede) ou o próprio espaço pessoal (professor autônomo)
**Pré-condições:** flags de autorização ligadas; vínculo explícito e vigente; protocolo do tipo `acesso` em `em_analise`, com sujeito vinculado e as duas conferências vigentes.
**Pós-condições:** concessão para o responsável atribuído, formato `cadastro_minimo_v1`, campos nome e/ou matrícula, com validade; protocolo com versão incrementada.
**Tela/rota:** web `GET /exportacoes-titulares?referencia=...`, `POST /exportacoes-titulares/conceder|revogar`; API `GET|POST /v1/autorizacoes-exportacao/pedidos/{id}`, `POST /v1/autorizacoes-exportacao/{id}/revogar`.

**Fluxo principal:**
1. O aprovador informa a referência do protocolo e vê a prévia (sujeito, destinatário, limite de validade, campos permitidos).
2. Escolhe campos, validade, motivo e evidência e confirma.
3. A API grava a concessão.
4. Para revogar, informa a versão e o motivo.

**Fluxos alternativos e de erro:**
- 2a. Validade além do limite (menor entre conferências, vínculo do aprovador, acesso do responsável e atribuição): 422 "Prazo fora da validade autorizável.".
- 3a. Concessão vigente para o mesmo sujeito e ciclo: 409 "Revogue a concessão anterior antes de substituir.".
- 3b. Protocolo não elegível ou conferências pendentes: 409.
- 3c. Suporte, Desenvolvedor, Financeiro ou Diretor Escolar: 403.

**Regras de negócio:** docs/109; docs/03 (`docs/03-perfis-e-permissoes.md`).

**Critérios de aceite:**
- [x] Só admin_rede e professor_autonomo concedem.
- [x] Revogação impede geração e entrega.

**Situação:** Implementado (homologação) — evidência: `AutorizacoesExportacaoTitular`, PR #145, #148.

---

## UC-064-08 — Gerar e baixar a exportação do titular

**Ator:** responsável interno destinatário da concessão
**Permissão:** `pedido.atender`; flags de geração e entrega
**Escopo:** concessão destinada ao próprio acesso interno
**Pré-condições:** concessão vigente, não revogada, com sujeito, ciclo e assinatura do aluno inalterados.
**Pós-condições:** artefato cifrado `disponivel` até a validade; download auditado.
**Tela/rota:** web `POST /exportacoes-titulares/gerar`, `GET|POST /exportacoes-titulares/{id}/arquivo`; API `POST /v1/pedidos-titulares/exportacoes/{id}`, `GET /v1/pedidos-titulares/exportacoes/{id}`, `GET /v1/pedidos-titulares/exportacoes/{id}/arquivo`.

**Fluxo principal:**
1. O responsável gera o artefato a partir da concessão (uma geração por concessão).
2. Consulta situação, campos e expiração.
3. Baixa o JSON (até 16 KiB) com manifesto e hash conferidos.

**Fluxos alternativos e de erro:**
- 1a. Concessão já usada: 409 "Autorização já utilizada para geração.".
- 1b. Conteúdo acima do limite: 422 "Conteúdo excede o limite do formato.".
- 3a. Integridade divergente ou prazo vencido: 409 "Exportação indisponível para entrega." ou 403.

**Regras de negócio:** docs/110, 111; RL4.

**Critérios de aceite:**
- [x] Só campos da lista positiva entram no arquivo.
- [x] Arquivo vencido ou revogado não é entregue.

**Situação:** Parcial (homologação). ⚠ RL4 pede formato estruturado dos dados do titular; o formato atual cobre só nome e matrícula. Ampliação exige novo formato e armazenamento (sem subfase no tronco; registrar como candidata se priorizada).

---

## UC-064-09 — Corrigir dados do titular preservando o histórico

**Ator:** responsável interno atribuído, com aprovação institucional a definir
**Permissão:** a definir no contrato de L4-D
**Escopo:** sujeito vinculado ao protocolo `correcao`
**Pré-condições:** contrato de conciliação de identidade (FN-016) e histórico de matrícula (FN-026).
**Pós-condições:** dado corrigido com registro de versão, sem romper histórico pedagógico.
**Tela/rota:** a definir.

**Fluxo principal:**
1. O responsável conclui a verificação de um pedido de correção.
2. O sistema apresenta os campos corrigíveis e o impacto.
3. A correção é aplicada e auditada.

**Fluxos alternativos e de erro:**
- 2a. Campo com histórico pedagógico vinculado: correção registrada sem reescrever o histórico.

**Regras de negócio:** docs/102 (L4-D); RL5.

**Critérios de aceite:**
- [ ] Correção reversível e auditada.

**Situação:** Não implementado — subfase 4.10.

---

## UC-064-10 — Avaliar guarda antes de eliminar ou anonimizar

**Ator:** responsável interno atribuído
**Permissão:** a definir
**Escopo:** sujeito do protocolo `anonimizacao` ou `eliminacao`
**Pré-condições:** prazos e fundamentos da instituição aprovados (FN-062).
**Pós-condições:** decisão de guarda registrada; sem política aplicável, a eliminação é bloqueada.
**Tela/rota:** a definir (`AvaliacaoGuardaTitular`, ausente no código).

**Fluxo principal:**
1. O sistema identifica tabelas e arquivos do sujeito e as obrigações de guarda.
2. Mostra o que pode ser eliminado, anonimizado ou preservado, com fundamento.
3. O responsável registra a decisão.

**Fluxos alternativos e de erro:**
- 1a. Instituição sem política de retenção: eliminação indisponível.

**Regras de negócio:** docs/102 (L4-D); RL5, RL6.

**Critérios de aceite:**
- [ ] Nenhuma eliminação sem fundamento registrado.

**Situação:** Não implementado — subfase 4.10.

---

## UC-064-11 — Simular anonimização ou eliminação e ensaiar recuperação

**Ator:** operação (Desenvolvedor)
**Permissão:** a definir
**Escopo:** base sintética
**Pré-condições:** UC-064-10 implementado.
**Pós-condições:** relatório de impacto por tabela e arquivo; ensaio de restauração com reaplicação da eliminação.
**Tela/rota:** a definir.

**Fluxo principal:**
1. Executar a simulação com dados sintéticos.
2. Conferir bloqueios de preservação e cópias independentes.
3. Restaurar o backup e reaplicar a eliminação.

**Fluxos alternativos e de erro:**
- 2a. Bloqueio de preservação ativo: item mantido e listado.

**Regras de negócio:** docs/102 (L4-E); risco R07 do docs/38.

**Critérios de aceite:**
- [ ] Relatório reproduzível; nenhum dado real usado.

**Situação:** Não implementado — subfase 4.11.
