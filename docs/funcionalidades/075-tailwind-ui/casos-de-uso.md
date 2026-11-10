# 075 — Migração para Tailwind e UX transversal — Casos de uso

**Natureza:** existente documentada a partir do código (parcial)
**Perfis envolvidos:** todos os perfis autenticados; visitante em TW6 (ver docs/03 (`docs/03-perfis-e-permissoes.md`))
**Atualizado em:** 2026-11-09

Funcionalidade transversal de apresentação. Os fluxos de negócio pertencem às FNs donas de cada tela; aqui ficam as garantias de navegação e interação comuns.

---

## UC-075-01 — Navegar pela estrutura autenticada

**Ator:** qualquer usuário autenticado
**Permissão:** os itens do menu respeitam o perfil e as capacidades do vínculo ativo
**Escopo:** vínculo ativo
**Pré-condições:** sessão autenticada com vínculo selecionado.
**Pós-condições:** usuário navega entre as telas permitidas.
**Tela/rota:** `layouts/app.blade.php` (`body.area-logada.tw2-app`), `public/css/estrutura-app.css`.

**Fluxo principal:**
1. Em ≥ 768 px, o usuário vê cabeçalho fixo de 64 px, lateral de 256 px e o conteúdo na largura útil.
2. Recolhe a lateral para 80 px (só ícones e rótulo curto); a preferência fica salva no navegador.
3. "Meu espaço" aparece no fim da lateral expandida e, recolhido, no topo do conteúdo.

**Fluxos alternativos e de erro:**
- 1a. Abaixo de 768 px: lateral oculta; barra inferior fixa e folha "Mais" (`dialog`), com alvos de 44 px.
- 1b. Impressão: cabeçalho, lateral, rodapé e navegação móvel ocultos.

**Critérios de aceite:**
- [x] Itens do menu conforme permissões, em todos os perfis.
- [x] Foco visível em links e botões da estrutura; link "pular para o conteúdo".
- [ ] ⚠ Smoke desktop/celular da release final registrado (subfase 2.3, Incerto).

**Situação:** Implementado — evidência: PR #223, #224; `tests/Feature/EstruturaTailwindTest.php`.

---

## UC-075-02 — Abrir o menu da conta e trocar de vínculo

**Ator:** qualquer usuário autenticado
**Permissão:** autenticado; "Trocar vínculo" só com mais de um vínculo elegível
**Escopo:** conta
**Pré-condições:** sessão autenticada.
**Pós-condições:** usuário abre o perfil, troca de vínculo ou sai.
**Tela/rota:** avatar no cabeçalho; saída por POST.

**Fluxo principal:**
1. O usuário aciona o avatar; o menu mostra nome completo, e-mail de acesso e perfil.
2. Escolhe "Meu perfil", "Trocar vínculo" ou "Sair".

**Fluxos alternativos e de erro:**
- 2a. Um único vínculo elegível: "Trocar vínculo" não aparece.
- 2b. Troca de vínculo: o preloader integral aparece só na aba que confirmou a troca (FN-076, UC-076-01).

**Critérios de aceite:**
- [x] Saída por POST com CSRF.
- [ ] ⚠ Conferência com usuário de dois vínculos na homologação (docs/186, Incerto).

**Situação:** Implementado — evidência: PR #223; docs/186.

---

## UC-075-03 — Filtrar a lista de turmas por modal

**Ator:** Coordenador, Diretor Escolar, Diretor de Núcleo, Administrador Rede, Professor (conforme permissão de turmas)
**Permissão:** `turma.ver`
**Escopo:** escola, núcleo ou rede do vínculo
**Pré-condições:** tela Turmas.
**Pós-condições:** lista filtrada pelo contrato de busca existente (`busca` por nome ou série).
**Tela/rota:** `turmas/index.blade.php` (`dialog#filtros-turmas`), método GET.

**Fluxo principal:**
1. O usuário aciona o botão de ícone de filtros; abre o modal.
2. Preenche "Nome ou série" e demais filtros; "Aplicar" confirma o recorte.
3. Os filtros aplicados aparecem numa linha discreta ("Busca: ...").

**Fluxos alternativos e de erro:**
- 2a. "Cancelar" ou Escape descartam o rascunho e devolvem o foco ao botão.

