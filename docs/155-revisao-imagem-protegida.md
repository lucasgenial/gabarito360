# Revisão — disposição da imagem e restrições de cópia

Planejamento revisto em 04/11/2026 a partir do pedido do responsável: imagem lateral no desktop; no celular, exibição somente ao abrir o visualizador. Dificultar as ações comuns de copiar/salvar/arrastar a imagem nesta etapa.

Consultar UI/UX e Product Design antes da implementação e validar desktop/celular. Preservar o modal, a questão atual, foco, ampliação, gestos de zoom e os bloqueios de atalhos durante a visualização. O card principal deve ocupar a área útil. Não alterar fotos, notas, respostas, URLs assinadas ou regras de acesso da API.

Aplicar restrições apenas às superfícies de imagem: menu de contexto, arrastar, seleção/cópia da imagem e menu de toque prolongado quando suportado. Não bloquear a área de transferência ou atalhos do navegador globalmente, nem copiar textos fora da imagem. Não prometer impossibilidade de captura: screenshots, ferramentas do navegador e extração de conteúdo já entregue ao dispositivo não podem ser impedidos completamente por uma página web. Não acrescentar marca d’água ou reprocessar a imagem sem pedido específico.

Regressões: composição responsiva e ausência de imagem inline no mobile; bloqueios locais e cópia de texto fora da imagem preservada; modal/foco/teclado/questão/assinaturas íntegros; nenhuma escrita involuntária. CI e merge após aceite especializado final, publicação somente na homologação sobre60e122b, com backup e comparação de dados. Produção preservada, sem migração.
