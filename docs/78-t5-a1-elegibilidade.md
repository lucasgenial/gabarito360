# 78 — T5-A1: elegibilidade dos autorizadores

O planejamento de docs/77 foi aprovado e incorporado pelo PR #111. Revisão antes da implementação: o AU1 atual exige campos HTTP; a autorização de cópia não pode usar esse formato como prova de execução em fila. Dividir T5-A para revisar separadamente essa mudança de contrato.

| Etapa | Entrega | Limite |
|---|---|---|
| T5-A1 (este pacote) | Capacidades explícitas e avaliação dos dois contextos autorizadores, com revalidação de vínculo, hierarquia e permissões | Somente elegibilidade; não concede consentimento, não cria lote nem copia dados |
| T5-A2 | Autorizações persistidas vinculadas à seleção, finalidade, validade e versão; lotes, itens, mapa e idempotência | Validar constraints e concorrência em MariaDB; revalidar no commit |
| T5-A3 | Outbox transacional, contrato versionado de evento de domínio, consumidor idempotente e recuperação | Preservar compatibilidade dos arquivos AU1; ensaiar Redis/auditoria indisponíveis |

T5-A só estará completo após A2/A3. T5-B depende dessa conclusão.

## Contrato deste incremento

- `copias.ativo` é falso por padrão. Nenhuma rota, job ou botão é adicionado.
- `copia.autorizar_origem` e `copia.autorizar_destino` são capacidades padrão de `admin_rede`, `dir_escolar` e proprietário `professor_autonomo`; retiradas por vínculo continuam eficazes. Outros perfis não recebem extras.
- O avaliador exige contexto explícito em cada lado, inclusive quando a pessoa é a mesma. Não usa perfil global nem soma vínculos. Contexto interno não autoriza.
- Os espaços que recebem/fornecem conteúdo nesta primeira versão são escola e pessoal. Rede é contexto de gestão, não destino de conteúdo. Administrador pode autorizar escola ativa na própria rede; diretor, somente sua escola; autônomo, somente seu espaço pessoal.
- Origem igual ao destino, espaço suspenso, cliente suspenso, vínculo expirado/revogado e permissão retirada bloqueiam a avaliação. O retorno contém apenas referências técnicas derivadas no servidor e versão do acesso.
- O resultado é uma fotografia de elegibilidade, **não uma autorização persistida ou um recibo reutilizável**. A2 deve vincular decisões à seleção e revalidar sob locks no commit. Este avaliador sozinho não resolve concorrência com revogação.
- Professor escolar poderá solicitar seleção limitada por turma/autoria em etapa posterior; solicitar nunca equivale a aprovar exportação. Evidência contratual, conteúdo identificado e procedimentos T6 continuam pendentes/bloqueados.

## Validação

Testes de integração cobrem separação dos dois contextos da mesma conta, hierarquia, matriz de perfis, retirada de capacidade, revogação após avaliação, validade, suspensão e flag. Incluir o teste no job MariaDB, além da suíte SQLite. A migration apenas sincroniza catálogo na futura publicação; não foi executada no servidor.
