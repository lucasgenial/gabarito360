# Catálogo de funcionalidades do Gabarito360

**Atualizado em:** 13/11/2026

## Para que serve este catálogo

Este catálogo é a **lista oficial** das funcionalidades do Gabarito360. Cada funcionalidade aparece aqui uma única vez.

Use este catálogo para:

- Encontrar uma funcionalidade e os seus documentos.
- Saber o estado atual de cada funcionalidade.
- Traduzir um identificador antigo (por exemplo, MP-032 ou T5) para o identificador atual (FN-NNN).
- Registrar uma funcionalidade nova.

Este catálogo **não** define a ordem de execução. A ordem está no [plano mestre](../planejamento/00-plano-mestre.md), que é o orquestrador da execução. As regras estão na [governança](../planejamento/01-governanca.md).

## Documentos de cada funcionalidade

Cada funcionalidade tem uma pasta `docs/funcionalidades/NNN-slug/` com dois documentos:

| Documento | Conteúdo |
|---|---|
| `planejamento.md` | Objetivo, escopo, dependências, fases, subfases com evidência e riscos |
| `casos-de-uso.md` | Atores, permissões, fluxos e critérios de aceite verificáveis |

Os modelos dos dois documentos estão em [`_modelos/`](_modelos/).

Uma funcionalidade só entra em execução depois que o responsável aprova os dois documentos.

## Colunas da tabela

| Coluna | Significado |
|---|---|
| FN | Identificador único e permanente. Um número nunca é reutilizado. |
| Nome | Nome curto da funcionalidade. |
| Identificador antigo (documento) | Códigos usados antes deste catálogo e o documento de origem. Use para ler documentos históricos. |
| Domínio | Área do sistema. |
| Implementação | Quanto da funcionalidade existe no código. |
| Etapa | Onde a funcionalidade está no ciclo de trabalho. |
| Documentos | Links para o planejamento e os casos de uso. |

**Valores de Implementação:**

| Valor | Significado |
|---|---|
| Concluída | Entregue e validada. |
| Em homologação | Publicada só na homologação, sem validação final. |
| Parcial | Uma parte existe no código. Outra parte está pendente. |
| Pendente | Nada existe no código. |
| Adiada | Retirada da fila por decisão do responsável. |
| Substituída | Outra funcionalidade ocupa o lugar desta. |
| Candidata | Ideia registrada, sem prioridade definida. |

**Valores de Etapa:**

| Valor | Significado |
|---|---|
| Candidata | Registrada no catálogo. Aguarda a priorização do responsável. |
| Priorizada | Tem posição definida no plano mestre. |
| Em planejamento | Os documentos estão em escrita ou aguardam aprovação. |
| Planejamento aprovado | O responsável aprovou os documentos. A execução pode começar. |
| Em execução | Uma subfase está em desenvolvimento. |
| Em homologação | A entrega está publicada só na homologação. |
| Concluída | A entrega está validada. A produção depende de decisão expressa. |
| Adiada ou Substituída | Fora da fila. O motivo está no planejamento. |

Em 10/11/2026, todos os documentos estão em rascunho e aguardam aprovação. Por isso, as funcionalidades com trabalho pendente estão na etapa "Em planejamento".

## Como registrar uma funcionalidade nova

Faça estes passos com o agente `gerente-projetos`:

1. Verifique se a funcionalidade já existe neste catálogo. Se existe, use a funcionalidade existente.
2. Use o próximo número livre de FN. A numeração continua a partir de FN-083.
3. Adicione uma linha nesta tabela. Use Implementação "Candidata" e Etapa "Candidata".
4. Crie a pasta `NNN-slug/` com os dois documentos, a partir dos modelos.
5. No `planejamento.md`, registre a origem (por exemplo, "surgiu na entrega dos painéis").
6. Peça a priorização ao responsável. O plano mestre recebe a funcionalidade só depois dessa decisão.

**Atenção:** a CI verifica a consistência deste catálogo. Uma pasta sem linha na tabela, uma linha sem pasta ou uma etapa diferente do `planejamento.md` falha a verificação.

## Funcionalidades

