# 064 — Pedidos dos titulares (L4) — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Prioridade no plano mestre:** Onda 3 (proposta): "064 pedidos-titulares (L4-D, L4-E) + pacote de retenção (062, 066, 004 expurgo)"
**Natureza:** existente documentada a partir do código (L4-A a C5); L4-D e L4-E novas
**Origem:** tronco original — docs/28 Fase 2, pacote L4, replanejado em L4-A..E no docs/102; requisitos RL3–RL6 do docs/33
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Atender pedidos de titulares (acesso, correção, anonimização, eliminação, informação) com protocolo, conferência de identidade e de autoridade, decisão registrada e, para pedidos de acesso, exportação controlada dos dados. Atende titulares, instituições controladoras e a equipe interna que opera o atendimento.

## 2. Escopo
- **Inclui:** protocolo com máquina de estados (`EstadoPedidoTitular`), abertura por Desenvolvedor, atribuição a responsável interno (Desenvolvedor ou Suporte), triagem, conferências de identidade e autoridade por ciclo, decisão; vínculo do protocolo ao aluno (sujeito); concessão de exportação por Administrador da Rede ou professor autônomo proprietário; geração cifrada no formato `cadastro_minimo_v1` (nome e matrícula); consulta e download pelo responsável; interface web; L4-D correção e avaliação de retenção/preservação; L4-E simulação de anonimização/eliminação e ensaio de recuperação; retenção própria dos protocolos; produção.
- **Não inclui:** abertura do pedido pelo próprio titular em canal público (não planejada; hoje a entrada é interna); encerramento de cliente (FN-028, T6); política geral de retenção (FN-062); contrato de conciliação de identidade (FN-016); formatos de exportação além de dois campos (exigirão novo formato e armazenamento).

