# Painéis de turmas e editor de painéis

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve os dois painéis de turmas (agregado e individual), a organização da turma, as imagens de turma, o editor de painéis e o contrato de preferências, no estado final da branch atual. O plano de execução e os recibos estão nos `planejamento.md` de [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md), [FN-054](../funcionalidades/054-paineis-editor/planejamento.md) e [FN-036](../funcionalidades/036-organizacao-turma/planejamento.md). A interface em geral está em [Interface e design system](09-interface-e-design-system.md).

## 1. Visão geral

São **dois dashboards distintos**, com conteúdo e agregações próprios:

| Painel | Rota Web | Pergunta que responde |
|---|---|---|
| Turmas (agregado) | `turmas.index` | "Como estão as turmas sob minha responsabilidade e onde preciso agir?" |
| Turma (individual) | `turmas.show` | "O que acontece nesta turma e quais resultados, alunos ou conteúdos precisam de acompanhamento?" |

Decisões do responsável, vigentes:

- A relação de turmas abre em **Tabela**. **Cards** compactos são alternativa explícita. A escolha afeta só a relação de registros, não o dashboard inteiro.
- Cards e gráficos são a apresentação dos indicadores e análises. Um card grande por turma não conclui o dashboard.
- Recursos futuros aparecem como modelos completos, com selo **Em breve** e identificação **Dados demonstrativos** (seção 6).
- Cada widget tem título autoexplicativo e conteúdo. Sem subtítulo, sem parágrafo didático e sem "Saiba mais". Base de cálculo e critérios ficam em tooltip, ARIA ou leitor de tela.
- A personalização (mover e redimensionar) existe só em desktop e tablet grande. Abaixo disso, a composição é fixa.

## 2. Organização da turma (TU1)

A página da turma (`turmas.show`) tem cinco seções locais em abas. O servidor autoriza os dados. As abas são melhoria progressiva: sem JavaScript, os painéis continuam legíveis.

| Seção (`secao`) | Conteúdo | Condição |
|---|---|---|
| `visao-geral` | Resumo fixo e painel analítico (seção 4) | Sempre |
| `alunos` | Lista de alunos, transferência, cadastro | Permissão `aluno.ver` |
| `disciplinas` | Disciplinas e professores | Módulo de disciplinas visível (DC1) |
| `provas` | Provas da turma | Permissão `prova.ver` |
| `acesso` | Acesso à turma (professores vinculados) | Perfil não pessoal |

Regras:

- O endereço usa só o fragmento (`#alunos`), nunca identificadores. `history.state` da navegação UR4 é preservado. O parâmetro `secao` serve para retorno de formulários e paginação e não vai à API.
- `turmas.secao` carrega sob demanda as seções `acesso` e `disciplinas`.
- Perfis administrativo e pessoal recebem só as seções que lhes cabem. A ocultação não é só visual.
- O JavaScript é `turma-abas.js`. Ele implementa `tablist`, `tab` e `tabpanel`, setas, Home, End, Enter e Espaço.

**Quatro operações independentes** (cada uma com endpoint, permissão, CSRF e revisão próprios):

1. Adicionar disciplina do catálogo à turma.
2. Indicar o professor na disciplina.
3. Conceder acesso à turma.
4. Registrar a atribuição com período e papel.

Além delas, a API oferece a operação composta aditiva `POST /api/v1/turmas/{id}/vinculos-docentes` (`disciplina_id`, `professor_vinculo_id`, `chave_idempotencia`). Ela cria o componente se preciso, indica o professor, concede o acesso e registra a atribuição desde hoje, em uma transação. Um recibo imutável permite reenvio sem repetir efeitos. A confirmação na tela explica os efeitos.

Regras de negócio da docência:

- Novas atribuições não podem sobrepor papel docente na mesma disciplina e turma. O fim do período é exclusivo. Um professor pode atuar em disciplinas distintas. O histórico existente, inclusive codocência, é preservado.
- Diretor e vice não recebem a gestão DC1 da coordenação.
- O catálogo de disciplinas na turma usa o GET paginado da API, com busca e paginação reais. Falha de consulta é diferente de lista vazia. A seleção mostra só a página consultada e a tela não a apresenta como catálogo inteiro.

**Edição e cadastro** (`turmas.edit`, página completa): nome, série, ano, turno, etapa de ensino, sala, capacidade e observações. Capacidade é planejamento e não bloqueia matrícula. Etapa é informação cadastral. Observações não entram nos documentos. Quatro colunas opcionais foram adicionadas a `turmas`.

