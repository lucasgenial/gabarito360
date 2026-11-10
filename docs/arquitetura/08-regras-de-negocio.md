# Regras de negócio

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve as regras de negócio vigentes do Gabarito360, agrupadas por domínio, conferidas no código da branch atual.

## Como ler este documento

Cada regra tem um código no formato `RN-<DOMÍNIO>-NN`. O código é permanente. Não reutilize um número.

| Coluna | Significado |
|---|---|
| Regra | O que o sistema faz hoje. |
| RN antiga | Regra equivalente do antigo `docs/06` (por exemplo, RN-007.2). Um traço indica regra nova. |
| FN | Funcionalidade dona da regra, no [catálogo](../funcionalidades/README.md). |
| Situação | Resultado da conferência no código. |

| Situação | Significado |
|---|---|
| Vigente | O código cumpre a regra. |
| Divergente | O código faz algo diferente da regra antiga. Vale o código. A seção "Divergências encontradas" explica. |
| Parcial | Uma parte da regra existe no código. |
| Não implementada | A regra foi planejada e não existe no código. |
| Substituída | Uma decisão posterior trocou a regra. |

### Domínios

| Código | Domínio | Seção |
|---|---|---|
| PRV | Provas e gabaritos | 1 |
| COR | Aplicação e correção | 2 |
| LEI | Leituras, revisão e tentativas | 3 |
| RES | Resultados, notas e relatórios | 4 |
| TUR | Turmas, alunos e matrículas | 5 |
| CUR | Disciplinas, áreas e currículo (DC1) | 6 |
| COP | Cópias entre espaços (T5) | 7 |
| DUP | Duplicação de provas (CP1 e CP2) | 8 |
| CAT | Catálogo nacional de instituições (CI1) | 9 |
| EIN | Escolas independentes (EI1) | 10 |
| GER | Regras gerais herdadas do `docs/06` (acesso, estrutura, configuração, assinatura) | 11 |

Outros documentos são donos de assuntos vizinhos. Não repita o conteúdo deles aqui.

| Assunto | Documento dono |
|---|---|
| Perfis, vínculos e permissões | [04 — Identidade, acesso e permissões](04-identidade-acesso-e-permissoes.md) |
| Espaços, vínculos e cópias entre espaços (estrutura) | [07 — Clientes, espaços e vínculos](07-clientes-espacos-e-vinculos.md) |
| Motor OMR, QR, fila e PDFs | [06 — OMR e documentos](06-omr-e-documentos.md) |
| Tabelas e chaves | [03 — Modelo de dados](03-modelo-de-dados.md) |
| Painéis por perfil | [10 — Painéis e editor](10-paineis-e-editor.md) |
| Auditoria e LGPD | [11 — Segurança e privacidade](11-seguranca-e-privacidade.md) |

---

## 1. Provas e gabaritos (PRV)

### Ciclo de vida, gabarito e configuração

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-PRV-01 | O ciclo de vida da prova é `rascunho`, `publicada`, `em_correcao` e `corrigida`. A criação sempre começa em `rascunho`. | RN-007.1 | FN-040 | Vigente |
| RN-PRV-02 | A prova muda para `em_correcao` e `corrigida` por regra automática da correção (RN-COR-09). O professor não precisa alterar o estado à mão. | RN-007.1 | FN-045 | Vigente |
| RN-PRV-03 | O gabarito só pode ser publicado com todas as questões preenchidas. A publicação exige `gabarito.editar` e marca a prova como `publicada`. | RN-007.2 | FN-040 | Vigente |
| RN-PRV-04 | O gabarito publicado não pode mais ser salvo. A API responde 422. | RN-007.3 | FN-040 | Divergente (D-01) |
| RN-PRV-05 | O gabarito aceita as letras A a E, o número da questão de 1 a 100, o peso e a marca de anulada. A API não compara a letra com `num_alternativas`. | RN-007.4 | FN-040 | Vigente |
| RN-PRV-06 | A prova tem 3, 4 ou 5 alternativas. A API exige o campo na criação. | RN-007.4 | FN-040 | Divergente (D-02) |
| RN-PRV-07 | A nota máxima vai de 0,5 a 100. O padrão do banco é 10. | RN-007.6 | FN-040 | Divergente (D-02) |
| RN-PRV-08 | O tipo de pontuação aceita `igual` e `personalizado`. O cálculo da nota usa só a contagem de acertos e ignora o peso. | RN-007.7 | FN-040 | Divergente (D-03) |
| RN-PRV-09 | Com a opção "Anular questão se todas marcadas", uma questão com várias marcações fica sem resposta e não gera ambiguidade. A questão conta como erro. | RN-007.5 | FN-045 | Divergente (D-04) |
| RN-PRV-10 | Uma questão marcada como anulada no gabarito sai do total de questões da nota. Ela não conta como acerto. | RN-007.5 | FN-040 | Divergente (D-04) |
| RN-PRV-11 | A prova com opção de língua guarda uma alternativa de espanhol por questão. O cartão com língua `ESP` usa essa alternativa na nota. | — | FN-040 | Vigente |
| RN-PRV-12 | O formato da avaliação tem hoje um único valor: `gabarito` (cartão-resposta). A edição não troca o formato. | — | FN-042 | Vigente |
| RN-PRV-13 | Uma prova se vincula a uma ou mais turmas da escola. A data de aplicação é única por prova (`provas.data_aplicacao`). | RN-007.8 | FN-040 | Divergente (D-05) |
| RN-PRV-14 | Só um rascunho pode ser excluído. Uma prova com procedência de cópia ou com currículo registrado é preservada (resposta 409). | — | FN-040 | Vigente |
| RN-PRV-15 | O gabarito é a fonte da nota. A nota guarda o resultado, mas não guarda a versão do gabarito que a gerou. | — | FN-049 | Vigente (ver RN-RES-12) |

### Autoria e visibilidade

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-PRV-16 | A prova registra o criador (`criado_por`), o vínculo e o perfil usado na criação (`perfil_criacao`). Trocar de cargo depois não muda esse registro. | — | FN-041 | Vigente |
| RN-PRV-17 | Coordenador e diretor escolar leem as provas e as turmas de todos os professores da própria escola. | — | FN-041 | Vigente |
| RN-PRV-18 | O professor institucional lê as próprias provas. Ele lê as provas criadas por coordenação ou direção só se a prova estiver ligada a uma turma atribuída a ele. | — | FN-041 | Vigente |
| RN-PRV-19 | O professor institucional não edita nem copia uma prova institucional. Isso vale mesmo quando a mesma pessoa a criou em um vínculo de gestão. | — | FN-041 | Vigente |
| RN-PRV-20 | O professor autônomo (espaço pessoal) lê apenas as próprias provas. | — | FN-041 | Vigente |
| RN-PRV-21 | Compartilhar uma turma entre dois professores não compartilha as provas deles. | — | FN-041 | Vigente |
| RN-PRV-22 | Com o módulo DC1 ativo, o professor lê uma prova institucional só se houver disciplina da prova com indicação e atribuição vigentes dele em uma turma autorizada (ver RN-CUR-09). | — | FN-038 | Vigente |
| RN-PRV-23 | O aplicador só vê a prova no dia da aplicação e só envia cartões. Ele não acessa o conteúdo da prova. | — | FN-045 | Vigente |
| RN-PRV-24 | As telas mostram "Criada por" com o nome do criador. A API não expõe CPF nem e-mail dele. | — | FN-041 | Vigente |

