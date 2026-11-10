# 057 — Notificações (módulo transversal) — Casos de uso

**Natureza:** nova
**Perfis envolvidos:** todos os perfis autenticados (destinatários); módulos do sistema (emissores). Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Nenhum caso de uso está implementado. O que existe hoje com o nome "notificações" é o toast de retorno de ação (`layouts/_notificacoes.blade.php`, `js/notificacoes.js`), que lê mensagens de sessão e não persiste nada; ele fica fora desta funcionalidade.

---

## UC-057-01 — Emitir notificação a partir de um módulo

**Ator:** sistema (módulo emissor: FN-042, FN-017, FN-059, FN-067 e outros)
**Permissão:** a do evento de origem, revalidada
**Escopo:** o do objeto de origem
**Pré-condições:** tipo registrado no catálogo único
**Pós-condições:** notificação e destinatários gravados; e-mail enfileirado se a preferência pedir
**Tela/rota:** serviço interno `Notificador::enviar(tipo, dados, destinatarios)`

**Fluxo principal:**
1. O módulo chama o Notificador com tipo, dados mínimos e regra de destinatários (perfil e escopo).
2. O Notificador resolve os destinatários por vínculo ativo e escopo.
3. Agrupa repetições do mesmo evento e grava a notificação.

**Fluxos alternativos e de erro:**
- 2a. Nenhum destinatário válido: nada é gravado; registro técnico sem dados pessoais.
- 3a. Mesma chave de origem repetida: não duplica.

**Regras de negócio:** docs/28 N1; docs/177 P5.4.3–P5.4.4.

**Critérios de aceite:**
- [ ] Testes de destinatários por escopo para cada perfil.
- [ ] Teste de idempotência por chave de origem.

**Situação:** Não implementado — subfase 1.1 (N1).

---

## UC-057-02 — Consultar a central de notificações

**Ator:** usuário autenticado
**Permissão:** nenhuma específica (só as próprias notificações)
**Escopo:** espaço pessoal do usuário, filtrado pelo vínculo ativo
**Pré-condições:** N2 entregue
**Pós-condições:** nenhuma
**Tela/rota:** sino no layout autenticado; lista de notificações (rota a definir)

**Fluxo principal:**
1. O layout mostra o contador de não lidas, atualizado a cada 60 s enquanto a aba está visível.
2. Usuário abre a lista e vê lidas e não lidas.

**Fluxos alternativos e de erro:**
- 1a. Aba oculta: a consulta para e volta ao reabrir.
- 2a. Lista vazia: estado vazio.

**Regras de negócio:** docs/28 N2.

**Critérios de aceite:**
- [ ] Usuário nunca vê notificação de outro usuário.
- [ ] Contador medido com o padrão de 1000 usuários simultâneos.

**Situação:** Não implementado — subfase 2.1 (N2).

---

## UC-057-03 — Marcar notificação como lida

**Ator:** usuário autenticado
**Permissão:** nenhuma específica
**Escopo:** próprias notificações
**Pré-condições:** notificação não lida
**Pós-condições:** notificação marcada como lida; contador atualizado
**Tela/rota:** lista da central

**Fluxo principal:**
1. Usuário marca uma ou todas como lidas.

**Fluxos alternativos e de erro:**
- 1a. Notificação de outro usuário: tratada como inexistente.

**Regras de negócio:** docs/28 N2.

**Critérios de aceite:**
- [ ] Marcação reflete no contador sem recarregar a página.

**Situação:** Não implementado — subfase 2.1 (N2).

---

## UC-057-04 — Definir preferências de canal por tipo

**Ator:** usuário autenticado
**Permissão:** nenhuma específica
**Escopo:** próprias preferências
**Pré-condições:** N3 entregue
**Pós-condições:** preferências gravadas
**Tela/rota:** seção Notificações de configurações (`mockups/configuracoes.html`) ou perfil (a definir)

**Fluxo principal:**
1. Usuário escolhe, por tipo, central, e-mail ou resumo diário.

**Fluxos alternativos e de erro:**
- 1a. Tipos obrigatórios (segurança da conta) não podem ser desligados.

**Regras de negócio:** docs/28 N3.

**Critérios de aceite:**
- [ ] Preferência respeitada nos testes de envio.

**Situação:** Não implementado — subfase 3.1 (N3).

---

## UC-057-05 — Receber notificação por e-mail e resumo diário

**Ator:** sistema (fila `baixa`); destinatário
**Permissão:** revalidada no envio
**Escopo:** o da notificação
**Pré-condições:** N3 e modelos `notificacao-geral` e `resumo-diario` (FN-060, E4 fase 2)
**Pós-condições:** e-mail enviado e registrado em `emails_enviados` (sem corpo)
**Tela/rota:** `EmailModelo` de FN-060

**Fluxo principal:**
1. Notificação com preferência de e-mail é enfileirada.
2. No envio, destinatário, vínculo e preferência são revalidados.
3. O resumo diário agrupa as notificações não lidas do dia.

**Fluxos alternativos e de erro:**
- 2a. Vínculo revogado ou preferência alterada: o envio é cancelado.

**Regras de negócio:** docs/28 N3 e E4; docs/177 P5.4.4.

**Critérios de aceite:**
- [ ] Teste de cancelamento por revogação antes do envio.

**Situação:** Não implementado — subfase 3.1 (N3).
