# Gabarito360 - Dashboards e Relatorios

## 1. Objetivo

Disponibilizar acompanhamento operacional durante as aplicacoes e analise consolidada apos a correcao, sempre respeitando o escopo de acesso e a privacidade dos alunos.

## 2. Principios

- Indicadores operacionais devem priorizar clareza e acao.
- Dados em tempo real devem ser identificados como provisarios enquanto a aplicacao estiver aberta.
- Somente resultados vigentes entram nos calculos.
- Filtros e criterios de ranking devem ser visiveis.
- Dados pessoais devem aparecer apenas quando necessarios e autorizados.
- Exportacoes devem ser auditadas.

## 3. Definicoes de indicadores

| Indicador | Formula inicial |
|---|---|
| Alunos previstos | Quantidade de `aplicacao_alunos` no escopo |
| Cartoes lidos | Alunos com cartao confirmado e resultado vigente |
| Alunos pendentes | Previstos sem cartao confirmado e nao marcados como ausentes |
| Percentual concluido | Cartoes lidos / alunos previstos x 100 |
| Media da aplicacao | Media das notas dos resultados vigentes |
| Taxa de acerto da questao | Respostas corretas / respostas validas consideradas |
| Taxa de revisao manual | Leituras confirmadas com ao menos uma alteracao manual / leituras confirmadas |
| Taxa de alerta | Leituras confirmadas com branco, dupla ou baixa confianca / leituras confirmadas |

Divisao por zero deve resultar em indicador indisponivel, nao em valor enganoso.

## 4. Dashboard do Nucleo

### 4.1 Objetivo

Oferecer visao consolidada de todas as escolas vinculadas ao nucleo.

### 4.2 Filtros

- Avaliacao.
- Periodo.
- Escola.
- Municipio.
- Status da aplicacao.
- Serie/nivel.

### 4.3 Cards

- Escolas participantes.
- Turmas participantes.
- Alunos previstos.
- Cartoes lidos.
- Percentual concluido.
- Aplicacoes em andamento.
- Aplicacoes pendentes.
- Leituras com alerta.

### 4.4 Graficos e tabelas

- Progresso por escola.
- Media geral por escola.
- Ranking de escolas, com criterio e empate documentados.
- Desempenho por questao.
- Questoes com maior indice de erro.
- Lista ou mapa de status por escola.
- Evolucao de leituras ao longo do tempo.

### 4.5 Acoes

- Abrir detalhe da escola.
- Abrir aplicacao em andamento.
- Gerar relatorio consolidado.
- Exportar dados autorizados.

## 5. Dashboard da Escola

### 5.1 Objetivo

Acompanhar turmas e aplicacoes da escola.

### 5.2 Cards

- Turmas participantes.
- Alunos previstos.
- Cartoes lidos.
- Alunos pendentes e ausentes.
- Media geral.
- Aplicacoes em andamento.
- Leituras com alerta.

### 5.3 Graficos e tabelas

- Progresso por turma.
- Media por turma.
- Desempenho por questao.
- Ranking de alunos quando autorizado.
- Aplicacoes recentes.
- Alunos pendentes.
- Leituras com inconsistencias.

### 5.4 Acoes

- Abrir turma ou aplicacao.
- Consultar aluno autorizado.
- Gerar relatorio da escola/turma.
- Exportar resultados.

## 6. Dashboard do Professor/Aplicador

### 6.1 Objetivo

Dar suporte operacional a aplicacao ativa.

### 6.2 Cards

- Alunos previstos.
- Alunos lidos.
- Alunos pendentes.
- Ausentes.
- Leituras com alerta.
- Sincronizacoes pendentes.

### 6.3 Listas

- Ultimas leituras.
- Alunos pendentes.
- Leituras com baixa confianca ou correcao manual.
- Questoes com maior erro na turma, quando permitido.

### 6.4 Acoes

- Nova leitura.
- Abrir aluno pendente.
- Consultar historico.
- Ver status de sincronizacao.
- Finalizar aplicacao quando autorizado.

## 7. Dashboard da Aplicacao em Tempo Real

