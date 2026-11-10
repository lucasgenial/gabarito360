# 014 — Perfis institucionais — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** Secretário escolar, Vice-diretor escolar, Auxiliar de secretaria; gestores que os convidam (Admin Rede, Dir. Núcleo, Dir. Escolar, Vice-diretor com `usuario.gerir`); Suporte e Desenvolvedor (exceção) — docs/03 §8 (`docs/03-perfis-e-permissoes.md`)
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Para uma funcionalidade existente, descreve o comportamento **atual**, marca as diferenças em relação ao planejado com ⚠ e aponta a subfase que as resolve.

O cadastro e o convite desses perfis usam UC-012-03 e UC-015-03; aqui ficam só as regras próprias.

---

## UC-014-01 — Atribuir um perfil institucional a uma pessoa

**Ator:** gestor com `usuario.gerir` que gere o perfil
**Permissão:** `usuario.gerir`
**Escopo:** escola
**Pré-condições:** gestor em vínculo selecionado; espaço de escola ativo.
**Pós-condições:** vínculo `convidado` com o perfil institucional; convite enviado.
**Tela/rota:** web `GET|POST /membros/convidar-vinculo` ou `POST /membros`; API `POST /api/v1/usuarios/convites-vinculo` ou `POST /api/v1/usuarios`

**Fluxo principal:**
1. O gestor escolhe a escola e um dos perfis `secretario_escolar`, `vice_dir_escolar` ou `auxiliar_secretaria`.
2. A API confere que o gestor gere o perfil e que o espaço é de escola.
3. A API confere os grupos funcionais (UC-014-03) e cria o vínculo.

**Fluxos alternativos e de erro:**
- 1a. Gestor sem vínculo selecionado: "Este perfil exige vínculo de escola."
- 2a. Perfil fora da gestão ou espaço de outro tipo: 403/404.

**Regras de negócio:** quem gere quem em `Cadastro::PODE_GERIR`; dir. núcleo gere vice-diretor, auxiliar e secretário, não professor.

**Critérios de aceite:**
- [ ] Nenhum desses perfis é criado em espaço de rede, núcleo ou pessoal.

**Situação:** Implementado — evidência: `ConviteVinculo::destino`, `UsuarioController::store`, PR #104, #164.

---

## UC-014-02 — Trabalhar como secretário, vice-diretor ou auxiliar

**Ator:** Secretário escolar, Vice-diretor, Auxiliar de secretaria
**Permissão:** as da política do perfil (mais extras, no caso do vice-diretor)
**Escopo:** escola do vínculo
**Pré-condições:** vínculo aceito, vigente e selecionado.
**Pós-condições:** nenhuma.
**Tela/rota:** painel `dashboard/secretario-escolar` ou painel do diretor escolar (vice-diretor usa `dashboard/dir-escolar`)

**Fluxo principal:**
1. Secretário escolar: vê escola, gere turmas e alunos (inclusive desativar), sem notas, provas ou resultados.
2. Auxiliar de secretaria: vê escola e turmas, vê e edita alunos; sem desempenho e sem extras.
3. Vice-diretor: acompanha a escola (11 padrões: escola, turmas, alunos, equipe, provas, gabaritos, correção, resultados e relatórios); outras ações só com extras concedidas.

**Fluxos alternativos e de erro:**
- 1a. Sem vínculo selecionado: nenhuma permissão, mesmo que o campo global da conta tenha o perfil.
- ⚠ 1b. `aluno.importar` existe na política do secretário, mas não há tela de importação (FN-034).

**Critérios de aceite:**
- [ ] Secretário e auxiliar não alcançam notas, provas ou resultados.
- [ ] Vice-diretor não herda automaticamente os poderes do diretor.

**Situação:** Implementado — evidência: `CatalogoPermissoes::SECRETARIO_ESCOLAR`, `VICE_DIRETOR`, `VICE_EXTRAS`, `AUXILIAR_SECRETARIA`; `Permissao::efetivas`.

---

## UC-014-03 — Impedir acúmulo de funções administrativas e pedagógicas

