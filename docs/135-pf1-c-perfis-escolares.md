# PF1-C — implementação dos perfis escolares prioritários

Planejamento revisado em03/11/2026 após aprovação expressa da matriz134. Escopo: Vice-diretor escolar e Auxiliar de secretaria, preservando Secretário escolar. Concluir C1–C5 sem novos pedidos de continuidade e publicar apenas na homologação. Produção, ID1, importação XLSX e portais futuros ficam fora da entrega.

| Subdivisão | Entrega | Situação |
|---|---|---|
| C1 | Catálogo independente, contexto e migração aditiva de enums | Concluído; testes locais aprovados |
| C2 | Gestão, convite, seleção e concessão limitada e auditada | Concluído; testes locais aprovados |
| C3 | Projeção administrativa sem desempenho, autoria institucional central e proteção de status | Concluído; testes locais aprovados |
| C4 | Rótulos, equipe, catálogo, painel e ações por capacidade | Aprovado por UI/UX e Product Design em04/11/2026 |
| C5 | Testes, CI, ensaio isolado, backup e publicação na homologação | Em execução |

A migração acrescenta os novos valores aos enums sem criar vínculos, alterar pessoas ou promover contas. Sincronização é idempotente e mantém a política do Secretário. Reversão destrutiva é recusada. A comparação pré/pós do deploy deve preservar todo o conteúdo acadêmico e o perfil_criacao já existente.

A classificação de autoria institucional é única para Coordenador, Diretor e Vice-diretor. Provas criadas pelo Vice autorizado são institucionais; ao selecionar Professor, a mesma pessoa precisa da turma e não edita nem copia a prova. A fila existente mantém o perfil/contexto do ator, sem novos poderes de transferência.

Auxiliar usa projeções administrativas e não recebe notas, médias, provas, equipe ou relatórios. Status de aluno exige aluno.desativar também no campo ativo dos endpoints de cadastro/edição; aluno.gerir não contorna essa exigência. Vice tem leitura da equipe, com mutações condicionadas à concessão usuario.gerir e à hierarquia; sem autogestão nem gestão de pares/superiores.

Critérios: regressão do Secretário, mesma pessoa com vínculos distintos, escopo entre escolas, concessão real/auditoria, recusa de extras proibidos, retirada, expiração, suspensão, início futuro, autoria institucional e arquivos/exportações sem concessão. Web confere conteúdo administrativo, ações visíveis e troca por contexto. Evidência de UI em desktop/celular e parecer independente são necessários antes da integração. Recibos operacionais e capturas permanecem fora do Git.


## Complemento do responsável — grupos funcionais e imagem

Confirmado em03/11/2026: funções administrativas (Secretário/Auxiliar) não se acumulam com Professor, Coordenação, Direção/Vice-direção ou Aplicador na mesma instituição. Vínculo encerrado não impede nova contratação de outro grupo; o histórico é preservado sem manter o acesso antigo. Escolas distintas são independentes. Acumulação excepcional exige referência de chamado e liberação individual por usuário/instituição, feita por Suporte/Desenvolvedor, com motivo, versão e auditoria imutável. A liberação não cria vínculo nem une permissões. Este complemento substitui orientações anteriores que admitiam acumulação administrativa sem exceção.

O responsável também autorizou foto de perfil e escolha de avatar para usuários/professores. A imagem é editada pelo próprio titular, com galeria existente ou upload limitado/reprocessado e armazenamento privado. Não altera identidade, papéis ou permissões. Evidências e testes dessas duas extensões integram o fechamento de PF1-C antes de publicar somente na homologação.


### Complemento de imagem — modal, aluno, câmera e arquivo

Autorizado pelo responsável e desenho consultado previamente com UI/UX e Product Design em03–04/11/2026: clicar no avatar abre o diálogo com Avatar ou Foto. Em Foto, duas ações distintas: Tirar foto (captura nativa `capture=user`, somente ao toque) e Escolher arquivo. A disponibilidade da captura depende do dispositivo/navegador; o computador pode abrir o seletor de arquivos. A câmera física do celular não é simulada pelo teste responsivo do Chrome.

Usuário altera a própria imagem. Para aluno, a imagem só é alterada com `aluno.gerir`, dentro do alcance do vínculo ativo; leitura exige `aluno.ver` e o mesmo alcance. O aluno novo prepara a escolha localmente em “Usar esta imagem”; foto e cadastro são enviados juntos e persistidos na mesma transação. A seleção é descartada ao cancelar o cadastro; depois de um retorno de erro do servidor, o navegador exige selecionar a foto novamente, com aviso explícito e os demais campos mantidos. Para aluno existente e usuário, salvar imagem por AJAX preserva rascunhos de outros formulários. Cancelar/Escape não salva e devolve o foco.

