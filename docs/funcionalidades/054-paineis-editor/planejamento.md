# 054 — Personalização e editor dos painéis — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Observação de estado:** entregue e validada na homologação
**Prioridade no plano mestre:** fora das ondas (funcionalidade concluída). Está na fila de produção (§7, bloco "Painéis e editor"), aguardando a decisão §4 item 2. O §5 é PROPOSTA.
**Natureza:** existente documentada a partir do código
**Origem:** ramo de P2.9–P2.11 dos painéis de turmas (docs/179, 180); evoluiu para editor hierárquico próprio (docs/188–193)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Permitir que cada pessoa organize os painéis de Turmas e da Turma — ordem, largura e altura dos cards, seções e painéis pessoais — e encontre a mesma organização ao voltar, sem que a personalização altere o que ela está autorizada a ver.

## 2. Escopo
- **Inclui:** mover e redimensionar cards com GridStack; redistribuição lateral de largura entre vizinhos; controles (alça, fixar, altura independente, paleta); seções e cards centrais reordenáveis; editor hierárquico (composição + geometria) com SortableJS e GridStack; painéis e seções pessoais; gravação com controle de versão; toast flutuante e mensagens do editor (FT1–FT3).
- **Não inclui:**
  - Conteúdo, indicadores e projeção por permissão dos painéis → FN-053.
  - Personalização dos painéis por perfil (`/app/painel`) → não planejada; o editor foi feito para ser reutilizado por futuros dashboards (FN-052), sem subfase aberta.
  - Componente de toast para toda a aplicação → FN-076.

## 3. Fontes de verdade
- Documentos de origem: docs/179 (`docs/179-execucao-paineis-turmas.md`) (seções finais), docs/180 (`docs/180-correcao-dashboards-diretriz.md`), docs/188 (`docs/188-tw3-acoes-direita.md`) (última seção), docs/189 (`docs/189-paineis-secoes-cards.md`), docs/190 (`docs/190-previa-editor-paineis.md`), docs/191 (`docs/191-editor-paineis-integracao.md`), docs/193 (`docs/193-feedback-editor-toast.md`), [contratos/preferencias-paineis.md](../../contratos/preferencias-paineis.md).
- Não há mockup original; a prévia local do editor (docs/190) foi aprovada pelo responsável em 09/11/2026.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) (`turma.ver`).

**Atenção a identificadores:** as etapas "EI1–EI6" do docs/191 são do editor e não têm relação com EI1 = escolas independentes (FN-029). A tabela do docs/191 ainda marca EI4–EI6 como "Pendente"; vale o plano mestre (PR #227 mesclado).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-053 paineis-turmas (P1.1, P1.5) | Catálogo de componentes e preferências por escopo | Entregue (homologação) |
| FN-015 vinculos-contexto | A preferência é do vínculo e do recurso | Concluída |
| FN-018 seguranca-urls-sessao | BFF sem identificadores na URL; seletor da turma por cabeçalho | Em homologação |
| FN-075 tailwind-ui | Componentes e ações | Parcial |

**Funcionalidades que dependem desta:** FN-053 e FN-055 (novos cards entram pelo mesmo catálogo e editor); futuros dashboards.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 GridStack e preferências (linhas cheias → larguras livres → nativo compacto) | Primeiras versões | — | Substituída pela redistribuição lateral | PR #211–#215 |
| 1.2 Controles (alça, fixar, altura, paleta) | — | — | Entregue (homologação) | PR #214 |
| 1.3 Redistribuição lateral | Largura redistribuída entre vizinhos; mínimo 3 colunas | — | Entregue (homologação) | PR #216 |
| 1.4 Escala da prévia e cancelamento de gesto | Zoom 125%; sem listeners órfãos | — | Entregue (homologação) | PR #218, #219 |
| 2.1 (docs/189) Seções e cards centrais reordenáveis | `ordem_card`, `ordem_secao` | — | Entregue (homologação) | PR #226 |
| 2.2 (EI1–EI6 do docs/191) Editor hierárquico integrado | Contrato opt-in, integração, testes, migration aditiva, conferência | Editor publicado e validado | Entregue (homologação, validado) | PR #227 |
| 2.3 (FT1–FT3) Toast e mensagens do editor | — | — | Entregue (homologação) | PR #228 |
| 3.1 Produção | — | Decisão expressa | Pendente | §7 |

## 6. Contrato técnico (proporcional ao risco)
- **API**: `GET|PUT v1/paineis/preferencias/{painel}` (`turma.ver`, limitador `preferencias-paineis`); `painel` ∈ catálogo (`turmas`, `turma`). A turma vai no cabeçalho `X-G360-Painel-Turma` (só para `turma`); consultas não aceitam parâmetros de query; o modo editor é opt-in por cabeçalho e devolve `ETag` (304 com `If-None-Match`). Respostas `private, no-store`.
- **Web (BFF)**: `PreferenciasPainelController` (`paineis.preferencias.consultar|salvar`), cabeçalho `X-G360-Painel-Editor: 3`; valida o formato do `ETag` e o contrato (`ContratoPreferenciasPainel`).
- **Serviços**: `PreferenciasPainel`, `ComposicaoPainel`, `OrganizacaoPainel`, `PadroesPainel`, `BootstrapEditorPainel`, `CacheComposicaoPainel`.
- **Dados**: `2026_11_07_000001_registrar_preferencias_paineis` e `2026_11_09_000001_adicionar_composicao_paineis` (aditiva).
- **Concorrência**: versão obrigatória — 409 "A organização do painel mudou. Atualize antes de salvar."; chave do catálogo — 409 "O catálogo mudou. Consulte a disposição atual."; identificador de pedido repetido com conteúdo diferente — 409 "Identificador já usado em outra configuração.".
- **Autorização**: exige vínculo válido ("Selecione um vínculo válido.") e `turma.ver`; o recurso turma passa por `Escopo::turmas`. A preferência só ordena e dimensiona componentes já permitidos; ao ler, componentes não autorizados são filtrados.
- **Rollback**: limitado — um binário anterior ao docs/189 recusa preferências novas (registrado no docs/189).

## 7. Interface
Modo de organização nos painéis de Turmas e da Turma (`turmas/_organizar_painel`), quadro hierárquico do editor, toast flutuante. Rodapé oculto até 767 px (correção entregue junto com o editor, docs/191). Conferência registrada por texto; sem prints.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre | — | Aprovar §1–§3 e Onda 0 | Pendente |
| §4 item 2 — gate de produção e fila acumulada | Promover em blocos / manter | Promover junto com FN-053 | Pendente |
| Rollback limitado das preferências | Aceitar / planejar migração reversa | Aceitar; promover API e Web juntos | — |

## 9. Ramos desta entrega
As variações de layout (linhas cheias, larguras livres, nativo compacto) foram ramos internos e terminaram substituídas. Nenhum ramo aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
