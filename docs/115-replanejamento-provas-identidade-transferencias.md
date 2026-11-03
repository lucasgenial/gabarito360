# 115 — Replanejamento integrado: provas, identidade, vínculos e transferências

03/11/2026. Solicitação explícita do responsável do produto. Planejamento; nenhuma implementação, migration, seed real, notificação ou deploy nesta entrega. Skill engineering:system-design aplicada. A orientação nova substitui as decisões incompatíveis dos docs40/64/77–98/114 sem apagar seu histórico. Não invalidar infraestrutura aproveitável. Detalhes de desenho abaixo são propostas técnicas para implementação, não funcionalidades já disponíveis.

## 1. Correção do entendimento e situação atual

A solicitação original de copiar prova era duplicação pedagógica no mesmo espaço, sem turmas/estudantes/resultados da original. T5 implementou outro problema: cópias independentes entre espaços com autorização bilateral. Esse mecanismo será reposicionado em Transferências; não será reutilizado como atalho para duplicar prova nem terá permissões ampliadas apenas por trocar o nome.

Código conferido: provas usam criado_por e disciplina textual; gabarito_questoes armazena alternativa/peso/anulação, não demonstra um editor completo de enunciados. Alunos têm turma atual; cartões possuem turma_aplicacao_id, já usada para preservar contexto histórico. Contas e vínculos são separados; ConviteVinculo já preserva conta/senha, mas parte de e-mail e impede aceite antecipado de início futuro. CPF de aluno é protegido por espaço; espaço pessoal bloqueia CPF e outros dados por constraints. Essas limitações precisam de migração explícita.

Escopo anterior: L4-A/C5 integrado até PR148; revisão UX de T5 PR149 integrada, ambas sem deploy. L4-D/E não concluídos; arquivos iniciais de guarda ainda não integrados. O novo plano altera dependências: não declarar L4 pronto nem publicar regras antigas de transferências antes da conciliação. Replanejar gate de release, preservando gabaritos e cadastros reais.

## 2. Vocabulário e fronteiras

| Fluxo | Finalidade | Efeito |
|---|---|---|
| Duplicar prova | Professor reutiliza/varia sua avaliação; coordenação fornece versão a outro professor da mesma escola | Nova prova em rascunho, IDs novos, sem vínculos de turmas, cartões, notas ou alunos |
| Alterar turmas da mesma prova | Aplicar a mesma versão também ao1ºB, quando elegível | Operação separada, com validação de lotação/estado; nunca alterar aplicação histórica já registrada |
| Movimentar matrícula | Aluno muda de turma ou escola | Encerra intervalo anterior e cria novo vínculo, preservando avaliações no contexto original |
| Transferências | Cessão/recebimento controlado de dados e histórico entre espaços | Conteúdo aprovado com origem, destino, corte e recibo; não apaga origem nem sincroniza tudo |
| Convidar para vínculo | Conta existente recebe nova função/escola | Mesma identidade/login; novo vínculo aceito e vigente, sem transportar automaticamente turmas/dados de outra escola |

## 3. CP — duplicação pedagógica de prova

### Professor para si

Em Provas, ação Duplicar prova nos itens elegíveis. Mostrar origem, título, disciplina, responsável e o que será copiado; novo título sugerido com indicação de cópia. Copiar configuração, gabarito e questões/conteúdo realmente suportado, sob lista positiva. Estado sempre rascunho. Zerar turmas, publicação, datas de aplicação, correção/fechamento, cartões, respostas, notas, QR, tokens, links e envios. Não gerar cartões antes de vincular explicitamente novas turmas e publicar.

A cópia fica atribuída ao professor atual e ganha campos de proveniência: prova_original_id imediato, prova_raiz_id, autor original, ator da duplicação, responsável da cópia e instante. Distinguir autoria de responsabilidade; preservar referência mesmo se original for arquivada, com FK restritiva ou referência histórica validada. No detalhe: Copiada de [prova], data e responsável; link só se o usuário ainda puder ler a original. Nada de revelar título/autor de fonte fora do escopo.

