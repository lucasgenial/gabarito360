# 031 — Catálogo nacional de instituições (CI1) — Planejamento

**Implementação:** Em homologação
**Etapa:** Em homologação
**Prioridade no plano mestre:** sem posição nas ondas do §5; está na fila de produção do §7 (bloco "Catálogo de instituições", PR #152–#158, #160), aguardando a decisão do §4 item 2.
**Natureza:** existente documentada a partir do código
**Origem:** docs/115 §10 e docs/116 (complemento pedido pelo responsável em 03/11/2026); docs/119–126, 129, 130
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Manter um catálogo nacional de escolas e instituições de ensino superior, importado de fontes oficiais (INEP/IBGE) e publicado por edições, para que o professor do espaço pessoal declare onde atua, peça a inclusão do que falta e para que suporte e gestores autorizados mantenham os cadastros — sem que o catálogo conceda acesso, vínculo ou contratação.

## 2. Escopo
- **Inclui:** importação em staging retomável e validação; publicação versionada por edição; relações sede/IES e cobertura; busca autenticada com filtros (UF, município, tipo, dependência, código, texto); "Minhas instituições" (referências pessoais); solicitações privadas de cadastro; fila interna de atendimento; fonte manual "Cadastro da plataforma"; autoridade institucional e manutenção pela gestão; validação integrada (CI1-F).
- **Não inclui:**
  - Otimização e novo desenho da busca (autocompletar) → FN-032 (adiada).
  - Uso da referência pessoal em turmas e disciplinas → FN-027 (PA1-A).
  - Cadastro operacional de escola e a base do Censo (`censo_escolas`, `escolas/censo/*`) → FN-033.
  - Escola independente e cadastro de redes pelo suporte → FN-029.
  - Manutenção pelo Secretário de Educação → depende de FN-017 (VI1).
  - Conciliação institucional em transferências → FN-025 (TE1).
  - Retenção dos protocolos de solicitação → FN-062.

## 3. Fontes de verdade
- Documentos de origem: docs/116 (`docs/116-catalogo-nacional-instituicoes.md`), docs/119 (`docs/119-ci1-a-contrato-catalogo-nacional.md`), 120 (`docs/120-ci1-b2-carga-retomavel.md`), 121 (`docs/121-ci1-b3-publicacao-versionada.md`), 122 (`docs/122-ci1-c-relacoes-e-cobertura.md`), 124 (`docs/124-ci1-d-selecao-pessoal.md`), 125 (`docs/125-ci1-e-solicitacoes-manutencao.md`), 126 (`docs/126-ci1-e-revisao-product-design.md`), 129 (`docs/129-h1-publicacao-ci1-e.md`), 130 (`docs/130-ci1-f-validacao-integrada.md`).
- Medição e pontos de uso da busca: docs/200 (`docs/200-f5-f6-diagnostico-catalogo.md`).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) (seção CI1-D); `CatalogoPermissoes`, `ContextoInterno`.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Sem UC no docs/07.
- Mockups: não há; interface aprovada pelo revisor independente (docs/126).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-022 espaco-pessoal | Contexto pessoal do proprietário para referências e solicitações | Concluída |
| FN-013 acessos-internos | Contexto interno de Suporte/Desenvolvedor | Concluída |
| FN-015 vinculos-contexto | Vínculo vigente dos gestores | Concluída |
| FN-003 publicacao-e-homologacao | Homologação isolada (ADR-H1) | Concluída |
| FN-033 estrutura-escolar | Escolas operacionais para a autoridade de gestão | Parcial |

**Funcionalidades que dependem desta:** FN-027 (PA1-A), FN-025 (TE1), FN-029 (reuso de INEP/UF/município e solicitações), FN-032 (busca).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (CI1-A) Contrato e inventário | Fontes INEP/IBGE, sigla, validador | Contrato aprovado | Entregue (código/homologação) | PR #152; docs/119 |
| 1.2 (CI1-B2) Staging retomável | Carga nacional com lease e triggers | Carga retomável | Entregue (código/homologação) | PR #153; docs/120 |
| 1.3 (CI1-B3) Publicação versionada | Edições; conciliação só em relatório | Edição publicada | Entregue (código/homologação) | PR #154; docs/121 |
| 1.4 (CI1-C1..C5) Relações e cobertura | Sede/IES, lacunas | Relações carregadas | Entregue (código/homologação) | PR #155; docs/122 |
| 1.5 (CI1-D1..D4) Seleção pessoal | "Minhas instituições" | Testes de isolamento e concorrência | Entregue (código/homologação) | PR #157; docs/124 |
| 1.6 (CI1-E1..E5) Solicitações e manutenção | Protocolos pessoais, fila interna, cadastro manual, gestão | UI aprovada pelo revisor | Entregue (homologação) | PR #158; docs/125, 126, 129 |
| 1.7 (CI1-F1..F5) Validação integrada | 20 conexões, atualização, conferência no Chrome | Ensaio aprovado | Entregue (homologação) | PR #160; docs/130 |
| 1.8 Conciliação das 6 escolas operacionais sem INEP | De-para manual | Revisão humana | Pendente | docs/119, 121 |
| 1.9 8 lacunas e unidades adicionais | Resolução via CI1-E | — | Incerto | docs/122 |
| 1.10 Backlog de UI no celular | Estado da autoridade no select; tabela de solicitações | Conferência por texto | Pendente | docs/126, 129 |

