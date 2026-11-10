# Registro de decisões do Gabarito360

**Atualizado em:** 10/11/2026, por lucasgenial

Este documento descreve o registro das decisões do responsável e lista os ADRs (registros de decisão de arquitetura e de produto) vigentes e superados.

## Para que serve

Cada decisão do responsável tem um arquivo `NNNN-slug.md`, no [modelo](_modelo.md). A lista fica em ordem cronológica. Os números nunca são reutilizados.

Use este registro para:

- Descobrir a regra vigente sobre um tema, sem ler o histórico de conversas.
- Ver qual decisão substituiu outra.
- Ligar uma funcionalidade (FN) às decisões que a afetam.

As regras vigentes resumidas estão no [AGENTS.md](../../AGENTS.md), com links para estes registros.

## Como registrar uma decisão nova

1. Copie `_modelo.md` e use o próximo número livre.
2. Preencha data, estado, origem e funcionalidades afetadas.
3. Cite o texto literal do responsável entre aspas.
4. Se a decisão substitui outra, edite o estado da antiga para "Substituída por ADR-NNNN" e cite a antiga em Consequências.
5. Preencha o cabeçalho de campos no início do arquivo (veja `_modelo.md`):
   - `verificacao`: `teste` (a CI verifica), `revisao-ia` (o agente de conformidade verifica) ou `processo` (rito de trabalho).
   - Com `teste`, cite pelo menos uma regra de [regras.yml](regras.yml). Uma regra nova entra no registro como `planejada` ou `ativa`.
   - Com `agents_secao`, `agents_ordem` e `agents_resumo`, a regra entra no AGENTS.md.
6. Acrescente uma linha à tabela abaixo.
7. Rode `python scripts/verificar_principios.py --atualizar` e `python scripts/gerar_agents.py`. O primeiro sobe a [versão dos princípios](VERSAO). O segundo gera o AGENTS.md. Não edite o AGENTS.md à mão: a CI reprova a diferença.

As regras de código usam a [linha de base](linha-de-base.json) das violações antigas. Uma violação nova reprova a CI. Ao corrigir uma violação antiga, rode `python scripts/verificar_regras_codigo.py --atualizar-linha-de-base`. A linha de base só pode diminuir.

## Estados

| Estado | Significado |
|---|---|
| Aceita | A decisão está em vigor. |
| Substituída por ADR-NNNN | Outra decisão a superou, no todo ou em parte. O texto indica a parte. |
| Revogada | O responsável retirou a decisão sem substituí-la. |

## Índice

