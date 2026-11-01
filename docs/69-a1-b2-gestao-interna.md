# 69 — A1-B2: gestão dos acessos internos

PR #102 incorporado com aprovação em `29222ad`. Planejamento revisto antes desta fase.

## Decisões

- Somente contexto Desenvolvedor válido pode consultar a gestão e conceder Suporte/Financeiro a contas existentes, ativas e com senha inicial já trocada. Não criar contas nem enviar convites nesta fase.
- Recusar a própria conta e qualquer conta que já possua atribuição Desenvolvedor, mesmo suspensa. Não oferecer concessão, edição ou remoção de Desenvolvedor pela API/web.
- Atribuição mantém pessoa e perfil imutáveis. Suspensão, reativação ou alteração de fim da vigência exige a versão atual e gera nova versão, invalidando tokens antigos. Não excluir histórico.
- Serializar escritas internas em uma linha de controle transacional; revalidar o ator após obter o bloqueio. Registro de evento e alteração são atômicos. Eventos registram IDs, estado anterior/novo, motivo e origem; não incluem senha, token, nome ou e-mail.
- Primeiro Desenvolvedor: comando local com simulação por padrão, aplicação explícita, confirmação do ID, justificativa, manutenção e habilitação interna desligada. Não pode executar novamente após bootstrap nem se já existir atribuição Desenvolvedor. Nenhum bootstrap em conta real nesta entrega.
- Interface: listagem paginada, concessão por e-mail exato, edição de situação e vigência com justificativa. Sem operações comerciais T4-B nem acesso pedagógico.

## Validação e publicação

Testar identidade/escola/Suporte/Financeiro recusados, autopromoção e pares bloqueados, conflito de versão, revogação de tokens, rollback do evento, repetição do bootstrap e gates operacionais. Regressões SQLite/MariaDB, web e ensaio local via Computer Use. Sem deploy ou migração no servidor.

## Procedimento futuro do primeiro Desenvolvedor

Após revisão e aprovação específicas do operador e do ID da conta existente, simular `php artisan g360:iniciar-desenvolvedor ID`. Para aplicar, em janela aprovada e manutenção, com `ACESSOS_INTERNOS_ATIVOS=false`, usar `--aplicar --confirmar-usuario=ID --motivo=REFERENCIA_DA_APROVACAO`. Conferir o evento antes de habilitar a área e efetuar novo login. O comando não cria conta, senha ou token e não promove o administrador escolar automaticamente.

Recuperação do Desenvolvedor permanece operação administrativa fora da interface: manter a atribuição e eventos, revogar sessões e revisar uma intervenção específica antes de executá-la. Suspender a única conta não autoriza repetir bootstrap; o marcador permanece. Não há recuperação automática nem concessão de pares pela web.

## Resultados locais

API: regressão inicial de gestão e seleção com 11 testes/125 verificações. Após ampliar casos de vínculo pedagógico e marcador de bootstrap, gestão passou com 5 testes/66 verificações. Web: 7 testes/82 verificações (gestão e contextos internos). Testes de gestão adicionados ao CI MariaDB. Revisão de código aplicada aos bloqueios transacionais, validação de alvo, revogação, auditoria e bootstrap. CI completo e ensaio visual devem ser conferidos antes de pedir merge.

Ensaio visual via Chrome/Computer Use concluído em SQLite local com dados fictícios: seleção de Desenvolvedor, menu e formulário, concessão de Suporte e suspensão pela interface. Tela confirmou `Suspenso`; conferência local confirmou versão 2 e eventos `concessao`/`alteracao`. Nenhuma atribuição real foi criada. Ajustes finais de espaçamento e atalho do painel incluídos no PR; aprovação do CI final deve ser conferida no GitHub.

## Incorporação

PR #103 incorporado com aprovação do usuário em `98b28e1b1625815a6a58e27caec0ac2e06b44ebe`; quatro verificações do CI aprovadas no head `720ac479`. Sem deploy ou migração no servidor. Continuação: [A1-C](70-a1-c-secretario-escolar.md).