## 6. Contrato técnico
- **Flag:** `CATALOGO_INTERFACE_ATIVA` (padrão `false`) na API e na web; desligada → `503 'Catálogo temporariamente indisponível.'` (API) ou `404` (web).
- **Permissões:** `catalogo.consultar`, `catalogo.referencia_pessoal.gerir`, `catalogo.solicitar` (só `professor_autonomo`); `catalogo.manter_escopo` (`admin_rede`, `dir_nucleo`, `dir_escolar`, com autoridade ativa em `catalogo_gestao_autoridades`); `catalogo.solicitacao.analisar` e `catalogo.manter_global` (Suporte e Desenvolvedor; Financeiro não).
- **API:** `/api/v1/catalogo/{ufs,municipios,instituicoes,instituicoes/{unidade},referencias-pessoais}`; `/api/v1/catalogo/minhas-solicitacoes`; `/api/v1/catalogo/atendimento`; `/api/v1/catalogo/gestao`. Throttle 60/min na leitura, 10/min nas gravações pessoais, `catalogo-operacao` nas demais.
- **Busca (`ConsultarCatalogoInstituicoes::buscar`):** exige UF, município, código ou texto de 3+ caracteres; `per_page` até 50; código exige `namespace` (`inep_escola` 8 dígitos, `inep_ies`, `plataforma`); texto por `LIKE '%termo%'` normalizado; duas consultas por requisição (contagem e página); edições fixadas por resposta.
- **Dados:** `catalogo_unidades`, `catalogo_instituicoes`, `catalogo_identificadores`, `catalogo_entradas`, `catalogo_observacoes`, `catalogo_municipios`, `catalogo_publicacoes`, `catalogo_fontes`, `referencias_instituicoes_pessoais`, solicitações e eventos append-only, `catalogo_gestao_autoridades`. Projeções públicas imutáveis; fonte `manual` com precedência por unidade.
- **Concorrência:** versão esperada em toda transição; chave de intenção; `409` em edição/versão mudada; ordem estável de locks.
- **Comandos:** `g360:catalogo-validar`, `g360:catalogo-preparar`, `g360:catalogo-promover`, `g360:catalogo-relacionar`, `g360:catalogo-relacoes-status`, `g360:catalogo-conciliar`.
- **Volume e desempenho (docs/200, homologação):** ~234 mil unidades, ~207 mil entradas (271 MB). Busca com UF: 0,13 s; busca nacional por texto: ~1,8 s após o buffer pool de 1 GB (F6-1). Sem medição com 1000 usuários simultâneos (FN-077). Otimização adiada (FN-032).

## 7. Interface
Menus: "Minhas instituições" e "Minhas solicitações" (contexto pessoal); "Cadastro de instituições" (atendimento interno); "Cadastro institucional" (gestão). Busca em formulário próprio com UF → município encadeados e descarte de respostas antigas; conferência em tela própria antes de adicionar; distinção entre escola de referência e sede administrativa de IES; aviso de cobertura limitada. Conferências registradas por texto nos docs/124, 126, 130. Backlog no celular: subfase 1.10.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção e fila acumulada (catálogo só na homologação) | Revogar o gate L4 em favor do ADR-H1; promoção em blocos | A do plano mestre | Pendente |
| Validação do responsável na homologação | — | Validar para passar a Concluída | Pendente |
| 6 escolas operacionais sem INEP; 8 lacunas | Revisão humana | Resolver antes da produção | Pendente |
| Busca nacional por texto lenta (docs/200) | Otimizar / adiar | Adiada em 09/11 (FN-032) | Decidida (adiar) |
| Retenção de protocolos e evidências das solicitações | — | Definir em FN-062; não herdar prazo de logs | Pendente |
| `secretario_educacao` sem `catalogo.manter_escopo` | — | Aguardar FN-017 | Pendente |
| docs/03 não lista as permissões de CI1-E (`catalogo.solicitar`, `manter_escopo`, `solicitacao.analisar`, `manter_global`) | Atualizar o docs/03 | Registrar a matriz em vigor | Pendente |

## 9. Ramos desta entrega
- Correção pontual do nome de um espaço em produção (docs/119 §1): encerrada.
- Experimento F6-1 (buffer pool 1 GB na VM 120): pertence a FN-077.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