| ADR | Título | Data | Estado | FN afetadas |
|---|---|---|---|---|
| [ADR-0001](0001-arquitetura-oficial.md) | O Gabarito360 usa monorepo com API Laravel (somente JSON), WEB Laravel e MariaDB | 16/06/2026 | Aceita | [FN-001](../funcionalidades/001-fundacao-monorepo/planejamento.md) |
| [ADR-0002](0002-fontes-de-verdade-e-legado.md) | Nenhum código nasce sem documentação aprovada; mockups e style system vencem a documentação; o legado só fornece regras | 16/06/2026 | Aceita | — |
| [ADR-0003](0003-identidade-visual-propria.md) | O produto segue identidade visual própria, direção "Atual refinada", com tokens em um arquivo único | 16/06/2026 | Substituída por [ADR-0026](0026-tailwindcss-como-base-visual.md) (parcial: a paleta Gabarito360 continua vigente) | [FN-070](../funcionalidades/070-identidade-visual/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0004](0004-convencao-de-commits-e-branches.md) | Commits seguem tipo(escopo): descrição em pt-BR, são atômicos e nascem em branches de feature | 16/06/2026 | Aceita | — |
| [ADR-0005](0005-micro-passos-e-definicao-de-concluido.md) | O trabalho avança em micro-passos (MP) com dependências e seis critérios de "Concluído" | 16/06/2026 | Substituída por [ADR-0048](0048-tronco-unico-de-planejamento.md) | [FN-001](../funcionalidades/001-fundacao-monorepo/planejamento.md), [FN-077](../funcionalidades/077-desempenho/planejamento.md) |
| [ADR-0006](0006-android-adiado-web-api-primeiro.md) | O aplicativo Android (MP-023) fica adiado; a equipe fecha antes as funcionalidades WEB e API | 18/06/2026 | Aceita | [FN-010](../funcionalidades/010-app-mobile/planejamento.md) |
| [ADR-0007](0007-saas-multi-tenant-autocadastro-assinatura.md) | O produto vira SaaS multi-tenant com secretaria, modalidade individual e institucional, cadastro autônomo e assinatura | 18/06/2026 | Aceita | [FN-021](../funcionalidades/021-clientes-espacos/planejamento.md), [FN-022](../funcionalidades/022-espaco-pessoal/planejamento.md), [FN-030](../funcionalidades/030-saas-autocadastro-assinatura/planejamento.md), [FN-068](../funcionalidades/068-financeiro/planejamento.md) |
| [ADR-0008](0008-motor-de-permissoes-configuraveis.md) | As permissões são um motor configurável por perfil e escola, migrado endpoint a endpoint | 18/06/2026 | Aceita | [FN-012](../funcionalidades/012-permissoes-motor/planejamento.md), [FN-013](../funcionalidades/013-acessos-internos/planejamento.md), [FN-014](../funcionalidades/014-perfis-institucionais/planejamento.md) |
| [ADR-0009](0009-limites-de-agentes-e-conclusao-de-pacote.md) | Ações sensíveis exigem aprovação explícita; um pacote só termina com testes, verificações e quadro atualizado | 31/10/2026 | Aceita | [FN-002](../funcionalidades/002-base-operacao/planejamento.md), [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md), [FN-008](../funcionalidades/008-ci-integracao/planejamento.md) |
| [ADR-0010](0010-email-resend-e-cloudflare.md) | O e-mail usa Resend para envio e Cloudflare Email Routing para recebimento, com quatro endereços | 31/10/2026 | Aceita | [FN-011](../funcionalidades/011-autenticacao-conta/planejamento.md), [FN-060](../funcionalidades/060-email-transacional/planejamento.md) |
| [ADR-0011](0011-vinculos-auditoria-e-ver-como.md) | Uma pessoa escolhe o vínculo ao entrar; a auditoria é restrita; o Suporte vê o sistema como outro usuário | 31/10/2026 | Aceita | [FN-004](../funcionalidades/004-auditoria/planejamento.md), [FN-005](../funcionalidades/005-suporte-ver-como/planejamento.md), [FN-013](../funcionalidades/013-acessos-internos/planejamento.md), [FN-015](../funcionalidades/015-vinculos-contexto/planejamento.md) |
| [ADR-0012](0012-alunos-professor-autonomo-e-avatar.md) | Coordenador muda aluno de turma; professor autônomo tem cadastro mínimo; o aluno é representado por avatar | 31/10/2026 | Aceita | [FN-026](../funcionalidades/026-matricula-enturmacao/planejamento.md), [FN-027](../funcionalidades/027-professor-independente/planejamento.md), [FN-033](../funcionalidades/033-estrutura-escolar/planejamento.md), [FN-066](../funcionalidades/066-avatares-alunos/planejamento.md) |
| [ADR-0013](0013-tipos-de-cliente-financeiro-e-divulgacao.md) | Cliente pode ser professor, escola, núcleo, rede, secretaria ou governo; o Financeiro é um perfil; a divulgação depende do plano | 31/10/2026 | Aceita | [FN-021](../funcionalidades/021-clientes-espacos/planejamento.md), [FN-023](../funcionalidades/023-cadastro-clientes/planejamento.md), [FN-068](../funcionalidades/068-financeiro/planejamento.md), [FN-069](../funcionalidades/069-selo-documentos/planejamento.md) |
| [ADR-0014](0014-portal-dos-responsaveis-regras-iniciais.md) | O portal dos responsáveis não usa CPF nem código por e-mail na primeira versão e usa Cloudflare Turnstile | 31/10/2026 | Aceita | [FN-035](../funcionalidades/035-responsaveis-alunos/planejamento.md), [FN-061](../funcionalidades/061-lgpd-termos-privacidade/planejamento.md), [FN-067](../funcionalidades/067-portal-responsaveis/planejamento.md) |
| [ADR-0015](0015-capacidade-inicial-e-topologia.md) | Meta inicial de 100 instituições, 600 professores e 1.000 responsáveis; WEB e API no mesmo servidor com grupos de processos separados | 31/10/2026 | Aceita | [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md), [FN-056](../funcionalidades/056-agenda-calendario/planejamento.md), [FN-077](../funcionalidades/077-desempenho/planejamento.md) |
| [ADR-0016](0016-homologacao-isolada-adr-h1.md) | Toda entrega vai primeiro a uma homologação isolada em VM própria; a promoção para produção é decisão do responsável | 03/11/2026 | Aceita | [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md) |
| [ADR-0017](0017-copia-pontual-de-producao-para-homologacao.md) | O responsável autorizou copiar os usuários e dados atuais de produção para a homologação, mantendo as senhas | 03/11/2026 | Aceita | [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md) |
| [ADR-0018](0018-producao-so-por-decisao-expressa.md) | A publicação em produção depende de decisão explícita do responsável, mesmo após CI, revisão e homologação | 03/11/2026 | Aceita | [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md), [FN-018](../funcionalidades/018-seguranca-urls-sessao/planejamento.md), [FN-046](../funcionalidades/046-revisao-leituras/planejamento.md), [FN-064](../funcionalidades/064-pedidos-titulares/planejamento.md) |
| [ADR-0019](0019-marcos-de-entrega-e-conciliacao-r0.md) | Publique no fechamento validado de etapa de nível 1; subdivisões internas avançam sem pedir autorização | 03/11/2026 | Aceita | [FN-016](../funcionalidades/016-identidade-pessoal/planejamento.md), [FN-017](../funcionalidades/017-convites-cpf/planejamento.md), [FN-025](../funcionalidades/025-transferencias-institucionais/planejamento.md), [FN-026](../funcionalidades/026-matricula-enturmacao/planejamento.md), [FN-031](../funcionalidades/031-catalogo-instituicoes/planejamento.md), [FN-042](../funcionalidades/042-duplicacao-provas/planejamento.md) |
| [ADR-0020](0020-revisao-de-ui-ux-por-especialista.md) | Toda mudança de interface exige aprovação do usuário ou de agente de UI/UX e Product Design, e consulta prévia | 03/11/2026 | Aceita | [FN-070](../funcionalidades/070-identidade-visual/planejamento.md), [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md), [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md), [FN-076](../funcionalidades/076-carregamento-estados/planejamento.md) |
| [ADR-0021](0021-telas-ocupam-toda-a-area-util.md) | As telas ocupam toda a área útil do viewport, sem max-width fixo que deixe faixa vazia | 03/11/2026 | Aceita | [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0022](0022-tabelas-cards-de-registros-e-avisos.md) | Tabelas operacionais usam x-tabela dentro do card principal; listas viram cards no celular; avisos têm margem | 04/11/2026 | Aceita | [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0023](0023-javascript-em-arquivos-externos.md) | O JavaScript executável fica em arquivos externos; as views não têm script inline nem eventos inline | 06/11/2026 | Aceita | [FN-018](../funcionalidades/018-seguranca-urls-sessao/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md), [FN-076](../funcionalidades/076-carregamento-estados/planejamento.md) |
| [ADR-0024](0024-vinculo-ativo-acompanha-a-sessao.md) | O vínculo ativo acompanha a sessão autenticada, sem expiração própria; sessão irrecuperável encerra tudo | 06/11/2026 | Aceita | [FN-011](../funcionalidades/011-autenticacao-conta/planejamento.md), [FN-015](../funcionalidades/015-vinculos-contexto/planejamento.md), [FN-018](../funcionalidades/018-seguranca-urls-sessao/planejamento.md) |
| [ADR-0025](0025-confirmacoes-em-modal-e-acoes-de-turma.md) | Confirmações de negócio usam modal da aplicação; Editar e Arquivar turma têm ícone e texto | 07/11/2026 | Aceita | [FN-036](../funcionalidades/036-organizacao-turma/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0026](0026-tailwindcss-como-base-visual.md) | O TailwindCSS é a base visual efetiva; a paleta Gabarito360 permanece e a estética anterior deixa de ser requisito | 08/11/2026 | Aceita | [FN-070](../funcionalidades/070-identidade-visual/planejamento.md), [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0027](0027-previa-isolada-antes-das-telas-operacionais.md) | Uma página de prévia com componentes, propriedades e regras exige aceite humano antes da nova identidade nas telas operacionais | 08/11/2026 | Aceita | [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0028](0028-ajuda-contextual-no-titulo-de-cards.md) | Orientações complementares de cards, gráficos e KPIs usam o próprio título como botão de ajuda contextual | 08/11/2026 | Aceita | [FN-052](../funcionalidades/052-dashboards-perfil/planejamento.md), [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0029](0029-previa-correcao-meu-espaco-no-card.md) | O Meu espaço da primeira prévia volta como bloco recolhível dentro do card, com escola e perfil em duas colunas | 08/11/2026 | Aceita | [FN-041](../funcionalidades/041-autoria-provas/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0030](0030-previa-avatar-abre-o-menu-da-conta.md) | O avatar abre o menu da conta, não a navegação principal; a troca de vínculo não se duplica | 08/11/2026 | Aceita | [FN-015](../funcionalidades/015-vinculos-contexto/planejamento.md), [FN-019](../funcionalidades/019-perfil-configuracoes/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0031](0031-previa-menu-principal-desktop-em-fluxo-lateral.md) | O menu principal abre em fluxo lateral somente no desktop, sem sobreposição; o mobile mantém quatro atalhos e Mais | 08/11/2026 | Substituída por [ADR-0032](0032-previa-rodada-2-menu-expandido-e-compacto.md) | [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0032](0032-previa-rodada-2-menu-expandido-e-compacto.md) | O menu desktop alterna entre expandido (256 px) e compacto (80 px); celulares até 767 px usam navegação inferior | 08/11/2026 | Aceita | [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0033](0033-previa-rodada-3-contexto-icones-e-acoes.md) | Rodada 3: contexto em largura total, Material Symbols locais e menu lateral claro e navy | 08/11/2026 | Substituída por [ADR-0039](0039-coluna-de-acoes-a-direita-e-ordenacao.md) (ações à esquerda) e [ADR-0036](0036-tw2-filtros-em-modal-e-superficie-escura.md) (cor escura da lateral); o restante continua vigente | [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0034](0034-previa-rodada-4-solucao-progressiva.md) | Rodada 4 aprovada: botões e ações se adaptam por largura disponível; logo oficial da homologação | 08/11/2026 | Aceita | [FN-071](../funcionalidades/071-marca-documentos/planejamento.md), [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0035](0035-aceite-da-previa-e-inicio-do-tw2.md) | A prévia (rodada 4) está aprovada; o TW2 na estrutura real está autorizado só na homologação | 08/11/2026 | Aceita | [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0036](0036-tw2-filtros-em-modal-e-superficie-escura.md) | No TW2, os filtros ficam em modal e a lateral e o cabeçalho escuros mantêm a superfície antiga | 08/11/2026 | Aceita | [FN-036](../funcionalidades/036-organizacao-turma/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0037](0037-versionamento-sem-apelidos.md) | A versão usa número sem apelidos, com fonte única em config/versao.php | 08/11/2026 | Substituída por [ADR-0038](0038-rodape-de-versao-com-ambiente.md) (a exibição só do número) | [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0038](0038-rodape-de-versao-com-ambiente.md) | O rodapé exibe literalmente "Gabarito360 - v0.1.0 - Homologação", com o ambiente derivado de APP_ENV | 08/11/2026 | Aceita | [FN-003](../funcionalidades/003-publicacao-e-homologacao/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0039](0039-coluna-de-acoes-a-direita-e-ordenacao.md) | Em todas as tabelas, a coluna de ações fica à direita, centralizada; colunas de dados ordenam | 08/11/2026 | Aceita | [FN-074](../funcionalidades/074-tabelas-padrao/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0040](0040-paineis-secoes-e-cards-reordenaveis.md) | Resultados por período e Agenda da turma entram em Resultados da turma; seções e cards centrais são reordenáveis | 08/11/2026 | Aceita | [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md), [FN-054](../funcionalidades/054-paineis-editor/planejamento.md) |
| [ADR-0041](0041-editor-de-paineis-previa-local.md) | A composição do editor de painéis em duas abas é apresentada primeiro como prévia local, sem aplicação nem publicação | 09/11/2026 | Substituída por [ADR-0043](0043-editor-de-paineis-aceite-e-integracao.md) | [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md), [FN-054](../funcionalidades/054-paineis-editor/planejamento.md) |
| [ADR-0042](0042-editor-de-paineis-quadro-de-colunas.md) | A aba Distribuição mantém o quadro de colunas por seção; a grade visual pertence aos Ajustes finos | 09/11/2026 | Aceita | [FN-054](../funcionalidades/054-paineis-editor/planejamento.md) |
| [ADR-0043](0043-editor-de-paineis-aceite-e-integracao.md) | O editor de painéis está aprovado e a integração real nos painéis Turmas e Turma está autorizada, só na homologação | 09/11/2026 | Aceita | [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md), [FN-054](../funcionalidades/054-paineis-editor/planejamento.md) |
| [ADR-0044](0044-rodape-de-versao-oculto-no-celular.md) | O rodapé de versão não aparece no celular (até 767 CSS px) e não reserva espaço | 09/11/2026 | Aceita | [FN-073](../funcionalidades/073-layout-responsivo/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0045](0045-direcao-de-desempenho-aprovada.md) | A direção de desempenho aprovada fecha o editor primeiro, mede a baseline autenticada e prioriza por evidência | 09/11/2026 | Aceita | [FN-054](../funcionalidades/054-paineis-editor/planejamento.md), [FN-077](../funcionalidades/077-desempenho/planejamento.md) |
| [ADR-0046](0046-fundamentos-de-desempenho-adr-195.md) | Fundamentos de desempenho e aceitação das funcionalidades (ADR-195) | 09/11/2026 | Aceita | [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md), [FN-053](../funcionalidades/053-paineis-turmas/planejamento.md), [FN-054](../funcionalidades/054-paineis-editor/planejamento.md), [FN-077](../funcionalidades/077-desempenho/planejamento.md) |
| [ADR-0047](0047-plano-integral-de-desempenho-f0-f6.md) | O plano integral de desempenho executa as etapas completas F0 a F6, com regras de medição e de cache | 09/11/2026 | Aceita | [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md), [FN-077](../funcionalidades/077-desempenho/planejamento.md) |
| [ADR-0048](0048-tronco-unico-de-planejamento.md) | O planejamento tem um único tronco: o plano mestre, regido pela governança, com catálogo de funcionalidades | 09/11/2026 | Aceita | — |
| [ADR-0049](0049-sem-prints-da-aplicacao.md) | Nenhum print da aplicação é mantido, em nenhum serviço; as conferências visuais são registradas por texto | 09/11/2026 | Aceita | [FN-008](../funcionalidades/008-ci-integracao/planejamento.md), [FN-070](../funcionalidades/070-identidade-visual/planejamento.md), [FN-075](../funcionalidades/075-tailwind-ui/planejamento.md) |
| [ADR-0050](0050-padrao-de-escrita-asd-ste100.md) | Todo documento novo ou revisado segue o padrão ASD-STE100 moderado, aplicado ao português | 10/11/2026 | Aceita | — |
| [ADR-0051](0051-autoria-exclusiva-do-responsavel.md) | Nenhuma IA se identifica como autora ou coautora; o único autor é o usuário lucasgenial | 10/11/2026 | Aceita | [FN-008](../funcionalidades/008-ci-integracao/planejamento.md) |
| [ADR-0052](0052-medicao-com-1000-usuarios-simultaneos.md) | Toda medição de desempenho usa 1.000 usuários simultâneos, sem simulação | 10/11/2026 | Aceita | [FN-007](../funcionalidades/007-escala-infraestrutura/planejamento.md), [FN-077](../funcionalidades/077-desempenho/planejamento.md) |
| [ADR-0053](0053-principios-executaveis-e-mutaveis.md) | Os princípios são executáveis, versionados e podem mudar a qualquer momento; a verificação é automática | 10/11/2026 | Aceita | Todas; [FN-078](../funcionalidades/078-governanca-executavel/planejamento.md) |
| [ADR-0054](0054-time-de-agentes-e-modelos-multifornecedor.md) | Time de agentes e modelos de IA de mais de um fornecedor | 10/11/2026 | Aceita | Todas |
| [ADR-0055](0055-estrutura-da-documentacao-e-documento-dono.md) | Estrutura da documentação e documento dono de cada assunto | 10/11/2026 | Aceita | Todas |

