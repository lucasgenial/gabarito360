# 19 — Plano: Telas por Perfil de Acesso

> **Status:** proposta para aprovação (29/10/2026). Baseado em `03-perfis-e-permissoes.md`,
> `01-inventario-dos-mockups.md`, `13-roadmap.md`, `18-omr-nativo-e-cartoes.md` e no código
> de `main` (67a5648). Nenhuma implementação começa antes da aprovação das decisões da seção 6.

**Objetivo:** cada perfil entra no sistema e encontra, no seu painel e no seu menu, tudo o que
a matriz de permissões (doc 03) lhe dá — em especial o ciclo **prova → cartões (PDF) →
correção pela câmera → resultados**, que hoje funciona de ponta a ponta mas só fica realmente
acessível para quem chega pela tela da prova.

A ideia é a mesma do ECOAR (tudo por perfil de acesso), mas com o visual dos mockups do
Gabarito360 (`mockups/`, Design System gov.br).

---

## 1. Diagnóstico — o que existe e o que falta

O roadmap marca MP-006 a MP-022 como concluídos, mas, ao percorrer o código, a experiência
por perfil está incompleta. Lacunas encontradas:

### 1.1 Navegação

| Problema | Onde | Efeito |
|----------|------|--------|
| Menu definido **em cada view** (`@section('nav')`), sempre `Painel · Provas · Turmas · Escolas` | `layouts/app.blade.php` + todas as telas | Professor vê "Escolas"; aplicador e aluno herdam itens que não podem usar; nada de "Alunos", "Equipe", "Correções" |
| Badge de contexto (doc 03, "Contexto do Badge") não é padronizado | header | Usuário não sabe em que escola/turma está |
| Menu do usuário só mostra Configurações para `admin_rede` | `layouts/app.blade.php:49` | Diretor escolar não chega às configurações da escola |

### 1.2 Painel do Professor (`dashboard/professor.blade.php`)

- Botões **"Resultados"** e **"Acompanhar"** de cada prova apontam para `#` (linhas 162–163).
- Ação rápida **"Capturar Cartões"** aponta para `#` (linha 267).
- **"+ Nova Prova"** abre a lista de provas, não o formulário de criação.
- Não há acesso a **🖨 Cartões da turma** nem a **📷 Fotografar cartão** a partir do painel.

### 1.3 Painel do Coordenador (`dashboard/coordenador.blade.php`)

- Ação rápida **"Acompanhar Correções"** aponta para `#` (linha 234).
- "Criar prova" abre a lista, não o formulário.
- Não existe a tela **Acompanhar correção por turma** (mockup `acompanhar-correcao-turma.html`, MP-033).
- Sem acesso à gestão de alunos/turmas/vínculo de professores a partir do painel.

### 1.4 Permissões na API que bloqueiam o uso real

| Problema | Onde | Efeito |
|----------|------|--------|
| Escopo do **professor** em provas/cartões exige escopo `escola`; o vínculo com a turma grava escopo `turma` | `ProvaController::escopoEscolaIds`, `CartaoController::escopoEscolaIds` | Professor vinculado só à turma **não vê a prova nem consegue gerar cartões/corrigir** |
| **Aplicador** cai no `default => []` do escopo de cartões | `CartaoController::escopoEscolaIds` | O formulário de envio do painel do aplicador recebe 404 |
| `VinculoProfessorController` insere `updated_at` em `usuario_escopos`, que só tem `created_at` | `VinculoProfessorController.php:~66` | **Vincular professor à turma deve falhar com erro SQL** (a confirmar em produção) |
| Provas importadas ficam com `criado_por` = quem importou | `g360:importar-provas` | Professor só as vê se estiver vinculado à turma (depende do item acima) |

### 1.5 Telas dos mockups sem tela no sistema

| Mockup | Situação |
|--------|----------|
| `acompanhar-correcao-turma.html` | não existe (MP-033 pendente) |
| `aluno-editar.html` | não há tela própria (edição só via API) |
| `criar-prova.html` (stepper 3 etapas) | tela existe, mas sem o fluxo Dados → Gabarito → Cartão & Turmas |
| `resultado.html` — acertos por tema, "Revisar leitura" | parcial |
| Painel da Secretaria / do Aplicador | existem, sem mockup (seção 30 do doc 01) |

---

## 2. Princípios de implementação

1. **Uma tela, ações por perfil.** Não duplicar telas por perfil: a mesma `provas/show`,
   `correcao/show`, `turmas/show` mostram/ocultam ações conforme a permissão. O painel é a
   única tela específica de cada perfil.
2. **Menu único e central.** `config/menu.php` (ou `App\Support\Menu`) define os itens por
   perfil conforme "Navegação por Perfil" do doc 03; o layout renderiza. As views deixam de
   declarar `@section('nav')` e só informam o item ativo.
