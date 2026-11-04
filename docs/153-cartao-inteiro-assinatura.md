# Hotfix — abertura do cartão inteiro

Planejamento revisto em 04/11/2026 após validação do responsável: revisão e fotos funcionando na homologação, mas a ação “Ver cartão inteiro” recebe Invalid signature.

O transportador de contexto por aba acrescenta `_ctx` a todos os links locais, incluindo URLs temporárias assinadas pela API. A imagem inline usa o endereço original; o link de nova aba é reescrito pelo MutationObserver. Acrescentar qualquer parâmetro altera a assinatura.

Preservar URLs assinadas integralmente no transportador, sem ignorar parâmetros na validação da API, renovar prazos ou ampliar permissões. Manter o comportamento dos links comuns e a independência entre abas. Sem mudança visual, banco ou dados. Consultar o especialista antes da implementação e conferir o clique real e a nova aba.

Regressão: assinatura e codificação inalteradas em links estáticos e dinâmicos, contexto presente nos links comuns, destinos externos intactos e assinatura alterada/expirada recusada pela API. CI, integração e publicação isolada somente na homologação, baseada em 46fee2d, com backup próprio e conferência dos dados. Produção permanece intocada.