**Critérios de aceite:**
- [x] Mesmo contrato GET (`busca`), sem formulário inline.
- [x] Rótulo "Nome ou série".

**Situação:** Implementado — evidência: PR #223, #224; docs/187.

---

## UC-075-04 — Executar ações de uma linha da tabela

**Ator:** qualquer perfil com ações na lista
**Permissão:** a de cada ação (por exemplo `turma.gerir` para Editar e `turma.desativar` para Arquivar/Desarquivar, conforme a tela)
**Escopo:** o da tela
**Pré-condições:** tabela com ações.
**Pós-condições:** ação executada com a mesma rota, CSRF e intenção de antes.
**Tela/rota:** todas as tabelas com célula `tabela-acoes`.

**Fluxo principal:**
1. A coluna Ações é a última, à direita, com título e botões centralizados e largura para todos os botões.
2. O usuário ordena qualquer coluna de dados pelo cabeçalho (crescente/decrescente, com anúncio do resultado).
3. Aciona a ação; Editar e Arquivar/Desarquivar turma têm ícone e texto.

**Fluxos alternativos e de erro:**
- 1a. Celular: ações no rodapé do card; ordenação pelo seletor.
- 2a. Números e datas ordenam por valor semântico; ausências ao final; empates mantêm a ordem original.

**Critérios de aceite:**
- [x] Ações à direita em todas as tabelas com ações (TW3 parcial).
- [x] `aria-sort` atualizado (`public/js/tabelas.js`).
- [ ] ⚠ Estilo Tailwind das ações e cards em todas as famílias — hoje só em Turmas e detalhe da turma; subfase 3.2.

**Situação:** Parcial — evidência: PR #225; docs/188.

---

## UC-075-05 — Confirmar uma operação por modal da aplicação

**Ator:** qualquer perfil que execute operação com confirmação (excluir cartão, retirar leitura, arquivar)
**Permissão:** a da operação
**Escopo:** o da tela
**Pré-condições:** operação exige confirmação.
**Pós-condições:** operação confirmada ou cancelada sem reenvio.
**Tela/rota:** formulários e diálogos de todas as telas.

**Fluxo principal (planejado):**
1. O usuário aciona a operação; abre um modal da aplicação com o efeito explícito.
2. "Confirmar" envia com CSRF e versão; "Cancelar" ou Escape fecha e devolve o foco.
3. Erros aparecem em aviso ou toast contextual, não em `alert`.

**Fluxos alternativos e de erro:**
- 2a. Falha de rede: mensagem contextual; a operação não é reenviada sozinha.

**Regras de negócio:** decisão de 07/11/2026 em `AGENTS.md`.

**Critérios de aceite:**
- [x] Arquivamento de turma usa modal (`arquivamento.js`).
- [ ] ⚠ Ainda há `confirm(` em `correcao-acompanhamento.js` e `alert(` em `correcao-acompanhamento.js`, `correcao-camera.js` e `correcao-revisao.js` — subfase 4.1 (TW4).

**Situação:** Parcial — evidência: busca por `confirm(` e `alert(` em `apps/web/public/js` (fora de `libs/`).

---

## UC-075-06 — Ver painéis, ferramentas e páginas públicas no padrão Tailwind

**Ator:** todos os perfis; visitante nas páginas públicas
**Permissão:** a de cada tela
**Escopo:** o de cada tela
**Pré-condições:** —
**Pós-condições:** GridStack, ECharts, agenda, recorte de imagem, leitores, login, site público, páginas de erro e e-mails com o mesmo padrão visual.
**Tela/rota:** painéis, editor, correção, `auth`, `public`, `errors/*`, e-mails.

**Fluxo principal (proposto):**
1. Cada família é migrada com retirada do CSS concorrente.
2. Preferências do usuário (disposição dos painéis, KPIs fixos) continuam funcionando.
3. PDF e impressão são comparados à versão anterior antes do aceite.

**Critérios de aceite:**
- [ ] Matriz por família/template com conferência por texto em claro/escuro, desktop e celular.
- [ ] PDF/impressão sem regressão.

**Situação:** Não implementado — subfases 5.1 (TW5) e 6.1 (TW6).