Exemplo1: original aplicada ao1ºA/C/D; professor recebeu lotação no1ºB. Duplica, escolhe somente1ºB, revisa e publica. Exemplo2: duplica para1ºC e altera parâmetros/gabarito; original permanece igual. Se houver edição de enunciados/números fora do modelo atual, detalhar editor/banco de questões em pacote próprio; não prometer que duplicar metadados já edita a prova textual.

### Coordenador para outro professor

Somente dentro da escola de seu vínculo. Ação Criar cópia para professor, selecionando professor com vínculo aceito e lotação elegível na disciplina. Não entrega acesso à prova original nem carrega turmas do autor. O destinatário recebe um rascunho próprio e seleciona suas turmas. Registrar origem, coordenador, professor destinatário, motivo e conteúdo/digest; notificar no sistema, com e-mail sem dados escolares. Não usar acesso interno para esta ação. Revalidar vínculo/lotação no commit e no posterior uso.

Permissões novas propostas: prova.duplicar_propria e prova.duplicar_para_professor. Professor institucional e professor_autonomo copiam para si dentro do próprio alcance; coordenador só para professores de sua escola. Demais poderes não são inferidos por hierarquia; manter operações já existentes de gestão sem ampliar duplicação silenciosamente. Duplo clique/reenvio da mesma intenção cria uma cópia; uma nova intenção pode criar outra variante, com outra chave.

## 4. ID — pessoa, conta e vínculos

Pessoa é identidade canônica; usuário é a conta de autenticação opcional; aluno/professor/diretor são papéis ou cadastros contextuais. Um aluno não precisa receber conta de login só para ser identificado. A mesma pessoa pode ser professora emA, coordenadora emB e titular de espaço pessoal. Nunca substituir os demais vínculos ao incluir um novo.

Proposta: pessoas(id interno, dados mínimos protegidos, estado de verificação); pessoa_documentos(pessoa_id,tipo,pais,valor_cifrado,indice_busca,versao_chave,verificado_em,origem_verificacao); usuarios.pessoa_id único; cadastros locais de aluno mantêm espaco_id e FK para pessoa quando conciliada. CPF válido e verificado possui unicidade global no registro canônico, não nas tabelas de matrícula. Reusar IDs internos como FKs; CPF nunca é PK, URL, token ou senha. Busca por índice HMAC com chave/versão controladas, cifra para recuperação autorizada; não SHA simples, não logs em claro. Normalizar e validar dígitos não autentica o titular nem consulta a Receita automaticamente.

Sem CPF: identidade provisória com UUID interno, cadastro limitado e marcação de conciliação. Nunca inventar CPF, fundir por nome/matrícula ou presumir que dois homônimos são a mesma pessoa. Ao informar CPF depois, abrir conciliação auditada, preservando os IDs históricos/mapeamentos; divergência de nome/documento exige revisão. A escola pode completar dados faltantes após verificar identidade e autorização. Situações sem CPF/documento incompatível não devem destruir um cadastro nem impedir registro histórico; caminho de exceção fica explícito.

Privacidade: busca global não é listagem pública. Somente operador autorizado, consulta exata, limitação de tentativas, auditoria, motivo e retorno mínimo. Nunca revelar e-mail completo, escola de origem, vínculos ou histórico antes do direito de consulta. CPF encontrado pode gerar referência opaca de pessoa/solicitação, não acesso aos dados dela. Duplicidade cadastral ou posse de CPF não é consentimento para cessão.

## 5. VI — cadastro a partir de CPF e convite de vínculo

1. Operador seleciona a instituição e informa CPF primeiro. Restante do formulário fica bloqueado enquanto a verificação está pendente.
2. Backend normaliza/valida e pesquisa identidade protegida. Se não houver correspondência conciliada, libera cadastro mínimo necessário. Revalida unicidade sob transação para duas pessoas cadastrando simultaneamente.
3. Encontrado usuário: mostrar apenas identificação mínima autorizada em leitura; não permitir sobrescrever nome/e-mail/senha globais. Operador informa papel, instituição, início e alcance do novo vínculo.
4. Início não pode ser anterior ao dia atual no fuso da instituição; armazenar instante UTC. Para hoje, não retroagir antes do instante autorizado; horário passado não cria acesso retrospectivo. Permitir início futuro. Importação de histórico anterior é fluxo distinto e não cria permissão retroativa.
5. Enviar convite para o e-mail já verificado da conta existente. Para pessoa nova, verificar o e-mail e concluir ativação por fluxo seguro. Não exibir ou reutilizar senha de terceiros, não trocar o e-mail de acesso por pedido da escola.
6. Titular aceita ou recusa. Aceite antecipado de convite futuro registra o aceite, mas mantém vínculo agendado; acesso só começa quando aceito, início atingido e instituição/conta ativas. Recusa/cancelamento/expiração não afetam vínculos de outras instituições.
7. Revalidar emissor, escopo e papéis ao aceitar e no acesso. Alterar papel/alcance/início após convite exige nova versão/convite; não elevar permissão silenciosamente.