| FN | Nome | Identificador antigo (documento) | Domínio | Implementação | Etapa | Documentos |
|---|---|---|---|---|---|---|
| FN-001 | Fundação do monorepo, banco e bases API/WEB | MP-001..005 (docs/13) | Plataforma e operação | Concluída | Concluída | [planejamento](001-fundacao-monorepo/planejamento.md) · [casos de uso](001-fundacao-monorepo/casos-de-uso.md) |
| FN-002 | Base de operação (backup, Redis, filas, leitura pela fila) | B1–B4, B6 (docs/28) | Plataforma e operação | Concluída | Concluída | [planejamento](002-base-operacao/planejamento.md) · [casos de uso](002-base-operacao/casos-de-uso.md) |
| FN-003 | Publicação, deploy e homologação isolada | B5 e extensões (`--manter-manutencao`, `--backup-final`, `umask`), procedimentos T2 (docs/49–54), publicação por fase (docs/99–100); H1 homologação isolada (docs/125–130, ADR-H1) | Plataforma e operação | Concluída | Concluída | [planejamento](003-publicacao-e-homologacao/planejamento.md) · [casos de uso](003-publicacao-e-homologacao/casos-de-uso.md) |
| FN-004 | Auditoria de requisições, expurgo e tela de consulta | AU1, expurgo, AU2 | Plataforma e operação | Parcial | Em planejamento | [planejamento](004-auditoria/planejamento.md) · [casos de uso](004-auditoria/casos-de-uso.md) |
| FN-005 | "Ver como" e atendimento auditado | AU3, S1 | Plataforma e operação | Pendente | Em planejamento | [planejamento](005-suporte-ver-como/planejamento.md) · [casos de uso](005-suporte-ver-como/casos-de-uso.md) |
| FN-006 | Painel técnico | A2 painel técnico (docs/28) — não confundir com T5-A2 | Plataforma e operação | Pendente | Em planejamento | [planejamento](006-painel-tecnico/planejamento.md) · [casos de uso](006-painel-tecnico/casos-de-uso.md) |
| FN-007 | Limites, armazenamento externo, separação e observabilidade | X3–X6; AP1-L; separação física da API | Plataforma e operação | Pendente | Em planejamento | [planejamento](007-escala-infraestrutura/planejamento.md) · [casos de uso](007-escala-infraestrutura/casos-de-uso.md) |
| FN-008 | Integração contínua (MariaDB) | CI do MariaDB (docs/181) | Plataforma e operação | Concluída | Concluída | [planejamento](008-ci-integracao/planejamento.md) · [casos de uso](008-ci-integracao/casos-de-uso.md) |
| FN-009 | Estrutura-alvo de diretórios | Estrutura-alvo de diretórios (docs/168) | Plataforma e operação | Candidata | Candidata | [planejamento](009-estrutura-diretorios-alvo/planejamento.md) · [casos de uso](009-estrutura-diretorios-alvo/casos-de-uso.md) |
| FN-010 | Aplicativos Android e iOS | MP-023 (Android) e iOS | Plataforma e operação | Adiada | Adiada | [planejamento](010-app-mobile/planejamento.md) · [casos de uso](010-app-mobile/casos-de-uso.md) |
| FN-011 | Autenticação e conta | MP-012; RF-001 | Identidade e acesso | Parcial | Em planejamento | [planejamento](011-autenticacao-conta/planejamento.md) · [casos de uso](011-autenticacao-conta/casos-de-uso.md) |
| FN-012 | Motor de permissões, equipe e menus | MP-013, 018, 026, 030, 031, 036; etapas 0–7 do docs/21 | Identidade e acesso | Concluída | Concluída | [planejamento](012-permissoes-motor/planejamento.md) · [casos de uso](012-permissoes-motor/casos-de-uso.md) |
| FN-013 | Acessos internos (Desenvolvedor, Suporte, Financeiro) | A1-A, A1-B1, A1-B2 | Identidade e acesso | Concluída | Concluída | [planejamento](013-acessos-internos/planejamento.md) · [casos de uso](013-acessos-internos/casos-de-uso.md) |
| FN-014 | Perfis institucionais (secretário escolar, vice-diretor e outros) | A1-C (secretário escolar); PF1 | Identidade e acesso | Concluída | Concluída | [planejamento](014-perfis-institucionais/planejamento.md) · [casos de uso](014-perfis-institucionais/casos-de-uso.md) |
| FN-015 | Vínculos por pessoa e seleção de vínculo | V1, V2 vínculos (docs/28, 55–62) | Identidade e acesso | Concluída | Concluída | [planejamento](015-vinculos-contexto/planejamento.md) · [casos de uso](015-vinculos-contexto/casos-de-uso.md) |
| FN-016 | Identidade pessoal (ID1) | ID1 (inclui ID1-D1 provisórios) | Identidade e acesso | Parcial | Em planejamento | [planejamento](016-identidade-pessoal/planejamento.md) · [casos de uso](016-identidade-pessoal/casos-de-uso.md) |
| FN-017 | Vínculos e convites CPF-primeiro (VI1) | VI1 | Identidade e acesso | Pendente | Em planejamento | [planejamento](017-convites-cpf/planejamento.md) · [casos de uso](017-convites-cpf/casos-de-uso.md) |
| FN-018 | Segurança de URLs, sessão e dependências (UR1–UR4) | UR1–UR4, UR2, UR2-P | Identidade e acesso | Em homologação | Em homologação | [planejamento](018-seguranca-urls-sessao/planejamento.md) · [casos de uso](018-seguranca-urls-sessao/casos-de-uso.md) |
| FN-019 | Meu perfil, configurações e imagem de perfil | MP-024; IM1 | Identidade e acesso | Concluída | Concluída | [planejamento](019-perfil-configuracoes/planejamento.md) · [casos de uso](019-perfil-configuracoes/casos-de-uso.md) |
| FN-020 | Acesso próprio do aluno | MP-041; decisão 6.4 do docs/19 | Identidade e acesso | Pendente | Em planejamento | [planejamento](020-acesso-aluno/planejamento.md) · [casos de uso](020-acesso-aluno/casos-de-uso.md) |
| FN-021 | Clientes e espaços (T1/T2) | T1, T2 | Clientes, espaços e vínculos | Concluída | Concluída | [planejamento](021-clientes-espacos/planejamento.md) · [casos de uso](021-clientes-espacos/casos-de-uso.md) |
| FN-022 | Espaço pessoal e professor autônomo (T3) | T3 | Clientes, espaços e vínculos | Concluída | Concluída | [planejamento](022-espaco-pessoal/planejamento.md) · [casos de uso](022-espaco-pessoal/casos-de-uso.md) |
| FN-023 | Cadastro de clientes (T4) | T4 | Clientes, espaços e vínculos | Concluída | Concluída | [planejamento](023-cadastro-clientes/planejamento.md) · [casos de uso](023-cadastro-clientes/casos-de-uso.md) |
| FN-024 | Cópias entre espaços (T5) | T5 (A–E) e revisão UX (PR #149) | Clientes, espaços e vínculos | Substituída | Substituída | [planejamento](024-copias-entre-espacos/planejamento.md) · [casos de uso](024-copias-entre-espacos/casos-de-uso.md) |
| FN-025 | Transferências institucionais (TR1, TE1, UX1) | TR1, TE1, UX1 | Clientes, espaços e vínculos | Pendente | Em planejamento | [planejamento](025-transferencias-institucionais/planejamento.md) · [casos de uso](025-transferencias-institucionais/casos-de-uso.md) |
| FN-026 | Matrícula e enturmação (MT1) | MP-035b (status de matrícula); A5 (importar/exportar): troca de turma com histórico; MT1 | Clientes, espaços e vínculos | Parcial | Em planejamento | [planejamento](026-matricula-enturmacao/planejamento.md) · [casos de uso](026-matricula-enturmacao/casos-de-uso.md) |
| FN-027 | Professor independente (PA1) | PA1 | Clientes, espaços e vínculos | Parcial | Em planejamento | [planejamento](027-professor-independente/planejamento.md) · [casos de uso](027-professor-independente/casos-de-uso.md) |
| FN-028 | Encerramento de cliente (T6) | T6 (A–E) | Clientes, espaços e vínculos | Pendente | Em planejamento | [planejamento](028-encerramento-cliente/planejamento.md) · [casos de uso](028-encerramento-cliente/casos-de-uso.md) |
| FN-029 | Escola independente / autônoma (EI1) | EI1 escolas independentes (docs/163) | Clientes, espaços e vínculos | Pendente | Em planejamento | [planejamento](029-escola-independente/planejamento.md) · [casos de uso](029-escola-independente/casos-de-uso.md) |
| FN-030 | Autocadastro, planos e assinatura | MP-025, 027, 028, 029; landing (só a rota) | Clientes, espaços e vínculos | Substituída | Substituída | [planejamento](030-saas-autocadastro-assinatura/planejamento.md) · [casos de uso](030-saas-autocadastro-assinatura/casos-de-uso.md) |
| FN-031 | Catálogo nacional de instituições (CI1) | CI1 | Clientes, espaços e vínculos | Em homologação | Em homologação | [planejamento](031-catalogo-instituicoes/planejamento.md) · [casos de uso](031-catalogo-instituicoes/casos-de-uso.md) |
| FN-032 | Busca do catálogo por autocompletar | F5 busca nacional do catálogo | Clientes, espaços e vínculos | Adiada | Adiada | [planejamento](032-busca-catalogo-autocompletar/planejamento.md) · [casos de uso](032-busca-catalogo-autocompletar/casos-de-uso.md) |
| FN-033 | Núcleos, escolas, turmas e alunos | MP-014..017, MP-035a (foto), MP-039; A3, A4 (docs/28) — não confundir com T5-A3 | Cadastros escolares | Parcial | Em planejamento | [planejamento](033-estrutura-escolar/planejamento.md) · [casos de uso](033-estrutura-escolar/casos-de-uso.md) |
| FN-034 | Importação e exportação de alunos | MP-016 (importação por planilha); A5 (importar/exportar) | Cadastros escolares | Pendente | Em planejamento | [planejamento](034-importacao-exportacao-alunos/planejamento.md) · [casos de uso](034-importacao-exportacao-alunos/casos-de-uso.md) |
| FN-035 | Dados dos responsáveis | A6 | Cadastros escolares | Pendente | Em planejamento | [planejamento](035-responsaveis-alunos/planejamento.md) · [casos de uso](035-responsaveis-alunos/casos-de-uso.md) |
| FN-036 | Organização da turma (TU1) | R1–R6 e TU1-R3 (docs/tu1-*); TU1 (docs/175, tu1-revisao-fluxos, tu1-r3) | Cadastros escolares | Em homologação | Em homologação | [planejamento](036-organizacao-turma/planejamento.md) · [casos de uso](036-organizacao-turma/casos-de-uso.md) |
| FN-037 | Imagens de turmas e educadores | TU-IMG, IMG-E/L/N/P | Cadastros escolares | Parcial | Em planejamento | [planejamento](037-imagens-turmas-educadores/planejamento.md) · [casos de uso](037-imagens-turmas-educadores/casos-de-uso.md) |
| FN-038 | Disciplinas, áreas e currículo das provas (DC1) | DC1 | Cadastros escolares | Em homologação | Em homologação | [planejamento](038-disciplinas-curriculo/planejamento.md) · [casos de uso](038-disciplinas-curriculo/casos-de-uso.md) |
| FN-039 | Períodos letivos | P4 painéis (períodos); "PL1"; M3 "Tempo escolar e agenda" (docs/177) | Cadastros escolares | Candidata | Candidata | [planejamento](039-periodos-letivos/planejamento.md) · [casos de uso](039-periodos-letivos/casos-de-uso.md) |
| FN-040 | Provas e gabaritos | MP-019, 020, 037 | Provas e gabaritos | Concluída | Concluída | [planejamento](040-provas-gabaritos/planejamento.md) · [casos de uso](040-provas-gabaritos/casos-de-uso.md) |
| FN-041 | Autoria das provas e "Meu espaço" | Autoria das provas e "Meu espaço" (docs/132) | Provas e gabaritos | Em homologação | Em homologação | [planejamento](041-autoria-provas/planejamento.md) · [casos de uso](041-autoria-provas/casos-de-uso.md) |
| FN-042 | Duplicação de provas (CP1, CP2) | A7; CP1, CP2 | Provas e gabaritos | Parcial | Em planejamento | [planejamento](042-duplicacao-provas/planejamento.md) · [casos de uso](042-duplicacao-provas/casos-de-uso.md) |
| FN-043 | Editor de provas e banco de questões | — | Provas e gabaritos | Candidata | Candidata | [planejamento](043-editor-provas-questoes/planejamento.md) · [casos de uso](043-editor-provas-questoes/casos-de-uso.md) |
| FN-044 | Acervo inteligente (GII) | P7.1 painéis; GII | Provas e gabaritos | Candidata | Candidata | [planejamento](044-acervo-gii/planejamento.md) · [casos de uso](044-acervo-gii/casos-de-uso.md) |
| FN-045 | Motor OMR e acompanhamento da correção | MP-021, MP-033 e MP-038 (mesma entrega) | Correção e OMR | Concluída | Concluída | [planejamento](045-omr-correcao/planejamento.md) · [casos de uso](045-omr-correcao/casos-de-uso.md) |
| FN-046 | Fotos da correção e revisão de leituras | R1–R3 fotos leves e revisão (docs/25); ações 1–4 do docs/24; hotfixes docs/152–157 | Correção e OMR | Parcial | Em planejamento | [planejamento](046-revisao-leituras/planejamento.md) · [casos de uso](046-revisao-leituras/casos-de-uso.md) |
| FN-047 | Qualidade, tentativas, resultado e documentos verificáveis (OMR1–3) | OMR1–OMR3 | Correção e OMR | Em homologação | Em homologação | [planejamento](047-omr-qualidade-resultado/planejamento.md) · [casos de uso](047-omr-qualidade-resultado/casos-de-uso.md) |
| FN-048 | Driver OMR externo | Driver OMR externo (fase 6 pós-MVP) | Correção e OMR | Candidata | Candidata | [planejamento](048-omr-driver-externo/planejamento.md) · [casos de uso](048-omr-driver-externo/casos-de-uso.md) |
| FN-049 | Resultados e relatórios consolidados | MP-022, MP-034; hotfix PDF em lote (docs/123) | Resultados e relatórios | Concluída | Concluída | [planejamento](049-relatorios-resultados/planejamento.md) · [casos de uso](049-relatorios-resultados/casos-de-uso.md) |
| FN-050 | Relatório PDF da turma | P8.1 painéis; TU1-PDF | Resultados e relatórios | Pendente | Em planejamento | [planejamento](050-relatorio-turma-pdf/planejamento.md) · [casos de uso](050-relatorio-turma-pdf/casos-de-uso.md) |
| FN-051 | TCT/TRI, integridade e sínteses | P7.2–P7.4 painéis (TCT/TRI, integridade, sínteses) | Resultados e relatórios | Candidata | Candidata | [planejamento](051-analises-avancadas/planejamento.md) · [casos de uso](051-analises-avancadas/casos-de-uso.md) |
| FN-052 | Painéis por perfil | MP-006..011, MP-040 (docs/13, docs/19); SEGES | Painéis | Concluída | Concluída | [planejamento](052-dashboards-perfil/planejamento.md) · [casos de uso](052-dashboards-perfil/casos-de-uso.md) |
| FN-053 | Painéis de turmas e da turma | P0–P3, P8.2–P8.4 painéis (docs/177) | Painéis | Parcial | Em planejamento | [planejamento](053-paineis-turmas/planejamento.md) · [casos de uso](053-paineis-turmas/casos-de-uso.md) |
| FN-054 | Personalização e editor dos painéis | EI1–EI6 etapas do editor (docs/191); FT1–FT3 | Painéis | Concluída | Concluída | [planejamento](054-paineis-editor/planejamento.md) · [casos de uso](054-paineis-editor/casos-de-uso.md) |
| FN-055 | Acompanhamento pedagógico | P6 painéis | Painéis | Pendente | Em planejamento | [planejamento](055-acompanhamento-pedagogico/planejamento.md) · [casos de uso](055-acompanhamento-pedagogico/casos-de-uso.md) |
| FN-056 | Agenda e calendário (módulo transversal) | C1–C3 calendário (docs/28); P5 dos painéis; M3 "Tempo escolar e agenda" (docs/177) | Agenda e notificações | Pendente | Em planejamento | [planejamento](056-agenda-calendario/planejamento.md) · [casos de uso](056-agenda-calendario/casos-de-uso.md) |
| FN-057 | Notificações (módulo transversal) | N1–N4; P5 painéis | Agenda e notificações | Pendente | Em planejamento | [planejamento](057-notificacoes/planejamento.md) · [casos de uso](057-notificacoes/casos-de-uso.md) |
| FN-058 | Visitas pedagógicas | MP-032 | Agenda e notificações | Pendente | Em planejamento | [planejamento](058-visitas-pedagogicas/planejamento.md) · [casos de uso](058-visitas-pedagogicas/casos-de-uso.md) |
| FN-059 | Marcação e aplicação de provas | C4 (provas no calendário e lembretes) | Agenda e notificações | Pendente | Em planejamento | [planejamento](059-agendamento-aplicacao-provas/planejamento.md) · [casos de uso](059-agendamento-aplicacao-provas/casos-de-uso.md) |
| FN-060 | E-mail transacional e Fale Conosco | E1–E7 (docs/28) | Comunicação e e-mail | Parcial | Em planejamento | [planejamento](060-email-transacional/planejamento.md) · [casos de uso](060-email-transacional/casos-de-uso.md) |
| FN-061 | Termos, privacidade e aceite | L1 LGPD (docs/28); RL1–RL19 (docs/33) | LGPD e portal | Parcial | Em planejamento | [planejamento](061-lgpd-termos-privacidade/planejamento.md) · [casos de uso](061-lgpd-termos-privacidade/casos-de-uso.md) |
| FN-062 | Inventário de dados e retenção | L2 LGPD (docs/28); RL1–RL19 (docs/33) | LGPD e portal | Parcial | Em planejamento | [planejamento](062-lgpd-inventario-retencao/planejamento.md) · [casos de uso](062-lgpd-inventario-retencao/casos-de-uso.md) |
| FN-063 | Proteção dos dados (cifragem) | L3 LGPD (docs/28); RL1–RL19 (docs/33) | LGPD e portal | Concluída | Concluída | [planejamento](063-lgpd-protecao-dados/planejamento.md) · [casos de uso](063-lgpd-protecao-dados/casos-de-uso.md) |
| FN-064 | Pedidos dos titulares (L4) | L4 LGPD (docs/28); RL1–RL19 (docs/33) | LGPD e portal | Parcial | Em planejamento | [planejamento](064-pedidos-titulares/planejamento.md) · [casos de uso](064-pedidos-titulares/casos-de-uso.md) |
| FN-065 | Plano de incidentes | L5 LGPD (docs/28); RL1–RL19 (docs/33) | LGPD e portal | Pendente | Em planejamento | [planejamento](065-plano-incidentes/planejamento.md) · [casos de uso](065-plano-incidentes/casos-de-uso.md) |
| FN-066 | Avatar no lugar de foto de aluno | L6 LGPD (docs/28); RL1–RL19 (docs/33) | LGPD e portal | Parcial | Em planejamento | [planejamento](066-avatares-alunos/planejamento.md) · [casos de uso](066-avatares-alunos/casos-de-uso.md) |
| FN-067 | Portal dos responsáveis | P1–P8 portal dos responsáveis (docs/28) | LGPD e portal | Pendente | Em planejamento | [planejamento](067-portal-responsaveis/planejamento.md) · [casos de uso](067-portal-responsaveis/casos-de-uso.md) |
| FN-068 | Financeiro (experiência limitada, integração e legado) | F1–F3 financeiro (docs/28); FIN | Financeiro | Pendente | Em planejamento | [planejamento](068-financeiro/planejamento.md) · [casos de uso](068-financeiro/casos-de-uso.md) |
| FN-069 | Selo dos documentos | R1 selo dos documentos (docs/28) | Financeiro | Pendente | Em planejamento | [planejamento](069-selo-documentos/planejamento.md) · [casos de uso](069-selo-documentos/casos-de-uso.md) |
| FN-070 | Identidade visual e gráficos | V1–V5 base visual (docs/26); G1–G3 gráficos (docs/27) | Público e marca | Concluída | Concluída | [planejamento](070-identidade-visual/planejamento.md) · [casos de uso](070-identidade-visual/casos-de-uso.md) |
| FN-071 | Marca oficial e cabeçalhos de documentos | H1 identidade visual oficial (docs/161–162, A–E) | Público e marca | Em homologação | Em homologação | [planejamento](071-marca-documentos/planejamento.md) · [casos de uso](071-marca-documentos/casos-de-uso.md) |
| FN-072 | Site público e apresentação | H1-P apresentação pública, etapas P1–P5 (docs/164); E8 (páginas públicas); home estática (docs/101–102) | Público e marca | Em homologação | Em homologação | [planejamento](072-site-publico/planejamento.md) · [casos de uso](072-site-publico/casos-de-uso.md) |
| FN-073 | Layout responsivo | L0–L8 layout (docs/23) | Qualidade transversal | Concluída | Concluída | [planejamento](073-layout-responsivo/planejamento.md) · [casos de uso](073-layout-responsivo/casos-de-uso.md) |
| FN-074 | Padrão de tabelas e listas | TBL1, TBL2 | Qualidade transversal | Em homologação | Em homologação | [planejamento](074-tabelas-padrao/planejamento.md) · [casos de uso](074-tabelas-padrao/casos-de-uso.md) |
| FN-075 | Migração para Tailwind e UX transversal | TW1–TW7; UX-D, UX-T; ações SMALL (docs/184) | Qualidade transversal | Parcial | Em planejamento | [planejamento](075-tailwind-ui/planejamento.md) · [casos de uso](075-tailwind-ui/casos-de-uso.md) |
| FN-076 | Carregamento, estados de erro e navegação | UI1, UI1-R; carregamento progressivo e breadcrumb (OMR2-C2) | Qualidade transversal | Parcial | Em planejamento | [planejamento](076-carregamento-estados/planejamento.md) · [casos de uso](076-carregamento-estados/casos-de-uso.md) |
| FN-077 | Desempenho e carga | F0–F4, F6, PERF-01..15, FUND-01..10 desempenho (docs/192–200); X1, X2; ST1, AP1-A..E | Qualidade transversal | Parcial | Em planejamento | [planejamento](077-desempenho/planejamento.md) · [casos de uso](077-desempenho/casos-de-uso.md) |
| FN-078 | Governança executável: princípios verificados e propagados automaticamente | — (nova, ADR-0053) | Qualidade transversal | Parcial | Em execução | [planejamento](078-governanca-executavel/planejamento.md) · [casos de uso](078-governanca-executavel/casos-de-uso.md) |
| FN-079 | Leitor360: motor de leitura de provas | — (nova, 11/11/2026) | Correção e OMR | Pendente | Planejamento aprovado | [planejamento](079-leitor360/planejamento.md) · [casos de uso](079-leitor360/casos-de-uso.md) |
| FN-080 | Escriba: IA local de transcrição de respostas manuscritas | — (nova, 11/11/2026) | Correção e OMR | Pendente | Planejamento aprovado | [planejamento](080-ia-local-transcricao/planejamento.md) · [casos de uso](080-ia-local-transcricao/casos-de-uso.md) |
| FN-081 | Créditos de correção (consumo no modo servidor) | — (nova, 11/11/2026) | Financeiro | Candidata | Candidata | [planejamento](081-creditos-correcao/planejamento.md) · [casos de uso](081-creditos-correcao/casos-de-uso.md) |
| FN-082 | Família de ícones G360 | — (nova, ADR-0063) | Qualidade transversal | Parcial | Em execução | [planejamento](082-familia-icones-g360/planejamento.md) · [casos de uso](082-familia-icones-g360/casos-de-uso.md) |
