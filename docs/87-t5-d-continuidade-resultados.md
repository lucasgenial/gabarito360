# 87 — T5-D: continuidade e resultados

Revisão antes da implementação, após merge #120. C3 passou nos cinco checks, incluindo restauração de evidência/staging/job e publicação fictícia de uma turma + 99 alunos. Sem deploy ou migração no servidor.

## Subdivisão

| Parte | Entrega | Limite |
|---|---|---|
| D1 — este incremento | Avaliação interna da continuidade pessoal/escolar, encerramento explícito de vínculo de professor e testes dos seis casos T1 | Avaliação orientativa sem efeitos; encerramento separado e autorizado não copia/apaga cadastros; nenhuma cópia automática |
| D2 | Contrato próprio de resultados identificados, seleção de dependências e evidência aplicável | Revisar notas/cartões/respostas, referências às versões de provas/gabaritos, minimização, arquivos e imutabilidade antes de implementar; evidência de cadastro não autoriza resultados |
| D3, se necessário após D2 | Execução/recuperação dos resultados e matriz residual | Não declarar anonimização nem T6 implementados; liberar somente modalidades validadas |

T5-D não se conclui com D1. Acompanhamento/interface continua em T5-E. Complementação de nascimento, procedimento operacional de evidências e ativação continuam pendentes.

## Avaliação D1

Serviço interno `ContinuidadeCopia::avaliar` recebe dois atores já resolvidos pelo servidor e os espaços explícitos. Revalida capacidades de origem/destino separadamente antes de consultar o histórico institucional. Não aceitar identidades/vínculos livres de um futuro corpo HTTP; não há endpoint neste PR.

O histórico consultado é somente o vínculo de professor do proprietário pessoal com a escola selecionada. Retorna códigos técnicos, sem nomes, tokens, IDs de alunos ou dados de outras instituições. Entrada na escola fica indicada como apta à preparação após vínculo ativo e vigente; convite pendente/recusado, vínculo suspenso ou fora de vigência não representam aceite válido. Saída pode ser avaliada com histórico ativo ou encerrado, usando autorizador institucional atual e o titular pessoal. O vínculo antigo nunca é credencial da origem.

A avaliação é informativa e deve ser refeita ao usar a interface futura. Não cria lote, não fotografa decisões, não substitui os serviços T5-B/C nem sua revalidação no worker. As cópias genéricas autorizadas entre espaços mantêm seu contrato; o estado histórico do professor não concede nem remove poderes institucionais do autorizador atual.

`EncerrarVinculoProfessor` oferece a operação interna explícita de saída: gestor com `usuario.gerir`, papel/alcance atual e sem autoencerramento, somente vínculo de professor ativo/suspenso. Persiste encerramento e fim de vigência, registra evento/versionamento V1 e preserva identidade, vínculos de outras escolas, espaço pessoal e dados. Repetição não duplica evento. Convite pendente usa seu próprio aceite/recusa. Não expor rota neste incremento; tokens antigos deixam de resolver o vínculo, sem exclusão de contas/tokens ou reativação para viabilizar cópia.

Materiais demandam revisão sem dados pessoais, decisões dos dois lados e auditoria. Cadastro demanda evidência própria, decisões, conciliação e campos completos. `autoriza_execucao` e `execucao_automatica` são sempre falsos na avaliação. Resultados identificados e estatísticas anônimas são modalidades conhecidas porém indisponíveis, inclusive com cadastro já autorizado ou conta pagante. Nada é copiado por aceitar/recusar convite, sair ou pagar.

## Matriz de integração

1. Aceitar convite real altera o vínculo, preserva o espaço pessoal e não cria lote/job/mapa. Depois, seleção e duas decisões permitem cópia explícita de material com IDs novos; cadastro escolar sem nascimento continua bloqueado conforme C1/C2.
2. Professor em E1/autoria de prova não permite autorizar saída; contexto de E2 e contexto pessoal não leem E1. Conteúdo escolar permanece escolar.
3. Após saída, representante institucional vigente pode preparar material/cadastro mínimo autorizado para o pessoal. Vínculo antigo continua encerrado e inutilizável, inclusive após concluir a cópia.
4. Saída sem solicitação não cria lote nem apaga a origem. Revogação de vínculo usado em autorização pendente bloqueia o worker e preserva dados/staging.
5. Escola, ancestral ou cliente suspenso/encerrado bloqueia avaliação e cópia comum, inclusive pelo administrador da rede. T6 é procedimento separado.
6. Conta pessoal pagante continua acessível, mas não desbloqueia a escola encerrada, resultados identificados ou anonimização.

Executar testes com fluxos reais de convite, serviços de cópia e jobs, em SQLite e MariaDB. Regressão da recuperação completa permanece obrigatória. Não usar chamadas ao servidor nem dados reais.