Estados propostos: convidado, aceito_agendado, ativo, suspenso, recusado, encerrado; materialização compatível com estados atuais deve ser definida na migration. Não escolher somente o vínculo mais recente: exibir todos os vínculos simultâneos válidos; considerar datas para o caso escolar concreto. Notificações em outbox transacional, repetição idempotente, reenvio controlado e histórico de entrega. E-mail de convite não equivale a aceite.

## 6. DC — disciplinas, turmas e lotação docente

Catálogo de referência versionado; cada escola ativa seu catálogo local e pode criar componentes próprios pelo Coordenador. Selects exibem somente disciplinas ativas da escola e professores elegíveis da disciplina. Evitar usar uma string solta de disciplina como chave de identidade. Inativar preserva histórico; alteração de nome não reescreve o rótulo de uma aplicação antiga.

Carga inicial planejada para Ensino Médio: Língua Portuguesa, Língua Inglesa, Arte, Educação Física, Matemática, Biologia, Física, Química, História, Geografia, Filosofia e Sociologia. Usar áreas como agrupadores. Espanhol, eletivas, projetos, componentes técnicos e outros são configuráveis conforme matriz local, sem declará-los universais. Para Fundamental, oferecer conjuntos próprios (ex.: Ciências), não aplicar cegamente a lista do Médio. Não semear toda escola automaticamente; coordenador confere matriz/ano/etapa. Nenhum seed executado nesta entrega.

Relações propostas: disciplinas_referencia; escola_disciplinas; turma_componentes(turma,disciplina_escolar,ano/etapa); docente_habilitacoes(vinculo_professor,disciplina,periodo); lotacoes_docentes(vinculo_professor,turma_componente,inicio,fim,papel). Permitir mais de um docente no componente quando autorizado e registrar substituições/codocência. Bloquear duplicidade exata/sobreposição indevida, não proibir casos legítimos por UNIQUE simplista. Todos os IDs e FKs compostos precisam corresponder à mesma escola/espaço. Coordenação administra essas atribuições.

Prova identifica disciplina e responsável; aplicação identifica turma_componente/lotação vigente e versão do material/gabarito. Encerrar uma lotação revoga operações futuras sem apagar aplicações/notas antigas. Copiar para si ou colega não herda lotação de outro professor. Migrar vínculos atuais por evidência de turma e disciplina; faltantes entram em fila de conciliação, sem inventar responsabilidade.

## 7. MT — aluno entre turmas na mesma escola

Criar matrícula escolar e enturmações temporais. Matrícula identifica pessoa+escola+ano/período; enturmação tem turma, início, fim e motivo, vinculada à matrícula. Períodos usam início inclusivo/fim exclusivo. Considerar ensino regular e modalidades paralelas: não impor um único vínculo global do aluno. Mudança comum encerra a enturmação vigente e cria a nova no instante de corte, atomicamente.

Não mover notas/cartões antigos para nova turma. Cada aplicação conserva escola, turma de aplicação, disciplina, docente/lotação e versão de prova/gabarito. Aluno aparece na lista atual da nova turma e mantém linha do tempo/histórico, conforme permissão. Relatório da turma anterior conserva avaliações feitas lá; relatório da turma nova distingue resultados anteriores de resultados produzidos nela, sem contar a mesma nota duas vezes. Correções tardias da avaliação antiga pertencem ao contexto original e geram versão/evento.

