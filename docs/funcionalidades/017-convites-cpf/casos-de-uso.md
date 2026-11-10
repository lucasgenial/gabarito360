# 017 — Vínculos e convites CPF-primeiro (VI1) — Casos de uso

**Natureza:** nova
**Perfis envolvidos:** gestores com `usuario.gerir` (Admin Rede, Dir. Núcleo, Dir. Escolar, Coordenador, Vice-diretor com extra); titulares convidados; Secretário de Educação ([docs/03](../../03-perfis-e-permissoes.md))
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Todos os casos abaixo são **propostos**, derivados do docs/115 §5. O comportamento atual de convites está em FN-015 (UC-015-02 a UC-015-04).

---

## UC-017-01 — Cadastrar uma pessoa começando pelo CPF

**Ator:** gestor com `usuario.gerir`
**Permissão:** `usuario.gerir` e `identidade.usuario.consultar` (proposta, FN-016)
**Escopo:** instituição no alcance do gestor
**Pré-condições:** matriz de consulta aprovada.
**Pós-condições:** pessoa nova com cadastro mínimo, ou pessoa existente identificada para receber vínculo.
**Tela/rota:** a definir

**Fluxo principal:**
1. O gestor escolhe a instituição e informa o CPF; o restante do formulário fica bloqueado.
2. O sistema normaliza, valida e consulta a identidade protegida.
3. Sem correspondência: libera o cadastro mínimo.
4. Com correspondência: mostra só a identificação mínima; nome, e-mail e senha da conta não podem ser alterados.

**Fluxos alternativos e de erro:**
- 2a. Dois operadores cadastrando o mesmo CPF: a unicidade é revalidada sob transação.
- 2b. Pessoa sem CPF: caminho de exceção com identidade provisória (FN-016).

**Critérios de aceite:**
- [ ] Nenhuma conta duplicada para o mesmo CPF verificado.
- [ ] A consulta fica registrada com ator e motivo.

**Situação:** Não implementado — subfase 1.1 (VI1-A).

---

## UC-017-02 — Propor um novo vínculo a uma pessoa existente

**Ator:** gestor com `usuario.gerir`
**Permissão:** `usuario.gerir`
**Escopo:** instituição no alcance do gestor
**Pré-condições:** pessoa localizada (UC-017-01).
**Pós-condições:** convite enviado ao e-mail já verificado da conta; demais vínculos intactos.
**Tela/rota:** a definir

**Fluxo principal:**
1. O gestor informa papel, instituição, início e alcance.
2. O sistema recusa início anterior ao dia atual no fuso da instituição e aceita início futuro.
3. Envia o convite; o e-mail de acesso não muda a pedido da escola.

**Fluxos alternativos e de erro:**
- 1a. Alterar papel, alcance ou início depois do envio: exige novo convite.

**Critérios de aceite:**
- [ ] Nenhuma permissão retroativa é criada.

**Situação:** Não implementado — subfase 1.1 (VI1-A).

---

## UC-017-03 — Aceitar antecipadamente um vínculo com início futuro

**Ator:** titular da conta
**Permissão:** nenhuma
**Escopo:** vínculo convidado
**Pré-condições:** convite vigente com início futuro.
**Pós-condições:** vínculo `aceito_agendado`; acesso só quando o início chegar e conta e instituição estiverem ativas.
**Tela/rota:** a definir; agendamento pelo módulo de agenda (FN-056)

**Fluxo principal:**
1. O titular aceita ou recusa.
2. Aceito antes do início, o vínculo fica agendado e não aparece como selecionável.
3. Na data, emissor, escopo e papéis são revalidados e o vínculo fica ativo.

**Fluxos alternativos e de erro:**
- 1a. Recusa, cancelamento ou expiração não afetam vínculos de outras instituições.

**Critérios de aceite:**
- [ ] Nenhum acesso antes do início.
- [ ] Todos os vínculos válidos simultâneos aparecem na escolha.

**Situação:** Não implementado — subfase 1.2 (VI1-B); aguarda FN-056.

---

## UC-017-04 — Receber notificações do convite

**Ator:** titular e gestor emissor
**Permissão:** nenhuma
**Escopo:** convite
**Pré-condições:** módulo de notificações (FN-057).
**Pós-condições:** histórico de entrega; reenvio controlado.
**Tela/rota:** a definir

**Fluxo principal:**
1. Emissão, aceite, recusa e expiração geram notificações por outbox transacional.
2. A repetição é idempotente.

**Critérios de aceite:**
- [ ] E-mail de convite não equivale a aceite.

**Situação:** Não implementado — subfase 1.3 (VI1-C); aguarda FN-057.

---

## UC-017-05 — Vincular o Secretário de Educação em contexto

**Ator:** gestor autorizado (a definir)
**Permissão:** a definir
**Escopo:** secretaria/rede
**Pré-condições:** contrato de espaço e seleção aprovado.
**Pós-condições:** vínculo selecionável para o perfil `secretario_educacao`.
**Tela/rota:** a definir

**Fluxo principal:** a desenhar; hoje o perfil não consta em `PerfisEspaco::POR_TIPO` (ver UC-014-05).

**Critérios de aceite:**
- [ ] `catalogo.manter_escopo` só depois do contrato (docs/125).

**Situação:** Não implementado — subfase 1.4.