---

## 2. Aplicação e correção (COR)

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-COR-01 | A prova precisa estar `publicada` para receber cartões. O aluno do cartão precisa pertencer a uma turma da prova. | RN-008.2 | FN-045 | Vigente |
| RN-COR-02 | O envio de uma foto usa fila e chave de idempotência (UUID). Repetir a mesma chave não cria outro cartão. | — | FN-002 | Vigente |
| RN-COR-03 | O cartão tem os estados `pendente`, `processando`, `lido` e `ambiguo`. | RN-008.2 | FN-045 | Vigente |
| RN-COR-04 | Uma leitura é ambígua quando a confiança é menor que 0,90 ou quando o motor marca dúvida. O limiar vale para todo o sistema. | RN-008.1 | FN-045 | Divergente (D-06) |
| RN-COR-05 | No motor, uma bolinha conta como marcada com preenchimento de 55% ou mais. Entre 30% e 55%, a marcação é dúvida. Abaixo de 8%, a questão é branca. | RN-008.1 | FN-045 | Vigente |
| RN-COR-06 | Uma questão sem marcação fica em branco, sem alternativa. Ela não é ambígua e não conta como acerto. | RN-008.4 | FN-045 | Vigente |
| RN-COR-07 | Toda foto passa por uma verificação de qualidade antes da leitura. A foto precisa mostrar os quatro marcadores de canto inteiros. A verificação também confere geometria, resolução e nitidez. | — | FN-047 | Vigente |
| RN-COR-08 | O worker repete a verificação de qualidade. Se o verificador falhar, o sistema responde 503 e mantém a correção atual. Uma foto recusada não gera nota. | — | FN-047 | Vigente |
| RN-COR-09 | O estado da prova é automático. Sem cartões, volta a `publicada`. Com cartões, fica `em_correcao`. Quando a correção conclui, fica `corrigida`. | RN-008.6 | FN-045 | Vigente |
| RN-COR-10 | A correção está concluída quando todo aluno esperado tem cartão `lido` com nota vigente. Aluno esperado é todo aluno ativo e distinto das turmas da prova. | RN-008.6 | FN-045 | Divergente (D-07) |
| RN-COR-11 | Sem alunos esperados, o percentual de conclusão é indefinido e a correção nunca conclui. | — | FN-045 | Vigente |
| RN-COR-12 | O acompanhamento mostra separados: alunos previstos, concluídos, sem cartão, em processamento e para revisão. Fotos duplicadas, não identificadas e propostas não aplicadas não aumentam a conclusão. | — | FN-047 | Vigente |
| RN-COR-13 | Quem tem `correcao.fechar` fecha a correção. Com a correção fechada, só quem tem `correcao.reabrir` envia, exclui ou altera cartões. | — | FN-045 | Vigente |
| RN-COR-14 | O envio de cartões usa `cartao.enviar`. O acompanhamento usa `correcao.acompanhar`. Resolver, revisar, vincular e excluir usam `correcao.alterar`. | — | FN-045 | Vigente |
| RN-COR-15 | Vincular um cartão a um aluno exige que o aluno pertença às turmas da prova. Um aluno que já tem correção exige o envio de outra foto, que gera uma tentativa (RN-LEI-09). | — | FN-045 | Vigente |

---

## 3. Leituras, revisão e tentativas (LEI)

### Ambiguidades e revisão

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-LEI-01 | O usuário resolve uma ambiguidade escolhendo A a E, `BRANCO` ou `ANULADA`. O sistema grava quem decidiu, o quê e quando em `ambiguidade_logs`. | RN-008.3 | FN-046 | Vigente |
| RN-LEI-02 | Quando não restam questões ambíguas, o cartão passa de `ambiguo` para `lido` e o sistema calcula a nota. | RN-008.3 | FN-045 | Vigente |
| RN-LEI-03 | Quem tem `correcao.alterar` resolve ambiguidades. Pelo catálogo, isso inclui administrador de rede, diretor escolar, coordenador e professor. | RN-008.3 | FN-012 | Divergente (D-08) |
| RN-LEI-04 | "Em branco" e "Anulada", na revisão, deixam a questão sem alternativa. Elas não pontuam e contam como erro. "Anulada" não altera o gabarito da prova. | — | FN-046 | Vigente |
| RN-LEI-05 | Só um cartão `lido` pode ser revisado depois da correção. A revisão recalcula a nota e grava um evento `revisada` no histórico. | RN-008.5 | FN-046 | Vigente |
| RN-LEI-06 | A fila de revisão mostra uma questão por vez, com o recorte da linha da questão. Responder salva e avança. Voltar troca a última resposta. Pular manda a questão ao fim da fila. | — | FN-046 | Vigente |

### Fotos e documento do cartão

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-LEI-07 | A foto do cartão chega por URL temporária assinada. Nenhum parâmetro pode ser acrescentado a essa URL. O sistema valida a assinatura e a autorização na abertura. | — | FN-046 | Vigente |
| RN-LEI-08 | A lista carrega as fotos sob demanda. O sistema emite os links de uma prova em lote, sem repetir a autorização por foto. A API não amplia o acesso do professor nesse processo. | — | FN-046 | Vigente |
| RN-LEI-09 | O visualizador do cartão completo usa PDF.js, em painel lateral no desktop e em modal no celular. Ele não oferece baixar nem imprimir. A proteção local contra cópia não impede captura de tela. | — | FN-046 | Vigente |

### Tentativas e histórico

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-LEI-10 | Cada nova foto identificada do mesmo aluno e prova cria uma tentativa numerada. Reenviar a mesma foto não aumenta o número. | — | FN-047 | Vigente |
| RN-LEI-11 | A nota vigente continua válida até alguém aplicar uma tentativa. Aplicar e descartar exigem `correcao.alterar`, motivo e a versão atual do cartão. | — | FN-047 | Vigente |
| RN-LEI-12 | O sistema guarda a foto, o hash SHA-256 e o conteúdo da tentativa em armazenamento privado. Registros selados não são atualizados nem apagados. | — | FN-047 | Vigente |
| RN-LEI-13 | A contagem de tentativas do aluno não inclui revisões de questões nem repetições técnicas. | — | FN-047 | Vigente |
| RN-LEI-14 | A seção "histórico de leituras" só aparece com mais de uma leitura. As cores do histórico usam a referência do gabarito congelada no registro. Registros antigos sem referência ficam neutros. | — | FN-047 | Vigente |
| RN-LEI-15 | Excluir um cartão exige `correcao.alterar` e a versão atual. A exclusão preserva o histórico em `cartao_tentativas`. | — | FN-045 | Vigente |

---

## 4. Resultados, notas e relatórios (RES)

### Nota e aprovação

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-RES-01 | A nota final é `acertos / total_questoes × nota_maxima`, com uma casa decimal. O total exclui as questões anuladas. | RN-009.1 | FN-045 | Vigente |
| RN-RES-02 | A nota é calculada por cartão, só para cartão com aluno vinculado e gabarito existente. A nota existente é atualizada, não duplicada. | RN-009.1 | FN-045 | Vigente |
| RN-RES-03 | O aluno é `aprovado` quando a nota atinge `meta_minima / 10 × nota_maxima`. Caso contrário, fica em `recuperacao`. Sem rede, a meta mínima é 6,0. | RN-009.2 | FN-045 | Vigente |
| RN-RES-04 | A rede configura `meta_media`, `meta_minima` (0 a 10) e `limiar_seges_min` (1 a 1440). | RN-009.5, RN-013.2 | FN-052 | Parcial (D-06) |
| RN-RES-05 | A média da turma e da prova é a média aritmética de `nota_final`. O relatório consolidado da turma usa 60% da nota máxima como corte de aprovação. | RN-009.3, RN-009.4 | FN-049 | Divergente (D-09) |
| RN-RES-06 | A seta de tendência (±0,1) e o agrupamento de acertos por tema não existem no código. | RN-009.6, RN-009.7 | FN-049 | Não implementada |
| RN-RES-07 | A nota guarda a turma de aplicação do cartão, e não a turma atual do aluno. Mudar o aluno de turma não move notas antigas. | — | FN-026 | Vigente |

