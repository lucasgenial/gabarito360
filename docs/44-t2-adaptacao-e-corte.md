# 44 — T2-D/E: adaptação dos consumidores e corte

Base: PR #75 incorporado em `7289fe1917bfceab7ab78c324e66c2b17602f8ea`. O responsável autorizou seguir T2-D/E, **sem deploy**. Implementação derivada de [T1 aprovado](40-clientes-espacos-e-vinculos.md), do [mapa de consumidores](41-t1-mapa-de-consultas.md) e do [manifesto aprovado](42-t2-inventario-e-manifesto.md).

## Entrega em revisão: T2-D1

Primeira parte de D: adaptador de escopo legado e consumidores de alunos, turmas e provas. Não representa conclusão de D ou E. Não executa migração, backfill, alteração de acesso em produção ou criação de vínculos.

- `Espacos` deriva espaços escolares da hierarquia institucional existente. Todas as referências precisam ter tipo compatível, cliente coerente e situação ativa. O pagamento não concede acesso.
- No modo novo, administrador recebe somente redes explicitamente registradas em `usuario_escopos`. Ausência de atribuição resulta em lista vazia. Não há fallback para todas as redes nem atribuição por ID fixo de usuário.
- A autorização aprovada para o administrador das duas redes continua registrada no manifesto. Sua conversão operacional precisa identificar a conta correta e registrar separadamente as duas atribuições; D1 não executa essa conversão. Os vínculos definitivos pertencem a V1.
- Professor continua limitado às turmas atribuídas e às provas permitidas por autoria/associação. Suspensão de escola, núcleo ou rede bloqueia o adaptador sem cache persistente de autorização.
- Alunos, turmas e provas consultam o espaço e o escopo institucional antes de resolver o ID. Registros com espaço nulo, incompatível com a escola/turma ou fora do acesso são recusados. Contagens e associações exibidas nesses controladores conferem o espaço.
- As criações desses registros e de `prova_turmas` calculam propriedade e autoria no servidor. Campos enviados pelo cliente não escolhem dono nem autor. Criação de prova e associações ocorre em transação.
- Correções válidas já no regime legado: criação/edição/ativação de aluno ou turma respeitam escopo; mudança de escola de turma ou de escola de aluno por edição simples é recusada. Transferência precisa do procedimento T5.

## Ativação bloqueada nesta entrega

`config/espacos.php` mantém `ativo = false`, sem variável de ambiente. Somente testes ativam o adaptador explicitamente. Não editar essa chave em produção nem anunciar isolamento completo. O modo novo ainda não cobre todos os consumidores do mapa; habilitá-lo isoladamente produziria uma mistura de regras e escritas incompletas.

No regime legado, a unicidade de matrícula/CPF permanece global, acompanhando as constraints atuais. A mudança conjunta de validações, importadores e índices por espaço pertence ao corte D/E; D1 não promete aceitar duplicidades entre instituições.

## Sequência restante

| Parte | Trabalho necessário antes do corte |
|---|---|
| D2 — instituições e gestão | Escolas/núcleos, configuração e logos sem `redes->first()`, usuários/permissões e vínculos legados; conciliação explícita do administrador das duas redes; nenhuma concessão global implícita |
| D3 — conteúdo e processamento | Gabaritos, cartões/respostas/notas/ambiguidade, fila B6, resultados, idempotência/locks/arquivos/URLs assinadas; revalidar contexto e suspensão em workers e retentativas |
| D4 — consultas e integrações | Painéis, relatórios/consolidados, acompanhamento, imports CLI e sincronização; contextos explícitos e exceções globais justificadas; revisar modelos e demais consumidores do mapa |
| E — integridade e corte | Unicidade de matrícula/CPF por espaço em todos os consumidores; validação prévia de cobertura/autoria/relações; NOT NULL e FKs simples/compostas; exclusividade das referências de espaço; ensaio MariaDB, duração do DDL e retomada |
| Publicação | PRs aprovados, backup/restauração, manutenção e fila drenada, manifesto atualizado, preenchimento final e comparação das contagens; aprovação operacional específica. Não autorizada nesta entrega |

V1/V2 continuam responsáveis por múltiplos perfis, permissões por vínculo e isolamento por aba. A sessão web mantém 120 minutos de inatividade; não é introduzida validade máxima de oito horas para tokens API.

## Validação e limitações

Testes de integração usam duas redes e um mesmo cliente fictício para provar que pagador não amplia acesso. Cobrem ausência/adição/remoção de atribuição, suspensão nos três níveis, IDs alheios em leitura/escrita/ativação, metadados forjados, relações divergentes, proteção das contagens, autoria calculada e impossibilidade de transferência por edição. Regressões do regime legado permanecem necessárias.

Os testes de D1 não certificam os consumidores D2–D4 nem as constraints de E. Resultados finais ficam registrados no PR. Nenhum dado real é usado nos testes.
