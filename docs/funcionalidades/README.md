# Funcionalidades do Gabarito360 — índice

**Atualizado em:** 09/11/2026
**Fonte de prioridade e de status:** [plano mestre](../planejamento/00-plano-mestre.md) (PROPOSTA, aguardando aprovação da priorização). Regras: [governança](../planejamento/01-governanca.md).

Cada funcionalidade tem um identificador canônico **FN-NNN** e uma pasta `docs/funcionalidades/NNN-slug/` com dois documentos, escritos nos [modelos](_modelos/):

- `planejamento.md` — objetivo, escopo, dependências, fases e subfases com evidência, riscos.
- `casos-de-uso.md` — atores, permissões, fluxos e critérios verificáveis.

As pastas ainda não foram criadas. Nenhuma funcionalidade pode entrar em execução sem os dois documentos aprovados.

**Funcionalidades existentes** (Concluída, Em homologação, Parcial, Substituída) terão os documentos escritos **a partir do código e das evidências** (PRs, docs numerados), descrevendo o comportamento atual e marcando com ⚠ as diferenças em relação ao planejado. **Funcionalidades novas** (Pendente, Candidata, Adiada) terão os documentos escritos antes da execução, na ordem das ondas do plano mestre.

O estado abaixo repete o do plano mestre; em divergência, vale o plano mestre. A equivalência com os identificadores antigos (MP-xxx, T5, P3.2, F4...) está no §1 do plano mestre.

