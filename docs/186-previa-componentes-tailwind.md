# Prévia para aprovação — componentes, propriedades e regras

08/11/2026. O responsável solicitou uma referência interativa ANTES de propagar a migração. O trabalho autenticado foi preservado localmente no commitc0216e5 da ramificação ui/botoes-tabelas-sm, sem merge/publicação. A entrega desta ramificação ui/previa-componentes-tailwind contém somente documentação, pipeline e uma página estática isolada.

## Escopo e gate

URL prevista: /previas/tailwind/index.html na homologação, sob a proteção já existente. Dados fictícios. Nenhuma alteração de layout,controller,rota,API,permissão,sessão,base de dados ou preferência operacional. Nenhum envio de formulário,upload,câmera,autenticação,persistência local ou transporte. CSP da própria página bloqueia conexões e envio de formulários. A página carrega somente sua folha Tailwind compilada e seuJS externo, com versões por hash de conteúdo; não carrega app.css ou scripts da aplicação.

Pré-consulta independente UI/UX e ProductDesign aprovou uma página com seis seções e propriedades/regras acessíveis. O aceite visual técnico da prévia será registrado separadamente. **A aprovação humana do padrão permanece obrigatória antes de aplicar a identidade às telas reais.** O responsável aprovou o escopo para oApp e pediu a mesma estrutura de menu mobile/desktop: botão no topo abre o painel sobre o conteúdo,fechamento recolhe por completo e a área útil fica livre. As páginas estáticas atuais ficam fora desta entrega; a avaliação delas será posterior.

**Referência vigente:** recuperar Meu espaço recolhível no card da primeira proposta. Escola e perfil completos em duas colunas, inclusive nome longo. Avatar abre a conta com nome/e-mail, Meu perfil, troca condicional e Sair; navegação principal usa controle próprio e Mais no mobile. Os modelos de selo/bloco noheader e ícone/modal informacional foram rejeitados e não integram esta entrega.

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

## Correção da referência recuperada

A decisão subsequente revoga o modelo de ícone/modal informacional. Recuperar a composição original de a23d364: Meu espaço é um details recolhido na faixa inicial do card, junto ao breadcrumb; ao abrir, mostra escola/perfil em duas colunas, sem executar troca. Preservar nome completo e piso96px do perfil. Header compacto mantém avatar→menu à mostra/recolhido, tema e o controle separado Trocar vínculo. Este só aparece com mais de um vínculo elegível. Na prévia, a propriedade de um/dois vínculos e o modal de seleção são fictícios e não modificam sessões reais. O usuário solicitou também dois vínculos reais do usuário coordenador@gabarito360.dev na mesma escola, Coordenador e Professor, exclusivamente na homologação, em operação separada e auditada, preservando senha e dados.

O parecer4ed9de5 é histórico; as partes alteradas exigem nova revisão. Nenhum merge ou deploy desse modelo rejeitado foi realizado.

Esclarecimento final do responsável: o AVATAR abre o menu DA CONTA, não a navegação principal. Mostrar nome completo e e-mail de acesso no topo da conta; ações Meu perfil, Trocar vínculo (somente mais de um elegível) e Sair. Não duplicar a troca de vínculo em outro ícone no header. A navegação principal possui botão próprio, pode ficar à mostra ou escondida, e também abre por Mais no celular. Meu espaço continua apenas informativo, recolhível no card. Na prévia, nome/e-mail e vínculos são fictícios; Perfil/Sair são feedback de demonstração, sem abrir cadastro ou encerrar sessão real.

O responsável aprovou explicitamente o menu do avatar após a inclusão do nome e e-mail. O restante da prévia continua sujeito ao aceite do conjunto antes da migração operacional.

Correção expressa do menu principal: SOMENTE DESKTOP deve abrir em fluxo lateral, refazendo a largura disponível do conteúdo, sem sobreposição, backdrop, inert ou prisão de foco; recolher devolve toda a largura. Manter aberto ao navegar nas seções até recolhimento explícito. A alteração não é para o mobile: preservar a estrutura da foto enviada (quatro atalhos inferiores e Mais) e não implementar menu acima do conteúdo. A resposta assíncrona sobre empurrar o conteúdo no celular foi corrigida pelo próprio responsável, que esclareceu estar falando do desktop. Avatar/conta é independente e aprovado. Apresentar os dois modelos desktop/mobile para avaliação antes de propagar para as telas reais.

## Rodada2 — dez correções para nova avaliação

