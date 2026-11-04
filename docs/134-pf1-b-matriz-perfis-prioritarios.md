# PF1-B — matriz proposta dos perfis prioritários

Estado: **matriz aprovada expressamente pelo responsável em03/11/2026; implementação PF1-C autorizada até validar na homologação**, sem produção. Complementa o [inventário133](133-planejamento-perfis-institucionais.md). Não modifica o [catálogo oficial03](03-perfis-e-permissoes.md), que exige aprovação do responsável para mudar as permissões. A implementação é PF1-C; não iniciar ID1 por consequência deste trabalho.

## Contrato proposto

Os três perfis exigem vínculo de escola aceito, ativo e vigente. O alcance é exclusivamente a escola daquele vínculo. Não há soma com os demais vínculos da mesma conta, acesso antecipado por data futura nem permissões derivadas do cargo cadastral.

- **Vice-diretor escolar** (`vice_dir_escolar`): acompanhamento de toda a escola como padrão; operações de gestão e intervenção somente por concessão explícita e auditada, limitada ao que o concedente também pode fazer. Não é um segundo administrador irrestrito. Pode lecionar por um vínculo Professor separado na mesma escola.
- **Secretário escolar** (`secretario_escolar`): manter exatamente a política existente de administração de turmas/alunos. Não adicionar poderes pedagógicos nem gestão de equipe nesta revisão.
- **Auxiliar de secretaria** (`auxiliar_secretaria`): cadastro e atualização de alunos, consulta administrativa de turmas/escola. Sem desativar alunos, gerir turmas, importar em massa, acessar desempenho ou administrar contas. Sem extras nesta primeira versão.

## Matriz de capacidades

P = padrão; E = extra concedível expressamente; — = proibido, inclusive como extra. Qualquer chave não listada é proibida para os dois perfis novos. Retiradas continuam possíveis. Não alterar a política atual do Secretário escolar para acomodar os novos perfis.

| Ação/chave atual | Vice-diretor | Secretário escolar | Auxiliar |
|---|:---:|:---:|:---:|
| `escola.ver` | P | P | P |
| `escola.editar`, `escola.logo` | E | — | — |
| `turma.ver` | P | P | P |
| `turma.gerir`, `turma.desativar` | E | P | — |
| `turma.vincular_professor` | E | — | — |
| `aluno.ver` | P | P | P |
| `aluno.gerir` | E | P | P |
| `aluno.importar`, `aluno.desativar` | E | P | — |
| `usuario.ver` | P | — | — |
| `usuario.gerir` | E | — | — |
| `usuario.permissoes` | — | — | — |
| `prova.ver`, `gabarito.ver` | P | — | — |
| `prova.criar`, `prova.editar`, `gabarito.editar` | E | — | — |
| `cartao.imprimir`, `cartao.enviar` | E | — | — |
| `correcao.acompanhar` | P | — | — |
| `correcao.alterar`, `correcao.fechar`, `correcao.reabrir` | E | — | — |
| `resultado.ver` | P | — | — |
| `resultado.compartilhar` | E | — | — |
| `relatorio.prova`, `relatorio.turma`, `relatorio.escola` | P | — | — |
| `prova.duplicar_propria` | — | — | — |
| `pedido.exportacao.autorizar` | — | — | — |
| `copia.autorizar_origem`, `copia.autorizar_destino` | — | — | — |
| `escola.criar`, `escola.desativar`, `nucleo.ver`, `nucleo.gerir`, `rede.configurar`, `rede.logo` | — | — | — |
| `relatorio.nucleo`, `relatorio.rede`, `relatorio.secretaria` | — | — | — |
| `catalogo.manter_escopo`, `catalogo.consultar`, `catalogo.referencia_pessoal.gerir`, `catalogo.solicitar` | — | — | — |

P em `aluno.importar` do Secretário escolar descreve o catálogo existente: **o importador XLSX não está implementado**. Não anunciar importação na interface nem criar botão funcional por conta desta matriz. `turma.vincular_professor` não antecipa a lotação detalhada por disciplina de DC1.

## Quem gere os vínculos

| Gestor | Vice-diretor | Secretário escolar | Auxiliar | Alcance |
|---|:---:|:---:|:---:|---|
| Administrador da rede | Sim | Sim, existente | Sim | Instituições da própria rede |
| Diretor de núcleo | Sim | Sim, existente | Sim | Instituições do próprio núcleo |
| Diretor escolar | Sim | Sim, existente | Sim | Própria escola |
| Vice-diretor com `usuario.gerir` concedida | Não | Sim | Sim | Própria escola; também Coordenador, Professor, Aplicador e Aluno |
| Coordenador | Não | Não | Não | Mantém sua política pedagógica atual |
| Secretário e Auxiliar | Não | Não | Não | Não administram contas ou vínculos |