**Aluno entre turmas** (`POST /api/v1/alunos/{id}/transferencias-turma`; Web `alunos.transferir`): move o aluno **entre turmas da mesma escola**, com revisão em duas etapas. Registra origem, destino, responsável, momento e motivo em `aluno_movimentacoes` (eventos imutáveis, motivo cifrado). Exige `aluno.ver`/`aluno.gerir` e perfil de Coordenação, Direção, Vice-direção ou Secretaria no próprio escopo. Conflito retorna 409, sem repetição automática. Leitura de cartões em andamento na escola bloqueia a movimentação. Resultados antigos não migram para a nova turma. Cartões guardam a turma de aplicação (`turma_aplicacao_id`).

**Arquivamento** de turma e de cadastro de aluno substitui a "desativação": é explícito, reversível e feito em diálogo próprio, sem `confirm()` nativo. Há recibo transacional imutável, chave idempotente, versão monotônica e autorização sob trava. Não representa encerramento de matrícula. As rotas `toggle` permanecem só por compatibilidade. Veja `ArquivamentoCadastroController` (`PUT .../arquivamento`, permissões `turma.desativar` e `aluno.desativar`).

## 3. Imagens de turma e de educadores (TU-IMG)

Entrega de 07/11/2026 (FN-037), separada do dashboard.

- A imagem aparece junto ao título da turma. Só quem gerencia a turma (`turma.gerir`) a edita.
- A escolha é avatar do catálogo ou foto, com arquivo, câmera, recorte e compactação. Cancelar ou falhar preserva a imagem anterior.
- Catálogos em atlas 6x6, usados por CSS: 36 educadores adultos, 36 brasões de turma (pixel art medieval original) e 36 estudantes. Arquivos em `apps/web/public/images/avatares/` (`educadores`, `brasoes`, `estudantes`, em PNG v1 e WebP v2).
- API aditiva: três campos com default em `turmas` (migration `2026_11_06_000005_adicionar_imagem_turmas`, `imagem_versao` e outros). Imagem privada e versionada, com autorização por alcance e perfil e com auditoria. Rotas Web: `turmas.imagem` (POST) e `turmas.foto` (GET).
- O professor reutiliza a imagem da própria conta (`avatar-usuario`).
- Estado atual: a imagem aparece no detalhe, no perfil próprio e em Editar turma. A lista de turmas ainda usa iniciais, e o modal Nova turma e o cadastro de membro não têm seletor de imagem (veja a seção 10).

## 4. Painel individual da turma

### 4.1 Recorte e filtros

`GET /api/v1/turmas/{id}/painel` (permissão `relatorio.turma`) devolve o DTO autorizado. Filtros opcionais:

| Filtro | Significado |
|---|---|
| `disciplina_id` | Seleciona as provas com vínculo curricular com a disciplina. A prova entra inteira |
| `professor_id` | **Professor autor** da prova (`criado_por` com perfil de professor). Não é o docente atribuído hoje |
| `inicio`, `fim` | Datas de aplicação |

Regras:

- Provas em correção ou corrigidas sem data só entram sem período delimitado. Provas agendadas não entram.
- O filtro de professor nunca atribui autoria ao docente atual. O rótulo permanece "Professor autor".
- Prova multidisciplinar: o resultado global da avaliação é identificado como tal. O sistema não inventa nota por disciplina.
- Os filtros ficam em um modal acionado por botão de ícone. Cancelar e Escape descartam o rascunho e devolvem o foco. O recorte aplicado aparece em linha discreta, só quando existe.
- No professor, o seletor de disciplina só aparece com mais de uma disciplina autorizada. Com uma, a disciplina é identificada sem seletor.

### 4.2 Indicadores

Fonte: `PainelTurma` e `AnalisePainelTurma` (API). Os resultados usam notas finais ligadas a cartão vigente, lido, na turma de aplicação.

| Bloco | Cálculo e base |
|---|---|
| Avaliações aplicadas | Provas distintas autorizadas, com aplicação até hoje |
| Resultados concluídos | Registros de nota do recorte (o mesmo aluno entra uma vez por avaliação) |
| Alunos com resultado | Cadastros `aluno_id` distintos com nota final. É cadastro local, não pessoa única entre instituições |
| Acerto médio | Média de `100 x acertos / total_questoes` por resultado válido. Cada resultado tem o mesmo peso. Sem base: nulo, não zero |
| Mediana e faixa observada | Mesma base válida: valor central, mínimo e máximo |
| Distribuição | Dez faixas `[0,10)` a `[90,100]` por resultado. A soma é o total válido |
| Média e mediana por avaliação | Por instrumento, com `n` próprio |
| Situações registradas | Status de aprovação registrado (aprovado, recuperação, outros). Sem limiar universal |

Grupos nominais (exigem `aluno.ver` e `resultado.ver`, com veto de aluno e aplicador):

