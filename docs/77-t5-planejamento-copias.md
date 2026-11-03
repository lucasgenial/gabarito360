# T5 — planejamento da cópia de dados entre espaços

Status: proposta para revisão, sem implementação ou ativação. PR #110 incorporado em `00aa91339610f8ebc552c22da3c7a1e510585a64`. Este plano detalha T1 e os seis casos aprovados; não autoriza cópias reais, alterações de permissões ou deploy.

## 1. Objetivo e dependências conferidas

Criar cópias independentes, rastreáveis e retomáveis, mantendo origem, propriedade por espaço e alterações posteriores do destino. Não transformar mudança de vínculo em transferência automática de todos os dados.

| Dependência | Situação e consequência para T5 |
|---|---|
| T1 / docs/40 | Modelo e seis casos aprovados; origem/destino distintos, matrícula não prova identidade e autoria não concede propriedade |
| T3 | Espaço pessoal e cadastro mínimo implementados; respeitar `EspacoPessoal::VEDADOS` também na persistência e nos arquivos |
| B4 | Redis e workers disponíveis; jobs devem transportar referências mínimas, revalidar autorização e suportar reentrega |
| AU1 | Implementação incorporada e recuperação/volume ensaiados; captura desligada no servidor. Auditoria HTTP não substitui evento transacional do job |
| V1/V2 | Contextos por vínculo implementados, mas o corte consolidado no servidor continua pendente; não executar T5 no regime legado |
| T4/F1 | Cadastro comercial tem `contrato_referencia`; não existe motor completo de contratos/cláusulas. Texto de referência não comprova autorização para dados identificados |
| AU3 / T6 | Acesso interno a dados pedagógicos e exportação de espaços encerrados não estão disponíveis. Não criar exceção de acesso genérica em T5 |

Fontes consultadas: docs/28, docs/40 §§5–8, docs/64–65, docs/71–76; `ConviteVinculo`, `ContextoVinculo`, `ContextoInterno`, `EspacoPessoal`, `Auditoria` e `GravarAuditoria`.

## 2. Escopo e regras invariantes

- Copiar cria IDs e caminhos novos; nunca alterar `espaco_id` da origem, mover arquivos ou compartilhar a mesma linha entre espaços.
- Origem continua disponível conforme suas próprias regras; retirar vínculo não apaga dados escolares. Cancelamento de lote não exclui a origem nem objetos já publicados.
- Selecionar conteúdo explicitamente e apresentar prévia do alcance. Aceitar convite apenas torna a pessoa elegível para solicitar a operação; não inicia cópia por si só.
- Exigir autorização válida para ler/exportar a seleção na origem e admitir o conteúdo no destino. Dois vínculos na mesma conta não unem poderes. Pagamento, autoria e contexto interno comercial não bastam.
- Versões de autorização, situação e vigência são revalidadas na solicitação, em cada lote de processamento, na publicação e na retomada. Revogação ou suspensão interrompe trabalho pendente e impede publicação.
- Matrícula só é única dentro do espaço. Reutilizar apenas mapeamento de origem comprovado; colisão sem mapeamento bloqueia o item para conciliação. Não mesclar por nome, CPF ou matrícula, nem revelar identidade de registro que o solicitante não pode consultar.
- Reentrega não duplica nem sobrescreve edições do destino. T5 não é sincronização contínua. Conteúdo alterado na origem durante a cópia exige nova prévia/autorização da seleção afetada.
- Sem tokens, senhas, convites, permissões, sessões, links de responsáveis, eventos antigos de auditoria ou recibos de envio na cópia. Os eventos de T5 descrevem a operação nova.

## 3. Autorização proposta — decisão de produto, ainda sem concessões

Criar capacidades próprias para solicitar, autorizar origem e autorizar destino, sem reutilizar `cliente.gerir` ou tornar quem paga administrador pedagógico. A aplicação dessas capacidades no catálogo e seus padrões será revisada em T5-A antes de qualquer endpoint de execução.

