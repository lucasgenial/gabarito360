# 035 — Dados dos responsáveis — Planejamento

**Estado no tronco:** Pendente
**Prioridade no plano mestre:** Onda 3 (PROPOSTA, §5) — depende de FN-016
**Natureza:** nova (há apenas o campo de texto livre `alunos.nome_responsavel`)
**Origem:** tronco original — docs/28 A6
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Registrar os responsáveis de cada aluno de forma estruturada, com contatos protegidos, para que a escola complete os dados pendentes e para que o portal dos responsáveis (FN-067) tenha a base de identificação de que precisa.

## 2. Escopo
- **Inclui:** tabelas `responsaveis` e `aluno_responsaveis`; contatos cifrados como campos opcionais nesta versão; inclusão no formulário do aluno e no arquivo de importação/exportação; relatório de alunos sem data de nascimento (docs/28 cita 54 na época).
- **Não inclui:**
  - Acesso do responsável, código de acesso e primeiro acesso → FN-067 (P1–P8).
  - Identidade pessoal e conciliação por CPF → FN-016.
  - Planilha de importação → FN-034 (recebe as colunas quando esta FN existir).
  - Retenção e pedidos de titulares → FN-062, FN-064.

## 3. Fontes de verdade
- Mockups: `mockups/aluno-cadastrar.html`, `aluno-cadastrar-redesign.html`, `aluno-detalhe.html` (campo "Responsável"; [docs/01](../../01-inventario-dos-mockups.md) §17, §19).
- Documento de origem: [docs/28](../../28-plano-mestre-proximos-modulos.md) A6; docs/33 (requisitos RL de dados de menores).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Perfis e permissões: [docs/03](../../03-perfis-e-permissoes.md) — hoje coberto por `aluno.gerir`/`aluno.ver`; chaves próprias a definir.
- Plano mestre: [§2 FN-035](../../planejamento/00-plano-mestre.md).

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-016 identidade-pessoal | Identificação do responsável como pessoa, sem duplicar contas | Parcial |
| FN-014 perfis-institucionais (A1) | Dependência declarada do A6 no docs/28 | Concluída |
| FN-063 lgpd-protecao-dados | Cifragem dos contatos | Concluída |

**Funcionalidades que dependem desta:** FN-067 portal-responsaveis (bloqueio explícito no plano mestre); FN-034 (colunas de responsável no arquivo).

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (A6-1, A6-2) | Tabelas `responsaveis` e `aluno_responsaveis`, contatos cifrados opcionais | Migration aditiva; sem perda do `nome_responsavel` atual | Pendente | nenhuma migration |
| 1.2 (A6-3) | Formulário do aluno e arquivo de importação/exportação | Escola completa os dados pendentes | Pendente | — |
| 1.3 (A6-4) | Relatório de alunos sem data de nascimento | Lista no escopo do ator | Pendente | — |

## 6. Contrato técnico (proporcional ao risco)
- **Existente:** `alunos.nome_responsavel` (texto, até 200, sem cifragem) aceito em `POST/PUT /v1/alunos` e exibido em `alunos/create`, `alunos/edit` e `alunos/show`.
- **Proposto:** migrations aditivas; contatos (telefone, e-mail) cifrados como o CPF (`DadoProtegido`); relação N:N com papel (mãe, pai, tutor etc.) e indicação de responsável principal; plano de migração do `nome_responsavel` sem conversão silenciosa.
- **Autorização:** chaves e matriz a definir e registrar no docs/03 antes da implementação.
- **Dados de menores:** tratamento conforme docs/33 e FN-061/FN-062.

## 7. Interface
- Proposta: bloco "Responsáveis" no cadastro, edição e detalhe do aluno, com adicionar, editar e remover; estados conforme FN-076.
- Mockup atual só prevê um campo "Responsável"; a forma estruturada precisa de aprovação visual.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 1 — aprovação do plano mestre e da priorização | Aprovar / ajustar | Aprovar §1–§3 e Onda 0 | Pendente |
| §4 item 9 — D10 (código do responsável) e D14 (revisão jurídica) bloqueiam o portal que consome esta FN | Responder D13/D14 primeiro | Confirmar D10 | Pendente |
| Destino do campo legado `nome_responsavel` (texto livre, não cifrado) | Migrar para a tabela / manter / remover | Decidir em 1.1 | Pendente |

## 9. Ramos desta entrega
Nenhum aberto.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
