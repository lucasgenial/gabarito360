# 025 — Transferências institucionais (TR1, TE1, UX1) — Planejamento

**Implementação:** Pendente
**Etapa:** Em planejamento
**Prioridade no plano mestre:** Onda 3 — Ciclo de vida de dados pessoais e institucional (TR1 → TE1 → UX1)
**Natureza:** nova (substitui a política de FN-024)
**Origem:** docs/115 §7–§9 e §12 (replanejamento de 03/11/2026), docs/116, 117, 139
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir a cessão e o recebimento controlados de dados e histórico de alunos entre escolas e espaços, com autoridade institucional explícita em cada lado, corte temporal, recibo e auditoria imutável. A origem preserva seus fatos; o destino recebe só o autorizado e produz fatos novos isolados.

## 2. Escopo
- **Inclui:**
  - **TR1 — controle institucional:** capacidades separadas para solicitar, confirmar, autorizar e executar; delegação por caso a Suporte/Desenvolvedor; auditoria append-only; notificações das etapas.
  - **TE1 — aluno entre escolas:** ingresso pelo destino (CPF ou identidade provisória verificada), solicitação de cessão com categorias e corte, decisão da origem, snapshot versionado cifrado no destino, recebimento e retificação por delta.
  - **UX1 — módulo Transferências:** tabela de solicitações com no máximo as ações Transferir e Receber; assistente por etapas; pendências e notificações.
- **Não inclui:**
  - Duplicar prova → FN-042.
  - Mudança de turma na mesma escola e matrícula temporal → FN-026 (MT1, pré-requisito).
  - Identidade de pessoa e conciliação → FN-016 (pré-requisito).
  - Convite de vínculo CPF-primeiro → FN-017.
  - Continuidade do professor independente ao sair da escola → FN-027 (PA1-D).
  - Encerramento de cliente → FN-028.
  - Exportação de dados do titular → FN-064.
  - Notificações como módulo → FN-057 (transversal; ver §8).

## 3. Fontes de verdade
- Documentos de origem: docs/115 §7–§9, §11–§13 (`docs/115-replanejamento-provas-identidade-transferencias.md`), docs/116 (`docs/116-catalogo-nacional-instituicoes.md`), docs/117 (`docs/117-r0-conciliacao-e-marcos-de-entrega.md`), docs/139 (`docs/139-id1-dc1-ativacao-e-sequencia.md`).
- Infraestrutura reaproveitável (sem a política): FN-024 (docs/77–99).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) — a matriz TR1 ainda não está no catálogo.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Sem UC no docs/07.
- Mockups: não há mockup do módulo Transferências; exigir aprovação de UI/UX antes de UX1.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-016 identidade-pessoal | Pessoa única, CPF protegido, provisórios e conciliação (ID1 precede TE1) | Parcial |
| FN-026 matricula-enturmacao | Matrícula com intervalos; MT1 precede TE1 | Parcial |
| FN-031 catalogo-instituicoes | Instituições de referência para conciliação institucional de TE1 | Em homologação |
| FN-057 notificacoes | TR1-D e UX1-D são notificações; módulo transversal | Pendente (não listado no plano mestre; ver §8) |
| FN-024 copias-entre-espacos | Infraestrutura de lotes, outbox, staging cifrado e recibos | Substituída (código ativo) |

**Funcionalidades que dependem desta:** FN-027 (integração pessoal → escola usa a matriz TR), FN-028 (T6 reconcilia recibos e cessões), FN-064 (L4-D/E sobre pessoa/matrículas/recibos).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (TR1-A) Capacidades | Matriz solicitar/confirmar/autorizar/executar por contexto; remoção das capacidades genéricas T5 nesta política | Matriz aprovada no docs/03 e testada | Pendente | docs/115 §9 |
| 1.2 (TR1-B) Delegação | Execução delegada por caso, categoria e prazo a Suporte/Desenvolvedor | Suporte não lê outro caso nem aprova como instituição | Pendente | docs/115 §13 |
| 1.3 (TR1-C) Auditoria imutável | Eventos append-only encadeados; sem UPDATE/DELETE pela credencial da aplicação | Teste de imutabilidade e falha segura | Pendente | docs/115 §9 |
| 1.4 (TR1-D) Notificações | Eventos de solicitação, decisão, início, conclusão, rejeição via outbox | Usa FN-057 | Pendente | docs/115 §9 |
| 2.1 (TE1-A) Ingresso | Destino inicia por CPF/identidade provisória; consulta protegida de matrícula elegível | Sem enumeração | Pendente | docs/115 §8 |
| 2.2 (TE1-B) Cessão e corte | Solicitação com categorias e data de corte; decisão da origem | Rejeição/cancelamento não aparece como concluído | Pendente | docs/115 §8 |
| 2.3 (TE1-C) Snapshot e conciliação | Manifesto, snapshot versionado cifrado, novos IDs locais | Origem conserva passado; destino isolado | Pendente | docs/115 §8 |
| 2.4 (TE1-D) Receber e retificar | Retificação como delta versionado | Recibo finalizado imutável | Pendente | docs/115 §8 |
| 3.1 (UX1-A) Acompanhamento | Tabela de solicitações, filtros, estado e próximo passo | Revisão UI/UX aprovada | Pendente | docs/115 §9 |
| 3.2 (UX1-B) Transferir | Assistente tipo → origem/destino → itens → corte → prévia → autorizações → recibo | Idem | Pendente | docs/115 §9 |
| 3.3 (UX1-C) Receber | Pendências e formulário de ingresso | Idem | Pendente | docs/115 §9 |
| 3.4 (UX1-D) Notificações na interface | Usa FN-057 | Idem | Pendente | docs/115 §9 |

