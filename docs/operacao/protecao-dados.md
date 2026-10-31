# L3 — Proteção de CPF, contatos, fila e logs

Implementação para revisão em 31/10/2026. Não altera o plano mestre nem autoriza merge, migração, configuração ou deploy em produção.

## O que muda

- `usuarios.email`, `usuarios.cpf`, `usuarios.telefone`, `alunos.cpf`, `leads.email`, `leads.telefone`, `escolas.email`, `escolas.telefone` e `trocas_email.novo_email` passam a usar a cifra autenticada do Laravel, com prefixo de versão `g360:v1:`. Valores nulos permanecem nulos. Não há retorno silencioso para texto simples se a chave estiver errada ou o conteúdo estiver adulterado.
- E-mail e CPF de usuários e CPF de alunos têm índices HMAC-SHA256 para pesquisa exata e unicidade. Os índices não são serializados pela API. A pesquisa parcial de membros continua funcionando: primeiro limita pelo escopo e perfil, depois filtra nome/e-mail já descriptografados. Não cria índice de fragmentos de e-mail; medir essa lista se o volume crescer.
- Login, validação de duplicidade, edição, convites, recuperação e confirmação de e-mail usam os índices. As listas de equipes/professores e a importação de contatos do Censo passam pelo modelo para aplicar a cifra.
- A chave dos registros de recuperação passa a ser o índice do e-mail. Os novos links levam `conta` opaca e token; os links antigos com e-mail continuam aceitos até o vencimento original. O token continua obrigatório e a validade não muda. O identificador opaco é pseudônimo, não prova de anonimização.
- E-mails transacionais são cifrados enquanto enfileirados, inclusive no payload preservado em `failed_jobs`. O destinatário e o conteúdo continuam necessários na entrega SMTP. Não se envia CPF em novos modelos.
- API e web omitem conteúdo livre de logs por padrão, preservando eventos técnicos conhecidos, IDs numéricos permitidos e diagnóstico de exceção (classe, arquivo, linha e pilha sem argumentos). SQL/bindings, corpo HTTP/SMTP, mensagens livres e objetos não são registrados. Novos eventos técnicos precisam ser adicionados à lista positiva, sem dados pessoais.
- O provedor `database-uuids` preserva payload para retentativa, mas grava somente diagnóstico sanitizado de exceção. Outros provedores de falhas não foram habilitados ou certificados.
- Respostas da aplicação recebem `Referrer-Policy: no-referrer` e `Cache-Control: private, no-store`. Arquivos estáticos servidos diretamente pelo nginx seguem sua configuração própria. A aparência das páginas não muda.

A base pública de referência do Censo mantém o telefone institucional publicado pelo INEP. O contato do cadastro editável da escola é cifrado. Nomes, matrículas, notas, datas de nascimento, mensagens livres e fotos não são abrangidos pela criptografia de campos deste pacote; continuam sujeitos a escopo, minimização e aos pacotes específicos. A futura informação de contato dos responsáveis e o código do portal devem usar proteção própria quando esses modelos existirem.

## Chaves e publicação futura

1. Exigir aprovação de merge/deploy e da configuração da chave dedicada `DADOS_HASH_KEY` na API. Gerar ao menos 32 bytes aleatórios, guardar em cofre e gravar pelo procedimento de segredos sem imprimir o valor. Fora do ambiente `testing`, a ausência da chave bloqueia a migração e a busca; não há fallback silencioso para `APP_KEY`.
2. Guardar separadamente a chave de busca, a `APP_KEY` da API e quaisquer `APP_PREVIOUS_KEYS` necessárias. Banco e chaves são necessários para recuperar os dados. Não registrar chaves em relatório, comando compartilhado, Git ou conversa.
3. Manter `DADOS_HASH_KEY` estável. Sua troca exige procedimento explícito de reindexação, incluindo recuperação/convites; não trocá-la como parte de uma rotação comum da `APP_KEY`. Para rotação da cifra, preservar as chaves anteriores até recifrar e verificar todos os registros e jobs.
4. Antes da publicação autorizada, obter backup recente e confirmar sua restauração com as chaves em ambiente isolado. O ensaio desta entrega usa somente dados fictícios; não substitui a conferência operacional das chaves reais no momento da publicação.
5. Usar o roteiro B5, com manutenção da web/API e fila drenada. Não executar a migração enquanto processos do código antigo ainda possam escrever contatos em texto simples. A fila deve estar vazia antes de converter os campos.
6. A migração amplia campos para texto, cria índices únicos e converte os registros em lotes. DDL do MariaDB não é transacional: se ocorrer falha, manter a manutenção, investigar sem imprimir valores e retomar com as mesmas chaves. O prefixo evita recifrar lotes concluídos. Colisões de índice impedem a conversão; não descartar registros para contorná-las.
7. Conferir login, convite/recuperação legados e novos, confirmação de e-mail, cadastro/edição de membros/alunos/escolas, Fale Conosco e consumo da fila. Usar contas fictícias autorizadas, sem envio a terceiros.
8. `migrate:rollback` não descriptografa esses campos. Retorno ao código antigo exige decisão explícita e restauração consistente do backup anterior, banco e chaves; nunca resetar apenas o Git após a migração.

## Limites operacionais que ainda exigem aprovação

- A política da aplicação não modifica nem elimina logs históricos, caixas de e-mail, payloads de falhas anteriores ou backups em texto simples. Retenção e descarte permanecem no fluxo aprovado do projeto.
- O nginx atual usa o log de acesso padrão, que pode conter query strings dos links. A revisão deve trocar seu formato por um que omita query string, cabeçalhos, IP e corpo; avaliar também logs de erro e Cloudflare. Isso não é aplicado pelo PR nem pelo roteiro B5 automaticamente. Não declarar que todos os logs da infraestrutura estão saneados.
- O virtual host consultado encaminha `/api/v1/` ao PHP da API e usa o `public` da web como raiz. Não foi demonstrada exposição de arquivos reais e nenhum arquivo pessoal foi baixado. Preservar o acesso autenticado/assinado às imagens e bloquear publicação direta dos diretórios de fotos antes de criar qualquer link público de storage. L6 permanece responsável por retirar fotos de alunos.
- Sem alteração de retenção, autorização de dados, termos públicos, infraestrutura ou planejamento nesta entrega. L3 fica sujeito à revisão e à validação operacional posterior; não declarar conformidade jurídica integral.

## Validação

- Regressões de criptografia/leitura, hash e unicidade, alteração/limpeza de campo, falha com cifra adulterada, conversão de registros legados e repetição da migração.
- Recuperação nova sem e-mail na URL, compatibilidade dos links antigos, payload de e-mail cifrado e restaurável, e rejeição de chave incorreta.
- Logs finais de API/web testados com nomes, CPF, e-mail, senha, SQL, exceção e texto SMTP fictícios. Teste do registro de falha da fila sem mensagem sensível.
- MariaDB no banco padrão `gabarito360_teste`, usando a cópia existente do esquema e dados fictícios: migração, leitura e índices, repetição sem recifrar, dump cifrado, restauração do dump e recuperação do job com a chave correta; chave diferente recusada.
- Depois do ensaio, a cópia de teste foi restaurada ao estado anterior (dois usuários e sem a migração L3), a credencial temporária foi revogada e os dumps temporários removidos. Nenhum dado de produção foi convertido ou publicado.
- Testes completos de API/web, verificação estática de telas e CI registrados no PR. Não houve alteração visual que exigisse novo aceite de layout.
