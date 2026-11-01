# 03 — Perfis e Permissões (catálogo oficial)

> **Versão 2 — 30/10/2026** (etapa 1 do `docs/21-plano-perfis-permissoes-e-telas.md`).
> Substitui as matrizes da versão de 18/06/2026. Este documento é a **fonte única** do que cada
> perfil pode fazer: o motor de permissões (etapa 2) é semeado a partir dele, as rotas da API e os
> botões das telas devem obedecê-lo. Mudanças aqui exigem aprovação do responsável pelo produto.

---

## 1. Conceitos

| Conceito | Significado |
|----------|-------------|
| **Perfil** | Papel do usuário (8 perfis, seção 2). Define o **escopo** e as **permissões padrão**. |
| **Escopo** | Até onde o usuário enxerga: secretaria, rede, núcleo, escola ou turmas. **Sempre verificado na API**. |
| **Permissão** | Uma ação com chave estável (ex.: `prova.criar`). Catálogo na seção 4. |
| **Padrão do perfil** (✓) | Permissões que todo usuário do perfil tem ao ser cadastrado. |
| **Extra** (○) | Permissão que **não** é padrão do perfil mas **pode ser concedida** a uma pessoa por quem a cadastra/gere. |
| **Nunca** (—) | Não pode ser dada a esse perfil, nem como extra. |
| **Retirada** | Uma permissão padrão pode ser **retirada** de uma pessoa específica (ex.: professor que não fecha correção). |

Regras de concessão (valem para extras e retiradas):
1. Só concede uma permissão quem **também a tem** (padrão ou extra).
2. Só concede **dentro do próprio escopo** (a escola do diretor, o núcleo do diretor de núcleo…).
3. Só concede a quem ele **pode gerir** (seção 3 — perfis abaixo do seu).
4. Toda concessão/retirada é **registrada**: quem deu, para quem, o quê, em qual escopo, quando e
   até quando (validade opcional, ex.: aplicador só no dia da prova).
5. O escopo nunca é ampliado por permissão extra: um professor com `relatorio.escola` vê o relatório
   **da escola dele**, não de outras.

---

## 2. Perfis

### Extensão A1 — contexto interno (01/11/2026)

Os perfis internos usam atribuição própria, sem integrar a matriz institucional de oito colunas nem ampliar o escopo de um vínculo escolar. Habilitação desligada por padrão; base A1-A e seleção por token/aba A1-B1 incorporadas nos PRs #101/#102. Gestão A1-B2 incorporada no PR #103 (`98b28e1`). Não há promoção automática de contas.

| Contexto interno | Cadastro comercial | Gestão de acessos internos | Dados pedagógicos |
|---|---|---|---|
| Desenvolvedor | Consultar e gerir clientes | Gestão auditada de Suporte/Financeiro, incorporada no PR #103 | Negado neste contexto; atendimento auditado é AU3 |
| Suporte | Consultar e gerir clientes | Negado | Negado neste contexto; atendimento auditado é AU3 |
| Financeiro | Consultar e gerir clientes | Negado | Negado |

Cada uso revalida titularidade, conta ativa, situação, vigência e versão da atribuição. Selecionar contexto interno remove o vínculo pedagógico do objeto e vice-versa; não há união de poderes. A1-B2 prepara comando controlado para o primeiro Desenvolvedor e gestão auditada de Suporte/Financeiro de outras contas. Autogestão e gestão de pares são recusadas. Concessão de outros Desenvolvedores não é oferecida. Secretário Escolar incorporado no PR #104: vínculo exclusivo de escola, gestão de alunos/turmas sem dados de desempenho, sem extras e sem gestão de equipe. Administrador de rede, diretor de núcleo e diretor escolar podem gerir esse vínculo no próprio escopo; coordenador não. Detalhes: [docs/69](69-a1-b2-gestao-interna.md).

### Perfis institucionais