**Ator:** sistema, ao convidar, criar ou alterar vínculos
**Permissão:** —
**Escopo:** escola
**Pré-condições:** pessoa com vínculo de um grupo funcional na mesma escola, em vigência que se sobrepõe.
**Pós-condições:** operação recusada, salvo exceção registrada.
**Tela/rota:** `GruposFuncionais::conferir`

**Fluxo principal:**
1. Ao criar um vínculo administrativo, o sistema procura vínculos pedagógicos convidados, ativos ou suspensos da mesma pessoa na mesma escola (e vice-versa).
2. Havendo sobreposição e não havendo exceção permitida, recusa.

**Fluxos alternativos e de erro:**
- 2a. Mensagem: "Esta instituição já possui um vínculo de outro grupo funcional para esta pessoa. Encerre o vínculo anterior ou solicite ao suporte uma exceção vinculada a chamado."

**Critérios de aceite:**
- [ ] Com exceção permitida para a pessoa e a escola, o acúmulo é aceito.

**Situação:** Implementado — evidência: `GruposFuncionais`.

---

## UC-014-04 — Registrar exceção de grupos funcionais

**Ator:** Suporte ou Desenvolvedor
**Permissão:** `usuario.excecao_funcional.gerir` (contexto interno)
**Escopo:** escola escolhida
**Pré-condições:** contexto interno de Suporte ou Desenvolvedor; pessoa ativa com vínculo na escola.
**Pós-condições:** exceção gravada com nova versão, chamado, motivo e evento.
**Tela/rota:** web `GET /configuracoes/usuarios`, `POST /configuracoes/usuarios/consultar`, `POST /configuracoes/usuarios`; API `GET /api/v1/configuracoes/funcoes/opcoes`, `POST /api/v1/configuracoes/funcoes/consultar`, `POST /api/v1/configuracoes/funcoes`

**Fluxo principal:**
1. O operador escolhe a escola e informa o e-mail da pessoa.
2. Vê os vínculos da pessoa na escola, a situação da exceção e os últimos 20 eventos.
3. Marca permitir ou não, informa chamado (3 a 80) e motivo (10 a 500) e salva.
4. A tela mostra "Configuração registrada. Os vínculos e suas permissões permanecem separados."

**Fluxos alternativos e de erro:**
- 2a. Pessoa sem vínculo na escola: "Não foi encontrado um usuário com vínculo nesta instituição. Confira os dados."
- 3a. Versão alterada por outra pessoa: 409 "Esta configuração mudou. Consulte novamente antes de salvar."
- 3b. Própria exceção: 403 "Outro responsável interno deve autorizar sua própria exceção."

**Critérios de aceite:**
- [ ] Toda exceção tem chamado, motivo, ator e perfil do ator.
- [ ] Ninguém autoriza a própria exceção.

**Situação:** Implementado — evidência: `ExcecaoFuncionalController` (API e web).

---

## UC-014-05 — Atuar como Secretário de Educação em contexto

**Ator:** Secretário de Educação
**Permissão:** a definir no VI1
**Escopo:** secretaria/rede
**Pré-condições:** contrato contextual próprio (FN-017).
**Pós-condições:** —
**Tela/rota:** painel `dashboard/secretaria` existe (rota legada `perfil:secretario_educacao`)

**Fluxo principal:** proposto. O perfil não consta em `PerfisEspaco::POR_TIPO`, então não pode ser selecionado como vínculo.

**Critérios de aceite:**
- [ ] Contrato aprovado em FN-017 antes de declarar o acesso disponível.

**Situação:** Não implementado no regime de vínculos — evidência: docs/133; subfase 3.2.

---

## UC-014-06 — Incluir perfis futuros

**Ator:** responsável
**Permissão:** —
**Escopo:** —
**Pré-condições:** pedido real e priorização.
**Pós-condições:** perfil registrado como candidata no plano mestre.
**Tela/rota:** —

**Fluxo principal:** proposto. Coordenador de área, orientador, gestor pedagógico, monitor, AEE, responsável, auditor institucional, gestor financeiro institucional, gestor de privacidade, autor/revisor de conteúdo, diretor de campus (docs/133).

**Critérios de aceite:**
- [ ] Cada perfil novo com matriz aprovada antes de código.

**Situação:** Não implementado — evidência: subfase 3.1.