### Análise, documentos e exportação

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-RES-08 | A análise por questão mostra percentuais agregados por turma e pelo conjunto das turmas da prova. Só educadores com acesso à prova veem esses percentuais. O aluno não os recebe. | — | FN-047 | Vigente |
| RN-RES-09 | Na análise, o denominador é o conjunto de resultados atuais com nota. Cada aluno entra uma vez. Respostas em branco contam como erro. Questões anuladas não têm percentual. | — | FN-047 | Vigente |
| RN-RES-10 | Todo PDF de resultado emitido recebe um registro de emissão com código aleatório de 128 bits, QR, hash SHA-256 do arquivo e selo HMAC. O registro não pode ser alterado nem apagado. | — | FN-047 | Vigente |
| RN-RES-11 | A validação do documento é pública, sem login, em `POST /api/v1/documentos/validar`. A resposta pública não revela aluno, nota nem emissor. Documentos emitidos antes do selo não têm validação. | — | FN-047 | Vigente |
| RN-RES-12 | A nota não guarda a versão do gabarito. Recalcular a nota atualiza o valor existente. Por isso o resultado antigo não prova qual regra o gerou. | — | FN-049 | Vigente |
| RN-RES-13 | O PDF em lote reúne os resultados de uma prova. Ele exige `relatorio.prova` e `resultado.compartilhar`, aceita o filtro `turma_id` e limita a 500 resultados e 120 s. O sistema revalida o acesso a cada aluno. | — | FN-049 | Vigente |
| RN-RES-14 | O PDF é gerado no servidor (ReportLab). A impressão por `window.print()` e a exportação de planilha não existem. | RN-014.2, RN-014.3 | FN-049 | Divergente (D-10) |
| RN-RES-15 | O aluno e o aplicador não geram o PDF em lote. O resultado individual exige `resultado.ver`. | RN-014.1 | FN-049 | Parcial (D-11) |
| RN-RES-16 | Os alertas e os indicadores dos painéis (cartões pendentes, escolas abaixo da meta, urgência de visita, alunos em atenção) pertencem aos painéis. Veja o [documento 10](10-paineis-e-editor.md). | RN-010.1 a RN-010.5 | FN-052, FN-053 | Parcial |

---

## 5. Turmas, alunos e matrículas (TUR)

### Estrutura e escola

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-TUR-01 | A hierarquia é rede, núcleo, escola, turma e aluno. A escola pertence a um núcleo (`escolas.nucleo_id` é obrigatório). | RN-002.1 | FN-033 | Vigente |
| RN-TUR-02 | O código INEP da escola tem 8 dígitos e é único. O tipo da rede é `estadual`, `municipal`, `federal` ou `privada`. | RN-003.1, RN-003.2 | FN-033 | Vigente |
| RN-TUR-03 | A sigla da escola é opcional, tem até 40 caracteres e pode se repetir. Ela entra na busca. | — | FN-033 | Vigente |
| RN-TUR-04 | Quem tem `escola.desativar` ativa e desativa a escola. A desativação não apaga dados históricos. | RN-002.2, RN-003.3 | FN-033 | Vigente |

### Turma

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-TUR-05 | A turma exige nome (até 50), série (até 50), turno (`manha`, `tarde`, `noite`, `integral`) e ano letivo (2020 a 2099). Sala, capacidade, etapa de ensino e observações são opcionais. | RN-004.2 | FN-033 | Vigente |
| RN-TUR-06 | O nome da turma não é único por escola e período. O código não valida duplicidade. | RN-004.1 | FN-033 | Divergente (D-12) |
| RN-TUR-07 | O status da turma ("em dia", "em recuperação", "com pendências") não existe como regra única. Os painéis calculam situações próprias. | RN-004.3 | FN-053 | Não implementada |
| RN-TUR-08 | A importação de alunos por planilha existe só como comando de linha (`ImportarAlunos`). A tela e a API de importação estão pendentes. | RN-004.4 | FN-034 | Parcial |
| RN-TUR-09 | O professor se vincula a turmas com `turma.vincular_professor`. O vínculo limita as provas e os alunos que ele vê. | RN-002.3 | FN-033 | Vigente |

### Aluno

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-TUR-10 | A matrícula do aluno é única dentro do espaço, e não dentro da rede. Tem até 20 caracteres e não segue o formato AAAA.NNNNN. | RN-005.1 | FN-033 | Divergente (D-13) |
| RN-TUR-11 | O aluno pertence a uma turma atual. Os cartões e as notas guardam a turma de aplicação. | RN-005.2 | FN-026 | Vigente |
| RN-TUR-12 | No cadastro escolar, a data de nascimento é obrigatória. No espaço pessoal, ela é proibida. | — | FN-033 | Vigente |
| RN-TUR-13 | O CPF do aluno é opcional, tem 11 dígitos, fica protegido (cifrado e com hash de busca) e é único por espaço. | — | FN-063 | Vigente |
| RN-TUR-14 | O aluno tem avatar de catálogo (1 a 36) ou foto. O espaço pessoal grava só os dados mínimos do aluno. | — | FN-066 | Vigente |
| RN-TUR-15 | O arquivamento do aluno e da turma exige `aluno.desativar` e `turma.desativar`. Ele preserva o histórico. | RN-006.3 | FN-033 | Vigente |
| RN-TUR-16 | O aluno não acessa o sistema hoje. A API bloqueia as provas para o perfil `aluno`. | RN-005.3 | FN-020 | Não implementada |
| RN-TUR-17 | A frequência e a sincronização com o SEGES existem só como tabela e limiar de alerta. Não há rotina de sincronização no código. | RN-005.4, RN-011.1 a RN-011.3 | FN-052 | Parcial |

### Troca de turma (MT1, parcial)

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-TUR-18 | Só coordenador, diretor escolar, vice-diretor e secretário escolar, com `aluno.gerir`, trocam o aluno de turma. O professor não troca. | — | FN-026 | Vigente |
| RN-TUR-19 | A troca ocorre entre turmas ativas da mesma escola e exige motivo e confirmação. A API retorna 409 se a turma do aluno mudou desde a leitura. | — | FN-026 | Vigente |
| RN-TUR-20 | A troca não move cartões nem notas. O sistema congela `turma_aplicacao_id` em cada cartão e registra a movimentação (`aluno_movimentacoes`) com motivo cifrado. | — | FN-026 | Vigente |
| RN-TUR-21 | A troca é bloqueada enquanto houver cartões em leitura (`pendente` ou `processando`) ou envios na fila no espaço. Histórico inconsistente também bloqueia a troca. | — | FN-026 | Vigente |
| RN-TUR-22 | A matrícula por período (matrícula e enturmação temporal), a movimentação entre escolas e o período letivo (bimestres, ano letivo ativo) ainda não existem. | RN-012.1, RN-012.2 | FN-026, FN-039 | Não implementada |

