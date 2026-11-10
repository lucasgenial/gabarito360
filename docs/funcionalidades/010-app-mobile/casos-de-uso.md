# 010 — Aplicativos Android e iOS — Casos de uso

**Natureza:** nova (adiada)
**Perfis envolvidos:** os mesmos da WEB, conforme [docs/03](../../03-perfis-e-permissoes.md); o foco do MP-023 é quem envia cartões (`cartao.enviar`) e quem consulta resultados.
**Atualizado em:** 2026-11-09

Casos de uso propostos, de alto nível, derivados do MP-023 ([docs/13](../../13-roadmap.md)) e da fase 8 do [docs/02](../../02-visao-geral.md). **Nenhum está implementado**: não existem `apps/android` nem `apps/ios`. Os fluxos de negócio são os do [docs/07](../../07-casos-de-uso.md) e não são duplicados aqui; cada caso só registra o que muda no aplicativo.

---

## UC-010-01 — Autenticar no aplicativo

**Ator:** todos os perfis
**Permissão:** nenhuma (login)
**Escopo:** conta do usuário e vínculo selecionado
**Pré-condições:** usuário cadastrado e ativo.
**Pós-condições:** sessão do app com token guardado de forma segura no dispositivo; vínculo selecionado.
**Tela/rota:** a definir; API `POST /api/v1/auth/login` e seleção de vínculo (FN-011, FN-015).

**Fluxo principal:** o de UC-001 do docs/07, seguido da seleção de vínculo.

**Fluxos alternativos e de erro:**
- Sem conexão: informa e não autentica.

**Critérios de aceite:**
- [ ] Token nunca em URL nem em armazenamento sem proteção.
- [ ] Revogação do vínculo encerra o acesso no app.

**Situação:** Não implementado — resolve: subfase 10.1.

---

## UC-010-02 — Capturar e enviar cartões pela câmera

**Ator:** Professor, Aplicador ou outro perfil com a permissão
**Permissão:** `cartao.enviar`
**Escopo:** prova dentro do escopo do vínculo
**Pré-condições:** prova publicada.
**Pós-condições:** envio aceito (`202`) e acompanhado até o resultado.
**Tela/rota:** a definir; API `POST /api/v1/provas/{id}/cartoes` e acompanhamento do envio ([FN-002](../002-base-operacao/casos-de-uso.md), UC-002-01 e UC-002-02).

**Fluxo principal:** o de UC-004 do docs/07; o app gera o `envio_id` e acompanha o envio.

**Fluxos alternativos e de erro:**
- Falha de rede: o app repete com o mesmo `envio_id`, sem duplicar o cartão.

**Critérios de aceite:**
- [ ] Mesmas recusas de qualidade e de escopo da WEB.

**Situação:** Não implementado — resolve: subfase 10.1. Na WEB móvel o fluxo já existe (FN-045).

---

## UC-010-03 — Ver o painel do perfil e os resultados

**Ator:** todos os perfis
**Permissão:** as mesmas da WEB para cada dado
**Escopo:** o do vínculo selecionado
**Pré-condições:** autenticado (UC-010-01).
**Pós-condições:** nenhuma alteração.
**Tela/rota:** a definir; endpoints de painel e de resultados existentes.

**Fluxo principal:** os de UC-005 e UC-007 do docs/07.

**Critérios de aceite:**
- [ ] O app não mostra dado que a API não entregaria ao mesmo vínculo na WEB.

**Situação:** Não implementado — resolve: subfases 10.1 e 10.2.

---

## UC-010-04 — Trabalhar sem conexão e sincronizar depois

**Ator:** Professor, Aplicador
**Permissão:** `cartao.enviar`
**Escopo:** provas baixadas previamente
**Pré-condições:** dados mínimos da prova no dispositivo; decisão de LGPD sobre guarda local.
**Pós-condições:** fotos capturadas offline enviadas ao recuperar a conexão.
**Tela/rota:** a definir.

**Fluxo principal (preliminar):**
1. O usuário captura os cartões sem conexão.
2. O app guarda as fotos e os identificadores de envio.
3. Com conexão, envia a fila local e mostra o resultado de cada cartão.

**Fluxos alternativos e de erro:**
- 3a. Vínculo revogado ou correção fechada nesse intervalo: os envios são recusados pela API e o app informa.

**Critérios de aceite:**
- [ ] Nenhum envio duplicado após a sincronização.
- [ ] Dados locais eliminados após o envio ou após prazo definido.

**Situação:** Não implementado — resolve: subfase 10.3.
