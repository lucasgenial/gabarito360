# 021 — Clientes e espaços (T1/T2) — Casos de uso

**Natureza:** existente documentada a partir do código
**Perfis envolvidos:** todos os perfis com vínculo (isolamento); Administrador da Rede e Diretor de Núcleo (criação de núcleo/escola); operador da plataforma (comandos). Ver [docs/03](../../03-perfis-e-permissoes.md).
**Atualizado em:** 2026-11-09

Cada caso de uso é verificável: os critérios viram testes ou conferências registradas por texto. Este documento descreve o comportamento **atual**, marca com ⚠ as diferenças em relação ao planejado e aponta a funcionalidade ou subfase que as resolve.

---

## UC-021-01 — Restringir consultas ao espaço do vínculo em contexto

**Ator:** qualquer usuário autenticado com vínculo selecionado
**Permissão:** a do recurso consultado (ex.: `turma.ver`, `aluno.ver`, `relatorio.turma`)
**Escopo:** rede | núcleo | escola | espaço pessoal (conforme o vínculo)
**Pré-condições:** `espacos.ativo = true`; vínculo aceito e vigente selecionado (FN-015).
**Pós-condições:** nenhuma linha de outro espaço é lida ou alterada.
**Tela/rota:** todas as rotas `/app/*` da web; endpoints `/api/v1/*` protegidos por `auth:sanctum` + `contexto_vinculo`.

**Fluxo principal:**
1. A requisição chega com o contexto de vínculo resolvido (`ContextoVinculo`).
2. `Espacos::ids($u)` e `Escopo::*` limitam a consulta aos espaços alcançados pelo vínculo.
3. A resposta contém apenas registros desses espaços.

**Fluxos alternativos e de erro:**
- 2a. Registro de outro espaço solicitado por ID → `404` (não revela existência).
- 2b. Gravação que associaria registros de espaços diferentes → recusada pelas FKs compostas (`espaco_id` + id) e por validação (`422`).

**Regras de negócio:** docs/40 §3–§5 (propriedade por espaço); docs/48 (restrições T2-E).

**Critérios de aceite:**
- [ ] Dois espaços com dados fictícios: listar turmas, alunos, provas e notas no contexto A não retorna nada de B.
- [ ] `g360:auditar-espacos` não aponta linhas sem `espaco_id` nem referências cruzadas.

**Situação:** Implementado — evidência: `apps/api/app/Support/{Espacos,Escopo,Instituicoes}.php`; PR #76–#80; job MariaDB do CI.

---

## UC-021-02 — Criar o espaço ao cadastrar núcleo ou escola

**Ator:** Administrador da Rede; Diretor de Núcleo (escola, quando autorizado)
**Permissão:** `nucleo.gerir` / `escola.criar`
**Escopo:** rede | núcleo
**Pré-condições:** regime de vínculos ativo (`RegimeVinculos::exigir`); núcleo ou rede pai com cliente válido.
**Pós-condições:** registro institucional e espaço correspondente criados na mesma transação, com o cliente herdado do pai.
**Tela/rota:** cadastro de núcleo e de escola (UC-002 do [docs/07](../../07-casos-de-uso.md)); `POST /api/v1/nucleos`, `POST /api/v1/escolas`.

**Fluxo principal:**
1. O ator envia o cadastro do núcleo ou da escola (UC-002).
2. A API grava o registro e chama `Instituicoes::criarEspaco(tipo, id, nome, cliente, ator)`.
3. O espaço nasce `ativo`, com `criado_por` e o `cliente_id` do pai.

**Fluxos alternativos e de erro:**
- 2a. Tipo diferente de `nucleo`/`escola` → `422`.
- 2b. Falha ao criar o espaço desfaz o cadastro inteiro.

**Regras de negócio:** docs/45 (T2-D2).

**Critérios de aceite:**
- [ ] Criar escola gera exatamente um espaço `escola` com o mesmo cliente do núcleo.
- [ ] Erro na criação do espaço não deixa escola sem espaço.

**Situação:** Implementado — evidência: `Instituicoes::criarEspaco`; `EscolaController.php:189`; `NucleoController.php:51`.