---

## 6. Disciplinas, áreas e currículo — DC1 (CUR)

O módulo DC1 só funciona com a configuração `disciplinas.ativo` ligada (`DISCIPLINAS_ATIVAS`). Com o módulo desligado, a prova mantém o caminho antigo, com disciplina em texto.

### Catálogo e áreas

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-CUR-01 | A referência nacional de disciplinas é só uma sugestão de nomenclatura. A identidade da disciplina é o cadastro local da escola, com ID. O sistema não compara texto para achar responsabilidade docente. | — | FN-038 | Vigente |
| RN-CUR-02 | A disciplina local tem nome, sigla opcional, referência opcional, situação e versão otimista. Inativar preserva o histórico. | — | FN-038 | Vigente |
| RN-CUR-03 | Quem tem `disciplina.gerir` (coordenador) administra disciplinas, áreas e atribuições. Direção e professores só leem. | — | FN-038 | Vigente |
| RN-CUR-04 | A indicação de professor exige vínculo `professor` aceito e vigente na mesma escola. É organização pedagógica, e não prova de formação. | — | FN-038 | Vigente |
| RN-CUR-05 | A área do conhecimento é própria da escola. O nome normalizado é único por espaço. A área não concede acesso a disciplinas, professores, turmas nem provas. | — | FN-038 | Vigente |
| RN-CUR-06 | Disciplina e área se relacionam por vínculos com período (`disciplina_area_vinculos`). Vincular abre um período. Desvincular encerra o período sem apagar o histórico. Revincular abre outro. | — | FN-038 | Vigente |
| RN-CUR-07 | Se a área e a disciplina já foram usadas juntas em uma prova, desvincular exige confirmação. O servidor confere o uso de novo na confirmação. | — | FN-038 | Vigente |
| RN-CUR-08 | Inativar uma área preserva os vínculos e impede novos vínculos. | — | FN-038 | Vigente |

### Turma, atribuição docente e provas

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-CUR-09 | O componente da turma liga uma disciplina local a uma turma, mesmo sem professor. A situação é independente do catálogo. A inativação é recusada com atribuições abertas. A reativação exige turma e catálogo ativos. | — | FN-038 | Vigente |
| RN-CUR-10 | A atribuição docente exige professor vigente, indicado na disciplina e autorizado na turma. Atribuir exige `disciplina.gerir` e `turma.vincular_professor`. | — | FN-038 | Vigente |
| RN-CUR-11 | A atribuição tem papel `titular`, `codocencia` ou `substituicao`. O período usa datas civis em America/Sao_Paulo, com início inclusivo e fim exclusivo. | — | FN-038 | Vigente |
| RN-CUR-12 | A atribuição nova não começa no passado. Sobreposição do mesmo professor no componente é recusada. Docentes diferentes sobrepostos exigem codocência ou substituição explícita. O sistema não encerra a atribuição anterior em silêncio. | — | FN-038 | Vigente |
| RN-CUR-13 | Uma atribuição futura pode ser cancelada. Uma atribuição em curso pode ser encerrada. Atribuição programada não dá acesso hoje. | — | FN-038 | Vigente |
| RN-CUR-14 | Uma prova institucional pode ter várias disciplinas locais. Cada disciplina precisa ser elegível em todas as turmas escolhidas. O professor precisa de indicação e atribuição vigentes em todas essas turmas. | — | FN-038 | Vigente |
| RN-CUR-15 | Gerir disciplinas não concede criar, editar nem publicar prova. Coordenação e direção mantêm as capacidades de prova que já tinham. | — | FN-038 | Vigente |
| RN-CUR-16 | Só um rascunho aceita mudança de turmas e disciplinas. A primeira publicação registra o currículo e as áreas usadas. Reabrir o rascunho não reclassifica a prova. | — | FN-038 | Vigente |
| RN-CUR-17 | A gravação do currículo revalida o contexto em transação própria, com isolamento `READ COMMITTED` e até três tentativas em caso de deadlock. Uma transação MariaDB aninhada é recusada. | — | FN-038 | Vigente |
| RN-CUR-18 | Uma prova antiga mantém a disciplina em texto e os IDs. Trocar disciplina ou turma exige seleção local explícita. O sistema não converte o texto em ID por semelhança de nome. | — | FN-038 | Vigente |
| RN-CUR-19 | A prova do espaço pessoal mantém o contrato próprio. Ela não escreve no catálogo da escola. | — | FN-038 | Vigente |
| RN-CUR-20 | Toda mudança de disciplinas, áreas e atribuições grava um evento imutável (`disciplina_eventos`) sem CPF, e-mail, notas nem fotos. | — | FN-038 | Vigente |
| RN-CUR-21 | O comando `PrepararSimuladosHomologacao` adapta os simulados de desenvolvimento e cria contas de teste. Ele serve só para a homologação e começa em modo de simulação. | — | FN-038 | Vigente |

---

## 7. Cópias entre espaços — T5 (COP)

A funcionalidade FN-024 está **substituída** como política, e o código continua publicado. O `docs/115` reposicionou o mecanismo como "Transferências" (FN-025, pendente). Esta seção registra o que vale hoje e o que mudou.

### O que continua vigente

