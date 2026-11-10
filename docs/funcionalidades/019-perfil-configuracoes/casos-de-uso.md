# 019 — Meu perfil, configurações e imagem de perfil — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis (perfil e imagem); Administrador da Rede (configurações) — docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

Referências: UC-011 "Editar Perfil do Usuário" e UC-012 "Configurar Sistema" de docs/07 (`docs/07-casos-de-uso.md`). A troca de senha e de e-mail feita nesta tela está em UC-011-05 e UC-011-06 (FN-011).

---

## UC-019-01 — Ver e editar o próprio perfil

**Ator:** qualquer pessoa autenticada
**Permissão:** nenhuma
**Escopo:** própria conta
**Pré-condições:** sessão ativa.
**Pós-condições:** nome atualizado na conta e na sessão.
**Tela/rota:** `mockups/perfil.html`; web `GET|PUT /app/perfil`; API `GET|PUT /api/v1/auth/me`

**Fluxo principal:**
1. A pessoa abre "Meu perfil" pelo menu do usuário.
2. Vê os dados da conta e, com vínculo, perfil e permissões do contexto.
3. Altera o nome (obrigatório, até 200 caracteres) e salva: "Dados atualizados."

**Fluxos alternativos e de erro:**
- 1a. API indisponível: "Não foi possível carregar seu perfil."
- 3a. Falha ao salvar: "Não foi possível atualizar os dados."

**Regras de negócio:** só o próprio perfil; CPF, perfil e escola não são editáveis aqui.

**Critérios de aceite:**
- [ ] Ninguém edita o perfil de outra pessoa por esta rota.
- [ ] O nome novo aparece no topo sem novo login.

**Situação:** Implementado — evidência: `PerfilController::show/update`, `AuthController::me/update`; commit 076e759.

---

## UC-019-02 — Escolher avatar ou enviar foto de perfil

**Ator:** qualquer pessoa autenticada
**Permissão:** nenhuma
**Escopo:** própria conta
**Pré-condições:** sessão ativa.
**Pós-condições:** `avatar_id` ou foto gravados; `imagem_versao` incrementada; foto anterior apagada do disco.
**Tela/rota:** modal Avatar/Foto em `perfil/show`; web `POST /app/perfil/imagem`; API `POST /api/v1/auth/imagem`

**Fluxo principal:**
1. A pessoa escolhe um dos 36 avatares, ou seleciona uma foto.
2. Para foto, ajusta o enquadramento circular, o zoom e a rotação no navegador; HEIC/HEIF é convertido antes do envio.
3. O servidor valida de novo, recodifica para WebP 384×384 e grava.
4. A tela mostra "Imagem de perfil atualizada." e troca a imagem no topo.

**Fluxos alternativos e de erro:**
- 1a. Escolher avatar remove a foto; enviar foto remove o avatar.
- 3a. Arquivo fora da regra (tipo, mais de 2 MB ou mais de 4096×4096): erro de validação.
- 3b. Muitas tentativas: 429 "Muitas tentativas seguidas. Aguarde alguns instantes e tente novamente."
- 3c. Falha de comunicação: "Não foi possível confirmar a atualização. Atualize a página para conferir."
- ⚠ 2a. DNG: só pela prévia JPEG embutida; sem suporte RAW geral, por decisão (subfase 2.3).

**Regras de negócio:** arquivos privados; sem originais nem base64 no banco ou em logs (docs/136).

**Critérios de aceite:**
- [ ] A foto antiga some do disco depois da troca bem-sucedida.
- [ ] Falha na gravação não deixa arquivo novo sem referência.
- [ ] ⚠ Limpeza de órfãos reconciliada e auditada (subfase 3.1).

**Situação:** Implementado (homologação, validado) — evidência: `ImagemUsuarioController::salvar`, `ImagemPerfil::guardar`, `PerfilController::imagem`, PR #164, #166, #167.

---

## UC-019-03 — Ver a foto de perfil

**Ator:** a própria pessoa; gestores da equipe
**Permissão:** própria foto: nenhuma; de outra pessoa: `usuario.ver`
**Escopo:** própria conta ou pessoas na gestão do vínculo
**Pré-condições:** foto gravada.
**Pós-condições:** nenhuma.
**Tela/rota:** web `GET /app/perfil/foto`, `GET /app/usuarios/{id}/foto`; API `GET /api/v1/auth/foto`, `GET /api/v1/usuarios/{id}/foto`

**Fluxo principal:**
1. A tela busca a foto por requisição autenticada; a web repassa à API.
2. A API devolve o WebP com `private, no-store`.

**Fluxos alternativos e de erro:**
- 2a. Pessoa fora da gestão do vínculo, sem `usuario.ver` ou sem foto: 404.

**Critérios de aceite:**
- [ ] Não existe endereço público para fotos de perfil.

**Situação:** Implementado (homologação) — evidência: `ImagemUsuarioController::propria/mostrar`, `PerfilController::foto/fotoUsuario`.

---

## UC-019-04 — Configurar os parâmetros da rede

**Ator:** Administrador da Rede
**Permissão:** `rede.configurar`
**Escopo:** rede
**Pré-condições:** vínculo de rede selecionado.
**Pós-condições:** metas e limiar gravados para a rede.
**Tela/rota:** `mockups/configuracoes.html`; web `GET|PUT /app/configuracoes`; API `GET|PUT /api/v1/configuracoes`

**Fluxo principal:**
1. O administrador abre "Configurações".
2. Com mais de uma rede no alcance, escolhe a rede.
3. Ajusta meta de média e meta mínima (0 a 10) e o limiar de alerta em minutos (1 a 1440).
4. Salva: "Configurações atualizadas."

**Fluxos alternativos e de erro:**
- 1a. Sem `rede.configurar`: 403.
- 4a. Falha: "Não foi possível salvar as configurações."
- ⚠ 1b. Diretor Escolar não tem configurações da escola, embora o mockup §25 e o MP-040 as prevejam (subfase 3.2).
- ⚠ 3a. Integrações, calendário letivo, parâmetros de avaliação e notificações do mockup não existem na tela (dependem de FN-056, FN-057 e FN-039).

**Critérios de aceite:**
- [ ] Só quem tem `rede.configurar` altera os parâmetros.
- [ ] As metas gravadas aparecem nos painéis da rede.

**Situação:** Parcial em relação ao mockup; implementado para metas da rede — evidência: `ConfiguracaoController` (API e web); commit 076e759.
