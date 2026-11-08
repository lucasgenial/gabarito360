> **Decisão mais recente — preenchimento lateral:** após homologação do PR215 (f70c99f), o responsável solicita redistribuição horizontal entre vizinhos: resize absorve/libera largura nos demais; inserir um card ao lado/entre outros redimensiona o conjunto para ocupar a largura disponível. Alturas permanecem independentes. Pins, mínimos/máximos, seções, KPIs fixos, Salvar/Cancelar e alternativa mobile continuam preservados. Esta decisão substitui somente a regra anterior de larguras independentes/sem expansão obrigatória. Plano e gates atuais ao fim do179.

> **Diretriz atual após aprovação doPR214:** a apresentação usa GridStack nativo em modo compacto, sem organização por linhas ou altura comum. w/h são individuais, pins invioláveis, sem esticar para preencher12. Save/Cancel, catálogo e escopos permanecem. altura_livre é aceito apenas por compatibilidade do transporte, sem botão/efeito; altura_desejada é preservado nos mapas legados e atualizado para h ao redimensionar o próprio widget. Trechos anteriores de linhas uniformes são históricos.

# Preferências pessoais dos dashboards

Contrato aditivo, sem alteração dos endpoints de turmas, resultados ou relatórios. Corresponde à persistência pessoal solicitada na correção dos painéis; não implementa agenda, indicadores novos nem exemplos operacionais. Base de implementação: main `0e6e906`.

## Acesso e isolamento

`GET /api/v1/paineis/preferencias/{painel}` e `PUT` no mesmo endereço, onde `painel` é `turmas` ou `turma`. Exigem autenticação normal, senha regular, vínculo explícito válido e `turma.ver`. Perfis internos e acessos legados sem vínculo não são habilitados por esta API. O Bearer contextual continua somente no servidor BFF.

A chave de armazenamento é usuário + vínculo + espaço + perfil ativo + painel + recurso, derivados do contexto revalidado. Para `turma`, o header técnico `X-G360-Painel-Turma` é obrigatório e contém um único ID inteiro positivo canônico; a API autoriza essa turma por `Escopo::turmas` antes de consultar preferências. Para `turmas`, o header é proibido e o recurso é o contexto agregado. IDs inválidos recebem422; turma fora do alcance recebe404. O header seleciona um recurso, não concede autoridade. Uma turma, vínculo ou perfil não reutiliza a configuração de outro. O BFF deriva o header exclusivamente do alvo da referência UR4 validada, sem usar seleção global de turma, corpo ou header arbitrário do navegador. Assim, duas abas de turmas distintas permanecem independentes. Campos de autoridade no corpo e parâmetros de query são recusados422.

Catálogo técnico finito: IDs e seções acordados com o BFF, sem títulos, nomes, notas, exemplos ou endpoints. Os modelos pedagógicos exigem `relatorio.turma` e têm veto de secretário/auxiliar; os dois widgets `alunos-destaques` e `alunos-atencao`, na seção `estudantes`, exigem também `aluno.ver` e `resultado.ver` e têm veto de aluno/aplicador. Conselho exige `turma.vincular_professor`; documentos administrativos exigem `aluno.ver`. Persistir um ID não concede permissão. GET filtra IDs que deixaram de ser autorizados, inclusive o antigo `alunos-recorte`, sem regravar o mapa armazenado; PUT recusa IDs não autorizados. Não há nova permissão nem mudança da matriz existente.

## Resposta

Envelope padrão `data` (catálogo e padrão abaixo são recortes ilustrativos; a resposta real contém todos os IDs autorizados):

```json
{
  "versao": 0,
  "apresentacao": "tabela",
  "layouts": {"desktop": [], "mobile": []},
  "catalogo": [{
    "id": "cadastros-turno", "section": "analises",
    "desktop": {"minW": 4, "maxW": 12, "minH": 8, "maxH": 20, "defaultW": 6, "defaultH": 9},
    "mobile": {"minW": 12, "maxW": 12, "minH": 8, "maxH": 20, "defaultW": 12, "defaultH": 9}
  }],
  "padrao": {"desktop": [{"id":"cadastros-turno","section":"analises","x":0,"y":0,"w":12,"h":9,"altura_desejada":9}]},
  "origem_disposicao": "perfil",
  "repetido": false
}
```

Versão0 significa ausência de preferência salva; GET não cria registros. O catálogo contém somente os componentes personalizáveis autorizados atuais. Toda a seção `resumo` é fixa nos dois painéis: seus KPIs e `turma-cadastro` não aparecem no catálogo/padrão nem podem ser enviados em layouts (422).

