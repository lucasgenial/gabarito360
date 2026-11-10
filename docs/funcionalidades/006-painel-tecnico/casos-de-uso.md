# 006 — Painel técnico — Casos de uso

**Natureza:** nova
**Perfis envolvidos:** Desenvolvedor (contexto interno). Todos os demais perfis e contextos são negados. Ver [docs/03](../../03-perfis-e-permissoes.md), extensão A1.
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Todos os casos abaixo são propostos a partir do docs/28 (A2) e **não estão implementados**. O [docs/07](../../07-casos-de-uso.md) não tem caso de uso equivalente.

---

## UC-006-01 — Consultar a saúde dos serviços e a versão publicada

**Ator:** Desenvolvedor
**Permissão:** a definir (contexto interno de Desenvolvedor)
**Escopo:** plataforma
**Pré-condições:** contexto interno de Desenvolvedor selecionado e vigente.
**Pós-condições:** consulta registrada na auditoria (FN-004).
**Tela/rota:** a definir.

**Fluxo principal:**
1. O Desenvolvedor abre o painel técnico.
2. O sistema mostra a situação de API, WEB, banco, Redis, workers e agendador, e o SHA e a fase publicados.

**Fluxos alternativos e de erro:**
- 2a. Um serviço não responde: o card mostra "indisponível" sem derrubar a tela.

**Critérios de aceite:**
- [ ] Dados reais, não demonstrativos.
- [ ] Nenhuma credencial ou variável de ambiente exposta.

**Situação:** Não implementado — resolve: subfase 6.1. Base existente: `GET /api/v1/health`, `GET /health`, `/up`.

---

## UC-006-02 — Consultar filas e trabalhos com falha

**Ator:** Desenvolvedor
**Permissão:** a mesma de UC-006-01
**Escopo:** plataforma
**Pré-condições:** as de UC-006-01.
**Pós-condições:** nenhuma alteração (só leitura).
**Tela/rota:** a definir.

**Fluxo principal:**
1. O sistema mostra o tamanho das filas `alta`, `padrao` e `baixa`.
2. Lista os trabalhos com falha (classe, fila, horário), sem payload.

**Fluxos alternativos e de erro:**
- 2a. Sem falhas: estado vazio.

**Critérios de aceite:**
- [ ] Payload de jobs nunca exibido (jobs de auditoria são cifrados e podem conter contexto).

**Situação:** Não implementado — resolve: subfase 6.1. Hoje: `php artisan queue:failed` no servidor.

---

## UC-006-03 — Consultar a situação dos e-mails

**Ator:** Desenvolvedor
**Permissão:** a mesma de UC-006-01
**Escopo:** plataforma
**Pré-condições:** as de UC-006-01.
**Pós-condições:** nenhuma alteração.
**Tela/rota:** a definir.

**Fluxo principal:**
1. O sistema mostra contagens de e-mails enviados e com falha por período e tipo.

**Critérios de aceite:**
- [ ] Destinatários não aparecem.

**Situação:** Não implementado — resolve: subfase 6.1, com FN-060.

---

## UC-006-04 — Negar o painel técnico aos demais perfis

**Ator:** qualquer usuário que não esteja em contexto interno de Desenvolvedor
**Permissão:** —
**Escopo:** —
**Pré-condições:** —
**Pós-condições:** recusa registrada na auditoria.
**Tela/rota:** a mesma de UC-006-01.

**Fluxo principal:**
1. O usuário tenta abrir o painel.
2. O sistema recusa (o item de menu não aparece; a rota responde 403 ou 404).

**Critérios de aceite:**
- [ ] Testes cobrem Suporte, Financeiro e todos os perfis escolares.

**Situação:** Não implementado — resolve: subfase 6.1.
