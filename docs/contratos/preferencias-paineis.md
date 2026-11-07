# Preferências pessoais dos dashboards

Contrato aditivo, sem alteração dos endpoints de turmas, resultados ou relatórios. Corresponde à persistência pessoal solicitada na correção dos painéis; não implementa agenda, indicadores novos nem exemplos operacionais. Base de implementação: main `0e6e906`.

## Acesso e isolamento

`GET /api/v1/paineis/preferencias/{painel}` e `PUT` no mesmo endereço, onde `painel` é `turmas` ou `turma`. Exigem autenticação normal, senha regular, vínculo explícito válido e `turma.ver`. Perfis internos e acessos legados sem vínculo não são habilitados por esta API. O Bearer contextual continua somente no servidor BFF.

A chave de armazenamento é usuário + vínculo + espaço + perfil ativo + painel + recurso, derivados do contexto revalidado. Para `turma`, o header técnico `X-G360-Painel-Turma` é obrigatório e contém um único ID inteiro positivo canônico; a API autoriza essa turma por `Escopo::turmas` antes de consultar preferências. Para `turmas`, o header é proibido e o recurso é o contexto agregado. IDs inválidos recebem422; turma fora do alcance recebe404. O header seleciona um recurso, não concede autoridade. Uma turma, vínculo ou perfil não reutiliza a configuração de outro. O BFF deriva o header exclusivamente do alvo da referência UR4 validada, sem usar seleção global de turma, corpo ou header arbitrário do navegador. Assim, duas abas de turmas distintas permanecem independentes. Campos de autoridade no corpo e parâmetros de query são recusados422.

Catálogo técnico finito: IDs e seções acordados com o BFF, sem títulos, nomes, notas, exemplos ou endpoints. Os modelos pedagógicos exigem `relatorio.turma` e têm veto de secretário/auxiliar; `alunos-recorte` exige também `aluno.ver` e `resultado.ver`; conselho exige `turma.vincular_professor`; documentos administrativos exigem `aluno.ver`. Persistir um ID não concede permissão. GET filtra IDs que deixaram de ser autorizados; PUT recusa IDs não autorizados. Não há nova permissão nem mudança da matriz existente.

## Resposta

Envelope padrão `data` (catálogo e padrão abaixo são recortes ilustrativos; a resposta real contém todos os IDs autorizados):

```json
{
  "versao": 0,
  "apresentacao": "tabela",
  "layouts": {"desktop": [], "mobile": []},
  "catalogo": [{
    "id": "cadastros-turno", "section": "analises",
    "desktop": {"minW": 4, "maxW": 12, "minH": 11, "maxH": 20, "defaultW": 6, "defaultH": 11},
    "mobile": {"minW": 12, "maxW": 12, "minH": 11, "maxH": 20, "defaultW": 12, "defaultH": 11}
  }],
  "padrao": {"desktop": [{"id":"cadastros-turno","section":"analises","x":0,"y":0,"w":6,"h":12,"altura_desejada":11}]},
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
  "layouts": {"desktop": [{"id":"cadastros-turno", "section":"analises", "x":0, "y":0, "w":6, "h":11}]}
}
```

`apresentacao` e `layouts` são opcionais, mas pelo menos um deve ser enviado em salvar. Apresentação aceita somente `tabela|cards`. Em layouts, enviar somente os dispositivos a alterar: mapa mobile omitido é preservado, e vice-versa. Um mapa enviado substitui o mapa daquele dispositivo. A API não reorganiza componentes: preserva a ordem e as coordenadas finais enviadas. A nova política visual GridStack permite movimentar vizinhos, uniformizar alturas da linha e remover linhas vazias; o cliente envia o mapa final completo dessas mudanças, sujeito à validação de limites e colisões.

Cada geometria contém os seis campos obrigatórios `id, section, x, y, w, h` e pode incluir apenas o campo técnico opcional `altura_desejada`. Números são inteiros JSON, sem coerção de strings. Até64 widgets por dispositivo, IDs únicos, seção fixa de cada widget, grade de12 colunas e200 linhas por seção. Colisões na mesma seção são recusadas. Larguras/alturas devem respeitar o catálogo; mobile tem largura12 fixa. Limites desktop dos componentes editáveis: largura4..12 e altura máxima20; gráficos reais têm mínima/padrão11, os cinco modelos gráficos têm mínima/padrão12; alunos-recorte tem altura mínima10/padrão12. As três agendas (`agenda-pedagogica`, `proximos-compromissos`, `agenda-administrativa`) têm altura mínima12 e padrão14, inclusive nos limites técnicos mobile. Outros componentes têm mínima7/padrão9. KPIs e turma-cadastro permanecem fora desta geometria editável. O cliente pode reorganizar as caixas conforme a política atual de linhas, sem atravessar a seção de cada ID; a API aceita somente o estado final válido, sem normalizar ou corrigir silenciosamente o pedido.


