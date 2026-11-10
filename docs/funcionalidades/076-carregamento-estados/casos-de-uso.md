# 076 — Carregamento, estados de erro e navegação — Casos de uso

**Natureza:** existente documentada a partir do código (parcial)
**Perfis envolvidos:** todos os perfis autenticados; visitante nas páginas de erro públicas (ver [docs/03](../../03-perfis-e-permissoes.md))
**Atualizado em:** 2026-11-09

Funcionalidade transversal. Relaciona-se a UC-004 (acompanhar correção) e UC-005 (resultado individual) de [docs/07](../../07-casos-de-uso.md), que usam o carregamento regional, o breadcrumb e o toast descritos aqui.

---

## UC-076-01 — Entrar no painel após escolher ou trocar o vínculo

**Ator:** qualquer usuário autenticado
**Permissão:** autenticado com vínculo elegível
**Escopo:** vínculo selecionado
**Pré-condições:** seleção de vínculo concluída com sucesso (login ou troca pelo menu da conta).
**Pós-condições:** painel do novo vínculo exibido.
**Tela/rota:** `/app/painel`; sinal `g360-entrada-vinculo` em `sessionStorage` (`public/js/estado-tela.js`); componente `x-carregando`.

**Fluxo principal:**
1. A seleção bem-sucedida grava, só nesta aba, a revisão retornada.
2. Ao abrir `/app/painel` com a mesma revisão, a aba mostra o preloader integral: marca centralizada e mensagem, pulso suave, sem card.
3. O sinal é consumido uma vez.

**Fluxos alternativos e de erro:**
- 2a. Navegação comum: não há preloader integral; a casca permanece e só o conteúdo pendente mostra esqueleto.
- 2b. Armazenamento bloqueado: segue sem preloader, sem erro.

**Critérios de aceite:**
- [x] Preloader só na aba que confirmou a troca.
- [x] Nenhum identificador na URL; seleção e sessão inalteradas.
- [x] Movimento reduzido respeitado.

**Situação:** Implementado — evidência: PR #201; docs/174.

---

## UC-076-02 — Ver uma página de erro com ação segura

**Ator:** qualquer usuário ou visitante
**Permissão:** —
**Escopo:** —
**Pré-condições:** a Web recebe ou gera erro HTTP 401, 403, 404, 419, 429, 500, 503 (ou outro 4xx/5xx).
**Pós-condições:** página estática com título, mensagem e ações de leitura ou retorno.
**Tela/rota:** `resources/views/errors/estado.blade.php` e `x-estado-tela`.

**Fluxo principal:**
1. A Web mostra título e mensagem do código (por exemplo 403: "Você não tem permissão para acessar esta página").
2. Oferece voltar ao painel (`/app/painel`) na área logada, ou ao início (`/`) fora dela.

**Fluxos alternativos e de erro:**
- 1a. 401/419: ação de entrar novamente; 419 orienta "Não reenvie o formulário anterior".
- 1b. 429 com `Retry-After` válido (1–3600 s): "Aguarde N segundos antes de tentar novamente".
- 1c. 500/503 em leitura (GET/HEAD): oferece tentar novamente; em envio (POST): "Confira se a operação foi concluída antes de tentar novamente. Não reenvie o formulário anterior."

**Critérios de aceite:**
- [x] Nenhum detalhe interno da exceção exibido.
- [x] Erros não se confundem com carregamento (sem animação).
- [x] Funciona sem JavaScript.
- [ ] ⚠ Visual no padrão Tailwind — TW6 de FN-075.

**Situação:** Implementado — evidência: PR #201; `tests/Feature/EstadoTelaWebTest.php`.

---

## UC-076-03 — Carregar uma região da tela de forma independente

**Ator:** qualquer perfil na tela que usa o motor regional
**Permissão:** a da região (revalidada pela API em cada pedido)
**Escopo:** o da tela
**Pré-condições:** região marcada com `data-carga-conteudo`, `data-carga-esqueleto` e `data-carga-feedback`.
**Pós-condições:** região exibida sem bloquear o restante da tela.
**Tela/rota:** correção (`correcao/show`), histórico do resultado (`resultados/_historico`), abas adiadas da turma (`turmas/_secao_adiada`, FN-077/F4-B).

