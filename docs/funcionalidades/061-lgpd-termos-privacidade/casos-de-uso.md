# 061 — Termos, privacidade e aceite — Casos de uso

**Natureza:** evolução de existente (prévias sem rota no código; o restante é proposto)
**Perfis envolvidos:** visitante anônimo; todos os perfis autenticados das instituições e do espaço pessoal; Desenvolvedor (publicação de versão). Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-061-01 — Consultar o aviso de privacidade e os termos de uso

**Ator:** visitante anônimo ou usuário autenticado
**Permissão:** nenhuma (página pública)
**Escopo:** público
**Pré-condições:** D13 e D14 respondidos; versão vigente publicada.
**Pós-condições:** nenhuma alteração de dados.
**Tela/rota:** `/privacidade` e `/termos` (web, layout público); links no rodapé público.

**Fluxo principal:**
1. O visitante abre o rodapé público e escolhe "Privacidade" ou "Termos".
2. O sistema mostra a versão vigente, com data, identificação da empresa e canal do encarregado.

**Fluxos alternativos e de erro:**
- 2a. Sem versão publicada: a rota não existe (comportamento atual, 404).

**Regras de negócio:** RL15 (canal do encarregado publicado), docs/33 §3 (conteúdo do aviso); campos sem valores inventados (docs/35).

**Critérios de aceite:**
- [ ] As duas páginas abrem sem login, em celular e desktop, nos dois temas.
- [ ] Nenhum campo `[...]` permanece na versão publicada.
- [ ] O canal do encarregado aparece na página de privacidade.

**Situação:** ⚠ Parcial — as views `apps/web/resources/views/legal/privacidade.blade.php` e `termos.blade.php` existem como prévia com campos a preencher, sem rota; `PaginasPublicasTest` confirma 404. Resolve: subfase 1.2.

---

## UC-061-02 — Aceitar nova versão dos termos no login

**Ator:** usuário autenticado (qualquer perfil)
**Permissão:** nenhuma específica; exige sessão válida
**Escopo:** conta do usuário
**Pré-condições:** existe versão vigente que o usuário ainda não aceitou.
**Pós-condições:** aceite registrado com versão e data.
**Tela/rota:** a definir na subfase 1.4 (tela de aceite após o login); endpoint de aceite na API.

**Fluxo principal:**
1. O usuário faz login (UC-001 do [docs/07](../../07-casos-de-uso.md)).
2. O sistema detecta versão não aceita e apresenta o texto resumido com links para a íntegra.
3. O usuário confirma o aceite.
4. A API grava o aceite e libera a navegação.

**Fluxos alternativos e de erro:**
- 3a. O usuário não aceita: a sessão não avança para o sistema e ele pode sair.
- 4a. Falha ao gravar: mensagem de erro e nova tentativa, sem liberar o acesso.

**Regras de negócio:** RL7 (registro de versão e aceite).

**Critérios de aceite:**
- [ ] Usuário sem aceite da versão vigente não acessa telas autenticadas.
- [ ] Novo aceite é pedido só quando a versão muda.
- [ ] O aceite é auditado.

**Situação:** Não implementado — não existem `termos_versoes`, `termos_aceites` nem verificação no login.

---

## UC-061-03 — Publicar nova versão dos termos ou do aviso

**Ator:** Desenvolvedor (a confirmar no contrato da subfase 1.3)
**Permissão:** a definir
**Escopo:** plataforma
**Pré-condições:** texto revisado conforme D14.
**Pós-condições:** nova versão vigente; versões anteriores preservadas.
**Tela/rota:** a definir (pode ser procedimento operacional sem tela).

**Fluxo principal:**
1. O ator registra a nova versão com data de vigência.
2. O sistema passa a exigir aceite (UC-061-02) a partir da vigência.

**Fluxos alternativos e de erro:**
- 1a. Versão sem revisão registrada: publicação recusada.

**Regras de negócio:** RL7; versões anteriores mantidas para prova do que foi aceito.

**Critérios de aceite:**
- [ ] Histórico de versões consultável.
- [ ] Cada aceite aponta para a versão exata.

**Situação:** Não implementado.

---

## UC-061-04 — Escolher cookies opcionais

**Ator:** visitante anônimo ou usuário autenticado
**Permissão:** nenhuma
**Escopo:** navegador do visitante
**Pré-condições:** existir pelo menos um cookie ou rastreador opcional.
**Pós-condições:** escolha registrada no navegador.
**Tela/rota:** aviso discreto nas páginas públicas (a definir).

**Fluxo principal:**
1. O visitante acessa uma página pública.
2. Só cookies necessários são usados por padrão.
3. Se houver opcionais, o visitante pode aceitá-los ou recusá-los.

**Fluxos alternativos e de erro:**
- 2a. Não há opcionais: nenhum aviso é exibido; o aviso de privacidade descreve os necessários (sessão e XSRF, conforme docs/39 §4).

**Regras de negócio:** RL16; RL14 (sem rastreamento em telas de alunos e responsáveis).

**Critérios de aceite:**
- [ ] Nenhum cookie opcional é gravado antes da escolha.
- [ ] Telas de alunos e responsáveis não carregam rastreadores.

**Situação:** Não implementado.
