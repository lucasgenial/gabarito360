# Prévia para aprovação — componentes, propriedades e regras

08/11/2026. O responsável solicitou uma referência interativa ANTES de propagar a migração. O trabalho autenticado foi preservado localmente no commitc0216e5 da ramificação ui/botoes-tabelas-sm, sem merge/publicação. A entrega desta ramificação ui/previa-componentes-tailwind contém somente documentação, pipeline e uma página estática isolada.

## Escopo e gate

URL prevista: /previas/tailwind/index.html na homologação, sob a proteção já existente. Dados fictícios. Nenhuma alteração de layout,controller,rota,API,permissão,sessão,base de dados ou preferência operacional. Nenhum envio de formulário,upload,câmera,autenticação,persistência local ou transporte. CSP da própria página bloqueia conexões e envio de formulários. A página carrega somente sua folha Tailwind compilada e seuJS externo, com versões por hash de conteúdo; não carrega app.css ou scripts da aplicação.

Pré-consulta independente UI/UX e ProductDesign aprovou uma página com seis seções e propriedades/regras acessíveis. O aceite visual técnico da prévia será registrado separadamente. **A aprovação humana do padrão permanece obrigatória antes de aplicar a identidade às telas reais.** O responsável aprovou o escopo para oApp e pediu a mesma estrutura de menu mobile/desktop: botão no topo abre o painel sobre o conteúdo,fechamento recolhe por completo e a área útil fica livre. As páginas estáticas atuais ficam fora desta entrega; a avaliação delas será posterior.

**Decisão humana posterior, substitui o ensaio de selo/bloco rejeitado:** “Meu espaço” é um botão de ícone noheader e abre modal com escola/perfil em ambos os modos. Nome fictício extenso,semreticências/overflow; perfil com piso de largura para não partir “Coordenação” dentro da palavra. O header volta a ser compacto. A foto/avatar abre o menu recolhido tanto no desktop quanto no mobile; o celular preserva a estrutura da navegação inferior doApp,quatro atalhos eMais. Nesta referência,os rótulos dos atalhos correspondem às seções de componentes,sem acesso a rotas operacionais. O ensaio anterior de bloco noheader não integra a proposta atual.

Toasts possuem quatro variantes próprias: informação,sucesso,atenção e erro,com fundo,borda,ícone,título e anúncio acessível distintos. Mensagens estáticas não geram alertas; notificações locais são disparadas por ação,fecháveis e sem expiração durante a leitura. A adaptação móvel dos registros usaCards explicitamente e oculta o seletor redundante,sem atribuir seleçãoTabela a uma apresentação emcards.

| Referência | Demonstração |
|---|---|
| Composição | Menu navy,contexto/título/ações integrados,abas por teclado,KPIs fictícios e ajuda pelo título |
| Ações e tamanhos | Texto/ícone+texto/ícone isolado,variantes,foco,desabilitado/carregando; pq32,md40,gd48,xg56; toque44 e largura lg100% separada da altura |
| Tabelas/cards | Mesmos três registros,nomes longos,busca local,linhas naturais,açõespq e apresentação móvel preservando dados |
| Formulários | Rótulos,required/opcional,erro inline associado,foco no inválido,campos desabilitados; sem formulário de envio |
| Feedback | Avisos,toast superior direito fechável e persistente,loading por seção,imagem ausente distinta de erro/repetição local |
| Regras | PaletaG360,escalaTailwind,acessibilidade,fronteira da aprovação e limitações da demonstração |

Arquivamento é apenas demonstração de diálogo,Cancelar/Escape/retorno de foco; nem os registros fictícios são arquivados. Paginação é ilustrativa e desabilitada por haver uma página. Gráficos, cálculos reais, edição de fotos e documentos/PDF continuam para validação própria; esta prévia não os declara migrados.

## Implementação e validação

TailwindCLI4.3.5 travado por lockfile; Preflight completo restrito ao documento standalone, utilitários/variáveis prefixados `tw` para evitar colisões legadas. Cores permanecemG360, demais dimensões/tipografia usam a escala do framework. Fontes de implementação em apps/web/resources/previas/tailwind; saída pública em apps/web/public/previas/tailwind. Build scripts/tailwind/build.mjs reproduzível e conferido noCI.

Auditoria automatizada scripts/auditoria/previa-componentes.mjs usa a página real compilada com servidorlocal de arquivos explicitamente permitidos e dados fictícios. Confere dimensões,largura,estados,tabela/cards,busca,diálogo sem operação,validação,tema,teclado/popover/menu320/390 e ausência de requests de negócio. Conferência visual independente atual noChrome obrigatória antes de publicar. Publicação exclusivamente na homologação com backup/verificação integral; sem migração ou acesso à produção.

| Etapa | Situação nesta preparação |
|---|---|
| TW1 — fundação e prévia isolada | Implementada; em validação |
| Aprovação humana dos componentes | Pendente após disponibilizar a prévia |
| TW2 — estrutura das telas operacionais | Aguarda aprovação da prévia |
| TW3 — ações,tabelas/cards operacionais | Aguarda aprovação da prévia |
| TW4 — formulários/diálogos operacionais | Pendente |
| TW5 — painéis,mídia e ferramentas | Pendente |
| TW6 — autenticação,público e documentos | Pendente |
| TW7 — validação transversal | Contínua por entrega; migração geral não concluída |
