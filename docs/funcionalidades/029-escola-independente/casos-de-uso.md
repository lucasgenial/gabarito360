# 029 — Escola independente / autônoma (EI1) — Casos de uso

**Natureza:** nova (casos de uso propostos, derivados do docs/163)
**Perfis envolvidos:** Suporte, Desenvolvedor (contexto interno); Diretor Escolar e equipe da escola (cargos já aprovados); gestores da rede (assunção de custeio). Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Todos os casos estão **Não implementados**: não há commits nem migrations de EI1. Hoje a escola só é criada dentro de um núcleo (UC-002 do docs/07 (`docs/07-casos-de-uso.md`); UC-021-02).

---

## UC-029-01 — Cadastrar escola independente

**Ator:** Suporte ou Desenvolvedor (provisionamento)
**Permissão:** a definir (contexto interno)
**Escopo:** plataforma
**Pré-condições:** adesão aceita (UC-029-04); instituição conferida no catálogo (FN-031) quando houver.
**Pós-condições:** escola sem rede/núcleo obrigatórios, com cliente e espaço próprios e equipe inicial.
**Tela/rota:** sem mockup; variação do cadastro de escola.

**Fluxo principal:**
1. O ator informa a escola, reutilizando INEP, UF/município e sigla, com verificação de duplicidade.
2. Indica se a escola integra uma rede; se sim, seleciona um registro já conferido.
3. O sistema cria cliente, espaço e a equipe inicial com os cargos existentes.

**Fluxos alternativos e de erro:**
- 2a. Rede declarada não existe → abre solicitação ao suporte (UC-029-03); a escola não cria rede.
- 1a. Instituição já operada por outro cliente → reivindicação exige comprovação; selecionar no catálogo não dá acesso.

**Critérios de aceite:**
- [ ] Nenhuma rede ou núcleo fictício é criado.
- [ ] Gestores de uma rede associada não acessam o espaço da escola.

**Situação:** Não implementado — EI1-B, EI1-C, EI1-F.

---

## UC-029-02 — Editar dados e relações institucionais da escola

**Ator:** Diretor Escolar (dados próprios); Suporte (relações institucionais)
**Permissão:** `escola.editar` para dados próprios; correção de relações a definir
**Escopo:** escola

**Fluxo principal:** a escola edita seus dados; troca de rede, categoria administrativa ou instituição superior é proposta e conferida pelo suporte, com trilha.

**Critérios de aceite:**
- [ ] Editar não contorna bloqueios de adesão.
- [ ] Mudança é prospectiva; documentos emitidos e históricos não são reescritos.
- [ ] Solicitação pendente não bloqueia a operação pedagógica.

**Situação:** Não implementado — EI1-B, EI1-D.

---

## UC-029-03 — Solicitar e conferir cadastro de rede ou instituição superior

**Ator:** escola (solicita); Suporte/Desenvolvedor (confere e cria)
**Escopo:** plataforma

**Fluxo principal:** a escola solicita; o suporte confere identificadores e fontes, registra origem, evidência, responsável e data; cria ou associa o registro.

**Critérios de aceite:**
- [ ] INEP da escola não é usado como identificador da rede.
- [ ] Pendência de comprovação não vira vínculo verificado.

**Situação:** Não implementado — EI1-D. Reutiliza o fluxo de solicitações do catálogo (FN-031).

---

## UC-029-04 — Aderir diretamente (escola privada) ou ser encaminhada (escola pública)

**Ator:** representante da escola; Suporte
**Escopo:** plataforma

**Fluxo principal:**
1. Escola privada, com ou sem rede declarada → adesão direta com cliente e espaço próprios.
2. Escola pública com instituição superior identificada → orientada a solicitar por essa via.

**Fluxos alternativos e de erro:**
- 2a. Escola pública insiste em contratar diretamente → liberação explícita de Suporte/Desenvolvedor com motivo, ator, data e alcance.
- 2b. Sem instituição superior confiável → conferência do suporte.

**Critérios de aceite:**
- [ ] Pagamento ou troca de categoria para "privada" não libera a adesão pública.

**Situação:** Não implementado — EI1-E (depende de FN-068).

---

## UC-029-05 — Assumir a responsabilidade financeira de escolas autônomas

**Ator:** gestor da instância superior (rede); Financeiro
**Escopo:** rede

**Fluxo principal:** fluxo próprio e auditável delimita quais escolas/espaços passam à responsabilidade da instância superior e a data de início; só então ela acessa os dados dessas escolas.

**Critérios de aceite:**
- [ ] Valores já pagos não são devolvidos.
- [ ] Histórico anterior, autoria e documentos emitidos preservados.

**Situação:** Não implementado — EI1-E; regras em FN-068.
