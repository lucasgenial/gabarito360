# 041 — Autoria das provas e "Meu espaço" — Planejamento

**Estado no tronco:** Em homologação
**Prioridade no plano mestre:** sem onda própria; restante (validação, produção) na fila de produção §7 ("Autoria das provas", PR #162)
**Natureza:** existente documentada a partir do código
**Origem:** ramo da correção de autoria, registrado como funcionalidade — docs/132
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Garantir que compartilhar uma turma não compartilhe provas entre professores: cada professor vê as próprias provas e as institucionais das turmas que lhe foram atribuídas; a gestão mantém a visão da escola. Mostrar quem criou cada prova e dar ao usuário um bloco "Meu espaço" com o contexto ativo e a troca de vínculo.

## 2. Escopo
- **Inclui:** A — escopo por autoria na consulta central de provas; B — "Criada por" na lista e no detalhe; C — bloco "Meu espaço" recolhível e troca de vínculo por ícone e modal; D — integração, conciliação explícita do legado (`perfil_criacao`) e publicação na homologação.
- **Não inclui:**
  - CRUD de provas e gabarito → [FN-040](../040-provas-gabaritos/planejamento.md).
  - Cópia da própria prova e cópia para colega → [FN-042](../042-duplicacao-provas/planejamento.md).
  - Seleção de vínculo e contexto por aba → FN-015.
  - Perfil Vice-diretor e demais perfis institucionais → FN-014.
  - Autoria contextual por vínculo e visibilidade por atribuição docente → entregues em [FN-038](../038-disciplinas-curriculo/planejamento.md) (PR #186).

## 3. Fontes de verdade
- Documento de origem: [docs/132](../../132-provas-autoria-cabecalho.md).
- Mockups: `mockups/provas.html` (lista); "Meu espaço" aprovado em revisão independente de UI/UX (docs/132), sem mockup próprio.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md).
- Plano mestre: [§2 FN-041](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-040 provas-gabaritos | Provas e `criado_por` | Concluída |
| FN-015 vinculos-contexto | Vínculo selecionado define o perfil de criação e a troca de acesso | Concluída |
| FN-012 permissoes-motor | Permissões efetivas por vínculo | Concluída |

**Funcionalidades que dependem desta:** FN-042 (elegibilidade da cópia pela autoria), FN-036 e FN-053 (filtro "professor autor"), FN-038 (autoria contextual), FN-049 (relatórios usam a mesma consulta central).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (A) | Escopo por autoria | Dois professores na mesma turma não compartilham provas; institucionais exigem turma atribuída | Entregue (homologação) | PR #162 (6305b63); `Escopo::consultarProvas` |
| 1.2 (B) | "Criada por" | Nome em lista/card/detalhe; conta não serializada | Entregue (homologação) | PR #162; `provas/index`, `provas/show` |
| 1.3 (C) | "Meu espaço" e troca de vínculo | Recolhível, teclado, temas | Entregue (homologação) | PR #162; `components/contexto-ativo.blade.php` |
| 1.4 (D) | Integração, conciliação do legado e homologação | CI, backup, comparação acadêmica | Entregue (homologação) | PR #162; fase HOMOLOGACAO-AUTORIA |
| 2.1 | Validação do responsável | Registro da validação | Pendente | — |
| 2.2 | Produção | Decisão expressa | Pendente | §4 item 2 |

## 6. Contrato técnico (proporcional ao risco)
- **Consulta central:** `Escopo::provas` protege listagem, painel, turma, gabarito, resultados, relatórios, cartões e operações diretas. Professor: próprias (`criado_por` e `perfil_criacao` nulo ou `professor`) ou institucionais (`perfil_criacao` em `PerfisEscolares::INSTITUCIONAIS`) ligadas a turma atribuída; com DC1, também exige indicação na disciplina e atribuição vigente. Professor autônomo: só as próprias.
- **Edição:** `Escopo::podeEditarProva` — professor só edita prova própria não institucional.
- **Modelo:** migration aditiva `perfil_criacao` (depois `vinculo_criacao_id`, `autoria_origem`), preenchidos por `AutoriaProva::capturar`, nunca pelo formulário; legado não inferido.
- **Projeção:** a API devolve `criado_por_nome`, sem CPF, e-mail ou conta serializada.
- **Troca de vínculo:** lista obtida no servidor pela identidade; resposta JSON só com URL e novo handle de sessão, sem token; limite de 20 s com mensagem de confirmação incerta.

## 7. Interface
- "Criada por: nome" em cards e detalhe; sem autoria registrada mostra "Autoria não registrada".
- "Meu espaço": `details/summary` nativo, fechado por padrão, duas colunas (Minha escola / Meu perfil; no pessoal, Tipo de espaço: Pessoal / Meu perfil).
- Ícone de troca (44 px) só quando há acesso alternativo; modal nativo com "Atual" e "Entrar".
- Conferência registrada por texto no docs/132.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção (PR #162 só na homologação) | Promoção em blocos / aguardar | Definir bloco após a segurança | Pendente |
| docs/132 cita capturas "fora do Git" usadas na conferência | — | Regra "sem prints": confirmar que não estão guardadas em nenhum serviço | Pendente |
| Provas legadas sem `perfil_criacao` | Conciliar caso a caso / manter | Só com confirmação do autor, como nas quatro já conciliadas | Registrado |

## 9. Ramos desta entrega
Encerrado: o perfil Vice-diretor apareceu ao testar a mesma pessoa como Diretora e Professora e foi tratado em FN-014.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
