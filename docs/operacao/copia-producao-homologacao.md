# Cópia pontual da produção para a homologação

**Atualizado em:** 10/11/2026, por lucasgenial
**Ambiente:** origem em produção (CT 111 e CT 106); destino na homologação (VM 120)
**Quando usar:** recarregar a homologação com os usuários e dados atuais da produção, ou desfazer essa carga e voltar ao banco fictício anterior.
**Duração estimada:** 30 a 60 minutos, conforme o volume de arquivos.

Este documento descreve a cópia pontual feita em 03/11/2026, as regras de isolamento e o retorno. A topologia está em [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md). A publicação de código está em [publicacao-homologacao.md](publicacao-homologacao.md).

> **Atenção:** a cópia traz dados pessoais reais para a homologação. Use-a só com autorização expressa do responsável. Ela vale para a homologação e **não autoriza** publicar código nem rodar migrations na produção. A cópia é independente: não existe sincronização automática, e alterações feitas na homologação nunca voltam à produção.

## Decisão que a originou

Em 03/11/2026, o responsável pediu que a homologação mantivesse os mesmos usuários e os dados que já existiam na produção. Essa instrução substituiu a restrição inicial a dados fictícios da ADR-H1. Uma recarga futura exige novo snapshot e a preservação dos testes que se queira manter.

## Pré-requisitos

- Autorização expressa do responsável para copiar dados reais.
- A VM 120 isolada e funcionando (ver [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md)).
- Espaço livre na VM para o banco novo e para os arquivos.
- O banco fictício anterior (`gabarito360_homolog`) preservado para retorno.
- Um local privado no Proxmox e na VM para o snapshot e o manifesto. Não guarde snapshot, material de chaves ou dados pessoais no Git.

## Passos

1. Faça um **snapshot somente de leitura** do banco de origem, sem colocar a produção em manutenção, sem parar escritores e sem alterar registros:

   ```bash
   mariadb-dump --single-transaction --quick --hex-blob --skip-lock-tables <banco>
   ```

   Exporte somente tabelas InnoDB. Compare as contagens das tabelas antes e depois da captura. Em 03/11/2026, as 68 tabelas coincidiram.
2. Copie os arquivos da API e da web por tarball. Compare cada arquivo por tamanho e SHA-256 com a origem. Em 03/11/2026, foram 149 arquivos da API e 3 da web (152 no total). Não copie logs, cookies, sessões web, caches do framework nem o `.env` completo.
3. Na VM, crie um **banco novo** (em 03/11/2026: `gabarito360_homolog_snapshot_20261103`). Não sobrescreva o banco anterior.
4. Importe o snapshot. Compare as contagens de todas as tabelas com o snapshot. Compare também o SHA-256 das linhas canônicas de 13 tabelas de identidade e dados acadêmicos, incluindo os hashes de senha.
5. Aplique as migrations aditivas da release da homologação no banco novo. Compare de novo as 13 tabelas, projetadas nas colunas originais.
6. Preserve os marcadores de release próprios da homologação.
7. Configure as credenciais da homologação (veja a próxima seção).
8. Limpe, **somente no banco novo**: sessões, tokens de acesso e de recuperação, jobs, lotes de jobs, falhas de jobs, caches e trocas de e-mail pendentes. Preserve o histórico acadêmico e a auditoria.
9. Desative o timer da agenda geral da homologação, para não executar cobranças nem rotinas herdadas. Mantenha o worker próprio ativo.
10. Aponte a configuração da VM para o banco novo, reconstrua os caches e reabra.

### Quantidades copiadas em 03/11/2026

Os valores são contagens agregadas, sem nomes.

| Dado | Quantidade |
|---|---:|
| Usuários | 10 |
| Alunos | 414 |
| Provas | 4 |
| Gabaritos | 4 |
| Questões de gabarito | 180 |
| Resultados e notas | 47 |
| Vínculos institucionais válidos | 7 |
| Acessos internos válidos | 3 |
| Arquivos da API | 149 |
| Arquivos da web | 3 |

## Credenciais e isolamento

Siga estas regras. Elas valem para toda recarga futura.