`padrao.desktop` é calculado a partir da configuração versionada e somente leitura do perfil ativo (`config/paineis.php`), sempre intersectada com o catálogo autorizado. O registro `CatalogoPaineis` declara os painéis, o tipo de recurso e as seções fixas. O padrão preenche as linhas de12 colunas, sem colisões, com altura uniforme igual à maior altura desejada da linha. Configurações inválidas falham503. A resposta não permite editar padrões de outros perfis nem transforma um padrão em concessão de acesso.

Um mapa desktop pessoal não vazio, após filtrar os IDs atualmente autorizados, tem precedência e produz `origem_disposicao: usuario`; caso contrário, a origem é `perfil` e o cliente usa `padrao.desktop`. Uma atualização futura do padrão não sobrescreve posições pessoais. Restaurar desktop limpa esse mapa e volta ao padrão vigente. A configuração de apresentação permanece independente e seu padrão é tabela. O BFF mantém o estado real/demonstrativo separado. A resposta usa `Cache-Control: private, no-store`.

## Salvar e restaurar

```json
{
  "versao": 0, "chave": "4ea91eba-b8cf-4bed-84a3-077c050ac18b", "acao": "salvar",
  "apresentacao": "cards",
  "layouts": {"desktop": [{"id":"cadastros-turno", "section":"analises", "x":0, "y":0, "w":12, "h":9}]}
}
```

`apresentacao` e `layouts` são opcionais, mas pelo menos um deve ser enviado em salvar. Apresentação aceita somente `tabela|cards`. Em layouts, enviar somente os dispositivos a alterar: mapa mobile omitido é preservado, e vice-versa. Um mapa enviado substitui o mapa daquele dispositivo. A API não reorganiza componentes: preserva a ordem e as coordenadas finais enviadas. A política visual vigente redistribui larguras dos vizinhos no mesmo segmento contíguo e preserva alturas individuais; o cliente envia o mapa final completo, sujeito à validação de limites e colisões.

Cada geometria contém os seis campos obrigatórios `id, section, x, y, w, h` e pode incluir os campos técnicos opcionais `altura_desejada`, `fixo`, `altura_livre` e `orientacao`. Números são inteiros JSON, sem coerção de strings. Até64 widgets por dispositivo, IDs únicos, seção fixa de cada widget, grade de12 colunas e200 linhas por seção. Colisões na mesma seção são recusadas. Larguras/alturas devem respeitar o catálogo; mobile tem largura12 fixa. Limites desktop dos componentes editáveis: largura3..12 e altura máxima20; gráficos reais e demonstrativos têm altura mínima8/padrão9; `alunos-destaques` e `alunos-atencao` têm altura mínima10/padrão12. As três agendas (`agenda-pedagogica`, `proximos-compromissos`, `agenda-administrativa`) têm altura mínima12 e padrão14, inclusive nos limites técnicos mobile. Outros componentes têm mínima7/padrão9. KPIs e turma-cadastro permanecem fora desta geometria editável. O cliente pode reorganizar as caixas conforme a distribuição horizontal vigente, sem atravessar a seção de cada ID; a API aceita somente o estado final válido, sem normalizar ou corrigir silenciosamente o pedido.

O padrão individual dispõe a seção `estudantes` após o resumo fixo e antes das análises: são dois widgets independentes, sem abas. Mantêm o Top5 e a lista de atenção fornecidos pelo DTO existente, sem mudar cálculos, fórmulas ou relatórios. Preferências parciais não removem componentes: o consumidor completa os IDs autorizados ausentes pelo padrão atual do perfil. No agregado, a relação operacional Tabela/Cards precede as análises. Os widgets mostram títulos autoexplicativos e conteúdo útil, sem subtítulos ou parágrafos de orientação; bases e critérios permanecem nos tooltips e alternativas acessíveis, e os exemplos conservam identificação compacta no cabeçalho.

**Histórico superado pela decisão lateral posterior ao PR215:** após a release31286c3, a organização pessoal pode deixar espaço livre à direita. A soma das larguras da linha deve ser no máximo12, sem obrigatoriedade de completar12; o cliente preserva larguras escolhidas que caibam, inclusive ao salvar/reabrir. Defaults do perfil continuam como composição inicial, sem impor expansão ao mapa pessoal. O contrato da API já permite essas geometrias e permanece inalterado; não há migration, backfill nem regravação automática de mapas existentes. Esta orientação histórica de altura comum e larguras livres foi substituída; prevalece a seção final vigente.


`h` é a altura individual efetiva do widget. `altura_desejada`, quando presente, conserva a intenção individual legada; o cliente não a usa para igualar alturas dos vizinhos. Exige inteiro JSON e `minH <= altura_desejada <= h <= maxH` do widget/dispositivo. Não é CSS, HTML, autorização ou dado pedagógico. É persistido no mesmo JSON e recibo, sem coluna SQL/migração adicional. Repetir a chave com altura desejada diferente é outro pedido e recebe409. A ausência mantém exatamente o payload legado de seis campos; o cliente usa `h` como fallback, sem a API inventar o campo. A API não infere grupos/linhas nem muda vizinhos por conta própria.

