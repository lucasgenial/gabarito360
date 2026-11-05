# Revisão de leituras — cartão completo em leitor PDF

05/11/2026. Planejamento revisto antes do código. O responsável confirmou: documento completo é o cartão-resposta digitalizado do aluno atual, não o gabarito oficial. Restaurar o trecho da marcação junto às alternativas em todos os tamanhos. Acrescentar painel lateral independente no desktop; em telas compactas, botão Visualizar gabarito abre modal PDF. Produção excluída.

Consulta prévia e direção complementar UI/UX aprovadas: revisão aproximadamente55%/leitor45%, ocupando área útil, painel acompanhando rolagem, página inteira inicialmente ajustada, controles de ampliação/ajuste e páginas quando necessário. Se não houver largura legível, usar o modal. Carregamento, falha/retry e identificação do aluno ficam no leitor. Trocar questão do mesmo cartão conserva o documento; trocar cartão limpa imediatamente o anterior e descarta respostas antigas. Modal suspende marcação/navegação, mantém foco e devolve-o ao acionador ao fechar.

PDF.js6.5.438 e pdf-lib1.17.1 ficam versionados e hospedados localmente, com licenças. Fontes técnicas: https://mozilla.github.io/pdf.js/examples/ e https://pdf-lib.js.org/docs/api/classes/pdfdocument . Buscar a URL assinada original sem adicionar parâmetros; converter imagem para PDF apenas em memória no navegador, preservando proporção, orientação e bordas. Não modificar arquivo, notas, API, permissões ou banco; sem migração. O componente também aceita conteúdo application/pdf, sem implementar ou planejar novos tipos de prova/editor/GII.

Não expor baixar/imprimir; dissuasão local de copiar/arrastar/menu apenas no documento e no recorte. Não prometer impedir screenshot ou extração de bytes já entregues ao navegador. Preservar seleção de textos externos, rolagem e recursos do navegador. O leitor não executa ações JavaScript do PDF nem monta anotações/links ativos.

Validar conversão/renderização PDF real, página inteira, zoom, documento correto e ausência de recargas na mesma pessoa, troca rápida, respostas atrasadas, erro/retry, foco, teclado sem gravação acidental, assinatura íntegra, desktop amplo/estreito e320/390px. Aceite visual independente, CI/merge e publicação somente na homologação com backup e comparação de dados/arquivos. DC1 anterior já concluído no PR186 e publicado273db8a.

Ajuste solicitado na conferência: alternativas devem parecer botões. Consulta prévia UI/UX aprovou contorno azul em todas as alternativas, fundo e indicação própria somente para resposta atual (sem confundir leituras incertas), secundários Em branco/Anulada, foco externo e bloqueio temporário com status Salvando resposta. Preservar ação imediata e atalhos. Estilos limitados à revisão, tokens claro/escuro; aceite visual final pendente.

Verificação inicial: 190 testes web/1794 asserções; ensaio real de PDF e eventos em 2560/1440/1099/390/320 aprovado. CI apontou verificação prematura do rótulo de zoom antes do render assíncrono; corrigida espera da atualização, sem alterar a aplicação para satisfazer o teste.

Direção complementar solicitada pelo usuário e aprovada previamente pelo especialista: Voltar/Pular com contorno visível e centralizados, preservando tamanho, ordem e espaçamento. Voltar indisponível distinguido pelo estado desabilitado.

Na menor largura, impedir quebra de A–E: colunas seguem a quantidade de alternativas, padding horizontal8px e gap4px até400px, mantendo48px de altura e ao menos44px de largura em320px. Direção previamente aprovada pelo especialista; teste mede geometria real.
