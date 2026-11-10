# 007 — Limites, armazenamento externo, separação e observabilidade — Casos de uso

**Natureza:** evolução de existente
**Perfis envolvidos:** todos os usuários (limites); visitantes de páginas públicas (Turnstile); operador técnico (observabilidade). Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para o que existe, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve. O docs/07 (`docs/07-casos-de-uso.md`) não tem caso de uso equivalente.

---

## UC-007-01 — Limitar requisições por rota

**Ator:** qualquer cliente da API ou da WEB
**Permissão:** a da rota (o limite não altera autorização)
**Escopo:** por usuário autenticado ou por IP
**Pré-condições:** rota com limite declarado.
**Pós-condições:** requisições acima da cota recebem `429`.
**Tela/rota:** rotas com `throttle` em `apps/api/routes/api.php` e `apps/web/routes/web.php`.

**Fluxo principal:**
1. O cliente faz a requisição.
2. O limitador conta pela chave (usuário, IP, rota, método, conforme o limitador).
3. Dentro da cota, a requisição segue; acima, `429`.

**Fluxos alternativos e de erro:**
- 1a. Rota sem limite: segue sem contagem.

**Regras de negócio:** docs/28 X3 ("limite por usuário e IP"); AP1-L preserva valores e janelas existentes.

**Critérios de aceite:**
- [x] Rotas públicas de recuperação de senha, convite, confirmação de e-mail, contato e validação de documento têm limite.
- [x] Catálogo, cópias, disciplinas, preferências de painéis, imagem de perfil e PDF da turma têm limite.
- [ ] ⚠ `POST /api/v1/auth/login` e `POST /login` sem limite de tentativas. Resolve: subfase 7.1.
- [ ] ⚠ Demais rotas sem limite (inventário AP1-L a refazer). Resolve: subfase 7.1.

**Situação:** Parcial — evidência: `apps/api/routes/api.php`, `apps/web/routes/web.php`, `App\Providers\AppServiceProvider` (limitadores nomeados).

---

## UC-007-02 — Validar o Turnstile em formulário público

**Ator:** visitante
**Permissão:** nenhuma (rota pública)
**Escopo:** formulário público
**Pré-condições:** `TURNSTILE_ENABLED=true` e chaves configuradas.
**Pós-condições:** envio aceito só com desafio válido.
**Tela/rota:** Fale Conosco — WEB `POST /fale-conosco`; API `POST /api/v1/leads`.

**Fluxo principal:**
1. O visitante preenche o formulário; o widget gera `cf-turnstile-response`.
2. A API valida o token com a Cloudflare, conferindo o hostname.
3. O contato é registrado.

**Fluxos alternativos e de erro:**
- 2a. Flag desligada: o campo é opcional e não é validado.
- 2b. Token inválido: envio recusado.

**Critérios de aceite:**
- [x] Fale Conosco valida o Turnstile quando ligado.
- [ ] ⚠ Demais rotas públicas (login, esqueci a senha, convite) sem Turnstile. Resolve: subfase 7.1.

**Situação:** Parcial — evidência: `App\Services\Turnstile`, `Api\LeadController`, `config/services.php` (API e WEB).

---

## UC-007-03 — Guardar e servir fotos em armazenamento externo

**Ator:** sistema (envio de cartão, imagem de perfil, imagem de aluno)
**Permissão:** a do fluxo de origem (por exemplo `cartao.enviar`)
**Escopo:** o do fluxo de origem
**Pré-condições:** disco externo configurado.
**Pós-condições:** arquivo fora do servidor da aplicação; acesso só por link assinado ou rota autenticada.
**Tela/rota:** a definir.

**Fluxo principal (proposto):**
1. O sistema grava a foto no disco externo.
2. A leitura ocorre por link assinado de curta duração ou pela rota autenticada existente.

**Critérios de aceite:**
- [ ] Nenhuma foto pública por URL permanente.
- [ ] Migração das fotos existentes sem quebrar leituras e revisões.

**Situação:** Não implementado — resolve: subfase 7.2.

---

## UC-007-04 — Serializar a sessão de uma aba entre processos

**Ator:** WEB (BFF)
**Permissão:** a do usuário autenticado
**Escopo:** sessão do usuário
**Pré-condições:** duas requisições simultâneas da mesma sessão.
**Pós-condições:** a sessão é lida, usada e salva por uma requisição de cada vez.
**Tela/rota:** middleware `SerializarSessao` (WEB).

**Fluxo principal:**
1. A requisição obtém o lock da sessão (arquivo com HMAC do SID).
2. Executa e salva a sessão; libera o lock.

**Fluxos alternativos e de erro:**
- 1a. Lock não obtido em 15 s: `423` "Há outra operação em andamento. Tente novamente.", sem executar a operação.

**Regras de negócio:** docs/167 (UR3-B).

**Critérios de aceite:**
- [x] Exclusão real entre processos conferida na CI (passo "Conferir exclusão real entre processos da sessão web").
- [ ] ⚠ Funciona só em um host; mais de um host exige solução distribuída. Resolve: subfase 7.5.

**Situação:** Parcial — evidência: `apps/web/app/Http/Middleware/SerializarSessao.php`, `scripts/auditoria/mutex-sessao-adversarial.py`.

---

## UC-007-05 — Receber alerta de erro ou degradação

**Ator:** operador técnico
**Permissão:** acesso à ferramenta de observabilidade (a definir)
**Escopo:** plataforma
**Pré-condições:** coleta de logs, erros e métricas configurada.
**Pós-condições:** alerta recebido com contexto sem dados pessoais.
**Tela/rota:** a definir.

**Fluxo principal (proposto):**
1. A aplicação emite logs estruturados e erros para a ferramenta.
2. Regras de alerta disparam por taxa de erro, latência, fila acumulada ou `failed_jobs`.
3. O operador recebe o alerta.

**Critérios de aceite:**
- [ ] Alertas testados para API fora do ar, fila parada e falhas acumuladas.
- [ ] Nenhum dado pessoal nos logs enviados (regra de L3, FN-063).

**Situação:** Não implementado — resolve: subfase 7.4.