A interface atual só oferece organização em viewport de pelo menos1024×700 CSSpixels; esse limiar é aproximado, não mede polegadas nem concede acesso. Abaixo dele não consulta, edita ou grava preferências de organização e não adapta/sobrescreve desktop. O contrato técnico mantém os dois mapas por compatibilidade; a UI atual consome/grava apenas desktop. O mapa mobile não é falsificado como vazio se houver configuração armazenada e continua isolado segundo as regras de patch/reset já descritas.

```json
{"versao":1,"chave":"faebdef8-1610-427b-89c3-d490f835d75a","acao":"restaurar","alvos":["mobile"]}
```

Restaurar exige subset não vazio e sem duplicatas de `desktop,mobile,apresentacao`. Reseta apenas os alvos explícitos; mobile não destrói desktop. Restaurar apresentação volta para tabela. Restaurar todos exige os três alvos; layouts/apresentação não podem acompanhar a ação restaurar.

## Versão, repetição e auditoria

PUT exige `versao` atual e chaveUUID. Cada novo pedido aceito incrementa versão, mesmo se a configuração resultante coincidir; evita ABA e fornece ordem de eventos. Aba antiga recebe409, sem aplicação parcial ou repetição automática. Repetir a mesma chave e pedido normalizado devolve o resultado original com `repetido:true`, sem reaplicar sobre um estado posterior; portanto esse resultado pode ter versão histórica. Após reconectar, usar GET para consultar o estado atual antes de outra edição. A mesma chave com outro pedido recebe409. A idempotência é isolada por preferência/contexto/painel/recurso; a mesma chave em duas turmas não cruza seus recibos.

O recibo persistente na mesma transação registra ação, hash do pedido, versão, resultado geométrico e horário; é protegido contra UPDATE/DELETE. Restaurar não apaga recibos. Falha no recibo reverte a atualização da preferência. Não há histórico de nomes/notas/dados pedagógicos nesses registros. O middleware de auditoria usual permanece ativo.

Escritas travam vínculo, espaço, turma quando aplicável e preferência; revalidam vínculo/capacidades e usam transação própria READ COMMITTED no MariaDB. A infraestrutura publicada na homologação comPR211 criou somente duas tabelas e os dois triggers de imutabilidade dos recibos, sem backfill nem alteração de tabelas anteriores. Este incremento de controles usa o JSON já instalado e não executa nova migration. Na nova tabela `preferencias_paineis`, `painel` é varchar64, `recurso_tipo` varchar16 obrigatório (`contexto|turma`) e `recurso_id` bigint unsigned nullable; esses dados também integram o hash de escopo. Nenhuma coluna adicional é necessária para altura desejada, que fica no JSON. `down()` não destrói histórico. Não executar DDL com a credencial DML da aplicação.

Limite nomeado próprio por usuário/método: GET60/min, PUT20/min; não divide contador com leituras de turmas ou emissões PDF.429 mantém Retry-After.401 autenticação;403 contexto/capacidade;404 painel fora do enum;409 versão/chave;422 shape/ID/geometria inválidos. O BFF mantém CSRF e revisão da sessão antes de traduzir esse PUT.

## Demonstrações e integração

Dados demonstrativos são autorizados pelo novo pedido, mas devem ficar exclusivamente em conjuntos estáticos separados no BFF, com identificação visível por componente. Esta API não aceita nem retorna datasets de demonstração. Um exemplo nunca substitui erro, zero ou vazio real; filtros reais não devem atribuir valores fictícios a nomes selecionados. Modelos e widgets reais mantêm o mesmo ID ao evoluir a fonte, preservando posição e dimensões. Catálogo futuro e alteração de seus limites exigem sincronização e testes consumidor/provedor, sem ampliar autorização a partir do layout.

Revisão independente de implementação e integração BFF/visual continuam obrigatórias. Os ensaios da API não aprovam movimentação, gráficos, acessibilidade ou publicação em homologação.


## Controles adicionais — decisão de07/11/2026, após release6444f2c

Contrato aditivo do JSON existente, sem nova tabela, coluna, migration, alteração de rotas ou do contrato dos recursos pedagógicos. `fixo` e `altura_livre` aceitam somente booleanos JSON; ausência não injeta `false`, e `false` explícito é preservado. `orientacao` aceita somente `horizontal|vertical` e somente nos IDs `distribuicao-acertos`, `resultados-avaliacao`, `periodos-escolares`, `habilidades`, `reavaliacoes`. Outros campos continuam recusados. Mapas legados de seis/sete campos mantêm a forma; todos os opcionais presentes entram no hash idempotente e no recibo imutável existente.

