# 016 — Identidade pessoal (ID1) — Casos de uso

**Natureza:** existente documentada a partir do código (UC-016-01 a 03) e nova (UC-016-04 a 06)
**Perfis envolvidos:** operador do servidor; futuramente Admin Rede, Dir. Núcleo, Dir. Escolar, Coordenador e Secretário escolar, conforme a matriz proposta no docs/139 (docs/03 (`docs/03-perfis-e-permissoes.md`))
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

O docs/07 não tem caso de uso de identidade. Nenhum caso abaixo tem tela ou rota HTTP hoje.

---

## UC-016-01 — Localizar uma pessoa pelo CPF (uso interno)

**Ator:** serviços da API (sem ator humano direto)
**Permissão:** nenhuma chave; não há rota
**Escopo:** global
**Pré-condições:** documento registrado com índice da versão vigente.
**Pós-condições:** nenhuma; leitura.
**Tela/rota:** nenhuma; `ConsultaIdentidadeInterna::porCpf`

**Fluxo principal:**
1. O serviço calcula o índice protegido do CPF.
2. Busca correspondência exata por tipo, país, versão e índice.
3. Devolve só a referência opaca da pessoa (uuid) e a situação do documento, ou nada.

**Regras de negócio:** o resultado não autoriza leitura, vínculo nem fusão (docs/115 §4).

**Critérios de aceite:**
- [ ] Nenhum nome, e-mail, escola ou vínculo é devolvido.
- [ ] Não existe rota HTTP que chame a busca.

**Situação:** Implementado — evidência: `ConsultaIdentidadeInterna`, PR #168; subfase 1.2.

---

## UC-016-02 — Inventariar documentos do legado

**Ator:** operador do servidor
**Permissão:** acesso ao console
**Escopo:** global
**Pré-condições:** nenhuma.
**Pós-condições:** nenhuma escrita.
**Tela/rota:** `php artisan g360:identidades-inventariar --limite=`

**Fluxo principal:**
1. O comando lê os documentos do legado até o limite (máximo 100000).
2. Mostra contagens agregadas, inclusive coincidências de CPF com máscaras diferentes, normalizadas só em memória.

**Critérios de aceite:**
- [ ] A saída não identifica pessoas.
- [ ] Nenhuma linha é alterada.

**Situação:** Implementado — evidência: `InventariarIdentidades`, `InventarioIdentidades`; subfase 1.3.

---

## UC-016-03 — Preparar referências provisórias

**Ator:** operador do servidor
**Permissão:** acesso ao console e flag `IDENTIDADE_PROVISORIOS_ATIVOS` para gravar
**Escopo:** global
**Pré-condições:** para gravar, flag ligada por decisão do responsável.
**Pós-condições:** com `--aplicar`, uma pessoa provisória por conta e por cadastro de aluno, com mapa imutável; sem fusão e sem documento verificado.
**Tela/rota:** `php artisan g360:identidades-provisorias [--aplicar] [--lote=100]`

**Fluxo principal:**
1. Sem `--aplicar`, o comando só simula.
2. Com `--aplicar` e a flag ligada, grava em lotes retomáveis (1 a 1000).

**Fluxos alternativos e de erro:**
- 2a. `--aplicar` com a flag desligada: recusado.
- ⚠ 2b. Execução real em base operacional está proibida até o contrato de conciliação (subfases 2.2 e 2.3).

**Critérios de aceite:**
- [ ] Reexecutar não duplica referências.
- [ ] Os mapas não aceitam UPDATE nem DELETE.

**Situação:** Parcial — implementado com a flag desligada; nunca aplicado em base operacional — evidência: `PrepararIdentidadesProvisorias`, PR #170; subfase 1.4.

---

## UC-016-04 — Consultar a identidade de um usuário para emitir convite

**Ator:** Admin Rede, Dir. Núcleo, Dir. Escolar, Coordenador com `usuario.gerir`
**Permissão:** `identidade.usuario.consultar` (proposta)
**Escopo:** instituição no alcance do operador e perfil de destino gerível
**Pré-condições:** matriz aprovada (§4 item 6).
**Pós-condições:** referência opaca para o convite; consulta auditada com motivo.
**Tela/rota:** a definir em FN-017

**Fluxo principal (proposto):**
1. O operador informa o CPF.
2. O sistema faz consulta exata, limitada em tentativas, e devolve só a identificação mínima autorizada.

**Critérios de aceite:**
- [ ] Não é listagem nem leitura geral de pessoa.
- [ ] E-mail completo, escola de origem e histórico não são revelados.

**Situação:** Não implementado — evidência: docs/139; subfase 2.1.

---

## UC-016-05 — Consultar a identidade de um aluno no ingresso

**Ator:** Dir. Escolar, Coordenador, Secretário escolar com `aluno.gerir`
**Permissão:** `identidade.aluno.consultar` (proposta)
**Escopo:** própria escola
**Pré-condições:** matriz aprovada.
**Pós-condições:** referência para matrícula; sem acesso a escola de origem, notas ou histórico.
**Tela/rota:** a definir (FN-026, FN-025)

**Fluxo principal (proposto):** como UC-016-04, restrito ao ingresso na própria escola.

**Critérios de aceite:**
- [ ] A posse do CPF não vale como consentimento de cessão de dados.

**Situação:** Não implementado — evidência: docs/139; subfase 2.1.

---

## UC-016-06 — Conciliar cadastros da mesma pessoa

**Ator:** operador autorizado (a definir no contrato)
**Permissão:** a definir
**Escopo:** global
**Pré-condições:** contrato de conciliação aprovado (§4 item 12).
**Pós-condições:** cadastros associados à mesma pessoa, com evidência registrada e IDs históricos preservados.
**Tela/rota:** a definir

**Fluxo principal (proposto):**
1. O operador registra o tipo de evidência e a referência protegida da conferência, sem guardar cópia de documento.
2. Divergência de nome ou documento exige segunda conferência.
3. Associação errada pode ser corrigida sem alterar fatos acadêmicos.

**Critérios de aceite:**
- [ ] Nunca fundir por coincidência de CPF, nome ou matrícula.

**Situação:** Não implementado — evidência: docs/139; subfases 2.2 e 2.3.