Proposta de autonomia: Coordenador movimenta enturmação dentro da escola; Secretaria Escolar/Direção conforme permissão explícita e estrutura atual de matrícula. Professor solicita/consulta o alcance autorizado, não movimenta alunos unilateralmente. Matrícula entre escolas é outro fluxo, não update simples de turma_id.

## 8. TE — aluno entre escolas

1. Destino inicia ingresso por CPF ou identidade provisória verificada. Sistema consulta matrícula(s) elegível(is), com retorno protegido; origem mais recente é candidata, nunca prova única de que não existem vínculos simultâneos.
2. Criar solicitação institucional de cessão de histórico com pessoa, origem/destino, motivo, dados/categorias, data de corte e destinatários. Permitir preparar ingresso pendente sem liberar histórico protegido; não fundir cadastros por matrícula textual.
3. Notificar a origem dentro do sistema. Representante escolar autorizado confere correspondência e aprova/rejeita a cessão daquele aluno, período e categorias. Gestão superior autoriza a operação dentro de sua jurisdição; ver matriz abaixo.
4. Capturar manifesto da seleção e histórico autorizado até o corte. Criar vínculos/mapeamentos no destino com novos IDs locais; dados canônicos de pessoa não são duplicados. Não ampliar seleção para colegas ou turmas completas por dependência.
5. Executar de forma idempotente e publicar unidade consistente. Origem preserva seu cadastro/matrícula encerrada e fatos próprios até o corte; destino recebe histórico admitido e abre nova matrícula/enturmação. A escolaA não vê fatos novos produzidos na escolaB depois do corte.
6. Histórico autorizado de A pode ser consultado por B desde os primeiros registros efetivamente disponíveis e incluídos na cessão; não prometer dados inexistentes ou de outras origens ainda não autorizadas. B registra fatos novos como proprietária do contexto de produção, sem sobrescrever A.
7. Retificação posterior em A não reescreve o pacote finalizado. Gera evento de retificação e nova versão/delta submetido a política de recebimento, com notificação. B vê proveniência e versão; diferenças de escala/disciplinas são conciliadas sem recalcular nota antiga ou simular equivalência.

Modelo híbrido recomendado: registros de produção permanecem em seu espaço de origem; histórico recebido é snapshot versionado cifrado/acervo com autorização e proveniência próprios no destino. Evita FKs pedagógicas atravessando espaços e preserva o isolamento atual. Sem usar um link mutável global para dar acesso irrestrito ao futuro da origem. Após conclusão, suspensão comercial da origem não apaga a cessão validamente recebida; retenção/preservação/exclusão seguem política específica, não cascade.

## 9. TR — novo módulo Transferências e matriz de autoridade

Entrada: tabela de solicitações, filtros e ações permitidas; no máximo botões Transferir e Receber conforme perfil. Nada de seis botões de conteúdo nem manual longo ocupando a abertura. Transferir abre assistente: tipo → origem/destino → pessoas/itens → corte/categorias → prévia → autorizações → execução/recibo. Receber abre pendências/notificações e formulário de ingresso. Cada etapa mostra ator responsável, estado atual e próximo passo.

| Contexto | Solicitação | Decisão institucional | Execução |
|---|---|---|---|
| Escola origem/destino | Solicitar ingresso/cessão de aluno e acompanhar | Representante autorizado confirma saída/entrada do escopo escolar, sem poder de remessa em massa | Não recebe execução genérica entre escolas |
| Direção de Núcleo | Abrir/acompanhar dentro das escolas do núcleo | Autorizar no próprio alcance, respeitando decisões das instituições | Acionar operação já aprovada, se capacidade específica |
| Gestão de rede/Secretaria de Educação | Coordenar escolas/redes de sua jurisdição | Autorizar dentro da hierarquia explícita | Acionar operação aprovada por capacidade específica |
| Suporte/Desenvolvedor | Atender solicitação atribuída e preparar tecnicamente | Não substitui autoridade institucional nem obtém dados globais por ser interno | Execução delegada por caso, categoria e prazo; acesso mínimo, auditado |
| Professor autônomo proprietário | Exportar/receber no próprio espaço e solicitar integração com escola | Decide apenas seu lado; escola decide seu recebimento/cessão | Somente operação pessoal explicitamente autorizada |
| Professor escolar/Coordenador | Cópia pedagógica e solicitações que seu papel permitir | Não aprovam remessa institucional entre escolas por essa função | Sem execução genérica |

