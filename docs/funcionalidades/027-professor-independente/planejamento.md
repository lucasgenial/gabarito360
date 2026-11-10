# 027 — Professor independente (PA1) — Planejamento

**Implementação:** Parcial
**Etapa:** Em planejamento
**Prioridade no plano mestre:** Onda 3 — Ciclo de vida de dados pessoais e institucional (depende de FN-016 e da revisão formal de T3)
**Natureza:** evolução de existente (FN-022)
**Origem:** docs/115 §10 e §12 (PA1), docs/124 (CI1-D)
**Atualizado em:** 2026-11-09, por lucasgenial

## 1. Objetivo
Evoluir o espaço pessoal do professor: organizar turmas e disciplinas pelas instituições em que ele declara atuar, permitir identificação opcional e protegida do aluno, integrar seus dados a uma escola por convite e conciliação, e preservar o que lhe cabe quando sai da escola.

## 2. Escopo
- **Inclui:**
  - PA1-A: uso da referência pessoal ao catálogo (FN-031, "Minhas instituições") para agrupar turmas e disciplinas do espaço pessoal.
  - PA1-B: cadastro mínimo com CPF opcional, protegido e verificado no aluno pessoal.
  - PA1-C: integração pessoal → escola com conciliação de identidade.
  - PA1-D: continuidade ao sair da escola ou ao encerrar o contrato.
  - Rótulo de interface "Professor independente" (sem renomear o enum `professor_autonomo`).
- **Não inclui:**
  - Seleção e manutenção das referências pessoais ("Minhas instituições") → FN-031 (CI1-D, entregue).
  - Base do espaço pessoal → FN-022.
  - Identidade de pessoa, CPF e conciliação → FN-016.
  - Matriz de autoridade e execução de cessões → FN-025 (TR1).
  - Convite de vínculo CPF-primeiro → FN-017.
  - Escola autônoma (modalidade institucional) → FN-029.

## 3. Fontes de verdade
- Documentos de origem: docs/115 §10, §13 (`docs/115-replanejamento-provas-identidade-transferencias.md`), docs/116 (`docs/116-catalogo-nacional-instituicoes.md`), docs/124 (`docs/124-ci1-d-selecao-pessoal.md`) ("o uso da referência no cadastro de turmas pertence ao PA1").
- Regras a rever: docs/63–65 (`docs/64-t3-b-dados-e-formularios-pessoais.md`) (T3).
- Perfis e permissões: docs/03 (`docs/03-perfis-e-permissoes.md`) (T3, CI1-D).
- Casos de uso: [casos-de-uso.md](casos-de-uso.md).
- Mockups: sem mockup; telas de turma do professor como base.

## 4. Dependências
| Depende de | Por quê | Situação |
|---|---|---|
| FN-022 espaco-pessoal | Base; revisão formal de T3 (constraints e termos) antes de PA1-B | Concluída (revisão pendente) |
| FN-031 catalogo-instituicoes | Referências pessoais (PA1-A) | Em homologação |
| FN-016 identidade-pessoal | CPF protegido, provisórios e conciliação (PA1-B/C) | Parcial |
| FN-025 transferencias-institucionais | Matriz TR para integração e continuidade (PA1-C/D) | Pendente |
| FN-061 lgpd-termos-privacidade | Termos afirmam cadastro pessoal mínimo; mudam com PA1-B | Parcial |

**Funcionalidades que dependem desta:** nenhuma declarada no plano mestre.

## 5. Fases e subfases
| Subfase | Entrega | Critério de pronto | Status | Evidência |
|---|---|---|---|---|
| 1.1 (PA1-A) Referências ao catálogo | Seleção de instituições (feita em CI1-D) e uso em turmas/disciplinas | Turmas pessoais agrupadas por referência; nomes iguais não conciliam | Parcial (seleção entregue; uso pendente) | PR #157; docs/124 |
| 1.2 (PA1-B) Mínimo e CPF opcional | Revisão de T3; CPF opcional protegido | Constraints e termos revistos; gravação só após decisão | Pendente | docs/115 §10 |
| 1.3 (PA1-C) Conciliação | Integração pessoal → escola por convite e conciliação | Sem fusão automática; proveniência preservada | Pendente | docs/115 §10 |
| 1.4 (PA1-D) Continuidade | Prévia e autorização do que fica com o professor ao sair | Sem exportação retroativa; sem perda silenciosa | Pendente | docs/115 §10 |