## Origem dos identificadores antigos

| Identificador antigo | ADR |
|---|---|
| ADR-H1 (`docs/127-h1-homologacao-isolada.md`) | [ADR-0016](0016-homologacao-isolada-adr-h1.md) |
| ADR-195 (`docs/195-adr-fundamentos-desempenho.md`) | [ADR-0046](0046-fundamentos-de-desempenho-adr-195.md) |
| R0 (`docs/117-r0-conciliacao-e-marcos-de-entrega.md`) | [ADR-0019](0019-marcos-de-entrega-e-conciliacao-r0.md) |
| MP-001 a MP-035 (`docs/13`, `docs/14`) | [ADR-0005](0005-micro-passos-e-definicao-de-concluido.md) e o catálogo de funcionalidades |

## Cadeias de substituição

| Decisão antiga | Substituída por | Motivo |
|---|---|---|
| [ADR-0005](0005-micro-passos-e-definicao-de-concluido.md) micro-passos | [ADR-0048](0048-tronco-unico-de-planejamento.md) tronco único | O plano mestre e o catálogo FN passaram a orientar a execução |
| [ADR-0003](0003-identidade-visual-propria.md) identidade visual própria | [ADR-0026](0026-tailwindcss-como-base-visual.md) Tailwind | O responsável adotou o Tailwind e dispensou a estética anterior; a paleta permanece |
| [ADR-0031](0031-previa-menu-principal-desktop-em-fluxo-lateral.md) menu em fluxo lateral | [ADR-0032](0032-previa-rodada-2-menu-expandido-e-compacto.md) rodada 2 | Menu expandido e compacto no lugar do recolhimento sem coluna |
| [ADR-0033](0033-previa-rodada-3-contexto-icones-e-acoes.md) rodada 3 (ações à esquerda; lateral navy) | [ADR-0039](0039-coluna-de-acoes-a-direita-e-ordenacao.md) e [ADR-0036](0036-tw2-filtros-em-modal-e-superficie-escura.md) | Ações à direita nas tabelas; superfície escura da homologação |
| [ADR-0037](0037-versionamento-sem-apelidos.md) versão só com número | [ADR-0038](0038-rodape-de-versao-com-ambiente.md) rodapé literal | O rodapé passou a mostrar versão e ambiente |
| [ADR-0041](0041-editor-de-paineis-previa-local.md) editor só em prévia local | [ADR-0043](0043-editor-de-paineis-aceite-e-integracao.md) aceite e integração | O responsável aprovou o editor |