Esta matriz propõe separar solicitar/confirmar/autorizar/executar para compatibilizar a gestão superior pedida com a aprovação da escola de origem. Remover antigas capacidades genéricas T5 de execução do Diretor Escolar e do contexto escolar comum nesta nova política; não remover sua participação no recebimento/cessão de aluno. Não dar Suporte acesso universal a dados sensíveis. Secretaria é a instituição e secretario_educacao é o perfil atual; pagador comum não cria jurisdição. Transferência fora da mesma hierarquia exige decisões dos dois lados ou delegação explícita, nunca autorização unilateral de um núcleo.

Solicitações/evidências/aceites/execução/recibos em fluxos separados, versão esperada, validade e contexto. Estado do workflow: rascunho → solicitado → aguardando_origem → aguardando_destino → autorizado → em_execucao → concluido; rejeitado/cancelado/expirado/bloqueado não são sucesso. Operação concluída não reabre; retificações recebem nova referência. Notificações de solicitação, decisão, início, conclusão, rejeição e pendência via outbox; e-mail somente alerta mínimo com link autenticado, sem CPF/histórico.

Auditoria: solicitante real, aprovadores por lado, executor humano e técnico distintos, destinatário responsável, instituição, pessoa referenciada, seleção/categorias, corte, instantes UTC, política, manifesto/hash, quantidades, resultado e recibo de recebimento persistido. Distinguir recebido pelo sistema de lido/confirmado pelo responsável; registrar cada um se houver confirmação humana. Proibir UPDATE/DELETE nos registros finalizados pela credencial da aplicação; snapshots e decisões append-only, eventos encadeados/verificáveis e cópia de evidência independente. Retificação é acréscimo, nunca alteração silenciosa. Administrador do banco ainda pode ter privilégios físicos; imutabilidade exige separação de credenciais, verificações e cópias protegidas, não só um campo finalizado. Retenção e eliminação de evidências seguem política própria, sem prometer guarda infinita de dados pessoais.

## 10. PA — professor autônomo e continuidade

Nome técnico atual: professor_autonomo; rótulo de interface proposto Professor independente (padronizar apresentação sem renomear enum/FKs de uma vez). Contratação pessoal mantém conta única e espaço próprio. Cadastrar escolas de atuação como referências organizacionais do espaço pessoal, não criar instituições oficiais nem afirmar vínculo institucional sem convite aceito. Poder agrupar turmas e disciplinas por escola declarada; nomes iguais não conciliam instituições.

CPF de aluno pessoal opcional, protegido quando necessário e verificado; revisar formalmente as constraints/termos de T3 que o proíbem antes de habilitar gravação. Pessoal continua com formulário mínimo e sem importação silenciosa de campos completos da escola. Posse dos dados pelo professor não implica poder irrestrito sobre histórico de terceiros.

Integração pessoal→escola: convite institucional aceito; destino identifica o aluno por CPF conciliado ou revisão manual de identidade provisória. Mostrar comparação permitida, selecionar reutilizar cadastro local ou criar/completar faltantes. Nenhuma fusão automática por nome/matrícula; preservar proveniência dos resultados do professor e não representar nota pessoal como nota oficial sem decisão explícita. Conflitos vão para conciliação com recibo e rollback por unidade.

Saída da escola/encerramento do contrato: conta/e-mail e outros vínculos permanecem. Não converter automaticamente o vínculo escolar em proprietário dos registros da escola. Professor pode manter/ativar espaço pessoal e histórico mínimo que tenha sido previamente autorizado e admitido nele, referente às turmas/disciplinas/períodos de sua atuação. Acesso escolar encerrado não pode servir para exportar retroativamente. Dados completos e fatos futuros de outros professores permanecem indisponíveis. Resolver autorização de continuidade antes do encerramento, com prévia do que ficará e do que não ficará; impedir perda silenciosa e cópia excessiva.

## 11. Migração e proteção do acervo existente

