# IM1 — correção do avatar no cabeçalho

Planejamento revisado em 04/11/2026: corrigir somente a apresentação da imagem no topo, preservando a foto gravada, o editor aprovado e a produção. A consulta prévia de UI/UX e Product Design aprovou wrapper sem espaço de baseline, círculo centralizado com contorno discreto e foco de teclado separado.

| Etapa | Escopo | Estado neste commit |
|---|---|---|
| Diagnóstico | Foto real da homologação e medidas dos elementos | Concluído |
| Correção | CSS restrito ao cabeçalho compartilhado | Concluído |
| Conferência | Desktop/mobile, claro/escuro, foto/galeria e teclado | Concluído; parecer independente registrado no PR |
| Integração | CI, merge e deploy somente na homologação | Recibos finais no PR |

O elemento inline-grid do avatar deixava espaço de baseline no wrapper: com zoom de 125%, a foto tinha 45px, mas o wrapper tinha 49px, deixando o círculo 2px físicos acima do centro. O wrapper do cabeçalho passa a grid de 36×36px CSS, no botão existente de 44×44px. O círculo ganha borda de 1px com o token `--border` e box-sizing border-box; a imagem usa display block, cover e posição central. O sprite da galeria não muda. Foco visível de 2px fica no botão.

Conferência Chrome em QA isolada, com pessoa e imagem fictícias: centros coincidentes nos dois eixos, botão 44×44, wrapper/círculo 36×36, bordas claras/escuras com tokens existentes, modal abre e cancela sem salvar. Foto, avatar da galeria e foco por teclado conferidos; console sem erros. Capturas atuais em desktop de 1440px e celular de 390px acompanham o parecer independente. Nenhum teste novo de implementação para este ajuste de CSS; CI existente e conferência visual verificam a entrega.

Não há alteração de dados, upload, recodificação, rota, controlador ou migração. A publicação autorizada limita-se à VM120 de homologação com backup e comparação dos dados acadêmicos. O encerramento operacional, aprovação final, SHA publicado e resultados de CI ficam no PR para corresponder ao código efetivamente publicado.
