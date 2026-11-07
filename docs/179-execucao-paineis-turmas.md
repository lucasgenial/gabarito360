# 179 — Execução dos painéis de turmas

07/11/2026. Plano canônico deste escopo: [177](177-plano-paineis-turmas.md). Protocolo autorizado: [178](178-protocolo-execucao-paineis.md). Os identificadores P0–P8 são locais ao doc177; não renumeram os pacotes de portal do doc28.

## Execução autônoma

Executar tarefas elegíveis sem pedir autorização para iniciar, continuar, fazer Git, integrar versões aprovadas ou publicar incrementos coerentes na homologação. Produção permanece excluída. Revisão especializada prévia e aprovação independente da versão final são obrigatórias; implementador não aprova sozinho. Falta de acesso ou decisão bloqueia apenas a dependência pertinente. Não reduzir testes, ampliar permissões ou simular dados para obter aprovação.

Registrar visual, funcional, testes, revisão, commit/PR, publicação e conferência autenticada separadamente. Modelo “Em breve” concluído visualmente não conclui o módulo. Não usar skeleton permanente, números/pessoas fictícias como reais nem chamadas para endpoints inexistentes. Preservar as entregas atuais e todos os itens do quadro176, inclusive fora destes painéis.

## P0 — referência e trabalhos preservados

- Main remoto conferido: `c16ceca5342b2313141d690a862ef488400b02bf` (PR209). Worktree novo `paineis-turmas`, branch `pacote/paineis-p0-p1`, sem carregar alterações de outras tarefas.
- Último recibo conhecido da homologação: `adb9698`, árvore do PR209; nova publicação exige reconfirmar o destino e sua release. Nenhuma evidência de produção é deduzida do merge.
- TU1-PDF permanece em `tu1-pdf-relatorio`, branch `feat/tu1-pdf-relatorio`, HEAD `b5cd6e0`, com teste local modificado. P8.1 reaproveita esse trabalho; não copiar o gerador antigo sobre ele.
- Imagem na edição IMG-E integrada no PR209; imagem no detalhe/perfil PR208. IMG-N/L/P continuam distintos. P3.1.3 amplia IMG-L; cadastro de turma e professor não são silenciosamente concluídos por isso.
- Agentes consultados: Product Design, indicadores e autorização. Pareceres são evidências específicas, não aprovação automática dos futuros incrementos.

## Matriz reconciliada

| Origem | Existente/lacuna | Tratamento e destino | Dependência e conclusão |
|---|---|---|---|
| TU1/175/R3 | Abas, seis KPIs, três análises e grupos nominais | Reaproveitar P2.3/P3.2 | Mesmo recorte/base/permissão; preservar ações e imagem209 |
| Listagem Turmas | Tabela; média bruta e KPIs fora do filtro | Ampliar P2.2/P3.1/P3.3 | Contagens com universo declarado; não usar média bruta como ganho |
| PF1/134/135 | Permissões/contexto existentes; rota mista precisa revisão por conteúdo | Ampliar P1.3/P8.2 | Negação administrativa e retirada de permissão testadas no servidor |
| DC1/148 | Áreas N:N e lotação temporal | Reaproveitar P1.2; desdobrar professor autor/responsável | Não renomear filtro legado sem contrato histórico novo |
| UI1/174/UI1-R/AP1 | Casca e estados regionais; cobertura parcial | Ampliar P3.4 | Medição antes/depois e falha regional; AP1-L mantém item próprio |
| C1–C4/N1–N3/doc28 | Agenda/notificações não operacionais | Ampliar P5, visual P2 | Modelos sem endpoints até fonte; público administrativo separado |
| MT1/doc115 | Transferência intraescola; intervalos/elegibilidade incompletos | Ampliar P1.4.6/P3.3.5/P4.4 | Captura histórica de novos conjuntos; não inferir presença de cartão |
| Período letivo | Não localizado como módulo completo; vínculo DC1 não é bimestre | Criar especificação P4; verificar reserva PL1 antes de adotá-la | Calendário por turma, histórico e datas sem conversão presumida |
| TU1 acompanhamento | Recuperação/Top5 não implementam intervenções | Ampliar P6 em subplano ligado TU1 | Evidência, ação, responsável e retorno; revisão humana |
| GII/editor/TRI/integridade | Reservas de produto, motores não comprovados | Desdobrar apenas interfaces/contratos P7 | Não construir motores implicitamente nem alegar equivalência de provas |
| TU1-PDF | WIP já preservado e não publicado | Reaproveitar/ampliar P8.1 | Mesmos dados, todas linhas, retrato/paisagem, sem modelos no PDF |
| EI1/163 | Escola autônoma planejada | Reaproveitar dependência condicional | Não exigir EI1 para painéis de espaços já autorizados |

Nenhum item anterior é apagado ou substituído nesta reconciliação. P0 é uma base executável, ampliada pontualmente ao encontrar fontes adicionais.

## Achados que afetam o primeiro incremento

1. Média da listagem usa `AVG(nota_final)` e apresentação em escala fixa; não equivale ao percentual válido do TU1-R3. Não ativar comparação agregada com essa fonte.
2. Totais `kpis` da listagem não recebem todos os filtros. Usar somente contagens cuja coleção/universo esteja explícito; estudantes únicos não são soma de matrículas.
3. Projeção administrativa existente não basta: usuário não administrativo com permissão pedagógica retirada precisa ter os dados correspondentes omitidos também na API. Primeiro incremento de P1 tratará essa revisão.

## Quadro de continuidade

| Fase | Visual | Funcional | Revisão/publicação | Próximo critério |
|---|---|---|---|---|
| P0 | Não aplicável | Base e conciliação registradas | Em revisão independente | Relacionar pareceres e preservar fontes |
| P1 | Catálogo em preparação | Projeção por conteúdo em implementação | Não publicado | Testes de permissão e contratos compatíveis |
| P2 | Préconsulta solicitada | Modelos não equivalem a função | Não implementado/publicado | Composições por perfil e estados revisados |
| P3 | Reaproveita TU1-R3 | Dados atuais parciais | R3 já publicado; novas integrações pendentes | Ativar somente fontes válidas |
| P4 | Modelos previstos em P2 | Pendente | Não publicado | Calendário, períodos, elegibilidade/presença |
| P5 | Modelos previstos em P2 | C/N pendentes | Não publicado | Eventos e destinatários corretos |
| P6 | Modelos previstos em P2 | Pendente | Não publicado | Habilidades/intervenções com evidências |
| P7 | Modelos previstos em P2 | Integrações futuras | Não publicado | Aceite individual de cada integração |
| P8 | PDF WIP preservado | Verificação transversal em curso | Conferência autenticada pendente | Validar cada incremento e publicar só homologação |

Checkpoint: próximo trabalho elegível é concluir a projeção P1 com testes negativos e revisão independente, junto ao catálogo para P2. Não há nova publicação autorizada em produção. Os itens externos do quadro176 permanecem pendentes.
