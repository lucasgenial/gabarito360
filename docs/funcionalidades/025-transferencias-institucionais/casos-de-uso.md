# 025 — Transferências institucionais (TR1, TE1, UX1) — Casos de uso

**Natureza:** nova (casos de uso propostos, derivados do docs/115)
**Perfis envolvidos:** Diretor Escolar e representante escolar autorizado, Diretor de Núcleo, Administrador da Rede, Secretário de Educação, Suporte/Desenvolvedor (delegação), professor autônomo (próprio lado). Matriz proposta no docs/115 §9 (`docs/115-replanejamento-provas-identidade-transferencias.md`); ainda não consta do docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Todos os casos abaixo estão **Não implementados**. As chaves de permissão são propostas e dependem da aprovação da matriz TR1 (subfase 1.1).

---

## UC-025-01 — Solicitar ingresso de aluno vindo de outra escola

**Ator:** representante da escola de destino
**Permissão:** proposta `transferencia.solicitar`
**Escopo:** escola (destino)
**Pré-condições:** vínculo vigente; identidade do aluno por CPF conciliado ou provisória verificada (FN-016).
**Pós-condições:** solicitação em `solicitado`/`aguardando_origem`; ingresso pendente sem liberar histórico protegido.
**Tela/rota:** módulo Transferências → Receber (UX1-C); sem mockup.

**Fluxo principal:**
1. O destino informa a pessoa e consulta matrícula(s) elegível(is), com retorno mínimo protegido.
2. Escolhe origem, categorias de dados, data de corte e motivo.
3. O sistema registra a solicitação e notifica a origem (FN-057).

**Fluxos alternativos e de erro:**
- 1a. CPF sem correspondência → não revela cadastro; segue identidade provisória.
- 1b. Mais de uma matrícula candidata → a mais recente é candidata, nunca prova de exclusividade.

**Critérios de aceite:**
- [ ] Conhecer o CPF alheio não revela histórico nem escola de origem antes do direito de consulta.
- [ ] Mesma chave de intenção não cria segunda solicitação.

**Situação:** Não implementado — TE1-A (subfase 2.1).

---

## UC-025-02 — Decidir a cessão na escola de origem

**Ator:** representante autorizado da escola de origem
**Permissão:** proposta `transferencia.confirmar`
**Escopo:** escola (origem)
**Pré-condições:** solicitação aguardando a origem.
**Pós-condições:** aprovada para o aluno, período e categorias, ou rejeitada com motivo.
**Tela/rota:** Transferências → pendências.

**Fluxo principal:**
1. A origem confere a correspondência da pessoa.
2. Aprova ou rejeita a cessão daquele aluno, período e categorias.

**Fluxos alternativos e de erro:**
- 2a. Pedido amplo (turma inteira, colegas) → bloqueado; seleção não se amplia por dependência.

**Critérios de aceite:**
- [ ] Rejeição não aparece como concluída em nenhuma tela.

**Situação:** Não implementado — TE1-B (2.2) e TR1-A (1.1).

---

## UC-025-03 — Autorizar na gestão superior

**Ator:** Diretor de Núcleo, Administrador da Rede ou Secretário de Educação
**Permissão:** proposta `transferencia.autorizar`
**Escopo:** núcleo | rede
**Pré-condições:** decisões das instituições envolvidas registradas.
**Pós-condições:** solicitação `autorizado` dentro da jurisdição.

**Fluxo principal:** a gestão confere e autoriza no próprio alcance, respeitando a decisão da escola.

**Fluxos alternativos e de erro:**
- Transferência fora da mesma hierarquia → exige decisão dos dois lados ou delegação explícita; nunca autorização unilateral de um núcleo.

**Situação:** Não implementado — TR1-A (1.1).

---

## UC-025-04 — Executar a transferência aprovada e emitir recibo

**Ator:** gestão com capacidade de execução, ou Suporte/Desenvolvedor por delegação do caso
**Permissão:** proposta `transferencia.executar`
**Escopo:** origem e destino
**Pré-condições:** estado `autorizado`.
**Pós-condições:** snapshot versionado cifrado no destino; nova matrícula/enturmação no destino; origem encerra a sua sem apagar fatos; recibo persistido.

**Fluxo principal:**
1. O executor dispara a operação aprovada.
2. O sistema captura o manifesto até o corte, cria IDs locais no destino e publica a unidade de forma idempotente.
3. Registra recibo do sistema e, se houver, confirmação humana do destino.

**Fluxos alternativos e de erro:**
- 2a. Revogação durante o job → publicação seguinte impedida.
- 2b. Auditoria indisponível → falha segura e retomável, sem perder o evento.

**Critérios de aceite:**
- [ ] Escola A mantém fatos anteriores; B vê só o autorizado; A não vê fatos novos de B.
- [ ] Recibo e decisões não aceitam UPDATE/DELETE pela aplicação.

**Situação:** Não implementado — TE1-C (2.3), TR1-B/C (1.2, 1.3).

---

## UC-025-05 — Consultar histórico recebido e registrar retificação

**Ator:** destino (consulta); origem (retificação)
**Permissão:** a do recurso no destino (ex.: `resultado.ver`); proposta `transferencia.retificar` na origem
**Escopo:** escola

**Fluxo principal:**
1. O destino consulta o histórico autorizado com proveniência e versão.
2. A origem corrige um fato anterior ao corte; o sistema gera delta versionado.
3. O destino recebe a nova versão conforme a política de recebimento e é notificado.

**Critérios de aceite:**
- [ ] Retificação não reescreve o pacote finalizado nem recalcula nota antiga.

**Situação:** Não implementado — TE1-D (2.4).

---

## UC-025-06 — Acompanhar transferências no módulo

**Ator:** qualquer participante autorizado
**Permissão:** a do papel na matriz TR1
**Escopo:** do vínculo
**Tela/rota:** Transferências (UX1-A); sem mockup.

**Fluxo principal:** tabela com filtros, estado atual, ator responsável e próximo passo; ações Transferir e Receber conforme o perfil.

**Critérios de aceite:**
- [ ] No máximo duas ações na abertura; estados de carga, vazio e erro.
- [ ] Rótulo "Transferências" só publicado com a política TR1 aplicada.

**Situação:** Não implementado — UX1-A..D (3.1–3.4).

---

## UC-025-07 — Atender caso delegado

**Ator:** Suporte ou Desenvolvedor
**Permissão:** delegação por caso, categoria e prazo (proposta)
**Escopo:** o caso atribuído

**Fluxo principal:** o atendente prepara tecnicamente a operação atribuída, com acesso mínimo e auditado.

**Fluxos alternativos e de erro:** tentativa de ler outro caso ou aprovar como instituição → `403`.

**Situação:** Não implementado — TR1-B (1.2).

---

Integração do espaço pessoal com a escola e continuidade ao sair: ver FN-027 (PA1-C/D), que usa esta matriz.