### 7.1 Eventos

- Aplicacao iniciada.
- Leitura confirmada.
- Aluno atualizado.
- Leitura cancelada/substituida.
- Alerta operacional criado.
- Aplicacao finalizada/reaberta.

### 7.2 Comportamento

- Carregar um snapshot inicial via API.
- Assinar canal WebSocket autorizado.
- Aplicar eventos incrementais.
- Recarregar snapshot quando houver perda de sequencia ou reconexao.
- Exibir horario da ultima atualizacao.
- Continuar funcional sem WebSocket, usando atualizacao periodica como contingencia.

## 8. Relatorios

| Relatorio | Conteudo principal | Perfis |
|---|---|---|
| Individual do aluno | Resultado, respostas, acertos e comparativos autorizados | Escola, nucleo e perfis concedidos |
| Turma | Progresso, resultados, media, distribuicao e desempenho por questao | Escola, nucleo, professor vinculado |
| Escola | Consolidado de turmas e aplicacoes | Escola e nucleo |
| Nucleo | Consolidado e comparativo entre escolas | Gestor do nucleo |
| Alunos pendentes | Alunos sem leitura valida ou ausentes | Aplicador e gestores |
| Cartoes lidos | Codigo impresso quando houver, codigo do sistema quando utilizado, aluno autorizado, horario, aplicador e status | Gestores autorizados |
| Inconsistencias | Baixa confianca, duplas, correcoes e conflitos | Gestores/suporte autorizado |
| Desempenho por questao | Acertos, erros, brancos e distribuicao de alternativas | Perfis pedagogicos autorizados |
| Auditoria operacional | Alteracoes manuais, reprocessamentos e cancelamentos | Perfis autorizados |

## 9. Exportacoes

### 9.1 Formatos

- CSV no MVP.
- PDF e XLSX na V2.
- JSON apenas para integracoes autorizadas.

### 9.2 Fluxo

1. Usuario define relatorio, filtros e formato.
2. Backend valida permissao e escopo.
3. Geracao curta retorna diretamente; geracao longa cria job em fila.
4. Usuario acompanha o estado do job.
5. Download e disponibilizado por URL temporaria.
6. Solicitacao e download sao auditados.

### 9.3 Estados

`solicitado`, `processando`, `concluido`, `falhou`, `expirado`.

## 10. Filtros e segmentacoes

- Nucleo, escola e turma.
- Avaliacao e aplicacao.
- Serie/nivel e ano letivo.
- Periodo.
- Status de leitura/aplicacao.
- Aluno, quando autorizado.
- Aplicador, para analise operacional autorizada.
- Questao e tipo de deteccao.

Filtros devem ser validados no backend e limitados ao escopo do usuario.

## 11. Permissoes e privacidade

- Gestor do nucleo acessa agregados e detalhes autorizados de suas escolas.
- Escola acessa apenas seus dados.
- Professor/aplicador acessa apenas turmas e aplicacoes vinculadas.
- Leitor/Consulta recebe escopo explicitamente concedido.
- Suporte acessa diagnosticos, preferencialmente anonimizados.
- Ranking individual deve ser configuravel e exibido apenas com finalidade definida.
- Exportacoes com dados pessoais exigem permissao especifica.

## 12. Desempenho e consistencia

- Indicadores em tempo real podem usar contadores atualizados por eventos.
- Resultados analiticos podem usar consultas agregadas, cache ou visoes materializadas na V2.
- Eventos devem ser publicados somente apos commit da confirmacao.
- Recalculo deve invalidar caches afetados.
- Dashboard deve informar quando dados ainda estao sendo processados.

## 13. Criterios de aceite do MVP

- Dashboard da aplicacao apresenta previstos, lidos, pendentes e percentual.
- Confirmacao valida atualiza o dashboard em ate 5 segundos na condicao homologada.
- Usuario nao acessa dashboard fora de seu escopo.
- Totais consideram somente resultados vigentes.
- Filtros preservam coerencia entre cards e listas.
- Relatorio basico por turma pode ser exportado em CSV.
- Geracao e download ficam registrados para auditoria.