Essas regras de gestão são propostas adicionais, não a hierarquia já implementada. Gestão do vínculo não equivale a conceder permissões: Vice-diretor **não** recebe `usuario.permissoes`. Somente um gestor que tenha essa capacidade, a ação pretendida e autoridade sobre o perfil pode conceder extras dentro do mesmo alcance, com registro de concedente, motivo, vigência e retirada. Ninguém gere o próprio vínculo, pares/superiores ou outros vínculos da mesma pessoa para obter escalada indireta. Não usar a antiga atribuição global de perfil como atalho.

## Autoria e vínculo docente

Se autorizado a criar provas, Vice-diretor grava `perfil_criacao=vice_dir_escolar`; a prova é institucional. Como Professor, a mesma pessoa só consulta essa prova se houver turma atribuída, não a copia nem a edita. A criação como Professor permanece uma entrada docente, com o contrato de autoria da entrega132. Não converter registros antigos nem presumir que o cargo atual identifica a função usada na criação histórica.

Ao implementar, centralizar a classificação das funções institucionais, hoje repetida em Escopo e DuplicarProva, para que a vice-direção não fique fora de uma das verificações. Manter a origem/autoria das cópias e revisar o consumidor ExecutaMaterialCopia. A alteração não concede cópia de colega ao coordenador (CP2) nem transferência geral.

Secretário e Auxiliar podem ter outro vínculo Professor. As respostas administrativas não devem conter provas, notas, médias, resultados, contadores de desempenho ou gabaritos; trocar de vínculo não reaproveita resposta/cache do acesso anterior. Cadastro de aluno não cria automaticamente conta, matrícula histórica ou cessão entre instituições ainda não suportadas.

## Roteiro de implementação e fechamento

| Subdivisão interna PF1-C | Trabalho | Aceite |
|---|---|---|
| C1 — catálogo/contexto | Política independente para os novos perfis, compatibilidade escolar, migração aditiva e sincronização idempotente | Sem promover contas reais; perfil global sozinho não concede acesso; política do Secretário permanece igual. |
| C2 — gestão/convites | Hierarquia no servidor, validadores, vigência, concessões e projeções administrativas | Sem autogestão/escalada, sem alterar credenciais nem outros vínculos; convite só funciona após aceite e vigência. |
| C3 — consumidores | Escopo, autoria, prova/gabarito, relatórios, arquivos e filas; classificador institucional único | Escola inteira apenas para Vice-diretor; proibições dos administrativos aplicadas na API, não só nos botões. |
| C4 — interface | Rótulos, filtros de equipe, seleção, Meu espaço, formulários e estados vazios | Usar layout e componentes atuais; aprovação independente UI/UX com evidência desktop/celular. |
| C5 — encerramento | Testes direcionados/CI, ensaio de migração, backup e deploy na homologação | Conteúdo acadêmico anterior idêntico; sem produção; tabela atualizada. Não publicar C1–C4 isoladamente. |

Testes obrigatórios: funções diferentes da mesma pessoa na mesma escola; vínculos em escolas distintas; leitura e mutação direta por ID; provas institucionais por turma; dados administrativos sem desempenho; extras negados/retirados/vencidos; convite recusado/futuro; revogação; arquivos/relatórios/exportações; fila revalidando o ator; conta existente sem mudar identidade; migração com os dados e gabaritos anteriores preservados. Os testes do Secretário escolar existentes são regressão obrigatória, não evidência de que os dois novos perfis já funcionam.

## Pendências classificadas

| Item | Situação |
|---|---|
| Correção132 na homologação | Concluída; não reabrir nem publicar produção para implementar este plano. |
| PF1-A — inventário | Concluído no doc133. |
| PF1-B — matriz e critérios | Aprovada pelo responsável em03/11/2026. |
| PF1-C — novos perfis | Implementação autorizada; acompanhamento no doc135. |
| Importação XLSX | Funcionalidade futura do módulo de alunos; não bloqueia a definição de perfis. |
| Portal de responsáveis / Aluno | Consumidores e relacionamentos futuros; cadastro de perfil não prova entrega de portal. |
| Outros perfis do doc133 | Backlog com dependências; não ativar todos de uma vez nem ampliar esta entrega implicitamente. |
| ID1/VI1/DC1 e demais etapas115 | Sequência estrutural preservada; implantação dos prioritários deve ser explicitamente priorizada sem fingir que conclui identidade/lotação. |

O único gate de produto para iniciar PF1-C é a aprovação desta matriz, incluindo o padrão de acompanhamento do Vice-diretor e as operações extras mediante concessão. As subdivisões C1–C5 são trabalho interno e não exigem novas autorizações de continuidade. Uma eventual promoção para produção permanece uma decisão separada.
