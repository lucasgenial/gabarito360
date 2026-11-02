# 109 — Conclusão L4-C2: concessão de exportação

Planejamento revisto antes da implementação em 02/11/2026. O usuário aprovou expressamente somente Administrador da Rede no contexto da própria rede ou proprietário do espaço pessoal. Diretor Escolar, demais perfis escolares, contexto legado e perfis internos não aprovam. A pendência de decisão do doc108 está resolvida. Sem deploy até concluir L4 completo.

## Contrato

Criar permissão pedido.exportacao.autorizar no catálogo, padrão exclusivo de admin_rede e professor_autonomo. Exigir vínculo explícito vigente, permissão efetiva (retiradas são respeitadas), identidade ativa sem troca obrigatória de senha e compatibilidade de espaço/propriedade. A feature tem flag própria PEDIDOS_EXPORTACAO_AUTORIZACOES_ATIVAS=false, além das flags de protocolos e espaços. O catálogo não cria acesso por si só.

Administrador aprova somente protocolo em espaço escolar subordinado à rede do vínculo, com mesmo cliente e hierarquia ativa. Proprietário aprova somente no próprio espaço pessoal. Nem igualdade do cliente pagador nem ID de protocolo conhecido substituem essa validação. Não há lista global de alunos/protocolos. Consulta mínima por UUID informa aluno_id, sujeito_versao, versão/ciclo, responsável e elegibilidade, sem relato ou evidência do atendimento.

Concessão exige protocolo tipo acesso em análise, sujeito vinculado coerente com aluno/turma/espaço e conferências vigentes de identidade e autoridade no ciclo atual. Recebe versão esperada do protocolo, aluno_id e sujeito_versao esperados, destinatario_acesso_id (obrigatoriamente o responsável interno atual), campos, validade explícita, motivo, referência da autorização institucional e chave idempotente. Nenhum booleano de autoridade vindo do formulário.

Primeiro formato fechado: cadastro_minimo_v1, campos nome e/ou matricula, seleção não vazia, distinta e canônica. Isso registra o limite da concessão; não gera arquivo. CPF, nascimento, responsável, notas e anexos não entram implicitamente. Expansão posterior em C3 requer novo formato/concessão, não amplia concessões existentes.

Persistir UUID, protocolo, sujeito/ciclo/versão, aluno, assinatura do vínculo do aprovador, assinatura da localização do aluno, responsável/versão, seleção, prazo UTC e evidência cifrada. Prazo precisa ser futuro e não ultrapassar conferências, atribuição ou fim do vínculo aprovador. Sem prazo legal presumido. Uma concessão atual por protocolo/sujeito/ciclo; nova concessão exige revogar, vencer ou invalidar a anterior. Conceder incrementa versão do protocolo. Recibo idempotente mínimo não é autorização de uso.

Revogação explícita por aprovador atualmente autorizado no escopo exige versão da concessão e motivo; não exige conferências ainda vigentes e não apaga histórico. Retirada de permissão, revogação do vínculo, fim da validade, mudança de sujeito/ciclo ou responsável e saída do aluno do escopo impedem uso mesmo sem atualização de estado por job. Repetir recibo não reativa concessão.

## Transação e integração

Serializar pelo controle de acessos internos, protocolo e concessão. Bloquear identidade/vínculo do aprovador, cliente/espaço e aluno/turma ao revalidar. As alterações de vínculos já versionam e bloqueiam esses registros. Conferir assinatura atual de ContextoVinculo::versaoAcesso; assinatura do aluno contém IDs de aluno, turma e espaço e updated_at, sem conteúdo pessoal. Evento cifrado e recibo são obrigatórios na mesma transação; falha reverte gravações. Datas DATETIME para evitar limite de 2038.

Serviço fornece comAutorizacao para consumidores C3/C4: revalida concessão e contexto interno destinatário antes de executar callback dentro da transação, mantendo locks. Retorno contém apenas referências e campos permitidos, sem conteúdo escolar. Não expor método de validação como permissão reutilizável fora da operação. C3/C4 continuam responsáveis por persistência privada/integridade, auditoria de geração/download e entrega depois do commit; C2 não envia bytes, e-mails nem arquivos.

Rotas de aprovação usam /autorizacoes-exportacao, sob autenticação e contexto escolar/pessoal normal. Não ampliar a exceção de middleware destinada aos protocolos internos. Sem interface nesta parte; UI C5 deverá apresentar sujeito, seleção, destinatário e prazo antes da confirmação.

## Testes e limites

Cobrir aprovador de rede e proprietário, diretor e internos negados, mesmo cliente com outra rede, sujeito/destinatário divergentes, campos desconhecidos, prazo inválido, expiração exata, conferência vencida, renovação de ciclo, alteração de sujeito, revogação de vínculo/permissão, reenvio idêntico e divergente, revogação e falha do evento com rollback. MariaDB no CI. Migration aditiva, sem execução no servidor. C2 não equivale a exportação disponível; C3 gera e C4 entrega.

## Validação local

Recorte de pedidos, concessões e perfis: 49 testes/392 asserções aprovados. Após reforçar as leituras bloqueadas contra snapshots antigos de REPEATABLE READ, suíte específica: 20 testes/99 asserções aprovados. O serviço verifica também os valores atuais de vínculo/permissão/hierarquia, sem depender apenas da assinatura calculada por leituras comuns. Suíte incluída explicitamente no job MariaDB. CI completo deve passar antes da integração.

Migration000010 cria concessões, eventos e recibos e registra a permissão aprovada. Nenhuma migration foi aplicada no servidor. Não há arquivo exportado nem tela nova nesta parte; C3/C4/C5 continuam pendentes.