A prévia continua isolada, com dados fictícios e sem migração das telas reais. Revisão do planejamento: capturar a lógica expandida/compacta do YouTube, sem copiar cores/design; uma marca completa no header (PNG de marca existente, com variante clara no escuro); menu desktop em fluxo de256/80px; tablets a partir de768CSSpx usam estrutura desktop, inicialmente compacta abaixo de1280; celular até767px conserva quatro atalhos+Mais, que abre dialog inferior com lista vertical, conforme a foto do próprio App. Não inferir polegadas físicas da viewport. CSS compartilhado, desktop e mobile possuem fontes separadas, compiladas em uma única folha isolada.

Meu espaço do card está preservado. Novo experimento com divisor no rodapé da lateral expandida, oculto na compacta. Celular apresenta Minha escola/nome e Meu perfil/perfil em sequência vertical; desktop mantém duas colunas no card. Troca fictícia atualiza ambas as referências, sem mudar sessão real. Avatar da entidade recebe classe avatar-entidade e id avatar-turma-exemplo:64px desktop/tablet e56px mobile; não altera avatar da conta.

FormValidation: o responsável confirmou licença e autorizou uso, apresentando comprovanteDeveloper0.8.1(2018) e e-mail do autor de06/02/2020 que concede1.6.0 gratuitamente e orienta baixar a versão mais recente pelo mesmo link. Esses documentos privados não integram Git nem assets. Usar pacotes oficiais2.4.0 travados, núcleo+validadores necessários, esbuild0.28.2 travado, semCDN/jQuery; preservar aviso de autoria. Adaptador externo validacao-formulario.js combina motor real com mensagenspt-BR, aria-invalid/erros associados, estadosTailwind, foco inicial inválido, limpeza e validação local. Não registrarDefaultSubmit/Remote. Os campos são umfieldset, semform de envio. A validação cliente não substitui validação/autorização do servidor quando migrarmos as telas reais.

Critérios de aceite:320/390,768/1024 e1440/amplo; temasclaro/escuro; uma marca visível; conteúdo reflui entre256/80 sem bloqueio; Mais abre folha inferior, Escape/Fechar/seleção restauram foco; transição de breakpoint fecha diálogo sem armadilha; contexto empilhado no celular; comparação entre os doisMeusEspaços; erros/sucesso deNome/Ano pelo motor real; nenhum transporte/gravação. Pré-consulta independente favorável; revisão visual e aceite humano desta rodada ainda pendentes.

## Rodada3 — revisão antes da implementação

Pré-consulta independente UI/UX e Product Design favorável. Escopo restrito à referência: remover a reserva lateral/breadcrumb junto ao contexto, com contexto-espaco usando toda a largura; Meu espaço na lateral expandida é fixo, sem collapse; menu branco no claro e navy no escuro. Ações reais na primeira coluna da tabela, tamanho intrínseco com limite responsivo e quebra; em cards, mesmo grupo no rodapé esquerdo, alinhado entre cards da mesma linha sem altura fixa. Identificar as ações pelo registro. Preservar pq32/44toque, teclado, menu da conta, folha móvel e FormValidation.

Adotar Material Symbols Outlined oficiais como SVG local, variante outlined400/24 de referência, sem fonte/CDN remoto. Licença Apache2 e origem fixada, sem copiar assets do YouTube. Ícones16 em açõespq e20 na navegação. Validar320/390/768/1024/1440, nomes longos, os dois temas, ações da tabela/cards, foco e ausência de transporte. Aceite humano geral permanece pendente; sem migração operacional, merge ou publicação.

## Rodada4 — solução progressiva aprovada antes de implementar

O responsável aprovou a progressão por espaço disponível e a logo da homologação, restritas à prévia. Pré-consulta Product Design favorável: amplo mantém ícone+texto completo; intermediário remove o contexto repetido (Editar/Arquivar); estreito usa Filtro/Editar só com ícone e preserva texto de Arquivar. Manter pq32/44toque, nomes acessíveis contextualizados, confirmação e mesmo elemento/ação. Não criar mp nesta rodada. Containers com largura alocada evitam dependência circular. Cards de registros mantêm rodapé esquerdo; Ver/Editar podem compactar, Arquivar permanece textual. Permitir wrap como último recurso com zoom/texto aumentado.

Marca: mesma composição oficial da homologação — SVG original, Figtree ExtraBold, numeral6 AlbertSans ExtraBold, medidasCSS32/18/8. O body da aba de homologação tem zoom1.25; os40px medidos eram32 ampliados, não um novo tamanho oficial. Não copiar zoom nem alterar a aba operacional. Fontes locais, marca completa no mobile e cores claras no escuro; não carregar CSS global operacional.

Aceite:320/348/375/393/440 +768/1440 e menu expandido/recolhido, ambos temas, alinhamento/foco/tooltip/diálogo, SVG e fontes locais com marca única, mesma validaçãoFormValidation e nenhuma operação de negócio. Revisão independente final e avaliação humana antes de migrar telas reais; nenhum servidor alterado.
