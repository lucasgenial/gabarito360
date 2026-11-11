<!-- Arquivo gerado por scripts/impacto_principio.py. Não edite à mão. -->
# Adequações pendentes aos princípios

**Versão dos princípios:** 17

Este documento lista as funcionalidades que precisam de revalidação, porque um ADR que as afeta mudou depois da versão registrada no campo "Conforme princípios" do `planejamento.md` ([ADR-0053](../decisoes/0053-principios-executaveis-e-mutaveis.md)).

Como usar:

1. Antes de alterar o código de uma funcionalidade, faça a subfase de adequação dela.
2. Leia os ADRs listados. Registre no `planejamento.md` as mudanças que eles exigem.
3. Suba "Conforme princípios" para a versão 17. A regra `R-VERSAO-PRINCIPIOS-FN` reprova o PR de código sem esse passo.
4. Rode `python scripts/impacto_principio.py --aplicar`. A funcionalidade sai desta lista.

Para ver quantos arquivos cada ADR alcança hoje, rode `python scripts/impacto_principio.py --adr ADR-NNNN`.

## 1. ADRs com adequação pendente

| ADR | Versão | Título | Verificação | Funcionalidades pendentes | Caminhos no escopo |
|---|---|---|---|---|---|
| [ADR-0004](../decisoes/0004-convencao-de-commits-e-branches.md) | 2 | Commits em tipo(escopo) pt-BR, atômicos, em branches de feature | processo | 74 | repositório inteiro |
| [ADR-0048](../decisoes/0048-tronco-unico-de-planejamento.md) | 6 | Tronco único de planejamento: plano mestre, governança e catálogo | teste (R-CATALOGO, R-FN-NO-PR) | 74 | `docs/funcionalidades/**`, `docs/planejamento/**`, `apps/**` |
| [ADR-0051](../decisoes/0051-autoria-exclusiva-do-responsavel.md) | 6 | Autoria exclusiva do responsável | teste (R-AUTORIA) | 1 | repositório inteiro |
| [ADR-0053](../decisoes/0053-principios-executaveis-e-mutaveis.md) | 7 | Princípios executáveis, versionados e mutáveis | teste (R-PRINCIPIOS, R-AGENTS-GERADO, R-VERSAO-PRINCIPIOS-FN, R-IMPACTO-GERADO) | 74 | `docs/decisoes/**`, `AGENTS.md`, `scripts/**` |
| [ADR-0002](../decisoes/0002-fontes-de-verdade-e-legado.md) | 8 | Documentação antes do código; mockups e style system vencem; legado só dá regras | revisao-ia | 75 | `mockups/**`, `style-system/**`, `docs/**` |
| [ADR-0057](../decisoes/0057-app-responsivo-em-todas-as-faixas.md) | 10 | O app é responsivo; toda tela e todo mockup atendem a todas as faixas de aparelho | revisao-ia | 75 | `apps/web/resources/views/**`, `apps/web/public/css/**`, `apps/web/public/js/**`, `mockups/**`, `style-system/**` |
| [ADR-0056](../decisoes/0056-app-atual-como-referencia-visual.md) | 11 | O app atual é a referência visual; style-system/ é o design system do app; os mockups originais são históricos | revisao-ia | 75 | `mockups/**`, `style-system/**`, `apps/web/resources/views/**`, `apps/web/public/css/**` |

## 2. Subfases de adequação por funcionalidade

75 funcionalidade(s) com adequação pendente.