## Decisões pendentes (sem ADR)

Estas decisões aguardam o responsável. Elas viram ADR quando forem tomadas. O plano mestre guarda o estudo de cada uma.

| Decisão | Tema | Recomendação registrada | Origem |
|---|---|---|---|
| D9 | O "ver como" do Suporte permite alterar dados? | Só leitura, como no ECOAR | `docs/28`, seção 4 |
| D10 | Primeiro acesso do responsável | O sistema gera o código, porque a secretaria precisa vê-lo | `docs/28`, seção 4 |
| D13 | Dados da empresa para os termos | Razão social, CNPJ, endereço e encarregado | `docs/28`, seção 4 |
| D14 | Revisão jurídica de `docs/33` antes de publicar | Advogado ou encarregado revisa | `docs/28`, seção 4 |
| D15 | Acesso à Cloudflare | Concluída na prática: o pacote E1 (domínio de e-mail) está concluído | `docs/28`, seção 4 |
| Decisões 1 a 12 do plano mestre | Aprovação do plano, regra de produção (gate L4), política T5, aceite visual de T5, autocadastro, matrizes ID1 e DC1, agenda, identidades de carga, retenção LGPD, conciliação de identidade | Ver recomendações no plano mestre | `docs/planejamento/00-plano-mestre.md`, seção 5 |