- **Atenção:** alunos com ao menos uma indicação de recuperação registrada. A lista expande. Não é diagnóstico nem plano de intervenção.
- **Dentro do critério:** alunos com resultados em que todos estão aprovados. Quem não tem resultado não entra. Permanece no contrato e nos relatórios, mas sem grupo visual próprio.
- **Top 5:** média dos percentuais válidos por aluno, com posições empatadas e aviso de outros empatados no corte. É descritivo, não é progresso.

Regras de semântica:

- Média bruta entre provas diferentes não é ganho de aprendizagem nem qualidade docente.
- Zero, nulo, parcial e erro são estados distintos. Falta de leitura não prova ausência.
- A média da listagem de turmas (`AVG(nota_final)`) não é a métrica do painel. Não a use em comparação.
- O sistema não guarda um conjunto esperado de alunos por aplicação. Matrícula atual não é participação nem frequência.
- A distribuição usa resultados vigentes, não tentativas arquivadas. Cartão em revisão não conta como final.
- Nenhum nome de aluno vai ao atributo de dados dos gráficos. O cliente recebe só `distribuicao_acertos` e `por_avaliacao`.

### 4.3 Documento do recorte

`GET /api/v1/turmas/{id}/painel/pdf` (Web `turmas.painelPdf`) gera o PDF com o mesmo recorte, em retrato ou paisagem (`orientacao`). Exige `relatorio.turma`, revalida a autorização e o DTO depois da geração e registra a emissão verificável. O arquivo temporário privado é apagado após o envio. O PDF ainda traz "acerto por questão" e não traz os grupos Atenção, Dentro do critério e Top 5 (pendência TU1-PDF, FN-050). Impressão e PDF abrem um diálogo de orientação.

## 5. Painel agregado de turmas

### 5.1 Composição

Ordem na tela: resumo fixo, relação de turmas (Tabela | Cards), análises, planejamento.

| Item | Detalhe |
|---|---|
| Resumo fixo | Quatro cards: Turmas no recorte, Em uso, Arquivadas e Cadastros de alunos ativos |
| Relação | `x-tabela` por padrão. Busca (rótulo "Nome ou série"), filtros em modal, ordenação, paginação e ações. A alternância Tabela e Cards usa a mesma coleção e preserva o estado |
| Brasão | Imagem da turma, quando autorizada |

O resumo vem de `data.resumo_cadastral` em `GET /api/v1/turmas`:

```json
{"versao":1,"base":"colecao_completa_no_recorte","colecao_completa":true,"turmas":2,"em_uso":1,"arquivadas":1,"cadastros_ativos":54}
```

- Os quatro contadores são inteiros maiores ou iguais a zero. `turmas = em_uso + arquivadas`.
- O recorte é a coleção completa autorizada depois de `busca`, `escola_id`, `ano_letivo` e `ativo`.
- `cadastros_ativos` conta `DISTINCT alunos.id` ativos nas turmas do recorte. É cadastro local, não pessoa global, matrícula histórica ou presença.
- Os campos legados `data.turmas` e `data.kpis` continuam, com o universo antigo (os KPIs ignoram os filtros).
- As consultas são agregadas em lote, sem consulta por turma. Não há cache de concessões.
- O BFF distingue ausência do campo (contrato antigo), nulo, inválido, incompleto, versão desconhecida e zero verdadeiro. Agregado inválido deixa os contadores indisponíveis, sem apagar a lista.

### 5.2 Comparações

A comparação de desempenho entre turmas, a cobertura por disciplina e a participação seguem como modelos demonstrativos. Falta base comparável (instrumentos, ponderação) e conjunto esperado de alunos. Veja FN-053 (P3.3, P4).

## 6. Estados e modelos demonstrativos

| Estado | Comportamento |
|---|---|
| Modelo (Em breve) | Composição completa com gráfico, agenda ou progresso, valores e nomes fictícios. Selo **Em breve** e **Dados demonstrativos** compactos no cabeçalho do card |
| Carregando | Busca real em andamento, limitada à região afetada |
| Sem registros | Recurso funcional, mas nenhum registro no recorte |
| Zero válido | Zero calculado sobre uma base conhecida |
| Parcial | Dado útil com cobertura e exclusões explícitas |
| Indisponível por falha | Erro e ação segura de nova consulta. Nenhum zero substitui a falha |
| Funcional | Dados reais, recorte correto e ações autorizadas |
| Sem permissão | O componente e seus dados não são enviados |

Regras dos modelos:

