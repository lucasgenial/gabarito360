# Plano mestre do Gabarito360 — tronco único

**Data:** 09/11/2026
**Status:** PROPOSTA — aguardando aprovação do responsável para a priorização (§5). O inventário de estados (§2 e §3) foi verificado contra código e Git; a ordem das ondas é recomendação.
**Regras:** [01-governanca.md](01-governanca.md). Este documento é a fonte única de prioridade e de status. Os documentos numerados `docs/NN-*.md` passam a ser histórico e evidência; quando divergirem deste plano, vale este plano (§6 lista as divergências conhecidas).
**Índice das funcionalidades:** [../funcionalidades/README.md](../funcionalidades/README.md).

## Como ler

- Cada funcionalidade tem um identificador canônico **FN-NNN** e um slug. A pasta prevista é `docs/funcionalidades/NNN-slug/`, com `planejamento.md` e `casos-de-uso.md` nos modelos de `docs/funcionalidades/_modelos/`. Nenhuma pasta foi criada ainda.
- **Estados no tronco:** Concluída · Em homologação · Parcial · Pendente · Adiada · Substituída · Candidata. "Concluída" = entregue e em produção, ou validada na homologação. "Em homologação" = publicada só na homologação, sem validação registrada. "Parcial" = parte entregue, parte pendente.
- **Status de subfase:** Entregue (produção) · Entregue (homologação) · Entregue (código) · Parcial · Pendente · Adiada · Substituída · Incerto.
- **Ambientes:** produção = CT 111 (release `a90fbf5` de 02/11/2026 mais o hotfix `83dd5ae`); homologação = VM 120. Tudo o que foi mesclado depois do PR #133, exceto o hotfix, está **só na homologação** (§7). Produção somente por decisão expressa.
- Regras transversais em vigor: nenhum print da aplicação no repositório ou em serviços; toda medição de desempenho usa o padrão de **1000 usuários simultâneos**, sem simulação; módulos transversais (agenda, notificações) não ganham versões locais dentro de outras funcionalidades.
- Os identificadores antigos (MP-xxx, T5, P3.2, F4...) continuam válidos como referência histórica. Use a tabela do §1 para traduzi-los.

---

## §1 Identificadores canônicos

### 1.1 Lista de FN por domínio

| Domínio | FN |
|---|---|
| Plataforma e operação | 001–010 |
| Identidade e acesso | 011–020 |
| Clientes, espaços e vínculos | 021–032 |
| Cadastros escolares | 033–039 |
| Provas e gabaritos | 040–044 |
| Correção e OMR | 045–048 |
| Resultados e relatórios | 049–051 |
| Painéis | 052–055 |
| Agenda e notificações | 056–059 |
| Comunicação e e-mail | 060 |
| LGPD e portal | 061–067 |
| Financeiro | 068–069 |
| Público e marca | 070–072 |
| Qualidade transversal (UI, Tailwind, desempenho, URLs) | 073–077 |

Novas funcionalidades recebem o próximo número livre (078 em diante), independentemente do domínio. Números não são reaproveitados.

### 1.2 Equivalência de identificadores antigos

