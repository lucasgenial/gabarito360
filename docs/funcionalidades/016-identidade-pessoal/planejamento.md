# 016 — Identidade pessoal (ID1) — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Conforme princípios:** versão 1
**Prioridade no plano mestre:** Onda 1, ordem 4 (fechamento + contrato de conciliação); os dois documentos desta pasta fazem parte da Onda 0. Proposta ainda não aprovada (§5).
**Natureza:** existente documentada a partir do código (fundação) com parte nova (operação e conciliação)
**Origem:** docs/115 §4, docs/138–140
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Criar a identidade canônica de pessoa, acima das contas e dos cadastros locais: uma pessoa pode ter conta de acesso, ser aluna em uma escola e professora em outra, sem que o CPF vire chave, URL ou senha. A fundação serve aos convites a partir do CPF, às transferências, ao professor independente e aos pedidos de titulares.

## 2. Escopo
- **Inclui:** tabelas `pessoas`, `pessoa_documentos`, `pessoa_documento_indices`, `pessoa_identidade_eventos`; busca exata interna por índice protegido; inventário somente leitura do legado; referências provisórias por conta e por aluno (`pessoa_usuarios`, `pessoa_alunos`); capacidades operacionais de consulta; contrato de conciliação global e backfill.
- **Não inclui:**
  - Cadastro e convite a partir do CPF: FN-017.
  - Transferências institucionais e de alunos: FN-025.
  - Matrícula temporal: FN-026.
  - CPF opcional do professor independente: FN-027.
  - Correção e anonimização de pedidos de titulares (L4-D/E): FN-064.
  - Dados dos responsáveis: FN-035.

## 3. Fontes de verdade
- Mockups: nenhum; não há interface nesta fundação.
- Origem: docs/115 §4 (`docs/115-replanejamento-provas-identidade-transferencias.md`), docs/138 (`docs/138-id1-fundacao-identidade.md`), docs/139 (`docs/139-id1-dc1-ativacao-e-sequencia.md`), docs/140 (`docs/140-id1-referencias-provisorias.md`).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) (as chaves `identidade.*` ainda não constam).
- Código: migrations `2026_11_04_000001_fundacao_identidade_pessoal` e `2026_11_04_000002_mapas_identidade_provisoria`; `apps/api/app/Services/ConsultaIdentidadeInterna.php`, `InventarioIdentidades.php`, `PrepararIdentidadesProvisorias.php`; `apps/api/app/Support/DocumentoIdentidade.php`; comandos `g360:identidades-inventariar` e `g360:identidades-provisorias`; `config/identidade.php`.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| R0 conciliação documental (sem FN) | Pré-requisito do docs/138 | Concluída (PR #150) |
| FN-063 lgpd-protecao-dados | Cifra e índice protegido dos documentos | Concluída |
| FN-012 permissoes-motor | Novas chaves `identidade.usuario.consultar` e `identidade.aluno.consultar` | Concluída (chaves pendentes de decisão) |
| FN-004 auditoria | Consulta de identidade exige motivo e trilha | Parcial |

**Funcionalidades que dependem desta:** FN-017, FN-025, FN-027, FN-035, FN-064 (L4-D) e, por consequência, FN-067 e FN-020.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (ID1-A) | Esquema aditivo de pessoas e documentos | Cifra, índice, unicidade e imutabilidade testadas em SQLite e MariaDB | Entregue (homologação) | PR #168 |
| 1.2 (ID1-B técnico) | Busca exata interna por índice | Sem HTTP nem listagem; retorno mínimo | Entregue (código) | docs/139; `ConsultaIdentidadeInterna` |
| 1.3 (ID1-C técnico) | Inventário de conciliação | Somente leitura; saída agregada | Entregue (código) | docs/139; `InventarioIdentidades` |
| 1.4 (ID1-D1) | Referências provisórias | Uma referência por conta/cadastro, sem fusão; flag desligada | Entregue (código) | PR #170 |
| 2.1 (ID1-B/C operacionais) | Capacidades de consulta de identidade | Matriz aprovada e chaves nos seeds | Pendente | decisão §4 item 6 |
| 2.2 | Contrato de conciliação global | Comprovação admitida, revisão, correção de associação errada | Pendente | decisão §4 item 12 |
| 2.3 (ID1-D) | Backfill real e conciliação | Só depois do contrato e com a flag autorizada | Pendente | proibido até contrato (docs/140–142) |

## 6. Contrato técnico (proporcional ao risco)
- **Modelo:** `pessoas` (uuid, situação `provisoria`/`documentada`); `pessoa_documentos` (tipo `CPF`, país `BR`, valor cifrado, situação `declarado`); `pessoa_documento_indices` (versão, índice de 64 hex, único por tipo/país/versão); eventos `provisoria_criada` e `documento_declarado`. Gatilhos validam o índice e tornam os mapas `pessoa_usuarios`/`pessoa_alunos` imutáveis (sem UPDATE nem DELETE).
- **Sem HTTP:** nenhuma rota expõe identidade. A busca por CPF existe só como serviço interno.
- **Flag:** `IDENTIDADE_PROVISORIOS_ATIVOS` (desligada); `g360:identidades-provisorias` simula por padrão e só grava com `--aplicar` e a flag ligada; lote de 1 a 1000.
- **Privacidade:** consulta exata, com motivo, limite de tentativas e retorno mínimo; nunca fundir por nome ou matrícula; CPF nunca em URL, log ou chave primária.
- **Volume:** inventário limitado a 100000 registros por execução.

## 7. Interface
Nenhuma nesta fase. As telas de consulta nascem em FN-017 (cadastro a partir do CPF).

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 6 — Matriz de permissão de ID1 | Aprovar `identidade.usuario.consultar` e `identidade.aluno.consultar`, extras do vice-diretor e comprovação admitida | Plano mestre: aprovar (destrava VI1, TR1/TE1 e L4-D) | Pendente |
| §4 item 12 — Contrato de conciliação de identidade | Ligar a flag e permitir backfill só depois do contrato | Plano mestre: escrever o contrato como subfase desta funcionalidade na Onda 1 | Pendente |
| Hash de CPF do legado depende da máscara | Corrigir em massa / tratar no ID1-D | docs/138: não corrigir antes de resolver concorrência, verificação e mapeamentos | Pendente |

Nenhuma divergência entre o plano mestre e o código encontrada.

## 9. Ramos desta entrega
ID1-D1 (referências provisórias) é subdivisão interna do ID1-D; o docs/140 pede que não seja publicada como fechamento de ID1.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | documento criado a partir do inventário e do código | lucasgenial |
