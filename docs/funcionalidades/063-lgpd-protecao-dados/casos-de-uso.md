# 063 — Proteção dos dados (cifragem) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis que cadastram ou consultam usuários, alunos, escolas e contatos; visitante do Fale Conosco; operação. Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Os casos abaixo descrevem garantias transversais aplicadas a fluxos já documentados em outras funcionalidades (login UC-001 e cadastros do docs/07 (`docs/07-casos-de-uso.md`)).

---

## UC-063-01 — Gravar e ler CPF e contatos protegidos

**Ator:** qualquer perfil que cadastra ou edita usuário, aluno, escola ou lead
**Permissão:** a do cadastro correspondente (por exemplo `aluno.gerir`)
**Escopo:** o do cadastro
**Pré-condições:** `DADOS_HASH_KEY` configurada na API.
**Pós-condições:** valor gravado cifrado com prefixo `g360:v1:`; índice HMAC atualizado quando o campo é pesquisável.
**Tela/rota:** formulários de usuários, alunos, escolas e Fale Conosco; cast `apps/api/app/Casts/DadoProtegido.php`.

**Fluxo principal:**
1. O ator salva um formulário com CPF, e-mail ou telefone.
2. A API cifra o valor e calcula o índice de busca.
3. Na leitura autorizada, a API descriptografa e devolve o valor; o índice nunca é serializado.

**Fluxos alternativos e de erro:**
- 2a. CPF ou e-mail já existente no índice: recusa por duplicidade (`UnicoProtegido`).
- 3a. Cifra adulterada ou chave errada: falha explícita, sem retorno em texto simples.

**Regras de negócio:** RL8.

**Critérios de aceite:**
- [x] Login, convites, recuperação e confirmação de e-mail usam o índice.
- [x] Índices não aparecem nas respostas da API.

**Situação:** Implementado — evidência: PR #69; docs/operacao/protecao-dados.md (validação e publicação de 31/10/2026).

---

## UC-063-02 — Recuperar a senha sem dado pessoal na URL

**Ator:** usuário sem sessão
**Permissão:** nenhuma
**Escopo:** conta própria
**Pré-condições:** conta ativa.
**Pós-condições:** link com conta opaca e token; e-mail enfileirado cifrado.
**Tela/rota:** fluxo de recuperação (FN-011, E3 de FN-060).

**Fluxo principal:**
1. O usuário pede a recuperação.
2. O sistema gera link com identificador opaco e token, sem e-mail.
3. O e-mail fica cifrado na fila até o envio SMTP.

**Fluxos alternativos e de erro:**
- 2a. Links antigos com e-mail continuam aceitos até o vencimento original.

**Regras de negócio:** RL9.

**Critérios de aceite:**
- [x] Link novo sem e-mail na URL.
- [x] Payload do job cifrado, inclusive em `failed_jobs`.

**Situação:** Implementado — evidência: PR #69.

---

## UC-063-03 — Registrar eventos técnicos sem dados pessoais

**Ator:** API, web e Nginx
**Permissão:** não se aplica
**Escopo:** plataforma
**Pré-condições:** nenhuma.
**Pós-condições:** log contém só eventos da lista positiva, IDs numéricos permitidos e diagnóstico de exceção sem argumentos.
**Tela/rota:** `apps/api/app/Logging/ProtegerDados.php`, `apps/web/app/Logging/ProtegerDados.php`; logs protegidos do Nginx.

**Fluxo principal:**
1. Ocorre um evento ou exceção.
2. O formatador descarta SQL, bindings, corpo HTTP/SMTP, mensagens livres e objetos.
3. Grava classe, arquivo, linha e pilha sem argumentos.

**Fluxos alternativos e de erro:**
- 2a. Evento novo fora da lista positiva: registrado sem conteúdo livre.

**Regras de negócio:** RL9.

**Critérios de aceite:**
- [x] Testes com nome, CPF, e-mail, senha e SQL fictícios não deixam marcas nos logs.

**Situação:** Implementado — evidência: PR #69. Logs históricos não foram alterados (tratamento em FN-062).

---

## UC-063-04 — Responder sem cache e sem referrer

**Ator:** API e web
**Permissão:** não se aplica
**Escopo:** todas as respostas da aplicação
**Pré-condições:** nenhuma.
**Pós-condições:** cabeçalhos `Referrer-Policy: no-referrer` e `Cache-Control: private, no-store`.
**Tela/rota:** middleware `ProtegerRespostas` (API e web).

**Fluxo principal:**
1. A aplicação produz qualquer resposta.
2. O middleware acrescenta os cabeçalhos.

**Fluxos alternativos e de erro:**
- 1a. Arquivos estáticos servidos pelo Nginx seguem configuração própria.

**Regras de negócio:** RL9.

**Critérios de aceite:**
- [x] Início, login, Fale Conosco e health responderam com os cabeçalhos em produção (31/10/2026).

**Situação:** Implementado — evidência: docs/operacao/protecao-dados.md.
