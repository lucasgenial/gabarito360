# DC1 — integração com provas e sequência CP2

04/11/2026. Revisão das dependências do doc115 durante o avanço autônomo autorizado. O PR171 entrega a organização escolar do doc141; não fecha sozinho o DC1. Sua revisão visual final continua pendente. O especialista UI/UX e Product Design aprovou previamente a direção deste contrato, sem aprovar a interface ainda não implementada.

## 1. Identidade da disciplina nas provas

Referência nacional é sugestão de nomenclatura; disciplina operacional é o cadastro local da escola identificado por ID. Não comparar texto para descobrir automaticamente responsabilidade docente. Não vincular disciplina de outra escola, nem aceitar o ID de referência como ID local.

Novas provas institucionais passam a selecionar uma disciplina local ativa. Professor institucional recebe opções compatíveis com suas atribuições e turmas já autorizadas. Coordenação/Direção continuam sujeitas às permissões de criar/editar prova que já possuam; leitura ou gestão DC1 não cria esse poder. Espaço pessoal mantém o contrato próprio e não edita o catálogo oficial da escola.

Turmas e disciplina devem formar uma combinação válida. Selecionar turmas primeiro e oferecer disciplinas comuns às turmas selecionadas; ao mudar a disciplina, não apagar a seleção silenciosamente. Se não existir opção comum, explicar quais pré-requisitos precisam ser corrigidos. Professor precisa de atribuição correspondente em **todas** as turmas da prova, não apenas em uma delas. O backend repete a conferência no POST; alterar o navegador não contorna o alcance.

### Vigência

- Acesso de hoje exige conta e vínculo aceitos/vigentes, classe autorizada, catálogo/componente ativos e atribuição em curso na data civil America/Sao_Paulo. Atribuição programada aparece como programada, nunca como vigente.
- Não liberar antecipadamente uma função futura. A primeira integração usa a vigência atual para criação/alteração/publicação; uma data futura de aplicação não se torna autorização atual.
- Revalidar no commit e na publicação. Se acesso/atribuição mudou entre formulário e gravação, preservar o preenchimento e explicar o requisito que deixou de valer.
- A fotografia histórica de aplicação e as regras de correção tardia pertencem a MT1. Não aplicar uma exigência de vigência atual para apagar fatos anteriores ou reescrever notas/cartões/PDFs antigos.

### Preservação do legado

Provas antigas continuam legíveis com sua disciplina textual e IDs. Exibir “Disciplina anterior: X” quando precisar de uma escolha local deliberada. Editar somente título/observação não exige associação curricular. Trocar disciplina, acrescentar/trocar turma ou mudar o contexto operacional exige seleção local e revalidação explícita antes de salvar/publicar; não reconstruir a relação pelo nome.

A migração deve ser aditiva e retomável. Preparar relação externa ou campo nulo com FK composta conforme ensaio, sem backfill por texto. Preservar rótulo anterior e proveniência da escolha; não recalcular relatórios já emitidos. Se acrescentar coluna às tabelas acadêmicas, incluí-la explicitamente na lista de adições aceitas pelo comparador de deploy e comparar todos os campos anteriores. Questões/enunciados do editor futuro continuam em domínio separado.

## 2. CP2 — coordenação fornece cópia para outro professor

A solicitação original do responsável já autoriza essa operação na mesma escola. Ela não equivale a ampliar cópia institucional para todo professor nem a transferir alunos/históricos entre escolas.

Fluxo previsto: Coordenador autorizado escolhe uma prova que pode consultar, seleciona um destinatário Professor com vínculo aceito/vigente e atribuição DC1 elegível, informa novo título e motivo, confere o resumo e confirma. A disciplina de destino precisa ser escolhida explicitamente quando houver ambiguidade; não inferir correspondência por texto legado.

