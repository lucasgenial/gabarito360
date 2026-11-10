# 028 — Encerramento de cliente (T6) — Casos de uso

**Natureza:** nova (casos de uso propostos, derivados do docs/102)
**Perfis envolvidos:** Suporte, Financeiro, Desenvolvedor (contexto interno); gestor do cliente (Administrador da Rede, Diretor Escolar ou professor autônomo proprietário). Permissões a definir no docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Todos os casos estão **Não implementados**: não há schema, endpoint nem interface de T6.

---

## UC-028-01 — Registrar pedido e ver a prévia do encerramento

**Ator:** Suporte ou Financeiro
**Permissão:** proposta `cliente.encerrar` (contexto interno)
**Escopo:** plataforma (um cliente)
**Pré-condições:** cliente ativo; condição contratual conferida (FN-068).
**Pós-condições:** pedido registrado; nenhum acesso alterado.
**Tela/rota:** detalhe do cliente → Encerramento; sem mockup.

**Fluxo principal:**
1. O ator abre o pedido a partir do cliente.
2. O sistema lista espaços, vínculos por situação, filas, exportações e cessões pendentes.
3. Destaca usuários que têm vínculos em outros clientes.

**Critérios de aceite:**
- [ ] A prévia não altera dados e não expõe dados pedagógicos ao Financeiro.

**Situação:** Não implementado — T6-A (subfase 1.1).

---

## UC-028-02 — Bloquear e desbloquear o acesso do cliente

**Ator:** Suporte
**Permissão:** proposta `cliente.encerrar`
**Escopo:** espaços do cliente
**Pós-condições:** sessões, tokens e jobs do escopo revalidados e recusados; reversível.

**Fluxo principal:** bloquear → usuários do cliente perdem o acesso àqueles espaços; desbloquear restaura.

**Fluxos alternativos e de erro:** usuário com vínculo em outro cliente continua acessando o outro vínculo.

**Critérios de aceite:**
- [ ] Job em andamento do espaço bloqueado não publica resultado.
- [ ] Identidade global e outros vínculos intactos.

**Situação:** Não implementado — T6-B (1.2).

---

## UC-028-03 — Entregar a exportação institucional

**Ator:** gestor do cliente (solicita/recebe); Suporte (prepara)
**Permissão:** a definir
**Escopo:** espaços do cliente

**Fluxo principal:** gerar o pacote institucional com contrato próprio, entregar com recibo; decisões de continuidade (professores, cessões) explícitas e minimizadas.

**Critérios de aceite:**
- [ ] Não reutiliza indiscriminadamente a exportação do titular (FN-064).

**Situação:** Não implementado — T6-C (1.3).

---

## UC-028-04 — Simular retenção e eliminação

**Ator:** Suporte, com aprovação do responsável
**Escopo:** espaços do cliente

**Fluxo principal:** aplicar os prazos do contrato, listar exceções de preservação e simular o resultado final sem executar.

**Critérios de aceite:**
- [ ] Nenhuma rotina inicia apenas porque uma data venceu.

**Situação:** Não implementado — T6-D (1.4); depende de FN-064 (L4-D/E) e FN-062.

---

## UC-028-05 — Executar e conferir o encerramento

**Ator:** Desenvolvedor/Suporte com autorização expressa
**Escopo:** espaços do cliente

**Fluxo principal:** executar o escopo final aprovado, de forma idempotente e com recibo; conferir a preservação dos demais espaços; registrar o tratamento dos backups.

**Critérios de aceite:**
- [ ] Recuperação ensaiada antes da execução.
- [ ] Cessões validamente recebidas por terceiros (FN-025) não são apagadas.

**Situação:** Não implementado — T6-E (1.5).