## 6. Contrato técnico (proposto, docs/115)
- **Estados:** rascunho → solicitado → aguardando_origem → aguardando_destino → autorizado → em_execucao → concluido; rejeitado, cancelado, expirado e bloqueado não são sucesso. Operação concluída não reabre.
- **Modelo híbrido:** registros de produção ficam na origem; histórico recebido é snapshot cifrado com proveniência no destino; nenhuma FK pedagógica atravessa espaços.
- **Idempotência:** chave de intenção; mesma chave não duplica pessoa, vínculo ou transferência.
- **Tempo:** `ocorrido_em` × `registrado_em`; lançamento tardio anterior ao corte gera retificação.
- **Migrations:** aditivas e retomáveis; expandir → mapear → conferir → trocar leituras → restringir.
- **Desempenho:** medir com o padrão de 1000 usuários simultâneos as telas de acompanhamento antes da homologação (FN-077).

### Detalhamento herdado do docs/115

Passos de TE1 (docs/115 §8):
1. O destino inicia o ingresso por CPF ou identidade provisória verificada. O sistema consulta matrículas elegíveis com retorno protegido. A origem mais recente é candidata. Ela não prova que não existem vínculos simultâneos.
2. O sistema cria a solicitação institucional de cessão de histórico com pessoa, origem, destino, motivo, categorias de dados, data de corte e destinatários. O ingresso pode ficar pendente sem liberar histórico protegido. Não funde cadastros por matrícula textual.
3. A origem é notificada no sistema. Um representante escolar autorizado confere a correspondência e aprova ou rejeita a cessão daquele aluno, período e categorias. A gestão superior autoriza dentro da sua jurisdição (matriz abaixo).
4. O sistema captura o manifesto da seleção e o histórico autorizado até o corte. Cria vínculos e mapeamentos no destino com novos IDs locais. Os dados canônicos da pessoa não são duplicados. A seleção não se estende a colegas ou turmas inteiras por dependência.
5. A execução é idempotente e publica uma unidade consistente. A origem preserva cadastro, matrícula encerrada e fatos próprios até o corte. O destino recebe o histórico admitido e abre nova matrícula e enturmação. A escola A não vê fatos novos da escola B depois do corte.
6. A escola B consulta o histórico autorizado de A desde os primeiros registros incluídos na cessão. Não promete dados inexistentes nem de outras origens. B registra fatos novos como dona do contexto de produção, sem sobrescrever A.
7. Uma retificação posterior em A não reescreve o pacote finalizado. Gera evento de retificação e nova versão ou delta, sujeito à política de recebimento, com notificação. B vê proveniência e versão. Diferenças de escala e de disciplinas são conciliadas sem recalcular nota antiga.

Após a conclusão, a suspensão comercial da origem não apaga a cessão validamente recebida. Retenção, preservação e exclusão seguem política própria, sem cascade.

Matriz de autoridade (docs/115 §9). Separa solicitar, confirmar, autorizar e executar.

| Contexto | Solicitação | Decisão institucional | Execução |
|---|---|---|---|
| Escola origem ou destino | Solicita ingresso ou cessão de aluno e acompanha | O representante autorizado confirma saída ou entrada do escopo escolar, sem remessa em massa | Sem execução genérica entre escolas |
| Direção de Núcleo | Abre e acompanha nas escolas do núcleo | Autoriza no próprio alcance, respeitando as instituições | Aciona operação já aprovada, se tiver capacidade específica |
| Gestão de rede ou Secretaria de Educação | Coordena escolas e redes da sua jurisdição | Autoriza dentro da hierarquia explícita | Aciona operação aprovada, com capacidade específica |
| Suporte ou Desenvolvedor | Atende solicitação atribuída e prepara a parte técnica | Não substitui a autoridade institucional. Não obtém dados globais por ser interno | Execução delegada por caso, categoria e prazo, com acesso mínimo e auditado |
| Professor autônomo proprietário | Exporta e recebe no próprio espaço. Solicita integração com escola | Decide só o seu lado. A escola decide o seu recebimento ou cessão | Só operação pessoal explicitamente autorizada |
| Professor escolar ou Coordenador | Cópia pedagógica e solicitações que o papel permitir | Não aprova remessa institucional entre escolas por essa função | Sem execução genérica |