1. Mantenha usuários, e-mails, hashes de senha, permissões e vínculos. O mesmo login e a mesma senha de produção valem na homologação, depois da proteção adicional (Basic Auth do ambiente). A credencial dessa proteção fica em arquivo privado entregue ao responsável. Não a registre em documentos.
2. A `APP_KEY` corrente da API da homologação é exclusiva. O material para ler os valores cifrados antigos entra em `APP_PREVIOUS_KEYS`, protegido. O índice HMAC (`DADOS_HASH_KEY`) é preservado para localizar as mesmas identidades.
3. A chave da web, as credenciais do MariaDB, as sessões, o Redis e as URLs são próprios da homologação.
4. **Não copie** as credenciais de SMTP, os tokens de pagamento, o token do túnel de produção nem as credenciais do banco operacional.
5. O e-mail fica em `log`. O Mercado Pago fica em sandbox, sem credenciais. A execução de transferências fica desativada.
6. O MariaDB e o nginx da VM escutam só em loopback. A VM bloqueia os endereços 10.0.0.6 e 10.0.0.11.
7. O acesso público usa o túnel exclusivo, a Basic Auth, os cabeçalhos `noindex`, `nofollow` e `noarchive` e cookies exclusivos.

## Verificação

1. Consulte as dez identidades pelo HTTP da API, com tokens temporários próprios. Selecione os sete vínculos e consulte provas. Selecione os três acessos internos e consulte-os. Encerre os tokens ao final.
2. Confira a leitura dos campos cifrados e a busca por e-mail para os dez usuários.
3. Confira, pelo HTTPS externo autenticado, que `/up` e `/login` retornam 200, com cabeçalho `noindex`, formulário apontando para o host correto e cookie exclusivo.
4. **Teste o caminho real do formulário.** O POST do login pela web deve chegar a `/api/v1/auth/login` e retornar 401 para uma identidade inexistente. Não use só chamadas diretas à API.
5. Confira que os serviços MariaDB e nginx, PHP-FPM e workers da produção seguem ativos.
6. Faça a conferência visual no Chrome (pendente até o responsável fazê-la). Não apresente testes HTTP como se fossem conferência visual.

## Reversão

1. Coloque somente a homologação em manutenção.
2. Pare o worker da homologação.
3. Restaure as configurações e os arquivos anteriores a partir do backup da VM (`/root/g360-homologa/antes-snapshot-20261103`, dentro da VM). As pastas anteriores de arquivos, cache e sessões foram preservadas com o sufixo `ficticio-20261103`.
4. Aponte a configuração para o banco fictício `gabarito360_homolog`.
5. Reconstrua os caches e valide antes de reabrir.
6. Nunca use o banco operacional da produção como destino de retorno.

## Intercorrência: login recusado após a cópia

Após a cópia, o formulário mostrava "E-mail ou senha incorretos". A URL base da web continha `/api/v1`, e o `LoginController` acrescenta `/v1/auth/login`. O resultado era `/api/v1/v1/auth/login` (404). Os testes diretos de API e o GET da página não cobriam esse caminho.

Correção: apenas a configuração da homologação. A base passou a ser `http://127.0.0.1:8081/api`. Reconstruiu-se o cache e recarregou-se o PHP-FPM. Nenhuma senha foi redefinida. O script operacional não reintroduz o `/v1`. O teste correto é o do passo 4 da verificação.

## Referências

- [13-infraestrutura-e-ambientes.md](../arquitetura/13-infraestrutura-e-ambientes.md)
- [publicacao-homologacao.md](publicacao-homologacao.md)
- [backup-e-restauracao.md](backup-e-restauracao.md)
- [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md)

## Divergências encontradas

| Documento antigo | Divergência | Vale |
|---|---|---|
| `docs/127` | Pedia dados iniciais fictícios e proibia clonar o banco de produção | A revisão autorizada de 03/11/2026 (`docs/128`) substituiu essa regra pela cópia pontual |
| `docs/128` | Dizia que a recuperação completa da VM e o roteiro repetível de publicação (H1-E) estavam pendentes | `docs/129` registrou a restauração da VM em clone e o `deploy.py` |
