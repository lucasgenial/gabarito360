# 052 — Painéis por perfil — Planejamento

**Estado no tronco:** Concluída
**Prioridade no plano mestre:** fora das ondas (funcionalidade concluída). Pendências do §3: confirmar MP-040 e SEGES; variantes por perfil (P2.4, tratadas em FN-053). O §5 é PROPOSTA.
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/13 MP-006..011; docs/19 MP-040; docs/27 G3 (gráficos e barra de progresso)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Dar a cada pessoa, logo após escolher o vínculo, um painel inicial com os indicadores e atalhos do seu escopo: rede, núcleo, escola, turmas próprias ou resultados próprios.

## 2. Escopo
- **Inclui:** painel inicial em `/app/painel` para Administrador da Rede, Diretor de Núcleo, Diretor Escolar (e Vice-diretor), Coordenador, Professor (e Professor autônomo), Aluno, Secretário de Educação e Aplicador; seleção de contexto quando a pessoa tem mais de uma unidade; leitura do registro de sincronização SEGES no painel do administrador.
- **Não inclui:**
  - Painéis de Turmas e da Turma, com composição e indicadores próprios → FN-053; personalização → FN-054.
  - Painel do secretário escolar (`dashboard.secretario-escolar`) → FN-014; painel interno (`dashboard.interno`) → FN-013.
  - Agenda e visitas pedagógicas mostradas nos mockups → FN-056 e FN-058 (UC-009).
  - Acesso próprio do aluno (login e vínculo usuário↔aluno) → FN-020.
  - Integração real com a SEGES → sem plano (ver item 8).

## 3. Fontes de verdade
- Mockups: `mockups/dashboard-admin.html`, `dashboard-diretor-nucleo.html`, `dashboard-diretor-escolar.html`, `dashboard-coordenador.html`, `dashboard-professor.html`, `dashboard-aluno.html` ([docs/01](../../01-inventario-dos-mockups.md) seções 02–07). Secretaria e aplicador não têm mockup próprio (vieram com o MP-026).
- Documentos de origem: [docs/13](../../13-roadmap.md) (MP-006..011), [docs/19](../../19-plano-telas-por-perfil.md) (MP-040), [docs/27](../../27-plano-graficos-echarts.md) (G3), [docs/04](../../04-requisitos-funcionais.md) RF-009.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md); UC-007 do [docs/07](../../07-casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) §3 (escopos por perfil).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-011 autenticacao-conta / FN-015 vinculos-contexto | Sessão e vínculo escolhido definem o perfil e a referência | Parcial / Concluída |
| FN-012 permissoes-motor | As 8 rotas `dashboard/*` ainda usam o middleware `perfil:`; a migração para permissões é pendência de FN-012 | Concluída (pendência aberta) |
| FN-033 estrutura-escolar | Redes, núcleos, escolas, turmas e alunos | Parcial |
| FN-045 e FN-049 | Progresso de correção, notas e atalhos para relatórios | Concluídas |
| FN-070 identidade-visual | Gráficos ECharts e barra de progresso (G3) | Concluída |

**Funcionalidades que dependem desta:** FN-058 (visitas no painel do núcleo), FN-056 (agenda nos painéis), FN-020 (painel do aluno com acesso próprio), FN-053 (variantes por perfil, P2.4).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (MP-006..011) Painéis dos 6 perfis | Admin, núcleo, escolar, coordenador, professor, aluno | Painel com dados do escopo | Entregue (produção) | commits MP-006..011 (c3b359b, d861be5, 281bf12, ecf8dcf, 7298b9c, d6f8316) |
| 1.2 (MP-040) Painel do diretor escolar e do aplicador | — | — | Incerto | sem commit identificável com "MP-040" (ver item 8) |
| 1.3 Alertas SEGES | Sincronização e alerta no painel do administrador | Integração operante | Incerto | só o model `SincronizacaoSeges` e a leitura no painel; sem integração |

## 6. Contrato técnico (proporcional ao risco)
- **API** (`DashboardController`): `GET v1/dashboard/admin` (`perfil:admin_rede`), `dir-nucleo` (`dir_nucleo`), `dir-escolar` (`dir_escolar,vice_dir_escolar`), `coordenador`, `professor` (`professor,professor_autonomo`), `aluno`, `secretaria` (`secretario_educacao`), `aplicador`.
- **Web**: `DashboardController::index` (rota `painel`, `/app/painel`) escolhe a vista pelo perfil do vínculo; parâmetros opcionais `escola_id`, `nucleo_id`, `aluno_id`, `secretaria_id`.
- **Referência**: `Instituicoes::selecionarReferencia` resolve a unidade; com mais de uma, a API responde 422 "Selecione o contexto da consulta." com as opções, e o Web mostra `dashboard.selecionar-contexto`. Sem referência: 403 ("Usuário não vinculado a nenhuma escola." e equivalentes).
- **Consultas**: `ConsultasPedagogicas` por espaço; lotes e EXISTS de F2 (FN-077). Testes `DashboardDesempenhoContratoTest`, `F2DashboardLotesTest`.
- **Aplicador**: só provas com data de aplicação igual a hoje, com total de alunos e cartões enviados; nunca notas.
- **Desempenho**: medição com 1000 usuários simultâneos pendente em FN-077.

## 7. Interface
Vistas `resources/views/dashboard/*.blade.php`. Estado sem dados quando a API falha (`dashboard` nulo). Layout responsivo (FN-073). Sem prints; conferências por texto.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre | — | Aprovar §1–§3 e Onda 0 | Pendente |
| §4 item 10 — login do aluno (decisão 6.4 do docs/19) | Decidir com o portal (FN-067) | Seguir a recomendação do §4; o painel do aluno já resolve o aluno pela referência do vínculo | Pendente |
| ⚠ MP-040 "Incerto" no plano mestre: no código existem o painel do aplicador (API `dashboard/aplicador`, vista `dashboard.aplicador`), introduzido no commit 917dd81 (MP-026), e o painel do diretor escolar (MP-008, 281bf12), também usado pelo vice-diretor | Declarar MP-040 atendido por MP-008 + MP-026 / manter Incerto até conferir "configurações da escola para DIR_ESC" (FN-019) | Conferir e registrar; não alterado nesta documentação | Pendente |
| SEGES sem integração | Planejar integração / retirar o alerta | Decidir; a API SEGES não está documentada (docs/16) | Pendente |
| Rotas por `perfil:` em vez de permissão | Migrar (pendência de FN-012) | Migrar junto com as variantes por perfil | Pendente |

## 9. Ramos desta entrega
Os painéis de Turmas e da Turma, a agenda e o acompanhamento nasceram daqui e viraram FN-053 a FN-056.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