| ID | Regra | FN | Situação |
|---|---|---|---|
| RN-COP-01 | A cópia cria registros com IDs e caminhos novos. Ela nunca move dados, nem altera o `espaco_id` da origem, nem compartilha uma linha entre espaços. | FN-024 | Vigente |
| RN-COP-02 | Seis flags controlam a cópia, todas `false` por padrão: `COPIAS_ATIVAS`, `COPIAS_INTERFACE_ATIVA`, `COPIAS_AUDITORIA_ENTREGA`, `COPIAS_EXECUCAO_ATIVA`, `COPIAS_CADASTRO_EXECUCAO_ATIVA` e `COPIAS_RESULTADOS_EXECUCAO_ATIVA`. | FN-024 | Vigente |
| RN-COP-03 | A cópia exige duas decisões persistidas, uma de cada lado (origem e destino), tomadas em contextos separados. Uma mesma pessoa com dois vínculos decide duas vezes. Nenhuma decisão soma poderes do outro vínculo. | FN-024 | Vigente |
| RN-COP-04 | As capacidades são `copia.autorizar_origem` e `copia.autorizar_destino`. Elas pertencem a `admin_rede`, `dir_escolar` e ao professor autônomo. Perfis internos e demais perfis não as recebem. | FN-012 | Vigente |
| RN-COP-05 | Os espaços de conteúdo são escola e pessoal. A rede é contexto de gestão, e não destino. Origem igual ao destino é recusada. Espaço, cliente ou vínculo suspenso, expirado ou revogado bloqueia a cópia. | FN-024 | Vigente |
| RN-COP-06 | Aceitar convite, sair da escola ou pagar não copia nada. A cópia só ocorre por seleção explícita, prévia e duas decisões. | FN-024 | Vigente |
| RN-COP-07 | Existem três modalidades: materiais (provas e gabaritos), cadastro mínimo (turma e alunos) e resultados identificados (acervo histórico). Cada modalidade tem política, evidência e executor próprios. A seleção não se amplia por dependência. | FN-024 | Vigente |
| RN-COP-08 | Materiais usam a projeção `material-1` (título, disciplina, série, quantidades, nota máxima, regra de anulação, língua e gabarito). Nenhum ID escolar, aluno, turma, nota, cartão, caminho ou link entra. Gabarito incompleto bloqueia a prévia. | FN-024 | Vigente |
| RN-COP-09 | A origem declara, depois de revisar, que o material não contém dados pessoais. Essa declaração é uma revisão humana vinculada ao digest da seleção. Ela não classifica o texto como anônimo. | FN-024 | Vigente |
| RN-COP-10 | A cópia de materiais cria prova em rascunho, novo gabarito e novas questões no destino. Datas, estado de publicação e geração de cartão são zerados. A edição posterior do destino é preservada em reentregas. | FN-024 | Vigente |
| RN-COP-11 | O cadastro mínimo copia turma (nome, série, turno, ano, situação) e aluno (nome, matrícula, avatar, situação). Ele nunca copia CPF, responsável, gênero, foto, cartões, notas nem links. Nascimento só vai para destino escolar. | FN-024 | Vigente |
| RN-COP-12 | A matrícula só é única dentro do espaço. Uma colisão sem mapa de origem comprovado bloqueia o item para conciliação. O sistema não mescla por nome, CPF nem matrícula. | FN-024 | Vigente |
| RN-COP-13 | O cadastro e os resultados exigem evidência: texto cifrado de até 64 KiB, com responsável, validade e confirmação humana de aplicabilidade. URL, pagamento ou `contrato_referencia` não bastam. O destino nunca vê o documento. | FN-024 | Vigente |
| RN-COP-14 | Os resultados identificados formam um acervo separado (`acervo_resultados`), cifrado, de 1 a 20 notas por pacote. Eles não entram nas tabelas operacionais de notas. O pacote declara `legado_sem_versao_original`. | FN-024 | Vigente |
| RN-COP-15 | O destino se indica por código aleatório de 256 bits, válido por 15 minutos, guardado só como SHA-256. Cada vínculo tem no máximo cinco códigos livres. O código aceita um único lote. | FN-024 | Vigente |
| RN-COP-16 | A decisão tem prazo máximo de 24 horas na interface. Uma decisão não vale além da validade da evidência. | FN-024 | Vigente |
| RN-COP-17 | A execução usa intenção durável, caixa de saída transacional (`copias_outbox`) e fila `baixa`. Cada unidade é preparada em staging cifrado e publicada em outra transação, com revalidação. O sistema repete a unidade sem duplicar. | FN-024 | Vigente |
| RN-COP-18 | O lote publicado não é revertido. A revogação interrompe o restante e preserva o que já foi publicado. Um acervo de resultados publicado não admite revogação. | FN-024 | Vigente |
| RN-COP-19 | O limite é de 100 provas por lote de materiais, 100 entidades por lote de cadastro e 20 notas por pacote de resultados. A fila publica no máximo 20 intenções por minuto. | FN-024 | Vigente |
| RN-COP-20 | Cada evento da cópia (solicitação, revisão, decisão, bloqueio, publicação) gera um recibo na caixa de saída e um evento de domínio na auditoria AU1, sem dados escolares. | FN-004 | Vigente |
| RN-COP-21 | A tela "Cópias" tem a entrada com as ações "Enviar uma cópia" e "Receber uma cópia" e as modalidades "Provas e gabaritos", "Turmas e alunos" e "Histórico de resultados". O texto evita "transferência" e "sincronização". | FN-024 | Vigente |

### O que mudou com o docs/115

| ID | Regra | FN | Situação |
|---|---|---|---|
| RN-COP-22 | O pedido original ("copiar prova") é duplicação pedagógica no mesmo espaço. Ela pertence a FN-042 (CP1 e CP2) e não usa o mecanismo T5. | FN-042 | Substituída |
| RN-COP-23 | A política de capacidades genéricas T5 do Diretor Escolar e do Administrador da Rede está **substituída**. A nova matriz (solicitar, confirmar, autorizar e executar, com delegação e execução por caso) pertence a TR1 e TE1 em FN-025. | FN-025 | Substituída (D-14) |
| RN-COP-24 | A transferência de aluno entre escolas, com corte de histórico e snapshot, é o fluxo TE1. Ela não existe no código. | FN-025 | Não implementada |
| RN-COP-25 | A continuidade do professor ao sair da escola passa a ser um item de PA1-D. O avaliador `ContinuidadeCopia` (D1) existe, mas só orienta. Ele nunca autoriza nem executa. | FN-027 | Parcial |
| RN-COP-26 | A solicitação de cópia pelo professor institucional, a estatística anônima, a complementação de nascimento e a limpeza de staging e de caixa de saída não foram feitas. | FN-024 | Não implementada |

### Documentos de execução T5 (histórico)

Os documentos `77` a `98` e `114` registram a execução das subfases T5-A a T5-E. Eles não trazem regra vigente nova além das listadas acima.

| Subfase | Entrega | Documento antigo |
|---|---|---|
| Planejamento | Modelo, escopo, matriz dos seis casos | 77 |
| A1 a A3 | Elegibilidade, lotes e decisões, caixa de saída e auditoria de domínio | 78, 79, 80 |
| B1 a B3 | Prévia e revisão de materiais, execução, recuperação e volume | 81, 82, 83 |
| C, C2, C3 | Cadastro mínimo: prévia, autorizações e conciliação, execução | 84, 85, 86 |
| D, D2, D2b, D3 | Continuidade, contrato de resultados, pacote histórico, publicação | 87, 88, 89, 90 |
| E1 a E3 | Acompanhamento, seleção, destinatário, cadastro, resultados e operação | 91 a 98 |
| Revisão de experiência | Entrada reorganizada e linguagem | 114 |

---

## 8. Duplicação de provas — CP1 e CP2 (DUP)

| ID | Regra | RN antiga | FN | Situação |
|---|---|---|---|---|
| RN-DUP-01 | `POST /api/v1/provas/{id}/copiar` recebe `titulo` (até 255) e a chave UUID da intenção. Exige `prova.duplicar_propria`. | — | FN-042 | Vigente |
| RN-DUP-02 | A capacidade `prova.duplicar_propria` pertence ao professor e ao professor autônomo. O autor da origem precisa ser o próprio ator. Uma prova apenas visível por turma não é elegível. | — | FN-042 | Vigente |
| RN-DUP-03 | A cópia nasce como rascunho no mesmo espaço e na mesma escola. Ela copia só a configuração e as questões do gabarito atual. O gabarito da cópia não está publicado. | — | FN-042 | Vigente |
| RN-DUP-04 | A cópia nunca leva turma, aluno, cartão, QR, resposta, nota, data de aplicação, fechamento, token ou link. | — | FN-042 | Vigente |
| RN-DUP-05 | A tabela `prova_duplicacoes` registra origem imediata, raiz, autor original, ator, vínculo e versão do contrato. Esses campos são somente leitura. | — | FN-042 | Vigente |
| RN-DUP-06 | A chave é única por vínculo. A mesma intenção, origem e título devolve a mesma prova. Payload diferente com a mesma chave retorna 409. Uma nova intenção cria outra variante. | — | FN-042 | Vigente |
| RN-DUP-07 | A cópia só gera cartões depois de receber turmas novas e gabarito publicado. A edição de status não publica a cópia. | — | FN-042 | Vigente |
| RN-DUP-08 | A origem aparece no detalhe da cópia como "Copiada de". O link só aparece se o usuário ainda puder ler a origem. | — | FN-042 | Vigente |
| RN-DUP-09 | A interface oferece a ação "Copiar" na lista e no detalhe da prova. Ela abre um modal nativo com origem, explicação e novo nome. Não há página dedicada. | — | FN-042 | Vigente |
| RN-DUP-10 | CP2 (o coordenador entrega uma cópia a outro professor da escola) está planejada. A chave `prova.duplicar_para_professor` não existe no catálogo de permissões. | — | FN-042 | Não implementada |
| RN-DUP-11 | Em CP2, o destinatário receberá um rascunho próprio, sem turmas do autor e sem acesso à prova original. O sistema registrará origem, coordenador, destinatário, motivo e digest, e notificará no sistema. | — | FN-042 | Não implementada |

