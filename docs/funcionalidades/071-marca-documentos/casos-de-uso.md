# 071 — Marca oficial e cabeçalhos de documentos — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis autenticados, visitantes e destinatários de e-mails e documentos. Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Os casos descrevem o comportamento atual, publicado só na homologação (PR #191). A emissão dos documentos em si pertence a FN-047 e FN-049 (UC-006 e UC-010 do docs/07 (`docs/07-casos-de-uso.md`)).

---

## UC-071-01 — Ver a marca oficial nas telas, no acesso e nos e-mails

**Ator:** qualquer usuário, visitante ou destinatário de e-mail
**Permissão:** a da tela visitada
**Escopo:** toda a aplicação
**Pré-condições:** nenhuma.
**Pós-condições:** nenhuma.
**Tela/rota:** layout da aplicação, layout público, login, recuperação, convites, seleção de vínculo, carregamento; `components/marca.blade.php`; e-mail `resources/emails/base.html`.

**Fluxo principal:**
1. O usuário abre uma tela ou recebe um e-mail.
2. O sistema mostra símbolo e wordmark oficiais na variante adequada ao espaço e ao tema.

**Fluxos alternativos e de erro:**
- 2a. Menu recolhido ou cabeçalho compacto: só o símbolo.
- 2b. Movimento reduzido: sem animação.
- 2c. Cliente de e-mail sem SVG ou fontes: PNG horizontal.

**Regras de negócio:** docs/162 (cores #001534, #0588FC e branco; Figtree 800, "6" em Albert Sans 800).

**Critérios de aceite:**
- [x] Um único nome acessível "Gabarito360" por ocorrência.
- [x] Animação só quando pedida, sem laço.

**Situação:** Implementado (homologação) — evidência: PR #191; `MarcaWebTest`. ⚠ Produção pendente: subfase 1.6.

---

## UC-071-02 — Identificar o site por ícone e prévia de link

**Ator:** visitante ou usuário
**Permissão:** nenhuma
**Escopo:** público
**Pré-condições:** nenhuma.
**Pós-condições:** nenhuma.
**Tela/rota:** `layouts/_marca-head.blade.php`; favicons, `manifest.json`, `site.webmanifest` e `brand/gabarito360-og.png` em `apps/web/public`.

**Fluxo principal:**
1. O navegador ou aplicativo lê ícones, manifest e metadados.
2. Mostra o ícone oficial e, ao compartilhar, a imagem 1200×630 com texto genérico.

**Fluxos alternativos e de erro:**
- 2a. Rotas privadas: metadados genéricos, nunca nome, nota, identificador ou contexto autenticado.

**Regras de negócio:** docs/162 e docs/164 (prévia de links).

**Critérios de aceite:**
- [x] Metadados sociais sem dados de telas privadas.
- [ ] Conferência do cartão em mensageiros após publicação pública (a homologação tem BasicAuth).

**Situação:** Parcial — implementado na homologação; conferência pública externa depende da produção (subfase 1.6).

---

## UC-071-03 — Emitir documento com cabeçalho institucional

**Ator:** perfil com permissão de relatório ou impressão em vínculo escolar
**Permissão:** a do documento (por exemplo `relatorio.prova`, `relatorio.turma`, `cartao.imprimir`)
**Escopo:** escola do vínculo
**Pré-condições:** documento associado a uma escola.
**Pós-condições:** nenhuma alteração de dados; arquivos antigos não são regenerados.
**Tela/rota:** relatórios impressos (`relatorios/_cabecalho.blade.php`) e PDFs gerados pela API (`Support/Cabecalho.php`).

**Fluxo principal:**
1. O ator imprime ou gera o PDF.
2. Escola com rede: logo da rede à esquerda e da escola à direita.
3. Título centralizado; aluno e nota em caixas próprias no resultado.

**Fluxos alternativos e de erro:**
- 2a. Escola sem rede: só a logo da escola, à esquerda.
- 2b. Logo ausente: o lugar correspondente fica vazio, sem trocar o arranjo.
- 3a. Cartão-resposta: variante compacta que preserva marcadores, QR e grade óptica.

**Regras de negócio:** docs/161. Dados institucionais existentes determinam o cabeçalho.

**Critérios de aceite:**
- [x] Leitura dos cartões inalterada.
- [x] PDFs de resultado mantêm selo, QR e numeração.

**Situação:** Implementado (homologação) — evidência: PR #191.

---

## UC-071-04 — Emitir documento em uso autônomo

**Ator:** professor autônomo no espaço pessoal
**Permissão:** a do documento
**Escopo:** espaço pessoal
**Pré-condições:** contexto pessoal ativo.
**Pós-condições:** nenhuma.
**Tela/rota:** as mesmas do UC-071-03.

**Fluxo principal:**
1. O professor imprime ou gera o PDF.
2. O cabeçalho mostra a marca completa do Gabarito360 e a indicação "Uso autônomo".

**Fluxos alternativos e de erro:**
- 2a. O professor não aparece como emissor institucional e nenhuma escola é inferida de referências pessoais.

**Regras de negócio:** docs/161.

**Critérios de aceite:**
- [x] Nenhuma logo de escola ou rede em documento do espaço pessoal.

**Situação:** Implementado (homologação) — evidência: PR #191; `relatorios/_cabecalho.blade.php`, `Cabecalho::paraPdf`.