- Os exemplos vêm de uma fábrica estática, com IDs conhecidos e dados coerentes de 2026. Ela não lê sessão, banco nem os nomes escolhidos nos filtros.
- Um gráfico nunca mistura dado real e fictício. Exemplo nunca alimenta indicador, relatório, notificação ou exportação reais.
- Erro, vazio e zero verdadeiro nunca viram exemplo.
- Modelo não chama endpoint inexistente, não faz polling e não simula gravação.
- Ao ativar a fonte real, o widget mantém ID, posição e tamanho. Um recorte real vazio mostra estado vazio, não outra demonstração.
- As três agendas (`agenda-pedagogica`, `proximos-compromissos`, `agenda-administrativa`) usam Calendar.js 1.1.0 em Timeline somente leitura. A agenda real depende de FN-056, FN-057 e FN-059.
- O modelo da secretaria e do auxiliar é só administrativo. Eles não recebem prova, nota, resposta, ranking, alerta de integridade nem contador pedagógico, inclusive em agenda, busca, notificação e exportação.
- Cards sem dados têm altura reduzida só na leitura (projeção separada da disposição salva). O desktop mede título e mensagem em pixels CSS locais e quantiza em células de 48 px. Pins mantêm posição. Nenhuma gravação automática ocorre.
- Cada tipo de componente tem piso de leitura no cliente: 240 px (progresso, contagens, tarefas, registros), 280 px (nominais, linhas do tempo, responsabilidade docente) e 300 px (gráficos).

## 7. Autorização e perfis

Poder vem das permissões efetivas do vínculo ativo, nunca do cargo textual. A API decide o conteúdo. A Web filtra o catálogo antes de renderizar. A interface não envia o bloco sem permissão.

| Conteúdo | Exigência |
|---|---|
| Modelos pedagógicos | `relatorio.turma`; veto de secretário escolar e auxiliar |
| `alunos-destaques`, `alunos-atencao` | `relatorio.turma` + `aluno.ver` + `resultado.ver`; veto de aluno e aplicador |
| Conselho | `turma.vincular_professor` |
| Documentos administrativos | `aluno.ver` |
| Contadores de revisão, processamento e falha | `correcao.acompanhar` |

Pontos de cuidado:

- O middleware `permissao:a,b` trata a lista como **OU**. Um bloco que exige duas permissões precisa de duas guardas.
- `GET /api/v1/turmas` e `GET /api/v1/turmas/{id}` exigem só `turma.ver`. A projeção por conteúdo omite `media_turma` e dados pedagógicos de quem não tem as permissões pedagógicas.
- Permissão de consultar um card não libera arquivo, PDF ou foto. Cada rota tem sua guarda.
- Professor vê apenas suas turmas e disciplinas. Professor autônomo trabalha no espaço pessoal. Escola autônoma é modalidade institucional, não perfil com acesso irrestrito.
- Os perfis administrativos (secretário escolar e auxiliar) têm a visão `turmas/administrativo`, com tabela, busca, cadastro e vínculos consultáveis, sem desempenho.
- Selecionar contexto de vínculo não soma permissões. Ao trocar de vínculo, o cliente descarta respostas do contexto anterior.

Composição padrão por perfil (resumo):

| Perfil | Turmas (agregado) | Turma (individual) |
|---|---|---|
| Professor autônomo e escolar | Resumo, as próprias turmas, pendências, comparação, agenda | Retrato, distribuição, acompanhamento, habilidades, agenda (só suas disciplinas) |
| Coordenação | Cobertura, mapa turma x disciplina, prioridades, ciclo de avaliações | Panorama entre disciplinas, alunos a acompanhar, intervenções, conselho |
| Direção e vice | Situação da escola, prioridades, decisões, compromissos | Visão consolidada, responsáveis, encaminhamentos |
| Secretaria e auxiliar | Cards administrativos, busca, listagem, solicitações | Cadastro, alunos, vínculos, movimentações, documentos, agenda administrativa |

## 8. Editor de painéis e personalização

### 8.1 Arquitetura

O fluxo é navegador, App Web (BFF), API e banco. A Web não lê nem grava as tabelas de preferências. O conteúdo operacional reutiliza os nós Blade autorizados. Não há `localStorage` de layout. Os arquivos principais são:

| Camada | Arquivos |
|---|---|
| API | `PreferenciaPainelController`, `PreferenciasPainel`, `CatalogoPaineis`, `CatalogoWidgetsPainel`, `ComposicaoPainel`, `OrganizacaoPainel`, `PadroesPainel`, `BootstrapEditorPainel`, `CacheComposicaoPainel`; `config/paineis.php` |
| Web (BFF) | `PreferenciasPainelController`; rotas `GET/PUT /paineis/preferencias/{painel}/{turmaId?}` |
| Cliente | `painel-grade.js` (GridStack), `editor-paineis.js` e `resources/js/editor-paineis/*` (SortableJS e GridStack), `painel-agenda.js`, `painel-graficos-opcoes.js`, `turma-painel.js`, `turma-graficos.js` |
| Estilos | `paineis-personalizaveis.css`, `painel-grade.css`, `painel-indicadores.css`, `painel-agenda.css`, `editor-paineis.css` |
| Build | `scripts/paineis-grid` (GridStack 14.1.0, SortableJS 1.15.7, esbuild) |