A autorização inclui retrato privado de aluno no espaço pessoal, sem liberar CPF, nascimento, gênero ou responsável nesse contexto. O banco mantém esses bloqueios e caminhos de arquivo continuam proibidos no cadastro genérico. A migração só acrescenta `alunos.imagem_versao` e ajusta a guarda de retrato pessoal; não preenche fotos, altera vínculos ou modifica gabaritos. JPG/PNG/WebP até2MB,4096px/lado e12MP são reprocessados em WebP384×384, com orientação EXIF quando disponível, corte central e remoção de metadados. Sem reconhecimento facial, URL pública ou envio a serviço externo.

O comparador de publicação protege todas as colunas acadêmicas anteriores. Só admite as adições explícitas `provas.perfil_criacao` e `alunos.imagem_versao` quando ainda ausentes na origem; após existirem, ambas também entram na comparação. Recibos guardam as colunas antes/depois, quantidades e hashes, além de backup de banco e arquivos.


## Verificação de04/11/2026

API:726 testes aprovados,24 dependentes de MariaDB pulados no SQLite,5935 asserções; Web:159 aprovados,1530 asserções. Após a projeção de foto nas turmas,40 testes dirigidos aprovados,531 asserções. CI com MariaDB, parecer final de design, ensaio do pacote final e publicação seguem como fechamento C5; não antecipar conclusão ou promover produção.

A revisão independente pediu e recebeu: botão Fechar depois do sucesso (Cancelar só para alteração ainda não salva), ajuda técnica secundária, círculo sem distorção (span e imagem72×72 conferidos no DOM), ocultação de conteúdo pedagógico na tela administrativa de aluno e orientação de consulta nas permissões sem autoridade de edição. Evidências de QA usam pessoas e retratos sintéticos. Captura física da câmera de um celular requer verificação no dispositivo; o fallback nativo de computador foi exercitado.


## Parecer independente final

Em04/11/2026, o agente especializado aprovou UI/UX e Product Design da implementação atual. Reabriu a captura final320px, conferiu contenção do título e considerou resolvidas as pendências de leitura/concessão, exceção, imagem, estados de erro/vazio e troca/convite. Sem bloqueios de design para integração e validação na homologação. O parecer usa código e prévias fictícias desktop/mobile, claro/escuro; não substitui a segurança da API ou auditoria integral de acessibilidade. A câmera física segue como conferência de dispositivo, sem impedir o fallback de arquivo disponível e testado.

C5 deve ser encerrado pelo CI da revisão final, ensaio isolado e recibo de deploy exclusivo da homologação. Em caso de divergência acadêmica, erro de migração, falha do login web/API ou serviço indisponível, não abrir a release; usar o backup de banco/arquivos e a release anterior, sem rollback destrutivo das migrações. A autorização do responsável nesta entrega inclui merge e homologação; produção permanece vedada.

## Correção após teste do responsável — limite da imagem

PF1-C foi integrado no PR164 e publicado exclusivamente na homologação como `efd3c1c2`, com cinco verificações de CI aprovadas, backups legíveis e hashes/contagens idênticos nas nove tabelas acadêmicas. Em seguida, o responsável relatou HTTP429 ao salvar o avatar no celular. Planejamento desta correção: reproduzir a colisão da cota, isolar as operações de imagem, verificar abuso/isolamento, passar CI e publicar somente na homologação; sem alteração de interface, migração de banco ou produção.

Reprodução automatizada: doze consultas de vínculos válidas seguidas do primeiro salvamento de avatar devolviam429. O middleware numérico de Laravel usa a mesma assinatura do usuário entre rotas; a navegação consumia a cota numérica usada pela imagem. As rotas de imagem de usuário/aluno passam a usar um limitador nomeado, separado por usuário e operação. Mantêm dez requisições por minuto e Retry-After; IDs de alunos diferentes não multiplicam a cota. Não limpar cache global nem remover a proteção contra abuso.

Regressões locais:29 testes aprovados,404 asserções, incluindo consultas seguidas de salvamento, bloqueio da11ª tentativa, recuperação após prazo, usuários distintos e cotas distintas de aluno/conta. CI e publicação desta correção serão registrados no respectivo PR após conclusão.
