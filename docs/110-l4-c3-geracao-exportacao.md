# 110 — L4-C3: geração privada e idempotente

Planejamento revisto antes da implementação em 02/11/2026, em continuidade expressamente autorizada pelo usuário após C2. Origem: docs107/109. Sem deploy até fechar todo L4.

## Decisão de armazenamento e execução

O formato cadastro_minimo_v1 contém exclusivamente nome e/ou matricula do aluno da concessão. Gerar JSON UTF-8 canônico com manifesto (formato, referência opaca, concessão, instante UTC, expiração, seleção e SHA-256 dos dados). Não serializar o model Aluno. Limite de 16 KiB de texto claro; campos novos, notas, CPF e anexos não entram.

Como este formato é pequeno, armazenar o documento completo cifrado com Crypt em coluna privada do MariaDB, sem arquivo no disco público nem caminho/URL. Cifra autenticada protege conteúdo e manifesto; SHA-256 do ciphertext permite conferência adicional. Banco e chave continuam sujeitos aos procedimentos de backup existentes. Formatos maiores exigirão revisão para armazenamento externo e fila; não introduzir esse custo para dois campos.

Geração síncrona em uma única transação protegida por comAutorizacao: validar concessão/escopo/sujeito/destinatário atuais, inserir solicitada, transicionar para gerando, construir e cifrar, persistir e conferir leitura do ciphertext, registrar disponivel e evento obrigatório. Revalidar a concessão e o prazo também ao final, antes do commit. Estados intermediários nunca ficam visíveis fora da transação. Falha reverte artefato e eventos; não há arquivo órfão, recibo de sucesso ou tentativa persistida em falhou nesta operação atômica. Uma repetição após rollback pode executar novamente. Sem job nesta versão.

## Contrato de entrada e idempotência

POST /pedidos-titulares/exportacoes/{autorizacao}: somente contexto interno responsável atual, Desenvolvedor/Suporte, sob as flags existentes e PEDIDOS_EXPORTACAO_GERACAO_ATIVA=false. Entrada fechada: versao da concessão e chave UUID. Não aceitar aluno, cliente, campos, prazo ou destinatário fornecidos pelo solicitante: vêm da concessão.

Chave única por acesso interno/versão, vinculada à concessão e versão esperada. Mesma chave/payload retorna referência mínima existente, após revalidar acesso e prazo; divergência retorna409. Uma concessão produz no máximo um artefato, com índice único; chave nova para concessão já gerada retorna409. Locks do controle interno serializam gerações/revogações; restrições únicas são segunda defesa. Nenhum recibo libera conteúdo ou renova expiração.

Resposta contém somente referência, situação e expiração. Não criar consulta/download, URL pública, e-mail nem interface. C4 terá de conferir destinatário/concessão, integridade, expiração exclusiva e auditoria antes de liberar bytes; nunca ler o ciphertext diretamente num controller sem essa política. Nenhum conteúdo escolar em eventos, erros ou logs.

## Critérios de saída

Testar campos selecionados/manifesto/cifragem, duas instituições, idempotência e divergência, acesso escolar/financeiro/outro destinatário negados, revogação, transferência e expiração antes/durante geração, flags, falha de gravação e de auditoria com rollback, limite de conteúdo e rota HTTP. Executar no MariaDB do CI além da suíte local. Teste de concorrência deve demonstrar restrições únicas e serialização; não declarar ensaio multiprocessos sem evidência. Migration aditiva e reversão bloqueada para preservar histórico. Dados do servidor permanecem intocados.

## Evidência local e limites

Recorte inicial concessão/geração:30 testes e156 asserções aprovados. Suíte final de geração:13 testes e68 asserções aprovados, incluindo falha do último evento, corrupção de gravação e índices únicos. Fixtures compartilhadas com C2, sem mudança de sua política. MariaDB incluído no CI; merge condicionado às cinco verificações. O teste dos índices é sequencial; ensaio multiprocessos permanece para integração C5, sem alegar que foi executado. O limite defensivo de16KiB está acima do máximo normal dos dois campos limitados no esquema; não houve criação de dados maiores no servidor. Nenhuma operação no servidor ou publicação nesta entrega.