### 8.2 Escopo, precedência e fixos

- A chave da preferência é: usuário + vínculo + espaço + perfil ativo + painel + recurso. O recurso é `contexto` (painel `turmas`) ou `turma` (painel `turma`, com o ID da turma). Uma turma não reutiliza a configuração de outra.
- Para o painel `turma`, o BFF envia o header técnico `X-G360-Painel-Turma` com um ID inteiro positivo, derivado da referência UR4 validada. A API autoriza essa turma por `Escopo::turmas` antes de consultar. Para `turmas`, o header é proibido. Turma fora do alcance retorna 404 e ID inválido retorna 422.
- Precedência: mapa pessoal válido (após filtrar IDs ainda autorizados) vence o padrão do perfil. Sem mapa pessoal, vale `padrao.desktop`. Revogação de acesso e catálogo atual vencem o salvo.
- O padrão por perfil vem de `config/paineis.php`, somente leitura e sempre cruzado com o catálogo autorizado. Configuração inválida retorna 503.
- O usuário só **reordena e redimensiona**. Não adiciona, exclui nem duplica widgets. Mapa parcial legado é aceito, e o cliente completa os IDs ausentes com o padrão.
- O **resumo/KPIs é fixo** nos dois painéis (seção `resumo`). Fica fora do catálogo editável e de `padrao.desktop`. Enviar o ID do resumo retorna 422. Não significa `position: sticky`.
- Edição só com viewport de pelo menos 1024 x 700 px CSS (aproximação por área disponível, não polegadas físicas). Abaixo disso a tela não consulta nem grava preferências e a composição fixa não sobrescreve o desktop. O mapa mobile do contrato permanece isolado e a UI atual só usa o desktop.

### 8.3 Geometria (GridStack nativo compacto)

- Grade de 12 colunas e até 200 linhas por seção, no máximo 64 widgets por dispositivo, com ID único e seção fixa por widget.
- Limites do catálogo: largura mínima 3 e máxima 12 (desktop); mobile fixo em 12. Altura máxima 20.
- Alturas mínima/padrão: gráficos 8/9; `alunos-destaques` e `alunos-atencao` 10/12; agendas 12/14; demais 7/9.
- Larguras e alturas são **individuais**. O motor nativo compacta espaços viáveis. Lacunas impostas por retângulos fixos são legítimas.
- **Redistribuição horizontal (decisão mais recente):** widgets lado a lado preenchem a largura do container. Ao mudar a largura de um, a sobra ou o excesso vai aos vizinhos do mesmo segmento contíguo. Inserir um card entre outros redimensiona o conjunto. O cliente decide a intenção a partir do snapshot anterior ao gesto. Impossibilidade real (mínimos somados maiores que o espaço) mantém o estado anterior com a mensagem "Os cards não cabem neste espaço. Solte acima ou abaixo."
- **Fixar posição:** o pin protege `x/y/w/h` contra gesto, resize e deslocamento de vizinhos. Pin prevalece sobre redistribuição. Operação inviável restaura a disposição inteira.
- Seções técnicas não se cruzam: um widget não sai da sua seção na grade.
- Gráficos de cinco IDs aceitam orientação horizontal ou vertical: `distribuicao-acertos`, `resultados-avaliacao`, `periodos-escolares`, `habilidades`, `reavaliacoes`. No desktop, a orientação é salva junto da geometria.
- Controles: alça de arraste com ícone de mão aberta (face de 32 px, alvo de 44 px), cursor grab/grabbing, foco visível, instrução de teclado. Organizar, Salvar, Cancelar e Restaurar padrão ficam na tela, sem modal de gerenciamento.
- Cancelar volta ao snapshot exibido antes da edição. O JSON armazenado só muda ao Salvar. Nenhum PUT é automático.
- O cliente pode vir com `zoom` do usuário. Coordenadas do ponteiro usam medidas visuais normalizadas uma vez. Medidas locais de CSS ficam para estilos e placeholder.
- Ao desmontar (mudança de viewport, Escape, `pointercancel`), os gestos pendentes são cancelados antes de destruir a grade.

### 8.4 Contrato de preferências

Rotas da API (autenticação normal, vínculo explícito, permissão `turma.ver`):

- `GET /api/v1/paineis/preferencias/{painel}`
- `PUT /api/v1/paineis/preferencias/{painel}`

`painel` é `turmas` ou `turma`. Perfis internos e acessos legados sem vínculo não usam esta API. O Bearer contextual fica só no servidor BFF.

Resposta (envelope `data`, recorte ilustrativo):

