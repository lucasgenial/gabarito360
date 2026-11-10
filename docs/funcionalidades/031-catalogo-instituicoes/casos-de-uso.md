# 031 — Catálogo nacional de instituições (CI1) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** `professor_autonomo` (referências e solicitações); Suporte e Desenvolvedor (atendimento); Administrador da Rede, Diretor de Núcleo e Diretor Escolar (gestão com autoridade); operador da plataforma (importação). Ver docs/03 (`docs/03-perfis-e-permissoes.md`).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável. Descreve o comportamento **atual** (publicado só na homologação) e marca com ⚠ as diferenças em relação ao planejado. Todos exigem `CATALOGO_INTERFACE_ATIVA = true`.

---

## UC-031-01 — Buscar instituição no catálogo

**Ator:** professor autônomo
**Permissão:** `catalogo.consultar`
**Escopo:** espaço pessoal (leitura de dado público)
**Pré-condições:** contexto pessoal vigente do proprietário; edição publicada.
**Pós-condições:** nenhuma.
**Tela/rota:** `/app/minhas-instituicoes/buscar` (+ `/municipios`); `GET /api/v1/catalogo/{ufs,municipios,instituicoes}`.

**Fluxo principal:**
1. O professor escolhe tipo, UF e município (lista pesquisável e paginada por UF) e/ou digita nome, sigla ou código oficial com a fonte.
2. A API devolve itens paginados com nome, sigla, tipo, dependência, situação na fonte, município, fonte e ano, mais o aviso de cobertura.

**Fluxos alternativos e de erro:**
- 1a. Sem UF, município ou código e com menos de 3 caracteres → `422 'Informe localização, código ou pelo menos três caracteres.'`
- 1b. Código incompatível com a fonte → `422 'Código incompatível com a fonte.'`
- 1c. Município fora da UF → `422 'Município não pertence à UF ou à geografia vigente.'`
- 1d. Envio de `espaco_id`, `usuario_id`, `escola_id` ou `cliente_id` → `422` (proibidos).
- 2a. Sem resultado → lista vazia, com caminho para solicitar cadastro (UC-031-04).
- 2b. Catálogo indisponível → `503`; a tela mostra indisponibilidade, nunca falsa ausência.
- 1e. Trocar a UF limpa município e resultados; respostas atrasadas são descartadas.

**Critérios de aceite:**
- [ ] Outro perfil (institucional, interno) recebe `403` nestas rotas.
- [ ] A resposta não traz contatos privados, contratação nem IDs operacionais.

**Situação:** Implementado (homologação) — evidência: `CatalogoPessoalController`, `CatalogoSelecao`, `ConsultarCatalogoInstituicoes`; PR #157.

⚠ Busca nacional por texto leva ~1,8 s na homologação (docs/200); otimização adiada → FN-032.

---

## UC-031-02 — Conferir e adicionar instituição às minhas referências

**Ator:** professor autônomo
**Permissão:** `catalogo.referencia_pessoal.gerir`
**Escopo:** espaço pessoal
**Pré-condições:** unidade elegível (básica em atividade; IES com situação não informada admitida).
**Pós-condições:** referência pessoal ativa, com a edição escolhida e evento imutável. Não cria escola, vínculo, contrato nem acesso.
**Tela/rota:** `/app/minhas-instituicoes/conferir/{unidade}` → `POST /app/minhas-instituicoes`; `POST /api/v1/catalogo/referencias-pessoais` (`unidade_id`, `edicao_id`, `confirmacao`).

**Fluxo principal:**
1. O professor abre a conferência da unidade.
2. Marca a confirmação (desmarcada por padrão) e adiciona.
3. Mensagem: "Instituição adicionada às suas referências pessoais."

**Fluxos alternativos e de erro:**
- 2a. Edição mudou entre buscar e confirmar → `409`; conferir de novo.
- 2b. Unidade paralisada/extinta → não admite nova seleção.
- 2c. Repetir a mesma seleção → devolve a mesma referência, sem evento duplicado.
- 2d. Falha ao gravar o evento → nada é gravado.

**Critérios de aceite:**
- [ ] Duas seleções concorrentes geram uma referência e um evento.
- [ ] Nenhuma tabela acadêmica é alterada.