| Perfil | Escopo | Faz (resumo) | Não faz | Restrições |
|--------|--------|--------------|---------|------------|
| **Secretário de Educação** (`secretario_educacao`) | redes da sua secretaria | relatórios consolidados das redes | operar escola, prova, correção, usuários | somente leitura |
| **Administrador da Rede** (`admin_rede`) | toda a rede | tudo dentro da rede | — | não vê outras redes; titularidade da assinatura é separada (seção 6) |
| **Diretor de Núcleo** (`dir_nucleo`) | escolas do seu núcleo | cadastra/edita/desativa **escolas e anexos** do núcleo, logo do ente, cadastra diretores e coordenadores, relatórios do núcleo e das escolas | criar prova, corrigir, cadastrar professor/aluno | não muda escolas para outro núcleo |
| **Diretor Escolar** (`dir_escolar`) | sua escola | edita dados e logo da escola, gere a equipe (coordenador, professor, aplicador, aluno), turmas e alunos, acompanha e **reabre correções**, relatórios da escola | cadastrar escola, criar/editar prova ou gabarito | não muda núcleo nem ativação da escola |
| **Coordenador** (`coordenador`) | sua escola | provas e gabaritos, cartões, correção (inclusive **fechada**), reabre correção, turmas, alunos, vínculo professor↔turma, equipe (professor, aplicador, aluno), logo da escola, relatórios da escola | cadastrar escola, gerir diretor | — |
| **Professor** (`professor`) | **suas turmas** (vínculo professor↔turma) | provas das suas turmas, gabarito, cartões, corrigir com a correção **aberta**, fechar correção, resultados e relatórios das suas turmas | alterar com correção fechada, ver outras turmas, gerir usuários | em conta individual (SaaS) recebe permissões de diretor na própria escola (seção 6) |
| **Aplicador** (`aplicador`) | provas **do dia** da sua escola | fotografar/enviar cartões | ver nota, gabarito, relatório, acompanhar correção | só provas com data de aplicação = hoje; nunca com correção fechada |
| **Aluno** (`aluno`) | seus próprios dados | ver seus resultados e boletim | ver qualquer dado de outro aluno | depende do vínculo usuário↔aluno (pendente) |

---

## 3. Quem cadastra e gere quem

Implementado em `apps/api/app/Support/Cadastro.php` (etapa 0). Vale para cadastrar, editar,
mudar perfil, trocar senha, desativar **e conceder/retirar permissões**.

| Quem | Pode cadastrar/gerir | Onde |
|------|----------------------|------|
| Administrador da Rede | admin da rede, diretor de núcleo, diretor escolar, coordenador, professor, aplicador, aluno | toda a rede |
| Diretor de Núcleo | diretor escolar, coordenador | escolas do núcleo |
| Diretor Escolar | coordenador, professor, aplicador, aluno | sua escola |
| Coordenador | professor, aplicador, aluno | sua escola |
| Professor, Aplicador, Aluno | ninguém | — |

O **Secretário de Educação** é cadastrado pela equipe do Gabarito360 (contratação institucional).

---

## 4. Catálogo de permissões

Colunas: **SEC** secretário · **ADM** admin da rede · **NUC** diretor de núcleo · **DIR** diretor
escolar · **COO** coordenador · **PRO** professor · **APL** aplicador · **ALU** aluno.
✓ padrão · ○ pode ser concedida como extra · — nunca.

### 4.1 Rede, núcleos e escolas

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|-------|------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `rede.configurar` | parâmetros da rede (metas, etc.) | — | ✓ | — | — | — | — | — | — |
| `rede.logo` | enviar logo do ente (governo/prefeitura/mantenedora) | — | ✓ | ✓ | ○ | — | — | — | — |
| `nucleo.ver` | ver núcleos | ✓ | ✓ | ✓ | — | — | — | — | — |
| `nucleo.gerir` | criar/editar/excluir núcleo | — | ✓ | ○¹ | — | — | — | — | — |
| `escola.ver` | ver dados da escola | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| `escola.criar` | cadastrar escola e **anexos** | — | ✓ | ✓ | — | — | — | — | — |
| `escola.editar` | editar dados da escola | — | ✓ | ✓ | ✓ | ○ | — | — | — |
| `escola.desativar` | ativar/desativar escola | — | ✓ | ✓ | — | — | — | — | — |
| `escola.logo` | enviar logo da escola | — | ✓ | ✓ | ✓ | ✓ | — | — | — |

¹ diretor de núcleo só edita o próprio núcleo.