| Papel/contexto | Proposta inicial |
|---|---|
| Proprietário pessoal | Solicitar e consentir com a seleção de seu próprio espaço pessoal; aceitar o recebimento no próprio espaço. Isso não aprova saída de dados escolares |
| Professor institucional | Solicitar seleção limitada ao conteúdo permitido por seu vínculo/turmas/autoria; não autorizar sozinho a saída institucional |
| Administrador de rede / diretor escolar | Aprovar a saída ou entrada institucional no próprio alcance, por capacidade T5 explícita. É uma capacidade nova proposta, não uma consequência da atual permissão de criar provas |
| Diretor de núcleo / coordenador / demais perfis | Sem concessão automática nesta primeira versão. Qualquer extensão requer revisão do catálogo e testes de escopo |
| Desenvolvedor / Suporte / Financeiro em contexto interno | Sem acesso pedagógico ou poder de autorizar T5 por esse contexto; AU3 é separado |

São duas decisões registradas, não obrigatoriamente duas pessoas distintas: quem possui legitimamente ambos os alcances poderá atuar em cada papel, sem seleção de contexto conferir o outro alcance automaticamente. A política deve guardar vínculo/versão de cada decisão. Servidor resolve identidade e escopo, nunca aceita `autorizado_por` enviado livremente pelo cliente.

Ao sair da escola, o antigo vínculo deixa de ser usado como credencial de leitura. Um representante institucional ainda autorizado executa/aprova a saída para o destinatário pessoal identificado, com concordância deste. Nunca reativar acesso escolar para viabilizar a cópia. Espaço suspenso/encerrado permanece bloqueado até existir o procedimento específico de T6; T5 comum não contorna a suspensão.

Para conteúdo identificado: autorização estruturada, com origem/destino, categorias/seleção, finalidade em catálogo, responsável, validade, versão da política e referência à evidência restrita aplicável. O responsável institucional deve confirmar a aplicabilidade da cláusula; não basta preencher uma URL ou `contrato_referencia`. Sem evidência validada, bloquear essa modalidade. F1 poderá fornecer a integração posteriormente; não inferir a base contratual de uma modalidade comercial.

## 4. Conteúdo permitido e minimização

| Conteúdo | Tratamento planejado |
|---|---|
| Provas, gabaritos e materiais | Primeiro fluxo funcional. Usar lista positiva de campos; campos livres e anexos podem conter dados pessoais, portanto não presumir que são anônimos. Excluir os não classificados/revisados |
| Turmas e alunos | Somente seleção autorizada, com destino explícito, novos IDs e mapa de origem; dependências não ampliam silenciosamente a seleção |
| Destino pessoal | Cadastro mínimo definido em T3: excluir CPF/hash, nascimento, gênero, responsável, foto/caminho e demais campos não admitidos. Não copiar fotos ou imagens de cartões que reintroduzam esses dados; avatar de catálogo permitido |
| Notas, cartões e respostas identificados | Apenas modalidade especificamente autorizada e minimizada. Recalcular referências sem misturar provas/turmas/alunos de espaços diferentes; não gerar links públicos ou disparar envio de resultados |
| Saída sem autorização para notas identificadas | Somente materiais sem dados pessoais. Estatística agregada depende de avaliação específica de reidentificação e grupos pequenos; não incluir notas individualizadas com ID trocado |
| Arquivos | Cópia física independente em armazenamento privado, digest e acesso por autorização. Links temporários da origem não são transportados |

Não preencher campos escolares obrigatórios com dados inventados quando a origem pessoal não os possui. A prévia aponta dados faltantes; entrada fica bloqueada até complementação autorizada ou até uma decisão de produto alterar o cadastro de destino em pacote próprio.

## 5. Modelo e execução propostos

Manter a base `copias_lotes` / `registros_origem` de docs/40, ainda não implementada. Acrescentar no desenho físico de T5-A:

