# UR4 — sessão e vínculo com o mesmo ciclo de vida

Decisão do responsável em 06/11/2026, posterior ao PR198: o vínculo não deve expirar separadamente. Escolher o vínculo é parte do login ou de uma troca explícita pelo controle de espaço/perfil. Contexto irrecuperável exige novo login completo.

## Planejamento revisto

1. Preservar o contrato da API e o transporte de recursos por POST, CSRF e revisão da sessão.
2. Manter o contexto ativo criptografado dentro da sessão, vinculado à identidade e ao ID da sessão. O cache anterior permanece apenas para compatibilidade; sessões existentes válidas são migradas ao serem lidas. Nenhum novo prazo é configurado.
3. Se um contexto anteriormente escolhido for perdido ou inválido, invalidar a sessão Web inteira, renovar o CSRF e descartar os snapshots de autoridade antes de responder401/encaminhar ao login. Não reenviar operações. A primeira seleção logo após autenticar continua disponível.
4. Preservar409 para revisão antiga após troca legítima: uma aba antiga não pode encerrar a sessão nova nem executar ações com o perfil novo. Oferecer o painel atual, onde o controle de troca permanece disponível.
5. Validar persistência além do antigo TTL do cache, perda/corrupção, CSRF, sessões isoladas, troca entre abas e retorno ao login. Revisão independente de segurança e UI/UX, CI, merge e publicação somente na homologação, com backup e preservação dos dados.

O PR198 permanece como histórico do fluxo anterior, agora substituído por esta decisão. A otimização de catálogo por requisição permanece. Não altera produção, esquema de banco ou dados escolares.