```json
{
  "versao": 0, "apresentacao": "tabela",
  "layouts": {"desktop": [], "mobile": []},
  "catalogo": [{"id": "cadastros-turno", "section": "analises",
    "desktop": {"minW": 3, "maxW": 12, "minH": 8, "maxH": 20, "defaultW": 6, "defaultH": 9},
    "mobile":  {"minW": 12, "maxW": 12, "minH": 8, "maxH": 20, "defaultW": 12, "defaultH": 9}}],
  "padrao": {"desktop": [{"id":"cadastros-turno","section":"analises","x":0,"y":0,"w":12,"h":9}]},
  "origem_disposicao": "perfil", "repetido": false
}
```

- Versão 0 significa "sem preferência salva". O GET nunca cria registro. A resposta usa `Cache-Control: private, no-store`.
- `origem_disposicao` é `usuario` (mapa pessoal) ou `perfil` (padrão).

Salvar (`acao: "salvar"`):

```json
{"versao": 0, "chave": "<uuid>", "acao": "salvar", "apresentacao": "cards",
 "layouts": {"desktop": [{"id":"cadastros-turno","section":"analises","x":0,"y":0,"w":12,"h":9}]}}
```

- `apresentacao` aceita só `tabela` ou `cards`. Pelo menos `apresentacao` ou `layouts` deve ir. Um mapa enviado substitui o mapa daquele dispositivo. Dispositivo omitido é preservado.
- Cada item tem os seis campos `id, section, x, y, w, h` e pode ter `altura_desejada`, `fixo`, `altura_livre` e `orientacao`. Números são inteiros JSON, sem coerção de texto.
- `altura_desejada` respeita `minH <= altura_desejada <= h <= maxH`. Sem o campo, o cliente usa `h`. `fixo` e `altura_livre` aceitam só booleanos (ausente não vira `false`). `orientacao` aceita `horizontal` ou `vertical` e só nos cinco IDs de gráfico. `altura_livre` é aceito por compatibilidade e não tem efeito.
- A API aceita somente o estado final válido. Ela não reorganiza nem corrige o pedido.
- Colisão na mesma seção, ID não autorizado, limite excedido ou campo extra retornam 422.

Restaurar (`acao: "restaurar"`): `alvos` é um subconjunto não vazio e sem repetição de `desktop`, `mobile` e `apresentacao`. Restaura só os alvos pedidos. Restaurar apresentação volta a `tabela`. A ação não acompanha `layouts` nem `apresentacao`.

Versão, repetição e auditoria:

- O PUT exige a `versao` atual e uma `chave` UUID. Todo pedido aceito incrementa a versão. Aba antiga recebe 409, sem aplicação parcial e sem repetição automática.
- Repetir a mesma chave e o mesmo pedido normalizado devolve o resultado original com `repetido: true` (a versão pode ser histórica). Mesma chave com pedido diferente retorna 409. A idempotência é isolada por preferência, contexto, painel e recurso.
- O recibo (`ação`, hash do pedido, versão, resultado geométrico e horário) é gravado na mesma transação e protegido contra UPDATE e DELETE por trigger. Falha no recibo reverte a preferência.
- Escritas travam vínculo, espaço, turma e preferência, e revalidam o vínculo. No MariaDB usam transação própria `READ COMMITTED`.
- Limites: GET 60 por minuto e PUT 20 por minuto por usuário (`throttle:preferencias-paineis`). 429 mantém `Retry-After`.
- Tabelas: `preferencias_paineis` (`painel` varchar 64, `recurso_tipo` varchar 16, `recurso_id` nullable, coluna `composicao` longText nullable) e a tabela de recibos. Migrations `2026_11_07_000001_registrar_preferencias_paineis` e `2026_11_09_000001_adicionar_composicao_paineis`. O `down()` recusa apagar histórico. Não rode DDL com a credencial DML da aplicação.

### 8.5 Editor (composição)

O editor é a segunda geração da personalização: deixa o usuário **distribuir** widgets entre painéis e seções, além de ajustar a geometria. Estado: concluído (FN-054).

Contrato do editor (extensão opcional do mesmo endpoint):

- O cliente pede o editor com o header `X-G360-Painel-Editor: 3`. Outra versão retorna 422. Cliente antigo continua com a representação anterior e não apaga a composição nova ao salvar geometria.
- A coluna `composicao` guarda a árvore (painéis, seções, widgets avulsos). A mesma transação, versão e chave de idempotência valem para geometria e composição. Eventos antigos permanecem imutáveis.
- O catálogo técnico e o padrão vêm do servidor. O payload não aceita usuário, vínculo, caminho, classe CSS ou HTML. A API rejeita catálogo obsoleto, IDs estranhos ou duplicados, ciclos, profundidade excedida (dois níveis de seção), limites e filhos cortados. Composição armazenada pode ter widgets dormentes, mas a projeção não expõe widget revogado.
- Limites de `ComposicaoPainel`: no máximo 400 nós e 15 seções. Títulos pessoais: até 60 caracteres por seção e 80 por widget, tratados como texto.
- Bootstrap: o editor vem junto da requisição que já entrega a lista ou a turma (`meta.editor_painel`). Não há GET extra de preferência na abertura. A árvore fica só em memória, sem cookie nem storage. A API usa `ETag` e `If-None-Match` com reautorização antes do 304. O BFF converte o 304 em 200 JSON explícito. Foco e visibilidade revalidam de forma coalescida e `BroadcastChannel` carrega só invalidação.
- O cache derivado não guarda títulos. A chave é imutável: contexto + versão + catálogo + schema + projetor + configuração.