| Identificador antigo (documento) | FN |
|---|---|
| MP-001..005 (docs/13) | FN-001 |
| MP-006..011, MP-040 (docs/13, docs/19); SEGES | FN-052 |
| MP-012; RF-001 | FN-011 |
| MP-013, 018, 026, 030, 031, 036; etapas 0–7 do docs/21 | FN-012 |
| MP-014..017, MP-035a (foto), MP-039 | FN-033 |
| MP-016 (importação por planilha) | FN-034 |
| MP-035b (status de matrícula) | FN-026 |
| MP-019, 020, 037 | FN-040 |
| MP-021, MP-033 e MP-038 (mesma entrega) | FN-045 |
| MP-022, MP-034; hotfix PDF em lote (docs/123) | FN-049 |
| MP-023 (Android) e iOS | FN-010 |
| MP-024 | FN-019 |
| MP-025, 027, 028, 029; landing (só a rota) | FN-030 |
| MP-032 | FN-058 |
| MP-041; decisão 6.4 do docs/19 | FN-020 |
| B1–B4, B6 (docs/28) | FN-002 |
| B5 e extensões (`--manter-manutencao`, `--backup-final`, `umask`), procedimentos T2 (docs/49–54), publicação por fase (docs/99–100) | FN-003 |
| **H1** homologação isolada (docs/125–130, ADR-H1) | FN-003 |
| **H1** identidade visual oficial (docs/161–162, A–E) | FN-071 |
| **H1-P** apresentação pública, etapas P1–P5 (docs/164) | FN-072 |
| E1–E7 (docs/28) | FN-060 |
| E8 (páginas públicas); home estática (docs/101–102) | FN-072 |
| **L1–L6** LGPD (docs/28) | L1 → FN-061 · L2 → FN-062 · L3 → FN-063 · L4 (A–E) → FN-064 · L5 → FN-065 · L6 → FN-066 |
| **L0–L8** layout (docs/23) | FN-073 |
| RL1–RL19 (docs/33) | FN-061 a FN-066, conforme o requisito |
| T1, T2 | FN-021 |
| T3 | FN-022 |
| T4 | FN-023 |
| T5 (A–E) e revisão UX (PR #149) | FN-024 |
| T6 (A–E) | FN-028 |
| **V1, V2** vínculos (docs/28, 55–62) | FN-015 |
| **V1–V5** base visual (docs/26); G1–G3 gráficos (docs/27) | FN-070 |
| A1-A, A1-B1, A1-B2 | FN-013 |
| A1-C (secretário escolar); PF1 | FN-014 |
| **A2** painel técnico (docs/28) — não confundir com T5-A2 | FN-006 |
| **A3**, A4 (docs/28) — não confundir com T5-A3 | FN-033 |
| A5 (importar/exportar) | FN-034 (troca de turma com histórico → FN-026) |
| A6 | FN-035 |
| A7; CP1, CP2 | FN-042 |
| AU1, expurgo, AU2 | FN-004 |
| AU3, S1 | FN-005 |
| N1–N4 | FN-057 |
| **C1–C3** calendário (docs/28); P5 dos painéis | FN-056 |
| C4 (provas no calendário e lembretes) | FN-059 |
| **P1–P8** portal dos responsáveis (docs/28) | FN-067 |
| **P0–P3, P8.2–P8.4** painéis (docs/177) | FN-053 |
| P4 painéis (períodos); "PL1" | FN-039 |
| P5 painéis | FN-056 e FN-057 |
| P6 painéis | FN-055 |
| P7.1 painéis; GII | FN-044 |
| P7.2–P7.4 painéis (TCT/TRI, integridade, sínteses) | FN-051 |
| P8.1 painéis; TU1-PDF | FN-050 |
| **M3** "Tempo escolar e agenda" (docs/177) | FN-056 e FN-039. "M3 Core" (mesmo doc) não tem correspondência identificada |
| **F1–F3** financeiro (docs/28); FIN | FN-068 |
| **F0–F4, F6**, PERF-01..15, FUND-01..10 desempenho (docs/192–200) | FN-077 |
| **F5** busca nacional do catálogo | FN-032 |
| **R1** selo dos documentos (docs/28) | FN-069 |
| **R1–R3** fotos leves e revisão (docs/25); ações 1–4 do docs/24; hotfixes docs/152–157 | FN-046 |
| R1–R6 e TU1-R3 (docs/tu1-*) | FN-036 |
| R0 conciliação documental (docs/117) | Sem FN: histórico, concluído (PR #150) |
| REL (gate de release do docs/115) | §7 |
| X1, X2 | FN-077 |
| X3–X6; AP1-L; separação física da API | FN-007 |
| CI1 | FN-031 |
| ID1 (inclui ID1-D1 provisórios) | FN-016 |
| VI1 | FN-017 |
| DC1 | FN-038 |
| MT1 | FN-026 |
| TR1, TE1, UX1 | FN-025 |
| PA1 | FN-027 |
| **EI1** escolas independentes (docs/163) | FN-029 |
| **EI1–EI6** etapas do editor (docs/191); FT1–FT3 | FN-054 |
| OMR1–OMR3 | FN-047 |
| Driver OMR externo (fase 6 pós-MVP) | FN-048 |
| UR1–UR4, UR2, UR2-P | FN-018 |
| ST1, AP1-A..E | FN-077 |
| UI1, UI1-R; carregamento progressivo e breadcrumb (OMR2-C2) | FN-076 |
| TBL1, TBL2 | FN-074 |
| TW1–TW7; UX-D, UX-T; ações SMALL (docs/184) | FN-075 |
| TU1 (docs/175, tu1-revisao-fluxos, tu1-r3) | FN-036 |
| TU-IMG, IMG-E/L/N/P | FN-037 |
| IM1 | FN-019 |
| Autoria das provas e "Meu espaço" (docs/132) | FN-041 |
| CI do MariaDB (docs/181) | FN-008 |
| Estrutura-alvo de diretórios (docs/168) | FN-009 |
| D9, D10, D13, D14, D15 (decisões do docs/28) | §4 |

---

## §2 Mapa de funcionalidades

Formato: título com estado; origem; tabela de subfases condensada. As pendências detalhadas estão no §3.

### Plataforma e operação

#### FN-001 fundacao-monorepo — Concluída
Origem: docs/12, 13 (MP-001..005), 14, 15.
| Subfase | Status | Evidência |
|---|---|---|
| MP-001 estrutura do monorepo | Parcial | só `apps/api` e `apps/web`; android/ios → FN-010 |
| MP-002..005 docs, migrations, API base, WEB base | Entregue (produção) | commits "MP-003..005" |

#### FN-002 base-operacao — Concluída (com ressalva)
Origem: docs/28 fase 0, docs/36, docs/operacao/backup.md, deploy-b6.
| Subfase | Status | Evidência |
|---|---|---|
| B1 backup e restauração | Entregue (produção) | PR #51, #62 |
| B2 troca de senha do banco | Cancelada | decisão de 31/10 |
| B3 Redis; B4 workers e agendador | Entregue (produção) | PR #52 |
| B6 leitura de cartão pela fila | Entregue (produção), com ressalva | PR #61, #65; docs/36; falta cartão real em produção |

#### FN-003 publicacao-e-homologacao — Concluída
Origem: docs/37, 49–54, 99, 100, 125–130 (ADR-H1).
| Subfase | Status | Evidência |
|---|---|---|
| B5 roteiro único + `--manter-manutencao`, guarda de esquema, `--backup-final` | Entregue (produção) | PR #66/#67, #81, #82, #84 |
| B5 `umask 022` | Incerto | docs/54; provável PR #86 |
| Ensaios de restauração (RTO ~7–8,5 min, não é SLA) | Entregue | docs/52, 100 |
| Publicação por fase (`publicar-fase.sh`) e publicação V1–T5 | Entregue (produção 02/11) | PR #133, `a90fbf5` |
| Encerrar marcador `primeira-ativacao-pendente` | Incerto | docs/100 |
| H1-A..E homologação isolada (VM 120, dados, HTTPS, `deploy.py`, backup) | Entregue | docs/127–129; PR #159 |

#### FN-004 auditoria — Parcial
Origem: docs/28 (AU1, AU2), 32, 72–76, operacao/auditoria.md.
| Subfase | Status | Evidência |
|---|---|---|
| AU1-A..B4 captura, retenção 180 d, arquivo mensal, ensaio de volume | Entregue | PR #106–#110 |
| Ativação com credencial restrita | Entregue (produção 02/11) | docs/100 |
| Expurgo de eventos vencidos | Pendente | docs/74, 80 |
| Particionamento físico / ledger | Adiada | docs/75 (após volume real) |
| AU2 tela de consulta e exportação | Pendente | sem rota |

#### FN-005 suporte-ver-como — Pendente
Origem: docs/28 (S1), docs/67, 71, 72, 77 (AU3).
| Subfase | Status | Evidência |
|---|---|---|
| S1 "ver como" | Pendente | bloqueado por D9 |
| AU3 atendimento com acesso pedagógico auditado | Pendente | sem documento próprio |

#### FN-006 painel-tecnico — Pendente
Origem: docs/28 (A2). Sem código.

#### FN-007 escala-infraestrutura — Pendente
Origem: docs/28 (X3–X6), docs/167, 172, 173, 176.
| Subfase | Status | Evidência |
|---|---|---|
| X3 limites por rota, WAF, Turnstile; AP1-L (93 rotas) | Pendente | throttle pontual só em L4; Turnstile só no contato |
| X4 fotos em armazenamento externo (R2) | Pendente | — |
| X5 separação de serviços | Parcial | grupos PHP web/API separados desde 30/10; separação física adiada |
| X6 observabilidade | Pendente | — |
| Mutex `flock` → solução distribuída | Pendente | docs/167 (antes de múltiplos hosts) |

#### FN-008 ci-integracao — Concluída
Origem: docs/181. tmpfs e guard no job MariaDB (29m44 → 20m15) e −28,9 % de consultas: PR #217, #218. Artefato final fora do Git: Incerto.

#### FN-009 estrutura-diretorios-alvo — Candidata
Origem: docs/168 (ramo do ST1). Estrutura `apps/web`, `apps/api/plataforma/` aceita pelo responsável, sem plano e sem execução.

#### FN-010 app-mobile — Adiada
Origem: docs/13 MP-023, docs/02 fase 8. Sem `apps/android` e `apps/ios`. A correção pela câmera foi entregue na web móvel.

### Identidade e acesso

#### FN-011 autenticacao-conta — Parcial
Origem: docs/13 MP-012, docs/04 RF-001, docs/28 E3/E6.
| Subfase | Status | Evidência |
|---|---|---|
| Esqueci/redefinir senha | Entregue (produção) | 3506ab4; E3 |
| Confirmação e troca de e-mail (E6) | Entregue (código), sem teste real | PR #54 |
| Autocadastro com aceite LGPD | Substituída | sem rota `/cadastro`; acesso por convite (FN-015) — ver FN-030 |
| Login gov.br (RF-001.3) | Incerto | não verificado |

#### FN-012 permissoes-motor — Concluída
Origem: docs/03, 13, 19, 21, 22.
| Subfase | Status | Evidência |
|---|---|---|
| MP-013 membros; MP-018 vínculo professor↔turma; MP-026 secretaria e aplicador | Entregue (produção) | f3dfa94, "MP-018", 917dd81 |
| Etapas 0–7 docs/21 (catálogo, motor, telas, menus, celular, escolas INEP, testes) | Entregue (produção) | PR #15–#22 |
| MP-030 motor configurável | Entregue por outro desenho | 509be82; `Support\Permissao` |
| MP-031 `perfil:` → `permissao:` | Parcial | restam 8 rotas `dashboard/*` |
| MP-036 menu por perfil | Entregue | 59a80ba |

#### FN-013 acessos-internos — Concluída
Origem: docs/66–69, 100, 102.
| Subfase | Status | Evidência |
|---|---|---|
| A1-A, A1-B1, A1-B2 (Desenvolvedor, Suporte, Financeiro) | Entregue (produção) | PR #101–#103 |
| Contas internas com CPF fictício | Entregue | docs/100, 102 |

#### FN-014 perfis-institucionais — Concluída
Origem: docs/70, 132–135, 139.
| Subfase | Status | Evidência |
|---|---|---|
| A1-C secretário escolar | Entregue (produção) | PR #104 |
| PF1-A..C5 (vice-diretor, auxiliar de secretaria, grupos funcionais) | Entregue (homologação, validado) | PR #163–#165; docs/139 |
| Backlog de perfis futuros (coordenador de área, orientador, AEE, responsável, auditor etc.) | Pendente | docs/133 |
| Secretário de Educação contextual | Pendente | depende de FN-017 |
| Código de acesso do responsável | Adiada | depende de FN-067 |

#### FN-015 vinculos-contexto — Concluída
Origem: docs/55–62, 100.
| Subfase | Status | Evidência |
|---|---|---|
| V1-A..C3 vínculos por pessoa, convites, regime obrigatório | Entregue (produção 02/11) | PR #87–#92 |
| V2-A..C seleção de vínculo e gestão por vínculo | Entregue (produção 02/11) | PR #93–#95 |
| Aceite visual de V2 | Pendente | docs/100 |
| Aluno sem vínculo de origem | Pendente (decisão) | docs/60, 100 |

#### FN-016 identidade-pessoal — Parcial
Origem: docs/115 §4, 138–140.
| Subfase | Status | Evidência |
|---|---|---|
| ID1-A esquema `pessoas`/documentos | Entregue (homologação) | PR #168 |
| ID1-B/C técnicos (busca interna, inventário) | Entregue (código) | docs/139 |
| ID1-D1 referências provisórias (flag desligada) | Entregue (código) | PR #170 |
| ID1-B/C operacionais (capacidades de consulta) | Pendente | decisão §4 |
| Backfill real e conciliação global | Pendente | proibido até contrato (docs/140–142) |

#### FN-017 convites-cpf — Pendente
Origem: docs/115 §5, 119, 125, 133, 139. VI1-A cadastro CPF-primeiro, VI1-B aceite antecipado, VI1-C notificações: nenhum código. Depende de FN-016 e FN-057.

#### FN-018 seguranca-urls-sessao — Em homologação
Origem: docs/165–171.
| Subfase | Status | Evidência |
|---|---|---|
| UR1-A contexto no path | Substituída por UR3 | PR #192 |
| UR1-B..E e-mails sem token, proxy de fotos | Entregue (homologação) | PR #192 |
| UR2 atualização de dependências + `composer audit` | Entregue (homologação) | PR #193 |
| UR2-P hotfix de dependências para produção | Pendente | PR #194 aberto desde 05/11 |
| UR3, UR4, UR4-R1, sessão única | Entregue (homologação) | PR #195, #197–#199 |

#### FN-019 perfil-configuracoes — Concluída
Origem: docs/13 MP-024, docs/19 MP-040, docs/134–137.
| Subfase | Status | Evidência |
|---|---|---|
| MP-024 perfil e configurações | Entregue (produção) | 076e759 |
| IM1 foto/avatar (recorte, HEIC, DNG parcial) | Entregue (homologação, validado) | PR #164, #166, #167 |
| Limpeza de imagens órfãs | Pendente | docs/136 |

#### FN-020 acesso-aluno — Pendente (Incerto)
Origem: docs/19 MP-041 e decisão 6.4; docs/03. `DashboardController::aluno` resolve o aluno por referência do vínculo, mas o docs/03 diz "pendente". Falta a decisão de liberar o login do aluno.

### Clientes, espaços e vínculos

#### FN-021 clientes-espacos — Concluída
Origem: docs/40–54.
| Subfase | Status | Evidência |
|---|---|---|
| T1 modelo e mapa de consultas | Entregue | PR #73 |
| T2-A..E, ensaios e corte | Entregue (produção 01/11) | PR #74–#85; release af4c6d1 |

#### FN-022 espaco-pessoal — Concluída (revisão pendente)
Origem: docs/63–65. T3-A..C: Entregue (produção 02/11), PR #96, #97, #99. Pendentes: tela de parâmetros pessoais; revisão das constraints que proíbem CPF no aluno pessoal (FN-027).

#### FN-023 cadastro-clientes — Concluída
Origem: docs/66, 71. T4-A/T4-B: Entregue (produção), PR #100, #105. Contratos completos → FN-068.

#### FN-024 copias-entre-espacos — Substituída (política); código ativo
Origem: docs/77–99, 114; substituição pelo docs/115 §9.
| Subfase | Status | Evidência |
|---|---|---|
| T5-A1..E3 (capacidades, lotes, outbox, materiais, cadastro, resultados, interface) | Entregue (produção 02/11, flags ativas; nenhuma cópia iniciada) | PR #111–#132; docs/100 |
| Revisão UX (PR #149) e flags na homologação | Entregue (homologação) | docs/114, 129 |
| Aceite visual E1–E3 | Pendente | docs/99, 100 |
| Política antiga (capacidades genéricas do Diretor Escolar) | Substituída por FN-025 | docs/115, 117 |
| Complemento de nascimento, solicitação do professor, estatística anônima, limpeza de staging, ensaio no servidor | Pendente | docs/77–90 |

#### FN-025 transferencias-institucionais — Pendente
Origem: docs/115 §7–§9, 116, 117, 139. TR1-A..D, TE1-A..D, UX1-A..D: sem código. Depende de FN-016, FN-026, FN-031.

#### FN-026 matricula-enturmacao — Parcial
Origem: docs/115 MT1, docs/13 MP-035b, docs/28 A5 (troca de turma).
| Subfase | Status | Evidência |
|---|---|---|
| Movimentação entre turmas da mesma escola (`aluno_movimentacoes`) | Entregue (homologação) | PR #204; `MovimentarAlunoTurma` |
| Arquivamento de aluno/turma (substitui status de 3 estados) | Entregue (homologação) | PR #207 |
| MT1-A..C matrícula temporal, relatórios pelo contexto histórico | Pendente | docs/115 |

#### FN-027 professor-independente — Parcial
Origem: docs/115 §10, 124. PA1-A referência ao catálogo: Parcial (seleção em CI1-D; uso em turmas pendente). PA1-B/C/D (CPF opcional, conciliação, continuidade): Pendente. Exige revisão formal de T3.

#### FN-028 encerramento-cliente — Pendente
Origem: docs/28 T6, docs/102, 115 §12. T6-A..E sem código. Depende de FN-064 (L4-D/E) e FN-068.

#### FN-029 escola-independente — Pendente
Origem: docs/163, 176. EI1-A diagnóstico: Parcial. EI1-B..G: Pendente, sem código. EI1-E depende de FN-068. Anunciada como "Em breve" na home.

#### FN-030 saas-autocadastro-assinatura — Substituída (parcial)
Origem: docs/13 MP-025..029, docs/06 RN-015/016, docs/01 §30.
| Subfase | Status | Evidência |
|---|---|---|
| MP-025 secretaria e modalidade | Entregue (produção) | "MP-025" |
| MP-027 planos, assinatura, checkout Mercado Pago | Substituída por contratação por contato | de68d3f → PR #59 |
| MP-028 autocadastro | Substituída | convites (FN-015), T4 (FN-023), espaço pessoal (FN-022) |
| MP-029 absorção de rede individual | Pendente, provavelmente obsoleta | sem código |

#### FN-031 catalogo-instituicoes — Em homologação
Origem: docs/116, 119–126, 129, 130.
| Subfase | Status | Evidência |
|---|---|---|
| CI1-A..D (inventário, staging, publicação versionada, relações, "Minhas instituições") | Entregue (código/homologação) | PR #152–#157 |
| CI1-E solicitações e manutenção; CI1-F validação integrada | Entregue (homologação) | PR #158, #160 |
| Conciliação das 6 escolas operacionais sem INEP | Pendente | docs/119, 121 |
| 8 lacunas e unidades adicionais | Incerto | docs/122 |
| Backlog de UI no celular | Pendente | docs/126, 129 |

#### FN-032 busca-catalogo-autocompletar — Adiada
Origem: docs/200 (F5, PERF-09/10/12). Adiada em 09/11/2026: sem uso real. Desenho futuro: componente único de autocompletar por prefixo, top 10, cache por edição.

### Cadastros escolares

#### FN-033 estrutura-escolar — Parcial
Origem: docs/13 MP-014..017, 035, 039; docs/28 A3, A4.
| Subfase | Status | Evidência |
|---|---|---|
| MP-014 núcleos (API), MP-015 escolas, MP-016 turmas, MP-017 alunos | Entregue (produção) | commits MP-014..017 |
| Ficha PDF do aluno | Incerto | rota não encontrada |
| MP-035a foto do aluno na edição | Entregue, depois substituída por avatares (FN-066) | ImagemAlunoController |
| A3 dados dos núcleos e `nucleo.editar` | Pendente | só `nucleo.gerir` legado |
| A4 separar equipes de núcleo e escola | Pendente | — |

#### FN-034 importacao-exportacao-alunos — Pendente
Origem: docs/13 MP-016, docs/28 A5, docs/70, 134. Só existe o comando `g360:importar-alunos`. Permissão `aluno.importar` sem rota; tela mostra "Em breve". Formato da planilha "a definir".

#### FN-035 responsaveis-alunos — Pendente
Origem: docs/28 A6. Sem tabelas. Pré-requisito de FN-067.

#### FN-036 organizacao-turma — Em homologação
Origem: docs/175, 176, tu1-revisao-fluxos, tu1-r3.
| Subfase | Status | Evidência |
|---|---|---|
| TU1-A..E abas e operações | Entregue (homologação) | PR #203, #204, #207 |
| R1, R2 (+cadastro), R4 PDF do recorte, R5 transferência entre turmas | Entregue (homologação) | PR #204–#206 |
| R3 dashboard em modal | Substituída por TU1-R3 | PR #207 |
| TU1-R3-A..F KPIs, gráficos, arquivamento | Entregue (homologação) | PR #207 |
| Conferência autenticada / aceite visual final | Pendente | docs/176 |
| Chaves legadas `*.desativar` e rotas toggle | Pendente | tu1-r3 |

#### FN-037 imagens-turmas-educadores — Parcial
Origem: docs/tu-img-avatar-foto, 176, 177.
| Subfase | Status | Evidência |
|---|---|---|
| TU-IMG (36 educadores, 36 brasões, API, interface) | Entregue (homologação) | PR #208, #209 |
| IMG-E, IMG-L; sprites WebP v2 | Entregue (homologação) | PR #209, #215, #233 |
| IMG-N imagem ao criar turma | Pendente | — |
| IMG-P imagem no cadastro de professor | Pendente | — |

#### FN-038 disciplinas-curriculo — Em homologação
Origem: docs/115 §6, 139, 141–151, 156, 157.
| Subfase | Status | Evidência |
|---|---|---|
| DC1-A..C BNCC, catálogo local, componente da turma | Entregue (homologação) | PR #171 |
| Áreas N:N, vínculos temporais, autoria contextual, integração curricular, formulário, simulados ENEM | Entregue (homologação) | PR #186; publicado 273db8a |
| Seleção em lote "área organiza disciplinas" | Substituída | docs/148 |
| Recurso genérico de institucionalização | Sem planejamento | docs/145 |

#### FN-039 periodos-letivos — Candidata
Origem: ramo dos painéis (docs/177 P4, 179, 180). Só o widget demonstrativo. P4.1–P4.4 pendentes. Dependências: FN-056, FN-038, FN-026. Aguarda priorização.

### Provas e gabaritos

#### FN-040 provas-gabaritos — Concluída
Origem: docs/13 MP-019, 020, 037. Entregue (produção). Stepper do MP-037: Incerto.

#### FN-041 autoria-provas — Em homologação
Origem: docs/132. A escopo por autoria, B "Criada por", C "Meu espaço", D conciliação: Entregue (homologação), PR #162.

#### FN-042 duplicacao-provas — Parcial
Origem: docs/28 A7, docs/115 §3, 118, 142.
| Subfase | Status | Evidência |
|---|---|---|
| CP1-A..D copiar a própria prova + `formato_avaliacao` | Entregue (homologação) | PR #151 |
| CP2 coordenação copia para professor | Pendente (só contrato) | docs/142; chave inexistente |
| A7 exportar/importar prova em JSON | Incerto | não verificado |

#### FN-043 editor-provas-questoes — Candidata
Origem: docs/02 e docs/12 (fases pós-MVP 2–5), docs/118, 164. Banco de questões, montador, versões, discursivas, formatos digital e misto, marcações na página da prova, relatório por tema. Anunciado "Em breve" na home; sem plano.

#### FN-044 acervo-gii — Candidata
Origem: docs/144, 146, 164, 176, 177 (P7.1). "Nenhum planejamento do GII foi iniciado".

### Correção e OMR

#### FN-045 omr-correcao — Concluída (com ressalva)
Origem: docs/08, 13, 18, 19.
| Subfase | Status | Evidência |
|---|---|---|
| MP-021 motor OMR, cartões com QR | Entregue (produção) | 15e26f4 |
| MP-033 = MP-038 acompanhamento por turma | Entregue (produção) | b1b8c0f |
| Teste com cartão impresso real | Pendente | docs/18 |

#### FN-046 revisao-leituras — Parcial
Origem: docs/24, 25, 152–157.
| Subfase | Status | Evidência |
|---|---|---|
| Ações 1–4 do docs/24; R1 miniaturas; R2 tela de revisão | Entregue (produção) | PR #37, #40, #41 |
| R3 ajuste de limiares | Pendente | docs/25 |
| Hotfix leituras 500 | Entregue (homologação); produção não autorizada | PR #182 |
| Cartão no modal, imagem protegida, leitor PDF lateral | Entregue (homologação) | PR #184, #185, #187 |
| Assinatura do cartão inteiro | Entregue, depois obsoleta (UR1/UR3) | PR #183 |

#### FN-047 omr-qualidade-resultado — Em homologação
Origem: docs/158–160. OMR1-A..D, OMR2-A..C2, OMR3-A..G (qualidade, tentativas, histórico, resultado, percentuais, PDFs, validação pública): Entregue (homologação 45f9248), PR #188–#190.

#### FN-048 omr-driver-externo — Candidata
Origem: docs/08, 12 (fase 6 pós-MVP). Sem plano.

### Resultados e relatórios

#### FN-049 relatorios-resultados — Concluída
Origem: docs/13 MP-022, 034; docs/123.
| Subfase | Status | Evidência |
|---|---|---|
| MP-022 resultado individual e da prova | Entregue (produção) | 7e0af6e |
| MP-034 escola/núcleo/rede + PDF | Entregue (produção) | f995232 |
| PDF em lote (hotfix) | Entregue (produção) | PR #156; 83dd5ae |
| Relatório por tema | Pendente | depende de FN-043 |

#### FN-050 relatorio-turma-pdf — Pendente
Origem: docs/176 (TU1-PDF), 177 (P8.1). WIP na branch `feat/tu1-pdf-relatorio` (b5cd6e0), fora da main. Dependência P3.2 já entregue.

#### FN-051 analises-avancadas — Candidata
Origem: docs/164, 177 (P7.2–P7.4). TCT/TRI, integridade e padrões de resposta, sínteses automáticas. Anunciado "Em breve"; sem plano.

### Painéis

#### FN-052 dashboards-perfil — Concluída
Origem: docs/13 MP-006..011, docs/19 MP-040, docs/27 G3.
| Subfase | Status | Evidência |
|---|---|---|
| MP-006..011 painéis dos 6 perfis | Entregue (produção) | commits MP-006..011 |
| MP-040 painel do dir. escolar e do aplicador | Incerto | sem commit identificável |
| Alertas SEGES | Incerto | só modelo; sem integração |

#### FN-053 paineis-turmas — Parcial
Origem: docs/176–183, contratos/resumo-cadastral-turmas.md.
| Subfase | Status | Evidência |
|---|---|---|
| P0, P1.1, P1.3, P1.5 | Entregue (homologação) | PR #210, #211 |
| P1.2 filtros; P1.4 semântica | Parcial | docs/179 |
| P1.4.6 elegíveis por aplicação | Pendente | depende de FN-026 e FN-059 |
| P2.1–P2.13 composição e modelos demonstrativos | Entregue (homologação) | PR #211–#214 |
| P2.4 variantes por perfil | Incerto | — |
| P3.1, P3.2 e ajuste | Entregue (homologação) | PR #215, #220, #221 |
| P3.3 painel agregado de turmas | Pendente | próximo pacote canônico do docs/182 |
| P3.4 estados por região | Parcial | F2/F4 |
| P8.2–P8.4 validação contínua | Contínua | — |

#### FN-054 paineis-editor — Concluída
Origem: docs/179, 180, 188–193. GridStack, redistribuição lateral, seções reordenáveis, editor hierárquico (etapas EI1–EI6 do docs/191), FT1–FT3: Entregue (homologação, validado), PR #211–#219, #226–#228.

#### FN-055 acompanhamento-pedagogico — Pendente
Origem: docs/177 P6, 179, 180. Visual demonstrativo entregue. P6.1–P6.4 pendentes. Aguarda FN-056 e FN-039.

### Agenda e notificações

#### FN-056 agenda-calendario — Pendente · MÓDULO TRANSVERSAL
Origem: docs/28 C1–C3, docs/177 P5, 179, 180, 189.
| Subfase | Status | Evidência |
|---|---|---|
| Widgets de agenda com CalendarJS (demonstrativos) | Entregue (homologação, visual) | PR #211–#214; `painel-agenda.js` |
| C1 prova de conceito | Parcial | biblioteca adotada; aprovação formal não registrada |
| C2 modelo de eventos e permissões `calendario.*` | Pendente | sem migration |
| C3 tela de calendário e cards reais | Pendente | — |
**Dependentes que aguardam este módulo:** FN-053 (cards de agenda), FN-055, FN-058, FN-059, FN-039, FN-017 (VI1-B agendado). Nenhum deles deve criar agenda própria.

#### FN-057 notificacoes — Pendente · MÓDULO TRANSVERSAL
Origem: docs/28 N1–N4, docs/177 P5.4. N1 modelo e Notificador, N2 central, N3 preferências e e-mail: Pendente. N4 push: Adiada (opcional).
**Dependentes:** FN-042 (CP2), FN-017 (VI1-C), FN-059, FN-060 (modelos E4 fase 2), FN-067 (P8), FN-053 (cards de avisos).

#### FN-058 visitas-pedagogicas — Pendente
Origem: docs/13 MP-032, docs/07 UC-009. Só a tabela `visitas` e o model. **Aguarda FN-056.**

#### FN-059 agendamento-aplicacao-provas — Pendente
Origem: docs/28 C4, docs/177 P1.4.6 e P5.4. Provas no calendário, lembretes, registro de aplicação e elegíveis. **Aguarda FN-056 e FN-057.**

### Comunicação e e-mail

#### FN-060 email-transacional — Parcial
Origem: docs/28 E1–E7, operacao/email.md.
| Subfase | Status | Evidência |
|---|---|---|
| E1 domínio, E2 motor, E3 recuperação, E7 Fale Conosco | Entregue (produção) | PR #53, #54, #58 |
| E4 modelos fase 1 | Entregue | PR #54 |
| E4 modelos fases 2–4 | Pendente | dependem de FN-057, FN-059, FN-034 |
| E5 convite, E6 troca de e-mail | Entregue (código), sem teste real | PR #54, #55 |
| DMARC `p=none` → `quarantine` | Pendente | operacao/email.md |

### LGPD e portal

#### FN-061 lgpd-termos-privacidade — Parcial
Origem: docs/28 L1, 33, 35. Prévias com campos a preencher (PR #64). Sem `/termos`, `/privacidade`, tabelas de aceite nem cookies (RL16). Bloqueio: D13, D14.

#### FN-062 lgpd-inventario-retencao — Parcial
Origem: docs/28 L2, 34, 38, 39. Inventário e RIPD preliminar entregues (PR #68, #71). Pendentes: contratos de terceiros, prazos por instituição, lista pública (RL12), validação do RIPD (RL13), retenções aprovadas no docs/39 sem implementação, criptografia do disco D: inconclusiva.

#### FN-063 lgpd-protecao-dados — Concluída
Origem: docs/28 L3, operacao/protecao-dados.md. Entregue (produção), PR #69.

#### FN-064 pedidos-titulares — Parcial
Origem: docs/102–112, 115, 117, 129.
| Subfase | Status | Evidência |
|---|---|---|
| L4-A, B1–B3 protocolos e interface | Entregue (homologação, flags ativas na VM 120) | PR #139–#142 |
| L4-C1..C5 exportação do titular | Entregue (homologação) | PR #143–#148 |
| L4-D correção e retenção | Pendente | `AvaliacaoGuardaTitular` ausente; docs/113 inexistente |
| L4-E anonimização e ensaio | Pendente | — |
| Produção | Pendente | gate §4 |

#### FN-065 plano-incidentes — Pendente
Origem: docs/28 L5. Não existe `docs/operacao/incidentes.md`. Desbloqueado (AU1 concluído).

#### FN-066 avatares-alunos — Parcial
Origem: docs/28 L6, operacao/avatares-alunos.md. Galeria de 36 avatares: Entregue (produção), PR #70. Passo 6 (apagar fotos, remover `foto_path`): Pendente, não autorizado.

#### FN-067 portal-responsaveis — Pendente
Origem: docs/28 P1–P8, 33 §4, 38. Nenhuma subfase iniciada. Bloqueios: D10, D14, FN-062, FN-035, FN-061, FN-069.

### Financeiro

#### FN-068 financeiro — Pendente
Origem: docs/28 F1–F3, 163 (regras de EI1), 176 (FIN). Contratos, faturas, telas, gateway: sem código. Só o `MercadoPagoService` legado.

#### FN-069 selo-documentos — Pendente
Origem: docs/28 R1. Selo presenteado/pagante nos documentos. Sem código.

### Público e marca

#### FN-070 identidade-visual — Concluída
Origem: docs/26 V1–V5, docs/27 G1–G3. Entregue (produção), PR #45–#49. Decisões 1 e 2 do docs/26: Incerto (provavelmente aplicadas).

#### FN-071 marca-documentos — Em homologação
Origem: docs/161, 162. H1-A..E (SVG oficial, `<x-marca>`, favicons, cabeçalhos de documentos): Entregue (homologação), PR #191. Produção da configuração Nginx: Pendente.

#### FN-072 site-publico — Em homologação
Origem: docs/28 E8, docs/101, 102, 161, 162, 164.
| Subfase | Status | Evidência |
|---|---|---|
| Landing e páginas públicas (E8 visual) | Entregue (produção) | PR #59, #60 |
| Home estática independente da manutenção; redes sociais | Entregue (servidor) | PR #135–#137; rodapé a reconciliar antes do próximo B5 |
| YouTube | Pendente | docs/101 |
| H1-P apresentação de pré-lançamento (P1–P5) | Entregue (homologação); versão final Incerta | PR #191; docs/164 |
| Abertura comercial / produção | Pendente | decisão comercial |

### Qualidade transversal

#### FN-073 layout-responsivo — Concluída
Origem: docs/23. L0–L8: Entregue (produção), PR #25, #28–#34.

#### FN-074 tabelas-padrao — Em homologação
Origem: docs/130, 131, 149.
| Subfase | Status | Evidência |
|---|---|---|
| TBL1-A, B, D | Entregue | PR #161 |
| TBL1-C resumo em cards das matrizes | Adiada | docs/131 |
| TBL2 tabelas integradas (50 instâncias) | Entregue (homologação) | PR #186 |
| Paginação opcional na API (`paginado=1`) | Pendente | docs/131 |

#### FN-075 tailwind-ui — Parcial
Origem: docs/176, 184–188.
| Subfase | Status | Evidência |
|---|---|---|
| Ações SMALL no CSS próprio | Substituída por TW | docs/185 |
| TW1, TW2 (+complemento, versão 0.1.0) | Entregue (homologação) | PR #222–#224 |
| TW2 smoke final | Incerto | docs/187 |
| TW3 parcial (ações e ordenação) | Entregue (homologação) | PR #225 |
| TW3 restante, TW4 (absorve UX-D), TW5, TW6 | Pendente | docs/188 |
| TW7 validação | Contínua | — |

#### FN-076 carregamento-estados — Parcial
Origem: docs/159, 174, 176. UI1-A..D: Entregue (homologação), PR #201, #202. Breadcrumb e toast (OMR2-C2): Entregue só em prova/correção/resultado. UI1-R carregamento regional em toda a aplicação: Parcial.

#### FN-077 desempenho — Parcial
Origem: docs/168, 172, 173, 192–200; docs/28 X1–X2.
| Subfase | Status | Evidência |
|---|---|---|
| ST1-A..E estabilização da turma | Entregue (homologação) | PR #196 |
| AP1-A..D baseline, EXISTS, contrato, `API_BASE_URL` | Entregue | PR #200 |
| AP1-E medição na homologação | Incerto | sem recibo |
| F0 ADR; F1 diagnóstico | Entregue | PR #229 |
| F2 autorização em SQL e lotes | Entregue (homologação) | PR #230–#232 |
| F3 cache, sprites, runtime ECharts | Entregue (homologação) | PR #233, #234 |
| F3 limpeza de artefatos antigos | Pendente | docs/198 |
| F4-A..D e conferência no Chrome | Entregue (homologação; conferência feita em 09/11) | PR #235–#237 |
| F5 busca nacional | Adiada → FN-032 | decisão 09/11 |
| F6-1 buffer pool 1 GB (VM 120) | Entregue (homologação) | docs/200 |
| F6 pools FPM, OPcache, gravação em lote UR4 | Pendente (aguarda uso real) | docs/199, 200 |
| X1 medição com 1000 usuários simultâneos | Pendente | nenhuma medição nesse padrão; depende de decisão §4 |
| X2 banco e consultas | Substituída em parte por F2–F4 | — |

---

## §3 Pendências por funcionalidade

O que falta para a entrega final de cada FN. Todas as FNs, inclusive as concluídas, precisam de `planejamento.md` e `casos-de-uso.md` (para as existentes, escritos a partir do código).

| FN | Pendências |
|---|---|
| 001 | Nenhuma (apps móveis → 010) |
| 002 | Validar B6 com cartão real em produção; implementar retenção do recibo B6 (30/90 dias, aprovada no docs/39) |
| 003 | Confirmar `umask` (PR #86) e encerramento do marcador da 1ª ativação; cópia externa dos backups da homologação; rever a reserva de 45 min da janela incluindo o backup final |
| 004 | Expurgo de 180 dias e sua política; revisão de privilégios das credenciais gerais; AU2 tela de consulta e exportação |
| 005 | Decisão D9; S1; AU3 |
| 006 | Tudo (A2) |
| 007 | X3 (inclui AP1-L, 93 rotas), X4, X5 separação física, X6; mutex distribuído |
| 008 | Confirmar artefato final do CI |
| 009 | Decidir se vira plano ou se é arquivada |
| 010 | Confirmar a manutenção do adiamento |
| 011 | Teste real de E6; decidir autocadastro (→ FN-030); verificar gov.br |
| 012 | Migrar as 8 rotas `perfil:` de `dashboard/*`; registrar respostas das decisões 1–6 do docs/21 |
| 013 | Nenhuma funcional; recuperação do Desenvolvedor segue manual |
| 014 | Backlog de perfis futuros; Secretário de Educação contextual (após 017); extras do vice-diretor (decisão) |
| 015 | Aceite visual de V2; destino da conta de aluno sem vínculo; perfis não suportados (secretaria) |
| 016 | Matriz de consulta de identidade; contrato de conciliação global (comprovação admitida); autorizar flag `IDENTIDADE_PROVISORIOS_ATIVOS` e backfill; risco do hash de CPF dependente de máscara |
| 017 | VI1-A..C inteiras |
| 018 | Decidir PR #194 (UR2-P); validação do responsável; produção |
| 019 | Limpeza de imagens órfãs; confirmar MP-040 configurações para DIR_ESC |
| 020 | Decisão 6.4 (login do aluno); confirmar vínculo usuário↔aluno |
| 021 | Nenhuma (índices e particionamento → 077/X1) |
| 022 | Tela de parâmetros pessoais; revisão de constraints (→ 027) |
| 023 | Contratos completos (→ 068) |
| 024 | Decisão de política (§4); aceite visual ou dispensa; complemento de nascimento, solicitação do professor, estatística anônima, limpeza de staging/outbox, ensaio de vazão no servidor — ou encerrar esses itens em favor de 025 |
| 025 | TR1, TE1, UX1 inteiros; não publicar o rótulo "Transferências" com as permissões antigas |
| 026 | MT1-A..C (intervalos de matrícula e contexto histórico) |
| 027 | PA1-A uso em turmas/disciplinas; PA1-B/C/D; revisão formal de T3 |
| 028 | T6-A..E |
| 029 | EI1-B..G; regras financeiras de adesão (→ 068) |
| 030 | Decisão sobre autocadastro, checkout e MP-029; preços (RN-015.1); revisão visual das telas SaaS; RN-016.3 |
| 031 | Conciliar 6 escolas sem INEP; 8 lacunas; backlog de UI; validação do responsável; produção |
| 032 | Retomar quando houver uso real; definir semântica (prefixo, palavras, UF) |
| 033 | A3, A4; confirmar ficha PDF do aluno |
| 034 | Importação por tela (XLSX), exportação, formato da planilha; modelos de e-mail da importação |
| 035 | A6 inteiro |
| 036 | Conferência autenticada; aposentar `*.desativar` e rotas toggle |
| 037 | IMG-N, IMG-P |
| 038 | Validação do responsável; produção |
| 039 | Priorização; P4.1–P4.4 |
| 040 | Confirmar stepper do MP-037 |
| 041 | Validação; produção |
| 042 | CP2 (após 057); confirmar A7 exportar/importar |
| 043 | Plano completo (inclui relatório por tema) |
| 044 | Plano completo |
| 045 | Teste com cartão impresso real |
| 046 | R3 limiares; registrar decisão 1 do docs/25 ("anulada" conta como erro); produção do hotfix de leituras 500 |
| 047 | Validação; produção |
| 048 | Plano |
| 049 | Relatório por tema (→ 043) |
| 050 | Integrar o WIP de `feat/tu1-pdf-relatorio` com paridade ao painel |
| 051 | Plano |
| 052 | Confirmar MP-040 e SEGES; variantes por perfil (P2.4) |
| 053 | P1.2, P1.4, P1.4.6, P2.4, P3.3, P3.4 |
| 054 | Nenhuma declarada |
| 055 | P6.1–P6.4 (após 056 e 039) |
| 056 | Aprovação formal da PoC C1; C2; C3; ativação dos cards reais |
| 057 | N1, N2, N3; N4 opcional |
| 058 | Mockup de gestão; CRUD sobre o módulo de agenda |
| 059 | C4; registro de aplicação e elegíveis |
| 060 | Testes reais de E5/E6; modelos E4 fases 2–4; DMARC |
| 061 | D13, D14; rotas e aceite versionado; cookies |
| 062 | Itens L2 em aberto; implementar retenções aprovadas; definir prazos abertos; criptografia de disco |
| 063 | Nenhuma |
| 064 | L4-D, L4-E; retenção própria de protocolos; versionar a proposta de guarda (docs/113 inexistente); produção |
| 065 | Plano de incidentes |
| 066 | Autorização do passo 6 |
| 067 | P1–P8; D10, D14; assunção do RIPD pelo controlador |
| 068 | F1–F3 |
| 069 | R1 |
| 070 | Registrar decisões 1–2 do docs/26 |
| 071 | Produção |
| 072 | YouTube; versão final da apresentação; condições de lançamento; reconciliar rodapé antes do próximo B5 |
| 073 | Nenhuma funcional (ver §6 sobre capturas) |
| 074 | TBL1-C; paginação opcional na API |
| 075 | TW2 smoke; TW3 restante; TW4–TW6; usuário coordenador com dois vínculos na homologação (docs/186, Incerto) |
| 076 | UI1-R em toda a aplicação; breadcrumb nas demais telas |
| 077 | Medição com 1000 usuários; F3 limpeza; F6 restante; custo de construção de escopos (ST1); AP1-E |

**Órfãos sinalizados pelos inventários** (sem plano ativo ou fora de qualquer fila): CP2 (042), EI1-B..G (029), AU2/AU3 (004/005), L4-D/E (064), T6 (028), N1–N4 (057), C1–C4 (056/059), importação de alunos pela tela (034), login do aluno (020), TU1-PDF (050), IMG-N/IMG-P (037), estrutura-alvo (009), as 7 frentes "Em breve" da home (043, 044, 051, 029), R3 dos limiares (046).

---

## §4 Decisões pendentes do responsável

1. **Aprovação deste plano e da priorização (§5).** Até lá, nada novo inicia. *Recomendação:* aprovar o §1–§3 como inventário e a Onda 0 de imediato; discutir as ondas seguintes.
2. **Gate de produção e fila acumulada.** O docs/102 diz "nada em produção antes de fechar L4"; o ADR-H1 (docs/127) diz "homologação obrigatória, produção por decisão expressa". A produção está em `a90fbf5` + hotfix; cerca de 100 PRs estão só na homologação (§7). *Recomendação:* revogar formalmente o gate L4 em favor do ADR-H1, mantendo as flags de L4 desligadas em produção; definir se haverá promoção em blocos e começar pela segurança (UR1–UR4, PR #194 e hotfix de leituras 500).
3. **Política T5 × docs/115 (Transferências).** T5 está ativo em produção com a política antiga (capacidades genéricas do Diretor Escolar), embora nenhuma cópia tenha sido iniciada; o docs/115 diz que T5 não atendia ao pedido original. *Recomendação:* declarar T5 substituída por FN-025; decidir se as flags `COPIAS_*` ficam desligadas em produção até TR1; não renomear para "Transferências" antes de TR1.
4. **Aceite visual de T5 (E1–E3).** *Recomendação:* dispensar o aceite da interface antiga e fazê-lo na UX1 (FN-025).
5. **Autocadastro e SaaS (MP-027/028/029, RN-016, docs/01 §30).** O código adotou contratação por contato e convites. *Recomendação:* declarar MP-028 e o checkout substituídos, MP-029 obsoleto, e registrar no docs/06 a contratação assistida; os preços (RN-015.1) entram em FN-068.
6. **Matrizes de permissão de ID1 e DC1.** ID1: `identidade.usuario.consultar` e `identidade.aluno.consultar`, extras do vice-diretor, comprovação admitida para associar cadastros. DC1: a matriz `disciplina.ver/gerir`, `lotacao.gerir` do docs/139 consta como pendente, mas DC1 foi entregue com `disciplina.gerir`. *Recomendação:* aprovar a matriz ID1 (destrava VI1, TR1/TE1 e L4-D) e registrar formalmente a matriz DC1 em vigor no docs/03.
7. **Escopo do módulo de agenda e PoC do CalendarJS (C1).** Os widgets usam CalendarJS sem aprovação formal da PoC; períodos letivos (FN-039) ainda são candidata. *Recomendação:* aprovar a PoC com os critérios do docs/28 (visão mensal, repetição) e priorizar FN-039 junto com FN-056, já que os painéis precisam de recorte por período.
8. **Identidades para a carga de 1000 usuários simultâneos.** Nenhuma medição F2–F6 seguiu o padrão. *Recomendação:* decidir a forma de obter 1000 identidades autenticadas na homologação (contas sintéticas geradas por comando, só na VM 120) e o gerador fora da VM.
9. **D9, D10, D13, D14 do docs/28.** D9 ("ver como" só leitura) bloqueia S1; D10 (código do responsável gerado pelo sistema) e D14 (revisão jurídica) bloqueiam o portal; D13 (dados da empresa) bloqueia L1. *Recomendação:* responder D13 e D14 primeiro (destravam L1 e P2); D10 já está decidido no texto do docs/28 e só precisa ser confirmado.
10. **Login do aluno (decisão 6.4 do docs/19).** *Recomendação:* decidir junto com o portal (FN-067), pois os dois tocam acesso individual de menores.
11. **LGPD operacional.** Autorizar o passo 6 de L6 (eliminar fotos); implementar as retenções aprovadas no docs/39; definir prazos ainda abertos (auditoria, fiscal, contas sem vínculo, CSV); política de retenção dos protocolos L4; expurgo de AU1; confirmar criptografia do disco D:. *Recomendação:* aprovar como um pacote único de retenção na Onda 3.
12. **Contrato de conciliação de identidade (ID1/L4).** Ligar a flag de provisórios e permitir backfill só depois do contrato. *Recomendação:* escrever o contrato como subfase de FN-016 na Onda 1.
13. **Frentes "Em breve" da home sem plano** (editor/versões, GII, marcações, formatos, TCT/TRI, integridade, escola autônoma). *Recomendação:* manter "Em breve" apenas para o que estiver priorizado; registrar as demais como candidatas (já feito: FN-043, 044, 051, 029).
14. **Condições de lançamento comercial** (docs/164) e regras financeiras de adesão da EI1. *Recomendação:* tratar no planejamento de FN-068.
15. **Estrutura-alvo de diretórios (FN-009).** *Recomendação:* manter como candidata até haver segundo motor (GII/TRI) que a justifique.
16. **PR #194 (UR2-P) aberto desde 05/11.** *Recomendação:* decidir junto com o item 2; se a produção não for promovida em breve, aplicar o hotfix de dependências isolado.

---

## §5 PROPOSTA de priorização em ondas

> **PROPOSTA.** A ordem abaixo não está aprovada. Critério: primeiro o que destrava decisões e saneia o tronco; depois os módulos transversais, antes das funcionalidades que dependem deles; depois o ciclo de vida de dados pessoais; por fim comercial, portal e frentes futuras. Uma funcionalidade por vez em execução, conforme a governança.

### Onda 0 — Saneamento e decisões (sem código de produto)
| Item | Por quê |
|---|---|
| Decisões §4 itens 1, 2, 3, 6, 7, 8 | Destravam todas as ondas seguintes e a fila de produção |
| `planejamento.md` e `casos-de-uso.md` das FNs da Onda 1 (056, 057, 039, 016, 026) | A governança proíbe executar sem os dois documentos aprovados |
| Registro de divergências do §6 nos documentos numerados (nota "superado pelo plano mestre") | Evita que agentes sigam documentos desatualizados |
| Se aprovado o item 2: promoção à produção em blocos (FN-003), começando por UR1–UR4, PR #194 e hotfix de leituras 500 | Segurança em produção está atrás da homologação desde 05/11 |

### Onda 1 — Módulos transversais e fundações de dados
| Ordem | FN | Por quê / dependências |
|---|---|---|
| 1 | 056 agenda-calendario (C1 formal → C2 → C3) | Transversal: painéis, visitas, marcação de provas, acompanhamento e VI1-B aguardam |
| 2 | 057 notificacoes (N1 → N2 → N3) | Transversal: CP2, VI1-C, C4, E4 fase 2, portal |
| 3 | 039 periodos-letivos (P4.1–P4.3) | Recorte por período da agenda e dos painéis; depende de C2 e usa DC1 |
| 4 | 016 identidade-pessoal (fechamento + contrato de conciliação) | Destrava VI1, TR1/TE1, PA1, L4-D |
| 5 | 026 matricula-enturmacao (MT1) | Destrava P1.4.6, P4.4, TE1, T6 |

Pode correr como trilha paralela de baixo risco, se o responsável aceitar: FN-065 (plano de incidentes, só documento) e FN-050 (integrar WIP do PDF da turma, sem dependência pendente).

### Onda 2 — Funcionalidades que esperavam os transversais
| FN | Depende de |
|---|---|
| 059 agendamento-aplicacao-provas (C4) | 056, 057 |
| 058 visitas-pedagogicas (MP-032) | 056 |
| 042 duplicacao-provas (CP2) | DC1 (feito), 057 |
| 017 convites-cpf (VI1) | 016, 056 (agendado), 057 |
| 053 paineis-turmas (P3.3, P1.4.6, P3.4) | 026, 059 |
| 055 acompanhamento-pedagogico | 056, 039 |
| 060 email-transacional (E4 fase 2, testes E5/E6) | 057 |

### Onda 3 — Ciclo de vida de dados pessoais e institucional
| FN | Depende de |
|---|---|
| 025 transferencias-institucionais (TR1 → TE1 → UX1) | 016, 026, 031 |
| 027 professor-independente (PA1) | 016, revisão de T3 |
| 064 pedidos-titulares (L4-D, L4-E) + pacote de retenção (062, 066, 004 expurgo) | 016, 026, decisão §4 item 11 |
| 034 importacao-exportacao-alunos | 026; e-mail (060) |
| 035 responsaveis-alunos (A6) | 016 |
| 033 estrutura-escolar (A3, A4) | — |

### Onda 4 — Comercial, portal e encerramento
| FN | Depende de |
|---|---|
| 061 termos e privacidade (L1) | D13, D14 |
| 068 financeiro → 069 selo | decisão comercial |
| 029 escola-independente (EI1) | 068 (EI1-E) |
| 028 encerramento-cliente (T6) | 064, 068 |
| 067 portal-responsaveis | 035, 061, 062, 069, D10 |
| 020 acesso-aluno | decisão §4 item 10; avaliar junto com 067 |
| 005 suporte-ver-como, 004 AU2, 006 painel técnico | D9, AU1 |

### Trilha contínua de qualidade (aplicada dentro das entregas, não como fila própria)
FN-075 Tailwind (TW3 restante a TW6), FN-076 carregamento regional, FN-074 paginação, FN-077 desempenho (medição com 1000 usuários antes de qualquer abertura comercial; F6 quando houver uso real), FN-007 escala. *Proposta:* cada entrega das ondas migra para Tailwind e carregamento regional as telas que tocar; uma janela própria para TW4–TW6 só depois da Onda 2.

### Futuro (candidatas sem prioridade)
FN-043 editor de provas e questões, FN-044 GII, FN-051 análises avançadas, FN-048 OMR externo, FN-010 app móvel, FN-009 estrutura de diretórios, FN-032 autocompletar do catálogo (adiada até uso real).

---

## §6 Conflitos e documentos desatualizados

| Onde | Divergência | O que vale agora |
|---|---|---|
| docs/13 tabela de status (parou em 19/06) | MP-030, 031, 033, 034 constam "Pendente", mas foram entregues; MP-028 e 035 foram substituídos | Este plano (§2) |
| docs/13 MP-001 | "Concluído", mas android/ios nunca foram criados | FN-001 Parcial no MP-001; FN-010 Adiada |
| docs/13 MP-030 | Descreve `PermissaoService`/`escola_perfil_permissoes`; o código usa `Support\Permissao` | docs/21 e o código |
| docs/13 MP-033 e docs/19 MP-038 | Mesma entrega com dois números | FN-045 |
| docs/14, 15, 16 | Cobrem só MP-001..022 / retrato do início | Histórico |
| CLAUDE.md, seção "Fluxo de Trabalho" | Manda ler o MP no docs/14 e as dependências no docs/13; a seção "Planejamento centralizado" do mesmo arquivo manda seguir o plano mestre | Plano mestre e governança. Resolvido em 09/11: a seção "Fluxo de Trabalho" foi reescrita para o tronco único |
| docs/06 RN-016 e docs/01 §30 | Autocadastro e checkout como planejados | Contratação por contato (PR #59); decisão §4 item 5 |
| docs/03 | Vínculo usuário↔aluno "pendente"; `DashboardController::aluno` já resolve por referência | Incerto até a decisão §4 item 10 |
| docs/10, 11 | Rotas parcialmente defasadas (pré-UR3/UR4) | Código |
| docs/22 | Usuários `@gabarito360.dev` e aluno "sem vínculo", anteriores a V1/A1 | Histórico |
| docs/23 | Aponta capturas em `docs/evidencias/layout-2026-10-30/`; a pasta não existe no worktree e não há imagens versionadas em `docs/` | Regra "sem prints": os 10 prints de `docs/evidencias/` foram removidos em 09/11 e a CI bloqueia novos (`scripts/verificar_imagens.py`); remover a referência ao reescrever FN-073 |
| docs/196 (F1) | Registra "8 capturas Chrome" | Confirmar que não estão versionadas nem em serviços |
| docs/28 §6 | Quadro parou em 02/11; previa docs 29, 30, 31, 36 que não existem (36 virou correções B6) | Este plano |
| docs/28 identificadores | Colisões L, R, P, F, V, A, C com outras séries | §1.2 |
| docs/32 | "Captura continua desligada" | Ativada em produção em 02/11 (docs/100) |
| docs/33, 34 | Numeração RL fora de ordem; docs/34 §4 descreve estado anterior a L3/L6 | L3/L6 entregues (FN-063, FN-066) |
| docs/40 (T1) e docs/63–65 (T3) | Identificação por espaço, CPF proibido no pessoal, capacidades da escola, nome T5 | Direção do docs/115, sujeita à decisão §4 itens 3 e 6 |
| docs/55–60 | "Sem deploy" | Publicado em produção em 02/11 (docs/100) |
| docs/92, 94 | Status "em revisão/rascunho" | Mesclados (docs/99) |
| docs/99 | "Publicado ainda em af4c6d1" | Histórico; produção em `a90fbf5` |
| docs/102, 106 | "Não publicar microfases de L4" | ADR-H1 para a homologação; produção pendente da decisão §4 item 2 |
| docs/24 × docs/25 | "Mesmo grupo de processos" × "já separados" | Separados desde 30/10 |
| docs/25 | "R2 em andamento" | R2 entregue (PR #41) |
| docs/117 | Cita docs/113 (proposta de guarda L4-D/E) | docs/113 não existe; arquivos de guarda fora do Git |
| docs/127–129 × docs/161–164 | "H1" com dois significados | FN-003 × FN-071/072 |
| docs/141–151 | Tabelas "em implementação/pendente" | PR #186 (DC1 entregue na homologação) |
| docs/163 × docs/165 | "EI1 após H1" × "UR1 → TU1 → EI1"; EI1 nunca retomada | Este plano (Onda 4) |
| docs/177–180 | Blocos "Decisão mais recente" empilhados e contraditórios; corpo do 177 (P2.9.3, P2.10.1) superado | Fim do docs/179 (PR #216) + editor do docs/191 (PR #227) |
| docs/179 | Tabelas com P2.6–P2.13 "pendente" | Mescladas (PR #211–#214) |
| docs/191 | EI4–EI6 "Pendente"; "EI" colide com escolas independentes | PR #227 mesclado; FN-054 |
| docs/199 | "Verificação visual no Chrome pendente" | Feita em 09/11 (decisão do responsável) |
| docs/200 | F5 em planejamento | Adiada em 09/11 (FN-032) |
| Inventários G4 × G5 (DC1) | G4: DC1-B..D "pendente/incerto"; G5: entregue via PR #186 | G5 (verificado no merge 3738d04) |
| Inventários G4 × G5 (ID1-D) | G4: "pendente/parcial?"; G5: ID1-D1 entregue (PR #170), backfill pendente | G5 |
| Inventário G6 (F4) | Conferência no Chrome pendente | Decisão de 09/11: feita, sem erros |
| Inventários G5 × G6 (TU1) | G5: aceite visual final incerto; G6: conferência autenticada pendente | Pendente (FN-036) |
| Inventários G1b × G6 (C1) | "Parcial/Incerto" × "Parcial" | Parcial, sem aprovação formal (FN-056) |
| docs/176 | Fila "TU1-PDF logo após IMG" | Ultrapassada; FN-050 Pendente |

---

### 6.1 Divergências encontradas ao documentar as funcionalidades (10/11/2026)

Registradas na seção 8 de cada `planejamento.md`. Viram decisões ou subfases quando o responsável aprovar os documentos.

| FN | Divergência |
|---|---|
| 011 | `POST /login` e `/api/v1/auth/login` sem limite de tentativas; "Manter conectado" sem efeito; senha mínima de 8 no código e de 6 no docs/04; sem integração gov.br |
| 012 | `secretario_educacao` aceito no cadastro de membro, mas não selecionável como vínculo; restam 8 rotas `perfil:` em `dashboard/*` |
| 019 | Sem configuração por escola para o Diretor Escolar; seções de integrações, calendário e notificações do mockup inexistentes |
| 020 | Painel do aluno funciona, mas o cadastro de membro `aluno` não cria a referência em `vinculo_alunos` (só o comando de conciliação) |
| 021–023 | Não há caminho na aplicação para criar espaço `pessoal` ou de `rede`; lacuna sem subfase |
| 025 | Faltava FN-057 (notificações) nas dependências (TR1-D, UX1-D) |
| 026 | Mudança de turma publicada como "Transferir aluno" (docs/115 reserva o termo); `PUT /alunos/{id}` com `turma_id` move sem motivo nem registro; espaço pessoal não grava `aluno_movimentacoes`; dependência de FN-039 não declarada |
| 030 | Webhook do Mercado Pago, `/checkout/*`, "Minha assinatura" e `POST /assinaturas/cancelar` (sem permissão) seguem ativos |
| 031 | Permissões do CI1-E em vigor não constam do docs/03 |
| 033 | `nucleo.gerir` concedível ao Diretor de Núcleo, mas `NucleoController::store` aceita só `admin_rede`; ficha PDF do aluno inexistente |
| 034 | Mockup prevê "Importar planilha"; só há importação por terminal |
| 035 | Campo legado `alunos.nome_responsavel` em texto livre, sem cifragem |
| 038 | Matriz do docs/139 (`disciplina.ver`, `lotacao.gerir`) não existe; só `disciplina.gerir`, atrás da flag `DISCIPLINAS_ATIVAS` |
| 049 | `relatorio.rede` do Secretário de Educação devolve 404 (`RelatorioConsolidadoController::rede` aceita só `admin_rede`) |
| 059 | "Provas do dia" usa data UTC; entre 21h e 24h de Brasília mostra o dia seguinte |
| 060 | Prévia dos modelos de e-mail (E4 fase 1) ausente; limite por e-mail/IP (E3) só por rota; modelo `boas-vindas-cadastro` sem remetente |
| 066 | Foto de aluno voltou a ser aceita (PF1-C/IM1, homologação), contrariando RL19 e o passo 6 do L6; autorização registrada no docs/135 |
| 068 | Tabelas `planos`, `assinaturas`, `pagamentos` e rotas de assinatura ativas (ver 030) |
| 072 | Link do YouTube já está no rodapé (homologação), embora conste pendente |
| 075 | Restam 1 `confirm(` e 4 `alert(` nativos em `correcao-*.js` |

---

## §7 Fila de produção (só na homologação, aguardando decisão)

Produção (CT 111): release `a90fbf5` (02/11/2026) + hotfix `83dd5ae` (PDF em lote, PR #156). Tudo abaixo foi mesclado e está só na homologação (VM 120), salvo indicação.

| Bloco | FN | PRs / referência |
|---|---|---|
| Segurança de URLs e sessão; dependências | 018 | #192, #193, #195, #197–#199; **#194 aberto** (UR2-P para a versão de produção) |
| Hotfix leituras 500 e revisão | 046 | #182–#185, #187 (produção expressamente não autorizada no docs/152) |
| Pedidos de titulares (flags ativas na homologação) | 064 | #139–#148 |
| Revisão UX de T5 | 024 | #149 |
| Duplicação de prova CP1 | 042 | #151 |
| Catálogo de instituições | 031 | #152–#158, #160 |
| Tabelas | 074 | #161, #186 |
| Autoria das provas | 041 | #162 |
| Perfis institucionais; imagem de perfil | 014, 019 | #163–#167 |
| Identidade (flags desligadas) | 016 | #168, #170 |
| Disciplinas e currículo | 038 | #171, #186 |
| OMR1–3 | 047 | #188–#190 |
| Marca e apresentação pública | 071, 072 | #191 (Nginx/home de produção pendente) |
| Estabilização e desempenho | 077 | #196, #200, #229–#237; F6-1 só na VM 120 |
| Carregamento e estados | 076 | #201, #202 |
| Organização da turma e imagens | 036, 037 | #203–#209 |
| Painéis e editor | 053, 054 | #210–#216, #218–#221, #226–#228 |
| Tailwind | 075 | #222–#225 |
| CI | 008 | #217 (sem impacto em produção) |
| Gate REL do docs/115, UR1-P | — | dependem da decisão §4 item 2 |
| Pendências operacionais antes do próximo B5 em produção | 003, 072 | reconciliar rodapé e helpers da home (docs/102) e o diff operacional do incidente do painel do professor (docs/operacao/incidente-painel-professor-2026-11-01.md) |

Nada desta lista vai para produção sem decisão expressa do responsável.