**Situação:** Implementado (homologação) — evidência: `ReferenciasInstituicoesPessoais`; docs/124.

⚠ A referência ainda não é usada em turmas/disciplinas → FN-027 (PA1-A).

---

## UC-031-03 — Arquivar referência pessoal

**Ator:** professor autônomo
**Permissão:** `catalogo.referencia_pessoal.gerir`
**Tela/rota:** `POST /app/minhas-instituicoes/{id}/arquivar`; `POST /api/v1/catalogo/referencias-pessoais/{id}/arquivar` (`versao`).

**Fluxo principal:** o professor arquiva; a referência sai da lista ativa e o histórico fica ("Referência arquivada; histórico preservado.").

**Fluxos alternativos e de erro:** versão desatualizada → erro com orientação de atualizar a lista; reativar exige nova confirmação de edição corrente.

**Situação:** Implementado (homologação) — evidência: PR #157.

---

## UC-031-04 — Solicitar cadastro de instituição ausente

**Ator:** professor autônomo
**Permissão:** `catalogo.solicitar`
**Escopo:** espaço pessoal (pedido privado; a gestão escolar não o vê)
**Pós-condições:** protocolo `enviada`, com vínculo de origem guardado como evidência.
**Tela/rota:** `/app/instituicoes/solicitacoes` (lista, `nova`, detalhe, `acoes`); `/api/v1/catalogo/minhas-solicitacoes`.

**Fluxo principal:**
1. Em "Minhas solicitações", o professor escolhe "Solicitar cadastro".
2. Informa nome, sigla, tipo, UF/município, código e fonte se souber, site público e observação. Sem anexos, CPF ou dados de alunos.
3. Acompanha o protocolo; quando pedido, complementa (`complementar`) ou cancela (`cancelar`, com confirmação).

**Fluxos alternativos e de erro:**
- 3a. Complementar sem pendência → `409 'Não há complementação pendente.'`
- 3b. Protocolo já decidido → `409 'Solicitação já encerrada.'`
- 3c. Versão mudou → `409 'A solicitação mudou. Atualize antes de continuar.'`
- 3d. Campos fora da lista → `422 'Campos não permitidos.'`

**Critérios de aceite:**
- [ ] Outro proprietário não lê o protocolo, mesmo conhecendo o UUID.
- [ ] A aprovação não adiciona a referência: o professor confere e seleciona depois (UC-031-02).

**Situação:** Implementado (homologação) — evidência: `SolicitacoesCatalogo`; PR #158. ⚠ Aviso só dentro do sistema, sem e-mail (FN-057/FN-060); retenção dos protocolos indefinida (FN-062).

---

## UC-031-05 — Analisar solicitações na fila interna

**Ator:** Suporte ou Desenvolvedor
**Permissão:** `catalogo.solicitacao.analisar` (contexto interno)
**Escopo:** plataforma
**Tela/rota:** `/app/catalogo/atendimento` (lista, detalhe, `buscar`, `acoes`); `/api/v1/catalogo/atendimento`.

**Fluxo principal:**
1. O atendente abre a fila e inicia a análise (`analisar`: enviada → em_analise).
2. Consulta o catálogo para verificar se a instituição já existe.
3. Decide: `pedir_complemento`, `indeferir`, `aprovar_existente` (aponta unidade e edição) ou `aprovar_nova` (cria na fonte manual, UC-031-06).

**Fluxos alternativos e de erro:**
- 3a. Decidir sem iniciar a análise → `409 'Inicie a análise antes de decidir.'`
- 3b. Complemento ou indeferimento sem mensagem de 5+ caracteres → `422 'Explique o motivo ao solicitante.'`
- 3c. Aprovar sem motivo e conferência → `422 'Registre o motivo e confirme a conferência.'`
- 3d. Catálogo mudou → `409 'O catálogo mudou. Confira novamente.'`
- 3e. Financeiro ou perfil escolar → `403`.

**Critérios de aceite:**
- [ ] Toda transição gera evento imutável; decisão repetida não duplica efeito.

**Situação:** Implementado (homologação) — evidência: `PoliticaCatalogo::interno`; PR #158.

---

## UC-031-06 — Cadastrar ou corrigir referência da plataforma