---

## 9. Catálogo nacional de instituições — CI1 (CAT)

O catálogo é uma referência pública compartilhada. Ele não é contratação, vínculo nem acesso.

### Fronteiras

| ID | Regra | FN | Situação |
|---|---|---|---|
| RN-CAT-01 | O catálogo, o espaço operacional, a atuação declarada do professor e o vínculo institucional são quatro coisas distintas. Selecionar uma instituição não cria cliente, espaço, vínculo, turma ou acesso. | FN-031 | Vigente |
| RN-CAT-02 | O catálogo usa as tabelas `catalogo_*`. A tabela legada `censo_escolas` continua para consulta e compatibilidade. O sistema não insere as referências nacionais em `escolas`. | FN-031 | Vigente |
| RN-CAT-03 | Cada instituição tem ID interno estável. O identificador externo é `(namespace, código)` e é único: `inep_escola` (8 dígitos) e `inep_ies`. O sistema nunca inventa código oficial. Cadastro manual sem código usa o namespace `plataforma`. | FN-031 | Vigente |
| RN-CAT-04 | Nome, CNPJ e endereço não fundem instituições. Relações de sede, escola vinculada e IES ofertante não provam identidade e não fundem cadastros. | FN-031 | Vigente |
| RN-CAT-05 | O tipo da instituição vem da fonte: `escola`, `faculdade`, `centro_universitario`, `universidade`, `instituto_federal` ou `cefet`. O sistema não infere o tipo pelo nome. A dependência administrativa é um filtro separado. | FN-031 | Vigente |
| RN-CAT-06 | Para IES, a unidade é a sede administrativa. O catálogo não declara campi nem polos que a fonte não traz. | FN-031 | Vigente |

### Carga, publicação e consulta

| ID | Regra | FN | Situação |
|---|---|---|---|
| RN-CAT-07 | A carga nacional é um procedimento de operador (`g360:catalogo-preparar`, `-promover`, `-relacionar`, `-conciliar`, `-validar`). Não há endpoint público de carga. | FN-031 | Vigente |
| RN-CAT-08 | A carga exige `--todas-ufs`, o SHA-256 esperado e cobertura das 27 UFs. Uma fonte inválida é recusada antes da primeira gravação. Repetir o mesmo arquivo não duplica. | FN-031 | Vigente |
| RN-CAT-09 | Os dados importados são imutáveis (gatilhos contra UPDATE e DELETE). Correções geram edição nova. Uma edição só vale depois de publicada por completo. | FN-031 | Vigente |
| RN-CAT-10 | Uma publicação mais antiga não substitui a vigente. Para o mesmo ano com hash diferente, o operador usa `--substituir` com o ID da publicação vigente e `--motivo`. | FN-031 | Vigente |
| RN-CAT-11 | O ajuste manual tem precedência sobre a fonte oficial por unidade e não é apagado por edição nova. A interface mostra a origem como "Cadastro da plataforma". | FN-031 | Vigente |
| RN-CAT-12 | A consulta usa só as edições vigentes e fixa as edições durante a resposta. `GET /api/v1/catalogo/{ufs,municipios,instituicoes}` pagina por 20 (máximo 50). O município deve pertencer à UF. Busca por código exato dispensa geografia. | FN-031 | Vigente |
| RN-CAT-13 | A consulta não devolve espaço, cliente, contrato, usuário, aluno nem contato privado. Ausência de resultado responde 200. Serviço indisponível responde 503. Filtro inválido responde 422. | FN-031 | Vigente |
| RN-CAT-14 | Uma escola extinta ou paralisada continua no histórico e na busca explícita. Ela não é elegível para nova seleção. | FN-031 | Vigente |
| RN-CAT-15 | A busca por autocompletar (F5) está adiada. | FN-032 | Não implementada |

### Seleção pessoal e solicitações

| ID | Regra | FN | Situação |
|---|---|---|---|
| RN-CAT-16 | A interface `Minhas instituições` depende de `CATALOGO_INTERFACE_ATIVA`. Só o professor autônomo, com `catalogo.consultar` e `catalogo.referencia_pessoal.gerir`, consulta e declara instituições. | FN-031 | Vigente |
| RN-CAT-17 | A referência pessoal é única por espaço e unidade. Selecionar exige confirmação explícita e a edição ainda vigente (409 se mudou). Repetir a seleção ativa devolve a mesma referência. Arquivar preserva o histórico. | FN-031 | Vigente |
| RN-CAT-18 | O professor pede um cadastro ausente em `catalogo/minhas-solicitacoes`, com chave UUID. O pedido é privado e vai só à fila interna de Suporte e Desenvolvimento. Gestão escolar, núcleo e secretaria não o veem. | FN-031 | Vigente |
| RN-CAT-19 | Os estados da solicitação são `enviada`, `em_analise`, `aguardando_complementacao`, `aprovada`, `indeferida` e `cancelada`. Toda transição exige a versão atual e grava um evento imutável. Aprovar não cria vínculo, referência pessoal nem acesso. | FN-031 | Vigente |
| RN-CAT-20 | O aviso de decisão aparece no sistema. O catálogo não envia e-mail. | FN-031 | Vigente |
| RN-CAT-21 | Suporte e Desenvolvimento analisam e mantêm o catálogo global. Diretor escolar, diretor de núcleo e administrador de rede mantêm só unidades com autoridade comprovada (`catalogo.manter_escopo`). Município, nome ou código informados não comprovam autoridade. | FN-031 | Vigente |
| RN-CAT-22 | Nenhuma capacidade do catálogo dá acesso a dados acadêmicos. O perfil `secretario_educacao` não recebe capacidade do catálogo enquanto o regime de vínculos (VI1) não o contemplar. | FN-031 | Vigente |

### Documentos de execução CI1 (histórico)

| Subfase | Entrega | Documento antigo |
|---|---|---|
| Plano e pesquisa | Fontes oficiais, modelo e experiência | 116 |
| CI1-A | Inventário e contrato | 119 |
| CI1-B2, B3 | Preparação retomável e publicação por edição | 120, 121 |
| CI1-C | Relações declaradas, cobertura e lacunas | 122 |
| CI1-D | Seleção nacional e instituições do espaço pessoal | 124 |
| CI1-E | Solicitações privadas e manutenção; revisão de design | 125, 126 |
| CI1-F | Validação integrada (concorrência, atualização, Chrome) | 130 |

---

## 10. Escolas independentes — EI1 (EIN)

A funcionalidade FN-029 **não está implementada**. As regras abaixo são decisões do responsável, vigentes como requisito. O código ainda exige `escolas.nucleo_id`.

