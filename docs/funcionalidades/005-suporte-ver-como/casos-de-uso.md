# 005 — "Ver como" e atendimento auditado — Casos de uso

**Natureza:** nova
**Perfis envolvidos:** Suporte (contexto interno); usuário atendido (qualquer perfil escolar permitido); Desenvolvedor (consulta do registro). Ver [docs/03](../../03-perfis-e-permissoes.md), extensão A1.
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Todos os casos abaixo são propostos a partir do docs/28 (S1) e **não estão implementados**. O [docs/07](../../07-casos-de-uso.md) não tem caso de uso equivalente.

---

## UC-005-01 — Iniciar o "ver como" de um usuário

**Ator:** Suporte
**Permissão:** a definir (contexto interno de Suporte)
**Escopo:** cliente ou espaço do usuário atendido
**Pré-condições:** contexto interno de Suporte selecionado e vigente; D9 decidida.
**Pós-condições:** sessão de "ver como" ativa por até 30 minutos; evento de início registrado.
**Tela/rota:** a definir (ação na tela de membros, docs/28).

**Fluxo principal:**
1. O Suporte escolhe o usuário e informa o motivo.
2. O sistema confere o alvo e emite o token próprio de 30 minutos.
3. A navegação passa a ocorrer como o usuário visto, com faixa fixa.

**Fluxos alternativos e de erro:**
- 1a. Motivo vazio: não inicia.
- 2a. Alvo Desenvolvedor, administrador ou outro Suporte: recusado.
- 2b. Usuário sem vínculo ativo: recusado.

**Regras de negócio:** docs/28 S1, passos 1, 2, 4 e 5.

**Critérios de aceite:**
- [ ] Não inicia sem motivo.
- [ ] Testes cobrem os alvos proibidos.
- [ ] Início registrado com usuário real e visto.

**Situação:** Não implementado — resolve: subfase 5.1.

---

## UC-005-02 — Navegar como o usuário atendido

**Ator:** Suporte em "ver como"
**Permissão:** interseção das permissões do usuário visto com as do Suporte
**Escopo:** o do vínculo do usuário visto
**Pré-condições:** UC-005-01 concluído e token vigente.
**Pós-condições:** cada requisição registrada com `usuario_real_id` (Suporte) e `usuario_visto_id` (atendido).
**Tela/rota:** telas do usuário visto.

**Fluxo principal:**
1. O Suporte navega pelas telas do usuário.
2. O sistema aplica as permissões limitadas e registra cada ação.

**Fluxos alternativos e de erro:**
- 2a. Tentativa de escrita, se D9 confirmar só leitura: recusada com mensagem clara.
- 2b. Dados pedagógicos sem AU3 aprovado: negados.

**Regras de negócio:** docs/28 S1, passos 2, 3 e 6; D9.

**Critérios de aceite:**
- [ ] Escrita bloqueada, se D9 confirmar.
- [ ] Nenhuma permissão além da interseção.

**Situação:** Não implementado — resolve: subfase 5.1.

---

## UC-005-03 — Encerrar o "ver como"

**Ator:** Suporte; sistema (expiração)
**Permissão:** a mesma de UC-005-01
**Escopo:** a sessão de "ver como"
**Pré-condições:** sessão de "ver como" ativa.
**Pós-condições:** volta ao contexto interno do Suporte; evento de fim registrado.
**Tela/rota:** botão "Sair" da faixa fixa.

**Fluxo principal:**
1. O Suporte clica em "Sair".
2. O sistema revoga o token e registra o fim.

**Fluxos alternativos e de erro:**
- 1a. 30 minutos vencidos: o sistema encerra sozinho e informa.
- 1b. Acesso interno revogado durante a sessão: encerra na próxima requisição.

**Critérios de aceite:**
- [ ] Token expirado é recusado.
- [ ] Fim registrado em todos os casos.

**Situação:** Não implementado — resolve: subfase 5.1.

---

## UC-005-04 — Avisar o usuário atendido

**Ator:** sistema
**Permissão:** não se aplica
**Escopo:** o usuário atendido
**Pré-condições:** sessão de "ver como" encerrada.
**Pós-condições:** e-mail enviado pela fila.
**Tela/rota:** modelo de e-mail "suporte-ver-como" (FN-060, E4 fase 2).

**Fluxo principal:**
1. Ao encerrar a sessão, o sistema enfileira o aviso.
2. O usuário recebe data, duração e motivo resumido do atendimento.

**Critérios de aceite:**
- [ ] O aviso não traz dados de terceiros.

**Situação:** Não implementado — resolve: subfase 5.1, com FN-060.

---

## UC-005-05 — Atender com acesso a dados pedagógicos (AU3)

**Ator:** Suporte; possivelmente Desenvolvedor
**Permissão:** a definir no contrato de AU3
**Escopo:** a definir
**Pré-condições:** contrato de AU3 aprovado.
**Pós-condições:** acesso pedagógico registrado e limitado no tempo.
**Tela/rota:** a definir.

**Fluxo principal (preliminar):**
1. O atendimento é aberto com motivo e, se exigido, autorização do cliente.
2. O acesso pedagógico é concedido só ao objeto do atendimento, por prazo definido.
3. Todas as leituras ficam auditadas.

**Critérios de aceite:**
- [ ] Contrato de AU3 aprovado antes de qualquer código.
- [ ] Financeiro continua sem acesso pedagógico (docs/67).

**Situação:** Não implementado — resolve: subfase 5.2.