Interface do editor (duas abas):

| Aba | Função |
|---|---|
| Distribuição | Quadro de colunas hierárquico (SortableJS). Cada coluna raiz é um painel. Seção filha é contêiner embutido. A área "Sem seção" reúne widgets avulsos. Permite criar painel e seção, renomear, ocultar, ativar e reordenar. Remover contêiner promove os filhos ao nível acima, com confirmação |
| Ajustes finos | Grade GridStack da raiz e de cada seção. Mover, redimensionar e fixar relativos ao pai, sem transferir widgets entre pais |

Regras do editor:

- Três painéis configurados por padrão por perfil. A preferência pessoal pode ter 1, 2, 3 ou mais.
- A ordem das colunas segue a expressão do responsável: da esquerda para a direita representa "base para topo". O botão Ver painel mostra o resultado. O painel vertical inverte as colunas.
- Contêiner sem descendente visível fica no editor, mas não aparece na leitura.
- As duas abas compartilham o rascunho. Salvar é explícito. Cancelar restaura tudo. Restaurar padrão continua rascunho até Salvar. Conflito (409) preserva o rascunho e não reenvia.
- Edição só em desktop elegível (1024 x 700 e ponteiro fino). No celular, a tela lê a mesma composição salva ou o padrão, sem controles nem gravação. Mudar de viewport durante o gesto suspende e preserva o rascunho.
- Pins impedem mover e ocultam o resize. Um pai não encolhe abaixo do conteúdo dos filhos nem amplia uma âncora fixada.
- Redimensionar mede o cabeçalho real depois do dimensionamento e propaga os limites de baixo para cima. O ciclo de gesto (`gestos-grade.mjs`) é único na raiz, com ticket e fase, e aceita o par arrastar e soltar de qualquer nível.
- Categorias iniciais: em Turmas, "Turmas (lista)", "Análises entre turmas" e "Acompanhamento pedagógico". Em Turma, "Resultados da turma", "Acompanhamento da turma" e "Planejamento da turma".
- Na turma, a seção "Resultados da turma" contém: Estudantes, depois Período e agenda, depois Análises da turma. Os grupos pedagógicos de modelo ficam em "Acompanhamento da turma" (planejamento, ensino, decisões).
- Seções de turma (`ComposicaoPainel`): `resultados`, `acompanhamento`, `planejamento`, `estudantes`, `periodo-agenda`, `analises`, `ensino`, `administrativo`, `grupo-planejamento`.
- Mover uma seção inteira ou um card central usa alças próprias no título, com setas para cima e para baixo. Escape, `pointercancel`, `blur` e `resize` cancelam o gesto. Os campos `ordem_card` e `ordem_secao` são opcionais por widget.
- A migração de `periodos-escolares` e `agenda-pedagogica` do grupo "Planejamento" para `periodo-agenda` é lida por projeção compatível. Preferências antigas continuam aceitas. Um leitor antigo recusa a composição nova depois de salva. O rollback é de código, sem restaurar SQL.

### 8.6 Notificações do editor

Salvar mostra toast de sucesso após o ACK válido. Ações discretas usam toast informativo ou região viva só para leitor de tela. Erros e conflitos persistem no aviso junto aos controles. A biblioteca é Solid com solid-toast, já instalada. O host é único, fixo e fora do fluxo. Em diálogo aberto, o host vai para o top-layer do diálogo e volta ao fechar. Sem a biblioteca, há fallback contextual acessível.

### 8.7 Catálogo resumido de widgets

IDs vêm de `config/paineis.php` e `ComposicaoPainel`.

| Painel | Widgets editáveis |
|---|---|
| Turmas | `cadastros-turno` (real), `comparacao-turmas`, `cobertura-disciplinas`, `prioridades`, `proximos-compromissos`, `devolutivas`, `responsabilidade-docente-aplicacao`, `intervencoes`, `agenda-administrativa`, `documentos-administrativos` (demonstrativos), `turmas-lista` (relação) |
| Turma | `alunos-destaques`, `alunos-atencao`, `distribuicao-acertos`, `resultados-avaliacao`, `situacoes-registradas` (reais); `periodos-escolares`, `agenda-pedagogica`, `devolutivas`, `participacao`, `responsabilidade-docente-aplicacao`, `habilidades`, `intervencoes`, `reavaliacoes`, `metas`, `conselho`, `agenda-administrativa`, `documentos-administrativos` (demonstrativos) |