| ID | Regra | FN | Situação |
|---|---|---|---|
| RN-EIN-01 | A escola independente (também chamada "escola autônoma" na adesão direta) não é espaço pessoal de professor nem um cargo novo. É uma modalidade institucional e comercial. Os cargos escolares seguem a matriz aprovada. | FN-029 | Não implementada |
| RN-EIN-02 | Três dimensões são independentes: associação institucional (rede), contratação (cliente responsável) e autorização de acesso. Pertencer a uma rede não dá aos gestores dela acesso ao espaço contratado pela escola. | FN-029 | Não implementada |
| RN-EIN-03 | Rede e núcleo não são obrigatórios para a escola independente. O sistema não cria rede nem núcleo fictícios. | FN-029 | Não implementada |
| RN-EIN-04 | A escola não cria rede no formulário. Ela seleciona uma rede conferida ou pede o cadastro ao suporte. Só Suporte ou Desenvolvimento cria redes nesse fluxo. | FN-029 | Não implementada |
| RN-EIN-05 | Escola privada sem rede adere direto. Escola privada com rede declarada pode aderir direto, mesmo sem contrato ou licença da rede. | FN-029 | Não implementada |
| RN-EIN-06 | Escola pública com instituição superior identificada informa essa instituição e é orientada a pedir cadastro por ela. Se insistir em contratar direto, precisa de liberação explícita de Suporte ou Desenvolvimento, com motivo, ator, data e alcance. | FN-029 | Não implementada |
| RN-EIN-07 | A instituição superior só acessa os dados da escola autônoma se assumir formalmente a responsabilidade financeira, por fluxo próprio e auditável. | FN-029 | Não implementada |
| RN-EIN-08 | Na transição de responsabilidade financeira, o sistema não devolve valores já pagos. Essa política vale também para escola, instituição e professor. | FN-029, FN-068 | Não implementada |
| RN-EIN-09 | Trocar rede, categoria administrativa ou instituição superior na edição não contorna bloqueios. Mudanças relevantes são conferidas, auditadas e valem daí em diante. Elas não reescrevem documentos, provas nem históricos. | FN-029 | Não implementada |
| RN-EIN-10 | O cabeçalho dos documentos mostra a logo da rede à esquerda e a da escola à direita. A escola sem rede mostra só a própria logo à esquerda. O professor autônomo mostra a marca do Gabarito360 com a indicação de uso autônomo. | FN-071 | Vigente |

Plano de execução EI1-A a EI1-G: veja o [planejamento de FN-029](../funcionalidades/029-escola-independente/planejamento.md). O documento antigo `163` fica como histórico.

---

## 11. Regras gerais herdadas do docs/06 (GER)

Estas regras pertencem a outros donos. A tabela registra a situação atual e aponta o dono.

| ID | Regra | RN antiga | FN | Situação | Dono |
|---|---|---|---|---|---|
| RN-GER-01 | A API valida o formato do e-mail. O sistema não exige domínio institucional. | RN-001.1 | FN-011 | Vigente | [04](04-identidade-acesso-e-permissoes.md) |
| RN-GER-02 | Uma pessoa pode ter vários vínculos (perfil, escola e espaço). O token e o contexto escolhido definem as permissões ativas. A regra "um perfil único por usuário" foi superada pelo modelo de vínculos. | RN-001.2 | FN-015 | Substituída | [04](04-identidade-acesso-e-permissoes.md) |
| RN-GER-03 | O escopo é validado na API. O usuário não altera o próprio escopo. | RN-001.3, RN-002.3 | FN-012 | Vigente | [04](04-identidade-acesso-e-permissoes.md) |
| RN-GER-04 | O acesso nasce de convite aceito ou de cadastro feito pelo suporte. Não há aprovação manual nem autocadastro. | RN-001.4 | FN-015 | Substituída | [04](04-identidade-acesso-e-permissoes.md) |
| RN-GER-05 | A sessão usa token Sanctum. A opção "manter conectado" e o refresh token seguem a configuração de autenticação. | RN-001.5 | FN-011 | Parcial | [04](04-identidade-acesso-e-permissoes.md) |
| RN-GER-06 | A rede tem `modalidade` (`institucional` ou `individual`). A criação automática de rede individual para o professor autônomo, e a absorção de rede individual por rede institucional, não existem. | RN-002.4, RN-016.1 a RN-016.4 | FN-030 | Substituída | [07](07-clientes-espacos-e-vinculos.md) |
| RN-GER-07 | A gestão de membros usa convites e vínculos. A desativação de membro encerra o acesso e preserva o histórico. | RN-006.1 a RN-006.3 | FN-015 | Vigente | [04](04-identidade-acesso-e-permissoes.md) |
| RN-GER-08 | Os parâmetros da rede são meta média, meta mínima e limiar de atraso do SEGES. O limiar de confiança OMR e o número padrão de alternativas não são configuráveis. | RN-013.1 a RN-013.3 | FN-052 | Divergente (D-06) | — |
| RN-GER-09 | A assinatura e o checkout automático foram substituídos por contratação assistida pelo Fale Conosco. A API de checkout responde 409. As tabelas `planos` e `assinaturas` existem, com os planos individual mensal (R$ 9,99), individual anual (R$ 99,90) e institucional (R$ 7,50 por licença). | RN-015.1 a RN-015.5 | FN-030, FN-068 | Substituída | — |
| RN-GER-10 | O ano letivo é o campo `ano_letivo` da turma. Bimestres e "um ano letivo ativo por rede" não existem. | RN-012.1, RN-012.2 | FN-039 | Não implementada | — |

---

## Mapa das regras antigas (docs/06)

| RN antiga | Nova regra |
|---|---|
| RN-001.1 a RN-001.5 | RN-GER-01 a RN-GER-05 |
| RN-002.1 | RN-TUR-01 |
| RN-002.2 | RN-TUR-04 |
| RN-002.3 | RN-GER-03, RN-TUR-09 |
| RN-002.4 | RN-GER-06 |
| RN-003.1 a RN-003.3 | RN-TUR-02, RN-TUR-04 |
| RN-004.1 a RN-004.4 | RN-TUR-06, RN-TUR-05, RN-TUR-07, RN-TUR-08 |
| RN-005.1 a RN-005.4 | RN-TUR-10, RN-TUR-11, RN-TUR-16, RN-TUR-17 |
| RN-006.1 a RN-006.3 | RN-GER-07, RN-TUR-15 |
| RN-007.1 a RN-007.8 | RN-PRV-01 a RN-PRV-13 |
| RN-008.1 a RN-008.6 | RN-COR-04 a RN-COR-10, RN-LEI-01 a RN-LEI-05 |
| RN-009.1 a RN-009.7 | RN-RES-01 a RN-RES-07 |
| RN-010.1 a RN-010.5 | RN-RES-16 |
| RN-011.1 a RN-011.3 | RN-TUR-17 |
| RN-012.1, RN-012.2 | RN-GER-10, RN-TUR-22 |
| RN-013.1 a RN-013.3 | RN-RES-04, RN-GER-08 |
| RN-014.1 a RN-014.3 | RN-RES-14, RN-RES-15 |
| RN-015.1 a RN-015.5 | RN-GER-09 |
| RN-016.1 a RN-016.4 | RN-GER-06 |

---

## Casos de uso do docs/07

Os doze casos de uso do `docs/07` foram distribuídos nos `casos-de-uso.md` de cada funcionalidade. A tabela confirma onde cada um está.