- **Autorizações:** decisões de origem/destino, vínculos e versões, categorias, validade e referência restrita. Evidência e conteúdo livre não vão para fila, logs ou tela de outro contexto.
- **Lote:** UUID idempotente, origem/destino, solicitante, autorização, política, seleção/digest, estado e contadores. Reutilizar chave com parâmetros diferentes deve falhar, em vez de reaproveitar um lote incompatível.
- **Itens:** entidade em catálogo fechado, ID de origem, versão/digest verificada, dependências, estado, erro técnico em catálogo, tentativa e checkpoint. Unicidade por lote/entidade/origem e vínculo composto ao lote/espacos. Sem nomes livres de tabelas ou caminhos fornecidos pelo cliente.
- **Mapeamento:** unicidades de docs/40, conferência de propriedade e integridade em transação. O mapa permanece após novas tentativas. A versão inicial mantém relação um-para-um: duas origens não serão fundidas no mesmo destino. Conciliação não pode violar essa chave.
- **Caixa de saída transacional:** intenção de execução e eventos de domínio persistidos na mesma transação da mudança de estado/item. Entrega à fila e à auditoria é retomável por UUID. `afterCommit` sozinho não cobre queda entre commit e envio ao Redis.

Estados propostos: `rascunho`, `aguardando_autorizacao`, `pronto`, `em_processamento`, `aguardando_conciliacao`, `falha_recuperavel`, `bloqueado`, `concluido`, `cancelado`. T5-A fechará transições válidas e o contrato dos contadores. Conclusão exige todos os itens previstos conciliados ou exclusões da seleção explicitamente reaprovadas; erro não pode virar sucesso silencioso.

Usar inicialmente a fila `baixa` em unidades curtas, com limites de itens/bytes/tempo e concorrência definidos no ensaio de T5-B. Medir atraso dos demais jobs, especialmente auditoria; não liberar cópias volumosas que impeçam a confirmação de seus próprios eventos. Caso seja necessária fila dedicada, incluir configuração e recuperação dos workers em revisão operacional própria, sem pressupor consumidor já instalado.

### Publicação e concorrência

Preparar conteúdo e arquivos em área privada de staging, invisível aos fluxos de alunos, provas, cartões, relatórios, buscas e URLs normais. Não usar apenas um campo de status que leitores existentes ignorem. T5-B deve provar esse isolamento antes de disponibilizar o primeiro fluxo.

Publicar por unidade de dependências consistente, após conferir dados, arquivos e autorizações, em transação curta. Registrar objetos e mapeamentos junto ao recibo transacional. Usar locks de banco e chaves únicas como garantia; lock Redis é auxiliar e expiração não autoriza dois consumidores a publicar. Para operações grandes, definir unidades menores explícitas, sem uma transação aberta durante toda a cópia de arquivos.

Se o processo cair depois de criar arquivo mas antes do commit, o arquivo continua privado e reconciliável pelo identificador técnico do item. Se cair após commit, a retentativa encontra o mapa e o evento pendente, sem duplicar. Não fazer compensação destrutiva de objetos já publicados ou editados; limpeza de staging depende de política própria, posterior à verificação de referências/preservação.

### Auditoria durável

Criar eventos de solicitação, decisões, bloqueio/revogação, publicação, falha, retentativa e conclusão. Guardar ator solicitante, atores autorizadores e executor técnico separadamente; nunca atribuir a ação de negócio ao usuário técnico do banco. Não incluir dados escolares, justificativa livre ou conteúdo de contrato no evento.

Definir adaptação versionada para eventos de domínio no AU1: não inventar requisição HTTP ou status 200 para representar um commit de job. O esquema/adaptador atual é centrado em HTTP e exige extensão explícita. Evento e alteração de negócio devem nascer atomicamente na caixa de saída; consumidor usa a credencial dedicada para entregar ao histórico, com UUID idempotente. Falha do banco de auditoria deixa entrega pendente e visível, sem perder o recibo nem duplicar a cópia. Não exibir conclusão auditada enquanto houver recibo obrigatório não confirmado.

## 6. Pacotes e critérios de saída

