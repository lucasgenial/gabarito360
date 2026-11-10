# 048 — Driver OMR externo — Casos de uso

**Natureza:** nova (Candidata; casos propostos de alto nível)
**Perfis envolvidos:** a definir no plano; provavelmente Coordenador e Administrador da Rede para o cadastro de layout, e os perfis de FN-045 para o envio. Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Casos derivados do [docs/08](../../08-omr.md). Nenhum está implementado. Serão detalhados quando a funcionalidade for priorizada.

---

## UC-048-01 — Cadastrar o layout de um cartão de terceiros

**Ator:** a definir
**Permissão:** a definir (nova chave)
**Escopo:** a definir
**Pré-condições:** cartão de referência digitalizado.
**Pós-condições:** layout salvo com regiões, questões e alternativas.
**Tela/rota:** sem mockup

**Fluxo principal:**
1. O usuário envia o cartão de referência.
2. Define as regiões de interesse e o número de questões e alternativas por região.
3. Salva o layout.

**Critérios de aceite:**
- [ ] Layout validado com cartões de teste antes de ficar disponível.

**Situação:** Não implementado

---

## UC-048-02 — Calibrar e validar o layout

**Ator:** a definir
**Permissão:** a definir
**Escopo:** a definir
**Pré-condições:** layout cadastrado.
**Pós-condições:** layout liberado para uso ou recusado com motivo.

**Fluxo principal:**
1. O sistema lê cartões de teste com o layout.
2. O usuário compara com as respostas esperadas e aprova.

**Critérios de aceite:**
- [ ] Taxa de acerto mínima definida no plano.

**Situação:** Não implementado

---

## UC-048-03 — Corrigir cartões de terceiros

**Ator:** perfis com `cartao.enviar` (FN-045)
**Permissão:** `cartao.enviar`
**Escopo:** prova no escopo
**Pré-condições:** prova associada a um layout externo aprovado.
**Pós-condições:** leituras e nota gravadas pelo mesmo fluxo do UC-045-02, com o driver externo.

**Fluxo principal:**
1. O usuário envia a foto como no cartão nativo.
2. O driver externo lê; identificação, qualidade e tentativas seguem regras a definir.

**Critérios de aceite:**
- [ ] Nenhuma mudança no fluxo de acompanhamento, revisão e resultados.

**Situação:** Não implementado