## 3. Fontes de verdade
docs/28 L4 (`docs/28-plano-mestre-proximos-modulos.md`); docs/102 (`docs/102-planejamento-l4-t6.md`); docs/103 (`docs/103-l4-a-contrato-protocolos.md`) a docs/112 (`docs/112-l4-c5-interface.md`); docs/115 (`docs/115-replanejamento-provas-identidade-transferencias.md`) §1 e §12; docs/117 (`docs/117-r0-conciliacao-e-marcos-de-entrega.md`); docs/129 (`docs/129-h1-publicacao-ci1-e.md`); docs/03 (`docs/03-perfis-e-permissoes.md`) seções L4-B1, L4-B2 e concessão de exportação.
Código: API `routes/api.php` (rotas `pedidos-titulares*` e `autorizacoes-exportacao*`), `Services/PedidosTitulares.php`, `AutorizacoesExportacaoTitular.php`, `GeracaoExportacaoTitular.php`, `EntregaExportacaoTitular.php`, `Support/EstadoPedidoTitular.php`, `EstadoExportacaoTitular.php`, `config/pedidos_titulares.php`, migrations `2026_11_02_000007..000011`. Web `routes/web.php` (`/pedidos-titulares*`, `/exportacoes-titulares*`), `PedidoTitularController`, `ExportacaoTitularController`, views `pedidos/*`, `config/services.php` (`pedidos.interface_ativa`, `pedidos.exportacoes`). Sem mockup (interface interna). Casos de uso: [casos-de-uso.md](casos-de-uso.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-013 acessos internos (A1) | Atendimento só por Desenvolvedor/Suporte com acesso interno vigente | Concluída |
| FN-004 auditoria (AU1) | Eventos de protocolo e exportação | Parcial (AU1 entregue) |
| FN-016 identidade pessoal | L4-D precisa do contrato de conciliação de identidade (§4 item 12) | Parcial |
| FN-026 matrícula e enturmação | L4-D/E dependem do histórico de matrícula (docs/115) | Pendente |
| FN-062 e decisão §4 item 11 | Prazos e política de retenção aplicáveis à correção/eliminação | Parcial / pendente |
| Decisão §4 item 2 | Gate "nada em produção antes de fechar L4" × ADR-H1 | Pendente |

**Funcionalidades que dependem desta:** FN-028 encerramento de cliente (T6 depende de L4 completo); FN-067 portal (RL3–RL6 atendidos antes do piloto, docs/38 §8).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 4.1 (L4-A) | Contrato e máquina de estados do protocolo | Transições e resultados terminais testados | Entregue (homologação) | PR #139; docs/103 |
| 4.2 (L4-B1) | Persistência, abertura por Desenvolvedor, atribuição, triagem e idempotência; flag `PEDIDOS_TITULARES_ATIVOS` | Recibos idempotentes; acesso só do responsável vigente | Entregue (homologação) | PR #140; docs/104 |
| 4.3 (L4-B2) | Conferências de identidade e autoridade por ciclo, análise e conclusão | Conclusão exige as duas conferências vigentes | Entregue (homologação) | PR #141; docs/105 |
| 4.4 (L4-B3) | Interface web de atendimento; flag `PEDIDOS_TITULARES_INTERFACE_ATIVA` | Telas de lista, abertura, detalhe e ações | Entregue (homologação) | PR #142; docs/106 |
| 4.5 (L4-C1) | Contrato do artefato de exportação | Estados do artefato testados | Entregue (homologação) | PR #143; docs/107 |
| 4.6 (L4-C2) | Vínculo do sujeito; concessão por admin_rede/professor_autonomo | Permissão `pedido.exportacao.autorizar`; revogação | Entregue (homologação) | PR #144, #145; docs/108, 109 |
| 4.7 (L4-C3) | Geração síncrona cifrada `cadastro_minimo_v1` | Lista positiva nome/matrícula; limite de bytes | Entregue (homologação) | PR #146; docs/110 |
| 4.8 (L4-C4) | Consulta e download autenticados com integridade | Manifesto e hash conferidos na entrega | Entregue (homologação) | PR #147; docs/111 |
| 4.9 (L4-C5) | Interface de exportação e ensaio multiprocessos MariaDB | Telas e CI MariaDB | Entregue (homologação) | PR #148; docs/112 |
| 4.10 (L4-D) | Correção e avaliação de retenção/preservação | Prazo e fundamento por instituição; sem política, sem eliminação; correção preserva histórico pedagógico | Pendente | `AvaliacaoGuardaTitular` ausente; docs/113 inexistente |
| 4.11 (L4-E) | Simulação de anonimização/eliminação e ensaio de recuperação | Relatório de impacto por tabela/arquivo; só dados sintéticos | Pendente | — |
| 4.12 | Retenção própria dos protocolos e eventos | Prazo aprovado e rotina | Pendente | §4 item 11 |
| 4.13 | Produção | Flags ligadas em produção por decisão expressa | Pendente | gate §4 item 2 |

## 6. Contrato técnico (proporcional ao risco)
- Quatro flags na API (`PEDIDOS_TITULARES_ATIVOS`, `PEDIDOS_EXPORTACAO_AUTORIZACOES_ATIVAS`, `PEDIDOS_EXPORTACAO_GERACAO_ATIVA`, `PEDIDOS_EXPORTACAO_ENTREGA_ATIVA`) e duas na web (`PEDIDOS_TITULARES_INTERFACE_ATIVA`, `PEDIDOS_EXPORTACOES_INTERFACE_ATIVA`), todas desligadas por padrão; ativas só na homologação (docs/129).
- Toda escrita usa chave UUID de idempotência por acesso e versão de autorização; reenvio com outros dados devolve 409. Concorrência serializada por `acessos_internos_controle` com `lockForUpdate` e versão otimista do protocolo (409 "Protocolo alterado. Atualize a consulta.").
- Identificação declarada, relato, motivos, evidências e resposta cifrados com `Crypt`; hashes com `DadoProtegido` (FN-063).
- Limites por rota: `throttle:10,1` em escritas e downloads, `throttle:30,1` em consultas. Lista paginada (20 por página), sem busca livre por identidade.
- Concessão limitada pela menor validade entre conferências, vínculo do aprovador, acesso do responsável e prazo de atribuição.
- L4-D/E: reconciliar com ID1 (FN-016), MT1 (FN-026) e TE1 (FN-025) conforme docs/115; arquivos de guarda do docs/113 ficaram fora do Git.

## 7. Interface
Telas internas em `pedidos/*` (lista, novo, detalhe, envio, recibo, exportações). Mensagens de erro: "Confira os campos obrigatórios e os limites do formulário.", "Consulta indisponível.", "Não foi possível consultar os pedidos.". Conferência visual de UX ainda segue o padrão anterior às tabelas TBL2 e ao Tailwind; migração ocorre se a entrega tocar as telas (trilha FN-075/076). Conferência registrada por texto, sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 2 — gate de produção | Manter "nada em produção antes de fechar L4" (docs/102) ou adotar ADR-H1 | Revogar o gate L4 em favor do ADR-H1, mantendo as flags de L4 desligadas em produção | Pendente |
| §4 item 11 — LGPD operacional | Política de retenção dos protocolos L4, prazos abertos | Pacote único de retenção na Onda 3 | Pendente |
| §4 item 12 — contrato de conciliação de identidade | Escrever como subfase de FN-016 | Onda 1 | Pendente |
| §4 item 6 — matriz ID1 | Aprovar `identidade.*.consultar` | Aprovar (destrava L4-D) | Pendente |
| Proposta de guarda (docs/113) | Versionar a proposta ou reescrever | Versionar dentro do contrato de 4.10 | Pendente |
| Canal do titular | Hoje só o Desenvolvedor abre protocolos a partir de pedido recebido por outro canal | Registrar como candidata se for desejado canal próprio | Não discutido |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