### 4.2 Equipe e usuários

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|-------|------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `usuario.ver` | ver a equipe do escopo | — | ✓ | ✓ | ✓ | ✓ | ○ | — | — |
| `usuario.gerir` | cadastrar/editar/desativar (conforme seção 3) | — | ✓ | ✓ | ✓ | ✓ | — | — | — |
| `usuario.permissoes` | conceder/retirar permissões (regras da seção 1) | — | ✓ | ✓ | ✓ | ○ | — | — | — |

### 4.3 Turmas e alunos

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|-------|------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `turma.ver` | ver turmas (professor: as suas) | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `turma.gerir` | criar/editar turma | — | ✓ | — | ✓ | ✓ | ○ | — | — |
| `turma.desativar` | ativar/desativar turma | — | ✓ | — | ✓ | ○ | — | — | — |
| `turma.vincular_professor` | vincular/desvincular professor | — | ✓ | — | ✓ | ✓ | — | — | — |
| `aluno.ver` | ver alunos (professor: das suas turmas) | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ○² |
| `aluno.gerir` | cadastrar/editar aluno | — | ✓ | — | ✓ | ✓ | ○ | — | — |
| `aluno.importar` | importar alunos por planilha | — | ✓ | — | ✓ | ✓ | ○ | — | — |
| `aluno.desativar` | ativar/desativar aluno | — | ✓ | — | ✓ | ✓ | — | — | — |

² aluno: somente o próprio cadastro.

### 4.4 Provas e gabaritos

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|-------|------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `prova.ver` | ver provas (professor: das suas turmas; aplicador: só identificação das do dia) | — | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| `prova.criar` | criar prova | — | ✓ | — | ○ | ✓ | ✓ | — | — |
| `prova.editar` | editar, publicar e excluir rascunho | — | ✓ | — | ○ | ✓ | ✓ | — | — |
| `gabarito.ver` | ver gabarito | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `gabarito.editar` | editar/publicar gabarito | — | ✓ | — | ○ | ✓ | ✓ | — | — |

### 4.5 Cartões e correção

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|-------|------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `cartao.imprimir` | gerar PDF dos cartões-resposta | — | ✓ | — | ✓ | ✓ | ✓ | ○ | — |
| `cartao.enviar` | fotografar/enviar cartões | — | ✓ | — | ✓ | ✓ | ✓ | ✓ | — |
| `correcao.acompanhar` | acompanhar correção (prova e turma) | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `correcao.alterar` | resolver ambíguos, vincular aluno, revisar leitura, **excluir correção** (correção aberta) | — | ✓ | — | ✓ | ✓ | ✓ | — | — |
| `correcao.fechar` | fechar a correção | — | ✓ | — | ✓ | ✓ | ✓ | — | — |
| `correcao.reabrir` | reabrir a correção **e** alterar/excluir/incluir com ela fechada | — | ✓ | — | ✓ | ✓ | ○ | — | — |

### 4.6 Resultados e relatórios

| Chave | Ação | SEC | ADM | NUC | DIR | COO | PRO | APL | ALU |
|-------|------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| `resultado.ver` | acertos e erros do aluno (aluno: só o próprio) | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |
| `resultado.compartilhar` | PDF/WhatsApp do resultado e boletim | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | ✓ |
| `relatorio.prova` | relatório da prova e da turma na prova (análise por questão) | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `relatorio.turma` | relatório consolidado da turma e boletim | — | ✓ | ✓ | ✓ | ✓ | ✓ | — | — |
| `relatorio.escola` | relatório da escola | — | ✓ | ✓ | ✓ | ✓ | ○ | — | — |
| `relatorio.nucleo` | relatório do núcleo | — | ✓ | ✓ | — | — | — | — | — |
| `relatorio.rede` | relatório da rede | ✓³ | ✓ | — | — | — | — | — | — |
| `relatorio.secretaria` | consolidado da secretaria | ✓ | — | — | — | — | — | — | — |

³ secretário: redes da sua secretaria.

---

## 5. Regras transversais (já em produção)

- **Escopo na API** em toda consulta e alteração (`App\Support\Escopo`); a tela só esconde botões.
- **Correção fechada** (`provas.correcao_fechada_em`): sem `correcao.reabrir`, ninguém envia, exclui,
  vincula ou revisa; quem tem `correcao.reabrir` continua alterando e pode reabrir.