| Pacote | Entrega | Critério para avançar |
|---|---|---|
| T5-A — fundação | Fechar matriz proposta, autorização estruturada, esquema/estados, itens/mapa, caixa de saída e eventos de domínio. Flag desligada; sem cópia exposta | Constraints MariaDB, autorização dupla, revogação, chaves e entrega durável testadas; revisão do contrato de auditoria |
| T5-B — materiais | Prévia e cópia em fila de provas/gabaritos e materiais admitidos; staging privado, FKs remapeadas e caminhos independentes | Primeira cópia e repetição concorrente passam; origem e edições no destino preservadas; conteúdo incompleto inacessível |
| T5-C — cadastro mínimo | Turmas/alunos autorizados, deduplicação por origem, conflitos de matrícula, minimização e dados faltantes | Conciliação sem mescla automática; pessoal sem campos/artefatos vedados; nenhuma transferência por edição simples |
| T5-D — continuidade e resultados | Integrar elegibilidade após aceite/saída; modalidade identificada somente com evidência aplicável; bloqueios de escola encerrada | Seis casos cobertos com resultados positivos/negativos. Anonimização e T6 não simulados como entregues |
| T5-E — acompanhamento e recuperação | Tela de prévia, autorização, progresso, conflitos e retentativa, com resultados mínimos por papel | API e interface não vazam origem, destinatários, IDs ou contagens fora de alcance; interrupção/replay/restauração ensaiados |

Revisar o plano antes de cada pacote. Se o resultado mostrar necessidade de subdivisão, registrar antes de implementar. Cada PR segue revisão/merge autorizado; publicação é etapa separada.

## 7. Matriz dos seis casos e testes

| Caso de T1 | Resultado exigido em T5 |
|---|---|
| 1. Autônomo entra na escola | Aceite não copia sozinho; seleção aprovada por origem/destino gera IDs novos. Pessoal permanece; repetir não duplica |
| 2. Trabalho na escola | Criação escolar permanece escolar. Autoria ou troca para aba pessoal não concede cópia; E2 não acessa E1 |
| 3. Saída com continuidade | Vínculo antigo continua revogado. Executor institucional autorizado pode realizar seleção validada; dados pessoais mínimos e gate contratual para resultados |
| 4. Saída sem continuidade | Origem intacta; jobs com autorização revogada bloqueados; nenhum lote criado automaticamente |
| 5. Escola encerrada | Acesso comum e cópia comum bloqueados; preservação/exportação especial pertence a T6, sem exclusão automática |
| 6. Continuidade após encerramento | Espaço pessoal não desbloqueia escola; aguardar procedimento institucional de T6. Materiais/resultados não são liberados por pagamento |

Testes adicionais obrigatórios: dois perfis em espaços diferentes sem soma de poderes; limites de turma; revogação concorrente ao commit; matriz de tipos de destino; origem=destino; IDs forjados; matrícula repetida; mapa prévio; fonte alterada; nova chave tentando recopiá-la; colisão de destino; queda antes/depois de commit/envio; Redis e auditoria indisponíveis; arquivo incompleto; URLs/cache/relatórios sem staging; mutação de origem/destino após cópia; minimização real no banco e nos arquivos; respostas e contagens sem vazamento.

Executar constraints/concorrência no MariaDB, e recuperação com Redis real. Testes SQLite não substituem esses ensaios. A matriz dos seis casos pode comprovar bloqueio correto de funcionalidade dependente de T6/F1, mas não permite declarar essas dependências implementadas.

## 8. Ativação e próximo passo concreto

Este PR contém apenas planejamento. Próximo passo proposto: **T5-A**, com revisão da matriz de autorização acima e do modelo de auditoria durável antes de criar migrations e serviços. Não alterar agora o catálogo oficial de permissões, o aceite dos convites ou os fluxos de desligamento.

T5 deve permanecer desligado por padrão. Ativação futura exige V1/V2 conciliados, AU1 operacional com credencial própria e monitoramento, evidência de autorização válida, testes de recuperação e aprovação da release. Não estimar porcentagem de T5 implementado a partir deste plano: execução funcional ainda não iniciada.

## Replanejamento de03/11/2026 — orientação posterior do produto

A solicitação posterior distinguiu duplicação pedagógica de prova, movimentação de matrícula e transferência institucional de dados. As regras incompatíveis deste documento (nome/posicionamento T5, capacidades de escola, restrições do CPF pessoal e identificação por espaço) devem ser revistas pelo [plano integrado115](115-replanejamento-provas-identidade-transferencias.md). O histórico deste documento descreve o que foi decidido/implementado antes; não é autorização para publicar a política antiga como se atendesse ao pedido novo. Plano115 é desenho para implementação, sem migration ou deploy nesta entrega. L4-D/E e o gate de publicação devem ser conciliados com as novas dependências.
