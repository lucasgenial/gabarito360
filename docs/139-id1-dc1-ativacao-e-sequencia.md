# ID1/DC1 — decisões de ativação e próxima entrega

04/11/2026. O responsável autorizou prosseguir sem interação e reunir decisões pendentes para seu retorno. A fundação técnica está no doc138. Esta proposta concreta não modifica o catálogo oficial03, concede permissões ou habilita consulta global. As subdivisões técnicas continuam autônomas; produção permanece excluída.

## ID1: proposta para a consulta de identidade

| Operação proposta | Quem seria elegível | Limite adicional |
|---|---|---|
| `identidade.usuario.consultar` | Administrador da Rede, Diretor de Núcleo, Diretor escolar e Coordenador, que já tenham `usuario.gerir` | Instituição no alcance e perfil de destino gerível pelo operador; consulta exata para emitir convite, não leitura geral de pessoa |
| Mesmo fluxo por Vice-diretor | Somente com concessão explícita da capacidade e `usuario.gerir` | Sem herança automática da direção e sem gestão de pares/superiores |
| `identidade.aluno.consultar` | Diretor escolar, Coordenador e Secretário escolar com `aluno.gerir` | Ingresso/matrícula na própria escola; não libera escola de origem, notas ou histórico |
| Auxiliar de secretaria | Proposta de extra específico, sem ativação automática | A permissão atual de editar aluno local não equivale à consulta global |
| Professor institucional/Aplicador/Financeiro | Nenhuma consulta global proposta | Mantêm somente o alcance atual |
| Professor independente | Contrato separado em PA1-B | CPF pessoal continua bloqueado pelas regras atuais até revisão explícita de T3 |
| Suporte/Desenvolvedor | Atendimento por caso atribuído e finalidade, depois do contrato de conciliação | Sem acesso global automático por ser equipe interna; não aprova em nome da escola |

Uma consulta autorizada retornará uma intenção opaca e o próximo passo permitido. Para conta existente, não revelar e-mail completo, outra escola, funções, telefone, nascimento ou histórico. A verificação do formulário não cria vínculo. Convite vai para o e-mail verificado da própria conta; o aceite não troca login nem credenciais e não antecipa vigência futura.

Consulta e ação final revalidam contexto aceito/vigente, alcance, autorização, validade da intenção e documento. Limitação própria por operador/operação, auditoria sem CPF em claro, resposta sem listagem e recusa segura em indisponibilidade. Saber um CPF não concede acesso. As chaves novas acima são propostas, sem inserção em seeds ou rotas.

### Decisão humana que permanece aberta

Definir a comprovação admitida antes de associar dois cadastros legados à mesma pessoa. Recomendação: documento declarado permanece declarado; conta existente confirma no próprio acesso verificado, e divergências/histórico de aluno exigem revisão institucional documentada. Nenhuma conciliação automática por nome, máscara, matrícula ou CPF com dígitos válidos. Contas fictícias de teste continuam fora de identidade verificada.

O operador deve registrar o tipo de evidência e referência protegida da conferência; não guardar cópias de documentos pessoais como justificativa genérica. Estabelecer quem pode revisar, segunda conferência quando houver conflito e como corrigir uma associação errada sem alterar fatos acadêmicos. Até esse contrato ser aprovado, ID1-D não grava mapas reais nem promove documentos declarados a verificados.

## DC1: contrato preparado enquanto a ativação de ID1 fica pendente

DC1-A pode preparar referência curricular sem consulta global de pessoas. DC1-B/C/D dependem da matriz de gestão abaixo e dos vínculos atuais, mas não devem esperar uma fusão de identidades para identificar um professor já existente. A referência é sugestão para montagem do catálogo local; não é matrícula, habilitação docente nem matriz escolar obrigatória.

| Conjunto inicial | Referências a oferecer para escolha local |
|---|---|
| Fundamental — anos iniciais | Língua Portuguesa, Arte, Educação Física, Matemática, Ciências, História, Geografia; Ensino Religioso tratado conforme oferta da instituição |
| Fundamental — anos finais | Referências anteriores e Língua Inglesa; escola confirma ano/modalidade e oferta |
| Ensino Médio | Língua Portuguesa, Língua Inglesa, Arte, Educação Física, Matemática, Biologia, Física, Química, História, Geografia, Filosofia e Sociologia |
| Componentes específicos | Espanhol, eletivas, projetos, componentes técnicos e locais cadastrados pela escola; não ativar universalmente |

