# 68 — A1-B: seleção de acessos internos por aba

01/11/2026. PR #101 incorporado com aprovação em `ee96bad`.

## Revisão antes da fase

A1-B atravessa autenticação e concessão de privilégios. Dividir em duas entregas para validar o isolamento antes de expor gestão:

1. **A1-B1 (esta entrega):** token interno ligado à sessão de identidade, seleção explícita por aba, revalidação da versão e revogação, painel interno sem dados pedagógicos. Reutilizar os handles criptografados de V2; nenhum token API no navegador. Exige regime de vínculos e habilitação interna ativos. Habilitação permanece desligada por padrão.
2. **A1-B2:** gestão auditada de Suporte/Financeiro por Desenvolvedor e procedimento controlado para o primeiro Desenvolvedor. Revisar concorrência, proibição de autopromoção e recuperação antes de implementar. Nenhuma conta real será promovida nesta etapa B1.
3. A1-C (Secretário Escolar/hierarquia) e T4-B (cadastro comercial na interface) continuam posteriores.

## Critérios de aceite de B1

- Identidade lista somente atribuições próprias, ativas e vigentes; token escolar ou interno não emite outros contextos.
- Token interno fixa atribuição, versão e identidade de origem. Rejeitar pai contextual, alheio, expirado ou encerrado e metadados inválidos.
- Mudança de versão, suspensão, expiração, conta inativa ou chave de habilitação desligada invalidam o contexto sem retorno ao perfil global.
- Duas abas mantêm perfis independentes. Logout da identidade encerra os contextos; logout contextual não encerra os irmãos.
- Seleção interna sempre explícita, inclusive com uma única atribuição. Contexto interno não chama painel pedagógico nem oferece menu escolar.
- Testes API (SQLite/MariaDB), web e validação visual local via Computer Use. Sem deploy ou migração no servidor.

## Verificação da entrega

Regressão local API: 21 testes, 217 verificações (seleção interna, seleção de vínculos e política interna). Web: 10 testes, 114 verificações (contextos internos e por aba). Os novos testes API integram também o job MariaDB. Revisão de código aplicada aos limites de contexto, origem dos tokens e revogação. CI completo deve estar aprovado antes do merge.

Ensaio visual com conta fictícia em SQLite local: login, opções internas separadas dos vínculos, painel Financeiro sem menu pedagógico. Evidências visuais e resultado final do CI acompanham a entrega. O painel interno é uma entrada mínima: gestão de acessos (B2) e cadastro de clientes (T4-B) continuam pendentes. Novas sessões recebem a disponibilidade da seleção no login; habilitação operacional continua fora deste PR.