| FN | Slug | Nome | Domínio | Estado | Pasta prevista | planejamento.md | casos-de-uso.md |
|---|---|---|---|---|---|---|---|
| FN-001 | fundacao-monorepo | Fundação do monorepo, banco e bases API/WEB | Plataforma e operação | Concluída | `001-fundacao-monorepo/` | a criar | a criar |
| FN-002 | base-operacao | Base de operação (backup, Redis, filas, leitura pela fila) | Plataforma e operação | Concluída | `002-base-operacao/` | a criar | a criar |
| FN-003 | publicacao-e-homologacao | Publicação, deploy e homologação isolada | Plataforma e operação | Concluída | `003-publicacao-e-homologacao/` | a criar | a criar |
| FN-004 | auditoria | Auditoria de requisições, expurgo e tela de consulta | Plataforma e operação | Parcial | `004-auditoria/` | a criar | a criar |
| FN-005 | suporte-ver-como | "Ver como" e atendimento auditado | Plataforma e operação | Pendente | `005-suporte-ver-como/` | a criar | a criar |
| FN-006 | painel-tecnico | Painel técnico | Plataforma e operação | Pendente | `006-painel-tecnico/` | a criar | a criar |
| FN-007 | escala-infraestrutura | Limites, armazenamento externo, separação e observabilidade | Plataforma e operação | Pendente | `007-escala-infraestrutura/` | a criar | a criar |
| FN-008 | ci-integracao | Integração contínua (MariaDB) | Plataforma e operação | Concluída | `008-ci-integracao/` | a criar | a criar |
| FN-009 | estrutura-diretorios-alvo | Estrutura-alvo de diretórios | Plataforma e operação | Candidata | `009-estrutura-diretorios-alvo/` | a criar | a criar |
| FN-010 | app-mobile | Aplicativos Android e iOS | Plataforma e operação | Adiada | `010-app-mobile/` | a criar | a criar |
| FN-011 | autenticacao-conta | Autenticação e conta | Identidade e acesso | Parcial | `011-autenticacao-conta/` | a criar | a criar |
| FN-012 | permissoes-motor | Motor de permissões, equipe e menus | Identidade e acesso | Concluída | `012-permissoes-motor/` | a criar | a criar |
| FN-013 | acessos-internos | Acessos internos (Desenvolvedor, Suporte, Financeiro) | Identidade e acesso | Concluída | `013-acessos-internos/` | a criar | a criar |
| FN-014 | perfis-institucionais | Perfis institucionais (secretário escolar, vice-diretor e outros) | Identidade e acesso | Concluída | `014-perfis-institucionais/` | a criar | a criar |
| FN-015 | vinculos-contexto | Vínculos por pessoa e seleção de vínculo | Identidade e acesso | Concluída | `015-vinculos-contexto/` | a criar | a criar |
| FN-016 | identidade-pessoal | Identidade pessoal (ID1) | Identidade e acesso | Parcial | `016-identidade-pessoal/` | a criar | a criar |
| FN-017 | convites-cpf | Vínculos e convites CPF-primeiro (VI1) | Identidade e acesso | Pendente | `017-convites-cpf/` | a criar | a criar |
| FN-018 | seguranca-urls-sessao | Segurança de URLs, sessão e dependências (UR1–UR4) | Identidade e acesso | Em homologação | `018-seguranca-urls-sessao/` | a criar | a criar |
| FN-019 | perfil-configuracoes | Meu perfil, configurações e imagem de perfil | Identidade e acesso | Concluída | `019-perfil-configuracoes/` | a criar | a criar |
| FN-020 | acesso-aluno | Acesso próprio do aluno | Identidade e acesso | Pendente | `020-acesso-aluno/` | a criar | a criar |
| FN-021 | clientes-espacos | Clientes e espaços (T1/T2) | Clientes, espaços e vínculos | Concluída | `021-clientes-espacos/` | a criar | a criar |
| FN-022 | espaco-pessoal | Espaço pessoal e professor autônomo (T3) | Clientes, espaços e vínculos | Concluída | `022-espaco-pessoal/` | a criar | a criar |
| FN-023 | cadastro-clientes | Cadastro de clientes (T4) | Clientes, espaços e vínculos | Concluída | `023-cadastro-clientes/` | a criar | a criar |
| FN-024 | copias-entre-espacos | Cópias entre espaços (T5) | Clientes, espaços e vínculos | Substituída | `024-copias-entre-espacos/` | a criar | a criar |
| FN-025 | transferencias-institucionais | Transferências institucionais (TR1, TE1, UX1) | Clientes, espaços e vínculos | Pendente | `025-transferencias-institucionais/` | a criar | a criar |
| FN-026 | matricula-enturmacao | Matrícula e enturmação (MT1) | Clientes, espaços e vínculos | Parcial | `026-matricula-enturmacao/` | a criar | a criar |
| FN-027 | professor-independente | Professor independente (PA1) | Clientes, espaços e vínculos | Parcial | `027-professor-independente/` | a criar | a criar |
| FN-028 | encerramento-cliente | Encerramento de cliente (T6) | Clientes, espaços e vínculos | Pendente | `028-encerramento-cliente/` | a criar | a criar |
| FN-029 | escola-independente | Escola independente / autônoma (EI1) | Clientes, espaços e vínculos | Pendente | `029-escola-independente/` | a criar | a criar |
| FN-030 | saas-autocadastro-assinatura | Autocadastro, planos e assinatura | Clientes, espaços e vínculos | Substituída | `030-saas-autocadastro-assinatura/` | a criar | a criar |
| FN-031 | catalogo-instituicoes | Catálogo nacional de instituições (CI1) | Clientes, espaços e vínculos | Em homologação | `031-catalogo-instituicoes/` | a criar | a criar |
| FN-032 | busca-catalogo-autocompletar | Busca do catálogo por autocompletar | Clientes, espaços e vínculos | Adiada | `032-busca-catalogo-autocompletar/` | a criar | a criar |
| FN-033 | estrutura-escolar | Núcleos, escolas, turmas e alunos | Cadastros escolares | Parcial | `033-estrutura-escolar/` | a criar | a criar |
| FN-034 | importacao-exportacao-alunos | Importação e exportação de alunos | Cadastros escolares | Pendente | `034-importacao-exportacao-alunos/` | a criar | a criar |
| FN-035 | responsaveis-alunos | Dados dos responsáveis | Cadastros escolares | Pendente | `035-responsaveis-alunos/` | a criar | a criar |
| FN-036 | organizacao-turma | Organização da turma (TU1) | Cadastros escolares | Em homologação | `036-organizacao-turma/` | a criar | a criar |
| FN-037 | imagens-turmas-educadores | Imagens de turmas e educadores | Cadastros escolares | Parcial | `037-imagens-turmas-educadores/` | a criar | a criar |
| FN-038 | disciplinas-curriculo | Disciplinas, áreas e currículo das provas (DC1) | Cadastros escolares | Em homologação | `038-disciplinas-curriculo/` | a criar | a criar |
| FN-039 | periodos-letivos | Períodos letivos | Cadastros escolares | Candidata | `039-periodos-letivos/` | a criar | a criar |
| FN-040 | provas-gabaritos | Provas e gabaritos | Provas e gabaritos | Concluída | `040-provas-gabaritos/` | a criar | a criar |
| FN-041 | autoria-provas | Autoria das provas e "Meu espaço" | Provas e gabaritos | Em homologação | `041-autoria-provas/` | a criar | a criar |
| FN-042 | duplicacao-provas | Duplicação de provas (CP1, CP2) | Provas e gabaritos | Parcial | `042-duplicacao-provas/` | a criar | a criar |
| FN-043 | editor-provas-questoes | Editor de provas e banco de questões | Provas e gabaritos | Candidata | `043-editor-provas-questoes/` | a criar | a criar |
| FN-044 | acervo-gii | Acervo inteligente (GII) | Provas e gabaritos | Candidata | `044-acervo-gii/` | a criar | a criar |
| FN-045 | omr-correcao | Motor OMR e acompanhamento da correção | Correção e OMR | Concluída | `045-omr-correcao/` | a criar | a criar |
| FN-046 | revisao-leituras | Fotos da correção e revisão de leituras | Correção e OMR | Parcial | `046-revisao-leituras/` | a criar | a criar |
| FN-047 | omr-qualidade-resultado | Qualidade, tentativas, resultado e documentos verificáveis (OMR1–3) | Correção e OMR | Em homologação | `047-omr-qualidade-resultado/` | a criar | a criar |
| FN-048 | omr-driver-externo | Driver OMR externo | Correção e OMR | Candidata | `048-omr-driver-externo/` | a criar | a criar |
| FN-049 | relatorios-resultados | Resultados e relatórios consolidados | Resultados e relatórios | Concluída | `049-relatorios-resultados/` | a criar | a criar |
| FN-050 | relatorio-turma-pdf | Relatório PDF da turma | Resultados e relatórios | Pendente | `050-relatorio-turma-pdf/` | a criar | a criar |
| FN-051 | analises-avancadas | TCT/TRI, integridade e sínteses | Resultados e relatórios | Candidata | `051-analises-avancadas/` | a criar | a criar |
| FN-052 | dashboards-perfil | Painéis por perfil | Painéis | Concluída | `052-dashboards-perfil/` | a criar | a criar |
| FN-053 | paineis-turmas | Painéis de turmas e da turma | Painéis | Parcial | `053-paineis-turmas/` | a criar | a criar |
| FN-054 | paineis-editor | Personalização e editor dos painéis | Painéis | Concluída | `054-paineis-editor/` | a criar | a criar |
| FN-055 | acompanhamento-pedagogico | Acompanhamento pedagógico | Painéis | Pendente | `055-acompanhamento-pedagogico/` | a criar | a criar |
| FN-056 | agenda-calendario | Agenda e calendário (módulo transversal) | Agenda e notificações | Pendente | `056-agenda-calendario/` | a criar | a criar |
| FN-057 | notificacoes | Notificações (módulo transversal) | Agenda e notificações | Pendente | `057-notificacoes/` | a criar | a criar |
| FN-058 | visitas-pedagogicas | Visitas pedagógicas | Agenda e notificações | Pendente | `058-visitas-pedagogicas/` | a criar | a criar |
| FN-059 | agendamento-aplicacao-provas | Marcação e aplicação de provas | Agenda e notificações | Pendente | `059-agendamento-aplicacao-provas/` | a criar | a criar |
| FN-060 | email-transacional | E-mail transacional e Fale Conosco | Comunicação e e-mail | Parcial | `060-email-transacional/` | a criar | a criar |
| FN-061 | lgpd-termos-privacidade | Termos, privacidade e aceite | LGPD e portal | Parcial | `061-lgpd-termos-privacidade/` | a criar | a criar |
| FN-062 | lgpd-inventario-retencao | Inventário de dados e retenção | LGPD e portal | Parcial | `062-lgpd-inventario-retencao/` | a criar | a criar |
| FN-063 | lgpd-protecao-dados | Proteção dos dados (cifragem) | LGPD e portal | Concluída | `063-lgpd-protecao-dados/` | a criar | a criar |
| FN-064 | pedidos-titulares | Pedidos dos titulares (L4) | LGPD e portal | Parcial | `064-pedidos-titulares/` | a criar | a criar |
| FN-065 | plano-incidentes | Plano de incidentes | LGPD e portal | Pendente | `065-plano-incidentes/` | a criar | a criar |
| FN-066 | avatares-alunos | Avatar no lugar de foto de aluno | LGPD e portal | Parcial | `066-avatares-alunos/` | a criar | a criar |
| FN-067 | portal-responsaveis | Portal dos responsáveis | LGPD e portal | Pendente | `067-portal-responsaveis/` | a criar | a criar |
| FN-068 | financeiro | Financeiro (contratos, faturas, pagamentos) | Financeiro | Pendente | `068-financeiro/` | a criar | a criar |
| FN-069 | selo-documentos | Selo dos documentos | Financeiro | Pendente | `069-selo-documentos/` | a criar | a criar |
| FN-070 | identidade-visual | Identidade visual e gráficos | Público e marca | Concluída | `070-identidade-visual/` | a criar | a criar |
| FN-071 | marca-documentos | Marca oficial e cabeçalhos de documentos | Público e marca | Em homologação | `071-marca-documentos/` | a criar | a criar |
| FN-072 | site-publico | Site público e apresentação | Público e marca | Em homologação | `072-site-publico/` | a criar | a criar |
| FN-073 | layout-responsivo | Layout responsivo | Qualidade transversal | Concluída | `073-layout-responsivo/` | a criar | a criar |
| FN-074 | tabelas-padrao | Padrão de tabelas e listas | Qualidade transversal | Em homologação | `074-tabelas-padrao/` | a criar | a criar |
| FN-075 | tailwind-ui | Migração para Tailwind e UX transversal | Qualidade transversal | Parcial | `075-tailwind-ui/` | a criar | a criar |
| FN-076 | carregamento-estados | Carregamento, estados de erro e navegação | Qualidade transversal | Parcial | `076-carregamento-estados/` | a criar | a criar |
| FN-077 | desempenho | Desempenho e carga | Qualidade transversal | Parcial | `077-desempenho/` | a criar | a criar |

**Totais por estado:** Concluída 20 · Em homologação 9 · Parcial 18 · Pendente 20 · Adiada 2 · Substituída 2 · Candidata 6 · total 77.

## Como incluir uma funcionalidade nova

1. Registrar no plano mestre como **Candidata**, com origem e data, usando o próximo número livre (FN-078 em diante).
2. Acrescentar a linha neste índice.
3. Aguardar a priorização do responsável antes de escrever os documentos ou executar.