## 6. Contrato técnico
- **Existente aproveitável:** `referencias_instituicoes_pessoais` (FN-031); `ContinuidadeCopia::avaliar` e `EncerrarVinculoProfessor` (T5-D1, sem rota, política antiga — FN-024).
- **Proposto:** coluna de referência pessoal em turmas do espaço pessoal (FK composta ao espaço); relaxamento controlado do trigger de `2026_11_01_000006_preparar_dados_pessoais` e de `EspacoPessoal::VEDADOS` para CPF, somente após a revisão formal; conciliação com recibo e rollback por unidade.
- **Migrations:** aditivas; nenhuma associação automática de turmas existentes.

### Detalhamento herdado do docs/115

Regras de PA1 (docs/115 §10). Os itens são propostas.
- Nome técnico atual: `professor_autonomo`. Rótulo de interface proposto: Professor independente. Padronizar a apresentação sem renomear enum nem FKs de uma vez.
- A contratação pessoal mantém conta única e espaço próprio. A seleção de instituição do catálogo nacional cria referência de atuação no espaço pessoal. Não cria instituição oficial nova nem vínculo institucional confirmado. Nomes iguais não conciliam instituições. A gestão institucional mantém só os seus cadastros e não recebe pedidos pessoais.
- CPF de aluno pessoal: opcional, protegido e verificado. Revisar formalmente as constraints e os termos de T3 que o proíbem antes de habilitar a gravação. O formulário pessoal continua mínimo, sem importação silenciosa dos campos completos da escola. Posse dos dados pelo professor não dá poder irrestrito sobre o histórico de terceiros.
- Integração pessoal para escola: convite institucional aceito. O destino identifica o aluno por CPF conciliado ou por revisão manual de identidade provisória. O sistema mostra a comparação permitida. O operador escolhe reutilizar o cadastro local ou criar e completar os campos faltantes. Sem fusão automática por nome ou matrícula. Preservar a proveniência dos resultados do professor. Não apresentar nota pessoal como oficial sem decisão explícita. Conflitos vão para conciliação com recibo e rollback por unidade.
- Saída da escola ou fim do contrato: conta, e-mail e outros vínculos permanecem. O vínculo escolar não vira dono dos registros da escola. O professor pode manter ou ativar o espaço pessoal e o histórico mínimo previamente autorizado e admitido, ligado às turmas, disciplinas e períodos da sua atuação. O acesso escolar encerrado não serve para exportar de forma retroativa. Dados completos e fatos futuros de outros professores ficam indisponíveis. A autorização de continuidade é resolvida antes do encerramento, com prévia do que fica e do que não fica.

Cenários de aceite (docs/115 §13):
- Professor pessoal sem CPF só integra após conciliação. Conflito não sobrescreve aluno da escola.
- Saída da escola preserva a conta pessoal e só os dados mínimos e o histórico admitidos, sem acesso aos fatos futuros da escola.

## 7. Interface
Rótulo "Professor independente" na apresentação (hoje "Professor autônomo" em `Acesso.php`, `EmissaoDocumento` e cabeçalho "Uso autônomo"). Seleção de instituição no formulário de turma pessoal; comparação e escolha na integração; prévia do que fica e do que não fica na saída. Revisão UI/UX obrigatória.

## 8. Riscos e decisões pendentes do responsável
| Item | Opções | Recomendação | Decisão |
|---|---|---|---|
| §4 item 6 — matriz de permissão ID1 e comprovação admitida para associar cadastros | Aprovar | A do plano mestre | Pendente |
| §4 item 12 — contrato de conciliação de identidade | Subfase de FN-016 na Onda 1 | A do plano mestre | Pendente |
| Revisão formal de T3 (CPF no aluno pessoal) | Manter proibição / CPF opcional | Decidir antes de PA1-B | Pendente |
| Rótulo "Professor independente" × "escola autônoma" (FN-029) × "Uso autônomo" nos documentos | Padronizar | Decidir na revisão UI/UX | Pendente |
| Nota pessoal apresentada como oficial na integração | — | Exigir decisão explícita (docs/115) | Pendente |

## 9. Ramos desta entrega
Nenhum.

## 10. Histórico
| Data | Mudança | Por |
|---|---|---|
| 2026-11-09 | Documento criado a partir do inventário e do código | lucasgenial |