3. **Helper de permissão no WEB** (`pode('cartoes.enviar')`), espelhando a matriz do doc 03,
   para esconder botões. **A API continua sendo a autoridade** (doc 03, Política de Isolamento);
   o helper é só UX. Quando o MP-030 (motor de permissões) chegar, o helper passa a consultá-lo.
4. **O ciclo de correção é o centro.** Para PROF, COORD, DIR_ESC e ADMIN, o painel mostra as
   provas com três ações sempre visíveis: **🖨 Cartões**, **📷 Corrigir**, **📊 Resultados**.
5. **Celular primeiro nas telas de correção.** Professor e aplicador corrigem pelo celular:
   painel e `correcao/show` devem funcionar em 375 px sem rolagem horizontal.
6. Sem novas regras de negócio fora do doc 03/06; o que não estiver lá vai para a seção 6.

---

## 3. Matriz de telas × perfis (alvo)

Legenda: ✅ existe e está ok · 🟡 existe, precisa ajuste · ❌ falta · — sem acesso

| Tela | Mockup | PROF | COORD | DIR_ESC | APLIC | ALUNO | DIR_NUC / ADMIN |
|------|--------|------|-------|---------|-------|-------|-----------------|
| Painel do perfil | `dashboard-*.html` | 🟡 | 🟡 | 🟡 | 🟡 | 🟡 | 🟡 |
| Provas (lista) | `provas.html` | 🟡 suas | 🟡 escola | 🟡 escola | — | — | 🟡 |
| Criar prova (3 etapas) | `criar-prova.html` | 🟡 | 🟡 | — | — | — | 🟡 admin |
| Gabarito (ver/editar) | `gabarito.html` | ✅ | ✅ | ✅ ver | — | — | ✅ |
| Cartões-resposta PDF | (doc 18) | 🟡 acesso | 🟡 acesso | 🟡 acesso | — | — | ✅ |
| Corrigir pela câmera / acompanhar | `acompanhar-correcao.html` | 🟡 | 🟡 | 🟡 ver | ❌ só envio | — | ✅ |
| Correção por turma | `acompanhar-correcao-turma.html` | ❌ | ❌ | ❌ | — | — | ❌ |
| Resultado do aluno | `resultado.html` | 🟡 | 🟡 | 🟡 | — | ❌ próprio | 🟡 |
| Relatório prova / turma | `relatorio-*.html` | ✅ | ✅ | ✅ | — | — | ✅ |
| Turmas / detalhe | `turmas.html`, `turma-detalhe-2.html` | 🟡 suas | 🟡 | 🟡 | — | — | 🟡 |
| Alunos (lista/cadastro/edição/detalhe) | `aluno-*.html` | 🟡 ver | 🟡 | 🟡 | — | — | 🟡 |
| Equipe (membros, vínculo prof↔turma) | `perfis-equipe.html`, `membro-*.html` | — | 🟡 ver | 🟡 | — | — | 🟡 |
| Escola (detalhe/config) | `escola-detalhe.html` | — | 🟡 ver | 🟡 | — | — | ✅ |
| Meu perfil | `perfil.html` | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

## 4. Fases (novos micro-passos)

Ordem pensada para destravar primeiro o uso real do Simulado ENEM 2026 (professores e
coordenação corrigindo), depois completar os demais perfis.

### MP-036 — Fundação: menu por perfil, permissões no WEB e correções de escopo  *(prioridade 1)*

- `App\Support\Menu` + `config/menu.php` com os itens do doc 03; layout renderiza o menu e o
  badge de contexto; remover `@section('nav')` das views (passa a ser `@section('menu-ativo', 'provas')`).
- Helper `pode()` / diretiva `@pode` com a matriz do doc 03.
- **API:**
  - professor: escopo de provas/cartões = escolas das turmas vinculadas (`usuario_escopos`
    tipo `turma` → `turmas.escola_id`), mantendo "só suas provas ou das suas turmas";
  - aplicador: escopo de cartões = sua escola, **só `store`** e só provas com
    `data_aplicacao` = hoje (doc 03, †APLIC);
  - corrigir o insert de `updated_at` em `VinculoProfessorController`.
- Testes de feature na API: um teste por perfil × (listar provas, gerar PDF, enviar cartão,
  ver relatório) com os usuários do seeder.
- **Aceite:** com os 8 usuários do seeder, cada um vê só os itens de menu do seu perfil e
  nenhum link leva a `#` ou a 403/404.

### MP-037 — Ciclo de avaliação completo para Professor  *(prioridade 1)*

- Painel do professor (`dashboard-professor.html`): ligar "Resultados", "Acompanhar",
  "Capturar cartões"; "Nova prova" → `provas.create`; em cada prova, **🖨 Cartões por
  turma**, **📷 Corrigir** e **📊 Resultados**.
