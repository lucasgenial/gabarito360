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

Categorias iniciais: Turmas = Turmas (lista), Análises entre turmas, Acompanhamento pedagógico. Turma = Resultados da turma e Acompanhamento da turma. Sem categorias extras para subgrupos internos; uma coluna representa diretamente um card central. Perfis administrativos não recebem indicadores pedagógicos; dados, nomes de usuários e vínculos são fictícios e identificados.

A prévia reutiliza os ativos/estilos Tailwind já aprovados; por estender projeto existente, não inicializa outro starter nem substitui o runtime do produto. O servidor de desenvolvimento escuta somente loopback e serve a prévia e uma lista restrita de ativos estáticos. CSP permite estilos necessários ao GridStack/ECharts, sem scripts inline, conexões externas ou acesso às APIs reais. Texto de títulos tratado como texto, até60 caracteres por seção/80 por widget.

## Integração futura, após aprovação

A API atual não aceita seções arbitrárias, visibilidade ou títulos pessoais. Portanto o protótipo não chama seus endpoints. A futura implementação exigirá contrato explícito e compatível para composição separada de geometria, validação servidor de catálogo/perfil/vínculo/painel/turma, versionamento/concorrência, auditoria e isolamento. Preferência não concede acesso a widget; a resposta autorizada do servidor sempre determina o catálogo efetivo. Novos widgets recebem padrão do perfil; ocultar não apaga dados. Preservar preferências antigas e eventos sem reescrever histórico.

Não executar migrações, alterar banco, gabaritos ou preferências reais, integrar automaticamente em main, nem publicar homologação/produção antes da aprovação da prévia.

## Situação da prévia

Implementada e conferida no Chrome, com revisão independente UI/UX/Product Design (`previas/editor-paineis/design-qa.md`). Dez testes de modelo e13 verificações HTTP no servidor local passaram. Revisão independente de segurança incluiu20 casos de caminhos/handler,4 vetores de escape e reprodução do build; a restrição de arquivos do servidor foi corrigida e revalidada. Capturas desktop1440/2560, mobile390/320 e tema escuro estão no diretório operacional, fora do Git.

Decisão humana adicional: edição SOMENTE desktop; mobile lê a disposição salva no desktop ou o padrão do perfil. A prévia aplica também ponteiro fino/hover além do mínimo1024×700; dimensão física em polegadas não é inferida pelo navegador. O simulador de contexto é recurso de demonstração, sem autorização real. A revisão local de versão é otimista e não atômica; CAS transacional continua requisito da integração futura.

| Entrega | Situação |
|---|---|
| Planejamento e bibliotecas | Concluído |
| Cards-seção nos dois painéis | Prévia local concluída |
| Distribuição e ajustes finos | Prévia local concluída |
| Revisão independente e Chrome | Concluído para apresentar a prévia |
| Avaliação humana | Pendente |
| Contrato servidor, integração e homologação | Aguardam aprovação humana; não executados |