| FN | Funcionalidade | Etapa | Conforme | Subfase de adequação |
|---|---|---|---|---|
| [FN-001](../funcionalidades/001-fundacao-monorepo/planejamento.md) | Fundação do monorepo, banco e bases API/WEB | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-002](../funcionalidades/002-base-operacao/planejamento.md) | Base de operação (backup, Redis, filas, leitura pela fila) | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md) | Publicação, deploy e homologação isolada | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-004](../funcionalidades/004-auditoria/planejamento.md) | Auditoria de requisições, expurgo e tela de consulta | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-005](../funcionalidades/005-suporte-ver-como/planejamento.md) | "Ver como" e atendimento auditado | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-006](../funcionalidades/006-painel-tecnico/planejamento.md) | Painel técnico | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md) | Limites, armazenamento externo, separação e observabilidade | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-008](../funcionalidades/008-ci-integracao/planejamento.md) | Integração contínua (MariaDB) | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0051, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-009](../funcionalidades/009-estrutura-diretorios-alvo/planejamento.md) | Estrutura-alvo de diretórios | Candidata | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-010](../funcionalidades/010-app-mobile/planejamento.md) | Aplicativos Android e iOS | Adiada | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-011](../funcionalidades/011-autenticacao-conta/planejamento.md) | Autenticação e conta | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-012](../funcionalidades/012-permissoes-motor/planejamento.md) | Motor de permissões, equipe e menus | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-013](../funcionalidades/013-acessos-internos/planejamento.md) | Acessos internos (Desenvolvedor, Suporte, Financeiro) | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-014](../funcionalidades/014-perfis-institucionais/planejamento.md) | Perfis institucionais (secretário escolar, vice-diretor e outros) | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-015](../funcionalidades/015-vinculos-contexto/planejamento.md) | Vínculos por pessoa e seleção de vínculo | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-016](../funcionalidades/016-identidade-pessoal/planejamento.md) | Identidade pessoal (ID1) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-017](../funcionalidades/017-convites-cpf/planejamento.md) | Vínculos e convites CPF-primeiro (VI1) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-018](../funcionalidades/018-seguranca-urls-sessao/planejamento.md) | Segurança de URLs, sessão e dependências (UR1–UR4) | Em homologação | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-019](../funcionalidades/019-perfil-configuracoes/planejamento.md) | Meu perfil, configurações e imagem de perfil | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-020](../funcionalidades/020-acesso-aluno/planejamento.md) | Acesso próprio do aluno | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-021](../funcionalidades/021-clientes-espacos/planejamento.md) | Clientes e espaços (T1/T2) | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-022](../funcionalidades/022-espaco-pessoal/planejamento.md) | Espaço pessoal e professor autônomo (T3) | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-023](../funcionalidades/023-cadastro-clientes/planejamento.md) | Cadastro de clientes (T4) | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-025](../funcionalidades/025-transferencias-institucionais/planejamento.md) | Transferências institucionais (TR1, TE1, UX1) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-026](../funcionalidades/026-matricula-enturmacao/planejamento.md) | Matrícula e enturmação (MT1) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-027](../funcionalidades/027-professor-independente/planejamento.md) | Professor independente (PA1) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-028](../funcionalidades/028-encerramento-cliente/planejamento.md) | Encerramento de cliente (T6) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-029](../funcionalidades/029-escola-independente/planejamento.md) | Escola independente / autônoma (EI1) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-031](../funcionalidades/031-catalogo-instituicoes/planejamento.md) | Catálogo nacional de instituições (CI1) | Em homologação | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-032](../funcionalidades/032-busca-catalogo-autocompletar/planejamento.md) | Busca do catálogo por autocompletar | Adiada | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-033](../funcionalidades/033-estrutura-escolar/planejamento.md) | Núcleos, escolas, turmas e alunos | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-034](../funcionalidades/034-importacao-exportacao-alunos/planejamento.md) | Importação e exportação de alunos | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-035](../funcionalidades/035-responsaveis-alunos/planejamento.md) | Dados dos responsáveis | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-036](../funcionalidades/036-organizacao-turma/planejamento.md) | Organização da turma (TU1) | Em homologação | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-037](../funcionalidades/037-imagens-turmas-educadores/planejamento.md) | Imagens de turmas e educadores | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-038](../funcionalidades/038-disciplinas-curriculo/planejamento.md) | Disciplinas, áreas e currículo das provas (DC1) | Em homologação | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-039](../funcionalidades/039-periodos-letivos/planejamento.md) | Períodos letivos | Candidata | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-040](../funcionalidades/040-provas-gabaritos/planejamento.md) | Provas e gabaritos | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-041](../funcionalidades/041-autoria-provas/planejamento.md) | Autoria das provas e "Meu espaço" | Em homologação | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-042](../funcionalidades/042-duplicacao-provas/planejamento.md) | Duplicação de provas (CP1, CP2) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-043](../funcionalidades/043-editor-provas-questoes/planejamento.md) | Editor de provas e banco de questões | Candidata | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-044](../funcionalidades/044-acervo-gii/planejamento.md) | Acervo inteligente (GII) | Candidata | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-045](../funcionalidades/045-omr-correcao/planejamento.md) | Motor OMR e acompanhamento da correção | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-046](../funcionalidades/046-revisao-leituras/planejamento.md) | Fotos da correção e revisão de leituras | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-047](../funcionalidades/047-omr-qualidade-resultado/planejamento.md) | Qualidade, tentativas, resultado e documentos verificáveis (OMR1–3) | Em homologação | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-048](../funcionalidades/048-omr-driver-externo/planejamento.md) | Driver OMR externo | Candidata | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-049](../funcionalidades/049-relatorios-resultados/planejamento.md) | Resultados e relatórios consolidados | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-050](../funcionalidades/050-relatorio-turma-pdf/planejamento.md) | Relatório PDF da turma | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-051](../funcionalidades/051-analises-avancadas/planejamento.md) | TCT/TRI, integridade e sínteses | Candidata | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-052](../funcionalidades/052-dashboards-perfil/planejamento.md) | Painéis por perfil | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md) | Painéis de turmas e da turma | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-054](../funcionalidades/054-paineis-editor/planejamento.md) | Personalização e editor dos painéis | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-055](../funcionalidades/055-acompanhamento-pedagogico/planejamento.md) | Acompanhamento pedagógico | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-056](../funcionalidades/056-agenda-calendario/planejamento.md) | Agenda e calendário (módulo transversal) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-057](../funcionalidades/057-notificacoes/planejamento.md) | Notificações (módulo transversal) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-058](../funcionalidades/058-visitas-pedagogicas/planejamento.md) | Visitas pedagógicas | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-059](../funcionalidades/059-agendamento-aplicacao-provas/planejamento.md) | Marcação e aplicação de provas | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-060](../funcionalidades/060-email-transacional/planejamento.md) | E-mail transacional e Fale Conosco | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-061](../funcionalidades/061-lgpd-termos-privacidade/planejamento.md) | Termos, privacidade e aceite | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-062](../funcionalidades/062-lgpd-inventario-retencao/planejamento.md) | Inventário de dados e retenção | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-063](../funcionalidades/063-lgpd-protecao-dados/planejamento.md) | Proteção dos dados (cifragem) | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-064](../funcionalidades/064-pedidos-titulares/planejamento.md) | Pedidos dos titulares (L4) | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-065](../funcionalidades/065-plano-incidentes/planejamento.md) | Plano de incidentes | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-066](../funcionalidades/066-avatares-alunos/planejamento.md) | Avatar no lugar de foto de aluno | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-067](../funcionalidades/067-portal-responsaveis/planejamento.md) | Portal dos responsáveis | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-069](../funcionalidades/069-selo-documentos/planejamento.md) | Selo dos documentos | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-070](../funcionalidades/070-identidade-visual/planejamento.md) | Identidade visual e gráficos | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-071](../funcionalidades/071-marca-documentos/planejamento.md) | Marca oficial e cabeçalhos de documentos | Em homologação | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-072](../funcionalidades/072-site-publico/planejamento.md) | Site público e apresentação | Em homologação | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md) | Layout responsivo | Concluída | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md) | Padrão de tabelas e listas | Em homologação | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) | Migração para Tailwind e UX transversal | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-076](../funcionalidades/076-carregamento-estados/planejamento.md) | Carregamento, estados de erro e navegação | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-077](../funcionalidades/077-desempenho/planejamento.md) | Desempenho e carga | Em planejamento | versão 1 | Revalidar contra ADR-0002, ADR-0004, ADR-0048, ADR-0053, ADR-0056, ADR-0057 e subir para a versão 17 |
| [FN-078](../funcionalidades/078-governanca-executavel/planejamento.md) | Governança executável: princípios verificados e propagados automaticamente | Em execução | versão 7 | Revalidar contra ADR-0002, ADR-0056, ADR-0057 e subir para a versão 17 |
