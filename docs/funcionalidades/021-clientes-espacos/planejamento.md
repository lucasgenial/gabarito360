# 021 — Clientes e espaços (T1/T2) — Planejamento

**Implementação:** Concluída
**Etapa:** Concluída
**Prioridade no plano mestre:** sem posição nas ondas do §5 (concluída). Pendência remanescente (índices e particionamento) remetida a FN-077/X1.
**Natureza:** existente documentada a partir do código
**Origem:** tronco original — docs/28 Fase 3 (T1, T2); docs/40–54
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Separar quem contrata (cliente), onde os dados vivem (espaço) e quem acessa (vínculo), para que redes, núcleos, escolas e espaços pessoais compartilhem o mesmo MariaDB com isolamento explícito por `espaco_id`. Atende todos os perfis de forma indireta: é a base de autorização por escopo das demais funcionalidades.

## 2. Escopo
- **Inclui:** tabelas `clientes` e `espacos`; coluna `espaco_id` e `origem_autoria` nas tabelas pedagógicas; adaptador `Espacos` e filtros por espaço nos consumidores (alunos, turmas, provas, gabaritos, cartões, notas, fila B6, arquivos, painéis, relatórios, importadores); criação automática do espaço ao cadastrar núcleo ou escola; comandos de inventário, preenchimento, conciliação, auditoria, restrição e verificação do corte; ensaio e publicação do corte (release `af4c6d1`).
- **Não inclui:**
  - Vínculos por pessoa e seleção de vínculo (V1/V2) → FN-015.
  - Espaço pessoal e perfil `professor_autonomo` (T3) → FN-022.
  - Cadastro comercial de clientes (T4) → FN-023.
  - Cópias entre espaços (T5) → FN-024; transferências institucionais → FN-025.
  - Encerramento de cliente (T6) → FN-028.
  - Procedimento de publicação B5, backup final e restauração → FN-003.
  - Índices e particionamento para volume (X1) → FN-077 / FN-007.
  - Identidade global de pessoa e CPF com unicidade global → FN-016.

