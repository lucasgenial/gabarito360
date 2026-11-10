# 022 — Espaço pessoal e professor autônomo (T3) — Planejamento

**Estado no tronco:** Concluída (revisão pendente)
**Prioridade no plano mestre:** sem posição nas ondas do §5 (concluída). A revisão formal de T3 é pré-requisito de FN-027 (Onda 3).
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 Fase 3 (T3); docs/63–65
**Atualizado em:** 2026-11-09, por agente de documentação

## 1. Objetivo
Permitir que um professor use o Gabarito360 por conta própria, num espaço pessoal isolado, sem escola fictícia nem poderes institucionais. O perfil `professor_autonomo` existe somente no espaço pessoal do qual a conta é proprietária.

## 2. Escopo
- **Inclui:** catálogo de compatibilidade perfil × tipo de espaço (`PerfisEspaco`); perfil `professor_autonomo` com política própria (`CatalogoPermissoes::AUTONOMO`); turmas, alunos e provas com escola nula e `espaco_tipo = pessoal`; cadastro mínimo de aluno (campos vedados); seleção do vínculo pessoal, menu, painel, PDFs e fila; cabeçalho "Uso autônomo"; flag `espacos.pessoal = true`.
- **Não inclui:**
  - Tela de parâmetros pessoais (aprovação 6/10 e média 7/10 fixas) → pendência desta funcionalidade (subfase 1.5).
  - CPF opcional do aluno pessoal, conciliação e continuidade ao sair da escola (PA1-B/C/D) → FN-027.
  - Referências a instituições do catálogo ("Minhas instituições") → FN-031 (CI1-D) e FN-027 (PA1-A).
  - Exportação ou recebimento entre espaços → FN-024 (código atual) / FN-025 (política nova).
  - Portal dos pais → FN-067 (exige espaço escolar).
  - Contratação e provisionamento comercial do espaço → FN-023 (T4) e FN-068.
  - Rótulo "Professor independente" → FN-027.

## 3. Fontes de verdade
- Documentos de origem: [docs/63](../../63-t3-perfis-por-espaco.md), [docs/64](../../64-t3-b-dados-e-formularios-pessoais.md), [docs/65](../../65-t3-c-integracao-pessoal.md).
- Revisão posterior: [docs/115 §10](../../115-replanejamento-provas-identidade-transferencias.md) (PA).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md), atualização T3 de 01/11/2026.
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Relacionados no docs/07: UC-001, UC-003, UC-004, UC-005, UC-007, UC-010.
- Mockups: reaproveita as telas do professor (`mockups/` do professor); sem mockup próprio para o espaço pessoal.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-021 clientes-espacos | Tipo de espaço `pessoal` e isolamento por `espaco_id` | Concluída |
| FN-015 vinculos-contexto | Vínculo do proprietário e seleção por aba (V2-C) | Concluída |
| FN-012 permissoes-motor | Política de permissões do perfil | Concluída |
| FN-045 omr-correcao | Fila e correção no espaço pessoal | Concluída |

**Funcionalidades que dependem desta:** FN-027 (professor independente, revisão de T3), FN-031 (referências pessoais exigem contexto pessoal), FN-024 (cópias com origem/destino pessoal), FN-064 (exportação autorizada pelo proprietário), FN-025 (professor autônomo na matriz TR).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (T3-A) Compatibilidade de perfis | `PerfisEspaco`, perfil `professor_autonomo`, bloqueio do perfil global | Perfil só no próprio espaço pessoal | Entregue (produção 02/11) | PR #96; docs/63 |
| 1.2 (T3-B) Dados e formulários pessoais | Escola nula só no pessoal (triggers + FK `espaco_tipo`); cadastro mínimo sem CPF; snapshot `cartoes.turma_aplicacao_id` | Constraints e formulários validados em MariaDB | Entregue (produção 02/11) | PR #97; migration `2026_11_01_000006_preparar_dados_pessoais`; docs/64 |
| 1.3 (T3-C) Integração | Seleção, menu, painel, PDFs, fila; `espacos.pessoal = true` | `IntegracaoPessoalTest` e testes web | Entregue (produção 02/11) | PR #99; docs/65; docs/100 |
| 1.4 Revisão formal de T3 | Rever constraints/termos que proíbem CPF no aluno pessoal (docs/115 §10) | Decisão registrada antes de PA1-B | Pendente | §3 do plano mestre (022 → 027) |
| 1.5 Tela de parâmetros pessoais | Metas próprias do espaço pessoal | Tela aprovada e parâmetros usados nos relatórios | Pendente | docs/64 ("não há tela nova de parâmetros") |

## 6. Contrato técnico
- **Habilitação:** `PerfisEspaco::habilitado('professor_autonomo')` exige `config('espacos.pessoal')`; `compativel()` exige `espaco.tipo = pessoal` e `usuario_proprietario_id` igual à conta.
- **Permissões padrão (sem extras):** `turma.ver/gerir/desativar`, `aluno.ver/gerir/desativar`, `prova.ver/criar/editar`, `gabarito.ver/editar`, `cartao.imprimir/enviar`, `correcao.acompanhar/alterar/fechar`, `resultado.ver/compartilhar`, `relatorio.prova/turma`; acrescidas depois: `prova.duplicar_propria` (FN-042), `catalogo.*` (FN-031), `pedido.exportacao.autorizar` (FN-064), `copia.autorizar_origem/destino` (FN-024).
- **Dados vedados no aluno pessoal:** `EspacoPessoal::VEDADOS` = `cpf`, `cpf_hash`, `data_nascimento`, `genero`, `nome_responsavel`, `foto`, `foto_path`, com reforço por trigger. O retrato foi autorizado depois (PF1-C, docs/03) por `validarAluno(..., aceitarRetrato: true)`.
- **Movimentação de turma no pessoal:** feita pela edição do aluno (`AtualizarAlunoPessoal`), sem registro em `aluno_movimentacoes` (ver FN-026).
- **Relatórios:** cabeçalho com nome do espaço e "Uso autônomo"; aprovação 6/10 e média 7/10 fixas.
- **Desempenho:** sem medição com 1000 usuários simultâneos; segue em FN-077.

## 7. Interface
Seleção do vínculo pessoal em `/app/vinculos`; menu móvel prioriza Painel, Corrigir, Provas e Turmas; Alunos no menu completo; sem gestão de equipe, escolas ou associação de professores. Conferência por texto no docs/65 (o diálogo JavaScript de publicação não teve confirmação visual pela automação).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 3 e item 6 (o §6 do plano mestre aponta que as regras de docs/63–65 sobre CPF no pessoal seguem a direção do docs/115, sujeitas a essas decisões) | Manter proibição / permitir CPF opcional protegido | A do plano mestre: aprovar matriz ID1; rever T3 antes de PA1-B (FN-027) | Pendente |
| Provisionamento do espaço pessoal: não há fluxo na aplicação que crie cliente/espaço pessoal (T4 não cria espaços; `Instituicoes::criarEspaco` só aceita núcleo/escola). O plano mestre lista como pendências apenas a tela de parâmetros e a revisão de constraints | Subfase nova em FN-022, item de FN-023 ou candidata | Levar ao responsável | Pendente |
| Termos de uso (`legal/termos`) afirmam cadastro limitado a nome, turma e identificação simples; mudar em PA1-B exige revisar o texto (FN-061) | — | Tratar junto com a revisão formal de T3 | Pendente |

## 9. Ramos desta entrega
- Correção do painel do professor (PR #98) e reconciliação operacional antes do deploy (docs/operacao/incidente-painel-professor-2026-11-01.md): encerrada no docs/100.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | agente de documentação |
