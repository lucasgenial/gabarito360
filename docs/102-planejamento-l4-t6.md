# 102 — Preparação L4 e T6 após a ativação

Planejamento revisto em 02/11/2026 após autorização para avançar. Base: plano mestre, docs. 39, 77–100 e código atual de CadastroCliente. Esta entrega organiza a dependência L4 → T6; não implementa rotinas de exclusão, altera contratos ou declara aceite visual de T5.

## Situação verificada

- V1–T5 e AU1 estão publicados. Aceite visual de T5 ainda deve ser registrado separadamente.
- PRs #136 (links públicos) e #137 (redes sociais) integrados com cinco checks aprovados por head. Correções pontuais já disponíveis no servidor.
- CadastroCliente cria clientes ativos e permite editar apenas campos comerciais explícitos; não oferece mudança arbitrária de situação. Manter essa separação.
- Contas internas dev/suporte/financeiro aguardam decisão sobre CPF. Essa pendência não autoriza criar identidades fictícias e não impede o planejamento.
- Servidor ainda usa a release a90fbf5 com ajustes operacionais documentados. Antes de próxima publicação pelo B5, reconciliar o rodapé modificado e preservar os helpers da home; merge não atualiza automaticamente a release.

## Sequência de implementação

| Microfase | Entrega | Critério para avançar |
|---|---|---|
| L4-A | Contrato técnico de protocolos: referência opaca, instituição/espaço, tipo de pedido, responsável, datas e histórico | Matriz de permissões e transições revisadas; testes de isolamento definidos; nenhum CPF ou conteúdo pessoal em logs |
| L4-B | Registro e triagem, sem executar ação sobre os dados do titular | Confirmar identidade/representação e autoridade da instituição antes de disponibilizar conteúdo; não permitir enumeração entre espaços |
| L4-C | Exportação de dados do titular por escopo e entrega controlada | Seleção positiva dos campos, minimização de dados de terceiros, download autorizado e temporário, auditoria de geração/acesso e repetição idempotente |
| L4-D | Correção e avaliação de retenção/preservação | Prazo e fundamento vinculados à instituição/contrato; ausência de política impede eliminação; corrigir sem romper histórico pedagógico |
| L4-E | Simulação de anonimização/eliminação e ensaio de recuperação | Relatório de impacto por tabela/arquivo, bloqueios de preservação e cópias independentes; somente dados sintéticos no ensaio |
| T6-A | Pedido e prévia do encerramento contratual | Inventário de espaços, acessos, filas, exportações e cópias; distinguir cliente de usuário que também tem outros vínculos |
| T6-B | Bloqueio reversível de acesso no escopo encerrado | Revalidar sessões, tokens e jobs; outros clientes/vínculos continuam funcionando; encerramento não significa apagar a identidade global |
| T6-C | Exportação institucional e continuidade autorizada | Exportação institucional tem contrato próprio; não reutilizar indiscriminadamente a exportação de um titular. Continuidade usa decisões explícitas e minimização de T5 |
| T6-D | Retenção, preservação excepcional e simulação final | Guarda por contrato, recibos de exportação e revisão de exceções; rotinas não iniciam só porque uma data venceu |
| T6-E | Execução autorizada e conferência do encerramento | Escopo final aprovado, recuperação ensaiada, tratamento de backups documentado e evidências de preservação dos demais espaços |

O primeiro incremento de implementação é L4-A. Cada microfase deve revisar este quadro, detalhar seu contrato, executar testes proporcionais e seguir o fluxo de publicação do doc. 99. Publicar a tela permite conferência visual pelo responsável; indisponibilidade do computer-use deve ser registrada, não tratada como teste aprovado.

## Contratos a fechar em L4-A

1. Separar pedido de titular, encerramento comercial e operação sobre dados. Um protocolo nunca dispara exportação/exclusão implicitamente.
2. Propor estados recebida, em verificação, em análise, aguardando informação, concluída e cancelada; definir transições e responsável antes de criar a migration. Conclusão requer resultado registrado; cancelamento não apaga histórico.
3. Definir permissão específica de protocolo. Poder comercial para gerir clientes não concede leitura de alunos/notas. Financeiro não recebe conteúdo pedagógico; permissões de Suporte/Desenvolvedor não dispensam escopo nem justificativa.
4. Separar metadados mínimos de evidências protegidas. Decidir limites, criptografia, acesso e retenção antes de aceitar anexos; primeira entrega pode operar sem anexos.
5. Prazo deve registrar origem da regra e datas verificáveis. Não presumir prazo legal universal nem reutilizar os 180 dias da auditoria como retenção de dados escolares. Requisitos legais específicos serão conferidos em fonte oficial ao detalhar a implementação.
6. Vincular sujeito/instituição por referências validadas, sem expor CPF em URL, logs, filtros públicos ou notificações. Evitar resposta que confirme a existência de pessoa em outro espaço.
7. Definir idempotência e concorrência: reenvio não duplica protocolo/operação; mudança de escopo ou permissão entre prévia e execução deve bloquear o processamento.

## Proteção dos dados e validação

- Nunca testar eliminação sobre os gabaritos existentes. Usar fixtures e ambiente isolado; guardar manifesto e comparar conteúdo, não apenas contagens.
- Cobrir duas instituições, usuário com dois vínculos, papel comercial sem poder pedagógico, perda de permissão, pedido duplicado, falha de auditoria, preservação excepcional e inexistência de contrato/prazo.
- Expiração de dados não é licença para remover backup integral compartilhado. Definir ciclo de backup, exceções e procedimento de restauração que reaplique as decisões vigentes antes de reabrir os serviços.
- Copias publicadas têm destino independente. Encerrar origem não apaga automaticamente dados de outro cliente; encerrar destino não altera os originais.
- Encerramento não concede poderes novos para envio de mensagens, exportação de terceiros ou eliminação. Cada operação deve apresentar seu escopo e consequência antes da execução autorizada.

## Saída desta preparação

Sequência e limites registrados; nenhum schema, endpoint ou interface L4/T6 criado. Não houve migration, encerramento de cliente, exportação de dados pessoais ou exclusão nesta etapa. Pendências operacionais e decisões de contas permanecem separadas.
