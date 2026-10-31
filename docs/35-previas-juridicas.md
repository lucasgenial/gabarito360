# 35 — Prévias de termos e privacidade

Escopo autorizado na conversa de 31/10/2026: textos jurídicos aprovados; preparar as páginas com espaços para preencher os dados depois. Base: documento 33. A aprovação de conteúdo não é apresentada como comprovação de revisão por advogado.

## Micropassos

1. Preencher as duas views de prévia existentes, sem publicar rotas ou links.
2. Transpor o aviso de privacidade do documento 33, mantendo os campos pendentes identificados.
3. Organizar condições de uso a partir da natureza do serviço, papéis e cláusulas do mesmo documento, sem criar preços, multas ou foro.
4. Usar o layout público e tokens existentes, renderizar localmente e verificar celular e desktop.

## Campos a preencher

[DATA], [RAZÃO SOCIAL], [CNPJ], [ENDEREÇO], [NOME DO ENCARREGADO], [E-MAIL DO ENCARREGADO], [LINK DOS SUBOPERADORES], [LINK DA TABELA DE RETENÇÃO], [PRAZO DE EXPORTAÇÃO E ELIMINAÇÃO].

Campos são texto, sem links fictícios, e-mails presumidos ou valores inventados. A identificação da empresa se repete nas duas páginas.

## Limites

L1 continua parcial: sem publicação, migração, registro de aceite ou bloqueio no login. O termo do responsável pertence ao portal. Os termos organizados nesta entrega são uma prévia baseada nas cláusulas do documento 33, que não continha termos de uso completos.

## Verificação

- Duas views Blade renderizadas localmente com as dependências existentes e PHP do WSL.
- Duas páginas em dez larguras, de 320 a 3840 px, nos temas claro e escuro: sem rolagem horizontal ou erros JavaScript.
- Imagens de desktop e celular inspecionadas; campos pendentes legíveis nos dois temas.
- Verificador estático: 24 scripts, zero erros.
- As rotas `/termos` e `/privacidade` continuam ausentes, conforme teste existente. Não foi executada auditoria com dados de produção, pois estas views não têm rotas públicas.
