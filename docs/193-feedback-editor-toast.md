# Feedback do editor e notificações flutuantes

Correção solicitada em09/11/2026: `Disposição salva.` estava em um parágrafo visível, e a pilha de notificações ocupava espaço na página. Reutilizar Solid/solid-toast já instalado, sem outra biblioteca.

Direção revisada e aprovada previamente por UI/UX e Product Design: toast de sucesso após ACK válido; informação para ações discretas; gestos em live-region somente acessível, sem spam visual. Erros/conflitos persistem no aviso compartilhado junto aos controles, sem duplicar toast. Falta da biblioteca mantém fallback contextual acessível. Não alterar API, banco, contrato, layout salvo, notas, cartões ou fotos.

Um único host fixo fica fora do fluxo principal. A pilha interna permanece em fluxo responsivo para acomodar alturas variáveis e resize; SolidToast0.5 mede a altura apenas na montagem. Em diálogo aberto, mover o mesmo host para o top-layer do diálogo, ainda fixo, e reancorar/limpar ao fechar. Preservar foco, temporizadores, pausa, Escape, texto escapado, safe-area e navegação mobile.

| Etapa | Aceite |
|---|---|
| FT1 | Integrar o feedback ao toast e avisos compartilhados; corrigir host fixo |
| FT2 | Validar geometrias/scroll antes/depois, pilha responsiva, diálogo, foco, fallback e salvamento sem repetição |
| FT3 | CI, revisão independente, merge e publicação somente na homologação; produção excluída |

Revisão prévia: diretório operacional, `editor-feedback-design-previo.md`. A revisão final exige capturas atuais desktop/mobile. Evidências ficam fora do repositório.
