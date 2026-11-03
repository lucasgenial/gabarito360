# 111 — L4-C4: consulta e download autenticados

Planejamento revisto antes da implementação em02/11/2026. Continuidade autorizada pelo usuário, a partir de docs107/109/110. Sem deploy até fechar L4 completo.

GET /pedidos-titulares/exportacoes/{artefato} retorna somente referência, estado, formato, campos e expiração. GET /pedidos-titulares/exportacoes/{artefato}/arquivo retorna documento JSON como anexo com nome opaco. UUID é referência, nunca credencial. Sem lista global, URL assinada/pública ou e-mail. Consulta e entrega exigem contexto interno responsável atual Desenvolvedor/Suporte, além da concessão emitida pelo administrador/proprietário. Aprovador escolar não recebe download automaticamente.

Flag PEDIDOS_EXPORTACAO_ENTREGA_ATIVA=false, independente da flag de geração: interromper novas gerações não invalida sozinho os arquivos; desligar autorizações/protocolos ou entrega impede acesso. Preservar políticas C2 de escopo, aluno, conferências, destinatário e emissor. Localizar referência mínima por acesso/versão e revalidar tudo em comAutorizacao, bloqueando o artefato depois do protocolo/concessão, na ordem C3. Conferir versão da concessão e destinatário gravados no artefato. Sem escrita de dados escolares.

Validar estado disponível, prazo futuro exclusivo e não maior que limite atual. Conferir tamanho do ciphertext antes de decifrar, SHA-256, MAC via Crypt, limite16KiB do documento, JSON canônico e contrato fechado. Manifesto deve casar referência/concessão/formato/campos/expiração, instante de geração coerente com persistência e hash do conteúdo. Dados devem conter somente campos autorizados, como strings ou null. Nenhum campo desconhecido, objeto aninhado ou conteúdo de outro artefato. Erros de integridade são genéricos, sem texto claro, ciphertext ou caminhos.

Registrar consulta_validada ou download_liberado na tabela de eventos C3 dentro da transação; falha impede retorno. Revalidar concessão e prazo ao final, antes do retorno da operação. Controller monta resposta só depois que o serviço conclui a transação. Não usar stream/callback que leia dados ou envie bytes após soltar locks. Download_liberado registra autorização da entrega, não prova recebimento completo pelo navegador. Uma revogação após commit não recolhe bytes já autorizados. Nenhum recibo anterior substitui nova validação.

Usar proteção de cache existente da API (private,no-store), reforçar Pragma/Expires/nosniff no download e nome de arquivo composto só por UUID. Não servir respostas304 nem intervalos parciais. HEAD na rota de arquivo é recusado antes da operação; GET é auditado a cada chamada. Sem interface nova (C5), sem migration adicional. Testes HTTP devem cobrir também erro sem autenticação e cache.

Critérios: consulta sem conteúdo, download fiel ao documento autorizado, acesso alheio/escolar/legado/financeiro negado, duas instituições, revogação/expiração/transferência, flags, adulteração de hash/MAC/manifesto/campos e troca de ciphertext entre artefatos, falha de auditoria, expiração durante leitura, cabeçalhos e ausência de download anônimo. Rodar local e CI MariaDB. Ensaio multiprocessos permanece C5 conforme doc110.

## Validação local

28 testes/136 asserções aprovados: consulta mínima, download fiel, auditoria por acesso, contextos indevidos, nove invalidações de autorização/estado/prazo, treze adulterações de integridade/manifesto, troca entre instituições, falha de auditoria e expiração durante leitura, HTTP401/403 e anexo sem cache/304/Range; HEAD405 sem novo evento. Suíte incluída no job MariaDB. CI completo obrigatório antes do merge. Sem interface nova nem conferência visual nesta parte; C5 continua pendente, incluindo ensaio multiprocessos. Nenhuma migration, exportação real ou alteração no servidor.