Resumo fixo do painel Turmas: `kpi-turmas`, `kpi-em-uso`, `kpi-arquivadas`, `kpi-cadastros`. Do painel Turma: `kpi-avaliacoes`, `kpi-resultados`, `kpi-alunos-resultado`, `kpi-media`, `kpi-mediana`, `kpi-faixa` e, no administrativo, `turma-cadastro`.

## 9. Ensaios e qualidade (resumo)

- O Chrome local com fixtures fictícias e a auditoria de navegador em `scripts/auditoria/` cobrem ponteiro, teclado, salvar e recarregar, cancelar, restaurar, mobile e 409 sem reenvio.
- O contrato de catálogo é confrontado com os padrões reais da API em teste (extrator de DTOs).
- Cada incremento passou por CI (cinco checks), revisão independente de UI/UX e publicação só na homologação, com backup e comparação das tabelas e arquivos. O histórico fica em FN-053 e FN-054 e no Git.
- Um desempenho medido do MariaDB na CI mostrou que o isolamento por `migrate:fresh` domina o tempo. O serviço de integridade usa `tmpfs` para `/var/lib/mysql` e o contexto de autorização foi consolidado em menos consultas. Veja [FN-077](../funcionalidades/077-desempenho/planejamento.md).

## 10. Pendências conhecidas

| Item | Onde |
|---|---|
| Comparação entre turmas, cobertura, participação (P3.3, P4) | FN-053 |
| Agenda real, períodos letivos, notificações | FN-056, FN-057, FN-059 |
| Habilidades, intervenções, metas, conselho (P6) | FN-055 |
| GII, TCT/TRI, integridade, sínteses (P7) | FN-044, FN-051 |
| PDF da turma com grupos nominais (TU1-PDF) | FN-050 |
| Imagem em Nova turma, no cadastro de professor e na lista de turmas | FN-036 e FN-037 |
| Conferência autenticada do TU-IMG na homologação | FN-037 |

## 11. Divergências encontradas

| # | Documento antigo | Código atual | Vale |
|---|---|---|---|
| 1 | Doc 177 (P2.7.3): "Celular não força Cards" | `painel-grade.js` mostra Cards quando `max-width: 767px`. A preferência salva continua sendo a do desktop | Código |
| 2 | Docs 177 e 180: organização por linhas de 12 colunas com altura comum, depois "vizinhos imóveis" | O motor atual é GridStack nativo compacto com alturas individuais e redistribuição horizontal. Os dois modelos anteriores estão superados | Código e decisão mais recente |
| 3 | Doc `contratos/preferencias-paineis`: "O padrão preenche linhas de 12 colunas, com altura uniforme" | `padrao.desktop` ainda pode ocupar a linha toda, mas é só composição inicial. O mapa pessoal pode deixar espaço livre | Decisão posterior |
| 4 | Doc 177: widgets de resumo (`kpi-*`) no catálogo editável | `CatalogoWidgetsPainel` lista os IDs, mas `CatalogoPaineis::definicao()['secoes_fixas']` remove a seção `resumo` do catálogo servido. O PUT com esses IDs retorna 422 | Código |
| 5 | Doc 190: editor com `localStorage` | Era só a prévia local. A integração real usa API e banco, sem `localStorage` de layout | Código |
| 6 | Doc 190: dois níveis de seção "nesta prévia" | A API de composição valida a profundidade. `MAX_ROWS` 400 e `MAX_SECOES` 15 | Código |
| 7 | Doc 179: mínimo de largura 4 | O catálogo usa mínimo 3 (aprovado pelo Design) | Código |
| 8 | Doc 131 e 149: "50 instâncias" e "41 views" no PR178 | Contagens históricas. Rode o inventário na CI para o número vigente | Código |
| 9 | Doc TU1-R3 cita quatro gráficos com "acerto por questão" | O quarto gráfico foi substituído por acompanhamento nominal. A análise por questão fica nos resultados da prova | Decisão posterior |
| 10 | Doc 175: imagens `*-v1.png` | Há também `*-v2.webp` em `public/images/avatares/` | Código |
| 11 | Doc 193: toast para todo feedback | Avisos de validação e erro continuam no aviso contextual, por decisão | Decisão |

Os históricos de execução dos documentos 177 a 191 e 193, com PRs, SHAs, contagens de teste e recibos de publicação, estão nos `planejamento.md` de FN-053 e FN-054 e no Git.
