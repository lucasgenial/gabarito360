# 072 — Site público e apresentação — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** visitante anônimo; operação (publicação da home). Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve. O envio do Fale Conosco pertence a FN-060 (E7).

---

## UC-072-01 — Conhecer o produto na página inicial

**Ator:** visitante anônimo
**Permissão:** nenhuma
**Escopo:** público
**Pré-condições:** nenhuma.
**Pós-condições:** nenhuma; a página não recebe sessão, cookies de aplicação nem token quando servida de forma estática.
**Tela/rota:** `GET /` (`HomeController::index`, view `home.index` com `_recursos`, `_demonstracoes`, `_faq`).

**Fluxo principal:**
1. O visitante abre a página inicial.
2. Vê a narrativa da avaliação (preparação, aplicação, leitura e correção, resultados, decisão), recursos atuais e demonstrações com dados fictícios.
3. Recursos futuros aparecem com o selo "Em breve".
4. Segue para "Falar com a gente", modalidades ou login.

**Fluxos alternativos e de erro:**
- 2a. Celular: menu móvel funciona sem JavaScript.
- 3a. Nenhum preço, periodicidade, compra ou data prometida é exibido.

**Regras de negócio:** docs/164 (sete frentes futuras; sem rótulos "Roadmap" ou "Planejado"); pesquisas com fontes primárias, sem atribuí-las ao produto.

**Critérios de aceite:**
- [x] Botão principal "Falar com a gente" leva a `/fale-conosco`.
- [x] HTML sem "R$".
- [ ] Versão final (pesquisas restauradas e contato compacto) confirmada na homologação.

**Situação:** Parcial — produção tem a landing anterior (PR #59, #60); a apresentação de pré-lançamento está só na homologação (PR #191). ⚠ Versão final Incerta: subfase 1.7; produção: subfase 1.9.

---

## UC-072-02 — Consultar as modalidades de atendimento

**Ator:** visitante anônimo
**Permissão:** nenhuma
**Escopo:** público
**Pré-condições:** nenhuma.
**Pós-condições:** nenhuma.
**Tela/rota:** `GET /planos` (`PlanoController::index`, view `planos.index` com `_apresentacao`; conteúdo em `config/apresentacao.php`).

**Fluxo principal:**
1. O visitante abre as modalidades.
2. Vê Professor autônomo, Escolas, Núcleos e redes e Secretarias, com recursos atuais separados dos futuros.
3. Segue para o contato.

**Fluxos alternativos e de erro:**
- 2a. As modalidades são categorias de comunicação: não criam perfil, contrato ou acesso.

**Regras de negócio:** docs/162 (apresentação sem preços); a página não consulta o catálogo comercial da API.

**Critérios de aceite:**
- [x] Sem valores, limites comerciais ou compra.

**Situação:** Implementado (homologação) — evidência: PR #191; `PaginasPublicasTest`. ⚠ O inventário de mockups ainda descreve "Planos/Preços" com cards de autoatendimento (MP-027); vale a decisão posterior do responsável, pendente de registro pelo §4 item 5.

---

## UC-072-03 — Acessar redes sociais e contato pelo rodapé

**Ator:** visitante anônimo
**Permissão:** nenhuma
**Escopo:** público
**Pré-condições:** nenhuma.
**Pós-condições:** nenhuma.
**Tela/rota:** `components/rodape-publico.blade.php`, presente nas páginas públicas e na home estática.

**Fluxo principal:**
1. O visitante vai ao rodapé.
2. Abre Instagram, Facebook, TikTok ou YouTube em nova aba, com aviso acessível.

**Fluxos alternativos e de erro:**
- 2a. Nenhuma requisição externa é feita ao carregar a página; só ao clicar.

**Regras de negócio:** docs/101 e docs/162 (canais oficiais informados pelo responsável).

**Critérios de aceite:**
- [x] Quatro links com rótulo e nome acessível (`MarcaWebTest`).
- [ ] Links de termos e privacidade (FN-061).

**Situação:** Implementado (homologação). ⚠ Divergência: o plano mestre marca o YouTube como Pendente, mas o link existe no código (subfase 1.3). O rodapé de produção precisa ser reconciliado antes do próximo B5 (subfase 1.8).

---

## UC-072-04 — Ver a página inicial durante a manutenção

**Ator:** visitante anônimo
**Permissão:** nenhuma
**Escopo:** público
**Pré-condições:** home estática publicada e snippet Nginx instalado no servidor.
**Pós-condições:** nenhuma.
**Tela/rota:** Nginx serve `/` e os assets de `/var/www/gabarito360-publico/current`; `manutencao.html`.

**Fluxo principal:**
1. A aplicação entra em manutenção ou o PHP fica indisponível.
2. O visitante abre a página inicial e navega pelas seções normalmente.
3. Ao tentar o login ou outra rota da aplicação, recebe a página 503 com links para voltar ou tentar de novo.

**Fluxos alternativos e de erro:**
- 3a. Chamadas à API recebem 503 em JSON, com `Retry-After: 60` e `Cache-Control: no-store`.
- 3b. Formulários não são aceitos em silêncio enquanto o sistema está parado.
- 3c. Indisponibilidade do próprio Nginx, do host ou da rede ainda afeta o site.

**Regras de negócio:** docs/101.

**Critérios de aceite:**
- [x] Home e assets 200 sem FastCGI no ensaio com Nginx isolado.
- [ ] Configuração aplicada em produção com a home da nova apresentação.

**Situação:** Parcial — a home independente está no servidor desde os PR #135–#137; a versão com bundle da marca foi ensaiada só na VM 120 (docs/162). Produção: subfase 1.9.

---

## UC-072-05 — Publicar nova versão da home estática

**Ator:** operação (root no servidor autorizado)
**Permissão:** não se aplica (procedimento operacional)
**Escopo:** servidor do ambiente
**Pré-condições:** aplicação disponível para renderizar a home de forma anônima.
**Pós-condições:** nova versão ativa; versões e bundles anteriores preservados.
**Tela/rota:** `scripts/operacao/publicar-home.sh <raiz do projeto> <raiz pública> <origem pública>`.

**Fluxo principal:**
1. O operador executa o publicador com a origem pública do ambiente.
2. O exportador renderiza a home, converte links internos em relativos e valida HTML e assets contra a whitelist.
3. O bundle é instalado e o symlink `current` é trocado de forma atômica.

**Fluxos alternativos e de erro:**
- 2a. HTML com formulário, token, contexto de usuário ou "R$": exportação recusada.
- 2b. Arquivo fora da whitelist, symlink ou hash divergente: recusado.
- 3a. Falha antes da troca: a versão anterior continua publicada.
- 3b. Origem com credenciais, IP ou caminho: recusada.

**Regras de negócio:** docs/101, docs/162. O B5 regenera a home após reabrir API e web.

**Critérios de aceite:**
- [x] Falha do publicador preserva o pointer anterior (teste na VM 120).
- [ ] Rodapé e helpers reconciliados antes do próximo B5 em produção.

**Situação:** Implementado — evidência: PR #135, #136, #191. ⚠ Reconciliação pendente: subfase 1.8.