`h` é a altura **efetiva** ocupada na grade depois de uniformizar a linha. `altura_desejada`, quando presente, guarda a intenção individual do usuário para que um componente menor não perca essa informação ao compartilhar a linha com outro mais alto. Exige inteiro JSON e `minH <= altura_desejada <= h <= maxH` do widget/dispositivo. Não é CSS, HTML, autorização ou dado pedagógico. É persistido no mesmo JSON e recibo, sem coluna SQL/migração adicional. Repetir a chave com altura desejada diferente é outro pedido e recebe409. A ausência mantém exatamente o payload legado de seis campos; o cliente usa `h` como fallback, sem a API inventar o campo. A API não infere grupos/linhas nem muda vizinhos por conta própria.

A interface atual só oferece organização em viewport de pelo menos1024×700 CSSpixels; esse limiar é aproximado, não mede polegadas nem concede acesso. Abaixo dele não consulta, edita ou grava preferências de organização e não adapta/sobrescreve desktop. O contrato técnico mantém os dois mapas por compatibilidade; a UI atual consome/grava apenas desktop. O mapa mobile não é falsificado como vazio se houver configuração armazenada e continua isolado segundo as regras de patch/reset já descritas.

```json
{"versao":1,"chave":"faebdef8-1610-427b-89c3-d490f835d75a","acao":"restaurar","alvos":["mobile"]}
```

Restaurar exige subset não vazio e sem duplicatas de `desktop,mobile,apresentacao`. Reseta apenas os alvos explícitos; mobile não destrói desktop. Restaurar apresentação volta para tabela. Restaurar todos exige os três alvos; layouts/apresentação não podem acompanhar a ação restaurar.

## Versão, repetição e auditoria

PUT exige `versao` atual e chaveUUID. Cada novo pedido aceito incrementa versão, mesmo se a configuração resultante coincidir; evita ABA e fornece ordem de eventos. Aba antiga recebe409, sem aplicação parcial ou repetição automática. Repetir a mesma chave e pedido normalizado devolve o resultado original com `repetido:true`, sem reaplicar sobre um estado posterior; portanto esse resultado pode ter versão histórica. Após reconectar, usar GET para consultar o estado atual antes de outra edição. A mesma chave com outro pedido recebe409. A idempotência é isolada por preferência/contexto/painel/recurso; a mesma chave em duas turmas não cruza seus recibos.

O recibo persistente na mesma transação registra ação, hash do pedido, versão, resultado geométrico e horário; é protegido contra UPDATE/DELETE. Restaurar não apaga recibos. Falha no recibo reverte a atualização da preferência. Não há histórico de nomes/notas/dados pedagógicos nesses registros. O middleware de auditoria usual permanece ativo.

Escritas travam vínculo, espaço, turma quando aplicável e preferência; revalidam vínculo/capacidades e usam transação própria READ COMMITTED no MariaDB. A migração ainda não publicada cria somente duas tabelas e os dois triggers de imutabilidade dos recibos, sem backfill nem alteração de tabelas anteriores. Na nova tabela `preferencias_paineis`, `painel` é varchar64, `recurso_tipo` varchar16 obrigatório (`contexto|turma`) e `recurso_id` bigint unsigned nullable; esses dados também integram o hash de escopo. Nenhuma coluna adicional é necessária para altura desejada, que fica no JSON. `down()` não destrói histórico. Não executar DDL com a credencial DML da aplicação.

Limite nomeado próprio por usuário/método: GET60/min, PUT20/min; não divide contador com leituras de turmas ou emissões PDF.429 mantém Retry-After.401 autenticação;403 contexto/capacidade;404 painel fora do enum;409 versão/chave;422 shape/ID/geometria inválidos. O BFF mantém CSRF e revisão da sessão antes de traduzir esse PUT.

## Demonstrações e integração

Dados demonstrativos são autorizados pelo novo pedido, mas devem ficar exclusivamente em conjuntos estáticos separados no BFF, com identificação visível por componente. Esta API não aceita nem retorna datasets de demonstração. Um exemplo nunca substitui erro, zero ou vazio real; filtros reais não devem atribuir valores fictícios a nomes selecionados. Modelos e widgets reais mantêm o mesmo ID ao evoluir a fonte, preservando posição e dimensões. Catálogo futuro e alteração de seus limites exigem sincronização e testes consumidor/provedor, sem ampliar autorização a partir do layout.

Revisão independente de implementação e integração BFF/visual continuam obrigatórias. Os ensaios da API não aprovam movimentação, gráficos, acessibilidade ou publicação em homologação.