Regras da matriz:
- Remover as capacidades genéricas de execução de T5 do Diretor Escolar e do contexto escolar comum. A participação no recebimento e na cessão de aluno permanece.
- Suporte não recebe acesso universal a dados sensíveis.
- Secretaria é a instituição. `secretario_educacao` é o perfil atual. Pagador comum não cria jurisdição.
- Transferência fora da mesma hierarquia exige decisão dos dois lados ou delegação explícita. Um núcleo não autoriza sozinho.
- Solicitação, evidência, aceite, execução e recibo ficam em fluxos separados, com versão esperada, validade e contexto.

Auditoria (docs/115 §9). Registrar: solicitante real, aprovadores por lado, executor humano e técnico distintos, destinatário responsável, instituição, pessoa referenciada, seleção e categorias, corte, instantes UTC, política, manifesto e hash, quantidades, resultado e recibo de recebimento. Distinguir "recebido pelo sistema" de "lido ou confirmado pelo responsável". Snapshots e decisões são append-only. Os eventos são encadeados e verificáveis. Guardar cópia de evidência independente. Retificação é acréscimo. O administrador do banco ainda tem privilégios físicos. Por isso a imutabilidade exige credenciais separadas, verificações e cópias protegidas, não só um campo "finalizado". Retenção e eliminação das evidências seguem política própria.

Notificações: o e-mail leva só alerta mínimo com link autenticado, sem CPF nem histórico.

UX1 (docs/115 §9). A página inicial mostra a tabela de solicitações, filtros e ações permitidas. No máximo dois botões: Transferir e Receber, conforme o perfil. O assistente Transferir segue: tipo, origem e destino, pessoas e itens, corte e categorias, prévia, autorizações, execução e recibo. Receber abre pendências, notificações e o formulário de ingresso. Cada etapa mostra o ator responsável, o estado atual e o próximo passo.

Estratégia de migração (docs/115 §11): expandir, mapear, conferir, trocar leituras e restringir. Antes da publicação: ensaiar a restauração completa com duas instituições e duas identidades, comparar contagens e hashes, pausar escritores, fazer backup final restaurável, usar migration aditiva e retomável, flags por pacote e smoke tests. Não publicar só o nome "Transferências" com as permissões antigas de T5.

Cenários de aceite (docs/115 §13):
- Escola A para B com corte: A mantém seus fatos anteriores. B recebe só o autorizado e registra fatos novos sem exposição a A. Pendência, rejeição, cancelamento ou falha parcial não aparece como concluído.
- Suporte com caso atribuído não lê nem processa outro caso. Não aprova como instituição. Revogação durante o job impede a publicação seguinte.
- Recibo finalizado e decisões não aceitam UPDATE nem DELETE pela aplicação. Retificação cria versão nova. Auditoria indisponível falha de forma segura e retomável, sem perder evento.
- Duas tentativas concorrentes com a mesma chave não duplicam transferência.
- Backup restaurado preserva gabaritos, questões, cartões, imagens, respostas, notas, mapas e recibos. A comparação cobre conteúdo, não só contagens.

## 7. Interface
Primeira página com tabela e até duas ações (Transferir, Receber); cada etapa mostra ator responsável, estado e próximo passo; sem manual longo na abertura. Estados de carga, vazio e erro conforme FN-076; tabela conforme FN-074. Não publicar o rótulo "Transferências" com as permissões antigas de T5.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 3 — política T5 × docs/115 | Declarar T5 substituída; flags `COPIAS_*` desligadas em produção até TR1; não renomear antes de TR1 | A do plano mestre | Pendente |
| §4 item 4 — aceite visual de T5 | Dispensar e fazer na UX1 | A do plano mestre | Pendente |
| §4 item 6 — matriz ID1 (destrava TR1/TE1) | Aprovar matriz | A do plano mestre | Pendente |
| §4 item 12 — contrato de conciliação de identidade | Escrever como subfase de FN-016 na Onda 1 | A do plano mestre | Pendente |
| Dependência de FN-057 (notificações) não consta no plano mestre (que lista só 016, 026, 031), embora TR1-D e UX1-D sejam notificações e a governança proíba versões locais | Acrescentar FN-057 às dependências / manter TR1-D sem notificação | Acrescentar no plano mestre | Pendente (divergência registrada) |
| Imutabilidade exige separação de credenciais do banco | — | Definir junto com FN-004 (revisão de privilégios) | Pendente |
| Regras contratuais de cessão e guarda por instituição | — | Não inventar prazos; registrar por contrato (FN-068) | Pendente |

## 9. Ramos desta entrega
Nenhum (não iniciada).

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
