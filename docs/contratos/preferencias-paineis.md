# Preferências pessoais dos dashboards

Contrato aditivo, sem alteração dos endpoints de turmas, resultados ou relatórios. Corresponde à persistência pessoal solicitada na correção dos painéis; não implementa agenda, indicadores novos nem exemplos operacionais. Base de implementação: main `0e6e906`.

## Acesso e isolamento

`GET /api/v1/paineis/preferencias/{painel}` e `PUT` no mesmo endereço, onde `painel` é `turmas` ou `turma`. Exigem autenticação normal, senha regular, vínculo explícito válido e `turma.ver`. Perfis internos e acessos legados sem vínculo não são habilitados por esta API. O Bearer contextual continua somente no servidor BFF.

A chave de armazenamento é usuário + vínculo + espaço + perfil + tipo de painel, derivados do contexto revalidado, nunca do corpo ou da URL. A preferência `turma` vale para a composição das turmas desse contexto; não contém ID de turma. Uma troca de vínculo/perfil não reutiliza a configuração de outro contexto. O cliente não pode enviar campos de autoridade; campos desconhecidos e parâmetros de query são recusados422.

Catálogo técnico finito: IDs e seções acordados com o BFF, sem títulos, nomes, notas, exemplos ou endpoints. Os modelos pedagógicos exigem `relatorio.turma` e têm veto de secretário/auxiliar; `alunos-recorte` exige também `aluno.ver` e `resultado.ver`; conselho exige `turma.vincular_professor`; documentos administrativos exigem `aluno.ver`. Persistir um ID não concede permissão. GET filtra IDs que deixaram de ser autorizados; PUT recusa IDs não autorizados. Não há nova permissão nem mudança da matriz existente.

## Resposta

Envelope padrão `data`:

```json
{
  "versao": 0,
  "apresentacao": "tabela",
  "layouts": {"desktop": [], "mobile": []},
  "catalogo": [{
    "id": "kpi-turmas", "section": "resumo",
    "desktop": {"minW": 2, "maxW": 12, "minH": 4, "maxH": 8, "defaultW": 3, "defaultH": 4},
    "mobile": {"minW": 12, "maxW": 12, "minH": 4, "maxH": 8, "defaultW": 12, "defaultH": 4}
  }],
  "repetido": false
}
```

Versão0 significa ausência de preferência salva; GET não cria registros. Mapas vazios significam disposição padrão do motor visual, não falta de permissão. O catálogo é o conjunto autorizado atual. O BFF aplica posições salvas somente aos IDs do catálogo e mantém o estado real/demonstrativo como responsabilidade separada. A resposta usa `Cache-Control: private, no-store`.

## Salvar e restaurar

```json
{
  "versao": 0, "chave": "4ea91eba-b8cf-4bed-84a3-077c050ac18b", "acao": "salvar",
  "apresentacao": "cards",
  "layouts": {"desktop": [{"id":"kpi-turmas", "section":"resumo", "x":0, "y":0, "w":3, "h":4}]}
}
```

`apresentacao` e `layouts` são opcionais, mas pelo menos um deve ser enviado em salvar. Apresentação aceita somente `tabela|cards`. Em layouts, enviar somente os dispositivos a alterar: mapa mobile omitido é preservado, e vice-versa. Um mapa enviado substitui o mapa daquele dispositivo, sem reposicionar vizinhos nem compactar espaços vazios. A ordem e as coordenadas enviadas são preservadas.

Cada geometria contém exatamente `id, section, x, y, w, h`. Números são inteiros JSON, sem coerção de strings. Até64 widgets por dispositivo, IDs únicos, seção fixa de cada widget, grade de12 colunas e200 linhas por seção. Colisões na mesma seção são recusadas. Larguras/alturas devem respeitar o catálogo; mobile tem largura12 fixa. Limites desktop: KPI largura2..12/altura4..8, gráficos largura4..12/altura7..20; alunos-recorte altura mínima10. Turma-cadastro mantém larguras de KPI, mas altura4..12 e padrão5, em ambos os dispositivos. Não há ajustes automáticos: uma troca de dois componentes deve enviar as duas caixas finais e só será aceita se ambas couberem.

```json
{"versao":1,"chave":"faebdef8-1610-427b-89c3-d490f835d75a","acao":"restaurar","alvos":["mobile"]}
```

Restaurar exige subset não vazio e sem duplicatas de `desktop,mobile,apresentacao`. Reseta apenas os alvos explícitos; mobile não destrói desktop. Restaurar apresentação volta para tabela. Restaurar todos exige os três alvos; layouts/apresentação não podem acompanhar a ação restaurar.

## Versão, repetição e auditoria

PUT exige `versao` atual e chaveUUID. Cada novo pedido aceito incrementa versão, mesmo se a configuração resultante coincidir; evita ABA e fornece ordem de eventos. Aba antiga recebe409, sem aplicação parcial ou repetição automática. Repetir a mesma chave e pedido normalizado devolve o resultado original com `repetido:true`, sem reaplicar sobre um estado posterior; portanto esse resultado pode ter versão histórica. Após reconectar, usar GET para consultar o estado atual antes de outra edição. A mesma chave com outro pedido recebe409. A idempotência é isolada por preferência/contexto/painel.

O recibo persistente na mesma transação registra ação, hash do pedido, versão, resultado geométrico e horário; é protegido contra UPDATE/DELETE. Restaurar não apaga recibos. Falha no recibo reverte a atualização da preferência. Não há histórico de nomes/notas/dados pedagógicos nesses registros. O middleware de auditoria usual permanece ativo.

Escritas travam vínculo, espaço e preferência; revalidam vínculo/capacidades e usam transação própria READ COMMITTED no MariaDB. A migração cria somente duas tabelas e os dois triggers de imutabilidade dos recibos, sem backfill nem alteração de registros anteriores. `down()` não destrói histórico. Não executar DDL com a credencial DML da aplicação.

Limite nomeado próprio por usuário/método: GET60/min, PUT20/min; não divide contador com leituras de turmas ou emissões PDF.429 mantém Retry-After.401 autenticação;403 contexto/capacidade;404 painel fora do enum;409 versão/chave;422 shape/ID/geometria inválidos. O BFF mantém CSRF e revisão da sessão antes de traduzir esse PUT.

## Demonstrações e integração

Dados demonstrativos são autorizados pelo novo pedido, mas devem ficar exclusivamente em conjuntos estáticos separados no BFF, com identificação visível por componente. Esta API não aceita nem retorna datasets de demonstração. Um exemplo nunca substitui erro, zero ou vazio real; filtros reais não devem atribuir valores fictícios a nomes selecionados. Modelos e widgets reais mantêm o mesmo ID ao evoluir a fonte, preservando posição e dimensões. Catálogo futuro e alteração de seus limites exigem sincronização e testes consumidor/provedor, sem ampliar autorização a partir do layout.

Revisão independente de implementação e integração BFF/visual continuam obrigatórias. Os ensaios da API não aprovam movimentação, gráficos, acessibilidade ou publicação em homologação.
