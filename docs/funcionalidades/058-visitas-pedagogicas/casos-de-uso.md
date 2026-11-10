# 058 — Visitas pedagógicas — Casos de uso

**Natureza:** evolução de existente (leitura implementada; gestão não implementada)
**Perfis envolvidos:** Diretor de Núcleo (`dir_nucleo`); escolas do núcleo como objeto. Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

O UC-009 do [docs/07](../../07-casos-de-uso.md) ("Agendar Visita Pedagógica") é a origem; os casos abaixo o detalham sem repeti-lo.

---

## UC-058-01 — Ver as próximas visitas no dashboard do núcleo

**Ator:** Diretor de Núcleo
**Permissão:** middleware `perfil:dir_nucleo` (sem chave `visita.*`)
**Escopo:** núcleo
**Pré-condições:** usuário com vínculo de diretor de núcleo
**Pós-condições:** nenhuma
**Tela/rota:** web `/painel` → `dashboard/diretor-nucleo.blade.php`; API `GET /dashboard/dir-nucleo` (`DashboardController::dirNucleo`)

**Fluxo principal:**
1. A API consulta `visitas` do núcleo pela `ConsultasPedagogicas` (espaço e coerência escola–núcleo conferidos), com `data_visita` a partir de hoje, ordenadas por data, limite 3, com o nome da escola.
2. O dashboard mostra escola, data (dd/mm/aaaa), tipo e o selo de urgência.

**Fluxos alternativos e de erro:**
- 1a. Sem visitas futuras: "Nenhuma visita agendada."

**Regras de negócio:** UC-009 (pós-condição: visita visível no dashboard do núcleo).

**Critérios de aceite:**
- [x] Só visitas do núcleo e de escolas do núcleo.
- [ ] ⚠ Não há como cadastrar visitas pela aplicação; a lista só mostra registros inseridos fora dela. Resolve: subfase 2.1.
- [ ] ⚠ A leitura vem direto de `visitas`, não da agenda única. Resolve: subfase 2.1 e decisão sobre `visitas` × eventos (FN-056 §8).

**Situação:** Implementado (leitura) — evidência: `DashboardController::dirNucleo`, `ConsultasPedagogicas` caso `visitas`

---

## UC-058-02 — Agendar visita pedagógica

**Ator:** Diretor de Núcleo
**Permissão:** `visita.gerir` (a criar)
**Escopo:** escolas do núcleo
**Pré-condições:** mockup de gestão aprovado; FN-056 C2 entregue
**Pós-condições:** visita gravada e evento correspondente na agenda; visita aparece no dashboard
**Tela/rota:** ação "Agendar Visita" do dashboard do núcleo; tela de gestão (rota a definir)

**Fluxo principal:**
1. Usuário seleciona a escola de destino (só escolas do núcleo).
2. Define data (e horário, se aprovado), tipo e urgência (Prioritária, Monitorar, Referência).
3. Salva; a API grava a visita e registra o evento na agenda pelo contrato de FN-056.

**Fluxos alternativos e de erro:**
- 1a. Escola fora do núcleo: 422/404.
- 2a. Data no passado: 422.

**Regras de negócio:** UC-009; docs/13 MP-032.

**Critérios de aceite:**
- [ ] Visita e evento criados juntos; repetir a requisição não duplica o evento.
- [ ] Nenhuma tabela ou tela de agenda própria criada.

**Situação:** Não implementado — subfases 1.1 e 2.1.

---

## UC-058-03 — Listar e filtrar visitas do núcleo

**Ator:** Diretor de Núcleo
**Permissão:** `visita.ver` (a criar)
**Escopo:** núcleo
**Pré-condições:** subfase 2.1
**Pós-condições:** nenhuma
**Tela/rota:** tela de gestão; também visíveis na agenda (FN-056)

**Fluxo principal:**
1. Usuário vê as visitas por período, escola, tipo e urgência, com paginação.

**Fluxos alternativos e de erro:**
- 1a. Sem resultados: estado vazio.

**Regras de negócio:** UC-009.

**Critérios de aceite:**
- [ ] Mesma fonte da agenda e do dashboard; sem divergência entre as três visões.

**Situação:** Não implementado — subfase 2.1.

---

## UC-058-04 — Alterar visita

**Ator:** Diretor de Núcleo
**Permissão:** `visita.gerir` (a criar)
**Escopo:** núcleo
**Pré-condições:** visita agendada
**Pós-condições:** visita e evento atualizados
**Tela/rota:** tela de gestão

**Fluxo principal:**
1. Usuário altera data, tipo ou urgência e salva.
2. O evento da agenda é atualizado pela origem.

**Fluxos alternativos e de erro:**
- 1a. Visita já realizada ou cancelada: alteração recusada.

**Regras de negócio:** contrato de integração de FN-056 (UC-056-10).

**Critérios de aceite:**
- [ ] Alteração refletida na agenda e no dashboard.

**Situação:** Não implementado — subfase 2.1.

---

## UC-058-05 — Cancelar ou concluir visita

**Ator:** Diretor de Núcleo
**Permissão:** `visita.gerir` (a criar)
**Escopo:** núcleo
**Pré-condições:** visita agendada
**Pós-condições:** situação "cancelada" ou "realizada"; evento cancelado ou marcado como concluído; histórico preservado
**Tela/rota:** tela de gestão

**Fluxo principal:**
1. Usuário cancela (com confirmação) ou marca a visita como realizada.

**Fluxos alternativos e de erro:**
- 1a. Exclusão física não é oferecida.

**Regras de negócio:** docs/177 P5.2.5 (sem apagar histórico relevante), aplicado por analogia.

**Critérios de aceite:**
- [ ] Visita cancelada sai das próximas visitas e permanece no histórico.

**Situação:** Não implementado — subfase 2.1 (requer campo de situação, ver §8 do planejamento).
