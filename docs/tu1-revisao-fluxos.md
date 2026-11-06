# TU1-R — revisão dos fluxos da turma

## Revisão TU1-R2 — direção aprovada antes da implementação

Solicitação posterior substitui as composições anteriores abaixo onde houver conflito.

| Frente | Entrega revisada | Aceite |
|---|---|---|
| Painel | Filtros em modal, resumo aplicado visível, cores e agregados pedagógicos reais | Cancelamento sem alterar recorte, amostra explícita, PDF equivalente |
| Docência | Dois selects: disciplina disponível e professor cadastrado | Operação atômica, confirmação dos acessos, idempotência e auditoria |
| Cadastro | Página de edição agrupada; sala, capacidade, etapa e observações opcionais | Migração aditiva, validação, preservação dos campos antigos |
| Alunos | Transferência em modal com conferência antes de salvar | Mesma escola, conflito tratado, histórico preservado |
| Interface | Ícones, desktop e celular | Revisão independente com evidências atuais |
| Entrega | CI, merge e publicação somente na homologação | Backup e comparação dos dados; produção excluída |

Capacidade é planejamento, sem bloqueio automático de matrícula. Etapa é informação cadastral, sem inferência curricular. Observações não entram automaticamente nos documentos. A atribuição conjunta informa expressamente os efeitos de indicação na disciplina e acesso à turma; nenhuma concessão parcial deve persistir em falha. APIs existentes permanecem compatíveis; a operação conjunta é aditiva.

Consulta prévia aprovada por UI/UX e Product Design e especialista em visualização pedagógica. Aprovação final depende da implementação atual, não dos prints anteriores.

Escopo solicitado em 06/11/2026. Publicação exclusiva na homologação; produção permanece intacta.

| Etapa interna | Entrega | Validação |
|---|---|---|
| R1 | Retornar do cadastro à aba Alunos; edição da turma em página completa | UR4, erros, retorno e perfis |
| R2 | Disciplina e professor no mesmo formulário; exclusividade por período | Transação, concorrência, duplicidade e histórico |
| R3 | Dashboard exclusivo da turma com recorte autorizado | Disciplina, professor autor, período e isolamento |
| R4 | Impressão e PDF do recorte, retrato/paisagem | Cabeçalho, emissão, paginação e revisão visual |
| R5 | Ações de alunos e movimentação entre turmas da mesma escola | Histórico, notas, auditoria, permissões e conflitos |
| R6 | Revisão independente, CI e publicação na homologação | Backup recuperável e preservação dos dados |

## Regras da revisão

- Novas atribuições não podem sobrepor nenhum papel docente na mesma disciplina da turma. Períodos usam fim exclusivo. O mesmo professor pode atuar em disciplinas distintas. Históricos existentes, inclusive codocência, são preservados.
- O formulário principal usa disciplina e professor. A confirmação identifica o par e a turma e explicita os efeitos de indicação e acesso. A API valida as quatro relações em uma transação; falhas não deixam concessões parciais. Não permite autoconcessão, vínculo pendente ou professor de outra escola.
- O filtro de professor do dashboard significa **professor autor**, usando a autoria registrada da prova; não atribui autoria ao professor atual da disciplina. Só entram avaliações aplicadas e visíveis ao ator.
- Secretaria continua sem acesso a notas ou análises pedagógicas. Ações de alunos exigem capacidade efetiva e escopo, além do perfil autorizado.
- Transferir significa mover **o aluno entre turmas da mesma escola**, registrando origem, destino, responsável, momento e motivo, sem reatribuir resultados antigos à nova turma.
- O contrato existente da API permanece; funcionalidades novas usam operações aditivas e documentadas. Referências de recursos continuam no transporte UR4, fora das URLs.

Consulta prévia: agente independente de UI/UX e Product Design; revisão de arquitetura e segurança independente. Aprovação final depende das evidências da implementação atual.

## Operações aditivas da API v1

- `GET /v1/turmas/{id}/painel`: DTO autorizado da turma. Filtros opcionais `disciplina_id`, `professor_id` (autor no contexto Professor), `inicio` e `fim` (datas de aplicação). Disciplina seleciona provas com vínculo curricular, não apenas texto livre e não uma fração das questões. Resultados são agregados no banco, sem transportar todas as notas para o BFF. Provas em correção/corrigidas sem data só entram no recorte sem período delimitado. Provas agendadas não entram.
- `GET /v1/turmas/{id}/painel/pdf`: mesmo recorte, com `orientacao=retrato|paisagem`. Exige `relatorio.turma`; revalida autorização e DTO depois da geração. Usa cabeçalho compartilhado e registro de emissão verificável. Arquivo temporário privado é eliminado após envio ou falha.
- `POST /v1/alunos/{id}/transferencias-turma`: `turma_id`, `turma_origem_id` esperada, `chave` UUID e `motivo`. Exige `aluno.gerir` e perfil Coordenação/Direção/Vice-direção/Secretaria, dentro do próprio escopo. Mesma escola/espaço e destino ativo; conflito 409 sem repetição automática. O PUT de cadastro existente conserva o formato e delega mudanças de turma ao mesmo serviço protegido.
- `GET /v1/turmas/{id}/vinculo-docente/opcoes` retorna disciplinas disponíveis e professores vigentes, sem CPF/e-mail. `POST /v1/turmas/{id}/vinculos-docentes` recebe `disciplina_id`, `professor_vinculo_id` e `chave_idempotencia` UUID. A transação cria o componente se necessário, indica o professor, concede acesso e registra a atribuição desde hoje. Os contratos anteriores permanecem disponíveis, inclusive gestão de períodos e histórico. Um recibo imutável permite reenvio sem repetir efeitos; chave com outro par gera conflito. Em MariaDB, a operação tem transação própria READ COMMITTED somente no próximo BEGIN, com retry de deadlock e sem alterar o isolamento da sessão.

As URLs de navegação Web continuam fixas. O catálogo UR4 transporta as referências de recursos e os filtros em requisições da sessão, com CSRF e revisão do vínculo; nenhuma nova URL Web inclui identificadores.

## Preservação e publicação

Migrações aditivas criam `aluno_movimentacoes` e `vinculos_docentes_compostos`, com eventos imutáveis no banco, motivo cifrado e autoria/perfil da ação. Não preenche nem altera alunos/cartões/notas existentes na migração. A fixação da turma de aplicação ocorre na transação de uma movimentação explícita. Havendo leitura/fila em andamento na escola, a movimentação é recusada até sua conclusão; novos envios, repetição, vínculo manual e confirmação de tentativa compartilham a barreira de espaço. Uma nova leitura confirmada conserva o snapshot de aplicação anterior.

O histórico do aluno no boletim inclui resultados autorizados de turmas anteriores; relatório da turma mantém resultados da coorte de aplicação, inclusive alunos que saíram. O professor da origem conserva o resultado da própria prova autorizada, sem receber o cadastro atual ou as novas provas da turma de destino.

Antes da abertura do tráfego na homologação, comparar todas as tabelas/arquivos anteriores; as diferenças previstas são duas tabelas novas vazias, quatro colunas opcionais nulas em turmas e os registros de migração. Backup recuperável obrigatório. Nenhuma migração ou publicação em produção está incluída.

## Situação da revisão

Fonte implementada e testes locais aprovados; PR204 permanece em rascunho. A conferência visual desktop/celular depende da recuperação do Computer Use no Chrome, cuja conexão está encerrando por timeout. Nenhum aceite visual final, merge, deploy ou migração em servidor ocorreu nesta revisão. O CI anterior de 6e1c649 estava verde; o novo código exige seu próprio CI.
