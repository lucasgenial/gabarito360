# 081 — Créditos de correção — Casos de uso

**Natureza:** nova (candidata; casos de uso preliminares de alto nível)
**Perfis envolvidos:** professor, coordenação e direção que corrigem pelo Leitor360 no modo servidor; responsável da escola ou da organização (titular do saldo, a decidir na D-1); perfil Financeiro (só totais). Ver [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md).
**Atualizado em:** 2026-11-11

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Nenhum caso está implementado. As decisões pendentes estão no [planejamento §8](planejamento.md#8-riscos-e-decisões-pendentes-do-responsável).

---

## UC-081-01 — Consumir um crédito numa correção no servidor

**Ator:** usuário que corrige no modo servidor (FN-079 UC-079-26)
**Permissão:** `cartao.enviar`; chave de consumo de créditos a definir (D-2)
**Escopo:** prova no escopo do vínculo ativo; saldo do titular (D-1)
**Pré-condições:** o usuário aceitou o aviso do modo servidor; existe saldo.
**Pós-condições:** 1 crédito consumido para o aluno × prova; lançamento gravado.
**Tela/rota:** modal "Usar a correção no servidor" (`mockups/leitor360.html`); rota a definir.

**Fluxo principal:**
1. O usuário envia a primeira página de um aluno numa prova.
2. O sistema reserva 1 crédito para esse aluno × prova.
3. As outras páginas do mesmo aluno na mesma prova usam a mesma reserva.
4. A correção termina no servidor, e o sistema confirma o consumo.

**Fluxos alternativos e de erro:**
- 2a. Sem saldo: UC-081-02.
- 4a. Falha do servidor: o sistema estorna a reserva (D-9).

**Regras de negócio:** planejamento §6, itens 1 a 7.

**Critérios de aceite:**
- [ ] Três páginas do mesmo aluno na mesma prova consomem 1 crédito.
- [ ] Dois envios simultâneos do mesmo aluno × prova consomem 1 crédito (teste de concorrência).
- [ ] O processamento no aparelho não gera lançamento.

**Situação:** Não implementado.

---

## UC-081-02 — Recusar o envio sem saldo

**Ator:** usuário que corrige no modo servidor
**Permissão:** `cartao.enviar`
**Escopo:** saldo do titular
**Pré-condições:** saldo zero ou insuficiente para o lote.
**Pós-condições:** nada é enviado; nada é cobrado.
**Tela/rota:** Leitor360, modal da aplicação (ADR-0025).

**Fluxo principal:**
1. O usuário tenta enviar.
2. O sistema recusa e mostra "Os créditos de correção acabaram." (texto proposto) e, para quem pode, o caminho para comprar (UC-081-05).

**Critérios de aceite:**
- [ ] Sem saldo, nenhum envio entra na fila.
- [ ] O aparelho com capacidade continua corrigindo sem créditos.

**Situação:** Não implementado.

---

## UC-081-03 — Reenviar um aluno com dificuldade sem custo

**Ator:** usuário que corrige no modo servidor
**Permissão:** `cartao.enviar`
**Pré-condições:** o servidor marcou dificuldade nesse aluno × prova (FN-079 UC-079-20).
**Pós-condições:** reenvio processado sem novo consumo.

**Fluxo principal:**
1. O usuário escolhe reenviar só os alunos com problema.
2. O sistema reenvia sem novo lançamento de consumo.

**Fluxos alternativos e de erro:**
- 1a. O usuário escolhe a revisão manual: nada é cobrado.
- 2a. Aluno sem a marca de dificuldade: o reenvio cobra normalmente.

**Critérios de aceite:**
- [ ] O reenvio de aluno marcado não muda o saldo.
- [ ] O reenvio de aluno sem marca consome 1 crédito.

**Situação:** Não implementado.

---

## UC-081-04 — Ver saldo e extrato

**Ator:** titular do saldo e quem consome (D-1, D-2); Financeiro (só totais)
**Pré-condições:** saldo existe.
**Pós-condições:** nenhuma.

**Fluxo principal:**
1. O usuário abre a página de créditos.
2. O sistema mostra o saldo, o limite incluso do período e o extrato (entradas, consumos, estornos), com data, autor, prova e aluno para quem tem acesso à prova.

**Critérios de aceite:**
- [ ] O saldo é igual à soma dos lançamentos.
- [ ] O Financeiro não vê nomes de alunos.

**Situação:** Não implementado.

---

## UC-081-05 — Comprar créditos avulsos

**Ator:** titular do saldo
**Pré-condições:** D-4 decidida; FN-068 com cobrança disponível.
**Pós-condições:** créditos entram no saldo depois do pagamento confirmado.

**Fluxo principal:**
1. O titular escolhe um pacote.
2. O sistema gera a cobrança pela FN-068.
3. Com o pagamento confirmado, o sistema credita o pacote.

**Critérios de aceite:**
- [ ] Nenhum crédito entra antes da confirmação do pagamento.
- [ ] O webhook repetido não credita duas vezes.

**Situação:** Não implementado.

---

## UC-081-06 — Renovar o limite incluso no plano

**Ator:** sistema
**Pré-condições:** D-3 e D-5 decididas.
**Pós-condições:** limite do novo período disponível.

**Fluxo principal:**
1. No início do período do plano, o sistema lança o limite incluso.
2. Conforme a D-5, o incluso não usado do período anterior vence.

**Critérios de aceite:**
- [ ] A renovação roda uma vez por período (idempotente).

**Situação:** Não implementado.

---

## Histórico de versões

| Versão | Data | Mudança | Por |
|---|---|---|---|
| 1 | 2026-11-11 | Casos de uso preliminares, com as regras decididas no Leitor360 | lucasgenial |