**Ator:** Suporte ou Desenvolvedor
**Permissão:** `catalogo.manter_global`
**Escopo:** plataforma
**Pós-condições:** nova edição da fonte `manual` ("Cadastro da plataforma"), com as anteriores preservadas.
**Tela/rota:** `/app/catalogo/atendimento/novo` e `/instituicoes/{unidade}`; `POST /api/v1/catalogo/atendimento/instituicoes[/{unidade}]` (`cadastro`, `motivo`, `conferencia`, `situacao`, `edicao_id`).

**Fluxo principal:** conferir que a instituição não existe → preencher o cadastro, o motivo e a conferência → gravar.

**Fluxos alternativos e de erro:** sem código oficial → identificador da plataforma (nunca INEP fictício); outra edição entrou → `409`; campos extras → `422 'Campos não permitidos.'`.

**Critérios de aceite:**
- [ ] A origem manual é sempre exibida; edição oficial nova não apaga o ajuste humano.
- [ ] Referências pessoais existentes não são reatribuídas.

**Situação:** Implementado (homologação) — evidência: `ManutencaoCatalogo`; docs/125.

---

## UC-031-07 — Registrar autoridade institucional sobre uma unidade

**Ator:** Suporte ou Desenvolvedor
**Permissão:** `catalogo.manter_global`
**Tela/rota:** `/app/catalogo/atendimento/autoridades/{unidade}`; `POST /api/v1/catalogo/atendimento/autoridades`.

**Fluxo principal:** o atendente associa uma escola operacional a uma unidade do catálogo após verificação interna; a partir daí a gestão dessa escola pode manter a unidade (UC-031-08).

**Critérios de aceite:**
- [ ] Autoridade não é deduzida de município, nome ou código informado.

**Situação:** Implementado (homologação) — evidência: `ManutencaoCatalogo::autoridade`; tabela `catalogo_gestao_autoridades`.

---

## UC-031-08 — Manter a unidade da própria instituição

**Ator:** Administrador da Rede, Diretor de Núcleo ou Diretor Escolar
**Permissão:** `catalogo.manter_escopo` + autoridade ativa para uma escola do alcance
**Escopo:** rede | núcleo | escola
**Tela/rota:** `/app/catalogo/gestao` e `/instituicoes/{unidade}`; `/api/v1/catalogo/gestao`.

**Fluxo principal:** o gestor vê somente as unidades autorizadas, revisa o cadastro, informa o motivo e grava; gera edição manual.

**Fluxos alternativos e de erro:** unidade sem autoridade → `404 'Instituição fora do alcance autorizado.'`; identidade, código e natureza não podem ser trocados.

**Critérios de aceite:**
- [ ] A gestão não vê protocolos nem solicitantes pessoais.

**Situação:** Implementado (homologação) — evidência: `PoliticaCatalogo::gestor`. ⚠ `secretario_educacao` não recebe a permissão até FN-017; ⚠ estado da autoridade no celular com pendência de UI (subfase 1.10).

---

## UC-031-09 — Importar e publicar uma edição do catálogo

**Ator:** operador da plataforma (linha de comando)
**Permissão:** acesso operacional ao servidor
**Escopo:** plataforma
**Tela/rota:** `g360:catalogo-validar`, `g360:catalogo-preparar` (com `--dry-run`, `--sha256`, lotes), `g360:catalogo-promover`, `g360:catalogo-relacionar`, `g360:catalogo-relacoes-status`, `g360:catalogo-conciliar`.

**Fluxo principal:** validar o arquivo da fonte → preparar o staging (retomável) → promover a edição → carregar relações → conferir lacunas e conciliação (somente relatório).

**Critérios de aceite:**
- [ ] Carga interrompida retoma sem duplicar.
- [ ] Publicar edição nova não altera referências pessoais nem tabelas acadêmicas.

**Situação:** Implementado (homologação) — evidência: PR #152–#155, #160; docs/119–122, 130.

---

## UC-031-10 — Conciliar escolas operacionais sem INEP

**Ator:** Suporte, com revisão humana
**Fluxo (pendente):** fazer o de-para manual das 6 escolas operacionais ainda não conciliadas e resolver as 8 lacunas e unidades adicionais pelos fluxos de UC-031-06/07.

**Situação:** Não implementado (trabalho de dados pendente) — subfases 1.8 e 1.9; docs/119, 121, 122.
