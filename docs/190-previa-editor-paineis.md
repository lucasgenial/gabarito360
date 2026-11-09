# Prévia local do editor de painéis

## Planejamento revisado

Solicitação humana: consolidar os cards-seção dos painéis Turmas e Turma; uma aba para composição e outra para geometria. Pré-consulta UI/UX/Product Design registrada em `operacao-20261102/paineis-editor-design-previo.md`. Implementar somente uma prévia local isolada; aguardar aprovação humana antes de alterar ou publicar telas reais. Nada de deploy nesta entrega.

| Passo | Escopo | Aceite |
|---|---|---|
| Fundação | SortableJS 1.15.7 + GridStack 14.1.0; marca, cores, escala Tailwind e ícones locais existentes | Sem React novo, CDN ou alteração de dependências do aplicativo |
| Distribuição | Coluna = card-seção central; transferir, ordenar, ativar/ocultar e renomear widgets/categorias | Uma instância por widget; catálogo do perfil; KPIs fixos |
| Ordem | Esquerda para direita representa base para topo, conforme expressão humana literal | Identificação visível e botão Ver painel para conferir; painel vertical inverte colunas |
| Ajustes finos | GridStack por seção, sem transferência entre grades | Preservar pin, limites, resize, ordenação, sem clonar/adicionar widgets |
| Rascunho | Duas abas compartilham composição/geometria | Salvar explícito, Cancelar restaura tudo, Restaurar padrão permanece rascunho |
| Preferências | Demonstração local por usuário/perfil/vínculo/painel/turma | localStorage fictício, validação de catálogo; nunca alegar autorização ou persistência no servidor |
| Revisão | Chrome desktop/mobile, temas, teclado, transferência, erros, salvar/reabrir | Parecer independente e capturas antes de apresentar para aprovação |

Categorias iniciais: Turmas = Turmas (lista), Análises entre turmas, Acompanhamento pedagógico. Turma = Resultados da turma, Acompanhamento da turma e Planejamento da turma. O padrão contém três painéis configurados por perfil; no perfil administrativo, os vazios ficam ocultos na leitura. Perfis administrativos não recebem indicadores pedagógicos; dados, nomes de usuários e vínculos são fictícios e identificados.

A prévia reutiliza os ativos/estilos Tailwind já aprovados; por estender projeto existente, não inicializa outro starter nem substitui o runtime do produto. O servidor de desenvolvimento escuta somente loopback e serve a prévia e uma lista restrita de ativos estáticos. CSP permite estilos necessários ao GridStack/ECharts, sem scripts inline, conexões externas ou acesso às APIs reais. Texto de títulos tratado como texto, até60 caracteres por seção/80 por widget.

## Integração futura, após aprovação

A API atual não aceita seções arbitrárias, visibilidade ou títulos pessoais. Portanto o protótipo não chama seus endpoints. A futura implementação exigirá contrato explícito e compatível para composição separada de geometria, validação servidor de catálogo/perfil/vínculo/painel/turma, versionamento/concorrência, auditoria e isolamento. Preferência não concede acesso a widget; a resposta autorizada do servidor sempre determina o catálogo efetivo. Novos widgets recebem padrão do perfil; ocultar não apaga dados. Preservar preferências antigas e eventos sem reescrever histórico.

Não executar migrações, alterar banco, gabaritos ou preferências reais, integrar automaticamente em main, nem publicar homologação/produção antes da aprovação da prévia.

## Situação da prévia

Primeira prévia foi conferida no Chrome e revisada independentemente. A rodada atual exige novo parecer em `previas/editor-paineis/design-qa.md`:23 testes de modelo passaram, incluindo árvore, migração local1/2 e proteção de pins; servidor e seus13 testes HTTP anteriores não mudaram. Revisão independente adicional incluiu24 negativos de hierarquia,20 casos de caminhos/handler, escape e reprodução do build. Capturas atuais ficam em `operacao-20261102/editor-arvore-visual`, fora do Git.

Decisão humana adicional: edição SOMENTE desktop; mobile lê a disposição salva no desktop ou o padrão do perfil. A prévia aplica também ponteiro fino/hover além do mínimo1024×700; dimensão física em polegadas não é inferida pelo navegador. O simulador de contexto é recurso de demonstração, sem autorização real. A revisão local de versão é otimista e não atômica; CAS transacional continua requisito da integração futura.

| Entrega | Situação |
|---|---|
| Planejamento e bibliotecas | Concluído |
| Cards-seção nos dois painéis | Prévia local concluída |
| Distribuição e ajustes finos | Prévia local concluída |
| Revisão independente e Chrome | Concluído para apresentar a prévia |
| Avaliação humana | Pendente |
| Contrato servidor, integração e homologação | Aguardam aprovação humana; não executados |

## Rodada2 — decisão humana vigente e planejamento consolidado

A aba Distribuição CONTINUA com o quadro de colunas anterior, agora hierárquico. A correção humana final revogou o ensaio WYSIWYG nessa aba; não interpretar a resposta assíncrona anterior como orientação atual. Uma coluna raiz representa um painel; uma seção filha é um contêiner embutido na coluna, com seus widgets/seções. A área fixa Sem seção reúne widgets avulsos. Base→Topo vale apenas para as colunas irmãs, com filhos em ordem vertical natural. Os ajustes finos mostram e manipulam a geometria final, sem transferência entre pais.

| Requisito atual | Implementação da prévia |
|---|---|
| Três painéis no padrão | Defaults por perfil; preferência pessoal pode ter1,2,3ou mais |
| Criar e renomear | Novo painel e Criar nova seção no select; título pessoal e ID estável |
| Remover contêiner | Modal confirma promoção dos filhos ao nível acima; widgets preservados |
| Seções filhas | Árvore de pais, caminhos no select; limite inicial de dois níveis de seção nesta prévia |
| Avulsos | Pai nulo; widgets visíveis fora dos contêineres e na mesma grade raiz |
| Vazio | Contêiner configurado permanece no editor, mas é ocultado na leitura se não tiver descendentes visíveis |
| Distribuição | SortableJS no quadro hierárquico; reflow só entre irmãos, preservando coordenadas internas das subárvores |
| Ajustes finos | GridStack na raiz e subgrades; resize/mover/pin relativos ao pai, sem reparenting |
| Proteções | Catálogo imutável, unicidade, ciclos/profundidade, pins e mínimo dos conteúdos; cabeçalhos medidos |
| Validação humana | Prévia aberta no Chrome; nenhum merge em main/deploy de homologação/produção nesta entrega |

Esquema local3 usa referências de pai e geometria por nó; leitura dos esquemas locais1/2 projeta a árvore sem regravar o registro até Salvar. Não é contrato da API real. Redimensionar pai não pode cortar filhos ou ampliar uma âncora fixada. Desmontagem encerra DD nos wrappers reais antes das engines; ensaio Chrome durante gesto com mudança390→1440 preservou rascunho e não produziu erro. Remoção via modal de três contêineres reduziu a um painel mantendo sete widgets, e Cancelar restaurou todo o rascunho.