⚠ Não existe caminho na aplicação para criar espaço de **rede** nem espaço **pessoal**; T4 não cria espaços (docs/71). Ver risco no [planejamento](planejamento.md) §8; escola sem núcleo depende de FN-029 (EI1-C/F).

---

## UC-021-03 — Exigir seleção quando o usuário alcança mais de uma rede

**Ator:** usuário com acesso a duas ou mais redes (ex.: conta administrativa com duas atribuições)
**Permissão:** a da operação (ex.: `rede.configurar`)
**Escopo:** rede
**Pré-condições:** `espacos.ativo = true`.
**Pós-condições:** a operação incide sobre a rede escolhida, nunca sobre "a primeira".
**Tela/rota:** configurações da rede, relatórios consolidados; `Instituicoes::redeSelecionada`.

**Fluxo principal:**
1. O ator informa `rede_id` ou opera com um vínculo que já define a rede.
2. A API valida que a rede está no alcance e executa.

**Fluxos alternativos e de erro:**
- 1a. Sem `rede_id` e com duas redes visíveis → `422 'Selecione a rede para esta operação.'`.
- 1b. `rede_id` fora do alcance → `404`.

**Critérios de aceite:**
- [ ] Usuário com duas redes recebe `422` sem seleção e opera corretamente com seleção.

**Situação:** Implementado — evidência: `apps/api/app/Support/Instituicoes.php` (`redeSelecionada`); docs/47.

---

## UC-021-04 — Inventariar e preencher espaços do legado

**Ator:** operador da plataforma (linha de comando no servidor)
**Permissão:** acesso operacional ao servidor; sem permissão de aplicação
**Escopo:** plataforma
**Pré-condições:** janela de manutenção; manifesto aprovado (`EstruturaEspacos::VERSAO`).
**Pós-condições:** clientes e espaços criados para as redes, núcleos e escolas existentes; `espaco_id` preenchido.
**Tela/rota:** `g360:inventariar-espacos`; `g360:preencher-espacos --aplicar --manifesto=`; `g360:conciliar-admin-espacos --usuario= --manifesto= --aplicar`.

**Fluxo principal:**
1. Operador executa o inventário (somente leitura) e confere o manifesto.
2. Executa o preenchimento com `--aplicar` e o manifesto correto.
3. Concilia a conta administrativa com as redes previstas.

**Fluxos alternativos e de erro:**
- 2a. Manifesto divergente → comando recusa aplicar.
- 3a. Preenchimento não concluído → `RuntimeException('Preenchimento não concluído.')`.

**Critérios de aceite:**
- [ ] Execução repetida é idempotente.
- [ ] Contagens antes/depois conferem com o manifesto.

**Situação:** Implementado (uso único no corte de 01/11) — evidência: docs/42, 43, 45, 54.

---

## UC-021-05 — Auditar, restringir e verificar o corte por espaço

**Ator:** operador da plataforma
**Permissão:** acesso operacional ao servidor
**Escopo:** plataforma
**Pré-condições:** escritores parados (`--escritores-parados`); backup final confirmado (FN-003).
**Pós-condições:** colunas `NOT NULL`, FKs compostas e unicidade por espaço aplicadas; verificação do corte aprovada.
**Tela/rota:** `g360:auditar-espacos`; `g360:restringir-espacos --aplicar --manifesto= --escritores-parados`; `g360:verificar-corte-espacos`.

**Fluxo principal:**
1. Auditoria sem divergências.
2. Restrição retomável aplicada.
3. Verificação do corte confirma o esquema antes de reabrir HTTP (B5 impede a reabertura se faltar o corte).

**Critérios de aceite:**
- [ ] DDL retomável após interrupção.
- [ ] B5 não reabre sem o esquema restritivo completo.

**Situação:** Implementado — evidência: PR #80, #82; docs/48, 50, 54.

⚠ A unicidade de CPF/matrícula é **por espaço**; o docs/115 §4 propõe unicidade global de CPF verificado numa tabela canônica de pessoas → FN-016 (contrato de conciliação).