As decisões D1 a D8, D11 e D12 do `docs/28` não aparecem na versão atual do documento. Suas respostas constam da lista "Já definidas", registrada nos [ADR-0010](0010-email-resend-e-cloudflare.md) a [ADR-0015](0015-capacidade-inicial-e-topologia.md).

## Divergências encontradas

- O CLAUDE.md manda documentar decisões de arquitetura em `docs/12-arquitetura.md`. O registro vigente é esta pasta.
- Os diretórios `apps/android` e `apps/ios` previstos no CLAUDE.md não existem (ver [ADR-0006](0006-android-adiado-web-api-primeiro.md)).
- O middleware `perfil:` ainda protege 8 rotas da API, embora o motor de permissões esteja concluído (ver [ADR-0008](0008-motor-de-permissoes-configuraveis.md)).
- Doze views ainda usam `onclick` ou `onsubmit` e um arquivo JavaScript ainda chama `confirm(` (ver [ADR-0023](0023-javascript-em-arquivos-externos.md) e [ADR-0025](0025-confirmacoes-em-modal-e-acoes-de-turma.md)).
- O CLAUDE.md cita `apps/web/public/css/app.css` como arquivo de tokens, mas o CSS-fonte vive em `apps/web/resources/css/` (ver [ADR-0003](0003-identidade-visual-propria.md)).
- Algumas datas do AGENTS.md não estão escritas (rodadas 3 e 4 da prévia, versionamento). Este registro as ordena pela sequência do texto e pelas datas dos docs 186 a 188 (08/11/2026).
