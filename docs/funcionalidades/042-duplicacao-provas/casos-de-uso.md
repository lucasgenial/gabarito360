# 042 — Duplicação de provas (CP1, CP2) — Casos de uso

**Natureza:** existente documentada a partir do código (CP1); proposta (CP2, A7)
**Perfis envolvidos:** Professor, Professor autônomo (CP1); Coordenador e Professor destinatário (CP2) — ver docs/03 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

---

## UC-042-01 — Copiar a própria prova

**Ator:** Professor (vínculo de escola) ou Professor autônomo (espaço pessoal)
**Permissão:** `prova.duplicar_propria`, mais `prova.ver`, `prova.criar`, `prova.editar`, `gabarito.ver`, `gabarito.editar`
**Escopo:** escola | espaço pessoal
**Pré-condições:** espaços ativos e vínculo selecionado; o ator é o autor da prova; prova não institucional; formato "Gabarito e cartão-resposta".
**Pós-condições:** novo rascunho independente no mesmo espaço, com configuração e gabarito não publicado; procedência registrada; original intacta.
**Tela/rota:** lista e detalhe de provas (modal Copiar); web `POST /provas/{id}/copiar`; API `POST /v1/provas/{id}/copiar`

**Fluxo principal:**
1. O ator clica em "Copiar"; o modal mostra a origem, a explicação e o campo do novo nome.
2. Informa o título (até 255) e confirma; a página envia uma chave UUID da intenção.
3. A API trava vínculo e origem, revalida e cria o rascunho; o ator é levado ao rascunho.

**Fluxos alternativos e de erro:**
- 1a. Cancelar ou Escape → nada é criado.
- 3a. Prova de outro autor, institucional, de outro espaço ou só visível por turma → 404 "Prova não disponível para cópia."
- 3b. Sem vínculo selecionado ou espaços desligados → 403.
- 3c. Reenvio com a mesma chave e os mesmos dados → devolve a mesma cópia.
- 3d. Mesma chave com dados diferentes → 409 "Esta solicitação já foi utilizada com outros dados."
- 3e. Falha de conexão → volta à lista com os dados da tentativa e reabre o modal.

**Regras de negócio:** docs/118 — nunca copiar turma, aluno, cartão, QR, resposta, nota, data de aplicação ou fechamento; origem sem gabarito é válida.

**Critérios de aceite:**
- [ ] Duas intenções distintas criam duas cópias independentes.
- [ ] Cópia de cópia mantém a raiz na procedência.
- [ ] A cópia não concede novo acesso à origem.

**Situação:** Implementado — evidência: `ProvaController::copiar`, `apps/api/app/Services/DuplicarProva.php`, `provas/_botao-copiar.blade.php`; PR #151.

---

## UC-042-02 — Ver a procedência da cópia

**Ator:** quem vê a cópia
**Permissão:** `prova.ver`
**Escopo:** escola | espaço pessoal
**Pré-condições:** prova criada por cópia.
**Pós-condições:** nenhuma.
**Tela/rota:** detalhe da prova; API `GET /v1/provas/{id}`

**Fluxo principal:**
1. O detalhe indica que a prova é uma cópia e mostra a origem.
2. O link para a origem só existe enquanto ela for legível pelo ator.

**Critérios de aceite:**
- [ ] Campos de procedência são somente leitura na API.

**Situação:** Implementado — evidência: `ProvaController::procedencia`; docs/118.

---

## UC-042-03 — Aplicar a cópia (turmas, publicação e cartões)

**Ator:** autor da cópia
**Permissão:** `prova.editar`, `gabarito.editar`, `cartao.imprimir`
**Escopo:** escola | espaço pessoal
**Pré-condições:** cópia em rascunho.
**Pós-condições:** cópia publicada pelo gabarito e pronta para cartões.
**Tela/rota:** fluxos de [FN-040](../040-provas-gabaritos/casos-de-uso.md)

**Fluxo principal:**
1. O ator revisa a cópia e escolhe as turmas.
2. Publica o gabarito; depois gera os cartões.