Estratégia expandir → mapear → conferir → trocar leituras → restringir. Não renumerar gabaritos/cartões/notas existentes nem substituir suas FKs em massa sem mapa. Inventariar cada leitura de turma atual, aluno, disciplina textual, criado_por e contexto de acesso (relatórios, PDF, OMR, filas, B6, exportação, auditoria, T5 e L4). Backfill produz manifesto de mapeamentos e lista de ambiguidades; nenhuma associação por coincidência de nome.

Adicionar pessoa_id/matricula_id/enturmacao_id/lotacao_id e snapshots gradualmente. Na ausência de data/contexto histórico comprovado, marcar legado sem reconstrução e conservar referência original. Congelar contexto de novas aplicações antes de permitir movimentações. Relatórios devem distinguir turma atual de turma da avaliação e consultar notas pelo contexto histórico.

Ensaiar restauração completa em ambiente equivalente com volume representativo e duas instituições/duas identidades. Capturar antes/depois contagens e hashes de conteúdo/arquivos para gabaritos, questões, provas, cartões, respostas, notas e cadastros; conferir PDFs e relatórios antigos. Pausar escritores, fazer backup final/restaurável, migration aditiva/retomável, flags por pacote e smoke tests. Rollback de aplicação não faz down destrutivo. Não publicar somente a renomeação Transferências com permissões antigas como se a política nova estivesse aplicada.

## 12. Ordem e tabela de implementação

Cada linha é um pacote de nível1; letras/números internos são organização de desenvolvimento, sem pedidos de autorização para avançar. Concluir checks, revisão e Git em cada incremento. A regra atual continua: nenhum deploy antes de fechar L4; esta mudança de escopo exige integrar o gate de publicação com as dependências de identidade/transferências. Evitar publicar metade da política. Não estimar percentual por contagem destas linhas.

| Pacote | Divisões internas | Entrega e critério de saída |
|---|---|---|
| R0 — reconciliação | R0-A inventário; R0-B decisões e migração | Mapear lacunas/fluxos T5, riscos de CPF e históricos; plano rastreável sem apagar legado |
| ID1 — identidade | ID1-A esquema/proteção; B busca; C conciliação; D backfill | Pessoa única, CPF protegido, provisórios e ausência de enumeração; testes concorrentes/isolamento |
| VI1 — vínculos e convites | VI1-A CPF-primeiro; B agenda/aceite; C notificações | Mesma conta, convite aceito/recusado, início futuro sem acesso antecipado; outros vínculos preservados |
| DC1 — disciplinas e lotação | DC1-A referência; B catálogo local; C turma-componente; D docente/vigência | Coordenador organiza escola; selects filtrados e histórico de responsabilidade preservado |
| CP1 — duplicar para si | CP1-A contrato; B serviço; C interface; D ensaio | Novo rascunho independente sem turma/nota, origem sinalizada e original intacta |
| CP2 — duplicar para colega | CP2-A permissão; B responsável; C notificação | Coordenador entrega rascunho ao professor elegível, sem acesso indevido à original |
| MT1 — enturmação histórica | MT1-A matrícula; B intervalos; C relatórios/PDF/OMR | MudançaA→B mantém notas antigas na turma de aplicação e aluno aparece na atual |
| TR1 — controle institucional | TR1-A capacidades; B delegação; C auditoria imutável; D notificações | Gestão superior e execução por caso, escola confirma cessão, sem acesso interno genérico |
| TE1 — aluno entre escolas | TE1-A ingresso; B cessão/corte; C snapshot/conciliar; D receber/retificar | Origem conserva passado, destino recebe histórico autorizado e produz fatos novos isolados |
| PA1 — independente e integração | PA1-A escolas declaradas; B mínimo/CPF; C conciliação; D continuidade | Identidade e histórico admitido preservados, sem cópia irrestrita ao encerrar escola |
| UX1 — módulo Transferências | UX1-A acompanhamento; B Transferir; C Receber; D notificações | Primeira página com tabela e até duas ações; percurso por tipo e responsabilidades claras |
| L4/T6 — reconciliação final | Retenção/preservação, recuperação e encerramento | Rever D/E/T6 sobre pessoa/matrículas e recibos; não eliminar histórico por perda de vínculo |
| REL — publicação validada | Backup/restauração; pausa; migração; smoke; comparação | Gabaritos e históricos preservados, políticas novas íntegras e evidências de antes/depois |

