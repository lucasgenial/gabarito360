# 57 — V1-C1: núcleo de autorização por vínculo

Data: 01/11/2026. Base: `7a13ffe`, merge autorizado do PR #88. Continuação de [V1-B](56-v1-conversao-conciliada.md).
Atualização: V1-C1 incorporado no PR #89 em `298c60f`. A primeira integração de consumidores está em [V1-C2a](58-v1-fila-e-consultas-contextuais.md), ainda sem ativação.

## Entrega

O núcleo aceita contexto explícito por `Usuario::noVinculo(id)`. Retorna outra instância da mesma conta, com identidade preservada e perfil calculado pelo vínculo. Não altera `usuarios.perfil`, a instância original, outros contextos ou um estado global de sessão. O ID do vínculo não é um atributo preenchível pela entrada de usuário.

`ContextoVinculo` revalida pessoa titular, conta ativa, vínculo ativo, início inclusivo e fim exclusivo, perfil compatível com o tipo de espaço, cliente ativo, referências institucionais coerentes e hierarquia ativa. Vínculo alheio, inexistente, convidado, suspenso, encerrado, futuro ou vencido é recusado sem retornar ao acesso legado.

Com contexto explícito:

- `Permissao` usa apenas perfil e ajustes do vínculo. Não usa o cache legado por pessoa, não importa ajustes da conta ou de outro vínculo e revalida concessões, retiradas e validade. Concessão fora das extras do catálogo não cria poder.
- `Escopo` limita escolas ao espaço institucional e descendentes; professor mantém as restrições de turma e de autoria existentes, dentro desse espaço. Ser autor de uma prova na escola A não permite lê-la pelo vínculo da escola B.
- `Instituicoes` deriva cabeçalhos e referências somente desse contexto, inclusive para quem administra duas redes.
- Aluno pode resolver somente os registros explicitamente atribuídos em `vinculo_alunos`. Não recebe todos os colegas ou toda a turma. Consulta geral de provas permanece negada: a integração do consumidor de resultado individual ainda será feita.
- `ContextoCorrecao::versaoAcesso` distingue vínculo, versão, alcance, restrições e permissões efetivas. Links assinados gerados com esse contexto carregam o vínculo na assinatura e o revalidam na abertura. Alteração do parâmetro, incremento da versão ou suspensão invalida o acesso.

`fresh()` conserva o vínculo e o revalida. `refresh()` mantém o ID contextual. Serialização implícita de um `Usuario` contextual por `SerializesModels` é recusada, porque o mecanismo transportaria apenas a identidade e perderia o vínculo. Consumidores assíncronos precisarão transportar IDs explícitos e reconstituir o contexto validado.

## Limites de integração

**V1-C ainda não está concluído e não está pronto para ativação.** Este PR não adiciona seleção por header, payload, sessão, token ou configuração. O código atual de login e as requisições normais continuam sem contexto explícito e preservam o comportamento T2. Não conectar `noVinculo()` ao usuário autenticado antes de adaptar os consumidores abaixo. Não há novo deploy ou migration no servidor.

Os testes do núcleo não provam isolamento de todas as rotas. O mapeamento encontrou consumidores que ainda usam `usuario_escopos`, perfil global ou concessões da conta diretamente:

| Área | Pontos que faltam adaptar/revisar |
|---|---|
| Identidade e gestão | `UsuarioController`, `VinculoProfessorController`, `GestaoLegada`, `ContaUsuario`: diferenciar pessoa e vínculo alvo, impedir alteração de todos os vínculos ao editar um contexto |
| Consultas diretas | `DashboardController`, `EscolaController`, `CensoController`, `RelatorioConsolidadoController`: retirar referências diretas ao escopo legado do ator e adaptar projeções dos vínculos de terceiros |
| Resultado do aluno | Consumidor individual e PDFs, sem liberar consulta geral à turma/prova |
| B6 | `FilaCartoes`, `ProcessarCartaoJob`, `ResultadoEnvio` e polling/retentativa: persistir vínculo no recibo, particionar idempotência, transportá-lo ao job e revalidar antes/depois da leitura OMR |
| Autenticação e web | Resolver contexto confiável, menus/perfil/permissões coerentes; entrada sem contexto não pode retornar silenciosamente ao modelo legado após a futura ativação |
| Ativação | Conferir vínculos contra o manifesto e fontes atuais, tratar contas adiadas, alterações posteriores e conciliação final; preparar corte sem sobrescrever vínculos revogados |

Uma consulta ou objeto já materializado não deve ser reutilizado entre requisições ou execuções de jobs. A revalidação ocorre ao chamar o núcleo; não transforma resultados antigos em dados revogáveis. Antes da ativação, medir consultas/latência no ambiente equivalente; esta entrega prioriza revalidação sem cache persistente de autorização.

## Validação e continuidade

`ContextoVinculoTest` cobre diretora em A/professora em B, alternância de contextos após preencher o cache legado, duas redes, restrições de turma/aluno, vigência, perfil/espaço incompatível, suspensão da conta/vínculo/hierarquia/cliente, ajustes isolados, versão de acesso, recarga da conta e links assinados. Os testes originais `PerfisTest` permanecem sem edição; a conversão V1-B também continua coberta.

Próximas entregas do V1-C:

1. **V1-C2:** integrar consumidores, gestão e B6, com testes de rotas, fila, revogação durante processamento e ausência de exposição entre vínculos.
2. **V1-C3:** conciliação final e mecanismo de ativação sem fallback legado, com evidências de cobertura e regressão. Avisar o responsável quando houver release pronta para ensaio/publicação; nenhum deploy implícito.

A experiência de escolha/troca por aba continua no V2 conforme T1. Não impor novo prazo máximo de token; sessão web permanece com a decisão de 120 minutos de inatividade.