Em Organizar painel, Fixar posição protege **x/y/w/h** contra movimentos, resize direto e deslocamento causado por vizinhos. Sua caixa é uma âncora na grade12×200. Alturas são independentes para todos os widgets, sem toggle; os campos opcionais legados continuam preservados no transporte. Ao liberar um pin, o widget volta a participar da distribuição horizontal. A fixação prevalece sobre a redistribuição. Lacunas impostas por âncoras e barreiras geométricas são mantidas; preencher somente o segmento contíguo legal, sem sobrepor caixas ou cruzar seções. Operação inviável mantém a disposição anterior inteira.

Orientação muda o gráfico imediatamente, sem ligar arraste/resize. No desktop elegível, Salvar disposição persiste orientação e geometria juntas; Cancelar devolve ambas ao estado salvo. No celular a alternância é apenas da exibição atual, sem GET/PUT de preferências e sem sobrescrever o desktop. Alterações de versão/acesso e falha de confirmação continuam bloqueadas até consulta explícita; não há reenvio automático.

Acompanhamento da turma agrupa visualmente os modelos pedagógicos já autorizados de planejamento, ensino e decisões. IDs/seções, origem demonstrativa, permissões e mapas pessoais permanecem; não transforma modelos em módulos operacionais. Leituras dos cartões foi retirado apenas da composição individual, preservando correção/relatórios/dados. Paleta comparativa neutra não representa aprovação/reprovação; situações reais usam cores semânticas vinculadas ao critério registrado e preservam rótulos/alternativas acessíveis.


## Histórico PR215 — substituição pelo engine nativo compacto

Na release PR215, antes da nova decisão lateral registrada abaixo, normalizeRows foi substituído: compactação e colisões são resolvidas pela biblioteca GridStack instalada, em12colunas/200linhas dentro da seção autorizada. Nem largura nem altura são distribuídas aos vizinhos. Altura do próprio widget independe das demais por padrão. Campos fixo, altura_livre, orientacao e altura_desejada mantêm tipos/semântica de transporte; não existe nova migration nem regravação retroativa.

Mapa válido armazenado pode ser compactado no cliente para a nova apresentação, sem alterar suas dimensões/pins; IDs autorizados faltantes são completados com o padrão. Não há PUT automático. Salvar persiste a geometria final validada e Cancelar restaura o snapshot compacto que estava exibido antes da edição. Movimento/resize ilegais ou que desloquem pins são revertidos integralmente. IDs, limites, seções, colisões e completude são conferidos antes da escrita. O usuário não adiciona, exclui ou duplica widgets.

Resumo continua fora do catálogo. Abaixo1024×700 não consultar nem gravar preferências; widgets retornam ao fluxo responsivo. Orientação H/V temporária no mobile não sobrescreve desktop. Títulos dos eixos e face32/alvo44 dos botões são apresentação, não novas opções de API.


## Redistribuição horizontal — decisão posterior ao PR215

O responsável solicitou largura total dos vizinhos lado a lado e redistribuição quando um deles muda de largura ou outro widget existente é inserido no conjunto. O cliente calcula essa intenção usando snapshot anterior ao gesto; colisão nativa não pode decidir silenciosamente expulsar um vizinho que caberia com larguras legais menores. Alturas e metadados opcionais continuam individuais. Pins fixamx/y/w/h e delimitam espaço disponível. Soma de mínimos/máximos inviável não autoriza corte, sobreposição, perda de ID ou ultrapassar seção; operação deve manter snapshot com feedback.

API continua validando apenas o estado final completo e os limites do catálogo, com os mesmos campos, autorização, CSRF, versão, idempotência e recibos. Nenhuma coluna/migration/backfill. Reorganização visual não faz PUT automático; Cancelar volta ao snapshot inicial. Mobile continua fora da personalização. Eventual mínimo de largura3 depende de revisão deDesign e contrato de catálogo aditivo, não muda altura ou permissões.


Parecer prévio aprovado: mínimo técnico desktop dos editáveis passa de4 para3; mobile12, alturas, defaults, IDs e permissões permanecem. A redução amplia geometrias permitidas, mantendo compatibilidade com pedidos anteriores; não introduz novo campo. O cliente exige piso de leitura por tipo nas novas operações (240/280/300px sobre largura útil da seção); não toma esse piso visual por limite retroativo do dado salvo. Distribuição horizontal usa segmentos contíguos do snapshot e conserva as alturas individuais. Engine carrega a saída exata semcompactglobal posterior que expulse um vizinho. Todas as regras de autorização/recibos/isolamento continuam.