- "Capturar cartões" abre um seletor "qual prova?" (provas publicadas/em correção do professor)
  e leva direto ao modo câmera de `correcao/show`.
- `criar-prova.html`: stepper **Dados → Gabarito → Cartão & Turmas** reaproveitando
  `provas/create` e `gabarito-edit` (incluindo opção de língua), terminando em "Baixar cartões".
- `correcao/show` em 375 px: botão da câmera fixo no rodapé, lista de envios, ambíguos.
- **Aceite:** um professor vinculado à turma 3A cria (ou recebe) uma prova, baixa os cartões,
  corrige uma foto pelo celular e vê a nota no relatório — sem passar pela URL da prova.

### MP-038 — Coordenador e correção por turma (MP-033)  *(prioridade 2)*

- Painel do coordenador com dados reais (`dashboard-coordenador.html`): provas da escola com
  progresso de leitura, cartões pendentes/ambíguos, alunos abaixo da média, ações ligadas.
- Nova tela **Acompanhar correção por turma** (`acompanhar-correcao-turma.html`): por turma,
  todas as provas com lidos/esperados, ambíguos e alunos sem cartão; atalho para cartões em
  branco e para a câmera.
- Coordenador pode criar prova **para** um professor/turma da escola (campo "professor
  responsável" → `criado_por`; ver decisão 6.2).
- **Aceite:** o coordenador acompanha as 4 provas do simulado nas turmas 3A/3B numa tela só e
  sabe quais alunos ainda não tiveram cartão lido.

### MP-039 — Turmas, alunos e equipe pela escola  *(prioridade 2)*

- `turmas.html` / `turma-detalhe-2.html` filtrados por perfil; no detalhe da turma: alunos,
  professores vinculados (vincular/desvincular), provas da turma com as 3 ações do ciclo.
- `aluno-editar.html` (tela própria), `aluno-detalhe.html` com histórico de notas.
- Importar alunos por planilha pela tela (hoje só `g360:importar-alunos` no terminal).
- `perfis-equipe.html` / `membro-*.html` para DIR_ESC (convidar professor/coordenador/aplicador).
- **Aceite:** o diretor cadastra um professor, vincula à 3A e esse professor passa a ver as
  provas da 3A (fecha o ciclo com o MP-036).

### MP-040 — Diretor escolar e aplicador  *(prioridade 3)*

- Painel do diretor (`dashboard-diretor-escolar.html`): turmas, equipe, calendário de provas,
  relatório da escola (depende de MP-034).
- Configurações da escola no menu do usuário para DIR_ESC.
- Painel do aplicador: provas do dia → modo câmera (só envio; sem notas, sem ambíguos).

### MP-041 — Aluno  *(prioridade 3)*

- Painel do aluno (`dashboard-aluno.html`) e "Minhas provas" → `resultado.html` com a folha
  corrigida (somente o próprio resultado). Requer vínculo `usuarios`(aluno) ↔ `alunos`
  (ver decisão 6.4).

### Demais perfis

DIR_NUC, ADMIN_REDE e SECRETARIO já têm painel; entram só no MP-036 (menu) e recebem os
relatórios consolidados no MP-034 já previsto.

---

## 5. Como validar cada fase

- Seeder com um usuário de cada perfil na escola de exemplo, com professor vinculado a uma
  turma e uma prova publicada com gabarito.
- Testes de feature na API por perfil (permitido/negado) — rodam no CI.
- Roteiro manual por perfil (login → painel → ciclo) no navegador desktop e em 375 px.
- Em produção: repetir o ciclo com a prova de Linguagens da 3A e **uma foto real de cartão
  impresso** (pendência do doc 18).

---

## 6. Decisões pendentes (preciso da sua resposta antes de implementar)

1. **Vínculo professor ↔ disciplina.** O vínculo hoje é só professor ↔ turma. No simulado,
   cada área (LC, CH, CN, MT) tem professores diferentes nas mesmas turmas. Opções:
   (a) professor vê **todas** as provas das suas turmas; (b) vínculo passa a ter disciplina e o
   professor vê só as provas da sua disciplina. *Sugestão: (a) agora, (b) depois.*
2. **Quem é o "dono" de uma prova criada pela coordenação** (ou importada): manter
   `criado_por` = quem criou, ou adicionar "professor responsável"? *Sugestão: campo novo
   `professor_id` opcional, sem mudar `criado_por`.*
3. **Diretor escolar cria prova?** O doc 03 diz que não; a API hoje permite. *Sugestão:
   seguir o doc 03 (só ver/acompanhar).*
4. **Login do aluno.** Existe o perfil ALUNO, mas não há ligação entre o usuário e o registro
   em `alunos`. Liberar o painel do aluno agora ou deixar para depois do simulado?
5. **Ordem das fases.** Proposta: MP-036 + MP-037 juntos (destravam professores no simulado),
   depois MP-038/039, depois 040/041.