- **Cartão de outra prova**: o QR manda o cartão para a prova certa da mesma escola; se não puder,
  o envio é recusado com o motivo.
- **Aplicador** nunca recebe nota, gabarito ou relatório na resposta da API.
- **Fotos de cartões** e PDFs só são servidos a quem acessa a prova (nunca por URL pública).

---

## 6. Assinatura (SaaS) e conta individual

**Atualização T3, autorizada em 01/11/2026:** para o novo regime de vínculos, a conta individual será `professor_autonomo` em espaço pessoal próprio, sem escola fictícia nem extras automáticas de diretor. O contrato e as etapas estão em [docs/63](63-t3-perfis-por-espaco.md). T3-A prepara o catálogo, mas mantém esse contexto bloqueado até a integração. As regras abaixo descrevem o modelo anterior e não autorizam provisionar novas contas dessa forma.

- **Assinatura e cobrança** não dependem do perfil: só o **titular** da conta (quem contratou) vê e
  gere plano, pagamentos e cancelamento.
- **Professor em conta individual**: continua com perfil `professor`, mas na escola criada para ele
  recebe como **extras automáticas** as permissões de diretor escolar daquela escola (`escola.editar`,
  `turma.gerir`, `aluno.gerir`, `aluno.importar`, `usuario.gerir` para professor/aplicador, `relatorio.escola`).

---

## 7. Divergências entre o código atual e este catálogo (corrigir na etapa 2)

| Onde | Hoje no código | Catálogo |
|------|----------------|----------|
| `PUT/POST/DELETE /provas/{id}`, `/provas/{id}/gabarito*` | diretor escolar **pode** editar/publicar/excluir prova e gabarito | `prova.editar`/`gabarito.editar` só como **extra** para diretor |
| `POST/PUT /alunos` | professor **pode** cadastrar/editar aluno | `aluno.gerir` só como **extra** para professor |
| `POST /turmas/{id}/toggle` | coordenador não pode | coordenador pode receber como extra (ok) |
| tela "Equipe" | professor não vê | `usuario.ver` como extra para professor |
| `GET /nucleos` | diretor escolar não vê | coerente (—) |
| perfil Aluno | sem vínculo usuário↔aluno: não vê nada | `resultado.ver` do próprio aluno (depende do vínculo) |

---

## 8. Histórico de decisões incorporadas

- 01/11/2026 — T3 autorizado: `professor_autonomo` exclusivo do próprio espaço pessoal. Padrões preparados: `turma.ver`, `turma.gerir`, `turma.desativar`, `aluno.ver`, `aluno.gerir`, `aluno.desativar`, `prova.ver`, `prova.criar`, `prova.editar`, `gabarito.ver`, `gabarito.editar`, `cartao.imprimir`, `cartao.enviar`, `correcao.acompanhar`, `correcao.alterar`, `correcao.fechar`, `resultado.ver`, `resultado.compartilhar`, `relatorio.prova`, `relatorio.turma`. Sem extras, gestão institucional ou portal dos pais. Contexto ainda bloqueado (T3-A).

- 29/10/2026 — Diretor escolar não cria prova (doc 19, decisão 3).
- 29/10/2026 — Professor vê todas as provas das turmas em que está vinculado (doc 19, decisão 1).
- 30/10/2026 — Coordenador vê o relatório da escola.
- 30/10/2026 — Escolas e anexos: cadastro só por diretor de núcleo/admin; diretor escolar edita a própria.
- 30/10/2026 — Correção aberta/fechada; reabrir só pela supervisão.
- 30/10/2026 — Logo da escola e do ente (público: governo/prefeitura; privado: mantenedora).
- 30/10/2026 — Quem cadastra quem (seção 3) e permissões extras/retiradas com registro (doc 21).

### A1-C — política administrativa

O perfil `secretario_escolar` usa política independente da matriz legada: `escola.ver`, `turma.ver`, `turma.gerir`, `turma.desativar`, `aluno.ver`, `aluno.gerir`, `aluno.importar` e `aluno.desativar`. A permissão de importação fica preparada; o importador XLSX ainda não está implementado. O código do responsável depende de P3/P4. Sem vínculo válido, o perfil global não concede permissões. [Escopo e validação](70-a1-c-secretario-escolar.md).