**Fluxo principal:**
1. A estrutura e os estilos aparecem primeiro; a região mostra esqueleto.
2. A promessa da região termina e o conteúdo é renderizado.
3. Atualizações seguintes mantêm os dados anteriores e mostram "Atualizando…".

**Fluxos alternativos e de erro:**
- 2a. Falha na primeira carga: "Não foi possível carregar este bloco." com "Tentar novamente"; o foco volta à região.
- 3a. Falha na atualização: "Não foi possível atualizar. Os dados anteriores foram mantidos."
- 3b. Atualização periódica (polling): feedback silencioso, sem anúncios repetidos.

**Critérios de aceite:**
- [x] Sem tela bloqueadora global.
- [x] Pedido duplicado ignorado enquanto a região está ocupada.
- [ ] ⚠ Carregamento regional em toda a aplicação — hoje só nas três telas citadas; subfase 3.1 (UI1-R).

**Situação:** Parcial — evidência: `public/js/carregamento.js`; docs/159; docs/176.

---

## UC-076-04 — Orientar-se pelo breadcrumb

**Ator:** qualquer perfil autenticado
**Permissão:** cada item só aparece se o perfil pode abrir o destino (`correcao.acompanhar`, `resultado.ver`, `turma.ver`)
**Escopo:** o da tela
**Pré-condições:** tela logada.
**Pós-condições:** trilha com links para os níveis anteriores e página atual marcada (`aria-current="page"`).
**Tela/rota:** `layouts/_breadcrumb.blade.php`; `App\Support\Breadcrumbs::itens`.

**Fluxo principal:**
1. Em `resultados.show`, a trilha segue a origem validada: Correção da prova (`from=correcao`), Relatório da prova (`from=prova`), Relatório da turma (`from=rel-turma`) ou a turma do aluno.
2. Em `correcao.show`/`correcao.revisao`, `provas.index` e `provas.show`, a trilha vem da rota nomeada e dos dados já autorizados.

**Fluxos alternativos e de erro:**
- 1a. Origem não permitida ao perfil: o item é omitido.
- 2a. Rota não mapeada: usa o breadcrumb próprio da view (seção `breadcrumb`), quando existir.

**Critérios de aceite:**
- [x] Só rotas internas nomeadas; nenhum parâmetro acrescentado a documentos assinados.
- [x] Quebra sem rolagem lateral no celular.
- [ ] ⚠ Trilha central nas demais telas — subfase 3.2.

**Situação:** Parcial — evidência: `app/Support/Breadcrumbs.php`; `tests/Feature/BreadcrumbsTest.php`.

---

## UC-076-05 — Receber o retorno de uma ação por toast

**Ator:** qualquer perfil que execute ação com retorno imediato (correção, editor de painéis, compartilhar resultado)
**Permissão:** a da ação
**Escopo:** o da tela
**Pré-condições:** tela com `#g360-notificacoes` (layout autenticado).
**Pós-condições:** mensagem visível sem roubar o foco.
**Tela/rota:** `window.G360Toast` (`public/js/notificacoes.js`); usado em `correcao-acompanhamento.js`, `editor-paineis.js`, `resultado-compartilhar.js`.

**Fluxo principal:**
1. A ação termina e o script chama o toast com tipo e texto seguro.
2. Sucesso some em 5 s; informação em 8 s; pausa em foco, hover ou aba oculta.

**Fluxos alternativos e de erro:**
- 2a. Erro e carregamento ficam até serem fechados (botão de 44 px), que devolve o foco.
- 2b. Mais de três mensagens: as mais antigas saem, preservando a que tem foco.

**Critérios de aceite:**
- [x] Região em fluxo no início do conteúdo, alinhada à direita, sem cobrir controles.
- [x] Movimento reduzido respeitado.
- [ ] ⚠ Ainda há `alert(` em telas de correção — tratado em TW4 de FN-075.

**Situação:** Implementado nas telas citadas — evidência: docs/159; `public/js/libs/solid-toast`.
