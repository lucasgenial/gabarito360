# 21 — Plano: Perfis, Permissões, Cadastro de Usuários, Menus e Celular

> **Status:** proposta para aprovação (30/10/2026). Nada deste documento foi implementado.
> Base: `03-perfis-e-permissoes.md`, `13-roadmap.md` (MP-030/031), `19-plano-telas-por-perfil.md`
> e o código de `main`. Organizado em **etapas grandes** — cada etapa vira um PR testado por
> perfil, no computador e no celular, antes de ir para produção.

---

## 0. Por que este plano

Nos testes reais apareceram quatro problemas que têm a mesma raiz: **as permissões estão
espalhadas** (no `perfil:` das rotas, em `Escopo`, em `@pode` nas telas) e **não existe um
lugar onde se veja e se decida o que cada perfil pode**.

| Problema | Exemplo encontrado |
|----------|--------------------|
| Falta documento único do que cada perfil faz | decisões soltas em conversas (diretor não cria prova, coordenador vê relatório da escola…) |
| Não dá para dar uma permissão a mais a uma pessoa | um professor que também corrige outra turma, um coordenador que pode cadastrar alunos |
| Cadastro de usuário inseguro | **a API deixa o diretor escolar criar um usuário "Administrador da Rede"** e ligá-lo a qualquer escola |
| Telas no celular | tabelas largas (alunos, turmas, provas, equipe, relatórios) estouram a tela; o menu vira uma faixa longa |

---

## 1. Perfis — o que cada um faz (resumo; a matriz completa é a etapa 1)

| Perfil | Enxerga | Faz | Não faz |
|--------|---------|-----|---------|
| **Secretário de Educação** | redes da secretaria | relatórios consolidados das redes | operar escola, prova ou correção |
| **Admin da Rede** | toda a rede | tudo na rede: núcleos, escolas, usuários, configurações, logo do ente, relatórios | — |
| **Diretor de Núcleo** | escolas do núcleo | **cadastra/edita/desativa escolas do núcleo**, logo do ente, cadastra diretores escolares, relatórios do núcleo, visitas | criar prova, corrigir |
| **Diretor Escolar** | sua escola | edita dados e logo da escola, equipe da escola (coordenador, professor, aplicador), turmas e alunos, **reabre correção**, relatórios da escola | cadastrar escola, criar prova, mudar núcleo |
| **Coordenador** | sua escola | provas e gabaritos, cartões, correção (inclusive fechada), **reabre correção**, turmas, alunos, vincular professor, relatórios da escola | cadastrar escola ou usuários (padrão) |
| **Professor** | suas turmas | provas das suas turmas, cartões, corrigir **com a correção aberta**, fechar correção, resultados e relatórios das suas turmas | alterar com correção fechada, ver outras turmas |
| **Aplicador** | provas do dia da sua escola | só fotografar/enviar cartões | ver notas, gabarito, relatórios |
| **Aluno** | seus dados | boletim e resultados próprios | qualquer dado de outro aluno |

Regras transversais que já valem e entram no documento oficial:
- **Escopo sempre na API** (escola, núcleo, turma); a tela só esconde botões.
- **Correção fechada**: só supervisão (coordenador, diretor escolar, admin) altera ou reabre.
- **Escola pública ou privada**: "ente" = governo do estado, prefeitura ou mantenedora (logo do ente).

---

## 2. Etapas

### Etapa 0 — Correção de segurança no cadastro de usuários *(urgente, pequena, antes das demais)*

- API de usuários: quem cadastra **só pode criar perfis abaixo do seu** e **só no seu escopo**
  (diretor escolar → coordenador/professor/aplicador/aluno da sua escola; diretor de núcleo →
  diretor escolar/coordenador/professor das escolas do núcleo; admin → todos da rede).
- Mesmo critério para editar, mudar perfil e desativar.
- **Aceite:** diretor escolar tentando criar "Admin da Rede" ou usuário de outra escola recebe 403.

### Etapa 1 — Documento oficial de perfis e permissões *(documentação)*

- Reescrever `03-perfis-e-permissoes.md` como **catálogo de permissões** (chaves estáveis, ex.:
  `escola.criar`, `prova.criar`, `cartao.enviar`, `correcao.reabrir`, `usuario.criar.professor`,
  `relatorio.escola`), agrupadas por módulo, com: descrição, **perfis que têm por padrão**,
  escopo em que vale e se pode ser **concedida como extra**.
- Para cada perfil: o que faz, o que não faz, restrições e **quem pode cadastrá-lo**.
- Incorporar as decisões já tomadas (diretor não cria prova; diretor de núcleo cria escola;
  coordenador vê relatório da escola; correção fechada; logos).
