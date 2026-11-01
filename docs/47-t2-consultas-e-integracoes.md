# 47 — T2-D4: consultas e integrações

Base: PR #78 incorporado em `56457f8668a82d883e04dc29eb1a191166b453c6`. O responsável autorizou seguir T2-D4, **sem deploy**. Deriva do [T1 aprovado](40-clientes-espacos-e-vinculos.md), do [mapa de consumidores](41-t1-mapa-de-consultas.md) e do [roteiro D/E](44-t2-adaptacao-e-corte.md).

## Entrega em revisão — PR #79

`ConsultasPedagogicas` recebe o ator explicitamente e limita as consultas antes de contagens, médias, rankings e projeções. Não usa usuário global, estado estático de contexto ou identidade do pagador. O adaptador continua desligado fora dos testes.

- Painéis dos oito perfis, relatórios por prova/turma/aluno, consolidados de escola/núcleo/rede e acompanhamento conferem hierarquia ativa e relações entre espaço, prova, turma, aluno, cartão, resposta e nota. Joins de tabelas pedagógicas recebem conjuntos já filtrados. Agregados das listagens de provas/turmas usam a mesma restrição.
- Professor continua limitado às turmas atribuídas, inclusive quando a prova reúne várias turmas ou foi criada por ele. Aluno recebe somente o registro explicitamente vinculado e as próprias notas. Resultado individual preparado em D3 permanece limitado ao consumidor autorizado.
- Administrador só agrega redes explicitamente atribuídas; ausência/suspensão não abre consulta global. Secretaria precisa da atribuição de secretaria **e de cada rede**, coerente com a relação institucional; não recebe redes pelo contrato ou por ser titular. O procedimento V1 deve conciliar esses vínculos, sem criá-los automaticamente neste pacote.
- Visitas conferem escola, núcleo e espaço. Indicadores SEGES só consideram execução com espaço de destino válido e autorizado; registros sem destino não entram por fallback. O inventário continua bloqueando o corte enquanto houver sincronizações sem destino conciliado.
- Comparativo de secretaria calcula médias independentemente das contagens de alunos, evitando multiplicação das notas por joins. Limiares pedagógicos existentes não foram redesenhados nesta etapa.

## Contexto da web

Configuração lista redes autorizadas e exige escolha quando necessário; GET e formulário de alteração transportam `rede_id`. Painéis de instituição/aluno com várias referências oferecem escolha e transportam o ID na URL. Nenhuma seleção é salva globalmente na sessão. IDs de provas, turmas, escolas, núcleos e redes nos demais links/downloads continuam sendo reautorizados pela API.

Esse encaminhamento resolve a ambiguidade do adaptador legado; **não substitui V1/V2**, que farão perfis e permissões por vínculo, token contextual e contexto completo por aba. Mantidos os 120 minutos de inatividade da sessão web, sem limite novo de oito horas da API.

## Importações e operações

No modo novo, CSV de alunos e JSON de provas exigem `--usuario=ID --espaco=ID`, além da escola já exigida. Conferem conta ativa, permissões e destino institucional; não escolhem administrador por padrão. Por padrão apenas validam/simulam; `--aplicar` grava em transação e revalida o contexto. Novos registros e filhos recebem espaço/autoria do servidor; atualizações preservam a autoria original.

CSV exige cabeçalho e linhas válidos, recusa duplicatas no arquivo e não transfere aluno de outra escola por matrícula. O índice global de matrícula ainda existe até E: colisão externa é recusa genérica, nunca atualização ou deduplicação entre instituições. E deve mudar conjuntamente índice, validações e consulta de colisão. Repetir o mesmo CSV no destino não duplica alunos. O importador JSON cria novas provas a cada aplicação explícita; não deve ser usado como retentativa automática.

Censo é catálogo público global administrado pelo operador CLI. No modo novo, importar o catálogo não atualiza escolas privadas automaticamente. Atualização institucional exige `--atualizar-escolas --usuario=ID --espaco=ID`, permissão de edição e destino autorizado; revalida antes de gravar a escola. Catálogo e atualização da escola são operações distintas: se o acesso mudar durante a importação, o catálogo pode estar atualizado e a alteração institucional é recusada com mensagem específica. `--dry-run` não grava nenhum dos dois.

Nenhum desses comandos foi executado em produção. Fixtures, seeders e ensaios não autorizam carga real.

## Revisão transversal e exceções

| Consumidor | Tratamento |
|---|---|
| Modelos e relações Eloquent | FKs/relacionamentos não são autorização. Nos consumidores D4, modelos entram por consulta explícita e relações são carregadas após validar o dono; joins de notas/cartões/turmas/provas conferem integridade. Sem global scope que dependa da última requisição de um worker. Constraints definitivas ficam em E |
| Cabeçalhos/PDFs/logos | Chamadores autorizam instituição/registro antes de obter cabeçalho ou arquivo. Não se cria endpoint global de cabeçalho; logo mantém autorização própria D2 |
| `CensoController`, `PlanoController`, `CatalogoPermissoes`, `ImportarCenso`, `SincronizarPermissoes` | Catálogos globais explícitos; operação CLI requer acesso operacional. Catálogo não concede acesso pedagógico. Sincronizar permissões mantém seu papel de catálogo e não cria vínculos |
| Auth, conta, convite, tokens e recuperação | Identidade global; gestão institucional transitória D2 preservada. Conversão de convites/permissões para vínculo pertence a V1/V2 |
| Assinaturas, pagamentos, webhook e job de inadimplência | Fluxo comercial existente, sem ativar cobrança ou conceder espaço. Migração comercial para cliente segue a fase financeira; não inferir autorização pedagógica dessas tabelas |
| Leads, Turnstile, e-mails, health, logs/falhas | Operação da plataforma. Health sem dados escolares; logs sanitizados L3 mantidos. Listeners não recebem contexto de requisição para reutilizar em outro job |
| Fila OMR, recibos, arquivos e chaves da câmera | Revalidação D3 preservada; nenhum worker novo ou sincronizador externo foi implantado |
| Seeders e scripts de carga | Não são caminho operacional do corte. Não executar em produção para preencher espaços; usar manifesto e comandos específicos revisados |

## Validação e próximo passo

Testes fictícios exercitam duas redes, suspensão, professor em prova compartilhada, aluno, secretaria, notas/respostas divergentes, visitas e sincronização, importação com contexto errado, simulação, autoria e seleção web. Inclui execução isolada em MariaDB e regressão de API/web. Resultados finais no PR; não certificam desempenho de produção. Índices/planos de consulta e restrições devem ser ensaiados em E/X1 com volume representativo.

Próximo passo: **T2-E — integridade e ensaio do corte**: unicidades por espaço em todos os consumidores, FKs simples/compostas, campos obrigatórios, exclusividade dos destinos, auditoria final de cobertura/autoria/relações e plano de manutenção/fila/retomada. Não ativar `espacos.ativo` nesta entrega. Não há migration nova, deploy, preenchimento real nem alteração de acessos em produção.