**Fluxos alternativos e de erro:**
- 1a. Publicar ou gerar cartões sem turmas → 422 "Selecione as turmas desta cópia antes de publicar ou gerar cartões."
- 2a. Publicar pela edição de status → 422 "Use a publicação do gabarito para publicar esta cópia."; pela publicação simples → 422 "Revise e publique o gabarito para publicar esta cópia."
- 2b. Cartões sem gabarito publicado → 422.
- 2c. Excluir rascunho com procedência → recusado com explicação (409).

**Critérios de aceite:**
- [ ] Não há atalho para aplicar cópia não revisada.
- [ ] Provas legadas não mudam de comportamento.

**Situação:** Implementado — evidência: `DuplicarProva::conferirAplicacao`; `ProvaController::update`, `publicar`, `destroy`.

---

## UC-042-04 — Coordenação fornece cópia a outro professor (CP2)

**Ator:** Coordenador
**Permissão:** `prova.duplicar_para_professor` (proposta; não existe no código)
**Escopo:** escola
**Pré-condições:** FN-057 disponível; destinatário Professor com vínculo aceito e vigente e atribuição DC1 elegível, na mesma escola.
**Pós-condições:** o destinatário recebe um rascunho novo, sem turmas; a original é preservada; o destinatário é notificado.
**Tela/rota:** a desenhar

**Fluxo principal:**
1. O coordenador escolhe uma prova que pode consultar e o professor destinatário.
2. Escolhe a disciplina de destino quando houver ambiguidade; informa novo título e motivo.
3. Confere o resumo ("O original será preservado. O destinatário receberá uma nova prova em rascunho para revisar e vincular às próprias turmas.") e confirma.
4. O destinatário é avisado, revisa e escolhe as próprias turmas antes de publicar.

**Fluxos alternativos e de erro:**
- 3a. Reenvio da mesma confirmação → uma única cópia.
- 3b. Vínculo ou atribuição revogados durante a operação → nenhuma cópia fora do alcance.

**Regras de negócio:** docs/142 §2 — não conceder leitura da original ao destinatário; não habilitar para equipe interna nem por hierarquia; e-mail, se houver, não leva dados escolares.

**Critérios de aceite:**
- [ ] Original intacta; rascunho sem turma nem nota.
- [ ] Autor original, coordenador que entregou e responsável pela cópia registrados separadamente.
- [ ] Notificação entregue na mesma transação (outbox).

**Situação:** Não implementado — origem: docs/142 (subfase 2.1).

---

## UC-042-05 — Importar provas de um arquivo JSON pelo terminal

**Ator:** operador técnico em nome de um usuário autorizado
**Permissão:** contexto do usuário e do vínculo indicados
**Escopo:** escola
**Pré-condições:** arquivo JSON com título, série e provas (disciplina e gabarito).
**Pós-condições:** com `--aplicar`, provas criadas com gabarito publicado e ligadas às turmas indicadas.
**Tela/rota:** `php artisan g360:importar-provas {arquivo} --escola= --usuario= [--vinculo=] [--turmas=] [--ano=] [--espaco=] [--aplicar]`

**Critérios de aceite:**
- [ ] Arquivo inválido é recusado antes de gravar.

**Situação:** Parcial — evidência: `apps/api/app/Console/Commands/ImportarProvas.php`. ⚠ O A7 previa exportação em JSON versionado, importação com prévia e importação pela tela; nada disso existe (subfase 3.1, Incerto).

---

## UC-042-06 — Exportar e importar prova pela tela (A7)

**Ator:** Professor, Coordenador
**Permissão:** a definir
**Escopo:** escola | espaço pessoal
**Pré-condições:** decisão do responsável sobre manter o A7.
**Pós-condições:** arquivo JSON versionado sem dados de alunos; prova importada como rascunho após prévia.
**Tela/rota:** a definir

**Critérios de aceite:**
- [ ] Notas e cartões nunca são exportados nem copiados.
- [ ] Prova importada é corrigida normalmente.

**Situação:** Não implementado — origem: docs/28 A7 passos 3–5 (subfase 3.1).