- **Aceite:** você aprova a matriz antes da etapa 2.

### Etapa 2 — Motor de permissões: padrão por perfil + extras por usuário *(back-end)*

- Tabelas: `permissoes` (catálogo da etapa 1), `perfil_permissoes` (padrão de cada perfil, semeado
  da etapa 1) e `usuario_permissoes` (**extras**: concedida ou **retirada**, em qual escopo,
  quem concedeu, quando e validade opcional).
- `Permissao::pode(usuario, chave, escopo)` = padrão do perfil + extras − retiradas, sempre dentro
  do escopo do usuário. Substitui gradualmente o `perfil:` das rotas e o `Acesso::pode` das telas
  (o WEB passa a receber do login a lista de permissões efetivas).
- **Regra de concessão:** só pode conceder uma permissão quem **também a tem**, e só dentro do
  **próprio escopo** (ninguém dá mais poder do que possui).
- Auditoria: toda concessão/retirada fica registrada (quem, para quem, o quê, quando).
- **Aceite:** com o padrão semeado, nada muda para ninguém (testes por perfil iguais aos de hoje);
  uma permissão extra dada a um professor passa a valer na API e na tela.

### Etapa 3 — Telas de usuários e permissões *(web)*

- **Cadastrar usuário** em passos: 1) dados pessoais → 2) perfil (só os que você pode criar) →
  3) escopo (escola; turmas, para professor) → 4) permissões: as **padrão do perfil vêm marcadas e
  travadas**, as **extras** aparecem como opções que você pode ligar → 5) revisão.
- **Editar usuário** (novo ou antigo): mesma tela, aba **Permissões** com "padrão do perfil",
  "extras concedidas" e "retiradas", mostrando quem concedeu e quando.
- **Equipe** (`perfis-equipe.html`): lista por perfil, filtros, ações em massa (desativar, reenviar
  acesso), e **"o que este perfil pode"** (leitura da matriz).
- **Aceite:** diretor escolar cadastra um professor, dá a ele a permissão extra "cadastrar alunos"
  e o professor passa a ver e usar a tela de alunos.

### Etapa 4 — Menus e navegação por permissão *(web)*

- O menu deixa de ser fixo por perfil e passa a ser **montado pelas permissões efetivas** (quem
  recebeu "relatórios da escola" como extra vê o item).
- **Computador:** menu superior com no máximo 6 itens + "Mais".
- **Celular:** **barra inferior fixa** com 4 itens principais do perfil (ex.: professor: Painel,
  Corrigir, Provas, Turmas) + **"Mais"** abrindo a lista completa; o topo fica só com logo,
  contexto (escola/turma) e avatar.
- **Aceite:** cada um dos 8 perfis com o menu certo nos dois tamanhos; nenhum item leva a 403.

### Etapa 5 — Tabelas e telas no celular *(web)*

- Padrão único para tabelas (componente/estilo comum):
  - **listas** (alunos, turmas, provas, equipe, escolas, resultado por aluno): no celular cada linha
    vira um **cartão** com os campos rotulados e as ações num botão "⋯";
  - **quadros** (alunos × provas, ranking, comparativos, marcações A–E): primeira coluna fixa e
    rolagem lateral **só dentro do quadro**, com indicação de que rola;
  - filtros recolhidos num botão "Filtrar"; formulários em uma coluna.
- Revisão de **todas** as telas com tabela (hoje: alunos, turmas, turma, provas, equipe, escolas,
  escola, painéis de admin/núcleo/diretor/aluno/aplicador/secretaria, assinatura, correção da turma
  e os 6 relatórios) em 375 px e 768 px, com captura antes/depois.
- **Aceite:** nenhuma tela com rolagem lateral da página em 375 px; ações alcançáveis com o polegar.

### Etapa 6 — Cadastro da escola padronizado com o ECOAR *(api + web)*

- Mesmo modelo de dados de escola do ECOAR-MQA (ver seção 3), importação da mesma base pública e
  busca pelo **código INEP** que preenche os dados.
- Cadastro só por **diretor de núcleo/admin** (já em produção); diretor escolar **edita** os dados
  da própria escola.
- Anexos da escola (documentos) pelo diretor de núcleo.
- **Aceite:** cadastrar uma escola pelo INEP traz os mesmos dados que o ECOAR mostra para ela.

### Etapa 7 — Testes por perfil *(fechamento)*

- Testes automáticos de API por perfil (permitido × negado) para as ações principais.
- Roteiro de usabilidade por perfil com os usuários de teste (`@gabarito360.dev`), no computador e no celular.

---

## 3. Cadastro de escola no ECOAR-MQA (referência para a etapa 6)

