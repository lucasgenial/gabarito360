# 082 — Família de ícones G360 — Casos de uso

**Natureza:** nova
**Perfis envolvidos:** nenhum perfil do app. Os atores são a equipe de desenvolvimento e de design. Ver [04 Identidade, acesso e permissões](../../arquitetura/04-identidade-acesso-e-permissoes.md).
**Atualizado em:** 2026-11-13

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. As regras estão no [planejamento §6](planejamento.md) e no [ADR-0063](../../decisoes/0063-familia-de-icones-propria-em-fonte.md).

---

## UC-082-01 — Usar um ícone da família numa página

**Ator:** desenvolvedor ou agente
**Permissão:** não se aplica
**Escopo:** todo o app web
**Pré-condições:** a página carrega `apps/web/public/css/g360-icones.css`.
**Pós-condições:** o ícone aparece com a cor do texto do controle.
**Tela/rota:** qualquer tela; nesta entrega, nenhuma tela usa a fonte ainda

**Fluxo principal:**
1. Procure o id do conceito em `style-system/icones/catalogo.json`.
2. Escreva `<span class="g360i g360i-<id>" aria-hidden="true"></span>`.
3. Para o item ativo do menu, acrescente `g360i--preenchido`.
4. Para cor, use só as classes de tom (`g360i--azul` e outras) ou `currentColor`.

**Fluxos alternativos e de erro:**
- 1a. O conceito não existe no catálogo. Siga o UC-082-02. Não use emoji, caractere ou SVG avulso.

**Regras de negócio:** ADR-0063.

**Critérios de aceite:**
- [ ] O CSS declara as duas famílias e aponta para `../fonts/icones/*.woff2`, que existem.
- [ ] Cada id do `registro.json` tem uma classe `g360i-<id>` com o mesmo código no CSS.
- [ ] Nenhuma regra de `.g360i` usa cor fixa.

**Situação:** Implementado (arquivos); uso nas telas pendente — evidência: `apps/web/public/css/g360-icones.css`

---

## UC-082-02 — Adicionar um ícone novo

**Ator:** designer ou agente
**Permissão:** não se aplica
**Escopo:** família G360 Icones
**Pré-condições:** o conceito não existe no catálogo nem no mapa de metáforas.
**Pós-condições:** o ícone novo tem código no fim do registro e aparece nas duas variantes.
**Tela/rota:** `style-system/icones/`

**Fluxo principal:**
1. Siga `style-system/icones/COMO-ADICIONAR.md`.
2. Compile a fonte e o CSS.
3. Copie os arquivos compilados para `apps/web/public/fonts/icones/` e `apps/web/public/css/`.

**Fluxos alternativos e de erro:**
- 1a. A metáfora pertence a outro conceito. Use selo ou escolha outra metáfora.

**Regras de negócio:** códigos fixos; um conceito, um desenho, um id (ADR-0063).

**Critérios de aceite:**
- [ ] Nenhum código existente muda no `registro.json`.
- [ ] O código novo fica depois do último código.

**Situação:** Implementado (fluxo documentado) — evidência: `style-system/icones/COMO-ADICIONAR.md`

---

## UC-082-03 — Instalar a fonte para desenho

**Ator:** designer
**Permissão:** não se aplica
**Escopo:** computador do designer (Windows, CorelDRAW)
**Pré-condições:** acesso ao repositório.
**Pós-condições:** as famílias "G360 Icones" e "G360 Icones Preenchido" aparecem na lista de fontes.
**Tela/rota:** `apps/web/public/fonts/icones/*.ttf`

**Fluxo principal:**
1. Abra `g360-icones.ttf` e `g360-icones-preenchido.ttf`.
2. Instale as duas fontes.
3. Digite o glifo pelo código (U+E001 a U+E08D).

**Critérios de aceite:**
- [ ] Os dois TTF abrem no visualizador de fontes do Windows e mostram os glifos.

**Situação:** Implementado — evidência: `apps/web/public/fonts/icones/`