Resumo de efeito: “O original será preservado. O destinatário receberá uma nova prova em rascunho para revisar e vincular às próprias turmas.” Copiar somente configuração e gabarito suportados pelo sistema. Não levar turmas, alunos, cartões, respostas, notas, publicação, correção, tokens, links, envios ou permissões. Não conceder leitura da original ao destinatário.

Responsabilidade da cópia, autor original e coordenador que a entregou são referências distintas. Registrar vínculo de origem/destino, motivo, versão do contrato, digest do conteúdo e instante. O destinatário seleciona suas próprias turmas elegíveis antes de publicar. Nova cópia pode ser editada sem alterar original e fatos acadêmicos associados.

Idempotência por intenção e ator: reenvio da mesma confirmação produz uma cópia; outra intenção deliberada pode produzir outra variante. Revalidar origem, destinatário, espaço, permissão e lotação sob transação/travas. Concorrência ou revogação não pode criar cópia fora do alcance. Outbox transacional entrega notificação no sistema; e-mail, quando habilitado, apenas informa que há uma cópia disponível, sem dados escolares. Homologação mantém mailer de log, sem envio real.

A chave proposta `prova.duplicar_para_professor` será padrão somente do Coordenador e exigirá as demais capacidades de leitura/criação necessárias; não habilitar para equipe interna nem por herança de hierarquia. A implementação deve atualizar o catálogo03 com a autorização original rastreável antes da sincronização. Nenhuma chave ou rota nova é ativada por este documento.

## 3. Aceite antes de fechar/publicar

| Conjunto | Evidência necessária |
|---|---|
| DC1 organização — PR171 | Catálogo/indicação/atribuição, inativação por turma, vigência, histórico, isolamento e revisão visual final |
| DC1 integração | Seleção local, várias turmas com interseção de elegibilidade, troca de contexto, concorrência/revogação e legado sem conversão automática |
| Revisão visual | Chrome atual desktop amplo/menu recolhido e 320/390px; teclado, erros associados, vazios, conflito com rascunho preservado, ações e retorno contextual |
| CP2 | Original intacta, destinatário correto, rascunho sem turma/nota, nenhuma leitura indevida da fonte, idempotência e notificação transacional |
| Recuperação | Backup completo restaurado, conteúdo/arquivos/índices/FKs/gatilhos conferidos, dados anteriores preservados |
| Homologação acessível | Publicar DC1 somente após organização + integração concluídas e validadas; ID1 operacional continua desligado |
| Produção | Não autorizada nesta execução; decisão posterior do responsável |

## 4. Tabela para continuidade

| Pacote | Estado nesta revisão | Próximo trabalho |
|---|---|---|
| PF1/IM1 e avatar | Disponíveis na homologação estável3b9e6100 | Preservar imagem salva e estilos aprovados |
| ID1 fundação/provisórios | PR168–170 integrados; ensaios isolados, sem aplicação operacional | Contrato de verificação/conciliação global continua pendente |
| DC1 organização | Implementada no PR171 em rascunho; testes locais e CI/recuperação em conferência | Resolver conferência visual e corrigir eventuais achados |
| DC1 integração com provas | Contrato revisto neste documento | Implementar seletor/relação e validação sem alterar legado |
| CP1 | Cópia para si já existente | Regressão ao integrar disciplina local |
| CP2 | Planejamento pronto para detalhamento técnico após DC1 | Responsabilidade, proveniência, cópia e outbox |
| VI1/MT1/TR1/TE1/PA1/UX1 | Dependências do plano115 preservadas | Avançar somente contratos independentes; não contornar identidade/histórico |
| L4/T6 e release final | Sem fechamento artificial | Conciliar dependências e preservação antes de produção |

O controle do Chrome interrompeu a conferência do PR171 em uma confirmação local. A tentativa de recuperação pela janela nativa foi recusada pela revisão automática por não confirmar a URL ativa. Esse fato não é falha do CI nem aprovação visual. Foi mantida a entrega em rascunho e avançaram as verificações e os planejamentos independentes, conforme solicitado pelo responsável.