Levantado no código de `D:\projetos\ECOAR-MQA` (`mqa-api`, `ecoar-react`) e em
`docs/10-integracao-censo-escolar-inep.md` do ECOAR.

**Fonte dos dados:** Microdados do **Censo Escolar da Educação Básica (INEP), Tabela_Escola**
(CSV `;`, ISO-8859-1, ~214 mil escolas). A coluna `CO_ENTIDADE` é o **código INEP** e é a chave
que liga os dois sistemas. O CSV não está versionado (≈425 MB); a importação é feita por comando.

**Modelo de escola no ECOAR** (o que o Gabarito360 passa a ter, em comum):

| Grupo | Campos | Origem no Censo |
|-------|--------|-----------------|
| Identificação | `codigo_inep` (8 dígitos, único), `nome`, `mantenedora` | CO_ENTIDADE, NO_ENTIDADE |
| Rede | `esfera_administrativa` (1 Federal, 2 Estadual, 3 Municipal, 4 Privada) | TP_DEPENDENCIA |
| Regional | `orgao_regional` (na Bahia = nº do NTE → nosso **núcleo**) | CO_ORGAO_REGIONAL |
| Situação | `situacao_funcionamento` (ativa, paralisada, extinta), `localizacao` (urbana, rural) | TP_SITUACAO_FUNCIONAMENTO, TP_LOCALIZACAO |
| Endereço | logradouro, número, complemento, bairro, CEP, **UF e município por código IBGE**, latitude/longitude | DS_ENDERECO… CO_CEP, CO_UF, CO_MUNICIPIO, LATITUDE/LONGITUDE |
| Contato | telefone "(DD) número", e-mail | NU_DDD + NU_TELEFONE (e-mail é manual) |
| Oferta | turnos, etapas, modalidades (tabelas de catálogo) | manual |
| Vínculo | `tipo` (sede, anexo, unidade vinculada) e `escola_pai` — é o que o ECOAR chama de **anexo** | manual |
| Controle | `origem` (censo/manual), `ano_censo`, `in_edicao_manual` (edição manual não é sobrescrita na reimportação) | — |

**No ECOAR:** quem cadastra escola são os gestores (federal, estadual, NTE, municipal) e o admin;
o **diretor escolar só visualiza**. A tela tem UF → município com busca, esfera/situação/localização
em listas fixas e turnos em caixas de seleção. **Não existe busca pelo INEP** que preencha os dados —
o INEP é digitado.

**Proposta para o Gabarito360 (etapa 6):**
- Adotar os mesmos campos e códigos (INEP, IBGE, esfera 1–4, situação, localização, tipo sede/anexo,
  turnos/etapas/modalidades) para que a mesma escola tenha os mesmos dados nos dois sistemas.
- **Importar do mesmo CSV do Censo** só a(s) UF(s) usadas (ex.: BA), com o mesmo mapeamento de colunas
  e a mesma regra de não sobrescrever edição manual.
- **Ir além do ECOAR:** ao digitar o INEP no cadastro, o sistema **preenche** nome, esfera, endereço,
  município, telefone e o núcleo (pelo órgão regional), e o diretor de núcleo só confere e salva.
- **Anexos** = escolas do tipo "anexo" ligadas à escola sede (como no ECOAR), cadastradas pelo diretor de núcleo.
- A escola de hoje (Eraldo Tinoco, INEP "PENDENTE") é atualizada pelo INEP real assim que você informar.

---

## 4. Decisões que preciso de você

1. **Quem cadastra quem** — proposta: admin → todos; diretor de núcleo → diretor escolar e
   coordenador; diretor escolar → coordenador, professor, aplicador e aluno; coordenador →
   professor, aplicador e aluno *(hoje o coordenador não cadastra ninguém)*. Confirma?
2. **Permissões extras com validade** (ex.: aplicador só no dia da prova) — incluir já na etapa 2?
3. **Extras retiradas** (tirar de alguém uma permissão que o perfil tem por padrão) — permitir?
4. **Barra inferior no celular** — os 4 itens de cada perfil podem ser definidos por mim e
   ajustados depois por você?
5. **Base de escolas:** importar o CSV do Censo no Gabarito360 (base própria, só a UF usada) **ou**
   consultar a base de escolas do ECOAR por API? Proposta: base própria, mesmo formato — os dois
   sistemas ficam independentes e iguais pelo código INEP. Você tem o `Tabela_Escola_2025.csv`?
6. **Ordem:** 0 → 1 → 2 → 3 → 4 → 5 → 6 → 7. A etapa 5 (celular) pode vir antes da 2 se os erros de
   mobile estiverem atrapalhando o uso agora.
