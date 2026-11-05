# DC1 — disciplinas da escola e atribuições docentes

04/11/2026. Planejamento revisto antes da implementação. A solicitação original do responsável já autoriza o Coordenador a criar disciplinas da escola, indicar os professores de cada disciplina e organizar sua atuação nas turmas. Essa autorização prevalece sobre a pendência genérica proposta no doc139: não é necessário solicitá-la novamente. A consulta global de identidade e a conciliação de pessoas continuam separadas e desativadas.

## Entrega e limites

- Referência nacional versionada, baseada na nomenclatura do PDF oficial BNCC/MEC citado no doc139. A escola escolhe o que oferece; não se impõe currículo nem certificação profissional.
- Catálogo local: nome, sigla opcional, referência opcional, situação e versão. Inativação não apaga registros nem altera provas antigas.
- Indicação de professor por disciplina: vínculo Professor aceito e vigente na mesma escola. É organização pedagógica, não comprovação de formação. Diretores que lecionam usam seu vínculo Professor.
- Componente da turma: disciplina local associada à turma, inclusive sem professor atribuído. Atribuição com período e papel explícito (titular, codocência ou substituição), histórico e encerramento/cancelamento. Nenhum vínculo, perfil ou acesso à turma é criado implicitamente.
- Ativação do componente independente do catálogo: inativar/reativar somente naquela turma, preservando histórico e outras turmas. Recusar inativação com atribuições abertas e reativação com turma/catálogo inativos. Direção UI aprovada previamente pelo especialista em04/11.
- Atribuir exige, no momento da escrita, professor vigente, indicado na disciplina e previamente autorizado à turma. O formulário explica cada requisito e oferece os fluxos específicos existentes, preservando contexto. Consulta e escrita revalidam tudo.
- Professor lê apenas suas indicações e atribuições nas turmas autorizadas. Direção e Vice-direção leem a organização da própria escola. Gestão somente Coordenador com `disciplina.gerir`; atribuição exige também `turma.vincular_professor`. Retiradas de permissões continuam efetivas. Equipe administrativa, pessoal e plataforma não recebem novos poderes.
- Períodos docentes usam datas civis em America/Sao_Paulo; início inclusivo e fim exclusivo. Novas atribuições não começam no passado. Uma atribuição futura pode ser cancelada; uma em curso pode ser encerrada. Duplicidade/sobreposição do mesmo professor no componente é recusada; docentes diferentes exigem codocência/substituição explícita quando sobrepostos. Nenhuma troca encerra o anterior silenciosamente.
- Novas tabelas externas aos registros acadêmicos; chaves compostas impedem cruzamento entre espaços. Escritas sob transação e travas, versão otimista e identificador de operação nas atribuições. Auditoria append-only das mudanças, sem CPF, e-mail, notas ou fotos.
- Provas existentes mantêm sua disciplina textual, IDs e vínculos. DC1 não concilia texto legado por nome nem modifica regras de leitura/cópia de provas. A vinculação de aplicações ao componente com fotografia histórica será tratada em MT1; esta entrega organiza os responsáveis sem reescrever relatórios antigos. Questões/enunciados/editor futuro continuam separados.

## Interface e validação

Consulta prévia ao agente especializado UI/UX e Product Design realizada antes do código: catálogo pesquisável, filtros e paginação real; componente antes da lista de alunos; fluxo de referência ou disciplina própria; atribuição contextual; estados vazios específicos; tabelas compartilhadas e cards móveis com todas as ações. Sem novos KPIs e sem limitar a largura útil. Aprovação final por especialista independente exige Chrome atual em desktop e 320/390px, fluxos principais, erros e somente leitura.

| Parte interna | Resultado esperado | Situação inicial |
|---|---|---|
| DC1-A | Referências, modelo, isolamento, histórico e contrato de permissões | Em implementação |
| DC1-B | Catálogo local e indicação de professores | Em implementação |
| DC1-C | Disciplinas das turmas e atribuições temporais | Em implementação |
| DC1-D | Testes, revisão UI/UX, CI, recuperação e homologação | Pendente |
| ID1 operacional | Verificação/conciliação global mediante contrato próprio | Mantido pendente, sem bloquear DC1 |
| Produção | Publicação somente após decisão posterior do responsável | Excluída desta entrega |

O fechamento é do DC1 completo. Subdivisões internas não geram pedidos de autorização nem deploy público isolado. Ao publicar DC1 na homologação, as fundações ID1 integradas permanecem sem consulta pública e com preparação de identidades desligada. Fazer backup restaurável e conferir gabaritos, notas, cartões, respostas, cadastros e imagens antes/depois. Não executar backfill de identidades no ambiente acessível.

## Evidências em andamento

Revisão de continuidade: especialista aprovou previamente a substituição dos confirmadores nativos introduzidos noDC1 por um diálogo HTML compartilhado, restrito às novas ações. Título identifica ação/alvo; Cancelar recebe foco inicial; Esc/Cancelar retornam foco e não gravam; confirmação envia o formulário original com versão/contexto/CSRF e impede duplicação. Mensagens de regra de negócio precisam ser associadas aos campos e preservar o preenchimento. Esses ajustes exigem capturas atuais e nova revisão final; não alteram confirmações legadas nem autorizam publicação.

Backend inicial validado em SQLite e MariaDB:7 testes/82 asserções. Após acrescentar a situação independente por turma:8 testes/92 asserções em SQLite. Interface:6 testes/58 asserções, incluindo isolamento por contexto e recuperação explícita de versão409 preservando o rascunho. Chrome com fonte e API reais em banco SQLite local fictício confirmou indicação, acesso à turma separado e retorno com professor, papel e datas preservados. Celular390px sem overflow, ação de card44px. Capturas/revisão visual final, CI completo e ensaio de recuperação ainda pendentes; não confundir essas evidências parciais com fechamento ou autorização de produção.

A preparação local usa contas fictícias e rotas de bootstrap somente fora do repositório. Não entram em Git nem em artefato de release. A ativação no deploy isolado é explícita somente nas fases `ENSAIO-DC1`/`HOMOLOGACAO-DC1`; as demais releases preservam sua configuração. `IDENTIDADE_PROVISORIOS_ATIVOS` permanece falso.