| UC antigo | Caso de uso | Funcionalidade dona | Regras deste documento |
|---|---|---|---|
| UC-001 | Autenticar no sistema | [FN-011](../funcionalidades/011-autenticacao-conta/casos-de-uso.md) | RN-GER-01 a RN-GER-05 |
| UC-002 | Cadastrar escola | [FN-033](../funcionalidades/033-estrutura-escolar/casos-de-uso.md) | RN-TUR-01 a RN-TUR-04 |
| UC-003 | Criar prova e gabarito | [FN-040](../funcionalidades/040-provas-gabaritos/casos-de-uso.md) | RN-PRV-01 a RN-PRV-15 |
| UC-004 | Acompanhar correção de prova | [FN-045](../funcionalidades/045-omr-correcao/casos-de-uso.md) e [FN-046](../funcionalidades/046-revisao-leituras/casos-de-uso.md) | RN-COR-01 a RN-COR-15, RN-LEI-01 a RN-LEI-15 |
| UC-005 | Visualizar resultado individual | [FN-047](../funcionalidades/047-omr-qualidade-resultado/casos-de-uso.md) | RN-RES-01 a RN-RES-12 |
| UC-006 | Consultar relatório de prova | [FN-049](../funcionalidades/049-relatorios-resultados/casos-de-uso.md) | RN-RES-05, RN-RES-13 |
| UC-007 | Monitorar desempenho (painéis) | [FN-052](../funcionalidades/052-dashboards-perfil/casos-de-uso.md) | RN-RES-16 |
| UC-008 | Gerenciar equipe da escola | [FN-012](../funcionalidades/012-permissoes-motor/casos-de-uso.md) e [FN-015](../funcionalidades/015-vinculos-contexto/casos-de-uso.md) | RN-GER-07 |
| UC-009 | Agendar visita pedagógica | [FN-058](../funcionalidades/058-visitas-pedagogicas/casos-de-uso.md) | — (pendente) |
| UC-010 | Exportar relatório ou PDF | [FN-049](../funcionalidades/049-relatorios-resultados/casos-de-uso.md) e [FN-047](../funcionalidades/047-omr-qualidade-resultado/casos-de-uso.md) | RN-RES-10 a RN-RES-15 |
| UC-011 | Editar perfil | [FN-019](../funcionalidades/019-perfil-configuracoes/casos-de-uso.md) | — |
| UC-012 | Configurar sistema | [FN-019](../funcionalidades/019-perfil-configuracoes/casos-de-uso.md) | RN-RES-04 |

## Documentos antigos tratados como histórico

Estes documentos registram a execução por subfase. O planejamento da funcionalidade correspondente e o Git guardam o detalhe.

| Documentos antigos | Assunto | Funcionalidade |
|---|---|---|
| 24, 25, 152 a 155, 157 | Fotos sob demanda, fila de revisão, incidente 500, cartão inteiro, modal e leitor PDF | [FN-046](../funcionalidades/046-revisao-leituras/planejamento.md) |
| 158, 159, 160 | OMR1, OMR2 e OMR3 | [FN-047](../funcionalidades/047-omr-qualidade-resultado/planejamento.md) |
| 123 | PDF de resultados em lote | [FN-049](../funcionalidades/049-relatorios-resultados/planejamento.md) |
| 132 | Autoria das provas e "Meu espaço" | [FN-041](../funcionalidades/041-autoria-provas/planejamento.md) |
| 118 | CP1 | [FN-042](../funcionalidades/042-duplicacao-provas/planejamento.md) |
| 141 a 148, 150, 151, 156 | DC1 | [FN-038](../funcionalidades/038-disciplinas-curriculo/planejamento.md) |
| 77 a 98, 114 | T5 | [FN-024](../funcionalidades/024-copias-entre-espacos/planejamento.md) |
| 116, 119 a 122, 124 a 126, 130 | CI1 | [FN-031](../funcionalidades/031-catalogo-instituicoes/planejamento.md) |
| 163 | EI1 | [FN-029](../funcionalidades/029-escola-independente/planejamento.md) |

---

## Divergências encontradas

Cada item compara um documento antigo com o código. Vale o código.

| ID | Documento antigo | Código |
|---|---|---|
| D-01 | `docs/06` RN-007.3 permite ao administrador corrigir o gabarito antes de qualquer cartão e prevê nova versão da prova. | `GabaritoController::salvar` recusa qualquer edição após a publicação (422). Não existe exceção do administrador nem versão do gabarito. |
| D-02 | RN-007.4 e RN-007.6 dizem "padrão 5 alternativas" e nota máxima de 1 a 100. | A API exige `num_alternativas` na criação (sem padrão). A nota máxima aceita de 0,5 a 100. O padrão 10 vem do banco. |
| D-03 | RN-007.7 prevê pesos personalizados só pós-MVP. | A API aceita `personalizado` e grava o peso por questão. `CalculadoraNota` ignora o peso e conta acertos. |
| D-04 | RN-007.5 diz que a questão anulada conta como acerto para todos. | Questão anulada no gabarito sai do total. Questão com várias marcações e `anular_se_todas` fica sem resposta e conta como erro. "Anulada" na revisão também conta como erro. |
| D-05 | RN-007.8 registra a data de aplicação por turma. | Existe uma data por prova (`provas.data_aplicacao`). A tabela `prova_turmas` liga prova e turma. FN-059 trata o agendamento por turma. |
| D-06 | RN-008.1 e RN-013.2 dizem que o limiar de confiança OMR é configurável (padrão 90%). | O limiar 0,90 é fixo em `GravadorLeituras` e em `omr/ler_cartao.py`. A configuração da rede não o contém. |
| D-07 | RN-008.6 conclui a prova quando todos os cartões estão lidos. | `ProgressoCorrecao` conclui quando todo aluno esperado tem cartão lido com nota vigente. |
| D-08 | RN-008.3 limita a resolução de ambíguos a professor da prova e coordenador. | A permissão `correcao.alterar` inclui administrador de rede, diretor escolar, coordenador e professor. O escopo da prova restringe cada um. |
| D-09 | RN-009.2 usa a meta mínima da rede. | O relatório consolidado da turma usa 60% da nota máxima como corte de aprovação, sem ler `meta_minima`. O cálculo da nota usa `meta_minima`. |
| D-10 | RN-014.2 gera PDF por `window.print()`. | O PDF individual, pedagógico e em lote é gerado no servidor, com registro de emissão. |
| D-11 | RN-014.1 e RN-005.3 deixam o aluno exportar o próprio resultado. | O perfil `aluno` não acessa provas na API. O acesso próprio do aluno é FN-020 (pendente). |
| D-12 | RN-004.1 exige nome de turma único por escola e período. | Nenhuma validação nem índice garante essa unicidade. |
| D-13 | RN-005.1 define a matrícula única por rede, no formato AAAA.NNNNN. | A unicidade é por espaço (após o corte de espaços). O formato é livre, com até 20 caracteres. |
| D-14 | `docs/115` manda remover a execução genérica T5 do Diretor Escolar. | `CatalogoPermissoes` ainda concede `copia.autorizar_origem` e `copia.autorizar_destino` a `admin_rede` e `dir_escolar`. As flags de cópia ficam `false` por padrão. |
| D-15 | `docs/77` a `docs/99` dizem que a interface T5 precisa de conferência visual. FN-024 registra produção com flags ativas desde 02/11. | As flags de `config/copias.php` valem `false` por padrão. O estado real das flags em cada servidor não foi conferido nesta migração. |
| D-16 | `docs/143` cita um professor de teste por disciplina, em homologação. | O comando `PrepararSimuladosHomologacao` existe. Os dados da homologação não foram conferidos. Este documento não registra as contas. |