## 3. Fontes de verdade
- Documentos de origem: [docs/40](../../40-clientes-espacos-e-vinculos.md) (modelo T1), [docs/41](../../41-t1-mapa-de-consultas.md) (mapa de consultas), docs/42–54 (T2-A a T2-E, ensaios e publicação).
- Revisão posterior: [docs/115](../../115-replanejamento-provas-identidade-transferencias.md) (apêndice de 03/11 do docs/40 declara regras incompatíveis).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md). Relacionados no docs/07: UC-002 (Cadastrar Escola), UC-008 (Gerenciar Equipe).
- Sem mockup próprio: a funcionalidade não tem tela; altera o comportamento de todas as telas por escopo.
- Plano mestre: [§2 FN-021](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-001 fundacao-monorepo | Esquema base e apps API/WEB | Concluída |
| FN-002 base-operacao | Fila B6 contextualizada por espaço (T2-D3) | Concluída |
| FN-003 publicacao-e-homologacao | B5 `--manter-manutencao`, `--backup-final` e guarda de esquema usados no corte | Concluída |
| FN-063 lgpd-protecao-dados | L2/L3 citados como base do T1 | Concluída |

**Funcionalidades que dependem desta:** FN-015 (vínculos por espaço), FN-022 (espaço pessoal), FN-023 (cliente ↔ espaços), FN-024 (cópias entre espaços), FN-025 (transferências), FN-028 (encerramento por cliente), FN-029 (escola com cliente próprio), FN-031 (referências pessoais e autoridade por escola), FN-064 (pedidos de titulares por cliente/espaço).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (T1) Modelo e mapa de consultas | Cliente ≠ espaço ≠ vínculo; propriedade por espaço; plano de migração T2-A…E; inventário de consumidores | Documento aprovado | Entregue | PR #73 (`294f707`); docs/40, 41 |
| 1.2 (T2-A) Inventário e manifesto | `g360:inventariar-espacos` (somente leitura); manifesto LEGADO-REDE-1/2 | Execução read-only e manifesto aprovado | Entregue | PR #74; docs/42 |
| 1.3 (T2-B/C) Estrutura e preenchimento | Migration `2026_10_31_000006_expandir_clientes_e_espacos`; `g360:preencher-espacos` | Preenchimento retomável com manifesto | Entregue | PR #75; docs/43 |
| 1.4 (T2-D1..D4) Adaptação dos consumidores | Adaptador `Espacos`; alunos/turmas/provas; núcleos/escolas; cartões/notas/fila/arquivos; consultas pedagógicas e importadores | Consumidores filtrando por espaço | Entregue | PR #76–#79; docs/44–47 |
| 1.5 (T2-E) Integridade e corte | `g360:auditar-espacos`, `g360:restringir-espacos` (NOT NULL, FKs compostas, unicidade por espaço), job MariaDB | Auditoria sem divergências | Entregue | PR #80; docs/48 |
| 1.6 (T2) Preparação, validação, ensaio e checkpoint | Ensaio sintético, `g360:verificar-corte-espacos`, restauração, backup final | Ensaio aprovado | Entregue | PR #81–#84; docs/49–53 |
| 1.7 (T2) Publicação | Corte com 2 clientes e 13 espaços | Release publicada | Entregue (produção 01/11) | PR #85; release `af4c6d1`; docs/54 |

## 6. Contrato técnico
- **Modelo de dados:** `clientes` (tipo, modalidade, situação; dados de contato cifrados acrescentados em FN-023) e `espacos` (`cliente_id`, `tipo` ∈ rede/nucleo/escola/pessoal, referência ao registro de origem, `usuario_proprietario_id` no pessoal, situação). `espaco_id` em 13 tabelas pedagógicas com FKs compostas.
- **Configuração:** `apps/api/config/espacos.php` com `ativo => true` (corte publicado) e `pessoal => true` (FN-022).
- **Autorização:** `App\Support\Espacos`, `Escopo`, `Instituicoes` restringem consultas ao conjunto de espaços do vínculo em contexto. Com duas redes visíveis e nenhuma seleção, a API recusa a ambiguidade (`422 'Selecione a rede para esta operação.'`) em vez de escolher a primeira.
- **Criação de espaços:** `Instituicoes::criarEspaco` cria o espaço na mesma transação do núcleo ou da escola, herdando o cliente do pai (`EscolaController`, `NucleoController`). Só aceita `nucleo` e `escola`.
- **Comandos operacionais:** `g360:inventariar-espacos`, `g360:preencher-espacos --aplicar --manifesto`, `g360:conciliar-admin-espacos`, `g360:auditar-espacos`, `g360:restringir-espacos --escritores-parados`, `g360:verificar-corte-espacos`.
- **Desempenho:** consolidado da rede corrigido de 127 s para 259 ms na validação (docs/50). Medição com 1000 usuários simultâneos não foi feita para esta funcionalidade; segue em FN-077.

## 7. Interface
Sem tela própria. Efeito visível: listas, painéis e relatórios mostram apenas dados do espaço do vínculo selecionado; seleção de rede quando o usuário tem mais de uma (V2, FN-015). Conferência registrada por texto no docs/54.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 3 (política T5 × docs/115) e item 6 (matriz ID1): o §6 do plano mestre registra que a identificação por espaço, a proibição de CPF no pessoal e as capacidades da escola do docs/40 seguem a direção do docs/115, sujeitas a essas decisões | Manter regra por espaço / adotar identidade global (FN-016) | A do plano mestre: aprovar a matriz ID1 e declarar T5 substituída | Pendente |
| Unicidade de CPF/matrícula "por espaço" (T2-E) × CPF com unicidade global proposto no docs/115 §4 | Migrar via FN-016 sem remover as restrições atuais | Tratar em FN-016 (contrato de conciliação) | Pendente (FN-016) |
| Criação de espaço de **rede** e de espaço **pessoal** não tem caminho na aplicação: `criarEspaco` aceita só núcleo/escola e T4 não cria espaços (docs/71). Hoje só scripts de ensaio e o preenchimento legado inserem esses tipos | Registrar como pendência de FN-023/FN-022 ou como candidata | Levar ao responsável; divergência não registrada no plano mestre | Pendente |

## 9. Ramos desta entrega
- Procedimento B5 com corte, checkpoint de backup e restauração: nasceu aqui e virou infraestrutura de FN-003.
- `umask 022` no B5 (Incerto, provavelmente PR #86): acompanhado em FN-003.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