Nomes e agrupamentos conferidos na [BNCC/MEC](https://basenacionalcomum.mec.gov.br/images/BNCC_EI_EF_110518_versaofinal_site.pdf), especialmente a estrutura de componentes do Fundamental, e na descrição da [base do Ensino Médio/MEC](https://basenacionalcomum.mec.gov.br/a-base). O PDF oficial foi aberto; a página HTML teve403 nesta rodada, mas o trecho indexado confirmou os agrupamentos. As fontes informam nomenclatura; o sistema não aplica carga horária, obrigatoriedade ou matriz legal automaticamente.

### Modelo e preservação

| Entidade | Contrato técnico |
|---|---|
| Referência versionada | Chave interna estável, nome, área, etapas sugeridas, fonte e edição; não apresentar chave interna como código oficial INEP/BNCC |
| Componente local | Espaço/escola, referência opcional, nome e sigla opcionais, situação e versão; criação local sem contaminar catálogo nacional |
| Turma–componente | Mesma escola/espaço, ano/período e componente local; impedir FK cruzada entre instituições |
| Habilitação docente | Vínculo Professor aceito/vigente, componente e intervalo; não inferir habilitação pelo cargo, nome ou prova antiga |
| Lotação | Vínculo Professor + turma–componente + início/fim + papel; codocência/substituição explícitas, sem UNIQUE que proíba dois docentes legítimos |
| Aplicação histórica | Preservar nomes/IDs do contexto e versão da atribuição; nome atual do componente não reescreve relatório antigo |

A mesma pessoa que é Diretor e Professor usa o vínculo Professor para lecionar. O cargo de direção não cria lotação automaticamente. Períodos usam início inclusivo e fim exclusivo; revalidar vínculo/lotação no uso e no processamento de fila. Encerrar vínculo não remove prova, gabarito, cartão ou nota. Sobreposição idêntica concorrente deve ser impedida sob trava do conjunto, preservando codocência autorizada.

Provas antigas continuam com disciplina textual e seus IDs. Um de-para sugere correspondência, mas valores ambíguos entram em revisão; não converter nomes automaticamente. Novas aplicações passam a referenciar o componente e conservar o contexto. Questões, enunciados e versões do material futuro continuam separados de provas, gabaritos e lotação. DC1 não implementa o editor textual.

### Matriz proposta para DC1

| Capacidade | Proposta inicial | Limite |
|---|---|---|
| `disciplina.ver` | Coordenação/Direção: toda a escola; Professor: componentes atribuídos | Sem dados de outras escolas; a referência pública não concede acesso escolar |
| `disciplina.gerir` | Padrão do Coordenador | Criar/ativar/inativar componente somente na escola do vínculo; não apagar histórico |
| `lotacao.gerir` | Padrão do Coordenador | Atribuir docente elegível às turmas/componentes da escola; não conceder perfil nem exceção funcional |
| Direção/Vice-direção | Leitura escolar; eventual gestão somente como extra explicitamente aprovado | Não inferir poderes novos pela hierarquia |
| Equipe administrativa e plataforma | Sem novos poderes pedagógicos | Mantém o catálogo vigente |
| Espaço pessoal | Gestão própria em contrato PA1/DC1 específico | Não modifica o cadastro oficial da escola selecionada como referência |

Esta matriz concretiza a solicitação de que a coordenação organize disciplinas e docentes. Sua ativação precisa respeitar a regra de aprovação de mudanças do catálogo03. As novas chaves permanecem fora dos seeds nesta proposta.

## Sequência e tabela para retomada

| Entrega | Situação nesta rodada | Próximo critério |
|---|---|---|
| PF1/IM1 e avatar do topo | Publicados e validados na homologação | Conferência de câmera física continua disponível para o responsável |
| ID1-A | Fundação implementada e testada, integração no PR168 | CI, ensaio e recibo conforme doc138 |
| ID1-B/C técnicos | Busca interna sem HTTP e inventário somente leitura implementados | Nenhuma habilitação pública implícita |
| ID1-B/C operacionais | Proposta de capacidades e verificação preparada aqui | Aprovar contrato antes de expor consulta/conciliação |
| ID1-D | Sem mapas ou fusão real | Verificação e tratamento do legado; preservar todos os IDs |
| DC1-A | Fontes e contrato preparados | Referência versionada e catálogo local sob matriz aprovada |
| DC1-B/C/D | Modelo e critérios preparados | Aprovar capacidades e implementar com revisão UI/UX prévia e final |
| VI1/CP2/MT1/TR1/TE1/PA1/UX1 | Dependências preservadas | Não contornar ID1/DC1 com acesso mais amplo ou transferência antiga |
| Produção | Sem publicação | Decisão expressa posterior do responsável |

As duas decisões de ativação ficam reunidas para o retorno do responsável, sem exigir respostas durante a execução técnica. Não declarar o conjunto ID1 ou DC1 concluído por ter preparado suas bases. Fechamentos de etapa, revisão, Git e publicação continuam seguindo as fronteiras combinadas; letras internas não viram deploys automáticos.