Dependências: R0 precede tudo; ID1 precede busca global/VI1/TE1/PA1; DC1 precede seleção docente definitiva CP2; CP1 pode avançar em paralelo com ID1 após R0 por não exigir transferência global; MT1 precede TE1; TR1 precede execução TE1/transferências institucionais; UX1 acompanha contratos TR1/TE1, não os antecipa. L4 concluído isoladamente não comprova que estas novas funções estão prontas.

## 13. Cenários de aceite obrigatórios

- Professor1ºA/C/D duplica para1ºB recém-atribuído: cópia nasce sem turmas e publicação valida nova lotação. Alterar seu gabarito não muda original nem recalcule notas antigas.
- Coordenador duplica para colega1ºD/E/F: colega não recebe vínculos1ºA/B/C nem leitura da original fora de escopo.
- Duas tentativas concorrentes com mesma chave não duplicam prova, pessoa, vínculo ou transferência. Nova intenção legítima pode criar nova cópia.
- CPF com máscara, inválido, ausente, corrigido, duplicado legado ou concorrente não gera identidade errada. Conhecer CPF alheio não revela cadastro/histórico.
- Novo usuário verifica e-mail; existente aceita vínculo no login habitual. Convite futuro aceito hoje não libera hoje; recusa e expiração preservam outros vínculos.
- Enturmação muda sem mover nota antiga; relatórios de antes/depois e PDFs mantêm contexto, inclusive correção posterior e codocência.
- EscolaA→B com corte: A mantém seus fatos anteriores, B recebe só o autorizado e registra novos fatos sem exposição aA. Pendência/rejeição/cancelamento/falha parcial não aparece como concluído.
- Suporte com caso atribuído não pode ler/processar outro caso, nem aprovar como instituição. Revogação durante job impede publicação seguinte.
- Recibo finalizado e decisões não aceitam UPDATE/DELETE pela aplicação; retificação cria versão nova; auditoria indisponível falha de forma segura/retomável sem perder evento.
- Pessoal semCPF integra só após conciliação; conflito não sobrescreve aluno da escola. Saída da escola preserva conta pessoal e apenas dados mínimos/histórico admitidos, sem acesso aos futuros da escola.
- Backup restaurado preserva gabaritos, questões, cartões/imagens, respostas/notas e todos os mapas/recibos. Comparar conteúdo, não apenas contagens.

## 14. Fontes oficiais consultadas e limites

Consultadas em03/11/2026. Lei14.534/2023 adota CPF como número suficiente nos bancos de serviços públicos; isso não transforma posse de CPF em autenticação, não prova que todos os registros existentes estão corretos e não autoriza consulta irrestrita de histórico por um produto privado. Recomendação técnica é ID interno estável mais documento protegido/verificado, com exceções de cadastro provisório.

- Câmara: Lei14.534/2023: https://www2.camara.leg.br/legin/fed/lei/2023/lei-14534-11-janeiro-2023-793687-norma-pl.html
- Câmara: LDB atualizada, art35-D, componentes do Ensino Médio: https://www2.camara.leg.br/legin/fed/lei/1996/lei-9394-20-dezembro-1996-362578-normaatualizada-pl.html
- MEC: panorama da política de Ensino Médio: https://www.gov.br/mec/pt-br/centrais-de-conteudo/infograficos/politica-nacional-do-ensino-medio/politica-nacional-ensino-medio-bra-jul-24.pdf

Catálogo proposto é referência inicial, não definição obrigatória e completa de todas as matrizes/etapas de cada escola. Regras contratuais de cessão, guarda e continuidade precisam ser registradas por instituição; não inventar prazo legal ou presumir que contratação autoriza dados de terceiros.

### Complemento: tempo do fato e tempo do registro

Cada fato histórico deve distinguir ocorrido_em (quando ocorreu pedagogicamente) de registrado_em (quando entrou no sistema), além da versão e autoria. O pacote de corte inclui os fatos aprovados conhecidos naquela versão; lançamento tardio de um fato anterior ao corte gera retificação/delta, não mudança invisível do recibo finalizado. Nomes/disciplinas/turma do contexto histórico são preservados como snapshots mínimos, de modo que uma alteração cadastral atual não reescreva automaticamente documentos antigos.
